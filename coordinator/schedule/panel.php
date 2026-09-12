<?php
require_once __DIR__ . '/../../includes/config.php';

$pageTitle   = 'Panel Assignment';
$role        = 'coordinator';
$currentPage = 'schedule';
$userName    = $mockCoordinator['name'];

$sch = findSchedule((string) ($_GET['id'] ?? '')) ?? (storeGet('schedules')[0] ?? null);
$panelNames = $sch ? array_values(array_filter(array_map('trim', explode(',', (string) ($sch['panel'] ?? ''))))) : [];
$members = storeGet('panels');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_avail'])) {
    $pm = findPanelMember((string) $_POST['toggle_avail']);
    if ($pm) {
        updatePanelMemberRecord($pm['id'], [
            'availability' => ($pm['availability'] ?? 'available') === 'available' ? 'unavailable' : 'available',
        ]);
    }
    $qid = $sch['id'] ?? '';
    redirectTo('coordinator/schedule/panel.php' . ($qid ? '?id=' . urlencode($qid) : ''));
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Panel Assignment</h2>
    <p><?= $sch ? htmlspecialchars(($sch['studentName'] ?? '') . ' — ' . ($sch['stage'] ?? '') . ' (' . ($sch['date'] ?? '') . ')') : 'Panel composition for selected presentation.' ?></p>
</div>

<div class="card">
    <div class="card-header"><h3>Assigned Panel — <?= htmlspecialchars($sch['studentName'] ?? '—') ?></h3></div>
    <div class="card-body">
        <?php if ($panelNames): ?>
        <div style="display:flex;flex-direction:column;gap:0.6rem;">
            <?php foreach ($panelNames as $idx => $name): ?>
            <div style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 1rem;background:var(--gray-50);border-radius:var(--radius);border:1px solid var(--gray-200);">
                <div style="width:32px;height:32px;border-radius:50%;background:rgba(6,2,151,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-user" style="color:var(--adzu-navy);font-size:0.8rem;"></i>
                </div>
                <div>
                    <div style="font-weight:600;font-size:0.875rem;"><?= htmlspecialchars($name) ?></div>
                    <div style="font-size:0.73rem;color:var(--gray-400);">Panel Member <?= $idx + 1 ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style="color:var(--gray-400);font-size:0.85rem;">No panel members assigned to this schedule yet.</p>
        <?php endif; ?>
        <?php if ($sch): ?>
        <div style="margin-top:0.75rem;">
            <a class="btn btn-sm btn-outline" href="<?= url('coordinator/schedule/edit.php?id=' . urlencode($sch['id'])) ?>"><i class="fas fa-edit"></i> Edit Panel in Schedule</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Panel Member Pool</h3></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Name</th><th>Qualification</th><th>Panel Sessions</th><th>Availability</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $pm): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($pm['name']) ?></strong></td>
                        <td style="font-size:0.82rem;"><?= htmlspecialchars($pm['qualification']) ?></td>
                        <td><?= (int) $pm['panelSessions'] ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="toggle_avail" value="<?= htmlspecialchars($pm['id']) ?>">
                                <button type="submit" class="btn btn-sm <?= ($pm['availability'] ?? '') === 'available' ? 'btn-primary' : 'btn-outline' ?>" style="font-size:0.73rem;padding:2px 10px;">
                                    <?= ($pm['availability'] ?? '') === 'available' ? 'Available' : 'Unavailable' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <a href="<?= url('coordinator/schedule/manage.php') ?>" class="btn btn-outline">Back to Schedules</a>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
