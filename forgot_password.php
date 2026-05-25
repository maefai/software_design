<?php
// forgot_password.php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

require_once 'includes/config.php';
require_once 'includes/mail_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit();
}

try {
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => true, 'message' => 'If an account exists, you will receive a password reset email.']);
        exit();
    }
    
    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
    $updateStmt->execute([$token, $expires, $user['id']]);
    
    $reset_link = SITE_URL . "reset_password.php?token=" . $token;
    
    // Get full name based on user_type
    $nameStmt = $conn->prepare("
        SELECT u.user_type, 
               COALESCE(s.fullname, c.company_name, 'User') as fullname
        FROM users u
        LEFT JOIN students s ON u.id = s.user_id
        LEFT JOIN companies c ON u.id = c.user_id
        WHERE u.id = ?
    ");
    $nameStmt->execute([$user['id']]);
    $profile = $nameStmt->fetch(PDO::FETCH_ASSOC);
    $fullname = $profile['fullname'] ?? 'User';
    
    $email_sent = sendResetEmail($email, $fullname, $reset_link);
    
    if ($email_sent) {
        echo json_encode(['success' => true, 'message' => 'Password reset link has been sent to your email.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'If an account exists, you will receive a password reset email.']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>