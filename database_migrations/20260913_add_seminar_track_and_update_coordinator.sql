-- Notes that this change is applied once to an existing csite_grad_school database.
-- Apply this once to an existing `csite_grad_school` database.
-- Notes that a fresh install already gets the updated schema from the project SQL dump.
-- A fresh installation only needs the updated project SQL dump.

-- Changes the students table that stores every student profile.
ALTER TABLE `students`
  -- Allows the new seminar track value in the track ENUM column.
  MODIFY `track` ENUM('thesis', 'capstone', 'seminar') NOT NULL;

-- Updates the coordinators table row of the main coordinator.
UPDATE `coordinators`
-- Sets the coordinator first and last name to Precious Opinion.
SET `first_name` = 'Precious', `last_name` = 'Opinion'
-- Only changes the coordinator whose coordinator_id is 1.
WHERE `coordinator_id` = 1;

-- Updates the matching user login account.
UPDATE `users`
-- Sets the account full name to Ma'am Precious Opinion.
SET `full_name` = 'Ma''am Precious Opinion'
-- Only updates user_id 2 when that account has the coordinator role.
WHERE `user_id` = 2 AND `role` = 'coordinator';
