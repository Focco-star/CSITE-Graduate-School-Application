<?php /* Opens the PHP block that builds the Add Documenter page. */ ?>
<?php
// Loads the shared config with helper functions and settings.
require_once __DIR__ . '/../../includes/config.php';
// Loads the DB class used for database queries.
require_once __DIR__ . '/../../includes/db.php';

// Checks whether no session is running yet.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so flash messages can be used.
    session_start();
// Closes the session check block.
}

// Sets the browser tab title to Add Documenter.
$pageTitle = 'Add Documenter';
// Marks the page as part of the coordinator area.
$role = 'coordinator';
// Highlights the Documenters menu in the sidebar.
$currentPage = 'documenters';
// Gets the coordinator's name for the top bar.
$userName = currentCoordinatorName();
// Creates an empty holder for validation errors.
$error = '';

// Only runs the save logic when the form was submitted by POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Reads the typed first name and standardises its capitalisation.
    $firstName = formatPersonName($_POST['first_name'] ?? '');
    // Reads the typed middle name the same way.
    $middleName = formatPersonName($_POST['middle_name'] ?? '');
    // Reads the typed last name and cleans it up.
    $lastName = formatPersonName($_POST['last_name'] ?? '');
    // Reads and trims the qualification text.
    $qualification = trim((string) ($_POST['qualification'] ?? ''));
    // Reads and trims the email address.
    $email = trim((string) ($_POST['email'] ?? ''));
    // Checks that first name, last name and qualification are filled.
    if ($firstName === '' || $lastName === '' || $qualification === '') {
        // Sets the error naming the required fields.
        $error = 'First name, last name, and qualification are required.';
    // Checks the email format only when an email was typed.
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Sets the invalid-email error message.
        $error = 'Enter a valid email address.';
    // Starts the else branch that saves valid input.
    } else {
        // Opens a try block so database errors are caught.
        try {
            // Prepares an INSERT for the documenter_pool table.
            $stmt = DB::getConnection()->prepare(
                // SQL inserting a new documenter with names, qualification, email and availability.
                'INSERT INTO documenter_pool
                    (first_name, middle_name, last_name, qualification, email, availability)
                 VALUES (:first_name, :middle_name, :last_name, :qualification, :email, :availability)'
            // Closes the prepare call and ends the SQL setup.
            );
            // Runs the insert with the collected form values.
            $stmt->execute([
                // Passes the first name to the query.
                'first_name' => $firstName,
                // Passes the middle name, or NULL when left blank.
                'middle_name' => $middleName !== '' ? $middleName : null,
                // Passes the last name to the query.
                'last_name' => $lastName,
                // Passes the qualification text to the query.
                'qualification' => $qualification,
                // Passes the email, or NULL when left blank.
                'email' => $email !== '' ? $email : null,
                // Keeps unavailable if chosen, otherwise stores available.
                'availability' => ($_POST['availability'] ?? 'available') === 'unavailable' ? 'unavailable' : 'available',
            // Closes the value array and finishes the insert.
            ]);
            // Queues a green success flash message.
            setFlash('success', 'Documenter added successfully.');
            // Redirects to the documenter list page.
            redirectTo('coordinator/documenter/documenter.php');
        // Catches any error thrown while saving.
        } catch (Throwable $e) {
            // Logs the technical error for debugging.
            error_log('[documenter pool] ' . $e->getMessage());
            // Shows a friendly message that saving failed.
            $error = 'The documenter could not be saved. Check the database migration and try again.';
        // Closes the catch block.
        }
    // Closes the else branch for valid submissions.
    }
// Closes the POST handling block.
}

// Loads the shared header with sidebar and top bar.
require_once __DIR__ . '/../../includes/header.php';
// Closes PHP so the HTML below is printed.
?>

<?php /* Opens the page header box. */ ?>
<div class="page-header">
<?php /*     Shows the Add Documenter heading. */ ?>
    <h2>Add Documenter</h2>
<?php /*     Explains that this page adds someone to the team. */ ?>
    <p>Add a documenter to the academic assessment team.</p>
<?php /* Closes the page header box. */ ?>
</div>

<?php /* Shows a red alert with the error, but only when one exists. */ ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php /* Opens the form posting back to this same page. */ ?>
<form class="card" method="post" action="<?= url('coordinator/documenter/add.php') ?>" data-validate>
<?php /*     Opens the card body holding the fields. */ ?>
    <div class="card-body">
<?php /*         Opens a row for the three name inputs. */ ?>
        <div class="form-row">
<?php /*             Opens the first name field group. */ ?>
            <div class="form-field">
<?php /*                 Labels the first name input as required. */ ?>
                <label>First Name <span class="required">*</span></label>
<?php /*                 First name input, keeping the typed value after a failed save. */ ?>
                <input type="text" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
<?php /*             Closes the first name field group. */ ?>
            </div>
<?php /*             Opens the middle name field group. */ ?>
            <div class="form-field">
<?php /*                 Labels the optional middle name input. */ ?>
                <label>Middle Name</label>
<?php /*                 Middle name input keeping the previous value. */ ?>
                <input type="text" name="middle_name" value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>">
<?php /*             Closes the middle name field group. */ ?>
            </div>
<?php /*             Opens the last name field group. */ ?>
            <div class="form-field">
<?php /*                 Labels the last name input as required. */ ?>
                <label>Last Name <span class="required">*</span></label>
<?php /*                 Last name input, required before saving. */ ?>
                <input type="text" name="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
<?php /*             Closes the last name field group. */ ?>
            </div>
<?php /*         Closes the row of name fields. */ ?>
        </div>
<?php /*         Opens the qualification field group. */ ?>
        <div class="form-field">
<?php /*             Labels the qualification field as required. */ ?>
            <label>Qualification / Degree <span class="required">*</span></label>
<?php /*             Qualification input with an example placeholder and remembered value. */ ?>
            <input type="text" name="qualification" required value="<?= htmlspecialchars($_POST['qualification'] ?? '') ?>" placeholder="e.g., MS in Information Technology">
<?php /*         Closes the qualification field group. */ ?>
        </div>
<?php /*         Opens a row for email and availability. */ ?>
        <div class="form-row">
<?php /*             Opens the email field group. */ ?>
            <div class="form-field">
<?php /*                 Labels the optional email field. */ ?>
                <label>Email</label>
<?php /*                 Email input with a school email hint and remembered value. */ ?>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="name@adzu.edu.ph">
<?php /*             Closes the email field group. */ ?>
            </div>
<?php /*             Opens the availability field group. */ ?>
            <div class="form-field">
<?php /*                 Labels the availability dropdown. */ ?>
                <label>Availability</label>
<?php /*                 Dropdown marking whether the documenter is available. */ ?>
                <select name="availability">
<?php /*                     Default option meaning the documenter can be assigned. */ ?>
                    <option value="available">Available</option>
<?php /*                     Unavailable option, re-selected when it was chosen before. */ ?>
                    <option value="unavailable" <?= ($_POST['availability'] ?? '') === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
<?php /*                 Closes the availability dropdown. */ ?>
                </select>
<?php /*             Closes the availability field group. */ ?>
            </div>
<?php /*         Closes the email and availability row. */ ?>
        </div>
<?php /*         Opens the row with save and cancel buttons. */ ?>
        <div class="form-actions">
<?php /*             Submit button that saves the new documenter. */ ?>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Documenter</button>
<?php /*             Cancel link back to the documenter list. */ ?>
            <a href="<?= url('coordinator/documenter/documenter.php') ?>" class="btn btn-outline">Cancel</a>
<?php /*         Closes the buttons row. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the form element. */ ?>
</form>

<?php /* Loads the shared footer scripts and closes PHP. */ ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>