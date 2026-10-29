<?php /* Starts the PHP code for the coordinator's student report page. */ ?>
<?php
// Loads the shared config file with paths, helpers, and session setup.
require_once __DIR__ . '/../../includes/config/config.php';
// Loads the database class so this page can query MySQL.
require_once __DIR__ . '/../../includes/config/db.php';

// Sets the page title used in the browser tab and header.
$pageTitle   = 'Student Reports';
// Tells the layout that the signed-in user is a coordinator.
$role        = 'coordinator';
// Marks the 'reports' sidebar link as the active one.
$currentPage = 'reports';
// Takes the demo coordinator name for the header greeting.
$userName    = $mockCoordinator['name'];

// Starts an empty list for students still working on their paper.
$ongoing = [];
// Starts an empty list for students who already completed their paper.
$completed = [];

// Opens a try block so database errors can be caught quietly.
try {
    // Gets one shared database connection to reuse for both queries.
    $pdo = DB::getConnection();

    // Notes that the query below lists active students with their latest stage.
    // Ongoing = every active student + their latest application stage (if any),
    // Notes the list is sorted by last name then first name.
    // alphabetical by last name, then first name — same source as Students list,
// so new accounts appear here automatically.
    // Runs the SQL query that finds every active student and newest application.
    $stmt = $pdo->query(
        // Begins the SQL text that picks name, program, stage, and start month.
        "SELECT
            TRIM(CONCAT(s.last_name, ', ', s.first_name, IF(s.middle_initial IS NULL OR TRIM(REPLACE(s.middle_initial, '.', '')) = '', '', CONCAT(' ', TRIM(REPLACE(s.middle_initial, '.', '')))))) AS name,
            CASE
                WHEN s.program LIKE '%Computer Science%' THEN 'MSCS'
                WHEN s.program LIKE '%Information Technology%' THEN 'MIT'
                WHEN s.program LIKE '%Mathematics%' THEN 'MATH'
                ELSE s.program
            END AS program,
            COALESCE(latest_app.presentation_stage, 'Not Started') AS stage,
            DATE_FORMAT(s.enrollment_date, '%Y-%m') AS since
         FROM students s
         LEFT JOIN users u ON u.user_id = s.user_id
         LEFT JOIN (
             SELECT a1.*
             FROM applications a1
             INNER JOIN (
                 SELECT user_id, MAX(submitted_at) AS max_submitted
                 FROM applications
                 WHERE archived_at IS NULL
                 GROUP BY user_id
             ) a2 ON a1.user_id = a2.user_id AND a1.submitted_at = a2.max_submitted
         ) latest_app ON latest_app.user_id = s.user_id
         WHERE s.archived_at IS NULL
         ORDER BY s.last_name ASC, s.first_name ASC"
    // Closes the query call after the SQL text ends.
    );
    // Stores all ongoing student rows returned by the query.
    $ongoing = $stmt->fetchAll();

    // Notes that the next query finds students whose latest paper is completed.
    // Completed = active students whose latest completed application carries the
    // Notes that only the newest completed application per student is used.
    // final outcome (latest 'completed' application per student).
    // Runs the second SQL query for students who already finished.
    $stmt = $pdo->query(
        // Begins the SQL text selecting name, program, title, and completion month.
        "SELECT
            TRIM(CONCAT(s.last_name, ', ', s.first_name, IF(s.middle_initial IS NULL OR TRIM(REPLACE(s.middle_initial, '.', '')) = '', '', CONCAT(' ', TRIM(REPLACE(s.middle_initial, '.', '')))))) AS name,
            CASE
                WHEN s.program LIKE '%Computer Science%' THEN 'MSCS'
                WHEN s.program LIKE '%Information Technology%' THEN 'MIT'
                WHEN s.program LIKE '%Mathematics%' THEN 'MATH'
                ELSE s.program
            END AS program,
            a.paper_title AS title,
            DATE_FORMAT(a.updated_at, '%Y-%m') AS completed
         FROM students s
         INNER JOIN users u ON u.user_id = s.user_id
         INNER JOIN applications a ON a.user_id = u.user_id
            AND a.status = 'completed' AND a.archived_at IS NULL
         WHERE s.archived_at IS NULL
           AND NOT EXISTS (
               SELECT 1 FROM applications a2
               WHERE a2.user_id = a.user_id
                 AND a2.status = 'completed' AND a2.archived_at IS NULL
                 AND (a2.submitted_at > a.submitted_at
                      OR (a2.submitted_at = a.submitted_at AND a2.application_id > a.application_id))
           )
         ORDER BY s.last_name ASC, s.first_name ASC"
    // Closes the query call for the completed students query.
    );
    // Stores all completed student rows returned by the query.
    $completed = $stmt->fetchAll();
// Catches any error thrown while connecting or running the queries.
} catch (Throwable $e) {
    // Falls back to an empty ongoing list so the page still loads.
    $ongoing = [];
    // Falls back to an empty completed list so the page still loads.
    $completed = [];
// Closes the try/catch block that loaded the report data.
}

// Loads the shared header, sidebar, and top navigation.
require_once __DIR__ . '/../../includes/layout/header.php';
// Closes PHP so the HTML below is printed to the browser.
?>

<?php /* Opens the page header area with the report title. */ ?>
<div class="page-header">
<?php /*     Shows the big heading 'Student Reports'. */ ?>
    <h2>Student Reports</h2>
<?php /*     Explains that these are semester reports of student progress. */ ?>
    <p>Semester-based reports of students by capstone/thesis progress.</p>
<?php /* Closes the page header area. */ ?>
</div>

<?php /* Opens a card box that holds the filter controls. */ ?>
<div class="card">
<?php /*     Opens the card header row for the filter card. */ ?>
    <div class="card-header">
<?php /*         Shows the small heading 'Filter'. */ ?>
        <h3>Filter</h3>
<?php /*         Starts a flex row that keeps the select and button side by side. */ ?>
        <div style="display:flex;gap:0.5rem;">
<?php /*             Shows a dropdown for choosing the semester to view. */ ?>
            <select style="padding:0.4rem 0.75rem;border:1px solid var(--gray-300);border-radius:var(--radius);font-size:0.85rem;">
<?php /*                 First semester option for school year 2024-2025. */ ?>
                <option>1st Semester 2024–2025</option>
<?php /*                 Second semester option for school year 2024-2025. */ ?>
                <option>2nd Semester 2024–2025</option>
<?php /*                 First semester option for school year 2025-2026. */ ?>
                <option>1st Semester 2025–2026</option>
<?php /*             Closes the semester dropdown. */ ?>
            </select>
<?php /*             Shows an Export button with a download icon. */ ?>
            <button class="btn btn-sm btn-outline"><i class="fas fa-download"></i> Export</button>
<?php /*         Closes the flex row holding the dropdown and button. */ ?>
        </div>
<?php /*     Closes the card header row. */ ?>
    </div>
<?php /* Closes the filter card box. */ ?>
</div>

<?php /* Opens the card that lists ongoing students. */ ?>
<div class="card">
<?php /*     Card header showing 'Ongoing Students' and the row count. */ ?>
    <div class="card-header"><h3>Ongoing Students (<?= count($ongoing) ?>)</h3></div>
<?php /*     Opens the card body that wraps the table. */ ?>
    <div class="card-body">
<?php /*         Wraps the table so it can scroll sideways on small screens. */ ?>
        <div class="table-responsive">
<?php /*             Starts the data table listing ongoing students. */ ?>
            <table class="data-table">
<?php /*                 Header row with Student, Program, Current Stage, and Since columns. */ ?>
                <thead><tr><th>Student</th><th>Program</th><th>Current Stage</th><th>Since</th></tr></thead>
<?php /*                 Starts the table body for the ongoing student rows. */ ?>
                <tbody>
<?php /*                     Loops over each ongoing student row from the query. */ ?>
                    <?php foreach ($ongoing as $s): ?>
<?php /*                     Starts one table row for this student. */ ?>
                    <tr>
<?php /*                         Shows the student's full name in bold, escaped for safety. */ ?>
                        <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
<?php /*                         Shows the program code such as MSCS or MIT. */ ?>
                        <td><?= htmlspecialchars($s['program']) ?></td>
<?php /*                         Shows the current presentation stage, or 'Not Started'. */ ?>
                        <td><?= htmlspecialchars($s['stage']) ?></td>
<?php /*                         Shows the year and month the student enrolled. */ ?>
                        <td><?= htmlspecialchars($s['since']) ?></td>
<?php /*                     Ends the table row for this student. */ ?>
                    </tr>
<?php /*                     Ends the loop over ongoing students. */ ?>
                    <?php endforeach; ?>
<?php /*                     Checks whether the ongoing list is empty. */ ?>
                    <?php if (!$ongoing): ?>
<?php /*                     Shows one wide row saying no ongoing students were found. */ ?>
                    <tr><td colspan="4" style="text-align:center;color:var(--gray-400);padding:1.5rem;">No ongoing students found.</td></tr>
<?php /*                     Ends the empty-list check. */ ?>
                    <?php endif; ?>
<?php /*                 Closes the table body. */ ?>
                </tbody>
<?php /*             Closes the ongoing students table. */ ?>
            </table>
<?php /*         Closes the scrolling table wrapper. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the ongoing students card. */ ?>
</div>

<?php /* Opens the card that lists completed students. */ ?>
<div class="card">
<?php /*     Card header showing 'Completed Students' and the row count. */ ?>
    <div class="card-header"><h3>Completed Students (<?= count($completed) ?>)</h3></div>
<?php /*     Opens the card body that wraps the second table. */ ?>
    <div class="card-body">
<?php /*         Wraps the table so it scrolls sideways on small screens. */ ?>
        <div class="table-responsive">
<?php /*             Starts the data table listing completed students. */ ?>
            <table class="data-table">
<?php /*                 Header row with Student, Program, Title, and Completed columns. */ ?>
                <thead><tr><th>Student</th><th>Program</th><th>Title</th><th>Completed</th></tr></thead>
<?php /*                 Starts the table body for completed student rows. */ ?>
                <tbody>
<?php /*                     Loops over each completed student row from the query. */ ?>
                    <?php foreach ($completed as $s): ?>
<?php /*                     Starts one table row for this completed student. */ ?>
                    <tr>
<?php /*                         Shows the student's full name in bold, escaped for safety. */ ?>
                        <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
<?php /*                         Shows the program code such as MSCS or MIT. */ ?>
                        <td><?= htmlspecialchars($s['program']) ?></td>
<?php /*                         Shows the title of the finished research paper. */ ?>
                        <td><?= htmlspecialchars($s['title']) ?></td>
<?php /*                         Shows the year and month the paper was marked completed. */ ?>
                        <td><?= htmlspecialchars($s['completed']) ?></td>
<?php /*                     Ends the table row for this student. */ ?>
                    </tr>
<?php /*                     Ends the loop over completed students. */ ?>
                    <?php endforeach; ?>
<?php /*                     Checks whether the completed list is empty. */ ?>
                    <?php if (!$completed): ?>
<?php /*                     Shows one wide row saying no completed students exist yet. */ ?>
                    <tr><td colspan="4" style="text-align:center;color:var(--gray-400);padding:1.5rem;">No completed students yet.</td></tr>
<?php /*                     Ends the empty-list check. */ ?>
                    <?php endif; ?>
<?php /*                 Closes the table body. */ ?>
                </tbody>
<?php /*             Closes the completed students table. */ ?>
            </table>
<?php /*         Closes the scrolling table wrapper. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the completed students card. */ ?>
</div>

<?php /* Opens the footer area that holds the back button. */ ?>
<div class="form-actions">
<?php /*     Link back to the reports dashboard. */ ?>
    <a href="<?= url('coordinator/reports/dashboard.php') ?>" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Reports</a>
<?php /* Closes the footer action area. */ ?>
</div>

<?php /* Loads the shared footer and closes the HTML page. */ ?>
<?php require_once __DIR__ . '/../../includes/layout/footer.php'; ?>
