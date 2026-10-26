-- Comment banner that opens the header of this migration.
-- =============================================================================
-- Names the file that adds the tracks and programs tables.
-- 20260926_add_tracks_and_programs.sql
-- Divider line under the file name.
-- -----------------------------------------------------------------------------
-- Says it adds the first priority tables for track and program data.
-- Adds the first priority database tables for track and program metadata.
-- Notes they support classification, workflow grouping, and reporting.
-- These support student classification, workflow grouping, and future reporting.
-- Closes the header comment banner.
-- =============================================================================

-- Creates the tracks table for thesis, capstone, and seminar tracks.
CREATE TABLE IF NOT EXISTS `tracks` (
  -- track_id is the auto-numbered primary key.
  `track_id` INT(11) NOT NULL AUTO_INCREMENT,
  -- track_code stores the short code, such as thesis.
  `track_code` VARCHAR(30) NOT NULL,
  -- track_name stores the readable track name.
  `track_name` VARCHAR(100) NOT NULL,
  -- description holds optional extra details about the track.
  `description` TEXT DEFAULT NULL,
  -- created_at records when the track row was added.
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  -- Makes track_id the primary key.
  PRIMARY KEY (`track_id`),
  -- Ensures the same track_code cannot be saved twice.
  UNIQUE KEY `uq_tracks_code` (`track_code`)
-- Uses InnoDB and utf8mb4 for keys and special characters.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Creates the programs table for graduate degree programs.
CREATE TABLE IF NOT EXISTS `programs` (
  -- program_id is the auto-numbered primary key.
  `program_id` INT(11) NOT NULL AUTO_INCREMENT,
  -- track_id links each program to one track.
  `track_id` INT(11) NOT NULL,
  -- program_code stores the short code, such as MSCS.
  `program_code` VARCHAR(30) NOT NULL,
  -- program_name stores the full program name.
  `program_name` VARCHAR(150) NOT NULL,
  -- description holds optional extra details about the program.
  `description` TEXT DEFAULT NULL,
  -- is_active marks whether the program is still offered.
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  -- created_at records when the program row was added.
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  -- Makes program_id the primary key.
  PRIMARY KEY (`program_id`),
  -- Ensures the same program_code cannot be saved twice.
  UNIQUE KEY `uq_programs_code` (`program_code`),
  -- Adds an index on track_id for faster lookups.
  KEY `idx_programs_track` (`track_id`),
  -- Starts the foreign key that links programs to tracks.
  CONSTRAINT `fk_programs_track`
    -- Points track_id at the tracks table.
    FOREIGN KEY (`track_id`) REFERENCES `tracks` (`track_id`)
    -- Blocks deleting a track still in use and updates ids on change.
    ON DELETE RESTRICT ON UPDATE CASCADE
-- Uses InnoDB and utf8mb4 for the programs table.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Starts the insert that adds the thesis track.
INSERT INTO `tracks` (`track_code`, `track_name`, `description`)
-- Supplies the thesis code, name, and description.
SELECT 'thesis', 'Thesis', 'Graduate thesis workflow'
-- Only inserts it when no thesis track exists yet.
WHERE NOT EXISTS (SELECT 1 FROM `tracks` WHERE `track_code` = 'thesis');

-- Starts the insert that adds the capstone track.
INSERT INTO `tracks` (`track_code`, `track_name`, `description`)
-- Supplies the capstone code, name, and description.
SELECT 'capstone', 'Capstone', 'Capstone workflow'
-- Only inserts it when no capstone track exists yet.
WHERE NOT EXISTS (SELECT 1 FROM `tracks` WHERE `track_code` = 'capstone');

-- Starts the insert that adds the seminar track.
INSERT INTO `tracks` (`track_code`, `track_name`, `description`)
-- Supplies the seminar code, name, and description.
SELECT 'seminar', 'Seminar Paper', 'Seminar paper workflow'
-- Only inserts it when no seminar track exists yet.
WHERE NOT EXISTS (SELECT 1 FROM `tracks` WHERE `track_code` = 'seminar');

-- Starts inserting the MSCS program row.
INSERT INTO `programs` (`track_id`, `program_code`, `program_name`, `description`)
-- Supplies the MSCS code, full name, and description.
SELECT t.track_id, 'MSCS', 'Master of Science in Computer Science', 'Thesis-based graduate program'
-- Finds the thesis track id to use for this program.
FROM `tracks` t WHERE t.track_code = 'thesis'
-- Only inserts when no MSCS program exists yet.
AND NOT EXISTS (SELECT 1 FROM `programs` WHERE `program_code` = 'MSCS');

-- Starts inserting the MIT program row.
INSERT INTO `programs` (`track_id`, `program_code`, `program_name`, `description`)
-- Supplies the MIT code, full name, and description.
SELECT t.track_id, 'MIT', 'Master in Information Technology', 'Capstone-based graduate program'
-- Finds the capstone track id to use for this program.
FROM `tracks` t WHERE t.track_code = 'capstone'
-- Only inserts when no MIT program exists yet.
AND NOT EXISTS (SELECT 1 FROM `programs` WHERE `program_code` = 'MIT');

-- Starts inserting the MATH program row.
INSERT INTO `programs` (`track_id`, `program_code`, `program_name`, `description`)
-- Supplies the MATH code, full name, and description.
SELECT t.track_id, 'MATH', 'Master of Arts in Mathematics', 'Seminar paper workflow program'
-- Finds the seminar track id to use for this program.
FROM `tracks` t WHERE t.track_code = 'seminar'
-- Only inserts when no MATH program exists yet.
AND NOT EXISTS (SELECT 1 FROM `programs` WHERE `program_code` = 'MATH');
