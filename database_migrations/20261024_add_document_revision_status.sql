-- Names the migration and date for the document revision action.
-- Migration: the revision action on a document (2026-10-24)
-- Divider line separating the title from the explanation.
--
-- Says the coordinator can now mark a document four ways.
-- The coordinator can now mark a document four ways:
-- Submitted means the student sent it and it waits for review.
--   submitted   the student sent it, it waits for the review
-- Verified means the coordinator approved it.
--   verified    the coordinator approved it
-- Incomplete means something in it has to be fixed.
--   incomplete  something in it has to be fixed
-- Revision means a new version of the document is needed.
--   revision    a new version of it is needed
-- Ends the list of document statuses.
--
-- Says the document status is what the student tracker follows.
-- The status of the document is what the Milestone Tracker of the student
-- So both portals always show the same status.
-- follows, so the two portals always show the same thing.

-- Changes the application_documents table.
ALTER TABLE `application_documents`
  -- Redefines the status ENUM with the four allowed values.
  MODIFY `status` ENUM('submitted','verified','incomplete','revision') NOT NULL DEFAULT 'submitted';
