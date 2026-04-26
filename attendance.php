<?php 
include_once 'includes/config.php'; 
include_once 'includes/auth.php';
requireLogin();

$success_msg = '';
$error_msg = '';

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_attendance') {
        $stockholder_id = $_POST['stockholder_id'] ?? null;
        $attendance_date = $_POST['attendance_date'] ?? '';
        $status = $_POST['status'] ?? 'Present';

        if ($stockholder_id && $attendance_date) {
            if (addAttendance($conn, $stockholder_id, $attendance_date, $status)) {
                $success_msg = "Attendance recorded successfully!";
            } else {
                $error_msg = "Error recording attendance: " . $conn->error;
            }
        } else {
            $error_msg = "Please select a stockholder and date!";
        }
    }
}

// Get attendance records for selected date
$selected_date = $_GET['date'] ?? date('Y-m-d');
$attendance_records = getAttendanceByDate($conn, $selected_date);
$stockholders = getAllStockholders($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - Stockholders System</title>
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
                <li class="nav-item"><a href="registration.php">Registration</a></li>
                <li class="nav-item"><a href="proxy.php">Add/Edit Proxy</a></li>
                <li class="nav-item"><a href="report.php">Reports</a></li>
                <li class="nav-item"><a href="attendance.php" class="active">Attendance</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Header -->
        <div class="card">
            <div class="card-header">✓ Attendance Management</div>
            <p>Record and view stockholder attendance records for meetings and events.</p>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- Add Attendance Form -->
        <div class="card wide">
            <div class="card-header">Record Attendance</div>
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="add_attendance">
                
                <div class="form-group">
                    <label for="stockholder_id">Stockholder *</label>
                    <select id="stockholder_id" name="stockholder_id" required>
                        <option value="">Select Stockholder</option>
                        <?php foreach ($stockholders as $sh): ?>
                            <option value="<?php echo $sh['id']; ?>"><?php echo htmlspecialchars($sh['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="attendance_date">Date *</label>
                    <input type="date" id="attendance_date" name="attendance_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="Present">Present</option>
                        <option value="Absent" selected>Absent</option>
                        <option value="Excused">Excused</option>
                    </select>
                </div>

                <div class="form-group form-submit">
                    <button type="submit" class="btn btn-primary">✓ Record Attendance</button>
                </div>
            </form>
        </div>

        <!-- Filter by Date -->
        <div class="card">
            <div class="card-header">🔍 Filter Attendance by Date</div>
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="dateFilter">Select Date</label>
                    <input type="date" id="dateFilter" name="date" value="<?php echo $selected_date; ?>" onchange="this.form.submit()">
                </div>
            </form>
        </div>

        <!-- Attendance Records for Selected Date -->
        <div class="card">
            <div class="card-header">Attendance for <?php echo date('F d, Y', strtotime($selected_date)); ?></div>
            
            <?php if (count($attendance_records) > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Stockholder Name</th>
                                <th>Status</th>
                                <th>Recorded Date</th>
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
                                    <td><?php echo date('M d, Y H:i', strtotime($att['created_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Attendance Summary -->
                <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #ecf0f1;">
                    <h3 style="color: var(--primary-color); margin-bottom: 15px;">Attendance Summary</h3>
                    <div class="stats-container">
                        <div class="stat-card" style="background: linear-gradient(135deg, #27ae60, #229954);">
                            <div class="stat-label">Present</div>
                            <div class="stat-number"><?php echo $present_count; ?></div>
                        </div>
                        <div class="stat-card" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                            <div class="stat-label">Absent</div>
                            <div class="stat-number"><?php echo $absent_count; ?></div>
                        </div>
                        <div class="stat-card" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                            <div class="stat-label">Excused</div>
                            <div class="stat-number"><?php echo $excused_count; ?></div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <div class="empty-state-text">No attendance records found for this date</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- All Attendance History -->
        <div class="card">
            <div class="card-header">Attendance History (All Records)</div>
            
            <?php
            $all_attendance = [];
            foreach ($stockholders as $sh) {
                $records = getAttendanceByStockholder($conn, $sh['id']);
                foreach ($records as $record) {
                    $all_attendance[] = [
                        'name' => $sh['name'],
                        'attendance_date' => $record['attendance_date'],
                        'status' => $record['status'],
                        'created_date' => $record['created_date']
                    ];
                }
            }
            
            // Sort by date descending
            usort($all_attendance, function($a, $b) {
                return strtotime($b['attendance_date']) - strtotime($a['attendance_date']);
            });
            
            if (count($all_attendance) > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Stockholder</th>
                                <th>Attendance Date</th>
                                <th>Status</th>
                                <th>Recorded Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $displayed = 0;
                            foreach ($all_attendance as $att):
                                if ($displayed >= 20) break; // Show last 20 records
                                
                                $status_class = match($att['status']) {
                                    'Present' => 'badge-success',
                                    'Absent' => 'badge-danger',
                                    'Excused' => 'badge-warning',
                                    default => 'badge-pending'
                                };
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($att['name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($att['attendance_date'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo $att['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($att['created_date'])); ?></td>
                                </tr>
                            <?php
                                $displayed++;
                            endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <div class="empty-state-text">No attendance records found</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <div class="card" style="text-align: center;">
            <button onclick="window.print();" class="btn btn-primary">🖨️ Print Attendance</button>
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
