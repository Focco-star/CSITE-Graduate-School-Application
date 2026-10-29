<?php /* Opens PHP mode so the server can run the schedule form logic. */ ?>
<?php
// Loads config.php, which holds site settings and the url() helper.
require_once __DIR__ . '/../../includes/config/config.php';
// Loads db.php, the DB class used for every database query on this page.
require_once __DIR__ . '/../../includes/config/db.php';

// Checks that no session is running yet, so it is not started twice.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so the logged-in coordinator can be recognised.
    session_start();
// Closes the if that starts the session.
}

// Reads the logged-in user array from the session, or an empty array.
$sessionUser = $_SESSION['user'] ?? [];
// If the user is missing or is not a coordinator, block access.
if (!is_array($sessionUser) || strtolower((string) ($sessionUser['role'] ?? '')) !== 'coordinator') {
    // Sends a non-coordinator to the coordinator login page.
    redirectTo('coordinator/login.php');
// Closes the if that guarded this page for coordinators only.
}

// Sets the page title shown in the browser tab.
$pageTitle   = 'Schedule Presentation';
// Tells the header this page belongs to the coordinator role.
$role        = 'coordinator';
// Marks the sidebar item for schedules as the active one.
$currentPage = 'schedule';
// Takes the coordinator's display name for the header greeting.
$userName    = $mockCoordinator['name'];

// Starts with the sample student list kept in the session store.
$students = storeGet('students');
// Starts a try block so a database error does not break the page.
try {
    // Runs the student query and stores the result in $databaseStudents.
    $databaseStudents = DB::query(
        // Selects student details and prefers the adviser saved on the application.
        "SELECT s.student_id, s.first_name, s.last_name, s.middle_initial,
                s.program, s.track,
                COALESCE(NULLIF(TRIM(ad.adviser), ''), s.adviser_name) AS adviser,
                u.email
         FROM students s
         INNER JOIN users u ON u.user_id = s.user_id
         LEFT JOIN application_drafts ad ON ad.user_id = s.user_id
         WHERE s.archived_at IS NULL AND u.role = 'student'
         ORDER BY s.last_name, s.first_name"
    // Fetches all matching rows as an array of student rows.
    )->fetchAll();
    // Only replaces the sample list when the database returned students.
    if ($databaseStudents) {
        // Maps every database student row through a small clean-up function.
        $students = array_map(static function (array $student): array {
            // Converts the stored program into the program code used by the screen.
            $student['program'] = programCodeForStudent($student);
            // Keeps the track, or works one out from the program when it is blank.
            $student['track'] = $student['track'] ?: getTrackForProgram($student['program']);
            // Returns the cleaned student row into the new list.
            return $student;
        // Applies that function to all rows and stores them in $students.
        }, $databaseStudents);
    // Closes the if that checked for database students.
    }
// Catches any database or connection error quietly.
} catch (Throwable $e) {
    // Note: keeps the prototype student list when the database is offline.
    // Keep the prototype student list available when the database is unavailable.
// Closes the try/catch that loaded the students.
}
// Loads the panel member choices used by the three panel drop-downs.
$panelOpts = panelSelectOptions();
// Prepares an empty list for the adviser pool options.
$adviserOpts = [];
// Prepares an empty list for the documenter pool options.
$documenterOpts = [];
// Prepares an empty error message about the documenter pool.
$documenterPoolError = '';
// Starts a try block so the adviser pool can fail safely.
try {
    // Loads adviser names, available ones first, for the adviser drop-down.
    $adviserOpts = DB::query("SELECT name, availability FROM advisor_pool ORDER BY availability = 'available' DESC, name ASC")->fetchAll();
// Catches a database error so the adviser list simply stays empty.
} catch (Throwable $e) {
// Closes the try/catch that loaded the adviser pool.
}
// Starts a try block so the documenter pool can fail safely.
try {
    // Stores the documenter query result for the loop further down.
    $documenterOpts = DB::query(
        // Selects available documenter name parts and qualification.
        "SELECT first_name, middle_name, last_name, qualification
         FROM documenter_pool
         WHERE availability = 'available'
         ORDER BY last_name, first_name, middle_name"
    // Runs the query and fetches every available documenter row.
    )->fetchAll();
// Catches a database error, usually because the pool table is missing.
} catch (Throwable $e) {
    // Logs the documenter pool error to the server error log.
    error_log('[documenter pool] ' . $e->getMessage());
    // Sets a message telling the coordinator to apply the pool migration.
    $documenterPoolError = 'The documenter pool is unavailable. Apply the documenter-pool migration before scheduling a presentation.';
// Closes the try/catch for the documenter pool.
}
// Prepares the final list of documenter option values.
$documenterOptions = [];
// Loops over each documenter row from the database.
foreach ($documenterOpts as $documenter) {
    // Builds one option entry holding a name and a qualification.
    $documenterOptions[] = [
        // Joins last, first and middle name into a 'Last, First Middle' label.
        'name' => trim($documenter['last_name'] . ', ' . $documenter['first_name'] . ' ' . ($documenter['middle_name'] ?? '')),
        // Stores the qualification text, or empty text when it is null.
        'qualification' => (string) ($documenter['qualification'] ?? ''),
    // Closes the option entry array.
    ];
// Closes the loop over the documenter rows.
}
// Starts the preselected student email as empty text.
$preselected = '';
// Only runs the preselect logic when an app id is in the URL.
if (!empty($_GET['app'])) {
    // Finds the application whose id came from the query string.
    $preApp = findApplication((int) $_GET['app']);
    // Uses that application's student email as the preselected student.
    $preselected = $preApp['studentEmail'] ?? '';
// Closes the if that handled the app query parameter.
}

// Prepares an empty map from student email to stage information.
$stageMap = [];
// Loops over every student to build the stage data map.
foreach ($students as $s) {
    // Gets the student's track, or works it out from the program.
    $track = $s['track'] ?? getTrackForProgram($s['program']);
    // Note (1 of 2): every stage of the student's track is listed.
    // Every stage of the student's track is listed, with the ones that are
    // Note (2 of 2): scheduled or not reached stages show as locked.
    // already scheduled or not reached yet locked (the form shows why).
    // Stores the track, program and stage choices under this student email.
    $stageMap[$s['email']] = [
        // Converts the track key into the label shown on the form.
        'track' => getTrackLabel($track),
        // Keeps the program code for the form's Program field.
        'program' => $s['program'],
        // Keeps the adviser's saved selection for the adviser drop-down.
        'adviser' => trim((string) ($s['adviser'] ?? '')),
        // Asks for the stage choices with their lock state and reasons.
        'stages' => studentPresentationStageChoices($s['email'], $s),
        // Asks for the label of the stage the student is currently working on.
        'currentStage' => studentCurrentStageLabel($s['email'], $s),
    // Closes the entry array for this student.
    ];
// Closes the loop that built the stage map.
}

// Only runs the save logic when the form was submitted by POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Reads the chosen student email and trims any extra spaces.
    $email = trim((string) ($_POST['studentEmail'] ?? ''));
    // Looks the student up in the session store by email.
    $st = findStudentByEmail($email);
    // If that lookup found nobody, try the database lookup below.
    if (!$st) {
        // Starts a try block for the database student lookup.
        try {
            // Prepares a query that finds one student row by email address.
            $studentStmt = DB::getConnection()->prepare(
                // Selects student details and prefers the adviser saved on the application.
                'SELECT s.student_id, s.first_name, s.last_name, s.middle_initial,
                        s.program, s.track,
                        COALESCE(NULLIF(TRIM(ad.adviser), ""), s.adviser_name) AS adviser,
                        u.email
                 FROM students s
                 INNER JOIN users u ON u.user_id = s.user_id
                 LEFT JOIN application_drafts ad ON ad.user_id = s.user_id
                 WHERE u.email = :email AND s.archived_at IS NULL
                 LIMIT 1'
            // Closes the prepare call after the SQL text ends.
            );
            // Runs the query with the email filled into the placeholder.
            $studentStmt->execute(['email' => $email]);
            // Fetches one row, or null when no student matched.
            $st = $studentStmt->fetch() ?: null;
            // If a row was found, clean up its program and track values.
            if ($st) {
                // Converts the program value into the standard program code.
                $st['program'] = programCodeForStudent($st);
                // Keeps the stored track, or works one out from the program.
                $st['track'] = $st['track'] ?: getTrackForProgram($st['program']);
            // Closes the if that cleaned the found student row.
            }
        // Catches any database error during the student lookup.
        } catch (Throwable $e) {
            // Leaves the student as null so the error message below can show.
            $st = null;
        // Closes the try/catch for the student lookup.
        }
    // Closes the if that tried the database when the store had no match.
    }
    // Reads the chosen presentation stage and trims spaces.
    $stage = trim($_POST['stage'] ?? '');
    // If no student or no stage came through, the form is incomplete.
    if (!$st || $stage === '') {
        // Sets a flash error asking for a student and a valid stage.
        setFlash('error', 'Select a student and a valid presentation stage.');
        // Sends the coordinator back to the schedule form.
        redirectTo('coordinator/schedule/add.php');
    // Closes the if that rejected an incomplete submission.
    }
    // Only continue when a student row was found.
    if ($st) {
        // Starts a note explaining that only valid stages may be scheduled.
        /*
         * The stage has to be one the student's track and workflow progress
         * allow. The list is the very same one the form offers, so a stage
         * that is not valid for this student is refused instead of being
         * scheduled.
         */
        // Prepares an empty list of stage labels allowed for this student.
        $availableStages = [];
        // Loops over the stages this student's track and progress allow.
        foreach (studentValidPresentationStages($email, $st) as $validStage) {
            // Adds each valid stage label as both key and value.
            $availableStages[$validStage['label']] = $validStage['label'];
        // Closes the loop over the valid stages.
        }
        // If the posted stage is not in the allowed list, refuse it.
        if (!isset($availableStages[$stage])) {
            // Note: when the stage exists but is locked, explain the reason.
            // When the stage exists but is locked, tell the coordinator why.
            // Starts an empty reason string for a locked stage.
            $lockedReason = '';
            // Loops over every stage choice to find the matching one.
            foreach (studentPresentationStageChoices($email, $st) as $choice) {
                // Checks whether this choice is the posted stage and is locked.
                if ($choice['label'] === $stage && $choice['locked']) {
                    // Saves the lock reason so the coordinator knows why.
                    $lockedReason = (string) $choice['reason'];
                    // Stops searching because the matching stage was found.
                    break;
                // Closes the if that matched a locked stage.
                }
            // Closes the loop over the stage choices.
            }
            // Shows the lock reason, or a general invalid-stage message.
            setFlash('error', $lockedReason !== '' ? $lockedReason : 'Select a presentation stage that is valid for this student’s track and workflow progress.');
            // Sends the coordinator back to the form to pick again.
            redirectTo('coordinator/schedule/add.php');
        // Closes the if that rejected an invalid or locked stage.
        }
        // Replaces the posted stage with the clean label from the list.
        $stage = $availableStages[$stage];
        // Reads the posted presentation date and trims spaces.
        $displayDate = trim($_POST['date'] ?? '');
        // Only reformat the date when the coordinator actually typed one.
        if ($displayDate !== '') {
            // Converts the typed date text into a Unix timestamp.
            $ts = strtotime($displayDate);
            // If the date is readable, continue with the formatting.
            if ($ts) {
                // Formats the date as 'Mon d, Y' for display and storage.
                $displayDate = date('M j, Y', $ts);
            // Closes the if that checked the readable date.
            }
        // Closes the if that handled a non-empty date.
        }
        // Reads the posted presentation time and trims spaces.
        $displayTime = trim($_POST['time'] ?? '');
        // Only reformat the time when the coordinator actually typed one.
        if ($displayTime !== '') {
            // Parses the time against a dummy date so strtotime understands it.
            $tts = strtotime('2000-01-01 ' . $displayTime);
            // If the time is readable, continue with the formatting.
            if ($tts) {
                // Formats the time as a 12-hour clock with AM or PM.
                $displayTime = date('g:i A', $tts);
            // Closes the if that checked the readable time.
            }
        // Closes the if that handled a non-empty time.
        }
        // Joins the chosen panel members into one comma-separated text.
        $panel = implode(', ', array_filter([$_POST['panel1'] ?? '', $_POST['panel2'] ?? '', $_POST['panel3'] ?? '']));
        // Reads the venue text and trims spaces.
        $venue = trim($_POST['venue'] ?? '');
        // Gets the newest application of this student from the store.
        $app = latestApplicationForEmail($email);
        // Reads its id as an integer, using 0 when there is none.
        $applicationId = (int) ($app['id'] ?? 0);
        // Looks up the database user id for this student email.
        $databaseUserId = databaseUserIdForEmail($email);
        // Only query the database when a real user id was found.
        if ($databaseUserId > 0) {
            // Finds the database application id for this student and stage.
            $databaseApplicationId = databaseApplicationIdForStudent($databaseUserId, $stage);
            // Only continue when a matching application row was found.
            if ($databaseApplicationId !== null) {
                // Starts a note about matching the schedule to the right application.
                /*
                 * A schedule belongs to the application of the chosen stage.
                 * The lookup falls back to the newest application, so make sure
                 * the row it returned is really the stage the coordinator
                 * picked instead of attaching the schedule to another one.
                 */
                // Loads the application row so its stage can be checked.
                $stageApplication = applicationById($databaseApplicationId);
                // If the stored stage differs from the chosen stage, it is the wrong row.
                if ($stageApplication && stageKeyFromLabel((string) ($stageApplication['stage'] ?? '')) !== stageKeyFromLabel($stage)) {
                    // Sets an error saying this stage has no application to schedule.
                    setFlash('error', 'The student has no application for the ' . $stage . ', so it cannot be scheduled.');
                    // Sends the coordinator back to the schedule form.
                    redirectTo('coordinator/schedule/add.php');
                // Closes the if that found a stage mismatch.
                }
                // Uses the database application id for the new schedule.
                $applicationId = $databaseApplicationId;
            // Closes the if that checked a real database application.
            }
        // Closes the if that looked up a database user id.
        }
        // Saves the new schedule row through addScheduleRecord().
        addScheduleRecord([
            // Passes the student email into the new schedule record.
            'studentEmail' => $email,
            // Passes the student's display name for the schedule list.
            'studentName' => studentDisplayName($st),
            // Passes the application id, or null when there is no application.
            'applicationId' => $applicationId > 0 ? $applicationId : null,
            // Passes the chosen presentation stage label.
            'stage' => $stage,
            // Passes the formatted presentation date.
            'date' => $displayDate,
            // Passes the formatted presentation time.
            'time' => $displayTime,
            // Passes the venue text.
            'venue' => $venue,
            // Uses the panel text, or 'TBD' when nobody was chosen.
            'panel' => $panel ?: 'TBD',
            // Formats the adviser name into the stored 'Last, First M.' style.
            'adviser' => formatPersonName($_POST['adviser'] ?? ''),
            // Passes the chosen documenter name.
            'documentor' => trim($_POST['documentor'] ?? ''),
            // Stores confirmed only when that status was chosen, else pending.
            'status' => ($_POST['status'] ?? 'pending') === 'confirmed' ? 'confirmed' : 'pending',
        // Closes the array of values given to addScheduleRecord().
        ]);
        // addScheduleRecord() synchronizes the application status: a confirmed
        // schedule moves the stage to Scheduled for Presentation, while a
        // pending schedule leaves the stage at Ready for Presentation.
        // Sends the coordinator to the schedule list after saving.
        redirectTo('coordinator/schedule/manage.php');
    // Closes the if that handled a found student and stage.
    }
// Closes the POST block that saved the schedule.
}

// Reloads the panel member choices for drawing the form.
$panelOpts = panelSelectOptions();
// Resets the adviser pool option list to empty.
$adviserOpts = [];
// Resets the documenter pool option list to empty.
$documenterOpts = [];
// Resets the documenter pool error message to empty.
$documenterPoolError = '';
// Starts a try block to load the adviser pool for the form.
try {
    // Loads adviser names with available ones first for the drop-down.
    $adviserOpts = DB::query("SELECT name, availability FROM advisor_pool ORDER BY availability = 'available' DESC, name ASC")->fetchAll();
// Catches a database error so the adviser list stays empty.
} catch (Throwable $e) {
// Closes the try/catch that loaded the adviser pool.
}
// Starts a try block to load the documenter pool for the form.
try {
    // Stores the documenter query result for the loop below.
    $documenterOpts = DB::query(
        // Selects available documenter names and qualifications.
        "SELECT first_name, middle_name, last_name, qualification
         FROM documenter_pool
         WHERE availability = 'available'
         ORDER BY last_name, first_name, middle_name"
    // Runs the query and fetches all available documenter rows.
    )->fetchAll();
// Catches a database error, usually a missing pool table.
} catch (Throwable $e) {
    // Logs the documenter pool error to the server error log.
    error_log('[documenter pool] ' . $e->getMessage());
    // Sets the message telling the coordinator to apply the migration.
    $documenterPoolError = 'The documenter pool is unavailable. Apply the documenter-pool migration before scheduling a presentation.';
// Closes the try/catch for the documenter pool.
}
// Prepares the list of documenter select options.
$documenterOptions = [];
// Loops over each documenter row returned above.
foreach ($documenterOpts as $documenter) {
    // Builds one option entry holding a name and a qualification.
    $documenterOptions[] = [
        // Joins the name parts into the 'Last, First Middle' label.
        'name' => trim($documenter['last_name'] . ', ' . $documenter['first_name'] . ' ' . ($documenter['middle_name'] ?? '')),
        // Stores the qualification text or an empty string.
        'qualification' => (string) ($documenter['qualification'] ?? ''),
    // Closes the option entry array.
    ];
// Closes the loop over the documenter rows.
}
// Starts the preselected student email as empty text.
$preselected = '';
// Only preselect when an application id is in the URL.
if (!empty($_GET['app'])) {
    // Finds the application using the id from the query string.
    $preApp = findApplication((int) $_GET['app']);
    // Uses its student email to preselect the student drop-down.
    $preselected = $preApp['studentEmail'] ?? '';
// Closes the if that handled the app query parameter.
}

// Prepares an empty map from student email to stage details.
$stageMap = [];
// Loops over every student to build the stage map again.
foreach ($students as $s) {
    // Gets the student's track, or works it out from the program.
    $track = $s['track'] ?? getTrackForProgram($s['program']);
    // Note (1 of 2): every stage of the track is listed for the student.
    // Every stage of the student's track is listed, with the ones that are
    // Note (2 of 2): already scheduled or unreached stages are locked.
    // already scheduled or not reached yet locked (the form shows why).
    // Stores track label, program and stages under this student email.
    $stageMap[$s['email']] = [
        // Converts the track key into the readable track label.
        'track' => getTrackLabel($track),
        // Keeps the program code for the Program field.
        'program' => $s['program'],
        // Gets the stage choices including lock state and reason.
        'stages' => studentPresentationStageChoices($s['email'], $s),
        // Gets the label of the student's current stage.
        'currentStage' => studentCurrentStageLabel($s['email'], $s),
    // Closes the entry array for this student.
    ];
// Closes the loop that built the stage map.
}

// Loads the shared header, which prints the page top and sidebar.
require_once __DIR__ . '/../../includes/layout/header.php';
// Closes PHP mode and switches back to HTML output.
?>

<?php /* Page header wrapper that groups the title and description. */ ?>
<div class="page-header">
<?php /*     Shows the page heading 'Schedule Presentation'. */ ?>
    <h2>Schedule Presentation</h2>
<?php /*     Explains what the coordinator sets on this page. */ ?>
    <p>Set the presentation stage, date, venue, and Academic Assessment Team. The stages offered follow the student's track and their workflow progress.</p>
<?php /* Closes the page header wrapper. */ ?>
</div>

<?php /* Form that posts the new schedule back to this same page. */ ?>
<form method="post" action="<?= url('coordinator/schedule/add.php') ?>" data-validate>
<?php /*     First card that holds the student and stage fields. */ ?>
    <div class="card">
<?php /*         Card header titled 'Student & Stage'. */ ?>
        <div class="card-header"><h3>Student & Stage</h3></div>
<?php /*         Card body wrapper for the form fields. */ ?>
        <div class="card-body">
<?php /*             Row that puts the student and course type fields side by side. */ ?>
            <div class="form-row">
<?php /*                 Field wrapper for the student drop-down. */ ?>
                <div class="form-field">
<?php /*                     Label for the student field, marked as required. */ ?>
                    <label>Student <span class="required">*</span></label>
<?php /*                     Drop-down that submits the chosen student's email address. */ ?>
                    <select name="studentEmail" id="schedStudent" required>
<?php /*                         Placeholder option shown before a student is picked. */ ?>
                        <option value="">Select student</option>
<?php /*                         PHP loop: one option for each student in the list. */ ?>
                        <?php foreach ($students as $s): ?>
<?php /*                         Option value is the email, preselected when it matches the URL app. */ ?>
                        <option value="<?= htmlspecialchars($s['email']) ?>" <?= $s['email'] === $preselected ? 'selected' : '' ?>>
<?php /*                             Shows the student's display name and program code. */ ?>
                            <?= htmlspecialchars(studentDisplayName($s)) ?> (<?= htmlspecialchars($s['program']) ?>)
<?php /*                         Closes the student option element. */ ?>
                        </option>
<?php /*                         Closes the loop that printed the student options. */ ?>
                        <?php endforeach; ?>
<?php /*                     Closes the student drop-down. */ ?>
                    </select>
<?php /*                 Closes the student field wrapper. */ ?>
                </div>
<?php /*                 Field wrapper for the read-only course type box. */ ?>
                <div class="form-field">
<?php /*                     Label for the course type (track) field. */ ?>
                    <label>Course Type</label>
<?php /*                     Read-only input that JavaScript fills with the student's track. */ ?>
                    <input type="text" id="schedTrack" value="" readonly>
<?php /*                 Closes the course type field wrapper. */ ?>
                </div>
<?php /*             Closes the row holding student and course type. */ ?>
            </div>
<?php /*             Row that puts the stage and program fields side by side. */ ?>
            <div class="form-row">
<?php /*                 Field wrapper for the presentation stage drop-down. */ ?>
                <div class="form-field">
<?php /*                     Label for the stage field, marked as required. */ ?>
                    <label>Presentation Stage <span class="required">*</span></label>
<?php /*                     Stage drop-down, filled by JavaScript once a student is chosen. */ ?>
                    <select name="stage" id="schedStage" required>
<?php /*                         Placeholder option telling the coordinator to pick a student first. */ ?>
                        <option value="">Select a student first</option>
<?php /*                     Closes the stage drop-down. */ ?>
                    </select>
<?php /*                     Hint paragraph that explains the stage choices. */ ?>
                    <p class="field-hint" id="schedStageHint"></p>
<?php /*                     Hidden warning box that explains why a stage is locked. */ ?>
                    <div class="alert alert-warning" id="schedStageLockNotice" role="status" style="display:none;margin:0.5rem 0 0;">
<?php /*                         Lock icon and an empty span that JavaScript fills with the reason. */ ?>
                        <i class="fas fa-lock"></i><span></span>
<?php /*                     Closes the lock warning box. */ ?>
                    </div>
<?php /*                 Closes the stage field wrapper. */ ?>
                </div>
<?php /*                 Field wrapper for the read-only program box. */ ?>
                <div class="form-field">
<?php /*                     Label for the program field. */ ?>
                    <label>Program</label>
<?php /*                     Read-only input that JavaScript fills with the program code. */ ?>
                    <input type="text" id="schedProgram" value="" readonly>
<?php /*                 Closes the program field wrapper. */ ?>
                </div>
<?php /*             Closes the row holding stage and program. */ ?>
            </div>
<?php /*         Closes the card body of Student & Stage. */ ?>
        </div>
<?php /*     Closes the Student & Stage card. */ ?>
    </div>

<?php /*     Second card that holds the date, time, venue and status. */ ?>
    <div class="card">
<?php /*         Card header titled 'Schedule Details'. */ ?>
        <div class="card-header"><h3>Schedule Details</h3></div>
<?php /*         Card body wrapper for the schedule fields. */ ?>
        <div class="card-body">
<?php /*             Row that puts the date and time fields side by side. */ ?>
            <div class="form-row">
<?php /*                 Field wrapper for the presentation date. */ ?>
                <div class="form-field">
<?php /*                     Label for the date field, marked as required. */ ?>
                    <label>Presentation Date <span class="required">*</span></label>
<?php /*                     Wrapper that styles the date picker input. */ ?>
                    <div class="schedule-picker-wrap">
<?php /*                         Date input named date, required, using the browser date picker. */ ?>
                        <input class="schedule-picker" type="date" name="date" required>
<?php /*                     Closes the date picker wrapper. */ ?>
                    </div>
<?php /*                 Closes the date field wrapper. */ ?>
                </div>
<?php /*                 Field wrapper for the presentation time. */ ?>
                <div class="form-field">
<?php /*                     Label for the time field, marked as required. */ ?>
                    <label>Presentation Time <span class="required">*</span></label>
<?php /*                     Wrapper that styles the time picker input. */ ?>
                    <div class="schedule-picker-wrap">
<?php /*                         Time input named time, required, using the browser time picker. */ ?>
                        <input class="schedule-picker schedule-time-picker" type="time" name="time" required>
<?php /*                     Closes the time picker wrapper. */ ?>
                    </div>
<?php /*                 Closes the time field wrapper. */ ?>
                </div>
<?php /*             Closes the row holding date and time. */ ?>
            </div>
<?php /*             Field wrapper for the venue text box. */ ?>
            <div class="form-field">
<?php /*                 Label for the venue field, marked as required. */ ?>
                <label>Venue <span class="required">*</span></label>
<?php /*                 Text input for the venue with an example placeholder. */ ?>
                <input type="text" name="venue" required placeholder="e.g. CSITE Seminar Room, Room 204">
<?php /*             Closes the venue field wrapper. */ ?>
            </div>
<?php /*             Field wrapper for the schedule status drop-down. */ ?>
            <div class="form-field">
<?php /*                 Label for the status field. */ ?>
                <label>Schedule Status</label>
<?php /*                 Drop-down that chooses pending or confirmed status. */ ?>
                <select name="status">
<?php /*                     Default option keeps the schedule as pending confirmation. */ ?>
                    <option value="pending">Pending Confirmation</option>
<?php /*                     Option marks the schedule as already confirmed. */ ?>
                    <option value="confirmed">Confirmed</option>
<?php /*                 Closes the status drop-down. */ ?>
                </select>
<?php /*             Closes the status field wrapper. */ ?>
            </div>
<?php /*             Info panel that lists what must be done before scheduling. */ ?>
            <div style="margin-top:0.5rem;padding:0.9rem 1rem;background:rgba(37,99,235,0.04);border-radius:var(--radius);border:1px solid rgba(37,99,235,0.12);font-size:0.82rem;">
<?php /*                 Panel title with a clipboard icon explaining the checklist. */ ?>
                <p style="font-weight:700;color:var(--gray-700);margin-bottom:0.5rem;"><i class="fas fa-clipboard-check" style="margin-right:5px;color:var(--adzu-navy);"></i>Prerequisites before scheduling:</p>
<?php /*                 Loops over the five prerequisite items shown as a checklist. */ ?>
                <?php foreach (['Paper submitted and verified','Adviser endorsement verified','Graduate School endorsement submitted and verified','Official receipt / payment recorded','Stage marked as Ready for Presentation'] as $req): ?>
<?php /*                 Row that holds one check icon and its requirement text. */ ?>
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.2rem;">
<?php /*                     Small green check icon marking a prerequisite. */ ?>
                    <i class="fas fa-check-circle" style="color:var(--success);font-size:0.75rem;"></i>
<?php /*                     Prints the prerequisite text, escaped for safety. */ ?>
                    <span style="color:var(--gray-600);"><?= htmlspecialchars($req) ?></span>
<?php /*                 Closes the prerequisite row. */ ?>
                </div>
<?php /*                 Closes the loop over the prerequisite items. */ ?>
                <?php endforeach; ?>
<?php /*             Closes the prerequisite info panel. */ ?>
            </div>
<?php /*         Closes the card body of Schedule Details. */ ?>
        </div>
<?php /*     Closes the Schedule Details card. */ ?>
    </div>

<?php /*     Third card for the Academic Assessment Team fields. */ ?>
    <div class="card">
<?php /*         Card header titled 'Academic Assessment Team'. */ ?>
        <div class="card-header"><h3>Academic Assessment Team</h3></div>
<?php /*         Card body wrapper for the team fields. */ ?>
        <div class="card-body">
<?php /*             Row holding the adviser drop-down. */ ?>
            <div class="form-row">
<?php /*                 Field wrapper for the adviser field. */ ?>
                <div class="form-field">
<?php /*                     Label for the adviser field, marked as required. */ ?>
                    <label>Adviser <span class="required">*</span></label>
<?php /*                     Adviser drop-down that submits the chosen adviser name. */ ?>
                    <select name="adviser" id="schedAdviser" required>
<?php /*                         Placeholder option shown before an adviser is picked. */ ?>
                        <option value="">Select adviser</option>
<?php /*                         Loops over adviser rows and flags those marked unavailable. */ ?>
                        <?php foreach ($adviserOpts as $adviser): $isUnavailable = ($adviser['availability'] ?? '') !== 'available'; ?>
<?php /*                         Option is disabled when the adviser is not available. */ ?>
                        <option value="<?= htmlspecialchars($adviser['name'], ENT_QUOTES, 'UTF-8') ?>" data-unavailable="<?= $isUnavailable ? 'true' : 'false' ?>" <?= $isUnavailable ? 'disabled' : '' ?>><?= htmlspecialchars($adviser['name']) ?><?= $isUnavailable ? ' [Unavailable]' : '' ?></option>
<?php /*                         Closes the loop over the adviser options. */ ?>
                        <?php endforeach; ?>
<?php /*                         Shows a disabled note when the adviser pool has no rows. */ ?>
                        <?php if (!$adviserOpts): ?><option value="" disabled>No available advisers found</option><?php endif; ?>
<?php /*                     Closes the adviser drop-down. */ ?>
                    </select>
<?php /*                 Closes the adviser field wrapper. */ ?>
                </div>
<?php /*             Closes the row holding the adviser drop-down. */ ?>
            </div>
<?php /*             Row that puts panel members 1 and 2 side by side. */ ?>
            <div class="form-row">
<?php /*                 Field wrapper for panel member 1. */ ?>
                <div class="form-field">
<?php /*                     Label for panel member 1, marked as required. */ ?>
                    <label>Panel Member 1 <span class="required">*</span></label>
<?php /*                     Panel 1 drop-down, sharing the panel-member-select class. */ ?>
                    <select name="panel1" class="panel-member-select" required>
<?php /*                         Placeholder option for panel member 1. */ ?>
                        <option value="">Select panel member</option>
<?php /*                         Loops over all panel members to fill panel drop-down 1. */ ?>
                        <?php foreach ($panelOpts as $p): ?><option><?= htmlspecialchars($p) ?></option><?php endforeach; ?>
<?php /*                     Closes the panel 1 drop-down. */ ?>
                    </select>
<?php /*                 Closes the panel 1 field wrapper. */ ?>
                </div>
<?php /*                 Field wrapper for panel member 2. */ ?>
                <div class="form-field">
<?php /*                     Label for panel member 2, marked as required. */ ?>
                    <label>Panel Member 2 <span class="required">*</span></label>
<?php /*                     Panel 2 drop-down, sharing the panel-member-select class. */ ?>
                    <select name="panel2" class="panel-member-select" required>
<?php /*                         Placeholder option for panel member 2. */ ?>
                        <option value="">Select panel member</option>
<?php /*                         Loops over all panel members to fill panel drop-down 2. */ ?>
                        <?php foreach ($panelOpts as $p): ?><option><?= htmlspecialchars($p) ?></option><?php endforeach; ?>
<?php /*                     Closes the panel 2 drop-down. */ ?>
                    </select>
<?php /*                 Closes the panel 2 field wrapper. */ ?>
                </div>
<?php /*             Closes the row holding panel members 1 and 2. */ ?>
            </div>
<?php /*             Row that puts panel member 3 and the documenter together. */ ?>
            <div class="form-row">
<?php /*                 Field wrapper for panel member 3. */ ?>
                <div class="form-field">
<?php /*                     Label for panel member 3, marked as required. */ ?>
                    <label>Panel Member 3 <span class="required">*</span></label>
<?php /*                     Panel 3 drop-down, sharing the panel-member-select class. */ ?>
                    <select name="panel3" class="panel-member-select" required>
<?php /*                         Placeholder option for panel member 3. */ ?>
                        <option value="">Select panel member</option>
<?php /*                         Loops over all panel members to fill panel drop-down 3. */ ?>
                        <?php foreach ($panelOpts as $p): ?><option><?= htmlspecialchars($p) ?></option><?php endforeach; ?>
<?php /*                     Closes the panel 3 drop-down. */ ?>
                    </select>
<?php /*                 Closes the panel 3 field wrapper. */ ?>
                </div>
<?php /*                 Field wrapper for the documenter drop-down. */ ?>
                <div class="form-field">
<?php /*                     Label for the documenter field, marked as required. */ ?>
                    <label>Documenter <span class="required">*</span></label>
<?php /*                     Documenter drop-down, disabled when the pool is unavailable. */ ?>
                    <select name="documentor" required <?= $documenterPoolError ? 'disabled' : '' ?>>
<?php /*                         Placeholder option shown before a documenter is picked. */ ?>
                        <option value="">Select documenter</option>
<?php /*                         Loops over the available documenter options. */ ?>
                        <?php foreach ($documenterOptions as $documenter): ?>
<?php /*                         Option value is the name; the label adds the qualification in brackets. */ ?>
                        <option value="<?= htmlspecialchars($documenter['name']) ?>"><?= htmlspecialchars($documenter['name'] . ($documenter['qualification'] !== '' ? ' (' . $documenter['qualification'] . ')' : '')) ?></option>
<?php /*                         Closes the loop over the documenter options. */ ?>
                        <?php endforeach; ?>
<?php /*                         Shows a disabled note when no documenter is available. */ ?>
                        <?php if (!$documenterPoolError && !$documenterOptions): ?><option value="" disabled>No available documenters found</option><?php endif; ?>
<?php /*                     Closes the documenter drop-down. */ ?>
                    </select>
<?php /*                 Closes the documenter field wrapper. */ ?>
                </div>
<?php /*             Closes the row holding panel 3 and the documenter. */ ?>
            </div>
<?php /*         Closes the card body of Academic Assessment Team. */ ?>
        </div>
<?php /*     Closes the Academic Assessment Team card. */ ?>
    </div>

<?php /*     Action row holding the submit and cancel buttons. */ ?>
    <div class="form-actions">
<?php /*         Submit button that finalizes and saves the schedule. */ ?>
        <button type="submit" class="btn btn-primary"><i class="fas fa-calendar-check"></i> Finalize Schedule</button>
<?php /*         Cancel link that goes back to the schedule list. */ ?>
        <a href="<?= url('coordinator/schedule/manage.php') ?>" class="btn btn-outline">Cancel</a>
<?php /*     Closes the action row. */ ?>
    </div>
<?php /* Closes the schedule form. */ ?>
</form>

<?php /* Starts a JavaScript block that fills the stage and team fields. */ ?>
<script>
<?php /* Wraps the script in a function so variables stay private. */ ?>
(function () {
<?php /*     Puts the PHP stage map into JavaScript as JSON. */ ?>
    const map = <?= json_encode($stageMap) ?>;
<?php /*     Gets the student drop-down element. */ ?>
    const student = document.getElementById('schedStudent');
<?php /*     Gets the stage drop-down element. */ ?>
    const stage = document.getElementById('schedStage');
    const adviser = document.getElementById('schedAdviser');
<?php /*     Gets the read-only course type input. */ ?>
    const track = document.getElementById('schedTrack');
<?php /*     Gets the read-only program input. */ ?>
    const program = document.getElementById('schedProgram');
<?php /*     Gets the hint paragraph under the stage drop-down. */ ?>
    const hint = document.getElementById('schedStageHint');
<?php /*     Gets the hidden lock warning box. */ ?>
    const lockNotice = document.getElementById('schedStageLockNotice');
<?php /*     Collects the three panel member drop-downs into an array. */ ?>
    const panelSelects = Array.from(document.querySelectorAll('.panel-member-select'));

<?php /*     Function that hides the lock warning box. */ ?>
    function hideLockNotice() {
<?php /*         Leaves early when the lock box is not on the page. */ ?>
        if (!lockNotice) return;
<?php /*         Hides the box by setting its display to none. */ ?>
        lockNotice.style.display = 'none';
<?php /*         Gets the span inside the lock box. */ ?>
        const text = lockNotice.querySelector('span');
<?php /*         Empties the span text when it exists. */ ?>
        if (text) text.textContent = '';
<?php /*     Closes the hideLockNotice function. */ ?>
    }

<?php /*     Function that shows a lock reason inside the warning box. */ ?>
    function showLockNotice(message) {
<?php /*         Leaves early when the lock box is not on the page. */ ?>
        if (!lockNotice) return;
<?php /*         Gets the span inside the lock box. */ ?>
        const text = lockNotice.querySelector('span');
<?php /*         Writes the given message into the span. */ ?>
        if (text) text.textContent = message;
<?php /*         Shows the box using flex display. */ ?>
        lockNotice.style.display = 'flex';
<?php /*     Closes the showLockNotice function. */ ?>
    }

    function selectStudentAdviser(name) {
        Array.from(adviser.options).forEach(option => {
            if (option.dataset.studentAdviser === 'true') {
                option.remove();
                return;
            }
            option.disabled = option.dataset.unavailable === 'true';
        });

        const adviserName = (name || '').trim();
        if (!adviserName) {
            adviser.value = '';
            return;
        }

        let option = Array.from(adviser.options).find(item => item.value === adviserName);
        if (!option) {
            option = new Option(adviserName, adviserName);
            option.dataset.studentAdviser = 'true';
            adviser.add(option);
        }
        option.disabled = false;
        adviser.value = adviserName;
    }

<?php /*     Function that fills the track, program and stage fields. */ ?>
    function fill() {
<?php /*         Looks up the selected student's information in the stage map. */ ?>
        const info = map[student.value];
<?php /*         Clears the old stage options. */ ?>
        stage.innerHTML = '';
<?php /*         Hides any old lock warning. */ ?>
        hideLockNotice();
<?php /*         If the student has no information, clear the fields. */ ?>
        if (!info) {
<?php /*             Empties the course type box. */ ?>
            track.value = '';
<?php /*             Empties the program box. */ ?>
            program.value = '';
            selectStudentAdviser('');
<?php /*             Empties the hint text. */ ?>
            hint.textContent = '';
<?php /*             Adds the 'Select a student first' placeholder option. */ ?>
            stage.appendChild(new Option('Select a student first', ''));
<?php /*             Stops here because there is nothing to fill. */ ?>
            return;
<?php /*         Closes the branch for an unknown student. */ ?>
        }
<?php /*         Shows the student's track label in the course type box. */ ?>
        track.value = info.track;
<?php /*         Shows the program code in the program box. */ ?>
        program.value = info.program;
        selectStudentAdviser(info.adviser);
<?php /*         Reads the stage entries array, or an empty array. */ ?>
        const stageEntries = info.stages || [];
<?php /*         Note (1 of 2): the stage the student is working on is preselected. */ ?>
        // The stage the student is working on is the one that is selected, so
<?php /*         Note (2 of 2): so the form matches the application and tracker. */ ?>
        // the form matches the student's application and Milestone Tracker.
<?php /*         Reads the current stage label, or empty text. */ ?>
        const current = info.currentStage || '';

<?php /*         Loops over every stage entry to build the options. */ ?>
        stageEntries.forEach(entry => {
<?php /*             Chooses the suffix text for scheduled or not reached stages. */ ?>
            const suffix = entry.state === 'scheduled'
<?php /*                 Suffix for a stage that already has a schedule. */ ?>
                ? ' (already scheduled)'
<?php /*                 Suffix for a stage that is still in the future. */ ?>
                : (entry.state === 'future' ? ' (not reached yet)' : '');
<?php /*             Creates an option with the label and any suffix. */ ?>
            const option = new Option(entry.label + suffix, entry.label);
<?php /*             Disables the option when the stage is locked. */ ?>
            option.disabled = Boolean(entry.locked);
<?php /*             Keeps the lock reason on the option for later messages. */ ?>
            option.dataset.reason = entry.reason || '';
<?php /*             Appends the new option to the stage drop-down. */ ?>
            stage.appendChild(option);
<?php /*         Closes the loop over the stage entries. */ ?>
        });

<?php /*         Keeps only the stages that are not locked. */ ?>
        const openEntries = stageEntries.filter(entry => !entry.locked);
<?php /*         If every stage is locked, tell the coordinator to edit instead. */ ?>
        if (!openEntries.length) {
<?php /*             Hint text explains that all stages already have schedules. */ ?>
            hint.textContent = 'Every presentation stage of this student already has a schedule. Edit it on the Presentation Schedules page.';
<?php /*             Clears the stage value so nothing invalid is submitted. */ ?>
            stage.value = '';
<?php /*             Stops here because there is no open stage. */ ?>
            return;
<?php /*         Closes the branch where every stage is locked. */ ?>
        }

<?php /*         Sets the hint text depending on whether a current stage exists. */ ?>
        hint.textContent = current
<?php /*             Hint names the current stage and explains locked stages. */ ?>
            ? 'Current stage: ' + current + '. Locked stages are either already scheduled or not reached yet.'
<?php /*             Fallback hint saying stages follow track and progress. */ ?>
            : 'The stages offered follow the student\u2019s track and their workflow progress.';

<?php /*         Note: prefer the stage the student is working on. */ ?>
        // Prefer the stage the student is working on, unless it is locked.
<?php /*         Picks the current stage when open, else the first open stage. */ ?>
        const preferred = openEntries.find(entry => entry.label === current) || openEntries[0];
<?php /*         Selects that stage in the drop-down. */ ?>
        stage.value = preferred.label;
<?php /*     Closes the fill function. */ ?>
    }
<?php /*     Function that stops the same panel member being chosen twice. */ ?>
    function updatePanelChoices() {
<?php /*         Collects the panel values already chosen, ignoring placeholders. */ ?>
        const selectedValues = panelSelects.map(select => select.value).filter(Boolean);
<?php /*         Loops over each panel member drop-down. */ ?>
        panelSelects.forEach(select => {
<?php /*             Loops over every option inside that drop-down. */ ?>
            Array.from(select.options).forEach(option => {
<?php /*                 Starts the check for an option already taken elsewhere. */ ?>
                option.disabled = option.value !== ''
<?php /*                     Skips the option that is already chosen in this drop-down. */ ?>
                    && option.value !== select.value
<?php /*                     Completes the check: another drop-down already uses this name. */ ?>
                    && selectedValues.includes(option.value);
<?php /*             Closes the option loop. */ ?>
            });
<?php /*         Closes the panel drop-down loop. */ ?>
        });
<?php /*     Closes the updatePanelChoices function. */ ?>
    }
<?php /*     Listens for a student change to refill the stage and team fields. */ ?>
    student.addEventListener('change', function () {
<?php /*         Refills the track, program and stage fields. */ ?>
        fill();
<?php /*         Re-checks which panel options must be disabled. */ ?>
        updatePanelChoices();
<?php /*     Closes the student change listener. */ ?>
    });
<?php /*     Loops over every panel member drop-down. */ ?>
    document.querySelectorAll('.panel-member-select').forEach(select => {
<?php /*         Re-checks panel options whenever one drop-down changes. */ ?>
        select.addEventListener('change', updatePanelChoices);
<?php /*     Closes the panel change listener loop. */ ?>
    });

<?php /*     Note (1 of 2): the custom dropdown reports a click on a locked stage. */ ?>
    // The custom dropdown reports a click on a locked stage; explain why it is
<?php /*     Note (2 of 2): the script then explains why it is locked. */ ?>
    // locked instead of silently doing nothing.
<?php /*     Listens for the blocked event fired by the stage dropdown. */ ?>
    stage.addEventListener('cselect:blocked', function (event) {
<?php /*         Reads the event details, or an empty object. */ ?>
        const detail = event.detail || {};
<?php /*         Uses the given reason, or builds a default explanation. */ ?>
        const reason = detail.reason || ('The ' + (detail.label || 'selected stage') + ' cannot be scheduled.');
<?php /*         Shows the lock reason in the warning box. */ ?>
        showLockNotice(reason);
<?php /*     Closes the blocked event listener. */ ?>
    });
<?php /*     Hides the lock warning when the coordinator picks another stage. */ ?>
    stage.addEventListener('change', hideLockNotice);

<?php /*     Runs fill once so the fields match the current selection. */ ?>
    fill();
<?php /* Closes and runs the function that set up the form. */ ?>
})();
<?php /* Closes the JavaScript block. */ ?>
</script>

<?php /* Loads the shared footer, which prints closing tags and scripts. */ ?>
<?php require_once __DIR__ . '/../../includes/layout/footer.php'; ?>