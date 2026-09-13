<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Ensure user is authenticated as a student
if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'student') {
    redirectTo('student/login.php');
}

$user = $_SESSION['user'];

// Fetch the student's complete profile record from MySQL
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

// Fetch stage progress dynamically using student's email
$progress   = getStudentProgress($user['email'], $track);
$currentIdx = 0;

foreach ($progress as $i => $p) {
    if (!in_array($p['stageStatus'] ?? '', ['completed', 'approved'], true)) {
        $currentIdx = $i;
        break;
    }
    $currentIdx = $i;
}

$activeStage = $progress[$currentIdx] ?? ($progress[0] ?? null);
$activeLabel = $stages[$currentIdx]['shortLabel'] ?? '—';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h2>Welcome, <?= htmlspecialchars($student['first_name'] ?? explode(' ', $userName)[0]) ?>!</h2>
    <p><?= htmlspecialchars($student['program']) ?> — <?= htmlspecialchars($trackLabel) ?> Track</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-book"></i></div>
        <div class="stat-value" style="font-size:1rem;"><?= htmlspecialchars($student['program']) ?></div>
        <div class="stat-label">Program</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon gold"><i class="fas fa-flag"></i></div>
        <div class="stat-value" style="font-size:1rem;"><?= htmlspecialchars($activeLabel) ?></div>
        <div class="stat-label">Current Stage</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-clock"></i></div>
        <div class="stat-value"><?= statusBadge($activeStage['stageStatus'] ?? 'pending') ?></div>
        <div class="stat-label">Stage Status</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-layer-group"></i></div>
        <div class="stat-value"><?= count($stages) ?></div>
        <div class="stat-label">Total Stages</div>
    </div>
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
                $isDone = $p && in_array($p['stageStatus'] ?? '', ['completed', 'approved'], true);
                $isActive = $idx === $currentIdx;
            ?>
            <div class="stage-stepper-item">
                <div class="stage-stepper-btn" style="cursor:default;">
                    <span class="stage-stepper-num <?= $isDone ? 'done' : ($isActive ? 'active' : '') ?>">
                        <?= $isDone ? '<i class="fas fa-check"></i>' : ($idx + 1) ?>
                    </span>
                    <span class="stage-stepper-label <?= $isActive ? 'is-active' : ($isDone ? 'is-done' : '') ?>"><?= htmlspecialchars($stage['shortLabel']) ?></span>
                    <?php if ($p): ?>
                    <span class="status-badge <?= (STATUSES[$p['stageStatus']]['class'] ?? 'status-pending') ?>" style="font-size:0.65rem;padding:2px 6px;"><?= htmlspecialchars(str_replace('_', ' ', $p['stageStatus'])) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($idx < count($stages) - 1): ?>
                <div class="stage-stepper-line <?= $isDone ? 'done' : '' ?>"></div>
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
                    ['Paper Submitted', $activeStage['paper']['status'] ?? 'pending', $activeStage['paper']['submitted'] ?? ''],
                    ['Adviser Endorsement', $activeStage['adviserEndorsement']['status'] ?? 'pending', $activeStage['adviserEndorsement']['submitted'] ?? ''],
                    ['Coordinator Review', $activeStage['coordReview']['status'] ?? 'pending', ''],
                    ['Grad School Endorsement', $activeStage['gradSchoolEndorsement']['status'] ?? 'pending', ''],
                    ['Payment Recorded', $activeStage['payment']['status'] ?? 'pending', $activeStage['payment']['submitted'] ?? ''],
                    ['Presentation', $activeStage['presentation']['status'] ?? 'pending', $activeStage['presentation']['date'] ?? ''],
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

<div class="card">
    <div class="card-header"><h3><i class="fas fa-bolt"></i> Quick Links</h3></div>
    <div class="card-body">
        <div class="info-grid">
            <a href="<?= url('student/application.php') ?>" class="info-card" style="text-decoration:none;">
                <div class="icon"><i class="fas fa-file-alt"></i></div>
                <h3>Application &amp; Upload</h3>
                <p>Submit your <?= htmlspecialchars(strtolower($trackLabel)) ?> application and upload required documents.</p>
            </a>
            <a href="<?= url('student/requirements.php') ?>" class="info-card" style="text-decoration:none;">
                <div class="icon"><i class="fas fa-tasks"></i></div>
                <h3>Process Tracker</h3>
                <p>View the complete step-by-step progress for each <?= htmlspecialchars(strtolower($trackLabel)) ?> stage.</p>
            </a>
            <a href="<?= url('student/templates.php') ?>" class="info-card" style="text-decoration:none;">
                <div class="icon"><i class="fas fa-download"></i></div>
                <h3>Templates &amp; Forms</h3>
                <p>Download forms and templates for your program.</p>
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>