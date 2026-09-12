<?php
require_once __DIR__ . '/../../includes/config.php';

$pageTitle   = 'Application Details';
$role        = 'coordinator';
$currentPage = 'applications';
$userName    = $mockCoordinator['name'];

$app = findApplication((int) ($_GET['id'] ?? 0)) ?? latestApplicationForEmail($mockStudent['email']);
if (!$app) {
    $app = storeGet('applications')[0] ?? null;
}
$uploads = $app ? uploadsForApplication((int) $app['id']) : [];
$student = $app ? findStudentByEmail($app['studentEmail']) : null;

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Application Details</h2>
    <p>Review submitted information and documents for this application.</p>
</div>

<?php if (!$app): ?>
<div class="alert alert-warning"><i class="fas fa-info-circle"></i> No application selected.</div>
<?php else: ?>
<div class="card">
    <div class="card-header">
        <h3>Student Information</h3>
        <?= statusBadge($app['status']) ?>
    </div>
    <div class="card-body">
        <div class="detail-grid">
            <div class="detail-item"><label>Student Name</label><span><?= htmlspecialchars($app['student']) ?></span></div>
            <div class="detail-item"><label>Program</label><span><?= htmlspecialchars(PROGRAMS[$app['program']] ?? $app['program']) ?></span></div>
            <div class="detail-item"><label>Email</label><span><?= htmlspecialchars($app['studentEmail']) ?></span></div>
            <div class="detail-item"><label>Adviser</label><span><?= htmlspecialchars($app['adviser'] ?: ($student['adviser'] ?? '—')) ?></span></div>
            <div class="detail-item"><label>Presentation Stage</label><span><?= htmlspecialchars($app['stage']) ?></span></div>
            <div class="detail-item"><label>Application Date</label><span><?= date('F d, Y', strtotime($app['date'])) ?></span></div>
            <div class="detail-item" style="grid-column:1/-1;"><label>Research Title</label><span><?= htmlspecialchars($app['title']) ?></span></div>
            <?php if (!empty($app['coordinatorComment'])): ?>
            <div class="detail-item" style="grid-column:1/-1;"><label>Coordinator Comment</label><span><?= htmlspecialchars($app['coordinatorComment']) ?></span></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Uploaded Documents</h3></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Document</th><th>Type</th><th>Upload Date</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php if (!$uploads): ?>
                    <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:1.5rem;">No documents uploaded for this application.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($uploads as $doc): ?>
                    <tr>
                        <td><?= htmlspecialchars($doc['fileName']) ?></td>
                        <td><?= htmlspecialchars($doc['docType']) ?></td>
                        <td><?= date('M d, Y', strtotime($doc['date'])) ?></td>
                        <td><?= statusBadge($doc['status']) ?></td>
                        <td><?php if (uploadStoragePath($doc)): ?><a class="btn btn-sm btn-outline" href="<?= url('coordinator/download.php?id=' . urlencode($doc['id'])) ?>"><i class="fas fa-download"></i> Download</a><?php else: ?><span style="color:var(--gray-400);font-size:.8rem;">Unavailable</span><?php endif; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="form-actions no-print">
    <a href="<?= url('coordinator/applications/process.php?id=' . $app['id']) ?>" class="btn btn-primary"><i class="fas fa-cog"></i> Process Application</a>
    <a href="<?= url('coordinator/applications/endorsement.php?id=' . $app['id']) ?>" class="btn btn-secondary"><i class="fas fa-file-signature"></i> Generate Endorsement</a>
    <a href="<?= url('coordinator/applications/manage.php') ?>" class="btn btn-outline">Back to List</a>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
