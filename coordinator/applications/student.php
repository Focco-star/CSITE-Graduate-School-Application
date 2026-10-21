<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

$pageTitle   = 'Student Applications';
$role        = 'coordinator';
$currentPage = 'applications';
$userName    = !empty($_SESSION['user']['full_name']) ? $_SESSION['user']['full_name'] : ($mockCoordinator['name'] ?? 'Coordinator');

$userId = (int) ($_GET['id'] ?? 0);
$selectedId = (int) ($_GET['app'] ?? 0);

/* ------------------------------------------------------------------
 * The student and the applications they have submitted.
 * ------------------------------------------------------------------ */
$student = null;
$applications = [];
foreach (coordinatorStudentsWithApplications() as $candidate) {
    if ((int) $candidate['userId'] === $userId) {
        $student = $candidate;
        break;
    }
}

if ($student) {
    $applications = $student['applications'];
}

$selected = null;
foreach ($applications as $application) {
    if ((int) $application['id'] === $selectedId) {
        $selected = $application;
        break;
    }
}
if (!$selected && $applications) {
    $selected = $applications[0];
}

$track     = $student['track'] ?? 'thesis';
$trackLabel = $student['trackLabel'] ?? getTrackLabel($track);
$milestoneSteps = [];
$milestoneStats = ['done' => 0, 'total' => 0, 'firstPending' => 0];
$uploads = [];
$requiredDocs = [];

if ($selected) {
    $stageKey = stageKeyFromLabel((string) ($selected['stage'] ?? ''));
    $uploads = uploadsForApplication((int) $selected['id']);
    $requiredDocs = requiredDocumentsForStage($track, $stageKey);
    $milestoneSteps = stageMilestoneSteps((string) $student['email'], $track, $stageKey);
    $milestoneStats = milestoneProgress($milestoneSteps);
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><?= $student ? htmlspecialchars($student['name']) : 'Student' ?></h2>
    <p>
        <?php if ($student): ?>
        Account information and the applications of <?= htmlspecialchars($student['name']) ?>.
        <?php else: ?>
        This student record could not be found.
        <?php endif; ?>
    </p>
</div>

<?php if (!$student): ?>
<div class="alert alert-warning"><i class="fas fa-info-circle"></i> This student could not be found.</div>
<?php else: ?>

<div class="card">
    <div class="card-header"><h3>Account Information</h3></div>
    <div class="card-body">
        <div class="detail-grid">
            <div class="detail-item"><label>Student Name</label><span><?= htmlspecialchars($student['name']) ?></span></div>
            <div class="detail-item"><label>Email</label><span><?= htmlspecialchars($student['email']) ?></span></div>
            <div class="detail-item"><label>ADZU ID</label><span><?= htmlspecialchars($student['studentId'] !== '' ? $student['studentId'] : '—') ?></span></div>
            <div class="detail-item"><label>Program</label><span><?= htmlspecialchars($student['program']) ?></span></div>
            <div class="detail-item"><label>Track</label><span><?= htmlspecialchars($trackLabel) ?></span></div>
            <div class="detail-item"><label>Adviser</label><span><?= htmlspecialchars($student['adviser'] !== '' ? $student['adviser'] : '—') ?></span></div>
            <div class="detail-item"><label>Enrolled</label><span><?= $student['enrolled'] ? date('M d, Y', strtotime($student['enrolled'])) : '—' ?></span></div>
            <div class="detail-item"><label>Registered</label><span><?= $student['registeredAt'] ? date('M d, Y', strtotime($student['registeredAt'])) : '—' ?></span></div>
            <div class="detail-item"><label>Submission Status</label><span><?= submissionBadge($applications !== []) ?></span></div>
        </div>
    </div>
</div>

<?php if (!$applications): ?>
<div class="card">
    <div class="card-header"><h3>Application Records</h3></div>
    <div class="card-body">
        <div class="alert alert-info" style="margin:0;">
            <i class="fas fa-info-circle"></i>
            <div><strong>This student has not submitted an application yet.</strong></div>
        </div>
    </div>
</div>
<?php else: ?>

<div class="card">
    <div class="card-header">
        <h3>Application Records <span class="filter-count"><?= count($applications) ?></span></h3>
    </div>
    <div class="card-body">
        <p style="margin-bottom:1rem;font-size:0.85rem;color:var(--gray-500);">
            <?= count($applications) === 1 ? 'This student has one application on record.' : 'This student has ' . count($applications) . ' applications on record. Select one to review it.' ?>
        </p>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Application</th>
                        <th>Stage</th>
                        <th>Title</th>
                        <th>Submitted</th>
                        <th>Application Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $application):
                        $isSelected = $selected && (int) $application['id'] === (int) $selected['id'];
                    ?>
                    <tr<?= $isSelected ? ' style="background:var(--gray-50);"' : '' ?>>
                        <td><strong>#<?= (int) $application['id'] ?></strong></td>
                        <td><?= htmlspecialchars($application['stage']) ?></td>
                        <td class="app-title-cell"><?= htmlspecialchars($application['title']) ?></td>
                        <td><?= $application['date'] ? date('M d, Y', strtotime($application['date'])) : '—' ?></td>
                        <td><?= statusBadge($application['status']) ?></td>
                        <td class="actions">
                            <a href="<?= url('coordinator/applications/student.php?id=' . (int) $student['userId'] . '&app=' . (int) $application['id']) ?>" class="btn btn-sm btn-outline">Select</a>
                            <a href="<?= url('coordinator/applications/view.php?id=' . (int) $application['id']) ?>" class="btn btn-sm btn-outline" title="View application"><i class="fas fa-eye"></i></a>
                            <a href="<?= url('coordinator/applications/process.php?id=' . (int) $application['id']) ?>" class="btn btn-sm btn-primary" title="Process application"><i class="fas fa-cog"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($selected): ?>
<div class="card">
    <div class="card-header">
        <h3>Application #<?= (int) $selected['id'] ?> — <?= htmlspecialchars($selected['stage']) ?></h3>
        <span class="status-badge status-submitted">Submitted</span>
    </div>
    <div class="card-body">
        <div class="detail-grid">
            <div class="detail-item" style="grid-column:1/-1;"><label>Research / Paper Title</label><span><?= htmlspecialchars($selected['title']) ?></span></div>
            <div class="detail-item"><label>Current Stage</label><span><?= htmlspecialchars($selected['stage']) ?></span></div>
            <div class="detail-item"><label>Application Status</label><span><?= statusBadge($selected['status']) ?></span></div>
            <div class="detail-item"><label>Adviser</label><span><?= htmlspecialchars($student['adviser'] !== '' ? $student['adviser'] : '—') ?></span></div>
            <div class="detail-item"><label>Submission Date</label><span><?= $selected['date'] ? date('F d, Y', strtotime($selected['date'])) : '—' ?></span></div>
            <div class="detail-item"><label>Presentation Result</label><span><?= $selected['result'] ? htmlspecialchars($selected['result'] === 'approved' ? 'Approved' : 'Requires Revision') : '—' ?></span></div>
            <div class="detail-item"><label>Graduate School Endorsement</label><span><?= !empty($selected['gradSchoolEndorsed']) ? 'Issued' : 'Not yet issued' ?></span></div>
            <div class="detail-item"><label>Payment Recorded</label><span><?= !empty($selected['paymentRecorded']) ? 'Yes' . (!empty($selected['receiptNumber']) ? ' (' . htmlspecialchars($selected['receiptNumber']) . ')' : '') : 'No' ?></span></div>
            <div class="detail-item"><label>Ready for Presentation</label><span><?= !empty($selected['readyForPresentation']) ? 'Yes' : 'No' ?></span></div>
            <?php if (!empty($selected['coordinatorComment'])): ?>
            <div class="detail-item" style="grid-column:1/-1;"><label>Coordinator Comment</label><span><?= htmlspecialchars($selected['coordinatorComment']) ?></span></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-tasks"></i> Milestone Progress</h3>
        <div class="ms-overview">
            <span class="ms-overview-text"><?= (int) $milestoneStats['done'] ?> of <?= (int) $milestoneStats['total'] ?> milestones complete</span>
            <span class="ms-overview-bar"><span class="ms-overview-fill" style="width:<?= $milestoneStats['total'] > 0 ? (int) round($milestoneStats['done'] * 100 / $milestoneStats['total']) : 0 ?>%;"></span></span>
        </div>
    </div>
    <div class="card-body">
        <?php if (!$milestoneSteps): ?>
        <p class="ms-empty">No milestones are defined for this stage.</p>
        <?php else: ?>
        <div class="ms-stage is-current is-open" data-ms-stage>
            <div class="ms-stage-body" style="border-top:none;">
                <?php foreach ($milestoneSteps as $si => $step):
                    $state = $step['state']['status'] ?? 'pending';
                    $isDone = $state === 'done';
                    $isFlagged = $state === 'flagged';
                    $isActive = !$isDone && !$isFlagged && $si === $milestoneStats['firstPending'];
                    $iconState = $isFlagged ? 'flagged' : ($isDone ? 'done' : ($isActive ? 'active' : 'pending'));
                    $rowClass = $isFlagged ? 'is-flagged' : ($isDone ? 'is-done' : ($isActive ? 'is-active' : 'is-pending'));
                ?>
                <div class="ms-step <?= $rowClass ?>">
                    <div class="ms-step-col">
                        <?= stepIconHtml($iconState) ?>
                        <?php if ($si < count($milestoneSteps) - 1): ?>
                        <span class="ms-step-line<?= $isDone ? ' done' : '' ?>"></span>
                        <?php endif; ?>
                    </div>
                    <div class="ms-step-body">
                        <div class="ms-step-label">
                            <span><?= htmlspecialchars($step['label']) ?></span>
                            <?php if ($step['coord']): ?><span class="coord-chip">Coordinator</span><?php endif; ?>
                            <?php if ($isActive): ?><span class="ms-step-tag active">Next</span><?php endif; ?>
                            <?php if ($isDone): ?><span class="ms-step-tag done">Done</span><?php endif; ?>
                            <?php if ($isFlagged): ?><span class="ms-step-tag flagged">Action needed</span><?php endif; ?>
                        </div>
                        <p class="ms-step-detail"><?= htmlspecialchars($step['detail']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Submitted Documents</h3></div>
    <div class="card-body">
        <?php if ($requiredDocs): ?>
        <p class="field-hint" style="margin-bottom:0.75rem;">
            Required documents for the <strong><?= htmlspecialchars($trackLabel) ?></strong> track — <?= htmlspecialchars($selected['stage']) ?>.
        </p>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Required Document</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($requiredDocs as $requirement):
                        $matched = bestUploadOfKind($uploads, $requirement['kind']);
                        $isDone = $matched && in_array($matched['status'] ?? '', ['verified', 'approved'], true);
                        $isFlagged = $matched && ($matched['status'] ?? '') === 'incomplete';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($requirement['label']) ?></td>
                        <td>
                            <?php if ($requirement['kind'] === 'grad_school_endorsement'): ?>
                                <span class="status-badge <?= !empty($selected['gradSchoolEndorsed']) ? 'status-approved' : 'status-not-submitted' ?>">
                                    <?= !empty($selected['gradSchoolEndorsed']) ? 'Issued' : 'Not Yet Issued' ?>
                                </span>
                            <?php elseif ($isDone): ?>
                                <span class="status-badge status-approved">Verified</span>
                            <?php elseif ($isFlagged): ?>
                                <span class="status-badge status-revision">Incomplete</span>
                            <?php elseif ($matched): ?>
                                <span class="status-badge status-submitted">Submitted</span>
                            <?php else: ?>
                                <span class="status-badge status-not-submitted">Not Yet Submitted</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="table-responsive" style="margin-top:1.25rem;">
            <table class="data-table">
                <thead><tr><th>File Name</th><th>Type</th><th>Stage</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (!$uploads): ?>
                    <tr><td colspan="6" style="text-align:center;color:var(--gray-400);padding:1.5rem;">No documents uploaded yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($uploads as $doc): ?>
                    <tr>
                        <td><?= htmlspecialchars($doc['fileName']) ?></td>
                        <td><?= htmlspecialchars($doc['docType']) ?></td>
                        <td><?= htmlspecialchars($doc['stage']) ?></td>
                        <td><?= date('M d, Y', strtotime($doc['date'])) ?></td>
                        <td><?= statusBadge($doc['status']) ?></td>
                        <td class="actions">
                            <?php if (uploadStoragePath($doc)): ?>
                            <a class="btn btn-sm btn-outline" href="<?= url('coordinator/download.php?id=' . urlencode($doc['id'])) ?>"><i class="fas fa-download"></i> Download</a>
                            <?php else: ?>
                            <span style="color:var(--gray-400);font-size:.8rem;">Unavailable</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<div class="form-actions">
    <a href="<?= url('coordinator/applications/manage.php') ?>" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Applications</a>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
