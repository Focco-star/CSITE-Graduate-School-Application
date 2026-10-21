<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

/*
 * The document upload now lives on the Application page of the Student
 * Portal: the student fills in the application, attaches the documents and
 * sends everything in one go. This page is kept only so older links still
 * land on the right screen instead of a duplicate upload form.
 */
$stage = trim((string) ($_GET['stage'] ?? ''));
$target = 'student/application.php' . ($stage !== '' ? '?stage=' . urlencode($stage) : '');
redirectTo($target);
