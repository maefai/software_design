<?php
// company/manage_internships.php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

// Check if company is logged in
requireCompany();

$user_id = $_SESSION['user_id'];
$company = getCompanyData($conn, $user_id);

$message = '';
$error = '';

try {
    if (isset($_POST['toggle_status'])) {
        $internship_id = $_POST['internship_id'];
        $new_status = $_POST['new_status'];
        
        $stmt = $conn->prepare("UPDATE internships SET status = ?, updated_at = NOW() WHERE id = ? AND company_id = ?");
        $stmt->execute([$new_status, $internship_id, $company['id']]);
        
        $status_text = $new_status == 'open' ? 'opened' : 'closed';
        $_SESSION['success'] = "Internship {$status_text} successfully!";
        header("Location: manage_internships.php");
        exit();
    }

    if (isset($_POST['delete_internship'])) {
        $internship_id = $_POST['internship_id'];
        
        $check = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE internship_id = ?");
        $check->execute([$internship_id]);
        $row = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($row['count'] > 0) {
            $_SESSION['error'] = "Cannot delete internship with existing applications. Close it instead.";
        } else {
            $stmt = $conn->prepare("DELETE FROM internships WHERE id = ? AND company_id = ?");
            $stmt->execute([$internship_id, $company['id']]);
            $_SESSION['success'] = "Internship deleted successfully!";
        }
        header("Location: manage_internships.php");
        exit();
    }

    $internships = [];
    $sql = "SELECT i.*, 
                   (SELECT COUNT(*) FROM applications WHERE internship_id = i.id) as application_count,
                   (SELECT COUNT(*) FROM applications WHERE internship_id = i.id AND status = 'pending') as pending_count
            FROM internships i
            WHERE i.company_id = ?
            ORDER BY i.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$company['id']]);
    $internships = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $open_count = count(array_filter($internships, fn($i) => $i['status'] == 'open'));
    $closed_count = count(array_filter($internships, fn($i) => $i['status'] == 'closed'));
    $total_count = count($internships);

    if (isset($_SESSION['success'])) { $message = $_SESSION['success']; unset($_SESSION['success']); }
    if (isset($_SESSION['error'])) { $error = $_SESSION['error']; unset($_SESSION['error']); }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Internships - GreenBridge</title>
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
        h1, h2, h3, .serif {
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
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
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
            padding: 1.25rem 1rem;
            text-align: center;
            border: 1px solid var(--gray-border);
            transition: all 0.25s ease;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
        }
        .stat-card:hover { 
            transform: translateY(-4px); 
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
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-top: 6px;
        }
        .stat-card i {
            font-size: 1.6rem;
            color: var(--mint);
        }
        
        /* Section Headings */
        .section-heading {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        /* Cards */
        .card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: none;
            margin-bottom: 1.8rem;
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
            font-size: 1rem;
            border-bottom: 1px solid var(--gray-border);
            color: var(--forest);
        }
        .card-header i {
            font-size: 1.1rem;
            margin-right: 8px;
            color: var(--mint);
        }
        .card-body { padding: 1.6rem 1.8rem; }
        
        /* Internship Card Refined */
        .internship-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-border);
            padding: 1.4rem;
            margin-bottom: 1rem;
            transition: all 0.25s ease;
        }
        .internship-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--sage-dk);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 700;
            display: inline-block;
            letter-spacing: 0.3px;
        }
        .status-open { background: var(--mint-light); color: #1e6f3f; border-left: 2px solid var(--mint); }
        .status-closed { background: #fee9e7; color: #b13e3e; border-left: 2px solid #e07c7c; }
        
        .badge-custom {
            background: #eef3ec;
            color: var(--forest-mid);
            padding: 0.25rem 0.7rem;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .btn-sm {
            padding: 0.35rem 0.9rem;
            font-size: 0.75rem;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-outline-secondary-custom {
            background: transparent;
            border: 1px solid var(--gray-border);
            color: var(--text-dark);
        }
        .btn-outline-secondary-custom:hover {
            background: var(--gray-light);
            border-color: var(--forest-lt);
        }
        
        .alert-success {
            background: #e6f7ef;
            color: #1e6f3f;
            padding: 1rem 1.2rem;
            border-radius: 14px;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--mint);
            font-weight: 500;
        }
        .alert-danger {
            background: #fee9e7;
            color: #b13e3e;
            padding: 1rem 1.2rem;
            border-radius: 14px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #e07c7c;
            font-weight: 500;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-muted);
        }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; color: var(--sage-dk); }
        
        /* Filter Buttons */
        .filter-buttons {
            display: flex;
            gap: 0.6rem;
            margin-bottom: 1.8rem;
            flex-wrap: wrap;
        }
        .filter-btn {
            padding: 0.45rem 1.2rem;
            border-radius: 40px;
            font-size: 0.8rem;
            font-weight: 600;
            background: var(--white);
            border: 1px solid var(--gray-border);
            color: var(--forest);
            cursor: pointer;
            transition: all 0.2s;
        }
        .filter-btn:hover {
            background: var(--sage);
            transform: translateY(-1px);
        }
        .filter-btn.active {
            background: var(--forest);
            color: white;
            border-color: var(--forest);
            box-shadow: 0 2px 6px rgba(26,58,36,0.2);
        }
        
        /* Tips List */
        .tips-list {
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
        }
        .tips-list li {
            padding: 0.4rem 0;
            padding-left: 1.5rem;
            position: relative;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        .tips-list li:before {
            content: "▹";
            position: absolute;
            left: 0;
            color: var(--mint);
            font-weight: 600;
        }
        
        .text-link {
            color: var(--forest-mid);
            text-decoration: none;
            font-weight: 500;
        }
        .text-link:hover {
            color: var(--mint);
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .main-container { padding: 1rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 0.8rem; }
            .internship-card .d-flex { flex-direction: column; gap: 1rem; }
            .card-body { padding: 1.2rem; }
            .page-title { font-size: 1.5rem; }
            .subnav { padding: 0 12px; }
        }
        
        /* animation */
        .fade-in {
            animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px);}
            to { opacity: 1; transform: translateY(0);}
        }
        .internship-card {
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
    <div class="subnav-item active" onclick="location.href='manage_internships.php'">Manage Internships</div>
    <div class="subnav-item" onclick="location.href='manage_applications.php'">Applications</div>
    <div class="subnav-item" onclick="location.href='chat.php'">Chat</div>
    <div class="subnav-item" onclick="location.href='scout_students.php'">Scout Students</div>
    <div class="subnav-item" onclick="location.href='manage_interns.php'">Manage Interns</div>
    <div class="subnav-item" onclick="location.href='logbook.php'">Logbooks</div>
    <div class="subnav-item" onclick="location.href='profile.php'">Profile</div>
</div>

<div class="main-container">
    <?php if ($message): ?>
        <div class="alert-success">
            <i class="bi bi-check-circle-fill me-2" style="font-size:0.9rem;"></i> 
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2" style="font-size:0.9rem;"></i> 
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <div class="page-header">
        <div class="page-title">Manage Internships</div>
        <div class="page-subtitle">View, edit, and manage all your internship postings in one place</div>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card" onclick="showAll()">
            <div class="stat-value"><?php echo $total_count; ?></div>
            <div class="stat-label">Total Postings</div>
        </div>
        <div class="stat-card" onclick="filterByStatus('open')">
            <div class="stat-value"><?php echo $open_count; ?></div>
            <div class="stat-label">Open Positions</div>
        </div>
        <div class="stat-card" onclick="filterByStatus('closed')">
            <div class="stat-value"><?php echo $closed_count; ?></div>
            <div class="stat-label">Closed Positions</div>
        </div>
        <div class="stat-card" onclick="location.href='post_internship.php'">
            <div><i class="bi bi-plus-circle"></i></div>
            <div class="stat-label mt-2">New Posting</div>
        </div>
    </div>
    
    <!-- Filter Buttons -->
    <div class="filter-buttons">
        <button class="filter-btn active" onclick="showAll()">All Internships</button>
        <button class="filter-btn" onclick="filterByStatus('open')">Open</button>
        <button class="filter-btn" onclick="filterByStatus('closed')">Closed</button>
    </div>
    
    <?php if (empty($internships)): ?>
        <div class="card">
            <div class="card-body empty-state">
                <i class="bi bi-briefcase"></i>
                <p class="mt-3">No internships posted yet. Start building opportunities for students.</p>
                <a href="post_internship.php" class="btn btn-primary rounded-pill px-4 py-2 mt-2" style="background: var(--forest); border:none; font-size:0.8rem; text-decoration:none; display:inline-block;">Post Your First Internship</a>
            </div>
        </div>
    <?php else: ?>
        <div id="internships-list">
            <?php foreach ($internships as $internship): ?>
                <div class="internship-card" data-status="<?php echo $internship['status']; ?>">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                <h5 class="mb-0 fw-bold" style="color: var(--forest);"><?php echo htmlspecialchars($internship['title']); ?></h5>
                                <span class="status-badge status-<?php echo $internship['status']; ?>">
                                    <?php echo ucfirst($internship['status']); ?>
                                </span>
                            </div>
                            <div class="text-muted small mb-2 d-flex flex-wrap gap-3">
                                <span><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($internship['location']); ?></span>
                                <span><i class="bi bi-laptop"></i> <?php echo htmlspecialchars($internship['setup']); ?></span>
                                <span><i class="bi bi-clock"></i> <?php echo $internship['duration_hours']; ?> hours</span>
                            </div>
                            <div class="d-flex gap-2 mb-3 flex-wrap">
                                <span class="badge-custom"><?php echo htmlspecialchars($internship['type']); ?></span>
                                <span class="badge-custom"><?php echo htmlspecialchars($internship['department']); ?></span>
                                <?php if ($internship['stipend']): ?>
                                    <span class="badge-custom"><i class="bi bi-cash-stack"></i> <?php echo htmlspecialchars($internship['stipend']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="small text-muted mb-1">
                                <i class="bi bi-people"></i> <?php echo $internship['application_count']; ?> applicant(s)
                                <?php if ($internship['pending_count'] > 0): ?>
                                    (<span class="fw-semibold" style="color: var(--gold);"><?php echo $internship['pending_count']; ?> pending review</span>)
                                <?php endif; ?>
                            </div>
                            <?php if ($internship['application_deadline']): ?>
                                <div class="small text-muted">
                                    <i class="bi bi-calendar"></i> Deadline: 
                                    <?php echo $internship['application_deadline'] instanceof DateTime ? $internship['application_deadline']->format('M d, Y') : date('M d, Y', strtotime($internship['application_deadline'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex gap-2 flex-wrap">
                            <?php if ($internship['status'] == 'open'): ?>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="internship_id" value="<?php echo $internship['id']; ?>">
                                    <input type="hidden" name="new_status" value="closed">
                                    <button type="submit" name="toggle_status" class="btn btn-sm btn-outline-secondary-custom" onclick="return confirm('Close this internship? Students will no longer be able to apply.')" style="border-color:#e0a800; color:#a47100;">
                                        <i class="bi bi-lock"></i> Close
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="internship_id" value="<?php echo $internship['id']; ?>">
                                    <input type="hidden" name="new_status" value="open">
                                    <button type="submit" name="toggle_status" class="btn btn-sm btn-outline-secondary-custom" onclick="return confirm('Reopen this internship? Students will be able to apply again.')" style="border-color:var(--mint); color:var(--mint);">
                                        <i class="bi bi-unlock"></i> Reopen
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <a href="post_internship.php?edit=<?php echo $internship['id']; ?>" class="btn btn-sm btn-outline-secondary-custom">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            
                            <?php if ($internship['application_count'] == 0): ?>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="internship_id" value="<?php echo $internship['id']; ?>">
                                    <button type="submit" name="delete_internship" class="btn btn-sm btn-danger" style="background:#e07c7c; border:none;" onclick="return confirm('Delete this internship? This action cannot be undone.')">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center flex-wrap" style="border-color: var(--gray-border);">
                        <div class="small text-muted">
                            <i class="bi bi-calendar-plus"></i> Posted: 
                            <?php echo $internship['created_at'] instanceof DateTime ? $internship['created_at']->format('M d, Y') : date('M d, Y', strtotime($internship['created_at'])); ?>
                            <?php if ($internship['updated_at']): ?>
                                • Updated: <?php echo $internship['updated_at'] instanceof DateTime ? $internship['updated_at']->format('M d, Y') : date('M d, Y', strtotime($internship['updated_at'])); ?>
                            <?php endif; ?>
                        </div>
                        <a href="view_applications.php?internship=<?php echo $internship['id']; ?>" class="text-link small">
                            <i class="bi bi-eye"></i> View Applications →
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Tips & Guidelines Card -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-info-circle"></i> Internship Management Guide
        </div>
        <div class="card-body">
            <ul class="tips-list">
                <li><strong>Open</strong> – Students can discover and apply to this internship.</li>
                <li><strong>Closed</strong> – Applications are no longer accepted, but existing applications remain visible.</li>
                <li><strong>Delete</strong> – Only available when no students have applied to prevent data loss.</li>
                <li><strong>Edit</strong> – Update description, requirements, or deadlines at any time.</li>
                <li><strong>Quick filters</strong> – Use status cards or filter buttons to view specific groups.</li>
                <li>Monitor applications directly from the "View Applications" link.</li>
            </ul>
            <div class="mt-3 pt-2 border-top" style="border-color: var(--gray-border);">
                <small class="text-muted"><i class="bi bi-lightbulb"></i> Tip: Regularly review pending applications to attract active candidates.</small>
            </div>
        </div>
    </div>
</div>

<script>
function filterByStatus(status) {
    // Update active button
    const buttons = document.querySelectorAll('.filter-btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    // Find the button that matches status or text
    if (status === 'open') {
        buttons[1]?.classList.add('active');
    } else if (status === 'closed') {
        buttons[2]?.classList.add('active');
    } else {
        buttons[0]?.classList.add('active');
    }
    
    // Filter cards
    const cards = document.querySelectorAll('.internship-card');
    cards.forEach(card => {
        if (card.dataset.status === status) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

function showAll() {
    // Update active button
    const buttons = document.querySelectorAll('.filter-btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    if(buttons[0]) buttons[0].classList.add('active');
    
    // Show all cards
    const cards = document.querySelectorAll('.internship-card');
    cards.forEach(card => {
        card.style.display = '';
    });
}

// Add smooth event listeners to stat cards without breaking existing onclick
document.querySelectorAll('.stat-card').forEach(card => {
    card.addEventListener('click', function(e) {
        // already handled by inline onclick, but prevent double triggers if needed
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>