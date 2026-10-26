-- Comment banner that opens the header of this migration.
-- =============================================================================
-- Names the migration file that adds workflow fields.
-- 20260916_add_application_workflow_fields.sql
-- Divider line under the migration file name.
-- -----------------------------------------------------------------------------
-- Starts the explanation of the problem being fixed.
-- Problem this fixes:
-- Notes the coordinator Process Application screen collects workflow steps.
--   The coordinator "Process Application" screen collects the real graduate
-- Lists the Graduate School endorsement as one collected step.
--   school workflow steps (Graduate School endorsement issued, official receipt
-- Lists the official receipt and payment as another collected step.
--   / payment recorded, requirements verified and ready for presentation,
-- Lists verified requirements and readiness for presentation.
--   receipt number / date / amount, coordinator recommendations, per-document
-- Lists receipt details, recommendations, and document review statuses.
--   review statuses and the presentation result) but the applications table had
-- Says only status and presentation_stage could be saved before.
--   nowhere to store them. Only `status` and `presentation_stage` were saved, so
-- Notes the other entered values were silently thrown away.
--   everything else was silently discarded and the student's Process Tracker
-- Notes the student tracker could never mark those steps complete.
--   could never show those steps as complete.
-- Ends the problem description paragraph.
--
-- Says the workflow shape is the same for all three tracks.
-- Applies to the official CSITE graduate school workflow, which is identical in
-- Introduces the list of track stage names.
-- shape for all three tracks:
-- Thesis stages: concept paper, proposal, then final thesis.
--     thesis  : Concept Paper -> Thesis Proposal -> Final Thesis
-- Capstone stages: capstone proposal, then final capstone.
--     capstone: Capstone Proposal -> Final Capstone
-- Seminar stages: seminar paper proposal, then final seminar paper.
--     seminar : Seminar Paper Proposal -> Final Seminar Paper
-- Says every stage also has adviser and coordinator endorsement.
--   and, at every stage: adviser endorsement -> coordinator endorsement to
-- Continues the steps: Graduate School endorsement and payment.
--   Graduate School -> payment (official receipt) -> ready for presentation ->
-- Ends with the scheduled presentation and recorded result.
--   scheduled presentation -> result recorded.
-- Ends the workflow description list.
--
-- Notes the script is idempotent and safe to rerun.
-- Idempotent: safe to run more than once.
-- Closes the header comment banner.
-- =============================================================================

-- Notes the guard for columns that were already added by hand.
-- Guard against environments where a column was added by hand.
-- Stores the current database name in a variable for the checks.
SET @db := DATABASE();

-- Explains the coordinator comment column being added.
-- Coordinator's recommendations / revision notes for the current stage.
-- Builds the ALTER or no-op statement based on a column check.
SET @sql := (SELECT IF(
  -- Counts matching rows in the columns metadata table.
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     -- Checks for an existing coordinator_comment column on applications.
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'applications' AND COLUMN_NAME = 'coordinator_comment') = 0,
  -- SQL that adds the coordinator_comment text column, or does nothing.
  'ALTER TABLE `applications` ADD COLUMN `coordinator_comment` TEXT DEFAULT NULL AFTER `status`',
  -- Empty alternative used when the column already exists.
  'DO 0'));
-- Prepares, runs, and frees the built statement.
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Explains the Graduate School endorsement flag.
-- Graduate School endorsement issued for this stage.
-- Builds the ALTER or no-op statement for that flag.
SET @sql := (SELECT IF(
  -- Counts matching rows in the columns metadata table.
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     -- Checks for an existing grad_school_endorsed column.
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'applications' AND COLUMN_NAME = 'grad_school_endorsed') = 0,
  -- SQL that adds the grad_school_endorsed flag column.
  'ALTER TABLE `applications` ADD COLUMN `grad_school_endorsed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `coordinator_comment`',
  -- Empty alternative used when the column already exists.
  'DO 0'));
-- Prepares, runs, and frees the built statement.
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Explains the payment recorded flag.
-- Official receipt / payment recorded for this stage.
-- Builds the ALTER or no-op statement for that payment flag.
SET @sql := (SELECT IF(
  -- Counts matching rows in the columns metadata table.
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     -- Checks for an existing payment_recorded column.
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'applications' AND COLUMN_NAME = 'payment_recorded') = 0,
  -- SQL that adds the payment_recorded flag column.
  'ALTER TABLE `applications` ADD COLUMN `payment_recorded` TINYINT(1) NOT NULL DEFAULT 0 AFTER `grad_school_endorsed`',
  -- Empty alternative used when the column already exists.
  'DO 0'));
-- Prepares, runs, and frees the built statement.
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Builds the ALTER or no-op statement for the receipt number.
SET @sql := (SELECT IF(
  -- Counts matching rows in the columns metadata table.
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     -- Checks for an existing receipt_number column.
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'applications' AND COLUMN_NAME = 'receipt_number') = 0,
  -- SQL that adds the receipt_number text column.
  'ALTER TABLE `applications` ADD COLUMN `receipt_number` VARCHAR(100) DEFAULT NULL AFTER `payment_recorded`',
  -- Empty alternative used when the column already exists.
  'DO 0'));
-- Prepares, runs, and frees the built statement.
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Builds the ALTER or no-op statement for the payment date.
SET @sql := (SELECT IF(
  -- Counts matching rows in the columns metadata table.
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     -- Checks for an existing payment_date column.
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'applications' AND COLUMN_NAME = 'payment_date') = 0,
  -- SQL that adds the payment_date date column.
  'ALTER TABLE `applications` ADD COLUMN `payment_date` DATE DEFAULT NULL AFTER `receipt_number`',
  -- Empty alternative used when the column already exists.
  'DO 0'));
-- Prepares, runs, and frees the built statement.
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Builds the ALTER or no-op statement for the payment amount.
SET @sql := (SELECT IF(
  -- Counts matching rows in the columns metadata table.
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     -- Checks for an existing payment_amount column.
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'applications' AND COLUMN_NAME = 'payment_amount') = 0,
  -- SQL that adds the payment_amount decimal column.
  'ALTER TABLE `applications` ADD COLUMN `payment_amount` DECIMAL(10,2) DEFAULT NULL AFTER `payment_date`',
  -- Empty alternative used when the column already exists.
  'DO 0'));
-- Prepares, runs, and frees the built statement.
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Explains the ready for presentation flag.
-- Requirements verified; the student may be scheduled for presentation.
-- Builds the ALTER or no-op statement for that flag.
SET @sql := (SELECT IF(
  -- Counts matching rows in the columns metadata table.
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     -- Checks for an existing ready_for_presentation column.
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'applications' AND COLUMN_NAME = 'ready_for_presentation') = 0,
  -- SQL that adds the ready_for_presentation flag column.
  'ALTER TABLE `applications` ADD COLUMN `ready_for_presentation` TINYINT(1) NOT NULL DEFAULT 0 AFTER `payment_amount`',
  -- Empty alternative used when the column already exists.
  'DO 0'));
-- Prepares, runs, and frees the built statement.
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Explains the JSON column that holds per-document review state.
-- Per-document review state (paper / adviser_endorsement / receipt) as JSON.
-- Builds the ALTER or no-op statement for workflow_state.
SET @sql := (SELECT IF(
  -- Counts matching rows in the columns metadata table.
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     -- Checks for an existing workflow_state column.
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'applications' AND COLUMN_NAME = 'workflow_state') = 0,
  -- SQL that adds the workflow_state text column.
  'ALTER TABLE `applications` ADD COLUMN `workflow_state` TEXT DEFAULT NULL AFTER `ready_for_presentation`',
  -- Empty alternative used when the column already exists.
  'DO 0'));
-- Prepares, runs, and frees the built statement.
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Explains the presentation result column and its allowed values.
-- Presentation outcome: approved / requires_revision / '' (not yet presented).
-- Builds the ALTER or no-op statement for the result column.
SET @sql := (SELECT IF(
  -- Counts matching rows in the columns metadata table.
  (SELECT COUNT(*) FROM information_schema.COLUMNS
     -- Checks for an existing result column.
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'applications' AND COLUMN_NAME = 'result') = 0,
  -- SQL that adds the result varchar column.
  'ALTER TABLE `applications` ADD COLUMN `result` VARCHAR(50) DEFAULT NULL AFTER `workflow_state`',
  -- Empty alternative used when the column already exists.
  'DO 0'));
-- Prepares, runs, and frees the built statement.
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
