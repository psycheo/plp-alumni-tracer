-- Create users table for alumni and admin authentication
-- Run this SQL in your MariaDB terminal or phpMyAdmin

USE plp_tracer;

-- Create the users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'alumni',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample alumni user
INSERT INTO `users` (`student_id`, `full_name`, `email`, `password`, `role`) VALUES
('23-00186', 'Sample Alumni', 'alumni@example.com', 'alumni123', 'alumni');

-- Insert admin user
INSERT INTO `users` (`student_id`, `full_name`, `email`, `password`, `role`) VALUES
('00-ADMIN', 'System Administrator', 'admin@plpasig.edu.ph', 'admin123', 'admin');

