-- Comment banner that opens the header of this repair script.
-- =============================================================================
-- Names the file and marks it as a user-approved repair.
-- 20260916_recreate_applications_table.sql  (user-approved repair)
-- Divider line under the file name.
-- -----------------------------------------------------------------------------
-- Explains the orphaned InnoDB tablespace entry for applications.
-- The live database's InnoDB dictionary still holds an orphaned tablespace
-- Notes the data and form files for the table were lost.
-- entry for `applications` (its .ibd/.frm were lost), so a plain CREATE TABLE
-- Says a normal CREATE TABLE fails with a tablespace exists error.
-- `applications` fails with 1813 "Tablespace exists". Workaround that avoids
-- Explains the workaround that avoids dropping the whole database.
-- dropping the whole database: create the table under a fresh name (allocates
-- Notes a fresh table name gets a clean tablespace.
-- a clean tablespace), seed it, then rename it over the ghost entry.
-- Says the new table is seeded then renamed over the ghost name.
--
-- Shows the command used to run this script.
-- Run:  mysql -u root csite_grad_school < 20260916_recreate_applications_table.sql
-- Closes the header comment banner.
-- =============================================================================

-- Drops any existing applications table before rebuilding.
DROP TABLE IF EXISTS `applications`;

-- Creates the new table under the temporary rebuild name.
CREATE TABLE `applications_rebuild` (
  -- application_id is the auto-numbered primary key.
  `application_id` int(11) NOT NULL AUTO_INCREMENT,
  -- student_id links each application to a student.
  `student_id` int(11) NOT NULL,
  -- presentation_stage stores the workflow stage name.
  `presentation_stage` varchar(100) NOT NULL,
  -- paper_title stores the title of the student paper.
  `paper_title` text NOT NULL,
  -- status keeps one of the nine workflow status values.
  `status` enum('submitted','under_review','for_payment','payment_recorded','ready_for_presentation','scheduled','approved','requires_revision','completed') DEFAULT 'submitted',
  -- coordinator_comment stores coordinator notes for the stage.
  `coordinator_comment` text DEFAULT NULL,
  -- grad_school_endorsed flags that the Graduate School endorsed the stage.
  `grad_school_endorsed` tinyint(1) NOT NULL DEFAULT 0,
  -- payment_recorded flags that the fee was already paid.
  `payment_recorded` tinyint(1) NOT NULL DEFAULT 0,
  -- receipt_number stores the official receipt number.
  `receipt_number` varchar(100) DEFAULT NULL,
  -- payment_date stores when the payment was made.
  `payment_date` date DEFAULT NULL,
  -- payment_amount stores the amount paid.
  `payment_amount` decimal(10,2) DEFAULT NULL,
  -- ready_for_presentation flags that requirements were verified.
  `ready_for_presentation` tinyint(1) NOT NULL DEFAULT 0,
  -- workflow_state stores the per-document review states as JSON.
  `workflow_state` text DEFAULT NULL,
  -- result stores the presentation outcome.
  `result` varchar(50) DEFAULT NULL,
  -- submitted_at records when the application was submitted.
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  -- updated_at refreshes automatically whenever the row changes.
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  -- archived_at marks a soft-deleted application.
  `archived_at` timestamp NULL DEFAULT NULL,
  -- Makes application_id the primary key.
  PRIMARY KEY (`application_id`),
  -- Adds an index on student_id for faster lookups.
  KEY `student_id` (`student_id`)
-- Uses InnoDB and utf8mb4 for keys and special characters.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Starts inserting the saved seed rows into the new table.
INSERT INTO `applications_rebuild`
  -- Lists the columns being filled, in table order.
  (`application_id`, `student_id`, `presentation_stage`, `paper_title`, `status`, `coordinator_comment`, `grad_school_endorsed`, `payment_recorded`, `receipt_number`, `payment_date`, `payment_amount`, `ready_for_presentation`, `workflow_state`, `result`, `submitted_at`, `updated_at`) VALUES
-- First seed row: student 4, a thesis proposal under review.
(17, 4, 'Thesis Proposal', 'Machine Learning Approaches for Predictive Analytics in Graduate Education', 'under_review', '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, '2026-09-10 01:30:00', '2026-09-13 06:58:19'),
-- Second seed row: student 5, a final capstone submission.
(18, 5, 'Final Capstone', 'Development of an Automated Graduate Application Tracking System', 'submitted', '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, '2026-09-11 06:15:00', '2026-09-13 06:58:19'),
-- Third seed row: student 6, a concept paper submission.
(19, 6, 'Concept Paper', 'Cloud Security Protocols for Institutional Repositories', 'submitted', '', 0, 0, NULL, NULL, NULL, 0, NULL, NULL, '2026-09-12 02:00:00', '2026-09-13 06:58:19'),
-- Fourth seed row: student 7, an approved capstone proposal.
(20, 7, 'Capstone Proposal', 'Mobile-Based Student Records and Notification Management', 'approved', '', 1, 1, NULL, NULL, NULL, 1, '{"paper":"verified","adviser_endorsement":"verified"}', 'approved', '2026-09-08 03:20:00', '2026-09-13 06:58:19'),
-- Fifth seed row: student 4, a thesis defense needing revision.
(21, 4, 'Final Thesis Defense', 'Optimizing Database Queries in Large-Scale Web Applications', 'requires_revision', 'Please strengthen the experimental validation chapter and resubmit.', 0, 0, NULL, NULL, NULL, 0, NULL, 'requires_revision', '2026-09-05 08:45:00', '2026-09-13 06:58:19');

-- Drops the old applications table before the rename.
DROP TABLE IF EXISTS `applications`;
-- Renames the rebuilt table to applications.
RENAME TABLE `applications_rebuild` TO `applications`;

-- Alters the renamed table to reset the id counter.
ALTER TABLE `applications`
  -- Keeps application_id auto-increment and restarts it at 22.
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

-- Alters the renamed table to add the student foreign key.
ALTER TABLE `applications`
  -- Ties each application to a student and deletes rows with that student.
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;
