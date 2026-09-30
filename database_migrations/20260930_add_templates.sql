-- Downloadable forms and reference files are scoped to a track and stage.
CREATE TABLE IF NOT EXISTS `templates` (
  `template_id` INT(11) NOT NULL AUTO_INCREMENT,
  `track_id` INT(11) NOT NULL,
  `stage_id` INT(11) DEFAULT NULL,
  `template_name` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `document_type` ENUM('form','template','reference') NOT NULL DEFAULT 'template',
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `mime_type` VARCHAR(100) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `managed_by_user_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`template_id`),
  KEY `idx_templates_track_stage` (`track_id`, `stage_id`),
  KEY `idx_templates_manager` (`managed_by_user_id`),
  CONSTRAINT `fk_templates_track` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`track_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_templates_stage` FOREIGN KEY (`stage_id`) REFERENCES `workflow_stages` (`stage_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_templates_manager` FOREIGN KEY (`managed_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;