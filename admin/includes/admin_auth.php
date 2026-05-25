<?php
// admin/includes/admin_auth.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . SITE_URL . "account.php");
    exit();
}

// Get admin data
$admin_id = $_SESSION['user_id'];

try {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        die("Admin user not found in the database.");
    }

    // Get counts for dashboard
    $counts = [];

    // Students count
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM students";
    $stmt = $conn->query($sql);
    if ($stmt) {
        $counts['students'] = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $counts['students'] = ['total' => 0, 'pending' => 0, 'active' => 0, 'rejected' => 0];
    }

    // Companies count
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM companies";
    $stmt = $conn->query($sql);
    if ($stmt) {
        $counts['companies'] = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $counts['companies'] = ['total' => 0, 'pending' => 0, 'active' => 0, 'rejected' => 0];
    }

    // Posts count
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'removed' THEN 1 ELSE 0 END) as removed,
                SUM(CASE WHEN flag IS NOT NULL THEN 1 ELSE 0 END) as flagged
            FROM posts";
    $stmt = $conn->query($sql);
    if ($stmt) {
        $counts['posts'] = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $counts['posts'] = ['total' => 0, 'pending' => 0, 'approved' => 0, 'removed' => 0, 'flagged' => 0];
    }

    // Reports count
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
                SUM(CASE WHEN status = 'investigating' THEN 1 ELSE 0 END) as investigating,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
            FROM reports";
    $stmt = $conn->query($sql);
    if ($stmt) {
        $counts['reports'] = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $counts['reports'] = ['total' => 0, 'open' => 0, 'investigating' => 0, 'resolved' => 0];
    }

} catch (PDOException $e) {
    die("Database error in admin auth: " . $e->getMessage());
}
?>