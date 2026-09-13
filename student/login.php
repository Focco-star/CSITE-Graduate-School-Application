<?php
session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$loginError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $loginError = 'Please enter both your email and password.';
    } else {
        // Query database for user by email
        $user = DB::find('users', ['email' => $email]);

        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true); // Generates a fresh session ID and clears old session file
            $_SESSION['user'] = $user;
            header('Location: ' . url('student/dashboard.php'));
            exit;
        }
        
        if ($user && password_verify($password, $user['password'])) {
            if ($user['role'] !== 'student') {
                $loginError = 'Access denied. Account is not registered as a student.';
            } else {
                // Fetch student profile details
                $student = DB::find('students', ['user_id' => $user['user_id']]);

                // Store active session variables
                $_SESSION['user_id']    = $user['user_id'];
                $_SESSION['student_id'] = $student['student_id'] ?? null;
                $_SESSION['full_name']  = $user['full_name'];
                $_SESSION['email']      = $user['email'];
                $_SESSION['role']       = $user['role'];

                // Redirect to dashboard
                header('Location: ' . url('student/dashboard.php'));
                exit;
            }
        } else {
            $loginError = 'Invalid email address or password.';
        }
    }
}

$pageTitle = 'Student Login';
$role = 'public';
$bodyClass = 'login-body';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/slideshow.php';
?>

<div class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <img src="<?= asset('img/adzu-seal.png') ?>" alt="AdZU Seal">
        </div>
        <h1><?= htmlspecialchars(SITE_SHORT) ?></h1>
        <p class="login-subtitle">Student Portal — Sign in with your ADZU account</p>

        <?php if ($loginError): ?>
        <div class="alert alert-danger" style="margin-bottom:1rem;"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($loginError) ?></div>
        <?php endif; ?>

        <form class="login-form" action="<?= url('student/login.php') ?>" method="post" data-validate>
            <div class="form-group">
                <label for="username">ADZU Email</label>
                <div class="input-icon-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="username" name="username" placeholder="yourname@adzu.edu.ph" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-group" style="margin-bottom:1.75rem;">
                <label for="password">Password</label>
                <div class="input-icon-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="has-toggle" placeholder="Enter your password" required>
                    <button type="button" class="pw-toggle" data-toggle-password="password" tabindex="-1" aria-label="Show password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
            <a href="<?= url('student/register.php') ?>" class="btn btn-google btn-lg" style="margin-top:0.75rem;">
                <i class="fab fa-google"></i> Sign in with Google
            </a>
        </form>

        <div class="login-divider">New to the system?</div>

        <a href="<?= url('student/register.php') ?>" class="btn btn-lg btn-ghost">
            <i class="fas fa-user-plus"></i> Create Account / Set Up Profile
        </a>

        <div class="login-links">
            <a href="<?= url('student/register.php') ?>">Need an account? Register</a>
        </div>
        <div class="login-back">
            <a href="<?= url('public/index.php') ?>"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>