<?php
/* CSITE configuration module: Template rows, workflow/course grouping, template library, and template CRUD. */

// Loads active templates with their track and stage details.
function databaseTemplateRows(string $trackCode = ''): array {
    // Returns an empty list when the DB class is missing.
    if (!class_exists('DB')) return [];
    // Starts the try block for the template query.
    try {
        // Notes that the Drive columns hold the last-updated text.
        // The Drive columns carry the "last updated" text, so the schema has
        // Notes that the schema must exist before the query runs.
        // to be in place before the query runs.
        // Creates the template sync columns first when that helper exists.
        if (function_exists('ensureTemplateSyncSchema')) {
            // Calls the schema helper.
            ensureTemplateSyncSchema();
        // Closes the helper check.
        }
        // Runs the template query.
        $rows = DB::query(
            // Selects the template id, label, file, and document type.
            'SELECT t.template_id AS id, t.template_name AS label, t.file_name AS file,
                    t.document_type AS docType, t.file_path AS filePath,
                    t.drive_file_id AS driveFileId, t.drive_modified_at AS driveModifiedAt,
                    t.updated_at AS updatedAt, t.needs_review AS needsReview,
                    tr.track_id AS trackId, tr.track_code AS trackCode, tr.track_name AS trackName,
                    t.stage_id AS stageId, ws.stage_key AS stageKey, ws.stage_label AS stage,
                    t.description
             FROM templates t
             INNER JOIN tracks tr ON tr.track_id = t.track_id
             LEFT JOIN workflow_stages ws ON ws.stage_id = t.stage_id
               WHERE t.is_active = 1 AND (:track_code_filter = "" OR tr.track_code = :track_code)
             ORDER BY tr.track_id, ws.stage_order, t.template_name'
           // Passes the track filter values and fetches all rows.
           , ['track_code_filter' => strtolower($trackCode), 'track_code' => strtolower($trackCode)])->fetchAll();
        // Loops through the template rows by reference.
        foreach ($rows as &$row) {
            // Adds the public URL for the template file.
            $row['url'] = templateFileUrl((string) $row['filePath']);
            // Starts the formatted modified date empty.
            $row['modifiedLabel'] = '';
            // Starts the relative modified time empty.
            $row['modifiedAgo']   = '';
            // Reads the Drive modified timestamp.
            $stamp = trim((string) ($row['driveModifiedAt'] ?? ''));
            // Checks whether the Drive timestamp is empty.
            if ($stamp === '') {
                // Falls back to the database updated timestamp.
                $stamp = trim((string) ($row['updatedAt'] ?? ''));
            // Closes the Drive timestamp check.
            }
            // Checks whether a timestamp is available.
            if ($stamp !== '') {
                // Converts the timestamp into Unix time.
                $time = strtotime($stamp);
                // Checks whether the conversion worked.
                if ($time) {
                    // Sets the long modified date.
                    $row['modifiedLabel'] = date('M j, Y', $time);
                    // Sets the short modified date.
                    $row['modifiedShort'] = date('M j', $time);
                    // Sets the relative time, such as 2 days ago.
                    $row['modifiedAgo']   = templateTimeAgo($time);
                // Closes the time-conversion check.
                }
            // Closes the timestamp check.
            }
            // Converts needs_review into a true or false flag.
            $row['needsReview'] = !empty($row['needsReview']);
            // Capitalizes the document type for the label.
            $row['typeLabel'] = ucfirst(strtolower(trim((string) ($row['docType'] ?? 'template'))));
            // Checks whether the label is Form, Template, or Reference.
            if (!in_array($row['typeLabel'], ['Form', 'Template', 'Reference'], true)) {
                // Falls back to the Template label for other values.
                $row['typeLabel'] = 'Template';
            // Closes the label check.
            }
        // Closes the loop over template rows.
        }
        // Breaks the reference after the loop.
        unset($row);
        // Returns the prepared template rows.
        return $rows;
    // Catches query errors so the page still loads.
    } catch (Throwable $e) {
        // Returns an empty list when the query fails.
        return [];
    // Closes the catch block.
    }
// Closes the databaseTemplateRows function.
}

/**
 * The workflow stages of every track, in the order the students work through
 * them.
 *
 * @return array<string, array<int, array{key:string,label:string,order:int}>>
 */
// Builds the ordered workflow stages for every track.
function templateWorkflowStages(): array {
    // Starts with an empty stages array.
    $stages = [];

    // Only queries the database when DB is available.
    if (class_exists('DB')) {
        // Starts the try block for the stage query.
        try {
            // Runs the stage query.
            $rows = DB::query(
                // Selects the track code and the stage key, label, and order.
                'SELECT t.track_code, w.stage_key, w.stage_label, w.stage_order
                   FROM workflow_stages w
                   INNER JOIN tracks t ON t.track_id = w.track_id
                  ORDER BY t.track_id, w.stage_order, w.stage_id'
            // Fetches all stage rows.
            )->fetchAll();
            // Loops through the stage rows.
            foreach ($rows as $row) {
                // Reads the lowercase track code.
                $track = strtolower(trim((string) $row['track_code']));
                // Adds the stage under its track.
                $stages[$track][] = [
                    // Sets the stage key.
                    'key'   => (string) $row['stage_key'],
                    // Sets the stage label.
                    'label' => (string) $row['stage_label'],
                    // Sets the stage order number.
                    'order' => (int) $row['stage_order'],
                // Closes the stage array.
                ];
            // Closes the loop over stage rows.
            }
        // Catches query errors so the fallback below can run.
        } catch (Throwable $e) {
            // Clears the stages so the fallback is used.
            $stages = [];
        // Closes the catch block.
        }
    // Closes the DB check.
    }

    // Notes that the built-in workflow is the fallback.
    // The workflow of the application is the fallback when the tables are
    // Notes the fallback runs when the tables are unavailable.
    // not available.
    // Checks whether no stages were loaded from the database.
    if (!$stages) {
        // Loops through the three track codes.
        foreach (['thesis', 'capstone', 'seminar'] as $track) {
            // Loops through the built-in stages with their indexes.
            foreach (getWorkflow($track)['stages'] as $index => $stage) {
                // Adds the fallback stage under its track.
                $stages[$track][] = [
                    // Sets the stage key, or an empty string.
                    'key'   => (string) ($stage['key'] ?? ''),
                    // Sets the stage label, or an empty string.
                    'label' => (string) ($stage['label'] ?? ''),
                    // Uses the index plus one as the order.
                    'order' => $index + 1,
                // Closes the fallback stage array.
                ];
            // Closes the loop over built-in stages.
            }
        // Closes the loop over tracks.
        }
    // Closes the no-stages check.
    }

    // Returns the stages grouped by track.
    return $stages;
// Closes the templateWorkflowStages function.
}

/**
 * The graduate programs of every track.
 *
 * The programs table holds the courses of the system; the PROGRAMS list in
 * this file holds the same courses with the track they follow. Both are read
 * so that a course is never left out.
 *
 * @return array<string, array<int, array{code:string,name:string}>>
 */
// Builds the list of graduate programs under each track.
function templateCoursesByTrack(): array {
    // Starts with an empty program list for each track.
    $courses = ['thesis' => [], 'capstone' => [], 'seminar' => []];
    // Starts the set of program codes already added.
    $seen    = [];

    // Checks that the DB class is loaded before reading program and track data.
    if (class_exists('DB')) {
        // Starts a guarded block so a database error does not break the page.
        try {
            // Notes that one course can follow more than one track.
            // A course can follow more than one track, so it is listed under
            // Explains that the course is listed under every track it belongs to.
            // every track it belongs to.
            // Runs the query that lists each active program together with its tracks.
            $rows = DB::query(
                // Selects the program code, program name, and track code for each pair.
                'SELECT p.program_code, p.program_name, t.track_code
                   FROM program_tracks pt
                   INNER JOIN programs p ON p.program_id = pt.program_id
                   INNER JOIN tracks t ON t.track_id = pt.track_id
                  WHERE p.is_active = 1
                  ORDER BY p.program_code, pt.is_primary DESC, t.track_id'
            // Fetches every matching program and track row as an array.
            )->fetchAll();
            // Loops over each program and track row returned by MySQL.
            foreach ($rows as $row) {
                // Reads the program code and trims extra spaces.
                $code  = trim((string) $row['program_code']);
                // Reads the track code in lowercase so it matches the track keys.
                $track = strtolower(trim((string) $row['track_code']));
                // Skips rows with no code or a track the page does not display.
                if ($code === '' || !isset($courses[$track])) {
                    // Moves on to the next row without adding this one.
                    continue;
                // Closes the check for an empty code or unknown track.
                }
                // Remembers that this program is already listed under this track.
                $seen[$code][$track] = true;
                // Adds the program to the course list of its track.
                $courses[$track][] = ['code' => $code, 'name' => trim((string) $row['program_name'])];
            // Closes the loop over the database rows.
            }
        // Catches database errors so the page can fall back to defaults.
        } catch (Throwable $e) {
            // Resets the course lists when the database query fails.
            $courses = ['thesis' => [], 'capstone' => [], 'seminar' => []];
        // Closes the database error handler.
        }
    // Closes the block that runs only when the DB class exists.
    }

    // Walks the built-in program list to add any course still missing.
    foreach (PROGRAMS as $code => $label) {
        // Removes a trailing parenthesised note from the program label.
        $name = trim((string) preg_replace('/\s*\([^)]*\)\s*$/', '', $label));
        // Loops over the tracks that this program belongs to.
        foreach (getTracksForProgram($code) as $track) {
            // Skips unknown tracks and programs already added from MySQL.
            if (!isset($courses[$track]) || isset($seen[$code][$track])) {
                // Jumps to the next track for this program.
                continue;
            // Closes the duplicate and unknown-track check.
            }
            // Marks the program as listed under this track.
            $seen[$code][$track] = true;
            // Adds the fallback program entry with its cleaned display name.
            $courses[$track][] = ['code' => $code, 'name' => $name !== '' ? $name : $code];
        // Closes the loop over this program's tracks.
        }
    // Closes the loop over the built-in program list.
    }

    // Returns the course lists grouped by track.
    return $courses;
// Closes the templateCoursesByTrack function.
}

/**
 * The whole template library, grouped the way both portals show it.
 *
 * Every track holds its courses, its workflow stages and its reference
 * documents. A template that belongs to a stage is shown with that stage; a
 * template that belongs to the track itself (the paper format of the track)
 * is shown at every stage, because it is used at every stage.
 *
 * @param  string $trackCode only this track, or every track when empty
 * @return array<int, array<string, mixed>>
 */
// Builds the template library grouped by track, stage, and reference.
function templateLibrary(string $trackCode = ''): array {
    // Loads the template rows from MySQL, optionally for one track.
    $rows      = databaseTemplateRows($trackCode);
    // Loads the workflow stages of each track.
    $stages    = templateWorkflowStages();
    // Loads the courses that each track offers.
    $courses   = templateCoursesByTrack();
    // Starts an empty list of track details.
    $trackMeta = [];

    // Only reads track details when the DB class is available.
    if (class_exists('DB')) {
        // Guards the track query against database errors.
        try {
            // Loops over every track row and records its id, code, and name.
            foreach (DB::query('SELECT track_id, track_code, track_name FROM tracks ORDER BY track_id')->fetchAll() as $track) {
                // Stores each track under its lowercase code for quick lookup.
                $trackMeta[strtolower((string) $track['track_code'])] = [
                    // Saves the numeric track id.
                    'id'   => (int) $track['track_id'],
                    // Saves the lowercase track code.
                    'code' => strtolower((string) $track['track_code']),
                    // Saves the display name of the track.
                    'name' => (string) $track['track_name'],
                // Closes the track details array.
                ];
            // Closes the loop over the tracks table.
            }
        // Catches database errors while loading the tracks.
        } catch (Throwable $e) {
            // Clears the track list when the query fails.
            $trackMeta = [];
        // Closes the track error handler.
        }
    // Closes the block guarded by class_exists.
    }

    // Falls back to the default tracks when no track row was read.
    if (!$trackMeta) {
        // Loops over the three default track codes and names.
        foreach (['thesis' => 'Thesis', 'capstone' => 'Capstone', 'seminar' => 'Seminar Paper'] as $code => $name) {
            // Adds a default track entry with id zero.
            $trackMeta[$code] = ['id' => 0, 'code' => $code, 'name' => $name];
        // Closes the loop over the default tracks.
        }
    // Closes the fallback block for missing tracks.
    }

    // Notes that templates are sorted by track, stage, and track-wide.
    // Sort the templates into their track, their stage and the track itself.
    // Starts the bucket list that groups templates per track.
    $byTrack = [];
    // Loops over every template row to place it in a bucket.
    foreach ($rows as $row) {
        // Reads the track code of the template in lowercase.
        $code = strtolower((string) ($row['trackCode'] ?? ''));
        // Checks whether the template has no track code.
        if ($code === '') {
            // Skips templates without a track because they cannot be grouped.
            continue;
        // Closes the empty track code check.
        }
        // Finds out whether the template is tied to a workflow stage.
        $hasStage  = ($row['stageId'] !== null && (int) $row['stageId'] > 0);
        // Flags a track-wide template that is used at every stage.
        $isTrackWide = (!$hasStage && $row['typeLabel'] === 'Template');
        // Flags a reference document that has no stage.
        $isReference = (!$hasStage && $row['typeLabel'] !== 'Template');

        // Detects a form template that has no stage attached.
        if (!$hasStage && $row['typeLabel'] === 'Form') {
            // Explains that a form without a stage cannot be filed.
            // A form without a stage cannot be filed, so it is flagged for
            // Explains that such a form is flagged for the coordinator.
            // the coordinator instead of being put in the wrong place.
            // Marks the stage-less form so the coordinator sees it.
            $row['needsReview'] = true;
        // Closes the stage-less form check.
        }

        // Chooses the bucket: references, trackWide, or staged.
        $bucket = $isReference ? 'references' : ($isTrackWide ? 'trackWide' : 'staged');
        // Stores the template under its track, bucket, and stage id.
        $byTrack[$code][$bucket][$hasStage ? (int) $row['stageId'] : 0][] = $row;
    // Closes the loop over the template rows.
    }

    // Starts the final library list returned to the caller.
    $library = [];

    // Loops over each track to build its library entry.
    foreach ($trackMeta as $code => $meta) {
        // Skips tracks other than the one that was requested.
        if ($trackCode !== '' && $code !== strtolower($trackCode)) {
            // Moves to the next track when the current one is filtered out.
            continue;
        // Closes the track filter check.
        }

        // Reads the template buckets of this track.
        $group      = $byTrack[$code] ?? [];
        // Reads the templates that apply to the whole track.
        $trackWide  = $group['trackWide'][0] ?? [];
        // Reads the reference documents of this track.
        $references = $group['references'][0] ?? [];
        // Reads the stage-specific templates of this track.
        $staged     = $group['staged'] ?? [];

        // Starts the list of stages for this track.
        $stageList = [];
        // Loops over the workflow stages of this track.
        foreach (($stages[$code] ?? []) as $stage) {
            // Starts the template list for the current stage.
            $items = [];
            // Loops over the staged template buckets keyed by stage id.
            foreach ($staged as $stageId => $bucket) {
                // Loops over each template inside the bucket.
                foreach ($bucket as $item) {
                    // Checks whether the template belongs to this stage.
                    if ((string) ($item['stageKey'] ?? '') === $stage['key']) {
                        // Adds the matching template to the stage list.
                        $items[] = $item;
                    // Closes the stage match check.
                    }
                // Closes the loop over the bucket items.
                }
            // Closes the loop over the stage buckets.
            }
            // Notes that track-wide templates appear at every stage.
            // The templates of the track itself are used at every stage.
            // Adds every track-wide template to this stage.
            foreach ($trackWide as $item) {
                // Appends the track-wide template to the stage items.
                $items[] = $item;
            // Closes the loop over track-wide templates.
            }

            // Starts one stage entry for the library.
            $stageList[] = [
                // Saves the stage key.
                'key'       => $stage['key'],
                // Saves the stage label shown to users.
                'label'     => $stage['label'],
                // Saves the position of the stage in the workflow.
                'order'     => $stage['order'],
                // Saves the templates that belong to the stage.
                'templates' => $items,
            // Closes the stage entry.
            ];
        // Closes the loop over the track's stages.
        }

        // Collects track-wide and reference templates for the review check.
        $allOfTrack = array_merge($trackWide, $references);
        // Loops over the stage buckets of this track.
        foreach ($staged as $bucket) {
            // Adds the stage templates to the same collection.
            $allOfTrack = array_merge($allOfTrack, $bucket);
        // Closes the loop over the stage buckets.
        }

        // Keeps only the templates that need coordinator review.
        $review = array_values(array_filter(
            // Passes the collected templates to the filter.
            $allOfTrack,
            // Selects the items flagged with needsReview.
            static fn($item) => !empty($item['needsReview'])
        // Closes the filter call and reindexes the results.
        ));

        // Notes that templates with missing files are still listed.
        // Templates that are put aside because their file is not there yet
        // Explains that this keeps the gap visible instead of silent.
        // are listed as well, so the gap is not silent.
        // Loops over the templates whose files are missing.
        foreach (templateMissingTemplates($code) as $missing) {
            // Tags the item so the page can explain why it is listed.
            $missing['reason'] = 'missing';
            // Adds the missing-file template to the review list.
            $review[] = $missing;
        // Closes the loop over missing templates.
        }

        // Starts one track entry for the returned library.
        $library[] = [
            // Saves the track code.
            'trackCode'   => $code,
            // Saves the track name.
            'trackName'   => $meta['name'],
            // Saves the numeric track id.
            'trackId'     => $meta['id'],
            // Saves the courses offered under this track.
            'courses'     => $courses[$code] ?? [],
            // Saves the stage list with its templates.
            'stages'      => $stageList,
            // Saves the reference documents of the track.
            'references'  => $references,
            // Saves the templates used at every stage.
            'trackWide'   => $trackWide,
            // Saves the items that need coordinator review.
            'needsReview' => $review,
        // Closes the track library entry.
        ];
    // Closes the loop over the tracks.
    }

    // Returns the grouped template library.
    return $library;
// Closes the templateLibrary function.
}

/**
 * The templates of a track whose file is not available yet.
 *
 * They are kept in the table so nothing is lost, but they are not part of
 * the library until the file is there.
 */
// Returns the templates of a track whose file is still missing.
function templateMissingTemplates(string $trackCode): array {
    // Checks that the DB class is available before querying.
    if (!class_exists('DB')) {
        // Returns an empty list when there is no database.
        return [];
    // Closes the DB availability check.
    }
    // Starts a guarded block for the missing-template query.
    try {
        // Queries the templates table for disabled items waiting for a file.
        $rows = DB::query(
            // Selects the template id, label, file, and review flags.
            'SELECT t.template_id AS id, t.template_name AS label, t.file_name AS file,
                    t.description, t.needs_review AS needsReview,
                    tr.track_id AS trackId, tr.track_code AS trackCode, tr.track_name AS trackName,
                    t.stage_id AS stageId, ws.stage_key AS stageKey, ws.stage_label AS stage
               FROM templates t
               INNER JOIN tracks tr ON tr.track_id = t.track_id
               LEFT JOIN workflow_stages ws ON ws.stage_id = t.stage_id
              WHERE t.is_active = 0 AND t.needs_review = 1 AND tr.track_code = :code
              ORDER BY t.template_name'
            // Passes the lowercase track code as the query parameter.
            , ['code' => strtolower($trackCode)]
        // Fetches all matching template rows.
        )->fetchAll();
        // Loops by reference so each row can be changed in place.
        foreach ($rows as &$row) {
            // Marks each row as needing review.
            $row['needsReview'] = true;
            // Sets the type label used by the library grouping.
            $row['typeLabel']   = 'Template';
        // Closes the loop over the rows.
        }
        // Breaks the reference after the loop to avoid side effects.
        unset($row);
        // Returns the missing templates.
        return $rows;
    // Catches database errors while reading the templates.
    } catch (Throwable $e) {
        // Returns an empty list when the query fails.
        return [];
    // Closes the error handler.
    }
// Closes the templateMissingTemplates function.
}

// Creates a template row and stores its uploaded file.
function createTemplateRecord(array $data, array $file): array {
    // Takes the safe base name of the uploaded file.
    $original = basename((string) ($file['name'] ?? ''));
    // Reads the lowercase extension of the file name.
    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    // Allows only PDF, DOC, and DOCX template files.
    if (!in_array($extension, ['pdf', 'doc', 'docx'], true)) {
        // Stops the upload with a clear message for other file types.
        throw new RuntimeException('Only PDF, DOC, or DOCX templates are allowed.');
    // Closes the file type check.
    }
    // Rejects files over 20 MB or uploads that failed.
    if ((int) ($file['size'] ?? 0) > 20 * 1024 * 1024 || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        // Stops the upload with an invalid file message.
        throw new RuntimeException('The template file is invalid or exceeds 20 MB.');
    // Closes the size and upload error check.
    }
    // Sets the folder where template files are saved.
    $storage = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'templates';
    // Creates the template folder when it does not exist.
    if (!is_dir($storage) && !mkdir($storage, 0750, true) && !is_dir($storage)) {
        // Stops when the storage folder cannot be created.
        throw new RuntimeException('Template storage is not available.');
    // Closes the storage folder check.
    }
    // Makes a random stored file name with the original extension.
    $stored = bin2hex(random_bytes(16)) . '.' . $extension;
    // Moves the uploaded file into the template storage folder.
    if (!move_uploaded_file((string) $file['tmp_name'], $storage . DIRECTORY_SEPARATOR . $stored)) {
        // Stops when the file cannot be saved.
        throw new RuntimeException('The template file could not be saved.');
    // Closes the move upload check.
    }
    // Looks up the track chosen by the coordinator.
    $track = DB::find('tracks', ['track_code' => strtolower((string) $data['program_track'])]);
    // Stops when the chosen track is not configured.
    if (!$track) throw new RuntimeException('The selected track is not configured.');
    // Looks up the stage by track id and stage label.
    $stage = DB::find('workflow_stages', ['track_id' => (int) $track['track_id'], 'stage_label' => $data['stage_label']]);
    // Notes that the coordinator already chose the track and stage.
    // The coordinator picked the track and the stage, so nothing is left to
    // Explains that a stage-less form is flagged instead of filed.
    // review. A form without a stage is flagged instead of being filed.
    // Flags a form only when no matching stage was found.
    $needsReview = (($data['document_type'] ?? '') === 'form' && empty($stage['stage_id']));

    // Opens the shared database connection.
    $pdo = DB::getConnection();
    // Prepares the insert for the new template row.
    $stmt = $pdo->prepare(
        // Inserts the track, stage, name, file, and review flags.
        'INSERT INTO `templates`
            (`track_id`, `stage_id`, `template_name`, `description`, `document_type`,
             `file_name`, `file_path`, `mime_type`, `is_active`, `needs_review`, `managed_by_user_id`)
         VALUES
            (:track_id, :stage_id, :template_name, :description, :document_type,
             :file_name, :file_path, :mime_type, 1, :needs_review, :managed_by_user_id)'
    // Closes the prepared insert statement.
    );
    // Runs the insert with the template values.
    $stmt->execute([
        // Binds the chosen track id.
        'track_id' => (int) $track['track_id'],
        // Binds the stage id, or null when none was found.
        'stage_id' => $stage['stage_id'] ?? null,
        // Binds the trimmed template name.
        'template_name' => trim($data['template_name']),
        // Binds the trimmed description, or null when it is empty.
        'description' => trim((string) ($data['description'] ?? '')) ?: null,
        // Binds the document type chosen by the coordinator.
        'document_type' => $data['document_type'],
        // Binds the original file name.
        'file_name' => $original,
        // Binds the file path inside storage/templates.
        'file_path' => 'storage/templates/' . $stored,
        // Binds the MIME type, or null when it is unknown.
        'mime_type' => (string) ($file['type'] ?? '') ?: null,
        // Binds 1 when the template needs review, otherwise 0.
        'needs_review' => $needsReview ? 1 : 0,
        // Binds the coordinator user id, falling back to 1.
        'managed_by_user_id' => databaseCoordinatorUserId() ?: 1,
    // Closes the value list for the insert.
    ]);

    // Returns the newly inserted template row.
    return DB::find('templates', ['template_id' => (int) $pdo->lastInsertId()]);
// Closes the createTemplateRecord function.
}
// Finds one template row by its numeric id.
function findTemplateRecord(string $id): ?array {
    // Returns null for a missing DB class or a non-numeric id.
    if (!class_exists('DB') || !ctype_digit($id)) return null;
    // Prepares a query that joins the template, its track, and stage.
    $stmt = DB::getConnection()->prepare('SELECT t.*, tr.track_code, ws.stage_label FROM templates t INNER JOIN tracks tr ON tr.track_id = t.track_id LEFT JOIN workflow_stages ws ON ws.stage_id = t.stage_id WHERE t.template_id = :id LIMIT 1');
    // Runs the query with the numeric template id.
    $stmt->execute(['id' => (int) $id]);
    // Returns the template row, or null when nothing matched.
    return $stmt->fetch() ?: null;
// Closes the findTemplateRecord function.
}

// Updates a template, replacing its file when a new one is sent.
function updateTemplateRecord(string $id, array $data, array $file = []): ?array {
    // Loads the existing template row first.
    $template = findTemplateRecord($id);
    // Stops when the template does not exist.
    if (!$template) return null;
    // Starts the fields that can be updated in place.
    $fields = [
        // Sets the new trimmed template name.
        'template_name' => trim($data['template_name']),
        // Sets the description, or null when it is empty.
        'description' => trim((string) ($data['description'] ?? '')) ?: null,
        // Sets the new document type.
        'document_type' => $data['document_type'],
    // Closes the field list.
    ];
    // Checks whether a replacement file was uploaded.
    if (!empty($file['name'])) {
        // Creates a new template row that uses the replacement file.
        $replacement = createTemplateRecord($data + ['program_track' => $template['track_code']], $file);
        // Disables the old template row.
        DB::update('templates', ['is_active' => 0], ['template_id' => (int) $template['template_id']]);
        // Returns the new template instead of the old one.
        return $replacement;
    // Closes the replacement file branch.
    }
    // Saves the edited fields without touching the file.
    DB::update('templates', $fields, ['template_id' => (int) $id]);
    // Returns the freshly updated template row.
    return findTemplateRecord($id);
// Closes the updateTemplateRecord function.
}
