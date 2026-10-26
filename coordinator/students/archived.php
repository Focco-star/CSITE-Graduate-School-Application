<?php /* Starts the PHP block for the coordinator archived students page. */ ?>
<?php
// Loads the shared configuration, helper functions, and constants.
require_once __DIR__ . '/../../includes/config.php';
// Loads the shared database connection class used by this page.
require_once __DIR__ . '/../../includes/db.php';

// Starts the session only when no session is already running.
if (session_status() === PHP_SESSION_NONE) session_start();
// Sets the browser page title to 'Archived Students'.
$pageTitle = 'Archived Students';
// Tells the header layout that this page belongs to the coordinator role.
$role = 'coordinator';
// Marks the sidebar item 'archived_students' as the active menu entry.
$currentPage = 'archived_students';
// Gets the display name of the signed-in coordinator for the header.
$userName = currentCoordinatorName();
// Prepares an empty error message that is shown when something fails.
$error = '';
// Prepares an empty list that will hold the archived student rows.
$students = [];

// Opens a try block so database problems show a friendly error instead.
try {
    // Gets the shared PDO connection object used by all queries below.
    $pdo = DB::getConnection();

    // Section marker for the code that restores an archived student.
    // ---------- RESTORE ----------
    // Runs the restore handler only when the restore form was posted.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_id'])) {
        // Builds the UPDATE that clears archived_at for the chosen student.
        $stmt = $pdo->prepare('UPDATE students SET archived_at = NULL WHERE student_id = :id AND archived_at IS NOT NULL');
        // Runs the update with the posted restore_id cast to a whole number.
        $stmt->execute(['id' => (int) $_POST['restore_id']]);
        // Queues a green flash message telling the coordinator it worked.
        setFlash('success', 'Student restored to the active student list.');
        // Redirects back to this page so the browser form is not resubmitted.
        redirectTo('coordinator/students/archived.php');
    // Closes the restore handler block.
    }

    // Section marker for the code that permanently deletes a student.
    // ---------- PERMANENT DELETE ----------
    // Runs the delete handler only on a POST that includes delete_id.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
        // Casts the posted delete_id to an integer student id.
        $studentId = (int) $_POST['delete_id'];
        // Prepares an empty list of uploaded file names to erase from disk.
        $storedFiles = [];
        // Tracks whether the delete transaction succeeded.
        $deleted = false;

        // Starts an inner try block so a failed delete can be rolled back.
        try {
            // Begins a transaction so all the deletes succeed or fail together.
            $pdo->beginTransaction();

            // Note explaining that only archived students can be deleted here.
            // Only archived students can be deleted from this page.
            // Locks and reads the user_id of the archived student being deleted.
            $stmt = $pdo->prepare('SELECT user_id FROM students WHERE student_id = :id AND archived_at IS NOT NULL FOR UPDATE');
            // Runs the lookup with the student id taken from the form.
            $stmt->execute(['id' => $studentId]);
            // Fetches the matching row, or false when no archived student matched.
            $row = $stmt->fetch();

            // Only continues when an archived student row was actually found.
            if ($row) {
                // Note explaining file names are collected before the rows vanish.
                // Collect uploaded file names before the rows disappear.
                // Collects the stored filenames of documents owned by this user.
                $stmt = $pdo->prepare('SELECT stored_name FROM application_documents WHERE user_id = :uid');
                // Runs the document lookup with the student's user_id.
                $stmt->execute(['uid' => (int) $row['user_id']]);
                // Stores just the stored_name column values as a simple list.
                $storedFiles = $stmt->fetchAll(PDO::FETCH_COLUMN);

                // Note explaining users cascade to students to applications to documents.
                // Deleting the user account cascades to students -> applications -> documents.
                // Only deletes the account when a user_id was found.
                if (!empty($row['user_id'])) {
                    // Deletes the student's user account, limited to the student role.
                    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :uid AND role = 'student'");
                    // Runs the user delete with the user_id from the locked row.
                    $stmt->execute(['uid' => (int) $row['user_id']]);
                // Closes the user account delete block.
                }
                // Note explaining a fallback delete of the student row itself.
                // Safety net in case the student row still exists.
                // Deletes the archived student row if it still exists.
                $stmt = $pdo->prepare('DELETE FROM students WHERE student_id = :id AND archived_at IS NOT NULL');
                // Runs the fallback delete with the student id.
                $stmt->execute(['id' => $studentId]);

                // Records that the deletion succeeded so files can be removed next.
                $deleted = true;
            // Closes the 'archived row was found' block.
            }

            // Commits the transaction, making all the deletes permanent.
            $pdo->commit();
        // Catches any error thrown while deleting the student.
        } catch (Throwable $e) {
            // Rolls the transaction back when it is still open.
            if ($pdo->inTransaction()) $pdo->rollBack();
            // Marks the delete as failed after the rollback.
            $deleted = false;
            // Sets the error message shown on the page after a failed delete.
            $error = 'The student could not be deleted. Please try again.';
        // Closes the inner try and catch block.
        }

        // Runs the file cleanup only when the database delete succeeded.
        if ($deleted) {
            // Note about removing uploaded files from the upload folder.
            // Remove uploaded files from disk (adjust the folder to match your upload path).
            // Chooses the upload folder, falling back to the project uploads folder.
            $uploadDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : dirname(__DIR__, 2) . '/uploads/';
            // Loops over each stored filename collected earlier.
            foreach ($storedFiles as $file) {
                // Builds the full file path using the safe base file name.
                $path = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . basename($file);
                // Deletes the file from disk when it exists, ignoring errors.
                if (is_file($path)) @unlink($path);
            // Closes the file removal loop.
            }
            // Queues a success flash message for the permanent delete.
            setFlash('success', 'Student was permanently deleted from the system.');
            // Redirects back to the archive page to refresh the list.
            redirectTo('coordinator/students/archived.php');
        // Handles the case where nothing was deleted and no error was set.
        } elseif ($error === '') {
            // Sets a message saying the record was not found or not archived.
            $error = 'That student record was not found or is no longer archived.';
        // Closes the delete failure branch.
        }
    // Closes the permanent delete handler block.
    }

    // Reads every archived student with a formatted full name for the table.
    $stmt = $pdo->query("SELECT s.student_id, TRIM(CONCAT(s.last_name, ', ', s.first_name, IF(s.middle_initial IS NULL OR TRIM(REPLACE(s.middle_initial, '.', '')) = '', '', CONCAT(' ', TRIM(REPLACE(s.middle_initial, '.', '')))))) AS name, s.program, s.track, s.enrollment_date, s.archived_at FROM students s WHERE s.archived_at IS NOT NULL ORDER BY s.last_name ASC, s.first_name ASC");
    // Stores all the archived student rows used by the table below.
    $students = $stmt->fetchAll();
// Catches connection or query errors for the whole page.
} catch (Throwable $e) {
    // Shows a message asking the admin to apply the archive migration.
    $error = 'Archived student records are unavailable. Confirm that the archive database migration has been applied.';
// Closes the outer try and catch block.
}

// Loads the shared header, sidebar, and page shell.
require_once __DIR__ . '/../../includes/header.php';
// Closes PHP so the HTML below is printed to the browser.
?>
<?php /* Starts the page stylesheet for the confirmation dialogs. */ ?>
<style>
<?php /*     Comment marking the shared confirmation dialog styles. */ ?>
    /* ---------- Shared confirmation dialog (Delete + Restore) ---------- */
<?php /*     Styles the confirmation dialog box used for restore and delete. */ ?>
    .confirm-modal {
<?php /*         Limits the dialog width so it stays easy to read. */ ?>
        max-width: 480px;
<?php /*         Lets the dialog shrink to fit narrow screens. */ ?>
        width: 100%;
<?php /*         Left aligns the text inside the dialog. */ ?>
        text-align: left;
<?php /*         Removes the default top border of the dialog. */ ?>
        border-top: none;
<?php /*         Clips the header corners inside the rounded dialog. */ ?>
        overflow: hidden;
<?php /*     Closes the confirmation dialog rule. */ ?>
    }

<?php /*     Styles the dialog header that holds the icon and title. */ ?>
    .confirm-header {
<?php /*         Lays the icon and text out in one row. */ ?>
        display: flex;
<?php /*         Vertically centres the icon with the text. */ ?>
        align-items: center;
<?php /*         Adds space between the icon and the title text. */ ?>
        gap: 0.9rem;
<?php /*         Adds inner padding around the dialog header. */ ?>
        padding: 1.25rem 1.5rem;
<?php /*         Draws a light line under the dialog header. */ ?>
        border-bottom: 1px solid var(--gray-200);
<?php /*     Closes the dialog header rule. */ ?>
    }
<?php /*     Styles the round icon shown in the dialog header. */ ?>
    .confirm-icon {
<?php /*         Keeps the icon a fixed 44 pixel square. */ ?>
        flex: 0 0 44px;
<?php /*         Sets the icon width and height in pixels. */ ?>
        width: 44px; height: 44px;
<?php /*         Makes the icon perfectly round. */ ?>
        border-radius: 50%;
<?php /*         Centres the icon glyph inside the circle. */ ?>
        display: flex; align-items: center; justify-content: center;
<?php /*         Sets the size of the icon glyph. */ ?>
        font-size: 1.05rem;
<?php /*     Closes the icon rule. */ ?>
    }
<?php /*     Red icon style used by the delete dialog. */ ?>
    .confirm-icon.is-danger  { background: var(--danger-bg); color: var(--danger); }
<?php /*     Navy icon style used by the restore dialog. */ ?>
    .confirm-icon.is-primary { background: var(--gray-100);   color: var(--adzu-navy); }

<?php /*     Styles the h3 title inside the dialog header. */ ?>
    .confirm-header h3 {
<?php /*         Removes the default title margin. */ ?>
        margin: 0;
<?php /*         Uses the site heading font for the dialog title. */ ?>
        font-family: var(--font-heading);
<?php /*         Sets the dialog title text size. */ ?>
        font-size: 1.05rem;
<?php /*         Makes the dialog title bold. */ ?>
        font-weight: 700;
<?php /*         Colours the dialog title navy. */ ?>
        color: var(--adzu-navy);
<?php /*         Keeps the dialog title line height tight. */ ?>
        line-height: 1.3;
<?php /*     Closes the dialog title rule. */ ?>
    }
<?php /*     Styles the small subtitle under the dialog title. */ ?>
    .confirm-header .confirm-subtitle {
<?php /*         Adds a little space above the subtitle. */ ?>
        margin: 0.15rem 0 0;
<?php /*         Makes the subtitle smaller than the title. */ ?>
        font-size: 0.8rem;
<?php /*         Colours the subtitle grey. */ ?>
        color: var(--gray-500);
<?php /*     Closes the subtitle rule. */ ?>
    }

<?php /*     Adds padding inside the dialog body. */ ?>
    .confirm-body { padding: 1.25rem 1.5rem; }
<?php /*     Styles the main message paragraph of the dialog. */ ?>
    .confirm-message {
<?php /*         Adds space below the message. */ ?>
        margin: 0 0 1rem;
<?php /*         Sets the message text size. */ ?>
        font-size: 0.9rem;
<?php /*         Sets comfortable line spacing for the message. */ ?>
        line-height: 1.55;
<?php /*         Colours the message text grey. */ ?>
        color: var(--gray-600);
<?php /*     Closes the message rule. */ ?>
    }

<?php /*     Styles the box that lists the student details. */ ?>
    .confirm-details {
<?php /*         Adds space below the details box. */ ?>
        margin: 0 0 1rem;
<?php /*         Draws a thin grey border around the details box. */ ?>
        border: 1px solid var(--gray-200);
<?php /*         Rounds the corners of the details box. */ ?>
        border-radius: var(--radius-md, 8px);
<?php /*         Gives the details box a very light grey background. */ ?>
        background: var(--gray-50);
<?php /*     Closes the details box rule. */ ?>
    }
<?php /*     Styles one label and value row inside the details box. */ ?>
    .confirm-details-row {
<?php /*         Puts the label and value on one line. */ ?>
        display: flex;
<?php /*         Pushes the label left and the value right. */ ?>
        justify-content: space-between;
<?php /*         Aligns both texts along their baselines. */ ?>
        align-items: baseline;
<?php /*         Adds a gap between the label and the value. */ ?>
        gap: 1rem;
<?php /*         Adds padding inside each detail row. */ ?>
        padding: 0.6rem 0.9rem;
<?php /*         Sets the detail row text size. */ ?>
        font-size: 0.85rem;
<?php /*     Closes the detail row rule. */ ?>
    }
<?php /*     Adds a separator line between stacked detail rows. */ ?>
    .confirm-details-row + .confirm-details-row { border-top: 1px solid var(--gray-200); }
<?php /*     Styles the small uppercase label in a detail row. */ ?>
    .confirm-details-label {
<?php /*         Colours the detail label grey. */ ?>
        color: var(--gray-500);
<?php /*         Makes the detail label semi bold. */ ?>
        font-weight: 600;
<?php /*         Shows the detail label in uppercase letters. */ ?>
        text-transform: uppercase;
<?php /*         Adds slight spacing between the label letters. */ ?>
        letter-spacing: 0.04em;
<?php /*         Makes the detail label text small. */ ?>
        font-size: 0.72rem;
<?php /*         Stops the label from wrapping to a new line. */ ?>
        white-space: nowrap;
<?php /*     Closes the label rule. */ ?>
    }
<?php /*     Styles the value shown on the right of a detail row. */ ?>
    .confirm-details-value {
<?php /*         Colours the detail value navy. */ ?>
        color: var(--adzu-navy);
<?php /*         Makes the detail value semi bold. */ ?>
        font-weight: 600;
<?php /*         Aligns the detail value to the right. */ ?>
        text-align: right;
<?php /*         Breaks very long values so they do not overflow. */ ?>
        word-break: break-word;
<?php /*     Closes the value rule. */ ?>
    }

<?php /*     Styles the bullet list in the delete dialog. */ ?>
    .confirm-list {
<?php /*         Adds space below the bullet list. */ ?>
        margin: 0 0 1rem;
<?php /*         Indents the bullet markers. */ ?>
        padding-left: 1.15rem;
<?php /*         Sets the list text size. */ ?>
        font-size: 0.85rem;
<?php /*         Colours the list text grey. */ ?>
        color: var(--gray-600);
<?php /*         Adds line spacing between list items. */ ?>
        line-height: 1.7;
<?php /*     Closes the bullet list rule. */ ?>
    }

<?php /*     Styles the coloured note box at the bottom of a dialog. */ ?>
    .confirm-note {
<?php /*         Lays the note icon and text out in a row. */ ?>
        display: flex;
<?php /*         Adds a small gap between the icon and the text. */ ?>
        gap: 0.6rem;
<?php /*         Aligns the note content to the top. */ ?>
        align-items: flex-start;
<?php /*         Removes the default note margin. */ ?>
        margin: 0;
<?php /*         Adds padding inside the note box. */ ?>
        padding: 0.7rem 0.9rem;
<?php /*         Rounds the note box corners. */ ?>
        border-radius: var(--radius-md, 8px);
<?php /*         Sets the note text size. */ ?>
        font-size: 0.8rem;
<?php /*         Sets the note line height. */ ?>
        line-height: 1.5;
<?php /*     Closes the note box rule. */ ?>
    }
<?php /*     Nudges the note icon down to line up with the text. */ ?>
    .confirm-note i { margin-top: 0.15rem; }
<?php /*     Red note style used for the delete warning. */ ?>
    .confirm-note.is-danger  { background: var(--danger-bg); color: var(--danger); }
<?php /*     Neutral grey note style used for the restore information. */ ?>
    .confirm-note.is-neutral { background: var(--gray-100);  color: var(--gray-600); }

<?php /*     Styles the footer row holding the dialog buttons. */ ?>
    .confirm-footer {
<?php /*         Puts the footer buttons on one line. */ ?>
        display: flex;
<?php /*         Aligns the footer buttons to the right. */ ?>
        justify-content: flex-end;
<?php /*         Adds a gap between the two buttons. */ ?>
        gap: 0.6rem;
<?php /*         Adds padding around the footer. */ ?>
        padding: 1rem 1.5rem;
<?php /*         Gives the footer a light grey background. */ ?>
        background: var(--gray-50);
<?php /*         Draws a light line above the footer. */ ?>
        border-top: 1px solid var(--gray-200);
<?php /*     Closes the footer rule. */ ?>
    }
<?php /*     Gives footer buttons a minimum width and a centred label. */ ?>
    .confirm-footer .btn { min-width: 120px; justify-content: center; }

<?php /*     Mobile style block for screens narrower than 480 pixels. */ ?>
    @media (max-width: 480px) {
<?php /*         Stacks the footer buttons in reverse order on phones. */ ?>
        .confirm-footer { flex-direction: column-reverse; }
<?php /*         Makes each footer button full width on phones. */ ?>
        .confirm-footer .btn { width: 100%; }
<?php /*     Closes the mobile style block. */ ?>
    }

<?php /*     Removes the default margin around the action forms in the table. */ ?>
    .data-table td .actions form { margin: 0; }
<?php /* Closes the page style block. */ ?>
</style>

<?php /* Page heading that explains what the archive page is for. */ ?>
<div class="page-header"><h2>Archived Students</h2><p>View retained student records, restore a record when it becomes active again, or permanently delete it.</p></div>
<?php /* Shows a red alert box when an error message was set. */ ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php /* Opens the card that holds the archived students table. */ ?>
<div class="card"><div class="card-header"><h3>Archived Student Records <span class="filter-count" data-filter-count></span></h3></div><div class="card-body">
<?php /*     Search box and clear button that filter the archived table. */ ?>
    <div class="filter-bar" data-filter-table="#archivedStudentsTable"><div class="filter-search"><i class="fas fa-search"></i><input type="search" data-filter-q placeholder="Search archived students..."></div><button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button></div>
<?php /*     Table header naming the student, program, track, dates, and actions. */ ?>
    <div class="table-responsive"><table class="data-table" id="archivedStudentsTable"><thead><tr><th>Student</th><th>Program</th><th>Track</th><th>Enrolled</th><th>Archived</th><th>Actions</th></tr></thead><tbody>
<?php /*     Prints one table row per archived student with restore and delete buttons. */ ?>
    <?php foreach ($students as $student): ?><tr data-search="<?= htmlspecialchars(strtolower($student['name'] . ' ' . $student['program'] . ' ' . $student['track'])) ?>"><td><strong><?= htmlspecialchars($student['name']) ?></strong></td><td><?= htmlspecialchars($student['program']) ?></td><td><?= htmlspecialchars(getTrackLabel($student['track'])) ?></td><td><?= htmlspecialchars(date('M d, Y', strtotime($student['enrollment_date']))) ?></td><td><?= htmlspecialchars(date('M d, Y g:i A', strtotime($student['archived_at']))) ?></td><td><div class="actions"><button type="button" class="btn btn-sm btn-primary" data-restore-trigger data-student-id="<?= (int) $student['student_id'] ?>" data-student-name="<?= htmlspecialchars($student['name']) ?>" data-student-program="<?= htmlspecialchars($student['program']) ?>"><i class="fas fa-undo"></i> Restore</button><button type="button" class="btn btn-sm btn-danger" data-delete-trigger data-student-id="<?= (int) $student['student_id'] ?>" data-student-name="<?= htmlspecialchars($student['name']) ?>" data-student-program="<?= htmlspecialchars($student['program']) ?>"><i class="fas fa-trash-alt"></i> Delete</button></div></td></tr><?php endforeach; ?>
<?php /*     Placeholder row shown when no archived students match the filter. */ ?>
    <tr data-filter-empty <?= $students ? 'hidden' : '' ?>><td colspan="6" style="text-align:center;color:var(--gray-400);padding:2rem;">No archived students found.</td></tr>
<?php /*     Closes the table body and the table wrapper. */ ?>
    </tbody></table></div>
<?php /* Closes the card body and the card. */ ?>
</div></div>

<?php /* Comment marking the restore confirmation pop-up. */ ?>
<!-- Restore confirmation pop-up -->
<?php /* Starts the hidden overlay for the restore dialog. */ ?>
<div class="modal-overlay" id="restoreStudentModal" role="dialog" aria-modal="true" aria-labelledby="restoreModalTitle">
<?php /*     Opens the dialog box inside the restore overlay. */ ?>
    <div class="modal confirm-modal">
<?php /*         Form that posts the restore request back to this page. */ ?>
        <form method="post" id="restoreStudentForm">
<?php /*             Hidden field that carries the student id to restore. */ ?>
            <input type="hidden" name="restore_id" id="restoreStudentId" value="">
<?php /*             Opens the header of the restore dialog. */ ?>
            <div class="confirm-header">
<?php /*                 Round navy icon shown in the restore header. */ ?>
                <div class="confirm-icon is-primary"><i class="fas fa-undo"></i></div>
<?php /*                 Opens the wrapper for the restore title text. */ ?>
                <div>
<?php /*                     Restore dialog title seen by the coordinator. */ ?>
                    <h3 id="restoreModalTitle">Restore Student Record</h3>
<?php /*                     Subtitle explaining the record returns to the active list. */ ?>
                    <p class="confirm-subtitle">Return this record to the active student list.</p>
<?php /*                 Closes the title text wrapper. */ ?>
                </div>
<?php /*             Closes the restore dialog header. */ ?>
            </div>
<?php /*             Opens the body of the restore dialog. */ ?>
            <div class="confirm-body">
<?php /*                 Message asking the coordinator to confirm the restore. */ ?>
                <p class="confirm-message">Please confirm that you want to restore the following student.</p>
<?php /*                 Opens the details box in the restore dialog. */ ?>
                <div class="confirm-details">
<?php /*                     Detail row for the student name. */ ?>
                    <div class="confirm-details-row">
<?php /*                         Label text 'Student' in the detail row. */ ?>
                        <span class="confirm-details-label">Student</span>
<?php /*                         Placeholder filled by JavaScript with the student name. */ ?>
                        <span class="confirm-details-value" id="restoreStudentName"></span>
<?php /*                     Closes the student detail row. */ ?>
                    </div>
<?php /*                     Detail row for the student's program. */ ?>
                    <div class="confirm-details-row">
<?php /*                         Label text 'Program' in the detail row. */ ?>
                        <span class="confirm-details-label">Program</span>
<?php /*                         Placeholder filled by JavaScript with the program name. */ ?>
                        <span class="confirm-details-value" id="restoreStudentProgram"></span>
<?php /*                     Closes the program detail row. */ ?>
                    </div>
<?php /*                 Closes the details box. */ ?>
                </div>
<?php /*                 Neutral information note about what the restore does. */ ?>
                <p class="confirm-note is-neutral">
<?php /*                     Information icon at the start of the note. */ ?>
                    <i class="fas fa-info-circle"></i>
<?php /*                     Explains the student will return to the active list. */ ?>
                    <span>The student will be removed from the archive and will appear in the active student list again.</span>
<?php /*                 Closes the information note. */ ?>
                </p>
<?php /*             Closes the restore dialog body. */ ?>
            </div>
<?php /*             Opens the footer with the cancel and confirm buttons. */ ?>
            <div class="confirm-footer">
<?php /*                 Cancel button that closes the restore dialog. */ ?>
                <button type="button" class="btn btn-outline" id="restoreCancelBtn">Cancel</button>
<?php /*                 Submit button that restores the student. */ ?>
                <button type="submit" class="btn btn-primary" id="restoreConfirmBtn"><i class="fas fa-undo"></i> Restore Student</button>
<?php /*             Closes the restore dialog footer. */ ?>
            </div>
<?php /*         Closes the restore form. */ ?>
        </form>
<?php /*     Closes the restore dialog box. */ ?>
    </div>
<?php /* Closes the restore overlay. */ ?>
</div>

<?php /* Comment marking the permanent delete confirmation pop-up. */ ?>
<!-- Delete confirmation pop-up -->
<?php /* Starts the hidden overlay for the delete dialog. */ ?>
<div class="modal-overlay" id="deleteStudentModal" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
<?php /*     Opens the delete dialog box. */ ?>
    <div class="modal confirm-modal">
<?php /*         Form that posts the delete request back to this page. */ ?>
        <form method="post" id="deleteStudentForm">
<?php /*             Hidden field that carries the student id to delete. */ ?>
            <input type="hidden" name="delete_id" id="deleteStudentId" value="">
<?php /*             Opens the header of the delete dialog. */ ?>
            <div class="confirm-header">
<?php /*                 Round red icon shown in the delete header. */ ?>
                <div class="confirm-icon is-danger"><i class="fas fa-trash-alt"></i></div>
<?php /*                 Opens the wrapper for the delete title text. */ ?>
                <div>
<?php /*                     Delete dialog title saying the action is permanent. */ ?>
                    <h3 id="deleteModalTitle">Permanently Delete Student</h3>
<?php /*                     Subtitle warning that the action cannot be undone. */ ?>
                    <p class="confirm-subtitle">This action is permanent and cannot be undone.</p>
<?php /*                 Closes the title text wrapper. */ ?>
                </div>
<?php /*             Closes the delete dialog header. */ ?>
            </div>
<?php /*             Opens the body of the delete dialog. */ ?>
            <div class="confirm-body">
<?php /*                 Message asking the coordinator to confirm the deletion. */ ?>
                <p class="confirm-message">Please confirm that you want to permanently delete the following student.</p>
<?php /*                 Opens the details box in the delete dialog. */ ?>
                <div class="confirm-details">
<?php /*                     Detail row for the student name in the delete dialog. */ ?>
                    <div class="confirm-details-row">
<?php /*                         Label text 'Student' in the delete detail row. */ ?>
                        <span class="confirm-details-label">Student</span>
<?php /*                         Placeholder filled by JavaScript with the student name. */ ?>
                        <span class="confirm-details-value" id="deleteStudentName"></span>
<?php /*                     Closes the student detail row. */ ?>
                    </div>
<?php /*                     Detail row for the program in the delete dialog. */ ?>
                    <div class="confirm-details-row">
<?php /*                         Label text 'Program' in the delete detail row. */ ?>
                        <span class="confirm-details-label">Program</span>
<?php /*                         Placeholder filled by JavaScript with the program name. */ ?>
                        <span class="confirm-details-value" id="deleteStudentProgram"></span>
<?php /*                     Closes the program detail row. */ ?>
                    </div>
<?php /*                 Closes the delete details box. */ ?>
                </div>
<?php /*                 Bullet list naming everything that will be removed. */ ?>
                <ul class="confirm-list">
<?php /*                     First item: the student account will be removed. */ ?>
                    <li>Student account</li>
<?php /*                     Second item: submitted applications will be removed. */ ?>
                    <li>Submitted applications</li>
<?php /*                     Third item: uploaded documents will be removed. */ ?>
                    <li>Uploaded documents</li>
<?php /*                 Closes the removal bullet list. */ ?>
                </ul>
<?php /*                 Red warning note shown under the bullet list. */ ?>
                <p class="confirm-note is-danger">
<?php /*                     Warning triangle icon at the start of the note. */ ?>
                    <i class="fas fa-exclamation-triangle"></i>
<?php /*                     States that all the listed items are deleted permanently. */ ?>
                    <span>All of the items above will be permanently removed from the system and database.</span>
<?php /*                 Closes the warning note. */ ?>
                </p>
<?php /*             Closes the delete dialog body. */ ?>
            </div>
<?php /*             Opens the footer with the cancel and delete buttons. */ ?>
            <div class="confirm-footer">
<?php /*                 Cancel button that closes the delete dialog. */ ?>
                <button type="button" class="btn btn-outline" id="deleteCancelBtn">Cancel</button>
<?php /*                 Submit button that permanently deletes the student. */ ?>
                <button type="submit" class="btn btn-danger" id="deleteConfirmBtn"><i class="fas fa-trash-alt"></i> Delete Student</button>
<?php /*             Closes the delete dialog footer. */ ?>
            </div>
<?php /*         Closes the delete form. */ ?>
        </form>
<?php /*     Closes the delete dialog box. */ ?>
    </div>
<?php /* Closes the delete overlay. */ ?>
</div>

<?php /* Starts the JavaScript that wires up both confirmation dialogs. */ ?>
<script>
<?php /* Wraps the dialog script so no variables leak into the global scope. */ ?>
(function () {
<?php /*     Comment describing the reusable confirmation modal helper. */ ?>
    // Reusable controller for a confirmation modal.
<?php /*     Defines a function that connects one modal to its trigger buttons. */ ?>
    function setupConfirmModal(cfg) {
<?php /*         Finds the modal overlay element by its id. */ ?>
        var overlay    = document.getElementById(cfg.overlay);
<?php /*         Finds the form inside the modal. */ ?>
        var form       = document.getElementById(cfg.form);
<?php /*         Finds the hidden input that stores the student id. */ ?>
        var idInput    = document.getElementById(cfg.idInput);
<?php /*         Finds the element that shows the student name. */ ?>
        var nameEl     = document.getElementById(cfg.name);
<?php /*         Finds the element that shows the program name. */ ?>
        var programEl  = document.getElementById(cfg.program);
<?php /*         Finds the cancel button of the dialog. */ ?>
        var cancelBtn  = document.getElementById(cfg.cancel);
<?php /*         Finds the confirm submit button of the dialog. */ ?>
        var confirmBtn = document.getElementById(cfg.confirm);

<?php /*         Defines the open function that fills and shows the modal. */ ?>
        function open(id, name, program) {
<?php /*             Puts the chosen student id into the hidden field. */ ?>
            idInput.value = id;
<?php /*             Shows the clicked student name inside the dialog. */ ?>
            nameEl.textContent = name;
<?php /*             Shows the program, or a dash when it is empty. */ ?>
            programEl.textContent = program || '—';
<?php /*             Re-enables the confirm button each time the dialog opens. */ ?>
            confirmBtn.disabled = false;
<?php /*             Adds the active class so the overlay becomes visible. */ ?>
            overlay.classList.add('active');
<?php /*             Locks page scrolling while the dialog is open. */ ?>
            document.body.style.overflow = 'hidden';
<?php /*             Focuses Cancel as the safe default action. */ ?>
            cancelBtn.focus(); // safe default: Cancel
<?php /*         Closes the open function. */ ?>
        }

<?php /*         Defines the close function that hides and resets the modal. */ ?>
        function close() {
<?php /*             Removes the active class so the overlay hides again. */ ?>
            overlay.classList.remove('active');
<?php /*             Restores normal page scrolling. */ ?>
            document.body.style.overflow = '';
<?php /*             Clears the stored student id from the hidden field. */ ?>
            idInput.value = '';
<?php /*             Clears the student name text. */ ?>
            nameEl.textContent = '';
<?php /*             Clears the program text. */ ?>
            programEl.textContent = '';
<?php /*         Closes the close function. */ ?>
        }

<?php /*         Comment explaining that any trigger button opens the modal. */ ?>
        // Open from any trigger button (works with filtered rows too)
<?php /*         Listens for clicks anywhere on the page. */ ?>
        document.addEventListener('click', function (e) {
<?php /*             Finds the trigger button closest to the clicked element. */ ?>
            var btn = e.target.closest(cfg.trigger);
<?php /*             Runs only when a matching trigger button was clicked. */ ?>
            if (btn) {
<?php /*                 Opens the dialog with the data attributes from the clicked button. */ ?>
                open(
<?php /*                     Reads the student id from the button. */ ?>
                    btn.getAttribute('data-student-id'),
<?php /*                     Reads the student name from the button. */ ?>
                    btn.getAttribute('data-student-name'),
<?php /*                     Reads the program name from the button. */ ?>
                    btn.getAttribute('data-student-program')
<?php /*                 Closes the open call arguments. */ ?>
                );
<?php /*             Closes the 'button clicked' block. */ ?>
            }
<?php /*         Closes the document click listener. */ ?>
        });

<?php /*         Closes the modal when the cancel button is clicked. */ ?>
        cancelBtn.addEventListener('click', close);

<?php /*         Comment explaining that clicking outside the box cancels. */ ?>
        // Click outside the box cancels
<?php /*         Listens for clicks on the overlay itself. */ ?>
        overlay.addEventListener('click', function (e) {
<?php /*             Closes the modal only when the dark backdrop was clicked. */ ?>
            if (e.target === overlay) close();
<?php /*         Closes the overlay click listener. */ ?>
        });

<?php /*         Comment explaining that the Escape key cancels. */ ?>
        // Esc cancels
<?php /*         Listens for key presses on the document. */ ?>
        document.addEventListener('keydown', function (e) {
<?php /*             Closes the open modal when Escape is pressed. */ ?>
            if (e.key === 'Escape' && overlay.classList.contains('active')) close();
<?php /*         Closes the keydown listener. */ ?>
        });

<?php /*         Comment explaining that double submits are prevented. */ ?>
        // Prevent double submit
<?php /*         Listens for the form submit so the button cannot be clicked twice. */ ?>
        form.addEventListener('submit', function () {
<?php /*             Disables the confirm button while the form is being sent. */ ?>
            confirmBtn.disabled = true;
<?php /*         Closes the submit listener. */ ?>
        });
<?php /*     Closes the setupConfirmModal function. */ ?>
    }

<?php /*     Wires the restore dialog to its restore trigger buttons. */ ?>
    setupConfirmModal({
<?php /*         Passes the restore overlay and form element ids. */ ?>
        overlay: 'restoreStudentModal', form: 'restoreStudentForm',
<?php /*         Passes the restore id, name, and program element ids. */ ?>
        idInput: 'restoreStudentId', name: 'restoreStudentName', program: 'restoreStudentProgram',
<?php /*         Passes the restore cancel and confirm button ids. */ ?>
        cancel: 'restoreCancelBtn', confirm: 'restoreConfirmBtn',
<?php /*         Sets the selector that finds all restore trigger buttons. */ ?>
        trigger: '[data-restore-trigger]'
<?php /*     Closes the restore dialog setup call. */ ?>
    });

<?php /*     Wires the delete dialog to its delete trigger buttons. */ ?>
    setupConfirmModal({
<?php /*         Passes the delete overlay and form element ids. */ ?>
        overlay: 'deleteStudentModal', form: 'deleteStudentForm',
<?php /*         Passes the delete id, name, and program element ids. */ ?>
        idInput: 'deleteStudentId', name: 'deleteStudentName', program: 'deleteStudentProgram',
<?php /*         Passes the delete cancel and confirm button ids. */ ?>
        cancel: 'deleteCancelBtn', confirm: 'deleteConfirmBtn',
<?php /*         Sets the selector that finds all delete trigger buttons. */ ?>
        trigger: '[data-delete-trigger]'
<?php /*     Closes the delete dialog setup call. */ ?>
    });
<?php /* Closes and immediately runs the dialog script wrapper. */ ?>
})();
<?php /* Ends the JavaScript block. */ ?>
</script>
<?php /* Loads the shared footer and closes the page. */ ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>