<?php
// admin/ajax/get_reports.php
require_once '../../includes/config.php';

try {
    $sql = "SELECT r.*,
                   reporter.email as reporter_email,
                   reporter.user_type as reporter_type,
                   subject.email as subject_email,
                   subject.user_type as subject_type,
                   p.content as post_content
            FROM reports r
            JOIN users reporter ON r.reporter_id = reporter.id
            JOIN users subject ON r.reported_id = subject.id
            LEFT JOIN posts p ON r.post_id = p.id
            ORDER BY r.created_at DESC";
    $stmt = $conn->query($sql);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($reports);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?>