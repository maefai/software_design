<?php
// student/logbook.php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

requireStudent();

$user_id = $_SESSION['user_id'];
$student = getStudentData($conn, $user_id);

$message = '';
$error = '';
$logbook = [];

try {
    // Get current internship
    $current_internship = null;
    $sql = "SELECT i.*, c.company_name FROM applications a JOIN internships i ON a.internship_id = i.id JOIN companies c ON i.company_id = c.id WHERE a.student_id = (SELECT id FROM students WHERE user_id = ?) AND a.status = 'accepted'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $current_internship = $stmt->fetch(PDO::FETCH_ASSOC);

    // Handle submission
    if (isset($_POST['submit_logbook'])) {
        $week = $_POST['week_number'] ?? '';
        $title = $_POST['title'] ?? '';
        $narrative = $_POST['narrative'] ?? '';
        $internship_id = $current_internship['id'] ?? 0;
        
        if (empty($week) || empty($title) || empty($narrative)) {
            $error = "Please fill in all fields.";
        } elseif (!$current_internship) {
            $error = "You don't have an active internship.";
        } else {
            $check = $conn->prepare("SELECT id FROM logbook_entries WHERE student_id = (SELECT id FROM students WHERE user_id = ?) AND week_number = ?");
            $check->execute([$user_id, $week]);
            if ($check->fetch()) {
                $error = "Week $week already submitted.";
            } else {
                $sql = "INSERT INTO logbook_entries (student_id, internship_id, week_number, title, narrative, status, created_at) VALUES ((SELECT id FROM students WHERE user_id = ?), ?, ?, ?, ?, 'pending', NOW())";
                $insertStmt = $conn->prepare($sql);
                if ($insertStmt->execute([$user_id, $internship_id, $week, $title, $narrative])) {
                    $message = "Week $week submitted successfully!";
                    $_POST = [];
                } else { 
                    $error = "Failed to submit."; 
                }
            }
        }
    }

    // Get all entries
    $sql = "SELECT l.*, c.company_name FROM logbook_entries l JOIN internships i ON l.internship_id = i.id JOIN companies c ON i.company_id = c.id WHERE l.student_id = (SELECT id FROM students WHERE user_id = ?) ORDER BY l.week_number DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $logbook = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

$current_week = date('W');
$submitted = false;
foreach ($logbook as $e) { if ($e['week_number'] == $current_week) { $submitted = true; break; } }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logbook - GreenBridge</title>
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
            max-width: 1000px;
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
        
        /* Cards */
        .card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-border);
            overflow: hidden;
            margin-bottom: 1.8rem;
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
            padding: 1.5rem;
        }
        
        /* Form Styles */
        .form-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--forest);
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .form-control {
            border: 1px solid var(--gray-border);
            border-radius: 10px;
            padding: 0.65rem 1rem;
            font-size: 0.85rem;
            width: 100%;
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: var(--forest-lt);
            box-shadow: 0 0 0 3px rgba(61,122,82,0.1);
            outline: none;
        }
        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }
        
        .btn-primary {
            background: var(--forest);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background: var(--forest-mid);
            transform: translateY(-1px);
        }
        
        /* Active Internship Badge */
        .active-internship-badge {
            background: var(--mint-light);
            border-radius: 12px;
            padding: 0.8rem 1rem;
            margin-bottom: 1.2rem;
            border-left: 3px solid var(--mint);
        }
        
        /* Log Entries */
        .log-entry {
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-border);
        }
        .log-entry:last-child {
            border-bottom: none;
        }
        .log-week {
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--forest);
        }
        .log-title {
            font-weight: 700;
            font-size: 0.95rem;
            margin: 0.2rem 0;
            color: var(--text-dark);
        }
        .log-status {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.25rem 0.8rem;
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
        
        /* Alerts */
        .alert-success {
            background: #e6f7ef;
            color: #1e6f3f;
            padding: 0.9rem 1.2rem;
            border-radius: 14px;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--mint);
            font-weight: 500;
        }
        .alert-danger {
            background: #fee9e7;
            color: #b13e3e;
            padding: 0.9rem 1.2rem;
            border-radius: 14px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #e07c7c;
            font-weight: 500;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
        }
        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
            opacity: 0.4;
        }
        
        @media (max-width: 768px) {
            .main-container { padding: 1rem; }
            .page-title { font-size: 1.5rem; }
            .subnav { padding: 0 12px; }
            .card-body { padding: 1rem; }
        }
        
        /* Animation */
        .fade-in {
            animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px);}
            to { opacity: 1; transform: translateY(0);}
        }
        .card, .log-entry {
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
    <div class="subnav-item" onclick="location.href='dtr.php'">DTR</div>
    <div class="subnav-item active">Logbook</div>
    <div class="subnav-item" onclick="location.href='profile.php'">Profile</div>
</div>

<div class="main-container">
    <div class="page-header">
        <div class="page-title">Logbook</div>
        <div class="page-subtitle">Document your weekly accomplishments and receive supervisor feedback</div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert-success">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <!-- Submit Weekly Report Card -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-journal-plus"></i> Submit Weekly Report
        </div>
        <div class="card-body">
            <?php if (!$current_internship): ?>
                <div class="alert-danger">
                    <i class="bi bi-info-circle-fill me-2"></i> You don't have an active internship yet. Once accepted, you can submit reports here.
                </div>
            <?php else: ?>
                <div class="active-internship-badge">
                    <i class="bi bi-briefcase-fill me-2"></i>
                    <strong>Active Internship:</strong> <?php echo htmlspecialchars($current_internship['company_name']); ?> - <?php echo htmlspecialchars($current_internship['title']); ?>
                </div>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Week Number</label>
                            <input type="number" name="week_number" class="form-control" value="<?php echo $submitted ? '' : $current_week; ?>" required>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label">Report Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g., API Integration, Database Design, Market Research" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Narrative / Accomplishments</label>
                        <textarea name="narrative" class="form-control" rows="5" placeholder="Describe your tasks, learnings, challenges, and achievements this week..." required></textarea>
                    </div>
                    <div class="mt-4">
                        <button type="submit" name="submit_logbook" class="btn-primary">
                            <i class="bi bi-send"></i> Submit Report
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Previous Reports Card -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-journal-bookmark-fill"></i> Previous Reports
        </div>
        <div class="card-body">
            <?php if (empty($logbook)): ?>
                <div class="empty-state">
                    <i class="bi bi-journal-text"></i>
                    <p class="mt-2">No logbook entries yet.</p>
                    <small class="text-muted">Submit your first weekly report to start documenting your journey.</small>
                </div>
            <?php else: ?>
                <?php foreach ($logbook as $entry): ?>
                    <div class="log-entry">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <div class="log-week">Week <?php echo $entry['week_number']; ?></div>
                                <div class="log-title"><?php echo htmlspecialchars($entry['title']); ?></div>
                                <div class="text-muted small mt-1">
                                    <i class="bi bi-building"></i> <?php echo htmlspecialchars($entry['company_name']); ?> • 
                                    <i class="bi bi-calendar"></i> <?php echo $entry['created_at'] instanceof DateTime ? $entry['created_at']->format('M d, Y') : date('M d, Y', strtotime($entry['created_at'])); ?>
                                </div>
                            </div>
                            <div>
                                <span class="log-status status-<?php echo $entry['status']; ?>">
                                    <?php echo strtoupper($entry['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="mt-2 text-muted small">
                            <?php echo nl2br(htmlspecialchars(substr($entry['narrative'], 0, 200))); ?>
                            <?php if (strlen($entry['narrative']) > 200): ?>...<?php endif; ?>
                        </div>
                        <?php if ($entry['company_feedback']): ?>
                            <div class="feedback-box mt-2">
                                <i class="bi bi-chat-dots"></i> <strong>Supervisor Feedback:</strong>
                                <div class="mt-1"><?php echo nl2br(htmlspecialchars($entry['company_feedback'])); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>