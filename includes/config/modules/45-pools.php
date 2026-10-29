<?php
/* CSITE configuration module: Adviser, panel-member, and documenter pool helpers and deletion actions. */

// Starts deleteScheduleRecord(), deleting a schedule from the DB and the store.
function deleteScheduleRecord(string $id): void {
    // Runs the database branch for numeric ids or db_ prefixed ids.
    if (class_exists('DB') && (ctype_digit($id) || preg_match('/^db_(\d+)$/', $id, $matches))) {
        // Gets the shared PDO connection for the delete transaction.
        $pdo = DB::getConnection();
        // Reads the application id from a db_ style id, or 0 for numeric ids.
        $applicationId = ctype_digit($id) ? 0 : (int) $matches[1];
        // Begins a transaction so the schedule and application change together.
        $pdo->beginTransaction();
        // Starts the guarded block for the delete transaction.
        try {
            // Handles real numeric schedule ids stored in the schedules table.
            if (ctype_digit($id)) {
                // Prepares a query for the application that owns this schedule.
                $scheduleStmt = $pdo->prepare('SELECT application_id FROM schedules WHERE schedule_id = :id LIMIT 1');
                // Runs the query with the schedule id.
                $scheduleStmt->execute(['id' => (int) $id]);
                // Stores the owning application id found by the query.
                $applicationId = (int) ($scheduleStmt->fetchColumn() ?: 0);
                // Prepares the delete statement for the schedule row.
                $deleteStmt = $pdo->prepare('DELETE FROM schedules WHERE schedule_id = :id');
                // Deletes the schedule with the given id.
                $deleteStmt->execute(['id' => (int) $id]);
            // Closes the numeric id branch.
            }

            // Only updates the application when an owning application was found.
            if ($applicationId > 0) {
                // Prepares a query for the application workflow state and status.
                $applicationStmt = $pdo->prepare('SELECT workflow_state, status FROM applications WHERE application_id = :id LIMIT 1');
                // Runs the query with the application id.
                $applicationStmt->execute(['id' => $applicationId]);
                // Fetches the application row.
                $application = $applicationStmt->fetch();
                // Only proceeds when the application row exists.
                if ($application) {
                    // Decodes the stored JSON workflow state into an array.
                    $workflow = json_decode((string) ($application['workflow_state'] ?? ''), true);
                    // Falls back to an empty array when the JSON is invalid.
                    $workflow = is_array($workflow) ? $workflow : [];
                    // Opens the unset call that clears the schedule details from workflow.
                    unset(
                        // Removes the saved presentation date.
                        $workflow['presentation_date'],
                        // Removes the saved presentation time.
                        $workflow['presentation_time'],
                        // Removes the saved presentation venue.
                        $workflow['presentation_venue'],
                        // Removes the saved presentation panel.
                        $workflow['presentation_panel'],
                        // Removes the saved presentation status.
                        $workflow['presentation_status']
                    // Closes the unset call.
                    );
                    // Prepares the update that saves the cleaned workflow state.
                    $applicationUpdate = $pdo->prepare(
                        // Opens the SQL that stores the workflow state and resets the scheduled status.
                        "UPDATE applications
                         SET workflow_state = :workflow_state,
                             status = CASE WHEN status = 'scheduled' THEN 'ready_for_presentation' ELSE status END
                         WHERE application_id = :id"
                    // Closes the prepared statement definition.
                    );
                    // Executes the update with the cleaned workflow state and the id.
                    $applicationUpdate->execute([
                        // Encodes the workflow state back to JSON for storage.
                        'workflow_state' => json_encode($workflow, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        // Passes the application id parameter.
                        'id' => $applicationId,
                    // Closes the execute parameter array.
                    ]);
                // Closes the application exists check.
                }
            // Closes the owning application check.
            }

            // Commits the transaction so both changes are saved.
            $pdo->commit();
        // Catches errors so the transaction can be rolled back first.
        } catch (Throwable $e) {
            // Checks whether the transaction is still open.
            if ($pdo->inTransaction()) {
                // Rolls back the unsaved changes.
                $pdo->rollBack();
            // Closes the transaction check.
            }
            // Rethrows the error so the caller sees it.
            throw $e;
        // Closes the try/catch for the transaction.
        }
    // Throws for invalid ids when the database is in use.
    } elseif (class_exists('DB')) {
        // Raises an error because the schedule id format is not valid.
        throw new InvalidArgumentException('The schedule identifier is invalid.');
    // Closes the invalid id branch.
    }

    // Loads the session schedules list for the store cleanup.
    $storedSchedules = storeGet('schedules');
    // Resets the tracked application id to zero.
    $applicationId = 0;
    // Loops over the stored schedules to find the deleted one.
    foreach ($storedSchedules as $schedule) {
        // Checks whether this stored schedule has the deleted id.
        if ((string) ($schedule['id'] ?? '') === $id) {
            // Remembers its application id before removing the schedule.
            $applicationId = (int) ($schedule['applicationId'] ?? 0);
            // Stops looping once the schedule is found.
            break;
        // Closes the schedule id check.
        }
    // Closes the stored schedule loop.
    }
    // Saves the schedule list with the deleted record filtered out.
    storeSet('schedules', array_values(array_filter($storedSchedules, static function ($s) use ($id) {
        // Keeps only schedules whose id differs from the deleted id.
        return (string) ($s['id'] ?? '') !== $id;
    // Closes the filter callback and the storeSet call.
    })));

    // Only cleans the application when the schedule had an application id.
    if ($applicationId > 0) {
        // Loads the session applications list.
        $applications = storeGet('applications');
        // Loops by reference over the applications to find the right one.
        foreach ($applications as &$application) {
            // Skips applications that are not the schedule's application.
            if ((int) ($application['id'] ?? 0) !== $applicationId) {
                // Continues to the next application record.
                continue;
            // Closes the application id check.
            }
            // Only clears workflow keys when the workflow state is an array.
            if (is_array($application['workflowState'] ?? null)) {
                // Loops over the presentation keys that must be removed.
                foreach (['presentation_date', 'presentation_time', 'presentation_venue', 'presentation_panel', 'presentation_status'] as $key) {
                    // Removes this presentation key from the workflow state.
                    unset($application['workflowState'][$key]);
                // Closes the presentation key loop.
                }
            // Closes the workflow state array check.
            }
            // Checks whether the application is still marked as scheduled.
            if (($application['status'] ?? '') === 'scheduled') {
                // Moves the status back to ready for presentation.
                $application['status'] = 'ready_for_presentation';
            // Closes the scheduled status check.
            }
        // Closes the application loop.
        }
        // Breaks the reference after the loop.
        unset($application);
        // Saves the updated applications list.
        storeSet('applications', $applications);
    // Closes the application cleanup block.
    }
// Closes deleteScheduleRecord().
}

// Starts deletePanelMemberRecord(), which deletes a panel member.
function deletePanelMemberRecord(string $id): void {
    // Uses the database branch for numeric panel member ids.
    if (class_exists('DB') && ctype_digit($id)) {
        // Starts the guarded block for the delete query.
        try {
            // Prepares the DELETE statement for the panel_members table.
            $stmt = DB::getConnection()->prepare('DELETE FROM panel_members WHERE panel_member_id = :id');
            // Deletes the panel member with the given id.
            $stmt->execute(['id' => (int) $id]);
            // Returns early because the database delete succeeded.
            return;
        // Catches database errors so the session store is cleaned instead.
        } catch (Throwable $e) {
        // Closes the empty catch block.
        }
    // Closes the database branch.
    }
    // Saves the panel list with the deleted member filtered out.
    storeSet('panels', array_values(array_filter(storeGet('panels'), static function ($p) use ($id) {
        // Keeps only panel members whose id differs from the deleted id.
        return (string) $p['id'] !== $id;
    // Closes the filter callback and the storeSet call.
    })));
// Closes deletePanelMemberRecord().
}

// Starts normalizePanelMemberRow(), which shapes a panel row for the views.
function normalizePanelMemberRow(array $row): array {
    // Trims the member's first name from the database row.
    $firstName = trim((string) ($row['first_name'] ?? ''));
    // Trims the middle name, empty when it was not set.
    $middleName = trim((string) ($row['middle_name'] ?? ''));
    // Trims the last name from the database row.
    $lastName = trim((string) ($row['last_name'] ?? ''));
    // Joins first and middle names into the given name text.
    $givenName = trim(implode(' ', array_filter([$firstName, $middleName])));
    // Returns the normalized panel member array.
    return [
        // Uses the panel_member_id, or id, as the member id.
        'id' => (string) ($row['panel_member_id'] ?? $row['id'] ?? ''),
        // Builds a last name first name label, or falls back to the name field.
        'name' => $lastName !== '' ? $lastName . ', ' . $givenName : (string) ($row['name'] ?? ''),
        // Keeps the clean first name.
        'first_name' => $firstName,
        // Keeps the clean middle name.
        'middle_name' => $middleName,
        // Keeps the clean last name.
        'last_name' => $lastName,
        // Keeps the qualification text, empty when not set.
        'qualification' => (string) ($row['qualification'] ?? ''),
        // Keeps the email address, empty when not set.
        'email' => (string) ($row['email'] ?? ''),
        // Keeps the notes, empty when not set.
        'notes' => (string) ($row['notes'] ?? ''),
        // Stores how many schedules the member is assigned to.
        'panelSessions' => (int) ($row['panel_sessions'] ?? $row['panelSessions'] ?? 0),
        // Keeps the availability, defaulting to available.
        'availability' => (string) ($row['availability'] ?? 'available'),
    // Closes the normalized member array.
    ];
// Closes normalizePanelMemberRow().
}

// Starts databasePanelMembers(), which lists panel members from the DB.
function databasePanelMembers(): array {
    // Runs the query only when the database layer exists.
    if (class_exists('DB')) {
        // Starts the guarded block for the panel member query.
        try {
            // Counts each member's schedule assignments and sorts by name.
            $rows = DB::query('SELECT p.*, COUNT(a.assignment_id) AS panel_sessions FROM panel_members p LEFT JOIN schedule_panel_assignments a ON a.panel_member_id = p.panel_member_id GROUP BY p.panel_member_id ORDER BY p.last_name, p.first_name, p.middle_name')->fetchAll();
            // Normalizes every database row into the shared member shape.
            return array_map('normalizePanelMemberRow', $rows);
        // Catches query errors so the session store can be used.
        } catch (Throwable $e) {
        // Closes the empty catch block.
        }
    // Closes the database condition.
    }
    // Returns the session store panel members in the same shape.
    return array_map('normalizePanelMemberRow', storeGet('panels'));
// Closes databasePanelMembers().
}

// Starts findPanelMember(), which finds one panel member by id.
function findPanelMember(string $id): ?array {
    // Searches the session store panel list first.
    foreach (storeGet('panels') as $p) {
        // Checks whether this stored member has the requested id.
        if ((string) $p['id'] === $id) {
            // Returns the stored member in normalized shape.
            return normalizePanelMemberRow($p);
        // Closes the stored member id check.
        }
    // Closes the store search loop.
    }
    // Falls back to the database for numeric panel member ids.
    if (class_exists('DB') && ctype_digit($id)) {
        // Starts the guarded block for the database lookup.
        try {
            // Prepares a query for the panel member row.
            $stmt = DB::getConnection()->prepare('SELECT * FROM panel_members WHERE panel_member_id = :id LIMIT 1');
            // Runs the query with the numeric member id.
            $stmt->execute(['id' => (int) $id]);
            // Fetches the member row.
            $row = $stmt->fetch();
            // Returns the normalized row, or null when it was not found.
            return $row ? normalizePanelMemberRow($row) : null;
        // Catches database errors and returns null.
        } catch (Throwable $e) {
            // Returns null because the lookup failed.
            return null;
        // Closes the catch block.
        }
    // Closes the database condition.
    }
    // Returns null when no panel member matched the id.
    return null;
// Closes findPanelMember().
}

// Starts addPanelMemberRecord(), which inserts a new panel member.
function addPanelMemberRecord(array $m): array {
    // Uses the database when it exists and names were provided.
    if (class_exists('DB') && isset($m['first_name'], $m['last_name'])) {
        // Inserts the member into the panel_members table.
        $row = DB::insert('panel_members', [
            // Formats the first name consistently before saving.
            'first_name' => formatPersonName($m['first_name']),
            // Formats the optional middle name, storing null when empty.
            'middle_name' => formatPersonName((string) ($m['middle_name'] ?? '')) ?: null,
            // Formats the last name consistently before saving.
            'last_name' => formatPersonName($m['last_name']),
            // Trims the qualification text.
            'qualification' => trim($m['qualification']),
            // Trims the email, storing null when empty.
            'email' => trim((string) ($m['email'] ?? '')) ?: null,
            // Trims the notes, storing null when empty.
            'notes' => trim((string) ($m['notes'] ?? '')) ?: null,
            // Saves the availability, defaulting to available.
            'availability' => $m['availability'] ?? 'available',
        // Closes the insert data array.
        ]);
        // Returns the inserted member in normalized shape.
        return normalizePanelMemberRow($row);
    // Closes the database branch.
    }
    // Loads the session store panel list.
    $list = storeGet('panels');
    // Starts the new record built for the session store.
    $rec = [
        // Generates a pm_ prefixed unique id for the session record.
        'id' => 'pm_' . uniqid(),
        // Stores the member's display name.
        'name' => $m['name'],
        // Stores the qualification text.
        'qualification' => $m['qualification'],
        // Stores the email, empty string when not provided.
        'email' => $m['email'] ?? '',
        // Stores the notes, empty string when not provided.
        'notes' => $m['notes'] ?? '',
        // Stores the session count, defaulting to zero.
        'panelSessions' => (int) ($m['panelSessions'] ?? 0),
        // Stores the availability, defaulting to available.
        'availability' => $m['availability'] ?? 'available',
    // Closes the new record array.
    ];
    // Appends the new member to the list.
    $list[] = $rec;
    // Saves the updated panel list.
    storeSet('panels', $list);
    // Returns the new session record.
    return $rec;
// Closes addPanelMemberRecord().
}

// Starts updatePanelMemberRecord(), which updates one panel member.
function updatePanelMemberRecord(string $id, array $patch): ?array {
    // Uses the database branch for numeric member ids.
    if (class_exists('DB') && ctype_digit($id)) {
        // Keeps only the fields the edit form is allowed to change.
        $data = array_intersect_key($patch, array_flip(['first_name', 'middle_name', 'last_name', 'qualification', 'email', 'notes', 'availability']));
        // Only updates when at least one allowed field was sent.
        if ($data) {
            // Loops over the name fields that need consistent formatting.
            foreach (['first_name', 'middle_name', 'last_name'] as $nameField) {
                // Checks whether this name field was actually submitted.
                if (array_key_exists($nameField, $data)) {
                    // Formats the submitted name field value.
                    $data[$nameField] = formatPersonName((string) $data[$nameField]);
                // Closes the name field presence check.
                }
            // Closes the name formatting loop.
            }
            // Loops over the fields that should be null when empty.
            foreach (['middle_name', 'email', 'notes'] as $nullable) {
                // Checks whether this optional field was submitted.
                if (array_key_exists($nullable, $data)) {
                    // Trims the value and stores null when it is empty.
                    $data[$nullable] = trim((string) $data[$nullable]) ?: null;
                // Closes the optional field presence check.
                }
            // Closes the optional field loop.
            }
            // Runs the update on panel_members for the given member id.
            DB::update('panel_members', $data, ['panel_member_id' => (int) $id]);
        // Closes the data check.
        }
        // Returns the fresh member record after the update.
        return findPanelMember($id);
    // Closes the database branch.
    }
    // Loads the session store panel list.
    $list = storeGet('panels');
    // Loops over the stored members to find the matching id.
    foreach ($list as $i => $p) {
        // Skips members whose id does not match.
        if ((string) $p['id'] !== $id) {
            // Continues to the next stored member.
            continue;
        // Closes the id check.
        }
        // Merges the submitted changes into the stored member.
        $list[$i] = array_merge($p, $patch);
        // Saves the updated panel list.
        storeSet('panels', $list);
        // Returns the updated member record.
        return $list[$i];
    // Closes the stored member loop.
    }
    // Returns null when no member matched the id.
    return null;
// Closes updatePanelMemberRecord().
}

/**
 * =========================================================================
 * Documenter Pool Functions
 * =========================================================================
 */

// Starts normalizeDocumenterRow(), which shapes a documenter row for views.
function normalizeDocumenterRow(array $row): array {
    // Trims the documenter's first name.
    $firstName = trim((string) ($row['first_name'] ?? ''));
    // Trims the middle name, empty when it was not set.
    $middleName = trim((string) ($row['middle_name'] ?? ''));
    // Trims the last name.
    $lastName = trim((string) ($row['last_name'] ?? ''));
    // Joins first and middle names into the given name text.
    $givenName = trim(implode(' ', array_filter([$firstName, $middleName])));
    // Returns the normalized documenter array.
    return [
        // Uses the documenter_id, or id, as the documenter id.
        'id' => (string) ($row['documenter_id'] ?? $row['id'] ?? ''),
        // Builds a last name first name label, or falls back to the name field.
        'name' => $lastName !== '' ? $lastName . ', ' . $givenName : (string) ($row['name'] ?? ''),
        // Keeps the clean first name.
        'first_name' => $firstName,
        // Keeps the clean middle name.
        'middle_name' => $middleName,
        // Keeps the clean last name.
        'last_name' => $lastName,
        // Keeps the qualification text, empty when not set.
        'qualification' => (string) ($row['qualification'] ?? ''),
        // Keeps the email address, empty when not set.
        'email' => (string) ($row['email'] ?? ''),
        // Keeps the notes, empty when not set.
        'notes' => (string) ($row['notes'] ?? ''),
        // Keeps the availability, defaulting to available.
        'availability' => (string) ($row['availability'] ?? 'available'),
    // Closes the normalized documenter array.
    ];
// Closes normalizeDocumenterRow().
}

// Starts databaseDocumenters(), which lists documenters from the database.
function databaseDocumenters(): array {
    // Runs the query only when the database layer exists.
    if (class_exists('DB')) {
        // Starts the guarded block for the documenter query.
        try {
            // Counts each documenter's schedules and sorts them by name.
            $rows = DB::query('SELECT d.*, COUNT(s.schedule_id) AS documenter_sessions FROM documenter_pool d LEFT JOIN schedules s ON s.documenter_id = d.documenter_id GROUP BY d.documenter_id ORDER BY d.last_name, d.first_name, d.middle_name')->fetchAll();
            // Normalizes every row into the shared documenter shape.
            return array_map('normalizeDocumenterRow', $rows);
        // Catches query errors so the session store can be used.
        } catch (Throwable $e) {
        // Closes the empty catch block.
        }
    // Closes the database condition.
    }
    // Returns the session store documenters in the same shape.
    return array_map('normalizeDocumenterRow', storeGet('documenters') ?: []);
// Closes databaseDocumenters().
}

// Starts findDocumenter(), which finds one documenter by id.
function findDocumenter(string $id): ?array {
    // Searches the session store documenter list first.
    foreach (storeGet('documenters') as $d) {
        // Checks whether this stored documenter has the requested id.
        if ((string) $d['id'] === $id) {
            // Returns the stored documenter in normalized shape.
            return normalizeDocumenterRow($d);
        // Closes the stored documenter id check.
        }
    // Closes the store search loop.
    }
    // Falls back to the database for numeric documenter ids.
    if (class_exists('DB') && ctype_digit($id)) {
        // Starts the guarded block for the database lookup.
        try {
            // Prepares a query for the documenter_pool row.
            $stmt = DB::getConnection()->prepare('SELECT * FROM documenter_pool WHERE documenter_id = :id LIMIT 1');
            // Runs the query with the numeric documenter id.
            $stmt->execute(['id' => (int) $id]);
            // Fetches the documenter row.
            $row = $stmt->fetch();
            // Returns the normalized row, or null when it was not found.
            return $row ? normalizeDocumenterRow($row) : null;
        // Catches database errors and returns null.
        } catch (Throwable $e) {
            // Returns null because the lookup failed.
            return null;
        // Closes the catch block.
        }
    // Closes the database condition.
    }
    // Returns null when no documenter matched the id.
    return null;
// Closes findDocumenter().
}

// Starts addDocumenterRecord(), which inserts a new documenter.
function addDocumenterRecord(array $m): array {
    // Uses the database when it exists and names were provided.
    if (class_exists('DB') && isset($m['first_name'], $m['last_name'])) {
        // Inserts the documenter into the documenter_pool table.
        $row = DB::insert('documenter_pool', [
            // Formats the first name consistently before saving.
            'first_name' => formatPersonName($m['first_name']),
            // Formats the optional middle name, storing null when empty.
            'middle_name' => formatPersonName((string) ($m['middle_name'] ?? '')) ?: null,
            // Formats the last name consistently before saving.
            'last_name' => formatPersonName($m['last_name']),
            // Trims the qualification text.
            'qualification' => trim($m['qualification']),
            // Trims the email, storing null when empty.
            'email' => trim((string) ($m['email'] ?? '')) ?: null,
            // Trims the notes, storing null when empty.
            'notes' => trim((string) ($m['notes'] ?? '')) ?: null,
            // Saves the availability, defaulting to available.
            'availability' => $m['availability'] ?? 'available',
        // Closes the insert data array.
        ]);
        // Returns the inserted documenter in normalized shape.
        return normalizeDocumenterRow($row);
    // Closes the database branch.
    }
    // Loads the session store documenter list.
    $list = storeGet('documenters') ?: [];
    // Starts the new record built for the session store.
    $rec = [
        // Generates a doc_ prefixed unique id for the session record.
        'id' => 'doc_' . uniqid(),
        // Stores the documenter's display name.
        'name' => $m['name'],
        // Stores the qualification text.
        'qualification' => $m['qualification'],
        // Stores the email, empty string when not provided.
        'email' => $m['email'] ?? '',
        // Stores the notes, empty string when not provided.
        'notes' => $m['notes'] ?? '',
        // Stores the availability, defaulting to available.
        'availability' => $m['availability'] ?? 'available',
    // Closes the new record array.
    ];
    // Appends the new documenter to the list.
    $list[] = $rec;
    // Saves the updated documenter list.
    storeSet('documenters', $list);
    // Returns the new session record.
    return $rec;
// Closes addDocumenterRecord().
}

// Starts updateDocumenterRecord(), which updates one documenter.
function updateDocumenterRecord(string $id, array $patch): ?array {
    // Uses the database branch for numeric documenter ids.
    if (class_exists('DB') && ctype_digit($id)) {
        // Keeps only the fields the edit form is allowed to change.
        $data = array_intersect_key($patch, array_flip(['first_name', 'middle_name', 'last_name', 'qualification', 'email', 'notes', 'availability']));
        // Only updates when at least one allowed field was sent.
        if ($data) {
            // Loops over the name fields that need consistent formatting.
            foreach (['first_name', 'middle_name', 'last_name'] as $nameField) {
                // Checks whether this name field was actually submitted.
                if (array_key_exists($nameField, $data)) {
                    // Formats the submitted name field value.
                    $data[$nameField] = formatPersonName((string) $data[$nameField]);
                // Closes the name field presence check.
                }
            // Closes the name formatting loop.
            }
            // Loops over the fields that should be null when empty.
            foreach (['middle_name', 'email', 'notes'] as $nullable) {
                // Checks whether this optional field was submitted.
                if (array_key_exists($nullable, $data)) {
                    // Trims the value and stores null when it is empty.
                    $data[$nullable] = trim((string) $data[$nullable]) ?: null;
                // Closes the optional field presence check.
                }
            // Closes the optional field loop.
            }
            // Runs the update on documenter_pool for the given documenter id.
            DB::update('documenter_pool', $data, ['documenter_id' => (int) $id]);
        // Closes the data check.
        }
        // Returns the fresh documenter record after the update.
        return findDocumenter($id);
    // Closes the database branch.
    }
    // Loads the session store documenter list.
    $list = storeGet('documenters') ?: [];
    // Loops over the stored documenters to find the matching id.
    foreach ($list as $i => $d) {
        // Skips documenters whose id does not match.
        if ((string) $d['id'] !== $id) {
            // Continues to the next stored documenter.
            continue;
        // Closes the id check.
        }
        // Merges the submitted changes into the stored documenter.
        $list[$i] = array_merge($d, $patch);
        // Saves the updated documenter list.
        storeSet('documenters', $list);
        // Returns the updated documenter record.
        return $list[$i];
    // Closes the stored documenter loop.
    }
    // Returns null when no documenter matched the id.
    return null;
// Closes updateDocumenterRecord().
}

// Starts deleteDocumenterRecord(), which deletes a documenter.
function deleteDocumenterRecord(string $id): void {
    // Uses the database branch for numeric documenter ids.
    if (class_exists('DB') && ctype_digit($id)) {
        // Starts the guarded block for the assignment check.
        try {
            // Prepares a count of schedules already using this documenter.
            $assigned = DB::getConnection()->prepare('SELECT COUNT(*) FROM schedules WHERE documenter_id = :id');
            // Runs the count query with the documenter id.
            $assigned->execute(['id' => (int) $id]);
            // Checks whether any schedule still uses this documenter.
            if ((int) $assigned->fetchColumn() > 0) {
                // Stops deletion and tells the coordinator to mark them unavailable instead.
                throw new LogicException('This documenter is assigned to existing schedules. Mark the documenter unavailable instead of removing them.');
            // Closes the assignment check.
            }
            // Deletes the documenter row now that nothing uses it.
            DB::query('DELETE FROM documenter_pool WHERE documenter_id = :id', ['id' => (int) $id]);
        // Catches the error so it can be rethrown to the caller.
        } catch (Throwable $e) {
            // Rethrows the error so the calling page can show it.
            throw $e;
        // Closes the try/catch.
        }
    // Closes the database branch.
    }
    // Loads the session store documenter list.
    $list = storeGet('documenters') ?: [];
    // Loops over the stored documenters to find the matching id.
    foreach ($list as $i => $d) {
        // Checks whether this stored documenter has the deleted id.
        if ((string) $d['id'] === $id) {
            // Removes the matching documenter from the session list.
            unset($list[$i]);
            // Saves the reindexed documenter list.
            storeSet('documenters', array_values($list));
            // Stops looping after the documenter is removed.
            break;
        // Closes the id check.
        }
    // Closes the stored documenter loop.
    }
// Closes deleteDocumenterRecord().
}

// Starts documenterSelectOptions(), which lists available documenters.
function documenterSelectOptions(): array {
    // Starts the guarded block for the documenter query.
    try {
        // Loads available documenters with names and qualifications, sorted by name.
        $rows = DB::query("SELECT first_name, middle_name, last_name, qualification FROM documenter_pool WHERE availability = 'available' ORDER BY last_name, first_name, middle_name")->fetchAll();
        // Prepares the list of option labels.
        $options = [];
        // Loops over each available documenter row.
        foreach ($rows as $row) {
            // Builds the last name first name label for the option.
            $name = trim($row['last_name'] . ', ' . $row['first_name'] . ' ' . ($row['middle_name'] ?? ''));
            // Adds the qualification in parentheses when it is not empty.
            $options[] = $name . ($row['qualification'] !== '' ? ' (' . $row['qualification'] . ')' : '');
        // Closes the documenter loop.
        }
        // Returns the list of documenter option labels.
        return $options;
    // Catches query errors and returns an empty list.
    } catch (Throwable $e) {
        // Returns an empty list when the lookup fails.
        return [];
    // Closes the catch block.
    }
// Closes documenterSelectOptions().
}
