<?php
require_once 'includes/admin_auth.php';

// Simple query to test
$sql = "SELECT s.*, u.email 
        FROM students s 
        JOIN users u ON s.user_id = u.id 
        ORDER BY s.id DESC";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die("Error: " . print_r(sqlsrv_errors(), true));
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Test</title>
</head>
<body>
    <h1>Student Verification Test</h1>
    
    <h3>Admin Info:</h3>
    <p>Logged in as: <?php echo htmlspecialchars($admin['email']); ?></p>
    
    <h3>Database Connection:</h3>
    <p style="color:green">✅ Connected successfully</p>
    
    <h3>Student Counts:</h3>
    <ul>
        <li>Total Students: <?php echo $counts['students']['total']; ?></li>
        <li>Pending: <?php echo $counts['students']['pending']; ?></li>
        <li>Active: <?php echo $counts['students']['active']; ?></li>
        <li>Rejected: <?php echo $counts['students']['rejected']; ?></li>
    </ul>
    
    <h3>Sample Students:</h3>
    <table border="1" cellpadding="5">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Course</th>
            <th>Status</th>
        </tr>
        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['fullname']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['course']); ?></td>
            <td><?php echo $row['status']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    
    <p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>