<?php
// admin/ajax/get_posts.php
require_once '../../includes/config.php';

try {
    $sql = "SELECT p.*, 
                   CASE WHEN u.user_type = 'student' THEN s.fullname 
                        WHEN u.user_type = 'company' THEN c.company_name 
                        ELSE u.email END as author_name,
                   u.user_type as author_type
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN students s ON u.id = s.user_id AND u.user_type = 'student'
            LEFT JOIN companies c ON u.id = c.user_id AND u.user_type = 'company'
            ORDER BY p.created_at DESC";
    $stmt = $conn->query($sql);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($posts);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?>