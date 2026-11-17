CREATE DATABASE IF NOT EXISTS specialization_dss
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE specialization_dss;

-- 1. ADMINS TABLE
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. STUDENTS TABLE
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30),
    academic_year VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. SPECIALIZATIONS TABLE
CREATE TABLE IF NOT EXISTS specializations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. CRITERIA TABLE
CREATE TABLE IF NOT EXISTS criteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    criterion_type ENUM('maximize', 'minimize') NOT NULL,
    weight DECIMAL(4,2) NOT NULL,
    description TEXT
);

-- 5. EVALUATIONS TABLE
CREATE TABLE IF NOT EXISTS evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    specialization_id INT NOT NULL,
    criterion_id INT NOT NULL,
    score DECIMAL(4,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_evaluation
    (student_id, specialization_id, criterion_id),

    FOREIGN KEY (student_id)
        REFERENCES students(id)
        ON DELETE CASCADE,

    FOREIGN KEY (specialization_id)
        REFERENCES specializations(id)
        ON DELETE CASCADE,

    FOREIGN KEY (criterion_id)
        REFERENCES criteria(id)
        ON DELETE CASCADE
);

INSERT IGNORE INTO specializations
(code, name, description)
VALUES
('P1', 'Artificial Intelligence',
 'Study of intelligent systems and machine learning'),

('P2', 'Cybersecurity',
 'Protection of systems, networks and data'),

('P3', 'Data Science',
 'Analysis and interpretation of data'),

('P4', 'Information Systems and Software Engineering',
 'Design and development of software and information systems');

INSERT IGNORE INTO criteria
(code, name, criterion_type, weight, description)
VALUES
('PROGRAMMING', 'Programming',
 'maximize', 0.20, 'Programming skills'),

('MATHEMATICS', 'Mathematics',
 'maximize', 0.15, 'Mathematical skills'),

('ALIGNMENT', 'Alignment with the student''s competencies',
 'maximize', 0.20, 'Compatibility with student competencies'),

('CAREER', 'Career opportunities',
 'maximize', 0.20, 'Professional opportunities'),

('DIFFICULTY', 'Difficulty',
 'minimize', 0.10, 'Level of specialization difficulty'),

('INTEREST', 'Personal interest',
 'maximize', 0.15, 'Student personal interest');
