<?php /* Opens the PHP block that handles coordinator sign-in. */ ?>
<?php
// Loads shared settings and helper functions such as url() and setFlash().
require_once __DIR__ . '/../includes/config.php';

// Checks whether a session is already running before starting one.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so login details can be remembered.
    session_start();
// Closes the session-start check.
}


// If a coordinator is already signed in, skip the login form.
if (!empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'coordinator') {
    // Redirects the signed-in coordinator straight to the dashboard.
    header('Location: ' . url('coordinator/dashboard.php'));
    // Stops the script after the redirect.
    exit;
// Closes the already-signed-in check.
}

// Creates an empty variable to hold any login error message.
$error = '';

// Only runs the login check when the form was submitted by POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Reads the typed email from the username field and trims spaces.
    $email    = trim($_POST['username'] ?? '');
    // Reads the typed password without trimming it.
    $password = $_POST['password'] ?? '';

    // Checks that both the email and password fields were filled in.
    if (empty($email) || empty($password)) {
        // Stores the error asking the user to enter both fields.
        $error = 'Please enter both email and password.';
    // Runs the database login attempt when both fields are present.
    } else {
        // Starts the try block so database errors can be caught.
        try {

            // Creates a database connection only if one does not exist yet.
            if (!isset($pdo) || !($pdo instanceof PDO)) {
                // Reads the database host setting, falling back to localhost.
                $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
                // Reads the database name setting, falling back to the default name.
                $dbName = defined('DB_NAME') ? DB_NAME : 'csite_grad_school';
                // Reads the database username, falling back to root.
                $dbUser = defined('DB_USER') ? DB_USER : 'root';
                // Reads the database password, falling back to an empty string.
                $dbPass = defined('DB_PASS') ? DB_PASS : '';

                // Opens a PDO connection to MySQL using those settings and UTF-8 text.
                $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
                    // Makes the connection throw exceptions when a query fails.
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    // Makes queries return rows as associative arrays by default.
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Closes the array of connection options.
                ]);
            // Closes the connection-creation check.
            }


            // Prepares a query looking for the coordinator account with this email.
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND role = 'coordinator' LIMIT 1");
            // Runs that query, binding the typed email to the placeholder.
            $stmt->execute(['email' => $email]);
            // Fetches the matching user row, or false if none was found.
            $user = $stmt->fetch();

            // Checks the user exists and the typed password matches the stored hash.
            if ($user && password_verify($password, $user['password'])) {
                // Creates a fresh session id to prevent session hijacking.
                session_regenerate_id(true);
                // Stores the signed-in user's details in the session array.
                $_SESSION['user'] = [
                    // Saves the user's database id in the session.
                    'id'        => $user['user_id'],
                    // Saves the user's full name in the session.
                    'full_name' => $user['full_name'],
                    // Saves the user's email address in the session.
                    'email'     => $user['email'],
                    // Saves the user's role so pages can check permissions.
                    'role'      => $user['role'],
                // Closes the session user array.
                ];
                // Queues a welcome message shown on the dashboard.
                setFlash('success', 'Welcome Back, Coordinator Precious Opinion');
                // Redirects the coordinator to the dashboard after login.
                header('Location: ' . url('coordinator/dashboard.php'));
                // Stops the script after the redirect.
                exit;
            // Runs when the email was not found or the password was wrong.
            } else {
                // Stores the error telling the user the login details are invalid.
                $error = 'Invalid email or password.';
            // Closes the password check.
            }
        // Catches database errors such as a wrong host or password.
        } catch (PDOException $e) {
            // Shows the database error message to help with setup problems.
            $error = 'Database Connection Error: ' . $e->getMessage();
        // Closes the try/catch block.
        }
    // Closes the both-fields-present branch.
    }
// Closes the POST request check.
}

// Sets the browser page title to Coordinator Login.
$pageTitle = 'Coordinator Login';
// Marks the page as public so the header shows no side menu.
$role = 'public';
// Adds a CSS class that styles the page as a login screen.
$bodyClass = 'login-body';
// Includes the shared header with styles and scripts.
require_once __DIR__ . '/../includes/header.php';
// Includes the background slideshow used on public pages.
require_once __DIR__ . '/../includes/slideshow.php';
// Closes PHP and switches to HTML output.
?>

<?php /* Outer wrapper that centres the login card on the page. */ ?>
<div class="login-page">
<?php /*     The white card box containing the login form. */ ?>
    <div class="login-card">
<?php /*         Wrapper for the school seal image. */ ?>
        <div class="login-logo">
<?php /*             Shows the Ateneo de Zamboanga University seal. */ ?>
            <img src="<?= asset('img/adzu-seal.png') ?>" alt="AdZU Seal">
<?php /*         Closes the logo wrapper. */ ?>
        </div>
<?php /*         Main heading for the page: Coordinator Portal. */ ?>
        <h1>Coordinator Portal</h1>
<?php /*         Subtitle showing the site short name and the coordinator role. */ ?>
        <p class="login-subtitle"><?= htmlspecialchars(SITE_SHORT) ?> – Graduate Program Coordinator</p>

<?php /*         Only shows the error box when an error message exists. */ ?>
        <?php if (!empty($error)): ?>
<?php /*             Opens a red alert box for the error text. */ ?>
            <div class="alert alert-danger" style="margin-bottom: 1rem;">
<?php /*                 Shows the warning icon and the escaped error message. */ ?>
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
<?php /*             Closes the alert box. */ ?>
            </div>
<?php /*         Closes the error message condition. */ ?>
        <?php endif; ?>

<?php /*         Starts the login form that posts to this same page. */ ?>
        <form class="login-form" action="<?= url('coordinator/login.php') ?>" method="post" data-validate>
<?php /*             Opens the field group for the email input. */ ?>
            <div class="form-group">
<?php /*                 Labels the username field as Username / Email. */ ?>
                <label for="username">Username / Email</label>
<?php /*                 Wrapper that adds the envelope icon inside the input. */ ?>
                <div class="input-icon-wrap">
<?php /*                     Envelope icon shown inside the email input. */ ?>
                    <i class="fas fa-envelope"></i>
<?php /*                     Email input that keeps the typed value after a failed login. */ ?>
                    <input type="email" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="gpc-csite@adzu.edu.ph" required>
<?php /*                 Closes the email input wrapper. */ ?>
                </div>
<?php /*             Closes the email field group. */ ?>
            </div>
<?php /*             Opens the field group for the password input. */ ?>
            <div class="form-group">
<?php /*                 Labels the password field. */ ?>
                <label for="password">Password</label>
<?php /*                 Wrapper that adds the lock icon inside the input. */ ?>
                <div class="input-icon-wrap">
<?php /*                     Lock icon shown inside the password input. */ ?>
                    <i class="fas fa-lock"></i>
<?php /*                     Password input that hides the typed characters. */ ?>
                    <input type="password" id="password" name="password" class="has-toggle" placeholder="Password" required>
<?php /*                     Small eye button that toggles showing or hiding the password. */ ?>
                    <button type="button" class="pw-toggle" data-toggle-password="password" tabindex="-1" aria-label="Show password">
<?php /*                         Eye icon shown on the show/hide password button. */ ?>
                        <i class="fas fa-eye"></i>
<?php /*                     Closes the eye toggle button. */ ?>
                    </button>
<?php /*                 Closes the password input wrapper. */ ?>
                </div>
<?php /*             Closes the password field group. */ ?>
            </div>
<?php /*             Large submit button that sends the login form. */ ?>
            <button type="submit" class="btn btn-primary btn-lg">
<?php /*                 Sign-in icon and the word Login on the button. */ ?>
                <i class="fas fa-sign-in-alt"></i> Login
<?php /*             Closes the submit button. */ ?>
            </button>
<?php /*         Closes the login form. */ ?>
        </form>

<?php /*         Wrapper for the link back to the public home page. */ ?>
        <div class="login-back">
<?php /*             Link returning visitors to the public home page. */ ?>
            <a href="<?= url('public/index.php') ?>"><i class="fas fa-arrow-left"></i> Back to Home</a>
<?php /*         Closes the back-link wrapper. */ ?>
        </div>
<?php /*     Closes the login card. */ ?>
    </div>
<?php /* Closes the outer login page wrapper. */ ?>
</div>

<?php /* Includes the shared footer that closes the page layout. */ ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
