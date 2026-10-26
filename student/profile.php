<?php /* Opens the page where a student views and edits their own profile. */ ?>
<?php
// Loads the shared configuration and helper functions.
require_once __DIR__ . '/../includes/config.php';
// Loads the database class used to read and save the profile.
require_once __DIR__ . '/../includes/db.php';
// Starts the session only if it is not already running.
if (session_status() === PHP_SESSION_NONE) session_start();
// Sends visitors who are not logged-in students to the login page.
if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'student') redirectTo('student/login.php');
// Copies the logged-in user row so the page can read its fields.
$user = $_SESSION['user'];
// Finds the student profile row that belongs to this user.
$student = DB::find('students', ['user_id' => (int) $user['user_id']]);
// If there is no student profile, send the user back to the login page.
if (!$student) redirectTo('student/login.php');
// Starts with an empty error message for the form.
$error = '';
// Only process the form when it was submitted with POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cleans the typed first and last names into proper name case.
    $first = formatPersonName($_POST['first_name'] ?? ''); $last = formatPersonName($_POST['last_name'] ?? '');
    // Reads the middle initial and age, converting the age to a whole number.
    $middle = strtoupper(trim($_POST['middle_initial'] ?? '')); $age = (int) ($_POST['age'] ?? 0);
    // Reads the chosen gender and trims extra spaces.
    $gender = trim($_POST['gender'] ?? '');
    // Notes that the program, track, and ADZU ID are assigned by the Graduate School.
    // The academic program, track and ADZU ID number are assigned by the
    // Continues the note about read-only fields and the saved record.
    // Graduate School. They are shown as read-only fields, and the saved record
    // Explains that the saved record stops a crafted request from changing them.
    // is used here as well so a crafted request cannot change them.
    // Gets the program code this student belongs to from the saved record.
    $programCode = programCodeForStudent($student);
    // Reads the saved track code for this student.
    $trackCode = (string) ($student['track'] ?? '');
    // Checks that the saved track is still a valid track for the program.
    if (!in_array($trackCode, getTracksForProgram($programCode), true)) {
        // Comments the fallback that keeps a legacy record's default course track.
        // A legacy record without a usable track keeps the default of its course.
        // Legacy record without a valid track: use the program's default track.
        $trackCode = getTrackForProgram($programCode);
    // Closes the if block that fixed an invalid track.
    }
    // Reads the saved ADZU ID number as text so digits are not lost.
    $adzuIdRaw = (string) ($student['student_id'] ?? '');
    // Validates the required fields: names, program, age and gender.
    if ($first === '' || $last === '' || !isset(PROGRAMS[$programCode]) || $age < 18 || $gender === '') {
        // Stores a message asking the student to complete the required fields.
        $error = 'Complete all required profile fields with valid values.';
    // Checks again that the track belongs to the student's program.
    } elseif (!in_array($trackCode, getTracksForProgram($programCode), true)) {
        // Stores a message asking the student to pick a valid track.
        $error = 'Select the track your program follows.';
    // Checks that the ADZU ID contains only 4 to 10 digits.
    } elseif ($adzuIdRaw === '' || !ctype_digit($adzuIdRaw) || strlen($adzuIdRaw) < 4 || strlen($adzuIdRaw) > 10) {
        // Stores a message about the correct ADZU ID format.
        $error = 'Enter a valid ADZU ID number (4–10 digits).';
    // Runs when every field passed validation, so the profile can be saved.
    } else {
        // Builds the standard full name from first, last and middle initial.
        $fullName = canonicalStudentName($first, $last, $middle);
        // Converts the ADZU ID text into a whole number.
        $newStudentId = (int) $adzuIdRaw;
        // Starts a transaction so all changes save together or not at all.
        DB::getConnection()->beginTransaction();
        // Starts a try block that saves the profile and can roll back on errors.
        try {
            // Only check for duplicates when the student changed their ADZU ID.
            if ($newStudentId !== (int) $student['student_id']) {
                // Looks for another student already using this ADZU ID number.
                $taken = DB::find('students', ['student_id' => $newStudentId]);
                // If the ID belongs to a different account, refuse the change.
                if ($taken && (int) $taken['user_id'] !== (int) $user['user_id']) {
                    // Throws an error with a readable message about the duplicate ID.
                    throw new LogicException('This ADZU ID number is already registered to another student.');
                // Closes the if block that rejected a duplicate ADZU ID.
                }
            // Closes the if block that checked for a changed ADZU ID.
            }
            // Updates the student row with the personal details and program.
            DB::update('students', ['student_id' => $newStudentId, 'first_name' => $first, 'last_name' => $last, 'middle_initial' => rtrim($middle, '.'), 'age' => $age, 'gender' => $gender, 'program' => PROGRAMS[$programCode], 'track' => $trackCode], ['user_id' => (int) $user['user_id']]);
            // Updates the matching user row so the full name stays in sync.
            DB::update('users', ['full_name' => $fullName], ['user_id' => $user['user_id']]);
            // Saves all database changes permanently.
            DB::getConnection()->commit();
            // Updates the cached full name stored in the session.
            $_SESSION['user']['full_name'] = $fullName;
            // Updates the cached student id stored in the session.
            $_SESSION['student_id'] = $newStudentId;
            // Reads the profile again so the page shows the saved values.
            $student = DB::find('students', ['user_id' => (int) $user['user_id']]);
            // Gets the student identity details that are kept in the session.
            $identity = databaseStudentIdentity();
            // Only refresh the session details when an identity was found.
            if ($identity) {
                // Stores the updated student identity in the session.
                upsertSessionStudent($identity);
            // Closes the if block that refreshed the session identity.
            }
            // Queues a success message for the next page.
            setFlash('success', 'Profile updated successfully.');
            // Sends the student to the dashboard after saving.
            redirectTo('student/dashboard.php');
        // Handles the duplicate-ID error: rolls back and shows the message.
        } catch (LogicException $e) { if (DB::getConnection()->inTransaction()) DB::getConnection()->rollBack(); $error = $e->getMessage();
        // Handles database errors: rolls back and shows a friendly message.
        } catch (PDOException $e) { if (DB::getConnection()->inTransaction()) DB::getConnection()->rollBack(); $error = ((string) ($e->getCode() ?? '') === '23000') ? 'This ADZU ID number is already registered to another student.' : 'Unable to update your profile. Please try again.';
        // Handles any other error: rolls back and shows a general message.
        } catch (Throwable $e) { if (DB::getConnection()->inTransaction()) DB::getConnection()->rollBack(); $error = 'Unable to update your profile. Please try again.'; }
    // Closes the else block that saved the valid profile.
    }
// Closes the if block that handled the POST request.
}
// Gets the program code again so the read-only fields can be shown.
$programCode = programCodeForStudent($student);
// Reads the current track code for the track dropdown.
$currentTrack = (string) ($student['track'] ?? '');
// Starts an empty list of tracks for every program.
$tracksByProgram = [];
// Loops over each program code in the PROGRAMS list.
foreach (array_keys(PROGRAMS) as $code) {
    // Saves that program's valid tracks so the page script can use them.
    $tracksByProgram[$code] = getTracksForProgram($code);
// Closes the foreach loop over the programs.
}
// Sets the page title, role, menu highlight and header user name.
$pageTitle = 'Edit My Profile'; $role = 'student'; $currentPage = 'dashboard'; $userName = $user['full_name'];
// Loads the shared page header, sidebar and navigation.
require_once __DIR__ . '/../includes/header.php';
// Closes PHP so the HTML part of the page can start.
?>
<?php /* Shows the page banner with the title and a short instruction. */ ?>
<div class="page-header page-banner"><h2>Edit My Profile</h2><p>Keep your student information and program assignment accurate.</p></div>
<?php /* If an error was set, shows a red alert box with the message. */ ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php /* The edit form with the student's name, age, gender, ID, program and track. */ ?>
<form method="post" class="card" data-validate><div class="card-body"><div class="form-row"><div class="form-field"><label>First Name <span class="required">*</span></label><input name="first_name" required value="<?= htmlspecialchars($student['first_name']) ?>"></div><div class="form-field"><label>Last Name <span class="required">*</span></label><input name="last_name" required value="<?= htmlspecialchars($student['last_name']) ?>"></div></div><div class="form-row"><div class="form-field"><label>Middle Initial</label><input name="middle_initial" maxlength="5" value="<?= htmlspecialchars($student['middle_initial'] ?? '') ?>"></div><div class="form-field"><label>Age <span class="required">*</span></label><input type="number" name="age" min="18" max="99" required value="<?= (int) $student['age'] ?>"></div><div class="form-field"><label>Gender <span class="required">*</span></label><select name="gender" required><?php foreach (['Male','Female','Prefer not to say'] as $option): ?><option <?= $student['gender'] === $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach; ?></select></div></div><div class="form-field"><label>ADZU ID Number</label><input name="student_id" readonly inputmode="numeric" pattern="[0-9]{4,10}" title="Assigned by the Graduate School" aria-readonly="true" value="<?= htmlspecialchars((string) ($student['student_id'] ?? '')) ?>"><p class="field-hint">Assigned by the Graduate School and cannot be changed here.</p></div><div class="form-field"><label>Academic Program</label><select name="program" id="profileProgram" disabled aria-disabled="true"><?php foreach (PROGRAMS as $code => $name): ?><option value="<?= $code ?>" <?= $programCode === $code ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option><?php endforeach; ?></select><p class="field-hint">Locked. Contact the Graduate School to change your program.</p></div><div class="form-field"><label>Track</label><select name="track" id="profileTrack" disabled aria-disabled="true"></select><p class="field-hint">Locked. Your track is set from your program and cannot be changed here.</p></div><div class="form-actions"><button class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button><a class="btn btn-outline" href="<?= url('student/dashboard.php') ?>">Cancel</a></div></div></form>
<?php /* Starts the JavaScript that fills the track dropdown. */ ?>
<script>
<?php /* Starts a self-running function so its variables stay private. */ ?>
(function () {
<?php /*     Explains the program-to-track list used by the profile form. */ ?>
    // The tracks a course can follow. A course that can be followed in more
<?php /*     Continues the note about courses that allow more than one track. */ ?>
    // than one way lets the student choose.
<?php /*     Gets the program dropdown element by its id. */ ?>
    var programSelect = document.getElementById('profileProgram');
<?php /*     Gets the track dropdown element by its id. */ ?>
    var trackSelect = document.getElementById('profileTrack');
<?php /*     Passes the PHP track list to JavaScript as JSON data. */ ?>
    var tracks = <?= json_encode($tracksByProgram) ?>;
<?php /*     Maps track codes to the readable labels shown to the student. */ ?>
    var labels = <?= json_encode(['thesis' => 'Thesis', 'capstone' => 'Capstone', 'seminar' => 'Seminar Paper']) ?>;
<?php /*     Passes the student's saved track to JavaScript. */ ?>
    var saved = <?= json_encode($currentTrack) ?>;

<?php /*     Defines fill(), which rebuilds the track options for a program. */ ?>
    function fill(keep) {
<?php /*         Gets the track list for the selected program, or an empty list. */ ?>
        var list = tracks[programSelect.value] || [];
<?php /*         Puts a placeholder option first in the track dropdown. */ ?>
        trackSelect.innerHTML = '<option value="">Select your track</option>'
<?php /*             Adds one option for each valid track using its readable label. */ ?>
            + list.map(function (t) { return '<option value="' + t + '">' + (labels[t] || t) + '</option>'; }).join('');
<?php /*         Keeps the chosen track, or falls back to the saved track. */ ?>
        var wanted = keep || saved;
<?php /*         If that track is valid for the program, select it. */ ?>
        if (wanted && list.indexOf(wanted) !== -1) {
<?php /*             Selects the wanted track in the dropdown. */ ?>
            trackSelect.value = wanted;
<?php /*         If the program has only one track, select it automatically. */ ?>
        } else if (list.length === 1) {
<?php /*             Selects the program's single track. */ ?>
            trackSelect.value = list[0];
<?php /*         Closes the if/else block that chose the track. */ ?>
        }
<?php /*     Closes the fill() function. */ ?>
    }
<?php /*     Rebuilds the track list when the program changes. */ ?>
    programSelect.addEventListener('change', function () { fill(''); });
<?php /*     Fills the track dropdown once when the page loads. */ ?>
    fill(saved);
<?php /* Closes the self-running function. */ ?>
})();
<?php /* Ends the JavaScript for the track dropdown. */ ?>
</script>
<?php /* Loads the shared footer and closes the page. */ ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
