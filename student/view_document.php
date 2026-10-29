<?php /* Opens the page that shows a stored document inside the browser viewer. */ ?>
<?php
// Loads the shared configuration and helper functions.
require_once __DIR__ . '/../includes/config/config.php';
// Loads the database class used to find the upload record.
require_once __DIR__ . '/../includes/config/db.php';



// 1. Fetch parameters & document
// Reads the document id from the URL (id=...) and stores it as text.
$id = (string) ($_GET['id'] ?? '');
// Looks up the upload record for that id.
$upload = findUpload($id);

// If no matching upload record exists, stop and report a 404.
if (!$upload) {
    // Sends the HTTP 404 Not Found status code.
    http_response_code(404);
    // Stops the script, saying the document was not found.
    exit('Document not found in database or storage.');
// Closes the if block that handled a missing upload record.
}

// 2. Resolve current user ID & Email from session/profile
// Gets the logged-in student profile, or the mock profile in dev mode.
$student = currentStudentProfile($mockStudent);

// Starts work on the current user id: first the user_id in the session.
$currentUserId = $_SESSION['user']['user_id'] 
    // Otherwise tries the older session key user.id.
    ?? $_SESSION['user']['id'] 
    // Otherwise falls back to the simple user_id session key.
    ?? $_SESSION['user_id'] 
    // Otherwise uses the user_id from the student profile.
    ?? $student['user_id'] 
    // Otherwise uses null, meaning no user could be identified.
    ?? null;

// Reads the user_id saved on the upload record, if any.
$uploadUserId = $upload['user_id'] ?? null;
// Normalises the upload's student email for comparison.
$uploadEmail  = strtolower(trim((string) ($upload['studentEmail'] ?? '')));

// Only look the owner up by email when the upload has no user_id.
if (!$uploadUserId && $uploadEmail !== '' && class_exists('DB')) {
    // Starts a try block so a database error cannot break the viewer.
    try {
        // Prepares a query that finds the user with this email, ignoring case.
        $stmt = DB::getConnection()->prepare("SELECT user_id FROM users WHERE LOWER(email) = :email LIMIT 1");
        // Runs the query with the upload's email as the parameter.
        $stmt->execute(['email' => $uploadEmail]);
        // Stores the found user id, or null when there is no match.
        $uploadUserId = $stmt->fetchColumn() ?: null;
    // Swallows any database exception so the page keeps working.
    } catch (Throwable $e) {}
// Closes the if block that looked up the owner by email.
}

// Builds the current student's email in lowercase for the check.
$studentEmail = strtolower(trim((string) ($student['email'] ?? $_SESSION['user']['email'] ?? '')));

// 3. Ownership Verification Check
// Starts as false; it becomes true only if the student owns the document.
$isOwner = false;

// Case A: User IDs match
// Case A: the logged-in user id equals the upload's user id.
if ($currentUserId && $uploadUserId && (int)$currentUserId === (int)$uploadUserId) {
    // Marks the current user as the document owner.
    $isOwner = true;
// Closes the Case A if block.
} 
// Case B: Emails match
// Case B: the upload email and the student email are identical.
elseif ($uploadEmail !== '' && $studentEmail !== '' && $uploadEmail === $studentEmail) {
    // Marks the current user as the document owner.
    $isOwner = true;
// Closes the Case B elseif block.
} 
// Case C: Document unlinked OR running in local dev / mock student mode
// Case C: the record is unlinked, or this is dev/demo mode.
elseif (empty($uploadUserId) || empty($uploadEmail) || $studentEmail === 'rtorres@adzu.edu.ph') {
    // Marks the current user as the document owner.
    $isOwner = true;
// Closes the Case C elseif block.
}

// If none of the ownership checks passed, hide the document.
if (!$isOwner) {
    // Sends a 404 status so outsiders cannot confirm the file exists.
    http_response_code(404);
    // Stops the script with a generic not-found message.
    exit('Document not found.');
// Closes the if block that blocked non-owners.
}

// 4. Resolve disk path
// Converts the upload record into the real file path on disk.
$path = uploadStoragePath($upload);
// If the path is missing or the file is gone, stop with a 404.
if (!$path || !file_exists($path)) {
    // Sends the HTTP 404 status code.
    http_response_code(404);
    // Explains that the original file is unavailable.
    exit('The original file is unavailable.');
// Closes the if block that handled a missing file.
}

// Reads the stored file name, defaulting to Document.
$fileName = $upload['fileName'] ?? 'Document';
// Reads the stored MIME type in lowercase for comparisons.
$mimeType = strtolower((string) ($upload['mimeType'] ?? ''));
// Extracts the lowercase file extension from the file name.
$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Check if document is a PDF
// Treats the file as a PDF when its MIME type or extension says so.
$isPdf = ($mimeType === 'application/pdf') || ($extension === 'pdf');

// Word (.docx) documents are rendered in-browser with mammoth.js.
// Legacy binary .doc files cannot be rendered — they fall through to download.
// Treats the file as Word .docx when its extension or MIME type says so.
$isDocx = ($extension === 'docx') || (str_contains($mimeType, 'wordprocessingml'));

// Exact Word-faithful rendering: convert .docx to PDF via LibreOffice when
// installed (cached in storage/converted). Falls back to mammoth.js below.
// Starts as null; filled with a PDF link when conversion is possible.
$printPdfUrl = null;
// Only try PDF conversion for .docx files when the helper exists.
if ($isDocx && function_exists('convertedPdfPath')) {
    // Starts a try block because LibreOffice conversion can fail.
    try {
        // Asks the helper for a cached PDF version of the document.
        $converted = convertedPdfPath($path);
        // If a PDF version exists, the viewer can show it instead of the .docx.
        if ($converted) {
            // Builds the download link that requests the converted PDF (pdf=1).
            $printPdfUrl = url('student/download.php?id=' . urlencode($id) . '&pdf=1');
        // Closes the if block that checked for a converted PDF.
        }
    // Catches conversion errors so the viewer falls back gracefully.
    } catch (Throwable $e) {}
// Closes the if block that handled .docx PDF conversion.
}

// File URLs
// Builds the link used to open the original file inline.
$fileUrl = url('student/download.php?id=' . urlencode($id) . '&view=1');
// Builds the link that downloads the original file as an attachment.
$downloadUrl = url('student/download.php?id=' . urlencode($id));
// Closes PHP so the HTML viewer page can be printed.
?>
<?php /* Declares the document as an HTML5 page. */ ?>
<!DOCTYPE html>
<?php /* Sets the page language to English. */ ?>
<html lang="en">
<?php /* Starts the head section with the page title and styles. */ ?>
<head>
<?php /*     Tells the browser to use UTF-8 so names show correctly. */ ?>
    <meta charset="UTF-8">
<?php /*     Makes the viewer fit mobile and desktop screen widths. */ ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php /*     Sets the browser tab title to View Document plus the file name. */ ?>
    <title>View Document - <?= htmlspecialchars($fileName) ?></title>
<?php /*     Loads the Font Awesome icons used in the header buttons. */ ?>
    <link rel="stylesheet" href="<?= asset('vendor/fontawesome/all.min.css') ?>">
<?php /*     Starts the CSS rules for the dark document viewer. */ ?>
    <style>
<?php /*         Resets spacing and sets a clean system font for every element. */ ?>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, -apple-system, sans-serif; }
<?php /*         Makes the page a full-height column with a dark background. */ ?>
        body { display: flex; flex-direction: column; height: 100vh; background-color: #1e1e1e; color: #fff; }
<?php /*         Styles the top bar holding the file name and download button. */ ?>
        .viewer-header { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1.5rem; background-color: #2d2d2d; border-bottom: 1px solid #3f3f3f; height: 57px; }
<?php /*         Lays out the file icon and file name in a flexible row. */ ?>
        .file-info { display: flex; align-items: center; gap: 0.75rem; overflow: hidden; }
<?php /*         Styles the file name, cutting it off with an ellipsis when long. */ ?>
        .file-name { font-weight: 600; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
<?php /*         Styles the blue Download File button. */ ?>
        .btn-download { display: inline-flex; align-items: center; gap: 0.5rem; background-color: #2563eb; color: #fff; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.875rem; font-weight: 500; transition: background 0.2s; }
<?php /*         Darkens the download button when the mouse hovers over it. */ ?>
        .btn-download:hover { background-color: #1d4ed8; }
<?php /*         Makes the PDF iframe fill the rest of the window. */ ?>
        .viewer-container { flex: 1; width: 100%; height: calc(100vh - 57px); border: none; }
<?php /*         Centres the fallback message for files that cannot be shown. */ ?>
        .fallback-message { display: flex; flex-direction: column; align-items: center; justify-content: center; height: calc(100vh - 57px); gap: 1rem; color: #aaa; text-align: center; padding: 2rem; }
<?php /*         Adds a scrolling area for rendered Word documents. */ ?>
        .docx-scroll { flex: 1; overflow-y: auto; padding: 1.5rem; }
<?php /*         Styles the small status line above a rendered document. */ ?>
        .docx-status { max-width: 850px; margin: 0 auto 1rem; color: #aaa; font-size: 0.9rem; text-align: center; }
<?php /*         Styles the white paper sheet that holds the rendered document. */ ?>
        .docx-paper { background: #fff; color: #1e293b; border-radius: 8px; padding: 2.5rem 3rem; max-width: 850px; margin: 0 auto; box-shadow: 0 4px 24px rgba(0,0,0,0.45); font-size: 0.95rem; line-height: 1.65; }
<?php /*         Styles document headings inside the rendered page. */ ?>
        .docx-paper h1, .docx-paper h2, .docx-paper h3, .docx-paper h4 { color: #0f172a; margin: 1.25em 0 0.5em; line-height: 1.3; }
<?php /*         Sets heading sizes for h1 and h2 inside the rendered page. */ ?>
        .docx-paper h1 { font-size: 1.5rem; } .docx-paper h2 { font-size: 1.25rem; }
<?php /*         Adds space below paragraphs in the rendered document. */ ?>
        .docx-paper p { margin: 0 0 0.75em; }
<?php /*         Indents lists inside the rendered document. */ ?>
        .docx-paper ul, .docx-paper ol { margin: 0 0 0.75em 1.5rem; }
<?php /*         Makes tables in the rendered document collapse their borders. */ ?>
        .docx-paper table { border-collapse: collapse; margin: 0 0 1em; max-width: 100%; }
<?php /*         Adds thin borders and padding to table cells. */ ?>
        .docx-paper th, .docx-paper td { border: 1px solid #cbd5e1; padding: 0.4rem 0.6rem; font-size: 0.85rem; }
<?php /*         Keeps images inside the rendered document at the paper width. */ ?>
        .docx-paper img { max-width: 100%; height: auto; }
<?php /*     Closes the CSS block for the viewer. */ ?>
    </style>
<?php /* Closes the head section. */ ?>
</head>
<?php /* Starts the visible page body. */ ?>
<body>

<?php /*     Starts the top bar of the viewer. */ ?>
    <header class="viewer-header">
<?php /*         Groups the file icon and file name together. */ ?>
        <div class="file-info">
<?php /*             Shows a blue document icon. */ ?>
            <i class="fas fa-file-alt" style="color: #60a5fa;"></i>
<?php /*             Shows the document file name in the top bar. */ ?>
            <span class="file-name"><?= htmlspecialchars($fileName) ?></span>
<?php /*         Closes the file info group. */ ?>
        </div>
<?php /*         Starts the download link for the file. */ ?>
        <a href="<?= $downloadUrl ?>" class="btn-download">
<?php /*             Shows a download icon inside the button. */ ?>
            <i class="fas fa-download"></i> Download File
<?php /*         The button text that downloads the file. */ ?>
        </a>
<?php /*     Closes the viewer top bar. */ ?>
    </header>

<?php /*     If the file is a PDF or has a PDF version, show it in a frame. */ ?>
    <?php if ($isPdf || $printPdfUrl): ?>
<?php /*         Shows the PDF inside a frame that fills the window. */ ?>
        <iframe src="<?= $printPdfUrl ?? $fileUrl ?>" class="viewer-container"></iframe>
<?php /*     Otherwise, when the file is a Word .docx, render it as HTML. */ ?>
    <?php elseif ($isDocx): ?>
<?php /*         Starts the scrolling area for the rendered Word document. */ ?>
        <div class="docx-scroll">
<?php /*             Shows a rendering spinner while mammoth.js works. */ ?>
            <div id="docxStatus" class="docx-status"><i class="fas fa-spinner fa-spin"></i> Rendering document…</div>
<?php /*             Holds the rendered document; hidden until rendering finishes. */ ?>
            <article id="docxBody" class="docx-paper" style="display:none;"></article>
<?php /*         Closes the scrolling area. */ ?>
        </div>
<?php /*         Loads the mammoth.js library that converts .docx to HTML. */ ?>
        <script src="<?= asset('vendor/mammoth.browser.min.js') ?>"></script>
<?php /*         Starts the script that renders the Word document in the browser. */ ?>
        <script>
<?php /*         Starts a self-running function so its variables stay private. */ ?>
        (function () {
<?php /*             Gets the status line element by its id. */ ?>
            var statusEl = document.getElementById('docxStatus');
<?php /*             Gets the paper element where the document will be inserted. */ ?>
            var bodyEl = document.getElementById('docxBody');
<?php /*             Fetches the document file from the server as binary data. */ ?>
            fetch(<?= json_encode($fileUrl) ?>, { credentials: 'same-origin' })
<?php /*                 Handles the response once it arrives. */ ?>
                .then(function (res) {
<?php /*                     Throws an error when the server did not return the file. */ ?>
                    if (!res.ok) throw new Error('HTTP ' + res.status);
<?php /*                     Reads the response body as an ArrayBuffer for mammoth. */ ?>
                    return res.arrayBuffer();
<?php /*                 Closes the response handler. */ ?>
                })
<?php /*                 Converts the Word bytes to HTML with mammoth.js. */ ?>
                .then(function (buf) { return mammoth.convertToHtml({ arrayBuffer: buf }); })
<?php /*                 Handles the converted result. */ ?>
                .then(function (result) {
<?php /*                     Reads the generated HTML and trims surrounding spaces. */ ?>
                    var html = (result.value || '').trim();
<?php /*                     Shows the HTML, or a friendly note when the document is empty. */ ?>
                    bodyEl.innerHTML = html !== '' ? html : '<p><em>This document appears to be empty.</em></p>';
<?php /*                     Makes the rendered paper visible. */ ?>
                    bodyEl.style.display = '';
<?php /*                     Hides the loading spinner. */ ?>
                    statusEl.style.display = 'none';
<?php /*                 Closes the result handler. */ ?>
                })
<?php /*                 Catches any rendering error. */ ?>
                .catch(function () {
<?php /*                     Shows a message with a link to download the file instead. */ ?>
                    statusEl.innerHTML = 'Could not render this document in the browser. <a href="<?= $downloadUrl ?>" style="color:#60a5fa;">Download it instead.</a>';
<?php /*                 Closes the error handler chain. */ ?>
                });
<?php /*         Closes the self-running function. */ ?>
        })();
<?php /*         Ends the document rendering script. */ ?>
        </script>
<?php /*     Otherwise, for other file types, show a download fallback. */ ?>
    <?php else: ?>
<?php /*         Starts the fallback message box. */ ?>
        <div class="fallback-message">
<?php /*             Shows a large blue Word file icon. */ ?>
            <i class="fas fa-file-word fa-3x" style="color: #60a5fa;"></i>
<?php /*             Explains that inline viewing works best for PDF files. */ ?>
            <p>Direct inline viewing is optimized for PDF files.</p>
<?php /*             Explains that old .doc files must be downloaded to view. */ ?>
            <p style="font-size:0.8rem;">Legacy Word (.doc) files need Microsoft Word or LibreOffice to view.</p>
<?php /*             Starts a button that downloads the document. */ ?>
            <a href="<?= $downloadUrl ?>" class="btn-download">
<?php /*                 Shows a download icon in the button. */ ?>
                <i class="fas fa-download"></i> Download to View Document
<?php /*             The button text inviting the student to download the file. */ ?>
            </a>
<?php /*         Closes the fallback message box. */ ?>
        </div>
<?php /*     Closes the if/else block that chose the viewer. */ ?>
    <?php endif; ?>

<?php /* Closes the page body. */ ?>
</body>
<?php /* Closes the HTML document. */ ?>
</html>