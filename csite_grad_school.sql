-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 30, 2026 at 12:45 PM
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

--
-- Dumping data for table `advisor_pool`
--

INSERT INTO `advisor_pool` (`adviser_id`, `name`, `qualification`, `email`, `availability`, `notes`, `created_at`) VALUES
(1, 'JohnMombo', 'Phd in s', 'chaosmultiple@gmail.com', 'available', 'Sad', '2026-09-30 09:50:01'),
(2, 'Mariah Carey', 'Phd in singing', 'poppy@adzu.edu.ph', 'unavailable', 'None', '2026-09-30 10:13:32');

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
(18, 6, 'Final Thesis', 'Development of an Automated Graduate Application Tracking System', 'submitted', 'Update this', 0, 0, NULL, NULL, NULL, 0, '[]', NULL, '2026-09-11 06:15:00', '2026-09-30 09:58:13', NULL),
(19, 7, 'Concept Paper', 'Cloud Security Protocols for Institutional Repositories', 'scheduled', '', 0, 0, NULL, NULL, NULL, 0, '{\"presentation_date\":\"Oct 10, 2026\",\"presentation_time\":\"2:00 AM\",\"presentation_venue\":\"das\",\"presentation_panel\":\"Dela Cruz, Juan (PhD in Computer Science), Fernandez, Lisa (MS in Information Technology), Fernandez, Lisa (MS in Information Technology)\"}', NULL, '2026-09-12 02:00:00', '2026-09-30 10:20:40', NULL),
(20, 8, 'Capstone Proposal', 'Mobile-Based Student Records and Notification Management', 'approved', '', 1, 1, '231231', '2026-09-30', 1.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\"}', 'approved', '2026-09-08 03:20:00', '2026-09-30 10:26:04', NULL),
(21, 5, 'Final Thesis Defense', 'Optimizing Database Queries in Large-Scale Web Applications', 'requires_revision', 'Please strengthen the experimental validation chapter and resubmit.', 0, 0, NULL, NULL, NULL, 0, NULL, 'requires_revision', '2026-09-05 08:45:00', '2026-09-13 06:58:19', NULL),
(29, 8, 'Final Thesis', 'Mobile-Based Student Records and Notification Management', 'submitted', NULL, 0, 0, NULL, NULL, NULL, 0, NULL, NULL, '2026-09-30 10:28:43', '2026-09-30 10:28:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `application_comments`
--

CREATE TABLE `application_comments` (
  `comment_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `coordinator_user_id` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `application_comments`
--

INSERT INTO `application_comments` (`comment_id`, `application_id`, `coordinator_user_id`, `comment_text`, `created_at`) VALUES
(1, 18, 2, 'Update this', '2026-09-30 09:58:13');

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

--
-- Dumping data for table `application_documents`
--

INSERT INTO `application_documents` (`document_id`, `application_id`, `user_id`, `stage`, `document_type`, `original_name`, `stored_name`, `mime_type`, `file_size`, `status`, `uploaded_at`) VALUES
(7, 29, 8, 'Final Capstone', 'Final Capstone Paper', '2016 Software Engineering (1).pdf', '522212cd8c61f130ff83a983cf33e012.pdf', 'application/pdf', 269283, 'submitted', '2026-09-30 10:28:43');

-- --------------------------------------------------------

--
-- Table structure for table `panel_members`
--

CREATE TABLE `panel_members` (
  `panel_member_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `qualification` varchar(150) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `availability` enum('available','unavailable') NOT NULL DEFAULT 'available',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `panel_members`
--

INSERT INTO `panel_members` (`panel_member_id`, `first_name`, `middle_name`, `last_name`, `qualification`, `email`, `availability`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Juan', NULL, 'Dela Cruz', 'PhD in Computer Science', 'delacruz@adzu.edu.ph', 'available', 'Demo panel member', '2026-09-30 09:34:20', '2026-09-30 09:34:20'),
(2, 'Ana', NULL, 'Reyes', 'PhD in Information Technology', 'reyes@adzu.edu.ph', 'available', 'Demo panel member', '2026-09-30 09:34:20', '2026-09-30 09:34:20'),
(3, 'Miguel', NULL, 'Santos', 'MS in Computer Science', 'santos@adzu.edu.ph', 'available', 'Demo panel member', '2026-09-30 09:34:20', '2026-09-30 09:34:20'),
(4, 'Lisa', NULL, 'Fernandez', 'MS in Information Technology', 'fernandez@adzu.edu.ph', 'available', 'Demo panel member', '2026-09-30 09:34:20', '2026-09-30 09:34:20');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `recorded_by_user_id` int(11) NOT NULL,
  `receipt_number` varchar(100) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `proof_document_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `application_id`, `recorded_by_user_id`, `receipt_number`, `payment_date`, `amount`, `proof_document_id`, `notes`, `created_at`, `updated_at`) VALUES
(1, 20, 2, '231231', '2026-09-30', 1.00, NULL, NULL, '2026-09-30 10:26:04', '2026-09-30 10:26:04');

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
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `managed_by_user_id` int(11) NOT NULL,
  `presentation_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time DEFAULT NULL,
  `venue` varchar(200) NOT NULL,
  `adviser_id` int(11) DEFAULT NULL,
  `documentor_first_name` varchar(100) DEFAULT NULL,
  `documentor_middle_name` varchar(100) DEFAULT NULL,
  `documentor_last_name` varchar(100) DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`schedule_id`, `application_id`, `managed_by_user_id`, `presentation_date`, `start_time`, `end_time`, `venue`, `adviser_id`, `documentor_first_name`, `documentor_middle_name`, `documentor_last_name`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(2, 19, 2, '2026-10-10', '02:00:00', NULL, 'das', 1, 'Lisa', '(MS in Information Technology)', 'Fernandez', 'confirmed', NULL, '2026-09-30 10:20:40', '2026-09-30 10:20:40');

-- --------------------------------------------------------

--
-- Table structure for table `schedule_panel_assignments`
--

CREATE TABLE `schedule_panel_assignments` (
  `assignment_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `panel_member_id` int(11) NOT NULL,
  `panel_role` enum('chair','member','adviser','documentor') NOT NULL DEFAULT 'member',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schedule_panel_assignments`
--

INSERT INTO `schedule_panel_assignments` (`assignment_id`, `schedule_id`, `panel_member_id`, `panel_role`, `notes`, `created_at`) VALUES
(1, 2, 1, 'member', NULL, '2026-09-30 10:20:40'),
(2, 2, 4, 'member', NULL, '2026-09-30 10:20:40');

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
(240092, 8, 'John', 'Gler', NULL, 21, 'Male', 'Master in Information Technology (Capstone)', 'capstone', NULL, '2026-09-13', NULL),
(240234, 7, 'Rhett', 'Epino', NULL, 21, 'Male', 'Master of Science in Computer Science (Thesis)', 'thesis', NULL, '2026-09-13', NULL),
(240527, 6, 'MARC', 'ARBILERA', 'M', 21, 'Male', 'Master of Science in Computer Science (Thesis)', 'thesis', NULL, '2026-09-12', NULL);

--
-- Triggers `students`
--
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
DELIMITER $$
CREATE TRIGGER `students_before_insert_autoid` BEFORE INSERT ON `students` FOR EACH ROW BEGIN
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

-- --------------------------------------------------------

--
-- Table structure for table `templates`
--

CREATE TABLE `templates` (
  `template_id` int(11) NOT NULL,
  `track_id` int(11) NOT NULL,
  `stage_id` int(11) DEFAULT NULL,
  `template_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `document_type` enum('form','template','reference') NOT NULL DEFAULT 'template',
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `managed_by_user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `templates`
--

INSERT INTO `templates` (`template_id`, `track_id`, `stage_id`, `template_name`, `description`, `document_type`, `file_name`, `file_path`, `mime_type`, `is_active`, `managed_by_user_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Graduate School Thesis Format', 'Official format for thesis documents', 'template', 'GRADUATE SCHOOL - CSITE - THESIS FORMAT.docx', 'assets/papers/THESIS/GRADUATE SCHOOL - CSITE - THESIS FORMAT.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
(2, 1, 1, 'Concept Paper Adviser Endorsement Form', 'Signed by the research adviser', 'form', '1 CONCEPT PAPER ADVISER ENDORSEMENT FORM_.docx', 'assets/papers/THESIS/1 CONCEPT PAPER ADVISER ENDORSEMENT FORM_.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
(3, 1, 1, 'Concept Paper Endorsement to Graduate School', 'Coordinator endorsement form', 'form', '2 CONCEPT PAPER ENDORSEMENT TO GRADSCHOOL.docx', 'assets/papers/THESIS/2 CONCEPT PAPER ENDORSEMENT TO GRADSCHOOL.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
(4, 1, 2, 'Thesis Proposal Adviser Endorsement Form', 'Signed by the research adviser', 'form', '3 THESIS PROPOSAL ADVISER ENDORSEMENT FORM.docx', 'assets/papers/THESIS/3 THESIS PROPOSAL ADVISER ENDORSEMENT FORM.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
(5, 1, 2, 'Thesis Proposal Endorsement to Graduate School', 'Coordinator endorsement form', 'form', '4 THESIS PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'assets/papers/THESIS/4 THESIS PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
(6, 1, 3, 'Final Thesis Adviser Endorsement Form', 'Signed by the research adviser', 'form', '5 FINAL THESIS ADVISER ENDORSEMENT FORM.docx', 'assets/papers/THESIS/5 FINAL THESIS ADVISER ENDORSEMENT FORM.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
(7, 1, 3, 'Final Thesis Endorsement to Graduate School', 'Coordinator endorsement form', 'form', '6 FINAL THESIS ENDORSEMENT TO GRADSCHOOL.docx', 'assets/papers/THESIS/6 FINAL THESIS ENDORSEMENT TO GRADSCHOOL.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
(8, 1, NULL, 'Final Thesis Cover Page', 'Final thesis cover page', 'reference', 'FINAL THESIS COVER PAGE v2026.docx', 'assets/papers/THESIS/FINAL THESIS COVER PAGE v2026.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
(9, 2, 4, 'Capstone Project Proposal Template', 'Official capstone proposal template', 'template', 'MIT GradSchool CAPSTONE PROJECT PROPOSAL TEMPLATE v2025.docx', 'assets/papers/CAPSTONE/MIT GradSchool CAPSTONE PROJECT PROPOSAL TEMPLATE v2025.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
(10, 2, 4, 'Capstone Adviser Endorsement Form', 'Signed by the capstone adviser', 'form', '1 CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'assets/papers/CAPSTONE/1 CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
(11, 2, 4, 'Capstone Proposal Endorsement to Graduate School', 'Coordinator endorsement form', 'form', '2 CAPSTONE PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'assets/papers/CAPSTONE/2 CAPSTONE PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
(12, 2, 5, 'Final Capstone Adviser Endorsement Form', 'Signed by the capstone adviser', 'form', '3 FINAL CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'assets/papers/CAPSTONE/3 FINAL CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
(13, 2, 5, 'Final Capstone Endorsement to Graduate School', 'Coordinator endorsement form', 'form', '4 FINAL CAPSTONE ENDORSEMENT TO GRADSCHOOL.docx', 'assets/papers/CAPSTONE/4 FINAL CAPSTONE ENDORSEMENT TO GRADSCHOOL.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
(14, 2, NULL, 'Capstone Guidelines', 'Capstone project guidelines', 'reference', 'MIT Capstone Guidelines.docx', 'assets/papers/CAPSTONE/MIT Capstone Guidelines.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
(15, 2, NULL, 'Final Capstone Cover Page', 'Final capstone cover page', 'reference', 'FINAL CAPSTONE COVER PAGE v2026.docx', 'assets/papers/CAPSTONE/FINAL CAPSTONE COVER PAGE v2026.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
(16, 3, 6, 'Seminar Paper Template Format', 'Official seminar paper format', 'template', 'MATH SEMINAR PAPER TEMPLATE FORMAT.docx', 'assets/papers/SEMINAR/MATH SEMINAR PAPER TEMPLATE FORMAT.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
(17, 3, 6, 'Seminar Paper Proposal Adviser Endorsement Form', 'Signed by the research adviser', 'form', '1 SEMINAR PAPER PROPOSAL ADVISER ENDORSEMENT FORM .docx', 'assets/papers/SEMINAR/1 SEMINAR PAPER PROPOSAL ADVISER ENDORSEMENT FORM .docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
(18, 3, 6, 'Seminar Paper Proposal Endorsement to Graduate School', 'Coordinator endorsement form', 'form', '2 SEMINAR PAPER PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'assets/papers/SEMINAR/2 SEMINAR PAPER PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
(19, 3, 7, 'Final Seminar Paper Adviser Endorsement Form', 'Signed by the research adviser', 'form', '3 FINAL SEMINAR PAPER ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'assets/papers/SEMINAR/3 FINAL SEMINAR PAPER ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
(20, 3, 7, 'Final Seminar Paper Endorsement to Graduate School', 'Coordinator endorsement form', 'form', '2 SEMINAR PAPER PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'assets/papers/SEMINAR/2 SEMINAR PAPER PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
(21, 3, NULL, 'Final Seminar Paper Cover Page', 'Final seminar paper cover page', 'reference', 'FINAL SEMINAR PAPER COVER PAGE v2026.docx', 'assets/papers/SEMINAR/FINAL SEMINAR PAPER COVER PAGE v2026.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23');

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
-- Stand-in structure for view `v_students_alpha`
-- (See below for the actual view)
--
CREATE TABLE `v_students_alpha` (
`student_id` int(11)
,`user_id` int(11)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`middle_initial` varchar(5)
,`display_name` varchar(108)
,`age` int(11)
,`gender` varchar(20)
,`program` varchar(100)
,`track` enum('thesis','capstone','seminar')
,`adviser_name` varchar(150)
,`enrollment_date` date
,`archived_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_users_alpha`
-- (See below for the actual view)
--
CREATE TABLE `v_users_alpha` (
`user_id` int(11)
,`full_name` varchar(150)
,`email` varchar(100)
,`role` enum('student','coordinator','panel','adviser')
,`created_at` timestamp
,`last_name` varchar(150)
,`first_name` varchar(150)
);

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

-- --------------------------------------------------------

--
-- Structure for view `v_students_alpha`
--
DROP TABLE IF EXISTS `v_students_alpha`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_students_alpha`  AS SELECT `students`.`student_id` AS `student_id`, `students`.`user_id` AS `user_id`, `students`.`first_name` AS `first_name`, `students`.`last_name` AS `last_name`, `students`.`middle_initial` AS `middle_initial`, trim(concat(`students`.`last_name`,', ',`students`.`first_name`,if(`students`.`middle_initial` is null or `students`.`middle_initial` = '','',concat(' ',`students`.`middle_initial`)))) AS `display_name`, `students`.`age` AS `age`, `students`.`gender` AS `gender`, `students`.`program` AS `program`, `students`.`track` AS `track`, `students`.`adviser_name` AS `adviser_name`, `students`.`enrollment_date` AS `enrollment_date`, `students`.`archived_at` AS `archived_at` FROM `students` ORDER BY `students`.`last_name` ASC, `students`.`first_name` ASC LIMIT 0, 9223372036854775807 ;

-- --------------------------------------------------------

--
-- Structure for view `v_users_alpha`
--
DROP TABLE IF EXISTS `v_users_alpha`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_users_alpha`  AS SELECT `users`.`user_id` AS `user_id`, `users`.`full_name` AS `full_name`, `users`.`email` AS `email`, `users`.`role` AS `role`, `users`.`created_at` AS `created_at`, substring_index(`users`.`full_name`,',',1) AS `last_name`, trim(substring_index(`users`.`full_name`,',',-1)) AS `first_name` FROM `users` ORDER BY substring_index(`users`.`full_name`,',',1) ASC, substring_index(`users`.`full_name`,',',-1) ASC LIMIT 0, 9223372036854775807 ;

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
-- Indexes for table `application_comments`
--
ALTER TABLE `application_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `idx_application_comments_application` (`application_id`,`created_at`),
  ADD KEY `idx_application_comments_coordinator` (`coordinator_user_id`);

--
-- Indexes for table `application_documents`
--
ALTER TABLE `application_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `application_documents_application_id` (`application_id`),
  ADD KEY `idx_application_documents_user_id` (`user_id`);

--
-- Indexes for table `panel_members`
--
ALTER TABLE `panel_members`
  ADD PRIMARY KEY (`panel_member_id`),
  ADD UNIQUE KEY `uq_panel_members_email` (`email`),
  ADD KEY `idx_panel_members_name` (`last_name`,`first_name`,`middle_name`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `uq_payments_application` (`application_id`),
  ADD UNIQUE KEY `uq_payments_receipt_number` (`receipt_number`),
  ADD KEY `idx_payments_recorder` (`recorded_by_user_id`),
  ADD KEY `idx_payments_proof_document` (`proof_document_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`program_id`),
  ADD UNIQUE KEY `uq_programs_code` (`program_code`),
  ADD KEY `idx_programs_track` (`track_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD UNIQUE KEY `uq_schedules_application` (`application_id`),
  ADD KEY `idx_schedules_date_time` (`presentation_date`,`start_time`),
  ADD KEY `idx_schedules_manager` (`managed_by_user_id`),
  ADD KEY `idx_schedules_adviser` (`adviser_id`);

--
-- Indexes for table `schedule_panel_assignments`
--
ALTER TABLE `schedule_panel_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `uq_schedule_panel_member` (`schedule_id`,`panel_member_id`),
  ADD KEY `idx_assignments_panel_member` (`panel_member_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_students_alpha` (`last_name`,`first_name`);

--
-- Indexes for table `templates`
--
ALTER TABLE `templates`
  ADD PRIMARY KEY (`template_id`),
  ADD KEY `idx_templates_track_stage` (`track_id`,`stage_id`),
  ADD KEY `idx_templates_manager` (`managed_by_user_id`),
  ADD KEY `fk_templates_stage` (`stage_id`);

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
  MODIFY `adviser_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `application_comments`
--
ALTER TABLE `application_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `application_documents`
--
ALTER TABLE `application_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `panel_members`
--
ALTER TABLE `panel_members`
  MODIFY `panel_member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `schedule_panel_assignments`
--
ALTER TABLE `schedule_panel_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=240528;

--
-- AUTO_INCREMENT for table `templates`
--
ALTER TABLE `templates`
  MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

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
-- Constraints for table `application_comments`
--
ALTER TABLE `application_comments`
  ADD CONSTRAINT `fk_application_comments_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_application_comments_coordinator` FOREIGN KEY (`coordinator_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `application_documents`
--
ALTER TABLE `application_documents`
  ADD CONSTRAINT `application_documents_application_fk` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `application_documents_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payments_proof_document` FOREIGN KEY (`proof_document_id`) REFERENCES `application_documents` (`document_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payments_recorder` FOREIGN KEY (`recorded_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `programs`
--
ALTER TABLE `programs`
  ADD CONSTRAINT `fk_programs_track` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`track_id`) ON UPDATE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `fk_schedules_adviser` FOREIGN KEY (`adviser_id`) REFERENCES `advisor_pool` (`adviser_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedules_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedules_manager` FOREIGN KEY (`managed_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `schedule_panel_assignments`
--
ALTER TABLE `schedule_panel_assignments`
  ADD CONSTRAINT `fk_assignments_panel_member` FOREIGN KEY (`panel_member_id`) REFERENCES `panel_members` (`panel_member_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_assignments_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`schedule_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `templates`
--
ALTER TABLE `templates`
  ADD CONSTRAINT `fk_templates_manager` FOREIGN KEY (`managed_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_templates_stage` FOREIGN KEY (`stage_id`) REFERENCES `workflow_stages` (`stage_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_templates_track` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`track_id`) ON UPDATE CASCADE;

--
-- Constraints for table `workflow_stages`
--
ALTER TABLE `workflow_stages`
  ADD CONSTRAINT `fk_workflow_track` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`track_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
