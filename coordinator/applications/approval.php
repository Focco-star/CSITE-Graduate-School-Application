<?php
require_once __DIR__ . '/../../includes/config.php';

$pageTitle   = 'Final Approval';
$role        = 'coordinator';
$currentPage = 'applications';
$userName    = $mockCoordinator['name'];

$app = findApplication((int) ($_GET['id'] ?? 0)) ?? (storeGet('applications')[0] ?? null);
$approvalSuccess = '';
$approvalError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app) {
    $result = $_POST['result'] ?? '';
    $statusMap = [
        'Approved' => 'completed',
        'Approved with Minor Revisions' => 'approved',
        'Requires Major Revisions' => 'requires_revision',
    ];
    if (!isset($statusMap[$result])) {
        $approvalError = 'Select a final presentation result.';
    } else {
        $app = updateApplicationRecord((int) $app['id'], [
            'status' => $statusMap[$result],
            'coordinatorComment' => trim($_POST['comment'] ?? ''),
            'result' => $result,
            'completionDate' => $_POST['completionDate'] ?? date('Y-m-d'),
        ]);
        $approvalSuccess = 'Final result recorded. The student status page now reflects this outcome.';
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Final Approval</h2>
    <p>Record completion of the final presentation process<?= $app ? ' for ' . htmlspecialchars($app['student']) : '' ?>.</p>
</div>

<?php if ($approvalSuccess): ?>
<div class="alert alert-success" data-auto-dismiss><i class="fas fa-check-circle"></i> <?= htmlspecialchars($approvalSuccess) ?></div>
<?php endif; ?>
<?php if ($approvalError): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($approvalError) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Final Presentation Status</h3>
        <?= statusBadge($app['status'] ?? 'pending') ?>
    </div>
    <div class="card-body">
        <div class="detail-grid">
            <div class="detail-item"><label>Student</label><span><?= htmlspecialchars($app['student'] ?? $mockStudent['name']) ?></span></div>
            <div class="detail-item"><label>Program</label><span><?= htmlspecialchars($app['program'] ?? $mockStudent['program_name']) ?></span></div>
            <div class="detail-item"><label>Adviser</label><span><?= htmlspecialchars($app['adviser'] ?? $mockStudent['adviser']) ?></span></div>
            <div class="detail-item"><label>Final Presentation Date</label><span><?= htmlspecialchars($app['completionDate'] ?? '—') ?></span></div>
            <div class="detail-item" style="grid-column:1/-1;"><label>Title</label><span><?= htmlspecialchars($app['title'] ?? $mockStudent['title']) ?></span></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Final Requirements & Documents</h3></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Document</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <tr>
                        <td>Final Research Paper</td>
                        <td><?= statusBadge('pending') ?></td>
                        <td><button class="btn btn-sm btn-outline"><i class="fas fa-download"></i></button></td>
                    </tr>
                    <tr>
                        <td>Adviser Endorsement (Final)</td>
                        <td><?= statusBadge('pending') ?></td>
                        <td><button class="btn btn-sm btn-outline"><i class="fas fa-download"></i></button></td>
                    </tr>
                    <tr>
                        <td>Panel Recommendations</td>
                        <td><?= statusBadge('pending') ?></td>
                        <td><button class="btn btn-sm btn-outline"><i class="fas fa-download"></i></button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<form method="post" action="<?= url('coordinator/applications/approval.php' . ($app ? '?id=' . $app['id'] : '')) ?>" data-validate>
    <div class="card">
        <div class="card-header"><h3>Final Approval Form</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-field">
                    <label>Final Presentation Result <span class="required">*</span></label>
                    <select name="result" required>
                        <option value="">Select result</option>
                        <option>Approved</option>
                        <option>Approved with Minor Revisions</option>
                        <option>Requires Major Revisions</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Completion Date</label>
                    <input type="date" name="completionDate">
                </div>
            </div>
            <div class="form-field">
                <label>Final Comments</label>
                <textarea name="comment" placeholder="Record final panel recommendations and completion notes..."></textarea>
            </div>
            <div class="form-field">
                <label>
                    <input type="checkbox" style="width:auto;margin-right:0.5rem;">
                    Student is ready for final submission to the Graduate School (hardbound)
                </label>
            </div>
        </div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-success"><i class="fas fa-check-circle"></i> Record Final Approval</button>
        <button type="button" class="btn btn-secondary" data-print><i class="fas fa-print"></i> Print Approval Form</button>
        <a href="<?= url('coordinator/applications/manage.php') ?>" class="btn btn-outline">Cancel</a>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
