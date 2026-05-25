<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Trying to include config.php...<br>";

$config_path = __DIR__ . '/../../includes/config.php';
echo "Path: " . $config_path . "<br>";

if (file_exists($config_path)) {
    echo "File exists! Including...<br>";
    include_once $config_path;
    echo "✅ config.php included successfully!<br>";
    
    // Check if connection works
    global $conn;
    if (isset($conn)) {
        echo "✅ Database connection established<br>";
    } else {
        echo "❌ Database connection not set<br>";
    }
} else {
    echo "❌ File does not exist!<br>";
}
?>