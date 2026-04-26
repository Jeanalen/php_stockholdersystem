<?php
include 'includes/config.php'; 
include 'includes/auth.php';
requireLogin();
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
    <!-- Navigation -->
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

    <!-- Main Content -->
    <div class="container">
        <div class="card">
            <div class="card-header">University of Bohol | Annual Stockholders Attendance System</div>
            <p>This system allows you to:</p>
            <ul style="margin-left: 20px; line-height: 2;">
                <li>Register stockholders and record attendance</li>
                <li>Manage proxies</li>
                <li>Generate attendance reports</li>
            </ul>
            <p style="margin-top: 20px;">Select an option from the menu to get started.</p>

            <div style="text-align: center; margin-top: 20px; margin-left: -25px; margin-right: -25px;">
                <img src="images/UB.jpg" alt="University of Bohol" style="width: 100%; height: 600px; object-fit: cover; border-radius: 0 0 0 0;">
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-label">Total Stockholders</div>
                <div class="stat-number">
                    <?php echo getActiveStockholders($conn); ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Shares</div>
                <div class="stat-number">
                    <?php echo number_format(getTotalShares($conn), 2); ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Dividends Paid</div>
                <div class="stat-number">
                    $<?php echo number_format(getTotalDividends($conn), 2); ?>
                </div>
            </div>
        </div>

        <!-- Recent Stockholders -->
        <div class="card">
            <div class="card-header">Recent Stockholders</div>
            <?php
            $stockholders = getAllStockholders($conn);
            if (count($stockholders) > 0) {
                echo '<table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Shares</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>';
                $count = 0;
                foreach ($stockholders as $sh) {
                    if ($count >= 5) break;
                    $badge_class = $sh['status'] == 'Active' ? 'badge-success' : 'badge-danger';
                    echo '<tr>
                        <td>' . htmlspecialchars($sh['name']) . '</td>
                        <td>' . htmlspecialchars($sh['email']) . '</td>
                        <td>' . htmlspecialchars($sh['phone']) . '</td>
                        <td>' . htmlspecialchars($sh['type']) . '</td>
                        <td>' . number_format($sh['shares'], 2) . '</td>
                        <td><span class="badge ' . $badge_class . '">' . $sh['status'] . '</span></td>
                        <td class="action-links">
                            <a href="edit-stockholder.php?edit=' . $sh['id'] . '">Edit</a>
                        </td>
                    </tr>';
                    $count++;
                }
                echo '</tbody></table>';
            } else {
                echo '<div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <div class="empty-state-text">No stockholders found</div>
                    <a href="add-stockholder.php" class="btn btn-primary">Add First Stockholder</a>
                </div>';
            }
            ?>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">Quick Actions</div>
            <div class="btn-group">
                <a href="add-stockholder.php" class="btn btn-primary">➕ Add New Stockholder</a>
                <a href="edit-stockholder.php" class="btn btn-secondary">📝 View All Stockholders</a>
                <a href="registration.php" class="btn btn-secondary">📋 Registration</a>
                <a href="proxy.php" class="btn btn-secondary">👤 Manage Proxies</a>
                <a href="report.php" class="btn btn-secondary">📊 View Reports</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <!-- <footer>
        <p>&copy; 2026 University of Bohol Stockholders' System. All rights reserved.</p>
    </footer> -->
</body>

</html>