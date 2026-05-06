<?php
$page_title = 'Browse Jobs';
$base = '../';
require_once '../config/db.php';
// Allow guests to browse, but require login to apply
if (isLoggedIn() && getRole() !== 'jobseeker') {
    redirect('../' . getRole() . '/dashboard.php');
}
$uid = isLoggedIn() ? getUserId() : 0;

// Single job view
$view_job = null;
if (isset($_GET['view'])) {
    $jid = (int)$_GET['view'];
    $view_job = $conn->query("
        SELECT j.*, u.full_name as hr_name, u.email as hr_email,
               COUNT(a.id) as app_count
        FROM jobs j JOIN users u ON j.hr_id=u.id
        LEFT JOIN applications a ON j.id=a.job_id
        WHERE j.id=$jid AND j.status='active'
        GROUP BY j.id
    ")->fetch_assoc();
}

// Filters
$keyword  = sanitize($_GET['keyword'] ?? '');
$category = sanitize($_GET['category'] ?? '');
$location = sanitize($_GET['location'] ?? '');
$job_type = sanitize($_GET['job_type'] ?? '');

$where = ["j.status='active'"];
if ($keyword)  $where[] = "(j.title LIKE '%$keyword%' OR j.description LIKE '%$keyword%' OR j.requirements LIKE '%$keyword%')";
if ($category) $where[] = "j.category='$category'";
if ($location) $where[] = "j.location='$location'";
if ($job_type) $where[] = "j.job_type='$job_type'";
$where_sql = implode(' AND ', $where);

$jobs = $conn->query("
    SELECT j.*, COUNT(a.id) as app_count
    FROM jobs j LEFT JOIN applications a ON j.id=a.job_id
    WHERE $where_sql GROUP BY j.id ORDER BY j.created_at DESC
");

// Applied jobs
$applied = [];
$ar = $conn->query("SELECT job_id FROM applications WHERE user_id=$uid");
while ($r = $ar->fetch_assoc()) $applied[] = $r['job_id'];

$categories = $conn->query("SELECT DISTINCT category FROM jobs WHERE status='active' AND category IS NOT NULL ORDER BY category");
$locations  = $conn->query("SELECT DISTINCT location FROM jobs WHERE status='active' AND location IS NOT NULL ORDER BY location");

include '../includes/head.php';
?>
<div class="layout">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div><div class="topbar-title">Browse Jobs</div><div class="topbar-sub">Find your perfect opportunity</div></div>
    </div>
    <div class="page-body">

    <?php if ($view_job): ?>
        <!-- Single Job Detail -->
        <div style="margin-bottom:16px;">
            <a href="browse.php" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Back to Jobs</a>
        </div>
        <div class="grid-2" style="align-items:start;">
            <div class="card">
                <div class="card-body">
                    <div style="display:flex;gap:16px;align-items:flex-start;margin-bottom:20px;">
                        <div class="job-logo" style="width:64px;height:64px;font-size:1.6rem;border-radius:14px;">
                            <?= strtoupper(substr($view_job['company'],0,1)) ?>
                        </div>
                        <div>
                            <h3 style="font-weight:800;margin:0 0 4px;"><?= htmlspecialchars($view_job['title']) ?></h3>
                            <div class="job-company" style="font-size:1rem;"><?= htmlspecialchars($view_job['company']) ?></div>
                            <div class="job-meta" style="margin-top:8px;">
                                <span><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($view_job['location'] ?? 'N/A') ?></span>
                                <span class="badge badge-<?= str_replace('-','',$view_job['job_type']) ?>"><?= ucfirst($view_job['job_type']) ?></span>
                                <span><i class="bi bi-people"></i> <?= $view_job['app_count'] ?> applicants</span>
                            </div>
                        </div>
                    </div>

                    <?php if ($view_job['salary_min']): ?>
                    <div style="background:#f0fdf4;border-radius:10px;padding:14px;margin-bottom:20px;">
                        <span class="salary">₹<?= number_format($view_job['salary_min']) ?> – ₹<?= number_format($view_job['salary_max']) ?> / year</span>
                    </div>
                    <?php endif; ?>

                    <h6 style="font-weight:700;margin-bottom:8px;">Job Description</h6>
                    <p style="color:#475569;line-height:1.8;"><?= nl2br(htmlspecialchars($view_job['description'])) ?></p>

                    <?php if ($view_job['requirements']): ?>
                    <h6 style="font-weight:700;margin:16px 0 8px;">Requirements</h6>
                    <p style="color:#475569;line-height:1.8;"><?= nl2br(htmlspecialchars($view_job['requirements'])) ?></p>
                    <?php endif; ?>

                    <?php if ($view_job['deadline']): ?>
                    <div style="margin-top:16px;padding:12px;background:#fffbeb;border-radius:8px;border:1px solid #fde68a;">
                        <i class="bi bi-calendar-event" style="color:#d97706;"></i>
                        <strong style="color:#92400e;"> Deadline: <?= date('d M Y', strtotime($view_job['deadline'])) ?></strong>
                    </div>
                    <?php endif; ?>

                    <div style="margin-top:20px;">
                        <?php if (in_array($view_job['id'], $applied)): ?>
                            <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> You have already applied for this job.</div>
                        <?php else: ?>
                            <a href="apply.php?job_id=<?= $view_job['id'] ?>" class="btn btn-primary btn-lg">
                                <i class="bi bi-send"></i> Apply Now
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5>Job Info</h5></div>
                <div class="card-body">
                    <table style="width:100%;font-size:0.88rem;">
                        <tr><td style="padding:8px 0;color:#94a3b8;width:40%;">Company</td><td class="fw-semibold"><?= htmlspecialchars($view_job['company']) ?></td></tr>
                        <tr><td style="padding:8px 0;color:#94a3b8;">Location</td><td><?= htmlspecialchars($view_job['location'] ?? 'N/A') ?></td></tr>
                        <tr><td style="padding:8px 0;color:#94a3b8;">Job Type</td><td><span class="badge badge-<?= str_replace('-','',$view_job['job_type']) ?>"><?= ucfirst($view_job['job_type']) ?></span></td></tr>
                        <tr><td style="padding:8px 0;color:#94a3b8;">Category</td><td><?= htmlspecialchars($view_job['category'] ?? 'General') ?></td></tr>
                        <?php if ($view_job['salary_min']): ?>
                        <tr><td style="padding:8px 0;color:#94a3b8;">Salary</td><td class="salary">₹<?= number_format($view_job['salary_min']) ?>–₹<?= number_format($view_job['salary_max']) ?></td></tr>
                        <?php endif; ?>
                        <tr><td style="padding:8px 0;color:#94a3b8;">Posted</td><td><?= timeAgo($view_job['created_at']) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Job Listings -->
        <form method="GET" class="search-bar">
            <div class="form-group">
                <label class="form-label">Keyword</label>
                <input type="text" name="keyword" class="form-control" placeholder="Job title or skill..."
                       value="<?= htmlspecialchars($keyword) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php while ($c = $categories->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($c['category']) ?>" <?= $category===$c['category'] ? 'selected':'' ?>>
                            <?= htmlspecialchars($c['category']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Location</label>
                <select name="location" class="form-select">
                    <option value="">All Locations</option>
                    <?php while ($l = $locations->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($l['location']) ?>" <?= $location===$l['location'] ? 'selected':'' ?>>
                            <?= htmlspecialchars($l['location']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Job Type</label>
                <select name="job_type" class="form-select">
                    <option value="">All Types</option>
                    <?php foreach (['full-time'=>'Full Time','part-time'=>'Part Time','internship'=>'Internship','remote'=>'Remote','contract'=>'Contract'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= $job_type===$v ? 'selected':'' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
                <a href="browse.php" class="btn btn-outline" style="margin-left:8px;">Clear</a>
            </div>
        </form>

        <div style="margin-bottom:16px;font-weight:600;color:#64748b;">
            <?= $jobs->num_rows ?> job<?= $jobs->num_rows != 1 ? 's' : '' ?> found
        </div>

        <?php if ($jobs->num_rows === 0): ?>
            <div class="empty-state">
                <i class="bi bi-search"></i>
                <h5>No jobs found</h5>
                <p>Try adjusting your search filters</p>
                <a href="browse.php" class="btn btn-primary" style="margin-top:12px;">Clear Filters</a>
            </div>
        <?php else: ?>
            <?php while ($j = $jobs->fetch_assoc()): ?>
            <div class="job-card mb-3">
                <div style="display:flex;gap:16px;align-items:flex-start;">
                    <div class="job-logo"><?= strtoupper(substr($j['company'],0,1)) ?></div>
                    <div style="flex:1;">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">
                            <div>
                                <a href="browse.php?view=<?= $j['id'] ?>" class="job-title"><?= htmlspecialchars($j['title']) ?></a>
                                <div class="job-company"><?= htmlspecialchars($j['company']) ?></div>
                            </div>
                            <span class="badge badge-<?= str_replace('-','',$j['job_type']) ?>"><?= ucfirst($j['job_type']) ?></span>
                        </div>
                        <div class="job-meta">
                            <span><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($j['location'] ?? 'N/A') ?></span>
                            <span><i class="bi bi-people"></i> <?= $j['app_count'] ?> applicants</span>
                            <span><i class="bi bi-clock"></i> <?= timeAgo($j['created_at']) ?></span>
                            <?php if ($j['salary_min']): ?>
                                <span class="salary"><i class="bi bi-currency-rupee"></i> <?= number_format($j['salary_min']) ?>–<?= number_format($j['salary_max']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;flex-shrink:0;">
                        <a href="browse.php?view=<?= $j['id'] ?>" class="btn btn-outline btn-sm">Details</a>
                        <?php if (in_array($j['id'], $applied)): ?>
                            <span class="btn btn-success btn-sm"><i class="bi bi-check"></i> Applied</span>
                        <?php else: ?>
                            <a href="apply.php?job_id=<?= $j['id'] ?>" class="btn btn-primary btn-sm">Apply</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    <?php endif; ?>

    </div>
</div>
</div>
</body></html>
