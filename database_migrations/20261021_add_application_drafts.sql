-- Migration: application draft for the student Application form (2026-10-21)
--
-- A student who types the research title, adviser, abstract and enrolment date
-- and then moves to another menu of the Student Portal used to lose everything.
-- The form is now backed by a draft row so the entered values survive a
-- refresh, a logout/login and a trip through the Dashboard or any other menu.
--
-- One draft per student (UNIQUE on user_id): the draft holds the fields the
-- student typed plus the document type and the name of the file they picked,
-- so the form can be restored exactly as they left it.
--
-- The draft is cleared once the application has been saved successfully, or
-- when the student explicitly starts a new application.

CREATE TABLE IF NOT EXISTS `application_drafts` (
  `draft_id`    INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`     INT(11)      NOT NULL,
  `title`       TEXT         DEFAULT NULL,
  `adviser`     VARCHAR(150) DEFAULT NULL,
  `abstract`    TEXT         DEFAULT NULL,
  `enroll_date` VARCHAR(20)  DEFAULT NULL,
  `stage_key`   VARCHAR(50)  DEFAULT NULL,
  `doc_type`    VARCHAR(150) DEFAULT NULL,
  `file_name`   VARCHAR(255) DEFAULT NULL,
  `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`draft_id`),
  UNIQUE KEY `application_drafts_user` (`user_id`),
  CONSTRAINT `application_drafts_user_fk` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
