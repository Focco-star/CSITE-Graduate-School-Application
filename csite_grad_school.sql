-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 29, 2026 at 02:56 PM
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
(1, 'Cassandra, Hiyake Y.', 'PhD in Computer Science', 'chaosmultiple@gmail.com', 'available', 'Sad', '2026-09-30 09:50:01'),
(2, 'Carey, Mariah', 'PhD in Science Education', 'poppy@adzu.edu.ph', 'available', 'Hi', '2026-09-30 10:13:32'),
(8, 'Villanueva, Ramon P.', 'PhD in Computer Science', 'rvillanueva@adzu.edu.ph', 'available', 'Graduate School faculty, available for thesis and capstone advising.', '2026-10-28 15:32:55'),
(9, 'Navarro, Cecilia M.', 'PhD in Information Technology', 'cnavarro@adzu.edu.ph', 'available', 'Graduate School faculty, available for thesis and capstone advising.', '2026-10-28 15:32:55'),
(10, 'Bautista, Eduardo L.', 'PhD in Mathematics Education', 'ebautista@adzu.edu.ph', 'available', 'Graduate School faculty, available for thesis and capstone advising.', '2026-10-28 15:32:55'),
(11, 'Mendoza, Patricia A.', 'PhD in Science Education', 'pmendoza@adzu.edu.ph', 'available', 'Graduate School faculty, available for thesis and capstone advising.', '2026-10-28 15:32:55'),
(12, 'Garcia, Fernando S.', 'PhD in Mechanical Engineering', 'fgarcia@adzu.edu.ph', 'available', 'Graduate School faculty, available for thesis and capstone advising.', '2026-10-28 15:32:55'),
(13, 'Ramos, Isabel T.', 'PhD in Library and Information Science', 'iramos@adzu.edu.ph', 'available', 'Graduate School faculty, available for thesis and capstone advising.', '2026-10-28 15:32:55');

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
(17, 5, 'Thesis Proposal', 'Machine Learning Approaches for Predictive Analytics in Graduate Education', 'under_review', '', 0, 0, NULL, NULL, NULL, 0, '[]', NULL, '2026-09-10 01:30:00', '2026-10-22 11:53:23', NULL),
(18, 6, 'Final Thesis', 'Development of an Automated Graduate Application Tracking System', 'under_review', 'coordinator note', 1, 1, 'R-12345', '2026-06-01', 1500.00, 1, '[]', 'approved', '2026-09-11 06:15:00', '2026-10-21 05:49:05', NULL),
(19, 7, 'Concept Paper', 'Cloud Security Protocols for Institutional Repositories', 'ready_for_presentation', '', 0, 0, NULL, NULL, NULL, 0, '{\"presentation_status\":\"confirmed\"}', NULL, '2026-09-12 02:00:00', '2026-10-24 16:20:16', '2026-10-23 17:54:05'),
(20, 8, 'Capstone Proposal', 'Mobile-Based Student Records and Notification Management', 'approved', '', 1, 1, '231231', '2026-09-30', 1.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\"}', 'approved', '2026-09-07 19:20:00', '2026-09-30 02:26:04', NULL),
(21, 5, 'Final Thesis Defense', 'Optimizing Database Queries in Large-Scale Web Applications', 'requires_revision', 'Please strengthen the experimental validation chapter and resubmit.', 0, 0, NULL, NULL, NULL, 0, NULL, 'requires_revision', '2026-09-05 08:45:00', '2026-10-23 17:54:21', '2026-10-23 17:54:21'),
(29, 8, 'Final Thesis', 'Mobile-Based Student Records and Notification Management', 'submitted', NULL, 0, 0, NULL, NULL, NULL, 0, NULL, NULL, '2026-09-30 10:28:43', '2026-10-23 17:54:14', '2026-10-23 17:54:14'),
(30, 7, 'Thesis Proposal', 'Cloud Security Protocols for Institutional Repositories', 'submitted', '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, '2026-10-14 14:09:21', '2026-10-23 17:54:02', '2026-10-23 17:54:02'),
(32, 7, 'Concept Paper', 'R', 'submitted', NULL, 0, 0, NULL, NULL, NULL, 0, NULL, NULL, '2026-10-15 15:55:25', '2026-10-23 17:53:52', '2026-10-23 17:53:52'),
(33, 7, 'Concept Paper', 'Ruee', 'submitted', NULL, 0, 0, NULL, NULL, NULL, 0, NULL, NULL, '2026-10-15 16:15:31', '2026-10-23 17:53:49', '2026-10-23 17:53:49'),
(45, 26, 'Concept Paper', 'Mobile-Based Flood Warning and Evacuation System for Coastal Communities in Zamboanga City', 'completed', '', 1, 1, '11231', '2026-11-06', 1200.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Oct 10, 2026\",\"presentation_time\":\"4:55 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Dela Cruz, Juan (PhD in Computer Science), Fernandez, Lisa (MS in Information Technology), Santos, Miguel (MS in Computer Science)\",\"grad_school_endorsement\":\"verified\",\"presentation_status\":\"confirmed\"}', 'approved', '2026-10-21 00:52:00', '2026-10-25 14:56:49', NULL),
(107, 14, 'Seminar Paper Proposal', 'The Impact of Autonomous Micro-Mobility Systems on Urban Air Quality and Pedestrian Flow Dynamics: A Multi-City Longitudinal Study', 'submitted', NULL, 0, 0, NULL, NULL, NULL, 0, '{}', NULL, '2026-10-22 16:45:17', '2026-10-23 16:33:43', NULL),
(108, 26, 'Thesis Proposal', 'Mobile-Based Flood Warning and Evacuation System for Coastal Communities in Zamboanga City', 'completed', '', 1, 1, '0981', '2026-11-04', 1700.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"receipt\":\"verified\",\"grad_school_endorsement\":\"verified\",\"presentation_status\":\"confirmed\",\"presentation_date\":\"Oct 10, 2026\",\"presentation_time\":\"12:15 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Dela Cruz, Juan (PhD in Computer Science), Reyes, Ana (PhD in Information Technology), Santos, Miguel (MS in Computer Science)\"}', 'approved', '2026-10-23 06:47:02', '2026-10-25 16:38:45', NULL),
(109, 26, 'Final Thesis', 'Mobile-Based Flood Warning and Evacuation System for Coastal Communities in Zamboanga City', 'submitted', '', 0, 0, NULL, NULL, NULL, 0, '{\"paper\":\"verified\"}', NULL, '2026-10-25 14:37:01', '2026-10-25 16:38:38', NULL),
(110, 79, 'Concept Paper', 'Digital Preservation Readiness of Local Cultural Heritage Collections: A Framework for Municipal Archives', 'completed', '', 1, 1, '111', '2026-10-05', 222.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Oct 10, 2026\",\"presentation_time\":\"11:50 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Dela Cruz, Juan (PhD in Computer Science), Fernandez, Lisa (MS in Information Technology), Reyes, Ana (PhD in Information Technology)\",\"presentation_status\":\"pending\"}', 'approved', '2026-10-25 15:41:04', '2026-10-25 16:36:33', NULL),
(111, 79, 'Thesis Proposal', 'Digital Preservation Readiness of Local Cultural Heritage Collections: A Framework for Municipal Archives', 'submitted', NULL, 0, 0, NULL, NULL, NULL, 0, NULL, NULL, '2026-10-25 15:53:27', '2026-10-25 15:53:27', NULL),
(114, 82, 'Seminar Paper Proposal', 'Pentagonal Numbers', 'submitted', NULL, 0, 0, NULL, NULL, NULL, 0, NULL, NULL, '2026-10-27 06:26:17', '2026-10-27 06:26:17', NULL),
(115, 83, 'Concept Paper', 'A Lightweight Intrusion Detection Model for Edge-Assisted Internet of Things Networks', 'submitted', NULL, 0, 0, NULL, NULL, NULL, 0, '{\"paper\":\"verified\"}', NULL, '2026-10-20 02:12:00', '2026-10-28 15:32:55', NULL),
(116, 84, 'Concept Paper', 'Predicting Student At-Risk Status in Blended Learning Using Gradient-Boosted Decision Trees', 'completed', 'Approved. Proceed to the Thesis Proposal.', 1, 1, 'OR-2026-1001', '2025-06-25', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Jul 12, 2025\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Aquino, Teresa B. (PhD in Computer Science), Salazar, Martin D. (PhD in Information Technology), Domingo, Rebecca C. (PhD in Mathematics Education)\",\"presentation_status\":\"confirmed\"}', 'approved', '2025-06-10 01:20:00', '2026-10-28 15:32:55', NULL),
(117, 84, 'Thesis Proposal', 'Predicting Student At-Risk Status in Blended Learning Using Gradient-Boosted Decision Trees', 'under_review', NULL, 0, 0, NULL, NULL, NULL, 0, '{\"paper\":\"submitted\",\"adviser_endorsement\":\"submitted\"}', NULL, '2026-10-12 00:45:00', '2026-10-28 15:32:55', NULL),
(118, 85, 'Concept Paper', 'Digital Preservation Readiness of Institutional Repositories in Mindanao', 'completed', 'Approved. Proceed to the Thesis Proposal.', 1, 1, 'OR-2026-1002', '2024-05-22', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Jun 15, 2024\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Pascual, Alfredo R. (PhD in Science Education), Lim, Jonathan K. (MS in Mechanical Engineering), Cruz, Veronica S. (PhD in Library and Information Science)\",\"presentation_status\":\"confirmed\"}', 'approved', '2024-05-06 01:10:00', '2026-10-28 15:32:55', NULL),
(119, 85, 'Thesis Proposal', 'Digital Preservation Readiness of Institutional Repositories in Mindanao', 'ready_for_presentation', NULL, 1, 1, 'OR-2026-1003', '2026-10-01', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\"}', NULL, '2026-09-21 01:30:00', '2026-10-28 15:32:55', NULL),
(120, 86, 'Concept Paper', 'An Adaptive Load-Balancing Strategy for Containerized Microservices in Campus Data Centers', 'completed', 'Approved. Proceed to the Thesis Proposal.', 1, 1, 'OR-2026-1004', '2023-11-22', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Dec 9, 2023\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Salazar, Martin D. (PhD in Information Technology), Pascual, Alfredo R. (PhD in Science Education), Cruz, Veronica S. (PhD in Library and Information Science)\",\"presentation_status\":\"confirmed\"}', 'approved', '2023-11-06 00:55:00', '2026-10-28 15:32:55', NULL),
(121, 86, 'Thesis Proposal', 'An Adaptive Load-Balancing Strategy for Containerized Microservices in Campus Data Centers', 'completed', 'Proposal approved. Proceed to the Final Thesis.', 1, 1, 'OR-2026-1005', '2025-02-26', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Mar 15, 2025\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Aquino, Teresa B. (PhD in Computer Science), Domingo, Rebecca C. (PhD in Mathematics Education), Lim, Jonathan K. (MS in Mechanical Engineering)\",\"presentation_status\":\"confirmed\"}', 'approved', '2025-02-10 01:05:00', '2026-10-28 15:32:55', NULL),
(122, 86, 'Final Thesis', 'An Adaptive Load-Balancing Strategy for Containerized Microservices in Campus Data Centers', 'completed', '', 1, 1, 'OR-2026-1006', '2026-09-30', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Nov 14, 2026\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Domingo, Rebecca C. (PhD in Mathematics Education), Pascual, Alfredo R. (PhD in Science Education), Lim, Jonathan K. (MS in Mechanical Engineering)\",\"presentation_status\":\"confirmed\"}', 'approved', '2026-09-15 01:15:00', '2026-10-28 16:48:44', NULL),
(123, 87, 'Concept Paper', 'Federated Learning for Privacy-Preserving Diabetic Retinopathy Screening in Rural Telehealth', 'completed', 'Approved. Proceed to the Thesis Proposal.', 1, 1, 'OR-2026-1007', '2022-10-19', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Nov 12, 2022\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Aquino, Teresa B. (PhD in Computer Science), Salazar, Martin D. (PhD in Information Technology), Cruz, Veronica S. (PhD in Library and Information Science)\",\"presentation_status\":\"confirmed\"}', 'approved', '2022-10-03 00:40:00', '2026-10-28 15:32:55', NULL),
(124, 87, 'Thesis Proposal', 'Federated Learning for Privacy-Preserving Diabetic Retinopathy Screening in Rural Telehealth', 'completed', 'Proposal approved. Proceed to the Final Thesis.', 1, 1, 'OR-2026-1008', '2024-01-31', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Feb 17, 2024\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Salazar, Martin D. (PhD in Information Technology), Domingo, Rebecca C. (PhD in Mathematics Education), Pascual, Alfredo R. (PhD in Science Education)\",\"presentation_status\":\"confirmed\"}', 'approved', '2024-01-15 01:25:00', '2026-10-28 15:32:55', NULL),
(125, 87, 'Final Thesis', 'Federated Learning for Privacy-Preserving Diabetic Retinopathy Screening in Rural Telehealth', 'completed', 'Final thesis approved. Congratulations.', 1, 1, 'OR-2026-1009', '2026-06-17', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Jul 18, 2026\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Aquino, Teresa B. (PhD in Computer Science), Cruz, Veronica S. (PhD in Library and Information Science), Lim, Jonathan K. (MS in Mechanical Engineering)\",\"presentation_status\":\"confirmed\"}', 'approved', '2026-06-01 01:05:00', '2026-10-28 16:14:34', NULL),
(126, 88, 'Concept Paper', 'Information Literacy Competencies of Senior High School Teachers in Davao City', 'completed', 'Approved. Proceed to the Thesis Proposal.', 1, 1, 'OR-2026-1010', '2024-08-21', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Sep 14, 2024\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Domingo, Rebecca C. (PhD in Mathematics Education), Pascual, Alfredo R. (PhD in Science Education), Cruz, Veronica S. (PhD in Library and Information Science)\",\"presentation_status\":\"confirmed\"}', 'approved', '2024-08-05 01:00:00', '2026-10-28 15:32:55', NULL),
(127, 88, 'Thesis Proposal', 'Information Literacy Competencies of Senior High School Teachers in Davao City', 'under_review', NULL, 0, 0, NULL, NULL, NULL, 0, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"submitted\"}', NULL, '2026-09-28 01:10:00', '2026-10-28 15:32:55', NULL),
(128, 89, 'Concept Paper', 'Automated Detection of Rice Leaf Diseases Using a Mobile Vision Transformer', 'completed', 'Approved. Proceed to the Thesis Proposal.', 1, 1, 'OR-2026-1011', '2023-07-26', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Aug 19, 2023\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Aquino, Teresa B. (PhD in Computer Science), Pascual, Alfredo R. (PhD in Science Education), Lim, Jonathan K. (MS in Mechanical Engineering)\",\"presentation_status\":\"confirmed\"}', 'approved', '2023-07-10 00:50:00', '2026-10-28 15:32:55', NULL),
(129, 89, 'Thesis Proposal', 'Automated Detection of Rice Leaf Diseases Using a Mobile Vision Transformer', 'ready_for_presentation', NULL, 1, 1, 'OR-2026-1012', '2026-09-29', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\"}', NULL, '2026-09-14 01:20:00', '2026-10-28 15:32:55', NULL),
(130, 90, 'Concept Paper', 'Metadata Quality Assessment of Open Access Agricultural Repositories in the Philippines', 'completed', 'Approved. Proceed to the Thesis Proposal.', 1, 1, 'OR-2026-1013', '2025-01-29', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Feb 15, 2025\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Aquino, Teresa B. (PhD in Computer Science), Salazar, Martin D. (PhD in Information Technology), Domingo, Rebecca C. (PhD in Mathematics Education)\",\"presentation_status\":\"confirmed\"}', 'approved', '2025-01-13 01:05:00', '2026-10-28 15:32:55', NULL),
(131, 90, 'Thesis Proposal', 'Metadata Quality Assessment of Open Access Agricultural Repositories in the Philippines', 'completed', 'Proposal approved. Proceed to the Final Thesis.', 1, 1, 'OR-2026-1014', '2026-01-28', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Feb 21, 2026\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Pascual, Alfredo R. (PhD in Science Education), Lim, Jonathan K. (MS in Mechanical Engineering), Cruz, Veronica S. (PhD in Library and Information Science)\",\"presentation_status\":\"confirmed\"}', 'approved', '2026-01-12 01:15:00', '2026-10-28 15:32:55', NULL),
(132, 90, 'Final Thesis', 'Metadata Quality Assessment of Open Access Agricultural Repositories in the Philippines', 'under_review', NULL, 0, 0, NULL, NULL, NULL, 0, '{\"paper\":\"submitted\",\"adviser_endorsement\":\"submitted\"}', NULL, '2026-10-19 01:35:00', '2026-10-28 15:32:55', NULL),
(133, 91, 'Concept Paper', 'Blockchain-Based Credential Verification for Technical-Vocational Education', 'submitted', NULL, 0, 0, NULL, NULL, NULL, 0, '{\"paper\":\"verified\",\"adviser_endorsement\":\"submitted\"}', NULL, '2026-10-22 01:30:00', '2026-10-28 15:32:55', NULL),
(134, 92, 'Capstone Proposal', 'A Web-Based Records Management System for Barangay Health Centers', 'submitted', NULL, 0, 0, NULL, NULL, NULL, 0, '{\"paper\":\"submitted\"}', NULL, '2026-10-21 02:05:00', '2026-10-28 15:32:55', NULL),
(135, 93, 'Capstone Proposal', 'Development of a Mobile Inventory Application for Small Retail Enterprises', 'ready_for_presentation', NULL, 1, 1, 'OR-2026-1015', '2026-10-02', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\"}', NULL, '2026-09-18 01:10:00', '2026-10-28 15:32:55', NULL),
(136, 94, 'Capstone Proposal', 'Design of a Digital Archive for Local Heritage Photographs', 'completed', 'Approved. Proceed to the Final Capstone.', 1, 1, 'OR-2026-1016', '2025-07-23', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Aug 16, 2025\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Salazar, Martin D. (PhD in Information Technology), Pascual, Alfredo R. (PhD in Science Education), Cruz, Veronica S. (PhD in Library and Information Science)\",\"presentation_status\":\"confirmed\"}', 'approved', '2025-07-07 01:00:00', '2026-10-28 15:32:55', NULL),
(137, 94, 'Final Capstone', 'Design of a Digital Archive for Local Heritage Photographs', 'scheduled', NULL, 1, 1, 'OR-2026-1017', '2026-10-05', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Nov 7, 2026\",\"presentation_time\":\"1:00 PM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Aquino, Teresa B. (PhD in Computer Science), Domingo, Rebecca C. (PhD in Mathematics Education), Lim, Jonathan K. (MS in Mechanical Engineering)\",\"presentation_status\":\"confirmed\"}', NULL, '2026-09-22 01:25:00', '2026-10-28 15:32:55', NULL),
(138, 95, 'Capstone Proposal', 'An IoT-Based Smart Irrigation Prototype for Community Gardens', 'completed', 'Approved. Proceed to the Final Capstone.', 1, 1, 'OR-2026-1018', '2022-12-21', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Jan 14, 2023\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Domingo, Rebecca C. (PhD in Mathematics Education), Pascual, Alfredo R. (PhD in Science Education), Lim, Jonathan K. (MS in Mechanical Engineering)\",\"presentation_status\":\"confirmed\"}', 'approved', '2022-12-05 01:15:00', '2026-10-28 15:32:55', NULL),
(139, 95, 'Final Capstone', 'An IoT-Based Smart Irrigation Prototype for Community Gardens', 'completed', 'Final capstone approved. Congratulations.', 1, 1, 'OR-2026-1019', '2026-05-20', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Jun 13, 2026\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Aquino, Teresa B. (PhD in Computer Science), Salazar, Martin D. (PhD in Information Technology), Cruz, Veronica S. (PhD in Library and Information Science)\",\"presentation_status\":\"confirmed\"}', 'approved', '2026-05-04 01:05:00', '2026-10-28 15:32:55', NULL),
(140, 96, 'Capstone Proposal', 'A Learning Management System for Senior High School STEM Teachers', 'completed', 'Approved. Proceed to the Final Capstone.', 1, 1, 'OR-2026-1020', '2023-08-23', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Sep 16, 2023\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Salazar, Martin D. (PhD in Information Technology), Domingo, Rebecca C. (PhD in Mathematics Education), Pascual, Alfredo R. (PhD in Science Education)\",\"presentation_status\":\"confirmed\"}', 'approved', '2023-08-07 00:55:00', '2026-10-28 15:32:55', NULL),
(141, 96, 'Final Capstone', 'A Learning Management System for Senior High School STEM Teachers', 'ready_for_presentation', NULL, 1, 1, 'OR-2026-1021', '2026-10-06', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\"}', NULL, '2026-09-25 01:20:00', '2026-10-28 15:32:55', NULL),
(142, 97, 'Capstone Proposal', 'Development of Low-Cost Water Quality Testing Kits for Secondary School Laboratories', 'submitted', NULL, 0, 0, NULL, NULL, NULL, 0, '{\"paper\":\"verified\"}', NULL, '2026-10-23 00:50:00', '2026-10-28 15:32:55', NULL),
(143, 98, 'Capstone Proposal', 'A Virtual Laboratory Module for Teaching Cellular Respiration', 'completed', 'Approved. Proceed to the Final Capstone.', 1, 1, 'OR-2026-1022', '2026-02-04', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Mar 7, 2026\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Aquino, Teresa B. (PhD in Computer Science), Lim, Jonathan K. (MS in Mechanical Engineering), Cruz, Veronica S. (PhD in Library and Information Science)\",\"presentation_status\":\"confirmed\"}', 'approved', '2026-01-19 01:10:00', '2026-10-28 15:32:55', NULL),
(144, 98, 'Final Capstone', 'A Virtual Laboratory Module for Teaching Cellular Respiration', 'under_review', NULL, 0, 0, NULL, NULL, NULL, 0, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"submitted\"}', NULL, '2026-10-16 01:30:00', '2026-10-28 15:32:55', NULL),
(145, 99, 'Capstone Proposal', 'Development of an Institutional Research Repository', 'under_review', 'The adviser endorsement form is incomplete: the signature page and date are missing. Please upload a corrected copy.', 1, 0, NULL, NULL, NULL, 0, '{\"paper\":\"verified\",\"adviser_endorsement\":\"revision\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"submitted\"}', NULL, '2026-09-30 01:15:00', '2026-10-28 15:32:55', NULL),
(146, 100, 'Capstone Proposal', 'Design of a Solar-Assisted Rice Grain Dryer for Small-Scale Farmers', 'completed', 'Approved. Proceed to the Final Capstone.', 1, 1, 'OR-2026-1023', '2025-09-24', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Oct 18, 2025\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Domingo, Rebecca C. (PhD in Mathematics Education), Pascual, Alfredo R. (PhD in Science Education), Cruz, Veronica S. (PhD in Library and Information Science)\",\"presentation_status\":\"confirmed\"}', 'approved', '2025-09-08 01:00:00', '2026-10-28 15:32:55', NULL),
(147, 100, 'Final Capstone', 'Design of a Solar-Assisted Rice Grain Dryer for Small-Scale Farmers', 'under_review', NULL, 0, 0, NULL, NULL, NULL, 0, '{\"paper\":\"submitted\"}', NULL, '2026-10-20 01:40:00', '2026-10-28 15:32:55', NULL),
(148, 101, 'Seminar Paper Proposal', 'Effectiveness of Problem-Based Learning in Teaching Quadratic Functions', 'submitted', NULL, 0, 0, NULL, NULL, NULL, 0, '{\"paper\":\"submitted\"}', NULL, '2026-10-19 01:15:00', '2026-10-28 15:32:55', NULL),
(149, 102, 'Seminar Paper Proposal', 'Students\' Mathematical Anxiety in Online Assessment', 'completed', 'Approved. Proceed to the Final Seminar Paper.', 1, 1, 'OR-2026-1024', '2024-03-20', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Apr 13, 2024\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Aquino, Teresa B. (PhD in Computer Science), Pascual, Alfredo R. (PhD in Science Education), Lim, Jonathan K. (MS in Mechanical Engineering)\",\"presentation_status\":\"confirmed\"}', 'approved', '2024-03-04 01:05:00', '2026-10-28 15:32:55', NULL),
(150, 102, 'Final Seminar Paper', 'Students\' Mathematical Anxiety in Online Assessment', 'scheduled', NULL, 1, 1, 'OR-2026-1025', '2026-10-13', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Nov 21, 2026\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Aquino, Teresa B. (PhD in Computer Science), Salazar, Martin D. (PhD in Information Technology), Domingo, Rebecca C. (PhD in Mathematics Education)\",\"presentation_status\":\"confirmed\"}', NULL, '2026-09-29 01:20:00', '2026-10-28 15:32:55', NULL),
(151, 103, 'Seminar Paper Proposal', 'Misconceptions in Newtonian Mechanics Among Grade 11 Learners', 'completed', 'Approved. Proceed to the Final Seminar Paper.', 1, 1, 'OR-2026-1026', '2025-08-20', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Sep 13, 2025\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Pascual, Alfredo R. (PhD in Science Education), Lim, Jonathan K. (MS in Mechanical Engineering), Cruz, Veronica S. (PhD in Library and Information Science)\",\"presentation_status\":\"confirmed\"}', 'approved', '2025-08-04 01:00:00', '2026-10-28 15:32:55', NULL),
(152, 103, 'Final Seminar Paper', 'Misconceptions in Newtonian Mechanics Among Grade 11 Learners', 'ready_for_presentation', NULL, 1, 1, 'OR-2026-1027', '2026-10-02', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\"}', NULL, '2026-09-17 01:25:00', '2026-10-28 15:32:55', NULL),
(153, 104, 'Seminar Paper Proposal', 'Integration of Climate Change Education in Junior High School Science', 'completed', 'Approved. Proceed to the Final Seminar Paper.', 1, 1, 'OR-2026-1028', '2025-11-26', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Dec 13, 2025\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Salazar, Martin D. (PhD in Information Technology), Pascual, Alfredo R. (PhD in Science Education), Cruz, Veronica S. (PhD in Library and Information Science)\",\"presentation_status\":\"confirmed\"}', 'approved', '2025-11-10 01:10:00', '2026-10-28 15:32:55', NULL),
(154, 104, 'Final Seminar Paper', 'Integration of Climate Change Education in Junior High School Science', 'under_review', NULL, 0, 0, NULL, NULL, NULL, 0, '{\"paper\":\"submitted\",\"adviser_endorsement\":\"submitted\"}', NULL, '2026-10-17 01:30:00', '2026-10-28 15:32:55', NULL),
(155, 105, 'Seminar Paper Proposal', 'Green Chemistry Practices in Senior High School Laboratory Instruction', 'completed', 'Approved. Proceed to the Final Seminar Paper.', 1, 1, 'OR-2026-1029', '2022-08-24', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"Sep 17, 2022\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Aquino, Teresa B. (PhD in Computer Science), Domingo, Rebecca C. (PhD in Mathematics Education), Lim, Jonathan K. (MS in Mechanical Engineering)\",\"presentation_status\":\"confirmed\"}', 'approved', '2022-08-08 00:45:00', '2026-10-28 15:32:55', NULL),
(156, 105, 'Final Seminar Paper', 'Green Chemistry Practices in Senior High School Laboratory Instruction', 'completed', 'Final seminar paper approved. Congratulations.', 1, 1, 'OR-2026-1030', '2026-04-29', 1500.00, 1, '{\"paper\":\"verified\",\"adviser_endorsement\":\"verified\",\"grad_school_endorsement\":\"verified\",\"receipt\":\"verified\",\"presentation_date\":\"May 16, 2026\",\"presentation_time\":\"9:00 AM\",\"presentation_venue\":\"CSITE Seminar Room\",\"presentation_panel\":\"Domingo, Rebecca C. (PhD in Mathematics Education), Pascual, Alfredo R. (PhD in Science Education), Lim, Jonathan K. (MS in Mechanical Engineering)\",\"presentation_status\":\"confirmed\"}', 'approved', '2026-04-13 01:10:00', '2026-10-28 15:32:55', NULL);

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
(1, 18, 2, 'Update this', '2026-09-30 09:58:13'),
(2, 18, 2, 'coord test note', '2026-10-21 05:44:36'),
(3, 18, 2, 'coord test note', '2026-10-21 05:44:36'),
(4, 18, 2, 'tmp', '2026-10-21 05:44:46'),
(5, 18, 2, 'tmp', '2026-10-21 05:44:46'),
(6, 18, 2, 'tmp', '2026-10-21 05:44:48'),
(7, 18, 2, 'tmp', '2026-10-21 05:44:48'),
(8, 18, 2, 'tmp', '2026-10-21 05:44:50'),
(9, 18, 2, 'tmp', '2026-10-21 05:44:50'),
(10, 18, 2, 'tmp', '2026-10-21 05:44:51'),
(11, 18, 2, 'tmp', '2026-10-21 05:44:51'),
(12, 18, 2, 'tmp', '2026-10-21 05:44:52'),
(13, 18, 2, 'tmp', '2026-10-21 05:44:52'),
(14, 18, 2, 'coordinator note', '2026-10-21 05:49:05'),
(15, 18, 2, 'coordinator note', '2026-10-21 05:49:05'),
(22, 17, 2, 'scoped save test', '2026-10-21 08:53:39'),
(23, 17, 2, 'scoped save test', '2026-10-21 08:53:39'),
(42, 125, 2, 'Final thesis approved. Congratulations.', '2026-10-28 16:07:33'),
(43, 125, 2, 'Final thesis approved. Congratulations.', '2026-10-28 16:07:33'),
(44, 125, 2, 'Final thesis approved. Congratulations.', '2026-10-28 16:08:02'),
(45, 125, 2, 'Final thesis approved. Congratulations.', '2026-10-28 16:08:02'),
(46, 125, 2, 'Final thesis approved. Congratulations.', '2026-10-28 16:08:56'),
(47, 125, 2, 'Final thesis approved. Congratulations.', '2026-10-28 16:08:57'),
(48, 125, 2, 'Final thesis approved. Congratulations.', '2026-10-28 16:09:33'),
(49, 125, 2, 'Final thesis approved. Congratulations.', '2026-10-28 16:09:33'),
(50, 125, 2, 'Final thesis approved. Congratulations.', '2026-10-28 16:13:40'),
(51, 125, 2, 'Final thesis approved. Congratulations.', '2026-10-28 16:13:40'),
(52, 125, 2, 'Final thesis approved. Congratulations.', '2026-10-28 16:13:45'),
(53, 125, 2, 'Final thesis approved. Congratulations.', '2026-10-28 16:13:45'),
(54, 125, 2, 'Final thesis approved. Congratulations.', '2026-10-28 16:14:28'),
(55, 125, 2, 'Final thesis approved. Congratulations.', '2026-10-28 16:14:28'),
(56, 125, 2, 'Final thesis approved. Congratulations.', '2026-10-28 16:14:34'),
(57, 125, 2, 'Final thesis approved. Congratulations.', '2026-10-28 16:14:34');

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
  `status` enum('submitted','verified','incomplete','revision') NOT NULL DEFAULT 'submitted',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `application_documents`
--

INSERT INTO `application_documents` (`document_id`, `application_id`, `user_id`, `stage`, `document_type`, `original_name`, `stored_name`, `mime_type`, `file_size`, `status`, `uploaded_at`) VALUES
(7, 29, 8, 'Final Capstone', 'Final Capstone Paper', '2016 Software Engineering (1).pdf', '522212cd8c61f130ff83a983cf33e012.pdf', 'application/pdf', 269283, 'submitted', '2026-09-30 10:28:43'),
(8, 30, 7, 'Capstone Proposal', 'Proposal Paper', '2 CAPSTONE PROPOSAL ENDORSEMENT TO GRADSCHOOL (1).docx', 'ece58bb9073e23b92b5e0efd11a1903f.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1198939, 'submitted', '2026-10-14 14:09:21'),
(9, 30, 7, 'Capstone Proposal', 'Proposal Paper', 'UI_UX Heuristic Evaluation & Observation Form.pdf', '617190b17ce9f07d8c2453126eb9e7e3.pdf', 'application/pdf', 205896, 'submitted', '2026-10-14 14:11:23'),
(12, 30, 7, 'Thesis Proposal', 'Proposal Paper', 'UI_UX Heuristic Evaluation & Observation Form.pdf', 'aeb7ef289004e43e2161668822b49a06.pdf', 'application/pdf', 205896, 'submitted', '2026-10-14 14:44:30'),
(13, 30, 7, 'Thesis Proposal', 'Proposal Paper', 'UI_UX Heuristic Evaluation & Observation Form.pdf', '53e5f5e1684db46ab318164cdc428443.pdf', 'application/pdf', 205896, 'submitted', '2026-10-14 15:16:54'),
(15, 33, 7, 'Concept Paper', 'Concept Paper Paper', 'Architectural Design.pdf', '9861b060d9cfae091f5b0ae218a58fed.pdf', 'application/pdf', 1847267, 'submitted', '2026-10-16 04:45:41'),
(16, 33, 7, 'Concept Paper', 'Concept Paper Paper', 'Algorithms_Sorting_Reviewer.docx', 'af287edbb2d4065a12fb9d071b025bb5.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 3357225, 'submitted', '2026-10-16 04:46:11'),
(34, 45, 26, 'Concept Paper', 'Concept Paper', '1 CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'bfc4070c7a7a57187fe3a9b87130a9f0.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 4904362, 'verified', '2026-10-22 13:51:30'),
(35, 45, 26, 'Concept Paper', 'Adviser Endorsement', 'GRADUATE SCHOOL - CSITE - THESIS FORMAT (5).docx', 'b496fa161b8f77334c05f6b334146a5a.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 10056, 'verified', '2026-10-23 14:19:03'),
(40, 45, 26, 'Concept Paper', 'Official Receipt / Payment Proof', '1 CONCEPT PAPER ADVISER ENDORSEMENT FORM_.docx', 'e0365a79f97dc2b396d84157de2e15e9.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 4033837, 'verified', '2026-10-21 01:16:47'),
(44, 45, 26, 'Concept Paper', 'Official Receipt / Payment Proof', '1 CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL.docx', '9623566baf47c3b865aacd56fd12fa4e.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 4904362, 'verified', '2026-10-23 05:23:21'),
(120, 107, 14, 'Seminar Paper Proposal', 'Seminar Paper Proposal', '1 CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL.docx', '3c4ea5c08d5670a84dd039343e660e27.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 4904362, 'submitted', '2026-10-22 16:45:17'),
(121, 108, 26, 'Thesis Proposal', 'Thesis Proposal', '1 CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL (8).docx', 'ab22d3fb11fce36ba4a177ab3cc409f6.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 4904362, 'verified', '2026-10-23 06:47:02'),
(122, 108, 26, 'Thesis Proposal', 'Thesis Proposal Adviser Endorsement Form', '1 CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL (7).docx', 'bd4eebf246de085f9c5679c32e445022.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 4904362, 'verified', '2026-10-23 06:51:17'),
(123, 108, 26, 'Thesis Proposal', 'Official Receipt / Payment Proof', '1 CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'a4a38156b8624f15eedb9e5e806da572.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 4904362, 'verified', '2026-10-23 06:59:52'),
(124, 45, 26, 'Concept Paper', 'Concept Paper Endorsement to Graduate School', '1 CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL (8).docx', '9519c1509a64d02002bd03f9f1b740a2.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 4904362, 'verified', '2026-10-23 13:36:21'),
(125, 108, 26, 'Thesis Proposal', 'Thesis Proposal Endorsement to Graduate School', 'GRADUATE SCHOOL - CSITE - THESIS FORMAT (4).docx', 'c8edd51d5b794abcf395d27c850788bc.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 10056, 'verified', '2026-10-23 13:36:32'),
(126, 109, 26, 'Final Thesis', 'Final Thesis', 'GRADUATE SCHOOL - CSITE - THESIS FORMAT (6).docx', '22607b0c343fe8c4dd5022e8ca632204.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 10056, 'verified', '2026-10-25 14:37:01'),
(127, 110, 79, 'Concept Paper', 'Concept Paper', '1 CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL (1).docx', '825993b3f7de955a668df2308854f6d6.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 3975616, 'verified', '2026-10-25 15:41:04'),
(128, 110, 79, 'Concept Paper', 'Concept Paper Adviser Endorsement Form', '1 CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL (2).docx', 'b61d478e38e4f9d3c13524b9f0a4b741.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 4904362, 'verified', '2026-10-25 15:45:11'),
(129, 110, 79, 'Concept Paper', 'Concept Paper Endorsement to Graduate School', '1 CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL (4).docx', '0749a47937b627ee14eaa16b1a1548b7.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 3975616, 'verified', '2026-10-25 15:46:33'),
(130, 110, 79, 'Concept Paper', 'Official Receipt / Payment Proof', '1 CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL (4).docx', '35db04140f4d5b5bc153a64a7741114e.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 3975616, 'verified', '2026-10-25 15:47:27'),
(131, 111, 79, 'Thesis Proposal', 'Thesis Proposal', '1 CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL (5).docx', '3cc6ad3603b61862010054a5da6227a7.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 4904362, 'submitted', '2026-10-25 15:53:27'),
(132, 114, 82, 'Seminar Paper Proposal', 'Seminar Paper Proposal', 'MATH SEMINAR PAPER TEMPLATE FORMAT.docx', '33d14586f51461f99a49ca5779277910.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 11183, 'submitted', '2026-10-27 06:26:17'),
(133, 115, 83, 'Concept Paper', 'Concept Paper', 'Alonzo_Concept_Paper.docx', 'cac53773639a38c7a8a31bd45946af18.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36476, 'verified', '2026-10-20 02:12:00'),
(134, 116, 84, 'Concept Paper', 'Concept Paper', 'Benedicto_Concept_Paper.docx', '95f69c7a0e538cf1af4976acd8ec3733.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36490, 'verified', '2025-06-10 01:20:00'),
(135, 116, 84, 'Concept Paper', 'Concept Paper Adviser Endorsement Form', 'Benedicto_Concept_Adviser_Endorsement.docx', '94da2d6572bbb463be0a252f8fc7a56e.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35761, 'verified', '2025-06-12 06:05:00'),
(136, 116, 84, 'Concept Paper', 'Concept Paper Endorsement to Graduate School', 'Benedicto_Concept_GradSchool_Endorsement.docx', '2293c9ab77579d12e12cc3bcb01d5960.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35791, 'verified', '2025-06-18 02:30:00'),
(137, 116, 84, 'Concept Paper', 'Official Receipt / Payment Proof', 'Benedicto_Concept_Official_Receipt.pdf', 'dd6659b97bee114cdf9928e7fe1d1b4f.pdf', 'application/pdf', 4960, 'verified', '2025-06-25 03:15:00'),
(138, 117, 84, 'Thesis Proposal', 'Thesis Proposal', 'Benedicto_Proposal_Paper.docx', '93761624f2292944d4893671c522ead3.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36490, 'submitted', '2026-10-12 00:45:00'),
(139, 117, 84, 'Thesis Proposal', 'Thesis Proposal Adviser Endorsement Form', 'Benedicto_Proposal_Adviser_Endorsement.docx', '7963e4369d377b9aef2bb18a9887493a.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35766, 'submitted', '2026-10-15 07:20:00'),
(140, 118, 85, 'Concept Paper', 'Concept Paper', 'Cabrera_Concept_Paper.docx', '7d5d2ef989966d584bc5322a3bbaeec7.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36461, 'verified', '2024-05-06 01:10:00'),
(141, 118, 85, 'Concept Paper', 'Concept Paper Adviser Endorsement Form', 'Cabrera_Concept_Adviser_Endorsement.docx', '96bb32bd4b56c9700f3a271237bda0f7.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35752, 'verified', '2024-05-08 05:40:00'),
(142, 118, 85, 'Concept Paper', 'Concept Paper Endorsement to Graduate School', 'Cabrera_Concept_GradSchool_Endorsement.docx', 'e5b2aa2c2c16d1081a2be7878de4bf9a.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35779, 'verified', '2024-05-15 02:00:00'),
(143, 118, 85, 'Concept Paper', 'Official Receipt / Payment Proof', 'Cabrera_Concept_Official_Receipt.pdf', 'b68b01f5547449a093f37cce7c75ff1f.pdf', 'application/pdf', 4985, 'verified', '2024-05-22 06:25:00'),
(144, 119, 85, 'Thesis Proposal', 'Thesis Proposal', 'Cabrera_Proposal_Paper.docx', '72276d090e8e8d75124a6d954677901b.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36464, 'verified', '2026-09-21 01:30:00'),
(145, 119, 85, 'Thesis Proposal', 'Thesis Proposal Adviser Endorsement Form', 'Cabrera_Proposal_Adviser_Endorsement.docx', '3e484c3d1063c4f6aad175841f585e2b.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35759, 'verified', '2026-09-23 06:10:00'),
(146, 119, 85, 'Thesis Proposal', 'Thesis Proposal Endorsement to Graduate School', 'Cabrera_Proposal_GradSchool_Endorsement.docx', 'a7f8cd1cdb9bf5950666786f2bcbf5b0.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35783, 'verified', '2026-09-29 03:05:00'),
(147, 119, 85, 'Thesis Proposal', 'Official Receipt / Payment Proof', 'Cabrera_Proposal_Official_Receipt.pdf', '3c02108e0c68fff8e4de1af8d258a9f3.pdf', 'application/pdf', 4979, 'verified', '2026-10-01 02:20:00'),
(148, 120, 86, 'Concept Paper', 'Concept Paper', 'Dizon_Concept_Paper.docx', '7804111759497571685b0612591bfb83.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36487, 'verified', '2023-11-06 00:55:00'),
(149, 120, 86, 'Concept Paper', 'Concept Paper Adviser Endorsement Form', 'Dizon_Concept_Adviser_Endorsement.docx', '4d70445fb4f803c68157034b036df647.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35764, 'verified', '2023-11-08 07:30:00'),
(150, 120, 86, 'Concept Paper', 'Concept Paper Endorsement to Graduate School', 'Dizon_Concept_GradSchool_Endorsement.docx', '44fa4404aeab57c13c1a2233d70f1cd7.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35792, 'verified', '2023-11-15 02:15:00'),
(151, 120, 86, 'Concept Paper', 'Official Receipt / Payment Proof', 'Dizon_Concept_Official_Receipt.pdf', 'bf054c16ba2787815c7d182241520d84.pdf', 'application/pdf', 4977, 'verified', '2023-11-22 05:50:00'),
(152, 121, 86, 'Thesis Proposal', 'Thesis Proposal', 'Dizon_Proposal_Paper.docx', '485895bee9ebde46cdb86354dc71158d.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36489, 'verified', '2025-02-10 01:05:00'),
(153, 121, 86, 'Thesis Proposal', 'Thesis Proposal Adviser Endorsement Form', 'Dizon_Proposal_Adviser_Endorsement.docx', 'a5c25f119803b80afc895de0c223117e.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35767, 'verified', '2025-02-12 06:35:00'),
(154, 121, 86, 'Thesis Proposal', 'Thesis Proposal Endorsement to Graduate School', 'Dizon_Proposal_GradSchool_Endorsement.docx', 'c97d8a9bb972eb77237d87554c229eef.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35795, 'verified', '2025-02-19 02:45:00'),
(155, 121, 86, 'Thesis Proposal', 'Official Receipt / Payment Proof', 'Dizon_Proposal_Official_Receipt.pdf', 'fd152840906e8e539cd4b14d98d3feaa.pdf', 'application/pdf', 4978, 'verified', '2025-02-26 03:30:00'),
(156, 122, 86, 'Final Thesis', 'Final Thesis', 'Dizon_Final_Paper.docx', '2daede54dcc19dac480f094f3add4a5f.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36488, 'verified', '2026-09-15 01:15:00'),
(157, 122, 86, 'Final Thesis', 'Final Thesis Adviser Endorsement Form', 'Dizon_Final_Adviser_Endorsement.docx', 'eefd392e75ec80e9c10e7dd7bdce936f.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35765, 'verified', '2026-09-17 06:20:00'),
(158, 122, 86, 'Final Thesis', 'Final Thesis Endorsement to Graduate School', 'Dizon_Final_GradSchool_Endorsement.docx', '27594fc1d189dc10431d5fb3a2ac2769.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35793, 'verified', '2026-09-24 02:10:00'),
(159, 122, 86, 'Final Thesis', 'Official Receipt / Payment Proof', 'Dizon_Final_Official_Receipt.pdf', '5bfd9bce5567f0fae6116115296b6420.pdf', 'application/pdf', 4976, 'verified', '2026-09-30 07:00:00'),
(160, 123, 87, 'Concept Paper', 'Concept Paper', 'Estrada_Concept_Paper.docx', 'ddbfcbc6d70bc4011f3d65877f938baf.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36502, 'verified', '2022-10-03 00:40:00'),
(161, 123, 87, 'Concept Paper', 'Concept Paper Adviser Endorsement Form', 'Estrada_Concept_Adviser_Endorsement.docx', '91f015221e6e97c1e3749d336e026526.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35768, 'verified', '2022-10-05 05:25:00'),
(162, 123, 87, 'Concept Paper', 'Concept Paper Endorsement to Graduate School', 'Estrada_Concept_GradSchool_Endorsement.docx', 'd2b84b05b6b9b1e088e89000d3952996.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35796, 'verified', '2022-10-12 02:05:00'),
(163, 123, 87, 'Concept Paper', 'Official Receipt / Payment Proof', 'Estrada_Concept_Official_Receipt.pdf', 'ae9eaf763e2a43ec202f5c83fcfd14b6.pdf', 'application/pdf', 5008, 'verified', '2022-10-19 06:40:00'),
(164, 124, 87, 'Thesis Proposal', 'Thesis Proposal', 'Estrada_Proposal_Paper.docx', '72014e768b8b881decb5b49a9c2a4035.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36502, 'verified', '2024-01-15 01:25:00'),
(165, 124, 87, 'Thesis Proposal', 'Thesis Proposal Adviser Endorsement Form', 'Estrada_Proposal_Adviser_Endorsement.docx', '34a7cb0a01df39fd6276297af30189c8.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35770, 'verified', '2024-01-17 07:10:00'),
(166, 124, 87, 'Thesis Proposal', 'Thesis Proposal Endorsement to Graduate School', 'Estrada_Proposal_GradSchool_Endorsement.docx', '629bd8572192d0da4efbb2d20403e231.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35798, 'verified', '2024-01-24 02:35:00'),
(167, 124, 87, 'Thesis Proposal', 'Official Receipt / Payment Proof', 'Estrada_Proposal_Official_Receipt.pdf', '8d0c50bfe11e4b4022c19bc5f787b4a6.pdf', 'application/pdf', 5010, 'verified', '2024-01-31 05:15:00'),
(168, 125, 87, 'Final Thesis', 'Final Thesis', 'Estrada_Final_Paper.docx', '8440c3a3a0663bb6ae1595b6d2f911d5.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36499, 'verified', '2026-06-01 01:05:00'),
(169, 125, 87, 'Final Thesis', 'Final Thesis Adviser Endorsement Form', 'Estrada_Final_Adviser_Endorsement.docx', '17c26e473d34f89da08e68b643d89221.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35764, 'verified', '2026-06-03 06:15:00'),
(170, 125, 87, 'Final Thesis', 'Final Thesis Endorsement to Graduate School', 'Estrada_Final_GradSchool_Endorsement.docx', '4ba380105d50261a81081bc10c5a8d32.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35793, 'verified', '2026-06-10 02:25:00'),
(171, 125, 87, 'Final Thesis', 'Official Receipt / Payment Proof', 'Estrada_Final_Official_Receipt.pdf', '9b692357077374be6656a42544af790f.pdf', 'application/pdf', 4982, 'verified', '2026-06-17 03:45:00'),
(172, 126, 88, 'Concept Paper', 'Concept Paper', 'Fajardo_Concept_Paper.docx', 'd896282b34b38c8b89b3d10a3716b261.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36475, 'verified', '2024-08-05 01:00:00'),
(173, 126, 88, 'Concept Paper', 'Concept Paper Adviser Endorsement Form', 'Fajardo_Concept_Adviser_Endorsement.docx', '76f418183bb4a19c7f46e123a29c8d29.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35759, 'verified', '2024-08-07 06:30:00'),
(174, 126, 88, 'Concept Paper', 'Concept Paper Endorsement to Graduate School', 'Fajardo_Concept_GradSchool_Endorsement.docx', 'd9963779a6917208c6c9a8228b5b1972.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35784, 'verified', '2024-08-14 02:20:00'),
(175, 126, 88, 'Concept Paper', 'Official Receipt / Payment Proof', 'Fajardo_Concept_Official_Receipt.pdf', '26458f4c63438de2566ac94d54652d70.pdf', 'application/pdf', 5016, 'verified', '2024-08-21 05:45:00'),
(176, 127, 88, 'Thesis Proposal', 'Thesis Proposal', 'Fajardo_Proposal_Paper.docx', '74c92c43074f135c61e198fa8db20135.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36477, 'verified', '2026-09-28 01:10:00'),
(177, 127, 88, 'Thesis Proposal', 'Thesis Proposal Adviser Endorsement Form', 'Fajardo_Proposal_Adviser_Endorsement.docx', '319a93eb562db634ed20033ca13f80a7.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35763, 'verified', '2026-09-30 07:05:00'),
(178, 127, 88, 'Thesis Proposal', 'Thesis Proposal Endorsement to Graduate School', 'Fajardo_Proposal_GradSchool_Endorsement.docx', '13a5cb46d1de084dca8d384ddf82cd6a.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35788, 'submitted', '2026-10-08 02:40:00'),
(179, 128, 89, 'Concept Paper', 'Concept Paper', 'Galvez_Concept_Paper.docx', '581e4c1df56a9a835643c962b4018b7f.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36471, 'verified', '2023-07-10 00:50:00'),
(180, 128, 89, 'Concept Paper', 'Concept Paper Adviser Endorsement Form', 'Galvez_Concept_Adviser_Endorsement.docx', 'd1452b356b16eafa3ed73dd65bb10df7.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35760, 'verified', '2023-07-12 05:35:00'),
(181, 128, 89, 'Concept Paper', 'Concept Paper Endorsement to Graduate School', 'Galvez_Concept_GradSchool_Endorsement.docx', 'cb366744a1566898d2772671ac32f355.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35787, 'verified', '2023-07-19 02:00:00'),
(182, 128, 89, 'Concept Paper', 'Official Receipt / Payment Proof', 'Galvez_Concept_Official_Receipt.pdf', '6aae715203f976d14bf074921a0bdb58.pdf', 'application/pdf', 4968, 'verified', '2023-07-26 06:20:00'),
(183, 129, 89, 'Thesis Proposal', 'Thesis Proposal', 'Galvez_Proposal_Paper.docx', 'ba93e5805c2a59cbe16f97798cddf2a0.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36472, 'verified', '2026-09-14 01:20:00'),
(184, 129, 89, 'Thesis Proposal', 'Thesis Proposal Adviser Endorsement Form', 'Galvez_Proposal_Adviser_Endorsement.docx', '12d7d496b7c5229a5aee22f8d6ba663d.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35766, 'verified', '2026-09-16 06:45:00'),
(185, 129, 89, 'Thesis Proposal', 'Thesis Proposal Endorsement to Graduate School', 'Galvez_Proposal_GradSchool_Endorsement.docx', 'aa7bfff21080a2e22187e673a25fa842.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35791, 'verified', '2026-09-23 02:30:00'),
(186, 129, 89, 'Thesis Proposal', 'Official Receipt / Payment Proof', 'Galvez_Proposal_Official_Receipt.pdf', 'f324e0cb9e84d1a0af9c44da4547a23c.pdf', 'application/pdf', 4996, 'verified', '2026-09-29 03:10:00'),
(187, 130, 90, 'Concept Paper', 'Concept Paper', 'Hernandez_Concept_Paper.docx', 'b9efe68f3f96ae9531d516cd0e8e7002.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36488, 'verified', '2025-01-13 01:05:00'),
(188, 130, 90, 'Concept Paper', 'Concept Paper Adviser Endorsement Form', 'Hernandez_Concept_Adviser_Endorsement.docx', '642f18f4a03c930ba69237da08cf4e2c.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35768, 'verified', '2025-01-15 06:00:00'),
(189, 130, 90, 'Concept Paper', 'Concept Paper Endorsement to Graduate School', 'Hernandez_Concept_GradSchool_Endorsement.docx', 'f68841770d9897db41af514d0b276eee.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35796, 'verified', '2025-01-22 02:15:00'),
(190, 130, 90, 'Concept Paper', 'Official Receipt / Payment Proof', 'Hernandez_Concept_Official_Receipt.pdf', 'cdafb437f9505e813504527bbc99a56d.pdf', 'application/pdf', 5017, 'verified', '2025-01-29 05:30:00'),
(191, 131, 90, 'Thesis Proposal', 'Thesis Proposal', 'Hernandez_Proposal_Paper.docx', 'c4502e022b1b6dca1a7ee9a4324e59f9.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36486, 'verified', '2026-01-12 01:15:00'),
(192, 131, 90, 'Thesis Proposal', 'Thesis Proposal Adviser Endorsement Form', 'Hernandez_Proposal_Adviser_Endorsement.docx', '78285eb50727678494382283f333775b.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35772, 'verified', '2026-01-14 07:20:00'),
(193, 131, 90, 'Thesis Proposal', 'Thesis Proposal Endorsement to Graduate School', 'Hernandez_Proposal_GradSchool_Endorsement.docx', '9576ce70183020c76e413681137734ab.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35798, 'verified', '2026-01-21 02:45:00'),
(194, 131, 90, 'Thesis Proposal', 'Official Receipt / Payment Proof', 'Hernandez_Proposal_Official_Receipt.pdf', 'e4105b96e9eeade06189b1501cbb95fb.pdf', 'application/pdf', 5019, 'verified', '2026-01-28 06:10:00'),
(195, 132, 90, 'Final Thesis', 'Final Thesis', 'Hernandez_Final_Paper.docx', 'a02cf24163909bd30bd1f05fe5e7beab.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36487, 'submitted', '2026-10-19 01:35:00'),
(196, 132, 90, 'Final Thesis', 'Final Thesis Adviser Endorsement Form', 'Hernandez_Final_Adviser_Endorsement.docx', 'c4b12921a80f8775755136afe40f6a35.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35768, 'submitted', '2026-10-21 06:25:00'),
(197, 133, 91, 'Concept Paper', 'Concept Paper', 'Ibarra_Concept_Paper.docx', '2e8ff7db693601dbe5930bc44551069c.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36468, 'verified', '2026-10-22 01:30:00'),
(198, 133, 91, 'Concept Paper', 'Concept Paper Adviser Endorsement Form', 'Ibarra_Concept_Adviser_Endorsement.docx', '04373cf21953155598fc28804debd8a2.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35755, 'submitted', '2026-10-24 06:05:00'),
(199, 134, 92, 'Capstone Proposal', 'Capstone Proposal', 'Javier_Proposal_Paper.docx', 'cd2b66273122dc095c99e5dc46d9e827.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36452, 'submitted', '2026-10-21 02:05:00'),
(200, 135, 93, 'Capstone Proposal', 'Capstone Proposal', 'Katigbak_Proposal_Paper.docx', '02f651cb0b812c6c42091edce3ce4865.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36463, 'verified', '2026-09-18 01:10:00'),
(201, 135, 93, 'Capstone Proposal', 'Capstone Adviser Endorsement Form - Proposal', 'Katigbak_Proposal_Adviser_Endorsement.docx', 'd708df1df6d779efea8c57e71f222fa5.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35763, 'verified', '2026-09-21 06:30:00'),
(202, 135, 93, 'Capstone Proposal', 'Capstone Proposal Endorsement to Graduate School', 'Katigbak_Proposal_GradSchool_Endorsement.docx', '1c888c2e8b1ea3e5aadb3f26fa51062f.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35788, 'verified', '2026-09-28 02:20:00'),
(203, 135, 93, 'Capstone Proposal', 'Official Receipt / Payment Proof', 'Katigbak_Proposal_Official_Receipt.pdf', 'ad05e09b3d83c48df638a1414ef7cf4e.pdf', 'application/pdf', 4977, 'verified', '2026-10-02 03:05:00'),
(204, 136, 94, 'Capstone Proposal', 'Capstone Proposal', 'Lagman_Proposal_Paper.docx', '9a3758db29f0c9561bd4bad0df40ae17.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36447, 'verified', '2025-07-07 01:00:00'),
(205, 136, 94, 'Capstone Proposal', 'Capstone Adviser Endorsement Form - Proposal', 'Lagman_Proposal_Adviser_Endorsement.docx', 'c000802fff6fe564ea23cc3bab247e4d.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35751, 'verified', '2025-07-09 06:15:00'),
(206, 136, 94, 'Capstone Proposal', 'Capstone Proposal Endorsement to Graduate School', 'Lagman_Proposal_GradSchool_Endorsement.docx', 'd737ddb240d4d69ac87c48cf0ffd63d7.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35778, 'verified', '2025-07-16 02:05:00'),
(207, 136, 94, 'Capstone Proposal', 'Official Receipt / Payment Proof', 'Lagman_Proposal_Official_Receipt.pdf', '24c848f60b8bf3e691ecd93507d8512d.pdf', 'application/pdf', 4975, 'verified', '2025-07-23 05:40:00'),
(208, 137, 94, 'Final Capstone', 'Final Capstone', 'Lagman_Final_Paper.docx', '00e4baa10dd8b501bfdab549ac06c843.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36448, 'verified', '2026-09-22 01:25:00'),
(209, 137, 94, 'Final Capstone', 'Final Capstone Adviser Endorsement Form', 'Lagman_Final_Adviser_Endorsement.docx', '478d18fa2a9aed4d3fb733e7c6ef435f.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35752, 'verified', '2026-09-24 07:00:00'),
(210, 137, 94, 'Final Capstone', 'Final Capstone Endorsement to Graduate School', 'Lagman_Final_GradSchool_Endorsement.docx', 'c8f36aae3a9ccbfb5b9e9fd9ed254b97.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35779, 'verified', '2026-09-30 02:35:00'),
(211, 137, 94, 'Final Capstone', 'Official Receipt / Payment Proof', 'Lagman_Final_Official_Receipt.pdf', '55031c5b7d73ae17719e8f520627886e.pdf', 'application/pdf', 4979, 'verified', '2026-10-05 06:20:00'),
(212, 138, 95, 'Capstone Proposal', 'Capstone Proposal', 'Magbanua_Proposal_Paper.docx', 'e854ea2c2ac585203b4d86aa3380df82.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36448, 'verified', '2022-12-05 01:15:00'),
(213, 138, 95, 'Capstone Proposal', 'Capstone Adviser Endorsement Form - Proposal', 'Magbanua_Proposal_Adviser_Endorsement.docx', 'ac8d5e2030571dbd8931cef528810c04.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35755, 'verified', '2022-12-07 06:25:00'),
(214, 138, 95, 'Capstone Proposal', 'Capstone Proposal Endorsement to Graduate School', 'Magbanua_Proposal_GradSchool_Endorsement.docx', 'fd7af4d3f0afe6fdce2d201714672fb4.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35784, 'verified', '2022-12-14 02:10:00'),
(215, 138, 95, 'Capstone Proposal', 'Official Receipt / Payment Proof', 'Magbanua_Proposal_Official_Receipt.pdf', '2646abd5315a8f536abd52c507a557d4.pdf', 'application/pdf', 5015, 'verified', '2022-12-21 05:55:00'),
(216, 139, 95, 'Final Capstone', 'Final Capstone', 'Magbanua_Final_Paper.docx', '09702293d47a7b369c0eb5c0ee8e4744.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36446, 'verified', '2026-05-04 01:05:00'),
(217, 139, 95, 'Final Capstone', 'Final Capstone Adviser Endorsement Form', 'Magbanua_Final_Adviser_Endorsement.docx', 'acb7466be62146b012a06d64cd5b7240.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35750, 'verified', '2026-05-06 06:40:00'),
(218, 139, 95, 'Final Capstone', 'Final Capstone Endorsement to Graduate School', 'Magbanua_Final_GradSchool_Endorsement.docx', 'f11040d2761f7e65cee82393d658f57a.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35777, 'verified', '2026-05-13 02:20:00'),
(219, 139, 95, 'Final Capstone', 'Official Receipt / Payment Proof', 'Magbanua_Final_Official_Receipt.pdf', 'fe24b246e1231db90e075041a0743b95.pdf', 'application/pdf', 5006, 'verified', '2026-05-20 03:35:00'),
(220, 140, 96, 'Capstone Proposal', 'Capstone Proposal', 'Ocampo_Proposal_Paper.docx', '824edf22b39e7fd44f413d7b1fe7e655.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36444, 'verified', '2023-08-07 00:55:00'),
(221, 140, 96, 'Capstone Proposal', 'Capstone Adviser Endorsement Form - Proposal', 'Ocampo_Proposal_Adviser_Endorsement.docx', '3e3cf678e7fc198599e9bbeadbcf146c.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35757, 'verified', '2023-08-09 05:50:00'),
(222, 140, 96, 'Capstone Proposal', 'Capstone Proposal Endorsement to Graduate School', 'Ocampo_Proposal_GradSchool_Endorsement.docx', 'd94f70013fdd444a94ea09d44741316e.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35784, 'verified', '2023-08-16 02:00:00'),
(223, 140, 96, 'Capstone Proposal', 'Official Receipt / Payment Proof', 'Ocampo_Proposal_Official_Receipt.pdf', 'bb8eeb6d1946bd7383d315f84c68e894.pdf', 'application/pdf', 5011, 'verified', '2023-08-23 06:05:00'),
(224, 141, 96, 'Final Capstone', 'Final Capstone', 'Ocampo_Final_Paper.docx', '2b287538e907cedaea266992f1ff1b39.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36446, 'verified', '2026-09-25 01:20:00'),
(225, 141, 96, 'Final Capstone', 'Final Capstone Adviser Endorsement Form', 'Ocampo_Final_Adviser_Endorsement.docx', '4743b2e0f4038803ed23afc20cf8c957.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35756, 'verified', '2026-09-28 06:55:00'),
(226, 141, 96, 'Final Capstone', 'Final Capstone Endorsement to Graduate School', 'Ocampo_Final_GradSchool_Endorsement.docx', '901c9f0cba689998af5a806f838144be.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35782, 'verified', '2026-10-02 02:30:00'),
(227, 141, 96, 'Final Capstone', 'Official Receipt / Payment Proof', 'Ocampo_Final_Official_Receipt.pdf', '817987695c8eaeb76ea75e412a7d354d.pdf', 'application/pdf', 4994, 'verified', '2026-10-06 05:25:00'),
(228, 142, 97, 'Capstone Proposal', 'Capstone Proposal', 'Peralta_Proposal_Paper.docx', 'f07877aaa8b2bad878993a8b92c51659.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36499, 'verified', '2026-10-23 00:50:00'),
(229, 143, 98, 'Capstone Proposal', 'Capstone Proposal', 'Quijano_Proposal_Paper.docx', 'cb14cf9f8b727bbaabf3ff4adaff2158.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36463, 'verified', '2026-01-19 01:10:00'),
(230, 143, 98, 'Capstone Proposal', 'Capstone Adviser Endorsement Form - Proposal', 'Quijano_Proposal_Adviser_Endorsement.docx', 'f893c800b5ed7051568265efb8a93766.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35762, 'verified', '2026-01-21 06:20:00'),
(231, 143, 98, 'Capstone Proposal', 'Capstone Proposal Endorsement to Graduate School', 'Quijano_Proposal_GradSchool_Endorsement.docx', '038df2687846c83c75e2df2d1c7161b2.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35790, 'verified', '2026-01-28 02:15:00'),
(232, 143, 98, 'Capstone Proposal', 'Official Receipt / Payment Proof', 'Quijano_Proposal_Official_Receipt.pdf', '08687fbedbccefabadf7e6f62381886f.pdf', 'application/pdf', 5005, 'verified', '2026-02-04 05:35:00'),
(233, 144, 98, 'Final Capstone', 'Final Capstone', 'Quijano_Final_Paper.docx', '439773d51391b94c0cadcad792eb87fe.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36462, 'verified', '2026-10-16 01:30:00'),
(234, 144, 98, 'Final Capstone', 'Final Capstone Adviser Endorsement Form', 'Quijano_Final_Adviser_Endorsement.docx', 'e6086f9226a09fbb72da9afa789965ee.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35760, 'verified', '2026-10-16 07:10:00'),
(235, 144, 98, 'Final Capstone', 'Final Capstone Endorsement to Graduate School', 'Quijano_Final_GradSchool_Endorsement.docx', 'fef1060b36531c1ccb167f0ce3f30a95.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35788, 'submitted', '2026-10-19 02:25:00'),
(236, 145, 99, 'Capstone Proposal', 'Capstone Proposal', 'Rivera_Proposal_Paper.docx', '6c84a6aaf8dbc52310b746067cf66f57.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36428, 'verified', '2026-09-30 01:15:00'),
(237, 145, 99, 'Capstone Proposal', 'Capstone Adviser Endorsement Form - Proposal', 'Rivera_Proposal_Adviser_Endorsement.docx', '1575c1159046948f99b9c52cd08094bf.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35742, 'revision', '2026-10-02 06:05:00'),
(238, 145, 99, 'Capstone Proposal', 'Capstone Proposal Endorsement to Graduate School', 'Rivera_Proposal_GradSchool_Endorsement.docx', 'aac9d2e2822837e9cf341b7ca0ceac67.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35771, 'verified', '2026-10-08 02:30:00'),
(239, 145, 99, 'Capstone Proposal', 'Official Receipt / Payment Proof', 'Rivera_Proposal_Official_Receipt.pdf', 'd4e9a6c452961a3840aca913580464dd.pdf', 'application/pdf', 5002, 'submitted', '2026-10-12 05:20:00'),
(240, 146, 100, 'Capstone Proposal', 'Capstone Proposal', 'Salvador_Proposal_Paper.docx', 'b282f178e1bb7edac23c5b70ceab97f8.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36474, 'verified', '2025-09-08 01:00:00'),
(241, 146, 100, 'Capstone Proposal', 'Capstone Adviser Endorsement Form - Proposal', 'Salvador_Proposal_Adviser_Endorsement.docx', '0a4c2f15594e8d4224d513b7c29cc1da.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35767, 'verified', '2025-09-10 06:30:00'),
(242, 146, 100, 'Capstone Proposal', 'Capstone Proposal Endorsement to Graduate School', 'Salvador_Proposal_GradSchool_Endorsement.docx', '5cb433b70215898e294667841e4ebde6.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35795, 'verified', '2025-09-17 02:05:00'),
(243, 146, 100, 'Capstone Proposal', 'Official Receipt / Payment Proof', 'Salvador_Proposal_Official_Receipt.pdf', 'a853b1d3ea4a44b0cbe2bfe15db74509.pdf', 'application/pdf', 5007, 'verified', '2025-09-24 05:45:00'),
(244, 147, 100, 'Final Capstone', 'Final Capstone', 'Salvador_Final_Paper.docx', 'fd06e9019f5ad7d372332c1ef4211647.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36473, 'submitted', '2026-10-20 01:40:00'),
(245, 148, 101, 'Seminar Paper Proposal', 'Seminar Paper Proposal', 'Tamayo_Proposal_Paper.docx', 'b3d143c9c17dab70b128eb164edc6eae.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36482, 'submitted', '2026-10-19 01:15:00'),
(246, 149, 102, 'Seminar Paper Proposal', 'Seminar Paper Proposal', 'Uy_Proposal_Paper.docx', 'b4c4e2f5452619bd60c6394d5ac51788.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36445, 'verified', '2024-03-04 01:05:00'),
(247, 149, 102, 'Seminar Paper Proposal', 'Seminar Paper Proposal Adviser Endorsement Form', 'Uy_Proposal_Adviser_Endorsement.docx', 'b74e9ccd9dbea9bd82db3923afc1673c.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35739, 'verified', '2024-03-06 06:10:00'),
(248, 149, 102, 'Seminar Paper Proposal', 'Seminar Paper Proposal Endorsement to Graduate School', 'Uy_Proposal_GradSchool_Endorsement.docx', 'd2f39781731f6a3ed46693bed646183f.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35764, 'verified', '2024-03-13 02:20:00'),
(249, 149, 102, 'Seminar Paper Proposal', 'Official Receipt / Payment Proof', 'Uy_Proposal_Official_Receipt.pdf', '17749fc181f38663a57d1acae8d22105.pdf', 'application/pdf', 4981, 'verified', '2024-03-20 05:30:00'),
(250, 150, 102, 'Final Seminar Paper', 'Final Seminar Paper', 'Uy_Final_Paper.docx', '67ed35452f493350bf09cbe56de16300.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36446, 'verified', '2026-09-29 01:20:00'),
(251, 150, 102, 'Final Seminar Paper', 'Final Seminar Paper Adviser Endorsement Form', 'Uy_Final_Adviser_Endorsement.docx', '17253110dbc5924c9589d40eebedd852.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35742, 'verified', '2026-10-01 07:15:00'),
(252, 150, 102, 'Final Seminar Paper', 'Final Seminar Paper Endorsement to Graduate School', 'Uy_Final_GradSchool_Endorsement.docx', '61f2c84e64b68a0e850d3baca1f348f8.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35765, 'verified', '2026-10-08 02:40:00'),
(253, 150, 102, 'Final Seminar Paper', 'Official Receipt / Payment Proof', 'Uy_Final_Official_Receipt.pdf', '911ea9dde41f8e9967fde735e5e5cc82.pdf', 'application/pdf', 4982, 'verified', '2026-10-13 06:05:00'),
(254, 151, 103, 'Seminar Paper Proposal', 'Seminar Paper Proposal', 'Valdez_Proposal_Paper.docx', '69425c1b0c41e674bd819fbe2cf76986.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36471, 'verified', '2025-08-04 01:00:00'),
(255, 151, 103, 'Seminar Paper Proposal', 'Seminar Paper Proposal Adviser Endorsement Form', 'Valdez_Proposal_Adviser_Endorsement.docx', '673acd9e9fd1a0b1d8fea10d826984e2.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35762, 'verified', '2025-08-06 06:25:00'),
(256, 151, 103, 'Seminar Paper Proposal', 'Seminar Paper Proposal Endorsement to Graduate School', 'Valdez_Proposal_GradSchool_Endorsement.docx', '556c607834a0486c1d6e905dc182274b.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35787, 'verified', '2025-08-13 02:10:00'),
(257, 151, 103, 'Seminar Paper Proposal', 'Official Receipt / Payment Proof', 'Valdez_Proposal_Official_Receipt.pdf', 'e391417f84412472a605672b59eccee5.pdf', 'application/pdf', 5027, 'verified', '2025-08-20 05:50:00'),
(258, 152, 103, 'Final Seminar Paper', 'Final Seminar Paper', 'Valdez_Final_Paper.docx', 'e28e05466d91d2552f788d30c04db968.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36472, 'verified', '2026-09-17 01:25:00'),
(259, 152, 103, 'Final Seminar Paper', 'Final Seminar Paper Adviser Endorsement Form', 'Valdez_Final_Adviser_Endorsement.docx', '295f883fe6586d6e99bbe7c2e7c342ef.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35761, 'verified', '2026-09-21 06:40:00'),
(260, 152, 103, 'Final Seminar Paper', 'Final Seminar Paper Endorsement to Graduate School', 'Valdez_Final_GradSchool_Endorsement.docx', '79ac2291eac5efdcf7d2fdd95e0b60db.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35786, 'verified', '2026-09-28 02:15:00'),
(261, 152, 103, 'Final Seminar Paper', 'Official Receipt / Payment Proof', 'Valdez_Final_Official_Receipt.pdf', '320c588e9df615cf935b99962172f584.pdf', 'application/pdf', 5010, 'verified', '2026-10-02 05:35:00'),
(262, 153, 104, 'Seminar Paper Proposal', 'Seminar Paper Proposal', 'Wenceslao_Proposal_Paper.docx', '5678d5827a13e8a4a6f46d42f24c8c48.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36482, 'verified', '2025-11-10 01:10:00'),
(263, 153, 104, 'Seminar Paper Proposal', 'Seminar Paper Proposal Adviser Endorsement Form', 'Wenceslao_Proposal_Adviser_Endorsement.docx', '0e836c785f12a7f11b1ad04d22918cad.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35762, 'verified', '2025-11-12 06:15:00'),
(264, 153, 104, 'Seminar Paper Proposal', 'Seminar Paper Proposal Endorsement to Graduate School', 'Wenceslao_Proposal_GradSchool_Endorsement.docx', '5c6cf2ab0e181b74152b2b49afc97397.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35788, 'verified', '2025-11-19 02:00:00'),
(265, 153, 104, 'Seminar Paper Proposal', 'Official Receipt / Payment Proof', 'Wenceslao_Proposal_Official_Receipt.pdf', '9c6cd9786bba8217bf29ef6572b6124b.pdf', 'application/pdf', 5018, 'verified', '2025-11-26 05:40:00'),
(266, 154, 104, 'Final Seminar Paper', 'Final Seminar Paper', 'Wenceslao_Final_Paper.docx', 'd03cd2cad03cb8709eafa5f564c571ba.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36481, 'submitted', '2026-10-17 01:30:00'),
(267, 154, 104, 'Final Seminar Paper', 'Final Seminar Paper Adviser Endorsement Form', 'Wenceslao_Final_Adviser_Endorsement.docx', '6f509b7a09c041243b6f88afe6c78ffb.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35759, 'submitted', '2026-10-20 07:05:00'),
(268, 155, 105, 'Seminar Paper Proposal', 'Seminar Paper Proposal', 'Yumul_Proposal_Paper.docx', '6d49e20cbcd41ad7cd09e4f9ba8c5478.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36478, 'verified', '2022-08-08 00:45:00'),
(269, 155, 105, 'Seminar Paper Proposal', 'Seminar Paper Proposal Adviser Endorsement Form', 'Yumul_Proposal_Adviser_Endorsement.docx', 'aaf01db394f5254778c0dbbb34255661.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35761, 'verified', '2022-08-10 05:30:00'),
(270, 155, 105, 'Seminar Paper Proposal', 'Seminar Paper Proposal Endorsement to Graduate School', 'Yumul_Proposal_GradSchool_Endorsement.docx', '8089766d069386e275b7b32c1dac0edb.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35787, 'verified', '2022-08-17 02:05:00'),
(271, 155, 105, 'Seminar Paper Proposal', 'Official Receipt / Payment Proof', 'Yumul_Proposal_Official_Receipt.pdf', '0af1d82b0357658a05968bdbfdbb2dad.pdf', 'application/pdf', 5003, 'verified', '2022-08-24 06:15:00'),
(272, 156, 105, 'Final Seminar Paper', 'Final Seminar Paper', 'Yumul_Final_Paper.docx', '4d6ae96116cf6f65444f7eb1df362e1b.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 36476, 'verified', '2026-04-13 01:10:00'),
(273, 156, 105, 'Final Seminar Paper', 'Final Seminar Paper Adviser Endorsement Form', 'Yumul_Final_Adviser_Endorsement.docx', 'db5cc368ae8eb21baa55d64a058044ab.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35759, 'verified', '2026-04-15 06:20:00'),
(274, 156, 105, 'Final Seminar Paper', 'Final Seminar Paper Endorsement to Graduate School', 'Yumul_Final_GradSchool_Endorsement.docx', '1a9070551d1515887f6c79f905d9da87.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 35784, 'verified', '2026-04-22 02:15:00'),
(275, 156, 105, 'Final Seminar Paper', 'Official Receipt / Payment Proof', 'Yumul_Final_Official_Receipt.pdf', '5dee2ebf64ec4848d09e59545d95127c.pdf', 'application/pdf', 5000, 'verified', '2026-04-29 05:55:00');

-- --------------------------------------------------------

--
-- Table structure for table `application_drafts`
--

CREATE TABLE `application_drafts` (
  `draft_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` text DEFAULT NULL,
  `adviser` varchar(150) DEFAULT NULL,
  `abstract` text DEFAULT NULL,
  `enroll_date` varchar(20) DEFAULT NULL,
  `stage_key` varchar(50) DEFAULT NULL,
  `doc_type` varchar(150) DEFAULT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `payment_amount` decimal(10,2) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application_drafts`
--

INSERT INTO `application_drafts` (`draft_id`, `user_id`, `title`, `adviser`, `abstract`, `enroll_date`, `stage_key`, `doc_type`, `receipt_number`, `payment_amount`, `payment_date`, `file_name`, `updated_at`) VALUES
(93, 14, 'Psychology & Health: The Relationship Between Smartphone Screen Time and Sleep Quality Among Adolescents', 'Franklin Sierra', '', '2026-10-31', 'proposal', '', '0981', 1.00, '2026-11-20', '', '2026-10-26 12:50:33'),
(195, 26, 'Psychology & Health: The Relationship Between Smartphone Screen Time and Sleep Quality Among Adolescents', 'Franklin Sierra', '', '2026-10-31', 'final', '', '0981', 1.00, '2026-11-20', '', '2026-10-29 09:09:23'),
(235, 79, 'Digital Preservation Readiness of Local Cultural Heritage Collections: A Framework for Municipal Archives', 'Saoirse Conrad', '', '2026-11-09', 'proposal', '', '111', 222.00, '2026-10-05', '', '2026-10-26 15:58:27'),
(241, 8, 'Psychology & Health: The Relationship Between Smartphone Screen Time and Sleep Quality Among Adolescents', 'Franklin Sierra', '', '2026-10-31', 'final', '', '0981', 1.00, '2026-11-20', '', '2026-10-26 12:43:25'),
(265, 12, 'Psychology & Health: The Relationship Between Smartphone Screen Time and Sleep Quality Among Adolescents', 'Franklin Sierra', '', '2026-10-31', 'concept', '', '0981', 1.00, '2026-11-20', '', '2026-10-26 12:38:54'),
(292, 7, '', 'Franklin Sierra', '', '2026-10-31', 'concept', '', NULL, NULL, NULL, '', '2026-10-26 14:38:43'),
(316, 82, 'Pentagonal Numbers', 'Engr. Belino Rosagaron', '', '2026-10-27', 'proposal', '', NULL, NULL, NULL, '', '2026-10-27 06:26:28'),
(317, 86, 'An Adaptive Load-Balancing Strategy for Containerized Microservices in Campus Data Centers', 'Villanueva, Ramon P.', '', '2023-06-19', 'concept', '', 'OR-2026-1004', 1.00, '2023-11-22', '', '2026-10-28 18:49:35');

-- --------------------------------------------------------

--
-- Table structure for table `documenter_pool`
--

CREATE TABLE `documenter_pool` (
  `documenter_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `availability` enum('available','unavailable') NOT NULL DEFAULT 'available',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `documenter_pool`
--

INSERT INTO `documenter_pool` (`documenter_id`, `first_name`, `middle_name`, `last_name`, `qualification`, `email`, `availability`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Maki', 'R', 'Miyu', 'MS in Information Technology', 'makimiyu@gmail.com', 'available', 'MSIT infrastructure achieves high availability by combining containerized microservices, Active-Active load balancing, and redundant RAID storage.', '2026-10-23 13:30:46', '2026-10-23 13:30:46'),
(3, 'Carla', 'M.', 'Bituin', 'MS in Information Technology', 'cbituin@adzu.edu.ph', 'available', 'Graduate School documenter.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(4, 'Daniel', 'F.', 'Ocampo', 'MS in Computer Science', 'docampo@adzu.edu.ph', 'available', 'Graduate School documenter.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(5, 'Michelle', 'P.', 'Reyes', 'MS in Mathematics Education', 'mpreyes@adzu.edu.ph', 'available', 'Graduate School documenter.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(6, 'Paolo', 'G.', 'Santos', 'MS in Science Education', 'pgsantos@adzu.edu.ph', 'available', 'Graduate School documenter.', '2026-10-28 15:32:55', '2026-10-28 15:32:55');

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
(4, 'Lisa', NULL, 'Fernandez', 'MS in Information Technology', 'fernandez@adzu.edu.ph', 'available', 'Demo panel member', '2026-09-30 09:34:20', '2026-09-30 09:34:20'),
(7, 'Teresa', 'B.', 'Aquino', 'PhD in Computer Science', 'taquino@adzu.edu.ph', 'available', 'Graduate School panel member.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(8, 'Martin', 'D.', 'Salazar', 'PhD in Information Technology', 'msalazar@adzu.edu.ph', 'available', 'Graduate School panel member.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(9, 'Rebecca', 'C.', 'Domingo', 'PhD in Mathematics Education', 'rdomingo@adzu.edu.ph', 'available', 'Graduate School panel member.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(10, 'Alfredo', 'R.', 'Pascual', 'PhD in Science Education', 'apascual@adzu.edu.ph', 'available', 'Graduate School panel member.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(11, 'Jonathan', 'K.', 'Lim', 'MS in Mechanical Engineering', 'jlim@adzu.edu.ph', 'available', 'Graduate School panel member.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(12, 'Veronica', 'S.', 'Cruz', 'PhD in Library and Information Science', 'vcruz@adzu.edu.ph', 'available', 'Graduate School panel member.', '2026-10-28 15:32:55', '2026-10-28 15:32:55');

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
(1, 20, 2, '231231', '2026-09-30', 1.00, NULL, NULL, '2026-09-30 10:26:04', '2026-09-30 10:26:04'),
(3, 18, 2, 'R-12345', '2026-06-01', 1500.00, NULL, NULL, '2026-10-21 05:49:05', '2026-10-21 05:49:05'),
(19, 45, 26, '11231', '2026-11-06', 1200.00, NULL, NULL, '2026-10-22 14:29:30', '2026-10-23 05:23:21'),
(21, 108, 26, '0981', '2026-11-04', 1700.00, NULL, NULL, '2026-10-23 06:59:52', '2026-10-23 06:59:52'),
(22, 110, 79, '111', '2026-10-05', 222.00, NULL, NULL, '2026-10-25 15:47:27', '2026-10-25 15:47:27'),
(23, 116, 2, 'OR-2026-1001', '2025-06-25', 1500.00, 137, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(24, 118, 2, 'OR-2026-1002', '2024-05-22', 1500.00, 143, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(25, 119, 2, 'OR-2026-1003', '2026-10-01', 1500.00, 147, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(26, 120, 2, 'OR-2026-1004', '2023-11-22', 1500.00, 151, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(27, 121, 2, 'OR-2026-1005', '2025-02-26', 1500.00, 155, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(28, 122, 2, 'OR-2026-1006', '2026-09-30', 1500.00, 159, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(29, 123, 2, 'OR-2026-1007', '2022-10-19', 1500.00, 163, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(30, 124, 2, 'OR-2026-1008', '2024-01-31', 1500.00, 167, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(31, 125, 2, 'OR-2026-1009', '2026-06-17', 1500.00, 171, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(32, 126, 2, 'OR-2026-1010', '2024-08-21', 1500.00, 175, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(33, 128, 2, 'OR-2026-1011', '2023-07-26', 1500.00, 182, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(34, 129, 2, 'OR-2026-1012', '2026-09-29', 1500.00, 186, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(35, 130, 2, 'OR-2026-1013', '2025-01-29', 1500.00, 190, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(36, 131, 2, 'OR-2026-1014', '2026-01-28', 1500.00, 194, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(37, 135, 2, 'OR-2026-1015', '2026-10-02', 1500.00, 203, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(38, 136, 2, 'OR-2026-1016', '2025-07-23', 1500.00, 207, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(39, 137, 2, 'OR-2026-1017', '2026-10-05', 1500.00, 211, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(40, 138, 2, 'OR-2026-1018', '2022-12-21', 1500.00, 215, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(41, 139, 2, 'OR-2026-1019', '2026-05-20', 1500.00, 219, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(42, 140, 2, 'OR-2026-1020', '2023-08-23', 1500.00, 223, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(43, 141, 2, 'OR-2026-1021', '2026-10-06', 1500.00, 227, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(44, 143, 2, 'OR-2026-1022', '2026-02-04', 1500.00, 232, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(45, 146, 2, 'OR-2026-1023', '2025-09-24', 1500.00, 243, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(46, 149, 2, 'OR-2026-1024', '2024-03-20', 1500.00, 249, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(47, 150, 2, 'OR-2026-1025', '2026-10-13', 1500.00, 253, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(48, 151, 2, 'OR-2026-1026', '2025-08-20', 1500.00, 257, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(49, 152, 2, 'OR-2026-1027', '2026-10-02', 1500.00, 261, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(50, 153, 2, 'OR-2026-1028', '2025-11-26', 1500.00, 265, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(51, 155, 2, 'OR-2026-1029', '2022-08-24', 1500.00, 271, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(52, 156, 2, 'OR-2026-1030', '2026-04-29', 1500.00, 275, 'Payment verified by the Graduate School coordinator.', '2026-10-28 15:32:56', '2026-10-28 15:32:56');

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
(3, 3, 'MATH', 'Master in Mathematics Education', 'Seminar paper workflow program', 1, '2026-09-26 09:34:53'),
(4, 2, 'MSED_CHEM', 'Master in Science Education major in Chemistry', NULL, 1, '2026-10-21 17:45:45'),
(5, 2, 'MSED_GS', 'Master in Science Education major in General Science', NULL, 1, '2026-10-21 17:45:45'),
(6, 2, 'MSED_BIO', 'Master in Science Education major in Biology', NULL, 1, '2026-10-21 17:45:45'),
(7, 2, 'MSED_PHY', 'Master in Science Education major in Physics', NULL, 1, '2026-10-21 17:45:45'),
(8, 2, 'MLIS', 'Master in Library and Information System', NULL, 1, '2026-10-21 17:45:45'),
(12, 1, 'MSLIS', 'Master of Library and Information Science', NULL, 1, '2026-10-22 14:49:27'),
(13, 2, 'MSME', 'Master of Science in Mechanical Engineering', NULL, 1, '2026-10-22 14:49:27');

-- --------------------------------------------------------

--
-- Table structure for table `program_tracks`
--

CREATE TABLE `program_tracks` (
  `program_id` int(11) NOT NULL,
  `track_id` int(11) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `program_tracks`
--

INSERT INTO `program_tracks` (`program_id`, `track_id`, `is_primary`) VALUES
(1, 1, 1),
(2, 2, 1),
(3, 3, 1),
(4, 2, 1),
(4, 3, 0),
(5, 2, 1),
(5, 3, 0),
(6, 2, 1),
(6, 3, 0),
(7, 2, 1),
(7, 3, 0),
(8, 2, 1),
(12, 1, 1),
(13, 2, 1),
(13, 3, 0);

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
(4, 45, 2, '2026-10-10', '04:55:00', NULL, 'CSITE Seminar Room', 1, 'Juan', '(PhD in Computer Science)', 'Dela Cruz', 'completed', NULL, '2026-10-23 05:53:31', '2026-10-28 16:40:10'),
(10, 110, 2, '2026-10-10', '11:50:00', NULL, 'CSITE Seminar Room', 2, 'Maki', 'R', 'Miyu', 'completed', NULL, '2026-10-25 15:49:15', '2026-10-28 16:40:10'),
(13, 108, 2, '2026-10-10', '00:15:00', NULL, 'CSITE Seminar Room', 2, 'Maki', 'R', 'Miyu', 'completed', NULL, '2026-10-25 16:16:45', '2026-10-28 16:40:10'),
(14, 116, 2, '2025-07-12', '09:00:00', '11:00:00', 'CSITE Seminar Room', 9, 'Carla', 'M.', 'Bituin', 'completed', 'Concept paper approved by the panel.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(15, 118, 2, '2024-06-15', '09:00:00', '11:00:00', 'CSITE Seminar Room', 13, 'Michelle', 'P.', 'Reyes', 'completed', 'Concept paper approved.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(16, 120, 2, '2023-12-09', '09:00:00', '11:00:00', 'CSITE Seminar Room', 8, 'Daniel', 'F.', 'Ocampo', 'completed', 'Concept paper approved.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(17, 121, 2, '2025-03-15', '09:00:00', '11:30:00', 'CSITE Seminar Room', 8, 'Paolo', 'G.', 'Santos', 'completed', 'Thesis proposal approved with minor comments.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(18, 122, 2, '2026-11-14', '09:00:00', '11:30:00', 'CSITE Seminar Room', 8, 'Michelle', 'P.', 'Reyes', 'completed', 'Final defense confirmed by the panel.', '2026-10-28 15:32:55', '2026-10-28 16:48:44'),
(19, 123, 2, '2022-11-12', '09:00:00', '11:00:00', 'CSITE Seminar Room', 9, 'Carla', 'M.', 'Bituin', 'completed', 'Concept paper approved.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(20, 124, 2, '2024-02-17', '09:00:00', '11:30:00', 'CSITE Seminar Room', 9, 'Daniel', 'F.', 'Ocampo', 'completed', 'Thesis proposal approved.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(21, 125, 2, '2026-07-18', '09:00:00', '11:30:00', 'CSITE Seminar Room', 9, 'Paolo', 'G.', 'Santos', 'completed', 'Final thesis defense passed.', '2026-10-28 15:32:55', '2026-10-28 16:39:45'),
(22, 126, 2, '2024-09-14', '09:00:00', '11:00:00', 'CSITE Seminar Room', 13, 'Michelle', 'P.', 'Reyes', 'completed', 'Concept paper approved.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(23, 128, 2, '2023-08-19', '09:00:00', '11:00:00', 'CSITE Seminar Room', 8, 'Carla', 'M.', 'Bituin', 'completed', 'Concept paper approved.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(24, 130, 2, '2025-02-15', '09:00:00', '11:00:00', 'CSITE Seminar Room', 13, 'Daniel', 'F.', 'Ocampo', 'completed', 'Concept paper approved.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(25, 131, 2, '2026-02-21', '09:00:00', '11:30:00', 'CSITE Seminar Room', 13, 'Paolo', 'G.', 'Santos', 'completed', 'Thesis proposal approved.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(26, 136, 2, '2025-08-16', '09:00:00', '11:00:00', 'CSITE Seminar Room', 13, 'Michelle', 'P.', 'Reyes', 'completed', 'Capstone proposal approved.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(27, 137, 2, '2026-11-07', '13:00:00', '15:30:00', 'CSITE Seminar Room', 13, 'Carla', 'M.', 'Bituin', 'confirmed', 'Final capstone presentation confirmed.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(28, 138, 2, '2023-01-14', '09:00:00', '11:00:00', 'CSITE Seminar Room', 8, 'Paolo', 'G.', 'Santos', 'completed', 'Capstone proposal approved.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(29, 139, 2, '2026-06-13', '09:00:00', '11:30:00', 'CSITE Seminar Room', 8, 'Daniel', 'F.', 'Ocampo', 'completed', 'Final capstone defense passed.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(30, 140, 2, '2023-09-16', '09:00:00', '11:00:00', 'CSITE Seminar Room', 9, 'Michelle', 'P.', 'Reyes', 'completed', 'Capstone proposal approved.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(31, 143, 2, '2026-03-07', '09:00:00', '11:00:00', 'CSITE Seminar Room', 11, 'Paolo', 'G.', 'Santos', 'completed', 'Capstone proposal approved.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(32, 146, 2, '2025-10-18', '09:00:00', '11:00:00', 'CSITE Seminar Room', 12, 'Carla', 'M.', 'Bituin', 'completed', 'Capstone proposal approved.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(33, 149, 2, '2024-04-13', '09:00:00', '11:00:00', 'CSITE Seminar Room', 10, 'Michelle', 'P.', 'Reyes', 'completed', 'Seminar paper proposal approved.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(34, 150, 2, '2026-11-21', '09:00:00', '11:00:00', 'CSITE Seminar Room', 10, 'Daniel', 'F.', 'Ocampo', 'confirmed', 'Final seminar paper presentation confirmed.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(35, 151, 2, '2025-09-13', '09:00:00', '11:00:00', 'CSITE Seminar Room', 11, 'Paolo', 'G.', 'Santos', 'completed', 'Seminar paper proposal approved.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(36, 153, 2, '2025-12-13', '09:00:00', '11:00:00', 'CSITE Seminar Room', 11, 'Carla', 'M.', 'Bituin', 'completed', 'Seminar paper proposal approved.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(37, 155, 2, '2022-09-17', '09:00:00', '11:00:00', 'CSITE Seminar Room', 11, 'Michelle', 'P.', 'Reyes', 'completed', 'Seminar paper proposal approved.', '2026-10-28 15:32:55', '2026-10-28 15:32:55'),
(38, 156, 2, '2026-05-16', '09:00:00', '11:00:00', 'CSITE Seminar Room', 11, 'Paolo', 'G.', 'Santos', 'completed', 'Final seminar paper presentation passed.', '2026-10-28 15:32:56', '2026-10-28 15:32:56');

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
(68, 4, 1, 'member', NULL, '2026-10-25 14:56:49'),
(69, 4, 4, 'member', NULL, '2026-10-25 14:56:49'),
(70, 4, 3, 'member', NULL, '2026-10-25 14:56:49'),
(92, 10, 1, 'member', NULL, '2026-10-25 16:36:33'),
(93, 10, 4, 'member', NULL, '2026-10-25 16:36:33'),
(94, 10, 2, 'member', NULL, '2026-10-25 16:36:33'),
(95, 13, 1, 'member', NULL, '2026-10-25 16:36:57'),
(96, 13, 2, 'member', NULL, '2026-10-25 16:36:57'),
(97, 13, 3, 'member', NULL, '2026-10-25 16:36:57'),
(98, 14, 7, 'chair', NULL, '2026-10-28 15:32:55'),
(99, 14, 8, 'member', NULL, '2026-10-28 15:32:55'),
(100, 14, 9, 'member', NULL, '2026-10-28 15:32:55'),
(101, 15, 10, 'chair', NULL, '2026-10-28 15:32:55'),
(102, 15, 11, 'member', NULL, '2026-10-28 15:32:55'),
(103, 15, 12, 'member', NULL, '2026-10-28 15:32:55'),
(104, 16, 8, 'chair', NULL, '2026-10-28 15:32:55'),
(105, 16, 10, 'member', NULL, '2026-10-28 15:32:55'),
(106, 16, 12, 'member', NULL, '2026-10-28 15:32:55'),
(107, 17, 7, 'chair', NULL, '2026-10-28 15:32:55'),
(108, 17, 9, 'member', NULL, '2026-10-28 15:32:55'),
(109, 17, 11, 'member', NULL, '2026-10-28 15:32:55'),
(110, 18, 9, 'chair', NULL, '2026-10-28 15:32:55'),
(111, 18, 10, 'member', NULL, '2026-10-28 15:32:55'),
(112, 18, 11, 'member', NULL, '2026-10-28 15:32:55'),
(113, 19, 7, 'chair', NULL, '2026-10-28 15:32:55'),
(114, 19, 8, 'member', NULL, '2026-10-28 15:32:55'),
(115, 19, 12, 'member', NULL, '2026-10-28 15:32:55'),
(116, 20, 8, 'chair', NULL, '2026-10-28 15:32:55'),
(117, 20, 9, 'member', NULL, '2026-10-28 15:32:55'),
(118, 20, 10, 'member', NULL, '2026-10-28 15:32:55'),
(122, 22, 9, 'chair', NULL, '2026-10-28 15:32:55'),
(123, 22, 10, 'member', NULL, '2026-10-28 15:32:55'),
(124, 22, 12, 'member', NULL, '2026-10-28 15:32:55'),
(125, 23, 7, 'chair', NULL, '2026-10-28 15:32:55'),
(126, 23, 10, 'member', NULL, '2026-10-28 15:32:55'),
(127, 23, 11, 'member', NULL, '2026-10-28 15:32:55'),
(128, 24, 7, 'chair', NULL, '2026-10-28 15:32:55'),
(129, 24, 8, 'member', NULL, '2026-10-28 15:32:55'),
(130, 24, 9, 'member', NULL, '2026-10-28 15:32:55'),
(131, 25, 10, 'chair', NULL, '2026-10-28 15:32:55'),
(132, 25, 11, 'member', NULL, '2026-10-28 15:32:55'),
(133, 25, 12, 'member', NULL, '2026-10-28 15:32:55'),
(134, 26, 8, 'chair', NULL, '2026-10-28 15:32:55'),
(135, 26, 10, 'member', NULL, '2026-10-28 15:32:55'),
(136, 26, 12, 'member', NULL, '2026-10-28 15:32:55'),
(137, 27, 7, 'chair', NULL, '2026-10-28 15:32:55'),
(138, 27, 9, 'member', NULL, '2026-10-28 15:32:55'),
(139, 27, 11, 'member', NULL, '2026-10-28 15:32:55'),
(140, 28, 9, 'chair', NULL, '2026-10-28 15:32:55'),
(141, 28, 10, 'member', NULL, '2026-10-28 15:32:55'),
(142, 28, 11, 'member', NULL, '2026-10-28 15:32:55'),
(143, 29, 7, 'chair', NULL, '2026-10-28 15:32:55'),
(144, 29, 8, 'member', NULL, '2026-10-28 15:32:55'),
(145, 29, 12, 'member', NULL, '2026-10-28 15:32:55'),
(146, 30, 8, 'chair', NULL, '2026-10-28 15:32:55'),
(147, 30, 9, 'member', NULL, '2026-10-28 15:32:55'),
(148, 30, 10, 'member', NULL, '2026-10-28 15:32:55'),
(149, 31, 7, 'chair', NULL, '2026-10-28 15:32:55'),
(150, 31, 11, 'member', NULL, '2026-10-28 15:32:55'),
(151, 31, 12, 'member', NULL, '2026-10-28 15:32:55'),
(152, 32, 9, 'chair', NULL, '2026-10-28 15:32:55'),
(153, 32, 10, 'member', NULL, '2026-10-28 15:32:55'),
(154, 32, 12, 'member', NULL, '2026-10-28 15:32:55'),
(155, 33, 7, 'chair', NULL, '2026-10-28 15:32:55'),
(156, 33, 10, 'member', NULL, '2026-10-28 15:32:55'),
(157, 33, 11, 'member', NULL, '2026-10-28 15:32:55'),
(158, 34, 7, 'chair', NULL, '2026-10-28 15:32:55'),
(159, 34, 8, 'member', NULL, '2026-10-28 15:32:55'),
(160, 34, 9, 'member', NULL, '2026-10-28 15:32:55'),
(161, 35, 10, 'chair', NULL, '2026-10-28 15:32:55'),
(162, 35, 11, 'member', NULL, '2026-10-28 15:32:55'),
(163, 35, 12, 'member', NULL, '2026-10-28 15:32:55'),
(164, 36, 8, 'chair', NULL, '2026-10-28 15:32:55'),
(165, 36, 10, 'member', NULL, '2026-10-28 15:32:55'),
(166, 36, 12, 'member', NULL, '2026-10-28 15:32:55'),
(167, 37, 7, 'chair', NULL, '2026-10-28 15:32:55'),
(168, 37, 9, 'member', NULL, '2026-10-28 15:32:55'),
(169, 37, 11, 'member', NULL, '2026-10-28 15:32:55'),
(170, 38, 9, 'chair', NULL, '2026-10-28 15:32:56'),
(171, 38, 10, 'member', NULL, '2026-10-28 15:32:56'),
(172, 38, 11, 'member', NULL, '2026-10-28 15:32:56'),
(179, 21, 7, 'member', NULL, '2026-10-28 16:13:16'),
(180, 21, 12, 'member', NULL, '2026-10-28 16:13:16'),
(181, 21, 11, 'member', NULL, '2026-10-28 16:13:16');

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
(222222, 26, 'Mila', 'Afus', 'R', 22, 'Male', 'MSCS', 'thesis', 'Franklin Sierra', '2026-10-21', NULL),
(230159, 5, 'Robbie', 'Torres', 'E', 21, 'Male', 'Master of Science in Computer Science (Thesis)', 'thesis', NULL, '2026-09-12', NULL),
(240092, 8, 'John', 'Gler', '', 21, 'Male', 'Master in Information Technology', 'capstone', NULL, '2026-09-13', NULL),
(240164, 82, 'Shalimar', 'Jaji', 'A', 40, 'Male', 'MATH', 'seminar', 'Engr. Belino Rosagaron', '2026-10-27', NULL),
(240234, 7, 'Rhett', 'Epino', '', 21, 'Male', 'MSCS', 'thesis', 'Franklin Sierra', '2026-09-13', NULL),
(240527, 6, 'MARC', 'ARBILERA', 'M', 21, 'Male', 'Master of Science in Computer Science (Thesis)', 'thesis', NULL, '2026-09-12', '2026-10-15 17:00:48'),
(250251, 12, 'James', 'Austin', 'R', 22, 'Male', 'Master in Science Education major in Chemistry (Capstone)', 'capstone', NULL, '2026-10-13', NULL),
(251001, 83, 'Maria Clara', 'Alonzo', 'D', 26, 'Female', 'Master of Science in Computer Science', 'thesis', 'Villanueva, Ramon P.', '2025-08-11', NULL),
(251002, 84, 'Rafael', 'Benedicto', 'L', 29, 'Male', 'Master of Science in Computer Science', 'thesis', 'Navarro, Cecilia M.', '2024-08-05', NULL),
(251003, 85, 'Liza Marie', 'Cabrera', 'S', 31, 'Female', 'Master of Library and Information Science', 'thesis', 'Ramos, Isabel T.', '2023-08-14', NULL),
(251004, 86, 'Angelo', 'Dizon', 'P', 33, 'Male', 'Master of Science in Computer Science', 'thesis', 'Villanueva, Ramon P.', '2023-06-19', NULL),
(251005, 87, 'Hannah Jane', 'Estrada', 'R', 35, 'Female', 'Master of Science in Computer Science', 'thesis', 'Navarro, Cecilia M.', '2022-06-13', NULL),
(251006, 88, 'Miguel Antonio', 'Fajardo', 'C', 28, 'Male', 'Master of Library and Information Science', 'thesis', 'Ramos, Isabel T.', '2024-01-15', NULL),
(251007, 89, 'Patricia Anne', 'Galvez', 'M', 30, 'Female', 'Master of Science in Computer Science', 'thesis', 'Villanueva, Ramon P.', '2023-01-09', NULL),
(251008, 90, 'Kevin Paul', 'Hernandez', 'T', 27, 'Male', 'Master of Library and Information Science', 'thesis', 'Ramos, Isabel T.', '2024-08-19', NULL),
(251009, 91, 'Sofia Nicole', 'Ibarra', 'B', 25, 'Female', 'Master of Science in Computer Science', 'thesis', 'Navarro, Cecilia M.', '2025-01-13', NULL),
(251010, 92, 'Carlo Emmanuel', 'Javier', 'D', 26, 'Male', 'Master in Information Technology', 'capstone', 'Villanueva, Ramon P.', '2025-08-18', NULL),
(251011, 93, 'Angela Rose', 'Katigbak', 'F', 29, 'Female', 'Master in Information Technology', 'capstone', 'Navarro, Cecilia M.', '2024-08-12', NULL),
(251012, 94, 'Robert John', 'Lagman', 'S', 32, 'Male', 'Master in Library and Information System', 'capstone', 'Ramos, Isabel T.', '2023-08-07', NULL),
(251013, 95, 'Denise Claire', 'Magbanua', 'P', 34, 'Female', 'Master in Information Technology', 'capstone', 'Villanueva, Ramon P.', '2022-08-15', NULL),
(251014, 96, 'Christian James', 'Ocampo', 'L', 30, 'Male', 'Master in Information Technology', 'capstone', 'Navarro, Cecilia M.', '2023-01-16', NULL),
(251015, 97, 'Jasmine Mae', 'Peralta', 'C', 24, 'Female', 'Master in Science Education major in Chemistry', 'capstone', 'Mendoza, Patricia A.', '2025-01-20', NULL),
(251016, 98, 'Mark Anthony', 'Quijano', 'R', 31, 'Male', 'Master in Science Education major in Biology', 'capstone', 'Mendoza, Patricia A.', '2023-08-21', NULL),
(251017, 99, 'Erika Lyn', 'Rivera', 'D', 27, 'Female', 'Master in Library and Information System', 'capstone', 'Ramos, Isabel T.', '2024-01-22', NULL),
(251018, 100, 'Paolo Miguel', 'Salvador', 'T', 29, 'Male', 'Master of Science in Mechanical Engineering', 'capstone', 'Garcia, Fernando S.', '2024-08-26', NULL),
(251019, 101, 'Alyssa Marie', 'Tamayo', 'B', 25, 'Female', 'Master in Mathematics Education', 'seminar', 'Bautista, Eduardo L.', '2025-08-25', NULL),
(251020, 102, 'Samantha Joy', 'Uy', 'L', 28, 'Female', 'Master in Mathematics Education', 'seminar', 'Bautista, Eduardo L.', '2023-01-23', NULL),
(251021, 103, 'Patricia Nicole', 'Valdez', 'M', 26, 'Female', 'Master in Science Education major in Physics', 'seminar', 'Mendoza, Patricia A.', '2023-08-28', NULL),
(251022, 104, 'John Patrick', 'Wenceslao', 'C', 30, 'Male', 'Master in Science Education major in General Science', 'seminar', 'Mendoza, Patricia A.', '2024-01-29', NULL),
(251023, 105, 'Rachel Anne', 'Yumul', 'S', 33, 'Female', 'Master in Science Education major in Chemistry', 'seminar', 'Mendoza, Patricia A.', '2022-01-17', NULL),
(333333, 79, 'Leighton', 'Kramer', 'Z', 22, 'Female', 'MSLIS', 'thesis', 'Saoirse Conrad', '2026-10-25', NULL),
(2402323, 14, 'Kay', 'Lee', 'R', 22, 'Male', 'MATH', 'seminar', 'Aubree Jennings', '2026-10-20', NULL);

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
  -- Respect an explicitly typed ADZU ID number; derive from the email only
  -- when the row arrives without one (student_id 0/NULL from AUTO_INCREMENT).
  IF (NEW.`student_id` IS NULL OR NEW.`student_id` = 0) THEN
  SELECT `email` INTO v_email FROM `users` WHERE `user_id` = NEW.`user_id` LIMIT 1;
  IF (v_email IS NOT NULL AND v_email <> '') THEN
    SET v_digits = REGEXP_REPLACE(SUBSTRING_INDEX(v_email, '@', 1), '[^0-9]', '');
  END IF;
  IF (v_digits IS NOT NULL AND v_digits <> '') THEN
    -- Fallback ID = numeric part of the ADZU email (e.g. co259344 -> 259344).
    SELECT COUNT(*) INTO v_taken FROM `students` WHERE `student_id` = CAST(v_digits AS UNSIGNED);
    IF (v_taken = 0) THEN
      SET NEW.`student_id` = CAST(v_digits AS UNSIGNED);
    END IF;
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
  `drive_file_id` varchar(120) DEFAULT NULL,
  `drive_modified_at` datetime DEFAULT NULL,
  `drive_size` bigint(20) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `needs_review` tinyint(1) NOT NULL DEFAULT 0,
  `managed_by_user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `templates`
--

INSERT INTO `templates` (`template_id`, `track_id`, `stage_id`, `template_name`, `description`, `document_type`, `file_name`, `file_path`, `drive_file_id`, `drive_modified_at`, `drive_size`, `mime_type`, `is_active`, `needs_review`, `managed_by_user_id`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'Graduate School Thesis Format', 'Official format for thesis documents', 'template', 'GRADUATE SCHOOL - CSITE - THESIS FORMAT.docx', 'assets/papers/THESIS/GRADUATE SCHOOL - CSITE - THESIS FORMAT.docx', '13xI7XayeOkmf-3FrZn3Y0B29wGgIjP_q', '2026-10-21 17:42:27', 10056, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 0, 2, '2026-09-30 09:20:23', '2026-10-21 17:45:45'),
(2, 1, 1, 'Concept Paper Adviser Endorsement Form', 'Signed by the research adviser', 'form', '1 CONCEPT PAPER ADVISER ENDORSEMENT FORM_.docx', 'assets/papers/THESIS/1 CONCEPT PAPER ADVISER ENDORSEMENT FORM_.docx', '1n7VUoaoCkGYXtVXQz12RfzIrM21j3N3L', '2026-10-21 17:42:28', 4033837, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 0, 2, '2026-09-30 09:20:23', '2026-10-21 18:36:38'),
(3, 1, 1, 'Concept Paper Endorsement to Graduate School', 'Coordinator endorsement form', 'form', '2 CONCEPT PAPER ENDORSEMENT TO GRADSCHOOL.docx', 'assets/papers/THESIS/2 CONCEPT PAPER ENDORSEMENT TO GRADSCHOOL.docx', '18VG29zTiCYFsl4LI6Du9LWsmzi7MOHCC', '2026-10-21 17:42:27', 908888, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 0, 2, '2026-09-30 09:20:23', '2026-10-21 16:22:31'),
(4, 1, 2, 'Thesis Proposal Adviser Endorsement Form', 'Signed by the research adviser', 'form', '3 THESIS PROPOSAL ADVISER ENDORSEMENT FORM.docx', 'assets/papers/THESIS/3 THESIS PROPOSAL ADVISER ENDORSEMENT FORM.docx', '1129a64xrns-n5Dnf1lRc04XkytTXTaGN', '2026-10-21 17:42:31', 4033879, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 0, 2, '2026-09-30 09:20:23', '2026-10-21 16:22:31'),
(5, 1, 2, 'Thesis Proposal Endorsement to Graduate School', 'Coordinator endorsement form', 'form', '4 THESIS PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'assets/papers/THESIS/4 THESIS PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', '1O0LWrHQKXcT6GKGlHugV698KU1DNZUZP', '2026-10-21 17:42:27', 908872, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 0, 2, '2026-09-30 09:20:23', '2026-10-21 16:22:31'),
(6, 1, 3, 'Final Thesis Adviser Endorsement Form', 'Signed by the research adviser', 'form', '5 FINAL THESIS ADVISER ENDORSEMENT FORM.docx', 'assets/papers/THESIS/5 FINAL THESIS ADVISER ENDORSEMENT FORM.docx', '1uSPcSdyfBfKwTNhlTuHSbgkt6I5zh98f', '2026-10-21 17:42:35', 4033772, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 0, 2, '2026-09-30 09:20:23', '2026-10-21 16:22:31'),
(7, 1, 3, 'Final Thesis Endorsement to Graduate School', 'Coordinator endorsement form', 'form', '6 FINAL THESIS ENDORSEMENT TO GRADSCHOOL.docx', 'assets/papers/THESIS/6 FINAL THESIS ENDORSEMENT TO GRADSCHOOL.docx', '1QQcUOZb5m8YZQ6bvDDjeefM3fjsUAPSj', '2026-10-21 17:42:27', 909098, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 0, 2, '2026-09-30 09:20:23', '2026-10-21 16:22:34'),
(8, 1, NULL, 'Final Thesis Cover Page', 'Final thesis cover page', 'reference', 'FINAL THESIS COVER PAGE v2026.docx', 'assets/papers/THESIS/FINAL THESIS COVER PAGE v2026.docx', '10RXf0MjgbloDX-ZRSodNqZuKcbHkrE-0', '2026-10-21 17:42:27', 139704, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 0, 2, '2026-09-30 09:20:23', '2026-10-21 16:22:34'),
(9, 2, NULL, 'Capstone Project Proposal Template', 'Official capstone proposal template', 'template', 'MIT GradSchool CAPSTONE PROJECT PROPOSAL TEMPLATE v2025.docx', 'assets/papers/CAPSTONE/MIT GradSchool CAPSTONE PROJECT PROPOSAL TEMPLATE v2025.docx', '1wo62bPTxq2YC0iUSzIJPCu4tMaGLcdy1', '2026-10-21 17:42:14', 516837, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 0, 2, '2026-09-30 09:20:23', '2026-10-21 17:45:45'),
(10, 2, 4, 'Capstone Adviser Endorsement Form', 'Signed by the capstone adviser', 'form', '1 CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'assets/papers/CAPSTONE/1 CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL.docx', '1QwO3JCORmIlfizn_-1DcNYFJfhFr0OSJ', '2026-10-22 10:13:15', 3975616, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 0, 2, '2026-09-30 09:20:23', '2026-10-22 15:07:51'),
(11, 2, 4, 'Capstone Proposal Endorsement to Graduate School', 'Coordinator endorsement form', 'form', '2 CAPSTONE PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'assets/papers/CAPSTONE/2 CAPSTONE PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', '15AqFMDvBH4HHjIlaBod4dK2mKTZvffoe', '2026-10-21 17:42:13', 980944, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 0, 2, '2026-09-30 09:20:23', '2026-10-21 16:22:26'),
(12, 2, 5, 'Final Capstone Adviser Endorsement Form', 'Signed by the capstone adviser', 'form', '3 FINAL CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'assets/papers/CAPSTONE/3 FINAL CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL.docx', '1Iv4MDP68b9lhQallYRliYGP079NTr5Zo', '2026-10-21 17:42:18', 4152906, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 0, 2, '2026-09-30 09:20:23', '2026-10-21 16:22:26'),
(13, 2, 5, 'Final Capstone Endorsement to Graduate School', 'Coordinator endorsement form', 'form', '4 FINAL CAPSTONE ENDORSEMENT TO GRADSCHOOL.docx', 'assets/papers/CAPSTONE/4 FINAL CAPSTONE ENDORSEMENT TO GRADSCHOOL.docx', '1usv8PRIVu4FL63jsyqJC2mL0g8mxwSsx', '2026-10-21 17:42:14', 908876, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 0, 2, '2026-09-30 09:20:23', '2026-10-21 16:22:26'),
(14, 2, NULL, 'Capstone Guidelines', 'Capstone project guidelines', 'reference', 'MIT Capstone Guidelines.docx', 'assets/papers/CAPSTONE/MIT Capstone Guidelines.docx', '1Ov_3Nryj4ENXA44Ngi0FvaNr3d8rUeI6', '2026-10-21 17:42:13', 589438, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 0, 2, '2026-09-30 09:20:23', '2026-10-21 18:34:13'),
(15, 2, NULL, 'Final Capstone Cover Page', 'Final capstone cover page', 'reference', 'FINAL CAPSTONE COVER PAGE v2026.docx', 'assets/papers/CAPSTONE/FINAL CAPSTONE COVER PAGE v2026.docx', '1sduGE4VC2wC0KBUMrMbkV48n5X5q2Ein', '2026-10-21 17:42:13', 139710, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 0, 2, '2026-09-30 09:20:23', '2026-10-21 16:22:26'),
(16, 3, NULL, 'Seminar Paper Template Format', 'Official seminar paper format', 'template', 'MATH SEMINAR PAPER TEMPLATE FORMAT.docx', 'assets/papers/SEMINAR PAPER/MATH SEMINAR PAPER TEMPLATE FORMAT.docx', '1J-AnVhMPO4gafX4zrh5aExn-E4c1mAjm', '2026-10-21 17:42:19', 11183, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 0, 2, '2026-09-30 09:20:23', '2026-10-21 17:45:45'),
(17, 3, 6, 'Seminar Paper Proposal Adviser Endorsement Form', 'Signed by the research adviser', 'form', '1 SEMINAR PAPER PROPOSAL ADVISER ENDORSEMENT FORM .docx', 'assets/papers/SEMINAR PAPER/1 SEMINAR PAPER PROPOSAL ADVISER ENDORSEMENT FORM .docx', '1imQW6qTFjV9EohoqKavaKItuCXGXIYG5', '2026-10-21 17:42:21', 4127563, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 0, 2, '2026-09-30 09:20:23', '2026-10-21 16:22:29'),
(18, 3, 6, 'Seminar Paper Proposal Endorsement to Graduate School', 'Coordinator endorsement form', 'form', '2 SEMINAR PAPER PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'assets/papers/SEMINAR PAPER/2 SEMINAR PAPER PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', '1CHphjfJU2S3lFGrWJIvwdz9KzLVdd6Dw', '2026-10-21 17:42:19', 908886, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 0, 2, '2026-09-30 09:20:23', '2026-10-21 16:22:29'),
(19, 3, 7, 'Final Seminar Paper Adviser Endorsement Form', 'Signed by the research adviser', 'form', '3 FINAL SEMINAR PAPER ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'assets/papers/SEMINAR PAPER/3 FINAL SEMINAR PAPER ADVISER ENDORSEMENT FORM - PROPOSAL.docx', '1xW5sd1jORlRl9lrkk-DbEsmID1YBHLs3', '2026-10-21 17:42:24', 4127567, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 0, 2, '2026-09-30 09:20:23', '2026-10-21 16:22:29'),
(20, 3, 7, 'Final Seminar Paper Endorsement to Graduate School', 'The file for this form has not been uploaded yet. Add it in the Google Drive template folder and it will appear here.', 'form', '4 FINAL SEMINAR PAPER ENDORSEMENT TO GRADSCHOOL.docx', 'assets/papers/SEMINAR PAPER/2 SEMINAR PAPER PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', NULL, NULL, NULL, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 0, 1, 2, '2026-09-30 09:20:23', '2026-10-21 17:55:36'),
(21, 3, NULL, 'Final Seminar Paper Cover Page', 'Final seminar paper cover page', 'reference', 'FINAL SEMINAR PAPER COVER PAGE v2026.docx', 'assets/papers/SEMINAR PAPER/FINAL SEMINAR PAPER COVER PAGE v2026.docx', '1dqnmm5vlFlfUHizNtwiY54b7g8Rorlu5', '2026-10-21 17:42:19', 139710, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1, 0, 2, '2026-09-30 09:20:23', '2026-10-21 16:22:29');

-- --------------------------------------------------------

--
-- Table structure for table `template_drive_pending`
--

CREATE TABLE `template_drive_pending` (
  `drive_file_id` varchar(120) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `change_type` enum('added','updated') NOT NULL DEFAULT 'updated',
  `track_code` varchar(40) DEFAULT NULL,
  `drive_modified_at` datetime DEFAULT NULL,
  `drive_size` bigint(20) DEFAULT NULL,
  `detected_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `template_notifications`
--

CREATE TABLE `template_notifications` (
  `notification_id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `drive_file_id` varchar(120) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `change_type` enum('added','updated') NOT NULL DEFAULT 'updated',
  `changed_at` datetime NOT NULL,
  `track_code` varchar(40) DEFAULT NULL,
  `seen_student_at` datetime DEFAULT NULL,
  `seen_coordinator_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `template_sync_state`
--

CREATE TABLE `template_sync_state` (
  `sync_key` varchar(60) NOT NULL,
  `last_sync_at` datetime DEFAULT NULL,
  `last_status` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `template_sync_state`
--

INSERT INTO `template_sync_state` (`sync_key`, `last_sync_at`, `last_status`) VALUES
('google_drive_templates', '2026-10-28 11:04:36', 'Checked 20 files in Google Drive. Everything is up to date.'),
('google_drive_templates_check', '2026-10-29 14:38:11', '0 file(s) waiting to be synchronized');

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
(8, 'Gler, John', 'co240092@adzu.edu.ph', '$2y$10$CLoYu2tFUOlcWaAN7Oy58ejJHTq5E9qgamndnNcHXyinTGphbN5PW', 'student', '2026-09-13 06:27:11'),
(12, 'Austin, James R', 'co250250@adzu.edu.ph', '$2y$10$fcuxGZAIukzEvop8fH5RcuC1WcB9m6EVukdFTsQOlk/xhWOw8DLN2', 'student', '2026-10-12 23:30:16'),
(14, 'Lee, Kay R', 'co2402323@adzu.edu.ph', '$2y$10$3YFZVLwIk5iCV25y7dce.OYZ8sg8QgdF7VlTBYcjyMbNXuA8SaOUi', 'student', '2026-10-20 18:34:51'),
(26, 'Afus, Mila R', 'co222222@adzu.edu.ph', '$2y$10$9WdhZWdNlNCjqWTFdfr.bONxTmssVkvbOpTL4mFaSWBg/YdU/ayB2', 'student', '2026-10-21 06:29:53'),
(79, 'Kramer, Leighton Z', 'co333333@adzu.edu.ph', '$2y$10$1/kQfbz4AqeGCECsdhu/JepWXHGTdgr78eAYNeObSEwoZ/BuwaXwC', 'student', '2026-10-25 15:38:10'),
(82, 'Jaji, Shalimar A', 'jajisahaa@adzu.edu.ph', '$2y$10$fllMBs.hP68p6d1bSxXxAOyNSlIEwhcq5.gr2rHKT0X1mJwIMWgX6', 'student', '2026-10-27 06:19:44'),
(83, 'Alonzo, Maria Clara D', 'co251001@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(84, 'Benedicto, Rafael L', 'co251002@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(85, 'Cabrera, Liza Marie S', 'co251003@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(86, 'Dizon, Angelo P', 'co251004@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(87, 'Estrada, Hannah Jane R', 'co251005@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(88, 'Fajardo, Miguel Antonio C', 'co251006@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(89, 'Galvez, Patricia Anne M', 'co251007@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(90, 'Hernandez, Kevin Paul T', 'co251008@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(91, 'Ibarra, Sofia Nicole B', 'co251009@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(92, 'Javier, Carlo Emmanuel D', 'co251010@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(93, 'Katigbak, Angela Rose F', 'co251011@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(94, 'Lagman, Robert John S', 'co251012@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(95, 'Magbanua, Denise Claire P', 'co251013@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(96, 'Ocampo, Christian James L', 'co251014@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(97, 'Peralta, Jasmine Mae C', 'co251015@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(98, 'Quijano, Mark Anthony R', 'co251016@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(99, 'Rivera, Erika Lyn D', 'co251017@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(100, 'Salvador, Paolo Miguel T', 'co251018@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(101, 'Tamayo, Alyssa Marie B', 'co251019@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(102, 'Uy, Samantha Joy L', 'co251020@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(103, 'Valdez, Patricia Nicole M', 'co251021@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(104, 'Wenceslao, John Patrick C', 'co251022@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55'),
(105, 'Yumul, Rachel Anne S', 'co251023@adzu.edu.ph', '$2y$10$t8mlQWyixKfk8MXAgQ9atuF1OlBBszd86hY0DahVD7753L0Irb46i', 'student', '2026-10-28 15:32:55');

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
-- Indexes for table `application_drafts`
--
ALTER TABLE `application_drafts`
  ADD PRIMARY KEY (`draft_id`),
  ADD UNIQUE KEY `application_drafts_user` (`user_id`);

--
-- Indexes for table `documenter_pool`
--
ALTER TABLE `documenter_pool`
  ADD PRIMARY KEY (`documenter_id`),
  ADD KEY `idx_availability` (`availability`);

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
-- Indexes for table `program_tracks`
--
ALTER TABLE `program_tracks`
  ADD PRIMARY KEY (`program_id`,`track_id`),
  ADD KEY `idx_program_tracks_track` (`track_id`);

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
-- Indexes for table `template_drive_pending`
--
ALTER TABLE `template_drive_pending`
  ADD PRIMARY KEY (`drive_file_id`),
  ADD KEY `idx_template_drive_pending_track` (`track_code`);

--
-- Indexes for table `template_notifications`
--
ALTER TABLE `template_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD UNIQUE KEY `uq_template_notification` (`drive_file_id`,`changed_at`),
  ADD KEY `idx_template_notifications_track` (`track_code`);

--
-- Indexes for table `template_sync_state`
--
ALTER TABLE `template_sync_state`
  ADD PRIMARY KEY (`sync_key`);

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
  MODIFY `adviser_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=159;

--
-- AUTO_INCREMENT for table `application_comments`
--
ALTER TABLE `application_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `application_documents`
--
ALTER TABLE `application_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=284;

--
-- AUTO_INCREMENT for table `application_drafts`
--
ALTER TABLE `application_drafts`
  MODIFY `draft_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=323;

--
-- AUTO_INCREMENT for table `documenter_pool`
--
ALTER TABLE `documenter_pool`
  MODIFY `documenter_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `panel_members`
--
ALTER TABLE `panel_members`
  MODIFY `panel_member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `schedule_panel_assignments`
--
ALTER TABLE `schedule_panel_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=194;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2402362;

--
-- AUTO_INCREMENT for table `templates`
--
ALTER TABLE `templates`
  MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `template_notifications`
--
ALTER TABLE `template_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `tracks`
--
ALTER TABLE `tracks`
  MODIFY `track_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

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
-- Constraints for table `application_drafts`
--
ALTER TABLE `application_drafts`
  ADD CONSTRAINT `application_drafts_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

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
-- Constraints for table `program_tracks`
--
ALTER TABLE `program_tracks`
  ADD CONSTRAINT `fk_program_tracks_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_program_tracks_track` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`track_id`) ON DELETE CASCADE;

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
