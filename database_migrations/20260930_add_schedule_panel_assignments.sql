-- Resolves the schedule-to-panel-member many-to-many relationship.
CREATE TABLE IF NOT EXISTS `schedule_panel_assignments` (
  `assignment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` INT(11) NOT NULL,
  `panel_member_id` INT(11) NOT NULL,
  `panel_role` ENUM('chair','member','adviser','documentor') NOT NULL DEFAULT 'member',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`assignment_id`),
  UNIQUE KEY `uq_schedule_panel_member` (`schedule_id`, `panel_member_id`),
  KEY `idx_assignments_panel_member` (`panel_member_id`),
  CONSTRAINT `fk_assignments_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`schedule_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_assignments_panel_member` FOREIGN KEY (`panel_member_id`) REFERENCES `panel_members` (`panel_member_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;