<?php
// oauth_signup.php
require_once 'includes/config.php';

// Check if we have OAuth data
if (!isset($_SESSION['oauth_email'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_type = $_POST['user_type'] ?? 'student';
    
    if ($user_type == 'student') {
        $fullname = $_POST['fullname'] ?? '';
        $student_id = $_POST['student_id'] ?? '';
        $university = $_POST['university'] ?? '';
        $college = $_POST['college'] ?? '';
        $course = $_POST['course'] ?? '';
        $year = $_POST['year_level'] ?? '';
        
        try {
            $conn->beginTransaction();
            
            // Insert into users table
            $userSql = "INSERT INTO users (user_type, email, status";
            $params = ['student', $_SESSION['oauth_email'], 'active'];
            
            if ($_SESSION['oauth_provider'] == 'google') {
                $userSql .= ", google_id";
                $params[] = $_SESSION['oauth_google_id'];
            } elseif ($_SESSION['oauth_provider'] == 'facebook') {
                $userSql .= ", facebook_id";
                $params[] = $_SESSION['oauth_facebook_id'];
            } elseif ($_SESSION['oauth_provider'] == 'microsoft') {
                $userSql .= ", microsoft_id";
                $params[] = $_SESSION['oauth_microsoft_id'];
            }
            
            $userSql .= ") VALUES (?, ?, ?";
            if (count($params) > 3) {
                $userSql .= ", ?";
            }
            $userSql .= ")";
            
            $userStmt = $conn->prepare($userSql);
            $userStmt->execute($params);
            $user_id = $conn->lastInsertId();
            
            // Insert into students table
            $studentSql = "INSERT INTO students (user_id, student_id, fullname, university, college, course, year_level, status, total_hours, required_hours) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'active', 0, 400)";
            $studentStmt = $conn->prepare($studentSql);
            $studentStmt->execute([$user_id, $student_id, $fullname, $university, $college, $course, $year]);
            
            $conn->commit();
            
            // Log the user in
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_type'] = 'student';
            $_SESSION['user_email'] = $_SESSION['oauth_email'];
            
            // Clear OAuth session data
            unset($_SESSION['oauth_email']);
            unset($_SESSION['oauth_name']);
            unset($_SESSION['oauth_provider']);
            unset($_SESSION['oauth_google_id']);
            unset($_SESSION['oauth_facebook_id']);
            unset($_SESSION['oauth_microsoft_id']);
            
            header("Location: student/dashboard.php");
            exit();
            
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Registration - GreenBridge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --forest: #1a3a24;
            --forest-mid: #2d5a3d;
            --mint: #4caf78;
            --sage: #eef3ec;
        }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--sage); }
        .register-container { max-width: 500px; margin: 50px auto; }
        .card { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .card-header { background: var(--forest); color: white; border-radius: 20px 20px 0 0; padding: 20px; }
        .btn-primary { background: var(--forest); border: none; }
        .btn-primary:hover { background: var(--forest-mid); }
        .form-control:focus { border-color: var(--mint); box-shadow: 0 0 0 0.2rem rgba(76,175,120,0.25); }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="card">
                <div class="card-header text-center">
                    <i class="bi bi-person-plus fs-1"></i>
                    <h4 class="mb-0">Complete Your Registration</h4>
                    <small>Welcome! Please provide additional details</small>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-envelope"></i> Email: <strong><?php echo htmlspecialchars($_SESSION['oauth_email']); ?></strong>
                        <br><small>You're signing up with <?php echo ucfirst($_SESSION['oauth_provider']); ?></small>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="user_type" value="student">
                        
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($_SESSION['oauth_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Student ID</label>
                            <input type="text" name="student_id" class="form-control" placeholder="e.g., 2022-12345" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">University</label>
                            <input type="text" name="university" class="form-control" value="De La Salle University" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">College</label>
                            <select name="college" class="form-select" required>
                                <option value="">Select College</option>
                                <option value="CCS">CCS - Computer Studies</option>
                                <option value="CEAT">CEAT - Engineering</option>
                                <option value="COB">COB - Business</option>
                                <option value="CAS">CAS - Arts & Sciences</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Course</label>
                            <input type="text" name="course" class="form-control" placeholder="e.g., BS Information Technology" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Year Level</label>
                            <select name="year_level" class="form-select" required>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-2">Complete Registration</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>