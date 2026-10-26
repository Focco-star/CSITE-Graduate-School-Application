-- Names the migration and date for the application draft table.
-- Migration: application draft for the student Application form (2026-10-21)
-- Divider line separating the title from the explanation.
--
-- Describes a student typing title, adviser, abstract, and date.
-- A student who types the research title, adviser, abstract and enrolment date
-- Says moving to another menu used to lose all that input.
-- and then moves to another menu of the Student Portal used to lose everything.
-- Explains that a draft row now keeps those entered values.
-- The form is now backed by a draft row so the entered values survive a
-- Says values survive a refresh, logout, and menu changes.
-- refresh, a logout/login and a trip through the Dashboard or any other menu.
-- Ends the explanation paragraph.
--
-- Notes there is only one draft per student.
-- One draft per student (UNIQUE on user_id): the draft holds the fields the
-- Says the draft holds the typed fields and picked file name.
-- student typed plus the document type and the name of the file they picked,
-- So the form can be restored exactly as the student left it.
-- so the form can be restored exactly as they left it.
-- Ends the draft description.
--
-- Notes the draft is cleared after the application is saved.
-- The draft is cleared once the application has been saved successfully, or
-- Or when the student explicitly starts a new application.
-- when the student explicitly starts a new application.

-- Creates the application_drafts table if it does not exist.
CREATE TABLE IF NOT EXISTS `application_drafts` (
  -- draft_id is the auto-numbered primary key.
  `draft_id`    INT(11)      NOT NULL AUTO_INCREMENT,
  -- user_id links the draft to a student account.
  `user_id`     INT(11)      NOT NULL,
  -- title stores the research title being typed.
  `title`       TEXT         DEFAULT NULL,
  -- adviser stores the adviser name being typed.
  `adviser`     VARCHAR(150) DEFAULT NULL,
  -- abstract stores the abstract text being typed.
  `abstract`    TEXT         DEFAULT NULL,
  -- enroll_date stores the typed enrolment date text.
  `enroll_date` VARCHAR(20)  DEFAULT NULL,
  -- stage_key stores the chosen workflow stage key.
  `stage_key`   VARCHAR(50)  DEFAULT NULL,
  -- doc_type stores the document type the student picked.
  `doc_type`    VARCHAR(150) DEFAULT NULL,
  -- file_name stores the name of the file the student picked.
  `file_name`   VARCHAR(255) DEFAULT NULL,
  -- updated_at refreshes automatically whenever the draft changes.
  `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  -- Makes draft_id the primary key.
  PRIMARY KEY (`draft_id`),
  -- Allows only one draft per student.
  UNIQUE KEY `application_drafts_user` (`user_id`),
  -- Starts the foreign key that links the draft to a user.
  CONSTRAINT `application_drafts_user_fk` FOREIGN KEY (`user_id`)
    -- Deletes the draft when its user account is deleted.
    REFERENCES `users` (`user_id`) ON DELETE CASCADE
-- Uses InnoDB and utf8mb4 for the table.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
