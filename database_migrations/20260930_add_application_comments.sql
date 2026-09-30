-- Coordinator review notes and revision instructions for an application.
CREATE TABLE IF NOT EXISTS `application_comments` (
  `comment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `application_id` INT(11) NOT NULL,
  `coordinator_user_id` INT(11) NOT NULL,
  `comment_text` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`comment_id`),
  KEY `idx_application_comments_application` (`application_id`, `created_at`),
  KEY `idx_application_comments_coordinator` (`coordinator_user_id`),
  CONSTRAINT `fk_application_comments_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_application_comments_coordinator` FOREIGN KEY (`coordinator_user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;