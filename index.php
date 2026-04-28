<?php
// 1. Core Error Reporting for Railway Debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Include Configuration and Authentication
// Ensure these paths are correct relative to your root folder
require_once 'includes/config.php'; 
//require_once 'includes/auth.php';

// 3. Secure the Page
// Note: If you haven't finished auth.php yet, comment out requireLogin() to test the UI
//requireLogin(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University of Bohol | Annual Stockholders' Meeting</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav>
        <div class="navbar-container">
            <div class="navbar-brand">
                📊 University of Bohol | Stockholders' System
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="index.php" class="active">Home</a></li>
                <li class="nav-item"><a href="add-stockholder.php">Add Stockholder</a></li>
                <li class="nav-item"><a href="edit-stockholder.php">Edit Stockholder</a></li>
                <li class="nav-item"><a href="registration.php">Registration & Attendance</a></li>
                <li class="nav-item"><a href="proxy.php">Add/Edit Proxy</a></li>
                <li class="nav-item"><a href="history.php">History of Actions</a></li>
                <li class="nav-item"><a href="report.php">Reports</a></li>
                
                <li class="nav-item" style="margin-top: auto; padding: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
                    <div style="color: white; margin-bottom: 10px; font-size: 14px;">
                        👤 <?php echo htmlspecialchars(getAdminName()); ?>
                    </div>
                    <a href="logout.php" style="color: #ff6b6b; padding: 0; display: inline;">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">University of Bohol | Annual Stockholders Attendance System</div>
            <p>Welcome to the central management portal. Use the sidebar to manage records or view reports.</p>
            
            <div style="text-align: center; margin: 20px -25px;">
                <img src="images/UB.jpg" alt="University of Bohol" style="width: 100%; height: 400px; object-fit: cover; border-radius: 4px;">
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-label">Total Stockholders</div>
                <div class="stat-number">
                    <?php echo isset($conn) ? getActiveStockholders($conn) : '0'; ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Shares</div>
                <div class="stat-number">
                    <?php echo isset($conn) ? number_format(getTotalShares($conn), 2) : '0.00'; ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Dividends Paid</div>
                <div class="stat-number">
                    $<?php echo isset($conn) ? number_format(getTotalDividends($conn), 2) : '0.00'; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Recent Stockholders</div>
            <?php
            if (isset($conn)) {
                $stockholders = getAllStockholders($conn);
                if (!empty($stockholders)) {
                    echo '<table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Shares</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>';
                    $displayCount = 0;
                    foreach ($stockholders as $sh) {
                        if ($displayCount >= 5) break;
                        $statusClass = ($sh['status'] == 'Active') ? 'badge-success' : 'badge-danger';
                        echo '<tr>
                            <td>' . htmlspecialchars($sh['name']) . '</td>
                            <td>' . htmlspecialchars($sh['type']) . '</td>
                            <td>' . number_format($sh['shares'], 2) . '</td>
                            <td><span class="badge ' . $statusClass . '">' . htmlspecialchars($sh['status']) . '</span></td>
                            <td class="action-links">
                                <a href="edit-stockholder.php?edit=' . $sh['id'] . '">Edit</a>
                            </td>
                        </tr>';
                        $displayCount++;
                    }
                    echo '</tbody></table>';
                } else {
                    echo '<div class="empty-state" style="text-align:center; padding: 40px;">
                            <div style="font-size: 40px;">📭</div>
                            <p>No stockholders found in the system.</p>
                            <a href="add-stockholder.php" class="btn btn-primary">Add First Stockholder</a>
                          </div>';
                }
            } else {
                echo '<p style="color:red; font-weight:bold;">⚠️ Database Connection Offline.</p>';
            }
            ?>
        </div>

        <div class="card">
            <div class="card-header">Quick Actions</div>
            <div class="btn-group">
                <a href="add-stockholder.php" class="btn btn-primary">➕ Add Stockholder</a>
                <a href="registration.php" class="btn btn-secondary">📋 Attendance</a>
                <a href="report.php" class="btn btn-secondary">📊 Reports</a>
            </div>
        </div>
    </div>
</body>
</html>
