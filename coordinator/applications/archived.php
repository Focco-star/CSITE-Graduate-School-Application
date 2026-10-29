<?php /* Opens the PHP block for the archived applications page. */ ?>
<?php
// Loads shared settings and helper functions.
require_once __DIR__ . '/../../includes/config/config.php';
// Loads the database connection helper class DB.
require_once __DIR__ . '/../../includes/config/db.php';

// Starts the session when none is active so flash messages work.
if (session_status() === PHP_SESSION_NONE) session_start();
// Sets the page title to 'Archived Applications'.
$pageTitle = 'Archived Applications';
// Tells the layout this page is used by a coordinator.
$role = 'coordinator';
// Marks the archived applications menu item as active.
$currentPage = 'archived_applications';
// Gets the display name of the signed-in coordinator.
$userName = currentCoordinatorName();
// Prepares an empty error message for the page.
$error = '';
// Starts with an empty list of archived applications.
$applications = [];

// Starts a block that may throw database errors.
try {
    // Gets the shared MySQL connection object.
    $pdo = DB::getConnection();

    // Comment noting the restore action below.
    // Restore
    // Runs the restore action when the restore form is posted.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_id'])) {
        // Clears archived_at so the application becomes active again.
        $stmt = $pdo->prepare('UPDATE applications SET archived_at = NULL WHERE application_id = :id AND archived_at IS NOT NULL');
        // Runs the restore using the posted id as an integer.
        $stmt->execute(['id' => (int) $_POST['restore_id']]);
        // Queues a success message about the restored application.
        setFlash('success', 'The application has been restored to the active application list.');
        // Reloads the archived page so the list updates.
        redirectTo('coordinator/applications/archived.php');
    // Closes the restore action block.
    }

    // Comment noting that permanent delete is allowed only for archived rows.
    // Permanent delete (archived applications only)
    // Runs the permanent delete action when that form is posted.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
        // Reads the application id to delete as an integer.
        $deleteId = (int) $_POST['delete_id'];
        // Starts a transaction so the deletes succeed or fail together.
        $pdo->beginTransaction();
        // Starts a guarded block for the delete work.
        try {
            // Looks up the application and confirms that it is archived.
            $check = $pdo->prepare('SELECT application_id FROM applications WHERE application_id = :id AND archived_at IS NOT NULL');
            // Runs the lookup with the posted id.
            $check->execute(['id' => $deleteId]);

            // Continues only when the archived application exists.
            if ($check->fetchColumn()) {
                // Finds the stored file names of the application documents.
                $docStmt = $pdo->prepare('SELECT stored_name FROM application_documents WHERE application_id = :id');
                // Runs the document lookup for this application.
                $docStmt->execute(['id' => $deleteId]);
                // Collects the stored file names into a simple list.
                $storedFiles = $docStmt->fetchAll(PDO::FETCH_COLUMN);

                // Deletes the document rows linked to the application.
                $pdo->prepare('DELETE FROM application_documents WHERE application_id = :id')->execute(['id' => $deleteId]);
                // Deletes the archived application row itself.
                $pdo->prepare('DELETE FROM applications WHERE application_id = :id AND archived_at IS NOT NULL')->execute(['id' => $deleteId]);
                // Saves both deletes permanently.
                $pdo->commit();

                // Only removes files when the upload folder is configured.
                if (defined('UPLOAD_DIR')) {
                    // Loops over each stored file name.
                    foreach ($storedFiles as $file) {
                        // Builds the full file path inside the upload folder.
                        $path = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . basename($file);
                        // Deletes the file from disk when it exists.
                        if (is_file($path)) @unlink($path);
                    // Closes the file loop.
                    }
                // Closes the upload folder check.
                }
                // Tells the coordinator the application was deleted.
                setFlash('success', 'The application has been permanently deleted.');
            // Runs when the id is not an archived application.
            } else {
                // Cancels the transaction because nothing was deleted.
                $pdo->rollBack();
                // Shows an error saying the record is missing or not archived.
                setFlash('error', 'The application could not be found or is not archived.');
            // Closes the found-or-not condition.
            }
        // Catches any failure during the delete.
        } catch (Throwable $e) {
            // Rolls back the transaction when it is still open.
            if ($pdo->inTransaction()) $pdo->rollBack();
            // Re-throws the error so the outer catch can show a message.
            throw $e;
        // Closes the delete try/catch.
        }
        // Reloads the archived list after the delete attempt.
        redirectTo('coordinator/applications/archived.php');
    // Closes the permanent delete block.
    }

    // Starts the query that loads all archived applications.
    $stmt = $pdo->query(
        // This SQL text selects archived applications with student, program, and dates.
        "SELECT
            a.application_id AS id,
            COALESCE(
                NULLIF(TRIM(CONCAT_WS(' ', s.first_name, NULLIF(TRIM(REPLACE(s.middle_initial, '.', '')), ''), s.last_name)), ''), 
                u.full_name
            ) AS student,
            CASE 
                WHEN s.program LIKE '%Computer Science%' THEN 'MSCS'
                WHEN s.program LIKE '%Information Technology%' THEN 'MIT'
                WHEN s.program LIKE '%Mathematics%' THEN 'MATH'
                ELSE s.program 
            END AS program,
            s.track AS db_track,
            a.presentation_stage AS stage,
            a.paper_title AS title,
            a.submitted_at AS date,
            a.status,
            a.archived_at
        FROM applications a
        INNER JOIN users u ON u.user_id = a.user_id
        LEFT JOIN students s ON s.user_id = u.user_id
        WHERE a.archived_at IS NOT NULL
        ORDER BY a.archived_at DESC"
    // Closes the query call after the SQL text.
    );
    // Stores all archived application rows for the table.
    $applications = $stmt->fetchAll();
// Catches failures such as a missing database column.
} catch (Throwable $e) {
    // Sets a friendly error when archived records cannot be loaded.
    $error = 'Archived application records are unavailable. Confirm that the application archive database migration has been applied.';
// Closes the outer try/catch.
}

// Loads the shared header, sidebar, and styles.
require_once __DIR__ . '/../../includes/layout/header.php';
// Closes PHP so the HTML can be written.
?>
<?php /* Starts page-specific CSS for the confirmation modal. */ ?>
<style>
<?php /* Styles the dark full-screen modal backdrop. */ ?>
.confirm-overlay{position:fixed;inset:0;background:rgba(17,24,39,.5);display:none;align-items:center;justify-content:center;z-index:2000;padding:1rem;}
<?php /* Shows the overlay as a flex box when it has the open class. */ ?>
.confirm-overlay.open{display:flex;}
<?php /* Styles the white modal box with rounded corners and shadow. */ ?>
.confirm-modal{background:#fff;border:1px solid #d1d5db;border-radius:6px;width:100%;max-width:540px;box-shadow:0 10px 30px rgba(0,0,0,.18);display:flex;flex-direction:column;max-height:90vh;overflow:hidden;}
<?php /* Styles the modal header row. */ ?>
.confirm-header{display:flex;align-items:center;gap:.75rem;padding:1.1rem 1.5rem;border-bottom:1px solid #e5e7eb;}
<?php /* Styles the icon shown at the left of the header. */ ?>
.confirm-icon{width:auto;height:auto;background:none;color:#4b5563;font-size:1.05rem;flex-shrink:0;}
<?php /* Styles the modal title text. */ ?>
.confirm-header h3{margin:0;font-size:1.05rem;font-weight:600;color:#111827;}
<?php /* Adds padding and scrolling to the modal body. */ ?>
.confirm-body{padding:1.25rem 1.5rem;overflow-y:auto;}
<?php /* Styles the intro paragraph inside the modal. */ ?>
.confirm-intro{margin:0 0 1rem;color:#374151;font-size:.92rem;line-height:1.55;}
<?php /* Styles the details table with collapsed borders. */ ?>
.confirm-details{width:100%;border-collapse:collapse;font-size:.88rem;border:1px solid #e5e7eb;}
<?php /* Adds a divider line between detail rows. */ ?>
.confirm-details tr+tr{border-top:1px solid #e5e7eb;}
<?php /* Styles the label cells with a grey background. */ ?>
.confirm-details th{text-align:left;width:30%;padding:.55rem .85rem;background:#f9fafb;color:#4b5563;font-weight:600;vertical-align:top;}
<?php /* Styles the value cells and wraps long text. */ ?>
.confirm-details td{padding:.55rem .85rem;color:#111827;word-break:break-word;}
<?php /* Styles the notice box that warns about the action. */ ?>
.confirm-notice{margin-top:1rem;padding:.7rem .9rem;background:#f9fafb;border:1px solid #e5e7eb;border-radius:4px;color:#374151;font-size:.85rem;line-height:1.5;}
<?php /* Lays out the footer buttons at opposite ends. */ ?>
.confirm-footer{display:flex;justify-content:space-between;align-items:center;padding:.9rem 1.5rem;border-top:1px solid #e5e7eb;background:#fff;}

<?php /* Gives the restore button a yellow Ateneo style. */ ?>
.confirm-submit.is-restore{background:#f5b800;border:1px solid #f5b800;color:#1f2937;font-weight:600;}
<?php /* Darkens the restore button on hover. */ ?>
.confirm-submit.is-restore:hover{background:#d9a300;border-color:#d9a300;}
<?php /* Gives the delete button a red danger style. */ ?>
.confirm-submit.is-delete{background:#b91c1c;border:1px solid #b91c1c;color:#fff;font-weight:600;}
<?php /* Darkens the delete button on hover. */ ?>
.confirm-submit.is-delete:hover{background:#991b1b;border-color:#991b1b;}
<?php /* Ends the page-specific CSS. */ ?>
</style>

<?php /* Page banner with the heading and an explanation of this page. */ ?>
<div class="page-header page-banner"><h2>Archived Applications</h2><p>View retained application records, restore an application when it becomes active again, or permanently delete it.</p></div>
<?php /* Shows the error alert only when a load error exists. */ ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php /* Opens the card around the archived application table. */ ?>
<div class="card"><div class="card-header"><h3>Archived Application Records <span class="filter-count" data-filter-count></span></h3></div><div class="card-body">
<?php /*     Search bar and clear button for the archived table. */ ?>
    <div class="filter-bar" data-filter-table="#archivedApplicationsTable"><div class="filter-search"><i class="fas fa-search"></i><input type="search" data-filter-q placeholder="Search archived applications..."></div><button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button></div>
<?php /*     Starts the archived table with its eight column headers. */ ?>
    <div class="table-responsive"><table class="data-table" id="archivedApplicationsTable"><thead><tr><th>Student</th><th>Program</th><th>Track</th><th>Stage</th><th>Title</th><th>Submitted</th><th>Archived</th><th>Actions</th></tr></thead><tbody>
<?php /*     Loops over every archived application row. */ ?>
    <?php foreach ($applications as $app):
        // Gets the stored track key, falling back to the program default.
        $rawTrack = $app['db_track'] ?? (function_exists('getTrackForProgram') ? getTrackForProgram($app['program']) : 'thesis');
        // Converts the track key into a readable label.
        $track = function_exists('getTrackLabel') ? getTrackLabel($rawTrack) : ucfirst($rawTrack);
        // Formats the submission date as Mon dd, yyyy.
        $submittedFmt = date('M d, Y', strtotime($app['date']));
        // Formats the archive date with date and time.
        $archivedFmt = date('M d, Y g:i A', strtotime($app['archived_at']));
        // Starts building the data attributes passed to the action buttons.
        $dataAttrs =
            // Adds the application id to the attributes.
            'data-id="' . (int) $app['id'] . '"' .
            // Adds the escaped student name.
            ' data-student="' . htmlspecialchars($app['student']) . '"' .
            // Adds the escaped program code.
            ' data-program="' . htmlspecialchars($app['program']) . '"' .
            // Adds the escaped track label.
            ' data-track="' . htmlspecialchars($track) . '"' .
            // Adds the escaped stage name.
            ' data-stage="' . htmlspecialchars($app['stage']) . '"' .
            // Adds the escaped paper title.
            ' data-title="' . htmlspecialchars($app['title']) . '"' .
            // Adds the status with underscores turned into spaces.
            ' data-status="' . htmlspecialchars(ucwords(str_replace('_', ' ', (string) $app['status']))) . '"' .
            // Adds the formatted submission date.
            ' data-submitted="' . htmlspecialchars($submittedFmt) . '"' .
            // Adds the formatted archive date and closes the attribute string.
            ' data-archived="' . htmlspecialchars($archivedFmt) . '"';
    // Closes PHP before the table row markup.
    ?>
<?php /*     Table row showing student, program, track, stage, title, and dates. */ ?>
    <tr data-search="<?= htmlspecialchars(strtolower($app['student'] . ' ' . $app['program'] . ' ' . $track . ' ' . $app['stage'] . ' ' . $app['title'])) ?>"><td><strong><?= htmlspecialchars($app['student']) ?></strong></td><td><?= htmlspecialchars($app['program']) ?></td><td><span style="font-size:0.75rem;background:var(--gray-100);padding:2px 8px;border-radius:99px;"><?= htmlspecialchars($track) ?></span></td><td><?= htmlspecialchars($app['stage']) ?></td><td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($app['title']) ?></td><td><?= htmlspecialchars($submittedFmt) ?></td><td><?= htmlspecialchars($archivedFmt) ?></td><td class="actions">
<?php /*         Eye button that opens the read-only application view. */ ?>
        <a href="<?= url('coordinator/applications/view.php?id=' . (int) $app['id']) ?>" class="btn btn-sm btn-outline" title="View"><i class="fas fa-eye"></i></a>
<?php /*         Restore button that opens the confirmation modal. */ ?>
        <button type="button" class="btn btn-sm btn-primary" data-confirm-action="restore" <?= $dataAttrs ?>><i class="fas fa-undo"></i> Restore</button>
<?php /*         Delete button that opens the confirmation modal. */ ?>
        <button type="button" class="btn btn-sm btn-danger" data-confirm-action="delete" <?= $dataAttrs ?>><i class="fas fa-trash"></i> Delete</button>
<?php /*     Ends the loop over archived applications. */ ?>
    </td></tr><?php endforeach; ?>
<?php /*     Row shown when there are no archived applications. */ ?>
    <tr data-filter-empty <?= $applications ? 'hidden' : '' ?>><td colspan="8" style="text-align:center;color:var(--gray-400);padding:2rem;">No archived applications found.</td></tr>
<?php /*     Closes the table body and the table element. */ ?>
    </tbody></table></div>
<?php /* Closes the card body and the card. */ ?>
</div></div>

<?php /* Note that the modal below is shared by restore and delete. */ ?>
<!-- Shared confirmation modal (Restore / Delete) -->
<?php /* Starts the shared confirmation modal overlay. */ ?>
<div class="confirm-overlay" id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
<?php /*     Form that posts the chosen action id back to this page. */ ?>
    <form method="post" class="confirm-modal" id="confirmForm">
<?php /*         Hidden field whose name is set to restore_id or delete_id. */ ?>
        <input type="hidden" name="" id="confirmId" value="">
<?php /*         Opens the modal header. */ ?>
        <div class="confirm-header">
<?php /*             Placeholder icon that changes with the action. */ ?>
            <div class="confirm-icon"><i class="fas" id="confirmIcon"></i></div>
<?php /*             Modal title filled by the script. */ ?>
            <h3 id="confirmTitle"></h3>
<?php /*         Closes the modal header. */ ?>
        </div>
<?php /*         Opens the modal body. */ ?>
        <div class="confirm-body">
<?php /*             Intro text filled by the script. */ ?>
            <p class="confirm-intro" id="confirmIntro"></p>
<?php /*             Starts the details table. */ ?>
            <table class="confirm-details">
<?php /*                 Row for the student name. */ ?>
                <tr><th>Student</th><td id="cfStudent"></td></tr>
<?php /*                 Row for the program. */ ?>
                <tr><th>Program</th><td id="cfProgram"></td></tr>
<?php /*                 Row for the track. */ ?>
                <tr><th>Track</th><td id="cfTrack"></td></tr>
<?php /*                 Row for the stage. */ ?>
                <tr><th>Stage</th><td id="cfStage"></td></tr>
<?php /*                 Row for the paper title. */ ?>
                <tr><th>Title</th><td id="cfTitle"></td></tr>
<?php /*                 Row for the application status. */ ?>
                <tr><th>Status</th><td id="cfStatus"></td></tr>
<?php /*                 Row for the submission date. */ ?>
                <tr><th>Submitted</th><td id="cfSubmitted"></td></tr>
<?php /*                 Row for the archive date. */ ?>
                <tr><th>Archived</th><td id="cfArchived"></td></tr>
<?php /*             Closes the details table. */ ?>
            </table>
<?php /*             Notice box whose warning text is set by the script. */ ?>
            <div class="confirm-notice" id="confirmNotice"></div>
<?php /*         Closes the modal body. */ ?>
        </div>
<?php /*         Opens the modal footer. */ ?>
        <div class="confirm-footer">
<?php /*             Go Back button that closes the modal. */ ?>
            <button type="button" class="btn btn-outline" id="confirmCancel">Go Back</button>
<?php /*             Confirm button whose label and style depend on the action. */ ?>
            <button type="submit" class="btn confirm-submit" id="confirmSubmit"></button>
<?php /*         Closes the modal footer. */ ?>
        </div>
<?php /*     Closes the confirmation form. */ ?>
    </form>
<?php /* Closes the confirmation overlay. */ ?>
</div>

<?php /* Starts the JavaScript for the confirmation modal. */ ?>
<script>
<?php /* Wraps the modal script in its own scope. */ ?>
(function () {
<?php /*     Finds the confirmation modal element. */ ?>
    var modal = document.getElementById('confirmModal');
<?php /*     Finds the hidden input that carries the id. */ ?>
    var idInput = document.getElementById('confirmId');

<?php /*     Starts the settings for the restore and delete actions. */ ?>
    var config = {
<?php /*         Starts the restore action settings. */ ?>
        restore: {
<?php /*             Posts the id under the name restore_id. */ ?>
            field: 'restore_id',
<?php /*             Uses the yellow restore button style. */ ?>
            btnClass: 'is-restore',
<?php /*             Uses the undo icon. */ ?>
            icon: 'fa-undo',
<?php /*             Sets the modal title for restoring. */ ?>
            title: 'Restore Application',
<?php /*             Sets the restore explanation text. */ ?>
            intro: 'You are about to restore the following application to the active application list. Please review the details below before proceeding.',
<?php /*             Explains what happens after restoring. */ ?>
            notice: 'Once restored, the application will reappear in the active list together with its current status and submitted documents.',
<?php /*             Label for the restore submit button. */ ?>
            submit: 'Restore Application'
<?php /*         Ends the restore settings. */ ?>
        },
<?php /*         Starts the delete action settings. */ ?>
        delete: {
<?php /*             Posts the id under the name delete_id. */ ?>
            field: 'delete_id',
<?php /*             Uses the red delete button style. */ ?>
            btnClass: 'is-delete',
<?php /*             Uses the trash icon. */ ?>
            icon: 'fa-trash',
<?php /*             Sets the modal title for deleting. */ ?>
            title: 'Delete Application',
<?php /*             Sets the delete explanation text. */ ?>
            intro: 'You are about to permanently delete the following application. Please review the details below before proceeding.',
<?php /*             Warns that deleting cannot be undone. */ ?>
            notice: 'This action is irreversible. The application record and all of its uploaded documents will be permanently removed.',
<?php /*             Label for the delete submit button. */ ?>
            submit: 'Delete Application'
<?php /*         Ends the delete settings. */ ?>
        }
<?php /*     Closes the config object. */ ?>
    };

<?php /*     Maps each detail key to the element id that shows it. */ ?>
    var fields = {
<?php /*         Lists the student, program, track, and stage element ids. */ ?>
        student: 'cfStudent', program: 'cfProgram', track: 'cfTrack', stage: 'cfStage',
<?php /*         Lists the title, status, submitted, and archived element ids. */ ?>
        title: 'cfTitle', status: 'cfStatus', submitted: 'cfSubmitted', archived: 'cfArchived'
<?php /*     Closes the fields map. */ ?>
    };

<?php /*     Defines the function that fills and shows the modal. */ ?>
    function openModal(btn) {
<?php /*         Picks the restore or delete settings from the clicked button. */ ?>
        var c = config[btn.dataset.confirmAction];
<?php /*         Stops when the button has an unknown action. */ ?>
        if (!c) return;
<?php /*         Sets the posted field name for this action. */ ?>
        idInput.name = c.field;
<?php /*         Puts the application id into the hidden field. */ ?>
        idInput.value = btn.dataset.id;
<?php /*         Sets the matching Font Awesome icon. */ ?>
        document.getElementById('confirmIcon').className = 'fas ' + c.icon;
<?php /*         Sets the modal title text. */ ?>
        document.getElementById('confirmTitle').textContent = c.title;
<?php /*         Sets the intro text. */ ?>
        document.getElementById('confirmIntro').textContent = c.intro;
<?php /*         Sets the warning notice text. */ ?>
        document.getElementById('confirmNotice').textContent = c.notice;
<?php /*         Finds the submit button. */ ?>
        var submitBtn = document.getElementById('confirmSubmit');
<?php /*         Sets the button label for the action. */ ?>
        submitBtn.textContent = c.submit;
<?php /*         Applies the yellow or red button style. */ ?>
        submitBtn.className = 'btn confirm-submit ' + c.btnClass;
<?php /*         Loops over the detail fields. */ ?>
        Object.keys(fields).forEach(function (k) {
<?php /*             Copies each value from the button's data attributes. */ ?>
            document.getElementById(fields[k]).textContent = btn.dataset[k] || '';
<?php /*         Closes the detail loop. */ ?>
        });
<?php /*         Shows the modal overlay. */ ?>
        modal.classList.add('open');
<?php /*     Closes the openModal function. */ ?>
    }

<?php /*     Defines the function that hides the modal. */ ?>
    function closeModal() {
<?php /*         Hides the overlay. */ ?>
        modal.classList.remove('open');
<?php /*         Clears the posted field name so nothing is submitted. */ ?>
        idInput.name = '';
<?php /*         Clears the stored id. */ ?>
        idInput.value = '';
<?php /*     Closes the closeModal function. */ ?>
    }

<?php /*     Listens for clicks anywhere on the page. */ ?>
    document.addEventListener('click', function (e) {
<?php /*         Finds the clicked restore or delete button. */ ?>
        var trigger = e.target.closest('[data-confirm-action]');
<?php /*         Opens the modal when an action button was clicked. */ ?>
        if (trigger) openModal(trigger);
<?php /*     Closes the click listener. */ ?>
    });
<?php /*     Closes the modal when Go Back is clicked. */ ?>
    document.getElementById('confirmCancel').addEventListener('click', closeModal);
<?php /*     Closes the modal when the dark backdrop is clicked. */ ?>
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
<?php /*     Closes the modal when Escape is pressed. */ ?>
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
<?php /* Closes and runs the modal script. */ ?>
})();
<?php /* Ends the JavaScript block. */ ?>
</script>
<?php /* Loads the shared footer and closing scripts. */ ?>
<?php require_once __DIR__ . '/../../includes/layout/footer.php'; ?>