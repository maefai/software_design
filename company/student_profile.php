<?php
// company/student_profile.php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

requireCompany();

$user_id = $_SESSION['user_id'];
$company = getCompanyData($conn, $user_id);
$student_id = $_GET['id'] ?? 0;

try {
    $stmt = $conn->prepare("SELECT s.*, u.email, u.created_at as registered_date, u.status as account_status FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ? AND s.status = 'active'");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        header("Location: scout_students.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM documents WHERE student_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$student_id]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT * FROM certificates WHERE student_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$student_id]);
    $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT * FROM skills WHERE student_id = ? ORDER BY level DESC, skill_name");
    $stmt->execute([$student_id]);
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT COUNT(*) as total_sessions, SUM(hours) as total_hours, AVG(hours) as avg_hours FROM dtr_logs WHERE student_id = ? AND status = 'completed'");
    $stmt->execute([$student_id]);
    $dtr_summary = $stmt->fetch(PDO::FETCH_ASSOC);

    // Translated TOP 5 to LIMIT 5
    $stmt = $conn->prepare("SELECT * FROM logbook_entries WHERE student_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$student_id]);
    $logbook = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $already_scouted = false;
    $stmt = $conn->prepare("SELECT id FROM messages WHERE sender_id = ? AND receiver_id = (SELECT user_id FROM students WHERE id = ?) AND sender_type = 'company'");
    $stmt->execute([$user_id, $student_id]);
    if ($stmt->fetch()) {
        $already_scouted = true;
    }

    if (isset($_POST['send_invite'])) {
        $message = $_POST['message'] ?? '';
        $student_user_id = $student['user_id'];
        
        $sql = "INSERT INTO messages (sender_id, receiver_id, sender_type, receiver_type, subject, message, created_at) 
                VALUES (?, ?, 'company', 'student', 'Internship Opportunity', ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id, $student_user_id, $message]);
        
        $_SESSION['success'] = "Invitation sent to " . $student['fullname'] . "!";
        header("Location: scout_students.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($student['fullname']); ?> - Student Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --forest: #1a3a24;
            --forest-mid: #2d5a3d;
            --forest-lt: #3d7a52;
            --sage: #e8f0e9;
            --mint: #4caf78;
            --white: #ffffff;
        }
        
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f4faf6; }
        
        .navbar { background: var(--forest) !important; padding: 0.8rem 2rem; }
        .navbar-brand { font-weight: 800; color: white !important; }
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--mint);
            display: grid;
            place-items: center;
            font-weight: 700;
            color: var(--forest);
        }
        
        .subnav {
            background: var(--forest-mid);
            padding: 0 28px;
            display: flex;
            gap: 2px;
        }
        .subnav-item {
            padding: 12px 18px;
            font-size: 13px;
            font-weight: 600;
            color: rgba(255,255,255,0.6);
            cursor: pointer;
            border-bottom: 2.5px solid transparent;
        }
        .subnav-item:hover { color: white; }
        .subnav-item.active { color: white; border-bottom-color: var(--mint); }
        
        .main-container { max-width: 1100px; margin: 0 auto; padding: 2rem; }
        .page-title { font-size: 1.3rem; font-weight: 800; margin-bottom: 0.2rem; }
        .page-subtitle { font-size: 0.85rem; color: #7a9882; margin-bottom: 1.5rem; }
        
        .profile-header {
            background: white;
            border-radius: 18px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid #e0ede7;
            text-align: center;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--forest-lt), var(--mint));
            display: grid;
            place-items: center;
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin: 0 auto 1rem;
        }
        
        .card {
            background: white;
            border-radius: 18px;
            border: 1px solid #e0ede7;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .card-header {
            background: #f8faf8;
            padding: 1rem 1.2rem;
            font-weight: 700;
            border-bottom: 1px solid #e0ede7;
        }
        .card-body { padding: 1.2rem; }
        
        .info-row {
            display: flex;
            padding: 0.6rem 0;
            border-bottom: 1px solid #f0f7f3;
        }
        .info-label { width: 120px; font-weight: 600; color: var(--forest); }
        .info-value { flex: 1; color: #4a6350; }
        
        .skill-tag {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            background: var(--sage);
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin: 0.2rem;
            color: var(--forest);
        }
        .skill-excellent { background: #d1fae5; color: #065f46; }
        .skill-good { background: #dbeafe; color: #1e40af; }
        .skill-learning { background: #fef3cd; color: #856404; }
        
        .doc-item, .cert-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.6rem;
            border-bottom: 1px solid #f0f7f3;
        }
        .doc-icon {
            width: 36px;
            height: 36px;
            background: var(--sage);
            border-radius: 8px;
            display: grid;
            place-items: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .stat-card {
            background: white;
            border-radius: 18px;
            padding: 1rem;
            text-align: center;
            border: 1px solid #e0ede7;
        }
        .stat-value { font-size: 1.5rem; font-weight: 800; color: var(--forest); }
        .stat-label { font-size: 0.7rem; color: #7a9882; }
        
        .log-entry {
            padding: 0.6rem 0;
            border-bottom: 1px solid #f0f7f3;
        }
        .log-week { font-weight: 700; font-size: 0.7rem; color: var(--forest); }
        .log-title { font-weight: 600; font-size: 0.85rem; }
        
        .btn-primary {
            background: var(--forest);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-outline {
            background: transparent;
            border: 1px solid var(--forest);
            color: var(--forest);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .alert-success { background: #d1fae5; color: #065f46; padding: 0.75rem; border-radius: 10px; margin-bottom: 1rem; }
        
        @media (max-width: 768px) { .main-container { padding: 1rem; } .info-row { flex-direction: column; gap: 0.2rem; } .info-label { width: auto; } }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php"><i class="bi bi-tree-fill me-2"></i>GREEN BRIDGE</a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <div class="dropdown">
                <div class="user-chip dropdown-toggle" data-bs-toggle="dropdown" style="cursor: pointer; display: flex; align-items: center; gap: 10px;">
                    <div class="user-avatar"><?php echo strtoupper(substr($company['company_name'], 0, 2)); ?></div>
                    <div><div style="font-size:13px; font-weight:600; color:white"><?php echo htmlspecialchars($company['company_name']); ?></div><div style="font-size:10px; color:rgba(255,255,255,0.5)">Company</div></div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="profile.php"><i class="bi bi-building"></i> Company Profile</a></li>
                    <li><a class="dropdown-item" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="subnav">
    <div class="subnav-item" onclick="location.href='dashboard.php'">Dashboard</div>
    <div class="subnav-item" onclick="location.href='post_internship.php'">Post Internship</div>
    <div class="subnav-item" onclick="location.href='manage_applications.php'">Applications</div>
    <div class="subnav-item" onclick="location.href='chat.php'">Chat</div>
    <div class="subnav-item active" onclick="location.href='scout_students.php'">Scout Students</div>
    <div class="subnav-item" onclick="location.href='manage_interns.php'">Manage Interns</div>
    <div class="subnav-item" onclick="location.href='logbook.php'">Logbooks</div>
    <div class="subnav-item" onclick="location.href='profile.php'">Profile</div>
</div>

<div class="main-container">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar"><?php echo strtoupper(substr($student['fullname'], 0, 2)); ?></div>
        <h4><?php echo htmlspecialchars($student['fullname']); ?></h4>
        <p class="text-muted"><?php echo htmlspecialchars($student['course']); ?> • Year <?php echo $student['year_level']; ?></p>
        <p><?php echo htmlspecialchars($student['university']); ?></p>
        
        <?php if (!$already_scouted): ?>
            <button class="btn-primary mt-2" onclick="openScoutModal()">
                <i class="bi bi-send"></i> Scout Student
            </button>
        <?php else: ?>
            <button class="btn-outline mt-2" disabled>
                <i class="bi bi-check-circle"></i> Already Contacted
            </button>
        <?php endif; ?>
    </div>
    
    <div class="row">
        <div class="col-lg-6">
            <!-- Personal Info -->
            <div class="card">
                <div class="card-header"><i class="bi bi-person me-2"></i> Personal Information</div>
                <div class="card-body">
                    <div class="info-row"><div class="info-label">Student ID</div><div class="info-value"><?php echo htmlspecialchars($student['student_id']); ?></div></div>
                    <div class="info-row"><div class="info-label">Email</div><div class="info-value"><?php echo htmlspecialchars($student['email']); ?></div></div>
                    <div class="info-row"><div class="info-label">Contact</div><div class="info-value"><?php echo htmlspecialchars($student['contact'] ?? 'Not provided'); ?></div></div>
                    <div class="info-row"><div class="info-label">Bio</div><div class="info-value"><?php echo nl2br(htmlspecialchars($student['bio'] ?? 'No bio available.')); ?></div></div>
                </div>
            </div>
            
            <!-- Skills -->
            <div class="card">
                <div class="card-header"><i class="bi bi-lightning-charge me-2"></i> Skills & Expertise</div>
                <div class="card-body">
                    <?php if (empty($skills)): ?>
                        <p class="text-muted text-center py-2">No skills listed.</p>
                    <?php else: ?>
                        <div class="d-flex flex-wrap">
                            <?php foreach ($skills as $skill): ?>
                                <span class="skill-tag skill-<?php echo $skill['level']; ?>">
                                    <?php echo htmlspecialchars($skill['skill_name']); ?>
                                    <?php if ($skill['level'] == 'excellent'): ?>⭐<?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Documents -->
            <div class="card">
                <div class="card-header"><i class="bi bi-folder me-2"></i> Documents</div>
                <div class="card-body">
                    <?php if (empty($documents)): ?>
                        <p class="text-muted text-center py-2">No documents uploaded.</p>
                    <?php else: ?>
                        <?php foreach ($documents as $doc): ?>
                            <div class="doc-item">
                                <div class="doc-icon"><i class="bi bi-file-pdf"></i></div>
                                <div style="flex:1"><div><strong><?php echo htmlspecialchars($doc['doc_name']); ?></strong></div><div class="text-muted small"><?php echo ucfirst($doc['doc_type']); ?> • <?php echo round($doc['file_size'] / 1024, 2); ?> KB</div></div>
                                <a href="<?php echo SITE_URL . $doc['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary">View</a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <!-- DTR Summary -->
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-value"><?php echo $dtr_summary['total_sessions'] ?? 0; ?></div><div class="stat-label">Sessions</div></div>
                <div class="stat-card"><div class="stat-value"><?php echo number_format($dtr_summary['total_hours'] ?? 0, 1); ?></div><div class="stat-label">Total Hours</div></div>
                <div class="stat-card"><div class="stat-value"><?php echo number_format($dtr_summary['avg_hours'] ?? 0, 1); ?></div><div class="stat-label">Avg Hours</div></div>
            </div>
            
            <!-- Certificates -->
            <div class="card">
                <div class="card-header"><i class="bi bi-award me-2"></i> Certificates</div>
                <div class="card-body">
                    <?php if (empty($certificates)): ?>
                        <p class="text-muted text-center py-2">No certificates uploaded.</p>
                    <?php else: ?>
                        <?php foreach ($certificates as $cert): ?>
                            <div class="cert-item">
                                <div class="doc-icon"><i class="bi bi-trophy"></i></div>
                                <div style="flex:1"><div><strong><?php echo htmlspecialchars($cert['certificate_name']); ?></strong></div><div class="text-muted small"><?php echo htmlspecialchars($cert['issuer']); ?> • <?php echo $cert['issue_date'] instanceof DateTime ? $cert['issue_date']->format('M Y') : date('M Y', strtotime($cert['issue_date'])); ?></div></div>
                                <?php if ($cert['file_path']): ?>
                                    <a href="<?php echo SITE_URL . $cert['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary">View</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Logbook -->
            <div class="card">
                <div class="card-header"><i class="bi bi-journal-text me-2"></i> Recent Logbook Entries</div>
                <div class="card-body">
                    <?php if (empty($logbook)): ?>
                        <p class="text-muted text-center py-2">No logbook entries yet.</p>
                    <?php else: ?>
                        <?php foreach ($logbook as $entry): ?>
                            <div class="log-entry">
                                <div class="log-week">Week <?php echo $entry['week_number']; ?></div>
                                <div class="log-title"><?php echo htmlspecialchars($entry['title']); ?></div>
                                <div class="text-muted small"><?php echo $entry['created_at'] instanceof DateTime ? $entry['created_at']->format('M d, Y') : date('M d, Y', strtotime($entry['created_at'])); ?></div>
                                <div class="small mt-1"><?php echo htmlspecialchars(substr($entry['narrative'], 0, 80)); ?>...</div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scout Modal -->
<div class="modal fade" id="scoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--forest); color:white;">
                <h5 class="modal-title"><i class="bi bi-send me-2"></i>Send Invitation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p>Send an invitation to <strong><?php echo htmlspecialchars($student['fullname']); ?></strong> for an internship opportunity.</p>
                    <div class="mb-3">
                        <label class="form-label">Personalized Message</label>
                        <textarea name="message" class="form-control" rows="5" placeholder="Hi! We're impressed with your profile and would like to invite you to apply for an internship at <?php echo htmlspecialchars($company['company_name']); ?>..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="send_invite" class="btn btn-primary">Send Invitation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openScoutModal() {
    new bootstrap.Modal(document.getElementById('scoutModal')).show();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>