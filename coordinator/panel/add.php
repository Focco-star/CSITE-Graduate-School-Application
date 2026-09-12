<?php
require_once __DIR__ . '/../../includes/config.php';

$pageTitle   = 'Add Panel Member';
$role        = 'coordinator';
$currentPage = 'panel';
$userName    = $mockCoordinator['name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $qual = trim($_POST['qualification'] ?? '');
    if ($name !== '' && $qual !== '') {
        addPanelMemberRecord([
            'name' => $name,
            'qualification' => $qual,
            'email' => trim($_POST['email'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
            'panelSessions' => (int) ($_POST['panelSessions'] ?? 0),
            'availability' => 'available',
        ]);
        redirectTo('coordinator/panel/manage.php');
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Add Panel Member</h2>
    <p>Register a new panel member for capstone/thesis presentations.</p>
</div>

<form class="card" method="post" action="<?= url('coordinator/panel/add.php') ?>" data-validate>
    <div class="card-body">
        <div class="form-field">
            <label>Panel Member Name <span class="required">*</span></label>
            <input type="text" name="name" required placeholder="Full name">
        </div>
        <div class="form-field">
            <label>Qualification / Degree <span class="required">*</span></label>
            <input type="text" name="qualification" required placeholder="e.g., PhD in Computer Science">
        </div>
        <div class="form-field">
            <label>Email</label>
            <input type="email" name="email" placeholder="email@adzu.edu.ph">
        </div>
        <div class="form-field">
            <label>Panel Sessions</label>
            <input type="number" name="panelSessions" min="0" value="0">
            <p class="field-hint">How many panel sessions has this member completed so far?</p>
        </div>
        <div class="form-field">
            <label>Notes</label>
            <textarea name="notes" placeholder="Additional notes about this panel member..."></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Panel Member</button>
            <a href="<?= url('coordinator/panel/manage.php') ?>" class="btn btn-outline">Cancel</a>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
