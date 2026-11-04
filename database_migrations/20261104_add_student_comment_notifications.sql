-- --------------------------------------------------------
-- Student comment notifications
--
-- Coordinator comments already live in `application_comments`. This column
-- records when the student dismisses the notification, so the comment stays in
-- the coordinator history but disappears from the student's milestone tracker.
-- --------------------------------------------------------

ALTER TABLE `application_comments`
  ADD COLUMN `student_dismissed_at` DATETIME NULL DEFAULT NULL AFTER `created_at`;
