<?php /* Opens PHP for the student logout script. */ ?>
<?php
// Loads config.php once for site settings and the url() helper.
require_once __DIR__ . '/../includes/config/config.php';

// Checks whether a session is already active.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so it can be cleared.
    session_start();
// Closes the session check.
}

// Empties all session data for the current user.
$_SESSION = [];

// Checks whether PHP is using session cookies.
if (ini_get("session.use_cookies")) {
    // Reads the current session cookie settings.
    $params = session_get_cookie_params();
    // Starts the setcookie call that expires the session cookie.
    setcookie(
        // Gets the session cookie name.
        session_name(),
        // Sets an empty cookie value.
        '',
        // Sets an expiry time in the past so the browser deletes the cookie.
        time() - 42000,
        // Reuses the current cookie path.
        $params["path"],
        // Reuses the current cookie domain.
        $params["domain"],
        // Reuses the current secure flag.
        $params["secure"],
        // Reuses the current httponly flag.
        $params["httponly"]
    // Closes the setcookie call.
    );
// Closes the cookie-clearing block.
}

// Destroys the session on the server.
session_destroy();

// Redirects the browser to the student login page.
header('Location: ' . url('student/login.php'));
// Stops the script after the redirect.
exit;