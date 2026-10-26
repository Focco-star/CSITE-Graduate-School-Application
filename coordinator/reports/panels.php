<?php /* Opens the PHP block that builds the panel participation report. */ ?>
<?php
// Loads shared settings and helper functions such as url().
require_once __DIR__ . '/../../includes/config.php';
// Loads the database connection and report helper functions.
require_once __DIR__ . '/../../includes/db.php';

// Sets the browser page title to Panel Participation Reports.
$pageTitle   = 'Panel Participation Reports';
// Tells the header layout to use the coordinator menu.
$role        = 'coordinator';
// Highlights the Reports item in the sidebar.
$currentPage = 'reports';
// Gets the coordinator's display name for the top bar.
$userName    = $mockCoordinator['name'];

// Loads the panel participation numbers from the database.
$panelReport = databasePanelParticipation();

// Includes the shared header with menu and styles.
require_once __DIR__ . '/../../includes/header.php';
// Closes PHP and switches to HTML output.
?>

<?php /* Opens the page header wrapper for the title block. */ ?>
<div class="page-header">
<?php /*     Shows the main heading Panel Participation Reports. */ ?>
    <h2>Panel Participation Reports</h2>
<?php /*     Explains that the page summarises panel assignments and service. */ ?>
    <p>Summary of panel member assignments and service frequency for capstone/thesis presentations.</p>
<?php /* Closes the page header wrapper. */ ?>
</div>

<?php /* Opens the card that holds the participation table. */ ?>
<div class="card">
<?php /*     Opens the card header row. */ ?>
    <div class="card-header">
<?php /*         Shows the heading Panel Participation Summary. */ ?>
        <h3>Panel Participation Summary</h3>
<?php /*         Button that would export the report as a file. */ ?>
        <button class="btn btn-sm btn-outline"><i class="fas fa-download"></i> Export Report</button>
<?php /*     Closes the card header row. */ ?>
    </div>
<?php /*     Opens the card body around the table. */ ?>
    <div class="card-body">
<?php /*         Wrapper that lets the table scroll sideways on small screens. */ ?>
        <div class="table-responsive">
<?php /*             Starts the data table of panel participation. */ ?>
            <table class="data-table">
<?php /*                 Opens the table header section. */ ?>
                <thead>
<?php /*                     Header row naming each column: member, qualification, times, last assignment. */ ?>
                    <tr><th>Panel Member</th><th>Qualification</th><th>Times Served</th><th>Last Assignment</th></tr>
<?php /*                 Closes the table header section. */ ?>
                </thead>
<?php /*                 Opens the table body where one row per member is printed. */ ?>
                <tbody>
<?php /*                     Loops over each panel member summary row. */ ?>
                    <?php foreach ($panelReport as $p): ?>
<?php /*                     Opens one table row for a panel member. */ ?>
                    <tr>
<?php /*                         Prints the member's name in bold. */ ?>
                        <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
<?php /*                         Prints the member's qualification. */ ?>
                        <td><?= htmlspecialchars($p['qualification']) ?></td>
<?php /*                         Prints how many times the member has served on a panel. */ ?>
                        <td><?= (int) $p['times'] ?></td>
<?php /*                         Shows the last assignment date, or a dash when there is none. */ ?>
                        <td><?= !empty($p['last_assignment']) ? date('M d, Y', strtotime($p['last_assignment'])) : '—' ?></td>
<?php /*                     Closes this member's table row. */ ?>
                    </tr>
<?php /*                     Ends the loop over the participation rows. */ ?>
                    <?php endforeach; ?>
<?php /*                 Closes the table body. */ ?>
                </tbody>
<?php /*             Closes the participation table. */ ?>
            </table>
<?php /*         Closes the responsive table wrapper. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the participation table card. */ ?>
</div>

<?php /* Opens a second card for the participation overview. */ ?>
<div class="card">
<?php /*     Card header showing the heading Participation Overview. */ ?>
    <div class="card-header"><h3>Participation Overview</h3></div>
<?php /*     Opens the card body for the overview stats. */ ?>
    <div class="card-body">
<?php /*         Starts the grid layout for the overview cards. */ ?>
        <div class="stats-grid">
<?php /*             Loops over the first four members to show a stat card each. */ ?>
            <?php foreach (array_slice($panelReport, 0, 4) as $p): ?>
<?php /*             Opens one stat card for a panel member. */ ?>
            <div class="stat-card">
<?php /*                 Shows a blue person icon on the stat card. */ ?>
                <div class="stat-icon blue"><i class="fas fa-user"></i></div>
<?php /*                 Prints how many times this member served. */ ?>
                <div class="stat-value"><?= (int) $p['times'] ?></div>
<?php /*                 Shows the member's second name word, or the full name if missing. */ ?>
                <div class="stat-label"><?= htmlspecialchars(explode(' ', $p['name'])[1] ?? $p['name']) ?></div>
<?php /*             Closes the stat card. */ ?>
            </div>
<?php /*             Ends the loop over the first four members. */ ?>
            <?php endforeach; ?>
<?php /*         Closes the stats grid layout. */ ?>
        </div>
<?php /*         Opens a small centred note paragraph. */ ?>
        <p style="font-size:0.8rem;color:var(--gray-400);margin-top:1rem;text-align:center;">
<?php /*             Explains that detailed charts may be added later. */ ?>
            Detailed charts can be added here in a future iteration. Summary counts are shown above.
<?php /*         Closes the note paragraph. */ ?>
        </p>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the overview card. */ ?>
</div>

<?php /* Opens the row holding the back button. */ ?>
<div class="form-actions">
<?php /*     Link that returns to the reports dashboard. */ ?>
    <a href="<?= url('coordinator/reports/dashboard.php') ?>" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Reports</a>
<?php /* Closes the back button row. */ ?>
</div>

<?php /* Includes the shared footer that closes the page layout. */ ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
