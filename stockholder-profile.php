<?php 
include 'includes/config.php';
include 'includes/auth.php';
requireLogin();

// Ensure only stockholders can access this specific layout
if ($_SESSION['role'] !== 'stockholder') {
    header('Location: stockholder-dashboard.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Fetch stockholder details from database
$stmt = $conn->prepare("SELECT * FROM stockholders WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("Profile not found. Debug: Searching for ID " . htmlspecialchars($user_id));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Stockholders System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
        .info-group { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .info-label { font-weight: bold; color: #667eea; font-size: 12px; text-transform: uppercase; }
        .info-value { font-size: 18px; color: #333; margin-top: 5px; }
        .profile-avatar { 
            background: #667eea; color: white; width: 100px; height: 100px; 
            border-radius: 50%; display: flex; align-items: center; 
            justify-content: center; font-size: 40px; margin: 0 auto 20px;
        }
    </style>
</head>
<body>
    <nav>
        <div class="navbar-container">
            <div class="navbar-brand">📊 University of Bohol | Stockholders' System</div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="stockholder-dashboard.php">Home</a></li>
                <li class="nav-item"><a href="stockholder-attendance.php">Attendance</a></li>
                <li class="nav-item"><a href="stockholder-reports.php">Reports</a></li>
                <li class="nav-item"><a href="stockholder-profile.php" class="active">My Profile</a></li>
                
                <li class="nav-item" style="margin-top: auto; padding: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
                    <div style="color: white; margin-bottom: 10px; font-size: 14px;">
                        👤 <?php echo htmlspecialchars($user['name']); ?>
                    </div>
                    <a href="logout.php" style="color: #ff6b6b; padding: 0; display: inline;">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">My Personal Profile</div>
            <div class="profile-grid" style="margin-top: 30px;">
                <div style="text-align: center; border-right: 1px solid #eee;">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                    <p class="badge badge-success"><?php echo $user['type']; ?> Stockholder</p>
                </div>

                <div style="padding-left: 20px;">
                    <div class="info-group">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['name']); ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Email Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Mobile Number</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Current Shares</div>
                        <div class="info-value"><?php echo number_format($user['shares'], 2); ?> Units</div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Account Status</div>
                        <div class="info-value"><?php echo $user['status']; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>