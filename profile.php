<?php
// company/profile.php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';
require_once '../includes/upload_functions.php';

requireCompany();

$user_id = $_SESSION['user_id'];
$company = getCompanyData($conn, $user_id);

$message = '';
$error = '';

try {
    if (isset($_POST['update_profile'])) {
        $company_name = $_POST['company_name'] ?? '';
        $industry = $_POST['industry'] ?? '';
        $website = $_POST['website'] ?? '';
        $contact_person = $_POST['contact_person'] ?? '';
        $contact_position = $_POST['contact_position'] ?? '';
        $contact_email = $_POST['contact_email'] ?? '';
        $contact_phone = $_POST['contact_phone'] ?? '';
        $company_address = $_POST['company_address'] ?? '';
        $company_description = $_POST['company_description'] ?? '';
        
        $sql = "UPDATE companies SET 
                company_name = ?, industry = ?, website = ?, contact_person = ?, 
                contact_position = ?, contact_email = ?, contact_phone = ?, 
                company_address = ?, company_description = ?
                WHERE user_id = ?";
        $params = [$company_name, $industry, $website, $contact_person, 
                   $contact_position, $contact_email, $contact_phone, 
                   $company_address, $company_description, $user_id];
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $message = "Profile updated successfully!";
        $company = getCompanyData($conn, $user_id);
    }

    if (isset($_POST['upload_logo'])) {
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES['logo'], 'company/', ALLOWED_IMAGES);
            if ($upload['success']) {
                $stmt = $conn->prepare("UPDATE companies SET company_logo = ? WHERE user_id = ?");
                $stmt->execute([$upload['filepath'], $user_id]);
                $message = "Logo uploaded successfully!";
                $company = getCompanyData($conn, $user_id);
            } else {
                $error = $upload['error'];
            }
        } else {
            $error = "Please select a file to upload.";
        }
    }

    $stats = [];
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM internships WHERE company_id = ?");
    $stmt->execute([$company['id']]);
    $stats['total_internships'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM internships WHERE company_id = ? AND status = 'open'");
    $stmt->execute([$company['id']]);
    $stats['active_internships'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM applications a JOIN internships i ON a.internship_id = i.id WHERE i.company_id = ?");
    $stmt->execute([$company['id']]);
    $stats['total_applications'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM applications a JOIN internships i ON a.internship_id = i.id WHERE i.company_id = ? AND a.status = 'accepted'");
    $stmt->execute([$company['id']]);
    $stats['accepted_applications'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $stmt = $conn->prepare("SELECT SUM(hours) as total FROM dtr_logs d JOIN internships i ON d.internship_id = i.id WHERE i.company_id = ?");
    $stmt->execute([$company['id']]);
    $stats['total_hours'] = round($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0, 1);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Profile - GreenBridge</title>
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
        .nav-link { color: rgba(255,255,255,0.75) !important; font-size: 0.85rem; font-weight: 500; transition: all 0.2s; }
        .nav-link:hover { color: white !important; opacity: 1; }
        
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
        .company-logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--sage) 0%, var(--sage-dk) 100%);
            display: grid;
            place-items: center;
            font-size: 2rem;
            font-weight: 700;
            margin: 0 auto 1rem;
            overflow: hidden;
            color: var(--forest);
        }
        .company-logo img { width: 100%; height: 100%; object-fit: cover; }
        .company-name {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 0.25rem;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.2rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 1.25rem;
            text-align: center;
            border: 1px solid var(--gray-border);
            transition: all 0.25s ease;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--mint);
        }
        .stat-value { 
            font-size: 1.8rem; 
            font-weight: 800; 
            color: var(--forest);
            font-family: 'Playfair Display', serif;
        }
        .stat-label { 
            font-size: 0.7rem; 
            font-weight: 600; 
            text-transform: uppercase; 
            color: var(--text-muted);
            letter-spacing: 0.5px;
            margin-top: 4px;
        }
        
        /* Form Cards */
        .form-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-border);
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: box-shadow 0.2s;
        }
        .form-card:hover {
            box-shadow: var(--shadow-md);
        }
        .form-header {
            background: var(--gray-light);
            padding: 1rem 1.5rem;
            font-weight: 700;
            border-bottom: 1px solid var(--gray-border);
            font-size: 0.9rem;
            color: var(--forest);
        }
        .form-header i {
            margin-right: 8px;
            color: var(--mint);
        }
        .form-body { padding: 1.5rem; }
        
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
        textarea.form-control { resize: vertical; min-height: 80px; }
        
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
        .btn-primary:hover { background: var(--forest-mid); transform: translateY(-1px); }
        .btn-outline {
            background: transparent;
            border: 1px solid var(--gray-border);
            padding: 0.6rem 1.2rem;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--text-muted);
        }
        .btn-outline:hover {
            background: var(--sage);
            border-color: var(--mint);
        }
        
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
        
        .info-row {
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--gray-border);
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 0.2rem;
        }
        .info-value {
            font-weight: 600;
            color: var(--forest);
        }
        
        .badge-status {
            padding: 0.25rem 0.8rem;
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-active {
            background: #e6f7ef;
            color: #1e6f3f;
        }
        .badge-pending {
            background: #fef3cd;
            color: #856404;
        }
        
        @media (max-width: 768px) {
            .main-container { padding: 1rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 0.8rem; }
            .page-title { font-size: 1.5rem; }
            .subnav { padding: 0 12px; }
            .form-body { padding: 1rem; }
        }
        
        /* Animation */
        .fade-in {
            animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px);}
            to { opacity: 1; transform: translateY(0);}
        }
        .stat-card, .form-card {
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
                <div class="user-chip dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar"><?php echo strtoupper(substr($company['company_name'], 0, 2)); ?></div>
                    <div>
                        <div style="font-size:13px; font-weight:600; color:white"><?php echo htmlspecialchars($company['company_name']); ?></div>
                        <div style="font-size:10px; color:rgba(255,255,255,0.6)">Company Account</div>
                    </div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li><a class="dropdown-item" href="profile.php"><i class="bi bi-building me-2"></i> Company Profile</a></li>
                    <li><a class="dropdown-item" href="dashboard.php"><i class="bi bi-grid-1x2 me-2"></i> Dashboard</a></li>
                    <li><a class="dropdown-item" href="post_internship.php"><i class="bi bi-plus-circle me-2"></i> Post Internship</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="subnav">
    <div class="subnav-item" onclick="location.href='dashboard.php'">Dashboard</div>
    <div class="subnav-item" onclick="location.href='post_internship.php'">Post Internship</div>
    <div class="subnav-item" onclick="location.href='manage_internships.php'">Manage Internships</div>
    <div class="subnav-item" onclick="location.href='manage_applications.php'">Applications</div>
    <div class="subnav-item" onclick="location.href='chat.php'">Chat</div>
    <div class="subnav-item" onclick="location.href='scout_students.php'">Scout Students</div>
    <div class="subnav-item" onclick="location.href='manage_interns.php'">Manage Interns</div>
    <div class="subnav-item active" onclick="location.href='profile.php'">Profile</div>
</div>

<div class="main-container">
    <?php if ($message): ?>
        <div class="alert-success">
            <i class="bi bi-check-circle-fill me-2"></i> 
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> 
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <div class="page-header">
        <div class="page-title">Company Profile</div>
        <div class="page-subtitle">Manage your company information and account settings</div>
    </div>
    
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="company-logo">
            <?php if ($company['company_logo']): ?>
                <img src="<?php echo htmlspecialchars($company['company_logo']); ?>" alt="Company logo">
            <?php else: ?>
                <i class="bi bi-building" style="font-size: 2.5rem;"></i>
            <?php endif; ?>
        </div>
        <div class="company-name"><?php echo htmlspecialchars($company['company_name']); ?></div>
        <p class="text-muted small"><?php echo htmlspecialchars($company['industry']); ?> • Member since <?php 
            if (isset($company['created_at']) && !empty($company['created_at'])) {
                if ($company['created_at'] instanceof DateTime) {
                    echo $company['created_at']->format('M Y');
                } else {
                    echo date('M Y', strtotime($company['created_at']));
                }
            } else {
                echo '2024';
            }
        ?></p>
        <form method="POST" enctype="multipart/form-data" style="display:inline-block;">
            <input type="file" name="logo" id="logoInput" style="display:none;" accept="image/*" onchange="this.form.submit()">
            <button type="button" class="btn-outline btn-sm" onclick="document.getElementById('logoInput').click()">
                <i class="bi bi-camera"></i> Change Logo
            </button>
            <input type="hidden" name="upload_logo" value="1">
        </form>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card" onclick="location.href='manage_internships.php'">
            <div class="stat-value"><?php echo $stats['total_internships']; ?></div>
            <div class="stat-label">Total Postings</div>
        </div>
        <div class="stat-card" onclick="location.href='manage_internships.php?filter=open'">
            <div class="stat-value"><?php echo $stats['active_internships']; ?></div>
            <div class="stat-label">Active Openings</div>
        </div>
        <div class="stat-card" onclick="location.href='manage_applications.php'">
            <div class="stat-value"><?php echo $stats['total_applications']; ?></div>
            <div class="stat-label">Applications Received</div>
        </div>
        <div class="stat-card" onclick="location.href='manage_interns.php'">
            <div class="stat-value"><?php echo $stats['total_hours']; ?></div>
            <div class="stat-label">Total Hours Logged</div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Company Information -->
        <div class="col-lg-7">
            <div class="form-card">
                <div class="form-header">
                    <i class="bi bi-building"></i> Company Information
                </div>
                <div class="form-body">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($company['company_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Industry</label>
                                <input type="text" name="industry" class="form-control" value="<?php echo htmlspecialchars($company['industry']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Website</label>
                                <input type="url" name="website" class="form-control" value="<?php echo htmlspecialchars($company['website'] ?? ''); ?>" placeholder="https://example.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Company Address</label>
                                <textarea name="company_address" class="form-control" rows="2" placeholder="Street address, city, postal code"><?php echo htmlspecialchars($company['company_address'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Company Description</label>
                                <textarea name="company_description" class="form-control" rows="3" placeholder="Describe your company, mission, and what you offer to interns"><?php echo htmlspecialchars($company['company_description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="mt-4 text-end">
                            <button type="submit" name="update_profile" class="btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Contact & Account Information -->
        <div class="col-lg-5">
            <div class="form-card">
                <div class="form-header">
                    <i class="bi bi-envelope"></i> Contact Information
                </div>
                <div class="form-body">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Contact Person</label>
                                <input type="text" name="contact_person" class="form-control" value="<?php echo htmlspecialchars($company['contact_person'] ?? ''); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Position / Title</label>
                                <input type="text" name="contact_position" class="form-control" value="<?php echo htmlspecialchars($company['contact_position'] ?? ''); ?>" placeholder="e.g., HR Manager">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Contact Email</label>
                                <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($company['contact_email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Contact Phone</label>
                                <input type="text" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($company['contact_phone'] ?? ''); ?>" placeholder="+63 XXX XXX XXXX">
                            </div>
                        </div>
                        <div class="mt-4 text-end">
                            <button type="submit" name="update_profile" class="btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Account Information -->
            <div class="form-card">
                <div class="form-header">
                    <i class="bi bi-shield-lock"></i> Account Information
                </div>
                <div class="form-body">
                    <div class="info-row">
                        <div class="info-label">Login Email</div>
                        <div class="info-value"><?php echo isset($company['email']) ? htmlspecialchars($company['email']) : 'Not available'; ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Account Status</div>
                        <div class="info-value">
                            <span class="badge-status <?php echo (isset($company['user_status']) && $company['user_status'] == 'active') ? 'badge-active' : 'badge-pending'; ?>">
                                <?php echo isset($company['user_status']) ? ucfirst($company['user_status']) : 'Pending'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Member Since</div>
                        <div class="info-value">
                            <?php 
                            if (isset($company['created_at']) && !empty($company['created_at'])) {
                                if ($company['created_at'] instanceof DateTime) {
                                    echo $company['created_at']->format('F d, Y');
                                } else {
                                    echo date('F d, Y', strtotime($company['created_at']));
                                }
                            } else {
                                echo 'Not available';
                            }
                            ?>
                        </div>
                    </div>
                    <hr class="my-3" style="border-color: var(--gray-border);">
                    <button class="btn-outline w-100" onclick="alert('Password change feature will be available soon.')">
                        <i class="bi bi-key"></i> Change Password
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>