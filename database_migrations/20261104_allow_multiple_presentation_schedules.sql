ALTER TABLE `schedules`
  DROP INDEX `uq_schedules_application`,
  ADD COLUMN `presentation_result` VARCHAR(50) DEFAULT NULL AFTER `status`,
  ADD KEY `idx_schedules_application` (`application_id`, `schedule_id`);

UPDATE `schedules` sc
INNER JOIN `applications` a ON a.`application_id` = sc.`application_id`
SET sc.`presentation_result` = a.`result`
WHERE sc.`presentation_result` IS NULL
  AND a.`result` IS NOT NULL
  AND a.`result` <> '';
