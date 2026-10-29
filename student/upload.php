<?php /* Opens the small script that forwards old upload links to the application page. */ ?>
<?php
// Loads the shared configuration and helper functions.
require_once __DIR__ . '/../includes/config/config.php';
// Loads the database class, kept for consistency with the other pages.
require_once __DIR__ . '/../includes/config/db.php';

/*
 * The document upload now lives on the Application page of the Student
 * Portal: the student fills in the application, attaches the documents and
 * sends everything in one go. This page is kept only so older links still
 * land on the right screen instead of a duplicate upload form.
 */
// Reads the optional stage value from the URL so the right step still opens.
$stage = trim((string) ($_GET['stage'] ?? ''));
// Builds the application page address, keeping the stage when one was given.
$target = 'student/application.php' . ($stage !== '' ? '?stage=' . urlencode($stage) : '');
// Redirects the browser to the application page and stops the script.
redirectTo($target);
