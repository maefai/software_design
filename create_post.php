<?php
// company/create_post.php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

// Check if company is logged in
requireCompany();

$user_id = $_SESSION['user_id'];
$company = getCompanyData($conn, $user_id);

// Handle post creation
if (isset($_POST['create_post'])) {
    $content = $_POST['content'] ?? '';
    
    if (!empty($content)) {
        try {
            $sql = "INSERT INTO posts (user_id, content, type, status, created_at) 
                    VALUES (?, ?, 'announcement', 'approved', NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id, $content]);
            $_SESSION['success'] = "Post created successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to create post: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Please enter some content.";
    }
    
    // Redirect back to dashboard
    header("Location: dashboard.php");
    exit();
}

// If accessed directly without POST, redirect to dashboard
header("Location: dashboard.php");
exit();
?>