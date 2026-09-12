<?php
require_once __DIR__ . '/../includes/config.php';

$mockStudent = currentStudentProfile($mockStudent);

$pageTitle   = 'Application Status';
$role        = 'student';
$currentPage = 'dashboard';
$userName    = $mockStudent['name'];
$track       = $mockStudent['track'];
$workflow    = getWorkflow($track);
$stages      = $workflow['stages'];
$progress    = getStudentProgress($mockStudent['email'], $track);
$uploads     = uploadsForEmail($mockStudent['email']);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h2>Application Status</h2>
    <p>Track your <?= htmlspecialchars(getTrackLabel($track)) ?> presentation progress and document submissions.</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-flag"></i></div>
        <div class="stat-value" style="font-size:1rem;"><?= htmlspecialchars(displayStageLabel(['track' => $track, 'program' => $mockStudent['program'], 'currentStage' => $mockStudent['current_stage']])) ?></div>
        <div class="stat-label">Current Stage</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-tasks"></i></div>
        <div class="stat-value"><?= statusBadge($mockStudent['status']) ?></div>
        <div class="stat-label">Overall Status</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon gold"><i class="fas fa-user-tie"></i></div>
        <div class="stat-value" style="font-size:1rem;"><?= htmlspecialchars($mockStudent['adviser']) ?></div>
        <div class="stat-label">Adviser</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-value" style="font-size:1rem;"><?= date('M d, Y', strtotime($mockStudent['completion_deadline'])) ?></div>
        <div class="stat-label">3-Year Deadline</div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3><?= htmlspecialchars(getTrackLabel($track)) ?> Stage Progress</h3></div>
    <div class="card-body">
        <div class="progress-timeline">
            <?php foreach ($stages as $idx => $stage):
                $p = $progress[$idx] ?? ['stageStatus' => 'pending'];
                $st = $p['stageStatus'];
                $class = in_array($st, ['completed', 'approved'], true) ? 'completed' : ($st !== 'not_started' && $st !== 'pending' ? 'active' : 'pending');
            ?>
            <div class="timeline-item <?= $class ?>">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <h4><?= htmlspecialchars($stage['label']) ?> <?= statusBadge($st) ?></h4>
                    <p><?= htmlspecialchars($p['coordinatorComments'] ?: (STATUSES[$st]['label'] ?? $st)) ?></p>
                    <?php if (!empty($p['presentation']['date'])): ?>
                    <p style="font-size:0.75rem;color:var(--gray-400);margin-top:0.25rem;">
                        <i class="fas fa-calendar"></i> <?= htmlspecialchars($p['presentation']['date']) ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Document Submission Status</h3></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Document</th><th>Stage</th><th>Status</th><th>Date</th></tr>
                </thead>
                <tbody>
                    <?php if (!$uploads): ?>
                    <tr><td colspan="4" style="text-align:center;color:var(--gray-400);padding:1.5rem;">No documents uploaded yet for this <?= htmlspecialchars(strtolower(getTrackLabel($track))) ?> track.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($uploads as $doc): ?>
                    <tr>
                        <td><?= htmlspecialchars($doc['fileName']) ?></td>
                        <td><?= htmlspecialchars($doc['stage']) ?></td>
                        <td><?= statusBadge($doc['status']) ?></td>
                        <td><?= date('M d, Y', strtotime($doc['date'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Important Dates</h3></div>
    <div class="card-body">
        <div class="detail-grid">
            <div class="detail-item">
                <label>Enrollment Date</label>
                <span><?= date('F d, Y', strtotime($mockStudent['enroll_date'])) ?></span>
            </div>
            <div class="detail-item">
                <label>Completion Deadline</label>
                <span><?= date('F d, Y', strtotime($mockStudent['completion_deadline'])) ?></span>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
