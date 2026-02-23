-- ---------------------------------------------------------------------------
-- Assessment (CA/Exam) Timetable - Incremental migration
-- Run this after the main migrations.sql if the DB already exists.
-- ---------------------------------------------------------------------------
USE timetable_db;

-- Assessment sessions: CA or Exam period (name, type, term, dates)
CREATE TABLE IF NOT EXISTS assessment_sessions (
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

-- Subjects to assess in a session: which class sits which subject (optional duration, optional supervisor)
CREATE TABLE IF NOT EXISTS assessment_subjects (
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

-- Generated assessment timetable: one row = one scheduled exam/CA slot
CREATE TABLE IF NOT EXISTS assessment_timetable (
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
