<?php /* Opens PHP for the background template-sync endpoint. */ ?>
<?php
/**
 * Background check of the Google Drive template folder.
 *
 * The sidebar calls this after every page load so the Templates badge shows
 * a file the teacher has just added or replaced. The endpoint only looks at
 * Google Drive: it never downloads anything, so a change stays "waiting"
 * until the coordinator clicks "Sync from Google Drive". The changes are
 * then applied and the students are told about them.
 *
 * Answer: {"ok":bool,"count":int,"pending":int,"pendingAdded":int,
 *          "pendingUpdated":int,"message":string}
 */

// Loads config.php once for settings and helper functions.
require_once __DIR__ . '/includes/config/config.php';
// Loads db.php once for database access.
require_once __DIR__ . '/includes/config/db.php';

// Tells the browser that the response is JSON.
header('Content-Type: application/json; charset=utf-8');
// Prevents the browser from caching the response.
header('Cache-Control: no-store, no-cache, must-revalidate');

// Reads the logged-in user's role from the session.
$sessionRole = (string) ($_SESSION['user']['role'] ?? '');
// Checks whether the user is a student or coordinator.
if ($sessionRole !== 'student' && $sessionRole !== 'coordinator') {
    // Starts the JSON error response for unauthorized users.
    echo json_encode([
        // Sets ok to false and all counts to zero.
        'ok' => false, 'count' => 0, 'pending' => 0,
        // Leaves the message empty for unauthorized users.
        'pendingAdded' => 0, 'pendingUpdated' => 0, 'message' => '',
    // Closes the JSON error response array.
    ]);
    // Stops the script for unauthorized users.
    exit;
// Closes the role check.
}

// Chooses coordinator or student as the active role.
$role  = $sessionRole === 'coordinator' ? 'coordinator' : 'student';
// Gets the student's track code, or an empty value for coordinators.
$track = $role === 'student' ? currentStudentTrackCode() : '';

// Look at Google Drive to find out what changed. Nothing is downloaded.
// Asks Google Drive which template files have changed.
$detect = templateDetectDriveChanges();

// Gets the pending template changes for this role and track.
$pending = templatePendingChanges($role === 'student' ? $track : null);
// Starts the counter for newly added templates.
$added   = 0;
// Starts the counter for updated templates.
$updated = 0;
// Loops through each pending template change.
foreach ($pending as $item) {
    // Checks whether this change is a new file.
    if (($item['change_type'] ?? '') === 'added') {
        // Counts the change as an addition.
        $added++;
    // Else branch for a replaced or updated file.
    } else {
        // Counts the change as an update.
        $updated++;
    // Closes the else branch.
    }
// Ends the loop over pending changes.
}

// Starts the JSON success response.
echo json_encode([
    // Reports whether the Google Drive check succeeded.
    'ok'             => (bool) ($detect['ok'] ?? false),
    // Reports the number of unread notifications for this role.
    'count'          => templateNotificationsUnreadCount($role, $track),
    // Reports how many template changes are pending.
    'pending'        => count($pending),
    // Reports how many pending changes are additions.
    'pendingAdded'   => $added,
    // Reports how many pending changes are updates.
    'pendingUpdated' => $updated,
    // Includes any message returned by the Drive check.
    'message'        => (string) ($detect['message'] ?? ''),
// Closes the JSON success response.
]);
