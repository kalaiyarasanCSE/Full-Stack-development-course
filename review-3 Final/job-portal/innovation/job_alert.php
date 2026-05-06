<?php
// ============================================
// Innovation Module: Job Alert System
// Students subscribe to job alerts by skill/location
// ============================================
$page_title = 'Job Alerts';
$base_path  = '../';
require_once '../config/db.php';
requireLogin();

$uid     = getUserId();
$success = '';
$error   = '';

// Create job_alerts table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS job_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    keyword VARCHAR(100),
    location VARCHAR(100),
    job_type VARCHAR(50),
    email_notify TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Save alert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_alert'])) {
    $keyword  = sanitize($_POST['keyword'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    $job_type = sanitize($_POST['job_type'] ?? '');

    if (empty($keyword) && empty($location)) {
        $error = 'Please enter at least a keyword or location.';
    } else {
        $conn->query("INSERT INTO job_alerts (user_id, keyword, location, job_type) VALUES ($uid, '$keyword', '$location', '$job_type')");
        $success = 'Job alert created! You will be notified when matching jobs are posted.';
    }
}

// Delete alert
if (isset($_GET['delete'])) {
    $aid = (int)$_GET['delete'];
    $conn->query("DELETE FROM job_alerts WHERE id = $aid AND user_id = $uid");
    header('Location: job_alert.php');
    exit();
}

// Get user's alerts
$alerts = $conn->query("SELECT * FROM job_alerts WHERE user_id = $uid ORDER BY created_at DESC");

// Get matching jobs for each alert
$locations = $conn->query("SELECT DISTINCT location FROM jobs WHERE status='active' ORDER BY location");

include '../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-bell-fill me-2"></i>Job Alerts</h1>
        <p>Get notified when new jobs match your preferences</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4">
        <!-- Create Alert Form -->
        <div class="col-lg-4">
            <div class="dash-card">
                <h6 class="fw-bold mb-3">🔔 Create New Alert</h6>

                <?php if ($success): ?>
                    <div class="alert alert-success small"><?= $success ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger small"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Keyword / Job Title</label>
                        <input type="text" name="keyword" class="form-control form-control-sm"
                               placeholder="e.g. PHP Developer, React">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Location</label>
                        <select name="location" class="form-select form-select-sm">
                            <option value="">Any Location</option>
                            <?php while ($loc = $locations->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($loc['location']) ?>"><?= htmlspecialchars($loc['location']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Job Type</label>
                        <select name="job_type" class="form-select form-select-sm">
                            <option value="">Any Type</option>
                            <option value="full-time">Full Time</option>
                            <option value="part-time">Part Time</option>
                            <option value="internship">Internship</option>
                            <option value="remote">Remote</option>
                            <option value="contract">Contract</option>
                        </select>
                    </div>
                    <button type="submit" name="save_alert" class="btn btn-primary w-100">
                        <i class="bi bi-bell me-2"></i>Create Alert
                    </button>
                </form>
            </div>
        </div>

        <!-- Active Alerts -->
        <div class="col-lg-8">
            <div class="dash-card">
                <h6 class="fw-bold mb-3">📋 Your Active Alerts</h6>

                <?php if ($alerts->num_rows === 0): ?>
                    <div class="empty-state text-center py-4">
                        <i class="bi bi-bell-slash" style="font-size:2.5rem;color:#cbd5e1;"></i>
                        <p class="mt-2 text-muted">No alerts yet. Create one to get notified!</p>
                    </div>
                <?php else: ?>
                    <?php while ($alert = $alerts->fetch_assoc()):
                        // Find matching jobs
                        $where = ["j.status='active'"];
                        if ($alert['keyword'])  $where[] = "(j.title LIKE '%{$alert['keyword']}%' OR j.skills_required LIKE '%{$alert['keyword']}%')";
                        if ($alert['location']) $where[] = "j.location = '{$alert['location']}'";
                        if ($alert['job_type']) $where[] = "j.job_type = '{$alert['job_type']}'";
                        $where_sql = implode(' AND ', $where);
                        $match_count = $conn->query("SELECT COUNT(*) as c FROM jobs j WHERE $where_sql")->fetch_assoc()['c'];
                    ?>
                    <div class="d-flex justify-content-between align-items-start p-3 mb-3 rounded" style="background:#f8fafc;border:1px solid #e2e8f0;">
                        <div>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <?php if ($alert['keyword']): ?>
                                    <span class="badge bg-primary"><i class="bi bi-search me-1"></i><?= htmlspecialchars($alert['keyword']) ?></span>
                                <?php endif; ?>
                                <?php if ($alert['location']): ?>
                                    <span class="badge bg-success"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($alert['location']) ?></span>
                                <?php endif; ?>
                                <?php if ($alert['job_type']): ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-briefcase me-1"></i><?= ucfirst($alert['job_type']) ?></span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i>Created <?= timeAgo($alert['created_at']) ?>
                            </small>
                            <div class="mt-1">
                                <span class="text-<?= $match_count > 0 ? 'success' : 'muted' ?> small fw-semibold">
                                    <i class="bi bi-briefcase me-1"></i><?= $match_count ?> matching job<?= $match_count != 1 ? 's' : '' ?> now
                                </span>
                                <?php if ($match_count > 0): ?>
                                    <a href="../student/browse-jobs.php?keyword=<?= urlencode($alert['keyword']) ?>&location=<?= urlencode($alert['location']) ?>&job_type=<?= urlencode($alert['job_type']) ?>"
                                       class="btn btn-sm btn-outline-primary ms-2" style="font-size:0.75rem;">View Jobs</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="job_alert.php?delete=<?= $alert['id'] ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Delete this alert?')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
