-- =============================================================================
-- 20260926_add_tracks_and_programs.sql
-- -----------------------------------------------------------------------------
-- Adds the first priority database tables for track and program metadata.
-- These support student classification, workflow grouping, and future reporting.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `tracks` (
  `track_id` INT(11) NOT NULL AUTO_INCREMENT,
  `track_code` VARCHAR(30) NOT NULL,
  `track_name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`track_id`),
  UNIQUE KEY `uq_tracks_code` (`track_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `programs` (
  `program_id` INT(11) NOT NULL AUTO_INCREMENT,
  `track_id` INT(11) NOT NULL,
  `program_code` VARCHAR(30) NOT NULL,
  `program_name` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`program_id`),
  UNIQUE KEY `uq_programs_code` (`program_code`),
  KEY `idx_programs_track` (`track_id`),
  CONSTRAINT `fk_programs_track`
    FOREIGN KEY (`track_id`) REFERENCES `tracks` (`track_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tracks` (`track_code`, `track_name`, `description`)
SELECT 'thesis', 'Thesis', 'Graduate thesis workflow'
WHERE NOT EXISTS (SELECT 1 FROM `tracks` WHERE `track_code` = 'thesis');

INSERT INTO `tracks` (`track_code`, `track_name`, `description`)
SELECT 'capstone', 'Capstone', 'Capstone workflow'
WHERE NOT EXISTS (SELECT 1 FROM `tracks` WHERE `track_code` = 'capstone');

INSERT INTO `tracks` (`track_code`, `track_name`, `description`)
SELECT 'seminar', 'Seminar Paper', 'Seminar paper workflow'
WHERE NOT EXISTS (SELECT 1 FROM `tracks` WHERE `track_code` = 'seminar');

INSERT INTO `programs` (`track_id`, `program_code`, `program_name`, `description`)
SELECT t.track_id, 'MSCS', 'Master of Science in Computer Science', 'Thesis-based graduate program'
FROM `tracks` t WHERE t.track_code = 'thesis'
AND NOT EXISTS (SELECT 1 FROM `programs` WHERE `program_code` = 'MSCS');

INSERT INTO `programs` (`track_id`, `program_code`, `program_name`, `description`)
SELECT t.track_id, 'MIT', 'Master in Information Technology', 'Capstone-based graduate program'
FROM `tracks` t WHERE t.track_code = 'capstone'
AND NOT EXISTS (SELECT 1 FROM `programs` WHERE `program_code` = 'MIT');

INSERT INTO `programs` (`track_id`, `program_code`, `program_name`, `description`)
SELECT t.track_id, 'MATH', 'Master of Arts in Mathematics', 'Seminar paper workflow program'
FROM `tracks` t WHERE t.track_code = 'seminar'
AND NOT EXISTS (SELECT 1 FROM `programs` WHERE `program_code` = 'MATH');
