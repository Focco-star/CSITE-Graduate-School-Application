<?php /* Opens the PHP block for the adviser pool page. */ ?>
<?php
// Loads shared config helpers such as url() and flash messages.
require_once __DIR__ . '/../../includes/config/config.php';
// Loads the DB class for database access.
require_once __DIR__ . '/../../includes/config/db.php';

// Checks whether a session is not running yet.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so flash messages work.
    session_start();
// Closes the session check block.
}

// Sets the browser tab title to Pool of Advisers.
$pageTitle = 'Pool of Advisers';
// Marks the page as part of the coordinator area.
$role = 'coordinator';
// Highlights the Advisers sidebar link.
$currentPage = 'advisers';
// Gets the coordinator name shown in the top bar.
$userName = currentCoordinatorName();
// Holds a load error message for the page.
$error = '';
// Starts with an empty list of advisers.
$advisers = [];

// Opens a try block to catch database errors.
try {
    // Opens the shared database connection.
    $pdo = DB::getConnection();
    // Checks for a numeric delete id in the URL before deleting.
    if (isset($_GET['delete']) && ctype_digit((string) $_GET['delete'])) {
        // Counts how many schedules already use this adviser.
        $assigned = $pdo->prepare('SELECT COUNT(*) FROM schedules WHERE adviser_id = :id');
        // Runs that count using the id from the URL.
        $assigned->execute(['id' => (int) $_GET['delete']]);
        // If any schedules use the adviser, removal is blocked.
        if ((int) $assigned->fetchColumn() > 0) {
            // Warns the coordinator to mark the adviser unavailable instead.
            setFlash('error', 'This adviser is assigned to existing schedules. Mark the adviser unavailable instead of removing them.');
            // Sends the user back to the adviser list.
            redirectTo('coordinator/advisers/manage.php');
        // Closes the block that blocks deletion of assigned advisers.
        }
        // Prepares a DELETE statement for the advisor_pool table.
        $delete = $pdo->prepare('DELETE FROM advisor_pool WHERE adviser_id = :id');
        // Deletes the adviser row with the given id.
        $delete->execute(['id' => (int) $_GET['delete']]);
        // Queues a success message about the removal.
        setFlash('success', 'Adviser removed from the pool.');
        // Reloads the adviser list page.
        redirectTo('coordinator/advisers/manage.php');
    // Closes the delete-request block.
    }
    // Checks for a POST request that asked to toggle availability.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_avail'])) {
        // Prepares the query that flips the availability value.
        $toggle = $pdo->prepare(
            // SQL that swaps available and unavailable for one adviser.
            "UPDATE advisor_pool
             SET availability = IF(availability = 'available', 'unavailable', 'available')
             WHERE adviser_id = :id"
        // Closes the SQL string and prepare call.
        );
        // Runs the toggle using the posted adviser id.
        $toggle->execute(['id' => (int) $_POST['toggle_avail']]);
        // Queues a success message for the availability change.
        setFlash('success', 'Adviser availability updated.');
        // Redirects back to the list so the table refreshes.
        redirectTo('coordinator/advisers/manage.php');
    // Closes the availability toggle block.
    }
    // Runs the main query that loads every adviser.
    $advisers = $pdo->query(
        // SQL listing advisers with a count of their scheduled sessions.
        'SELECT a.*, COUNT(s.schedule_id) AS adviser_sessions
         FROM advisor_pool a
         LEFT JOIN schedules s ON s.adviser_id = a.adviser_id
         GROUP BY a.adviser_id
         ORDER BY a.availability = "available" DESC, a.name ASC'
    // Fetches all rows into the advisers array.
    )->fetchAll();
// Catches any error thrown while loading the page data.
} catch (Throwable $e) {
    // Logs the technical error message.
    error_log('[adviser pool] ' . $e->getMessage());
    // Shows a friendly message about the adviser pool failing to load.
    $error = 'The adviser pool could not be loaded. Check that the database migrations are applied and try again.';
// Closes the catch block.
}

// Loads the shared header with sidebar.
require_once __DIR__ . '/../../includes/layout/header.php';
// Closes PHP so HTML is output.
?>

<?php /* Opens the page header box. */ ?>
<div class="page-header">
<?php /*     Shows the Pool of Advisers heading. */ ?>
    <h2>Pool of Advisers</h2>
<?php /*     Explains what can be managed on this page. */ ?>
    <p>Manage adviser qualifications, assigned presentation schedules, and availability.</p>
<?php /* Closes the page header box. */ ?>
</div>

<?php /* Only shows the error alert when an error exists. */ ?>
<?php if ($error): ?>
<?php /* Red alert box showing the error text safely escaped. */ ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php /* Closes the error-check block. */ ?>
<?php endif; ?>

<?php /* Opens a toolbar strip for the add button. */ ?>
<div class="form-actions" style="border:none;padding:0;margin-bottom:1.5rem;">
<?php /*     Button linking to the page for adding a new adviser. */ ?>
    <a href="<?= url('coordinator/advisers/add.php') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Add Adviser</a>
<?php /* Closes the toolbar strip. */ ?>
</div>

<?php /* Opens the card that holds the adviser table. */ ?>
<div class="card">
<?php /*     Card header with the title and total number of advisers. */ ?>
    <div class="card-header"><h3>Advisers <span style="font-size:0.8rem;font-weight:400;color:var(--gray-500);">(<?= count($advisers) ?>)</span></h3></div>
<?php /*     Opens the card body. */ ?>
    <div class="card-body">
<?php /*         Wrapper that lets the table scroll on small screens. */ ?>
        <div class="table-responsive">
<?php /*             Opens the data table listing advisers. */ ?>
            <table class="data-table">
<?php /*                 Opens the table head section. */ ?>
                <thead>
<?php /*                     Header row naming each column, including Adviser Sessions. */ ?>
                    <tr><th>Name</th><th>Qualification</th><th style="text-align:center;">Adviser Sessions</th><th>Availability</th><th>Actions</th></tr>
<?php /*                 Closes the table head section. */ ?>
                </thead>
<?php /*                 Opens the table body where rows are printed. */ ?>
                <tbody>
<?php /*                     Checks whether the adviser list is empty. */ ?>
                    <?php if (!$advisers): ?>
<?php /*                     Single message row shown when there are no advisers yet. */ ?>
                    <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:2rem;">No advisers in the pool yet.</td></tr>
<?php /*                     Closes the empty-list check. */ ?>
                    <?php endif; ?>
<?php /*                     Loops through each adviser to build one table row. */ ?>
                    <?php foreach ($advisers as $adviser): ?>
<?php /*                     Opens a table row for one adviser. */ ?>
                    <tr>
<?php /*                         Opens the cell holding the adviser name. */ ?>
                        <td>
<?php /*                             Prints the adviser name, escaped for safety. */ ?>
                            <strong><?= htmlspecialchars($adviser['name']) ?></strong>
<?php /*                             Checks whether this adviser has an email address. */ ?>
                            <?php if (!empty($adviser['email'])): ?>
<?php /*                             Shows the email in small grey text under the name. */ ?>
                            <div style="font-size:0.75rem;color:var(--gray-400);"><?= htmlspecialchars($adviser['email']) ?></div>
<?php /*                             Closes the email check. */ ?>
                            <?php endif; ?>
<?php /*                         Closes the name cell. */ ?>
                        </td>
<?php /*                         Prints the adviser qualification in its own cell. */ ?>
                        <td style="font-size:0.83rem;"><?= htmlspecialchars($adviser['qualification']) ?></td>
<?php /*                         Shows how many schedules this adviser is assigned to. */ ?>
                        <td style="text-align:center;font-weight:600;color:var(--adzu-navy);"><?= (int) $adviser['adviser_sessions'] ?></td>
<?php /*                         Opens the cell holding the availability toggle form. */ ?>
                        <td>
<?php /*                             Small form that posts to this same page. */ ?>
                            <form method="post">
<?php /*                                 Hidden field carrying the adviser id for the toggle. */ ?>
                                <input type="hidden" name="toggle_avail" value="<?= (int) $adviser['adviser_id'] ?>">
<?php /*                                 Submit button styled green when available, outlined when not. */ ?>
                                <button type="submit" class="btn btn-sm <?= $adviser['availability'] === 'available' ? 'btn-success' : 'btn-outline' ?>" style="font-size:0.75rem;padding:3px 10px;">
<?php /*                                     Checks whether the adviser is currently available. */ ?>
                                    <?php if ($adviser['availability'] === 'available'): ?>
<?php /*                                     Shows a check icon and the word Available. */ ?>
                                    <i class="fas fa-check-circle"></i> Available
<?php /*                                     Starts the else branch for unavailable advisers. */ ?>
                                    <?php else: ?>
<?php /*                                     Shows a ban icon and the word Unavailable. */ ?>
                                    <i class="fas fa-ban"></i> Unavailable
<?php /*                                     Closes the available/unavailable if/else. */ ?>
                                    <?php endif; ?>
<?php /*                                 Closes the toggle button. */ ?>
                                </button>
<?php /*                             Closes the availability form. */ ?>
                            </form>
<?php /*                         Closes the availability cell. */ ?>
                        </td>
<?php /*                         Opens the actions cell for edit and delete. */ ?>
                        <td class="actions">
<?php /*                             Edit link that opens this adviser's edit page with its id. */ ?>
                            <a href="<?= url('coordinator/advisers/edit.php?id=' . (int) $adviser['adviser_id']) ?>" class="btn btn-primary coord-edit-btn" title="Edit adviser"><i class="fas fa-pen-to-square" aria-hidden="true"></i> Edit</a>
<?php /*                             Delete link that asks for confirmation before removing the adviser. */ ?>
                            <?= coordDeleteLink(url('coordinator/advisers/manage.php?delete=' . (int) $adviser['adviser_id']), 'Remove this adviser from the pool?') ?>
<?php /*                         Closes the actions cell. */ ?>
                        </td>
<?php /*                     Closes the adviser table row. */ ?>
                    </tr>
<?php /*                     Ends the loop over advisers. */ ?>
                    <?php endforeach; ?>
<?php /*                 Closes the table body. */ ?>
                </tbody>
<?php /*             Closes the table element. */ ?>
            </table>
<?php /*         Closes the scrolling wrapper. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the card. */ ?>
</div>

<?php /* Loads the shared footer scripts and closes PHP. */ ?>
<?php require_once __DIR__ . '/../../includes/layout/footer.php'; ?>
