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

if (!defined('GDRIVE_TEMPLATE_FOLDER_ID')) {
    define('GDRIVE_TEMPLATE_FOLDER_ID', '1o4Mk29HL-PQwJFgByRgGtkmCdK9ZBJqg');
}

/** Browser user agent: Drive answers a plain script with an error page. */
function gdriveUserAgent(): string {
    return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';
}

/** Direct download endpoint for a Drive file. */
function gdriveDownloadUrl(string $fileId): string {
    return 'https://drive.google.com/uc?export=download&id=' . rawurlencode($fileId);
}

/**
 * The download host answers with the file headers straight away, while the
 * drive.google.com address first answers with a redirect. The header check
 * uses this one because it saves a round trip per file.
 */
function gdriveHeaderUrl(string $fileId): string {
    return 'https://drive.usercontent.google.com/download?id=' . rawurlencode($fileId) . '&export=download';
}

/** Public folder page for a Drive folder. */
function gdriveFolderUrl(string $folderId): string {
    return 'https://drive.google.com/drive/folders/' . rawurlencode($folderId);
}

/**
 * GET a URL and return the body (empty string on failure).
 */
function gdriveHttpGet(string $url, int $timeout = 25): string {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT      => gdriveUserAgent(),
            CURLOPT_ENCODING       => '',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $err  = curl_errno($ch);
        curl_close($ch);
        return ($err === 0 && is_string($body)) ? $body : '';
    }

    // Fallback when the curl extension is not available.
    $ctx = stream_context_create([
        'http' => [
            'timeout'         => $timeout,
            'user_agent'      => gdriveUserAgent(),
            'follow_location' => 1,
            'max_redirects'   => 5,
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    return is_string($body) ? $body : '';
}

/**
 * Pull the name of one entry out of a Drive folder row.
 *
 * A file row shows the name as a plain text node ending in the document
 * extension; a folder row shows it in a <strong> element.
 */
function gdriveRowName(string $body): string {
    $clean = preg_replace('/<svg\b.*?<\/svg>/s', ' ', $body);
    $clean = preg_replace('/<[^>]+>/', "\x01", (string) $clean);
    $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    foreach (explode("\x01", $clean) as $part) {
        $part = trim(preg_replace('/\s+/', ' ', (string) $part));
        if ($part !== '' && preg_match('/\.(docx?|pdf|xlsx?|pptx?|odt|ods)$/i', $part)) {
            return $part;
        }
    }

    if (preg_match('/<strong[^>]*>(.*?)<\/strong>/s', $body, $m)) {
        $name = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($name !== '') {
            return $name;
        }
    }

    if (preg_match('/data-tooltip="([^"]+)"/', $body, $m)) {
        $name = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $name = preg_replace('/\s+Shared folder$/i', '', $name);
        if ($name !== '') {
            return $name;
        }
    }

    return '';
}

/**
 * Turn a size written the way Drive writes it ("3.8 MB") into bytes.
 */
function gdriveParseSize(string $text): int {
    $text = trim($text);
    if ($text === '' || !preg_match('/^([0-9]+(?:\.[0-9]+)?)\s*(B|KB|MB|GB|TB|Bytes)?$/i', $text, $m)) {
        return 0;
    }
    $value = (float) $m[1];
    $unit  = strtolower((string) ($m[2] ?? 'b'));
    $factors = [
        'b' => 1, 'byte' => 1, 'bytes' => 1,
        'kb' => 1024, 'mb' => 1048576, 'gb' => 1073741824, 'tb' => 1099511627776,
    ];
    return (int) round($value * ($factors[$unit] ?? 1));
}

/**
 * The size of one entry of a Drive folder, in bytes.
 *
 * The size column of the folder page carries it, which is a cheap second
 * signal for the files whose header request did not come back.
 */
function gdriveRowSize(string $body): int {
    if (preg_match('/aria-label="Size:\s*([^"]*)"/i', $body, $m)) {
        $size = gdriveParseSize(trim(explode("\n", $m[1])[0]));
        if ($size > 0) {
            return $size;
        }
    }
    if (preg_match('/data-column-field="3"[^>]*>(.*?)<\/td>/s', $body, $m)) {
        $text = preg_replace('/<svg\b.*?<\/svg>/s', ' ', $m[1]);
        $text = preg_replace('/<[^>]+>/', ' ', (string) $text);
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        return gdriveParseSize($text);
    }
    return 0;
}

/**
 * Parse a Drive folder page into its entries.
 *
 * @return array<int, array{id:string,name:string,isFolder:bool,size:int}>
 */
function gdriveParseFolderHtml(string $html): array {
    $entries = [];
    if ($html === '') {
        return $entries;
    }

    if (preg_match_all('/<tr\b[^>]*\bdata-id="([A-Za-z0-9_-]{25,50})"[^>]*>(.*?)<\/tr>/s', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $row) {
            $id   = $row[1];
            $body = $row[2];
            $name = gdriveRowName($body);
            if ($name === '') {
                continue;
            }
            $isFolder = (stripos($body, 'Shared folder') !== false);
            if (!$isFolder && preg_match('/data-tooltip="([^"]*)"/', $body, $t) && stripos($t[1], 'folder') !== false) {
                $isFolder = true;
            }
            $entries[] = [
                'id'       => $id,
                'name'     => $name,
                'isFolder' => $isFolder,
                'size'     => $isFolder ? 0 : gdriveRowSize($body),
            ];
        }
    }

    return $entries;
}

/**
 * List the entries of a public Drive folder.
 *
 * @return array<int, array{id:string,name:string,isFolder:bool,size:int}>
 */
function gdriveListFolder(string $folderId): array {
    $html = gdriveHttpGet(gdriveFolderUrl($folderId));
    return gdriveParseFolderHtml($html);
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
function gdriveListFolders(array $folderIds): array {
    $folderIds = array_values(array_unique(array_filter($folderIds)));
    $result    = [];

    if ($folderIds === []) {
        return $result;
    }

    if (!function_exists('curl_multi_init')) {
        foreach ($folderIds as $id) {
            $result[$id] = gdriveListFolder($id);
        }
        return $result;
    }

    $multi   = curl_multi_init();
    $handles = [];

    foreach ($folderIds as $id) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => gdriveFolderUrl($id),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT      => gdriveUserAgent(),
            CURLOPT_ENCODING       => '',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        curl_multi_add_handle($multi, $ch);
        $handles[spl_object_id($ch)] = ['id' => $id, 'ch' => $ch];
    }

    $running = null;
    do {
        curl_multi_exec($multi, $running);
        if ($running > 0) {
            curl_multi_select($multi, 1.0);
        }
    } while ($running > 0);

    foreach ($handles as $handle) {
        $html = curl_multi_getcontent($handle['ch']);
        $result[$handle['id']] = is_string($html) ? gdriveParseFolderHtml($html) : [];
        curl_multi_remove_handle($multi, $handle['ch']);
        curl_close($handle['ch']);
    }
    curl_multi_close($multi);

    return $result;
}

/**
 * Read the response headers of the download endpoint.
 *
 * @return array{modified:int,size:int,name:string}
 */
function gdriveParseHeadHeaders(string $raw): array {
    $meta = ['modified' => 0, 'size' => 0, 'name' => ''];

    if (preg_match_all('/^Last-Modified:\s*(.+)$/mi', $raw, $m)) {
        $ts = strtotime(trim(end($m[1])));
        if ($ts !== false) {
            $meta['modified'] = (int) $ts;
        }
    }
    if (preg_match_all('/^Content-Length:\s*(\d+)/mi', $raw, $m)) {
        $meta['size'] = (int) end($m[1]);
    }
    if (preg_match_all('/filename="([^"]*)"/i', $raw, $m)) {
        $meta['name'] = trim(html_entity_decode((string) end($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    return $meta;
}

/**
 * Read the modification time, the size and the file name of one Drive file.
 *
 * @return array{modified:int,size:int,name:string}
 */
function gdriveFileMeta(string $fileId): array {
    if (!function_exists('curl_init')) {
        return ['modified' => 0, 'size' => 0, 'name' => ''];
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => gdriveHeaderUrl($fileId),
        CURLOPT_NOBODY         => true,
        CURLOPT_HEADER         => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT      => gdriveUserAgent(),
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);

    if (!is_string($raw) || $raw === '') {
        return ['modified' => 0, 'size' => 0, 'name' => ''];
    }

    return gdriveParseHeadHeaders($raw);
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
function gdriveFilesMeta(array $fileIds): array {
    $fileIds = array_values(array_unique(array_filter($fileIds)));
    $result  = [];

    if ($fileIds === []) {
        return $result;
    }

    if (!function_exists('curl_multi_init')) {
        foreach ($fileIds as $id) {
            $result[$id] = gdriveFileMeta($id);
        }
        return $result;
    }

    $multi   = curl_multi_init();
    $handles = [];

    foreach ($fileIds as $id) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => gdriveHeaderUrl($id),
            CURLOPT_NOBODY         => true,
            CURLOPT_HEADER         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT      => gdriveUserAgent(),
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        curl_multi_add_handle($multi, $ch);
        $handles[spl_object_id($ch)] = ['id' => $id, 'ch' => $ch];
    }

    $running = null;
    do {
        curl_multi_exec($multi, $running);
        if ($running > 0) {
            curl_multi_select($multi, 1.0);
        }
    } while ($running > 0);

    foreach ($handles as $handle) {
        $raw = curl_multi_getcontent($handle['ch']);
        $result[$handle['id']] = is_string($raw) && $raw !== ''
            ? gdriveParseHeadHeaders($raw)
            : ['modified' => 0, 'size' => 0, 'name' => ''];
        curl_multi_remove_handle($multi, $handle['ch']);
        curl_close($handle['ch']);
    }
    curl_multi_close($multi);

    return $result;
}

/**
 * Download a Drive file to a local path.
 *
 * The file is written to a temporary name first and moved into place, so a
 * failed or interrupted download never replaces a good copy.
 */
function gdriveDownloadFile(string $fileId, string $destination): bool {
    if (!function_exists('curl_init')) {
        return false;
    }

    $folder = dirname($destination);
    if (!is_dir($folder) && !@mkdir($folder, 0755, true) && !is_dir($folder)) {
        return false;
    }

    $temp = $destination . '.part';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => gdriveDownloadUrl($fileId),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_USERAGENT      => gdriveUserAgent(),
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!is_string($body) || $body === '' || $code < 200 || $code >= 300) {
        return false;
    }

    if (@file_put_contents($temp, $body) === false) {
        return false;
    }
    if (!@rename($temp, $destination)) {
        @unlink($temp);
        return false;
    }

    return true;
}
