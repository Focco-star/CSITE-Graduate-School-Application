<?php /* Starts the PHP code for the student management page. */ ?>
<?php
// Loads config settings, helpers, demo data, and function libraries.
require_once __DIR__ . '/../../includes/config/config.php';

// Checks whether no session has been started yet.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so the coordinator's name can be read.
    session_start();
// Closes the session start check.
}

// Sets the page title used in the browser tab.
$pageTitle   = 'Manage Students';
// Tells the layout the signed-in user is a coordinator.
$role        = 'coordinator';
// Marks the 'students' sidebar link as active.
$currentPage = 'students';
// Uses the signed-in full name, or the demo coordinator name.
$userName    = !empty($_SESSION['user']['full_name']) ? $_SESSION['user']['full_name'] : ($mockCoordinator['name'] ?? 'Coordinator');


// Checks whether a usable PDO database connection is missing.
if (!isset($pdo) || !($pdo instanceof PDO)) {
    // Opens a try block for building the database connection.
    try {
        // Reads the database host setting, defaulting to localhost.
        $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
        // Reads the database name, defaulting to csite_grad_school.
        $dbName = defined('DB_NAME') ? DB_NAME : 'csite_grad_school';
        // Reads the database user, defaulting to root.
        $dbUser = defined('DB_USER') ? DB_USER : 'root';
        // Reads the database password, defaulting to empty.
        $dbPass = defined('DB_PASS') ? DB_PASS : '';
        // Creates the PDO connection to MySQL with utf8mb4 text.
        $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
            // Makes PDO throw exceptions instead of failing silently.
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Makes PDO return rows as associative arrays.
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Closes the options array and the PDO constructor.
        ]);
    // Catches any database connection error.
    } catch (PDOException $e) {
        // Sets the connection to null so the page can use demo data.
        $pdo = null;
    // Closes the connection error handler.
    }
// Closes the connection setup check.
}


// Checks the form was posted and an archive id was sent.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_id'])) {
    // Converts the submitted archive id into a whole number.
    $archiveId = (int) $_POST['archive_id'];
    // Checks we have a database and the id is a positive number.
    if ($pdo && $archiveId > 0) {
        // Opens a try block around the archive update.
        try {
            // Prepares the update that marks the student as archived now.
            $stmt = $pdo->prepare("UPDATE students SET archived_at = NOW() WHERE student_id = :id AND archived_at IS NULL");
            // Runs the update with the student id.
            $stmt->execute([':id' => $archiveId]);
            // Stores a success flash message about the archived student.
            setFlash('success', 'Student archived successfully. The record is retained for reporting.');
        // Catches any database error during the archive update.
        } catch (PDOException $e) {
            // Stores an error flash message explaining the archive failed.
            setFlash('error', 'The student could not be archived. Apply the database migration and try again.');
        // Closes the update error handler.
        }
    // Uses the demo archive function when no database is available.
    } elseif (function_exists('archiveStudentRecord')) {
        // Archives the student in the demo data store.
        archiveStudentRecord((string) $archiveId);
        // Stores a success flash message for the demo archive.
        setFlash('success', 'Student archived successfully.');
    // Closes the database / demo archive check.
    }
    // Redirects back to the student list after archiving.
    redirectTo('coordinator/students/manage.php');
// Closes the archive request block.
}


// Starts an empty list that will hold the student rows.
$students = [];

// Only queries the database when a connection exists.
if ($pdo) {
    // Opens a try block around the student query.
    try {
        // Begins the SQL that loads every active student with latest stage.
        $stmt = $pdo->query("
            SELECT 
                s.student_id AS id,
                COALESCE(
                    NULLIF(TRIM(CONCAT(s.last_name, ', ', s.first_name, IF(s.middle_initial IS NULL OR TRIM(REPLACE(s.middle_initial, '.', '')) = '', '', CONCAT(' ', TRIM(REPLACE(s.middle_initial, '.', '')))))), ''), 
                    u.full_name
                ) AS name,
                COALESCE(s.program, 'MSCS') AS program,
                s.track,
                s.enrollment_date AS enrollDate,
                latest_app.presentation_stage AS stageLabel,
                latest_app.status AS status
            FROM students s
            LEFT JOIN users u ON s.user_id = u.user_id
            LEFT JOIN (
                SELECT a1.*
                FROM applications a1
                INNER JOIN (
                    SELECT user_id, MAX(submitted_at) AS max_submitted
                    FROM applications
                    GROUP BY user_id
                ) a2 ON a1.user_id = a2.user_id AND a1.submitted_at = a2.max_submitted
            ) latest_app ON latest_app.user_id = s.user_id
            WHERE s.archived_at IS NULL
            ORDER BY s.last_name ASC, s.first_name ASC
        ");
        // Stores all student rows returned by the query.
        $students = $stmt->fetchAll();
    // Catches a database error from the student query.
    } catch (PDOException $e) {
        // Falls back to an empty student list on error.
        $students = [];
    // Closes the query error handler.
    }
// Closes the database query check.
}





// Uses demo students when there is no database and the list is empty.
if (!$pdo && empty($students) && function_exists('storeGet')) {
    // Filters the demo student list to drop archived records.
    $students = array_values(array_filter(storeGet('students') ?? [], static function ($s) {
        // Keeps only demo students without an archived date.
        return empty($s['archivedAt'] ?? '');
    // Closes the filter callback and array_values call.
    }));
// Closes the demo student fallback check.
}

// Lists the status values used to build the status filter dropdown.
$statusFilters = ['not_started','submitted','under_review','for_payment','payment_recorded','ready_for_presentation','scheduled','approved','requires_revision','completed'];

// Loads the shared header and navigation.
require_once __DIR__ . '/../../includes/layout/header.php';
// Closes PHP so the HTML below is printed.
?>

<?php /* Opens the page header area. */ ?>
<div class="page-header">
<?php /*     Shows the big heading 'Student Management'. */ ?>
    <h2>Student Management</h2>
<?php /*     Describes the page as graduate student records and progress. */ ?>
    <p>View CSITE graduate student records and application progress.</p>
<?php /* Closes the page header area. */ ?>
</div>

<?php /* Opens the main card that holds the filters and table. */ ?>
<div class="card">
<?php /*     Opens the card header row. */ ?>
    <div class="card-header">
<?php /*         Heading 'Graduate Students' plus a slot for the match count. */ ?>
        <h3>Graduate Students <span class="filter-count" data-filter-count></span></h3>
<?php /*     Closes the card header row. */ ?>
    </div>
<?php /*     Opens the card body. */ ?>
    <div class="card-body">
<?php /*         Filter bar that controls the student table by its id. */ ?>
        <div class="filter-bar" data-filter-table="#studentsTable">
<?php /*             Wrapper for the search icon and search box. */ ?>
            <div class="filter-search">
<?php /*                 Shows a small magnifying glass icon. */ ?>
                <i class="fas fa-search"></i>
<?php /*                 Search box that filters by name, program, stage, or status. */ ?>
                <input type="search" data-filter-q placeholder="Search by name, program, stage, or status...">
<?php /*             Closes the search wrapper. */ ?>
            </div>
<?php /*             Clear button that resets all filters, hidden until used. */ ?>
            <button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button>
<?php /*             Program dropdown that filters the table by program code. */ ?>
            <select data-filter="program">
<?php /*                 Default option meaning no program filter. */ ?>
                <option value="">All Programs</option>
<?php /*                 Loops over each program code in the PROGRAMS list. */ ?>
                <?php foreach (array_keys(PROGRAMS) as $code): ?>
<?php /*                 Shows one program code as a dropdown option. */ ?>
                <option><?= htmlspecialchars($code) ?></option>
<?php /*                 Ends the program option loop. */ ?>
                <?php endforeach; ?>
<?php /*             Closes the program dropdown. */ ?>
            </select>
<?php /*             Status dropdown that filters the table by status. */ ?>
            <select data-filter="status">
<?php /*                 Default option meaning no status filter. */ ?>
                <option value="">All Statuses</option>
<?php /*                 Loops over each status key in the status filter list. */ ?>
                <?php foreach ($statusFilters as $key): ?>
<?php /*                 Shows the status label, or a capitalised key as fallback. */ ?>
                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars(STATUSES[$key]['label'] ?? ucfirst($key)) ?></option>
<?php /*                 Ends the status option loop. */ ?>
                <?php endforeach; ?>
<?php /*             Closes the status dropdown. */ ?>
            </select>
<?php /*         Closes the filter bar. */ ?>
        </div>
<?php /*         Wraps the table so it can scroll sideways on small screens. */ ?>
        <div class="table-responsive">
<?php /*             Starts the students data table with the id studentsTable. */ ?>
            <table class="data-table" id="studentsTable">
<?php /*                 Opens the table head section. */ ?>
                <thead>
<?php /*                     Header row: Student, Program, Track, Stage, Enrolled, Status, Actions. */ ?>
                    <tr><th>Student</th><th>Program</th><th>Track</th><th>Current Stage</th><th>Enrolled</th><th>Status</th><th>Actions</th></tr>
<?php /*                 Closes the table head section. */ ?>
                </thead>
<?php /*                 Starts the table body. */ ?>
                <tbody>
<?php /*                     Loops over each student row and prepares display values. */ ?>
                    <?php foreach ($students as $s):
                        // Gets the student's display name, or a fallback 'Student'.
                        $name = function_exists('studentDisplayName') ? studentDisplayName($s) : ($s['name'] ?? 'Student');
                        // Gets the stage label, or 'Not Started' when unknown.
                        $stageLabel = !empty($s['stageLabel']) ? $s['stageLabel'] : (function_exists('displayStageLabel') ? displayStageLabel($s) : 'Not Started');
                        // Gets the student's track, guessing from the program if missing.
                        $rawTrack = $s['track'] ?? (function_exists('getTrackForProgram') ? getTrackForProgram($s['program']) : 'thesis');
                        // Gets the readable track name, or capitalises the raw track.
                        $trackLabel = $s['trackLabel'] ?? (function_exists('getTrackLabel') ? getTrackLabel($rawTrack) : ucfirst($rawTrack));
                        // Gets the application status, defaulting to not_started.
                        $status = !empty($s['status']) ? $s['status'] : 'not_started';
                        // Gets the enrolment date, defaulting to 2024-08-01.
                        $enrollDate = !empty($s['enrollDate']) ? $s['enrollDate'] : '2024-08-01';
                    // Closes the setup block and reopens PHP output mode.
                    ?>
<?php /*                     Starts the row and stores its program for filtering. */ ?>
                    <tr data-program="<?= htmlspecialchars($s['program']) ?>"
<?php /*                         Stores the status on the row for the status filter. */ ?>
                        data-status="<?= htmlspecialchars($status) ?>"
<?php /*                         Stores lowercase searchable text for the search box. */ ?>
                        data-search="<?= htmlspecialchars(strtolower($name . ' ' . $s['program'] . ' ' . $stageLabel . ' ' . $status . ' ' . $trackLabel)) ?>">
<?php /*                         Shows the student's name in bold. */ ?>
                        <td><strong><?= htmlspecialchars($name) ?></strong></td>
<?php /*                         Shows the program code. */ ?>
                        <td><?= htmlspecialchars($s['program']) ?></td>
<?php /*                         Shows the track as a small rounded gray pill. */ ?>
                        <td><span style="font-size:0.75rem;background:var(--gray-100);padding:2px 8px;border-radius:99px;"><?= htmlspecialchars($trackLabel) ?></span></td>
<?php /*                         Shows the current presentation stage. */ ?>
                        <td><?= htmlspecialchars($stageLabel) ?></td>
<?php /*                         Shows enrolment month and year, adding a day when missing. */ ?>
                        <td><?= date('M Y', strtotime($enrollDate . (strlen($enrollDate) === 7 ? '-01' : ''))) ?></td>
<?php /*                         Shows a coloured status badge for the application. */ ?>
                        <td><?= statusBadge($status) ?></td>
<?php /*                         Opens the actions cell for the row buttons. */ ?>
                        <td class="actions">
<?php /*                             View button linking to the student profile page. */ ?>
                            <a href="<?= url('coordinator/students/view.php?id=' . urlencode($s['id'])) ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i> View</a>
<?php /*                             Archive button that opens the confirm dialog with student data. */ ?>
                            <button type="button" class="btn btn-sm btn-danger" data-archive-open data-student-id="<?= htmlspecialchars($s['id']) ?>" data-student-name="<?= htmlspecialchars($name) ?>"><i class="fas fa-box-archive"></i> Archive</button>
<?php /*                         Closes the actions cell. */ ?>
                        </td>
<?php /*                     Closes the table row. */ ?>
                    </tr>
<?php /*                     Ends the loop over students. */ ?>
                    <?php endforeach; ?>
<?php /*                     Row shown when filters match nothing; hidden when students exist. */ ?>
                    <tr data-filter-empty <?= $students ? 'hidden' : '' ?>>
<?php /*                         Wide centered message saying no students match the search. */ ?>
                        <td colspan="7" style="text-align:center;color:var(--gray-400);padding:2rem;">No students match your search or filters.</td>
<?php /*                     Closes the empty-results row. */ ?>
                    </tr>
<?php /*                 Closes the table body. */ ?>
                </tbody>
<?php /*             Closes the students table. */ ?>
            </table>
<?php /*         Closes the scrolling table wrapper. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the main student card. */ ?>
</div>

<?php /* Opens the hidden overlay for the archive confirmation dialog. */ ?>
<div class="modal-overlay" id="archiveStudentModal" aria-hidden="true">
<?php /*     The dialog box itself, marked as a modal for screen readers. */ ?>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="archiveStudentTitle">
<?php /*         Dialog header with the question and a close X button. */ ?>
        <div class="modal-header"><h3 id="archiveStudentTitle">Archive student record?</h3><button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button></div>
<?php /*         Dialog body explaining what archiving does to the record. */ ?>
        <div class="modal-body"><p>Archive <strong id="archiveStudentName"></strong>? The student will be removed from active lists, but their record and application history will be retained.</p></div>
<?php /*         Form that submits the archive request back to this page. */ ?>
        <form method="post" action="<?= url('coordinator/students/manage.php') ?>" class="modal-footer">
<?php /*             Hidden field that stores the id of the student to archive. */ ?>
            <input type="hidden" name="archive_id" id="archiveStudentId">
<?php /*             Cancel button that closes the dialog without changes. */ ?>
            <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
<?php /*             Submit button that archives the student. */ ?>
            <button type="submit" class="btn btn-danger"><i class="fas fa-box-archive"></i> Archive Student</button>
<?php /*         Closes the archive form. */ ?>
        </form>
<?php /*     Closes the dialog box. */ ?>
    </div>
<?php /* Closes the modal overlay. */ ?>
</div>

<?php /* Opens the JavaScript block for the archive dialog. */ ?>
<script>
<?php /* Adds a click handler to every archive button on the page. */ ?>
document.querySelectorAll('[data-archive-open]').forEach(button => button.addEventListener('click', () => {
<?php /*     Copies the student id into the hidden form field. */ ?>
    document.getElementById('archiveStudentId').value = button.dataset.studentId;
<?php /*     Copies the student name into the dialog question. */ ?>
    document.getElementById('archiveStudentName').textContent = button.dataset.studentName;
<?php /*     Shows the dialog by adding the 'active' class. */ ?>
    document.getElementById('archiveStudentModal').classList.add('active');
<?php /* Closes the click handler callback. */ ?>
}));
<?php /* Closes the JavaScript block. */ ?>
</script>

<?php /* Loads the shared footer and closes the page. */ ?>
<?php require_once __DIR__ . '/../../includes/layout/footer.php'; ?>
