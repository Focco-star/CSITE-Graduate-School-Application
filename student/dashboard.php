<?php /* Opens the PHP block for the student dashboard page. */ ?>
<?php
// Loads the shared config with session, constants, and helper functions.
require_once __DIR__ . '/../includes/config/config.php';
// Loads the DB helper class used for the student lookup below.
require_once __DIR__ . '/../includes/config/db.php';

// Checks that someone is logged in and that their role is student.
if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'student') {
    // Sends guests and non-student users to the student login page.
    redirectTo('student/login.php');
// Closes the if that redirects non-students to the login page.
}

// Copies the logged-in user details from the session into $user.
$user = $_SESSION['user'];

// Finds the students row whose user_id matches this account.
$student = DB::find('students', ['user_id' => $user['user_id']]);

// If no student profile row was found, stop rendering the page.
if (!$student) {
    // Shows a plain error message and ends the request.
    die("Student profile not found.");
// Closes the if that stops the page when no profile exists.
}

// Sets the page title shown in the browser and header.
$pageTitle   = 'Dashboard';
// Tells the shared layout that the current role is student.
$role        = 'student';
// Marks Dashboard as the active sidebar menu item.
$currentPage = 'dashboard';
// Stores the user's full name for the header display.
$userName    = $user['full_name'];
// Reads the student's chosen track, defaulting to thesis.
$track       = $student['track'] ?? 'thesis';
// Turns the track key into a readable label such as Thesis.
$trackLabel  = getTrackLabel($track);
// Loads the workflow definition, which holds the stage list.
$workflow    = getWorkflow($track);
// Keeps the stages array, or an empty array if missing.
$stages      = $workflow['stages'] ?? [];

// Loads the student's saved progress for every stage.
$progress   = getStudentProgress($user['email'], $track);
// Finds the index of the stage the student is currently on.
$currentIdx = workflowCurrentIndex($progress, $stages);
// The stages the student is not allowed to work on yet.
// Builds a map of which stages are locked and the reason why.
$lockStates = stageLockStates($stages, $currentIdx);

// Picks the progress row of the current stage, or the first one.
$activeStage = $progress[$currentIdx] ?? ($progress[0] ?? null);

// Panel, adviser and documenter of every scheduled presentation, keyed by the
// workflow stage of the presentation.
// Loads panel, adviser, and documenter info per presentation stage.
$presentationDetails = studentPresentationDetails($user['email']);
// "Juan R. Dela Cruz" — used by the welcome card and the sign-in notification.
// Builds the friendly display name used in the welcome banner.
$welcomeName = studentWelcomeName($student, $user);

// Includes the shared header with styles, sidebar, and top bar.
require_once __DIR__ . '/../includes/layout/header.php';
// Closes the PHP block before the HTML output starts.
?>

<?php /* Banner at the top of the dashboard that greets the student. */ ?>
<div class="page-header page-banner">
<?php /*     Shows Welcome plus the student's display name, safely escaped. */ ?>
    <h2>Welcome, Student <?= htmlspecialchars($welcomeName) ?></h2>
<?php /*     Shows the student's program and track label under the greeting. */ ?>
    <p><?= htmlspecialchars($student['program']) ?> — <?= htmlspecialchars($trackLabel) ?> Track</p>
<?php /* Closes the welcome page banner div. */ ?>
</div>

<?php /* Card that holds the workflow progress stepper. */ ?>
<div class="card">
<?php /*     Header area of the progress card. */ ?>
    <div class="card-header">
<?php /*         Card title showing the track label with a route icon. */ ?>
        <h3><i class="fas fa-route"></i> <?= htmlspecialchars($trackLabel) ?> Progress</h3>
<?php /*     Closes the progress card header. */ ?>
    </div>
<?php /*     Body of the progress card that holds the stepper. */ ?>
    <div class="card-body">
<?php /*         Horizontal stepper row listing every stage of the workflow. */ ?>
        <div class="stage-stepper" style="margin-bottom:1.5rem;padding-bottom:0.5rem;">
<?php /*             Loops over each workflow stage with its index; opens PHP. */ ?>
            <?php foreach ($stages as $idx => $stage):
                // Gets the saved progress row for this stage, or null.
                $p = $progress[$idx] ?? null;
                // Reads this stage's status, defaulting to not_started.
                $st = $p['stageStatus'] ?? 'not_started';
                // Checks whether this stage is locked for the student.
                $isLocked = $lockStates[$stage['key']]['locked'] ?? false;
                // True when a real status was submitted, not pending or draft.
                $isSubmitted = $p && !in_array($st, ['not_started', 'pending', 'draft', ''], true);
                // True when the stage is completed, approved, or already passed.
                $isDone = in_array($st, ['completed', 'approved'], true) || $idx < $currentIdx;
                // Flags the stepper number so it gets highlight styling.
                $isHighlighted = $isSubmitted || $isDone;
                // True when this is the stage the student is working on now.
                $isActive = $idx === $currentIdx;
                // If the stage is locked, the badge should say Locked.
                if ($isLocked) {
                    // Sets the label text for a locked stage.
                    $statusLabel = 'Locked';
                    // Sets the CSS class used by a locked badge.
                    $statusClass = 'status-locked';
                // Else if a status was submitted, use its stored status label.
                } elseif ($isSubmitted) {
                    // Gets the friendly label for that status constant.
                    $statusLabel = STATUSES[$st]['label'] ?? 'Submitted';
                    // Gets the CSS class that colours that status badge.
                    $statusClass = STATUSES[$st]['class'] ?? 'status-submitted';
                // Otherwise the stage has nothing submitted yet.
                } else {
                    // Label shown for a stage with no submission.
                    $statusLabel = 'Not Submitted';
                    // Pending CSS class for the not-submitted badge.
                    $statusClass = 'status-pending';
                // Closes the if/elseif/else that picks the status label.
                }
            // Closes the PHP block, still inside the stage loop.
            ?>
<?php /*             One stepper item; adds is-locked when the stage is locked. */ ?>
            <div class="stage-stepper-item<?= $isLocked ? ' is-locked' : '' ?>">
<?php /*                 Stepper button wrapper whose tooltip explains any lock. */ ?>
                <div class="stage-stepper-btn" style="cursor:default;" title="<?= htmlspecialchars($lockStates[$stage['key']]['reason'] ?? '') ?>">
<?php /*                     Number circle, highlighted when the stage is submitted or done. */ ?>
                    <span class="stage-stepper-num <?= $isHighlighted ? 'highlighted done' : ($isActive ? 'active' : '') ?>">
<?php /*                         Shows a lock icon when locked, otherwise the stage number. */ ?>
                        <?php if ($isLocked): ?><i class="fas fa-lock" aria-hidden="true"></i><?php else: ?><?= $idx + 1 ?><?php endif; ?>
<?php /*                     Closes the number span opened on the previous line. */ ?>
                    </span>
<?php /*                     Shows the stage short label, styled active or done. */ ?>
                    <span class="stage-stepper-label <?= $isActive ? 'is-active' : ($isHighlighted ? 'is-done' : '') ?>"><?= htmlspecialchars($stage['shortLabel']) ?></span>
<?php /*                     Shows the small status badge with its label and colour. */ ?>
                    <span class="status-badge <?= $statusClass ?>" style="font-size:0.65rem;padding:2px 6px;"><?= htmlspecialchars($statusLabel) ?></span>
<?php /*                 Closes the stepper button wrapper. */ ?>
                </div>
<?php /*                 Adds a connector line for every stage except the last one. */ ?>
                <?php if ($idx < count($stages) - 1): ?>
<?php /*                 The connector line drawn between two stepper circles. */ ?>
                <div class="stage-stepper-line <?= $isHighlighted ? 'done' : '' ?>"></div>
<?php /*                 Closes the if that adds the connector line. */ ?>
                <?php endif; ?>
<?php /*             Closes the stepper item div. */ ?>
            </div>
<?php /*             Ends the loop over the workflow stages. */ ?>
            <?php endforeach; ?>
<?php /*         Closes the stepper row div. */ ?>
        </div>

<?php /*         Only shows the current-stage panel when a stage exists. */ ?>
        <?php if ($activeStage): ?>
<?php /*         Grey panel box that describes the student's current stage. */ ?>
        <div style="background:var(--gray-50);border-radius:var(--radius-lg);padding:1.25rem;border:1px solid var(--gray-200);">
<?php /*             Heading of the current-stage box. */ ?>
            <h4 style="color:var(--adzu-navy);margin-bottom:0.75rem;">
<?php /*                 Map-marker icon placed before the Current Stage text. */ ?>
                <i class="fas fa-map-marker-alt" style="margin-right:6px;"></i>
<?php /*                 Shows the full label of the current stage. */ ?>
                Current Stage: <?= htmlspecialchars($stages[$currentIdx]['label'] ?? '') ?>
<?php /*             Closes the current-stage heading. */ ?>
            </h4>
<?php /*             If the coordinator left a note on this stage, show it. */ ?>
            <?php if (!empty($activeStage['coordinatorComments'])): ?>
<?php /*             Warning alert box that holds the coordinator note. */ ?>
            <div class="alert alert-warning" style="margin-top:0;margin-bottom:0.75rem;">
<?php /*                 Chat icon shown inside the coordinator note box. */ ?>
                <i class="fas fa-comment-alt"></i>
<?php /*                 Shows the coordinator note text, safely escaped. */ ?>
                <div><strong>Coordinator note:</strong> <?= htmlspecialchars($activeStage['coordinatorComments']) ?></div>
<?php /*             Closes the warning alert box. */ ?>
            </div>
<?php /*             Closes the if that checks for a coordinator note. */ ?>
            <?php endif; ?>
<?php /*         Closes the current-stage grey panel. */ ?>
        </div>
<?php /*         Closes the if that shows the current stage. */ ?>
        <?php endif; ?>
<?php /*     Closes the progress card body. */ ?>
    </div>
<?php /* Closes the progress card. */ ?>
</div>

<?php /* Starts PHP to compute the numbers for the milestone tracker. */ ?>
<?php
/*
 * Milestone Tracker — the same milestones the student sees on the Process
 * Tracker (Requirements) page, shown on the dashboard for every stage of the
 * student's own track. The wording of each milestone follows the track and the
 * stage, so thesis, capstone and seminar-paper students each see the labels
 * that apply to their own paper.
 */
// Counter for all milestones across the student's stages.
$milestoneTotal = 0;
// Counter for milestones the coordinator already verified.
$milestoneDone = 0;
// Holds one block of tracker data per stage.
$milestoneBlocks = [];
// Loops over every stage to build its milestone block.
foreach ($stages as $idx => $stage) {
    // Uses saved progress, or blank progress when none exists yet.
    $row = $progress[$idx] ?? blankStageProgress($stage);
    // Builds the milestone steps for this stage and this track.
    $steps = milestoneStepsForStage($row, $track, $stage['key']);
    // Counts the total and completed milestones in those steps.
    $stats = milestoneProgress($steps);
    // Adds this stage's milestone total to the overall total.
    $milestoneTotal += $stats['total'];
    // Adds this stage's completed milestones to the overall count.
    $milestoneDone += $stats['done'];
    // Starts the array entry that describes this stage for the view.
    $milestoneBlocks[] = [
        // Stores the stage definition, such as its label and key.
        'stage'     => $stage,
        // Stores the stage's index number.
        'index'     => $idx,
        // Stores the milestone steps of this stage.
        'steps'     => $steps,
        // Stores the done and total counts for this stage.
        'stats'     => $stats,
        // Marks whether this is the stage the student is on now.
        'isCurrent' => $idx === $currentIdx,
        // Marks stages that are finished or already passed.
        'isDone'    => in_array($row['stageStatus'] ?? 'not_started', ['completed', 'approved'], true) || $idx < $currentIdx,
        // Marks the stage locked so the view can hide upload buttons.
        'isLocked'  => $lockStates[$stage['key']]['locked'] ?? false,
    // Closes the array entry for this stage.
    ];
// Closes the loop that builds the milestone blocks.
}
// Calculates the overall milestone completion percentage.
$milestonePct = $milestoneTotal > 0 ? (int) round($milestoneDone * 100 / $milestoneTotal) : 0;
// Closes the PHP block before the tracker HTML.
?>
<?php /* Card that shows the Milestone Tracker. */ ?>
<div class="card milestone-card" id="milestone-tracker">
<?php /*     Header of the milestone tracker card. */ ?>
    <div class="card-header">
<?php /*         Title of the card with a tasks icon. */ ?>
        <h3><i class="fas fa-tasks"></i> Milestone Tracker</h3>
<?php /*         Right-side overview with the counts and progress bar. */ ?>
        <div class="ms-overview">
<?php /*             Text such as 3 of 12 milestones complete. */ ?>
            <span class="ms-overview-text"><?= (int) $milestoneDone ?> of <?= (int) $milestoneTotal ?> milestones complete</span>
<?php /*             Progress bar filled to the completion percentage. */ ?>
            <span class="ms-overview-bar"><span class="ms-overview-fill" style="width:<?= (int) $milestonePct ?>%;"></span></span>
<?php /*         Closes the overview wrapper. */ ?>
        </div>
<?php /*     Closes the tracker card header. */ ?>
    </div>
<?php /*     Body of the milestone tracker card. */ ?>
    <div class="card-body">
<?php /*         Starts PHP to work out the student's next step. */ ?>
        <?php
        // Explains the next-step banner: the first step the student must do.
        // The next step of the student, in the order of their workflow. It
        // Continues that note: the next step changes after a document is verified.
        // changes as soon as the coordinator verifies a document.
        // Finds the next pending milestone in the first stage block.
        $nextStep = nextMilestoneStep($milestoneBlocks[0]['steps'] ?? []);
        // Closes the PHP block for the next step.
        ?>
<?php /*         Only shows the next-step banner when a step was found. */ ?>
        <?php if ($nextStep): ?>
<?php /*         True when the next step was flagged by the coordinator. */ ?>
        <?php $nextStepFlagged = $nextStep['status'] === 'flagged'; ?>
<?php /*         Banner div with extra classes for coordinator or alert state. */ ?>
        <div class="tpl-next-step<?= $nextStep['coord'] ? ' is-coord' : '' ?><?= $nextStepFlagged ? ' is-alert' : '' ?>">
<?php /*             Chooses a warning, coordinator, or arrow icon for the banner. */ ?>
            <i class="fas <?= $nextStepFlagged ? 'fa-exclamation-triangle' : ($nextStep['coord'] ? 'fa-user-tie' : 'fa-arrow-right') ?>" aria-hidden="true"></i>
<?php /*             Wraps the banner text so it sits beside the icon. */ ?>
            <div>
<?php /*                 Shows Action needed or Next step plus the milestone label. */ ?>
                <strong><?= $nextStepFlagged ? 'Action needed' : 'Next step' ?>: <?= htmlspecialchars($nextStep['label']) ?></strong>
<?php /*                 Shows the detail sentence explaining the next step. */ ?>
                <span><?= htmlspecialchars($nextStep['detail']) ?></span>
<?php /*             Closes the banner text wrapper. */ ?>
            </div>
<?php /*         Closes the next-step banner. */ ?>
        </div>
<?php /*         Closes the if for the next-step banner. */ ?>
        <?php endif; ?>
<?php /*         Loops over each stage block to render it; opens PHP. */ ?>
        <?php foreach ($milestoneBlocks as $block):
            // Alias for the stage definition of this block.
            $stage = $block['stage'];
            // Alias for the milestone steps of this block.
            $steps = $block['steps'];
            // Alias for the done and total counts of this block.
            $stats = $block['stats'];
            // Reads the saved stage status for this stage.
            $stageStatus = $progress[$block['index']]['stageStatus'] ?? 'not_started';
            // True when the stage has a real submitted status.
            $stageIsSubmitted = !in_array($stageStatus, ['not_started', 'pending', 'draft', ''], true);
            // Badge label: Locked, the real status label, or Not Started.
            $stageStatusLabel = $block['isLocked'] ? 'Locked' : ($stageIsSubmitted ? (STATUSES[$stageStatus]['label'] ?? 'Submitted') : 'Not Started');
            // Badge CSS class that matches the label above.
            $stageStatusClass = $block['isLocked'] ? 'status-locked' : ($stageIsSubmitted ? (STATUSES[$stageStatus]['class'] ?? 'status-submitted') : 'status-pending');
            // Keeps current or finished stages expanded by default.
            $openByDefault = $block['isCurrent'] || $block['isDone'];
            // Explains how the open document types are chosen for this stage.
            // The document types the student may upload now. The same rule
            // Continues: the same rule picks the Application page document list.
            // decides the Document Type list of the Application page. A stage
            // Continues: a locked stage has no open document type at all.
            // that is locked has no open document type at all.
            // Document types the student may upload now; none if locked.
            $openTypes = $block['isLocked'] ? [] : openDocumentTypes($steps);
        // Closes the PHP block, still inside the stage block loop.
        ?>
<?php /*         One collapsible stage block with current, done, locked classes. */ ?>
        <div class="ms-stage<?= $block['isCurrent'] ? ' is-current' : '' ?><?= $block['isDone'] ? ' is-done' : '' ?><?= $block['isLocked'] ? ' is-locked' : '' ?><?= $openByDefault ? ' is-open' : '' ?>" data-ms-stage>
<?php /*             Clickable header button that opens or closes the stage. */ ?>
            <button type="button" class="ms-stage-head" data-ms-toggle aria-expanded="<?= $openByDefault ? 'true' : 'false' ?>">
<?php /*                 Shows a lock icon when locked, otherwise the stage number. */ ?>
                <span class="ms-stage-num"><?= $block['isLocked'] ? '<i class="fas fa-lock" aria-hidden="true"></i>' : (int) $block['index'] + 1 ?></span>
<?php /*                 Shows the full stage label. */ ?>
                <span class="ms-stage-title"><?= htmlspecialchars($stage['label']) ?></span>
<?php /*                 Shows how many milestones are done out of the total. */ ?>
                <span class="ms-stage-count"><?= (int) $stats['done'] ?> / <?= (int) $stats['total'] ?> milestones</span>
<?php /*                 Shows the status badge for this stage. */ ?>
                <span class="status-badge <?= $stageStatusClass ?>" style="font-size:0.65rem;padding:2px 6px;"><?= htmlspecialchars($stageStatusLabel) ?></span>
<?php /*                 Chevron icon hinting that the block can be expanded. */ ?>
                <i class="fas fa-chevron-down ms-stage-caret" aria-hidden="true"></i>
<?php /*             Closes the stage header button. */ ?>
            </button>
<?php /*             Body of the stage block that holds the milestone steps. */ ?>
            <div class="ms-stage-body">
<?php /*                 If the stage is locked, show the reason instead of steps. */ ?>
                <?php if ($block['isLocked']): ?>
<?php /*                 Note explaining why this stage is locked. */ ?>
                <p class="ms-locked-note"><i class="fas fa-lock" aria-hidden="true"></i> <?= htmlspecialchars($lockStates[$stage['key']]['reason'] ?? 'This stage is locked.') ?></p>
<?php /*                 Closes the locked-note if. */ ?>
                <?php endif; ?>
<?php /*                 Loops over each milestone step of the stage; opens PHP. */ ?>
                <?php foreach ($steps as $si => $step):
                    // Reads the step status, defaulting to pending.
                    $state = $step['state']['status'] ?? 'pending';
                    // True when the step is verified as done.
                    $isDone = $state === 'done';
                    // True when the coordinator flagged the step.
                    $isFlagged = $state === 'flagged';
                    // True when the student submitted the step.
                    $isSubmitted = $state === 'submitted';
                    // True for the first pending step of an unlocked stage.
                    $isActive = !$block['isLocked'] && !$isDone && !$isFlagged && !$isSubmitted && $si === $stats['firstPending'];
                    // Chooses which icon state to draw for the step.
                    $iconState = $isFlagged ? 'flagged' : ($isDone ? 'done' : ($isSubmitted ? 'submitted' : ($isActive ? 'active' : 'pending')));
                    // Chooses the row CSS class that matches the step state.
                    $rowClass = $isFlagged ? 'is-flagged' : ($isDone ? 'is-done' : ($isSubmitted ? 'is-submitted' : ($isActive ? 'is-active' : 'is-pending')));
                // Closes the PHP block, still inside the step loop.
                ?>
<?php /*                 One milestone step row with its state class. */ ?>
                <div class="ms-step <?= $rowClass ?>">
<?php /*                     Left column that holds the step icon and connector line. */ ?>
                    <div class="ms-step-col">
<?php /*                         Draws the icon that matches the step state. */ ?>
                        <?= stepIconHtml($iconState) ?>
<?php /*                         Adds a connector line for every step except the last. */ ?>
                        <?php if ($si < count($steps) - 1): ?>
<?php /*                         Connector line, filled when the step is done. */ ?>
                        <span class="ms-step-line<?= $isDone ? ' done' : '' ?>"></span>
<?php /*                         Closes the if that adds the connector line. */ ?>
                        <?php endif; ?>
<?php /*                     Closes the left icon column. */ ?>
                    </div>
<?php /*                     Body of the step that holds its label and detail. */ ?>
                    <div class="ms-step-body">
<?php /*                         Row holding the step label and its small tags. */ ?>
                        <div class="ms-step-label">
<?php /*                             Shows the milestone label text. */ ?>
                            <span><?= htmlspecialchars($step['label']) ?></span>
<?php /*                             Shows a Coordinator chip when staff handle this step. */ ?>
                            <?php if ($step['coord']): ?><span class="coord-chip">Coordinator</span><?php endif; ?>
<?php /*                             Shows a Next tag on the step the student should do. */ ?>
                            <?php if ($isActive): ?><span class="ms-step-tag active">Next</span><?php endif; ?>
<?php /*                             Shows a Verified tag on completed steps. */ ?>
                            <?php if ($isDone): ?><span class="ms-step-tag done">Verified</span><?php endif; ?>
<?php /*                             Shows a Submitted tag on steps waiting for verification. */ ?>
                            <?php if ($isSubmitted): ?><span class="ms-step-tag submitted">Submitted</span><?php endif; ?>
<?php /*                             Shows an Action needed tag on flagged steps. */ ?>
                            <?php if ($isFlagged): ?><span class="ms-step-tag flagged">Action needed</span><?php endif; ?>
<?php /*                         Closes the step label row. */ ?>
                        </div>
<?php /*                         Shows the detail text telling the student what to do. */ ?>
                        <p class="ms-step-detail"><?= htmlspecialchars($step['detail']) ?></p>
<?php /*                     Closes the step body. */ ?>
                    </div>
<?php /*                     Right column that holds the Upload button. */ ?>
                    <div class="ms-step-action">
<?php /*                         Starts PHP to decide whether an upload button is shown. */ ?>
                        <?php
                        // Explains the rule used by the Upload button in this column.
                        // The Upload button follows the same rule as the Document
                        // Continues: it follows the Application page document type list.
                        // Type list of the Application page: the document of
                        // Continues: only the document of the current milestone is offered.
                        // the milestone the student is working on.
                        // Reads the document type required by this milestone.
                        $stepDocType = (string) ($step['docType'] ?? '');
                        // Shows Upload only when that document type is currently open.
                        if ($stepDocType !== '' && in_array($stepDocType, $openTypes, true)):
                        // Closes the PHP block before the button markup.
                        ?>
<?php /*                         Upload link that opens the Application page for this document. */ ?>
                        <a class="btn btn-sm btn-primary" href="<?= url('student/application.php?stage=' . urlencode($stage['key']) . '&docType=' . urlencode($stepDocType)) ?>"><i class="fas fa-upload"></i> Upload</a>
<?php /*                         Closes the if that shows the Upload button. */ ?>
                        <?php endif; ?>
<?php /*                     Closes the step action column. */ ?>
                    </div>
<?php /*                 Closes the step row. */ ?>
                </div>
<?php /*                 Ends the loop over milestone steps. */ ?>
                <?php endforeach; ?>
<?php /*             Closes the stage body div. */ ?>
            </div>
<?php /*         Closes the stage block div. */ ?>
        </div>
<?php /*         Ends the loop over the stage blocks. */ ?>
        <?php endforeach; ?>
<?php /*     Closes the tracker card body. */ ?>
    </div>
<?php /* Closes the milestone tracker card. */ ?>
</div>

<?php /* Starts the JavaScript block for the tracker toggles. */ ?>
<script>
<?php /* Wraps the script in a function so its names stay private. */ ?>
(function () {
<?php /*     Note above: the click handler collapses or expands a stage block. */ ?>
    // Collapse / expand a stage block of the milestone tracker.
<?php /*     Finds every stage header button that can toggle a block. */ ?>
    document.querySelectorAll('[data-ms-toggle]').forEach(toggle => {
<?php /*         Adds a click listener to each toggle button. */ ?>
        toggle.addEventListener('click', () => {
<?php /*             Finds the stage block that contains the clicked button. */ ?>
            const block = toggle.closest('[data-ms-stage]');
<?php /*             Stops early if the block element was not found. */ ?>
            if (!block) return;
<?php /*             Toggles the is-open class and returns the new state. */ ?>
            const open = block.classList.toggle('is-open');
<?php /*             Updates aria-expanded so screen readers know the state. */ ?>
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
<?php /*         Closes the click listener function. */ ?>
        });
<?php /*     Closes the forEach over the toggle buttons. */ ?>
    });
<?php /* Closes and immediately runs the wrapper function. */ ?>
})();
<?php /* Closes the JavaScript block. */ ?>
</script>

<?php /* Starts the CSS block for the scheduled presentation cards. */ ?>
<style>
<?php /* CSS note header for the scheduled presentation cards. */ ?>
/* Scheduled Presentations: one block per stage, with the presentation team
<?php /*    CSS note continues: the team is shown under date, time, venue. */ ?>
   (panel, adviser, documenter) listed under the date, time and venue. */
<?php /* Grid that lays out one card per presentation stage. */ ?>
.sched-list { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(330px, 1fr)); }
<?php /* One presentation card, stacked vertically inside. */ ?>
.sched-item { display: flex; flex-direction: column; border: 1px solid var(--gray-200); border-radius: var(--radius-lg); background: var(--white); overflow: hidden; }
<?php /* Blue border for cards that already have a schedule. */ ?>
.sched-item.is-scheduled { border-color: rgba(37, 99, 235, 0.3); }
<?php /* Card header row with icon, title, and status badge. */ ?>
.sched-head { display: flex; align-items: center; gap: 0.65rem; padding: 0.85rem 1.1rem; background: var(--gray-50); border-bottom: 1px solid var(--gray-100); }
<?php /* Tinted header for presentations that are scheduled. */ ?>
.sched-item.is-scheduled .sched-head { background: rgba(37, 99, 235, 0.06); border-bottom-color: rgba(37, 99, 235, 0.15); }
<?php /* Square icon box on the left of the card header. */ ?>
.sched-icon { display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; flex-shrink: 0; border: 1px solid var(--gray-200); border-radius: var(--radius); background: var(--white); color: var(--gray-400); }
<?php /* Blue icon colour when the presentation is scheduled. */ ?>
.sched-item.is-scheduled .sched-icon { color: var(--info); border-color: rgba(37, 99, 235, 0.25); }
<?php /* Stage title text that fills the free space. */ ?>
.sched-title { flex: 1; font-size: 0.88rem; font-weight: 600; color: var(--adzu-navy); }
<?php /* Keeps the status badge at its natural width. */ ?>
.sched-status { flex-shrink: 0; }
<?php /* Card body holding date, venue, panel, adviser, documenter. */ ?>
.sched-body { display: flex; flex-direction: column; gap: 0.7rem; padding: 1rem 1.1rem; }
<?php /* Line showing the date and time with a clock icon. */ ?>
.sched-when { display: flex; align-items: center; gap: 0.4rem; margin: 0; font-size: 0.85rem; font-weight: 600; color: var(--gray-700); }
<?php /* Sets the colour of the clock icon. */ ?>
.sched-when i { color: var(--info); }
<?php /* Two-column row: small label left, value right. */ ?>
.sched-row { display: grid; grid-template-columns: 116px 1fr; gap: 0.55rem; align-items: start; font-size: 0.82rem; }
<?php /* Uppercase grey label for a row such as Venue. */ ?>
.sched-label { display: flex; align-items: center; gap: 0.35rem; padding-top: 2px; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.02em; text-transform: uppercase; color: var(--gray-500); }
<?php /* Fixes the width of icons inside the row labels. */ ?>
.sched-label i { width: 12px; text-align: center; color: var(--gray-400); }
<?php /* Value text shown next to a row label. */ ?>
.sched-value { color: var(--gray-700); }
<?php /* Flex container that wraps the panel member chips. */ ?>
.sched-chips { display: flex; flex-wrap: wrap; gap: 0.35rem; }
<?php /* Pill-shaped chip that shows one panel member. */ ?>
.sched-chip { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.18rem 0.6rem; border: 1px solid var(--gray-200); border-radius: 999px; background: var(--gray-50); font-size: 0.78rem; color: var(--gray-700); }
<?php /* Small grey user icon inside a chip. */ ?>
.sched-chip i { font-size: 0.7rem; color: var(--gray-400); }
<?php /* Small Chair tag shown next to a panel chair. */ ?>
.sched-chip-tag { padding: 0.05rem 0.4rem; border-radius: 999px; background: rgba(37, 99, 235, 0.12); color: var(--info); font-size: 0.62rem; font-weight: 600; letter-spacing: 0.03em; text-transform: uppercase; }
<?php /* Italic grey text used for not-yet-assigned values. */ ?>
.sched-none { color: var(--gray-400); font-style: italic; }
<?php /* Italic message shown when a stage has no schedule. */ ?>
.sched-empty { margin: 0; padding: 1.1rem; font-size: 0.8rem; font-style: italic; color: var(--gray-400); }
<?php /* On small screens, stacks the label above the value. */ ?>
@media (max-width: 600px) { .sched-row { grid-template-columns: 1fr; gap: 0.15rem; } }
<?php /* Closes the scheduled presentation CSS block. */ ?>
</style>

<?php /* Card that lists the scheduled presentations. */ ?>
<div class="card">
<?php /*     Header with a calendar icon and the card title. */ ?>
    <div class="card-header"><h3><i class="fas fa-calendar-alt"></i> Scheduled Presentations</h3></div>
<?php /*     Body of the scheduled presentations card. */ ?>
    <div class="card-body">
<?php /*         Grid wrapper holding one card per stage. */ ?>
        <div class="sched-list">
<?php /*             Loops over the stages to render a card; opens PHP. */ ?>
            <?php foreach ($stages as $idx => $stage):
                // Gets the saved progress for the stage, or an empty array.
                $p = $progress[$idx] ?? [];
                // True when a presentation date has been set.
                $hasSched = !empty($p['presentation']['date']);
                // Gets the saved panel, adviser, and documenter details.
                $detail = $presentationDetails[$stage['key']] ?? null;
                // List of panel members for the stage, if any.
                $panelMembers = $detail['panel'] ?? [];
                // Old free-text panel value stored on the progress row.
                $panelText = trim((string) ($p['presentation']['panel'] ?? ''));
                // Checks whether that old panel text is just TBD.
                if (strcasecmp($panelText, 'TBD') === 0) {
                    // Clears the text so TBD is not shown as a person's name.
                    $panelText = '';
                // Closes the TBD check.
                }
                // Adviser name for the stage, trimmed.
                $adviserName = trim((string) ($detail['adviser'] ?? ''));
                // Documenter name for the stage, trimmed.
                $documenterName = trim((string) ($detail['documentor'] ?? ''));
            // Closes the PHP block, inside the stage loop.
            ?>
<?php /*             One presentation card, marked scheduled when a date exists. */ ?>
            <article class="sched-item<?= $hasSched ? ' is-scheduled' : '' ?>">
<?php /*                 Header row of the presentation card. */ ?>
                <header class="sched-head">
<?php /*                     Calendar icon box in the card header. */ ?>
                    <span class="sched-icon"><i class="fas fa-calendar-day"></i></span>
<?php /*                     Shows the stage label as the card title. */ ?>
                    <span class="sched-title"><?= htmlspecialchars($stage['label']) ?></span>
<?php /*                     Only shows the status badge when a schedule exists. */ ?>
                    <?php if ($hasSched): ?>
<?php /*                     Shows the stage status badge with its colours. */ ?>
                    <span class="sched-status"><?= statusBadge($p['stageStatus'] ?? 'pending') ?></span>
<?php /*                     Closes the if for the status badge. */ ?>
                    <?php endif; ?>
<?php /*                 Closes the card header. */ ?>
                </header>
<?php /*                 Only shows the details when the presentation is scheduled. */ ?>
                <?php if ($hasSched): ?>
<?php /*                 Body of the scheduled presentation card. */ ?>
                <div class="sched-body">
<?php /*                     Line showing when the presentation happens. */ ?>
                    <p class="sched-when">
<?php /*                         Clock icon placed before the date. */ ?>
                        <i class="fas fa-clock"></i>
<?php /*                         Shows the date, then the time after a dot when it is set. */ ?>
                        <?= htmlspecialchars($p['presentation']['date']) ?><?php if (!empty($p['presentation']['time'])): ?> · <?= htmlspecialchars($p['presentation']['time']) ?><?php endif; ?>
<?php /*                     Closes the when paragraph. */ ?>
                    </p>
<?php /*                     Row for the presentation venue. */ ?>
                    <div class="sched-row">
<?php /*                         Venue label with a map-marker icon. */ ?>
                        <span class="sched-label"><i class="fas fa-map-marker-alt"></i> Venue</span>
<?php /*                         Value cell for the venue. */ ?>
                        <span class="sched-value">
<?php /*                             Checks whether a venue was entered. */ ?>
                            <?php if (!empty($p['presentation']['venue'])): ?>
<?php /*                             Shows the venue name. */ ?>
                            <?= htmlspecialchars($p['presentation']['venue']) ?>
<?php /*                             Otherwise shows a placeholder for the venue. */ ?>
                            <?php else: ?>
<?php /*                             Placeholder text To be announced. */ ?>
                            <span class="sched-none">To be announced</span>
<?php /*                             Closes the if/else for the venue. */ ?>
                            <?php endif; ?>
<?php /*                         Closes the venue value cell. */ ?>
                        </span>
<?php /*                     Closes the venue row. */ ?>
                    </div>
<?php /*                     Row for the panel members. */ ?>
                    <div class="sched-row">
<?php /*                         Panel Members label with a users icon. */ ?>
                        <span class="sched-label"><i class="fas fa-users"></i> Panel Members</span>
<?php /*                         Value cell for the panel members. */ ?>
                        <span class="sched-value">
<?php /*                             If real panel members exist, list them as chips. */ ?>
                            <?php if ($panelMembers): ?>
<?php /*                             Wrapper that lets the chips wrap onto several lines. */ ?>
                            <span class="sched-chips">
<?php /*                                 Loops over each panel member. */ ?>
                                <?php foreach ($panelMembers as $member): ?>
<?php /*                                 One chip for a panel member. */ ?>
                                <span class="sched-chip">
<?php /*                                     Shows the member's name with a user icon. */ ?>
                                    <i class="fas fa-user"></i> <?= htmlspecialchars($member['name']) ?>
<?php /*                                     Adds a Chair tag when the member is the panel chair. */ ?>
                                    <?php if (($member['role'] ?? 'member') === 'chair'): ?><span class="sched-chip-tag">Chair</span><?php endif; ?>
<?php /*                                 Closes the member chip. */ ?>
                                </span>
<?php /*                                 Ends the panel member loop. */ ?>
                                <?php endforeach; ?>
<?php /*                             Closes the chips wrapper. */ ?>
                            </span>
<?php /*                             Else if only the older panel text exists, show it in one chip. */ ?>
                            <?php elseif ($panelText !== ''): ?>
<?php /*                             Single chip built from the old free-text panel value. */ ?>
                            <span class="sched-chips"><span class="sched-chip"><i class="fas fa-user"></i> <?= htmlspecialchars($panelText) ?></span></span>
<?php /*                             Otherwise no panel has been assigned yet. */ ?>
                            <?php else: ?>
<?php /*                             Placeholder saying the panel is not assigned yet. */ ?>
                            <span class="sched-none">Not assigned yet</span>
<?php /*                             Closes the if/elseif/else for panel members. */ ?>
                            <?php endif; ?>
<?php /*                         Closes the panel value cell. */ ?>
                        </span>
<?php /*                     Closes the panel row. */ ?>
                    </div>
<?php /*                     Row for the adviser. */ ?>
                    <div class="sched-row">
<?php /*                         Adviser label with a user-tie icon. */ ?>
                        <span class="sched-label"><i class="fas fa-user-tie"></i> Adviser</span>
<?php /*                         Value cell for the adviser. */ ?>
                        <span class="sched-value">
<?php /*                             Checks whether an adviser name exists. */ ?>
                            <?php if ($adviserName !== ''): ?>
<?php /*                             Shows the adviser's name. */ ?>
                            <?= htmlspecialchars($adviserName) ?>
<?php /*                             Otherwise shows the not-assigned placeholder. */ ?>
                            <?php else: ?>
<?php /*                             Placeholder saying no adviser is assigned yet. */ ?>
                            <span class="sched-none">Not assigned yet</span>
<?php /*                             Closes the if/else for the adviser. */ ?>
                            <?php endif; ?>
<?php /*                         Closes the adviser value cell. */ ?>
                        </span>
<?php /*                     Closes the adviser row. */ ?>
                    </div>
<?php /*                     Row for the documenter. */ ?>
                    <div class="sched-row">
<?php /*                         Documenter label with a clipboard icon. */ ?>
                        <span class="sched-label"><i class="fas fa-clipboard"></i> Documenter</span>
<?php /*                         Value cell for the documenter. */ ?>
                        <span class="sched-value">
<?php /*                             Checks whether a documenter name exists. */ ?>
                            <?php if ($documenterName !== ''): ?>
<?php /*                             Shows the documenter's name. */ ?>
                            <?= htmlspecialchars($documenterName) ?>
<?php /*                             Otherwise shows the not-assigned placeholder. */ ?>
                            <?php else: ?>
<?php /*                             Placeholder saying no documenter is assigned yet. */ ?>
                            <span class="sched-none">Not assigned yet</span>
<?php /*                             Closes the if/else for the documenter. */ ?>
                            <?php endif; ?>
<?php /*                         Closes the documenter value cell. */ ?>
                        </span>
<?php /*                     Closes the documenter row. */ ?>
                    </div>
<?php /*                 Closes the body of the scheduled card. */ ?>
                </div>
<?php /*                 Else branch for stages that have no schedule yet. */ ?>
                <?php else: ?>
<?php /*                 Message saying this presentation is not yet scheduled. */ ?>
                <p class="sched-empty">Not yet scheduled</p>
<?php /*                 Closes the if/else for the schedule details. */ ?>
                <?php endif; ?>
<?php /*             Closes the presentation card article. */ ?>
            </article>
<?php /*             Ends the loop over the stages. */ ?>
            <?php endforeach; ?>
<?php /*         Closes the grid wrapper. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the scheduled presentations card. */ ?>
</div>

<?php /* Includes the shared footer that closes the page layout. */ ?>
<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>