<?php /* Opens the PHP block for the student Process Tracker page. */ ?>
<?php
// Loads the shared config with session, constants, and helpers.
require_once __DIR__ . '/../includes/config/config.php';
// Loads the DB helper for the student and progress lookups.
require_once __DIR__ . '/../includes/config/db.php';

// Loads the logged-in student's profile into $mockStudent.
$mockStudent = currentStudentProfile($mockStudent);

// Sets the page title to Requirements and Process.
$pageTitle   = 'Requirements & Process';
// Tells the shared layout that the role is student.
$role        = 'student';
// Marks requirements as the active sidebar menu item.
$currentPage = 'requirements';
// Stores the student's name for the header.
$userName    = $mockStudent['name'];
// Reads the student's chosen track.
$track       = $mockStudent['track'];
// Turns the track key into a readable label.
$trackLabel  = getTrackLabel($track);
// Loads the workflow definition for this track.
$workflow    = getWorkflow($track);
// Keeps the list of workflow stages.
$stages      = $workflow['stages'];
// Loads the student's saved progress for the track.
$progress    = getStudentProgress($mockStudent['email'], $track);
// Finds the index of the stage the student is on now.
$currentIndex = workflowCurrentIndex($progress, $stages);
// The stages the student is not allowed to work on yet.
// Maps which stages are locked and the reason for each.
$lockStates = stageLockStates($stages, $currentIndex);

// A student may only open the stage they are working on. A future stage is
// locked until the coordinator approves the progression.
// Checks which stage the student may open from the URL.
$access = resolveStudentStageAccess($progress, $stages, $currentIndex, (string) ($_GET['stage'] ?? ''));
// Stores the key of the stage currently being shown.
$openKey = $access['stageKey'];
// Keeps the lock warning text, or an empty string.
$lockedNotice = $access['locked'] ? $access['reason'] : '';
// If no stage was resolved, fall back to the current stage.
if ($openKey === '') {
    // Uses the current stage from the workflow.
    $openKey = ($stages[$currentIndex]['key'] ?? '');
    // Falls back to the first stage, or proposal, if needed.
    $openKey = $openKey ?: ($stages[0]['key'] ?? 'proposal');
// Closes the fallback if.
}

// Includes the shared header with styles and navigation.
require_once __DIR__ . '/../includes/layout/header.php';
// Closes the PHP block before the page HTML.
?>

<?php /* Banner at the top of the Process Tracker page. */ ?>
<div class="page-header page-banner">
<?php /*     Heading of the Process Tracker page. */ ?>
    <h2>Process Tracker</h2>
<?php /*     Shows the track label, program, and a short description. */ ?>
    <p><?= htmlspecialchars($trackLabel) ?> Track (<?= htmlspecialchars($mockStudent['program']) ?>) — step-by-step progress for each stage.</p>
<?php /* Closes the page banner. */ ?>
</div>

<?php /* Card that holds the stage stepper at the top. */ ?>
<div class="card" style="margin-bottom:1rem;">
<?php /*     Body of the stepper card. */ ?>
    <div class="card-body" style="padding-top:1.25rem;padding-bottom:1.25rem;">
<?php /*         Horizontal stepper listing all stages. */ ?>
        <div class="stage-stepper">
<?php /*             Loops over the stages with their index; opens PHP. */ ?>
            <?php foreach ($stages as $idx => $stage):
                // Gets the saved progress row for this stage, or null.
                $p = $progress[$idx] ?? null;
                // Reads the stage status, defaulting to not_started.
                $st = $p['stageStatus'] ?? 'not_started';
                // Checks whether this stage is locked.
                $isLocked = $lockStates[$stage['key']]['locked'] ?? false;
                // True when a real status was submitted, not pending or draft.
                $isSubmitted = $p && !in_array($st, ['not_started', 'pending', 'draft', ''], true);
                // True when the stage is completed, approved, or already passed.
                $isDone = in_array($st, ['completed', 'approved'], true) || $idx < $currentIndex;
                // Flags the stage as highlighted.
                $isHighlighted = $isSubmitted || $isDone;
                // True when this stage is the one currently open.
                $isActive = $stage['key'] === $openKey;
                // If the stage is locked, the badge should say Locked.
                if ($isLocked) {
                    // Label text for a locked stage.
                    $statusLabel = 'Locked';
                    // CSS class for the locked badge.
                    $statusClass = 'status-locked';
                // Else if a status was submitted, use its stored label.
                } elseif ($isSubmitted) {
                    // Friendly label for that status constant.
                    $statusLabel = STATUSES[$st]['label'] ?? 'Submitted';
                    // CSS class that colours that status badge.
                    $statusClass = STATUSES[$st]['class'] ?? 'status-submitted';
                // Otherwise nothing has been submitted for the stage.
                } else {
                    // Label for a stage with no submission.
                    $statusLabel = 'Not Submitted';
                    // Pending CSS class for that badge.
                    $statusClass = 'status-pending';
                // Closes the if/elseif/else that picks the status label.
                }
            // Closes the PHP block, inside the stage loop.
            ?>
<?php /*             One stepper item, marked locked when the stage is locked. */ ?>
            <div class="stage-stepper-item<?= $isLocked ? ' is-locked' : '' ?>">
<?php /*                 If the stage is locked, render it as not clickable. */ ?>
                <?php if ($isLocked): ?>
<?php /*                 Non-clickable button with a tooltip explaining the lock. */ ?>
                <div class="stage-stepper-btn" style="cursor:not-allowed;" title="<?= htmlspecialchars($lockStates[$stage['key']]['reason'] ?? '') ?>">
<?php /*                     Lock icon in place of the stage number. */ ?>
                    <span class="stage-stepper-num"><i class="fas fa-lock" aria-hidden="true"></i></span>
<?php /*                     Shows the stage's short label. */ ?>
                    <span class="stage-stepper-label"><?= htmlspecialchars($stage['shortLabel']) ?></span>
<?php /*                     Shows the locked status badge. */ ?>
                    <span class="status-badge <?= $statusClass ?>" style="font-size:0.65rem;padding:2px 6px;"><?= htmlspecialchars($statusLabel) ?></span>
<?php /*                 Closes the locked stepper button. */ ?>
                </div>
<?php /*                 Otherwise render the stage as a link the student can open. */ ?>
                <?php else: ?>
<?php /*                 Link that reloads the page with this stage selected in the URL. */ ?>
                <a href="<?= url('student/requirements.php?stage=' . urlencode($stage['key'])) ?>" class="stage-stepper-btn">
<?php /*                     Number circle, highlighted when submitted or done. */ ?>
                    <span class="stage-stepper-num <?= $isHighlighted ? 'highlighted done' : '' ?>">
<?php /*                         Shows the stage number. */ ?>
                        <?= $idx + 1 ?>
<?php /*                     Closes the number span. */ ?>
                    </span>
<?php /*                     Shows the short label, styled active or done. */ ?>
                    <span class="stage-stepper-label <?= $isActive ? 'is-active' : ($isHighlighted ? 'is-done' : '') ?>"><?= htmlspecialchars($stage['shortLabel']) ?></span>
<?php /*                     Shows the status badge for this stage. */ ?>
                    <span class="status-badge <?= $statusClass ?>" style="font-size:0.65rem;padding:2px 6px;"><?= htmlspecialchars($statusLabel) ?></span>
<?php /*                 Closes the stage link. */ ?>
                </a>
<?php /*                 Closes the lock if/else. */ ?>
                <?php endif; ?>
<?php /*                 Adds a connector line for every stage except the last. */ ?>
                <?php if ($idx < count($stages) - 1): ?>
<?php /*                 The connector line, filled when the stage is highlighted. */ ?>
                <div class="stage-stepper-line <?= $isHighlighted ? 'done' : '' ?>"></div>
<?php /*                 Closes the connector if. */ ?>
                <?php endif; ?>
<?php /*             Closes the stepper item div. */ ?>
            </div>
<?php /*             Ends the loop over the stages. */ ?>
            <?php endforeach; ?>
<?php /*         Closes the stepper row. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the stepper card. */ ?>
</div>

<?php /* If the student asked for a locked stage, show a warning. */ ?>
<?php if ($lockedNotice): ?>
<?php /* Warning alert box for the locked stage. */ ?>
<div class="alert alert-warning">
<?php /*     Lock icon inside the warning. */ ?>
    <i class="fas fa-lock"></i>
<?php /*     Explains that the student is shown their current stage instead. */ ?>
    <div><?= htmlspecialchars($lockedNotice) ?> You are now viewing the stage you are currently working on.</div>
<?php /* Closes the alert text div. */ ?>
</div>
<?php /* Closes the locked notice if. */ ?>
<?php endif; ?>

<?php /* Loops over the stages to render only the open one; opens PHP. */ ?>
<?php foreach ($stages as $idx => $stage):
    // Skips stages that are not the one currently open.
    if ($stage['key'] !== $openKey) {
        // Jumps to the next stage in the loop.
        continue;
    // Closes the skip if.
    }
    // Uses saved progress, or blank progress for the stage.
    $p = $progress[$idx] ?? blankStageProgress($stage);
    // True when the stage is completed or approved.
    $isDone = in_array($p['stageStatus'], ['completed', 'approved'], true);
    // Explains that the dashboard shows the same milestones.
    // Shared with the dashboard so both show the same milestones, worded for
    // Continues: the wording follows the student's own track and stage.
    // the student's own track and stage.
    // Builds the milestone steps for this stage and track.
    $steps = milestoneStepsForStage($p, $track, $stage['key']);
    // Finds the index of the first pending milestone.
    $firstPending = milestoneProgress($steps)['firstPending'];
    // Explains how the uploadable document types are chosen.
    // The document types the student may upload now. The same rule decides
    // Continues: the same rule builds the Application page list.
    // the Document Type list of the Application page. A stage that is locked
    // Continues: a locked stage has no open document type.
    // has no open document type at all.
    // Document types the student may upload now; none if locked.
    $openTypes = ($lockStates[$stage['key']]['locked'] ?? false) ? [] : openDocumentTypes($steps);
// Closes the PHP block before the stage card.
?>
<?php /* Card for the open stage. */ ?>
<div class="card">
<?php /*     Header tinted green when done, navy otherwise. */ ?>
    <div class="card-header" style="background:<?= $isDone ? 'rgba(22,163,74,0.06)' : 'rgba(6,2,151,0.04)' ?>;">
<?php /*         Heading row with the stage number, label, and badge. */ ?>
        <h3 style="display:flex;align-items:center;gap:0.6rem;">
<?php /*             Number circle, green when the stage is done. */ ?>
            <span class="stage-stepper-num <?= $isDone ? 'highlighted done' : 'active' ?>" style="width:28px;height:28px;font-size:0.8rem;"><?= $idx + 1 ?></span>
<?php /*             Shows the full stage label. */ ?>
            <?= htmlspecialchars($stage['label']) ?>
<?php /*             Shows the stage status badge. */ ?>
            <?= statusBadge($p['stageStatus']) ?>
<?php /*         Closes the heading row. */ ?>
        </h3>
<?php /*     Closes the card header. */ ?>
    </div>
<?php /*     Body of the stage card. */ ?>
    <div class="card-body">
<?php /*         If the coordinator left a note, show it first. */ ?>
        <?php if (!empty($p['coordinatorComments'])): ?>
<?php /*         Warning alert box for the coordinator note. */ ?>
        <div class="alert alert-warning" style="margin-bottom:1.25rem;">
<?php /*             Comment icon inside the note box. */ ?>
            <i class="fas fa-comment-dots"></i>
<?php /*             Shows the coordinator note text. */ ?>
            <div><strong>Coordinator:</strong> <?= htmlspecialchars($p['coordinatorComments']) ?></div>
<?php /*         Closes the note text div. */ ?>
        </div>
<?php /*         Closes the coordinator note if. */ ?>
        <?php endif; ?>

<?php /*         Wrapper that lists the milestone steps of the stage. */ ?>
        <div class="substep-list">
<?php /*             Starts PHP to find the student's next step. */ ?>
            <?php
            // Comment describing the next-step banner below.
            // The next step of the student, in the order of their workflow.
            // Finds the next pending milestone of this stage.
            $nextStep = nextMilestoneStep($steps);
            // Closes the PHP block for the next step.
            ?>
<?php /*             Only shows the next-step banner when a step exists. */ ?>
            <?php if ($nextStep): ?>
<?php /*             True when the next step is flagged by the coordinator. */ ?>
            <?php $nextStepFlagged = $nextStep['status'] === 'flagged'; ?>
<?php /*             Banner div with classes for coordinator or alert state. */ ?>
            <div class="tpl-next-step<?= $nextStep['coord'] ? ' is-coord' : '' ?><?= $nextStepFlagged ? ' is-alert' : '' ?>">
<?php /*                 Picks warning, coordinator, or arrow icon. */ ?>
                <i class="fas <?= $nextStepFlagged ? 'fa-exclamation-triangle' : ($nextStep['coord'] ? 'fa-user-tie' : 'fa-arrow-right') ?>" aria-hidden="true"></i>
<?php /*                 Wraps the banner text. */ ?>
                <div>
<?php /*                     Shows Action needed or Next step plus the label. */ ?>
                    <strong><?= $nextStepFlagged ? 'Action needed' : 'Next step' ?>: <?= htmlspecialchars($nextStep['label']) ?></strong>
<?php /*                     Shows the detail sentence for the next step. */ ?>
                    <span><?= htmlspecialchars($nextStep['detail']) ?></span>
<?php /*                 Closes the banner text wrapper. */ ?>
                </div>
<?php /*             Closes the next-step banner. */ ?>
            </div>
<?php /*             Closes the if for the next-step banner. */ ?>
            <?php endif; ?>
<?php /*             Loops over the milestone steps; opens PHP. */ ?>
            <?php foreach ($steps as $si => $step):
                // Reads the step status, defaulting to pending.
                $st = $step['state']['status'] ?? 'pending';
                // True when the student submitted the step.
                $isSubmitted = $st === 'submitted';
                // Chooses the icon state for the step.
                $iconState = $st === 'flagged' ? 'flagged' : ($st === 'done' ? 'done' : ($isSubmitted ? 'submitted' : ($si === $firstPending ? 'active' : 'pending')));
            // Closes the PHP block, inside the step loop.
            ?>
<?php /*             One milestone row in the substep list. */ ?>
            <div class="substep-row">
<?php /*                 Left column holding the step icon. */ ?>
                <div class="substep-icon-col">
<?php /*                     Draws the icon matching the step state. */ ?>
                    <?= stepIconHtml($iconState) ?>
<?php /*                     Adds a connector line for all but the last step. */ ?>
                    <?php if ($si < count($steps) - 1): ?><div class="substep-line <?= $st === 'done' ? 'done' : '' ?>"></div><?php endif; ?>
<?php /*                 Closes the icon column. */ ?>
                </div>
<?php /*                 Main column holding the label and detail. */ ?>
                <div style="flex:1;">
<?php /*                     Row that holds the label and its tags. */ ?>
                    <div style="display:flex;align-items:center;gap:0.4rem;margin-bottom:0.2rem;">
<?php /*                         Shows the milestone label, grey when still pending. */ ?>
                        <span style="font-size:0.875rem;font-weight:600;color:<?= $st === 'pending' ? 'var(--gray-400)' : 'var(--gray-800)' ?>;"><?= htmlspecialchars($step['label']) ?></span>
<?php /*                         Shows a Coordinator chip for staff-handled steps. */ ?>
                        <?php if ($step['coord']): ?><span class="coord-chip">Coordinator</span><?php endif; ?>
<?php /*                         Shows a Verified tag on completed steps. */ ?>
                        <?php if ($st === 'done'): ?><span class="ms-step-tag done">Verified</span><?php endif; ?>
<?php /*                         Shows a Submitted tag on submitted steps. */ ?>
                        <?php if ($isSubmitted): ?><span class="ms-step-tag submitted">Submitted</span><?php endif; ?>
<?php /*                         Shows an Action needed tag on flagged steps. */ ?>
                        <?php if ($st === 'flagged'): ?><span class="ms-step-tag flagged">Action needed</span><?php endif; ?>
<?php /*                     Closes the label row. */ ?>
                    </div>
<?php /*                     Shows the detail text, amber when the step is flagged. */ ?>
                    <p style="font-size:0.78rem;color:<?= $st === 'flagged' ? '#b45309' : 'var(--gray-500)' ?>;margin:0;line-height:1.4;"><?= htmlspecialchars($step['detail']) ?></p>
<?php /*                 Closes the main column. */ ?>
                </div>
<?php /*                 Right column that holds the Upload button. */ ?>
                <div>
<?php /*                     Starts PHP to decide whether to show an upload button. */ ?>
                    <?php
                    // Comment: the Upload button follows the Application page rule.
                    // The Upload button follows the same rule as the Document
                    // Comment continues: it offers the current milestone's document.
                    // Type list of the Application page: the document of the
                    // Comment continues: only open documents get an upload link.
                    // milestone the student is working on.
                    // Reads the document type required by this milestone.
                    $stepDocType = (string) ($step['docType'] ?? '');
                    // Shows Upload only when that document type is open.
                    if ($stepDocType !== '' && in_array($stepDocType, $openTypes, true)):
                    // Closes the PHP block before the button.
                    ?>
<?php /*                     Upload link that opens the Application page for this document. */ ?>
                    <a class="btn btn-sm btn-primary" href="<?= url('student/application.php?stage=' . urlencode($stage['key']) . '&docType=' . urlencode($stepDocType)) ?>"><i class="fas fa-upload"></i> Upload</a>
<?php /*                     Closes the if that shows the Upload button. */ ?>
                    <?php endif; ?>
<?php /*                 Closes the action column. */ ?>
                </div>
<?php /*             Closes the milestone row. */ ?>
            </div>
<?php /*             Ends the loop over milestone steps. */ ?>
            <?php endforeach; ?>
<?php /*         Closes the substep list wrapper. */ ?>
        </div>

<?php /*         Box listing the documents required for this stage. */ ?>
        <div class="req-docs-box">
<?php /*             Heading of the required documents box. */ ?>
            <h4>Required Documents for this Stage</h4>
<?php /*             Starts PHP to build the document list. */ ?>
            <?php
            // Joins the stage documents with the two endorsement forms.
            $docs = array_merge($stage['documents'], [$stage['adviserEndorsementForm'], $stage['gradSchoolEndorsementForm']]);
            // Loops over each required document.
            foreach ($docs as $doc):
                // Skips receipts here; they are shown in a separate box.
                if (($doc['type'] ?? '') === 'receipt') {
                    // Jumps to the next document.
                    continue;
                // Closes the receipt skip if.
                }
            // Closes the PHP block before the document rows.
            ?>
<?php /*             One row for a required document. */ ?>
            <div class="req-doc-row">
<?php /*                 Form or document icon depending on the file type. */ ?>
                <i class="fas <?= ($doc['type'] ?? '') === 'form' ? 'fa-file-signature' : 'fa-file-word' ?>"></i>
<?php /*                 Shows the document label. */ ?>
                <span><?= htmlspecialchars($doc['label']) ?></span>
<?php /*                 Only shows Download when a file exists. */ ?>
                <?php if (!empty($doc['file'])): ?>
<?php /*                 Download link that saves the file from the papers folder. */ ?>
                <a class="btn btn-sm btn-outline" href="<?= htmlspecialchars(papersUrl($doc['folder'], $doc['file'])) ?>" download><i class="fas fa-download"></i> Download</a>
<?php /*                 Closes the download if. */ ?>
                <?php endif; ?>
<?php /*             Closes the document row. */ ?>
            </div>
<?php /*             Ends the loop over required documents. */ ?>
            <?php endforeach; ?>
<?php /*         Closes the required documents box. */ ?>
        </div>

<?php /*         Loops over the stage documents to find the receipt. */ ?>
        <?php foreach ($stage['documents'] as $doc):
            // Skips everything that is not a receipt.
            if (($doc['type'] ?? '') !== 'receipt') {
                // Jumps to the next document.
                continue;
            // Closes the not-a-receipt check.
            }
        // Closes the PHP block before the receipt box.
        ?>
<?php /*         Box that asks the student for payment proof. */ ?>
        <div class="receipt-box">
<?php /*             Row holding the receipt icon and title. */ ?>
            <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.65rem;">
<?php /*                 Receipt icon in gold. */ ?>
                <i class="fas fa-receipt" style="color:var(--adzu-gold);"></i>
<?php /*                 Title text Payment Proof Required. */ ?>
                <span style="font-weight:700;font-size:0.82rem;text-transform:uppercase;letter-spacing:0.05em;color:var(--gray-600);">Payment Proof Required</span>
<?php /*             Closes the title row. */ ?>
            </div>
<?php /*             Shows the receipt document label. */ ?>
            <p style="font-size:0.83rem;color:var(--gray-700);margin-bottom:0.75rem;"><?= htmlspecialchars($doc['label']) ?></p>
<?php /*             Starts PHP to check whether the receipt is open. */ ?>
            <?php
            // Comment: the button follows the Application page rule.
            // The button follows the same rule as the Document Type list of
            // Comment continues: only the workflow can open this receipt.
            // the Application page: the receipt is only open when the
            // Comment continues: otherwise the receipt stays locked.
            // workflow of the track asks for it.
            // True when the receipt document type is currently open.
            $receiptOpen = in_array('Official Receipt / Payment Proof', $openTypes, true);
            // Closes the PHP block before the button.
            ?>
<?php /*             If the receipt is open, show an upload button. */ ?>
            <?php if ($receiptOpen): ?>
<?php /*             Upload Receipt link for the Official Receipt document. */ ?>
            <a class="btn btn-primary btn-sm" href="<?= url('student/application.php?stage=' . urlencode($stage['key']) . '&docType=' . urlencode('Official Receipt / Payment Proof')) ?>"><i class="fas fa-upload"></i> Upload Receipt</a>
<?php /*             Otherwise explain that the receipt is still locked. */ ?>
            <?php else: ?>
<?php /*             Hint paragraph shown instead of the button. */ ?>
            <p class="field-hint" style="margin:0;">
<?php /*                 Lock icon in the hint. */ ?>
                <i class="fas fa-lock" aria-hidden="true"></i>
<?php /*                 Text saying the receipt opens after the earlier milestones. */ ?>
                The receipt opens when the milestones before it are done.
<?php /*             Closes the hint paragraph. */ ?>
            </p>
<?php /*             Closes the receipt open if/else. */ ?>
            <?php endif; ?>
<?php /*         Closes the receipt box. */ ?>
        </div>
<?php /*         Ends the loop over the stage documents. */ ?>
        <?php endforeach; ?>
<?php /*     Closes the stage card body. */ ?>
    </div>
<?php /* Closes the stage card. */ ?>
</div>
<?php /* Ends the loop over the stages. */ ?>
<?php endforeach; ?>

<?php /* Card at the bottom with a link to the templates page. */ ?>
<div class="card">
<?php /*     Body of the bottom info card. */ ?>
    <div class="card-body">
<?php /*         Paragraph that points students to the templates page. */ ?>
        <p style="font-size:0.875rem;color:var(--gray-500);margin:0;">
<?php /*             Info icon before the paragraph text. */ ?>
            <i class="fas fa-info-circle" style="margin-right:6px;color:var(--adzu-navy);"></i>
<?php /*             Link to the Templates and Forms page. */ ?>
            Download templates and forms from the <a href="<?= url('student/templates.php') ?>"><strong>Templates &amp; Forms</strong></a> page.
<?php /*         Closes the paragraph. */ ?>
        </p>
<?php /*     Closes the info card body. */ ?>
    </div>
<?php /* Closes the info card. */ ?>
</div>

<?php /* Includes the shared footer that ends the page. */ ?>
<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
