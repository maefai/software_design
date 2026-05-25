<?php
// admin/ajax/approve_company.php
require_once '../../includes/config.php';

$id = $_POST['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'No ID provided']);
    exit();
}

$conn->beginTransaction();

try {
    // Get user_id first
    $sql = "SELECT user_id FROM companies WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) throw new Exception("Company not found.");
    $user_id = $company['user_id'];
    
    // Update companies table
    $sql1 = "UPDATE companies SET status = 'active' WHERE id = ?";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->execute([$id]);
    
    // Update users table
    $sql2 = "UPDATE users SET status = 'active', verified_at = NOW() WHERE id = ?";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->execute([$user_id]);
    
    // Log admin action
    $sql3 = "INSERT INTO admin_actions (admin_id, action_type, target_type, target_id, created_at) 
             VALUES (?, 'approve_company', 'company', ?, NOW())";
    $stmt3 = $conn->prepare($sql3);
    $stmt3->execute([$_SESSION['user_id'], $id]);
    
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>