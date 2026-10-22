-- Migration: the courses and the tracks they can follow (2026-10-26)
--
-- A course does not always belong to a single track. The Master of Science in
-- Education courses and the Master of Science in Mechanical Engineering can
-- be followed as a Capstone or as a Seminar Paper, so the student picks the
-- track during the account setup.
--
-- 1. The table program_tracks holds every track a course can follow. The
--    column track_id of the programs table stays the track the course belongs
--    to when there is only one, so the older code keeps working.
--
-- 2. The courses that were missing are added: MSLIS (Master of Library and
--    Information Science, Thesis) and MSME (Master of Science in Mechanical
--    Engineering, Capstone and Seminar Paper).
--
-- 3. MLIS moves to the Capstone track and MSLIS is added to the Thesis track,
--    so the two library courses follow the tracks of their faculty.

CREATE TABLE IF NOT EXISTS `program_tracks` (
  `program_id` int(11) NOT NULL,
  `track_id`   int(11) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`program_id`, `track_id`),
  KEY `idx_program_tracks_track` (`track_id`),
  CONSTRAINT `fk_program_tracks_program` FOREIGN KEY (`program_id`)
      REFERENCES `programs` (`program_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_program_tracks_track` FOREIGN KEY (`track_id`)
      REFERENCES `tracks` (`track_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The courses that were missing.
INSERT IGNORE INTO `programs` (`track_id`, `program_code`, `program_name`, `is_active`)
SELECT t.track_id, x.code, x.name, 1
  FROM tracks t
  JOIN (
    SELECT 'MSLIS' AS code, 'Master of Library and Information Science' AS name, 'thesis' AS track
    UNION ALL SELECT 'MSME', 'Master of Science in Mechanical Engineering', 'capstone'
  ) x
    ON x.track = t.track_code;

-- MLIS follows the Capstone track, MSLIS the Thesis track.
UPDATE `programs` p
  INNER JOIN tracks t ON t.track_code = 'capstone'
   SET p.track_id = t.track_id
 WHERE p.program_code = 'MLIS';

UPDATE `programs` p
  INNER JOIN tracks t ON t.track_code = 'thesis'
   SET p.track_id = t.track_id
 WHERE p.program_code = 'MSLIS';

-- Every course and the tracks it can follow.
INSERT IGNORE INTO `program_tracks` (`program_id`, `track_id`, `is_primary`)
SELECT p.program_id, t.track_id,
       CASE WHEN t.track_code = p_track.track_code THEN 1 ELSE 0 END
  FROM programs p
  JOIN (
    SELECT 'MSCS' AS code, 'thesis' AS track
    UNION ALL SELECT 'MSLIS', 'thesis'
    UNION ALL SELECT 'MIT', 'capstone'
    UNION ALL SELECT 'MLIS', 'capstone'
    UNION ALL SELECT 'MSED_BIO', 'capstone'
    UNION ALL SELECT 'MSED_CHEM', 'capstone'
    UNION ALL SELECT 'MSED_GS', 'capstone'
    UNION ALL SELECT 'MSED_PHY', 'capstone'
    UNION ALL SELECT 'MSME', 'capstone'
    UNION ALL SELECT 'MATH', 'seminar'
    UNION ALL SELECT 'MSED_BIO', 'seminar'
    UNION ALL SELECT 'MSED_CHEM', 'seminar'
    UNION ALL SELECT 'MSED_GS', 'seminar'
    UNION ALL SELECT 'MSED_PHY', 'seminar'
    UNION ALL SELECT 'MSME', 'seminar'
  ) x
    ON x.code = p.program_code
  JOIN tracks t ON t.track_code = x.track
  LEFT JOIN tracks p_track ON p_track.track_id = p.track_id;

-- The primary track of a course is the one the programs table names.
UPDATE `program_tracks` pt
  JOIN `programs` p ON p.program_id = pt.program_id
   SET pt.is_primary = 1
 WHERE pt.track_id = p.track_id;
