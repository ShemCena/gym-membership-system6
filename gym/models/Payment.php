<?php
/**
 * Payment Model
 * Gym Membership Management System
 */

require_once __DIR__ . '/../config/database.php';

class Payment {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            // Insert payment record
            $stmt = $this->db->pdo->prepare("
                INSERT INTO payments (member_id, plan_id, original_amount, discount_percent, discount_amount, final_amount, payment_date, paid_by, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $paymentResult = $stmt->execute([
                $data['member_id'],
                $data['plan_id'],
                $data['original_amount'],
                $data['discount_percent'],
                $data['discount_amount'],
                $data['final_amount'],
                $data['payment_date'],
                $data['paid_by'],
                $data['notes'] ?? null
            ]);
            
            if (!$paymentResult) {
                $this->db->rollback();
                return false;
            }
            
            // Update member expiry date
            $stmt = $this->db->pdo->prepare("
                UPDATE members 
                SET expiry_date = DATE_ADD(expiry_date, INTERVAL (SELECT duration_months FROM plans WHERE id = ?) MONTH),
                    status = 'Active'
                WHERE id = ?
            ");
            $memberResult = $stmt->execute([$data['plan_id'], $data['member_id']]);
            
            if (!$memberResult) {
                $this->db->rollback();
                return false;
            }
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollback();
            error_log("Create payment error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAll($search = '', $memberId = '', $planId = '', $startDate = '', $endDate = '', $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            $where = [];
            $params = [];
            
            if ($search) {
                $where[] = "(m.full_name LIKE ? OR p.plan_name LIKE ? OR pm.paid_by LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($memberId) {
                $where[] = "pm.member_id = ?";
                $params[] = $memberId;
            }
            
            if ($planId) {
                $where[] = "pm.plan_id = ?";
                $params[] = $planId;
            }
            
            if ($startDate) {
                $where[] = "pm.payment_date >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $where[] = "pm.payment_date <= ?";
                $params[] = $endDate;
            }
            
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Get total count
            $countSql = "
                SELECT COUNT(*) as total 
                FROM payments pm
                JOIN members m ON pm.member_id = m.id
                JOIN plans p ON pm.plan_id = p.id
                $whereClause
            ";
            $stmt = $this->db->pdo->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];
            
            // Get payments with member and plan details
            $sql = "
                SELECT pm.*, m.full_name, m.email, m.member_type, p.plan_name, p.duration_months
                FROM payments pm
                JOIN members m ON pm.member_id = m.id
                JOIN plans p ON pm.plan_id = p.id
                $whereClause
                ORDER BY pm.payment_date DESC, pm.created_at DESC
                LIMIT ? OFFSET ?
            ";
            $stmt = $this->db->pdo->prepare($sql);
            $stmt->execute(array_merge($params, [$limit, $offset]));
            $payments = $stmt->fetchAll();
            
            return [
                'payments' => $payments,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ];
        } catch (PDOException $e) {
            error_log("Get payments error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getById($id) {
        try {
            $stmt = $this->db->pdo->prepare("
                SELECT pm.*, m.full_name, m.email, m.member_type, p.plan_name, p.duration_months
                FROM payments pm
                JOIN members m ON pm.member_id = m.id
                JOIN plans p ON pm.plan_id = p.id
                WHERE pm.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get payment by ID error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getByMemberId($memberId) {
        try {
            $stmt = $this->db->pdo->prepare("
                SELECT pm.*, p.plan_name, p.duration_months
                FROM payments pm
                JOIN plans p ON pm.plan_id = p.id
                WHERE pm.member_id = ?
                ORDER BY pm.payment_date DESC
            ");
            $stmt->execute([$memberId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get payments by member ID error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getStatistics() {
        try {
            $stats = [];
            
            // Total revenue
            $stmt = $this->db->pdo->prepare("SELECT SUM(final_amount) as total FROM payments");
            $stmt->execute();
            $stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;
            
            // Revenue this month
            $stmt = $this->db->pdo->prepare("
                SELECT SUM(final_amount) as total FROM payments 
                WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())
            ");
            $stmt->execute();
            $stats['revenue_this_month'] = $stmt->fetch()['total'] ?? 0;
            
            // Revenue today
            $stmt = $this->db->pdo->prepare("
                SELECT SUM(final_amount) as total FROM payments 
                WHERE payment_date = CURDATE()
            ");
            $stmt->execute();
            $stats['revenue_today'] = $stmt->fetch()['total'] ?? 0;
            
            // Total payments
            $stmt = $this->db->pdo->prepare("SELECT COUNT(*) as total FROM payments");
            $stmt->execute();
            $stats['total_payments'] = $stmt->fetch()['total'];
            
            // Average payment amount
            $stmt = $this->db->pdo->prepare("SELECT AVG(final_amount) as avg FROM payments");
            $stmt->execute();
            $stats['average_payment'] = $stmt->fetch()['avg'] ?? 0;
            
            // Revenue by month (last 6 months)
            $stmt = $this->db->pdo->prepare("
                SELECT 
                    DATE_FORMAT(payment_date, '%Y-%m') as month,
                    SUM(final_amount) as revenue,
                    COUNT(*) as payment_count
                FROM payments 
                WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
                ORDER BY month ASC
            ");
            $stmt->execute();
            $stats['monthly_revenue'] = $stmt->fetchAll();
            
            // Revenue by plan
            $stmt = $this->db->pdo->prepare("
                SELECT p.plan_name, SUM(pm.final_amount) as revenue, COUNT(*) as payment_count
                FROM payments pm
                JOIN plans p ON pm.plan_id = p.id
                GROUP BY p.id, p.plan_name
                ORDER BY revenue DESC
            ");
            $stmt->execute();
            $stats['revenue_by_plan'] = $stmt->fetchAll();
            
            // Discount statistics
            $stmt = $this->db->pdo->prepare("
                SELECT 
                    SUM(discount_amount) as total_discounts,
                    AVG(discount_percent) as avg_discount_percent
                FROM payments
                WHERE discount_percent > 0
            ");
            $stmt->execute();
            $result = $stmt->fetch();
            $stats['total_discounts'] = $result['total_discounts'] ?? 0;
            $stats['avg_discount_percent'] = $result['avg_discount_percent'] ?? 0;
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Get payment statistics error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getMonthlyRevenue($year = null) {
        try {
            $year = $year ?? date('Y');
            
            $stmt = $this->db->pdo->prepare("
                SELECT 
                    MONTH(payment_date) as month,
                    SUM(final_amount) as revenue,
                    COUNT(*) as payment_count
                FROM payments 
                WHERE YEAR(payment_date) = ?
                GROUP BY MONTH(payment_date)
                ORDER BY month ASC
            ");
            $stmt->execute([$year]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get monthly revenue error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getTopMembers($limit = 10) {
        try {
            $stmt = $this->db->pdo->prepare("
                SELECT 
                    m.id, m.full_name, m.member_type,
                    COUNT(pm.id) as payment_count,
                    SUM(pm.final_amount) as total_spent
                FROM members m
                JOIN payments pm ON m.id = pm.member_id
                GROUP BY m.id, m.full_name, m.member_type
                ORDER BY total_spent DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get top members error: " . $e->getMessage());
            return false;
        }
    }
}
?>
