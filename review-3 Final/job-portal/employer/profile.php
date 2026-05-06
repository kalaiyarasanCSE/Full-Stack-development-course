<?php
$page_title = 'Company Profile';
$base_path = '../';
require_once '../config/db.php';
requireRole('employer');

$uid = getUserId();
$user    = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();
$profile = $conn->query("SELECT * FROM employer_profiles WHERE user_id = $uid")->fetch_assoc();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name   = sanitize($_POST['full_name'] ?? '');
    $phone       = sanitize($_POST['phone'] ?? '');
    $location    = sanitize($_POST['location'] ?? '');
    $company     = sanitize($_POST['company_name'] ?? '');
    $description = sanitize($_POST['company_description'] ?? '');
    $industry    = sanitize($_POST['industry'] ?? '');
    $website     = sanitize($_POST['website'] ?? '');
    $size        = sanitize($_POST['company_size'] ?? '');

    if (empty($company)) {
        $error = 'Company name is required.';
    } else {
        $conn->query("UPDATE users SET full_name='$full_name', phone='$phone', location='$location' WHERE id=$uid");
        $conn->query("UPDATE employer_profiles SET company_name='$company', company_description='$description',
                      industry='$industry', website='$website', company_size='$size' WHERE user_id=$uid");

        $_SESSION['full_name'] = $full_name;
        $success = 'Company profile updated successfully!';

        $user    = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();
        $profile = $conn->query("SELECT * FROM employer_profiles WHERE user_id = $uid")->fetch_assoc();
    }
}

include '../includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="row g-0">
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
                <a class="nav-link" href="applicants.php"><i class="bi bi-people"></i> All Applicants</a>
                <a class="nav-link active" href="profile.php"><i class="bi bi-building"></i> Company Profile</a>
                <hr class="mx-3">
                <a class="nav-link text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
        </div>

        <div class="col-lg-10 p-4">
            <div class="mb-4">
                <h4 class="fw-bold mb-1">Company Profile</h4>
                <p class="text-muted mb-0">Update your company information visible to job seekers</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-auto-dismiss"><i class="bi bi-check-circle-fill me-2"></i><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-auto-dismiss"><i class="bi bi-exclamation-circle-fill me-2"></i><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="form-card">
                            <h6 class="fw-bold mb-4 pb-2 border-bottom"><i class="bi bi-building me-2 text-primary"></i>Company Information</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Contact Person Name *</label>
                                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Company Name *</label>
                                    <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($profile['company_name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Location</label>
                                    <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($user['location'] ?? '') ?>" placeholder="City, Country">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Industry</label>
                                    <select name="industry" class="form-select">
                                        <option value="">Select Industry</option>
                                        <?php
                                        $industries = ['Information Technology', 'Finance', 'Healthcare', 'Education', 'Manufacturing', 'Retail', 'Media', 'Consulting', 'Real Estate', 'Other'];
                                        foreach ($industries as $ind):
                                        ?>
                                            <option value="<?= $ind ?>" <?= ($profile['industry'] ?? '') === $ind ? 'selected' : '' ?>><?= $ind ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Company Size</label>
                                    <select name="company_size" class="form-select">
                                        <option value="">Select Size</option>
                                        <?php
                                        $sizes = ['1-10', '11-50', '51-100', '100-500', '500-1000', '1000+'];
                                        foreach ($sizes as $s):
                                        ?>
                                            <option value="<?= $s ?>" <?= ($profile['company_size'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?> employees</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Website</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-globe"></i></span>
                                        <input type="url" name="website" class="form-control" value="<?= htmlspecialchars($profile['website'] ?? '') ?>" placeholder="https://yourcompany.com">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Company Description</label>
                                    <textarea name="company_description" class="form-control" rows="5" maxlength="1000"
                                              placeholder="Tell job seekers about your company, culture, and mission..."><?= htmlspecialchars($profile['company_description'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="form-card">
                            <h6 class="fw-bold mb-3">Profile Preview</h6>
                            <div class="text-center mb-3">
                                <div style="width:80px;height:80px;border-radius:16px;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:2rem;margin:0 auto;">
                                    <?= strtoupper(substr($profile['company_name'] ?? 'C', 0, 1)) ?>
                                </div>
                                <div class="fw-bold mt-2"><?= htmlspecialchars($profile['company_name'] ?? '') ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($profile['industry'] ?? '') ?></div>
                            </div>
                            <ul class="list-unstyled small">
                                <?php if ($user['location']): ?>
                                <li class="mb-2"><i class="bi bi-geo-alt me-2 text-primary"></i><?= htmlspecialchars($user['location']) ?></li>
                                <?php endif; ?>
                                <?php if ($profile['company_size']): ?>
                                <li class="mb-2"><i class="bi bi-people me-2 text-primary"></i><?= htmlspecialchars($profile['company_size']) ?> employees</li>
                                <?php endif; ?>
                                <?php if ($profile['website']): ?>
                                <li class="mb-2"><i class="bi bi-globe me-2 text-primary"></i><a href="<?= htmlspecialchars($profile['website']) ?>" target="_blank">Website</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary px-5">
                            <i class="bi bi-save me-2"></i>Save Profile
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
