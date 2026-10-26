<?php /* Opens the PHP block for the application details page. */ ?>
<?php
// Loads the shared config with helpers and demo data.
require_once __DIR__ . '/../../includes/config.php';
// Loads the DB class for database access.
require_once __DIR__ . '/../../includes/db.php';

// Checks whether a session is not running yet.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so login details can be read.
    session_start();
// Closes the session check block.
}

// Reads the logged-in user array from the session, or an empty array.
$sessionUser = $_SESSION['user'] ?? [];
// Chooses the top bar name from the session user or the demo coordinator.
$userName    = !empty($sessionUser['full_name']) ? $sessionUser['full_name'] : ($mockCoordinator['name'] ?? 'Coordinator');
// Sets the browser tab title to Application Details.
$pageTitle   = 'Application Details';
// Marks the page as part of the coordinator area.
$role        = 'coordinator';
// Highlights the Applications menu in the sidebar.
$currentPage = 'applications';


// Checks that no working database connection exists yet.
if (!isset($pdo) || !($pdo instanceof PDO)) {
    // Opens a try block to build the connection safely.
    try {
        // Reads the DB host constant, defaulting to localhost.
        $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
        // Reads the DB user constant, defaulting to root.
        $dbUser = defined('DB_USER') ? DB_USER : 'root';
        // Reads the DB password constant, defaulting to empty.
        $dbPass = defined('DB_PASS') ? DB_PASS : '';
        // Reads the DB name constant, defaulting to csite_grad_school.
        $dbName = defined('DB_NAME') ? DB_NAME : 'csite_grad_school';
        // Creates the PDO connection to MySQL using UTF-8 encoding.
        $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
            // Makes PDO throw exceptions when a query fails.
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
// Closes the block that only runs when no connection existed.
}

// Reads the application id from the URL as a number.
$appId = (int) ($_GET['id'] ?? 0);
// Starts with no application loaded.
$app = null;

// Only queries when a connection exists and the id is valid.
if ($pdo && $appId > 0) {
    // Opens a try block for the application query.
    try {
        // SQL joining applications, users and students to build one detail row.
        $stmt = $pdo->prepare("
            SELECT 
                a.application_id AS id,
                COALESCE(
                    NULLIF(TRIM(CONCAT(s.last_name, ', ', s.first_name, IF(s.middle_initial IS NULL OR TRIM(REPLACE(s.middle_initial, '.', '')) = '', '', CONCAT(' ', TRIM(REPLACE(s.middle_initial, '.', '')))))), ''), 
                    u.full_name
                ) AS student,
                u.email AS studentEmail,
                COALESCE(s.program, 'Graduate Program') AS program,
                s.adviser_name AS adviser,
                a.presentation_stage AS stage,
                a.paper_title AS title,
                a.submitted_at AS date,
                a.status
            FROM applications a
            JOIN users u ON u.user_id = a.user_id
            LEFT JOIN students s ON s.user_id = u.user_id
            WHERE a.application_id = :id
            LIMIT 1
        ");
        // Runs the query with the application id.
        $stmt->execute([':id' => $appId]);
        // Fetches the single application row.
        $app = $stmt->fetch();
    // Catches a failed query.
    } catch (PDOException $e) {
        // Clears the application so the page shows a warning.
        $app = null;
    // Closes the query catch block.
    }
// Closes the database lookup block.
}


// Falls back to demo data when no database row was found.
if (!$app && function_exists('findApplication')) {
    // Loads the demo application by id or the student's latest one.
    $app = findApplication($appId) ?? latestApplicationForEmail($mockStudent['email']);
    // Checks whether still nothing was found and demo helpers exist.
    if (!$app && function_exists('storeGet')) {
        // Uses the first demo application as a last resort.
        $app = storeGet('applications')[0] ?? null;
    // Closes the demo fallback check.
    }
// Closes the demo data fallback block.
}




// Loads the uploaded documents for this application, or an empty list.
$uploads = ($app && function_exists('uploadsForApplication')) ? uploadsForApplication((int) $app['id']) : [];
// Looks up the student profile row using the application's email.
$student = ($app && function_exists('findStudentByEmail') && isset($app['studentEmail'])) ? findStudentByEmail($app['studentEmail']) : null;

// Loads the shared header with sidebar.
require_once __DIR__ . '/../../includes/header.php';
// Closes PHP so the HTML below is printed.
?>

<?php /* Opens the page banner. */ ?>
<div class="page-header page-banner">
<?php /*     Shows the Application Details heading. */ ?>
    <h2>Application Details</h2>
<?php /*     Explains that this page shows the submission for review. */ ?>
    <p>Review submitted information and documents for this application.</p>
<?php /* Closes the page banner. */ ?>
</div>

<?php /* Checks whether no application could be loaded. */ ?>
<?php if (!$app): ?>
<?php /* Warning box telling the coordinator nothing is selected. */ ?>
<div class="alert alert-warning"><i class="fas fa-info-circle"></i> No application selected.</div>
<?php /* Starts the else branch that shows the application. */ ?>
<?php else: ?>
<?php /* Opens the student information card. */ ?>
<div class="card">
<?php /*     Opens the card header. */ ?>
    <div class="card-header">
<?php /*         Title of the student information card. */ ?>
        <h3>Student Information</h3>
<?php /*         Shows a coloured badge for the application status. */ ?>
        <?= statusBadge($app['status']) ?>
<?php /*     Closes the card header. */ ?>
    </div>
<?php /*     Opens the card body. */ ?>
    <div class="card-body">
<?php /*         Opens the grid of student details. */ ?>
        <div class="detail-grid">
<?php /*             Shows the student's full name. */ ?>
            <div class="detail-item"><label>Student Name</label><span><?= htmlspecialchars($app['student']) ?></span></div>
<?php /*             Shows the program's full name using the PROGRAMS lookup. */ ?>
            <div class="detail-item"><label>Program</label><span><?= htmlspecialchars(PROGRAMS[$app['program']] ?? $app['program']) ?></span></div>
<?php /*             Shows the student email or a dash when missing. */ ?>
            <div class="detail-item"><label>Email</label><span><?= htmlspecialchars($app['studentEmail'] ?? '—') ?></span></div>
<?php /*             Shows the adviser from the application or the student profile. */ ?>
            <div class="detail-item"><label>Adviser</label><span><?= htmlspecialchars(!empty($app['adviser']) ? $app['adviser'] : ($student['adviser'] ?? '—')) ?></span></div>
<?php /*             Shows the current presentation stage. */ ?>
            <div class="detail-item"><label>Presentation Stage</label><span><?= htmlspecialchars($app['stage']) ?></span></div>
<?php /*             Shows the submission date formatted as month day, year. */ ?>
            <div class="detail-item"><label>Application Date</label><span><?= date('F d, Y', strtotime($app['date'])) ?></span></div>
<?php /*             Shows the research title across the whole row. */ ?>
            <div class="detail-item" style="grid-column:1/-1;"><label>Research Title</label><span><?= htmlspecialchars($app['title']) ?></span></div>
<?php /*             Only shows the coordinator comment when one was saved. */ ?>
            <?php if (!empty($app['coordinatorComment'])): ?>
<?php /*             Shows the coordinator's comment across the whole row. */ ?>
            <div class="detail-item" style="grid-column:1/-1;"><label>Coordinator Comment</label><span><?= htmlspecialchars($app['coordinatorComment']) ?></span></div>
<?php /*             Closes the coordinator comment check. */ ?>
            <?php endif; ?>
<?php /*         Closes the details grid. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the student information card. */ ?>
</div>

<?php /* Opens the uploaded documents card. */ ?>
<div class="card">
<?php /*     Header naming the uploaded documents card. */ ?>
    <div class="card-header"><h3>Uploaded Documents</h3></div>
<?php /*     Opens the documents card body. */ ?>
    <div class="card-body">
<?php /*         Wrapper allowing the documents table to scroll. */ ?>
        <div class="table-responsive">
<?php /*             Opens the documents table. */ ?>
            <table class="data-table">
<?php /*                 Opens the table head. */ ?>
                <thead>
<?php /*                     Header row naming the five document columns. */ ?>
                    <tr><th>Document</th><th>Type</th><th>Upload Date</th><th>Status</th><th>Action</th></tr>
<?php /*                 Closes the table head. */ ?>
                </thead>
<?php /*                 Opens the table body. */ ?>
                <tbody>
<?php /*                     Checks whether this application has no uploads. */ ?>
                    <?php if (!$uploads): ?>
<?php /*                     Message row shown when there are no documents. */ ?>
                    <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:1.5rem;">No documents uploaded for this application.</td></tr>
<?php /*                     Closes the empty-uploads check. */ ?>
                    <?php endif; ?>
<?php /*                     Loops over each uploaded document. */ ?>
                    <?php foreach ($uploads as $doc): ?>
<?php /*                     Opens a table row for one document. */ ?>
                    <tr>
<?php /*                         Shows the stored file name. */ ?>
                        <td><?= htmlspecialchars($doc['fileName']) ?></td>
<?php /*                         Shows the document type, such as proposal or endorsement. */ ?>
                        <td><?= htmlspecialchars($doc['docType']) ?></td>
<?php /*                         Shows the upload date shortened to month day, year. */ ?>
                        <td><?= date('M d, Y', strtotime($doc['date'])) ?></td>
<?php /*                         Shows a status badge for the document. */ ?>
                        <td><?= statusBadge($doc['status']) ?></td>
<?php /*                         Opens the action cell for the document links. */ ?>
                        <td>
<?php /*                             Only shows links when the file really exists in storage. */ ?>
                            <?php if (function_exists('uploadStoragePath') && uploadStoragePath($doc)): ?>
<?php /*                                 View link opening the document in a new tab. */ ?>
                                <a class="btn btn-sm btn-outline" href="<?= url('coordinator/view_document.php?id=' . urlencode($doc['id']) . '&mode=view') ?>" target="_blank" style="margin-right:0.25rem;"><i class="fas fa-eye"></i> View</a>
<?php /*                                 Download link that saves the document file. */ ?>
                                <a class="btn btn-sm btn-outline" href="<?= url('coordinator/download.php?id=' . urlencode($doc['id'])) ?>"><i class="fas fa-download"></i> Download</a>
<?php /*                             Starts the else branch for missing files. */ ?>
                            <?php else: ?>
<?php /*                                 Grey text saying the file is unavailable. */ ?>
                                <span style="color:var(--gray-400);font-size:.8rem;">Unavailable</span>
<?php /*                             Closes the missing-file else branch. */ ?>
                            <?php endif; ?>
<?php /*                         Closes the action cell. */ ?>
                        </td>
<?php /*                     Closes the document table row. */ ?>
                    </tr>
<?php /*                     Ends the uploads loop. */ ?>
                    <?php endforeach; ?>
<?php /*                 Closes the table body. */ ?>
                </tbody>
<?php /*             Closes the documents table. */ ?>
            </table>
<?php /*         Closes the scrolling wrapper. */ ?>
        </div>
<?php /*     Closes the documents card body. */ ?>
    </div>
<?php /* Closes the documents card. */ ?>
</div>

<?php /* Opens the button row, hidden when printing. */ ?>
<div class="form-actions no-print">
<?php /*     Button that opens the processing page for this application. */ ?>
    <a href="<?= url('coordinator/applications/process.php?id=' . $app['id']) ?>" class="btn btn-primary"><i class="fas fa-cog"></i> Process Application</a>
<?php /*     Link that returns to the applications list. */ ?>
    <a href="<?= url('coordinator/applications/manage.php') ?>" class="btn btn-outline">Back to List</a>
<?php /* Closes the button row. */ ?>
</div>
<?php /* Closes the if/else that chose details or the warning. */ ?>
<?php endif; ?>

<?php /* Loads the shared footer scripts and closes PHP. */ ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
