<?php
/**
 * MyADZU — Vertical Sidebar Navigation Panel
 * ------------------------------------------------------------------
 * Modular layout partial. Include from includes/header.php via:
 *   $role = 'student' | 'coordinator'; $currentPage = 'dashboard'; ...
 *   include __DIR__ . '/sidebar.php';
 *
 * Theme:
 *   Sidebar bg  : Dark Slate Grey #232b35
 *   Brand bar   : Dark Blue       #0f2a4a (AdZU blue)
 *   Text        : off-white / light grey #e8edf2 for contrast
 *
 * Behaviour:
 *   - CSITE roots + nested submenus toggle via [data-submenu-toggle] + .open
 *     (handled by assets/js/script.js — no inline framework needed).
 *   - Search field live-filters menu items via [data-menu-filter].
 *   - Active states resolved with PHP (navActive() from config.php).
 */

$role        = $role ?? 'student';
$currentPage = $currentPage ?? '';
$userName    = $userName ?? ($_SESSION['user']['full_name'] ?? '');
$isCoordinator = ($role === 'coordinator');

$studentLinks = [
    ['key' => 'dashboard',   'label' => 'Dashboard',   'icon' => 'fa-gauge-high', 'href' => url('student/dashboard.php')],
    ['key' => 'application', 'label' => 'Application', 'icon' => 'fa-file-lines', 'href' => url('student/application.php')],
    [
        'key'     => 'process',
        'label'   => 'Process',
        'icon'    => 'fa-gears',
        'submenu' => [
            ['key' => 'requirements', 'label' => 'Requirements', 'icon' => 'fa-list-check', 'href' => url('student/requirements.php')],
            ['key' => 'templates',    'label' => 'Templates',    'icon' => 'fa-file-arrow-down', 'href' => url('student/templates.php')],
        ],
    ],
];

$coordinatorLinks = [
    ['key' => 'dashboard',    'label' => 'Dashboard',              'icon' => 'fa-gauge-high',     'href' => url('coordinator/dashboard.php')],
    [
        'key'     => 'applications',
        'label'   => 'Applications',
        'icon'    => 'fa-folder-open',
        'submenu' => [
            ['key' => 'applications',          'label' => 'Applications',         'icon' => 'fa-inbox',       'href' => url('coordinator/applications/manage.php')],
            ['key' => 'archived_applications', 'label' => 'Archived Application', 'icon' => 'fa-box-archive', 'href' => url('coordinator/applications/archived.php')],
        ],
    ],
    [
        'key'     => 'students',
        'label'   => 'Students',
        'icon'    => 'fa-user-graduate',
        'submenu' => [
            ['key' => 'students',          'label' => 'Students',          'icon' => 'fa-users',       'href' => url('coordinator/students/manage.php')],
            ['key' => 'archived_students', 'label' => 'Archived Students', 'icon' => 'fa-box-archive', 'href' => url('coordinator/students/archived.php')],
        ],
    ],
    ['key' => 'templates', 'label' => 'Templates/Form',         'icon' => 'fa-file-pen',      'href' => url('coordinator/templates/manage.php')],
    ['key' => 'panel',     'label' => 'Panel Members',          'icon' => 'fa-users-gear',    'href' => url('coordinator/panel/manage.php')],
    ['key' => 'advisers',  'label' => 'Pool of Advisers',       'icon' => 'fa-user-tie',      'href' => url('coordinator/advisers/manage.php')],
    ['key' => 'schedule',  'label' => 'Presentation Schedules', 'icon' => 'fa-calendar-days', 'href' => url('coordinator/schedule/manage.php')],
    ['key' => 'reports',   'label' => 'Reports',                'icon' => 'fa-chart-column',  'href' => url('coordinator/reports/dashboard.php')],
];

$socialLinks = [
    ['label' => 'Facebook',   'icon' => 'fa-facebook-f', 'href' => 'https://www.facebook.com/ateneodezamboangauniversity'],
    ['label' => 'Twitter / X','icon' => 'fa-x-twitter',  'href' => 'https://x.com/AdZUOfficial'],
    ['label' => 'Instagram',  'icon' => 'fa-instagram',  'href' => 'https://www.instagram.com/adzuofficial/'],
    ['label' => 'TikTok',     'icon' => 'fa-tiktok',     'href' => 'https://www.tiktok.com/@adzuofficial'],
    ['label' => 'YouTube',    'icon' => 'fa-youtube',    'href' => 'https://www.youtube.com/@ateneodezamboangauniversity'],
];

/** Collect every key inside a link tree (parent + children). */
$collectKeys = static function (array $links) use (&$collectKeys): array {
    $keys = [];
    foreach ($links as $item) {
        if (!empty($item['key'])) {
            $keys[] = $item['key'];
        }
        if (!empty($item['submenu'])) {
            $keys = array_merge($keys, $collectKeys($item['submenu']));
        }
    }
    return $keys;
};

$studentKeys    = $collectKeys($studentLinks);
$coordinatorKeys = $collectKeys($coordinatorLinks);

$csiteStudentOpen = in_array($currentPage, $studentKeys, true);
$csiteCoordOpen   = in_array($currentPage, $coordinatorKeys, true);

$isSubmenuChildActive = static function (array $submenu, string $current): bool {
    foreach ($submenu as $sub) {
        if (($sub['key'] ?? '') === $current) {
            return true;
        }
        if (!empty($sub['submenu'])) {
            foreach ($sub['submenu'] as $nested) {
                if (($nested['key'] ?? '') === $current) {
                    return true;
                }
            }
        }
    }
    return false;
};

$showStudentSection    = !$isCoordinator;
$showCoordinatorSection = $isCoordinator;
?>

<style id="myadzu-sidebar-styles">
  /* ===== MyADZU Sidebar — Dark Slate #232b35 / Brand Blue #0f2a4a ===== */
  #sidebar.myadzu-sidebar {
    background: #232b35;
    border-right: 1px solid rgba(255,255,255,.07);
    color: #e8edf2;
    width: var(--sidebar-width, 260px);
  }
  #sidebar.myadzu-sidebar .myadzu-brandbar {
    background: #0f2a4a;
    color: #ffffff;
    text-align: center;
    font-family: var(--font-heading, 'Montserrat', sans-serif);
    font-weight: 800;
    font-size: 1.15rem;
    letter-spacing: .01em;
    padding: .85rem 1rem;
    line-height: 1.2;
  }
  #sidebar.myadzu-sidebar .myadzu-profile {
    padding: 1.1rem 1rem .9rem;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,.07);
    background: #232b35;
  }
  #sidebar.myadzu-sidebar .myadzu-avatar {
    width: 96px; height: 96px;
    margin: 0 auto .35rem;
    border-radius: 50%;
    border: 3px solid rgba(255,255,255,.9);
    position: relative;
    overflow: hidden;
    display: flex; align-items: center; justify-content: center;
    background: #ffffff;
  }
  #sidebar.myadzu-sidebar .myadzu-avatar img.myadzu-seal {
    position: absolute; inset: 0;
    width: 100%; height: 100%;
    object-fit: cover;
    opacity: 1;
    pointer-events: none;
  }
  #sidebar.myadzu-sidebar .myadzu-username {
    font-size: .8rem; color: #cbd5e1;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    margin-bottom: .7rem;
  }
  #sidebar.myadzu-sidebar .myadzu-search { position: relative; }
  #sidebar.myadzu-sidebar .myadzu-search input {
    width: 100%;
    background: #1a222c;
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 6px;
    color: #e8edf2;
    font-size: .82rem;
    padding: .55rem 2.2rem .55rem .8rem;
    font-family: inherit;
  }
  #sidebar.myadzu-sidebar .myadzu-search input::placeholder { color: #8a94a6; }
  #sidebar.myadzu-sidebar .myadzu-search input:focus {
    outline: none; border-color: #FFB82B;
    box-shadow: 0 0 0 2px rgba(255,184,43,.2);
  }
  #sidebar.myadzu-sidebar .myadzu-search i {
    position: absolute; right: .8rem; top: 50%;
    transform: translateY(-50%);
    color: #8a94a6; font-size: .85rem; pointer-events: none;
  }
  #sidebar.myadzu-sidebar .myadzu-nav { flex: 1; overflow-y: auto; padding: .4rem 0 1rem; }
  #sidebar.myadzu-sidebar .myadzu-section-label {
    font-size: .68rem; font-weight: 700;
    letter-spacing: .06em;
    color: #8a94a6;
    padding: 1rem 1.25rem .45rem;
    text-transform: uppercase;
  }
  #sidebar.myadzu-sidebar .myadzu-nav ul { list-style: none; margin: 0; padding: 0; }
  /* Root CSITE toggle */
  #sidebar.myadzu-sidebar .myadzu-root {
    display: flex; align-items: center; justify-content: space-between;
    padding: .7rem 1.25rem;
    color: #ffffff; font-weight: 700; font-size: .9rem;
    cursor: pointer; user-select: none;
    border-left: 3px solid transparent;
    transition: background .2s ease;
  }
  #sidebar.myadzu-sidebar .myadzu-root:hover { background: #2e3844; }
  #sidebar.myadzu-sidebar .myadzu-root.is-open { background: #1c242e; border-left-color: #FFB82B; }
  #sidebar.myadzu-sidebar .myadzu-root-left { display: flex; align-items: center; gap: .7rem; }
  #sidebar.myadzu-sidebar .myadzu-root-left > i { color: #FFB82B; width: 20px; text-align: center; }
  #sidebar.myadzu-sidebar .myadzu-root .nav-chevron { color: #8a94a6; font-size: .75rem; transition: transform .15s ease; }
  #sidebar.myadzu-sidebar .myadzu-root .nav-chevron.open { transform: rotate(180deg); }
  /* Child links — instant show/hide (height animations stutter on low-end machines) */
  #sidebar.myadzu-sidebar .myadzu-children { display: none; contain: layout style; }
  #sidebar.myadzu-sidebar .myadzu-children.open { display: block; }
  #sidebar.myadzu-sidebar .myadzu-children > ul { min-height: 0; }
  #sidebar.myadzu-sidebar .myadzu-link,
  #sidebar.myadzu-sidebar .myadzu-sub-toggle {
    display: flex; align-items: center; gap: .65rem;
    padding: .55rem 1.25rem .55rem 2.6rem;
    color: #cbd5e1; font-size: .85rem; font-weight: 500;
    border-left: 3px solid transparent;
    text-decoration: none;
    transition: background .18s ease, color .18s ease;
  }
  #sidebar.myadzu-sidebar .myadzu-sub-toggle { cursor: pointer; user-select: none; width: 100%; box-sizing: border-box; }
  #sidebar.myadzu-sidebar .myadzu-link i,
  #sidebar.myadzu-sidebar .myadzu-sub-toggle > i:first-child { width: 18px; text-align: center; font-size: .8rem; color: #8fa0b5; }
  #sidebar.myadzu-sidebar .myadzu-link:hover,
  #sidebar.myadzu-sidebar .myadzu-sub-toggle:hover { background: #2e3844; color: #ffffff; }
  #sidebar.myadzu-sidebar .myadzu-link.active { background: #1a222c; color: #ffffff; border-left-color: #FFB82B; font-weight: 700; }
  #sidebar.myadzu-sidebar .myadzu-link.active i { color: #FFB82B; }
  #sidebar.myadzu-sidebar .myadzu-sub-toggle.is-open { color: #ffffff; background: #1c242e; }
  #sidebar.myadzu-sidebar .myadzu-sub-toggle .nav-chevron { margin-left: auto; font-size: .65rem; color: #8a94a6; }
  #sidebar.myadzu-sidebar .myadzu-nested { display: none; contain: layout style; }
  #sidebar.myadzu-sidebar .myadzu-nested.open { display: block; }
  #sidebar.myadzu-sidebar .myadzu-nested > ul { min-height: 0; }
  #sidebar.myadzu-sidebar .myadzu-nested .myadzu-link { padding-left: 3.8rem; font-size: .82rem; }
  /* Socials */
  #sidebar.myadzu-sidebar .myadzu-socials { padding-bottom: 1rem; border-top: 1px solid rgba(255,255,255,.07); }
  #sidebar.myadzu-sidebar .myadzu-social-link {
    display: flex; align-items: center; gap: .7rem;
    padding: .5rem 1.25rem;
    color: #cbd5e1; font-size: .85rem; text-decoration: none;
    transition: background .18s ease, color .18s ease;
  }
  #sidebar.myadzu-sidebar .myadzu-social-link i { width: 20px; text-align: center; color: #FFB82B; font-size: .9rem; }
  #sidebar.myadzu-sidebar .myadzu-social-link:hover { background: #2e3844; color: #ffffff; }
  #sidebar.myadzu-sidebar .myadzu-empty { padding: .4rem 1.25rem; font-size: .78rem; color: #8a94a6; font-style: italic; }
  #sidebar.myadzu-sidebar::-webkit-scrollbar,
  #sidebar.myadzu-sidebar .myadzu-nav::-webkit-scrollbar { width: 8px; }
  #sidebar.myadzu-sidebar .myadzu-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 4px; }
  /* Perf: zero animation inside sidebar so toggles apply instantly (no tween jank) */
  #sidebar.myadzu-sidebar, #sidebar.myadzu-sidebar * { transition: none !important; animation: none !important; }
</style>

<aside class="sidebar myadzu-sidebar" id="sidebar" aria-label="MyADZU sidebar navigation">

  <!-- 1. TOP BRANDING & PROFILE SECTION -->
  <div class="myadzu-brandbar">MyADZU</div>

  <div class="myadzu-profile">
    <div class="myadzu-avatar" role="img" aria-label="ADZU seal">
      <img class="myadzu-seal" src="<?= asset('img/adzu-seal.png') ?>" alt="ADZU seal" onerror="this.style.display='none'">
    </div>
    <?php if (!empty($userName)): ?>
      <div class="myadzu-username"><?= htmlspecialchars($userName) ?></div>
    <?php endif; ?>
    <div class="myadzu-search">
      <label for="myadzuMenuSearch" class="sr-only" style="position:absolute;left:-9999px;">Search menu</label>
      <input type="search" id="myadzuMenuSearch" placeholder="Search menu..." autocomplete="off" data-menu-filter aria-label="Search menu">
      <i class="fas fa-search" aria-hidden="true"></i>
    </div>
  </div>

  <!-- 2 + 3. NAVIGATION SECTIONS -->
  <nav class="myadzu-nav" aria-label="Portal navigation">

    <?php if ($showStudentSection): ?>
      <div class="myadzu-section-label">Main Navigation</div>
      <ul>
        <li>
          <div class="myadzu-root <?= $csiteStudentOpen ? 'is-open' : '' ?>"
               data-submenu-toggle role="button" tabindex="0"
               aria-expanded="<?= $csiteStudentOpen ? 'true' : 'false' ?>">
            <span class="myadzu-root-left">
              <i class="fas fa-user-graduate" aria-hidden="true"></i>
              <span>CSITE</span>
            </span>
            <i class="fas fa-chevron-down nav-chevron <?= $csiteStudentOpen ? 'open' : '' ?>" aria-hidden="true"></i>
          </div>
          <div class="myadzu-children <?= $csiteStudentOpen ? 'open' : '' ?>">
            <ul>
              <?php foreach ($studentLinks as $item): ?>
                <li data-menu-item data-menu-label="<?= htmlspecialchars(strtolower($item['label'])) ?>">
                  <?php if (!empty($item['submenu'])): ?>
                    <?php $subOpen = $isSubmenuChildActive($item['submenu'], $currentPage) || ($item['key'] === $currentPage); ?>
                    <div class="myadzu-sub-toggle <?= $subOpen ? 'is-open' : '' ?>"
                         data-submenu-toggle role="button" tabindex="0"
                         aria-expanded="<?= $subOpen ? 'true' : 'false' ?>">
                      <i class="fas <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
                      <span><?= htmlspecialchars($item['label']) ?></span>
                      <i class="fas fa-chevron-down nav-chevron <?= $subOpen ? 'open' : '' ?>" aria-hidden="true"></i>
                    </div>
                    <div class="myadzu-nested <?= $subOpen ? 'open' : '' ?>">
                      <ul>
                        <?php foreach ($item['submenu'] as $sub): ?>
                          <li data-menu-item data-menu-label="<?= htmlspecialchars(strtolower($sub['label'])) ?>">
                            <a class="myadzu-link <?= navActive($sub['key'], $currentPage) ?>"
                               href="<?= $sub['href'] ?>">
                              <i class="fas <?= htmlspecialchars($sub['icon']) ?>" aria-hidden="true"></i>
                              <span><?= htmlspecialchars($sub['label']) ?></span>
                            </a>
                          </li>
                        <?php endforeach; ?>
                      </ul>
                    </div>
                  <?php else: ?>
                    <a class="myadzu-link <?= navActive($item['key'], $currentPage) ?>"
                       href="<?= $item['href'] ?>">
                      <i class="fas <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
                      <span><?= htmlspecialchars($item['label']) ?></span>
                    </a>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </li>
      </ul>
    <?php endif; ?>

    <?php if ($showCoordinatorSection): ?>
      <div class="myadzu-section-label">Coordinator</div>
      <ul>
        <li>
          <div class="myadzu-root <?= $csiteCoordOpen ? 'is-open' : '' ?>"
               data-submenu-toggle role="button" tabindex="0"
               aria-expanded="<?= $csiteCoordOpen ? 'true' : 'false' ?>">
            <span class="myadzu-root-left">
              <i class="fas fa-user-graduate" aria-hidden="true"></i>
              <span>CSITE</span>
            </span>
            <i class="fas fa-chevron-down nav-chevron <?= $csiteCoordOpen ? 'open' : '' ?>" aria-hidden="true"></i>
          </div>
          <div class="myadzu-children <?= $csiteCoordOpen ? 'open' : '' ?>">
            <ul>
              <?php foreach ($coordinatorLinks as $item): ?>
                <li data-menu-item data-menu-label="<?= htmlspecialchars(strtolower($item['label'])) ?>">
                  <?php if (!empty($item['submenu'])): ?>
                    <?php $subOpen = $isSubmenuChildActive($item['submenu'], $currentPage) || ($item['key'] === $currentPage); ?>
                    <div class="myadzu-sub-toggle <?= $subOpen ? 'is-open' : '' ?>"
                         data-submenu-toggle role="button" tabindex="0"
                         aria-expanded="<?= $subOpen ? 'true' : 'false' ?>">
                      <i class="fas <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
                      <span><?= htmlspecialchars($item['label']) ?></span>
                      <i class="fas fa-chevron-down nav-chevron <?= $subOpen ? 'open' : '' ?>" aria-hidden="true"></i>
                    </div>
                    <div class="myadzu-nested <?= $subOpen ? 'open' : '' ?>">
                      <ul>
                        <?php foreach ($item['submenu'] as $sub): ?>
                          <li data-menu-item data-menu-label="<?= htmlspecialchars(strtolower($sub['label'])) ?>">
                            <a class="myadzu-link <?= navActive($sub['key'], $currentPage) ?>"
                               href="<?= $sub['href'] ?>">
                              <i class="fas <?= htmlspecialchars($sub['icon']) ?>" aria-hidden="true"></i>
                              <span><?= htmlspecialchars($sub['label']) ?></span>
                            </a>
                          </li>
                        <?php endforeach; ?>
                      </ul>
                    </div>
                  <?php else: ?>
                    <a class="myadzu-link <?= navActive($item['key'], $currentPage) ?>"
                       href="<?= $item['href'] ?>">
                      <i class="fas <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
                      <span><?= htmlspecialchars($item['label']) ?></span>
                    </a>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </li>
      </ul>
    <?php endif; ?>

    <!-- 4. ADZU SOCIALS FOOTER LINKS -->
    <div class="myadzu-socials">
      <div class="myadzu-section-label">Adzu Socials</div>
      <ul>
        <?php foreach ($socialLinks as $social): ?>
          <li>
            <a class="myadzu-social-link"
               href="<?= htmlspecialchars($social['href']) ?>"
               target="_blank" rel="noopener noreferrer">
              <i class="fab <?= htmlspecialchars($social['icon']) ?>" aria-hidden="true"></i>
              <span><?= htmlspecialchars($social['label']) ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="myadzu-empty" data-menu-empty hidden>No menu items match your search.</div>
    </div>

  </nav>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
(function () {
  // Live-filter sidebar menu items. Scoped to this partial so it works
  // even if assets/js/script.js is cached or deferred.
  var input = document.querySelector('[data-menu-filter]');
  if (!input) return;
  var sidebar = document.getElementById('sidebar');
  var debounceTimer = null;
  function scheduleFilter() {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilter, 120);
  }
  function applyFilter() {
    var q = (input.value || '').trim().toLowerCase();
    var items = sidebar ? sidebar.querySelectorAll('[data-menu-item]') : [];
    var visible = 0;
    items.forEach(function (li) {
      var label = (li.getAttribute('data-menu-label') || '').toLowerCase();
      // Only leaf links participate; parent <li> wraps children so check
      // whether any descendant leaf matches before hiding.
      var leaves = li.querySelectorAll(':scope > a, :scope .myadzu-nested a');
      if (leaves.length === 0) leaves = [li];
      var match = !q;
      if (q) {
        match = label.indexOf(q) !== -1;
        if (!match) {
          leaves.forEach(function (a) {
            if ((a.textContent || '').toLowerCase().indexOf(q) !== -1) match = true;
          });
        }
      }
      // For parent wrappers with nested lists, keep visible if a child matches.
      if (!match && li.querySelector('.myadzu-nested')) {
        var childText = (li.textContent || '').toLowerCase();
        match = childText.indexOf(q) !== -1;
      }
      li.hidden = !match;
      li.style.display = match ? '' : 'none';
      if (match && li.querySelector(':scope > a')) visible++;
    });
    // Auto-expand collapsed groups while searching.
    if (sidebar) {
      sidebar.querySelectorAll('.myadzu-children, .myadzu-nested').forEach(function (box) {
        if (q) { box.classList.add('open'); }
      });
    }
    var empty = sidebar ? sidebar.querySelector('[data-menu-empty]') : null;
    if (empty) {
      var anyVisible = Array.prototype.some.call(items, function (li) { return !li.hidden; });
      empty.hidden = anyVisible;
      empty.style.display = anyVisible ? 'none' : '';
    }
  }
  input.addEventListener('input', scheduleFilter);
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { input.value = ''; applyFilter(); }
  });
})();
</script>
