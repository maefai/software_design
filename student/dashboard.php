<?php
// student/dashboard.php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

requireStudent();

$user_id = $_SESSION['user_id'];
$student = getStudentData($conn, $user_id);

$stats = [];
$friendRequests = [];
$friends = [];
$posts = [];
$recommended = [];

try {
    // Get student stats
    $sql = "SELECT 
                (SELECT IFNULL(SUM(hours), 0) FROM dtr_logs WHERE student_id = s.id AND status = 'completed') as total_hours,
                (SELECT COUNT(*) FROM friends WHERE student_id = s.id AND status = 'accepted') as total_friends,
                (SELECT COUNT(*) FROM posts WHERE user_id = ? AND status = 'approved') as total_posts,
                (SELECT COUNT(*) FROM applications WHERE student_id = s.id AND status = 'pending') as pending_apps
            FROM students s WHERE s.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id, $user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get friend requests
    $sql = "SELECT f.*, s.fullname, s.course
            FROM friends f
            JOIN students s ON f.friend_id = s.id
            WHERE f.student_id = (SELECT id FROM students WHERE user_id = ?) AND f.status = 'pending'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $friendRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get friends list
    $sql = "SELECT s.id, s.fullname, s.course, s.year_level
            FROM friends f
            JOIN students s ON f.friend_id = s.id
            WHERE f.student_id = (SELECT id FROM students WHERE user_id = ?) AND f.status = 'accepted'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get posts feed
    $sql = "SELECT p.*, 
                   CASE WHEN u.user_type = 'student' THEN s.fullname ELSE c.company_name END as author_name,
                   u.user_type as author_type,
                   (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
                   (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_liked,
                   (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN students s ON u.id = s.user_id AND u.user_type = 'student'
            LEFT JOIN companies c ON u.id = c.user_id AND u.user_type = 'company'
            WHERE p.status = 'approved'
            ORDER BY p.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recommended students (Translated NEWID() to RAND())
    $sql = "SELECT id, fullname, course, year_level FROM students WHERE user_id != ? AND status = 'active' ORDER BY RAND() LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $recommended = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle actions
    if (isset($_POST['create_post'])) {
        $content = $_POST['post_content'] ?? '';
        if (!empty($content)) {
            $conn->prepare("INSERT INTO posts (user_id, content, type, status, created_at) VALUES (?, ?, 'post', 'pending', NOW())")->execute([$user_id, $content]);
            $_SESSION['success'] = "Post submitted for review!";
        }
        header("Location: dashboard.php");
        exit();
    }

    if (isset($_POST['like_post'])) {
        $post_id = $_POST['post_id'];
        $check = $conn->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
        $check->execute([$post_id, $user_id]);
        if ($check->fetch()) {
            $conn->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?")->execute([$post_id, $user_id]);
        } else {
            $conn->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)")->execute([$post_id, $user_id]);
        }
        header("Location: dashboard.php");
        exit();
    }

    if (isset($_POST['add_comment'])) {
        $post_id = $_POST['post_id'];
        $comment = $_POST['comment_content'] ?? '';
        if (!empty($comment)) {
            $conn->prepare("INSERT INTO post_comments (post_id, user_id, comment) VALUES (?, ?, ?)")->execute([$post_id, $user_id, $comment]);
        }
        header("Location: dashboard.php");
        exit();
    }

    if (isset($_POST['add_friend'])) {
        $friend_id = $_POST['friend_id'];
        $conn->prepare("INSERT INTO friends (student_id, friend_id, status) VALUES ((SELECT id FROM students WHERE user_id = ?), ?, 'pending')")->execute([$user_id, $friend_id]);
        $_SESSION['success'] = "Friend request sent!";
        header("Location: dashboard.php");
        exit();
    }

    if (isset($_POST['accept_friend'])) {
        $friend_id = $_POST['friend_id'];
        $conn->prepare("UPDATE friends SET status = 'accepted' WHERE student_id = (SELECT id FROM students WHERE user_id = ?) AND friend_id = ?")->execute([$user_id, $friend_id]);
        $_SESSION['success'] = "Friend request accepted!";
        header("Location: dashboard.php");
        exit();
    }

    if (isset($_POST['reject_friend'])) {
        $friend_id = $_POST['friend_id'];
        $conn->prepare("DELETE FROM friends WHERE student_id = (SELECT id FROM students WHERE user_id = ?) AND friend_id = ?")->execute([$user_id, $friend_id]);
        $_SESSION['success'] = "Friend request rejected.";
        header("Location: dashboard.php");
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
}

$total_hours = $stats['total_hours'] ?? 0;
$required_hours = $student['required_hours'] ?? 400;
$progress = min(100, round(($total_hours / $required_hours) * 100));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - GreenBridge</title>
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
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        /* Welcome Section */
        .welcome-section {
            margin-bottom: 1.8rem;
        }
        .welcome-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--forest);
            margin-bottom: 0.2rem;
        }
        .welcome-subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
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
        .progress { height: 6px; background: #e0ede7; border-radius: 10px; margin-top: 0.5rem; }
        .progress-bar { background: var(--mint); border-radius: 10px; }
        
        /* Create Post */
        .create-post {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-border);
            padding: 1.2rem;
            margin-bottom: 1.8rem;
            box-shadow: var(--shadow-sm);
            transition: box-shadow 0.2s;
        }
        .create-post:hover {
            box-shadow: var(--shadow-md);
        }
        .create-post textarea {
            width: 100%;
            border: 1px solid var(--gray-border);
            border-radius: 12px;
            padding: 0.8rem;
            resize: none;
            font-size: 0.85rem;
            font-family: 'Inter', sans-serif;
        }
        .create-post textarea:focus {
            outline: none;
            border-color: var(--mint);
            box-shadow: 0 0 0 2px rgba(76,175,120,0.1);
        }
        
        /* Post Cards */
        .post-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-border);
            padding: 1.2rem;
            margin-bottom: 1rem;
            transition: all 0.25s ease;
            box-shadow: var(--shadow-sm);
        }
        .post-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .post-header { display: flex; gap: 0.75rem; margin-bottom: 0.8rem; }
        .post-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--sage) 0%, var(--sage-dk) 100%);
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 1rem;
            color: var(--forest);
        }
        .post-author {
            font-weight: 700;
            color: var(--forest);
        }
        .post-badge {
            font-size: 0.65rem;
            padding: 0.2rem 0.6rem;
            border-radius: 30px;
            background: var(--sage);
            color: var(--forest-mid);
            margin-left: 8px;
        }
        .post-content {
            background: var(--gray-light);
            padding: 0.9rem;
            border-radius: 12px;
            font-size: 0.85rem;
            line-height: 1.5;
            margin-bottom: 0.8rem;
            color: var(--text-dark);
        }
        .post-actions {
            display: flex;
            gap: 1.2rem;
            padding-top: 0.5rem;
            border-top: 1px solid var(--gray-border);
        }
        .post-action {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 0.8rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.2s;
        }
        .post-action:hover { color: var(--mint); }
        .liked { color: var(--mint); }
        
        .comments-section { margin-top: 0.8rem; padding-top: 0.8rem; border-top: 1px solid var(--gray-border); display: none; }
        .comment { display: flex; gap: 0.5rem; margin-bottom: 0.6rem; font-size: 0.8rem; }
        .comment-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--sage);
            display: grid;
            place-items: center;
            font-weight: 600;
            font-size: 0.7rem;
        }
        .comment-text {
            background: var(--gray-light);
            padding: 0.4rem 0.8rem;
            border-radius: 16px;
            flex: 1;
        }
        .comment-form { display: flex; gap: 0.5rem; margin-top: 0.6rem; }
        .comment-input {
            flex: 1;
            border: 1px solid var(--gray-border);
            border-radius: 40px;
            padding: 0.4rem 1rem;
            font-size: 0.75rem;
        }
        .comment-input:focus {
            outline: none;
            border-color: var(--mint);
        }
        
        /* Sidebar Sections */
        .sidebar-section {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-border);
            padding: 1.2rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: box-shadow 0.2s;
        }
        .sidebar-section:hover {
            box-shadow: var(--shadow-md);
        }
        .sidebar-title {
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--forest);
        }
        .sidebar-title i {
            color: var(--mint);
        }
        
        .friend-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.6rem 0;
            border-bottom: 1px solid var(--gray-border);
        }
        .friend-item:last-child { border-bottom: none; }
        .friend-avatar-small {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--sage);
            display: grid;
            place-items: center;
            margin-right: 0.75rem;
            font-weight: 700;
        }
        
        .btn-sm { padding: 0.25rem 0.7rem; font-size: 0.7rem; border-radius: 30px; }
        .btn-success-sm { background: #e6f7ef; color: #1e6f3f; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-success-sm:hover { background: #c8e9da; }
        .btn-danger-sm { background: #fee9e7; color: #b13e3e; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-danger-sm:hover { background: #fcd6d2; }
        .btn-outline-sm { background: transparent; border: 1px solid var(--gray-border); cursor: pointer; transition: all 0.2s; }
        .btn-outline-sm:hover { background: var(--sage); border-color: var(--mint); }
        .btn-post { background: var(--forest); color: white; border: none; padding: 0.5rem 1.5rem; border-radius: 40px; font-weight: 600; font-size: 0.8rem; transition: all 0.2s; }
        .btn-post:hover { background: var(--forest-mid); transform: translateY(-1px); }
        
        .alert-success {
            background: #e6f7ef;
            color: #1e6f3f;
            padding: 0.9rem 1.2rem;
            border-radius: 14px;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--mint);
            font-weight: 500;
        }
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
        }
        .empty-state i { font-size: 2.5rem; margin-bottom: 0.5rem; opacity: 0.4; }
        
        .section-heading {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--forest);
        }
        
        @media (max-width: 768px) {
            .main-container { padding: 1rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 0.8rem; }
            .welcome-title { font-size: 1.3rem; }
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
        .post-card, .stat-card {
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
    <div class="subnav-item active">Home</div>
    <div class="subnav-item" onclick="location.href='community.php'">Community</div>
    <div class="subnav-item" onclick="location.href='opportunities.php'">Opportunities</div>
    <div class="subnav-item" onclick="location.href='applications.php'">Applications</div>
    <div class="subnav-item" onclick="location.href='chat.php'">Chat</div>
    <div class="subnav-item" onclick="location.href='performance.php'">Performance</div>
    <div class="subnav-item" onclick="location.href='dtr.php'">DTR</div>
    <div class="subnav-item" onclick="location.href='logbook.php'">Logbook</div>
    <div class="subnav-item" onclick="location.href='profile.php'">Profile</div>
</div>

<div class="main-container">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="welcome-section">
                <div class="welcome-title">Welcome back, <?php echo htmlspecialchars(explode(' ', $student['fullname'])[0]); ?></div>
                <div class="welcome-subtitle">Stay updated with the latest community activity and opportunities.</div>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i> 
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card" onclick="location.href='dtr.php'">
                    <div class="stat-value"><?php echo $total_hours; ?>/<?php echo $required_hours; ?></div>
                    <div class="stat-label">OJT Hours</div>
                    <div class="progress"><div class="progress-bar" style="width: <?php echo $progress; ?>%"></div></div>
                </div>
                <div class="stat-card" onclick="location.href='applications.php'">
                    <div class="stat-value"><?php echo $stats['pending_apps'] ?? 0; ?></div>
                    <div class="stat-label">Active Applications</div>
                </div>
                <div class="stat-card" onclick="location.href='profile.php?tab=friends'">
                    <div class="stat-value"><?php echo count($friends); ?></div>
                    <div class="stat-label">Connections</div>
                </div>
                <div class="stat-card" onclick="location.href='profile.php?tab=posts'">
                    <div class="stat-value"><?php echo $stats['total_posts'] ?? 0; ?></div>
                    <div class="stat-label">My Posts</div>
                </div>
            </div>
            
            <div class="create-post">
                <form method="POST">
                    <textarea name="post_content" rows="3" placeholder="Share your OJT experience, ask questions, or connect with peers..."></textarea>
                    <div class="mt-2 text-end">
                        <button type="submit" name="create_post" class="btn-post">Publish Post</button>
                    </div>
                </form>
            </div>
            
            <div class="section-heading">Recent Activity</div>
            <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <i class="bi bi-chat-dots"></i>
                    <p class="mt-2">No posts yet. Be the first to share something!</p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): 
                    $comments = [];
                    $cs = $conn->prepare("SELECT c.*, CASE WHEN u.user_type='student' THEN s.fullname ELSE u.email END as name FROM post_comments c JOIN users u ON c.user_id=u.id LEFT JOIN students s ON u.id=s.user_id WHERE c.post_id=? ORDER BY c.created_at");
                    $cs->execute([$post['id']]);
                    $comments = $cs->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="post-card">
                    <div class="post-header">
                        <div class="post-avatar"><?php echo strtoupper(substr($post['author_name'], 0, 1)); ?></div>
                        <div>
                            <div>
                                <span class="post-author"><?php echo htmlspecialchars($post['author_name']); ?></span>
                                <span class="post-badge"><?php echo $post['author_type'] == 'student' ? 'Student' : 'Company'; ?></span>
                            </div>
                            <div class="text-muted small"><?php echo $post['created_at'] instanceof DateTime ? $post['created_at']->format('M d, Y g:i A') : date('M d, Y g:i A', strtotime($post['created_at'])); ?></div>
                        </div>
                    </div>
                    <div class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>
                    <div class="post-actions">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <button type="submit" name="like_post" class="post-action <?php echo $post['user_liked'] ? 'liked' : ''; ?>">
                                <i class="bi <?php echo $post['user_liked'] ? 'bi-heart-fill' : 'bi-heart'; ?>"></i> 
                                <span><?php echo $post['like_count']; ?></span>
                            </button>
                        </form>
                        <button class="post-action" onclick="toggleComments(<?php echo $post['id']; ?>)">
                            <i class="bi bi-chat"></i> <?php echo $post['comment_count']; ?>
                        </button>
                        <!-- FLAG BUTTON ADDED HERE -->
                        <button class="post-action" onclick="flagPost(<?php echo $post['id']; ?>)">
                            <i class="bi bi-flag"></i> Report
                        </button>
                    </div>
                    <div class="comments-section" id="comments-<?php echo $post['id']; ?>">
                        <?php foreach ($comments as $c): ?>
                            <div class="comment">
                                <div class="comment-avatar"><?php echo strtoupper(substr($c['name'], 0, 1)); ?></div>
                                <div class="comment-text">
                                    <strong><?php echo htmlspecialchars($c['name']); ?></strong><br>
                                    <?php echo nl2br(htmlspecialchars($c['comment'])); ?>
                                    <!-- Flag button for comments -->
                                    <div class="mt-1">
                                        <button class="btn btn-sm btn-link text-muted p-0" onclick="flagComment(<?php echo $c['id']; ?>)">
                                            <i class="bi bi-flag"></i> Report this comment
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <form method="POST" class="comment-form">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <input type="text" name="comment_content" class="comment-input" placeholder="Write a comment...">
                            <button type="submit" name="add_comment" class="btn-outline-sm btn-sm">Post</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <?php if (!empty($friendRequests)): ?>
            <div class="sidebar-section">
                <div class="sidebar-title">
                    <i class="bi bi-person-plus"></i> Friend Requests
                    <span class="badge" style="background: var(--mint); color: white; margin-left: auto;"><?php echo count($friendRequests); ?></span>
                </div>
                <?php foreach ($friendRequests as $req): ?>
                <div class="friend-item">
                    <div class="d-flex align-items-center">
                        <div class="friend-avatar-small"><?php echo strtoupper(substr($req['fullname'], 0, 1)); ?></div>
                        <div>
                            <div class="fw-bold"><?php echo htmlspecialchars($req['fullname']); ?></div>
                            <div class="small text-muted"><?php echo htmlspecialchars($req['course']); ?></div>
                        </div>
                    </div>
                    <div>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="friend_id" value="<?php echo $req['friend_id']; ?>">
                            <button type="submit" name="accept_friend" class="btn-success-sm btn-sm me-1">
                                <i class="bi bi-check-lg"></i>
                            </button>
                        </form>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="friend_id" value="<?php echo $req['friend_id']; ?>">
                            <button type="submit" name="reject_friend" class="btn-danger-sm btn-sm">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="sidebar-section">
                <div class="sidebar-title">
                    <i class="bi bi-people"></i> My Connections
                    <span class="text-muted small ms-auto"><?php echo count($friends); ?></span>
                </div>
                <?php if (empty($friends)): ?>
                    <div class="text-center text-muted py-2">No connections yet. Connect with peers!</div>
                <?php else: ?>
                    <?php foreach ($friends as $f): ?>
                    <div class="friend-item">
                        <div class="d-flex align-items-center">
                            <div class="friend-avatar-small"><?php echo strtoupper(substr($f['fullname'], 0, 1)); ?></div>
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($f['fullname']); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars($f['course']); ?> • Year <?php echo $f['year_level']; ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="sidebar-section">
                <div class="sidebar-title">
                    <i class="bi bi-search"></i> Find Peers
                </div>
                <?php if (empty($recommended)): ?>
                    <div class="text-center text-muted py-2">No more peers to connect with!</div>
                <?php else: ?>
                    <?php foreach ($recommended as $r): ?>
                    <div class="friend-item">
                        <div class="d-flex align-items-center">
                            <div class="friend-avatar-small"><?php echo strtoupper(substr($r['fullname'], 0, 1)); ?></div>
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($r['fullname']); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars($r['course']); ?> • Year <?php echo $r['year_level']; ?></div>
                            </div>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="friend_id" value="<?php echo $r['id']; ?>">
                            <button type="submit" name="add_friend" class="btn-outline-sm btn-sm">Add</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleComments(id) {
    const div = document.getElementById('comments-' + id);
    if (div) {
        div.style.display = div.style.display === 'none' ? 'block' : 'none';
    }
}

// Flag Post Function
function flagPost(postId) {
    let reason = prompt("Why are you reporting this post?\n\nSelect a reason:\n- Inappropriate content\n- Spam\n- Harassment\n- Misinformation\n- Other");
    
    if (reason && reason.trim()) {
        // Get API token from localStorage (you need to store it after login)
        let token = localStorage.getItem('api_token');
        
        if (!token) {
            alert('Please login again to report content');
            return;
        }
        
        fetch('/api/flag.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({
                item_type: 'post',
                item_id: postId,
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Thank you for reporting. Our team will review this content.');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to report. Please try again.');
        });
    }
}

// Flag Comment Function
function flagComment(commentId) {
    let reason = prompt("Why are you reporting this comment?\n\nSelect a reason:\n- Inappropriate content\n- Spam\n- Harassment\n- Misinformation\n- Other");
    
    if (reason && reason.trim()) {
        let token = localStorage.getItem('api_token');
        
        if (!token) {
            alert('Please login again to report content');
            return;
        }
        
        fetch('/api/flag.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({
                item_type: 'comment',
                item_id: commentId,
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Thank you for reporting. Our team will review this content.');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to report. Please try again.');
        });
    }
}

// Store API token after login (add this to your login function)
// Example: after successful login, call localStorage.setItem('api_token', response.token);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>