<?php 
include 'includes/config.php';
include 'includes/auth.php'; // Add this line to load getAdminName()
requireLogin();              // Optional: Ensures only logged-in admins see this page

$success_msg = '';
$error_msg = '';

// Handle attendance/registration submission with status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'record_attendance') {
        $stockholder_id = $_POST['stockholder_id'] ?? null;
        $attendance_date = $_POST['attendance_date'] ?? date('Y-m-d');
        $status = $_POST['status'] ?? 'Present';

        if ($stockholder_id && $attendance_date) {
            if (addAttendance($conn, $stockholder_id, $attendance_date, $status)) {
                $success_msg = "Attendance recorded successfully! Status: " . $status;
            } else {
                $error_msg = "Error recording attendance: " . $conn->error;
            }
        } else {
            $error_msg = "Please select a stockholder and date!";
        }
    }
}

$stockholders = getAllStockholders($conn);
$today = date('Y-m-d');
$selected_date = $_GET['date'] ?? $today;
$attendance_records = getAttendanceByDate($conn, $selected_date);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration & Attendance - Stockholders System</title>
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
                <li class="nav-item"><a href="stockholder-attendance.php" class="active">Registration & Attendance</a></li>
                <li class="nav-item"><a href="stockholder-reports.php">Reports</a></li>
                
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
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="two-col-grid">
            <div class="card">
                <div class="card-header">Attendance for <?php echo date('F d, Y', strtotime($selected_date)); ?></div>
                <form method="GET" class="filter-form" style="margin-bottom: 15px;">
                    <div class="form-group">
                        <label for="dateFilter">Filter by Date:</label>
                        <input type="date" id="dateFilter" name="date" value="<?php echo $selected_date; ?>" onchange="this.form.submit()">
                    </div>
                </form>
                
                <?php if (count($attendance_records) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Stockholder Name</th>
                                    <th>Status</th>
                                    <th>Recorded Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $present_count = 0;
                                $absent_count = 0;
                                $excused_count = 0;
                                foreach ($attendance_records as $att):
                                    if ($att['status'] == 'Present') $present_count++;
                                    if ($att['status'] == 'Absent') $absent_count++;
                                    if ($att['status'] == 'Excused') $excused_count++;
                                    
                                    $status_class = match($att['status']) {
                                        'Present' => 'badge-success',
                                        'Absent' => 'badge-danger',
                                        'Excused' => 'badge-warning',
                                        default => 'badge-pending'
                                    };
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($att['name']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo $att['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('H:i', strtotime($att['created_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Attendance Summary -->
                    <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 8px;">
                        <strong>Attendance Summary:</strong>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-top: 10px;">
                            <div>✓ Present: <strong><?php echo $present_count; ?></strong></div>
                            <div>✗ Absent: <strong><?php echo $absent_count; ?></strong></div>
                            <div>⊘ Excused: <strong><?php echo $excused_count; ?></strong></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <div class="empty-state-text">No attendance records found for this date</div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-header">Unregistered Stockholders</div>
                
                <?php
                $registered_ids = array_map(function($att) { return $att['stockholder_id'] ?? null; }, $attendance_records);
                $unregistered = array_filter($stockholders, function($sh) use ($registered_ids) {
                    return !in_array($sh['id'], $registered_ids) && $sh['status'] == 'Active';
                });
                ?>

                <?php if (count($unregistered) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Type</th>
                                    <th>Shares</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($unregistered as $sh): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sh['name']); ?></td>
                                        <td><?php echo htmlspecialchars($sh['email']); ?></td>
                                        <td><?php echo htmlspecialchars($sh['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($sh['type']); ?></td>
                                        <td><?php echo number_format($sh['shares'], 2); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="register">
                                                <input type="hidden" name="stockholder_id" value="<?php echo $sh['id']; ?>">
                                                <input type="hidden" name="registration_date" value="<?php echo $today; ?>">
                                                <button type="submit" class="btn btn-primary btn-small">Register</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">✓</div>
                        <div class="empty-state-text">All active stockholders are registered</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <!-- <footer>
        <p>&copy; 2026 University of Bohol Stockholders' System. All rights reserved.</p>
    </footer> -->

    <script src="js/form-validation.js"></script>
</body>

</html>
