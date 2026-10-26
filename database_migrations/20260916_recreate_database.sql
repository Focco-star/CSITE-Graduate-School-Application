-- Notes this is an approved drop and recreate of the database.
-- User-approved: drop and recreate the csite_grad_school database.
-- Explains the InnoDB system tablespace holds an orphaned applications entry.
-- The InnoDB system tablespace holds an orphaned dictionary entry for
-- Says DROP TABLE cannot purge the entry because its file is missing.
-- `applications` that cannot be purged by DROP TABLE (the .ibd is missing and
-- Notes InnoDB recreates a placeholder at every server start.
-- InnoDB recreates a placeholder at every start). Full mysqldump backups of
-- Says full backups of the current data were taken first.
-- the current data were taken first:
-- Names the data-only backup file.
--   db_backup/csite_grad_school_data_only.sql
-- Names the full backup file.
--   db_backup/csite_grad_school_full.sql
-- Notes that the updated SQL dump must be re-imported afterwards.
-- After this file runs, re-import csite_grad_school.sql (the updated dump).
-- Drops the csite_grad_school database if it exists.
DROP DATABASE IF EXISTS `csite_grad_school`;
-- Creates the empty database with utf8mb4 encoding.
CREATE DATABASE `csite_grad_school` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
