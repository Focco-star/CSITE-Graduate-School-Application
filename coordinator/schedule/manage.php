<?php /* Opens PHP mode so the server can run the schedule list logic. */ ?>
<?php
// Loads config.php, which holds site settings and the url() helper.
require_once __DIR__ . '/../../includes/config.php';
// Loads db.php, the DB class used for database queries.
require_once __DIR__ . '/../../includes/db.php';
// Loads calendar.php, which draws the presentation calendar.
require_once __DIR__ . '/../../includes/calendar.php';

// Checks whether a session is already running.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so the logged-in coordinator is known.
    session_start();
// Closes the if that starts the session.
}

// Sets the page title shown in the browser tab.
$pageTitle   = 'Presentation Schedules';
// Tells the header this page belongs to the coordinator role.
$role        = 'coordinator';
// Marks the sidebar item for schedules as the active one.
$currentPage = 'schedule';
// Takes the coordinator's display name for the header greeting.
$userName    = $mockCoordinator['name'];

// Only handle a delete request when a delete id was sent.
if (isset($_GET['delete']) && $_GET['delete'] !== '') {
    // Starts a try block so a delete error does not break the page.
    try {
        // Deletes the schedule row with the id from the query string.
        deleteScheduleRecord((string) $_GET['delete']);
        // Shows a success message saying the schedule was removed.
        setFlash('success', 'Presentation schedule removed from the list and calendar.');
    // Catches any error raised while deleting the schedule.
    } catch (Throwable $e) {
        // Logs the delete error to the server error log.
        error_log('[schedule delete] ' . $e->getMessage());
        // Shows an error message telling the coordinator to try again.
        setFlash('error', 'The presentation schedule could not be removed. Please try again.');
    // Closes the try/catch around the delete.
    }
    // Goes back to the same list so the delete id leaves the URL.
    redirectTo('coordinator/schedule/manage.php');
// Closes the if that handled a delete request.
}

// Loads all schedule rows through the databaseSchedules() helper.
$schedules = databaseSchedules();

// ---------------------------------------------------------------------------
// Upcoming presentations (moved here from coordinator/dashboard.php)
// ---------------------------------------------------------------------------
// Only build a PDO connection when none exists yet.
if (!isset($pdo) || !($pdo instanceof PDO)) {
    // Starts a try block for creating the fallback connection.
    try {
        // Reads the database host from config, defaulting to localhost.
        $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
        // Reads the database name, defaulting to csite_grad_school.
        $dbName = defined('DB_NAME') ? DB_NAME : 'csite_grad_school';
        // Reads the database user from config, defaulting to root.
        $dbUser = defined('DB_USER') ? DB_USER : 'root';
        // Reads the database password from config, defaulting to blank.
        $dbPass = defined('DB_PASS') ? DB_PASS : '';
        // Opens a new PDO connection to MySQL with UTF-8 support.
        $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
            // Makes PDO throw exceptions so errors are easy to catch.
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Returns rows as associative arrays keyed by column name.
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Closes the PDO options array and the constructor call.
        ]);
    // Catches a connection failure such as a wrong password.
    } catch (PDOException $e) {
        // Sets $pdo to null so the page knows there is no connection.
        $pdo = null;
    // Closes the try/catch around the connection.
    }
// Closes the if that created the connection.
}

// Starts the presentation list from the schedule rows, or empty.
$upcomingPresentations = is_array($schedules) ? $schedules : [];

// Merge database-backed scheduled applications (schedule/add.php records the
// presentation date/time/venue/panel in applications.workflow_state), so the
// list also works across sessions where the session-only schedule list is
// empty. Existing rows win on duplicates (same application).
// Only merge database rows when a connection is available.
if ($pdo) {
    // Starts a try block around the scheduled presentations query.
    try {
        // Holds the prepared statement for the scheduled applications query.
        $scheduledStmt = $pdo->prepare(
            // Prepares a query listing applications whose status is scheduled.
            "SELECT a.application_id AS applicationId,
                    COALESCE(
                        NULLIF(TRIM(CONCAT(s.last_name, ', ', s.first_name, IF(s.middle_initial IS NULL OR TRIM(REPLACE(s.middle_initial, '.', '')) = '', '', CONCAT(' ', TRIM(REPLACE(s.middle_initial, '.', '')))))), ''),
                        u.full_name
                    ) AS studentName,
                    u.email AS studentEmail,
                    a.presentation_stage AS stage,
                    a.workflow_state AS workflowState,
                    a.status
             FROM applications a
             JOIN users u ON u.user_id = a.user_id
             LEFT JOIN students s ON s.user_id = u.user_id
             WHERE a.archived_at IS NULL AND a.status = 'scheduled'"
        // Closes the prepare call after the SQL text ends.
        );
        // Runs the prepared query against the database.
        $scheduledStmt->execute();

        // Starts an empty list of application ids already in the list.
        $coveredAppIds = [];
        // Loops over the schedules loaded earlier.
        foreach ($upcomingPresentations as $existing) {
            // Reads the application id from either key used by the rows.
            $candidate = (int) ($existing['applicationId'] ?? $existing['application_id'] ?? 0);
            // Only record ids that are real positive numbers.
            if ($candidate > 0) {
                // Marks this application id as already covered.
                $coveredAppIds[$candidate] = true;
            // Closes the if that checked the application id.
            }
        // Closes the loop over the existing schedule rows.
        }

        // Loops over each scheduled application row from the database.
        foreach ($scheduledStmt->fetchAll() as $row) {
            // Reads the application id from the row as an integer.
            $appId = (int) ($row['applicationId'] ?? 0);
            // Skips rows with no id, or ids already in the list.
            if ($appId <= 0 || isset($coveredAppIds[$appId])) {
                // Jumps to the next row so no duplicate is added.
                continue;
            // Closes the if that skipped duplicates or empty ids.
            }
            // Decodes the stored workflow state JSON, or uses an empty array.
            $wf = json_decode((string) ($row['workflowState'] ?? ''), true) ?: [];
            // Skips rows whose workflow state has no presentation date.
            if (empty($wf['presentation_date'])) {
                // Jumps to the next row when there is no date to show.
                continue;
            // Closes the if that checked the presentation date.
            }
            // Adds a new presentation entry built from this database row.
            $upcomingPresentations[] = [
                // Builds an id prefixed with db_ so the row is treated as database only.
                'id'            => 'db_' . $appId,
                // Keeps the real application id for linking and lookups.
                'applicationId' => $appId,
                // Passes the student email from the users table.
                'studentEmail'  => $row['studentEmail'] ?? '',
                // Passes the student name built from the students table.
                'studentName'   => $row['studentName'] ?? '',
                // Passes the presentation stage stored on the application.
                'stage'         => $row['stage'] ?? '',
                // Reads the presentation date saved in the workflow state.
                'date'          => (string) ($wf['presentation_date'] ?? ''),
                // Reads the presentation time saved in the workflow state.
                'time'          => (string) ($wf['presentation_time'] ?? ''),
                // Reads the presentation venue saved in the workflow state.
                'venue'         => (string) ($wf['presentation_venue'] ?? ''),
                // Reads the panel text, defaulting to TBD.
                'panel'         => (string) ($wf['presentation_panel'] ?? 'TBD'),
                // Marks the entry confirmed only when the workflow says so.
                'status'        => (($wf['presentation_status'] ?? '') === 'confirmed') ? 'confirmed' : 'pending',
            // Closes the array of values for the new entry.
            ];
        // Closes the loop over the scheduled application rows.
        }
    // Catches any query error so the page still renders.
    } catch (Throwable $e) {
        // Note: keeps whatever rows we have and still shows the page.
        // Keep whatever we have; the page still renders.
    // Closes the try/catch around the merge query.
    }
// Closes the if that ran the merge query.
}

// Sorts the presentation list by date using a compare function.
usort($upcomingPresentations, static function ($a, $b) {
    // Converts the first schedule's date text into a timestamp.
    $ta = strtotime((string) ($a['date'] ?? ''));
    // Converts the second schedule's date text into a timestamp.
    $tb = strtotime((string) ($b['date'] ?? ''));
    // Only compare by time when both dates are readable.
    if ($ta && $tb) {
        // Returns the order so the earlier presentation comes first.
        return $ta <=> $tb;
    // Closes the if that compared two readable dates.
    }
    // Falls back so readable dates come before unreadable ones.
    return $ta ? -1 : ($tb ? 1 : 0);
// Closes the sorting compare function.
});
// Puts the sorted merged list back into $schedules.
$schedules = $upcomingPresentations;

/*
 * The Academic Assessment Team of every schedule, and one group per student.
 * A student may have a schedule for more than one stage, so the entries are
 * grouped by student and then ordered by the stage sequence of that student's
 * track instead of being left as one flat, easily confused list.
 */
// Starts an empty cache of student presentation details per email.
$profileCache = [];
// Starts an empty cache of student profiles per email.
$detailsCache = [];
// Loops over each schedule with its index in the list.
foreach ($schedules as $index => $sch) {
    // Lowercases and trims the schedule's student email.
    $email = strtolower(trim((string) ($sch['studentEmail'] ?? '')));
    // Turns the stage label into a stable stage key.
    $stageKey = stageKeyFromLabel((string) ($sch['stage'] ?? ''));

    // Only load the student's presentation details once per email.
    if ($email !== '' && !array_key_exists($email, $detailsCache)) {
        // Caches the details so the lookup is not repeated.
        $detailsCache[$email] = studentPresentationDetails($email);
    // Closes the if that loaded presentation details.
    }
    // Only load the student's profile once per email.
    if ($email !== '' && !array_key_exists($email, $profileCache)) {
        // Caches the profile for the other rows of the same student.
        $profileCache[$email] = studentProfileForEmail($email);
    // Closes the if that loaded the profile.
    }
    // Gets the team data for this student and stage, or null.
    $team = $email !== '' ? ($detailsCache[$email][$stageKey] ?? null) : null;

    // Note (1 of 2): panel names come from the assignment rows.
    // Panel members come from the assignment rows, which keep first and last
    // Note (2 of 2): those rows keep last and first name separately.
    // names separately ("Dela Cruz, Juan"), unlike the joined panel string.
    // Only fetch panel rows when none are set and the id is numeric.
    if (empty($schedules[$index]['panelMembers']) && ctype_digit((string) ($sch['id'] ?? ''))) {
        // Loads the panel member rows assigned to this schedule.
        $members = databaseSchedulePanelMembersForSchedule((int) $sch['id']);
        // Only use them when at least one member was found.
        if ($members) {
            // Starts an array_map over the assigned panel member rows.
            $schedules[$index]['panelMembers'] = array_values(array_map(
                // Maps each panel row to its name text.
                static fn($member) => (string) $member['name'],
                // Passes the panel rows into array_map.
                $members
            // Closes array_values and ends the assignment.
            ));
        // Closes the if that found panel member rows.
        }
    // Closes the if that looked the panel members up in the database.
    }
    // Falls back to the team panel when no panel rows were assigned.
    if (empty($schedules[$index]['panelMembers']) && $team && !empty($team['panel'])) {
        // Starts an array_map over the team panel members.
        $schedules[$index]['panelMembers'] = array_values(array_map(
            // Maps each team panel member to its name text.
            static fn($member) => (string) $member['name'],
            // Passes the team panel array into array_map.
            $team['panel']
        // Closes array_values and ends the assignment.
        ));
    // Closes the if that used the team panel fallback.
    }
    // Only fill the adviser when the schedule has none.
    if (empty($schedules[$index]['adviser'])) {
        // Uses the adviser from the presentation details team.
        $schedules[$index]['adviser'] = (string) ($team['adviser'] ?? '');
    // Closes the if that filled the adviser from the team.
    }
    // Only fill the adviser when it is still empty.
    if (empty($schedules[$index]['adviser'])) {
        // Falls back to the adviser stored on the student profile.
        $schedules[$index]['adviser'] = (string) ($profileCache[$email]['adviser'] ?? '');
    // Closes the if that used the profile adviser.
    }
    // Only fill the documenter when the schedule has none.
    if (empty($schedules[$index]['documentor'])) {
        // Uses the documenter from the presentation details team.
        $schedules[$index]['documentor'] = (string) ($team['documentor'] ?? '');
    // Closes the if that filled the documenter.
    }
    // Stores the stage key on the row for later sorting.
    $schedules[$index]['stageKey'] = $stageKey;
    // Stores the student profile on the row for the view.
    $schedules[$index]['profile'] = $profileCache[$email] ?? null;
// Closes the loop over the schedules.
}

// Starts an empty list of groups, one per student.
$scheduleGroups = [];
// Loops over every schedule to put it in a student group.
foreach ($schedules as $sch) {
    // Builds the group key from the student email.
    $groupKey = strtolower(trim((string) ($sch['studentEmail'] ?? '')));
    // If there is no email, use the student name instead.
    if ($groupKey === '') {
        // Builds a name-based key using lowercased text.
        $groupKey = 'name:' . strtolower(trim((string) ($sch['studentName'] ?? '')));
    // Closes the if that fell back to a name key.
    }
    // Only create the group the first time this key is seen.
    if (!isset($scheduleGroups[$groupKey])) {
        // Starts the group with the student's name, email, program and track.
        $scheduleGroups[$groupKey] = [
            // Uses the student name, or the older student field, or blank text.
            'name' => (string) ($sch['studentName'] ?? $sch['student'] ?? ''),
            // Uses the student email, or blank text.
            'email' => (string) ($sch['studentEmail'] ?? ''),
            // Uses the program from the profile, or blank text.
            'program' => (string) ($sch['profile']['program'] ?? ''),
            // Uses the track from the profile, or blank text.
            'track' => (string) ($sch['profile']['track'] ?? ''),
            // Starts an empty list of schedules for this student.
            'schedules' => [],
        // Closes the group array.
        ];
    // Closes the if that created a new group.
    }
    // Adds this schedule to its student group.
    $scheduleGroups[$groupKey]['schedules'][] = $sch;
// Closes the loop that grouped the schedules.
}

// Loops over the groups by reference so they can be sorted.
foreach ($scheduleGroups as &$group) {
    // Uses the group track, or works it out from the program.
    $track = $group['track'] !== '' ? $group['track'] : getTrackForProgram($group['program']);
    // Starts an empty map of stage label to stage order.
    $stageOrder = [];
    // Loops over the track's workflow stages with their position.
    foreach (getWorkflow($track)['stages'] as $stageIndex => $stage) {
        // Stores the stage label with its order number.
        $stageOrder[(string) $stage['label']] = $stageIndex;
    // Closes the loop over the workflow stages.
    }
    // Sorts this group's schedules by stage order and then date.
    usort($group['schedules'], static function ($a, $b) use ($stageOrder) {
        // Reads the first schedule's stage order, defaulting to 99.
        $ia = $stageOrder[(string) ($a['stage'] ?? '')] ?? 99;
        // Reads the second schedule's stage order, defaulting to 99.
        $ib = $stageOrder[(string) ($b['stage'] ?? '')] ?? 99;
        // Only compare by date when both stages have the same order.
        if ($ia !== $ib) {
            // Returns the stage order comparison.
            return $ia <=> $ib;
        // Closes the if that compared the stage order.
        }
        // Falls back to comparing the presentation dates.
        return strtotime((string) ($a['date'] ?? '')) <=> strtotime((string) ($b['date'] ?? ''));
    // Closes the sorting compare function.
    });
// Closes the loop over the student groups.
}
// Removes the reference so the last group cannot be changed by mistake.
unset($group);

// Sorts the student groups alphabetically by student name.
usort($scheduleGroups, static fn($a, $b) => strcasecmp($a['name'], $b['name']));
// Counts all schedules for the summary text.
$scheduleTotal = count($schedules);

// Loads the shared header, which prints the page top and sidebar.
require_once __DIR__ . '/../../includes/header.php';
// Closes PHP mode and switches back to HTML output.
?>

<?php /* Page header wrapper that groups the title and description. */ ?>
<div class="page-header">
<?php /*     Shows the heading 'Presentation Schedule Dashboard'. */ ?>
    <h2>Presentation Schedule Dashboard</h2>
<?php /*     Short description of what the coordinator can do here. */ ?>
    <p>View and manage scheduled capstone/thesis presentations.</p>
<?php /* Closes the page header wrapper. */ ?>
</div>

<?php /* Action row holding the schedule button. */ ?>
<div class="form-actions" style="border:none;padding:0;margin-bottom:1.5rem;">
<?php /*     Button link that opens the Schedule Presentation form. */ ?>
    <a href="<?= url('coordinator/schedule/add.php') ?>" class="btn btn-primary"><i class="fas fa-calendar-plus"></i> Schedule Presentation</a>
<?php /* Closes the action row. */ ?>
</div>

<?php /* Draws the shared calendar with the scheduled presentations. */ ?>
<?php renderScheduleCalendar(); ?>

<?php /* Starts a style block for the grouped schedule cards. */ ?>
<style>
<?php /* CSS note (1 of 2): schedules are grouped by student, one card per stage. */ ?>
/* Scheduled presentations: grouped by student, one card per stage, so two
<?php /*    CSS note (2 of 2): so two presentations of one student stay distinct. */ ?>
   presentations of the same student are always distinguishable. */
<?php /* Styles the small summary text above the groups. */ ?>
.psched-summary { font-size: 0.78rem; color: var(--gray-500); }
<?php /* Lays the student groups out in a vertical flex column. */ ?>
.psched-groups { display: flex; flex-direction: column; gap: 1.25rem; }
<?php /* Gives each student group a border and rounded corners. */ ?>
.psched-group { border: 1px solid var(--gray-200); border-radius: var(--radius-lg); overflow: hidden; }
<?php /* Styles the group header row with a light background. */ ?>
.psched-group-head { display: flex; align-items: center; gap: 0.75rem; padding: 0.85rem 1.1rem; background: var(--gray-50); border-bottom: 1px solid var(--gray-200); }
<?php /* Draws the round avatar circle for the student group. */ ?>
.psched-avatar { display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; flex-shrink: 0; border-radius: 50%; background: rgba(6, 2, 151, 0.08); color: var(--adzu-navy); }
<?php /* Lets the group title take the free space and shrink when needed. */ ?>
.psched-group-title { flex: 1; min-width: 0; }
<?php /* Styles the student name heading in the group header. */ ?>
.psched-group-title h4 { margin: 0; font-size: 0.95rem; color: var(--adzu-navy); }
<?php /* Styles the small program and email line under the name. */ ?>
.psched-group-title p { margin: 0.15rem 0 0; font-size: 0.78rem; color: var(--gray-500); }
<?php /* Styles the pill showing how many presentations a student has. */ ?>
.psched-count { flex-shrink: 0; padding: 0.2rem 0.6rem; border: 1px solid rgba(37, 99, 235, 0.16); border-radius: 999px; background: rgba(37, 99, 235, 0.08); font-size: 0.72rem; font-weight: 600; color: var(--info); white-space: nowrap; }
<?php /* Lays the entries out in a responsive auto-fit grid. */ ?>
.psched-entries { display: grid; gap: 0.85rem; padding: 1rem 1.1rem; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }
<?php /* Styles one presentation card as a vertical flex box. */ ?>
.psched-entry { display: flex; flex-direction: column; gap: 0.6rem; padding: 0.9rem; border: 1px solid var(--gray-200); border-radius: var(--radius); background: var(--white); }
<?php /* Puts the stage name and status badge on one row. */ ?>
.psched-entry-head { display: flex; align-items: center; justify-content: space-between; gap: 0.6rem; }
<?php /* Styles the stage name as a bold label. */ ?>
.psched-stage { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; font-weight: 700; color: var(--adzu-navy); }
<?php /* Colours the stage icon blue. */ ?>
.psched-stage i { color: var(--info); }
<?php /* Lays the date, time and venue in a wrapping row. */ ?>
.psched-when { display: flex; flex-wrap: wrap; gap: 0.4rem 1rem; font-size: 0.8rem; color: var(--gray-600); }
<?php /* Adds spacing and colour to the small date and time icons. */ ?>
.psched-when i { margin-right: 4px; color: var(--info); }
<?php /* Lays the team details out in two columns. */ ?>
.psched-team { display: grid; grid-template-columns: 1fr 1fr; gap: 0.55rem 1rem; padding-top: 0.6rem; border-top: 1px dashed var(--gray-200); }
<?php /* Styles one team entry as a small stacked block. */ ?>
.psched-person { display: flex; flex-direction: column; gap: 0.15rem; min-width: 0; font-size: 0.82rem; color: var(--gray-700); }
<?php /* Makes the panel members row span the full width. */ ?>
.psched-person.is-wide { grid-column: 1 / -1; }
<?php /* Styles the small uppercase team labels. */ ?>
.psched-label { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: var(--gray-400); }
<?php /* Styles the italic 'Not assigned' placeholder text. */ ?>
.psched-none { color: var(--gray-400); font-style: italic; }
<?php /* Lays the panel member chips out in a wrapping row. */ ?>
.psched-chips { display: flex; flex-wrap: wrap; gap: 0.35rem; }
<?php /* Styles one panel member chip with a border and background. */ ?>
.psched-chip { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.16rem 0.55rem; border: 1px solid var(--gray-200); border-radius: 999px; background: var(--gray-50); font-size: 0.76rem; color: var(--gray-700); }
<?php /* Styles the small person icon inside a chip. */ ?>
.psched-chip i { font-size: 0.66rem; color: var(--gray-400); }
<?php /* Puts the edit and delete buttons in a row at the bottom. */ ?>
.psched-actions { display: flex; align-items: center; gap: 0.5rem; margin-top: auto; padding-top: 0.5rem; }
<?php /* Styles the empty message shown when nothing is scheduled. */ ?>
.psched-empty { margin: 0; padding: 2rem 1rem; text-align: center; color: var(--gray-400); }
<?php /* On small screens, drops the team grid to a single column. */ ?>
@media (max-width: 640px) { .psched-team { grid-template-columns: 1fr; } }
<?php /* Closes the style block. */ ?>
</style>

<?php /* Main card that holds the whole schedule dashboard. */ ?>
<div class="card">
<?php /*     Card header with the list title and the count summary. */ ?>
    <div class="card-header">
<?php /*         Heading with a calendar icon for the list. */ ?>
        <h3><i class="fas fa-calendar-alt"></i> Scheduled Presentations</h3>
<?php /*         Shows the number of schedules and the number of students. */ ?>
        <span class="psched-summary"><?= (int) $scheduleTotal ?> schedule<?= $scheduleTotal === 1 ? '' : 's' ?> · <?= count($scheduleGroups) ?> student<?= count($scheduleGroups) === 1 ? '' : 's' ?></span>
<?php /*     Closes the card header. */ ?>
    </div>
<?php /*     Card body wrapper for the grouped list. */ ?>
    <div class="card-body">
<?php /*         If no groups exist, show the empty message below. */ ?>
        <?php if (!$scheduleGroups): ?>
<?php /*         Empty message saying no presentations are scheduled yet. */ ?>
        <p class="psched-empty">No presentations scheduled yet.</p>
<?php /*         Else branch: groups exist, so draw the list. */ ?>
        <?php else: ?>
<?php /*         Wrapper that stacks the student groups. */ ?>
        <div class="psched-groups">
<?php /*             Loops over each student group. */ ?>
            <?php foreach ($scheduleGroups as $group): ?>
<?php /*             Section element for one student's schedules. */ ?>
            <section class="psched-group">
<?php /*                 Header row for the student group. */ ?>
                <header class="psched-group-head">
<?php /*                     Round avatar holding a graduate icon. */ ?>
                    <span class="psched-avatar"><i class="fas fa-user-graduate"></i></span>
<?php /*                     Wrapper for the student name and program line. */ ?>
                    <div class="psched-group-title">
<?php /*                         Shows the student name, escaped for safety. */ ?>
                        <h4><?= htmlspecialchars($group['name']) ?></h4>
<?php /*                         Shows the program code and email, or a fallback text. */ ?>
                        <p><?= htmlspecialchars(($group['program'] !== '' ? $group['program'] : 'Program not set') . ($group['email'] !== '' ? ' · ' . $group['email'] : '')) ?></p>
<?php /*                     Closes the title wrapper. */ ?>
                    </div>
<?php /*                     Pill showing how many presentations this student has. */ ?>
                    <span class="psched-count"><?= count($group['schedules']) ?> presentation<?= count($group['schedules']) === 1 ? '' : 's' ?></span>
<?php /*                 Closes the group header. */ ?>
                </header>
<?php /*                 Grid wrapper that holds one card per presentation. */ ?>
                <div class="psched-entries">
<?php /*                     Loops over each schedule in this group and prepares values. */ ?>
                    <?php foreach ($group['schedules'] as $sch):
                        // Trims the adviser name for display.
                        $adviser = trim((string) ($sch['adviser'] ?? ''));
                        // Trims the documenter name for display.
                        $documentor = trim((string) ($sch['documentor'] ?? ''));
                        // Removes the qualification in brackets from the documenter name.
                        $documentor = trim(preg_replace('/\s*\([^)]*\)/', ' ', $documentor) ?? '');
                        // Reads the panel member names, or an empty array.
                        $panelMembers = $sch['panelMembers'] ?? [];
                        // Reads the joined panel text as a fallback.
                        $panelFallback = trim((string) ($sch['panel'] ?? ''));
                        // Reads the raw date text from the schedule.
                        $dateRaw = (string) ($sch['date'] ?? '');
                        // Formats the date as 'Mon d, Y', or a dash when it is empty.
                        $dateLabel = $dateRaw !== '' ? date('M j, Y', strtotime($dateRaw)) : '—';
                        // Reads the raw time text from the schedule.
                        $timeRaw = (string) ($sch['time'] ?? '');
                        // Formats the time as a 12-hour clock, or a dash when it is empty.
                        $timeLabel = $timeRaw !== '' ? date('g:i A', strtotime('2000-01-01 ' . $timeRaw)) : '—';
                    // Closes the PHP block that prepared the display values.
                    ?>
<?php /*                     Article element for one presentation card. */ ?>
                    <article class="psched-entry">
<?php /*                         Top row holding the stage name and the status badge. */ ?>
                        <div class="psched-entry-head">
<?php /*                             Shows the stage name with a layered icon. */ ?>
                            <span class="psched-stage"><i class="fas fa-layer-group"></i> <?= htmlspecialchars((string) $sch['stage']) ?></span>
<?php /*                             Status badge showing confirmed or pending. */ ?>
                            <?= statusBadge((string) $sch['status']) ?>
<?php /*                         Closes the top row. */ ?>
                        </div>
<?php /*                         Row holding the date, time and venue. */ ?>
                        <div class="psched-when">
<?php /*                             Shows the presentation date with a calendar icon. */ ?>
                            <span><i class="fas fa-calendar-day"></i><?= htmlspecialchars($dateLabel) ?></span>
<?php /*                             Shows the presentation time with a clock icon. */ ?>
                            <span><i class="fas fa-clock"></i><?= htmlspecialchars($timeLabel) ?></span>
<?php /*                             Only show the venue line when a venue is set. */ ?>
                            <?php if (!empty($sch['venue'])): ?>
<?php /*                             Shows the venue with a location icon. */ ?>
                            <span><i class="fas fa-map-marker-alt"></i><?= htmlspecialchars((string) $sch['venue']) ?></span>
<?php /*                             Closes the if that showed the venue. */ ?>
                            <?php endif; ?>
<?php /*                         Closes the date, time and venue row. */ ?>
                        </div>
<?php /*                         Grid holding the adviser, documenter and panel details. */ ?>
                        <div class="psched-team">
<?php /*                             Block for the adviser entry. */ ?>
                            <div class="psched-person">
<?php /*                                 Small label reading 'Adviser'. */ ?>
                                <span class="psched-label">Adviser</span>
<?php /*                                 Shows the adviser name, or 'Not assigned' when it is empty. */ ?>
                                <span><?= $adviser !== '' ? htmlspecialchars($adviser) : '<span class="psched-none">Not assigned</span>' ?></span>
<?php /*                             Closes the adviser block. */ ?>
                            </div>
<?php /*                             Block for the documenter entry. */ ?>
                            <div class="psched-person">
<?php /*                                 Small label reading 'Documenter'. */ ?>
                                <span class="psched-label">Documenter</span>
<?php /*                                 Shows the documenter name, or 'Not assigned' when it is empty. */ ?>
                                <span><?= $documentor !== '' ? htmlspecialchars($documentor) : '<span class="psched-none">Not assigned</span>' ?></span>
<?php /*                             Closes the documenter block. */ ?>
                            </div>
<?php /*                             Block for the panel members, spanning the full width. */ ?>
                            <div class="psched-person is-wide">
<?php /*                                 Small label reading 'Panel Members'. */ ?>
                                <span class="psched-label">Panel Members</span>
<?php /*                                 Only show chips when panel member names exist. */ ?>
                                <?php if ($panelMembers): ?>
<?php /*                                 Wrapper for the panel member chips. */ ?>
                                <span class="psched-chips">
<?php /*                                     Loops over each panel member name. */ ?>
                                    <?php foreach ($panelMembers as $member): ?>
<?php /*                                     Shows one chip with a person icon and the member name. */ ?>
                                    <span class="psched-chip"><i class="fas fa-user"></i> <?= htmlspecialchars((string) $member) ?></span>
<?php /*                                     Closes the panel member loop. */ ?>
                                    <?php endforeach; ?>
<?php /*                                 Closes the chip wrapper. */ ?>
                                </span>
<?php /*                                 Else, use the joined panel text when it is not TBD. */ ?>
                                <?php elseif ($panelFallback !== '' && strcasecmp($panelFallback, 'TBD') !== 0): ?>
<?php /*                                 Shows the joined panel text as plain text. */ ?>
                                <span><?= htmlspecialchars($panelFallback) ?></span>
<?php /*                                 Else branch: there is no panel information at all. */ ?>
                                <?php else: ?>
<?php /*                                 Shows 'Not assigned' for the panel members. */ ?>
                                <span class="psched-none">Not assigned</span>
<?php /*                                 Closes the if/else that picked the panel display. */ ?>
                                <?php endif; ?>
<?php /*                             Closes the panel block. */ ?>
                            </div>
<?php /*                         Closes the team grid. */ ?>
                        </div>
<?php /*                         Row holding the action buttons. */ ?>
                        <div class="psched-actions">
<?php /*                             Only show Edit for real schedule rows, not database-only ones. */ ?>
                            <?php if (strpos((string) ($sch['id'] ?? ''), 'db_') !== 0): ?>
<?php /*                             Edit button linking to the edit form for this schedule id. */ ?>
                            <a href="<?= url('coordinator/schedule/edit.php?id=' . urlencode((string) $sch['id'])) ?>" class="btn btn-primary coord-edit-btn" title="Edit schedule"><i class="fas fa-pen-to-square" aria-hidden="true"></i> Edit</a>
<?php /*                             Delete link with a confirm question for a real schedule row. */ ?>
                            <?= coordDeleteLink(url('coordinator/schedule/manage.php?delete=' . urlencode((string) $sch['id'])), 'Delete this schedule?') ?>
<?php /*                             Else branch: this entry came straight from the applications table. */ ?>
                            <?php else: ?>
<?php /*                             Delete link that removes it from the list and the calendar. */ ?>
                            <?= coordDeleteLink(url('coordinator/schedule/manage.php?delete=' . urlencode((string) $sch['id'])), 'Remove this presentation schedule from the list and calendar?') ?>
<?php /*                             Closes the if/else that chose the action links. */ ?>
                            <?php endif; ?>
<?php /*                         Closes the action row. */ ?>
                        </div>
<?php /*                     Closes the presentation card. */ ?>
                    </article>
<?php /*                     Closes the loop over the schedules in this group. */ ?>
                    <?php endforeach; ?>
<?php /*                 Closes the entries grid. */ ?>
                </div>
<?php /*             Closes the student group section. */ ?>
            </section>
<?php /*             Closes the loop over the student groups. */ ?>
            <?php endforeach; ?>
<?php /*         Closes the groups wrapper. */ ?>
        </div>
<?php /*         Closes the if/else that chose between the empty message and the list. */ ?>
        <?php endif; ?>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the main card. */ ?>
</div>

<?php /* Loads the shared footer, which prints closing tags and scripts. */ ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>