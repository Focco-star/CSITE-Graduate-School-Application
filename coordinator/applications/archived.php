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
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_id'])) {
        $stmt = $pdo->prepare('UPDATE applications SET archived_at = NULL WHERE application_id = :id AND archived_at IS NOT NULL');
        $stmt->execute(['id' => (int) $_POST['restore_id']]);
        setFlash('success', 'Application restored to the active application list.');
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
<div class="page-header"><h2>Archived Applications</h2><p>View retained application records and restore an application when it becomes active again.</p></div>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card"><div class="card-header"><h3>Archived Application Records <span class="filter-count" data-filter-count></span></h3></div><div class="card-body">
    <div class="filter-bar" data-filter-table="#archivedApplicationsTable"><div class="filter-search"><i class="fas fa-search"></i><input type="search" data-filter-q placeholder="Search archived applications..."></div><button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button></div>
    <div class="table-responsive"><table class="data-table" id="archivedApplicationsTable"><thead><tr><th>Student</th><th>Program</th><th>Track</th><th>Stage</th><th>Title</th><th>Submitted</th><th>Archived</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($applications as $app):
        $rawTrack = $app['db_track'] ?? (function_exists('getTrackForProgram') ? getTrackForProgram($app['program']) : 'thesis');
        $track = function_exists('getTrackLabel') ? getTrackLabel($rawTrack) : ucfirst($rawTrack);
    ?>
    <tr data-search="<?= htmlspecialchars(strtolower($app['student'] . ' ' . $app['program'] . ' ' . $track . ' ' . $app['stage'] . ' ' . $app['title'])) ?>"><td><strong><?= htmlspecialchars($app['student']) ?></strong></td><td><?= htmlspecialchars($app['program']) ?></td><td><span style="font-size:0.75rem;background:var(--gray-100);padding:2px 8px;border-radius:99px;"><?= htmlspecialchars($track) ?></span></td><td><?= htmlspecialchars($app['stage']) ?></td><td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($app['title']) ?></td><td><?= htmlspecialchars(date('M d, Y', strtotime($app['date']))) ?></td><td><?= htmlspecialchars(date('M d, Y g:i A', strtotime($app['archived_at']))) ?></td><td class="actions">
        <a href="<?= url('coordinator/applications/view.php?id=' . (int) $app['id']) ?>" class="btn btn-sm btn-outline" title="View"><i class="fas fa-eye"></i></a>
        <form method="post" style="display:inline;" data-confirm-form data-confirm-message="Restore this application to the active application list?"><input type="hidden" name="restore_id" value="<?= (int) $app['id'] ?>"><button type="button" class="btn btn-sm btn-primary" data-confirm-trigger><i class="fas fa-undo"></i> Restore</button></form>
    </td></tr><?php endforeach; ?>
    <tr data-filter-empty <?= $applications ? 'hidden' : '' ?>><td colspan="8" style="text-align:center;color:var(--gray-400);padding:2rem;">No archived applications found.</td></tr>
    </tbody></table></div>
</div></div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
