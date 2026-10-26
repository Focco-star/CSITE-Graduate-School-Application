<?php /* Opens the PHP block that builds the add-panel-member page. */ ?>
<?php
// Loads shared settings and helper functions such as url() and redirectTo().
require_once __DIR__ . '/../../includes/config.php';
// Loads the database connection and panel helper functions.
require_once __DIR__ . '/../../includes/db.php';

// Starts the session only if one is not running yet.
if (session_status() === PHP_SESSION_NONE) session_start();

// Sets the browser page title to Add Panel Member.
$pageTitle   = 'Add Panel Member';
// Tells the header layout to use the coordinator menu.
$role        = 'coordinator';
// Highlights the Panel item in the sidebar.
$currentPage = 'panel';
// Gets the logged-in coordinator's name for the top bar.
$userName    = currentCoordinatorName();
// Starts the success pop-up data as null until a save happens.
$popup       = null;   // success pop-up, shown after the save redirect (see below)
// Creates an empty variable to hold any form error message.
$error       = '';

// Only runs the save logic when the form was submitted by POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cleans the typed first name using the shared name formatter.
    $firstName = formatPersonName($_POST['first_name'] ?? '');
    // Cleans the typed middle name using the shared name formatter.
    $middleName = formatPersonName($_POST['middle_name'] ?? '');
    // Cleans the typed last name using the shared name formatter.
    $lastName = formatPersonName($_POST['last_name'] ?? '');
    // Reads the qualification text and removes extra spaces.
    $qual = trim($_POST['qualification'] ?? '');
    // Reads the email address and removes extra spaces.
    $email = trim($_POST['email'] ?? '');

    // Checks the required first name, last name, and qualification are filled.
    if ($firstName === '' || $lastName === '' || $qual === '') {
        // Stores the error listing which required fields are missing.
        $error = 'First name, last name, and qualification are required.';
    // Otherwise checks that any email entered looks valid.
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Stores the error shown when the email format is wrong.
        $error = 'Enter a valid email address.';
    // Runs the insert when the form data passed validation.
    } else {
        // Starts the try block so database errors can be caught.
        try {
            // Calls the helper that inserts a new panel member row.
            addPanelMemberRecord([
                // Passes the cleaned first name into the new record.
                'first_name' => $firstName,
                // Passes the middle name into the new record.
                'middle_name' => $middleName,
                // Passes the cleaned last name into the new record.
                'last_name' => $lastName,
                // Passes the qualification into the new record.
                'qualification' => $qual,
                // Passes the email address into the new record.
                'email' => $email,
                // Stores available unless the coordinator chose unavailable.
                'availability' => ($_POST['availability'] ?? 'available') === 'unavailable' ? 'unavailable' : 'available',
            // Closes the array of panel member values.
            ]);
            // Reloads the page after saving so the success pop-up can appear.
            // Post/Redirect/Get: reload this page so the success pop-up shows, then it forwards to the list
            // Saves the pop-up details in the session for the next page load.
            $_SESSION['panel_success_popup'] = [
                // Sets the pop-up heading saying the member was added.
                'title'   => 'Panel Member Added Successfully',
                // Builds the pop-up message with the new member's full name.
                'message' => '"' . trim($firstName . ' ' . $lastName) . '" was added to the panel.',
            // Closes the pop-up data array.
            ];
            // Redirects back to this page so the browser reloads it.
            redirectTo('coordinator/panel/add.php');
        // Catches any error thrown while inserting the record.
        } catch (Throwable $e) {
            // Writes the technical error to the server log for debugging.
            error_log('[panel pool] ' . $e->getMessage());
            // Shows a friendly message telling the coordinator to check the database.
            $error = 'The panel member could not be saved. Check the database connection and try again.';
        // Closes the try/catch error handling block.
        }
    // Closes the validation if/elseif/else block.
    }
// Runs when the page was opened normally rather than submitted.
} else {
    // Reads any stored pop-up details to show once.
    $popup = $_SESSION['panel_success_popup'] ?? null;
    // Removes the pop-up from the session so it only shows once.
    unset($_SESSION['panel_success_popup']);
// Closes the POST/GET branch.
}

// Includes the shared header with menu and styles.
require_once __DIR__ . '/../../includes/header.php';
// Closes PHP and switches to HTML output.
?>

<?php /* Opens the page header wrapper for the title block. */ ?>
<div class="page-header">
<?php /*     Shows the main heading Add Panel Member. */ ?>
    <h2>Add Panel Member</h2>
<?php /*     Explains that panel members attend capstone and thesis presentations. */ ?>
    <p>Register a new panel member for capstone/thesis presentations.</p>
<?php /* Closes the page header wrapper. */ ?>
</div>

<?php /* Shows the error alert in red when a save error exists. */ ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php /* Starts the card form that posts to this page with validation. */ ?>
<form class="card" method="post" action="<?= url('coordinator/panel/add.php') ?>" data-validate>
<?php /*     Opens the card body that holds the input fields. */ ?>
    <div class="card-body">
<?php /*         Starts a form row so the three name fields sit side by side. */ ?>
        <div class="form-row">
<?php /*             Opens the field container for the first name. */ ?>
            <div class="form-field">
<?php /*                 Labels the first name input as required. */ ?>
                <label>First Name <span class="required">*</span></label>
<?php /*                 Text input for the first name, keeping the value after a failed save. */ ?>
                <input type="text" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
<?php /*             Closes the first name field container. */ ?>
            </div>
<?php /*             Opens the field container for the middle name. */ ?>
            <div class="form-field">
<?php /*                 Labels the optional middle name input. */ ?>
                <label>Middle Name</label>
<?php /*                 Text input for the middle name, keeping the typed value. */ ?>
                <input type="text" name="middle_name" value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>">
<?php /*             Closes the middle name field container. */ ?>
            </div>
<?php /*             Opens the field container for the last name. */ ?>
            <div class="form-field">
<?php /*                 Labels the last name input as required. */ ?>
                <label>Last Name <span class="required">*</span></label>
<?php /*                 Text input for the last name, keeping the value after a failed save. */ ?>
                <input type="text" name="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
<?php /*             Closes the last name field container. */ ?>
            </div>
<?php /*         Closes the three-column name row. */ ?>
        </div>
<?php /*         Opens the full-width field container for qualification. */ ?>
        <div class="form-field">
<?php /*             Labels the qualification input as required. */ ?>
            <label>Qualification / Degree <span class="required">*</span></label>
<?php /*             Text input for the degree with an example placeholder. */ ?>
            <input type="text" name="qualification" required value="<?= htmlspecialchars($_POST['qualification'] ?? '') ?>" placeholder="e.g., PhD in Computer Science">
<?php /*         Closes the qualification field container. */ ?>
        </div>
<?php /*         Starts a form row holding email and availability. */ ?>
        <div class="form-row">
<?php /*             Opens the field container for email. */ ?>
            <div class="form-field">
<?php /*                 Labels the optional email input. */ ?>
                <label>Email</label>
<?php /*                 Email input with a sample address as placeholder text. */ ?>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="name@adzu.edu.ph">
<?php /*             Closes the email field container. */ ?>
            </div>
<?php /*             Opens the field container for availability. */ ?>
            <div class="form-field">
<?php /*                 Labels the availability dropdown. */ ?>
                <label>Availability</label>
<?php /*                 Opens the availability select box. */ ?>
                <select name="availability">
<?php /*                     Available option, the default choice. */ ?>
                    <option value="available">Available</option>
<?php /*                     Unavailable option, kept selected when it was the last choice. */ ?>
                    <option value="unavailable" <?= ($_POST['availability'] ?? '') === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
<?php /*                 Closes the availability dropdown. */ ?>
                </select>
<?php /*             Closes the availability field container. */ ?>
            </div>
<?php /*         Closes the email and availability row. */ ?>
        </div>
<?php /*         Opens the row that holds the save and cancel buttons. */ ?>
        <div class="form-actions">
<?php /*             Submit button that posts the form to save the panel member. */ ?>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Panel Member</button>
<?php /*             Cancel link that returns to the panel list without saving. */ ?>
            <a href="<?= url('coordinator/panel/manage.php') ?>" class="btn btn-outline">Cancel</a>
<?php /*         Closes the form action button row. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the panel member form. */ ?>
</form>

<?php /* Only prints the pop-up data block when pop-up details exist. */ ?>
<?php if ($popup): ?>
<?php /* Hidden block that feeds the success pop-up its text and redirect. */ ?>
<!-- Shows for 5 seconds (or until the X is clicked), then returns to the panel list -->
<?php /* Hidden div carrying the pop-up data for the page script. */ ?>
<div id="successPopupData" hidden
<?php /*      Data attribute holding the pop-up title text. */ ?>
     data-title="<?= htmlspecialchars($popup['title']) ?>"
<?php /*      Data attribute holding the pop-up message text. */ ?>
     data-message="<?= htmlspecialchars($popup['message']) ?>"
<?php /*      Data attribute holding the list page to open after the pop-up. */ ?>
     data-redirect="<?= htmlspecialchars(url('coordinator/panel/manage.php')) ?>"></div>
<?php /* Closes the pop-up data condition. */ ?>
<?php endif; ?>

<?php /* Includes the shared footer that closes the page layout. */ ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
