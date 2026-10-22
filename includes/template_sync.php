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

require_once __DIR__ . '/gdrive.php';

if (!defined('GDRIVE_TEMPLATE_SYNC_INTERVAL')) {
    /** Seconds before the portal checks Google Drive again. */
    define('GDRIVE_TEMPLATE_SYNC_INTERVAL', 900);
}

if (!defined('GDRIVE_TEMPLATE_SYNC_KEY')) {
    /** Row key of the sync state in template_sync_state. */
    define('GDRIVE_TEMPLATE_SYNC_KEY', 'google_drive_templates');
}

if (!defined('GDRIVE_TEMPLATE_DETECT_INTERVAL')) {
    /** Seconds before Google Drive is looked at for changes again. */
    define('GDRIVE_TEMPLATE_DETECT_INTERVAL', 60);
}

if (!defined('GDRIVE_TEMPLATE_DETECT_KEY')) {
    /** Row key of the last look in template_sync_state. */
    define('GDRIVE_TEMPLATE_DETECT_KEY', 'google_drive_templates_check');
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
function ensureTemplateSyncSchema(): void {
    static $done = false;
    if ($done || !class_exists('DB')) {
        return;
    }
    $done = true;

    $marker = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . '.template-schema.v1';
    if (is_file($marker)) {
        return;
    }

    try {
        $pdo = DB::getConnection();

        $columns = [];
        foreach ($pdo->query('SHOW COLUMNS FROM `templates`') as $column) {
            $columns[$column['Field']] = true;
        }
        if (!isset($columns['drive_file_id'])) {
            $pdo->exec('ALTER TABLE `templates` ADD COLUMN `drive_file_id` VARCHAR(120) DEFAULT NULL AFTER `file_path`');
        }
        if (!isset($columns['drive_modified_at'])) {
            $pdo->exec('ALTER TABLE `templates` ADD COLUMN `drive_modified_at` DATETIME DEFAULT NULL AFTER `drive_file_id`');
        }
        if (!isset($columns['drive_size'])) {
            $pdo->exec('ALTER TABLE `templates` ADD COLUMN `drive_size` BIGINT DEFAULT NULL AFTER `drive_modified_at`');
        }
        if (!isset($columns['needs_review'])) {
            $pdo->exec('ALTER TABLE `templates` ADD COLUMN `needs_review` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`');
        }

        $pdo->exec(
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
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS `template_sync_state` (
                `sync_key`     VARCHAR(60) NOT NULL,
                `last_sync_at` DATETIME    DEFAULT NULL,
                `last_status`  VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (`sync_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        // The files that are in Google Drive but not in the system yet. The
        // list is written when Drive is looked at and emptied by the
        // synchronisation, so the coordinator can be told that a change is
        // waiting before it is applied.
        $pdo->exec(
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
        );

        // The seminar rows were saved with the folder name "SEMINAR" while the
        // files live in "SEMINAR PAPER", so those downloads were dead links.
        $pdo->exec(
            "UPDATE `templates`
                SET `file_path` = REPLACE(`file_path`, 'assets/papers/SEMINAR/', 'assets/papers/SEMINAR PAPER/')
              WHERE `file_path` LIKE 'assets/papers/SEMINAR/%'"
        );

        $folder = dirname($marker);
        if (!is_dir($folder)) {
            @mkdir($folder, 0755, true);
        }
        @file_put_contents($marker, (string) time());
    } catch (Throwable $e) {
        // The migration file has the same statements; nothing else to do.
    }
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
function templateFileUrl(?string $filePath): string {
    $path = trim((string) $filePath);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    // Encode every part of the path but keep the folder separators.
    $encoded = implode('/', array_map('rawurlencode', explode('/', ltrim($path, '/'))));
    return url($encoded);
}

/** Absolute path of a stored template file. */
function templateAbsolutePath(string $relativePath): string {
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($relativePath, '/'));
}

/** The track a Drive subfolder belongs to. */
function templateTrackForFolderName(string $folderName): string {
    $name = strtoupper(trim($folderName));
    if (str_contains($name, 'CAPSTONE')) {
        return 'capstone';
    }
    if (str_contains($name, 'SEMINAR')) {
        return 'seminar';
    }
    if (str_contains($name, 'THESIS')) {
        return 'thesis';
    }
    return '';
}

/** The local papers folder of a track. */
function templateFolderForTrack(string $track): string {
    $folders = ['thesis' => 'THESIS', 'capstone' => 'CAPSTONE', 'seminar' => 'SEMINAR PAPER'];
    return $folders[strtolower(trim($track))] ?? '';
}

/**
 * Guess the stage of a Drive file from its name.
 *
 * The endorsement forms of a track name the stage they belong to, so the
 * wording of the file is what is read. An empty answer means the stage
 * could not be worked out.
 */
function templateStageKeyForFile(string $fileName, string $track): string {
    $name = strtoupper(trim($fileName));

    $stage = '';
    if (str_contains($name, 'FINAL')) {
        $stage = 'final';
    } elseif (str_contains($name, 'CONCEPT')) {
        $stage = 'concept';
    } elseif (str_contains($name, 'PROPOSAL')) {
        $stage = 'proposal';
    }
    if ($stage === '') {
        return '';
    }

    // Only a stage that exists in this track is used.
    foreach (templateWorkflowStages()[$track] ?? [] as $workflowStage) {
        if ((string) ($workflowStage['key'] ?? '') === $stage) {
            return $stage;
        }
    }

    return '';
}

/**
 * Guess the document type of a Drive file from its name.
 *
 * An endorsement form belongs to a stage, so it is filed under the stage
 * its name mentions. A paper format is used at every stage of the track and
 * a reference document belongs to the track as a whole.
 */
function templateDocTypeForFile(string $fileName): string {
    $name = strtoupper($fileName);
    // "FORM" is only a form when it stands on its own; a file called
    // "... FORMAT.docx" is the paper format of the track.
    if (preg_match('/\bENDORSEMENT\b/', $name) || preg_match('/\bFORM\b/', $name)) {
        return 'form';
    }
    if (preg_match('/\bGUIDELINES?\b/', $name) || preg_match('/\bCOVER PAGE\b/', $name)) {
        return 'reference';
    }
    return 'template';
}

/** A readable name for a file that has no template row yet. */
function templateDisplayName(string $fileName): string {
    $name = preg_replace('/\.[A-Za-z0-9]{1,6}$/', '', trim($fileName));
    $name = preg_replace('/^\d+\s*[.)-]\s*/', '', (string) $name);
    $name = preg_replace('/\s*[-_]\s*$/', '', (string) $name);
    $name = trim(preg_replace('/\s+/', ' ', (string) $name));
    return $name !== '' ? $name : trim($fileName);
}

/** Normalised file name, used to match a Drive file with a template row. */
function templateNormaliseName(string $name): string {
    return (string) preg_replace('/[^a-z0-9]+/', '', strtolower(trim($name)));
}

/** Where a Drive file is stored inside the portal. */
function templateDriveLocalPath(string $track, string $fileName): string {
    $folder = templateFolderForTrack($track);
    $safe = preg_replace('/[\\\\\/:\*\?"<>\|\x00-\x1F]+/', '-', $fileName);
    $safe = trim((string) $safe);
    $safe = trim($safe, " \t.-");
    if ($safe === '') {
        $safe = 'template';
    }
    return 'assets/papers/' . $folder . '/' . $safe;
}

/**
 * The current time of the database.
 *
 * The times of the template records are written by the database, so they are
 * compared with the clock of the database and not with the clock of the web
 * server, which can sit in another time zone.
 */
function templateDatabaseNow(): string {
    static $now = null;
    if ($now !== null) {
        return $now;
    }
    $now = '';
    if (class_exists('DB')) {
        try {
            $now = (string) (DB::query('SELECT NOW() AS n')->fetch()['n'] ?? '');
        } catch (Throwable $e) {
            $now = '';
        }
    }
    if ($now === '') {
        $now = date('Y-m-d H:i:s');
    }
    return $now;
}

/** "3 minutes ago" style text for a timestamp. */
function templateTimeAgo(?int $timestamp): string {
    if (!$timestamp) {
        return 'never';
    }
    $now = strtotime(templateDatabaseNow());
    if ($now === false) {
        $now = time();
    }
    $diff = max(0, $now - $timestamp);
    if ($diff < 60) {
        return 'just now';
    }
    if ($diff < 3600) {
        $m = (int) floor($diff / 60);
        return $m . ' minute' . ($m === 1 ? '' : 's') . ' ago';
    }
    if ($diff < 86400) {
        $h = (int) floor($diff / 3600);
        return $h . ' hour' . ($h === 1 ? '' : 's') . ' ago';
    }
    $d = (int) floor($diff / 86400);
    return $d . ' day' . ($d === 1 ? '' : 's') . ' ago';
}

/* ------------------------------------------------------------------
 * Sync state
 * ------------------------------------------------------------------ */

/** When the last successful check of Drive happened. */
function templateLastSyncAt(): ?int {
    try {
        $row = DB::query(
            'SELECT `last_sync_at` FROM `template_sync_state` WHERE `sync_key` = :k LIMIT 1',
            ['k' => GDRIVE_TEMPLATE_SYNC_KEY]
        )->fetch();
        if (!$row || empty($row['last_sync_at'])) {
            return null;
        }
        $ts = strtotime((string) $row['last_sync_at']);
        return $ts ? (int) $ts : null;
    } catch (Throwable $e) {
        return null;
    }
}

/** Remember when Drive was checked and how it went. */
function templateSaveSyncState(?int $timestamp, string $status): void {
    templateSaveSyncStateForKey(GDRIVE_TEMPLATE_SYNC_KEY, $timestamp, $status);
}

/** Remember when something happened, under a key of its own. */
function templateSaveSyncStateForKey(string $key, ?int $timestamp, string $status): void {
    try {
        DB::query(
            'INSERT INTO `template_sync_state` (`sync_key`, `last_sync_at`, `last_status`)
                  VALUES (:k, :at, :st)
             ON DUPLICATE KEY UPDATE `last_sync_at` = VALUES(`last_sync_at`), `last_status` = VALUES(`last_status`)',
            [
                'k'  => $key,
                'at' => $timestamp ? date('Y-m-d H:i:s', $timestamp) : null,
                'st' => mb_substr($status, 0, 240),
            ]
        );
    } catch (Throwable $e) {
        // The state is only used for the refresh timer; ignore write errors.
    }
}

/** The lock file that keeps two syncs from running at once. */
function templateSyncLockPath(): string {
    $folder = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage';
    if (!is_dir($folder)) {
        @mkdir($folder, 0755, true);
    }
    return $folder . DIRECTORY_SEPARATOR . '.template-sync.lock';
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
function templateDriveEntries(): array {
    $root = gdriveListFolder(GDRIVE_TEMPLATE_FOLDER_ID);
    if (!$root) {
        return [];
    }

    $entries       = [];
    $subfolderIds  = [];

    foreach ($root as $item) {
        if (!$item['isFolder']) {
            $entries[] = [
                'id'    => $item['id'],
                'name'  => $item['name'],
                'track' => templateTrackForFolderName($item['name']),
                'size'  => (int) ($item['size'] ?? 0),
            ];
            continue;
        }
        $subfolderIds[$item['id']] = templateTrackForFolderName($item['name']);
    }

    foreach (gdriveListFolders(array_keys($subfolderIds)) as $folderId => $files) {
        $track = $subfolderIds[$folderId] ?? '';
        foreach ($files as $file) {
            if ($file['isFolder']) {
                continue;
            }
            $entries[] = [
                'id'    => $file['id'],
                'name'  => $file['name'],
                'track' => $track,
                'size'  => (int) ($file['size'] ?? 0),
            ];
        }
    }

    return $entries;
}

/** Every template row, newest first. */
function templateAllRows(): array {
    try {
        return DB::query('SELECT * FROM `templates` ORDER BY `template_id` ASC')->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Find the template row that belongs to a Drive file.
 *
 * A row that is already linked to another Drive file is never reused, so a
 * rename in Drive cannot steal a template that another file owns.
 */
function templateMatchRowByFileName(array $rows, string $fileName, array $usedTemplateIds): ?array {
    $wanted = templateNormaliseName($fileName);
    if ($wanted === '') {
        return null;
    }
    foreach ($rows as $row) {
        $id = (int) ($row['template_id'] ?? 0);
        if ($id > 0 && isset($usedTemplateIds[$id])) {
            continue;
        }
        if (templateNormaliseName((string) ($row['file_name'] ?? '')) === $wanted) {
            return $row;
        }
    }
    return null;
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
function templateDriveChangeType(?array $row, int $driveTime, int $driveSize, bool $headOk, int $pageSize): ?string {
    if ($row === null) {
        return 'added';
    }

    $knownStamp = trim((string) ($row['drive_modified_at'] ?? ''));
    $knownTime  = $knownStamp !== '' ? (int) strtotime($knownStamp) : 0;
    $knownSize  = (int) ($row['drive_size'] ?? 0);

    if ($knownTime > 0 && $headOk) {
        if ($knownTime !== $driveTime || $knownSize !== $driveSize) {
            return 'updated';
        }
        return null;
    }

    if ($knownSize > 0 && $pageSize > 0) {
        // No header came back, so the size of the folder page is the only
        // signal that is left.
        return ($knownSize !== $pageSize) ? 'updated' : null;
    }

    // First time this file is linked. If the copy in the portal is a
    // different size, the teacher had already replaced it.
    $local     = templateAbsolutePath((string) ($row['file_path'] ?? ''));
    $localSize = is_file($local) ? (int) filesize($local) : 0;
    if ($localSize > 0 && $localSize !== $driveSize) {
        return 'updated';
    }

    return null;
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
function templateDetectDriveChanges(bool $force = false): array {
    $out = ['ok' => false, 'message' => '', 'added' => 0, 'updated' => 0, 'checked' => 0, 'skipped' => false];

    ensureTemplateSyncSchema();

    if (!$force) {
        $last = templateLastDetectAt();
        if ($last !== null && (time() - $last) < GDRIVE_TEMPLATE_DETECT_INTERVAL) {
            $out['skipped'] = true;
            $out['message'] = 'Google Drive was looked at ' . templateTimeAgo($last) . '.';
            return $out;
        }
    }

    $lock = @fopen(templateSyncLockPath(), 'c');
    if ($lock === false) {
        $out['skipped'] = true;
        $out['message'] = 'The template check could not start.';
        return $out;
    }
    if (!flock($lock, LOCK_EX | LOCK_NB)) {
        fclose($lock);
        $out['skipped'] = true;
        $out['message'] = 'A template check is already running.';
        return $out;
    }

    try {
        $entries = templateDriveEntries();
        if (!$entries) {
            $out['message'] = 'The Google Drive folder could not be read. It may no longer be shared.';
            templateSaveDetectState(null, $out['message']);
            return $out;
        }

        $metas = gdriveFilesMeta(array_column($entries, 'id'));
        $rows  = templateAllRows();

        $linked = [];
        foreach ($rows as $row) {
            $driveId = trim((string) ($row['drive_file_id'] ?? ''));
            if ($driveId !== '') {
                $linked[$driveId] = $row;
            }
        }

        $usedTemplateIds = [];
        $pending          = [];

        foreach ($entries as $entry) {
            $meta      = $metas[$entry['id']] ?? null;
            $pageSize  = (int) ($entry['size'] ?? 0);
            $headOk    = $meta && !empty($meta['modified']);
            $driveTime = $headOk ? (int) $meta['modified'] : 0;
            $driveSize = $headOk ? (int) $meta['size'] : $pageSize;

            if (!$headOk && $pageSize <= 0) {
                continue;
            }
            $out['checked']++;

            $row = $linked[$entry['id']] ?? null;
            if ($row === null) {
                $row = templateMatchRowByFileName($rows, $entry['name'], $usedTemplateIds);
            }
            if ($row !== null) {
                $usedTemplateIds[(int) ($row['template_id'] ?? 0)] = true;
            }

            $track = $entry['track'] !== '' ? $entry['track'] : templateTrackForFolderName((string) ($row['track_code'] ?? ''));
            if ($track === '' && $row) {
                $track = (string) ($row['track_code'] ?? '');
            }

            $changeType = templateDriveChangeType($row, $driveTime, $driveSize, $headOk, $pageSize);
            if ($changeType === null) {
                continue;
            }

            $pending[] = [
                'drive_file_id'     => $entry['id'],
                'file_name'         => $entry['name'],
                'change_type'       => $changeType,
                'track_code'        => $track !== '' ? $track : null,
                'drive_modified_at' => $driveTime > 0 ? date('Y-m-d H:i:s', $driveTime) : null,
                'drive_size'        => $driveSize,
            ];

            if ($changeType === 'added') {
                $out['added']++;
            } else {
                $out['updated']++;
            }
        }

        templateReplacePendingChanges($pending);
        templateSaveDetectState(time(), $out['added'] + $out['updated'] . ' file(s) waiting to be synchronized');

        $out['ok'] = true;
        $out['message'] = ($out['added'] + $out['updated']) === 0
            ? 'Google Drive holds no changes.'
            : $out['added'] . ' new and ' . $out['updated'] . ' changed file(s) are waiting to be synchronized.';

        return $out;
    } catch (Throwable $e) {
        $out['message'] = 'The template check failed: ' . $e->getMessage();
        templateSaveDetectState(null, $out['message']);
        return $out;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

/**
 * The files that are waiting to be synchronized.
 */
function templatePendingChanges(?string $trackCode = null): array {
    try {
        $sql    = 'SELECT * FROM `template_drive_pending`';
        $params = [];
        if ($trackCode !== null && $trackCode !== '') {
            $sql .= ' WHERE `track_code` = :t';
            $params['t'] = $trackCode;
        }
        $sql .= ' ORDER BY `change_type` ASC, `file_name` ASC';
        $rows = DB::query($sql, $params)->fetchAll();
        foreach ($rows as &$row) {
            $row['changedLabel'] = !empty($row['drive_modified_at'])
                ? date('M j, Y g:i A', strtotime((string) $row['drive_modified_at']))
                : '';
        }
        unset($row);
        return $rows;
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * How many files are waiting to be synchronized.
 */
function templatePendingCount(?string $trackCode = null): int {
    return count(templatePendingChanges($trackCode));
}

/**
 * Write down the files that are waiting, replacing the list of the last look.
 */
function templateReplacePendingChanges(array $pending): void {
    try {
        $pdo = DB::getConnection();
        $pdo->exec('DELETE FROM `template_drive_pending`');
        if (!$pending) {
            return;
        }
        $stmt = $pdo->prepare(
            'INSERT INTO `template_drive_pending`
                (`drive_file_id`, `file_name`, `change_type`, `track_code`, `drive_modified_at`, `drive_size`, `detected_at`)
             VALUES (:dfid, :fname, :ctype, :track, :changed, :size, NOW())'
        );
        foreach ($pending as $item) {
            $stmt->execute([
                'dfid'    => $item['drive_file_id'],
                'fname'   => mb_substr((string) $item['file_name'], 0, 250),
                'ctype'   => $item['change_type'],
                'track'   => $item['track_code'],
                'changed' => $item['drive_modified_at'],
                'size'    => (int) $item['drive_size'],
            ]);
        }
    } catch (Throwable $e) {
        // The list is only a hint; a failure here must not stop the portal.
    }
}

/**
 * Take the files that were synchronized off the waiting list.
 */
function templateClearPendingChanges(array $driveFileIds): void {
    try {
        if (!$driveFileIds) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($driveFileIds), '?'));
        DB::query(
            'DELETE FROM `template_drive_pending` WHERE `drive_file_id` IN (' . $placeholders . ')',
            array_values($driveFileIds)
        );
    } catch (Throwable $e) {
        // Nothing to do; the list is rebuilt on the next look.
    }
}

/** When Google Drive was looked at the last time. */
function templateLastDetectAt(): ?int {
    try {
        $row = DB::query(
            'SELECT `last_sync_at` FROM `template_sync_state` WHERE `sync_key` = :k LIMIT 1',
            ['k' => GDRIVE_TEMPLATE_DETECT_KEY]
        )->fetch();
        if (!$row || empty($row['last_sync_at'])) {
            return null;
        }
        $ts = strtotime((string) $row['last_sync_at']);
        return $ts ? (int) $ts : null;
    } catch (Throwable $e) {
        return null;
    }
}

/** Remember when Google Drive was looked at. */
function templateSaveDetectState(?int $timestamp, string $status): void {
    templateSaveSyncStateForKey(GDRIVE_TEMPLATE_DETECT_KEY, $timestamp, $status);
}

/** Create a template row for a file that is not in the system yet. */
function templateCreateDriveRow(array $entry, int $driveTime, int $driveSize, string $track): ?array {
    if ($track === '' || !class_exists('DB')) {
        return null;
    }

    try {
        $pdo = DB::getConnection();
        $trackRow = DB::find('tracks', ['track_code' => $track]);
        if (!$trackRow) {
            return null;
        }

        $documentType = templateDocTypeForFile($entry['name']);

        // Only a form belongs to a stage. A paper format is used at every
        // stage of the track and a reference belongs to the track as a
        // whole, so both are filed without a stage.
        $stageKey = '';
        $stageId  = null;
        if ($documentType === 'form') {
            $stageKey = templateStageKeyForFile($entry['name'], $track);
            if ($stageKey !== '') {
                $stage = DB::query(
                    'SELECT `stage_id` FROM `workflow_stages` WHERE `track_id` = :t AND `stage_key` = :k LIMIT 1',
                    ['t' => (int) $trackRow['track_id'], 'k' => $stageKey]
                )->fetch();
                $stageId = $stage ? (int) $stage['stage_id'] : null;
            }
        }

        // A form without a stage cannot be filed, so it is flagged for the
        // coordinator instead of being put in the wrong place.
        $needsReview = ($documentType === 'form' && $stageId === null);

        $manager = (int) databaseCoordinatorUserId();
        if ($manager <= 0) {
            $manager = 1;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO `templates`
                (`track_id`, `stage_id`, `template_name`, `description`, `document_type`,
                 `file_name`, `file_path`, `mime_type`, `is_active`, `needs_review`, `managed_by_user_id`,
                 `drive_file_id`, `drive_modified_at`, `drive_size`)
             VALUES
                (:track_id, :stage_id, :template_name, :description, :document_type,
                 :file_name, :file_path, :mime_type, 1, :needs_review, :managed_by_user_id,
                 :drive_file_id, :drive_modified_at, :drive_size)'
        );
        $stmt->execute([
            'track_id'          => (int) $trackRow['track_id'],
            'stage_id'          => $stageId,
            'template_name'     => mb_substr(templateDisplayName($entry['name']), 0, 145),
            'description'       => $needsReview
                ? 'Added from the Google Drive template folder. The stage could not be worked out, so it needs to be filed.'
                : 'Added from the Google Drive template folder.',
            'document_type'     => $documentType,
            'file_name'         => mb_substr($entry['name'], 0, 250),
            'file_path'         => templateDriveLocalPath($track, $entry['name']),
            'mime_type'         => null,
            'needs_review'      => $needsReview ? 1 : 0,
            'managed_by_user_id' => $manager,
            'drive_file_id'     => $entry['id'],
            'drive_modified_at' => date('Y-m-d H:i:s', $driveTime),
            'drive_size'        => $driveSize,
        ]);

        $id = (int) $pdo->lastInsertId();
        if ($id <= 0) {
            return null;
        }

        return DB::find('templates', ['template_id' => $id]);
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Record that a file changed.
 *
 * The unique key on (drive_file_id, changed_at) means the same version of
 * a file can never be announced twice, even if two syncs overlap.
 */
function templateRecordNotification(array $row, array $entry, int $changedAt, string $changeType, string $track): bool {
    try {
        $stmt = DB::query(
            'INSERT IGNORE INTO `template_notifications`
                (`template_id`, `drive_file_id`, `file_name`, `change_type`, `changed_at`, `track_code`)
             VALUES (:tid, :dfid, :fname, :ctype, :changed, :track)',
            [
                'tid'     => (int) ($row['template_id'] ?? 0),
                'dfid'    => $entry['id'],
                'fname'   => mb_substr($entry['name'], 0, 250),
                'ctype'   => $changeType,
                'changed' => date('Y-m-d H:i:s', $changedAt),
                'track'   => $track !== '' ? $track : null,
            ]
        );
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Mirror the Drive folder into the portal.
 *
 * @param  bool $force ignore the refresh interval
 * @return array{ok:bool,message:string,added:int,updated:int,checked:int,skipped:bool,downloaded:int}
 */
function syncTemplatesFromDrive(bool $force = false): array {
    $out = [
        'ok' => false, 'message' => '', 'added' => 0, 'updated' => 0,
        'checked' => 0, 'skipped' => false, 'downloaded' => 0,
    ];

    ensureTemplateSyncSchema();

    $lock = @fopen(templateSyncLockPath(), 'c');
    if ($lock === false) {
        $out['skipped'] = true;
        $out['message'] = 'The template sync could not start.';
        return $out;
    }
    if (!flock($lock, LOCK_EX | LOCK_NB)) {
        fclose($lock);
        $out['skipped'] = true;
        $out['message'] = 'A template sync is already running.';
        return $out;
    }

    try {
        if (!$force) {
            $last = templateLastSyncAt();
            if ($last !== null && (time() - $last) < GDRIVE_TEMPLATE_SYNC_INTERVAL) {
                $out['skipped'] = true;
                $out['message'] = 'Google Drive was checked ' . templateTimeAgo($last) . '.';
                return $out;
            }
        }

        $entries = templateDriveEntries();
        if (!$entries) {
            $out['message'] = 'The Google Drive folder could not be read. It may no longer be shared.';
            templateSaveSyncState(null, $out['message']);
            return $out;
        }

        $metas = gdriveFilesMeta(array_column($entries, 'id'));
        $rows  = templateAllRows();

        $linked = [];
        foreach ($rows as $row) {
            $driveId = trim((string) ($row['drive_file_id'] ?? ''));
            if ($driveId !== '') {
                $linked[$driveId] = $row;
            }
        }

        $usedTemplateIds = [];
        $changedAt = time();
        $synchronisedIds = [];

        foreach ($entries as $entry) {
            $meta      = $metas[$entry['id']] ?? null;
            $pageSize  = (int) ($entry['size'] ?? 0);
            $headOk    = $meta && !empty($meta['modified']);
            $driveTime = $headOk ? (int) $meta['modified'] : 0;
            $driveSize = $headOk ? (int) $meta['size'] : $pageSize;

            if (!$headOk && $pageSize <= 0) {
                // Nothing is known about this file, so it cannot be checked.
                continue;
            }
            $out['checked']++;

            $row = $linked[$entry['id']] ?? null;
            if ($row === null) {
                $row = templateMatchRowByFileName($rows, $entry['name'], $usedTemplateIds);
            }

            $track = $entry['track'] !== '' ? $entry['track'] : templateTrackForFolderName((string) ($row['track_code'] ?? ''));
            if ($track === '' && $row) {
                $track = (string) ($row['track_code'] ?? '');
            }

            $changeType = '';

            if ($row === null) {
                // A file the teacher has just added to the folder.
                $newRow = templateCreateDriveRow($entry, $driveTime ?: time(), $driveSize, $track);
                if (!$newRow) {
                    continue;
                }
                $row = $newRow;
                $usedTemplateIds[(int) $row['template_id']] = true;
                $changeType = 'added';
            } else {
                $usedTemplateIds[(int) ($row['template_id'] ?? 0)] = true;
                // The same rule the look for changes uses, so the two can
                // never disagree about what changed.
                $changeType = templateDriveChangeType($row, $driveTime, $driveSize, $headOk, $pageSize) ?? '';
            }

            $localRelative = templateDriveLocalPath($track !== '' ? $track : 'thesis', $entry['name']);
            $localAbsolute = templateAbsolutePath($localRelative);

            $needsDownload = ($changeType !== '');
            if (!$needsDownload) {
                $needsDownload = !is_file($localAbsolute);
            }

            if ($needsDownload && gdriveDownloadFile($entry['id'], $localAbsolute)) {
                $out['downloaded']++;
                // Remove the copy that carried the old name, if nothing else uses it.
                $previous = trim((string) ($row['file_path'] ?? ''));
                if ($previous !== '' && $previous !== $localRelative) {
                    templateRemoveUnusedFile($previous, (int) ($row['template_id'] ?? 0));
                }
            }

            $update = [
                'drive_file_id' => $entry['id'],
                'drive_size'    => $driveSize,
                'file_name'     => mb_substr($entry['name'], 0, 250),
                'file_path'     => $localRelative,
            ];
            if ($headOk) {
                $update['drive_modified_at'] = date('Y-m-d H:i:s', $driveTime);
            }

            // A template that was flagged because its stage could not be
            // worked out is filed now if the name gives the stage away.
            if (!empty($row['needsReview']) && $track !== '' && templateDocTypeForFile($entry['name']) === 'form') {
                $guessedKey = templateStageKeyForFile($entry['name'], $track);
                if ($guessedKey !== '') {
                    $guessedStage = DB::query(
                        'SELECT `stage_id` FROM `workflow_stages` WHERE `track_id` = :t AND `stage_key` = :k LIMIT 1',
                        ['t' => (int) ($row['trackId'] ?? 0), 'k' => $guessedKey]
                    )->fetch();
                    if ($guessedStage) {
                        $update['stage_id']     = (int) $guessedStage['stage_id'];
                        $update['needs_review'] = 0;
                    }
                }
            }

            // A template that was put aside because its file was missing
            // comes back as soon as the file is there.
            if ((int) ($row['is_active'] ?? 1) === 0 && !empty($row['needsReview']) && is_file($localAbsolute)) {
                $update['is_active']    = 1;
                $update['needs_review'] = 0;
            }

            DB::update('templates', $update, ['template_id' => (int) ($row['template_id'] ?? 0)]);
            if ($changeType !== '') {
                $fresh = DB::find('templates', ['template_id' => (int) $row['template_id']]) ?: $row;
                if (templateRecordNotification($fresh, $entry, $driveTime ?: time(), $changeType, $track)) {
                    if ($changeType === 'added') {
                        $out['added']++;
                    } else {
                        $out['updated']++;
                    }
                }
            }

            // The file is in the system now, so it is no longer waiting.
            $synchronisedIds[] = $entry['id'];
        }

        // The files that were taken care of are taken off the waiting list.
        // A file that could not be applied stays on it, so the coordinator
        // is still told about it.
        templateClearPendingChanges($synchronisedIds);

        $out['ok'] = true;
        $out['message'] = 'Checked ' . $out['checked'] . ' file' . ($out['checked'] === 1 ? '' : 's') . ' in Google Drive. '
            . ($out['added'] + $out['updated'] === 0
                ? 'Everything is up to date.'
                : $out['added'] . ' new and ' . $out['updated'] . ' updated file' . (($out['added'] + $out['updated']) === 1 ? '' : 's') . '.');
        templateSaveSyncState($changedAt, $out['message']);

        return $out;
    } catch (Throwable $e) {
        $out['message'] = 'The template sync failed: ' . $e->getMessage();
        templateSaveSyncState(null, $out['message']);
        return $out;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

/** Delete a replaced file when no other template still points at it. */
function templateRemoveUnusedFile(string $relativePath, int $templateId): void {
    try {
        $still = DB::query(
            'SELECT COUNT(*) AS c FROM `templates` WHERE `file_path` = :p AND `template_id` <> :id',
            ['p' => $relativePath, 'id' => $templateId]
        )->fetch();
        if ((int) ($still['c'] ?? 0) > 0) {
            return;
        }
    } catch (Throwable $e) {
        return;
    }

    $absolute = templateAbsolutePath($relativePath);
    if (is_file($absolute) && str_contains(str_replace('\\', '/', $relativePath), 'assets/papers/')) {
        @unlink($absolute);
    }
}

/**
 * True when Google Drive has not been looked at for a while.
 */
function templateSyncIsStale(): bool {
    $last = templateLastDetectAt();
    if ($last === null) {
        return true;
    }
    return (time() - $last) >= GDRIVE_TEMPLATE_DETECT_INTERVAL;
}

/**
 * Look at Google Drive when the portal has not looked at it for a while.
 *
 * This only finds out what changed; it does not download anything. The
 * changes are applied when the coordinator clicks "Sync from Google Drive",
 * so the portal can say that a change is waiting before it happens.
 */
function maybeAutoSyncTemplates(): array {
    ensureTemplateSyncSchema();
    if (!templateSyncIsStale()) {
        return ['ok' => true, 'skipped' => true, 'message' => '', 'added' => 0, 'updated' => 0, 'checked' => 0, 'downloaded' => 0];
    }
    return templateDetectDriveChanges(false);
}

/* ------------------------------------------------------------------
 * Notifications
 * ------------------------------------------------------------------ */

/** The column that holds the "seen" time of a role. */
function templateSeenColumn(string $role): string {
    return $role === 'coordinator' ? 'seen_coordinator_at' : 'seen_student_at';
}

/**
 * How many template updates this user has not looked at yet.
 *
 * A student only counts the updates of their own track; the coordinator
 * counts all of them. A file that was changed more than once counts once, so
 * the number of files is not the number of changes.
 */
function templateNotificationsUnreadCount(string $role, string $trackCode = ''): int {
    try {
        $seen = templateSeenColumn($role);
        $sql = 'SELECT COUNT(DISTINCT COALESCE(`drive_file_id`, `file_name`)) AS c FROM `template_notifications` WHERE `' . $seen . '` IS NULL';
        $params = [];
        if ($role !== 'coordinator' && $trackCode !== '') {
            $sql .= " AND (`track_code` = :t OR `track_code` IS NULL OR `track_code` = '')";
            $params['t'] = $trackCode;
        }
        $row = DB::query($sql, $params)->fetch();
        return (int) ($row['c'] ?? 0);
    } catch (Throwable $e) {
        return 0;
    }
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
function templateNotificationsList(string $role, string $trackCode = '', int $limit = 25, string $mode = 'all'): array {
    try {
        $seen = templateSeenColumn($role);
        $sql = 'SELECT n.*, t.template_name, t.file_path, t.stage_id, t.track_id
                  FROM `template_notifications` n
                  LEFT JOIN `templates` t ON t.template_id = n.template_id
                 WHERE 1 = 1';
        $params = [];
        if ($mode === 'unseen') {
            $sql .= ' AND n.`' . $seen . '` IS NULL';
        } elseif ($mode === 'seen') {
            $sql .= ' AND n.`' . $seen . '` IS NOT NULL';
        }
        if ($role !== 'coordinator' && $trackCode !== '') {
            $sql .= " AND (n.`track_code` = :t OR n.`track_code` IS NULL OR n.`track_code` = '')";
            $params['t'] = $trackCode;
        }
        $sql .= ' ORDER BY n.`changed_at` DESC, n.`notification_id` DESC LIMIT ' . max(1, min(200, (int) $limit));

        $rows = DB::query($sql, $params)->fetchAll();

        /*
         * One file is one entry. The rows come newest first, so the first
         * row of a file is its last change and the older ones are left out.
         */
        $listed = [];
        $seenFiles = [];
        foreach ($rows as $row) {
            $fileKey = trim((string) ($row['drive_file_id'] ?? ''));
            if ($fileKey === '') {
                $fileKey = strtolower(trim((string) ($row['file_name'] ?? '')));
            }
            if ($fileKey !== '' && isset($seenFiles[$fileKey])) {
                continue;
            }
            if ($fileKey !== '') {
                $seenFiles[$fileKey] = true;
            }
            $row['isNew'] = empty($row[$seen]);
            $row['changedLabel'] = date('M j, Y g:i A', strtotime((string) $row['changed_at']));
            $row['ago'] = templateTimeAgo(strtotime((string) $row['changed_at']) ?: null);
            $listed[] = $row;
            if (count($listed) >= max(1, (int) $limit)) {
                break;
            }
        }
        return $listed;
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Take the update entries of the last day off the table.
 *
 * The history is there to show what changed recently, so an entry that is
 * older than a day is removed instead of piling up.
 */
function templatePruneNotifications(int $hours = 24): int {
    try {
        // The clock of the database is used, because it is the clock that
        // wrote the times of the entries.
        return DB::query(
            'DELETE FROM `template_notifications` WHERE `changed_at` < (NOW() - INTERVAL ' . max(1, (int) $hours) . ' HOUR)'
        )->rowCount();
    } catch (Throwable $e) {
        return 0;
    }
}

/** Mark the updates of a role as looked at. */
function markTemplateNotificationsSeen(string $role, string $trackCode = ''): int {
    try {
        $seen = templateSeenColumn($role);
        $sql = 'UPDATE `template_notifications` SET `' . $seen . '` = NOW() WHERE `' . $seen . '` IS NULL';
        $params = [];
        if ($role !== 'coordinator' && $trackCode !== '') {
            $sql .= " AND (`track_code` = :t OR `track_code` IS NULL OR `track_code` = '')";
            $params['t'] = $trackCode;
        }
        return DB::query($sql, $params)->rowCount();
    } catch (Throwable $e) {
        return 0;
    }
}

/** The track of the signed in student. */
function currentStudentTrackCode(): string {
    static $track = null;
    if ($track !== null) {
        return $track;
    }
    $track = '';
    try {
        $userId = (int) ($_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? 0);
        if ($userId > 0 && class_exists('DB')) {
            $row = DB::query('SELECT `track` FROM `students` WHERE `user_id` = :u LIMIT 1', ['u' => $userId])->fetch();
            $track = strtolower(trim((string) ($row['track'] ?? '')));
        }
    } catch (Throwable $e) {
        $track = '';
    }
    return $track;
}
