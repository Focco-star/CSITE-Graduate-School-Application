-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 26, 2026 at 12:54 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `csite_grad_school`
--

-- --------------------------------------------------------

--
-- Table structure for table `advisor_pool`
--

CREATE TABLE `advisor_pool` (
  `adviser_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `qualification` varchar(150) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `availability` enum('available','unavailable') NOT NULL DEFAULT 'available',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `application_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `presentation_stage` varchar(100) NOT NULL,
  `paper_title` text NOT NULL,
  `status` enum('submitted','under_review','for_payment','payment_recorded','ready_for_presentation','scheduled','approved','requires_revision','completed') DEFAULT 'submitted',
  `coordinator_comment` text DEFAULT NULL,
  `grad_school_endorsed` tinyint(1) NOT NULL DEFAULT 0,
  `payment_recorded` tinyint(1) NOT NULL DEFAULT 0,
  `receipt_number` varchar(100) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_amount` decimal(10,2) DEFAULT NULL,
  `ready_for_presentation` tinyint(1) NOT NULL DEFAULT 0,
  `workflow_state` text DEFAULT NULL,
  `result` varchar(50) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`application_id`, `user_id`, `presentation_stage`, `paper_title`, `status`, `coordinator_comment`, `grad_school_endorsed`, `payment_recorded`, `receipt_number`, `payment_date`, `payment_amount`, `ready_for_presentation`, `workflow_state`, `result`, `submitted_at`, `updated_at`, `archived_at`) VALUES
(17, 5, 'Thesis Proposal', 'Machine Learning Approaches for Predictive Analytics in Graduate Education', 'under_review', '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, '2026-09-10 01:30:00', '2026-09-13 06:58:19', NULL),
(18, 6, 'Final Capstone', 'Development of an Automated Graduate Application Tracking System', 'submitted', '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, '2026-09-11 06:15:00', '2026-09-13 06:58:19', NULL),
(19, 7, 'Concept Paper', 'Cloud Security Protocols for Institutional Repositories', 'submitted', '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, '2026-09-12 02:00:00', '2026-09-13 06:58:19', NULL),
(20, 8, 'Capstone Proposal', 'Mobile-Based Student Records and Notification Management', 'approved', '', 1, 1, NULL, NULL, NULL, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\"}', 'approved', '2026-09-08 03:20:00', '2026-09-13 06:58:19', NULL),
(21, 5, 'Final Thesis Defense', 'Optimizing Database Queries in Large-Scale Web Applications', 'requires_revision', 'Please strengthen the experimental validation chapter and resubmit.', 0, 0, NULL, NULL, NULL, 0, NULL, 'requires_revision', '2026-09-05 08:45:00', '2026-09-13 06:58:19', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `application_documents`
--

CREATE TABLE `application_documents` (
  `document_id` int(11) NOT NULL,
  `application_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `stage` varchar(100) NOT NULL,
  `document_type` varchar(150) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) NOT NULL DEFAULT 0,
  `status` enum('submitted','verified','incomplete') NOT NULL DEFAULT 'submitted',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- NOTE: No dedicated `coordinators` table. Coordinators live in `users`
-- with role='coordinator' (see users seed below).
-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `program_id` int(11) NOT NULL,
  `track_id` int(11) NOT NULL,
  `program_code` varchar(30) NOT NULL,
  `program_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`program_id`, `track_id`, `program_code`, `program_name`, `description`, `is_active`, `created_at`) VALUES
(1, 1, 'MSCS', 'Master of Science in Computer Science', 'Thesis-based graduate program', 1, '2026-09-26 09:34:52'),
(2, 2, 'MIT', 'Master in Information Technology', 'Capstone-based graduate program', 1, '2026-09-26 09:34:52'),
(3, 3, 'MATH', 'Master of Arts in Mathematics', 'Seminar paper workflow program', 1, '2026-09-26 09:34:53');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_initial` varchar(5) DEFAULT NULL,
  `age` int(11) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `program` varchar(100) NOT NULL,
  `track` enum('thesis','capstone','seminar') NOT NULL,
  `adviser_name` varchar(150) DEFAULT NULL,
  `enrollment_date` date DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `first_name`, `last_name`, `middle_initial`, `age`, `gender`, `program`, `track`, `adviser_name`, `enrollment_date`, `archived_at`) VALUES
(230159, 5, 'Robbie', 'Torres', 'E', 21, 'Male', 'Master of Science in Computer Science (Thesis)', 'thesis', NULL, '2026-09-12', NULL),
(240527, 6, 'MARC', 'ARBILERA', 'M', 21, 'Male', 'Master of Science in Computer Science (Thesis)', 'thesis', NULL, '2026-09-12', NULL),
(240234, 7, 'Rhett', 'Epino', NULL, 21, 'Male', 'Master of Science in Computer Science (Thesis)', 'thesis', NULL, '2026-09-13', NULL),
(240092, 8, 'John', 'Gler', NULL, 21, 'Male', 'Master in Information Technology (Capstone)', 'capstone', NULL, '2026-09-13', NULL);

--
-- Triggers `students`
--
DELIMITER $$
CREATE TRIGGER `students_before_insert_autoid` BEFORE INSERT ON `students` FOR EACH ROW
BEGIN
  DECLARE v_email VARCHAR(100) DEFAULT '';
  DECLARE v_digits VARCHAR(20) DEFAULT '';
  DECLARE v_taken INT DEFAULT 0;
  SELECT `email` INTO v_email FROM `users` WHERE `user_id` = NEW.`user_id` LIMIT 1;
  IF (v_email IS NOT NULL AND v_email <> '') THEN
    SET v_digits = REGEXP_REPLACE(SUBSTRING_INDEX(v_email, '@', 1), '[^0-9]', '');
  END IF;
  IF (v_digits IS NOT NULL AND v_digits <> '') THEN
    -- Official student ID = numeric part of the ADZU email (e.g. co259344 -> 259344).
    SELECT COUNT(*) INTO v_taken FROM `students` WHERE `student_id` = CAST(v_digits AS UNSIGNED);
    IF (v_taken = 0) THEN
      SET NEW.`student_id` = CAST(v_digits AS UNSIGNED);
    END IF;
  END IF;
  IF (NEW.`middle_initial` IS NOT NULL) THEN
    SET NEW.`middle_initial` = NULLIF(TRIM(REPLACE(NEW.`middle_initial`, '.', '')), '');
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `students_after_insert_sync_user` AFTER INSERT ON `students` FOR EACH ROW UPDATE `users`
SET `full_name` = TRIM(CONCAT(NEW.`last_name`, ', ', NEW.`first_name`, IF(NEW.`middle_initial` IS NULL OR TRIM(REPLACE(NEW.`middle_initial`, '.', '')) = '', '', CONCAT(' ', TRIM(REPLACE(NEW.`middle_initial`, '.', ''))))))
WHERE `user_id` = NEW.`user_id`
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `students_after_update_sync_user` AFTER UPDATE ON `students` FOR EACH ROW UPDATE `users`
SET `full_name` = TRIM(CONCAT(NEW.`last_name`, ', ', NEW.`first_name`, IF(NEW.`middle_initial` IS NULL OR TRIM(REPLACE(NEW.`middle_initial`, '.', '')) = '', '', CONCAT(' ', TRIM(REPLACE(NEW.`middle_initial`, '.', ''))))))
WHERE `user_id` = NEW.`user_id`
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `tracks`
--

CREATE TABLE `tracks` (
  `track_id` int(11) NOT NULL,
  `track_code` varchar(30) NOT NULL,
  `track_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tracks`
--

INSERT INTO `tracks` (`track_id`, `track_code`, `track_name`, `description`, `created_at`) VALUES
(1, 'thesis', 'Thesis', 'Graduate thesis workflow', '2026-09-26 09:34:52'),
(2, 'capstone', 'Capstone', 'Capstone workflow', '2026-09-26 09:34:52'),
(3, 'seminar', 'Seminar Paper', 'Seminar paper workflow', '2026-09-26 09:34:52');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','coordinator','panel','adviser') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `role`, `created_at`) VALUES
(2, 'Opinion, Precious', 'gpc-csite@adzu.edu.ph', '$2y$10$/GU6.G4XuEPjhuMik5iHCOo5Fbxb1EjYnpNxHbyt4.Rrcf.6lfhH2', 'coordinator', '2026-09-12 08:35:44'),
(5, 'Torres, Robbie E', 'co230159@adzu.edu.ph', '$2y$10$bCJWuc15jVKGbM7NoIkVGuHNiKkzMgg6BFTmPxYUSf377Ld94Uvp6', 'student', '2026-09-12 08:56:22'),
(6, 'ARBILERA, MARC M', 'co240527@adzu.edu.ph', '$2y$10$cedOtistAVNlRw0mLqcfs.BpZHbOPvOK/TCMlkYvtAo/QDrrEZ1Ka', 'student', '2026-09-12 09:01:46'),
(7, 'Epino, Rhett', 'co240234@adzu.edu.ph', '$2y$10$gs/sMbuaDzBd8PysrKHuruO3QR.dD47/yRQZD8Ir.QmDHa5/MTE8a', 'student', '2026-09-13 06:25:29'),
(8, 'Gler, John', 'co240092@adzu.edu.ph', '$2y$10$CLoYu2tFUOlcWaAN7Oy58ejJHTq5E9qgamndnNcHXyinTGphbN5PW', 'student', '2026-09-13 06:27:11');

-- --------------------------------------------------------

--
-- Table structure for table `workflow_stages`
--

CREATE TABLE `workflow_stages` (
  `stage_id` int(11) NOT NULL,
  `track_id` int(11) NOT NULL,
  `stage_key` varchar(50) NOT NULL,
  `stage_label` varchar(100) NOT NULL,
  `stage_order` int(11) NOT NULL DEFAULT 1,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workflow_stages`
--

INSERT INTO `workflow_stages` (`stage_id`, `track_id`, `stage_key`, `stage_label`, `stage_order`, `is_required`, `created_at`) VALUES
(1, 1, 'concept', 'Concept Paper', 1, 1, '2026-09-26 09:34:58'),
(2, 1, 'proposal', 'Thesis Proposal', 2, 1, '2026-09-26 09:34:58'),
(3, 1, 'final', 'Final Thesis', 3, 1, '2026-09-26 09:34:58'),
(4, 2, 'proposal', 'Capstone Proposal', 1, 1, '2026-09-26 09:34:58'),
(5, 2, 'final', 'Final Capstone', 2, 1, '2026-09-26 09:34:58'),
(6, 3, 'proposal', 'Seminar Paper Proposal', 1, 1, '2026-09-26 09:34:58'),
(7, 3, 'final', 'Final Seminar Paper', 2, 1, '2026-09-26 09:34:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `advisor_pool`
--
ALTER TABLE `advisor_pool`
  ADD PRIMARY KEY (`adviser_id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `idx_applications_user_id` (`user_id`);

--
-- Indexes for table `application_documents`
--
ALTER TABLE `application_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `application_documents_application_id` (`application_id`),
  ADD KEY `idx_application_documents_user_id` (`user_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`program_id`),
  ADD UNIQUE KEY `uq_programs_code` (`program_code`),
  ADD KEY `idx_programs_track` (`track_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_students_alpha` (`last_name`, `first_name`);

--
-- Indexes for table `tracks`
--
ALTER TABLE `tracks`
  ADD PRIMARY KEY (`track_id`),
  ADD UNIQUE KEY `uq_tracks_code` (`track_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_full_name` (`full_name`);

--
-- Indexes for table `workflow_stages`
--
ALTER TABLE `workflow_stages`
  ADD PRIMARY KEY (`stage_id`),
  ADD UNIQUE KEY `uq_workflow_stage` (`track_id`,`stage_key`),
  ADD KEY `idx_workflow_track_order` (`track_id`,`stage_order`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `advisor_pool`
--
ALTER TABLE `advisor_pool`
  MODIFY `adviser_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `application_documents`
--
ALTER TABLE `application_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=240528;

--
-- AUTO_INCREMENT for table `tracks`
--
ALTER TABLE `tracks`
  MODIFY `track_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `workflow_stages`
--
ALTER TABLE `workflow_stages`
  MODIFY `stage_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `application_documents`
--
ALTER TABLE `application_documents`
  ADD CONSTRAINT `application_documents_application_fk` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `application_documents_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `programs`
--
ALTER TABLE `programs`
  ADD CONSTRAINT `fk_programs_track` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`track_id`) ON UPDATE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `workflow_stages`
--
ALTER TABLE `workflow_stages`
  ADD CONSTRAINT `fk_workflow_track` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`track_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Alphabetical views: last name, then first name
--
CREATE OR REPLACE VIEW `v_students_alpha` AS
SELECT `student_id`,
       `user_id`,
       `first_name`,
       `last_name`,
       `middle_initial`,
       TRIM(CONCAT(`last_name`, ', ', `first_name`,
            IF(`middle_initial` IS NULL OR `middle_initial` = '',
               '', CONCAT(' ', `middle_initial`)))) AS `display_name`,
       `age`,
       `gender`,
       `program`,
       `track`,
       `adviser_name`,
       `enrollment_date`,
       `archived_at`
FROM `students`
ORDER BY `last_name` ASC, `first_name` ASC
LIMIT 18446744073709551615;

CREATE OR REPLACE VIEW `v_users_alpha` AS
SELECT `user_id`,
       `full_name`,
       `email`,
       `role`,
       `created_at`,
       SUBSTRING_INDEX(`full_name`, ',', 1) AS `last_name`,
       TRIM(SUBSTRING_INDEX(`full_name`, ',', -1)) AS `first_name`
FROM `users`
ORDER BY SUBSTRING_INDEX(`full_name`, ',', 1) ASC,
         SUBSTRING_INDEX(`full_name`, ',', -1) ASC
LIMIT 18446744073709551615;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
