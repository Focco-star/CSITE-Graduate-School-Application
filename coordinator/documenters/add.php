<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!is_array($_SESSION['user'] ?? null) || strtolower((string) ($_SESSION['user']['role'] ?? '')) !== 'coordinator') {
    redirectTo('coordinator/login.php');
}

$pageTitle = 'Add Documenter';
$role = 'coordinator';
$currentPage = 'documenters';
$userName = currentCoordinatorName();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = formatPersonName($_POST['first_name'] ?? '');
    $lastName = formatPersonName($_POST['last_name'] ?? '');
    $middleName = strtoupper(trim($_POST['middle_initial'] ?? ''));
    $qualification = trim($_POST['qualification'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $availability = $_POST['availability'] ?? 'available';

    if ($firstName === '' || $lastName === '') {
        $error = 'First name and last name are required.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        try {
            addDocumenterRecord([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'middle_name' => $middleName,
                'qualification' => $qualification,
                'email' => $email,
                'notes' => $notes,
                'availability' => $availability,
            ]);
            setFlash('success', 'Documenter added to the pool.');
            redirectTo('coordinator/documenters/manage.php');
        } catch (Throwable $e) {
            $error = 'Unable to add documenter. Please try again.';
        }
    }
}

$pageTitle = 'Add Documenter';
$role = 'coordinator';
$currentPage = 'documenters';
$userName = currentCoordinatorName();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Add Documenter</h2>
    <p>Add a new documenter to the pool.</p>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= url('coordinator/documenters/add.php') ?>" class="card" data-validate>
    <div class="card-header"><h3>Documenter Information</h3></div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-field">
                <label>First Name <span class="required">*</span></label>
                <input type="text" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label>Middle Initial</label>
                <input type="text" name="middle_initial" maxlength="2" style="text-transform:uppercase;" value="<?= htmlspecialchars($_POST['middle_initial'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label>Last Name <span class="required">*</span></label>
                <input type="text" name="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
            </div>
        </div>
        <div class="form-field">
            <label>Qualification</label>
            <input type="text" name="qualification" placeholder="e.g. PhD, MSc, etc." value="<?= htmlspecialchars($_POST['qualification'] ?? '') ?>">
        </div>
        <div class="form-field">
            <label>Email</label>
            <input type="email" name="email" placeholder="documenter@adzu.edu.ph" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-field">
            <label>Notes</label>
            <textarea name="notes" placeholder="Any additional notes..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
        </div>
        <div class="form-field">
            <label>Availability</label>
            <select name="availability">
                <option value="available" <?= ($_POST['availability'] ?? 'available') === 'available' ? 'selected' : '' ?>>Available</option>
                <option value="unavailable" <?= ($_POST['availability'] ?? '') === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
            </select>
        </div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Documenter</button>
        <a href="<?= url('coordinator/documenters/manage.php') ?>" class="btn btn-outline">Cancel</a>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>