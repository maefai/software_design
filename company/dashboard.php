<?php
// company/dashboard.php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

// Check if company is logged in
requireCompany();

$user_id = $_SESSION['user_id'];
$company = getCompanyData($conn, $user_id);

// Get company statistics
$stats = [];

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM internships WHERE company_id = ?");
    $stmt->execute([$company['id']]);
    $stats['total_internships'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM internships WHERE company_id = ? AND status = 'open'");
    $stmt->execute([$company['id']]);
    $stats['active_internships'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM applications a JOIN internships i ON a.internship_id = i.id WHERE i.company_id = ?");
    $stmt->execute([$company['id']]);
    $stats['total_applications'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM applications a JOIN internships i ON a.internship_id = i.id WHERE i.company_id = ? AND a.status = 'pending'");
    $stmt->execute([$company['id']]);
    $stats['pending_applications'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(DISTINCT d.student_id) as total FROM dtr_logs d JOIN internships i ON d.internship_id = i.id WHERE i.company_id = ? AND d.status = 'active'");
    $stmt->execute([$company['id']]);
    $stats['active_interns'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $recent_applications = [];
    $stmt = $conn->prepare("SELECT a.*, i.title as internship_title, s.fullname as student_name, s.course, s.year_level FROM applications a JOIN internships i ON a.internship_id = i.id JOIN students s ON a.student_id = s.id WHERE i.company_id = ? ORDER BY a.applied_at DESC");
    $stmt->execute([$company['id']]);
    $recent_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $recent_posts = [];
    $stmt = $conn->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $recent_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Dashboard - GreenBridge</title>
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
            --shadow-lg: 0 16px 32px rgba(26,58,36,0.12);
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
        
        /* Welcome Section - No emoji */
        .welcome-section {
            background: linear-gradient(135deg, var(--forest) 0%, var(--forest-mid) 100%);
            border-radius: var(--radius-lg);
            padding: 1.8rem 2rem;
            margin-bottom: 2rem;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(76,175,120,0.1);
            border-radius: 50%;
        }
        .welcome-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
            letter-spacing: -0.2px;
        }
        .welcome-text {
            opacity: 0.9;
            font-size: 0.9rem;
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
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
        }
        .stat-card::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--mint);
            transform: scaleX(0);
            transition: transform 0.3s;
        }
        .stat-card:hover { 
            transform: translateY(-5px); 
            box-shadow: var(--shadow-lg);
        }
        .stat-card:hover::before { transform: scaleX(1); }
        .stat-icon {
            font-size: 2rem;
            color: var(--mint);
            margin-bottom: 0.5rem;
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
        }
        
        /* Section Headings */
        .section-heading {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Cards */
        .card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: none;
            margin-bottom: 1.8rem;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: box-shadow 0.3s;
        }
        .card:hover { box-shadow: var(--shadow-md); }
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
        .card-header a {
            font-size: 0.7rem;
            font-weight: 500;
        }
        .card-body { padding: 1.2rem 1.5rem; }
        
        /* Application Row */
        .app-row {
            display: flex;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid var(--gray-border);
            transition: background 0.2s;
        }
        .app-row:hover { background: #fafbfa; padding-left: 0.5rem; margin-left: -0.5rem; border-radius: 12px; }
        .app-row:last-child { border-bottom: none; }
        .app-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--sage) 0%, var(--sage-dk) 100%);
            display: grid;
            place-items: center;
            font-weight: 700;
            margin-right: 1rem;
            flex-shrink: 0;
            color: var(--forest);
        }
        .app-info { flex: 1; }
        .app-name { font-weight: 700; font-size: 0.9rem; }
        .app-details { font-size: 0.7rem; color: var(--text-muted); margin-top: 2px; }
        .app-status {
            font-size: 0.7rem;
            padding: 0.25rem 0.85rem;
            border-radius: 30px;
            font-weight: 600;
        }
        .status-pending { background: #fef3cd; color: #856404; }
        .status-approved, .status-accepted { background: #e6f7ef; color: #1e6f3f; }
        .status-rejected { background: #fee9e7; color: #b13e3e; }
        
        /* Quick Action Buttons */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        .action-btn {
            background: var(--white);
            border: 1px solid var(--gray-border);
            border-radius: var(--radius);
            padding: 1rem;
            text-align: center;
            text-decoration: none;
            color: var(--text-dark);
            transition: all 0.2s;
            cursor: pointer;
        }
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--mint);
        }
        .action-icon {
            font-size: 1.5rem;
            color: var(--mint);
            margin-bottom: 0.5rem;
        }
        .action-title {
            font-weight: 700;
            font-size: 0.8rem;
        }
        .action-desc {
            font-size: 0.65rem;
            color: var(--text-muted);
            margin-top: 0.2rem;
        }
        
        /* Post Card */
        .post-card {
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-border);
            transition: background 0.2s;
        }
        .post-card:hover { background: #fafbfa; padding-left: 0.5rem; margin-left: -0.5rem; border-radius: 12px; }
        .post-card:last-child { border-bottom: none; }
        .post-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }
        .post-icon {
            width: 36px;
            height: 36px;
            background: var(--sage);
            border-radius: 10px;
            display: grid;
            place-items: center;
            color: var(--forest);
        }
        .post-content { 
            font-size: 0.85rem; 
            line-height: 1.5; 
            margin-bottom: 0.6rem;
            color: var(--text-dark);
        }
        .post-meta { 
            font-size: 0.7rem; 
            color: var(--text-muted);
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .post-meta i {
            margin-right: 3px;
        }
        .status-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 30px;
            font-size: 0.6rem;
            font-weight: 700;
        }
        .status-approved { background: #e6f7ef; color: #1e6f3f; }
        .status-pending { background: #fef3cd; color: #856404; }
        
        .btn-primary {
            background: var(--forest);
            color: white;
            border: none;
            padding: 0.5rem 1.2rem;
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
            padding: 0.4rem 1rem;
            border-radius: 40px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--text-muted);
        }
        .btn-outline:hover {
            background: var(--sage);
            border-color: var(--mint);
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
        }
        .empty-state i { font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.4; color: var(--sage-dk); }
        
        /* Modal */
        .modal-content {
            border-radius: var(--radius-lg);
            border: none;
        }
        .modal-header {
            background: var(--forest);
            color: white;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            padding: 1rem 1.5rem;
        }
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        .form-control {
            border-radius: var(--radius);
            border: 1px solid var(--gray-border);
            padding: 0.75rem;
            font-size: 0.9rem;
        }
        .form-control:focus {
            border-color: var(--mint);
            box-shadow: 0 0 0 0.2rem rgba(76,175,120,0.2);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-in {
            animation: fadeInUp 0.5s ease-out;
        }
        
        @media (max-width: 768px) {
            .main-container { padding: 1rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 0.8rem; }
            .quick-actions { grid-template-columns: 1fr; }
            .welcome-section { padding: 1.2rem; }
            .welcome-title { font-size: 1.3rem; }
            .card-body { padding: 1rem; }
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
    <div class="subnav-item active" onclick="location.href='dashboard.php'">Dashboard</div>
    <div class="subnav-item" onclick="location.href='post_internship.php'">Post Internship</div>
    <div class="subnav-item" onclick="location.href='manage_internships.php'">Manage Internships</div>
    <div class="subnav-item" onclick="location.href='manage_applications.php'">Applications</div>
    <div class="subnav-item" onclick="location.href='chat.php'">Chat</div>
    <div class="subnav-item" onclick="location.href='scout_students.php'">Scout Students</div>
    <div class="subnav-item" onclick="location.href='manage_interns.php'">Manage Interns</div>
    <div class="subnav-item" onclick="location.href='logbook.php'">Logbooks</div>
    <div class="subnav-item" onclick="location.href='profile.php'">Profile</div>
</div>

<div class="main-container animate-in">
    <!-- Welcome Section (no emoji) -->
    <div class="welcome-section">
        <div class="welcome-title">
            Welcome back, <?php echo htmlspecialchars($company['company_name']); ?>
        </div>
        <div class="welcome-text">
            Manage your internship postings, review applications, and scout talented students for your organization.
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card" onclick="location.href='manage_internships.php'">
            <div class="stat-icon"><i class="bi bi-briefcase"></i></div>
            <div class="stat-value"><?php echo $stats['total_internships']; ?></div>
            <div class="stat-label">Total Postings</div>
        </div>
        <div class="stat-card" onclick="location.href='manage_internships.php?filter=open'">
            <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
            <div class="stat-value"><?php echo $stats['active_internships']; ?></div>
            <div class="stat-label">Active Openings</div>
        </div>
        <div class="stat-card" onclick="location.href='manage_applications.php'">
            <div class="stat-icon"><i class="bi bi-people"></i></div>
            <div class="stat-value"><?php echo $stats['total_applications']; ?></div>
            <div class="stat-label">Total Applications</div>
        </div>
        <div class="stat-card" onclick="location.href='manage_interns.php'">
            <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
            <div class="stat-value"><?php echo $stats['active_interns']; ?></div>
            <div class="stat-label">Active Interns</div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Left Column - Recent Applications -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-people"></i> Recent Applications
                    <a href="manage_applications.php" class="float-end text-muted" style="font-size:0.7rem; text-decoration:none;">View all →</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_applications)): ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>No applications yet. Post internships to receive applications.</p>
                            <a href="post_internship.php" class="btn-primary" style="display:inline-block; text-decoration:none;">Post Internship</a>
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($recent_applications, 0, 5) as $app): ?>
                            <div class="app-row">
                                <div class="app-avatar"><?php echo strtoupper(substr($app['student_name'], 0, 2)); ?></div>
                                <div class="app-info">
                                    <div class="app-name"><?php echo htmlspecialchars($app['student_name']); ?></div>
                                    <div class="app-details"><?php echo htmlspecialchars($app['course']); ?> • Year <?php echo $app['year_level']; ?></div>
                                    <div class="app-details">Applied for: <?php echo htmlspecialchars($app['internship_title']); ?></div>
                                </div>
                                <div>
                                    <?php 
                                    $statusClass = 'status-pending';
                                    if ($app['status'] == 'accepted') {
                                        $statusClass = 'status-accepted';
                                    } elseif ($app['status'] == 'rejected') {
                                        $statusClass = 'status-rejected';
                                    }
                                    ?>
                                    <span class="app-status <?php echo $statusClass; ?>"><?php echo ucfirst($app['status']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($recent_applications) > 5): ?>
                            <div class="text-center mt-3">
                                <a href="manage_applications.php" class="btn-outline" style="text-decoration:none;">View all <?php echo count($recent_applications); ?> applications</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Quick Actions & Recent Posts -->
        <div class="col-lg-6">
            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header"><i class="bi bi-lightning-charge"></i> Quick Actions</div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="post_internship.php" class="action-btn">
                            <div class="action-icon"><i class="bi bi-plus-circle"></i></div>
                            <div class="action-title">Post Internship</div>
                            <div class="action-desc">Create new opportunity</div>
                        </a>
                        <a href="scout_students.php" class="action-btn">
                            <div class="action-icon"><i class="bi bi-search"></i></div>
                            <div class="action-title">Scout Students</div>
                            <div class="action-desc">Find talented students</div>
                        </a>
                        <a href="manage_applications.php" class="action-btn">
                            <div class="action-icon"><i class="bi bi-file-check"></i></div>
                            <div class="action-title">Review Applications</div>
                            <div class="action-desc">Process pending apps</div>
                        </a>
                        <a href="manage_interns.php" class="action-btn">
                            <div class="action-icon"><i class="bi bi-clock-history"></i></div>
                            <div class="action-title">Manage Interns</div>
                            <div class="action-desc">Track working hours</div>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Recent Company Posts -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-chat-dots"></i> Recent Posts
                    <button class="float-end btn-outline" onclick="openPostModal()" style="border: none; background: transparent; padding: 0.2rem 0.8rem;">+ Create Post</button>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_posts)): ?>
                        <div class="empty-state">
                            <i class="bi bi-chat-square-text"></i>
                            <p>No posts yet. Share updates with the community.</p>
                            <button class="btn-primary" onclick="openPostModal()" style="display:inline-block;">Create First Post</button>
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($recent_posts, 0, 3) as $post): ?>
                            <div class="post-card">
                                <div class="post-header">
                                    <div class="post-icon">
                                        <i class="bi bi-building"></i>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($company['company_name']); ?></strong>
                                        <span class="status-badge status-<?php echo $post['status']; ?> ms-2"><?php echo ucfirst($post['status']); ?></span>
                                    </div>
                                </div>
                                <div class="post-content">
                                    <?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 150))); ?>
                                    <?php if (strlen($post['content']) > 150): ?>...<?php endif; ?>
                                </div>
                                <div class="post-meta">
                                    <span><i class="bi bi-clock"></i> <?php echo $post['created_at'] instanceof DateTime ? $post['created_at']->format('M d, Y h:i A') : date('M d, Y h:i A', strtotime($post['created_at'])); ?></span>
                                    <span><i class="bi bi-hand-thumbs-up"></i> <?php echo $post['likes_count'] ?? 0; ?> likes</span>
                                    <span><i class="bi bi-chat"></i> <?php echo $post['comments_count'] ?? 0; ?> comments</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($recent_posts) > 3): ?>
                            <div class="text-center mt-3">
                                <a href="community.php" class="btn-outline" style="text-decoration:none;">View all posts →</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Post Modal -->
<div class="modal fade" id="postModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-megaphone me-2"></i>Create Post</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="create_post.php">
                <div class="modal-body">
                    <label class="form-label fw-semibold">What would you like to share?</label>
                    <textarea name="content" class="form-control" rows="5" placeholder="Share updates, job openings, announcements, or tips for students..." required></textarea>
                    <div class="text-muted small mt-2">
                        <i class="bi bi-info-circle"></i> Posts will be visible to all students and need admin approval.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_post" class="btn-primary">Publish Post</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openPostModal() {
    new bootstrap.Modal(document.getElementById('postModal')).show();
}

// Animate stats cards on scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -30px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

document.querySelectorAll('.stat-card, .card').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'all 0.5s ease-out';
    observer.observe(el);
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>