<?php
// company/chat.php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

requireCompany();

$user_id = $_SESSION['user_id'];
$company = getCompanyData($conn, $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Chat - GreenBridge</title>
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
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        /* Navbar Refined */
        .navbar {
            background: var(--forest) !important;
            padding: 0.7rem 2rem;
            height: 64px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            flex-shrink: 0;
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
            flex-shrink: 0;
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
        
        /* Chat Layout */
        .chat-container {
            display: flex;
            flex: 1;
            overflow: hidden;
            padding: 1.5rem;
            gap: 1.5rem;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
        }
        
        /* Sidebar Glassmorphic */
        .chat-sidebar {
            flex: 0 0 350px;
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
            background: var(--mint-light);
            color: var(--mint);
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
                    <div class="user-avatar"><?php echo strtoupper(substr($company['company_name'], 0, 2)); ?></div>
                    <div>
                        <div style="font-size:13px; font-weight:600; color:white"><?php echo htmlspecialchars($company['company_name']); ?></div>
                        <div style="font-size:10px; color:rgba(255,255,255,0.6)">Company Account</div>
                    </div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li><a class="dropdown-item" href="profile.php"><i class="bi bi-building me-2"></i> Company Profile</a></li>
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
    <div class="subnav-item active">Chat</div>
    <div class="subnav-item" onclick="location.href='scout_students.php'">Scout Students</div>
    <div class="subnav-item" onclick="location.href='manage_interns.php'">Manage Interns</div>
    <div class="subnav-item" onclick="location.href='profile.php'">Profile</div>
</div>

<div class="chat-container">
    <div class="chat-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-title">
                <i class="bi bi-chat-fill"></i> Messages
            </div>
        </div>
        <div class="contact-list" id="contact-list">
            <!-- Dynamically Loaded Contacts -->
            <div class="text-center py-4 text-muted">Loading conversations...</div>
        </div>
    </div>
    
    <div class="chat-main" id="chat-main">
        <div class="chat-empty" id="chat-empty">
            <i class="bi bi-chat-left-dots"></i>
            <h4 class="fw-bold">Your Conversations</h4>
            <p>Select an applicant or intern from the sidebar to start sending real-time messages.</p>
        </div>
        
        <div class="d-none flex-column h-100" id="chat-active">
            <div class="chat-header">
                <div class="active-user-info">
                    <div class="contact-avatar" id="active-avatar">S</div>
                    <div>
                        <span class="active-user-name" id="active-name">Student Candidate</span>
                        <span class="active-user-role" id="active-role">Student</span>
                    </div>
                </div>
            </div>
            
            <div class="chat-messages" id="chat-messages">
                <!-- Dynamically Loaded Message Bubbles -->
            </div>
            
            <div class="chat-footer">
                <form id="chat-send-form" autocomplete="off">
                    <div class="chat-input-row">
                        <input type="text" id="chat-input" class="chat-input" placeholder="Type a message..." required>
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
        listDiv.innerHTML = '<div class="text-center py-4 text-muted">No active contacts available</div>';
        return;
    }
    
    let html = '';
    contactsList.forEach(c => {
        const isActive = c.id == activeContactId ? 'active' : '';
        const initial = c.name.charAt(0).toUpperCase();
        const unreadHTML = c.unread_count > 0 ? `<span class="unread-indicator">${c.unread_count}</span>` : '';
        
        let badgeColor = '#4caf78'; // green for student
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
    document.getElementById('active-role').innerText = role.charAt(0).toUpperCase() + role.slice(1);
    
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
