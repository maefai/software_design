<?php
// reset_password.php
require_once 'includes/config.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// Verify token
if ($token) {
    $stmt = $conn->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $error = "Invalid or expired reset token. Please request a new password reset.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($user['id'])) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $stmt->execute([$hashed, $user['id']]);
        
        $success = "Password has been reset successfully! You can now login.";
        // Clear token from URL
        $token = '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - GreenBridge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --forest: #1a3a24; --mint: #4caf78; }
        body { background: #eef3ec; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .reset-container { max-width: 450px; margin: 80px auto; }
        .card { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .card-header { background: var(--forest); color: white; border-radius: 20px 20px 0 0; padding: 20px; text-align: center; }
        .btn-primary { background: var(--forest); border: none; border-radius: 10px; padding: 12px; }
        .btn-primary:hover { background: #2d5a3d; }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-container">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-key fs-1"></i>
                    <h4 class="mb-0">Reset Password</h4>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <div class="text-center mt-3">
                            <a href="index.php" class="btn btn-primary">Go to Login</a>
                        </div>
                    <?php elseif (!$token || !isset($user['id'])): ?>
                        <div class="alert alert-danger">Invalid or expired reset link. Please request a new one.</div>
                        <div class="text-center mt-3">
                            <a href="index.php" class="btn btn-primary">Back to Login</a>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" id="password" class="form-control" minlength="8" required>
                                </div>
                                <small class="text-muted">Minimum 8 characters</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Simple password match validation
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>