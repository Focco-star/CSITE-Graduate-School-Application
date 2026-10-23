-- Migration: add documenter pool table (2026-10-27)
--
-- The documenter pool manages documenters who record presentation proceedings.
-- Follows the same pattern as advisor_pool and panel_members.

CREATE TABLE IF NOT EXISTS `documenter_pool` (
  `documenter_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `availability` enum('available','unavailable') NOT NULL DEFAULT 'available',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`documenter_id`),
  KEY `idx_availability` (`availability`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;