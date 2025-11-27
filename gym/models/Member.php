<?php
/**
 * Member Model
 * Gym Membership Management System
 */

require_once __DIR__ . '/../config/database.php';

class Member {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function create($data) {
        try {
            $stmt = $this->db->pdo->prepare("
                INSERT INTO members (full_name, email, phone, address, member_type, photo, join_date, expiry_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $data['full_name'],
                $data['email'],
                $data['phone'],
                $data['address'],
                $data['member_type'],
                $data['photo'] ?? null,
                $data['join_date'],
                $data['expiry_date'],
                $data['status']
            ]);
        } catch (PDOException $e) {
            error_log("Create member error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAll($search = '', $status = '', $memberType = '', $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            $where = [];
            $params = [];
            
            if ($search) {
                $where[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status) {
                $where[] = "status = ?";
                $params[] = $status;
            }
            
            if ($memberType) {
                $where[] = "member_type = ?";
                $params[] = $memberType;
            }
            
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM members $whereClause";
            $stmt = $this->db->pdo->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];
            
            // Get members
            $sql = "SELECT * FROM members $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $stmt = $this->db->pdo->prepare($sql);
            $stmt->execute(array_merge($params, [$limit, $offset]));
            $members = $stmt->fetchAll();
            
            return [
                'members' => $members,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ];
        } catch (PDOException $e) {
            error_log("Get members error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getById($id) {
        try {
            $stmt = $this->db->pdo->prepare("SELECT * FROM members WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get member by ID error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getByEmail($email) {
        try {
            $stmt = $this->db->pdo->prepare("SELECT * FROM members WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get member by email error: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($id, $data) {
        try {
            $stmt = $this->db->pdo->prepare("
                UPDATE members 
                SET full_name = ?, email = ?, phone = ?, address = ?, member_type = ?, 
                    photo = ?, join_date = ?, expiry_date = ?, status = ? 
                WHERE id = ?
            ");
            return $stmt->execute([
                $data['full_name'],
                $data['email'],
                $data['phone'],
                $data['address'],
                $data['member_type'],
                $data['photo'] ?? null,
                $data['join_date'],
                $data['expiry_date'],
                $data['status'],
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Update member error: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        try {
            // Check if member has payments or attendance records
            $stmt = $this->db->pdo->prepare("
                SELECT COUNT(*) as count FROM payments WHERE member_id = ?
                UNION
                SELECT COUNT(*) as count FROM attendance WHERE member_id = ?
            ");
            $stmt->execute([$id, $id]);
            $results = $stmt->fetchAll();
            
            $hasRecords = false;
            foreach ($results as $result) {
                if ($result['count'] > 0) {
                    $hasRecords = true;
                    break;
                }
            }
            
            if ($hasRecords) {
                // Don't allow deletion if member has records
                return false;
            }
            
            // Delete member
            $stmt = $this->db->pdo->prepare("DELETE FROM members WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Delete member error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getStatistics() {
        try {
            $stats = [];
            
            // Total members
            $stmt = $this->db->pdo->prepare("SELECT COUNT(*) as total FROM members");
            $stmt->execute();
            $stats['total_members'] = $stmt->fetch()['total'];
            
            // Active members
            $stmt = $this->db->pdo->prepare("SELECT COUNT(*) as count FROM members WHERE status = 'Active'");
            $stmt->execute();
            $stats['active_members'] = $stmt->fetch()['count'];
            
            // Expired members
            $stmt = $this->db->pdo->prepare("SELECT COUNT(*) as count FROM members WHERE status = 'Expired'");
            $stmt->execute();
            $stats['expired_members'] = $stmt->fetch()['count'];
            
            // Expiring soon (within 30 days)
            $stmt = $this->db->pdo->prepare("
                SELECT COUNT(*) as count FROM members 
                WHERE status = 'Active' AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $stats['expiring_soon'] = $stmt->fetch()['count'];
            
            // New members this month
            $stmt = $this->db->pdo->prepare("
                SELECT COUNT(*) as count FROM members 
                WHERE MONTH(join_date) = MONTH(CURDATE()) AND YEAR(join_date) = YEAR(CURDATE())
            ");
            $stmt->execute();
            $stats['new_this_month'] = $stmt->fetch()['count'];
            
            // Members by type
            $stmt = $this->db->pdo->prepare("
                SELECT member_type, COUNT(*) as count 
                FROM members 
                GROUP BY member_type
            ");
            $stmt->execute();
            $stats['by_type'] = $stmt->fetchAll();
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Get statistics error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getExpiringSoon($days = 30) {
        try {
            $stmt = $this->db->pdo->prepare("
                SELECT * FROM members 
                WHERE status = 'Active' AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY expiry_date ASC
            ");
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get expiring soon error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateExpiredStatus() {
        try {
            $stmt = $this->db->pdo->prepare("
                UPDATE members 
                SET status = 'Expired' 
                WHERE status = 'Active' AND expiry_date < CURDATE()
            ");
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update expired status error: " . $e->getMessage());
            return false;
        }
    }
}
?>
