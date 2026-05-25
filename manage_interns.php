<?php
// company/manage_interns.php - WITH DASHBOARD STYLES
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

// Check if company is logged in
requireCompany();

$user_id = $_SESSION['user_id'];
$company = getCompanyData($conn, $user_id);

$message = '';
$error = '';

try {
    // Handle Clock In
    if (isset($_POST['clock_in_submit'])) {
        $student_id = $_POST['student_id'];
        $internship_id = $_POST['internship_id'];
        
        $checkStmt = $conn->prepare("SELECT id FROM dtr_logs WHERE student_id = ? AND internship_id = ? AND clock_out IS NULL");
        $checkStmt->execute([$student_id, $internship_id]);
        
        if ($checkStmt->fetch()) {
            $error = "Student is already clocked in!";
        } else {
            $stmt = $conn->prepare("INSERT INTO dtr_logs (student_id, internship_id, clock_in, status) VALUES (?, ?, NOW(), 'active')");
            $stmt->execute([$student_id, $internship_id]);
            $message = "✅ Student clocked in successfully!";
        }
        
        if ($message) $_SESSION['flash_message'] = $message;
        if ($error) $_SESSION['flash_error'] = $error;
        header("Location: manage_interns.php");
        exit();
    }

    // Handle Clock Out
    if (isset($_POST['clock_out_submit'])) {
        $log_id = $_POST['log_id'];
        
        // Translated DATEDIFF to TIMESTAMPDIFF for MySQL
        $sql = "UPDATE dtr_logs 
                SET clock_out = NOW(), 
                    hours = CAST(TIMESTAMPDIFF(MINUTE, clock_in, NOW()) AS FLOAT) / 60.0,
                    status = 'completed'
                WHERE id = ? AND clock_out IS NULL";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$log_id]);
        $message = "✅ Student clocked out successfully!";
        
        if ($message) $_SESSION['flash_message'] = $message;
        header("Location: manage_interns.php");
        exit();
    }

    // Get flash messages
    if (isset($_SESSION['flash_message'])) { $message = $_SESSION['flash_message']; unset($_SESSION['flash_message']); }
    if (isset($_SESSION['flash_error'])) { $error = $_SESSION['flash_error']; unset($_SESSION['flash_error']); }

    $stats = [];
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT s.id) as total FROM applications a JOIN students s ON a.student_id = s.id JOIN internships i ON a.internship_id = i.id WHERE i.company_id = ? AND a.status = 'accepted'");
    $stmt->execute([$company['id']]);
    $stats['total_interns'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(DISTINCT d.student_id) as total FROM dtr_logs d JOIN internships i ON d.internship_id = i.id WHERE i.company_id = ? AND d.clock_out IS NULL");
    $stmt->execute([$company['id']]);
    $stats['active_interns'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Translated ISNULL to IFNULL and GETDATE to NOW
    $stmt = $conn->prepare("SELECT IFNULL(SUM(hours), 0) as total_hours FROM dtr_logs d JOIN internships i ON d.internship_id = i.id WHERE i.company_id = ? AND MONTH(d.clock_in) = MONTH(NOW()) AND d.status = 'completed'");
    $stmt->execute([$company['id']]);
    $stats['total_hours'] = number_format($stmt->fetch(PDO::FETCH_ASSOC)['total_hours'] ?? 0, 1);

    $interns = [];
    $sql = "SELECT DISTINCT s.id as student_id, s.fullname, s.course, s.year_level, s.student_id as student_number,
                i.id as internship_id, i.title as internship_title, d.id as dtr_log_id, d.clock_in, d.clock_out, d.hours, d.status as dtr_status
            FROM applications a
            JOIN students s ON a.student_id = s.id
            JOIN internships i ON a.internship_id = i.id
            LEFT JOIN dtr_logs d ON d.student_id = s.id AND d.internship_id = i.id AND d.clock_out IS NULL
            WHERE i.company_id = ? AND a.status = 'accepted'
            ORDER BY s.fullname";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$company['id']]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['is_clocked_in'] = ($row['dtr_status'] == 'active' && !$row['clock_out']);
        if ($row['is_clocked_in'] && $row['clock_in']) {
            $row['clock_in_timestamp'] = strtotime($row['clock_in']) * 1000;
            $row['clock_in_time'] = date('h:i A', strtotime($row['clock_in']));
        }
        $interns[] = $row;
    }

    // Translated TOP 20 to LIMIT 20
    $dtr_logs = [];
    $stmt = $conn->prepare("SELECT d.*, s.fullname as student_name FROM dtr_logs d JOIN students s ON d.student_id = s.id JOIN internships i ON d.internship_id = i.id WHERE i.company_id = ? ORDER BY d.clock_in DESC LIMIT 20");
    $stmt->execute([$company['id']]);
    $dtr_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Interns - GreenBridge</title>
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
            max-width: 1400px;
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
        
        /* Section Headings */
        .section-heading {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--forest);
        }
        .section-heading i {
            color: var(--mint);
        }
        
        /* Intern Cards */
        .intern-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-border);
            padding: 1.4rem;
            margin-bottom: 1rem;
            transition: all 0.25s ease;
            box-shadow: var(--shadow-sm);
        }
        .intern-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--sage-dk);
        }
        
        .status-badge {
            padding: 0.25rem 0.85rem;
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            letter-spacing: 0.3px;
        }
        .status-active { background: #e6f7ef; color: #1e6f3f; }
        .status-off { background: #f3f4f6; color: #6b7280; border: 1px solid var(--gray-border); }
        
        .running-timer {
            background: #fef3cd;
            color: #92400e;
            padding: 0.6rem 1rem;
            border-radius: 12px;
            font-family: 'Inter', monospace;
            font-size: 1rem;
            font-weight: 700;
            text-align: center;
            margin: 0.75rem 0;
        }
        
        .btn-clock {
            padding: 0.5rem 1.2rem;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-clock-in { background: var(--forest); color: white; }
        .btn-clock-in:hover { background: var(--forest-mid); transform: translateY(-1px); }
        .btn-clock-out { background: #dc2626; color: white; }
        .btn-clock-out:hover { background: #b91c1c; transform: translateY(-1px); }
        
        .table-card {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-border);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .table-card .table { margin: 0; }
        .table-card .table thead th { 
            background: var(--gray-light); 
            padding: 0.9rem 1rem; 
            font-size: 0.7rem; 
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: var(--forest);
            border-bottom: 1px solid var(--gray-border);
        }
        .table-card .table tbody td { 
            padding: 0.8rem 1rem; 
            vertical-align: middle;
            font-size: 0.8rem;
        }
        
        .alert {
            padding: 0.9rem 1.2rem;
            border-radius: 14px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .alert-success {
            background: #e6f7ef;
            color: #1e6f3f;
            border-left: 4px solid var(--mint);
        }
        .alert-danger {
            background: #fee9e7;
            color: #b13e3e;
            border-left: 4px solid #e07c7c;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: var(--white);
            border-radius: var(--radius-lg);
            color: var(--text-muted);
        }
        .empty-state i { font-size: 3rem; margin-bottom: 0.75rem; opacity: 0.4; color: var(--sage-dk); }
        
        .intern-avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--sage) 0%, var(--sage-dk) 100%);
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 1rem;
            color: var(--forest);
            flex-shrink: 0;
        }
        
        @media (max-width: 768px) {
            .main-container { padding: 1rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 0.8rem; }
            .running-timer { font-size: 0.85rem; }
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
        .intern-card, .stat-card {
            animation: fadeIn 0.3s ease;
        }
        
        .text-link {
            color: var(--forest-mid);
            text-decoration: none;
        }
        .text-link:hover {
            color: var(--mint);
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
    <div class="subnav-item active" onclick="location.href='manage_interns.php'">Manage Interns</div>
    <div class="subnav-item" onclick="location.href='profile.php'">Profile</div>
</div>

<div class="main-container">
    <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <div class="page-header">
        <div class="page-title">Manage Interns</div>
        <div class="page-subtitle">Clock in/out interns and track working hours in real-time</div>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card" onclick="location.href='#interns-list'">
            <div class="stat-value"><?php echo $stats['total_interns']; ?></div>
            <div class="stat-label">Total Interns</div>
        </div>
        <div class="stat-card" id="activeCountCard" onclick="scrollToActive()">
            <div class="stat-value" id="activeCount"><?php echo $stats['active_interns']; ?></div>
            <div class="stat-label">Currently Active</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['total_hours']; ?></div>
            <div class="stat-label">Hours This Month</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo count($dtr_logs); ?></div>
            <div class="stat-label">Total Sessions</div>
        </div>
    </div>
    
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="section-heading">
                <i class="bi bi-people"></i> Current Interns
            </div>
            
            <?php if (empty($interns)): ?>
                <div class="empty-state">
                    <i class="bi bi-person-badge"></i>
                    <p class="mt-3 mb-0">No interns assigned yet.</p>
                    <small class="text-muted">Accept applications to get started with intern management.</small>
                    <div class="mt-3">
                        <a href="manage_applications.php" class="btn-primary" style="display: inline-block; text-decoration: none; padding: 0.5rem 1.2rem; border-radius: 40px; background: var(--forest); color: white; font-size: 0.8rem;">Review Applications</a>
                    </div>
                </div>
            <?php else: ?>
                <div id="interns-list">
                    <?php foreach ($interns as $intern): ?>
                        <div class="intern-card" id="intern-card-<?php echo $intern['student_id']; ?>">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                <div class="d-flex gap-3">
                                    <div class="intern-avatar">
                                        <?php echo strtoupper(substr($intern['fullname'], 0, 2)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="color: var(--forest);"><?php echo htmlspecialchars($intern['fullname']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($intern['course']); ?> • Year <?php echo $intern['year_level']; ?></div>
                                        <div class="text-muted small mt-1">ID: <?php echo htmlspecialchars($intern['student_number']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($intern['internship_title']); ?></div>
                                    </div>
                                </div>
                                <div>
                                    <span class="status-badge <?php echo $intern['is_clocked_in'] ? 'status-active' : 'status-off'; ?>">
                                        <i class="bi <?php echo $intern['is_clocked_in'] ? 'bi-record-fill' : 'bi-record'; ?>"></i>
                                        <?php echo $intern['is_clocked_in'] ? 'On Duty' : 'Off Duty'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($intern['is_clocked_in']): ?>
                                <div class="running-timer mt-3" id="timer-<?php echo $intern['student_id']; ?>">
                                    <i class="bi bi-stopwatch"></i> 
                                    <span id="duration-<?php echo $intern['student_id']; ?>">0h 0m 0s</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> Started: <?php echo $intern['clock_in_time']; ?>
                                    </small>
                                    <form method="POST" style="margin: 0;" onsubmit="return confirm('Clock out <?php echo addslashes($intern['fullname']); ?>? This will record the hours worked.')">
                                        <input type="hidden" name="log_id" value="<?php echo $intern['dtr_log_id']; ?>">
                                        <input type="hidden" name="student_id" value="<?php echo $intern['student_id']; ?>">
                                        <button type="submit" name="clock_out_submit" class="btn-clock btn-clock-out">
                                            <i class="bi bi-stop-circle"></i> Clock Out
                                        </button>
                                    </form>
                                </div>
                                <script>
                                    (function() {
                                        var startTime = <?php echo $intern['clock_in_timestamp']; ?>;
                                        var el = document.getElementById('duration-<?php echo $intern['student_id']; ?>');
                                        if (el) {
                                            function updateTimer() {
                                                var now = new Date().getTime();
                                                var elapsed = Math.floor((now - startTime) / 1000);
                                                var hours = Math.floor(elapsed / 3600);
                                                var minutes = Math.floor((elapsed % 3600) / 60);
                                                var seconds = elapsed % 60;
                                                el.textContent = hours + 'h ' + minutes + 'm ' + seconds + 's';
                                            }
                                            updateTimer();
                                            setInterval(updateTimer, 1000);
                                        }
                                    })();
                                </script>
                            <?php else: ?>
                                <div class="text-end mt-3">
                                    <form method="POST" style="margin: 0;" onsubmit="return confirm('Clock in <?php echo addslashes($intern['fullname']); ?>?')">
                                        <input type="hidden" name="student_id" value="<?php echo $intern['student_id']; ?>">
                                        <input type="hidden" name="internship_id" value="<?php echo $intern['internship_id']; ?>">
                                        <button type="submit" name="clock_in_submit" class="btn-clock btn-clock-in">
                                            <i class="bi bi-play-circle"></i> Clock In
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Total hours completed -->
                            <?php 
                            $totalHoursSql = "SELECT IFNULL(SUM(hours), 0) as total FROM dtr_logs WHERE student_id = ? AND status = 'completed'";
                            $thStmt = $conn->prepare($totalHoursSql);
                            $thStmt->execute([$intern['student_id']]);
                            $thRow = $thStmt->fetch(PDO::FETCH_ASSOC);
                            $total_hours = $thRow['total'] ?? 0;
                            ?>
                            <div class="mt-3 pt-2 border-top" style="border-color: var(--gray-border);">
                                <small class="text-muted">
                                    <i class="bi bi-hourglass-split"></i> Total Hours Completed: 
                                    <strong><?php echo number_format($totalHoursRow['total'] ?? 0, 1); ?></strong> hrs
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-5">
            <div class="section-heading">
                <i class="bi bi-clock-history"></i> Recent DTR Records
            </div>
            <div class="table-card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Date</th>
                                <th>Clock In</th>
                                <th>Clock Out</th>
                                <th>Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dtr_logs)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-calendar-x"></i><br>
                                        No DTR records available
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dtr_logs as $log): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($log['student_name']); ?></td>
                                        <td><small><?php echo $log['clock_in'] instanceof DateTime ? $log['clock_in']->format('M d, Y') : date('M d, Y', strtotime($log['clock_in'])); ?></small></td>
                                        <td><small><?php echo $log['clock_in'] instanceof DateTime ? $log['clock_in']->format('h:i A') : date('h:i A', strtotime($log['clock_in'])); ?></small></td>
                                        <td><small><?php echo $log['clock_out'] ? ($log['clock_out'] instanceof DateTime ? $log['clock_out']->format('h:i A') : date('h:i A', strtotime($log['clock_out']))) : '--:--'; ?></small></td>
                                        <td><strong><?php echo $log['hours'] ? number_format($log['hours'], 2) : '--'; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function scrollToActive() {
    const activeCards = document.querySelectorAll('.status-active');
    if (activeCards.length > 0) {
        activeCards[0].closest('.intern-card')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// Update active count if needed via AJAX (optional)
setInterval(function() {
    fetch('get_active_interns_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.active_count !== undefined) {
                document.getElementById('activeCount').textContent = data.active_count;
            }
        })
        .catch(err => console.log('Polling error:', err));
}, 30000); // refresh every 30 seconds
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>