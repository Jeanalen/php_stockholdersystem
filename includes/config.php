<?php
// 1. Error Reporting & Session
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Database Configuration
// Using getenv() to pull from Railway Variables
define('DB_HOST', getenv('DB_HOST') ?: 'gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com');
define('DB_PORT', getenv('DB_PORT') ?: 4000);
define('DB_USER', getenv('DB_USER') ?: '2B6tDnXn3qLev5o.root');
define('DB_PASS', getenv('DB_PASS') ?: 'vDPhV7S3MvCQ36Pf'); 
define('DB_NAME', getenv('DB_NAME') ?: 'stockholder_db');

// 3. Initialize Connection with SSL (Required for TiDB)
$conn = mysqli_init();

// Path to CA Cert for Railway environments
$ssl_ca = '/etc/ssl/certs/ca-certificates.crt';
if (!file_exists($ssl_ca)) { 
    $ssl_ca = NULL; 
}

mysqli_ssl_set($conn, NULL, NULL, $ssl_ca, NULL, NULL);

// Set a connection timeout to prevent hanging
mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 10);

$success = mysqli_real_connect(
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
    die("❌ Connection failed: " . mysqli_connect_error());
}

// 4. Create tables if they do not exist
$tables_sql = "
CREATE TABLE IF NOT EXISTS stockholders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255),
    email VARCHAR(100),
    phone VARCHAR(20),
    type ENUM('Individual', 'Corporate') DEFAULT 'Individual',
    tax_id VARCHAR(50),
    shares DECIMAL(10, 2) NOT NULL DEFAULT 0,
    share_percentage DECIMAL(5, 2) NOT NULL DEFAULT 0,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS dividends (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stockholder_id INT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    distribution_date DATE,
    status ENUM('Pending', 'Paid') DEFAULT 'Pending',
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stockholder_id) REFERENCES stockholders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stockholder_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('Present', 'Absent', 'Excused') DEFAULT 'Present',
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stockholder_id) REFERENCES stockholders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS proxies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stockholder_id INT NOT NULL,
    proxy_name VARCHAR(100),
    proxy_email VARCHAR(100),
    proxy_phone VARCHAR(20),
    authorization_date DATE,
    expiry_date DATE,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stockholder_id) REFERENCES stockholders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT,
    admin_name VARCHAR(100),
    action_type VARCHAR(50),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";

// Execute table creation
if ($conn->multi_query($tables_sql)) {
    while ($conn->next_result()) {;}
}

// 5. Utility Functions
function getAllStockholders($conn) {
    $sql = "SELECT * FROM stockholders ORDER BY created_date DESC";
    $result = $conn->query($sql);
    return ($result) ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getStockholderById($conn, $id) {
    $sql = "SELECT * FROM stockholders WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function addStockholder($conn, $name, $address, $email, $phone, $type, $tax_id, $shares) {
    $sql = "INSERT INTO stockholders (name, address, email, phone, type, tax_id, shares) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssd", $name, $address, $email, $phone, $type, $tax_id, $shares);
    return $stmt->execute();
}

function getTotalShares($conn) {
    $sql = "SELECT SUM(shares) as total FROM stockholders WHERE status = 'Active'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

function getTotalDividends($conn) {
    $sql = "SELECT SUM(amount) as total FROM dividends WHERE status = 'Paid'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

function getActiveStockholders($conn) {
    $sql = "SELECT COUNT(*) as count FROM stockholders WHERE status = 'Active'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['count'] ?? 0;
}

function getAdminName() {
    return $_SESSION['admin_name'] ?? 'Administrator';
}

function logActivity($conn, $action_type, $description) {
    $admin_id = $_SESSION['admin_id'] ?? 0; 
    $admin_name = $_SESSION['admin_name'] ?? 'System';
    $stmt = $conn->prepare("INSERT INTO activity_logs (admin_id, admin_name, action_type, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $admin_id, $admin_name, $action_type, $description);
    $stmt->execute();
    $stmt->close();
}
?>
