-- Explains the table is a dedicated pool for presentation documenters.
-- Dedicated pool for presentation documenters.
-- Creates the documenter_pool table if it does not exist.
CREATE TABLE IF NOT EXISTS `documenter_pool` (
  -- documenter_id is the auto-numbered primary key.
  `documenter_id` INT(11) NOT NULL AUTO_INCREMENT,
  -- first_name stores the documenter first name.
  `first_name` VARCHAR(100) NOT NULL,
  -- middle_name stores the optional middle name.
  `middle_name` VARCHAR(100) DEFAULT NULL,
  -- last_name stores the documenter last name.
  `last_name` VARCHAR(100) NOT NULL,
  -- qualification describes the documenter credentials.
  `qualification` VARCHAR(150) NOT NULL,
  -- email stores an optional contact email address.
  `email` VARCHAR(100) DEFAULT NULL,
  -- availability marks whether the documenter can still serve.
  `availability` ENUM('available','unavailable') NOT NULL DEFAULT 'available',
  -- notes holds extra free-text remarks.
  `notes` TEXT DEFAULT NULL,
  -- created_at records when the documenter row was added.
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  -- Makes documenter_id the primary key.
  PRIMARY KEY (`documenter_id`),
  -- Adds an index for finding available documenters by name.
  KEY `idx_documenter_availability_name` (`availability`, `last_name`, `first_name`)
-- Uses InnoDB and utf8mb4 for the table.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
