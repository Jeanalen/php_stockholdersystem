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
    <title>History - Stockholders System</title>
    <link rel="stylesheet" href="css/style.css">
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
                <li class="nav-item"><a href="registration.php">Registration & Attendance</a></li>
                <li class="nav-item"><a href="proxy.php">Add/Edit Proxy</a></li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item"><a href="history.php"  class="active">History of Actions</a></li>
                <?php endif; ?>
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
            <div class="card-header">System Activity History</div>
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 100");
                    while($row = $result->fetch_assoc()): 
                        // Reset variable for each row
                        $badgeClass = 'badge-pending'; 

                        if($row['action_type'] == 'INSERT')     $badgeClass = 'badge-success';
                        if($row['action_type'] == 'DELETE')     $badgeClass = 'badge-danger';
                        if($row['action_type'] == 'UPDATE')     $badgeClass = 'badge-warning';
                        if($row['action_type'] == 'LOGIN')      $badgeClass = 'badge-success';
                        if($row['action_type'] == 'LOGOUT')     $badgeClass = 'badge-danger';
                        if($row['action_type'] == 'ATTENDANCE') $badgeClass = 'badge-info'; 
                    ?>
                    <tr>
                        <td><?php echo date('M d, Y | h:i A', strtotime($row['created_at'])); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['admin_name']); ?></strong>
                            <br><small>ID: <?php echo $row['admin_id']; ?></small>
                        </td>
                        <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $row['action_type']; ?></span></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>