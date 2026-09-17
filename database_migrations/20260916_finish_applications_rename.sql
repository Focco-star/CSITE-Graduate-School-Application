-- Finish the user-approved applications table repair (strategy: create in place).
-- The orphaned `applications` inode/dictionary entry is gone, so CREATE TABLE
-- under the real name now succeeds. Create it with the full workflow schema,
-- copy the seeded rows across from applications_rebuild, then retire the
-- temporary rebuild table.
DROP TABLE IF EXISTS `applications`;

CREATE TABLE `applications` (
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

INSERT INTO `applications`
  (`application_id`, `student_id`, `presentation_stage`, `paper_title`, `status`, `coordinator_comment`, `grad_school_endorsed`, `payment_recorded`, `receipt_number`, `payment_date`, `payment_amount`, `ready_for_presentation`, `workflow_state`, `result`, `submitted_at`, `updated_at`)
SELECT `application_id`, `student_id`, `presentation_stage`, `paper_title`, `status`, `coordinator_comment`, `grad_school_endorsed`, `payment_recorded`, `receipt_number`, `payment_date`, `payment_amount`, `ready_for_presentation`, `workflow_state`, `result`, `submitted_at`, `updated_at`
FROM `applications_rebuild`;

DROP TABLE IF EXISTS `applications_rebuild`;
DROP TABLE IF EXISTS `applications_probe2`;

ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;
