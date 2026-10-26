-- Names the migration and date for organizing the template library.
-- Migration: organise the template library by track, course and stage (2026-10-23)
-- Divider line separating the title from the explanation.
--
-- Says both portals now read the same template records.
-- The coordinator and the student portal now read the same template records
-- And show them with the same grouping.
-- and show them with the same grouping. To make that possible the data has
-- So the data must say the same thing the workflow says.
-- to say the same thing the workflow already says:
-- Ends the introduction.
--
-- First change: templates can be flagged for coordinator review.
-- 1. A template can be flagged for the coordinator to look at. Google Drive
-- Drive files with an unclear track or stage are flagged.
--    files whose track or stage cannot be worked out reliably are flagged
-- So they are not filed under a guessed track or stage.
--    instead of being filed under a guess.
-- Ends the first change.
--
-- Second change: the programs table listed only three programs.
-- 2. The programs table only listed three of the graduate programs the
-- The missing graduate programs are added here.
--    system knows about. The missing ones are added here, with the track
-- Each is added with its track so courses group correctly.
--    they follow, so that every course is shown under the right track.
-- Ends the second change.
--
-- Third change: the MATH program name is aligned.
-- 3. The name of MATH is aligned with the name the students see.
-- So it matches the name the students see.
--
-- Fourth change: a track paper template applies to every stage.
-- 4. The paper template of a track is used at every stage of that track
-- Explains the thesis format guide covers concept, proposal, and final.
--    (the thesis format is the format guide for the concept paper, for the
-- So the template belongs to the track.
--    proposal and for the final thesis). It therefore belongs to the track
-- And not to one single stage.
--    and not to a single stage.

-- Changes the templates table to add a review flag.
ALTER TABLE `templates`
  -- Adds needs_review after is_active, defaulting to zero.
  ADD COLUMN `needs_review` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`;

-- Inserts the missing programs, ignoring duplicates.
INSERT IGNORE INTO `programs` (`track_id`, `program_code`, `program_name`, `is_active`)
-- Selects the track id and the new program code and name.
SELECT t.track_id, x.code, x.name, 1
  -- Reads the track rows from the tracks table.
  FROM tracks t
  -- Starts the inline list of new programs to add.
  JOIN (
    -- First new program: MSED_CHEM, science education chemistry.
    SELECT 'MSED_CHEM' AS code, 'Master in Science Education major in Chemistry' AS name
    -- Second new program: MSED_GS, general science major.
    UNION ALL SELECT 'MSED_GS',   'Master in Science Education major in General Science'
    -- Third new program: MSED_BIO, biology major.
    UNION ALL SELECT 'MSED_BIO',  'Master in Science Education major in Biology'
    -- Fourth new program: MSED_PHY, physics major.
    UNION ALL SELECT 'MSED_PHY',  'Master in Science Education major in Physics'
    -- Fifth new program: MLIS, library and information system.
    UNION ALL SELECT 'MLIS',      'Master in Library and Information System'
  -- Ends the inline program list.
  ) x
 -- Attaches all these programs to the capstone track.
 WHERE t.track_code = 'capstone';

-- Updates the programs table to rename MATH.
UPDATE `programs`
   -- Sets the new name to Master in Mathematics Education.
   SET `program_name` = 'Master in Mathematics Education'
 -- Only changes the row whose program_code is MATH.
 WHERE `program_code` = 'MATH';

-- Updates templates to detach templates from stages.
UPDATE `templates`
   -- Clears stage_id so the template applies to the whole track.
   SET `stage_id` = NULL
 -- Only for rows marked as a template document type.
 WHERE `document_type` = 'template'
   -- And only where a stage was set before.
   AND `stage_id` IS NOT NULL;

-- Explains a seminar endorsement row used the wrong file.
-- The row for the "Final Seminar Paper Endorsement to Graduate School" was
-- So the same file was listed twice.
-- saved with the file of the proposal endorsement, so the same file was
-- Notes the correct final file was never uploaded.
-- listed twice. The file for the final stage has never been uploaded, so
-- So the row is hidden and flagged for review.
-- the row is put aside and flagged; the coordinator is told about it and
-- The coordinator is told, and the row returns when the file is added.
-- the row comes back as soon as the file is added in Google Drive.
-- Updates the templates table to fix that row.
UPDATE `templates`
   -- Sets the correct final endorsement file name.
   SET `file_name`     = '4 FINAL SEMINAR PAPER ENDORSEMENT TO GRADSCHOOL.docx',
       -- Adds a description explaining the file is not uploaded yet.
       `description`   = 'The file for this form has not been uploaded yet. Add it in the Google Drive template folder and it will appear here.',
       -- Hides the row by marking it inactive.
       `is_active`     = 0,
       -- Flags the row so the coordinator reviews it.
       `needs_review`  = 1
 -- Only changes the row with this exact template name.
 WHERE `template_name` = 'Final Seminar Paper Endorsement to Graduate School'
   -- And only when it still points at the proposal file.
   AND `file_name` = '2 SEMINAR PAPER PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx';
