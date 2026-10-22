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
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/calendar.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');

function scheduleCalendarRespond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

// ---------------------------------------------------------------------------
// Access control: signed-in coordinators only (same rule as the coordinator pages).
// If your session stores the role under a different key, adjust the two lines below.
// ---------------------------------------------------------------------------
$sessionUser = $_SESSION['user'] ?? null;
$sessionRole = is_array($sessionUser) ? strtolower((string) ($sessionUser['role'] ?? '')) : '';
// Release the session lock so polling never blocks the user's other requests.
session_write_close();

if (!is_array($sessionUser) || $sessionUser === []) {
    scheduleCalendarRespond(401, ['ok' => false, 'error' => 'Your session has expired. Please sign in again.']);
}
if ($sessionRole !== '' && $sessionRole !== 'coordinator') {
    scheduleCalendarRespond(403, ['ok' => false, 'error' => 'You do not have permission to view the schedule.']);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    scheduleCalendarRespond(405, ['ok' => false, 'error' => 'Method not allowed.']);
}

// ---------------------------------------------------------------------------
// Validate the requested range (a month grid is 42 days; allow some slack).
// ---------------------------------------------------------------------------
$start = (string) ($_GET['start'] ?? '');
$end   = (string) ($_GET['end'] ?? '');

if (!scheduleCalendarIsDate($start) || !scheduleCalendarIsDate($end) || $end < $start) {
    scheduleCalendarRespond(400, ['ok' => false, 'error' => 'Invalid date range.']);
}
$spanDays = (int) (new DateTimeImmutable($start))->diff(new DateTimeImmutable($end))->days;
if ($spanDays > 70) {
    scheduleCalendarRespond(400, ['ok' => false, 'error' => 'Date range is too large.']);
}

// ---------------------------------------------------------------------------
// Load and return.
// ---------------------------------------------------------------------------
$db = scheduleCalendarPdo();
if (!$db) {
    scheduleCalendarRespond(503, ['ok' => false, 'error' => 'The schedule database is unavailable right now. Please try again shortly.']);
}

try {
    $result = scheduleCalendarFetch($db, $start, $end);
} catch (Throwable $e) {
    error_log('[schedule calendar] ' . $e->getMessage());
    scheduleCalendarRespond(500, ['ok' => false, 'error' => 'We couldn’t load the schedule. Please try again.']);
}

scheduleCalendarRespond(200, [
    'ok'        => true,
    'timezone'  => scheduleCalendarTimezone()->getName(),
    'today'     => (new DateTimeImmutable('now', scheduleCalendarTimezone()))->format('Y-m-d'),
    'start'     => $start,
    'end'       => $end,
    'signature' => $result['signature'],
    // Force an object even when empty so the browser always receives {}.
    'dates'     => (object) $result['dates'],
]);