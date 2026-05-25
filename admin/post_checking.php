<?php
// admin/post_checking.php
require_once 'includes/admin_auth.php';

// 1. Handle post actions via POST
if (isset($_POST['action']) && isset($_POST['post_id'])) {
    $post_id = $_POST['post_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'approve') {
            $conn->prepare("UPDATE posts SET status = 'approved', updated_at = NOW() WHERE id = ?")->execute([$post_id]);
            $conn->prepare("INSERT INTO admin_actions (admin_id, action_type, target_type, target_id, created_at) VALUES (?, 'approve_post', 'post', ?, NOW())")->execute([$_SESSION['user_id'], $post_id]);
            $_SESSION['success'] = "Post approved successfully!";
            
        } elseif ($action === 'remove') {
            $conn->prepare("UPDATE posts SET status = 'removed', updated_at = NOW() WHERE id = ?")->execute([$post_id]);
            $conn->prepare("INSERT INTO admin_actions (admin_id, action_type, target_type, target_id, created_at) VALUES (?, 'remove_post', 'post', ?, NOW())")->execute([$_SESSION['user_id'], $post_id]);
            $_SESSION['success'] = "Post removed.";
            
        } elseif ($action === 'flag') {
            $reason = $_POST['reason'] ?? 'Violation of community guidelines';
            $conn->prepare("UPDATE posts SET flag = ?, updated_at = NOW() WHERE id = ?")->execute([$reason, $post_id]);
            $conn->prepare("INSERT INTO admin_actions (admin_id, action_type, target_type, target_id, details, created_at) VALUES (?, 'flag_post', 'post', ?, ?, NOW())")->execute([$_SESSION['user_id'], $post_id, $reason]);
            $_SESSION['success'] = "Post flagged.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
    
    header("Location: post_checking.php");
    exit();
}

// 2. Fetch Statistics
$stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'removed' => 0, 'flagged' => 0];
try {
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'removed' THEN 1 ELSE 0 END) as removed,
                SUM(CASE WHEN flag IS NOT NULL THEN 1 ELSE 0 END) as flagged
            FROM posts";
    $stmt = $conn->query($sql);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats = [
            'total' => (int)($row['total'] ?? 0),
            'pending' => (int)($row['pending'] ?? 0),
            'approved' => (int)($row['approved'] ?? 0),
            'removed' => (int)($row['removed'] ?? 0),
            'flagged' => (int)($row['flagged'] ?? 0)
        ];
    }
} catch (PDOException $e) {
    error_log("Stats error: " . $e->getMessage());
}

// 3. Fetch Posts List
$posts = [];
try {
    $sql = "SELECT p.*, 
                   CASE WHEN u.user_type = 'student' THEN s.fullname 
                        WHEN u.user_type = 'company' THEN c.company_name 
                        ELSE u.email END as author_name,
                   u.user_type as author_type,
                   (SELECT COUNT(*) FROM reports r WHERE r.post_id = p.id) as reports_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN students s ON u.id = s.user_id AND u.user_type = 'student'
            LEFT JOIN companies c ON u.id = c.user_id AND u.user_type = 'company'
            ORDER BY p.created_at DESC";
    $stmt = $conn->query($sql);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Posts fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Moderation - GreenBridge Admin</title>
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
        .serif, h1, h2, h3, .playfair {
            font-family: 'Playfair Display', serif;
        }
        
        /* Sidebar */
        #sidebar {
            width: 280px;
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: var(--forest);
            color: white;
            z-index: 100;
            box-shadow: 2px 0 12px rgba(0,0,0,0.08);
        }
        
        .sidebar-logo {
            padding: 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .logo-icon {
            width: 42px;
            height: 42px;
            background: var(--mint);
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--forest);
        }
        
        .logo-text span {
            display: block;
            font-size: 1.1rem;
            font-weight: 800;
            color: white;
            font-family: 'Playfair Display', serif;
        }
        
        .logo-text small {
            font-size: 0.65rem;
            color: rgba(255,255,255,0.45);
            letter-spacing: 0.5px;
        }
        
        .sidebar-section {
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.35);
            padding: 1.2rem 1.5rem 0.4rem 1.5rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.7rem 1.5rem;
            color: rgba(255,255,255,0.65);
            font-size: 0.85rem;
            font-weight: 500;
            border-left: 3px solid transparent;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .nav-link i { font-size: 1.1rem; width: 24px; }
        .nav-link:hover { color: white; background: rgba(255,255,255,0.06); }
        .nav-link.active {
            color: var(--mint);
            border-left-color: var(--mint);
            background: rgba(76,175,120,0.1);
            font-weight: 600;
        }
        
        .badge-count {
            margin-left: auto;
            background: var(--mint);
            color: var(--forest);
            font-size: 0.6rem;
            font-weight: 800;
            border-radius: 30px;
            padding: 0.15rem 0.55rem;
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 1.2rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--mint);
            display: grid;
            place-items: center;
            font-weight: 700;
            color: var(--forest);
        }
        
        .user-details span {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
        }
        
        .user-details small {
            font-size: 0.65rem;
            color: rgba(255,255,255,0.45);
        }
        
        /* Topbar */
        #topbar {
            position: fixed;
            top: 0;
            left: 280px;
            right: 0;
            height: 64px;
            background: var(--white);
            border-bottom: 1px solid var(--gray-border);
            display: flex;
            align-items: center;
            padding: 0 1.8rem;
            gap: 1rem;
            z-index: 90;
            box-shadow: var(--shadow-sm);
        }
        
        #topbar h5 {
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
            color: var(--forest);
            font-family: 'Playfair Display', serif;
        }
        
        .search-box {
            position: relative;
            margin-left: auto;
        }
        
        .search-box input {
            padding-left: 2.2rem;
            border: 1px solid var(--gray-border);
            border-radius: 40px;
            font-size: 0.8rem;
            width: 240px;
            height: 38px;
            background: var(--gray-light);
            outline: none;
            transition: all 0.2s;
        }
        
        .search-box input:focus {
            border-color: var(--mint);
            width: 280px;
            background: white;
            box-shadow: 0 0 0 2px rgba(76,175,120,0.1);
        }
        
        .search-box i {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        
        .icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--gray-light);
            border: 1px solid var(--gray-border);
            display: grid;
            place-items: center;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .icon-btn:hover { background: var(--sage); border-color: var(--mint); color: var(--forest); }
        
        /* Main Content */
        #main {
            margin-left: 280px;
            margin-top: 64px;
            padding: 1.8rem;
        }
        
        .page-title {
            font-size: 1.6rem;
            font-weight: 800;
            font-family: 'Playfair Display', serif;
            margin-bottom: 0.2rem;
            color: var(--forest);
        }
        
        .page-subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }
        
        /* Stat Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.8rem;
        }
        
        .stat-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 1.2rem;
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
        .stat-value { font-size: 1.8rem; font-weight: 800; font-family: 'Playfair Display', serif; color: var(--forest); }
        .stat-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; margin-top: 4px; }
        
        /* Filter Row */
        .filter-row {
            display: flex;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-search {
            position: relative;
        }
        
        .filter-search input {
            padding-left: 2rem;
            border: 1px solid var(--gray-border);
            border-radius: 40px;
            font-size: 0.8rem;
            height: 40px;
            width: 220px;
            background: var(--gray-light);
            transition: all 0.2s;
        }
        
        .filter-search input:focus {
            outline: none;
            border-color: var(--mint);
            background: white;
            width: 250px;
        }
        
        .filter-search i {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        
        .filter-select {
            border: 1px solid var(--gray-border);
            border-radius: 40px;
            font-size: 0.8rem;
            height: 40px;
            padding: 0 1rem;
            background: var(--gray-light);
            color: var(--text-dark);
            outline: none;
            cursor: pointer;
        }
        
        .filter-select:focus { border-color: var(--mint); }
        
        /* Post Cards */
        .post-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-border);
            padding: 1.3rem;
            margin-bottom: 1rem;
            transition: all 0.25s ease;
            box-shadow: var(--shadow-sm);
        }
        
        .post-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
        
        .post-header {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 0.8rem;
        }
        
        .post-avatar {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--sage);
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 1rem;
            color: var(--forest);
        }
        
        .post-author {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--forest);
        }
        
        .post-meta {
            font-size: 0.7rem;
            color: var(--text-muted);
        }
        
        .post-badge {
            font-size: 0.65rem;
            padding: 0.2rem 0.7rem;
            border-radius: 30px;
            margin-left: 0.5rem;
        }
        
        .badge-student { background: #e8f0fe; color: #1e40af; }
        .badge-company { background: #fef3cd; color: #856404; }
        
        .post-content {
            font-size: 0.85rem;
            line-height: 1.6;
            margin: 0.8rem 0;
            padding: 1rem;
            background: var(--gray-light);
            border-radius: 12px;
            color: var(--text-dark);
        }
        
        .flag-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: #fce7f3;
            color: #9d174d;
            border-radius: 30px;
            padding: 0.2rem 0.7rem;
            font-size: 0.65rem;
            font-weight: 600;
        }
        
        /* Badges */
        .badge-status {
            display: inline-flex;
            align-items: center;
            font-size: 0.65rem;
            font-weight: 600;
            border-radius: 30px;
            padding: 0.2rem 0.8rem;
        }
        
        .badge-pending { background: #fef3cd; color: #856404; }
        .badge-approved { background: #e6f7ef; color: #1e6f3f; }
        .badge-removed { background: #fee9e7; color: #b13e3e; }
        
        /* Action Buttons */
        .action-btn {
            border: none;
            border-radius: 30px;
            padding: 0.4rem 0.9rem;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        
        .btn-approve { background: #e6f7ef; color: #1e6f3f; }
        .btn-approve:hover { background: #c8e9da; transform: translateY(-1px); }
        .btn-remove { background: #fee9e7; color: #b13e3e; }
        .btn-remove:hover { background: #fcd6d2; transform: translateY(-1px); }
        .btn-flag { background: #fef3cd; color: #856404; }
        .btn-flag:hover { background: #fde68a; transform: translateY(-1px); }
        .btn-view { background: var(--gray-light); color: var(--forest); border: 1px solid var(--gray-border); }
        .btn-view:hover { background: var(--sage); transform: translateY(-1px); }
        
        .action-group {
            display: flex;
            gap: 0.6rem;
            justify-content: flex-end;
            margin-top: 0.8rem;
            flex-wrap: wrap;
        }
        
        /* Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .modal-overlay.open { display: flex; }
        
        .modal-content {
            background: var(--white);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 580px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
        }
        
        .modal-header {
            background: var(--forest);
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }
        
        .modal-header h6 { margin: 0; font-size: 1rem; font-weight: 700; }
        
        .modal-close {
            background: none;
            border: none;
            color: rgba(255,255,255,0.6);
            font-size: 1.2rem;
            cursor: pointer;
        }
        
        .modal-close:hover { color: white; }
        
        .modal-body { padding: 1.5rem; }
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--gray-border);
            display: flex;
            justify-content: flex-end;
            gap: 0.8rem;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .detail-label {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 0.2rem;
        }
        
        .detail-value { font-weight: 600; font-size: 0.9rem; color: var(--text-dark); }
        
        .form-select {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1px solid var(--gray-border);
            border-radius: 10px;
            font-size: 0.85rem;
            background: white;
        }
        
        .form-select:focus {
            outline: none;
            border-color: var(--mint);
        }
        
        /* Alert */
        .alert-success {
            background: #e6f7ef;
            color: #1e6f3f;
            border-radius: 12px;
            padding: 0.9rem 1.2rem;
            margin-bottom: 1.2rem;
            border-left: 4px solid var(--mint);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: var(--white);
            border-radius: var(--radius-lg);
            color: var(--text-muted);
        }
        
        .empty-state i { font-size: 3rem; margin-bottom: 0.75rem; opacity: 0.4; }
        
        @media (max-width: 992px) {
            #sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            #topbar, #main { left: 0; margin-left: 0; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        
        /* Animation */
        .fade-in {
            animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px);}
            to { opacity: 1; transform: translateY(0);}
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">G</div>
        <div class="logo-text">
            <span>Green Bridge</span>
            <small>Administrator Panel</small>
        </div>
    </div>
    
    <div class="sidebar-section">Overview</div>
    <a class="nav-link" href="dashboard.php">
        <i class="bi bi-grid-1x2-fill"></i> Dashboard
    </a>
    <a class="nav-link" href="chat.php">
        <i class="bi bi-chat-fill"></i> Chat Portal
    </a>
    
    <div class="sidebar-section">Verification</div>
    <a class="nav-link" href="student_verification.php">
        <i class="bi bi-person-check-fill"></i> Student Verification
    </a>
    <a class="nav-link" href="company_verification.php">
        <i class="bi bi-building-check"></i> Company Verification
    </a>
    
    <div class="sidebar-section">Content</div>
    <a class="nav-link active" href="post_checking.php">
        <i class="bi bi-shield-exclamation"></i> Post Moderation
        <span class="badge-count"><?php echo $stats['pending']; ?></span>
    </a>
    <a class="nav-link" href="user_reports.php">
        <i class="bi bi-flag-fill"></i> User Reports
    </a>
    
    <div class="sidebar-section">Analytics</div>
    <a class="nav-link" href="analytics.php">
        <i class="bi bi-bar-chart-fill"></i> Reports & Analytics
    </a>
    
    <div class="sidebar-section">System</div>
    <a class="nav-link" href="settings.php">
        <i class="bi bi-gear-fill"></i> System Settings
    </a>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?php echo strtoupper(substr($admin['email'], 0, 1)); ?></div>
            <div class="user-details">
                <span><?php echo htmlspecialchars($admin['email']); ?></span>
                <small>Administrator</small>
            </div>
        </div>
    </div>
</div>

<!-- Topbar -->
<div id="topbar">
    <h5>Post Moderation</h5>
    <div class="search-box ms-auto">
        <i class="bi bi-search"></i>
        <input type="text" id="search-input" placeholder="Search...">
    </div>
    <div class="icon-btn" onclick="window.location.href='user_reports.php'">
        <i class="bi bi-bell"></i>
    </div>
    <div class="icon-btn" onclick="window.location.href='analytics.php'">
        <i class="bi bi-bar-chart-line"></i>
    </div>
</div>

<!-- Main Content -->
<div id="main">
    <div class="page-title">Post Moderation</div>
    <div class="page-subtitle">Review and moderate user posts. Approve appropriate content, remove violations, and flag concerning posts.</div>
    
    <!-- Success Message -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-success">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card" onclick="filterByStatus('pending')">
            <div class="stat-value"><?php echo $stats['pending']; ?></div>
            <div class="stat-label">Pending Review</div>
        </div>
        <div class="stat-card" onclick="filterByStatus('approved')">
            <div class="stat-value"><?php echo $stats['approved']; ?></div>
            <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card" onclick="filterByStatus('removed')">
            <div class="stat-value"><?php echo $stats['removed']; ?></div>
            <div class="stat-label">Removed</div>
        </div>
        <div class="stat-card" onclick="filterByStatus('flagged')">
            <div class="stat-value"><?php echo $stats['flagged']; ?></div>
            <div class="stat-label">Flagged</div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filter-row">
        <div class="filter-search">
            <i class="bi bi-search"></i>
            <input type="text" id="search" placeholder="Search by author or content...">
        </div>
        <select class="filter-select" id="type-filter">
            <option value="all">All Types</option>
            <option value="student">Student Posts</option>
            <option value="company">Company Posts</option>
        </select>
        <select class="filter-select" id="status-filter">
            <option value="all">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="removed">Removed</option>
        </select>
        <select class="filter-select" id="flag-filter">
            <option value="all">All Flags</option>
            <option value="inappropriate">Inappropriate Language</option>
            <option value="spam">Spam</option>
            <option value="harassment">Harassment</option>
            <option value="misinformation">Misinformation</option>
        </select>
        <span class="ms-auto" style="font-size:0.8rem; color:var(--text-muted)" id="post-count">
            <?php echo count($posts); ?> posts
        </span>
    </div>
    
    <!-- Posts List -->
    <div id="posts-container">
        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <i class="bi bi-chat-dots"></i>
                <p class="mt-2">No posts found.</p>
                <small class="text-muted">Posts from students and companies will appear here.</small>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="post-card" data-type="<?php echo $post['author_type']; ?>" 
                     data-status="<?php echo $post['status']; ?>"
                     data-flag="<?php echo $post['flag']; ?>"
                     data-search="<?php echo strtolower($post['author_name'] . ' ' . $post['content']); ?>">
                    
                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                        <div class="post-header">
                            <div class="post-avatar"><?php echo strtoupper(substr($post['author_name'], 0, 1)); ?></div>
                            <div>
                                <span class="post-author"><?php echo htmlspecialchars($post['author_name']); ?></span>
                                <span class="post-badge <?php echo $post['author_type'] === 'student' ? 'badge-student' : 'badge-company'; ?>">
                                    <?php echo $post['author_type'] === 'student' ? 'Student' : 'Company'; ?>
                                </span>
                                <div class="post-meta">
                                    <?php 
                                        $date = $post['created_at'];
                                        if ($date instanceof DateTime) {
                                            echo $date->format('M d, Y g:i A');
                                        } else {
                                            echo date('M d, Y g:i A', strtotime($date));
                                        }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <?php if ($post['flag']): ?>
                                <span class="flag-chip"><i class="bi bi-flag-fill"></i> <?php echo ucfirst($post['flag']); ?></span>
                            <?php endif; ?>
                            <span class="badge-status badge-<?php echo $post['status']; ?>">
                                <?php echo ucfirst($post['status']); ?>
                            </span>
                            <?php if ($post['reports_count'] > 0): ?>
                                <span class="badge-status" style="background:#fee9e7; color:#b13e3e;">
                                    <i class="bi bi-exclamation-triangle"></i> <?php echo $post['reports_count']; ?> reports
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="post-content">
                        <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                    </div>
                    
                    <div class="action-group">
                        <button class="action-btn btn-view" onclick="viewPost(<?php echo $post['id']; ?>)">
                            <i class="bi bi-eye"></i> Details
                        </button>
                        <?php if ($post['status'] === 'pending' || $post['status'] === 'approved' || $post['status'] === 'removed'): ?>
                            <?php if ($post['status'] === 'pending' || $post['status'] === 'removed'): ?>
                                <button class="action-btn btn-approve" onclick="approvePost(<?php echo $post['id']; ?>)">
                                    <i class="bi <?php echo $post['status'] === 'removed' ? 'bi-arrow-counterclockwise' : 'bi-check-lg'; ?>"></i> <?php echo $post['status'] === 'removed' ? 'Restore' : 'Approve'; ?>
                                </button>
                            <?php endif; ?>
                            <?php if ($post['status'] === 'pending' || $post['status'] === 'approved'): ?>
                                <button class="action-btn btn-remove" onclick="removePost(<?php echo $post['id']; ?>)">
                                    <i class="bi bi-trash"></i> Remove
                                </button>
                                <?php if (!$post['flag']): ?>
                                    <button class="action-btn btn-flag" onclick="openFlagModal(<?php echo $post['id']; ?>)">
                                        <i class="bi bi-flag"></i> Flag
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- View Post Modal -->
<div class="modal-overlay" id="view-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h6><i class="bi bi-file-text me-2"></i>Post Details</h6>
            <button class="modal-close" onclick="closeModal('view-modal')">&times;</button>
        </div>
        <div class="modal-body" id="view-modal-body"></div>
        <div class="modal-footer">
            <button class="action-btn btn-view" onclick="closeModal('view-modal')">Close</button>
        </div>
    </div>
</div>

<!-- Flag Post Modal -->
<div class="modal-overlay" id="flag-modal">
    <div class="modal-content">
        <div class="modal-header" style="background:#856404;">
            <h6><i class="bi bi-flag me-2"></i>Flag Post</h6>
            <button class="modal-close" onclick="closeModal('flag-modal')">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <p>Select the reason for flagging this post:</p>
                <div class="mb-3">
                    <select name="flag_reason" class="form-select" required>
                        <option value="">Select a reason</option>
                        <option value="inappropriate">Inappropriate Language</option>
                        <option value="spam">Spam / Promotional</option>
                        <option value="harassment">Harassment / Bullying</option>
                        <option value="misinformation">Misinformation</option>
                        <option value="offensive">Offensive Content</option>
                    </select>
                </div>
                <input type="hidden" name="post_id" id="flag-post-id">
                <input type="hidden" name="action" value="flag">
            </div>
            <div class="modal-footer">
                <button type="button" class="action-btn btn-view" onclick="closeModal('flag-modal')">Cancel</button>
                <button type="submit" class="action-btn btn-flag">Flag Post</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Post data for view modal
const postData = <?php 
    $simple_data = [];
    foreach ($posts as $p) {
        $simple_data[$p['id']] = [
            'author_name' => $p['author_name'],
            'author_type' => $p['author_type'],
            'content' => $p['content'],
            'created_at' => $p['created_at'] instanceof DateTime ? $p['created_at']->format('M d, Y g:i A') : date('M d, Y g:i A', strtotime($p['created_at'])),
            'status' => $p['status'],
            'flag' => $p['flag'],
            'reports_count' => $p['reports_count']
        ];
    }
    echo json_encode($simple_data); 
?>;

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// View post details
function viewPost(id) {
    const p = postData[id];
    if (!p) return;
    
    const statusDisplay = p.status.charAt(0).toUpperCase() + p.status.slice(1);
    let statusClass = '';
    if (p.status === 'pending') statusClass = 'badge-pending';
    else if (p.status === 'approved') statusClass = 'badge-approved';
    else statusClass = 'badge-removed';
    
    document.getElementById('view-modal-body').innerHTML = `
        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="post-avatar" style="width:50px; height:50px; font-size:1.2rem">
                ${escapeHtml(p.author_name.charAt(0))}
            </div>
            <div>
                <div style="font-weight:700; font-size:1rem">${escapeHtml(p.author_name)}</div>
                <div style="font-size:0.8rem; color:var(--text-muted)">${escapeHtml(p.created_at)}</div>
                <div>
                    <span class="post-badge ${p.author_type === 'student' ? 'badge-student' : 'badge-company'}">
                        ${p.author_type === 'student' ? 'Student' : 'Company'}
                    </span>
                </div>
            </div>
        </div>
        
        <div class="detail-grid">
            <div>
                <div class="detail-label">Status</div>
                <div class="detail-value">
                    <span class="badge-status ${statusClass}">${statusDisplay}</span>
                </div>
            </div>
            <div>
                <div class="detail-label">Flag</div>
                <div class="detail-value">${p.flag ? `<span class="flag-chip">${escapeHtml(p.flag)}</span>` : 'None'}</div>
            </div>
            <div>
                <div class="detail-label">Reports</div>
                <div class="detail-value">${p.reports_count}</div>
            </div>
        </div>
        
        <div class="mt-3">
            <div class="detail-label mb-2">Content</div>
            <div style="background:var(--gray-light); padding:1rem; border-radius:12px; font-size:0.85rem; line-height:1.6">
                ${escapeHtml(p.content).replace(/\n/g, '<br>')}
            </div>
        </div>
    `;
    openModal('view-modal');
}

// Approve post
function approvePost(id) {
    if (confirm('Are you sure you want to approve this post?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="post_id" value="${id}">
            <input type="hidden" name="action" value="approve">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Remove post
function removePost(id) {
    if (confirm('Are you sure you want to remove this post? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="post_id" value="${id}">
            <input type="hidden" name="action" value="remove">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function filterByStatus(status) {
    const statusFilter = document.getElementById('status-filter');
    statusFilter.value = status;
    filterPosts();
}

// Open flag modal
function openFlagModal(id) {
    document.getElementById('flag-post-id').value = id;
    openModal('flag-modal');
}

// Modal functions
function openModal(id) {
    document.getElementById(id).classList.add('open');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

// Close modals when clicking overlay
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            overlay.classList.remove('open');
        }
    });
});

// Filter functionality
function filterPosts() {
    const search = document.getElementById('search').value.toLowerCase();
    const type = document.getElementById('type-filter').value;
    const status = document.getElementById('status-filter').value;
    const flag = document.getElementById('flag-filter').value;
    
    const posts = document.querySelectorAll('.post-card');
    let visibleCount = 0;
    
    posts.forEach(post => {
        const postType = post.dataset.type;
        const postStatus = post.dataset.status;
        const postFlag = post.dataset.flag || '';
        const searchText = post.dataset.search || '';
        
        let show = true;
        if (type !== 'all' && postType !== type) show = false;
        if (status !== 'all' && postStatus !== status) show = false;
        if (flag !== 'all' && !postFlag.includes(flag)) show = false;
        if (search && !searchText.includes(search)) show = false;
        
        post.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });
    
    document.getElementById('post-count').textContent = visibleCount + ' posts';
}

document.getElementById('search').addEventListener('input', filterPosts);
document.getElementById('type-filter').addEventListener('change', filterPosts);
document.getElementById('status-filter').addEventListener('change', filterPosts);
document.getElementById('flag-filter').addEventListener('change', filterPosts);
</script>
</body>
</html>