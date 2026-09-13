<?php
require_once __DIR__ . '/../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in as coordinator
if (!empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'coordinator') {
    header('Location: ' . url('coordinator/dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        try {
            // Establish PDO connection directly if $pdo is not set in config.php
            if (!isset($pdo) || !($pdo instanceof PDO)) {
                $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
                $dbName = defined('DB_NAME') ? DB_NAME : 'websys'; // Replace with your actual database name
                $dbUser = defined('DB_USER') ? DB_USER : 'root';
                $dbPass = defined('DB_PASS') ? DB_PASS : '';

                $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            }

            // Fetch user from DB
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND role = 'coordinator' LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'        => $user['user_id'],
                    'full_name' => $user['full_name'],
                    'email'     => $user['email'],
                    'role'      => $user['role'],
                ];
                header('Location: ' . url('coordinator/dashboard.php'));
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database Connection Error: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Coordinator Login';
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
        <h1>Coordinator Portal</h1>
        <p class="login-subtitle"><?= htmlspecialchars(SITE_SHORT) ?> – Graduate Program Coordinator</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-bottom: 1rem;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form class="login-form" action="<?= url('coordinator/login.php') ?>" method="post" data-validate>
            <div class="form-group">
                <label for="username">Username / Email</label>
                <div class="input-icon-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="gpc-csite@adzu.edu.ph" required>
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="has-toggle" placeholder="Password" required>
                    <button type="button" class="pw-toggle" data-toggle-password="password" tabindex="-1" aria-label="Show password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div class="login-back">
            <a href="<?= url('public/index.php') ?>"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>