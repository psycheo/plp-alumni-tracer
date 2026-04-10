-- Extended official academic profile (admin-maintained; read-only for alumni).
-- Run after migration_users_academic.sql. Skip any line that errors if column already exists.

ALTER TABLE `users`
  ADD COLUMN `program_id` int(11) DEFAULT NULL COMMENT 'Degree/program on file',
  ADD COLUMN `avg_professional_grade` decimal(5,2) DEFAULT NULL COMMENT 'Coursework avg 0-100',
  ADD COLUMN `avg_elective_grade` decimal(5,2) DEFAULT NULL COMMENT 'Coursework avg 0-100',
  ADD COLUMN `record_soft_skills_avg` decimal(5,2) DEFAULT NULL COMMENT 'Official soft skills avg 0-100',
  ADD COLUMN `record_hard_skills_avg` decimal(5,2) DEFAULT NULL COMMENT 'Official hard skills avg 0-100';

ALTER TABLE `users`
  ADD KEY `idx_users_program` (`program_id`);

-- Optional: enforce program reference (uncomment if your DB is clean)
-- ALTER TABLE `users`
--   ADD CONSTRAINT `fk_users_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
