<?php
/* CSITE configuration module: Runs the prototype store bootstrap and loads the Google Drive/template sync integrations. */
// Initialises the demo data store used by the prototype pages.
initPrototypeStore();
// Loads the current student profile into the demo variable.
$mockStudent = currentStudentProfile($mockStudent);

// Google Drive template sync and the update notifications it feeds.
// Loaded last so the helpers above are already available to them.
// Loads the Google Drive helper used by the template sync.
require_once __DIR__ . '/../../integrations/gdrive.php';
// Loads the template sync helper for template updates.
require_once __DIR__ . '/../../integrations/template_sync.php';
