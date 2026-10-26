-- Comment banner that opens the header of this migration.
-- ============================================================================
-- Names the migration that adds alphabetical views.
-- Migration: Alphabetical database views for users and students
-- Shows the date of this migration.
-- Date: 2026-09-30
-- Line break between the title and the explanation.
--
-- Notes tables have no fixed row order without ORDER BY.
-- Relational tables have no inherent row order — alphabetical retrieval is
-- Says these views build the ordering into the database.
-- only guaranteed by ORDER BY. These views bake the ordering into the
-- Says results sort by last name, then first name.
-- database itself: last name first, first name as tiebreak.
-- Notes the large limit keeps the order inside the view.
-- (The high LIMIT preserves the view's ORDER BY under MariaDB/MySQL, which
-- Explains MySQL and MariaDB otherwise ignore that order.
-- otherwise ignores ORDER BY inside a view body.)
-- Closes the header comment banner.
-- ============================================================================

-- Creates or replaces the alphabetical students view.
CREATE OR REPLACE VIEW `v_students_alpha` AS
-- Selects the student id column.
SELECT `student_id`,
       -- Selects the linked user id column.
       `user_id`,
       -- Selects the first name column.
       `first_name`,
       -- Selects the last name column.
       `last_name`,
       -- Selects the middle initial column.
       `middle_initial`,
       -- Starts building the display name as last name, first name.
       TRIM(CONCAT(`last_name`, ', ', `first_name`,
            -- Checks whether the middle initial is missing or empty.
            IF(`middle_initial` IS NULL OR `middle_initial` = '',
               -- Adds nothing when empty, else a space plus the initial, and names it display_name.
               '', CONCAT(' ', `middle_initial`)))) AS `display_name`,
       -- Selects the age column.
       `age`,
       -- Selects the gender column.
       `gender`,
       -- Selects the program column.
       `program`,
       -- Selects the track column.
       `track`,
       -- Selects the adviser name column.
       `adviser_name`,
       -- Selects the enrollment date column.
       `enrollment_date`,
       -- Selects the archive timestamp column.
       `archived_at`
-- Reads rows from the students table.
FROM `students`
-- Sorts rows by last name and then first name.
ORDER BY `last_name` ASC, `first_name` ASC
-- Huge limit that keeps the view ORDER BY working.
LIMIT 18446744073709551615;

-- Creates or replaces the alphabetical users view.
CREATE OR REPLACE VIEW `v_users_alpha` AS
-- Selects the user id column.
SELECT `user_id`,
       -- Selects the full name column.
       `full_name`,
       -- Selects the email column.
       `email`,
       -- Selects the role column.
       `role`,
       -- Selects the account creation date.
       `created_at`,
       -- Pulls the last name as the text before the first comma.
       SUBSTRING_INDEX(`full_name`, ',', 1) AS `last_name`,
       -- Pulls the first name as the text after that comma.
       TRIM(SUBSTRING_INDEX(`full_name`, ',', -1)) AS `first_name`
-- Reads rows from the users table.
FROM `users`
-- Sorts by the last name part of full_name.
ORDER BY SUBSTRING_INDEX(`full_name`, ',', 1) ASC,
         -- Then sorts by the first name part as a tiebreak.
         SUBSTRING_INDEX(`full_name`, ',', -1) ASC
-- Huge limit that keeps the view ORDER BY working.
LIMIT 18446744073709551615;
