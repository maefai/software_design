<?php
// admin/ajax/get_companies.php
require_once '../../includes/config.php';

try {
    $sql = "SELECT c.*, u.email, u.status as user_status, u.created_at
            FROM companies c 
            JOIN users u ON c.user_id = u.id 
            ORDER BY c.id DESC";
    $stmt = $conn->query($sql);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($companies);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?>