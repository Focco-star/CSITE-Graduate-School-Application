<?php /* Opens the PHP block for the Edit Documenter page. */ ?>
<?php
// Loads shared config helpers such as url() and redirectTo().
require_once __DIR__ . '/../../includes/config.php';
// Loads the DB class that opens the MySQL connection.
require_once __DIR__ . '/../../includes/db.php';

// Checks if a session has already been started.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so flash messages can be shown.
    session_start();
// Closes the session check block.
}

// Sets the browser tab title to Edit Documenter.
$pageTitle = 'Edit Documenter';
// Marks this page as part of the coordinator area.
$role = 'coordinator';
// Highlights the Documenters menu in the sidebar.
$currentPage = 'documenters';
// Gets the coordinator's display name for the top bar.
$userName = currentCoordinatorName();
// Holds any validation or loading error message.
$error = '';
// Will store the documenter row loaded from the database.
$documenter = null;

// Starts a try block so database errors are caught.
try {
    // Opens the shared database connection.
    $pdo = DB::getConnection();
    // Prepares a query that fetches one documenter by documenter_id.
    $stmt = $pdo->prepare('SELECT * FROM documenter_pool WHERE documenter_id = :id LIMIT 1');
    // Runs the query using the id from the URL, or 0 when missing.
    $stmt->execute(['id' => (int) ($_GET['id'] ?? 0)]);
    // Stores the found row, or null when nothing matches.
    $documenter = $stmt->fetch() ?: null;
// Catches errors thrown while loading the documenter.
} catch (Throwable $e) {
    // Logs the technical error for debugging.
    error_log('[documenter pool] ' . $e->getMessage());
    // Shows a friendly message that the record could not be loaded.
    $error = 'The documenter record could not be loaded.';
// Closes the catch block.
}

// Runs the update only on POST and only when the record exists.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $documenter) {
    // Reads and formats the posted first name.
    $firstName = formatPersonName($_POST['first_name'] ?? '');
    // Reads and formats the posted middle name.
    $middleName = formatPersonName($_POST['middle_name'] ?? '');
    // Reads and formats the posted last name.
    $lastName = formatPersonName($_POST['last_name'] ?? '');
    // Reads and trims the qualification field.
    $qualification = trim((string) ($_POST['qualification'] ?? ''));
    // Reads and trims the email field.
    $email = trim((string) ($_POST['email'] ?? ''));
    // Checks that first name, last name and qualification are filled.
    if ($firstName === '' || $lastName === '' || $qualification === '') {
        // Sets the error telling which fields are required.
        $error = 'First name, last name, and qualification are required.';
    // Checks the email format only when something was typed.
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Sets the invalid-email error message.
        $error = 'Enter a valid email address.';
    // Starts the else branch that saves valid input.
    } else {
        // Opens a try block for the database update.
        try {
            // Prepares the UPDATE statement for the documenter row.
            $update = $pdo->prepare(
                // SQL that updates names, qualification, email, availability and notes by id.
                'UPDATE documenter_pool
                 SET first_name = :first_name, middle_name = :middle_name, last_name = :last_name,
                     qualification = :qualification, email = :email, availability = :availability, notes = :notes
                 WHERE documenter_id = :id'
            // Closes the SQL statement preparation.
            );
            // Runs the update with all the new values.
            $update->execute([
                // Sends the first name to the query.
                'first_name' => $firstName,
                // Sends the middle name, or NULL when left blank.
                'middle_name' => $middleName !== '' ? $middleName : null,
                // Sends the last name to the query.
                'last_name' => $lastName,
                // Sends the qualification text to the query.
                'qualification' => $qualification,
                // Sends the email, or NULL when it was erased.
                'email' => $email !== '' ? $email : null,
                // Keeps unavailable if chosen, otherwise stores available.
                'availability' => ($_POST['availability'] ?? 'available') === 'unavailable' ? 'unavailable' : 'available',
                // Sends the notes text, or NULL when empty.
                'notes' => trim((string) ($_POST['notes'] ?? '')) ?: null,
                // Identifies which documenter row to update.
                'id' => (int) $documenter['documenter_id'],
            // Closes the parameter array and finishes the update.
            ]);
            // Queues a green success flash message.
            setFlash('success', 'Documenter information updated.');
            // Redirects back to the documenter list page.
            redirectTo('coordinator/documenter/documenter.php');
        // Catches any error during the update.
        } catch (Throwable $e) {
            // Logs the update error details.
            error_log('[documenter pool] ' . $e->getMessage());
            // Shows a friendly update-failure message.
            $error = 'The documenter could not be updated. Please try again.';
        // Closes the update catch block.
        }
    // Closes the else branch for valid submissions.
    }
    // Replaces the documenter array with the submitted values for redisplay.
    $documenter = array_merge($documenter, [
        // Keeps the submitted first name visible.
        'first_name' => $firstName,
        // Keeps the submitted middle name visible.
        'middle_name' => $middleName,
        // Keeps the submitted last name visible.
        'last_name' => $lastName,
        // Keeps the submitted qualification visible.
        'qualification' => $qualification,
        // Keeps the submitted email visible.
        'email' => $email,
        // Keeps the selected availability value.
        'availability' => $_POST['availability'] ?? $documenter['availability'],
        // Keeps the typed notes text.
        'notes' => $_POST['notes'] ?? $documenter['notes'],
    // Closes the array merge call.
    ]);
// Closes the POST handling block.
}

// Loads the shared header with sidebar and navigation.
require_once __DIR__ . '/../../includes/header.php';
// Closes PHP so the HTML below is printed.
?>

<?php /* Opens the page header box. */ ?>
<div class="page-header">
<?php /*     Shows the Edit Documenter heading. */ ?>
    <h2>Edit Documenter</h2>
<?php /*     Short description of what the page does. */ ?>
    <p>Update documenter information and availability.</p>
<?php /* Closes the page header box. */ ?>
</div>

<?php /* Shows a red alert with the error message when one exists. */ ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php /* Checks whether the documenter could not be found. */ ?>
<?php if (!$documenter): ?>
<?php /* Warns the coordinator that the record is missing. */ ?>
<div class="alert alert-warning">Documenter not found.</div>
<?php /* Button taking the user back to the documenter list. */ ?>
<a class="btn btn-outline" href="<?= url('coordinator/documenter/documenter.php') ?>">Back to Documenter Pool</a>
<?php /* Starts the else branch that shows the edit form. */ ?>
<?php else: ?>
<?php /* Opens the form posting here with the documenter id in the URL. */ ?>
<form class="card" method="post" action="<?= url('coordinator/documenter/edit.php?id=' . (int) $documenter['documenter_id']) ?>" data-validate>
<?php /*     Opens the card body holding the form fields. */ ?>
    <div class="card-body">
<?php /*         Opens a row for the three name inputs. */ ?>
        <div class="form-row">
<?php /*             Opens the first name field group. */ ?>
            <div class="form-field">
<?php /*                 Labels the first name input as required. */ ?>
                <label>First Name <span class="required">*</span></label>
<?php /*                 First name input pre-filled with the stored value. */ ?>
                <input type="text" name="first_name" required value="<?= htmlspecialchars($documenter['first_name']) ?>">
<?php /*             Closes the first name field group. */ ?>
            </div>
<?php /*             Opens the middle name field group. */ ?>
            <div class="form-field">
<?php /*                 Labels the optional middle name input. */ ?>
                <label>Middle Name</label>
<?php /*                 Middle name input pre-filled from the record. */ ?>
                <input type="text" name="middle_name" value="<?= htmlspecialchars($documenter['middle_name'] ?? '') ?>">
<?php /*             Closes the middle name field group. */ ?>
            </div>
<?php /*             Opens the last name field group. */ ?>
            <div class="form-field">
<?php /*                 Labels the last name input as required. */ ?>
                <label>Last Name <span class="required">*</span></label>
<?php /*                 Last name input pre-filled with the stored value. */ ?>
                <input type="text" name="last_name" required value="<?= htmlspecialchars($documenter['last_name']) ?>">
<?php /*             Closes the last name field group. */ ?>
            </div>
<?php /*         Closes the name fields row. */ ?>
        </div>
<?php /*         Opens the qualification field group. */ ?>
        <div class="form-field">
<?php /*             Labels the qualification field as required. */ ?>
            <label>Qualification / Degree <span class="required">*</span></label>
<?php /*             Qualification input pre-filled with the stored degree. */ ?>
            <input type="text" name="qualification" required value="<?= htmlspecialchars($documenter['qualification']) ?>">
<?php /*         Closes the qualification field group. */ ?>
        </div>
<?php /*         Opens a row for email and availability. */ ?>
        <div class="form-row">
<?php /*             Opens the email field group. */ ?>
            <div class="form-field">
<?php /*                 Labels the optional email field. */ ?>
                <label>Email</label>
<?php /*                 Email input showing the stored address. */ ?>
                <input type="email" name="email" value="<?= htmlspecialchars($documenter['email'] ?? '') ?>">
<?php /*             Closes the email field group. */ ?>
            </div>
<?php /*             Opens the availability field group. */ ?>
            <div class="form-field">
<?php /*                 Labels the availability dropdown. */ ?>
                <label>Availability</label>
<?php /*                 Dropdown that stores whether the documenter is available. */ ?>
                <select name="availability">
<?php /*                     Available option, selected when the record says available. */ ?>
                    <option value="available" <?= $documenter['availability'] === 'available' ? 'selected' : '' ?>>Available</option>
<?php /*                     Unavailable option, selected when the record says unavailable. */ ?>
                    <option value="unavailable" <?= $documenter['availability'] === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
<?php /*                 Closes the availability dropdown. */ ?>
                </select>
<?php /*             Closes the availability field group. */ ?>
            </div>
<?php /*         Closes the email and availability row. */ ?>
        </div>
<?php /*         Opens the field group for notes. */ ?>
        <div class="form-field">
<?php /*             Labels the free-text notes box. */ ?>
            <label>Notes</label>
<?php /*             Textarea holding any existing notes about the documenter. */ ?>
            <textarea name="notes"><?= htmlspecialchars($documenter['notes'] ?? '') ?></textarea>
<?php /*         Closes the notes field group. */ ?>
        </div>
<?php /*         Opens the row with the save and cancel buttons. */ ?>
        <div class="form-actions">
<?php /*             Submit button that saves the changes. */ ?>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
<?php /*             Cancel link that leaves without saving. */ ?>
            <a href="<?= url('coordinator/documenter/documenter.php') ?>" class="btn btn-outline">Cancel</a>
<?php /*         Closes the buttons row. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the form element. */ ?>
</form>
<?php /* Closes the if/else that chooses form or not-found message. */ ?>
<?php endif; ?>

<?php /* Loads the shared footer scripts and closes the PHP block. */ ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
