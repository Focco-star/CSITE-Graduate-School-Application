-- --------------------------------------------------------
-- Student account validation
--
-- New student accounts start as `pending` and cannot sign in until the
-- coordinator approves them. Existing accounts are marked `approved`, so no
-- current student loses access after this migration is applied.
-- --------------------------------------------------------

ALTER TABLE `users`
  ADD COLUMN `account_status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved' AFTER `role`,
  ADD COLUMN `account_reviewed_at` DATETIME NULL DEFAULT NULL AFTER `account_status`,
  ADD COLUMN `account_reviewed_by` INT(11) NULL DEFAULT NULL AFTER `account_reviewed_at`,
  ADD KEY `idx_users_account_status` (`account_status`);
