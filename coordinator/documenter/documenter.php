<?php /* Opens the PHP block for the documenter pool page. */ ?>
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

// Sets the browser tab title to Documenter Pool.
$pageTitle = 'Documenter Pool';
// Marks the page as part of the coordinator area.
$role = 'coordinator';
// Highlights the Documenters sidebar link.
$currentPage = 'documenters';
// Gets the coordinator name for the top bar.
$userName = currentCoordinatorName();
// Holds a load error message for the page.
$error = '';
// Starts with an empty list of documenters.
$documenters = [];

// Opens a try block to catch database errors.
try {
    // Opens the shared database connection.
    $pdo = DB::getConnection();
    // Checks for a numeric delete id in the URL.
    if (isset($_GET['delete']) && ctype_digit((string) $_GET['delete'])) {
        // Prepares a DELETE statement for the documenter_pool table.
        $delete = $pdo->prepare('DELETE FROM documenter_pool WHERE documenter_id = :id');
        // Deletes the documenter row with the given id.
        $delete->execute(['id' => (int) $_GET['delete']]);
        // Queues a success message about the removal.
        setFlash('success', 'Documenter removed from the pool.');
        // Reloads the documenter list page.
        redirectTo('coordinator/documenter/documenter.php');
    // Closes the delete-request block.
    }
    // Checks for a POST request that asked to toggle availability.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_avail'])) {
        // Prepares the query that flips the availability value.
        $toggle = $pdo->prepare(
            // SQL that swaps available and unavailable for one documenter.
            "UPDATE documenter_pool
             SET availability = IF(availability = 'available', 'unavailable', 'available')
             WHERE documenter_id = :id"
        // Closes the SQL string and prepare call.
        );
        // Runs the toggle using the posted documenter id.
        $toggle->execute(['id' => (int) $_POST['toggle_avail']]);
        // Queues a success message for the availability change.
        setFlash('success', 'Documenter availability updated.');
        // Redirects back to the list so the table refreshes.
        redirectTo('coordinator/documenter/documenter.php');
    // Closes the availability toggle block.
    }
    // Runs the main query that loads every documenter.
    $documenters = $pdo->query(
        // SQL listing documenters with a count of matching scheduled sessions.
        "SELECT d.*,
                (SELECT COUNT(*) FROM schedules s
                 WHERE s.documentor_last_name = d.last_name
                   AND s.documentor_first_name = d.first_name
                   AND COALESCE(s.documentor_middle_name, '') = COALESCE(d.middle_name, '')) AS documenter_sessions
         FROM documenter_pool d
         ORDER BY d.availability = 'available' DESC, d.last_name, d.first_name, d.middle_name"
    // Fetches all rows into the documenters array.
    )->fetchAll();
// Catches any error thrown while loading page data.
} catch (Throwable $e) {
    // Logs the technical error message.
    error_log('[documenter pool] ' . $e->getMessage());
    // Shows a friendly message about the pool failing to load.
    $error = 'The documenter pool could not be loaded. Apply the documenter-pool migration and try again.';
// Closes the catch block.
}

// Loads the shared header with sidebar.
require_once __DIR__ . '/../../includes/layout/header.php';
// Closes PHP so HTML is output.
?>

<?php /* Opens the page header box. */ ?>
<div class="page-header">
<?php /*     Shows the Documenter Pool heading. */ ?>
    <h2>Documenter Pool</h2>
<?php /*     Explains what can be managed on this page. */ ?>
    <p>Manage documenter qualifications, assigned presentation schedules, and availability.</p>
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
<?php /*     Button linking to the page for adding a documenter. */ ?>
    <a href="<?= url('coordinator/documenter/add.php') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Add Documenter</a>
<?php /* Closes the toolbar strip. */ ?>
</div>

<?php /* Opens the card that holds the documenter table. */ ?>
<div class="card">
<?php /*     Card header with the title and total number of documenters. */ ?>
    <div class="card-header"><h3>Documenters <span style="font-size:0.8rem;font-weight:400;color:var(--gray-500);">(<?= count($documenters) ?>)</span></h3></div>
<?php /*     Opens the card body. */ ?>
    <div class="card-body">
<?php /*         Wrapper that lets the table scroll on small screens. */ ?>
        <div class="table-responsive">
<?php /*             Opens the data table listing documenters. */ ?>
            <table class="data-table">
<?php /*                 Opens the table head section. */ ?>
                <thead>
<?php /*                     Header row naming each column, including Documenter Sessions. */ ?>
                    <tr><th>Name</th><th>Qualification</th><th style="text-align:center;">Documenter Sessions</th><th>Availability</th><th>Actions</th></tr>
<?php /*                 Closes the table head section. */ ?>
                </thead>
<?php /*                 Opens the table body where rows are printed. */ ?>
                <tbody>
<?php /*                     Checks whether the documenter list is empty. */ ?>
                    <?php if (!$documenters): ?>
<?php /*                     Single message row shown when there are no documenters yet. */ ?>
                    <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:2rem;">No documenters in the pool yet.</td></tr>
<?php /*                     Closes the empty-list check. */ ?>
                    <?php endif; ?>
<?php /*                     Loops through each documenter to build one table row. */ ?>
                    <?php foreach ($documenters as $documenter): ?>
<?php /*                     Builds a display name as last name, first name and middle name. */ ?>
                    <?php $name = trim($documenter['last_name'] . ', ' . $documenter['first_name'] . ' ' . ($documenter['middle_name'] ?? '')); ?>
<?php /*                     Opens a table row for one documenter. */ ?>
                    <tr>
<?php /*                         Opens the cell holding the documenter name. */ ?>
                        <td>
<?php /*                             Prints the built full name, escaped for safety. */ ?>
                            <strong><?= htmlspecialchars($name) ?></strong>
<?php /*                             Checks whether this documenter has an email address. */ ?>
                            <?php if (!empty($documenter['email'])): ?>
<?php /*                             Shows the email in small grey text under the name. */ ?>
                            <div style="font-size:0.75rem;color:var(--gray-400);"><?= htmlspecialchars($documenter['email']) ?></div>
<?php /*                             Closes the email check. */ ?>
                            <?php endif; ?>
<?php /*                         Closes the name cell. */ ?>
                        </td>
<?php /*                         Prints the documenter qualification in its own cell. */ ?>
                        <td style="font-size:0.83rem;"><?= htmlspecialchars($documenter['qualification']) ?></td>
<?php /*                         Shows how many schedules this documenter is assigned to. */ ?>
                        <td style="text-align:center;font-weight:600;color:var(--adzu-navy);"><?= (int) $documenter['documenter_sessions'] ?></td>
<?php /*                         Opens the cell holding the availability toggle form. */ ?>
                        <td>
<?php /*                             Small form that posts to this same page. */ ?>
                            <form method="post">
<?php /*                                 Hidden field carrying the documenter id for the toggle. */ ?>
                                <input type="hidden" name="toggle_avail" value="<?= (int) $documenter['documenter_id'] ?>">
<?php /*                                 Submit button styled green when available, outlined when not. */ ?>
                                <button type="submit" class="btn btn-sm <?= $documenter['availability'] === 'available' ? 'btn-success' : 'btn-outline' ?>" style="font-size:0.75rem;padding:3px 10px;">
<?php /*                                     Checks whether the documenter is currently available. */ ?>
                                    <?php if ($documenter['availability'] === 'available'): ?>
<?php /*                                     Shows a check icon and the word Available. */ ?>
                                    <i class="fas fa-check-circle"></i> Available
<?php /*                                     Starts the else branch for unavailable documenters. */ ?>
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
<?php /*                             Edit link that opens this documenter's edit page with its id. */ ?>
                            <a href="<?= url('coordinator/documenter/edit.php?id=' . (int) $documenter['documenter_id']) ?>" class="btn btn-primary coord-edit-btn" title="Edit documenter"><i class="fas fa-pen-to-square" aria-hidden="true"></i> Edit</a>
<?php /*                             Delete link that asks for confirmation before removing them. */ ?>
                            <?= coordDeleteLink(url('coordinator/documenter/documenter.php?delete=' . (int) $documenter['documenter_id']), 'Remove this documenter? Existing schedules will keep their saved details.') ?>
<?php /*                         Closes the actions cell. */ ?>
                        </td>
<?php /*                     Closes the documenter table row. */ ?>
                    </tr>
<?php /*                     Ends the loop over documenters. */ ?>
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
