-- Comment banner that opens the header of this data-sync migration.
-- =============================================================================
-- Names the migration file that syncs application and student data.
-- 20260915_sync_application_student_data.sql
-- Divider line under the migration file name.
-- -----------------------------------------------------------------------------
-- Explains that this script fixes inconsistent student data.
-- Fixes the data-persistence inconsistency between the application/registration
-- Notes which records are compared: users and applications versus students.
-- record (users + applications) and the Students table:
-- Separates the summary from the numbered list of fixes.
--
-- First fix: rewrites student names into one standard format.
--   1. Normalizes every existing student name to the single canonical format
-- Shows the standard format: first name, middle initial, last name, no dot.
--      "First M Last" (no trailing dot) in users.full_name, matching the
-- Says users.full_name must match the students name columns.
--      students.first_name / middle_initial / last_name columns.
-- Second fix: fills students.student_number for older records.
--   2. Populates students.student_number for every student that was registered
-- Shows the generated number format CSITE- plus the six-digit student id.
--      before the field existed (CSITE-<6-digit student_id>).
-- Third fix: recreates triggers that keep account names in sync.
--   3. Re-installs the AFTER INSERT / AFTER UPDATE triggers that keep
-- Says the triggers keep users.full_name matching the students table.
--      users.full_name in lock-step with the Students table forever.
-- Ends the numbered list of fixes.
--
-- Notes the script is idempotent, so running it again is safe.
-- Idempotent: safe to run on a fresh import and on an already-patched DB.
-- Closes the header comment banner.
-- =============================================================================

-- Starts fix 1: removes stray dots from middle initials.
-- 1) Normalize middle initials (drop stray dots) so the canonical name is stable.
-- Updates the students table.
UPDATE `students`
-- Strips dots from middle_initial and turns empty text into null.
SET `middle_initial` = NULLIF(TRIM(REPLACE(`middle_initial`, '.', '')), '')
-- Only touches rows that actually have a middle initial.
WHERE `middle_initial` IS NOT NULL;

-- Starts fix 2: gives old records a student number.
-- 2) Assign deterministic student numbers to records that predate the column.
-- Updates students and aliases the table as s.
UPDATE `students` s
-- Builds CSITE- plus the student_id padded to six digits.
SET `student_number` = CONCAT('CSITE-', LPAD(s.`student_id`, 6, '0'))
-- Only fills rows whose student_number is empty or null.
WHERE s.`student_number` IS NULL OR s.`student_number` = '';

-- Starts fix 3: copies the canonical name into users.full_name.
-- 3) Sync users.full_name to the canonical Students-table identity.
-- Updates the users table and aliases it as u.
UPDATE `users` u
-- Joins each user to the student row that points to it.
INNER JOIN `students` s ON s.`user_id` = u.`user_id`
-- Sets full_name from first name, optional middle initial, and last name.
SET u.`full_name` = TRIM(CONCAT_WS(' ', s.`first_name`, NULLIF(TRIM(REPLACE(s.`middle_initial`, '.', '')), ''), s.`last_name`))
-- Only updates accounts that have the student role.
WHERE u.`role` = 'student';

-- Opens fix 4: triggers that keep names in sync on future writes.
-- 4) Triggers that keep the account name and the student profile synchronized
-- Notes the triggers cover all later inserts and updates.
--    for all future writes.
-- Drops the old insert trigger so it can be recreated.
DROP TRIGGER IF EXISTS `students_after_insert_sync_user`;
-- Creates the trigger that runs after a student row is inserted.
CREATE TRIGGER `students_after_insert_sync_user`
-- Sets the trigger to fire AFTER INSERT on students.
AFTER INSERT ON `students`
-- Makes the trigger run once for each inserted row.
FOR EACH ROW
-- Updates the users table from inside the trigger.
UPDATE `users`
-- Builds the same canonical name from the NEW row values.
SET `full_name` = TRIM(CONCAT_WS(' ', NEW.`first_name`, NULLIF(TRIM(REPLACE(NEW.`middle_initial`, '.', '')), ''), NEW.`last_name`))
-- Only updates the account that owns the new student row.
WHERE `user_id` = NEW.`user_id`;

-- Drops the old update trigger before recreating it.
DROP TRIGGER IF EXISTS `students_after_update_sync_user`;
-- Creates the trigger that runs after a student row is updated.
CREATE TRIGGER `students_after_update_sync_user`
-- Sets the trigger to fire AFTER UPDATE on students.
AFTER UPDATE ON `students`
-- Makes the trigger run once for each updated row.
FOR EACH ROW
-- Updates the users table from inside the trigger.
UPDATE `users`
-- Builds the canonical name from the updated NEW values.
SET `full_name` = TRIM(CONCAT_WS(' ', NEW.`first_name`, NULLIF(TRIM(REPLACE(NEW.`middle_initial`, '.', '')), ''), NEW.`last_name`))
-- Only updates the account that owns the updated student row.
WHERE `user_id` = NEW.`user_id`;
