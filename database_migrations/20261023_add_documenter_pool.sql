-- Dedicated pool for presentation documenters.
CREATE TABLE IF NOT EXISTS `documenter_pool` (
  `documenter_id` INT(11) NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) DEFAULT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `qualification` VARCHAR(150) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `availability` ENUM('available','unavailable') NOT NULL DEFAULT 'available',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`documenter_id`),
  KEY `idx_documenter_availability_name` (`availability`, `last_name`, `first_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
