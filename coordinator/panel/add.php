<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle   = 'Add Panel Member';
$role        = 'coordinator';
$currentPage = 'panel';
$userName    = $mockCoordinator['name'];
$popup       = null;   // success pop-up, shown after the save redirect (see below)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = formatPersonName($_POST['first_name'] ?? '');
    $lastName = formatPersonName($_POST['last_name'] ?? '');
    $qual = trim($_POST['qualification'] ?? '');
    if ($firstName !== '' && $lastName !== '' && $qual !== '') {
        addPanelMemberRecord([
            'first_name' => $firstName,
            'middle_name' => formatPersonName($_POST['middle_name'] ?? ''),
            'last_name' => $lastName,
            'qualification' => $qual,
            'email' => trim($_POST['email'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
            'availability' => 'available',
        ]);
        // Post/Redirect/Get: reload this page so the success pop-up shows, then it forwards to the list
        $_SESSION['panel_success_popup'] = [
            'title'   => 'Panel Member Added Successfully',
            'message' => '"' . trim($firstName . ' ' . $lastName) . '" was added to the panel.',
        ];
        redirectTo('coordinator/panel/add.php');
    }
} else {
    $popup = $_SESSION['panel_success_popup'] ?? null;
    unset($_SESSION['panel_success_popup']);
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
            <label>First Name <span class="required">*</span></label>
            <input type="text" name="first_name" required>
        </div>
        <div class="form-field">
            <label>Middle Name</label>
            <input type="text" name="middle_name">
        </div>
        <div class="form-field">
            <label>Last Name <span class="required">*</span></label>
            <input type="text" name="last_name" required>
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
            <label>Notes</label>
            <textarea name="notes" placeholder="Additional notes about this panel member..."></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Panel Member</button>
            <a href="<?= url('coordinator/panel/manage.php') ?>" class="btn btn-outline">Cancel</a>
        </div>
    </div>
</form>

<?php if ($popup): ?>
<!-- Shows for 5 seconds (or until the X is clicked), then returns to the panel list -->
<div id="successPopupData" hidden
     data-title="<?= htmlspecialchars($popup['title']) ?>"
     data-message="<?= htmlspecialchars($popup['message']) ?>"
     data-redirect="<?= htmlspecialchars(url('coordinator/panel/manage.php')) ?>"></div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>