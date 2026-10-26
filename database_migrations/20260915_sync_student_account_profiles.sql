-- Notes that this script keeps account and student profile names in sync.
-- Keep the account name and the student profile name synchronized for all future writes.
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
-- Builds the full name from first name, middle initial, and last name.
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
-- Rebuilds the full name from the updated NEW values.
SET `full_name` = TRIM(CONCAT_WS(' ', NEW.`first_name`, NULLIF(TRIM(REPLACE(NEW.`middle_initial`, '.', '')), ''), NEW.`last_name`))
-- Only updates the account that owns the updated student row.
WHERE `user_id` = NEW.`user_id`;

-- Backfills existing records so names already match.
-- Backfill existing records so the Students list reads the same identity as its linked account.
-- Updates the users table and aliases it as u.
UPDATE `users` u
-- Joins each user to the student row that points to it.
INNER JOIN `students` s ON s.`user_id` = u.`user_id`
-- Sets full_name from the student first name, middle initial, last name.
SET u.`full_name` = TRIM(CONCAT_WS(' ', s.`first_name`, NULLIF(TRIM(REPLACE(s.`middle_initial`, '.', '')), ''), s.`last_name`))
-- Only updates accounts whose role is student.
WHERE u.`role` = 'student';
