<?php /* Starts the PHP code for the coordinator's student profile page. */ ?>
<?php
// Loads config settings, helpers, and demo data arrays.
require_once __DIR__ . '/../../includes/config.php';
// Loads the database helper in case the student comes from MySQL.
require_once __DIR__ . '/../../includes/db.php';

// Sets the page title used in the browser tab.
$pageTitle   = 'Student Profile';
// Tells the layout the signed-in user is a coordinator.
$role        = 'coordinator';
// Marks the 'students' sidebar link as active.
$currentPage = 'students';
// Takes the demo coordinator name for the header greeting.
$userName    = $mockCoordinator['name'];

// Reads the student id from the URL, or 0 when missing.
$studentId = (int) ($_GET['id'] ?? 0);
// Finds the student by id, else falls back to the demo email.
$rec = findStudentById((string) $studentId) ?? findStudentByEmail($mockStudent['email']);
// Builds the profile from that student, or uses the demo student.
$profile = $rec ? currentStudentProfile(array_merge($mockStudent, ['email' => $rec['email']])) : $mockStudent;


// Only queries MySQL when a real student id was given.
if ($studentId > 0) {
    // Opens a try block around the database lookup.
    try {
        // Prepares a query joining students and users for one student.
        $stmt = DB::getConnection()->prepare('SELECT s.*, u.full_name, u.email FROM students s INNER JOIN users u ON u.user_id = s.user_id WHERE s.student_id = :id LIMIT 1');
        // Runs the query with the student id.
        $stmt->execute(['id' => $studentId]);
        // Fetches the matching student row, or false when there is none.
        $dbStudent = $stmt->fetch();
        // Checks that a student row was actually found.
        if ($dbStudent) {
            // Works out the program code by matching the stored program name.
            $programCode = array_key_first(array_filter(PROGRAMS, static fn($label) => $label === $dbStudent['program'])) ?: $dbStudent['program'];
            // Gets the full program name from the PROGRAMS list.
            $programName = PROGRAMS[$programCode] ?? $dbStudent['program'];
            // Uses the enrolment date, or today's date when it is empty.
            $enroll = $dbStudent['enrollment_date'] ?: date('Y-m-d');
            // Builds a summary record with track label, age, gender, and stage.
            $rec = ['trackLabel' => getTrackLabel($dbStudent['track']), 'age' => $dbStudent['age'], 'gender' => $dbStudent['gender'], 'track' => $dbStudent['track'], 'program' => $programCode, 'currentStage' => 'not_started'];
            // Merges real student details into the profile array for display.
            $profile = array_merge($mockStudent, ['name' => studentDisplayName($dbStudent), 'email' => $dbStudent['email'], 'program' => $programCode, 'program_name' => preg_replace('/\s*\(.*\)$/', '', $programName) ?: $programName, 'track' => $dbStudent['track'], 'enroll_date' => $enroll, 'adviser' => $dbStudent['adviser_name'] ?? '', 'completion_deadline' => ((int) substr($enroll, 0, 4) + 3) . substr($enroll, 4)]);
        // Closes the found-student check.
        }
    // Catches any error from the database lookup.
    } catch (Throwable $e) {

    // Closes the try/catch block around the lookup.
    }
// Closes the real-student-id check.
}
// Finds the latest application for this student's email.
$latest = latestApplicationForEmail($profile['email']);
// Loads the workflow stages that match the student's track.
$stages = getStagesForTrack($profile['track']);
// Loads all presentation schedules for this student.
$studentSchedules = schedulesForEmail($profile['email']);
// Takes the last schedule as the most recent one, or null.
$latestSchedule = $studentSchedules ? end($studentSchedules) : null;

// Loads the shared header and navigation.
require_once __DIR__ . '/../../includes/header.php';
// Closes PHP so the HTML below is printed.
?>

<?php /* Opens the page header area. */ ?>
<div class="page-header">
<?php /*     Shows the big heading 'Student Profile'. */ ?>
    <h2>Student Profile</h2>
<?php /*     Shows the student's name in the subtitle sentence. */ ?>
    <p>Detailed capstone/thesis information for <?= htmlspecialchars($profile['name']) ?>.</p>
<?php /* Closes the page header area. */ ?>
</div>

<?php /* Opens the card for personal information. */ ?>
<div class="card">
<?php /*     Card header 'Personal Information'. */ ?>
    <div class="card-header"><h3>Personal Information</h3></div>
<?php /*     Opens the card body. */ ?>
    <div class="card-body">
<?php /*         Starts a grid that lays out the detail items. */ ?>
        <div class="detail-grid">
<?php /*             Shows the student's full name. */ ?>
            <div class="detail-item"><label>Student Name</label><span><?= htmlspecialchars($profile['name']) ?></span></div>
<?php /*             Shows the program name, for example MSCS. */ ?>
            <div class="detail-item"><label>Program</label><span><?= htmlspecialchars($profile['program_name']) ?></span></div>
<?php /*             Shows the student's email address. */ ?>
            <div class="detail-item"><label>Email</label><span><?= htmlspecialchars($profile['email']) ?></span></div>
<?php /*             Shows the track label such as Thesis or Capstone. */ ?>
            <div class="detail-item"><label>Track</label><span><?= htmlspecialchars(($rec['trackLabel'] ?? getTrackLabel($profile['track']))) ?></span></div>
<?php /*             Shows the age, or a dash when it is empty. */ ?>
            <div class="detail-item"><label>Age</label><span><?= htmlspecialchars(($rec['age'] ?? '') !== '' ? $rec['age'] : '—') ?></span></div>
<?php /*             Shows the gender, or a dash when it is empty. */ ?>
            <div class="detail-item"><label>Gender</label><span><?= htmlspecialchars(($rec['gender'] ?? '') !== '' ? $rec['gender'] : '—') ?></span></div>
<?php /*             Shows a coloured badge for the current application status. */ ?>
            <div class="detail-item"><label>Current Status</label><span><?= statusBadge($profile['status']) ?></span></div>
<?php /*         Closes the detail grid. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the personal information card. */ ?>
</div>

<?php /* Opens the card for important dates. */ ?>
<div class="card">
<?php /*     Card header 'Important Dates'. */ ?>
    <div class="card-header"><h3>Important Dates</h3></div>
<?php /*     Opens the card body. */ ?>
    <div class="card-body">
<?php /*         Starts a grid that lays out the three dates. */ ?>
        <div class="detail-grid">
<?php /*             Shows the enrolment date as a readable date. */ ?>
            <div class="detail-item"><label>Enrollment / Start Date</label><span><?= date('F d, Y', strtotime($profile['enroll_date'])) ?></span></div>
<?php /*             Shows the application date, or a dash when there is none. */ ?>
            <div class="detail-item"><label>Application Date</label><span><?= $latest ? date('F d, Y', strtotime($latest['date'])) : '—' ?></span></div>
<?php /*             Shows the completion deadline three years after enrolment. */ ?>
            <div class="detail-item"><label>Completion Deadline (3 Years)</label><span><?= date('F d, Y', strtotime($profile['completion_deadline'])) ?></span></div>
<?php /*         Closes the detail grid. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the important dates card. */ ?>
</div>

<?php /* Opens the card for capstone or thesis information. */ ?>
<div class="card">
<?php /*     Card header 'Capstone/Thesis Information'. */ ?>
    <div class="card-header"><h3>Capstone/Thesis Information</h3></div>
<?php /*     Opens the card body. */ ?>
    <div class="card-body">
<?php /*         Starts a grid that lays out the research details. */ ?>
        <div class="detail-grid">
<?php /*             Shows the research title across the full row. */ ?>
            <div class="detail-item" style="grid-column:1/-1;"><label>Research Title</label><span><?= htmlspecialchars($latest['title'] ?? $profile['title'] ?: '—') ?></span></div>
<?php /*             Shows the adviser name, or a dash when unknown. */ ?>
            <div class="detail-item"><label>Adviser</label><span><?= htmlspecialchars($latest['adviser'] ?? $profile['adviser'] ?: '—') ?></span></div>
<?php /*             Shows the current stage label for the student's track. */ ?>
            <div class="detail-item"><label>Current Stage</label><span><?= htmlspecialchars(displayStageLabel($rec ?: ['track' => $profile['track'], 'program' => $profile['program'], 'currentStage' => $profile['current_stage']])) ?></span></div>
<?php /*         Closes the detail grid. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the capstone information card. */ ?>
</div>

<?php /* Opens the footer area that holds the action buttons. */ ?>
<div class="form-actions">
<?php /*     Only shows the View Application button when an application exists. */ ?>
    <?php if ($latest): ?>
<?php /*     Link to the coordinator's application view page. */ ?>
    <a href="<?= url('coordinator/applications/view.php?id=' . $latest['id']) ?>" class="btn btn-primary"><i class="fas fa-file-alt"></i> View Application</a>
<?php /*     Ends the application button check. */ ?>
    <?php endif; ?>
<?php /*     Only shows the notify button when a schedule exists. */ ?>
    <?php if ($latestSchedule): ?>
<?php /*     Link to the documentor notification page for that schedule. */ ?>
    <a href="<?= url('coordinator/schedule/notification.php?id=' . urlencode($latestSchedule['id'])) ?>" class="btn btn-secondary"><i class="fas fa-file-export"></i> Notify Documentor</a>
<?php /*     Ends the schedule button check. */ ?>
    <?php endif; ?>
<?php /*     Link back to the student list page. */ ?>
    <a href="<?= url('coordinator/students/manage.php') ?>" class="btn btn-outline">Back to List</a>
<?php /* Closes the footer action area. */ ?>
</div>

<?php /* Loads the shared footer and closes the page. */ ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
