<?php
require_once __DIR__ . '/../includes/config.php';

$upload = findUpload((string) ($_GET['id'] ?? ''));
$student = currentStudentProfile($mockStudent);

if (!$upload || strcasecmp((string) $upload['studentEmail'], (string) $student['email']) !== 0) {
    http_response_code(404);
    exit('Document not found.');
}

$path = uploadStoragePath($upload);
if (!$path) {
    http_response_code(404);
    exit('The original file is unavailable for this older upload record.');
}

$name = basename((string) $upload['fileName']);
$disposition = ($_GET['view'] ?? '') === '1' ? 'inline' : 'attachment';
header('Content-Type: ' . (($upload['mimeType'] ?? '') ?: 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . $disposition . '; filename="' . addcslashes($name, "\\\"") . '"');
readfile($path);
exit;
