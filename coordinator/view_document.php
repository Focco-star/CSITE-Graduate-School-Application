<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure authorized coordinator/admin session

// 1. Fetch document parameter and record from database
$id = (int) ($_GET['id'] ?? 0);
$doc = null;

if ($id > 0) {
    try {
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare("
            SELECT 
                d.document_id AS id,
                d.application_id,
                d.original_name AS fileName,
                d.document_type AS docType,
                d.stored_name,
                d.mime_type AS mimeType,
                d.status,
                d.uploaded_at AS date
            FROM application_documents d
            WHERE d.document_id = :id
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
    exit('Document not found in database.');
}

// 2. Resolve disk path dynamically
$filePath = '';

if (function_exists('uploadStoragePath')) {
    $filePath = uploadStoragePath($doc);
}

if (!$filePath || !file_exists($filePath)) {
    $storedName = $doc['stored_name'] ?? '';
    $possiblePaths = [
        __DIR__ . '/../uploads/' . $storedName,
        __DIR__ . '/../../uploads/' . $storedName,
        __DIR__ . '/../public/uploads/' . $storedName,
        __DIR__ . '/../storage/uploads/' . $storedName,
    ];

    foreach ($possiblePaths as $p) {
        if ($storedName !== '' && file_exists($p)) {
            $filePath = $p;
            break;
        }
    }
}

if (!$filePath || !file_exists($filePath)) {
    http_response_code(404);
    exit('The original file is unavailable on the server.');
}

$fileName  = $doc['fileName'] ?? 'Document';
$mimeType  = strtolower((string) ($doc['mimeType'] ?? ''));
$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Check if document can be rendered inline as PDF
$isPdf = ($mimeType === 'application/pdf') || ($extension === 'pdf');

// Action URLs
$fileUrl     = url('coordinator/download.php?id=' . urlencode((string)$id) . '&mode=inline');
$downloadUrl = url('coordinator/download.php?id=' . urlencode((string)$id));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Document - <?= htmlspecialchars($fileName) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, -apple-system, sans-serif; }
        body { display: flex; flex-direction: column; height: 100vh; background-color: #1e1e1e; color: #fff; }
        .viewer-header { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1.5rem; background-color: #2d2d2d; border-bottom: 1px solid #3f3f3f; height: 57px; }
        .file-info { display: flex; align-items: center; gap: 0.75rem; overflow: hidden; }
        .file-name { font-weight: 600; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .btn-download { display: inline-flex; align-items: center; gap: 0.5rem; background-color: #2563eb; color: #fff; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.875rem; font-weight: 500; transition: background 0.2s; }
        .btn-download:hover { background-color: #1d4ed8; }
        .viewer-container { flex: 1; width: 100%; height: calc(100vh - 57px); border: none; }
        .fallback-message { display: flex; flex-direction: column; align-items: center; justify-content: center; height: calc(100vh - 57px); gap: 1rem; color: #aaa; text-align: center; padding: 2rem; }
    </style>
</head>
<body>

    <header class="viewer-header">
        <div class="file-info">
            <i class="fas fa-file-alt" style="color: #60a5fa;"></i>
            <span class="file-name"><?= htmlspecialchars($fileName) ?></span>
        </div>
        <a href="<?= $downloadUrl ?>" class="btn-download">
            <i class="fas fa-download"></i> Download File
        </a>
    </header>

    <?php if ($isPdf): ?>
        <iframe src="<?= $fileUrl ?>" class="viewer-container"></iframe>
    <?php else: ?>
        <div class="fallback-message">
            <i class="fas fa-file-word fa-3x" style="color: #60a5fa;"></i>
            <p>Direct inline viewing is optimized for PDF files.</p>
            <a href="<?= $downloadUrl ?>" class="btn-download">
                <i class="fas fa-download"></i> Download to View Document
            </a>
        </div>
    <?php endif; ?>

</body>
</html>