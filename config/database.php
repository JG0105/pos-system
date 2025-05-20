<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'dbr4ccrunbea1c');
define('DB_USER', 'u8pspp1ggfdgc');
define('DB_PASS', 't7u434m97jzi');

// Application configuration
define('SITE_NAME', 'Gawler Irrigation POS');
define('SITE_URL', 'https://gawlerirrigation.com.au/pos');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Australia/Adelaide');

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>
