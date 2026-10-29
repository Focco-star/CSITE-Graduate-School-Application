<?php /* Opens the script that safely sends a stored document file to the browser. */ ?>
<?php
// Loads the shared configuration, helpers and settings needed by this page.
require_once __DIR__ . '/../includes/config/config.php';

// 1. Fetch parameters & document
// Reads the document id from the URL (id=...) and stores it as text.
$id = (string) ($_GET['id'] ?? '');
// Looks up the upload record for that id in the database or storage index.
$upload = findUpload($id);

// If no matching upload record was found, stop and report a 404.
if (!$upload) {
    // Sends the HTTP 404 Not Found status code to the browser.
    http_response_code(404);
    // Stops the script with a short message saying the document was not found.
    exit('Document not found in database or storage.');
// Closes the if block that handled a missing document.
}

// 2. Resolve current user ID & Email from session/profile
// Gets the logged-in student profile, or the mock profile in local dev mode.
$student = currentStudentProfile($mockStudent);

// Starts work on the current user id: first the user_id saved in the session.
$currentUserId = $_SESSION['user']['user_id'] 
    // Otherwise tries the older session key user.id.
    ?? $_SESSION['user']['id'] 
    // Otherwise falls back to the simple user_id session key.
    ?? $_SESSION['user_id'] 
    // Otherwise uses the user_id taken from the student profile.
    ?? $student['user_id'] 
    // Otherwise uses null, meaning no user could be identified.
    ?? null;

// Resolve upload user_id (fall back to looking up user_id by studentEmail if missing)
// Reads the user_id saved on the upload record, if any.
$uploadUserId = $upload['user_id'] ?? null;
// Normalises the upload's student email to lowercase text for comparison.
$uploadEmail  = strtolower(trim((string) ($upload['studentEmail'] ?? '')));

// Only look the owner up by email when the upload has no user_id but has one.
if (!$uploadUserId && $uploadEmail !== '' && class_exists('DB')) {
    // Starts a try block so a database error cannot break the download.
    try {
        // Prepares a query that finds the user with this email, ignoring letter case.
        $stmt = DB::getConnection()->prepare("SELECT user_id FROM users WHERE LOWER(email) = :email LIMIT 1");
        // Runs the query, passing the upload's email as the named parameter.
        $stmt->execute(['email' => $uploadEmail]);
        // Stores the found user id, or null when there is no match.
        $uploadUserId = $stmt->fetchColumn() ?: null;
    // Swallows any database exception so the download can continue.
    } catch (Throwable $e) {}
// Closes the if block that looked up the upload owner by email.
}

// Builds the current student's email in lowercase for the ownership check.
$studentEmail = strtolower(trim((string) ($student['email'] ?? $_SESSION['user']['email'] ?? '')));

// 3. Ownership Verification Check
// Starts as false; it becomes true only if the student owns this document.
$isOwner = false;

// Case A: User IDs match
// Case A: the logged-in user id equals the upload's user id.
if ($currentUserId && $uploadUserId && (int)$currentUserId === (int)$uploadUserId) {
    // Marks the current user as the owner of the document.
    $isOwner = true;
// Closes the Case A if block.
} 
// Case B: Emails match
// Case B: the upload email and the student email are identical.
elseif ($uploadEmail !== '' && $studentEmail !== '' && $uploadEmail === $studentEmail) {
    // Marks the current user as the owner of the document.
    $isOwner = true;
// Closes the Case B elseif block.
} 
// Case C: Document unlinked OR running in local dev / mock student mode
// Case C: the record is unlinked, or this is local dev / demo mode.
elseif (empty($uploadUserId) || empty($uploadEmail) || $studentEmail === 'rtorres@adzu.edu.ph') {
    // Marks the current user as the owner of the document.
    $isOwner = true;
// Closes the Case C elseif block.
}

// If none of the ownership checks passed, hide the document from this user.
if (!$isOwner) {
    // Sends a 404 status so outsiders cannot confirm that the file exists.
    http_response_code(404);
    // Stops the script with a generic not-found message.
    exit('Document not found.');
// Closes the if block that blocked non-owners.
}

// 4. Resolve physical file path on disk
// Converts the upload record into the real file path on the server disk.
$path = uploadStoragePath($upload);
// If the path is missing or the file is gone, stop with a 404.
if (!$path || !file_exists($path)) {
    // Sends the HTTP 404 status code.
    http_response_code(404);
    // Explains that the original file is unavailable for this older record.
    exit('The original file is unavailable for this older upload record.');
// Closes the if block that handled a missing file on disk.
}

// 5. Clean output buffers and stream file headers
// Checks again for active output buffering before the normal download.
if (ob_get_level()) {
    // Clears any buffered output so the file download stays clean.
    ob_end_clean();
// Closes the if block that cleared output buffers.
}

// Takes just the file name part of the stored file for the download name.
$name = basename((string) ($upload['fileName'] ?? 'document'));
// Reads view=1 from the URL to decide between showing and downloading.
$isView = ($_GET['view'] ?? '') === '1';
// Uses inline display for viewing, or attachment to force a download.
$disposition = $isView ? 'inline' : 'attachment';

// Sends the stored MIME type, defaulting to a generic binary file type.
header('Content-Type: ' . (($upload['mimeType'] ?? '') ?: 'application/octet-stream'));
// Sets the file name and whether the browser opens or downloads the file.
header('Content-Disposition: ' . $disposition . '; filename="' . rawurlencode($name) . '"');
// Sends the file size so the browser knows how much data to expect.
header('Content-Length: ' . filesize($path));
// Prevents the browser and proxies from caching this private file.
header('Cache-Control: private, max-age=0, must-revalidate');
// Legacy header that marks the response as public.
header('Pragma: public');

// Sends the actual document bytes from disk to the browser.
readfile($path);
// Ends the script right after the file so nothing else is printed.
exit;