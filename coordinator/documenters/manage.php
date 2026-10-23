<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Documenter Pool';
$role = 'coordinator';
$currentPage = 'documenters';
$userName = currentCoordinatorName();
$error = '';
$documenters = [];

try {
    $pdo = DB::getConnection();

    if (isset($_GET['delete']) && ctype_digit((string) $_GET['delete'])) {
        deleteDocumenterRecord((string) $_GET['delete']);
        setFlash('success', 'Documenter removed from the pool.');
        redirectTo('coordinator/documenters/manage.php');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_avail'])) {
        $doc = findDocumenter((string) $_POST['toggle_avail']);
        if ($doc) {
            $newAvailability = $doc['availability'] === 'available' ? 'unavailable' : 'available';
            $pdo = DB::getConnection();
            $toggle = $pdo->prepare(
                "UPDATE documenter_pool SET availability = :availability WHERE documenter_id = :id"
            );
            $toggle->execute(['id' => (int) $_POST['toggle_avail'], 'availability' => $newAvailability]);
            setFlash('success', 'Documenter availability updated.');
            redirectTo('coordinator/documenters/manage.php');
        }
    }

    $documenters = databaseDocumenters();
} catch (Throwable $e) {
    error_log('[documenter pool] ' . $e->getMessage());
    $error = 'The documenter pool could not be loaded. Check that the database migrations are applied and try again.';
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Documenter Pool</h2>
    <p>Manage documenters who record presentation proceedings, their qualifications, and availability.</p>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-actions" style="border:none;padding:0;margin-bottom:1.5rem;">
    <a href="<?= url('coordinator/documenters/add.php') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Add Documenter</a>
</div>

<div class="card">
    <div class="card-header"><h3>Documenters <span style="font-size:0.8rem;font-weight:400;color:var(--gray-500);">(<?= count($documenters) ?>)</span></h3></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Name</th><th>Qualification</th><th style="text-align:center;">Documenter Sessions</th><th>Availability</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (!$documenters): ?>
                    <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:2rem;">No documenters in the pool yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($documenters as $documenter): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($documenter['name']) ?></strong>
                            <?php if (!empty($documenter['email'])): ?>
                            <div style="font-size:0.75rem;color:var(--gray-400);"><?= htmlspecialchars($documenter['email']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:0.83rem;"><?= htmlspecialchars($documenter['qualification']) ?></td>
                        <td style="text-align:center;font-weight:600;color:var(--adzu-navy);"><?= (int) ($documenter['documenter_sessions'] ?? 0) ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="toggle_avail" value="<?= (int) $documenter['id'] ?>">
                                <button type="submit" class="btn btn-sm <?= $documenter['availability'] === 'available' ? 'btn-primary' : 'btn-outline' ?>" style="font-size:0.75rem;padding:3px 10px;">
                                    <?php if ($documenter['availability'] === 'available'): ?>
                                    <i class="fas fa-check-circle"></i> Available
                                    <?php else: ?>
                                    <i class="fas fa-ban"></i> Unavailable
                                    <?php endif; ?>
                                </button>
                            </form>
                        </td>
                        <td class="actions">
                            <a href="<?= url('coordinator/documenters/edit.php?id=' . (int) $documenter['id']) ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i> Edit</a>
                            <?= coordDeleteLink(url('coordinator/documenters/manage.php?delete=' . (int) $documenter['id']), 'Remove this documenter from the pool?') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>