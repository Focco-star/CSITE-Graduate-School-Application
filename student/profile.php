<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'student') redirectTo('student/login.php');
$user = $_SESSION['user'];
$student = DB::find('students', ['user_id' => (int) $user['user_id']]);
if (!$student) redirectTo('student/login.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = formatPersonName($_POST['first_name'] ?? ''); $last = formatPersonName($_POST['last_name'] ?? '');
    $middle = strtoupper(trim($_POST['middle_initial'] ?? '')); $age = (int) ($_POST['age'] ?? 0);
    $gender = trim($_POST['gender'] ?? ''); $programCode = $_POST['program'] ?? '';
    $adzuIdRaw = trim($_POST['student_id'] ?? '');
    // The track the student follows. A course that can be followed in more
    // than one way lets the student choose.
    $trackCode = trim($_POST['track'] ?? '');
    if ($first === '' || $last === '' || !isset(PROGRAMS[$programCode]) || $age < 18 || $gender === '') {
        $error = 'Complete all required profile fields with valid values.';
    } elseif (!in_array($trackCode, getTracksForProgram($programCode), true)) {
        $error = 'Select the track your program follows.';
    } elseif ($adzuIdRaw === '' || !ctype_digit($adzuIdRaw) || strlen($adzuIdRaw) < 4 || strlen($adzuIdRaw) > 10) {
        $error = 'Enter a valid ADZU ID number (4–10 digits).';
    } else {
        $fullName = canonicalStudentName($first, $last, $middle);
        $newStudentId = (int) $adzuIdRaw;
        DB::getConnection()->beginTransaction();
        try {
            if ($newStudentId !== (int) $student['student_id']) {
                $taken = DB::find('students', ['student_id' => $newStudentId]);
                if ($taken && (int) $taken['user_id'] !== (int) $user['user_id']) {
                    throw new LogicException('This ADZU ID number is already registered to another student.');
                }
            }
            DB::update('students', ['student_id' => $newStudentId, 'first_name' => $first, 'last_name' => $last, 'middle_initial' => rtrim($middle, '.'), 'age' => $age, 'gender' => $gender, 'program' => PROGRAMS[$programCode], 'track' => $trackCode], ['user_id' => (int) $user['user_id']]);
            DB::update('users', ['full_name' => $fullName], ['user_id' => $user['user_id']]);
            DB::getConnection()->commit();
            $_SESSION['user']['full_name'] = $fullName;
            $_SESSION['student_id'] = $newStudentId;
            $student = DB::find('students', ['user_id' => (int) $user['user_id']]);
            $identity = databaseStudentIdentity();
            if ($identity) {
                upsertSessionStudent($identity);
            }
            setFlash('success', 'Profile updated successfully.');
            redirectTo('student/dashboard.php');
        } catch (LogicException $e) { if (DB::getConnection()->inTransaction()) DB::getConnection()->rollBack(); $error = $e->getMessage();
        } catch (PDOException $e) { if (DB::getConnection()->inTransaction()) DB::getConnection()->rollBack(); $error = ((string) ($e->getCode() ?? '') === '23000') ? 'This ADZU ID number is already registered to another student.' : 'Unable to update your profile. Please try again.';
        } catch (Throwable $e) { if (DB::getConnection()->inTransaction()) DB::getConnection()->rollBack(); $error = 'Unable to update your profile. Please try again.'; }
    }
}
$programCode = programCodeForStudent($student);
$currentTrack = (string) ($student['track'] ?? '');
$tracksByProgram = [];
foreach (array_keys(PROGRAMS) as $code) {
    $tracksByProgram[$code] = getTracksForProgram($code);
}
$pageTitle = 'Edit My Profile'; $role = 'student'; $currentPage = 'dashboard'; $userName = $user['full_name'];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><h2>Edit My Profile</h2><p>Keep your student information and program assignment accurate.</p></div>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" class="card" data-validate><div class="card-body"><div class="form-row"><div class="form-field"><label>First Name <span class="required">*</span></label><input name="first_name" required value="<?= htmlspecialchars($student['first_name']) ?>"></div><div class="form-field"><label>Last Name <span class="required">*</span></label><input name="last_name" required value="<?= htmlspecialchars($student['last_name']) ?>"></div></div><div class="form-row"><div class="form-field"><label>Middle Initial</label><input name="middle_initial" maxlength="5" value="<?= htmlspecialchars($student['middle_initial'] ?? '') ?>"></div><div class="form-field"><label>Age <span class="required">*</span></label><input type="number" name="age" min="18" max="99" required value="<?= (int) $student['age'] ?>"></div><div class="form-field"><label>Gender <span class="required">*</span></label><select name="gender" required><?php foreach (['Male','Female','Prefer not to say'] as $option): ?><option <?= $student['gender'] === $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach; ?></select></div></div><div class="form-field"><label>ADZU ID Number <span class="required">*</span></label><input name="student_id" required inputmode="numeric" pattern="[0-9]{4,10}" title="4–10 digits" value="<?= htmlspecialchars((string) ($student['student_id'] ?? '')) ?>"></div><div class="form-field"><label>Academic Program <span class="required">*</span></label><select name="program" id="profileProgram" required><?php foreach (PROGRAMS as $code => $name): ?><option value="<?= $code ?>" <?= $programCode === $code ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option><?php endforeach; ?></select></div><div class="form-field"><label>Track <span class="required">*</span></label><select name="track" id="profileTrack" required></select><p class="field-hint">Your program can be followed in more than one way. Choose the track you are enrolled in.</p></div><div class="form-actions"><button class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button><a class="btn btn-outline" href="<?= url('student/dashboard.php') ?>">Cancel</a></div></div></form>
<script>
(function () {
    // The tracks a course can follow. A course that can be followed in more
    // than one way lets the student choose.
    var programSelect = document.getElementById('profileProgram');
    var trackSelect = document.getElementById('profileTrack');
    var tracks = <?= json_encode($tracksByProgram) ?>;
    var labels = <?= json_encode(['thesis' => 'Thesis', 'capstone' => 'Capstone', 'seminar' => 'Seminar Paper']) ?>;
    var saved = <?= json_encode($currentTrack) ?>;

    function fill(keep) {
        var list = tracks[programSelect.value] || [];
        trackSelect.innerHTML = '<option value="">Select your track</option>'
            + list.map(function (t) { return '<option value="' + t + '">' + (labels[t] || t) + '</option>'; }).join('');
        var wanted = keep || saved;
        if (wanted && list.indexOf(wanted) !== -1) {
            trackSelect.value = wanted;
        } else if (list.length === 1) {
            trackSelect.value = list[0];
        }
    }
    programSelect.addEventListener('change', function () { fill(''); });
    fill(saved);
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
