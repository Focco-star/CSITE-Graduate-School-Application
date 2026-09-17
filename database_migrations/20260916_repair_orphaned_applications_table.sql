-- =============================================================================
-- 20260916_repair_orphaned_applications_table.sql
-- -----------------------------------------------------------------------------
-- Purpose (user-approved):
--   The live `csite_grad_school` database has an orphaned InnoDB entry for the
--   `applications` table: its data file (applications.ibd) and .frm are missing,
--   but the InnoDB data dictionary still lists the name. As a result:
--     * SHOW TABLES does not list it,
--     * SELECT/CREATE both fail (1146 doesn't exist / 1050 already exists).
--   The official fix is to DROP the ghost entry (removes only the stale
--   dictionary row; there is no data file to lose) and re-create the table
--   from the updated schema dump.
--
-- Run order:
--   1. mysql -u root csite_grad_school < 20260916_repair_orphaned_applications_table.sql
--   2. mysql -u root csite_grad_school < 20260916_add_application_workflow_fields.sql
--   3. Re-import the seed rows (or run csite_grad_school.sql on a fresh DB).
-- =============================================================================

DROP TABLE IF EXISTS `applications`;

-- The table is recreated by importing the updated csite_grad_school.sql
-- (which now carries the full workflow columns). On this machine the seed
-- INSERTs are applied from that same file.
