-- Explains that this approved repair removes stale statistics rows.
-- User-approved repair: remove the stale InnoDB statistics rows for the
-- Names the orphaned applications table being cleaned up.
-- orphaned `applications` table so `applications_rebuild` can be renamed over
-- Says the rows live only in statistics tables, not in real data.
-- it. These rows exist only in the statistics tables; there is no table data
-- Notes no table data is lost because the table file was missing.
-- to lose (the applications.ibd was already confirmed missing/orphaned).
-- Deletes the matching row from the InnoDB table statistics.
DELETE FROM mysql.innodb_table_stats WHERE database_name='csite_grad_school' AND table_name='applications';
-- Deletes the matching rows from the InnoDB index statistics.
DELETE FROM mysql.innodb_index_stats WHERE database_name='csite_grad_school' AND table_name='applications';
