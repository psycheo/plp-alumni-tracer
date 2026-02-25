-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 19, 2026 at 05:43 PM
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
(1, 'Juan Dela Cruz', 1, 2022, 'Not Employed', '', '', '', 0, 1.51, 1.25, 3.50, 3.00, '', 'Good Match', 'Network Administrator', '2026-02-19 16:15:42');

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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `alumni_assessments`
--
ALTER TABLE `alumni_assessments`
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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `alumni_assessments`
--
ALTER TABLE `alumni_assessments`
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
