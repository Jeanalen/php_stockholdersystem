<?php
// 1. Enable Error Reporting (Crucial for debugging Railway errors)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Database Configuration
// Railway Variables will override these defaults automatically
define('DB_HOST', getenv('DB_HOST') ?: 'gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com');
define('DB_PORT', getenv('DB_PORT') ?: 4000);
define('DB_USER', getenv('DB_USER') ?: '2B6tDnXn3qLev5o.root');
define('DB_PASS', getenv('DB_PASS') ?: 'vDPhV7S3MvCQ36Pf'); 
define('DB_NAME', getenv('DB_NAME') ?: 'stockholder_db');

// 3. Establish Connection with SSL
$conn = mysqli_init();

// Path for Railway/Linux environments
$ssl_ca = is_file('/etc/ssl/certs/ca-certificates.crt') ? '/etc/ssl/certs/ca-certificates.crt' : NULL;
mysqli_ssl_set($conn, NULL, NULL, $ssl_ca, NULL, NULL);

// Connection with a 5-second timeout to prevent "Application failed to respond"
mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 5);

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
    die("❌ Database Connection Failed: " . mysqli_connect_error());
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
    // Clear results from multi_query
    while ($conn->next_result()) {;}
} else {
    error_log("Error creating tables: " . $conn->error);
}

// 5. Utility Functions
function getAllStockholders($conn) {
    $sql = "SELECT * FROM stockholders ORDER BY created_date DESC";
    $result = $conn->query($sql);
    $stockholders = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $stockholders[] = $row;
        }
    }
    return $stockholders;
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

function updateStockholder($conn, $id, $name, $address, $email, $phone, $type, $tax_id, $shares, $status) {
    $sql = "UPDATE stockholders SET name=?, address=?, email=?, phone=?, type=?, tax_id=?, shares=?, status=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssdsi", $name, $address, $email, $phone, $type, $tax_id, $shares, $status, $id);
    return $stmt->execute();
}

function deleteStockholder($conn, $id) {
    $sql = "DELETE FROM stockholders WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

function addDividend($conn, $stockholder_id, $amount, $distribution_date) {
    $sql = "INSERT INTO dividends (stockholder_id, amount, distribution_date) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ids", $stockholder_id, $amount, $distribution_date);
    return $stmt->execute();
}

function getDividendsByStockholder($conn, $stockholder_id) {
    $sql = "SELECT * FROM dividends WHERE stockholder_id = ? ORDER BY distribution_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $stockholder_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getAllDividends($conn) {
    $sql = "SELECT d.*, s.name FROM dividends d JOIN stockholders s ON d.stockholder_id = s.id ORDER BY d.distribution_date DESC";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function addAttendance($conn, $stockholder_id, $attendance_date, $status) {
    $sql = "INSERT INTO attendance (stockholder_id, attendance_date, status) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $stockholder_id, $attendance_date, $status);
    return $stmt->execute();
}

function getAttendanceByStockholder($conn, $stockholder_id) {
    $sql = "SELECT * FROM attendance WHERE stockholder_id = ? ORDER BY attendance_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $stockholder_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getAttendanceByDate($conn, $date) {
    $sql = "SELECT a.*, s.name FROM attendance a JOIN stockholders s ON a.stockholder_id = s.id WHERE a.attendance_date = ? ORDER BY s.name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function addProxy($conn, $stockholder_id, $proxy_name, $proxy_email, $proxy_phone, $authorization_date, $expiry_date) {
    $sql = "INSERT INTO proxies (stockholder_id, proxy_name, proxy_email, proxy_phone, authorization_date, expiry_date) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssss", $stockholder_id, $proxy_name, $proxy_email, $proxy_phone, $authorization_date, $expiry_date);
    return $stmt->execute();
}

function getProxiesByStockholder($conn, $stockholder_id) {
    $sql = "SELECT * FROM proxies WHERE stockholder_id = ? ORDER BY created_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $stockholder_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getAllProxies($conn) {
    $sql = "SELECT p.*, s.name FROM proxies p JOIN stockholders s ON p.stockholder_id = s.id ORDER BY p.created_date DESC";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function updateProxy($conn, $id, $proxy_name, $proxy_email, $proxy_phone, $authorization_date, $expiry_date, $status) {
    $sql = "UPDATE proxies SET proxy_name=?, proxy_email=?, proxy_phone=?, authorization_date=?, expiry_date=?, status=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $proxy_name, $proxy_email, $proxy_phone, $authorization_date, $expiry_date, $status, $id);
    return $stmt->execute();
}

function deleteProxy($conn, $id) {
    $sql = "DELETE FROM proxies WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
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

function logActivity($conn, $action_type, $description) {
    $admin_id = $_SESSION['admin_id'] ?? 0; 
    $admin_name = $_SESSION['admin_name'] ?? 'System';

    $stmt = $conn->prepare("INSERT INTO activity_logs (admin_id, admin_name, action_type, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $admin_id, $admin_name, $action_type, $description);
    $stmt->execute();
    $stmt->close();
}
?>
