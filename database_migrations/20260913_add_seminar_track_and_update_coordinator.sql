-- Apply this once to an existing `csite_grad_school` database.
-- A fresh installation only needs the updated project SQL dump.

ALTER TABLE `students`
  MODIFY `track` ENUM('thesis', 'capstone', 'seminar') NOT NULL;

UPDATE `coordinators`
SET `first_name` = 'Precious', `last_name` = 'Opinion'
WHERE `coordinator_id` = 1;

UPDATE `users`
SET `full_name` = 'Ma''am Precious Opinion'
WHERE `user_id` = 2 AND `role` = 'coordinator';
