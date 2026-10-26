<?php /* Opens the PHP block for the final approval page. */ ?>
<?php
// Loads shared config helpers and demo data stores.
require_once __DIR__ . '/../../includes/config.php';

// Sets the browser tab title to Final Approval.
$pageTitle   = 'Final Approval';
// Marks the page as part of the coordinator area.
$role        = 'coordinator';
// Highlights the Applications menu in the sidebar.
$currentPage = 'applications';
// Uses the coordinator name from the demo data for the top bar.
$userName    = $mockCoordinator['name'];

// Finds the application by URL id, else falls back to the first stored one.
$app = findApplication((int) ($_GET['id'] ?? 0)) ?? (storeGet('applications')[0] ?? null);
// Holds the success message after saving a result.
$approvalSuccess = '';
// Holds the error message when the result is missing.
$approvalError = '';

// Only processes the form on POST and when an application exists.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app) {
    // Reads the chosen final result from the posted form.
    $result = $_POST['result'] ?? '';
    // Maps each readable result name to the status stored in data.
    $statusMap = [
        // Approved marks the application as completed.
        'Approved' => 'completed',
        // Approved with minor revisions keeps an approved status.
        'Approved with Minor Revisions' => 'approved',
        // Requires major revisions flags the student for more work.
        'Requires Major Revisions' => 'requires_revision',
    // Closes the result-to-status lookup array.
    ];
    // Checks that the submitted result is one of the allowed choices.
    if (!isset($statusMap[$result])) {
        // Sets the error asking the coordinator to pick a result.
        $approvalError = 'Select a final presentation result.';
    // Starts the else branch that saves the approval.
    } else {
        // Updates the application record with the new status and details.
        $app = updateApplicationRecord((int) $app['id'], [
            // Stores the status that matches the chosen result.
            'status' => $statusMap[$result],
            // Stores the trimmed final comment from the textarea.
            'coordinatorComment' => trim($_POST['comment'] ?? ''),
            // Stores the readable result text as typed.
            'result' => $result,
            // Stores the completion date, defaulting to today.
            'completionDate' => $_POST['completionDate'] ?? date('Y-m-d'),
        // Closes the array of fields to update.
        ]);
        // Tells the coordinator the result was recorded.
        $approvalSuccess = 'Final result recorded. The student status page now reflects this outcome.';
    // Closes the else branch that saved the record.
    }
// Closes the POST handling block.
}

// Loads the shared header with sidebar and top bar.
require_once __DIR__ . '/../../includes/header.php';
// Closes PHP so the HTML below is printed.
?>

<?php /* Opens the page banner with the page title. */ ?>
<div class="page-header page-banner">
<?php /*     Shows the Final Approval heading. */ ?>
    <h2>Final Approval</h2>
<?php /*     Describes the page and names the student when known. */ ?>
    <p>Record completion of the final presentation process<?= $app ? ' for ' . htmlspecialchars($app['student']) : '' ?>.</p>
<?php /* Closes the page banner. */ ?>
</div>

<?php /* Only shows the success alert after a successful save. */ ?>
<?php if ($approvalSuccess): ?>
<?php /* Green alert that fades automatically with the success text. */ ?>
<div class="alert alert-success" data-auto-dismiss><i class="fas fa-check-circle"></i> <?= htmlspecialchars($approvalSuccess) ?></div>
<?php /* Closes the success alert check. */ ?>
<?php endif; ?>
<?php /* Only shows the error alert when there is an error. */ ?>
<?php if ($approvalError): ?>
<?php /* Red alert showing the error message. */ ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($approvalError) ?></div>
<?php /* Closes the error alert check. */ ?>
<?php endif; ?>

<?php /* Opens a card summarising the final presentation status. */ ?>
<div class="card">
<?php /*     Opens the card header. */ ?>
    <div class="card-header">
<?php /*         Title of the summary card. */ ?>
        <h3>Final Presentation Status</h3>
<?php /*         Shows a coloured badge for the current application status. */ ?>
        <?= statusBadge($app['status'] ?? 'pending') ?>
<?php /*     Closes the card header. */ ?>
    </div>
<?php /*     Opens the card body. */ ?>
    <div class="card-body">
<?php /*         Opens a grid of key-value detail items. */ ?>
        <div class="detail-grid">
<?php /*             Shows the student name, falling back to the demo student. */ ?>
            <div class="detail-item"><label>Student</label><span><?= htmlspecialchars($app['student'] ?? $mockStudent['name']) ?></span></div>
<?php /*             Shows the program name, falling back to the demo student. */ ?>
            <div class="detail-item"><label>Program</label><span><?= htmlspecialchars($app['program'] ?? $mockStudent['program_name']) ?></span></div>
<?php /*             Shows the adviser name, falling back to the demo student. */ ?>
            <div class="detail-item"><label>Adviser</label><span><?= htmlspecialchars($app['adviser'] ?? $mockStudent['adviser']) ?></span></div>
<?php /*             Shows the final presentation date or a dash when empty. */ ?>
            <div class="detail-item"><label>Final Presentation Date</label><span><?= htmlspecialchars($app['completionDate'] ?? '—') ?></span></div>
<?php /*             Shows the research title across the full grid width. */ ?>
            <div class="detail-item" style="grid-column:1/-1;"><label>Title</label><span><?= htmlspecialchars($app['title'] ?? $mockStudent['title']) ?></span></div>
<?php /*         Closes the detail grid. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the summary card. */ ?>
</div>

<?php /* Opens a card listing the final requirement documents. */ ?>
<div class="card">
<?php /*     Header naming the requirements card. */ ?>
    <div class="card-header"><h3>Final Requirements & Documents</h3></div>
<?php /*     Opens the requirements card body. */ ?>
    <div class="card-body">
<?php /*         Wrapper allowing the documents table to scroll. */ ?>
        <div class="table-responsive">
<?php /*             Opens the documents table. */ ?>
            <table class="data-table">
<?php /*                 Header row with Document, Status and Action columns. */ ?>
                <thead><tr><th>Document</th><th>Status</th><th>Action</th></tr></thead>
<?php /*                 Opens the table body. */ ?>
                <tbody>
<?php /*                     Row for the final research paper document. */ ?>
                    <tr>
<?php /*                         Names the first required document. */ ?>
                        <td>Final Research Paper</td>
<?php /*                         Shows a pending badge for this document. */ ?>
                        <td><?= statusBadge('pending') ?></td>
<?php /*                         Download button for the research paper file. */ ?>
                        <td><button class="btn btn-sm btn-outline"><i class="fas fa-download"></i></button></td>
<?php /*                     Closes the research paper row. */ ?>
                    </tr>
<?php /*                     Row for the final adviser endorsement document. */ ?>
                    <tr>
<?php /*                         Names the endorsement requirement. */ ?>
                        <td>Adviser Endorsement (Final)</td>
<?php /*                         Shows a pending badge for the endorsement. */ ?>
                        <td><?= statusBadge('pending') ?></td>
<?php /*                         Download button for the endorsement file. */ ?>
                        <td><button class="btn btn-sm btn-outline"><i class="fas fa-download"></i></button></td>
<?php /*                     Closes the endorsement row. */ ?>
                    </tr>
<?php /*                     Row for the panel recommendations document. */ ?>
                    <tr>
<?php /*                         Names the panel recommendations requirement. */ ?>
                        <td>Panel Recommendations</td>
<?php /*                         Shows a pending badge for the recommendations. */ ?>
                        <td><?= statusBadge('pending') ?></td>
<?php /*                         Download button for the recommendations file. */ ?>
                        <td><button class="btn btn-sm btn-outline"><i class="fas fa-download"></i></button></td>
<?php /*                     Closes the recommendations row. */ ?>
                    </tr>
<?php /*                 Closes the table body. */ ?>
                </tbody>
<?php /*             Closes the documents table. */ ?>
            </table>
<?php /*         Closes the scrolling wrapper. */ ?>
        </div>
<?php /*     Closes the requirements card body. */ ?>
    </div>
<?php /* Closes the requirements card. */ ?>
</div>

<?php /* Opens the approval form posting to this page with the application id. */ ?>
<form method="post" action="<?= url('coordinator/applications/approval.php' . ($app ? '?id=' . $app['id'] : '')) ?>" data-validate>
<?php /*     Opens the card that holds the approval form. */ ?>
    <div class="card">
<?php /*         Header for the approval form card. */ ?>
        <div class="card-header"><h3>Final Approval Form</h3></div>
<?php /*         Opens the form card body. */ ?>
        <div class="card-body">
<?php /*             Opens a row for the result and date fields. */ ?>
            <div class="form-row">
<?php /*                 Opens the field group for the final result. */ ?>
                <div class="form-field">
<?php /*                     Labels the result dropdown as required. */ ?>
                    <label>Final Presentation Result <span class="required">*</span></label>
<?php /*                     Dropdown of allowed final presentation results. */ ?>
                    <select name="result" required>
<?php /*                         Placeholder option asking the coordinator to choose. */ ?>
                        <option value="">Select result</option>
<?php /*                         Option for a straight approval. */ ?>
                        <option>Approved</option>
<?php /*                         Option for approval needing only small fixes. */ ?>
                        <option>Approved with Minor Revisions</option>
<?php /*                         Option for work that needs major revisions. */ ?>
                        <option>Requires Major Revisions</option>
<?php /*                     Closes the result dropdown. */ ?>
                    </select>
<?php /*                 Closes the result field group. */ ?>
                </div>
<?php /*                 Opens the field group for the completion date. */ ?>
                <div class="form-field">
<?php /*                     Labels the completion date input. */ ?>
                    <label>Completion Date</label>
<?php /*                     Date picker for when the presentation finished. */ ?>
                    <input type="date" name="completionDate">
<?php /*                 Closes the date field group. */ ?>
                </div>
<?php /*             Closes the row of result and date. */ ?>
            </div>
<?php /*             Opens the field group for final comments. */ ?>
            <div class="form-field">
<?php /*                 Labels the comments textarea. */ ?>
                <label>Final Comments</label>
<?php /*                 Textarea where the coordinator writes final notes. */ ?>
                <textarea name="comment" placeholder="Record final panel recommendations and completion notes..."></textarea>
<?php /*             Closes the comments field group. */ ?>
            </div>
<?php /*             Opens the field group for the readiness checkbox. */ ?>
            <div class="form-field">
<?php /*                 Opens the label wrapping the checkbox and its text. */ ?>
                <label>
<?php /*                     Checkbox marking the student ready for hardbound submission. */ ?>
                    <input type="checkbox" style="width:auto;margin-right:0.5rem;">
<?php /*                     Readable text explaining what the checkbox means. */ ?>
                    Student is ready for final submission to the Graduate School (hardbound)
<?php /*                 Closes the checkbox label. */ ?>
                </label>
<?php /*             Closes the checkbox field group. */ ?>
            </div>
<?php /*         Closes the form card body. */ ?>
        </div>
<?php /*     Closes the form card. */ ?>
    </div>
<?php /*     Opens the row of action buttons. */ ?>
    <div class="form-actions">
<?php /*         Submit button that records the final approval. */ ?>
        <button type="submit" class="btn btn-success"><i class="fas fa-check-circle"></i> Record Final Approval</button>
<?php /*         Button that prints the approval form. */ ?>
        <button type="button" class="btn btn-secondary" data-print><i class="fas fa-print"></i> Print Approval Form</button>
<?php /*         Cancel link back to the applications list. */ ?>
        <a href="<?= url('coordinator/applications/manage.php') ?>" class="btn btn-outline">Cancel</a>
<?php /*     Closes the action buttons row. */ ?>
    </div>
<?php /* Closes the approval form. */ ?>
</form>

<?php /* Loads the shared footer scripts and closes PHP. */ ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
