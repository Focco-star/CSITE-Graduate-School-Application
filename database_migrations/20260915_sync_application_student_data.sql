-- =============================================================================
-- 20260915_sync_application_student_data.sql
-- -----------------------------------------------------------------------------
-- Fixes the data-persistence inconsistency between the application/registration
-- record (users + applications) and the Students table:
--
--   1. Normalizes every existing student name to the single canonical format
--      "First M Last" (no trailing dot) in users.full_name, matching the
--      students.first_name / middle_initial / last_name columns.
--   2. Populates students.student_number for every student that was registered
--      before the field existed (CSITE-<6-digit student_id>).
--   3. Re-installs the AFTER INSERT / AFTER UPDATE triggers that keep
--      users.full_name in lock-step with the Students table forever.
--
-- Idempotent: safe to run on a fresh import and on an already-patched DB.
-- =============================================================================

-- 1) Normalize middle initials (drop stray dots) so the canonical name is stable.
UPDATE `students`
SET `middle_initial` = NULLIF(TRIM(REPLACE(`middle_initial`, '.', '')), '')
WHERE `middle_initial` IS NOT NULL;

-- 2) Assign deterministic student numbers to records that predate the column.
UPDATE `students` s
SET `student_number` = CONCAT('CSITE-', LPAD(s.`student_id`, 6, '0'))
WHERE s.`student_number` IS NULL OR s.`student_number` = '';

-- 3) Sync users.full_name to the canonical Students-table identity.
UPDATE `users` u
INNER JOIN `students` s ON s.`user_id` = u.`user_id`
SET u.`full_name` = TRIM(CONCAT_WS(' ', s.`first_name`, NULLIF(TRIM(REPLACE(s.`middle_initial`, '.', '')), ''), s.`last_name`))
WHERE u.`role` = 'student';

-- 4) Triggers that keep the account name and the student profile synchronized
--    for all future writes.
DROP TRIGGER IF EXISTS `students_after_insert_sync_user`;
CREATE TRIGGER `students_after_insert_sync_user`
AFTER INSERT ON `students`
FOR EACH ROW
UPDATE `users`
SET `full_name` = TRIM(CONCAT_WS(' ', NEW.`first_name`, NULLIF(TRIM(REPLACE(NEW.`middle_initial`, '.', '')), ''), NEW.`last_name`))
WHERE `user_id` = NEW.`user_id`;

DROP TRIGGER IF EXISTS `students_after_update_sync_user`;
CREATE TRIGGER `students_after_update_sync_user`
AFTER UPDATE ON `students`
FOR EACH ROW
UPDATE `users`
SET `full_name` = TRIM(CONCAT_WS(' ', NEW.`first_name`, NULLIF(TRIM(REPLACE(NEW.`middle_initial`, '.', '')), ''), NEW.`last_name`))
WHERE `user_id` = NEW.`user_id`;
