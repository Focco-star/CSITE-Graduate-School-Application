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

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$sessionRole = (string) ($_SESSION['user']['role'] ?? '');
if ($sessionRole !== 'student' && $sessionRole !== 'coordinator') {
    echo json_encode([
        'ok' => false, 'count' => 0, 'pending' => 0,
        'pendingAdded' => 0, 'pendingUpdated' => 0, 'message' => '',
    ]);
    exit;
}

$role  = $sessionRole === 'coordinator' ? 'coordinator' : 'student';
$track = $role === 'student' ? currentStudentTrackCode() : '';

// Look at Google Drive to find out what changed. Nothing is downloaded.
$detect = templateDetectDriveChanges();

$pending = templatePendingChanges($role === 'student' ? $track : null);
$added   = 0;
$updated = 0;
foreach ($pending as $item) {
    if (($item['change_type'] ?? '') === 'added') {
        $added++;
    } else {
        $updated++;
    }
}

echo json_encode([
    'ok'             => (bool) ($detect['ok'] ?? false),
    'count'          => templateNotificationsUnreadCount($role, $track),
    'pending'        => count($pending),
    'pendingAdded'   => $added,
    'pendingUpdated' => $updated,
    'message'        => (string) ($detect['message'] ?? ''),
]);
