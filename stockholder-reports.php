<?php 
include 'includes/config.php';
include 'includes/auth.php'; // Add this line to load getAdminName()
requireLogin();              // Optional: Ensures only logged-in admins see this page

$success_msg = '';
$error_msg = '';

// Handle dividend submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_dividend') {
    $stockholder_id = $_POST['stockholder_id'] ?? null;
    $amount = $_POST['amount'] ?? 0;
    $distribution_date = $_POST['distribution_date'] ?? '';

    if ($stockholder_id && $amount > 0 && $distribution_date) {
        if (addDividend($conn, $stockholder_id, $amount, $distribution_date)) {
            $success_msg = "Dividend recorded successfully!";
        } else {
            $error_msg = "Error recording dividend: " . $conn->error;
        }
    } else {
        $error_msg = "Please fill in all required fields!";
    }
}

$stockholders = getAllStockholders($conn);
$total_shares = getTotalShares($conn);
$dividends = getAllDividends($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Stockholders System</title>
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
                <li class="nav-item"><a href="stockholder-dashboard.php">Home</a></li>
                <li class="nav-item"><a href="stockholder-attendance.php">Registration & Attendance</a></li>
                <li class="nav-item"><a href="stockholder-reports.php" class="active">Reports</a></li>

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
        <!-- Report Header -->
        <div class="card">
            <div class="card-header">📊 Reports & Analytics</div>
            <p>View detailed reports on shares, dividends, and stockholder information.</p>
        </div>

        <!-- Stats Overview -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-label">Total Stockholders</div>
                <div class="stat-number"><?php echo count($stockholders); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Shares</div>
                <div class="stat-number"><?php echo number_format($total_shares, 2); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Dividends Paid</div>
                <div class="stat-number">$<?php echo number_format(getTotalDividends($conn), 2); ?></div>
            </div>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- Stockholders with Share Percentage -->
        <div class="card">
            <div class="card-header">Stockholder Share Distribution</div>
            
            <?php if (count($stockholders) > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Shares</th>
                                <th>Share %</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stockholders as $sh):
                                $percentage = $total_shares > 0 ? ($sh['shares'] / $total_shares) * 100 : 0;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sh['name']); ?></td>
                                    <td><?php echo htmlspecialchars($sh['email']); ?></td>
                                    <td><?php echo htmlspecialchars($sh['type']); ?></td>
                                    <td><?php echo number_format($sh['shares'], 2); ?></td>
                                    <td>
                                        <div style="width: 100px; background: #ecf0f1; border-radius: 5px; overflow: hidden;">
                                            <div style="width: <?php echo $percentage; ?>%; background: linear-gradient(90deg, #3498db, #2980b9); height: 20px; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: bold;">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $sh['status'] == 'Active' ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo $sh['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <div class="empty-state-text">No stockholders found</div>
                </div>
            <?php endif; ?>
        </div>

    <script src="js/form-validation.js"></script>
</body>

</html>
