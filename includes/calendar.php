<?php /* Starts the PHP block that defines the shared schedule calendar helpers. */ ?>
<?php
/**
 * Presentation Schedule Calendar
 * ---------------------------------------------------------------------------
 * Shared by coordinator/dashboard.php and coordinator/schedule/manage.php.
 *
 *   scheduleCalendarFetch()   Reads schedules from the database (data layer).
 *   renderScheduleCalendar()  Prints the calendar markup. The behaviour lives
 *                             in script.js, the styling in style.css.
 *
 * The browser never talks to the database. It calls
 * coordinator/schedule/calendar_data.php, which calls scheduleCalendarFetch().
 *
 * The `schedules` table is the source of truth, joined to `applications`
 * (paper title and stage) and `users` / `students` (student name).
 *
 * Timezone: dates are interpreted in SCHEDULE_CALENDAR_TIMEZONE. Define it in
 * includes/config.php before this file loads to override the default.
 */

// Only sets the default timezone when config.php has not defined it already.
if (!defined('SCHEDULE_CALENDAR_TIMEZONE')) {
    // Defines SCHEDULE_CALENDAR_TIMEZONE as Asia/Manila.
    define('SCHEDULE_CALENDAR_TIMEZONE', 'Asia/Manila');
// Closes the check that defines the timezone constant.
}

// Guards the timezone helper so it is declared only once.
if (!function_exists('scheduleCalendarTimezone')) {
    // Declares the function that returns the calendar's DateTimeZone object.
    function scheduleCalendarTimezone(): DateTimeZone
    // Opens the body of scheduleCalendarTimezone().
    {
        // Starts the attempt to build a timezone from the configured name.
        try {
            // Creates a DateTimeZone for the SCHEDULE_CALENDAR_TIMEZONE value.
            return new DateTimeZone(SCHEDULE_CALENDAR_TIMEZONE);
        // Catches an invalid timezone name so the page does not crash.
        } catch (Throwable $e) {
            // Falls back to Asia/Manila when the configured name is invalid.
            return new DateTimeZone('Asia/Manila');
        // Closes the catch block for the timezone fallback.
        }
    // Closes the scheduleCalendarTimezone() function.
    }
// Closes the guard that declares the timezone helper once.
}

/**
 * Returns the shared PDO connection, or opens one using the same fallback
 * the dashboard already uses. Returns null when the database is unreachable.
 */
// Guards the database helper so it is declared only once.
if (!function_exists('scheduleCalendarPdo')) {
    // Declares the helper that returns a PDO connection, or null on failure.
    function scheduleCalendarPdo(): ?PDO
    // Opens the body of scheduleCalendarPdo().
    {
        // Brings the shared $pdo connection into this function's scope.
        global $pdo;
        // Reuses the existing connection when one is already open.
        if (isset($pdo) && $pdo instanceof PDO) {
            // Returns the shared PDO connection right away.
            return $pdo;
        // Closes the reuse-existing-connection check.
        }
        // Starts the attempt to open a new database connection.
        try {
            // Reads DB_HOST, defaulting to localhost.
            $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
            // Reads DB_NAME, defaulting to the csite_grad_school database.
            $dbName = defined('DB_NAME') ? DB_NAME : 'csite_grad_school';
            // Reads DB_USER, defaulting to the MySQL root user.
            $dbUser = defined('DB_USER') ? DB_USER : 'root';
            // Reads DB_PASS, defaulting to an empty password.
            $dbPass = defined('DB_PASS') ? DB_PASS : '';
            // Opens a PDO connection to MySQL using utf8mb4 encoding.
            $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
                // Makes PDO throw exceptions when a database error happens.
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                // Makes queries return rows as associative arrays by default.
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Closes the PDO options array and the connection call.
            ]);
            // Returns the newly created PDO connection.
            return $pdo;
        // Catches connection errors so the calendar can fail gracefully.
        } catch (Throwable $e) {
            // Returns null when the database cannot be reached.
            return null;
        // Closes the catch block for the connection attempt.
        }
    // Closes the scheduleCalendarPdo() function.
    }
// Closes the guard around the database helper.
}

/** True when $value is a real calendar date written as YYYY-MM-DD. */
// Guards the date checker so it is declared only once.
if (!function_exists('scheduleCalendarIsDate')) {
    // Declares the helper that checks for a YYYY-MM-DD date string.
    function scheduleCalendarIsDate($value): bool
    // Opens the body of scheduleCalendarIsDate().
    {
        // Rejects values that are not text or do not look like YYYY-MM-DD.
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            // Reports the value as not a valid date.
            return false;
        // Closes the format check inside scheduleCalendarIsDate().
        }
        // Parses the text strictly as a year-month-day date.
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        // Confirms the parse worked and round-trips to the same text.
        return $dt !== false && $dt->format('Y-m-d') === $value;
    // Closes the scheduleCalendarIsDate() function.
    }
// Closes the guard around the date checker.
}

/** Parses stored schedule dates in ISO or legacy display format. */
// Guards the date parser so it is declared only once.
if (!function_exists('scheduleCalendarParseDate')) {
    // Declares the helper that turns stored date text into YYYY-MM-DD.
    function scheduleCalendarParseDate($value): ?string
    // Opens the body of scheduleCalendarParseDate().
    {
        // Converts the input to text and removes surrounding spaces.
        $value = trim((string) $value);
        // Tries each accepted date format: ISO, M j Y, and F j Y.
        foreach (['!Y-m-d', '!M j, Y', '!F j, Y'] as $format) {
            // Attempts to parse the value with the current format.
            $date = DateTimeImmutable::createFromFormat($format, $value);
            // Checks that the parse produced no warnings or errors.
            if (scheduleCalendarCleanParse($date)) {
                // Returns the date in the standard YYYY-MM-DD form.
                return $date->format('Y-m-d');
            // Closes the successful-parse branch.
            }
        // Closes the loop over the date formats.
        }
        // Returns null when no format matched.
        return null;
    // Closes the scheduleCalendarParseDate() function.
    }
// Closes the guard around the date parser.
}

/** Runs a prepared statement and returns every row; throws on failure. */
// Guards the query helper so it is declared only once.
if (!function_exists('scheduleCalendarQuery')) {
    // Declares the helper that runs a prepared query and returns all rows.
    function scheduleCalendarQuery(PDO $pdo, string $sql, array $params = []): array
    // Opens the body of scheduleCalendarQuery().
    {
        // Prepares the SQL statement so parameters can be bound safely.
        $stmt = $pdo->prepare($sql);
        // Stops when preparing or executing the statement fails.
        if (!$stmt || !$stmt->execute($params)) {
            // Throws an error so the caller knows the query failed.
            throw new RuntimeException('Schedule query failed.');
        // Closes the prepare/execute failure check.
        }
        // Returns all rows as arrays, or an empty array when there are none.
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    // Closes the scheduleCalendarQuery() function.
    }
// Closes the guard around the query helper.
}

/** True when a createFromFormat() result is a clean parse. */
// Guards the parse checker so it is declared only once.
if (!function_exists('scheduleCalendarCleanParse')) {
    // Declares the helper that reports whether a parsed date is valid.
    function scheduleCalendarCleanParse($dt): bool
    // Opens the body of scheduleCalendarCleanParse().
    {
        // Handles the case where parsing failed completely.
        if ($dt === false) {
            // Reports the parse as unsuccessful.
            return false;
        // Closes the parse-failed check.
        }
        // Reads the warning and error counts from the last parse.
        $errors = DateTimeImmutable::getLastErrors();
        // Starts the check that the last parse had no warnings or errors.
        return $errors === false
            // Accepts the parse only when both error counts are zero.
            || ((int) ($errors['warning_count'] ?? 0) === 0 && (int) ($errors['error_count'] ?? 0) === 0);
    // Closes the scheduleCalendarCleanParse() function.
    }
// Closes the guard around the parse checker.
}

/** Normalises '14:00:00', '2:00 AM', '14:00' ... to 'H:i', or null. */
// Guards the time parser so it is declared only once.
if (!function_exists('scheduleCalendarParseTime')) {
    // Declares the helper that normalises a stored time to H:i.
    function scheduleCalendarParseTime($raw): ?string
    // Opens the body of scheduleCalendarParseTime().
    {
        // Converts the input to text and trims surrounding spaces.
        $raw = trim((string) $raw);
        // Checks for an empty time value.
        if ($raw === '') {
            // Returns null when there is no time to parse.
            return null;
        // Closes the empty-time check.
        }
        // Tries each accepted time format such as 14:00:00 or 2:00 AM.
        foreach (['H:i:s', 'H:i', 'g:i A', 'g:iA', 'g:i a', 'g:ia', 'h:i A', 'h:iA'] as $format) {
            // Parses the time text using the current format.
            $dt = DateTimeImmutable::createFromFormat('!' . $format, $raw);
            // Keeps the result only when the parse was clean.
            if (scheduleCalendarCleanParse($dt)) {
                // Returns the time in 24-hour H:i form.
                return $dt->format('H:i');
            // Closes the successful-parse branch.
            }
        // Closes the loop over the time formats.
        }
        // Returns null when no time format matched.
        return null;
    // Closes the scheduleCalendarParseTime() function.
    }
// Closes the guard around the time parser.
}

/** '14:00' -> '2:00 PM' */
// Guards the time label helper so it is declared only once.
if (!function_exists('scheduleCalendarTimeLabel')) {
    // Declares the helper that converts H:i time to a 12-hour label.
    function scheduleCalendarTimeLabel(?string $hhmm): ?string
    // Opens the body of scheduleCalendarTimeLabel().
    {
        // Checks whether no time value was supplied.
        if ($hhmm === null) {
            // Returns null so the caller shows nothing for the time.
            return null;
        // Closes the missing-time check.
        }
        // Parses the H:i time text into a date object.
        $dt = DateTimeImmutable::createFromFormat('!H:i', $hhmm);
        // Returns a label like 2:00 PM, or null when parsing failed.
        return $dt ? $dt->format('g:i A') : null;
    // Closes the scheduleCalendarTimeLabel() function.
    }
// Closes the guard around the time label helper.
}

/** Minutes between two 'H:i' values -> '45 min', '1 hr 30 min', '2 hr'. */
// Guards the duration helper so it is declared only once.
if (!function_exists('scheduleCalendarDurationLabel')) {
    // Declares the helper that labels how long a presentation lasts.
    function scheduleCalendarDurationLabel(?string $start, ?string $end): ?string
    // Opens the body of scheduleCalendarDurationLabel().
    {
        // Checks that both a start and an end time exist.
        if ($start === null || $end === null) {
            // Returns null when a time is missing.
            return null;
        // Closes the missing-time check.
        }
        // Splits the start time into integer hours and minutes.
        [$sh, $sm] = array_map('intval', explode(':', $start));
        // Splits the end time into integer hours and minutes.
        [$eh, $em] = array_map('intval', explode(':', $end));
        // Works out the total minutes between start and end.
        $minutes = ($eh * 60 + $em) - ($sh * 60 + $sm);
        // Rejects zero or negative durations as bad data.
        if ($minutes <= 0) {
            // Returns null when the duration is not positive.
            return null;
        // Closes the non-positive duration check.
        }
        // Computes the whole hours in the duration.
        $hours = intdiv($minutes, 60);
        // Computes the leftover minutes after the whole hours.
        $rest  = $minutes % 60;
        // Handles durations shorter than one hour.
        if ($hours === 0) {
            // Returns a label such as 45 min.
            return $rest . ' min';
        // Closes the under-one-hour branch.
        }
        // Returns labels like 2 hr or 1 hr 30 min.
        return $hours . ' hr' . ($rest ? ' ' . $rest . ' min' : '');
    // Closes the scheduleCalendarDurationLabel() function.
    }
// Closes the guard around the duration helper.
}

/**
 * Normalises a presentation status for display.
 *
 * The dashboard and the Presentation Schedule Calendar share this mapping, so a
 * pending presentation always reads "Pending" on both. `schedules.status` is an
 * enum (pending, confirmed, completed, cancelled); the workflow fallback may
 * store the same words with different casing or a legacy spelling.
 *
 * @return array{key: string, label: string}
 */
// Guards the status helper so it is declared only once.
if (!function_exists('scheduleCalendarStatus')) {
    // Declares the helper that normalises a schedule status for display.
    function scheduleCalendarStatus($status): array
    // Opens the body of scheduleCalendarStatus().
    {
        // Lowercases and trims the stored status text.
        $raw = strtolower(trim((string) $status));
        // Starts the map of legacy status spellings to standard keys.
        $aliases = [
            // Treats unconfirmed as pending.
            'unconfirmed' => 'pending',
            // Treats tentative as pending.
            'tentative'   => 'pending',
            // Treats awaiting as pending.
            'awaiting'    => 'pending',
            // Treats awaiting_confirmation as pending.
            'awaiting_confirmation' => 'pending',
            // Treats approved as confirmed.
            'approved'    => 'confirmed',
            // Treats done as completed.
            'done'        => 'completed',
            // Treats the American spelling canceled as cancelled.
            'canceled'    => 'cancelled',
        // Closes the alias map.
        ];
        // Replaces a legacy spelling with its standard key, else keeps the text.
        $key = $aliases[$raw] ?? $raw;
        // Checks for an empty status key.
        if ($key === '') {
            // Treats an empty status as pending.
            $key = 'pending';
        // Closes the empty-status check.
        }
        // Starts the map of status keys to display labels.
        $labels = [
            // Labels pending as Pending.
            'pending'   => 'Pending',
            // Labels confirmed as Confirmed.
            'confirmed' => 'Confirmed',
            // Labels completed as Completed.
            'completed' => 'Completed',
            // Labels cancelled as Cancelled.
            'cancelled' => 'Cancelled',
        // Closes the label map.
        ];
        // Strips unsafe characters from the key used in CSS class names.
        $safeKey = preg_replace('/[^a-z0-9_-]/', '', $key) ?: 'pending';

        // Returns the normalised key and label as an array.
        return [
            // Returns the CSS-safe status key.
            'key'   => $safeKey,
            // Returns the display label, building one when the key is unknown.
            'label' => $labels[$key] ?? ucwords(str_replace(['_', '-'], ' ', $key)),
        // Closes the returned array.
        ];
    // Closes the scheduleCalendarStatus() function.
    }
// Closes the guard around the status helper.
}

/** Builds one calendar event in the shape the front end expects. */
// Guards the event builder so it is declared only once.
if (!function_exists('scheduleCalendarEvent')) {
    // Declares the helper that builds one calendar event array.
    function scheduleCalendarEvent(string $id, int $applicationId, $student, $title, $stage, $venue, string $status, ?string $start, ?string $end): array
    // Opens the body of scheduleCalendarEvent().
    {
        // Checks for an end time at or before the start time.
        if ($end !== null && $start !== null && $end <= $start) {
            // Discards the bad end time so no wrong duration is shown.
            $end = null; // an end time at or before the start is bad data
        // Closes the bad end time check.
        }
        // Normalises the status into its key and label.
        $statusMeta = scheduleCalendarStatus($status);
        // Starts the event array handed to the front end.
        return [
            // Stores the unique event id.
            'id'            => $id,
            // Stores the application id the event belongs to.
            'applicationId' => $applicationId,
            // Stores the trimmed student name.
            'student'       => trim((string) $student),
            // Stores the trimmed paper title.
            'title'         => trim((string) $title),
            // Stores the trimmed presentation stage.
            'stage'         => trim((string) $stage),
            // Stores the trimmed venue.
            'venue'         => trim((string) $venue),
            // Stores the normalised status key.
            'status'        => $statusMeta['key'],
            // Stores the status key again for script compatibility.
            'statusKey'     => $statusMeta['key'],
            // Stores the readable status label.
            'statusLabel'   => $statusMeta['label'],
            // Stores the start time in H:i form.
            'start'         => $start,
            // Stores the end time in H:i form.
            'end'           => $end,
            // Stores the start time as a 12-hour label.
            'startLabel'    => scheduleCalendarTimeLabel($start),
            // Stores the end time as a 12-hour label.
            'endLabel'      => scheduleCalendarTimeLabel($end),
            // Stores the duration label between start and end.
            'duration'      => scheduleCalendarDurationLabel($start, $end),
        // Closes the event array.
        ];
    // Closes the scheduleCalendarEvent() function.
    }
// Closes the guard around the event builder.
}

/**
 * Loads every presentation between $start and $end (inclusive, YYYY-MM-DD).
 *
 * @return array{dates: array<string, array<int, array>>, signature: string}
 *         dates maps 'YYYY-MM-DD' to the presentations on that day, earliest first.
 *         signature changes whenever the returned data changes (used for polling).
 */
// Guards the fetch helper so it is declared only once.
if (!function_exists('scheduleCalendarFetch')) {
    // Declares the helper that loads presentations between two dates.
    function scheduleCalendarFetch(PDO $pdo, string $start, string $end): array
    // Opens the body of scheduleCalendarFetch().
    {
        // Comment: the name expression matches the other coordinator pages.
        // Same 'Last, First MI' name format used across the coordinator pages.
        // Starts the SQL name snippet: Last, First MI, falling back to the user name.
        $nameSql = "COALESCE(
                        NULLIF(TRIM(CONCAT(s.last_name, ', ', s.first_name, IF(s.middle_initial IS NULL OR TRIM(REPLACE(s.middle_initial, '.', '')) = '', '', CONCAT(' ', TRIM(REPLACE(s.middle_initial, '.', '')))))), ''),
                        u.full_name
                    )";

        // Starts the map from date to that day's presentations.
        $byDate = [];
        // Tracks which presentations were already added, to remove duplicates.
        $seen   = [];
        // Remembers applications already listed from the schedules table.
        $scheduledApplications = [];
        // Defines a helper that adds an event only once per app, date and time.
        $add = static function (string $date, array $event) use (&$byDate, &$seen): void {
            // Comment: one presentation means one application at one date and time.
            // One presentation = one application at one date and time.
            // Builds a unique key from application id, date and start time.
            $key = $event['applicationId'] . '|' . $date . '|' . ($event['start'] ?? '');
            // Checks whether this presentation was already added.
            if (isset($seen[$key])) {
                // Skips the duplicate event.
                return;
            // Closes the duplicate check.
            }
            // Marks this presentation as already added.
            $seen[$key] = true;
            // Adds the event to the list for its date.
            $byDate[$date][] = $event;
        // Closes the anonymous add-event helper.
        };

        // Comment: the schedules table is the main data source.
        // 1) Primary source: the schedules table.
        // Runs the query that loads saved schedule rows.
        $rows = scheduleCalendarQuery(
            // Passes the database connection into the query helper.
            $pdo,
            // Starts the SQL that joins schedules, applications, users and students.
            "SELECT sc.schedule_id, sc.application_id, sc.presentation_date, sc.start_time, sc.end_time,
                    sc.venue, sc.status AS schedule_status,
                    a.paper_title, a.presentation_stage,
                    {$nameSql} AS student_name
             FROM schedules sc
             JOIN applications a ON a.application_id = sc.application_id
             JOIN users u ON u.user_id = a.user_id
             LEFT JOIN students s ON s.user_id = u.user_id
             WHERE a.archived_at IS NULL
               AND sc.status <> 'cancelled'
               AND sc.presentation_date BETWEEN :start AND :end
             ORDER BY sc.presentation_date ASC, sc.start_time ASC, sc.schedule_id ASC",
            // Binds the start and end date values to the query.
            [':start' => $start, ':end' => $end]
        // Closes the schedules query call.
        );
        // Loops over each schedule row returned.
        foreach ($rows as $row) {
            // Reads the presentation date as text.
            $date = (string) $row['presentation_date'];
            // Checks that the stored date is a real YYYY-MM-DD date.
            if (!scheduleCalendarIsDate($date)) {
                // Skips rows with an unusable date.
                continue;
            // Closes the date check.
            }
            // Records the application as already scheduled.
            $scheduledApplications[(int) $row['application_id']] = true;
            // Adds a calendar event built from this schedule row.
            $add($date, scheduleCalendarEvent(
                // Uses the schedule id as the event id.
                'sch_' . (int) $row['schedule_id'],
                // Passes the application id linked to the schedule.
                (int) $row['application_id'],
                // Passes the student's full name.
                $row['student_name'],
                // Passes the paper title.
                $row['paper_title'],
                // Passes the workflow stage of the presentation.
                $row['presentation_stage'],
                // Passes the presentation venue.
                $row['venue'],
                // Passes the schedule status text.
                (string) $row['schedule_status'],
                // Converts the stored start time to H:i.
                scheduleCalendarParseTime($row['start_time']),
                // Converts the stored end time to H:i.
                scheduleCalendarParseTime($row['end_time'])
            // Closes the event builder and the add call.
            ));
        // Closes the loop over schedule rows.
        }

        // Comment: older workflow-only schedules stay visible for now.
        // Keep older workflow-only schedules visible while the schedule table
        // Comment: the schedules table still decides for saved records.
        // remains the source of truth for newly saved or edited records.
        // Runs the fallback query for applications with no schedule row.
        $workflowRows = scheduleCalendarQuery(
            // Passes the database connection into the query helper.
            $pdo,
            // Starts the SQL that reads scheduling data from applications.
            "SELECT a.application_id, a.workflow_state, a.paper_title, a.presentation_stage,
                    {$nameSql} AS student_name
             FROM applications a
             JOIN users u ON u.user_id = a.user_id
             LEFT JOIN students s ON s.user_id = u.user_id
             WHERE a.archived_at IS NULL
               AND a.status = 'scheduled'
               AND NOT EXISTS (
                    SELECT 1 FROM schedules sc WHERE sc.application_id = a.application_id
               )"
        // Closes the fallback query call.
        );
        // Loops over each workflow-only row.
        foreach ($workflowRows as $row) {
            // Casts the application id to a number.
            $applicationId = (int) $row['application_id'];
            // Skips bad ids and applications already shown from schedules.
            if ($applicationId <= 0 || isset($scheduledApplications[$applicationId])) {
                // Jumps to the next row.
                continue;
            // Closes the skip check.
            }
            // Decodes the stored workflow_state JSON into an array.
            $workflow = json_decode((string) ($row['workflow_state'] ?? ''), true);
            // Checks that the JSON decoded into a usable array.
            if (!is_array($workflow)) {
                // Skips rows whose workflow data is unreadable.
                continue;
            // Closes the JSON check.
            }
            // Reads and normalises the presentation date from the workflow.
            $date = scheduleCalendarParseDate($workflow['presentation_date'] ?? '');
            // Skips dates that are invalid or outside the requested range.
            if ($date === null || $date < $start || $date > $end) {
                // Jumps to the next row.
                continue;
            // Closes the date range check.
            }
            // Adds an event built from the workflow state.
            $add($date, scheduleCalendarEvent(
                // Uses the application id as the event id.
                'app_' . $applicationId,
                // Passes the application id to the event.
                $applicationId,
                // Passes the student's full name.
                $row['student_name'],
                // Passes the paper title.
                $row['paper_title'],
                // Passes the presentation stage.
                $row['presentation_stage'],
                // Passes the venue saved in the workflow, or empty text.
                $workflow['presentation_venue'] ?? '',
                // Passes the workflow status, defaulting to pending.
                (string) ($workflow['presentation_status'] ?? 'pending'),
                // Converts the workflow presentation time to H:i.
                scheduleCalendarParseTime($workflow['presentation_time'] ?? ''),
                // Passes null because the workflow has no end time.
                null
            // Closes the event builder and the add call.
            ));
        // Closes the loop over workflow-only rows.
        }

        // Comment: sorts each day's events with the earliest time first.
        // Earliest first on each day; presentations without a time go last.
        // Loops over every date that has events.
        foreach ($byDate as $date => $events) {
            // Sorts the day's events with a custom comparison.
            usort($events, static function (array $a, array $b): int {
                // Uses a late fake time for events without a start time.
                $ta = $a['start'] ?? '99:99';
                // Uses the same fake late time for the other event.
                $tb = $b['start'] ?? '99:99';
                // Compares start time, then student name, then event id.
                return [$ta, $a['student'], $a['id']] <=> [$tb, $b['student'], $b['id']];
            // Closes the sort comparison function.
            });
            // Stores the sorted list back under the date.
            $byDate[$date] = $events;
        // Closes the loop over dates.
        }
        // Sorts the date keys in ascending order.
        ksort($byDate);

        // Starts the result array returned to the caller.
        return [
            // Returns the events grouped by date.
            'dates'     => $byDate,
            // Builds an MD5 signature used by polling to detect changes.
            'signature' => md5((string) json_encode($byDate)),
        // Closes the result array.
        ];
    // Closes the scheduleCalendarFetch() function.
    }
// Closes the guard around the fetch helper.
}

/**
 * Prints the calendar. Call once per page, inside the page content.
 *
 * @param array $options  pollSeconds (int, default 30; 0 turns live refresh off)
 */
// Guards the render function so it is declared only once.
if (!function_exists('renderScheduleCalendar')) {
    // Declares the function that prints the calendar markup.
    function renderScheduleCalendar(array $options = []): void
    // Opens the body of renderScheduleCalendar().
    {
        // Keeps a counter so each calendar on a page gets a unique id.
        static $instance = 0;
        // Increments the counter for this calendar instance.
        $instance++;
        // Builds the unique id prefix used in the markup.
        $uid      = 'psc' . $instance;
        // Gets the timezone used for the calendar's today value.
        $tz       = scheduleCalendarTimezone();
        // Computes today's date in YYYY-MM-DD in the calendar timezone.
        $today    = (new DateTimeImmutable('now', $tz))->format('Y-m-d');
        // Finds the URL of the endpoint that supplies calendar data.
        $endpoint = function_exists('url') ? url('coordinator/schedule/calendar_data.php') : 'calendar_data.php';
        // Converts pollSeconds to milliseconds, defaulting to 30 seconds.
        $poll     = max(0, (int) ($options['pollSeconds'] ?? 30)) * 1000;
        // Defines a helper that escapes text for safe HTML output.
        $e        = static function ($value): string {
            // Escapes special characters as UTF-8 with quoted attributes.
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        // Closes the HTML escaping helper.
        };
        // Closes the PHP block so the calendar markup can be printed.
        ?>
<?php /* Opens the calendar section element flagged for JavaScript. */ ?>
<section class="psc" data-schedule-calendar
<?php /*          Stores the data endpoint URL for the script. */ ?>
         data-endpoint="<?= $e($endpoint) ?>"
<?php /*          Stores the timezone name for the script. */ ?>
         data-timezone="<?= $e($tz->getName()) ?>"
<?php /*          Stores today's date for the script. */ ?>
         data-today="<?= $e($today) ?>"
<?php /*          Stores the polling interval in milliseconds. */ ?>
         data-poll="<?= (int) $poll ?>"
<?php /*          Points the section at its title id for accessibility. */ ?>
         aria-labelledby="<?= $uid ?>-title">

<?php /*     Opens the calendar header block. */ ?>
    <header class="psc-header">
<?php /*         Opens the heading area that holds the title. */ ?>
        <div class="psc-heading">
<?php /*             Shows the calendar title text. */ ?>
            <h3 class="psc-title" id="<?= $uid ?>-title">Presentation Schedule Calendar</h3>
<?php /*             Shows the summary text that is replaced once data loads. */ ?>
            <p class="psc-summary" data-psc-summary aria-live="polite">Loading schedules…</p>
<?php /*         Closes the heading area. */ ?>
        </div>
<?php /*         Opens the legend list for the status colours. */ ?>
        <ul class="psc-legend" aria-label="Presentation status legend">
<?php /*             Shows the confirmed colour swatch in the legend. */ ?>
            <li><span class="psc-swatch psc-swatch-confirmed" aria-hidden="true"></span>Confirmed</li>
<?php /*             Shows the pending colour swatch in the legend. */ ?>
            <li><span class="psc-swatch psc-swatch-pending" aria-hidden="true"></span>Pending</li>
<?php /*         Closes the legend list. */ ?>
        </ul>
<?php /*     Closes the calendar header. */ ?>
    </header>

<?php /*     Opens the toolbar that holds month navigation. */ ?>
    <div class="psc-toolbar">
<?php /*         Opens the previous and next month controls. */ ?>
        <div class="psc-nav">
<?php /*             Prints the button that moves to the previous month. */ ?>
            <button type="button" class="psc-nav-btn" data-psc-prev aria-label="Previous month"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
<?php /*             Prints the heading where the current month name appears. */ ?>
            <h4 class="psc-month" data-psc-month aria-live="polite"></h4>
<?php /*             Prints the button that moves to the next month. */ ?>
            <button type="button" class="psc-nav-btn" data-psc-next aria-label="Next month"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
<?php /*         Closes the month navigation group. */ ?>
        </div>
<?php /*         Prints the button that jumps back to today. */ ?>
        <button type="button" class="psc-today-btn" data-psc-today>Today</button>
<?php /*     Closes the toolbar. */ ?>
    </div>

<?php /*     Prints the loading progress bar container. */ ?>
    <div class="psc-progress" data-psc-progress aria-hidden="true"></div>

<?php /*     Opens the hidden error alert box. */ ?>
    <div class="psc-alert" data-psc-alert role="alert" hidden>
<?php /*         Shows the warning icon inside the alert. */ ?>
        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
<?php /*         Shows the error message when loading fails. */ ?>
        <span data-psc-alert-msg>We couldn’t load the schedule. Please try again.</span>
<?php /*         Prints the button that retries loading the schedule. */ ?>
        <button type="button" class="psc-alert-btn" data-psc-retry>Try again</button>
<?php /*     Closes the error alert box. */ ?>
    </div>

<?php /*     Opens the grid container that will hold the calendar. */ ?>
    <div class="psc-calendar" role="grid" aria-labelledby="<?= $uid ?>-title" aria-busy="true">
<?php /*         Opens the row of weekday headers. */ ?>
        <div class="psc-weekdays" role="row">
<?php /*             Loops over the weekday short and long names. */ ?>
            <?php foreach (['Sun' => 'Sunday', 'Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 'Thu' => 'Thursday', 'Fri' => 'Friday', 'Sat' => 'Saturday'] as $short => $long): ?>
<?php /*             Prints one weekday column header. */ ?>
            <div class="psc-weekday" role="columnheader" aria-label="<?= $long ?>"><?= $short ?></div>
<?php /*             Ends the weekday loop. */ ?>
            <?php endforeach; ?>
<?php /*         Closes the weekday header row. */ ?>
        </div>
<?php /*         Prints the empty container that JavaScript fills with weeks. */ ?>
        <div class="psc-weeks" data-psc-grid></div>
<?php /*     Closes the calendar grid container. */ ?>
    </div>

<?php /*     Opens the hidden overlay used for the details dialog. */ ?>
    <div class="psc-overlay" data-psc-overlay hidden>
<?php /*         Opens the dialog box that shows schedule details. */ ?>
        <div class="psc-dialog" role="dialog" aria-modal="true" aria-labelledby="<?= $uid ?>-dlg-title" aria-describedby="<?= $uid ?>-dlg-sub" tabindex="-1">
<?php /*             Opens the dialog header. */ ?>
            <header class="psc-dialog-head">
<?php /*                 Shows the calendar icon in the dialog header. */ ?>
                <span class="psc-dialog-icon" aria-hidden="true"><i class="fas fa-calendar-check"></i></span>
<?php /*                 Opens the area holding the dialog title and subtitle. */ ?>
                <div class="psc-dialog-heading">
<?php /*                     Prints an empty title that JavaScript fills in. */ ?>
                    <h3 class="psc-dialog-title" id="<?= $uid ?>-dlg-title" data-psc-dlg-title></h3>
<?php /*                     Prints an empty subtitle that JavaScript fills in. */ ?>
                    <p class="psc-dialog-sub" id="<?= $uid ?>-dlg-sub" data-psc-dlg-sub></p>
<?php /*                 Closes the dialog heading area. */ ?>
                </div>
<?php /*                 Prints the button that closes the dialog. */ ?>
                <button type="button" class="psc-close" data-psc-close aria-label="Close schedule details"><i class="fas fa-times" aria-hidden="true"></i></button>
<?php /*             Closes the dialog header. */ ?>
            </header>
<?php /*             Prints the empty container for the dialog details. */ ?>
            <div class="psc-dialog-body" data-psc-dlg-body></div>
<?php /*         Closes the dialog box. */ ?>
        </div>
<?php /*     Closes the overlay. */ ?>
    </div>
<?php /* Closes the calendar section. */ ?>
</section>
<?php /*         Reopens PHP after the calendar markup. */ ?>
        <?php
    // Closes the renderScheduleCalendar() function.
    }
// Closes the guard around the render function.
}