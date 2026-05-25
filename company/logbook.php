<?php
// company/logbook.php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

requireCompany();

$user_id = $_SESSION['user_id'];
$company = getCompanyData($conn, $user_id);

$success_message = '';
$error_message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $log_id = $_POST['log_id'] ?? 0;
        
        // Verify this logbook entry belongs to an internship of this company
        $stmt = $conn->prepare("SELECT l.id, l.student_id FROM logbook_entries l JOIN internships i ON l.internship_id = i.id WHERE l.id = ? AND i.company_id = ?");
        $stmt->execute([$log_id, $company['id']]);
        $log_entry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($log_entry) {
            if ($_POST['action'] === 'approve') {
                $stmt = $conn->prepare("UPDATE logbook_entries SET status = 'approved' WHERE id = ?");
                $stmt->execute([$log_id]);
                
                // Add a notification for the student
                $notifSql = "INSERT INTO notifications (user_id, title, message, status, created_at) 
                             VALUES ((SELECT user_id FROM students WHERE id = ?), 'Logbook Approved', CONCAT('Your supervisor has approved your logbook entry for Week ', (SELECT week_number FROM logbook_entries WHERE id = ?)), 'unread', NOW())";
                $notifStmt = $conn->prepare($notifSql);
                $notifStmt->execute([$log_entry['student_id'], $log_id]);
                
                $success_message = "Logbook entry approved successfully.";
            } elseif ($_POST['action'] === 'feedback') {
                $feedback = $_POST['feedback'] ?? '';
                $stmt = $conn->prepare("UPDATE logbook_entries SET company_feedback = ?, status = 'commented' WHERE id = ?");
                $stmt->execute([$feedback, $log_id]);
                
                // Add notification
                $notifSql = "INSERT INTO notifications (user_id, title, message, status, created_at) 
                             VALUES ((SELECT user_id FROM students WHERE id = ?), 'New Logbook Feedback', CONCAT('Your supervisor left feedback on your logbook entry for Week ', (SELECT week_number FROM logbook_entries WHERE id = ?)), 'unread', NOW())";
                $notifStmt = $conn->prepare($notifSql);
                $notifStmt->execute([$log_entry['student_id'], $log_id]);
                
                $success_message = "Feedback submitted successfully.";
            }
        } else {
            $error_message = "Invalid logbook entry.";
        }
    }
}

// Fetch all interns for filter dropdown
$interns = [];
$stmt = $conn->prepare("SELECT DISTINCT s.id, s.fullname FROM students s JOIN dtr_logs d ON s.id = d.student_id JOIN internships i ON d.internship_id = i.id WHERE i.company_id = ? ORDER BY s.fullname");
$stmt->execute([$company['id']]);
$interns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch stats
$stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'commented' => 0];
$stmt = $conn->prepare("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN l.status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN l.status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN l.status = 'commented' THEN 1 ELSE 0 END) as commented
        FROM logbook_entries l
        JOIN internships i ON l.internship_id = i.id
        WHERE i.company_id = ?");
$stmt->execute([$company['id']]);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats = [
        'total' => (int)($row['total'] ?? 0),
        'pending' => (int)($row['pending'] ?? 0),
        'approved' => (int)($row['approved'] ?? 0),
        'commented' => (int)($row['commented'] ?? 0)
    ];
}

// Filters
$student_filter = $_GET['student_id'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

$sql = "SELECT l.*, s.fullname as student_name, s.course, s.year_level, s.id as student_real_id, i.title as internship_title
        FROM logbook_entries l
        JOIN students s ON l.student_id = s.id
        JOIN internships i ON l.internship_id = i.id
        WHERE i.company_id = ?";

$params = [$company['id']];

if ($student_filter !== 'all') {
    $sql .= " AND l.student_id = ?";
    $params[] = $student_filter;
}
if ($status_filter !== 'all') {
    $sql .= " AND l.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY l.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Logbooks - GreenBridge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
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

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--sage);
            color: var(--text-dark);
            min-height: 100vh;
        }

        .navbar {
            background-color: var(--forest);
            padding: 1rem 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: white !important;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-chip {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            padding: 0.35rem 1rem;
            border-radius: 40px;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            cursor: pointer;
            transition: all 0.25s;
        }

        .user-chip:hover {
            background: rgba(255,255,255,0.15);
        }

        .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--mint);
            color: var(--forest);
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
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

        .main-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: var(--forest);
            margin-bottom: 0.25rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.25rem;
            border: 1px solid var(--gray-border);
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--forest);
            font-family: 'Playfair Display', serif;
        }

        .stat-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-top: 0.25rem;
        }

        .filter-card {
            background: white;
            border-radius: var(--radius);
            border: 1px solid var(--gray-border);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .logbook-card {
            background: white;
            border-radius: var(--radius);
            border: 1px solid var(--gray-border);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.25s;
        }

        .logbook-card:hover {
            box-shadow: var(--shadow-md);
        }

        .student-meta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .student-avatar {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: var(--mint-light);
            color: var(--forest-mid);
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .student-name {
            font-weight: 600;
            color: var(--forest);
            text-decoration: none;
            transition: color 0.2s;
        }

        .student-name:hover {
            color: var(--mint);
        }

        .badge-status {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.25rem 0.8rem;
            border-radius: 30px;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .badge-pending { background-color: #fef3cd; color: #856404; }
        .badge-approved { background-color: #d1e7dd; color: #0f5132; }
        .badge-commented { background-color: #cff4fc; color: #087990; }

        .narrative-box {
            background: var(--gray-light);
            border-radius: 10px;
            padding: 1rem;
            font-size: 0.9rem;
            line-height: 1.6;
            color: var(--text-dark);
            margin-bottom: 1rem;
            border-left: 3px solid var(--forest-mid);
        }

        .feedback-box {
            background: #fff9db;
            border-radius: 10px;
            padding: 1rem;
            font-size: 0.85rem;
            line-height: 1.5;
            color: #665400;
            border-left: 3px solid var(--gold);
            margin-top: 1rem;
        }

        .btn-action {
            border-radius: 30px;
            padding: 0.4rem 1.2rem;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-approve {
            background-color: var(--forest);
            color: white;
            border: none;
        }

        .btn-approve:hover {
            background-color: var(--forest-mid);
        }

        .btn-submit {
            background-color: var(--mint);
            color: white;
            border: none;
        }

        .btn-submit:hover {
            background-color: #3b9462;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-border);
            color: var(--text-muted);
            box-shadow: var(--shadow-sm);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--mint);
            opacity: 0.6;
        }

        .feedback-input {
            border-radius: var(--radius);
            font-size: 0.85rem;
            resize: none;
        }

        .feedback-input:focus {
            border-color: var(--mint);
            box-shadow: 0 0 0 2px rgba(76,175,120,0.2);
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
    <div class="subnav-item" onclick="location.href='manage_interns.php'">Manage Interns</div>
    <div class="subnav-item active" onclick="location.href='logbook.php'">Logbooks</div>
    <div class="subnav-item" onclick="location.href='profile.php'">Profile</div>
</div>

<div class="main-container">
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="page-header">
        <h2 class="page-title">Intern Logbooks</h2>
        <p class="text-muted">Review, approve, and provide feedback on submitted weekly narrative logbooks from your interns.</p>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Logbooks</div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--gold);">
            <div class="stat-value text-warning"><?php echo $stats['pending']; ?></div>
            <div class="stat-label">Pending Review</div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--forest);">
            <div class="stat-value text-success"><?php echo $stats['approved']; ?></div>
            <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--mint);">
            <div class="stat-value text-info"><?php echo $stats['commented']; ?></div>
            <div class="stat-label">Feedback Provided</div>
        </div>
    </div>

    <!-- Filters Row -->
    <div class="filter-card">
        <form method="GET" class="row g-3 align-items-center">
            <div class="col-md-4">
                <label class="form-label mb-1">Filter by Student</label>
                <select name="student_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Students</option>
                    <?php foreach ($interns as $intern): ?>
                        <option value="<?php echo $intern['id']; ?>" <?php echo $student_filter == $intern['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($intern['fullname']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1">Filter by Status</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Statuses</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="commented" <?php echo $status_filter === 'commented' ? 'selected' : ''; ?>>Feedback Provided</option>
                </select>
            </div>
            <div class="col-md-4 text-md-end mt-4">
                <a href="logbook.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-clockwise"></i> Reset Filters
                </a>
            </div>
        </form>
    </div>

    <!-- Logbook Entries List -->
    <?php if (empty($entries)): ?>
        <div class="empty-state">
            <i class="bi bi-journal-x"></i>
            <h5 class="mt-3">No logbook entries found</h5>
            <p class="text-muted small">Try adjusting your filters or wait for your interns to submit their weekly narratives.</p>
        </div>
    <?php else: ?>
        <div class="logbook-container">
            <?php foreach ($entries as $entry): ?>
                <div class="logbook-card">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div class="student-meta">
                            <div class="student-avatar">
                                <?php echo strtoupper(substr($entry['student_name'], 0, 2)); ?>
                            </div>
                            <div>
                                <a href="student_profile.php?id=<?php echo $entry['student_real_id']; ?>" class="student-name">
                                    <?php echo htmlspecialchars($entry['student_name']); ?>
                                </a>
                                <div class="text-muted small">
                                    <?php echo htmlspecialchars($entry['course']); ?> • Year <?php echo $entry['year_level']; ?>
                                </div>
                                <div class="text-muted small">
                                    Opportunity: <strong><?php echo htmlspecialchars($entry['internship_title']); ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="text-md-end">
                            <span class="badge-status badge-<?php echo $entry['status']; ?>">
                                <i class="bi <?php echo $entry['status'] === 'approved' ? 'bi-check-circle-fill' : ($entry['status'] === 'commented' ? 'bi-chat-left-text-fill' : 'bi-clock-fill'); ?>"></i>
                                <?php echo ucfirst($entry['status'] === 'commented' ? 'Feedback Provided' : $entry['status']); ?>
                            </span>
                            <div class="text-muted small mt-1">
                                Submitted on: <?php echo date('M d, Y', strtotime($entry['created_at'])); ?>
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-3">
                        <h6 class="fw-bold mb-2 text-success">
                            Week <?php echo $entry['week_number']; ?>: <?php echo htmlspecialchars($entry['title']); ?>
                        </h6>
                        <div class="narrative-box">
                            <?php echo nl2br(htmlspecialchars($entry['narrative'])); ?>
                        </div>
                    </div>

                    <?php if ($entry['company_feedback']): ?>
                        <div class="feedback-box">
                            <div class="fw-bold mb-1"><i class="bi bi-chat-dots-fill"></i> Your Feedback:</div>
                            <?php echo nl2br(htmlspecialchars($entry['company_feedback'])); ?>
                        </div>
                    <?php endif; ?>

                    <div class="border-top pt-3 mt-3">
                        <div class="row align-items-center">
                            <div class="col-lg-8 mb-3 mb-lg-0">
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="log_id" value="<?php echo $entry['id']; ?>">
                                    <input type="hidden" name="action" value="feedback">
                                    <textarea name="feedback" rows="2" class="form-control feedback-input" placeholder="Leave feedback or supervisor notes..." required><?php echo htmlspecialchars($entry['company_feedback'] ?? ''); ?></textarea>
                                    <button type="submit" class="btn btn-action btn-submit align-self-end">
                                        <i class="bi bi-send"></i> Feedback
                                    </button>
                                </form>
                            </div>
                            <div class="col-lg-4 text-lg-end">
                                <?php if ($entry['status'] !== 'approved'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="log_id" value="<?php echo $entry['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-action btn-approve w-100 w-lg-auto">
                                            <i class="bi bi-check-lg me-1"></i> Approve Logbook
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-success small fw-bold">
                                        <i class="bi bi-check2-all"></i> Approved & Signed off
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
