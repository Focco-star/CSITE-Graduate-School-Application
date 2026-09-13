<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

$pageTitle   = 'Process Application';
$role        = 'coordinator';
$currentPage = 'applications';
$userName    = $mockCoordinator['name'];

$appId = (int) ($_GET['id'] ?? 0);
$app = null;
$isDatabaseApplication = false;

if ($appId > 0) {
    try {
        $stmt = DB::getConnection()->prepare(
            "SELECT
                a.application_id AS id,
                u.email AS studentEmail,
                COALESCE(NULLIF(TRIM(CONCAT(s.first_name, ' ', IFNULL(s.middle_initial, ''), ' ', s.last_name)), ''), u.full_name) AS student,
                CASE
                    WHEN s.program LIKE '%Computer Science%' THEN 'MSCS'
                    WHEN s.program LIKE '%Information Technology%' THEN 'MIT'
                    WHEN s.program LIKE '%Mathematics%' THEN 'MATH'
                    ELSE s.program
                END AS program,
                s.track,
                s.adviser_name AS adviser,
                a.presentation_stage AS stage,
                a.paper_title AS title,
                a.status,
                a.submitted_at AS date
             FROM applications a
             INNER JOIN students s ON s.student_id = a.student_id
             INNER JOIN users u ON u.user_id = s.user_id
             WHERE a.application_id = :id
             LIMIT 1"
        );
        $stmt->execute(['id' => $appId]);
        $app = $stmt->fetch() ?: null;
        if ($app) {
            $app['stageKey'] = stageKeyFromLabel($app['stage']);
            $isDatabaseApplication = true;
        }
    } catch (PDOException $e) {
        $app = null;
    }
}

if (!$app) {
    $app = findApplication($appId) ?? (storeGet('applications')[0] ?? null);
}
$processSuccess = '';
$processError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app) {
    $status = $_POST['status'] ?? '';
    $stageKey = $_POST['stage'] ?? ($app['stageKey'] ?? 'proposal');
    $comment = trim($_POST['comment'] ?? '');
    $advance = ($_POST['advance'] ?? 'no') === 'yes';
    $allowed = ['submitted', 'under_review', 'for_payment', 'payment_recorded', 'ready_for_presentation', 'scheduled', 'approved', 'requires_revision', 'completed'];
    if (!in_array($status, $allowed, true)) {
        $processError = 'Select a valid application status.';
    } else {
        $track = $app['track'] ?? getTrackForProgram($app['program']);
        $stages = getStagesForTrack($track);
        $workflowState = $app['workflowState'] ?? [];
        foreach (($_POST['upload_status'] ?? []) as $uploadId => $uploadStatus) {
            if (!in_array($uploadStatus, ['submitted', 'verified', 'incomplete'], true)) continue;
            $upload = findUpload((string) $uploadId);
            if (!$upload || (int) ($upload['applicationId'] ?? 0) !== (int) $app['id']) continue;
            updateUploadStatus((string) $uploadId, $uploadStatus);
            $type = strtolower((string) $upload['docType']);
            $key = (str_contains($type, 'receipt') || str_contains($type, 'payment')) ? 'receipt' : ((str_contains($type, 'endorsement') || str_contains($type, 'adviser')) ? 'adviser_endorsement' : 'paper');
            $workflowState[$key] = $uploadStatus;
        }
        if ($advance && $status === 'approved') {
            $keys = array_keys($stages);
            $idx = array_search($stageKey, $keys, true);
            if ($idx !== false && isset($keys[$idx + 1])) {
                $stageKey = $keys[$idx + 1];
            }
        }
        $updatedFields = [
            'status' => $status,
            'stageKey' => $stageKey,
            'stage' => $stages[$stageKey] ?? $app['stage'],
            'coordinatorComment' => $comment,
            'workflowState' => $workflowState,
            'gradSchoolEndorsed' => isset($_POST['gradSchoolEndorsed']),
            'paymentRecorded' => isset($_POST['paymentRecorded']),
            'receiptNumber' => trim($_POST['receiptNumber'] ?? ''),
            'paymentDate' => trim($_POST['paymentDate'] ?? ''),
            'paymentAmount' => trim($_POST['paymentAmount'] ?? ''),
            'readyForPresentation' => isset($_POST['readyForPresentation']),
        ];

        try {
            if ($isDatabaseApplication) {
                $saveStatus = DB::getConnection()->prepare(
                    'UPDATE applications
                     SET status = :status, presentation_stage = :presentation_stage
                     WHERE application_id = :application_id'
                );
                $saveStatus->execute([
                    'status' => $status,
                    'presentation_stage' => $updatedFields['stage'],
                    'application_id' => (int) $app['id'],
                ]);
            }

            // Keep existing prototype-session screens synchronized when the same record exists there.
            if (findApplication((int) $app['id'])) {
                updateApplicationRecord((int) $app['id'], $updatedFields);
            }

            $app = array_merge($app, $updatedFields);
            $processSuccess = 'Application status updated and saved to the database.';
        } catch (Throwable $e) {
            $processError = 'Unable to save the application status. Please try again.';
        }
    }
}

$track = $app ? ($app['track'] ?? getTrackForProgram($app['program'])) : 'thesis';
$stages = getStagesForTrack($track);
$uploads = $app ? uploadsForApplication((int) $app['id']) : [];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Process Application</h2>
    <p>Update application status and record progression<?= $app ? ' for ' . htmlspecialchars($app['student']) : '' ?>.</p>
</div>

<?php if ($processSuccess): ?>
<div class="alert alert-success" data-auto-dismiss><i class="fas fa-check-circle"></i> <?= htmlspecialchars($processSuccess) ?></div>
<?php endif; ?>
<?php if ($processError): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($processError) ?></div>
<?php endif; ?>

<?php if (!$app): ?>
<div class="alert alert-warning"><i class="fas fa-info-circle"></i> No application selected.</div>
<?php else: ?>
<div class="card">
    <div class="card-header"><h3>Current Application</h3></div>
    <div class="card-body">
        <div class="detail-grid">
            <div class="detail-item"><label>Student</label><span><?= htmlspecialchars($app['student']) ?></span></div>
            <div class="detail-item"><label>Program</label><span><?= htmlspecialchars($app['program']) ?></span></div>
            <div class="detail-item"><label>Current Stage</label><span><?= htmlspecialchars($app['stage']) ?></span></div>
            <div class="detail-item"><label>Current Status</label><span><?= statusBadge($app['status']) ?></span></div>
        </div>
    </div>
</div>

<form method="post" action="<?= url('coordinator/applications/process.php?id=' . $app['id']) ?>" data-validate>
    <div class="card">
        <div class="card-header"><h3>Update Status</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-field">
                    <label>Application Status <span class="required">*</span></label>
                    <select name="status" required>
                        <option value="">Select status</option>
                        <?php foreach (['submitted','under_review','for_payment','payment_recorded','ready_for_presentation','scheduled','approved','requires_revision','completed'] as $key): ?>
                        <option value="<?= $key ?>" <?= $app['status'] === $key ? 'selected' : '' ?>><?= htmlspecialchars(STATUSES[$key]['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label>Presentation Stage</label>
                    <select name="stage">
                        <?php foreach ($stages as $key => $label): ?>
                        <option value="<?= $key ?>" <?= ($app['stageKey'] ?? '') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-field">
                <label>Recommendations / Comments</label>
                <textarea name="comment" placeholder="Enter recommendations or revision notes for the student..."><?= htmlspecialchars($app['coordinatorComment'] ?? '') ?></textarea>
            </div>
            <div class="form-field">
                <label>Advance to Next Stage?</label>
                <select name="advance">
                    <option value="no">No – keep at current stage</option>
                    <option value="yes">Yes – advance to next presentation stage</option>
                </select>
                <p class="field-hint">Upon approval, the student can proceed to the next stage in the presentation workflow.</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Workflow Records</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-field"><label><input type="checkbox" name="gradSchoolEndorsed" value="1" style="width:auto;margin-right:.5rem;" <?= !empty($app['gradSchoolEndorsed']) ? 'checked' : '' ?>>Graduate School endorsement issued</label></div>
                <div class="form-field"><label><input type="checkbox" name="readyForPresentation" value="1" style="width:auto;margin-right:.5rem;" <?= !empty($app['readyForPresentation']) ? 'checked' : '' ?>>Requirements verified; ready for presentation</label></div>
            </div>
            <div class="form-row">
                <div class="form-field"><label><input type="checkbox" name="paymentRecorded" value="1" style="width:auto;margin-right:.5rem;" <?= !empty($app['paymentRecorded']) ? 'checked' : '' ?>>Official payment / receipt recorded</label></div>
                <div class="form-field"><label>Receipt Number</label><input type="text" name="receiptNumber" value="<?= htmlspecialchars($app['receiptNumber'] ?? '') ?>" placeholder="Receipt number"></div>
            </div>
            <div class="form-row">
                <div class="form-field"><label>Payment Date</label><input type="date" name="paymentDate" value="<?= htmlspecialchars($app['paymentDate'] ?? '') ?>"></div>
                <div class="form-field"><label>Amount</label><input type="text" name="paymentAmount" value="<?= htmlspecialchars($app['paymentAmount'] ?? '') ?>" placeholder="e.g. 1500.00"></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Submitted Documents</h3></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Document</th><th>Review Status</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php if (!$uploads): ?>
                        <tr><td colspan="3" style="color:var(--gray-400);">No documents uploaded.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($uploads as $doc): ?>
                        <tr>
                            <td><?= htmlspecialchars($doc['fileName']) ?></td>
                            <td><select name="upload_status[<?= htmlspecialchars($doc['id']) ?>]" style="padding:3px 8px;"><option value="submitted" <?= $doc['status'] === 'submitted' ? 'selected' : '' ?>>Submitted</option><option value="verified" <?= $doc['status'] === 'verified' || $doc['status'] === 'approved' ? 'selected' : '' ?>>Verified</option><option value="incomplete" <?= $doc['status'] === 'incomplete' ? 'selected' : '' ?>>Incomplete</option></select></td>
                            <td><?php if (uploadStoragePath($doc)): ?><a target="_blank" class="btn btn-sm btn-outline" href="<?= url('coordinator/download.php?id=' . urlencode($doc['id']) . '&view=1') ?>"><i class="fas fa-eye"></i> View</a><?php else: ?><span style="color:var(--gray-400);font-size:.8rem;">Unavailable</span><?php endif; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save &amp; Process</button>
        <a href="<?= url('coordinator/applications/endorsement.php?id=' . $app['id']) ?>" class="btn btn-secondary"><i class="fas fa-file-signature"></i> Generate Endorsement</a>
        <a href="<?= url('coordinator/schedule/add.php?app=' . $app['id']) ?>" class="btn btn-outline"><i class="fas fa-calendar-plus"></i> Schedule Presentation</a>
        <a href="<?= url('coordinator/applications/manage.php') ?>" class="btn btn-outline">Cancel</a>
    </div>
</form>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
