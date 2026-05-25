<?php
// google_login.php
require_once 'includes/config.php';
require_once 'includes/oauth_config.php';

// Google OAuth URLs
$auth_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'email profile',
    'access_type' => 'offline',
    'prompt' => 'consent'
]);

header("Location: " . $auth_url);
exit();
?>