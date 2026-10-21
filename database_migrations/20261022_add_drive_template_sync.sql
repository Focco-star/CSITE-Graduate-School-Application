-- Migration: Google Drive template sync and update notifications (2026-10-22)
--
-- The teacher keeps the official forms and templates in a Google Drive
-- folder. The portal mirrors that folder so that a new or a replaced file
-- is available to the coordinator and to the students straight away, and
-- so that the Templates menu can show a badge when something changed.
--
-- templates:
--   drive_file_id       the Google Drive id of the file, NULL for templates
--                       that were uploaded through the portal itself
--   drive_modified_at   the "last modified" time Drive reported, used to
--                       recognise the same version of a file
--   drive_size          the size of the file, a second change signal
--
-- template_notifications:
--   one row per changed file. The unique key on (drive_file_id, changed_at)
--   is what stops the same version of a file from being announced twice.
--   Each portal tracks its own "seen" time, so the coordinator and the
--   students each clear their own badge.
--
-- template_sync_state:
--   when Google Drive was checked last, and how that went.

ALTER TABLE `templates`
  ADD COLUMN `drive_file_id` VARCHAR(120) DEFAULT NULL AFTER `file_path`,
  ADD COLUMN `drive_modified_at` DATETIME DEFAULT NULL AFTER `drive_file_id`,
  ADD COLUMN `drive_size` BIGINT DEFAULT NULL AFTER `drive_modified_at`;

CREATE TABLE IF NOT EXISTS `template_notifications` (
  `notification_id`     INT(11)      NOT NULL AUTO_INCREMENT,
  `template_id`         INT(11)      DEFAULT NULL,
  `drive_file_id`       VARCHAR(120) NOT NULL,
  `file_name`           VARCHAR(255) NOT NULL,
  `change_type`         ENUM('added','updated') NOT NULL DEFAULT 'updated',
  `changed_at`          DATETIME     NOT NULL,
  `track_code`          VARCHAR(40)  DEFAULT NULL,
  `seen_student_at`     DATETIME     DEFAULT NULL,
  `seen_coordinator_at` DATETIME     DEFAULT NULL,
  PRIMARY KEY (`notification_id`),
  UNIQUE KEY `uq_template_notification` (`drive_file_id`, `changed_at`),
  KEY `idx_template_notifications_track` (`track_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `template_sync_state` (
  `sync_key`     VARCHAR(60) NOT NULL,
  `last_sync_at` DATETIME    DEFAULT NULL,
  `last_status`  VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`sync_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The seminar rows were saved with the folder name "SEMINAR" while the files
-- live in "SEMINAR PAPER", so those six downloads were dead links.
UPDATE `templates`
   SET `file_path` = REPLACE(`file_path`, 'assets/papers/SEMINAR/', 'assets/papers/SEMINAR PAPER/')
 WHERE `file_path` LIKE 'assets/papers/SEMINAR/%';
