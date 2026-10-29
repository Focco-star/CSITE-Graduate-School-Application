<?php /* Opens the PHP block that builds the edit-panel-member page. */ ?>
<?php
// Loads shared settings and helper functions such as url() and redirectTo().
require_once __DIR__ . '/../../includes/config/config.php';
// Loads the database connection and panel helper functions.
require_once __DIR__ . '/../../includes/config/db.php';

// Sets the browser page title to Edit Panel Member.
$pageTitle   = 'Edit Panel Member';
// Tells the header layout to use the coordinator menu.
$role        = 'coordinator';
// Highlights the Panel item in the sidebar.
$currentPage = 'panel';
// Gets the coordinator's display name for the top bar.
$userName    = $mockCoordinator['name'];

// Loads the panel member by id from the URL, or falls back to the first one.
$pm = findPanelMember((string) ($_GET['id'] ?? '')) ?? (databasePanelMembers()[0] ?? null);

// Only saves when the form was posted and a member record exists.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pm) {
    // Calls the helper that updates this panel member's row.
    updatePanelMemberRecord($pm['id'], [
        // Saves the cleaned first name, keeping the old one if empty.
        'first_name' => formatPersonName($_POST['first_name'] ?? $pm['first_name']),
        // Saves the cleaned middle name, keeping the old one if empty.
        'middle_name' => formatPersonName($_POST['middle_name'] ?? $pm['middle_name']),
        // Saves the cleaned last name, keeping the old one if empty.
        'last_name' => formatPersonName($_POST['last_name'] ?? $pm['last_name']),
        // Saves the trimmed qualification, keeping the old one if empty.
        'qualification' => trim($_POST['qualification'] ?? $pm['qualification']),
        // Saves the trimmed email address, defaulting to empty text.
        'email' => trim($_POST['email'] ?? ''),
        // Saves the trimmed notes, defaulting to empty text.
        'notes' => trim($_POST['notes'] ?? ''),
        // Saves unavailable only when that option was chosen, else available.
        'availability' => ($_POST['availability'] ?? 'available') === 'unavailable' ? 'unavailable' : 'available',
    // Closes the array of updated panel member values.
    ]);
    // Returns to the panel list after saving the changes.
    redirectTo('coordinator/panel/manage.php');
// Closes the save-on-POST block.
}

// Includes the shared header with menu and styles.
require_once __DIR__ . '/../../includes/layout/header.php';
// Closes PHP and switches to HTML output.
?>

<?php /* Opens the page header wrapper for the title block. */ ?>
<div class="page-header">
<?php /*     Shows the main heading Edit Panel Member. */ ?>
    <h2>Edit Panel Member</h2>
<?php /*     Explains that this page updates details and availability. */ ?>
    <p>Update panel member information and availability.</p>
<?php /* Closes the page header wrapper. */ ?>
</div>

<?php /* Runs when no matching panel member record was found. */ ?>
<?php if (!$pm): ?>
<?php /* Shows a yellow warning saying the panel member was not found. */ ?>
<div class="alert alert-warning">Panel member not found.</div>
<?php /* Button that takes the coordinator back to the panel list. */ ?>
<a class="btn btn-outline" href="<?= url('coordinator/panel/manage.php') ?>">Back</a>
<?php /* Runs when a member record was found and the form can show. */ ?>
<?php else: ?>
<?php /* Starts the form posting to this page with the member id in the URL. */ ?>
<form class="card" method="post" action="<?= url('coordinator/panel/edit.php?id=' . urlencode($pm['id'])) ?>" data-validate>
<?php /*     Opens the card body that holds the input fields. */ ?>
    <div class="card-body">
<?php /*         Opens the field container for the first name. */ ?>
        <div class="form-field">
<?php /*             Labels the first name input as required. */ ?>
            <label>First Name <span class="required">*</span></label>
<?php /*             Text input filled with the saved first name. */ ?>
            <input type="text" name="first_name" required value="<?= htmlspecialchars($pm['first_name']) ?>">
<?php /*         Closes the first name field container. */ ?>
        </div>
<?php /*         Opens the field container for the middle name. */ ?>
        <div class="form-field">
<?php /*             Labels the optional middle name input. */ ?>
            <label>Middle Name</label>
<?php /*             Text input filled with the saved middle name. */ ?>
            <input type="text" name="middle_name" value="<?= htmlspecialchars($pm['middle_name']) ?>">
<?php /*         Closes the middle name field container. */ ?>
        </div>
<?php /*         Opens the field container for the last name. */ ?>
        <div class="form-field">
<?php /*             Labels the last name input as required. */ ?>
            <label>Last Name <span class="required">*</span></label>
<?php /*             Text input filled with the saved last name. */ ?>
            <input type="text" name="last_name" required value="<?= htmlspecialchars($pm['last_name']) ?>">
<?php /*         Closes the last name field container. */ ?>
        </div>
<?php /*         Opens the field container for qualification. */ ?>
        <div class="form-field">
<?php /*             Labels the qualification input as required. */ ?>
            <label>Qualification / Degree <span class="required">*</span></label>
<?php /*             Text input filled with the saved qualification or degree. */ ?>
            <input type="text" name="qualification" required value="<?= htmlspecialchars($pm['qualification']) ?>">
<?php /*         Closes the qualification field container. */ ?>
        </div>
<?php /*         Opens the field container for email. */ ?>
        <div class="form-field">
<?php /*             Labels the optional email input. */ ?>
            <label>Email</label>
<?php /*             Email input filled with the saved email address. */ ?>
            <input type="email" name="email" value="<?= htmlspecialchars($pm['email'] ?? '') ?>">
<?php /*         Closes the email field container. */ ?>
        </div>
<?php /*         Starts a form row for the availability field. */ ?>
        <div class="form-row">
<?php /*             Opens the field container for availability. */ ?>
            <div class="form-field">
<?php /*                 Labels the availability dropdown. */ ?>
                <label>Availability</label>
<?php /*                 Opens the availability select box. */ ?>
                <select name="availability">
<?php /*                     Available option, selected when the saved status is available. */ ?>
                    <option value="available" <?= ($pm['availability'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
<?php /*                     Unavailable option, selected when the saved status is unavailable. */ ?>
                    <option value="unavailable" <?= ($pm['availability'] ?? '') === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
<?php /*                 Closes the availability dropdown. */ ?>
                </select>
<?php /*             Closes the availability field container. */ ?>
            </div>
<?php /*         Closes the availability form row. */ ?>
        </div>
<?php /*         Opens the field container for notes. */ ?>
        <div class="form-field">
<?php /*             Labels the notes text area. */ ?>
            <label>Notes</label>
<?php /*             Multi-line text box holding the saved notes for this member. */ ?>
            <textarea name="notes"><?= htmlspecialchars($pm['notes'] ?? '') ?></textarea>
<?php /*         Closes the notes field container. */ ?>
        </div>
<?php /*         Opens the row that holds the save and cancel buttons. */ ?>
        <div class="form-actions">
<?php /*             Submit button that posts the changed details to be saved. */ ?>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
<?php /*             Cancel link that returns to the panel list without saving. */ ?>
            <a href="<?= url('coordinator/panel/manage.php') ?>" class="btn btn-outline">Cancel</a>
<?php /*         Closes the form action button row. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the whole panel member form. */ ?>
</form>
<?php /* Closes the found/not-found if/else block. */ ?>
<?php endif; ?>

<?php /* Includes the shared footer that closes the page layout. */ ?>
<?php require_once __DIR__ . '/../../includes/layout/footer.php'; ?>
