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
                <li class="nav-item"><a href="index.php">Home</a></li>
                <li class="nav-item"><a href="add-stockholder.php">Add Stockholder</a></li>
                <li class="nav-item"><a href="edit-stockholder.php">Edit Stockholder</a></li>
                <li class="nav-item"><a href="registration.php">Registration & Attendance</a></li>
                <li class="nav-item"><a href="proxy.php">Add/Edit Proxy</a></li>
                <li class="nav-item"><a href="history.php">History of Actions</a></li>
                <li class="nav-item"><a href="report.php" class="active">Reports</a></li>

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

        <!-- Add Dividend Form -->
        <div class="card wide">
            <div class="card-header">Record New Dividend</div>
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="add_dividend">

                <div class="form-group">
                    <label for="stockholder_id">Stockholder</label>
                    <select id="stockholder_id" name="stockholder_id" required>
                        <option value="">Select Stockholder</option>
                        <?php foreach ($stockholders as $sh): ?>
                            <option value="<?php echo $sh['id']; ?>"><?php echo htmlspecialchars($sh['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="amount">Amount ($)</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0" required placeholder="Enter amount">
                </div>

                <div class="form-group">
                    <label for="distribution_date">Distribution Date</label>
                    <input type="date" id="distribution_date" name="distribution_date" required>
                </div>

                <div class="form-submit">
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">💰 Record Dividend</button>
                    </div>
                </div>
            </form>
        </div>

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

        <!-- Dividends History -->
        <div class="card">
            <div class="card-header">Dividend Distribution History</div>
            
            <?php if (count($dividends) > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Stockholder</th>
                                <th>Amount</th>
                                <th>Distribution Date</th>
                                <th>Status</th>
                                <th>Recorded Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dividends as $div): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($div['name']); ?></td>
                                    <td>$<?php echo number_format($div['amount'], 2); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($div['distribution_date'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo $div['status'] == 'Paid' ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo $div['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($div['created_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">💳</div>
                    <div class="empty-state-text">No dividends recorded yet</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Print Report -->
        <div class="card" style="text-align: center;">
            <button onclick="window.print();" class="btn btn-primary">🖨️ Print Report</button>
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>

    <!-- Footer -->
    <!-- <footer>
        <p>&copy; 2026 University of Bohol Stockholders' System. All rights reserved.</p>
    </footer> -->

    <script src="js/form-validation.js"></script>
</body>

</html>
