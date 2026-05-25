<?php
// admin/user_reports.php
require_once 'includes/admin_auth.php';

// Handle report actions
if (isset($_POST['action']) && isset($_POST['report_id'])) {
    $report_id = $_POST['report_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'resolve') {
            $conn->prepare("UPDATE reports SET status = 'resolved', resolved_by = ?, resolved_at = NOW() WHERE id = ?")->execute([$_SESSION['user_id'], $report_id]);
            $_SESSION['success'] = "Report resolved successfully!";
            
        } elseif ($action === 'investigate') {
            $conn->prepare("UPDATE reports SET status = 'investigating' WHERE id = ?")->execute([$report_id]);
            $_SESSION['success'] = "Report marked as investigating";
            
        } elseif ($action === 'dismiss') {
            $conn->prepare("UPDATE reports SET status = 'dismissed', resolved_by = ?, resolved_at = NOW() WHERE id = ?")->execute([$_SESSION['user_id'], $report_id]);
            $_SESSION['success'] = "Report dismissed";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
    
    header("Location: user_reports.php");
    exit();
}

// Get counts for stats
$stats = ['total' => 0, 'open' => 0, 'investigating' => 0, 'resolved' => 0, 'dismissed' => 0];
try {
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM reports GROUP BY status");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats[$row['status']] = $row['count'];
        $stats['total'] += $row['count'];
    }
} catch (PDOException $e) {
    error_log("Stats error: " . $e->getMessage());
}

// ============================================
// ADD THIS SECTION - FETCH REPORTS DATA
// ============================================
$reports = [];
try {
    $sql = "SELECT r.*,
                   reporter.email as reporter_email,
                   reporter.user_type as reporter_type,
                   subject.email as subject_email,
                   subject.user_type as subject_type,
                   p.content as post_content,
                   c.comment as comment_content
            FROM reports r
            JOIN users reporter ON r.reporter_id = reporter.id
            JOIN users subject ON r.reported_id = subject.id
            LEFT JOIN posts p ON r.post_id = p.id
            LEFT JOIN comments c ON r.comment_id = c.id
            ORDER BY r.created_at DESC";
    $stmt = $conn->query($sql);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Reports fetch error: " . $e->getMessage());
    $reports = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Reports - GreenBridge Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Playfair+Display:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Keep all your existing styles here - they are fine */
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
        
        /* Table Card */
        .table-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-border);
            overflow-x: auto;
            box-shadow: var(--shadow-sm);
        }
        
        .table-card .table {
            margin: 0;
            font-size: 0.85rem;
            min-width: 950px;
        }
        
        .table-card .table thead th {
            background: var(--gray-light);
            border-bottom: 1px solid var(--gray-border);
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            padding: 1rem 1rem;
        }
        
        .table-card .table tbody td {
            padding: 0.9rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--gray-border);
        }
        
        .table-card .table tbody tr:hover td { background: var(--gray-light); }
        
        /* Badges */
        .badge-status {
            display: inline-flex;
            font-size: 0.7rem;
            font-weight: 600;
            border-radius: 30px;
            padding: 0.25rem 0.9rem;
        }
        
        .badge-open { background: #fee9e7; color: #b13e3e; }
        .badge-investigating { background: #fef3cd; color: #856404; }
        .badge-resolved { background: #e6f7ef; color: #1e6f3f; }
        .badge-dismissed { background: #f1f5f9; color: #475569; }
        
        /* Action Buttons */
        .action-btn {
            border: none;
            border-radius: 30px;
            padding: 0.35rem 0.8rem;
            font-size: 0.7rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.2s;
        }
        
        .btn-resolve { background: #e6f7ef; color: #1e6f3f; }
        .btn-resolve:hover { background: #c8e9da; transform: translateY(-1px); }
        .btn-investigate { background: #fef3cd; color: #856404; }
        .btn-investigate:hover { background: #fde68a; transform: translateY(-1px); }
        .btn-dismiss { background: #f1f5f9; color: #475569; }
        .btn-dismiss:hover { background: #e2e8f0; transform: translateY(-1px); }
        .btn-view { background: var(--gray-light); color: var(--forest); border: 1px solid var(--gray-border); }
        .btn-view:hover { background: var(--sage); transform: translateY(-1px); }
        
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
            justify-content: space-between;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }
        
        .modal-header h6 { margin: 0; font-size: 1rem; font-weight: 700; }
        .modal-close { background: none; border: none; color: rgba(255,255,255,0.6); font-size: 1.2rem; cursor: pointer; }
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
        
        .form-control {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1px solid var(--gray-border);
            border-radius: 10px;
            font-size: 0.85rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--mint);
        }
        
        .report-type {
            background: #e8f0fe;
            color: #1e40af;
            padding: 0.2rem 0.7rem;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
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
    <a class="nav-link active" href="user_reports.php">
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
    <h5>User Reports</h5>
    <div class="search-box ms-auto">
        <i class="bi bi-search"></i>
        <input type="text" id="search" placeholder="Search reports...">
    </div>
    <div class="icon-btn" onclick="window.location.href='analytics.php'">
        <i class="bi bi-bar-chart-line"></i>
    </div>
</div>

<!-- Main Content -->
<div id="main">
    <div class="page-title">User Reports</div>
    <div class="page-subtitle">Investigate and resolve reports submitted by users regarding inappropriate content or behavior.</div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-success">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card" onclick="filterByStatus('open')">
            <div class="stat-value"><?php echo $stats['open'] ?? 0; ?></div>
            <div class="stat-label">Open</div>
        </div>
        <div class="stat-card" onclick="filterByStatus('investigating')">
            <div class="stat-value"><?php echo $stats['investigating'] ?? 0; ?></div>
            <div class="stat-label">Investigating</div>
        </div>
        <div class="stat-card" onclick="filterByStatus('resolved')">
            <div class="stat-value"><?php echo $stats['resolved'] ?? 0; ?></div>
            <div class="stat-label">Resolved</div>
        </div>
        <div class="stat-card" onclick="filterByStatus('all')">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Reports</div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filter-row">
        <div class="filter-search">
            <i class="bi bi-search"></i>
            <input type="text" id="search-input" placeholder="Search by reporter or subject...">
        </div>
        <select class="filter-select" id="status-filter">
            <option value="all">All Statuses</option>
            <option value="open">Open</option>
            <option value="investigating">Investigating</option>
            <option value="resolved">Resolved</option>
            <option value="dismissed">Dismissed</option>
        </select>
        <span class="ms-auto" style="font-size:0.8rem; color:var(--text-muted)" id="report-count">
            <?php echo count($reports); ?> reports
        </span>
    </div>
    
    <!-- Reports Table -->
    <div class="table-card">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Reporter</th>
                    <th>Reported User</th>
                    <th>Reason</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="report-table">
                <?php if (empty($reports)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size:2rem; opacity:0.4;"></i>
                            <p class="mt-2">No reports found.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reports as $report): ?>
                        <tr data-status="<?php echo $report['status']; ?>" 
                            data-search="<?php echo strtolower(($report['reporter_email'] ?? '') . ' ' . ($report['subject_email'] ?? '')); ?>">
                            <td style="font-family:monospace;">#<?php echo $report['id']; ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($report['reporter_email'] ?? 'N/A'); ?></div>
                                <small class="text-muted"><?php echo isset($report['reporter_type']) ? ucfirst($report['reporter_type']) : 'User'; ?></small>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($report['subject_email'] ?? 'N/A'); ?></div>
                                <small class="text-muted"><?php echo isset($report['subject_type']) ? ucfirst($report['subject_type']) : 'User'; ?></small>
                            </td>
                            <td><span class="report-type"><?php echo ucfirst($report['reason'] ?? 'Other'); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($report['created_at'])); ?></td>
                            <td><span class="badge-status badge-<?php echo $report['status']; ?>"><?php echo ucfirst($report['status']); ?></span></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="action-btn btn-view" onclick="viewReport(<?php echo $report['id']; ?>)">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                    <?php if (($report['status'] ?? '') === 'open'): ?>
                                        <button class="action-btn btn-investigate" onclick="openInvestigateModal(<?php echo $report['id']; ?>)">
                                            <i class="bi bi-search"></i> Investigate
                                        </button>
                                    <?php endif; ?>
                                    <?php if (($report['status'] ?? '') !== 'resolved' && ($report['status'] ?? '') !== 'dismissed'): ?>
                                        <button class="action-btn btn-resolve" onclick="resolveReport(<?php echo $report['id']; ?>)">
                                            <i class="bi bi-check-lg"></i> Resolve
                                        </button>
                                        <button class="action-btn btn-dismiss" onclick="dismissReport(<?php echo $report['id']; ?>)">
                                            <i class="bi bi-x-lg"></i> Dismiss
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- View Modal -->
<div class="modal-overlay" id="view-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h6><i class="bi bi-file-text me-2"></i>Report Details</h6>
            <button class="modal-close" onclick="closeModal('view-modal')">&times;</button>
        </div>
        <div class="modal-body" id="view-modal-body"></div>
        <div class="modal-footer">
            <button class="action-btn btn-view" onclick="closeModal('view-modal')">Close</button>
        </div>
    </div>
</div>

<!-- Investigate Modal -->
<div class="modal-overlay" id="investigate-modal">
    <div class="modal-content">
        <div class="modal-header" style="background:#856404;">
            <h6><i class="bi bi-search me-2"></i>Investigate Report</h6>
            <button class="modal-close" onclick="closeModal('investigate-modal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <div class="mb-3">
                    <label class="detail-label">Investigation Notes</label>
                    <textarea name="notes" class="form-control" rows="4" placeholder="Document your findings, actions taken, and next steps..."></textarea>
                </div>
                <input type="hidden" name="report_id" id="investigate-id">
                <input type="hidden" name="action" value="investigate">
            </div>
            <div class="modal-footer">
                <button type="button" class="action-btn btn-view" onclick="closeModal('investigate-modal')">Cancel</button>
                <button type="submit" class="action-btn btn-investigate">Start Investigation</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Report data for view modal
const reportData = <?php 
    $data = [];
    foreach ($reports as $r) {
        $data[$r['id']] = [
            'reporter_email' => $r['reporter_email'] ?? 'N/A',
            'reporter_type' => isset($r['reporter_type']) ? ucfirst($r['reporter_type']) : 'User',
            'subject_email' => $r['subject_email'] ?? 'N/A',
            'subject_type' => isset($r['subject_type']) ? ucfirst($r['subject_type']) : 'User',
            'reason' => $r['reason'] ?? 'Other',
            'description' => $r['description'] ?? 'No description provided.',
            'created_at' => date('M d, Y g:i A', strtotime($r['created_at'])),
            'status' => $r['status']
        ];
    }
    echo json_encode($data); 
?>;

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function viewReport(id) {
    const r = reportData[id];
    if (!r) return;
    
    let statusClass = '';
    if (r.status === 'open') statusClass = 'badge-open';
    else if (r.status === 'investigating') statusClass = 'badge-investigating';
    else if (r.status === 'resolved') statusClass = 'badge-resolved';
    else statusClass = 'badge-dismissed';
    
    document.getElementById('view-modal-body').innerHTML = `
        <div class="detail-grid">
            <div>
                <div class="detail-label">Reporter</div>
                <div class="detail-value">${escapeHtml(r.reporter_email)}</div>
                <small class="text-muted">${escapeHtml(r.reporter_type)}</small>
            </div>
            <div>
                <div class="detail-label">Reported User</div>
                <div class="detail-value">${escapeHtml(r.subject_email)}</div>
                <small class="text-muted">${escapeHtml(r.subject_type)}</small>
            </div>
            <div>
                <div class="detail-label">Reason</div>
                <div class="detail-value"><span class="report-type">${escapeHtml(r.reason)}</span></div>
            </div>
            <div>
                <div class="detail-label">Status</div>
                <div class="detail-value"><span class="badge-status ${statusClass}">${r.status.charAt(0).toUpperCase() + r.status.slice(1)}</span></div>
            </div>
            <div>
                <div class="detail-label">Date Reported</div>
                <div class="detail-value">${escapeHtml(r.created_at)}</div>
            </div>
        </div>
        <div class="mt-3">
            <div class="detail-label">Description</div>
            <div class="detail-value" style="background:var(--gray-light); padding:0.8rem; border-radius:12px; margin-top:0.3rem">
                ${escapeHtml(r.description)}
            </div>
        </div>
    `;
    openModal('view-modal');
}

function resolveReport(id) { 
    if (confirm('Mark this report as resolved?')) submitAction(id, 'resolve'); 
}

function dismissReport(id) { 
    if (confirm('Dismiss this report? No further action will be taken.')) submitAction(id, 'dismiss'); 
}

function filterByStatus(status) {
    const statusFilter = document.getElementById('status-filter');
    statusFilter.value = status;
    filterReports();
}

function openInvestigateModal(id) { 
    document.getElementById('investigate-id').value = id; 
    openModal('investigate-modal'); 
}

function submitAction(id, action) { 
    const form = document.createElement('form'); 
    form.method = 'POST'; 
    form.innerHTML = `<input type="hidden" name="report_id" value="${id}"><input type="hidden" name="action" value="${action}">`; 
    document.body.appendChild(form); 
    form.submit(); 
}

function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// Close modals when clicking overlay
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            overlay.classList.remove('open');
        }
    });
});

// Filter functionality
function filterReports() {
    const search = document.getElementById('search-input').value.toLowerCase();
    const status = document.getElementById('status-filter').value;
    const rows = document.querySelectorAll('#report-table tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        if (row.querySelector('td[colspan]')) return;
        const rowStatus = row.dataset.status;
        const searchText = row.dataset.search || '';
        
        let show = true;
        if (status !== 'all' && rowStatus !== status) show = false;
        if (search && !searchText.includes(search)) show = false;
        
        row.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });
    
    document.getElementById('report-count').textContent = visibleCount + ' reports';
}

document.getElementById('search-input').addEventListener('input', filterReports);
document.getElementById('status-filter').addEventListener('change', filterReports);
</script>
</body>
</html>