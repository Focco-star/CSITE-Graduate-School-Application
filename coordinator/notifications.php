<?php
// Opens a coordinator notification, marks it as viewed, and goes to its page.
require_once __DIR__ . '/../includes/config/config.php';
require_once __DIR__ . '/../includes/config/db.php';

// Starts the session when it is not running yet.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so the signed-in coordinator can be read.
    session_start();
// Closes the session check.
}

// Sends visitors who are not coordinators to the coordinator login page.
if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'coordinator') {
    // Redirects to the login page.
    redirectTo('coordinator/login.php');
// Closes the coordinator check.
}

// Reads the notification id from the link.
$notificationId = (int) ($_GET['open'] ?? 0);
// Reads the signed-in coordinator id (the session stores it as id).
$coordinatorId = (int) ($_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? 0);

// Marks the notification as viewed and reads its destination.
$notification = markCoordinatorNotificationSeen($notificationId, $coordinatorId);
// Starts with the home page as the safe fallback.
$target = 'coordinator/homepage.php';
// Uses the stored link only when it points inside the coordinator area.
if ($notification && preg_match('~^coordinator/[A-Za-z0-9_./?=&%#-]+$~', (string) $notification['link'])) {
    // Uses the notification's own page.
    $target = (string) $notification['link'];
// Closes the link check.
}

// Sends the coordinator to the action page.
redirectTo($target);
