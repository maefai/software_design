<?php
require_once 'includes/admin_auth.php';

$sql = "SELECT c.*, u.email 
        FROM companies c 
        JOIN users u ON c.user_id = u.id 
        ORDER BY c.id DESC";
$stmt = sqlsrv_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Company Test</title>
</head>
<body>
    <h1>Company Verification Test</h1>
    
    <h3>Company Counts:</h3>
    <ul>
        <li>Total Companies: <?php echo $counts['companies']['total']; ?></li>
        <li>Pending: <?php echo $counts['companies']['pending']; ?></li>
        <li>Active: <?php echo $counts['companies']['active']; ?></li>
        <li>Rejected: <?php echo $counts['companies']['rejected']; ?></li>
    </ul>
    
    <h3>Sample Companies:</h3>
    <table border="1" cellpadding="5">
        <tr>
            <th>ID</th>
            <th>Company Name</th>
            <th>Email</th>
            <th>Industry</th>
            <th>Status</th>
        </tr>
        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['company_name']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['industry']); ?></td>
            <td><?php echo $row['status']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    
    <p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>