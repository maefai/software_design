<?php
// signup.php - Student Sign Up Page
require_once 'includes/config.php';
require_once 'includes/auth_functions.php';
require_once 'includes/upload_functions.php';
require_once 'includes/mail_helper.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isStudent()) header("Location: student/dashboard.php");
    elseif (isCompany()) header("Location: company/dashboard.php");
    elseif (isAdmin()) header("Location: admin/dashboard.php");
    exit();
}

$error = '';
$success = '';

// Student Sign Up
if (isset($_POST['btn_student_signup'])) {
    $fullname = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $university = $_POST['university'] ?? '';
    $course = $_POST['course'] ?? '';
    $year = $_POST['year_level'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Validation
    if (strlen($password) < 16 || strlen($password) > 32) {
        $error = "Password must be between 16 and 32 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        try {
            // Check if email exists
            $checkSql = "SELECT id FROM users WHERE email = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute([$email]);
            
            if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
                $error = "This email is already registered.";
            } else {
                // Start transaction
                $conn->beginTransaction();

                try {
                    // Insert into users table
                    $passHash = password_hash($password, PASSWORD_DEFAULT);
                    $userSql = "INSERT INTO users (user_type, email, password, status, created_at) VALUES ('student', ?, ?, 'pending', NOW())";
                    $userStmt = $conn->prepare($userSql);
                    $userStmt->execute([$email, $passHash]);

                    // Get the new user ID
                    $user_id = $conn->lastInsertId();

                    if (!$user_id) {
                        throw new Exception("Could not retrieve user ID");
                    }

                    // Insert into students table
                    $studentSql = "INSERT INTO students (user_id, fullname, university, course, year_level) VALUES (?, ?, ?, ?, ?)";
                    $studentStmt = $conn->prepare($studentSql);
                    $studentStmt->execute([$user_id, $fullname, $university, $course, $year]);

                    $conn->commit();
                    sendWelcomePendingEmail($email, $fullname, 'student');
                    $success = "Account created! Please wait for admin verification.";

                } catch (Exception $e) {
                    $conn->rollBack();
                    $error = "Registration failed: " . $e->getMessage();
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Green Bridge — Student Sign Up</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Fraunces:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="gb-blob blob-tl"></div>
<div class="gb-blob blob-br"></div>

<nav class="gb-navbar">
    <a class="gb-brand" href="index.php">
        <div class="gb-brand-icon">
            <img src="assets/images/logo.png" alt="GreenBridge Logo" style="width:100%;height:100%;object-fit:cover;"
                 onerror="this.style.display='none';this.parentElement.innerHTML='🌿'">
        </div>
        GREEN BRIDGE
    </a>
    
    <div class="gb-nav-links">
        <a class="gb-nav-link" href="index.php">Home</a>
        <a class="gb-nav-link" href="#">Internships</a>
        <a class="gb-nav-link" href="#">Companies</a>
        <a class="gb-nav-link" href="#">Resources</a>
    </div>
    
    <div class="d-flex align-items-center gap-2">
        <a href="index.php" class="btn-nav-outline">Log In</a>
        <a href="signup.php" class="btn-nav-solid">Sign Up</a>
    </div>
</nav>

<div class="page-wrap">
    <div class="auth-split">
        
        <div class="auth-left">
            <div class="al-logo">
                <div class="al-logo-icon">
                    <img src="assets/images/logo.png" alt="" style="width:100%;height:100%;object-fit:cover;"
                         onerror="this.style.display='none';this.parentElement.innerHTML='🌿'">
                </div>
                <span class="al-logo-text">Green Bridge</span>
            </div>
            
            <div class="al-body">
                <h2>Your Professional Journey Starts Here</h2>
                <p>DLSU's official partnered portal. Track your hours, submit weekly reports, and connect with top companies — all in one place.</p>
                <div class="al-features">
                    <div class="al-feature"><div class="al-feature-icon">🕐</div> Real-time DTR & hour tracking</div>
                    <div class="al-feature"><div class="al-feature-icon">📝</div> Digital logbook & supervisor feedback</div>
                    <div class="al-feature"><div class="al-feature-icon">📁</div> Document management & certificates</div>
                    <div class="al-feature"><div class="al-feature-icon">🔍</div> Discover & apply to OJT openings</div>
                </div>
            </div>
            
            <div class="al-footer">© <?= date('Y') ?> Green Bridge · De La Salle University · OJT Portal</div>
        </div>
        
        <div class="auth-right">
            
            <?php if (!empty($error)): ?>
                <div class="alert-message alert-error">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert-message alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <span class="portal-badge"><i class="bi bi-mortarboard-fill me-1"></i>Student Portal</span>
                <div class="auth-heading">Create Account</div>
                <p class="auth-subtitle">Already a member? <a href="index.php">Log in</a></p>
                
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-person"></i>
                        <input type="text" name="fullname" class="form-control" placeholder="e.g. Juan dela Cruz" value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">School Email</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-envelope"></i>
                        <input type="email" name="email" class="form-control" placeholder="you@dlsu.edu.ph" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">University / School</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-building"></i>
                        <input type="text" name="university" class="form-control" placeholder="e.g. De La Salle University" value="<?php echo isset($_POST['university']) ? htmlspecialchars($_POST['university']) : 'De La Salle University'; ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Course / Program</label>
                        <div class="select-wrapper">
                            <select name="course" class="form-select" required>
                                <option value="" disabled <?php echo !isset($_POST['course']) ? 'selected' : ''; ?>>Select your course</option>
                                <optgroup label="💻 Information Technology & Computing">
                                    <option value="BS Information Technology" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BS Information Technology') ? 'selected' : ''; ?>>BS Information Technology</option>
                                    <option value="BS Computer Science" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BS Computer Science') ? 'selected' : ''; ?>>BS Computer Science</option>
                                    <option value="BS Computer Engineering" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BS Computer Engineering') ? 'selected' : ''; ?>>BS Computer Engineering</option>
                                    <option value="BS Information Systems" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BS Information Systems') ? 'selected' : ''; ?>>BS Information Systems</option>
                                </optgroup>
                                <optgroup label="⚙️ Engineering">
                                    <option value="BS Electronics Engineering" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BS Electronics Engineering') ? 'selected' : ''; ?>>BS Electronics Engineering</option>
                                    <option value="BS Electrical Engineering" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BS Electrical Engineering') ? 'selected' : ''; ?>>BS Electrical Engineering</option>
                                    <option value="BS Civil Engineering" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BS Civil Engineering') ? 'selected' : ''; ?>>BS Civil Engineering</option>
                                    <option value="BS Mechanical Engineering" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BS Mechanical Engineering') ? 'selected' : ''; ?>>BS Mechanical Engineering</option>
                                </optgroup>
                                <optgroup label="💼 Business">
                                    <option value="BS Accountancy" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BS Accountancy') ? 'selected' : ''; ?>>BS Accountancy</option>
                                    <option value="BS Business Administration" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BS Business Administration') ? 'selected' : ''; ?>>BS Business Administration</option>
                                    <option value="BS Marketing Management" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BS Marketing Management') ? 'selected' : ''; ?>>BS Marketing Management</option>
                                </optgroup>
                                <optgroup label="🎨 Arts & Sciences">
                                    <option value="AB Communication" <?php echo (isset($_POST['course']) && $_POST['course'] == 'AB Communication') ? 'selected' : ''; ?>>AB Communication</option>
                                    <option value="BS Psychology" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BS Psychology') ? 'selected' : ''; ?>>BS Psychology</option>
                                    <option value="BS Biology" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BS Biology') ? 'selected' : ''; ?>>BS Biology</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Year Level</label>
                        <div class="select-wrapper">
                            <select name="year_level" class="form-select" required>
                                <option value="" disabled <?php echo !isset($_POST['year_level']) ? 'selected' : ''; ?>>Select year</option>
                                <option value="1st Year" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] == '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                                <option value="2nd Year" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] == '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                                <option value="3rd Year" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] == '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                                <option value="4th Year" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] == '4th Year') ? 'selected' : ''; ?>>4th Year</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password <span style="font-size:10px; font-weight:400; color:var(--text-lt);">(16–32 characters)</span></label>
                    <div class="pass-wrap">
                        <input type="password" name="password" id="password" class="form-control" placeholder="16–32 characters" minlength="16" maxlength="32" required>
                        <button type="button" class="pass-toggle" onclick="togglePassword('password', this)"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <div class="pass-wrap">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-enter password" minlength="16" maxlength="32" required>
                        <button type="button" class="pass-toggle" onclick="togglePassword('confirm_password', this)"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                
                <div class="form-check d-flex gap-2 mb-3">
                    <input class="form-check-input" type="checkbox" id="terms" required>
                    <label class="form-check-label" for="terms">
                        I agree to the <a href="#">Terms of Service</a> and confirm I am currently enrolled as a student.
                    </label>
                </div>
                
                <button type="submit" name="btn_student_signup" class="btn-submit">
                    <i class="bi bi-person-plus"></i> Create Account
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Password toggle function
function togglePassword(inputId, btn) {
    var input = document.getElementById(inputId);
    var icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    var password = document.getElementById('password').value;
    var confirm = document.getElementById('confirm_password').value;
    
    if (password !== confirm) {
        e.preventDefault();
        alert('Passwords do not match!');
    }
});
</script>

</body>
</html>