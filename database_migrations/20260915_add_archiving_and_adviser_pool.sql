-- Notes that this migration is for existing csite_grad_school databases.
-- Apply this migration to existing csite_grad_school databases.
-- Adds archived_at to students to mark when a profile was archived.
ALTER TABLE `students` ADD COLUMN `archived_at` TIMESTAMP NULL DEFAULT NULL AFTER `enrollment_date`;

-- Creates the advisor_pool table that lists available advisers.
CREATE TABLE IF NOT EXISTS `advisor_pool` (
  -- adviser_id is the auto-numbered primary key for each adviser.
  `adviser_id` INT(11) NOT NULL AUTO_INCREMENT,
  -- name stores the adviser full name.
  `name` VARCHAR(150) NOT NULL,
  -- qualification describes the adviser academic qualification.
  `qualification` VARCHAR(150) NOT NULL,
  -- email stores an optional contact email address.
  `email` VARCHAR(100) DEFAULT NULL,
  -- availability marks whether the adviser can still take students.
  `availability` ENUM('available','unavailable') NOT NULL DEFAULT 'available',
  -- notes holds extra free-text remarks about the adviser.
  `notes` TEXT DEFAULT NULL,
  -- created_at records when the adviser row was added.
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  -- Makes adviser_id the primary key so each adviser row is unique.
  PRIMARY KEY (`adviser_id`)
-- Uses InnoDB and utf8mb4 for reliable storage of names.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
