-- Panel members are stored as individual name parts so records can be sorted
-- and searched without parsing a display-name string.
CREATE TABLE IF NOT EXISTS `panel_members` (
  `panel_member_id` INT(11) NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) DEFAULT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `qualification` VARCHAR(150) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `availability` ENUM('available','unavailable') NOT NULL DEFAULT 'available',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`panel_member_id`),
  UNIQUE KEY `uq_panel_members_email` (`email`),
  KEY `idx_panel_members_name` (`last_name`, `first_name`, `middle_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;