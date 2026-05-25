<?php
// admin/analytics.php
require_once 'includes/admin_auth.php';

// Initialize defaults
$studentStats = ['total' => 0, 'pending' => 0, 'active' => 0, 'rejected' => 0];
$companyStats = ['total' => 0, 'pending' => 0, 'active' => 0, 'rejected' => 0];
$postStats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'removed' => 0];
$reportStats = ['total' => 0, 'open' => 0, 'investigating' => 0, 'resolved' => 0, 'dismissed' => 0];
$recent_actions = [];

try {
    // Get student statistics
    $sql = "SELECT COUNT(*) as total,
                   SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                   SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                   SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM students";
    $stmt = $conn->query($sql);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $studentStats = $row;

    // Get company statistics
    $sql = "SELECT COUNT(*) as total,
                   SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                   SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                   SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM companies";
    $stmt = $conn->query($sql);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $companyStats = $row;

    // Get post statistics
    $sql = "SELECT COUNT(*) as total,
                   SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                   SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                   SUM(CASE WHEN status = 'removed' THEN 1 ELSE 0 END) as removed
            FROM posts";
    $stmt = $conn->query($sql);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $postStats = $row;

    // Get report statistics
    $sql = "SELECT COUNT(*) as total,
                   SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
                   SUM(CASE WHEN status = 'investigating' THEN 1 ELSE 0 END) as investigating,
                   SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                   SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) as dismissed
            FROM reports";
    $stmt = $conn->query($sql);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $reportStats = $row;

    // Get recent actions (Translated from TOP 10 to LIMIT 10)
    $sql = "SELECT a.*, u.email as admin_email 
            FROM admin_actions a
            JOIN users u ON a.admin_id = u.id
            ORDER BY a.created_at DESC LIMIT 10";
    $stmt = $conn->query($sql);
    $recent_actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Analytics DB Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - GreenBridge Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Playfair+Display:wght@500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        /* Chart Cards */
        .chart-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-border);
            padding: 1.2rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: box-shadow 0.2s;
        }
        
        .chart-card:hover {
            box-shadow: var(--shadow-md);
        }
        
        .chart-card h6 {
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--forest);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .chart-card h6 i {
            color: var(--mint);
        }
        
        canvas {
            max-height: 280px;
        }
        
        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.7rem 0;
            border-bottom: 1px solid var(--gray-border);
        }
        
        .list-item:last-child { border-bottom: none; }
        
        .list-name { font-weight: 500; color: var(--text-dark); }
        .list-count { 
            background: var(--sage);
            padding: 0.2rem 0.8rem;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--forest);
        }
        
        .empty-list {
            text-align: center;
            padding: 1.5rem;
            color: var(--text-muted);
            font-size: 0.8rem;
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
    <a class="nav-link" href="post_checking.php">
        <i class="bi bi-shield-exclamation"></i> Post Moderation
    </a>
    <a class="nav-link" href="user_reports.php">
        <i class="bi bi-flag-fill"></i> User Reports
    </a>
    
    <div class="sidebar-section">Analytics</div>
    <a class="nav-link active" href="analytics.php">
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
    <h5>Reports & Analytics</h5>
    <div class="search-box ms-auto">
        <i class="bi bi-search"></i>
        <input type="text" placeholder="Search reports...">
    </div>
    <div class="icon-btn" onclick="window.location.href='settings.php'">
        <i class="bi bi-gear"></i>
    </div>
</div>

<!-- Main Content -->
<div id="main">
    <div class="page-title">Reports & Analytics</div>
    <div class="page-subtitle">Platform-wide insights on user activity, verifications, and content moderation</div>
    
    <!-- Overview Cards -->
    <div class="stats-grid">
        <div class="stat-card" onclick="window.location.href='student_verification.php'">
            <div class="stat-value"><?php echo ($studentStats['total'] ?? 0) + ($companyStats['total'] ?? 0); ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card" onclick="window.location.href='post_checking.php'">
            <div class="stat-value"><?php echo $postStats['total'] ?? 0; ?></div>
            <div class="stat-label">Total Posts</div>
        </div>
        <div class="stat-card" onclick="window.location.href='user_reports.php'">
            <div class="stat-value"><?php echo $reportStats['total'] ?? 0; ?></div>
            <div class="stat-label">Total Reports</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo ($studentStats['active'] ?? 0) + ($companyStats['active'] ?? 0); ?></div>
            <div class="stat-label">Active Users</div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="chart-card">
                <h6><i class="bi bi-pie-chart"></i> Student Verification Status</h6>
                <canvas id="studentChart" height="200"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-card">
                <h6><i class="bi bi-pie-chart"></i> Company Verification Status</h6>
                <canvas id="companyChart" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <div class="col-md-6">
            <div class="chart-card">
                <h6><i class="bi bi-bar-chart-steps"></i> Post Moderation Status</h6>
                <canvas id="postChart" height="200"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-card">
                <h6><i class="bi bi-bar-chart-steps"></i> Report Resolution Status</h6>
                <canvas id="reportChart" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <div class="col-md-6">
            <div class="chart-card">
                <h6><i class="bi bi-building"></i> Top Industries</h6>
                <div id="industries-list">
                    <?php if (empty($topIndustries)): ?>
                        <div class="empty-list">
                            <i class="bi bi-bar-chart" style="opacity:0.4;"></i>
                            <p class="mt-2">No industry data available</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($topIndustries as $i): ?>
                            <div class="list-item">
                                <span class="list-name"><?php echo htmlspecialchars($i['industry']); ?></span>
                                <span class="list-count"><?php echo $i['count']; ?> companies</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-card">
                <h6><i class="bi bi-mortarboard"></i> Top Courses</h6>
                <div id="courses-list">
                    <?php if (empty($topCourses)): ?>
                        <div class="empty-list">
                            <i class="bi bi-bar-chart" style="opacity:0.4;"></i>
                            <p class="mt-2">No course data available</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($topCourses as $c): ?>
                            <div class="list-item">
                                <span class="list-name"><?php echo htmlspecialchars($c['course']); ?></span>
                                <span class="list-count"><?php echo $c['count']; ?> students</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add this section to your analytics page -->
<div class="card mt-4">
    <div class="card-header">
        <h5><i class="bi bi-clock-history"></i> Admin Audit Log</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>IP Address</th>
                        <th>Date/Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $auditSql = "SELECT a.*, u.email as admin_email 
                                FROM admin_actions a 
                                JOIN users u ON a.admin_id = u.id 
                                ORDER BY a.created_at DESC 
                                LIMIT 50";
                    $auditLogs = $conn->query($auditSql)->fetchAll();
                    foreach ($auditLogs as $log): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['admin_email']); ?></td>
                        <td><?php echo htmlspecialchars($log['action_type']); ?></td>
                        <td><?php echo htmlspecialchars($log['target_type'] . ' #' . $log['target_id']); ?></td>
                        <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                        <td><?php echo $log['created_at']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Student Verification Chart
const studentCtx = document.getElementById('studentChart').getContext('2d');
new Chart(studentCtx, {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Approved', 'Rejected'],
        datasets: [{
            data: [<?php echo $studentStats['pending'] ?? 0; ?>, <?php echo $studentStats['active'] ?? 0; ?>, <?php echo $studentStats['rejected'] ?? 0; ?>],
            backgroundColor: ['#f39c12', '#2ecc71', '#e74c3c'],
            borderWidth: 0,
            hoverOffset: 8
        }]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 } } }
        }
    }
});

// Company Verification Chart
const companyCtx = document.getElementById('companyChart').getContext('2d');
new Chart(companyCtx, {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Approved', 'Rejected'],
        datasets: [{
            data: [<?php echo $companyStats['pending'] ?? 0; ?>, <?php echo $companyStats['active'] ?? 0; ?>, <?php echo $companyStats['rejected'] ?? 0; ?>],
            backgroundColor: ['#f39c12', '#2ecc71', '#e74c3c'],
            borderWidth: 0,
            hoverOffset: 8
        }]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 } } }
        }
    }
});

// Post Status Chart
const postCtx = document.getElementById('postChart').getContext('2d');
new Chart(postCtx, {
    type: 'bar',
    data: {
        labels: ['Pending', 'Approved', 'Removed', 'Flagged'],
        datasets: [{
            label: 'Posts',
            data: [<?php echo $postStats['pending'] ?? 0; ?>, <?php echo $postStats['approved'] ?? 0; ?>, <?php echo $postStats['removed'] ?? 0; ?>, <?php echo $postStats['flagged'] ?? 0; ?>],
            backgroundColor: ['#f39c12', '#2ecc71', '#e74c3c', '#9b59b6'],
            borderRadius: 8,
            barPercentage: 0.65
        }]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: true, 
        plugins: { 
            legend: { position: 'top', labels: { font: { size: 11 } } },
            tooltip: { callbacks: { label: (ctx) => ctx.raw + ' posts' } }
        },
        scales: { y: { beginAtZero: true, title: { display: true, text: 'Number of Posts', font: { size: 10 } } } }
    }
});

// Report Status Chart
const reportCtx = document.getElementById('reportChart').getContext('2d');
new Chart(reportCtx, {
    type: 'bar',
    data: {
        labels: ['Open', 'Investigating', 'Resolved', 'Dismissed'],
        datasets: [{
            label: 'Reports',
            data: [<?php echo $reportStats['open'] ?? 0; ?>, <?php echo $reportStats['investigating'] ?? 0; ?>, <?php echo $reportStats['resolved'] ?? 0; ?>, <?php echo $reportStats['dismissed'] ?? 0; ?>],
            backgroundColor: ['#e74c3c', '#f39c12', '#2ecc71', '#95a5a6'],
            borderRadius: 8,
            barPercentage: 0.65
        }]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: true, 
        plugins: { 
            legend: { position: 'top', labels: { font: { size: 11 } } },
            tooltip: { callbacks: { label: (ctx) => ctx.raw + ' reports' } }
        },
        scales: { y: { beginAtZero: true, title: { display: true, text: 'Number of Reports', font: { size: 10 } } } }
    }
});
</script>
</body>
</html>