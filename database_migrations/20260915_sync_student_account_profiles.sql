-- Keep the account name and the student profile name synchronized for all future writes.
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

-- Backfill existing records so the Students list reads the same identity as its linked account.
UPDATE `users` u
INNER JOIN `students` s ON s.`user_id` = u.`user_id`
SET u.`full_name` = TRIM(CONCAT_WS(' ', s.`first_name`, NULLIF(TRIM(REPLACE(s.`middle_initial`, '.', '')), ''), s.`last_name`))
WHERE u.`role` = 'student';
