<?php
// company/scout_students.php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

requireCompany();

$user_id = $_SESSION['user_id'];
$company = getCompanyData($conn, $user_id);

$skills_list = [];
$students = [];

try {
    $stmt = $conn->query("SELECT DISTINCT skill_name FROM skills ORDER BY skill_name");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $skills_list[] = $row['skill_name'];
    }

    // Translated STRING_AGG to GROUP_CONCAT for MySQL
    $sql = "SELECT s.id, s.fullname, s.course, s.year_level, s.bio, 
                   u.email, u.profile_picture,
                   (SELECT GROUP_CONCAT(skill_name SEPARATOR ', ') FROM skills WHERE student_id = s.id) as skills
            FROM students s
            JOIN users u ON s.user_id = u.id
            WHERE s.status = 'active'
            ORDER BY s.fullname";
    $stmt = $conn->query($sql);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (isset($_POST['send_invite'])) {
        $student_id = $_POST['student_id'];
        $message = $_POST['message'] ?? '';
        
        $stmt = $conn->prepare("SELECT u.email, s.fullname FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($student) {
            $sql = "INSERT INTO messages (sender_id, receiver_id, sender_type, receiver_type, subject, message, created_at) 
                    VALUES (?, (SELECT user_id FROM students WHERE id = ?), 'company', 'student', 'Internship Opportunity', ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id, $student_id, $message]);
            
            $_SESSION['success'] = "Invitation sent to " . $student['fullname'] . "!";
        }
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
    <title>Scout Students - GreenBridge</title>
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
            max-width: 1400px;
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
        
        /* Filter Sidebar Enhanced */
        .filter-sidebar {
            background: var(--forest);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            color: white;
            position: sticky;
            top: 100px;
            box-shadow: var(--shadow-md);
        }
        .filter-title { 
            font-family: 'Playfair Display', serif; 
            font-size: 1.2rem; 
            font-weight: 700; 
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .filter-title i {
            color: var(--mint);
        }
        .filter-group { margin-bottom: 1.3rem; }
        .filter-label {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.55);
            margin-bottom: 0.5rem;
        }
        .filter-select, .filter-input {
            width: 100%;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 0.6rem 0.8rem;
            border-radius: 10px;
            font-size: 0.8rem;
            transition: all 0.2s;
        }
        .filter-select option {
            background: var(--forest-mid);
            color: white;
        }
        .filter-input::placeholder { color: rgba(255,255,255,0.5); }
        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: var(--mint);
            background: rgba(255,255,255,0.15);
        }
        .btn-reset {
            width: 100%;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.25);
            color: white;
            padding: 0.6rem;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 0.5rem;
        }
        .btn-reset:hover { background: rgba(255,255,255,0.22); transform: translateY(-1px); }
        
        /* Student Cards Grid */
        .student-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }
        .student-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-border);
            padding: 1.4rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }
        .student-card:hover { 
            transform: translateY(-4px); 
            box-shadow: var(--shadow-md);
            border-color: var(--sage-dk);
        }
        .student-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--sage) 0%, var(--sage-dk) 100%);
            display: grid;
            place-items: center;
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0 auto 0.8rem;
            color: var(--forest);
        }
        .student-name { 
            font-weight: 800; 
            font-size: 1rem; 
            text-align: center; 
            margin-bottom: 0.2rem;
            color: var(--forest);
        }
        .student-course { 
            font-size: 0.75rem; 
            color: var(--text-muted); 
            text-align: center; 
            margin-bottom: 0.8rem;
        }
        .student-skills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin: 0.8rem 0;
            justify-content: center;
        }
        .skill-badge {
            font-size: 0.65rem;
            padding: 0.2rem 0.7rem;
            background: var(--sage);
            border-radius: 30px;
            color: var(--forest);
            font-weight: 500;
        }
        .student-bio {
            font-size: 0.75rem;
            color: var(--text-muted);
            line-height: 1.45;
            margin-bottom: 1rem;
            text-align: center;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .btn-scout {
            width: 100%;
            background: var(--forest);
            color: white;
            border: none;
            padding: 0.6rem;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-scout:hover { 
            background: var(--forest-mid); 
            transform: translateY(-1px);
        }
        
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
            padding: 3rem;
            background: var(--white);
            border-radius: var(--radius-lg);
            color: var(--text-muted);
        }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.4; color: var(--sage-dk); }
        
        /* Modal Styles */
        .modal-content {
            border-radius: var(--radius-lg);
            border: none;
        }
        .modal-header {
            background: var(--forest);
            color: white;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            padding: 1rem 1.5rem;
        }
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        .form-label {
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--forest);
            margin-bottom: 0.4rem;
        }
        .form-control {
            border-radius: 10px;
            border: 1px solid var(--gray-border);
            padding: 0.7rem;
            font-size: 0.85rem;
        }
        .form-control:focus {
            border-color: var(--mint);
            box-shadow: 0 0 0 2px rgba(76,175,120,0.2);
        }
        .modal-footer .btn-outline {
            background: transparent;
            border: 1px solid var(--gray-border);
            padding: 0.5rem 1.2rem;
            border-radius: 40px;
            font-size: 0.8rem;
        }
        .modal-footer .btn-primary {
            background: var(--forest);
            color: white;
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 40px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        /* No Results */
        .no-results {
            text-align: center;
            padding: 2.5rem;
            background: var(--white);
            border-radius: var(--radius-lg);
        }
        
        @media (max-width: 768px) {
            .main-container { padding: 1rem; }
            .student-grid { grid-template-columns: 1fr; }
            .filter-sidebar { position: relative; top: 0; margin-bottom: 1.5rem; }
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
        .student-card {
            animation: fadeIn 0.3s ease;
        }
        
        .student-profile-link {
            text-decoration: none;
            color: inherit;
            display: block;
            text-align: center;
            margin-bottom: 0.2rem;
        }
        .student-profile-link:hover .student-name {
            color: var(--mint);
        }
        .student-profile-link:hover .student-avatar {
            transform: scale(1.06);
            box-shadow: 0 4px 12px rgba(76,175,120,0.2);
        }
        .student-avatar {
            transition: all 0.25s ease-in-out;
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
    <div class="subnav-item active" onclick="location.href='scout_students.php'">Scout Students</div>
    <div class="subnav-item" onclick="location.href='manage_interns.php'">Manage Interns</div>
    <div class="subnav-item" onclick="location.href='logbook.php'">Logbooks</div>
    <div class="subnav-item" onclick="location.href='profile.php'">Profile</div>
</div>

<div class="main-container">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-success">
            <i class="bi bi-check-circle-fill me-2"></i> 
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <div class="page-header">
        <div class="page-title">Scout Students</div>
        <div class="page-subtitle">Find and connect with talented students for your internship opportunities</div>
    </div>
    
    <div class="row g-4">
        <!-- Filter Sidebar -->
        <div class="col-lg-3">
            <div class="filter-sidebar">
                <div class="filter-title">
                    <i class="bi bi-funnel"></i> Filters
                </div>
                <div class="filter-group">
                    <div class="filter-label">Search</div>
                    <input type="text" id="searchInput" class="filter-input" placeholder="Name, course, or skills...">
                </div>
                <div class="filter-group">
                    <div class="filter-label">Course / Program</div>
                    <select id="courseFilter" class="filter-select">
                        <option value="">All Courses</option>
                        <option value="BS Computer Science">BS Computer Science</option>
                        <option value="BS Information Technology">BS Information Technology</option>
                        <option value="BS Computer Engineering">BS Computer Engineering</option>
                        <option value="BS Electronics Engineering">BS Electronics Engineering</option>
                        <option value="BS Accountancy">BS Accountancy</option>
                        <option value="BS Business Administration">BS Business Administration</option>
                    </select>
                </div>
                <div class="filter-group">
                    <div class="filter-label">Year Level</div>
                    <select id="yearFilter" class="filter-select">
                        <option value="">All Years</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                    </select>
                </div>
                <div class="filter-group">
                    <div class="filter-label">Skills</div>
                    <select id="skillFilter" class="filter-select">
                        <option value="">All Skills</option>
                        <?php foreach ($skills_list as $skill): ?>
                            <option value="<?php echo htmlspecialchars($skill); ?>"><?php echo htmlspecialchars($skill); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn-reset" onclick="resetFilters()">
                    <i class="bi bi-arrow-repeat"></i> Reset Filters
                </button>
            </div>
        </div>
        
        <!-- Student Cards Grid -->
        <div class="col-lg-9">
            <?php if (empty($students)): ?>
                <div class="empty-state">
                    <i class="bi bi-person-badge"></i>
                    <p>No students found. Check back later for new profiles.</p>
                </div>
            <?php else: ?>
                <div class="student-grid" id="studentGrid">
                    <?php foreach ($students as $student): ?>
                        <div class="student-card" 
                             data-name="<?php echo strtolower($student['fullname']); ?>"
                             data-course="<?php echo $student['course']; ?>"
                             data-year="<?php echo $student['year_level']; ?>"
                             data-skills="<?php echo strtolower($student['skills'] ?? ''); ?>">
                            <a href="student_profile.php?id=<?php echo $student['id']; ?>" class="student-profile-link">
                                <div class="student-avatar"><?php echo strtoupper(substr($student['fullname'], 0, 2)); ?></div>
                                <div class="student-name"><?php echo htmlspecialchars($student['fullname']); ?></div>
                            </a>
                            <div class="student-course"><?php echo htmlspecialchars($student['course']); ?> • Year <?php echo $student['year_level']; ?></div>
                            <div class="student-skills">
                                <?php if ($student['skills']): ?>
                                    <?php foreach (explode(',', $student['skills']) as $skill): ?>
                                        <span class="skill-badge"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="skill-badge">No skills listed</span>
                                <?php endif; ?>
                            </div>
                            <div class="student-bio">
                                <?php echo htmlspecialchars(substr($student['bio'] ?? 'No bio available.', 0, 100)); ?>
                            </div>
                            <button class="btn-scout" onclick="openScoutModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['fullname']); ?>')">
                                <i class="bi bi-send"></i> Scout Student
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="noResultsMsg" class="no-results" style="display: none;">
                    <i class="bi bi-search" style="font-size: 2rem; opacity: 0.4;"></i>
                    <p class="mt-2">No students match your filters. Try adjusting your criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Scout Modal -->
<div class="modal fade" id="scoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-envelope-paper me-2"></i>Send Invitation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p>Send an invitation to <strong id="scoutStudentName"></strong> for an internship opportunity.</p>
                    <div class="mb-3">
                        <label class="form-label">Personalized Message</label>
                        <textarea name="message" class="form-control" rows="5" placeholder="Hello! We're impressed with your profile and would like to invite you to apply for an internship at <?php echo htmlspecialchars($company['company_name']); ?>..."></textarea>
                    </div>
                    <input type="hidden" name="student_id" id="scoutStudentId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="send_invite" class="btn-primary">Send Invitation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openScoutModal(id, name) {
    document.getElementById('scoutStudentId').value = id;
    document.getElementById('scoutStudentName').textContent = name;
    new bootstrap.Modal(document.getElementById('scoutModal')).show();
}

function filterStudents() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const course = document.getElementById('courseFilter').value;
    const year = document.getElementById('yearFilter').value;
    const skill = document.getElementById('skillFilter').value.toLowerCase();
    
    const cards = document.querySelectorAll('.student-card');
    let visibleCount = 0;
    
    cards.forEach(card => {
        const name = card.dataset.name || '';
        const cardCourse = card.dataset.course || '';
        const cardYear = card.dataset.year || '';
        const cardSkills = card.dataset.skills || '';
        
        let show = true;
        if (search && !name.includes(search) && !cardCourse.toLowerCase().includes(search)) show = false;
        if (course && cardCourse !== course) show = false;
        if (year && cardYear !== year) show = false;
        if (skill && !cardSkills.includes(skill)) show = false;
        
        card.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });
    
    const noResultsDiv = document.getElementById('noResultsMsg');
    if (noResultsDiv) {
        noResultsDiv.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('courseFilter').value = '';
    document.getElementById('yearFilter').value = '';
    document.getElementById('skillFilter').value = '';
    filterStudents();
}

// Event listeners
document.getElementById('searchInput').addEventListener('input', filterStudents);
document.getElementById('courseFilter').addEventListener('change', filterStudents);
document.getElementById('yearFilter').addEventListener('change', filterStudents);
document.getElementById('skillFilter').addEventListener('change', filterStudents);

// Initial filter to set correct display
filterStudents();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>