<?php
// microsoft_login.php
require_once 'includes/config.php';
require_once 'includes/oauth_config.php';

// Microsoft OAuth Authorization Endpoint
$auth_url = "https://login.microsoftonline.com/common/oauth2/v2.0/authorize?" . http_build_query([
    'client_id' => MICROSOFT_CLIENT_ID,
    'response_type' => 'code',
    'redirect_uri' => MICROSOFT_REDIRECT_URI,
    'response_mode' => 'query',
    'scope' => 'openid email profile User.Read',
    'state' => bin2hex(random_bytes(16))
]);

header("Location: " . $auth_url);
exit();
?>
