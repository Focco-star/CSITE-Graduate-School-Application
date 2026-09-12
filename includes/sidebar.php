<?php

$role        = $role ?? 'student';
$currentPage = $currentPage ?? '';

$studentNav = [
    ['key' => 'dashboard',    'label' => 'Dashboard',   'icon' => 'fa-home',     'href' => url('student/dashboard.php')],
    ['key' => 'application',  'label' => 'Application', 'icon' => 'fa-file-alt', 'href' => url('student/application.php')],
    [
        'key'     => 'process',
        'label'   => 'Process',
        'icon'    => 'fa-tasks',
        'submenu' => [
            ['key' => 'requirements', 'label' => 'Requirements', 'icon' => 'fa-list-check', 'href' => url('student/requirements.php')],
            ['key' => 'templates',    'label' => 'Templates',    'icon' => 'fa-download',   'href' => url('student/templates.php')],
        ],
    ],
];

$coordinatorNav = [
    ['key' => 'dashboard',    'label' => 'Dashboard',              'icon' => 'fa-home',           'href' => url('coordinator/dashboard.php')],
    ['key' => 'applications', 'label' => 'Applications',           'icon' => 'fa-file-alt',       'href' => url('coordinator/applications/manage.php')],
    ['key' => 'students',     'label' => 'Students',               'icon' => 'fa-user-graduate',  'href' => url('coordinator/students/manage.php')],
    ['key' => 'templates',    'label' => 'Templates / Forms',      'icon' => 'fa-folder-open',    'href' => url('coordinator/templates/manage.php')],
    ['key' => 'panel',        'label' => 'Panel Members',          'icon' => 'fa-users',          'href' => url('coordinator/panel/manage.php')],
    ['key' => 'schedule',     'label' => 'Presentation Schedules', 'icon' => 'fa-calendar-alt',   'href' => url('coordinator/schedule/manage.php')],
    ['key' => 'reports',      'label' => 'Reports',                'icon' => 'fa-chart-bar',      'href' => url('coordinator/reports/dashboard.php')],
];

$navItems = $role === 'coordinator' ? $coordinatorNav : $studentNav;
$processOpen = in_array($currentPage, ['requirements', 'templates'], true);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">
            <img src="<?= asset('img/adzu-seal.png') ?>" alt="ADZU" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <div class="brand-logo-fallback"><i class="fas fa-university"></i></div>
        </div>
        <div class="brand-text">
            <strong><?= htmlspecialchars(SITE_SHORT) ?></strong>
            <small><?= $role === 'coordinator' ? 'Coordinator Portal' : 'Student Portal' ?></small>
        </div>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <?php foreach ($navItems as $item): ?>
            <li>
                <?php if (!empty($item['submenu'])): ?>
                <div class="nav-parent-item <?= $processOpen ? 'active' : '' ?>" data-submenu-toggle>
                    <div class="nav-parent-content">
                        <i class="fas <?= $item['icon'] ?>"></i>
                        <span><?= htmlspecialchars($item['label']) ?></span>
                    </div>
                    <i class="fas fa-chevron-down nav-chevron <?= $processOpen ? 'open' : '' ?>"></i>
                </div>
                <div class="nav-submenu <?= $processOpen ? 'open' : '' ?>">
                    <?php foreach ($item['submenu'] as $sub): ?>
                    <a href="<?= $sub['href'] ?>" class="<?= navActive($sub['key'], $currentPage) ?>">
                        <i class="fas <?= $sub['icon'] ?>"></i>
                        <span><?= htmlspecialchars($sub['label']) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <a href="<?= $item['href'] ?>" class="<?= navActive($item['key'], $currentPage) ?>">
                    <i class="fas <?= $item['icon'] ?>"></i>
                    <span><?= htmlspecialchars($item['label']) ?></span>
                </a>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="<?= url('public/index.php') ?>" class="sidebar-home-link">
            <i class="fas fa-arrow-left"></i> <span>Back to Home</span>
        </a>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
