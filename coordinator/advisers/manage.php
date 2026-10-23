<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Pool of Advisers';
$role = 'coordinator';
$currentPage = 'advisers';
$userName = currentCoordinatorName();
$error = '';
$advisers = [];

try {
    $pdo = DB::getConnection();
    if (isset($_GET['delete']) && ctype_digit((string) $_GET['delete'])) {
        $assigned = $pdo->prepare('SELECT COUNT(*) FROM schedules WHERE adviser_id = :id');
        $assigned->execute(['id' => (int) $_GET['delete']]);
        if ((int) $assigned->fetchColumn() > 0) {
            setFlash('error', 'This adviser is assigned to existing schedules. Mark the adviser unavailable instead of removing them.');
            redirectTo('coordinator/advisers/manage.php');
        }
        $delete = $pdo->prepare('DELETE FROM advisor_pool WHERE adviser_id = :id');
        $delete->execute(['id' => (int) $_GET['delete']]);
        setFlash('success', 'Adviser removed from the pool.');
        redirectTo('coordinator/advisers/manage.php');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_avail'])) {
        $toggle = $pdo->prepare(
            "UPDATE advisor_pool
             SET availability = IF(availability = 'available', 'unavailable', 'available')
             WHERE adviser_id = :id"
        );
        $toggle->execute(['id' => (int) $_POST['toggle_avail']]);
        setFlash('success', 'Adviser availability updated.');
        redirectTo('coordinator/advisers/manage.php');
    }
    $advisers = $pdo->query(
        'SELECT a.*, COUNT(s.schedule_id) AS adviser_sessions
         FROM advisor_pool a
         LEFT JOIN schedules s ON s.adviser_id = a.adviser_id
         GROUP BY a.adviser_id
         ORDER BY a.availability = "available" DESC, a.name ASC'
    )->fetchAll();
} catch (Throwable $e) {
    error_log('[adviser pool] ' . $e->getMessage());
    $error = 'The adviser pool could not be loaded. Check that the database migrations are applied and try again.';
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Pool of Advisers</h2>
    <p>Manage adviser qualifications, assigned presentation schedules, and availability.</p>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-actions" style="border:none;padding:0;margin-bottom:1.5rem;">
    <a href="<?= url('coordinator/advisers/add.php') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Add Adviser</a>
</div>

<div class="card">
    <div class="card-header"><h3>Advisers <span style="font-size:0.8rem;font-weight:400;color:var(--gray-500);">(<?= count($advisers) ?>)</span></h3></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Name</th><th>Qualification</th><th style="text-align:center;">Adviser Sessions</th><th>Availability</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (!$advisers): ?>
                    <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:2rem;">No advisers in the pool yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($advisers as $adviser): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($adviser['name']) ?></strong>
                            <?php if (!empty($adviser['email'])): ?>
                            <div style="font-size:0.75rem;color:var(--gray-400);"><?= htmlspecialchars($adviser['email']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:0.83rem;"><?= htmlspecialchars($adviser['qualification']) ?></td>
                        <td style="text-align:center;font-weight:600;color:var(--adzu-navy);"><?= (int) $adviser['adviser_sessions'] ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="toggle_avail" value="<?= (int) $adviser['adviser_id'] ?>">
                                <button type="submit" class="btn btn-sm <?= $adviser['availability'] === 'available' ? 'btn-primary' : 'btn-outline' ?>" style="font-size:0.75rem;padding:3px 10px;">
                                    <?php if ($adviser['availability'] === 'available'): ?>
                                    <i class="fas fa-check-circle"></i> Available
                                    <?php else: ?>
                                    <i class="fas fa-ban"></i> Unavailable
                                    <?php endif; ?>
                                </button>
                            </form>
                        </td>
                        <td class="actions">
                            <a href="<?= url('coordinator/advisers/edit.php?id=' . (int) $adviser['adviser_id']) ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i> Edit</a>
                            <?= coordDeleteLink(url('coordinator/advisers/manage.php?delete=' . (int) $adviser['adviser_id']), 'Remove this adviser from the pool?') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
