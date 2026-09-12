<?php

require_once __DIR__ . '/config.php';

$pageTitle   = $pageTitle ?? SITE_NAME;
$role        = $role ?? 'public';
$currentPage = $currentPage ?? '';
$userName    = $userName ?? '';
$bodyClass   = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars(SITE_TAGLINE) ?>">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= htmlspecialchars(SITE_SHORT) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
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
                <span class="<?= $role === 'student' ? 'user-name-btn' : 'user-name' ?>">
                    <i class="fas fa-user-circle"></i> <?= htmlspecialchars($userName) ?>
                    <?php if ($role === 'student'): ?>
                    <i class="fas fa-pencil-alt" style="font-size:0.7rem;opacity:0.6;margin-left:2px;"></i>
                    <?php endif; ?>
                </span>
                <?php endif; ?>
                <a href="<?= $role === 'student' ? url('student/logout.php') : url('coordinator/login.php') ?>" class="btn btn-sm btn-outline" title="Logout">
                    <i class="fas fa-sign-out-alt"></i> <span class="hide-mobile">Logout</span>
                </a>
            </div>
        </header>
        <main class="main-content">
<?php else: ?>
<main class="public-main">
<?php endif; ?>
