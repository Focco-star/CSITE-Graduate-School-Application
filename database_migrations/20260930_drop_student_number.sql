-- ============================================================================
-- Migration: Drop redundant students.student_number
-- Date: 2026-09-30
--
-- students.student_id already IS the official email-derived ID
-- (e.g. co259344@adzu.edu.ph -> 259344), so student_number duplicated it.
-- ============================================================================

DROP VIEW IF EXISTS `v_students_alpha`;

-- Trigger without the student_number fill (keeps email->student_id + MI cleanup).
DROP TRIGGER IF EXISTS `students_before_insert_autoid`;
DELIMITER $$
CREATE TRIGGER `students_before_insert_autoid` BEFORE INSERT ON `students` FOR EACH ROW
BEGIN
  DECLARE v_email VARCHAR(100) DEFAULT '';
  DECLARE v_digits VARCHAR(20) DEFAULT '';
  DECLARE v_taken INT DEFAULT 0;
  SELECT `email` INTO v_email FROM `users` WHERE `user_id` = NEW.`user_id` LIMIT 1;
  IF (v_email IS NOT NULL AND v_email <> '') THEN
    SET v_digits = REGEXP_REPLACE(SUBSTRING_INDEX(v_email, '@', 1), '[^0-9]', '');
  END IF;
  IF (v_digits IS NOT NULL AND v_digits <> '') THEN
    -- Official student ID = numeric part of the ADZU email (e.g. co259344 -> 259344).
    SELECT COUNT(*) INTO v_taken FROM `students` WHERE `student_id` = CAST(v_digits AS UNSIGNED);
    IF (v_taken = 0) THEN
      SET NEW.`student_id` = CAST(v_digits AS UNSIGNED);
    END IF;
  END IF;
  IF (NEW.`middle_initial` IS NOT NULL) THEN
    SET NEW.`middle_initial` = NULLIF(TRIM(REPLACE(NEW.`middle_initial`, '.', '')), '');
  END IF;
END$$
DELIMITER ;

-- Dropping the column also drops its UNIQUE key.
ALTER TABLE `students` DROP COLUMN `student_number`;

CREATE OR REPLACE VIEW `v_students_alpha` AS
SELECT `student_id`,
       `user_id`,
       `first_name`,
       `last_name`,
       `middle_initial`,
       TRIM(CONCAT(`last_name`, ', ', `first_name`,
            IF(`middle_initial` IS NULL OR `middle_initial` = '',
               '', CONCAT(' ', `middle_initial`)))) AS `display_name`,
       `age`,
       `gender`,
       `program`,
       `track`,
       `adviser_name`,
       `enrollment_date`,
       `archived_at`
FROM `students`
ORDER BY `last_name` ASC, `first_name` ASC
LIMIT 18446744073709551615;
