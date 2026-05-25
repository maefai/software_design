<?php
// includes/oauth_config.php

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only define constants if not already defined
if (!defined('SITE_URL')) {
    define('SITE_URL', 'https://greenbridgedlsud.site/');
}

// Google OAuth Configuration
if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', '254401699849-v3ki4so9ifdtjsnr1qsj43iai5c6j26r.apps.googleusercontent.com');
}
if (!defined('GOOGLE_CLIENT_SECRET')) {
    define('GOOGLE_CLIENT_SECRET', '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');
}
if (!defined('GOOGLE_REDIRECT_URI')) {
    define('GOOGLE_REDIRECT_URI', SITE_URL . 'google_callback.php');
}

// Facebook OAuth Configuration  
if (!defined('FACEBOOK_APP_ID')) {
    define('FACEBOOK_APP_ID', '962245593096004');
}
if (!defined('FACEBOOK_APP_SECRET')) {
    define('FACEBOOK_APP_SECRET', '10a161f5c0ab447032269e26e50f60c0');
}
if (!defined('FACEBOOK_REDIRECT_URI')) {
    define('FACEBOOK_REDIRECT_URI', SITE_URL . 'facebook_callback.php');
}

// Microsoft OAuth Configuration
if (!defined('MICROSOFT_CLIENT_ID')) {
    define('MICROSOFT_CLIENT_ID', '4fc71000-9f70-4c50-a2e1-ea251642d9f0');
}
if (!defined('MICROSOFT_CLIENT_SECRET')) {
    define('MICROSOFT_CLIENT_SECRET', 'HXH8Q~rCRKa-Cw4B64XdZ5G-8VBkt14n4Wu_Mcs8');
}
if (!defined('MICROSOFT_REDIRECT_URI')) {
    define('MICROSOFT_REDIRECT_URI', SITE_URL . 'microsoft_callback.php');
}

// Email Configuration
if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', 'smtp.hostinger.com');
}
if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', 465);
}
if (!defined('SMTP_USER')) {
    define('SMTP_USER', 'noreply@greenbridgedlsud.site');
}
if (!defined('SMTP_PASS')) {
    define('SMTP_PASS', 'your-email-password');
}
if (!defined('SMTP_FROM')) {
    define('SMTP_FROM', 'noreply@greenbridgedlsud.site');
}
if (!defined('SMTP_FROM_NAME')) {
    define('SMTP_FROM_NAME', 'GreenBridge OJT Portal');
}

// Mailjet API Configuration
if (!defined('MAILJET_API_KEY')) {
    define('MAILJET_API_KEY', '1633278f8bbddac1df2b3765d8a6b342');
}
if (!defined('MAILJET_SECRET_KEY')) {
    define('MAILJET_SECRET_KEY', '6345dfe1e25c888ccb67d5d9af664711');
}
if (!defined('MAILJET_SENDER_EMAIL')) {
    define('MAILJET_SENDER_EMAIL', 'welcomegreenbridge@greenbridgedlsud.site');
}
if (!defined('MAILJET_SENDER_NAME')) {
    define('MAILJET_SENDER_NAME', 'GreenBridge OJT Portal');
}
?>