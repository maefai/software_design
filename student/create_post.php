<?php
// student/create_post.php - UPDATED (no admin approval)
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

requireStudent();

if (isset($_POST['create_post'])) {
    $content = $_POST['post_content'] ?? '';
    
    if (!empty($content)) {
        try {
            // Status is now 'approved' by default (no admin approval needed)
            $sql = "INSERT INTO posts (user_id, content, type, status, created_at) VALUES (?, ?, 'post', 'approved', NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$_SESSION['user_id'], $content]);
            $_SESSION['success'] = "Post published successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    }
    header("Location: dashboard.php");
    exit();
}
?>