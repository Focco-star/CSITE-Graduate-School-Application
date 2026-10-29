<?php /* Opens the PHP block for the shared sidebar layout partial. */ ?>
<?php
/**
 * MyADZU — Vertical Sidebar Navigation Panel
 * ------------------------------------------------------------------
 * Modular layout partial. Include from includes/layout/header.php via:
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
 *     (handled by assets/js/shared/app.js — no inline framework needed).
 *   - Search field live-filters menu items via [data-menu-filter].
 *   - Active states resolved with PHP (navActive() from config.php).
 */

// Sets the user role, defaulting to student when the page did not pass one.
$role        = $role ?? 'student';
// Sets the current page key, defaulting to an empty string.
$currentPage = $currentPage ?? '';
// Gets the user name from the session when the page did not pass one.
$userName    = $userName ?? ($_SESSION['user']['full_name'] ?? '');
// Stores whether the current user is a coordinator in $isCoordinator.
$isCoordinator = ($role === 'coordinator');

/**
 * Template updates the user has not looked at yet.
 *
 * A student only counts the updates of their own track; the coordinator
 * counts all of them. The badge is refreshed in the background by
 * template_sync.php, so a file the teacher just added to Google Drive
 * shows up here without a page reload.
 */
// Starts the unread template-update counter at zero.
$templateBadgeCount = 0;
// Only counts updates when the helper function and DB class are available.
if (function_exists('templateNotificationsUnreadCount') && class_exists('DB')) {
    // Starts a try block so a database failure cannot break the sidebar.
    try {
        // Chooses the count query based on the user role.
        $templateBadgeCount = $isCoordinator
            // Coordinators count every template update.
            ? templateNotificationsUnreadCount('coordinator')
            // Students count only updates for their own track.
            : templateNotificationsUnreadCount('student', currentStudentTrackCode());
    // Catches any error thrown by the count query.
    } catch (Throwable $e) {
        // Falls back to a zero badge count when the query fails.
        $templateBadgeCount = 0;
    // Closes the catch block.
    }
// Closes the check for the helper function and DB class.
}
// Forces the badge count to a whole number that is never negative.
$templateBadgeCount = max(0, (int) $templateBadgeCount);

// Starts the list of menu links shown to students.
$studentLinks = [
    // Adds the student Dashboard link with its icon and URL.
    ['key' => 'dashboard',   'label' => 'Dashboard',   'icon' => 'fa-gauge-high', 'href' => url('student/dashboard.php')],
    // Adds the student Application link.
    ['key' => 'application', 'label' => 'Application', 'icon' => 'fa-file-lines', 'href' => url('student/application.php')],
    // Adds the student Templates link with the unread badge count.
    ['key' => 'templates',   'label' => 'Templates',   'icon' => 'fa-file-arrow-down', 'href' => url('student/templates.php'), 'badge' => $templateBadgeCount],
// Closes the student links array.
];

// Starts the list of menu links shown to coordinators.
$coordinatorLinks = [
    // Adds the coordinator Dashboard link.
    ['key' => 'dashboard',    'label' => 'Dashboard',              'icon' => 'fa-gauge-high',     'href' => url('coordinator/dashboard.php')],
    // Starts the group definition for the Applications submenu.
    [
        // Sets the key used to detect this page.
        'key'     => 'applications',
        // Sets the label shown to the user.
        'label'   => 'Applications',
        // Sets the icon shown beside the label.
        'icon'    => 'fa-folder-open',
        // Starts the list of child links for this group.
        'submenu' => [
            // Adds the Applications child link.
            ['key' => 'applications',          'label' => 'Applications',         'icon' => 'fa-inbox',       'href' => url('coordinator/applications/manage.php')],
            // Adds the Archived Application child link.
            ['key' => 'archived_applications', 'label' => 'Archived Application', 'icon' => 'fa-box-archive', 'href' => url('coordinator/applications/archived.php')],
        // Closes the child link list.
        ],
    // Closes the Applications group definition.
    ],
    // Adds the Templates/Form link with the update badge.
    ['key' => 'templates', 'label' => 'Templates/Form',         'icon' => 'fa-file-pen',      'href' => url('coordinator/templates/manage.php'), 'badge' => $templateBadgeCount],
    // Starts the Academic Assessment Team group.
    [
        // Sets the key for the assessment team group.
        'key'     => 'academic_assessment_team',
        // Sets the visible label for the group.
        'label'   => 'Academic Assessment Team',
        // Sets the icon for the group.
        'icon'    => 'fa-user-group',
        // Starts the child links for the assessment team.
        'submenu' => [
            // Adds the Pool of Advisers link.
            ['key' => 'advisers', 'label' => 'Pool of Advisers', 'icon' => 'fa-user-tie',   'href' => url('coordinator/advisers/manage.php')],
            // Adds the Panel Members link.
            ['key' => 'panel',    'label' => 'Panel Members',    'icon' => 'fa-users-gear', 'href' => url('coordinator/panel/manage.php')],
            // Adds the Documenter Pool link.
            ['key' => 'documenters', 'label' => 'Documenter Pool', 'icon' => 'fa-file-signature', 'href' => url('coordinator/documenter/documenter.php')],
        // Closes the assessment team child list.
        ],
    // Closes the Academic Assessment Team group.
    ],
    // Adds the Presentation Schedules link.
    ['key' => 'schedule',  'label' => 'Presentation Schedules', 'icon' => 'fa-calendar-days', 'href' => url('coordinator/schedule/manage.php')],
// Closes the coordinator links array.
];


/** Collect every key inside a link tree (parent + children). */
// Defines a function that collects every menu key in a link tree.
$collectKeys = static function (array $links) use (&$collectKeys): array {
    // Starts an empty list of keys.
    $keys = [];
    // Loops over each item in the given links.
    foreach ($links as $item) {
        // Checks whether the item has a key.
        if (!empty($item['key'])) {
            // Stores the item key in the key list.
            $keys[] = $item['key'];
        // Closes the check for an item key.
        }
        // Checks whether this item has a submenu.
        if (!empty($item['submenu'])) {
            // Recursively merges the keys found inside the submenu.
            $keys = array_merge($keys, $collectKeys($item['submenu']));
        // Closes the submenu check.
        }
    // Closes the loop over the links.
    }
    // Returns the collected list of keys.
    return $keys;
// Closes the anonymous key-collecting function.
};

// Collects all keys used by the student menu.
$studentKeys    = $collectKeys($studentLinks);
// Collects all keys used by the coordinator menu.
$coordinatorKeys = $collectKeys($coordinatorLinks);

// True when the current page belongs to the student menu.
$csiteStudentOpen = in_array($currentPage, $studentKeys, true);
// True when the current page belongs to the coordinator menu.
$csiteCoordOpen   = in_array($currentPage, $coordinatorKeys, true);

// Defines a helper that reports whether the current page is inside a submenu.
$isSubmenuChildActive = static function (array $submenu, string $current): bool {
    // Loops over each submenu entry.
    foreach ($submenu as $sub) {
        // Checks whether this entry matches the current page key.
        if (($sub['key'] ?? '') === $current) {
            // Returns true as soon as a matching child is found.
            return true;
        // Closes the match check.
        }
        // Checks whether the child has its own nested submenu.
        if (!empty($sub['submenu'])) {
            // Loops over the items one level deeper.
            foreach ($sub['submenu'] as $nested) {
                // Checks whether a nested item matches the current page.
                if (($nested['key'] ?? '') === $current) {
                    // Returns true for a matching nested item.
                    return true;
                // Closes the nested match check.
                }
            // Closes the nested loop.
            }
        // Closes the nested submenu check.
        }
    // Closes the top-level submenu loop.
    }
    // Returns false when no child matched the current page.
    return false;
// Closes the submenu helper function.
};

// Shows the student section only for non-coordinator users.
$showStudentSection    = !$isCoordinator;
// Shows the coordinator section only for coordinators.
$showCoordinatorSection = $isCoordinator;
// Closes the PHP block so the markup below can be printed.
?>

<?php /* Starts the first style block that holds the sidebar base styles. */ ?>
<style id="myadzu-sidebar-styles">
<?php /*   CSS comment naming the base colour palette. */ ?>
  /* ===== MyADZU Sidebar — Dark Slate #232b35 / Brand Blue #0f2a4a ===== */
<?php /*   Starts the rule for the main sidebar container. */ ?>
  #sidebar.myadzu-sidebar {
<?php /*     Fills the sidebar with dark slate grey. */ ?>
    background: #232b35;
<?php /*     Draws a faint line between the sidebar and the page. */ ?>
    border-right: 1px solid rgba(255,255,255,.07);
<?php /*     Sets the default sidebar text colour. */ ?>
    color: #e8edf2;
<?php /*     Sets the sidebar width, using a 260px fallback. */ ?>
    width: var(--sidebar-width, 260px);
<?php /*   Closes the main sidebar rule. */ ?>
  }
<?php /*   Starts the rule for the brand bar at the top. */ ?>
  #sidebar.myadzu-sidebar .myadzu-brandbar {
<?php /*     Gives the brand bar the AdZU dark blue background. */ ?>
    background: #0f2a4a;
<?php /*     Makes the brand text white. */ ?>
    color: #ffffff;
<?php /*     Centres the brand text. */ ?>
    text-align: center;
<?php /*     Uses the heading font for the brand. */ ?>
    font-family: var(--font-heading, 'Montserrat', sans-serif);
<?php /*     Makes the brand text heavy and bold. */ ?>
    font-weight: 800;
<?php /*     Sets the brand text size to 1.15rem. */ ?>
    font-size: 1.15rem;
<?php /*     Tightens the spacing between brand letters. */ ?>
    letter-spacing: .01em;
<?php /*     Adds space above and below the brand text. */ ?>
    padding: .85rem 1rem;
<?php /*     Sets a compact line height for the brand. */ ?>
    line-height: 1.2;
<?php /*   Closes the brand bar rule. */ ?>
  }
<?php /*   Starts the rule for the profile area with the seal. */ ?>
  #sidebar.myadzu-sidebar .myadzu-profile {
<?php /*     Adds inner spacing around the profile content. */ ?>
    padding: 1.1rem 1rem .9rem;
<?php /*     Centres the profile content. */ ?>
    text-align: center;
<?php /*     Adds a divider under the profile area. */ ?>
    border-bottom: 1px solid rgba(255,255,255,.07);
<?php /*     Keeps the profile background dark slate grey. */ ?>
    background: #232b35;
<?php /*   Closes the profile area rule. */ ?>
  }
<?php /*   Starts the rule for the avatar box that holds the seal. */ ?>
  #sidebar.myadzu-sidebar .myadzu-avatar {
<?php /*     Fixes the avatar box at 96 by 96 pixels. */ ?>
    width: 96px; height: 96px;
<?php /*     Centres the avatar box and adds a little space below. */ ?>
    margin: 0 auto .35rem;
<?php /*     Removes rounded corners from the avatar box. */ ?>
    border-radius: 0;
<?php /*     Removes the border from the avatar box. */ ?>
    border: none;
<?php /*     Lets the seal image be positioned inside the box. */ ?>
    position: relative;
<?php /*     Allows the glow to show outside the box. */ ?>
    overflow: visible;
<?php /*     Centres the seal contents with flexbox. */ ?>
    display: flex; align-items: center; justify-content: center;
<?php /*     Keeps the avatar box background transparent. */ ?>
    background: transparent;
<?php /*     Adds a soft golden glow around the seal. */ ?>
    filter: drop-shadow(0 0 5px rgba(217,181,69,.55));
<?php /*   Closes the avatar rule. */ ?>
  }
<?php /*   Starts the rule for the seal image itself. */ ?>
  #sidebar.myadzu-sidebar .myadzu-avatar img.myadzu-seal {
<?php /*     Pins the seal image to the edges of its box. */ ?>
    position: absolute; inset: 0;
<?php /*     Makes the seal image fill the whole box. */ ?>
    width: 100%; height: 100%;
<?php /*     Crops the seal image so it keeps its shape. */ ?>
    object-fit: cover;
<?php /*     Shows the seal image fully. */ ?>
    opacity: 1;
<?php /*     Stops the seal image from catching mouse clicks. */ ?>
    pointer-events: none;
<?php /*   Closes the seal image rule. */ ?>
  }
<?php /*   Starts the rule for the user name text. */ ?>
  #sidebar.myadzu-sidebar .myadzu-username {
<?php /*     Sets a small, light grey user name. */ ?>
    font-size: .8rem; color: #cbd5e1;
<?php /*     Keeps the name on one line and cuts it off with dots. */ ?>
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
<?php /*     Adds space below the user name. */ ?>
    margin-bottom: .7rem;
<?php /*   Closes the user name rule. */ ?>
  }
<?php /*   Makes the search box a positioning context for its icon. */ ?>
  #sidebar.myadzu-sidebar .myadzu-search { position: relative; }
<?php /*   Starts the rule for the search input box. */ ?>
  #sidebar.myadzu-sidebar .myadzu-search input {
<?php /*     Makes the search box fill the available width. */ ?>
    width: 100%;
<?php /*     Uses a darker background for the search box. */ ?>
    background: #1a222c;
<?php /*     Adds a subtle border to the search box. */ ?>
    border: 1px solid rgba(255,255,255,.12);
<?php /*     Rounds the search box corners slightly. */ ?>
    border-radius: 6px;
<?php /*     Sets the search text colour to light grey. */ ?>
    color: #e8edf2;
<?php /*     Sets a small font for the search text. */ ?>
    font-size: .82rem;
<?php /*     Adds padding and leaves room for the search icon. */ ?>
    padding: .55rem 2.2rem .55rem .8rem;
<?php /*     Makes the search box use the page font. */ ?>
    font-family: inherit;
<?php /*   Closes the search input rule. */ ?>
  }
<?php /*   Styles the placeholder text in muted grey. */ ?>
  #sidebar.myadzu-sidebar .myadzu-search input::placeholder { color: #8a94a6; }
<?php /*   Starts the focus style for the search box. */ ?>
  #sidebar.myadzu-sidebar .myadzu-search input:focus {
<?php /*     Removes the default outline and shows a gold border. */ ?>
    outline: none; border-color: #FFB82B;
<?php /*     Adds a soft gold glow when the search box is focused. */ ?>
    box-shadow: 0 0 0 2px rgba(255,184,43,.2);
<?php /*   Closes the search focus rule. */ ?>
  }
<?php /*   Starts the rule for the search icon. */ ?>
  #sidebar.myadzu-sidebar .myadzu-search i {
<?php /*     Positions the icon at the right side of the search box. */ ?>
    position: absolute; right: .8rem; top: 50%;
<?php /*     Centres the icon vertically. */ ?>
    transform: translateY(-50%);
<?php /*     Colours and sizes the icon and lets clicks pass through. */ ?>
    color: #8a94a6; font-size: .85rem; pointer-events: none;
<?php /*   Closes the search icon rule. */ ?>
  }
<?php /*   Makes the menu area scrollable and fills the sidebar height. */ ?>
  #sidebar.myadzu-sidebar .myadzu-nav { flex: 1; overflow-y: auto; padding: .4rem 0 1rem; }
<?php /*   Starts the rule for small section labels. */ ?>
  #sidebar.myadzu-sidebar .myadzu-section-label {
<?php /*     Sets a tiny, bold label font. */ ?>
    font-size: .68rem; font-weight: 700;
<?php /*     Spreads the label letters apart. */ ?>
    letter-spacing: .06em;
<?php /*     Uses muted grey for the label text. */ ?>
    color: #8a94a6;
<?php /*     Adds spacing around the label. */ ?>
    padding: 1rem 1.25rem .45rem;
<?php /*     Shows the label in uppercase. */ ?>
    text-transform: uppercase;
<?php /*   Closes the section label rule. */ ?>
  }
<?php /*   Removes bullets and default spacing from sidebar lists. */ ?>
  #sidebar.myadzu-sidebar .myadzu-nav ul { list-style: none; margin: 0; padding: 0; }
<?php /*   CSS comment marking the menu toggle styles. */ ?>
  /* Root CSITE toggle */
<?php /*   Starts the rule for the CSITE menu root row. */ ?>
  #sidebar.myadzu-sidebar .myadzu-root {
<?php /*     Lays the root row out as a spaced flex row. */ ?>
    display: flex; align-items: center; justify-content: space-between;
<?php /*     Adds inner padding to the root row. */ ?>
    padding: .7rem 1.25rem;
<?php /*     Makes the root text white, bold and slightly larger. */ ?>
    color: #ffffff; font-weight: 700; font-size: .9rem;
<?php /*     Shows a pointer cursor and stops text selection. */ ?>
    cursor: pointer; user-select: none;
<?php /*     Reserves a left border that turns gold when active. */ ?>
    border-left: 3px solid transparent;
<?php /*     Animates background changes on hover. */ ?>
    transition: background .2s ease;
<?php /*   Closes the root row rule. */ ?>
  }
<?php /*   Lightens the root row background on hover. */ ?>
  #sidebar.myadzu-sidebar .myadzu-root:hover { background: #2e3844; }
<?php /*   Highlights an open root row with a dark background and gold border. */ ?>
  #sidebar.myadzu-sidebar .myadzu-root.is-open { background: #1c242e; border-left-color: #FFB82B; }
<?php /*   Lays out the icon and label inside the root row. */ ?>
  #sidebar.myadzu-sidebar .myadzu-root-left { display: flex; align-items: center; gap: .7rem; }
<?php /*   Gives the root icon a gold colour and fixed width. */ ?>
  #sidebar.myadzu-sidebar .myadzu-root-left > i { color: #FFB82B; width: 20px; text-align: center; }
<?php /*   Styles the small chevron beside the root label. */ ?>
  #sidebar.myadzu-sidebar .myadzu-root .nav-chevron { color: #8a94a6; font-size: .75rem; transition: transform .15s ease; }
<?php /*   Rotates the chevron when the group is open. */ ?>
  #sidebar.myadzu-sidebar .myadzu-root .nav-chevron.open { transform: rotate(180deg); }
<?php /*   CSS comment noting that child links show and hide instantly. */ ?>
  /* Child links — instant show/hide (height animations stutter on low-end machines) */
<?php /*   Hides child menus until their group is opened. */ ?>
  #sidebar.myadzu-sidebar .myadzu-children { display: none; contain: layout style; }
<?php /*   Shows child menus when they have the open class. */ ?>
  #sidebar.myadzu-sidebar .myadzu-children.open { display: block; }
<?php /*   Allows the child list to shrink inside its container. */ ?>
  #sidebar.myadzu-sidebar .myadzu-children > ul { min-height: 0; }
<?php /*   Starts a shared rule for menu links and submenu toggles. */ ?>
  #sidebar.myadzu-sidebar .myadzu-link,
<?php /*   Continues the selector for submenu toggles. */ ?>
  #sidebar.myadzu-sidebar .myadzu-sub-toggle {
<?php /*     Lays out each link as a row with an icon and label. */ ?>
    display: flex; align-items: center; gap: .65rem;
<?php /*     Adds left padding so child links look indented. */ ?>
    padding: .55rem 1.25rem .55rem 2.6rem;
<?php /*     Sets the link text colour, size and weight. */ ?>
    color: #cbd5e1; font-size: .85rem; font-weight: 500;
<?php /*     Reserves a left border for the active marker. */ ?>
    border-left: 3px solid transparent;
<?php /*     Removes the underline from links. */ ?>
    text-decoration: none;
<?php /*     Animates background and text colour changes. */ ?>
    transition: background .18s ease, color .18s ease;
<?php /*   Closes the shared link rule. */ ?>
  }
<?php /*   Makes submenu toggles fill the width and act like buttons. */ ?>
  #sidebar.myadzu-sidebar .myadzu-sub-toggle { cursor: pointer; user-select: none; width: 100%; box-sizing: border-box; }
<?php /*   Starts a shared rule for link icons and toggle icons. */ ?>
  #sidebar.myadzu-sidebar .myadzu-link i,
<?php /*   Sizes and colours the small icon beside each label. */ ?>
  #sidebar.myadzu-sidebar .myadzu-sub-toggle > i:first-child { width: 18px; text-align: center; font-size: .8rem; color: #8fa0b5; }
<?php /*   Starts the hover rule for links and toggles. */ ?>
  #sidebar.myadzu-sidebar .myadzu-link:hover,
<?php /*   Lightens the background and text on hover. */ ?>
  #sidebar.myadzu-sidebar .myadzu-sub-toggle:hover { background: #2e3844; color: #ffffff; }
<?php /*   Highlights the link for the page the user is on. */ ?>
  #sidebar.myadzu-sidebar .myadzu-link.active { background: #1a222c; color: #ffffff; border-left-color: #FFB82B; font-weight: 700; }
<?php /*   Turns the active link icon gold. */ ?>
  #sidebar.myadzu-sidebar .myadzu-link.active i { color: #FFB82B; }
<?php /*   Highlights an open submenu toggle. */ ?>
  #sidebar.myadzu-sidebar .myadzu-sub-toggle.is-open { color: #ffffff; background: #1c242e; }
<?php /*   Pushes the submenu chevron to the right edge. */ ?>
  #sidebar.myadzu-sidebar .myadzu-sub-toggle .nav-chevron { margin-left: auto; font-size: .65rem; color: #8a94a6; }
<?php /*   Hides nested submenus until their parent is opened. */ ?>
  #sidebar.myadzu-sidebar .myadzu-nested { display: none; contain: layout style; }
<?php /*   Shows a nested submenu when it has the open class. */ ?>
  #sidebar.myadzu-sidebar .myadzu-nested.open { display: block; }
<?php /*   Allows the nested list to shrink inside its container. */ ?>
  #sidebar.myadzu-sidebar .myadzu-nested > ul { min-height: 0; }
<?php /*   Indents links inside a nested submenu a little more. */ ?>
  #sidebar.myadzu-sidebar .myadzu-nested .myadzu-link { padding-left: 3.8rem; font-size: .82rem; }
<?php /*   CSS comment marking the social link styles. */ ?>
  /* Socials */
<?php /*   Pads the socials area and adds a divider above it. */ ?>
  #sidebar.myadzu-sidebar .myadzu-socials { padding-bottom: 1rem; border-top: 1px solid rgba(255,255,255,.07); }
<?php /*   Starts the rule for a social media link. */ ?>
  #sidebar.myadzu-sidebar .myadzu-social-link {
<?php /*     Lays out each social link as a spaced row. */ ?>
    display: flex; align-items: center; gap: .7rem;
<?php /*     Adds spacing inside each social link. */ ?>
    padding: .5rem 1.25rem;
<?php /*     Sets the link colour and size and removes underlines. */ ?>
    color: #cbd5e1; font-size: .85rem; text-decoration: none;
<?php /*     Animates the social link colours on hover. */ ?>
    transition: background .18s ease, color .18s ease;
<?php /*   Closes the social link rule. */ ?>
  }
<?php /*   Sizes and colours the social icon. */ ?>
  #sidebar.myadzu-sidebar .myadzu-social-link i { width: 20px; text-align: center; color: #FFB82B; font-size: .9rem; }
<?php /*   Lightens the social link on hover. */ ?>
  #sidebar.myadzu-sidebar .myadzu-social-link:hover { background: #2e3844; color: #ffffff; }
<?php /*   Styles the empty-state text in the menu. */ ?>
  #sidebar.myadzu-sidebar .myadzu-empty { padding: .4rem 1.25rem; font-size: .78rem; color: #8a94a6; font-style: italic; }
<?php /*   CSS comment explaining the template update badge. */ ?>
  /* Template update badge on the Templates menu item */
<?php /*   Starts the rule for the red template update badge. */ ?>
  #sidebar.myadzu-sidebar .myadzu-badge {
<?php /*     Pushes the badge to the right end of the link. */ ?>
    margin-left: auto;
<?php /*     Stops the badge from stretching. */ ?>
    flex: 0 0 auto;
<?php /*     Sets a minimum badge width. */ ?>
    min-width: 1.2rem;
<?php /*     Sets the badge height. */ ?>
    height: 1.2rem;
<?php /*     Adds a little horizontal padding inside the badge. */ ?>
    padding: 0 .32rem;
<?php /*     Makes the badge fully rounded like a pill. */ ?>
    border-radius: 999px;
<?php /*     Gives the badge a red background. */ ?>
    background: #e02424;
<?php /*     Sets the badge text to white. */ ?>
    color: #ffffff;
<?php /*     Uses a very small font for the count. */ ?>
    font-size: .64rem;
<?php /*     Makes the count text extra bold. */ ?>
    font-weight: 800;
<?php /*     Centres the count text vertically. */ ?>
    line-height: 1.2rem;
<?php /*     Centres the count text horizontally. */ ?>
    text-align: center;
<?php /*     Adds a dark ring so the badge stands out from the link. */ ?>
    box-shadow: 0 0 0 2px #232b35;
<?php /*   Closes the badge rule. */ ?>
  }
<?php /*   Hides the badge when the hidden attribute is set. */ ?>
  #sidebar.myadzu-sidebar .myadzu-badge[hidden] { display: none; }
<?php /*   Adjusts the badge ring colour on an active link. */ ?>
  #sidebar.myadzu-sidebar .myadzu-link.active .myadzu-badge { box-shadow: 0 0 0 2px #1a222c; }
<?php /*   Starts the scrollbar styling for the sidebar. */ ?>
  #sidebar.myadzu-sidebar::-webkit-scrollbar,
<?php /*   Sets a thin scrollbar for the scrolling menu area. */ ?>
  #sidebar.myadzu-sidebar .myadzu-nav::-webkit-scrollbar { width: 8px; }
<?php /*   Styles the scrollbar thumb in translucent white. */ ?>
  #sidebar.myadzu-sidebar .myadzu-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 4px; }
<?php /*   CSS comment noting that animations are disabled for speed. */ ?>
  /* Perf: zero animation inside sidebar so toggles apply instantly (no tween jank) */
<?php /*   Removes all transitions and animations inside the sidebar. */ ?>
  #sidebar.myadzu-sidebar, #sidebar.myadzu-sidebar * { transition: none !important; animation: none !important; }
<?php /* Closes the first style block. */ ?>
</style>

<?php /* Starts the second style block that refines the sidebar look. */ ?>
<style id="myadzu-sidebar-theme">
<?php /*   CSS comment naming the AdZU slate and blue theme. */ ?>
  /* ===== MyADZU navigation — AdZU slate + blue, centred seal ===== */
<?php /*   Starts the themed rule for the sidebar container. */ ?>
  #sidebar.myadzu-sidebar {
<?php /*     Starts a layered background for the sidebar. */ ?>
    background:
<?php /*       Adds a soft blue glow at the top of the sidebar. */ ?>
      radial-gradient(120% 55% at 50% 0%, rgba(15, 42, 74, 0.55), transparent 62%),
<?php /*       Adds a vertical slate gradient down the sidebar. */ ?>
      linear-gradient(180deg, #27313c 0%, #232b35 42%, #1c242e 100%);
<?php /*     Draws a faint divider on the right edge. */ ?>
    border-right: 1px solid rgba(255, 255, 255, 0.07);
<?php /*     Sets the sidebar text colour. */ ?>
    color: #e8edf2;
<?php /*     Adds a subtle shadow to the right of the sidebar. */ ?>
    box-shadow: 8px 0 30px -22px rgba(2, 6, 15, 0.85);
<?php /*   Closes the themed sidebar rule. */ ?>
  }

<?php /*   CSS comment marking the brand bar styles. */ ?>
  /* Brand bar */
<?php /*   Starts the themed rule for the brand header. */ ?>
  #sidebar.myadzu-sidebar .myadzu-brand {
<?php /*     Lays the brand contents out with flexbox. */ ?>
    display: flex;
<?php /*     Stacks the brand text vertically. */ ?>
    flex-direction: column;
<?php /*     Centres the brand items across the bar. */ ?>
    align-items: center;
<?php /*     Centres the brand content vertically. */ ?>
    justify-content: center;
<?php /*     Adds a small gap between brand lines. */ ?>
    gap: 0.1rem;
<?php /*     Matches the brand bar height to the top bar. */ ?>
    height: var(--topbar-height, 64px);
<?php /*     Adds compact padding inside the brand bar. */ ?>
    padding: 0.35rem 1rem;
<?php /*     Gives the brand bar the AdZU dark blue background. */ ?>
    background: #0f2a4a;
<?php /*     Adds a faint divider under the brand bar. */ ?>
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
<?php /*     Centres the brand text. */ ?>
    text-align: center;
<?php /*   Closes the brand bar rule. */ ?>
  }
<?php /*   Starts the rule for the bold MyADZU name. */ ?>
  #sidebar.myadzu-sidebar .myadzu-brand strong {
<?php /*     Uses the heading font for the brand name. */ ?>
    font-family: var(--font-heading, 'Montserrat', sans-serif);
<?php /*     Sets the brand name size. */ ?>
    font-size: 1.08rem;
<?php /*     Makes the brand name extra bold. */ ?>
    font-weight: 800;
<?php /*     Spaces the brand letters slightly. */ ?>
    letter-spacing: 0.02em;
<?php /*     Makes the brand name white. */ ?>
    color: #ffffff;
<?php /*   Closes the brand name rule. */ ?>
  }
<?php /*   Starts the rule for the school name under the brand. */ ?>
  #sidebar.myadzu-sidebar .myadzu-brand span {
<?php /*     Sets a small font for the school name. */ ?>
    font-size: 0.6rem;
<?php /*     Makes the school name semi-bold. */ ?>
    font-weight: 600;
<?php /*     Spreads the school name letters apart. */ ?>
    letter-spacing: 0.1em;
<?php /*     Shows the school name in uppercase. */ ?>
    text-transform: uppercase;
<?php /*     Fades the school name to soft white. */ ?>
    color: rgba(255, 255, 255, 0.6);
<?php /*   Closes the school name rule. */ ?>
  }

<?php /*   CSS comment marking the seal and user name styles. */ ?>
  /* Centred seal and name */
<?php /*   Starts the themed rule for the profile area. */ ?>
  #sidebar.myadzu-sidebar .myadzu-profile {
<?php /*     Adds padding around the profile content. */ ?>
    padding: 1rem 0.8rem 0.95rem;
<?php /*     Keeps the profile background transparent. */ ?>
    background: transparent;
<?php /*     Adds a divider under the profile area. */ ?>
    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
<?php /*     Centres the profile content. */ ?>
    text-align: center;
<?php /*   Closes the profile area rule. */ ?>
  }
<?php /*   Starts the themed rule for the round avatar box. */ ?>
  #sidebar.myadzu-sidebar .myadzu-avatar {
<?php /*     Sets the avatar box width to 84 pixels. */ ?>
    width: 84px;
<?php /*     Sets the avatar box height to 84 pixels. */ ?>
    height: 84px;
<?php /*     Centres the avatar and adds space below it. */ ?>
    margin: 0 auto 0.55rem;
<?php /*     Uses flex layout inside the avatar box. */ ?>
    display: flex;
<?php /*     Centres the avatar content vertically. */ ?>
    align-items: center;
<?php /*     Centres the avatar content horizontally. */ ?>
    justify-content: center;
<?php /*     Makes the avatar box a circle. */ ?>
    border-radius: 50%;
<?php /*     Adds a soft gold glow inside the circle. */ ?>
    background: radial-gradient(circle at 50% 35%, rgba(255, 184, 43, 0.16), rgba(0, 0, 0, 0));
<?php /*     Adds a soft shadow around the avatar. */ ?>
    filter: drop-shadow(0 4px 12px rgba(255, 184, 43, 0.28));
<?php /*   Closes the avatar rule. */ ?>
  }
<?php /*   Starts the themed rule for the seal image. */ ?>
  #sidebar.myadzu-sidebar .myadzu-avatar img.myadzu-seal {
<?php /*     Makes the seal image fill the circle width. */ ?>
    width: 100%;
<?php /*     Makes the seal image fill the circle height. */ ?>
    height: 100%;
<?php /*     Fits the whole seal inside the circle without cropping. */ ?>
    object-fit: contain;
<?php /*     Displays the image as a block element. */ ?>
    display: block;
<?php /*   Closes the seal image rule. */ ?>
  }
<?php /*   Starts the themed rule for the user name. */ ?>
  #sidebar.myadzu-sidebar .myadzu-username {
<?php /*     Adds a small margin around the user name. */ ?>
    margin: 0 0.3rem 0.7rem;
<?php /*     Sets the user name font size. */ ?>
    font-size: 0.82rem;
<?php /*     Makes the user name semi-bold. */ ?>
    font-weight: 600;
<?php /*     Sets the user name colour to light grey. */ ?>
    color: #e8edf2;
<?php /*     Keeps the name on one line. */ ?>
    white-space: nowrap;
<?php /*     Hides any text that overflows. */ ?>
    overflow: hidden;
<?php /*     Cuts off long names with an ellipsis. */ ?>
    text-overflow: ellipsis;
<?php /*   Closes the user name rule. */ ?>
  }

<?php /*   CSS comment marking the search box styles. */ ?>
  /* Search */
<?php /*   Makes the search box a positioning context for its icon. */ ?>
  #sidebar.myadzu-sidebar .myadzu-search { position: relative; }
<?php /*   Starts the themed rule for the search input. */ ?>
  #sidebar.myadzu-sidebar .myadzu-search input {
<?php /*     Makes the search input fill the available width. */ ?>
    width: 100%;
<?php /*     Adds padding and space on the left for the icon. */ ?>
    padding: 0.55rem 0.8rem 0.55rem 2.05rem;
<?php /*     Uses a darker background for the search input. */ ?>
    background: #1a222c;
<?php /*     Adds a faint border to the search input. */ ?>
    border: 1px solid rgba(255, 255, 255, 0.12);
<?php /*     Rounds the search input corners. */ ?>
    border-radius: 10px;
<?php /*     Sets the search text colour. */ ?>
    color: #e8edf2;
<?php /*     Sets a small font for the search text. */ ?>
    font-size: 0.8rem;
<?php /*     Makes the search input use the page font. */ ?>
    font-family: inherit;
<?php /*     Animates the search box colours on focus. */ ?>
    transition: background-color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease !important;
<?php /*   Closes the search input rule. */ ?>
  }
<?php /*   Styles the search placeholder in muted grey. */ ?>
  #sidebar.myadzu-sidebar .myadzu-search input::placeholder { color: #8a94a6; }
<?php /*   Starts the focus style for the search input. */ ?>
  #sidebar.myadzu-sidebar .myadzu-search input:focus {
<?php /*     Removes the default browser outline. */ ?>
    outline: none;
<?php /*     Slightly lightens the background on focus. */ ?>
    background: #1e2731;
<?php /*     Turns the border gold on focus. */ ?>
    border-color: #FFB82B;
<?php /*     Adds a gold glow around the focused search box. */ ?>
    box-shadow: 0 0 0 3px rgba(255, 184, 43, 0.18);
<?php /*   Closes the search focus rule. */ ?>
  }
<?php /*   Starts the rule that moves the search icon to the left. */ ?>
  #sidebar.myadzu-sidebar .myadzu-search i {
<?php /*     Resets the icon right position. */ ?>
    right: auto;
<?php /*     Places the icon near the left edge. */ ?>
    left: 0.8rem;
<?php /*     Colours the icon muted grey. */ ?>
    color: #8a94a6;
<?php /*     Sizes the icon a little smaller. */ ?>
    font-size: 0.78rem;
<?php /*   Closes the search icon rule. */ ?>
  }

<?php /*   CSS comment marking the menu styles. */ ?>
  /* Menu */
<?php /*   Adds padding inside the scrollable menu area. */ ?>
  #sidebar.myadzu-sidebar .myadzu-nav { padding: 0.45rem 0.55rem 1.1rem; }
<?php /*   Starts the themed rule for section labels. */ ?>
  #sidebar.myadzu-sidebar .myadzu-section-label {
<?php /*     Lays the label out as a flex row. */ ?>
    display: flex;
<?php /*     Centres the label items vertically. */ ?>
    align-items: center;
<?php /*     Adds a small gap before the label text. */ ?>
    gap: 0.45rem;
<?php /*     Adds spacing around the section label. */ ?>
    padding: 0.95rem 0.6rem 0.4rem;
<?php /*     Sets a very small label font. */ ?>
    font-size: 0.62rem;
<?php /*     Makes the label bold. */ ?>
    font-weight: 700;
<?php /*     Spreads the label letters wide apart. */ ?>
    letter-spacing: 0.12em;
<?php /*     Shows the label in uppercase. */ ?>
    text-transform: uppercase;
<?php /*     Colours the label muted grey. */ ?>
    color: #8a94a6;
<?php /*   Closes the section label rule. */ ?>
  }
<?php /*   Starts the gold dot before each section label. */ ?>
  #sidebar.myadzu-sidebar .myadzu-section-label::before {
<?php /*     Creates an empty box for the dot. */ ?>
    content: '';
<?php /*     Sets the dot width to 5 pixels. */ ?>
    width: 5px;
<?php /*     Sets the dot height to 5 pixels. */ ?>
    height: 5px;
<?php /*     Makes the dot round. */ ?>
    border-radius: 50%;
<?php /*     Gives the dot a gold colour. */ ?>
    background: #FFB82B;
<?php /*     Softens the dot slightly. */ ?>
    opacity: 0.85;
<?php /*   Closes the section label dot rule. */ ?>
  }
<?php /*   Starts the themed rule for the CSITE root row. */ ?>
  #sidebar.myadzu-sidebar .myadzu-root {
<?php /*     Adds a small outer margin to the root row. */ ?>
    margin: 0.2rem 0.1rem 0.35rem;
<?php /*     Adds compact padding inside the root row. */ ?>
    padding: 0.6rem 0.65rem;
<?php /*     Gives the root row a faint white background. */ ?>
    background: rgba(255, 255, 255, 0.03);
<?php /*     Adds a faint border around the root row. */ ?>
    border: 1px solid rgba(255, 255, 255, 0.06);
<?php /*     Rounds the root row corners. */ ?>
    border-radius: 10px;
<?php /*     Sets a small font for the root label. */ ?>
    font-size: 0.78rem;
<?php /*     Makes the root label bold. */ ?>
    font-weight: 700;
<?php /*     Spaces the root letters slightly. */ ?>
    letter-spacing: 0.06em;
<?php /*     Shows the root label in uppercase. */ ?>
    text-transform: uppercase;
<?php /*     Makes the root label white. */ ?>
    color: #ffffff;
<?php /*     Animates the root colours on hover. */ ?>
    transition: background-color 0.18s ease, border-color 0.18s ease !important;
<?php /*   Closes the root row rule. */ ?>
  }
<?php /*   Lightens the root row background on hover. */ ?>
  #sidebar.myadzu-sidebar .myadzu-root:hover { background: #2e3844; }
<?php /*   Starts the style for an open root row. */ ?>
  #sidebar.myadzu-sidebar .myadzu-root.is-open {
<?php /*     Darkens the open root row background. */ ?>
    background: #1c242e;
<?php /*     Turns the open root row border gold. */ ?>
    border-color: rgba(255, 184, 43, 0.32);
<?php /*   Closes the open root row rule. */ ?>
  }
<?php /*   Adds a small gap between root icon and text. */ ?>
  #sidebar.myadzu-sidebar .myadzu-root-left { gap: 0.55rem; }
<?php /*   Sizes and colours the root icon gold. */ ?>
  #sidebar.myadzu-sidebar .myadzu-root-left > i { width: 18px; font-size: 0.85rem; color: #FFB82B; }
<?php /*   Colours the root chevron muted grey. */ ?>
  #sidebar.myadzu-sidebar .myadzu-root .nav-chevron { color: #8a94a6; }
<?php /*   Starts the themed rule shared by links and toggles. */ ?>
  #sidebar.myadzu-sidebar .myadzu-link,
<?php /*   Continues the selector for submenu toggles. */ ?>
  #sidebar.myadzu-sidebar .myadzu-sub-toggle {
<?php /*     Lets the active marker be positioned absolutely. */ ?>
    position: relative;
<?php /*     Adds a small margin around each link. */ ?>
    margin: 0.1rem 0.1rem;
<?php /*     Adds padding inside each link. */ ?>
    padding: 0.56rem 0.65rem;
<?php /*     Removes the default border from toggles. */ ?>
    border: 0;
<?php /*     Rounds each link corners. */ ?>
    border-radius: 8px;
<?php /*     Sets the link text colour. */ ?>
    color: #cbd5e1;
<?php /*     Sets the link font size. */ ?>
    font-size: 0.84rem;
<?php /*     Sets the link text weight. */ ?>
    font-weight: 500;
<?php /*     Animates link colours on hover. */ ?>
    transition: background-color 0.18s ease, color 0.18s ease !important;
<?php /*   Closes the shared link rule. */ ?>
  }
<?php /*   Starts the themed rule for link icons. */ ?>
  #sidebar.myadzu-sidebar .myadzu-link i,
<?php /*   Continues the selector for toggle icons. */ ?>
  #sidebar.myadzu-sidebar .myadzu-sub-toggle > i:first-child {
<?php /*     Sets a fixed icon width. */ ?>
    width: 18px;
<?php /*     Sizes the icon a little smaller. */ ?>
    font-size: 0.82rem;
<?php /*     Colours the icon a soft blue-grey. */ ?>
    color: #8fa0b5;
<?php /*   Closes the icon rule. */ ?>
  }
<?php /*   Starts the hover style for links and toggles. */ ?>
  #sidebar.myadzu-sidebar .myadzu-link:hover,
<?php /*   Continues the selector for submenu toggles. */ ?>
  #sidebar.myadzu-sidebar .myadzu-sub-toggle:hover {
<?php /*     Lightens the link background on hover. */ ?>
    background: #2e3844;
<?php /*     Makes the link text white on hover. */ ?>
    color: #ffffff;
<?php /*   Closes the hover rule. */ ?>
  }
<?php /*   Starts the hover style for link icons. */ ?>
  #sidebar.myadzu-sidebar .myadzu-link:hover i,
<?php /*   Makes the icons white on hover. */ ?>
  #sidebar.myadzu-sidebar .myadzu-sub-toggle:hover > i:first-child { color: #ffffff; }
<?php /*   Starts the style for the active link. */ ?>
  #sidebar.myadzu-sidebar .myadzu-link.active {
<?php /*     Darkens the background of the active link. */ ?>
    background: #1a222c;
<?php /*     Makes the active link text white. */ ?>
    color: #ffffff;
<?php /*     Makes the active link text bold. */ ?>
    font-weight: 700;
<?php /*     Adds a faint inner outline to the active link. */ ?>
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.05);
<?php /*   Closes the active link rule. */ ?>
  }
<?php /*   Starts the gold bar marker on the active link. */ ?>
  #sidebar.myadzu-sidebar .myadzu-link.active::before {
<?php /*     Creates an empty box for the marker. */ ?>
    content: '';
<?php /*     Positions the marker inside the link. */ ?>
    position: absolute;
<?php /*     Places the marker near the left edge. */ ?>
    left: 0.15rem;
<?php /*     Starts the marker a little below the top. */ ?>
    top: 0.5rem;
<?php /*     Ends the marker a little above the bottom. */ ?>
    bottom: 0.5rem;
<?php /*     Sets the marker width to 3 pixels. */ ?>
    width: 3px;
<?php /*     Rounds the marker ends. */ ?>
    border-radius: 3px;
<?php /*     Colours the marker gold. */ ?>
    background: #FFB82B;
<?php /*   Closes the active link marker rule. */ ?>
  }
<?php /*   Turns the active link icon gold. */ ?>
  #sidebar.myadzu-sidebar .myadzu-link.active i { color: #FFB82B; }
<?php /*   Starts the style for an open submenu toggle. */ ?>
  #sidebar.myadzu-sidebar .myadzu-sub-toggle.is-open {
<?php /*     Darkens the open toggle background. */ ?>
    background: #1c242e;
<?php /*     Makes the open toggle text white. */ ?>
    color: #ffffff;
<?php /*   Closes the open toggle rule. */ ?>
  }
<?php /*   Colours the toggle chevron a muted grey. */ ?>
  #sidebar.myadzu-sidebar .myadzu-sub-toggle .nav-chevron { color: #8a94a6; }
<?php /*   ===== Navigation tree: connectors from the CSITE tab to its menus ===== */ ?>
  /* ===== Navigation tree: connectors from the CSITE tab to its menus =====
     One slate trunk drops from the tab icon; each menu hangs from it on a soft
     elbow that stops at the item edge, so lines never run under hover or
     active backgrounds. The route to the current page is lit in gold. */
<?php /*   Design tokens for the connector lines, kept in one place for easy tuning. */ ?>
  #sidebar.myadzu-sidebar {
<?php /*     Resting colour of every connector line. */ ?>
    --tree-line: #465467;
<?php /*     Colour of a connector when its row is hovered. */ ?>
    --tree-hover: #8fa0b5;
<?php /*     Colour of the route leading to the current page. */ ?>
    --tree-gold: #FFB82B;
<?php /*     Length of the horizontal arm between the trunk and an item. */ ?>
    --tree-arm: 0.5rem;
<?php /*     Corner radius of each elbow. */ ?>
    --tree-curve: 6px;
<?php /*     Vertical gap above and below each menu row. */ ?>
    --tree-gap: 0.05rem;
<?php /*   Closes the token rule. */ ?>
  }
<?php /*   Places the main trunk under the centre of the CSITE tab icon. */ ?>
  #sidebar.myadzu-sidebar .myadzu-children { --tree-x: calc(0.75rem + 10px); position: relative; padding: 0.1rem 0 0.25rem; }
<?php /*   Places the sub-menu trunk under the centre of its parent icon. */ ?>
  #sidebar.myadzu-sidebar .myadzu-nested { --tree-x: calc(0.75rem + 9px); position: relative; margin: 0 0 0.1rem; padding: 0; border: 0; }
<?php /*   Resets the lists and indents them just past the trunk and arm. */ ?>
  #sidebar.myadzu-sidebar :is(.myadzu-children, .myadzu-nested) > ul {
    margin: 0; list-style: none;
    padding: 0 0 0 calc(var(--tree-x) + 1px + var(--tree-arm) - 0.1rem);
<?php /*   Closes the list reset rule. */ ?>
  }
<?php /*   Lets each item own its vertical space so trunk segments meet exactly. */ ?>
  #sidebar.myadzu-sidebar :is(.myadzu-children, .myadzu-nested) > ul > li { position: relative; display: flow-root; }
<?php /*   Keeps items hidden by the search filter hidden. */ ?>
  #sidebar.myadzu-sidebar :is(.myadzu-children, .myadzu-nested) > ul > li[hidden] { display: none; }
<?php /*   Gives each row a fixed vertical gap so the elbow can reach the item above. */ ?>
  #sidebar.myadzu-sidebar :is(.myadzu-children, .myadzu-nested) > ul > li > :is(.myadzu-link, .myadzu-sub-toggle) {
    margin: var(--tree-gap) 0.1rem;
<?php /*   Closes the row spacing rule. */ ?>
  }
<?php /*   Draws the short stub joining the CSITE tab to the first menu. */ ?>
  #sidebar.myadzu-sidebar .myadzu-children::before {
    content: ''; position: absolute; top: -0.35rem; height: 0.45rem;
    left: var(--tree-x); width: 1px; background: var(--tree-line);
<?php /*   Closes the tab stub rule. */ ?>
  }
<?php /*   Draws the short stub joining a parent row to its first sub-menu. */ ?>
  #sidebar.myadzu-sidebar .myadzu-nested::before {
    content: ''; position: absolute; top: calc(-1 * var(--tree-gap)); height: var(--tree-gap);
    left: var(--tree-x); width: 1px; background: var(--tree-line);
<?php /*   Closes the parent stub rule. */ ?>
  }
<?php /*   Draws the trunk that passes behind every item that has another after it. */ ?>
  #sidebar.myadzu-sidebar :is(.myadzu-children, .myadzu-nested) > ul > li:not(:last-child)::before {
    content: ''; position: absolute; top: 0; bottom: 0;
    left: calc(0.1rem - 1px - var(--tree-arm)); width: 1px; background: var(--tree-line);
<?php /*   Closes the trunk rule. */ ?>
  }
<?php /*   Ends the trunk at the last visible item while the search filter is active. */ ?>
  #sidebar.myadzu-sidebar :is(.myadzu-children, .myadzu-nested) > ul > li:not(:has(~ li:not([hidden])))::before { display: none; }
<?php /*   Draws the elbow: down from the item above, curving into this row. */ ?>
  #sidebar.myadzu-sidebar :is(.myadzu-children, .myadzu-nested) > ul > li > :is(.myadzu-link, .myadzu-sub-toggle)::after {
    content: ''; position: absolute; pointer-events: none; box-sizing: border-box;
    top: calc(-1 * var(--tree-gap)); height: calc(50% + var(--tree-gap));
    left: calc(-1 * (var(--tree-arm) + 1px)); width: calc(var(--tree-arm) + 1px);
    border: solid transparent; border-width: 0 0 1px 1px;
    border-left-color: var(--tree-line); border-bottom-color: var(--tree-line);
    border-bottom-left-radius: var(--tree-curve);
    transition: border-color 0.18s ease;
<?php /*   Closes the elbow rule. */ ?>
  }
<?php /*   Brightens the arm of the row the pointer is on. */ ?>
  #sidebar.myadzu-sidebar :is(.myadzu-children, .myadzu-nested) > ul > li > :is(.myadzu-link, .myadzu-sub-toggle):hover::after { border-bottom-color: var(--tree-hover); }
<?php /*   Lights the elbow of the current page, or of the group that contains it. */ ?>
  #sidebar.myadzu-sidebar :is(.myadzu-children, .myadzu-nested) > ul > li.is-active-path > :is(.myadzu-link, .myadzu-sub-toggle)::after {
    border-left-color: var(--tree-gold); border-bottom-color: var(--tree-gold);
<?php /*   Closes the active elbow rule. */ ?>
  }
<?php /*   Lights the trunk stub leading into a menu that holds the current page. */ ?>
  #sidebar.myadzu-sidebar .myadzu-children:has(> ul > li.is-active-path)::before,
  #sidebar.myadzu-sidebar .myadzu-nested:has(> ul > li.is-active-path)::before { background: var(--tree-gold); }
<?php /*   Carries the gold route down the trunk through every item above the current page. */ ?>
  #sidebar.myadzu-sidebar :is(.myadzu-children, .myadzu-nested) > ul > li:has(~ li.is-active-path)::before { background: var(--tree-gold); }
<?php /*   Carries the gold route down the upper half of each elbow above the current page. */ ?>
  #sidebar.myadzu-sidebar :is(.myadzu-children, .myadzu-nested) > ul > li:has(~ li.is-active-path) > :is(.myadzu-link, .myadzu-sub-toggle)::after { border-left-color: var(--tree-gold); }
<?php /*   Keeps the nested link text slightly smaller than the main menu. */ ?>
  #sidebar.myadzu-sidebar .myadzu-nested .myadzu-link { padding-left: 0.6rem; font-size: 0.82rem; }
<?php /*   Starts the themed rule for the red update badge. */ ?>
  #sidebar.myadzu-sidebar .myadzu-badge {
<?php /*     Gives the badge its red background. */ ?>
    background: #e02424;
<?php /*     Makes the badge text white. */ ?>
    color: #ffffff;
<?php /*     Makes the badge count extra bold. */ ?>
    font-weight: 800;
<?php /*     Adds a dark ring around the badge. */ ?>
    box-shadow: 0 0 0 2px #232b35;
<?php /*   Closes the badge rule. */ ?>
  }
<?php /*   Matches the badge ring to the active link background. */ ?>
  #sidebar.myadzu-sidebar .myadzu-link.active .myadzu-badge { box-shadow: 0 0 0 2px #1a222c; }
<?php /*   Adds a divider above the social links. */ ?>
  #sidebar.myadzu-sidebar .myadzu-socials { border-top: 1px solid rgba(255, 255, 255, 0.07); }
<?php /*   Sets the social link text colour. */ ?>
  #sidebar.myadzu-sidebar .myadzu-social-link { color: #cbd5e1; }
<?php /*   Lightens the social links on hover. */ ?>
  #sidebar.myadzu-sidebar .myadzu-social-link:hover { background: #2e3844; color: #ffffff; }
<?php /*   Sets the empty-state text colour. */ ?>
  #sidebar.myadzu-sidebar .myadzu-empty { color: #8a94a6; }
<?php /*   Sets a thin scrollbar for the menu area. */ ?>
  #sidebar.myadzu-sidebar .myadzu-nav::-webkit-scrollbar { width: 6px; }
<?php /*   Starts the style for the scrollbar thumb. */ ?>
  #sidebar.myadzu-sidebar .myadzu-nav::-webkit-scrollbar-thumb {
<?php /*     Colours the scrollbar thumb translucent white. */ ?>
    background: rgba(255, 255, 255, 0.16);
<?php /*     Rounds the scrollbar thumb fully. */ ?>
    border-radius: 99px;
<?php /*   Closes the second style block. */ ?>
  }</style>

<?php /* Starts the sidebar element that holds the whole navigation panel. */ ?>
<aside class="sidebar myadzu-sidebar" id="sidebar" aria-label="MyADZU sidebar navigation">

<?php /*   HTML comment marking the brand, seal and user group. */ ?>
  <!-- 1. BRAND, CENTRED SEAL & USER -->
<?php /*   Starts the brand bar at the top of the sidebar. */ ?>
  <div class="myadzu-brand">
<?php /*     Shows the MyADZU product name. */ ?>
    <strong>MyADZU</strong>
<?php /*     Shows the school name under the brand. */ ?>
    <span>CSITE Graduate School</span>
<?php /*   Closes the brand bar. */ ?>
  </div>

<?php /*   Starts the profile area with the seal and user name. */ ?>
  <div class="myadzu-profile">
<?php /*     Starts the seal container with an accessible label. */ ?>
    <div class="myadzu-avatar" role="img" aria-label="ADZU seal">
<?php /*       Shows the ADZU seal image and hides it if the file is missing. */ ?>
      <img class="myadzu-seal" src="<?= asset('img/adzu-seal.png') ?>" alt="ADZU seal" onerror="this.style.display='none'">
<?php /*     Closes the seal container. */ ?>
    </div>
<?php /*     Checks whether a user name is available before printing it. */ ?>
    <?php if (!empty($userName)): ?>
<?php /*       Prints the logged-in user name safely escaped. */ ?>
      <div class="myadzu-username"><?= htmlspecialchars($userName) ?></div>
<?php /*     Closes the check for a user name. */ ?>
    <?php endif; ?>
<?php /*     Starts the search box wrapper. */ ?>
    <div class="myadzu-search">
<?php /*       Adds a hidden label so screen readers can name the search box. */ ?>
      <label for="myadzuMenuSearch" class="sr-only" style="position:absolute;left:-9999px;">Search menu</label>
<?php /*       Adds the search input that filters the menu as the user types. */ ?>
      <input type="search" id="myadzuMenuSearch" placeholder="Search menu..." autocomplete="off" data-menu-filter aria-label="Search menu">
<?php /*       Shows the magnifying glass icon inside the search box. */ ?>
      <i class="fas fa-search" aria-hidden="true"></i>
<?php /*     Closes the search box wrapper. */ ?>
    </div>
<?php /*   Closes the profile area. */ ?>
  </div>

<?php /*   HTML comment marking the start of the navigation sections. */ ?>
  <!-- 2 + 3. NAVIGATION SECTIONS -->
<?php /*   Starts the navigation area that holds the menu sections. */ ?>
  <nav class="myadzu-nav" aria-label="Portal navigation">

<?php /*     Checks whether the student menu should be shown. */ ?>
    <?php if ($showStudentSection): ?>
<?php /*       Shows the Main Navigation section label. */ ?>
      <div class="myadzu-section-label">Main Navigation</div>
<?php /*       Starts the list of student menu items. */ ?>
      <ul>
<?php /*         Starts the list item for the CSITE group. */ ?>
        <li>
<?php /*           Starts the CSITE root toggle and opens it when a student page is active. */ ?>
          <div class="myadzu-root <?= $csiteStudentOpen ? 'is-open' : '' ?>"
<?php /*                Marks the root row as a clickable toggle for the script. */ ?>
               data-submenu-toggle role="button" tabindex="0"
<?php /*                Tells screen readers whether the group is expanded. */ ?>
               aria-expanded="<?= $csiteStudentOpen ? 'true' : 'false' ?>">
<?php /*             Starts the group that holds the root icon and label. */ ?>
            <span class="myadzu-root-left">
<?php /*               Shows the graduate cap icon for the group. */ ?>
              <i class="fas fa-user-graduate" aria-hidden="true"></i>
<?php /*               Shows the CSITE group name. */ ?>
              <span>CSITE</span>
<?php /*             Closes the root icon and label group. */ ?>
            </span>
<?php /*             Shows the chevron and rotates it when the group is open. */ ?>
            <i class="fas fa-chevron-down nav-chevron <?= $csiteStudentOpen ? 'open' : '' ?>" aria-hidden="true"></i>
<?php /*           Closes the CSITE root toggle. */ ?>
          </div>
<?php /*           Starts the child container and opens it on student pages. */ ?>
          <div class="myadzu-children <?= $csiteStudentOpen ? 'open' : '' ?>">
<?php /*             Starts the list of student links. */ ?>
            <ul>
<?php /*               Loops over every student menu link. */ ?>
              <?php foreach ($studentLinks as $item): ?>
<?php /*                 Starts a list item and stores its label for the search filter. */ ?>
                <li class="<?= $item['key'] === $currentPage || (!empty($item['submenu']) && $isSubmenuChildActive($item['submenu'], $currentPage)) ? 'is-active-path' : '' ?>" data-menu-item data-menu-label="<?= htmlspecialchars(strtolower($item['label'])) ?>">
<?php /*                   Checks whether this menu item has a submenu. */ ?>
                  <?php if (!empty($item['submenu'])): ?>
<?php /*                     Works out whether this submenu should start open. */ ?>
                    <?php $subOpen = $isSubmenuChildActive($item['submenu'], $currentPage) || ($item['key'] === $currentPage); ?>
<?php /*                     Starts the submenu toggle row and marks it open when needed. */ ?>
                    <div class="myadzu-sub-toggle <?= $subOpen ? 'is-open' : '' ?>"
<?php /*                          Marks the toggle row as clickable for the script. */ ?>
                         data-submenu-toggle role="button" tabindex="0"
<?php /*                          Tells screen readers whether the submenu is expanded. */ ?>
                         aria-expanded="<?= $subOpen ? 'true' : 'false' ?>">
<?php /*                       Shows the icon for this submenu group. */ ?>
                      <i class="fas <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
<?php /*                       Shows the label for this submenu group. */ ?>
                      <span><?= htmlspecialchars($item['label']) ?></span>
<?php /*                       Shows the chevron and rotates it when the submenu is open. */ ?>
                      <i class="fas fa-chevron-down nav-chevron <?= $subOpen ? 'open' : '' ?>" aria-hidden="true"></i>
<?php /*                     Closes the submenu toggle row. */ ?>
                    </div>
<?php /*                     Starts the nested list and opens it when needed. */ ?>
                    <div class="myadzu-nested <?= $subOpen ? 'open' : '' ?>">
<?php /*                       Starts the list of submenu links. */ ?>
                      <ul>
<?php /*                         Loops over every link inside the submenu. */ ?>
                        <?php foreach ($item['submenu'] as $sub): ?>
<?php /*                           Starts a submenu list item with its search label. */ ?>
                          <li class="<?= $sub['key'] === $currentPage ? 'is-active-path' : '' ?>" data-menu-item data-menu-label="<?= htmlspecialchars(strtolower($sub['label'])) ?>">
<?php /*                             Starts a submenu link and marks it active when it matches the page. */ ?>
                            <a class="myadzu-link <?= navActive($sub['key'], $currentPage) ?>"
<?php /*                                Sets the link address. */ ?>
                               href="<?= $sub['href'] ?>">
<?php /*                               Shows the link icon. */ ?>
                              <i class="fas <?= htmlspecialchars($sub['icon']) ?>" aria-hidden="true"></i>
<?php /*                               Shows the link label. */ ?>
                              <span><?= htmlspecialchars($sub['label']) ?></span>
<?php /*                             Closes the submenu link. */ ?>
                            </a>
<?php /*                           Closes the submenu list item. */ ?>
                          </li>
<?php /*                         Ends the loop over submenu links. */ ?>
                        <?php endforeach; ?>
<?php /*                       Closes the nested list. */ ?>
                      </ul>
<?php /*                     Closes the nested container. */ ?>
                    </div>
<?php /*                   Runs when the menu item has no submenu. */ ?>
                  <?php else: ?>
<?php /*                     Starts a plain menu link and marks it active when it matches the page. */ ?>
                    <a class="myadzu-link <?= navActive($item['key'], $currentPage) ?>"
<?php /*                        Sets the plain link address. */ ?>
                       href="<?= $item['href'] ?>">
<?php /*                       Shows the plain link icon. */ ?>
                      <i class="fas <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
<?php /*                       Shows the plain link label. */ ?>
                      <span><?= htmlspecialchars($item['label']) ?></span>
<?php /*                       Checks whether this item carries a badge count. */ ?>
                      <?php if (array_key_exists('badge', $item)):
                          // Converts the badge count to a whole number for display.
                          $badgeCount = (int) $item['badge']; ?>
<?php /*                         Shows the badge and hides it when the count is zero. */ ?>
                        <span class="myadzu-badge" data-template-badge<?= $badgeCount > 0 ? '' : ' hidden' ?>
<?php /*                               Explains the badge count in a tooltip and caps it at 9+. */ ?>
                              title="<?= $badgeCount ?> template update<?= $badgeCount === 1 ? '' : 's' ?> to review"><?= $badgeCount > 9 ? '9+' : $badgeCount ?></span>
<?php /*                       Closes the badge check. */ ?>
                      <?php endif; ?>
<?php /*                     Closes the plain menu link. */ ?>
                    </a>
<?php /*                   Closes the check for a submenu. */ ?>
                  <?php endif; ?>
<?php /*                 Closes the student list item. */ ?>
                </li>
<?php /*               Ends the loop over student links. */ ?>
              <?php endforeach; ?>
<?php /*             Closes the student link list. */ ?>
            </ul>
<?php /*           Closes the student child container. */ ?>
          </div>
<?php /*         Closes the CSITE student group item. */ ?>
        </li>
<?php /*       Closes the student menu list. */ ?>
      </ul>
<?php /*     Closes the check for the student section. */ ?>
    <?php endif; ?>

<?php /*     Checks whether the coordinator menu should be shown. */ ?>
    <?php if ($showCoordinatorSection): ?>
<?php /*       Shows the Coordinator section label. */ ?>
      <div class="myadzu-section-label">Coordinator</div>
<?php /*       Starts the list of coordinator menu items. */ ?>
      <ul>
<?php /*         Starts the list item for the CSITE group. */ ?>
        <li>
<?php /*           Starts the CSITE root toggle and opens it on coordinator pages. */ ?>
          <div class="myadzu-root <?= $csiteCoordOpen ? 'is-open' : '' ?>"
<?php /*                Marks the root row as a clickable toggle for the script. */ ?>
               data-submenu-toggle role="button" tabindex="0"
<?php /*                Tells screen readers whether the group is expanded. */ ?>
               aria-expanded="<?= $csiteCoordOpen ? 'true' : 'false' ?>">
<?php /*             Starts the group that holds the root icon and label. */ ?>
            <span class="myadzu-root-left">
<?php /*               Shows the graduate cap icon for the group. */ ?>
              <i class="fas fa-user-graduate" aria-hidden="true"></i>
<?php /*               Shows the CSITE group name. */ ?>
              <span>CSITE</span>
<?php /*             Closes the root icon and label group. */ ?>
            </span>
<?php /*             Shows the chevron and rotates it when the group is open. */ ?>
            <i class="fas fa-chevron-down nav-chevron <?= $csiteCoordOpen ? 'open' : '' ?>" aria-hidden="true"></i>
<?php /*           Closes the CSITE root toggle. */ ?>
          </div>
<?php /*           Starts the child container and opens it on coordinator pages. */ ?>
          <div class="myadzu-children <?= $csiteCoordOpen ? 'open' : '' ?>">
<?php /*             Starts the list of coordinator links. */ ?>
            <ul>
<?php /*               Loops over every coordinator menu link. */ ?>
              <?php foreach ($coordinatorLinks as $item): ?>
<?php /*                 Starts a list item and stores its label for the search filter. */ ?>
                <li class="<?= $item['key'] === $currentPage || (!empty($item['submenu']) && $isSubmenuChildActive($item['submenu'], $currentPage)) ? 'is-active-path' : '' ?>" data-menu-item data-menu-label="<?= htmlspecialchars(strtolower($item['label'])) ?>">
<?php /*                   Checks whether this menu item has a submenu. */ ?>
                  <?php if (!empty($item['submenu'])): ?>
<?php /*                     Works out whether this submenu should start open. */ ?>
                    <?php $subOpen = $isSubmenuChildActive($item['submenu'], $currentPage) || ($item['key'] === $currentPage); ?>
<?php /*                     Starts the submenu toggle row and marks it open when needed. */ ?>
                    <div class="myadzu-sub-toggle <?= $subOpen ? 'is-open' : '' ?>"
<?php /*                          Marks the toggle row as clickable for the script. */ ?>
                         data-submenu-toggle role="button" tabindex="0"
<?php /*                          Tells screen readers whether the submenu is expanded. */ ?>
                         aria-expanded="<?= $subOpen ? 'true' : 'false' ?>">
<?php /*                       Shows the icon for this submenu group. */ ?>
                      <i class="fas <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
<?php /*                       Shows the label for this submenu group. */ ?>
                      <span><?= htmlspecialchars($item['label']) ?></span>
<?php /*                       Shows the chevron and rotates it when the submenu is open. */ ?>
                      <i class="fas fa-chevron-down nav-chevron <?= $subOpen ? 'open' : '' ?>" aria-hidden="true"></i>
<?php /*                     Closes the submenu toggle row. */ ?>
                    </div>
<?php /*                     Starts the nested list and opens it when needed. */ ?>
                    <div class="myadzu-nested <?= $subOpen ? 'open' : '' ?>">
<?php /*                       Starts the list of submenu links. */ ?>
                      <ul>
<?php /*                         Loops over every link inside the submenu. */ ?>
                        <?php foreach ($item['submenu'] as $sub): ?>
<?php /*                           Starts a submenu list item with its search label. */ ?>
                          <li class="<?= $sub['key'] === $currentPage ? 'is-active-path' : '' ?>" data-menu-item data-menu-label="<?= htmlspecialchars(strtolower($sub['label'])) ?>">
<?php /*                             Starts a submenu link and marks it active when it matches the page. */ ?>
                            <a class="myadzu-link <?= navActive($sub['key'], $currentPage) ?>"
<?php /*                                Sets the link address. */ ?>
                               href="<?= $sub['href'] ?>">
<?php /*                               Shows the link icon. */ ?>
                              <i class="fas <?= htmlspecialchars($sub['icon']) ?>" aria-hidden="true"></i>
<?php /*                               Shows the link label. */ ?>
                              <span><?= htmlspecialchars($sub['label']) ?></span>
<?php /*                             Closes the submenu link. */ ?>
                            </a>
<?php /*                           Closes the submenu list item. */ ?>
                          </li>
<?php /*                         Ends the loop over submenu links. */ ?>
                        <?php endforeach; ?>
<?php /*                       Closes the nested list. */ ?>
                      </ul>
<?php /*                     Closes the nested container. */ ?>
                    </div>
<?php /*                   Runs when the menu item has no submenu. */ ?>
                  <?php else: ?>
<?php /*                     Starts a plain menu link and marks it active when it matches the page. */ ?>
                    <a class="myadzu-link <?= navActive($item['key'], $currentPage) ?>"
<?php /*                        Sets the plain link address. */ ?>
                       href="<?= $item['href'] ?>">
<?php /*                       Shows the plain link icon. */ ?>
                      <i class="fas <?= htmlspecialchars($item['icon']) ?>" aria-hidden="true"></i>
<?php /*                       Shows the plain link label. */ ?>
                      <span><?= htmlspecialchars($item['label']) ?></span>
<?php /*                       Checks whether this item carries a badge count. */ ?>
                      <?php if (array_key_exists('badge', $item)):
                          // Converts the badge count to a whole number for display.
                          $badgeCount = (int) $item['badge']; ?>
<?php /*                         Shows the badge and hides it when the count is zero. */ ?>
                        <span class="myadzu-badge" data-template-badge<?= $badgeCount > 0 ? '' : ' hidden' ?>
<?php /*                               Explains the badge count in a tooltip and caps it at 9+. */ ?>
                              title="<?= $badgeCount ?> template update<?= $badgeCount === 1 ? '' : 's' ?> to review"><?= $badgeCount > 9 ? '9+' : $badgeCount ?></span>
<?php /*                       Closes the badge check. */ ?>
                      <?php endif; ?>
<?php /*                     Closes the plain menu link. */ ?>
                    </a>
<?php /*                   Closes the check for a submenu. */ ?>
                  <?php endif; ?>
<?php /*                 Closes the coordinator list item. */ ?>
                </li>
<?php /*               Ends the loop over coordinator links. */ ?>
              <?php endforeach; ?>
<?php /*             Closes the coordinator link list. */ ?>
            </ul>
<?php /*           Closes the coordinator child container. */ ?>
          </div>
<?php /*         Closes the CSITE coordinator group item. */ ?>
        </li>
<?php /*       Closes the coordinator menu list. */ ?>
      </ul>
<?php /*     Closes the check for the coordinator section. */ ?>
    <?php endif; ?>


<?php /*   Closes the navigation area. */ ?>
  </nav>
<?php /* Closes the sidebar element. */ ?>
</aside>
<?php /* Adds the dark overlay shown behind the sidebar on small screens. */ ?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<?php /* Starts the script that powers menu search and badge refresh. */ ?>
<script>
<?php /* Starts an immediately invoked function to keep variables private. */ ?>
(function () {
<?php /*   JS comment explaining the live menu filter. */ ?>
  // Live-filter sidebar menu items. Scoped to this partial so it works
<?php /*   JS comment explaining why the filter lives in this file. */ ?>
  // even if assets/js/shared/app.js is cached or deferred.
<?php /*   Finds the search input inside the sidebar. */ ?>
  var input = document.querySelector('[data-menu-filter]');
<?php /*   Stops the script when the search input is missing. */ ?>
  if (!input) return;
<?php /*   Gets the sidebar element for scoped queries. */ ?>
  var sidebar = document.getElementById('sidebar');
<?php /*   Holds the timer used to debounce typing. */ ?>
  var debounceTimer = null;
<?php /*   Defines the debounce helper for the search input. */ ?>
  function scheduleFilter() {
<?php /*     Cancels the previous timer so only one run is queued. */ ?>
    if (debounceTimer) clearTimeout(debounceTimer);
<?php /*     Runs the filter 120ms after the user stops typing. */ ?>
    debounceTimer = setTimeout(applyFilter, 120);
<?php /*   Closes the debounce helper. */ ?>
  }
<?php /*   Defines the function that filters the menu items. */ ?>
  function applyFilter() {
<?php /*     Reads and cleans the search text. */ ?>
    var q = (input.value || '').trim().toLowerCase();
<?php /*     Gets every menu item, or an empty list without a sidebar. */ ?>
    var items = sidebar ? sidebar.querySelectorAll('[data-menu-item]') : [];
<?php /*     Counts how many links stay visible. */ ?>
    var visible = 0;
<?php /*     Loops over every menu item. */ ?>
    items.forEach(function (li) {
<?php /*       Reads the item search label in lowercase. */ ?>
      var label = (li.getAttribute('data-menu-label') || '').toLowerCase();
<?php /*       JS comment explaining which links are checked. */ ?>
      // Only leaf links participate; parent <li> wraps children so check
<?php /*       JS comment explaining the parent and child link check. */ ?>
      // whether any descendant leaf matches before hiding.
<?php /*       Finds the links inside this item, including nested ones. */ ?>
      var leaves = li.querySelectorAll(':scope > a, :scope .myadzu-nested a');
<?php /*       Uses the item itself when it has no links. */ ?>
      if (leaves.length === 0) leaves = [li];
<?php /*       Treats an empty search as a match for everything. */ ?>
      var match = !q;
<?php /*       Checks the search text only when the user typed something. */ ?>
      if (q) {
<?php /*         Looks for the search text inside the item label. */ ?>
        match = label.indexOf(q) !== -1;
<?php /*         Tries the link text when the label does not match. */ ?>
        if (!match) {
<?php /*           Loops over the links inside this item. */ ?>
          leaves.forEach(function (a) {
<?php /*             Marks a match when a link text contains the search text. */ ?>
            if ((a.textContent || '').toLowerCase().indexOf(q) !== -1) match = true;
<?php /*           Closes the link text loop. */ ?>
          });
<?php /*         Closes the link text fallback. */ ?>
        }
<?php /*       Closes the typed-text check. */ ?>
      }
<?php /*       JS comment explaining that parent rows stay visible for matching children. */ ?>
      // For parent wrappers with nested lists, keep visible if a child matches.
<?php /*       Keeps a parent row visible when one of its children matches. */ ?>
      if (!match && li.querySelector('.myadzu-nested')) {
<?php /*         Reads all text inside the item, including children. */ ?>
        var childText = (li.textContent || '').toLowerCase();
<?php /*         Checks whether that text contains the search text. */ ?>
        match = childText.indexOf(q) !== -1;
<?php /*       Closes the parent row check. */ ?>
      }
<?php /*       Hides the item with the hidden attribute. */ ?>
      li.hidden = !match;
<?php /*       Also hides the item with inline display styles. */ ?>
      li.style.display = match ? '' : 'none';
<?php /*       Counts the item as visible when it has a direct link. */ ?>
      if (match && li.querySelector(':scope > a')) visible++;
<?php /*     Closes the loop over menu items. */ ?>
    });
<?php /*     JS comment explaining that groups open while searching. */ ?>
    // Auto-expand collapsed groups while searching.
<?php /*     Only touches the sidebar when it exists. */ ?>
    if (sidebar) {
<?php /*       Loops over every collapsible menu group. */ ?>
      sidebar.querySelectorAll('.myadzu-children, .myadzu-nested').forEach(function (box) {
<?php /*         Opens each group while a search is active. */ ?>
        if (q) { box.classList.add('open'); }
<?php /*       Closes the group loop. */ ?>
      });
<?php /*     Closes the sidebar check. */ ?>
    }
<?php /*     Finds the optional empty-state message in the sidebar. */ ?>
    var empty = sidebar ? sidebar.querySelector('[data-menu-empty]') : null;
<?php /*     Runs the empty-state check when that message exists. */ ?>
    if (empty) {
<?php /*       Checks whether any menu item is still visible. */ ?>
      var anyVisible = Array.prototype.some.call(items, function (li) { return !li.hidden; });
<?php /*       Hides the empty message when something is visible. */ ?>
      empty.hidden = anyVisible;
<?php /*       Also toggles the empty message with inline styles. */ ?>
      empty.style.display = anyVisible ? 'none' : '';
<?php /*     Closes the empty-state check. */ ?>
    }
<?php /*   Closes the filter function. */ ?>
  }
<?php /*   Runs the filter, with debounce, whenever the user types. */ ?>
  input.addEventListener('input', scheduleFilter);
<?php /*   Adds a key handler so Escape clears the search. */ ?>
  input.addEventListener('keydown', function (e) {
<?php /*     Clears the search text and refreshes the menu on Escape. */ ?>
    if (e.key === 'Escape') { input.value = ''; applyFilter(); }
<?php /*   Closes the Escape key handler. */ ?>
  });

<?php /*   JS comment explaining the badge refresh request. */ ?>
  // Keep the template update badge in step with Google Drive. The endpoint
<?php /*   JS comment noting the endpoint only calls Drive when stale. */ ?>
  // only looks at Drive when the last check has gone stale, so this stays
<?php /*   JS comment about the request staying cheap and non-blocking. */ ?>
  // cheap and never blocks the page.
<?php /*   Finds the template badge in the sidebar. */ ?>
  var badge = document.querySelector('[data-template-badge]');
<?php /*   Finds the optional pending-sync badge. */ ?>
  var pendingBadge = document.querySelector('[data-template-pending]');
<?php /*   Only refreshes when a badge exists and fetch is supported. */ ?>
  if ((badge || pendingBadge) && window.fetch) {
<?php /*     Defines the function that fetches fresh badge counts. */ ?>
    var refreshBadge = function () {
<?php /*       Calls the sync endpoint with the current session cookies. */ ?>
      fetch(<?= json_encode(url('template_sync.php')) ?>, { credentials: 'same-origin' })
<?php /*         Reads the JSON reply, or null when the request failed. */ ?>
        .then(function (response) { return response.ok ? response.json() : null; })
<?php /*         Handles the JSON data returned by the endpoint. */ ?>
        .then(function (data) {
<?php /*           Stops when the response had no usable data. */ ?>
          if (!data) return;
<?php /*           Updates the badge only when the count is a number. */ ?>
          if (badge && typeof data.count === 'number') {
<?php /*             Checks whether there are updates to show. */ ?>
            if (data.count > 0) {
<?php /*               Writes the count, capping it at 9+. */ ?>
              badge.textContent = data.count > 9 ? '9+' : String(data.count);
<?php /*               Updates the badge tooltip to match the count. */ ?>
              badge.title = data.count + ' template update' + (data.count === 1 ? '' : 's') + ' to review';
<?php /*               Shows the badge again. */ ?>
              badge.hidden = false;
<?php /*             Runs when there are no updates. */ ?>
            } else {
<?php /*               Hides the badge when the count is zero. */ ?>
              badge.hidden = true;
<?php /*             Closes the count check. */ ?>
            }
<?php /*           Closes the numeric count check. */ ?>
          }
<?php /*           JS comment explaining the pending Drive file count. */ ?>
          // The number of files in Google Drive that are not in the system
<?php /*           JS comment explaining what the coordinator does with pending files. */ ?>
          // yet. The coordinator applies them with "Sync from Google Drive".
<?php /*           Updates the pending badge when the endpoint sent a number. */ ?>
          if (pendingBadge && typeof data.pending === 'number') {
<?php /*             Checks whether there are pending files. */ ?>
            if (data.pending > 0) {
<?php /*               Writes the pending count, capping it at 9+. */ ?>
              pendingBadge.textContent = data.pending > 9 ? '9+' : String(data.pending);
<?php /*               Updates the pending badge tooltip. */ ?>
              pendingBadge.title = data.pending + ' file(s) waiting to be synchronized';
<?php /*               Shows the pending badge again. */ ?>
              pendingBadge.hidden = false;
<?php /*             Runs when there are no pending files. */ ?>
            } else {
<?php /*               Hides the pending badge. */ ?>
              pendingBadge.hidden = true;
<?php /*             Closes the pending count check. */ ?>
            }
<?php /*           Closes the numeric pending check. */ ?>
          }
<?php /*         Closes the response handler. */ ?>
        })
<?php /*         Ignores network errors so the page still works offline. */ ?>
        .catch(function () {});
<?php /*     Closes the badge refresh function. */ ?>
    };
<?php /*     Runs the refresh right away when the page has finished loading. */ ?>
    if (document.readyState === 'complete') {
<?php /*       Waits 1.2 seconds before refreshing the badge. */ ?>
      setTimeout(refreshBadge, 1200);
<?php /*     Runs when the page is still loading. */ ?>
    } else {
<?php /*       Refreshes the badge after the load event plus a short delay. */ ?>
      window.addEventListener('load', function () { setTimeout(refreshBadge, 1200); });
<?php /*     Closes the load-state check. */ ?>
    }
<?php /*   Closes the check for badge elements and fetch support. */ ?>
  }
<?php /* Closes the immediately invoked function. */ ?>
})();
<?php /* Closes the script tag. */ ?>
</script>