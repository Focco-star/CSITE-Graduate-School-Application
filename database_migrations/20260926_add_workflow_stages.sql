-- Comment banner that opens the header of this migration.
-- =============================================================================
-- Names the file that adds the workflow_stages table.
-- 20260926_add_workflow_stages.sql
-- Divider line under the file name.
-- -----------------------------------------------------------------------------
-- Says it adds the stages needed for per-track academic stages.
-- Adds workflow stages needed to support per-track academic stages.
-- Closes the header comment banner.
-- =============================================================================

-- Creates the workflow_stages table that lists stages for each track.
CREATE TABLE IF NOT EXISTS `workflow_stages` (
  -- stage_id is the auto-numbered primary key.
  `stage_id` INT(11) NOT NULL AUTO_INCREMENT,
  -- track_id links each stage to a track.
  `track_id` INT(11) NOT NULL,
  -- stage_key stores the short stage code, such as proposal.
  `stage_key` VARCHAR(50) NOT NULL,
  -- stage_label stores the readable stage name.
  `stage_label` VARCHAR(100) NOT NULL,
  -- stage_order gives the position of the stage within the track.
  `stage_order` INT(11) NOT NULL DEFAULT 1,
  -- is_required marks whether the stage must be completed.
  `is_required` TINYINT(1) NOT NULL DEFAULT 1,
  -- created_at records when the stage row was added.
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  -- Makes stage_id the primary key.
  PRIMARY KEY (`stage_id`),
  -- Ensures one stage key per track cannot be repeated.
  UNIQUE KEY `uq_workflow_stage` (`track_id`, `stage_key`),
  -- Adds an index for listing stages by track and order.
  KEY `idx_workflow_track_order` (`track_id`, `stage_order`),
  -- Starts the foreign key that links stages to tracks.
  CONSTRAINT `fk_workflow_track`
    -- Points track_id at the tracks table.
    FOREIGN KEY (`track_id`) REFERENCES `tracks` (`track_id`)
    -- Deletes stages with their track and updates ids on change.
    ON DELETE CASCADE ON UPDATE CASCADE
-- Uses InnoDB and utf8mb4 for the workflow_stages table.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Starts inserting the first thesis stage.
INSERT INTO `workflow_stages` (`track_id`, `stage_key`, `stage_label`, `stage_order`)
-- Adds concept paper as stage 1 for the thesis track.
SELECT `tracks`.`track_id`, 'concept', 'Concept Paper', 1
-- Finds the thesis track by its code.
FROM `tracks` WHERE `tracks`.`track_code` = 'thesis'
-- Skips the insert when that stage already exists.
AND NOT EXISTS (SELECT 1 FROM `workflow_stages` WHERE `workflow_stages`.`track_id` = `tracks`.`track_id` AND `workflow_stages`.`stage_key` = 'concept');

-- Starts inserting the second thesis stage.
INSERT INTO `workflow_stages` (`track_id`, `stage_key`, `stage_label`, `stage_order`)
-- Adds thesis proposal as stage 2 for the thesis track.
SELECT `tracks`.`track_id`, 'proposal', 'Thesis Proposal', 2
-- Finds the thesis track by its code.
FROM `tracks` WHERE `tracks`.`track_code` = 'thesis'
-- Skips the insert when that stage already exists.
AND NOT EXISTS (SELECT 1 FROM `workflow_stages` WHERE `workflow_stages`.`track_id` = `tracks`.`track_id` AND `workflow_stages`.`stage_key` = 'proposal');

-- Starts inserting the third thesis stage.
INSERT INTO `workflow_stages` (`track_id`, `stage_key`, `stage_label`, `stage_order`)
-- Adds final thesis as stage 3 for the thesis track.
SELECT `tracks`.`track_id`, 'final', 'Final Thesis', 3
-- Finds the thesis track by its code.
FROM `tracks` WHERE `tracks`.`track_code` = 'thesis'
-- Skips the insert when that stage already exists.
AND NOT EXISTS (SELECT 1 FROM `workflow_stages` WHERE `workflow_stages`.`track_id` = `tracks`.`track_id` AND `workflow_stages`.`stage_key` = 'final');

-- Starts inserting the first capstone stage.
INSERT INTO `workflow_stages` (`track_id`, `stage_key`, `stage_label`, `stage_order`)
-- Adds capstone proposal as stage 1 for the capstone track.
SELECT `tracks`.`track_id`, 'proposal', 'Capstone Proposal', 1
-- Finds the capstone track by its code.
FROM `tracks` WHERE `tracks`.`track_code` = 'capstone'
-- Skips the insert when that stage already exists.
AND NOT EXISTS (SELECT 1 FROM `workflow_stages` WHERE `workflow_stages`.`track_id` = `tracks`.`track_id` AND `workflow_stages`.`stage_key` = 'proposal');

-- Starts inserting the second capstone stage.
INSERT INTO `workflow_stages` (`track_id`, `stage_key`, `stage_label`, `stage_order`)
-- Adds final capstone as stage 2 for the capstone track.
SELECT `tracks`.`track_id`, 'final', 'Final Capstone', 2
-- Finds the capstone track by its code.
FROM `tracks` WHERE `tracks`.`track_code` = 'capstone'
-- Skips the insert when that stage already exists.
AND NOT EXISTS (SELECT 1 FROM `workflow_stages` WHERE `workflow_stages`.`track_id` = `tracks`.`track_id` AND `workflow_stages`.`stage_key` = 'final');

-- Starts inserting the first seminar stage.
INSERT INTO `workflow_stages` (`track_id`, `stage_key`, `stage_label`, `stage_order`)
-- Adds seminar paper proposal as stage 1 for the seminar track.
SELECT `tracks`.`track_id`, 'proposal', 'Seminar Paper Proposal', 1
-- Finds the seminar track by its code.
FROM `tracks` WHERE `tracks`.`track_code` = 'seminar'
-- Skips the insert when that stage already exists.
AND NOT EXISTS (SELECT 1 FROM `workflow_stages` WHERE `workflow_stages`.`track_id` = `tracks`.`track_id` AND `workflow_stages`.`stage_key` = 'proposal');

-- Starts inserting the second seminar stage.
INSERT INTO `workflow_stages` (`track_id`, `stage_key`, `stage_label`, `stage_order`)
-- Adds final seminar paper as stage 2 for the seminar track.
SELECT `tracks`.`track_id`, 'final', 'Final Seminar Paper', 2
-- Finds the seminar track by its code.
FROM `tracks` WHERE `tracks`.`track_code` = 'seminar'
-- Skips the insert when that stage already exists.
AND NOT EXISTS (SELECT 1 FROM `workflow_stages` WHERE `workflow_stages`.`track_id` = `tracks`.`track_id` AND `workflow_stages`.`stage_key` = 'final');
