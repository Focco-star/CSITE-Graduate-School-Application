<?php /* Starts the PHP code that shows a document in the browser. */ ?>
<?php
// Loads the shared config one folder up from the coordinator folder.
require_once __DIR__ . '/../includes/config/config.php';
// Loads the database helper to look up the document record.
require_once __DIR__ . '/../includes/config/db.php';

// Checks whether no session has been started yet.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so login state can be checked.
    session_start();
// Closes the session start check.
}

// Ensure authorized coordinator/admin session

// 1. Fetch document parameter and record from database
// Reads the document id from the URL, or 0 when missing.
$id = (int) ($_GET['id'] ?? 0);
// Starts the document variable empty until the record is loaded.
$doc = null;

// Only looks up the document when a real id was given.
if ($id > 0) {
    // Opens a try block around the database lookup.
    try {
        // Gets the shared database connection.
        $pdo = DB::getConnection();
        // Begins the SQL that selects one document row by id.
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
        // Runs the query with the document id.
        $stmt->execute([':id' => $id]);
        // Fetches the document row as an associative array.
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    // Catches any error from the document lookup.
    } catch (Exception $e) {
        // Leaves the document empty so the 404 message shows.
        $doc = null;
    // Closes the lookup error handler.
    }
// Closes the document id check.
}

// Runs when no document record was found.
if (!$doc) {
    // Sets the HTTP status code to 404 Not Found.
    http_response_code(404);
    // Stops the script with a 'not found' message.
    exit('Document not found in database.');
// Closes the missing document check.
}

// 2. Resolve disk path dynamically
// Starts the file path empty until it is resolved.
$filePath = '';

// Checks whether the storage path helper function exists.
if (function_exists('uploadStoragePath')) {
    // Asks the helper for the disk path of the stored file.
    $filePath = uploadStoragePath($doc);
// Closes the helper check.
}

// Runs when no path was found or the file is missing.
if (!$filePath || !file_exists($filePath)) {
    // Reads the stored file name from the document row.
    $storedName = $doc['stored_name'] ?? '';
    // Builds a list of folders where the file might live.
    $possiblePaths = [
        // Candidate path inside the app's uploads folder.
        __DIR__ . '/../uploads/' . $storedName,
        // Candidate path two folders up in uploads.
        __DIR__ . '/../../uploads/' . $storedName,
        // Candidate path inside public/uploads.
        __DIR__ . '/../public/uploads/' . $storedName,
        // Candidate path inside the storage/uploads folder.
        __DIR__ . '/../storage/uploads/' . $storedName,
    // Closes the list of candidate paths.
    ];

    // Loops over each candidate path to test it.
    foreach ($possiblePaths as $p) {
        // Uses the path when a name exists and the file is really there.
        if ($storedName !== '' && file_exists($p)) {
            // Saves the working path for later use.
            $filePath = $p;
            // Stops the loop once a real file is found.
            break;
        // Closes the candidate check.
        }
    // Closes the candidate path loop.
    }
// Closes the fallback path search block.
}

// Runs when the file still cannot be found on disk.
if (!$filePath || !file_exists($filePath)) {
    // Sets the HTTP status code to 404 Not Found.
    http_response_code(404);
    // Stops the script saying the original file is unavailable.
    exit('The original file is unavailable on the server.');
// Closes the missing file check.
}

// Gets the original file name for the header and title.
$fileName  = $doc['fileName'] ?? 'Document';
// Gets the file mime type in lowercase.
$mimeType  = strtolower((string) ($doc['mimeType'] ?? ''));
// Gets the file extension in lowercase from its name.
$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Check if document can be rendered inline as PDF
// Marks the document as a PDF so it can be shown inline.
$isPdf = ($mimeType === 'application/pdf') || ($extension === 'pdf');

// Action URLs
// Builds the inline viewer URL that opens the file in the browser.
$fileUrl     = url('coordinator/download.php?id=' . urlencode((string)$id) . '&mode=inline');
// Builds the download URL that forces a file download.
$downloadUrl = url('coordinator/download.php?id=' . urlencode((string)$id));
// Closes PHP so the HTML page below can be printed.
?>
<?php /* Declares the page as an HTML5 document. */ ?>
<!DOCTYPE html>
<?php /* Opens the HTML element and sets the page language to English. */ ?>
<html lang="en">
<?php /* Opens the head section with page metadata. */ ?>
<head>
<?php /*     Sets the character encoding to UTF-8. */ ?>
    <meta charset="UTF-8">
<?php /*     Makes the page scale correctly on phones and tablets. */ ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php /*     Sets the browser tab title using the document name. */ ?>
    <title>View Document - <?= htmlspecialchars($fileName) ?></title>
<?php /*     Loads the Font Awesome icon stylesheet from a CDN. */ ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<?php /*     Opens the CSS block that styles the viewer. */ ?>
    <style>
<?php /*         Resets spacing and sets a system font for all elements. */ ?>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, -apple-system, sans-serif; }
<?php /*         Makes the body a full-height dark column layout. */ ?>
        body { display: flex; flex-direction: column; height: 100vh; background-color: #1e1e1e; color: #fff; }
<?php /*         Styles the dark header bar with file info and download button. */ ?>
        .viewer-header { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1.5rem; background-color: #2d2d2d; border-bottom: 1px solid #3f3f3f; height: 57px; }
<?php /*         Lays out the file icon and name in a row, hiding overflow. */ ?>
        .file-info { display: flex; align-items: center; gap: 0.75rem; overflow: hidden; }
<?php /*         Styles the file name to cut off with an ellipsis. */ ?>
        .file-name { font-weight: 600; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
<?php /*         Styles the blue download button with rounded corners. */ ?>
        .btn-download { display: inline-flex; align-items: center; gap: 0.5rem; background-color: #2563eb; color: #fff; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.875rem; font-weight: 500; transition: background 0.2s; }
<?php /*         Darkens the download button when the mouse hovers. */ ?>
        .btn-download:hover { background-color: #1d4ed8; }
<?php /*         Makes the viewer fill the remaining page height. */ ?>
        .viewer-container { flex: 1; width: 100%; height: calc(100vh - 57px); border: none; }
<?php /*         Styles the centered fallback message for non-PDF files. */ ?>
        .fallback-message { display: flex; flex-direction: column; align-items: center; justify-content: center; height: calc(100vh - 57px); gap: 1rem; color: #aaa; text-align: center; padding: 2rem; }
<?php /*     Closes the CSS block. */ ?>
    </style>
<?php /* Closes the head section. */ ?>
</head>
<?php /* Opens the page body. */ ?>
<body>

<?php /*     Opens the dark header bar of the viewer. */ ?>
    <header class="viewer-header">
<?php /*         Opens the box holding the file icon and name. */ ?>
        <div class="file-info">
<?php /*             Shows a blue file icon. */ ?>
            <i class="fas fa-file-alt" style="color: #60a5fa;"></i>
<?php /*             Shows the document's file name. */ ?>
            <span class="file-name"><?= htmlspecialchars($fileName) ?></span>
<?php /*         Closes the file info box. */ ?>
        </div>
<?php /*         Download link pointing to the download URL. */ ?>
        <a href="<?= $downloadUrl ?>" class="btn-download">
<?php /*             Shows a download icon inside the link. */ ?>
            <i class="fas fa-download"></i> Download File
<?php /*         Closes the download link. */ ?>
        </a>
<?php /*     Closes the header bar. */ ?>
    </header>

<?php /*     Only shows the inline viewer when the file is a PDF. */ ?>
    <?php if ($isPdf): ?>
<?php /*         Embedded frame that displays the PDF inline. */ ?>
        <iframe src="<?= $fileUrl ?>" class="viewer-container"></iframe>
<?php /*     Runs when the file is not a PDF. */ ?>
    <?php else: ?>
<?php /*         Opens the centered fallback message box. */ ?>
        <div class="fallback-message">
<?php /*             Shows a large Word file icon. */ ?>
            <i class="fas fa-file-word fa-3x" style="color: #60a5fa;"></i>
<?php /*             Explains that inline viewing works best for PDFs. */ ?>
            <p>Direct inline viewing is optimized for PDF files.</p>
<?php /*             Download link for the non-PDF file. */ ?>
            <a href="<?= $downloadUrl ?>" class="btn-download">
<?php /*                 Shows a download icon inside the fallback link. */ ?>
                <i class="fas fa-download"></i> Download to View Document
<?php /*             Closes the fallback download link. */ ?>
            </a>
<?php /*         Closes the fallback message box. */ ?>
        </div>
<?php /*     Ends the PDF / fallback check. */ ?>
    <?php endif; ?>

<?php /* Closes the page body. */ ?>
</body>
<?php /* Closes the HTML document. */ ?>
</html>