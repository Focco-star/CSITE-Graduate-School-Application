<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Edit Adviser';
$role = 'coordinator';
$currentPage = 'advisers';
$userName = currentCoordinatorName();
$error = '';
$adviser = null;
$legacySingleName = false;

try {
    $pdo = DB::getConnection();
    $stmt = $pdo->prepare('SELECT * FROM advisor_pool WHERE adviser_id = :id LIMIT 1');
    $stmt->execute(['id' => (int) ($_GET['id'] ?? 0)]);
    $adviser = $stmt->fetch() ?: null;
    if ($adviser) {
        $nameParts = array_map('trim', explode(',', (string) $adviser['name'], 2));
        if (count($nameParts) === 2) {
            $adviser['last_name'] = $nameParts[0];
            $givenParts = preg_split('/\s+/', $nameParts[1]) ?: [];
            $adviser['first_name'] = array_shift($givenParts) ?? '';
            $adviser['middle_name'] = implode(' ', $givenParts);
        } else {
            $nameParts = preg_split('/\s+/', trim((string) $adviser['name'])) ?: [];
            $adviser['first_name'] = count($nameParts) > 1 ? (array_shift($nameParts) ?? '') : '';
            $adviser['last_name'] = implode(' ', $nameParts);
            if ($adviser['last_name'] === '') {
                $adviser['first_name'] = trim((string) $adviser['name']);
                $legacySingleName = true;
            }
            $adviser['middle_name'] = '';
        }
    }
} catch (Throwable $e) {
    error_log('[adviser pool] ' . $e->getMessage());
    $error = 'The adviser record could not be loaded.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $adviser) {
    $firstName = formatPersonName($_POST['first_name'] ?? '');
    $middleName = formatPersonName($_POST['middle_name'] ?? '');
    $lastName = formatPersonName($_POST['last_name'] ?? '');
    $name = $lastName !== ''
        ? $lastName . ', ' . $firstName . ($middleName !== '' ? ' ' . $middleName : '')
        : $firstName;
    $qualification = trim((string) ($_POST['qualification'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    if ($firstName === '' || (!$legacySingleName && $lastName === '') || $qualification === '') {
        $error = 'First name, last name, and qualification are required.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        try {
            $update = $pdo->prepare(
                'UPDATE advisor_pool
                 SET name = :name, qualification = :qualification, email = :email,
                     availability = :availability, notes = :notes
                 WHERE adviser_id = :id'
            );
            $update->execute([
                'name' => $name,
                'qualification' => $qualification,
                'email' => $email !== '' ? $email : null,
                'availability' => ($_POST['availability'] ?? 'available') === 'unavailable' ? 'unavailable' : 'available',
                'notes' => trim((string) ($_POST['notes'] ?? '')) ?: null,
                'id' => (int) $adviser['adviser_id'],
            ]);
            setFlash('success', 'Adviser information updated.');
            redirectTo('coordinator/advisers/manage.php');
        } catch (Throwable $e) {
            error_log('[adviser pool] ' . $e->getMessage());
            $error = 'The adviser could not be updated. Please try again.';
        }
    }
    $adviser = array_merge($adviser, [
        'name' => $name,
        'first_name' => $firstName,
        'middle_name' => $middleName,
        'last_name' => $lastName,
        'qualification' => $qualification,
        'email' => $email,
        'availability' => $_POST['availability'] ?? $adviser['availability'],
        'notes' => $_POST['notes'] ?? $adviser['notes'],
    ]);
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Edit Adviser</h2>
    <p>Update adviser information and availability.</p>
</div>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if (!$adviser): ?>
<div class="alert alert-warning">Adviser not found.</div>
<a class="btn btn-outline" href="<?= url('coordinator/advisers/manage.php') ?>">Back to Pool of Advisers</a>
<?php else: ?>
<form class="card" method="post" action="<?= url('coordinator/advisers/edit.php?id=' . (int) $adviser['adviser_id']) ?>" data-validate>
    <div class="card-body">
        <div class="form-row">
            <div class="form-field">
                <label>First Name <span class="required">*</span></label>
                <input type="text" name="first_name" required value="<?= htmlspecialchars($adviser['first_name']) ?>">
            </div>
            <div class="form-field">
                <label>Middle Name</label>
                <input type="text" name="middle_name" value="<?= htmlspecialchars($adviser['middle_name'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label>Last Name <?= $legacySingleName ? '' : '<span class="required">*</span>' ?></label>
                <input type="text" name="last_name" <?= $legacySingleName ? '' : 'required' ?> value="<?= htmlspecialchars($adviser['last_name']) ?>">
            </div>
        </div>
        <div class="form-field">
            <label>Qualification / Degree <span class="required">*</span></label>
            <input type="text" name="qualification" required value="<?= htmlspecialchars($adviser['qualification']) ?>">
        </div>
        <div class="form-row">
            <div class="form-field">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($adviser['email'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label>Availability</label>
                <select name="availability">
                    <option value="available" <?= ($adviser['availability'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="unavailable" <?= ($adviser['availability'] ?? '') === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
                </select>
            </div>
        </div>
        <div class="form-field">
            <label>Notes</label>
            <textarea name="notes"><?= htmlspecialchars($adviser['notes'] ?? '') ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            <a href="<?= url('coordinator/advisers/manage.php') ?>" class="btn btn-outline">Cancel</a>
        </div>
    </div>
</form>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
