<?php
// company/test_db.php
require_once '../includes/config.php';

echo "<h1>Database Test</h1>";

try {
    // Test 1: Check if dtr_logs table exists
    // Changed from INFORMATION_SCHEMA to SHOW TABLES to prevent Hostinger permission errors
    $sql = "SHOW TABLES LIKE 'dtr_logs'";
    $stmt = $conn->query($sql);
    
    if ($stmt) {
        $row = $stmt->fetch(PDO::FETCH_NUM);
        if ($row) {
            echo "<p style='color:green'>✓ dtr_logs table EXISTS</p>";
        } else {
            echo "<p style='color:red'>✗ dtr_logs table DOES NOT EXIST! Please create it.</p>";
        }
    } else {
        echo "<p style='color:red'>✗ Cannot query table list</p>";
    }

    // Test 2: Check for accepted applications
    $sql = "SELECT COUNT(*) as count FROM applications WHERE status = 'accepted'";
    $stmt = $conn->query($sql);
    
    if ($stmt) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Accepted applications: " . $row['count'] . "</p>";
    } else {
        echo "<p style='color:red'>Cannot query applications</p>";
    }

    // Test 3: Show all tables
    echo "<h2>All Tables:</h2>";
    // Changed from INFORMATION_SCHEMA to SHOW TABLES
    $sql = "SHOW TABLES";
    $stmt = $conn->query($sql);
    
    if ($stmt) {
        echo "<ul>";
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            echo "<li>" . htmlspecialchars($row[0]) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red'>Cannot retrieve tables.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color:red'><strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>