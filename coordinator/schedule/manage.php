<?php
require_once __DIR__ . '/../../includes/config.php';

$pageTitle   = 'Presentation Schedules';
$role        = 'coordinator';
$currentPage = 'schedule';
$userName    = $mockCoordinator['name'];

if (isset($_GET['delete']) && $_GET['delete'] !== '') {
    deleteScheduleRecord((string) $_GET['delete']);
    redirectTo('coordinator/schedule/manage.php');
}

$schedules = storeGet('schedules');

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Presentation Schedule Dashboard</h2>
    <p>View and manage scheduled capstone/thesis presentations.</p>
</div>

<div class="form-actions" style="border:none;padding:0;margin-bottom:1.5rem;">
    <a href="<?= url('coordinator/schedule/add.php') ?>" class="btn btn-primary"><i class="fas fa-calendar-plus"></i> Schedule Presentation</a>
</div>

<div class="card">
    <div class="card-header"><h3>Scheduled Presentations</h3></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Stage</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Panel Members</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$schedules): ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--gray-400);padding:2rem;">No presentations scheduled yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($schedules as $sch): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($sch['studentName'] ?? $sch['student'] ?? '') ?></strong></td>
                        <td><?= htmlspecialchars($sch['stage']) ?></td>
                        <td><?= htmlspecialchars($sch['date']) ?></td>
                        <td><?= htmlspecialchars($sch['time']) ?></td>
                        <td style="max-width:200px;font-size:0.8rem;"><?= htmlspecialchars($sch['panel'] ?? $sch['panels'] ?? '') ?></td>
                        <td><?= statusBadge($sch['status']) ?></td>
                        <td class="actions">
                            <a href="<?= url('coordinator/schedule/edit.php?id=' . urlencode($sch['id'])) ?>" class="btn btn-sm btn-outline" title="Edit"><i class="fas fa-edit"></i></a>
                            <a href="<?= url('coordinator/schedule/panel.php?id=' . urlencode($sch['id'])) ?>" class="btn btn-sm btn-outline" title="Panel"><i class="fas fa-users"></i></a>
                            <a href="<?= url('coordinator/schedule/notification.php?id=' . urlencode($sch['id'])) ?>" class="btn btn-sm btn-primary" title="Notify"><i class="fas fa-bell"></i></a>
                            <?= coordDeleteLink(url('coordinator/schedule/manage.php?delete=' . urlencode($sch['id'])), 'Delete this schedule?') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
