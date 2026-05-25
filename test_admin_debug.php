<?php
// test_admin_debug.php - Complete admin login debugger
require_once 'includes/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Admin Login Debugger</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .success { color: green; background: #e8f5e8; padding: 10px; border-radius: 5px; }
        .error { color: red; background: #ffe8e8; padding: 10px; border-radius: 5px; }
        .info { background: #e8f0fe; padding: 10px; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; background: white; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔍 Admin Login Debugger</h1>";

// Test 1: Database Connection
echo "<h2>Test 1: Database Connection</h2>";
if (isset($conn) && $conn instanceof PDO) {
    echo "<p class='success'>✅ Database connected successfully via PDO</p>";
} else {
    echo "<p class='error'>❌ Database connection failed</p>";
    exit();
}

// Test 2: Check if users table exists and get all users
echo "<h2>Test 2: All Users in Database</h2>";
try {
    $sql = "SELECT id, email, user_type, status, 
            CASE 
                WHEN password LIKE '$2y$%' THEN 'bcrypt hash'
                WHEN password LIKE '$2a$%' THEN 'bcrypt hash'
                ELSE 'unknown format'
            END as hash_type,
            LENGTH(password) as hash_length
            FROM users 
            ORDER BY user_type, id";
    $stmt = $conn->query($sql);
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Email</th><th>Type</th><th>Status</th><th>Hash Type</th><th>Hash Length</th></tr>";
    $has_users = false;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $has_users = true;
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['user_type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['hash_type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['hash_length']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if (!$has_users) {
        echo "<p class='error'>❌ No users found in database!</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Query failed: " . $e->getMessage() . "</p>";
}

// Test 3: Check specific admin users
echo "<h2>Test 3: Admin Users Only</h2>";
try {
    $sql = "SELECT id, email, user_type, status, password 
            FROM users 
            WHERE user_type = 'admin'";
    $stmt = $conn->query($sql);
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($admins) > 0) {
        echo "<p class='success'>✅ Found " . count($admins) . " admin(s)</p>";
        foreach ($admins as $admin) {
            echo "<div class='info'>";
            echo "<p><strong>ID:</strong> " . htmlspecialchars($admin['id']) . "</p>";
            echo "<p><strong>Email:</strong> " . htmlspecialchars($admin['email']) . "</p>";
            echo "<p><strong>Status:</strong> " . htmlspecialchars($admin['status']) . "</p>";
            echo "<p><strong>Password Hash:</strong> " . htmlspecialchars(substr($admin['password'], 0, 30)) . "...</p>";
            echo "</div>";
        }
    } else {
        echo "<p class='error'>❌ No admin users found!</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Query failed: " . $e->getMessage() . "</p>";
}

// Test 4: Test password verification
echo "<h2>Test 4: Password Verification Test</h2>";

$test_emails = ['admin@greenbridge.com', 'superadmin@greenbridge.com'];
$test_password = 'admin123';

foreach ($test_emails as $test_email) {
    echo "<h3>Testing: $test_email</h3>";
    
    try {
        $sql = "SELECT * FROM users WHERE email = ? AND user_type = 'admin'";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$test_email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo "<p class='error'>❌ User not found with email: $test_email</p>";
            continue;
        }
        
        echo "<p>✅ User found in database</p>";
        echo "<p>Stored hash: " . htmlspecialchars($user['password']) . "</p>";
        
        if (password_verify($test_password, $user['password'])) {
            echo "<p class='success'>✅ Password '$test_password' is CORRECT!</p>";
        } else {
            echo "<p class='error'>❌ Password '$test_password' is INCORRECT!</p>";
            $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
            echo "<p>New hash for '$test_password' would be: " . htmlspecialchars($new_hash) . "</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Query failed: " . $e->getMessage() . "</p>";
    }
}

// Test 5: Reset admin password directly via PHP
echo "<h2>Test 5: Password Reset Option</h2>";
echo "<p>Click the button below to reset admin passwords to 'admin123'</p>";
echo "<form method='POST'>";
echo "<input type='submit' name='reset_passwords' value='Reset Admin Passwords' style='padding:10px 20px; background:#4CAF50; color:white; border:none; border-radius:5px; cursor:pointer;'>";
echo "</form>";

if (isset($_POST['reset_passwords'])) {
    echo "<h3>Resetting passwords...</h3>";
    
    try {
        $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
        
        $sql1 = "UPDATE users SET password = ? WHERE email = 'admin@greenbridge.com'";
        $stmt1 = $conn->prepare($sql1);
        $res1 = $stmt1->execute([$new_hash]);
        
        if ($res1) {
            echo "<p class='success'>✅ Passwords reset successfully!</p>";
            echo "<p>New hash: " . htmlspecialchars($new_hash) . "</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Failed to reset passwords: " . $e->getMessage() . "</p>";
    }
}

// Test 6: Create a new test admin
echo "<h2>Test 6: Create Test Admin (Optional)</h2>";
echo "<form method='POST'>";
echo "<input type='submit' name='create_test_admin' value='Create Test Admin (test@test.com / test123)' style='padding:10px 20px; background:#2196F3; color:white; border:none; border-radius:5px; cursor:pointer;'>";
echo "</form>";

if (isset($_POST['create_test_admin'])) {
    $test_email = 'test@test.com';
    $test_pass = 'test123';
    $new_hash = password_hash($test_pass, PASSWORD_DEFAULT);
    
    try {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$test_email]);
        if ($check->fetch()) {
            echo "<p class='error'>❌ Test admin already exists!</p>";
        } else {
            $sql = "INSERT INTO users (user_type, email, password, status, created_at, verified_at) 
                    VALUES ('admin', ?, ?, 'active', NOW(), NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$test_email, $new_hash]);
            
            echo "<p class='success'>✅ Test admin created successfully!</p>";
            echo "<p>Email: test@test.com</p>";
            echo "<p>Password: test123</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Failed to create test admin: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Click 'Reset Admin Passwords' button above</li>";
echo "<li>Try logging in with admin@greenbridge.com / admin123</li>";
echo "</ol>";

echo "<p><a href='index.php?admin=1' style='display:inline-block; padding:10px 20px; background:#9b59b6; color:white; text-decoration:none; border-radius:5px;'>Go to Admin Login Page</a></p>";
?>