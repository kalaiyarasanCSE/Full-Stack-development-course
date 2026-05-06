-- ============================================================
-- Job Portal System - Database Setup
-- Run this in phpMyAdmin > SQL tab
-- ============================================================

DROP DATABASE IF EXISTS jobportal;
CREATE DATABASE jobportal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE jobportal;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    full_name  VARCHAR(100) NOT NULL,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('admin','hr','jobseeker') NOT NULL DEFAULT 'jobseeker',
    phone      VARCHAR(20),
    location   VARCHAR(100),
    company    VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE: activity_log
-- ============================================================
CREATE TABLE activity_log (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    action     ENUM('login','logout') NOT NULL,
    ip_address VARCHAR(45),
    logged_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: jobs
-- ============================================================
CREATE TABLE jobs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    hr_id        INT NOT NULL,
    title        VARCHAR(150) NOT NULL,
    company      VARCHAR(150) NOT NULL,
    description  TEXT NOT NULL,
    requirements TEXT,
    location     VARCHAR(100),
    job_type     ENUM('full-time','part-time','internship','remote','contract') DEFAULT 'full-time',
    category     VARCHAR(100),
    salary_min   DECIMAL(10,2),
    salary_max   DECIMAL(10,2),
    deadline     DATE,
    status       ENUM('active','closed','draft') DEFAULT 'active',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hr_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: applications
-- ============================================================
CREATE TABLE applications (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    job_id       INT NOT NULL,
    user_id      INT NOT NULL,
    cover_letter TEXT,
    resume_path  VARCHAR(255),
    status       ENUM('pending','reviewed','shortlisted','rejected','hired') DEFAULT 'pending',
    applied_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id)  REFERENCES jobs(id)  ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_app (job_id, user_id)
);

-- ============================================================
-- ADMIN ACCOUNT (PERMANENT)
-- Email    : admin@jobportal.com
-- Password : vtu26102password
-- Name     : Kalaiyarasan
-- NOTE: HR and Job Seekers register themselves via /auth/register.php
-- ============================================================
INSERT INTO users (full_name, email, password, role, phone, location) VALUES
('Kalaiyarasan', 'admin@jobportal.com', '$2y$10$bZbC.4wyWjMrE0SYhyoL9u0QLA0t8sxuMO9omR1CMfhCfx.49G0Pm', 'admin', '9000000001', 'Chennai');

-- ============================================================
-- VERIFY
-- ============================================================
SELECT 'users' AS tbl, COUNT(*) AS total FROM users
UNION ALL SELECT 'jobs',         COUNT(*) FROM jobs
UNION ALL SELECT 'applications', COUNT(*) FROM applications
UNION ALL SELECT 'activity_log', COUNT(*) FROM activity_log;
