<?php
// 1. Core Settings
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Database Variables (Pulls from Railway Env)
define('DB_HOST', getenv('DB_HOST') ?: 'gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com');
define('DB_PORT', getenv('DB_PORT') ?: 4000);
define('DB_USER', getenv('DB_USER') ?: '2B6tDnXn3qLev5o.root');
define('DB_PASS', getenv('DB_PASS') ?: 'zhprfBGhQO9t2xvL'); 
define('DB_NAME', getenv('DB_NAME') ?: 'stockholder_db');

$conn = mysqli_init();

// Try the two most common Linux SSL paths
if (file_exists('/etc/ssl/certs/ca-certificates.crt')) {
    $ssl_ca = '/etc/ssl/certs/ca-certificates.crt';
} elseif (file_exists('/etc/pki/tls/certs/ca-bundle.crt')) {
    $ssl_ca = '/etc/pki/tls/certs/ca-bundle.crt';
} else {
    $ssl_ca = NULL; // Fallback
}

mysqli_ssl_set($conn, NULL, NULL, $ssl_ca, NULL, NULL);

// This line is VITAL. It stops the "Failed to respond" hang after 5 seconds.
mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 5); 

$success = @mysqli_real_connect(
    $conn, 
    DB_HOST, 
    DB_USER, 
    DB_PASS, 
    DB_NAME, 
    DB_PORT, 
    NULL, 
    MYSQLI_CLIENT_SSL
);

if (!$success) {
    die("<h1>Database Connection Error</h1><p>" . mysqli_connect_error() . "</p>");
}

// 4. Helper Functions
function getAllStockholders($conn) {
    $result = $conn->query("SELECT * FROM stockholders ORDER BY created_date DESC");
    return ($result) ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getActiveStockholders($conn) {
    $result = $conn->query("SELECT COUNT(*) as count FROM stockholders WHERE status = 'Active'");
    $row = $result->fetch_assoc();
    return $row['count'] ?? 0;
}

function getTotalShares($conn) {
    $result = $conn->query("SELECT SUM(shares) as total FROM stockholders WHERE status = 'Active'");
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

function getTotalDividends($conn) {
    $result = $conn->query("SELECT SUM(amount) as total FROM dividends WHERE status = 'Paid'");
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

function getAdminName() {
    return $_SESSION['admin_name'] ?? 'Administrator';
}

// Minimal Table Setup (Only runs once if missing)
$conn->query("CREATE TABLE IF NOT EXISTS stockholders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    type VARCHAR(50),
    shares DECIMAL(15, 2) DEFAULT 0,
    status VARCHAR(20) DEFAULT 'Active',
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
?>
