<?php /* Opens PHP for the shared page header. */ ?>
<?php
// Loads config.php once for site settings and helper functions.
require_once __DIR__ . '/config.php';

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
<?php /*     Loads the main stylesheet with a version based on its last edit time. */ ?>
    <link rel="stylesheet" href="<?= asset('css/style.css?v=' . filemtime(__DIR__ . '/../assets/css/style.css')) ?>">
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
