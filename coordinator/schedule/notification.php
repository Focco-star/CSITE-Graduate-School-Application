<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

$pageTitle   = 'Presentation Notifications';
$role        = 'coordinator';
$currentPage = 'schedule';
$userName    = currentCoordinatorName();

$sch = findSchedule((string) ($_GET['id'] ?? '')) ?? (storeGet('schedules')[0] ?? null);
$notificationPanelMembers = $sch ? databaseSchedulePanelMembers((int) ($sch['applicationId'] ?? 0)) : [];
$panelNames = array_map(static function (array $member): string {
    return trim($member['last_name'] . ', ' . $member['first_name'] . (!empty($member['middle_name']) ? ' ' . $member['middle_name'] : ''));
}, $notificationPanelMembers);
$isScheduled = $sch && !empty($sch['date']) && !empty($sch['time']) && !empty($sch['venue']);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Presentation Notifications</h2>
    <p><?= $sch ? htmlspecialchars(($sch['studentName'] ?? '') . ' — ' . ($sch['stage'] ?? '')) : 'Notification status for panel members and documentor.' ?></p>
</div>


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

<div class="card">
    <div class="card-header"><h3>Panel &amp; Documentor Notifications</h3></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Name</th><th>Role</th><th>Notification</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($panelNames as $i => $name): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($name) ?></strong></td>
                        <td>Panel Member <?= $i + 1 ?></td>
                        <td><?= $isScheduled ? '<span class="status-badge status-confirmed">Automatically ready</span>' : '<span class="status-badge status-pending">Waiting for schedule details</span>' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($sch['adviser'] ?? '—') ?></strong></td>
                        <td>Adviser</td>
                        <td><?= $isScheduled ? '<span class="status-badge status-confirmed">Automatically ready</span>' : '<span class="status-badge status-pending">Waiting for schedule details</span>' ?></td>
                    </tr>
                    <tr>
                        <td><strong><?= htmlspecialchars($sch['documentor'] ?? '—') ?></strong></td>
                        <td><strong>Documentor</strong></td>
                        <td><?= $isScheduled ? '<span class="status-badge status-confirmed">Automatically ready</span>' : '<span class="status-badge status-pending">Waiting for schedule details</span>' ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <span style="font-size:.85rem;color:var(--gray-500);">Notification readiness is derived automatically from the completed schedule. No manual invitation status is needed.</span>
        <a href="<?= url('coordinator/schedule/manage.php') ?>" class="btn btn-outline">Back</a>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
