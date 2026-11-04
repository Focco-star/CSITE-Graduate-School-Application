-- --------------------------------------------------------
-- Comment removal tracking
--
-- Coordinator comments are never deleted. These columns record when a
-- coordinator removes a comment and who did it, so the coordinator history can
-- show the removal while the student stops seeing the comment.
-- --------------------------------------------------------

ALTER TABLE `application_comments`
  ADD COLUMN `coordinator_removed_at` DATETIME NULL DEFAULT NULL AFTER `student_dismissed_at`,
  ADD COLUMN `coordinator_removed_by` INT(11) NULL DEFAULT NULL AFTER `coordinator_removed_at`;
