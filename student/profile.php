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
    $first = trim($_POST['first_name'] ?? ''); $last = trim($_POST['last_name'] ?? '');
    $middle = strtoupper(trim($_POST['middle_initial'] ?? '')); $age = (int) ($_POST['age'] ?? 0);
    $gender = trim($_POST['gender'] ?? ''); $programCode = $_POST['program'] ?? '';
    if ($first === '' || $last === '' || !isset(PROGRAMS[$programCode]) || $age < 18 || $gender === '') {
        $error = 'Complete all required profile fields with valid values.';
    } else {
        $fullName = canonicalStudentName($first, $last, $middle);
        DB::getConnection()->beginTransaction();
        try {
            DB::update('students', ['first_name' => $first, 'last_name' => $last, 'middle_initial' => rtrim($middle, '.'), 'age' => $age, 'gender' => $gender, 'program' => PROGRAMS[$programCode], 'track' => getTrackForProgram($programCode)], ['student_id' => $student['student_id']]);
            DB::update('users', ['full_name' => $fullName], ['user_id' => $user['user_id']]);
            DB::getConnection()->commit();
            $_SESSION['user']['full_name'] = $fullName;
            $identity = databaseStudentIdentity();
            if ($identity) {
                upsertSessionStudent($identity);
            }
            setFlash('success', 'Profile updated successfully.');
            redirectTo('student/dashboard.php');
        } catch (Throwable $e) { if (DB::getConnection()->inTransaction()) DB::getConnection()->rollBack(); $error = 'Unable to update your profile. Please try again.'; }
    }
}
$programCode = array_key_first(array_filter(PROGRAMS, fn($value) => $value === $student['program'])) ?: 'MSCS';
$pageTitle = 'Edit My Profile'; $role = 'student'; $currentPage = 'dashboard'; $userName = $user['full_name'];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><h2>Edit My Profile</h2><p>Keep your student information and program assignment accurate.</p></div>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" class="card" data-validate><div class="card-body"><div class="form-row"><div class="form-field"><label>First Name <span class="required">*</span></label><input name="first_name" required value="<?= htmlspecialchars($student['first_name']) ?>"></div><div class="form-field"><label>Last Name <span class="required">*</span></label><input name="last_name" required value="<?= htmlspecialchars($student['last_name']) ?>"></div></div><div class="form-row"><div class="form-field"><label>Middle Initial</label><input name="middle_initial" maxlength="5" value="<?= htmlspecialchars($student['middle_initial'] ?? '') ?>"></div><div class="form-field"><label>Age <span class="required">*</span></label><input type="number" name="age" min="18" max="99" required value="<?= (int) $student['age'] ?>"></div><div class="form-field"><label>Gender <span class="required">*</span></label><select name="gender" required><?php foreach (['Male','Female','Prefer not to say'] as $option): ?><option <?= $student['gender'] === $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach; ?></select></div></div><div class="form-field"><label>Academic Program <span class="required">*</span></label><select name="program" required><?php foreach (PROGRAMS as $code => $name): ?><option value="<?= $code ?>" <?= $programCode === $code ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option><?php endforeach; ?></select></div><div class="form-actions"><button class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button><a class="btn btn-outline" href="<?= url('student/dashboard.php') ?>">Cancel</a></div></div></form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
