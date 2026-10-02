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

$activeStage = $progress[$currentIdx] ?? ($progress[0] ?? null);
$activeLabel = $stages[$currentIdx]['shortLabel'] ?? '—';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h2>Welcome, <?= htmlspecialchars($student['first_name'] ?? explode(' ', $userName)[0]) ?>!</h2>
    <p><?= htmlspecialchars($student['program']) ?> — <?= htmlspecialchars($trackLabel) ?> Track</p>
</div>

<div class="dashstat-grid cols-4 theme-ateneo">
    <a href="<?= url('student/profile.php') ?>" class="dashstat dashstat-blue">
        <div class="dashstat-text">
            <div class="dashstat-textvalue"><?= htmlspecialchars($student['program']) ?></div>
            <div class="dashstat-label">Program</div>
        </div>
        <div class="dashstat-icon"><i class="fas fa-book"></i></div>
    </a>
    <a href="<?= url('student/requirements.php') ?>" class="dashstat dashstat-amber">
        <div class="dashstat-text">
            <div class="dashstat-textvalue"><?= htmlspecialchars($activeLabel) ?></div>
            <div class="dashstat-label">Current Stage</div>
        </div>
        <div class="dashstat-icon"><i class="fas fa-flag"></i></div>
    </a>
    <a href="<?= url('student/status.php') ?>" class="dashstat dashstat-purple">
        <div class="dashstat-text">
            <div class="dashstat-textvalue"><?= statusBadge($activeStage['stageStatus'] ?? 'pending') ?></div>
            <div class="dashstat-label">Stage Status</div>
        </div>
        <div class="dashstat-icon"><i class="fas fa-clock"></i></div>
    </a>
    <a href="<?= url('student/requirements.php') ?>" class="dashstat dashstat-emerald">
        <div class="dashstat-text">
            <div class="dashstat-value"><?= count($stages) ?></div>
            <div class="dashstat-label">Total Stages</div>
        </div>
        <div class="dashstat-icon"><i class="fas fa-layer-group"></i></div>
    </a>
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
                $isSubmitted = $p && !in_array($st, ['not_started', 'pending', 'draft', ''], true);
                $isDone = in_array($st, ['completed', 'approved'], true) || $idx < $currentIdx;
                $isHighlighted = $isSubmitted || $isDone;
                $isActive = $idx === $currentIdx;
                $statusLabel = $isSubmitted ? (STATUSES[$st]['label'] ?? 'Submitted') : 'Not Submitted';
                $statusClass = $isSubmitted ? (STATUSES[$st]['class'] ?? 'status-submitted') : 'status-pending';
            ?>
            <div class="stage-stepper-item">
                <div class="stage-stepper-btn" style="cursor:default;">
                    <span class="stage-stepper-num <?= $isHighlighted ? 'highlighted done' : '' ?>">
                        <?= $idx + 1 ?>
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
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:0.5rem;">
                <?php
                $checks = [
                    ['Paper Prepared & Submitted', $activeStage['paper']['status'] ?? 'pending', $activeStage['paper']['submitted'] ?? ''],
                    ['Adviser Endorsement Submitted', $activeStage['adviserEndorsement']['status'] ?? 'pending', $activeStage['adviserEndorsement']['submitted'] ?? ''],
                    ['Coordinator Document Review', $activeStage['coordReview']['status'] ?? 'pending', ''],
                    ['Graduate School Endorsement', $activeStage['gradSchoolEndorsement']['status'] ?? 'pending', ''],
                    ['Official Receipt / Payment Uploaded', $activeStage['payment']['status'] ?? 'pending', $activeStage['payment']['submitted'] ?? ''],
                    ['Status Set to Ready for Presentation', $activeStage['readyForPresentation']['status'] ?? 'pending', ''],
                    ['Presentation Scheduled', $activeStage['presentation']['status'] ?? 'pending', $activeStage['presentation']['date'] ?? ''],
                    ['Presentation Conducted & Result Recorded', $activeStage['result']['status'] ?? 'pending', $activeStage['result']['value'] ?? ''],
                ];
                foreach ($checks as [$label, $state, $extra]):
                ?>
                <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.8rem;">
                    <?= stepIconHtml($state === 'flagged' ? 'flagged' : ($state === 'done' ? 'done' : 'pending')) ?>
                    <span style="color:<?= $state === 'done' ? 'var(--gray-700)' : 'var(--gray-400)' ?>;">
                        <?= htmlspecialchars($label) ?><?= $extra ? ' (' . htmlspecialchars($extra) . ')' : '' ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($activeStage['coordinatorComments'])): ?>
            <div class="alert alert-warning" style="margin-top:1rem;margin-bottom:0;">
                <i class="fas fa-comment-alt"></i>
                <div><strong>Coordinator note:</strong> <?= htmlspecialchars($activeStage['coordinatorComments']) ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

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

<div class="dashstat-grid cols-3 theme-ateneo">
    <a href="<?= url('student/application.php') ?>" class="dashstat dashstat-blue">
        <div class="dashstat-text">
            <div class="dashstat-textvalue">Application &amp; Upload</div>
            <div class="dashstat-desc">Submit your <?= htmlspecialchars(strtolower($trackLabel)) ?> application and upload required documents.</div>
            <div class="dashstat-link">Open <i class="fas fa-arrow-right"></i></div>
        </div>
        <div class="dashstat-icon"><i class="fas fa-file-alt"></i></div>
    </a>
    <a href="<?= url('student/requirements.php') ?>" class="dashstat dashstat-amber">
        <div class="dashstat-text">
            <div class="dashstat-textvalue">Process Tracker</div>
            <div class="dashstat-desc">View the complete step-by-step progress for each <?= htmlspecialchars(strtolower($trackLabel)) ?> stage.</div>
            <div class="dashstat-link">Open <i class="fas fa-arrow-right"></i></div>
        </div>
        <div class="dashstat-icon"><i class="fas fa-tasks"></i></div>
    </a>
    <a href="<?= url('student/templates.php') ?>" class="dashstat dashstat-emerald">
        <div class="dashstat-text">
            <div class="dashstat-textvalue">Templates &amp; Forms</div>
            <div class="dashstat-desc">Download forms and templates for your program.</div>
            <div class="dashstat-link">Open <i class="fas fa-arrow-right"></i></div>
        </div>
        <div class="dashstat-icon"><i class="fas fa-download"></i></div>
    </a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>