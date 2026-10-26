<?php /* Opens the PHP file and switches the server into PHP mode. */ ?>
<?php
// Loads shared config: session, helper functions and mock data.
require_once __DIR__ . '/../../includes/config.php';
// Loads the database connection code used by the app.
require_once __DIR__ . '/../../includes/db.php';

// Sets the page title shown in the browser tab to 'Student Applications'.
$pageTitle   = 'Student Applications';
// Tells the shared layout that this page belongs to the coordinator role.
$role        = 'coordinator';
// Marks 'applications' as the active item in the coordinator sidebar.
$currentPage = 'applications';
// Coordinator's full name from the session, else mock name, else 'Coordinator'.
$userName    = !empty($_SESSION['user']['full_name']) ? $_SESSION['user']['full_name'] : ($mockCoordinator['name'] ?? 'Coordinator');

// Reads the student's user id from the URL (?id=), defaulting to 0.
$userId = (int) ($_GET['id'] ?? 0);
// Reads the application id to show from the URL (?app=), defaulting to 0.
$selectedId = (int) ($_GET['app'] ?? 0);

/* ------------------------------------------------------------------
 * The student and the applications they have submitted.
 * ------------------------------------------------------------------ */
// Starts with no student record; the loop below may fill it in.
$student = null;
// Starts with an empty list of applications for this student.
$applications = [];
// Loops over every student record returned by the coordinator helper.
foreach (coordinatorStudentsWithApplications() as $candidate) {
    // Checks whether this candidate's userId equals the id from the URL.
    if ((int) $candidate['userId'] === $userId) {
        // Remembers the matching student record.
        $student = $candidate;
        // Stops the loop as soon as the right student is found.
        break;
    // Closes the if that compared the candidate's userId.
    }
// Closes the foreach that searched the student list.
}

// Only continues when a matching student record was actually found.
if ($student) {
    // Copies the student's applications into the local $applications list.
    $applications = $student['applications'];
// Closes the if that checked whether a student was found.
}

// Starts with no chosen application selected.
$selected = null;
// Loops through the student's applications to find the chosen one.
foreach ($applications as $application) {
    // Checks whether this application id matches the ?app= value.
    if ((int) $application['id'] === $selectedId) {
        // Stores the matching application as the selected one.
        $selected = $application;
        // Stops the loop as soon as the matching application is found.
        break;
    // Closes the if that compared the application id.
    }
// Closes the foreach that searched the application list.
}
// If nothing matched but applications exist, fall back to the first one.
if (!$selected && $applications) {
    // Defaults the selection to the student's first application.
    $selected = $applications[0];
// Closes the if that picked a default application.
}

// Reads the student's track, defaulting to 'thesis' when missing.
$track     = $student['track'] ?? 'thesis';
// Gets a readable track name from the record or from getTrackLabel().
$trackLabel = $student['trackLabel'] ?? getTrackLabel($track);
// Starts with an empty list of milestone steps.
$milestoneSteps = [];
// Starts milestone counters: done, total, and index of the first pending step.
$milestoneStats = ['done' => 0, 'total' => 0, 'firstPending' => 0];
// Starts with an empty list of uploaded documents.
$uploads = [];
// Starts with an empty list of documents required for the stage.
$requiredDocs = [];

// Only builds milestone and document data when an application is selected.
if ($selected) {
    // Converts the application's stage label into a short internal stage key.
    $stageKey = stageKeyFromLabel((string) ($selected['stage'] ?? ''));
    // Loads all uploaded files that belong to the selected application.
    $uploads = uploadsForApplication((int) $selected['id']);
    // Loads the documents required for this track and stage.
    $requiredDocs = requiredDocumentsForStage($track, $stageKey);
    // Builds the milestone steps for this student, track and stage.
    $milestoneSteps = stageMilestoneSteps((string) $student['email'], $track, $stageKey);
    // Counts completed vs total milestones and finds the first pending one.
    $milestoneStats = milestoneProgress($milestoneSteps);
// Closes the if that prepared data for the selected application.
}

// Loads the shared header, sidebar and navigation HTML.
require_once __DIR__ . '/../../includes/header.php';
// Closes PHP mode so the HTML below is sent to the browser.
?>

<?php /* Opens the page banner that holds the title and description. */ ?>
<div class="page-header page-banner">
<?php /*     Heading shows the student's name escaped, or 'Student' if none. */ ?>
    <h2><?= $student ? htmlspecialchars($student['name']) : 'Student' ?></h2>
<?php /*     Opens the paragraph that describes the account. */ ?>
    <p>
<?php /*         Only shows account wording when a student record was found. */ ?>
        <?php if ($student): ?>
<?php /*         Sentence naming the student whose applications are listed below. */ ?>
        Account information and the applications of <?= htmlspecialchars($student['name']) ?>.
<?php /*         Runs when no student record was found. */ ?>
        <?php else: ?>
<?php /*         Tells the coordinator that the student record could not be found. */ ?>
        This student record could not be found.
<?php /*         Ends the if/else that chose the description text. */ ?>
        <?php endif; ?>
<?php /*     Closes the description paragraph. */ ?>
    </p>
<?php /* Closes the page banner. */ ?>
</div>

<?php /* Shows a warning instead of the details when the student is missing. */ ?>
<?php if (!$student): ?>
<?php /* Yellow alert saying the student could not be found. */ ?>
<div class="alert alert-warning"><i class="fas fa-info-circle"></i> This student could not be found.</div>
<?php /* Runs when a valid student was found, so the details are shown. */ ?>
<?php else: ?>

<?php /* Opens the card that holds the student's account information. */ ?>
<div class="card">
<?php /*     Card header with the 'Account Information' title. */ ?>
    <div class="card-header"><h3>Account Information</h3></div>
<?php /*     Opens the card body that holds the detail grid. */ ?>
    <div class="card-body">
<?php /*         Opens a grid layout for the label/value detail pairs. */ ?>
        <div class="detail-grid">
<?php /*             Shows the student's full name, escaped for safety. */ ?>
            <div class="detail-item"><label>Student Name</label><span><?= htmlspecialchars($student['name']) ?></span></div>
<?php /*             Shows the student's email address, escaped for safety. */ ?>
            <div class="detail-item"><label>Email</label><span><?= htmlspecialchars($student['email']) ?></span></div>
<?php /*             Shows the ADZU student id, or an em dash when it is empty. */ ?>
            <div class="detail-item"><label>ADZU ID</label><span><?= htmlspecialchars($student['studentId'] !== '' ? $student['studentId'] : '—') ?></span></div>
<?php /*             Shows the student's degree program. */ ?>
            <div class="detail-item"><label>Program</label><span><?= htmlspecialchars($student['program']) ?></span></div>
<?php /*             Shows the readable track name (Thesis, Capstone, etc.). */ ?>
            <div class="detail-item"><label>Track</label><span><?= htmlspecialchars($trackLabel) ?></span></div>
<?php /*             Shows the adviser's name, or an em dash when empty. */ ?>
            <div class="detail-item"><label>Adviser</label><span><?= htmlspecialchars($student['adviser'] !== '' ? $student['adviser'] : '—') ?></span></div>
<?php /*             Shows the enrollment date formatted, or an em dash when empty. */ ?>
            <div class="detail-item"><label>Enrolled</label><span><?= $student['enrolled'] ? date('M d, Y', strtotime($student['enrolled'])) : '—' ?></span></div>
<?php /*             Shows the registration date formatted, or an em dash when empty. */ ?>
            <div class="detail-item"><label>Registered</label><span><?= $student['registeredAt'] ? date('M d, Y', strtotime($student['registeredAt'])) : '—' ?></span></div>
<?php /*             Badge showing whether the student has submitted any application. */ ?>
            <div class="detail-item"><label>Submission Status</label><span><?= submissionBadge($applications !== []) ?></span></div>
<?php /*         Closes the detail grid. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the account information card. */ ?>
</div>

<?php /* Branches on whether the student has any application records. */ ?>
<?php if (!$applications): ?>
<?php /* Opens the card used for the 'no applications' message. */ ?>
<div class="card">
<?php /*     Card header titled 'Application Records'. */ ?>
    <div class="card-header"><h3>Application Records</h3></div>
<?php /*     Opens the card body. */ ?>
    <div class="card-body">
<?php /*         Info alert box with zero margin holding the empty message. */ ?>
        <div class="alert alert-info" style="margin:0;">
<?php /*             Shows an info circle icon inside the alert. */ ?>
            <i class="fas fa-info-circle"></i>
<?php /*             Bold message saying the student has not submitted an application yet. */ ?>
            <div><strong>This student has not submitted an application yet.</strong></div>
<?php /*         Closes the info alert box. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the empty-state card. */ ?>
</div>
<?php /* Runs when the student does have applications to list. */ ?>
<?php else: ?>

<?php /* Opens the card that lists the student's applications. */ ?>
<div class="card">
<?php /*     Opens the card header. */ ?>
    <div class="card-header">
<?php /*         Title 'Application Records' plus a badge with the number of records. */ ?>
        <h3>Application Records <span class="filter-count"><?= count($applications) ?></span></h3>
<?php /*     Closes the card header. */ ?>
    </div>
<?php /*     Opens the card body. */ ?>
    <div class="card-body">
<?php /*         Hint paragraph styled small and grey. */ ?>
        <p style="margin-bottom:1rem;font-size:0.85rem;color:var(--gray-500);">
<?php /*             Sentence that adapts to one or many applications and asks to pick one. */ ?>
            <?= count($applications) === 1 ? 'This student has one application on record.' : 'This student has ' . count($applications) . ' applications on record. Select one to review it.' ?>
<?php /*         Closes the hint paragraph. */ ?>
        </p>
<?php /*         Scrollable wrapper so the table fits narrow screens. */ ?>
        <div class="table-responsive">
<?php /*             Table listing every application of this student. */ ?>
            <table class="data-table" id="applicationRecordsTable">
<?php /*                 Opens the table header section. */ ?>
                <thead>
<?php /*                     Starts the header row. */ ?>
                    <tr>
<?php /*                         Column header for the application number. */ ?>
                        <th>Application</th>
<?php /*                         Column header for the workflow stage. */ ?>
                        <th>Stage</th>
<?php /*                         Column header for the research/paper title. */ ?>
                        <th>Title</th>
<?php /*                         Column header for the submission date. */ ?>
                        <th>Submitted</th>
<?php /*                         Column header for the application status. */ ?>
                        <th>Application Status</th>
<?php /*                         Column header for row action buttons. */ ?>
                        <th>Actions</th>
<?php /*                     Closes the header row. */ ?>
                    </tr>
<?php /*                 Closes the table header section. */ ?>
                </thead>
<?php /*                 Opens the table body. */ ?>
                <tbody>
<?php /*                     Loops over each application to build one table row. */ ?>
                    <?php foreach ($applications as $application):
                        // True when this row is the application currently selected for details.
                        $isSelected = $selected && (int) $application['id'] === (int) $selected['id'];
                    // Closes the PHP setup so the row HTML can be printed.
                    ?>
<?php /*                     Row, shaded grey when it is the selected application. */ ?>
                    <tr<?= $isSelected ? ' style="background:var(--gray-50);"' : '' ?>>
<?php /*                         Shows the application id number in bold. */ ?>
                        <td data-label="Application"><strong>#<?= (int) $application['id'] ?></strong></td>
<?php /*                         Shows the application's workflow stage. */ ?>
                        <td data-label="Stage"><?= htmlspecialchars($application['stage']) ?></td>
<?php /*                         Shows the research or paper title. */ ?>
                        <td class="app-title-cell" data-label="Title"><?= htmlspecialchars($application['title']) ?></td>
<?php /*                         Shows the submission date, or an em dash when empty. */ ?>
                        <td data-label="Submitted"><?= $application['date'] ? date('M d, Y', strtotime($application['date'])) : '—' ?></td>
<?php /*                         Status badge for this application's status. */ ?>
                        <td data-label="Status"><?= statusBadge($application['status']) ?></td>
<?php /*                         Opens the actions cell for this row. */ ?>
                        <td class="actions" data-label="Actions">
<?php /*                             Select link reloads the page with this student and application id. */ ?>
                            <a href="<?= url('coordinator/applications/student.php?id=' . (int) $student['userId'] . '&app=' . (int) $application['id']) ?>" class="btn btn-sm btn-outline app-select-btn">Select</a>
<?php /*                             Edit link opens process.php to edit this application. */ ?>
                            <a href="<?= url('coordinator/applications/process.php?id=' . (int) $application['id']) ?>" class="btn btn-primary app-edit-btn" title="Edit application"><i class="fas fa-pen-to-square" aria-hidden="true"></i> Edit</a>
<?php /*                         Closes the actions cell. */ ?>
                        </td>
<?php /*                     Closes the application row. */ ?>
                    </tr>
<?php /*                     Ends the per-application row loop. */ ?>
                    <?php endforeach; ?>
<?php /*                 Closes the table body. */ ?>
                </tbody>
<?php /*             Closes the applications table. */ ?>
            </table>
<?php /*         Closes the scrollable table wrapper. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the application records card. */ ?>
</div>

<?php /* Shows the detail sections only when an application is selected. */ ?>
<?php if ($selected): ?>
<?php /* Opens the card for the selected application's details. */ ?>
<div class="card">
<?php /*     Opens the card header. */ ?>
    <div class="card-header">
<?php /*         Title shows the application id and its stage. */ ?>
        <h3>Application #<?= (int) $selected['id'] ?> — <?= htmlspecialchars($selected['stage']) ?></h3>
<?php /*         Static 'Submitted' status badge in the header. */ ?>
        <span class="status-badge status-submitted">Submitted</span>
<?php /*     Closes the card header. */ ?>
    </div>
<?php /*     Opens the card body. */ ?>
    <div class="card-body">
<?php /*         Opens the detail grid for the selected application. */ ?>
        <div class="detail-grid">
<?php /*             Research title shown across the full width of the grid. */ ?>
            <div class="detail-item" style="grid-column:1/-1;"><label>Research / Paper Title</label><span><?= htmlspecialchars($selected['title']) ?></span></div>
<?php /*             Shows the application's current stage. */ ?>
            <div class="detail-item"><label>Current Stage</label><span><?= htmlspecialchars($selected['stage']) ?></span></div>
<?php /*             Status badge for the selected application. */ ?>
            <div class="detail-item"><label>Application Status</label><span><?= statusBadge($selected['status']) ?></span></div>
<?php /*             Shows the student's adviser, or an em dash when empty. */ ?>
            <div class="detail-item"><label>Adviser</label><span><?= htmlspecialchars($student['adviser'] !== '' ? $student['adviser'] : '—') ?></span></div>
<?php /*             Shows the submission date with the full month name. */ ?>
            <div class="detail-item"><label>Submission Date</label><span><?= $selected['date'] ? date('F d, Y', strtotime($selected['date'])) : '—' ?></span></div>
<?php /*             Shows Approved, Requires Revision, or an em dash when no result. */ ?>
            <div class="detail-item"><label>Presentation Result</label><span><?= $selected['result'] ? htmlspecialchars($selected['result'] === 'approved' ? 'Approved' : 'Requires Revision') : '—' ?></span></div>
<?php /*             Picks the best graduate school endorsement upload for this application. */ ?>
            <?php $gradSchoolUpload = bestUploadOfKind($uploads, 'grad_school_endorsement'); ?>
<?php /*             Shows the endorsement status, or 'Not submitted' when missing. */ ?>
            <div class="detail-item"><label>Graduate School Endorsement</label><span><?= $gradSchoolUpload ? htmlspecialchars(ucfirst((string) ($gradSchoolUpload['status'] ?? 'submitted'))) : 'Not submitted' ?></span></div>
<?php /*             Shows payment status and the receipt number when recorded. */ ?>
            <div class="detail-item"><label>Payment Recorded</label><span><?= paymentIsRecorded($selected, $uploads) ? 'Yes' . (!empty($selected['receiptNumber']) ? ' (' . htmlspecialchars($selected['receiptNumber']) . ')' : '') : 'No' ?></span></div>
<?php /*             Shows Yes/No whether the application is ready for presentation. */ ?>
            <div class="detail-item"><label>Ready for Presentation</label><span><?= !empty($selected['readyForPresentation']) ? 'Yes' : 'No' ?></span></div>
<?php /*             Only shows the comment row when a coordinator comment exists. */ ?>
            <?php if (!empty($selected['coordinatorComment'])): ?>
<?php /*             Shows the coordinator's comment across the full width. */ ?>
            <div class="detail-item" style="grid-column:1/-1;"><label>Coordinator Comment</label><span><?= htmlspecialchars($selected['coordinatorComment']) ?></span></div>
<?php /*             Ends the check for a coordinator comment. */ ?>
            <?php endif; ?>
<?php /*         Closes the detail grid. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the selected application card. */ ?>
</div>

<?php /* Opens the card that shows milestone progress. */ ?>
<div class="card">
<?php /*     Opens the milestone card header. */ ?>
    <div class="card-header">
<?php /*         Title 'Milestone Progress' with a checklist icon. */ ?>
        <h3><i class="fas fa-tasks"></i> Milestone Progress</h3>
<?php /*         Opens the overview box holding the count and progress bar. */ ?>
        <div class="ms-overview">
<?php /*             Text showing how many milestones are done out of the total. */ ?>
            <span class="ms-overview-text"><?= (int) $milestoneStats['done'] ?> of <?= (int) $milestoneStats['total'] ?> milestones complete</span>
<?php /*             Progress bar fill width as a percentage of completed milestones. */ ?>
            <span class="ms-overview-bar"><span class="ms-overview-fill" style="width:<?= $milestoneStats['total'] > 0 ? (int) round($milestoneStats['done'] * 100 / $milestoneStats['total']) : 0 ?>%;"></span></span>
<?php /*         Closes the milestone overview box. */ ?>
        </div>
<?php /*     Closes the milestone card header. */ ?>
    </div>
<?php /*     Opens the milestone card body. */ ?>
    <div class="card-body">
<?php /*         Handles the case where no milestones are defined for the stage. */ ?>
        <?php if (!$milestoneSteps): ?>
<?php /*         Message saying no milestones are defined for this stage. */ ?>
        <p class="ms-empty">No milestones are defined for this stage.</p>
<?php /*         Runs when milestone steps exist and should be drawn. */ ?>
        <?php else: ?>
<?php /*         Container marking the current, open milestone stage. */ ?>
        <div class="ms-stage is-current is-open" data-ms-stage>
<?php /*             Opens the body of the milestone stage. */ ?>
            <div class="ms-stage-body" style="border-top:none;">
<?php /*                 Loops over each milestone step with its position index. */ ?>
                <?php foreach ($milestoneSteps as $si => $step):
                    // Reads this step's status, defaulting to 'pending'.
                    $state = $step['state']['status'] ?? 'pending';
                    // True when the step is completed.
                    $isDone = $state === 'done';
                    // True when the step is flagged as needing action.
                    $isFlagged = $state === 'flagged';
                    // True for the first pending step, which is the student's next task.
                    $isActive = !$isDone && !$isFlagged && $si === $milestoneStats['firstPending'];
                    // Chooses which icon state to draw for this step.
                    $iconState = $isFlagged ? 'flagged' : ($isDone ? 'done' : ($isActive ? 'active' : 'pending'));
                    // Chooses the CSS class that colours this milestone row.
                    $rowClass = $isFlagged ? 'is-flagged' : ($isDone ? 'is-done' : ($isActive ? 'is-active' : 'is-pending'));
                // Closes the PHP setup so the step HTML can be printed.
                ?>
<?php /*                 One milestone row with its state class. */ ?>
                <div class="ms-step <?= $rowClass ?>">
<?php /*                     Opens the icon column of the milestone row. */ ?>
                    <div class="ms-step-col">
<?php /*                         Prints the icon matching this step's state. */ ?>
                        <?= stepIconHtml($iconState) ?>
<?php /*                         Only draws a connector line when this is not the last step. */ ?>
                        <?php if ($si < count($milestoneSteps) - 1): ?>
<?php /*                         Vertical connector line between milestones, filled when done. */ ?>
                        <span class="ms-step-line<?= $isDone ? ' done' : '' ?>"></span>
<?php /*                         Ends the not-last-step check. */ ?>
                        <?php endif; ?>
<?php /*                     Closes the icon column. */ ?>
                    </div>
<?php /*                     Opens the text column of the milestone row. */ ?>
                    <div class="ms-step-body">
<?php /*                         Opens the label line for the milestone. */ ?>
                        <div class="ms-step-label">
<?php /*                             Shows the milestone's readable label. */ ?>
                            <span><?= htmlspecialchars($step['label']) ?></span>
<?php /*                             Shows a 'Coordinator' chip when the step is handled by the coordinator. */ ?>
                            <?php if ($step['coord']): ?><span class="coord-chip">Coordinator</span><?php endif; ?>
<?php /*                             Shows a green 'Next' tag on the step the student must do next. */ ?>
                            <?php if ($isActive): ?><span class="ms-step-tag active">Next</span><?php endif; ?>
<?php /*                             Shows a 'Done' tag when the milestone is completed. */ ?>
                            <?php if ($isDone): ?><span class="ms-step-tag done">Done</span><?php endif; ?>
<?php /*                             Shows an 'Action needed' tag when the milestone is flagged. */ ?>
                            <?php if ($isFlagged): ?><span class="ms-step-tag flagged">Action needed</span><?php endif; ?>
<?php /*                         Closes the label line. */ ?>
                        </div>
<?php /*                         Shows the milestone's detail or hint text. */ ?>
                        <p class="ms-step-detail"><?= htmlspecialchars($step['detail']) ?></p>
<?php /*                     Closes the text column. */ ?>
                    </div>
<?php /*                 Closes the milestone row. */ ?>
                </div>
<?php /*                 Ends the loop over milestone steps. */ ?>
                <?php endforeach; ?>
<?php /*             Closes the milestone stage body. */ ?>
            </div>
<?php /*         Closes the milestone stage container. */ ?>
        </div>
<?php /*         Ends the check for whether milestones exist. */ ?>
        <?php endif; ?>
<?php /*     Closes the milestone card body. */ ?>
    </div>
<?php /* Closes the milestone progress card. */ ?>
</div>

<?php /* Opens the card that lists submitted documents. */ ?>
<div class="card">
<?php /*     Card header titled 'Submitted Documents'. */ ?>
    <div class="card-header"><h3>Submitted Documents</h3></div>
<?php /*     Opens the documents card body. */ ?>
    <div class="card-body">
<?php /*         Shows the requirement checklist only when requirements exist. */ ?>
        <?php if ($requiredDocs): ?>
<?php /*         Small hint paragraph above the requirements table. */ ?>
        <p class="field-hint" style="margin-bottom:0.75rem;">
<?php /*             Text naming the track and stage these requirements apply to. */ ?>
            Required documents for the <strong><?= htmlspecialchars($trackLabel) ?></strong> track — <?= htmlspecialchars($selected['stage']) ?>.
<?php /*         Closes the hint paragraph. */ ?>
        </p>
<?php /*         Scrollable wrapper for the requirements table. */ ?>
        <div class="table-responsive">
<?php /*             Table listing required documents and their status. */ ?>
            <table class="data-table">
<?php /*                 Header row with 'Required Document' and 'Status' columns. */ ?>
                <thead><tr><th>Required Document</th><th>Status</th></tr></thead>
<?php /*                 Opens the requirements table body. */ ?>
                <tbody>
<?php /*                     Loops over each required document for the stage. */ ?>
                    <?php foreach ($requiredDocs as $requirement):
                        // Finds the best upload matching this requirement's kind.
                        $matched = bestUploadOfKind($uploads, $requirement['kind']);
                        // True when the upload is verified or approved.
                        $isDone = $matched && in_array($matched['status'] ?? '', ['verified', 'approved'], true);
                        // True when the upload was marked incomplete.
                        $isFlagged = $matched && ($matched['status'] ?? '') === 'incomplete';
                    // Closes the PHP setup before printing the row.
                    ?>
<?php /*                     Requirement row. */ ?>
                    <tr>
<?php /*                         Shows the requirement's readable label. */ ?>
                        <td><?= htmlspecialchars($requirement['label']) ?></td>
<?php /*                         Opens the status cell. */ ?>
                        <td>
<?php /*                             When verified, show the green Verified badge. */ ?>
                            <?php if ($isDone): ?>
<?php /*                                 Green 'Verified' status badge. */ ?>
                                <span class="status-badge status-approved">Verified</span>
<?php /*                             When incomplete, show the revision badge instead. */ ?>
                            <?php elseif ($isFlagged): ?>
<?php /*                                 Red 'Incomplete' status badge. */ ?>
                                <span class="status-badge status-revision">Incomplete</span>
<?php /*                             When an upload exists but is not yet checked, show Submitted. */ ?>
                            <?php elseif ($matched): ?>
<?php /*                                 Blue 'Submitted' status badge. */ ?>
                                <span class="status-badge status-submitted">Submitted</span>
<?php /*                             When nothing was uploaded for this requirement. */ ?>
                            <?php else: ?>
<?php /*                                 Grey 'Not Yet Submitted' status badge. */ ?>
                                <span class="status-badge status-not-submitted">Not Yet Submitted</span>
<?php /*                             Ends the badge if/else chain. */ ?>
                            <?php endif; ?>
<?php /*                         Closes the status cell. */ ?>
                        </td>
<?php /*                     Closes the requirement row. */ ?>
                    </tr>
<?php /*                     Ends the loop over required documents. */ ?>
                    <?php endforeach; ?>
<?php /*                 Closes the requirements table body. */ ?>
                </tbody>
<?php /*             Closes the requirements table. */ ?>
            </table>
<?php /*         Closes the requirements scroll wrapper. */ ?>
        </div>
<?php /*         Ends the block that shows required documents. */ ?>
        <?php endif; ?>

<?php /*         Scrollable wrapper for the uploaded files table, pushed down a bit. */ ?>
        <div class="table-responsive" style="margin-top:1.25rem;">
<?php /*             Table listing every uploaded file for this application. */ ?>
            <table class="data-table">
<?php /*                 Header row: file name, type, stage, date, status and actions. */ ?>
                <thead><tr><th>File Name</th><th>Type</th><th>Stage</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
<?php /*                 Opens the uploads table body. */ ?>
                <tbody>
<?php /*                     Handles the case where the application has no uploads. */ ?>
                    <?php if (!$uploads): ?>
<?php /*                     Single row saying no documents have been uploaded yet. */ ?>
                    <tr><td colspan="6" style="text-align:center;color:var(--gray-400);padding:1.5rem;">No documents uploaded yet.</td></tr>
<?php /*                     Ends the empty uploads check. */ ?>
                    <?php endif; ?>
<?php /*                     Loops over each uploaded document. */ ?>
                    <?php foreach ($uploads as $doc): ?>
<?php /*                     Upload row. */ ?>
                    <tr>
<?php /*                         Shows the stored file name. */ ?>
                        <td><?= htmlspecialchars($doc['fileName']) ?></td>
<?php /*                         Shows the document type. */ ?>
                        <td><?= htmlspecialchars($doc['docType']) ?></td>
<?php /*                         Shows the stage the document belongs to. */ ?>
                        <td><?= htmlspecialchars($doc['stage']) ?></td>
<?php /*                         Shows the upload date formatted. */ ?>
                        <td><?= date('M d, Y', strtotime($doc['date'])) ?></td>
<?php /*                         Status badge for this document. */ ?>
                        <td><?= statusBadge($doc['status']) ?></td>
<?php /*                         Opens the actions cell for the upload. */ ?>
                        <td class="actions">
<?php /*                             Only offers a download link when the file exists in storage. */ ?>
                            <?php if (uploadStoragePath($doc)): ?>
<?php /*                             Download button pointing to the coordinator download handler. */ ?>
                            <a class="btn btn-sm btn-outline" href="<?= url('coordinator/download.php?id=' . urlencode($doc['id'])) ?>"><i class="fas fa-download"></i> Download</a>
<?php /*                             Runs when the stored file is missing. */ ?>
                            <?php else: ?>
<?php /*                             Grey 'Unavailable' text instead of a download link. */ ?>
                            <span style="color:var(--gray-400);font-size:.8rem;">Unavailable</span>
<?php /*                             Ends the storage-file check. */ ?>
                            <?php endif; ?>
<?php /*                         Closes the upload actions cell. */ ?>
                        </td>
<?php /*                     Closes the upload row. */ ?>
                    </tr>
<?php /*                     Ends the loop over uploaded documents. */ ?>
                    <?php endforeach; ?>
<?php /*                 Closes the uploads table body. */ ?>
                </tbody>
<?php /*             Closes the uploads table. */ ?>
            </table>
<?php /*         Closes the uploads scroll wrapper. */ ?>
        </div>
<?php /*     Closes the documents card body. */ ?>
    </div>
<?php /* Closes the submitted documents card. */ ?>
</div>
<?php /* Ends the block that shows the selected application details. */ ?>
<?php endif; ?>

<?php /* Ends the if/else that chose between the empty state and the records. */ ?>
<?php endif; ?>

<?php /* Opens the footer action bar with the back button. */ ?>
<div class="form-actions">
<?php /*     Back button returns the coordinator to the applications list page. */ ?>
    <a href="<?= url('coordinator/applications/manage.php') ?>" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Applications</a>
<?php /* Closes the footer action bar. */ ?>
</div>

<?php /* Ends the check for a missing student, closing the outer if/else. */ ?>
<?php endif; ?>

<?php /* Loads the shared footer and its closing scripts. */ ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
