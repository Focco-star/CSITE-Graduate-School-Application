<?php
/* CSITE configuration module: Schedule records, panel assignments, schedule/application synchronization, and schedule forms. */

// Starts findSchedule(), which finds one schedule by id.
function findSchedule(string $id): ?array {
    // Searches the session store schedules first.
    foreach (storeGet('schedules') as $s) {
        // Checks whether this stored schedule has the requested id.
        if ((string) $s['id'] === $id) {
            // Returns the matching session schedule.
            return $s;
        // Closes the schedule id check.
        }
    // Closes the store search loop.
    }
    // Falls back to the database for numeric schedule ids.
    if (class_exists('DB') && ctype_digit($id)) {
        // Starts the guarded block for the schedule query.
        try {
            // Prepares the query that joins schedule, application, user and panel data.
            $stmt = DB::getConnection()->prepare(
                // Opens the SQL selecting schedule fields, student, adviser, documentor and panel.
                'SELECT sc.schedule_id AS id, sc.application_id AS applicationId,
                        u.email AS studentEmail,
                        TRIM(CONCAT(s.last_name, ", ", s.first_name, IF(s.middle_initial IS NULL OR s.middle_initial = "", "", CONCAT(" ", s.middle_initial)))) AS studentName,
                        a.presentation_stage AS stage, sc.presentation_date AS date,
                        sc.start_time AS time, sc.venue, sc.status, ap.name AS adviser,
                        TRIM(CONCAT_WS(" ", sc.documentor_first_name, sc.documentor_middle_name, sc.documentor_last_name)) AS documentor,
                        GROUP_CONCAT(CONCAT(pm.last_name, ", ", pm.first_name, IF(pm.middle_name IS NULL OR pm.middle_name = "", "", CONCAT(" ", pm.middle_name)) ) ORDER BY spa.assignment_id SEPARATOR ", ") AS panel
                 FROM schedules sc
                 INNER JOIN applications a ON a.application_id = sc.application_id
                 INNER JOIN users u ON u.user_id = a.user_id
                 LEFT JOIN students s ON s.user_id = u.user_id
                 LEFT JOIN advisor_pool ap ON ap.adviser_id = sc.adviser_id
                 LEFT JOIN schedule_panel_assignments spa ON spa.schedule_id = sc.schedule_id
                 LEFT JOIN panel_members pm ON pm.panel_member_id = spa.panel_member_id
                 WHERE sc.schedule_id = :id
                 GROUP BY sc.schedule_id
                 LIMIT 1'
            // Closes the prepared statement definition.
            );
            // Runs the query with the numeric schedule id.
            $stmt->execute(['id' => (int) $id]);
            // Returns the schedule row, or null when it was not found.
            return $stmt->fetch() ?: null;
        // Catches query errors and returns null.
        } catch (Throwable $e) {
            // Returns null because the lookup failed.
            return null;
        // Closes the catch block.
        }
    // Closes the database condition.
    }
    // Returns null when no schedule matched the id.
    return null;
// Closes findSchedule().
}

// Starts databaseSchedules(), which lists all schedules from the database.
function databaseSchedules(): array {
    // Uses the session store when the database layer is missing.
    if (!class_exists('DB')) {
        // Returns the stored schedules as a fallback.
        return storeGet('schedules');
    // Closes the missing database check.
    }
    // Starts the guarded block for the schedule list query.
    try {
        // Runs the query that joins schedules with students, advisers and panels.
        $rows = DB::query(
            // Opens the SQL selecting schedule fields, student, adviser, documentor and panel.
            'SELECT sc.schedule_id AS id, sc.application_id AS applicationId,
                    u.email AS studentEmail,
                    TRIM(CONCAT(s.last_name, ", ", s.first_name, IF(s.middle_initial IS NULL OR s.middle_initial = "", "", CONCAT(" ", s.middle_initial)))) AS studentName,
                    a.presentation_stage AS stage, sc.presentation_date AS date,
                    sc.start_time AS time, sc.venue, sc.status, ap.name AS adviser,
                    TRIM(CONCAT_WS(" ", sc.documentor_first_name, sc.documentor_middle_name, sc.documentor_last_name)) AS documentor,
                    GROUP_CONCAT(CONCAT(pm.last_name, ", ", pm.first_name, IF(pm.middle_name IS NULL OR pm.middle_name = "", "", CONCAT(" ", pm.middle_name)) ) ORDER BY spa.assignment_id SEPARATOR ", ") AS panel
             FROM schedules sc
             INNER JOIN applications a ON a.application_id = sc.application_id
             INNER JOIN users u ON u.user_id = a.user_id
             LEFT JOIN students s ON s.user_id = u.user_id
             LEFT JOIN advisor_pool ap ON ap.adviser_id = sc.adviser_id
             LEFT JOIN schedule_panel_assignments spa ON spa.schedule_id = sc.schedule_id
             LEFT JOIN panel_members pm ON pm.panel_member_id = spa.panel_member_id
             GROUP BY sc.schedule_id
             ORDER BY sc.presentation_date, sc.start_time'
        // Fetches all schedule rows from the query.
        )->fetchAll();
        // Returns the schedule rows.
        return $rows;
    // Catches query errors so the session store can be used.
    } catch (Throwable $e) {
        // Returns the stored schedules when the query fails.
        return storeGet('schedules');
    // Closes the catch block.
    }
// Closes databaseSchedules().
}

// Starts databaseSchedulePanelMembers(), listing panel members by application.
function databaseSchedulePanelMembers(int $applicationId): array {
    // Returns early for missing DB or invalid id, then opens the try block.
    if (!class_exists('DB') || $applicationId <= 0) return [];    try {
        // Runs the query that joins assignments with panel member details.
        $rows = DB::query(
                // Opens the SQL selecting member fields plus the assigned panel role.
                'SELECT pm.*, spa.panel_role
             FROM schedules sc
             INNER JOIN schedule_panel_assignments spa ON spa.schedule_id = sc.schedule_id
             INNER JOIN panel_members pm ON pm.panel_member_id = spa.panel_member_id
             WHERE sc.application_id = :application_id
             ORDER BY spa.assignment_id'
        // Runs the query with the application id and fetches all rows.
        , ['application_id' => $applicationId])->fetchAll();
        // Notes that the rows use the same shape as the panel pool.
        // The same shape as the panel pool, so every consumer finds the name
        // Notes that consumers find the member name under the same key.
        // of a member under the same key.
        // Maps each database row into the shared panel member shape.
        return array_map(static function (array $row): array {
            // Normalizes the row into the common member array.
            $member = normalizePanelMemberRow($row);
            // Adds the panel role only when the query returned one.
            if (isset($row['panel_role'])) {
                // Stores the panel role as text on the member.
                $member['panel_role'] = (string) $row['panel_role'];
            // Closes the panel role check.
            }
            // Returns the normalized member for this row.
            return $member;
        // Closes the mapping callback and the array_map call.
        }, $rows);
    // Catches query errors and returns an empty list.
    } catch (Throwable $e) {
        // Returns an empty list when the lookup fails.
        return [];
    // Closes the catch block.
    }
// Closes databaseSchedulePanelMembers().
}

/**
 * Panel members assigned to exactly ONE schedule - the edit form preselects
 * these, so it must not mix in members of any other schedule of the same
 * application the way databaseSchedulePanelMembers() does.
 */
// Starts databaseSchedulePanelMembersForSchedule(), the per-schedule variant.
function databaseSchedulePanelMembersForSchedule(int $scheduleId): array {
    // Returns early when the database is missing or the schedule id is invalid.
    if (!class_exists('DB') || $scheduleId <= 0) return [];
    // Starts the guarded block for the per-schedule panel query.
    try {
        // Runs the query for the panel members of one schedule.
        $rows = DB::query(
                // Opens the SQL selecting member fields plus the assigned panel role.
                'SELECT pm.*, spa.panel_role
             FROM schedule_panel_assignments spa
             INNER JOIN panel_members pm ON pm.panel_member_id = spa.panel_member_id
             WHERE spa.schedule_id = :schedule_id
             ORDER BY spa.assignment_id',
            // Runs the query with the schedule id and fetches all rows.
            ['schedule_id' => $scheduleId])->fetchAll();
        // Maps each row into the shared panel member shape.
        return array_map(static function (array $row): array {
            // Normalizes the row into the common member array.
            $member = normalizePanelMemberRow($row);
            // Adds the panel role only when the query returned one.
            if (isset($row['panel_role'])) {
                // Stores the panel role as text on the member.
                $member['panel_role'] = (string) $row['panel_role'];
            // Closes the panel role check.
            }
            // Returns the normalized member for this row.
            return $member;
        // Closes the mapping callback and the array_map call.
        }, $rows);
    // Catches query errors and returns an empty list.
    } catch (Throwable $e) {
        // Returns an empty list when the lookup fails.
        return [];
    // Closes the catch block.
    }
// Closes databaseSchedulePanelMembersForSchedule().
}

// Starts schedulesForEmail(), which lists a student's schedules by email.
function schedulesForEmail(string $email): array {
    // Uses the database only when it exists and an email was given.
    if (class_exists('DB') && $email !== '') {
        // Starts the guarded block for the schedule query.
        try {
            // Prepares the query for this student's schedules, ordered by date and time.
            $stmt = DB::getConnection()->prepare('SELECT sc.schedule_id AS id, sc.application_id AS applicationId, u.email AS studentEmail, a.presentation_stage AS stage, sc.presentation_date AS date, sc.start_time AS time, sc.venue, sc.status FROM schedules sc INNER JOIN applications a ON a.application_id = sc.application_id INNER JOIN users u ON u.user_id = a.user_id WHERE u.email = :email ORDER BY sc.presentation_date, sc.start_time');
            // Runs the query with the student's email.
            $stmt->execute(['email' => $email]);
            // Returns all schedule rows for the student.
            return $stmt->fetchAll();
        // Catches query errors so the session store can be used.
        } catch (Throwable $e) {
        // Closes the empty catch block.
        }
    // Closes the database condition.
    }
    // Filters the stored schedules down to this student email.
    return array_values(array_filter(storeGet('schedules'), static function ($s) use ($email) {
        // Compares emails without caring about letter case.
        return strcasecmp((string) ($s['studentEmail'] ?? ''), $email) === 0;
    // Closes the filter callback and returns the matching list.
    }));
// Closes schedulesForEmail().
}

/**
 * Who is on the panel of each scheduled presentation of a student, together
 * with the adviser and the documenter of that schedule.
 *
 * The keys are the workflow stage keys ('concept', 'proposal', 'final'), so the
 * Student Dashboard can show the team next to the date, time and venue of the
 * stage. The coordinator pages stay the single source of truth: this only
 * reads schedules, schedule_panel_assignments, panel_members and advisor_pool.
 *
 * @return array<string, array{scheduleId: string, panel: array<int, array{name: string, role: string}>, adviser: string, documentor: string}>
 */
// Starts studentPresentationDetails(), which collects team details per stage.
function studentPresentationDetails(string $email): array {
    // Trims the student email before using it in queries.
    $email = trim($email);
    // Stops when no email was given.
    if ($email === '') {
        // Returns an empty list because no student can be matched.
        return [];
    // Closes the empty email check.
    }

    // Prepares the map of stage key to presentation details.
    $details = [];
    // Prepares the map from schedule id to stage key.
    $stageBySchedule = [];

    // Runs the queries only when the database layer exists.
    if (class_exists('DB')) {
        // Starts the guarded block for the presentation queries.
        try {
            // Prepares the query for schedule, adviser and documentor details.
            $stmt = DB::getConnection()->prepare(
                // Opens the SQL selecting schedule id, stage, adviser and documentor name.
                'SELECT sc.schedule_id AS id, a.presentation_stage AS stage,
                        ap.name AS adviser,
                        TRIM(CONCAT_WS(" ", sc.documentor_first_name, sc.documentor_middle_name, sc.documentor_last_name)) AS documentor
                 FROM schedules sc
                 INNER JOIN applications a ON a.application_id = sc.application_id
                 INNER JOIN users u ON u.user_id = a.user_id
                 LEFT JOIN advisor_pool ap ON ap.adviser_id = sc.adviser_id
                 WHERE u.email = :email AND a.archived_at IS NULL
                 ORDER BY sc.presentation_date ASC, sc.start_time ASC'
            // Closes the prepared statement definition.
            );
            // Runs the query with the student email.
            $stmt->execute(['email' => $email]);
            // Loops over each schedule row of the student.
            foreach ($stmt->fetchAll() as $row) {
                // Converts the schedule stage label into a stage key.
                $key = stageKeyFromLabel((string) ($row['stage'] ?? ''));
                // Notes that later schedules replace older ones for the same stage.
                // Later schedules win, so a rescheduled stage shows the newest.
                // Starts the details entry for this stage.
                $details[$key] = [
                    // Stores the schedule id for the stage.
                    'scheduleId' => (string) ($row['id'] ?? ''),
                    // Starts with an empty panel list to be filled later.
                    'panel' => [],
                    // Stores the trimmed adviser name.
                    'adviser' => trim((string) ($row['adviser'] ?? '')),
                    // Notes that some documenter entries include the qualification.
                    // Some schedules carry the qualification next to the name;
                    // Notes that the student only needs the documenter name.
                    // the student only needs the name itself.
                    // Stores the documenter name with parentheses and extra spaces removed.
                    'documentor' => trim(preg_replace('/\s+/', ' ', preg_replace('/\([^)]*\)/', ' ', (string) ($row['documentor'] ?? ''))) ?? ''),
                // Closes the stage details array.
                ];
                // Remembers which stage this schedule id belongs to.
                $stageBySchedule[(string) ($row['id'] ?? '')] = $key;
            // Closes the schedule row loop.
            }

            // Loads panel members only when schedules were found.
            if ($stageBySchedule) {
                // Prepares the query for panel members of the student's schedules.
                $panelStmt = DB::getConnection()->prepare(
                    // Opens the SQL selecting schedule id, role and member names.
                    'SELECT spa.schedule_id, spa.panel_role, pm.first_name, pm.middle_name, pm.last_name
                     FROM schedule_panel_assignments spa
                     INNER JOIN panel_members pm ON pm.panel_member_id = spa.panel_member_id
                     INNER JOIN schedules sc ON sc.schedule_id = spa.schedule_id
                     INNER JOIN applications a ON a.application_id = sc.application_id
                     INNER JOIN users u ON u.user_id = a.user_id
                     WHERE u.email = :email
                     ORDER BY spa.assignment_id ASC'
                // Closes the prepared statement definition.
                );
                // Runs the query with the student email.
                $panelStmt->execute(['email' => $email]);
                // Loops over each panel assignment row.
                foreach ($panelStmt->fetchAll() as $row) {
                    // Reads the schedule id of this panel assignment.
                    $scheduleId = (string) ($row['schedule_id'] ?? '');
                    // Finds the stage key for that schedule, blank when unknown.
                    $key = $stageBySchedule[$scheduleId] ?? '';
                    // Skips assignments with no stage or no details entry.
                    if ($key === '' || !isset($details[$key])) {
                        // Continues to the next panel assignment.
                        continue;
                    // Closes the stage check.
                    }
                    // Notes that panels of older schedules are ignored.
                    // Ignore the panel of an older schedule of the same stage.
                    // Skips panels that belong to an older schedule of the same stage.
                    if ((string) ($details[$key]['scheduleId'] ?? '') !== $scheduleId) {
                        // Continues to the next panel assignment.
                        continue;
                    // Closes the older schedule check.
                    }
                    // Collects the member name parts that are not empty.
                    $parts = array_values(array_filter([
                        // Adds the trimmed first name when present.
                        trim((string) ($row['first_name'] ?? '')),
                        // Adds the trimmed middle name when present.
                        trim((string) ($row['middle_name'] ?? '')),
                        // Adds the trimmed last name when present.
                        trim((string) ($row['last_name'] ?? '')),
                    // Closes the name parts array and its filter callback.
                    ], static fn($part) => $part !== ''));
                    // Skips members with no usable name parts.
                    if (!$parts) {
                        // Continues to the next panel assignment.
                        continue;
                    // Closes the empty name check.
                    }
                    // Starts one panel member entry for the stage.
                    $details[$key]['panel'][] = [
                        // Formats the joined name with single spaces.
                        'name' => formatPersonName(preg_replace('/\s+/', ' ', implode(' ', $parts)) ?: ''),
                        // Stores the panel role, defaulting to member.
                        'role' => (string) ($row['panel_role'] ?? 'member'),
                    // Closes the panel member entry.
                    ];
                // Closes the panel assignment loop.
                }
            // Closes the panel query block.
            }
        // Catches query errors so the session fallback can run.
        } catch (Throwable $e) {
            // Clears the collected details because the query data failed.
            $details = [];
            // Clears the schedule to stage map too.
            $stageBySchedule = [];
        // Closes the catch block.
        }
    // Closes the database block.
    }

    // Starts a comment explaining the session-only fallback below.
    /*
     * Session-only prototype data has no schedule ids or panel assignments, so
     * keep the comma separated panel string the coordinator typed.
     */
    // Uses the session schedules only when no database details were found.
    if (!$details) {
        // Loops over the stored schedules.
        foreach (storeGet('schedules') as $schedule) {
            // Skips schedules whose student email is different.
            if (strcasecmp((string) ($schedule['studentEmail'] ?? ''), $email) !== 0) {
                // Continues to the next stored schedule.
                continue;
            // Closes the email check.
            }
            // Prepares an empty panel list for this schedule.
            $panel = [];
            // Splits the stored comma separated panel names.
            foreach (explode(',', (string) ($schedule['panel'] ?? '')) as $name) {
                // Trims spaces around the typed member name.
                $name = trim($name);
                // Skips empty names and the TBD placeholder.
                if ($name === '' || strcasecmp($name, 'TBD') === 0) {
                    // Continues to the next typed name.
                    continue;
                // Closes the placeholder check.
                }
                // Adds the typed member name with the member role.
                $panel[] = ['name' => $name, 'role' => 'member'];
            // Closes the typed name loop.
            }
            // Stores the details for the stage of this stored schedule.
            $details[stageKeyFromLabel((string) ($schedule['stage'] ?? ''))] = [
                // Stores the session schedule id.
                'scheduleId' => (string) ($schedule['id'] ?? ''),
                // Stores the typed panel list.
                'panel' => $panel,
                // Stores the trimmed adviser name.
                'adviser' => trim((string) ($schedule['adviser'] ?? '')),
                // Stores the trimmed documenter name.
                'documentor' => trim((string) ($schedule['documentor'] ?? '')),
            // Closes the stage details array.
            ];
        // Closes the stored schedule loop.
        }
    // Closes the session fallback block.
    }

    // Returns the collected presentation details by stage.
    return $details;
// Closes studentPresentationDetails().
}

/**
 * Normalise any schedule date a form can submit to 'Y-m-d'.
 *
 * The picker-backed schedule forms post machine dates ('2026-10-10'), while the
 * display side of the app writes milestone dates like 'Oct 10, 2026'. Accept both
 * (plus the other spellings strtotime understands) so valid dates never hit the
 * "invalid date or time" guard just because of the format they arrive in.
 */
// Starts scheduleDateYMD(), which converts any submitted date to Y-m-d.
function scheduleDateYMD(string $raw): ?string {
    // Trims spaces around the submitted date text.
    $raw = trim($raw);
    // Loops over the accepted date formats, most exact first.
    foreach (['!Y-m-d', '!Y-n-j', '!m/d/Y', '!n/j/Y', '!n/j/y', 'M j, Y', 'F j, Y', 'l, F j, Y'] as $format) {
        // Tries to read the date text using one of the accepted date formats.
        $d = DateTimeImmutable::createFromFormat($format, $raw);
        // Checks the date parsed and formats back to exactly the same text.
        if ($d instanceof DateTimeImmutable && $d->format(str_replace('!', '', $format)) === $raw) {
            // Returns the date in MySQL's Y-m-d form when a format matched.
            return $d->format('Y-m-d');
        // Closes the parsed-date check.
        }
    // Closes the date format loop.
    }
    // Starts the note about the fallback date parser below.
    // Fallback for the remaining human spellings - but never let a bare time
    // Explains that a time alone must not be treated as a date.
    // ("9:30", "9:30 AM") masquerade as a date, so a year has to be present.
    // Rejects text without a four-digit year so a time alone is not a date.
    if (!preg_match('/\b\d{4}\b/', $raw)) {
        // Returns null because the date is not usable.
        return null;
    // Closes the year check.
    }
    // Lets PHP's strtotime guess the date from other human spellings.
    $ts = strtotime($raw);
    // Returns the parsed date, or null when strtotime fails.
    return $ts === false ? null : date('Y-m-d', $ts);
// Closes scheduleDateYMD().
}

/**
 * Normalise any schedule time a form can submit to 'H:i:s'.
 * Accepts '14:55', '04:55:00', '4:55 PM', '4:55 pm', '9:05 am', ...
 */
// Normalises any submitted schedule time into the H:i:s database format.
function scheduleTimeHMS(string $raw): ?string {
    // Trims spaces around the submitted time text.
    $raw = trim($raw);
    // Checks whether the submitted time is empty.
    if ($raw === '') {
        // Returns null because there is no time to normalise.
        return null;
    // Closes the empty-time check.
    }
    // Tries each accepted clock format on the submitted time.
    foreach (['!H:i', '!H:i:s', '!g:i A', '!g:i:s A', '!G:i', '!G:i:s'] as $format) {
        // Parses the time text with the current format.
        $d = DateTimeImmutable::createFromFormat($format, $raw);
        // Checks the time parsed and matched the text ignoring letter case.
        if ($d instanceof DateTimeImmutable && strcasecmp($d->format(str_replace('!', '', $format)), $raw) === 0) {
            // Returns the time in 24-hour H:i:s database form.
            return $d->format('H:i:s');
        // Closes the parsed-time check.
        }
    // Closes the time format loop.
    }
    // Starts the note about the fallback time parser.
    // Fallback for spellings createFromFormat chokes on ('9:5 am'). A time
    // Explains that a time must still look like a clock value.
    // must still look like a clock value, anchored to a date for strtotime.
    // Requires a clock-like value before using the fallback parser.
    if (!preg_match('/^\d{1,2}:\d{1,2}(:\d{1,2})?\s*(?:[AaPp][Mm])?$/', $raw)) {
        // Returns null when the text does not look like a time.
        return null;
    // Closes the clock pattern check.
    }
    // Parses the time with a fixed dummy date so strtotime accepts it.
    $ts = strtotime('2000-01-01 ' . $raw);
    // Returns the time in H:i:s, or null when parsing fails.
    return $ts === false ? null : date('H:i:s', $ts);
// Closes scheduleTimeHMS().
}

/**
 * Split a documentor name written 'Last, First Middle' (the format the
 * Documenter Pool selects submit) into the schedules table's three name
 * columns, the same way addScheduleRecord() and updateScheduleRecord() store it.
 */
// Splits a 'Last, First Middle' documentor name into its three parts.
function documentorNameParts(string $name): array {
    // Removes extra spaces from the whole name.
    $name = trim($name);
    // Starts the surname as an empty string.
    $last = '';
    // Starts the list of given names as an empty array.
    $given = [];
    // Only splits the name when something was typed.
    if ($name !== '') {
        // Splits the name at the first comma and trims both halves.
        $halves = array_map('trim', explode(',', $name, 2));
        // Takes the text before the comma as the surname.
        $last = $halves[0];
        // Splits the given names on spaces and drops empty pieces.
        $given = array_values(array_filter(preg_split('/\s+/', $halves[1] ?? '') ?: [], 'strlen'));
    // Closes the non-empty name check.
    }
    // Returns the three name parts as an array.
    return [
        // Puts the surname in the last-name slot.
        'last' => $last,
        // Takes the first given name, or empty when none was typed.
        'first' => $given ? (string) array_shift($given) : '',
        // Joins the remaining given names into the middle name.
        'middle' => $given ? implode(' ', $given) : '',
    // Closes the returned name array.
    ];
// Closes documentorNameParts().
}

/**
 * Write a saved schedule into the workflow state of its application.
 *
 * The Milestone Tracker reads the presentation milestone from the application's
 * workflow_state. Writing the schedule only to the schedules table left
 * "Presentation Scheduled" unverified even though the schedule said Confirmed,
 * so the two pages could disagree. Every schedule save now goes through here.
 *
 * Only the keys that are present are written, so a later edit that changes the
 * date does not wipe the panel (and vice versa).
 *
 * @param array{date?: string, time?: string, venue?: string, panel?: string, status?: string} $schedule
 */
// Copies a saved schedule into the application's workflow state.
function syncScheduleToApplication(int $applicationId, array $schedule): void {
    // Skips the sync when the application id is missing or the DB is unavailable.
    if ($applicationId <= 0 || !class_exists('DB')) {
        // Returns early because there is nothing safe to update.
        return;
    // Closes the guard check.
    }
    // Starts the block that protects the database work.
    try {
        // Gets the shared PDO database connection.
        $pdo = DB::getConnection();
        // Reads the workflow_state JSON for this application.
        $stmt = $pdo->prepare('SELECT workflow_state FROM applications WHERE application_id = :id LIMIT 1');
        // Runs the query with the application id bound.
        $stmt->execute(['id' => $applicationId]);
        // Fetches the application row.
        $row = $stmt->fetch();
        // Stops when no application row was found.
        if (!$row) {
            // Returns because there is no row to update.
            return;
        // Closes the missing-row check.
        }

        // Decodes the stored workflow state JSON into an array.
        $wf = json_decode((string) ($row['workflow_state'] ?? '[]'), true);
        // Falls back to an empty array when the JSON is not usable.
        $wf = is_array($wf) ? $wf : [];

        // Only writes the presentation status when the schedule sends one.
        if (array_key_exists('status', $schedule)) {
            // Stores 'confirmed' for confirmed or completed, otherwise 'pending'.
            $wf['presentation_status'] = in_array((string) $schedule['status'], ['confirmed', 'completed'], true) ? 'confirmed' : 'pending';
        // Closes the status check.
        }
        // Only writes the date when the schedule has a non-empty date.
        if (!empty($schedule['date'])) {
            // Normalises the submitted date to Y-m-d.
            $ymd = scheduleDateYMD((string) $schedule['date']);
            // Checks that the date could be understood.
            if ($ymd !== null) {
                // Saves the date in the display format 'M j, Y'.
                $wf['presentation_date'] = date('M j, Y', strtotime($ymd));
            // Closes the parsed-date check.
            }
        // Closes the date check.
        }
        // Only writes the time when the schedule has a non-empty time.
        if (!empty($schedule['time'])) {
            // Normalises the submitted time to H:i:s.
            $hms = scheduleTimeHMS((string) $schedule['time']);
            // Checks that the time could be understood.
            if ($hms !== null) {
                // Saves the time in the display format 'g:i A'.
                $wf['presentation_time'] = date('g:i A', strtotime('2000-01-01 ' . $hms));
            // Closes the parsed-time check.
            }
        // Closes the time check.
        }
        // Writes the venue even when the coordinator cleared it.
        if (array_key_exists('venue', $schedule)) {
            // Saves the trimmed venue text in the workflow state.
            $wf['presentation_venue'] = trim((string) $schedule['venue']);
        // Closes the venue check.
        }
        // Only writes a panel when a non-empty panel text was sent.
        if (array_key_exists('panel', $schedule) && trim((string) $schedule['panel']) !== '') {
            // Saves the trimmed panel text in the workflow state.
            $wf['presentation_panel'] = trim((string) $schedule['panel']);
        // Closes the panel check.
        }
        // Defaults the presentation date key so it always exists.
        $wf['presentation_date'] = $wf['presentation_date'] ?? '';
        // Defaults the presentation time key so it always exists.
        $wf['presentation_time'] = $wf['presentation_time'] ?? '';
        // Defaults the presentation venue key so it always exists.
        $wf['presentation_venue'] = $wf['presentation_venue'] ?? '';
        // Defaults the presentation panel key so it always exists.
        $wf['presentation_panel'] = $wf['presentation_panel'] ?? '';

        // Writes the updated workflow state back to the applications table.
        $pdo->prepare('UPDATE applications SET workflow_state = :wf WHERE application_id = :id')
            // Binds the JSON text and the application id for the update.
            ->execute([
                // Encodes the workflow state without escaping unicode or slashes.
                'wf' => json_encode($wf, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                // Passes the application id used in the WHERE clause.
                'id' => $applicationId,
            // Closes the update parameters array.
            ]);
    // Catches any database error so scheduling still succeeds.
    } catch (Throwable $e) {
        // Logs the schedule sync error for the developer.
        error_log('[schedule sync] ' . $e->getMessage());
    // Closes the try/catch block.
    }
// Closes syncScheduleToApplication().
}

/**
 * Writes an application status directly, with an optional readiness flag.
 *
 * The coordinator pages use this for automatic transitions so every entry
 * point (application edit, schedule add/edit) stores the same status.
 */
// Sets the status of one application.
function setApplicationStatusRecord(int $applicationId, string $status, ?bool $readyForPresentation = null): void {
    // Ignores an invalid application id.
    if ($applicationId <= 0) {
        // Returns because there is nothing to update.
        return;
    // Closes the id guard.
    }

    // Updates the database row when the database layer is available.
    if (class_exists('DB')) {
        // Starts the guarded database update.
        try {
            // Builds the status update and its parameters.
            $sql = 'UPDATE applications SET status = :status';
            $params = ['status' => $status, 'id' => $applicationId];
            // Adds the readiness flag only when a value was supplied.
            if ($readyForPresentation !== null) {
                // Appends the readiness column to the update.
                $sql .= ', ready_for_presentation = :ready';
                // Binds the readiness flag as 1 or 0.
                $params['ready'] = $readyForPresentation ? 1 : 0;
            // Closes the readiness check.
            }
            // Finishes the WHERE clause.
            $sql .= ' WHERE application_id = :id';
            // Runs the status update.
            DB::getConnection()->prepare($sql)->execute($params);
        // Catches database errors so the caller can continue.
        } catch (Throwable $e) {
            // Logs the failed automatic status update.
            error_log('[application status sync] ' . $e->getMessage());
        // Closes the database error handler.
        }
    // Closes the database check.
    }

    // Also updates the fallback store when the application is kept there.
    $apps = storeGet('applications');
    // Loops over the stored applications.
    foreach ($apps as $index => $app) {
        // Skips applications with a different id.
        if ((int) ($app['id'] ?? 0) !== $applicationId) {
            // Continues to the next stored application.
            continue;
        // Closes the id check.
        }
        // Stores the new status.
        $apps[$index]['status'] = $status;
        // Stores the readiness flag when it was supplied.
        if ($readyForPresentation !== null) {
            // Saves the readiness value in the same shape the pages read.
            $apps[$index]['readyForPresentation'] = $readyForPresentation;
        // Closes the readiness check.
        }
        // Writes the updated list back to the store.
        storeSet('applications', $apps);
        // Stops after the matching row was updated.
        break;
    // Closes the loop over stored applications.
    }
// Ends setApplicationStatusRecord.
}

/**
 * Sets the status of the schedule that belongs to an application.
 *
 * Used when an application is completed (schedule becomes completed) and when
 * an approval is revoked (schedule returns to confirmed).
 */
// Sets the schedule status for one application.
function setScheduleStatusForApplication(int $applicationId, string $status): void {
    // Rejects an invalid id or status.
    if ($applicationId <= 0 || !in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
        // Returns because the request cannot be applied safely.
        return;
    // Closes the guard.
    }

    // Updates the database schedule when available.
    if (class_exists('DB')) {
        // Starts the guarded database update.
        try {
            // Updates the schedule row linked to the application.
            DB::getConnection()
                ->prepare('UPDATE schedules SET status = :status WHERE application_id = :application_id')
                ->execute(['status' => $status, 'application_id' => $applicationId]);
        // Catches database errors so the caller can continue.
        } catch (Throwable $e) {
            // Logs the failed schedule sync.
            error_log('[schedule status sync] ' . $e->getMessage());
        // Closes the database error handler.
        }
    // Closes the database check.
    }

    // Also updates the fallback schedule store.
    $schedules = storeGet('schedules');
    // Loops over the stored schedules.
    foreach ($schedules as $index => $schedule) {
        // Skips schedules that belong to another application.
        if ((int) ($schedule['applicationId'] ?? 0) !== $applicationId) {
            // Continues to the next schedule.
            continue;
        // Closes the application check.
        }
        // Stores the new schedule status.
        $schedules[$index]['status'] = $status;
        // Writes the updated list back to the store.
        storeSet('schedules', $schedules);
        // Stops after the matching schedule was updated.
        break;
    // Closes the loop over schedules.
    }
// Ends setScheduleStatusForApplication.
}

/**
 * Aligns the application status with a saved schedule.
 *
 * A confirmed schedule moves a ready stage to Scheduled for Presentation.
 * A pending schedule no longer keeps a stage scheduled. A schedule saved for
 * an already completed application stays completed.
 */
// Synchronizes the application status from its schedule.
function syncApplicationStatusFromSchedule(int $applicationId, string $scheduleStatus): void {
    // Skips an invalid application id.
    if ($applicationId <= 0) {
        // Returns because there is no row to synchronize.
        return;
    // Closes the id guard.
    }

    // Loads the current application row.
    $app = applicationById($applicationId);
    // Stops when the application cannot be found.
    if (!$app) {
        // Returns because there is nothing to update.
        return;
    // Closes the missing-application check.
    }

    // Reads the current status and result.
    $currentStatus = (string) ($app['status'] ?? '');
    $result = (string) ($app['result'] ?? '');

    // A finished application always keeps a completed schedule.
    if (in_array($currentStatus, ['completed', 'approved'], true)) {
        // Marks the schedule completed when the result was approved.
        if ($result === 'approved' && $scheduleStatus !== 'completed') {
            // Writes the completed schedule status.
            setScheduleStatusForApplication($applicationId, 'completed');
        // Closes the approved-result check.
        }
        // Stops because a finished stage is never moved back automatically.
        return;
    // Closes the finished-application check.
    }

    // Starts without a target status.
    $targetStatus = null;
    // Starts without a readiness change.
    $targetReady = null;

    // Confirmed and completed schedules are verified presentations.
    if (in_array($scheduleStatus, ['confirmed', 'completed'], true)) {
        // An approved result completes the stage.
        if ($result === 'approved') {
            // Targets the completed status.
            $targetStatus = 'completed';
            // Keeps readiness on record.
            $targetReady = true;
        // Otherwise the stage is scheduled once the schedule is verified.
        } elseif ($currentStatus !== 'scheduled') {
            // Targets the scheduled status.
            $targetStatus = 'scheduled';
            // Keeps readiness on record.
            $targetReady = true;
        // Closes the status branch.
        }
    // A pending or cancelled schedule no longer supports a scheduled status.
    } elseif ($currentStatus === 'scheduled') {
        // Returns to ready when the stage was already marked ready.
        $targetStatus = !empty($app['readyForPresentation']) ? 'ready_for_presentation' : 'under_review';
    // Closes the schedule status branch.
    }

    // Applies the target status when it differs from the stored one.
    if ($targetStatus !== null && $targetStatus !== $currentStatus) {
        // Saves the new status and readiness value.
        setApplicationStatusRecord($applicationId, $targetStatus, $targetReady);
    // Closes the status change check.
    }

    // A completed stage also completes its schedule.
    if ($targetStatus === 'completed') {
        // Writes the completed schedule status.
        setScheduleStatusForApplication($applicationId, 'completed');
    // Closes the completed-stage check.
    }
// Ends syncApplicationStatusFromSchedule.
}

// Adds a new presentation schedule and returns the saved record.
function addScheduleRecord(array $s): array {
    // Uses MySQL when a database exists and an application id is given.
    if (class_exists('DB') && !empty($s['applicationId'])) {
        // Gets the shared PDO connection.
        $pdo = DB::getConnection();
        // Finds the logged-in coordinator's user id.
        $coordinator = (int) ($_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0);
        // Checks whether no coordinator is logged in.
        if ($coordinator <= 0) {
            // Falls back to the first coordinator account in the users table.
            $coordinator = (int) ($pdo->query("SELECT user_id FROM users WHERE role = 'coordinator' ORDER BY user_id LIMIT 1")->fetchColumn() ?: 0);
        // Closes the missing-coordinator check.
        }
        // Stops when still no coordinator account was found.
        if ($coordinator <= 0) {
            // Throws an error because a schedule needs a managing coordinator.
            throw new RuntimeException('No coordinator account is available to manage the schedule.');
        // Closes the coordinator check.
        }
        // Reads and trims the submitted schedule date.
        $date = trim((string) ($s['date'] ?? ''));
        // Reads and trims the submitted start time.
        $time = trim((string) ($s['time'] ?? ''));
        // Normalises the date to Y-m-d for the database.
        $dateValue = scheduleDateYMD($date);
        // Normalises the time to H:i:s for the database.
        $timeValue = scheduleTimeHMS($time);
        // Checks whether the date or time could not be understood.
        if ($dateValue === null || $timeValue === null) {
            // Rejects the save with a clear invalid date or time error.
            throw new InvalidArgumentException('The schedule date or time is invalid.');
        // Closes the date and time validation.
        }
        // Looks up the adviser id by the adviser's name.
        $adviserStmt = $pdo->prepare('SELECT adviser_id FROM advisor_pool WHERE name = :name LIMIT 1');
        // Runs the adviser lookup with the submitted name.
        $adviserStmt->execute(['name' => trim((string) ($s['adviser'] ?? ''))]);
        // Stores the adviser id, or zero when no match was found.
        $adviserId = (int) ($adviserStmt->fetchColumn() ?: 0);
        // Splits the documentor name into last, first and middle parts.
        $documentorParts = documentorNameParts((string) ($s['documentor'] ?? ''));
        // Uses the first name, or null when the field is empty.
        $documentorFirst = $documentorParts['first'] !== '' ? $documentorParts['first'] : null;
        // Uses the middle name, or null when the field is empty.
        $documentorMiddle = $documentorParts['middle'] !== '' ? $documentorParts['middle'] : null;
        // Uses the surname as the required last-name value.
        $documentorLast = $documentorParts['last'];
        // Starts a transaction so the schedule save is all-or-nothing.
        $pdo->beginTransaction();
        // Starts the try block that can roll the transaction back.
        try {
            // Prepares the INSERT statement for the schedules table.
            $stmt = $pdo->prepare(
                // Starts the SQL text that inserts or updates the schedule row.
                'INSERT INTO schedules
                    (application_id, managed_by_user_id, presentation_date, start_time, venue, adviser_id,
                     documentor_first_name, documentor_middle_name, documentor_last_name, status)
                 VALUES
                    (:application_id, :managed_by_user_id, :presentation_date, :start_time, :venue, :adviser_id,
                     :documentor_first_name, :documentor_middle_name, :documentor_last_name, :status)
                 ON DUPLICATE KEY UPDATE
                    schedule_id = LAST_INSERT_ID(schedule_id),
                    managed_by_user_id = VALUES(managed_by_user_id),
                    presentation_date = VALUES(presentation_date),
                    start_time = VALUES(start_time),
                    venue = VALUES(venue),
                    adviser_id = VALUES(adviser_id),
                    documentor_first_name = VALUES(documentor_first_name),
                    documentor_middle_name = VALUES(documentor_middle_name),
                    documentor_last_name = VALUES(documentor_last_name),
                    status = VALUES(status)'
            // Closes the insert statement text.
            );
            // Binds every schedule column value for the insert.
            $stmt->execute([
                // Passes the application this schedule belongs to.
                'application_id' => (int) $s['applicationId'],
                // Passes the coordinator who manages this schedule.
                'managed_by_user_id' => $coordinator,
                // Passes the normalised presentation date.
                'presentation_date' => $dateValue,
                // Passes the normalised start time.
                'start_time' => $timeValue,
                // Passes the trimmed venue text.
                'venue' => trim((string) ($s['venue'] ?? '')),
                // Passes the adviser id, or null when no adviser matched.
                'adviser_id' => $adviserId > 0 ? $adviserId : null,
                // Passes the documentor's first name.
                'documentor_first_name' => $documentorFirst,
                // Passes the documentor's middle name.
                'documentor_middle_name' => $documentorMiddle,
                // Passes the documentor's surname, or null when empty.
                'documentor_last_name' => $documentorLast !== '' ? $documentorLast : null,
                // Stores 'confirmed' only when the schedule was confirmed.
                'status' => ($s['status'] ?? 'pending') === 'confirmed' ? 'confirmed' : 'pending',
            // Closes the execute parameter array.
            ]);
            // Reads the schedule id of the inserted or updated row.
            $scheduleId = (int) $pdo->lastInsertId();
            // Checks whether MySQL did not return a schedule id.
            if ($scheduleId <= 0) {
                // Prepares a lookup of the schedule id for this application.
                $scheduleStmt = $pdo->prepare('SELECT schedule_id FROM schedules WHERE application_id = :application_id LIMIT 1');
                // Runs the schedule id lookup.
                $scheduleStmt->execute(['application_id' => (int) $s['applicationId']]);
                // Reads the found schedule id, or zero when missing.
                $scheduleId = (int) ($scheduleStmt->fetchColumn() ?: 0);
            // Closes the missing schedule id check.
            }
            // Checks again that a usable schedule id exists.
            if ($scheduleId <= 0) {
                // Throws an error because the saved row cannot be found.
                throw new RuntimeException('The schedule could not be loaded after saving.');
            // Closes the schedule id check.
            }
            // Removes the old panel assignments before saving the new ones.
            $pdo->prepare('DELETE FROM schedule_panel_assignments WHERE schedule_id = :schedule_id')
                // Runs the delete for this schedule id.
                ->execute(['schedule_id' => $scheduleId]);
            // Prepares the insert that links panel members to the schedule.
            $assignmentStmt = $pdo->prepare('INSERT IGNORE INTO schedule_panel_assignments (schedule_id, panel_member_id, panel_role) VALUES (:schedule_id, :panel_member_id, :panel_role)');
            // Reads the panel text submitted by the coordinator.
            $panelText = (string) ($s['panel'] ?? '');
            // Loads every panel member so names can be matched.
            $panelRows = $pdo->query('SELECT panel_member_id, first_name, middle_name, last_name FROM panel_members')->fetchAll();
            // Loops over each panel member row.
            foreach ($panelRows as $panelRow) {
                // Builds the member's displayed name as 'Last, First Middle'.
                $panelName = trim($panelRow['last_name'] . ', ' . $panelRow['first_name'] . (!empty($panelRow['middle_name']) ? ' ' . $panelRow['middle_name'] : ''));
                // Skips members whose name is not in the panel text.
                if ($panelName === '' || !str_contains($panelText, $panelName)) {
                    // Continues to the next panel member.
                    continue;
                // Closes the name match check.
                }
                // Reads the panel member id as an integer.
                $memberId = (int) $panelRow['panel_member_id'];
                // Checks that the panel member id is valid.
                if ($memberId > 0) {
                    // Links this panel member to the schedule as a member.
                    $assignmentStmt->execute(['schedule_id' => $scheduleId, 'panel_member_id' => $memberId, 'panel_role' => 'member']);
                // Closes the valid member id check.
                }
            // Ends the panel member loop.
            }
            // Starts the note about keeping the tracker in step with the schedule.
            // Keep the Milestone Tracker in step with the schedule that was
            // Explains that a confirmed schedule verifies the presentation step.
            // just saved (Confirmed -> "Presentation Scheduled" verified).
            // Syncs the saved schedule into the application's workflow state.
            syncScheduleToApplication((int) $s['applicationId'], [
                // Sends the normalised date to the sync.
                'date' => $dateValue,
                // Sends the normalised time to the sync.
                'time' => $timeValue,
                // Sends the trimmed venue to the sync.
                'venue' => trim((string) ($s['venue'] ?? '')),
                // Sends the panel text to the sync.
                'panel' => (string) ($s['panel'] ?? ''),
                // Sends the confirmed or pending status to the sync.
                'status' => ($s['status'] ?? 'pending') === 'confirmed' ? 'confirmed' : 'pending',
            // Closes the sync data array.
            ]);
            // Aligns the application status with the saved schedule.
            syncApplicationStatusFromSchedule((int) $s['applicationId'], (string) ($s['status'] ?? 'pending'));
            // Commits the transaction now that everything saved.
            $pdo->commit();
            // Returns the saved schedule, or the input data with its id.
            return findSchedule((string) $scheduleId) ?: array_merge($s, ['id' => (string) $scheduleId]);
        // Catches an error so the transaction can be rolled back.
        } catch (Throwable $e) {
            // Undoes the partial save.
            $pdo->rollBack();
            // Rethrows the error for the caller to handle.
            throw $e;
        // Closes the try/catch block.
        }
    // Closes the database branch.
    }
    // Loads the JSON fallback schedule list.
    $list = storeGet('schedules');
    // Starts the fallback record with default values.
    $rec = array_merge([
        // Gives the fallback record a unique id.
        'id' => 'sch_' . uniqid(),
        // Defaults the student email to empty.
        'studentEmail' => '',
        // Defaults the student name to empty.
        'studentName' => '',
        // Defaults the application id to null.
        'applicationId' => null,
        // Defaults the stage label to empty.
        'stage' => '',
        // Defaults the date to empty.
        'date' => '',
        // Defaults the time to empty.
        'time' => '',
        // Defaults the venue to empty.
        'venue' => '',
        // Defaults the panel to TBD.
        'panel' => 'TBD',
        // Defaults the adviser to empty.
        'adviser' => '',
        // Defaults the documentor to empty.
        'documentor' => '',
        // Defaults the notifications list to empty.
        'notifications' => [],
        // Defaults the status to pending.
        'status' => 'pending',
        // Stamps the record with the current date and time.
        'createdAt' => date('c'),
    // Merges the submitted schedule fields over the defaults.
    ], $s);
    // Adds the new record to the stored schedule list.
    $list[] = $rec;
    // Saves the updated list back to storage.
    storeSet('schedules', $list);
    // Returns the new fallback record.
    return $rec;
// Closes addScheduleRecord().
}

// Updates an existing schedule and returns the changed record.
function updateScheduleRecord(string $id, array $patch): ?array {
    // Uses MySQL when available and the id is a numeric database id.
    if (class_exists('DB') && ctype_digit($id)) {
        // Starts the array of columns that will be updated.
        $data = [];
        // Only updates the date when the patch includes one.
        if (isset($patch['date'])) {
            // Normalises the new date to Y-m-d.
            $dateValue = scheduleDateYMD((string) $patch['date']);
            // Queues the new date when it was understood.
            if ($dateValue !== null) $data['presentation_date'] = $dateValue;
        // Closes the date check.
        }
        // Only updates the time when the patch includes one.
        if (isset($patch['time'])) {
            // Normalises the new time to H:i:s.
            $timeValue = scheduleTimeHMS((string) $patch['time']);
            // Queues the new time when it was understood.
            if ($timeValue !== null) $data['start_time'] = $timeValue;
        // Closes the time check.
        }
        // Queues the trimmed venue when it was sent.
        if (isset($patch['venue'])) $data['venue'] = trim((string) $patch['venue']);
        // Queues a valid schedule status when one was sent.
        if (isset($patch['status'])) {
            // Reads the requested status in lowercase.
            $requestedStatus = strtolower(trim((string) $patch['status']));
            // Accepts every status stored by the schedule table.
            $data['status'] = in_array($requestedStatus, ['pending', 'confirmed', 'completed', 'cancelled'], true)
                ? $requestedStatus
                : 'pending';
        // Closes the status check.
        }
        // Only looks up the adviser when the name was sent.
        if (isset($patch['adviser'])) {
            // Reads and trims the adviser name.
            $adviserName = trim((string) $patch['adviser']);
            // Checks that the adviser name is not empty.
            if ($adviserName !== '') {
                // Prepares the adviser id lookup by name.
                $adviserStmt = DB::getConnection()->prepare('SELECT adviser_id FROM advisor_pool WHERE name = :name LIMIT 1');
                // Runs the lookup with the adviser name.
                $adviserStmt->execute(['name' => $adviserName]);
                // Queues the adviser id, or null when no match was found.
                $data['adviser_id'] = (int) ($adviserStmt->fetchColumn() ?: 0) ?: null;
            // Closes the non-empty adviser check.
            }
        // Closes the adviser patch check.
        }
        // Only splits the documentor name when it was sent.
        if (isset($patch['documentor'])) {
            // Splits the name into last, first and middle parts.
            $documentorParts = documentorNameParts((string) $patch['documentor']);
            // Checks that a surname was typed.
            if ($documentorParts['last'] !== '') {
                // Queues the documentor's surname.
                $data['documentor_last_name'] = $documentorParts['last'];
                // Queues the first name, or null when empty.
                $data['documentor_first_name'] = $documentorParts['first'] !== '' ? $documentorParts['first'] : null;
                // Queues the middle name, or null when empty.
                $data['documentor_middle_name'] = $documentorParts['middle'] !== '' ? $documentorParts['middle'] : null;
            // Closes the surname check.
            }
        // Closes the documentor patch check.
        }
        // Writes the changed columns to the schedule row.
        if ($data) DB::update('schedules', $data, ['schedule_id' => (int) $id]);
        // Rebuilds the panel links only when a panel was sent.
        if (isset($patch['panel'])) {
            // Gets the shared PDO connection.
            $pdo = DB::getConnection();
            // Deletes the old panel assignments for this schedule.
            $pdo->prepare('DELETE FROM schedule_panel_assignments WHERE schedule_id = :id')->execute(['id' => (int) $id]);
            // Prepares the insert that adds panel members to the schedule.
            $assignmentStmt = $pdo->prepare('INSERT IGNORE INTO schedule_panel_assignments (schedule_id, panel_member_id, panel_role) VALUES (:schedule_id, :panel_member_id, "member")');
            // Reads the panel text from the patch.
            $panelText = (string) $patch['panel'];
            // Loads every panel member so names can be matched.
            $panelRows = $pdo->query('SELECT panel_member_id, first_name, middle_name, last_name FROM panel_members')->fetchAll();
            // Loops over each panel member row.
            foreach ($panelRows as $panelRow) {
                // Builds the member's displayed name as 'Last, First Middle'.
                $panelName = trim($panelRow['last_name'] . ', ' . $panelRow['first_name'] . (!empty($panelRow['middle_name']) ? ' ' . $panelRow['middle_name'] : ''));
                // Checks whether this member's name appears in the panel text.
                if ($panelName !== '' && str_contains($panelText, $panelName)) {
                    // Links the matching panel member to the schedule.
                    $assignmentStmt->execute(['schedule_id' => (int) $id, 'panel_member_id' => (int) $panelRow['panel_member_id']]);
                // Closes the name match check.
                }
            // Ends the panel member loop.
            }
        // Closes the panel patch check.
        }
        
        // Starts the note about syncing the workflow state with the schedule.
        // Keep the application's workflow state in step with the schedule for
        // Explains that a confirmed schedule verifies the presentation step.
        // the Milestone Tracker (Confirmed -> "Presentation Scheduled"
        // Notes that the schedule row is read back after an edit.
        // verified). The schedule row is read back so an edit that only moved
        // Notes that the coordinator's status is kept after the edit.
        // the date still keeps the status the coordinator sees.
        // Only syncs when the database connection class exists.
        if (class_exists('DB')) {
            // Starts the block that protects the schedule sync.
            try {
                // Gets the shared PDO connection.
                $pdo = DB::getConnection();
                // Reads the saved schedule row so the sync uses stored values.
                $sched = $pdo->prepare('SELECT application_id, presentation_date, start_time, venue, status FROM schedules WHERE schedule_id = :id LIMIT 1');
                // Runs the schedule lookup with the schedule id.
                $sched->execute(['id' => (int) $id]);
                // Fetches the schedule row.
                $scheduleRow = $sched->fetch();
                // Reads the application id linked to this schedule.
                $appId = (int) ($scheduleRow['application_id'] ?? 0);
                // Only syncs when the schedule belongs to an application.
                if ($appId > 0) {
                    // Builds the schedule data sent to the workflow sync.
                    $sync = [
                        // Uses the stored presentation date.
                        'date' => (string) ($scheduleRow['presentation_date'] ?? ''),
                        // Uses the stored start time.
                        'time' => (string) ($scheduleRow['start_time'] ?? ''),
                        // Uses the stored venue.
                        'venue' => (string) ($scheduleRow['venue'] ?? ''),
                        // Uses the stored status.
                        'status' => (string) ($scheduleRow['status'] ?? 'pending'),
                    // Closes the sync data array.
                    ];
                    // Adds the panel text when the patch included one.
                    if (isset($patch['panel']) && trim((string) $patch['panel']) !== '') {
                        // Stores the trimmed panel text for the sync.
                        $sync['panel'] = (string) $patch['panel'];
                    // Closes the panel check.
                    }
                    // Writes the schedule values into the application workflow state.
                    syncScheduleToApplication($appId, $sync);
                    // Aligns the application status with the saved schedule.
                    syncApplicationStatusFromSchedule($appId, (string) ($scheduleRow['status'] ?? 'pending'));
                // Closes the application check.
                }
            // Catches sync errors so the schedule update still succeeds.
            } catch (Throwable $e) {
                // Logs the schedule sync error for the developer.
                error_log('[schedule sync] ' . $e->getMessage());
            // Closes the try/catch block.
            }
        // Closes the database sync check.
        }
        
        // Returns the freshly loaded schedule record.
        return findSchedule($id);
    // Closes the database branch.
    }
    // Loads the JSON fallback schedule list.
    $list = storeGet('schedules');
    // Loops over stored schedules to find the matching id.
    foreach ($list as $i => $s) {
        // Skips schedules whose id does not match.
        if ((string) $s['id'] !== $id) {
            // Continues to the next stored schedule.
            continue;
        // Closes the id match check.
        }
        // Merges the patch values into the found schedule.
        $list[$i] = array_merge($s, $patch);
        // Saves the updated schedule list to storage.
        storeSet('schedules', $list);
        // Returns the updated fallback schedule.
        return $list[$i];
    // Closes the stored schedule loop.
    }
    // Returns null when no schedule had that id.
    return null;
// Closes updateScheduleRecord().
}

// Builds the list of panel names shown in the schedule form.
function panelSelectOptions(): array {
    // Starts the empty options list.
    $opts = [];
    // Loops over the panel members stored in the database.
    foreach (databasePanelMembers() as $p) {
        // Adds the member's name with their qualification in brackets.
        $opts[] = $p['name'] . ($p['qualification'] ? ' (' . $p['qualification'] . ')' : '');
    // Closes the panel member loop.
    }
    // Returns the database names, or sample names when none exist.
    return $opts ?: [
        // Sample panel option: Dr. Juan Dela Cruz.
        'Dr. Juan Dela Cruz (PhD in Computer Science)',
        // Sample panel option: Dr. Ana Reyes.
        'Dr. Ana Reyes (PhD in Information Technology)',
        // Sample panel option: Prof. Miguel Santos.
        'Prof. Miguel Santos (MS in Computer Science)',
        // Sample panel option: Prof. Lisa Fernandez.
        'Prof. Lisa Fernandez (MS in Information Technology)',
        // Sample panel option: Dr. Maria Santos.
        'Dr. Maria Santos (PhD in Computer Science)',
    // Closes the sample options array.
    ];
// Closes panelSelectOptions().
}
