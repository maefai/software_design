<?php
// student/community.php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

requireStudent();

$user_id = $_SESSION['user_id'];
$student = getStudentData($conn, $user_id);

$students = [];
try {
    $sql = "SELECT s.id, s.fullname, s.course, s.year_level,
                   CASE WHEN f.status = 'accepted' THEN 'connected' WHEN f.status = 'pending' THEN 'pending' ELSE 'not' END as status
            FROM students s
            LEFT JOIN friends f ON f.friend_id = s.id AND f.student_id = (SELECT id FROM students WHERE user_id = ?)
            WHERE s.user_id != ?
            ORDER BY s.fullname";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id, $user_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community - GreenBridge</title>
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
            max-width: 1200px;
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
        
        /* Community Grid */
        .community-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        /* Community Card */
        .comm-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-border);
            padding: 1.6rem;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }
        .comm-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--sage-dk);
        }
        .comm-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--forest-lt) 0%, var(--mint) 100%);
            display: grid;
            place-items: center;
            color: white;
            font-weight: 700;
            font-size: 1.8rem;
            margin: 0 auto 1rem;
            box-shadow: 0 4px 12px rgba(26,58,36,0.15);
        }
        .comm-name { 
            font-weight: 800; 
            font-size: 1.1rem; 
            margin-bottom: 0.3rem;
            color: var(--forest);
        }
        .comm-details { 
            font-size: 0.75rem; 
            color: var(--text-muted); 
            margin-bottom: 0.8rem;
        }
        .skills-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            justify-content: center;
            margin: 0.8rem 0;
        }
        .skill-tag {
            display: inline-block;
            font-size: 0.65rem;
            padding: 0.25rem 0.7rem;
            background: var(--sage);
            border-radius: 30px;
            color: var(--forest-mid);
            font-weight: 500;
        }
        .btn-connect {
            width: 100%;
            padding: 0.6rem;
            background: var(--forest);
            color: white;
            border: none;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-connect:hover {
            background: var(--forest-mid);
            transform: translateY(-1px);
        }
        .btn-connected {
            background: #cfdecb;
            color: var(--forest);
            cursor: default;
        }
        .btn-connected:hover {
            transform: none;
            background: #cfdecb;
        }
        .btn-pending {
            background: #fef3cd;
            color: #856404;
            cursor: default;
        }
        .btn-pending:hover {
            transform: none;
            background: #fef3cd;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: var(--white);
            border-radius: var(--radius-lg);
            color: var(--text-muted);
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 0.75rem;
            opacity: 0.4;
            color: var(--sage-dk);
        }
        
        /* Filter Bar */
        .filter-bar {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 1rem 1.5rem;
            margin-bottom: 1.8rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
            border: 1px solid var(--gray-border);
            box-shadow: var(--shadow-sm);
        }
        .filter-search {
            flex: 1;
            min-width: 200px;
            position: relative;
        }
        .filter-search input {
            width: 100%;
            padding: 0.6rem 1rem 0.6rem 2.3rem;
            border: 1px solid var(--gray-border);
            border-radius: 40px;
            font-size: 0.8rem;
            background: var(--white);
        }
        .filter-search input:focus {
            outline: none;
            border-color: var(--mint);
        }
        .filter-search i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .filter-stats {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        
        @media (max-width: 768px) {
            .main-container { padding: 1rem; }
            .community-grid { grid-template-columns: 1fr; gap: 1rem; }
            .page-title { font-size: 1.5rem; }
            .subnav { padding: 0 12px; }
            .filter-bar { flex-direction: column; align-items: stretch; }
        }
        
        /* Animation */
        .fade-in {
            animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px);}
            to { opacity: 1; transform: translateY(0);}
        }
        .comm-card {
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
    <div class="subnav-item active">Community</div>
    <div class="subnav-item" onclick="location.href='opportunities.php'">Opportunities</div>
    <div class="subnav-item" onclick="location.href='applications.php'">Applications</div>
    <div class="subnav-item" onclick="location.href='chat.php'">Chat</div>
    <div class="subnav-item" onclick="location.href='performance.php'">Performance</div>
    <div class="subnav-item" onclick="location.href='dtr.php'">DTR</div>
    <div class="subnav-item" onclick="location.href='logbook.php'">Logbook</div>
    <div class="subnav-item" onclick="location.href='profile.php'">Profile</div>
</div>

<div class="main-container">
    <div class="page-header">
        <div class="page-title">Community</div>
        <div class="page-subtitle">Connect with fellow DLSU interns and build your professional network</div>
    </div>
    
    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-search">
            <i class="bi bi-search"></i>
            <input type="text" id="searchInput" placeholder="Search by name or course...">
        </div>
        <div class="filter-stats" id="resultCount">
            Showing <span id="visibleCount"><?php echo count($students); ?></span> of <?php echo count($students); ?> members
        </div>
    </div>
    
    <div class="community-grid" id="communityGrid">
        <?php if (empty($students)): ?>
            <div class="empty-state" style="grid-column: 1/-1;">
                <i class="bi bi-people"></i>
                <p class="mt-2">No community members found.</p>
            </div>
        <?php else: ?>
            <?php foreach ($students as $person): ?>
                <div class="comm-card" 
                     data-name="<?php echo strtolower($person['fullname']); ?>"
                     data-course="<?php echo strtolower($person['course']); ?>">
                    <div class="comm-avatar"><?php echo strtoupper(substr($person['fullname'], 0, 2)); ?></div>
                    <div class="comm-name"><?php echo htmlspecialchars($person['fullname']); ?></div>
                    <div class="comm-details"><?php echo htmlspecialchars($person['course']); ?> • Year <?php echo $person['year_level']; ?></div>
                    <div class="skills-container">
                        <?php 
                        $displaySkills = ['Leadership', 'Communication', 'Teamwork'];
                        if (strpos(strtolower($person['course']), 'computer') !== false || strpos(strtolower($person['course']), 'it') !== false) {
                            $displaySkills = ['Python', 'React', 'Problem Solving'];
                        } elseif (strpos(strtolower($person['course']), 'business') !== false || strpos(strtolower($person['course']), 'account') !== false) {
                            $displaySkills = ['Analytical', 'Excel', 'Presentation'];
                        } elseif (strpos(strtolower($person['course']), 'engineer') !== false) {
                            $displaySkills = ['AutoCAD', 'MATLAB', 'Critical Thinking'];
                        }
                        foreach ($displaySkills as $skill): ?>
                            <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($person['status'] == 'connected'): ?>
                        <button class="btn-connect btn-connected" disabled>
                            <i class="bi bi-check-circle"></i> Connected
                        </button>
                    <?php elseif ($person['status'] == 'pending'): ?>
                        <button class="btn-connect btn-pending" disabled>
                            <i class="bi bi-hourglass-split"></i> Request Sent
                        </button>
                    <?php else: ?>
                        <form method="POST" action="dashboard.php">
                            <input type="hidden" name="friend_id" value="<?php echo $person['id']; ?>">
                            <button type="submit" name="add_friend" class="btn-connect">
                                <i class="bi bi-person-plus"></i> Connect
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Search/filter functionality
const searchInput = document.getElementById('searchInput');
const cards = document.querySelectorAll('.comm-card');
const visibleCountSpan = document.getElementById('visibleCount');

function filterMembers() {
    const searchTerm = searchInput.value.toLowerCase();
    let visible = 0;
    
    cards.forEach(card => {
        const name = card.dataset.name || '';
        const course = card.dataset.course || '';
        
        if (searchTerm === '' || name.includes(searchTerm) || course.includes(searchTerm)) {
            card.style.display = '';
            visible++;
        } else {
            card.style.display = 'none';
        }
    });
    
    if (visibleCountSpan) {
        visibleCountSpan.textContent = visible;
    }
}

searchInput.addEventListener('input', filterMembers);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>