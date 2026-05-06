// ===== Job Portal - Main JavaScript =====

document.addEventListener('DOMContentLoaded', function () {

    // ===== Live Job Search Filter =====
    const searchInput = document.getElementById('liveSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            filterJobs();
        });
    }

    // ===== Filter dropdowns =====
    const filterSelects = document.querySelectorAll('.filter-select');
    filterSelects.forEach(sel => {
        sel.addEventListener('change', filterJobs);
    });

    function filterJobs() {
        const keyword  = (document.getElementById('liveSearch')?.value || '').toLowerCase();
        const category = (document.getElementById('filterCategory')?.value || '').toLowerCase();
        const location = (document.getElementById('filterLocation')?.value || '').toLowerCase();
        const jobType  = (document.getElementById('filterType')?.value || '').toLowerCase();

        const cards = document.querySelectorAll('.job-card-wrapper');
        let visible = 0;

        cards.forEach(card => {
            const title        = (card.dataset.title    || '').toLowerCase();
            const cardCategory = (card.dataset.category || '').toLowerCase();
            const cardLocation = (card.dataset.location || '').toLowerCase();
            const cardType     = (card.dataset.type     || '').toLowerCase();
            const skills       = (card.dataset.skills   || '').toLowerCase();

            const matchKeyword  = !keyword  || title.includes(keyword) || skills.includes(keyword) || cardCategory.includes(keyword);
            const matchCategory = !category || cardCategory.includes(category);
            const matchLocation = !location || cardLocation.includes(location);
            const matchType     = !jobType  || cardType === jobType;

            if (matchKeyword && matchCategory && matchLocation && matchType) {
                card.style.display = '';
                visible++;
            } else {
                card.style.display = 'none';
            }
        });

        const noResults = document.getElementById('noResults');
        if (noResults) {
            noResults.style.display = visible === 0 ? 'block' : 'none';
        }

        const resultCount = document.getElementById('resultCount');
        if (resultCount) {
            resultCount.textContent = visible + ' job' + (visible !== 1 ? 's' : '') + ' found';
        }
    }

    // ===== Table Search Filter =====
    const tableSearch = document.getElementById('tableSearch');
    if (tableSearch) {
        tableSearch.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            const rows  = document.querySelectorAll('.searchable-table tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // ===== Notification Bell =====
    const notifBell = document.getElementById('notifBell');
    if (notifBell) {
        notifBell.addEventListener('click', function () {
            markNotificationsRead();
        });
    }

    function markNotificationsRead() {
        const base = document.querySelector('meta[name="base_path"]')?.content || '../';
        fetch(base + 'auth/mark_notifications.php', { method: 'POST' })
            .then(() => {
                const badge = document.querySelector('.notif-badge');
                if (badge) badge.remove();
            })
            .catch(() => {});
    }

    // ===== Role Selector on Register =====
    const roleBtns  = document.querySelectorAll('.role-btn');
    const roleInput = document.getElementById('roleInput');

    roleBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            roleBtns.forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
            if (roleInput) roleInput.value = this.dataset.role;
        });
    });

    // ===== Password Strength Indicator =====
    const passwordInput = document.getElementById('password');
    const strengthBar   = document.getElementById('strengthBar');
    const strengthText  = document.getElementById('strengthText');

    if (passwordInput && strengthBar) {
        passwordInput.addEventListener('input', function () {
            const val = this.value;
            let strength = 0;
            if (val.length >= 8)          strength++;
            if (/[A-Z]/.test(val))        strength++;
            if (/[0-9]/.test(val))        strength++;
            if (/[^A-Za-z0-9]/.test(val)) strength++;

            const levels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
            const colors = ['', '#dc2626', '#d97706', '#2563eb', '#16a34a'];
            const widths = ['0%', '25%', '50%', '75%', '100%'];

            strengthBar.style.width      = widths[strength];
            strengthBar.style.background = colors[strength];
            if (strengthText) {
                strengthText.textContent = levels[strength];
                strengthText.style.color = colors[strength];
            }
        });
    }

    // ===== Confirm Delete =====
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function (e) {
            if (!confirm('Are you sure you want to delete this? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // ===== Auto-dismiss alerts =====
    const alerts = document.querySelectorAll('.alert-auto-dismiss');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity    = '0';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    // ===== Salary Range Display =====
    const salaryMin     = document.getElementById('salary_min');
    const salaryMax     = document.getElementById('salary_max');
    const salaryDisplay = document.getElementById('salaryDisplay');

    function updateSalaryDisplay() {
        if (salaryDisplay && salaryMin && salaryMax) {
            const min = parseInt(salaryMin.value) || 0;
            const max = parseInt(salaryMax.value) || 0;
            if (min > 0 || max > 0) {
                salaryDisplay.textContent = '\u20B9' + min.toLocaleString() + ' - \u20B9' + max.toLocaleString();
            } else {
                salaryDisplay.textContent = '';
            }
        }
    }

    if (salaryMin) salaryMin.addEventListener('input', updateSalaryDisplay);
    if (salaryMax) salaryMax.addEventListener('input', updateSalaryDisplay);

    // ===== Character Counter for Textareas =====
    const textareas = document.querySelectorAll('textarea[maxlength]');
    textareas.forEach(ta => {
        const counter       = document.createElement('small');
        counter.className   = 'text-muted float-end';
        ta.parentNode.appendChild(counter);

        function updateCounter() {
            const remaining        = ta.maxLength - ta.value.length;
            counter.textContent    = remaining + ' characters remaining';
            counter.style.color    = remaining < 50 ? '#dc2626' : '#94a3b8';
        }

        ta.addEventListener('input', updateCounter);
        updateCounter();
    });

    // ===== File Upload Preview =====
    const resumeInput = document.getElementById('resume_file');
    const fileLabel   = document.getElementById('fileLabel');

    if (resumeInput && fileLabel) {
        resumeInput.addEventListener('change', function () {
            if (this.files.length > 0) {
                const file = this.files[0];
                const size = (file.size / 1024 / 1024).toFixed(2);
                fileLabel.innerHTML = '<i class="bi bi-file-earmark-check text-success"></i> ' + file.name + ' (' + size + ' MB)';
            }
        });
    }

    // ===== Tooltip Init =====
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(el => new bootstrap.Tooltip(el));

    // ===== Smooth scroll =====
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ===== Dashboard Chart (if canvas exists) =====
    const chartCanvas = document.getElementById('applicationsChart');
    if (chartCanvas && typeof Chart !== 'undefined') {
        const ctx    = chartCanvas.getContext('2d');
        const labels = JSON.parse(chartCanvas.dataset.labels || '[]');
        const data   = JSON.parse(chartCanvas.dataset.values || '[]');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Applications',
                    data: data,
                    backgroundColor: 'rgba(37, 99, 235, 0.7)',
                    borderColor:     'rgba(37, 99, 235, 1)',
                    borderWidth:     2,
                    borderRadius:    6
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    // ===== Status color update =====
    const statusSelects = document.querySelectorAll('.status-select');
    statusSelects.forEach(sel => {
        sel.addEventListener('change', function () {
            const row = this.closest('tr');
            if (row) {
                const badge = row.querySelector('.status-badge');
                if (badge) {
                    badge.className   = 'status-badge status-' + this.value;
                    badge.textContent = this.value;
                }
            }
        });
    });

});
