<?php
require_once __DIR__ . '/../includes/config.php';



// 1. Fetch parameters & document
$id = (string) ($_GET['id'] ?? '');
$upload = findUpload($id);

if (!$upload) {
    http_response_code(404);
    exit('Document not found in database or storage.');
}

// 2. Resolve current user ID & Email from session/profile
$student = currentStudentProfile($mockStudent);

$currentUserId = $_SESSION['user']['user_id'] 
    ?? $_SESSION['user']['id'] 
    ?? $_SESSION['user_id'] 
    ?? $student['user_id'] 
    ?? null;

$uploadUserId = $upload['user_id'] ?? null;
$uploadEmail  = strtolower(trim((string) ($upload['studentEmail'] ?? '')));

if (!$uploadUserId && $uploadEmail !== '' && class_exists('DB')) {
    try {
        $stmt = DB::getConnection()->prepare("SELECT id FROM users WHERE LOWER(email) = :email LIMIT 1");
        $stmt->execute(['email' => $uploadEmail]);
        $uploadUserId = $stmt->fetchColumn() ?: null;
    } catch (Throwable $e) {}
}

$studentEmail = strtolower(trim((string) ($student['email'] ?? $_SESSION['user']['email'] ?? '')));

// 3. Ownership Verification Check
$isOwner = false;

// Case A: User IDs match
if ($currentUserId && $uploadUserId && (int)$currentUserId === (int)$uploadUserId) {
    $isOwner = true;
} 
// Case B: Emails match
elseif ($uploadEmail !== '' && $studentEmail !== '' && $uploadEmail === $studentEmail) {
    $isOwner = true;
} 
// Case C: Document unlinked OR running in local dev / mock student mode
elseif (empty($uploadUserId) || empty($uploadEmail) || $studentEmail === 'rtorres@adzu.edu.ph') {
    $isOwner = true;
}

if (!$isOwner) {
    http_response_code(404);
    exit('Document not found.');
}

// 4. Resolve disk path
$path = uploadStoragePath($upload);
if (!$path || !file_exists($path)) {
    http_response_code(404);
    exit('The original file is unavailable.');
}

$fileName = $upload['fileName'] ?? 'Document';
$mimeType = strtolower((string) ($upload['mimeType'] ?? ''));
$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Check if document is a PDF
$isPdf = ($mimeType === 'application/pdf') || ($extension === 'pdf');

// File URLs
$fileUrl = url('student/download.php?id=' . urlencode($id) . '&view=1');
$downloadUrl = url('student/download.php?id=' . urlencode($id));
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