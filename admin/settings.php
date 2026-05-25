<?php
// admin/settings.php
require_once 'includes/admin_auth.php';

// Handle settings updates
$message = '';
$messageType = '';

if (isset($_POST['save_settings'])) {
    $site_name = $_POST['site_name'] ?? 'GreenBridge';
    $site_url = $_POST['site_url'] ?? '';
    $contact_email = $_POST['contact_email'] ?? '';
    $auto_approve_posts = isset($_POST['auto_approve_posts']) ? 1 : 0;
    $require_email_verification = isset($_POST['require_email_verification']) ? 1 : 0;
    $max_file_size = $_POST['max_file_size'] ?? 5;
    
    // Save to JSON file
    $settings = [
        'site_name' => $site_name,
        'site_url' => $site_url,
        'contact_email' => $contact_email,
        'auto_approve_posts' => $auto_approve_posts,
        'require_email_verification' => $require_email_verification,
        'max_file_size' => $max_file_size,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $settings_file = __DIR__ . '/../includes/settings.json';
    if (file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT))) {
        $message = "Settings saved successfully!";
        $messageType = "success";
        
        // Log admin action via PDO
        try {
            $logSql = "INSERT INTO admin_actions (admin_id, action_type, target_type, target_id, details, created_at) 
                       VALUES (?, 'update_settings', 'system', 0, 'Platform settings updated', NOW())";
            $stmt = $conn->prepare($logSql);
            $stmt->execute([$_SESSION['user_id']]);
        } catch (PDOException $e) {
            error_log("Failed to log setting change: " . $e->getMessage());
        }
    } else {
        $message = "Failed to save settings.";
        $messageType = "danger";
    }
}

// Load current settings
$settings = [];
$settings_file = __DIR__ . '/../includes/settings.json';
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
}

// Default settings
$site_name = $settings['site_name'] ?? 'GreenBridge';
$site_url = $settings['site_url'] ?? 'http://localhost/website/greenbridge/';
$contact_email = $settings['contact_email'] ?? 'admin@greenbridge.com';
$auto_approve_posts = $settings['auto_approve_posts'] ?? 0;
$require_email_verification = $settings['require_email_verification'] ?? 1;
$max_file_size = $settings['max_file_size'] ?? 5;

// Get system info
$php_version = phpversion();
$server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$database_type = 'MySQL / MariaDB (PDO)'; // Updated for Hostinger!
$upload_max_filesize = ini_get('upload_max_filesize');
$post_max_size = ini_get('post_max_size');
$memory_limit = ini_get('memory_limit');

// Get recent admin actions
$recentActions = [];
try {
    $sql = "SELECT a.*, u.email as admin_email
            FROM admin_actions a
            JOIN users u ON a.admin_id = u.id
            ORDER BY a.created_at DESC LIMIT 10";
    $stmt = $conn->query($sql);
    $recentActions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Failed to fetch recent actions: " . $e->getMessage());
}

// Get admin stats
$adminStats = ['total_actions' => 0];
try {
    $sql = "SELECT COUNT(*) as total_actions FROM admin_actions";
    $stmt = $conn->query($sql);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $adminStats['total_actions'] = $row['total_actions'] ?? 0;
    }
} catch (PDOException $e) {
    error_log("Failed to fetch admin stats: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - GreenBridge Admin</title>
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
        .serif, h1, h2, h3, .playfair {
            font-family: 'Playfair Display', serif;
        }
        
        /* Sidebar */
        #sidebar {
            width: 280px;
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: var(--forest);
            color: white;
            z-index: 100;
            box-shadow: 2px 0 12px rgba(0,0,0,0.08);
        }
        
        .sidebar-logo {
            padding: 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .logo-icon {
            width: 42px;
            height: 42px;
            background: var(--mint);
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--forest);
        }
        
        .logo-text span {
            display: block;
            font-size: 1.1rem;
            font-weight: 800;
            color: white;
            font-family: 'Playfair Display', serif;
        }
        
        .logo-text small {
            font-size: 0.65rem;
            color: rgba(255,255,255,0.45);
            letter-spacing: 0.5px;
        }
        
        .sidebar-section {
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.35);
            padding: 1.2rem 1.5rem 0.4rem 1.5rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.7rem 1.5rem;
            color: rgba(255,255,255,0.65);
            font-size: 0.85rem;
            font-weight: 500;
            border-left: 3px solid transparent;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .nav-link i { font-size: 1.1rem; width: 24px; }
        .nav-link:hover { color: white; background: rgba(255,255,255,0.06); }
        .nav-link.active {
            color: var(--mint);
            border-left-color: var(--mint);
            background: rgba(76,175,120,0.1);
            font-weight: 600;
        }
        
        .badge-count {
            margin-left: auto;
            background: var(--mint);
            color: var(--forest);
            font-size: 0.6rem;
            font-weight: 800;
            border-radius: 30px;
            padding: 0.15rem 0.55rem;
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 1.2rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--mint);
            display: grid;
            place-items: center;
            font-weight: 700;
            color: var(--forest);
        }
        
        .user-details span {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
        }
        
        .user-details small {
            font-size: 0.65rem;
            color: rgba(255,255,255,0.45);
        }
        
        /* Topbar */
        #topbar {
            position: fixed;
            top: 0;
            left: 280px;
            right: 0;
            height: 64px;
            background: var(--white);
            border-bottom: 1px solid var(--gray-border);
            display: flex;
            align-items: center;
            padding: 0 1.8rem;
            gap: 1rem;
            z-index: 90;
            box-shadow: var(--shadow-sm);
        }
        
        #topbar h5 {
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
            color: var(--forest);
            font-family: 'Playfair Display', serif;
        }
        
        .search-box {
            position: relative;
            margin-left: auto;
        }
        
        .search-box input {
            padding-left: 2.2rem;
            border: 1px solid var(--gray-border);
            border-radius: 40px;
            font-size: 0.8rem;
            width: 240px;
            height: 38px;
            background: var(--gray-light);
            outline: none;
            transition: all 0.2s;
        }
        
        .search-box input:focus {
            border-color: var(--mint);
            width: 280px;
            background: white;
            box-shadow: 0 0 0 2px rgba(76,175,120,0.1);
        }
        
        .search-box i {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        
        .icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--gray-light);
            border: 1px solid var(--gray-border);
            display: grid;
            place-items: center;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .icon-btn:hover { background: var(--sage); border-color: var(--mint); color: var(--forest); }
        
        /* Main Content */
        #main {
            margin-left: 280px;
            margin-top: 64px;
            padding: 1.8rem;
        }
        
        .page-title {
            font-size: 1.6rem;
            font-weight: 800;
            font-family: 'Playfair Display', serif;
            margin-bottom: 0.2rem;
            color: var(--forest);
        }
        
        .page-subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }
        
        /* Cards */
        .settings-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-border);
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: box-shadow 0.2s;
        }
        
        .settings-card:hover {
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
            padding: 1.2rem 1.5rem;
        }
        
        .settings-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid var(--gray-border);
        }
        
        .settings-row:last-child { border-bottom: none; }
        
        .settings-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-dark);
        }
        
        .settings-desc {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 0.2rem;
        }
        
        .form-control-custom {
            border: 1px solid var(--gray-border);
            border-radius: 10px;
            padding: 0.6rem 1rem;
            font-size: 0.85rem;
            width: 100%;
            transition: all 0.2s;
        }
        
        .form-control-custom:focus {
            border-color: var(--mint);
            outline: none;
            box-shadow: 0 0 0 3px rgba(76,175,120,0.1);
        }
        
        .btn-save {
            background: var(--forest);
            color: white;
            border: none;
            border-radius: 40px;
            padding: 0.7rem 1.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-save:hover {
            background: var(--forest-mid);
            transform: translateY(-1px);
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.7rem 0;
            border-bottom: 1px solid var(--gray-border);
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--text-muted);
            font-size: 0.75rem;
        }
        
        .info-value {
            font-weight: 500;
            color: var(--text-dark);
            font-size: 0.85rem;
        }
        
        .action-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.7rem 0;
            border-bottom: 1px solid var(--gray-border);
        }
        
        .action-item:last-child {
            border-bottom: none;
        }
        
        .action-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--sage);
            display: grid;
            place-items: center;
            font-size: 0.9rem;
            flex-shrink: 0;
            color: var(--forest);
        }
        
        .action-text { flex: 1; }
        .action-title { font-weight: 600; font-size: 0.8rem; color: var(--text-dark); }
        .action-meta { font-size: 0.65rem; color: var(--text-muted); margin-top: 2px; }
        
        .alert-success {
            background: #e6f7ef;
            color: #1e6f3f;
            padding: 0.9rem 1.2rem;
            border-radius: 14px;
            margin-bottom: 1.2rem;
            border-left: 4px solid var(--mint);
        }
        
        .alert-danger {
            background: #fee9e7;
            color: #b13e3e;
            padding: 0.9rem 1.2rem;
            border-radius: 14px;
            margin-bottom: 1.2rem;
            border-left: 4px solid #e07c7c;
        }
        
        .form-switch .form-check-input {
            width: 2.5rem;
            height: 1.3rem;
            cursor: pointer;
        }
        
        .form-switch .form-check-input:checked {
            background-color: var(--mint);
            border-color: var(--mint);
        }
        
        .btn-outline-secondary-custom {
            background: transparent;
            border: 1px solid var(--gray-border);
            border-radius: 30px;
            padding: 0.3rem 0.9rem;
            font-size: 0.7rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-outline-secondary-custom:hover {
            background: var(--sage);
            border-color: var(--mint);
        }
        
        .btn-danger-custom {
            background: #fee9e7;
            color: #b13e3e;
            border: none;
            border-radius: 30px;
            padding: 0.3rem 0.9rem;
            font-size: 0.7rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .btn-danger-custom:hover {
            background: #fcd6d2;
            transform: translateY(-1px);
        }
        
        .empty-state {
            text-align: center;
            padding: 1.5rem;
            color: var(--text-muted);
            font-size: 0.8rem;
        }
        
        .badge-target {
            background: var(--sage);
            color: var(--forest-mid);
            font-size: 0.6rem;
            padding: 0.2rem 0.6rem;
            border-radius: 30px;
            margin-left: 8px;
        }
        
        @media (max-width: 992px) {
            #sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            #topbar, #main { left: 0; margin-left: 0; }
            .settings-row { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
        }
        
        /* Animation */
        .fade-in {
            animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px);}
            to { opacity: 1; transform: translateY(0);}
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">G</div>
        <div class="logo-text">
            <span>Green Bridge</span>
            <small>Administrator Panel</small>
        </div>
    </div>
    
    <div class="sidebar-section">Overview</div>
    <a class="nav-link" href="dashboard.php">
        <i class="bi bi-grid-1x2-fill"></i> Dashboard
    </a>
    <a class="nav-link" href="chat.php">
        <i class="bi bi-chat-fill"></i> Chat Portal
    </a>
    
    <div class="sidebar-section">Verification</div>
    <a class="nav-link" href="student_verification.php">
        <i class="bi bi-person-check-fill"></i> Student Verification
    </a>
    <a class="nav-link" href="company_verification.php">
        <i class="bi bi-building-check"></i> Company Verification
    </a>
    
    <div class="sidebar-section">Content</div>
    <a class="nav-link" href="post_checking.php">
        <i class="bi bi-shield-exclamation"></i> Post Moderation
    </a>
    <a class="nav-link" href="user_reports.php">
        <i class="bi bi-flag-fill"></i> User Reports
    </a>
    
    <div class="sidebar-section">Analytics</div>
    <a class="nav-link" href="analytics.php">
        <i class="bi bi-bar-chart-fill"></i> Reports & Analytics
    </a>
    
    <div class="sidebar-section">System</div>
    <a class="nav-link active" href="settings.php">
        <i class="bi bi-gear-fill"></i> System Settings
    </a>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?php echo strtoupper(substr($admin['email'], 0, 1)); ?></div>
            <div class="user-details">
                <span><?php echo htmlspecialchars($admin['email']); ?></span>
                <small>Administrator</small>
            </div>
        </div>
    </div>
</div>

<!-- Topbar -->
<div id="topbar">
    <h5>System Settings</h5>
    <div class="search-box ms-auto">
        <i class="bi bi-search"></i>
        <input type="text" id="searchSettings" placeholder="Search settings...">
    </div>
    <div class="icon-btn" onclick="window.location.href='../logout.php'">
        <i class="bi bi-box-arrow-right"></i>
    </div>
</div>

<!-- Main Content -->
<div id="main">
    <div class="page-title">System Settings</div>
    <div class="page-subtitle">Configure platform settings, manage your account, and view system information</div>
    
    <?php if ($message): ?>
        <div class="alert-<?php echo $messageType; ?>">
            <i class="bi <?php echo $messageType === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <div class="row g-4">
        <div class="col-lg-6">
            <!-- General Settings -->
            <div class="settings-card">
                <div class="card-header">
                    <i class="bi bi-sliders2"></i> General Settings
                </div>
                <form method="POST" action="">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="settings-label">Platform Name</label>
                            <input type="text" name="site_name" class="form-control-custom" value="<?php echo htmlspecialchars($site_name); ?>">
                            <div class="settings-desc">The name displayed throughout the platform</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="settings-label">Platform URL</label>
                            <input type="text" name="site_url" class="form-control-custom" value="<?php echo htmlspecialchars($site_url); ?>">
                            <div class="settings-desc">Your website URL</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="settings-label">Contact Email</label>
                            <input type="email" name="contact_email" class="form-control-custom" value="<?php echo htmlspecialchars($contact_email); ?>">
                            <div class="settings-desc">Email address for support and contact inquiries</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="settings-label">Maximum File Size (MB)</label>
                            <input type="number" name="max_file_size" class="form-control-custom" value="<?php echo $max_file_size; ?>" min="1" max="50">
                            <div class="settings-desc">Maximum file size allowed for document uploads</div>
                        </div>
                    </div>
            </div>
            
            <!-- Content Moderation -->
            <div class="settings-card">
                <div class="card-header">
                    <i class="bi bi-shield-check"></i> Content Moderation
                </div>
                <div class="card-body">
                    <div class="settings-row">
                        <div>
                            <div class="settings-label">Auto-approve posts</div>
                            <div class="settings-desc">Automatically approve new posts without admin review</div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="auto_approve_posts" <?php echo $auto_approve_posts ? 'checked' : ''; ?>>
                        </div>
                    </div>
                    
                    <div class="settings-row">
                        <div>
                            <div class="settings-label">Require email verification</div>
                            <div class="settings-desc">Users must verify their email address before logging in</div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="require_email_verification" <?php echo $require_email_verification ? 'checked' : ''; ?>>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Save Button -->
            <div class="mb-4">
                <button type="submit" name="save_settings" class="btn-save">
                    <i class="bi bi-save"></i> Save All Settings
                </button>
            </div>
            </form>
        </div>
        
        <div class="col-lg-6">
            <!-- System Information -->
            <div class="settings-card">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> System Information
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">PHP Version</span>
                        <span class="info-value"><?php echo $php_version; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Server Software</span>
                        <span class="info-value"><?php echo $server_software; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Database</span>
                        <span class="info-value"><?php echo $database_type; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Upload Max Filesize</span>
                        <span class="info-value"><?php echo $upload_max_filesize; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Post Max Size</span>
                        <span class="info-value"><?php echo $post_max_size; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Memory Limit</span>
                        <span class="info-value"><?php echo $memory_limit; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total Admin Actions</span>
                        <span class="info-value"><?php echo number_format($adminStats['total_actions']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Recent Admin Actions -->
            <div class="settings-card">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i> Recent Admin Actions
                </div>
                <div class="card-body">
                    <?php if (empty($recentActions)): ?>
                        <div class="empty-state">
                            <i class="bi bi-clock" style="font-size: 1.5rem; opacity: 0.4;"></i>
                            <p class="mt-2">No recent actions recorded</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentActions as $action): ?>
                            <div class="action-item">
                                <div class="action-icon">
                                    <?php
                                        $icon = 'check-circle';
                                        if (strpos($action['action_type'], 'reject') !== false) $icon = 'x-circle';
                                        if (strpos($action['action_type'], 'remove') !== false) $icon = 'trash';
                                        if (strpos($action['action_type'], 'flag') !== false) $icon = 'flag';
                                        if (strpos($action['action_type'], 'resolve') !== false) $icon = 'check2-circle';
                                    ?>
                                    <i class="bi bi-<?php echo $icon; ?>"></i>
                                </div>
                                <div class="action-text">
                                    <div class="action-title">
                                        <?php 
                                            $action_name = str_replace('_', ' ', $action['action_type']);
                                            echo ucfirst($action_name);
                                        ?>
                                        <span class="badge-target"><?php echo ucfirst($action['target_type']); ?></span>
                                    </div>
                                    <div class="action-meta">
                                        By <?php echo htmlspecialchars($action['admin_email']); ?> · 
                                        <?php 
                                            $date = $action['created_at'];
                                            if ($date instanceof DateTime) {
                                                echo $date->format('M d, Y g:i A');
                                            } else {
                                                echo date('M d, Y g:i A', strtotime($date));
                                            }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Account Actions -->
            <div class="settings-card">
                <div class="card-header">
                    <i class="bi bi-person-circle"></i> Account Actions
                </div>
                <div class="card-body">
                    <div class="settings-row">
                        <div>
                            <div class="settings-label">Change Password</div>
                            <div class="settings-desc">Update your administrator account password</div>
                        </div>
                        <button class="btn-outline-secondary-custom" onclick="alert('Password change feature will be available soon.')">
                            <i class="bi bi-key"></i> Change
                        </button>
                    </div>
                    
                    <div class="settings-row">
                        <div>
                            <div class="settings-label">Sign Out</div>
                            <div class="settings-desc">End your current session and log out</div>
                        </div>
                        <a href="../logout.php" class="btn-danger-custom">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Search functionality for settings cards
document.getElementById('searchSettings').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.settings-card');
    
    cards.forEach(card => {
        const cardText = card.textContent.toLowerCase();
        if (searchTerm === '' || cardText.includes(searchTerm)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
});
</script>
</body>
</html>