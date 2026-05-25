<?php
// Try connection with port 33066 first (for host machine execution), then container host
$hosts = [
    "mysql:host=127.0.0.1;port=33066;dbname=u815114538_GREENBRIDGE",
    "mysql:host=localhost;port=33066;dbname=u815114538_GREENBRIDGE",
    "mysql:host=greenbridge-db;dbname=u815114538_GREENBRIDGE",
    "mysql:host=localhost;dbname=u815114538_GREENBRIDGE"
];

$conn = null;
foreach ($hosts as $dsn) {
    try {
        $conn = new PDO($dsn, "u815114538_admin", "greenBridge1");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        break; // Success
    } catch (PDOException $e) {
        // Try next
    }
}

if (!$conn) {
    die("Could not connect to database on any attempted DSN.\n");
}

try {
    $sql = "SELECT c.id as company_id, c.company_name, c.status as company_status, 
                   u.id as user_id, u.status as user_status 
            FROM companies c 
            JOIN users u ON c.user_id = u.id";
    $stmt = $conn->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "COMPANIES STATUS IN DB:\n";
    foreach ($results as $row) {
        printf("Company ID: %d | Name: %s | Company Status: %s | User Status: %s\n", 
            $row['company_id'], $row['company_name'], $row['company_status'], $row['user_status']);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
