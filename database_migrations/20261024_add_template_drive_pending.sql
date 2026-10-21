-- Migration: files waiting to be synchronized (2026-10-24)
--
-- The coordinator has to be told that Google Drive holds a template that is
-- not in the system yet, before anything is downloaded. This table keeps
-- that list.
--
-- The rows are written when Google Drive is looked at and are taken off
-- again by the synchronisation, so a file that was not applied stays on the
-- list and the coordinator is still told about it.
--
-- The primary key is the Google Drive file id, so a file can never be
-- counted twice.

CREATE TABLE IF NOT EXISTS `template_drive_pending` (
  `drive_file_id`     VARCHAR(120) NOT NULL,
  `file_name`         VARCHAR(255) NOT NULL,
  `change_type`       ENUM('added','updated') NOT NULL DEFAULT 'updated',
  `track_code`        VARCHAR(40)  DEFAULT NULL,
  `drive_modified_at` DATETIME     DEFAULT NULL,
  `drive_size`        BIGINT       DEFAULT NULL,
  `detected_at`       DATETIME     NOT NULL,
  PRIMARY KEY (`drive_file_id`),
  KEY `idx_template_drive_pending_track` (`track_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
