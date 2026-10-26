<?php /* Opens the PHP block for the coordinator application management page. */ ?>
<?php
// Loads the shared config file with app settings and helper functions.
require_once __DIR__ . '/../../includes/config.php';
// Loads the shared database connection code.
require_once __DIR__ . '/../../includes/db.php';

// Sets the page title shown in the browser tab.
$pageTitle   = 'Applications';
// Tells the layout that this page belongs to a coordinator.
$role        = 'coordinator';
// Marks the Applications menu item as active in the sidebar.
$currentPage = 'applications';
// Uses the logged-in coordinator name, or a mock name as fallback.
$userName    = !empty($_SESSION['user']['full_name']) ? $_SESSION['user']['full_name'] : ($mockCoordinator['name'] ?? 'Coordinator');

// Checks whether a working database connection already exists.
if (!isset($pdo) || !($pdo instanceof PDO)) {
    // Starts a guarded block that may throw a database error.
    try {
        // Reads the database host from config, defaulting to localhost.
        $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
        // Reads the database name from config, defaulting to csite_grad_school.
        $dbName = defined('DB_NAME') ? DB_NAME : 'csite_grad_school';
        // Reads the database user name from config, defaulting to root.
        $dbUser = defined('DB_USER') ? DB_USER : 'root';
        // Reads the database password from config, defaulting to empty.
        $dbPass = defined('DB_PASS') ? DB_PASS : '';
        // Builds a MySQL connection to the CSITE database using UTF-8.
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
            // Makes database errors throw exceptions so they can be caught.
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Returns query rows as associative arrays keyed by column name.
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Closes the options array and the PDO constructor call.
        ]);
    // Catches a failed database connection attempt.
    } catch (PDOException $e) {
        // Marks the connection as missing so the page can still render.
        $pdo = null;
    // Closes the catch block.
    }
// Closes the check that opens a database connection when needed.
}

// Runs the archive action when the archive form was submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_id'])) {
    // Reads the application id to archive and converts it to a number.
    $archiveId = (int) $_POST['archive_id'];
    // Only archives when the database is connected and the id is valid.
    if ($pdo && $archiveId > 0) {
        // Starts a guarded block for the archive update.
        try {
            // Sets archived_at to now so the application leaves the active list.
            $stmt = $pdo->prepare("UPDATE applications SET archived_at = NOW() WHERE application_id = :id AND archived_at IS NULL");
            // Runs the update with the posted application id.
            $stmt->execute(['id' => $archiveId]);
            // Checks whether the update actually changed a row.
            if ($stmt->rowCount() > 0) {
                // Shows a success message saying the application was archived.
                setFlash('success', 'Application archived successfully. The record is retained for reporting.');
            // Runs when the update changed no row at all.
            } else {
                // Shows an error saying the application may already be archived.
                setFlash('error', 'The application could not be archived. It may already be archived.');
            // Closes the else branch for the archive result.
            }
        // Catches archive failures such as a missing archived_at column.
        } catch (PDOException $e) {
            // Tells the coordinator to apply the database migration and retry.
            setFlash('error', 'The application could not be archived. Apply the database migration and try again.');
        // Closes the archive try/catch block.
        }
    // Falls back to a helper when no database connection is available.
    } elseif (function_exists('archiveApplicationRecord')) {
        // Archives the application through the demo helper function.
        archiveApplicationRecord($archiveId);
        // Shows a success message for the fallback archive.
        setFlash('success', 'Application archived successfully.');
    // Closes the fallback archive branch.
    }
    // Sends the browser back to the application list after archiving.
    redirectTo('coordinator/applications/manage.php');
// Closes the archive POST handling block.
}


// Runs the restore action when the restore form was submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_id'])) {
    // Reads the application id to restore as an integer.
    $restoreId = (int) $_POST['restore_id'];
    // Only restores when the database is connected and the id is valid.
    if ($pdo && $restoreId > 0) {
        // Starts a guarded block for the restore update.
        try {
            // Clears archived_at so the application returns to the active list.
            $stmt = $pdo->prepare("UPDATE applications SET archived_at = NULL WHERE application_id = :id AND archived_at IS NOT NULL");
            // Runs the restore update using the posted id.
            $stmt->execute(['id' => $restoreId]);
            // Tells the coordinator the application is active again.
            setFlash('success', 'Application restored to the active application list.');
        // Catches restore errors, for example a missing archived_at column.
        } catch (PDOException $e) {
            // Shows an error asking the coordinator to try again.
            setFlash('error', 'The application could not be restored. Try again.');
        // Closes the restore try/catch block.
        }
    // Closes the database check for restoring.
    }
    // Reloads the manage page after the restore attempt.
    redirectTo('coordinator/applications/manage.php');
// Closes the restore POST handling block.
}



// Catches old delete links and blocks deleting from this page.
if (($_GET['delete'] ?? '') !== '') {
    // Explains that applications must be archived instead of deleted.
    setFlash('error', 'Applications can no longer be deleted from the website. Use Archive instead.');
    // Sends the browser back to the manage page.
    redirectTo('coordinator/applications/manage.php');
// Closes the delete-link guard block.
}

/* ------------------------------------------------------------------
 * Every registered student, with the applications they have submitted.
 *
 * A student who has registered but not applied yet is part of the list as
 * well: they carry the automatic "Not Yet Submitted" indicator, and their
 * application details read N/A because there is no application to show.
 * ------------------------------------------------------------------ */
// Loads every registered student together with their applications.
$students = coordinatorStudentsWithApplications();

// Starts the archived application counter at zero.
$archivedAppCount = 0;
// Only counts archived applications when a database exists.
if ($pdo) {
    // Starts a guarded block for the count query.
    try {
        // Counts applications whose archived_at value is set.
        $archivedAppCount = (int) $pdo->query('SELECT COUNT(*) FROM applications WHERE archived_at IS NOT NULL')->fetchColumn();
    // Catches errors from the count query.
    } catch (PDOException $e) {
        // Keeps the counter at zero when the query fails.
        $archivedAppCount = 0;
    // Closes the count try/catch block.
    }
// Closes the database check for the archive count.
}

/* ------------------------------------------------------------------
 * Milestone progress of one application.
 *
 * The list used to read the student's *current* stage, so a finished stage
 * (or a student already at Presentation Complete) showed "0 of 8" and
 * looked as if nothing had been done. Measuring the stage the row actually
 * shows keeps the tracker honest for every row, completed ones included.
 * ------------------------------------------------------------------ */
// Defines a helper that returns milestone progress for one application.
$applicationMilestone = static function (string $email, string $track, string $stageLabel): array {
    // Gets the milestone steps and computes how many are done.
    $stats = milestoneProgress(stageMilestoneSteps($email, $track, stageKeyFromLabel($stageLabel)));
    // Reads the total number of milestones as an integer.
    $total = (int) ($stats['total'] ?? 0);
    // Reads the number of finished milestones as an integer.
    $done  = (int) ($stats['done'] ?? 0);

    // Starts the array of progress values the helper returns.
    return [
        // Stores the count of completed milestones.
        'done'     => $done,
        // Stores the total milestone count.
        'total'    => $total,
        // Computes the completion percentage, guarding against divide by zero.
        'pct'      => $total > 0 ? (int) round($done * 100 / $total) : 0,
        // Marks complete only when there are steps and all of them are done.
        'complete' => $total > 0 && $done >= $total,
        // Stores the stage label shown for the application.
        'stage'    => trim($stageLabel),
    // Closes the returned progress array.
    ];
// Closes the milestone helper function.
};

/* The milestone block is rendered once and reused by the newest application
   and by the earlier applications of the same student. */
// Defines a helper that builds the milestone progress HTML block.
$milestoneMarkup = static function (array $ms): string {
    // Reads the completed milestone count from the progress array.
    $done     = (int) ($ms['done'] ?? 0);
    // Reads the total milestone count from the progress array.
    $total    = (int) ($ms['total'] ?? 0);
    // Reads the percentage from the progress array.
    $pct      = (int) ($ms['pct'] ?? 0);
    // Reads the completed flag as a boolean.
    $complete = (bool) ($ms['complete'] ?? false);
    // Reads the stage label as text.
    $stage    = (string) ($ms['stage'] ?? '');

    // Starts the progress box and adds a complete class when all are done.
    $html  = '<div class="app-ms' . ($complete ? ' is-complete' : '') . '">';
    // Opens the top line that holds the milestone summary text.
    $html .= '<div class="app-ms-top"><span class="ms-overview-text">'
           // Writes either 'x of y milestones complete' or 'No milestones yet'.
           . ($total > 0 ? $done . ' of ' . $total . ' milestones complete' : 'No milestones yet')
           // Closes the summary text span.
           . '</span>';
    // Only shows a percentage when there are milestones.
    if ($total > 0) {
        // Prints the completion percentage in the top line.
        $html .= '<span class="app-ms-pct">' . $pct . '%</span>';
    // Closes the percentage condition.
    }
    // Closes the top line of the progress box.
    $html .= '</div>';

    // Only draws the progress bar when milestones exist.
    if ($total > 0) {
        // Draws the bar with a fill width equal to the percentage.
        $html .= '<span class="ms-overview-bar"><span class="ms-overview-fill" style="width:' . $pct . '%;"></span></span>';
    // Closes the progress bar condition.
    }
    // Only shows the stage chip when a stage label exists.
    if ($stage !== '') {
        // Prints the stage name with a layer icon.
        $html .= '<span class="app-ms-stage"><i class="fas fa-layer-group" aria-hidden="true"></i> ' . htmlspecialchars($stage) . '</span>';
    // Closes the stage chip condition.
    }
    // Only shows the all-complete note when every milestone is done.
    if ($complete) {
        // Prints the 'All milestones complete' note.
        $html .= '<span class="app-ms-state"><i class="fas fa-circle-check" aria-hidden="true"></i> All milestones complete</span>';
    // Closes the complete note condition.
    }

    // Closes the progress box and returns the finished HTML.
    return $html . '</div>';
// Closes the milestone markup helper.
};

// Loads the shared page header, sidebar, and styles.
require_once __DIR__ . '/../../includes/header.php';
// Closes PHP so the HTML layout can be written.
?>

<?php /* Opens the page banner at the top of the screen. */ ?>
<div class="page-header page-banner">
<?php /*     Shows the heading 'Application Management'. */ ?>
    <h2>Application Management</h2>
<?php /*     Explains that the table lists students and their applications. */ ?>
    <p>Every registered student and the applications they have submitted.</p>
<?php /* Closes the page banner. */ ?>
</div>
<?php /* Only shows the archive notice when archived applications exist. */ ?>
<?php if ($archivedAppCount > 0): ?>
<?php /* Opens a blue info banner about archived records. */ ?>
<div class="alert alert-info" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
<?php /*     Shows how many archived applications are kept for reporting. */ ?>
    <div><i class="fas fa-box-archive"></i> <strong><?= $archivedAppCount ?></strong> archived application<?= $archivedAppCount === 1 ? '' : 's' ?> are retained for reporting.</div>
<?php /*     Links to the Archived Applications page. */ ?>
    <a href="<?= url('coordinator/applications/archived.php') ?>" class="btn btn-sm btn-outline"><i class="fas fa-box-archive"></i> View Archived Applications</a>
<?php /* Closes the info banner. */ ?>
</div>
<?php /* Closes the archived notice condition. */ ?>
<?php endif; ?>

<?php /* Opens the main card that holds the student table. */ ?>
<div class="card">
<?php /*     Opens the card header area. */ ?>
    <div class="card-header">
<?php /*         Shows the table title and a placeholder for the visible row count. */ ?>
        <h3>Students &amp; Applications <span class="filter-count" data-filter-count></span></h3>
<?php /*     Closes the card header. */ ?>
    </div>
<?php /*     Opens the card body around the filters and table. */ ?>
    <div class="card-body">
<?php /*         Opens the filter bar that controls the applications table. */ ?>
        <div class="filter-bar apps-filter-bar" data-apps-filter="#appsTable">
<?php /*             Opens the search box wrapper. */ ?>
            <div class="filter-search">
<?php /*                 Shows the magnifying glass icon in the search box. */ ?>
                <i class="fas fa-search"></i>
<?php /*                 Search box that filters by student name, title, or adviser. */ ?>
                <input type="search" data-filter-q placeholder="Search by student name, title, adviser...">
<?php /*             Closes the search wrapper. */ ?>
            </div>
<?php /*             Button that clears the search and filters, hidden until needed. */ ?>
            <button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button>
<?php /*             Dropdown that filters rows by submission status. */ ?>
            <select data-filter="submission">
<?php /*                 Default option that shows all submission statuses. */ ?>
                <option value="">All Submission Statuses</option>
<?php /*                 Option for students who have not submitted an application yet. */ ?>
                <option value="not_submitted">Not Yet Submitted</option>
<?php /*                 Option for students who have submitted an application. */ ?>
                <option value="submitted">Submitted</option>
<?php /*             Closes the submission status dropdown. */ ?>
            </select>
<?php /*             Dropdown that filters rows by application status. */ ?>
            <select data-filter="appstatus">
<?php /*                 Default option that shows all application statuses. */ ?>
                <option value="">All Application Statuses</option>
<?php /*                 Loops over the statuses the coordinator is allowed to use. */ ?>
                <?php foreach (COORDINATOR_STATUSES as $key): ?>
<?php /*                 Adds one option showing the readable status label. */ ?>
                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars(STATUSES[$key]['label']) ?></option>
<?php /*                 Ends the status dropdown loop. */ ?>
                <?php endforeach; ?>
<?php /*             Closes the application status dropdown. */ ?>
            </select>
<?php /*             Dropdown that filters rows by program code. */ ?>
            <select data-filter="program">
<?php /*                 Default option that shows all programs. */ ?>
                <option value="">All Programs</option>
<?php /*                 Loops over each program code in the PROGRAMS list. */ ?>
                <?php foreach (array_keys(PROGRAMS) as $code): ?>
<?php /*                 Adds one option for the program code. */ ?>
                <option><?= htmlspecialchars($code) ?></option>
<?php /*                 Ends the program dropdown loop. */ ?>
                <?php endforeach; ?>
<?php /*             Closes the program dropdown. */ ?>
            </select>
<?php /*             Dropdown that filters rows by thesis track. */ ?>
            <select data-filter="track">
<?php /*                 Default option that shows all tracks. */ ?>
                <option value="">All Tracks</option>
<?php /*                 Adds the Thesis track option. */ ?>
                <option>Thesis</option>
<?php /*                 Adds the Capstone track option. */ ?>
                <option>Capstone</option>
<?php /*                 Adds the Seminar Paper track option. */ ?>
                <option>Seminar Paper</option>
<?php /*             Closes the track dropdown. */ ?>
            </select>
<?php /*         Closes the filter bar. */ ?>
        </div>
<?php /*         Opens the scrollable wrapper around the table. */ ?>
        <div class="table-responsive apps-table-wrap">
<?php /*             Starts the applications data table with id appsTable. */ ?>
            <table class="data-table" id="appsTable">
<?php /*                 Opens the column width definitions. */ ?>
                <colgroup>
<?php /*                     Sets the width hint for the Student column. */ ?>
                    <col class="app-col-student">
<?php /*                     Sets the width hint for the Milestone Progress column. */ ?>
                    <col class="app-col-milestones">
<?php /*                     Sets the width hint for the Application column. */ ?>
                    <col class="app-col-application">
<?php /*                     Sets the width hint for the Actions column. */ ?>
                    <col class="app-col-actions">
<?php /*                 Closes the column definition group. */ ?>
                </colgroup>
<?php /*                 Opens the table header section. */ ?>
                <thead>
<?php /*                     Starts the header row. */ ?>
                    <tr>
<?php /*                         Column header for the student's name and email. */ ?>
                        <th scope="col">Student</th>
<?php /*                         Column header for milestone progress. */ ?>
                        <th scope="col">Milestone Progress</th>
<?php /*                         Column header for the application details. */ ?>
                        <th scope="col">Application</th>
<?php /*                         Column header for the edit and archive buttons. */ ?>
                        <th scope="col">Actions</th>
<?php /*                     Closes the header row. */ ?>
                    </tr>
<?php /*                 Closes the table header section. */ ?>
                </thead>
<?php /*                 Opens the table body with one group of rows per student. */ ?>
                <tbody>
<?php /*                     Loops over every student returned by the helper. */ ?>
                    <?php foreach ($students as $student):
                        // Gets the list of applications belonging to this student.
                        $applications = $student['applications'];
                        // Takes the newest application as the main row, or null if none.
                        $primary = $applications[0] ?? null;
                        // Keeps the older applications for the expandable child rows.
                        $children = array_slice($applications, 1);
                        // Builds a unique group id from the student's user id.
                        $groupId = 'grp-' . (int) $student['userId'];
                        // Builds the link to the student's detail page.
                        $studentUrl = url('coordinator/applications/student.php?id=' . (int) $student['userId']);

                        // Comment noting that each row carries its own program and track.
                        // Each row carries the program and the track of the
                        // Comment continues: the program filter works on the application.
                        // application it shows, so the program filter works on
                        // Comment ends: not only on the student record.
                        // the application and not only on the student.
                        // Uses the application program, falling back to the student program.
                        $rowProgram = $primary['program'] ?? $student['program'];
                        // Uses the application track, falling back to the student track label.
                        $rowTrack   = $primary['track'] ?? $student['trackLabel'];

                        // Comment says the row search text belongs to the row itself.
                        // The searchable text of a row is its own: the student
                        // Comment continues: the row searches student plus application data.
                        // plus the application the row shows. A search that
                        // Comment explains that an older application can match its own row.
                        // matches an older application therefore matches that
                        // Comment ends: not only the newest application.
                        // row and not the newest one.
                        // Builds lower-case searchable text by joining the row's values.
                        $parentSearch = strtolower(implode(' ', array_filter([
                            // Adds the student name, email, and program to the search text.
                            $student['name'], $student['email'], $student['program'],
                            // Adds the track label and adviser to the search text.
                            $student['trackLabel'], $student['adviser'],
                            // Adds the application stage, title, and status to the search text.
                            $primary['stage'] ?? '', $primary['title'] ?? '', $primary['status'] ?? '',
                        // Closes the search text builder and lowercases it.
                        ])));
                        // Comment: the tracker follows the stage this row actually shows.
                        // Milestone progress of the application this row shows
                        // Comment: never the student's next stage.
                        // (never the student's next stage), so a finished stage
                        // Comment: a finished stage still shows its completed tracker.
                        // still displays its completed tracker.
                        // Starts computing progress only when the row has an application.
                        $primaryMs = $primary
                            // Calls the milestone helper with the email, track, and stage.
                            ? $applicationMilestone($student['email'], $student['track'], (string) ($primary['stage'] ?? ''))
                            // Uses null when there is no application at all.
                            : null;
                    // Closes PHP before the row markup is written.
                    ?>
<?php /*                     Checks whether this student has no application yet. */ ?>
                    <?php if (!$primary): ?>
<?php /*                     HTML note marking that this student has not submitted yet. */ ?>
                    <!-- Registered, but no application yet. -->
<?php /*                     Starts the row for a registered student with no application. */ ?>
                    <tr data-submission="not_submitted"
<?php /*                         Leaves the status filter value empty because no application exists. */ ?>
                        data-appstatus=""
<?php /*                         Stores the student program so the program filter can match. */ ?>
                        data-program="<?= htmlspecialchars($student['program']) ?>"
<?php /*                         Stores the student track so the track filter can match. */ ?>
                        data-track="<?= htmlspecialchars($student['trackLabel']) ?>"
<?php /*                         Tags the row with the student group id. */ ?>
                        data-group="<?= htmlspecialchars($groupId) ?>"
<?php /*                         Stores the lower-case search text for this row. */ ?>
                        data-search="<?= htmlspecialchars($parentSearch) ?>">
<?php /*                         Opens the student cell. */ ?>
                        <td class="app-cell app-cell-student" data-label="Student">
<?php /*                             Wraps the student name and email. */ ?>
                            <div class="student-cell">
<?php /*                                 Groups the name link and email together. */ ?>
                                <div class="student-cell-main">
<?php /*                                     Shows the student name as a link to their detail page. */ ?>
                                    <a href="<?= $studentUrl ?>" class="student-name-link"><?= htmlspecialchars($student['name']) ?></a>
<?php /*                                     Shows the student's email under the name. */ ?>
                                    <span class="student-cell-mail"><?= htmlspecialchars($student['email']) ?></span>
<?php /*                                 Closes the name and email group. */ ?>
                                </div>
<?php /*                             Closes the student cell wrapper. */ ?>
                            </div>
<?php /*                         Closes the student cell. */ ?>
                        </td>
<?php /*                         Opens the milestone column for this student. */ ?>
                        <td class="app-cell app-cell-milestones" data-label="Milestones">
<?php /*                             Shows an empty progress box. */ ?>
                            <div class="app-ms is-empty">
<?php /*                                 Says there is no application yet. */ ?>
                                <span class="ms-overview-text">No application yet</span>
<?php /*                                 Explains milestones start after the first submission. */ ?>
                                <span class="app-ms-stage"><i class="fas fa-hourglass-start" aria-hidden="true"></i> Milestones begin after the first submission</span>
<?php /*                             Closes the empty progress box. */ ?>
                            </div>
<?php /*                         Closes the milestone cell. */ ?>
                        </td>
<?php /*                         Opens the application details cell. */ ?>
                        <td class="app-cell app-cell-application" data-label="Application">
<?php /*                             Starts the empty application details block. */ ?>
                            <div class="app-details app-details-empty">
<?php /*                                 Shows the 'not submitted' badge for this student. */ ?>
                                <div class="app-details-top"><?= submissionBadge(false) ?></div>
<?php /*                                 Shows a muted line saying no application was submitted. */ ?>
                                <p class="app-details-title text-muted-soft">No application submitted yet</p>
<?php /*                                 Opens the program, track, and adviser details. */ ?>
                                <div class="app-details-meta">
<?php /*                                     Shows the student's program. */ ?>
                                    <span class="app-meta-item"><i class="fas fa-graduation-cap" aria-hidden="true"></i><span class="app-meta-label">Program</span><span class="app-meta-value"><?= htmlspecialchars($student['program']) ?></span></span>
<?php /*                                     Shows the student's track. */ ?>
                                    <span class="app-meta-item"><i class="fas fa-route" aria-hidden="true"></i><span class="app-meta-label">Track</span><span class="app-meta-value"><?= htmlspecialchars($student['trackLabel']) ?></span></span>
<?php /*                                     Shows the adviser, or N/A when none is set. */ ?>
                                    <span class="app-meta-item"><i class="fas fa-user-tie" aria-hidden="true"></i><span class="app-meta-label">Adviser</span><span class="app-meta-value"><?= htmlspecialchars($student['adviser'] !== '' ? $student['adviser'] : 'N/A') ?></span></span>
<?php /*                                 Closes the meta details. */ ?>
                                </div>
<?php /*                             Closes the application details block. */ ?>
                            </div>
<?php /*                         Closes the application cell. */ ?>
                        </td>
<?php /*                         Opens the actions cell. */ ?>
                        <td class="app-cell app-cell-actions" data-label="Actions">
<?php /*                             Wraps the action buttons. */ ?>
                            <div class="app-actions">
<?php /*                                 Button linking to the student's detail page. */ ?>
                                <a href="<?= $studentUrl ?>" class="btn btn-outline app-view-student-btn"><i class="fas fa-user" aria-hidden="true"></i> View Student</a>
<?php /*                             Closes the actions wrapper. */ ?>
                            </div>
<?php /*                         Closes the actions cell. */ ?>
                        </td>
<?php /*                     Closes the no-application row. */ ?>
                    </tr>
<?php /*                     Runs when the student has at least one application. */ ?>
                    <?php else: ?>
<?php /*                     Starts the main row showing the newest application. */ ?>
                    <tr class="app-group-row" data-submission="submitted"
<?php /*                         Stores the application status for filtering. */ ?>
                        data-appstatus="<?= htmlspecialchars($primary['status']) ?>"
<?php /*                         Stores the row's program for filtering. */ ?>
                        data-program="<?= htmlspecialchars($rowProgram) ?>"
<?php /*                         Stores the row's track for filtering. */ ?>
                        data-track="<?= htmlspecialchars($rowTrack) ?>"
<?php /*                         Tags the row with the group id. */ ?>
                        data-group="<?= htmlspecialchars($groupId) ?>"
<?php /*                         Stores the searchable text for the row. */ ?>
                        data-search="<?= htmlspecialchars($parentSearch) ?>">
<?php /*                         Opens the student cell of the application row. */ ?>
                        <td class="app-cell app-cell-student" data-label="Student">
<?php /*                             Wraps the toggle, name, and email. */ ?>
                            <div class="student-cell">
<?php /*                                 Only shows the expand arrow when older applications exist. */ ?>
                                <?php if ($children): ?>
<?php /*                                 Arrow button that shows or hides the student's earlier applications. */ ?>
                                <button type="button" class="student-cell-toggle" data-group-toggle="<?= htmlspecialchars($groupId) ?>" aria-expanded="false" aria-label="Show the other applications of this student" title="Show the other applications of this student">
<?php /*                                     Chevron icon that rotates when the group opens. */ ?>
                                    <i class="fas fa-chevron-right toggle-caret" aria-hidden="true"></i>
<?php /*                                     Shows how many applications the student has. */ ?>
                                    <span class="student-group-count"><?= count($applications) ?></span>
<?php /*                                 Closes the group toggle button. */ ?>
                                </button>
<?php /*                                 Closes the expand arrow condition. */ ?>
                                <?php endif; ?>
<?php /*                                 Groups the student name link and email. */ ?>
                                <div class="student-cell-main">
<?php /*                                     Shows the student name as a link to their page. */ ?>
                                    <a href="<?= $studentUrl ?>" class="student-name-link"><?= htmlspecialchars($student['name']) ?></a>
<?php /*                                     Shows the student's email. */ ?>
                                    <span class="student-cell-mail"><?= htmlspecialchars($student['email']) ?></span>
<?php /*                                 Closes the name and email group. */ ?>
                                </div>
<?php /*                             Closes the student cell wrapper. */ ?>
                            </div>
<?php /*                         Closes the student cell. */ ?>
                        </td>
<?php /*                         Opens the milestone column. */ ?>
                        <td class="app-cell app-cell-milestones" data-label="Milestones">
<?php /*                             Prints the progress box for the newest application. */ ?>
                            <?= $milestoneMarkup($primaryMs) ?>
<?php /*                         Closes the milestone cell. */ ?>
                        </td>
<?php /*                         Opens the application details cell. */ ?>
                        <td class="app-cell app-cell-application" data-app-cell data-label="Application">
<?php /*                             Wraps the application details. */ ?>
                            <div class="cell-value app-details">
<?php /*                                 Opens the top line with status and track. */ ?>
                                <div class="app-details-top">
<?php /*                                     Shows a coloured badge for the application status. */ ?>
                                    <?= statusBadge($primary['status']) ?>
<?php /*                                     Shows the track name as a small chip. */ ?>
                                    <span class="track-chip"><?= htmlspecialchars($rowTrack) ?></span>
<?php /*                                 Closes the top line. */ ?>
                                </div>
<?php /*                                 Shows the paper title of the application. */ ?>
                                <p class="app-details-title"><?= htmlspecialchars($primary['title']) ?></p>
<?php /*                                 Opens the program, stage, adviser, and date details. */ ?>
                                <div class="app-details-meta">
<?php /*                                     Shows the application program. */ ?>
                                    <span class="app-meta-item"><i class="fas fa-graduation-cap" aria-hidden="true"></i><span class="app-meta-label">Program</span><span class="app-meta-value"><?= htmlspecialchars($rowProgram) ?></span></span>
<?php /*                                     Shows the current presentation stage. */ ?>
                                    <span class="app-meta-item"><i class="fas fa-layer-group" aria-hidden="true"></i><span class="app-meta-label">Stage</span><span class="app-meta-value"><?= htmlspecialchars($primary['stage']) ?></span></span>
<?php /*                                     Shows the adviser, or a dash when empty. */ ?>
                                    <span class="app-meta-item"><i class="fas fa-user-tie" aria-hidden="true"></i><span class="app-meta-label">Adviser</span><span class="app-meta-value"><?= htmlspecialchars($student['adviser'] !== '' ? $student['adviser'] : '—') ?></span></span>
<?php /*                                     Shows the submitted date formatted as Mon dd, yyyy. */ ?>
                                    <span class="app-meta-item"><i class="fas fa-calendar-day" aria-hidden="true"></i><span class="app-meta-label">Submitted</span><span class="app-meta-value"><?= $primary['date'] ? date('M d, Y', strtotime($primary['date'])) : '—' ?></span></span>
<?php /*                                 Closes the meta details. */ ?>
                                </div>
<?php /*                             Closes the application details wrapper. */ ?>
                            </div>
<?php /*                         Closes the application cell. */ ?>
                        </td>
<?php /*                         Opens the actions cell. */ ?>
                        <td class="app-cell app-cell-actions" data-label="Actions">
<?php /*                             Wraps the edit link and archive switch. */ ?>
                            <div class="app-actions">
<?php /*                                 Edit button opening the process page for this application. */ ?>
                                <a href="<?= url('coordinator/applications/process.php?id=' . (int) $primary['id']) ?>" class="btn btn-primary app-edit-btn" title="Edit application"><i class="fas fa-pen-to-square" aria-hidden="true"></i> Edit</a>
<?php /*                                 Wraps the archive label and toggle switch. */ ?>
                                <div class="app-archive-control">
<?php /*                                     Shows the word 'Archive' beside the switch. */ ?>
                                    <span class="app-archive-text">Archive</span>
<?php /*                                     Switch that opens the archive modal for this application. */ ?>
                                    <button type="button" class="app-switch" role="switch" aria-checked="false" aria-label="Archive application of <?= htmlspecialchars($student['name']) ?>" aria-controls="archiveApplicationModal" data-archive-open data-application-id="<?= (int) $primary['id'] ?>" data-application-student="<?= htmlspecialchars($student['name']) ?>" data-application-title="<?= htmlspecialchars($primary['title']) ?>" title="Archive this application">
<?php /*                                         Draws the switch track and thumb. */ ?>
                                        <span class="app-switch-track" aria-hidden="true"><span class="app-switch-thumb"></span></span>
<?php /*                                     Closes the archive switch button. */ ?>
                                    </button>
<?php /*                                 Closes the archive control wrapper. */ ?>
                                </div>
<?php /*                             Closes the actions wrapper. */ ?>
                            </div>
<?php /*                         Closes the actions cell. */ ?>
                        </td>
<?php /*                     Closes the newest-application row. */ ?>
                    </tr>
<?php /*                     Loops over the older applications of this student. */ ?>
                    <?php foreach ($children as $child): ?>
<?php /*                     Opens a PHP block to compute values for the child row. */ ?>
                    <?php
                        // Uses the child application program or the row program.
                        $childProgram = $child['program'] ?? $rowProgram;
                        // Uses the child application track or the row track.
                        $childTrack   = $child['track'] ?? $rowTrack;
                        // Computes milestone progress for this child application.
                        $childMs      = $applicationMilestone($student['email'], $student['track'], (string) ($child['stage'] ?? ''));
                    // Closes the PHP block that prepared the child row.
                    ?>
<?php /*                     Starts a hidden child row for an earlier application. */ ?>
                    <tr class="app-child-row" data-child-of="<?= htmlspecialchars($groupId) ?>" data-submission="submitted" data-appstatus="<?= htmlspecialchars($child['status']) ?>" data-program="<?= htmlspecialchars($childProgram) ?>" data-track="<?= htmlspecialchars($childTrack) ?>" data-search="<?= htmlspecialchars(strtolower($student['name'] . ' ' . $child['stage'] . ' ' . $child['title'] . ' ' . $child['status'])) ?>">
<?php /*                         Opens the child student cell. */ ?>
                        <td class="app-cell app-cell-student" data-label="Student">
<?php /*                             Shows 'Also submitted' to mark an older application. */ ?>
                            <span class="child-label"><i class="fas fa-level-down" aria-hidden="true"></i> Also submitted</span>
<?php /*                         Closes the child student cell. */ ?>
                        </td>
<?php /*                         Opens the milestone cell for the child row. */ ?>
                        <td class="app-cell app-cell-milestones" data-label="Milestones">
<?php /*                             Prints the progress box for the child application. */ ?>
                            <?= $milestoneMarkup($childMs) ?>
<?php /*                         Closes the child milestone cell. */ ?>
                        </td>
<?php /*                         Opens the child application cell. */ ?>
                        <td class="app-cell app-cell-application" data-app-cell data-label="Application">
<?php /*                             Wraps the child application details. */ ?>
                            <div class="cell-value app-details">
<?php /*                                 Opens the status and track line. */ ?>
                                <div class="app-details-top">
<?php /*                                     Shows the child application status badge. */ ?>
                                    <?= statusBadge($child['status']) ?>
<?php /*                                     Shows the child application track. */ ?>
                                    <span class="track-chip"><?= htmlspecialchars($childTrack) ?></span>
<?php /*                                 Closes the top line. */ ?>
                                </div>
<?php /*                                 Shows the child application title. */ ?>
                                <p class="app-details-title"><?= htmlspecialchars($child['title']) ?></p>
<?php /*                                 Opens the child details list. */ ?>
                                <div class="app-details-meta">
<?php /*                                     Shows the child application program. */ ?>
                                    <span class="app-meta-item"><i class="fas fa-graduation-cap" aria-hidden="true"></i><span class="app-meta-label">Program</span><span class="app-meta-value"><?= htmlspecialchars($childProgram) ?></span></span>
<?php /*                                     Shows the child application stage. */ ?>
                                    <span class="app-meta-item"><i class="fas fa-layer-group" aria-hidden="true"></i><span class="app-meta-label">Stage</span><span class="app-meta-value"><?= htmlspecialchars($child['stage']) ?></span></span>
<?php /*                                     Shows the adviser, or a dash when empty. */ ?>
                                    <span class="app-meta-item"><i class="fas fa-user-tie" aria-hidden="true"></i><span class="app-meta-label">Adviser</span><span class="app-meta-value"><?= htmlspecialchars($student['adviser'] !== '' ? $student['adviser'] : '—') ?></span></span>
<?php /*                                     Shows the child submission date. */ ?>
                                    <span class="app-meta-item"><i class="fas fa-calendar-day" aria-hidden="true"></i><span class="app-meta-label">Submitted</span><span class="app-meta-value"><?= $child['date'] ? date('M d, Y', strtotime($child['date'])) : '—' ?></span></span>
<?php /*                                 Closes the child details list. */ ?>
                                </div>
<?php /*                             Closes the child details wrapper. */ ?>
                            </div>
<?php /*                         Closes the child application cell. */ ?>
                        </td>
<?php /*                         Opens the child actions cell. */ ?>
                        <td class="app-cell app-cell-actions" data-label="Actions">
<?php /*                             Wraps the child edit and archive controls. */ ?>
                            <div class="app-actions">
<?php /*                                 Edit button for the child application. */ ?>
                                <a href="<?= url('coordinator/applications/process.php?id=' . (int) $child['id']) ?>" class="btn btn-primary app-edit-btn" title="Edit application"><i class="fas fa-pen-to-square" aria-hidden="true"></i> Edit</a>
<?php /*                                 Wraps the child archive switch. */ ?>
                                <div class="app-archive-control">
<?php /*                                     Shows the 'Archive' label. */ ?>
                                    <span class="app-archive-text">Archive</span>
<?php /*                                     Switch that opens the archive modal for the child application. */ ?>
                                    <button type="button" class="app-switch" role="switch" aria-checked="false" aria-label="Archive application of <?= htmlspecialchars($student['name']) ?>" aria-controls="archiveApplicationModal" data-archive-open data-application-id="<?= (int) $child['id'] ?>" data-application-student="<?= htmlspecialchars($student['name']) ?>" data-application-title="<?= htmlspecialchars($child['title']) ?>" title="Archive this application">
<?php /*                                         Draws the switch track and thumb. */ ?>
                                        <span class="app-switch-track" aria-hidden="true"><span class="app-switch-thumb"></span></span>
<?php /*                                     Closes the child archive switch. */ ?>
                                    </button>
<?php /*                                 Closes the child archive control. */ ?>
                                </div>
<?php /*                             Closes the child actions wrapper. */ ?>
                            </div>
<?php /*                         Closes the child actions cell. */ ?>
                        </td>
<?php /*                     Closes the child row. */ ?>
                    </tr>
<?php /*                     Ends the loop over older applications. */ ?>
                    <?php endforeach; ?>
<?php /*                     Ends the has-application and no-application condition. */ ?>
                    <?php endif; ?>
<?php /*                     Ends the loop over all students. */ ?>
                    <?php endforeach; ?>
<?php /*                     Row shown when no student matches the filters. */ ?>
                    <tr data-filter-empty <?= $students ? 'hidden' : '' ?>>
<?php /*                         Message telling the coordinator nothing matched. */ ?>
                        <td colspan="4" class="app-empty-cell">No students match your search or filters.</td>
<?php /*                     Closes the empty-results row. */ ?>
                    </tr>
<?php /*                 Closes the table body. */ ?>
                </tbody>
<?php /*             Closes the applications table. */ ?>
            </table>
<?php /*         Closes the responsive table wrapper. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the main card. */ ?>
</div>

<?php /* Opens the archive confirmation modal overlay. */ ?>
<div class="modal-overlay" id="archiveApplicationModal" aria-hidden="true">
<?php /*     Starts the dialog box with accessible labels. */ ?>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="archiveApplicationTitle">
<?php /*         Header with the question and a close button. */ ?>
        <div class="modal-header"><h3 id="archiveApplicationTitle">Archive application?</h3><button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button></div>
<?php /*         Explains archiving and shows the application title. */ ?>
        <div class="modal-body"><p>Archive the application for <strong id="archiveApplicationStudent"></strong>? The application will be removed from the active list, but its record and documents will be retained for reporting.</p><p style="margin-top:0.5rem;font-size:0.85rem;color:var(--gray-500);"><em id="archiveApplicationTitleText"></em></p></div>
<?php /*         Form that posts the archive id back to this page. */ ?>
        <form method="post" action="<?= url('coordinator/applications/manage.php') ?>" class="modal-footer">
<?php /*             Hidden field filled with the id of the application to archive. */ ?>
            <input type="hidden" name="archive_id" id="archiveApplicationId">
<?php /*             Cancel button that closes the modal. */ ?>
            <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
<?php /*             Submit button that archives the application. */ ?>
            <button type="submit" class="btn btn-danger"><i class="fas fa-box-archive"></i> Archive Application</button>
<?php /*         Closes the archive form. */ ?>
        </form>
<?php /*     Closes the modal dialog. */ ?>
    </div>
<?php /* Closes the modal overlay. */ ?>
</div>

<?php /* Starts the JavaScript that powers the archive modal. */ ?>
<script>
<?php /* Wraps the modal script in its own scope. */ ?>
(function () {
<?php /*     Finds the archive confirmation modal element. */ ?>
    const modal = document.getElementById('archiveApplicationModal');
<?php /*     Stops the script when the modal is not on the page. */ ?>
    if (!modal) return;

<?php /*     Finds the hidden field that stores the application id. */ ?>
    const idField = document.getElementById('archiveApplicationId');
<?php /*     Finds the element that shows the student name. */ ?>
    const studentField = document.getElementById('archiveApplicationStudent');
<?php /*     Finds the element that shows the application title. */ ?>
    const titleField = document.getElementById('archiveApplicationTitleText');
<?php /*     Keeps track of the archive switch that was clicked. */ ?>
    let activeSwitch = null;

<?php /*     Defines a helper that turns the switch back off. */ ?>
    function resetSwitch() {
<?php /*         Only resets when a switch is currently active. */ ?>
        if (activeSwitch) {
<?php /*             Marks the switch as not checked. */ ?>
            activeSwitch.setAttribute('aria-checked', 'false');
<?php /*             Clears the stored switch reference. */ ?>
            activeSwitch = null;
<?php /*         Closes the active switch condition. */ ?>
        }
<?php /*     Closes the resetSwitch function. */ ?>
    }

<?php /*     Finds every archive switch on the page. */ ?>
    document.querySelectorAll('[data-archive-open]').forEach(button => {
<?php /*         Runs when a switch is clicked. */ ?>
        button.addEventListener('click', () => {
<?php /*             Remembers which switch opened the modal. */ ?>
            activeSwitch = button;
<?php /*             Shows the switch in the on position. */ ?>
            button.setAttribute('aria-checked', 'true');
<?php /*             Puts the application id into the hidden form field. */ ?>
            idField.value = button.dataset.applicationId;
<?php /*             Shows the student name in the modal. */ ?>
            studentField.textContent = button.dataset.applicationStudent;
<?php /*             Shows the quoted application title in the modal. */ ?>
            titleField.textContent = button.dataset.applicationTitle ? '\u201C' + button.dataset.applicationTitle + '\u201D' : '';
<?php /*             Makes the modal visible. */ ?>
            modal.classList.add('active');
<?php /*         Closes the click handler. */ ?>
        });
<?php /*     Closes the loop over the switches. */ ?>
    });

<?php /*     Comment says cancelling leaves the application untouched. */ ?>
    // Cancel, the close cross, a click on the backdrop and Escape all leave
<?php /*     Comment continues: the switch slides back to off. */ ?>
    // the application untouched, so the switch slides back to off.
<?php /*     Cancel and close buttons reset the switch. */ ?>
    modal.querySelectorAll('[data-modal-close]').forEach(btn => btn.addEventListener('click', resetSwitch));
<?php /*     Listens for clicks on the modal backdrop. */ ?>
    modal.addEventListener('click', e => {
<?php /*         Closes the modal when the backdrop itself is clicked. */ ?>
        if (e.target === modal) resetSwitch();
<?php /*     Closes the backdrop click handler. */ ?>
    });
<?php /*     Listens for keyboard presses while the page is open. */ ?>
    document.addEventListener('keydown', e => {
<?php /*         Checks for Escape while the modal is open. */ ?>
        if (e.key === 'Escape' && modal.classList.contains('active')) {
<?php /*             Hides the modal. */ ?>
            modal.classList.remove('active');
<?php /*             Turns the switch back off. */ ?>
            resetSwitch();
<?php /*         Closes the Escape condition. */ ?>
        }
<?php /*     Closes the keydown handler. */ ?>
    });
<?php /* Closes and runs the archive modal script. */ ?>
})();

<?php /* Comment introduces the group-aware filter for this table. */ ?>
// Group-aware filter for this table.
<?php /* Comment spacer line inside the filter explanation. */ ?>
//
<?php /* Comment: the shared filter hides rows one by one. */ ?>
// The shared filter in assets/js/script.js hides rows one by one, which would
<?php /* Comment: that would leave a student without their applications. */ ?>
// leave a student visible without the applications that belong to them. Here
<?php /* Comment: here the whole group is the unit of filtering. */ ?>
// the group is the unit: a group stays visible when any of its applications
<?php /* Comment: matching groups stay and child rows follow the group. */ ?>
// matches, and its child rows follow the group.
<?php /* Starts the group-aware filtering script. */ ?>
(function () {
<?php /*     Finds the filter bar for the applications table. */ ?>
    const bar = document.querySelector('[data-apps-filter]');
<?php /*     Finds the table named by the filter bar's data attribute. */ ?>
    const table = document.querySelector(bar?.dataset.appsFilter);
<?php /*     Stops when the filter bar or table is missing. */ ?>
    if (!bar || !table) return;

<?php /*     Finds the search input. */ ?>
    const search = bar.querySelector('[data-filter-q]');
<?php /*     Finds the clear filters button. */ ?>
    const clearBtn = bar.querySelector('[data-filter-clear]');
<?php /*     Collects all dropdown filters in the bar. */ ?>
    const selects = [...bar.querySelectorAll('select[data-filter]')];
<?php /*     Finds the element that shows the visible student count. */ ?>
    const countEl = bar.querySelector('[data-filter-count]') || document.querySelector('[data-filter-count]');
<?php /*     Finds the empty-results row. */ ?>
    const empty = table.querySelector('[data-filter-empty]');
<?php /*     Collects the main row of each student group. */ ?>
    const groups = [...table.querySelectorAll('tr[data-group]')];

<?php /*     Comment: rowMatches tests a row against search and dropdowns. */ ?>
    // True when a row passes the current search text and dropdown filters.
<?php /*     Defines a helper that tests one row against the filters. */ ?>
    function rowMatches(row, q) {
<?php /*         A row passes when it contains the search text or nothing was typed. */ ?>
        let ok = !q || (row.dataset.search || '').includes(q);
<?php /*         Checks each dropdown filter in turn. */ ?>
        selects.forEach(sel => {
<?php /*             Gets the data attribute name the dropdown filters on. */ ?>
            const key = sel.dataset.filter;
<?php /*             Gets the selected dropdown value. */ ?>
            const val = sel.value;
<?php /*             Skips dropdowns left on their 'All' option. */ ?>
            if (!val) return;
<?php /*             Fails the row when its value differs from the selected value. */ ?>
            if ((row.dataset[key] || '') !== val) ok = false;
<?php /*         Closes the dropdown loop. */ ?>
        });
<?php /*         Returns whether the row passed every filter. */ ?>
        return ok;
<?php /*     Closes the rowMatches function. */ ?>
    }

<?php /*     Defines the function that applies all filters to the table. */ ?>
    function apply() {
<?php /*         Reads the search text, trims it, and lowercases it. */ ?>
        const q = (search?.value || '').trim().toLowerCase();
<?php /*         True when a search or any dropdown filter is active. */ ?>
        const filtering = !!(q || selects.some(sel => sel.value));
<?php /*         Counts student groups that pass the filters. */ ?>
        let visibleGroups = 0;
<?php /*         Counts applications that pass the filters. */ ?>
        let visibleApps = 0;

<?php /*         Loops over each student group row. */ ?>
        groups.forEach(row => {
<?php /*             Reads the group id from the row. */ ?>
            const groupId = row.dataset.group;
<?php /*             Finds the child rows that belong to this group. */ ?>
            const children = [...table.querySelectorAll('tr[data-child-of="' + groupId + '"]')];
<?php /*             Finds the expand arrow for the group. */ ?>
            const toggle = row.querySelector('[data-group-toggle]');
<?php /*             Reads whether the group is currently expanded. */ ?>
            let expanded = toggle?.getAttribute('aria-expanded') === 'true';

<?php /*             Comment: a row matches when it passes text and dropdown filters. */ ?>
            // A row matches when it passes the search text and the dropdown
<?php /*             Comment: every row carries its own program and track. */ ?>
            // filters. Every row carries the program and the track of the
<?php /*             Comment: so the program filter works per application. */ ?>
            // application it shows, so the program filter works per
<?php /*             Comment ends: not only per student. */ ?>
            // application and not only per student.
<?php /*             Tests the main row against the search and dropdowns. */ ?>
            const parentMatches = rowMatches(row, q);
<?php /*             Tests every child row against the filters. */ ?>
            const childMatches = children.map(child => rowMatches(child, q));
<?php /*             True when at least one child application matches. */ ?>
            const childHit = childMatches.some(Boolean);
<?php /*             Keeps the group when the main row or any child matches. */ ?>
            const groupOk = parentMatches || childHit;

<?php /*             Hides the main row when the group does not match. */ ?>
            row.hidden = !groupOk;
<?php /*             Also sets display none so the row really disappears. */ ?>
            row.style.display = groupOk ? '' : 'none';

<?php /*             Handles a group that fails the filters. */ ?>
            if (!groupOk) {
<?php /*                 Hides every child row of the failing group. */ ?>
                children.forEach(child => {
<?php /*                     Removes the visible class from the child row. */ ?>
                    child.classList.remove('is-shown');
<?php /*                     Marks the child row hidden. */ ?>
                    child.hidden = true;
<?php /*                     Applies display none to the child row. */ ?>
                    child.style.display = 'none';
<?php /*                 Closes the child hiding loop. */ ?>
                });
<?php /*                 Skips to the next group. */ ?>
                return;
<?php /*             Closes the failing-group block. */ ?>
            }

<?php /*             Comment: a match on an older application should open the group. */ ?>
            // When a filter or a search matches only an older application of
<?php /*             Comment: so the matching application is actually visible. */ ?>
            // this student, the group opens so the matching application is
<?php /*             Comment: once the coordinator touches the arrow, their choice wins. */ ?>
            // actually visible instead of sitting behind a closed arrow.
<?php /*             Comment ends: a filter never overrides the arrow. */ ?>
            // Once the coordinator has touched the arrow themselves, their
<?php /*             Comment: the coordinator's arrow choice wins over the filter. */ ?>
            // choice wins and the group stays as they left it.
<?php /*             Opens the group when only an older application matches. */ ?>
            if (filtering && !parentMatches && childHit && toggle && toggle.dataset.manual !== '1') {
<?php /*                 Expands the group arrow automatically. */ ?>
                toggle.setAttribute('aria-expanded', 'true');
<?php /*                 Updates the local expanded flag. */ ?>
                expanded = true;
<?php /*             Closes the auto-expand condition. */ ?>
            }

<?php /*             Comment: the newest application may not pass the filter. */ ?>
            // The newest application of the group does not pass the filter:
<?php /*             Comment: its details are hidden so no filtered record is shown. */ ?>
            // its details are hidden so the table never shows a record the
<?php /*             Comment ends the filtered-out record explanation. */ ?>
            // coordinator filtered out.
<?php /*             Marks the row so a filtered-out newest application is hidden. */ ?>
            row.classList.toggle('is-parent-mismatch', groupOk && !parentMatches && filtering);

<?php /*             Counts this group as visible. */ ?>
            visibleGroups += 1;
<?php /*             Counts the main application when it matches. */ ?>
            if (parentMatches) visibleApps += 1;
<?php /*             Looks at each child application for counting. */ ?>
            children.forEach((child, i) => {
<?php /*                 Counts matching child applications. */ ?>
                if (childMatches[i]) visibleApps += 1;
<?php /*             Closes the child counting loop. */ ?>
            });

<?php /*             Comment: the arrow decides whether extra applications show. */ ?>
            // The arrow always decides whether the extra applications of the
<?php /*             Comment: whether a filter is active or not. */ ?>
            // student are shown, whether a filter is active or not.
<?php /*             Shows or hides each child row after filtering. */ ?>
            children.forEach((child, i) => {
<?php /*                 A child row shows only when it matches and the group is open. */ ?>
                const show = childMatches[i] && expanded;
<?php /*                 Adds or removes the visible class on the child. */ ?>
                child.classList.toggle('is-shown', show);
<?php /*                 Hides the child row when it should not show. */ ?>
                child.hidden = !show;
<?php /*                 Sets display none or normal for the child. */ ?>
                child.style.display = show ? '' : 'none';
<?php /*             Closes the child visibility loop. */ ?>
            });
<?php /*         Closes the loop over student groups. */ ?>
        });

<?php /*         Only updates the empty row when it exists. */ ?>
        if (empty) {
<?php /*             Hides the message when at least one group is visible. */ ?>
            empty.hidden = visibleGroups > 0;
<?php /*             Applies the matching display style. */ ?>
            empty.style.display = visibleGroups > 0 ? 'none' : '';
<?php /*         Closes the empty row condition. */ ?>
        }
<?php /*         Updates the visible count label when present. */ ?>
        if (countEl) {
<?php /*             Writes the visible student count beside the table title. */ ?>
            countEl.textContent = '(' + visibleGroups + ' student' + (visibleGroups === 1 ? '' : 's') + ')';
<?php /*         Closes the count label condition. */ ?>
        }
<?php /*         Updates the clear button when present. */ ?>
        if (clearBtn) {
<?php /*             Shows the clear button only while filtering. */ ?>
            clearBtn.hidden = !filtering;
<?php /*         Closes the clear button condition. */ ?>
        }
<?php /*     Closes the apply function. */ ?>
    }

<?php /*     Comment: the caret opens or closes a student's earlier applications. */ ?>
    // Clicking the caret opens or closes that student's earlier applications,
<?php /*     Comment: the group is then re-checked against the filters. */ ?>
    // then the group is re-evaluated against the filters. The choice of the
<?php /*     Comment: the coordinator's choice is remembered by the arrow. */ ?>
    // coordinator is remembered, so a filter never overrides the arrow.
<?php /*     Finds every group expand arrow. */ ?>
    document.querySelectorAll('[data-group-toggle]').forEach(button => {
<?php /*         Runs when an arrow is clicked. */ ?>
        button.addEventListener('click', () => {
<?php /*             Reads the current expanded state. */ ?>
            const expanded = button.getAttribute('aria-expanded') === 'true';
<?php /*             Flips the expanded state. */ ?>
            button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
<?php /*             Updates the tooltip to match the new state. */ ?>
            button.setAttribute('title', expanded ? 'Show the other applications of this student' : 'Hide the other applications of this student');
<?php /*             Marks the arrow as manually changed by the coordinator. */ ?>
            button.dataset.manual = '1';
<?php /*             Re-runs the filters with the new state. */ ?>
            apply();
<?php /*         Closes the arrow click handler. */ ?>
        });
<?php /*     Closes the loop over arrows. */ ?>
    });

<?php /*     Re-applies the filters whenever the search text changes. */ ?>
    search?.addEventListener('input', apply);
<?php /*     Listens for keys pressed in the search box. */ ?>
    search?.addEventListener('keydown', e => {
<?php /*         Checks for the Escape key. */ ?>
        if (e.key === 'Escape') {
<?php /*             Clears the search text. */ ?>
            search.value = '';
<?php /*             Resets every dropdown to 'All'. */ ?>
            selects.forEach(sel => { sel.value = ''; });
<?php /*             Re-applies the filters after clearing. */ ?>
            apply();
<?php /*         Closes the Escape condition. */ ?>
        }
<?php /*     Closes the search keydown handler. */ ?>
    });
<?php /*     Clears the search and filters when Clear is clicked. */ ?>
    clearBtn?.addEventListener('click', () => {
<?php /*         Clears the search box when it exists. */ ?>
        if (search) search.value = '';
<?php /*         Resets every dropdown. */ ?>
        selects.forEach(sel => { sel.value = ''; });
<?php /*         Re-applies the filters. */ ?>
        apply();
<?php /*     Closes the clear button handler. */ ?>
    });
<?php /*     Re-applies the filters when any dropdown changes. */ ?>
    selects.forEach(sel => sel.addEventListener('change', apply));
<?php /*     Applies the filters once when the page loads. */ ?>
    apply();
<?php /* Closes and runs the group filter script. */ ?>
})();
<?php /* Ends the JavaScript block. */ ?>
</script>

<?php /* Loads the shared footer and closing scripts. */ ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
