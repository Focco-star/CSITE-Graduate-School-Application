-- Header comment: this whole file is a database dump exported by phpMyAdmin.
-- phpMyAdmin SQL Dump
-- The export was produced by phpMyAdmin version 5.2.1.
-- version 5.2.1
-- Link to the phpMyAdmin website for reference.
-- https://www.phpmyadmin.net/
-- Empty comment line separating the header details.
--
-- The database ran on host 127.0.0.1, meaning the local machine.
-- Host: 127.0.0.1
-- Export date: Oct 26, 2026 at 04:10 PM
-- Generation Time: Oct 26, 2026 at 04:10 PM
-- The database server was MariaDB version 10.4.32.
-- Server version: 10.4.32-MariaDB
-- The server was running PHP version 8.0.30.
-- PHP Version: 8.0.30

-- NO_AUTO_VALUE_ON_ZERO lets a row keep id 0 instead of being auto-numbered.
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
-- Starts a transaction so all the statements below are applied together.
START TRANSACTION;
-- Sets the session time zone to UTC (offset +00:00).
SET time_zone = "+00:00";


-- Saves the current client character set so it can be restored at the end.
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
-- Saves the current result character set so it can be restored at the end.
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
-- Saves the current connection collation so it can be restored at the end.
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
-- Tells MySQL to use utf8mb4, which stores all Unicode characters.
/*!40101 SET NAMES utf8mb4 */;

-- Comment divider before the database name section.
--
-- Database name: csite_grad_school, the schema this dump rebuilds.
-- Database: `csite_grad_school`
-- Comment divider closing the database name header.
--

-- Visual divider that marks the end of a table section.
-- --------------------------------------------------------

-- Comment divider introducing the advisor_pool table description.
--
-- Says the next statement builds the advisor_pool (adviser) table.
-- Table structure for table `advisor_pool`
-- Comment divider closing the advisor_pool description header.
--

-- Creates advisor_pool: the list of advisers who can be assigned to defenses.
CREATE TABLE `advisor_pool` (
  -- adviser_id is the adviser's number; it becomes the primary key later.
  `adviser_id` int(11) NOT NULL,
  -- name stores the adviser's full name.
  `name` varchar(150) NOT NULL,
  -- qualification stores the adviser's degree, e.g. PhD in Computer Science.
  `qualification` varchar(150) NOT NULL,
  -- email is an optional contact address; NULL means none was given.
  `email` varchar(100) DEFAULT NULL,
  -- availability is 'available' or 'unavailable', defaulting to available.
  `availability` enum('available','unavailable') NOT NULL DEFAULT 'available',
  -- notes holds optional free text about the adviser.
  `notes` text DEFAULT NULL,
  -- created_at records when the adviser row was added; defaults to now.
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
-- Uses InnoDB storage with utf8mb4 text and a unicode collation.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comment divider introducing the advisor_pool data section.
--
-- Says the next INSERT fills advisor_pool with sample advisers.
-- Dumping data for table `advisor_pool`
-- Comment divider closing the advisor_pool data header.
--

-- Inserts demo advisers and lists the columns that receive values.
INSERT INTO `advisor_pool` (`adviser_id`, `name`, `qualification`, `email`, `availability`, `notes`, `created_at`) VALUES
-- Adviser 1: JohnMombo, PhD, available, with a short note.
(1, 'JohnMombo', 'Phd in s', 'chaosmultiple@gmail.com', 'available', 'Sad', '2026-09-30 09:50:01'),
-- Adviser 2: Mariah Carey, PhD in singing, currently unavailable.
(2, 'Mariah Carey', 'Phd in singing', 'poppy@adzu.edu.ph', 'unavailable', 'None', '2026-09-30 10:13:32');

-- Visual divider before the documenter_pool table section.
-- --------------------------------------------------------

-- Comment divider introducing the documenter_pool table.
--
-- Says the next statement builds the documenter_pool table.
-- Table structure for table `documenter_pool`
-- Comment divider closing the documenter_pool header.
--

-- Creates documenter_pool: people who can document (record) defenses.
CREATE TABLE `documenter_pool` (
  -- documenter_id auto-numbers each documenter and is the primary key.
  `documenter_id` int(11) NOT NULL AUTO_INCREMENT,
  -- first_name stores the documenter's given name.
  `first_name` varchar(100) NOT NULL,
  -- middle_name is optional and may be NULL.
  `middle_name` varchar(100) DEFAULT NULL,
  -- last_name stores the documenter's family name.
  `last_name` varchar(100) NOT NULL,
  -- qualification stores the documenter's degree.
  `qualification` varchar(150) NOT NULL,
  -- email is an optional contact address for the documenter.
  `email` varchar(100) DEFAULT NULL,
  -- availability holds available/unavailable and defaults to available.
  `availability` enum('available','unavailable') NOT NULL DEFAULT 'available',
  -- notes keeps optional remarks about the documenter.
  `notes` text DEFAULT NULL,
  -- created_at stores when the documenter row was created.
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  -- Declares documenter_id as the primary key, the unique row identifier.
  PRIMARY KEY (`documenter_id`),
  -- Indexes availability plus last and first name for faster lookups.
  KEY `idx_documenter_availability_name` (`availability`,`last_name`,`first_name`)
-- Uses InnoDB and utf8mb4 so names with special characters work.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Visual divider before the applications table section.
-- --------------------------------------------------------

-- Comment divider introducing the applications table.
--
-- Says the next statement builds the applications table.
-- Table structure for table `applications`
-- Comment divider closing the applications header.
--

-- Creates applications: one row per student paper submission for a stage.
CREATE TABLE `applications` (
  -- application_id will become the primary key; it is auto-numbered later.
  `application_id` int(11) NOT NULL,
  -- user_id links the application to the student's users row.
  `user_id` int(11) NOT NULL,
  -- presentation_stage names the stage, for example Thesis Proposal.
  `presentation_stage` varchar(100) NOT NULL,
  -- paper_title stores the full title of the student's paper.
  `paper_title` text NOT NULL,
  -- status tracks the workflow step from submitted through completed.
  `status` enum('submitted','under_review','for_payment','payment_recorded','ready_for_presentation','scheduled','approved','requires_revision','completed') DEFAULT 'submitted',
  -- coordinator_comment stores the coordinator's feedback text.
  `coordinator_comment` text DEFAULT NULL,
  -- grad_school_endorsed is 1 when the graduate school endorsed the paper.
  `grad_school_endorsed` tinyint(1) NOT NULL DEFAULT 0,
  -- payment_recorded is 1 when a payment was already recorded.
  `payment_recorded` tinyint(1) NOT NULL DEFAULT 0,
  -- receipt_number keeps the official receipt number for the payment.
  `receipt_number` varchar(100) DEFAULT NULL,
  -- payment_date stores the day the payment was made.
  `payment_date` date DEFAULT NULL,
  -- payment_amount stores the amount paid, with two decimal places.
  `payment_amount` decimal(10,2) DEFAULT NULL,
  -- ready_for_presentation is 1 once the paper is cleared for defense.
  `ready_for_presentation` tinyint(1) NOT NULL DEFAULT 0,
  -- workflow_state keeps a JSON text snapshot of checklist progress.
  `workflow_state` text DEFAULT NULL,
  -- result stores the defense outcome, e.g. approved or requires_revision.
  `result` varchar(50) DEFAULT NULL,
  -- submitted_at records when the application was created.
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  -- updated_at refreshes automatically whenever the row changes.
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  -- archived_at marks when the row was archived; NULL means active.
  `archived_at` timestamp NULL DEFAULT NULL
-- Uses InnoDB storage and utf8mb4 text encoding.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comment divider introducing the applications data section.
--
-- Says the next INSERT fills applications with sample rows.
-- Dumping data for table `applications`
-- Comment divider closing the applications data header.
--

-- Inserts six demo applications and lists the 17 columns being filled.
INSERT INTO `applications` (`application_id`, `user_id`, `presentation_stage`, `paper_title`, `status`, `coordinator_comment`, `grad_school_endorsed`, `payment_recorded`, `receipt_number`, `payment_date`, `payment_amount`, `ready_for_presentation`, `workflow_state`, `result`, `submitted_at`, `updated_at`, `archived_at`) VALUES
-- Application 17: user 5's thesis proposal, currently under review.
(17, 5, 'Thesis Proposal', 'Machine Learning Approaches for Predictive Analytics in Graduate Education', 'under_review', '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, '2026-09-10 01:30:00', '2026-09-13 06:58:19', NULL),
-- Application 18: user 6's final thesis, submitted with the note 'Update this'.
(18, 6, 'Final Thesis', 'Development of an Automated Graduate Application Tracking System', 'submitted', 'Update this', 0, 0, NULL, NULL, NULL, 0, '[]', NULL, '2026-09-11 06:15:00', '2026-09-30 09:58:13', NULL),
-- Application 19: user 7's concept paper, scheduled with JSON schedule details.
(19, 7, 'Concept Paper', 'Cloud Security Protocols for Institutional Repositories', 'scheduled', '', 0, 0, NULL, NULL, NULL, 0, '{\"presentation_date\":\"Oct 10, 2026\",\"presentation_time\":\"2:00 AM\",\"presentation_venue\":\"das\",\"presentation_panel\":\"Dela Cruz, Juan (PhD in Computer Science), Fernandez, Lisa (MS in Information Technology), Fernandez, Lisa (MS in Information Technology)\"}', NULL, '2026-09-12 02:00:00', '2026-09-30 10:20:40', NULL),
-- Application 20: user 8's capstone proposal, approved and already paid.
(20, 8, 'Capstone Proposal', 'Mobile-Based Student Records and Notification Management', 'approved', '', 1, 1, '231231', '2026-09-30', 1.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\"}', 'approved', '2026-09-08 03:20:00', '2026-09-30 10:26:04', NULL),
-- Application 21: user 5's final defense, sent back for revision.
(21, 5, 'Final Thesis Defense', 'Optimizing Database Queries in Large-Scale Web Applications', 'requires_revision', 'Please strengthen the experimental validation chapter and resubmit.', 0, 0, NULL, NULL, NULL, 0, NULL, 'requires_revision', '2026-09-05 08:45:00', '2026-09-13 06:58:19', NULL),
-- Application 29: a new final thesis submission from user 8.
(29, 8, 'Final Thesis', 'Mobile-Based Student Records and Notification Management', 'submitted', NULL, 0, 0, NULL, NULL, NULL, 0, NULL, NULL, '2026-09-30 10:28:43', '2026-09-30 10:28:43', NULL);

-- Visual divider before the application_comments table section.
-- --------------------------------------------------------

-- Comment divider introducing the application_comments table.
--
-- Says the next statement builds the application_comments table.
-- Table structure for table `application_comments`
-- Comment divider closing the comments table header.
--

-- Creates application_comments: coordinator comments attached to applications.
CREATE TABLE `application_comments` (
  -- comment_id is the primary key and is auto-numbered later.
  `comment_id` int(11) NOT NULL,
  -- application_id says which application the comment belongs to.
  `application_id` int(11) NOT NULL,
  -- coordinator_user_id says which coordinator wrote the comment.
  `coordinator_user_id` int(11) NOT NULL,
  -- comment_text stores the actual message from the coordinator.
  `comment_text` text NOT NULL,
  -- created_at records when the comment was posted.
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
-- Uses InnoDB storage and utf8mb4 text encoding.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comment divider introducing the application comments data section.
--
-- Says the next INSERT fills application_comments.
-- Dumping data for table `application_comments`
-- Comment divider closing the comments data header.
--

-- Inserts one demo comment and names its five columns.
INSERT INTO `application_comments` (`comment_id`, `application_id`, `coordinator_user_id`, `comment_text`, `created_at`) VALUES
-- Comment 1 by coordinator 2 on application 18: 'Update this'.
(1, 18, 2, 'Update this', '2026-09-30 09:58:13');

-- Visual divider before the application_documents table section.
-- --------------------------------------------------------

-- Comment divider introducing the application_documents table.
--
-- Says the next statement builds the application_documents table.
-- Table structure for table `application_documents`
-- Comment divider closing the documents table header.
--

-- Creates application_documents: uploaded requirement files for a student.
CREATE TABLE `application_documents` (
  -- document_id is the primary key and is auto-numbered later.
  `document_id` int(11) NOT NULL,
  -- application_id links the file to an application; NULL is allowed.
  `application_id` int(11) DEFAULT NULL,
  -- user_id says which student uploaded the file.
  `user_id` int(11) NOT NULL,
  -- stage names the workflow stage the file belongs to.
  `stage` varchar(100) NOT NULL,
  -- document_type names the requirement, e.g. Final Capstone Paper.
  `document_type` varchar(150) NOT NULL,
  -- original_name is the file name exactly as the student uploaded it.
  `original_name` varchar(255) NOT NULL,
  -- stored_name is the renamed file saved on the server to avoid clashes.
  `stored_name` varchar(255) NOT NULL,
  -- mime_type records the file type, such as application/pdf.
  `mime_type` varchar(100) DEFAULT NULL,
  -- file_size stores the file size in bytes.
  `file_size` int(11) NOT NULL DEFAULT 0,
  -- status is submitted, verified, or incomplete; it defaults to submitted.
  `status` enum('submitted','verified','incomplete') NOT NULL DEFAULT 'submitted',
  -- uploaded_at records when the file was uploaded.
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
-- Uses InnoDB storage and utf8mb4 text encoding.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comment divider introducing the application documents data section.
--
-- Says the next INSERT fills application_documents.
-- Dumping data for table `application_documents`
-- Comment divider closing the documents data header.
--

-- Inserts one uploaded PDF and lists its eleven columns.
INSERT INTO `application_documents` (`document_id`, `application_id`, `user_id`, `stage`, `document_type`, `original_name`, `stored_name`, `mime_type`, `file_size`, `status`, `uploaded_at`) VALUES
-- Document 7: a 269 KB capstone PDF saved under a hashed file name.
(7, 29, 8, 'Final Capstone', 'Final Capstone Paper', '2016 Software Engineering (1).pdf', '522212cd8c61f130ff83a983cf33e012.pdf', 'application/pdf', 269283, 'submitted', '2026-09-30 10:28:43');

-- Visual divider before the panel_members table section.
-- --------------------------------------------------------

-- Comment divider introducing the panel_members table.
--
-- Says the next statement builds the panel_members table.
-- Table structure for table `panel_members`
-- Comment divider closing the panel members table header.
--

-- Creates panel_members: faculty who can sit on defense panels.
CREATE TABLE `panel_members` (
  -- panel_member_id is the primary key and is auto-numbered later.
  `panel_member_id` int(11) NOT NULL,
  -- first_name stores the panelist's given name.
  `first_name` varchar(100) NOT NULL,
  -- middle_name is optional and may be NULL.
  `middle_name` varchar(100) DEFAULT NULL,
  -- last_name stores the panelist's family name.
  `last_name` varchar(100) NOT NULL,
  -- qualification stores the panelist's degree.
  `qualification` varchar(150) NOT NULL,
  -- email is the panelist's contact address; it is made unique later.
  `email` varchar(100) DEFAULT NULL,
  -- availability is available/unavailable, defaulting to available.
  `availability` enum('available','unavailable') NOT NULL DEFAULT 'available',
  -- notes holds optional remarks about the panelist.
  `notes` text DEFAULT NULL,
  -- created_at records when the panelist row was added.
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  -- updated_at refreshes automatically whenever the row changes.
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
-- Uses InnoDB storage and utf8mb4 text encoding.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comment divider introducing the panel members data section.
--
-- Says the next INSERT fills panel_members.
-- Dumping data for table `panel_members`
-- Comment divider closing the panel members data header.
--

-- Inserts four demo panelists and lists the ten columns.
INSERT INTO `panel_members` (`panel_member_id`, `first_name`, `middle_name`, `last_name`, `qualification`, `email`, `availability`, `notes`, `created_at`, `updated_at`) VALUES
-- Panelist 1: Juan Dela Cruz, PhD in Computer Science, available.
(1, 'Juan', NULL, 'Dela Cruz', 'PhD in Computer Science', 'delacruz@adzu.edu.ph', 'available', 'Demo panel member', '2026-09-30 09:34:20', '2026-09-30 09:34:20'),
-- Panelist 2: Ana Reyes, PhD in Information Technology, available.
(2, 'Ana', NULL, 'Reyes', 'PhD in Information Technology', 'reyes@adzu.edu.ph', 'available', 'Demo panel member', '2026-09-30 09:34:20', '2026-09-30 09:34:20'),
-- Panelist 3: Miguel Santos, MS in Computer Science, available.
(3, 'Miguel', NULL, 'Santos', 'MS in Computer Science', 'santos@adzu.edu.ph', 'available', 'Demo panel member', '2026-09-30 09:34:20', '2026-09-30 09:34:20'),
-- Panelist 4: Lisa Fernandez, MS in Information Technology, available.
(4, 'Lisa', NULL, 'Fernandez', 'MS in Information Technology', 'fernandez@adzu.edu.ph', 'available', 'Demo panel member', '2026-09-30 09:34:20', '2026-09-30 09:34:20');

-- Visual divider before the payments table section.
-- --------------------------------------------------------

-- Comment divider introducing the payments table.
--
-- Says the next statement builds the payments table.
-- Table structure for table `payments`
-- Comment divider closing the payments table header.
--

-- Creates payments: official payment records tied to applications.
CREATE TABLE `payments` (
  -- payment_id is the primary key and is auto-numbered later.
  `payment_id` int(11) NOT NULL,
  -- application_id says which application the payment belongs to.
  `application_id` int(11) NOT NULL,
  -- recorded_by_user_id says which coordinator recorded the payment.
  `recorded_by_user_id` int(11) NOT NULL,
  -- receipt_number stores the unique official receipt number.
  `receipt_number` varchar(100) NOT NULL,
  -- payment_date stores the day the payment was received.
  `payment_date` date NOT NULL,
  -- amount stores the money paid, with two decimal places.
  `amount` decimal(10,2) NOT NULL,
  -- created_at records when the payment row was inserted.
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  -- updated_at refreshes automatically whenever the row changes.
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
-- Uses InnoDB storage and utf8mb4 text encoding.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comment divider introducing the payments data section.
--
-- Says the next INSERT fills payments.
-- Dumping data for table `payments`
-- Comment divider closing the payments data header.
--

-- Inserts one demo payment and lists its eight columns.
INSERT INTO `payments` (`payment_id`, `application_id`, `recorded_by_user_id`, `receipt_number`, `payment_date`, `amount`, `created_at`, `updated_at`) VALUES
-- Payment 1: receipt 231231 for application 20, amount 1.00.
(1, 20, 2, '231231', '2026-09-30', 1.00, '2026-09-30 10:26:04', '2026-09-30 10:26:04');

-- Visual divider before the programs table section.
-- --------------------------------------------------------

-- Comment divider introducing the programs table.
--
-- Says the next statement builds the programs table.
-- Table structure for table `programs`
-- Comment divider closing the programs table header.
--

-- Creates programs: degree programs offered, each linked to a track.
CREATE TABLE `programs` (
  -- program_id is the primary key and is auto-numbered later.
  `program_id` int(11) NOT NULL,
  -- track_id links the program to its workflow track.
  `track_id` int(11) NOT NULL,
  -- program_code is the short unique code, for example MSCS.
  `program_code` varchar(30) NOT NULL,
  -- program_name is the full program title shown to students.
  `program_name` varchar(150) NOT NULL,
  -- description optionally explains the program.
  `description` text DEFAULT NULL,
  -- is_active is 1 when the program is offered, 0 when it is hidden.
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  -- created_at records when the program row was added.
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
-- Uses InnoDB storage and utf8mb4 text encoding.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comment divider introducing the programs data section.
--
-- Says the next INSERT fills programs.
-- Dumping data for table `programs`
-- Comment divider closing the programs data header.
--

-- Inserts three demo programs and lists the seven columns.
INSERT INTO `programs` (`program_id`, `track_id`, `program_code`, `program_name`, `description`, `is_active`, `created_at`) VALUES
-- Program 1: MSCS, the thesis-based computer science master's program.
(1, 1, 'MSCS', 'Master of Science in Computer Science', 'Thesis-based graduate program', 1, '2026-09-26 09:34:52'),
-- Program 2: MIT, the capstone-based information technology program.
(2, 2, 'MIT', 'Master in Information Technology', 'Capstone-based graduate program', 1, '2026-09-26 09:34:52'),
-- Program 3: MATH, the seminar-paper mathematics program.
(3, 3, 'MATH', 'Master of Arts in Mathematics', 'Seminar paper workflow program', 1, '2026-09-26 09:34:53');

-- Visual divider before the schedules table section.
-- --------------------------------------------------------

-- Comment divider introducing the schedules table.
--
-- Says the next statement builds the schedules table.
-- Table structure for table `schedules`
-- Comment divider closing the schedules table header.
--

-- Creates schedules: defense date, time, and venue for an application.
CREATE TABLE `schedules` (
  -- schedule_id is the primary key and is auto-numbered later.
  `schedule_id` int(11) NOT NULL,
  -- application_id says which application is being scheduled.
  `application_id` int(11) NOT NULL,
  -- managed_by_user_id says which coordinator created the schedule.
  `managed_by_user_id` int(11) NOT NULL,
  -- presentation_date stores the defense date.
  `presentation_date` date NOT NULL,
  -- start_time stores the defense start time.
  `start_time` time NOT NULL,
  -- end_time optionally stores when the defense ends.
  `end_time` time DEFAULT NULL,
  -- venue stores where the defense will happen.
  `venue` varchar(200) NOT NULL,
  -- adviser_id optionally links to an adviser in advisor_pool.
  `adviser_id` int(11) DEFAULT NULL,
  -- documentor_first_name stores the documenter's given name.
  `documentor_first_name` varchar(100) DEFAULT NULL,
  -- documentor_middle_name stores the documenter's optional middle name.
  `documentor_middle_name` varchar(100) DEFAULT NULL,
  -- documentor_last_name stores the documenter's family name.
  `documentor_last_name` varchar(100) DEFAULT NULL,
  -- status is pending, confirmed, completed, or cancelled.
  `status` enum('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  -- notes holds optional remarks about the schedule.
  `notes` text DEFAULT NULL,
  -- created_at records when the schedule was created.
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  -- updated_at refreshes automatically whenever the row changes.
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
-- Uses InnoDB storage and utf8mb4 text encoding.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comment divider introducing the schedules data section.
--
-- Says the next INSERT fills schedules.
-- Dumping data for table `schedules`
-- Comment divider closing the schedules data header.
--

-- Inserts one demo defense schedule and lists its fifteen columns.
INSERT INTO `schedules` (`schedule_id`, `application_id`, `managed_by_user_id`, `presentation_date`, `start_time`, `end_time`, `venue`, `adviser_id`, `documentor_first_name`, `documentor_middle_name`, `documentor_last_name`, `status`, `notes`, `created_at`, `updated_at`) VALUES
-- Schedule 2: application 19 defends on Oct 10, 2026 at 2 AM in 'das'.
(2, 19, 2, '2026-10-10', '02:00:00', NULL, 'das', 1, 'Lisa', '(MS in Information Technology)', 'Fernandez', 'confirmed', NULL, '2026-09-30 10:20:40', '2026-09-30 10:20:40');

-- Visual divider before the schedule_panel_assignments table section.
-- --------------------------------------------------------

-- Comment divider introducing the schedule_panel_assignments table.
--
-- Says the next statement builds the panel assignment table.
-- Table structure for table `schedule_panel_assignments`
-- Comment divider closing the assignments table header.
--

-- Creates schedule_panel_assignments: which panelists serve at each defense.
CREATE TABLE `schedule_panel_assignments` (
  -- assignment_id is the primary key and is auto-numbered later.
  `assignment_id` int(11) NOT NULL,
  -- schedule_id points to the defense schedule.
  `schedule_id` int(11) NOT NULL,
  -- panel_member_id points to the assigned panelist.
  `panel_member_id` int(11) NOT NULL,
  -- panel_role is chair, member, adviser, or documentor; default is member.
  `panel_role` enum('chair','member','adviser','documentor') NOT NULL DEFAULT 'member',
  -- notes holds optional remarks about the assignment.
  `notes` text DEFAULT NULL,
  -- created_at records when the assignment was made.
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
-- Uses InnoDB storage and utf8mb4 text encoding.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comment divider introducing the panel assignments data section.
--
-- Says the next INSERT fills schedule_panel_assignments.
-- Dumping data for table `schedule_panel_assignments`
-- Comment divider closing the assignments data header.
--

-- Inserts two panel assignments and lists the six columns.
INSERT INTO `schedule_panel_assignments` (`assignment_id`, `schedule_id`, `panel_member_id`, `panel_role`, `notes`, `created_at`) VALUES
-- Assignment 1: panelist 1 serves as a member on schedule 2.
(1, 2, 1, 'member', NULL, '2026-09-30 10:20:40'),
-- Assignment 2: panelist 4 serves as a member on schedule 2.
(2, 2, 4, 'member', NULL, '2026-09-30 10:20:40');

-- Visual divider before the students table section.
-- --------------------------------------------------------

-- Comment divider introducing the students table.
--
-- Says the next statement builds the students table.
-- Table structure for table `students`
-- Comment divider closing the students table header.
--

-- Creates students: extra profile details for each student user.
CREATE TABLE `students` (
  -- student_id is the ADZU student number and the primary key.
  `student_id` int(11) NOT NULL,
  -- user_id links the profile to a users row; one profile per user.
  `user_id` int(11) NOT NULL,
  -- first_name stores the student's given name.
  `first_name` varchar(50) NOT NULL,
  -- last_name stores the student's family name.
  `last_name` varchar(50) NOT NULL,
  -- middle_initial stores one middle initial letter; it is optional.
  `middle_initial` varchar(5) DEFAULT NULL,
  -- age stores the student's age in years.
  `age` int(11) NOT NULL,
  -- gender stores the student's gender as text.
  `gender` varchar(20) NOT NULL,
  -- program stores the program name, for example MSCS (Thesis).
  `program` varchar(100) NOT NULL,
  -- track is thesis, capstone, or seminar.
  `track` enum('thesis','capstone','seminar') NOT NULL,
  -- adviser_name stores the adviser's name as plain text.
  `adviser_name` varchar(150) DEFAULT NULL,
  -- enrollment_date stores when the student enrolled.
  `enrollment_date` date DEFAULT NULL,
  -- archived_at marks when the profile was archived; NULL means active.
  `archived_at` timestamp NULL DEFAULT NULL
-- Uses InnoDB storage and utf8mb4 text encoding.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comment divider introducing the students data section.
--
-- Says the next INSERT fills students.
-- Dumping data for table `students`
-- Comment divider closing the students data header.
--

-- Inserts four student profiles and lists the twelve columns.
INSERT INTO `students` (`student_id`, `user_id`, `first_name`, `last_name`, `middle_initial`, `age`, `gender`, `program`, `track`, `adviser_name`, `enrollment_date`, `archived_at`) VALUES
-- Student 230159: Robbie Torres, MSCS thesis, enrolled Sep 12, 2026.
(230159, 5, 'Robbie', 'Torres', 'E', 21, 'Male', 'Master of Science in Computer Science (Thesis)', 'thesis', NULL, '2026-09-12', NULL),
-- Student 240092: John Gler, MIT capstone, enrolled Sep 13, 2026.
(240092, 8, 'John', 'Gler', NULL, 21, 'Male', 'Master in Information Technology (Capstone)', 'capstone', NULL, '2026-09-13', NULL),
-- Student 240234: Rhett Epino, MSCS thesis, enrolled Sep 13, 2026.
(240234, 7, 'Rhett', 'Epino', NULL, 21, 'Male', 'Master of Science in Computer Science (Thesis)', 'thesis', NULL, '2026-09-13', NULL),
-- Student 240527: MARC ARBILERA, MSCS thesis, enrolled Sep 12, 2026.
(240527, 6, 'MARC', 'ARBILERA', 'M', 21, 'Male', 'Master of Science in Computer Science (Thesis)', 'thesis', NULL, '2026-09-12', NULL);

-- Comment divider introducing the students triggers section.
--
-- Says the next statements define triggers on the students table.
-- Triggers `students`
-- Comment divider closing the triggers header.
--
-- Changes the delimiter to $$ so the trigger body can contain semicolons.
DELIMITER $$
-- Trigger: after a student insert, refresh that user's full_name value.
CREATE TRIGGER `students_after_insert_sync_user` AFTER INSERT ON `students` FOR EACH ROW UPDATE `users`
-- Builds 'Last, First M' and skips a blank middle initial.
SET `full_name` = TRIM(CONCAT(NEW.`last_name`, ', ', NEW.`first_name`, IF(NEW.`middle_initial` IS NULL OR TRIM(REPLACE(NEW.`middle_initial`, '.', '')) = '', '', CONCAT(' ', TRIM(REPLACE(NEW.`middle_initial`, '.', ''))))))
-- Applies the update only to the users row matching the new student.
WHERE `user_id` = NEW.`user_id`
-- Ends the trigger body using the temporary $$ delimiter.
$$
-- Restores the normal semicolon delimiter.
DELIMITER ;
-- Sets the $$ delimiter again for the next trigger definition.
DELIMITER $$
-- Trigger: after a student update, refresh that user's full_name value.
CREATE TRIGGER `students_after_update_sync_user` AFTER UPDATE ON `students` FOR EACH ROW UPDATE `users`
-- Rebuilds 'Last, First M' from the updated student values.
SET `full_name` = TRIM(CONCAT(NEW.`last_name`, ', ', NEW.`first_name`, IF(NEW.`middle_initial` IS NULL OR TRIM(REPLACE(NEW.`middle_initial`, '.', '')) = '', '', CONCAT(' ', TRIM(REPLACE(NEW.`middle_initial`, '.', ''))))))
-- Targets the users row linked to the updated student.
WHERE `user_id` = NEW.`user_id`
-- Ends the trigger body.
$$
-- Restores the normal semicolon delimiter.
DELIMITER ;
-- Sets the $$ delimiter for the auto-id trigger definition.
DELIMITER $$
-- Trigger: before insert, assign a student_id from the ADZU email if missing.
CREATE TRIGGER `students_before_insert_autoid` BEFORE INSERT ON `students` FOR EACH ROW BEGIN
  -- Declares a variable to hold the user's email address.
  DECLARE v_email VARCHAR(100) DEFAULT '';
  -- Declares a variable to hold the digits found in that email.
  DECLARE v_digits VARCHAR(20) DEFAULT '';
  -- Declares a counter to check whether an id is already taken.
  DECLARE v_taken INT DEFAULT 0;
  -- Existing comment: an explicitly typed ADZU ID number is respected.
  -- Respect an explicitly typed ADZU ID number; derive from the email only
  -- Existing comment: otherwise the id is derived from the email.
  -- when the row arrives without one (student_id 0/NULL from AUTO_INCREMENT).
  -- Runs the id logic only when no student_id was supplied (0 or NULL).
  IF (NEW.`student_id` IS NULL OR NEW.`student_id` = 0) THEN
  -- Reads the email of the users row this student belongs to.
  SELECT `email` INTO v_email FROM `users` WHERE `user_id` = NEW.`user_id` LIMIT 1;
  -- Continues only when a non-empty email was found.
  IF (v_email IS NOT NULL AND v_email <> '') THEN
    -- Keeps only the digits before the @ symbol, e.g. co259344 becomes 259344.
    SET v_digits = REGEXP_REPLACE(SUBSTRING_INDEX(v_email, '@', 1), '[^0-9]', '');
  -- Closes the check for a usable email.
  END IF;
  -- Continues only when some digits were extracted from the email.
  IF (v_digits IS NOT NULL AND v_digits <> '') THEN
    -- Existing comment: the fallback id is the number in the email.
    -- Fallback ID = numeric part of the ADZU email (e.g. co259344 -> 259344).
    -- Counts how many students already use this candidate id.
    SELECT COUNT(*) INTO v_taken FROM `students` WHERE `student_id` = CAST(v_digits AS UNSIGNED);
    -- Checks whether the candidate id is still free.
    IF (v_taken = 0) THEN
      -- Assigns the email digits as the new student_id value.
      SET NEW.`student_id` = CAST(v_digits AS UNSIGNED);
    -- Closes the 'id is free' block.
    END IF;
  -- Closes the 'digits were found' block.
  END IF;
  -- Closes the 'no student_id yet' block.
  END IF;
  -- Checks whether a middle initial was provided.
  IF (NEW.`middle_initial` IS NOT NULL) THEN
    -- Trims it, removes dots, and turns an empty result into NULL.
    SET NEW.`middle_initial` = NULLIF(TRIM(REPLACE(NEW.`middle_initial`, '.', '')), '');
  -- Closes the middle-initial cleanup block.
  END IF;
-- Ends the trigger body.
END
-- Ends the CREATE TRIGGER statement with the $$ delimiter.
$$
-- Restores the normal semicolon delimiter.
DELIMITER ;

-- Visual divider before the templates table section.
-- --------------------------------------------------------

-- Comment divider introducing the templates table.
--
-- Says the next statement builds the templates table.
-- Table structure for table `templates`
-- Comment divider closing the templates table header.
--

-- Creates templates: downloadable forms and reference files for each track.
CREATE TABLE `templates` (
  -- template_id is the primary key and is auto-numbered later.
  `template_id` int(11) NOT NULL,
  -- track_id says which track the template belongs to.
  `track_id` int(11) NOT NULL,
  -- stage_id optionally links the template to a workflow stage.
  `stage_id` int(11) DEFAULT NULL,
  -- template_name is the title shown to coordinators.
  `template_name` varchar(150) NOT NULL,
  -- description optionally explains what the file is for.
  `description` text DEFAULT NULL,
  -- document_type is form, template, or reference.
  `document_type` enum('form','template','reference') NOT NULL DEFAULT 'template',
  -- file_name stores the original file name.
  `file_name` varchar(255) NOT NULL,
  -- file_path stores where the file lives under assets/papers.
  `file_path` varchar(500) NOT NULL,
  -- mime_type records the file type, usually a Word document.
  `mime_type` varchar(100) DEFAULT NULL,
  -- is_active is 1 when the template is offered, 0 when it is hidden.
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  -- managed_by_user_id says which coordinator uploaded it.
  `managed_by_user_id` int(11) NOT NULL,
  -- created_at records when the template row was added.
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  -- updated_at refreshes automatically whenever the row changes.
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
-- Uses InnoDB storage and utf8mb4 text encoding.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comment divider introducing the templates data section.
--
-- Says the next INSERT fills templates.
-- Dumping data for table `templates`
-- Comment divider closing the templates data header.
--

-- Inserts 21 template files and lists the thirteen columns.
INSERT INTO `templates` (`template_id`, `track_id`, `stage_id`, `template_name`, `description`, `document_type`, `file_name`, `file_path`, `mime_type`, `is_active`, `managed_by_user_id`, `created_at`, `updated_at`) VALUES
-- Template 1: the official thesis format document for track 1.
(1, 1, 1, 'Graduate School Thesis Format', 'Official format for thesis documents', 'template', 'GRADUATE SCHOOL - CSITE - THESIS FORMAT.docx', 'assets/papers/THESIS/GRADUATE SCHOOL - CSITE - THESIS FORMAT.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
-- Template 2: concept paper adviser endorsement form, thesis stage 1.
(2, 1, 1, 'Concept Paper Adviser Endorsement Form', 'Signed by the research adviser', 'form', '1 CONCEPT PAPER ADVISER ENDORSEMENT FORM_.docx', 'assets/papers/THESIS/1 CONCEPT PAPER ADVISER ENDORSEMENT FORM_.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
-- Template 3: concept paper endorsement sent to the graduate school.
(3, 1, 1, 'Concept Paper Endorsement to Graduate School', 'Coordinator endorsement form', 'form', '2 CONCEPT PAPER ENDORSEMENT TO GRADSCHOOL.docx', 'assets/papers/THESIS/2 CONCEPT PAPER ENDORSEMENT TO GRADSCHOOL.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
-- Template 4: thesis proposal adviser endorsement form, stage 2.
(4, 1, 2, 'Thesis Proposal Adviser Endorsement Form', 'Signed by the research adviser', 'form', '3 THESIS PROPOSAL ADVISER ENDORSEMENT FORM.docx', 'assets/papers/THESIS/3 THESIS PROPOSAL ADVISER ENDORSEMENT FORM.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
-- Template 5: thesis proposal endorsement sent to the graduate school.
(5, 1, 2, 'Thesis Proposal Endorsement to Graduate School', 'Coordinator endorsement form', 'form', '4 THESIS PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'assets/papers/THESIS/4 THESIS PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
-- Template 6: final thesis adviser endorsement form, stage 3.
(6, 1, 3, 'Final Thesis Adviser Endorsement Form', 'Signed by the research adviser', 'form', '5 FINAL THESIS ADVISER ENDORSEMENT FORM.docx', 'assets/papers/THESIS/5 FINAL THESIS ADVISER ENDORSEMENT FORM.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
-- Template 7: final thesis endorsement sent to the graduate school.
(7, 1, 3, 'Final Thesis Endorsement to Graduate School', 'Coordinator endorsement form', 'form', '6 FINAL THESIS ENDORSEMENT TO GRADSCHOOL.docx', 'assets/papers/THESIS/6 FINAL THESIS ENDORSEMENT TO GRADSCHOOL.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
-- Template 8: final thesis cover page with no stage attached.
(8, 1, NULL, 'Final Thesis Cover Page', 'Final thesis cover page', 'reference', 'FINAL THESIS COVER PAGE v2026.docx', 'assets/papers/THESIS/FINAL THESIS COVER PAGE v2026.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
-- Template 9: capstone project proposal template for track 2.
(9, 2, 4, 'Capstone Project Proposal Template', 'Official capstone proposal template', 'template', 'MIT GradSchool CAPSTONE PROJECT PROPOSAL TEMPLATE v2025.docx', 'assets/papers/CAPSTONE/MIT GradSchool CAPSTONE PROJECT PROPOSAL TEMPLATE v2025.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
-- Template 10: capstone adviser endorsement form for the proposal.
(10, 2, 4, 'Capstone Adviser Endorsement Form', 'Signed by the capstone adviser', 'form', '1 CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'assets/papers/CAPSTONE/1 CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
-- Template 11: capstone proposal endorsement sent to the graduate school.
(11, 2, 4, 'Capstone Proposal Endorsement to Graduate School', 'Coordinator endorsement form', 'form', '2 CAPSTONE PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'assets/papers/CAPSTONE/2 CAPSTONE PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
-- Template 12: final capstone adviser endorsement form.
(12, 2, 5, 'Final Capstone Adviser Endorsement Form', 'Signed by the capstone adviser', 'form', '3 FINAL CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'assets/papers/CAPSTONE/3 FINAL CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
-- Template 13: final capstone endorsement sent to the graduate school.
(13, 2, 5, 'Final Capstone Endorsement to Graduate School', 'Coordinator endorsement form', 'form', '4 FINAL CAPSTONE ENDORSEMENT TO GRADSCHOOL.docx', 'assets/papers/CAPSTONE/4 FINAL CAPSTONE ENDORSEMENT TO GRADSCHOOL.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
-- Template 14: capstone guidelines reference file with no stage.
(14, 2, NULL, 'Capstone Guidelines', 'Capstone project guidelines', 'reference', 'MIT Capstone Guidelines.docx', 'assets/papers/CAPSTONE/MIT Capstone Guidelines.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
-- Template 15: final capstone cover page reference file.
(15, 2, NULL, 'Final Capstone Cover Page', 'Final capstone cover page', 'reference', 'FINAL CAPSTONE COVER PAGE v2026.docx', 'assets/papers/CAPSTONE/FINAL CAPSTONE COVER PAGE v2026.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
-- Template 16: seminar paper template format for track 3.
(16, 3, 6, 'Seminar Paper Template Format', 'Official seminar paper format', 'template', 'MATH SEMINAR PAPER TEMPLATE FORMAT.docx', 'assets/papers/SEMINAR/MATH SEMINAR PAPER TEMPLATE FORMAT.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
-- Template 17: seminar paper proposal adviser endorsement form.
(17, 3, 6, 'Seminar Paper Proposal Adviser Endorsement Form', 'Signed by the research adviser', 'form', '1 SEMINAR PAPER PROPOSAL ADVISER ENDORSEMENT FORM .docx', 'assets/papers/SEMINAR/1 SEMINAR PAPER PROPOSAL ADVISER ENDORSEMENT FORM .docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
-- Template 18: seminar paper proposal endorsement to the grad school.
(18, 3, 6, 'Seminar Paper Proposal Endorsement to Graduate School', 'Coordinator endorsement form', 'form', '2 SEMINAR PAPER PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'assets/papers/SEMINAR/2 SEMINAR PAPER PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
-- Template 19: final seminar paper adviser endorsement form.
(19, 3, 7, 'Final Seminar Paper Adviser Endorsement Form', 'Signed by the research adviser', 'form', '3 FINAL SEMINAR PAPER ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'assets/papers/SEMINAR/3 FINAL SEMINAR PAPER ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
-- Template 20: final seminar paper endorsement to the grad school.
(20, 3, 7, 'Final Seminar Paper Endorsement to Graduate School', 'Coordinator endorsement form', 'form', '2 SEMINAR PAPER PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'assets/papers/SEMINAR/2 SEMINAR PAPER PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23'),
-- Template 21: final seminar paper cover page reference file.
(21, 3, NULL, 'Final Seminar Paper Cover Page', 'Final seminar paper cover page', 'reference', 'FINAL SEMINAR PAPER COVER PAGE v2026.docx', 'assets/papers/SEMINAR/FINAL SEMINAR PAPER COVER PAGE v2026.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 2, '2026-09-30 09:20:23', '2026-09-30 09:20:23');

-- Visual divider before the tracks table section.
-- --------------------------------------------------------

-- Comment divider introducing the tracks table.
--
-- Says the next statement builds the tracks table.
-- Table structure for table `tracks`
-- Comment divider closing the tracks table header.
--

-- Creates tracks: the three paper workflows (thesis, capstone, seminar).
CREATE TABLE `tracks` (
  -- track_id is the primary key and is auto-numbered later.
  `track_id` int(11) NOT NULL,
  -- track_code is the short unique code, for example thesis.
  `track_code` varchar(30) NOT NULL,
  -- track_name is the readable name shown in the app.
  `track_name` varchar(100) NOT NULL,
  -- description optionally explains the workflow.
  `description` text DEFAULT NULL,
  -- created_at records when the track row was added.
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
-- Uses InnoDB storage and utf8mb4 text encoding.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comment divider introducing the tracks data section.
--
-- Says the next INSERT fills tracks.
-- Dumping data for table `tracks`
-- Comment divider closing the tracks data header.
--

-- Inserts the three workflow tracks and lists the five columns.
INSERT INTO `tracks` (`track_id`, `track_code`, `track_name`, `description`, `created_at`) VALUES
-- Track 1: thesis, the graduate thesis workflow.
(1, 'thesis', 'Thesis', 'Graduate thesis workflow', '2026-09-26 09:34:52'),
-- Track 2: capstone, the capstone project workflow.
(2, 'capstone', 'Capstone', 'Capstone workflow', '2026-09-26 09:34:52'),
-- Track 3: seminar, the seminar paper workflow.
(3, 'seminar', 'Seminar Paper', 'Seminar paper workflow', '2026-09-26 09:34:52');

-- Visual divider before the users table section.
-- --------------------------------------------------------

-- Comment divider introducing the users table.
--
-- Says the next statement builds the users table.
-- Table structure for table `users`
-- Comment divider closing the users table header.
--

-- Creates users: login accounts for students, coordinators, panels, advisers.
CREATE TABLE `users` (
  -- user_id is the primary key and is auto-numbered later.
  `user_id` int(11) NOT NULL,
  -- full_name stores the display name, usually written 'Last, First'.
  `full_name` varchar(150) NOT NULL,
  -- email is the unique login name.
  `email` varchar(100) NOT NULL,
  -- password stores the hashed password, never the plain text.
  `password` varchar(255) NOT NULL,
  -- role is student, coordinator, panel, or adviser.
  `role` enum('student','coordinator','panel','adviser') NOT NULL,
  -- created_at records when the account was made.
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
-- Uses InnoDB storage and utf8mb4 text encoding.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comment divider introducing the users data section.
--
-- Says the next INSERT fills users.
-- Dumping data for table `users`
-- Comment divider closing the users data header.
--

-- Inserts five demo accounts and lists the six columns.
INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `role`, `created_at`) VALUES
-- User 2: Precious Opinion, coordinator using the grad school email.
(2, 'Opinion, Precious', 'gpc-csite@adzu.edu.ph', '$2y$10$/GU6.G4XuEPjhuMik5iHCOo5Fbxb1EjYnpNxHbyt4.Rrcf.6lfhH2', 'coordinator', '2026-09-12 08:35:44'),
-- User 5: Robbie Torres, student account with a bcrypt password hash.
(5, 'Torres, Robbie E', 'co230159@adzu.edu.ph', '$2y$10$bCJWuc15jVKGbM7NoIkVGuHNiKkzMgg6BFTmPxYUSf377Ld94Uvp6', 'student', '2026-09-12 08:56:22'),
-- User 6: MARC ARBILERA, student account with a bcrypt password hash.
(6, 'ARBILERA, MARC M', 'co240527@adzu.edu.ph', '$2y$10$cedOtistAVNlRw0mLqcfs.BpZHbOPvOK/TCMlkYvtAo/QDrrEZ1Ka', 'student', '2026-09-12 09:01:46'),
-- User 7: Rhett Epino, student account with a bcrypt password hash.
(7, 'Epino, Rhett', 'co240234@adzu.edu.ph', '$2y$10$gs/sMbuaDzBd8PysrKHuruO3QR.dD47/yRQZD8Ir.QmDHa5/MTE8a', 'student', '2026-09-13 06:25:29'),
-- User 8: John Gler, student account with a bcrypt password hash.
(8, 'Gler, John', 'co240092@adzu.edu.ph', '$2y$10$CLoYu2tFUOlcWaAN7Oy58ejJHTq5E9qgamndnNcHXyinTGphbN5PW', 'student', '2026-09-13 06:27:11');

-- Visual divider before the v_students_alpha view placeholder.
-- --------------------------------------------------------

-- Comment divider introducing the v_students_alpha placeholder.
--
-- Placeholder table standing in for the v_students_alpha view.
-- Stand-in structure for view `v_students_alpha`
-- Notes that the real view definition appears later in the file.
-- (See below for the actual view)
-- Comment divider closing the placeholder header.
--
-- Creates a fake table so the dump can describe the view's columns.
CREATE TABLE `v_students_alpha` (
-- Column student_id of the view, copied from students.
`student_id` int(11)
-- Column user_id of the view, copied from students.
,`user_id` int(11)
-- Column first_name of the view, copied from students.
,`first_name` varchar(50)
-- Column last_name of the view, copied from students.
,`last_name` varchar(50)
-- Column middle_initial of the view, copied from students.
,`middle_initial` varchar(5)
-- Column display_name: a 108-character 'Last, First M' label.
,`display_name` varchar(108)
-- Column age of the view, copied from students.
,`age` int(11)
-- Column gender of the view, copied from students.
,`gender` varchar(20)
-- Column program of the view, copied from students.
,`program` varchar(100)
-- Column track of the view, copied from students.
,`track` enum('thesis','capstone','seminar')
-- Column adviser_name of the view, copied from students.
,`adviser_name` varchar(150)
-- Column enrollment_date of the view, copied from students.
,`enrollment_date` date
-- Column archived_at of the view, copied from students.
,`archived_at` timestamp
-- Closes the placeholder table definition.
);

-- Visual divider before the v_users_alpha view placeholder.
-- --------------------------------------------------------

-- Comment divider introducing the v_users_alpha placeholder.
--
-- Placeholder table standing in for the v_users_alpha view.
-- Stand-in structure for view `v_users_alpha`
-- Notes that the real view definition appears later in the file.
-- (See below for the actual view)
-- Comment divider closing the placeholder header.
--
-- Creates a fake table describing the v_users_alpha view's columns.
CREATE TABLE `v_users_alpha` (
-- Column user_id of the view, copied from users.
`user_id` int(11)
-- Column full_name of the view, copied from users.
,`full_name` varchar(150)
-- Column email of the view, copied from users.
,`email` varchar(100)
-- Column role of the view, copied from users.
,`role` enum('student','coordinator','panel','adviser')
-- Column created_at of the view, copied from users.
,`created_at` timestamp
-- Column last_name: the part of full_name before the comma.
,`last_name` varchar(150)
-- Column first_name: the part after the comma, trimmed.
,`first_name` varchar(150)
-- Closes the placeholder table definition.
);

-- Visual divider before the workflow_stages table section.
-- --------------------------------------------------------

-- Comment divider introducing the workflow_stages table.
--
-- Says the next statement builds the workflow_stages table.
-- Table structure for table `workflow_stages`
-- Comment divider closing the workflow stages table header.
--

-- Creates workflow_stages: ordered steps that belong to each track.
CREATE TABLE `workflow_stages` (
  -- stage_id is the primary key and is auto-numbered later.
  `stage_id` int(11) NOT NULL,
  -- track_id says which track the stage belongs to.
  `track_id` int(11) NOT NULL,
  -- stage_key is a short machine name, e.g. concept or final.
  `stage_key` varchar(50) NOT NULL,
  -- stage_label is the human-readable stage name.
  `stage_label` varchar(100) NOT NULL,
  -- stage_order gives the position of the stage in the workflow.
  `stage_order` int(11) NOT NULL DEFAULT 1,
  -- is_required is 1 when the stage must be completed.
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  -- created_at records when the stage row was added.
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
-- Uses InnoDB storage and utf8mb4 text encoding.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comment divider introducing the workflow stages data section.
--
-- Says the next INSERT fills workflow_stages.
-- Dumping data for table `workflow_stages`
-- Comment divider closing the stages data header.
--

-- Inserts seven workflow stages and lists the seven columns.
INSERT INTO `workflow_stages` (`stage_id`, `track_id`, `stage_key`, `stage_label`, `stage_order`, `is_required`, `created_at`) VALUES
-- Stage 1: Concept Paper, the first step of the thesis track.
(1, 1, 'concept', 'Concept Paper', 1, 1, '2026-09-26 09:34:58'),
-- Stage 2: Thesis Proposal, the second step of the thesis track.
(2, 1, 'proposal', 'Thesis Proposal', 2, 1, '2026-09-26 09:34:58'),
-- Stage 3: Final Thesis, the third step of the thesis track.
(3, 1, 'final', 'Final Thesis', 3, 1, '2026-09-26 09:34:58'),
-- Stage 4: Capstone Proposal, the first step of the capstone track.
(4, 2, 'proposal', 'Capstone Proposal', 1, 1, '2026-09-26 09:34:58'),
-- Stage 5: Final Capstone, the second step of the capstone track.
(5, 2, 'final', 'Final Capstone', 2, 1, '2026-09-26 09:34:58'),
-- Stage 6: Seminar Paper Proposal, first step of the seminar track.
(6, 3, 'proposal', 'Seminar Paper Proposal', 1, 1, '2026-09-26 09:34:58'),
-- Stage 7: Final Seminar Paper, second step of the seminar track.
(7, 3, 'final', 'Final Seminar Paper', 2, 1, '2026-09-26 09:34:58');

-- Visual divider before the real view definitions.
-- --------------------------------------------------------

-- Comment divider introducing the real v_students_alpha view.
--
-- Says the next statements create the v_students_alpha view.
-- Structure for view `v_students_alpha`
-- Comment divider closing the view header.
--
-- Drops the placeholder table before the real view is created.
DROP TABLE IF EXISTS `v_students_alpha`;

-- Creates the view: students sorted by name, with a combined display name.
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_students_alpha`  AS SELECT `students`.`student_id` AS `student_id`, `students`.`user_id` AS `user_id`, `students`.`first_name` AS `first_name`, `students`.`last_name` AS `last_name`, `students`.`middle_initial` AS `middle_initial`, trim(concat(`students`.`last_name`,', ',`students`.`first_name`,if(`students`.`middle_initial` is null or `students`.`middle_initial` = '','',concat(' ',`students`.`middle_initial`)))) AS `display_name`, `students`.`age` AS `age`, `students`.`gender` AS `gender`, `students`.`program` AS `program`, `students`.`track` AS `track`, `students`.`adviser_name` AS `adviser_name`, `students`.`enrollment_date` AS `enrollment_date`, `students`.`archived_at` AS `archived_at` FROM `students` ORDER BY `students`.`last_name` ASC, `students`.`first_name` ASC LIMIT 0, 9223372036854775807 ;

-- Visual divider before the v_users_alpha view definition.
-- --------------------------------------------------------

-- Comment divider introducing the real v_users_alpha view.
--
-- Says the next statements create the v_users_alpha view.
-- Structure for view `v_users_alpha`
-- Comment divider closing the view header.
--
-- Drops the placeholder table before the real view is created.
DROP TABLE IF EXISTS `v_users_alpha`;

-- Creates the view: users sorted by name with last and first name split out.
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_users_alpha`  AS SELECT `users`.`user_id` AS `user_id`, `users`.`full_name` AS `full_name`, `users`.`email` AS `email`, `users`.`role` AS `role`, `users`.`created_at` AS `created_at`, substring_index(`users`.`full_name`,',',1) AS `last_name`, trim(substring_index(`users`.`full_name`,',',-1)) AS `first_name` FROM `users` ORDER BY substring_index(`users`.`full_name`,',',1) ASC, substring_index(`users`.`full_name`,',',-1) ASC LIMIT 0, 9223372036854775807 ;

-- Comment divider for the index section.
--
-- Says the next block adds indexes to the dumped tables.
-- Indexes for dumped tables
-- Comment divider closing the index section header.
--

-- Comment divider introducing the advisor_pool indexes.
--
-- Says the next ALTER adds indexes to advisor_pool.
-- Indexes for table `advisor_pool`
-- Comment divider closing the advisor_pool index header.
--
-- Starts altering advisor_pool to add its key.
ALTER TABLE `advisor_pool`
  -- Makes adviser_id the primary key, which is unique and never null.
  ADD PRIMARY KEY (`adviser_id`);

-- Comment divider introducing the applications indexes.
--
-- Says the next ALTER adds indexes to applications.
-- Indexes for table `applications`
-- Comment divider closing the applications index header.
--
-- Starts altering applications to add its primary key and index.
ALTER TABLE `applications`
  -- Makes application_id the primary key.
  ADD PRIMARY KEY (`application_id`),
  -- Indexes user_id so one student's applications are found quickly.
  ADD KEY `idx_applications_user_id` (`user_id`);

-- Comment divider introducing the application_comments indexes.
--
-- Says the next ALTER adds indexes to application_comments.
-- Indexes for table `application_comments`
-- Comment divider closing the comments index header.
--
-- Starts altering application_comments to add its keys.
ALTER TABLE `application_comments`
  -- Makes comment_id the primary key.
  ADD PRIMARY KEY (`comment_id`),
  -- Indexes application_id plus created_at for ordered comment lists.
  ADD KEY `idx_application_comments_application` (`application_id`,`created_at`),
  -- Indexes coordinator_user_id to find comments by coordinator.
  ADD KEY `idx_application_comments_coordinator` (`coordinator_user_id`);

-- Comment divider introducing the application_documents indexes.
--
-- Says the next ALTER adds indexes to application_documents.
-- Indexes for table `application_documents`
-- Comment divider closing the documents index header.
--
-- Starts altering application_documents to add its keys.
ALTER TABLE `application_documents`
  -- Makes document_id the primary key.
  ADD PRIMARY KEY (`document_id`),
  -- Indexes application_id to list a paper's files quickly.
  ADD KEY `application_documents_application_id` (`application_id`),
  -- Indexes user_id to list all files uploaded by a student.
  ADD KEY `idx_application_documents_user_id` (`user_id`);

-- Comment divider introducing the panel_members indexes.
--
-- Says the next ALTER adds indexes to panel_members.
-- Indexes for table `panel_members`
-- Comment divider closing the panel members index header.
--
-- Starts altering panel_members to add its keys.
ALTER TABLE `panel_members`
  -- Makes panel_member_id the primary key.
  ADD PRIMARY KEY (`panel_member_id`),
  -- Forces email to be unique so two panelists cannot share one address.
  ADD UNIQUE KEY `uq_panel_members_email` (`email`),
  -- Indexes last, first, and middle names for sorted name searches.
  ADD KEY `idx_panel_members_name` (`last_name`,`first_name`,`middle_name`);

-- Comment divider introducing the payments indexes.
--
-- Says the next ALTER adds indexes to payments.
-- Indexes for table `payments`
-- Comment divider closing the payments index header.
--
-- Starts altering payments to add its keys.
ALTER TABLE `payments`
  -- Makes payment_id the primary key.
  ADD PRIMARY KEY (`payment_id`),
  -- Allows only one payment row per application.
  ADD UNIQUE KEY `uq_payments_application` (`application_id`),
  -- Forces every receipt number to be unique.
  ADD UNIQUE KEY `uq_payments_receipt_number` (`receipt_number`),
  -- Indexes recorded_by_user_id to find payments by coordinator.
  ADD KEY `idx_payments_recorder` (`recorded_by_user_id`);

-- Comment divider introducing the programs indexes.
--
-- Says the next ALTER adds indexes to programs.
-- Indexes for table `programs`
-- Comment divider closing the programs index header.
--
-- Starts altering programs to add its keys.
ALTER TABLE `programs`
  -- Makes program_id the primary key.
  ADD PRIMARY KEY (`program_id`),
  -- Forces program codes such as MSCS to be unique.
  ADD UNIQUE KEY `uq_programs_code` (`program_code`),
  -- Indexes track_id to list programs under a track.
  ADD KEY `idx_programs_track` (`track_id`);

-- Comment divider introducing the schedules indexes.
--
-- Says the next ALTER adds indexes to schedules.
-- Indexes for table `schedules`
-- Comment divider closing the schedules index header.
--
-- Starts altering schedules to add its keys.
ALTER TABLE `schedules`
  -- Makes schedule_id the primary key.
  ADD PRIMARY KEY (`schedule_id`),
  -- Allows only one schedule per application.
  ADD UNIQUE KEY `uq_schedules_application` (`application_id`),
  -- Indexes presentation_date plus start_time for calendar lookups.
  ADD KEY `idx_schedules_date_time` (`presentation_date`,`start_time`),
  -- Indexes managed_by_user_id to find schedules by coordinator.
  ADD KEY `idx_schedules_manager` (`managed_by_user_id`),
  -- Indexes adviser_id to find defenses handled by an adviser.
  ADD KEY `idx_schedules_adviser` (`adviser_id`);

-- Comment divider introducing the panel assignment indexes.
--
-- Says the next ALTER adds indexes to schedule_panel_assignments.
-- Indexes for table `schedule_panel_assignments`
-- Comment divider closing the assignments index header.
--
-- Starts altering schedule_panel_assignments to add its keys.
ALTER TABLE `schedule_panel_assignments`
  -- Makes assignment_id the primary key.
  ADD PRIMARY KEY (`assignment_id`),
  -- Stops the same panelist being added twice to the same schedule.
  ADD UNIQUE KEY `uq_schedule_panel_member` (`schedule_id`,`panel_member_id`),
  -- Indexes panel_member_id to find all of a panelist's assignments.
  ADD KEY `idx_assignments_panel_member` (`panel_member_id`);

-- Comment divider introducing the students indexes.
--
-- Says the next ALTER adds indexes to students.
-- Indexes for table `students`
-- Comment divider closing the students index header.
--
-- Starts altering students to add its keys.
ALTER TABLE `students`
  -- Makes student_id the primary key.
  ADD PRIMARY KEY (`student_id`),
  -- Forces each user to have at most one student profile.
  ADD UNIQUE KEY `user_id` (`user_id`),
  -- Indexes last and first name for alphabetical student lists.
  ADD KEY `idx_students_alpha` (`last_name`,`first_name`);

-- Comment divider introducing the templates indexes.
--
-- Says the next ALTER adds indexes to templates.
-- Indexes for table `templates`
-- Comment divider closing the templates index header.
--
-- Starts altering templates to add its keys.
ALTER TABLE `templates`
  -- Makes template_id the primary key.
  ADD PRIMARY KEY (`template_id`),
  -- Indexes track_id and stage_id to find templates by workflow step.
  ADD KEY `idx_templates_track_stage` (`track_id`,`stage_id`),
  -- Indexes managed_by_user_id to find templates by uploader.
  ADD KEY `idx_templates_manager` (`managed_by_user_id`),
  -- Adds a supporting index for the stage_id foreign key.
  ADD KEY `fk_templates_stage` (`stage_id`);

-- Comment divider introducing the tracks indexes.
--
-- Says the next ALTER adds indexes to tracks.
-- Indexes for table `tracks`
-- Comment divider closing the tracks index header.
--
-- Starts altering tracks to add its keys.
ALTER TABLE `tracks`
  -- Makes track_id the primary key.
  ADD PRIMARY KEY (`track_id`),
  -- Forces track codes such as thesis to be unique.
  ADD UNIQUE KEY `uq_tracks_code` (`track_code`);

-- Comment divider introducing the users indexes.
--
-- Says the next ALTER adds indexes to users.
-- Indexes for table `users`
-- Comment divider closing the users index header.
--
-- Starts altering users to add its keys.
ALTER TABLE `users`
  -- Makes user_id the primary key.
  ADD PRIMARY KEY (`user_id`),
  -- Forces every account email to be unique.
  ADD UNIQUE KEY `email` (`email`),
  -- Indexes full_name for name-based searches and sorting.
  ADD KEY `idx_users_full_name` (`full_name`);

-- Comment divider introducing the workflow_stages indexes.
--
-- Says the next ALTER adds indexes to workflow_stages.
-- Indexes for table `workflow_stages`
-- Comment divider closing the stages index header.
--
-- Starts altering workflow_stages to add its keys.
ALTER TABLE `workflow_stages`
  -- Makes stage_id the primary key.
  ADD PRIMARY KEY (`stage_id`),
  -- Stops the same stage key appearing twice in one track.
  ADD UNIQUE KEY `uq_workflow_stage` (`track_id`,`stage_key`),
  -- Indexes track_id and stage_order for ordered stage lists.
  ADD KEY `idx_workflow_track_order` (`track_id`,`stage_order`);

-- Comment divider for the AUTO_INCREMENT section.
--
-- Says the next block sets the auto-number starting points.
-- AUTO_INCREMENT for dumped tables
-- Comment divider closing the AUTO_INCREMENT section header.
--

-- Comment divider introducing the advisor_pool auto-increment.
--
-- Says the next ALTER sets the advisor_pool counter.
-- AUTO_INCREMENT for table `advisor_pool`
-- Comment divider closing the advisor counter header.
--
-- Starts modifying the adviser_id column.
ALTER TABLE `advisor_pool`
  -- Makes adviser_id auto-numbered and the next value 7.
  MODIFY `adviser_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

-- Comment divider introducing the applications auto-increment.
--
-- Says the next ALTER sets the applications counter.
-- AUTO_INCREMENT for table `applications`
-- Comment divider closing the applications counter header.
--
-- Starts modifying the application_id column.
ALTER TABLE `applications`
  -- Makes application_id auto-numbered and the next value 30.
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

-- Comment divider introducing the application_comments auto-increment.
--
-- Says the next ALTER sets the application_comments counter.
-- AUTO_INCREMENT for table `application_comments`
-- Comment divider closing the comments counter header.
--
-- Starts modifying the comment_id column.
ALTER TABLE `application_comments`
  -- Makes comment_id auto-numbered and the next value 2.
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

-- Comment divider introducing the application_documents auto-increment.
--
-- Says the next ALTER sets the application_documents counter.
-- AUTO_INCREMENT for table `application_documents`
-- Comment divider closing the documents counter header.
--
-- Starts modifying the document_id column.
ALTER TABLE `application_documents`
  -- Makes document_id auto-numbered and the next value 8.
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

-- Comment divider introducing the panel_members auto-increment.
--
-- Says the next ALTER sets the panel_members counter.
-- AUTO_INCREMENT for table `panel_members`
-- Comment divider closing the panel members counter header.
--
-- Starts modifying the panel_member_id column.
ALTER TABLE `panel_members`
  -- Makes panel_member_id auto-numbered and the next value 6.
  MODIFY `panel_member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

-- Comment divider introducing the payments auto-increment.
--
-- Says the next ALTER sets the payments counter.
-- AUTO_INCREMENT for table `payments`
-- Comment divider closing the payments counter header.
--
-- Starts modifying the payment_id column.
ALTER TABLE `payments`
  -- Makes payment_id auto-numbered and the next value 3.
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

-- Comment divider introducing the programs auto-increment.
--
-- Says the next ALTER sets the programs counter.
-- AUTO_INCREMENT for table `programs`
-- Comment divider closing the programs counter header.
--
-- Starts modifying the program_id column.
ALTER TABLE `programs`
  -- Makes program_id auto-numbered and the next value 4.
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

-- Comment divider introducing the schedules auto-increment.
--
-- Says the next ALTER sets the schedules counter.
-- AUTO_INCREMENT for table `schedules`
-- Comment divider closing the schedules counter header.
--
-- Starts modifying the schedule_id column.
ALTER TABLE `schedules`
  -- Makes schedule_id auto-numbered and the next value 3.
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

-- Comment divider introducing the schedule_panel_assignments auto-increment.
--
-- Says the next ALTER sets the schedule_panel_assignments counter.
-- AUTO_INCREMENT for table `schedule_panel_assignments`
-- Comment divider closing the assignments counter header.
--
-- Starts modifying the assignment_id column.
ALTER TABLE `schedule_panel_assignments`
  -- Makes assignment_id auto-numbered and the next value 3.
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

-- Comment divider introducing the students auto-increment.
--
-- Says the next ALTER sets the students counter.
-- AUTO_INCREMENT for table `students`
-- Comment divider closing the students counter header.
--
-- Starts modifying the student_id column.
ALTER TABLE `students`
  -- Makes student_id auto-numbered and the next value 240528.
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=240528;

-- Comment divider introducing the templates auto-increment.
--
-- Says the next ALTER sets the templates counter.
-- AUTO_INCREMENT for table `templates`
-- Comment divider closing the templates counter header.
--
-- Starts modifying the template_id column.
ALTER TABLE `templates`
  -- Makes template_id auto-numbered and the next value 33.
  MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

-- Comment divider introducing the tracks auto-increment.
--
-- Says the next ALTER sets the tracks counter.
-- AUTO_INCREMENT for table `tracks`
-- Comment divider closing the tracks counter header.
--
-- Starts modifying the track_id column.
ALTER TABLE `tracks`
  -- Makes track_id auto-numbered and the next value 4.
  MODIFY `track_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

-- Comment divider introducing the users auto-increment.
--
-- Says the next ALTER sets the users counter.
-- AUTO_INCREMENT for table `users`
-- Comment divider closing the users counter header.
--
-- Starts modifying the user_id column.
ALTER TABLE `users`
  -- Makes user_id auto-numbered and the next value 12.
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

-- Comment divider introducing the workflow_stages auto-increment.
--
-- Says the next ALTER sets the workflow_stages counter.
-- AUTO_INCREMENT for table `workflow_stages`
-- Comment divider closing the stages counter header.
--
-- Starts modifying the stage_id column.
ALTER TABLE `workflow_stages`
  -- Makes stage_id auto-numbered and the next value 8.
  MODIFY `stage_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

-- Comment divider for the foreign key constraints section.
--
-- Says the next block adds foreign keys that link the tables.
-- Constraints for dumped tables
-- Comment divider closing the constraints section header.
--

-- Comment divider introducing the applications constraints.
--
-- Says the next ALTER adds foreign keys to applications.
-- Constraints for table `applications`
-- Comment divider closing the applications constraint header.
--
-- Starts altering applications to add its foreign key.
ALTER TABLE `applications`
  -- user_id must exist in users; deleting a user deletes their applications.
  ADD CONSTRAINT `applications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

-- Comment divider introducing the application_comments constraints.
--
-- Says the next ALTER adds foreign keys to application_comments.
-- Constraints for table `application_comments`
-- Comment divider closing the comments constraint header.
--
-- Starts altering application_comments to add its foreign keys.
ALTER TABLE `application_comments`
  -- application_id must exist in applications; deleting it deletes the comments.
  ADD CONSTRAINT `fk_application_comments_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  -- coordinator_user_id must exist in users; user id updates cascade.
  ADD CONSTRAINT `fk_application_comments_coordinator` FOREIGN KEY (`coordinator_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

-- Comment divider introducing the application_documents constraints.
--
-- Says the next ALTER adds foreign keys to application_documents.
-- Constraints for table `application_documents`
-- Comment divider closing the documents constraint header.
--
-- Starts altering application_documents to add its foreign keys.
ALTER TABLE `application_documents`
  -- application_id links to applications; if that is deleted the value becomes NULL.
  ADD CONSTRAINT `application_documents_application_fk` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE SET NULL,
  -- user_id must exist in users; deleting a user deletes their uploaded files.
  ADD CONSTRAINT `application_documents_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

-- Comment divider introducing the payments constraints.
--
-- Says the next ALTER adds foreign keys to payments.
-- Constraints for table `payments`
-- Comment divider closing the payments constraint header.
--
-- Starts altering payments to add its foreign keys.
ALTER TABLE `payments`
  -- application_id links to applications; deleting one deletes its payment.
  ADD CONSTRAINT `fk_payments_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  -- recorded_by_user_id must exist in users; user id updates cascade.
  ADD CONSTRAINT `fk_payments_recorder` FOREIGN KEY (`recorded_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

-- Comment divider introducing the programs constraints.
--
-- Says the next ALTER adds foreign keys to programs.
-- Constraints for table `programs`
-- Comment divider closing the programs constraint header.
--
-- Starts altering programs to add its foreign key.
ALTER TABLE `programs`
  -- track_id must exist in tracks; track id updates cascade.
  ADD CONSTRAINT `fk_programs_track` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`track_id`) ON UPDATE CASCADE;

-- Comment divider introducing the schedules constraints.
--
-- Says the next ALTER adds foreign keys to schedules.
-- Constraints for table `schedules`
-- Comment divider closing the schedules constraint header.
--
-- Starts altering schedules to add its foreign keys.
ALTER TABLE `schedules`
  -- adviser_id links to advisor_pool; deleting an adviser sets it to NULL.
  ADD CONSTRAINT `fk_schedules_adviser` FOREIGN KEY (`adviser_id`) REFERENCES `advisor_pool` (`adviser_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  -- application_id links to applications; deleting one deletes its schedule.
  ADD CONSTRAINT `fk_schedules_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  -- managed_by_user_id must exist in users; user id updates cascade.
  ADD CONSTRAINT `fk_schedules_manager` FOREIGN KEY (`managed_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

-- Comment divider introducing the schedule_panel_assignments constraints.
--
-- Says the next ALTER adds foreign keys to schedule_panel_assignments.
-- Constraints for table `schedule_panel_assignments`
-- Comment divider closing the assignments constraint header.
--
-- Starts altering schedule_panel_assignments to add its foreign keys.
ALTER TABLE `schedule_panel_assignments`
  -- panel_member_id must exist in panel_members; id updates cascade.
  ADD CONSTRAINT `fk_assignments_panel_member` FOREIGN KEY (`panel_member_id`) REFERENCES `panel_members` (`panel_member_id`) ON UPDATE CASCADE,
  -- schedule_id links to schedules; deleting a schedule deletes its assignments.
  ADD CONSTRAINT `fk_assignments_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`schedule_id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Comment divider introducing the students constraints.
--
-- Says the next ALTER adds foreign keys to students.
-- Constraints for table `students`
-- Comment divider closing the students constraint header.
--
-- Starts altering students to add its foreign key.
ALTER TABLE `students`
  -- user_id must exist in users; deleting a user deletes their student profile.
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

-- Comment divider introducing the templates constraints.
--
-- Says the next ALTER adds foreign keys to templates.
-- Constraints for table `templates`
-- Comment divider closing the templates constraint header.
--
-- Starts altering templates to add its foreign keys.
ALTER TABLE `templates`
  -- managed_by_user_id must exist in users; user id updates cascade.
  ADD CONSTRAINT `fk_templates_manager` FOREIGN KEY (`managed_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  -- stage_id links to workflow_stages; deleting a stage sets it to NULL.
  ADD CONSTRAINT `fk_templates_stage` FOREIGN KEY (`stage_id`) REFERENCES `workflow_stages` (`stage_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  -- track_id must exist in tracks; track id updates cascade.
  ADD CONSTRAINT `fk_templates_track` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`track_id`) ON UPDATE CASCADE;

-- Comment divider introducing the workflow_stages constraints.
--
-- Says the next ALTER adds foreign keys to workflow_stages.
-- Constraints for table `workflow_stages`
-- Comment divider closing the stages constraint header.
--
-- Starts altering workflow_stages to add its foreign key.
ALTER TABLE `workflow_stages`
  -- track_id must exist in tracks; deleting a track deletes its stages.
  ADD CONSTRAINT `fk_workflow_track` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`track_id`) ON DELETE CASCADE ON UPDATE CASCADE;
-- Commits the transaction, saving all the statements above.
COMMIT;

-- Restores the client character set that was saved at the top of the file.
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
-- Restores the result character set that was saved at the top of the file.
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
-- Restores the connection collation that was saved at the top of the file.
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
