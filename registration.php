<?php 
include 'includes/config.php';
include 'includes/auth.php'; 
requireLogin();

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    // --- RECORD ATTENDANCE LOGIC ---
    if ($_POST['action'] == 'record_attendance') {
        $stockholder_id = $_POST['stockholder_id'] ?? null;
        $attendance_date = $_POST['attendance_date'] ?? date('Y-m-d');
        $status = $_POST['status'] ?? 'Present';

        if ($stockholder_id && $attendance_date) {
            // NEW: BLOCK DUPLICATES - Check if record exists for this date
            $check = $conn->query("SELECT id FROM attendance WHERE stockholder_id = $stockholder_id AND attendance_date = '$attendance_date'");
            
            if ($check->num_rows > 0) {
                $error_msg = "This stockholder has already been recorded for this date!";
            } else {
                if (addAttendance($conn, $stockholder_id, $attendance_date, $status)) {
                    $res = $conn->query("SELECT name FROM stockholders WHERE id = $stockholder_id");
                    $sh_data = $res->fetch_assoc();
                    $stockholder_name = $sh_data['name'] ?? "Unknown";

                    $description = "Marked $status for stockholder: " . $stockholder_name;
                    logActivity($conn, 'ATTENDANCE', $description);

                    $success_msg = "Attendance recorded successfully! Status: " . $status;
                } else {
                    $error_msg = "Error recording attendance: " . $conn->error;
                }
            }
        } else {
            $error_msg = "Please select a stockholder and date!";
        }
    }

    // --- QUICK REGISTER LOGIC ---
    if ($_POST['action'] == 'register') {
        $stockholder_id = $_POST['stockholder_id'];
        $reg_date = $_POST['registration_date'] ?? date('Y-m-d');
        
        // NEW: BLOCK DUPLICATES for quick register
        $check = $conn->query("SELECT id FROM attendance WHERE stockholder_id = $stockholder_id AND attendance_date = '$reg_date'");
        if ($check->num_rows == 0) {
            if (addAttendance($conn, $stockholder_id, $reg_date, 'Present')) {
                $res = $conn->query("SELECT name FROM stockholders WHERE id = $stockholder_id");
                $sh_data = $res->fetch_assoc();
                $name = $sh_data['name'] ?? "Unknown";

                logActivity($conn, 'ATTENDANCE', "Quick registered/Marked Present: " . $name);
                $success_msg = "Stockholder " . $name . " registered and marked Present!";
            }
        } else {
            $error_msg = "Stockholder already registered for today.";
        }
    }
}

$stockholders = getAllStockholders($conn);
$today = date('Y-m-d');
$selected_date = $_GET['date'] ?? $today;
$attendance_records = getAttendanceByDate($conn, $selected_date);

// Identify IDs of those already marked today to filter the dropdown and unregistered list
$already_marked_ids = array_map(function($att) { return $att['stockholder_id']; }, $attendance_records);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration & Attendance - Stockholders System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Print Styles */
        @media print {
            nav, .filter-form, .card-subtitle, hr, .form-grid, .btn-group, .empty-state, .search-container, .no-print {
                display: none !important;
            }
            body { background: white; }
            .container { width: 100%; max-width: 100%; margin: 0; padding: 0; }
            .card { border: 1px solid #ccc; box-shadow: none; margin-bottom: 20px; }
            .two-col-grid { display: block; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        }

        /* Search & Print UI */
        .search-container { margin-bottom: 15px; display: flex; gap: 10px; }
        .search-input { 
            padding: 10px; border: 1px solid #ddd; border-radius: 4px; flex-grow: 1; font-size: 14px;
        }
        .btn-print { 
            background: #2c3e50; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 600;
        }
    </style>
</head>

<body>
    <nav>
        <div class="navbar-container">
            <div class="navbar-brand">
                📊 University of Bohol | Stockholders' System
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="index.php">Home</a></li>
                <li class="nav-item"><a href="add-stockholder.php">Add Stockholder</a></li>
                <li class="nav-item"><a href="edit-stockholder.php">Edit Stockholder</a></li>
                <li class="nav-item"><a href="registration.php" class="active">Registration & Attendance</a></li>
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
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="two-col-grid">
            <div class="card" id="printableArea">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Attendance for <?php echo date('F d, Y', strtotime($selected_date)); ?></span>
                    <button onclick="window.print()" class="btn-print no-print">🖨️ Print List</button>
                </div>

                <div class="search-container no-print">
                    <input type="text" id="attendanceSearch" class="search-input" placeholder="🔍 Search recorded names..." onkeyup="filterAttendance()">
                </div>

                <form method="GET" class="filter-form no-print" style="margin-bottom: 15px;">
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
                            <tbody id="attendanceTableBody">
                                <?php
                                $present_count = 0; $absent_count = 0; $excused_count = 0;
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
                                    <tr class="attendance-row">
                                        <td class="st-name"><?php echo htmlspecialchars($att['name']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo $att['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('h:i A', strtotime($att['created_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

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

            <div class="card no-print">
                <div class="card-header">📋 Registration & Attendance</div>
                <div class="card-subtitle">Record attendance or register stockholders for meetings.</div>
                <p>Select a stockholder and attendance status to record their attendance.</p>

                <hr style="margin:16px 0; border:none; border-top:1px solid #eef3f5;">

                <form method="POST" class="form-grid">
                    <input type="hidden" name="action" value="record_attendance">
                    
                    <div class="form-group">
                        <label for="stockholder_id">Select Stockholder *</label>
                        <select id="stockholder_id" name="stockholder_id" required>
                            <option value="">Choose a stockholder</option>
                            <?php foreach ($stockholders as $sh): 
                                // NEW: Only show stockholders NOT already recorded for today
                                if (in_array($sh['id'], $already_marked_ids)) continue;
                            ?>
                                <option value="<?php echo $sh['id']; ?>"><?php echo htmlspecialchars($sh['name']); ?> (Shares: <?php echo $sh['shares']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="attendance_date">Attendance Date *</label>
                        <input type="date" id="attendance_date" name="attendance_date" value="<?php echo $today; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="status">Attendance Status *</label>
                        <select id="status" name="status" required>
                            <option value="Present">Present</option>
                            <option value="Absent" selected>Absent</option>
                            <option value="Excused">Excused</option>
                        </select>
                    </div>

                    <div class="form-submit" style="text-align: left;">
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">✓ Record Attendance</button>
                            <a href="index.php" class="btn btn-secondary">Back to Home</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card no-print">
                <div class="card-header">Unregistered Stockholders</div>
                
                <?php
                // NEW: Filter unregistered based on the today list
                $unregistered = array_filter($stockholders, function($sh) use ($already_marked_ids) {
                    return !in_array($sh['id'], $already_marked_ids) && $sh['status'] == 'Active';
                });
                ?>

                <?php if (count($unregistered) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Shares</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($unregistered as $sh): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sh['name']); ?></td>
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

    <script>
    // Live Search Function
    function filterAttendance() {
        let input = document.getElementById('attendanceSearch').value.toLowerCase();
        let rows = document.querySelectorAll('.attendance-row');
        
        rows.forEach(row => {
            let name = row.querySelector('.st-name').innerText.toLowerCase();
            if (name.includes(input)) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    }
    </script>
    <script src="js/form-validation.js"></script>
</body>

</html>