<?php
require_once 'includes/admin_auth.php';

$sql = "SELECT p.*, 
               CASE WHEN u.user_type = 'student' THEN s.fullname 
                    WHEN u.user_type = 'company' THEN c.company_name 
                    ELSE u.email END as author_name
        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN students s ON u.id = s.user_id AND u.user_type = 'student'
        LEFT JOIN companies c ON u.id = c.user_id AND u.user_type = 'company'
        ORDER BY p.id DESC";
$stmt = sqlsrv_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Post Test</title>
</head>
<body>
    <h1>Post Checking Test</h1>
    
    <h3>Post Counts:</h3>
    <ul>
        <li>Total Posts: <?php echo $counts['posts']['total']; ?></li>
        <li>Pending: <?php echo $counts['posts']['pending']; ?></li>
        <li>Approved: <?php echo $counts['posts']['approved']; ?></li>
        <li>Removed: <?php echo $counts['posts']['removed']; ?></li>
        <li>Flagged: <?php echo $counts['posts']['flagged']; ?></li>
    </ul>
    
    <h3>Sample Posts:</h3>
    <table border="1" cellpadding="5">
        <tr>
            <th>ID</th>
            <th>Author</th>
            <th>Content</th>
            <th>Status</th>
            <th>Flag</th>
        </tr>
        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['author_name']); ?></td>
            <td><?php echo htmlspecialchars(substr($row['content'], 0, 50)) . '...'; ?></td>
            <td><?php echo $row['status']; ?></td>
            <td><?php echo $row['flag'] ?: 'None'; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    
    <p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>