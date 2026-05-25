<?php
// student/bookmarks.php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

requireStudent();

$user_id = $_SESSION['user_id'];
$student = getStudentData($conn, $user_id);

// Get bookmarked opportunities (you'll need a bookmarks table)
// For now, using sample data
$bookmarks = [
    ['id' => 2, 'title' => 'Data Analyst (Remote)', 'company' => 'IBM Philippines', 'location' => 'Remote', 'type' => 'Internship', 'department' => 'CEAT/CLAC']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookmarks - GreenBridge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --forest: #1a3a24;
            --forest-mid: #2d5a3d;
            --mint: #4caf78;
            --sage: #e8f0e9;
        }
        
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f4faf6; }
        .navbar { background: var(--forest) !important; padding: 0.8rem 2rem; }
        .subnav { background: var(--forest-mid); padding: 0 28px; display: flex; gap: 2px; }
        .subnav-item { padding: 12px 18px; font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.6); cursor: pointer; border-bottom: 2.5px solid transparent; }
        .subnav-item.active { color: white; border-bottom-color: var(--mint); }
        
        .main-content { padding: 2rem; max-width: 1100px; margin: 0 auto; }
        .page-title { font-size: 1.3rem; font-weight: 800; margin-bottom: 0.2rem; }
        .page-subtitle { font-size: 0.85rem; color: #7a9882; margin-bottom: 1.5rem; }
        
        .bookmark-card {
            background: white;
            border-radius: 18px;
            border: 1px solid #e0ede7;
            padding: 1.2rem;
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .bookmark-type { font-size: 0.7rem; font-weight: 700; padding: 3px 10px; border-radius: 6px; background: var(--forest-mid); color: white; display: inline-block; }
        .bookmark-title { font-weight: 700; font-size: 1rem; margin: 0.5rem 0 0.2rem; }
        .bookmark-company { font-size: 0.8rem; color: #4a6350; }
        .bookmark-tags { display: flex; gap: 5px; margin: 0.5rem 0; }
        .bookmark-tag { font-size: 0.65rem; padding: 2px 8px; background: var(--sage); border-radius: 20px; }
        .btn-details { background: var(--forest); color: white; border: none; padding: 0.3rem 1rem; border-radius: 6px; font-size: 0.75rem; cursor: pointer; }
        
        @media (max-width: 768px) { .main-content { padding: 1rem; } .bookmark-card { flex-direction: column; } }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php"><i class="bi bi-tree-fill me-2"></i>GREEN BRIDGE</a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <div class="user-avatar" style="width:32px; height:32px; background:var(--mint); border-radius:8px; display:grid; place-items:center;"><?php echo strtoupper(substr($student['fullname'], 0, 2)); ?></div>
            <span style="color:white; font-size:13px;"><?php echo explode(' ', $student['fullname'])[0]; ?></span>
        </div>
    </div>
</nav>

<div class="subnav">
    <div class="subnav-item" onclick="window.location.href='dashboard.php'">Home</div>
    <div class="subnav-item" onclick="window.location.href='community.php'">Community</div>
    <div class="subnav-item" onclick="window.location.href='applications.php'">Applications</div>
    <div class="subnav-item" onclick="window.location.href='performance.php'">Performance Report</div>
    <div class="subnav-item active">Bookmarks</div>
    <div class="subnav-item" onclick="window.location.href='dashboard.php'"><i class="bi bi-grid-1x2-fill me-1"></i> My Dashboard</div>
</div>

<div class="main-content">
    <div class="page-title">Bookmarks</div>
    <div class="page-subtitle">Opportunities you've saved for later</div>
    
    <?php if (empty($bookmarks)): ?>
        <div class="text-center py-5" style="color:#7a9882">
            <i class="bi bi-bookmark" style="font-size: 3rem;"></i>
            <p class="mt-3">No bookmarks yet. Click the bookmark icon on any opportunity to save it here.</p>
        </div>
    <?php else: ?>
        <?php foreach ($bookmarks as $bookmark): ?>
            <div class="bookmark-card">
                <div style="flex:1">
                    <span class="bookmark-type"><?php echo $bookmark['type']; ?></span>
                    <div class="bookmark-title"><?php echo htmlspecialchars($bookmark['title']); ?></div>
                    <div class="bookmark-company"><?php echo htmlspecialchars($bookmark['company']); ?> · <?php echo $bookmark['location']; ?></div>
                    <div class="bookmark-tags">
                        <span class="bookmark-tag"><?php echo $bookmark['department']; ?></span>
                        <span class="bookmark-tag"><?php echo $bookmark['type']; ?></span>
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button class="btn-details">Details</button>
                    <button class="btn-details" style="background:var(--forest-mid);">Apply</button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>