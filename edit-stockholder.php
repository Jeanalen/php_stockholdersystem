<?php 
include 'includes/config.php';
include 'includes/auth.php'; // Add this line to load getAdminName()
requireLogin();              // Optional: Ensures only logged-in admins see this page


$success_msg = '';
$error_msg = '';
$edit_mode = false;
$current_stockholder = null;

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_to_delete = $_GET['delete'];
    
    // 1. Fetch name first for the history log
    $stmt = $conn->prepare("SELECT name FROM stockholders WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    $stmt->execute();
    $res = $stmt->get_result();
    $user_data = $res->fetch_assoc();
    
    if ($user_data) {
        $name_deleted = $user_data['name'];

        // 2. Perform the actual deletion
        if (deleteStockholder($conn, $id_to_delete)) {
            // 3. SUCCESS: Log the action for history.php
            $description = "Deleted stockholder: " . $name_deleted . " (ID: " . $id_to_delete . ")";
            logActivity($conn, 'DELETE', $description);

            $success_msg = "Stockholder '$name_deleted' deleted successfully!";
        } else {
            $error_msg = "Error deleting stockholder!";
        }
    } else {
        $error_msg = "Stockholder not found.";
    }
}

// Check if an ID is provided for editing
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = $_GET['edit'];
    $edit_mode = true;
    
    // Fetch the specific stockholder to fill the form
    $stmt = $conn->prepare("SELECT * FROM stockholders WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_stockholder = $result->fetch_assoc();

    if (!$current_stockholder) {
        $error_msg = "Stockholder not found.";
        $edit_mode = false;
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id = $_POST['id'];
    $name = $_POST['name'] ?? '';
    $address = $_POST['address'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $type = $_POST['type'] ?? 'Individual';
    $tax_id = $_POST['tax_id'] ?? '';
    $shares = $_POST['shares'] ?? 0;
    $status = $_POST['status'] ?? 'Active';

    if (empty($name) || empty($shares)) {
        $error_msg = "Name and Shares are required fields!";
    } else {
        // Using your helper function updateStockholder
        if (updateStockholder($conn, $id, $name, $address, $email, $phone, $type, $tax_id, $shares, $status)) {
            // Log activity
            $description = "Updated stockholder: " . $name . " (ID: " . $id . ")";
            logActivity($conn, 'UPDATE', $description);
            
            $success_msg = "Stockholder updated successfully!";
            // Optional: header("Location: edit-stockholder.php?success=1"); exit();
        } else {
            $error_msg = "Error updating stockholder: " . $conn->error;
        }
    }
}

$stockholders = getAllStockholders($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Stockholder - Stockholders System</title>
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
                <li class="nav-item"><a href="edit-stockholder.php" class="active">Edit Stockholder</a></li>
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
        <!-- Edit Form -->
        <?php if ($edit_mode && $current_stockholder): ?>
            <div class="card wide" style="margin-bottom:30px;">
                <div class="card-header">Edit Stockholder</div>

                <?php if ($success_msg): ?>
                    <div class="alert alert-success"><?php echo $success_msg; ?></div>
                <?php endif; ?>

                <?php if ($error_msg): ?>
                    <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <form method="POST" class="form-grid">
                    <input type="hidden" name="id" value="<?php echo $current_stockholder['id']; ?>">

                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($current_stockholder['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($current_stockholder['address']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($current_stockholder['email']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="phone" id="phone" name="phone" value="<?php echo htmlspecialchars($current_stockholder['phone']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="type">Type</label>
                        <select id="type" name="type">
                            <option value="Individual" <?php echo $current_stockholder['type'] == 'Individual' ? 'selected' : ''; ?>>Individual</option>
                            <option value="Corporate" <?php echo $current_stockholder['type'] == 'Corporate' ? 'selected' : ''; ?>>Corporate</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tax_id">Tax ID</label>
                        <input type="text" id="tax_id" name="tax_id" value="<?php echo htmlspecialchars($current_stockholder['tax_id']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="shares">Number of Shares *</label>
                        <input type="number" id="shares" name="shares" value="<?php echo htmlspecialchars($current_stockholder['shares']); ?>" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="Active" <?php echo $current_stockholder['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo $current_stockholder['status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="form-submit">
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">💾 Update Stockholder</button>
                            <a href="edit-stockholder.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Stockholders List -->
        <div class="card">
            <div class="card-header">Stockholders Register</div>

            <?php if ($success_msg): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="alert alert-danger"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <div class="search-container">
                <input type="text" id="searchInput" placeholder="Search stockholders by name, email, or phone..." onkeyup="filterTable()">
                <a href="add-stockholder.php" class="btn btn-primary">➕ Add New Stockholder</a>
            </div>

            <?php if (count($stockholders) > 0): ?>
                <div style="overflow-x: auto;">
                    <table id="stockholdersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Type</th>
                                <th>Shares</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stockholders as $sh): ?>
                                <tr>
                                    <td><?php echo $sh['id']; ?></td>
                                    <td><?php echo htmlspecialchars($sh['name']); ?></td>
                                    <td><?php echo htmlspecialchars($sh['email']); ?></td>
                                    <td><?php echo htmlspecialchars($sh['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($sh['type']); ?></td>
                                    <td><?php echo number_format($sh['shares'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo $sh['status'] == 'Active' ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo $sh['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($sh['created_date'])); ?></td>
                                    <td class="action-links">
                                        <a href="edit-stockholder.php?edit=<?php echo $sh['id']; ?>">Edit</a>
                                        <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $sh['id']; ?>, '<?php echo addslashes($sh['name']); ?>');" class="delete">Delete</a>
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
                    <a href="add-stockholder.php" class="btn btn-primary">Add First Stockholder</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <!-- <footer>
        <p>&copy; 2026 University of Bohol Stockholders' System. All rights reserved.</p>
    </footer> -->

    <script src="js/form-validation.js"></script>
    <script>
        function filterTable() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const table = document.getElementById('stockholdersTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(input) ? '' : 'none';
            }
        }

        function confirmDelete(id, name) {
            if (confirm('Are you sure you want to delete "' + name + '"? This action cannot be undone and will be recorded in the history.')) {
                window.location.href = 'edit-stockholder.php?delete=' + id;
            }
        }
    </script>
</body>

</html>
