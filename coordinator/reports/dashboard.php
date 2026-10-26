<?php /* Opens the PHP block that builds the reports dashboard page. */ ?>
<?php
// Loads shared settings and helper functions.
require_once __DIR__ . '/../../includes/config.php';
// Loads the database connection code used by the page.
require_once __DIR__ . '/../../includes/db.php';
// Loads the helper that draws the program population chart.
require_once __DIR__ . '/../../includes/program_chart.php';

// Sets the browser page title to Reports.
$pageTitle   = 'Reports';
// Tells the header layout to use the coordinator menu.
$role        = 'coordinator';
// Highlights the Reports item in the sidebar.
$currentPage = 'reports';
// Gets the coordinator's display name, defaulting to Coordinator.
$userName    = $mockCoordinator['name'] ?? 'Coordinator';

// Loads the students list from storage helpers, or an empty array.
$students = function_exists('storeGet') ? (array) (storeGet('students') ?? []) : [];
// Counts students whose status is still ongoing with a filter callback.
$ongoing  = count(array_filter($students, static function ($s) {
    // Treats completed and approved statuses as finished, all else as ongoing.
    return !in_array($s['status'] ?? '', ['completed', 'approved'], true);
// Closes the filter callback and the counting call.
}));
// Works out completed students by subtracting ongoing from the total.
$completed = count($students) - $ongoing;

// Includes the shared header with menu and styles.
require_once __DIR__ . '/../../includes/header.php';
// Closes PHP and switches to HTML output.
?>

<?php /* Opens the page header wrapper for the title block. */ ?>
<div class="page-header">
<?php /*     Shows the main heading Reports Dashboard. */ ?>
    <h2>Reports Dashboard</h2>
<?php /*     Explains that this page holds the detailed report statistics. */ ?>
    <p>Generate and review official system reports. Detailed statistics are kept on this page to keep the main dashboard concise.</p>
<?php /* Closes the page header wrapper. */ ?>
</div>

<?php /* Opens the summary panel that holds the quick statistics. */ ?>
<div class="report-summary">
<?php /*     Opens the summary header row. */ ?>
    <div class="report-summary-header">
<?php /*         Shows the heading Quick Summary. */ ?>
        <h3>Quick Summary</h3>
<?php /*         Shows a small label for the current academic year. */ ?>
        <span>Current academic year</span>
<?php /*     Closes the summary header row. */ ?>
    </div>
<?php /*     Opens the summary body holding the stat cards. */ ?>
    <div class="report-summary-body">
<?php /*         Starts the grid layout for the four stat cards. */ ?>
        <div class="report-stat-grid">
<?php /*             Opens the card for ongoing students. */ ?>
            <div class="report-stat">
<?php /*                 Shows a spinner icon in the ongoing card. */ ?>
                <div class="report-stat-icon"><i class="fas fa-spinner"></i></div>
<?php /*                 Opens the text container for the number and label. */ ?>
                <div>
<?php /*                     Prints the number of students still working on their program. */ ?>
                    <div class="report-stat-value"><?= $ongoing ?></div>
<?php /*                     Labels the number as Ongoing Students. */ ?>
                    <div class="report-stat-label">Ongoing Students</div>
<?php /*                 Closes the text container. */ ?>
                </div>
<?php /*             Closes the ongoing students card. */ ?>
            </div>
<?php /*             Opens the card for completed students. */ ?>
            <div class="report-stat">
<?php /*                 Shows a check icon in the completed card. */ ?>
                <div class="report-stat-icon"><i class="fas fa-check-circle"></i></div>
<?php /*                 Opens the text container for the number and label. */ ?>
                <div>
<?php /*                     Prints the number of students who finished this year. */ ?>
                    <div class="report-stat-value"><?= $completed ?></div>
<?php /*                     Labels the number as Completed This Year. */ ?>
                    <div class="report-stat-label">Completed This Year</div>
<?php /*                 Closes the text container. */ ?>
                </div>
<?php /*             Closes the completed students card. */ ?>
            </div>
<?php /*             Opens the card for active panel members. */ ?>
            <div class="report-stat">
<?php /*                 Shows a teacher icon in the panel card. */ ?>
                <div class="report-stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
<?php /*                 Opens the text container for the number and label. */ ?>
                <div>
<?php /*                     Prints a fixed count of five active panel members. */ ?>
                    <div class="report-stat-value">5</div>
<?php /*                     Labels the number as Active Panel Members. */ ?>
                    <div class="report-stat-label">Active Panel Members</div>
<?php /*                 Closes the text container. */ ?>
                </div>
<?php /*             Closes the panel members card. */ ?>
            </div>
<?php /*             Opens the card for presentations this school year. */ ?>
            <div class="report-stat">
<?php /*                 Shows a board icon in the presentations card. */ ?>
                <div class="report-stat-icon"><i class="fas fa-chalkboard"></i></div>
<?php /*                 Opens the text container for the number and label. */ ?>
                <div>
<?php /*                     Prints a fixed count of twenty-four presentations. */ ?>
                    <div class="report-stat-value">24</div>
<?php /*                     Labels the number with the school year 2026-2027. */ ?>
                    <div class="report-stat-label">Presentations, SY 2026-2027</div>
<?php /*                 Closes the text container. */ ?>
                </div>
<?php /*             Closes the presentations card. */ ?>
            </div>
<?php /*         Closes the stat grid layout. */ ?>
        </div>
<?php /*     Closes the summary body. */ ?>
    </div>
<?php /* Closes the summary panel. */ ?>
</div>

<?php /* Draws the program population chart using the database connection. */ ?>
<?php renderProgramPopulationChart($pdo ?? null); ?>

<?php /* Includes the shared footer that closes the page layout. */ ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>