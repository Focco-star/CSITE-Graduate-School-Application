<?php /* Starts the PHP script that returns calendar events as JSON. */ ?>
<?php
/**
 * JSON endpoint for the Presentation Schedule Calendar.
 *
 *   GET calendar_data.php?start=YYYY-MM-DD&end=YYYY-MM-DD
 *
 * Returns every presentation in that range, grouped by date:
 *   { "ok": true, "timezone": "Asia/Manila", "today": "2026-10-22",
 *     "start": "...", "end": "...", "signature": "...",
 *     "dates": { "2026-10-10": [ { "student": "...", "title": "...", ... } ] } }
 */
// Loads config settings such as the timezone and helper functions.
require_once __DIR__ . '/../../includes/config/config.php';
// Loads the database helper used by the calendar functions.
require_once __DIR__ . '/../../includes/config/db.php';
// Loads the calendar helper file with the shared schedule functions.
require_once __DIR__ . '/../../includes/components/calendar.php';

// Checks whether no session is running yet.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so the signed-in user can be read.
    session_start();
// Closes the session start check.
}

// Tells the browser the response is JSON in UTF-8.
header('Content-Type: application/json; charset=utf-8');
// Tells the browser not to cache this response.
header('Cache-Control: no-store, max-age=0');
// Stops the browser from guessing the content type.
header('X-Content-Type-Options: nosniff');

// Defines a helper that sends a JSON response and stops the script.
function scheduleCalendarRespond(int $status, array $payload): void
// Opens the helper function body.
{
    // Sets the HTTP status code on the response.
    http_response_code($status);
    // Prints the payload as JSON, keeping unicode and slashes readable.
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    // Stops the script so nothing else is printed after the JSON.
    exit;
// Closes the scheduleCalendarRespond helper function.
}

// ---------------------------------------------------------------------------
// Access control: signed-in coordinators only (same rule as the coordinator pages).
// If your session stores the role under a different key, adjust the two lines below.
// ---------------------------------------------------------------------------
// Reads the signed-in user array from the session, if it exists.
$sessionUser = $_SESSION['user'] ?? null;
// Reads the role in lowercase, or empty text when there is none.
$sessionRole = is_array($sessionUser) ? strtolower((string) ($sessionUser['role'] ?? '')) : '';
// Release the session lock so polling never blocks the user's other requests.
// Releases the session lock so other requests are not blocked.
session_write_close();

// Rejects the request when no user is signed in.
if (!is_array($sessionUser) || $sessionUser === []) {
    // Sends error 401 telling the user to sign in again.
    scheduleCalendarRespond(401, ['ok' => false, 'error' => 'Your session has expired. Please sign in again.']);
// Closes the signed-in check block.
}
// Rejects users whose stored role is not coordinator.
if ($sessionRole !== '' && $sessionRole !== 'coordinator') {
    // Sends error 403 saying the user has no permission.
    scheduleCalendarRespond(403, ['ok' => false, 'error' => 'You do not have permission to view the schedule.']);
// Closes the role check block.
}

// Checks that the request used the GET method.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    // Sends error 405 because only GET is allowed.
    scheduleCalendarRespond(405, ['ok' => false, 'error' => 'Method not allowed.']);
// Closes the request method check block.
}

// ---------------------------------------------------------------------------
// Validate the requested range (a month grid is 42 days; allow some slack).
// ---------------------------------------------------------------------------
// Reads the start date sent by the calendar page.
$start = (string) ($_GET['start'] ?? '');
// Reads the end date sent by the calendar page.
$end   = (string) ($_GET['end'] ?? '');

// Checks both dates are valid and the end is not before the start.
if (!scheduleCalendarIsDate($start) || !scheduleCalendarIsDate($end) || $end < $start) {
    // Sends error 400 because the date range is invalid.
    scheduleCalendarRespond(400, ['ok' => false, 'error' => 'Invalid date range.']);
// Closes the invalid range check block.
}
// Works out how many days the requested range covers.
$spanDays = (int) (new DateTimeImmutable($start))->diff(new DateTimeImmutable($end))->days;
// Checks the range is not longer than 70 days.
if ($spanDays > 70) {
    // Sends error 400 because the range is too large.
    scheduleCalendarRespond(400, ['ok' => false, 'error' => 'Date range is too large.']);
// Closes the range length check block.
}

// ---------------------------------------------------------------------------
// Load and return.
// ---------------------------------------------------------------------------
// Gets a database connection for the schedule tables.
$db = scheduleCalendarPdo();
// Checks whether the database connection failed.
if (!$db) {
    // Sends error 503 saying the schedule database is unavailable.
    scheduleCalendarRespond(503, ['ok' => false, 'error' => 'The schedule database is unavailable right now. Please try again shortly.']);
// Closes the database check block.
}

// Opens a try block around the schedule loading.
try {
    // Fetches all presentations between the start and end dates.
    $result = scheduleCalendarFetch($db, $start, $end);
// Catches any error thrown while loading the schedule.
} catch (Throwable $e) {
    // Writes the error message to the server log.
    error_log('[schedule calendar] ' . $e->getMessage());
    // Sends error 500 saying the schedule could not be loaded.
    scheduleCalendarRespond(500, ['ok' => false, 'error' => 'We couldn’t load the schedule. Please try again.']);
// Closes the try/catch block.
}

// Sends the successful JSON response with the schedule data.
scheduleCalendarRespond(200, [
    // Marks the response as ok so the page knows it worked.
    'ok'        => true,
    // Includes the timezone name used for the dates.
    'timezone'  => scheduleCalendarTimezone()->getName(),
    // Includes today's date in the schedule timezone.
    'today'     => (new DateTimeImmutable('now', scheduleCalendarTimezone()))->format('Y-m-d'),
    // Echoes the start date that was requested.
    'start'     => $start,
    // Echoes the end date that was requested.
    'end'       => $end,
    // Includes a signature so the page can detect changes.
    'signature' => $result['signature'],
    // Notes that dates are cast to an object so the browser gets {}.
    // Force an object even when empty so the browser always receives {}.
    // Sends dates as a JSON object so empty results still look like {}.
    'dates'     => (object) $result['dates'],
// Closes the response array and the helper call.
]);