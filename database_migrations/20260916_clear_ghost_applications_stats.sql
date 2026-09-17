-- User-approved repair: remove the stale InnoDB statistics rows for the
-- orphaned `applications` table so `applications_rebuild` can be renamed over
-- it. These rows exist only in the statistics tables; there is no table data
-- to lose (the applications.ibd was already confirmed missing/orphaned).
DELETE FROM mysql.innodb_table_stats WHERE database_name='csite_grad_school' AND table_name='applications';
DELETE FROM mysql.innodb_index_stats WHERE database_name='csite_grad_school' AND table_name='applications';
