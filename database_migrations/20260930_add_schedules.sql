-- Explains the table holds presentation schedule details.
-- Presentation schedule details. Panel membership is normalized separately.
-- Notes panel membership is stored in a separate table.
CREATE TABLE IF NOT EXISTS `schedules` (
  -- Creates the schedules table if it does not exist.
  `schedule_id` INT(11) NOT NULL AUTO_INCREMENT,
  -- schedule_id is the auto-numbered primary key.
  `application_id` INT(11) NOT NULL,
  -- application_id links the schedule to an application.
  `managed_by_user_id` INT(11) NOT NULL,
  -- managed_by_user_id identifies who manages the schedule.
  `presentation_date` DATE NOT NULL,
  -- presentation_date stores the defense date.
  `start_time` TIME NOT NULL,
  -- start_time stores when the presentation starts.
  `end_time` TIME DEFAULT NULL,
  -- end_time stores when it ends, if known.
  `venue` VARCHAR(200) NOT NULL,
  -- venue stores where the presentation happens.
  `adviser_id` INT(11) DEFAULT NULL,
  -- adviser_id optionally links an adviser from the pool.
  `documentor_first_name` VARCHAR(100) DEFAULT NULL,
  -- documentor_first_name stores the documenter first name.
  `documentor_middle_name` VARCHAR(100) DEFAULT NULL,
  -- documentor_middle_name stores the documenter middle name.
  `documentor_last_name` VARCHAR(100) DEFAULT NULL,
  -- documentor_last_name stores the documenter last name.
  `status` ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  -- status marks it pending, confirmed, completed, or cancelled.
  `notes` TEXT DEFAULT NULL,
  -- notes holds extra free-text remarks.
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  -- created_at records when the schedule row was added.
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  -- updated_at refreshes automatically when the row changes.
  PRIMARY KEY (`schedule_id`),
  -- Makes schedule_id the primary key.
  UNIQUE KEY `uq_schedules_application` (`application_id`),
  -- Allows only one schedule per application.
  KEY `idx_schedules_date_time` (`presentation_date`, `start_time`),
  -- Adds an index for finding schedules by date and time.
  KEY `idx_schedules_manager` (`managed_by_user_id`),
  -- Adds an index on the managing user.
  KEY `idx_schedules_adviser` (`adviser_id`),
  -- Adds an index on the linked adviser.
  CONSTRAINT `fk_schedules_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  -- Ties the schedule to its application and deletes it with that application.
  CONSTRAINT `fk_schedules_manager` FOREIGN KEY (`managed_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  -- Ties the schedule to its manager and blocks deleting that account.
  CONSTRAINT `fk_schedules_adviser` FOREIGN KEY (`adviser_id`) REFERENCES `advisor_pool` (`adviser_id`) ON DELETE SET NULL ON UPDATE CASCADE
-- Ties the adviser and clears the link if the adviser row is deleted.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;