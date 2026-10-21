-- Migration: organise the template library by track, course and stage (2026-10-23)
--
-- The coordinator and the student portal now read the same template records
-- and show them with the same grouping. To make that possible the data has
-- to say the same thing the workflow already says:
--
-- 1. A template can be flagged for the coordinator to look at. Google Drive
--    files whose track or stage cannot be worked out reliably are flagged
--    instead of being filed under a guess.
--
-- 2. The programs table only listed three of the graduate programs the
--    system knows about. The missing ones are added here, with the track
--    they follow, so that every course is shown under the right track.
--
-- 3. The name of MATH is aligned with the name the students see.
--
-- 4. The paper template of a track is used at every stage of that track
--    (the thesis format is the format guide for the concept paper, for the
--    proposal and for the final thesis). It therefore belongs to the track
--    and not to a single stage.

ALTER TABLE `templates`
  ADD COLUMN `needs_review` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`;

INSERT IGNORE INTO `programs` (`track_id`, `program_code`, `program_name`, `is_active`)
SELECT t.track_id, x.code, x.name, 1
  FROM tracks t
  JOIN (
    SELECT 'MSED_CHEM' AS code, 'Master in Science Education major in Chemistry' AS name
    UNION ALL SELECT 'MSED_GS',   'Master in Science Education major in General Science'
    UNION ALL SELECT 'MSED_BIO',  'Master in Science Education major in Biology'
    UNION ALL SELECT 'MSED_PHY',  'Master in Science Education major in Physics'
    UNION ALL SELECT 'MLIS',      'Master in Library and Information System'
  ) x
 WHERE t.track_code = 'capstone';

UPDATE `programs`
   SET `program_name` = 'Master in Mathematics Education'
 WHERE `program_code` = 'MATH';

UPDATE `templates`
   SET `stage_id` = NULL
 WHERE `document_type` = 'template'
   AND `stage_id` IS NOT NULL;

-- The row for the "Final Seminar Paper Endorsement to Graduate School" was
-- saved with the file of the proposal endorsement, so the same file was
-- listed twice. The file for the final stage has never been uploaded, so
-- the row is put aside and flagged; the coordinator is told about it and
-- the row comes back as soon as the file is added in Google Drive.
UPDATE `templates`
   SET `file_name`     = '4 FINAL SEMINAR PAPER ENDORSEMENT TO GRADSCHOOL.docx',
       `description`   = 'The file for this form has not been uploaded yet. Add it in the Google Drive template folder and it will appear here.',
       `is_active`     = 0,
       `needs_review`  = 1
 WHERE `template_name` = 'Final Seminar Paper Endorsement to Graduate School'
   AND `file_name` = '2 SEMINAR PAPER PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx';
