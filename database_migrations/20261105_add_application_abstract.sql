-- --------------------------------------------------------
-- Application Brief Description / Abstract
--
-- The student enters the Brief Description / Abstract on the final stage of
-- their track. It is stored on the stage application so the coordinator can
-- read it from the final paper document in the milestone tracker.
-- --------------------------------------------------------

ALTER TABLE `applications`
  ADD COLUMN `abstract` TEXT NULL DEFAULT NULL AFTER `paper_title`;
