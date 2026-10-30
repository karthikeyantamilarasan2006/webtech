-- =====================================================================
-- DATABASE: student_feedback_system
-- PROJECT: Student Feedback and Course Evaluation System
-- CSE Mini-Project Academic Schema & Realistic Sample Data
-- =====================================================================

-- ---------------------------------------------------------------------
-- DATABASE SPECIFICATION
-- If importing via InfinityFree / cPanel phpMyAdmin, open your existing database first.
-- For manual command line: CREATE DATABASE IF NOT EXISTS `student_feedback_system`; USE `student_feedback_system`;
-- ---------------------------------------------------------------------

-- Disable foreign key checks during schema re-creation
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `feedback`;
DROP TABLE IF EXISTS `feedback_submissions`;
DROP TABLE IF EXISTS `course_faculty`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `faculty`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `admins`;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- TABLE 1: departments
-- Stores academic departments in the institution
-- ---------------------------------------------------------------------
CREATE TABLE `departments` (
  `department_id` INT AUTO_INCREMENT PRIMARY KEY,
  `department_code` VARCHAR(20) NOT NULL UNIQUE,
  `department_name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 2: courses
-- Stores subjects/courses offered by departments across semesters
-- ---------------------------------------------------------------------
CREATE TABLE `courses` (
  `course_id` INT AUTO_INCREMENT PRIMARY KEY,
  `course_code` VARCHAR(20) NOT NULL UNIQUE,
  `course_name` VARCHAR(150) NOT NULL,
  `department_id` INT NOT NULL,
  `semester` TINYINT NOT NULL CHECK (`semester` BETWEEN 1 AND 8),
  `academic_year` VARCHAR(20) NOT NULL DEFAULT '2025-2026',
  `credits` TINYINT NOT NULL DEFAULT 3,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_courses_department` FOREIGN KEY (`department_id`)
    REFERENCES `departments` (`department_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 3: faculty
-- Stores teaching faculty members and their profiles
-- ---------------------------------------------------------------------
CREATE TABLE `faculty` (
  `faculty_id` INT AUTO_INCREMENT PRIMARY KEY,
  `faculty_code` VARCHAR(20) NOT NULL UNIQUE,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(20) DEFAULT NULL,
  `department_id` INT NOT NULL,
  `designation` VARCHAR(50) NOT NULL,
  `qualification` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_faculty_department` FOREIGN KEY (`department_id`)
    REFERENCES `departments` (`department_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 4: course_faculty (Mapping)
-- Maps which faculty member is assigned to teach which course
-- ---------------------------------------------------------------------
CREATE TABLE `course_faculty` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT NOT NULL,
  `faculty_id` INT NOT NULL,
  `academic_year` VARCHAR(20) NOT NULL DEFAULT '2025-2026',
  `semester` TINYINT NOT NULL,
  CONSTRAINT `fk_cf_course` FOREIGN KEY (`course_id`)
    REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cf_faculty` FOREIGN KEY (`faculty_id`)
    REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY `unique_course_faculty_term` (`course_id`, `faculty_id`, `academic_year`, `semester`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 5: students
-- Stores registered students with credentials and academic info
-- ---------------------------------------------------------------------
CREATE TABLE `students` (
  `student_id` INT AUTO_INCREMENT PRIMARY KEY,
  `roll_number` VARCHAR(30) NOT NULL UNIQUE,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `department_id` INT NOT NULL,
  `academic_year` VARCHAR(20) NOT NULL DEFAULT '2025-2026',
  `semester` TINYINT NOT NULL CHECK (`semester` BETWEEN 1 AND 8),
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_students_department` FOREIGN KEY (`department_id`)
    REFERENCES `departments` (`department_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 6: admins
-- Stores administrative personnel with management privileges
-- ---------------------------------------------------------------------
CREATE TABLE `admins` (
  `admin_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('superadmin', 'coordinator') NOT NULL DEFAULT 'superadmin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 7: feedback_submissions (Tracking & Duplicate Guard)
-- Records student submission receipts to prevent duplicate submissions
-- ---------------------------------------------------------------------
CREATE TABLE `feedback_submissions` (
  `submission_id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `course_id` INT NOT NULL,
  `faculty_id` INT NOT NULL,
  `academic_year` VARCHAR(20) NOT NULL,
  `semester` TINYINT NOT NULL,
  `is_anonymous` TINYINT(1) NOT NULL DEFAULT 0,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_fbs_student` FOREIGN KEY (`student_id`)
    REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fbs_course` FOREIGN KEY (`course_id`)
    REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fbs_faculty` FOREIGN KEY (`faculty_id`)
    REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY `unique_student_evaluation` (`student_id`, `course_id`, `faculty_id`, `academic_year`, `semester`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLE 8: feedback (Evaluation Records)
-- Stores evaluation criteria scores (1-5), calculated average & comments
-- If is_anonymous = 1, student_id is set to NULL to protect anonymity
-- ---------------------------------------------------------------------
CREATE TABLE `feedback` (
  `feedback_id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT DEFAULT NULL,
  `course_id` INT NOT NULL,
  `faculty_id` INT NOT NULL,
  `department_id` INT NOT NULL,
  `academic_year` VARCHAR(20) NOT NULL,
  `semester` TINYINT NOT NULL,
  `is_anonymous` TINYINT(1) NOT NULL DEFAULT 0,
  `teaching_effectiveness` TINYINT NOT NULL CHECK (`teaching_effectiveness` BETWEEN 1 AND 5),
  `subject_knowledge` TINYINT NOT NULL CHECK (`subject_knowledge` BETWEEN 1 AND 5),
  `communication_skills` TINYINT NOT NULL CHECK (`communication_skills` BETWEEN 1 AND 5),
  `course_content_quality` TINYINT NOT NULL CHECK (`course_content_quality` BETWEEN 1 AND 5),
  `classroom_interaction` TINYINT NOT NULL CHECK (`classroom_interaction` BETWEEN 1 AND 5),
  `doubt_clarification` TINYINT NOT NULL CHECK (`doubt_clarification` BETWEEN 1 AND 5),
  `overall_satisfaction` TINYINT NOT NULL CHECK (`overall_satisfaction` BETWEEN 1 AND 5),
  `average_rating` DECIMAL(3, 2) NOT NULL,
  `comments` TEXT DEFAULT NULL,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_fb_student` FOREIGN KEY (`student_id`)
    REFERENCES `students` (`student_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fb_course` FOREIGN KEY (`course_id`)
    REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fb_faculty` FOREIGN KEY (`faculty_id`)
    REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fb_department` FOREIGN KEY (`department_id`)
    REFERENCES `departments` (`department_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SEED SAMPLE DATA
-- =====================================================================

-- 1. Departments
INSERT INTO `departments` (`department_id`, `department_code`, `department_name`) VALUES
(1, 'CSE', 'Computer Science and Engineering'),
(2, 'ECE', 'Electronics and Communication Engineering'),
(3, 'IT', 'Information Technology'),
(4, 'MECH', 'Mechanical Engineering');

-- 2. Courses
INSERT INTO `courses` (`course_id`, `course_code`, `course_name`, `department_id`, `semester`, `academic_year`, `credits`) VALUES
(1, 'CS501', 'Database Management Systems', 1, 5, '2025-2026', 4),
(2, 'CS502', 'Web Technologies', 1, 5, '2025-2026', 3),
(3, 'CS503', 'Operating Systems', 1, 5, '2025-2026', 4),
(4, 'CS504', 'Computer Networks', 1, 5, '2025-2026', 3),
(5, 'EC501', 'Digital Signal Processing', 2, 5, '2025-2026', 4),
(6, 'IT501', 'Cloud Computing Architecture', 3, 5, '2025-2026', 3);

-- 3. Faculty
INSERT INTO `faculty` (`faculty_id`, `faculty_code`, `full_name`, `email`, `phone`, `department_id`, `designation`, `qualification`) VALUES
(1, 'FAC101', 'Dr. Alan Turing', 'alan.turing@college.edu', '9876543210', 1, 'Professor', 'Ph.D. in Computer Science'),
(2, 'FAC102', 'Dr. Ada Lovelace', 'ada.lovelace@college.edu', '9876543211', 1, 'Associate Professor', 'Ph.D. in Software Engineering'),
(3, 'FAC103', 'Prof. Grace Hopper', 'grace.hopper@college.edu', '9876543212', 1, 'Assistant Professor', 'M.Tech in Computer Science'),
(4, 'FAC104', 'Dr. Claude Shannon', 'claude.shannon@college.edu', '9876543213', 2, 'Professor', 'Ph.D. in Information Theory'),
(5, 'FAC105', 'Prof. John von Neumann', 'john.vonneumann@college.edu', '9876543214', 1, 'Professor', 'Ph.D. in Applied Mathematics');

-- 4. Course Faculty Assignment
INSERT INTO `course_faculty` (`course_id`, `faculty_id`, `academic_year`, `semester`) VALUES
(1, 1, '2025-2026', 5), -- Dr. Alan Turing -> DBMS
(2, 2, '2025-2026', 5), -- Dr. Ada Lovelace -> Web Technologies
(3, 3, '2025-2026', 5), -- Prof. Grace Hopper -> Operating Systems
(4, 5, '2025-2026', 5), -- Prof. John von Neumann -> Computer Networks
(5, 4, '2025-2026', 5); -- Dr. Claude Shannon -> DSP

-- 5. Seed Admin Account
-- Default Username: admin
-- Default Password: Admin@123
INSERT INTO `admins` (`admin_id`, `username`, `full_name`, `email`, `password`, `role`) VALUES
(1, 'admin', 'System Administrator', 'admin@college.edu', '$2y$10$T1KqLz14ZfDqWc.q1zF5uOBgL25s4a7n2vE9rF6a7uV4a7m8z6zQ6', 'superadmin');

-- 6. Seed Student Accounts
-- Default Password: Student@123
INSERT INTO `students` (`student_id`, `roll_number`, `full_name`, `email`, `password`, `department_id`, `academic_year`, `semester`, `status`) VALUES
(1, '2023CSE001', 'Rahul Sharma', 'rahul.cse@college.edu', '$2y$10$T1KqLz14ZfDqWc.q1zF5uOBgL25s4a7n2vE9rF6a7uV4a7m8z6zQ6', 1, '2025-2026', 5, 'active'),
(2, '2023CSE002', 'Priya Patel', 'priya.cse@college.edu', '$2y$10$T1KqLz14ZfDqWc.q1zF5uOBgL25s4a7n2vE9rF6a7uV4a7m8z6zQ6', 1, '2025-2026', 5, 'active'),
(3, '2023CSE003', 'Amit Kumar', 'amit.cse@college.edu', '$2y$10$T1KqLz14ZfDqWc.q1zF5uOBgL25s4a7n2vE9rF6a7uV4a7m8z6zQ6', 1, '2025-2026', 5, 'active');

-- 7. Seed Feedback Submissions (Submission Receipts for Duplicate Prevention)
INSERT INTO `feedback_submissions` (`submission_id`, `student_id`, `course_id`, `faculty_id`, `academic_year`, `semester`, `is_anonymous`, `submitted_at`) VALUES
(1, 1, 1, 1, '2025-2026', 5, 0, '2026-10-15 10:30:00'),
(2, 2, 1, 1, '2025-2026', 5, 1, '2026-10-16 11:15:00'),
(3, 3, 2, 2, '2025-2026', 5, 0, '2026-10-17 14:00:00');

-- 8. Seed Feedback Responses (Evaluation Records)
-- Notice that feedback_id=2 has student_id = NULL because is_anonymous = 1!
INSERT INTO `feedback` (`feedback_id`, `student_id`, `course_id`, `faculty_id`, `department_id`, `academic_year`, `semester`, `is_anonymous`, `teaching_effectiveness`, `subject_knowledge`, `communication_skills`, `course_content_quality`, `classroom_interaction`, `doubt_clarification`, `overall_satisfaction`, `average_rating`, `comments`, `submitted_at`) VALUES
(1, 1, 1, 1, 1, '2025-2026', 5, 0, 5, 5, 4, 5, 4, 5, 5, 4.71, 'Dr. Alan Turing explains relational normalization and query execution with exceptional clarity and real-world database examples.', '2026-10-15 10:30:00'),
(2, NULL, 1, 1, 1, '2025-2026', 5, 1, 4, 5, 4, 4, 5, 4, 4, 4.29, 'Excellent depth in database concepts. More hands-on lab worksheets for indexing would be very beneficial.', '2026-10-16 11:15:00'),
(3, 3, 2, 2, 1, '2025-2026', 5, 0, 5, 5, 5, 5, 5, 5, 5, 5.00, 'Dr. Ada Lovelace makes full-stack development enjoyable and practical, focusing on responsive UI and backend security.', '2026-10-17 14:00:00');

