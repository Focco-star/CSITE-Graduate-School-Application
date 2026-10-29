<?php
/* CSITE configuration module: Database lookups for student IDs, applications, documents, uploads, and file conversion. */

/**
 * Resolve the MySQL students.student_id for an email address.
 * Kept for screens that still need the students PK (profile edits); new
 * application/document relations use databaseUserIdForEmail() instead.
 */
// Finds the students.student_id that belongs to an email address.
function databaseStudentIdForEmail(string $email): int {
    // Finds the users.user_id for the email first.
    $userId = databaseUserIdForEmail($email);
    // Checks whether no user matched the email.
    if ($userId <= 0) {
        // Returns zero when the user is unknown.
        return 0;
    // Closes the user id check.
    }
    // Guards the student lookup against database errors.
    try {
        // Prepares the query for the student row of this user.
        $stmt = DB::getConnection()->prepare(
            // Selects the student id linked to the user id.
            'SELECT student_id FROM students WHERE user_id = :user_id LIMIT 1'
        // Closes the prepared statement.
        );
        // Runs the query with the user id.
        $stmt->execute(['user_id' => $userId]);
        // Returns the student id, or 0 when none was found.
        return (int) ($stmt->fetchColumn() ?: 0);
    // Catches database errors in the student lookup.
    } catch (Throwable $e) {
        // Returns zero when the lookup fails.
        return 0;
    // Closes the error handler.
    }
// Closes the databaseStudentIdForEmail function.
}

/**
 * Latest (or stage-matching) application id for a user. Used to link a
 * freshly uploaded document to the student's active application so the
 * coordinator panel can surface it immediately.
 * NOTE: unified key is users.user_id (not students.student_id).
 */
// Finds the application id for a user, optionally for one stage.
function databaseApplicationIdForStudent(int $userId, string $stageLabel = ''): ?int {
    // Returns early when the DB class or the user id is missing.
    if (!class_exists('DB') || $userId <= 0) {
        // Returns null when there is no database or user.
        return null;
    // Closes the availability check.
    }
    // Guards the application lookups against database errors.
    try {
        // Opens the shared database connection.
        $pdo = DB::getConnection();
        // Checks whether a stage label was given.
        if ($stageLabel !== '') {
            // Prepares a query for the application of this stage.
            $stmt = $pdo->prepare(
                // Selects the id and stage of the matching application.
                'SELECT application_id, presentation_stage FROM applications
                 WHERE user_id = :user_id AND archived_at IS NULL AND presentation_stage = :stage
                 ORDER BY submitted_at DESC LIMIT 1'
            // Closes the prepared statement.
            );
            // Runs the query with the user id and stage label.
            $stmt->execute(['user_id' => $userId, 'stage' => $stageLabel]);
            // Reads the first application id returned.
            $exact = $stmt->fetchColumn();
            // Checks whether an exact stage match was found.
            if ($exact) {
                // Returns the application id of the exact stage match.
                return (int) $exact;
            // Closes the exact match check.
            }

            // Prepares a query for all live applications of the user.
            $stageStmt = $pdo->prepare(
                // Selects the id and stage of every live application.
                'SELECT application_id, presentation_stage FROM applications
                 WHERE user_id = :user_id AND archived_at IS NULL
                 ORDER BY submitted_at DESC'
            // Closes the prepared statement.
            );
            // Runs the query with the user id.
            $stageStmt->execute(['user_id' => $userId]);
            // Converts the requested stage label into a stage key.
            $requestedStageKey = stageKeyFromLabel($stageLabel);
            // Loops over the applications of this user.
            foreach ($stageStmt->fetchAll() as $application) {
                // Checks whether the application is for the same stage.
                if (stageKeyFromLabel((string) $application['presentation_stage']) === $requestedStageKey) {
                    // Returns the matching application id.
                    return (int) $application['application_id'];
                // Closes the stage key match check.
                }
            // Closes the loop over the applications.
            }
        // Closes the stage label branch.
        }
        // Prepares the fallback query for the latest application.
        $stmt = $pdo->prepare(
            // Selects the newest application id of the user.
            'SELECT application_id FROM applications
             WHERE user_id = :user_id AND archived_at IS NULL
             ORDER BY submitted_at DESC LIMIT 1'
        // Closes the prepared statement.
        );
        // Runs the fallback query with the user id.
        $stmt->execute(['user_id' => $userId]);
        // Returns the latest application id, or null when none exists.
        return ($id = $stmt->fetchColumn()) ? (int) $id : null;
    // Catches database errors in the application lookup.
    } catch (Throwable $e) {
        // Returns null when the lookup fails.
        return null;
    // Closes the error handler.
    }
// Closes the databaseApplicationIdForStudent function.
}

/**
 * Every non-archived application for a user, newest first.
 * Returns the title/stage the student submitted (spelling included).
 * The student's "Application" page no longer lists a history table —
 * the coordinator reviews submissions from their own side — so this is
 * kept as a lookup helper for records and reporting.
 */
// Lists every non-archived application of a user, newest first.
function databaseApplicationsForUser(int $userId): array {
    // Returns early when the DB class or the user id is missing.
    if (!class_exists('DB') || $userId <= 0) {
        // Returns an empty list when there is no database or user.
        return [];
    // Closes the availability check.
    }
    // Guards the application list query against errors.
    try {
        // Prepares the query for the user's applications.
        $stmt = DB::getConnection()->prepare(
            // Selects the id, stage, title, status, and date.
            'SELECT application_id AS id, presentation_stage AS stage,
                    paper_title AS title, status, submitted_at AS date
             FROM applications
             WHERE user_id = :user_id AND archived_at IS NULL
             ORDER BY submitted_at DESC, application_id DESC'
        // Closes the prepared statement.
        );
        // Runs the query with the user id.
        $stmt->execute(['user_id' => $userId]);
        // Starts the list of application rows.
        $rows = [];
        // Loops over every application row.
        foreach ($stmt->fetchAll() as $row) {
            // Converts the application id to an integer.
            $row['id'] = (int) $row['id'];
            // Adds the stage key derived from the stage label.
            $row['stageKey'] = stageKeyFromLabel((string) ($row['stage'] ?? ''));
            // Adds the row to the result list.
            $rows[] = $row;
        // Closes the loop over the application rows.
        }
        // Returns the application list.
        return $rows;
    // Catches database errors in the application list.
    } catch (Throwable $e) {
        // Returns an empty list when the query fails.
        return [];
    // Closes the error handler.
    }
// Closes the databaseApplicationsForUser function.
}

/**
 * Read student documents straight from MySQL, optionally scoped.
 * This is what makes an upload visible to the coordinator: the student and the
 * coordinator are different PHP sessions, so a session-only store can never be
 * seen across accounts.
 *
 * Unified key: application_documents.user_id -> users.user_id.
 *
 * $applicationId NULL + $userId set  -> every document for that user
 * $applicationId set + $userId set   -> documents linked to the application
 *                                          OR still unlinked (application_id IS NULL)
 */
// Reads student documents from MySQL with optional filtering.
function databaseDocuments(?int $applicationId = null, int $userId = 0, bool $includeUnlinked = true): array {
    // Checks that the DB class is available.
    if (!class_exists('DB')) {
        // Returns an empty list without a database.
        return [];
    // Closes the DB availability check.
    }
    // Guards the document query against database errors.
    try {
        // Opens the shared database connection.
        $pdo = DB::getConnection();
        // Starts the list of SQL conditions.
        $where = [];
        // Starts the list of bound query parameters.
        $params = [];
        // Checks whether an application id was supplied.
        if ($applicationId !== null && $applicationId > 0) {
            // Also includes unlinked documents of the same user.
            if ($userId > 0 && $includeUnlinked) {
                // Filters by the application or the user's unlinked documents.
                $where[] = '(d.application_id = :application_id OR (d.application_id IS NULL AND d.user_id = :user_id))';
                // Binds the application id.
                $params['application_id'] = $applicationId;
                // Binds the user id used for unlinked documents.
                $params['user_id'] = $userId;
            // Handles the case where only the application id is used.
            } else {
                // Filters documents by application id only.
                $where[] = 'd.application_id = :application_id';
                // Binds the application id.
                $params['application_id'] = $applicationId;
            // Closes the application-only branch.
            }
        // Uses only the user id when no application id was given.
        } elseif ($userId > 0) {
            // Filters documents by user id.
            $where[] = 'd.user_id = :user_id';
            // Binds the user id.
            $params['user_id'] = $userId;
        // Handles a call with no application and no user.
        } else {
            // Returns nothing when the scope is unknown.
            return [];
        // Closes the scope checks.
        }
        // Builds the select for document details and the student email.
        $sql = 'SELECT d.document_id, d.application_id, d.user_id, d.stage, d.document_type,
                       d.original_name, d.stored_name, d.mime_type, d.file_size, d.status, d.uploaded_at,
                       u.email AS studentEmail
                FROM application_documents d
                INNER JOIN users u ON u.user_id = d.user_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY d.uploaded_at DESC, d.document_id DESC';
        // Prepares the final document query.
        $stmt = $pdo->prepare($sql);
        // Runs the query with the bound parameters.
        $stmt->execute($params);
        // Converts each database row into the app document shape.
        return array_map(
            // Normalizes one row and passes the student email.
            static fn($row) => normalizeDatabaseDocument($row, (string) ($row['studentEmail'] ?? '')),
            // Passes all fetched document rows to the mapper.
            $stmt->fetchAll()
        // Closes the array_map call.
        );
    // Catches database errors in the document query.
    } catch (Throwable $e) {
        // Returns an empty list when the query fails.
        return [];
    // Closes the error handler.
    }
// Closes the databaseDocuments function.
}

/**
 * Fetch a single document by its MySQL primary key.
 */
// Fetches one application document by its primary key.
function databaseDocumentById(int $documentId): ?array {
    // Returns early for a missing DB or an invalid document id.
    if (!class_exists('DB') || $documentId <= 0) {
        // Returns null when the document cannot be queried.
        return null;
    // Closes the availability check.
    }
    // Guards the single document query against errors.
    try {
        // Prepares the query for one document.
        $stmt = DB::getConnection()->prepare(
            // Selects the document fields and the student email.
            'SELECT d.document_id AS id, d.document_id, d.application_id, d.user_id, d.stage, d.document_type,
                    d.original_name, d.stored_name, d.mime_type, d.file_size, d.status, d.uploaded_at,
                    u.email AS studentEmail
             FROM application_documents d
             LEFT JOIN users u ON u.user_id = d.user_id
             WHERE d.document_id = :document_id
             LIMIT 1'
        // Closes the prepared statement.
        );
        // Runs the query with the document id.
        $stmt->execute(['document_id' => $documentId]);
        // Fetches the matching document row.
        $row = $stmt->fetch();
        // Normalizes the row or returns null when it is not found.
        return $row ? normalizeDatabaseDocument($row, (string) ($row['studentEmail'] ?? '')) : null;
    // Catches database errors in the single document query.
    } catch (Throwable $e) {
        // Returns null when the query fails.
        return null;
    // Closes the error handler.
    }
// Closes the databaseDocumentById function.
}

// Maps a document type label to a short kind for comparisons.
function classifyUploadDocType(string $docType): string {
    // Lowercases the label so matching ignores case.
    $doc = strtolower($docType);
    // Checks whether the type is a payment receipt.
    if (str_contains($doc, 'receipt') || str_contains($doc, 'payment')) {
        // Treats it as a receipt.
        return 'receipt';
    // Closes the receipt check.
    }
    // Checks whether the type is a Graduate School endorsement.
    if (str_contains($doc, 'graduate school')) {
        // Treats it as a Graduate School endorsement.
        return 'grad_school_endorsement';
    // Closes the Graduate School check.
    }
    // Checks whether the type is an adviser endorsement.
    if (str_contains($doc, 'endorsement') || str_contains($doc, 'adviser')) {
        // Treats it as an adviser endorsement.
        return 'adviser_endorsement';
    // Closes the adviser endorsement check.
    }
    // Falls back to the paper kind for every other document.
    return 'paper';
// Closes the classifyUploadDocType function.
}

/**
 * The newest file of a kind.
 *
 * A student may send the same kind of document more than once. The file that
 * was sent last is the one the coordinator reviews, so it is the one that
 * decides the state of a milestone and of a requirement. Every place that
 * looks for the document of a kind uses this one rule, so the list of
 * documents of the coordinator, the milestone tracker and the requirements of
 * the stage can never disagree.
 */
// Finds the newest upload of a given document kind.
function latestUploadOfKind(array $uploads, string $kind): ?array {
    // Starts with no newest upload.
    $latest = null;
    // Starts with an empty newest date.
    $latestDate = '';
    // Starts with no newest upload id.
    $latestId = null;
    // Loops over every upload passed in.
    foreach ($uploads as $upload) {
        // Skips uploads that belong to a different kind.
        if (classifyUploadDocType((string) ($upload['docType'] ?? '')) !== $kind) {
            // Moves to the next upload.
            continue;
        // Closes the kind check.
        }
        // Reads the upload date as text.
        $date = (string) ($upload['date'] ?? '');
        // Reads the upload id as text.
        $id   = (string) ($upload['id'] ?? '');
        // Notes that uploads in the same second are ordered by id.
        // Two files that were sent in the same second are told apart by their
        // Explains that the higher id is treated as the newer file.
        // number, so the one that was added last is the newer one.
        // Starts checking whether this upload is newer.
        $newer = $latest === null
            // Accepts an upload that has a later date.
            || $date > $latestDate
            // Breaks a same-date tie using the higher id.
            || ($date === $latestDate && $latestId !== null && $id !== '' && $id > $latestId);
        // Checks whether this upload beats the current newest.
        if ($newer) {
            // Records this upload as the newest one.
            $latest = $upload;
            // Saves the date of the newest upload.
            $latestDate = $date;
            // Saves the id of the newest upload.
            $latestId = $id !== '' ? $id : $latestId;
        // Closes the newer upload check.
        }
    // Closes the loop over the uploads.
    }
    // Returns the newest upload, or null when none matched.
    return $latest;
// Closes the latestUploadOfKind function.
}

/**
 * The check of an upload against the workflow of the track.
 *
 * The Milestone Tracker decides which document the student may send now, so
 * this check uses the same rule as the Document Type list of the Application
 * page and the Upload buttons of the Dashboard.
 *
 * Returns null when the upload is allowed, otherwise the reason.
 */
// Checks whether the student may send this document now.
function validateUploadSequence(string $email, string $track, string $stageKey, string $docType): ?string {
    // Trims spaces from the chosen document type.
    $docType = trim($docType);
    // Checks whether no document type was chosen.
    if ($docType === '') {
        // Asks the student to choose a document first.
        return 'Choose the document you are sending.';
    // Closes the empty document type check.
    }

    // Loads the workflow stages of the student's track.
    $stages = getWorkflow($track)['stages'] ?? [];
    // Falls back to the stage key as the stage label.
    $stageLabel = $stageKey;
    // Loops over the stages to find the matching one.
    foreach ($stages as $s) {
        // Checks whether this stage matches the requested key.
        if (($s['key'] ?? '') === $stageKey) {
            // Uses the stage label when the key matches.
            $stageLabel = $s['label'] ?? $stageKey;
            // Stops searching once the stage is found.
            break;
        // Closes the stage match check.
        }
    // Closes the loop over the stages.
    }

    // Reads the student's progress for this track.
    $progress = getStudentProgress($email, $track);

    // Notes that a stage the student has not reached stays locked.
    // A stage the student has not reached yet stays locked: the workflow of
    // Explains that the workflow asks for one stage after another.
    // the track asks for one stage after the other.
    // Works out which stages accept uploads right now.
    $stageOpen = openStagesForUploads($progress, $stages);
    // Checks whether this stage is still locked.
    if (isset($stageOpen[$stageKey]) && !$stageOpen[$stageKey]) {
        // Tells the student that the stage is not open yet.
        return 'You cannot send documents for ' . $stageLabel . ' yet. '
            // Points the student to the dashboard Milestone Tracker.
            . 'Follow the Milestone Tracker on your dashboard for the stage you are working on.';
    // Closes the locked stage check.
    }

    // Lists the document types that may be sent at this stage now.
    $open = openDocumentTypes(stageMilestoneSteps($email, $track, $stageKey));
    // Loops over the allowed document types.
    foreach ($open as $type) {
        // Checks whether the chosen type is allowed now.
        if (strcasecmp($type, $docType) === 0) {
            // Returns null to mean the upload is allowed.
            return null;
        // Closes the allowed type check.
        }
    // Closes the loop over the allowed types.
    }

    // Reads the document type that the workflow expects first.
    $expected = $open[0] ?? '';
    // Checks whether another document must be sent first.
    if ($expected !== '') {
        // Tells the student which document comes first.
        return 'Your workflow for ' . $stageLabel . ' asks for the ' . $expected
            // Explains that the coordinator can review it afterwards.
            . ' first. Send that one, then the coordinator can review it.';
    // Closes the expected document check.
    }
    // Explains that every document of the stage was already sent.
    return 'All the documents of ' . $stageLabel . ' are already with the coordinator. '
        // Points the student to what comes next on the tracker.
        . 'Follow the Milestone Tracker on your dashboard for what comes next.';
// Closes the validateUploadSequence function.
}

// Finds the session application row for an email and stage.
function findStageApplicationForEmail(string $email, string $stageKey): ?array {
    // Loops over the applications kept in the session store.
    foreach (storeGet('applications') as $a) {
        // Skips rows that belong to another email.
        if (strcasecmp((string) ($a['studentEmail'] ?? ''), $email) !== 0) {
            // Moves to the next application row.
            continue;
        // Closes the email match check.
        }
        // Checks whether the row is for the requested stage.
        if (($a['stageKey'] ?? stageKeyFromLabel($a['stage'] ?? '')) === $stageKey) {
            // Returns the matching application row.
            return $a;
        // Closes the stage match check.
        }
    // Closes the loop over the applications.
    }
    // Returns null when no matching application exists.
    return null;
// Closes the findStageApplicationForEmail function.
}

// Makes sure an application row exists for the email and stage.
function ensureStageApplication(string $email, string $stageKey, string $stageLabel): ?array {
    // Normalizes the email to lowercase without spaces.
    $email = strtolower(trim($email));
    // Checks whether the email is empty.
    if ($email === '') {
        // Returns null when there is no email to use.
        return null;
    // Closes the empty email check.
    }
    // Looks for an existing row for this email and stage.
    $existing = findStageApplicationForEmail($email, $stageKey);
    // Reads the track from the existing row, if there is one.
    $track = $existing['track'] ?? null;
    // Checks whether the track is still unknown.
    if ($track === null) {
        // Looks up the student profile for this email.
        $student = findStudentByEmail($email);
        // Uses the student's track or derives it from the program.
        $track = $student['track'] ?? getTrackForProgram($student['program'] ?? 'MSCS');
    // Closes the track lookup branch.
    }
    // Checks whether no track could be found.
    if ($track === null || $track === '') {
        // Falls back to the thesis track.
        $track = 'thesis';
    // Closes the track fallback check.
    }
    // Reads the stage labels of the chosen track.
    $stages = getWorkflowStageLabels($track);
    // Uses the standard stage label when one exists.
    $canonLabel = $stages[$stageKey] ?? $stageLabel;

    // Checks whether an application row already exists.
    if ($existing) {
        // Checks whether the row is still unsubmitted.
        if (in_array($existing['status'] ?? '', ['not_started', 'pending', 'draft', ''], true)) {
            // Marks the existing row as submitted.
            updateApplicationRecord((int) $existing['id'], ['status' => 'submitted']);
            // Updates the in-memory status to match the store.
            $existing['status'] = 'submitted';
        // Closes the status update check.
        }
    // Handles the case where no application row exists.
    } else {
        // Looks up the student profile again for the new row.
        $student = findStudentByEmail($email);
        // Creates a new application row for this stage.
        $existing = addApplicationRecord([
            // Saves the student email on the new row.
            'studentEmail' => $email,
            // Saves the display name, or the email when it is unknown.
            'student' => $student ? studentDisplayName($student) : $email,
            // Saves the program, defaulting to MSCS.
            'program' => $student['program'] ?? 'MSCS',
            // Saves the standard stage label.
            'stage' => $canonLabel,
            // Saves the stage key.
            'stageKey' => $stageKey,
            // Starts with an empty paper title.
            'title' => '',
            // Saves the adviser name when the profile has one.
            'adviser' => $student['adviser'] ?? '',
        // Closes the new application row values.
        ]);
    // Closes the create-row branch.
    }

    // Also mirrors the row into MySQL when the DB class exists.
    if (class_exists('DB')) {
        // Guards the MySQL mirroring against errors.
        try {
            // Finds the users.user_id for this email.
            $userId = databaseUserIdForEmail($email);
            // Checks whether the email maps to a user.
            if ($userId > 0) {
                // Opens the shared database connection.
                $pdo = DB::getConnection();
                // Prepares a query for the application of this stage.
                $stmt = $pdo->prepare(
                    // Selects the id, status, and title of the live application.
                    'SELECT application_id, status, paper_title FROM applications
                     WHERE user_id = :user_id AND archived_at IS NULL AND presentation_stage = :stage
                     ORDER BY submitted_at DESC LIMIT 1'
                // Closes the prepared statement.
                );
                // Runs the query with the user id and stage label.
                $stmt->execute(['user_id' => $userId, 'stage' => $canonLabel]);
                // Fetches the matching application row.
                $row = $stmt->fetch();
                // Checks whether MySQL already has this application.
                if ($row) {
                    // Checks whether the database row is still unsubmitted.
                    if (in_array($row['status'] ?? '', ['not_started', 'pending', 'draft', ''], true)) {
                        // Prepares an update that marks the row as submitted.
                        $upd = $pdo->prepare('UPDATE applications SET status = :status WHERE application_id = :id');
                        // Runs the status update for this application.
                        $upd->execute(['status' => 'submitted', 'id' => (int) $row['application_id']]);
                    // Closes the database status check.
                    }
                // Handles the case where MySQL has no application row.
                } else {
                    // Starts with an empty paper title.
                    $title = '';
                    // Reads the student's latest application for its title.
                    $latest = latestApplicationForEmail($email);
                    // Checks whether the latest application has a title.
                    if (!empty($latest['title'])) {
                        // Reuses the previous paper title.
                        $title = (string) $latest['title'];
                    // Closes the title check.
                    }
                    // Prepares an insert for a new application row.
                    $ins = $pdo->prepare(
                        // Inserts the user, stage, paper title, and status.
                        'INSERT INTO applications (user_id, presentation_stage, paper_title, status)
                         VALUES (:user_id, :presentation_stage, :paper_title, :status)'
                    // Closes the prepared insert.
                    );
                    // Runs the insert with the application values.
                    $ins->execute([
                        // Binds the user id.
                        'user_id' => $userId,
                        // Binds the standard stage label.
                        'presentation_stage' => $canonLabel,
                        // Binds the title, or Untitled paper when it is empty.
                        'paper_title' => $title !== '' ? $title : 'Untitled paper',
                        // Binds the submitted status.
                        'status' => 'submitted',
                    // Closes the insert values.
                    ]);
                // Closes the new database row branch.
                }
            // Closes the user id check.
            }
        // Catches database errors while mirroring the row.
        } catch (Throwable $e) {
        // Closes the empty catch block.
        }
    // Closes the DB availability block.
    }
    // Returns the stored row, or the in-memory row as a fallback.
    return findStageApplicationForEmail($email, $stageKey) ?? $existing;
// Closes the ensureStageApplication function.
}

/**
 * The single write point for a student document.
 *
 * Records the upload in BOTH stores: the session prototype (kept for the
 * legacy prototype screens) and MySQL `application_documents` (the shared
 * source of truth the coordinator panel reads). Writing only to the session
 * was the reason a coordinator could never see a student's upload.
 */
/**
 * The document a new upload replaces.
 *
 * A student who sends a document again replaces the record of the document
 * they sent before, so the coordinator always reviews the newest file and no
 * duplicate records pile up. A "Revision" replaces the document the
 * coordinator sent back to the student.
 */
// Finds the document record that a new upload should replace.
function findReplaceableDocument(int $applicationId, string $stageLabel, string $docType): ?array {
    // Checks whether the application id is valid.
    if ($applicationId <= 0) {
        // Returns null when there is no application to search.
        return null;
    // Closes the application id check.
    }

    // Starts the list of documents from the same stage.
    $sameStage = [];
    // Loops over the documents of the application.
    foreach (uploadsForApplication($applicationId) as $upload) {
        // Reads the stage label of the document.
        $stage = (string) ($upload['stage'] ?? '');
        // Compares the stage label while ignoring case.
        $same  = strcasecmp($stage, $stageLabel) === 0
            // Also compares the stage keys for a looser match.
            || stageKeyFromLabel($stage) === stageKeyFromLabel($stageLabel);
        // Checks whether the document is from the same stage.
        if ($same) {
            // Adds the document to the same-stage list.
            $sameStage[] = $upload;
        // Closes the same-stage check.
        }
    // Closes the loop over the documents.
    }
    // Checks whether the stage has no documents yet.
    if (!$sameStage) {
        // Returns null when there is nothing to replace.
        return null;
    // Closes the empty stage list check.
    }

    // Notes that a Revision replaces the document sent back by the coordinator.
    // A revision replaces the document that was sent back to the student.
    // Checks whether the new document is a revision.
    if (strcasecmp(trim($docType), 'Revision') === 0) {
        // Loops over the documents of the same stage.
        foreach ($sameStage as $upload) {
            // Looks for a document sent back for revision.
            if (in_array((string) ($upload['status'] ?? ''), ['incomplete', 'revision'], true)) {
                // Returns that document so the revision replaces it.
                return $upload;
            // Closes the returned document check.
            }
        // Closes the loop over same-stage documents.
        }
    // Closes the revision branch.
    }

    // Notes that other documents replace the document of the same kind.
    // Otherwise the document of the same kind is replaced.
    // Classifies the new document type.
    $kind = classifyUploadDocType($docType);
    // Loops over the same-stage documents again.
    foreach ($sameStage as $upload) {
        // Checks whether the document is of the same kind.
        if (classifyUploadDocType((string) ($upload['docType'] ?? '')) === $kind) {
            // Returns that document so it can be replaced.
            return $upload;
        // Closes the same-kind check.
        }
    // Closes the loop over same-stage documents.
    }

    // Returns null when no document should be replaced.
    return null;
// Closes the findReplaceableDocument function.
}

/**
 * Remove a stored file when no document uses it any more.
 */
// Deletes a stored file that no document uses any more.
function removeUnreferencedUploadFile(string $storedName): void {
    // Keeps only the safe file name of the stored file.
    $stored = basename(trim($storedName));
    // Checks for an empty name or a missing DB class.
    if ($stored === '' || !class_exists('DB')) {
        // Stops when the file name or the database is unusable.
        return;
    // Closes the input check.
    }
    // Guards the reference count query against errors.
    try {
        // Counts how many documents still point to this file.
        $used = (int) DB::query(
            // Counts rows in application_documents by stored name.
            'SELECT COUNT(*) c FROM application_documents WHERE stored_name = :name',
            // Passes the stored file name as the parameter.
            ['name' => $stored]
        // Reads the count value from the result row.
        )->fetch()['c'];
        // Checks whether any document still uses the file.
        if ($used > 0) {
            // Keeps the file when it is still referenced.
            return;
        // Closes the reference count check.
        }
    // Catches database errors during the count.
    } catch (Throwable $e) {
        // Does nothing when the count cannot be read.
        return;
    // Closes the error handler.
    }

    // Resolves the full path of the stored file.
    $path = uploadStoragePath(['storedFile' => $stored]);
    // Checks whether the file really exists on disk.
    if ($path && is_file($path)) {
        // Deletes the unused file from storage.
        @unlink($path);
    // Closes the file existence check.
    }
// Closes the removeUnreferencedUploadFile function.
}

// Saves a student document in both the session store and MySQL.
function recordStudentDocument(array $meta, ?string $email = null, ?int $userId = null): ?int {
    // Normalizes the email from the argument or the metadata.
    $email = strtolower((string) ($email ?? ($meta['studentEmail'] ?? '')));
    // Checks whether an email is available.
    if ($email === '') {
        // Returns null when the document has no owner.
        return null;
    // Closes the empty email check.
    }

    // Reads the stage label from the document metadata.
    $stageLabel = (string) ($meta['stage'] ?? '');
    // Converts the stage label into a stage key.
    $stageKey = stageKeyFromLabel($stageLabel);
    // Ensures an application row exists for this stage.
    $stageApp = ensureStageApplication($email, $stageKey, $stageLabel);
    // Checks whether an application row was found or created.
    if ($stageApp) {
        // Saves the application id in the metadata.
        $meta['applicationId'] = $stageApp['id'];
    // Closes the application row check.
    }

    // Starts with no MySQL document id.
    $documentId = null;
    // Writes to MySQL only when the DB class is available.
    if (class_exists('DB')) {
        // Guards the MySQL document write against errors.
        try {
            // Uses the given user id or looks it up from the email.
            $resolvedUserId = $userId !== null && $userId > 0 ? (int) $userId : databaseUserIdForEmail($email);
            // Checks whether a user id was resolved.
            if ($resolvedUserId > 0) {
                // Finds the application id for this user and stage.
                $applicationId = databaseApplicationIdForStudent($resolvedUserId, $stageLabel);
                // Falls back to the application id stored in the session row.
                if ($applicationId === null && !empty($meta['applicationId'])) {
                    // Reads the candidate application id.
                    $candidate = (int) $meta['applicationId'];
                    // Checks whether the candidate id is valid.
                    if ($candidate > 0) {
                        // Prepares a check that the application exists in MySQL.
                        $existsStmt = DB::getConnection()->prepare(
                            // Selects 1 when the application row exists.
                            'SELECT 1 FROM applications WHERE application_id = :id LIMIT 1'
                        // Closes the prepared statement.
                        );
                        // Runs the existence check with the candidate id.
                        $existsStmt->execute(['id' => $candidate]);
                        // Checks whether the application row was found.
                        if ($existsStmt->fetchColumn()) {
                            // Uses the confirmed application id.
                            $applicationId = $candidate;
                        // Closes the found application check.
                        }
                    // Closes the candidate id check.
                    }
                // Closes the fallback application id block.
                }
                // Reads the document status, defaulting to submitted.
                $status = (string) ($meta['status'] ?? 'submitted');
                // Checks whether the status is one of the allowed values.
                if (!in_array($status, ['submitted', 'verified', 'incomplete', 'revision'], true)) {
                    // Falls back to submitted for an unknown status.
                    $status = 'submitted';
                // Closes the status check.
                }

                // Explains why a resent document replaces the earlier record.
                /*
                 * A document that is sent again replaces the record of the
                 * document that was there before: the newest file is the one
                 * that is reviewed, and the student never ends up with two
                 * records of the same document.
                 */
                // Looks for a document record that can be replaced.
                $replaceable = findReplaceableDocument(
                    // Passes the application id.
                    (int) $applicationId,
                    // Passes the stage label.
                    $stageLabel,
                    // Passes the document type.
                    (string) ($meta['docType'] ?? '')
                // Closes the findReplaceableDocument call.
                );
                // Reads the id of the replaceable document, or 0.
                $replaceableId = (int) ($replaceable['id'] ?? 0);

                // Checks whether an earlier document was found.
                if ($replaceableId > 0) {
                    // Reads the stored name of the file being replaced.
                    $previousFile = (string) ($replaceable['storedFile'] ?? $replaceable['stored_name'] ?? '');
                    // Prepares the update that reuses the existing document row.
                    $stmt = DB::getConnection()->prepare(
                        // Updates the document row with the new file details.
                        'UPDATE application_documents
                            SET original_name = :original_name,
                                stored_name   = :stored_name,
                                mime_type     = :mime_type,
                                file_size     = :file_size,
                                status        = :status,
                                uploaded_at   = CURRENT_TIMESTAMP
                          WHERE document_id = :document_id'
                    // Closes the prepared update.
                    );
                    // Runs the update with the new file values.
                    $stmt->execute([
                        // Binds the new original file name.
                        'original_name' => (string) ($meta['fileName'] ?? ''),
                        // Binds the new stored file name.
                        'stored_name'   => (string) ($meta['storedFile'] ?? ''),
                        // Binds the new MIME type.
                        'mime_type'     => (string) ($meta['mimeType'] ?? ''),
                        // Binds the new file size.
                        'file_size'     => (int) ($meta['size'] ?? 0),
                        // Sets the status back to submitted.
                        'status'        => 'submitted',
                        // Binds the document row being replaced.
                        'document_id'   => $replaceableId,
                    // Closes the update values.
                    ]);
                    // Keeps the same document id for the new file.
                    $documentId = $replaceableId;
                    // Notes that the replaced file is no longer used.
                    // The file that was replaced is not used any more.
                    // Checks whether a different old file should be removed.
                    if ($previousFile !== '' && $previousFile !== (string) ($meta['storedFile'] ?? '')) {
                        // Deletes the old file when nothing else uses it.
                        removeUnreferencedUploadFile($previousFile);
                    // Closes the old file cleanup check.
                    }
                // Handles the case where a new document row is needed.
                } else {
                    // Prepares the insert for a new document row.
                    $stmt = DB::getConnection()->prepare(
                        // Inserts the new document into application_documents.
                        'INSERT INTO application_documents
                            (application_id, user_id, stage, document_type, original_name, stored_name, mime_type, file_size, status)
                          VALUES
                            (:application_id, :user_id, :stage, :document_type, :original_name, :stored_name, :mime_type, :file_size, :status)'
                    // Closes the prepared insert.
                    );
                    // Runs the insert with the document values.
                    $stmt->execute([
                        // Binds the application id.
                        'application_id' => $applicationId,
                        // Binds the user id.
                        'user_id' => $resolvedUserId,
                        // Binds the stage label.
                        'stage' => $stageLabel,
                        // Binds the document type, defaulting to Document.
                        'document_type' => (string) ($meta['docType'] ?? 'Document'),
                        // Binds the original file name.
                        'original_name' => (string) ($meta['fileName'] ?? ''),
                        // Binds the stored file name.
                        'stored_name' => (string) ($meta['storedFile'] ?? ''),
                        // Binds the MIME type.
                        'mime_type' => (string) ($meta['mimeType'] ?? ''),
                        // Binds the file size.
                        'file_size' => (int) ($meta['size'] ?? 0),
                        // Binds the document status.
                        'status' => $status,
                    // Closes the insert values.
                    ]);
                    // Saves the new document id from the insert.
                    $documentId = (int) DB::getConnection()->lastInsertId();
                // Closes the insert branch.
                }
            // Closes the resolved user id check.
            }
        // Catches database errors during the document write.
        } catch (Throwable $e) {
            // Logs the failure with the email and the error message.
            error_log('CSITE: failed to record application document for ' . $email . ' — ' . $e->getMessage());
            // Clears the document id because the write failed.
            $documentId = null;
        // Closes the error handler.
        }
    // Closes the DB availability block.
    }

    // Also saves the upload in the session store.
    addUploadRecord(array_merge($meta, [
        // Saves the student email with the upload.
        'studentEmail' => $email,
        // Saves the application id when one is known.
        'applicationId' => $meta['applicationId'] ?? null,
        // Uses the MySQL id, or null when the write failed.
        'id' => $documentId ? (string) $documentId : null,
        // Notes that a student upload always waits for review.
        // A document the student sends is always waiting for the review.
        // Marks the upload as submitted for review.
        'status' => 'submitted',
    // Closes the upload record values.
    ]));

    // Returns the new document id, or null on failure.
    return $documentId;
// Closes the recordStudentDocument function.
}

// Finds an upload by id in MySQL first, then the session store.
function findUpload(string $id): ?array {
    // Checks whether the id looks like a MySQL number.
    if (ctype_digit($id)) {
        // Looks the document up in MySQL.
        $databaseUpload = databaseDocumentById((int) $id);
        // Checks whether MySQL returned the document.
        if ($databaseUpload) {
            // Returns the database document when it was found.
            return $databaseUpload;
        // Closes the database result check.
        }
    // Closes the numeric id check.
    }

    // Loops over the uploads kept in the session store.
    foreach (storeGet('uploads') as $upload) {
        // Checks whether the upload id matches.
        if ((string) ($upload['id'] ?? '') === $id) {
            // Returns the matching session upload.
            return $upload;
        // Closes the id match check.
        }
    // Closes the loop over the session uploads.
    }

    // Returns null when no upload matches the id.
    return null;
// Closes the findUpload function.
}

// Finds the real file path of an uploaded document.
function uploadStoragePath(array $upload): ?string {
    // Reads the safe stored file name of the upload.
    $stored = basename((string) ($upload['storedFile'] ?? $upload['stored_name'] ?? ''));
    // Returns null when the stored name is empty.
    if ($stored === '') return null;

    // Builds the base storage folder path.
    $baseDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR;

    // Notes that files directly inside storage are checked first.
    // Check directly inside /storage/
    // Builds the path directly inside the storage folder.
    $path = $baseDir . $stored;
    // Returns the path when the file exists there.
    if (is_file($path)) return $path;

    // Notes that storage/uploads is checked when the first path fails.
    // Check inside /storage/uploads/
    // Builds the path inside the storage/uploads folder.
    $pathUploads = $baseDir . 'uploads' . DIRECTORY_SEPARATOR . $stored;
    // Returns the path when the file exists there.
    if (is_file($pathUploads)) return $pathUploads;

    // Returns null when the file is not found.
    return null;
// Closes the uploadStoragePath function.
}

// Starts updateUploadStatus(), which changes the status of one upload.
function updateUploadStatus(string $id, string $status): void {
    // Runs the database update only for numeric ids when the DB layer exists.
    if (ctype_digit($id) && class_exists('DB')) {
        // Starts the guarded block around the status update query.
        try {
            // Prepares the UPDATE statement for the application_documents table.
            $stmt = DB::getConnection()->prepare(
                // Sets the new status for the document matching the given document_id.
                'UPDATE application_documents SET status = :status WHERE document_id = :document_id'
            // Closes the prepared SQL statement text.
            );
            // Executes the update with the status and the numeric document id.
            $stmt->execute(['status' => $status, 'document_id' => (int) $id]);
        // Catches database errors so the session copy is still updated.
        } catch (Throwable $e) {
        // Closes the empty catch block for the database update.
        }
    // Closes the database-only branch.
    }
    // Loads the session uploads list to update the matching record too.
    $list = storeGet('uploads');
    // Loops by reference so the matching upload can be changed in place.
    foreach ($list as &$u) {
        // Skips uploads whose id does not match the requested document.
        if ((string) $u['id'] !== $id) {
            // Continues to the next upload in the list.
            continue;
        // Closes the upload id comparison.
        }
        // Sets the new status on the matching session upload.
        $u['status'] = $status;
        // Stops looping once the matching upload is updated.
        break;
    // Closes the loop over the session uploads.
    }
    // Breaks the reference so the loop variable is safe to reuse.
    unset($u);
    // Saves the updated uploads list back to the session store.
    storeSet('uploads', $list);
// Closes updateUploadStatus().
}

// Starts syncUploadStatusesFromWorkflow(), copying workflow statuses to uploads.
function syncUploadStatusesFromWorkflow(int $appId, array $workflowState): void {
    // Loads the session uploads list for the given application.
    $list = storeGet('uploads');
    // Loops by reference over each upload record.
    foreach ($list as &$u) {
        // Skips uploads that belong to a different application.
        if ((int) ($u['applicationId'] ?? 0) !== $appId) {
            // Continues to the next upload record.
            continue;
        // Closes the application id check.
        }
        // Maps the upload document type to its workflow state key.
        $key = classifyUploadDocType((string) $u['docType']);
        // Copies the workflow status only when it is a known review status.
        if (!empty($workflowState[$key]) && in_array($workflowState[$key], ['verified', 'incomplete', 'revision', 'submitted'], true)) {
            // Stores the workflow review status on the upload record.
            $u['status'] = $workflowState[$key];
        // Closes the status copy condition.
        }
    // Closes the loop over the session uploads.
    }
    // Breaks the reference after the loop.
    unset($u);
    // Saves the updated uploads list back to the session store.
    storeSet('uploads', $list);
// Closes syncUploadStatusesFromWorkflow().
}

// Starts uploadsForEmail(), which merges session and database uploads.
function uploadsForEmail(string $email): array {
    // Keeps only session uploads whose student email matches, ignoring case.
    $sessionUploads = array_values(array_filter(storeGet('uploads'), static function ($u) use ($email) {
        // Compares the upload's student email with the requested email.
        return strcasecmp((string) $u['studentEmail'], $email) === 0;
    // Closes the filter callback for session uploads.
    }));
    // Loads the database documents for the user id that owns this email.
    $dbUploads = databaseDocuments(null, databaseUserIdForEmail($email));
    // Prepares the merged result list.
    $merged = [];
    // Prepares a set of keys already added so duplicates are skipped.
    $seen = [];
    // Adds every session upload to the merged list first.
    foreach ($sessionUploads as $u) {
        // Records the id and stored file pair so duplicates can be detected.
        $seen[(string) ($u['id'] ?? '') . '|' . (string) ($u['storedFile'] ?? '')] = true;
        // Appends the session upload to the merged list.
        $merged[] = $u;
    // Closes the loop over session uploads.
    }
    // Loops over the database uploads to add the missing ones.
    foreach ($dbUploads as $u) {
        // Builds the same id and stored file key for the database upload.
        $key = (string) ($u['id'] ?? '') . '|' . (string) ($u['storedFile'] ?? '');
        // Skips the database upload when the session already had it.
        if (isset($seen[$key])) {
            // Continues to the next database upload.
            continue;
        // Closes the duplicate check.
        }
        // Marks the key as seen so it is not added twice.
        $seen[$key] = true;
        // Appends the database upload to the merged list.
        $merged[] = $u;
    // Closes the loop over database uploads.
    }
    // Returns the combined list of session and database uploads.
    return $merged;
// Closes uploadsForEmail().
}

// Starts uploadsForApplication(), which merges uploads for one application.
function uploadsForApplication(int $id): array {
    // Sets the owner user id to zero until the database lookup runs.
    $userId = 0;
    // Looks up the owner only when DB exists and the application id is valid.
    if (class_exists('DB') && $id > 0) {
        // Starts the guarded block for the owner lookup.
        try {
            // Prepares a query for the user_id that owns this application.
            $stmt = DB::getConnection()->prepare('SELECT user_id FROM applications WHERE application_id = :id LIMIT 1');
            // Runs the query with the application id parameter.
            $stmt->execute(['id' => $id]);
            // Stores the owner user id, or 0 when no row was found.
            $userId = (int) ($stmt->fetchColumn() ?: 0);
        // Catches database errors so the page can still show uploads.
        } catch (Throwable $e) {
            // Falls back to user id 0 when the lookup fails.
            $userId = 0;
        // Closes the catch block for the owner lookup.
        }
    // Closes the owner lookup condition.
    }
    // Keeps only session uploads that belong to this application id.
    $sessionUploads = array_values(array_filter(storeGet('uploads'), static function ($u) use ($id) {
        // Compares the upload's application id with the requested one.
        return (int) ($u['applicationId'] ?? 0) === $id;
    // Closes the filter callback for session uploads.
    }));
    // Loads database documents for this application and its owner.
    $dbUploads = databaseDocuments($id, $userId, $userId > 0);
    // Prepares the merged result list.
    $merged = [];
    // Prepares the set of stored files already merged.
    $seen = [];
    // Adds each session upload to the merged list first.
    foreach ($sessionUploads as $u) {
        // Records its stored file name so duplicates are skipped later.
        $seen[(string) ($u['storedFile'] ?? '')] = true;
        // Appends the session upload to the merged list.
        $merged[] = $u;
    // Closes the loop over session uploads.
    }
    // Loops over the database uploads to add the missing ones.
    foreach ($dbUploads as $u) {
        // Reads the stored file name used as the duplicate key.
        $key = (string) ($u['storedFile'] ?? '');
        // Skips the database upload when its file is already merged.
        if ($key !== '' && isset($seen[$key])) {
            // Continues to the next database upload.
            continue;
        // Closes the duplicate file check.
        }
        // Appends the database upload to the merged list.
        $merged[] = $u;
    // Closes the loop over database uploads.
    }
    // Returns the combined upload list for the application.
    return $merged;
// Closes uploadsForApplication().
}
