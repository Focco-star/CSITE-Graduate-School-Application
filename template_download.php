<?php
// Captures any BOM or stray output from included files so the file stays intact.
ob_start();
// Serves a template file and re-fetches it from Google Drive when the local copy is missing.
require_once __DIR__ . '/includes/config/config.php';
require_once __DIR__ . '/includes/config/db.php';

// Starts the session when it is not running yet.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so the right template page can be used on errors.
    session_start();
// Closes the session check.
}

// Reads the requested template id.
$templateId = (int) ($_GET['id'] ?? 0);
// Starts with no template row.
$template = null;
// Guards the lookup against database errors.
try {
    // Loads only an active template.
    $template = DB::find('templates', ['template_id' => $templateId, 'is_active' => 1]);
// Catches a database failure and treats it as a missing template.
} catch (Throwable $e) {
    // Leaves the template empty.
    $template = null;
// Closes the lookup guard.
}

// Sends the user back to the template page with a readable error.
function templateDownloadFail(string $message): void {
    // Discards any buffered BOM or notices before redirecting.
    while (ob_get_level() > 0) {
        // Removes the buffered output.
        ob_end_clean();
    // Closes the buffer cleanup loop.
    }
    // Gets the signed-in role.
    $role = (string) ($_SESSION['user']['role'] ?? '');
    // Chooses the coordinator library or the student library.
    $target = $role === 'coordinator' ? 'coordinator/templates/manage.php' : 'student/templates.php';
    // Queues the error message for the next page.
    setFlash('error', $message);
    // Returns to the library.
    redirectTo($target);
// Closes the failure helper.
}

// Rejects an unknown or inactive template.
if (!$template) {
    // Explains that the template does not exist.
    templateDownloadFail('That template could not be found. Please refresh the Templates page and try again.');
// Closes the missing-template check.
}

// Builds the absolute path of the stored template file.
$filePath = templateAbsolutePath((string) $template['file_path']);
// Reads the Drive file id used for a missing local copy.
$driveId = trim((string) ($template['drive_file_id'] ?? ''));

// Checks whether the local copy exists.
if (!is_file($filePath)) {
    // Starts a guarded block for the Drive re-download.
    try {
        // Reads the stored file name for the extension check.
        $fileName = (string) ($template['file_name'] ?? basename($filePath));
        // Creates the destination folder when it does not exist.
        $folder = dirname($filePath);
        // Creates the folder when needed.
        if (!is_dir($folder)) {
            // Makes the folder with normal permissions.
            @mkdir($folder, 0755, true);
        // Closes the folder creation check.
        }
        // Only tries Drive when the row still has a file id.
        if ($driveId !== '') {
            // Tries the normal binary download first.
            if (gdriveDownloadFile($driveId, $filePath, '')) {
                // Checks whether the response is a real Office/PDF file.
                if (!templateDownloadLooksValid($filePath, $fileName)) {
                    // Removes an HTML error page saved under the template name.
                    @unlink($filePath);
                // Closes the file-content check.
                }
            // Closes the binary download check.
            }
            // Tries the Google export when the direct file is not usable.
            if (!is_file($filePath)) {
                // Reads the extension to choose the export type.
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                // Maps Office extensions to Google export types.
                $googleType = ['docx' => 'document', 'xlsx' => 'spreadsheet', 'pptx' => 'presentation'][$extension] ?? '';
                // Downloads the exported Google file when a type was found.
                if ($googleType !== '') {
                    // Runs the export download.
                    gdriveDownloadFile($driveId, $filePath, $googleType);
                // Closes the export-type check.
                }
            // Closes the export fallback check.
            }
        // Closes the Drive-id check.
        }
    // Catches a download error and lets the failure message below run.
    } catch (Throwable $e) {
        // Leaves the file missing.
    // Closes the re-download guard.
    }
// Closes the missing-file check.
}

// Rejects the download when the file is still missing.
if (!is_file($filePath)) {
    // Explains how the file can be restored.
    templateDownloadFail('The template file is not available right now. Please ask the coordinator to synchronize Google Drive, then try again.');
// Closes the missing-after-download check.
}

// Reads the original file name, falling back to the stored path.
$downloadName = trim((string) ($template['file_name'] ?? ''));
// Falls back to the base name when the row has no file name.
if ($downloadName === '') {
    // Uses the stored file's base name.
    $downloadName = basename($filePath);
// Closes the empty-name fallback.
}
// Reads the stored MIME type, falling back to a generic binary type.
$downloadType = trim((string) ($template['mime_type'] ?? ''));
// Uses a safe default when the type is missing.
if ($downloadType === '') {
    // Falls back to a generic file type.
    $downloadType = 'application/octet-stream';
// Closes the MIME fallback.
}

// Removes characters that would break the Content-Disposition header.
$asciiName = preg_replace('/[\r\n"\\\\]+/', '', $downloadName);
// Discards any buffered BOM or notices before sending the file.
while (ob_get_level() > 0) {
    // Removes the buffered output.
    ob_end_clean();
// Closes the buffer cleanup loop.
}
// Sends the file type to the browser.
header('Content-Type: ' . $downloadType);
// Sends the file name, with an encoded version for spaces and special characters.
header('Content-Disposition: attachment; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
// Sends the exact file size.
header('Content-Length: ' . filesize($filePath));
// Stops the browser from guessing a different type.
header('X-Content-Type-Options: nosniff');
// Keeps the download out of shared caches.
header('Cache-Control: private, max-age=0, must-revalidate');
// Streams the file to the browser.
readfile($filePath);
// Stops after sending the file.
exit;

/**
 * Whether a downloaded file starts with the signature its extension expects.
 */
function templateDownloadLooksValid(string $path, string $fileName): bool {
    // Rejects a missing or tiny file.
    if (!is_file($path) || filesize($path) < 100) {
        // Reports an unusable file.
        return false;
    // Closes the size check.
    }
    // Reads the file signature.
    $handle = @fopen($path, 'rb');
    // Rejects a file that cannot be opened.
    if (!$handle) {
        // Reports an unreadable file.
        return false;
    // Closes the open check.
    }
    // Reads the first eight bytes.
    $signature = (string) fread($handle, 8);
    // Closes the handle.
    fclose($handle);
    // Reads the lower-case extension.
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    // Office Open XML files are ZIP archives.
    if (in_array($extension, ['docx', 'xlsx', 'pptx'], true)) {
        // Checks the ZIP signature.
        return str_starts_with($signature, "PK\x03\x04");
    // Closes the Office check.
    }
    // PDF files start with %PDF.
    if ($extension === 'pdf') {
        // Checks the PDF signature.
        return str_starts_with($signature, '%PDF');
    // Closes the PDF check.
    }
    // Legacy Word files start with the OLE signature.
    if ($extension === 'doc') {
        // Checks the OLE signature.
        return str_starts_with($signature, "\xD0\xCF\x11\xE0");
    // Closes the legacy Word check.
    }
    // Accepts other file types when the response is not empty.
    return $signature !== '';
// Closes the signature check.
}
