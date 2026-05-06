# JobConnect - Job Portal

A complete PHP + MySQL job portal built for XAMPP. Supports three roles: **Student**, **Employer**, and **Admin**.

---

## Quick Setup (XAMPP)

### Step 1 — Start XAMPP
Open XAMPP Control Panel and start **Apache** and **MySQL**.

### Step 2 — Copy Project
Copy the `job-portal` folder into:
```
C:\xampp\htdocs\job-portal
```

### Step 3 — Import Database
1. Open **phpMyAdmin**: http://localhost/phpmyadmin
2. Click the **SQL** tab
3. Open `install.sql`, copy all contents, paste into the SQL tab and click **Go**

### Step 4 — Open the App
Visit: http://localhost/job-portal/

---

## Demo Login Credentials

All accounts use password: **`password`**

| Role     | Email                    |
|----------|--------------------------|
| Admin    | admin@jobportal.com      |
| Employer | employer@techcorp.com    |
| Student  | student@example.com      |

> On the login page, click the role buttons to auto-fill credentials.

---

## Features

### Student
- Register and complete profile (skills, education, resume upload)
- Browse and search jobs with filters (category, location, type, experience)
- Apply to jobs with cover letter and resume
- Track application status (pending → reviewed → shortlisted → hired)
- Recommended jobs based on skills
- Job alerts (subscribe by keyword/location/type)
- In-app notifications

### Employer
- Post and manage job listings
- View all applicants per job
- Update application status (sends notification to student)
- Company profile management
- Dashboard with stats and charts

### Admin
- View and delete users
- View and toggle/delete all jobs
- Monitor all applications across the platform

### Innovation Features
- **Chatbot** — AI-style assistant answers job queries (bottom-right on every page)
- **Job Map** — Interactive map showing job locations across India (Leaflet.js, no API key needed)
- **Job Alerts** — Students subscribe to alerts by keyword/location/type

---

## Project Structure

```
job-portal/
├── config/
│   └── db.php              ← Database connection + all helper functions
├── auth/
│   ├── login.php           ← Login with OTP verification
│   ├── register.php        ← Register as student or employer
│   ├── logout.php
│   ├── notifications.php   ← View all notifications
│   └── mark_notifications.php
├── student/
│   ├── dashboard.php
│   ├── browse-jobs.php
│   ├── apply.php
│   ├── my-applications.php
│   ├── profile.php
│   └── recommended.php
├── employer/
│   ├── dashboard.php
│   ├── post-job.php
│   ├── manage-jobs.php
│   ├── applicants.php
│   └── profile.php
├── admin/
│   ├── dashboard.php
│   ├── users.php
│   ├── jobs.php
│   └── applications.php
├── innovation/
│   ├── chatbot.php         ← Chatbot API endpoint
│   ├── job_alert.php       ← Job alert subscriptions
│   └── maps.php            ← Interactive job map
├── includes/
│   ├── header.php          ← Navbar + chatbot widget
│   └── footer.php
├── assets/
│   ├── css/style.css
│   └── js/main.js
├── uploads/
│   └── resumes/            ← Uploaded resume files (writable)
└── install.sql             ← Single database setup file
```

---

## Database Tables

| Table              | Purpose                          |
|--------------------|----------------------------------|
| users              | All users (student/employer/admin)|
| student_profiles   | Student details, skills, resume  |
| employer_profiles  | Company information              |
| jobs               | Job listings                     |
| applications       | Job applications with status     |
| notifications      | In-app notifications             |
| job_alerts         | Student job alert subscriptions  |

---

## Requirements
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- XAMPP (Apache + MySQL)
- The `uploads/resumes/` folder must be writable by the web server
