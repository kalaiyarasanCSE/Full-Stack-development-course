<?php
$page_title = 'Admin Dashboard';
$base_path = '../';
require_once '../config/db.php';
requireLogin();
if (getUserRole() !== 'admin') redirect('../index.php');

$uid = getUserId();
$user = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();

// Stats
$total_users     = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$total_students  = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student'")->fetch_assoc()['c'];
$total_employers = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='employer'")->fetch_assoc()['c'];
$total_jobs      = $conn->query("SELECT COUNT(*) as c FROM jobs")->fetch_assoc()['c'];
$active_jobs     = $conn->query("SELECT COUNT(*) as c FROM jobs WHERE status='active'")->fetch_assoc()['c'];
$total_apps      = $conn->query("SELECT COUNT(*) as c FROM applications")->fetch_assoc()['c'];

// Recent users
$recent_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 8");

// Recent jobs
$recent_jobs = $conn->query("
    SELECT j.*, ep.company_name, COUNT(a.id) as app_count
    FROM jobs j
    JOIN employer_profiles ep ON j.employer_id = ep.user_id
    LEFT JOIN applications a ON j.id = a.job_id
    GROUP BY j.id
    ORDER BY j.created_at DESC LIMIT 5
");

include '../includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Sidebar -->
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
                <a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link" href="users.php"><i class="bi bi-people"></i> Manage Users</a>
                <a class="nav-link" href="jobs.php"><i class="bi bi-briefcase"></i> Manage Jobs</a>
                <a class="nav-link" href="applications.php"><i class="bi bi-file-earmark-text"></i> Applications</a>
                <hr class="mx-3">
                <a class="nav-link text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-lg-10 p-4">
            <div class="mb-4">
                <h4 class="fw-bold mb-1">Admin Dashboard</h4>
                <p class="text-muted mb-0">System overview and management</p>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-2">
                    <div class="dash-stat-card bg-gradient-blue text-center">
                        <h2><?= $total_users ?></h2>
                        <p>Total Users</p>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="dash-stat-card bg-gradient-green text-center">
                        <h2><?= $total_students ?></h2>
                        <p>Students</p>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="dash-stat-card bg-gradient-purple text-center">
                        <h2><?= $total_employers ?></h2>
                        <p>Employers</p>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="dash-stat-card bg-gradient-orange text-center">
                        <h2><?= $total_jobs ?></h2>
                        <p>Total Jobs</p>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="dash-stat-card bg-gradient-green text-center">
                        <h2><?= $active_jobs ?></h2>
                        <p>Active Jobs</p>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="dash-stat-card bg-gradient-blue text-center">
                        <h2><?= $total_apps ?></h2>
                        <p>Applications</p>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Recent Users -->
                <div class="col-lg-6">
                    <div class="dash-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Recent Users</h6>
                            <a href="users.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($u = $recent_users->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?= htmlspecialchars($u['full_name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($u['email']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : ($u['role'] === 'employer' ? 'bg-primary' : 'bg-success') ?>">
                                                    <?= ucfirst($u['role']) ?>
                                                </span>
                                            </td>
                                            <td><small class="text-muted"><?= timeAgo($u['created_at']) ?></small></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Jobs -->
                <div class="col-lg-6">
                    <div class="dash-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Recent Jobs</h6>
                            <a href="jobs.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Job</th>
                                        <th>Company</th>
                                        <th>Apps</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($job = $recent_jobs->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold small"><?= htmlspecialchars($job['title']) ?></div>
                                                <small class="text-muted"><?= timeAgo($job['created_at']) ?></small>
                                            </td>
                                            <td><small><?= htmlspecialchars($job['company_name']) ?></small></td>
                                            <td><span class="badge bg-primary"><?= $job['app_count'] ?></span></td>
                                            <td><span class="status-badge status-<?= $job['status'] ?>"><?= ucfirst($job['status']) ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
