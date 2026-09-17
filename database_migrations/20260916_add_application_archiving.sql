-- Apply this migration to existing csite_grad_school databases.
-- Adds soft-delete (archive) support to the applications table, mirroring
-- the existing students.archived_at column. Archiving an application through
-- the coordinator UI only flags the row; it is never physically deleted.
ALTER TABLE `applications` ADD COLUMN `archived_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`;
