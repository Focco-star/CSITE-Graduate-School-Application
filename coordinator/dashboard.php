<?php /* Opens the PHP block for the coordinator dashboard. */ ?>
<?php
// Loads the shared config with helpers and demo data.
require_once __DIR__ . '/../includes/config/config.php';
// Loads the DB class for database access.
require_once __DIR__ . '/../includes/config/db.php';
// Loads the helper that draws the program population chart.
require_once __DIR__ . '/../includes/components/program_chart.php';
// Checks whether a session is not running yet.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so the logged-in user can be read.
    session_start();
// Closes the session check block.
}

// Reads the logged-in user array, or an empty array if none.
$sessionUser = $_SESSION['user'] ?? [];
// Picks the display name from the session or the demo coordinator.
$userName    = !empty($sessionUser['full_name']) ? $sessionUser['full_name'] : ($mockCoordinator['name'] ?? 'Coordinator');
// Sets the browser tab title to Dashboard.
$pageTitle   = 'Dashboard';
// Marks the page as part of the coordinator area.
$role        = 'coordinator';
// Highlights the Dashboard link in the sidebar.
$currentPage = 'dashboard';


// Checks that no database connection exists yet.
if (!isset($pdo) || !($pdo instanceof PDO)) {
    // Opens a try block to build the connection safely.
    try {
        // Reads the DB host constant, defaulting to localhost.
        $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
        // Reads the DB name constant, defaulting to csite_grad_school.
        $dbName = defined('DB_NAME') ? DB_NAME : 'csite_grad_school';
        // Reads the DB user constant, defaulting to root.
        $dbUser = defined('DB_USER') ? DB_USER : 'root';
        // Reads the DB password constant, defaulting to empty.
        $dbPass = defined('DB_PASS') ? DB_PASS : '';
        // Creates the PDO connection to MySQL using UTF-8.
        $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
            // Makes PDO throw exceptions when queries fail.
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Fetches rows as associative arrays by default.
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Closes the PDO option array and connection call.
        ]);
    // Catches a failed connection attempt.
    } catch (PDOException $e) {
        // Leaves the connection empty so the page can still load.
        $pdo = null;
    // Closes the connection catch block.
    }
// Closes the block that only ran when no connection existed.
}


// Works out the academic year start, with July as the new year.
$academicYearStart = (int) date('n') >= 7 ? (int) date('Y') : (int) date('Y') - 1;
// Builds the academic year label such as 2025-2026.
$academicYearLabel = $academicYearStart . '-' . ($academicYearStart + 1);
// Builds the inclusive start and exclusive end dates for the academic year.
$academicYearStartDate = sprintf('%04d-07-01', $academicYearStart);
$academicYearEndDate = sprintf('%04d-07-01', $academicYearStart + 1);

// Loads summary counts when the database connection is available.
$dashboardStats = null;
if ($pdo instanceof PDO) {
    $statsStatement = $pdo->prepare(
        "SELECT
            (SELECT COUNT(DISTINCT a.user_id)
             FROM applications a
             INNER JOIN students st ON st.user_id = a.user_id
             WHERE a.archived_at IS NULL
               AND st.archived_at IS NULL
               AND a.presentation_stage = 'Concept Paper'
               AND a.status NOT IN ('approved', 'completed')) AS concept_students,
            (SELECT COUNT(DISTINCT a.user_id)
             FROM applications a
             INNER JOIN students st ON st.user_id = a.user_id
             WHERE a.archived_at IS NULL
               AND st.archived_at IS NULL
               AND a.presentation_stage LIKE '%Proposal%'
               AND a.status NOT IN ('approved', 'completed')) AS proposal_students,
            (SELECT COUNT(DISTINCT a.user_id)
             FROM applications a
             INNER JOIN students st ON st.user_id = a.user_id
             WHERE a.archived_at IS NULL
               AND st.archived_at IS NULL
               AND a.presentation_stage LIKE 'Final%'
               AND a.status NOT IN ('approved', 'completed')) AS final_students,
            (SELECT COUNT(DISTINCT a.user_id)
             FROM applications a
             INNER JOIN students st ON st.user_id = a.user_id
             INNER JOIN schedules s ON s.application_id = a.application_id
             WHERE a.archived_at IS NULL
               AND st.archived_at IS NULL
               AND a.status IN ('approved', 'completed')
               AND s.status = 'completed'
               AND s.presentation_date >= :academic_year_start
               AND s.presentation_date < :academic_year_end) AS completed_students"
    );
    $statsStatement->execute([
        'academic_year_start' => $academicYearStartDate,
        'academic_year_end' => $academicYearEndDate,
    ]);
    $dashboardStats = $statsStatement->fetch(PDO::FETCH_ASSOC) ?: null;
}

// ---------------------------------------------------------------------------
// NEW: Program breakdown for the Quick Summary dropdown cards.
// ---------------------------------------------------------------------------

// Escapes text for safe output inside the dropdown panels.
if (!function_exists('qsEscape')) {
    function qsEscape($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
}

// Uses the student's program for the stage breakdown.
$programExpr = "COALESCE(NULLIF(TRIM(st.program), ''), 'Unspecified')";

// Describes how to count students for each card, matching the card totals above.
$stageDefinitions = [
    'concept' => [
        'joins'  => '',
        'where'  => "st.archived_at IS NULL AND a.presentation_stage = 'Concept Paper' AND a.status NOT IN ('approved', 'completed')",
        'params' => [],
    ],
    'proposal' => [
        'joins'  => '',
        'where'  => "st.archived_at IS NULL AND a.presentation_stage LIKE '%Proposal%' AND a.status NOT IN ('approved', 'completed')",
        'params' => [],
    ],
    'final' => [
        'joins'  => '',
        'where'  => "st.archived_at IS NULL AND a.presentation_stage LIKE 'Final%' AND a.status NOT IN ('approved', 'completed')",
        'params' => [],
    ],
    'completed' => [
        'joins'  => 'INNER JOIN schedules s ON s.application_id = a.application_id',
        'where'  => "st.archived_at IS NULL AND a.status IN ('approved', 'completed') AND s.status = 'completed'
                     AND s.presentation_date >= :ay_start AND s.presentation_date < :ay_end",
        'params' => ['ay_start' => $academicYearStartDate, 'ay_end' => $academicYearEndDate],
    ],
];

// Holds the program breakdown for each card; null means it could not be loaded.
$stageBreakdown = ['concept' => null, 'proposal' => null, 'final' => null, 'completed' => null];

// Runs one grouped query per card when the database is available.
if ($pdo instanceof PDO) {
    foreach ($stageDefinitions as $stageKey => $def) {
        try {
            $st = $pdo->prepare(
                "SELECT {$programExpr} AS program_name, COUNT(DISTINCT a.user_id) AS total
                 FROM applications a
                 INNER JOIN students st ON st.user_id = a.user_id
                 {$def['joins']}
                 WHERE a.archived_at IS NULL AND {$def['where']}
                 GROUP BY program_name
                 ORDER BY total DESC, program_name ASC"
            );
            $st->execute($def['params']);
            $stageBreakdown[$stageKey] = $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // A wrong column name only disables the dropdown, not the whole page.
            $stageBreakdown[$stageKey] = null;
        }
    }
}

// Defines the four cards so the markup below can loop over them.
$summaryCards = [
    ['key' => 'concept',   'tone' => 'navy',  'icon' => 'fa-file-lines',     'label' => 'Concept Paper',               'stat' => 'concept_students'],
    ['key' => 'proposal',  'tone' => 'gold',  'icon' => 'fa-book-open',      'label' => 'Proposal',                    'stat' => 'proposal_students'],
    ['key' => 'final',     'tone' => 'blue',  'icon' => 'fa-graduation-cap', 'label' => 'Final Paper',                 'stat' => 'final_students'],
    ['key' => 'completed', 'tone' => 'green', 'icon' => 'fa-circle-check',   'label' => 'Completed This School Year', 'stat' => 'completed_students'],
];

// Loads the shared header with sidebar.
require_once __DIR__ . '/../includes/layout/header.php';
// Closes PHP so the HTML below is printed.
?>

<section class="quick-summary" aria-labelledby="dashboardSummaryTitle">
    <header class="quick-summary-header">
        <div class="quick-summary-heading">
            <span class="quick-summary-mark" aria-hidden="true"><i class="fas fa-chart-pie"></i></span>
            <div class="quick-summary-heading-text">
                <h3 id="dashboardSummaryTitle">Quick Summary</h3>
                <p>Students by research stage</p>
            </div>
        </div>
        <span class="quick-summary-year"><i class="fas fa-calendar-days" aria-hidden="true"></i> School Year <?= htmlspecialchars($academicYearLabel, ENT_QUOTES, 'UTF-8') ?></span>
    </header>
    <div class="quick-summary-body">
        <div class="quick-summary-grid">
            <?php foreach ($summaryCards as $card):
                $rows       = $stageBreakdown[$card['key']];
                $stageTotal = $rows ? array_sum(array_column($rows, 'total')) : 0;
                $panelId    = 'qsPanel-' . $card['key'];
            ?>
                <?php /* The tone class sits on the wrapper so the card and its panel share the same accent colours. */ ?>
                <div class="quick-summary-item quick-summary-stat--<?= qsEscape($card['tone']) ?>">
                    <button type="button" class="quick-summary-stat" data-qs-toggle
                            aria-expanded="false" aria-controls="<?= qsEscape($panelId) ?>">
                        <span class="quick-summary-stat-head">
                            <span class="quick-summary-icon" aria-hidden="true"><i class="fas <?= qsEscape($card['icon']) ?>"></i></span>
                            <span class="quick-summary-arrow" aria-hidden="true"><i class="fas fa-chevron-down"></i></span>
                        </span>
                        <strong class="quick-summary-value"><?= $dashboardStats === null ? '&mdash;' : number_format((int) $dashboardStats[$card['stat']]) ?></strong>
                        <span class="quick-summary-label"><?= qsEscape($card['label']) ?></span>
                    </button>

                    <div class="quick-summary-panel" id="<?= qsEscape($panelId) ?>" role="region"
                         aria-label="<?= qsEscape($card['label']) ?> by program" hidden>
                        <div class="quick-summary-panel-head">
                            <span>Program</span>
                            <span>Students</span>
                        </div>

                        <?php if ($rows === null): ?>
                            <p class="quick-summary-panel-empty">Program breakdown is unavailable.</p>
                        <?php elseif (!$rows): ?>
                            <p class="quick-summary-panel-empty">No students in this stage yet.</p>
                        <?php else: ?>
                            <ul class="quick-summary-panel-list">
                                <?php foreach ($rows as $row):
                                    $count   = (int) $row['total'];
                                    $percent = $stageTotal > 0 ? round($count / $stageTotal * 100) : 0;
                                ?>
                                    <li class="quick-summary-panel-row">
                                        <span class="quick-summary-panel-name"><?= qsEscape($row['program_name']) ?></span>
                                        <strong class="quick-summary-panel-count"><?= number_format($count) ?></strong>
                                        <span class="quick-summary-panel-bar" aria-hidden="true"><i style="width: <?= (int) $percent ?>%"></i></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="quick-summary-panel-total">
                                <span>Total</span>
                                <strong><?= number_format($stageTotal) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($dashboardStats === null): ?>
            <p class="quick-summary-unavailable" role="status">Summary counts are unavailable because the database connection could not be established.</p>
        <?php endif; ?>
    </div>
</section>

<script>
// Handles opening and closing the Quick Summary program dropdowns.
(function () {
    'use strict';
    // Collects every card wrapper that has a dropdown.
    var items = Array.prototype.slice.call(document.querySelectorAll('.quick-summary-item'));

    // Opens or closes one dropdown and keeps aria-expanded in sync.
    function setOpen(item, open) {
        var btn = item.querySelector('[data-qs-toggle]');
        var panel = item.querySelector('.quick-summary-panel');
        if (!btn || !panel) return;
        panel.hidden = !open;
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        item.classList.toggle('is-open', open);
    }

    // Closes every dropdown except the one passed in.
    function closeOthers(except) {
        items.forEach(function (it) { if (it !== except) setOpen(it, false); });
    }

    items.forEach(function (item) {
        var btn = item.querySelector('[data-qs-toggle]');
        if (!btn) return;
        // Toggles this card's dropdown; only one stays open at a time.
        btn.addEventListener('click', function () {
            var willOpen = btn.getAttribute('aria-expanded') !== 'true';
            closeOthers(item);
            setOpen(item, willOpen);
        });
    });

    // Closes any open dropdown when clicking outside the cards.
    document.addEventListener('click', function (e) {
        if (!e.target.closest || !e.target.closest('.quick-summary-item')) closeOthers(null);
    });

    // Escape closes the dropdown and returns focus to its card.
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        items.forEach(function (it) {
            if (it.classList.contains('is-open')) {
                setOpen(it, false);
                var btn = it.querySelector('[data-qs-toggle]');
                if (btn) btn.focus();
            }
        });
    });
})();
</script>

<?php /* Opens the box that holds the program chart. */ ?>
<div class="dash-chart">
<?php /*     Opens PHP to call the chart helper. */ ?>
    <?php
    // Starts the student population chart with the database connection.
    renderProgramPopulationChart($pdo ?? null, [
        // Sets the small label above the chart title.
        'eyebrow'  => 'Enrollment Overview',
        // Sets the chart title.
        'title'    => 'Student Population by Program',
        // Sets the chart subtitle.
        'subtitle' => 'Active graduate students across academic programs',
        // Sets the icon used in the chart header.
        'icon'     => 'fa-chart-column',
    // Closes the chart options array and the helper call.
    ]);
    // Closes the PHP block that rendered the chart.
    ?>
<?php /* Closes the chart box. */ ?>
</div>

<?php /* Loads the shared footer scripts and closes PHP. */ ?>
<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>