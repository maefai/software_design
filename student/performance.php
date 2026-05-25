<?php
// student/performance.php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

requireStudent();

$user_id = $_SESSION['user_id'];
$student = getStudentData($conn, $user_id);

$evaluation = null;
$logbook = [];
$total_hours = 0;

try {
    // Get evaluation data
    $sql = "SELECT e.*, c.company_name 
            FROM evaluations e
            JOIN companies c ON e.company_id = c.id
            WHERE e.student_id = (SELECT id FROM students WHERE user_id = ?)
            ORDER BY e.evaluated_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get logbook entries with feedback
    $sql = "SELECT l.*, c.company_name
            FROM logbook_entries l
            JOIN internships i ON l.internship_id = i.id
            JOIN companies c ON i.company_id = c.id
            WHERE l.student_id = (SELECT id FROM students WHERE user_id = ?)
            ORDER BY l.week_number DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $logbook = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total hours
    $sql = "SELECT IFNULL(SUM(hours), 0) as total FROM dtr_logs 
            WHERE student_id = (SELECT id FROM students WHERE user_id = ?) AND status = 'completed'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $total_hours = $row['total'] ?? 0;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

$required_hours = $student['required_hours'] ?? 400;
$progress_percentage = min(100, round(($total_hours / $required_hours) * 100));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Report - GreenBridge</title>
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
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--forest) 0%, var(--forest-mid) 100%);
            border-radius: var(--radius-lg);
            padding: 1.8rem 2rem;
            color: white;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
        }
        .hero-stat {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--mint);
            font-family: 'Playfair Display', serif;
        }
        .hero-label {
            font-size: 0.7rem;
            opacity: 0.8;
            letter-spacing: 0.5px;
        }
        
        /* Score Cards */
        .score-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 1.25rem;
            border: 1px solid var(--gray-border);
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: box-shadow 0.2s;
        }
        .score-card:hover {
            box-shadow: var(--shadow-md);
        }
        .card-title {
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--forest);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-title i {
            color: var(--mint);
        }
        .score-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--forest);
        }
        .score-bar {
            height: 6px;
            background: var(--gray-border);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        .score-fill {
            height: 100%;
            background: var(--mint);
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        .metric-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.8rem;
        }
        
        /* Logbook Entries */
        .log-entry {
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-border);
        }
        .log-entry:last-child {
            border-bottom: none;
        }
        .log-week {
            font-weight: 700;
            color: var(--forest);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .log-title {
            font-weight: 600;
            font-size: 0.9rem;
            margin: 0.2rem 0;
            color: var(--text-dark);
        }
        .log-status {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.2rem 0.8rem;
            border-radius: 30px;
            display: inline-block;
        }
        .status-approved { background: #e6f7ef; color: #1e6f3f; }
        .status-pending { background: #fef3cd; color: #856404; }
        .status-commented { background: #fef3cd; color: #856404; }
        .feedback-box {
            background: var(--gray-light);
            padding: 0.7rem;
            border-radius: 12px;
            margin-top: 0.6rem;
            border-left: 3px solid var(--mint);
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .skill-tag {
            display: inline-block;
            padding: 0.25rem 0.8rem;
            background: var(--sage);
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 500;
            margin: 0.2rem;
            color: var(--forest-mid);
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
        
        @media (max-width: 768px) {
            .main-container { padding: 1rem; }
            .hero { padding: 1.2rem; text-align: center; }
            .hero .row { gap: 1rem; }
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
        .score-card, .hero {
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
    <div class="subnav-item active">Performance</div>
    <div class="subnav-item" onclick="location.href='dtr.php'">DTR</div>
    <div class="subnav-item" onclick="location.href='logbook.php'">Logbook</div>
    <div class="subnav-item" onclick="location.href='profile.php'">Profile</div>
</div>

<div class="main-container">
    <div class="page-header">
        <div class="page-title">Performance Report</div>
        <div class="page-subtitle">Review your evaluation scores and supervisor feedback</div>
    </div>
    
    <!-- Hero Stats -->
    <div class="hero">
        <div class="row align-items-center">
            <div class="col-md-5">
                <h2 class="mb-1" style="font-family: 'Playfair Display', serif;">Performance Report</h2>
                <p style="color: rgba(255,255,255,0.7); font-size: 0.8rem;">
                    <?php echo $evaluation ? 'Evaluation Completed' : 'Awaiting Evaluation'; ?>
                </p>
            </div>
            <div class="col-md-7">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="hero-stat"><?php echo $evaluation ? number_format($evaluation['overall_score'], 1) : '--'; ?></div>
                        <div class="hero-label">Overall Score</div>
                    </div>
                    <div class="col-4">
                        <div class="hero-stat"><?php echo $total_hours; ?></div>
                        <div class="hero-label">Hours Completed</div>
                    </div>
                    <div class="col-4">
                        <div class="hero-stat"><?php echo $progress_percentage; ?>%</div>
                        <div class="hero-label">Progress</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Score Breakdown Column -->
        <div class="col-md-6">
            <div class="score-card">
                <div class="card-title">
                    <i class="bi bi-bar-chart-steps"></i> Score Breakdown
                </div>
                <?php if ($evaluation): ?>
                    <?php 
                    $metrics = [
                        ['Technical Skills', $evaluation['technical_skills']],
                        ['Work Attitude', $evaluation['work_attitude']],
                        ['Communication', $evaluation['communication']],
                        ['Initiative', $evaluation['initiative']],
                        ['Teamwork', $evaluation['teamwork']]
                    ]; 
                    ?>
                    <?php foreach ($metrics as $m): ?>
                        <div class="mb-3">
                            <div class="metric-row">
                                <span><?php echo $m[0]; ?></span>
                                <span class="fw-bold"><?php echo number_format($m[1], 1); ?> / 5.0</span>
                            </div>
                            <div class="score-bar">
                                <div class="score-fill" style="width: <?php echo ($m[1] / 5) * 100; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-clipboard-data"></i>
                        <p class="mt-2">No evaluation available yet.</p>
                        <small class="text-muted">Your supervisor will evaluate your performance after the internship.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Supervisor Notes Column -->
        <div class="col-md-6">
            <div class="score-card">
                <div class="card-title">
                    <i class="bi bi-chat-square-text"></i> Supervisor Notes
                </div>
                <?php if ($evaluation && $evaluation['comments']): ?>
                    <p style="font-size: 0.85rem; line-height: 1.6; color: var(--text-dark);">
                        <?php echo nl2br(htmlspecialchars($evaluation['comments'])); ?>
                    </p>
                    <?php if ($evaluation['recommendations']): ?>
                        <div class="feedback-box mt-2">
                            <strong><i class="bi bi-lightbulb"></i> Recommendations for Growth:</strong>
                            <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($evaluation['recommendations'])); ?></p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-chat-dots"></i>
                        <p class="mt-2">No supervisor notes available yet.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Skills Endorsed Card -->
            <div class="score-card">
                <div class="card-title">
                    <i class="bi bi-stars"></i> Skills Endorsed
                </div>
                <div>
                    <span class="skill-tag">Laravel</span>
                    <span class="skill-tag">React.js</span>
                    <span class="skill-tag">REST APIs</span>
                    <span class="skill-tag">Team Collaboration</span>
                    <span class="skill-tag">Problem Solving</span>
                </div>
                <small class="text-muted d-block mt-2">Skills recognized by your supervisor during evaluation</small>
            </div>
        </div>
    </div>
    
    <!-- Logbook Feedback Section -->
    <div class="score-card mt-2">
        <div class="card-title">
            <i class="bi bi-journal-bookmark-fill"></i> Logbook Feedback
        </div>
        <?php if (empty($logbook)): ?>
            <div class="empty-state">
                <i class="bi bi-journal-text"></i>
                <p class="mt-2">No logbook entries yet.</p>
                <small class="text-muted">Submit your weekly reports to receive feedback from your supervisor.</small>
            </div>
        <?php else: ?>
            <?php foreach ($logbook as $log): ?>
                <div class="log-entry">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <div class="log-week">Week <?php echo $log['week_number']; ?></div>
                            <div class="log-title"><?php echo htmlspecialchars($log['title']); ?></div>
                            <div class="text-muted small mt-1">
                                <i class="bi bi-building"></i> <?php echo htmlspecialchars($log['company_name']); ?> • 
                                <i class="bi bi-calendar"></i> <?php echo $log['created_at'] instanceof DateTime ? $log['created_at']->format('M d, Y') : date('M d, Y', strtotime($log['created_at'])); ?>
                            </div>
                        </div>
                        <div>
                            <span class="log-status status-<?php echo $log['status']; ?>">
                                <?php echo strtoupper($log['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="mt-2 text-muted small">
                        <?php echo nl2br(htmlspecialchars(substr($log['narrative'], 0, 150))); ?>
                        <?php if (strlen($log['narrative']) > 150): ?>...<?php endif; ?>
                    </div>
                    <?php if ($log['company_feedback']): ?>
                        <div class="feedback-box mt-2">
                            <i class="bi bi-chat-dots"></i> <strong>Supervisor Feedback:</strong>
                            <div class="mt-1"><?php echo nl2br(htmlspecialchars($log['company_feedback'])); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>