<?php
$page_title = 'My Applications';
$base_path = '../';
require_once '../config/db.php';
requireRole('student');

$uid = getUserId();
$user = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();

// Filter by status
$status_filter = sanitize($_GET['status'] ?? '');
$where = "a.student_id = $uid";
if ($status_filter) $where .= " AND a.status = '$status_filter'";

$applications = $conn->query("
    SELECT a.*, j.title, j.location, j.job_type, j.salary_min, j.salary_max, j.deadline,
           ep.company_name, ep.industry
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN employer_profiles ep ON j.employer_id = ep.user_id
    WHERE $where
    ORDER BY a.applied_at DESC
");

// Status counts
$counts = [];
$count_result = $conn->query("SELECT status, COUNT(*) as c FROM applications WHERE student_id = $uid GROUP BY status");
while ($row = $count_result->fetch_assoc()) {
    $counts[$row['status']] = $row['c'];
}
$total = array_sum($counts);

// Messages
$msg = $_GET['msg'] ?? '';

include '../includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Sidebar -->
        <div class="col-lg-2 sidebar d-none d-lg-block">
            <div class="sidebar-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="user-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
                    <div>
                        <div class="fw-bold" style="font-size:0.9rem;"><?= htmlspecialchars($user['full_name']) ?></div>
                        <small style="opacity:0.8;">Job Seeker</small>
                    </div>
                </div>
            </div>
            <nav class="nav flex-column mt-2">
                <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link" href="profile.php"><i class="bi bi-person-circle"></i> My Profile</a>
                <a class="nav-link" href="browse-jobs.php"><i class="bi bi-search"></i> Browse Jobs</a>
                <a class="nav-link active" href="my-applications.php"><i class="bi bi-file-earmark-text"></i> My Applications</a>
                <a class="nav-link" href="../auth/notifications.php"><i class="bi bi-bell"></i> Notifications</a>
                <hr class="mx-3">
                <a class="nav-link text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1">My Applications</h4>
                    <p class="text-muted mb-0">Track all your job applications</p>
                </div>
                <a href="browse-jobs.php" class="btn btn-primary">
                    <i class="bi bi-plus me-2"></i>Apply to More Jobs
                </a>
            </div>

            <?php if ($msg === 'applied'): ?>
                <div class="alert alert-success alert-auto-dismiss"><i class="bi bi-check-circle-fill me-2"></i>Application submitted successfully! Good luck!</div>
            <?php elseif ($msg === 'already_applied'): ?>
                <div class="alert alert-warning alert-auto-dismiss"><i class="bi bi-exclamation-triangle-fill me-2"></i>You have already applied for this job.</div>
            <?php endif; ?>

            <!-- Status Filter Tabs -->
            <div class="d-flex flex-wrap gap-2 mb-4">
                <a href="my-applications.php" class="btn btn-sm <?= !$status_filter ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    All <span class="badge bg-white text-dark ms-1"><?= $total ?></span>
                </a>
                <?php
                $statuses = ['pending' => 'warning', 'reviewed' => 'info', 'shortlisted' => 'success', 'rejected' => 'danger', 'hired' => 'success'];
                foreach ($statuses as $s => $color):
                    $count = $counts[$s] ?? 0;
                    if ($count > 0 || $status_filter === $s):
                ?>
                    <a href="?status=<?= $s ?>" class="btn btn-sm <?= $status_filter === $s ? "btn-$color" : "btn-outline-$color" ?>">
                        <?= ucfirst($s) ?> <span class="badge bg-white text-dark ms-1"><?= $count ?></span>
                    </a>
                <?php endif; endforeach; ?>
            </div>

            <?php if ($applications->num_rows === 0): ?>
                <div class="empty-state">
                    <i class="bi bi-file-earmark-x"></i>
                    <h5>No Applications Found</h5>
                    <p><?= $status_filter ? "No $status_filter applications." : "You haven't applied to any jobs yet." ?></p>
                    <a href="browse-jobs.php" class="btn btn-primary mt-2">Browse Jobs</a>
                </div>
            <?php else: ?>
                <!-- Search -->
                <div class="mb-3">
                    <input type="text" id="tableSearch" class="form-control" placeholder="Search applications..." style="max-width:300px;">
                </div>

                <div class="table-card">
                    <div class="table-responsive">
                        <table class="table searchable-table">
                            <thead>
                                <tr>
                                    <th>Job Position</th>
                                    <th>Company</th>
                                    <th>Type</th>
                                    <th>Salary</th>
                                    <th>Applied</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($app = $applications->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($app['title']) ?></div>
                                            <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($app['location']) ?></small>
                                        </td>
                                        <td>
                                            <div><?= htmlspecialchars($app['company_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($app['industry'] ?? '') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge-job-type badge-<?= str_replace('-', '', $app['job_type']) ?>"><?= ucfirst($app['job_type']) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($app['salary_min']): ?>
                                                <span class="salary-range small">$<?= number_format($app['salary_min']) ?> - $<?= number_format($app['salary_max']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="small"><?= date('M d, Y', strtotime($app['applied_at'])) ?></div>
                                            <small class="text-muted"><?= timeAgo($app['applied_at']) ?></small>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= $app['status'] ?>"><?= ucfirst($app['status']) ?></span>
                                            <?php if ($app['status'] === 'shortlisted'): ?>
                                                <div class="mt-1"><small class="text-success"><i class="bi bi-star-fill"></i> Congratulations!</small></div>
                                            <?php elseif ($app['status'] === 'hired'): ?>
                                                <div class="mt-1"><small class="text-success"><i class="bi bi-trophy-fill"></i> You got the job!</small></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="browse-jobs.php?view=<?= $app['job_id'] ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="View Job">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($app['resume_file']): ?>
                                                    <a href="../uploads/resumes/<?= htmlspecialchars($app['resume_file']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="View Resume">
                                                        <i class="bi bi-file-earmark-pdf"></i>
                                                    </a>
                                                <?php endif; ?>
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
