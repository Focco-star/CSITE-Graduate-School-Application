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
 * Data sources (no new tables):
 *   1. `schedules`      - the primary source (date, start/end time, venue, status),
 *                         joined to `applications` (paper title, stage) and to
 *                         `users` / `students` (student name).
 *   2. applications.workflow_state - fallback for applications that are
 *                         'scheduled' but have no `schedules` row yet. This is
 *                         the same fallback dashboard.php / manage.php already
 *                         use for the "Upcoming Presentations" count.
 *
 * Timezone: dates are interpreted in SCHEDULE_CALENDAR_TIMEZONE. Define it in
 * includes/config.php before this file loads to override the default.
 */

if (!defined('SCHEDULE_CALENDAR_TIMEZONE')) {
    define('SCHEDULE_CALENDAR_TIMEZONE', 'Asia/Manila');
}

if (!function_exists('scheduleCalendarTimezone')) {
    function scheduleCalendarTimezone(): DateTimeZone
    {
        try {
            return new DateTimeZone(SCHEDULE_CALENDAR_TIMEZONE);
        } catch (Throwable $e) {
            return new DateTimeZone('Asia/Manila');
        }
    }
}

/**
 * Returns the shared PDO connection, or opens one using the same fallback
 * the dashboard already uses. Returns null when the database is unreachable.
 */
if (!function_exists('scheduleCalendarPdo')) {
    function scheduleCalendarPdo(): ?PDO
    {
        global $pdo;
        if (isset($pdo) && $pdo instanceof PDO) {
            return $pdo;
        }
        try {
            $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
            $dbName = defined('DB_NAME') ? DB_NAME : 'csite_grad_school';
            $dbUser = defined('DB_USER') ? DB_USER : 'root';
            $dbPass = defined('DB_PASS') ? DB_PASS : '';
            $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            return $pdo;
        } catch (Throwable $e) {
            return null;
        }
    }
}

/** True when $value is a real calendar date written as YYYY-MM-DD. */
if (!function_exists('scheduleCalendarIsDate')) {
    function scheduleCalendarIsDate($value): bool
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $dt !== false && $dt->format('Y-m-d') === $value;
    }
}

/** Runs a prepared statement and returns every row; throws on failure. */
if (!function_exists('scheduleCalendarQuery')) {
    function scheduleCalendarQuery(PDO $pdo, string $sql, array $params = []): array
    {
        $stmt = $pdo->prepare($sql);
        if (!$stmt || !$stmt->execute($params)) {
            throw new RuntimeException('Schedule query failed.');
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

/** True when a createFromFormat() result is a clean parse. */
if (!function_exists('scheduleCalendarCleanParse')) {
    function scheduleCalendarCleanParse($dt): bool
    {
        if ($dt === false) {
            return false;
        }
        $errors = DateTimeImmutable::getLastErrors();
        return $errors === false
            || ((int) ($errors['warning_count'] ?? 0) === 0 && (int) ($errors['error_count'] ?? 0) === 0);
    }
}

/** Normalises '14:00:00', '2:00 AM', '14:00' ... to 'H:i', or null. */
if (!function_exists('scheduleCalendarParseTime')) {
    function scheduleCalendarParseTime($raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        foreach (['H:i:s', 'H:i', 'g:i A', 'g:iA', 'g:i a', 'g:ia', 'h:i A', 'h:iA'] as $format) {
            $dt = DateTimeImmutable::createFromFormat('!' . $format, $raw);
            if (scheduleCalendarCleanParse($dt)) {
                return $dt->format('H:i');
            }
        }
        return null;
    }
}

/** Normalises 'Oct 10, 2026', '2026-10-10', '10/10/2026' ... to 'Y-m-d', or null. */
if (!function_exists('scheduleCalendarParseDate')) {
    function scheduleCalendarParseDate($raw, DateTimeZone $tz): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        foreach (['Y-m-d', 'M j, Y', 'F j, Y', 'n/j/Y', 'j M Y', 'j F Y'] as $format) {
            $dt = DateTimeImmutable::createFromFormat('!' . $format, $raw, $tz);
            if (scheduleCalendarCleanParse($dt)) {
                $year = (int) $dt->format('Y');
                return ($year >= 2000 && $year <= 2100) ? $dt->format('Y-m-d') : null;
            }
        }
        return null;
    }
}

/** '14:00' -> '2:00 PM' */
if (!function_exists('scheduleCalendarTimeLabel')) {
    function scheduleCalendarTimeLabel(?string $hhmm): ?string
    {
        if ($hhmm === null) {
            return null;
        }
        $dt = DateTimeImmutable::createFromFormat('!H:i', $hhmm);
        return $dt ? $dt->format('g:i A') : null;
    }
}

/** Minutes between two 'H:i' values -> '45 min', '1 hr 30 min', '2 hr'. */
if (!function_exists('scheduleCalendarDurationLabel')) {
    function scheduleCalendarDurationLabel(?string $start, ?string $end): ?string
    {
        if ($start === null || $end === null) {
            return null;
        }
        [$sh, $sm] = array_map('intval', explode(':', $start));
        [$eh, $em] = array_map('intval', explode(':', $end));
        $minutes = ($eh * 60 + $em) - ($sh * 60 + $sm);
        if ($minutes <= 0) {
            return null;
        }
        $hours = intdiv($minutes, 60);
        $rest  = $minutes % 60;
        if ($hours === 0) {
            return $rest . ' min';
        }
        return $hours . ' hr' . ($rest ? ' ' . $rest . ' min' : '');
    }
}

/** Builds one calendar event in the shape the front end expects. */
if (!function_exists('scheduleCalendarEvent')) {
    function scheduleCalendarEvent(string $id, int $applicationId, $student, $title, $stage, $venue, string $status, ?string $start, ?string $end): array
    {
        if ($end !== null && $start !== null && $end <= $start) {
            $end = null; // an end time at or before the start is bad data
        }
        return [
            'id'            => $id,
            'applicationId' => $applicationId,
            'student'       => trim((string) $student),
            'title'         => trim((string) $title),
            'stage'         => trim((string) $stage),
            'venue'         => trim((string) $venue),
            'status'        => $status,
            'start'         => $start,
            'end'           => $end,
            'startLabel'    => scheduleCalendarTimeLabel($start),
            'endLabel'      => scheduleCalendarTimeLabel($end),
            'duration'      => scheduleCalendarDurationLabel($start, $end),
        ];
    }
}

/**
 * Loads every presentation between $start and $end (inclusive, YYYY-MM-DD).
 *
 * @return array{dates: array<string, array<int, array>>, signature: string}
 *         dates maps 'YYYY-MM-DD' to the presentations on that day, earliest first.
 *         signature changes whenever the returned data changes (used for polling).
 */
if (!function_exists('scheduleCalendarFetch')) {
    function scheduleCalendarFetch(PDO $pdo, string $start, string $end): array
    {
        $tz = scheduleCalendarTimezone();

        // Same 'Last, First MI' name format used across the coordinator pages.
        $nameSql = "COALESCE(
                        NULLIF(TRIM(CONCAT(s.last_name, ', ', s.first_name, IF(s.middle_initial IS NULL OR TRIM(REPLACE(s.middle_initial, '.', '')) = '', '', CONCAT(' ', TRIM(REPLACE(s.middle_initial, '.', '')))))), ''),
                        u.full_name
                    )";

        $byDate = [];
        $seen   = [];
        $add = static function (string $date, array $event) use (&$byDate, &$seen): void {
            // One presentation = one application at one date and time.
            $key = $event['applicationId'] . '|' . $date . '|' . ($event['start'] ?? '');
            if (isset($seen[$key])) {
                return;
            }
            $seen[$key] = true;
            $byDate[$date][] = $event;
        };

        // 1) Primary source: the schedules table.
        $rows = scheduleCalendarQuery(
            $pdo,
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
            [':start' => $start, ':end' => $end]
        );
        foreach ($rows as $row) {
            $date = (string) $row['presentation_date'];
            if (!scheduleCalendarIsDate($date)) {
                continue;
            }
            $add($date, scheduleCalendarEvent(
                'sch_' . (int) $row['schedule_id'],
                (int) $row['application_id'],
                $row['student_name'],
                $row['paper_title'],
                $row['presentation_stage'],
                $row['venue'],
                (string) $row['schedule_status'],
                scheduleCalendarParseTime($row['start_time']),
                scheduleCalendarParseTime($row['end_time'])
            ));
        }

        // 2) Fallback: 'scheduled' applications that have no schedules row at all.
        $covered = [];
        foreach (scheduleCalendarQuery($pdo, "SELECT DISTINCT application_id FROM schedules") as $row) {
            $covered[(int) $row['application_id']] = true;
        }
        $fallback = scheduleCalendarQuery(
            $pdo,
            "SELECT a.application_id, a.paper_title, a.presentation_stage, a.workflow_state,
                    {$nameSql} AS student_name
             FROM applications a
             JOIN users u ON u.user_id = a.user_id
             LEFT JOIN students s ON s.user_id = u.user_id
             WHERE a.archived_at IS NULL AND a.status = 'scheduled'
             ORDER BY a.application_id ASC"
        );
        foreach ($fallback as $row) {
            $applicationId = (int) $row['application_id'];
            if (isset($covered[$applicationId])) {
                continue;
            }
            $workflow = json_decode((string) ($row['workflow_state'] ?? ''), true);
            if (!is_array($workflow)) {
                continue;
            }
            $date = scheduleCalendarParseDate($workflow['presentation_date'] ?? '', $tz);
            if ($date === null || $date < $start || $date > $end) {
                continue;
            }
            $add($date, scheduleCalendarEvent(
                'app_' . $applicationId,
                $applicationId,
                $row['student_name'],
                $row['paper_title'],
                $row['presentation_stage'],
                $workflow['presentation_venue'] ?? '',
                'scheduled',
                scheduleCalendarParseTime($workflow['presentation_time'] ?? ''),
                null
            ));
        }

        // Earliest first on each day; presentations without a time go last.
        foreach ($byDate as $date => $events) {
            usort($events, static function (array $a, array $b): int {
                $ta = $a['start'] ?? '99:99';
                $tb = $b['start'] ?? '99:99';
                return [$ta, $a['student'], $a['id']] <=> [$tb, $b['student'], $b['id']];
            });
            $byDate[$date] = $events;
        }
        ksort($byDate);

        return [
            'dates'     => $byDate,
            'signature' => md5((string) json_encode($byDate)),
        ];
    }
}

/**
 * Prints the calendar. Call once per page, inside the page content.
 *
 * @param array $options  pollSeconds (int, default 30; 0 turns live refresh off)
 */
if (!function_exists('renderScheduleCalendar')) {
    function renderScheduleCalendar(array $options = []): void
    {
        static $instance = 0;
        $instance++;
        $uid      = 'psc' . $instance;
        $tz       = scheduleCalendarTimezone();
        $today    = (new DateTimeImmutable('now', $tz))->format('Y-m-d');
        $endpoint = function_exists('url') ? url('coordinator/schedule/calendar_data.php') : 'calendar_data.php';
        $poll     = max(0, (int) ($options['pollSeconds'] ?? 30)) * 1000;
        $e        = static function ($value): string {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };
        ?>
<section class="psc" data-schedule-calendar
         data-endpoint="<?= $e($endpoint) ?>"
         data-timezone="<?= $e($tz->getName()) ?>"
         data-today="<?= $e($today) ?>"
         data-poll="<?= (int) $poll ?>"
         aria-labelledby="<?= $uid ?>-title">

    <header class="psc-header">
        <div class="psc-heading">
            <h3 class="psc-title" id="<?= $uid ?>-title">Presentation Schedule Calendar</h3>
            <p class="psc-summary" data-psc-summary aria-live="polite">Loading schedules…</p>
        </div>
        <ul class="psc-legend" aria-label="Calendar legend">
            <li><span class="psc-swatch psc-swatch-assigned" aria-hidden="true"></span>Assigned Schedule</li>
            <li><span class="psc-swatch psc-swatch-empty" aria-hidden="true"></span>No Assigned Schedule</li>
        </ul>
    </header>

    <div class="psc-toolbar">
        <div class="psc-nav">
            <button type="button" class="psc-nav-btn" data-psc-prev aria-label="Previous month"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
            <h4 class="psc-month" data-psc-month aria-live="polite"></h4>
            <button type="button" class="psc-nav-btn" data-psc-next aria-label="Next month"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
        </div>
        <button type="button" class="psc-today-btn" data-psc-today>Today</button>
    </div>

    <div class="psc-progress" data-psc-progress aria-hidden="true"></div>

    <div class="psc-alert" data-psc-alert role="alert" hidden>
        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
        <span data-psc-alert-msg>We couldn’t load the schedule. Please try again.</span>
        <button type="button" class="psc-alert-btn" data-psc-retry>Try again</button>
    </div>

    <div class="psc-calendar" role="grid" aria-labelledby="<?= $uid ?>-title" aria-busy="true">
        <div class="psc-weekdays" role="row">
            <?php foreach (['Sun' => 'Sunday', 'Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 'Thu' => 'Thursday', 'Fri' => 'Friday', 'Sat' => 'Saturday'] as $short => $long): ?>
            <div class="psc-weekday" role="columnheader" aria-label="<?= $long ?>"><?= $short ?></div>
            <?php endforeach; ?>
        </div>
        <div class="psc-weeks" data-psc-grid></div>
    </div>

    <div class="psc-overlay" data-psc-overlay hidden>
        <div class="psc-dialog" role="dialog" aria-modal="true" aria-labelledby="<?= $uid ?>-dlg-title" aria-describedby="<?= $uid ?>-dlg-sub" tabindex="-1">
            <header class="psc-dialog-head">
                <span class="psc-dialog-icon" aria-hidden="true"><i class="fas fa-calendar-check"></i></span>
                <div class="psc-dialog-heading">
                    <h3 class="psc-dialog-title" id="<?= $uid ?>-dlg-title" data-psc-dlg-title></h3>
                    <p class="psc-dialog-sub" id="<?= $uid ?>-dlg-sub" data-psc-dlg-sub></p>
                </div>
                <button type="button" class="psc-close" data-psc-close aria-label="Close schedule details"><i class="fas fa-times" aria-hidden="true"></i></button>
            </header>
            <div class="psc-dialog-body" data-psc-dlg-body></div>
        </div>
    </div>
</section>
        <?php
    }
}