<?php
// admin/ajax/get_students.php
require_once '../../includes/config.php';

try {
    $sql = "SELECT s.*, u.email, u.status as user_status, u.created_at
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            ORDER BY s.id DESC";
    $stmt = $conn->query($sql);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($students);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?>