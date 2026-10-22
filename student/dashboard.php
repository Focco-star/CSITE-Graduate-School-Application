<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'student') {
    redirectTo('student/login.php');
}

$user = $_SESSION['user'];

$student = DB::find('students', ['user_id' => $user['user_id']]);

if (!$student) {
    die("Student profile not found.");
}

$pageTitle   = 'Dashboard';
$role        = 'student';
$currentPage = 'dashboard';
$userName    = $user['full_name'];
$track       = $student['track'] ?? 'thesis';
$trackLabel  = getTrackLabel($track);
$workflow    = getWorkflow($track);
$stages      = $workflow['stages'] ?? [];

$progress   = getStudentProgress($user['email'], $track);
$currentIdx = workflowCurrentIndex($progress, $stages);
// The stages the student is not allowed to work on yet.
$lockStates = stageLockStates($stages, $currentIdx);

$activeStage = $progress[$currentIdx] ?? ($progress[0] ?? null);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h2>Welcome, <?= htmlspecialchars($student['first_name'] ?? explode(' ', $userName)[0]) ?>!</h2>
    <p><?= htmlspecialchars($student['program']) ?> — <?= htmlspecialchars($trackLabel) ?> Track</p>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-route"></i> <?= htmlspecialchars($trackLabel) ?> Progress</h3>
        <a href="<?= url('student/status.php') ?>" class="btn btn-sm btn-outline">View Full Status</a>
    </div>
    <div class="card-body">
        <div class="stage-stepper" style="margin-bottom:1.5rem;padding-bottom:0.5rem;">
            <?php foreach ($stages as $idx => $stage):
                $p = $progress[$idx] ?? null;
                $st = $p['stageStatus'] ?? 'not_started';
                $isLocked = $lockStates[$stage['key']]['locked'] ?? false;
                $isSubmitted = $p && !in_array($st, ['not_started', 'pending', 'draft', ''], true);
                $isDone = in_array($st, ['completed', 'approved'], true) || $idx < $currentIdx;
                $isHighlighted = $isSubmitted || $isDone;
                $isActive = $idx === $currentIdx;
                if ($isLocked) {
                    $statusLabel = 'Locked';
                    $statusClass = 'status-locked';
                } elseif ($isSubmitted) {
                    $statusLabel = STATUSES[$st]['label'] ?? 'Submitted';
                    $statusClass = STATUSES[$st]['class'] ?? 'status-submitted';
                } else {
                    $statusLabel = 'Not Submitted';
                    $statusClass = 'status-pending';
                }
            ?>
            <div class="stage-stepper-item<?= $isLocked ? ' is-locked' : '' ?>">
                <div class="stage-stepper-btn" style="cursor:default;" title="<?= htmlspecialchars($lockStates[$stage['key']]['reason'] ?? '') ?>">
                    <span class="stage-stepper-num <?= $isHighlighted ? 'highlighted done' : ($isActive ? 'active' : '') ?>">
                        <?php if ($isLocked): ?><i class="fas fa-lock" aria-hidden="true"></i><?php else: ?><?= $idx + 1 ?><?php endif; ?>
                    </span>
                    <span class="stage-stepper-label <?= $isActive ? 'is-active' : ($isHighlighted ? 'is-done' : '') ?>"><?= htmlspecialchars($stage['shortLabel']) ?></span>
                    <span class="status-badge <?= $statusClass ?>" style="font-size:0.65rem;padding:2px 6px;"><?= htmlspecialchars($statusLabel) ?></span>
                </div>
                <?php if ($idx < count($stages) - 1): ?>
                <div class="stage-stepper-line <?= $isHighlighted ? 'done' : '' ?>"></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($activeStage): ?>
        <div style="background:var(--gray-50);border-radius:var(--radius-lg);padding:1.25rem;border:1px solid var(--gray-200);">
            <h4 style="color:var(--adzu-navy);margin-bottom:0.75rem;">
                <i class="fas fa-map-marker-alt" style="margin-right:6px;"></i>
                Current Stage: <?= htmlspecialchars($stages[$currentIdx]['label'] ?? '') ?>
            </h4>
            <?php if (!empty($activeStage['coordinatorComments'])): ?>
            <div class="alert alert-warning" style="margin-top:0;margin-bottom:0.75rem;">
                <i class="fas fa-comment-alt"></i>
                <div><strong>Coordinator note:</strong> <?= htmlspecialchars($activeStage['coordinatorComments']) ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
/*
 * Milestone Tracker — the same milestones the student sees on the Process
 * Tracker (Requirements) page, shown on the dashboard for every stage of the
 * student's own track. The wording of each milestone follows the track and the
 * stage, so thesis, capstone and seminar-paper students each see the labels
 * that apply to their own paper.
 */
$milestoneTotal = 0;
$milestoneDone = 0;
$milestoneBlocks = [];
foreach ($stages as $idx => $stage) {
    $row = $progress[$idx] ?? blankStageProgress($stage);
    $steps = milestoneStepsForStage($row, $track, $stage['key']);
    $stats = milestoneProgress($steps);
    $milestoneTotal += $stats['total'];
    $milestoneDone += $stats['done'];
    $milestoneBlocks[] = [
        'stage'     => $stage,
        'index'     => $idx,
        'steps'     => $steps,
        'stats'     => $stats,
        'isCurrent' => $idx === $currentIdx,
        'isDone'    => in_array($row['stageStatus'] ?? 'not_started', ['completed', 'approved'], true) || $idx < $currentIdx,
        'isLocked'  => $lockStates[$stage['key']]['locked'] ?? false,
    ];
}
$milestonePct = $milestoneTotal > 0 ? (int) round($milestoneDone * 100 / $milestoneTotal) : 0;
?>
<div class="card milestone-card" id="milestone-tracker">
    <div class="card-header">
        <h3><i class="fas fa-tasks"></i> Milestone Tracker</h3>
        <div class="ms-overview">
            <span class="ms-overview-text"><?= (int) $milestoneDone ?> of <?= (int) $milestoneTotal ?> milestones complete</span>
            <span class="ms-overview-bar"><span class="ms-overview-fill" style="width:<?= (int) $milestonePct ?>%;"></span></span>
        </div>
    </div>
    <div class="card-body">
        <?php
        // The next step of the student, in the order of their workflow. It
        // changes as soon as the coordinator verifies a document.
        $nextStep = nextMilestoneStep($milestoneBlocks[0]['steps'] ?? []);
        ?>
        <?php if ($nextStep): ?>
        <div class="tpl-next-step<?= $nextStep['coord'] ? ' is-coord' : '' ?>">
            <i class="fas <?= $nextStep['coord'] ? 'fa-user-tie' : 'fa-arrow-right' ?>" aria-hidden="true"></i>
            <div>
                <strong>Next step: <?= htmlspecialchars($nextStep['label']) ?></strong>
                <span><?= htmlspecialchars($nextStep['detail']) ?></span>
            </div>
        </div>
        <?php endif; ?>
        <?php foreach ($milestoneBlocks as $block):
            $stage = $block['stage'];
            $steps = $block['steps'];
            $stats = $block['stats'];
            $stageStatus = $progress[$block['index']]['stageStatus'] ?? 'not_started';
            $stageIsSubmitted = !in_array($stageStatus, ['not_started', 'pending', 'draft', ''], true);
            $stageStatusLabel = $block['isLocked'] ? 'Locked' : ($stageIsSubmitted ? (STATUSES[$stageStatus]['label'] ?? 'Submitted') : 'Not Started');
            $stageStatusClass = $block['isLocked'] ? 'status-locked' : ($stageIsSubmitted ? (STATUSES[$stageStatus]['class'] ?? 'status-submitted') : 'status-pending');
            $openByDefault = $block['isCurrent'] || $block['isDone'];
            // The document types the student may upload now. The same rule
            // decides the Document Type list of the Application page. A stage
            // that is locked has no open document type at all.
            $openTypes = $block['isLocked'] ? [] : openDocumentTypes($steps);
        ?>
        <div class="ms-stage<?= $block['isCurrent'] ? ' is-current' : '' ?><?= $block['isDone'] ? ' is-done' : '' ?><?= $block['isLocked'] ? ' is-locked' : '' ?><?= $openByDefault ? ' is-open' : '' ?>" data-ms-stage>
            <button type="button" class="ms-stage-head" data-ms-toggle aria-expanded="<?= $openByDefault ? 'true' : 'false' ?>">
                <span class="ms-stage-num"><?= $block['isLocked'] ? '<i class="fas fa-lock" aria-hidden="true"></i>' : (int) $block['index'] + 1 ?></span>
                <span class="ms-stage-title"><?= htmlspecialchars($stage['label']) ?></span>
                <span class="ms-stage-count"><?= (int) $stats['done'] ?> / <?= (int) $stats['total'] ?> milestones</span>
                <span class="status-badge <?= $stageStatusClass ?>" style="font-size:0.65rem;padding:2px 6px;"><?= htmlspecialchars($stageStatusLabel) ?></span>
                <i class="fas fa-chevron-down ms-stage-caret" aria-hidden="true"></i>
            </button>
            <div class="ms-stage-body">
                <?php if ($block['isLocked']): ?>
                <p class="ms-locked-note"><i class="fas fa-lock" aria-hidden="true"></i> <?= htmlspecialchars($lockStates[$stage['key']]['reason'] ?? 'This stage is locked.') ?></p>
                <?php endif; ?>
                <?php foreach ($steps as $si => $step):
                    $state = $step['state']['status'] ?? 'pending';
                    $isDone = $state === 'done';
                    $isFlagged = $state === 'flagged';
                    $isSubmitted = $state === 'submitted';
                    $isActive = !$block['isLocked'] && !$isDone && !$isFlagged && !$isSubmitted && $si === $stats['firstPending'];
                    $iconState = $isFlagged ? 'flagged' : ($isDone ? 'done' : ($isSubmitted ? 'submitted' : ($isActive ? 'active' : 'pending')));
                    $rowClass = $isFlagged ? 'is-flagged' : ($isDone ? 'is-done' : ($isSubmitted ? 'is-submitted' : ($isActive ? 'is-active' : 'is-pending')));
                ?>
                <div class="ms-step <?= $rowClass ?>">
                    <div class="ms-step-col">
                        <?= stepIconHtml($iconState) ?>
                        <?php if ($si < count($steps) - 1): ?>
                        <span class="ms-step-line<?= $isDone ? ' done' : '' ?>"></span>
                        <?php endif; ?>
                    </div>
                    <div class="ms-step-body">
                        <div class="ms-step-label">
                            <span><?= htmlspecialchars($step['label']) ?></span>
                            <?php if ($step['coord']): ?><span class="coord-chip">Coordinator</span><?php endif; ?>
                            <?php if ($isActive): ?><span class="ms-step-tag active">Next</span><?php endif; ?>
                            <?php if ($isDone): ?><span class="ms-step-tag done">Verified</span><?php endif; ?>
                            <?php if ($isSubmitted): ?><span class="ms-step-tag submitted">Submitted</span><?php endif; ?>
                            <?php if ($isFlagged): ?><span class="ms-step-tag flagged">Action needed</span><?php endif; ?>
                        </div>
                        <p class="ms-step-detail"><?= htmlspecialchars($step['detail']) ?></p>
                    </div>
                    <div class="ms-step-action">
                        <?php
                        // The Upload button follows the same rule as the Document
                        // Type list of the Application page: the document of
                        // the milestone the student is working on.
                        $stepDocType = (string) ($step['docType'] ?? '');
                        if ($stepDocType !== '' && in_array($stepDocType, $openTypes, true)):
                        ?>
                        <a class="btn btn-sm btn-primary" href="<?= url('student/application.php?stage=' . urlencode($stage['key']) . '&docType=' . urlencode($stepDocType)) ?>"><i class="fas fa-upload"></i> Upload</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
(function () {
    // Collapse / expand a stage block of the milestone tracker.
    document.querySelectorAll('[data-ms-toggle]').forEach(toggle => {
        toggle.addEventListener('click', () => {
            const block = toggle.closest('[data-ms-stage]');
            if (!block) return;
            const open = block.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });
})();
</script>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-calendar-alt"></i> Scheduled Presentations</h3></div>
    <div class="card-body">
        <div class="info-grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
            <?php foreach ($stages as $idx => $stage):
                $p = $progress[$idx] ?? [];
                $hasSched = !empty($p['presentation']['date']);
            ?>
            <div style="border:1px solid var(--gray-200);border-radius:var(--radius-lg);padding:1.1rem;">
                <div style="display:flex;align-items:center;gap:0.6rem;margin-bottom:0.65rem;">
                    <div style="width:34px;height:34px;background:<?= $hasSched ? 'rgba(37,99,235,0.1)' : 'var(--gray-50)' ?>;border-radius:var(--radius);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-calendar-day" style="color:<?= $hasSched ? 'var(--info)' : 'var(--gray-300)' ?>;"></i>
                    </div>
                    <strong style="font-size:0.88rem;color:var(--adzu-navy);"><?= htmlspecialchars($stage['label']) ?></strong>
                </div>
                <?php if ($hasSched): ?>
                    <p style="font-size:0.82rem;color:var(--gray-600);margin:0 0 0.25rem;"><i class="fas fa-clock" style="margin-right:5px;"></i><?= htmlspecialchars($p['presentation']['date']) ?> – <?= htmlspecialchars($p['presentation']['time'] ?? '') ?></p>
                    <?php if (!empty($p['presentation']['venue'])): ?><p style="font-size:0.82rem;color:var(--gray-600);margin:0 0 0.25rem;"><i class="fas fa-map-marker-alt" style="margin-right:5px;"></i><?= htmlspecialchars($p['presentation']['venue']) ?></p><?php endif; ?>
                    <?php if (!empty($p['presentation']['panel'])): ?><p style="font-size:0.82rem;color:var(--gray-500);margin:0;"><i class="fas fa-users" style="margin-right:5px;"></i><?= htmlspecialchars($p['presentation']['panel']) ?></p><?php endif; ?>
                    <div style="margin-top:0.5rem;"><?= statusBadge($p['stageStatus'] ?? 'pending') ?></div>
                <?php else: ?>
                    <p style="font-size:0.8rem;color:var(--gray-400);margin:0;font-style:italic;">Not yet scheduled</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>