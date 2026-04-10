-- Align alumni_assessments with application code (dashboard, process_prediction.php).
-- Run once per database. If the column already exists, skip this migration.

ALTER TABLE `alumni_assessments`
  ADD COLUMN `user_id` int(11) DEFAULT NULL AFTER `id`,
  ADD KEY `idx_alumni_assessments_user_id` (`user_id`);
