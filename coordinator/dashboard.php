<?php /* Opens the PHP block for the coordinator dashboard. */ ?>
<?php
// Loads the shared config with helpers and demo data.
require_once __DIR__ . '/../includes/config/config.php';
// Loads the DB class for database access.
require_once __DIR__ . '/../includes/config/db.php';
// Loads the helper that draws the program population chart.
require_once __DIR__ . '/../includes/components/program_chart.php';
// Loads the helper that renders the schedule calendar.
require_once __DIR__ . '/../includes/components/calendar.php';

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

// Loads the shared header with sidebar.
require_once __DIR__ . '/../includes/layout/header.php';
// Closes PHP so the HTML below is printed.
?>

<?php /* Opens the welcome hero section of the dashboard. */ ?>
<section class="dash-hero" aria-labelledby="dashHeroTitle">
<?php /*     Opens the main area of the hero section. */ ?>
    <div class="dash-hero-main">
<?php /*         Shows a university icon in the hero badge. */ ?>
        <span class="dash-hero-mark" aria-hidden="true"><i class="fas fa-university"></i></span>
<?php /*         Opens the text block inside the hero. */ ?>
        <div class="dash-hero-copy">
<?php /*             Small label naming the CSITE Graduate School. */ ?>
            <span class="dash-hero-eyebrow"><i class="fas fa-graduation-cap" aria-hidden="true"></i> CSITE Graduate School</span>
<?php /*             Big welcome heading with the coordinator's name. */ ?>
            <h2 class="dash-hero-title" id="dashHeroTitle">Welcome Back, Coordinator Precious Opinion</h2>
<?php /*             Subtitle showing the coordinator role and university. */ ?>
            <p class="dash-hero-subtitle">Graduate Program Coordinator <span class="dash-hero-dot" aria-hidden="true">&middot;</span> Ateneo de Zamboanga University</p>
<?php /*         Closes the hero text block. */ ?>
        </div>
<?php /*     Closes the hero main area. */ ?>
    </div>
<?php /*     Opens the side area of the hero. */ ?>
    <div class="dash-hero-aside">
<?php /*         Shows the current academic year with a calendar icon. */ ?>
        <span class="dash-hero-ay"><i class="fas fa-calendar-days" aria-hidden="true"></i> Academic Year <?= htmlspecialchars($academicYearLabel) ?></span>
<?php /*     Closes the hero side area. */ ?>
    </div>
<?php /* Closes the hero section. */ ?>
</section>

<?php /* Draws the schedule calendar for the coordinator. */ ?>
<?php renderScheduleCalendar(); ?>

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