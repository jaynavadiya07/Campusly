-- =====================================================================
-- Campusly Academic Management System Schema
-- Features: RBAC, Dynamic Dashboard Queries, Registrations, Audit Logging
-- Database Name: campusly
-- Tables: students, events, registrations, users, audit_logs
-- =====================================================================

CREATE DATABASE IF NOT EXISTS campusly
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE campusly;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS students;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Table: students (Entity in 3NF)
CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    course VARCHAR(100) NOT NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table: events (Campus Events Catalog in 3NF)
CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(100) NOT NULL,
    venue VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table: registrations (Relational Junction linking Students & Events)
CREATE TABLE registrations (
    reg_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    event_id INT NOT NULL,
    registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reg_students FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_reg_events FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table: users (Authentication, RBAC, Status Toggle, Remember-Me)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    last_login DATETIME NULL,
    remember_token_hash VARCHAR(64) NULL,
    remember_token_expires DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    student_id INT NULL,
    CONSTRAINT fk_users_students FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Table: audit_logs (Audit Trail for Administrative Actions & Security Violations)
CREATE TABLE audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    user_email VARCHAR(100) NOT NULL,
    user_role VARCHAR(50) NOT NULL DEFAULT 'admin',
    action VARCHAR(100) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NOT NULL DEFAULT '127.0.0.1',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_users FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SEED DATA
-- =====================================================================

-- Students
INSERT INTO students (student_id, name, email, course, status) VALUES
(1, 'Jay Navadiya', 'jay@campusly.com', 'CE', 'Active'),
(2, 'jayyyy', 'jayyyy@campusly.com', 'CSE', 'Active'),
(3, 'Aarav Patel', '25cs003@charusat.edu.in', 'CE', 'Active'),
(4, 'Ananya Sharma', '25ce004@charusat.edu.in', 'CE', 'Active'),
(5, 'Rohan Mehta', '25it005@charusat.edu.in', 'IT', 'Active'),
(6, 'Priya Desai', '25cs006@charusat.edu.in', 'CSE', 'Active'),
(7, 'Vikram Joshi', '25ce007@charusat.edu.in', 'CE', 'Active'),
(8, 'Sneha Shah', '25it008@charusat.edu.in', 'IT', 'Active'),
(9, 'Dev Dave', '25ec009@charusat.edu.in', 'EC', 'Active'),
(10, 'Diya Trivedi', '25cs010@charusat.edu.in', 'CSE', 'Active'),
(11, 'Harsh Panchal', '25ce011@charusat.edu.in', 'CE', 'Active'),
(12, 'Ishan Parekh', '25it012@charusat.edu.in', 'IT', 'Active'),
(13, 'Kavya Soni', '25cs013@charusat.edu.in', 'CSE', 'Active'),
(14, 'Meera Vora', '25ce014@charusat.edu.in', 'CE', 'Active'),
(15, 'Neel Shukla', '25ec015@charusat.edu.in', 'EC', 'Active'),
(16, 'Riya Pandya', '25it016@charusat.edu.in', 'IT', 'Active'),
(17, 'Siddharth Rawal', '25cs017@charusat.edu.in', 'CSE', 'Active'),
(18, 'Tanvi Bhatt', '25ce018@charusat.edu.in', 'CE', 'Active'),
(19, 'Varun Prajapati', '25it019@charusat.edu.in', 'IT', 'Active'),
(20, 'Yash Solanki', '25ec020@charusat.edu.in', 'EC', 'Active'),
(21, 'Aditya Verma', '25cs026@charusat.edu.in', 'CSE', 'Inactive');

-- Events
INSERT INTO events (event_id, event_name, venue) VALUES
(1, 'Hackathon', 'Lab 301'),
(2, 'Web Workshop', 'Lab 302'),
(3, 'AI Workshop', 'Auditorium'),
(4, 'Code Wizards', 'CSPIT');

-- Registrations (Linked records with timestamps across months)
INSERT INTO registrations (reg_id, student_id, event_id, registered_at) VALUES
(1, 1, 1, '2026-10-21 14:44:25'),
(2, 2, 2, '2026-10-21 14:44:25'),
(3, 3, 1, '2026-08-15 10:20:00'),
(4, 4, 2, '2026-08-22 14:45:00'),
(5, 5, 3, '2026-09-05 09:15:00'),
(6, 6, 1, '2026-09-12 11:30:00'),
(7, 7, 2, '2026-09-18 16:00:00'),
(8, 8, 4, '2026-09-27 13:25:00'),
(9, 9, 3, '2026-10-02 10:00:00'),
(10, 10, 1, '2026-10-08 14:10:00'),
(11, 11, 2, '2026-10-14 11:45:00'),
(12, 12, 4, '2026-10-18 15:30:00'),
(13, 13, 3, '2026-10-20 09:50:00'),
(14, 14, 1, '2026-10-21 11:15:00');

-- Users
-- Password for admin: Admin@123 ($2y$12$nah.j6ixJ8ZOl33C.6kyouGO3kDb5n.KK.zOJDtTFnslMpmKjImbq)
-- Password for student: Student@123 ($2y$12$0YY8yE51ZMgUQlbHx8h.AeqEnzcjke5n7E.VAdlKwV8RwPbImdbVO)
INSERT INTO users (user_id, name, email, password_hash, role, status, last_login, created_at, student_id) VALUES
(1, 'Administrator', 'admin@campusly.com', '$2y$12$nah.j6ixJ8ZOl33C.6kyouGO3kDb5n.KK.zOJDtTFnslMpmKjImbq', 'admin', 'Active', NOW(), '2026-10-14 23:53:24', NULL),
(2, 'Jay Navadiya', 'jay@campusly.com', '$2y$12$0YY8yE51ZMgUQlbHx8h.AeqEnzcjke5n7E.VAdlKwV8RwPbImdbVO', 'student', 'Active', NOW(), '2026-10-14 23:53:24', 1),
(3, 'Jash Patel', 'student@campusly.com', '$2y$12$0YY8yE51ZMgUQlbHx8h.AeqEnzcjke5n7E.VAdlKwV8RwPbImdbVO', 'student', 'Active', '2026-10-14 23:53:24', '2026-10-14 23:53:24', 2),
(4, 'Bhavy Gol', 'bhavy@campusly.com', '$2y$12$0YY8yE51ZMgUQlbHx8h.AeqEnzcjke5n7E.VAdlKwV8RwPbImdbVO', 'student', 'Active', '2026-10-14 23:53:24', '2026-10-14 23:53:24', NULL);

-- Audit Logs
INSERT INTO audit_logs (user_id, user_email, user_role, action, details, ip_address, created_at) VALUES
(1, 'admin@campusly.com', 'admin', 'LOGIN_SUCCESS', 'Administrator authenticated successfully via secure password_verify()', '127.0.0.1', '2026-10-21 09:30:12'),
(1, 'admin@campusly.com', 'admin', 'USER_STATUS_TOGGLE', 'Toggled user status for Account #3 (jash@campusly.com) to Active', '127.0.0.1', '2026-10-21 10:14:05'),
(1, 'admin@campusly.com', 'admin', 'EVENT_CREATE', 'Published new campus event: AI Workshop (Venue: Auditorium)', '127.0.0.1', '2026-10-21 11:02:40'),
(2, 'jay@campusly.com', 'student', 'UNAUTHORIZED_ACCESS_403', 'Access Violation: Student attempted to access restricted Admin Dashboard /dashboard.php (HTTP 403 Blocked)', '127.0.0.1', '2026-10-21 12:45:18'),
(1, 'admin@campusly.com', 'admin', 'USER_ROLE_CHANGE', 'Promoted user #4 permissions to verified Student', '127.0.0.1', '2026-10-21 13:10:22'),
(1, 'admin@campusly.com', 'admin', 'PASSWORD_RESET', 'Initiated password reset for user #2 (temporary credentials issued)', '127.0.0.1', '2026-10-21 13:35:44'),
(1, 'admin@campusly.com', 'admin', 'EVENT_CREATE', 'Published new campus event: Code Wizards (Venue: CSPIT)', '127.0.0.1', '2026-10-21 14:05:10'),
(1, 'admin@campusly.com', 'admin', 'LOGIN_SUCCESS', 'Admin session refreshed with session_regenerate_id()', '127.0.0.1', '2026-10-21 14:30:00');
