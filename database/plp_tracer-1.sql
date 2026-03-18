-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 16, 2026 at 07:57 AM
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
  `ojt_grade` decimal(3,2) NOT NULL,
  `soft_skills_avg` decimal(3,2) NOT NULL,
  `hard_skills_avg` decimal(3,2) NOT NULL,
  `cv_filename` varchar(255) DEFAULT NULL,
  `employability_status` varchar(50) NOT NULL,
  `recommended_profession` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alumni_assessments`
--

INSERT INTO `alumni_assessments` (`id`, `name`, `program_id`, `grad_year`, `employment_status`, `current_company`, `current_position`, `current_salary`, `years_experience`, `gpa`, `ojt_grade`, `soft_skills_avg`, `hard_skills_avg`, `cv_filename`, `employability_status`, `recommended_profession`, `created_at`) VALUES
(1, 'Juan Dela Cruz', 1, 2022, 'Not Employed', '', '', '', 0, 1.51, 1.25, 3.50, 3.00, '', 'Good Match', 'Network Administrator', '2026-02-19 16:15:42'),
(2, 'Juan Cruz', 2, 2022, 'Employed', 'ABC Company', 'Software Engineer', 'Below 20k', 2, 1.05, 2.25, 0.00, 0.00, '', 'Good Match', 'Continue growing as a Software Engineer', '2026-02-20 05:48:17'),
(3, 'Rex Navarro Jr.', 1, 2026, 'Unemployed', '', '', '', 0, 1.00, 1.00, 3.00, 3.00, '', 'Job Mismatch', 'Software Engineer', '2026-02-25 01:03:13'),
(4, 'Rex Navarro Iii', 3, 2021, 'Unemployed', '', '', '', 0, 2.50, 1.25, 1.00, 1.00, '', 'Job Mismatch', 'General Corporate Roles', '2026-02-25 01:42:18'),
(5, 'Jhun Alvarez', 1, 2004, 'Unemployed', '', '', '', 0, 1.00, 1.25, 3.00, 3.00, '', 'Job Mismatch', 'Network Administrator', '2026-02-25 02:13:22'),
(6, 'Kelly Lola', 1, 2026, 'Employed', 'Abc Company', 'It Practitioner', '20k-40k', 2, 1.50, 1.25, 0.00, 0.00, '', 'Good Match', 'Continue growing as a It Practitioner', '2026-02-25 06:05:14'),
(7, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 1.51, 1.25, 4.00, 2.00, '', 'Good Match', 'Software Engineer', '2026-03-08 10:04:29'),
(8, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 2.50, 1.50, 1.00, 1.00, '', 'Job Mismatch', 'Software Engineer', '2026-03-08 10:05:24'),
(9, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 3.00, 1.49, 1.00, 1.00, '', 'Job Mismatch', 'Network Administrator', '2026-03-08 10:10:12'),
(10, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 1.00, 1.00, 5.00, 5.00, '', 'Good Match', 'Software Engineer', '2026-03-08 10:16:02'),
(11, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Good Match', 'Network Administrator', '2026-03-08 10:28:43'),
(12, 'Juan Cruz', 2, 2025, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Good Match', 'Data Analyst', '2026-03-08 10:38:36'),
(13, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Good Match', 'Network Administrator', '2026-03-08 11:13:02'),
(14, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 5.00, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Network Administrator', '2026-03-08 11:13:32'),
(15, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Network Administrator', '2026-03-08 11:13:57'),
(16, 'Juan Cruz', 2, 2025, 'Unemployed', '', '', '', 0, 5.00, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Data Analyst', '2026-03-08 11:55:59'),
(17, 'Juan Cruz', 2, 2025, 'Unemployed', '', '', '', 0, 5.00, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Data Analyst', '2026-03-08 11:56:02'),
(18, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Good Match', 'Network Administrator', '2026-03-08 12:31:35'),
(19, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Good Match', 'Software Engineer', '2026-03-08 12:42:16'),
(20, 'Juan Cruz', 2, 2025, 'Unemployed', '', '', '', 0, 5.00, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Data Analyst', '2026-03-08 12:43:01'),
(21, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 5.00, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Network Administrator', '2026-03-08 12:43:27'),
(22, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 1.00, 9.99, 9.99, 9.99, '', 'Good Match', 'Software Engineer', '2026-03-08 13:09:34'),
(23, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 5.00, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Network Administrator', '2026-03-08 13:10:21'),
(24, 'Juan Cruz', 1, 2025, 'Unemployed', '', '', '', 0, 1.50, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Network Administrator', '2026-03-08 13:10:59'),
(25, 'Juan Cruz', 2, 2016, 'Unemployed', '', '', '', 0, 1.60, 9.99, 9.99, 9.99, '', 'Job Mismatch', 'Data Analyst', '2026-03-08 13:18:49');

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
(1, 1, 5, 'Testing Feedback, March 08, 2026', 'Unresolved', '2026-03-08 09:08:40'),
(2, 1, 4, 'Feedback ko Lans', 'Unresolved', '2026-03-16 06:55:21');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `student_id`, `full_name`, `email`, `password`, `role`, `created_at`) VALUES
(1, '23-00186', 'Sample Alumni', 'alumni@example.com', 'alumni123', 'alumni', '2026-02-19 16:15:42'),
(2, '00-ADMIN', 'System Administrator', 'admin@plpasig.edu.ph', 'admin123', 'admin', '2026-02-19 16:15:42');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `professions`
--
ALTER TABLE `professions`
  ADD CONSTRAINT `professions_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
