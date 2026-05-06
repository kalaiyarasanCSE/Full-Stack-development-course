<?php
$page_title = 'Activity Log';
$base = '../';
require_once '../config/db.php';
requireRole('admin');

$filter_action = sanitize($_GET['action'] ?? '');
$filter_role   = sanitize($_GET['role'] ?? '');
$search        = sanitize($_GET['search'] ?? '');

$where = '1=1';
if ($filter_action) $where .= " AND al.action = '$filter_action'";
if ($filter_role)   $where .= " AND u.role = '$filter_role'";
if ($search)        $where .= " AND u.full_name LIKE '%$search%'";

$logs = $conn->query("
    SELECT al.*, u.full_name, u.email, u.role
    FROM activity_log al
    JOIN users u ON al.user_id = u.id
    WHERE $where
    ORDER BY al.logged_at DESC
    LIMIT 200
");

$total_logins  = $conn->query("SELECT COUNT(*) c FROM activity_log WHERE action='login'")->fetch_assoc()['c'];
$total_logouts = $conn->query("SELECT COUNT(*) c FROM activity_log WHERE action='logout'")->fetch_assoc()['c'];
$today_logins  = $conn->query("SELECT COUNT(*) c FROM activity_log WHERE action='login' AND DATE(logged_at)=CURDATE()")->fetch_assoc()['c'];

include '../includes/head.php';
?>
<div class="layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div>
            <div class="topbar-title">Activity Log</div>
            <div class="topbar-sub">Monitor all login and logout activity</div>
        </div>
    </div>
    <div class="page-body">

        <!-- Stats -->
        <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px;">
            <div class="stat-card bg-green">
                <i class="bi bi-box-arrow-in-right"></i>
                <h2><?= $total_logins ?></h2><p>Total Logins</p>
            </div>
            <div class="stat-card bg-orange">
                <i class="bi bi-box-arrow-right"></i>
                <h2><?= $total_logouts ?></h2><p>Total Logouts</p>
            </div>
            <div class="stat-card bg-blue">
                <i class="bi bi-calendar-check"></i>
                <h2><?= $today_logins ?></h2><p>Today's Logins</p>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
            <input type="text" name="search" class="form-control" placeholder="Search by name..."
                   value="<?= htmlspecialchars($search) ?>" style="max-width:220px;">
            <select name="action" class="form-select" style="max-width:140px;">
                <option value="">All Actions</option>
                <option value="login"  <?= $filter_action==='login'  ? 'selected':'' ?>>Login</option>
                <option value="logout" <?= $filter_action==='logout' ? 'selected':'' ?>>Logout</option>
            </select>
            <select name="role" class="form-select" style="max-width:140px;">
                <option value="">All Roles</option>
                <option value="admin"     <?= $filter_role==='admin'     ? 'selected':'' ?>>Admin</option>
                <option value="hr"        <?= $filter_role==='hr'        ? 'selected':'' ?>>HR</option>
                <option value="jobseeker" <?= $filter_role==='jobseeker' ? 'selected':'' ?>>Job Seeker</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Filter</button>
            <a href="activity.php" class="btn btn-outline btn-sm">Clear</a>
        </form>

        <div class="table-wrap">
            <div class="card-header">
                <h5><i class="bi bi-clock-history"></i> Login / Logout Records</h5>
            </div>
            <table>
                <thead>
                    <tr><th>#</th><th>User</th><th>Role</th><th>Action</th><th>IP Address</th><th>Date & Time</th></tr>
                </thead>
                <tbody>
                <?php if ($logs->num_rows === 0): ?>
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:#94a3b8;">No activity records found.</td></tr>
                <?php else: ?>
                <?php $i = 1; while ($log = $logs->fetch_assoc()): ?>
                    <tr>
                        <td class="text-muted text-small"><?= $i++ ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($log['full_name']) ?></div>
                            <div class="text-muted text-small"><?= htmlspecialchars($log['email']) ?></div>
                        </td>
                        <td><span class="badge badge-<?= $log['role'] ?>"><?= ucfirst($log['role']) ?></span></td>
                        <td>
                            <?php if ($log['action'] === 'login'): ?>
                                <span style="color:#16a34a;font-weight:600;"><i class="bi bi-box-arrow-in-right"></i> Login</span>
                            <?php else: ?>
                                <span style="color:#dc2626;font-weight:600;"><i class="bi bi-box-arrow-right"></i> Logout</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-small text-muted"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                        <td class="text-small text-muted"><?= date('d M Y, h:i A', strtotime($log['logged_at'])) ?></td>
                    </tr>
                <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>
</div>
</body></html>
