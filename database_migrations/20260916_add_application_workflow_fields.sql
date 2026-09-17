-- =============================================================================
-- 20260916_add_application_workflow_fields.sql
-- -----------------------------------------------------------------------------
-- Problem this fixes:
--   The coordinator "Process Application" screen collects the real graduate
--   school workflow steps (Graduate School endorsement issued, official receipt
--   / payment recorded, requirements verified and ready for presentation,
--   receipt number / date / amount, coordinator recommendations, per-document
--   review statuses and the presentation result) but the applications table had
--   nowhere to store them. Only `status` and `presentation_stage` were saved, so
--   everything else was silently discarded and the student's Process Tracker
--   could never show those steps as complete.
--
-- Applies to the official CSITE graduate school workflow, which is identical in
-- shape for all three tracks:
--     thesis  : Concept Paper -> Thesis Proposal -> Final Thesis
--     capstone: Capstone Proposal -> Final Capstone
--     seminar : Seminar Paper Proposal -> Final Seminar Paper
--   and, at every stage: adviser endorsement -> coordinator endorsement to
--   Graduate School -> payment (official receipt) -> ready for presentation ->
--   scheduled presentation -> result recorded.
--
-- Idempotent: safe to run more than once.
-- =============================================================================

-- Guard against environments where a column was added by hand.
SET @db := DATABASE();

-- Coordinator's recommendations / revision notes for the current stage.
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'applications' AND COLUMN_NAME = 'coordinator_comment') = 0,
  'ALTER TABLE `applications` ADD COLUMN `coordinator_comment` TEXT DEFAULT NULL AFTER `status`',
  'DO 0'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Graduate School endorsement issued for this stage.
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'applications' AND COLUMN_NAME = 'grad_school_endorsed') = 0,
  'ALTER TABLE `applications` ADD COLUMN `grad_school_endorsed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `coordinator_comment`',
  'DO 0'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Official receipt / payment recorded for this stage.
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'applications' AND COLUMN_NAME = 'payment_recorded') = 0,
  'ALTER TABLE `applications` ADD COLUMN `payment_recorded` TINYINT(1) NOT NULL DEFAULT 0 AFTER `grad_school_endorsed`',
  'DO 0'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'applications' AND COLUMN_NAME = 'receipt_number') = 0,
  'ALTER TABLE `applications` ADD COLUMN `receipt_number` VARCHAR(100) DEFAULT NULL AFTER `payment_recorded`',
  'DO 0'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'applications' AND COLUMN_NAME = 'payment_date') = 0,
  'ALTER TABLE `applications` ADD COLUMN `payment_date` DATE DEFAULT NULL AFTER `receipt_number`',
  'DO 0'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'applications' AND COLUMN_NAME = 'payment_amount') = 0,
  'ALTER TABLE `applications` ADD COLUMN `payment_amount` DECIMAL(10,2) DEFAULT NULL AFTER `payment_date`',
  'DO 0'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Requirements verified; the student may be scheduled for presentation.
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'applications' AND COLUMN_NAME = 'ready_for_presentation') = 0,
  'ALTER TABLE `applications` ADD COLUMN `ready_for_presentation` TINYINT(1) NOT NULL DEFAULT 0 AFTER `payment_amount`',
  'DO 0'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Per-document review state (paper / adviser_endorsement / receipt) as JSON.
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'applications' AND COLUMN_NAME = 'workflow_state') = 0,
  'ALTER TABLE `applications` ADD COLUMN `workflow_state` TEXT DEFAULT NULL AFTER `ready_for_presentation`',
  'DO 0'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Presentation outcome: approved / requires_revision / '' (not yet presented).
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'applications' AND COLUMN_NAME = 'result') = 0,
  'ALTER TABLE `applications` ADD COLUMN `result` VARCHAR(50) DEFAULT NULL AFTER `workflow_state`',
  'DO 0'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
