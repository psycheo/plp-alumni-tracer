-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2026 at 07:35 AM
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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'alumni',
  `grad_year` int(4) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_temporary` tinyint(1) NOT NULL DEFAULT 1,
  `program_id` int(11) DEFAULT NULL COMMENT 'Degree/program on file',
  `avg_professional_grade` decimal(5,2) DEFAULT NULL COMMENT 'Coursework avg 0-100',
  `avg_elective_grade` decimal(5,2) DEFAULT NULL COMMENT 'Coursework avg 0-100',
  `record_soft_skills_avg` decimal(5,2) DEFAULT NULL COMMENT 'Official soft skills avg 0-100',
  `record_hard_skills_avg` decimal(5,2) DEFAULT NULL COMMENT 'Official hard skills avg 0-100',
  `gpa` decimal(3,2) DEFAULT NULL COMMENT 'PLP scale 1.00-5.00 (lower is better)',
  `ojt_grade_percent` decimal(5,2) DEFAULT NULL COMMENT 'OJT final 60-100'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `student_id`, `full_name`, `email`, `password`, `role`, `grad_year`, `created_at`, `is_temporary`, `program_id`, `avg_professional_grade`, `avg_elective_grade`, `record_soft_skills_avg`, `record_hard_skills_avg`, `gpa`, `ojt_grade_percent`) VALUES
(1, '23-00001', 'Test Alumni', 'alumni@example.com', '$2y$10$9rCXeyLu9ndPZkJgrPJr.utPzkZ5e5qnOTCWxFdYE2czyqUIQW38O', 'alumni', NULL, '2026-02-19 16:15:42', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, '00-ADMIN', 'System Administrator', 'admin@plpasig.edu.ph', 'admin123', 'admin', NULL, '2026-02-19 16:15:42', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, '23-00197', 'Rex ', 'navarro@plpasig.edu.ph', '123', 'alumni', NULL, '2026-03-18 05:35:51', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, '23-00303', 'Kathleen Anne R. Abalos', 'abalos_kathleenanne@plpasig.edu.ph', '$2y$10$8ia9Fe3VUFV0HhUECmSKHeO5SpKMysYGiCAk0adzTSchz/HKk21cu', 'alumni', 2025, '2026-05-03 12:04:18', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, '23-01027', 'Lans Adrian A. Alonzo', 'alonzo_lansadrian@plpasig.edu.ph', '$2y$10$PHYa4k9Z4t/hwKlDuYUvhuhG7IQB69j0MoEovgQV3MaDMWTYXml9O', 'alumni', 2025, '2026-05-03 12:04:18', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, '23-00335', 'Aldrin Shane P. Arcillas', 'arcillas_aldrinshane@plpasig.edu.ph', '$2y$10$wcuZM/6BH8c4JwlIcg5r2eD2F8M/mXDmjIXih1pgOpSJJHMgqK2wK', 'alumni', 2025, '2026-05-03 12:04:18', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, '23-00651', 'Gabriel A. Balang', 'balang_gabriel@plpasig.edu.ph', '$2y$10$aLwFriMnfLghlam7Jax71.EpA2HCyX9oaqQcHSFIeRFPcP7A6Qj6.', 'alumni', 2025, '2026-05-03 12:04:18', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, '23-00277', 'Stephen Kyle B. Batara', 'batara_stephenkyle@plpasig.edu.ph', '$2y$10$oJhwusl5P1dR2XIZecOO6OTvVWmHcUXDMOb1O94sh93iH3DLvLWBS', 'alumni', 2025, '2026-05-03 12:04:18', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, '23-00317', 'Ryza L. Bayot', 'bayot_ryza@plpasig.edu.ph', '$2y$10$z2VQEVNHYztUQzozFlgXwOPv/.S3VIJFdNhwyXhIcWXGXxd5Ls32.', 'alumni', 2025, '2026-05-03 12:04:18', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, '23-00157', 'Richelle Dorothy U. Benitez', 'benitez_richelledorothy@plpasig.edu.ph', '$2y$10$w0VOGcZwLn4z9Lgpvf6XM.3trO.smK.NIy0Iq7zm9XzOttPA6rTx2', 'alumni', 2025, '2026-05-03 12:04:18', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, '23-00262', 'Najil J. Bumacod', 'bumacod_najil@plpasig.edu.ph', '$2y$10$dI5hTc1A/IoNaXj59a/jVufhGiG.QLJ3LIYp94F4UwhzDqI0x476S', 'alumni', 2025, '2026-05-03 12:04:18', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, '23-01039', 'Jose Luis M. Cagalingan', 'cagalingan_joseluis@plpasig.edu.ph', '$2y$10$MVr4gSNPemv8XHAZsXxU2.cbA440VzBvtlGT/uHa77XLYe6IbJksK', 'alumni', 2025, '2026-05-03 12:04:19', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, '23-00274', 'Jan Alain S. Cainglet', 'cainglet_janalain@plpasig.edu.ph', '$2y$10$2eedUdeCel7JnpGFqTvH4.fBuYenHghqqpxqIgiqUybQcwU6JoWGG', 'alumni', 2025, '2026-05-03 12:04:19', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, '23-00246', 'Rhonalyn K. Cantorna', 'cantorna_rhonalyn@plpasig.edu.ph', '$2y$10$se4zVMsuyA8N.uVmihYsk.pgXy7QvQek7WD303yupUVwKmceXvADS', 'alumni', 2025, '2026-05-03 12:04:19', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(17, '23-00150', 'Alfredo G. Edrada III', 'edrada_alfredo@plpasig.edu.ph', '$2y$10$O2nqWYl35VofmO/uencw4Oj8uZ/oZPBaUeGiaZFrcPtOtEb1LvqoW', 'alumni', 2025, '2026-05-03 12:04:19', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, '23-00211', 'Justine Angelo B. Faustino', 'faustino_justineangelo@plpasig.edu.ph', '$2y$10$.GSBaaR8KV8M4jG/CiaKsehQ/sH1yHnlC4Ki8Vs8.4Xd4kRTLJ3ry', 'alumni', 2025, '2026-05-03 12:04:19', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, '23-00220', 'Carl Aj G. Junio', 'junio_carlaj@plpasig.edu.ph', '$2y$10$xvPgqDilsSZv4gFt3EzuYO89UrazydtRX6pMGqikYVdwu.zbva4F6', 'alumni', 2025, '2026-05-03 12:04:19', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(20, '23-00161', 'Ron Michael C. Legaspi', 'legaspi_ronmichael@plpasig.edu.ph', '$2y$10$.UCp7RftZWk4pb0.JMnWNeAiiPelxK6Rk3AXtbKBqZRrF8qJnlJau', 'alumni', 2025, '2026-05-03 12:04:19', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(21, '23-00159', 'Kelly Rowland M. Lola', 'lola_kellyrowland@plpasig.edu.ph', '$2y$10$.WaIWyv1dGcIiRk1YZxJWOZtsn1ShrHyhn8cO3WhYxwe.4j5Co0EC', 'alumni', 2025, '2026-05-03 12:04:19', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(22, '23-00152', 'Anthony M. Loterte', 'loterte_anthony@plpasig.edu.ph', '$2y$10$WmKncL7wPtaEm9khLVND2edSgddv8lrSAiF3wPM6ckMGMqBqCsdP6', 'alumni', 2025, '2026-05-03 12:04:19', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(23, '23-01095', 'Maverick S. Mabingnay', 'mabingnay_maverick@plpasig.edu.ph', '$2y$10$tsdYgyFHiklQjPIV5oEMVexHcjgVpU7ByIeCdRYGsS9.Zzb4gHC/K', 'alumni', 2025, '2026-05-03 12:04:20', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(24, '23-00245', 'Eugene Joezer B. Manlangit', 'manlangit_eugenejoezer@plpasig.edu.ph', '$2y$10$Baytoqe59uvSQiG3xrsxpuhyNFmktJqfx9Qf9BPhwdvyQEa6i.R.C', 'alumni', 2025, '2026-05-03 12:04:20', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(25, '23-00255', 'Prince Emir L. Manlogon', 'manlogon_princeemir@plpasig.edu.ph', '$2y$10$8VR99MM41xsMmcHvD9zEdOoVFLCsRM7n8l4aTtxsxzVguXxE4djzC', 'alumni', 2025, '2026-05-03 12:04:20', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(26, '23-00259', 'John Edrian V. Martinez', 'martinez_johnedrian@plpasig.edu.ph', '$2y$10$aJQ6NjxD4dfLrQH0rM84r.20rOV181sGQonvlpxxD.zrqxv.ZyOdu', 'alumni', 2025, '2026-05-03 12:04:20', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(27, '23-01036', 'Charles Adrian B. Mejia', 'mejia_charlesadrian@plpasig.edu.ph', '$2y$10$kOWKi.qN8BMV0DWEMR4ylOuvcrlY1YxwuO5Cv9nyHVDwjoJ0brZEm', 'alumni', 2025, '2026-05-03 12:04:20', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(28, '23-00241', 'Cecille T. Menciano', 'menciano_cecille@plpasig.edu.ph', '$2y$10$6NG5c.i4QqI5ZRYYxbwx..sRhSMLO.H7mhBXBEUbKnGAOayDAamFm', 'alumni', 2025, '2026-05-03 12:04:20', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(29, '23-00235', 'Hanzel Gwen P. Nañez', 'nanez_hanzelgwen@plpasig.edu.ph', '$2y$10$hQ4u4x6oOr7cz6Zy5/Fto.i4B3wmMAbJO8Vpl6KTm2.EIpKp6iBFS', 'alumni', 2025, '2026-05-03 12:04:20', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(30, '23-00171', 'Dhone Bert T. Napay', 'napay_dhonebert@plpasig.edu.ph', '$2y$10$66TEt5ELCXb8wmlfFehQMeLOgNnG8pcBIU8dm599KcvEejXcKYfmG', 'alumni', 2025, '2026-05-03 12:04:20', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(31, '23-00186', 'Rex S. Navarro Jr.', 'navarro_rex@plpasig.edu.ph', '$2y$10$FOeTMVr2HHAlm4qGBqNx/ee98ll33CXeFI0hMd8Laj7tIl7ziXzbm', 'alumni', 2025, '2026-05-03 12:04:20', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(32, '23-00199', 'Ryan G. Neri', 'neri_ryan@plpasig.edu.ph', '$2y$10$zdCQ.8pCCh0DFIVfgIQDSuyCzXFN3Pt9jJzcFsrkLRtF858k/dWbK', 'alumni', 2025, '2026-05-03 12:04:21', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(33, '23-00244', 'Marc Ryane R. Oliveros', 'oliveros_marcryane@plpasig.edu.ph', '$2y$10$igO0AhmcDBaoKh4NCrjVMOCbKKe723WI2nVh9FU8IfPrZ9DOXmY7S', 'alumni', 2025, '2026-05-03 12:04:21', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(34, '23-00299', 'Avril Lavigne A. Pascua', 'pascua_avrillavigne@plpasig.edu.ph', '$2y$10$kMkuv6O8rDz4fJ.doOfcnOTXc5GhkwyxF2zvjICy0Y6D3j.00CPEm', 'alumni', 2025, '2026-05-03 12:04:21', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(35, '23-01035', 'Jamie A. Rosell', 'rosell_jamie@plpasig.edu.ph', '$2y$10$Xs9pc6OHk3rDikR5Vw5NQu0uoWwF6KTXDtAoAQnrHT5z6P29lRJOm', 'alumni', 2025, '2026-05-03 12:04:21', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(36, '23-00187', 'Milan Franco L. Santos', 'santos_milanfranco@plpasig.edu.ph', '$2y$10$gaiFVI0OieFe9/.eolhiSOHUjtCn.EWRblFDfLP/cGMUVBs0qB3cW', 'alumni', 2025, '2026-05-03 12:04:21', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(37, '23-00296', 'Alyssa Mae G. Suarez', 'suarez_alyssamae@plpasig.edu.ph', '$2y$10$Dnya9ilU1Z7kpL2ld1U78.s/g1zkhWDJ5eUaKCOnH6wclz2TGSuLi', 'alumni', 2025, '2026-05-03 12:04:21', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(38, '23-00294', 'Jayvelyn M. Tolento', 'tolento_jayvelyn@plpasig.edu.ph', '$2y$10$Waq3aFyoBn8ue3c/FlZ/BufynUj81LgJKuXBF2GvcA2jAm05ejSs6', 'alumni', 2025, '2026-05-03 12:04:21', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(39, '23-00223', 'Leo Nathaniel V. Velasquez', 'velasquez_leonathaniel@plpasig.edu.ph', '$2y$10$nsKcEtzt0jV42mJ7FEsmZ.t4dJZEHEYCYsBaODD7hrPoDeHWcXUmq', 'alumni', 2025, '2026-05-03 12:04:21', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(40, '23-00191', 'Archie D. Viterbo', 'viterbo_archie@plpasig.edu.ph', '$2y$10$K3UCN7wsFP5kmoycCoe6TOXug.RWIf7NzAzM/2cmGiHC96GM06z3m', 'alumni', 2025, '2026-05-03 12:04:21', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `users_ibfk_program` (`program_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
