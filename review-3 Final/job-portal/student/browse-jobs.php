<?php
$page_title = 'Browse Jobs';
$base_path = '../';
require_once '../config/db.php';

// Get filter values from GET
$keyword  = sanitize($_GET['keyword'] ?? '');
$category = sanitize($_GET['category'] ?? '');
$location = sanitize($_GET['location'] ?? '');
$job_type = sanitize($_GET['job_type'] ?? '');
$exp      = sanitize($_GET['experience'] ?? '');

// Build WHERE clause
$where = ["j.status = 'active'"];
if ($keyword)  $where[] = "(j.title LIKE '%$keyword%' OR j.description LIKE '%$keyword%' OR j.skills_required LIKE '%$keyword%')";
if ($category) $where[] = "j.category = '$category'";
if ($location) $where[] = "j.location = '$location'";
if ($job_type) $where[] = "j.job_type = '$job_type'";
if ($exp)      $where[] = "j.experience_required = '$exp'";

$where_sql = implode(' AND ', $where);

$jobs = $conn->query("
    SELECT j.*, ep.company_name,
           (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as app_count
    FROM jobs j
    JOIN employer_profiles ep ON j.employer_id = ep.user_id
    WHERE $where_sql
    ORDER BY j.created_at DESC
");

// Get distinct categories from DB
$categories = $conn->query("SELECT DISTINCT category FROM jobs WHERE status='active' AND category IS NOT NULL ORDER BY category");

// Get distinct locations from DB
$locations = $conn->query("SELECT DISTINCT location FROM jobs WHERE status='active' AND location IS NOT NULL ORDER BY location");

// Check applied jobs for logged-in student
$applied_jobs = [];
if (isLoggedIn() && getUserRole() === 'student') {
    $uid = getUserId();
    $applied_result = $conn->query("SELECT job_id FROM applications WHERE student_id = $uid");
    while ($row = $applied_result->fetch_assoc()) {
        $applied_jobs[] = $row['job_id'];
    }
}

// Single job detail view
$view_job = null;
if (isset($_GET['view'])) {
    $jid = (int)$_GET['view'];
    $view_job = $conn->query("
        SELECT j.*, ep.company_name, ep.company_description, ep.industry, ep.website, ep.company_size,
               u.email as emp_email, u.location as emp_location,
               (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as app_count
        FROM jobs j
        JOIN employer_profiles ep ON j.employer_id = ep.user_id
        JOIN users u ON j.employer_id = u.id
        WHERE j.id = $jid
    ")->fetch_assoc();
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-search me-2"></i>Browse Jobs</h1>
        <p>Find your perfect opportunity from hundreds of listings</p>
        <!-- Top Search Bar -->
        <form method="GET" action="browse-jobs.php" class="mt-3 d-flex gap-2" style="max-width:600px;">
            <input type="text" name="keyword" class="form-control" placeholder="Search by title, skill, or keyword..."
                   value="<?= htmlspecialchars($keyword) ?>">
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-search"></i>
            </button>
        </form>
    </div>
</div>

<div class="container mb-5">
<?php if ($view_job): ?>
    <!-- ===== Single Job Detail View ===== -->
    <div class="mb-3">
        <a href="browse-jobs.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Jobs
        </a>
    </div>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="form-card">
                <div class="d-flex gap-4 align-items-start mb-4">
                    <div class="company-logo" style="width:70px;height:70px;font-size:1.8rem;border-radius:16px;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;flex-shrink:0;">
                        <?= strtoupper(substr($view_job['company_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-1"><?= htmlspecialchars($view_job['title']) ?></h3>
                        <div class="text-muted"><?= htmlspecialchars($view_job['company_name']) ?></div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <span class="badge-job-type badge-<?= str_replace('-','', $view_job['job_type']) ?>"><?= ucfirst($view_job['job_type']) ?></span>
                            <span class="text-muted small"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($view_job['location']) ?></span>
                            <span class="text-muted small"><i class="bi bi-people"></i> <?= $view_job['app_count'] ?> applicants</span>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4 p-3 rounded" style="background:#f8fafc;">
                    <?php if ($view_job['salary_min']): ?>
                    <div class="col-6 col-md-3 text-center">
                        <div class="fw-bold text-success">₹<?= number_format($view_job['salary_min']) ?> - ₹<?= number_format($view_job['salary_max']) ?></div>
                        <small class="text-muted">Salary Range</small>
                    </div>
                    <?php endif; ?>
                    <div class="col-6 col-md-3 text-center">
                        <div class="fw-bold"><?= htmlspecialchars($view_job['experience_required'] ?? 'Any') ?></div>
                        <small class="text-muted">Experience</small>
                    </div>
                    <div class="col-6 col-md-3 text-center">
                        <div class="fw-bold"><?= htmlspecialchars($view_job['category'] ?? 'General') ?></div>
                        <small class="text-muted">Category</small>
                    </div>
                    <?php if ($view_job['deadline']): ?>
                    <div class="col-6 col-md-3 text-center">
                        <div class="fw-bold <?= strtotime($view_job['deadline']) < time() ? 'text-danger' : 'text-warning' ?>">
                            <?= date('M d, Y', strtotime($view_job['deadline'])) ?>
                        </div>
                        <small class="text-muted">Deadline</small>
                    </div>
                    <?php endif; ?>
                </div>

                <h6 class="fw-bold">Job Description</h6>
                <div class="text-muted mb-4" style="line-height:1.8;"><?= nl2br(htmlspecialchars($view_job['description'])) ?></div>

                <?php if ($view_job['skills_required']): ?>
                <h6 class="fw-bold">Required Skills</h6>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <?php foreach (explode(',', $view_job['skills_required']) as $skill): ?>
                        <span class="badge" style="background:#eff6ff;color:#1d4ed8;padding:6px 14px;border-radius:20px;font-size:0.85rem;">
                            <?= htmlspecialchars(trim($skill)) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (isLoggedIn() && getUserRole() === 'student'): ?>
                    <?php if (in_array($view_job['id'], $applied_jobs)): ?>
                        <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>You have already applied for this job.</div>
                    <?php elseif ($view_job['status'] === 'active'): ?>
                        <a href="apply.php?job_id=<?= $view_job['id'] ?>" class="btn btn-primary btn-lg">
                            <i class="bi bi-send me-2"></i>Apply Now
                        </a>
                    <?php else: ?>
                        <div class="alert alert-warning">This job is no longer accepting applications.</div>
                    <?php endif; ?>
                <?php elseif (!isLoggedIn()): ?>
                    <a href="../auth/login.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login to Apply
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Company Info Sidebar -->
        <div class="col-lg-4">
            <div class="form-card">
                <h6 class="fw-bold mb-3">About the Company</h6>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="width:50px;height:50px;border-radius:12px;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:1.2rem;">
                        <?= strtoupper(substr($view_job['company_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="fw-bold"><?= htmlspecialchars($view_job['company_name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($view_job['industry'] ?? '') ?></small>
                    </div>
                </div>
                <?php if ($view_job['company_description']): ?>
                    <p class="text-muted small"><?= htmlspecialchars($view_job['company_description']) ?></p>
                <?php endif; ?>
                <ul class="list-unstyled small">
                    <?php if ($view_job['company_size']): ?>
                    <li class="mb-2"><i class="bi bi-people me-2 text-primary"></i><?= htmlspecialchars($view_job['company_size']) ?> employees</li>
                    <?php endif; ?>
                    <?php if ($view_job['emp_location']): ?>
                    <li class="mb-2"><i class="bi bi-geo-alt me-2 text-primary"></i><?= htmlspecialchars($view_job['emp_location']) ?></li>
                    <?php endif; ?>
                    <?php if ($view_job['website']): ?>
                    <li class="mb-2"><i class="bi bi-globe me-2 text-primary"></i><a href="<?= htmlspecialchars($view_job['website']) ?>" target="_blank">Company Website</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ===== Job Listings with Filters ===== -->
    <form method="GET" action="browse-jobs.php" id="filterForm">
        <div class="row g-4 mt-1">

            <!-- ===== Filter Sidebar ===== -->
            <div class="col-lg-3">
                <div class="filter-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 fw-bold">Filters</h6>
                        <a href="browse-jobs.php" class="small text-primary">Clear All</a>
                    </div>

                    <!-- Category -->
                    <label class="form-label fw-semibold small">Category</label>
                    <select name="category" class="form-select form-select-sm mb-3">
                        <option value="">All Categories</option>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($cat['category']) ?>"
                                <?= $category === $cat['category'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <!-- Job Type -->
                    <label class="form-label fw-semibold small">Job Type</label>
                    <select name="job_type" class="form-select form-select-sm mb-3">
                        <option value="">All Types</option>
                        <option value="full-time"  <?= $job_type==='full-time'  ? 'selected':'' ?>>Full Time</option>
                        <option value="part-time"  <?= $job_type==='part-time'  ? 'selected':'' ?>>Part Time</option>
                        <option value="internship" <?= $job_type==='internship' ? 'selected':'' ?>>Internship</option>
                        <option value="remote"     <?= $job_type==='remote'     ? 'selected':'' ?>>Remote</option>
                        <option value="contract"   <?= $job_type==='contract'   ? 'selected':'' ?>>Contract</option>
                    </select>

                    <!-- Location Dropdown -->
                    <label class="form-label fw-semibold small">Location</label>
                    <select name="location" class="form-select form-select-sm mb-3">
                        <option value="">All Locations</option>
                        <?php while ($loc = $locations->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($loc['location']) ?>"
                                <?= $location === $loc['location'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($loc['location']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <!-- Experience -->
                    <label class="form-label fw-semibold small">Experience</label>
                    <select name="experience" class="form-select form-select-sm mb-4">
                        <option value="">Any Experience</option>
                        <option value="0 years"   <?= $exp==='0 years'   ? 'selected':'' ?>>Fresher</option>
                        <option value="0-1 years" <?= $exp==='0-1 years' ? 'selected':'' ?>>0-1 years</option>
                        <option value="1-3 years" <?= $exp==='1-3 years' ? 'selected':'' ?>>1-3 years</option>
                        <option value="3-5 years" <?= $exp==='3-5 years' ? 'selected':'' ?>>3-5 years</option>
                        <option value="5+ years"  <?= $exp==='5+ years'  ? 'selected':'' ?>>5+ years</option>
                    </select>

                    <!-- Search Button -->
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-2"></i>Search Jobs
                    </button>
                </div>
            </div>

            <!-- ===== Job Cards ===== -->
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-bold">
                        <?= $jobs->num_rows ?> job<?= $jobs->num_rows != 1 ? 's' : '' ?> found
                        <?php if ($keyword || $category || $location || $job_type || $exp): ?>
                            <span class="text-muted fw-normal ms-1">
                                <?php
                                $active = [];
                                if ($keyword)  $active[] = "\"$keyword\"";
                                if ($category) $active[] = $category;
                                if ($location) $active[] = $location;
                                if ($job_type) $active[] = ucfirst($job_type);
                                if ($exp)      $active[] = $exp;
                                echo 'for ' . implode(', ', $active);
                                ?>
                            </span>
                        <?php endif; ?>
                    </span>
                </div>

                <?php if ($jobs->num_rows === 0): ?>
                    <div class="empty-state text-center py-5">
                        <i class="bi bi-search" style="font-size:3rem;color:#cbd5e1;"></i>
                        <h5 class="mt-3">No jobs found</h5>
                        <p class="text-muted">Try adjusting your filters or <a href="browse-jobs.php">clear all</a></p>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php while ($job = $jobs->fetch_assoc()): ?>
                            <div class="col-12">
                                <div class="job-card">
                                    <div class="d-flex gap-3 align-items-start">
                                        <div class="company-logo"><?= strtoupper(substr($job['company_name'], 0, 1)) ?></div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                                <div>
                                                    <a href="browse-jobs.php?view=<?= $job['id'] ?>" class="job-title d-block"><?= htmlspecialchars($job['title']) ?></a>
                                                    <div class="company-name"><?= htmlspecialchars($job['company_name']) ?></div>
                                                </div>
                                                <span class="badge-job-type badge-<?= str_replace('-','', $job['job_type']) ?>"><?= ucfirst(str_replace('-',' ',$job['job_type'])) ?></span>
                                            </div>
                                            <div class="job-meta mt-2 d-flex flex-wrap gap-3">
                                                <span><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($job['location']) ?></span>
                                                <?php if ($job['experience_required']): ?>
                                                    <span><i class="bi bi-briefcase"></i> <?= htmlspecialchars($job['experience_required']) ?></span>
                                                <?php endif; ?>
                                                <span><i class="bi bi-people"></i> <?= $job['app_count'] ?> applicants</span>
                                                <span><i class="bi bi-clock"></i> <?= timeAgo($job['created_at']) ?></span>
                                            </div>
                                            <?php if ($job['skills_required']): ?>
                                                <div class="mt-2 d-flex flex-wrap gap-1">
                                                    <?php foreach (array_slice(explode(',', $job['skills_required']), 0, 4) as $skill): ?>
                                                        <span style="background:#f1f5f9;color:#475569;padding:2px 10px;border-radius:20px;font-size:0.75rem;"><?= htmlspecialchars(trim($skill)) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="mt-3 d-flex justify-content-between align-items-center border-top pt-3">
                                        <?php if ($job['salary_min']): ?>
                                            <span class="salary-range"><i class="bi bi-currency-rupee"></i> <?= number_format($job['salary_min']) ?> - <?= number_format($job['salary_max']) ?>/yr</span>
                                        <?php else: ?>
                                            <span class="text-muted small">Salary not disclosed</span>
                                        <?php endif; ?>
                                        <div class="d-flex gap-2">
                                            <a href="browse-jobs.php?view=<?= $job['id'] ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                            <?php if (isLoggedIn() && getUserRole() === 'student'): ?>
                                                <?php if (in_array($job['id'], $applied_jobs)): ?>
                                                    <span class="btn btn-sm btn-success disabled"><i class="bi bi-check"></i> Applied</span>
                                                <?php else: ?>
                                                    <a href="apply.php?job_id=<?= $job['id'] ?>" class="btn btn-sm btn-primary">Apply</a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <a href="../auth/login.php" class="btn btn-sm btn-primary">Apply</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </form>
<?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
