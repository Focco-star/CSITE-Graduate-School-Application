<?php /* Opens the protected viewer for a shared student document. */ ?>
<?php
// Loads the shared configuration and helper functions.
require_once __DIR__ . '/includes/config/config.php';
// Loads the database class used to find the shared document.
require_once __DIR__ . '/includes/config/db.php';

// Checks whether a session is already running before starting one.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so the signed-in user can be checked.
    session_start();
// Closes the session-start check.
}

// Checks whether anyone is signed in before deciding how to handle the request.
if (empty($_SESSION['user'])) {
    // Reads the page the user tried to open so it can be restored after login.
    $returnPath = (string) ($_SERVER['REQUEST_URI'] ?? '');
    // Builds the coordinator login address with the return path when one exists.
    $loginTarget = url('coordinator/login.php') . ($returnPath !== '' ? '?next=' . rawurlencode($returnPath) : '');
    // Sends the visitor to the coordinator login page.
    header('Location: ' . $loginTarget);
    // Stops the script after the redirect.
    exit;
// Closes the signed-out check.
}

// Blocks signed-in users whose role is not coordinator or admin.
if (!in_array($_SESSION['user']['role'] ?? '', ['coordinator', 'admin'], true)) {
    // Sends an HTTP 403 status meaning access is forbidden.
    http_response_code(403);
    // Tells the browser the error page is HTML in UTF-8.
    header('Content-Type: text/html; charset=utf-8');
    // Shows a short page explaining that only authorized users may open the file.
    exit('<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Access denied</title></head><body style="font-family:system-ui,sans-serif;background:#f1f5f9;color:#1e293b;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:1.5rem;"><div style="background:#fff;border-radius:10px;padding:2rem;max-width:460px;box-shadow:0 12px 30px rgba(15,23,42,.15);text-align:center;"><h1 style="margin:0 0 .75rem;font-size:1.25rem;">Access denied</h1><p style="margin:0;font-size:.95rem;line-height:1.6;">This document link is only available to the coordinator or another authorized user.</p></div></body></html>');
// Closes the role check.
}

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
$fileName = basename((string) ($upload['fileName'] ?? 'Document'));
// Reads the stored MIME type, or detects it from the file itself.
$mimeType = (string) ($upload['mimeType'] ?? '');
// Detects the type from the file when the database has no MIME type.
if ($mimeType === '') {
    // Uses the detected type, or a generic binary type as the last fallback.
    $mimeType = (string) (mime_content_type($path) ?: 'application/octet-stream');
// Closes the MIME fallback check.
}
// Reads the lowercase extension for the viewer decision.
$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
// Treats the file as a Word .docx when its extension or MIME type says so.
$isDocx = ($extension === 'docx') || str_contains(strtolower($mimeType), 'wordprocessingml');
// Serves the original bytes when the viewer asks for the raw file.
$isRaw = ($_GET['raw'] ?? '') === '1';
// Serves the original bytes as a download when requested.
$isDownload = ($_GET['download'] ?? '') === '1';

// Streams the original file for non-Word documents and for raw/download requests.
if ($isRaw || $isDownload || !$isDocx) {
    // Uses attachment only when a download was explicitly requested.
    $disposition = $isDownload ? 'attachment' : 'inline';
    // Clears any output buffering so the file bytes are not corrupted.
    if (ob_get_level()) {
        // Ends every active output buffer.
        ob_end_clean();
    // Closes the output buffer cleanup check.
    }
    // Sends the original file type and size to the browser.
    header('Content-Type: ' . $mimeType);
    // Tells the browser the exact size of the original file.
    header('Content-Length: ' . filesize($path));
    // Sends the original file name and lets the browser show or download the file.
    header('Content-Disposition: ' . $disposition . '; filename="' . addcslashes($fileName, "\\\"") . '"');
    // Stops the browser from guessing a different content type.
    header('X-Content-Type-Options: nosniff');
    // Prevents browsers and proxies from caching the private file.
    header('Cache-Control: private, max-age=0, must-revalidate');
    // Sends the original file bytes without changing the content or formatting.
    readfile($path);
    // Ends the script so nothing else is appended to the file.
    exit;
// Closes the original-file branch.
}

// Builds the internal address used to fetch the original Word bytes.
$rawUrl = url('share_document.php?token=' . rawurlencode($token) . '&raw=1');
// Builds the address that downloads the original Word file.
$downloadUrl = url('share_document.php?token=' . rawurlencode($token) . '&download=1');
// Closes PHP so the Word viewer page can be printed.
?>
<?php /* Declares the viewer as an HTML5 page. */ ?>
<!DOCTYPE html>
<?php /* Sets the page language to English. */ ?>
<html lang="en">
<?php /* Starts the head section with the page title and viewer styles. */ ?>
<head>
<?php /*     Tells the browser to use UTF-8 so document text shows correctly. */ ?>
    <meta charset="UTF-8">
<?php /*     Makes the viewer fit mobile and desktop screen widths. */ ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php /*     Keeps the shared page out of search engines. */ ?>
    <meta name="robots" content="noindex, nofollow">
<?php /*     Sets the browser tab title to the document name. */ ?>
    <title>View Document - <?= htmlspecialchars($fileName) ?></title>
<?php /*     Loads the Font Awesome icons used in the header. */ ?>
    <link rel="stylesheet" href="<?= asset('vendor/fontawesome/all.min.css') ?>">
<?php /*     Starts the viewer styles. */ ?>
    <style>
<?php /*         Resets spacing and uses a clean system font. */ ?>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, -apple-system, sans-serif; }
<?php /*         Gives the viewer a light grey desk background. */ ?>
        body { background: #e5e7eb; color: #1e293b; min-height: 100vh; }
<?php /*         Makes the top bar stay visible while scrolling the document. */ ?>
        .viewer-header { position: sticky; top: 0; z-index: 20; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.75rem 1.5rem; background: #2d2d2d; color: #fff; border-bottom: 1px solid #3f3f3f; min-height: 57px; flex-wrap: wrap; }
<?php /*         Lays out the file icon and file name in a flexible row. */ ?>
        .file-info { display: flex; align-items: center; gap: 0.75rem; overflow: hidden; min-width: 0; }
<?php /*         Styles the file name, cutting it off with an ellipsis when long. */ ?>
        .file-name { font-weight: 600; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
<?php /*         Lays out the header actions. */ ?>
        .viewer-actions { display: flex; align-items: center; gap: 0.5rem; }
<?php /*         Styles the blue download button. */ ?>
        .btn-download { display: inline-flex; align-items: center; gap: 0.5rem; background: #2563eb; color: #fff; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.875rem; font-weight: 500; transition: background 0.2s; }
<?php /*         Darkens the download button on hover. */ ?>
        .btn-download:hover { background: #1d4ed8; }
<?php /*         Holds the rendered Word pages and scrolls sideways when a page is wide. */ ?>
        #docxContainer { padding: 0; overflow-x: auto; }
<?php /*         Shows a centred status line while the document renders. */ ?>
        .docx-status { display: flex; align-items: center; justify-content: center; gap: 0.6rem; min-height: 60vh; padding: 2rem; color: #475569; font-size: 0.95rem; text-align: center; }
<?php /*         Keeps the rendered pages on the grey desk with their original page size. */ ?>
        #docxContainer .docx-wrapper { background: #e5e7eb !important; padding: 24px 16px 40px !important; overflow-x: auto; }
<?php /*         Adds a soft shadow around each rendered page. */ ?>
        #docxContainer .docx-wrapper > section.docx { box-shadow: 0 4px 24px rgba(15, 23, 42, 0.18); margin-bottom: 24px; }
<?php /*         Matches the viewer colours when the browser prints the page. */ ?>
        @media print { .viewer-header { display: none; } body { background: #fff; } #docxContainer .docx-wrapper { background: #fff !important; padding: 0 !important; } #docxContainer .docx-wrapper > section.docx { box-shadow: none; margin: 0; } }
<?php /*     Closes the viewer styles. */ ?>
    </style>
<?php /* Closes the head section. */ ?>
</head>
<?php /* Starts the visible page body. */ ?>
<body>

<?php /*     Starts the top bar of the viewer. */ ?>
    <header class="viewer-header">
<?php /*         Groups the file icon and file name together. */ ?>
        <div class="file-info">
<?php /*             Shows a Word document icon. */ ?>
            <i class="fas fa-file-word" style="color: #60a5fa;"></i>
<?php /*             Shows the original document name. */ ?>
            <span class="file-name"><?= htmlspecialchars($fileName) ?></span>
<?php /*         Closes the file info group. */ ?>
        </div>
<?php /*         Holds the download fallback for the original file. */ ?>
        <div class="viewer-actions">
<?php /*             Downloads the untouched original Word file. */ ?>
            <a href="<?= $downloadUrl ?>" class="btn-download"><i class="fas fa-download"></i> Download Original</a>
<?php /*         Closes the header actions. */ ?>
        </div>
<?php /*     Closes the viewer top bar. */ ?>
    </header>

<?php /*     Shows the loading status until the document finishes rendering. */ ?>
    <div id="docxStatus" class="docx-status"><i class="fas fa-spinner fa-spin"></i> Rendering the original document…</div>
<?php /*     Holds the rendered Word pages. */ ?>
    <div id="docxContainer"></div>

<?php /*     Loads the zip reader that opens the original .docx package. */ ?>
    <script src="<?= asset('vendor/jszip.min.js?v=' . filemtime(__DIR__ . '/assets/vendor/jszip.min.js')) ?>"></script>
<?php /*     Loads the renderer that keeps the Word page layout and styling. */ ?>
    <script src="<?= asset('vendor/docx-preview.min.js?v=' . filemtime(__DIR__ . '/assets/vendor/docx-preview.min.js')) ?>"></script>
<?php /*     Starts the script that renders the original Word document. */ ?>
    <script>
<?php /*     Runs the renderer inside a self-starting function. */ ?>
    (async function () {
<?php /*         Gets the status line and the page container. */ ?>
        var status = document.getElementById('docxStatus');
<?php /*         Gets the container where the pages are inserted. */ ?>
        var container = document.getElementById('docxContainer');
<?php /*         Fetches the untouched original .docx bytes from the protected endpoint. */ ?>
        try {
<?php /*             Requests the original file with the current coordinator session. */ ?>
            var response = await fetch(<?= json_encode($rawUrl) ?>, { credentials: 'same-origin' });
<?php /*             Treats a login redirect or server error as a failed load. */ ?>
            if (!response.ok || response.redirected) throw new Error('HTTP ' + response.status);
<?php /*             Reads the original file as binary data. */ ?>
            var buffer = await response.arrayBuffer();
<?php /*             Renders the original Word pages with their layout and formatting. */ ?>
            await docx.renderAsync(buffer, container, null, {
<?php /*                 Uses the docx class prefix for the generated styles. */ ?>
                className: 'docx',
<?php /*                 Keeps the page wrapper and original page size. */ ?>
                inWrapper: true,
<?php /*                 Keeps manual and Word page breaks. */ ?>
                breakPages: true,
<?php /*                 Uses the page breaks Word already recorded. */ ?>
                ignoreLastRenderedPageBreak: false,
<?php /*                 Embeds images and fonts directly so nothing is missing. */ ?>
                useBase64URL: true,
<?php /*                 Keeps headers, footers, footnotes and endnotes. */ ?>
                renderHeaders: true,
<?php /*                 Keeps footers in the rendered pages. */ ?>
                renderFooters: true,
<?php /*                 Keeps footnotes in the rendered pages. */ ?>
                renderFootnotes: true,
<?php /*                 Keeps endnotes in the rendered pages. */ ?>
                renderEndnotes: true,
<?php /*                 Does not render embedded HTML parts from the file. */ ?>
                renderAltChunks: false,
<?php /*                 Enables the closest tab-stop calculation. */ ?>
                experimental: true
<?php /*             Closes the renderer options. */ ?>
            });
<?php /*             Makes document links safe after the pages are rendered. */ ?>
            container.querySelectorAll('a[href]').forEach(function (link) {
<?php /*                 Reads the link target from the rendered document. */ ?>
                var href = link.getAttribute('href') || '';
<?php /*                 Keeps only ordinary web, mail and in-page links. */ ?>
                if (!/^(https?:|mailto:|#)/i.test(href)) {
<?php /*                     Removes any script or data link from the document. */ ?>
                    link.removeAttribute('href');
<?php /*                 Closes the unsafe-link check. */ ?>
                }
<?php /*                 Opens safe links in a separate tab without control of this page. */ ?>
                link.setAttribute('target', '_blank');
<?php /*                 Blocks the new tab from reaching back into this page. */ ?>
                link.setAttribute('rel', 'noopener noreferrer');
<?php /*             Closes the rendered-link cleanup. */ ?>
            });
<?php /*             Hides the loading status after a successful render. */ ?>
            status.style.display = 'none';
<?php /*         Shows a clear fallback when the document cannot be rendered. */ ?>
        } catch (error) {
<?php /*             Replaces the status with an explanation and download link. */ ?>
            status.innerHTML = 'This document could not be displayed in the browser. <a href="<?= $downloadUrl ?>" style="color:#2563eb;font-weight:600;">Download the original file</a> to view it in Word.';
<?php /*         Closes the render error handler. */ ?>
        }
<?php /*     Closes the renderer function. */ ?>
    })();
<?php /*     Closes the renderer script. */ ?>
    </script>

<?php /* Closes the page body. */ ?>
</body>
<?php /* Closes the HTML document. */ ?>
</html>
