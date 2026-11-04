<?php /* Starts the PHP block that defines the read-only Google Drive helpers. */ ?>
<?php
/**
 * Google Drive client for the CSITE template library.
 * ------------------------------------------------------------------
 * The teacher's template folder is published with "Anyone with the link",
 * so the portal can read the folder listing and download the files
 * without a Google account, a service account or an API key.
 *
 * Everything in here is read-only: the portal never writes to Drive.
 *
 * The Drive API itself refuses anonymous callers, so the client reads the
 * public folder page instead. That page carries the file id and the name of
 * every entry in the folder, and every file can be fetched through the
 * normal download endpoint.
 *
 * A HEAD request on the download endpoint returns the "Last-Modified" and
 * "Content-Length" of the file, which is all the portal needs to know
 * whether a file changed - no download required for the check.
 */

// Defines the official Google Drive folder id of each graduate school track.
if (!defined('GDRIVE_TEMPLATE_FOLDERS')) {
    // Maps each track code to the Google Drive folder the coordinator manages.
    define('GDRIVE_TEMPLATE_FOLDERS', [
        // Capstone forms and templates folder.
        'capstone' => '1yKy8MWnNP5fYpBlJuujPyNZtVjTJO8ul',
        // Seminar Paper forms and templates folder.
        'seminar'  => '1ApUzCqlldk4OffQ_l8uOyH-lwq52F_wn',
        // Thesis forms and templates folder.
        'thesis'   => '1oIqNK3LNe5YqBDuOjVXGW19rNiphHjCD',
    // Closes the track folder map.
    ]);
// Closes the check that defines the track folder map.
}

// Keeps the official folder links exactly as the coordinator supplied them.
if (!defined('GDRIVE_TEMPLATE_FOLDER_LINKS')) {
    // Maps each track to its official Google Drive folder link.
    define('GDRIVE_TEMPLATE_FOLDER_LINKS', [
        // Capstone folder link, including the coordinator's Google account slot.
        'capstone' => 'https://drive.google.com/drive/u/2/folders/1yKy8MWnNP5fYpBlJuujPyNZtVjTJO8ul',
        // Seminar Paper folder link.
        'seminar'  => 'https://drive.google.com/drive/u/2/folders/1ApUzCqlldk4OffQ_l8uOyH-lwq52F_wn',
        // Thesis folder link.
        'thesis'   => 'https://drive.google.com/drive/u/2/folders/1oIqNK3LNe5YqBDuOjVXGW19rNiphHjCD',
    // Closes the official folder link map.
    ]);
// Closes the official folder link definition.
}

// Keeps the old single-folder constant for any older code that still reads it.
if (!defined('GDRIVE_TEMPLATE_FOLDER_ID')) {
    // Falls back to the thesis folder, which is the default track.
    define('GDRIVE_TEMPLATE_FOLDER_ID', GDRIVE_TEMPLATE_FOLDERS['thesis']);
// Closes the check that defines the old template folder id.
}

// Returns the Drive folder id of one track.
function gdriveTemplateFolderId(string $track): string {
    // Reads the lowercase track code.
    $track = strtolower(trim($track));
    // Returns the folder id, or empty text for an unknown track.
    return GDRIVE_TEMPLATE_FOLDERS[$track] ?? '';
// Closes the gdriveTemplateFolderId function.
}

// Returns the public Drive folder URL of one track.
function gdriveTemplateFolderUrl(string $track): string {
    // Reads the folder id of the track.
    $folderId = gdriveTemplateFolderId($track);
    // Returns an empty URL when the track has no folder.
    if ($folderId === '') {
        // Returns empty text for an unknown track.
        return '';
    // Closes the empty folder check.
    }
    // Returns the public folder URL.
    return gdriveFolderUrl($folderId);
// Closes the gdriveTemplateFolderUrl function.
}

// Returns the official Drive folder links of every track.
function gdriveTemplateFolderLinks(): array {
    // Returns the exact links supplied by the coordinator.
    return GDRIVE_TEMPLATE_FOLDER_LINKS;
// Closes the gdriveTemplateFolderLinks function.
}

/** Browser user agent: Drive answers a plain script with an error page. */
// Declares the function that returns the browser user agent string.
function gdriveUserAgent(): string {
    // Returns a Chrome user agent because Drive rejects plain script requests.
    return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';
// Closes the gdriveUserAgent() function.
}

/** Direct download endpoint for a Drive file. */
// Declares the function that builds a direct download URL for a file id.
function gdriveDownloadUrl(string $fileId): string {
    // Returns the Drive download URL with the id URL-encoded.
    return 'https://drive.google.com/uc?export=download&id=' . rawurlencode($fileId);
// Closes the gdriveDownloadUrl() function.
}

/**
 * The download host answers with the file headers straight away, while the
 * drive.google.com address first answers with a redirect. The header check
 * uses this one because it saves a round trip per file.
 */
// Declares the function that builds the header-check URL for a file id.
function gdriveHeaderUrl(string $fileId): string {
    // Returns the usercontent download URL used for HEAD requests.
    return 'https://drive.usercontent.google.com/download?id=' . rawurlencode($fileId) . '&export=download';
// Closes the gdriveHeaderUrl() function.
}

// Returns the export URL of a Google Docs, Sheets, or Slides file.
function gdriveGoogleExportUrl(string $fileId, string $googleType): string {
    // Reads the lowercase Google file type.
    $type = strtolower(trim($googleType));
    // Builds the export URL for a Google Doc.
    if ($type === 'document') {
        // Exports the document as a Word file.
        return 'https://docs.google.com/document/d/' . rawurlencode($fileId) . '/export?format=docx';
    // Closes the Google Doc branch.
    }
    // Builds the export URL for a Google Sheet.
    if ($type === 'spreadsheet') {
        // Exports the sheet as an Excel file.
        return 'https://docs.google.com/spreadsheets/d/' . rawurlencode($fileId) . '/export?format=xlsx';
    // Closes the Google Sheet branch.
    }
    // Builds the export URL for Google Slides.
    if ($type === 'presentation') {
        // Exports the slides as a PowerPoint file.
        return 'https://docs.google.com/presentation/d/' . rawurlencode($fileId) . '/export?format=pptx';
    // Closes the Google Slides branch.
    }
    // Returns empty text for an unknown Google file type.
    return '';
// Closes the gdriveGoogleExportUrl function.
}

// Returns the local extension used for an exported Google file.
function gdriveGoogleExtension(string $googleType): string {
    // Maps Google file types to their exported extensions.
    $extensions = ['document' => 'docx', 'spreadsheet' => 'xlsx', 'presentation' => 'pptx'];
    // Returns the extension, or empty text for a normal Drive file.
    return $extensions[strtolower(trim($googleType))] ?? '';
// Closes the gdriveGoogleExtension function.
}

// Reads the file extension hint from a folder row.
function gdriveFileExtensionFromRow(string $body): string {
    // Reads the tooltip text that names the Drive file type.
    if (preg_match('/data-tooltip="([^"]+)"/', $body, $m)) {
        // Decodes the tooltip into plain text and lowercases it.
        $tooltip = strtolower(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        // Maps the file type words to their normal extension.
        $types = [
            'google docs'        => 'docx',
            'google sheets'      => 'xlsx',
            'google slides'      => 'pptx',
            'microsoft word'     => 'docx',
            'microsoft excel'    => 'xlsx',
            'microsoft powerpoint' => 'pptx',
            'pdf document'       => 'pdf',
            'pdf'                => 'pdf',
        ];
        // Loops over the known type words.
        foreach ($types as $word => $extension) {
            // Returns the extension when the tooltip mentions this type.
            if (str_contains($tooltip, $word)) {
                // Returns the extension for the detected file type.
                return $extension;
            // Closes the type-word check.
            }
        // Closes the loop over type words.
        }
    // Closes the tooltip check.
    }
    // Returns empty text when the row gives no type hint.
    return '';
// Closes the gdriveFileExtensionFromRow function.
}

// Reads the Google file type from a folder row.
function gdriveGoogleTypeFromRow(string $body): string {
    // Reads the tooltip text that names the Drive file type.
    if (preg_match('/data-tooltip="([^"]+)"/', $body, $m)) {
        // Decodes the tooltip into plain text and lowercases it.
        $tooltip = strtolower(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        // Detects Google Docs files.
        if (str_contains($tooltip, 'google docs')) {
            // Returns the Google Doc type.
            return 'document';
        // Closes the Google Docs check.
        }
        // Detects Google Sheets files.
        if (str_contains($tooltip, 'google sheets')) {
            // Returns the Google Sheet type.
            return 'spreadsheet';
        // Closes the Google Sheets check.
        }
        // Detects Google Slides files.
        if (str_contains($tooltip, 'google slides')) {
            // Returns the Google Slides type.
            return 'presentation';
        // Closes the Google Slides check.
        }
    // Closes the tooltip check.
    }
    // Returns empty text for a normal Drive file.
    return '';
// Closes the gdriveGoogleTypeFromRow function.
}

/** Public folder page for a Drive folder. */
// Declares the function that builds the public folder page URL.1q
function gdriveFolderUrl(string $folderId): string {
    // Returns the Drive folder URL for the given folder id.
    return 'https://drive.google.com/drive/folders/' . rawurlencode($folderId);
// Closes the gdriveFolderUrl() function.
}

/**
 * GET a URL and return the body (empty string on failure).
 */
// Declares the function that GETs a URL and returns the body text.2q   
function gdriveHttpGet(string $url, int $timeout = 25): string {
    // Checks whether the curl extension is available to use first.
    if (function_exists('curl_init')) {
        // Starts a new curl handle for the request.
        $ch = curl_init();
        // Starts the array of curl options for this request.
        curl_setopt_array($ch, [
            // Sets the URL that curl should request.
            CURLOPT_URL            => $url,
            // Asks curl to return the response body as a string.
            CURLOPT_RETURNTRANSFER => true,
            // Tells curl to follow HTTP redirects automatically.
            CURLOPT_FOLLOWLOCATION => true,
            // Limits redirects to five to avoid endless loops.
            CURLOPT_MAXREDIRS      => 5,
            // Sets the total request timeout in seconds.
            CURLOPT_TIMEOUT        => $timeout,
            // Sets the connection timeout to ten seconds.
            CURLOPT_CONNECTTIMEOUT => 10,
            // Sends the browser user agent with the request.
            CURLOPT_USERAGENT      => gdriveUserAgent(),
            // Lets curl request and decode compressed responses.
            CURLOPT_ENCODING       => '',
            // Keeps SSL certificate verification on for safety.
            CURLOPT_SSL_VERIFYPEER => true,
        // Closes the curl options array and the setopt call.
        ]);
        // Executes the request and stores the body.
        $body = curl_exec($ch);
        // Reads the curl error number after the request.
        $err  = curl_errno($ch);
        // Closes the curl handle and frees memory.
        curl_close($ch);
        // Returns the body only when there was no error.
        return ($err === 0 && is_string($body)) ? $body : '';
    // Closes the curl branch of gdriveHttpGet().
    }

    // Comment: this is the fallback when curl is not installed.
    // Fallback when the curl extension is not available.
    // Creates a stream context for a plain PHP HTTP request.
    $ctx = stream_context_create([
        // Opens the http section of the stream context options.
        'http' => [
            // Sets the timeout for the plain HTTP request.
            'timeout'         => $timeout,
            // Sets the browser user agent for the request.
            'user_agent'      => gdriveUserAgent(),
            // Allows the request to follow redirects.
            'follow_location' => 1,
            // Limits redirects to five in the fallback too.
            'max_redirects'   => 5,
        // Closes the http options array.
        ],
    // Closes the stream context options array.
    ]);
    // Fetches the URL with file_get_contents using the context.
    $body = @file_get_contents($url, false, $ctx);
    // Returns the body text, or an empty string when it failed.
    return is_string($body) ? $body : '';
// Closes the gdriveHttpGet() function.
}

/**
 * Pull the name of one entry out of a Drive folder row.
 *
 * A file row shows the name as a plain text node ending in the document
 * extension; a folder row shows it in a <strong> element.
 */
// Declares the function that extracts one entry name from a folder row.
function gdriveRowName(string $body): string {
    // Removes inline SVG icons so they do not pollute the name text.
    $clean = preg_replace('/<svg\b.*?<\/svg>/s', ' ', $body);
    // Replaces HTML tags with a marker so the text pieces can be split.
    $clean = preg_replace('/<[^>]+>/', "\x01", (string) $clean);
    // Decodes HTML entities such as &amp; into real characters.
    $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Loops over the text pieces separated by the tag marker.
    foreach (explode("\x01", $clean) as $part) {
        // Collapses whitespace and trims the piece.
        $part = trim(preg_replace('/\s+/', ' ', (string) $part));
        // Accepts a piece that ends in a known document extension.
        if ($part !== '' && preg_match('/\.(docx?|pdf|xlsx?|pptx?|odt|ods)$/i', $part)) {
            // Returns the file name found in the row.
            return $part;
        // Closes the check that the piece looks like a file name.
        }
    // Closes the loop over text pieces.
    }

    // Looks for a folder name inside a strong element.
    if (preg_match('/<strong[^>]*>(.*?)<\/strong>/s', $body, $m)) {
        // Cleans the matched text by stripping tags and decoding entities.
        $name = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        // Checks that the folder name is not empty.
        if ($name !== '') {
            // Returns the folder name.
            return $name;
        // Closes the non-empty folder name check.
        }
    // Closes the strong-element folder name check.
    }

    // Looks for a name in the data-tooltip attribute.
    if (preg_match('/data-tooltip="([^"]+)"/', $body, $m)) {
        // Decodes the tooltip text into a plain name.
        $name = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        // Removes the trailing Shared folder wording from the name.
        $name = preg_replace('/\s+Shared folder$/i', '', $name);
        // Checks that the tooltip name is not empty.
        if ($name !== '') {
            // Returns the tooltip name.
            return $name;
        // Closes the non-empty tooltip name check.
        }
    // Closes the data-tooltip check.
    }

    // Returns an empty string when no name could be found.
    return '';
// Closes the gdriveRowName() function.
}

/**
 * Turn a size written the way Drive writes it ("3.8 MB") into bytes.
 */
// Declares the function that converts a Drive size text to bytes.
function gdriveParseSize(string $text): int {
    // Trims surrounding spaces from the size text.
    $text = trim($text);
    // Matches a number with an optional unit such as MB or GB.
    if ($text === '' || !preg_match('/^([0-9]+(?:\.[0-9]+)?)\s*(B|KB|MB|GB|TB|Bytes)?$/i', $text, $m)) {
        // Returns zero when the text is empty or not a size.
        return 0;
    // Closes the invalid size check.
    }
    // Reads the numeric part of the size as a float.
    $value = (float) $m[1];
    // Reads the unit, defaulting to bytes, and lowercases it.
    $unit  = strtolower((string) ($m[2] ?? 'b'));
    // Starts the map of unit names to byte multipliers.
    $factors = [
        // Maps byte spellings to a multiplier of one.
        'b' => 1, 'byte' => 1, 'bytes' => 1,
        // Maps KB, MB, GB and TB to their byte multipliers.
        'kb' => 1024, 'mb' => 1048576, 'gb' => 1073741824, 'tb' => 1099511627776,
    // Closes the unit multiplier map.
    ];
    // Multiplies the value by the unit factor and rounds to bytes.
    return (int) round($value * ($factors[$unit] ?? 1));
// Closes the gdriveParseSize() function.
}

/**
 * The size of one entry of a Drive folder, in bytes.
 *
 * The size column of the folder page carries it, which is a cheap second
 * signal for the files whose header request did not come back.
 */
// Declares the function that reads an entry's size from a folder row.
function gdriveRowSize(string $body): int {
    // Looks for the size text inside the aria-label attribute.
    if (preg_match('/aria-label="Size:\s*([^"]*)"/i', $body, $m)) {
        // Parses the first line of the size label into bytes.
        $size = gdriveParseSize(trim(explode("\n", $m[1])[0]));
        // Checks that a usable positive size was found.
        if ($size > 0) {
            // Returns the size in bytes.
            return $size;
        // Closes the usable size check.
        }
    // Closes the aria-label size check.
    }
    // Looks for the size in the table cell with column field 3.
    if (preg_match('/data-column-field="3"[^>]*>(.*?)<\/td>/s', $body, $m)) {
        // Removes inline SVG icons from the cell HTML.
        $text = preg_replace('/<svg\b.*?<\/svg>/s', ' ', $m[1]);
        // Replaces the remaining tags with spaces.
        $text = preg_replace('/<[^>]+>/', ' ', (string) $text);
        // Collapses whitespace and decodes HTML entities.
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        // Returns the parsed size from that cell text.
        return gdriveParseSize($text);
    // Closes the table cell size check.
    }
    // Returns zero when neither size source worked.
    return 0;
// Closes the gdriveRowSize() function.
}

/**
 * Parse a Drive folder page into its entries.
 *
 * @return array<int, array{id:string,name:string,isFolder:bool,size:int}>
 */
// Declares the function that turns a folder page into a list of entries.3q
function gdriveParseFolderHtml(string $html): array {
    // Starts with an empty list of entries.
    $entries = [];
    // Handles an empty page body.
    if ($html === '') {
        // Returns the empty list straight away.
        return $entries;
    // Closes the empty body check.
    }

    // Finds every table row that carries a Drive file id.
    if (preg_match_all('/<tr\b[^>]*\bdata-id="([A-Za-z0-9_-]{25,50})"[^>]*>(.*?)<\/tr>/s', $html, $matches, PREG_SET_ORDER)) {
        // Loops over each matched row.
        foreach ($matches as $row) {
            // Reads the entry id from the matched row.
            $id   = $row[1];
            // Reads the row HTML into $body.
            $body = $row[2];
            // Extracts the entry name from the row HTML.
            $name = gdriveRowName($body);
            // Checks whether a name was found.
            if ($name === '') {
                // Skips rows without a usable name.
                continue;
            // Closes the missing name check.
            }
            // Marks the entry as a folder when the row says Shared folder.
            $isFolder = (stripos($body, 'Shared folder') !== false);
            // Also treats rows whose tooltip mentions folder as folders.
            if (!$isFolder && preg_match('/data-tooltip="([^"]*)"/', $body, $t) && stripos($t[1], 'folder') !== false) {
                // Sets the folder flag when the tooltip check matched.
                $isFolder = true;
            // Closes the tooltip folder check.
            }
            // Adds the entry to the result list.
            $entries[] = [
                // Stores the entry id.
                'id'       => $id,
                // Stores the entry name.
                'name'     => $name,
                // Stores whether the entry is a folder.
                'isFolder' => $isFolder,
                // Stores the size, using zero for folders.
                'size'     => $isFolder ? 0 : gdriveRowSize($body),
                // Stores the Google Docs, Sheets, or Slides type, if any.
                'googleType' => $isFolder ? '' : gdriveGoogleTypeFromRow($body),
                // Stores the extension hint for files named without one.
                'fileExt' => $isFolder ? '' : gdriveFileExtensionFromRow($body),
            // Closes the entry array.
            ];
        // Closes the loop over matched rows.
        }
    // Closes the row matching check.
    }

    // Returns the list of parsed entries.
    return $entries;
// Closes the gdriveParseFolderHtml() function.
}

/**
 * List the entries of a public Drive folder.
 *
 * @return array<int, array{id:string,name:string,isFolder:bool,size:int}>
 */
// Declares the function that lists one public folder's entries.
function gdriveListFolder(string $folderId): array {
    // Downloads the folder page HTML.1q
    $html = gdriveHttpGet(gdriveFolderUrl($folderId));
    // Parses that HTML into entries and returns them.
    return gdriveParseFolderHtml($html);
// Closes the gdriveListFolder() function.
}

/**
 * List several folders at the same time.
 *
 * The template folder has one subfolder per track, and the pages are read
 * in parallel so the whole folder is known in about a second.
 *
 * @param  array<int,string> $folderIds
 * @return array<string, array<int, array{id:string,name:string,isFolder:bool,size:int}>>
 */
// Declares the function that lists several folders at once.
function gdriveListFolders(array $folderIds): array {
    // Cleans the id list by removing empties and duplicates.
    $folderIds = array_values(array_unique(array_filter($folderIds)));
    // Starts with an empty result map.
    $result    = [];

    // Checks whether there is anything to fetch.
    if ($folderIds === []) {
        // Returns the empty result when the list is empty.
        return $result;
    // Closes the empty list check.
    }

    // Falls back to one-by-one requests when curl_multi is missing.
    if (!function_exists('curl_multi_init')) {
        // Loops over each folder id.
        foreach ($folderIds as $id) {
            // Stores that folder's entries in the result.
            $result[$id] = gdriveListFolder($id);
        // Closes the one-by-one loop.
        }
        // Returns the collected results.
        return $result;
    // Closes the fallback branch.
    }

    // Creates the curl multi handle for parallel requests.
    $multi   = curl_multi_init();
    // Starts an empty map of curl handles.
    $handles = [];

    // Loops over each folder id to start a request.
    foreach ($folderIds as $id) {
        // Creates a curl handle for this folder.
        $ch = curl_init();
        // Starts the curl options array.
        curl_setopt_array($ch, [
            // Sets the folder page URL.
            CURLOPT_URL            => gdriveFolderUrl($id),
            // Asks curl to return the body as a string.
            CURLOPT_RETURNTRANSFER => true,
            // Follows redirects automatically.
            CURLOPT_FOLLOWLOCATION => true,
            // Limits redirects to five.
            CURLOPT_MAXREDIRS      => 5,
            // Sets the request timeout to 25 seconds.
            CURLOPT_TIMEOUT        => 25,
            // Sets the connection timeout to 10 seconds.
            CURLOPT_CONNECTTIMEOUT => 10,
            // Sends the browser user agent.
            CURLOPT_USERAGENT      => gdriveUserAgent(),
            // Accepts compressed responses.
            CURLOPT_ENCODING       => '',
            // Keeps SSL verification enabled.
            CURLOPT_SSL_VERIFYPEER => true,
        // Closes the curl options array.
        ]);
        // Adds the handle to the multi request group.
        curl_multi_add_handle($multi, $ch);
        // Remembers which folder id belongs to the handle.
        $handles[spl_object_id($ch)] = ['id' => $id, 'ch' => $ch];
    // Closes the loop that prepares the requests.
    }

    // Starts the running counter for the multi requests.
    $running = null;
    // Starts a do-while loop that keeps curl multi running.
    do {
        // Runs the next chunk of parallel transfers.
        curl_multi_exec($multi, $running);
        // Checks whether transfers are still running.
        if ($running > 0) {
            // Waits up to one second for network activity.
            curl_multi_select($multi, 1.0);
        // Closes the still-running check.
        }
    // Repeats until no transfers are running.
    } while ($running > 0);

    // Loops over each finished handle.
    foreach ($handles as $handle) {
        // Reads the response body from the handle.
        $html = curl_multi_getcontent($handle['ch']);
        // Stores the parsed entries, or an empty list on failure.
        $result[$handle['id']] = is_string($html) ? gdriveParseFolderHtml($html) : [];
        // Removes the handle from the multi group.
        curl_multi_remove_handle($multi, $handle['ch']);
        // Closes the curl handle.
        curl_close($handle['ch']);
    // Closes the loop over finished handles.
    }
    // Closes the curl multi handle.
    curl_multi_close($multi);

    // Returns the entries grouped by folder id.
    return $result;
// Closes the gdriveListFolders() function.
}

/**
 * Read the response headers of the download endpoint.
 *
 * @return array{modified:int,size:int,name:string}
 */
// Declares the function that parses raw response headers.
function gdriveParseHeadHeaders(string $raw): array {
    // Starts metadata with zero values and an empty name.
    $meta = ['modified' => 0, 'size' => 0, 'name' => ''];

    // Finds every Last-Modified header in the raw text.
    if (preg_match_all('/^Last-Modified:\s*(.+)$/mi', $raw, $m)) {
        // Converts the final Last-Modified date into a timestamp.
        $ts = strtotime(trim(end($m[1])));
        // Checks that the date converted successfully.
        if ($ts !== false) {
            // Stores the modification time as an integer.
            $meta['modified'] = (int) $ts;
        // Closes the conversion check.
        }
    // Closes the Last-Modified block.
    }
    // Finds every Content-Length header value.
    if (preg_match_all('/^Content-Length:\s*(\d+)/mi', $raw, $m)) {
        // Stores the last Content-Length as the size in bytes.
        $meta['size'] = (int) end($m[1]);
    // Closes the Content-Length check.
    }
    // Finds the file name in the Content-Disposition header.
    if (preg_match_all('/filename="([^"]*)"/i', $raw, $m)) {
        // Decodes the filename into plain text and trims it.
        $meta['name'] = trim(html_entity_decode((string) end($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    // Closes the filename check.
    }

    // Returns the collected modification time, size and name.
    return $meta;
// Closes the gdriveParseHeadHeaders() function.
}

/**
 * Read the modification time, the size and the file name of one Drive file.
 *
 * @return array{modified:int,size:int,name:string}
 */
// Declares the function that reads metadata for one Drive file.
function gdriveFileMeta(string $fileId): array {
    // Returns empty metadata when curl is not available.
    if (!function_exists('curl_init')) {
        // Gives zero values because the header request is impossible.
        return ['modified' => 0, 'size' => 0, 'name' => ''];
    // Closes the curl availability check.
    }

    // Creates a curl handle for the header request.
    $ch = curl_init();
    // Starts the curl options array.
    curl_setopt_array($ch, [
        // Sets the header-check URL for this file.
        CURLOPT_URL            => gdriveHeaderUrl($fileId),
        // Uses a HEAD request so no file body is downloaded.
        CURLOPT_NOBODY         => true,
        // Asks curl to include the response headers in the output.
        CURLOPT_HEADER         => true,
        // Returns the header text as a string.
        CURLOPT_RETURNTRANSFER => true,
        // Follows redirects to reach the file host.
        CURLOPT_FOLLOWLOCATION => true,
        // Limits redirects to five.
        CURLOPT_MAXREDIRS      => 5,
        // Sets the request timeout to 20 seconds.
        CURLOPT_TIMEOUT        => 20,
        // Sets the connection timeout to 10 seconds.
        CURLOPT_CONNECTTIMEOUT => 10,
        // Sends the browser user agent.
        CURLOPT_USERAGENT      => gdriveUserAgent(),
        // Keeps SSL verification enabled.
        CURLOPT_SSL_VERIFYPEER => true,
    // Closes the curl options array.
    ]);
    // Executes the HEAD request and stores the headers.
    $raw = curl_exec($ch);
    // Closes the curl handle.
    curl_close($ch);

    // Checks that the response text is usable.
    if (!is_string($raw) || $raw === '') {
        // Returns empty metadata when the request failed.
        return ['modified' => 0, 'size' => 0, 'name' => ''];
    // Closes the response check.
    }

    // Returns the parsed headers as metadata.
    return gdriveParseHeadHeaders($raw);
// Closes the gdriveFileMeta() function.
}

/**
 * Read the metadata of many Drive files at the same time.
 *
 * The requests run in parallel, so checking the whole template folder
 * stays well inside a second.
 *
 * @param  array<int,string> $fileIds
 * @return array<string, array{modified:int,size:int,name:string}>
 */
// Declares the function that reads metadata for many files at once.
function gdriveFilesMeta(array $fileIds): array {
    // Cleans the id list by removing empties and duplicates.
    $fileIds = array_values(array_unique(array_filter($fileIds)));
    // Starts with an empty result map.
    $result  = [];

    // Checks whether there is anything to fetch.
    if ($fileIds === []) {
        // Returns the empty result when the list is empty.
        return $result;
    // Closes the empty list check.
    }

    // Falls back to one-by-one header requests without curl_multi.
    if (!function_exists('curl_multi_init')) {
        // Loops over each file id.
        foreach ($fileIds as $id) {
            // Stores that file's metadata in the result.
            $result[$id] = gdriveFileMeta($id);
        // Closes the one-by-one loop.
        }
        // Returns the collected results.
        return $result;
    // Closes the fallback branch.
    }

    // Creates the curl multi handle for parallel header reads.
    $multi   = curl_multi_init();
    // Starts an empty map of curl handles.
    $handles = [];

    // Loops over each file id to start a request.
    foreach ($fileIds as $id) {
        // Creates a curl handle for this file.
        $ch = curl_init();
        // Starts the curl options array.
        curl_setopt_array($ch, [
            // Sets the header-check URL for this file.
            CURLOPT_URL            => gdriveHeaderUrl($id),
            // Uses a HEAD request so no file body is downloaded.
            CURLOPT_NOBODY         => true,
            // Asks curl to include the response headers.
            CURLOPT_HEADER         => true,
            // Returns the header text as a string.
            CURLOPT_RETURNTRANSFER => true,
            // Follows redirects to reach the file host.
            CURLOPT_FOLLOWLOCATION => true,
            // Limits redirects to five.
            CURLOPT_MAXREDIRS      => 5,
            // Sets the request timeout to 20 seconds.
            CURLOPT_TIMEOUT        => 20,
            // Sets the connection timeout to 10 seconds.
            CURLOPT_CONNECTTIMEOUT => 10,
            // Sends the browser user agent.
            CURLOPT_USERAGENT      => gdriveUserAgent(),
            // Keeps SSL verification enabled.
            CURLOPT_SSL_VERIFYPEER => true,
        // Closes the curl options array.
        ]);
        // Adds the handle to the multi request group.
        curl_multi_add_handle($multi, $ch);
        // Remembers which file id belongs to the handle.
        $handles[spl_object_id($ch)] = ['id' => $id, 'ch' => $ch];
    // Closes the loop that prepares the requests.
    }

    // Starts the running counter for the multi requests.
    $running = null;
    // Starts a do-while loop that keeps curl multi running.
    do {
        // Runs the next chunk of parallel transfers.
        curl_multi_exec($multi, $running);
        // Checks whether transfers are still running.
        if ($running > 0) {
            // Waits up to one second for network activity.
            curl_multi_select($multi, 1.0);
        // Closes the still-running check.
        }
    // Repeats until no transfers are running.
    } while ($running > 0);

    // Loops over each finished handle. Response
    foreach ($handles as $handle) {
        // Reads the raw header text from the handle.
        $raw = curl_multi_getcontent($handle['ch']);
        // Uses parsed headers when the response is usable.
        $result[$handle['id']] = is_string($raw) && $raw !== ''
            // Otherwise falls back to empty metadata values.
            ? gdriveParseHeadHeaders($raw)
            // Closes the metadata expression for this handle.
            : ['modified' => 0, 'size' => 0, 'name' => ''];
        // Removes the handle from the multi group.
        curl_multi_remove_handle($multi, $handle['ch']);
        // Closes the curl handle.
        curl_close($handle['ch']);
    // Closes the loop over finished handles.
    }
    // Closes the curl multi handle.
    curl_multi_close($multi);

    // Returns the metadata keyed by file id.
    return $result;
// Closes the gdriveFilesMeta() function.
}

/**
 * Download a Drive file to a local path.
 *
 * The file is written to a temporary name first and moved into place, so a
 * failed or interrupted download never replaces a good copy.
 */
// Declares the function that downloads a Drive file to a local path.
function gdriveDownloadFile(string $fileId, string $destination, string $googleType = ''): bool {
    // Returns false when curl is not available.
    if (!function_exists('curl_init')) {
        // Gives up because the download needs curl.
        return false;
    // Closes the curl availability check.
    }

    // Reads the folder part of the destination path.
    $folder = dirname($destination);
    // Creates the destination folder when it does not exist.
    if (!is_dir($folder) && !@mkdir($folder, 0755, true) && !is_dir($folder)) {
        // Returns false when the folder cannot be created.
        return false;
    // Closes the folder creation check.
    }

    // Builds a temporary .part file name for the download.
    $temp = $destination . '.part';

    // Chooses the Google export URL for Docs, Sheets, or Slides files.
    $downloadUrl = gdriveGoogleExportUrl($fileId, $googleType);
    // Falls back to the normal direct download for ordinary Drive files.
    if ($downloadUrl === '') {
        // Uses the normal download endpoint.
        $downloadUrl = gdriveDownloadUrl($fileId);
    // Closes the export URL fallback.
    }

    // Creates a curl handle for the download.
    $ch = curl_init();
    // Starts the curl options array.
    curl_setopt_array($ch, [
        // Sets the direct download or Google export URL for the file.
        CURLOPT_URL            => $downloadUrl,
        // Returns the file body as a string.
        CURLOPT_RETURNTRANSFER => true,
        // Follows redirects to the download host.
        CURLOPT_FOLLOWLOCATION => true,
        // Limits redirects to five.
        CURLOPT_MAXREDIRS      => 5,
        // Allows up to 120 seconds for the download.
        CURLOPT_TIMEOUT        => 120,
        // Sets the connection timeout to 15 seconds. 
        CURLOPT_CONNECTTIMEOUT => 15,
        // Sends the browser user agent.
        CURLOPT_USERAGENT      => gdriveUserAgent(),
        // Keeps SSL verification enabled.
        CURLOPT_SSL_VERIFYPEER => true,
    // Closes the curl options array.
    ]);
    // Executes the download and stores the body.
    $body = curl_exec($ch);
    // Reads the HTTP status code of the response.
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // Closes the curl handle.
    curl_close($ch);

    // Rejects empty bodies and non-2xx responses. checks if the download succeded
    if (!is_string($body) || $body === '' || $code < 200 || $code >= 300) {
        // Returns false when the download failed.
        return false;
    // Closes the download failure check.
    }

    // Writes the body to the temporary file.
    if (@file_put_contents($temp, $body) === false) {
        // Returns false when the temporary file cannot be written.
        return false;
    // Closes the temp write check.
    }
    // Moves the temporary file into its final place.
    if (!@rename($temp, $destination)) {
        // Deletes the temporary file when the move failed.
        @unlink($temp);
        // Returns false because the download did not finish.
        return false;
    // Closes the rename failure check.
    }

    // Returns true when the file was downloaded and moved.
    return true;
// Closes the gdriveDownloadFile() function.
}
