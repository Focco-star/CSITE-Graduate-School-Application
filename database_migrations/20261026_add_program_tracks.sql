-- Names the migration and date for course and track links.
-- Migration: the courses and the tracks they can follow (2026-10-26)
-- Divider line separating the title from the explanation.
--
-- Says a course does not always belong to a single track.
-- A course does not always belong to a single track. The Master of Science in
-- Gives science education and mechanical engineering as examples.
-- Education courses and the Master of Science in Mechanical Engineering can
-- They can be followed as a capstone or a seminar paper.
-- be followed as a Capstone or as a Seminar Paper, so the student picks the
-- So the student picks the track during account setup.
-- track during the account setup.
-- Ends the summary.
--
-- First change: program_tracks stores every track a course follows.
-- 1. The table program_tracks holds every track a course can follow. The
-- Notes programs.track_id keeps the single default track.
--    column track_id of the programs table stays the track the course belongs
-- So older code that reads one track keeps working.
--    to when there is only one, so the older code keeps working.
-- Ends the first change.
--
-- Second change: the missing courses are added.
-- 2. The courses that were missing are added: MSLIS (Master of Library and
-- Names MSLIS, a library and information science thesis course.
--    Information Science, Thesis) and MSME (Master of Science in Mechanical
-- Names MSME, a mechanical engineering capstone and seminar course.
--    Engineering, Capstone and Seminar Paper).
-- Ends the second change.
--
-- Third change: MLIS moves to capstone and MSLIS to thesis.
-- 3. MLIS moves to the Capstone track and MSLIS is added to the Thesis track,
-- So the two library courses follow their faculty tracks.
--    so the two library courses follow the tracks of their faculty.

-- Creates the program_tracks table if it does not exist.
CREATE TABLE IF NOT EXISTS `program_tracks` (
  -- program_id links the row to a program.
  `program_id` int(11) NOT NULL,
  -- track_id links the row to a track.
  `track_id`   int(11) NOT NULL,
  -- is_primary marks the default track of the program.
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  -- Makes the program and track pair the primary key.
  PRIMARY KEY (`program_id`, `track_id`),
  -- Adds an index on track_id for reverse lookups.
  KEY `idx_program_tracks_track` (`track_id`),
  -- Starts the foreign key linking rows to programs.
  CONSTRAINT `fk_program_tracks_program` FOREIGN KEY (`program_id`)
      -- Deletes the link when its program is deleted.
      REFERENCES `programs` (`program_id`) ON DELETE CASCADE,
  -- Starts the foreign key linking rows to tracks.
  CONSTRAINT `fk_program_tracks_track` FOREIGN KEY (`track_id`)
      -- Deletes the link when its track is deleted.
      REFERENCES `tracks` (`track_id`) ON DELETE CASCADE
-- Uses InnoDB and utf8mb4 for the table.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notes the insert adds the courses that were missing.
-- The courses that were missing.
-- Inserts the new programs, ignoring duplicates.
INSERT IGNORE INTO `programs` (`track_id`, `program_code`, `program_name`, `is_active`)
-- Selects the track id and the new code and name.
SELECT t.track_id, x.code, x.name, 1
  -- Reads the track rows from the tracks table.
  FROM tracks t
  -- Starts the inline list of new courses.
  JOIN (
    -- First new course: MSLIS on the thesis track.
    SELECT 'MSLIS' AS code, 'Master of Library and Information Science' AS name, 'thesis' AS track
    -- Second new course: MSME on the capstone track.
    UNION ALL SELECT 'MSME', 'Master of Science in Mechanical Engineering', 'capstone'
  -- Ends the inline course list.
  ) x
    -- Matches each course to its track by track code.
    ON x.track = t.track_code;

-- Notes MLIS follows capstone and MSLIS follows thesis.
-- MLIS follows the Capstone track, MSLIS the Thesis track.
-- Updates the programs table for the MLIS course.
UPDATE `programs` p
  -- Joins the capstone track row.
  INNER JOIN tracks t ON t.track_code = 'capstone'
   -- Sets the program track to that capstone track.
   SET p.track_id = t.track_id
 -- Only changes the row whose code is MLIS.
 WHERE p.program_code = 'MLIS';

-- Updates the programs table for the MSLIS course.
UPDATE `programs` p
  -- Joins the thesis track row.
  INNER JOIN tracks t ON t.track_code = 'thesis'
   -- Sets the program track to that thesis track.
   SET p.track_id = t.track_id
 -- Only changes the row whose code is MSLIS.
 WHERE p.program_code = 'MSLIS';

-- Notes this inserts every course and allowed track.
-- Every course and the tracks it can follow.
-- Inserts the program and track pairs, ignoring duplicates.
INSERT IGNORE INTO `program_tracks` (`program_id`, `track_id`, `is_primary`)
-- Selects the program id and track id.
SELECT p.program_id, t.track_id,
       -- Marks the row primary when it matches the default track.
       CASE WHEN t.track_code = p_track.track_code THEN 1 ELSE 0 END
  -- Reads the program rows.
  FROM programs p
  -- Starts the inline list of course and track pairs.
  JOIN (
    -- MSCS can follow the thesis track.
    SELECT 'MSCS' AS code, 'thesis' AS track
    -- MSLIS can follow the thesis track.
    UNION ALL SELECT 'MSLIS', 'thesis'
    -- MIT can follow the capstone track.
    UNION ALL SELECT 'MIT', 'capstone'
    -- MLIS can follow the capstone track.
    UNION ALL SELECT 'MLIS', 'capstone'
    -- MSED_BIO can follow the capstone track.
    UNION ALL SELECT 'MSED_BIO', 'capstone'
    -- MSED_CHEM can follow the capstone track.
    UNION ALL SELECT 'MSED_CHEM', 'capstone'
    -- MSED_GS can follow the capstone track.
    UNION ALL SELECT 'MSED_GS', 'capstone'
    -- MSED_PHY can follow the capstone track.
    UNION ALL SELECT 'MSED_PHY', 'capstone'
    -- MSME can follow the capstone track.
    UNION ALL SELECT 'MSME', 'capstone'
    -- MATH can follow the seminar track.
    UNION ALL SELECT 'MATH', 'seminar'
    -- MSED_BIO can also follow the seminar track.
    UNION ALL SELECT 'MSED_BIO', 'seminar'
    -- MSED_CHEM can also follow the seminar track.
    UNION ALL SELECT 'MSED_CHEM', 'seminar'
    -- MSED_GS can also follow the seminar track.
    UNION ALL SELECT 'MSED_GS', 'seminar'
    -- MSED_PHY can also follow the seminar track.
    UNION ALL SELECT 'MSED_PHY', 'seminar'
    -- MSME can also follow the seminar track.
    UNION ALL SELECT 'MSME', 'seminar'
  -- Ends the inline pair list.
  ) x
    -- Matches each pair to its program by code.
    ON x.code = p.program_code
  -- Matches each pair to its track by track code.
  JOIN tracks t ON t.track_code = x.track
  -- Joins the program default track for the primary check.
  LEFT JOIN tracks p_track ON p_track.track_id = p.track_id;

-- Notes the default track comes from the programs table.
-- The primary track of a course is the one the programs table names.
-- Updates the program_tracks rows.
UPDATE `program_tracks` pt
  -- Joins each link to its program row.
  JOIN `programs` p ON p.program_id = pt.program_id
   -- Marks the link primary.
   SET pt.is_primary = 1
 -- Only when the link track is the program default track.
 WHERE pt.track_id = p.track_id;
