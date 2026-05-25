<?php
// admin/chat.php
require_once '../includes/config.php';
require_once 'includes/admin_auth.php'; // Ensures only authenticated admins can access

$user_id = $_SESSION['user_id'];
$admin_email = $_SESSION['user_email'] ?? 'admin@greenbridge.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Chat - GreenBridge</title>
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
            --red: #e74c3c;
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
            height: 100vh;
            overflow: hidden;
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
        
        /* Main Layout */
        #main {
            margin-left: 280px;
            margin-top: 64px;
            padding: 1.5rem;
            height: calc(100vh - 64px);
            display: flex;
            gap: 1.5rem;
        }
        
        /* Sidebar Glassmorphic */
        .chat-sidebar {
            flex: 0 0 320px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border: 1px solid var(--gray-border);
            border-radius: var(--radius-lg);
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }
        
        .sidebar-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--gray-border);
            background: rgba(26,58,36,0.02);
        }
        
        .sidebar-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--forest);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .contact-list {
            flex: 1;
            overflow-y: auto;
            padding: 0.5rem;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.8rem 1rem;
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 0.4rem;
        }
        
        .contact-item:hover {
            background: var(--sage);
        }
        
        .contact-item.active {
            background: var(--mint-light);
            border-left: 4px solid var(--mint);
        }
        
        .contact-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--sage-dk);
            display: grid;
            place-items: center;
            font-weight: 700;
            color: var(--forest);
            position: relative;
            flex-shrink: 0;
        }
        
        .contact-badge {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid white;
        }
        
        .contact-info {
            flex: 1;
            min-width: 0;
        }
        
        .contact-name-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 2px;
        }
        
        .contact-name {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .contact-time {
            font-size: 0.65rem;
            color: var(--text-muted);
        }
        
        .contact-lastmsg {
            font-size: 0.75rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .unread-indicator {
            background: var(--mint);
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            min-width: 18px;
            height: 18px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            padding: 0 4px;
        }
        
        /* Chat Main Box */
        .chat-main {
            flex: 1;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border: 1px solid var(--gray-border);
            border-radius: var(--radius-lg);
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }
        
        .chat-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(26,58,36,0.02);
        }
        
        .active-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .active-user-name {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--forest);
        }
        
        .active-user-role {
            font-size: 0.7rem;
            padding: 0.15rem 0.5rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            background: rgba(249, 251, 248, 0.5);
        }
        
        .message-row {
            display: flex;
            width: 100%;
        }
        
        .message-row.sent {
            justify-content: flex-end;
        }
        
        .message-row.received {
            justify-content: flex-start;
        }
        
        .message-bubble {
            max-width: 60%;
            padding: 0.8rem 1rem;
            border-radius: var(--radius);
            font-size: 0.85rem;
            line-height: 1.45;
            box-shadow: var(--shadow-sm);
            position: relative;
        }
        
        .message-row.sent .message-bubble {
            background: var(--forest);
            color: white;
            border-bottom-right-radius: 2px;
        }
        
        .message-row.received .message-bubble {
            background: white;
            color: var(--text-dark);
            border: 1px solid var(--gray-border);
            border-bottom-left-radius: 2px;
        }
        
        .message-time {
            font-size: 0.6rem;
            margin-top: 4px;
            text-align: right;
            opacity: 0.7;
        }
        
        .chat-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--gray-border);
            background: white;
        }
        
        .chat-input-row {
            display: flex;
            gap: 10px;
        }
        
        .chat-input {
            flex: 1;
            border: 1px solid var(--gray-border);
            border-radius: 30px;
            padding: 0.7rem 1.2rem;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        
        .chat-input:focus {
            outline: none;
            border-color: var(--mint);
            box-shadow: 0 0 0 3px rgba(76,175,120,0.15);
        }
        
        .btn-send {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--forest);
            color: white;
            border: none;
            display: grid;
            place-items: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1.1rem;
        }
        
        .btn-send:hover {
            background: var(--forest-mid);
            transform: scale(1.05);
        }
        
        /* Empty State */
        .chat-empty {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: var(--text-muted);
            padding: 3rem;
            text-align: center;
        }
        
        .chat-empty i {
            font-size: 4rem;
            color: var(--sage-dk);
            margin-bottom: 1rem;
        }
        
        .notification-banner {
            background: var(--mint-light);
            border: 1px solid rgba(76,175,120,0.3);
            padding: 8px 16px;
            border-radius: var(--radius);
            font-size: 0.75rem;
            font-weight: 500;
            color: #1e6f3f;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .notification-banner button {
            border: none;
            background: var(--mint);
            color: white;
            font-size: 0.7rem;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
        }
        
        @media (max-width: 992px) {
            #sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            #topbar, #main { left: 0; margin-left: 0; }
        }
    </style>
</head>
<body>

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
    <a class="nav-link active" href="chat.php">
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
    <a class="nav-link" href="settings.php">
        <i class="bi bi-gear-fill"></i> System Settings
    </a>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">A</div>
            <div class="user-details">
                <span><?php echo htmlspecialchars($admin_email); ?></span>
                <small>Administrator</small>
            </div>
        </div>
    </div>
</div>

<div id="topbar">
    <h5>Platform Support Chat</h5>
</div>

<div id="main">
    <div class="chat-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-title">
                <i class="bi bi-chat-fill"></i> Users & Members
            </div>
        </div>
        <div class="contact-list" id="contact-list">
            <div class="text-center py-4 text-muted">Loading system members...</div>
        </div>
    </div>
    
    <div class="chat-main" id="chat-main">
        <div class="chat-empty" id="chat-empty">
            <i class="bi bi-chat-left-dots"></i>
            <h4 class="fw-bold">Platform Direct Messaging</h4>
            <p>Select any registered student, representative company, or moderator to check messages and support them directly.</p>
        </div>
        
        <div class="d-none flex-column h-100" id="chat-active">
            <div class="chat-header">
                <div class="active-user-info">
                    <div class="contact-avatar" id="active-avatar">U</div>
                    <div>
                        <span class="active-user-name" id="active-name">User Account</span>
                        <span class="active-user-role" id="active-role">Student</span>
                    </div>
                </div>
            </div>
            
            <div class="chat-messages" id="chat-messages">
                <!-- Dynamically Loaded Messages -->
            </div>
            
            <div class="chat-footer">
                <form id="chat-send-form" autocomplete="off">
                    <div class="chat-input-row">
                        <input type="text" id="chat-input" class="chat-input" placeholder="Type a support reply..." required>
                        <button type="submit" class="btn-send"><i class="bi bi-send-fill"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let activeContactId = null;
let activeContactName = '';
let activeContactRole = '';
let contactsList = [];
let lastMessageCount = 0;

// Setup Desktop Notifications API
function initNotifications() {
    if (!("Notification" in window)) {
        console.log("This browser does not support desktop notifications.");
        return;
    }
    
    if (Notification.permission !== "granted" && Notification.permission !== "denied") {
        const header = document.querySelector('.sidebar-header');
        const banner = document.createElement('div');
        banner.className = 'notification-banner';
        banner.innerHTML = `
            <span>Enable alerts for new messages?</span>
            <button onclick="requestPermissionAndHide(this)">Allow</button>
        `;
        header.after(banner);
    }
}

function requestPermissionAndHide(btn) {
    Notification.requestPermission().then(permission => {
        if (permission === "granted") {
            console.log("Desktop notification permission granted.");
        }
        btn.parentElement.remove();
    });
}

function showNotification(senderName, messageText) {
    if (Notification.permission === "granted" && document.visibilityState === "hidden") {
        new Notification("GreenBridge Alert", {
            body: senderName + ": " + messageText,
            icon: "https://cdn-icons-png.flaticon.com/512/3135/3135715.png"
        });
    }
}

// Fetch Contacts Sidebar
function loadContacts() {
    fetch('../api/chat_handler.php?action=get_contacts')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                contactsList = data.contacts;
                renderContacts();
            }
        });
}

function renderContacts() {
    const listDiv = document.getElementById('contact-list');
    
    if (contactsList.length === 0) {
        listDiv.innerHTML = '<div class="text-center py-4 text-muted">No system members registered</div>';
        return;
    }
    
    let html = '';
    contactsList.forEach(c => {
        const isActive = c.id == activeContactId ? 'active' : '';
        const initial = c.name.charAt(0).toUpperCase();
        const unreadHTML = c.unread_count > 0 ? `<span class="unread-indicator">${c.unread_count}</span>` : '';
        
        let badgeColor = '#4caf78'; // student
        if (c.type === 'company') badgeColor = '#c9952a'; // company
        if (c.type === 'admin') badgeColor = '#1a3a24'; // admin
        
        html += `
            <div class="contact-item ${isActive}" onclick="selectContact(${c.id}, '${c.name.replace(/'/g, "\\'")}', '${c.type}')">
                <div class="contact-avatar">
                    ${initial}
                    <span class="contact-badge" style="background: ${badgeColor}"></span>
                </div>
                <div class="contact-info">
                    <div class="contact-name-row">
                        <span class="contact-name">${c.name}</span>
                        <span class="contact-time">${formatTime(c.last_message_time)}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="contact-lastmsg">${c.last_message}</span>
                        ${unreadHTML}
                    </div>
                </div>
            </div>
        `;
    });
    listDiv.innerHTML = html;
}

function formatTime(timestamp) {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// Select Contact & Show Chat Room
function selectContact(id, name, role) {
    activeContactId = id;
    activeContactName = name;
    activeContactRole = role;
    
    document.getElementById('chat-empty').classList.add('d-none');
    document.getElementById('chat-active').classList.remove('d-none');
    document.getElementById('chat-active').classList.add('d-flex');
    
    document.getElementById('active-avatar').innerText = name.charAt(0).toUpperCase();
    document.getElementById('active-name').innerText = name;
    
    // Style active user badge and title
    const roleBadge = document.getElementById('active-role');
    roleBadge.innerText = role.toUpperCase();
    if (role === 'student') {
        roleBadge.style.background = '#e0f5ea';
        roleBadge.style.color = '#1e6f3f';
    } else if (role === 'company') {
        roleBadge.style.background = '#fef3cd';
        roleBadge.style.color = '#856404';
    } else {
        roleBadge.style.background = '#eef3ec';
        roleBadge.style.color = '#1a3a24';
    }
    
    renderContacts();
    loadMessages(true);
}

// Fetch Messages
function loadMessages(shouldScroll = false) {
    if (!activeContactId) return;
    
    fetch(`../api/chat_handler.php?action=get_messages&contact_id=${activeContactId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const messageContainer = document.getElementById('chat-messages');
                
                if (data.messages.length > lastMessageCount) {
                    const newMsgsCount = data.messages.length - lastMessageCount;
                    if (lastMessageCount > 0) {
                        const lastMsg = data.messages[data.messages.length - 1];
                        if (lastMsg.sender_id == activeContactId) {
                            showNotification(activeContactName, lastMsg.message);
                        }
                    }
                    lastMessageCount = data.messages.length;
                    shouldScroll = true;
                }
                
                let html = '';
                data.messages.forEach(m => {
                    const isSent = m.sender_id == <?php echo $user_id; ?>;
                    const rowClass = isSent ? 'sent' : 'received';
                    const time = formatTime(m.created_at);
                    
                    html += `
                        <div class="message-row ${rowClass}">
                            <div class="message-bubble">
                                <div>${m.message}</div>
                                <div class="message-time">${time}</div>
                            </div>
                        </div>
                    `;
                });
                
                messageContainer.innerHTML = html;
                
                if (shouldScroll) {
                    messageContainer.scrollTop = messageContainer.scrollHeight;
                }
            }
        });
}

// Send Message
document.getElementById('chat-send-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const input = document.getElementById('chat-input');
    const msg = input.value.trim();
    if (!msg || !activeContactId) return;
    
    const formData = new FormData();
    formData.append('contact_id', activeContactId);
    formData.append('message', msg);
    
    const messageContainer = document.getElementById('chat-messages');
    const timeNow = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    messageContainer.innerHTML += `
        <div class="message-row sent">
            <div class="message-bubble">
                <div>${msg}</div>
                <div class="message-time">${timeNow}</div>
            </div>
        </div>
    `;
    messageContainer.scrollTop = messageContainer.scrollHeight;
    input.value = '';
    
    fetch('../api/chat_handler.php?action=send_message', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            loadContacts();
            loadMessages(true);
        }
    });
});

// Run Polling
initNotifications();
loadContacts();
setInterval(loadContacts, 3000);
setInterval(loadMessages, 2000);
</script>
</body>
</html>
