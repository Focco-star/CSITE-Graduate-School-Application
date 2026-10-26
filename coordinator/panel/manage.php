<?php /* Opens the PHP block that builds the panel member list page. */ ?>
<?php
// Loads shared settings and helper functions such as url() and redirectTo().
require_once __DIR__ . '/../../includes/config.php';
// Loads the database connection and panel helper functions.
require_once __DIR__ . '/../../includes/db.php';

// Sets the browser page title to Panel Members.
$pageTitle   = 'Panel Members';
// Tells the header layout to use the coordinator menu.
$role        = 'coordinator';
// Highlights the Panel item in the sidebar.
$currentPage = 'panel';
// Gets the coordinator's display name for the top bar.
$userName    = $mockCoordinator['name'];

// Checks if a delete request with an id was passed in the URL.
if (isset($_GET['delete']) && $_GET['delete'] !== '') {
    // Deletes that panel member row from the database.
    deletePanelMemberRecord((string) $_GET['delete']);
    // Reloads the list so the removed member disappears.
    redirectTo('coordinator/panel/manage.php');
// Closes the delete request check.
}

// Checks if the availability toggle button was posted from the table.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_avail'])) {
    // Loads the panel member row for the posted id.
    $pm = findPanelMember((string) $_POST['toggle_avail']);
    // Only continues when that panel member really exists.
    if ($pm) {
        // Calls the update helper with the new availability value.
        updatePanelMemberRecord($pm['id'], [
            // Flips the status: available becomes unavailable and the reverse.
            'availability' => ($pm['availability'] ?? 'available') === 'available' ? 'unavailable' : 'available',
        // Closes the array of updated values.
        ]);
    // Closes the record-exists check.
    }
    // Reloads the list so the new status is shown.
    redirectTo('coordinator/panel/manage.php');
// Closes the availability toggle check.
}

// Loads all panel members, with session counts, into the list variable.
$panelMembers = databasePanelMembers();

// Includes the shared header with menu and styles.
require_once __DIR__ . '/../../includes/header.php';
// Closes PHP and switches to HTML output.
?>

<?php /* Opens the page header wrapper for the title block. */ ?>
<div class="page-header">
<?php /*     Shows the main heading Panel Member Management. */ ?>
    <h2>Panel Member Management</h2>
<?php /*     Explains that this page manages members and their availability. */ ?>
    <p>Manage available panel members, qualifications, and availability.</p>
<?php /* Closes the page header wrapper. */ ?>
</div>

<?php /* Toolbar row that holds the add button. */ ?>
<div class="form-actions" style="border:none;padding:0;margin-bottom:1.5rem;">
<?php /*     Button linking to the page for adding a new panel member. */ ?>
    <a href="<?= url('coordinator/panel/add.php') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Add Panel Member</a>
<?php /* Closes the toolbar row. */ ?>
</div>

<?php /* Opens the card that holds the panel member table. */ ?>
<div class="card">
<?php /*     Card header showing the title and the number of members. */ ?>
    <div class="card-header"><h3>Panel Members <span style="font-size:0.8rem;font-weight:400;color:var(--gray-500);">(<?= count($panelMembers) ?>)</span></h3></div>
<?php /*     Opens the card body around the table. */ ?>
    <div class="card-body">
<?php /*         Wrapper that lets the table scroll sideways on small screens. */ ?>
        <div class="table-responsive">
<?php /*             Starts the data table of panel members. */ ?>
            <table class="data-table">
<?php /*                 Opens the table header section. */ ?>
                <thead>
<?php /*                     Header row naming each column: name, qualification, sessions, status, actions. */ ?>
                    <tr><th>Name</th><th>Qualification</th><th style="text-align:center;">Panel Sessions</th><th>Availability</th><th>Actions</th></tr>
<?php /*                 Closes the table header section. */ ?>
                </thead>
<?php /*                 Opens the table body where one row per member is printed. */ ?>
                <tbody>
<?php /*                     Shows the empty notice when no panel members exist yet. */ ?>
                    <?php if (!$panelMembers): ?>
<?php /*                     Single cell spanning all columns with the no-members message. */ ?>
                    <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:2rem;">No panel members yet.</td></tr>
<?php /*                     Closes the empty-list check. */ ?>
                    <?php endif; ?>
<?php /*                     Loops over each panel member to print a table row. */ ?>
                    <?php foreach ($panelMembers as $pm): ?>
<?php /*                     Opens one table row for a panel member. */ ?>
                    <tr>
<?php /*                         Opens the name cell. */ ?>
                        <td>
<?php /*                             Prints the panel member's full name in bold. */ ?>
                            <strong><?= htmlspecialchars($pm['name']) ?></strong>
<?php /*                             Checks whether this member has an email to show. */ ?>
                            <?php if (!empty($pm['email'])): ?>
<?php /*                             Prints the email in small grey text under the name. */ ?>
                            <div style="font-size:0.75rem;color:var(--gray-400);"><?= htmlspecialchars($pm['email']) ?></div>
<?php /*                             Closes the email check. */ ?>
                            <?php endif; ?>
<?php /*                         Closes the name cell. */ ?>
                        </td>
<?php /*                         Prints the qualification text in a small font. */ ?>
                        <td style="font-size:0.83rem;"><?= htmlspecialchars($pm['qualification']) ?></td>
<?php /*                         Opens the cell that counts panel sessions. */ ?>
                        <td style="text-align:center;font-weight:600;color:var(--adzu-navy);">
<?php /*                             Prints how many sessions this member joined, defaulting to 0. */ ?>
                            <?= (int) ($pm['panelSessions'] ?? 0) ?>
<?php /*                         Closes the session count cell. */ ?>
                        </td>
<?php /*                         Opens the availability cell. */ ?>
                        <td>
<?php /*                             Small form that posts a request to flip this member's status. */ ?>
                            <form method="post">
<?php /*                                 Hidden field carrying this member's id with the toggle request. */ ?>
                                <input type="hidden" name="toggle_avail" value="<?= htmlspecialchars($pm['id']) ?>">
<?php /*                                 Button coloured green when available, outlined when unavailable. */ ?>
                                <button type="submit" class="btn btn-sm <?= ($pm['availability'] ?? 'available') === 'available' ? 'btn-success' : 'btn-outline' ?>" style="font-size:0.75rem;padding:3px 10px;">
<?php /*                                     Checks if the current status is available. */ ?>
                                    <?php if (($pm['availability'] ?? 'available') === 'available'): ?>
<?php /*                                     Shows a check icon and the word Available. */ ?>
                                    <i class="fas fa-check-circle"></i> Available
<?php /*                                     Runs when the member is not available. */ ?>
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
<?php /*                             Edit link that opens this member's edit page by id. */ ?>
                            <a href="<?= url('coordinator/panel/edit.php?id=' . urlencode($pm['id'])) ?>" class="btn btn-primary coord-edit-btn" title="Edit panel member"><i class="fas fa-pen-to-square" aria-hidden="true"></i> Edit</a>
<?php /*                             Delete link with a confirm question before removing the member. */ ?>
                            <?= coordDeleteLink(url('coordinator/panel/manage.php?delete=' . urlencode($pm['id'])), 'Remove this panel member?') ?>
<?php /*                         Closes the actions cell. */ ?>
                        </td>
<?php /*                     Closes this member's table row. */ ?>
                    </tr>
<?php /*                     Ends the loop over all panel members. */ ?>
                    <?php endforeach; ?>
<?php /*                 Closes the table body. */ ?>
                </tbody>
<?php /*             Closes the panel member table. */ ?>
            </table>
<?php /*         Closes the responsive table wrapper. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the card around the table. */ ?>
</div>

<?php /* Includes the shared footer that closes the page layout. */ ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>