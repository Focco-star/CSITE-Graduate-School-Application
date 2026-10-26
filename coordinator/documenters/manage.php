<?php /* Opens the PHP block that builds the documenter pool list page. */ ?>
<?php
// Loads shared settings and helper functions such as url() and redirectTo().
require_once __DIR__ . '/../../includes/config.php';
// Loads the database connection and documenter helper functions.
require_once __DIR__ . '/../../includes/db.php';

// Checks whether a session is already running before starting one.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so login details and flash messages work.
    session_start();
// Closes the session-start check.
}

// Sets the browser page title to Documenter Pool.
$pageTitle = 'Documenter Pool';
// Tells the header layout to use the coordinator menu.
$role = 'coordinator';
// Highlights the Documenters item in the sidebar.
$currentPage = 'documenters';
// Gets the logged-in coordinator's name for the top bar.
$userName = currentCoordinatorName();
// Creates an empty variable for any loading error message.
$error = '';
// Starts the documenter list as an empty array.
$documenters = [];

// Starts the try block so database errors can be caught.
try {
    // Gets the shared PDO database connection.
    $pdo = DB::getConnection();

    // Checks if a delete request with a numeric id was passed in the URL.
    if (isset($_GET['delete']) && ctype_digit((string) $_GET['delete'])) {
        // Deletes that documenter row from the pool table.
        deleteDocumenterRecord((string) $_GET['delete']);
        // Queues a success message confirming the removal.
        setFlash('success', 'Documenter removed from the pool.');
        // Reloads the list so the removed row disappears.
        redirectTo('coordinator/documenters/manage.php');
    // Closes the delete request check.
    }

    // Checks if the availability toggle button was posted from the table.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_avail'])) {
        // Loads the documenter row for the posted id.
        $doc = findDocumenter((string) $_POST['toggle_avail']);
        // Only continues when that documenter record really exists.
        if ($doc) {
            // Flips the status: available becomes unavailable and the reverse.
            $newAvailability = $doc['availability'] === 'available' ? 'unavailable' : 'available';
            // Gets the database connection again before preparing a query.
            $pdo = DB::getConnection();
            // Prepares an update statement to change only the availability column.
            $toggle = $pdo->prepare(
                // SQL that sets a new availability value for the matching documenter id.
                "UPDATE documenter_pool SET availability = :availability WHERE documenter_id = :id"
            // Closes the prepare call and stores the statement object.
            );
            // Runs the update with the documenter id and the flipped status.
            $toggle->execute(['id' => (int) $_POST['toggle_avail'], 'availability' => $newAvailability]);
            // Queues a success message saying availability was updated.
            setFlash('success', 'Documenter availability updated.');
            // Reloads the list so the new status is shown.
            redirectTo('coordinator/documenters/manage.php');
        // Closes the record-exists check.
        }
    // Closes the availability toggle check.
    }

    // Loads all documenters, with session counts, into the list variable.
    $documenters = databaseDocumenters();
// Catches any database or runtime error from the block above.
} catch (Throwable $e) {
    // Writes the technical error to the server log for debugging.
    error_log('[documenter pool] ' . $e->getMessage());
    // Sets a friendly error message telling the coordinator what to check.
    $error = 'The documenter pool could not be loaded. Check that the database migrations are applied and try again.';
// Closes the try/catch error handling block.
}

// Includes the shared header with menu and styles.
require_once __DIR__ . '/../../includes/header.php';
// Closes PHP and switches to HTML output.
?>

<?php /* Opens the page header wrapper for the title block. */ ?>
<div class="page-header">
<?php /*     Shows the main heading Documenter Pool. */ ?>
    <h2>Documenter Pool</h2>
<?php /*     Explains what documenters do and what this page manages. */ ?>
    <p>Manage documenters who record presentation proceedings, their qualifications, and availability.</p>
<?php /* Closes the page header wrapper. */ ?>
</div>

<?php /* Only shows the error box when a loading error exists. */ ?>
<?php if ($error): ?>
<?php /* Displays the error text in a red alert box with an icon. */ ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php /* Closes the error message condition. */ ?>
<?php endif; ?>

<?php /* Toolbar row that holds the add button. */ ?>
<div class="form-actions" style="border:none;padding:0;margin-bottom:1.5rem;">
<?php /*     Button linking to the page for adding a new documenter. */ ?>
    <a href="<?= url('coordinator/documenters/add.php') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Add Documenter</a>
<?php /* Closes the toolbar row. */ ?>
</div>

<?php /* Opens the card that holds the documenter table. */ ?>
<div class="card">
<?php /*     Card header showing the title and the number of documenters. */ ?>
    <div class="card-header"><h3>Documenters <span style="font-size:0.8rem;font-weight:400;color:var(--gray-500);">(<?= count($documenters) ?>)</span></h3></div>
<?php /*     Opens the card body around the table. */ ?>
    <div class="card-body">
<?php /*         Wrapper that lets the table scroll sideways on small screens. */ ?>
        <div class="table-responsive">
<?php /*             Starts the data table of documenters. */ ?>
            <table class="data-table">
<?php /*                 Opens the table header section. */ ?>
                <thead>
<?php /*                     Header row naming each column: name, qualification, sessions, status, actions. */ ?>
                    <tr><th>Name</th><th>Qualification</th><th style="text-align:center;">Documenter Sessions</th><th>Availability</th><th>Actions</th></tr>
<?php /*                 Closes the table header section. */ ?>
                </thead>
<?php /*                 Opens the table body where one row per documenter is printed. */ ?>
                <tbody>
<?php /*                     Shows the empty notice when no documenters exist yet. */ ?>
                    <?php if (!$documenters): ?>
<?php /*                     Single cell spanning all columns with the no-documenters message. */ ?>
                    <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:2rem;">No documenters in the pool yet.</td></tr>
<?php /*                     Closes the empty-list check. */ ?>
                    <?php endif; ?>
<?php /*                     Loops over each documenter row to print a table row. */ ?>
                    <?php foreach ($documenters as $documenter): ?>
<?php /*                     Opens one table row for a documenter. */ ?>
                    <tr>
<?php /*                         Opens the name cell. */ ?>
                        <td>
<?php /*                             Prints the documenter's full name in bold. */ ?>
                            <strong><?= htmlspecialchars($documenter['name']) ?></strong>
<?php /*                             Checks whether this documenter has an email to show. */ ?>
                            <?php if (!empty($documenter['email'])): ?>
<?php /*                             Prints the email in small grey text under the name. */ ?>
                            <div style="font-size:0.75rem;color:var(--gray-400);"><?= htmlspecialchars($documenter['email']) ?></div>
<?php /*                             Closes the email check. */ ?>
                            <?php endif; ?>
<?php /*                         Closes the name cell. */ ?>
                        </td>
<?php /*                         Prints the qualification text in a small font. */ ?>
                        <td style="font-size:0.83rem;"><?= htmlspecialchars($documenter['qualification']) ?></td>
<?php /*                         Prints how many sessions this documenter has recorded, defaulting to 0. */ ?>
                        <td style="text-align:center;font-weight:600;color:var(--adzu-navy);"><?= (int) ($documenter['documenter_sessions'] ?? 0) ?></td>
<?php /*                         Opens the availability cell. */ ?>
                        <td>
<?php /*                             Small form that posts a request to flip this documenter's status. */ ?>
                            <form method="post">
<?php /*                                 Hidden field carrying this documenter's id with the toggle request. */ ?>
                                <input type="hidden" name="toggle_avail" value="<?= (int) $documenter['id'] ?>">
<?php /*                                 Button coloured green when available, outlined when unavailable. */ ?>
                                <button type="submit" class="btn btn-sm <?= $documenter['availability'] === 'available' ? 'btn-success' : 'btn-outline' ?>" style="font-size:0.75rem;padding:3px 10px;">
<?php /*                                     Checks if the current status is available. */ ?>
                                    <?php if ($documenter['availability'] === 'available'): ?>
<?php /*                                     Shows a check icon and the word Available. */ ?>
                                    <i class="fas fa-check-circle"></i> Available
<?php /*                                     Runs when the documenter is not available. */ ?>
                                    <?php else: ?>
<?php /*                                     Shows a ban icon and the word Unavailable. */ ?>
                                    <i class="fas fa-ban"></i> Unavailable
<?php /*                                     Closes the availability status check. */ ?>
                                    <?php endif; ?>
<?php /*                                 Closes the toggle button. */ ?>
                                </button>
<?php /*                             Closes the availability toggle form. */ ?>
                            </form>
<?php /*                         Closes the availability cell. */ ?>
                        </td>
<?php /*                         Opens the actions cell for edit and delete controls. */ ?>
                        <td class="actions">
<?php /*                             Edit link that opens this documenter's edit page by id. */ ?>
                            <a href="<?= url('coordinator/documenters/edit.php?id=' . (int) $documenter['id']) ?>" class="btn btn-primary coord-edit-btn" title="Edit documenter"><i class="fas fa-pen-to-square" aria-hidden="true"></i> Edit</a>
<?php /*                             Delete link with a confirm question before removing the documenter. */ ?>
                            <?= coordDeleteLink(url('coordinator/documenters/manage.php?delete=' . (int) $documenter['id']), 'Remove this documenter from the pool?') ?>
<?php /*                         Closes the actions cell. */ ?>
                        </td>
<?php /*                     Closes this documenter's table row. */ ?>
                    </tr>
<?php /*                     Ends the loop over all documenters. */ ?>
                    <?php endforeach; ?>
<?php /*                 Closes the table body. */ ?>
                </tbody>
<?php /*             Closes the documenter table. */ ?>
            </table>
<?php /*         Closes the responsive table wrapper. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the card around the table. */ ?>
</div>

<?php /* Includes the shared footer that closes the page layout. */ ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>