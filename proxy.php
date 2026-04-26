<?php 
include 'includes/config.php';
include 'includes/auth.php'; // Add this line to load getAdminName()
requireLogin();              // Optional: Ensures only logged-in admins see this page

$success_msg = '';
$error_msg = '';
$edit_mode = false;
$current_proxy = null;

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (deleteProxy($conn, $_GET['delete'])) {
        $success_msg = "Proxy deleted successfully!";
    } else {
        $error_msg = "Error deleting proxy!";
    }
}

// Handle edit mode
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $sql = "SELECT * FROM proxies WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $current_proxy = $stmt->get_result()->fetch_assoc();
    if ($current_proxy) {
        $edit_mode = true;
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_proxy'])) {
    $stockholder_id = $_POST['stockholder_id'];
    $proxy_name = $_POST['proxy_name'];
    // We get the stockholder name to make the history log more descriptive
    $stockholder_name = $_POST['stockholder_name']; 

    // 1. Prepare the Update Statement
    $stmt = $conn->prepare("UPDATE stockholders SET proxy_name = ?, has_proxy = 1 WHERE id = ?");
    $stmt->bind_param("si", $proxy_name, $stockholder_id);

    // 2. Execute the query
    if ($stmt->execute()) {
        
        /** * INSERT THE LOG HERE 
         * This records the action so it appears in your "History of Actions" bar
         **/
        $description = "Assigned " . $proxy_name . " as proxy for " . $stockholder_name;
        logActivity($conn, 'PROXY', $description);

        // 3. Redirect after successful log
        header("Location: proxy.php?success=proxy_assigned");
        exit();
    } else {
        echo "Error updating proxy: " . $conn->error;
    }
    $stmt->close();
}

$stockholders = getAllStockholders($conn);
$proxies = getAllProxies($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add/Edit Proxy - Stockholders System</title>
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
                <li class="nav-item"><a href="proxy.php" class="active">Add/Edit Proxy</a></li>
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

        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- Edit Form -->
        <?php if ($edit_mode && $current_proxy): ?>
            <div class="card wide" style="margin-bottom:30px;">
                <div class="card-header">Edit Proxy</div>

                <form method="POST" class="form-grid">
                    <input type="hidden" name="action" value="edit_proxy">
                    <input type="hidden" name="id" value="<?php echo $current_proxy['id']; ?>">

                    <div class="form-group">
                        <label for="proxy_name">Proxy Name *</label>
                        <input type="text" id="proxy_name" name="proxy_name" value="<?php echo htmlspecialchars($current_proxy['proxy_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="proxy_email">Proxy Email</label>
                        <input type="email" id="proxy_email" name="proxy_email" value="<?php echo htmlspecialchars($current_proxy['proxy_email']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="proxy_phone">Proxy Phone</label>
                        <input type="phone" id="proxy_phone" name="proxy_phone" value="<?php echo htmlspecialchars($current_proxy['proxy_phone']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="authorization_date">Authorization Date *</label>
                        <input type="date" id="authorization_date" name="authorization_date" value="<?php echo $current_proxy['authorization_date']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="expiry_date">Expiry Date</label>
                        <input type="date" id="expiry_date" name="expiry_date" value="<?php echo $current_proxy['expiry_date']; ?>">
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="Active" <?php echo $current_proxy['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo $current_proxy['status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="form-submit">
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">💾 Update Proxy</button>
                            <a href="proxy.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Add Proxy Form -->
        <div class="card wide">
            <div class="card-header">👤 Proxy Management</div>
            <div class="card-subtitle">Assign a proxy representative for stockholders to authorize attendance and voting rights.</div>
            <p>Fill in the proxy details and authorization dates below.</p>

            <hr style="margin:16px 0; border:none; border-top:1px solid #eef3f5;">

            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="add_proxy">

                <div class="form-group">
                    <label for="stockholder_id">Stockholder *</label>
                    <select id="stockholder_id" name="stockholder_id" required>
                        <option value="">Select a stockholder</option>
                        <?php foreach ($stockholders as $sh): ?>
                            <option value="<?php echo $sh['id']; ?>"><?php echo htmlspecialchars($sh['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="proxy_name">Proxy Name *</label>
                    <input type="text" id="proxy_name" name="proxy_name" value="<?php echo htmlspecialchars($_POST['proxy_name'] ?? ''); ?>" required placeholder="Enter proxy name">
                </div>

                <div class="form-group">
                    <label for="proxy_email">Proxy Email</label>
                    <input type="email" id="proxy_email" name="proxy_email" value="<?php echo htmlspecialchars($_POST['proxy_email'] ?? ''); ?>" placeholder="Enter proxy email">
                </div>

                <div class="form-group">
                    <label for="proxy_phone">Proxy Phone</label>
                    <input type="phone" id="proxy_phone" name="proxy_phone" value="<?php echo htmlspecialchars($_POST['proxy_phone'] ?? ''); ?>" placeholder="Enter proxy phone">
                </div>

                <div class="form-group">
                    <label for="authorization_date">Authorization Date *</label>
                    <input type="date" id="authorization_date" name="authorization_date" value="<?php echo htmlspecialchars($_POST['authorization_date'] ?? date('Y-m-d')); ?>" required>
                </div>

                <div class="form-group">
                    <label for="expiry_date">Expiry Date</label>
                    <input type="date" id="expiry_date" name="expiry_date" value="<?php echo htmlspecialchars($_POST['expiry_date'] ?? ''); ?>" placeholder="Optional">
                </div>

                <div class="form-submit">
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">➕ Add Proxy</button>
                        <a href="index.php" class="btn btn-secondary">Back to Home</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Proxies List -->
        <div class="card">
            <div class="card-header">Active Proxies</div>

            <div class="search-container">
                <input type="text" id="searchInput" placeholder="Search proxies by stockholder or proxy name..." onkeyup="filterTable()">
            </div>

            <?php if (count($proxies) > 0): ?>
                <div style="overflow-x: auto;">
                    <table id="proxiesTable">
                        <thead>
                            <tr>
                                <th>Stockholder</th>
                                <th>Proxy Name</th>
                                <th>Proxy Email</th>
                                <th>Auth. Date</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proxies as $proxy): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($proxy['name']); ?></td>
                                    <td><?php echo htmlspecialchars($proxy['proxy_name']); ?></td>
                                    <td><?php echo htmlspecialchars($proxy['proxy_email']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($proxy['authorization_date'])); ?></td>
                                    <td><?php echo $proxy['expiry_date'] ? date('M d, Y', strtotime($proxy['expiry_date'])) : 'N/A'; ?></td>
                                    <td>
                                        <span class="badge <?php echo $proxy['status'] == 'Active' ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo $proxy['status']; ?>
                                        </span>
                                    </td>
                                    <td class="action-links">
                                        <a href="proxy.php?edit=<?php echo $proxy['id']; ?>">Edit</a>
                                        <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $proxy['id']; ?>);" class="delete">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">👤</div>
                    <div class="empty-state-text">No proxies found</div>
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
            const table = document.getElementById('proxiesTable');
            if (!table) return;
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(input) ? '' : 'none';
            }
        }

        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this proxy? This action cannot be undone.')) {
                window.location.href = 'proxy.php?delete=' + id;
            }
        }
    </script>
</body>

</html>
