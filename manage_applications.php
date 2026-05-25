<?php
// company/manage_applications.php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

// Check if company is logged in
requireCompany();

$user_id = $_SESSION['user_id'];
$company = getCompanyData($conn, $user_id);

// Initialize default variables to prevent undefined warnings
$status_filter = 'all';
$internship_filter = 'all';
$applications = [];
$stats = [
    'total' => 0,
    'pending' => 0,
    'accepted' => 0,
    'rejected' => 0
];
$internships = [];

try {
    // Handle application status update
    if (isset($_POST['update_status'])) {
        $application_id = $_POST['application_id'];
        $new_status = $_POST['status'];
        $notes = $_POST['notes'] ?? '';
        
        $sql = "UPDATE applications SET status = ?, company_notes = ?, reviewed_at = NOW() 
                WHERE id = ? AND internship_id IN (SELECT id FROM internships WHERE company_id = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$new_status, $notes, $application_id, $company['id']]);
        
        $_SESSION['success'] = "Application status updated to " . ucfirst($new_status) . "!";
        
        // Create notification for student
        $notifSql = "INSERT INTO notifications (user_id, type, title, message, link) 
                     VALUES ((SELECT user_id FROM applications WHERE id = ?), 'application', 'Application Update', 
                     'Your application has been " . $new_status . " by " . $company['company_name'] . "', '/student/applications.php')";
        $conn->prepare($notifSql)->execute([$application_id]);
        
        header("Location: manage_applications.php");
        exit();
    }

    $status_filter = $_GET['status'] ?? 'all';
    $internship_filter = $_GET['internship'] ?? 'all';

    $stmt = $conn->prepare("SELECT id, title FROM internships WHERE company_id = ? ORDER BY created_at DESC");
    $stmt->execute([$company['id']]);
    $internships = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT a.*, 
                   i.title as internship_title, i.type as internship_type, i.location,
                   s.fullname as student_name, s.course, s.year_level, s.student_id,
                   u.email as student_email,
                   (SELECT COUNT(*) FROM documents WHERE student_id = s.id AND doc_type = 'resume') as has_resume
            FROM applications a
            JOIN internships i ON a.internship_id = i.id
            JOIN students s ON a.student_id = s.id
            JOIN users u ON s.user_id = u.id
            WHERE i.company_id = ?";
    $params = [$company['id']];

    if ($status_filter != 'all') {
        $sql .= " AND a.status = ?";
        $params[] = $status_filter;
    }
    if ($internship_filter != 'all') {
        $sql .= " AND i.id = ?";
        $params[] = $internship_filter;
    }

    $sql .= " ORDER BY CASE a.status WHEN 'pending' THEN 0 WHEN 'accepted' THEN 1 ELSE 2 END, a.applied_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sqlStats = "SELECT COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM applications a JOIN internships i ON a.internship_id = i.id WHERE i.company_id = ?";
    $stmtStats = $conn->prepare($sqlStats);
    $stmtStats->execute([$company['id']]);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applications - GreenBridge</title>
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
            max-width: 1280px;
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
        
        /* Stats Cards Enhanced */
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
            font-size: 2rem; 
            font-weight: 800; 
            color: var(--forest);
            font-family: 'Playfair Display', serif;
            line-height: 1.2;
        }
        .stat-label { 
            font-size: 0.7rem; 
            font-weight: 600; 
            text-transform: uppercase; 
            color: var(--text-muted);
            letter-spacing: 0.5px;
            margin-top: 6px;
        }
        
        /* Filters Bar */
        .filter-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.8rem;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-select {
            border: 1px solid var(--gray-border);
            border-radius: 40px;
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            background: var(--white);
            font-weight: 500;
            color: var(--text-dark);
            cursor: pointer;
            transition: all 0.2s;
        }
        .filter-select:focus {
            outline: none;
            border-color: var(--mint);
            box-shadow: 0 0 0 2px rgba(76,175,120,0.2);
        }
        
        /* Application Cards */
        .app-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-border);
            padding: 1.4rem;
            margin-bottom: 1rem;
            transition: all 0.25s ease;
        }
        .app-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--sage-dk);
        }
        
        .app-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .student-info { display: flex; gap: 1rem; align-items: center; }
        .student-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--sage) 0%, var(--sage-dk) 100%);
            display: grid;
            place-items: center;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--forest);
        }
        .student-name { font-weight: 700; font-size: 1rem; color: var(--forest); }
        .student-details { font-size: 0.7rem; color: var(--text-muted); margin-top: 3px; }
        
        .status-badge {
            padding: 0.25rem 0.9rem;
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .status-pending { background: #fef3cd; color: #856404; }
        .status-accepted { background: #e6f7ef; color: #1e6f3f; }
        .status-rejected { background: #fee9e7; color: #b13e3e; }
        
        .app-details {
            display: flex;
            flex-wrap: wrap;
            gap: 1.2rem;
            margin-bottom: 1rem;
            padding: 0.8rem 1rem;
            background: var(--gray-light);
            border-radius: 12px;
        }
        .detail-item { font-size: 0.75rem; }
        .detail-label { font-weight: 700; color: var(--forest); margin-right: 4px; }
        
        .cover-letter {
            background: var(--gray-light);
            padding: 0.9rem 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            font-size: 0.8rem;
            border-left: 3px solid var(--mint);
            line-height: 1.5;
        }
        .cover-letter strong {
            color: var(--forest);
        }
        
        .action-buttons {
            display: flex;
            gap: 0.6rem;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        .btn-accept { 
            background: #e6f7ef; 
            color: #1e6f3f; 
            border: none; 
            padding: 0.4rem 1.2rem; 
            border-radius: 30px; 
            font-size: 0.75rem; 
            font-weight: 600;
            cursor: pointer; 
            transition: all 0.2s;
        }
        .btn-accept:hover { background: #c8e9da; transform: translateY(-1px); }
        .btn-reject { 
            background: #fee9e7; 
            color: #b13e3e; 
            border: none; 
            padding: 0.4rem 1.2rem; 
            border-radius: 30px; 
            font-size: 0.75rem; 
            font-weight: 600;
            cursor: pointer; 
            transition: all 0.2s;
        }
        .btn-reject:hover { background: #fcd6d2; transform: translateY(-1px); }
        .btn-view { 
            background: var(--white); 
            border: 1px solid var(--gray-border); 
            padding: 0.4rem 1.2rem; 
            border-radius: 30px; 
            font-size: 0.75rem; 
            font-weight: 500;
            cursor: pointer; 
            transition: all 0.2s;
        }
        .btn-view:hover { background: var(--sage); border-color: var(--mint); }
        
        .alert-success {
            background: #e6f7ef;
            color: #1e6f3f;
            padding: 0.9rem 1.2rem;
            border-radius: 14px;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--mint);
            font-weight: 500;
        }
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-muted);
        }
        .empty-state i { font-size: 3rem; margin-bottom: 0.75rem; opacity: 0.4; color: var(--sage-dk); }
        
        /* Modal Styles */
        .modal-content {
            border-radius: var(--radius-lg);
            border: none;
        }
        .modal-header {
            background: #b13e3e;
            color: white;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            padding: 1rem 1.5rem;
        }
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        .form-label {
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--forest);
        }
        .form-control {
            border-radius: 10px;
            border: 1px solid var(--gray-border);
            padding: 0.65rem;
            font-size: 0.85rem;
        }
        .form-control:focus {
            border-color: var(--mint);
            box-shadow: 0 0 0 2px rgba(76,175,120,0.2);
        }
        .modal-footer .btn-outline {
            background: transparent;
            border: 1px solid var(--gray-border);
            padding: 0.4rem 1.2rem;
            border-radius: 30px;
        }
        
        @media (max-width: 768px) {
            .main-container { padding: 1rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 0.8rem; }
            .app-header { flex-direction: column; gap: 0.5rem; }
            .action-buttons { justify-content: flex-start; margin-top: 0.5rem; }
            .page-title { font-size: 1.5rem; }
            .subnav { padding: 0 12px; }
        }
        
        /* Animation */
        .fade-in {
            animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px);}
            to { opacity: 1; transform: translateY(0);}
        }
        .app-card {
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
    <div class="subnav-item active" onclick="location.href='manage_applications.php'">Applications</div>
    <div class="subnav-item" onclick="location.href='chat.php'">Chat</div>
    <div class="subnav-item" onclick="location.href='scout_students.php'">Scout Students</div>
    <div class="subnav-item" onclick="location.href='manage_interns.php'">Manage Interns</div>
    <div class="subnav-item" onclick="location.href='profile.php'">Profile</div>
</div>

<div class="main-container">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-success">
            <i class="bi bi-check-circle-fill me-2"></i> 
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <div class="page-header">
        <div class="page-title">Manage Applications</div>
        <div class="page-subtitle">Review and manage student applications for your internship opportunities</div>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card" onclick="filterByStatus('all')">
            <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
            <div class="stat-label">Total Applications</div>
        </div>
        <div class="stat-card" onclick="filterByStatus('pending')">
            <div class="stat-value"><?php echo $stats['pending'] ?? 0; ?></div>
            <div class="stat-label">Pending Review</div>
        </div>
        <div class="stat-card" onclick="filterByStatus('accepted')">
            <div class="stat-value"><?php echo $stats['accepted'] ?? 0; ?></div>
            <div class="stat-label">Accepted</div>
        </div>
        <div class="stat-card" onclick="filterByStatus('rejected')">
            <div class="stat-value"><?php echo $stats['rejected'] ?? 0; ?></div>
            <div class="stat-label">Rejected</div>
        </div>
    </div>
    
    <!-- Filters Bar -->
    <div class="filter-bar">
        <select class="filter-select" id="statusFilter" onchange="filterApplications()">
            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="accepted" <?php echo $status_filter == 'accepted' ? 'selected' : ''; ?>>Accepted</option>
            <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
        </select>
        
        <select class="filter-select" id="internshipFilter" onchange="filterApplications()">
            <option value="all" <?php echo $internship_filter == 'all' ? 'selected' : ''; ?>>All Internships</option>
            <?php foreach ($internships as $internship): ?>
                <option value="<?php echo $internship['id']; ?>" <?php echo $internship_filter == $internship['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($internship['title']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <!-- Applications List -->
    <?php if (empty($applications)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p>No applications yet. Post internships to receive student applications.</p>
            <a href="post_internship.php" class="btn-primary" style="display:inline-block; text-decoration:none; background: var(--forest); color: white; padding: 0.5rem 1.2rem; border-radius: 30px; font-size:0.8rem;">Post Internship</a>
        </div>
    <?php else: ?>
        <?php foreach ($applications as $app): ?>
            <div class="app-card">
                <div class="app-header">
                    <div class="student-info">
                        <div class="student-avatar"><?php echo strtoupper(substr($app['student_name'], 0, 2)); ?></div>
                        <div>
                            <div class="student-name"><?php echo htmlspecialchars($app['student_name']); ?></div>
                            <div class="student-details">
                                <?php echo htmlspecialchars($app['course']); ?> • Year <?php echo $app['year_level']; ?>
                                <br>Student ID: <?php echo htmlspecialchars($app['student_id']); ?>
                            </div>
                        </div>
                    </div>
                    <div>
                        <span class="status-badge status-<?php echo $app['status']; ?>">
                            <?php echo ucfirst($app['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="app-details">
                    <div class="detail-item">
                        <span class="detail-label">Position:</span> <?php echo htmlspecialchars($app['internship_title']); ?>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Applied:</span> <?php echo $app['applied_at'] instanceof DateTime ? $app['applied_at']->format('M d, Y') : date('M d, Y', strtotime($app['applied_at'])); ?>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Resume:</span>
                        <?php if ($app['has_resume']): ?>
                            <span style="color: var(--mint);"><i class="bi bi-check-circle"></i> Uploaded</span>
                        <?php else: ?>
                            <span class="text-muted">Not uploaded</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($app['cover_letter']): ?>
                    <div class="cover-letter">
                        <strong><i class="bi bi-envelope"></i> Cover Letter:</strong><br>
                        <?php if (strlen($app['cover_letter']) > 300): ?>
                            <span id="short-cover-<?php echo $app['id']; ?>">
                                <?php echo nl2br(htmlspecialchars(substr($app['cover_letter'], 0, 300))); ?>...
                                <a href="javascript:void(0);" onclick="toggleCover(<?php echo $app['id']; ?>, true)" style="color: var(--mint); text-decoration: none; font-weight: 600; margin-left: 5px;">Read More</a>
                            </span>
                            <span id="full-cover-<?php echo $app['id']; ?>" style="display: none;">
                                <?php echo nl2br(htmlspecialchars($app['cover_letter'])); ?>
                                <a href="javascript:void(0);" onclick="toggleCover(<?php echo $app['id']; ?>, false)" style="color: var(--mint); text-decoration: none; font-weight: 600; margin-left: 5px;">Read Less</a>
                            </span>
                        <?php else: ?>
                            <?php echo nl2br(htmlspecialchars($app['cover_letter'])); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="action-buttons">
                    <button class="btn-view" onclick="viewStudentProfile(<?php echo $app['student_id']; ?>)">
                        <i class="bi bi-eye"></i> View Profile
                    </button>
                    <?php if ($app['status'] == 'pending'): ?>
                        <button class="btn-accept" onclick="updateStatus(<?php echo $app['id']; ?>, 'accepted')">
                            <i class="bi bi-check-lg"></i> Accept
                        </button>
                        <button class="btn-reject" onclick="openRejectModal(<?php echo $app['id']; ?>, '<?php echo htmlspecialchars($app['student_name']); ?>')">
                            <i class="bi bi-x-lg"></i> Reject
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Reject Application</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p>You are about to reject <strong id="rejectStudentName"></strong>'s application.</p>
                    <div class="mb-3">
                        <label class="form-label">Reason (optional)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Provide feedback to help the student improve..."></textarea>
                    </div>
                    <input type="hidden" name="application_id" id="rejectAppId">
                    <input type="hidden" name="status" value="rejected">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_status" class="btn-reject" style="padding:0.5rem 1.2rem;">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleCover(id, expand) {
    const shortSpan = document.getElementById('short-cover-' + id);
    const fullSpan = document.getElementById('full-cover-' + id);
    if (expand) {
        shortSpan.style.display = 'none';
        fullSpan.style.display = 'inline';
    } else {
        shortSpan.style.display = 'inline';
        fullSpan.style.display = 'none';
    }
}

function filterApplications() {
    const status = document.getElementById('statusFilter').value;
    const internship = document.getElementById('internshipFilter').value;
    window.location.href = `manage_applications.php?status=${status}&internship=${internship}`;
}

function filterByStatus(status) {
    window.location.href = `manage_applications.php?status=${status}&internship=all`;
}

function updateStatus(appId, status) {
    const action = status === 'accepted' ? 'accept' : 'reject';
    if (confirm(`Are you sure you want to ${action} this application?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="application_id" value="${appId}">
            <input type="hidden" name="status" value="${status}">
            <input type="hidden" name="update_status" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function openRejectModal(appId, studentName) {
    document.getElementById('rejectAppId').value = appId;
    document.getElementById('rejectStudentName').textContent = studentName;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function viewStudentProfile(studentId) {
    window.location.href = `student_profile.php?id=${studentId}`;
}

// Add smooth animation for stat cards
document.querySelectorAll('.stat-card').forEach(card => {
    card.style.cursor = 'pointer';
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>