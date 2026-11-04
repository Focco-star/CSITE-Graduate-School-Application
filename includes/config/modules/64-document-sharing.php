<?php
/* CSITE configuration module: Shareable links for uploaded student documents. */

/**
 * Make sure the document table has a share-token column.
 *
 * The token makes a document link unguessable, so a coordinator can send the
 * link to another person without exposing every other document. The check runs
 * once per request and is skipped when the database class is not available.
 */
// Creates the share-token column and index when they are missing.
function ensureDocumentShareSchema(): void {
    // Remembers whether the schema was already checked in this request.
    static $checked = false;
    // Stops when the check already ran or the DB class is missing.
    if ($checked || !class_exists('DB')) {
        // Returns early because there is nothing else to do.
        return;
    // Closes the early-return check.
    }
    // Marks the check as done so it does not run again in this request.
    $checked = true;
    // Starts a guarded block because the table may not exist yet.
    try {
        // Reads every column of the application_documents table.
        $columns = DB::query('SHOW COLUMNS FROM `application_documents`')->fetchAll(PDO::FETCH_COLUMN);
        // Adds the share-token column when it is missing.
        if (!in_array('share_token', $columns, true)) {
            // Stores one random token per shared document.
            DB::getConnection()->exec("ALTER TABLE `application_documents` ADD COLUMN `share_token` VARCHAR(64) NULL DEFAULT NULL AFTER `stored_name`");
        // Closes the share-token column check.
        }
        // Reads the table indexes so the unique key can be checked.
        $indexes = DB::query('SHOW INDEX FROM `application_documents`')->fetchAll();
        // Starts assuming the unique index is missing.
        $hasIndex = false;
        // Loops over every index row.
        foreach ($indexes as $index) {
            // Checks whether the share-token index already exists.
            if (($index['Key_name'] ?? '') === 'uniq_application_documents_share_token') {
                // Marks the index as present.
                $hasIndex = true;
                // Stops looking once the index is found.
                break;
            // Closes the index-name check.
            }
        // Closes the loop over indexes.
        }
        // Adds the unique index when it is missing.
        if (!$hasIndex) {
            // Keeps each share token unique while allowing many empty tokens.
            DB::getConnection()->exec('ALTER TABLE `application_documents` ADD UNIQUE KEY `uniq_application_documents_share_token` (`share_token`)');
        // Closes the unique index check.
        }
    // Ignores schema errors so the page can explain the problem instead of crashing.
    } catch (Throwable $e) {
        // Leaves the schema untouched when the check could not run.
    // Closes the catch block.
    }
// Closes the ensureDocumentShareSchema function.
}

/**
 * The share token of one uploaded document.
 *
 * A token is created the first time the document is shared and then reused, so
 * every copy of the link points to the same document. Empty text is returned
 * when the document is not stored in MySQL.
 */
// Returns the share token for a document, creating it when needed.
function documentShareToken(array $upload): string {
    // Reads the numeric document id, which only database rows have.
    $documentId = (int) ($upload['id'] ?? 0);
    // Stops when the document has no database id or the DB class is missing.
    if ($documentId <= 0 || !class_exists('DB')) {
        // Returns empty text because the document cannot be shared this way.
        return '';
    // Closes the document-id check.
    }
    // Makes sure the token column exists before it is read.
    ensureDocumentShareSchema();
    // Starts a guarded block so a database error cannot break the page.
    try {
        // Reads the token already saved for this document.
        $token = (string) DB::query(
            // Selects the share token of the requested document.
            'SELECT `share_token` FROM `application_documents` WHERE `document_id` = :id LIMIT 1',
            // Binds the document id.
            ['id' => $documentId]
        // Closes the query call.
        )->fetchColumn();
        // Reuses the saved token when it has the expected shape.
        if (preg_match('/^[a-f0-9]{64}$/', $token)) {
            // Returns the existing share token.
            return $token;
        // Closes the existing-token check.
        }
        // Tries a few times in case two requests create a token together.
        for ($attempt = 0; $attempt < 3; $attempt++) {
            // Creates a new random token with 256 bits of entropy.
            $newToken = bin2hex(random_bytes(32));
            // Saves the token without replacing one made by another request.
            $stmt = DB::getConnection()->prepare(
                // Updates only a document that has no usable token yet.
                'UPDATE `application_documents`
                    SET `share_token` = :token
                  WHERE `document_id` = :id
                    AND (`share_token` IS NULL OR `share_token` = \'\')'
            );
            // Runs the update with the new token.
            $stmt->execute(['token' => $newToken, 'id' => $documentId]);
            // Reads back the token, which may come from another request.
            $token = (string) DB::query(
                // Selects the saved share token again.
                'SELECT `share_token` FROM `application_documents` WHERE `document_id` = :id LIMIT 1',
                // Binds the document id.
                ['id' => $documentId]
            // Closes the query call.
            )->fetchColumn();
            // Returns the token once it has the expected shape.
            if (preg_match('/^[a-f0-9]{64}$/', $token)) {
                // Returns the saved share token.
                return $token;
            // Closes the saved-token check.
            }
        // Closes the retry loop.
        }
    // Catches database errors so the page can still render.
    } catch (Throwable $e) {
        // Returns empty text when no token could be created.
        return '';
    // Closes the catch block.
    }
    // Returns empty text when every attempt failed.
    return '';
// Closes the documentShareToken function.
}

/**
 * The full, shareable URL of one uploaded document.
 *
 * The URL is absolute so it can be pasted into an email or chat. It works
 * without a coordinator login because the random token identifies the file.
 */
// Builds the shareable URL for a document.
function documentShareUrl(array $upload): string {
    // Reads or creates the share token for this document.
    $token = documentShareToken($upload);
    // Stops when the document cannot be shared.
    if ($token === '') {
        // Returns empty text so the caller can hide the copy button.
        return '';
    // Closes the token check.
    }
    // Builds the site-relative address of the public document viewer.
    $path = url('share_document.php?token=' . rawurlencode($token));
    // Detects HTTPS, including when a proxy reports it in the forwarded header.
    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        // Also trusts the proxy header when the web server reports HTTPS.
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    // Chooses the URL scheme for the link.
    $scheme = $isHttps ? 'https' : 'http';
    // Reads the host name used by the current request.
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    // Returns the relative path when there is no host, such as during a CLI run.
    if ($host === '') {
        // Returns the site path without a domain.
        return $path;
    // Closes the missing-host check.
    }
    // Returns the complete shareable address.
    return $scheme . '://' . $host . $path;
// Closes the documentShareUrl function.
}

/**
 * Load one uploaded document by its share token.
 *
 * Returns the normalised document array used by the viewer, or null when the
 * token is invalid or the document no longer exists.
 */
// Finds a shared document by token.
function documentShareFind(string $token): ?array {
    // Rejects anything that is not a 64-character lowercase hex token.
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        // Returns null for a malformed token.
        return null;
    // Closes the token-shape check.
    }
    // Stops when the database class is missing.
    if (!class_exists('DB')) {
        // Returns null because the document cannot be found.
        return null;
    // Closes the DB check.
    }
    // Makes sure the token column exists before it is used.
    ensureDocumentShareSchema();
    // Starts a guarded block so a database error becomes a friendly 404.
    try {
        // Loads the document row and the student email that owns it.
        $row = DB::query(
            // Selects every document column plus the owner email.
            'SELECT d.*, u.`email` AS email
               FROM `application_documents` d
               LEFT JOIN `users` u ON u.`user_id` = d.`user_id`
              WHERE d.`share_token` = :token
              LIMIT 1',
            // Binds the validated share token.
            ['token' => $token]
        // Closes the query call.
        )->fetch();
        // Returns null when no document matches the token.
        if (!$row) {
            // Returns null for an unknown or revoked token.
            return null;
        // Closes the missing-row check.
        }
        // Converts the database row into the shape the viewer expects.
        return normalizeDatabaseDocument($row, (string) ($row['email'] ?? ''));
    // Catches database errors and reports a missing document.
    } catch (Throwable $e) {
        // Returns null when the document could not be read.
        return null;
    // Closes the catch block.
    }
// Closes the documentShareFind function.
}
