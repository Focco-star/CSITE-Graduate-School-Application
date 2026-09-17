-- =============================================================================
-- 20260916_recreate_applications_table.sql  (user-approved repair)
-- -----------------------------------------------------------------------------
-- The live database's InnoDB dictionary still holds an orphaned tablespace
-- entry for `applications` (its .ibd/.frm were lost), so a plain CREATE TABLE
-- `applications` fails with 1813 "Tablespace exists". Workaround that avoids
-- dropping the whole database: create the table under a fresh name (allocates
-- a clean tablespace), seed it, then rename it over the ghost entry.
--
-- Run:  mysql -u root csite_grad_school < 20260916_recreate_applications_table.sql
-- =============================================================================

DROP TABLE IF EXISTS `applications`;

CREATE TABLE `applications_rebuild` (
  `application_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
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
  `archived_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`application_id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `applications_rebuild`
  (`application_id`, `student_id`, `presentation_stage`, `paper_title`, `status`, `coordinator_comment`, `grad_school_endorsed`, `payment_recorded`, `receipt_number`, `payment_date`, `payment_amount`, `ready_for_presentation`, `workflow_state`, `result`, `submitted_at`, `updated_at`) VALUES
(17, 4, 'Thesis Proposal', 'Machine Learning Approaches for Predictive Analytics in Graduate Education', 'under_review', '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, '2026-09-10 01:30:00', '2026-09-13 06:58:19'),
(18, 5, 'Final Capstone', 'Development of an Automated Graduate Application Tracking System', 'submitted', '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, '2026-09-11 06:15:00', '2026-09-13 06:58:19'),
(19, 6, 'Concept Paper', 'Cloud Security Protocols for Institutional Repositories', 'submitted', '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, '2026-09-12 02:00:00', '2026-09-13 06:58:19'),
(20, 7, 'Capstone Proposal', 'Mobile-Based Student Records and Notification Management', 'approved', '', 1, 1, NULL, NULL, NULL, 1, '{"paper":"verified","adviser_endorsement":"verified"}', 'approved', '2026-09-08 03:20:00', '2026-09-13 06:58:19'),
(21, 4, 'Final Thesis Defense', 'Optimizing Database Queries in Large-Scale Web Applications', 'requires_revision', 'Please strengthen the experimental validation chapter and resubmit.', 0, 0, NULL, NULL, NULL, 0, NULL, 'requires_revision', '2026-09-05 08:45:00', '2026-09-13 06:58:19');

DROP TABLE IF EXISTS `applications`;
RENAME TABLE `applications_rebuild` TO `applications`;

ALTER TABLE `applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;
