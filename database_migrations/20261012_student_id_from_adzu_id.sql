-- Migration: student_id comes from the typed ADZU ID number (2026-10-12)
--
-- The BEFORE INSERT trigger used to overwrite EVERY new student_id with the
-- numeric part of the ADZU email. It now only derives from the email when the
-- row arrives without an explicit ID (0/NULL), so the ID number the student
-- types at registration (or edits in their profile) is what lands in
-- students.student_id.
--
-- NOTE: InfinityFree free MySQL has no TRIGGER privilege, so do NOT run this
-- there (triggers are stripped from the InfinityFree import file). Run only on
-- local XAMPP / Cloud SQL where the trigger exists.

DROP TRIGGER IF EXISTS `students_before_insert_autoid`;

DELIMITER $$

CREATE TRIGGER `students_before_insert_autoid` BEFORE INSERT ON `students` FOR EACH ROW BEGIN
  DECLARE v_email VARCHAR(100) DEFAULT '';
  DECLARE v_digits VARCHAR(20) DEFAULT '';
  DECLARE v_taken INT DEFAULT 0;
  IF (NEW.`student_id` IS NULL OR NEW.`student_id` = 0) THEN
  SELECT `email` INTO v_email FROM `users` WHERE `user_id` = NEW.`user_id` LIMIT 1;
  IF (v_email IS NOT NULL AND v_email <> '') THEN
    SET v_digits = REGEXP_REPLACE(SUBSTRING_INDEX(v_email, '@', 1), '[^0-9]', '');
  END IF;
  IF (v_digits IS NOT NULL AND v_digits <> '') THEN
    SELECT COUNT(*) INTO v_taken FROM `students` WHERE `student_id` = CAST(v_digits AS UNSIGNED);
    IF (v_taken = 0) THEN
      SET NEW.`student_id` = CAST(v_digits AS UNSIGNED);
    END IF;
  END IF;
  END IF;
  IF (NEW.`middle_initial` IS NOT NULL) THEN
    SET NEW.`middle_initial` = NULLIF(TRIM(REPLACE(NEW.`middle_initial`, '.', '')), '');
  END IF;
END
$$

DELIMITER ;
