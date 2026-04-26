<?php 
include 'includes/config.php';
include 'includes/auth.php'; // Add this line to load getAdminName()
requireLogin();              // Optional: Ensures only logged-in admins see this page

$success_msg = '';
$error_msg = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $shares = $_POST['shares'];

    // 1. Prepare the SQL Statement
    $stmt = $conn->prepare("INSERT INTO stockholders (name, email, shares, status) VALUES (?, ?, ?, 'Active')");
    $stmt->bind_param("ssd", $name, $email, $shares);

    // 2. Execute and handle the result
    if ($stmt->execute()) {
        // SUCCESS: Now we log the action for the History nav bar
        $description = "Added new stockholder: " . $name . " with " . $shares . " shares.";
        logActivity($conn, 'INSERT', $description);

        // Redirect to index with success flag
        header("Location: index.php?success=add");
        exit();
    } else {
        // FAILURE
        $message = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
    }
    $stmt->close();
}

// if ($query_success) {
//     logActivity($conn, 'INSERT', "Added new stockholder: " . $_POST['name']);
//     header("Location: index.php?success=1");
// }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Stockholder - Stockholders System</title>
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
                <li class="nav-item"><a href="add-stockholder.php" class="active">Add Stockholder</a></li>
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
        <div class="card wide">
            <div class="card-header">Add New Stockholder</div>

            <?php if ($success_msg): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="alert alert-danger"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <form method="POST" id="stockholderForm" class="form-grid">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required placeholder="Enter stockholder name">
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" placeholder="Enter address">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="Enter email address">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="phone" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" placeholder="Enter phone number">
                </div>

                <div class="form-group">
                    <label for="type">Type</label>
                    <select id="type" name="type">
                        <option value="Individual" <?php echo ($_POST['type'] ?? '') == 'Individual' ? 'selected' : ''; ?>>Individual</option>
                        <option value="Corporate" <?php echo ($_POST['type'] ?? '') == 'Corporate' ? 'selected' : ''; ?>>Corporate</option>
                    </select>
                </div>

                <!-- <div class="form-group">
                    <label for="tax_id">Tax ID</label>
                    <input type="text" id="tax_id" name="tax_id" value="<?php echo htmlspecialchars($_POST['tax_id'] ?? ''); ?>" placeholder="Enter tax ID">
                </div> -->

                <div class="form-group">
                    <label for="shares">Number of Shares *</label>
                    <input type="number" id="shares" name="shares" value="<?php echo htmlspecialchars($_POST['shares'] ?? ''); ?>" step="0.01" required placeholder="Enter number of shares">
                </div>

                <div class="form-submit">
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">➕ Add Stockholder</button>
                        <a href="edit-stockholder.php" class="btn btn-secondary">View All Stockholders</a>
                        <a href="index.php" class="btn btn-secondary">Back to Home</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Guidelines Card -->
        <div class="card wide">
            <div class="card-header">Guidelines</div>
            <ul style="margin-left: 20px; line-height: 1.8;">
                <li>Ensure the Full Name is complete and accurate</li>
                <li>Address should be the primary residence or business address</li>
                <li>Email should be a valid email address for correspondence</li>
                <li>Phone number should include area code if applicable</li>
                <li>Type can be Individual or Corporate</li>
                <li>Tax ID is optional but recommended</li>
                <li>Number of Shares should be a positive number (can include decimals)</li>
                <li>All marked fields (*) are mandatory</li>
            </ul>
        </div>
    </div>

    <!-- Footer -->
    <!-- <footer>
        <p>&copy; 2026 University of Bohol Stockholders' System. All rights reserved.</p>
    </footer> -->

    <script src="js/form-validation.js"></script>
</body>

</html>
