<?php /* Opens the PHP block that builds the edit-documenter page. */ ?>
<?php
// Loads shared settings and helper functions such as redirectTo and url().
require_once __DIR__ . '/../../includes/config.php';
// Loads the database connection and documenter helper functions.
require_once __DIR__ . '/../../includes/db.php';

// Checks whether a session is already running before starting one.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so the coordinator's login details are available.
    session_start();
// Closes the session-start check.
}

// Blocks access unless the session user exists and has the coordinator role.
if (!is_array($_SESSION['user'] ?? null) || strtolower((string) ($_SESSION['user']['role'] ?? '')) !== 'coordinator') {
    // Sends non-coordinators back to the coordinator login page.
    redirectTo('coordinator/login.php');
// Closes the coordinator access check.
}

// Sets the browser page title to Edit Documenter.
$pageTitle = 'Edit Documenter';
// Tells the header layout to use the coordinator menu.
$role = 'coordinator';
// Highlights the Documenters item in the sidebar.
$currentPage = 'documenters';
// Gets the logged-in coordinator's name for the top bar.
$userName = currentCoordinatorName();
// Creates an empty variable to hold any form error message.
$error = '';

// Reads the record id from the URL and trims extra spaces.
$id = trim((string) ($_GET['id'] ?? ''));
// Checks that the id is present and made only of digits.
if (!$id || !ctype_digit($id)) {
    // Sends the coordinator back to the list when the id is bad.
    redirectTo('coordinator/documenters/manage.php');
// Closes the id validation check.
}

// Loads that documenter's row from the database by id.
$documenter = findDocumenter($id);
// Checks whether no matching documenter record was found.
if (!$documenter) {
    // Queues an error message saying the documenter was not found.
    setFlash('error', 'Documenter not found.');
    // Returns to the documenter list instead of showing the form.
    redirectTo('coordinator/documenters/manage.php');
// Closes the missing-record check.
}

// Only runs the update logic when the form was submitted by POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cleans the typed first name using the shared name formatter.
    $firstName = formatPersonName($_POST['first_name'] ?? '');
    // Cleans the typed last name using the shared name formatter.
    $lastName = formatPersonName($_POST['last_name'] ?? '');
    // Trims the middle initial and makes it uppercase.
    $middleName = strtoupper(trim($_POST['middle_initial'] ?? ''));
    // Reads the typed qualification and removes extra spaces.
    $qualification = trim($_POST['qualification'] ?? '');
    // Reads the typed email and removes extra spaces.
    $email = trim($_POST['email'] ?? '');
    // Reads the typed notes and removes extra spaces.
    $notes = trim($_POST['notes'] ?? '');
    // Reads the chosen availability, defaulting to available.
    $availability = $_POST['availability'] ?? 'available';

    // Checks that the required first and last names were filled in.
    if ($firstName === '' || $lastName === '') {
        // Stores the error shown when a required name is missing.
        $error = 'First name and last name are required.';
    // Otherwise checks that any email entered is a valid address.
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Stores the error shown when the email format is wrong.
        $error = 'Enter a valid email address.';
    // Runs the update when the form data passed validation.
    } else {
        // Starts the try block so database errors can be caught.
        try {
            // Calls the helper that updates this documenter's row.
            updateDocumenterRecord($id, [
                // Passes the cleaned first name into the update.
                'first_name' => $firstName,
                // Passes the cleaned last name into the update.
                'last_name' => $lastName,
                // Passes the middle initial into the update.
                'middle_name' => $middleName,
                // Passes the qualification text into the update.
                'qualification' => $qualification,
                // Passes the email address into the update.
                'email' => $email,
                // Passes the notes text into the update.
                'notes' => $notes,
                // Passes the availability status into the update.
                'availability' => $availability,
            // Closes the array of updated documenter values.
            ]);
            // Queues a success message to show on the next page.
            setFlash('success', 'Documenter updated successfully.');
            // Sends the coordinator back to the documenter list after saving.
            redirectTo('coordinator/documenters/manage.php');
        // Catches any error thrown while updating the record.
        } catch (Throwable $e) {
            // Shows a friendly message when the update fails.
            $error = 'Unable to update documenter. Please try again.';
        // Closes the try/catch error handling block.
        }
    // Closes the validation if/elseif/else block.
    }
// Closes the POST request check.
}

// Sets the page title again for the HTML output section.
$pageTitle = 'Edit Documenter';
// Sets the layout role to coordinator for the header.
$role = 'coordinator';
// Keeps the sidebar on the Documenters section.
$currentPage = 'documenters';
// Gets the coordinator name for the page header.
$userName = currentCoordinatorName();

// Includes the shared header with menu and styles.
require_once __DIR__ . '/../../includes/header.php';
// Closes PHP and switches to HTML output.
?>

<?php /* Opens the page header wrapper for the title block. */ ?>
<div class="page-header">
<?php /*     Shows the main heading Edit Documenter. */ ?>
    <h2>Edit Documenter</h2>
<?php /*     Shows a short sentence explaining the page purpose. */ ?>
    <p>Update documenter information.</p>
<?php /* Closes the page header wrapper. */ ?>
</div>

<?php /* Only shows the error box when an error message exists. */ ?>
<?php if ($error): ?>
<?php /* Displays the error text in a red alert box with an icon. */ ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php /* Closes the error message condition. */ ?>
<?php endif; ?>

<?php /* Starts the form posting to this page with the current id kept in the URL. */ ?>
<form method="post" action="<?= url('coordinator/documenters/edit.php?id=' . htmlspecialchars($id)) ?>" class="card" data-validate>
<?php /*     Card header showing the section name Documenter Information. */ ?>
    <div class="card-header"><h3>Documenter Information</h3></div>
<?php /*     Opens the card body that holds the input fields. */ ?>
    <div class="card-body">
<?php /*         Starts a form row so three fields sit side by side. */ ?>
        <div class="form-row">
<?php /*             Opens the field container for the first name. */ ?>
            <div class="form-field">
<?php /*                 Labels the first name input as required. */ ?>
                <label>First Name <span class="required">*</span></label>
<?php /*                 Text input filled with the saved first name of the documenter. */ ?>
                <input type="text" name="first_name" required value="<?= htmlspecialchars($documenter['first_name'] ?? '') ?>">
<?php /*             Closes the first name field container. */ ?>
            </div>
<?php /*             Opens the field container for the middle initial. */ ?>
            <div class="form-field">
<?php /*                 Labels the optional middle initial input. */ ?>
                <label>Middle Initial</label>
<?php /*                 Short uppercase input filled with the saved middle initial. */ ?>
                <input type="text" name="middle_initial" maxlength="2" style="text-transform:uppercase;" value="<?= htmlspecialchars($documenter['middle_name'] ?? '') ?>">
<?php /*             Closes the middle initial field container. */ ?>
            </div>
<?php /*             Opens the field container for the last name. */ ?>
            <div class="form-field">
<?php /*                 Labels the last name input as required. */ ?>
                <label>Last Name <span class="required">*</span></label>
<?php /*                 Text input filled with the saved last name of the documenter. */ ?>
                <input type="text" name="last_name" required value="<?= htmlspecialchars($documenter['last_name'] ?? '') ?>">
<?php /*             Closes the last name field container. */ ?>
            </div>
<?php /*         Closes the three-column form row. */ ?>
        </div>
<?php /*         Opens the full-width field container for qualification. */ ?>
        <div class="form-field">
<?php /*             Labels the qualification input. */ ?>
            <label>Qualification</label>
<?php /*             Text input filled with the saved qualification value. */ ?>
            <input type="text" name="qualification" placeholder="e.g. PhD, MSc, etc." value="<?= htmlspecialchars($documenter['qualification'] ?? '') ?>">
<?php /*         Closes the qualification field container. */ ?>
        </div>
<?php /*         Opens the full-width field container for email. */ ?>
        <div class="form-field">
<?php /*             Labels the email input. */ ?>
            <label>Email</label>
<?php /*             Email input filled with the saved email address. */ ?>
            <input type="email" name="email" placeholder="documenter@adzu.edu.ph" value="<?= htmlspecialchars($documenter['email'] ?? '') ?>">
<?php /*         Closes the email field container. */ ?>
        </div>
<?php /*         Opens the field container for notes. */ ?>
        <div class="form-field">
<?php /*             Labels the notes text area. */ ?>
            <label>Notes</label>
<?php /*             Multi-line text box holding the saved notes for this documenter. */ ?>
            <textarea name="notes" placeholder="Any additional notes..."><?= htmlspecialchars($documenter['notes'] ?? '') ?></textarea>
<?php /*         Closes the notes field container. */ ?>
        </div>
<?php /*         Opens the field container for availability. */ ?>
        <div class="form-field">
<?php /*             Labels the availability dropdown. */ ?>
            <label>Availability</label>
<?php /*             Opens the availability select box. */ ?>
            <select name="availability">
<?php /*                 Available option, selected when the saved status is available. */ ?>
                <option value="available" <?= ($documenter['availability'] ?? 'available') === 'available' ? 'selected' : '' ?>>Available</option>
<?php /*                 Unavailable option, selected when the saved status is unavailable. */ ?>
                <option value="unavailable" <?= ($documenter['availability'] ?? '') === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
<?php /*             Closes the availability dropdown. */ ?>
            </select>
<?php /*         Closes the availability field container. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /*     Opens the row that holds the save and cancel buttons. */ ?>
    <div class="form-actions">
<?php /*         Submit button that posts the changed details to be saved. */ ?>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
<?php /*         Cancel link that returns to the documenter list without saving. */ ?>
        <a href="<?= url('coordinator/documenters/manage.php') ?>" class="btn btn-outline">Cancel</a>
<?php /*     Closes the form action button row. */ ?>
    </div>
<?php /* Closes the whole documenter form. */ ?>
</form>

<?php /* Includes the shared footer that closes the page layout. */ ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>