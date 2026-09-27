-- =============================================================================
-- 20260926_add_workflow_stages.sql
-- -----------------------------------------------------------------------------
-- Adds workflow stages needed to support per-track academic stages.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `workflow_stages` (
  `stage_id` INT(11) NOT NULL AUTO_INCREMENT,
  `track_id` INT(11) NOT NULL,
  `stage_key` VARCHAR(50) NOT NULL,
  `stage_label` VARCHAR(100) NOT NULL,
  `stage_order` INT(11) NOT NULL DEFAULT 1,
  `is_required` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`stage_id`),
  UNIQUE KEY `uq_workflow_stage` (`track_id`, `stage_key`),
  KEY `idx_workflow_track_order` (`track_id`, `stage_order`),
  CONSTRAINT `fk_workflow_track`
    FOREIGN KEY (`track_id`) REFERENCES `tracks` (`track_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `workflow_stages` (`track_id`, `stage_key`, `stage_label`, `stage_order`)
SELECT `tracks`.`track_id`, 'concept', 'Concept Paper', 1
FROM `tracks` WHERE `tracks`.`track_code` = 'thesis'
AND NOT EXISTS (SELECT 1 FROM `workflow_stages` WHERE `workflow_stages`.`track_id` = `tracks`.`track_id` AND `workflow_stages`.`stage_key` = 'concept');

INSERT INTO `workflow_stages` (`track_id`, `stage_key`, `stage_label`, `stage_order`)
SELECT `tracks`.`track_id`, 'proposal', 'Thesis Proposal', 2
FROM `tracks` WHERE `tracks`.`track_code` = 'thesis'
AND NOT EXISTS (SELECT 1 FROM `workflow_stages` WHERE `workflow_stages`.`track_id` = `tracks`.`track_id` AND `workflow_stages`.`stage_key` = 'proposal');

INSERT INTO `workflow_stages` (`track_id`, `stage_key`, `stage_label`, `stage_order`)
SELECT `tracks`.`track_id`, 'final', 'Final Thesis', 3
FROM `tracks` WHERE `tracks`.`track_code` = 'thesis'
AND NOT EXISTS (SELECT 1 FROM `workflow_stages` WHERE `workflow_stages`.`track_id` = `tracks`.`track_id` AND `workflow_stages`.`stage_key` = 'final');

INSERT INTO `workflow_stages` (`track_id`, `stage_key`, `stage_label`, `stage_order`)
SELECT `tracks`.`track_id`, 'proposal', 'Capstone Proposal', 1
FROM `tracks` WHERE `tracks`.`track_code` = 'capstone'
AND NOT EXISTS (SELECT 1 FROM `workflow_stages` WHERE `workflow_stages`.`track_id` = `tracks`.`track_id` AND `workflow_stages`.`stage_key` = 'proposal');

INSERT INTO `workflow_stages` (`track_id`, `stage_key`, `stage_label`, `stage_order`)
SELECT `tracks`.`track_id`, 'final', 'Final Capstone', 2
FROM `tracks` WHERE `tracks`.`track_code` = 'capstone'
AND NOT EXISTS (SELECT 1 FROM `workflow_stages` WHERE `workflow_stages`.`track_id` = `tracks`.`track_id` AND `workflow_stages`.`stage_key` = 'final');

INSERT INTO `workflow_stages` (`track_id`, `stage_key`, `stage_label`, `stage_order`)
SELECT `tracks`.`track_id`, 'proposal', 'Seminar Paper Proposal', 1
FROM `tracks` WHERE `tracks`.`track_code` = 'seminar'
AND NOT EXISTS (SELECT 1 FROM `workflow_stages` WHERE `workflow_stages`.`track_id` = `tracks`.`track_id` AND `workflow_stages`.`stage_key` = 'proposal');

INSERT INTO `workflow_stages` (`track_id`, `stage_key`, `stage_label`, `stage_order`)
SELECT `tracks`.`track_id`, 'final', 'Final Seminar Paper', 2
FROM `tracks` WHERE `tracks`.`track_code` = 'seminar'
AND NOT EXISTS (SELECT 1 FROM `workflow_stages` WHERE `workflow_stages`.`track_id` = `tracks`.`track_id` AND `workflow_stages`.`stage_key` = 'final');
