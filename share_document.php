<?php /* Opens the public viewer for a shared student document. */ ?>
<?php
// Loads the shared configuration and helper functions.
require_once __DIR__ . '/includes/config/config.php';
// Loads the database class used to find the shared document.
require_once __DIR__ . '/includes/config/db.php';

// Reads the share token from the URL.
$token = (string) ($_GET['token'] ?? '');
// Looks up the document that belongs to this token.
$upload = documentShareFind($token);

// Stops with a 404 when the token is invalid or the document no longer exists.
if (!$upload) {
    // Sends the HTTP 404 Not Found status code.
    http_response_code(404);
    // Stops the script with a short explanation.
    exit('This shared document link is invalid or no longer available.');
// Closes the missing-document check.
}

// Finds the real file location for this upload.
$path = uploadStoragePath($upload);
// Stops with a 404 when the file is missing from the server.
if (!$path || !file_exists($path)) {
    // Sends the HTTP 404 Not Found status code.
    http_response_code(404);
    // Stops the script and explains the file is unavailable.
    exit('The shared document is unavailable on the server.');
// Closes the missing-file check.
}

// Reads the original file name, falling back to a simple label.
$fileName = (string) ($upload['fileName'] ?? 'Document');
// Reads the stored MIME type in lowercase for comparisons.
$mimeType = strtolower((string) ($upload['mimeType'] ?? ''));
// Reads the file extension in lowercase for comparisons.
$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Serves the raw file when the viewer or a download button asks for it.
if (($_GET['raw'] ?? '') === '1' || ($_GET['download'] ?? '') === '1') {
    // Uses the original name only, never a folder from the database.
    $downloadName = basename($fileName);
    // Uses the stored MIME type, or detects it from the file itself.
    $sendType = $mimeType !== '' ? $mimeType : (string) (mime_content_type($path) ?: 'application/octet-stream');
    // Chooses attachment for downloads and inline for the in-page viewer.
    $disposition = ($_GET['download'] ?? '') === '1' ? 'attachment' : 'inline';
    // Clears any output buffering so the file bytes are not corrupted.
    if (ob_get_level()) {
        // Ends every active output buffer.
        ob_end_clean();
    // Closes the output buffer cleanup check.
    }
    // Tells the browser what type of file is being sent.
    header('Content-Type: ' . $sendType);
    // Tells the browser the exact size of the file in bytes.
    header('Content-Length: ' . filesize($path));
    // Sets the file name and whether to show it or download it.
    header('Content-Disposition: ' . $disposition . '; filename="' . addcslashes($downloadName, "\\\"") . '"');
    // Prevents browsers and proxies from caching the private file.
    header('Cache-Control: private, max-age=0, must-revalidate');
    // Sends the actual file contents to the browser.
    readfile($path);
    // Ends the script so nothing else is appended to the file.
    exit;
// Closes the raw-file branch.
}

// Treats the file as a PDF when its MIME type or extension says so.
$isPdf = ($mimeType === 'application/pdf') || ($extension === 'pdf');
// Treats the file as a Word .docx when its MIME type or extension says so.
$isDocx = ($extension === 'docx') || str_contains($mimeType, 'wordprocessingml');
// Treats the file as an image when its MIME type or extension says so.
$isImage = str_starts_with($mimeType, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true);
// Treats the file as plain text when its MIME type or extension says so.
$isText = str_starts_with($mimeType, 'text/') || in_array($extension, ['txt', 'csv', 'log'], true);

// Builds the link that shows the original file inside the viewer.
$fileUrl = url('share_document.php?token=' . rawurlencode($token) . '&raw=1');
// Builds the link that downloads the original file as an attachment.
$downloadUrl = url('share_document.php?token=' . rawurlencode($token) . '&download=1');
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
<?php /*     Keeps the shared page out of search engines. */ ?>
    <meta name="robots" content="noindex, nofollow">
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
<?php /*         Styles the top bar holding the file name and the buttons. */ ?>
        .viewer-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.75rem 1.5rem; background-color: #2d2d2d; border-bottom: 1px solid #3f3f3f; min-height: 57px; flex-wrap: wrap; }
<?php /*         Lays out the file icon and file name in a flexible row. */ ?>
        .file-info { display: flex; align-items: center; gap: 0.75rem; overflow: hidden; min-width: 0; }
<?php /*         Styles the file name, cutting it off with an ellipsis when long. */ ?>
        .file-name { font-weight: 600; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
<?php /*         Lays out the header buttons in a row. */ ?>
        .viewer-actions { display: flex; align-items: center; gap: 0.5rem; }
<?php /*         Styles the blue Download File button. */ ?>
        .btn-download { display: inline-flex; align-items: center; gap: 0.5rem; background-color: #2563eb; color: #fff; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.875rem; font-weight: 500; transition: background 0.2s; }
<?php /*         Darkens the download button when the mouse hovers over it. */ ?>
        .btn-download:hover { background-color: #1d4ed8; }
<?php /*         Makes the PDF iframe fill the rest of the window. */ ?>
        .viewer-container { flex: 1; width: 100%; height: calc(100vh - 57px); border: none; }
<?php /*         Centres images inside the remaining window space. */ ?>
        .image-stage { flex: 1; display: flex; align-items: center; justify-content: center; overflow: auto; padding: 1.5rem; background-color: #141414; }
<?php /*         Keeps images inside the visible window. */ ?>
        .image-stage img { max-width: 100%; max-height: 100%; object-fit: contain; box-shadow: 0 4px 24px rgba(0,0,0,0.45); }
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
<?php /*             Shows a document icon. */ ?>
            <i class="fas fa-file-alt" style="color: #60a5fa;"></i>
<?php /*             Shows the document file name in the top bar. */ ?>
            <span class="file-name"><?= htmlspecialchars($fileName) ?></span>
<?php /*         Closes the file info group. */ ?>
        </div>
<?php /*         Starts the download link for the file. */ ?>
        <div class="viewer-actions">
<?php /*             Downloads the shared file as an attachment. */ ?>
            <a href="<?= $downloadUrl ?>" class="btn-download"><i class="fas fa-download"></i> Download File</a>
<?php /*         Closes the header buttons group. */ ?>
        </div>
<?php /*     Closes the viewer top bar. */ ?>
    </header>

<?php /*     Shows PDF files inside a frame. */ ?>
    <?php if ($isPdf): ?>
<?php /*         Shows the PDF inside a frame that fills the window. */ ?>
        <iframe src="<?= $fileUrl ?>" class="viewer-container" title="<?= htmlspecialchars($fileName) ?>"></iframe>
<?php /*     Shows image files directly on the page. */ ?>
    <?php elseif ($isImage): ?>
<?php /*         Opens the stage that centres the image. */ ?>
        <div class="image-stage">
<?php /*             Shows the uploaded image at its natural aspect ratio. */ ?>
            <img src="<?= $fileUrl ?>" alt="<?= htmlspecialchars($fileName) ?>">
<?php /*         Closes the image stage. */ ?>
        </div>
<?php /*     Shows plain text files inside a frame. */ ?>
    <?php elseif ($isText): ?>
<?php /*         Shows the text file inside a frame that fills the window. */ ?>
        <iframe src="<?= $fileUrl ?>" class="viewer-container" title="<?= htmlspecialchars($fileName) ?>"></iframe>
<?php /*     Renders Word .docx files as HTML. */ ?>
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
<?php /*     Shows a download fallback for file types the browser cannot preview. */ ?>
    <?php else: ?>
<?php /*         Starts the fallback message box. */ ?>
        <div class="fallback-message">
<?php /*             Shows a large file icon. */ ?>
            <i class="fas fa-file fa-3x" style="color: #60a5fa;"></i>
<?php /*             Explains that this file type cannot be shown directly. */ ?>
            <p>This file type cannot be previewed directly in the browser.</p>
<?php /*             Starts a button that downloads the document. */ ?>
            <a href="<?= $downloadUrl ?>" class="btn-download"><i class="fas fa-download"></i> Download to View Document</a>
<?php /*         Closes the fallback message box. */ ?>
        </div>
<?php /*     Closes the viewer selection block. */ ?>
    <?php endif; ?>

<?php /* Closes the page body. */ ?>
</body>
<?php /* Closes the HTML document. */ ?>
</html>
