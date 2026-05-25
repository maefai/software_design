<?php
echo "Testing includes...<br>";

// Test if config.php exists
$config_path = '../includes/config.php';
if (file_exists($config_path)) {
    echo "✅ config.php found<br>";
    require_once $config_path;
    echo "✅ config.php loaded<br>";
} else {
    echo "❌ config.php NOT found at: $config_path<br>";
}

// Test if auth_functions.php exists
$auth_path = '../includes/auth_functions.php';
if (file_exists($auth_path)) {
    echo "✅ auth_functions.php found<br>";
    require_once $auth_path;
    echo "✅ auth_functions.php loaded<br>";
    
    // Check if function exists
    if (function_exists('requireStudent')) {
        echo "✅ requireStudent() function exists!<br>";
    } else {
        echo "❌ requireStudent() function does NOT exist!<br>";
    }
} else {
    echo "❌ auth_functions.php NOT found at: $auth_path<br>";
}

// Show current directory
echo "<br>Current directory: " . __DIR__ . "<br>";
echo "Script: " . __FILE__ . "<br>";
?>