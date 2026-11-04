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

// Loads summary counts when the database connection is available.
$dashboardStats = null;
if ($pdo instanceof PDO) {
    $statsStatement = $pdo->query(
        "SELECT
            (SELECT COUNT(*)
             FROM applications
             WHERE archived_at IS NULL) AS active_applications,
            (SELECT COUNT(*)
             FROM applications
             WHERE archived_at IS NULL AND status IN ('submitted', 'under_review')) AS awaiting_review,
            (SELECT COUNT(*)
             FROM schedules
             WHERE presentation_date >= CURDATE()
               AND status IN ('pending', 'confirmed')) AS upcoming_presentations"
    );
    $dashboardStats = $statsStatement->fetch(PDO::FETCH_ASSOC) ?: null;
}

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
                <p>Current graduate school activity at a glance</p>
            </div>
        </div>
        <span class="quick-summary-year"><i class="fas fa-calendar-days" aria-hidden="true"></i> Academic Year <?= htmlspecialchars($academicYearLabel, ENT_QUOTES, 'UTF-8') ?></span>
    </header>
    <div class="quick-summary-body">
        <div class="quick-summary-grid quick-summary-grid--three">
            <a class="quick-summary-stat quick-summary-stat--navy" href="<?= url('coordinator/applications/manage.php') ?>">
                <span class="quick-summary-stat-head">
                    <span class="quick-summary-icon" aria-hidden="true"><i class="fas fa-folder-open"></i></span>
                    <span class="quick-summary-arrow" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
                </span>
                <strong class="quick-summary-value"><?= $dashboardStats === null ? '&mdash;' : number_format((int) $dashboardStats['active_applications']) ?></strong>
                <span class="quick-summary-label">Active Applications</span>
            </a>
            <a class="quick-summary-stat quick-summary-stat--gold" href="<?= url('coordinator/applications/manage.php') ?>">
                <span class="quick-summary-stat-head">
                    <span class="quick-summary-icon" aria-hidden="true"><i class="fas fa-hourglass-half"></i></span>
                    <span class="quick-summary-arrow" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
                </span>
                <strong class="quick-summary-value"><?= $dashboardStats === null ? '&mdash;' : number_format((int) $dashboardStats['awaiting_review']) ?></strong>
                <span class="quick-summary-label">Awaiting Review</span>
            </a>
            <a class="quick-summary-stat quick-summary-stat--blue" href="<?= url('coordinator/schedule/manage.php') ?>">
                <span class="quick-summary-stat-head">
                    <span class="quick-summary-icon" aria-hidden="true"><i class="fas fa-calendar-check"></i></span>
                    <span class="quick-summary-arrow" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
                </span>
                <strong class="quick-summary-value"><?= $dashboardStats === null ? '&mdash;' : number_format((int) $dashboardStats['upcoming_presentations']) ?></strong>
                <span class="quick-summary-label">Upcoming Presentations</span>
            </a>
        </div>
        <?php if ($dashboardStats === null): ?>
            <p class="quick-summary-unavailable" role="status">Summary counts are unavailable because the database connection could not be established.</p>
        <?php endif; ?>
    </div>
</section>

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