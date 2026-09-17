-- Apply this migration to existing csite_grad_school databases.
ALTER TABLE `students` ADD COLUMN `archived_at` TIMESTAMP NULL DEFAULT NULL AFTER `enrollment_date`;

CREATE TABLE IF NOT EXISTS `advisor_pool` (
  `adviser_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `qualification` VARCHAR(150) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `availability` ENUM('available','unavailable') NOT NULL DEFAULT 'available',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`adviser_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
