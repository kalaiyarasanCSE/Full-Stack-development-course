<?php
$page_title = 'Admin Dashboard';
$base = '../';
require_once '../config/db.php';
requireRole('admin');

$total_users     = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
$total_hr        = $conn->query("SELECT COUNT(*) c FROM users WHERE role='hr'")->fetch_assoc()['c'];
$total_seekers   = $conn->query("SELECT COUNT(*) c FROM users WHERE role='jobseeker'")->fetch_assoc()['c'];
$total_jobs      = $conn->query("SELECT COUNT(*) c FROM jobs")->fetch_assoc()['c'];
$active_jobs     = $conn->query("SELECT COUNT(*) c FROM jobs WHERE status='active'")->fetch_assoc()['c'];
$total_apps      = $conn->query("SELECT COUNT(*) c FROM applications")->fetch_assoc()['c'];

$recent_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 6");
$recent_logs  = $conn->query("
    SELECT al.*, u.full_name, u.role FROM activity_log al
    JOIN users u ON al.user_id = u.id
    ORDER BY al.logged_at DESC LIMIT 8
");
if (!$recent_logs) $recent_logs = new class { public function fetch_assoc() { return null; } };

include '../includes/head.php';
?>
<div class="layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div>
            <div class="topbar-title">Admin Dashboard</div>
            <div class="topbar-sub">System overview</div>
        </div>
        <span style="font-size:0.85rem;color:#94a3b8;"><?= date('D, d M Y') ?></span>
    </div>
    <div class="page-body">

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card bg-blue">
                <i class="bi bi-people"></i>
                <h2><?= $total_users ?></h2><p>Total Users</p>
            </div>
            <div class="stat-card bg-purple">
                <i class="bi bi-building"></i>
                <h2><?= $total_hr ?></h2><p>HR Recruiters</p>
            </div>
            <div class="stat-card bg-green">
                <i class="bi bi-person-graduation"></i>
                <h2><?= $total_seekers ?></h2><p>Job Seekers</p>
            </div>
            <div class="stat-card bg-orange">
                <i class="bi bi-briefcase"></i>
                <h2><?= $total_jobs ?></h2><p>Total Jobs</p>
            </div>
            <div class="stat-card bg-teal">
                <i class="bi bi-check-circle"></i>
                <h2><?= $active_jobs ?></h2><p>Active Jobs</p>
            </div>
            <div class="stat-card bg-pink">
                <i class="bi bi-file-earmark-text"></i>
                <h2><?= $total_apps ?></h2><p>Applications</p>
            </div>
        </div>

        <div class="grid-2">
            <!-- Recent Users -->
            <div class="table-wrap">
                <div class="card-header">
                    <h5><i class="bi bi-people me-2"></i>Recent Users</h5>
                    <a href="users.php" class="btn btn-outline btn-sm">View All</a>
                </div>
                <table>
                    <thead><tr><th>Name</th><th>Role</th><th>Joined</th></tr></thead>
                    <tbody>
                    <?php while ($u = $recent_users->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($u['full_name']) ?></div>
                                <div class="text-muted text-small"><?= htmlspecialchars($u['email']) ?></div>
                            </td>
                            <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                            <td class="text-muted text-small"><?= timeAgo($u['created_at']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Activity -->
            <div class="table-wrap">
                <div class="card-header">
                    <h5><i class="bi bi-clock-history me-2"></i>Recent Activity</h5>
                    <a href="activity.php" class="btn btn-outline btn-sm">View All</a>
                </div>
                <table>
                    <thead><tr><th>User</th><th>Action</th><th>Time</th></tr></thead>
                    <tbody>
                    <?php while ($log = $recent_logs->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($log['full_name']) ?></div>
                                <span class="badge badge-<?= $log['role'] ?>"><?= ucfirst($log['role']) ?></span>
                            </td>
                            <td>
                                <span style="color:<?= $log['action']==='login' ? '#16a34a' : '#dc2626' ?>;font-weight:600;">
                                    <i class="bi bi-<?= $log['action']==='login' ? 'box-arrow-in-right' : 'box-arrow-right' ?>"></i>
                                    <?= ucfirst($log['action']) ?>
                                </span>
                            </td>
                            <td class="text-muted text-small"><?= timeAgo($log['logged_at']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
</div>
</body></html>
