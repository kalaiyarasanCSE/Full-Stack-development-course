<?php
$page_title = 'Home';
$base_path = '';
require_once 'config/db.php';

// Stats for hero
$total_jobs      = $conn->query("SELECT COUNT(*) as c FROM jobs WHERE status='active'")->fetch_assoc()['c'];
$total_companies = $conn->query("SELECT COUNT(*) as c FROM employer_profiles")->fetch_assoc()['c'];
$total_students  = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student'")->fetch_assoc()['c'];
$total_hired     = $conn->query("SELECT COUNT(*) as c FROM applications WHERE status='hired'")->fetch_assoc()['c'];

// Featured jobs
$featured_jobs = $conn->query("
    SELECT j.*, ep.company_name,
           (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as app_count
    FROM jobs j
    JOIN employer_profiles ep ON j.employer_id = ep.user_id
    WHERE j.status = 'active'
    ORDER BY j.created_at DESC LIMIT 6
");

// Categories with job counts
$categories = $conn->query("
    SELECT category, COUNT(*) as count
    FROM jobs WHERE status='active' AND category IS NOT NULL
    GROUP BY category ORDER BY count DESC LIMIT 8
");

$category_icons = [
    'Technology' => 'bi-laptop',
    'Design' => 'bi-palette',
    'Marketing' => 'bi-megaphone',
    'Finance' => 'bi-graph-up',
    'Healthcare' => 'bi-heart-pulse',
    'Education' => 'bi-book',
    'Sales' => 'bi-cart',
    'HR' => 'bi-people',
    'Operations' => 'bi-gear',
    'Other' => 'bi-briefcase',
];

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container position-relative" style="z-index:1;">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="mb-3">Find Your <span style="color:#93c5fd;">Dream Job</span><br>Start Your Career Today</h1>
                <p class="mb-4">Connect with top employers and discover thousands of opportunities. Your next career move starts here.</p>

                <!-- Search Box -->
                <form action="student/browse-jobs.php" method="GET">
                    <div class="search-box d-flex gap-2">
                        <input type="text" name="keyword" class="form-control border-0" placeholder="Job title, skill, or keyword..." style="font-size:1rem;">
                        <select name="category" class="form-select border-0" style="max-width:160px;">
                            <option value="">All Categories</option>
                            <?php
                            $cats = ['Technology', 'Design', 'Marketing', 'Finance', 'Healthcare', 'Education', 'Sales', 'HR'];
                            foreach ($cats as $cat): ?>
                                <option value="<?= $cat ?>"><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-search me-1"></i>Search
                        </button>
                    </div>
                </form>

                <div class="mt-3 d-flex flex-wrap gap-2">
                    <span class="text-white-50 small">Popular:</span>
                    <?php foreach (['PHP Developer', 'Frontend', 'Remote', 'Internship'] as $tag): ?>
                        <a href="student/browse-jobs.php?keyword=<?= urlencode($tag) ?>"
                           class="badge text-decoration-none"
                           style="background:rgba(255,255,255,0.15);color:white;padding:6px 14px;border-radius:20px;font-size:0.8rem;">
                            <?= $tag ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-flex justify-content-center">
                <div style="font-size:12rem;opacity:0.15;line-height:1;">💼</div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-6 col-md-3 stat-card">
                <h3><?= number_format($total_jobs) ?>+</h3>
                <p>Active Jobs</p>
            </div>
            <div class="col-6 col-md-3 stat-card">
                <h3><?= number_format($total_companies) ?>+</h3>
                <p>Companies</p>
            </div>
            <div class="col-6 col-md-3 stat-card">
                <h3><?= number_format($total_students) ?>+</h3>
                <p>Job Seekers</p>
            </div>
            <div class="col-6 col-md-3 stat-card">
                <h3><?= number_format($total_hired) ?>+</h3>
                <p>Successful Hires</p>
            </div>
        </div>
    </div>
</section>

<!-- Job Categories -->
<?php if ($categories->num_rows > 0): ?>
<section class="py-5" style="background:#f8fafc;">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="fw-bold">Browse by Category</h2>
            <p class="text-muted">Find jobs in your field of expertise</p>
        </div>
        <div class="row g-3">
            <?php while ($cat = $categories->fetch_assoc()): ?>
                <div class="col-6 col-md-3">
                    <a href="student/browse-jobs.php?category=<?= urlencode($cat['category']) ?>" class="text-decoration-none">
                        <div class="dash-card text-center p-4" style="cursor:pointer;transition:all 0.3s;">
                            <i class="bi <?= $category_icons[$cat['category']] ?? 'bi-briefcase' ?> fs-2 text-primary d-block mb-2"></i>
                            <div class="fw-bold"><?= htmlspecialchars($cat['category']) ?></div>
                            <small class="text-muted"><?= $cat['count'] ?> job<?= $cat['count'] > 1 ? 's' : '' ?></small>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Featured Jobs -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Latest Job Openings</h2>
                <p class="text-muted mb-0">Fresh opportunities posted recently</p>
            </div>
            <a href="student/browse-jobs.php" class="btn btn-outline-primary">View All Jobs</a>
        </div>

        <?php if ($featured_jobs->num_rows === 0): ?>
            <div class="empty-state">
                <i class="bi bi-briefcase"></i>
                <h5>No jobs available yet</h5>
                <p>Check back soon for new opportunities!</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php while ($job = $featured_jobs->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="job-card h-100">
                            <div class="d-flex gap-3 align-items-start">
                                <div class="company-logo"><?= strtoupper(substr($job['company_name'], 0, 1)) ?></div>
                                <div class="flex-grow-1">
                                    <a href="student/browse-jobs.php?view=<?= $job['id'] ?>" class="job-title d-block"><?= htmlspecialchars($job['title']) ?></a>
                                    <div class="company-name"><?= htmlspecialchars($job['company_name']) ?></div>
                                    <div class="job-meta mt-2">
                                        <span class="me-3"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($job['location']) ?></span>
                                        <span class="badge-job-type badge-<?= str_replace('-', '', $job['job_type']) ?>"><?= ucfirst($job['job_type']) ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php if ($job['skills_required']): ?>
                                <div class="mt-3 d-flex flex-wrap gap-1">
                                    <?php foreach (array_slice(explode(',', $job['skills_required']), 0, 3) as $skill): ?>
                                        <span style="background:#f1f5f9;color:#475569;padding:2px 10px;border-radius:20px;font-size:0.75rem;"><?= htmlspecialchars(trim($skill)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="mt-3 d-flex justify-content-between align-items-center border-top pt-3">
                                <?php if ($job['salary_min']): ?>
                                    <span class="salary-range small">$<?= number_format($job['salary_min']) ?> - $<?= number_format($job['salary_max']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted small"><i class="bi bi-people"></i> <?= $job['app_count'] ?> applicants</span>
                                <?php endif; ?>
                                <a href="<?= isLoggedIn() && getUserRole() === 'student' ? 'student/apply.php?job_id='.$job['id'] : 'auth/login.php' ?>"
                                   class="btn btn-sm btn-primary">Apply Now</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- How It Works -->
<section class="py-5" style="background:#f8fafc;">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">How It Works</h2>
            <p class="text-muted">Get started in just a few simple steps</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <div style="width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;color:white;font-size:1.5rem;">
                    <i class="bi bi-person-plus"></i>
                </div>
                <h5 class="fw-bold">1. Create Account</h5>
                <p class="text-muted">Register as a job seeker or employer. Complete your profile to stand out.</p>
            </div>
            <div class="col-md-4 text-center">
                <div style="width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#ec4899);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;color:white;font-size:1.5rem;">
                    <i class="bi bi-search"></i>
                </div>
                <h5 class="fw-bold">2. Find & Apply</h5>
                <p class="text-muted">Browse thousands of jobs, filter by your preferences, and apply with one click.</p>
            </div>
            <div class="col-md-4 text-center">
                <div style="width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,#16a34a,#0891b2);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;color:white;font-size:1.5rem;">
                    <i class="bi bi-trophy"></i>
                </div>
                <h5 class="fw-bold">3. Get Hired</h5>
                <p class="text-muted">Track your applications, get notified when shortlisted, and land your dream job.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<?php if (!isLoggedIn()): ?>
<section class="py-5" style="background:linear-gradient(135deg,#1e3a8a,#2563eb,#7c3aed);color:white;">
    <div class="container text-center">
        <h2 class="fw-bold mb-3">Ready to Start Your Journey?</h2>
        <p class="mb-4 opacity-75">Join thousands of professionals who found their dream jobs through JobConnect</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="auth/register.php" class="btn btn-light btn-lg px-5 fw-bold">
                <i class="bi bi-person-plus me-2"></i>Get Started Free
            </a>
            <a href="student/browse-jobs.php" class="btn btn-outline-light btn-lg px-5">
                <i class="bi bi-search me-2"></i>Browse Jobs
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
