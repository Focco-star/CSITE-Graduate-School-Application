<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php'; // Ensure DB class is loaded

$registerError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['firstName'] ?? '');
    $last = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $program = $_POST['program'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirmPassword'] ?? '';
    $age = trim($_POST['age'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $middleInitial = strtoupper(trim($_POST['middleInitial'] ?? ''));

    if ($first === '' || $last === '' || $email === '' || $program === '' || $password === '') {
        $registerError = 'Please complete all required fields.';
    } elseif (!isset(PROGRAMS[$program])) {
        $registerError = 'Select a valid program.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registerError = 'Enter a valid ADZU email address.';
    } elseif ($password !== $confirm) {
        $registerError = 'Passwords do not match.';
    } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password)) {
        $registerError = 'Password must be at least 8 characters and include one uppercase letter and one number.';
    } else {
        try {
            $pdo = DB::getConnection();

            // 1. Check if email already exists
            $existingUser = DB::find('users', ['email' => $email]);
            if ($existingUser) {
                throw new LogicException('An account with this email address already exists.');
            }

            // Determine track from helper function
            $track = getTrackForProgram($program);
            $fullName = implode(' ', array_filter([$first, $middleInitial, $last]));

            $pdo->beginTransaction();

            // 2. Insert into users table
            $user = DB::insert('users', [
                'full_name' => $fullName,
                'email'     => $email,
                'password'  => password_hash($password, PASSWORD_DEFAULT),
                'role'      => 'student'
            ]);

            // 3. Insert into students table
            DB::insert('students', [
                'user_id'        => $user['user_id'],
                'first_name'     => $first,
                'last_name'      => $last,
                'middle_initial' => $middleInitial,
                'age'            => (int) $age,
                'gender'         => $gender,
                'program'        => PROGRAMS[$program] ?? $program,
                'track'          => $track,
                'enrollment_date'=> date('Y-m-d')
            ]);

            $pdo->commit();

            // Set session variables and redirect
            $_SESSION['user'] = $user;
            redirectTo('student/login.php');

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $registerError = $e->getMessage();
        }
    }
}

$pageTitle = 'Student Profile Setup';
$role = 'public';
$bodyClass = 'login-body';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/slideshow.php';

$trackHints = [
    'thesis'   => 'Thesis track — 3 presentations (Concept Paper → Proposal → Final Defense)',
    'capstone' => 'Capstone track — 2 presentations (Proposal → Final Presentation)',
    'seminar'  => 'Seminar Paper track — 2 presentations (Proposal → Final Paper)',
];
?>

<div class="login-page" style="align-items:flex-start;padding-top:3rem;padding-bottom:3rem;">
    <div class="login-card register-card">
        <div class="login-logo">
            <img src="<?= asset('img/adzu-seal.png') ?>" alt="AdZU Seal">
        </div>
        <h1>Student Profile Setup</h1>
        <p class="login-subtitle">Complete your profile to get started</p>

        <?php if ($registerError): ?>
        <div class="alert alert-danger" style="margin-bottom:1rem;"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($registerError) ?></div>
        <?php endif; ?>

        <form class="login-form" action="<?= url('student/register.php') ?>" method="post" data-validate>
            <div class="form-section-label">Personal Information</div>

            <div class="register-grid-2">
                <div class="form-group">
                    <label for="firstName">First Name *</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-user"></i>
                        <input type="text" id="firstName" name="firstName" placeholder="First name" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="lastName">Last Name *</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-user"></i>
                        <input type="text" id="lastName" name="lastName" placeholder="Last name" required>
                    </div>
                </div>
            </div>

            <div class="register-grid-3">
                <div class="form-group">
                    <label for="middleInitial">Middle Initial</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-user"></i>
                        <input type="text" id="middleInitial" name="middleInitial" placeholder="e.g. R" maxlength="2" style="text-transform:uppercase;">
                    </div>
                </div>
                <div class="form-group">
                    <label for="age">Age *</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-calendar-alt"></i>
                        <input type="number" id="age" name="age" placeholder="Age" min="18" max="99" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="gender">Gender *</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-venus-mars"></i>
                        <select id="gender" name="gender" required>
                            <option value="" disabled selected>Select</option>
                            <option>Male</option>
                            <option>Female</option>
                            <option>Prefer not to say</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section-label">Academic Program</div>
            <div class="form-group">
                <label>Program *</label>
                <input type="hidden" name="program" id="programValue" required>
                <div class="program-picker">
                    <button type="button" class="program-picker-toggle" id="programToggle">
                        <i class="fas fa-graduation-cap" style="color:var(--adzu-gold);font-size:0.9rem;"></i>
                        <span id="programLabel" style="flex:1;font-size:0.9rem;">Select your program</span>
                        <i class="fas fa-chevron-down" style="color:rgba(255,255,255,0.4);font-size:0.75rem;"></i>
                    </button>
                    <div class="program-list" id="programList">
                        <?php foreach (PROGRAMS as $code => $name): ?>
                        <button type="button" class="program-option" data-code="<?= htmlspecialchars($code) ?>" data-track="<?= getTrackForProgram($code) ?>">
                            <span><?= htmlspecialchars($name) ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <p id="trackHint" style="display:none;margin-top:0.5rem;font-size:0.76rem;color:rgba(255,255,255,0.55);">
                    <i class="fas fa-info-circle" style="color:var(--adzu-gold);"></i>
                    <span></span>
                </p>
            </div>

            <div class="form-section-label">Account Credentials</div>
            <div class="form-group">
                <label for="email">ADZU Email *</label>
                <div class="input-icon-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="yourname@adzu.edu.ph" required>
                </div>
            </div>
            <div class="register-grid-2">
                <div class="form-group">
                    <label for="regPassword">Password *</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="regPassword" name="password" class="has-toggle" placeholder="Min. 8 chars" required minlength="8">
                        <button type="button" class="pw-toggle" data-toggle-password="regPassword" tabindex="-1"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Re-enter Password *</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirmPassword" name="confirmPassword" class="has-toggle" placeholder="Repeat password" required>
                        <button type="button" class="pw-toggle" data-toggle-password="confirmPassword" tabindex="-1"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
            </div>
            <p style="margin:0.25rem 0 1.75rem;font-size:0.73rem;color:rgba(255,255,255,0.4);line-height:1.5;">
                <i class="fas fa-shield-alt" style="margin-right:5px;color:var(--adzu-gold);"></i>
                Password must be at least 8 characters and include one uppercase letter and one number.
            </p>
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-check-circle"></i> Complete Setup &amp; Continue
            </button>
        </form>

        <div class="login-back">
            <a href="<?= url('student/login.php') ?>"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</div>

<script>
window.CSITE_TRACK_HINTS = <?= json_encode($trackHints) ?>;
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
