CREATE TABLE IF NOT EXISTS `application_panel_assignments` (
  `assignment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `application_id` INT(11) NOT NULL,
  `panel_member_id` INT(11) NOT NULL,
  `panel_role` ENUM('chair','member','documentor') NOT NULL DEFAULT 'member',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`assignment_id`),
  UNIQUE KEY `uq_application_panel_member` (`application_id`, `panel_member_id`),
  KEY `idx_application_panel_member` (`panel_member_id`),
  CONSTRAINT `fk_application_panel_application`
    FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_application_panel_member`
    FOREIGN KEY (`panel_member_id`) REFERENCES `panel_members` (`panel_member_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
