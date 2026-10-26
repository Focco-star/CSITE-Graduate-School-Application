-- Notes that this migration is for existing csite_grad_school databases.
-- Apply this migration to existing csite_grad_school databases.
-- Explains that the change adds archive, or soft delete, support.
-- Adds soft-delete (archive) support to the applications table, mirroring
-- Notes it mirrors the archived_at column already used on students.
-- the existing students.archived_at column. Archiving an application through
-- Says archiving only flags the row and never deletes it.
-- the coordinator UI only flags the row; it is never physically deleted.
-- Adds the nullable archived_at timestamp column to applications.
ALTER TABLE `applications` ADD COLUMN `archived_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`;
