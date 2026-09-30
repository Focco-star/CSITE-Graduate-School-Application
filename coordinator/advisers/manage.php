<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Pool of Advisers';
$role = 'coordinator';
$currentPage = 'advisers';
$userName = currentCoordinatorName();
$error = '';

try {
    $pdo = DB::getConnection();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            $name = formatPersonName($_POST['name'] ?? '');
            $qualification = trim($_POST['qualification'] ?? '');
            $email = trim($_POST['email'] ?? '');
            if ($name === '' || $qualification === '') throw new RuntimeException('Name and qualification are required.');
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid adviser email address.');
            $stmt = $pdo->prepare('INSERT INTO advisor_pool (name, qualification, email, availability, notes) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $qualification, $email ?: null, ($_POST['availability'] ?? 'available') === 'unavailable' ? 'unavailable' : 'available', trim($_POST['notes'] ?? '') ?: null]);
            setFlash('success', 'Adviser added to the pool successfully.');
            redirectTo('coordinator/advisers/manage.php');
        }
        if ($action === 'toggle') {
            $stmt = $pdo->prepare("UPDATE advisor_pool SET availability = IF(availability = 'available', 'unavailable', 'available') WHERE adviser_id = ?");
            $stmt->execute([(int) ($_POST['adviser_id'] ?? 0)]);
            setFlash('success', 'Adviser availability updated.');
            redirectTo('coordinator/advisers/manage.php');
        }
    }
    $advisers = $pdo->query("SELECT * FROM advisor_pool ORDER BY availability = 'available' DESC, name ASC")->fetchAll();
} catch (Throwable $e) {
    $advisers = [];
    $error = 'The adviser pool is not ready yet. Import the included database migration, then reload this page.';
}
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><h2>Pool of Advisers</h2><p>Maintain qualified advisers who may be assigned to student applications and presentation schedules.</p></div>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card"><div class="card-header"><h3>Add Adviser</h3></div><form method="post" class="card-body" data-validate><input type="hidden" name="action" value="add"><div class="form-row"><div class="form-field"><label>Name <span class="required">*</span></label><input name="name" required placeholder="Full name"></div><div class="form-field"><label>Qualification <span class="required">*</span></label><input name="qualification" required placeholder="e.g., PhD in Computer Science"></div></div><div class="form-row"><div class="form-field"><label>Email</label><input type="email" name="email" placeholder="name@adzu.edu.ph"></div><div class="form-field"><label>Availability</label><select name="availability"><option value="available">Available</option><option value="unavailable">Unavailable</option></select></div></div><div class="form-field"><label>Notes</label><textarea name="notes" placeholder="Area of expertise or availability notes"></textarea></div><div class="form-actions"><button class="btn btn-primary"><i class="fas fa-plus"></i> Add Adviser</button></div></form></div>
<div class="card"><div class="card-header"><h3>Available Adviser Records</h3></div><div class="card-body"><div class="table-responsive"><table class="data-table"><thead><tr><th>Name</th><th>Qualification</th><th>Email</th><th>Availability</th><th>Action</th></tr></thead><tbody><?php if (!$advisers): ?><tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:2rem;">No advisers in the pool yet.</td></tr><?php endif; ?><?php foreach ($advisers as $adviser): ?><tr><td><strong><?= htmlspecialchars($adviser['name']) ?></strong></td><td><?= htmlspecialchars($adviser['qualification']) ?></td><td><?= htmlspecialchars($adviser['email'] ?: '-') ?></td><td><?= statusBadge($adviser['availability'] === 'available' ? 'confirmed' : 'pending') ?></td><td><form method="post"><input type="hidden" name="action" value="toggle"><input type="hidden" name="adviser_id" value="<?= (int) $adviser['adviser_id'] ?>"><button class="btn btn-sm btn-outline">Mark <?= $adviser['availability'] === 'available' ? 'unavailable' : 'available' ?></button></form></td></tr><?php endforeach; ?></tbody></table></div></div></div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
