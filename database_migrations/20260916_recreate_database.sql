-- User-approved: drop and recreate the csite_grad_school database.
-- The InnoDB system tablespace holds an orphaned dictionary entry for
-- `applications` that cannot be purged by DROP TABLE (the .ibd is missing and
-- InnoDB recreates a placeholder at every start). Full mysqldump backups of
-- the current data were taken first:
--   db_backup/csite_grad_school_data_only.sql
--   db_backup/csite_grad_school_full.sql
-- After this file runs, re-import csite_grad_school.sql (the updated dump).
DROP DATABASE IF EXISTS `csite_grad_school`;
CREATE DATABASE `csite_grad_school` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
