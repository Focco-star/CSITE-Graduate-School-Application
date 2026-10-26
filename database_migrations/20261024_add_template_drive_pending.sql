-- Names the migration and date for files waiting to be synchronized.
-- Migration: files waiting to be synchronized (2026-10-24)
-- Divider line separating the title from the explanation.
--
-- Says the coordinator must be told Drive holds a new template.
-- The coordinator has to be told that Google Drive holds a template that is
-- That happens before anything is downloaded.
-- not in the system yet, before anything is downloaded. This table keeps
-- This table keeps that waiting list.
-- that list.
-- Ends the summary.
--
-- Says rows are written when Google Drive is checked.
-- The rows are written when Google Drive is looked at and are taken off
-- And removed again by the synchronisation.
-- again by the synchronisation, so a file that was not applied stays on the
-- So a file that was not applied stays on the list.
-- list and the coordinator is still told about it.
-- And the coordinator is still told about it.
--
-- Notes the primary key is the Google Drive file id.
-- The primary key is the Google Drive file id, so a file can never be
-- So a file can never be counted twice.
-- counted twice.

-- Creates the template_drive_pending table if needed.
CREATE TABLE IF NOT EXISTS `template_drive_pending` (
  -- drive_file_id is the Drive id and the primary key.
  `drive_file_id`     VARCHAR(120) NOT NULL,
  -- file_name stores the name of the waiting file.
  `file_name`         VARCHAR(255) NOT NULL,
  -- change_type marks it as added or updated.
  `change_type`       ENUM('added','updated') NOT NULL DEFAULT 'updated',
  -- track_code stores the related track code, if any.
  `track_code`        VARCHAR(40)  DEFAULT NULL,
  -- drive_modified_at stores the Drive last modified time.
  `drive_modified_at` DATETIME     DEFAULT NULL,
  -- drive_size stores the file size in bytes.
  `drive_size`        BIGINT       DEFAULT NULL,
  -- detected_at stores when the portal noticed the file.
  `detected_at`       DATETIME     NOT NULL,
  -- Makes drive_file_id the primary key.
  PRIMARY KEY (`drive_file_id`),
  -- Adds an index for filtering pending files by track.
  KEY `idx_template_drive_pending_track` (`track_code`)
-- Uses InnoDB and utf8mb4 for the table.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
