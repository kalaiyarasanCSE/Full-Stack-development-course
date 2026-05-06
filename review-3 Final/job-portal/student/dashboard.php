<?php
$page_title = 'Student Dashboard';
$base_path = '../';
require_once '../config/db.php';
requireRole('student');

$uid = getUserId();

// Stats
$total_apps   = $conn->query("SELECT COUNT(*) as c FROM applications WHERE student_id = $uid")->fetch_assoc()['c'];
$shortlisted  = $conn->query("SELECT COUNT(*) as c FROM applications WHERE student_id = $uid AND status = 'shortlisted'")->fetch_assoc()['c'];
$pending      = $conn->query("SELECT COUNT(*) as c FROM applications WHERE student_id = $uid AND status = 'pending'")->fetch_assoc()['c'];
$hired        = $conn->query("SELECT COUNT(*) as c FROM applications WHERE student_id = $uid AND status = 'hired'")->fetch_assoc()['c'];

// Recent applications
$recent_apps = $conn->query("
    SELECT a.*, j.title, j.location, j.job_type, ep.company_name
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN employer_profiles ep ON j.employer_id = ep.user_id
    WHERE a.student_id = $uid
    ORDER BY a.applied_at DESC LIMIT 5
");

// Profile
$profile = $conn->query("SELECT * FROM student_profiles WHERE user_id = $uid")->fetch_assoc();
$user    = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();

// Profile completion
$fields = [$user['phone'], $user['location'], $profile['bio'], $profile['skills'], $profile['education'], $profile['resume_file']];
$filled = count(array_filter($fields));
$completion = round(($filled / count($fields)) * 100);

// Recommended jobs
$skills_arr = array_map('trim', explode(',', $profile['skills'] ?? ''));
$skill_conditions = array_map(fn($s) => "j.skills_required LIKE '%" . $conn->real_escape_string($s) . "%'", array_filter($skills_arr));
$skill_where = $skill_conditions ? '(' . implode(' OR ', $skill_conditions) . ')' : '1=1';

$recommended = $conn->query("
    SELECT j.*, ep.company_name
    FROM jobs j
    JOIN employer_profiles ep ON j.employer_id = ep.user_id
    WHERE j.status = 'active' AND $skill_where
    AND j.id NOT IN (SELECT job_id FROM applications WHERE student_id = $uid)
    ORDER BY j.created_at DESC LIMIT 4
");

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
                <a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link" href="profile.php"><i class="bi bi-person-circle"></i> My Profile</a>
                <a class="nav-link" href="browse-jobs.php"><i class="bi bi-search"></i> Browse Jobs</a>
                <a class="nav-link" href="my-applications.php"><i class="bi bi-file-earmark-text"></i> My Applications</a>
                <a class="nav-link" href="recommended.php"><i class="bi bi-stars"></i> Recommended Jobs</a>
                <a class="nav-link" href="../auth/notifications.php"><i class="bi bi-bell"></i> Notifications</a>
                <hr class="mx-3">
                <a class="nav-link text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-lg-10 p-4">
            <!-- Welcome -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1">Welcome back, <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?>! 👋</h4>
                    <p class="text-muted mb-0">Here's your job search overview</p>
                </div>
                <a href="browse-jobs.php" class="btn btn-primary">
                    <i class="bi bi-search me-2"></i>Find Jobs
                </a>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="dash-stat-card bg-gradient-blue">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2><?= $total_apps ?></h2>
                                <p>Total Applied</p>
                            </div>
                            <i class="bi bi-send"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="dash-stat-card bg-gradient-orange">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2><?= $pending ?></h2>
                                <p>Pending</p>
                            </div>
                            <i class="bi bi-clock"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="dash-stat-card bg-gradient-purple">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2><?= $shortlisted ?></h2>
                                <p>Shortlisted</p>
                            </div>
                            <i class="bi bi-star"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="dash-stat-card bg-gradient-green">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2><?= $hired ?></h2>
                                <p>Hired</p>
                            </div>
                            <i class="bi bi-trophy"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Recent Applications -->
                <div class="col-lg-8">
                    <div class="dash-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Recent Applications</h6>
                            <a href="my-applications.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <?php if ($recent_apps->num_rows === 0): ?>
                            <div class="empty-state py-4">
                                <i class="bi bi-file-earmark-x fs-1 d-block mb-2"></i>
                                <p>No applications yet. <a href="browse-jobs.php">Browse jobs</a> to get started!</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Job</th>
                                            <th>Company</th>
                                            <th>Status</th>
                                            <th>Applied</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($app = $recent_apps->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold"><?= htmlspecialchars($app['title']) ?></div>
                                                    <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($app['location']) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($app['company_name']) ?></td>
                                                <td><span class="status-badge status-<?= $app['status'] ?>"><?= ucfirst($app['status']) ?></span></td>
                                                <td><small class="text-muted"><?= timeAgo($app['applied_at']) ?></small></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Profile Completion -->
                <div class="col-lg-4">
                    <div class="dash-card mb-3">
                        <h6 class="fw-bold mb-3">Profile Completion</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="small text-muted">Progress</span>
                            <span class="fw-bold text-primary"><?= $completion ?>%</span>
                        </div>
                        <div class="profile-completion mb-3">
                            <div class="fill" style="width:<?= $completion ?>%"></div>
                        </div>
                        <ul class="list-unstyled small">
                            <li class="mb-1 <?= $user['phone'] ? 'text-success' : 'text-muted' ?>">
                                <i class="bi bi-<?= $user['phone'] ? 'check-circle-fill' : 'circle' ?> me-2"></i>Phone Number
                            </li>
                            <li class="mb-1 <?= $user['location'] ? 'text-success' : 'text-muted' ?>">
                                <i class="bi bi-<?= $user['location'] ? 'check-circle-fill' : 'circle' ?> me-2"></i>Location
                            </li>
                            <li class="mb-1 <?= $profile['bio'] ? 'text-success' : 'text-muted' ?>">
                                <i class="bi bi-<?= $profile['bio'] ? 'check-circle-fill' : 'circle' ?> me-2"></i>Bio
                            </li>
                            <li class="mb-1 <?= $profile['skills'] ? 'text-success' : 'text-muted' ?>">
                                <i class="bi bi-<?= $profile['skills'] ? 'check-circle-fill' : 'circle' ?> me-2"></i>Skills
                            </li>
                            <li class="mb-1 <?= $profile['education'] ? 'text-success' : 'text-muted' ?>">
                                <i class="bi bi-<?= $profile['education'] ? 'check-circle-fill' : 'circle' ?> me-2"></i>Education
                            </li>
                            <li class="mb-1 <?= $profile['resume_file'] ? 'text-success' : 'text-muted' ?>">
                                <i class="bi bi-<?= $profile['resume_file'] ? 'check-circle-fill' : 'circle' ?> me-2"></i>Resume Uploaded
                            </li>
                        </ul>
                        <?php if ($completion < 100): ?>
                            <a href="profile.php" class="btn btn-sm btn-primary w-100 mt-2">Complete Profile</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recommended Jobs -->
            <?php if ($recommended->num_rows > 0): ?>
            <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Recommended for You</h6>
                    <a href="browse-jobs.php" class="btn btn-sm btn-outline-primary">See All Jobs</a>
                </div>
                <div class="row g-3">
                    <?php while ($job = $recommended->fetch_assoc()): ?>
                        <div class="col-md-6">
                            <div class="job-card">
                                <div class="d-flex gap-3 align-items-start">
                                    <div class="company-logo"><?= strtoupper(substr($job['company_name'], 0, 1)) ?></div>
                                    <div class="flex-grow-1">
                                        <a href="browse-jobs.php?view=<?= $job['id'] ?>" class="job-title d-block"><?= htmlspecialchars($job['title']) ?></a>
                                        <div class="company-name"><?= htmlspecialchars($job['company_name']) ?></div>
                                        <div class="job-meta mt-2">
                                            <span class="me-3"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($job['location']) ?></span>
                                            <span class="badge-job-type badge-<?= str_replace('-', '', $job['job_type']) ?>"><?= ucfirst($job['job_type']) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 d-flex justify-content-between align-items-center">
                                    <?php if ($job['salary_min']): ?>
                                        <span class="salary-range">$<?= number_format($job['salary_min']) ?> - $<?= number_format($job['salary_max']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">Salary not disclosed</span>
                                    <?php endif; ?>
                                    <a href="apply.php?job_id=<?= $job['id'] ?>" class="btn btn-sm btn-primary">Apply Now</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
