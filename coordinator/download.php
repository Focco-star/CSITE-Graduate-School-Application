<?php
require_once __DIR__ . '/../includes/config.php';

$upload = findUpload((string) ($_GET['id'] ?? ''));
$path = $upload ? uploadStoragePath($upload) : null;
if (!$path) {
    http_response_code(404);
    exit('Document not found.');
}
$name = basename((string) $upload['fileName']);
$disposition = ($_GET['view'] ?? '') === '1' ? 'inline' : 'attachment';
header('Content-Type: ' . (($upload['mimeType'] ?? '') ?: 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . $disposition . '; filename="' . addcslashes($name, "\\\"") . '"');
readfile($path);
exit;
