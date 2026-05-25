<?php
// admin/ajax/remove_post.php
require_once '../../includes/config.php';

$id = $_POST['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'No ID provided']);
    exit();
}

$conn->beginTransaction();

try {
    // Update post
    $sql1 = "UPDATE posts SET status = 'removed', updated_at = NOW() WHERE id = ?";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->execute([$id]);
    
    // Log admin action
    $sql2 = "INSERT INTO admin_actions (admin_id, action_type, target_type, target_id, created_at) 
             VALUES (?, 'remove_post', 'post', ?, NOW())";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->execute([$_SESSION['user_id'], $id]);
    
    $conn->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>