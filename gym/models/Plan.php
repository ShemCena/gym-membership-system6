<?php

require_once __DIR__ . '/../config/database.php';

class Plan {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function create($data) {
        try {
            $stmt = $this->db->pdo->prepare("
                INSERT INTO plans (plan_name, duration_months, price, description) 
                VALUES (:plan_name, :duration_months, :price, :description)
            ");
            
            $stmt->bindParam(':plan_name', $data['plan_name']);
            $stmt->bindParam(':duration_months', $data['duration_months']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':description', $data['description']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Plan creation error: " . $e->getMessage());
            return false;
        }
    }

    public function getAll($search = '', $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            $whereClause = "";
            $params = [];
            
            if (!empty($search)) {
                $whereClause = "WHERE plan_name LIKE :search OR description LIKE :search";
                $params[':search'] = '%' . $search . '%';
            }
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM plans $whereClause";
            $countStmt = $this->db->pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get plans with pagination
            $sql = "SELECT * FROM plans $whereClause ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->db->pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'plans' => $plans,
                'total' => $total,
                'page' => $page,
                'total_pages' => ceil($total / $limit)
            ];
        } catch (PDOException $e) {
            error_log("Plan getAll error: " . $e->getMessage());
            return false;
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->pdo->prepare("SELECT * FROM plans WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Plan getById error: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data) {
        try {
            $stmt = $this->db->pdo->prepare("
                UPDATE plans 
                SET plan_name = :plan_name, 
                    duration_months = :duration_months, 
                    price = :price, 
                    description = :description
                WHERE id = :id
            ");
            
            $stmt->bindParam(':plan_name', $data['plan_name']);
            $stmt->bindParam(':duration_months', $data['duration_months']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Plan update error: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            // Check if plan has existing payments
            $checkStmt = $this->db->pdo->prepare("SELECT COUNT(*) as count FROM payments WHERE plan_id = :id");
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            $paymentCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($paymentCount > 0) {
                return false; // Cannot delete plan with existing payments
            }
            
            $stmt = $this->db->pdo->prepare("DELETE FROM plans WHERE id = :id");
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Plan delete error: " . $e->getMessage());
            return false;
        }
    }

    public function getAllActive() {
        try {
            $stmt = $this->db->pdo->prepare("
                SELECT id, plan_name, price, duration_months 
                FROM plans 
                ORDER BY plan_name
            ");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Plan getAllActive error: " . $e->getMessage());
            return false;
        }
    }

    public function getAllForDropdown() {
        try {
            $stmt = $this->db->pdo->prepare("SELECT id, plan_name, price FROM plans ORDER BY plan_name");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Plan getAllForDropdown error: " . $e->getMessage());
            return false;
        }
    }

    public function getStatistics() {
        try {
            $stmt = $this->db->pdo->prepare("
                SELECT 
                    COUNT(*) as total_plans,
                    AVG(price) as avg_price,
                    MIN(price) as min_price,
                    MAX(price) as max_price
                FROM plans
            ");
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Plan getStatistics error: " . $e->getMessage());
            return false;
        }
    }
}
