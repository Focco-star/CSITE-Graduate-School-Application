-- Names the migration and date: student id comes from the typed ADZU id.
-- Migration: student_id comes from the typed ADZU ID number (2026-10-12)
-- Divider line separating the title from the explanation.
--
-- Explains that the old trigger overwrote every new student_id.
-- The BEFORE INSERT trigger used to overwrite EVERY new student_id with the
-- Says it replaced the id with digits from the ADZU email.
-- numeric part of the ADZU email. It now only derives from the email when the
-- Says it now only derives an id when the row arrives without one.
-- row arrives without an explicit ID (0/NULL), so the ID number the student
-- Notes a typed zero or null id is treated as missing.
-- types at registration (or edits in their profile) is what lands in
-- Says the id typed at registration is what gets saved.
-- students.student_id.
-- Notes profile edits also keep that typed id.
--
-- Warns that InfinityFree free MySQL has no trigger privilege.
-- NOTE: InfinityFree free MySQL has no TRIGGER privilege, so do NOT run this
-- Says triggers are stripped from the InfinityFree import file.
-- there (triggers are stripped from the InfinityFree import file). Run only on
-- Says it should only run on local XAMPP or Cloud SQL.
-- local XAMPP / Cloud SQL where the trigger exists.

-- Drops the old auto-id trigger before recreating it.
DROP TRIGGER IF EXISTS `students_before_insert_autoid`;

-- Switches the delimiter so the trigger body can use semicolons.
DELIMITER $$

-- Creates the auto-id trigger on students before insert.
CREATE TRIGGER `students_before_insert_autoid` BEFORE INSERT ON `students` FOR EACH ROW BEGIN
  -- Declares a variable to hold the account email.
  DECLARE v_email VARCHAR(100) DEFAULT '';
  -- Declares a variable to hold the digits found in the email.
  DECLARE v_digits VARCHAR(20) DEFAULT '';
  -- Declares a counter to check whether an id is already taken.
  DECLARE v_taken INT DEFAULT 0;
  -- Only derives an id when the row has no explicit id.
  IF (NEW.`student_id` IS NULL OR NEW.`student_id` = 0) THEN
  -- Reads the email of the user that owns the new student row.
  SELECT `email` INTO v_email FROM `users` WHERE `user_id` = NEW.`user_id` LIMIT 1;
  -- Only runs the extraction when an email was found.
  IF (v_email IS NOT NULL AND v_email <> '') THEN
    -- Extracts digits from the part of the email before the at sign.
    SET v_digits = REGEXP_REPLACE(SUBSTRING_INDEX(v_email, '@', 1), '[^0-9]', '');
  -- Ends the email check block.
  END IF;
  -- Only continues when some digits were actually found.
  IF (v_digits IS NOT NULL AND v_digits <> '') THEN
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
  -- Ends the block that only runs for missing ids.
  END IF;
  -- Only cleans the middle initial when it is not null.
  IF (NEW.`middle_initial` IS NOT NULL) THEN
    -- Strips dots from the middle initial and turns blanks into null.
    SET NEW.`middle_initial` = NULLIF(TRIM(REPLACE(NEW.`middle_initial`, '.', '')), '');
  -- Ends the middle initial block.
  END IF;
-- Ends the trigger body.
END
-- Closes the trigger body with the custom delimiter.
$$

-- Restores the normal semicolon delimiter.
DELIMITER ;
