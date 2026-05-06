<?php
require_once __DIR__ . '/../config/db.php';

$notifications = [];
$unread_count = 0;
if (isLoggedIn()) {
    $notifications = getUnreadNotifications(getUserId());
    $unread_count = count($notifications);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Job Portal' ?> | JobConnect</title>
    <meta name="base_path" content="<?= $base_path ?? '../' ?>">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= $base_path ?? '../' ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- ===== Innovation: Chatbot Widget CSS ===== -->
<style>
#chatbot-btn{position:fixed;bottom:24px;right:24px;width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);color:white;border:none;font-size:1.5rem;cursor:pointer;box-shadow:0 4px 20px rgba(37,99,235,0.4);z-index:9999;transition:transform 0.2s;}
#chatbot-btn:hover{transform:scale(1.1);}
#chatbot-box{position:fixed;bottom:90px;right:24px;width:320px;background:white;border-radius:16px;box-shadow:0 10px 40px rgba(0,0,0,0.15);z-index:9999;display:none;flex-direction:column;overflow:hidden;}
#chatbot-header{background:linear-gradient(135deg,#2563eb,#7c3aed);color:white;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;}
#chatbot-messages{height:280px;overflow-y:auto;padding:12px;display:flex;flex-direction:column;gap:8px;}
.bot-msg{background:#f1f5f9;padding:10px 14px;border-radius:12px 12px 12px 0;font-size:0.85rem;max-width:85%;white-space:pre-line;}
.user-msg{background:linear-gradient(135deg,#2563eb,#7c3aed);color:white;padding:10px 14px;border-radius:12px 12px 0 12px;font-size:0.85rem;max-width:85%;align-self:flex-end;}
#chatbot-input-area{display:flex;border-top:1px solid #e2e8f0;padding:10px;}
#chatbot-input{flex:1;border:none;outline:none;font-size:0.9rem;padding:4px 8px;}
#chatbot-send{background:linear-gradient(135deg,#2563eb,#7c3aed);color:white;border:none;border-radius:8px;padding:6px 14px;cursor:pointer;}
</style>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?= $base_path ?? '../' ?>index.php">
            <i class="bi bi-briefcase-fill"></i> Job<span>Connect</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base_path ?? '../' ?>index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base_path ?? '../' ?>student/browse-jobs.php">Browse Jobs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base_path ?? '../' ?>innovation/maps.php">
                        <i class="bi bi-map me-1"></i>Job Map
                    </a>
                </li>
                <?php if (isLoggedIn() && getUserRole() === 'student'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $base_path ?? '../' ?>innovation/job_alert.php">
                        <i class="bi bi-bell me-1"></i>Job Alerts
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <?php if (isLoggedIn()): ?>
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" id="notifBell" data-bs-toggle="dropdown">
                            <i class="bi bi-bell fs-5"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notif-badge" style="font-size:0.65rem;">
                                    <?= $unread_count ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end notification-dropdown p-0">
                            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                                <strong>Notifications</strong>
                                <?php if ($unread_count > 0): ?>
                                    <span class="badge bg-primary"><?= $unread_count ?> new</span>
                                <?php endif; ?>
                            </div>
                            <?php if (empty($notifications)): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="bi bi-bell-slash fs-3 d-block mb-2"></i>
                                    No new notifications
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notif): ?>
                                    <div class="notification-item unread">
                                        <p><?= htmlspecialchars($notif['message']) ?></p>
                                        <small><?= timeAgo($notif['created_at']) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <div class="p-2 border-top text-center">
                                <a href="<?= $base_path ?? '../' ?>auth/notifications.php" class="small text-primary">View all</a>
                            </div>
                        </div>
                    </li>

                    <!-- User Menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                            <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.85rem;">
                                <?= strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header"><?= ucfirst($_SESSION['role'] ?? '') ?> Account</h6></li>
                            <?php if ($_SESSION['role'] === 'student'): ?>
                                <li><a class="dropdown-item" href="<?= $base_path ?? '../' ?>student/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?= $base_path ?? '../' ?>student/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                                <li><a class="dropdown-item" href="<?= $base_path ?? '../' ?>student/my-applications.php"><i class="bi bi-file-text me-2"></i>My Applications</a></li>
                            <?php elseif ($_SESSION['role'] === 'employer'): ?>
                                <li><a class="dropdown-item" href="<?= $base_path ?? '../' ?>employer/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?= $base_path ?? '../' ?>employer/post-job.php"><i class="bi bi-plus-circle me-2"></i>Post a Job</a></li>
                                <li><a class="dropdown-item" href="<?= $base_path ?? '../' ?>employer/manage-jobs.php"><i class="bi bi-briefcase me-2"></i>Manage Jobs</a></li>
                            <?php elseif ($_SESSION['role'] === 'admin'): ?>
                                <li><a class="dropdown-item" href="<?= $base_path ?? '../' ?>admin/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Admin Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= $base_path ?? '../' ?>auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_path ?? '../' ?>auth/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm px-3" href="<?= $base_path ?? '../' ?>auth/register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- ===== Innovation: Chatbot Widget ===== -->
<button id="chatbot-btn" title="Chat with us">💬</button>

<div id="chatbot-box">
    <div id="chatbot-header">
        <div>
            <div class="fw-bold">JobConnect Assistant 🤖</div>
            <small style="opacity:0.8;">Ask me anything about jobs!</small>
        </div>
        <button onclick="toggleChat()" style="background:none;border:none;color:white;font-size:1.2rem;cursor:pointer;">✕</button>
    </div>
    <div id="chatbot-messages">
        <div class="bot-msg">👋 Hi! I'm your JobConnect assistant. Ask me about jobs, how to apply, salaries, or anything else!</div>
    </div>
    <div id="chatbot-input-area">
        <input type="text" id="chatbot-input" placeholder="Type a message..." onkeypress="if(event.key==='Enter') sendChat()">
        <button id="chatbot-send" onclick="sendChat()">Send</button>
    </div>
</div>

<script>
function toggleChat() {
    const box = document.getElementById('chatbot-box');
    box.style.display = box.style.display === 'flex' ? 'none' : 'flex';
    box.style.flexDirection = 'column';
    if (box.style.display === 'flex') {
        document.getElementById('chatbot-input').focus();
    }
}

document.getElementById('chatbot-btn').addEventListener('click', toggleChat);

function sendChat() {
    const input = document.getElementById('chatbot-input');
    const msg = input.value.trim();
    if (!msg) return;

    const messages = document.getElementById('chatbot-messages');

    // Add user message
    const userDiv = document.createElement('div');
    userDiv.className = 'user-msg';
    userDiv.textContent = msg;
    messages.appendChild(userDiv);
    input.value = '';
    messages.scrollTop = messages.scrollHeight;

    // Add typing indicator
    const typing = document.createElement('div');
    typing.className = 'bot-msg';
    typing.textContent = '...';
    typing.id = 'typing';
    messages.appendChild(typing);
    messages.scrollTop = messages.scrollHeight;

    // Send to chatbot API
    const base = document.querySelector('meta[name="base_path"]')?.content || '../';
    fetch(base + 'innovation/chatbot.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'message=' + encodeURIComponent(msg)
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('typing')?.remove();
        const botDiv = document.createElement('div');
        botDiv.className = 'bot-msg';
        botDiv.textContent = data.response;
        messages.appendChild(botDiv);
        messages.scrollTop = messages.scrollHeight;
    })
    .catch(() => {
        document.getElementById('typing')?.remove();
        const botDiv = document.createElement('div');
        botDiv.className = 'bot-msg';
        botDiv.textContent = 'Sorry, I am having trouble connecting. Please try again!';
        messages.appendChild(botDiv);
    });
}
</script>
