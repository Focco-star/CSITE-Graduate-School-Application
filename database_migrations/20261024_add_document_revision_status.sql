-- Migration: the revision action on a document (2026-10-24)
--
-- The coordinator can now mark a document four ways:
--   submitted   the student sent it, it waits for the review
--   verified    the coordinator approved it
--   incomplete  something in it has to be fixed
--   revision    a new version of it is needed
--
-- The status of the document is what the Milestone Tracker of the student
-- follows, so the two portals always show the same thing.

ALTER TABLE `application_documents`
  MODIFY `status` ENUM('submitted','verified','incomplete','revision') NOT NULL DEFAULT 'submitted';
