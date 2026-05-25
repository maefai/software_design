<?php
// api/2fa-verify.php
require_once '../includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Method not allowed', null, 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$code = $input['code'] ?? '';
$temp_user_id = $_SESSION['2fa_temp_user_id'] ?? 0;

if (empty($code)) {
    sendJsonResponse(false, 'Verification code required');
}

// Check OTP expiration
if (!isset($_SESSION['2fa_otp_expires']) || time() > $_SESSION['2fa_otp_expires']) {
    unset($_SESSION['2fa_temp_user_id'], $_SESSION['2fa_otp'], $_SESSION['2fa_otp_expires']);
    sendJsonResponse(false, 'OTP expired. Please login again');
}

if ($code != $_SESSION['2fa_otp']) {
    sendJsonResponse(false, 'Invalid verification code');
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$temp_user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    sendJsonResponse(false, 'User not found');
}

// Generate API token
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+7 days'));

$stmt = $conn->prepare("INSERT INTO api_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
$stmt->execute([$user['id'], $token, $expires]);

// Clear 2FA session
unset($_SESSION['2fa_temp_user_id'], $_SESSION['2fa_otp'], $_SESSION['2fa_otp_expires']);

sendJsonResponse(true, '2FA verified successfully', [
    'token' => $token,
    'user' => [
        'id' => $user['id'],
        'email' => $user['email'],
        'type' => $user['user_type']
    ]
]);
?>