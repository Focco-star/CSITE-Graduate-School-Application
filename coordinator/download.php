<?php /* Opens the PHP block that streams a stored document file to the browser. */ ?>
<?php
// Loads shared settings and helper functions from the includes folder.
require_once __DIR__ . '/../includes/config.php';
// Loads the database connection class used to look up the document.
require_once __DIR__ . '/../includes/db.php';

// Checks whether a session is already running before starting one.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so the logged-in user can be checked.
    session_start();
// Closes the session-start check.
}

// Blocks the request unless the user is signed in as coordinator or admin.
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'] ?? '', ['coordinator', 'admin'])) {
    // Sends an HTTP 403 status meaning access is forbidden.
    http_response_code(403);
    // Stops the script and prints a short access-denied message.
    exit('Access denied. Please sign in as the Graduate Program Coordinator.');
// Closes the access check.
}

// Reads the document id from the URL and casts it to a number.
$id = (int) ($_GET['id'] ?? 0);
// Starts the document variable as null until a row is found.
$doc = null;

// Only queries the database when a positive id was given.
if ($id > 0) {
    // Starts the try block so database errors can be caught.
    try {
        // Gets the shared PDO database connection.
        $pdo = DB::getConnection();
        // Prepares a query (below) that loads one document's name and file details by id.
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
        // Runs the query with the document id bound to the placeholder.
        $stmt->execute([':id' => $id]);
        // Fetches the matching row as an associative array into $doc.
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    // Catches a database error so the page fails quietly instead of crashing.
    } catch (Exception $e) {
        // Leaves $doc as null so the not-found check below handles it.
        $doc = null;
    // Closes the try/catch block.
    }
// Closes the positive-id check.
}

// Checks whether no document record was loaded.
if (!$doc) {
    // Sends an HTTP 404 status meaning the record was not found.
    http_response_code(404);
    // Stops the script and prints that the record is missing.
    exit('Document record not found.');
// Closes the missing-record check.
}

// Resolve disk path dynamically
// Starts the disk path as empty text before resolving the real file.
$path = '';

// Uses the shared helper when it is available to build the file path.
if (function_exists('uploadStoragePath')) {
    // Asks the helper for the stored file location of this document.
    $path = uploadStoragePath($doc);
// Closes the helper availability check.
}

// Runs the fallback search when no path was found or the file is missing.
if (!$path || !file_exists($path)) {
    // Reads the stored file name from the database row.
    $storedName = $doc['stored_name'] ?? '';
    // Starts the list of folders where the uploaded file might live.
    $possiblePaths = [
        // Candidate path one: the uploads folder one level above coordinator.
        __DIR__ . '/../uploads/' . $storedName,
        // Candidate path two: the uploads folder two levels above coordinator.
        __DIR__ . '/../../uploads/' . $storedName,
        // Candidate path three: the public uploads folder.
        __DIR__ . '/../public/uploads/' . $storedName,
        // Candidate path four: the storage uploads folder.
        __DIR__ . '/../storage/uploads/' . $storedName,
    // Closes the list of candidate paths.
    ];

    // Loops through each candidate path to find the real file.
    foreach ($possiblePaths as $p) {
        // Checks that a file name exists and that the file is really there.
        if ($storedName !== '' && file_exists($p)) {
            // Uses this path because the stored file was found.
            $path = $p;
            // Stops the loop once a working path is found.
            break;
        // Closes the file-found check.
        }
    // Closes the loop over candidate paths.
    }
// Closes the fallback search block.
}

// Checks again whether a real file still could not be located.
if (!$path || !file_exists($path)) {
    // Sends an HTTP 404 status meaning the file is missing.
    http_response_code(404);
    // Stops the script and explains the original file is unavailable.
    exit('The original file is unavailable on the server.');
// Closes the file-not-found check.
}

// Takes just the file name part of the original name for the download.
$name = basename((string) $doc['fileName']);
// Uses the stored MIME type, or detects it from the file itself.
$mimeType = ($doc['mimeType'] ?? '') ?: mime_content_type($path);

// Check if mode is inline (viewing inside iframe) or attachment (downloading)
// Reads the optional mode value telling how to serve the file.
$mode = $_GET['mode'] ?? '';
// Reads the optional view flag used for previewing in a frame.
$viewParam = $_GET['view'] ?? '';
// Shows the file in the browser when inline or view was requested.
$disposition = ($mode === 'inline' || $viewParam === '1') ? 'inline' : 'attachment';

// Clear output buffers to prevent corrupt files or output errors
// Checks whether any output buffering is still active.
if (ob_get_level()) {
    // Clears those buffers so no stray text corrupts the file.
    ob_end_clean();
// Closes the output buffer cleanup check.
}

// Tells the browser what type of file is being sent.
header('Content-Type: ' . $mimeType);
// Tells the browser the exact size of the file in bytes.
header('Content-Length: ' . filesize($path));
// Sets the file name and whether to show it or download it.
header('Content-Disposition: ' . $disposition . '; filename="' . addcslashes($name, "\\\"") . '"');
// Prevents browsers or proxies from caching the private file.
header('Cache-Control: private, max-age=0, must-revalidate');
// Old-style header kept for compatibility with older browsers.
header('Pragma: public');

// Sends the actual file contents to the browser.
readfile($path);
// Ends the script so nothing else is appended to the file.
exit;