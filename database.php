<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'food_ordering');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site Configuration
define('SITE_NAME', 'FoodOrder');
define('SITE_URL', 'http://localhost/03_restaurant');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection
class Database
{
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->conn;
    }
}

// Helper function to get database connection
function getDB()
{
    return Database::getInstance()->getConnection();
}

// Flash message functions
function setFlash($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Redirect function
function redirect($url)
{
    header("Location: $url");
    exit;
}

// Sanitize input
function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

// Format currency
function formatMoney($amount)
{
    return number_format($amount, 2) . ' บาท';
}

// Get base URL
function baseUrl($path = '')
{
    return SITE_URL . '/' . ltrim($path, '/');
}
?>