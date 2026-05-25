<?php
// admin/dashboard.php
require_once '../includes/config.php'; // Adjusted path if needed, assuming admin/ is a subfolder
require_once 'includes/admin_auth.php'; // Adjust path based on your folder structure

// ========== FIXED: Add error handling and counts queries ==========

// Get counts for students (check BOTH student and user status)
$counts = [
    'students' => ['pending' => 0, 'active' => 0, 'rejected' => 0, 'total' => 0],
    'companies' => ['pending' => 0, 'active' => 0, 'rejected' => 0, 'total' => 0],
    'posts' => ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total' => 0],
    'reports' => ['open' => 0, 'resolved' => 0, 'dismissed' => 0, 'total' => 0]
];

try {
    // Get student counts
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN s.status = 'pending' OR u.status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN s.status = 'active' AND u.status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN s.status = 'rejected' OR u.status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM students s
            JOIN users u ON s.user_id = u.id";
    $stmt = $conn->query($sql);
    if ($stmt) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $counts['students'] = [
                'pending' => (int)($row['pending'] ?? 0),
                'active' => (int)($row['active'] ?? 0),
                'rejected' => (int)($row['rejected'] ?? 0),
                'total' => (int)($row['total'] ?? 0)
            ];
        }
    }

    // Get counts for companies
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM companies";
    $stmt = $conn->query($sql);
    if ($stmt) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $counts['companies'] = [
                'pending' => (int)($row['pending'] ?? 0),
                'active' => (int)($row['active'] ?? 0),
                'rejected' => (int)($row['rejected'] ?? 0),
                'total' => (int)($row['total'] ?? 0)
            ];
        }
    }

    // Get counts for posts (pending moderation)
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM posts";
    $stmt = $conn->query($sql);
    if ($stmt) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $counts['posts'] = [
                'pending' => (int)($row['pending'] ?? 0),
                'approved' => (int)($row['approved'] ?? 0),
                'rejected' => (int)($row['rejected'] ?? 0),
                'total' => (int)($row['total'] ?? 0)
            ];
        }
    }

    // Get counts for reports (open reports)
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) as dismissed
            FROM reports";
    $stmt = $conn->query($sql);
    if ($stmt) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $counts['reports'] = [
                'open' => (int)($row['open'] ?? 0),
                'resolved' => (int)($row['resolved'] ?? 0),
                'dismissed' => (int)($row['dismissed'] ?? 0),
                'total' => (int)($row['total'] ?? 0)
            ];
        }
    }

    // Get recent activities
    $recent_activities = [];

    // Get recent student verifications (Changed TOP 5 to LIMIT 5)
    $sql = "SELECT 'student' as type, s.fullname, a.action_type, a.created_at 
            FROM admin_actions a
            JOIN students s ON a.target_id = s.id
            WHERE a.action_type IN ('approve_student', 'reject_student')
            ORDER BY a.created_at DESC LIMIT 5";
    $stmt = $conn->query($sql);
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $recent_activities[] = $row;
        }
    }

    // Get recent company verifications (Changed TOP 5 to LIMIT 5)
    $sql = "SELECT 'company' as type, c.company_name, a.action_type, a.created_at 
            FROM admin_actions a
            JOIN companies c ON a.target_id = c.id
            WHERE a.action_type IN ('approve_company', 'reject_company')
            ORDER BY a.created_at DESC LIMIT 5";
    $stmt = $conn->query($sql);
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $recent_activities[] = $row;
        }
    }

    // Get recent post actions (Changed TOP 5 to LIMIT 5)
    $sql = "SELECT 'post' as type, p.content, a.action_type, a.created_at 
            FROM admin_actions a
            JOIN posts p ON a.target_id = p.id
            WHERE a.action_type IN ('approve_post', 'remove_post', 'flag_post')
            ORDER BY a.created_at DESC LIMIT 5";
    $stmt = $conn->query($sql);
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $recent_activities[] = $row;
        }
    }

    // Sort activities by date
    usort($recent_activities, function($a, $b) {
        $date_a = strtotime($a['created_at']);
        $date_b = strtotime($b['created_at']);
        return $date_b - $date_a;
    });
    $recent_activities = array_slice($recent_activities, 0, 10);

} catch (PDOException $e) {
    // Handle error quietly or log it
    error_log("Dashboard DB Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - GreenBridge</title>
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
            --red: #e74c3c;
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
        
        /* Hero Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, var(--forest) 0%, var(--forest-mid) 100%);
            border-radius: var(--radius-lg);
            padding: 1.5rem 2rem;
            color: white;
            margin-bottom: 1.8rem;
            box-shadow: var(--shadow-md);
        }
        
        .welcome-section h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }
        
        .welcome-section p {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.7);
            margin: 0;
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
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            font-size: 1.3rem;
            margin: 0 auto 0.7rem;
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
        }
        
        .stat-trend {
            font-size: 0.7rem;
            font-weight: 600;
            margin-top: 0.3rem;
        }
        
        .trend-up { color: var(--mint); }
        .trend-warn { color: var(--gold); }
        
        /* Cards */
        .card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-border);
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
            border-bottom: 1px solid var(--gray-border);
            font-size: 0.9rem;
            color: var(--forest);
        }
        
        .card-header i {
            margin-right: 8px;
            color: var(--mint);
        }
        
        .card-body {
            padding: 1.2rem 1.5rem;
        }
        
        /* Activity List */
        .activity-item {
            display: flex;
            gap: 0.8rem;
            padding: 0.8rem 0;
            border-bottom: 1px solid var(--gray-border);
        }
        
        .activity-item:last-child { border-bottom: none; }
        
        .activity-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        
        .activity-content { flex: 1; }
        .activity-text { font-size: 0.85rem; font-weight: 500; color: var(--text-dark); }
        .activity-meta { font-size: 0.7rem; color: var(--text-muted); margin-top: 0.2rem; }
        
        /* Quick Actions */
        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            width: 100%;
            padding: 0.7rem 1rem;
            background: var(--gray-light);
            border: 1px solid var(--gray-border);
            border-radius: 12px;
            color: var(--text-dark);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 0.6rem;
        }
        
        .quick-action-btn:hover {
            background: var(--sage);
            transform: translateX(3px);
            border-color: var(--mint);
        }
        
        .quick-action-btn i { width: 24px; font-size: 1rem; color: var(--mint); }
        
        .badge-warning {
            background: #fef3cd;
            color: #856404;
            font-size: 0.65rem;
            padding: 0.2rem 0.6rem;
            border-radius: 30px;
            margin-left: auto;
        }
        
        .badge-danger {
            background: #fee9e7;
            color: #b13e3e;
            font-size: 0.65rem;
            padding: 0.2rem 0.6rem;
            border-radius: 30px;
            margin-left: auto;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
        }
        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            opacity: 0.4;
        }
        
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
        .stat-card, .card {
            animation: fadeIn 0.3s ease;
        }
    </style>
</head>
<body>

<div id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">G</div>
        <div class="logo-text">
            <span>Green Bridge</span>
            <small>Administrator Panel</small>
        </div>
    </div>
    
    <div class="sidebar-section">Overview</div>
    <a class="nav-link active" href="dashboard.php">
        <i class="bi bi-grid-1x2-fill"></i> Dashboard
    </a>
    <a class="nav-link" href="chat.php">
        <i class="bi bi-chat-fill"></i> Chat Portal
    </a>
    
    <div class="sidebar-section">Verification</div>
    <a class="nav-link" href="student_verification.php">
        <i class="bi bi-person-check-fill"></i> Student Verification
        <span class="badge-count"><?php echo $counts['students']['pending']; ?></span>
    </a>
    <a class="nav-link" href="company_verification.php">
        <i class="bi bi-building-check"></i> Company Verification
        <span class="badge-count"><?php echo $counts['companies']['pending']; ?></span>
    </a>
    
    <div class="sidebar-section">Content</div>
    <a class="nav-link" href="post_checking.php">
        <i class="bi bi-shield-exclamation"></i> Post Moderation
        <span class="badge-count"><?php echo $counts['posts']['pending']; ?></span>
    </a>
    <a class="nav-link" href="user_reports.php">
        <i class="bi bi-flag-fill"></i> User Reports
        <span class="badge-count"><?php echo $counts['reports']['open']; ?></span>
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
            <div class="user-avatar"><?php echo strtoupper(substr($admin['email'] ?? 'A', 0, 1)); ?></div>
            <div class="user-details">
                <span><?php echo htmlspecialchars($admin['email'] ?? 'Admin'); ?></span>
                <small>Administrator</small>
            </div>
        </div>
    </div>
</div>

<div id="topbar">
    <h5>Dashboard</h5>
    <div class="search-box ms-auto">
        <i class="bi bi-search"></i>
        <input type="text" placeholder="Search...">
    </div>
    <div class="icon-btn" onclick="window.location.href='post_checking.php'">
        <i class="bi bi-bell"></i>
    </div>
    <div class="icon-btn" onclick="window.location.href='analytics.php'">
        <i class="bi bi-bar-chart-line"></i>
    </div>
</div>

<div id="main">
    <div class="welcome-section">
        <h3>Welcome back, Administrator</h3>
        <p>Here's your platform overview and pending tasks for today.</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card" onclick="window.location.href='student_verification.php'">
            <div class="stat-icon" style="background: rgba(76,175,120,0.12); color: var(--mint);">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="stat-value"><?php echo $counts['students']['pending']; ?></div>
            <div class="stat-label">Pending Students</div>
            <div class="stat-trend trend-warn">Awaiting verification</div>
        </div>
        <div class="stat-card" onclick="window.location.href='company_verification.php'">
            <div class="stat-icon" style="background: rgba(52,152,219,0.12); color: #3498db;">
                <i class="bi bi-building"></i>
            </div>
            <div class="stat-value"><?php echo $counts['companies']['pending']; ?></div>
            <div class="stat-label">Pending Companies</div>
            <div class="stat-trend trend-warn">Awaiting verification</div>
        </div>
        <div class="stat-card" onclick="window.location.href='post_checking.php'">
            <div class="stat-icon" style="background: rgba(231,76,60,0.12); color: #e74c3c;">
                <i class="bi bi-shield-exclamation"></i>
            </div>
            <div class="stat-value"><?php echo $counts['posts']['pending']; ?></div>
            <div class="stat-label">Posts to Review</div>
            <div class="stat-trend trend-warn">Needs attention</div>
        </div>
        <div class="stat-card" onclick="window.location.href='user_reports.php'">
            <div class="stat-icon" style="background: rgba(155,89,182,0.12); color: #9b59b6;">
                <i class="bi bi-flag-fill"></i>
            </div>
            <div class="stat-value"><?php echo $counts['reports']['open']; ?></div>
            <div class="stat-label">Open Reports</div>
            <div class="stat-trend trend-warn">User-submitted</div>
        </div>
    </div>
    
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-activity"></i> Recent Activity
                </div>
                <div class="card-body">
                    <?php if (empty($recent_activities)): ?>
                        <div class="empty-state">
                            <i class="bi bi-clock-history"></i>
                            <p class="mt-2">No recent activity</p>
                            <small class="text-muted">Actions will appear here as you moderate content.</small>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon" style="background:<?php 
                                    if ($activity['action_type'] == 'approve_student' || $activity['action_type'] == 'approve_company') echo 'rgba(46,204,113,0.12); color:#27ae60';
                                    elseif ($activity['action_type'] == 'reject_student' || $activity['action_type'] == 'reject_company') echo 'rgba(231,76,60,0.12); color:#e74c3c';
                                    elseif ($activity['action_type'] == 'approve_post') echo 'rgba(46,204,113,0.12); color:#27ae60';
                                    elseif ($activity['action_type'] == 'remove_post') echo 'rgba(231,76,60,0.12); color:#e74c3c';
                                    else echo 'rgba(243,156,18,0.12); color:#f39c12';
                                ?>">
                                    <i class="bi <?php 
                                        if ($activity['action_type'] == 'approve_student' || $activity['action_type'] == 'approve_company') echo 'bi-check-circle-fill';
                                        elseif ($activity['action_type'] == 'reject_student' || $activity['action_type'] == 'reject_company') echo 'bi-x-circle-fill';
                                        elseif ($activity['action_type'] == 'approve_post') echo 'bi-check2-circle';
                                        elseif ($activity['action_type'] == 'remove_post') echo 'bi-trash-fill';
                                        else echo 'bi-flag-fill';
                                    ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-text">
                                        <?php
                                        if ($activity['type'] == 'student') {
                                            echo ucfirst(str_replace('_', ' ', $activity['action_type'])) . ' - ' . htmlspecialchars($activity['fullname']);
                                        } elseif ($activity['type'] == 'company') {
                                            echo ucfirst(str_replace('_', ' ', $activity['action_type'])) . ' - ' . htmlspecialchars($activity['company_name']);
                                        } elseif ($activity['type'] == 'post') {
                                            $action = $activity['action_type'] == 'approve_post' ? 'Approved' : ($activity['action_type'] == 'remove_post' ? 'Removed' : 'Flagged');
                                            echo $action . ' post - ' . htmlspecialchars(substr($activity['content'], 0, 50)) . (strlen($activity['content']) > 50 ? '...' : '');
                                        }
                                        ?>
                                    </div>
                                    <div class="activity-meta">
                                        <?php 
                                            $date = $activity['created_at'];
                                            echo date('M d, Y g:i A', strtotime($date));
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-lightning-charge"></i> Quick Actions
                </div>
                <div class="card-body">
                    <div class="quick-action-btn" onclick="window.location.href='student_verification.php'">
                        <i class="bi bi-person-check"></i> Review Student Applications
                        <?php if ($counts['students']['pending'] > 0): ?>
                            <span class="badge-warning"><?php echo $counts['students']['pending']; ?> pending</span>
                        <?php endif; ?>
                    </div>
                    <div class="quick-action-btn" onclick="window.location.href='company_verification.php'">
                        <i class="bi bi-building-check"></i> Review Company Applications
                        <?php if ($counts['companies']['pending'] > 0): ?>
                            <span class="badge-warning"><?php echo $counts['companies']['pending']; ?> pending</span>
                        <?php endif; ?>
                    </div>
                    <div class="quick-action-btn" onclick="window.location.href='post_checking.php'">
                        <i class="bi bi-shield-exclamation"></i> Moderate Community Posts
                        <?php if ($counts['posts']['pending'] > 0): ?>
                            <span class="badge-warning"><?php echo $counts['posts']['pending']; ?> pending</span>
                        <?php endif; ?>
                    </div>
                    <div class="quick-action-btn" onclick="window.location.href='user_reports.php'">
                        <i class="bi bi-flag"></i> Review User Reports
                        <?php if ($counts['reports']['open'] > 0): ?>
                            <span class="badge-danger"><?php echo $counts['reports']['open']; ?> open</span>
                        <?php endif; ?>
                    </div>
                    <div class="quick-action-btn" onclick="window.location.href='analytics.php'">
                        <i class="bi bi-bar-chart"></i> View Platform Analytics
                    </div>
                    <div class="quick-action-btn" onclick="window.location.href='settings.php'">
                        <i class="bi bi-gear"></i> Configure System Settings
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>