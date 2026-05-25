<?php
// microsoft_callback.php
require_once 'includes/config.php';
require_once 'includes/auth_functions.php';
require_once 'includes/oauth_config.php';

if (!isset($_GET['code'])) {
    header("Location: index.php?error=microsoft_auth_failed");
    exit();
}

$code = $_GET['code'];

// Exchange code for access token
$token_url = "https://login.microsoftonline.com/common/oauth2/v2.0/token";
$post_data = [
    'client_id' => MICROSOFT_CLIENT_ID,
    'client_secret' => MICROSOFT_CLIENT_SECRET,
    'code' => $code,
    'redirect_uri' => MICROSOFT_REDIRECT_URI,
    'grant_type' => 'authorization_code',
    'scope' => 'openid email profile User.Read'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$response = curl_exec($ch);
curl_close($ch);

$token_data = json_decode($response, true);

if (!isset($token_data['access_token'])) {
    header("Location: index.php?error=microsoft_token_failed");
    exit();
}

// Query user details from Microsoft Graph API
$user_info_url = "https://graph.microsoft.com/v1.0/me";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $user_info_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token_data['access_token'],
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$user_response = curl_exec($ch);
curl_close($ch);

$user_data = json_decode($user_response, true);

if (!$user_data || (!isset($user_data['mail']) && !isset($user_data['userPrincipalName']))) {
    header("Location: index.php?error=microsoft_userinfo_failed");
    exit();
}

$email = $user_data['mail'] ?? $user_data['userPrincipalName'];
$name = $user_data['displayName'] ?? '';
$microsoft_id = $user_data['id'];

// Check if user exists in database
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR microsoft_id = ?");
$stmt->execute([$email, $microsoft_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // User exists - update microsoft_id if missing
    if (!$user['microsoft_id']) {
        $stmt = $conn->prepare("UPDATE users SET microsoft_id = ? WHERE id = ?");
        $stmt->execute([$microsoft_id, $user['id']]);
    }
    
    // Login user
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['user_email'] = $user['email'];
    
    // Update last login timestamp
    $updateSql = "UPDATE users SET last_login = NOW() WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->execute([$user['id']]);
    
    redirectAfterLogin($user['user_type']);
} else {
    // New user - redirect to registration form with prepopulated details
    $_SESSION['oauth_email'] = $email;
    $_SESSION['oauth_name'] = $name;
    $_SESSION['oauth_microsoft_id'] = $microsoft_id;
    $_SESSION['oauth_provider'] = 'microsoft';
    
    header("Location: oauth_signup.php");
    exit();
}

function redirectAfterLogin($user_type) {
    if ($user_type == 'student') {
        header("Location: student/dashboard.php");
    } elseif ($user_type == 'company') {
        header("Location: company/dashboard.php");
    } elseif ($user_type == 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}
?>
