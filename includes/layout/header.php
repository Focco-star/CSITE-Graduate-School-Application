<?php /* Opens PHP for the shared page header. */ ?>
<?php
// Loads config.php once for site settings and helper functions.
require_once __DIR__ . '/../config/config.php';
// Loads the database class so account validation can be checked on every portal page.
require_once __DIR__ . '/../config/db.php';

// Checks whether a session has already been started.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so login details are available to this page.
    session_start();
// Closes the session check.
}

// Uses the page title if one was provided, otherwise the site name.
$pageTitle   = $pageTitle ?? SITE_NAME;
// Uses the page role if provided, otherwise the logged-in user's role or public.
$role        = $role ?? ($_SESSION['user']['role'] ?? 'public');
// Uses the current page key if provided, otherwise an empty value.
$currentPage = $currentPage ?? '';
// Uses the body class if provided, otherwise an empty value.
$bodyClass   = $bodyClass ?? '';

// Signs out a student whose account was not approved by the coordinator.
if ($role === 'student' && function_exists('enforceStudentAccountApproval')) {
    // Checks the live account status before the page is shown.
    enforceStudentAccountApproval();
// Closes the student account check.
}

// Starts the coordinator notification count at zero.
$pendingAccountCount = 0;
// Counts the students waiting for validation on coordinator pages.
if ($role === 'coordinator' && function_exists('pendingStudentAccountCount')) {
    // Starts a guarded block so a database problem cannot break the layout.
    try {
        // Reads the number of pending student accounts.
        $pendingAccountCount = pendingStudentAccountCount();
    // Ignores errors and leaves the badge empty.
    } catch (Throwable $e) {
        // Falls back to zero when the count cannot be read.
        $pendingAccountCount = 0;
    // Closes the catch block.
    }
// Closes the coordinator notification count check.
}

// Checks for a coordinator page when the session user is not a coordinator.
if ($role === 'coordinator' && (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'coordinator')) {
    // Sends unauthorized visitors to the coordinator login page.
    redirectTo('coordinator/login.php');
// Closes the coordinator access check.
}
// Checks for a student page when the session user is not a student.
if ($role === 'student' && (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'student')) {
    // Sends unauthorized visitors to the student login page.
    redirectTo('student/login.php');
// Closes the student access check.
}

// Checks whether the user name is empty while a user is logged in.
if (empty($userName) && !empty($_SESSION['user'])) {
    // Reads the full name or name from the session user record.
    $userName = $_SESSION['user']['full_name'] ?? $_SESSION['user']['name'] ?? '';
// Closes the user-name fallback block.
}

// Closes PHP so the HTML document can be printed.
?>
<?php /* Declares the document as HTML5. */ ?>
<!DOCTYPE html>
<?php /* Sets the page language to English. */ ?>
<html lang="en">
<?php /* Starts the head section with page metadata and stylesheets. */ ?>
<head>
<?php /*     Sets UTF-8 encoding so special characters display correctly. */ ?>
    <meta charset="UTF-8">
<?php /*     Makes the layout scale correctly on phones and tablets. */ ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php /*     Adds the site tagline as the page description for search engines. */ ?>
    <meta name="description" content="<?= htmlspecialchars(SITE_TAGLINE) ?>">
<?php /*     Prints the page title followed by the short site name. */ ?>
    <title><?= htmlspecialchars($pageTitle) ?> | <?= htmlspecialchars(SITE_SHORT) ?></title>
<?php /*     Loads the local font stylesheet. */ ?>
    <link rel="stylesheet" href="<?= asset('vendor/fonts/fonts.css') ?>">
<?php /*     Loads the Font Awesome icon stylesheet. */ ?>
    <link rel="stylesheet" href="<?= asset('vendor/fontawesome/all.min.css') ?>">
<?php /*     Loads the organized stylesheet sections in their original cascade order. */ ?>
<?php
// Lists every stylesheet section so the cascade order matches the original file.
$csiteStyleSections = [
    'shared/00-base.css',
    'applications/10-student-application.css',
    'coordinator/20-applications-list.css',
    'coordinator/30-process-application.css',
    'coordinator/40-notifications.css',
    'templates/50-templates.css',
    'student/60-dashboard.css',
    'student/70-application-page.css',
    'shared/80-success-popup.css',
    'coordinator/90-charts-calendar.css',
    'student/95-portal-pages.css',
    'coordinator/96-dashboard.css',
    'coordinator/homepagestyle.css',
    'applications/97-coordinator-table.css',
    'coordinator/98-edit-controls.css',
    'applications/99-records.css',
    'shared/pickers.css',
    'shared/responsive.css',
];
// Prints each section with a version based on its last edit time.
foreach ($csiteStyleSections as $csiteStyleSection) {
    // Builds the absolute path used for the version timestamp.
    $csiteStyleFile = __DIR__ . '/../../assets/css/' . $csiteStyleSection;
    // Prints the link for this section.
    echo '    <link rel="stylesheet" href="' . asset('css/' . $csiteStyleSection . '?v=' . filemtime($csiteStyleFile)) . '">' . PHP_EOL;
}
// Clears the temporary header variables.
unset($csiteStyleSections, $csiteStyleSection, $csiteStyleFile);
?>
<?php /* Closes the head section. */ ?>
</head>
<?php /* Starts the body with role and page-specific CSS classes. */ ?>
<body class="role-<?= htmlspecialchars($role) ?> <?= htmlspecialchars($bodyClass) ?>">

<?php /* Checks whether this page belongs to a logged-in role, not the public site. */ ?>
<?php if ($role !== 'public'): ?>
<?php /* Starts the application layout that holds the sidebar and main content. */ ?>
<div class="app-layout">
<?php /*     Includes the sidebar navigation for the current role. */ ?>
    <?php include __DIR__ . '/sidebar.php'; ?>
<?php /*     Starts the main wrapper beside the sidebar. */ ?>
    <div class="main-wrapper">
<?php /*         Starts the top bar above the page content. */ ?>
        <header class="topbar">
<?php /*             Adds the hamburger button that opens and closes the sidebar. */ ?>
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle navigation">
<?php /*                 Shows the hamburger icon inside the button. */ ?>
                <i class="fas fa-bars"></i>
<?php /*             Closes the sidebar toggle button. */ ?>
            </button>
<?php /*             Starts the area that shows the portal name. */ ?>
            <div class="topbar-title">
<?php /*                 Prints Coordinator Portal or Student Portal based on the role. */ ?>
                <span class="topbar-portal"><?= $role === 'coordinator' ? 'Coordinator Portal' : 'Student Portal' ?></span>
<?php /*             Closes the portal title area. */ ?>
            </div>
<?php /*             Starts the area that holds the profile and logout controls. */ ?>
            <div class="topbar-user">
<?php /*                 Shows the notification bell to coordinators only. */ ?>
                <?php if ($role === 'coordinator'): ?>
<?php /*                 Loads the latest notifications for the bell panel. */ ?>
                <?php $coordinatorBell = coordinatorNotifications((int) ($_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? 0)); ?>
<?php /*                 Remembers the page to reopen after a notification is removed. */ ?>
                <?php $notificationReturnPath = coordinatorNotificationReturnPath(); ?>
<?php /*                 Opens the notification bell wrapper. */ ?>
                <div class="topbar-notif" data-topbar-notif>
<?php /*                     Button that opens and closes the notification panel. */ ?>
                    <button type="button" class="topbar-notification<?= (int) $coordinatorBell['unseen'] > 0 ? ' has-count' : '' ?>" data-notif-toggle aria-expanded="false" aria-controls="coordinatorNotificationPanel" title="Notifications" aria-label="Notifications">
<?php /*                         Bell icon that marks the notification area. */ ?>
                        <i class="fas fa-bell" aria-hidden="true"></i>
<?php /*                         Shows the badge only when something is new. */ ?>
                        <?php if ((int) $coordinatorBell['unseen'] > 0): ?>
<?php /*                         Caps the visible count at 9+. */ ?>
                        <span class="topbar-notification-badge"><?= (int) $coordinatorBell['unseen'] > 9 ? '9+' : (int) $coordinatorBell['unseen'] ?></span>
<?php /*                         Closes the badge check. */ ?>
                        <?php endif; ?>
<?php /*                     Closes the notification button. */ ?>
                    </button>
<?php /*                     Panel that lists new notifications and viewed updates. */ ?>
                    <div class="notif-panel" id="coordinatorNotificationPanel" hidden>
<?php /*                         Header of the notification panel. */ ?>
                        <div class="notif-panel-head">
<?php /*                             Panel title. */ ?>
                            <strong><i class="fas fa-bell" aria-hidden="true"></i> Notifications</strong>
<?php /*                             Shows how many items are new. */ ?>
                            <span><?= (int) $coordinatorBell['unseen'] ?> new</span>
<?php /*                         Closes the panel header. */ ?>
                        </div>
<?php /*                         Scrollable body of the panel. */ ?>
                        <div class="notif-panel-body">
<?php /*                             Heading for unseen notifications. */ ?>
                            <div class="notif-section-title">New</div>
<?php /*                             Shows the empty state when nothing is new. */ ?>
                            <?php if (!$coordinatorBell['new']): ?>
                            <p class="notif-empty">No new notifications. You are all caught up.</p>
<?php /*                             Otherwise lists every unseen notification. */ ?>
                            <?php else: ?>
<?php /*                                 Loops over the unseen notifications, newest first. */ ?>
                                <?php foreach ($coordinatorBell['new'] as $notification): ?>
<?php /*                                 One unseen notification linking to the action page. */ ?>
                                <div class="notif-item is-new">
<?php /*                                     Link portion that opens the notification. */ ?>
                                    <a class="notif-item-link" href="<?= url('coordinator/notifications.php?open=' . (int) $notification['notification_id']) ?>">
<?php /*                                         Icon that matches the notification type. */ ?>
                                        <span class="notif-item-icon"><i class="fas <?= $notification['type'] === 'account' ? 'fa-user-check' : ($notification['type'] === 'document' ? 'fa-file-arrow-up' : 'fa-file-circle-check') ?>" aria-hidden="true"></i></span>
<?php /*                                         Title, message and time of the notification. */ ?>
                                        <span class="notif-item-body">
<?php /*                                             Action title. */ ?>
                                            <strong><?= htmlspecialchars((string) $notification['title']) ?></strong>
<?php /*                                             Action message. */ ?>
                                            <span><?= htmlspecialchars((string) $notification['message']) ?></span>
<?php /*                                             When the notification was created. */ ?>
                                            <em><?= htmlspecialchars(coordinatorNotificationTime((string) $notification['created_at'])) ?></em>
<?php /*                                         Closes the notification body. */ ?>
                                        </span>
<?php /*                                     Closes the unseen notification link. */ ?>
                                    </a>
<?php /*                                     Form that removes this notification. */ ?>
                                    <form method="post" action="<?= url('coordinator/notification_remove.php') ?>" class="notif-remove-form">
                                        <input type="hidden" name="notification_id" value="<?= (int) $notification['notification_id'] ?>">
                                        <input type="hidden" name="return" value="<?= htmlspecialchars($notificationReturnPath) ?>">
                                        <button type="submit" class="notif-remove" title="Remove notification" aria-label="Remove notification"><i class="fas fa-xmark" aria-hidden="true"></i></button>
                                    </form>
<?php /*                                 Closes the unseen notification row. */ ?>
                                </div>
<?php /*                                 Ends the unseen notification loop. */ ?>
                                <?php endforeach; ?>
<?php /*                             Closes the non-empty notification branch. */ ?>
                            <?php endif; ?>

<?php /*                             Heading for notifications already viewed. */ ?>
                            <div class="notif-section-title">Updates</div>
<?php /*                             Shows the empty state when nothing was viewed yet. */ ?>
                            <?php if (!$coordinatorBell['updates']): ?>
                            <p class="notif-empty">Notifications you open will stay here for reference.</p>
<?php /*                             Otherwise lists the viewed notifications. */ ?>
                            <?php else: ?>
<?php /*                                 Loops over the viewed notifications, newest first. */ ?>
                                <?php foreach ($coordinatorBell['updates'] as $notification): ?>
<?php /*                                 One viewed notification linking straight to its page. */ ?>
                                <div class="notif-item">
<?php /*                                     Link portion that opens the notification. */ ?>
                                    <a class="notif-item-link" href="<?= url((string) $notification['link']) ?>">
<?php /*                                         Icon that matches the notification type. */ ?>
                                        <span class="notif-item-icon"><i class="fas <?= $notification['type'] === 'account' ? 'fa-user-check' : ($notification['type'] === 'document' ? 'fa-file-arrow-up' : 'fa-file-circle-check') ?>" aria-hidden="true"></i></span>
<?php /*                                         Title, message and viewed time. */ ?>
                                        <span class="notif-item-body">
<?php /*                                             Action title. */ ?>
                                            <strong><?= htmlspecialchars((string) $notification['title']) ?></strong>
<?php /*                                             Action message. */ ?>
                                            <span><?= htmlspecialchars((string) $notification['message']) ?></span>
<?php /*                                             When the notification was viewed. */ ?>
                                            <em>Viewed <?= htmlspecialchars(coordinatorNotificationTime((string) $notification['seen_at'])) ?></em>
<?php /*                                         Closes the notification body. */ ?>
                                        </span>
<?php /*                                     Closes the viewed notification link. */ ?>
                                    </a>
<?php /*                                     Form that removes this notification. */ ?>
                                    <form method="post" action="<?= url('coordinator/notification_remove.php') ?>" class="notif-remove-form">
                                        <input type="hidden" name="notification_id" value="<?= (int) $notification['notification_id'] ?>">
                                        <input type="hidden" name="return" value="<?= htmlspecialchars($notificationReturnPath) ?>">
                                        <button type="submit" class="notif-remove" title="Remove notification" aria-label="Remove notification"><i class="fas fa-xmark" aria-hidden="true"></i></button>
                                    </form>
<?php /*                                 Closes the viewed notification row. */ ?>
                                </div>
<?php /*                                 Ends the viewed notification loop. */ ?>
                                <?php endforeach; ?>
<?php /*                             Closes the viewed notification branch. */ ?>
                            <?php endif; ?>
<?php /*                         Closes the panel body. */ ?>
                        </div>
<?php /*                     Closes the notification panel. */ ?>
                    </div>
<?php /*                 Closes the notification bell wrapper. */ ?>
                </div>
<?php /*                 Closes the coordinator notification check. */ ?>
                <?php endif; ?>
<?php /*                 Checks whether the current user is a student. */ ?>
                <?php if ($role === 'student'): ?>
<?php /*                 Links the avatar to the student profile page. */ ?>
                <a href="<?= url('student/profile.php') ?>" class="topbar-avatar" title="Edit your profile" aria-label="Edit your profile">
<?php /*                     Shows a user icon inside the avatar link. */ ?>
                    <i class="fas fa-user" aria-hidden="true"></i>
<?php /*                 Closes the avatar link. */ ?>
                </a>
<?php /*                 Ends the student-only avatar block. */ ?>
                <?php endif; ?>
<?php /*                 Links to the logout page for the current role. */ ?>
                <a href="<?= $role === 'student' ? url('student/logout.php') : url('coordinator/logout.php') ?>" class="btn btn-sm btn-outline topbar-logout" title="Logout">
<?php /*                     Shows the logout icon and the word Logout. */ ?>
                    <i class="fas fa-sign-out-alt"></i> <span class="hide-mobile">Logout</span>
<?php /*                 Closes the logout link. */ ?>
                </a>
<?php /*             Closes the user controls area. */ ?>
            </div>
<?php /*         Closes the top bar. */ ?>
        </header>
<?php /*         Starts the main content area inside the application layout. */ ?>
        <main class="main-content">
<?php /* Else branch for public pages that do not use the sidebar. */ ?>
<?php else: ?>
<?php /* Starts the simpler main content area for public pages. */ ?>
<main class="public-main">
<?php /* Ends the if/else that chose the application or public layout. */ ?>
<?php endif; ?>
<?php /* Pulls any one-time flash message and checks whether one exists. */ ?>
<?php if ($flash = pullFlash()): ?>
<?php /*     Starts the alert box with danger or success styling. */ ?>
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?>" data-auto-dismiss>
<?php /*         Shows the matching error or success icon. */ ?>
        <i class="fas <?= $flash['type'] === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
<?php /*         Prints the flash message after escaping it for safety. */ ?>
        <?= htmlspecialchars($flash['message']) ?>
<?php /*     Closes the alert box. */ ?>
    </div>
<?php /* Ends the flash message block. */ ?>
<?php endif; ?>
