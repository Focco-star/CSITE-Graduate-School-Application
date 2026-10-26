-- Comment banner that opens the header of this migration.
-- ============================================================================
-- Names the migration that drops the redundant student number column.
-- Migration: Drop redundant students.student_number
-- Shows the date of this migration.
-- Date: 2026-09-30
-- Line break between the title and the explanation.
--
-- Notes the student_id already is the official email-derived id.
-- students.student_id already IS the official email-derived ID
-- Gives the email example that becomes id 259344.
-- (e.g. co259344@adzu.edu.ph -> 259344), so student_number duplicated it.
-- Closes the header comment banner.
-- ============================================================================

-- Drops the old alphabetical students view before the change.
DROP VIEW IF EXISTS `v_students_alpha`;

-- Notes the new trigger drops the student number fill.
-- Trigger without the student_number fill (keeps email->student_id + MI cleanup).
-- Drops the old auto-id trigger before recreating it.
DROP TRIGGER IF EXISTS `students_before_insert_autoid`;
-- Switches the delimiter so the trigger body can use semicolons.
DELIMITER $$
-- Creates the auto-id trigger on students before insert.
CREATE TRIGGER `students_before_insert_autoid` BEFORE INSERT ON `students` FOR EACH ROW
-- Opens the trigger body block.
BEGIN
  -- Declares a variable to hold the account email.
  DECLARE v_email VARCHAR(100) DEFAULT '';
  -- Declares a variable to hold the digits found in the email.
  DECLARE v_digits VARCHAR(20) DEFAULT '';
  -- Declares a counter to check whether an id is already taken.
  DECLARE v_taken INT DEFAULT 0;
  -- Reads the email of the user that owns the new student row.
  SELECT `email` INTO v_email FROM `users` WHERE `user_id` = NEW.`user_id` LIMIT 1;
  -- Only runs the extraction when an email was found.
  IF (v_email IS NOT NULL AND v_email <> '') THEN
    -- Extracts digits from the part of the email before the at sign.
    SET v_digits = REGEXP_REPLACE(SUBSTRING_INDEX(v_email, '@', 1), '[^0-9]', '');
  -- Ends the email check block.
  END IF;
  -- Only continues when some digits were found.
  IF (v_digits IS NOT NULL AND v_digits <> '') THEN
    -- Notes the official id is the numeric part of the email.
    -- Official student ID = numeric part of the ADZU email (e.g. co259344 -> 259344).
    -- Counts students already using those digits as their id.
    SELECT COUNT(*) INTO v_taken FROM `students` WHERE `student_id` = CAST(v_digits AS UNSIGNED);
    -- Checks that no other student already has this id.
    IF (v_taken = 0) THEN
      -- Assigns the email digits as the new student_id.
      SET NEW.`student_id` = CAST(v_digits AS UNSIGNED);
    -- Ends the id availability check.
    END IF;
  -- Ends the digits check block.
  END IF;
  -- Only cleans the middle initial when it is not null.
  IF (NEW.`middle_initial` IS NOT NULL) THEN
    -- Strips dots from the middle initial and turns blanks into null.
    SET NEW.`middle_initial` = NULLIF(TRIM(REPLACE(NEW.`middle_initial`, '.', '')), '');
  -- Ends the middle initial block.
  END IF;
-- Ends the trigger body.
END$$
-- Restores the normal semicolon delimiter.
DELIMITER ;

-- Notes that dropping the column also drops its unique key.
-- Dropping the column also drops its UNIQUE key.
-- Drops the student_number column from students.
ALTER TABLE `students` DROP COLUMN `student_number`;

-- Creates or replaces the alphabetical students view again.
CREATE OR REPLACE VIEW `v_students_alpha` AS
-- Selects the student id column.
SELECT `student_id`,
       -- Selects the linked user id column.
       `user_id`,
       -- Selects the first name column.
       `first_name`,
       -- Selects the last name column.
       `last_name`,
       -- Selects the middle initial column.
       `middle_initial`,
       -- Starts building the display name as last name, first name.
       TRIM(CONCAT(`last_name`, ', ', `first_name`,
            -- Checks whether the middle initial is missing or empty.
            IF(`middle_initial` IS NULL OR `middle_initial` = '',
               -- Adds nothing when empty, else a space plus the initial, and names it display_name.
               '', CONCAT(' ', `middle_initial`)))) AS `display_name`,
       -- Selects the age column.
       `age`,
       -- Selects the gender column.
       `gender`,
       -- Selects the program column.
       `program`,
       -- Selects the track column.
       `track`,
       -- Selects the adviser name column.
       `adviser_name`,
       -- Selects the enrollment date column.
       `enrollment_date`,
       -- Selects the archive timestamp column.
       `archived_at`
-- Reads rows from the students table.
FROM `students`
-- Sorts rows by last name and then first name.
ORDER BY `last_name` ASC, `first_name` ASC
-- Huge limit that keeps the view ORDER BY working.
LIMIT 18446744073709551615;
