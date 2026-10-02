<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle   = $pageTitle ?? SITE_NAME;
$role        = $role ?? ($_SESSION['user']['role'] ?? 'public');
$currentPage = $currentPage ?? '';
$bodyClass   = $bodyClass ?? '';

if ($role === 'coordinator' && (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'coordinator')) {
    redirectTo('coordinator/login.php');
}
if ($role === 'student' && (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'student')) {
    redirectTo('student/login.php');
}

if (empty($userName) && !empty($_SESSION['user'])) {
    $userName = $_SESSION['user']['full_name'] ?? $_SESSION['user']['name'] ?? '';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars(SITE_TAGLINE) ?>">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= htmlspecialchars(SITE_SHORT) ?></title>
    <link rel="stylesheet" href="<?= asset('vendor/fonts/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/fontawesome/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/style.css?v=20261009') ?>">
</head>
<body class="role-<?= htmlspecialchars($role) ?> <?= htmlspecialchars($bodyClass) ?>">

<?php if ($role !== 'public'): ?>
<div class="app-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="main-wrapper">
        <header class="topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
            <div class="topbar-title">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </div>
            <div class="topbar-user">
                <?php if ($userName): ?>
                <?php if ($role === 'student'): ?><a href="<?= url('student/profile.php') ?>" class="user-name-btn" title="Edit your profile"><?php else: ?><span class="user-name"><?php endif; ?>
                    <i class="fas fa-user-circle"></i> <?= htmlspecialchars($userName) ?>
                    <?php if ($role === 'student'): ?>
                    <i class="fas fa-pencil-alt" style="font-size:0.7rem;opacity:0.6;margin-left:2px;"></i>
                    <?php endif; ?>
                <?php if ($role === 'student'): ?></a><?php else: ?></span><?php endif; ?>
                <?php endif; ?>
                <a href="<?= $role === 'student' ? url('student/logout.php') : url('coordinator/logout.php') ?>" class="btn btn-sm btn-outline" title="Logout">
                    <i class="fas fa-sign-out-alt"></i> <span class="hide-mobile">Logout</span>
                </a>
            </div>
        </header>
        <main class="main-content">
<?php else: ?>
<main class="public-main">
<?php endif; ?>
<?php if ($flash = pullFlash()): ?>
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?>" data-auto-dismiss>
        <i class="fas <?= $flash['type'] === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>
