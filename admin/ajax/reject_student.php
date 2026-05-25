<?php
// admin/ajax/reject_student.php
require_once '../../includes/config.php';

$id = $_POST['id'] ?? 0;
$reason = $_POST['reason'] ?? '';

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'No ID provided']);
    exit();
}

$conn->beginTransaction();

try {
    // Get user_id first
    $sql = "SELECT user_id FROM students WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) throw new Exception("Student not found.");
    $user_id = $student['user_id'];
    
    // Update students table
    $sql1 = "UPDATE students SET status = 'rejected' WHERE id = ?";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->execute([$id]);
    
    // Update users table
    $sql2 = "UPDATE users SET status = 'rejected', rejected_reason = ? WHERE id = ?";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->execute([$reason, $user_id]);
    
    // Log admin action
    $sql3 = "INSERT INTO admin_actions (admin_id, action_type, target_type, target_id, details, created_at) 
             VALUES (?, 'reject_student', 'student', ?, ?, NOW())";
    $stmt3 = $conn->prepare($sql3);
    $stmt3->execute([$_SESSION['user_id'], $id, $reason]);
    
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>