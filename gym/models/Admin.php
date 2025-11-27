<?php
/**
 * Admin Model
 * Gym Membership Management System
 */

require_once __DIR__ . '/../config/database.php';

class Admin {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function login($username, $password) {
        try {
            $stmt = $this->db->pdo->prepare("SELECT id, username, password_hash FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Regenerate session ID for security
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    public function logout() {
        // Destroy all session data
        session_destroy();
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
    }
    
    public function getCurrentAdmin() {
        if (isset($_SESSION['admin_id'])) {
            return [
                'id' => $_SESSION['admin_id'],
                'username' => $_SESSION['admin_username']
            ];
        }
        return null;
    }
    
    public function updatePassword($adminId, $currentPassword, $newPassword) {
        try {
            // Verify current password
            $stmt = $this->db->pdo->prepare("SELECT password_hash FROM admins WHERE id = ?");
            $stmt->execute([$adminId]);
            $admin = $stmt->fetch();
            
            if (!$admin || !password_verify($currentPassword, $admin['password_hash'])) {
                return false;
            }
            
            // Update password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
            return $stmt->execute([$newPasswordHash, $adminId]);
            
        } catch (PDOException $e) {
            error_log("Password update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function create($username, $password) {
        try {
            // Check if username already exists
            $stmt = $this->db->pdo->prepare("SELECT id FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                return false; // Username already exists
            }
            
            // Create new admin
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
            return $stmt->execute([$username, $passwordHash]);
            
        } catch (PDOException $e) {
            error_log("Admin creation error: " . $e->getMessage());
            return false;
        }
    }
}
?>
