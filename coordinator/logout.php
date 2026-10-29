<?php /* Opens PHP for the coordinator logout script. */ ?>
<?php
// Loads config.php once for site settings and the url() helper.
require_once __DIR__ . '/../includes/config/config.php';

// Checks whether a session is already active.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so it can be cleared.
    session_start();
// Closes the session check.
}


// Empties all session data for the current coordinator.
$_SESSION = [];
// Checks whether PHP is using session cookies.
if (ini_get("session.use_cookies")) {
    // Reads the current session cookie settings.
    $params = session_get_cookie_params();
    // Starts the setcookie call that expires the session cookie.
    setcookie(session_name(), '', time() - 42000,
        // Gets the session cookie name and sets an empty value.
        $params["path"], $params["domain"],
        // Sets an expiry time in the past so the browser deletes the cookie.
        $params["secure"], $params["httponly"]
    // Reuses the current cookie path and domain.
    );
// Reuses the current secure and httponly flags.
}
// Closes the setcookie call.
session_destroy();

// Redirects the browser to the coordinator login page.
header('Location: ' . url('coordinator/login.php'));
// Stops the script after the redirect.
exit;