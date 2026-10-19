<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'] ?? '', ['coordinator', 'admin'])) {
    http_response_code(403);
    exit('Access denied. Please sign in as the Graduate Program Coordinator.');
}

$id = (int) ($_GET['id'] ?? 0);
$doc = null;

if ($id > 0) {
    try {
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare("
            SELECT 
                document_id AS id,
                original_name AS fileName,
                stored_name,
                mime_type AS mimeType
            FROM application_documents 
            WHERE document_id = :id 
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $doc = null;
    }
}

if (!$doc) {
    http_response_code(404);
    exit('Document record not found.');
}

// Resolve disk path dynamically
$path = '';

if (function_exists('uploadStoragePath')) {
    $path = uploadStoragePath($doc);
}

if (!$path || !file_exists($path)) {
    $storedName = $doc['stored_name'] ?? '';
    $possiblePaths = [
        __DIR__ . '/../uploads/' . $storedName,
        __DIR__ . '/../../uploads/' . $storedName,
        __DIR__ . '/../public/uploads/' . $storedName,
        __DIR__ . '/../storage/uploads/' . $storedName,
    ];

    foreach ($possiblePaths as $p) {
        if ($storedName !== '' && file_exists($p)) {
            $path = $p;
            break;
        }
    }
}

if (!$path || !file_exists($path)) {
    http_response_code(404);
    exit('The original file is unavailable on the server.');
}

$name = basename((string) $doc['fileName']);
$mimeType = ($doc['mimeType'] ?? '') ?: mime_content_type($path);

// Check if mode is inline (viewing inside iframe) or attachment (downloading)
$mode = $_GET['mode'] ?? '';
$viewParam = $_GET['view'] ?? '';
$disposition = ($mode === 'inline' || $viewParam === '1') ? 'inline' : 'attachment';

// Clear output buffers to prevent corrupt files or output errors
if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . $disposition . '; filename="' . addcslashes($name, "\\\"") . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($path);
exit;