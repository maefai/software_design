<?php
// admin/student_verification.php
require_once 'includes/admin_auth.php';

// 1. Handle approval/rejection via POST
if (isset($_POST['action']) && isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'approve') {
            $stmt = $conn->prepare("SELECT user_id FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $user_id = $stmt->fetch(PDO::FETCH_ASSOC)['user_id'];
            
            $conn->prepare("UPDATE students SET status = 'active' WHERE id = ?")->execute([$student_id]);
            $conn->prepare("UPDATE users SET status = 'active', verified_at = NOW() WHERE id = ?")->execute([$user_id]);
            $conn->prepare("INSERT INTO admin_actions (admin_id, action_type, target_type, target_id, created_at) VALUES (?, 'approve_student', 'student', ?, NOW())")->execute([$_SESSION['user_id'], $student_id]);
            
            $_SESSION['success'] = "Student approved successfully!";
        } elseif ($action === 'reject') {
            $reason = $_POST['reason'] ?? '';
            $stmt = $conn->prepare("SELECT user_id FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $user_id = $stmt->fetch(PDO::FETCH_ASSOC)['user_id'];
            
            $conn->prepare("UPDATE students SET status = 'rejected' WHERE id = ?")->execute([$student_id]);
            $conn->prepare("UPDATE users SET status = 'rejected', rejected_reason = ? WHERE id = ?")->execute([$reason, $user_id]);
            $conn->prepare("INSERT INTO admin_actions (admin_id, action_type, target_type, target_id, details, created_at) VALUES (?, 'reject_student', 'student', ?, ?, NOW())")->execute([$_SESSION['user_id'], $student_id, $reason]);
            
            $_SESSION['success'] = "Student rejected.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
    
    header("Location: student_verification.php");
    exit();
}

// 2. Fetch Statistics
$stats = ['total' => 0, 'pending' => 0, 'active' => 0, 'rejected' => 0];
try {
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM students";
    $stmt = $conn->query($sql);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats = [
            'total' => (int)($row['total'] ?? 0),
            'pending' => (int)($row['pending'] ?? 0),
            'active' => (int)($row['active'] ?? 0),
            'rejected' => (int)($row['rejected'] ?? 0)
        ];
    }
} catch (PDOException $e) {
    error_log("Stats error: " . $e->getMessage());
}

// 3. Fetch Students List
$students = [];
try {
    // Added 's.status as display_status' to match the HTML variables
    $sql = "SELECT s.*, u.email, u.status as user_status, s.status as display_status, u.created_at as registered_date
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            ORDER BY s.created_at DESC";
    $stmt = $conn->query($sql);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Students fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Verification - GreenBridge Admin</title>
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
            min-width: 1000px;
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
            align-items: center;
            font-size: 0.7rem;
            font-weight: 600;
            border-radius: 30px;
            padding: 0.25rem 0.9rem;
        }
        
        .badge-pending { background: #fef3cd; color: #856404; }
        .badge-active { background: #e6f7ef; color: #1e6f3f; }
        .badge-rejected { background: #fee9e7; color: #b13e3e; }
        
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
            margin: 0 2px;
        }
        
        .btn-approve { background: #e6f7ef; color: #1e6f3f; }
        .btn-approve:hover { background: #c8e9da; transform: translateY(-1px); }
        
        .btn-reject { background: #fee9e7; color: #b13e3e; }
        .btn-reject:hover { background: #fcd6d2; transform: translateY(-1px); }
        
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
            max-width: 550px;
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
        .modal-close { background: none; border: none; color: white; font-size: 1.2rem; cursor: pointer; opacity: 0.8; }
        .modal-close:hover { opacity: 1; }
        
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
    <a class="nav-link active" href="student_verification.php">
        <i class="bi bi-person-check-fill"></i> Student Verification
        <span class="badge-count"><?php echo $stats['pending']; ?></span>
    </a>
    <a class="nav-link" href="company_verification.php">
        <i class="bi bi-building-check"></i> Company Verification
        <span class="badge-count">0</span>
    </a>
    
    <div class="sidebar-section">Content</div>
    <a class="nav-link" href="post_checking.php">
        <i class="bi bi-shield-exclamation"></i> Post Moderation
        <span class="badge-count">0</span>
    </a>
    <a class="nav-link" href="user_reports.php">
        <i class="bi bi-flag-fill"></i> User Reports
        <span class="badge-count">0</span>
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
    <h5>Student Verification</h5>
    <div class="search-box ms-auto">
        <i class="bi bi-search"></i>
        <input type="text" id="search" placeholder="Search...">
    </div>
    <div class="icon-btn" onclick="window.location.href='post_checking.php'"><i class="bi bi-bell"></i></div>
    <div class="icon-btn" onclick="window.location.href='analytics.php'"><i class="bi bi-bar-chart-line"></i></div>
</div>

<!-- Main Content -->
<div id="main">
    <div class="page-title">Student Verification</div>
    <div class="page-subtitle">Review and approve or reject student account applications</div>
    
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
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card" onclick="filterByStatus('active')">
            <div class="stat-value"><?php echo $stats['active']; ?></div>
            <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card" onclick="filterByStatus('rejected')">
            <div class="stat-value"><?php echo $stats['rejected']; ?></div>
            <div class="stat-label">Rejected</div>
        </div>
        <div class="stat-card" onclick="filterByStatus('all')">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total</div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filter-row">
        <div class="filter-search">
            <i class="bi bi-search"></i>
            <input type="text" id="searchInput" placeholder="Search by name, ID, email...">
        </div>
        <select class="filter-select" id="status-filter">
            <option value="all">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="active">Approved</option>
            <option value="rejected">Rejected</option>
        </select>
        <select class="filter-select" id="college-filter">
            <option value="all">All Colleges</option>
            <option value="CCS">CCS - Computer Studies</option>
            <option value="CEAT">CEAT - Engineering</option>
            <option value="COB">COB - Business</option>
            <option value="CAS">CAS - Arts & Sciences</option>
        </select>
        <span class="ms-auto" style="font-size:0.8rem; color:var(--text-muted)" id="student-count"><?php echo count($students); ?> students</span>
    </div>
    
    <!-- Students Table -->
    <div class="table-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>ID Number</th>
                    <th>Course</th>
                    <th>College</th>
                    <th>Email</th>
                    <th>Registered</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="student-table">
                <?php if (empty($students)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No students found.</td></tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <tr data-status="<?php echo $student['display_status']; ?>" 
                            data-college="<?php echo $student['college']; ?>"
                            data-search="<?php echo strtolower($student['fullname'] . ' ' . $student['student_id'] . ' ' . $student['email']); ?>">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="user-avatar" style="width:34px; height:34px; font-size:0.8rem;"><?php echo strtoupper(substr($student['fullname'], 0, 1)); ?></div>
                                    <div>
                                        <div style="font-weight:600"><?php echo htmlspecialchars($student['fullname']); ?></div>
                                        <div style="font-size:0.7rem; color:var(--text-muted)">Year <?php echo $student['year_level']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span style="font-family:monospace"><?php echo htmlspecialchars($student['student_id']); ?></span></td>
                            <td><?php echo htmlspecialchars($student['course']); ?></td>
                            <td><span class="badge-status" style="background:#e8f0fe; color:#1e40af"><?php echo $student['college']; ?></span></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td>
                                <?php 
                                    $date = $student['registered_date'];
                                    if ($date instanceof DateTime) {
                                        echo $date->format('M d, Y');
                                    } else {
                                        echo date('M d, Y', strtotime($date));
                                    }
                                ?>
                             </td>
                            <td><span class="badge-status badge-<?php echo $student['display_status']; ?>"><?php echo ucfirst($student['display_status']); ?></span></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="action-btn btn-view" onclick="viewStudent(<?php echo $student['id']; ?>)"><i class="bi bi-eye"></i> View</button>
                                    <?php if ($student['display_status'] === 'pending'): ?>
                                        <button class="action-btn btn-approve" onclick="approveStudent(<?php echo $student['id']; ?>)">
                                            <i class="bi bi-check-lg"></i> Accept
                                        </button>
                                        <button class="action-btn btn-reject" onclick="openRejectModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['fullname']); ?>')">
                                            <i class="bi bi-x-lg"></i> Reject
                                        </button>
                                    <?php elseif ($student['display_status'] === 'active'): ?>
                                        <button class="action-btn btn-reject" onclick="openRejectModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['fullname']); ?>')">
                                            <i class="bi bi-slash-circle"></i> Revoke
                                        </button>
                                    <?php elseif ($student['display_status'] === 'rejected'): ?>
                                        <button class="action-btn btn-approve" onclick="approveStudent(<?php echo $student['id']; ?>)">
                                            <i class="bi bi-arrow-counterclockwise"></i> Restore
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

<!-- View Student Modal -->
<div class="modal-overlay" id="view-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h6><i class="bi bi-person-badge me-2"></i>Student Details</h6>
            <button class="modal-close" onclick="closeModal('view-modal')">&times;</button>
        </div>
        <div class="modal-body" id="view-modal-body"></div>
        <div class="modal-footer">
            <button class="action-btn btn-view" onclick="closeModal('view-modal')">Close</button>
        </div>
    </div>
</div>

<!-- Reject Student Modal -->
<div class="modal-overlay" id="reject-modal">
    <div class="modal-content">
        <div class="modal-header" style="background:#b13e3e;">
            <h6><i class="bi bi-exclamation-triangle me-2"></i>Reject Student</h6>
            <button class="modal-close" onclick="closeModal('reject-modal')">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <p>You are about to reject <strong id="reject-student-name"></strong>'s application.</p>
                <div class="mb-3">
                    <label class="detail-label">Reason for rejection</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="Provide a reason for the student..." required></textarea>
                </div>
                <input type="hidden" name="student_id" id="reject-student-id">
                <input type="hidden" name="action" value="reject">
            </div>
            <div class="modal-footer">
                <button type="button" class="action-btn btn-view" onclick="closeModal('reject-modal')">Cancel</button>
                <button type="submit" class="action-btn btn-reject">Reject Student</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Student data for view modal
const studentData = <?php 
    $data = [];
    foreach ($students as $s) {
        $data[$s['id']] = [
            'fullname' => $s['fullname'],
            'student_id' => $s['student_id'],
            'course' => $s['course'],
            'college' => $s['college'],
            'year_level' => $s['year_level'],
            'email' => $s['email'],
            'university' => $s['university'],
            'display_status' => $s['display_status']
        ];
    }
    echo json_encode($data); 
?>;

function viewStudent(id) {
    const s = studentData[id];
    if (!s) return;
    const statusDisplay = s.display_status.charAt(0).toUpperCase() + s.display_status.slice(1);
    let statusClass = '';
    if (s.display_status === 'pending') statusClass = 'badge-pending';
    else if (s.display_status === 'active') statusClass = 'badge-active';
    else statusClass = 'badge-rejected';
    
    document.getElementById('view-modal-body').innerHTML = `
        <div class="detail-grid">
            <div><div class="detail-label">Full Name</div><div class="detail-value">${escapeHtml(s.fullname)}</div></div>
            <div><div class="detail-label">Student ID</div><div class="detail-value" style="font-family:monospace">${escapeHtml(s.student_id)}</div></div>
            <div><div class="detail-label">Course</div><div class="detail-value">${escapeHtml(s.course)}</div></div>
            <div><div class="detail-label">College</div><div class="detail-value">${escapeHtml(s.college)}</div></div>
            <div><div class="detail-label">Year Level</div><div class="detail-value">Year ${s.year_level}</div></div>
            <div><div class="detail-label">Email</div><div class="detail-value">${escapeHtml(s.email)}</div></div>
            <div><div class="detail-label">University</div><div class="detail-value">${escapeHtml(s.university)}</div></div>
            <div><div class="detail-label">Status</div><div class="detail-value"><span class="badge-status ${statusClass}">${statusDisplay}</span></div></div>
        </div>
    `;
    openModal('view-modal');
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function approveStudent(id) {
    if (confirm('Are you sure you want to approve this student?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="student_id" value="${id}"><input type="hidden" name="action" value="approve">`;
        document.body.appendChild(form);
        form.submit();
    }
}

function openRejectModal(id, name) {
    document.getElementById('reject-student-id').value = id;
    document.getElementById('reject-student-name').textContent = name;
    openModal('reject-modal');
}

function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function filterByStatus(status) {
    const statusFilter = document.getElementById('status-filter');
    statusFilter.value = status;
    filterTable();
}

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            overlay.classList.remove('open');
        }
    });
});

// Filter and search functionality
function filterTable() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('status-filter').value;
    const college = document.getElementById('college-filter').value;
    
    const rows = document.querySelectorAll('#student-table tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        if (row.querySelector('td[colspan]')) return;
        
        const rowStatus = row.dataset.status;
        const rowCollege = row.dataset.college;
        const searchText = row.dataset.search || '';
        
        let show = true;
        if (status !== 'all' && rowStatus !== status) show = false;
        if (college !== 'all' && rowCollege !== college) show = false;
        if (search && !searchText.includes(search)) show = false;
        
        row.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });
    
    document.getElementById('student-count').textContent = visibleCount + ' students';
}

document.getElementById('searchInput').addEventListener('input', filterTable);
document.getElementById('status-filter').addEventListener('change', filterTable);
document.getElementById('college-filter').addEventListener('change', filterTable);
</script>
</body>
</html>