<?php
// api/flag.php - Updated to use flagged_posts table
require_once '../includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Verify API token
$headers = getallheaders();
$token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

$stmt = $conn->prepare("SELECT user_id FROM api_tokens WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tokenData) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
    exit();
}

$user_id = $tokenData['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$item_type = $input['item_type'] ?? ''; // 'post' or 'comment'
$item_id = $input['item_id'] ?? 0;
$reason = $input['reason'] ?? '';
$details = $input['details'] ?? '';

if (empty($item_type) || empty($item_id) || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

if (!in_array($item_type, ['post', 'comment'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid item type']);
    exit();
}

try {
    $conn->beginTransaction();
    
    // Check if already flagged by this user
    if ($item_type === 'post') {
        $checkSql = "SELECT id FROM flagged_posts WHERE post_id = ? AND reported_by = ? AND status = 'pending'";
    } else {
        $checkSql = "SELECT id FROM flagged_comments WHERE comment_id = ? AND reported_by = ? AND status = 'pending'";
    }
    
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$item_id, $user_id]);
    
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You have already flagged this content']);
        exit();
    }
    
    // Insert into flagged table
    if ($item_type === 'post') {
        $sql = "INSERT INTO flagged_posts (post_id, reported_by, reason, details, status, created_at) 
                VALUES (?, ?, ?, ?, 'pending', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$item_id, $user_id, $reason, $details]);
        
        // Update post to show it's flagged (but not deleted)
        $updateStmt = $conn->prepare("UPDATE posts SET is_flagged = 1, flag_reason = ? WHERE id = ?");
        $updateStmt->execute([$reason, $item_id]);
        
    } else {
        $sql = "INSERT INTO flagged_comments (comment_id, reported_by, reason, details, status, created_at) 
                VALUES (?, ?, ?, ?, 'pending', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$item_id, $user_id, $reason, $details]);
        
        $updateStmt = $conn->prepare("UPDATE comments SET is_flagged = 1, flag_reason = ? WHERE id = ?");
        $updateStmt->execute([$reason, $item_id]);
    }
    
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Content has been reported to admin']);
    
} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>