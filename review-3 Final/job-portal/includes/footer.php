<footer>
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <h5><i class="bi bi-briefcase-fill text-primary"></i> JobConnect</h5>
                <p class="mt-2" style="font-size:0.9rem;">Connecting talented professionals with great opportunities. Find your dream job or hire the best talent today.</p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#"><i class="bi bi-facebook fs-5"></i></a>
                    <a href="#"><i class="bi bi-twitter fs-5"></i></a>
                    <a href="#"><i class="bi bi-linkedin fs-5"></i></a>
                    <a href="#"><i class="bi bi-instagram fs-5"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <h5>For Job Seekers</h5>
                <ul class="list-unstyled mt-2" style="font-size:0.9rem;">
                    <li class="mb-1"><a href="<?= ($base_path ?? '../') ?>student/browse-jobs.php">Browse Jobs</a></li>
                    <li class="mb-1"><a href="<?= ($base_path ?? '../') ?>auth/register.php">Create Account</a></li>
                    <li class="mb-1"><a href="<?= ($base_path ?? '../') ?>student/profile.php">My Profile</a></li>
                    <li class="mb-1"><a href="<?= ($base_path ?? '../') ?>student/my-applications.php">Applications</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-6">
                <h5>For Employers</h5>
                <ul class="list-unstyled mt-2" style="font-size:0.9rem;">
                    <li class="mb-1"><a href="<?= ($base_path ?? '../') ?>employer/post-job.php">Post a Job</a></li>
                    <li class="mb-1"><a href="<?= ($base_path ?? '../') ?>employer/manage-jobs.php">Manage Jobs</a></li>
                    <li class="mb-1"><a href="<?= ($base_path ?? '../') ?>employer/applicants.php">View Applicants</a></li>
                    <li class="mb-1"><a href="<?= ($base_path ?? '../') ?>auth/register.php">Register</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h5>Contact Us</h5>
                <ul class="list-unstyled mt-2" style="font-size:0.9rem;">
                    <li class="mb-2"><i class="bi bi-envelope me-2 text-primary"></i>support@jobconnect.com</li>
                    <li class="mb-2"><i class="bi bi-telephone me-2 text-primary"></i>+91 98765 43210</li>
                    <li class="mb-2"><i class="bi bi-geo-alt me-2 text-primary"></i>Chennai, Tamil Nadu, India</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom text-center">
            <p class="mb-0" style="font-size:0.875rem;">&copy; <?= date('Y') ?> JobConnect. All rights reserved. Built with PHP &amp; MySQL.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js (optional) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- Custom JS -->
<script src="<?= $base_path ?? '../' ?>assets/js/main.js"></script>
</body>
</html>
