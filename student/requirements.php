<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$mockStudent = currentStudentProfile($mockStudent);

$pageTitle   = 'Requirements & Process';
$role        = 'student';
$currentPage = 'requirements';
$userName    = $mockStudent['name'];
$track       = $mockStudent['track'];
$trackLabel  = getTrackLabel($track);
$workflow    = getWorkflow($track);
$stages      = $workflow['stages'];
$progress    = getStudentProgress($mockStudent['email'], $track);
$currentIndex = workflowCurrentIndex($progress, $stages);
// The stages the student is not allowed to work on yet.
$lockStates = stageLockStates($stages, $currentIndex);

// A student may only open the stage they are working on. A future stage is
// locked until the coordinator approves the progression.
$access = resolveStudentStageAccess($progress, $stages, $currentIndex, (string) ($_GET['stage'] ?? ''));
$openKey = $access['stageKey'];
$lockedNotice = $access['locked'] ? $access['reason'] : '';
if ($openKey === '') {
    $openKey = ($stages[$currentIndex]['key'] ?? '');
    $openKey = $openKey ?: ($stages[0]['key'] ?? 'proposal');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h2>Process Tracker</h2>
    <p><?= htmlspecialchars($trackLabel) ?> Track (<?= htmlspecialchars($mockStudent['program']) ?>) — step-by-step progress for each stage.</p>
</div>

<div class="card" style="margin-bottom:1rem;">
    <div class="card-body" style="padding-top:1.25rem;padding-bottom:1.25rem;">
        <div class="stage-stepper">
            <?php foreach ($stages as $idx => $stage):
                $p = $progress[$idx] ?? null;
                $st = $p['stageStatus'] ?? 'not_started';
                $isLocked = $lockStates[$stage['key']]['locked'] ?? false;
                $isSubmitted = $p && !in_array($st, ['not_started', 'pending', 'draft', ''], true);
                $isDone = in_array($st, ['completed', 'approved'], true) || $idx < $currentIndex;
                $isHighlighted = $isSubmitted || $isDone;
                $isActive = $stage['key'] === $openKey;
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
                <?php if ($isLocked): ?>
                <div class="stage-stepper-btn" style="cursor:not-allowed;" title="<?= htmlspecialchars($lockStates[$stage['key']]['reason'] ?? '') ?>">
                    <span class="stage-stepper-num"><i class="fas fa-lock" aria-hidden="true"></i></span>
                    <span class="stage-stepper-label"><?= htmlspecialchars($stage['shortLabel']) ?></span>
                    <span class="status-badge <?= $statusClass ?>" style="font-size:0.65rem;padding:2px 6px;"><?= htmlspecialchars($statusLabel) ?></span>
                </div>
                <?php else: ?>
                <a href="<?= url('student/requirements.php?stage=' . urlencode($stage['key'])) ?>" class="stage-stepper-btn">
                    <span class="stage-stepper-num <?= $isHighlighted ? 'highlighted done' : '' ?>">
                        <?= $idx + 1 ?>
                    </span>
                    <span class="stage-stepper-label <?= $isActive ? 'is-active' : ($isHighlighted ? 'is-done' : '') ?>"><?= htmlspecialchars($stage['shortLabel']) ?></span>
                    <span class="status-badge <?= $statusClass ?>" style="font-size:0.65rem;padding:2px 6px;"><?= htmlspecialchars($statusLabel) ?></span>
                </a>
                <?php endif; ?>
                <?php if ($idx < count($stages) - 1): ?>
                <div class="stage-stepper-line <?= $isHighlighted ? 'done' : '' ?>"></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if ($lockedNotice): ?>
<div class="alert alert-warning">
    <i class="fas fa-lock"></i>
    <div><?= htmlspecialchars($lockedNotice) ?> You are now viewing the stage you are currently working on.</div>
</div>
<?php endif; ?>

<?php foreach ($stages as $idx => $stage):
    if ($stage['key'] !== $openKey) {
        continue;
    }
    $p = $progress[$idx] ?? blankStageProgress($stage);
    $isDone = in_array($p['stageStatus'], ['completed', 'approved'], true);
    // Shared with the dashboard so both show the same milestones, worded for
    // the student's own track and stage.
    $steps = milestoneStepsForStage($p, $track, $stage['key']);
    $firstPending = milestoneProgress($steps)['firstPending'];
    // The document types the student may upload now. The same rule decides
    // the Document Type list of the Application page. A stage that is locked
    // has no open document type at all.
    $openTypes = ($lockStates[$stage['key']]['locked'] ?? false) ? [] : openDocumentTypes($steps);
?>
<div class="card">
    <div class="card-header" style="background:<?= $isDone ? 'rgba(22,163,74,0.06)' : 'rgba(6,2,151,0.04)' ?>;">
        <h3 style="display:flex;align-items:center;gap:0.6rem;">
            <span class="stage-stepper-num <?= $isDone ? 'highlighted done' : 'active' ?>" style="width:28px;height:28px;font-size:0.8rem;"><?= $idx + 1 ?></span>
            <?= htmlspecialchars($stage['label']) ?>
            <?= statusBadge($p['stageStatus']) ?>
        </h3>
    </div>
    <div class="card-body">
        <?php if (!empty($p['coordinatorComments'])): ?>
        <div class="alert alert-warning" style="margin-bottom:1.25rem;">
            <i class="fas fa-comment-dots"></i>
            <div><strong>Coordinator:</strong> <?= htmlspecialchars($p['coordinatorComments']) ?></div>
        </div>
        <?php endif; ?>

        <div class="substep-list">
            <?php
            // The next step of the student, in the order of their workflow.
            $nextStep = nextMilestoneStep($steps);
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
            <?php foreach ($steps as $si => $step):
                $st = $step['state']['status'] ?? 'pending';
                $isSubmitted = $st === 'submitted';
                $iconState = $st === 'flagged' ? 'flagged' : ($st === 'done' ? 'done' : ($isSubmitted ? 'submitted' : ($si === $firstPending ? 'active' : 'pending')));
            ?>
            <div class="substep-row">
                <div class="substep-icon-col">
                    <?= stepIconHtml($iconState) ?>
                    <?php if ($si < count($steps) - 1): ?><div class="substep-line <?= $st === 'done' ? 'done' : '' ?>"></div><?php endif; ?>
                </div>
                <div style="flex:1;">
                    <div style="display:flex;align-items:center;gap:0.4rem;margin-bottom:0.2rem;">
                        <span style="font-size:0.875rem;font-weight:600;color:<?= $st === 'pending' ? 'var(--gray-400)' : 'var(--gray-800)' ?>;"><?= htmlspecialchars($step['label']) ?></span>
                        <?php if ($step['coord']): ?><span class="coord-chip">Coordinator</span><?php endif; ?>
                        <?php if ($st === 'done'): ?><span class="ms-step-tag done">Verified</span><?php endif; ?>
                        <?php if ($isSubmitted): ?><span class="ms-step-tag submitted">Submitted</span><?php endif; ?>
                        <?php if ($st === 'flagged'): ?><span class="ms-step-tag flagged">Action needed</span><?php endif; ?>
                    </div>
                    <p style="font-size:0.78rem;color:<?= $st === 'flagged' ? '#b45309' : 'var(--gray-500)' ?>;margin:0;line-height:1.4;"><?= htmlspecialchars($step['detail']) ?></p>
                </div>
                <div>
                    <?php
                    // The Upload button follows the same rule as the Document
                    // Type list of the Application page: the document of the
                    // milestone the student is working on.
                    $stepDocType = (string) ($step['docType'] ?? '');
                    if ($stepDocType !== '' && in_array($stepDocType, $openTypes, true)):
                    ?>
                    <a class="btn btn-sm btn-primary" href="<?= url('student/application.php?stage=' . urlencode($stage['key']) . '&docType=' . urlencode($stepDocType)) ?>"><i class="fas fa-upload"></i> Upload</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="req-docs-box">
            <h4>Required Documents for this Stage</h4>
            <?php
            $docs = array_merge($stage['documents'], [$stage['adviserEndorsementForm'], $stage['gradSchoolEndorsementForm']]);
            foreach ($docs as $doc):
                if (($doc['type'] ?? '') === 'receipt') {
                    continue;
                }
            ?>
            <div class="req-doc-row">
                <i class="fas <?= ($doc['type'] ?? '') === 'form' ? 'fa-file-signature' : 'fa-file-word' ?>"></i>
                <span><?= htmlspecialchars($doc['label']) ?></span>
                <?php if (!empty($doc['file'])): ?>
                <a class="btn btn-sm btn-outline" href="<?= htmlspecialchars(papersUrl($doc['folder'], $doc['file'])) ?>" download><i class="fas fa-download"></i> Download</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php foreach ($stage['documents'] as $doc):
            if (($doc['type'] ?? '') !== 'receipt') {
                continue;
            }
        ?>
        <div class="receipt-box">
            <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.65rem;">
                <i class="fas fa-receipt" style="color:var(--adzu-gold);"></i>
                <span style="font-weight:700;font-size:0.82rem;text-transform:uppercase;letter-spacing:0.05em;color:var(--gray-600);">Payment Proof Required</span>
            </div>
            <p style="font-size:0.83rem;color:var(--gray-700);margin-bottom:0.75rem;"><?= htmlspecialchars($doc['label']) ?></p>
            <?php
            // The button follows the same rule as the Document Type list of
            // the Application page: the receipt is only open when the
            // workflow of the track asks for it.
            $receiptOpen = in_array('Official Receipt / Payment Proof', $openTypes, true);
            ?>
            <?php if ($receiptOpen): ?>
            <a class="btn btn-primary btn-sm" href="<?= url('student/application.php?stage=' . urlencode($stage['key']) . '&docType=' . urlencode('Official Receipt / Payment Proof')) ?>"><i class="fas fa-upload"></i> Upload Receipt</a>
            <?php else: ?>
            <p class="field-hint" style="margin:0;">
                <i class="fas fa-lock" aria-hidden="true"></i>
                The receipt opens when the milestones before it are done.
            </p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<div class="card">
    <div class="card-body">
        <p style="font-size:0.875rem;color:var(--gray-500);margin:0;">
            <i class="fas fa-info-circle" style="margin-right:6px;color:var(--adzu-navy);"></i>
            Download templates and forms from the <a href="<?= url('student/templates.php') ?>"><strong>Templates &amp; Forms</strong></a> page.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
