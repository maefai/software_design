<?php
require_once 'includes/admin_auth.php';

$sql = "SELECT r.*,
               reporter.email as reporter_email,
               subject.email as subject_email
        FROM reports r
        JOIN users reporter ON r.reporter_id = reporter.id
        JOIN users subject ON r.reported_id = subject.id
        ORDER BY r.id DESC";
$stmt = sqlsrv_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reports Test</title>
</head>
<body>
    <h1>User Reports Test</h1>
    
    <h3>Report Counts:</h3>
    <ul>
        <li>Total Reports: <?php echo $counts['reports']['total']; ?></li>
        <li>Open: <?php echo $counts['reports']['open']; ?></li>
        <li>Investigating: <?php echo $counts['reports']['investigating']; ?></li>
        <li>Resolved: <?php echo $counts['reports']['resolved']; ?></li>
    </ul>
    
    <h3>Sample Reports:</h3>
    <table border="1" cellpadding="5">
        <tr>
            <th>ID</th>
            <th>Reporter</th>
            <th>Subject</th>
            <th>Reason</th>
            <th>Status</th>
        </tr>
        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['reporter_email']); ?></td>
            <td><?php echo htmlspecialchars($row['subject_email']); ?></td>
            <td><?php echo htmlspecialchars($row['reason']); ?></td>
            <td><?php echo $row['status']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    
    <p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>