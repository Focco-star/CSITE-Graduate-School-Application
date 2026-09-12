<?php
require_once __DIR__ . '/../../includes/config.php';

$pageTitle   = 'Panel Members';
$role        = 'coordinator';
$currentPage = 'panel';
$userName    = $mockCoordinator['name'];

if (isset($_GET['delete']) && $_GET['delete'] !== '') {
    deletePanelMemberRecord((string) $_GET['delete']);
    redirectTo('coordinator/panel/manage.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_avail'])) {
    $pm = findPanelMember((string) $_POST['toggle_avail']);
    if ($pm) {
        updatePanelMemberRecord($pm['id'], [
            'availability' => ($pm['availability'] ?? 'available') === 'available' ? 'unavailable' : 'available',
        ]);
    }
    redirectTo('coordinator/panel/manage.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sessions_id'])) {
    updatePanelMemberRecord((string) $_POST['sessions_id'], [
        'panelSessions' => (int) ($_POST['panelSessions'] ?? 0),
    ]);
    redirectTo('coordinator/panel/manage.php');
}

$panelMembers = storeGet('panels');

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Panel Member Management</h2>
    <p>Manage available panel members, qualifications, and availability.</p>
</div>

<div class="form-actions" style="border:none;padding:0;margin-bottom:1.5rem;">
    <a href="<?= url('coordinator/panel/add.php') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Add Panel Member</a>
</div>

<div class="card">
    <div class="card-header"><h3>Panel Members <span style="font-size:0.8rem;font-weight:400;color:var(--gray-500);">(<?= count($panelMembers) ?>)</span></h3></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Name</th><th>Qualification</th><th>Panel Sessions</th><th>Availability</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (!$panelMembers): ?>
                    <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:2rem;">No panel members yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($panelMembers as $pm): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($pm['name']) ?></strong>
                            <?php if (!empty($pm['email'])): ?>
                            <div style="font-size:0.75rem;color:var(--gray-400);"><?= htmlspecialchars($pm['email']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:0.83rem;"><?= htmlspecialchars($pm['qualification']) ?></td>
                        <td>
                            <form method="post" style="display:flex;align-items:center;gap:0.5rem;">
                                <input type="hidden" name="sessions_id" value="<?= htmlspecialchars($pm['id']) ?>">
                                <input type="number" min="0" name="panelSessions" value="<?= (int) $pm['panelSessions'] ?>" style="width:72px;padding:0.25rem 0.4rem;">
                                <button class="btn btn-sm btn-outline" type="submit" title="Save sessions"><i class="fas fa-save"></i></button>
                            </form>
                        </td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="toggle_avail" value="<?= htmlspecialchars($pm['id']) ?>">
                                <button type="submit" class="btn btn-sm <?= ($pm['availability'] ?? 'available') === 'available' ? 'btn-primary' : 'btn-outline' ?>" style="font-size:0.75rem;padding:3px 10px;">
                                    <?php if (($pm['availability'] ?? 'available') === 'available'): ?>
                                    <i class="fas fa-check-circle"></i> Available
                                    <?php else: ?>
                                    <i class="fas fa-ban"></i> Unavailable
                                    <?php endif; ?>
                                </button>
                            </form>
                        </td>
                        <td class="actions">
                            <a href="<?= url('coordinator/panel/edit.php?id=' . urlencode($pm['id'])) ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i> Edit</a>
                            <?= coordDeleteLink(url('coordinator/panel/manage.php?delete=' . urlencode($pm['id'])), 'Remove this panel member?') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
