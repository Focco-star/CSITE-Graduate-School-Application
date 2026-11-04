-- --------------------------------------------------------
-- Shareable document links
--
-- Each uploaded document can carry one random share token. The coordinator
-- copies a link that includes the token, and anyone with that link can open
-- only that one document without signing in. The token is created the first
-- time the Copy Link button is used.
-- --------------------------------------------------------

ALTER TABLE `application_documents`
  ADD COLUMN `share_token` VARCHAR(64) NULL DEFAULT NULL AFTER `stored_name`,
  ADD UNIQUE KEY `uniq_application_documents_share_token` (`share_token`);
