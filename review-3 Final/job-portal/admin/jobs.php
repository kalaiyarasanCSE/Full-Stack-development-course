<?php
$page_title = 'Manage Jobs';
$base_path = '../';
require_once '../config/db.php';
requireLogin();
if (getUserRole() !== 'admin') redirect('../index.php');

$uid = getUserId();
$user = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();

// Delete job
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM jobs WHERE id = $del_id");
    redirect('jobs.php?msg=deleted');
}

// Toggle status
if (isset($_GET['toggle'])) {
    $tog_id = (int)$_GET['toggle'];
    $current = $conn->query("SELECT status FROM jobs WHERE id = $tog_id")->fetch_assoc();
    if ($current) {
        $new_status = $current['status'] === 'active' ? 'closed' : 'active';
        $conn->query("UPDATE jobs SET status = '$new_status' WHERE id = $tog_id");
    }
    redirect('jobs.php?msg=updated');
}

$status_filter = sanitize($_GET['status'] ?? '');
$where = '1=1';
if ($status_filter) $where = "j.status = '$status_filter'";

$jobs = $conn->query("
    SELECT j.*, ep.company_name, u.email as employer_email,
           COUNT(a.id) as app_count
    FROM jobs j
    JOIN employer_profiles ep ON j.employer_id = ep.user_id
    JOIN users u ON j.employer_id = u.id
    LEFT JOIN applications a ON j.id = a.job_id
    WHERE $where
    GROUP BY j.id
    ORDER BY j.created_at DESC
");

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
                <a class="nav-link" href="users.php"><i class="bi bi-people"></i> Manage Users</a>
                <a class="nav-link active" href="jobs.php"><i class="bi bi-briefcase"></i> Manage Jobs</a>
                <a class="nav-link" href="applications.php"><i class="bi bi-file-earmark-text"></i> Applications</a>
                <hr class="mx-3">
                <a class="nav-link text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
        </div>

        <div class="col-lg-10 p-4">
            <div class="mb-4">
                <h4 class="fw-bold mb-1">Manage Jobs</h4>
                <p class="text-muted mb-0">View and moderate all job listings</p>
            </div>

            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-auto-dismiss">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= $_GET['msg'] === 'deleted' ? 'Job deleted.' : 'Job status updated.' ?>
                </div>
            <?php endif; ?>

            <div class="d-flex gap-2 mb-3">
                <a href="jobs.php" class="btn btn-sm <?= !$status_filter ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
                <a href="?status=active" class="btn btn-sm <?= $status_filter === 'active' ? 'btn-success' : 'btn-outline-success' ?>">Active</a>
                <a href="?status=closed" class="btn btn-sm <?= $status_filter === 'closed' ? 'btn-danger' : 'btn-outline-danger' ?>">Closed</a>
                <a href="?status=draft" class="btn btn-sm <?= $status_filter === 'draft' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Draft</a>
            </div>

            <div class="mb-3">
                <input type="text" id="tableSearch" class="form-control" placeholder="Search jobs..." style="max-width:300px;">
            </div>

            <div class="table-card">
                <div class="table-responsive">
                    <table class="table searchable-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Job Title</th>
                                <th>Company</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Applications</th>
                                <th>Posted</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($job = $jobs->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $job['id'] ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($job['title']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($job['category'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($job['company_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($job['employer_email']) ?></small>
                                    </td>
                                    <td><span class="badge-job-type badge-<?= str_replace('-', '', $job['job_type']) ?>"><?= ucfirst($job['job_type']) ?></span></td>
                                    <td><small><?= htmlspecialchars($job['location']) ?></small></td>
                                    <td><span class="badge bg-primary"><?= $job['app_count'] ?></span></td>
                                    <td><small class="text-muted"><?= date('M d, Y', strtotime($job['created_at'])) ?></small></td>
                                    <td><span class="status-badge status-<?= $job['status'] ?>"><?= ucfirst($job['status']) ?></span></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="?toggle=<?= $job['id'] ?>" class="btn btn-sm btn-outline-<?= $job['status'] === 'active' ? 'warning' : 'success' ?>"
                                               data-bs-toggle="tooltip" title="<?= $job['status'] === 'active' ? 'Close' : 'Activate' ?>">
                                                <i class="bi bi-<?= $job['status'] === 'active' ? 'pause-circle' : 'play-circle' ?>"></i>
                                            </a>
                                            <a href="?delete=<?= $job['id'] ?>" class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Delete this job?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
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
