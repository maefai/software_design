<?php
// api/get-messages.php
require_once '../includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Verify API token
$headers = getallheaders();
$token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

if (empty($token)) {
    sendJsonResponse(false, 'Authentication required');
}

$stmt = $conn->prepare("SELECT user_id FROM api_tokens WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tokenData) {
    sendJsonResponse(false, 'Invalid or expired token');
}

$user_id = $tokenData['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(false, 'Method not allowed', null, 405);
}

$with_user = $_GET['with_user'] ?? 0;

if (empty($with_user)) {
    sendJsonResponse(false, 'User ID required');
}

try {
    // Get or create conversation
    $stmt = $conn->prepare("SELECT id FROM conversations WHERE (participant1_id = ? AND participant2_id = ?) OR (participant1_id = ? AND participant2_id = ?)");
    $stmt->execute([$user_id, $with_user, $with_user, $user_id]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$conversation) {
        sendJsonResponse(true, 'No messages yet', ['messages' => []]);
    }
    
    // Get messages
    $stmt = $conn->prepare("SELECT m.*, 
                           CASE WHEN m.sender_id = ? THEN 'sent' ELSE 'received' END as direction
                           FROM messages m 
                           WHERE m.conversation_id = ? 
                           ORDER BY m.created_at ASC");
    $stmt->execute([$user_id, $conversation['id']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark messages as read
    $stmt = $conn->prepare("UPDATE messages SET is_read = 1, read_at = NOW() WHERE conversation_id = ? AND sender_id = ? AND is_read = 0");
    $stmt->execute([$conversation['id'], $with_user]);
    
    sendJsonResponse(true, 'Messages retrieved', ['messages' => $messages]);
    
} catch (PDOException $e) {
    sendJsonResponse(false, 'Database error');
}
?>