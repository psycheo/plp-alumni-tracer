-- Official GPA and OJT on file for alumni (admin-maintained; read-only on prediction form).
ALTER TABLE `users`
  ADD COLUMN `gpa` decimal(3,2) DEFAULT NULL COMMENT 'PLP scale 1.00-5.00 (lower is better)',
  ADD COLUMN `ojt_grade_percent` decimal(5,2) DEFAULT NULL COMMENT 'OJT final 60-100';
