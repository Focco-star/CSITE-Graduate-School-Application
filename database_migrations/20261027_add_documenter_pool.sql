-- Names the migration and date for the documenter pool table.
-- Migration: add documenter pool table (2026-10-27)
-- Divider line separating the title from the explanation.
--
-- Says the pool manages documenters who record presentation proceedings.
-- The documenter pool manages documenters who record presentation proceedings.
-- Notes it follows the same pattern as advisor_pool and panel_members.
-- Follows the same pattern as advisor_pool and panel_members.

-- Creates the documenter_pool table if it does not exist.
CREATE TABLE IF NOT EXISTS `documenter_pool` (
  -- documenter_id is the auto-numbered primary key.
  `documenter_id` int(11) NOT NULL AUTO_INCREMENT,
  -- first_name stores the documenter first name.
  `first_name` varchar(100) NOT NULL,
  -- middle_name stores the optional middle name.
  `middle_name` varchar(100) DEFAULT NULL,
  -- last_name stores the documenter last name.
  `last_name` varchar(100) NOT NULL,
  -- qualification optionally describes the documenter credentials.
  `qualification` varchar(255) DEFAULT NULL,
  -- email stores an optional contact email address.
  `email` varchar(255) DEFAULT NULL,
  -- availability marks whether the documenter can still serve.
  `availability` enum('available','unavailable') NOT NULL DEFAULT 'available',
  -- notes holds extra free-text remarks.
  `notes` text DEFAULT NULL,
  -- created_at records when the documenter row was added.
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  -- updated_at refreshes automatically when the row changes.
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  -- Makes documenter_id the primary key.
  PRIMARY KEY (`documenter_id`),
  -- Adds an index on availability for quick filtering.
  KEY `idx_availability` (`availability`)
-- Uses InnoDB and utf8mb4 for the table.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;