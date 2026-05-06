<?php
$page_title = 'Employer Dashboard';
$base_path = '../';
require_once '../config/db.php';
requireRole('employer');

$uid = getUserId();
$user    = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();
$profile = $conn->query("SELECT * FROM employer_profiles WHERE user_id = $uid")->fetch_assoc();

// Stats
$total_jobs   = $conn->query("SELECT COUNT(*) as c FROM jobs WHERE employer_id = $uid")->fetch_assoc()['c'];
$active_jobs  = $conn->query("SELECT COUNT(*) as c FROM jobs WHERE employer_id = $uid AND status = 'active'")->fetch_assoc()['c'];
$total_apps   = $conn->query("SELECT COUNT(*) as c FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = $uid")->fetch_assoc()['c'];
$shortlisted  = $conn->query("SELECT COUNT(*) as c FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = $uid AND a.status = 'shortlisted'")->fetch_assoc()['c'];

// Recent applications
$recent_apps = $conn->query("
    SELECT a.*, j.title as job_title, u.full_name, u.email, sp.skills, sp.experience
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.student_id = u.id
    LEFT JOIN student_profiles sp ON a.student_id = sp.user_id
    WHERE j.employer_id = $uid
    ORDER BY a.applied_at DESC LIMIT 8
");

// Jobs with application counts
$jobs_with_counts = $conn->query("
    SELECT j.*, COUNT(a.id) as app_count
    FROM jobs j
    LEFT JOIN applications a ON j.id = a.job_id
    WHERE j.employer_id = $uid
    GROUP BY j.id
    ORDER BY j.created_at DESC LIMIT 5
");

// Chart data - applications per job
$chart_data = $conn->query("
    SELECT j.title, COUNT(a.id) as count
    FROM jobs j
    LEFT JOIN applications a ON j.id = a.job_id
    WHERE j.employer_id = $uid
    GROUP BY j.id
    ORDER BY count DESC LIMIT 6
");
$chart_labels = [];
$chart_values = [];
while ($row = $chart_data->fetch_assoc()) {
    $chart_labels[] = strlen($row['title']) > 20 ? substr($row['title'], 0, 20) . '...' : $row['title'];
    $chart_values[] = (int)$row['count'];
}

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
                        <div class="fw-bold" style="font-size:0.9rem;"><?= htmlspecialchars($profile['company_name'] ?? $user['full_name']) ?></div>
                        <small style="opacity:0.8;">Employer</small>
                    </div>
                </div>
            </div>
            <nav class="nav flex-column mt-2">
                <a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link" href="post-job.php"><i class="bi bi-plus-circle"></i> Post a Job</a>
                <a class="nav-link" href="manage-jobs.php"><i class="bi bi-briefcase"></i> Manage Jobs</a>
                <a class="nav-link" href="applicants.php"><i class="bi bi-people"></i> All Applicants</a>
                <a class="nav-link" href="profile.php"><i class="bi bi-building"></i> Company Profile</a>
                <a class="nav-link" href="../auth/notifications.php"><i class="bi bi-bell"></i> Notifications</a>
                <hr class="mx-3">
                <a class="nav-link text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1">Welcome, <?= htmlspecialchars($profile['company_name'] ?? $user['full_name']) ?>! 👋</h4>
                    <p class="text-muted mb-0">Here's your hiring overview</p>
                </div>
                <a href="post-job.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Post New Job
                </a>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="dash-stat-card bg-gradient-blue">
                        <div class="d-flex justify-content-between align-items-start">
                            <div><h2><?= $total_jobs ?></h2><p>Total Jobs</p></div>
                            <i class="bi bi-briefcase"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="dash-stat-card bg-gradient-green">
                        <div class="d-flex justify-content-between align-items-start">
                            <div><h2><?= $active_jobs ?></h2><p>Active Jobs</p></div>
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="dash-stat-card bg-gradient-purple">
                        <div class="d-flex justify-content-between align-items-start">
                            <div><h2><?= $total_apps ?></h2><p>Total Applications</p></div>
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="dash-stat-card bg-gradient-orange">
                        <div class="d-flex justify-content-between align-items-start">
                            <div><h2><?= $shortlisted ?></h2><p>Shortlisted</p></div>
                            <i class="bi bi-star"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Recent Applications -->
                <div class="col-lg-7">
                    <div class="dash-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Recent Applications</h6>
                            <a href="applicants.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <?php if ($recent_apps->num_rows === 0): ?>
                            <div class="empty-state py-4">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                <p>No applications yet. <a href="post-job.php">Post a job</a> to start receiving applications.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Applicant</th>
                                            <th>Job</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($app = $recent_apps->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold"><?= htmlspecialchars($app['full_name']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($app['experience'] ?? 'N/A') ?></small>
                                                </td>
                                                <td><small><?= htmlspecialchars($app['job_title']) ?></small></td>
                                                <td><span class="status-badge status-<?= $app['status'] ?>"><?= ucfirst($app['status']) ?></span></td>
                                                <td>
                                                    <a href="applicants.php?job_id=<?= $app['job_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Chart + Jobs -->
                <div class="col-lg-5">
                    <?php if (!empty($chart_labels)): ?>
                    <div class="dash-card mb-4">
                        <h6 class="fw-bold mb-3">Applications per Job</h6>
                        <canvas id="applicationsChart" height="200"
                                data-labels='<?= json_encode($chart_labels) ?>'
                                data-values='<?= json_encode($chart_values) ?>'></canvas>
                    </div>
                    <?php endif; ?>

                    <div class="dash-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Your Jobs</h6>
                            <a href="manage-jobs.php" class="btn btn-sm btn-outline-primary">Manage</a>
                        </div>
                        <?php if ($jobs_with_counts->num_rows === 0): ?>
                            <p class="text-muted small text-center py-3">No jobs posted yet.</p>
                        <?php else: ?>
                            <?php while ($job = $jobs_with_counts->fetch_assoc()): ?>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <div class="fw-semibold small"><?= htmlspecialchars($job['title']) ?></div>
                                        <small class="text-muted"><?= $job['app_count'] ?> applicants</small>
                                    </div>
                                    <span class="status-badge status-<?= $job['status'] ?>"><?= ucfirst($job['status']) ?></span>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
