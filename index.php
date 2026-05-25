<?php
// account.php - Merged Login and Signup
require_once 'includes/config.php';
require_once 'includes/auth_functions.php';
require_once 'includes/upload_functions.php';
require_once 'includes/mail_helper.php';

// Initialize variables
$error = '';
$success = '';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isStudent()) header("Location: student/dashboard.php");
    elseif (isCompany()) header("Location: company/dashboard.php");
    elseif (isAdmin()) header("Location: admin/dashboard.php");
    exit();
}

/* =================================================
   ADMIN LOGIN
================================================= */
if (isset($_POST['btn_admin_login'])) {
    $email = $_POST['txt_admin_email'] ?? '';
    $pass = $_POST['txt_admin_password'] ?? '';

    try {
        $sql = "SELECT * FROM users WHERE email = ? AND user_type = 'admin'";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pass, $user['password'])) {
            if ($user['status'] == 'pending') {
                $error = "Your account is pending verification.";
            } elseif ($user['status'] == 'rejected') {
                $error = "Your account has been rejected. Please contact support.";
            } elseif ($user['status'] == 'suspended') {
                $error = "Your account has been suspended. Please contact support.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['user_email'] = $user['email'];
                
                $updateSql = "UPDATE users SET last_login = NOW() WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([$user['id']]);
                
                header("Location: admin/dashboard.php");
                exit();
            }
        } else {
            $error = "Invalid admin email or password.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

/* =================================================
   STUDENT LOGIN
================================================= */
if (isset($_POST['btn_student_login'])) {
    $email = $_POST['txt_email'] ?? '';
    $pass = $_POST['txt_password'] ?? '';

    try {
        $sql = "SELECT * FROM users WHERE email = ? AND user_type = 'student'";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pass, $user['password'])) {
            if ($user['status'] == 'pending') {
                $error = "Your account is pending verification. Please wait for admin approval.";
            } elseif ($user['status'] == 'rejected') {
                $error = "Your account has been rejected. Please contact support.";
            } elseif ($user['status'] == 'suspended') {
                $error = "Your account has been suspended. Please contact support.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['user_email'] = $user['email'];
                
                $updateSql = "UPDATE users SET last_login = NOW() WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([$user['id']]);
                
                header("Location: student/dashboard.php");
                exit();
            }
        } else {
            $error = "Invalid email or password.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

/* =================================================
   COMPANY LOGIN
================================================= */
if (isset($_POST['btn_company_login'])) {
    $email = $_POST['txt_comp_email'] ?? '';
    $pass = $_POST['txt_comp_password'] ?? '';

    try {
        $sql = "SELECT * FROM users WHERE email = ? AND user_type = 'company'";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pass, $user['password'])) {
            if ($user['status'] == 'pending') {
                $error = "Your account is pending verification. Please wait for admin approval.";
            } elseif ($user['status'] == 'rejected') {
                $error = "Your account has been rejected. Please contact support.";
            } elseif ($user['status'] == 'suspended') {
                $error = "Your account has been suspended. Please contact support.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['user_email'] = $user['email'];
                
                $updateSql = "UPDATE users SET last_login = NOW() WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([$user['id']]);
                
                header("Location: company/dashboard.php");
                exit();
            }
        } else {
            $error = "Invalid email or password.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

/* =================================================
   STUDENT SIGN UP - FIXED
================================================= */
if (isset($_POST['btn_student_signup'])) {
    $fullname = $_POST['txt_fullname'] ?? '';
    $email = $_POST['txt_email'] ?? '';
    $student_id = $_POST['txt_student_id'] ?? '';
    $university = $_POST['txt_school'] ?? '';
    $college = $_POST['sel_college'] ?? '';
    $course = $_POST['sel_course'] ?? '';
    $year = $_POST['sel_year'] ?? '';
    $password = $_POST['txt_password'] ?? '';
    $confirm = $_POST['txt_confirm_password'] ?? '';

    // Validation
    if (strlen($password) < 16 || strlen($password) > 32) {
        $error = "Password must be between 16 and 32 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (empty($student_id)) {
        $error = "Student ID is required.";
    } elseif (empty($college)) {
        $error = "College is required.";
    } else {
        try {
            // Check if email exists
            $checkSql = "SELECT id FROM users WHERE email = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute([$email]);
            
            if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
                $error = "This email is already registered.";
            } else {
                // Check if student_id already exists
                $checkStudentSql = "SELECT id FROM students WHERE student_id = ?";
                $checkStudentStmt = $conn->prepare($checkStudentSql);
                $checkStudentStmt->execute([$student_id]);
                
                if ($checkStudentStmt->fetch(PDO::FETCH_ASSOC)) {
                    $error = "This Student ID is already registered.";
                } else {
                    // Start transaction
                    $conn->beginTransaction();

                    try {
                        // Insert into users table
                        $passHash = password_hash($password, PASSWORD_DEFAULT);
                        // Replaced OUTPUT INSERTED with standard insert, followed by lastInsertId()
                        $userSql = "INSERT INTO users (user_type, email, password, status, created_at) VALUES ('student', ?, ?, 'pending', NOW())";
                        $userStmt = $conn->prepare($userSql);
                        $userStmt->execute([$email, $passHash]);
                        
                        $user_id = $conn->lastInsertId();
                        
                        if (!$user_id) {
                            throw new Exception("Could not retrieve user ID");
                        }

                        // Insert into students table with ALL columns
                        $studentSql = "INSERT INTO students 
                        (user_id, student_id, fullname, university, college, course, year_level, status, total_hours, required_hours) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 0, 400)";
                        
                        $studentParams = [
                            $user_id, 
                            $student_id, 
                            $fullname, 
                            $university, 
                            $college, 
                            $course, 
                            $year
                        ];
                        
                        $studentStmt = $conn->prepare($studentSql);
                        $studentStmt->execute($studentParams);

                        $conn->commit();
                        sendWelcomePendingEmail($email, $fullname, 'student');
                        $success = "Account created! Please wait for admin verification.";

                    } catch (Exception $e) {
                        $conn->rollBack();
                        $error = "Registration failed: " . $e->getMessage();
                    }
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

/* =================================================
   COMPANY SIGN UP - FIXED
================================================= */
if (isset($_POST['btn_company_signup'])) {
    $company_name = $_POST['txt_comp_name'] ?? '';
    $email = $_POST['txt_comp_email'] ?? '';
    $industry = $_POST['sel_industry'] ?? '';
    $website = $_POST['txt_web'] ?? '';
    $contact_person = $_POST['txt_contact'] ?? '';
    $contact_position = $_POST['txt_contact_position'] ?? '';
    $contact_email = $_POST['txt_contact_email'] ?? '';
    $contact_phone = $_POST['txt_contact_phone'] ?? '';
    $company_address = $_POST['txt_address'] ?? '';
    $password = $_POST['txt_comp_password'] ?? '';
    $confirm = $_POST['txt_comp_confirm_password'] ?? '';

    // Validation
    if (strlen($password) < 16 || strlen($password) > 32) {
        $error = "Password must be between 16 and 32 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (empty($company_name)) {
        $error = "Company name is required.";
    } else {
        try {
            // Check if email exists
            $checkSql = "SELECT id FROM users WHERE email = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute([$email]);
            
            if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
                $error = "This email is already registered.";
            } else {
                // Handle verification document upload
                $verification_file = null;
                if (isset($_FILES['verification_doc']) && $_FILES['verification_doc']['error'] == UPLOAD_ERR_OK) {
                    $upload = uploadFile($_FILES['verification_doc'], 'company/', ['pdf', 'jpg', 'jpeg', 'png']);
                    if ($upload['success']) {
                        $verification_file = $upload['filepath'];
                    } else {
                        $error = "File upload failed: " . ($upload['error'] ?? 'Unknown error');
                    }
                } else {
                    $error = "Please upload verification document.";
                }

                if (empty($error)) {
                    // Start transaction
                    $conn->beginTransaction();

                    try {
                        // Insert into users table
                        $passHash = password_hash($password, PASSWORD_DEFAULT);
                        // Replaced OUTPUT INSERTED with standard insert, followed by lastInsertId()
                        $userSql = "INSERT INTO users (user_type, email, password, status, created_at) VALUES ('company', ?, ?, 'pending', NOW())";
                        $userStmt = $conn->prepare($userSql);
                        $userStmt->execute([$email, $passHash]);

                        $user_id = $conn->lastInsertId();

                        if (!$user_id) {
                            throw new Exception("Could not retrieve user ID");
                        }

                        // Insert into companies table
                        $companySql = "INSERT INTO companies 
                            (user_id, company_name, industry, website, contact_person, contact_position, contact_email, contact_phone, company_address, verification_document, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                        
                        $companyParams = [
                            $user_id, 
                            $company_name, 
                            $industry, 
                            $website, 
                            $contact_person, 
                            $contact_position, 
                            $contact_email, 
                            $contact_phone, 
                            $company_address, 
                            $verification_file
                        ];
                        
                        $companyStmt = $conn->prepare($companySql);
                        $companyStmt->execute($companyParams);

                        $conn->commit();
                        sendWelcomePendingEmail($email, $company_name, 'company');
                        $success = "Company account created! Please wait for admin verification.";

                    } catch (Exception $e) {
                        $conn->rollBack();
                        $error = "Registration failed: " . $e->getMessage();
                    }
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
<title>Green Bridge — Login & Sign Up</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Fraunces:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">

</head>
<body>

<div class="gb-blob blob-tl"></div>
<div class="gb-blob blob-br"></div>

<nav class="gb-navbar">
  <a class="gb-brand" href="#">
    <div class="gb-brand-icon">
      <img src="assets/images/logo.png" alt="GreenBridge Logo"
           onerror="this.style.display='none';this.parentElement.innerHTML='🌿'">
    </div>
    GREEN BRIDGE
  </a>

  <div class="gb-nav-links">
    <a class="gb-nav-link" href="#">Home</a>
    <a class="gb-nav-link" href="#">Internships</a>
    <a class="gb-nav-link" href="#">Companies</a>
    <a class="gb-nav-link" href="#">Resources</a>
  </div>

  <div class="d-flex align-items-center gap-2 ms-auto gb-navbar-btns">
    <button class="btn-nav-outline" onclick="showForm(currentType,'login')">Log In</button>
    <button class="btn-nav-solid" onclick="showForm(currentType,'signup')">Sign Up</button>
    <button class="btn-nav-admin" onclick="window.location.href='index.php?admin=1'">
      <i class="bi bi-shield-lock me-1"></i>Admin
    </button>
  </div>
</nav>

<div class="page-wrap">
  <div class="auth-split">

    <div class="auth-left">
      <div class="al-logo">
        <div class="al-logo-icon">
          <img src="assets/images/logo.png" alt=""
               onerror="this.style.display='none';this.parentElement.innerHTML='🌿'">
        </div>
        <span class="al-logo-text">Green Bridge</span>
      </div>

      <div class="al-body">
        <h2>Your Professional Journey Starts Here</h2>
        <p>DLSUD's official partnered portal. Track your hours, submit weekly reports, and connect with top companies — all in one place.</p>
        <div class="al-features">
          <div class="al-feature"><div class="al-feature-icon">🕐</div> Real-time DTR &amp; hour tracking</div>
          <div class="al-feature"><div class="al-feature-icon">📝</div> Digital logbook &amp; supervisor feedback</div>
          <div class="al-feature"><div class="al-feature-icon">📁</div> Document management &amp; certificates</div>
          <div class="al-feature"><div class="al-feature-icon">🔍</div> Discover &amp; apply to OJT openings</div>
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

      <div class="type-toggle">
        <button type="button" class="type-btn active" id="btn-student" onclick="setUserType('student')">
          <i class="bi bi-mortarboard me-1"></i> Student
        </button>
        <button type="button" class="type-btn" id="btn-company" onclick="setUserType('company')">
          <i class="bi bi-building me-1"></i> Company
        </button>
      </div>

      <div class="auth-panel active" id="page-student-login">
        <form method="POST" action="">
          <span class="portal-badge"><i class="bi bi-mortarboard-fill me-1"></i>Student Portal</span>
          <div class="auth-heading">Welcome back</div>
          <p class="auth-subtitle">New here? <a href="#" onclick="showForm('student','signup')">Create an account</a></p>

          <div class="mb-3">
            <label class="form-label">School Email</label>
            <div class="input-icon-wrap">
              <i class="bi bi-envelope"></i>
              <input type="email" name="txt_email" class="form-control" placeholder="student@dlsud.edu.ph" required>
            </div>
          </div>

          <div class="mb-1">
            <label class="form-label">Password</label>
            <div class="pass-wrap">
              <input type="password" name="txt_password" id="pw-sl" class="form-control" placeholder="••••••••" required>
              <button type="button" class="pass-toggle" onclick="togglePass('pw-sl',this)"><i class="bi bi-eye"></i></button>
            </div>
          </div>

          <a href="#" class="forgot-link" onclick="openFP();return false;">Forgot password?</a>

          <button type="submit" name="btn_student_login" class="btn-submit">
            <i class="bi bi-box-arrow-in-right"></i> Log In
          </button>

          <!-- Replace the existing social buttons with these -->
            <div class="or-divider">or continue with</div>
            <div class="d-flex gap-2">
                <a href="google_login.php" class="btn-social text-decoration-none" style="flex: 1;">
                    <svg width="18" height="18" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Google
                </a>
                <a href="facebook_login.php" class="btn-social text-decoration-none" style="flex: 1;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    Facebook
                </a>
                <a href="microsoft_login.php" class="btn-social text-decoration-none" style="flex: 1;">
                    <svg width="18" height="18" viewBox="0 0 23 23" style="margin-right: 2px;">
                        <path fill="#f35325" d="M0 0h11v11H0z"/>
                        <path fill="#81bc06" d="M12 0h11v11H12z"/>
                        <path fill="#05a6f0" d="M0 12h11v11H0z"/>
                        <path fill="#ffba08" d="M12 12h11v11H12z"/>
                    </svg>
                    Microsoft
                </a>
            </div>
        </form>
      </div>

      <div class="auth-panel" id="page-student-signup">
        <form method="POST" action="">
          <span class="portal-badge"><i class="bi bi-mortarboard-fill me-1"></i>Student Portal</span>
          <div class="auth-heading">Create Account</div>
          <p class="auth-subtitle">Already a member? <a href="#" onclick="showForm('student','login')">Log in</a></p>

          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <div class="input-icon-wrap">
              <i class="bi bi-person"></i>
              <input type="text" name="txt_fullname" class="form-control" placeholder="e.g. Juan dela Cruz" value="<?php echo isset($_POST['txt_fullname']) ? htmlspecialchars($_POST['txt_fullname']) : ''; ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Student ID</label>
            <div class="input-icon-wrap">
              <i class="bi bi-card-heading"></i>
              <input type="text" name="txt_student_id" class="form-control" placeholder="2022-12345" value="<?php echo isset($_POST['txt_student_id']) ? htmlspecialchars($_POST['txt_student_id']) : ''; ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">School Email</label>
            <div class="input-icon-wrap">
              <i class="bi bi-envelope"></i>
              <input type="email" name="txt_email" class="form-control" placeholder="you@dlsud.edu.ph" value="<?php echo isset($_POST['txt_email']) ? htmlspecialchars($_POST['txt_email']) : ''; ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">University / School</label>
            <div class="input-icon-wrap">
              <i class="bi bi-building"></i>
              <input type="text" name="txt_school" class="form-control" placeholder="e.g. De La Salle University" value="<?php echo isset($_POST['txt_school']) ? htmlspecialchars($_POST['txt_school']) : ''; ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">College</label>
            <div class="select-wrapper">
              <select name="sel_college" class="form-select" required>
                <option value="" disabled <?php echo !isset($_POST['sel_college']) ? 'selected' : ''; ?>>Select College</option>
                <option value="CCS" <?php echo (isset($_POST['sel_college']) && $_POST['sel_college'] == 'CCS') ? 'selected' : ''; ?>>CCS - Computer Studies</option>
                <option value="CEAT" <?php echo (isset($_POST['sel_college']) && $_POST['sel_college'] == 'CEAT') ? 'selected' : ''; ?>>CEAT - Engineering</option>
                <option value="COB" <?php echo (isset($_POST['sel_college']) && $_POST['sel_college'] == 'COB') ? 'selected' : ''; ?>>COB - Business</option>
                <option value="CAS" <?php echo (isset($_POST['sel_college']) && $_POST['sel_college'] == 'CAS') ? 'selected' : ''; ?>>CAS - Arts & Sciences</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Course / Program</label>
            <div class="select-wrapper">
              <select name="sel_course" class="form-select" required>
                <option value="" disabled selected>Select your course</option>
                <optgroup label="💻 Information Technology & Computing">
                  <option value="BS Information Technology" <?php echo (isset($_POST['sel_course']) && $_POST['sel_course'] == 'BS Information Technology') ? 'selected' : ''; ?>>BS Information Technology</option>
                  <option value="BS Computer Science" <?php echo (isset($_POST['sel_course']) && $_POST['sel_course'] == 'BS Computer Science') ? 'selected' : ''; ?>>BS Computer Science</option>
                  <option value="BS Computer Engineering" <?php echo (isset($_POST['sel_course']) && $_POST['sel_course'] == 'BS Computer Engineering') ? 'selected' : ''; ?>>BS Computer Engineering</option>
                  <option value="BS Information Systems" <?php echo (isset($_POST['sel_course']) && $_POST['sel_course'] == 'BS Information Systems') ? 'selected' : ''; ?>>BS Information Systems</option>
                </optgroup>
                <optgroup label="⚙️ Engineering">
                  <option value="BS Electronics Engineering" <?php echo (isset($_POST['sel_course']) && $_POST['sel_course'] == 'BS Electronics Engineering') ? 'selected' : ''; ?>>BS Electronics Engineering</option>
                  <option value="BS Electrical Engineering" <?php echo (isset($_POST['sel_course']) && $_POST['sel_course'] == 'BS Electrical Engineering') ? 'selected' : ''; ?>>BS Electrical Engineering</option>
                  <option value="BS Civil Engineering" <?php echo (isset($_POST['sel_course']) && $_POST['sel_course'] == 'BS Civil Engineering') ? 'selected' : ''; ?>>BS Civil Engineering</option>
                  <option value="BS Mechanical Engineering" <?php echo (isset($_POST['sel_course']) && $_POST['sel_course'] == 'BS Mechanical Engineering') ? 'selected' : ''; ?>>BS Mechanical Engineering</option>
                </optgroup>
                <optgroup label="💼 Business">
                  <option value="BS Accountancy" <?php echo (isset($_POST['sel_course']) && $_POST['sel_course'] == 'BS Accountancy') ? 'selected' : ''; ?>>BS Accountancy</option>
                  <option value="BS Business Administration" <?php echo (isset($_POST['sel_course']) && $_POST['sel_course'] == 'BS Business Administration') ? 'selected' : ''; ?>>BS Business Administration</option>
                  <option value="BS Marketing Management" <?php echo (isset($_POST['sel_course']) && $_POST['sel_course'] == 'BS Marketing Management') ? 'selected' : ''; ?>>BS Marketing Management</option>
                </optgroup>
                <optgroup label="🎨 Arts & Sciences">
                  <option value="AB Communication" <?php echo (isset($_POST['sel_course']) && $_POST['sel_course'] == 'AB Communication') ? 'selected' : ''; ?>>AB Communication</option>
                  <option value="BS Psychology" <?php echo (isset($_POST['sel_course']) && $_POST['sel_course'] == 'BS Psychology') ? 'selected' : ''; ?>>BS Psychology</option>
                  <option value="BS Biology" <?php echo (isset($_POST['sel_course']) && $_POST['sel_course'] == 'BS Biology') ? 'selected' : ''; ?>>BS Biology</option>
                </optgroup>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Year Level</label>
            <div class="select-wrapper">
              <select name="sel_year" class="form-select" required>
                <option value="" disabled <?php echo !isset($_POST['sel_year']) ? 'selected' : ''; ?>>Select year level</option>
                <option value="1st Year" <?php echo (isset($_POST['sel_year']) && $_POST['sel_year'] == '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                <option value="2nd Year" <?php echo (isset($_POST['sel_year']) && $_POST['sel_year'] == '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                <option value="3rd Year" <?php echo (isset($_POST['sel_year']) && $_POST['sel_year'] == '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                <option value="4th Year" <?php echo (isset($_POST['sel_year']) && $_POST['sel_year'] == '4th Year') ? 'selected' : ''; ?>>4th Year</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Password <span style="font-size:10px;font-weight:400;color:var(--text-lt)">(16–32 characters)</span></label>
            <div class="pass-wrap">
              <input type="password" name="txt_password" id="pw-ss" class="form-control" placeholder="16–32 characters" minlength="16" maxlength="32" required>
              <button type="button" class="pass-toggle" onclick="togglePass('pw-ss',this)"><i class="bi bi-eye"></i></button>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <div class="pass-wrap">
              <input type="password" name="txt_confirm_password" id="pw-cs" class="form-control" placeholder="Re-enter password" minlength="16" maxlength="32" required>
              <button type="button" class="pass-toggle" onclick="togglePass('pw-cs',this)"><i class="bi bi-eye"></i></button>
            </div>
          </div>

          <div class="form-check d-flex gap-2 mb-3">
            <input class="form-check-input" type="checkbox" id="terms-student" required>
            <label class="form-check-label" for="terms-student">
              I agree to the <a href="#">Terms of Service</a> and confirm I am currently enrolled as a student.
            </label>
          </div>

          <button type="submit" name="btn_student_signup" class="btn-submit">
            <i class="bi bi-person-plus"></i> Create Account
          </button>
        </form>
      </div>

      <div class="auth-panel" id="page-company-login">
        <form method="POST" action="">
          <span class="portal-badge company"><i class="bi bi-buildings-fill me-1"></i>Company Portal</span>
          <div class="auth-heading">Company Login</div>
          <p class="auth-subtitle">Not yet registered? <a href="#" onclick="showForm('company','signup')">Sign up</a></p>

          <div class="mb-3">
            <label class="form-label">Company Email</label>
            <div class="input-icon-wrap">
              <i class="bi bi-envelope"></i>
              <input type="email" name="txt_comp_email" class="form-control" placeholder="hr@yourcompany.com" required>
            </div>
          </div>

          <div class="mb-1">
            <label class="form-label">Password</label>
            <div class="pass-wrap">
              <input type="password" name="txt_comp_password" id="pw-cl" class="form-control" placeholder="Your password" required>
              <button type="button" class="pass-toggle" onclick="togglePass('pw-cl',this)"><i class="bi bi-eye"></i></button>
            </div>
          </div>

          <a href="#" class="forgot-link" onclick="openFP();return false;">Forgot password?</a>

          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="rm-s"/>
            <label class="form-check-label" for="rm-s">Remember me on this device</label>
          </div>

          <button class="btn-submit" name="btn_company_login" type="submit">
            <i class="bi bi-box-arrow-in-right"></i> Log In as Company
          </button>

          <div class="or-divider">or continue with</div>
          <div class="d-flex gap-2">
            <button type="button" class="btn-social" style="flex: 1;">
              <svg width="16" height="16" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
              Google
            </button>
          </div>
        </form>
      </div>

      <div class="auth-panel" id="page-company-signup">
        <form method="POST" action="index.php" enctype="multipart/form-data">
          <span class="portal-badge company"><i class="bi bi-buildings-fill me-1"></i>Company Portal</span>
          <div class="auth-heading">Register Company</div>
          <p class="auth-subtitle">Already registered? <a href="#" onclick="showForm('company','login')">Log in</a></p>

          <div class="mb-3">
            <label class="form-label">Company Name</label>
            <div class="input-icon-wrap">
              <i class="bi bi-buildings"></i>
              <input type="text" name="txt_comp_name" class="form-control" placeholder="e.g. Accenture Philippines" value="<?php echo isset($_POST['txt_comp_name']) ? htmlspecialchars($_POST['txt_comp_name']) : ''; ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Company Email (Login)</label>
            <div class="input-icon-wrap">
              <i class="bi bi-envelope"></i>
              <input type="email" name="txt_comp_email" class="form-control" placeholder="hr@yourcompany.com" value="<?php echo isset($_POST['txt_comp_email']) ? htmlspecialchars($_POST['txt_comp_email']) : ''; ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Industry</label>
            <div class="select-wrapper">
              <select name="sel_industry" class="form-select" required>
                <option value="" disabled <?php echo !isset($_POST['sel_industry']) ? 'selected' : ''; ?>>Select industry</option>
                <option value="Information Technology" <?php echo (isset($_POST['sel_industry']) && $_POST['sel_industry'] == 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                <option value="Finance & Banking" <?php echo (isset($_POST['sel_industry']) && $_POST['sel_industry'] == 'Finance & Banking') ? 'selected' : ''; ?>>Finance & Banking</option>
                <option value="Healthcare" <?php echo (isset($_POST['sel_industry']) && $_POST['sel_industry'] == 'Healthcare') ? 'selected' : ''; ?>>Healthcare</option>
                <option value="Engineering" <?php echo (isset($_POST['sel_industry']) && $_POST['sel_industry'] == 'Engineering') ? 'selected' : ''; ?>>Engineering</option>
                <option value="Marketing & Advertising" <?php echo (isset($_POST['sel_industry']) && $_POST['sel_industry'] == 'Marketing & Advertising') ? 'selected' : ''; ?>>Marketing & Advertising</option>
                <option value="Education" <?php echo (isset($_POST['sel_industry']) && $_POST['sel_industry'] == 'Education') ? 'selected' : ''; ?>>Education</option>
                <option value="Manufacturing" <?php echo (isset($_POST['sel_industry']) && $_POST['sel_industry'] == 'Manufacturing') ? 'selected' : ''; ?>>Manufacturing</option>
                <option value="Retail & E-Commerce" <?php echo (isset($_POST['sel_industry']) && $_POST['sel_industry'] == 'Retail & E-Commerce') ? 'selected' : ''; ?>>Retail & E-Commerce</option>
                <option value="Other" <?php echo (isset($_POST['sel_industry']) && $_POST['sel_industry'] == 'Other') ? 'selected' : ''; ?>>Other</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Company Website</label>
            <div class="input-icon-wrap">
              <i class="bi bi-globe"></i>
              <input type="url" name="txt_web" class="form-control" placeholder="https://yourcompany.com" value="<?php echo isset($_POST['txt_web']) ? htmlspecialchars($_POST['txt_web']) : ''; ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Contact Position</label>
            <div class="input-icon-wrap">
              <i class="bi bi-briefcase"></i>
              <input type="text" name="txt_contact_position" class="form-control" placeholder="e.g. HR Director" value="<?php echo isset($_POST['txt_contact_position']) ? htmlspecialchars($_POST['txt_contact_position']) : ''; ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">HR / Contact Person</label>
            <div class="input-icon-wrap">
              <i class="bi bi-person-badge"></i>
              <input type="text" name="txt_contact" class="form-control" placeholder="Full name of HR representative" value="<?php echo isset($_POST['txt_contact']) ? htmlspecialchars($_POST['txt_contact']) : ''; ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Contact Email</label>
            <div class="input-icon-wrap">
              <i class="bi bi-envelope"></i>
              <input type="email" name="txt_contact_email" class="form-control" placeholder="hr.manager@company.com" value="<?php echo isset($_POST['txt_contact_email']) ? htmlspecialchars($_POST['txt_contact_email']) : ''; ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Contact Phone</label>
            <div class="input-icon-wrap">
              <i class="bi bi-telephone"></i>
              <input type="text" name="txt_contact_phone" class="form-control" placeholder="+63 2 1234 5678" value="<?php echo isset($_POST['txt_contact_phone']) ? htmlspecialchars($_POST['txt_contact_phone']) : ''; ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Company Address</label>
            <div class="input-icon-wrap">
              <i class="bi bi-geo-alt"></i>
              <textarea name="txt_address" class="form-control" rows="2" placeholder="Company address"><?php echo isset($_POST['txt_address']) ? htmlspecialchars($_POST['txt_address']) : ''; ?></textarea>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Verification Document (SEC/DTI Registration)</label>
            <input type="file" name="verification_doc" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
            <small class="text-muted">Upload PDF or image (max 5MB). This will be verified by admin.</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Password <span style="font-size:10px;font-weight:400;color:var(--text-lt)">(16–32 characters)</span></label>
            <div class="pass-wrap">
              <input type="password" name="txt_comp_password" id="pw-cp" class="form-control" placeholder="At least 16 characters" minlength="16" maxlength="32" required>
              <button type="button" class="pass-toggle" onclick="togglePass('pw-cp',this)"><i class="bi bi-eye"></i></button>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <div class="pass-wrap">
              <input type="password" name="txt_comp_confirm_password" id="pw-cc" class="form-control" placeholder="Re-enter password" minlength="16" maxlength="32" required>
              <button type="button" class="pass-toggle" onclick="togglePass('pw-cc',this)"><i class="bi bi-eye"></i></button>
            </div>
          </div>

          <div class="form-check d-flex gap-2 mb-3">
            <input class="form-check-input" type="checkbox" id="terms-company" required>
            <label class="form-check-label" for="terms-company">
              I agree to the <a href="#">Terms of Service</a> and confirm I am authorized to register this company.
            </label>
          </div>

          <button class="btn-submit" name="btn_company_signup" type="submit">
            <i class="bi bi-building-add"></i> Register Company
          </button>
        </form>
      </div>

      <div class="auth-panel" id="page-admin-login">
        <form method="POST" action="">
          <span class="portal-badge" style="background:rgba(155,89,182,0.15); color:#9b59b6; border-color:rgba(155,89,182,0.3);">
            <i class="bi bi-shield-lock-fill me-1"></i>Admin Portal
          </span>
          <div class="auth-heading">Admin Login</div>
          <p class="auth-subtitle">Secure access for administrators only.</p>

          <div class="mb-3">
            <label class="form-label">Admin Email</label>
            <div class="input-icon-wrap">
              <i class="bi bi-envelope"></i>
              <input type="email" name="txt_admin_email" class="form-control" placeholder="admin@greenbridge.edu.ph" required>
            </div>
          </div>

          <div class="mb-1">
            <label class="form-label">Password</label>
            <div class="pass-wrap">
              <input type="password" name="txt_admin_password" id="pw-admin" class="form-control" placeholder="••••••••" required>
              <button type="button" class="pass-toggle" onclick="togglePass('pw-admin',this)"><i class="bi bi-eye"></i></button>
            </div>
          </div>

          <button type="submit" name="btn_admin_login" class="btn-submit" style="background:#9b59b6;">
            <i class="bi bi-shield-lock-fill"></i> Access Admin Panel
          </button>
        </form>
      </div>

    </div></div></div>
    <!-- SIMPLE FORGOT PASSWORD MODAL - Single Step -->
<div class="fp-overlay" id="fp-overlay" onclick="fpOverlayClick(event)">
  <div class="fp-box">
    <div class="fp-head">
      <div class="fp-head-top">
        <div class="fp-icon-wrap"><i class="bi bi-envelope"></i></div>
        <button class="fp-close" onclick="closeFP()"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="fp-title">Forgot Password?</div>
      <div class="fp-sub">Enter your email and we'll send you a password reset link.</div>
    </div>
    <div class="fp-body">
      <div class="fp-field">
        <label class="fp-label">Email Address</label>
        <div class="fp-input-row" id="fp-email-row">
          <i class="bi bi-envelope-fill"></i>
          <input type="email" id="fp-email" placeholder="you@dlsud.edu.ph"/>
        </div>
      </div>
    </div>
    <div class="fp-foot">
      <button class="fp-btn-primary" id="fp-primary" onclick="sendResetLink()">
            <i class="bi bi-send"></i>
            <span>Send Reset Link</span>
        </button>
      <button class="fp-btn-ghost" onclick="closeFP()">
        <i class="bi bi-arrow-left"></i>
        <span>Back to Login</span>
      </button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
var fpStep = 1, fpEmail = '', fpResendInterval = null;

function openFP() {
  fpReset();
  document.getElementById('fp-overlay').classList.add('open');
  setTimeout(function() { document.getElementById('fp-email').focus(); }, 380);
}

function closeFP() {
  document.getElementById('fp-overlay').classList.remove('open');
  clearInterval(fpResendInterval);
}

function fpOverlayClick(e) {
  if (e.target === document.getElementById('fp-overlay')) closeFP();
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeFP();
  if (e.key === 'Enter' && document.getElementById('fp-overlay').classList.contains('open') && fpStep !== 2) fpHandlePrimary();
});

function fpGoTo(n) {
  fpStep = n;
  for (var i = 1; i <= 4; i++) document.getElementById('fpp-' + i).classList.toggle('active', i === n);
  document.getElementById('fp-steps').style.display = n === 4 ? 'none' : 'flex';
  for (var i = 1; i <= 3; i++) {
    var s = document.getElementById('fps-' + i);
    s.classList.remove('active', 'done');
    if (i < n) s.classList.add('done');
    if (i === n) s.classList.add('active');
    document.getElementById('fpsc-' + i).innerHTML = i < n ? '<i class="bi bi-check-lg" style="font-size:.65rem"></i>' : i;
  }
  for (var i = 1; i <= 2; i++) document.getElementById('fpsl-' + i).classList.toggle('done', i < n);
  var titles = {
    1: ['Forgot Password?', "No worries! We'll send a verification code to your registered email."],
    2: ['Check Your Email', 'Enter the 6-digit code we sent to verify your identity.'],
    3: ['New Password', 'Almost done! Set a strong new password for your account.'],
    4: ['All Done!', 'Your password has been reset successfully.']
  };
  document.getElementById('fp-title').textContent = titles[n][0];
  document.getElementById('fp-sub').textContent = titles[n][1];
  var lbls = { 1: 'Send Verification Code', 2: 'Verify Code', 3: 'Reset Password', 4: 'Back to Login' };
  var icos = { 1: 'arrow-right-circle-fill', 2: 'shield-check', 3: 'key-fill', 4: 'box-arrow-in-right' };
  document.getElementById('fp-btn-lbl').textContent = lbls[n];
  document.getElementById('fp-btn-icon').className = 'bi bi-' + icos[n];
  document.getElementById('fp-ghost-lbl').textContent = (n === 1 || n === 4) ? 'Back to Login' : 'Go Back';
}

function fpHandlePrimary() {
  if (fpStep === 1) fpSubmitEmail();
  else if (fpStep === 2) fpVerifyOTP();
  else if (fpStep === 3) fpSubmitPassword();
  else closeFP();
}

function fpHandleGhost() {
  if (fpStep === 1 || fpStep === 4) { closeFP(); return; }
  clearInterval(fpResendInterval);
  fpGoTo(fpStep - 1);
}

function fpSubmitEmail() {
  var email = document.getElementById('fp-email').value.trim();
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    fpShowErr(1, 'Please enter a valid email address.');
    fpShake('fp-email-row');
    return;
  }
  fpEmail = email;
  fpSetLoading(true, 'Sending code…');
  setTimeout(function() {
    fpSetLoading(false);
    document.getElementById('fp-sent-to').textContent = fpMaskEmail(email);
    fpGoTo(2);
    fpStartResend(60);
    setTimeout(function() { document.getElementById('fpotp-0').focus(); }, 300);
  }, 1800);
}

function fpMaskEmail(e) {
  var p = e.split('@');
  return p[0].slice(0, 2) + '•••' + p[0].slice(-1) + '@' + p[1];
}

function fpOtpIn(i) {
  var b = document.getElementById('fpotp-' + i);
  b.value = b.value.replace(/\D/g, '');
  b.classList.toggle('filled', b.value.length > 0);
  fpClearErr(2);
  if (b.value && i < 5) document.getElementById('fpotp-' + (i + 1)).focus();
}

function fpOtpKey(i, e) {
  if (e.key === 'Backspace' && !document.getElementById('fpotp-' + i).value && i > 0)
    document.getElementById('fpotp-' + (i - 1)).focus();
  if (e.key === 'Enter') fpVerifyOTP();
}

function fpVerifyOTP() {
  var code = [0, 1, 2, 3, 4, 5].map(function(i) { return document.getElementById('fpotp-' + i).value; }).join('');
  if (code.length < 6) { fpShowErr(2, 'Please enter all 6 digits.'); return; }
  fpSetLoading(true, 'Verifying…');
  setTimeout(function() {
    fpSetLoading(false);
    clearInterval(fpResendInterval);
    fpGoTo(3);
    setTimeout(function() { document.getElementById('fp-pw-new').focus(); }, 300);
  }, 1400);
}

function fpStartResend(sec) {
  clearInterval(fpResendInterval);
  var r = sec;
  var btn = document.getElementById('fp-resend-btn'), tmr = document.getElementById('fp-resend-timer');
  btn.style.display = 'none';
  tmr.style.display = 'inline';
  tmr.textContent = 'Resend in ' + r + 's';
  fpResendInterval = setInterval(function() {
    r--;
    tmr.textContent = 'Resend in ' + r + 's';
    if (r <= 0) {
      clearInterval(fpResendInterval);
      btn.style.display = 'inline';
      tmr.style.display = 'none';
    }
  }, 1000);
}

function fpResend() {
  var btn = document.getElementById('fp-resend-btn');
  btn.textContent = 'Sending…';
  btn.disabled = true;
  setTimeout(function() {
    btn.textContent = 'Resend Code';
    btn.disabled = false;
    fpStartResend(60);
  }, 1200);
}

function fpCheckStr() {
  var pw = document.getElementById('fp-pw-new').value, s = 0;
  if (pw.length >= 8) s++;
  if (/[A-Z]/.test(pw)) s++;
  if (/[0-9]/.test(pw)) s++;
  if (/[^A-Za-z0-9]/.test(pw)) s++;
  var cls = ['', 'weak', 'fair', 'good', 'strong'],
      txt = ['Enter a password', 'Weak — too short', 'Fair — add numbers', 'Good — add symbols', 'Strong ✓'],
      cols = ['', '#e74c3c', 'var(--gold)', '#27ae60', 'var(--mint)'];
  for (var i = 1; i <= 4; i++) document.getElementById('fps-b' + i).className = 'fp-sb ' + (i <= s ? cls[s] : '');
  var lbl = document.getElementById('fp-str-lbl');
  lbl.textContent = txt[s];
  lbl.style.color = cols[s] || 'var(--text-lt)';
  fpClearErr(3);
}

function fpTogglePw(inp, ico) {
  var el = document.getElementById(inp);
  el.type = el.type === 'password' ? 'text' : 'password';
  document.getElementById(ico).className = el.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

function fpSubmitPassword() {
  var pw = document.getElementById('fp-pw-new').value,
      conf = document.getElementById('fp-pw-conf').value;
  if (!pw || pw.length < 8) { fpShowErr(3, 'Password must be at least 8 characters.'); return; }
  if (pw !== conf) { fpShowErr(3, 'Passwords do not match. Please try again.'); fpShake('fp-pw-conf'); return; }
  fpSetLoading(true, 'Resetting password…');
  setTimeout(function() { fpSetLoading(false); fpGoTo(4); }, 2000);
}

function fpSetLoading(on, label) {
  var btn = document.getElementById('fp-primary'),
      spin = document.getElementById('fp-spin'),
      ico = document.getElementById('fp-btn-icon'),
      lbl = document.getElementById('fp-btn-lbl');
  btn.classList.toggle('loading', on);
  spin.style.display = on ? 'block' : 'none';
  ico.style.display = on ? 'none' : 'block';
  if (on && label) lbl.textContent = label;
}

function fpShowErr(p, msg) {
  document.getElementById('fpe-' + p + '-txt').textContent = msg;
  document.getElementById('fpe-' + p).classList.add('show');
}

function fpClearErr(p) {
  var el = document.getElementById('fpe-' + p);
  if (el) el.classList.remove('show');
}

function fpShake(id) {
  var el = document.getElementById(id);
  if (!el) return;
  var t = el.closest('.fp-input-row') || el;
  t.classList.add('fp-shake');
  setTimeout(function() { t.classList.remove('fp-shake'); }, 350);
}

function fpReset() {
  fpStep = 1;
  fpEmail = '';
  clearInterval(fpResendInterval);
  document.getElementById('fp-email').value = '';
  for (var i = 0; i < 6; i++) {
    var b = document.getElementById('fpotp-' + i);
    b.value = '';
    b.classList.remove('filled');
  }
  ['fp-pw-new', 'fp-pw-conf'].forEach(function(id) {
    var el = document.getElementById(id);
    el.value = '';
    el.type = 'password';
  });
  document.getElementById('fp-eye-new').className = 'bi bi-eye';
  document.getElementById('fp-eye-conf').className = 'bi bi-eye';
  for (var i = 1; i <= 4; i++) document.getElementById('fps-b' + i).className = 'fp-sb';
  document.getElementById('fp-str-lbl').textContent = 'Enter a password';
  document.getElementById('fp-str-lbl').style.color = '';
  [1, 2, 3].forEach(fpClearErr);
  fpGoTo(1);
}

// User type and form switching
var currentType = 'student';
var currentForm = 'login';

function setUserType(type) {
  currentType = type;
  document.getElementById('btn-student').classList.toggle('active', type === 'student');
  document.getElementById('btn-company').classList.toggle('active', type === 'company');
  showForm(type, currentForm);
}

function showForm(type, form) {
  currentType = type;
  currentForm = form;
  document.querySelectorAll('.auth-panel').forEach(function(p) { p.classList.remove('active'); });
  var target = document.getElementById('page-' + type + '-' + form);
  if (target) target.classList.add('active');
  document.getElementById('btn-student').classList.toggle('active', type === 'student');
  document.getElementById('btn-company').classList.toggle('active', type === 'company');
  var card = document.querySelector('.auth-right');
  card.style.animation = 'none';
  void card.offsetHeight;
  card.style.animation = '';
}

// Password toggle
function togglePass(inputId, btn) {
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

// Check URL parameter for admin login
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('admin') === '1') {
  // Hide student/company toggle
  document.querySelector('.type-toggle').style.display = 'none';
  // Show admin login panel
  document.querySelectorAll('.auth-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('page-admin-login').classList.add('active');
}

// Simple Forgot Password Functions
function openFP() {
  document.getElementById('fp-overlay').classList.add('open');
  document.getElementById('fp-email').value = '';
}

function closeFP() {
  document.getElementById('fp-overlay').classList.remove('open');
}

function fpOverlayClick(e) {
  if (e.target === document.getElementById('fp-overlay')) closeFP();
}

// Forgot Password AJAX - DEBUG VERSION
function sendResetLink() {
    console.log("sendResetLink called");
    
    const email = document.getElementById('fp-email').value;
    console.log("Email entered:", email);
    
    if (!email) {
        alert('Please enter your email address');
        return false;
    }
    
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert('Please enter a valid email address');
        return false;
    }
    
    const btn = document.querySelector('#fp-primary');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<span>Sending...</span>';
    btn.disabled = true;
    
    const url = '/forgot_password.php';
    console.log("Sending request to:", url);
    
    fetch(url, {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'email=' + encodeURIComponent(email)
    })
    .then(response => {
        console.log("Response status:", response.status);
        return response.json();
    })
    .then(data => {
        console.log("Response data:", data);
        alert(data.message);
        if (data.success) {
            closeFP();
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert('Error: ' + error.message);
    })
    .finally(() => {
        btn.innerHTML = originalHTML;
        btn.disabled = false;
    });
    
    return false;
}
</script>
</body>
</html>