-- ============================================================================
-- Migration: Alphabetical database views for users and students
-- Date: 2026-09-30
--
-- Relational tables have no inherent row order — alphabetical retrieval is
-- only guaranteed by ORDER BY. These views bake the ordering into the
-- database itself: last name first, first name as tiebreak.
-- (The high LIMIT preserves the view's ORDER BY under MariaDB/MySQL, which
-- otherwise ignores ORDER BY inside a view body.)
-- ============================================================================

CREATE OR REPLACE VIEW `v_students_alpha` AS
SELECT `student_id`,
       `user_id`,
       `first_name`,
       `last_name`,
       `middle_initial`,
       TRIM(CONCAT(`last_name`, ', ', `first_name`,
            IF(`middle_initial` IS NULL OR `middle_initial` = '',
               '', CONCAT(' ', `middle_initial`)))) AS `display_name`,
       `age`,
       `gender`,
       `program`,
       `track`,
       `adviser_name`,
       `enrollment_date`,
       `archived_at`
FROM `students`
ORDER BY `last_name` ASC, `first_name` ASC
LIMIT 18446744073709551615;

CREATE OR REPLACE VIEW `v_users_alpha` AS
SELECT `user_id`,
       `full_name`,
       `email`,
       `role`,
       `created_at`,
       SUBSTRING_INDEX(`full_name`, ',', 1) AS `last_name`,
       TRIM(SUBSTRING_INDEX(`full_name`, ',', -1)) AS `first_name`
FROM `users`
ORDER BY SUBSTRING_INDEX(`full_name`, ',', 1) ASC,
         SUBSTRING_INDEX(`full_name`, ',', -1) ASC
LIMIT 18446744073709551615;
