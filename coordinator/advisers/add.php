<?php /* Opens the PHP block that builds the Add Adviser page. */ ?>
<?php
// Loads the shared config file with helper functions and settings.
require_once __DIR__ . '/../../includes/config.php';
// Loads the database helper class DB used for all queries.
require_once __DIR__ . '/../../includes/db.php';

// Checks whether no session is running yet.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so flash messages and login data are available.
    session_start();
// Closes the session check and start block.
}

// Sets the browser tab title to Add Adviser.
$pageTitle = 'Add Adviser';
// Tells the shared layout this page belongs to the coordinator role.
$role = 'coordinator';
// Highlights the Advisers menu item in the sidebar.
$currentPage = 'advisers';
// Gets the logged-in coordinator's name for the top bar.
$userName = currentCoordinatorName();
// Creates an empty error message holder for validation problems.
$error = '';

// Only runs the save logic when the form was submitted by POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Reads the typed first name and standardises its capitalisation.
    $firstName = formatPersonName($_POST['first_name'] ?? '');
    // Reads the typed middle name, cleaned up the same way.
    $middleName = formatPersonName($_POST['middle_name'] ?? '');
    // Reads the typed last name and normalises its capitalisation.
    $lastName = formatPersonName($_POST['last_name'] ?? '');
    // Starts building the display name only if last and first name exist.
    $name = $lastName !== '' && $firstName !== ''
        // Joins name as Last, First plus middle initial text when given.
        ? $lastName . ', ' . $firstName . ($middleName !== '' ? ' ' . $middleName : '')
        // Leaves the name empty when first or last name is missing.
        : '';
    // Reads and trims the qualification text, e.g. PhD in Computer Science.
    $qualification = trim((string) ($_POST['qualification'] ?? ''));
    // Reads and trims the email address typed in the form.
    $email = trim((string) ($_POST['email'] ?? ''));
    // Checks that the required first name, last name and qualification are filled.
    if ($firstName === '' || $lastName === '' || $qualification === '') {
        // Sets the error message telling the user which fields are required.
        $error = 'First name, last name, and qualification are required.';
    // If an email was given, checks that it looks like a real email address.
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Stores the invalid-email error message.
        $error = 'Enter a valid email address.';
    // Runs this block when all the validation checks passed.
    } else {
        // Starts a try block so database errors can be caught safely.
        try {
            // Prepares an INSERT statement for the advisor_pool table.
            $stmt = DB::getConnection()->prepare(
                // SQL inserting a new adviser row with name, qualification, email and availability.
                'INSERT INTO advisor_pool (name, qualification, email, availability)
                 VALUES (:name, :qualification, :email, :availability)'
            // Closes the prepare call and ends the SQL statement setup.
            );
            // Runs the insert, passing the values collected from the form.
            $stmt->execute([
                // Passes the combined adviser name into the query.
                'name' => $name,
                // Passes the qualification text into the query.
                'qualification' => $qualification,
                // Passes the email, or NULL when the field was left blank.
                'email' => $email !== '' ? $email : null,
                // Stores unavailable when chosen, otherwise defaults to available.
                'availability' => ($_POST['availability'] ?? 'available') === 'unavailable' ? 'unavailable' : 'available',
            // Closes the array of values and runs the execute call.
            ]);
            // Queues a green success message for the next page load.
            setFlash('success', 'Adviser added successfully.');
            // Sends the coordinator back to the adviser list page.
            redirectTo('coordinator/advisers/manage.php');
        // Catches any database or runtime error from the try block.
        } catch (Throwable $e) {
            // Writes the error details to the log for debugging.
            error_log('[adviser pool] ' . $e->getMessage());
            // Shows a friendly failure message instead of a crash.
            $error = 'The adviser could not be saved. Check the database connection and try again.';
        // Closes the catch block.
        }
    // Closes the validation else block that saved the adviser.
    }
// Closes the POST-submission check block.
}

// Loads the shared page header with sidebar and top navigation.
require_once __DIR__ . '/../../includes/header.php';
// Closes PHP so the HTML markup below is output.
?>

<?php /* Opens the page header box with the title and description. */ ?>
<div class="page-header">
<?php /*     Shows the page heading Add Adviser. */ ?>
    <h2>Add Adviser</h2>
<?php /*     Explains that this page adds someone to the assessment team. */ ?>
    <p>Add an adviser to the academic assessment team.</p>
<?php /* Closes the page header box. */ ?>
</div>

<?php /* Shows a red alert with the validation error, but only if one exists. */ ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php /* Opens the form card that posts back to this same page. */ ?>
<form class="card" method="post" action="<?= url('coordinator/advisers/add.php') ?>" data-validate>
<?php /*     Opens the card body that holds all the input fields. */ ?>
    <div class="card-body">
<?php /*         Opens a row to place the three name fields side by side. */ ?>
        <div class="form-row">
<?php /*             Opens the field group for the first name. */ ?>
            <div class="form-field">
<?php /*                 Labels the first name input and marks it as required. */ ?>
                <label>First Name <span class="required">*</span></label>
<?php /*                 Text input for the first name; keeps the typed value after a failed save. */ ?>
                <input type="text" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
<?php /*             Closes the first name field group. */ ?>
            </div>
<?php /*             Opens the field group for the middle name. */ ?>
            <div class="form-field">
<?php /*                 Labels the optional middle name input. */ ?>
                <label>Middle Name</label>
<?php /*                 Text input for the middle name, keeping the previous value if any. */ ?>
                <input type="text" name="middle_name" value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>">
<?php /*             Closes the middle name field group. */ ?>
            </div>
<?php /*             Opens the field group for the last name. */ ?>
            <div class="form-field">
<?php /*                 Labels the last name input and marks it as required. */ ?>
                <label>Last Name <span class="required">*</span></label>
<?php /*                 Text input for the last name; required before saving. */ ?>
                <input type="text" name="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
<?php /*             Closes the last name field group. */ ?>
            </div>
<?php /*         Closes the row holding the three name fields. */ ?>
        </div>
<?php /*         Opens the field group for the qualification. */ ?>
        <div class="form-field">
<?php /*             Labels the qualification field as required. */ ?>
            <label>Qualification / Degree <span class="required">*</span></label>
<?php /*             Text input for the degree with an example placeholder, value kept on error. */ ?>
            <input type="text" name="qualification" required value="<?= htmlspecialchars($_POST['qualification'] ?? '') ?>" placeholder="e.g., PhD in Computer Science">
<?php /*         Closes the qualification field group. */ ?>
        </div>
<?php /*         Opens a row for the email and availability fields. */ ?>
        <div class="form-row">
<?php /*             Opens the field group for the email address. */ ?>
            <div class="form-field">
<?php /*                 Labels the optional email field. */ ?>
                <label>Email</label>
<?php /*                 Email input with a school email hint; value is remembered after errors. */ ?>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="name@adzu.edu.ph">
<?php /*             Closes the email field group. */ ?>
            </div>
<?php /*             Opens the field group for availability. */ ?>
            <div class="form-field">
<?php /*                 Labels the availability dropdown. */ ?>
                <label>Availability</label>
<?php /*                 Dropdown letting the coordinator mark the adviser available or not. */ ?>
                <select name="availability">
<?php /*                     The default option meaning the adviser can take on students. */ ?>
                    <option value="available">Available</option>
<?php /*                     Unavailable option, re-selected automatically when it was chosen before. */ ?>
                    <option value="unavailable" <?= ($_POST['availability'] ?? '') === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
<?php /*                 Closes the availability dropdown. */ ?>
                </select>
<?php /*             Closes the availability field group. */ ?>
            </div>
<?php /*         Closes the row with email and availability. */ ?>
        </div>
<?php /*         Opens the row holding the save and cancel buttons. */ ?>
        <div class="form-actions">
<?php /*             Submit button that sends the form and shows a save icon. */ ?>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Adviser</button>
<?php /*             Cancel link that returns to the adviser list without saving. */ ?>
            <a href="<?= url('coordinator/advisers/manage.php') ?>" class="btn btn-outline">Cancel</a>
<?php /*         Closes the buttons row. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the form card element. */ ?>
</form>

<?php /* Loads the shared footer with scripts, then closes PHP. */ ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
