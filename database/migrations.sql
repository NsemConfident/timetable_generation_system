-- Timetable Generation System - Database Schema
-- Run this file to create the database and all tables.

CREATE DATABASE IF NOT EXISTS timetable_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE timetable_db;

-- ---------------------------------------------------------------------------
-- Users & Authentication
-- ---------------------------------------------------------------------------
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role ENUM('admin', 'head_teacher', 'teacher') NOT NULL DEFAULT 'teacher',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_role (role)
) ENGINE=InnoDB;

CREATE TABLE user_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_tokens_token (token),
    INDEX idx_user_tokens_user_id (user_id),
    INDEX idx_user_tokens_expires (expires_at)
) ENGINE=INNODB;

-- ---------------------------------------------------------------------------
-- Academic Structure
-- ---------------------------------------------------------------------------
CREATE TABLE academic_years (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_academic_years_active (is_active)
) ENGINE=INNODB;

CREATE TABLE terms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academic_year_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
    INDEX idx_terms_academic_year (academic_year_id)
) ENGINE=INNODB;

CREATE TABLE classes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academic_year_id INT UNSIGNED NOT NULL,
    term_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
    FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE CASCADE,
    INDEX idx_classes_academic_year (academic_year_id),
    INDEX idx_classes_term (term_id)
) ENGINE=INNODB;

-- ---------------------------------------------------------------------------
-- Teachers
-- ---------------------------------------------------------------------------
CREATE TABLE teachers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_teachers_user_id (user_id)
) ENGINE=INNODB;

-- ---------------------------------------------------------------------------
-- Teacher subjects (many-to-many)
-- ---------------------------------------------------------------------------
CREATE TABLE subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_subjects_code (code)
) ENGINE=INNODB;

CREATE TABLE teacher_subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY uk_teacher_subject (teacher_id, subject_id),
    INDEX idx_teacher_subjects_teacher (teacher_id),
    INDEX idx_teacher_subjects_subject (subject_id)
) ENGINE=INNODB;

-- ---------------------------------------------------------------------------
-- Teacher availability (when teacher is free)
-- ---------------------------------------------------------------------------
CREATE TABLE school_days (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL,
    day_order TINYINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_school_days_order (day_order)
) ENGINE=INNODB;

CREATE TABLE time_slots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_order TINYINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_time_slots_order (slot_order)
) ENGINE=INNODB;

CREATE TABLE teacher_availability (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    school_day_id INT UNSIGNED NOT NULL,
    time_slot_id INT UNSIGNED NOT NULL,
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (school_day_id) REFERENCES school_days(id) ON DELETE CASCADE,
    FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE CASCADE,
    UNIQUE KEY uk_teacher_day_slot (teacher_id, school_day_id, time_slot_id),
    INDEX idx_teacher_availability_teacher (teacher_id)
) ENGINE=INNODB;

-- ---------------------------------------------------------------------------
-- Rooms
-- ---------------------------------------------------------------------------
CREATE TABLE rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    capacity INT UNSIGNED NOT NULL DEFAULT 0,
    type VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rooms_type (type)
) ENGINE=INNODB;

-- ---------------------------------------------------------------------------
-- Break periods (optional - slots that are breaks)
-- ---------------------------------------------------------------------------
CREATE TABLE break_periods (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    time_slot_id INT UNSIGNED NOT NULL,
    school_day_id INT UNSIGNED NULL,
    name VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE CASCADE,
    FOREIGN KEY (school_day_id) REFERENCES school_days(id) ON DELETE SET NULL,
    INDEX idx_break_periods_slot (time_slot_id)
) ENGINE=INNODB;

-- ---------------------------------------------------------------------------
-- Class-Subject allocations (what to teach, to which class, how many periods)
-- ---------------------------------------------------------------------------
CREATE TABLE class_subject_allocations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    periods_per_week INT UNSIGNED NOT NULL DEFAULT 1,
    academic_year_id INT UNSIGNED NOT NULL,
    term_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
    FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE CASCADE,
    INDEX idx_allocations_class (class_id),
    INDEX idx_allocations_teacher (teacher_id),
    INDEX idx_allocations_term (term_id)
) ENGINE=INNODB;

-- ---------------------------------------------------------------------------
-- Generated timetable entries
-- ---------------------------------------------------------------------------
CREATE TABLE timetable_entries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NULL,
    school_day_id INT UNSIGNED NOT NULL,
    time_slot_id INT UNSIGNED NOT NULL,
    academic_year_id INT UNSIGNED NOT NULL,
    term_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    FOREIGN KEY (school_day_id) REFERENCES school_days(id) ON DELETE CASCADE,
    FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
    FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE CASCADE,
    UNIQUE KEY uk_class_day_slot_term (class_id, school_day_id, time_slot_id, term_id),
    INDEX idx_timetable_class (class_id),
    INDEX idx_timetable_teacher (teacher_id),
    INDEX idx_timetable_room (room_id),
    INDEX idx_timetable_term (term_id),
    INDEX idx_timetable_day_slot (school_day_id, time_slot_id)
) ENGINE=INNODB;

-- ---------------------------------------------------------------------------
-- Seed default admin user (password: Admin@123)
-- ---------------------------------------------------------------------------
-- Default admin: admin@school.local / Admin@123
INSERT INTO users (email, password_hash, name, role) VALUES
('admin@school.local', '$2y$10$MM7Y7J768DmmGucdu9QOnu36MgYD.k36z/8JXDLR24h1tKoBcdzd2', 'System Admin', 'admin');

-- Seed sample school days (Mon–Fri)
INSERT INTO school_days (name, day_order) VALUES
('Monday', 1), ('Tuesday', 2), ('Wednesday', 3), ('Thursday', 4), ('Friday', 5);

-- ---------------------------------------------------------------------------
-- Assessment (CA/Exam) Timetable
-- ---------------------------------------------------------------------------
CREATE TABLE assessment_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    type ENUM('ca', 'exam') NOT NULL,
    term_id INT UNSIGNED NOT NULL,
    academic_year_id INT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    default_duration_minutes INT UNSIGNED NOT NULL DEFAULT 60,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
    INDEX idx_assessment_sessions_term (term_id),
    INDEX idx_assessment_sessions_type (type)
) ENGINE=InnoDB;

CREATE TABLE assessment_subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_session_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    duration_minutes INT UNSIGNED NULL,
    supervisor_teacher_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assessment_session_id) REFERENCES assessment_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (supervisor_teacher_id) REFERENCES teachers(id) ON DELETE SET NULL,
    UNIQUE KEY uk_session_class_subject (assessment_session_id, class_id, subject_id),
    INDEX idx_assessment_subjects_session (assessment_session_id),
    INDEX idx_assessment_subjects_class (class_id)
) ENGINE=InnoDB;

CREATE TABLE assessment_timetable (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_session_id INT UNSIGNED NOT NULL,
    assessment_subject_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NULL,
    school_day_id INT UNSIGNED NOT NULL,
    time_slot_id INT UNSIGNED NOT NULL,
    supervisor_teacher_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assessment_session_id) REFERENCES assessment_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (assessment_subject_id) REFERENCES assessment_subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    FOREIGN KEY (school_day_id) REFERENCES school_days(id) ON DELETE CASCADE,
    FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE CASCADE,
    FOREIGN KEY (supervisor_teacher_id) REFERENCES teachers(id) ON DELETE SET NULL,
    UNIQUE KEY uk_assessment_subject_slot (assessment_subject_id, school_day_id, time_slot_id),
    INDEX idx_assessment_timetable_session (assessment_session_id),
    INDEX idx_assessment_timetable_room (room_id),
    INDEX idx_assessment_timetable_day_slot (school_day_id, time_slot_id)
) ENGINE=InnoDB;
