<?php
$page_title = 'My Profile';
$base_path = '../';
require_once '../config/db.php';
requireRole('student');

$uid = getUserId();
$success = '';
$error = '';

$user    = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();
$profile = $conn->query("SELECT * FROM student_profiles WHERE user_id = $uid")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = sanitize($_POST['full_name'] ?? '');
    $phone      = sanitize($_POST['phone'] ?? '');
    $location   = sanitize($_POST['location'] ?? '');
    $bio        = sanitize($_POST['bio'] ?? '');
    $skills     = sanitize($_POST['skills'] ?? '');
    $experience = sanitize($_POST['experience'] ?? '');
    $education  = sanitize($_POST['education'] ?? '');
    $linkedin   = sanitize($_POST['linkedin'] ?? '');
    $portfolio  = sanitize($_POST['portfolio'] ?? '');

    // Handle resume upload
    $resume_file = $profile['resume_file'] ?? '';
    if (!empty($_FILES['resume_file']['name'])) {
        $allowed = ['pdf', 'doc', 'docx'];
        $ext = strtolower(pathinfo($_FILES['resume_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $error = 'Only PDF, DOC, DOCX files are allowed for resume.';
        } elseif ($_FILES['resume_file']['size'] > 5 * 1024 * 1024) {
            $error = 'Resume file size must be under 5MB.';
        } else {
            $upload_dir = '../uploads/resumes/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $filename = 'resume_' . $uid . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['resume_file']['tmp_name'], $upload_dir . $filename)) {
                // Delete old file
                if ($resume_file && file_exists($upload_dir . $resume_file)) {
                    unlink($upload_dir . $resume_file);
                }
                $resume_file = $filename;
            } else {
                $error = 'Failed to upload resume. Check folder permissions.';
            }
        }
    }

    if (!$error) {
        $conn->query("UPDATE users SET full_name='$full_name', phone='$phone', location='$location' WHERE id=$uid");
        $conn->query("UPDATE student_profiles SET bio='$bio', skills='$skills', experience='$experience',
                      education='$education', linkedin='$linkedin', portfolio='$portfolio',
                      resume_file='$resume_file' WHERE user_id=$uid");

        $_SESSION['full_name'] = $full_name;
        $success = 'Profile updated successfully!';

        // Refresh data
        $user    = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();
        $profile = $conn->query("SELECT * FROM student_profiles WHERE user_id = $uid")->fetch_assoc();
    }
}

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
                <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link active" href="profile.php"><i class="bi bi-person-circle"></i> My Profile</a>
                <a class="nav-link" href="browse-jobs.php"><i class="bi bi-search"></i> Browse Jobs</a>
                <a class="nav-link" href="my-applications.php"><i class="bi bi-file-earmark-text"></i> My Applications</a>
                <a class="nav-link" href="../auth/notifications.php"><i class="bi bi-bell"></i> Notifications</a>
                <hr class="mx-3">
                <a class="nav-link text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1">My Profile</h4>
                    <p class="text-muted mb-0">Keep your profile updated to attract employers</p>
                </div>
                <?php if ($profile['resume_file']): ?>
                    <a href="../uploads/resumes/<?= htmlspecialchars($profile['resume_file']) ?>" target="_blank" class="btn btn-outline-primary">
                        <i class="bi bi-file-earmark-pdf me-2"></i>View Resume
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-auto-dismiss"><i class="bi bi-check-circle-fill me-2"></i><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-auto-dismiss"><i class="bi bi-exclamation-circle-fill me-2"></i><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row g-4">
                    <!-- Personal Info -->
                    <div class="col-lg-8">
                        <div class="form-card">
                            <h6 class="fw-bold mb-4 pb-2 border-bottom"><i class="bi bi-person me-2 text-primary"></i>Personal Information</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                                    <small class="text-muted">Email cannot be changed</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+1 (555) 000-0000">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Location</label>
                                    <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($user['location'] ?? '') ?>" placeholder="City, Country">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Professional Bio</label>
                                    <textarea name="bio" class="form-control" rows="4" maxlength="500" placeholder="Tell employers about yourself..."><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-card mt-4">
                            <h6 class="fw-bold mb-4 pb-2 border-bottom"><i class="bi bi-mortarboard me-2 text-primary"></i>Professional Details</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Skills <small class="text-muted">(comma separated)</small></label>
                                    <input type="text" name="skills" class="form-control" value="<?= htmlspecialchars($profile['skills'] ?? '') ?>" placeholder="PHP, JavaScript, MySQL, HTML, CSS">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Experience Level</label>
                                    <select name="experience" class="form-select">
                                        <option value="">Select experience</option>
                                        <?php
                                        $exp_options = ['0 years' => 'Fresher (0 years)', '0-1 years' => '0-1 years', '1-3 years' => '1-3 years', '3-5 years' => '3-5 years', '5+ years' => '5+ years'];
                                        foreach ($exp_options as $val => $label):
                                        ?>
                                            <option value="<?= $val ?>" <?= ($profile['experience'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Education</label>
                                    <input type="text" name="education" class="form-control" value="<?= htmlspecialchars($profile['education'] ?? '') ?>" placeholder="B.Sc Computer Science">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">LinkedIn Profile</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-linkedin"></i></span>
                                        <input type="url" name="linkedin" class="form-control" value="<?= htmlspecialchars($profile['linkedin'] ?? '') ?>" placeholder="https://linkedin.com/in/...">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Portfolio / Website</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-globe"></i></span>
                                        <input type="url" name="portfolio" class="form-control" value="<?= htmlspecialchars($profile['portfolio'] ?? '') ?>" placeholder="https://yourportfolio.com">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resume Upload -->
                    <div class="col-lg-4">
                        <div class="form-card">
                            <h6 class="fw-bold mb-4 pb-2 border-bottom"><i class="bi bi-file-earmark-person me-2 text-primary"></i>Resume</h6>
                            <?php if ($profile['resume_file']): ?>
                                <div class="p-3 rounded mb-3" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-file-earmark-check-fill text-success fs-4"></i>
                                        <div>
                                            <div class="fw-semibold small">Resume Uploaded</div>
                                            <small class="text-muted"><?= htmlspecialchars($profile['resume_file']) ?></small>
                                        </div>
                                    </div>
                                    <a href="../uploads/resumes/<?= htmlspecialchars($profile['resume_file']) ?>" target="_blank" class="btn btn-sm btn-outline-success w-100 mt-2">
                                        <i class="bi bi-eye me-1"></i>View Resume
                                    </a>
                                </div>
                            <?php endif; ?>

                            <div class="border-2 border-dashed rounded p-4 text-center" style="border:2px dashed #e2e8f0;cursor:pointer;" onclick="document.getElementById('resume_file').click()">
                                <i class="bi bi-cloud-upload fs-2 text-primary d-block mb-2"></i>
                                <div id="fileLabel" class="small text-muted">
                                    Click to upload resume<br>
                                    <span class="text-muted" style="font-size:0.75rem;">PDF, DOC, DOCX (Max 5MB)</span>
                                </div>
                            </div>
                            <input type="file" name="resume_file" id="resume_file" class="d-none" accept=".pdf,.doc,.docx">
                        </div>

                        <!-- Skills Preview -->
                        <?php if ($profile['skills']): ?>
                        <div class="form-card mt-4">
                            <h6 class="fw-bold mb-3">Your Skills</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach (explode(',', $profile['skills']) as $skill): ?>
                                    <span class="badge" style="background:#eff6ff;color:#1d4ed8;font-size:0.8rem;padding:6px 12px;border-radius:20px;">
                                        <?= htmlspecialchars(trim($skill)) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
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
