<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Edit Documenter';
$role = 'coordinator';
$currentPage = 'documenters';
$userName = currentCoordinatorName();
$error = '';
$documenter = null;

try {
    $pdo = DB::getConnection();
    $stmt = $pdo->prepare('SELECT * FROM documenter_pool WHERE documenter_id = :id LIMIT 1');
    $stmt->execute(['id' => (int) ($_GET['id'] ?? 0)]);
    $documenter = $stmt->fetch() ?: null;
} catch (Throwable $e) {
    error_log('[documenter pool] ' . $e->getMessage());
    $error = 'The documenter record could not be loaded.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $documenter) {
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
            $update = $pdo->prepare(
                'UPDATE documenter_pool
                 SET first_name = :first_name, middle_name = :middle_name, last_name = :last_name,
                     qualification = :qualification, email = :email, availability = :availability, notes = :notes
                 WHERE documenter_id = :id'
            );
            $update->execute([
                'first_name' => $firstName,
                'middle_name' => $middleName !== '' ? $middleName : null,
                'last_name' => $lastName,
                'qualification' => $qualification,
                'email' => $email !== '' ? $email : null,
                'availability' => ($_POST['availability'] ?? 'available') === 'unavailable' ? 'unavailable' : 'available',
                'notes' => trim((string) ($_POST['notes'] ?? '')) ?: null,
                'id' => (int) $documenter['documenter_id'],
            ]);
            setFlash('success', 'Documenter information updated.');
            redirectTo('coordinator/documenter/documenter.php');
        } catch (Throwable $e) {
            error_log('[documenter pool] ' . $e->getMessage());
            $error = 'The documenter could not be updated. Please try again.';
        }
    }
    $documenter = array_merge($documenter, [
        'first_name' => $firstName,
        'middle_name' => $middleName,
        'last_name' => $lastName,
        'qualification' => $qualification,
        'email' => $email,
        'availability' => $_POST['availability'] ?? $documenter['availability'],
        'notes' => $_POST['notes'] ?? $documenter['notes'],
    ]);
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Edit Documenter</h2>
    <p>Update documenter information and availability.</p>
</div>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if (!$documenter): ?>
<div class="alert alert-warning">Documenter not found.</div>
<a class="btn btn-outline" href="<?= url('coordinator/documenter/documenter.php') ?>">Back to Documenter Pool</a>
<?php else: ?>
<form class="card" method="post" action="<?= url('coordinator/documenter/edit.php?id=' . (int) $documenter['documenter_id']) ?>" data-validate>
    <div class="card-body">
        <div class="form-row">
            <div class="form-field">
                <label>First Name <span class="required">*</span></label>
                <input type="text" name="first_name" required value="<?= htmlspecialchars($documenter['first_name']) ?>">
            </div>
            <div class="form-field">
                <label>Middle Name</label>
                <input type="text" name="middle_name" value="<?= htmlspecialchars($documenter['middle_name'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label>Last Name <span class="required">*</span></label>
                <input type="text" name="last_name" required value="<?= htmlspecialchars($documenter['last_name']) ?>">
            </div>
        </div>
        <div class="form-field">
            <label>Qualification / Degree <span class="required">*</span></label>
            <input type="text" name="qualification" required value="<?= htmlspecialchars($documenter['qualification']) ?>">
        </div>
        <div class="form-row">
            <div class="form-field">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($documenter['email'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label>Availability</label>
                <select name="availability">
                    <option value="available" <?= $documenter['availability'] === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="unavailable" <?= $documenter['availability'] === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
                </select>
            </div>
        </div>
        <div class="form-field">
            <label>Notes</label>
            <textarea name="notes"><?= htmlspecialchars($documenter['notes'] ?? '') ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            <a href="<?= url('coordinator/documenter/documenter.php') ?>" class="btn btn-outline">Cancel</a>
        </div>
    </div>
</form>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
