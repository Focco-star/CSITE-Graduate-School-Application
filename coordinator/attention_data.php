<?php
// Returns the latest homepage attention counts and notifications as JSON.
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
    // Sends a JSON forbidden response.
    http_response_code(403);
    // Sets the JSON content type.
    header('Content-Type: application/json; charset=utf-8');
    // Prints a small error body.
    echo json_encode(['error' => 'forbidden']);
    // Stops the script.
    exit;
// Closes the coordinator check.
}

// Reads the coordinator id (the session stores it as id).
$coordinatorId = (int) ($_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? 0);
// Loads the latest new and viewed notifications.
$notifications = coordinatorNotifications($coordinatorId);
// Counts the applications waiting for review.
$newApplications = coordinatorNewApplicationCount();
// Counts the accounts waiting for validation.
$pendingAccounts = function_exists('pendingStudentAccountCount') ? pendingStudentAccountCount() : 0;

// Builds the notification list shown in the dropdown.
$items = [];
foreach ($notifications['new'] as $notification) {
    // Adds one notification with a ready-to-use link.
    $items[] = [
        'id' => (int) $notification['notification_id'],
        'url' => url('coordinator/notifications.php?open=' . (int) $notification['notification_id']),
        'title' => (string) $notification['title'],
        'message' => (string) $notification['message'],
        'time' => coordinatorNotificationTime((string) $notification['created_at']),
        'type' => (string) $notification['type'],
    ];
}

// Tells the browser not to cache the response.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
// Prints the counts and the notification list.
echo json_encode([
    'new_applications' => $newApplications,
    'pending_accounts' => $pendingAccounts,
    'unseen' => (int) $notifications['unseen'],
    'notifications' => $items,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
