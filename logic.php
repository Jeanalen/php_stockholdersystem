<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'stakeholder_system';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$page = $_GET['page'] ?? 'home';

// Basic Router
switch($page) {
    case 'add':
        echo "<h2>Add New Stakeholder</h2>
              <form method='POST' action='logic.php?action=insert'>
                <div class='form-group'>Name: <input type='text' name='name' required></div>
                <div class='form-group'>Shares: <input type='number' name='shares' required></div>
                <div class='form-group'>Dividend Rate ($): <input type='text' name='rate'></div>
                <button type='submit'>Save Stakeholder</button>
              </form>";
        break;

    case 'report':
        $result = $conn->query("SELECT *, (share_count * dividend_rate) AS total_payout FROM stakeholders");
        echo "<h2>Dividend Distribution Report</h2><table>
              <tr><th>Name</th><th>Shares</th><th>Rate</th><th>Total Payout</th></tr>";
        while($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['name']}</td><td>{$row['share_count']}</td><td>\${$row['dividend_rate']}</td><td><strong>\${$row['total_payout']}</strong></td></tr>";
        }
        echo "</table>";
        break;

    default: // Home Page
        $stats = $conn->query("SELECT SUM(share_count) as total_shares, COUNT(id) as total_users FROM stakeholders")->fetch_assoc();
        echo "<h2>Dashboard</h2>";
        echo "<p>Total Stakeholders: " . ($stats['total_users'] ?? 0) . "</p>";
        echo "<p>Total Shares Issued: " . ($stats['total_shares'] ?? 0) . "</p>";
        break;
}

// Handle Form Submission
if (isset($_GET['action']) && $_GET['action'] == 'insert') {
    $name = $_POST['name'];
    $shares = $_POST['shares'];
    $rate = $_POST['rate'];
    $conn->query("INSERT INTO stakeholders (name, share_count, dividend_rate) VALUES ('$name', '$shares', '$rate')");
    header("Location: index.php?page=report");
}
?>