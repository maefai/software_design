<?php
// facebook_callback.php
require_once 'includes/config.php';
require_once 'includes/auth_functions.php';
require_once 'includes/oauth_config.php';

if (!isset($_GET['code'])) {
    header("Location: index.php?error=facebook_auth_failed");
    exit();
}

$code = $_GET['code'];

// Exchange code for access token
$token_url = "https://graph.facebook.com/v18.0/oauth/access_token?" . http_build_query([
    'client_id' => FACEBOOK_APP_ID,
    'client_secret' => FACEBOOK_APP_SECRET,
    'redirect_uri' => FACEBOOK_REDIRECT_URI,
    'code' => $code
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$token_data = json_decode($response, true);

if (!isset($token_data['access_token'])) {
    header("Location: index.php?error=facebook_token_failed");
    exit();
}

// Get user info
$user_info_url = "https://graph.facebook.com/v18.0/me?fields=id,name,email&access_token=" . $token_data['access_token'];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $user_info_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$user_response = curl_exec($ch);
curl_close($ch);

$user_data = json_decode($user_response, true);

if (!$user_data || !isset($user_data['email'])) {
    header("Location: index.php?error=facebook_userinfo_failed");
    exit();
}

// Check if user exists
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR facebook_id = ?");
$stmt->execute([$user_data['email'], $user_data['id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // User exists - update facebook_id if missing
    if (!$user['facebook_id']) {
        $stmt = $conn->prepare("UPDATE users SET facebook_id = ? WHERE id = ?");
        $stmt->execute([$user_data['id'], $user['id']]);
    }
    
    // Login user
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['user_email'] = $user['email'];
    
    redirectAfterLogin($user['user_type']);
} else {
    // New user - show signup form with pre-filled data
    $_SESSION['oauth_email'] = $user_data['email'];
    $_SESSION['oauth_name'] = $user_data['name'] ?? '';
    $_SESSION['oauth_facebook_id'] = $user_data['id'];
    $_SESSION['oauth_provider'] = 'facebook';
    
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