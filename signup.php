<?php
session_start();
include_once 'includes/config.php'; 
include_once 'includes/auth.php';
requireLogin();

$error = '';
$success = '';

// FIX 1: Initialize all variables to empty strings at the top.
// This prevents the "Undefined variable" warning on the first page load.
$name = $email = $phone = $address = $type = $shares = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form data
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $type = $_POST['type'] ?? '';
    $shares = $_POST['shares'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($type) || empty($shares) || empty($password)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        // Check if email already exists
        $check_query = "SELECT id FROM stockholders WHERE email = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            // FIX 2: Hash the password and set default status
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $status = 'Active';
            $date_registered = date('Y-m-d H:i:s');

            // FIX 3: Ensure the query matches your database columns
            $insert_query = "INSERT INTO stockholders (name, email, phone, address, type, shares, password, status, date_registered) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $insert_stmt = $conn->prepare($insert_query);
            
            // Check if prepare() succeeded
            if ($insert_stmt) {
                // "sssssdsss" means: string, string, string, string, string, double(number), string, string, string
                $insert_stmt->bind_param("sssssdsss", $name, $email, $phone, $address, $type, $shares, $hashed_password, $status, $date_registered);

                if ($insert_stmt->execute()) {
                    $success = 'Registration successful! You can now log in.';
                    // Optional: Reset fields after success
                    $name = $email = $phone = $address = $type = $shares = '';
                } else {
                    $error = 'Registration failed: ' . $insert_stmt->error;
                }
                $insert_stmt->close();
            } else {
                $error = 'Database error: Unable to prepare the request.';
            }
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stockholder Registration | University of Bohol</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 40px 20px; }
        .signup-card { background: white; padding: 40px; border-radius: 20px; max-width: 800px; margin: 0 auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; font-size: 13px; margin-bottom: 8px; color: #444; }
        .form-group input, .form-group select, .form-group textarea { padding: 12px; border: 2px solid #e5e5e5; border-radius: 10px; font-size: 14px; }
        .error-message { background: #fff5f5; color: #c53030; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 5px solid #c53030; }
        .success-message { background: #f0fdf4; color: #16a34a; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 5px solid #16a34a; }
        .signup-btn { width: 100%; padding: 15px; background: #667eea; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 700; font-size: 16px; transition: 0.3s; }
        .signup-btn:hover { background: #5a6fd6; }
        .footer-link { text-align: center; margin-top: 20px; }
        @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="signup-card">
    <h2 style="text-align: center; color: #333;">Stockholder Registration</h2>
    <p style="text-align: center; color: #777; margin-bottom: 30px;">Create your account to manage your shares and attendance.</p>

    <?php if ($error): ?>
        <div class="error-message">⚠️ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success-message">✓ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" action="signup.php">
        <div class="form-row">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>
            <div class="form-group">
                <label>Stockholder Type *</label>
                <select name="type" required>
                    <option value="">Select Type...</option>
                    <option value="Individual" <?php echo ($type === 'Individual') ? 'selected' : ''; ?>>Individual</option>
                    <option value="Corporate" <?php echo ($type === 'Corporate') ? 'selected' : ''; ?>>Corporate</option>
                    <option value="Foundation" <?php echo ($type === 'Foundation') ? 'selected' : ''; ?>>Foundation</option>
                    <option value="Institutional" <?php echo ($type === 'Institutional') ? 'selected' : ''; ?>>Institutional</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <div class="form-group">
                <label>Phone Number *</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label>Address *</label>
            <textarea name="address" rows="2" required><?php echo htmlspecialchars($address); ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Number of Shares *</label>
                <input type="number" step="0.01" name="shares" value="<?php echo htmlspecialchars($shares); ?>" required>
            </div>
            <div class="form-group">
                </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" required>
            </div>
        </div>

        <button type="submit" class="signup-btn">Register Account</button>
    </form>

    <div class="footer-link">
        <p>Already have an account? <a href="login.php" style="color: #667eea; font-weight: 600;">Login here</a></p>
    </div>
</div>

</body>
</html>