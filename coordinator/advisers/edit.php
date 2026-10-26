<?php /* Opens the PHP block for the Edit Adviser page. */ ?>
<?php
// Loads the shared config with helpers such as url() and redirectTo().
require_once __DIR__ . '/../../includes/config.php';
// Loads the DB class that opens the MySQL connection.
require_once __DIR__ . '/../../includes/db.php';

// Checks if a session has already been started.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so flash messages can be shown.
    session_start();
// Closes the session check block.
}

// Sets the browser tab title to Edit Adviser.
$pageTitle = 'Edit Adviser';
// Marks this page as part of the coordinator area.
$role = 'coordinator';
// Highlights the Advisers menu entry in the sidebar.
$currentPage = 'advisers';
// Gets the coordinator's display name for the top bar.
$userName = currentCoordinatorName();
// Holds any validation or loading error message.
$error = '';
// Will store the adviser row loaded from the database.
$adviser = null;
// Flags old records that only have one full-name field.
$legacySingleName = false;

// Starts a try block so database errors are caught.
try {
    // Opens the shared database connection.
    $pdo = DB::getConnection();
    // Prepares a query that fetches one adviser by adviser_id.
    $stmt = $pdo->prepare('SELECT * FROM advisor_pool WHERE adviser_id = :id LIMIT 1');
    // Runs the query using the id from the URL, or 0 when missing.
    $stmt->execute(['id' => (int) ($_GET['id'] ?? 0)]);
    // Stores the found adviser row, or null when nothing matches.
    $adviser = $stmt->fetch() ?: null;
    // Continues only when an adviser record was actually found.
    if ($adviser) {
        // Splits the stored name on the first comma into last and given parts.
        $nameParts = array_map('trim', explode(',', (string) $adviser['name'], 2));
        // Checks that the name had exactly two comma-separated parts.
        if (count($nameParts) === 2) {
            // Uses the text before the comma as the last name.
            $adviser['last_name'] = $nameParts[0];
            // Splits the given-name part on spaces into words.
            $givenParts = preg_split('/\s+/', $nameParts[1]) ?: [];
            // Takes the first word as the first name.
            $adviser['first_name'] = array_shift($givenParts) ?? '';
            // Joins the remaining words back as the middle name.
            $adviser['middle_name'] = implode(' ', $givenParts);
        // Handles names that do not use the Last, First format.
        } else {
            // Splits the whole stored name on spaces as a fallback.
            $nameParts = preg_split('/\s+/', trim((string) $adviser['name'])) ?: [];
            // Treats the first word as first name when more than one word exists.
            $adviser['first_name'] = count($nameParts) > 1 ? (array_shift($nameParts) ?? '') : '';
            // Uses the remaining words as the last name.
            $adviser['last_name'] = implode(' ', $nameParts);
            // Checks whether no last name could be worked out.
            if ($adviser['last_name'] === '') {
                // Falls back to showing the whole stored name as the first name.
                $adviser['first_name'] = trim((string) $adviser['name']);
                // Remembers this is a legacy single-name record.
                $legacySingleName = true;
            // Closes the legacy single-name check block.
            }
            // Clears the middle name for the fallback case.
            $adviser['middle_name'] = '';
        // Closes the else branch that handled names without a comma.
        }
    // Closes the block that ran when an adviser row was found.
    }
// Catches errors thrown while loading the adviser.
} catch (Throwable $e) {
    // Logs the technical error for debugging.
    error_log('[adviser pool] ' . $e->getMessage());
    // Shows a friendly message that the record could not be loaded.
    $error = 'The adviser record could not be loaded.';
// Closes the catch block.
}

// Runs the update only on POST and only when the adviser exists.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $adviser) {
    // Reads and formats the posted first name.
    $firstName = formatPersonName($_POST['first_name'] ?? '');
    // Reads and formats the posted middle name.
    $middleName = formatPersonName($_POST['middle_name'] ?? '');
    // Reads and formats the posted last name.
    $lastName = formatPersonName($_POST['last_name'] ?? '');
    // Starts building the display name when a last name is present.
    $name = $lastName !== ''
        // Combines last name and first name, adding the middle name if given.
        ? $lastName . ', ' . $firstName . ($middleName !== '' ? ' ' . $middleName : '')
        // Falls back to just the first name when there is no last name.
        : $firstName;
    // Reads and trims the qualification field.
    $qualification = trim((string) ($_POST['qualification'] ?? ''));
    // Reads and trims the email field.
    $email = trim((string) ($_POST['email'] ?? ''));
    // Validates the required fields, skipping last name for legacy records.
    if ($firstName === '' || (!$legacySingleName && $lastName === '') || $qualification === '') {
        // Sets the error telling the coordinator which fields are required.
        $error = 'First name, last name, and qualification are required.';
    // Checks the email format only when something was typed.
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Sets the invalid-email error message.
        $error = 'Enter a valid email address.';
    // Starts the else branch that saves valid input.
    } else {
        // Opens a try block for the database update.
        try {
            // Prepares the UPDATE statement for the adviser row.
            $update = $pdo->prepare(
                // SQL that updates name, qualification, email, availability and notes by id.
                'UPDATE advisor_pool
                 SET name = :name, qualification = :qualification, email = :email,
                     availability = :availability, notes = :notes
                 WHERE adviser_id = :id'
            // Closes the SQL statement preparation.
            );
            // Runs the update with all the new values.
            $update->execute([
                // Sends the combined adviser name to the query.
                'name' => $name,
                // Sends the qualification text to the query.
                'qualification' => $qualification,
                // Sends the email, or NULL when it was erased.
                'email' => $email !== '' ? $email : null,
                // Keeps unavailable if chosen, otherwise stores available.
                'availability' => ($_POST['availability'] ?? 'available') === 'unavailable' ? 'unavailable' : 'available',
                // Sends the notes text, or NULL when empty.
                'notes' => trim((string) ($_POST['notes'] ?? '')) ?: null,
                // Identifies which adviser row to update.
                'id' => (int) $adviser['adviser_id'],
            // Closes the parameter array and finishes the update.
            ]);
            // Queues a green success flash message.
            setFlash('success', 'Adviser information updated.');
            // Redirects back to the adviser list page.
            redirectTo('coordinator/advisers/manage.php');
        // Catches any error during the update.
        } catch (Throwable $e) {
            // Logs the update error details.
            error_log('[adviser pool] ' . $e->getMessage());
            // Shows a friendly update-failure message.
            $error = 'The adviser could not be updated. Please try again.';
        // Closes the update catch block.
        }
    // Closes the else branch for valid submissions.
    }
    // Replaces the adviser array with the submitted values for redisplay.
    $adviser = array_merge($adviser, [
        // Keeps the rebuilt full name visible in the form.
        'name' => $name,
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
        'availability' => $_POST['availability'] ?? $adviser['availability'],
        // Keeps the typed notes text.
        'notes' => $_POST['notes'] ?? $adviser['notes'],
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
<?php /*     Shows the Edit Adviser heading. */ ?>
    <h2>Edit Adviser</h2>
<?php /*     Short description of what the page does. */ ?>
    <p>Update adviser information and availability.</p>
<?php /* Closes the page header box. */ ?>
</div>

<?php /* Shows a red alert with the error message when one exists. */ ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php /* Checks whether the adviser could not be found. */ ?>
<?php if (!$adviser): ?>
<?php /* Warns the coordinator that the adviser record is missing. */ ?>
<div class="alert alert-warning">Adviser not found.</div>
<?php /* Button taking the user back to the adviser list. */ ?>
<a class="btn btn-outline" href="<?= url('coordinator/advisers/manage.php') ?>">Back to Pool of Advisers</a>
<?php /* Starts the else branch that shows the edit form. */ ?>
<?php else: ?>
<?php /* Opens the form posting to this page with the adviser id in the URL. */ ?>
<form class="card" method="post" action="<?= url('coordinator/advisers/edit.php?id=' . (int) $adviser['adviser_id']) ?>" data-validate>
<?php /*     Opens the card body holding the form fields. */ ?>
    <div class="card-body">
<?php /*         Opens a row for the three name inputs. */ ?>
        <div class="form-row">
<?php /*             Opens the first name field group. */ ?>
            <div class="form-field">
<?php /*                 Labels the first name input as required. */ ?>
                <label>First Name <span class="required">*</span></label>
<?php /*                 First name input pre-filled with the stored value. */ ?>
                <input type="text" name="first_name" required value="<?= htmlspecialchars($adviser['first_name']) ?>">
<?php /*             Closes the first name field group. */ ?>
            </div>
<?php /*             Opens the middle name field group. */ ?>
            <div class="form-field">
<?php /*                 Labels the optional middle name input. */ ?>
                <label>Middle Name</label>
<?php /*                 Middle name input pre-filled from the record. */ ?>
                <input type="text" name="middle_name" value="<?= htmlspecialchars($adviser['middle_name'] ?? '') ?>">
<?php /*             Closes the middle name field group. */ ?>
            </div>
<?php /*             Opens the last name field group. */ ?>
            <div class="form-field">
<?php /*                 Shows the required star only for normal records, not legacy names. */ ?>
                <label>Last Name <?= $legacySingleName ? '' : '<span class="required">*</span>' ?></label>
<?php /*                 Last name input, required unless this is a legacy single-name record. */ ?>
                <input type="text" name="last_name" <?= $legacySingleName ? '' : 'required' ?> value="<?= htmlspecialchars($adviser['last_name']) ?>">
<?php /*             Closes the last name field group. */ ?>
            </div>
<?php /*         Closes the name fields row. */ ?>
        </div>
<?php /*         Opens the qualification field group. */ ?>
        <div class="form-field">
<?php /*             Labels the qualification field as required. */ ?>
            <label>Qualification / Degree <span class="required">*</span></label>
<?php /*             Qualification input pre-filled with the stored degree. */ ?>
            <input type="text" name="qualification" required value="<?= htmlspecialchars($adviser['qualification']) ?>">
<?php /*         Closes the qualification field group. */ ?>
        </div>
<?php /*         Opens a row for email and availability. */ ?>
        <div class="form-row">
<?php /*             Opens the email field group. */ ?>
            <div class="form-field">
<?php /*                 Labels the optional email field. */ ?>
                <label>Email</label>
<?php /*                 Email input showing the stored address. */ ?>
                <input type="email" name="email" value="<?= htmlspecialchars($adviser['email'] ?? '') ?>">
<?php /*             Closes the email field group. */ ?>
            </div>
<?php /*             Opens the availability field group. */ ?>
            <div class="form-field">
<?php /*                 Labels the availability dropdown. */ ?>
                <label>Availability</label>
<?php /*                 Dropdown that stores whether the adviser is available. */ ?>
                <select name="availability">
<?php /*                     Available option, selected when the record says available. */ ?>
                    <option value="available" <?= ($adviser['availability'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
<?php /*                     Unavailable option, selected when the record says unavailable. */ ?>
                    <option value="unavailable" <?= ($adviser['availability'] ?? '') === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
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
<?php /*             Textarea holding any existing notes about the adviser. */ ?>
            <textarea name="notes"><?= htmlspecialchars($adviser['notes'] ?? '') ?></textarea>
<?php /*         Closes the notes field group. */ ?>
        </div>
<?php /*         Opens the row with the save and cancel buttons. */ ?>
        <div class="form-actions">
<?php /*             Submit button that saves the changes. */ ?>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
<?php /*             Cancel link that leaves without saving. */ ?>
            <a href="<?= url('coordinator/advisers/manage.php') ?>" class="btn btn-outline">Cancel</a>
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
