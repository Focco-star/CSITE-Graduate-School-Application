<?php
/* CSITE configuration module: Application records, latest application lookup, and application updates. */

// Adds a new application to the session store.
function addApplicationRecord(array $a): array {
    // Loads the session applications.
    $list = storeGet('applications');
    // Uses the current time as the new application id.
    $id = time();
    // Finds the track that belongs to the program.
    $track = getTrackForProgram($a['program']);
    // Gets the stage labels in workflow order for that track.
    $stages = getWorkflowStageLabels($track);
    // Uses the given stage key or starts at proposal.
    $stageKey = $a['stageKey'] ?? 'proposal';
    // Starts the record from the default application fields.
    $rec = array_merge(appWorkflowDefaults(), [
        // Sets the application id.
        'id' => $id,
        // Sets the student email that owns the application.
        'studentEmail' => $a['studentEmail'],
        // Sets the student display name.
        'student' => $a['student'],
        // Sets the program code.
        'program' => $a['program'],
        // Sets the track code.
        'track' => $track,
        // Sets the stage label for the stage key.
        'stage' => $stages[$stageKey] ?? $a['stage'] ?? '',
        // Sets the stage key.
        'stageKey' => $stageKey,
        // Sets the paper title.
        'title' => $a['title'],
        // Sets today as the submission date.
        'date' => date('Y-m-d'),
        // Marks the application as submitted.
        'status' => 'submitted',
        // Starts with no coordinator comment.
        'coordinatorComment' => '',
        // Sets the adviser, or an empty string.
        'adviser' => $a['adviser'] ?? '',
        // Sets the abstract, or an empty string.
        'abstract' => $a['abstract'] ?? '',
    // Closes the new application record.
    ]);
    // Adds the record to the applications list.
    $list[] = $rec;
    // Saves the applications list.
    storeSet('applications', $list);
    // Mirrors the new stage and status onto the student row.
    updateStudentStage($a['studentEmail'], $stageKey, 'submitted', $a['title'] ?? null, $a['adviser'] ?? null);
    // Returns the new application record.
    return $rec;
// Closes the addApplicationRecord function.
}

// Finds an application by id, first in the database.
function findApplication(int $id): ?array {
    // Only queries when DB exists and the id is positive.
    if (class_exists('DB') && $id > 0) {
        // Starts the try block for the database lookup.
        try {
            // Prepares the SQL that selects one application with its student details.
            $stmt = DB::getConnection()->prepare(
                // Starts the SQL text that loads applications with student email and formatted name.
                'SELECT a.application_id AS id, u.email AS studentEmail,
                        COALESCE(NULLIF(TRIM(CONCAT(s.last_name, ", ", s.first_name, IF(s.middle_initial IS NULL OR s.middle_initial = "", "", CONCAT(" ", s.middle_initial)))), ""), u.full_name) AS student,
                        CASE WHEN s.program LIKE "%Computer Science%" THEN "MSCS" WHEN s.program LIKE "%Information Technology%" THEN "MIT" WHEN s.program LIKE "%Mathematics%" THEN "MATH" ELSE s.program END AS program,
                        s.track, s.adviser_name AS adviser, a.presentation_stage AS stage,
                        a.paper_title AS title, a.status, a.coordinator_comment AS coordinatorComment,
                        a.result, a.submitted_at AS date
                 FROM applications a
                 INNER JOIN users u ON u.user_id = a.user_id
                 LEFT JOIN students s ON s.user_id = u.user_id
                 WHERE a.application_id = :id AND a.archived_at IS NULL
                 LIMIT 1'
            // Closes the prepared SQL statement text.
            );
            // Runs the query with the application id.
            $stmt->execute(['id' => $id]);
            // Fetches the matching row.
            $row = $stmt->fetch();
            // Checks whether a database row was found.
            if ($row) {
                // Converts the stage label back into a stage key.
                $row['stageKey'] = stageKeyFromLabel((string) $row['stage']);
                // Returns the database application row.
                return $row;
            // Closes the row-found check.
            }
        // Catches query errors so the session fallback still works.
        } catch (Throwable $e) {
        // Closes the catch block.
        }
    // Closes the DB-and-id check.
    }
    // Loops through the session applications.
    foreach (storeGet('applications') as $a) {
        // Compares the stored id with the requested id.
        if ((int) $a['id'] === $id) {
            // Returns the matching session application.
            return $a;
        // Closes the id comparison.
        }
    // Closes the loop over applications.
    }
    // Returns null when the application is not found.
    return null;
// Closes the findApplication function.
}

// Finds the newest application for an email address.
function latestApplicationForEmail(string $email): ?array {
    // Only queries when DB exists and an email is given.
    if (class_exists('DB') && $email !== '') {
        // Starts the try block for the database lookup.
        try {
            // Prepares the SQL that selects the newest application of a student.
            $stmt = DB::getConnection()->prepare(
                // Starts the same application query used to build the coordinator report list.
                'SELECT a.application_id AS id, u.email AS studentEmail,
                        COALESCE(NULLIF(TRIM(CONCAT(s.last_name, ", ", s.first_name, IF(s.middle_initial IS NULL OR s.middle_initial = "", "", CONCAT(" ", s.middle_initial)))), ""), u.full_name) AS student,
                        CASE WHEN s.program LIKE "%Computer Science%" THEN "MSCS" WHEN s.program LIKE "%Information Technology%" THEN "MIT" WHEN s.program LIKE "%Mathematics%" THEN "MATH" ELSE s.program END AS program,
                        s.track, s.adviser_name AS adviser, a.presentation_stage AS stage,
                        a.paper_title AS title, a.status, a.coordinator_comment AS coordinatorComment,
                        a.result, a.submitted_at AS date
                 FROM applications a
                 INNER JOIN users u ON u.user_id = a.user_id
                 LEFT JOIN students s ON s.user_id = u.user_id
                 WHERE u.email = :email AND a.archived_at IS NULL
                 ORDER BY a.submitted_at DESC LIMIT 1'
            // Closes the prepared SQL statement text.
            );
            // Runs the query with the email parameter.
            $stmt->execute(['email' => $email]);
            // Fetches the matching row.
            $row = $stmt->fetch();
            // Checks whether a database row was found.
            if ($row) {
                // Converts the stage label back into a stage key.
                $row['stageKey'] = stageKeyFromLabel((string) $row['stage']);
                // Returns the database application row.
                return $row;
            // Closes the row-found check.
            }
        // Catches query errors so the session fallback still works.
        } catch (Throwable $e) {
        // Closes the catch block.
        }
    // Closes the DB-and-email check.
    }
    // Starts with no fallback application.
    $found = null;
    // Loops through the session applications.
    foreach (storeGet('applications') as $a) {
        // Compares the student emails without caring about case.
        if (strcasecmp((string) $a['studentEmail'], $email) === 0) {
            // Keeps this application as the newest match so far.
            $found = $a;
        // Closes the email comparison.
        }
    // Closes the loop over applications.
    }
    // Returns the newest match, or null.
    return $found;
// Closes the latestApplicationForEmail function.
}

// Updates a student's stage and status, and optionally mirrors the latest application.
function updateStudentStage(string $email, string $stage, string $status, ?string $title = null, ?string $adviser = null, bool $mirrorLatestApp = true): void {
    // Loads the session students.
    $students = storeGet('students');
    // Loops through the students by reference.
    foreach ($students as &$s) {
        // Compares emails without caring about letter case.
        if (strcasecmp($s['email'], $email) === 0) {
            // Stores the new current stage.
            $s['currentStage'] = $stage;
            // Stores the new status.
            $s['status'] = $status;
            // Checks whether a non-empty title was given.
            if ($title !== null && $title !== '') {
                // Updates the paper title.
                $s['title'] = $title;
            // Closes the title check.
            }
            // Checks whether a non-empty adviser was given.
            if ($adviser !== null && $adviser !== '') {
                // Updates the adviser.
                $s['adviser'] = $adviser;
            // Closes the adviser check.
            }
            // Stops the loop once the student is updated.
            break;
        // Closes the email comparison.
        }
    // Closes the loop over students.
    }
    // Breaks the reference after the loop.
    unset($s);
    // Saves the updated students list.
    storeSet('students', $students);

    // Checks whether the latest application should not be mirrored.
    if (!$mirrorLatestApp) {
        // Stops here when mirroring is turned off.
        return;
    // Closes the mirror check.
    }

    // Loads the session applications.
    $apps = storeGet('applications');
    // Starts the latest index at -1, meaning none found.
    $last = -1;
    // Loops through the applications with their indexes.
    foreach ($apps as $i => $a) {
        // Compares the student email without caring about case.
        if (strcasecmp((string) $a['studentEmail'], $email) === 0) {
            // Remembers the index of the newest matching application.
            $last = $i;
        // Closes the email comparison.
        }
    // Closes the loop over applications.
    }
    // Checks whether a matching application was found.
    if ($last >= 0) {
        // Copies the new status onto the application.
        $apps[$last]['status'] = $status;
        // Gets the stage labels for the application's track.
        $stages = getStagesForTrack($apps[$last]['track'] ?? getTrackForProgram($apps[$last]['program']));
        // Checks whether the stage key exists for that track.
        if (isset($stages[$stage])) {
            // Sets the stage key on the application.
            $apps[$last]['stageKey'] = $stage;
            // Sets the readable stage label.
            $apps[$last]['stage'] = $stages[$stage];
        // Closes the stage-key check.
        }
        // Checks whether a title was given.
        if ($title) {
            // Copies the title onto the application.
            $apps[$last]['title'] = $title;
        // Closes the title check.
        }
        // Saves the updated applications list.
        storeSet('applications', $apps);
    // Closes the matching-application check.
    }
// Closes the updateStudentStage function.
}

// Updates an application in the database, or the session store as fallback.
function updateApplicationRecord(int $id, array $patch): ?array {
    // Only uses the database when DB exists and the id is positive.
    if (class_exists('DB') && $id > 0) {
        // Starts the try block for the database update.
        try {
            // Starts the array of columns to update.
            $data = [];
            // Adds the status column when the patch has one.
            if (array_key_exists('status', $patch)) $data['status'] = $patch['status'];
            // Maps the stage key to the presentation_stage column.
            if (array_key_exists('stage', $patch)) $data['presentation_stage'] = $patch['stage'];
            // Maps the coordinator comment to its column.
            if (array_key_exists('coordinatorComment', $patch)) $data['coordinator_comment'] = $patch['coordinatorComment'];
            // Maps the result, storing null when it is empty.
            if (array_key_exists('result', $patch)) $data['result'] = $patch['result'] !== '' ? $patch['result'] : null;
            // Runs the update only when there is something to change.
            if ($data) DB::update('applications', $data, ['application_id' => $id]);
            // Also saves the comment in the application comments table.
            if (!empty($patch['coordinatorComment'])) saveApplicationComment($id, (string) $patch['coordinatorComment']);
            // Returns the application read back from the database.
            return findApplication($id);
        // Catches update errors so the session fallback can run.
        } catch (Throwable $e) {
        // Closes the catch block.
        }
    // Closes the DB-and-id check.
    }
    // Loads the session applications.
    $apps = storeGet('applications');
    // Loops through the applications with their indexes.
    foreach ($apps as $i => $a) {
        // Skips applications whose id is different.
        if ((int) $a['id'] !== $id) {
            // Moves on to the next application.
            continue;
        // Closes the id check.
        }
        // Merges the patch into the matching application.
        $apps[$i] = array_merge($a, $patch);
        // Saves the updated applications list.
        storeSet('applications', $apps);
        // Reads the student email from the updated application.
        $email = $apps[$i]['studentEmail'];
        // Reads the status from the updated application.
        $status = $apps[$i]['status'];
        // Reads the stage key, or defaults to proposal.
        $stage = $apps[$i]['stageKey'] ?? 'proposal';
        // Mirrors the values onto the student without mirroring applications again.
        updateStudentStage($email, $stage, $status, $apps[$i]['title'] ?? null, $apps[$i]['adviser'] ?? null, false);
        // Returns the updated application.
        return $apps[$i];
    // Closes the loop over applications.
    }
    // Returns null when no application matched.
    return null;
// Closes the updateApplicationRecord function.
}
