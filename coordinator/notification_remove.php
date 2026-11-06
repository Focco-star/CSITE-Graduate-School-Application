<?php
// Removes one notification from the signed-in coordinator's lists.
require_once __DIR__ . '/../includes/config/config.php';
require_once __DIR__ . '/../includes/config/db.php';

// Starts the session when it is not running yet.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so the coordinator can be identified.
    session_start();
// Closes the session check.
}

// Rejects anyone who is not a signed-in coordinator.
if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'coordinator') {
    // Sends a plain forbidden response.
    http_response_code(403);
    // Prints a short explanation.
    echo 'Forbidden';
    // Stops the script.
    exit;
// Closes the coordinator check.
}

// Reads the coordinator id from either session key style.
$coordinatorId = (int) ($_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? 0);
// Reads the notification to remove.
$notificationId = (int) ($_POST['notification_id'] ?? 0);
// Removes the notification and reports the result with a flash message.
if ($notificationId > 0 && removeCoordinatorNotification($notificationId, $coordinatorId)) {
    // Tells the coordinator that the row was removed.
    setFlash('success', 'Notification removed.');
// Explains when the notification was already removed or could not be found.
} else {
    // Shows a safe message for an unavailable notification.
    setFlash('error', 'That notification could not be removed. It may already be gone.');
// Closes the removal result check.
}

// Starts with the coordinator homepage as the safe return page.
$returnPath = 'coordinator/homepage.php';
// Reads the page that submitted the removal form.
$postedReturn = trim((string) ($_POST['return'] ?? ''));
// Accepts only a coordinator page path.
if ($postedReturn !== '' && preg_match('~^coordinator/[A-Za-z0-9_./?=&%#-]+$~', $postedReturn)) {
    // Reopens the page the coordinator was using.
    $returnPath = $postedReturn;
// Closes the return path check.
}
// Returns to the page that showed the notification.
redirectTo($returnPath);
