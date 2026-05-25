<?php
// api/login.php
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
    sendJsonResponse(false, 'Method not allowed', null, 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$user_type = $input['user_type'] ?? 'student';

if (empty($email) || empty($password)) {
    sendJsonResponse(false, 'Email and password required');
}

try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND user_type = ?");
    $stmt->execute([$email, $user_type]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($password, $user['password'])) {
        sendJsonResponse(false, 'Invalid credentials');
    }
    
    // Check 2FA
    if ($user['twofa_enabled']) {
        $otp = sprintf("%06d", random_int(0, 999999));
        $_SESSION['2fa_temp_user_id'] = $user['id'];
        $_SESSION['2fa_otp'] = $otp;
        $_SESSION['2fa_otp_expires'] = time() + 300;
        
        mail($email, "Your GreenBridge 2FA Code", "Your OTP code is: $otp\nValid for 5 minutes.", "From: noreply@" . $_SERVER['HTTP_HOST']);
        
        sendJsonResponse(false, '2FA required', ['requires_2fa' => true]);
    }
    
    // Generate API token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
    
    $stmt = $conn->prepare("INSERT INTO api_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user['id'], $token, $expires]);
    
    // Log login
    $stmt = $conn->prepare("INSERT INTO admin_actions (admin_id, action_type, target_type, target_id, ip_address, created_at) VALUES (?, 'login', 'user', ?, ?, NOW())");
    $stmt->execute([$user['id'], $user['id'], $_SERVER['REMOTE_ADDR']]);
    
    sendJsonResponse(true, 'Login successful', [
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'type' => $user['user_type']
        ]
    ]);
    
} catch (PDOException $e) {
    sendJsonResponse(false, 'Database error');
}
?>