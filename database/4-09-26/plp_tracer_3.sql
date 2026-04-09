-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 09, 2026 at 04:42 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `plp_tracer`
--

-- --------------------------------------------------------

--
-- Table structure for table `alumni_assessments`
--

CREATE TABLE `alumni_assessments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `program_id` int(11) NOT NULL,
  `grad_year` int(11) DEFAULT NULL,
  `employment_status` varchar(50) DEFAULT 'Not Employed',
  `current_company` varchar(255) DEFAULT NULL,
  `current_position` varchar(255) DEFAULT NULL,
  `current_salary` varchar(100) DEFAULT NULL,
  `years_experience` int(11) DEFAULT NULL,
  `gpa` decimal(3,2) NOT NULL,
  `ojt_grade` decimal(5,2) NOT NULL,
  `soft_skills_avg` decimal(5,2) NOT NULL,
  `hard_skills_avg` decimal(5,2) NOT NULL,
  `cv_filename` varchar(255) DEFAULT NULL,
  `employability_status` varchar(50) NOT NULL,
  `recommended_profession` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alumni_assessments`
--

INSERT INTO `alumni_assessments` (`id`, `name`, `program_id`, `grad_year`, `employment_status`, `current_company`, `current_position`, `current_salary`, `years_experience`, `gpa`, `ojt_grade`, `soft_skills_avg`, `hard_skills_avg`, `cv_filename`, `employability_status`, `recommended_profession`, `created_at`, `user_id`) VALUES
(1, 'Juan Dela Cruz', 1, 2022, 'Not Employed', '', '', '', 0, 1.51, 1.25, 3.50, 3.00, '', 'Good Match', 'Network Administrator', '2026-02-19 16:15:42', NULL),
(2, 'Juan Cruz', 2, 2022, 'Employed', 'ABC Company', 'Software Engineer', 'Below 20k', 2, 1.05, 2.25, 0.00, 0.00, '', 'Good Match', 'Continue growing as a Software Engineer', '2026-02-20 05:48:17', NULL),
(3, 'Rex Navarro Jr.', 1, 2026, 'Unemployed', '', '', '', 0, 1.00, 1.00, 3.00, 3.00, '', 'Job Mismatch', 'Software Engineer', '2026-02-25 01:03:13', NULL),
(4, 'Rex Navarro Iii', 3, 2021, 'Unemployed', '', '', '', 0, 2.50, 1.25, 1.00, 1.00, '', 'Job Mismatch', 'General Corporate Roles', '2026-02-25 01:42:18', NULL),
(5, 'Jhun Alvarez', 1, 2004, 'Unemployed', '', '', '', 0, 1.00, 1.25, 3.00, 3.00, '', 'Job Mismatch', 'Network Administrator', '2026-02-25 02:13:22', NULL),
(6, 'Kelly Lola', 1, 2026, 'Employed', 'Abc Company', 'It Practitioner', '20k-40k', 2, 1.50, 1.25, 0.00, 0.00, '', 'Good Match', 'Continue growing as a It Practitioner', '2026-02-25 06:05:14', NULL),
(7, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 1.51, 1.25, 4.00, 2.00, '', 'Good Match', 'Software Engineer', '2026-03-08 10:04:29', NULL),
(8, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 2.50, 1.50, 1.00, 1.00, '', 'Job Mismatch', 'Software Engineer', '2026-03-08 10:05:24', NULL),
(9, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 3.00, 1.49, 1.00, 1.00, '', 'Job Mismatch', 'Network Administrator', '2026-03-08 10:10:12', NULL),
(10, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 1.00, 1.00, 5.00, 5.00, '', 'Good Match', 'Software Engineer', '2026-03-08 10:16:02', NULL),
(11, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Good Match', 'Network Administrator', '2026-03-08 10:28:43', NULL),
(12, 'Juan Cruz', 2, 2025, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Good Match', 'Data Analyst', '2026-03-08 10:38:36', NULL),
(13, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Good Match', 'Network Administrator', '2026-03-08 11:13:02', NULL),
(14, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 5.00, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Network Administrator', '2026-03-08 11:13:32', NULL),
(15, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Network Administrator', '2026-03-08 11:13:57', NULL),
(16, 'Juan Cruz', 2, 2025, 'Unemployed', '', '', '', 0, 5.00, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Data Analyst', '2026-03-08 11:55:59', NULL),
(17, 'Juan Cruz', 2, 2025, 'Unemployed', '', '', '', 0, 5.00, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Data Analyst', '2026-03-08 11:56:02', NULL),
(18, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Good Match', 'Network Administrator', '2026-03-08 12:31:35', NULL),
(19, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Good Match', 'Software Engineer', '2026-03-08 12:42:16', NULL),
(20, 'Juan Cruz', 2, 2025, 'Unemployed', '', '', '', 0, 5.00, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Data Analyst', '2026-03-08 12:43:01', NULL),
(21, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 5.00, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Network Administrator', '2026-03-08 12:43:27', NULL),
(22, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Good Match', 'Software Engineer', '2026-03-08 13:09:34', NULL),
(23, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 5.00, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Network Administrator', '2026-03-08 13:10:21', NULL),
(24, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 1.50, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Network Administrator', '2026-03-08 13:10:59', NULL),
(25, 'Juan Cruz', 2, 2016, 'Unemployed', '', '', '', 0, 1.60, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Data Analyst', '2026-03-08 13:18:49', NULL),
(26, 'Ron', 1, 2024, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Network Administrator', '2026-03-18 00:28:33', NULL),
(27, 'Ron Michael', 1, 2025, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Good Match', 'Software Engineer', '2026-03-18 00:41:40', NULL),
(28, 'Ron', 2, 2014, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Good Match', 'Data Analyst', '2026-03-18 00:50:38', NULL),
(29, 'Hanzel', 1, 2020, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Good Match', 'Network Administrator', '2026-03-18 01:14:52', NULL),
(30, 'Aj', 1, 2024, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Good Match', 'Network Administrator', '2026-03-18 01:15:49', NULL),
(31, 'Ron', 2, 2023, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Good Match', 'Data Analyst', '2026-03-18 01:21:09', NULL),
(32, 'Lans', 1, 2022, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Good Match', 'Software Engineer', '2026-03-18 03:42:43', NULL),
(33, 'Mav', 2, 2020, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Data Analyst', '2026-03-18 03:43:25', NULL),
(34, 'Juan', 1, 2025, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Good Match', 'Software Engineer', '2026-03-18 05:12:24', NULL),
(35, 'Juan', 2, 2024, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Data Analyst', '2026-03-18 05:13:31', NULL),
(36, 'Juan', 1, 2025, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Network Administrator', '2026-03-18 05:21:15', NULL),
(37, 'Juan', 1, 2024, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Good Match', 'Network Administrator', '2026-03-18 05:31:07', NULL),
(38, 'Sample Alumni', 1, 2025, 'Unemployed', '', '', '', 0, 1.00, 92.00, 100.00, 62.86, '', 'Good Match', 'Software Engineer', '2026-04-05 13:48:12', NULL),
(39, 'Juan', 1, 2025, 'Unemployed', '', '', '', 0, 1.00, 90.00, 100.00, 55.43, '', 'Good Match', 'Network Administrator', '2026-04-09 12:32:57', NULL),
(40, 'Kelly', 1, 2019, 'Unemployed', '', '', '', 0, 1.00, 90.00, 100.00, 72.14, '', 'Good Match', 'Software Engineer', '2026-04-09 13:14:56', NULL),
(41, 'Juan', 2, 2021, 'Unemployed', '', '', '', 0, 1.00, 90.00, 100.00, 74.00, '', 'Good Match', 'Data Analyst', '2026-04-09 13:44:02', NULL),
(42, 'Juan Dela Cruz', 1, 2023, 'Unemployed', '', '', '', 0, 1.00, 90.00, 100.00, 81.43, '', 'Good Match', 'Software Engineer', '2026-04-09 13:51:42', NULL),
(43, 'Sample Alumni', 1, 2023, 'Unemployed', '', '', '', 0, 1.00, 90.00, 100.00, 79.57, '', 'Good Match', 'Network Administrator', '2026-04-09 14:02:34', 1),
(44, 'Sample Alumni', 1, 2024, 'Unemployed', '', '', '', 0, 1.00, 90.00, 100.00, 83.29, '', 'Good Match', 'Network Administrator', '2026-04-09 14:10:40', 1),
(45, 'Sample Alumni', 1, 2022, 'Unemployed', '', '', '', 0, 1.00, 90.00, 100.00, 54.86, '', 'Good Match', 'Cybersecurity Analyst', '2026-04-09 14:21:20', 1);

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) DEFAULT 'Unresolved',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedbacks`
--

INSERT INTO `feedbacks` (`id`, `user_id`, `rating`, `message`, `status`, `created_at`) VALUES
(1, 1, 5, 'Testing Feedback, March 08, 2026', 'Resolved', '2026-03-08 09:08:40'),
(2, 1, 4, 'Feedback ko Lans', 'Unresolved', '2026-03-16 06:55:21'),
(3, 1, 5, 'Lans Gumagana Feedback ko', 'Unresolved', '2026-03-18 03:55:11'),
(4, 4, 5, 'Lans 26 ka na', 'Unresolved', '2026-03-18 03:56:00'),
(5, 5, 3, 'panget ng ui! palitan niyo yan', 'Resolved', '2026-03-18 05:38:06'),
(6, 1, 5, 'feedback test', 'Unresolved', '2026-04-09 13:15:42'),
(7, 1, 5, 'Feedback Test 2', 'Unresolved', '2026-04-09 13:24:12'),
(8, 1, 5, 'Fill', 'Unresolved', '2026-04-09 13:24:37'),
(9, 1, 5, '1', 'Unresolved', '2026-04-09 13:36:46');

-- --------------------------------------------------------

--
-- Table structure for table `feedback_replies`
--

CREATE TABLE `feedback_replies` (
  `id` int(11) NOT NULL,
  `feedback_id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL,
  `reply_text` text NOT NULL,
  `is_seen` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `professions`
--

CREATE TABLE `professions` (
  `id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `avg_salary` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `professions`
--

INSERT INTO `professions` (`id`, `program_id`, `title`, `avg_salary`, `description`) VALUES
(1, 1, 'Software Engineer', '₱45,000 - ₱90,000/mo', 'Develops and maintains software applications, systems, and network networks.'),
(2, 1, 'Network Administrator', '₱35,000 - ₱70,000/mo', 'Manages and maintains an organization\'s computer networks.'),
(3, 2, 'Data Analyst', '₱40,000 - ₱85,000/mo', 'Interprets data and turns it into information which can offer ways to improve a business.'),
(4, 7, 'Registered Nurse', '₱30,000 - ₱60,000/mo', 'Provides and coordinates patient care, educates patients about various health conditions.');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `college` varchar(255) NOT NULL,
  `graduates` int(11) NOT NULL,
  `employment_rate` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `name`, `college`, `graduates`, `employment_rate`) VALUES
(1, 'Information Technology', 'College of Computer Studies', 420, 92),
(2, 'Computer Science', 'College of Computer Studies', 380, 94),
(3, 'Accountancy', 'College of Business and Accountancy', 450, 89),
(4, 'Business Administration Major in Marketing', 'College of Business and Accountancy', 520, 88),
(5, 'Entrepreneurship', 'College of Business and Accountancy', 280, 82),
(6, 'Hospitality Management', 'College of International Hospitality Management', 350, 85),
(7, 'Nursing', 'College of Nursing', 480, 96),
(8, 'Electronics & Communications Engineering', 'College of Engineering', 280, 90),
(9, 'Secondary Education (English)', 'College of Education', 320, 87),
(10, 'Secondary Education (Mathematics)', 'College of Education', 260, 86),
(11, 'Secondary Education (Filipino)', 'College of Education', 280, 86),
(12, 'Elementary Education', 'College of Education', 420, 85);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'alumni',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_temporary` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `student_id`, `full_name`, `email`, `password`, `role`, `created_at`, `is_temporary`) VALUES
(1, '23-00186', 'Sample Alumni', 'alumni@example.com', 'alumni123', 'alumni', '2026-02-19 16:15:42', 0),
(2, '00-ADMIN', 'System Administrator', 'admin@plpasig.edu.ph', 'admin123', 'admin', '2026-02-19 16:15:42', 0),
(4, '23-00159', 'Lans Adrian Alonzo', 'alonzo_lansadrian@plpasig.edu.ph', 'alonzo123', 'alumni', '2026-03-18 03:51:49', 0),
(5, '23-00197', 'Rex ', 'navarro@plpasig.edu.ph', '123', 'alumni', '2026-03-18 05:35:51', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alumni_assessments`
--
ALTER TABLE `alumni_assessments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback_replies`
--
ALTER TABLE `feedback_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `feedback_id` (`feedback_id`),
  ADD KEY `alumni_id` (`alumni_id`);

--
-- Indexes for table `professions`
--
ALTER TABLE `professions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alumni_assessments`
--
ALTER TABLE `alumni_assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `feedback_replies`
--
ALTER TABLE `feedback_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `professions`
--
ALTER TABLE `professions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `feedback_replies`
--
ALTER TABLE `feedback_replies`
  ADD CONSTRAINT `feedback_replies_ibfk_1` FOREIGN KEY (`alumni_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `feedback_replies_ibfk_feedback` FOREIGN KEY (`feedback_id`) REFERENCES `feedbacks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `professions`
--
ALTER TABLE `professions`
  ADD CONSTRAINT `professions_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
