<?php
// company/post_internship.php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';
require_once '../includes/upload_functions.php';

// Check if company is logged in
requireCompany();

$user_id = $_SESSION['user_id'];
$company = getCompanyData($conn, $user_id);

$message = '';
$error = '';

$edit_id = $_GET['edit'] ?? 0;
$is_edit = false;
$internship_data = null;

try {
    if ($edit_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM internships WHERE id = ? AND company_id = ?");
        $stmt->execute([$edit_id, $company['id']]);
        $internship_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($internship_data) {
            $is_edit = true;
        }
    }

    if (isset($_POST['post_internship'])) {
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $requirements = $_POST['requirements'] ?? '';
        $type = $_POST['type'] ?? '';
        $location = $_POST['location'] ?? '';
        $setup = $_POST['setup'] ?? '';
        $department = $_POST['department'] ?? '';
        $duration_hours = $_POST['duration_hours'] ?? '';
        $slots = $_POST['slots'] ?? '';
        $stipend = $_POST['stipend'] ?? '';
        $application_deadline = empty($_POST['application_deadline']) ? null : $_POST['application_deadline'];
        
        if (empty($title) || empty($description) || empty($requirements) || empty($type) || 
            empty($location) || empty($setup) || empty($department) || empty($duration_hours) || empty($slots)) {
            $error = "Please fill in all required fields.";
        } else {
            if ($is_edit) {
                $sql = "UPDATE internships SET 
                        title = ?, description = ?, requirements = ?, type = ?, location = ?, 
                        setup = ?, department = ?, duration_hours = ?, slots = ?, 
                        stipend = ?, application_deadline = ?, updated_at = NOW()
                        WHERE id = ? AND company_id = ?";
                $params = [$title, $description, $requirements, $type, $location, $setup, 
                           $department, $duration_hours, $slots, $stipend, $application_deadline, 
                           $edit_id, $company['id']];
                
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $_SESSION['success'] = "Internship updated successfully!";
                header("Location: manage_internships.php");
                exit();
            } else {
                $sql = "INSERT INTO internships (company_id, title, description, requirements, type, location, 
                        setup, department, duration_hours, slots, stipend, application_deadline, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'open', NOW())";
                $params = [$company['id'], $title, $description, $requirements, $type, $location, 
                           $setup, $department, $duration_hours, $slots, $stipend, $application_deadline];
                
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                
                $post_content = "📢 NEW OPPORTUNITY: We're hiring for a $title position! Apply now through GreenBridge.";
                $conn->prepare("INSERT INTO posts (user_id, content, type, status, created_at) VALUES (?, ?, 'announcement', 'approved', NOW())")
                     ->execute([$user_id, $post_content]);
                
                $_SESSION['success'] = "Internship posted successfully!";
                header("Location: manage_internships.php");
                exit();
            }
        }
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Internship' : 'Post Internship'; ?> - GreenBridge</title>
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
            --gold: #c9952a;
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
        h1, h2, h3, .serif {
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
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
        
        /* Form Card Enhanced */
        .card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: none;
            margin-bottom: 1.8rem;
            overflow: hidden;
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
            font-size: 1rem;
            border-bottom: 1px solid var(--gray-border);
            color: var(--forest);
        }
        .card-header i {
            font-size: 1.1rem;
            margin-right: 8px;
            color: var(--mint);
        }
        .card-body { padding: 1.6rem 1.8rem; }
        
        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--forest);
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .form-control, .form-select {
            border: 1px solid var(--gray-border);
            border-radius: 10px;
            padding: 0.7rem 1rem;
            font-size: 0.9rem;
            width: 100%;
            transition: all 0.2s;
            background: white;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--forest-lt);
            outline: none;
            box-shadow: 0 0 0 3px rgba(61,122,82,0.12);
        }
        textarea.form-control {
            resize: vertical;
            min-height: 110px;
        }
        
        .btn-primary {
            background: var(--forest);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.25s;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            letter-spacing: 0.3px;
        }
        .btn-primary:hover { 
            background: var(--forest-mid); 
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(45,90,61,0.25);
        }
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .form-section {
            border-bottom: 1px solid var(--gray-border);
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .form-section-title {
            font-weight: 700;
            margin-bottom: 1.2rem;
            color: var(--forest);
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-section-title i {
            color: var(--mint);
            font-size: 1.1rem;
        }
        
        /* Alert Styles */
        .alert-success {
            background: #e6f7ef;
            color: #1e6f3f;
            padding: 1rem 1.2rem;
            border-radius: 14px;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--mint);
            font-weight: 500;
        }
        .alert-danger {
            background: #fee9e7;
            color: #b13e3e;
            padding: 1rem 1.2rem;
            border-radius: 14px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #e07c7c;
            font-weight: 500;
        }
        
        .required-star {
            color: #d9534f;
            margin-left: 3px;
            font-weight: 600;
        }
        
        /* Tips Card Styling */
        .tips-list {
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
        }
        .tips-list li {
            padding: 0.4rem 0;
            padding-left: 1.5rem;
            position: relative;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        .tips-list li:before {
            content: "▹";
            position: absolute;
            left: 0;
            color: var(--mint);
            font-weight: 600;
        }
        
        /* Helper text */
        .text-muted-small {
            font-size: 0.7rem;
            color: #8aa094;
            margin-top: 5px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-container { padding: 1.2rem; }
            .card-body { padding: 1.2rem; }
            .page-title { font-size: 1.5rem; }
            .subnav { padding: 0 12px; }
            .btn-primary { padding: 0.65rem 1.2rem; }
        }
        
        /* subtle animation */
        .fade-in {
            animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px);}
            to { opacity: 1; transform: translateY(0);}
        }
        .card {
            animation: fadeIn 0.35s ease;
        }
        
        /* custom placeholder style */
        ::placeholder {
            color: #b9c5b5;
            font-size: 0.85rem;
        }
        
        /* inline icon alignment */
        .bi {
            vertical-align: middle;
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
    <div class="subnav-item active" onclick="location.href='post_internship.php'">Post Internship</div>
    <div class="subnav-item" onclick="location.href='manage_internships.php'">Manage Internships</div>
    <div class="subnav-item" onclick="location.href='manage_applications.php'">Applications</div>
    <div class="subnav-item" onclick="location.href='chat.php'">Chat</div>
    <div class="subnav-item" onclick="location.href='scout_students.php'">Scout Students</div>
    <div class="subnav-item" onclick="location.href='manage_interns.php'">Manage Interns</div>
    <div class="subnav-item" onclick="location.href='profile.php'">Profile</div>
</div>

<div class="main-container">
    <?php if ($message): ?>
        <div class="alert-success">
            <i class="bi bi-check-circle-fill me-2" style="font-size:0.9rem;"></i> 
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2" style="font-size:0.9rem;"></i> 
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title"><?php echo $is_edit ? 'Edit Internship' : 'Post New Internship'; ?></div>
        <div class="page-subtitle"><?php echo $is_edit ? 'Update your internship posting details' : 'Share an opportunity to shape future professionals'; ?></div>
    </div>
    
    <!-- Main Form Card -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-briefcase"></i> <?php echo $is_edit ? 'Edit Internship Details' : 'Internship Information'; ?>
        </div>
        <div class="card-body">
            <form method="POST">
                <!-- Basic Information -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-info-square"></i> Basic Information
                    </div>
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label">Position Title <span class="required-star">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="e.g., Frontend Developer Intern, Marketing Assistant" value="<?php echo $is_edit ? htmlspecialchars($internship_data['title']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Internship Type <span class="required-star">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="">Select type</option>
                                <option value="OJT" <?php echo ($is_edit && $internship_data['type'] == 'OJT') ? 'selected' : ''; ?>>OJT (On-the-Job Training)</option>
                                <option value="Internship" <?php echo ($is_edit && $internship_data['type'] == 'Internship') ? 'selected' : ''; ?>>Internship</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Target Department <span class="required-star">*</span></label>
                            <select name="department" class="form-select" required>
                                <option value="">Select department</option>
                                <option value="CCS" <?php echo ($is_edit && $internship_data['department'] == 'CCS') ? 'selected' : ''; ?>>CCS - Computer Studies</option>
                                <option value="CEAT" <?php echo ($is_edit && $internship_data['department'] == 'CEAT') ? 'selected' : ''; ?>>CEAT - Engineering & Technology</option>
                                <option value="COB" <?php echo ($is_edit && $internship_data['department'] == 'COB') ? 'selected' : ''; ?>>COB - Business & Accountancy</option>
                                <option value="CAS" <?php echo ($is_edit && $internship_data['department'] == 'CAS') ? 'selected' : ''; ?>>CAS - Arts & Sciences</option>
                                <option value="All" <?php echo ($is_edit && $internship_data['department'] == 'All') ? 'selected' : ''; ?>>All Departments (Open to all)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Location & Work Setup -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-geo-alt"></i> Location & Work Setup
                    </div>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Office / Location <span class="required-star">*</span></label>
                            <input type="text" name="location" class="form-control" placeholder="e.g., Makati City, Remote, BGC, Taguig" value="<?php echo $is_edit ? htmlspecialchars($internship_data['location']) : ''; ?>" required>
                            <div class="text-muted-small">Full address or main work area</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Work Arrangement <span class="required-star">*</span></label>
                            <select name="setup" class="form-select" required>
                                <option value="">Select arrangement</option>
                                <option value="Onsite" <?php echo ($is_edit && $internship_data['setup'] == 'Onsite') ? 'selected' : ''; ?>>Onsite (Face-to-Face)</option>
                                <option value="Remote" <?php echo ($is_edit && $internship_data['setup'] == 'Remote') ? 'selected' : ''; ?>>Remote (Work from home)</option>
                                <option value="Hybrid" <?php echo ($is_edit && $internship_data['setup'] == 'Hybrid') ? 'selected' : ''; ?>>Hybrid (Combined)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Role Overview -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-file-text"></i> Role Description & Requirements
                    </div>
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label">Job Description <span class="required-star">*</span></label>
                            <textarea name="description" class="form-control" rows="5" placeholder="Describe the intern's day-to-day tasks, projects, and learning outcomes. Be clear and inspiring..." required><?php echo $is_edit ? htmlspecialchars($internship_data['description']) : ''; ?></textarea>
                            <div class="text-muted-small">Highlight responsibilities and growth opportunities</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Qualifications & Requirements <span class="required-star">*</span></label>
                            <textarea name="requirements" class="form-control" rows="4" placeholder="List specific skills, software knowledge, education level, or other prerequisites..." required><?php echo $is_edit ? htmlspecialchars($internship_data['requirements']) : ''; ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Duration & Compensation -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-clock-history"></i> Duration & Compensation
                    </div>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label class="form-label">Total Hours <span class="required-star">*</span></label>
                            <input type="number" name="duration_hours" class="form-control" placeholder="e.g., 400 hours" value="<?php echo $is_edit ? $internship_data['duration_hours'] : ''; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Available Slots <span class="required-star">*</span></label>
                            <input type="number" name="slots" class="form-control" placeholder="Number of interns needed" value="<?php echo $is_edit ? $internship_data['slots'] : ''; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Stipend / Allowance</label>
                            <input type="text" name="stipend" class="form-control" placeholder="e.g., ₱5,000/month, Meal allowance, Unpaid" value="<?php echo $is_edit ? htmlspecialchars($internship_data['stipend']) : ''; ?>">
                            <div class="text-muted-small">Optional but recommended</div>
                        </div>
                    </div>
                </div>
                
                <!-- Application Deadline -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-calendar-week"></i> Application Timeline
                    </div>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Application Deadline</label>
                            <input type="date" name="application_deadline" class="form-control" value="<?php echo $is_edit && $internship_data['application_deadline'] ? ($internship_data['application_deadline'] instanceof DateTime ? $internship_data['application_deadline']->format('Y-m-d') : date('Y-m-d', strtotime($internship_data['application_deadline']))) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted-small mt-2">Leave empty if the position is open until filled</div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="mt-4 d-flex gap-3">
                    <button type="submit" name="post_internship" class="btn-primary">
                        <i class="bi <?php echo $is_edit ? 'bi-pencil-square' : 'bi-send'; ?>"></i> 
                        <?php echo $is_edit ? 'Update Internship' : 'Publish Internship'; ?>
                    </button>
                    <?php if (!$is_edit): ?>
                    <button type="reset" class="btn btn-outline-secondary rounded-pill px-4" style="border-color: var(--gray-border); color: var(--text-muted); font-weight:500;">Reset</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tips & Best Practices Card -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-lightbulb"></i> Tips for an Outstanding Internship Posting
        </div>
        <div class="card-body">
            <ul class="tips-list">
                <li>Write a clear and specific title – avoid vague terms like "Intern Wanted"</li>
                <li>List concrete tasks so students know what they will learn</li>
                <li>Mention mentorship, training, or certificates provided</li>
                <li>Specify work schedule flexibility if applicable</li>
                <li>Stipend details increase applications, even a small allowance helps</li>
                <li>Keep requirements realistic for students (no 5+ years experience)</li>
            </ul>
            <div class="mt-3 pt-2 border-top" style="border-color: var(--gray-border);">
                <small class="text-muted"><i class="bi bi-eye"></i> Pro tip: Postings with complete details receive 3x more quality applicants.</small>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Add subtle client-side validation for empty fields if needed (improves UX)
    (function() {
        const form = document.querySelector('form');
        if(form) {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let empty = false;
                requiredFields.forEach(field => {
                    if(!field.value.trim()) {
                        field.style.borderColor = '#d9534f';
                        empty = true;
                    } else {
                        field.style.borderColor = '';
                    }
                });
                if(empty) {
                    e.preventDefault();
                    // gentle scroll to first empty field
                    const firstEmpty = form.querySelector('[required]');
                    if(firstEmpty && firstEmpty.style.borderColor === 'rgb(217, 83, 79)') {
                        firstEmpty.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstEmpty.focus();
                    }
                    // show temporary alert if needed
                    let alertDiv = document.querySelector('.alert-danger');
                    if(!alertDiv || alertDiv.innerText.indexOf('Please fill') === -1) {
                        const msgDiv = document.createElement('div');
                        msgDiv.className = 'alert-danger mb-3';
                        msgDiv.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i> Please fill in all required fields marked with *';
                        const container = document.querySelector('.main-container');
                        const firstChild = container.firstChild;
                        container.insertBefore(msgDiv, firstChild);
                        setTimeout(() => { msgDiv.style.opacity = '0'; setTimeout(() => msgDiv.remove(), 800); }, 3500);
                    }
                }
            });
            // remove red border on input
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('input', function() { this.style.borderColor = ''; });
                input.addEventListener('change', function() { this.style.borderColor = ''; });
            });
        }
    })();
</script>
</body>
</html>