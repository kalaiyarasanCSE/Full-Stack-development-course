<?php
$page_title = 'All Applications';
$base_path = '../';
require_once '../config/db.php';
requireLogin();
if (getUserRole() !== 'admin') redirect('../index.php');

$uid = getUserId();
$user = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();

$status_filter = sanitize($_GET['status'] ?? '');
$where = '1=1';
if ($status_filter) $where = "a.status = '$status_filter'";

$applications = $conn->query("
    SELECT a.*, j.title as job_title, j.job_type,
           u.full_name as student_name, u.email as student_email,
           ep.company_name
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.student_id = u.id
    JOIN employer_profiles ep ON j.employer_id = ep.user_id
    WHERE $where
    ORDER BY a.applied_at DESC
");

$count_result = $conn->query("SELECT status, COUNT(*) as c FROM applications GROUP BY status");
$counts = [];
while ($row = $count_result->fetch_assoc()) $counts[$row['status']] = $row['c'];

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
                <a class="nav-link" href="jobs.php"><i class="bi bi-briefcase"></i> Manage Jobs</a>
                <a class="nav-link active" href="applications.php"><i class="bi bi-file-earmark-text"></i> Applications</a>
                <hr class="mx-3">
                <a class="nav-link text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
        </div>

        <div class="col-lg-10 p-4">
            <div class="mb-4">
                <h4 class="fw-bold mb-1">All Applications</h4>
                <p class="text-muted mb-0">Monitor all job applications across the platform</p>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-4">
                <a href="applications.php" class="btn btn-sm <?= !$status_filter ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    All <span class="badge bg-white text-dark ms-1"><?= array_sum($counts) ?></span>
                </a>
                <?php foreach (['pending' => 'warning', 'reviewed' => 'info', 'shortlisted' => 'success', 'rejected' => 'danger', 'hired' => 'success'] as $s => $color): ?>
                    <a href="?status=<?= $s ?>" class="btn btn-sm <?= $status_filter === $s ? "btn-$color" : "btn-outline-$color" ?>">
                        <?= ucfirst($s) ?> <span class="badge bg-white text-dark ms-1"><?= $counts[$s] ?? 0 ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="mb-3">
                <input type="text" id="tableSearch" class="form-control" placeholder="Search applications..." style="max-width:300px;">
            </div>

            <div class="table-card">
                <div class="table-responsive">
                    <table class="table searchable-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Applicant</th>
                                <th>Job</th>
                                <th>Company</th>
                                <th>Applied</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($app = $applications->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $app['id'] ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($app['student_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($app['student_email']) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold small"><?= htmlspecialchars($app['job_title']) ?></div>
                                        <span class="badge-job-type badge-<?= str_replace('-', '', $app['job_type']) ?>" style="font-size:0.7rem;"><?= ucfirst($app['job_type']) ?></span>
                                    </td>
                                    <td><small><?= htmlspecialchars($app['company_name']) ?></small></td>
                                    <td><small class="text-muted"><?= date('M d, Y', strtotime($app['applied_at'])) ?></small></td>
                                    <td><span class="status-badge status-<?= $app['status'] ?>"><?= ucfirst($app['status']) ?></span></td>
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
