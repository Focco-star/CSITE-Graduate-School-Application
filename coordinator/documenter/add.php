<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Add Documenter';
$role = 'coordinator';
$currentPage = 'documenters';
$userName = currentCoordinatorName();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = formatPersonName($_POST['first_name'] ?? '');
    $middleName = formatPersonName($_POST['middle_name'] ?? '');
    $lastName = formatPersonName($_POST['last_name'] ?? '');
    $qualification = trim((string) ($_POST['qualification'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    if ($firstName === '' || $lastName === '' || $qualification === '') {
        $error = 'First name, last name, and qualification are required.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        try {
            $stmt = DB::getConnection()->prepare(
                'INSERT INTO documenter_pool
                    (first_name, middle_name, last_name, qualification, email, availability, notes)
                 VALUES (:first_name, :middle_name, :last_name, :qualification, :email, :availability, :notes)'
            );
            $stmt->execute([
                'first_name' => $firstName,
                'middle_name' => $middleName !== '' ? $middleName : null,
                'last_name' => $lastName,
                'qualification' => $qualification,
                'email' => $email !== '' ? $email : null,
                'availability' => ($_POST['availability'] ?? 'available') === 'unavailable' ? 'unavailable' : 'available',
                'notes' => trim((string) ($_POST['notes'] ?? '')) ?: null,
            ]);
            setFlash('success', 'Documenter added successfully.');
            redirectTo('coordinator/documenter/documenter.php');
        } catch (Throwable $e) {
            error_log('[documenter pool] ' . $e->getMessage());
            $error = 'The documenter could not be saved. Check the database migration and try again.';
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Add Documenter</h2>
    <p>Add a documenter to the academic assessment team.</p>
</div>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<form class="card" method="post" action="<?= url('coordinator/documenter/add.php') ?>" data-validate>
    <div class="card-body">
        <div class="form-row">
            <div class="form-field">
                <label>First Name <span class="required">*</span></label>
                <input type="text" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label>Middle Name</label>
                <input type="text" name="middle_name" value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label>Last Name <span class="required">*</span></label>
                <input type="text" name="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
            </div>
        </div>
        <div class="form-field">
            <label>Qualification / Degree <span class="required">*</span></label>
            <input type="text" name="qualification" required value="<?= htmlspecialchars($_POST['qualification'] ?? '') ?>" placeholder="e.g., MS in Information Technology">
        </div>
        <div class="form-row">
            <div class="form-field">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="name@adzu.edu.ph">
            </div>
            <div class="form-field">
                <label>Availability</label>
                <select name="availability">
                    <option value="available">Available</option>
                    <option value="unavailable" <?= ($_POST['availability'] ?? '') === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
                </select>
            </div>
        </div>
        <div class="form-field">
            <label>Notes</label>
            <textarea name="notes" placeholder="Expertise or availability notes"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Documenter</button>
            <a href="<?= url('coordinator/documenter/documenter.php') ?>" class="btn btn-outline">Cancel</a>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>