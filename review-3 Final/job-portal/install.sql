-- ============================================================
-- Job Portal - XAMPP Install Script
-- HOW TO USE:
--   1. Open phpMyAdmin (http://localhost/phpmyadmin)
--   2. Click "SQL" tab
--   3. Paste this entire file and click "Go"
-- ============================================================

DROP DATABASE IF EXISTS job_portal;
CREATE DATABASE job_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE job_portal;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    full_name  VARCHAR(100) NOT NULL,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('student','employer','admin') NOT NULL DEFAULT 'student',
    phone      VARCHAR(20),
    location   VARCHAR(100),
    profile_pic VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE: student_profiles
-- ============================================================
CREATE TABLE student_profiles (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    bio         TEXT,
    skills      TEXT,
    experience  VARCHAR(50),
    education   VARCHAR(200),
    resume_file VARCHAR(255),
    linkedin    VARCHAR(255),
    portfolio   VARCHAR(255),
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: employer_profiles
-- ============================================================
CREATE TABLE employer_profiles (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT NOT NULL,
    company_name        VARCHAR(150) NOT NULL,
    company_description TEXT,
    industry            VARCHAR(100),
    website             VARCHAR(255),
    company_size        VARCHAR(50),
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: jobs
-- ============================================================
CREATE TABLE jobs (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    employer_id         INT NOT NULL,
    title               VARCHAR(150) NOT NULL,
    description         TEXT NOT NULL,
    skills_required     TEXT,
    category            VARCHAR(100),
    location            VARCHAR(100),
    job_type            ENUM('full-time','part-time','internship','remote','contract') DEFAULT 'full-time',
    experience_required VARCHAR(50),
    salary_min          DECIMAL(10,2),
    salary_max          DECIMAL(10,2),
    deadline            DATE,
    status              ENUM('active','closed','draft') DEFAULT 'active',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: applications
-- ============================================================
CREATE TABLE applications (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    job_id       INT NOT NULL,
    student_id   INT NOT NULL,
    cover_letter TEXT,
    resume_file  VARCHAR(255),
    status       ENUM('pending','reviewed','shortlisted','rejected','hired') DEFAULT 'pending',
    applied_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id)     REFERENCES jobs(id)  ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (job_id, student_id)
);

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    message    TEXT NOT NULL,
    is_read    TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: job_alerts  (used by innovation/job_alert.php)
-- ============================================================
CREATE TABLE job_alerts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    keyword      VARCHAR(100),
    location     VARCHAR(100),
    job_type     VARCHAR(50),
    email_notify TINYINT(1) DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- DEMO USERS
-- Password for ALL accounts = "password"
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- ============================================================
INSERT INTO users (full_name, email, password, role, location) VALUES
-- Admin
('Admin User',      'admin@jobportal.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',    'Chennai'),
-- Employers
('TechCorp HR',     'employer@techcorp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employer', 'Chennai'),
('Infosys HR',      'hr@infosys.com',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employer', 'Bangalore'),
('Zoho Corp HR',    'hr@zoho.com',           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employer', 'Chennai'),
('Wipro Recruiter', 'hr@wipro.com',          '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employer', 'Bangalore'),
('TCS Talent',      'hr@tcs.com',            '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employer', 'Mumbai'),
('Freshworks HR',   'hr@freshworks.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employer', 'Chennai'),
-- Students
('John Doe',        'student@example.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',  'Chennai'),
('Priya Kumar',     'priya@example.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',  'Bangalore'),
('Rahul Singh',     'rahul@example.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',  'Mumbai'),
('Anita Sharma',    'anita@example.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',  'Hyderabad'),
('Karthik R',       'karthik@example.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',  'Chennai');

-- ============================================================
-- EMPLOYER PROFILES
-- ============================================================
INSERT INTO employer_profiles (user_id, company_name, company_description, industry, website, company_size) VALUES
(2, 'TechCorp Solutions', 'A leading technology company specializing in software development and digital transformation.', 'Information Technology', 'https://techcorp.com',    '100-500'),
(3, 'Infosys',            'Global leader in next-generation digital services and consulting.',                            'Information Technology', 'https://infosys.com',     '100000+'),
(4, 'Zoho Corporation',   'Technology company that makes software to run your entire business.',                          'Software Products',      'https://zoho.com',        '10000+'),
(5, 'Wipro',              'Leading global IT, consulting and business process services company.',                         'Information Technology', 'https://wipro.com',       '50000+'),
(6, 'TCS',                'Tata Consultancy Services - IT services, consulting and business solutions.',                  'Information Technology', 'https://tcs.com',         '100000+'),
(7, 'Freshworks',         'Cloud-based customer engagement software for businesses of all sizes.',                        'SaaS / Cloud',           'https://freshworks.com',  '5000+');

-- ============================================================
-- STUDENT PROFILES
-- ============================================================
INSERT INTO student_profiles (user_id, bio, skills, experience, education) VALUES
(8,  'Passionate PHP developer looking for opportunities to grow.',        'PHP, MySQL, JavaScript, HTML, CSS',        '0-1 years', 'B.Sc Computer Science'),
(9,  'Frontend developer with React experience and a keen eye for UI.',    'React, JavaScript, HTML, CSS, Bootstrap',  '0-1 years', 'B.E Computer Science'),
(10, 'Data analyst fresher with strong Python and SQL skills.',            'Python, SQL, Excel, Power BI',             '0 years',   'B.Sc Statistics'),
(11, 'Full stack developer with 1 year of hands-on experience.',           'React, Node.js, MongoDB, Express',         '1-3 years', 'MCA'),
(12, 'Mobile app developer interested in Android and cross-platform dev.', 'Java, Android, Firebase, XML',             '0-1 years', 'B.E Electronics');

-- ============================================================
-- JOBS
-- ============================================================
INSERT INTO jobs (employer_id, title, description, skills_required, category, location, job_type, experience_required, salary_min, salary_max, deadline, status) VALUES
-- TechCorp (user_id=2)
(2, 'PHP Developer',          'Looking for an experienced PHP developer to build and maintain web applications. You will work on backend logic, database design, and REST APIs.',                    'PHP, MySQL, Laravel, JavaScript',        'Technology',     'Chennai',   'full-time',  '1-3 years', 300000,  600000,  '2026-09-01', 'active'),
(2, 'React Developer',        'Frontend developer needed to build modern, responsive UI components using React.js.',                                                                               'React, JavaScript, CSS, HTML, Redux',    'Technology',     'Chennai',   'full-time',  '0-1 years', 250000,  500000,  '2026-09-01', 'active'),
(2, 'Full Stack Developer',   'Build end-to-end web applications using React on the frontend and Node.js on the backend.',                                                                         'React, Node.js, MongoDB, Express',       'Technology',     'Chennai',   'full-time',  '1-3 years', 500000,  900000,  '2026-09-01', 'active'),
(2, 'Web Designer Intern',    'Great opportunity for freshers to gain real-world web design experience. Work on live projects.',                                                                    'HTML, CSS, Figma, Photoshop',            'Design',         'Chennai',   'internship', '0 years',   10000,   20000,   '2026-09-01', 'active'),
(2, 'Node.js Developer',      'Backend API developer using Node.js and Express. Build scalable microservices.',                                                                                    'Node.js, Express, MongoDB, JavaScript',  'Technology',     'Remote',    'remote',     '0-1 years', 300000,  550000,  '2026-09-01', 'active'),
-- Infosys (user_id=3)
(3, 'Java Full Stack Developer','Design and develop high-volume, low-latency applications for mission-critical systems.',                                                                          'Java, Spring Boot, React, MySQL',        'Technology',     'Chennai',   'full-time',  '1-3 years', 400000,  700000,  '2026-09-01', 'active'),
(3, 'Python Developer',       'Develop and maintain Python-based applications and data pipelines.',                                                                                               'Python, Django, Flask, PostgreSQL',      'Technology',     'Bangalore', 'full-time',  '1-3 years', 450000,  750000,  '2026-09-01', 'active'),
(3, 'Software Engineer Intern','Work on real-world projects and gain industry experience. Mentorship provided.',                                                                                   'Java, Python, HTML, CSS, JavaScript',    'Technology',     'Chennai',   'internship', '0 years',   15000,   25000,   '2026-09-01', 'active'),
(3, 'DevOps Engineer',        'Manage CI/CD pipelines, cloud infrastructure and deployment automation.',                                                                                          'AWS, Docker, Kubernetes, Jenkins',       'Technology',     'Hyderabad', 'full-time',  '3-5 years', 800000,  1200000, '2026-09-01', 'active'),
-- Zoho (user_id=4)
(4, 'Product Engineer',       'Build and enhance Zoho product features used by millions of customers worldwide.',                                                                                 'Java, JavaScript, MySQL, REST API',      'Technology',     'Chennai',   'full-time',  '0-1 years', 400000,  700000,  '2026-09-01', 'active'),
(4, 'UI/UX Designer',         'Design intuitive and beautiful user interfaces for Zoho products.',                                                                                                'Figma, Adobe XD, HTML, CSS',             'Design',         'Chennai',   'full-time',  '1-3 years', 400000,  700000,  '2026-09-01', 'active'),
(4, 'Content Writer',         'Write technical blogs, product documentation and marketing content for Zoho products.',                                                                            'Content Writing, SEO, English',          'Marketing',      'Remote',    'part-time',  '0 years',   80000,   150000,  '2026-09-01', 'active'),
-- Wipro (user_id=5)
(5, 'React.js Developer',     'Build modern, responsive web applications using React.js for enterprise clients.',                                                                                 'React, JavaScript, HTML, CSS, Redux',    'Technology',     'Bangalore', 'full-time',  '1-3 years', 500000,  800000,  '2026-09-01', 'active'),
(5, 'Node.js Backend Dev',    'Develop scalable backend services and REST APIs using Node.js.',                                                                                                   'Node.js, Express, MongoDB, REST API',    'Technology',     'Pune',      'full-time',  '1-3 years', 450000,  750000,  '2026-09-01', 'active'),
(5, 'QA Test Engineer',       'Perform manual and automated testing of web and mobile applications.',                                                                                             'Selenium, Java, TestNG, JIRA',           'Technology',     'Chennai',   'full-time',  '0-1 years', 300000,  500000,  '2026-09-01', 'active'),
-- TCS (user_id=6)
(6, 'PHP Laravel Developer',  'Develop and maintain web applications using PHP and the Laravel framework.',                                                                                       'PHP, Laravel, MySQL, JavaScript',        'Technology',     'Mumbai',    'full-time',  '1-3 years', 350000,  600000,  '2026-09-01', 'active'),
(6, 'Data Analyst',           'Analyze large datasets and provide actionable business insights using Python and SQL.',                                                                             'Python, SQL, Excel, Power BI',           'Technology',     'Chennai',   'full-time',  '0-1 years', 350000,  600000,  '2026-09-01', 'active'),
(6, 'Machine Learning Eng',   'Build and deploy machine learning models for real-world business applications.',                                                                                   'Python, TensorFlow, Scikit-learn, SQL',  'Technology',     'Bangalore', 'full-time',  '3-5 years', 900000,  1500000, '2026-09-01', 'active'),
(6, 'HR Recruiter',           'Source, screen and recruit top talent for TCS technology roles across India.',                                                                                     'Recruitment, Communication, LinkedIn',   'Human Resources','Mumbai',    'full-time',  '0-1 years', 300000,  500000,  '2026-09-01', 'active'),
-- Freshworks (user_id=7)
(7, 'Frontend Engineer',      'Build fast, accessible and beautiful frontend experiences for Freshworks products.',                                                                               'React, JavaScript, TypeScript, CSS',     'Technology',     'Chennai',   'full-time',  '1-3 years', 600000,  1000000, '2026-09-01', 'active'),
(7, 'Web Designer Intern',    'Create web designs and landing pages for Freshworks marketing campaigns.',                                                                                         'HTML, CSS, Figma, Photoshop',            'Design',         'Chennai',   'internship', '0 years',   12000,   20000,   '2026-09-01', 'active'),
(7, 'iOS Developer',          'Build and maintain iOS applications for the Freshworks mobile product suite.',                                                                                     'Swift, Xcode, iOS SDK, REST API, Git',   'Technology',     'Chennai',   'full-time',  '1-3 years', 700000,  1200000, '2026-09-01', 'active');

-- ============================================================
-- SAMPLE APPLICATIONS
-- ============================================================
INSERT INTO applications (job_id, student_id, cover_letter, status) VALUES
(1,  8,  'I am a PHP developer with strong MySQL skills. I would love to join your team and contribute to your projects.', 'pending'),
(2,  9,  'I have React experience and have built several projects. I am excited about this opportunity.',                  'shortlisted'),
(3,  11, 'Full stack developer with React and Node.js experience. I have built production-ready applications.',            'hired'),
(5,  11, 'I have Node.js and MongoDB experience and enjoy building scalable APIs.',                                        'reviewed'),
(17, 10, 'I am a data analyst with Python and SQL skills. I am passionate about turning data into insights.',              'pending'),
(4,  9,  'I am a fresher web designer with Figma skills and a strong portfolio.',                                          'pending');

-- ============================================================
-- SAMPLE NOTIFICATIONS
-- ============================================================
INSERT INTO notifications (user_id, message, is_read) VALUES
(8,  'Your application for PHP Developer at TechCorp has been received.',              0),
(9,  'Congratulations! You have been shortlisted for React Developer at TechCorp.',    1),
(10, 'Your application for Data Analyst at TCS is under review.',                      0),
(11, 'Congratulations! You have been hired for Full Stack Developer at TechCorp!',     1);

-- ============================================================
-- VERIFY COUNTS
-- ============================================================
SELECT 'users'             AS table_name, COUNT(*) AS total FROM users
UNION ALL SELECT 'employer_profiles', COUNT(*) FROM employer_profiles
UNION ALL SELECT 'student_profiles',  COUNT(*) FROM student_profiles
UNION ALL SELECT 'jobs',              COUNT(*) FROM jobs
UNION ALL SELECT 'applications',      COUNT(*) FROM applications
UNION ALL SELECT 'notifications',     COUNT(*) FROM notifications
UNION ALL SELECT 'job_alerts',        COUNT(*) FROM job_alerts;
