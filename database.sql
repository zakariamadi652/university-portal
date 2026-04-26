-- University Portal Database

DROP DATABASE IF EXISTS gestion_scolarite;
CREATE DATABASE gestion_scolarite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestion_scolarite;

-- ------------------------------------------------------------
-- Table: students
-- ------------------------------------------------------------
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(20) NOT NULL UNIQUE,
    last_name VARCHAR(100) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    email VARCHAR(150) NOT NULL,
    study_level VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: teachers
-- ------------------------------------------------------------
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(100) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    specialty VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: modules
-- ------------------------------------------------------------
CREATE TABLE modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_code VARCHAR(20) NOT NULL UNIQUE,
    module_name VARCHAR(200) NOT NULL,
    coefficient INT NOT NULL DEFAULT 1,
    teacher_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: grades
-- ------------------------------------------------------------
CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    module_id INT NOT NULL,
    grade_value DECIMAL(5,2) NOT NULL CHECK (grade_value >= 0 AND grade_value <= 20),
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    UNIQUE KEY unique_grade (student_id, module_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: users
-- ------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Teacher', 'Student') NOT NULL,
    reference_id INT DEFAULT NULL COMMENT 'Reference to students.id or teachers.id',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Demonstration Data
-- ============================================================

-- Teachers
INSERT INTO teachers (last_name, first_name, email, specialty) VALUES
('Laachemi', 'Mohammed', 'laachemi@univ.dz', 'Web Programming'),
('Benali', 'Fatima', 'benali@univ.dz', 'Databases'),
('Khedim', 'Ahmed', 'khedim@univ.dz', 'Computer Networks');

-- Modules
INSERT INTO modules (module_code, module_name, coefficient, teacher_id) VALUES
('PROGWEB', 'Web Programming', 3, 1),
('BDD', 'Databases', 3, 2),
('RES', 'Computer Networks', 2, 3),
('ALGO', 'Advanced Algorithms', 2, 1);

-- Students
INSERT INTO students (student_number, last_name, first_name, date_of_birth, email, study_level) VALUES
('ETU2025001', 'Boumediene', 'Amine', '2003-05-15', 'amine.b@etu.dz', 'L3 Computer Science'),
('ETU2025002', 'Hamidi', 'Sara', '2002-11-22', 'sara.h@etu.dz', 'L3 Computer Science'),
('ETU2025003', 'Cherif', 'Youssef', '2003-01-08', 'youssef.c@etu.dz', 'L3 Computer Science'),
('ETU2025004', 'Mansouri', 'Amina', '2002-08-30', 'amina.m@etu.dz', 'L3 Computer Science'),
('ETU2025005', 'Tabet', 'Karim', '2003-03-19', 'karim.t@etu.dz', 'L3 Computer Science');

-- Grades
INSERT INTO grades (student_id, module_id, grade_value) VALUES
(1, 1, 15.50), (1, 2, 14.00), (1, 3, 16.00), (1, 4, 13.50),
(2, 1, 17.00), (2, 2, 16.50), (2, 3, 14.50), (2, 4, 15.00),
(3, 1, 12.00), (3, 2, 11.50), (3, 3, 13.00), (3, 4, 10.00),
(4, 1, 18.00), (4, 2, 17.50), (4, 3, 15.00), (4, 4, 16.50),
(5, 1, 10.00), (5, 2, 9.50),  (5, 3, 11.00), (5, 4, 12.00);

-- Users (passwords hashed with password_hash)
-- admin / admin123
-- prof1 / prof123 , prof2 / prof123, prof3 / prof123
-- ETU2025001..ETU2025005 / (student_number as password)
INSERT INTO users (username, password, role, reference_id) VALUES
('admin',      '$2y$10$as2kSCLBxfleNjPFu7.py.ZFjCYK3Czuxl0DnMCPE993.F77hsjdy', 'Admin', NULL),
('prof1',      '$2y$10$cFfTaMTBSM.mnYlyn2zCiOFumIOGm0iBGjETq2/hYh688jsHfExa6', 'Teacher', 1),
('prof2',      '$2y$10$cFfTaMTBSM.mnYlyn2zCiOFumIOGm0iBGjETq2/hYh688jsHfExa6', 'Teacher', 2),
('prof3',      '$2y$10$cFfTaMTBSM.mnYlyn2zCiOFumIOGm0iBGjETq2/hYh688jsHfExa6', 'Teacher', 3),
('ETU2025001', '$2y$10$4B9YT4P9tEQ5XQ/2fQMPZ.2Ci1/62hmZiU/J6EsR.mb/Lkqa1./9y', 'Student', 1),
('ETU2025002', '$2y$10$K66vFuPP5cnr/RMS2521zeU0wmE2SsTDAkJX0WYuTQoJcT8AhsO6u', 'Student', 2),
('ETU2025003', '$2y$10$h58p1m1MKxO1qQmlAl6.O.UHkvheguC/BhqDUSFzHki6BdgoUOeHq', 'Student', 3),
('ETU2025004', '$2y$10$.8rb.nEzPS6nKFpERgmRWOcd764573VfXeiIErh/iRusLvksF3y5S', 'Student', 4),
('ETU2025005', '$2y$10$5nwJQhkEh4m5fRxZ7uMgz.exYsRNKl52MpYF4FKB.vGSsBMsOOrcG', 'Student', 5);
