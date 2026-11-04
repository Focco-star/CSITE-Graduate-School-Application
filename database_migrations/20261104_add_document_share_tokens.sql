-- --------------------------------------------------------
-- Shareable document links
--
-- Each uploaded document can carry one random share token. The coordinator
-- copies a link that includes the token. The link opens only while the visitor
-- is signed in as a coordinator or admin, and it points to exactly one document.
-- The token is created the first time the document link is built.
-- --------------------------------------------------------

ALTER TABLE `application_documents`
  ADD COLUMN `share_token` VARCHAR(64) NULL DEFAULT NULL AFTER `stored_name`,
  ADD UNIQUE KEY `uniq_application_documents_share_token` (`share_token`);
