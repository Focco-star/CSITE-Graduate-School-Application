-- ============================================================================
-- Migration: Standardize names, consolidate roles, unify keys, automate ID
-- Date: 2026-09-27
--
-- 1) Standardize Name Formats: [Last Name], [First Name] [Middle Initial]
--    + alphabetical retrieval (ORDER BY applied in queries / indexes below).
-- 2) Consolidate Roles: drop dedicated `coordinators` table (role lives in
--    `users.role = 'coordinator'`).
-- 3) Unify Key Relations: applications.student_id -> applications.user_id,
--    application_documents.student_id -> application_documents.user_id,
--    both FK -> users(user_id).
-- 4) Automate Student ID Extraction: backfill students.student_number from the
--    numeric characters of the ADZU email local part (e.g. co240255@... -> 240255)
--    and add triggers to do it automatically on account creation.
-- ============================================================================

-- --------------------------------------------------------------------------
-- 1) STANDARDIZE NAME FORMATS
-- --------------------------------------------------------------------------

-- Normalize stored middle initials ('.' / blank -> NULL-safe empty).
UPDATE `students`
SET `middle_initial` = NULLIF(TRIM(REPLACE(`middle_initial`, '.', '')), '')
WHERE `middle_initial` IS NOT NULL;

-- Backfill every student user display name to "Last, First MI".
UPDATE `users` u
INNER JOIN `students` s ON s.`user_id` = u.`user_id`
SET u.`full_name` = TRIM(CONCAT(
  s.`last_name`, ', ', s.`first_name`,
  IF(s.`middle_initial` IS NULL OR s.`middle_initial` = '',
     '', CONCAT(' ', s.`middle_initial`))
))
WHERE u.`role` = 'student';

-- Standardize the single coordinator account the same way ("Opinion, Precious").
UPDATE `users`
SET `full_name` = 'Opinion, Precious'
WHERE `email` = 'gpc-csite@adzu.edu.ph' AND `role` = 'coordinator';

-- Replace sync triggers so every future write keeps the canonical format.
DROP TRIGGER IF EXISTS `students_after_insert_sync_user`;
DROP TRIGGER IF EXISTS `students_after_update_sync_user`;

DELIMITER $$
CREATE TRIGGER `students_after_insert_sync_user` AFTER INSERT ON `students` FOR EACH ROW
BEGIN
  UPDATE `users`
  SET `full_name` = TRIM(CONCAT(
    NEW.`last_name`, ', ', NEW.`first_name`,
    IF(NEW.`middle_initial` IS NULL OR TRIM(REPLACE(NEW.`middle_initial`, '.', '')) = '',
       '', CONCAT(' ', TRIM(REPLACE(NEW.`middle_initial`, '.', ''))))
  ))
  WHERE `user_id` = NEW.`user_id`;
END$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `students_after_update_sync_user` AFTER UPDATE ON `students` FOR EACH ROW
BEGIN
  UPDATE `users`
  SET `full_name` = TRIM(CONCAT(
    NEW.`last_name`, ', ', NEW.`first_name`,
    IF(NEW.`middle_initial` IS NULL OR TRIM(REPLACE(NEW.`middle_initial`, '.', '')) = '',
       '', CONCAT(' ', TRIM(REPLACE(NEW.`middle_initial`, '.', ''))))
  ))
  WHERE `user_id` = NEW.`user_id`;
END$$
DELIMITER ;

-- Index to support alphabetical retrieval (Last, First).
CREATE INDEX IF NOT EXISTS `idx_students_alpha` ON `students` (`last_name`, `first_name`);
CREATE INDEX IF NOT EXISTS `idx_users_full_name` ON `users` (`full_name`);

-- --------------------------------------------------------------------------
-- 4) AUTOMATE STUDENT ID EXTRACTION (backfill existing rows first)
-- --------------------------------------------------------------------------
-- Official student ID = numeric characters of the email local part.
-- e.g. co240255@adzu.edu.ph -> 240255. Only overwrites NULL/empty/CSITE-* values
-- so manually assigned IDs are never clobbered.

UPDATE `students` s
INNER JOIN `users` u ON u.`user_id` = s.`user_id`
SET s.`student_number` = REGEXP_REPLACE(SUBSTRING_INDEX(u.`email`, '@', 1), '[^0-9]', '')
WHERE (s.`student_number` IS NULL OR s.`student_number` = '' OR s.`student_number` LIKE 'CSITE-%')
  AND REGEXP_REPLACE(SUBSTRING_INDEX(u.`email`, '@', 1), '[^0-9]', '') <> '';

-- Auto-generate the official student ID + number on account creation when the
-- caller leaves them blank. Official ID = numeric part of the ADZU email
-- (e.g. co259344@adzu.edu.ph -> student_id 259344).
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
    SELECT COUNT(*) INTO v_taken FROM `students` WHERE `student_id` = CAST(v_digits AS UNSIGNED);
    IF (v_taken = 0) THEN
      SET NEW.`student_id` = CAST(v_digits AS UNSIGNED);
    END IF;
    IF (NEW.`student_number` IS NULL OR NEW.`student_number` = '') THEN
      SELECT COUNT(*) INTO v_taken FROM `students` WHERE `student_number` = v_digits;
      IF (v_taken = 0) THEN
        SET NEW.`student_number` = v_digits;
      END IF;
    END IF;
  END IF;
  -- Keep middle initial clean (".." / "." -> empty).
  IF (NEW.`middle_initial` IS NOT NULL) THEN
    SET NEW.`middle_initial` = NULLIF(TRIM(REPLACE(NEW.`middle_initial`, '.', '')), '');
  END IF;
END$$
DELIMITER ;

-- --------------------------------------------------------------------------
-- 2) CONSOLIDATE ROLES: remove dedicated coordinator table
-- --------------------------------------------------------------------------
-- Coordinators are already rows in `users` with role='coordinator', so the
-- side table is redundant. Data is preserved implicitly via users.
DROP TABLE IF EXISTS `coordinators`;

-- --------------------------------------------------------------------------
-- 3) UNIFY KEY RELATIONS: student_id -> user_id on applications + documents
-- --------------------------------------------------------------------------

-- ---- applications ---------------------------------------------------------
ALTER TABLE `applications` ADD COLUMN IF NOT EXISTS `user_id` INT(11) NULL AFTER `application_id`;

-- Backfill user_id from the linked student row (only path to users).
UPDATE `applications` a
INNER JOIN `students` s ON s.`student_id` = a.`student_id`
SET a.`user_id` = s.`user_id`
WHERE a.`user_id` IS NULL;

-- Orphan guard: any application whose student row is gone keeps NULL and is
-- removed so the new NOT NULL + FK can be applied cleanly.
DELETE FROM `applications` WHERE `user_id` IS NULL;

ALTER TABLE `applications` ADD INDEX IF NOT EXISTS `idx_applications_user_id` (`user_id`);

-- Drop old FK + index when they exist (statements are idempotent on a fresh
-- import where the old schema is present; ignore errors on re-run).
-- NOTE: MySQL/MariaDB has no IF EXISTS for DROP FOREIGN KEY, so these two
-- lines may warn on re-run — safe to ignore.
ALTER TABLE `applications` DROP FOREIGN KEY `applications_ibfk_1`;
ALTER TABLE `applications` DROP INDEX `student_id`;
ALTER TABLE `applications` DROP COLUMN `student_id`;
ALTER TABLE `applications` MODIFY `user_id` INT(11) NOT NULL;
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

-- ---- application_documents ------------------------------------------------
ALTER TABLE `application_documents` ADD COLUMN IF NOT EXISTS `user_id` INT(11) NULL AFTER `application_id`;

UPDATE `application_documents` d
INNER JOIN `students` s ON s.`student_id` = d.`student_id`
SET d.`user_id` = s.`user_id`
WHERE d.`user_id` IS NULL;

-- Documents with no resolvable user cannot satisfy the new FK; drop them
-- rather than leaving them orphaned.
DELETE FROM `application_documents` WHERE `user_id` IS NULL;

ALTER TABLE `application_documents` ADD INDEX IF NOT EXISTS `idx_application_documents_user_id` (`user_id`);

ALTER TABLE `application_documents` DROP FOREIGN KEY `application_documents_student_fk`;
ALTER TABLE `application_documents` DROP INDEX `application_documents_student_id`;
-- Keep application_id FK untouched.
ALTER TABLE `application_documents` DROP COLUMN `student_id`;
ALTER TABLE `application_documents` MODIFY `user_id` INT(11) NOT NULL;
ALTER TABLE `application_documents`
  ADD CONSTRAINT `application_documents_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

-- --------------------------------------------------------------------------
-- 5) STUDENT_ID = EMAIL DIGITS for pre-existing rows
-- --------------------------------------------------------------------------
-- Runs AFTER section 3 on purpose: the applications backfill above needed the
-- old student_ids to resolve user_id. Nothing still references
-- students.student_id (applications/documents now point at users.user_id),
-- so the PK values can be replaced with the official email-derived IDs.
-- Rows whose digits are already taken, or whose email has no digits, keep
-- their current student_id.

UPDATE `students` s
INNER JOIN `users` u ON u.`user_id` = s.`user_id`
LEFT JOIN `students` s2
  ON s2.`student_id` = CAST(REGEXP_REPLACE(SUBSTRING_INDEX(u.`email`, '@', 1), '[^0-9]', '') AS UNSIGNED)
  AND s2.`student_id` <> s.`student_id`
SET s.`student_id` = CAST(REGEXP_REPLACE(SUBSTRING_INDEX(u.`email`, '@', 1), '[^0-9]', '') AS UNSIGNED)
WHERE REGEXP_REPLACE(SUBSTRING_INDEX(u.`email`, '@', 1), '[^0-9]', '') <> ''
  AND s2.`student_id` IS NULL;
