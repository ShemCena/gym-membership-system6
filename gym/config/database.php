<?php
class Database {
    private $host = 'sql307.infinityfree.com';
    private $port = '3306';
    private $dbname = 'if0_37631348_gym';
    private $username = 'if0_37631348';
    private $password = 'GrokFixedYourProject123';
    private $charset = 'utf8mb4';
   
    public $pdo;
   
    public function __construct() { $this->connect(); }
   
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset={$this->charset}";
            $this->pdo = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
   
    public function getConnection() { return $this->pdo; }
}
?>
