<?php
$page_title = 'Notifications';
$base_path = '../';
require_once '../config/db.php';
requireLogin();

// Mark all as read
$uid = getUserId();
$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $uid");

// Get all notifications
$notifs = $conn->query("SELECT * FROM notifications WHERE user_id = $uid ORDER BY created_at DESC");

include '../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-bell me-2"></i>Notifications</h1>
        <p>Stay updated with your job applications and activity</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="table-card">
                <?php if ($notifs->num_rows === 0): ?>
                    <div class="empty-state">
                        <i class="bi bi-bell-slash"></i>
                        <h5>No Notifications</h5>
                        <p>You're all caught up! No notifications at this time.</p>
                    </div>
                <?php else: ?>
                    <?php while ($n = $notifs->fetch_assoc()): ?>
                        <div class="notification-item <?= $n['is_read'] ? '' : 'unread' ?>">
                            <div class="d-flex align-items-start gap-3">
                                <div style="width:36px;height:36px;border-radius:50%;background:<?= $n['is_read'] ? '#e2e8f0' : '#dbeafe' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="bi bi-bell <?= $n['is_read'] ? 'text-muted' : 'text-primary' ?>"></i>
                                </div>
                                <div>
                                    <p class="mb-1"><?= htmlspecialchars($n['message']) ?></p>
                                    <small class="text-muted"><?= timeAgo($n['created_at']) ?> &bull; <?= date('M d, Y H:i', strtotime($n['created_at'])) ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
