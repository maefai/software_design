<?php
// student/profile.php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';
require_once '../includes/upload_functions.php';

requireStudent();

$user_id = $_SESSION['user_id'];
$student = getStudentData($conn, $user_id);

$message = '';
$error = '';

// Update profile
if (isset($_POST['update_profile'])) {
    $fullname = $_POST['fullname'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $bio = $_POST['bio'] ?? '';
    
    try {
        $sql = "UPDATE students SET fullname = ?, contact = ?, bio = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$fullname, $contact, $bio, $user_id]);
        
        $message = "Profile updated!";
        $student = getStudentData($conn, $user_id);
    } catch (PDOException $e) {
        $error = "Update failed: " . $e->getMessage();
    }
}

// Upload resume
if (isset($_POST['upload_resume']) && isset($_FILES['resume']) && $_FILES['resume']['error'] == UPLOAD_ERR_OK) {
    $upload = uploadFile($_FILES['resume'], 'resumes/', ['pdf', 'doc', 'docx']);
    if ($upload['success']) {
        try {
            $checkStmt = $conn->prepare("SELECT id FROM documents WHERE student_id = ? AND doc_type = 'resume'");
            $checkStmt->execute([$student['id']]);
            
            if ($checkStmt->fetch()) {
                $sql = "UPDATE documents SET file_name = ?, file_path = ?, file_size = ?, uploaded_at = NOW() WHERE student_id = ? AND doc_type = 'resume'";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$upload['filename'], $upload['filepath'], $upload['size'], $student['id']]);
            } else {
                $sql = "INSERT INTO documents (student_id, doc_type, doc_name, file_name, file_path, file_size, uploaded_at) VALUES (?, 'resume', ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$student['id'], $upload['filename'], $upload['filename'], $upload['filepath'], $upload['size']]);
            }
            $message = "Resume uploaded!";
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else { 
        $error = $upload['error']; 
    }
}

// Upload certificate
if (isset($_POST['upload_certificate'])) {
    $name = $_POST['cert_name'] ?? '';
    $issuer = $_POST['issuer'] ?? '';
    $date = $_POST['issue_date'] ?? '';
    $cred_id = $_POST['credential_id'] ?? '';
    
    if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] == UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['certificate'], 'certificates/', ['pdf', 'jpg', 'jpeg', 'png']);
        if ($upload['success']) {
            try {
                $sql = "INSERT INTO certificates (student_id, certificate_name, issuer, issue_date, credential_id, file_path, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$student['id'], $name, $issuer, $date, $cred_id, $upload['filepath']]);
                $message = "Certificate uploaded!";
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else { 
            $error = $upload['error']; 
        }
    } else { 
        $error = "Please select a file."; 
    }
}

// Get documents
$docs = [];
try {
    $sql = "SELECT * FROM documents WHERE student_id = ? ORDER BY uploaded_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$student['id']]);
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Docs fetch error: " . $e->getMessage());
}

// Get certificates
$certs = [];
try {
    $sql = "SELECT * FROM certificates WHERE student_id = ? ORDER BY uploaded_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$student['id']]);
    $certs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Certs fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - GreenBridge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Playfair+Display:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --forest: #1a3a24;
            --forest-mid: #2d5a3d;
            --forest-lt: #3d7a52;
            --sage: #eef3ec;
            --sage-dk: #cfdecb;
            --mint: #4caf78;
            --mint-light: #e0f5ea;
            --white: #ffffff;
            --gray-light: #f9fbf8;
            --gray-border: #e2e8e0;
            --text-dark: #1e2a23;
            --text-muted: #5a6e5f;
            --shadow-sm: 0 2px 8px rgba(26,58,36,0.06);
            --shadow-md: 0 8px 20px rgba(26,58,36,0.08);
            --radius: 12px;
            --radius-lg: 20px;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--sage);
            color: var(--text-dark);
            line-height: 1.5;
        }
        
        /* Typography */
        .serif, h1, h2, h3, .playfair {
            font-family: 'Playfair Display', serif;
        }
        
        /* Navbar Refined */
        .navbar {
            background: var(--forest) !important;
            padding: 0.7rem 2rem;
            height: 64px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .navbar-brand { 
            font-family: 'Playfair Display', serif; 
            font-weight: 800; 
            color: white !important;
            font-size: 1.4rem;
            letter-spacing: -0.3px;
        }
        .navbar-brand i {
            font-size: 1.3rem;
            margin-right: 6px;
        }
        
        .user-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 40px;
            padding: 5px 14px 5px 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .user-chip:hover {
            background: rgba(255,255,255,0.18);
        }
        .user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: var(--mint);
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--forest);
        }
        
        /* Sub Navigation Clean */
        .subnav {
            background: var(--forest-mid);
            padding: 0 28px;
            display: flex;
            gap: 4px;
            overflow-x: auto;
            scrollbar-width: thin;
        }
        .subnav-item {
            padding: 12px 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: rgba(255,255,255,0.65);
            cursor: pointer;
            border-bottom: 2.5px solid transparent;
            white-space: nowrap;
            transition: all 0.2s;
            letter-spacing: 0.2px;
        }
        .subnav-item:hover { color: white; background: rgba(255,255,255,0.05); }
        .subnav-item.active { color: white; border-bottom-color: var(--mint); background: transparent; }
        
        /* Main Container */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .page-header {
            margin-bottom: 1.8rem;
        }
        .page-title { 
            font-size: 1.8rem; 
            font-weight: 800; 
            font-family: 'Playfair Display', serif;
            margin-bottom: 0.3rem;
            color: var(--forest);
            letter-spacing: -0.3px;
        }
        .page-subtitle { 
            font-size: 0.9rem; 
            color: var(--text-muted);
            border-left: 3px solid var(--mint);
            padding-left: 12px;
            margin-top: 4px;
        }
        
        /* Profile Header */
        .profile-header {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 2rem;
            text-align: center;
            margin-bottom: 2rem;
            border: 1px solid var(--gray-border);
            box-shadow: var(--shadow-sm);
            transition: box-shadow 0.2s;
        }
        .profile-header:hover {
            box-shadow: var(--shadow-md);
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--forest-lt) 0%, var(--mint) 100%);
            display: grid;
            place-items: center;
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin: 0 auto 1rem;
            box-shadow: 0 4px 12px rgba(26,58,36,0.15);
        }
        
        /* Cards */
        .card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-border);
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: box-shadow 0.2s;
        }
        .card:hover {
            box-shadow: var(--shadow-md);
        }
        .card-header {
            background: var(--gray-light);
            padding: 1rem 1.5rem;
            font-weight: 700;
            border-bottom: 1px solid var(--gray-border);
            font-size: 0.9rem;
            color: var(--forest);
        }
        .card-header i {
            margin-right: 8px;
            color: var(--mint);
        }
        .card-body {
            padding: 1.5rem;
        }
        
        /* Form Styles */
        .form-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--forest);
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .form-control {
            border: 1px solid var(--gray-border);
            border-radius: 10px;
            padding: 0.65rem 1rem;
            font-size: 0.85rem;
            width: 100%;
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: var(--forest-lt);
            box-shadow: 0 0 0 3px rgba(61,122,82,0.1);
            outline: none;
        }
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }
        
        .btn-primary {
            background: var(--forest);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background: var(--forest-mid);
            transform: translateY(-1px);
        }
        .btn-outline-secondary {
            background: transparent;
            border: 1px solid var(--gray-border);
            padding: 0.25rem 0.8rem;
            border-radius: 30px;
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-outline-secondary:hover {
            background: var(--sage);
            border-color: var(--mint);
        }
        
        /* Document & Certificate Items */
        .doc-item, .cert-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem;
            border-bottom: 1px solid var(--gray-border);
            transition: background 0.2s;
        }
        .doc-item:hover, .cert-item:hover {
            background: var(--gray-light);
            border-radius: 12px;
        }
        .doc-item:last-child, .cert-item:last-child {
            border-bottom: none;
        }
        .doc-icon {
            width: 44px;
            height: 44px;
            background: var(--sage);
            border-radius: 10px;
            display: grid;
            place-items: center;
            font-size: 1.2rem;
            color: var(--forest);
        }
        
        /* Alerts */
        .alert-success {
            background: #e6f7ef;
            color: #1e6f3f;
            padding: 0.9rem 1.2rem;
            border-radius: 14px;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--mint);
            font-weight: 500;
        }
        .alert-danger {
            background: #fee9e7;
            color: #b13e3e;
            padding: 0.9rem 1.2rem;
            border-radius: 14px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #e07c7c;
            font-weight: 500;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
        }
        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
            opacity: 0.4;
        }
        
        @media (max-width: 768px) {
            .main-container { padding: 1rem; }
            .page-title { font-size: 1.5rem; }
            .subnav { padding: 0 12px; }
            .card-body { padding: 1rem; }
            .doc-item, .cert-item { flex-wrap: wrap; }
        }
        
        /* Animation */
        .fade-in {
            animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px);}
            to { opacity: 1; transform: translateY(0);}
        }
        .card, .profile-header {
            animation: fadeIn 0.3s ease;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-tree-fill"></i> GREEN BRIDGE
        </a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <div class="dropdown">
                <div class="user-chip dropdown-toggle" data-bs-toggle="dropdown">
                    <div class="user-avatar"><?php echo strtoupper(substr($student['fullname'], 0, 2)); ?></div>
                    <div>
                        <div style="font-size:13px; font-weight:600; color:white"><?php echo htmlspecialchars(explode(' ', $student['fullname'])[0]); ?></div>
                        <div style="font-size:10px; color:rgba(255,255,255,0.6)">Student</div>
                    </div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i> My Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="subnav">
    <div class="subnav-item" onclick="location.href='dashboard.php'">Home</div>
    <div class="subnav-item" onclick="location.href='community.php'">Community</div>
    <div class="subnav-item" onclick="location.href='opportunities.php'">Opportunities</div>
    <div class="subnav-item" onclick="location.href='applications.php'">Applications</div>
    <div class="subnav-item" onclick="location.href='chat.php'">Chat</div>
    <div class="subnav-item" onclick="location.href='performance.php'">Performance</div>
    <div class="subnav-item" onclick="location.href='dtr.php'">DTR</div>
    <div class="subnav-item" onclick="location.href='logbook.php'">Logbook</div>
    <div class="subnav-item active">Profile</div>
</div>

<div class="main-container">
    <div class="page-header">
        <div class="page-title">My Profile</div>
        <div class="page-subtitle">Manage your personal information, documents, and certificates</div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert-success">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar"><?php echo strtoupper(substr($student['fullname'], 0, 2)); ?></div>
        <h4 class="mb-1"><?php echo htmlspecialchars($student['fullname']); ?></h4>
        <p class="text-muted small mb-2">
            <?php echo htmlspecialchars($student['student_id']); ?> • 
            <?php echo htmlspecialchars($student['course']); ?> • 
            Year <?php echo $student['year_level']; ?>
        </p>
        <p class="mb-0"><?php echo htmlspecialchars($student['university']); ?></p>
    </div>
    
    <div class="row g-4">
        <!-- Left Column -->
        <div class="col-lg-6">
            <!-- Personal Information Card -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-person"></i> Personal Information
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($student['fullname']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact" class="form-control" value="<?php echo htmlspecialchars($student['contact'] ?? ''); ?>" placeholder="+63 XXX XXX XXXX">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bio / About Me</label>
                            <textarea name="bio" class="form-control" rows="3" placeholder="Tell us about yourself, your skills, and career aspirations..."><?php echo htmlspecialchars($student['bio'] ?? ''); ?></textarea>
                        </div>
                        <div class="text-end">
                            <button type="submit" name="update_profile" class="btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Resume Upload Card -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-file-earmark-person"></i> Resume / CV
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <input type="file" name="resume" class="form-control" accept=".pdf,.doc,.docx">
                            <small class="text-muted">Supported formats: PDF, DOC, DOCX (max 5MB)</small>
                        </div>
                        <div class="text-end">
                            <button type="submit" name="upload_resume" class="btn-primary">Upload Resume</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="col-lg-6">
            <!-- Upload Certificate Card -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-patch-check"></i> Upload Certificate
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-2">
                            <input type="text" name="cert_name" class="form-control" placeholder="Certificate Name" required>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <input type="text" name="issuer" class="form-control" placeholder="Issuer (e.g., DLSU, Coursera)" required>
                            </div>
                            <div class="col-md-6">
                                <input type="month" name="issue_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-2">
                            <input type="text" name="credential_id" class="form-control" placeholder="Credential ID (optional)">
                        </div>
                        <div class="mb-3">
                            <input type="file" name="certificate" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                            <small class="text-muted">Supported formats: PDF, JPG, PNG (max 5MB)</small>
                        </div>
                        <div class="text-end">
                            <button type="submit" name="upload_certificate" class="btn-primary">Upload Certificate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- My Documents Card -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-folder"></i> My Documents
        </div>
        <div class="card-body">
            <?php if (empty($docs)): ?>
                <div class="empty-state">
                    <i class="bi bi-folder2-open"></i>
                    <p class="mt-2">No documents uploaded yet.</p>
                    <small class="text-muted">Upload your resume and other documents to share with employers.</small>
                </div>
            <?php else: ?>
                <?php foreach ($docs as $doc): ?>
                    <div class="doc-item">
                        <div class="doc-icon">
                            <i class="bi bi-file-pdf"></i>
                        </div>
                        <div style="flex: 1">
                            <div><strong><?php echo htmlspecialchars($doc['doc_name']); ?></strong></div>
                            <div class="text-muted small">
                                <?php echo ucfirst($doc['doc_type']); ?> • 
                                <?php echo round($doc['file_size'] / 1024, 2); ?> KB
                            </div>
                        </div>
                        <a href="<?php echo SITE_URL . htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn-outline-secondary btn-sm">View</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- My Certificates Card -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-award"></i> My Certificates
        </div>
        <div class="card-body">
            <?php if (empty($certs)): ?>
                <div class="empty-state">
                    <i class="bi bi-award"></i>
                    <p class="mt-2">No certificates uploaded yet.</p>
                    <small class="text-muted">Add certificates to showcase your skills and achievements.</small>
                </div>
            <?php else: ?>
                <?php foreach ($certs as $cert): ?>
                    <div class="cert-item">
                        <div class="doc-icon">
                            <i class="bi bi-trophy"></i>
                        </div>
                        <div style="flex: 1">
                            <div><strong><?php echo htmlspecialchars($cert['certificate_name']); ?></strong></div>
                            <div class="text-muted small">
                                <?php echo htmlspecialchars($cert['issuer']); ?> • 
                                <?php echo $cert['issue_date'] instanceof DateTime ? $cert['issue_date']->format('M Y') : date('M Y', strtotime($cert['issue_date'])); ?>
                                <?php if ($cert['credential_id']): ?>
                                    • ID: <?php echo htmlspecialchars($cert['credential_id']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="<?php echo SITE_URL . htmlspecialchars($cert['file_path']); ?>" target="_blank" class="btn-outline-secondary btn-sm">View</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>