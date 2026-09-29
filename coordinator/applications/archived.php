<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Archived Applications';
$role = 'coordinator';
$currentPage = 'archived_applications';
$userName = currentCoordinatorName();
$error = '';
$applications = [];

try {
    $pdo = DB::getConnection();

    // Restore
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_id'])) {
        $stmt = $pdo->prepare('UPDATE applications SET archived_at = NULL WHERE application_id = :id AND archived_at IS NOT NULL');
        $stmt->execute(['id' => (int) $_POST['restore_id']]);
        setFlash('success', 'The application has been restored to the active application list.');
        redirectTo('coordinator/applications/archived.php');
    }

    // Permanent delete (archived applications only)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
        $deleteId = (int) $_POST['delete_id'];
        $pdo->beginTransaction();
        try {
            $check = $pdo->prepare('SELECT application_id FROM applications WHERE application_id = :id AND archived_at IS NOT NULL');
            $check->execute(['id' => $deleteId]);

            if ($check->fetchColumn()) {
                $docStmt = $pdo->prepare('SELECT stored_name FROM application_documents WHERE application_id = :id');
                $docStmt->execute(['id' => $deleteId]);
                $storedFiles = $docStmt->fetchAll(PDO::FETCH_COLUMN);

                $pdo->prepare('DELETE FROM application_documents WHERE application_id = :id')->execute(['id' => $deleteId]);
                $pdo->prepare('DELETE FROM applications WHERE application_id = :id AND archived_at IS NOT NULL')->execute(['id' => $deleteId]);
                $pdo->commit();

                if (defined('UPLOAD_DIR')) {
                    foreach ($storedFiles as $file) {
                        $path = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . basename($file);
                        if (is_file($path)) @unlink($path);
                    }
                }
                setFlash('success', 'The application has been permanently deleted.');
            } else {
                $pdo->rollBack();
                setFlash('error', 'The application could not be found or is not archived.');
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
        redirectTo('coordinator/applications/archived.php');
    }

    $stmt = $pdo->query(
        "SELECT
            a.application_id AS id,
            COALESCE(
                NULLIF(TRIM(CONCAT_WS(' ', s.first_name, NULLIF(TRIM(REPLACE(s.middle_initial, '.', '')), ''), s.last_name)), ''), 
                u.full_name
            ) AS student,
            CASE 
                WHEN s.program LIKE '%Computer Science%' THEN 'MSCS'
                WHEN s.program LIKE '%Information Technology%' THEN 'MIT'
                WHEN s.program LIKE '%Mathematics%' THEN 'MATH'
                ELSE s.program 
            END AS program,
            s.track AS db_track,
            a.presentation_stage AS stage,
            a.paper_title AS title,
            a.submitted_at AS date,
            a.status,
            a.archived_at
        FROM applications a
        JOIN students s ON a.student_id = s.student_id
        LEFT JOIN users u ON s.user_id = u.user_id
        WHERE a.archived_at IS NOT NULL
        ORDER BY a.archived_at DESC"
    );
    $applications = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = 'Archived application records are unavailable. Confirm that the application archive database migration has been applied.';
}

require_once __DIR__ . '/../../includes/header.php';
?>
<style>
.confirm-overlay{position:fixed;inset:0;background:rgba(17,24,39,.5);display:none;align-items:center;justify-content:center;z-index:2000;padding:1rem;}
.confirm-overlay.open{display:flex;}
.confirm-modal{background:#fff;border:1px solid #d1d5db;border-radius:6px;width:100%;max-width:540px;box-shadow:0 10px 30px rgba(0,0,0,.18);display:flex;flex-direction:column;max-height:90vh;overflow:hidden;}
.confirm-header{display:flex;align-items:center;gap:.75rem;padding:1.1rem 1.5rem;border-bottom:1px solid #e5e7eb;}
.confirm-icon{width:auto;height:auto;background:none;color:#4b5563;font-size:1.05rem;flex-shrink:0;}
.confirm-header h3{margin:0;font-size:1.05rem;font-weight:600;color:#111827;}
.confirm-body{padding:1.25rem 1.5rem;overflow-y:auto;}
.confirm-intro{margin:0 0 1rem;color:#374151;font-size:.92rem;line-height:1.55;}
.confirm-details{width:100%;border-collapse:collapse;font-size:.88rem;border:1px solid #e5e7eb;}
.confirm-details tr+tr{border-top:1px solid #e5e7eb;}
.confirm-details th{text-align:left;width:30%;padding:.55rem .85rem;background:#f9fafb;color:#4b5563;font-weight:600;vertical-align:top;}
.confirm-details td{padding:.55rem .85rem;color:#111827;word-break:break-word;}
.confirm-notice{margin-top:1rem;padding:.7rem .9rem;background:#f9fafb;border:1px solid #e5e7eb;border-radius:4px;color:#374151;font-size:.85rem;line-height:1.5;}
.confirm-footer{display:flex;justify-content:space-between;align-items:center;padding:.9rem 1.5rem;border-top:1px solid #e5e7eb;background:#fff;}

/* Same neutral confirm button for both Restore and Delete */
.confirm-submit{background:#1f2937;border:1px solid #1f2937;color:#fff;}
.confirm-submit:hover{background:#111827;border-color:#111827;}
</style>

<div class="page-header"><h2>Archived Applications</h2><p>View retained application records, restore an application when it becomes active again, or permanently delete it.</p></div>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card"><div class="card-header"><h3>Archived Application Records <span class="filter-count" data-filter-count></span></h3></div><div class="card-body">
    <div class="filter-bar" data-filter-table="#archivedApplicationsTable"><div class="filter-search"><i class="fas fa-search"></i><input type="search" data-filter-q placeholder="Search archived applications..."></div><button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button></div>
    <div class="table-responsive"><table class="data-table" id="archivedApplicationsTable"><thead><tr><th>Student</th><th>Program</th><th>Track</th><th>Stage</th><th>Title</th><th>Submitted</th><th>Archived</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($applications as $app):
        $rawTrack = $app['db_track'] ?? (function_exists('getTrackForProgram') ? getTrackForProgram($app['program']) : 'thesis');
        $track = function_exists('getTrackLabel') ? getTrackLabel($rawTrack) : ucfirst($rawTrack);
        $submittedFmt = date('M d, Y', strtotime($app['date']));
        $archivedFmt = date('M d, Y g:i A', strtotime($app['archived_at']));
        $dataAttrs =
            'data-id="' . (int) $app['id'] . '"' .
            ' data-student="' . htmlspecialchars($app['student']) . '"' .
            ' data-program="' . htmlspecialchars($app['program']) . '"' .
            ' data-track="' . htmlspecialchars($track) . '"' .
            ' data-stage="' . htmlspecialchars($app['stage']) . '"' .
            ' data-title="' . htmlspecialchars($app['title']) . '"' .
            ' data-status="' . htmlspecialchars(ucwords(str_replace('_', ' ', (string) $app['status']))) . '"' .
            ' data-submitted="' . htmlspecialchars($submittedFmt) . '"' .
            ' data-archived="' . htmlspecialchars($archivedFmt) . '"';
    ?>
    <tr data-search="<?= htmlspecialchars(strtolower($app['student'] . ' ' . $app['program'] . ' ' . $track . ' ' . $app['stage'] . ' ' . $app['title'])) ?>"><td><strong><?= htmlspecialchars($app['student']) ?></strong></td><td><?= htmlspecialchars($app['program']) ?></td><td><span style="font-size:0.75rem;background:var(--gray-100);padding:2px 8px;border-radius:99px;"><?= htmlspecialchars($track) ?></span></td><td><?= htmlspecialchars($app['stage']) ?></td><td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($app['title']) ?></td><td><?= htmlspecialchars($submittedFmt) ?></td><td><?= htmlspecialchars($archivedFmt) ?></td><td class="actions">
        <a href="<?= url('coordinator/applications/view.php?id=' . (int) $app['id']) ?>" class="btn btn-sm btn-outline" title="View"><i class="fas fa-eye"></i></a>
        <button type="button" class="btn btn-sm btn-primary" data-confirm-action="restore" <?= $dataAttrs ?>><i class="fas fa-undo"></i> Restore</button>
        <button type="button" class="btn btn-sm btn-danger" data-confirm-action="delete" <?= $dataAttrs ?>><i class="fas fa-trash"></i> Delete</button>
    </td></tr><?php endforeach; ?>
    <tr data-filter-empty <?= $applications ? 'hidden' : '' ?>><td colspan="8" style="text-align:center;color:var(--gray-400);padding:2rem;">No archived applications found.</td></tr>
    </tbody></table></div>
</div></div>

<!-- Shared confirmation modal (Restore / Delete) -->
<div class="confirm-overlay" id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
    <form method="post" class="confirm-modal" id="confirmForm">
        <input type="hidden" name="" id="confirmId" value="">
        <div class="confirm-header">
            <div class="confirm-icon"><i class="fas" id="confirmIcon"></i></div>
            <h3 id="confirmTitle"></h3>
        </div>
        <div class="confirm-body">
            <p class="confirm-intro" id="confirmIntro"></p>
            <table class="confirm-details">
                <tr><th>Student</th><td id="cfStudent"></td></tr>
                <tr><th>Program</th><td id="cfProgram"></td></tr>
                <tr><th>Track</th><td id="cfTrack"></td></tr>
                <tr><th>Stage</th><td id="cfStage"></td></tr>
                <tr><th>Title</th><td id="cfTitle"></td></tr>
                <tr><th>Status</th><td id="cfStatus"></td></tr>
                <tr><th>Submitted</th><td id="cfSubmitted"></td></tr>
                <tr><th>Archived</th><td id="cfArchived"></td></tr>
            </table>
            <div class="confirm-notice" id="confirmNotice"></div>
        </div>
        <div class="confirm-footer">
            <button type="button" class="btn btn-outline" id="confirmCancel">Go Back</button>
            <button type="submit" class="btn confirm-submit" id="confirmSubmit"></button>
        </div>
    </form>
</div>

<script>
(function () {
    var modal = document.getElementById('confirmModal');
    var idInput = document.getElementById('confirmId');

    var config = {
        restore: {
            field: 'restore_id',
            icon: 'fa-undo',
            title: 'Restore Application',
            intro: 'You are about to restore the following application to the active application list. Please review the details below before proceeding.',
            notice: 'Once restored, the application will reappear in the active list together with its current status and submitted documents.',
            submit: 'Restore Application'
        },
        delete: {
            field: 'delete_id',
            icon: 'fa-trash',
            title: 'Delete Application',
            intro: 'You are about to permanently delete the following application. Please review the details below before proceeding.',
            notice: 'This action is irreversible. The application record and all of its uploaded documents will be permanently removed.',
            submit: 'Delete Application'
        }
    };

    var fields = {
        student: 'cfStudent', program: 'cfProgram', track: 'cfTrack', stage: 'cfStage',
        title: 'cfTitle', status: 'cfStatus', submitted: 'cfSubmitted', archived: 'cfArchived'
    };

    function openModal(btn) {
        var c = config[btn.dataset.confirmAction];
        if (!c) return;
        idInput.name = c.field;
        idInput.value = btn.dataset.id;
        document.getElementById('confirmIcon').className = 'fas ' + c.icon;
        document.getElementById('confirmTitle').textContent = c.title;
        document.getElementById('confirmIntro').textContent = c.intro;
        document.getElementById('confirmNotice').textContent = c.notice;
        document.getElementById('confirmSubmit').textContent = c.submit;
        Object.keys(fields).forEach(function (k) {
            document.getElementById(fields[k]).textContent = btn.dataset[k] || '';
        });
        modal.classList.add('open');
    }

    function closeModal() {
        modal.classList.remove('open');
        idInput.name = '';
        idInput.value = '';
    }

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-confirm-action]');
        if (trigger) openModal(trigger);
    });
    document.getElementById('confirmCancel').addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
})();
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>