-- Explains the table holds coordinator notes and revision instructions.
-- Coordinator review notes and revision instructions for an application.
-- Creates the application_comments table if it does not exist.
CREATE TABLE IF NOT EXISTS `application_comments` (
  -- comment_id is the auto-numbered primary key.
  `comment_id` INT(11) NOT NULL AUTO_INCREMENT,
  -- application_id links the note to an application.
  `application_id` INT(11) NOT NULL,
  -- coordinator_user_id identifies the coordinator who wrote it.
  `coordinator_user_id` INT(11) NOT NULL,
  -- comment_text stores the actual note text.
  `comment_text` TEXT NOT NULL,
  -- created_at records when the note was written.
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  -- Makes comment_id the primary key.
  PRIMARY KEY (`comment_id`),
  -- Adds an index for listing notes per application by time.
  KEY `idx_application_comments_application` (`application_id`, `created_at`),
  -- Adds an index on the coordinator id for lookups.
  KEY `idx_application_comments_coordinator` (`coordinator_user_id`),
  -- Ties the note to its application and deletes it with that application.
  CONSTRAINT `fk_application_comments_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  -- Ties the note to a coordinator account and blocks deleting that account.
  CONSTRAINT `fk_application_comments_coordinator` FOREIGN KEY (`coordinator_user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
-- Uses InnoDB and utf8mb4 for keys and special characters.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;