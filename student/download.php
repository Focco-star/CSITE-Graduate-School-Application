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

// Resolve upload user_id (fall back to looking up user_id by studentEmail if missing)
$uploadUserId = $upload['user_id'] ?? null;
$uploadEmail  = strtolower(trim((string) ($upload['studentEmail'] ?? '')));

if (!$uploadUserId && $uploadEmail !== '' && class_exists('DB')) {
    try {
        $stmt = DB::getConnection()->prepare("SELECT user_id FROM users WHERE LOWER(email) = :email LIMIT 1");
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

// 4. Resolve physical file path on disk
$path = uploadStoragePath($upload);
if (!$path || !file_exists($path)) {
    http_response_code(404);
    exit('The original file is unavailable for this older upload record.');
}

// 4b. Exact-rendering PDF for Word documents (converted via LibreOffice).
// Falls through to the original file when no conversion is available.
if (($_GET['pdf'] ?? '') === '1' && function_exists('convertedPdfPath')) {
    try {
        $convertedPdf = convertedPdfPath($path);
    } catch (Throwable $e) {
        $convertedPdf = null;
    }
    if (!empty($convertedPdf)) {
        if (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . rawurlencode(pathinfo((string) ($upload['fileName'] ?? 'document'), PATHINFO_FILENAME) . '.pdf') . '"');
        header('Content-Length: ' . filesize($convertedPdf));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        readfile($convertedPdf);
        exit;
    }
}

// 5. Clean output buffers and stream file headers
if (ob_get_level()) {
    ob_end_clean();
}

$name = basename((string) ($upload['fileName'] ?? 'document'));
$isView = ($_GET['view'] ?? '') === '1';
$disposition = $isView ? 'inline' : 'attachment';

header('Content-Type: ' . (($upload['mimeType'] ?? '') ?: 'application/octet-stream'));
header('Content-Disposition: ' . $disposition . '; filename="' . rawurlencode($name) . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($path);
exit;