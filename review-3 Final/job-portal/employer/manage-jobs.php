<?php
$page_title = 'Manage Jobs';
$base_path = '../';
require_once '../config/db.php';
requireRole('employer');

$uid = getUserId();
$user    = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();
$profile = $conn->query("SELECT * FROM employer_profiles WHERE user_id = $uid")->fetch_assoc();

// Delete job
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM jobs WHERE id = $del_id AND employer_id = $uid");
    redirect('manage-jobs.php?msg=deleted');
}

// Toggle status
if (isset($_GET['toggle'])) {
    $tog_id = (int)$_GET['toggle'];
    $current = $conn->query("SELECT status FROM jobs WHERE id = $tog_id AND employer_id = $uid")->fetch_assoc();
    if ($current) {
        $new_status = $current['status'] === 'active' ? 'closed' : 'active';
        $conn->query("UPDATE jobs SET status = '$new_status' WHERE id = $tog_id AND employer_id = $uid");
    }
    redirect('manage-jobs.php?msg=updated');
}

$status_filter = sanitize($_GET['status'] ?? '');
$where = "j.employer_id = $uid";
if ($status_filter) $where .= " AND j.status = '$status_filter'";

$jobs = $conn->query("
    SELECT j.*, COUNT(a.id) as app_count,
           SUM(CASE WHEN a.status = 'shortlisted' THEN 1 ELSE 0 END) as shortlisted_count
    FROM jobs j
    LEFT JOIN applications a ON j.id = a.job_id
    WHERE $where
    GROUP BY j.id
    ORDER BY j.created_at DESC
");

include '../includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Sidebar -->
        <div class="col-lg-2 sidebar d-none d-lg-block">
            <div class="sidebar-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="user-avatar"><?= strtoupper(substr($profile['company_name'] ?? 'E', 0, 1)) ?></div>
                    <div>
                        <div class="fw-bold" style="font-size:0.9rem;"><?= htmlspecialchars($profile['company_name'] ?? '') ?></div>
                        <small style="opacity:0.8;">Employer</small>
                    </div>
                </div>
            </div>
            <nav class="nav flex-column mt-2">
                <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link" href="post-job.php"><i class="bi bi-plus-circle"></i> Post a Job</a>
                <a class="nav-link active" href="manage-jobs.php"><i class="bi bi-briefcase"></i> Manage Jobs</a>
                <a class="nav-link" href="applicants.php"><i class="bi bi-people"></i> All Applicants</a>
                <a class="nav-link" href="profile.php"><i class="bi bi-building"></i> Company Profile</a>
                <hr class="mx-3">
                <a class="nav-link text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1">Manage Jobs</h4>
                    <p class="text-muted mb-0">View, edit, and manage your job listings</p>
                </div>
                <a href="post-job.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Post New Job
                </a>
            </div>

            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-auto-dismiss">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= $_GET['msg'] === 'deleted' ? 'Job deleted successfully.' : 'Job status updated.' ?>
                </div>
            <?php endif; ?>

            <!-- Filter Tabs -->
            <div class="d-flex gap-2 mb-4">
                <a href="manage-jobs.php" class="btn btn-sm <?= !$status_filter ? 'btn-primary' : 'btn-outline-secondary' ?>">All Jobs</a>
                <a href="?status=active" class="btn btn-sm <?= $status_filter === 'active' ? 'btn-success' : 'btn-outline-success' ?>">Active</a>
                <a href="?status=closed" class="btn btn-sm <?= $status_filter === 'closed' ? 'btn-danger' : 'btn-outline-danger' ?>">Closed</a>
                <a href="?status=draft" class="btn btn-sm <?= $status_filter === 'draft' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Draft</a>
            </div>

            <!-- Search -->
            <div class="mb-3">
                <input type="text" id="tableSearch" class="form-control" placeholder="Search jobs..." style="max-width:300px;">
            </div>

            <?php if ($jobs->num_rows === 0): ?>
                <div class="empty-state">
                    <i class="bi bi-briefcase"></i>
                    <h5>No Jobs Found</h5>
                    <p>You haven't posted any jobs yet.</p>
                    <a href="post-job.php" class="btn btn-primary mt-2">Post Your First Job</a>
                </div>
            <?php else: ?>
                <div class="table-card">
                    <div class="table-responsive">
                        <table class="table searchable-table">
                            <thead>
                                <tr>
                                    <th>Job Title</th>
                                    <th>Type</th>
                                    <th>Location</th>
                                    <th>Applications</th>
                                    <th>Deadline</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($job = $jobs->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($job['title']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($job['category'] ?? '') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge-job-type badge-<?= str_replace('-', '', $job['job_type']) ?>"><?= ucfirst($job['job_type']) ?></span>
                                        </td>
                                        <td><small><?= htmlspecialchars($job['location']) ?></small></td>
                                        <td>
                                            <a href="applicants.php?job_id=<?= $job['id'] ?>" class="text-decoration-none">
                                                <span class="fw-bold text-primary"><?= $job['app_count'] ?></span>
                                                <small class="text-muted"> total</small>
                                            </a>
                                            <?php if ($job['shortlisted_count'] > 0): ?>
                                                <br><small class="text-success"><i class="bi bi-star-fill"></i> <?= $job['shortlisted_count'] ?> shortlisted</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($job['deadline']): ?>
                                                <small class="<?= strtotime($job['deadline']) < time() ? 'text-danger' : 'text-muted' ?>">
                                                    <?= date('M d, Y', strtotime($job['deadline'])) ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">No deadline</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= $job['status'] ?>"><?= ucfirst($job['status']) ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="applicants.php?job_id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="View Applicants">
                                                    <i class="bi bi-people"></i>
                                                </a>
                                                <a href="post-job.php?edit=<?= $job['id'] ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="?toggle=<?= $job['id'] ?>" class="btn btn-sm btn-outline-<?= $job['status'] === 'active' ? 'warning' : 'success' ?>"
                                                   data-bs-toggle="tooltip" title="<?= $job['status'] === 'active' ? 'Close Job' : 'Activate Job' ?>">
                                                    <i class="bi bi-<?= $job['status'] === 'active' ? 'pause-circle' : 'play-circle' ?>"></i>
                                                </a>
                                                <a href="?delete=<?= $job['id'] ?>" class="btn btn-sm btn-outline-danger"
                                                   data-bs-toggle="tooltip" title="Delete"
                                                   onclick="return confirm('Delete this job? All applications will also be deleted.')">
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
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
