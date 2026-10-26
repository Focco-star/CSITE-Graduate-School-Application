-- Notes that this script only upgrades databases built by the earlier schema.
-- Run only when upgrading a database created by the earlier schema.
-- Notes that fresh installs already create advisor_pool from the main SQL dump.
-- Fresh installations use advisor_pool directly from csite_grad_school.sql.
-- Renames the old adviser_pool table to the corrected advisor_pool name.
RENAME TABLE `adviser_pool` TO `advisor_pool`;
