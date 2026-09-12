<?php
require_once __DIR__ . '/../includes/config.php';

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
$openKey     = $_GET['stage'] ?? '';
if ($openKey === '') {
    foreach ($progress as $p) {
        if (!in_array($p['stageStatus'], ['completed', 'approved'], true)) {
            $openKey = $p['stageKey'];
            break;
        }
    }
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
                $isDone = $p && in_array($p['stageStatus'], ['completed', 'approved'], true);
                $isActive = $stage['key'] === $openKey;
            ?>
            <div class="stage-stepper-item">
                <a href="<?= url('student/requirements.php?stage=' . urlencode($stage['key'])) ?>" class="stage-stepper-btn">
                    <span class="stage-stepper-num <?= $isDone ? 'done' : ($isActive ? 'active' : '') ?>">
                        <?= $isDone ? '<i class="fas fa-check"></i>' : ($idx + 1) ?>
                    </span>
                    <span class="stage-stepper-label <?= $isActive ? 'is-active' : ($isDone ? 'is-done' : '') ?>"><?= htmlspecialchars($stage['shortLabel']) ?></span>
                </a>
                <?php if ($idx < count($stages) - 1): ?>
                <div class="stage-stepper-line <?= $isDone ? 'done' : '' ?>"></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php foreach ($stages as $idx => $stage):
    if ($stage['key'] !== $openKey) {
        continue;
    }
    $p = $progress[$idx] ?? blankStageProgress($stage);
    $isDone = in_array($p['stageStatus'], ['completed', 'approved'], true);
    $steps = [
        ['label' => 'Paper Prepared & Submitted', 'state' => $p['paper'], 'coord' => false, 'detail' => !empty($p['paper']['submitted']) ? 'Uploaded: ' . $p['paper']['submitted'] : 'Upload required'],
        ['label' => 'Adviser Endorsement Submitted', 'state' => $p['adviserEndorsement'], 'coord' => false, 'detail' => !empty($p['adviserEndorsement']['submitted']) ? 'Uploaded: ' . $p['adviserEndorsement']['submitted'] : 'Obtain signature and upload scanned form'],
        ['label' => 'Coordinator Document Review', 'state' => $p['coordReview'], 'coord' => true, 'detail' => ($p['coordReview']['comment'] ?? '') ?: ($p['coordReview']['status'] === 'done' ? 'Documents verified' : 'Waiting for coordinator')],
        ['label' => 'Graduate School Endorsement', 'state' => $p['gradSchoolEndorsement'], 'coord' => true, 'detail' => $p['gradSchoolEndorsement']['status'] === 'done' ? 'Endorsement issued' : 'Coordinator endorses student to Graduate School'],
        ['label' => 'Official Receipt / Payment Uploaded', 'state' => $p['payment'], 'coord' => false, 'detail' => !empty($p['payment']['submitted']) ? 'Uploaded: ' . $p['payment']['submitted'] : 'Submit official receipt after payment at the Graduate School'],
        ['label' => 'Status Set to Ready for Presentation', 'state' => $p['readyForPresentation'], 'coord' => true, 'detail' => $p['readyForPresentation']['status'] === 'done' ? 'Coordinator confirmed readiness' : 'Coordinator marks ready after receipt is verified'],
        ['label' => 'Presentation Scheduled', 'state' => $p['presentation'], 'coord' => true, 'detail' => !empty($p['presentation']['date']) ? trim($p['presentation']['date'] . ' – ' . ($p['presentation']['time'] ?? '') . (!empty($p['presentation']['venue']) ? ', ' . $p['presentation']['venue'] : '') . (!empty($p['presentation']['panel']) ? ' | Panel: ' . $p['presentation']['panel'] : '')) : 'Coordinator schedules date, time, venue, and panel'],
        ['label' => 'Presentation Conducted & Result Recorded', 'state' => $p['result'], 'coord' => true, 'detail' => (($p['result']['value'] ?? '') === 'approved' ? 'Approved — proceed to next stage' : (($p['result']['value'] ?? '') === 'revision' ? 'Revision required — resubmit updated paper' : 'Awaiting presentation'))],
    ];
    $firstPending = 0;
    foreach ($steps as $si => $st) {
        if (($st['state']['status'] ?? 'pending') !== 'done') {
            $firstPending = $si;
            break;
        }
        $firstPending = $si + 1;
    }
?>
<div class="card">
    <div class="card-header" style="background:<?= $isDone ? 'rgba(22,163,74,0.06)' : 'rgba(6,2,151,0.04)' ?>;">
        <h3 style="display:flex;align-items:center;gap:0.6rem;">
            <span class="stage-stepper-num <?= $isDone ? 'done' : 'active' ?>" style="width:28px;height:28px;font-size:0.8rem;"><?= $isDone ? '<i class="fas fa-check"></i>' : ($idx + 1) ?></span>
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
            <?php foreach ($steps as $si => $step):
                $st = $step['state']['status'] ?? 'pending';
                $iconState = $st === 'flagged' ? 'flagged' : ($st === 'done' ? 'done' : ($si === $firstPending ? 'active' : 'pending'));
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
                    </div>
                    <p style="font-size:0.78rem;color:<?= $st === 'flagged' ? '#b45309' : 'var(--gray-500)' ?>;margin:0;line-height:1.4;"><?= htmlspecialchars($step['detail']) ?></p>
                </div>
                <div>
                    <?php if ($st === 'pending' && !$step['coord'] && $si <= $firstPending): ?>
                    <a class="btn btn-sm btn-primary" href="<?= url('student/upload.php') ?>"><i class="fas fa-upload"></i> Upload</a>
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
            <a class="btn btn-primary btn-sm" href="<?= url('student/upload.php') ?>"><i class="fas fa-upload"></i> Upload Receipt</a>
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
