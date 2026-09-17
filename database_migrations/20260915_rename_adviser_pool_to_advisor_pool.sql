-- Run only when upgrading a database created by the earlier schema.
-- Fresh installations use advisor_pool directly from csite_grad_school.sql.
RENAME TABLE `adviser_pool` TO `advisor_pool`;
