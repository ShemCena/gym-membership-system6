<?php
/**
 * Database Configuration and Connection Class
 * Fitness Club Management System
 */
class Database {
    private $host = 'db4free.net';
    private $port = '3306';
    private $dbname = 'grok_gym_project';
    private $username = 'grok_gym_user';
    private $password = 'GrokGym2025SecurePass!';
    private $charset = 'utf8mb4';
   
    public $pdo;
   
    public function __construct() {
        $this->connect();
    }
   
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
           
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
   
    public function getConnection() {
        return $this->pdo;
    }
   
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
   
    public function commit() {
        return $this->pdo->commit();
    }
   
    public function rollback() {
        return $this->pdo->rollback();
    }
   
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}
?>
