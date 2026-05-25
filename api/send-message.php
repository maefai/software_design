<?php
// api/send-message.php
require_once '../includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

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

$sender_id = $tokenData['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Method not allowed', null, 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$receiver_id = $input['receiver_id'] ?? 0;
$message = $input['message'] ?? '';

if (empty($receiver_id) || empty($message)) {
    sendJsonResponse(false, 'Receiver and message are required');
}

try {
    // Find or create conversation
    $stmt = $conn->prepare("SELECT id FROM conversations WHERE (participant1_id = ? AND participant2_id = ?) OR (participant1_id = ? AND participant2_id = ?)");
    $stmt->execute([$sender_id, $receiver_id, $receiver_id, $sender_id]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($conversation) {
        $conversation_id = $conversation['id'];
    } else {
        $stmt = $conn->prepare("INSERT INTO conversations (participant1_id, participant2_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$sender_id, $receiver_id]);
        $conversation_id = $conn->lastInsertId();
    }
    
    // Insert message
    $stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
    $stmt->execute([$conversation_id, $sender_id, $message]);
    
    // Update conversation last message
    $stmt = $conn->prepare("UPDATE conversations SET last_message = ?, last_message_time = NOW() WHERE id = ?");
    $stmt->execute([$message, $conversation_id]);
    
    sendJsonResponse(true, 'Message sent', ['message_id' => $conn->lastInsertId()]);
    
} catch (PDOException $e) {
    sendJsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>