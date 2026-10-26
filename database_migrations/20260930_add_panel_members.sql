-- Explains names are stored in parts so records sort and search easily.
-- Panel members are stored as individual name parts so records can be sorted
-- Notes no display-name string parsing is needed.
-- and searched without parsing a display-name string.
-- Creates the panel_members table if it does not exist.
CREATE TABLE IF NOT EXISTS `panel_members` (
  -- panel_member_id is the auto-numbered primary key.
  `panel_member_id` INT(11) NOT NULL AUTO_INCREMENT,
  -- first_name stores the panel member first name.
  `first_name` VARCHAR(100) NOT NULL,
  -- middle_name stores the optional middle name.
  `middle_name` VARCHAR(100) DEFAULT NULL,
  -- last_name stores the panel member last name.
  `last_name` VARCHAR(100) NOT NULL,
  -- qualification describes the panel member credentials.
  `qualification` VARCHAR(150) NOT NULL,
  -- email stores an optional contact email address.
  `email` VARCHAR(100) DEFAULT NULL,
  -- availability marks whether the member can still serve.
  `availability` ENUM('available','unavailable') NOT NULL DEFAULT 'available',
  -- notes holds extra free-text remarks.
  `notes` TEXT DEFAULT NULL,
  -- created_at records when the member row was added.
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  -- updated_at refreshes automatically when the row changes.
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  -- Makes panel_member_id the primary key.
  PRIMARY KEY (`panel_member_id`),
  -- Ensures one panel member per email address.
  UNIQUE KEY `uq_panel_members_email` (`email`),
  -- Adds an index for sorting members by name.
  KEY `idx_panel_members_name` (`last_name`, `first_name`, `middle_name`)
-- Uses InnoDB and utf8mb4 for the table.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;