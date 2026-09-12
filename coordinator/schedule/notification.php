<?php
require_once __DIR__ . '/../../includes/config.php';

$pageTitle   = 'Presentation Notifications';
$role        = 'coordinator';
$currentPage = 'schedule';
$userName    = $mockCoordinator['name'];

$sch = findSchedule((string) ($_GET['id'] ?? '')) ?? (storeGet('schedules')[0] ?? null);
$panelNames = $sch ? array_values(array_filter(array_map('trim', explode(',', (string) ($sch['panel'] ?? ''))))) : ['Dr. Juan Dela Cruz', 'Dr. Ana Reyes', 'Prof. Miguel Santos'];
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $sch) {
    $notifications = [];
    foreach (($_POST['invite'] ?? []) as $key => $status) {
        $notifications[$key] = ['invite' => in_array($status, ['pending', 'submitted', 'approved'], true) ? $status : 'pending', 'template' => ($_POST['template'][$key] ?? 'n/a') === 'completed' ? 'completed' : (($_POST['template'][$key] ?? 'n/a') === 'incomplete' ? 'incomplete' : 'n/a')];
    }
    $sch = updateScheduleRecord($sch['id'], ['notifications' => $notifications]);
    $notice = 'Notification statuses saved.';
}
$notifications = $sch['notifications'] ?? [];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Presentation Notifications</h2>
    <p><?= $sch ? htmlspecialchars(($sch['studentName'] ?? '') . ' — ' . ($sch['stage'] ?? '')) : 'Notification status for panel members and documentor.' ?></p>
</div>

<?php if ($notice): ?><div class="alert alert-success" data-auto-dismiss><i class="fas fa-check-circle"></i> <?= htmlspecialchars($notice) ?></div><?php endif; ?>

<div class="card">
    <div class="card-header"><h3>Presentation Details</h3></div>
    <div class="card-body">
        <div class="detail-grid">
            <div class="detail-item"><label>Student</label><span><?= htmlspecialchars($sch['studentName'] ?? '—') ?></span></div>
            <div class="detail-item"><label>Stage</label><span><?= htmlspecialchars($sch['stage'] ?? '—') ?></span></div>
            <div class="detail-item"><label>Date &amp; Time</label><span><?= $sch ? htmlspecialchars(($sch['date'] ?? '') . ' – ' . ($sch['time'] ?? '')) : '—' ?></span></div>
            <?php if (!empty($sch['venue'])): ?>
            <div class="detail-item"><label>Venue</label><span><?= htmlspecialchars($sch['venue']) ?></span></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<form method="post" action="<?= url('coordinator/schedule/notification.php?id=' . urlencode($sch['id'] ?? '')) ?>" class="card">
    <div class="card-header"><h3>Panel &amp; Documentor Notifications</h3></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Name</th><th>Role</th><th>Invitation Status</th><th>Template Sent</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($panelNames as $i => $name): $key = 'panel_' . $i; $row = $notifications[$key] ?? []; ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($name) ?></strong></td>
                        <td>Panel Member <?= $i + 1 ?></td>
                        <td>
                            <select name="invite[<?= $key ?>]" style="padding:3px 8px;border:1px solid var(--gray-300);border-radius:var(--radius);font-size:0.8rem;">
                                <option value="pending" <?= ($row['invite'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="submitted" <?= ($row['invite'] ?? '') === 'submitted' ? 'selected' : '' ?>>Sent</option>
                                <option value="approved" <?= ($row['invite'] ?? '') === 'approved' ? 'selected' : '' ?>>Confirmed</option>
                            </select>
                        </td>
                        <td><span style="font-size:0.75rem;color:var(--gray-300);font-style:italic;">Not applicable</span></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr><?php $adviserKey = 'adviser'; $adviserRow = $notifications[$adviserKey] ?? []; ?>
                        <td><strong><?= htmlspecialchars($sch['adviser'] ?? 'Dr. Maria Santos') ?></strong></td>
                        <td>Adviser</td>
                        <td>
                            <select name="invite[<?= $adviserKey ?>]" style="padding:3px 8px;border:1px solid var(--gray-300);border-radius:var(--radius);font-size:0.8rem;">
                                <option value="pending" <?= ($adviserRow['invite'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="submitted" <?= ($adviserRow['invite'] ?? '') === 'submitted' ? 'selected' : '' ?>>Sent</option>
                                <option value="approved" <?= ($adviserRow['invite'] ?? '') === 'approved' ? 'selected' : '' ?>>Confirmed</option>
                            </select>
                        </td>
                        <td><span style="font-size:0.75rem;color:var(--gray-300);font-style:italic;">Not applicable</span></td>
                    </tr>
                    <tr><?php $documentorKey = 'documentor'; $documentorRow = $notifications[$documentorKey] ?? []; ?>
                        <td><strong><?= htmlspecialchars($sch['documentor'] ?? 'Prof. Lisa Fernandez') ?></strong></td>
                        <td><strong>Documentor</strong></td>
                        <td>
                            <select name="invite[<?= $documentorKey ?>]" style="padding:3px 8px;border:1px solid var(--gray-300);border-radius:var(--radius);font-size:0.8rem;">
                                <option value="pending" <?= ($documentorRow['invite'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="submitted" <?= ($documentorRow['invite'] ?? '') === 'submitted' ? 'selected' : '' ?>>Sent</option>
                                <option value="approved" <?= ($documentorRow['invite'] ?? '') === 'approved' ? 'selected' : '' ?>>Confirmed</option>
                            </select>
                        </td>
                        <td>
                            <select name="template[<?= $documentorKey ?>]" style="padding:3px 8px;border:1px solid var(--gray-300);border-radius:var(--radius);font-size:0.8rem;">
                                <option value="incomplete" <?= ($documentorRow['template'] ?? '') === 'incomplete' ? 'selected' : '' ?>>Incomplete</option>
                                <option value="completed" <?= ($documentorRow['template'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Notification Status</button>
        <a href="<?= url('coordinator/schedule/manage.php') ?>" class="btn btn-outline">Back</a>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
