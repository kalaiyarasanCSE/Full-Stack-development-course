<?php
$page_title = 'Manage Users';
$base_path = '../';
require_once '../config/db.php';
requireLogin();
if (getUserRole() !== 'admin') redirect('../index.php');

$uid = getUserId();
$user = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();

// Delete user
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    if ($del_id !== $uid) {
        $conn->query("DELETE FROM users WHERE id = $del_id");
        redirect('users.php?msg=deleted');
    }
}

// Toggle role
if (isset($_GET['toggle_role'])) {
    $tog_id = (int)$_GET['toggle_role'];
    $new_role = sanitize($_GET['new_role'] ?? '');
    if (in_array($new_role, ['student', 'employer', 'admin']) && $tog_id !== $uid) {
        $conn->query("UPDATE users SET role = '$new_role' WHERE id = $tog_id");
        redirect('users.php?msg=updated');
    }
}

$role_filter = sanitize($_GET['role'] ?? '');
$where = '1=1';
if ($role_filter) $where = "role = '$role_filter'";

$users = $conn->query("SELECT * FROM users WHERE $where ORDER BY created_at DESC");

include '../includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <div class="col-lg-2 sidebar d-none d-lg-block">
            <div class="sidebar-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="user-avatar"><i class="bi bi-shield-check"></i></div>
                    <div>
                        <div class="fw-bold" style="font-size:0.9rem;"><?= htmlspecialchars($user['full_name']) ?></div>
                        <small style="opacity:0.8;">Administrator</small>
                    </div>
                </div>
            </div>
            <nav class="nav flex-column mt-2">
                <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link active" href="users.php"><i class="bi bi-people"></i> Manage Users</a>
                <a class="nav-link" href="jobs.php"><i class="bi bi-briefcase"></i> Manage Jobs</a>
                <a class="nav-link" href="applications.php"><i class="bi bi-file-earmark-text"></i> Applications</a>
                <hr class="mx-3">
                <a class="nav-link text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
        </div>

        <div class="col-lg-10 p-4">
            <div class="mb-4">
                <h4 class="fw-bold mb-1">Manage Users</h4>
                <p class="text-muted mb-0">View and manage all registered users</p>
            </div>

            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-auto-dismiss">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= $_GET['msg'] === 'deleted' ? 'User deleted.' : 'User updated.' ?>
                </div>
            <?php endif; ?>

            <!-- Filter Tabs -->
            <div class="d-flex gap-2 mb-3">
                <a href="users.php" class="btn btn-sm <?= !$role_filter ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
                <a href="?role=student" class="btn btn-sm <?= $role_filter === 'student' ? 'btn-success' : 'btn-outline-success' ?>">Students</a>
                <a href="?role=employer" class="btn btn-sm <?= $role_filter === 'employer' ? 'btn-primary' : 'btn-outline-primary' ?>">Employers</a>
                <a href="?role=admin" class="btn btn-sm <?= $role_filter === 'admin' ? 'btn-danger' : 'btn-outline-danger' ?>">Admins</a>
            </div>

            <div class="mb-3">
                <input type="text" id="tableSearch" class="form-control" placeholder="Search users..." style="max-width:300px;">
            </div>

            <div class="table-card">
                <div class="table-responsive">
                    <table class="table searchable-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Location</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($u = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $u['id'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.8rem;flex-shrink:0;">
                                                <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                            </div>
                                            <?= htmlspecialchars($u['full_name']) ?>
                                        </div>
                                    </td>
                                    <td><small><?= htmlspecialchars($u['email']) ?></small></td>
                                    <td>
                                        <span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : ($u['role'] === 'employer' ? 'bg-primary' : 'bg-success') ?>">
                                            <?= ucfirst($u['role']) ?>
                                        </span>
                                    </td>
                                    <td><small class="text-muted"><?= htmlspecialchars($u['location'] ?? '-') ?></small></td>
                                    <td><small class="text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></small></td>
                                    <td>
                                        <?php if ($u['id'] !== $uid): ?>
                                            <div class="d-flex gap-1">
                                                <a href="?delete=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Delete this user and all their data?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">You</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
