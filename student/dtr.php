<?php
// student/dtr.php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

requireStudent();

$user_id = $_SESSION['user_id'];
$student = getStudentData($conn, $user_id);

$dtr_logs = [];
$total_hours = 0;
$this_month_hours = 0;
$current_session = null;
$weekly_hours = [];
$monthly_hours = [];

try {
    // Get DTR logs
    $sql = "SELECT d.*, i.title as internship_title, i.company_id, c.company_name
            FROM dtr_logs d
            JOIN internships i ON d.internship_id = i.id
            JOIN companies c ON i.company_id = c.id
            WHERE d.student_id = (SELECT id FROM students WHERE user_id = ?)
            ORDER BY d.clock_in DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $dtr_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total hours summary
    $sql = "SELECT SUM(hours) as total FROM dtr_logs 
            WHERE student_id = (SELECT id FROM students WHERE user_id = ?) AND status = 'completed'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $total_hours = round($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0, 2);

    // Get this month's hours
    $sql = "SELECT SUM(hours) as total FROM dtr_logs 
            WHERE student_id = (SELECT id FROM students WHERE user_id = ?) 
            AND status = 'completed' AND MONTH(clock_in) = MONTH(NOW()) AND YEAR(clock_in) = YEAR(NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $this_month_hours = round($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0, 2);

    // Get current session
    $sql = "SELECT d.*, i.title as internship_title, c.company_name
            FROM dtr_logs d
            JOIN internships i ON d.internship_id = i.id
            JOIN companies c ON i.company_id = c.id
            WHERE d.student_id = (SELECT id FROM students WHERE user_id = ?) 
            AND d.clock_out IS NULL ORDER BY d.clock_in DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $current_session = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get weekly hours for chart
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $sql = "SELECT SUM(hours) as total FROM dtr_logs 
                WHERE student_id = (SELECT id FROM students WHERE user_id = ?) 
                AND DATE(clock_in) = ? AND status = 'completed'";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id, $date]);
        $weekly_hours[] = [
            'date' => date('D', strtotime($date)),
            'full_date' => date('M d', strtotime($date)),
            'hours' => round($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0, 2)
        ];
    }

    // Get monthly hours for chart
    for ($i = 5; $i >= 0; $i--) {
        $month = date('n', strtotime("-$i months"));
        $year = date('Y', strtotime("-$i months"));
        $month_name = date('M', strtotime("-$i months"));
        
        $sql = "SELECT SUM(hours) as total FROM dtr_logs 
                WHERE student_id = (SELECT id FROM students WHERE user_id = ?) 
                AND status = 'completed' AND MONTH(clock_in) = ? AND YEAR(clock_in) = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id, $month, $year]);
        $monthly_hours[] = [
            'month' => $month_name,
            'hours' => round($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0, 2)
        ];
    }
} catch (PDOException $e) {
    error_log("DTR error: " . $e->getMessage());
}

$required_hours = $student['required_hours'] ?? 400;
$progress_percentage = min(100, round(($total_hours / $required_hours) * 100));
$remaining_hours = max(0, $required_hours - $total_hours);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My DTR - GreenBridge</title>
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
            margin-top: 4px;
        }
        .progress { height: 6px; background: var(--gray-border); border-radius: 10px; margin-top: 0.6rem; overflow: hidden; }
        .progress-bar { background: var(--mint); border-radius: 10px; transition: width 0.5s ease; }
        
        /* Chart Cards */
        .chart-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-border);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: box-shadow 0.2s;
        }
        .chart-card:hover {
            box-shadow: var(--shadow-md);
        }
        .chart-title {
            font-weight: 700;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: var(--forest);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .chart-title i {
            color: var(--mint);
        }
        
        /* Table Card */
        .table-card {
            background: var(--white);
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
        
        /* Status Badges */
        .status-badge {
            padding: 0.25rem 0.8rem;
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }
        .status-active { background: #fef3cd; color: #856404; }
        .status-completed { background: #e6f7ef; color: #1e6f3f; }
        
        /* Current Session Card */
        .current-session-card {
            background: linear-gradient(135deg, var(--forest) 0%, var(--forest-mid) 100%);
            color: white;
            border-radius: var(--radius-lg);
            padding: 1.4rem;
            margin-bottom: 1.8rem;
            box-shadow: var(--shadow-md);
        }
        .running-timer {
            font-family: 'Inter', monospace;
            font-size: 2rem;
            font-weight: 700;
            text-align: center;
            background: rgba(255,255,255,0.12);
            padding: 0.6rem;
            border-radius: 14px;
            margin-top: 0.8rem;
            letter-spacing: 1px;
        }
        
        /* Empty State */
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
        
        /* Alert Info */
        .alert-info {
            background: var(--gray-light);
            border: 1px solid var(--gray-border);
            color: var(--text-muted);
            padding: 0.9rem 1.2rem;
            border-radius: 14px;
            margin-top: 1.5rem;
            font-size: 0.8rem;
        }
        
        @media (max-width: 768px) {
            .main-container { padding: 1rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 0.8rem; }
            .running-timer { font-size: 1.3rem; }
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
        .stat-card, .chart-card, .table-card {
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
    <div class="subnav-item" onclick="location.href='applications.php'">Applications</div>
    <div class="subnav-item" onclick="location.href='chat.php'">Chat</div>
    <div class="subnav-item" onclick="location.href='performance.php'">Performance</div>
    <div class="subnav-item active">DTR</div>
    <div class="subnav-item" onclick="location.href='logbook.php'">Logbook</div>
    <div class="subnav-item" onclick="location.href='profile.php'">Profile</div>
</div>

<div class="main-container">
    <div class="page-header">
        <div class="page-title">Daily Time Record</div>
        <div class="page-subtitle">Track your OJT hours recorded by your company supervisor</div>
    </div>
    
    <!-- Current Active Session -->
    <?php if ($current_session): ?>
        <div class="current-session-card">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <i class="bi bi-clock-history fs-4"></i>
                    <span class="fw-bold ms-2">Currently Working</span>
                </div>
                <span class="status-badge status-active">Active Session</span>
            </div>
            <div class="mt-2 small">
                <div><i class="bi bi-building"></i> <?php echo htmlspecialchars($current_session['company_name']); ?></div>
                <div><i class="bi bi-briefcase"></i> <?php echo htmlspecialchars($current_session['internship_title']); ?></div>
                <div><i class="bi bi-clock"></i> Started at: <?php echo $current_session['clock_in'] instanceof DateTime ? $current_session['clock_in']->format('h:i A') : date('h:i A', strtotime($current_session['clock_in'])); ?></div>
            </div>
            <div class="running-timer" id="runningTimer">
                <i class="bi bi-stopwatch"></i> <span id="timerDisplay">0h 0m 0s</span>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card" onclick="location.href='#dtr-table'">
            <div class="stat-value"><?php echo $total_hours; ?>/<?php echo $required_hours; ?></div>
            <div class="stat-label">Total Hours Completed</div>
            <div class="progress">
                <div class="progress-bar" style="width: <?php echo $progress_percentage; ?>%"></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $remaining_hours; ?></div>
            <div class="stat-label">Remaining Hours</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $this_month_hours; ?></div>
            <div class="stat-label">Hours This Month</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo count($dtr_logs); ?></div>
            <div class="stat-label">Total Sessions</div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="chart-card">
                <div class="chart-title">
                    <i class="bi bi-graph-up"></i> Weekly Hours (Last 7 Days)
                </div>
                <canvas id="weeklyChart" height="200"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-card">
                <div class="chart-title">
                    <i class="bi bi-calendar-month"></i> Monthly Hours (Last 6 Months)
                </div>
                <canvas id="monthlyChart" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <!-- DTR History Table -->
    <div class="table-card" id="dtr-table">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Company</th>
                        <th>Internship</th>
                        <th>Clock In</th>
                        <th>Clock Out</th>
                        <th>Hours</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dtr_logs)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="empty-state py-3">
                                    <i class="bi bi-calendar"></i>
                                    <p class="mb-0">No DTR records yet.</p>
                                    <small class="text-muted">Your company will record your hours here.</small>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dtr_logs as $log): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $date = $log['clock_in'] instanceof DateTime ? $log['clock_in'] : new DateTime($log['clock_in']);
                                    echo $date->format('M d, Y');
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['company_name']); ?></td>
                                <td><?php echo htmlspecialchars($log['internship_title']); ?></td>
                                <td><?php echo $date->format('h:i A'); ?></td>
                                <td>
                                    <?php 
                                    if ($log['clock_out']) {
                                        $outDate = $log['clock_out'] instanceof DateTime ? $log['clock_out'] : new DateTime($log['clock_out']);
                                        echo $outDate->format('h:i A');
                                    } else {
                                        echo '<span class="text-warning">In Progress</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <strong><?php echo $log['hours'] ? number_format($log['hours'], 2) : '--'; ?></strong>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $log['status'] == 'completed' ? 'status-completed' : 'status-active'; ?>">
                                        <?php echo ucfirst($log['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Info Note -->
    <div class="alert-info">
        <i class="bi bi-info-circle-fill me-2"></i>
        <strong>Note:</strong> Your hours are recorded by your company supervisor. You cannot clock in or out manually. 
        The timer shown above updates automatically when you are on duty.
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Timer for current session
<?php if ($current_session): ?>
    (function() {
        const startTime = new Date('<?php echo $current_session['clock_in'] instanceof DateTime ? $current_session['clock_in']->format('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($current_session['clock_in'])); ?>').getTime();
        const timerDisplay = document.getElementById('timerDisplay');
        
        function updateTimer() {
            const now = new Date().getTime();
            const elapsed = Math.floor((now - startTime) / 1000);
            const hours = Math.floor(elapsed / 3600);
            const minutes = Math.floor((elapsed % 3600) / 60);
            const seconds = elapsed % 60;
            timerDisplay.textContent = hours + 'h ' + minutes + 'm ' + seconds + 's';
        }
        
        updateTimer();
        setInterval(updateTimer, 1000);
    })();
<?php endif; ?>

// Weekly Chart
const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
new Chart(weeklyCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($weekly_hours, 'date')); ?>,
        datasets: [{
            label: 'Hours',
            data: <?php echo json_encode(array_column($weekly_hours, 'hours')); ?>,
            backgroundColor: 'rgba(76, 175, 120, 0.7)',
            borderRadius: 8,
            barPercentage: 0.65
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: (ctx) => ctx.raw + ' hours' } }
        },
        scales: {
            y: { beginAtZero: true, title: { display: true, text: 'Hours', font: { size: 11 } } },
            x: { title: { display: true, text: 'Day', font: { size: 11 } } }
        }
    }
});

// Monthly Chart
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($monthly_hours, 'month')); ?>,
        datasets: [{
            label: 'Hours',
            data: <?php echo json_encode(array_column($monthly_hours, 'hours')); ?>,
            borderColor: 'var(--forest)',
            backgroundColor: 'rgba(76, 175, 120, 0.08)',
            fill: true,
            tension: 0.3,
            pointBackgroundColor: 'var(--mint)',
            pointBorderColor: 'var(--forest)',
            pointRadius: 4,
            pointHoverRadius: 6,
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            tooltip: { callbacks: { label: (ctx) => ctx.raw + ' hours' } }
        },
        scales: {
            y: { beginAtZero: true, title: { display: true, text: 'Hours', font: { size: 11 } } },
            x: { title: { display: true, text: 'Month', font: { size: 11 } } }
        }
    }
});
</script>

</body>
</html>