<?php
// student/applications.php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

requireStudent();

$user_id = $_SESSION['user_id'];
$student = getStudentData($conn, $user_id);

$applications = [];
try {
    $sql = "SELECT a.*, i.title, i.type, i.location, i.department, c.company_name
            FROM applications a
            JOIN internships i ON a.internship_id = i.id
            JOIN companies c ON i.company_id = c.id
            WHERE a.student_id = (SELECT id FROM students WHERE user_id = ?)
            ORDER BY a.applied_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

$filter = $_GET['status'] ?? 'all';
$counts = ['pending' => 0, 'accepted' => 0, 'rejected' => 0];
foreach ($applications as $a) { if (isset($counts[$a['status']])) $counts[$a['status']]++; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - GreenBridge</title>
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
            max-width: 1100px;
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
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.8rem;
            flex-wrap: wrap;
        }
        .tab {
            padding: 0.5rem 1.5rem;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 600;
            background: var(--white);
            color: var(--forest);
            cursor: pointer;
            border: 1px solid var(--gray-border);
            transition: all 0.2s;
        }
        .tab:hover {
            background: var(--sage);
            border-color: var(--mint);
        }
        .tab.active {
            background: var(--forest);
            color: white;
            border-color: var(--forest);
        }
        .tab-count {
            background: rgba(0,0,0,0.1);
            border-radius: 30px;
            padding: 0.1rem 0.5rem;
            font-size: 0.7rem;
            margin-left: 0.5rem;
        }
        .tab.active .tab-count {
            background: rgba(255,255,255,0.2);
        }
        
        /* Application Cards */
        .app-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-border);
            padding: 1.25rem;
            margin-bottom: 1rem;
            display: flex;
            gap: 1.25rem;
            transition: all 0.25s ease;
            box-shadow: var(--shadow-sm);
        }
        .app-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--sage-dk);
        }
        .app-logo {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--sage) 0%, var(--sage-dk) 100%);
            border-radius: 14px;
            display: grid;
            place-items: center;
            font-size: 1.3rem;
            flex-shrink: 0;
            color: var(--forest);
        }
        .app-info {
            flex: 1;
        }
        .app-title {
            font-weight: 800;
            font-size: 0.95rem;
            margin-bottom: 0.2rem;
            color: var(--forest);
        }
        .app-company {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .app-tags {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
        }
        .app-tag {
            font-size: 0.65rem;
            padding: 0.2rem 0.7rem;
            background: var(--sage);
            border-radius: 30px;
            color: var(--forest-mid);
            font-weight: 500;
        }
        .app-status {
            text-align: right;
            min-width: 110px;
        }
        .status-badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.25rem 1rem;
            border-radius: 40px;
            display: inline-block;
            letter-spacing: 0.3px;
        }
        .status-pending { background: #fef3cd; color: #856404; }
        .status-accepted { background: #e6f7ef; color: #1e6f3f; }
        .status-rejected { background: #fee9e7; color: #b13e3e; }
        .app-date {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 0.4rem;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: var(--white);
            border-radius: var(--radius-lg);
            color: var(--text-muted);
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 0.75rem;
            opacity: 0.4;
            color: var(--sage-dk);
        }
        .btn-outline-success-custom {
            background: transparent;
            border: 1px solid var(--forest);
            color: var(--forest);
            padding: 0.5rem 1.2rem;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        .btn-outline-success-custom:hover {
            background: var(--forest);
            color: white;
        }
        
        @media (max-width: 768px) {
            .main-container { padding: 1rem; }
            .app-card { flex-direction: column; text-align: center; gap: 0.75rem; }
            .app-status { text-align: center; }
            .app-logo { margin: 0 auto; }
            .page-title { font-size: 1.5rem; }
            .subnav { padding: 0 12px; }
            .tabs { justify-content: center; }
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
    <div class="subnav-item active">Applications</div>
    <div class="subnav-item" onclick="location.href='performance.php'">Performance</div>
    <div class="subnav-item" onclick="location.href='dtr.php'">DTR</div>
    <div class="subnav-item" onclick="location.href='logbook.php'">Logbook</div>
    <div class="subnav-item" onclick="location.href='profile.php'">Profile</div>
</div>

<div class="main-container">
    <div class="page-header">
        <div class="page-title">My Applications</div>
        <div class="page-subtitle">Track the status of all your internship and OJT applications</div>
    </div>
    
    <div class="tabs">
        <div class="tab <?php echo $filter == 'all' ? 'active' : ''; ?>" onclick="location.href='?status=all'">
            All <span class="tab-count"><?php echo count($applications); ?></span>
        </div>
        <div class="tab <?php echo $filter == 'pending' ? 'active' : ''; ?>" onclick="location.href='?status=pending'">
            Pending <span class="tab-count"><?php echo $counts['pending']; ?></span>
        </div>
        <div class="tab <?php echo $filter == 'accepted' ? 'active' : ''; ?>" onclick="location.href='?status=accepted'">
            Accepted <span class="tab-count"><?php echo $counts['accepted']; ?></span>
        </div>
        <div class="tab <?php echo $filter == 'rejected' ? 'active' : ''; ?>" onclick="location.href='?status=rejected'">
            Rejected <span class="tab-count"><?php echo $counts['rejected']; ?></span>
        </div>
    </div>
    
    <?php if (empty($applications)): ?>
        <div class="empty-state">
            <i class="bi bi-briefcase"></i>
            <p class="mt-2">No applications submitted yet.</p>
            <p class="small text-muted">Start exploring opportunities to begin your internship journey.</p>
            <a href="opportunities.php" class="btn-outline-success-custom mt-3">Browse Opportunities</a>
        </div>
    <?php else: ?>
        <?php 
        $displayed = 0;
        foreach ($applications as $app): 
            if ($filter != 'all' && $app['status'] != $filter) continue;
            $displayed++;
        ?>
        <div class="app-card">
            <div class="app-logo">
                <i class="bi bi-building"></i>
            </div>
            <div class="app-info">
                <div class="app-title"><?php echo htmlspecialchars($app['title']); ?></div>
                <div class="app-company">
                    <i class="bi bi-building"></i> <?php echo htmlspecialchars($app['company_name']); ?>
                </div>
                <div class="app-tags">
                    <span class="app-tag"><?php echo htmlspecialchars($app['type']); ?></span>
                    <span class="app-tag"><?php echo htmlspecialchars($app['location']); ?></span>
                    <span class="app-tag"><?php echo htmlspecialchars($app['department']); ?></span>
                </div>
            </div>
            <div class="app-status">
                <span class="status-badge status-<?php echo $app['status']; ?>">
                    <?php echo ucfirst($app['status']); ?>
                </span>
                <div class="app-date">
                    <i class="bi bi-calendar"></i> 
                    <?php echo $app['applied_at'] instanceof DateTime ? $app['applied_at']->format('M d, Y') : date('M d, Y', strtotime($app['applied_at'])); ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if ($displayed == 0 && $filter != 'all'): ?>
            <div class="empty-state">
                <i class="bi bi-folder"></i>
                <p class="mt-2">No <?php echo $filter; ?> applications found.</p>
                <a href="?status=all" class="btn-outline-success-custom mt-3">View All Applications</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>