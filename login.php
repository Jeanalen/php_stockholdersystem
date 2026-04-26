<?php
session_start();
include 'includes/config.php'; // Ensure logActivity() is defined in here

$error = '';
$view = $_GET['view'] ?? 'select';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role_type = $_POST['role_type'] ?? '';

    $authenticated = false;

    // 1. Admin Authentication Logic
    if ($role_type === 'admin') {
        $admins = [
            ['username' => 'admin1', 'password' => 'Admin@2026', 'name' => 'Dr. Maria Santos'],
            ['username' => 'admin2', 'password' => 'SecurePass123', 'name' => 'Prof. John Cruz'],
            ['username' => 'admin3', 'password' => 'UBAdmin456', 'name' => 'Ms. Rosa Garcia'],
            ['username' => 'admin4', 'password' => 'StakeholderMgr789', 'name' => 'Mr. Carlos Reyes']
        ];

        foreach ($admins as $admin) {
            if ($username === $admin['username'] && $password === $admin['password']) {
                // Set Sessions FIRST
                $_SESSION['admin_id'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['role'] = 'admin'; 
                $_SESSION['logged_in'] = true;
                $authenticated = true;

                // RECORD THE LOGIN ACTION NOW
                // This must happen after sessions are set so the name is recorded correctly
                logActivity($conn, 'LOGIN', "Admin successfully logged into the system.");
                
                break;
            }
        }
    } 
    
    // 2. Stockholder Authentication Logic
    else if ($role_type === 'stockholder') {
        $query = "SELECT id, name, password FROM stockholders WHERE email = ? AND status = 'Active'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_name'] = $user['name'];
                $_SESSION['role'] = 'stockholder'; 
                $_SESSION['logged_in'] = true;
                $authenticated = true;
                // Note: Usually, you don't log stockholder logins in the Admin History
            }
        }
    }

    if ($authenticated) {
        if ($_SESSION['role'] === 'admin') {
            header('Location: index.php');
        } else {
            header('Location: stockholder-dashboard.php');
        }
        exit();
    } else {
        $error = 'Invalid credentials for the selected role.';
        $view = $role_type; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | University of Bohol</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center; justify-content: center;
            padding: 20px;
        }
        .login-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            width: 100%;
            max-width: 1000px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        .login-left {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 60px 40px;
            display: flex; flex-direction: column;
            justify-content: center; align-items: center; text-align: center;
        }
        .login-right { padding: 60px 40px; display: flex; flex-direction: column; justify-content: center; position: relative; }
        
        /* Selection Styles */
        .selection-container { display: flex; flex-direction: column; gap: 15px; margin-top: 20px; }
        .role-card {
            display: flex; align-items: center; padding: 20px;
            border: 2px solid #e5e5e5; border-radius: 15px;
            text-decoration: none; color: #333; transition: all 0.3s ease;
        }
        .role-card:hover { border-color: #667eea; background: #f8f9ff; transform: translateX(10px); }
        .role-icon { font-size: 24px; margin-right: 20px; background: #eee; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 10px; }
        .role-info h3 { font-size: 16px; margin: 0; }
        .role-info p { font-size: 12px; color: #777; }
        
        .back-link { position: absolute; top: 30px; left: 40px; color: #667eea; text-decoration: none; font-weight: 600; font-size: 13px; }

        /* Form Styles */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 12px; text-transform: uppercase; }
        .form-group input { width: 100%; padding: 12px; border: 2px solid #e5e5e5; border-radius: 8px; font-family: inherit; }
        .login-btn { width: 100%; padding: 14px; background: #667eea; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .error-message { background: #fff5f5; color: #c53030; padding: 12px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #c53030; font-size: 13px; }
        
        @media (max-width: 768px) { .login-wrapper { grid-template-columns: 1fr; } .login-left { display: none; } }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-left">
            <div style="font-size: 50px; margin-bottom: 20px;">🏫</div>
            <h2>University of Bohol</h2>
            <p>Stockholders' Attendance & Management System</p>
        </div>

        <div class="login-right">
            <?php if ($view === 'select'): ?>
                <h1>Welcome</h1>
                <p>Please select your role to proceed to login</p>
                <div class="selection-container">
                    <a href="?view=admin" class="role-card">
                        <div class="role-icon">🔑</div>
                        <div class="role-info">
                            <h3>Admin Login</h3>
                            <p>System management and reporting</p>
                        </div>
                    </a>
                    <a href="?view=stockholder" class="role-card">
                        <div class="role-icon">👤</div>
                        <div class="role-info">
                            <h3>Stockholder Login</h3>
                            <p>Personal portfolio and attendance</p>
                        </div>
                    </a>
                </div>
            <?php else: ?>
                <a href="?view=select" class="back-link">← Change Role</a>
                <h1 style="margin-top: 20px;"><?php echo ucfirst($view); ?> Login</h1>
                <p>Enter your credentials to access your dashboard</p>

                <?php if ($error): ?>
                    <div class="error-message">⚠️ <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <input type="hidden" name="role_type" value="<?php echo $view; ?>">
                    <div class="form-group">
                        <label><?php echo ($view === 'admin') ? 'Username' : 'Email Address'; ?></label>
                        <input type="text" name="username" required autofocus placeholder="Enter your credentials">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="••••••••">
                    </div>
                    <button type="submit" class="login-btn">Sign In as <?php echo ucfirst($view); ?></button>
                </form>
            <?php endif; ?>

            <!-- <div style="margin-top: 30px; text-align: center; border-top: 1px solid #eee; padding-top: 20px;">
                <p style="font-size: 13px; color: #666;">New Stockholder? <a href="signup.php" style="color: #667eea; font-weight: 600; text-decoration: none;">Register Here</a></p>
            </div> -->
        </div>
    </div>
</body>
</html>