<?php
/**
 * Configurazione Database - Biblioteca Gobetti
 * Database: my_cattimanuel
 */

// Configurazione database
define('DB_HOST', 'localhost');
define('DB_NAME', 'my_cattimanuel');  // ← Database esistente
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Connessione PDO
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch(PDOException $e) {
            die("Errore di connessione al database: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // Previene clonazione
    private function __clone() {}
    
    // Previene unserialize
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Funzione helper per ottenere la connessione
function getDB() {
    return Database::getInstance()->getConnection();
}
?>
