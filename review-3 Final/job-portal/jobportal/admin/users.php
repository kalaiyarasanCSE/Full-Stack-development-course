<?php
$page_title = 'Manage Users';
$base = '../';
require_once '../config/db.php';
requireRole('admin');

// Delete user — admin account is protected
if (isset($_GET['delete'])) {
    $del = (int)$_GET['delete'];
    // Never allow deleting admin account (id=1) or self
    $target = $conn->query("SELECT role FROM users WHERE id=$del")->fetch_assoc();
    if ($del !== getUserId() && $target && $target['role'] !== 'admin') {
        $conn->query("DELETE FROM users WHERE id = $del");
    }
    redirect('users.php?msg=deleted');
}

$role_filter = sanitize($_GET['role'] ?? '');
$search      = sanitize($_GET['search'] ?? '');
$where = '1=1';
if ($role_filter) $where .= " AND role = '$role_filter'";
if ($search)      $where .= " AND (full_name LIKE '%$search%' OR email LIKE '%$search%')";

$users = $conn->query("SELECT * FROM users WHERE $where ORDER BY created_at DESC");

include '../includes/head.php';
?>
<div class="layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div>
            <div class="topbar-title">Manage Users</div>
            <div class="topbar-sub">View and manage all registered users</div>
        </div>
    </div>
    <div class="page-body">

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> User deleted successfully.</div>
        <?php endif; ?>

        <!-- Filters -->
        <form method="GET" style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
            <input type="text" name="search" class="form-control" placeholder="Search name or email..."
                   value="<?= htmlspecialchars($search) ?>" style="max-width:260px;">
            <select name="role" class="form-select" style="max-width:160px;" onchange="this.form.submit()">
                <option value="">All Roles</option>
                <option value="admin"     <?= $role_filter==='admin'     ? 'selected':'' ?>>Admin</option>
                <option value="hr"        <?= $role_filter==='hr'        ? 'selected':'' ?>>HR</option>
                <option value="jobseeker" <?= $role_filter==='jobseeker' ? 'selected':'' ?>>Job Seeker</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Search</button>
            <a href="users.php" class="btn btn-outline btn-sm">Clear</a>
        </form>

        <div class="table-wrap">
            <div class="card-header">
                <h5><i class="bi bi-people"></i> All Users (<?= $users->num_rows ?>)</h5>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th><th>Name</th><th>Email</th><th>Role</th>
                        <th>Phone</th><th>Location</th><th>Joined</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($users->num_rows === 0): ?>
                    <tr><td colspan="8" style="text-align:center;padding:40px;color:#94a3b8;">No users found.</td></tr>
                <?php else: ?>
                <?php while ($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td class="text-muted text-small"><?= $u['id'] ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.85rem;flex-shrink:0;">
                                    <?= strtoupper(substr($u['full_name'],0,1)) ?>
                                </div>
                                <span class="fw-semibold"><?= htmlspecialchars($u['full_name']) ?></span>
                            </div>
                        </td>
                        <td class="text-small"><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                        <td class="text-small text-muted"><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                        <td class="text-small text-muted"><?= htmlspecialchars($u['location'] ?? '-') ?></td>
                        <td class="text-small text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <?php if ($u['id'] !== getUserId() && $u['role'] !== 'admin'): ?>
                                <a href="?delete=<?= $u['id'] ?>" class="btn btn-danger btn-sm"
                                   onclick="return confirm('Delete this user and all their data?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            <?php elseif ($u['role'] === 'admin'): ?>
                                <span style="font-size:0.78rem;color:#dc2626;font-weight:600;"><i class="bi bi-shield-lock"></i> Protected</span>
                            <?php else: ?>
                                <span class="text-muted text-small">You</span>
                            <?php endif; ?>
                        </td>
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
