-- Notes that this finishes the approved repair of the applications table.
-- Finish the user-approved applications table repair (strategy: create in place).
-- Says the orphaned applications table entry is already gone.
-- The orphaned `applications` inode/dictionary entry is gone, so CREATE TABLE
-- Notes that creating the table under the real name now works.
-- under the real name now succeeds. Create it with the full workflow schema,
-- Says the table is created with the full workflow schema.
-- copy the seeded rows across from applications_rebuild, then retire the
-- Notes rows are copied from the temporary rebuild table.
-- temporary rebuild table.
-- Drops any leftover applications table before creating it.
DROP TABLE IF EXISTS `applications`;

-- Creates the real applications table with the full workflow schema.
CREATE TABLE `applications` (
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

-- Starts the insert that copies saved rows into applications.
INSERT INTO `applications`
  -- Lists the destination columns, matching the rebuild table order.
  (`application_id`, `student_id`, `presentation_stage`, `paper_title`, `status`, `coordinator_comment`, `grad_school_endorsed`, `payment_recorded`, `receipt_number`, `payment_date`, `payment_amount`, `ready_for_presentation`, `workflow_state`, `result`, `submitted_at`, `updated_at`)
-- Selects the same columns from the temporary rebuild table.
SELECT `application_id`, `student_id`, `presentation_stage`, `paper_title`, `status`, `coordinator_comment`, `grad_school_endorsed`, `payment_recorded`, `receipt_number`, `payment_date`, `payment_amount`, `ready_for_presentation`, `workflow_state`, `result`, `submitted_at`, `updated_at`
-- Reads the saved rows from applications_rebuild.
FROM `applications_rebuild`;

-- Drops the temporary rebuild table now that rows are copied.
DROP TABLE IF EXISTS `applications_rebuild`;
-- Drops the leftover probe table from earlier repair attempts.
DROP TABLE IF EXISTS `applications_probe2`;

-- Alters applications to attach the student foreign key.
ALTER TABLE `applications`
  -- Ties each application to a student and deletes rows with that student.
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;
