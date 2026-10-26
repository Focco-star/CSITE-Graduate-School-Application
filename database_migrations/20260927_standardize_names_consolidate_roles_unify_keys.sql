-- Comment banner that opens the header of this migration.
-- ============================================================================
-- Summarizes the migration: standard names, roles, keys, and ids.
-- Migration: Standardize names, consolidate roles, unify keys, automate ID
-- Shows the date of this migration.
-- Date: 2026-09-27
-- Line break between the title and the numbered goals.
--
-- First goal: standardize names to last name, first name, middle initial.
-- 1) Standardize Name Formats: [Last Name], [First Name] [Middle Initial]
-- Notes results are sorted alphabetically using the indexes below.
--    + alphabetical retrieval (ORDER BY applied in queries / indexes below).
-- Second goal: consolidate roles by dropping the coordinators table.
-- 2) Consolidate Roles: drop dedicated `coordinators` table (role lives in
-- Says the coordinator role now lives only in users.role.
--    `users.role = 'coordinator'`).
-- Third goal: unify keys by replacing student_id with user_id.
-- 3) Unify Key Relations: applications.student_id -> applications.user_id,
-- Applies the same key change to application_documents.
--    application_documents.student_id -> application_documents.user_id,
-- Both foreign keys now point at users.user_id.
--    both FK -> users(user_id).
-- Fourth goal: extract student numbers from ADZU email digits.
-- 4) Automate Student ID Extraction: backfill students.student_number from the
-- Shows an example email that maps to student number 240255.
--    numeric characters of the ADZU email local part (e.g. co240255@... -> 240255)
-- Notes triggers will do this automatically for new accounts.
--    and add triggers to do it automatically on account creation.
-- Closes the header comment banner.
-- ============================================================================

-- Starts the section divider for name standardization.
-- --------------------------------------------------------------------------
-- Names section 1: standardize name formats.
-- 1) STANDARDIZE NAME FORMATS
-- Closes the section divider.
-- --------------------------------------------------------------------------

-- Explains that stored middle initials get cleaned up.
-- Normalize stored middle initials ('.' / blank -> NULL-safe empty).
-- Updates the students table.
UPDATE `students`
-- Strips dots from middle initials and converts blanks to null.
SET `middle_initial` = NULLIF(TRIM(REPLACE(`middle_initial`, '.', '')), '')
-- Only touches rows that have a middle initial.
WHERE `middle_initial` IS NOT NULL;

-- Explains that every student display name is rebuilt.
-- Backfill every student user display name to "Last, First MI".
-- Updates users and aliases the table as u.
UPDATE `users` u
-- Joins each user to the student row linked to it.
INNER JOIN `students` s ON s.`user_id` = u.`user_id`
-- Starts building the full name in Last, First MI order.
SET u.`full_name` = TRIM(CONCAT(
  -- Puts the last name, a comma, and the first name.
  s.`last_name`, ', ', s.`first_name`,
  -- Checks whether the middle initial is missing or empty.
  IF(s.`middle_initial` IS NULL OR s.`middle_initial` = '',
     -- Adds a space plus the initial, or nothing when it is empty.
     '', CONCAT(' ', s.`middle_initial`))
-- Closes the name-building expression.
))
-- Only updates accounts that have the student role.
WHERE u.`role` = 'student';

-- Says the single coordinator account is formatted the same way.
-- Standardize the single coordinator account the same way ("Opinion, Precious").
-- Updates the users table for the coordinator account.
UPDATE `users`
-- Sets the coordinator name to Opinion, Precious.
SET `full_name` = 'Opinion, Precious'
-- Matches the coordinator by email address and role.
WHERE `email` = 'gpc-csite@adzu.edu.ph' AND `role` = 'coordinator';

-- Notes the triggers are replaced to keep the new format.
-- Replace sync triggers so every future write keeps the canonical format.
-- Drops the old insert trigger before recreating it.
DROP TRIGGER IF EXISTS `students_after_insert_sync_user`;
-- Drops the old update trigger before recreating it.
DROP TRIGGER IF EXISTS `students_after_update_sync_user`;

-- Switches the delimiter so the trigger body can use semicolons.
DELIMITER $$
-- Creates the trigger that runs after a student row is inserted.
CREATE TRIGGER `students_after_insert_sync_user` AFTER INSERT ON `students` FOR EACH ROW
-- Opens the trigger body block.
BEGIN
  -- Updates the users table from inside the trigger.
  UPDATE `users`
  -- Builds the name in Last, First MI order from the NEW row.
  SET `full_name` = TRIM(CONCAT(
    -- Puts the new last name, a comma, and the first name.
    NEW.`last_name`, ', ', NEW.`first_name`,
    -- Checks whether the new middle initial is missing or blank.
    IF(NEW.`middle_initial` IS NULL OR TRIM(REPLACE(NEW.`middle_initial`, '.', '')) = '',
       -- Adds the cleaned middle initial when it exists.
       '', CONCAT(' ', TRIM(REPLACE(NEW.`middle_initial`, '.', ''))))
  -- Closes the name-building expression.
  ))
  -- Updates only the account that owns the new student row.
  WHERE `user_id` = NEW.`user_id`;
-- Ends the trigger body.
END$$
-- Restores the normal semicolon delimiter.
DELIMITER ;
-- Switches the delimiter for the update trigger.
DELIMITER $$
-- Creates the trigger that runs after a student row is updated.
CREATE TRIGGER `students_after_update_sync_user` AFTER UPDATE ON `students` FOR EACH ROW
-- Opens the trigger body block.
BEGIN
  -- Updates the users table from inside the trigger.
  UPDATE `users`
  -- Builds the name from the updated NEW row values.
  SET `full_name` = TRIM(CONCAT(
    -- Puts the updated last name, a comma, and the first name.
    NEW.`last_name`, ', ', NEW.`first_name`,
    -- Checks whether the updated middle initial is missing or blank.
    IF(NEW.`middle_initial` IS NULL OR TRIM(REPLACE(NEW.`middle_initial`, '.', '')) = '',
       -- Adds the cleaned middle initial when it exists.
       '', CONCAT(' ', TRIM(REPLACE(NEW.`middle_initial`, '.', ''))))
  -- Closes the name-building expression.
  ))
  -- Updates only the account that owns the updated student row.
  WHERE `user_id` = NEW.`user_id`;
-- Ends the trigger body.
END$$
-- Restores the normal semicolon delimiter.
DELIMITER ;

-- Explains the indexes that speed up alphabetical listing.
-- Index to support alphabetical retrieval (Last, First).
-- Adds an index on student last name and first name.
CREATE INDEX IF NOT EXISTS `idx_students_alpha` ON `students` (`last_name`, `first_name`);
-- Adds an index on the users full_name column.
CREATE INDEX IF NOT EXISTS `idx_users_full_name` ON `users` (`full_name`);

-- Starts the section divider for student id automation.
-- --------------------------------------------------------------------------
-- Names section 4: build student ids from email digits.
-- 4) AUTOMATE STUDENT ID EXTRACTION (backfill existing rows first)
-- Closes the section divider.
-- --------------------------------------------------------------------------
-- Explains that the student id is the numeric part of the email.
-- Official student ID = numeric characters of the email local part.
-- Gives the email example that maps to number 240255.
-- e.g. co240255@adzu.edu.ph -> 240255. Only overwrites NULL/empty/CSITE-* values
-- Says only null, empty, or CSITE- values are overwritten.
-- so manually assigned IDs are never clobbered.

-- Updates the students table and aliases it as s.
UPDATE `students` s
-- Joins each student to the user account that owns it.
INNER JOIN `users` u ON u.`user_id` = s.`user_id`
-- Sets student_number to digits taken from the email name part.
SET s.`student_number` = REGEXP_REPLACE(SUBSTRING_INDEX(u.`email`, '@', 1), '[^0-9]', '')
-- Only fills numbers that are null, empty, or a temporary CSITE id.
WHERE (s.`student_number` IS NULL OR s.`student_number` = '' OR s.`student_number` LIKE 'CSITE-%')
  -- Skips emails that contain no digits at all.
  AND REGEXP_REPLACE(SUBSTRING_INDEX(u.`email`, '@', 1), '[^0-9]', '') <> '';

-- Says new accounts also get an id and number automatically.
-- Auto-generate the official student ID + number on account creation when the
-- Notes the official id is the email number, such as 259344.
-- caller leaves them blank. Official ID = numeric part of the ADZU email
-- Gives another email example that becomes student_id 259344.
-- (e.g. co259344@adzu.edu.ph -> student_id 259344).
-- Drops the old auto-id trigger before recreating it.
DROP TRIGGER IF EXISTS `students_before_insert_autoid`;
-- Switches the delimiter so the trigger body can use semicolons.
DELIMITER $$
-- Creates the trigger that runs before a student row is inserted.
CREATE TRIGGER `students_before_insert_autoid` BEFORE INSERT ON `students` FOR EACH ROW
-- Opens the trigger body block.
BEGIN
  -- Declares a variable to hold the account email.
  DECLARE v_email VARCHAR(100) DEFAULT '';
  -- Declares a variable to hold the digits found in the email.
  DECLARE v_digits VARCHAR(20) DEFAULT '';
  -- Declares a counter to check whether an id is already taken.
  DECLARE v_taken INT DEFAULT 0;
  -- Reads the email of the user that owns the new student row.
  SELECT `email` INTO v_email FROM `users` WHERE `user_id` = NEW.`user_id` LIMIT 1;
  -- Only runs the extraction when an email was found.
  IF (v_email IS NOT NULL AND v_email <> '') THEN
    -- Extracts digits from the part of the email before the at sign.
    SET v_digits = REGEXP_REPLACE(SUBSTRING_INDEX(v_email, '@', 1), '[^0-9]', '');
  -- Ends the email check block.
  END IF;
  -- Only continues when some digits were actually found.
  IF (v_digits IS NOT NULL AND v_digits <> '') THEN
    -- Counts students already using those digits as their id.
    SELECT COUNT(*) INTO v_taken FROM `students` WHERE `student_id` = CAST(v_digits AS UNSIGNED);
    -- Checks that no other student already has this id.
    IF (v_taken = 0) THEN
      -- Assigns the email digits as the new student_id.
      SET NEW.`student_id` = CAST(v_digits AS UNSIGNED);
    -- Ends the id availability check.
    END IF;
    -- Checks whether the student number was left blank.
    IF (NEW.`student_number` IS NULL OR NEW.`student_number` = '') THEN
      -- Counts students already using those digits as a number.
      SELECT COUNT(*) INTO v_taken FROM `students` WHERE `student_number` = v_digits;
      -- Checks that the number is still free.
      IF (v_taken = 0) THEN
        -- Assigns the email digits as the student_number.
        SET NEW.`student_number` = v_digits;
      -- Ends the number availability check.
      END IF;
    -- Ends the size check for the student number.
    END IF;
  -- Ends the digits check block.
  END IF;
  -- Notes that the middle initial is cleaned here too.
  -- Keep middle initial clean (".." / "." -> empty).
  -- Only cleans the middle initial when it is not null.
  IF (NEW.`middle_initial` IS NOT NULL) THEN
    -- Strips dots from the middle initial and turns blanks into null.
    SET NEW.`middle_initial` = NULLIF(TRIM(REPLACE(NEW.`middle_initial`, '.', '')), '');
  -- Ends the middle initial block.
  END IF;
-- Ends the trigger body.
END$$
-- Restores the normal semicolon delimiter.
DELIMITER ;

-- Starts the section divider for role consolidation.
-- --------------------------------------------------------------------------
-- Names section 2: remove the dedicated coordinator table.
-- 2) CONSOLIDATE ROLES: remove dedicated coordinator table
-- Closes the section divider.
-- --------------------------------------------------------------------------
-- Explains coordinators already exist as users with that role.
-- Coordinators are already rows in `users` with role='coordinator', so the
-- Says the side table is redundant and its data lives in users.
-- side table is redundant. Data is preserved implicitly via users.
-- Drops the coordinators table if it still exists.
DROP TABLE IF EXISTS `coordinators`;

-- Starts the section divider for unifying key relations.
-- --------------------------------------------------------------------------
-- Names section 3: change student_id keys to user_id keys.
-- 3) UNIFY KEY RELATIONS: student_id -> user_id on applications + documents
-- Closes the section divider.
-- --------------------------------------------------------------------------

-- Opens the applications part of the key change.
-- ---- applications ---------------------------------------------------------
-- Adds a nullable user_id column after application_id.
ALTER TABLE `applications` ADD COLUMN IF NOT EXISTS `user_id` INT(11) NULL AFTER `application_id`;

-- Explains the backfill of user_id from each linked student.
-- Backfill user_id from the linked student row (only path to users).
-- Updates applications and aliases the table as a.
UPDATE `applications` a
-- Joins each application to its student row.
INNER JOIN `students` s ON s.`student_id` = a.`student_id`
-- Copies the student user_id into the application user_id.
SET a.`user_id` = s.`user_id`
-- Only fills rows where user_id is still null.
WHERE a.`user_id` IS NULL;

-- Explains that orphaned applications keep null and are removed.
-- Orphan guard: any application whose student row is gone keeps NULL and is
-- Notes this lets the not-null rule and foreign key apply cleanly.
-- removed so the new NOT NULL + FK can be applied cleanly.
-- Deletes applications that have no resolvable user.
DELETE FROM `applications` WHERE `user_id` IS NULL;

-- Adds an index on applications.user_id for faster lookups.
ALTER TABLE `applications` ADD INDEX IF NOT EXISTS `idx_applications_user_id` (`user_id`);

-- Explains that the old foreign key and index get dropped.
-- Drop old FK + index when they exist (statements are idempotent on a fresh
-- Notes the old schema exists on a fresh import.
-- import where the old schema is present; ignore errors on re-run).
-- Notes that MySQL has no drop foreign key if exists.
-- NOTE: MySQL/MariaDB has no IF EXISTS for DROP FOREIGN KEY, so these two
-- Says warnings on a re-run are safe to ignore.
-- lines may warn on re-run — safe to ignore.
-- Drops the old student foreign key on applications.
ALTER TABLE `applications` DROP FOREIGN KEY `applications_ibfk_1`;
-- Drops the old index on the student_id column.
ALTER TABLE `applications` DROP INDEX `student_id`;
-- Drops the student_id column from applications.
ALTER TABLE `applications` DROP COLUMN `student_id`;
-- Makes user_id required so every application has an owner.
ALTER TABLE `applications` MODIFY `user_id` INT(11) NOT NULL;
-- Starts adding the new user foreign key.
ALTER TABLE `applications`
  -- Links applications.user_id to users and deletes rows with the user.
  ADD CONSTRAINT `applications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

-- Opens the application_documents part of the key change.
-- ---- application_documents ------------------------------------------------
-- Adds a nullable user_id column after application_id.
ALTER TABLE `application_documents` ADD COLUMN IF NOT EXISTS `user_id` INT(11) NULL AFTER `application_id`;

-- Updates application_documents and aliases it as d.
UPDATE `application_documents` d
-- Joins each document to its student row.
INNER JOIN `students` s ON s.`student_id` = d.`student_id`
-- Copies the student user_id into the document user_id.
SET d.`user_id` = s.`user_id`
-- Only fills rows where user_id is still null.
WHERE d.`user_id` IS NULL;

-- Explains that documents without a user cannot satisfy the new key.
-- Documents with no resolvable user cannot satisfy the new FK; drop them
-- Notes they are dropped instead of left orphaned.
-- rather than leaving them orphaned.
-- Deletes documents that have no resolvable user.
DELETE FROM `application_documents` WHERE `user_id` IS NULL;

-- Adds an index on application_documents.user_id.
ALTER TABLE `application_documents` ADD INDEX IF NOT EXISTS `idx_application_documents_user_id` (`user_id`);

-- Drops the old student foreign key on application_documents.
ALTER TABLE `application_documents` DROP FOREIGN KEY `application_documents_student_fk`;
-- Drops the old index on the student_id column.
ALTER TABLE `application_documents` DROP INDEX `application_documents_student_id`;
-- Notes the application_id foreign key stays untouched.
-- Keep application_id FK untouched.
-- Drops the student_id column from application_documents.
ALTER TABLE `application_documents` DROP COLUMN `student_id`;
-- Makes user_id required so every document has an owner.
ALTER TABLE `application_documents` MODIFY `user_id` INT(11) NOT NULL;
-- Starts adding the new user foreign key.
ALTER TABLE `application_documents`
  -- Links documents.user_id to users and deletes rows with the user.
  ADD CONSTRAINT `application_documents_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

-- Starts the section divider for existing student ids.
-- --------------------------------------------------------------------------
-- Names section 5: set student_id to the email digits.
-- 5) STUDENT_ID = EMAIL DIGITS for pre-existing rows
-- Closes the section divider.
-- --------------------------------------------------------------------------
-- Explains that it runs after section 3 on purpose.
-- Runs AFTER section 3 on purpose: the applications backfill above needed the
-- Notes applications used the old ids to resolve user_id.
-- old student_ids to resolve user_id. Nothing still references
-- Says nothing references students.student_id anymore.
-- students.student_id (applications/documents now point at users.user_id),
-- So primary key values can be replaced with the email ids.
-- so the PK values can be replaced with the official email-derived IDs.
-- Says taken digits or emails without digits are left alone.
-- Rows whose digits are already taken, or whose email has no digits, keep
-- Ends the explanation of this update.
-- their current student_id.

-- Updates the students table and aliases it as s.
UPDATE `students` s
-- Joins each student to the user account that owns it.
INNER JOIN `users` u ON u.`user_id` = s.`user_id`
-- Joins again to find any other student with the same digits.
LEFT JOIN `students` s2
  -- Computes the candidate id from the email digits.
  ON s2.`student_id` = CAST(REGEXP_REPLACE(SUBSTRING_INDEX(u.`email`, '@', 1), '[^0-9]', '') AS UNSIGNED)
  -- Excludes the current row from that matching join.
  AND s2.`student_id` <> s.`student_id`
-- Sets student_id to the digits taken from the email.
SET s.`student_id` = CAST(REGEXP_REPLACE(SUBSTRING_INDEX(u.`email`, '@', 1), '[^0-9]', '') AS UNSIGNED)
-- Only runs when the email actually contains digits.
WHERE REGEXP_REPLACE(SUBSTRING_INDEX(u.`email`, '@', 1), '[^0-9]', '') <> ''
  -- Only updates when no other student already holds that id.
  AND s2.`student_id` IS NULL;
