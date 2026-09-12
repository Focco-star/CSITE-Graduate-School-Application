<?php
require_once __DIR__ . '/../../includes/config.php';

$pageTitle   = 'Edit Panel Member';
$role        = 'coordinator';
$currentPage = 'panel';
$userName    = $mockCoordinator['name'];

$pm = findPanelMember((string) ($_GET['id'] ?? '')) ?? (storeGet('panels')[0] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pm) {
    updatePanelMemberRecord($pm['id'], [
        'name' => trim($_POST['name'] ?? $pm['name']),
        'qualification' => trim($_POST['qualification'] ?? $pm['qualification']),
        'email' => trim($_POST['email'] ?? ''),
        'notes' => trim($_POST['notes'] ?? ''),
        'panelSessions' => (int) ($_POST['panelSessions'] ?? 0),
        'availability' => ($_POST['availability'] ?? 'available') === 'unavailable' ? 'unavailable' : 'available',
    ]);
    redirectTo('coordinator/panel/manage.php');
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Edit Panel Member</h2>
    <p>Update panel member information and availability.</p>
</div>

<?php if (!$pm): ?>
<div class="alert alert-warning">Panel member not found.</div>
<a class="btn btn-outline" href="<?= url('coordinator/panel/manage.php') ?>">Back</a>
<?php else: ?>
<form class="card" method="post" action="<?= url('coordinator/panel/edit.php?id=' . urlencode($pm['id'])) ?>" data-validate>
    <div class="card-body">
        <div class="form-field">
            <label>Panel Member Name <span class="required">*</span></label>
            <input type="text" name="name" required value="<?= htmlspecialchars($pm['name']) ?>">
        </div>
        <div class="form-field">
            <label>Qualification / Degree <span class="required">*</span></label>
            <input type="text" name="qualification" required value="<?= htmlspecialchars($pm['qualification']) ?>">
        </div>
        <div class="form-field">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($pm['email'] ?? '') ?>">
        </div>
        <div class="form-row">
            <div class="form-field">
                <label>Panel Sessions</label>
                <input type="number" name="panelSessions" min="0" value="<?= (int) ($pm['panelSessions'] ?? 0) ?>">
            </div>
            <div class="form-field">
                <label>Availability</label>
                <select name="availability">
                    <option value="available" <?= ($pm['availability'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="unavailable" <?= ($pm['availability'] ?? '') === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
                </select>
            </div>
        </div>
        <div class="form-field">
            <label>Notes</label>
            <textarea name="notes"><?= htmlspecialchars($pm['notes'] ?? '') ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            <a href="<?= url('coordinator/panel/manage.php') ?>" class="btn btn-outline">Cancel</a>
        </div>
    </div>
</form>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
