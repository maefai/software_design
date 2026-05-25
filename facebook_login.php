<?php
// facebook_login.php
require_once 'includes/config.php';
require_once 'includes/oauth_config.php';

$auth_url = "https://www.facebook.com/v18.0/dialog/oauth?" . http_build_query([
    'client_id' => FACEBOOK_APP_ID,
    'redirect_uri' => FACEBOOK_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'email,public_profile'
]);

header("Location: " . $auth_url);
exit();
?>