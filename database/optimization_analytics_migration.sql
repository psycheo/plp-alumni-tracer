-- Optimization + Analytics migration
-- Safe to run multiple times where IF NOT EXISTS is supported.

CREATE TABLE IF NOT EXISTS fact_metric_daily (
    metric_date date NOT NULL,
    metric_name varchar(100) NOT NULL,
    program_id int(11) NOT NULL DEFAULT 0,
    cohort_year int(11) NOT NULL DEFAULT 0,
    value decimal(12,4) NOT NULL DEFAULT 0,
    numerator decimal(12,4) NOT NULL DEFAULT 0,
    denominator decimal(12,4) NOT NULL DEFAULT 0,
    definition_version varchar(32) NOT NULL DEFAULT 'v1',
    run_id varchar(64) NOT NULL,
    computed_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (metric_date, metric_name, program_id, cohort_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS etl_run_log (
    run_id varchar(64) NOT NULL,
    pipeline_name varchar(100) NOT NULL,
    source_watermark varchar(128) DEFAULT NULL,
    row_count_in int(11) NOT NULL DEFAULT 0,
    row_count_out int(11) NOT NULL DEFAULT 0,
    status varchar(20) NOT NULL DEFAULT 'started',
    error text DEFAULT NULL,
    started_at timestamp NOT NULL DEFAULT current_timestamp(),
    ended_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (run_id),
    KEY idx_pipeline_started (pipeline_name, started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Query performance indexes
ALTER TABLE alumni_assessments
    ADD INDEX idx_assess_program_created (program_id, created_at),
    ADD INDEX idx_assess_grad_program_created (grad_year, program_id, created_at),
    ADD INDEX idx_assess_student_created (student_id, created_at);

ALTER TABLE users
    ADD INDEX idx_users_role_created (role, created_at),
    ADD INDEX idx_users_program (program_id);

ALTER TABLE feedbacks
    ADD INDEX idx_feedbacks_status_created (status, created_at),
    ADD INDEX idx_feedbacks_user (user_id);

ALTER TABLE feedback_replies
    ADD INDEX idx_replies_feedback (feedback_id),
    ADD INDEX idx_replies_alumni (alumni_id);

ALTER TABLE audit_logs
    ADD INDEX idx_audit_created (created_at),
    ADD INDEX idx_audit_action_created (action, created_at);

-- Feature flag defaults
INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES
('analytics_v2_enabled', '1'),
('performance_logging_enabled', '1');

