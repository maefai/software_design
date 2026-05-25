<?php
// admin/ajax/resolve_report.php
require_once '../../includes/config.php';

$id = $_POST['id'] ?? 0;
$action = $_POST['action'] ?? 'resolved'; // resolved, dismissed, investigating

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'No ID provided']);
    exit();
}

$conn->beginTransaction();

try {
    // Update report
    $sql1 = "UPDATE reports SET status = ?, resolved_by = ?, resolved_at = NOW() WHERE id = ?";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->execute([$action, $_SESSION['user_id'], $id]);
    
    // Log admin action
    $sql2 = "INSERT INTO admin_actions (admin_id, action_type, target_type, target_id, created_at) 
             VALUES (?, ?, 'report', ?, NOW())";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->execute([$_SESSION['user_id'], $action . '_report', $id]);
    
    $conn->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>