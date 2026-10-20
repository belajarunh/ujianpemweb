USE ujianpemweb2;

CREATE TABLE IF NOT EXISTS students (
    nim VARCHAR(30) NOT NULL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    student_class VARCHAR(20) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_results (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    attempt_id VARCHAR(80) NOT NULL UNIQUE,
    student_nim VARCHAR(30) NOT NULL,
    score TINYINT UNSIGNED NOT NULL,
    total_correct TINYINT UNSIGNED NOT NULL,
    theory_correct TINYINT UNSIGNED NOT NULL,
    practice_correct TINYINT UNSIGNED NOT NULL,
    total_questions TINYINT UNSIGNED NOT NULL,
    unanswered_count TINYINT UNSIGNED NOT NULL,
    completion_time_seconds INT UNSIGNED NOT NULL,
    exam_date DATE NOT NULL,
    details JSON NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_results_ranking (score, completion_time_seconds),
    INDEX idx_results_exam_date (exam_date),
    CONSTRAINT fk_results_student FOREIGN KEY (student_nim)
        REFERENCES students (nim) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_attempts (
    attempt_id CHAR(48) NOT NULL PRIMARY KEY,
    student_nim VARCHAR(30) NOT NULL,
    current_question TINYINT UNSIGNED NOT NULL DEFAULT 1,
    deadline_epoch BIGINT UNSIGNED NOT NULL,
    started_epoch BIGINT UNSIGNED NOT NULL,
    completed_epoch BIGINT UNSIGNED NULL,
    status ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_attempts_status (status),
    CONSTRAINT fk_attempts_student FOREIGN KEY (student_nim)
        REFERENCES students (nim) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_answers (
    attempt_id CHAR(48) NOT NULL,
    question_id TINYINT UNSIGNED NOT NULL,
    student_answer TEXT NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    is_timeout TINYINT(1) NOT NULL DEFAULT 0,
    answered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (attempt_id, question_id),
    CONSTRAINT fk_answers_attempt FOREIGN KEY (attempt_id)
        REFERENCES exam_attempts (attempt_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
