<?php
$page_title = 'Recommended Jobs';
$base_path  = '../';
require_once '../config/db.php';
requireRole('student');

$uid     = getUserId();
$profile = $conn->query("SELECT * FROM student_profiles WHERE user_id = $uid")->fetch_assoc();
$user    = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();

// Get skills from profile
$skills_raw = $profile['skills'] ?? '';
$skills_arr = array_filter(array_map('trim', explode(',', $skills_raw)));

// Build skill-based query
$recommended = [];
$skill_match_info = [];

if (!empty($skills_arr)) {
    foreach ($skills_arr as $skill) {
        $skill_safe = $conn->real_escape_string($skill);
        $jobs = $conn->query("
            SELECT j.*, ep.company_name,
                   (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as app_count
            FROM jobs j
            JOIN employer_profiles ep ON j.employer_id = ep.user_id
            WHERE j.status = 'active'
            AND j.skills_required LIKE '%$skill_safe%'
            AND j.id NOT IN (SELECT job_id FROM applications WHERE student_id = $uid)
        ");
        while ($job = $jobs->fetch_assoc()) {
            if (!isset($recommended[$job['id']])) {
                $recommended[$job['id']] = $job;
                $recommended[$job['id']]['matched_skills'] = [];
            }
            $recommended[$job['id']]['matched_skills'][] = $skill;
        }
    }
    // Sort by number of matched skills
    usort($recommended, fn($a, $b) => count($b['matched_skills']) - count($a['matched_skills']));
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-stars me-2"></i>Recommended Jobs</h1>
        <p>Jobs matched based on your skills profile</p>
    </div>
</div>

<div class="container mb-5">
    <?php if (empty($skills_arr)): ?>
        <div class="empty-state text-center py-5">
            <i class="bi bi-person-gear" style="font-size:3rem;color:#cbd5e1;"></i>
            <h5 class="mt-3">No skills in your profile</h5>
            <p class="text-muted">Add your skills to get personalized job recommendations</p>
            <a href="profile.php" class="btn btn-primary mt-2">Update Profile</a>
        </div>
    <?php else: ?>
        <!-- Skills Used -->
        <div class="dash-card mb-4">
            <h6 class="fw-bold mb-3">🎯 Matching based on your skills:</h6>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($skills_arr as $skill): ?>
                    <span class="badge" style="background:#eff6ff;color:#1d4ed8;padding:6px 14px;border-radius:20px;font-size:0.85rem;">
                        <?= htmlspecialchars($skill) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (empty($recommended)): ?>
            <div class="empty-state text-center py-5">
                <i class="bi bi-search" style="font-size:3rem;color:#cbd5e1;"></i>
                <h5 class="mt-3">No matching jobs found</h5>
                <p class="text-muted">Try updating your skills or browse all jobs</p>
                <a href="browse-jobs.php" class="btn btn-primary mt-2">Browse All Jobs</a>
            </div>
        <?php else: ?>
            <p class="text-muted mb-3"><strong><?= count($recommended) ?></strong> jobs matched for you</p>
            <div class="row g-3">
                <?php foreach ($recommended as $job): ?>
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
                                        <div class="d-flex gap-2 align-items-center">
                                            <span class="badge bg-success">
                                                <?= count($job['matched_skills']) ?> skill<?= count($job['matched_skills']) > 1 ? 's' : '' ?> matched
                                            </span>
                                            <span class="badge-job-type badge-<?= str_replace('-','', $job['job_type']) ?>"><?= ucfirst(str_replace('-',' ', $job['job_type'])) ?></span>
                                        </div>
                                    </div>
                                    <div class="job-meta mt-2 d-flex flex-wrap gap-3">
                                        <span><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($job['location']) ?></span>
                                        <?php if ($job['experience_required']): ?>
                                            <span><i class="bi bi-briefcase"></i> <?= htmlspecialchars($job['experience_required']) ?></span>
                                        <?php endif; ?>
                                        <span><i class="bi bi-people"></i> <?= $job['app_count'] ?> applicants</span>
                                    </div>
                                    <!-- Matched Skills -->
                                    <div class="mt-2 d-flex flex-wrap gap-1">
                                        <?php foreach ($job['matched_skills'] as $ms): ?>
                                            <span style="background:#dcfce7;color:#16a34a;padding:2px 10px;border-radius:20px;font-size:0.75rem;">
                                                ✓ <?= htmlspecialchars($ms) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
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
                                    <a href="apply.php?job_id=<?= $job['id'] ?>" class="btn btn-sm btn-primary">Apply Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
