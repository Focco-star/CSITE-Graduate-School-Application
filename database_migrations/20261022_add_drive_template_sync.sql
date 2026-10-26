-- Names the migration and date for Google Drive template sync.
-- Migration: Google Drive template sync and update notifications (2026-10-22)
-- Divider line separating the title from the explanation.
--
-- Says the teacher keeps the official forms in a Google Drive folder.
-- The teacher keeps the official forms and templates in a Google Drive
-- Says the portal mirrors that Drive folder.
-- folder. The portal mirrors that folder so that a new or a replaced file
-- So new or replaced files reach coordinators and students quickly.
-- is available to the coordinator and to the students straight away, and
-- So the Templates menu can show a badge when something changed.
-- so that the Templates menu can show a badge when something changed.
-- Ends the summary paragraph.
--
-- Opens the list of new templates columns.
-- templates:
-- drive_file_id stores the Google Drive file id.
--   drive_file_id       the Google Drive id of the file, NULL for templates
-- Notes it stays null for portal-uploaded templates.
--                       that were uploaded through the portal itself
-- drive_modified_at stores the Drive last modified time.
--   drive_modified_at   the "last modified" time Drive reported, used to
-- Used to recognise the same version of a file.
--                       recognise the same version of a file
-- drive_size stores the file size as a second change signal.
--   drive_size          the size of the file, a second change signal
-- Ends the templates column list.
--
-- Opens the description of template_notifications.
-- template_notifications:
-- Says it stores one row per changed file.
--   one row per changed file. The unique key on (drive_file_id, changed_at)
-- The unique key on file id and change time stops duplicates.
--   is what stops the same version of a file from being announced twice.
-- So the same file version is not announced twice.
--   Each portal tracks its own "seen" time, so the coordinator and the
-- Each portal tracks its own seen time.
--   students each clear their own badge.
-- So coordinator and students clear their badges separately.
--
-- Opens the description of template_sync_state.
-- template_sync_state:
-- Notes it stores when Drive was last checked and the result.
--   when Google Drive was checked last, and how that went.

-- Changes the templates table to add Drive sync columns.
ALTER TABLE `templates`
  -- Adds an optional Google Drive file id after file_path.
  ADD COLUMN `drive_file_id` VARCHAR(120) DEFAULT NULL AFTER `file_path`,
  -- Adds the Drive last modified datetime after that id.
  ADD COLUMN `drive_modified_at` DATETIME DEFAULT NULL AFTER `drive_file_id`,
  -- Adds the Drive file size in bytes after the modified time.
  ADD COLUMN `drive_size` BIGINT DEFAULT NULL AFTER `drive_modified_at`;

-- Creates the template_notifications table if needed.
CREATE TABLE IF NOT EXISTS `template_notifications` (
  -- notification_id is the auto-numbered primary key.
  `notification_id`     INT(11)      NOT NULL AUTO_INCREMENT,
  -- template_id optionally links the notification to a template.
  `template_id`         INT(11)      DEFAULT NULL,
  -- drive_file_id stores the Drive id of the changed file.
  `drive_file_id`       VARCHAR(120) NOT NULL,
  -- file_name stores the name of the changed file.
  `file_name`           VARCHAR(255) NOT NULL,
  -- change_type marks it as added or updated.
  `change_type`         ENUM('added','updated') NOT NULL DEFAULT 'updated',
  -- changed_at stores when Drive reported the change.
  `changed_at`          DATETIME     NOT NULL,
  -- track_code stores the related track code, if any.
  `track_code`          VARCHAR(40)  DEFAULT NULL,
  -- seen_student_at stores when students last viewed it.
  `seen_student_at`     DATETIME     DEFAULT NULL,
  -- seen_coordinator_at stores when the coordinator last viewed it.
  `seen_coordinator_at` DATETIME     DEFAULT NULL,
  -- Makes notification_id the primary key.
  PRIMARY KEY (`notification_id`),
  -- Stops the same file version from being recorded twice.
  UNIQUE KEY `uq_template_notification` (`drive_file_id`, `changed_at`),
  -- Adds an index for filtering notifications by track.
  KEY `idx_template_notifications_track` (`track_code`)
-- Uses InnoDB and utf8mb4 for the table.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Creates the template_sync_state table if needed.
CREATE TABLE IF NOT EXISTS `template_sync_state` (
  -- sync_key is the primary key naming the sync job.
  `sync_key`     VARCHAR(60) NOT NULL,
  -- last_sync_at stores when Drive was last checked.
  `last_sync_at` DATETIME    DEFAULT NULL,
  -- last_status stores a short result message for that check.
  `last_status`  VARCHAR(255) DEFAULT NULL,
  -- Makes sync_key the primary key.
  PRIMARY KEY (`sync_key`)
-- Uses InnoDB and utf8mb4 for the table.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Explains that seminar rows used the wrong folder name.
-- The seminar rows were saved with the folder name "SEMINAR" while the files
-- So six downloads were dead links.
-- live in "SEMINAR PAPER", so those six downloads were dead links.
-- Updates the templates table to fix those paths.
UPDATE `templates`
   -- Replaces the old SEMINAR folder with SEMINAR PAPER.
   SET `file_path` = REPLACE(`file_path`, 'assets/papers/SEMINAR/', 'assets/papers/SEMINAR PAPER/')
 -- Only fixes rows whose path starts with the old folder.
 WHERE `file_path` LIKE 'assets/papers/SEMINAR/%';
