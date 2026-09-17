-- User-approved cleanup of the temporary/ghost application tables.
-- Run ONLY while MariaDB is started with --innodb-force-recovery=6, which
-- stops InnoDB from re-registering the ghost `applications` tablespace at
-- startup, so these DROP statements remove the stale dictionary entry cleanly.
DROP TABLE IF EXISTS `applications`;
DROP TABLE IF EXISTS `applications_probe2`;
DROP TABLE IF EXISTS `applications_rebuild`;
