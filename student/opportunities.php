<?php
// student/opportunities.php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

requireStudent();

$user_id = $_SESSION['user_id'];
$student = getStudentData($conn, $user_id);

// Get all active internships from approved companies
$opportunities = [];
try {
    $sql = "SELECT i.*, c.company_name, c.industry, c.company_description, c.website
            FROM internships i
            JOIN companies c ON i.company_id = c.id
            WHERE i.status = 'open' AND c.status = 'active'
            AND (i.application_deadline IS NULL OR i.application_deadline >= NOW())
            ORDER BY i.created_at DESC";
    $stmt = $conn->query($sql);
    $internships = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if already applied
    $checkStmt = $conn->prepare("SELECT id FROM applications WHERE student_id = (SELECT id FROM students WHERE user_id = ?) AND internship_id = ?");
    foreach ($internships as $row) {
        $checkStmt->execute([$user_id, $row['id']]);
        $row['applied'] = $checkStmt->fetch() ? true : false;
        $opportunities[] = $row;
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
    <title>Opportunities - GreenBridge</title>
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
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .filter-title i {
            color: var(--mint);
        }
        .filter-group { margin-bottom: 1.25rem; }
        .filter-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            color: rgba(255,255,255,0.55);
            margin-bottom: 0.5rem;
            display: block;
            letter-spacing: 0.5px;
        }
        .filter-select {
            width: 100%;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 0.55rem 0.8rem;
            border-radius: 10px;
            font-size: 0.8rem;
            transition: all 0.2s;
        }
        .filter-select:focus {
            outline: none;
            border-color: var(--mint);
            background: rgba(255,255,255,0.18);
        }
        .filter-select option { background: var(--forest-mid); }
        .filter-check {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.85);
            margin-bottom: 0.6rem;
            cursor: pointer;
        }
        .filter-check input { 
            accent-color: var(--mint);
            width: 16px;
            height: 16px;
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
            margin-top: 0.5rem;
            transition: all 0.2s;
        }
        .btn-reset:hover { background: rgba(255,255,255,0.22); transform: translateY(-1px); }
        
        /* Opportunity Grid */
        .opportunity-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.5rem;
        }
        .opportunity-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 1.4rem;
            border: 1px solid var(--gray-border);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-sm);
        }
        .opportunity-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--sage-dk);
        }
        .opportunity-type {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.25rem 0.9rem;
            border-radius: 30px;
            display: inline-block;
            width: fit-content;
            margin-bottom: 0.75rem;
            letter-spacing: 0.3px;
        }
        .type-ojt { background: var(--forest); color: white; }
        .type-internship { background: var(--forest-mid); color: white; }
        
        .opportunity-title {
            font-weight: 800;
            font-size: 1rem;
            margin-bottom: 0.25rem;
            color: var(--forest);
        }
        .opportunity-company {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .opportunity-details {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .detail-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.7rem;
            background: var(--sage);
            border-radius: 30px;
            color: var(--forest-mid);
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-weight: 500;
        }
        .opportunity-description {
            font-size: 0.8rem;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 1rem;
            flex: 1;
        }
        .opportunity-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 0.8rem;
            border-top: 1px solid var(--gray-border);
        }
        .slots-badge { 
            font-size: 0.7rem; 
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .btn-apply {
            background: var(--forest);
            color: white;
            border: none;
            padding: 0.45rem 1.2rem;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-apply:hover { background: var(--forest-mid); transform: translateY(-1px); }
        .btn-applied { 
            background: var(--mint); 
            cursor: default; 
        }
        .btn-applied:hover { background: var(--mint); transform: none; }
        .btn-details {
            background: transparent;
            border: 1px solid var(--gray-border);
            color: var(--text-dark);
            padding: 0.45rem 1rem;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            margin-right: 0.5rem;
            transition: all 0.2s;
        }
        .btn-details:hover { background: var(--sage); border-color: var(--mint); }
        
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
            border-bottom: none;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            padding: 1rem 1.5rem;
        }
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        .company-logo-modal {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--sage) 0%, var(--sage-dk) 100%);
            border-radius: 16px;
            display: grid;
            place-items: center;
            font-size: 1.8rem;
            color: var(--forest);
        }
        .badge-modal {
            display: inline-block;
            padding: 0.25rem 0.8rem;
            background: var(--sage);
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 500;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            color: var(--forest-mid);
        }
        .info-row-modal {
            display: flex;
            padding: 0.7rem 0;
            border-bottom: 1px solid var(--gray-border);
        }
        .info-label-modal { 
            width: 140px; 
            font-weight: 700; 
            color: var(--forest);
            font-size: 0.8rem;
        }
        .info-value-modal { 
            flex: 1; 
            color: var(--text-muted);
            font-size: 0.8rem;
        }
        
        @media (max-width: 768px) {
            .main-container { padding: 1rem; }
            .opportunity-grid { grid-template-columns: 1fr; gap: 1rem; }
            .filter-sidebar { position: relative; top: 0; margin-bottom: 1.5rem; display: none; }
            .page-title { font-size: 1.5rem; }
            .subnav { padding: 0 12px; }
            .info-row-modal { flex-direction: column; gap: 0.25rem; }
            .info-label-modal { width: auto; }
        }
        
        /* Animation */
        .fade-in {
            animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px);}
            to { opacity: 1; transform: translateY(0);}
        }
        .opportunity-card {
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
    <div class="subnav-item active">Opportunities</div>
    <div class="subnav-item" onclick="location.href='applications.php'">Applications</div>
    <div class="subnav-item" onclick="location.href='chat.php'">Chat</div>
    <div class="subnav-item" onclick="location.href='performance.php'">Performance</div>
    <div class="subnav-item" onclick="location.href='dtr.php'">DTR</div>
    <div class="subnav-item" onclick="location.href='logbook.php'">Logbook</div>
    <div class="subnav-item" onclick="location.href='profile.php'">Profile</div>
</div>

<div class="main-container">
    <div class="page-header">
        <div class="page-title">Internships & OJT Opportunities</div>
        <div class="page-subtitle">Discover opportunities from verified partner companies</div>
    </div>
    
    <div class="row g-4">
        <!-- Filters Sidebar -->
        <div class="col-lg-3">
            <div class="filter-sidebar">
                <div class="filter-title">
                    <i class="bi bi-funnel"></i> Filters
                </div>
                <div class="filter-group">
                    <div class="filter-label">Type</div>
                    <select class="filter-select" id="filterType">
                        <option value="">All Types</option>
                        <option value="OJT">OJT</option>
                        <option value="Internship">Internship</option>
                    </select>
                </div>
                <div class="filter-group">
                    <div class="filter-label">Location</div>
                    <select class="filter-select" id="filterLocation">
                        <option value="">All Locations</option>
                        <option value="Manila">Manila</option>
                        <option value="Makati">Makati</option>
                        <option value="BGC">BGC</option>
                        <option value="Remote">Remote</option>
                    </select>
                </div>
                <div class="filter-group">
                    <div class="filter-label">Department</div>
                    <select class="filter-select" id="filterDept">
                        <option value="">All Departments</option>
                        <option value="CCS">CCS - Computer Studies</option>
                        <option value="CEAT">CEAT - Engineering</option>
                        <option value="COB">COB - Business</option>
                        <option value="CAS">CAS - Arts & Sciences</option>
                    </select>
                </div>
                <div class="filter-group">
                    <div class="filter-label">Work Setup</div>
                    <label class="filter-check">
                        <input type="checkbox" id="chkRemote" checked> Remote
                    </label>
                    <label class="filter-check">
                        <input type="checkbox" id="chkOnsite" checked> Onsite / Hybrid
                    </label>
                </div>
                <button class="btn-reset" onclick="resetFilters()">
                    <i class="bi bi-arrow-repeat"></i> Reset Filters
                </button>
            </div>
        </div>
        
        <!-- Opportunities Grid -->
        <div class="col-lg-9">
            <?php if (empty($opportunities)): ?>
                <div class="empty-state">
                    <i class="bi bi-briefcase"></i>
                    <p class="mt-2">No opportunities available at the moment.</p>
                </div>
            <?php else: ?>
                <div class="opportunity-grid" id="opportunitiesGrid">
                    <?php foreach ($opportunities as $opp): ?>
                        <div class="opportunity-card" 
                             data-id="<?php echo $opp['id']; ?>"
                             data-type="<?php echo $opp['type']; ?>" 
                             data-location="<?php echo $opp['location']; ?>" 
                             data-dept="<?php echo $opp['department']; ?>" 
                             data-setup="<?php echo $opp['setup']; ?>">
                            <span class="opportunity-type type-<?php echo strtolower($opp['type']); ?>"><?php echo $opp['type']; ?></span>
                            <div class="opportunity-title"><?php echo htmlspecialchars($opp['title']); ?></div>
                            <div class="opportunity-company">
                                <i class="bi bi-building"></i> <?php echo htmlspecialchars($opp['company_name']); ?>
                            </div>
                            <div class="opportunity-details">
                                <span class="detail-badge"><i class="bi bi-geo-alt"></i> <?php echo $opp['location']; ?></span>
                                <span class="detail-badge"><i class="bi bi-laptop"></i> <?php echo $opp['setup']; ?></span>
                                <span class="detail-badge"><i class="bi bi-clock"></i> <?php echo $opp['duration_hours']; ?> hrs</span>
                            </div>
                            <div class="opportunity-description">
                                <?php echo htmlspecialchars(substr($opp['description'] ?? 'No description available.', 0, 100)); ?>...
                            </div>
                            <div class="opportunity-footer">
                                <span class="slots-badge"><i class="bi bi-people"></i> <?php echo $opp['slots']; ?> slots</span>
                                <div>
                                    <button class="btn-details" onclick="showDetails(<?php echo $opp['id']; ?>)">Details</button>
                                    <?php if ($opp['applied']): ?>
                                        <button class="btn-apply btn-applied" disabled>
                                            <i class="bi bi-check-lg"></i> Applied
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-apply" onclick="location.href='apply.php?id=<?php echo $opp['id']; ?>'">Apply</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Opportunity Details Modal -->
<div class="modal fade" id="opportunityModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-info-circle me-2"></i> Opportunity Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="modalApplyBtn">Apply Now</button>
            </div>
        </div>
    </div>
</div>

<script>
// Store opportunities data for modal
const opportunitiesData = <?php 
    $data = [];
    foreach ($opportunities as $opp) {
        $data[$opp['id']] = [
            'id' => $opp['id'],
            'title' => $opp['title'],
            'company_name' => $opp['company_name'],
            'type' => $opp['type'],
            'location' => $opp['location'],
            'setup' => $opp['setup'],
            'department' => $opp['department'],
            'duration_hours' => $opp['duration_hours'],
            'slots' => $opp['slots'],
            'stipend' => $opp['stipend'],
            'description' => $opp['description'],
            'requirements' => $opp['requirements'],
            'industry' => $opp['industry'],
            'application_deadline' => $opp['application_deadline'],
            'applied' => $opp['applied']
        ];
    }
    echo json_encode($data); 
?>;

function showDetails(id) {
    const opp = opportunitiesData[id];
    if (!opp) return;
    
    const badges = [opp.type, opp.setup, opp.location, opp.department, opp.duration_hours + ' hours'];
    let deadlineHtml = '';
    let stipendHtml = '';
    
    if (opp.application_deadline) {
        const deadlineDate = new Date(opp.application_deadline);
        deadlineHtml = `<div class="info-row-modal"><div class="info-label-modal">Application Deadline</div><div class="info-value-modal">${deadlineDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</div></div>`;
    }
    if (opp.stipend) {
        stipendHtml = `<div class="info-row-modal"><div class="info-label-modal">Stipend</div><div class="info-value-modal">${escapeHtml(opp.stipend)}</div></div>`;
    }
    
    document.getElementById('modalContent').innerHTML = `
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="company-logo-modal"><i class="bi bi-building"></i></div>
            <div>
                <h4 class="mb-0" style="color: var(--forest);">${escapeHtml(opp.title)}</h4>
                <p class="text-muted mb-0">${escapeHtml(opp.company_name)}</p>
            </div>
        </div>
        <div class="mb-3">${badges.map(b => `<span class="badge-modal">${escapeHtml(b)}</span>`).join('')}</div>
        <div class="info-row-modal"><div class="info-label-modal">Industry</div><div class="info-value-modal">${escapeHtml(opp.industry) || 'Not specified'}</div></div>
        <div class="info-row-modal"><div class="info-label-modal">Available Slots</div><div class="info-value-modal">${opp.slots} position(s)</div></div>
        ${stipendHtml}
        ${deadlineHtml}
        <div class="mt-4"><h6 class="fw-bold mb-2">Description</h6><p class="text-muted">${escapeHtml(opp.description || 'No description available.')}</p></div>
        <div class="mt-3"><h6 class="fw-bold mb-2">Requirements</h6><p class="text-muted">${escapeHtml(opp.requirements || 'No specific requirements listed.')}</p></div>
    `;
    
    const applyBtn = document.getElementById('modalApplyBtn');
    if (opp.applied) {
        applyBtn.textContent = 'Already Applied';
        applyBtn.disabled = true;
        applyBtn.classList.remove('btn-primary');
        applyBtn.classList.add('btn-secondary');
    } else {
        applyBtn.textContent = 'Apply Now';
        applyBtn.disabled = false;
        applyBtn.classList.add('btn-primary');
        applyBtn.classList.remove('btn-secondary');
        applyBtn.onclick = function() { window.location.href = 'apply.php?id=' + opp.id; };
    }
    
    new bootstrap.Modal(document.getElementById('opportunityModal')).show();
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function resetFilters() {
    document.getElementById('filterType').value = '';
    document.getElementById('filterLocation').value = '';
    document.getElementById('filterDept').value = '';
    document.getElementById('chkRemote').checked = true;
    document.getElementById('chkOnsite').checked = true;
    filterOpportunities();
}

function filterOpportunities() {
    const type = document.getElementById('filterType').value;
    const location = document.getElementById('filterLocation').value;
    const dept = document.getElementById('filterDept').value;
    const remote = document.getElementById('chkRemote').checked;
    const onsite = document.getElementById('chkOnsite').checked;
    
    const cards = document.querySelectorAll('.opportunity-card');
    cards.forEach(card => {
        let show = true;
        if (type && card.dataset.type !== type) show = false;
        if (location && card.dataset.location !== location) show = false;
        if (dept && card.dataset.dept !== dept) show = false;
        const setup = card.dataset.setup;
        if (setup === 'Remote' && !remote) show = false;
        if ((setup === 'Onsite' || setup === 'Hybrid') && !onsite) show = false;
        card.style.display = show ? '' : 'none';
    });
}

// Event listeners for filters
document.getElementById('filterType')?.addEventListener('change', filterOpportunities);
document.getElementById('filterLocation')?.addEventListener('change', filterOpportunities);
document.getElementById('filterDept')?.addEventListener('change', filterOpportunities);
document.getElementById('chkRemote')?.addEventListener('change', filterOpportunities);
document.getElementById('chkOnsite')?.addEventListener('change', filterOpportunities);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>