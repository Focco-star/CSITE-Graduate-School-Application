-- Explains this table links schedules to their panel members.
-- Resolves the schedule-to-panel-member many-to-many relationship.
-- Creates the schedule_panel_assignments table if it does not exist.
CREATE TABLE IF NOT EXISTS `schedule_panel_assignments` (
  -- assignment_id is the auto-numbered primary key.
  `assignment_id` INT(11) NOT NULL AUTO_INCREMENT,
  -- schedule_id links the row to a schedule.
  `schedule_id` INT(11) NOT NULL,
  -- panel_member_id links the row to a panel member.
  `panel_member_id` INT(11) NOT NULL,
  -- panel_role stores chair, member, adviser, or documentor.
  `panel_role` ENUM('chair','member','adviser','documentor') NOT NULL DEFAULT 'member',
  -- notes holds extra free-text remarks about the assignment.
  `notes` TEXT DEFAULT NULL,
  -- created_at records when the assignment row was added.
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  -- Makes assignment_id the primary key.
  PRIMARY KEY (`assignment_id`),
  -- Prevents adding the same member twice to one schedule.
  UNIQUE KEY `uq_schedule_panel_member` (`schedule_id`, `panel_member_id`),
  -- Adds an index on the panel member for reverse lookups.
  KEY `idx_assignments_panel_member` (`panel_member_id`),
  -- Ties the row to its schedule and deletes it with that schedule.
  CONSTRAINT `fk_assignments_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`schedule_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  -- Ties the row to a panel member and blocks deleting that member.
  CONSTRAINT `fk_assignments_panel_member` FOREIGN KEY (`panel_member_id`) REFERENCES `panel_members` (`panel_member_id`) ON DELETE RESTRICT ON UPDATE CASCADE
-- Uses InnoDB and utf8mb4 for the table.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;