<?php
require_once __DIR__ . '/../../includes/config.php';

$pageTitle   = 'Edit Schedule';
$role        = 'coordinator';
$currentPage = 'schedule';
$userName    = $mockCoordinator['name'];

$sch = findSchedule((string) ($_GET['id'] ?? '')) ?? (storeGet('schedules')[0] ?? null);
$panelOpts = panelSelectOptions();
$parsed = $sch ? array_map('trim', explode(',', (string) ($sch['panel'] ?? ''))) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $sch) {
    $panel = implode(', ', array_filter([$_POST['panel1'] ?? '', $_POST['panel2'] ?? '', $_POST['panel3'] ?? '']));
    updateScheduleRecord($sch['id'], [
        'date' => trim($_POST['date'] ?? $sch['date']),
        'time' => trim($_POST['time'] ?? $sch['time']),
        'venue' => trim($_POST['venue'] ?? ($sch['venue'] ?? '')),
        'status' => ($_POST['status'] ?? 'pending') === 'confirmed' ? 'confirmed' : 'pending',
        'panel' => $panel ?: ($sch['panel'] ?? 'TBD'),
    ]);
    redirectTo('coordinator/schedule/manage.php');
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Edit Presentation Schedule</h2>
    <p><?= $sch ? htmlspecialchars(($sch['studentName'] ?? '') . ' — ' . ($sch['stage'] ?? '')) : 'Edit schedule details.' ?></p>
</div>

<?php if (!$sch): ?>
<div class="alert alert-warning">Schedule not found.</div>
<?php else: ?>
<form method="post" action="<?= url('coordinator/schedule/edit.php?id=' . urlencode($sch['id'])) ?>" data-validate>
    <div class="card">
        <div class="card-header"><h3>Schedule Details</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-field">
                    <label>Presentation Date <span class="required">*</span></label>
                    <input type="text" name="date" required value="<?= htmlspecialchars($sch['date']) ?>" placeholder="e.g. Apr 10, 2025">
                </div>
                <div class="form-field">
                    <label>Presentation Time <span class="required">*</span></label>
                    <input type="text" name="time" required value="<?= htmlspecialchars($sch['time']) ?>" placeholder="e.g. 9:00 AM">
                </div>
            </div>
            <div class="form-field">
                <label>Venue</label>
                <input type="text" name="venue" value="<?= htmlspecialchars($sch['venue'] ?? '') ?>" placeholder="e.g. CSITE Seminar Room">
            </div>
            <div class="form-field">
                <label>Status</label>
                <select name="status">
                    <option value="pending" <?= $sch['status'] === 'pending' ? 'selected' : '' ?>>Pending Confirmation</option>
                    <option value="confirmed" <?= $sch['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3>Panel Members</h3></div>
        <div class="card-body">
            <?php for ($i = 1; $i <= 3; $i++): $val = $parsed[$i - 1] ?? ''; ?>
            <div class="form-field">
                <label>Panel Member <?= $i ?></label>
                <select name="panel<?= $i ?>">
                    <option value="">Keep current</option>
                    <?php foreach ($panelOpts as $p): ?>
                    <option <?= $val === $p || str_contains($p, $val) ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endfor; ?>
        </div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
        <a href="<?= url('coordinator/schedule/manage.php') ?>" class="btn btn-outline">Cancel</a>
    </div>
</form>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
