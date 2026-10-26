-- Notes that this is an approved cleanup of temporary ghost tables.
-- User-approved cleanup of the temporary/ghost application tables.
-- Says it only runs while InnoDB force recovery level 6 is enabled.
-- Run ONLY while MariaDB is started with --innodb-force-recovery=6, which
-- Explains that mode stops InnoDB re-registering the ghost tablespace.
-- stops InnoDB from re-registering the ghost `applications` tablespace at
-- Says the drops then remove the stale dictionary entry cleanly.
-- startup, so these DROP statements remove the stale dictionary entry cleanly.
-- Drops the ghost applications table if it still exists.
DROP TABLE IF EXISTS `applications`;
-- Drops the temporary probe table if it still exists.
DROP TABLE IF EXISTS `applications_probe2`;
-- Drops the temporary rebuild table if it still exists.
DROP TABLE IF EXISTS `applications_rebuild`;
