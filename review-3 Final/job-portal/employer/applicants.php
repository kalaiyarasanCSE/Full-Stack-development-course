<?php
$page_title = 'View Applicants';
$base_path = '../';
require_once '../config/db.php';
requireRole('employer');

$uid = getUserId();
$user    = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();
$profile = $conn->query("SELECT * FROM employer_profiles WHERE user_id = $uid")->fetch_assoc();

// Update application status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $app_id    = (int)$_POST['app_id'];
    $new_status = sanitize($_POST['new_status']);
    $allowed_statuses = ['pending', 'reviewed', 'shortlisted', 'rejected', 'hired'];

    if (in_array($new_status, $allowed_statuses)) {
        // Verify this application belongs to employer's job
        $check = $conn->query("SELECT a.id, a.student_id, j.title FROM applications a JOIN jobs j ON a.job_id = j.id WHERE a.id = $app_id AND j.employer_id = $uid");
        if ($check->num_rows > 0) {
            $app_data = $check->fetch_assoc();
            $conn->query("UPDATE applications SET status = '$new_status' WHERE id = $app_id");

            // Notify student
            $status_messages = [
                'shortlisted' => "Congratulations! You've been shortlisted for '{$app_data['title']}'.",
                'rejected'    => "Your application for '{$app_data['title']}' was not selected at this time.",
                'hired'       => "Congratulations! You've been hired for '{$app_data['title']}'!",
                'reviewed'    => "Your application for '{$app_data['title']}' is being reviewed.",
            ];
            if (isset($status_messages[$new_status])) {
                addNotification($app_data['student_id'], $status_messages[$new_status]);
            }
        }
    }
    redirect('applicants.php' . (isset($_GET['job_id']) ? '?job_id=' . (int)$_GET['job_id'] : ''));
}

// Filter by job
$job_id_filter = (int)($_GET['job_id'] ?? 0);
$status_filter = sanitize($_GET['status'] ?? '');

$where = "j.employer_id = $uid";
if ($job_id_filter) $where .= " AND a.job_id = $job_id_filter";
if ($status_filter) $where .= " AND a.status = '$status_filter'";

$applications = $conn->query("
    SELECT a.*, j.title as job_title, j.location as job_location, j.job_type,
           u.full_name, u.email, u.phone, u.location as student_location,
           sp.skills, sp.experience, sp.education, sp.linkedin, sp.portfolio
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.student_id = u.id
    LEFT JOIN student_profiles sp ON a.student_id = sp.user_id
    WHERE $where
    ORDER BY a.applied_at DESC
");

// Get employer's jobs for filter dropdown
$employer_jobs = $conn->query("SELECT id, title FROM jobs WHERE employer_id = $uid ORDER BY created_at DESC");

// Status counts
$count_result = $conn->query("
    SELECT a.status, COUNT(*) as c FROM applications a
    JOIN jobs j ON a.job_id = j.id
    WHERE j.employer_id = $uid
    GROUP BY a.status
");
$counts = [];
while ($row = $count_result->fetch_assoc()) $counts[$row['status']] = $row['c'];

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
                <a class="nav-link" href="manage-jobs.php"><i class="bi bi-briefcase"></i> Manage Jobs</a>
                <a class="nav-link active" href="applicants.php"><i class="bi bi-people"></i> All Applicants</a>
                <a class="nav-link" href="profile.php"><i class="bi bi-building"></i> Company Profile</a>
                <hr class="mx-3">
                <a class="nav-link text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1">Applicants</h4>
                    <p class="text-muted mb-0">Review and manage job applications</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <select class="form-select" onchange="window.location.href='applicants.php?job_id='+this.value+'&status=<?= $status_filter ?>'">
                        <option value="">All Jobs</option>
                        <?php while ($j = $employer_jobs->fetch_assoc()): ?>
                            <option value="<?= $j['id'] ?>" <?= $job_id_filter === $j['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($j['title']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" id="tableSearch" class="form-control" placeholder="Search applicants...">
                </div>
            </div>

            <!-- Status Tabs -->
            <div class="d-flex flex-wrap gap-2 mb-4">
                <a href="applicants.php<?= $job_id_filter ? '?job_id='.$job_id_filter : '' ?>" class="btn btn-sm <?= !$status_filter ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    All <span class="badge bg-white text-dark ms-1"><?= array_sum($counts) ?></span>
                </a>
                <?php
                $statuses = ['pending' => 'warning', 'reviewed' => 'info', 'shortlisted' => 'success', 'rejected' => 'danger', 'hired' => 'success'];
                foreach ($statuses as $s => $color):
                    $count = $counts[$s] ?? 0;
                ?>
                    <a href="applicants.php?<?= $job_id_filter ? 'job_id='.$job_id_filter.'&' : '' ?>status=<?= $s ?>"
                       class="btn btn-sm <?= $status_filter === $s ? "btn-$color" : "btn-outline-$color" ?>">
                        <?= ucfirst($s) ?> <span class="badge bg-white text-dark ms-1"><?= $count ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if ($applications->num_rows === 0): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h5>No Applications Found</h5>
                    <p>No applications match your current filters.</p>
                </div>
            <?php else: ?>
                <div class="table-card">
                    <div class="table-responsive">
                        <table class="table searchable-table">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Job Applied</th>
                                    <th>Skills</th>
                                    <th>Resume</th>
                                    <th>Applied</th>
                                    <th>Status</th>
                                    <th>Update Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($app = $applications->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.85rem;flex-shrink:0;">
                                                    <?= strtoupper(substr($app['full_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?= htmlspecialchars($app['full_name']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($app['email']) ?></small>
                                                    <?php if ($app['phone']): ?>
                                                        <br><small class="text-muted"><i class="bi bi-telephone"></i> <?= htmlspecialchars($app['phone']) ?></small>
                                                    <?php endif; ?>
                                                    <?php if ($app['experience']): ?>
                                                        <br><small class="text-muted"><i class="bi bi-briefcase"></i> <?= htmlspecialchars($app['experience']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold small"><?= htmlspecialchars($app['job_title']) ?></div>
                                            <span class="badge-job-type badge-<?= str_replace('-', '', $app['job_type']) ?>" style="font-size:0.7rem;"><?= ucfirst($app['job_type']) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($app['skills']): ?>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <?php foreach (array_slice(explode(',', $app['skills']), 0, 3) as $skill): ?>
                                                        <span style="background:#f1f5f9;color:#475569;padding:2px 8px;border-radius:20px;font-size:0.7rem;"><?= htmlspecialchars(trim($skill)) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <small class="text-muted">Not specified</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($app['resume_file']): ?>
                                                <a href="../uploads/resumes/<?= htmlspecialchars($app['resume_file']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-file-earmark-pdf me-1"></i>View
                                                </a>
                                            <?php else: ?>
                                                <small class="text-muted">No resume</small>
                                            <?php endif; ?>
                                            <?php if ($app['cover_letter']): ?>
                                                <button class="btn btn-sm btn-outline-secondary mt-1" data-bs-toggle="modal" data-bs-target="#coverModal<?= $app['id'] ?>">
                                                    <i class="bi bi-chat-text me-1"></i>Cover Letter
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?= date('M d, Y', strtotime($app['applied_at'])) ?></small><br>
                                            <small class="text-muted"><?= timeAgo($app['applied_at']) ?></small>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= $app['status'] ?>"><?= ucfirst($app['status']) ?></span>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-flex gap-1">
                                                <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                                <select name="new_status" class="form-select form-select-sm" style="width:130px;">
                                                    <?php foreach (['pending', 'reviewed', 'shortlisted', 'rejected', 'hired'] as $s): ?>
                                                        <option value="<?= $s ?>" <?= $app['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" name="update_status" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Update Status">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Cover Letter Modal -->
                                    <?php if ($app['cover_letter']): ?>
                                    <div class="modal fade" id="coverModal<?= $app['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Cover Letter - <?= htmlspecialchars($app['full_name']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p style="white-space:pre-wrap;line-height:1.8;"><?= htmlspecialchars($app['cover_letter']) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
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
