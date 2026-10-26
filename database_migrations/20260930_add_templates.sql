-- Explains templates are downloadable forms scoped to a track and stage.
-- Downloadable forms and reference files are scoped to a track and stage.
-- Creates the templates table if it does not exist.
CREATE TABLE IF NOT EXISTS `templates` (
  -- template_id is the auto-numbered primary key.
  `template_id` INT(11) NOT NULL AUTO_INCREMENT,
  -- track_id links the template to a track.
  `track_id` INT(11) NOT NULL,
  -- stage_id optionally links the template to a workflow stage.
  `stage_id` INT(11) DEFAULT NULL,
  -- template_name stores the display name of the template.
  `template_name` VARCHAR(150) NOT NULL,
  -- description holds optional extra details.
  `description` TEXT DEFAULT NULL,
  -- document_type marks it as a form, template, or reference.
  `document_type` ENUM('form','template','reference') NOT NULL DEFAULT 'template',
  -- file_name stores the original file name.
  `file_name` VARCHAR(255) NOT NULL,
  -- file_path stores where the file lives in storage.
  `file_path` VARCHAR(500) NOT NULL,
  -- mime_type records the file type.
  `mime_type` VARCHAR(100) DEFAULT NULL,
  -- is_active marks whether the template is still offered.
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  -- managed_by_user_id identifies who uploaded the template.
  `managed_by_user_id` INT(11) NOT NULL,
  -- created_at records when the template row was added.
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  -- updated_at refreshes automatically when the row changes.
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  -- Makes template_id the primary key.
  PRIMARY KEY (`template_id`),
  -- Adds an index for listing templates by track and stage.
  KEY `idx_templates_track_stage` (`track_id`, `stage_id`),
  -- Adds an index on the managing user.
  KEY `idx_templates_manager` (`managed_by_user_id`),
  -- Ties the template to a track and blocks deleting a track in use.
  CONSTRAINT `fk_templates_track` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`track_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  -- Ties the template to a stage and clears the link if it is deleted.
  CONSTRAINT `fk_templates_stage` FOREIGN KEY (`stage_id`) REFERENCES `workflow_stages` (`stage_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  -- Ties the template to its manager and blocks deleting that account.
  CONSTRAINT `fk_templates_manager` FOREIGN KEY (`managed_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
-- Uses InnoDB and utf8mb4 for the table.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;