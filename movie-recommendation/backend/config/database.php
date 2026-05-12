<?php
// Configuração do banco de dados - NUNCA commitar credenciais reais em produção
class DatabaseConfig {
    private static $instance = null;
    private $connection;
    
    // Configurações (alterar conforme seu ambiente)
    private $host = 'localhost';  // Alterado: removido :3307
    private $port = '3307';       // Porta modificada do MySQL no XAMPP
    private $dbname = 'movie_recommendation';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false
            ];
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Service unavailable. Please try again later.");
        }
    }
    
    public static function getConnection() {
        if (self::$instance === null) {
            self::$instance = new DatabaseConfig();
        }
        return self::$instance->connection;
    }
    
    private function __clone() {}
    public function __wakeup() {}
}

function getDB() {
    return DatabaseConfig::getConnection();
}
?>