<?php
// includes/config.php - UPDATED VERSION
if (defined('CONFIG_LOADED')) {
    return;
}
define('CONFIG_LOADED', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'u815114538_GREENBRIDGE';
$username = getenv('DB_USER') ?: 'u815114538_admin';
$password = getenv('DB_PASS') ?: 'greenBridge1';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die("Database connection failed. Please contact the administrator.");
}

define('SITE_NAME', 'GreenBridge');

// Load system settings from JSON
$settings = [];
$settings_file = __DIR__ . '/settings.json';
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
}

// System Constants
define('MAX_FILE_SIZE', ($settings['max_file_size'] ?? 5) * 1024 * 1024); // Default to 5MB in Bytes
define('ALLOWED_IMAGES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_DOCUMENTS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Dynamic Site URL detection with fallback
$defaultSiteUrl = 'https://greenbridgedlsud.site/';
if (getenv('SITE_URL')) {
    $siteUrl = getenv('SITE_URL');
} else if (isset($_SERVER['HTTP_HOST'])) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $siteUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/';
} else {
    $siteUrl = $defaultSiteUrl;
}
define('SITE_URL', $siteUrl);

// API Response Helper Function
function sendJsonResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}
?>