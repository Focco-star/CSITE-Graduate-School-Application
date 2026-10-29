<?php /* Opens the PHP block that builds the add-documenter page. */ ?>
<?php
// Loads shared settings and helper functions such as redirectTo and url().
require_once __DIR__ . '/../../includes/config/config.php';
// Loads the database connection code used by the documenter functions.
require_once __DIR__ . '/../../includes/config/db.php';

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

// Sets the browser page title to Add Documenter.
$pageTitle = 'Add Documenter';
// Tells the header layout to use the coordinator menu.
$role = 'coordinator';
// Highlights the Documenters item in the sidebar.
$currentPage = 'documenters';
// Gets the logged-in coordinator's name for the top bar.
$userName = currentCoordinatorName();
// Creates an empty variable to hold any form error message.
$error = '';

// Only runs the save logic when the form was submitted by POST.
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
    // Runs the insert when the form data passed validation.
    } else {
        // Starts the try block so database errors can be caught.
        try {
            // Calls the helper that inserts a new documenter row.
            addDocumenterRecord([
                // Passes the cleaned first name into the new record.
                'first_name' => $firstName,
                // Passes the cleaned last name into the new record.
                'last_name' => $lastName,
                // Passes the middle initial into the new record.
                'middle_name' => $middleName,
                // Passes the qualification text into the new record.
                'qualification' => $qualification,
                // Passes the email address into the new record.
                'email' => $email,
                // Passes the availability status into the new record.
                'availability' => $availability,
            // Closes the array of documenter values.
            ]);
            // Queues a success message to show on the next page.
            setFlash('success', 'Documenter added to the pool.');
            // Sends the coordinator to the documenter list after saving.
            redirectTo('coordinator/documenters/manage.php');
        // Catches any error thrown while inserting the record.
        } catch (Throwable $e) {
            // Shows a friendly message when the insert fails.
            $error = 'Unable to add documenter. Please try again.';
        // Closes the try/catch error handling block.
        }
    // Closes the validation if/elseif/else block.
    }
// Closes the POST request check.
}

// Sets the page title again for the HTML output section.
$pageTitle = 'Add Documenter';
// Sets the layout role to coordinator for the header.
$role = 'coordinator';
// Keeps the sidebar on the Documenters section.
$currentPage = 'documenters';
// Gets the coordinator name for the page header.
$userName = currentCoordinatorName();

// Includes the shared header with menu and styles.
require_once __DIR__ . '/../../includes/layout/header.php';
// Closes PHP and switches to HTML output.
?>

<?php /* Opens the page header wrapper for the title block. */ ?>
<div class="page-header">
<?php /*     Shows the main heading Add Documenter. */ ?>
    <h2>Add Documenter</h2>
<?php /*     Shows a short sentence explaining the page purpose. */ ?>
    <p>Add a new documenter to the pool.</p>
<?php /* Closes the page header wrapper. */ ?>
</div>

<?php /* Only shows the error box when an error message exists. */ ?>
<?php if ($error): ?>
<?php /* Displays the error text in a red alert box with an icon. */ ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php /* Closes the error message condition. */ ?>
<?php endif; ?>

<?php /* Starts the form that posts to this page and has client-side validation. */ ?>
<form method="post" action="<?= url('coordinator/documenters/add.php') ?>" class="card" data-validate>
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
<?php /*                 Text input for the first name, keeping the value after a failed save. */ ?>
                <input type="text" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
<?php /*             Closes the first name field container. */ ?>
            </div>
<?php /*             Opens the field container for the middle initial. */ ?>
            <div class="form-field">
<?php /*                 Labels the optional middle initial input. */ ?>
                <label>Middle Initial</label>
<?php /*                 Short text input for the middle initial, shown in uppercase. */ ?>
                <input type="text" name="middle_initial" maxlength="2" style="text-transform:uppercase;" value="<?= htmlspecialchars($_POST['middle_initial'] ?? '') ?>">
<?php /*             Closes the middle initial field container. */ ?>
            </div>
<?php /*             Opens the field container for the last name. */ ?>
            <div class="form-field">
<?php /*                 Labels the last name input as required. */ ?>
                <label>Last Name <span class="required">*</span></label>
<?php /*                 Text input for the last name, keeping the value after a failed save. */ ?>
                <input type="text" name="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
<?php /*             Closes the last name field container. */ ?>
            </div>
<?php /*         Closes the three-column form row. */ ?>
        </div>
<?php /*         Opens the full-width field container for qualification. */ ?>
        <div class="form-field">
<?php /*             Labels the qualification input. */ ?>
            <label>Qualification</label>
<?php /*             Text input for the qualification with an example placeholder. */ ?>
            <input type="text" name="qualification" placeholder="e.g. PhD, MSc, etc." value="<?= htmlspecialchars($_POST['qualification'] ?? '') ?>">
<?php /*         Closes the qualification field container. */ ?>
        </div>
<?php /*         Opens the full-width field container for email. */ ?>
        <div class="form-field">
<?php /*             Labels the email input. */ ?>
            <label>Email</label>
<?php /*             Email input with a sample address as placeholder text. */ ?>
            <input type="email" name="email" placeholder="documenter@adzu.edu.ph" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
<?php /*         Closes the email field container. */ ?>
        </div>
<?php /*         Opens the field container for availability. */ ?>
        <div class="form-field">
<?php /*             Labels the availability dropdown. */ ?>
            <label>Availability</label>
<?php /*             Opens the availability select box. */ ?>
            <select name="availability">
<?php /*                 Available option, kept selected when that was the last choice. */ ?>
                <option value="available" <?= ($_POST['availability'] ?? 'available') === 'available' ? 'selected' : '' ?>>Available</option>
<?php /*                 Unavailable option, selected when that was the last choice. */ ?>
                <option value="unavailable" <?= ($_POST['availability'] ?? '') === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
<?php /*             Closes the availability dropdown. */ ?>
            </select>
<?php /*         Closes the availability field container. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /*     Opens the row that holds the save and cancel buttons. */ ?>
    <div class="form-actions">
<?php /*         Submit button that posts the form to save the documenter. */ ?>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Documenter</button>
<?php /*         Cancel link that returns to the documenter list without saving. */ ?>
        <a href="<?= url('coordinator/documenters/manage.php') ?>" class="btn btn-outline">Cancel</a>
<?php /*     Closes the form action button row. */ ?>
    </div>
<?php /* Closes the whole documenter form. */ ?>
</form>

<?php /* Includes the shared footer that closes the page layout. */ ?>
<?php require_once __DIR__ . '/../../includes/layout/footer.php'; ?>