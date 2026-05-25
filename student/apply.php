<?php
// student/apply.php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

requireStudent();

$user_id = $_SESSION['user_id'];
$student = getStudentData($conn, $user_id);
$internship_id = $_GET['id'] ?? 0;

$internship = null;
$already_applied = false;
$error = '';

try {
    $sql = "SELECT i.*, c.company_name FROM internships i JOIN companies c ON i.company_id = c.id WHERE i.id = ? AND i.status = 'open' AND c.status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$internship_id]);
    $internship = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$internship) { header("Location: opportunities.php"); exit(); }

    $check = $conn->prepare("SELECT id FROM applications WHERE student_id = (SELECT id FROM students WHERE user_id = ?) AND internship_id = ?");
    $check->execute([$user_id, $internship_id]);
    if ($check->fetch()) $already_applied = true;

    if (isset($_POST['submit_application']) && !$already_applied) {
        $cover = $_POST['cover_letter'] ?? '';
        $resStmt = $conn->prepare("SELECT file_path FROM documents WHERE student_id = (SELECT id FROM students WHERE user_id = ?) AND doc_type = 'resume'");
        $resStmt->execute([$user_id]);
        $resume = $resStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$resume) {
            $error = "Please upload your resume in your profile before applying.";
        } else {
            $sql = "INSERT INTO applications (student_id, internship_id, cover_letter, resume_used, status, applied_at) VALUES ((SELECT id FROM students WHERE user_id = ?), ?, ?, ?, 'pending', NOW())";
            $insertStmt = $conn->prepare($sql);
            if ($insertStmt->execute([$user_id, $internship_id, $cover, $resume['file_path']])) {
                header("Location: applications.php");
                exit();
            } else { 
                $error = "Failed to submit application."; 
            }
        }
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply - GreenBridge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --forest: #1a3a24; --forest-mid: #2d5a3d; --forest-lt: #3d7a52; --sage: #e8f0e9; --mint: #4caf78; --white: #ffffff; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f4faf6; }
        .navbar { background: var(--forest) !important; padding: 0.8rem 2rem; }
        .navbar-brand { font-weight: 800; color: white !important; }
        .user-avatar { width: 32px; height: 32px; border-radius: 8px; background: var(--mint); display: grid; place-items: center; font-weight: 700; color: var(--forest); }
        .subnav { background: var(--forest-mid); padding: 0 28px; display: flex; gap: 2px; }
        .subnav-item { padding: 12px 18px; font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.6); cursor: pointer; border-bottom: 2.5px solid transparent; }
        .subnav-item:hover { color: white; }
        .main-container { max-width: 800px; margin: 0 auto; padding: 2rem; }
        .page-title { font-size: 1.3rem; font-weight: 800; margin-bottom: 0.2rem; }
        .page-subtitle { font-size: 0.85rem; color: #7a9882; margin-bottom: 1.5rem; }
        .card { background: white; border-radius: 18px; border: 1px solid #e0ede7; overflow: hidden; margin-bottom: 1.5rem; }
        .card-header { background: #f8faf8; padding: 1rem 1.2rem; font-weight: 700; border-bottom: 1px solid #e0ede7; }
        .card-body { padding: 1.2rem; }
        .form-control { border: 1px solid #e0ede7; border-radius: 8px; padding: 0.6rem 0.8rem; width: 100%; }
        .btn-primary { background: var(--forest); color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 600; width: 100%; cursor: pointer; }
        .badge-custom { display: inline-block; padding: 0.2rem 0.6rem; background: var(--sage); border-radius: 20px; font-size: 0.7rem; margin-right: 0.5rem; }
        .alert-danger { background: #fee2e2; color: #991b1b; padding: 0.75rem; border-radius: 10px; margin-bottom: 1rem; }
        @media (max-width: 768px) { .main-container { padding: 1rem; } }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php"><i class="bi bi-tree-fill me-2"></i>GREEN BRIDGE</a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <div class="dropdown">
                <div class="user-chip dropdown-toggle" data-bs-toggle="dropdown" style="cursor: pointer; display: flex; align-items: center; gap: 10px;">
                    <div class="user-avatar"><?php echo strtoupper(substr($student['fullname'], 0, 2)); ?></div>
                    <div><div style="font-size:13px; font-weight:600; color:white"><?php echo explode(' ', $student['fullname'])[0]; ?></div><div style="font-size:10px; color:rgba(255,255,255,0.5)">Student</div></div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> My Profile</a></li><li><a class="dropdown-item" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li></ul>
            </div>
        </div>
    </div>
</nav>

<div class="subnav">
    <div class="subnav-item" onclick="location.href='dashboard.php'">Home</div>
    <div class="subnav-item" onclick="location.href='community.php'">Community</div>
    <div class="subnav-item" onclick="location.href='opportunities.php'">Opportunities</div>
    <div class="subnav-item active" onclick="location.href='applications.php'">Applications</div>
    <div class="subnav-item" onclick="location.href='chat.php'">Chat</div>
    <div class="subnav-item" onclick="location.href='performance.php'">Performance</div>
    <div class="subnav-item" onclick="location.href='dtr.php'">DTR</div>
    <div class="subnav-item" onclick="location.href='logbook.php'">Logbook</div>
    <div class="subnav-item" onclick="location.href='profile.php'">Profile</div>
</div>

<div class="main-container">
    <?php if ($already_applied): ?>
        <div class="alert alert-info">You have already applied for this position. <a href="applications.php">View My Applications →</a></div>
    <?php else: ?>
        <?php if ($error): ?><div class="alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <div class="card">
            <div class="card-header"><i class="bi bi-briefcase me-2"></i> Apply for Position</div>
            <div class="card-body">
                <div class="mb-3"><h5><?php echo htmlspecialchars($internship['title']); ?></h5><p class="text-muted"><?php echo htmlspecialchars($internship['company_name']); ?> • <?php echo $internship['location']; ?></p>
                <div><span class="badge-custom"><?php echo $internship['type']; ?></span><span class="badge-custom"><?php echo $internship['setup']; ?></span><span class="badge-custom"><?php echo $internship['duration_hours']; ?> hours</span></div></div>
                <form method="POST">
                    <div class="mb-3"><label class="form-label">Cover Letter (Optional)</label><textarea name="cover_letter" class="form-control" rows="5" placeholder="Introduce yourself, explain why you're interested..."></textarea></div>
                    <div class="mb-3"><div class="alert alert-info" style="background:#f8faf8;"><i class="bi bi-file-earmark-text"></i> Your resume will be automatically attached. <a href="profile.php" target="_blank">Update Resume</a></div></div>
                    <button type="submit" name="submit_application" class="btn-primary">Submit Application</button>
                </form>
            </div>
        </div>
        <div class="card"><div class="card-header"><i class="bi bi-info-circle me-2"></i> Application Tips</div><div class="card-body"><ul class="small text-muted mb-0"><li>Personalize your cover letter for this position</li><li>Highlight relevant coursework and projects</li><li>Explain why you're interested in this company</li><li>Proofread before submitting</li></ul></div></div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>