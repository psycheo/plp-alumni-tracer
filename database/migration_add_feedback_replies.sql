-- Admin replies to alumni feedback (used by admin/process_feedback.php and alumni/dashboard.php)
CREATE TABLE IF NOT EXISTS `feedback_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `feedback_id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL,
  `reply_text` text NOT NULL,
  `is_seen` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `feedback_id` (`feedback_id`),
  KEY `alumni_id` (`alumni_id`),
  CONSTRAINT `feedback_replies_ibfk_feedback` FOREIGN KEY (`feedback_id`) REFERENCES `feedbacks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
