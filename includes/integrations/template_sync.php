<?php /* Opens PHP mode so the template sync helper functions can be defined. */ ?>
<?php
/**
 * Google Drive template sync and the update notifications it feeds.
 * ------------------------------------------------------------------
 * The teacher keeps the official forms and templates in a Google Drive
 * folder. This file mirrors that folder into the portal:
 *
 *   1. read the folder (one request per subfolder),
 *   2. ask every file for its "last modified" time (cheap HEAD requests),
 *   3. download only the files that actually changed,
 *   4. write a notification so the Templates menu can show a badge.
 *
 * Duplicate notifications are avoided three ways:
 *   - an exclusive lock stops two syncs from running at the same time,
 *   - a sync only looks at Drive again after the refresh interval,
 *   - the notification table has a unique key on (file id + changed time),
 *     so the same version of a file can never be announced twice.
 */

// Loads the Google Drive helper functions that this sync file relies on.
require_once __DIR__ . '/gdrive.php';

// Only defines the Drive sync interval constant when it is not set yet.
if (!defined('GDRIVE_TEMPLATE_SYNC_INTERVAL')) {
    // Docblock: how many seconds to wait before checking Google Drive again.
    /** Seconds before the portal checks Google Drive again. */
    // Sets the Drive sync interval to 900 seconds, which is fifteen minutes.
    define('GDRIVE_TEMPLATE_SYNC_INTERVAL', 900);
// Closes the if block that guards the sync interval constant.
}

// Only defines the sync state key constant when it is not set yet.
if (!defined('GDRIVE_TEMPLATE_SYNC_KEY')) {
    // Docblock: the row key used in the template_sync_state table.
    /** Row key of the sync state in template_sync_state. */
    // Stores the template sync state under the key google_drive_templates.
    define('GDRIVE_TEMPLATE_SYNC_KEY', 'google_drive_templates');
// Closes the if block that guards the sync key constant.
}

// Only defines the change detection interval constant when it is not set yet.
if (!defined('GDRIVE_TEMPLATE_DETECT_INTERVAL')) {
    // Docblock: seconds before Google Drive is checked for changes again.
    /** Seconds before Google Drive is looked at for changes again. */
    // Sets the detection interval to 60 seconds, which is one minute.
    define('GDRIVE_TEMPLATE_DETECT_INTERVAL', 60);
// Closes the if block that guards the detection interval constant.
}

// Only defines the detection state key constant when it is not set yet.
if (!defined('GDRIVE_TEMPLATE_DETECT_KEY')) {
    // Docblock: row key of the last Drive check in template_sync_state.
    /** Row key of the last look in template_sync_state. */
    // Stores the detection state under the key google_drive_templates_check.
    define('GDRIVE_TEMPLATE_DETECT_KEY', 'google_drive_templates_check');
// Closes the if block that guards the detection key constant.
}

/* ------------------------------------------------------------------
 * Schema
 * ------------------------------------------------------------------ */

/**
 * Create the tables and columns the sync needs, once.
 *
 * The same statements live in
 * database_migrations/20261022_add_drive_template_sync.sql - this keeps
 * the feature working on a database where the migration was not applied.
 * A marker file remembers that the database is already up to date, so the
 * check costs nothing on later requests.
 */
// Creates the tables and columns the Drive sync needs, once per request.
function ensureTemplateSyncSchema(): void {
    // Remembers in a static variable whether the schema was already ensured.
    static $done = false;
    // Stops early if the schema was checked or the DB class is missing.
    if ($done || !class_exists('DB')) { 
        // Returns from the function because no schema work is needed.
        return;
    // Closes the if block that guards the early return.
    }
    // Marks the schema as ensured so later calls return immediately.
    $done = true;

    // Builds the path of the marker file that records the schema version.
    $marker = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . '.template-schema.v1';
    // Checks whether the schema marker file already exists.
    if (is_file($marker)) {
        // Returns early because the database was already upgraded before.
        return;
    // Closes the if block for an existing marker file.
    }

    // Starts a try block so database errors do not break the page.
    try {
        // Gets the shared PDO database connection from the DB class.
        $pdo = DB::getConnection();

        // Starts an empty list of the existing columns of the templates table.
        $columns = [];
        // Asks MySQL for the current columns of the templates table.
        foreach ($pdo->query('SHOW COLUMNS FROM `templates`') as $column) {
            // Adds each column name to the list so it can be looked up quickly.
            $columns[$column['Field']] = true;
        // Closes the foreach loop over the templates table columns.
        }
        // Checks whether the drive_file_id column is still missing.
        if (!isset($columns['drive_file_id'])) {
            // Adds drive_file_id to store the linked Google Drive file id.
            $pdo->exec('ALTER TABLE `templates` ADD COLUMN `drive_file_id` VARCHAR(120) DEFAULT NULL AFTER `file_path`');
        // Closes the if block for the drive_file_id column.
        }
        // Checks whether the drive_modified_at column is still missing.
        if (!isset($columns['drive_modified_at'])) {
            // Adds drive_modified_at to store the file's last modified time from Drive.
            $pdo->exec('ALTER TABLE `templates` ADD COLUMN `drive_modified_at` DATETIME DEFAULT NULL AFTER `drive_file_id`');
        // Closes the if block for the drive_modified_at column.
        }
        // Checks whether the drive_size column is still missing.
        if (!isset($columns['drive_size'])) {
            // Adds drive_size to store the file size seen in Google Drive.
            $pdo->exec('ALTER TABLE `templates` ADD COLUMN `drive_size` BIGINT DEFAULT NULL AFTER `drive_modified_at`');
        // Closes the if block for the drive_size column.
        }
        // Checks whether the needs_review column is still missing.
        if (!isset($columns['needs_review'])) {
            // Adds needs_review so a coordinator can flag a template for checking.
            $pdo->exec('ALTER TABLE `templates` ADD COLUMN `needs_review` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`');
        // Closes the if block for the needs_review column.
        }

        // Runs the SQL that creates the template_notifications table.
        $pdo->exec(
            // Starts the CREATE TABLE for template_notifications, one row per Drive change notice.
            'CREATE TABLE IF NOT EXISTS `template_notifications` (
                `notification_id`     INT(11)      NOT NULL AUTO_INCREMENT,
                `template_id`         INT(11)      DEFAULT NULL,
                `drive_file_id`       VARCHAR(120) NOT NULL,
                `file_name`           VARCHAR(255) NOT NULL,
                `change_type`         ENUM(\'added\',\'updated\') NOT NULL DEFAULT \'updated\',
                `changed_at`          DATETIME     NOT NULL,
                `track_code`          VARCHAR(40)  DEFAULT NULL,
                `seen_student_at`     DATETIME     DEFAULT NULL,
                `seen_coordinator_at` DATETIME     DEFAULT NULL,
                PRIMARY KEY (`notification_id`),
                UNIQUE KEY `uq_template_notification` (`drive_file_id`, `changed_at`),
                KEY `idx_template_notifications_track` (`track_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        // Closes the exec call that created the template_notifications table.
        );

        // Runs the SQL that creates the template_sync_state table.
        $pdo->exec(
            // Starts the CREATE TABLE for template_sync_state, which stores sync time and status.
            'CREATE TABLE IF NOT EXISTS `template_sync_state` (
                `sync_key`     VARCHAR(60) NOT NULL,
                `last_sync_at` DATETIME    DEFAULT NULL,
                `last_status`  VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (`sync_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        // Closes the exec call that created the template_sync_state table.
        );

        // Comment: the pending list holds Drive files that are not applied yet.
        // The files that are in Google Drive but not in the system yet. The
        // Comment: the list is filled by the check and emptied by the sync.
        // list is written when Drive is looked at and emptied by the
        // Comment: it lets the coordinator be warned before a change is applied.
        // synchronisation, so the coordinator can be told that a change is
        // Comment: this ends the explanation of the pending table.
        // waiting before it is applied.
        // Runs the SQL that creates the template_drive_pending table.
        $pdo->exec(
            // Starts the CREATE TABLE for template_drive_pending, the files waiting to be synced.
            'CREATE TABLE IF NOT EXISTS `template_drive_pending` (
                `drive_file_id`     VARCHAR(120) NOT NULL,
                `file_name`         VARCHAR(255) NOT NULL,
                `change_type`       ENUM(\'added\',\'updated\') NOT NULL DEFAULT \'updated\',
                `track_code`        VARCHAR(40)  DEFAULT NULL,
                `drive_modified_at` DATETIME     DEFAULT NULL,
                `drive_size`        BIGINT       DEFAULT NULL,
                `detected_at`       DATETIME     NOT NULL,
                PRIMARY KEY (`drive_file_id`),
                KEY `idx_template_drive_pending_track` (`track_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        // Closes the exec call that created the template_drive_pending table.
        );

        // Comment: seminar rows used the wrong SEMINAR folder name in file_path.
        // The seminar rows were saved with the folder name "SEMINAR" while the
        // Comment: the real files live in SEMINAR PAPER, so those links were dead.
        // files live in "SEMINAR PAPER", so those downloads were dead links.
        // Runs the SQL that repairs the old seminar file paths.
        $pdo->exec(
            // Starts the UPDATE that rewrites the wrong seminar folder in file_path.
            "UPDATE `templates`
                SET `file_path` = REPLACE(`file_path`, 'assets/papers/SEMINAR/', 'assets/papers/SEMINAR PAPER/')
              WHERE `file_path` LIKE 'assets/papers/SEMINAR/%'"
        // Closes the exec call that repaired the seminar file paths.
        );

        // Gets the folder that will hold the schema marker file.
        $folder = dirname($marker);
        // Checks whether the storage folder exists on disk.
        if (!is_dir($folder)) {
            // Creates the storage folder quietly when it is missing.
            @mkdir($folder, 0755, true);
        // Closes the if block that creates the storage folder.
        }
        // Writes the marker file so the schema check is skipped next time.
        @file_put_contents($marker, (string) time());
    // Catches any database error so the rest of the sync keeps working.
    } catch (Throwable $e) {
        // Comment: the migration file repeats these statements, so errors are ignored.
        // The migration file has the same statements; nothing else to do.
    // Closes the catch block of the schema try statement.
    }
// Closes the ensureTemplateSyncSchema function.
}

/* ------------------------------------------------------------------
 * Small helpers
 * ------------------------------------------------------------------ */

/**
 * Public URL of a stored template file.
 *
 * templates.file_path is relative to the application root, so it must not
 * go through asset() - that would add a second "assets/" and break the link.
 */
// Builds the public URL of a template file from its stored path.
function templateFileUrl(?string $filePath): string {
    // Trims the stored file path and treats null as empty text.
    $path = trim((string) $filePath);
    // Checks whether the file path is empty.
    if ($path === '') {
        // Returns an empty link when there is no file path.
        return '';
    // Closes the if block for the empty path.
    }
    // Checks whether the path is already a full http or https link.
    if (preg_match('#^https?://#i', $path)) {
        // Returns the external link unchanged.
        return $path;
    // Closes the if block for absolute links.
    }
    // Comment: each path part is URL encoded so spaces and symbols work.
    // Encode every part of the path but keep the folder separators.
    // Encodes every folder and file name separately while keeping the slashes.
    $encoded = implode('/', array_map('rawurlencode', explode('/', ltrim($path, '/'))));
    // Turns the encoded path into a full site URL with the url() helper.
    return url($encoded);
// Closes the templateFileUrl function.
}

/** Absolute path of a stored template file. */
// Builds the absolute disk path of a stored template file.
function templateAbsolutePath(string $relativePath): string {
    // Joins the project root with the relative path and fixes separators.
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($relativePath, '/'));
// Closes the templateAbsolutePath function.
}

/** The track a Drive subfolder belongs to. */
// Works out which track a Drive folder belongs to from its name.
function templateTrackForFolderName(string $folderName): string {
    // Upper-cases and trims the folder name for easy matching.
    $name = strtoupper(trim($folderName));
    // Checks whether the folder name contains CAPSTONE.
    if (str_contains($name, 'CAPSTONE')) {
        // Returns the capstone track code.
        return 'capstone';
    // Closes the CAPSTONE if block.
    }
    // Checks whether the folder name contains SEMINAR.
    if (str_contains($name, 'SEMINAR')) {
        // Returns the seminar track code.
        return 'seminar';
    // Closes the SEMINAR if block.
    }
    // Checks whether the folder name contains THESIS.
    if (str_contains($name, 'THESIS')) {
        // Returns the thesis track code.
        return 'thesis';
    // Closes the THESIS if block.
    }
    // Returns an empty track when the folder name is not recognised.
    return '';
// Closes the templateTrackForFolderName function.
}

/** The local papers folder of a track. */
// Returns the local papers folder name for a track code.
function templateFolderForTrack(string $track): string {
    // Maps track codes to their folder names inside assets/papers.
    $folders = ['thesis' => 'THESIS', 'capstone' => 'CAPSTONE', 'seminar' => 'SEMINAR PAPER'];
    // Returns the folder name, or empty text for an unknown track.
    return $folders[strtolower(trim($track))] ?? '';
// Closes the templateFolderForTrack function.
}

/**
 * Guess the stage of a Drive file from its name.
 *
 * The endorsement forms of a track name the stage they belong to, so the
 * wording of the file is what is read. An empty answer means the stage
 * could not be worked out.
 */
// Guesses the workflow stage of a Drive file from words in its name.
function templateStageKeyForFile(string $fileName, string $track): string {
    // Upper-cases and trims the file name for matching.
    $name = strtoupper(trim($fileName));

    // Starts with an empty stage.
    $stage = '';
    // Checks whether the file name mentions FINAL.
    if (str_contains($name, 'FINAL')) {
        // Uses the final stage.
        $stage = 'final';
    // Otherwise checks whether the name mentions CONCEPT.
    } elseif (str_contains($name, 'CONCEPT')) {
        // Uses the concept stage.
        $stage = 'concept';
    // Otherwise checks whether the name mentions PROPOSAL.
    } elseif (str_contains($name, 'PROPOSAL')) {
        // Uses the proposal stage.
        $stage = 'proposal';
    // Closes the stage if and elseif chain.
    }
    // Checks whether no stage word was found.
    if ($stage === '') {
        // Returns empty text because the stage is unknown.
        return '';
    // Closes the if block for an unknown stage.
    }

    // Comment: the stage is only used if this track really has it.
    // Only a stage that exists in this track is used.
    // Loops over the workflow stages defined for the track.
    foreach (templateWorkflowStages()[$track] ?? [] as $workflowStage) {
        // Checks whether this stage key matches the guessed stage.
        if ((string) ($workflowStage['key'] ?? '') === $stage) {
            // Returns the stage key because the track supports it.
            return $stage;
        // Closes the if block that matched the stage.
        }
    // Closes the foreach loop over the stages of the track.
    }

    // Returns empty text when the track does not use this stage.
    return '';
// Closes the templateStageKeyForFile function.
}

/**
 * Guess the document type of a Drive file from its name.
 *
 * An endorsement form belongs to a stage, so it is filed under the stage
 * its name mentions. A paper format is used at every stage of the track and
 * a reference document belongs to the track as a whole.
 */
// Guesses the document type of a Drive file from its name.
function templateDocTypeForFile(string $fileName): string {
    // Upper-cases the file name for pattern matching.
    $name = strtoupper($fileName);
    // Comment: FORM only counts as a form when it is a whole word.
    // "FORM" is only a form when it stands on its own; a file called
    // Comment: a file named FORMAT.docx is the paper format, not a form.
    // "... FORMAT.docx" is the paper format of the track.
    // Checks for ENDORSEMENT or FORM as whole words in the name.
    if (preg_match('/\bENDORSEMENT\b/', $name) || preg_match('/\bFORM\b/', $name)) {
        // Returns form for endorsement forms.
        return 'form';
    // Closes the if block for forms.
    }
    // Checks for GUIDELINE or GUIDELINES or COVER PAGE as whole words.
    if (preg_match('/\bGUIDELINES?\b/', $name) || preg_match('/\bCOVER PAGE\b/', $name)) {
        // Returns reference for guideline and cover page documents.
        return 'reference';
    // Closes the if block for reference documents.
    }
    // Returns template for every other file.
    return 'template';
// Closes the templateDocTypeForFile function.
}

/** A readable name for a file that has no template row yet. */
// Turns a Drive file name into a readable title.
function templateDisplayName(string $fileName): string {
    // Removes the file extension from the name.
    $name = preg_replace('/\.[A-Za-z0-9]{1,6}$/', '', trim($fileName));
    // Removes a leading number such as 1. or 2 - from the name.
    $name = preg_replace('/^\d+\s*[.)-]\s*/', '', (string) $name);
    // Removes trailing dashes or underscores.
    $name = preg_replace('/\s*[-_]\s*$/', '', (string) $name);
    // Collapses repeated spaces into one and trims the result.
    $name = trim(preg_replace('/\s+/', ' ', (string) $name));
    // Returns the clean name, or the raw file name if it became empty.
    return $name !== '' ? $name : trim($fileName);
// Closes the templateDisplayName function.
}

/** Normalised file name, used to match a Drive file with a template row. */
// Normalises a file name so two names can be compared.
function templateNormaliseName(string $name): string {
    // Lower-cases the name and keeps only letters and digits.
    return (string) preg_replace('/[^a-z0-9]+/', '', strtolower(trim($name)));
// Closes the templateNormaliseName function.
}

/** Where a Drive file is stored inside the portal. */
// Builds the portal storage path for a Drive file of a track.
function templateDriveLocalPath(string $track, string $fileName): string {
    // Gets the local folder name of the track.
    $folder = templateFolderForTrack($track);
    // Replaces characters that are not allowed in file names with dashes.
    $safe = preg_replace('/[\\\\\/:\*\?"<>\|\x00-\x1F]+/', '-', $fileName);
    // Trims spaces from the cleaned file name.
    $safe = trim((string) $safe);
    // Trims spaces, dots and dashes from both ends of the name.
    $safe = trim($safe, " \t.-");
    // Checks whether the name became empty after cleaning.
    if ($safe === '') {
        // Falls back to a generic name when nothing is left.
        $safe = 'template';
    // Closes the if block for the empty name.
    }
    // Returns the path under assets/papers for the track folder.
    return 'assets/papers/' . $folder . '/' . $safe;
// Closes the templateDriveLocalPath function.
}

/**
 * The current time of the database.
 *
 * The times of the template records are written by the database, so they are
 * compared with the clock of the database and not with the clock of the web
 * server, which can sit in another time zone.
 */
// Returns the current database time as Y-m-d H:i:s.
function templateDatabaseNow(): string {
    // Caches the time in a static variable for the rest of the request.
    static $now = null;
    // Checks whether the time was already loaded.
    if ($now !== null) {
        // Returns the cached database time.
        return $now;
    // Closes the if block for the cached time.
    }
    // Starts with empty text in case the database cannot answer.
    $now = '';
    // Only asks the database when the DB class is available.
    if (class_exists('DB')) {
        // Starts a try block around the database time query.
        try {
            // Reads NOW() from MySQL so times match the database clock.
            $now = (string) (DB::query('SELECT NOW() AS n')->fetch()['n'] ?? '');
        // Catches a query error and keeps the empty time.
        } catch (Throwable $e) {
            // Resets the time to empty text when the query failed.
            $now = '';
        // Closes the catch block for the time query.
        }
    // Closes the if block for the DB class check.
    }
    // Checks whether the database time was not obtained.
    if ($now === '') {
        // Falls back to the web server clock in Y-m-d H:i:s format.
        $now = date('Y-m-d H:i:s');
    // Closes the if block for the fallback time.
    }
    // Returns the final time text.
    return $now;
// Closes the templateDatabaseNow function.
}

/** "3 minutes ago" style text for a timestamp. */
// Turns a timestamp into friendly text like 3 minutes ago.
function templateTimeAgo(?int $timestamp): string {
    // Checks whether the timestamp is missing or zero.
    if (!$timestamp) {
        // Returns never when there is no timestamp.
        return 'never';
    // Closes the if block for a missing timestamp.
    }
    // Gets the current database time as a Unix timestamp.
    $now = strtotime(templateDatabaseNow());
    // Checks whether the database time could not be parsed.
    if ($now === false) {
        // Uses the web server time as a fallback.
        $now = time();
    // Closes the if block for the fallback time.
    }
    // Computes the seconds since the timestamp, never negative.
    $diff = max(0, $now - $timestamp);
    // Checks whether the difference is under one minute.
    if ($diff < 60) {
        // Returns just now for very recent times.
        return 'just now';
    // Closes the if block for under a minute.
    }
    // Checks whether the difference is under one hour.
    if ($diff < 3600) {
        // Converts the difference to whole minutes.
        $m = (int) floor($diff / 60);
        // Returns the minutes with correct singular or plural wording.
        return $m . ' minute' . ($m === 1 ? '' : 's') . ' ago';
    // Closes the if block for under an hour.
    }
    // Checks whether the difference is under one day.
    if ($diff < 86400) {
        // Converts the difference to whole hours.
        $h = (int) floor($diff / 3600);
        // Returns the hours with correct singular or plural wording.
        return $h . ' hour' . ($h === 1 ? '' : 's') . ' ago';
    // Closes the if block for under a day.
    }
    // Converts the difference to whole days.
    $d = (int) floor($diff / 86400);
    // Returns the days with correct singular or plural wording.
    return $d . ' day' . ($d === 1 ? '' : 's') . ' ago';
// Closes the templateTimeAgo function.
}

/* ------------------------------------------------------------------
 * Sync state
 * ------------------------------------------------------------------ */

/** When the last successful check of Drive happened. */
// Returns the time of the last successful Drive check, or null.
function templateLastSyncAt(): ?int {
    // Starts a try block so a database error returns null.
    try {
        // Reads the last_sync_at value from template_sync_state.
        $row = DB::query(
            // Selects the stored sync time for the template sync key.
            'SELECT `last_sync_at` FROM `template_sync_state` WHERE `sync_key` = :k LIMIT 1',
            // Binds the sync key constant to the query placeholder.
            ['k' => GDRIVE_TEMPLATE_SYNC_KEY]
        // Fetches the first matching row.
        )->fetch();
        // Checks whether the row or its time is missing.
        if (!$row || empty($row['last_sync_at'])) {
            // Returns null when Drive was never checked.
            return null;
        // Closes the if block for a missing row.
        }
        // Converts the stored date text into a Unix timestamp.
        $ts = strtotime((string) $row['last_sync_at']);
        // Returns the timestamp, or null if parsing failed.
        return $ts ? (int) $ts : null;
    // Catches database errors.
    } catch (Throwable $e) {
        // Returns null when the state could not be read.
        return null;
    // Closes the catch block.
    }
// Closes the templateLastSyncAt function.
}

/** Remember when Drive was checked and how it went. */
// Saves the time and status of the main template sync.
function templateSaveSyncState(?int $timestamp, string $status): void {
    // Delegates to the shared save function with the sync key.
    templateSaveSyncStateForKey(GDRIVE_TEMPLATE_SYNC_KEY, $timestamp, $status);
// Closes the templateSaveSyncState function.
}

/** Remember when something happened, under a key of its own. */
// Inserts or updates a sync state row under any key.
function templateSaveSyncStateForKey(string $key, ?int $timestamp, string $status): void {
    // Starts a try block so state errors never break the sync.
    try {
        // Writes the state row with an insert-or-update query.
        DB::query(
            // Starts the INSERT into template_sync_state with three columns.
            'INSERT INTO `template_sync_state` (`sync_key`, `last_sync_at`, `last_status`)
                  VALUES (:k, :at, :st)
             ON DUPLICATE KEY UPDATE `last_sync_at` = VALUES(`last_sync_at`), `last_status` = VALUES(`last_status`)',
            // Passes the query parameters as an array.
            [
                // Binds the state key.
                'k'  => $key,
                // Formats the timestamp, or stores null when there is none.
                'at' => $timestamp ? date('Y-m-d H:i:s', $timestamp) : null,
                // Limits the status text to 240 characters.
                'st' => mb_substr($status, 0, 240),
            // Closes the parameter array.
            ]
        // Closes the state upsert query call.
        );
    // Catches errors while saving the state.
    } catch (Throwable $e) {
        // Comment: the state only feeds the timer, so failures are ignored.
        // The state is only used for the refresh timer; ignore write errors.
    // Closes the catch block.
    }
// Closes the templateSaveSyncStateForKey function.
}

/** The lock file that keeps two syncs from running at once. */
// Returns the path of the lock file used to prevent parallel syncs.
function templateSyncLockPath(): string {
    // Builds the storage folder path inside the project.
    $folder = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage';
    // Checks whether the storage folder exists.
    if (!is_dir($folder)) {
        // Creates the storage folder quietly when it is missing.
        @mkdir($folder, 0755, true);
    // Closes the if block that creates the storage folder.
    }
    // Returns the full path of the template sync lock file.
    return $folder . DIRECTORY_SEPARATOR . '.template-sync.lock';
// Closes the templateSyncLockPath function.
}

/* ------------------------------------------------------------------
 * The sync itself
 * ------------------------------------------------------------------ */

/**
 * Read the teacher's Drive folder.
 *
 * The root holds one folder per track; every file inside those folders is a
 * template. The subfolder pages are read in parallel, so the whole folder
 * is known in about a second.
 *
 * @return array<int, array{id:string,name:string,track:string,size:int}>
 */
// Reads the teacher's Drive folder and returns every template file.
function templateDriveEntries(): array {
    // Lists the root Drive folder that holds the track subfolders.
    $root = gdriveListFolder(GDRIVE_TEMPLATE_FOLDER_ID);
    // Checks whether the root folder could not be read.
    if (!$root) {
        // Returns an empty list when Drive gave no answer.
        return [];
    // Closes the if block for an unreadable root folder.
    }

    // Starts the list of template files with their track.
    $entries       = [];
    // Starts the list of subfolder ids with their track codes.
    $subfolderIds  = [];

    // Loops over each item in the root Drive folder.
    foreach ($root as $item) {
        // Checks whether the item is a file and not a folder.
        if (!$item['isFolder']) {
            // Adds a root level file to the list of entries.
            $entries[] = [
                // Stores the Drive file id.
                'id'    => $item['id'],
                // Stores the file name.
                'name'  => $item['name'],
                // Guesses the track from the file name.
                'track' => templateTrackForFolderName($item['name']),
                // Stores the file size, or zero when it is unknown.
                'size'  => (int) ($item['size'] ?? 0),
            // Closes the entry array.
            ];
            // Skips the rest of the loop for this file.
            continue;
        // Closes the if block for files.
        }
        // Remembers the subfolder id and the track it belongs to.
        $subfolderIds[$item['id']] = templateTrackForFolderName($item['name']);
    // Closes the foreach loop over the root folder.
    }

    // Lists the files of all track subfolders in one batch.
    foreach (gdriveListFolders(array_keys($subfolderIds)) as $folderId => $files) {
        // Gets the track code remembered for this subfolder.
        $track = $subfolderIds[$folderId] ?? '';
        // Loops over the files of the subfolder.
        foreach ($files as $file) {
            // Checks whether the item is another folder.
            if ($file['isFolder']) {
                // Skips folders because only files become templates.
                continue;
            // Closes the if block for folders.
            }
            // Adds the subfolder file to the list of entries.
            $entries[] = [
                // Stores the Drive file id.
                'id'    => $file['id'],
                // Stores the file name.
                'name'  => $file['name'],
                // Stores the track of the subfolder.
                'track' => $track,
                // Stores the file size, or zero when it is unknown.
                'size'  => (int) ($file['size'] ?? 0),
            // Closes the entry array.
            ];
        // Closes the foreach loop over the subfolder files.
        }
    // Closes the foreach loop over the subfolders.
    }

    // Returns the complete list of Drive template files.
    return $entries;
// Closes the templateDriveEntries function.
}

/** Every template row, newest first. */
// Loads every row of the templates table.
function templateAllRows(): array {
    // Starts a try block so a database error returns an empty list.
    try {
        // Selects all template rows ordered by template id.
        return DB::query('SELECT * FROM `templates` ORDER BY `template_id` ASC')->fetchAll();
    // Catches database errors.
    } catch (Throwable $e) {
        // Returns an empty list when the query failed.
        return [];
    // Closes the catch block.
    }
// Closes the templateAllRows function.
}

/**
 * Find the template row that belongs to a Drive file.
 *
 * A row that is already linked to another Drive file is never reused, so a
 * rename in Drive cannot steal a template that another file owns.
 */
// Finds the template row whose file name matches a Drive file.
function templateMatchRowByFileName(array $rows, string $fileName, array $usedTemplateIds): ?array {
    // Normalises the Drive file name for comparison.
    $wanted = templateNormaliseName($fileName);
    // Checks whether the name became empty after normalising.
    if ($wanted === '') {
        // Returns null because an empty name cannot match.
        return null;
    // Closes the if block for the empty name.
    }
    // Loops over all template rows.
    foreach ($rows as $row) {
        // Reads the template id of the row.
        $id = (int) ($row['template_id'] ?? 0);
        // Skips rows that are already linked to another Drive file.
        if ($id > 0 && isset($usedTemplateIds[$id])) {
            // Jumps to the next row.
            continue;
        // Closes the if block for already used rows.
        }
        // Checks whether the template file name matches the Drive name.
        if (templateNormaliseName((string) ($row['file_name'] ?? '')) === $wanted) {
            // Returns the matching template row.
            return $row;
        // Closes the if block for a match.
        }
    // Closes the foreach loop over template rows.
    }
    // Returns null when no row matched.
    return null;
// Closes the templateMatchRowByFileName function.
}

/**
 * The kind of change a Drive file carries for a template row.
 *
 * 'added'   the file is not in the system yet
 * 'updated' the file is in the system but its version is different
 * null      the file is the same as the one the system already holds
 *
 * The same rule is used when a change is looked for and when it is applied,
 * so the two can never disagree about what changed.
 */
// Decides whether a Drive file is added, updated, or unchanged.
function templateDriveChangeType(?array $row, int $driveTime, int $driveSize, bool $headOk, int $pageSize): ?string {
    // Checks whether no template row is linked to the file.
    if ($row === null) {
        // Returns added because the file is new to the system.
        return 'added';
    // Closes the if block for a missing row.
    }

    // Reads the stored Drive modified time of the template.
    $knownStamp = trim((string) ($row['drive_modified_at'] ?? ''));
    // Converts the stored modified time to a timestamp, or zero.
    $knownTime  = $knownStamp !== '' ? (int) strtotime($knownStamp) : 0;
    // Reads the stored Drive file size, or zero.
    $knownSize  = (int) ($row['drive_size'] ?? 0);

    // Uses the modified time when both sides have one.
    if ($knownTime > 0 && $headOk) {
        // Checks whether the modified time or the size changed.
        if ($knownTime !== $driveTime || $knownSize !== $driveSize) {
            // Returns updated because Drive has a newer version.
            return 'updated';
        // Closes the if block for a changed file.
        }
        // Returns null because the file is unchanged.
        return null;
    // Closes the if block for a known modified time.
    }

    // Falls back to comparing sizes when no HEAD time is known.
    if ($knownSize > 0 && $pageSize > 0) {
        // Comment: the folder page size is the only signal left.
        // No header came back, so the size of the folder page is the only
        // Comment: this ends the explanation of the size fallback.
        // signal that is left.
        // Returns updated when the sizes differ, otherwise null.
        return ($knownSize !== $pageSize) ? 'updated' : null;
    // Closes the if block for the size fallback.
    }

    // Comment: first link of the file, so compare the local copy size.
    // First time this file is linked. If the copy in the portal is a
    // Comment: a different local size means the teacher replaced the file.
    // different size, the teacher had already replaced it.
    // Builds the local disk path of the stored template file.
    $local     = templateAbsolutePath((string) ($row['file_path'] ?? ''));
    // Reads the local file size, or zero when the file is missing.
    $localSize = is_file($local) ? (int) filesize($local) : 0;
    // Checks whether the local copy differs in size from Drive.
    if ($localSize > 0 && $localSize !== $driveSize) {
        // Returns updated because the local copy is outdated.
        return 'updated';
    // Closes the if block for a different local size.
    }

    // Returns null when nothing shows a change.
    return null;
// Closes the templateDriveChangeType function.
}

/**
 * Look for files in Google Drive that the system does not hold yet.
 *
 * Nothing is downloaded and nothing is changed: the files that differ are
 * only written down, so the coordinator can be told that a synchronisation
 * is waiting. The list is emptied by the synchronisation itself.
 *
 * @return array{ok:bool,message:string,added:int,updated:int,checked:int,skipped:bool}
 */
// Looks for new or changed Drive files without downloading them.
function templateDetectDriveChanges(bool $force = false): array {
    // Prepares the result array with counts and a message.
    $out = ['ok' => false, 'message' => '', 'added' => 0, 'updated' => 0, 'checked' => 0, 'skipped' => false];

    // Makes sure the required tables and columns exist.
    ensureTemplateSyncSchema();

    // Skips the time throttle when the caller forces a check.
    if (!$force) {
        // Reads when Drive was last checked for changes.
        $last = templateLastDetectAt();
        // Checks whether the last check was within the detection interval.
        if ($last !== null && (time() - $last) < GDRIVE_TEMPLATE_DETECT_INTERVAL) {
            // Marks the result as skipped.
            $out['skipped'] = true;
            // Builds a message saying when Drive was last checked.
            $out['message'] = 'Google Drive was looked at ' . templateTimeAgo($last) . '.';
            // Returns without contacting Drive.
            return $out;
        // Closes the if block for the recent check.
        }
    // Closes the if block for the non-forced check.
    }

    // Opens or creates the sync lock file.
    $lock = @fopen(templateSyncLockPath(), 'c');
    // Checks whether the lock file could not be opened.
    if ($lock === false) {
        // Marks the result as skipped.
        $out['skipped'] = true;
        // Sets a message that the check could not start.
        $out['message'] = 'The template check could not start.';
        // Returns without doing any work.
        return $out;
    // Closes the if block for the failed lock open.
    }
    // Tries to take an exclusive lock without waiting.
    if (!flock($lock, LOCK_EX | LOCK_NB)) {
        // Closes the lock handle when the lock is busy.
        fclose($lock);
        // Marks the result as skipped.
        $out['skipped'] = true;
        // Sets a message that another check is already running.
        $out['message'] = 'A template check is already running.';
        // Returns because a sync already holds the lock.
        return $out;
    // Closes the if block for the busy lock.
    }

    // Starts a try block around the Drive work so the lock can be released later.
    try {
        // Reads the list of template files from the Drive folder.
        $entries = templateDriveEntries();
        // Checks whether the Drive folder returned no files.
        if (!$entries) {
            // Sets a message that the Drive folder could not be read.
            $out['message'] = 'The Google Drive folder could not be read. It may no longer be shared.';
            // Saves the failed check state with its message.
            templateSaveDetectState(null, $out['message']);
            // Returns the failed result.
            return $out;
        // Closes the if block for an empty Drive folder.
        }

        // Asks Drive for the modified time and size of every file.
        $metas = gdriveFilesMeta(array_column($entries, 'id'));
        // Loads all template rows to compare them with Drive.
        $rows  = templateAllRows();

        // Starts a lookup of template rows by their Drive file id.
        $linked = [];
        // Loops over every template row.
        foreach ($rows as $row) {
            // Reads the Drive file id stored on the row.
            $driveId = trim((string) ($row['drive_file_id'] ?? ''));
            // Checks whether the row is linked to a Drive file.
            if ($driveId !== '') {
                // Remembers the row under its Drive file id.
                $linked[$driveId] = $row;
            // Closes the if block for linked rows.
            }
        // Closes the foreach loop over template rows.
        }

        // Starts the set of template ids already matched to a file.
        $usedTemplateIds = [];
        // Starts the list of Drive changes waiting to be applied.
        $pending          = [];

        // Loops over every Drive file found in the folder.
        foreach ($entries as $entry) {
            // Gets the HEAD metadata of this file, if any.
            $meta      = $metas[$entry['id']] ?? null;
            // Reads the file size shown on the folder page.
            $pageSize  = (int) ($entry['size'] ?? 0);
            // Checks whether a modified time came back from Drive.
            $headOk    = $meta && !empty($meta['modified']);
            // Uses the Drive modified time, or zero when unknown.
            $driveTime = $headOk ? (int) $meta['modified'] : 0;
            // Uses the HEAD size, or the page size as a fallback.
            $driveSize = $headOk ? (int) $meta['size'] : $pageSize;

            // Skips files that have no time and no size to compare.
            if (!$headOk && $pageSize <= 0) {
                // Jumps to the next Drive file.
                continue;
            // Closes the if block for files without data.
            }
            // Counts this file as checked.
            $out['checked']++;

            // Finds the template row already linked to this Drive file.
            $row = $linked[$entry['id']] ?? null;
            // Checks whether no row is linked yet.
            if ($row === null) {
                // Tries to match an unlinked row by file name.
                $row = templateMatchRowByFileName($rows, $entry['name'], $usedTemplateIds);
            // Closes the if block for an unlinked file.
            }
            // Checks whether a template row was matched.
            if ($row !== null) {
                // Marks the template id as used so it is not matched twice.
                $usedTemplateIds[(int) ($row['template_id'] ?? 0)] = true;
            // Closes the if block for a matched row.
            }

            // Chooses the track from the folder, the file name, or the row.
            $track = $entry['track'] !== '' ? $entry['track'] : templateTrackForFolderName((string) ($row['track_code'] ?? ''));
            // Falls back to the row's track when none was found.
            if ($track === '' && $row) {
                // Uses the track_code stored on the template row.
                $track = (string) ($row['track_code'] ?? '');
            // Closes the if block for the fallback track.
            }

            // Works out whether the file was added, updated, or unchanged.
            $changeType = templateDriveChangeType($row, $driveTime, $driveSize, $headOk, $pageSize);
            // Checks whether the file is unchanged.
            if ($changeType === null) {
                // Skips unchanged files.
                continue;
            // Closes the if block for unchanged files.
            }

            // Adds the change to the pending list.
            $pending[] = [
                // Stores the Drive file id.
                'drive_file_id'     => $entry['id'],
                // Stores the file name.
                'file_name'         => $entry['name'],
                // Stores the kind of change, which is added or updated.
                'change_type'       => $changeType,
                // Stores the track code, or null when the track is unknown.
                'track_code'        => $track !== '' ? $track : null,
                // Stores the Drive modified time, or null when Drive gave none.
                'drive_modified_at' => $driveTime > 0 ? date('Y-m-d H:i:s', $driveTime) : null,
                // Stores the file size for the pending change.
                'drive_size'        => $driveSize,
            // Closes the pending change array.
            ];

            // Checks whether the Drive file is new to the system.
            if ($changeType === 'added') {
                // Counts one added file.
                $out['added']++;
            // Otherwise the file already exists and was changed.
            } else {
                // Counts one updated file.
                $out['updated']++;
            // Closes the if and else block for the change type.
            }
        // Closes the foreach loop over the Drive files.
        }

        // Saves the waiting list, replacing the list of the previous check.
        templateReplacePendingChanges($pending);
        // Remembers when the check ran and how many files are waiting.
        templateSaveDetectState(time(), $out['added'] + $out['updated'] . ' file(s) waiting to be synchronized');

        // Marks the check as successful.
        $out['ok'] = true;
        // Builds the result message, depending on the number of changes.
        $out['message'] = ($out['added'] + $out['updated']) === 0
            // Message used when Drive holds no changes.
            ? 'Google Drive holds no changes.'
            // Message listing how many new and changed files are waiting.
            : $out['added'] . ' new and ' . $out['updated'] . ' changed file(s) are waiting to be synchronized.';

        // Returns the successful result to the caller.
        return $out;
    // Catches any error thrown by the Drive check.
    } catch (Throwable $e) {
        // Builds a failure message with the error text.
        $out['message'] = 'The template check failed: ' . $e->getMessage();
        // Saves the failed check state.
        templateSaveDetectState(null, $out['message']);
        // Returns the failed result.
        return $out;
    // Runs the finally block so the lock is always released.
    } finally {
        // Releases the exclusive lock.
        flock($lock, LOCK_UN);
        // Closes the lock file handle.
        fclose($lock);
    // Closes the finally block.
    }
// Closes the templateDetectDriveChanges function.
}

/**
 * The files that are waiting to be synchronized.
 */
// Returns the Drive files that are waiting to be synchronized.
function templatePendingChanges(?string $trackCode = null): array {
    // Starts a try block so a database error returns an empty list.
    try {
        // Starts the query on the template_drive_pending table.
        $sql    = 'SELECT * FROM `template_drive_pending`';
        // Prepares an empty list of query parameters.
        $params = [];
        // Adds a track filter when a track code was given.
        if ($trackCode !== null && $trackCode !== '') {
            // Adds the WHERE condition for the track code.
            $sql .= ' WHERE `track_code` = :t';
            // Binds the track code parameter.
            $params['t'] = $trackCode;
        // Closes the if block for the track filter.
        }
        // Sorts by change type and then by file name.
        $sql .= ' ORDER BY `change_type` ASC, `file_name` ASC';
        // Runs the query and fetches all waiting rows.
        $rows = DB::query($sql, $params)->fetchAll();
        // Loops over the rows by reference so they can be changed.
        foreach ($rows as &$row) {
            // Formats the Drive modified time into a readable label.
            $row['changedLabel'] = !empty($row['drive_modified_at'])
                // Builds the date label like Jan 5, 2026 3:00 PM.
                ? date('M j, Y g:i A', strtotime((string) $row['drive_modified_at']))
                // Leaves the label empty when there is no modified time.
                : '';
        // Closes the loop body of the waiting rows.
        }
        // Breaks the reference to the last row.
        unset($row);
        // Returns the waiting rows.
        return $rows;
    // Catches database errors.
    } catch (Throwable $e) {
        // Returns an empty list when the query failed.
        return [];
    // Closes the catch block.
    }
// Closes the templatePendingChanges function.
}

/**
 * How many files are waiting to be synchronized.
 */
// Counts how many files are waiting to be synchronized.
function templatePendingCount(?string $trackCode = null): int {
    // Counts the rows returned by templatePendingChanges.
    return count(templatePendingChanges($trackCode));
// Closes the templatePendingCount function.
}

/**
 * Write down the files that are waiting, replacing the list of the last look.
 */
// Replaces the waiting list with the changes of the latest check.
function templateReplacePendingChanges(array $pending): void {
    // Starts a try block so a write error does not stop the portal.
    try {
        // Gets the shared PDO database connection.
        $pdo = DB::getConnection();
        // Empties the waiting table before writing the new list.
        $pdo->exec('DELETE FROM `template_drive_pending`');
        // Checks whether the new waiting list is empty.
        if (!$pending) {
            // Returns because there is nothing to insert.
            return;
        // Closes the if block for an empty list.
        }
        // Prepares the INSERT for one waiting file.
        $stmt = $pdo->prepare(
            // Starts the INSERT into template_drive_pending.
            'INSERT INTO `template_drive_pending`
                (`drive_file_id`, `file_name`, `change_type`, `track_code`, `drive_modified_at`, `drive_size`, `detected_at`)
             VALUES (:dfid, :fname, :ctype, :track, :changed, :size, NOW())'
        // Closes the prepared statement call.
        );
        // Loops over each waiting change.
        foreach ($pending as $item) {
            // Runs the insert with the values of this change.
            $stmt->execute([
                // Binds the Drive file id.
                'dfid'    => $item['drive_file_id'],
                // Binds the file name, cut to 250 characters.
                'fname'   => mb_substr((string) $item['file_name'], 0, 250),
                // Binds the change type, which is added or updated.
                'ctype'   => $item['change_type'],
                // Binds the track code.
                'track'   => $item['track_code'],
                // Binds the Drive modified time.
                'changed' => $item['drive_modified_at'],
                // Binds the file size as an integer.
                'size'    => (int) $item['drive_size'],
            // Closes the parameter array.
            ]);
        // Closes the foreach loop over the waiting changes.
        }
    // Catches write errors.
    } catch (Throwable $e) {
        // Comment: the waiting list is only a hint, so errors are ignored here.
        // The list is only a hint; a failure here must not stop the portal.
    // Closes the catch block.
    }
// Closes the templateReplacePendingChanges function.
}

/**
 * Take the files that were synchronized off the waiting list.
 */
// Removes synchronized files from the waiting list.
function templateClearPendingChanges(array $driveFileIds): void {
    // Starts a try block so errors are ignored.
    try {
        // Checks whether the list of Drive file ids is empty.
        if (!$driveFileIds) {
            // Returns because there is nothing to clear.
            return;
        // Closes the if block for an empty id list.
        }
        // Builds one question mark placeholder per Drive file id.
        $placeholders = implode(',', array_fill(0, count($driveFileIds), '?'));
        // Runs a DELETE for the given Drive file ids.
        DB::query(
            // Deletes the waiting rows whose id is in the placeholder list.
            'DELETE FROM `template_drive_pending` WHERE `drive_file_id` IN (' . $placeholders . ')',
            // Passes the Drive file ids as the query parameters.
            array_values($driveFileIds)
        // Closes the delete query call.
        );
    // Catches database errors.
    } catch (Throwable $e) {
        // Comment: the list is rebuilt on the next check, so nothing else is needed.
        // Nothing to do; the list is rebuilt on the next look.
    // Closes the catch block.
    }
// Closes the templateClearPendingChanges function.
}

/** When Google Drive was looked at the last time. */
// Returns when Drive was last checked, or null.
function templateLastDetectAt(): ?int {
    // Starts a try block so a database error returns null.
    try {
        // Reads the last check time from template_sync_state.
        $row = DB::query(
            // Selects the stored time for the detection key.
            'SELECT `last_sync_at` FROM `template_sync_state` WHERE `sync_key` = :k LIMIT 1',
            // Binds the detection key constant.
            ['k' => GDRIVE_TEMPLATE_DETECT_KEY]
        // Fetches the first matching row.
        )->fetch();
        // Checks whether the row or its time is missing.
        if (!$row || empty($row['last_sync_at'])) {
            // Returns null when Drive was never checked.
            return null;
        // Closes the if block for a missing row.
        }
        // Converts the stored date text into a timestamp.
        $ts = strtotime((string) $row['last_sync_at']);
        // Returns the timestamp, or null if parsing failed.
        return $ts ? (int) $ts : null;
    // Catches database errors.
    } catch (Throwable $e) {
        // Returns null when the state could not be read.
        return null;
    // Closes the catch block.
    }
// Closes the templateLastDetectAt function.
}

/** Remember when Google Drive was looked at. */
// Saves when Drive was last checked and the result message.
function templateSaveDetectState(?int $timestamp, string $status): void {
    // Writes the state under the detection key.
    templateSaveSyncStateForKey(GDRIVE_TEMPLATE_DETECT_KEY, $timestamp, $status);
// Closes the templateSaveDetectState function.
}

/** Create a template row for a file that is not in the system yet. */
// Creates a templates row for a Drive file that is not in the system.
function templateCreateDriveRow(array $entry, int $driveTime, int $driveSize, string $track): ?array {
    // Stops when the track is unknown or the DB class is missing.
    if ($track === '' || !class_exists('DB')) {
        // Returns null because the row cannot be created.
        return null;
    // Closes the if block for bad input.
    }

    // Starts a try block so insert errors return null.
    try {
        // Gets the shared PDO database connection.
        $pdo = DB::getConnection();
        // Finds the tracks row for the track code.
        $trackRow = DB::find('tracks', ['track_code' => $track]);
        // Checks whether the track code exists in the tracks table.
        if (!$trackRow) {
            // Returns null for an unknown track.
            return null;
        // Closes the if block for a missing track.
        }

        // Guesses the document type from the file name.
        $documentType = templateDocTypeForFile($entry['name']);

        // Comment: only an endorsement form belongs to a workflow stage.
        // Only a form belongs to a stage. A paper format is used at every
        // Comment: a paper format is used at every stage of the track.
        // stage of the track and a reference belongs to the track as a
        // Comment: reference documents belong to the track as a whole.
        // whole, so both are filed without a stage.
        // Starts with an empty stage key.
        $stageKey = '';
        // Starts with no stage id.
        $stageId  = null;
        // Checks whether the file is an endorsement form.
        if ($documentType === 'form') {
            // Guesses the stage key from the file name and the track.
            $stageKey = templateStageKeyForFile($entry['name'], $track);
            // Checks whether a stage key was found.
            if ($stageKey !== '') {
                // Looks up the stage id in the workflow_stages table.
                $stage = DB::query(
                    // Selects the stage id for the track and the stage key.
                    'SELECT `stage_id` FROM `workflow_stages` WHERE `track_id` = :t AND `stage_key` = :k LIMIT 1',
                    // Binds the track id and the stage key.
                    ['t' => (int) $trackRow['track_id'], 'k' => $stageKey]
                // Fetches the matching stage row.
                )->fetch();
                // Stores the stage id, or null when no stage matched.
                $stageId = $stage ? (int) $stage['stage_id'] : null;
            // Closes the if block for a stage key.
            }
        // Closes the if block for an endorsement form.
        }

        // Comment: a form with no stage cannot be filed automatically.
        // A form without a stage cannot be filed, so it is flagged for the
        // Comment: it is flagged for the coordinator instead of guessing.
        // coordinator instead of being put in the wrong place.
        // Flags the new template when a form has no stage.
        $needsReview = ($documentType === 'form' && $stageId === null);

        // Reads the coordinator user id who manages templates.
        $manager = (int) databaseCoordinatorUserId();
        // Checks whether no coordinator id was found.
        if ($manager <= 0) {
            // Falls back to user id 1 as the manager.
            $manager = 1;
        // Closes the if block for the fallback manager.
        }

        // Prepares the INSERT that creates the new template row.
        $stmt = $pdo->prepare(
            // Starts the INSERT into the templates table.
            'INSERT INTO `templates`
                (`track_id`, `stage_id`, `template_name`, `description`, `document_type`,
                 `file_name`, `file_path`, `mime_type`, `is_active`, `needs_review`, `managed_by_user_id`,
                 `drive_file_id`, `drive_modified_at`, `drive_size`)
             VALUES
                (:track_id, :stage_id, :template_name, :description, :document_type,
                 :file_name, :file_path, :mime_type, 1, :needs_review, :managed_by_user_id,
                 :drive_file_id, :drive_modified_at, :drive_size)'
        // Closes the prepared statement call.
        );
        // Runs the insert with the values of the new template.
        $stmt->execute([
            // Binds the track id.
            'track_id'          => (int) $trackRow['track_id'],
            // Binds the stage id, or null when there is none.
            'stage_id'          => $stageId,
            // Binds the display name, cut to 145 characters.
            'template_name'     => mb_substr(templateDisplayName($entry['name']), 0, 145),
            // Chooses the description text, depending on the review flag.
            'description'       => $needsReview
                // Description used when the stage could not be worked out.
                ? 'Added from the Google Drive template folder. The stage could not be worked out, so it needs to be filed.'
                // Default description for a file added from Drive.
                : 'Added from the Google Drive template folder.',
            // Binds the document type.
            'document_type'     => $documentType,
            // Binds the original file name, cut to 250 characters.
            'file_name'         => mb_substr($entry['name'], 0, 250),
            // Binds the local storage path of the file.
            'file_path'         => templateDriveLocalPath($track, $entry['name']),
            // Leaves the mime type empty for now.
            'mime_type'         => null,
            // Binds the needs_review flag as 1 or 0.
            'needs_review'      => $needsReview ? 1 : 0,
            // Binds the manager user id.
            'managed_by_user_id' => $manager,
            // Binds the Drive file id.
            'drive_file_id'     => $entry['id'],
            // Binds the Drive modified time as a database date.
            'drive_modified_at' => date('Y-m-d H:i:s', $driveTime),
            // Binds the Drive file size.
            'drive_size'        => $driveSize,
        // Closes the parameter array.
        ]);

        // Reads the id of the row that was just inserted.
        $id = (int) $pdo->lastInsertId();
        // Checks whether the insert did not produce an id.
        if ($id <= 0) {
            // Returns null when the insert failed.
            return null;
        // Closes the if block for a failed insert.
        }

        // Returns the fresh template row from the database.
        return DB::find('templates', ['template_id' => $id]);
    // Catches insert errors.
    } catch (Throwable $e) {
        // Returns null when the row could not be created.
        return null;
    // Closes the catch block.
    }
// Closes the templateCreateDriveRow function.
}

/**
 * Record that a file changed.
 *
 * The unique key on (drive_file_id, changed_at) means the same version of
 * a file can never be announced twice, even if two syncs overlap.
 */
// Records a Drive change as a notification for the menu badge.
function templateRecordNotification(array $row, array $entry, int $changedAt, string $changeType, string $track): bool {
    // Starts a try block so a failed insert returns false.
    try {
        // Runs the INSERT IGNORE into template_notifications.
        $stmt = DB::query(
            // Starts the insert that ignores a version already announced.
            'INSERT IGNORE INTO `template_notifications`
                (`template_id`, `drive_file_id`, `file_name`, `change_type`, `changed_at`, `track_code`)
             VALUES (:tid, :dfid, :fname, :ctype, :changed, :track)',
            // Passes the notification parameters.
            [
                // Binds the template id, or zero when it is unknown.
                'tid'     => (int) ($row['template_id'] ?? 0),
                // Binds the Drive file id.
                'dfid'    => $entry['id'],
                // Binds the file name, cut to 250 characters.
                'fname'   => mb_substr($entry['name'], 0, 250),
                // Binds the change type, which is added or updated.
                'ctype'   => $changeType,
                // Binds the change time as a database date.
                'changed' => date('Y-m-d H:i:s', $changedAt),
                // Binds the track code, or null when it is unknown.
                'track'   => $track !== '' ? $track : null,
            // Closes the parameter array.
            ]
        // Closes the insert query call.
        );
        // Returns true only when a new notification row was inserted.
        return $stmt->rowCount() > 0;
    // Catches insert errors.
    } catch (Throwable $e) {
        // Returns false when the notification was not saved.
        return false;
    // Closes the catch block.
    }
// Closes the templateRecordNotification function.
}

/**
 * Mirror the Drive folder into the portal.
 *
 * @param  bool $force ignore the refresh interval
 * @return array{ok:bool,message:string,added:int,updated:int,checked:int,skipped:bool,downloaded:int}
 */
// Downloads the Drive folder into the portal and records changes.
function syncTemplatesFromDrive(bool $force = false): array {
    // Prepares the result array with counters and a message.
    $out = [
        // Starts the counters for added and updated files.
        'ok' => false, 'message' => '', 'added' => 0, 'updated' => 0,
        // Adds counters for checked, skipped, and downloaded files.
        'checked' => 0, 'skipped' => false, 'downloaded' => 0,
    // Closes the result array.
    ];

    // Makes sure the required tables and columns exist.
    ensureTemplateSyncSchema();

    // Opens or creates the sync lock file.
    $lock = @fopen(templateSyncLockPath(), 'c');
    // Checks whether the lock file could not be opened.
    if ($lock === false) {
        // Marks the sync as skipped.
        $out['skipped'] = true;
        // Sets a message that the sync could not start.
        $out['message'] = 'The template sync could not start.';
        // Returns without doing any work.
        return $out;
    // Closes the if block for the failed lock open.
    }
    // Tries to take an exclusive lock without waiting.
    if (!flock($lock, LOCK_EX | LOCK_NB)) {
        // Closes the lock handle when the lock is busy.
        fclose($lock);
        // Marks the sync as skipped.
        $out['skipped'] = true;
        // Sets a message that another sync is already running.
        $out['message'] = 'A template sync is already running.';
        // Returns because a sync already holds the lock.
        return $out;
    // Closes the if block for the busy lock.
    }

    // Starts a try block so the lock is always released later.
    try {
        // Skips the refresh interval when the caller forces a sync.
        if (!$force) {
            // Reads when the last successful sync happened.
            $last = templateLastSyncAt();
            // Checks whether the last sync was within the refresh interval.
            if ($last !== null && (time() - $last) < GDRIVE_TEMPLATE_SYNC_INTERVAL) {
                // Marks the sync as skipped.
                $out['skipped'] = true;
                // Builds a message saying when Drive was last checked.
                $out['message'] = 'Google Drive was checked ' . templateTimeAgo($last) . '.';
                // Returns without contacting Drive.
                return $out;
            // Closes the if block for the recent sync.
            }
        // Closes the if block for the non-forced sync.
        }

        // Reads the list of template files from the Drive folder.
        $entries = templateDriveEntries();
        // Checks whether the Drive folder returned no files.
        if (!$entries) {
            // Sets a message that the Drive folder could not be read.
            $out['message'] = 'The Google Drive folder could not be read. It may no longer be shared.';
            // Saves the failed sync state.
            templateSaveSyncState(null, $out['message']);
            // Returns the failed result.
            return $out;
        // Closes the if block for an empty Drive folder.
        }

        // Asks Drive for the modified time and size of every file.
        $metas = gdriveFilesMeta(array_column($entries, 'id'));
        // Loads all template rows to compare them with Drive.
        $rows  = templateAllRows();

        // Starts a lookup of template rows by their Drive file id.
        $linked = [];
        // Loops over every template row.
        foreach ($rows as $row) {
            // Reads the Drive file id stored on the row.
            $driveId = trim((string) ($row['drive_file_id'] ?? ''));
            // Checks whether the row is linked to a Drive file.
            if ($driveId !== '') {
                // Remembers the row under its Drive file id.
                $linked[$driveId] = $row;
            // Closes the if block for linked rows.
            }
        // Closes the foreach loop over template rows.
        }

        // Starts the set of template ids already used in this sync.
        $usedTemplateIds = [];
        // Records the time used for all notifications of this run.
        $changedAt = time();
        // Starts the list of Drive file ids that were synchronized.
        $synchronisedIds = [];

        // Loops over every Drive file found in the folder.
        foreach ($entries as $entry) {
            // Gets the HEAD metadata of this file, if any.
            $meta      = $metas[$entry['id']] ?? null;
            // Reads the file size shown on the folder page.
            $pageSize  = (int) ($entry['size'] ?? 0);
            // Checks whether a modified time came back from Drive.
            $headOk    = $meta && !empty($meta['modified']);
            // Uses the Drive modified time, or zero when it is unknown.
            $driveTime = $headOk ? (int) $meta['modified'] : 0;
            // Uses the HEAD size, or the page size as a fallback.
            $driveSize = $headOk ? (int) $meta['size'] : $pageSize;

            // Skips files that have no time and no size to compare.
            if (!$headOk && $pageSize <= 0) {
                // Comment: nothing is known about this file, so it cannot be checked.
                // Nothing is known about this file, so it cannot be checked.
                // Jumps to the next Drive file.
                continue;
            // Closes the if block for files without data.
            }
            // Counts this file as checked.
            $out['checked']++;

            // Finds the template row already linked to this Drive file.
            $row = $linked[$entry['id']] ?? null;
            // Checks whether no row is linked yet.
            if ($row === null) {
                // Tries to match an unlinked row by file name.
                $row = templateMatchRowByFileName($rows, $entry['name'], $usedTemplateIds);
            // Closes the if block for an unlinked file.
            }

            // Chooses the track from the folder, the file name, or the row.
            $track = $entry['track'] !== '' ? $entry['track'] : templateTrackForFolderName((string) ($row['track_code'] ?? ''));
            // Falls back to the row's track when none was found.
            if ($track === '' && $row) {
                // Uses the track_code stored on the template row.
                $track = (string) ($row['track_code'] ?? '');
            // Closes the if block for the fallback track.
            }

            // Starts with no change type for this file.
            $changeType = '';

            // Checks whether the file has no template row yet.
            if ($row === null) {
                // Comment: this is a file the teacher has just added.
                // A file the teacher has just added to the folder.
                // Creates a new template row for the Drive file.
                $newRow = templateCreateDriveRow($entry, $driveTime ?: time(), $driveSize, $track);
                // Checks whether the new row could not be created.
                if (!$newRow) {
                    // Skips the file so the coordinator can fix it later.
                    continue;
                // Closes the if block for a failed insert.
                }
                // Uses the new row for the rest of the loop.
                $row = $newRow;
                // Marks the new template id as used.
                $usedTemplateIds[(int) $row['template_id']] = true;
                // Marks the change as an added file.
                $changeType = 'added';
            // Otherwise the file already has a template row.
            } else {
                // Marks the existing template id as used.
                $usedTemplateIds[(int) ($row['template_id'] ?? 0)] = true;
                // Comment: this uses the same rule as the change detection.
                // The same rule the look for changes uses, so the two can
                // Comment: so both always agree about what changed.
                // never disagree about what changed.
                // Works out the change type, or empty when unchanged.
                $changeType = templateDriveChangeType($row, $driveTime, $driveSize, $headOk, $pageSize) ?? '';
            // Closes the if and else block for the row lookup.
            }

            // Builds the local storage path for the file.
            $localRelative = templateDriveLocalPath($track !== '' ? $track : 'thesis', $entry['name']);
            // Turns the relative path into an absolute disk path.
            $localAbsolute = templateAbsolutePath($localRelative);

            // Marks the file for download when a change was detected.
            $needsDownload = ($changeType !== '');
            // Checks whether no change was detected.
            if (!$needsDownload) {
                // Also downloads when the local file is missing.
                $needsDownload = !is_file($localAbsolute);
            // Closes the if block for the missing local file.
            }

            // Downloads the file from Drive when it is needed.
            if ($needsDownload && gdriveDownloadFile($entry['id'], $localAbsolute)) {
                // Counts one downloaded file.
                $out['downloaded']++;
                // Comment: removes the old copy when no other template uses it.
                // Remove the copy that carried the old name, if nothing else uses it.
                // Reads the file path the template row used before.
                $previous = trim((string) ($row['file_path'] ?? ''));
                // Checks whether the old path is different and not empty.
                if ($previous !== '' && $previous !== $localRelative) {
                    // Deletes the old file when no other template points at it.
                    templateRemoveUnusedFile($previous, (int) ($row['template_id'] ?? 0));
                // Closes the if block for the old path.
                }
            // Closes the if block for a successful download.
            }

            // Builds the column values that link the row to Drive.
            $update = [
                // Stores the Drive file id.
                'drive_file_id' => $entry['id'],
                // Stores the file size from Drive.
                'drive_size'    => $driveSize,
                // Stores the current file name, cut to 250 characters.
                'file_name'     => mb_substr($entry['name'], 0, 250),
                // Stores the local path of the downloaded file.
                'file_path'     => $localRelative,
            // Closes the update array.
            ];
            // Only stores the modified time when Drive gave one.
            if ($headOk) {
                // Stores the Drive modified time as a database date.
                $update['drive_modified_at'] = date('Y-m-d H:i:s', $driveTime);
            // Closes the if block for the modified time.
            }

            // Comment: a template flagged for a missing stage may be filed now.
            // A template that was flagged because its stage could not be
            // Comment: this works when the file name gives the stage away.
            // worked out is filed now if the name gives the stage away.
            // Checks whether the row needs review and is a form with a track.
            if (!empty($row['needsReview']) && $track !== '' && templateDocTypeForFile($entry['name']) === 'form') {
                // Guesses the stage key from the file name and the track.
                $guessedKey = templateStageKeyForFile($entry['name'], $track);
                // Checks whether a stage key was guessed.
                if ($guessedKey !== '') {
                    // Looks up the stage id in the workflow_stages table.
                    $guessedStage = DB::query(
                        // Selects the stage id for the row's track and stage key.
                        'SELECT `stage_id` FROM `workflow_stages` WHERE `track_id` = :t AND `stage_key` = :k LIMIT 1',
                        // Binds the row's track id and the guessed stage key.
                        ['t' => (int) ($row['trackId'] ?? 0), 'k' => $guessedKey]
                    // Fetches the matching stage row.
                    )->fetch();
                    // Checks whether the stage was found.
                    if ($guessedStage) {
                        // Sets the stage id on the template row.
                        $update['stage_id']     = (int) $guessedStage['stage_id'];
                        // Clears the needs_review flag.
                        $update['needs_review'] = 0;
                    // Closes the if block for a found stage.
                    }
                // Closes the if block for a guessed stage key.
                }
            // Closes the if block for a flagged form.
            }

            // Comment: a template set aside because its file was missing comes back.
            // A template that was put aside because its file was missing
            // Comment: it is reactivated once the file exists again.
            // comes back as soon as the file is there.
            // Checks whether an inactive flagged template now has its file.
            if ((int) ($row['is_active'] ?? 1) === 0 && !empty($row['needsReview']) && is_file($localAbsolute)) {
                // Marks the template active again.
                $update['is_active']    = 1;
                // Clears the needs_review flag.
                $update['needs_review'] = 0;
            // Closes the if block for reactivation.
            }

            // Saves the changed columns on the template row.
            DB::update('templates', $update, ['template_id' => (int) ($row['template_id'] ?? 0)]);
            // Only records a notification when something changed.
            if ($changeType !== '') {
                // Reloads the template row so the notification has fresh values.
                $fresh = DB::find('templates', ['template_id' => (int) $row['template_id']]) ?: $row;
                // Inserts the notification and checks that it was new.
                if (templateRecordNotification($fresh, $entry, $driveTime ?: time(), $changeType, $track)) {
                    // Checks whether the change type is added.
                    if ($changeType === 'added') {
                        // Counts one added file.
                        $out['added']++;
                    // Otherwise the change type is updated.
                    } else {
                        // Counts one updated file.
                        $out['updated']++;
                    // Closes the if and else block for the change type.
                    }
                // Closes the if block for a recorded notification.
                }
            // Closes the if block for a change.
            }

            // Comment: the file is in the system now, so it is no longer pending.
            // The file is in the system now, so it is no longer waiting.
            // Remembers the Drive file id as synchronized.
            $synchronisedIds[] = $entry['id'];
        // Closes the foreach loop over the Drive files.
        }

        // Comment: files handled in this run are removed from the waiting list.
        // The files that were taken care of are taken off the waiting list.
        // Comment: files that failed stay on it so the coordinator still sees them.
        // A file that could not be applied stays on it, so the coordinator
        // Comment: this ends the explanation of the pending cleanup.
        // is still told about it.
        // Clears the synchronized files from the waiting list.
        templateClearPendingChanges($synchronisedIds);

        // Marks the sync as successful.
        $out['ok'] = true;
        // Builds the result message with the number of checked files.
        $out['message'] = 'Checked ' . $out['checked'] . ' file' . ($out['checked'] === 1 ? '' : 's') . ' in Google Drive. '
            // Continues the message with the change counts.
            . ($out['added'] + $out['updated'] === 0
                // Message used when everything is already up to date.
                ? 'Everything is up to date.'
                // Message listing how many files were added and updated.
                : $out['added'] . ' new and ' . $out['updated'] . ' updated file' . (($out['added'] + $out['updated']) === 1 ? '' : 's') . '.');
        // Saves the sync time and the result message.
        templateSaveSyncState($changedAt, $out['message']);

        // Returns the successful result to the caller.
        return $out;
    // Catches any error thrown by the sync.
    } catch (Throwable $e) {
        // Builds a failure message with the error text.
        $out['message'] = 'The template sync failed: ' . $e->getMessage();
        // Saves the failed sync state.
        templateSaveSyncState(null, $out['message']);
        // Returns the failed result.
        return $out;
    // Runs the finally block so the lock is always released.
    } finally {
        // Releases the exclusive lock.
        flock($lock, LOCK_UN);
        // Closes the lock file handle.
        fclose($lock);
    // Closes the finally block.
    }
// Closes the syncTemplatesFromDrive function.
}

/** Delete a replaced file when no other template still points at it. */
// Deletes an old template file when no other row uses it.
function templateRemoveUnusedFile(string $relativePath, int $templateId): void {
    // Starts a try block so a database error stops the delete.
    try {
        // Counts other templates that still use the same file path.
        $still = DB::query(
            // Selects the count of rows with the path but another id.
            'SELECT COUNT(*) AS c FROM `templates` WHERE `file_path` = :p AND `template_id` <> :id',
            // Binds the file path and the current template id.
            ['p' => $relativePath, 'id' => $templateId]
        // Fetches the count row.
        )->fetch();
        // Checks whether another template still points at the file.
        if ((int) ($still['c'] ?? 0) > 0) {
            // Returns without deleting the file.
            return;
        // Closes the if block for a file still in use.
        }
    // Catches database errors.
    } catch (Throwable $e) {
        // Returns without deleting when the check failed.
        return;
    // Closes the catch block.
    }

    // Turns the relative path into an absolute disk path.
    $absolute = templateAbsolutePath($relativePath);
    // Only deletes a real file inside the assets/papers folder.
    if (is_file($absolute) && str_contains(str_replace('\\', '/', $relativePath), 'assets/papers/')) {
        // Deletes the file quietly.
        @unlink($absolute);
    // Closes the if block for the delete.
    }
// Closes the templateRemoveUnusedFile function.
}

/**
 * True when Google Drive has not been looked at for a while.
 */
// Tells whether Drive has not been checked for a while.
function templateSyncIsStale(): bool {
    // Reads when Drive was last checked.
    $last = templateLastDetectAt();
    // Checks whether Drive was never checked.
    if ($last === null) {
        // Returns true so the first check always runs.
        return true;
    // Closes the if block for a missing check time.
    }
    // Returns true when the last check is older than the interval.
    return (time() - $last) >= GDRIVE_TEMPLATE_DETECT_INTERVAL;
// Closes the templateSyncIsStale function.
}

/**
 * Look at Google Drive when the portal has not looked at it for a while.
 *
 * This only finds out what changed; it does not download anything. The
 * changes are applied when the coordinator clicks "Sync from Google Drive",
 * so the portal can say that a change is waiting before it happens.
 */
// Checks Drive automatically when the portal has not looked for a while.
function maybeAutoSyncTemplates(): array {
    // Makes sure the required tables and columns exist.
    ensureTemplateSyncSchema();
    // Checks whether the last check is still recent.
    if (!templateSyncIsStale()) {
        // Returns a skipped result without contacting Drive.
        return ['ok' => true, 'skipped' => true, 'message' => '', 'added' => 0, 'updated' => 0, 'checked' => 0, 'downloaded' => 0];
    // Closes the if block for a recent check.
    }
    // Runs the change detection without forcing it.
    return templateDetectDriveChanges(false);
// Closes the maybeAutoSyncTemplates function.
}

/* ------------------------------------------------------------------
 * Notifications
 * ------------------------------------------------------------------ */

/** The column that holds the "seen" time of a role. */
// Returns the notifications column that stores the seen time.
function templateSeenColumn(string $role): string {
    // Chooses the coordinator column or the student column.
    return $role === 'coordinator' ? 'seen_coordinator_at' : 'seen_student_at';
// Closes the templateSeenColumn function.
}

/**
 * How many template updates this user has not looked at yet.
 *
 * A student only counts the updates of their own track; the coordinator
 * counts all of them. A file that was changed more than once counts once, so
 * the number of files is not the number of changes.
 */
// Counts unread template updates for a role and a track.
function templateNotificationsUnreadCount(string $role, string $trackCode = ''): int {
    // Starts a try block so a database error returns zero.
    try {
        // Gets the seen column that belongs to the role.
        $seen = templateSeenColumn($role);
        // Counts distinct files whose seen time is still empty.
        $sql = 'SELECT COUNT(DISTINCT COALESCE(`drive_file_id`, `file_name`)) AS c FROM `template_notifications` WHERE `' . $seen . '` IS NULL';
        // Prepares an empty list of query parameters.
        $params = [];
        // Students only see the updates of their own track.
        if ($role !== 'coordinator' && $trackCode !== '') {
            // Adds the track filter, including general updates.
            $sql .= " AND (`track_code` = :t OR `track_code` IS NULL OR `track_code` = '')";
            // Binds the student's track code.
            $params['t'] = $trackCode;
        // Closes the if block for the track filter.
        }
        // Runs the count query and fetches the row.
        $row = DB::query($sql, $params)->fetch();
        // Returns the count, or zero when it is missing.
        return (int) ($row['c'] ?? 0);
    // Catches database errors.
    } catch (Throwable $e) {
        // Returns zero when the count could not be read.
        return 0;
    // Closes the catch block.
    }
// Closes the templateNotificationsUnreadCount function.
}

/**
 * The template updates, newest first.
 *
 * A file that was changed more than once is listed once, with its last
 * change, so the history stays short and the same file is not shown again and
 * again.
 *
 * @param  string $role       'coordinator' or 'student'
 * @param  string $trackCode  only this track, or every track when empty
 * @param  int    $limit      how many files at most
 * @param  string $mode       'all', 'unseen' (not looked at yet) or 'seen'
 */
// Returns the template update list for a role, newest first.
function templateNotificationsList(string $role, string $trackCode = '', int $limit = 25, string $mode = 'all'): array {
    // Starts a try block so a database error returns an empty list.
    try {
        // Gets the seen column that belongs to the role.
        $seen = templateSeenColumn($role);
        // Selects the notification and the matching template details.
        $sql = 'SELECT n.*, t.template_name, t.file_path, t.stage_id, t.track_id
                  FROM `template_notifications` n
                  LEFT JOIN `templates` t ON t.template_id = n.template_id
                 WHERE 1 = 1';
        // Prepares an empty list of query parameters.
        $params = [];
        // Filters to updates that were not seen yet.
        if ($mode === 'unseen') {
            // Adds the condition that the seen time is empty.
            $sql .= ' AND n.`' . $seen . '` IS NULL';
        // Otherwise filters to updates that were already seen.
        } elseif ($mode === 'seen') {
            // Adds the condition that the seen time is filled.
            $sql .= ' AND n.`' . $seen . '` IS NOT NULL';
        // Closes the if and elseif block for the mode.
        }
        // Students only see the updates of their own track.
        if ($role !== 'coordinator' && $trackCode !== '') {
            // Adds the track filter, including general updates.
            $sql .= " AND (n.`track_code` = :t OR n.`track_code` IS NULL OR n.`track_code` = '')";
            // Binds the student's track code.
            $params['t'] = $trackCode;
        // Closes the if block for the track filter.
        }
        // Sorts newest first and limits the number of rows.
        $sql .= ' ORDER BY n.`changed_at` DESC, n.`notification_id` DESC LIMIT ' . max(1, min(200, (int) $limit));

        // Runs the query and fetches all matching rows.
        $rows = DB::query($sql, $params)->fetchAll();

        // Starts a block comment explaining how duplicate files are removed.
        /*
         * One file is one entry. The rows come newest first, so the first
         * row of a file is its last change and the older ones are left out.
         */
        // Starts the list of updates that will be returned.
        $listed = [];
        // Starts the set of files already added to the list.
        $seenFiles = [];
        // Loops over the rows, which come newest first.
        foreach ($rows as $row) {
            // Uses the Drive file id as the key of the file.
            $fileKey = trim((string) ($row['drive_file_id'] ?? ''));
            // Checks whether the notification has no Drive file id.
            if ($fileKey === '') {
                // Falls back to the lower-cased file name as the key.
                $fileKey = strtolower(trim((string) ($row['file_name'] ?? '')));
            // Closes the if block for the fallback key.
            }
            // Checks whether this file is already in the list.
            if ($fileKey !== '' && isset($seenFiles[$fileKey])) {
                // Skips older changes of the same file.
                continue;
            // Closes the if block for an older duplicate.
            }
            // Checks whether the file has a key.
            if ($fileKey !== '') {
                // Remembers the file so later rows are skipped.
                $seenFiles[$fileKey] = true;
            // Closes the if block for remembering the key.
            }
            // Marks the update as new when its seen time is empty.
            $row['isNew'] = empty($row[$seen]);
            // Formats the change time into a readable label.
            $row['changedLabel'] = date('M j, Y g:i A', strtotime((string) $row['changed_at']));
            // Adds friendly text like 3 minutes ago.
            $row['ago'] = templateTimeAgo(strtotime((string) $row['changed_at']) ?: null);
            // Adds the update row to the returned list.
            $listed[] = $row;
            // Checks whether the limit was reached.
            if (count($listed) >= max(1, (int) $limit)) {
                // Stops the loop once enough updates were collected.
                break;
            // Closes the if block for the limit.
            }
        // Closes the foreach loop over the rows.
        }
        // Returns the list of updates.
        return $listed;
    // Catches database errors.
    } catch (Throwable $e) {
        // Returns an empty list when the query failed.
        return [];
    // Closes the catch block.
    }
// Closes the templateNotificationsList function.
}

/**
 * Take the update entries of the last day off the table.
 *
 * The history is there to show what changed recently, so an entry that is
 * older than a day is removed instead of piling up.
 */
// Deletes template update entries older than the given hours.
function templatePruneNotifications(int $hours = 24): int {
    // Starts a try block so a database error returns zero.
    try {
        // Comment: the database clock is used because it wrote the times.
        // The clock of the database is used, because it is the clock that
        // Comment: this ends the explanation of the delete time.
        // wrote the times of the entries.
        // Runs the DELETE on old notification rows.
        return DB::query(
            // Deletes rows changed before the cutoff of the last hours.
            'DELETE FROM `template_notifications` WHERE `changed_at` < (NOW() - INTERVAL ' . max(1, (int) $hours) . ' HOUR)'
        // Returns the number of deleted rows.
        )->rowCount();
    // Catches database errors.
    } catch (Throwable $e) {
        // Returns zero when the delete failed.
        return 0;
    // Closes the catch block.
    }
// Closes the templatePruneNotifications function.
}

/** Mark the updates of a role as looked at. */
// Marks all unread updates of a role as seen.
function markTemplateNotificationsSeen(string $role, string $trackCode = ''): int {
    // Starts a try block so a database error returns zero.
    try {
        // Gets the seen column that belongs to the role.
        $seen = templateSeenColumn($role);
        // Starts the UPDATE that fills the seen time with NOW().
        $sql = 'UPDATE `template_notifications` SET `' . $seen . '` = NOW() WHERE `' . $seen . '` IS NULL';
        // Prepares an empty list of query parameters.
        $params = [];
        // Students only mark the updates of their own track.
        if ($role !== 'coordinator' && $trackCode !== '') {
            // Adds the track filter, including general updates.
            $sql .= " AND (`track_code` = :t OR `track_code` IS NULL OR `track_code` = '')";
            // Binds the student's track code.
            $params['t'] = $trackCode;
        // Closes the if block for the track filter.
        }
        // Runs the update and returns the number of changed rows.
        return DB::query($sql, $params)->rowCount();
    // Catches database errors.
    } catch (Throwable $e) {
        // Returns zero when the update failed.
        return 0;
    // Closes the catch block.
    }
// Closes the markTemplateNotificationsSeen function.
}

/** The track of the signed in student. */
// Returns the track code of the signed in student.
function currentStudentTrackCode(): string {
    // Caches the track in a static variable for the request.
    static $track = null;
    // Checks whether the track was already loaded.
    if ($track !== null) {
        // Returns the cached track code.
        return $track;
    // Closes the if block for the cached track.
    }
    // Starts with an empty track code.
    $track = '';
    // Starts a try block so an error keeps the empty track.
    try {
        // Reads the user id from the session, or zero.
        $userId = (int) ($_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? 0);
        // Only queries the database when a user is signed in.
        if ($userId > 0 && class_exists('DB')) {
            // Selects the track of the student row for this user.
            $row = DB::query('SELECT `track` FROM `students` WHERE `user_id` = :u LIMIT 1', ['u' => $userId])->fetch();
            // Lower-cases and trims the stored track code.
            $track = strtolower(trim((string) ($row['track'] ?? '')));
        // Closes the if block for the session user.
        }
    // Catches database errors.
    } catch (Throwable $e) {
        // Resets the track to empty text.
        $track = '';
    // Closes the catch block.
    }
    // Returns the student's track code.
    return $track;
// Closes the currentStudentTrackCode function.
}
