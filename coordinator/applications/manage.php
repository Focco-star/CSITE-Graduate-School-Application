<?php
require_once __DIR__ . '/../../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle   = 'Manage Applications';
$role        = 'coordinator';
$currentPage = 'applications';
$userName    = !empty($_SESSION['user']['full_name']) ? $_SESSION['user']['full_name'] : ($mockCoordinator['name'] ?? 'Coordinator');


if (!isset($pdo) || !($pdo instanceof PDO)) {
    try {
        $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
        $dbName = defined('DB_NAME') ? DB_NAME : 'csite_grad_school';
        $dbUser = defined('DB_USER') ? DB_USER : 'root';
        $dbPass = defined('DB_PASS') ? DB_PASS : '';
        $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        $pdo = null;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_id'])) {
    $archiveId = (int) $_POST['archive_id'];
    if ($pdo && $archiveId > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE applications SET archived_at = NOW() WHERE application_id = :id AND archived_at IS NULL");
            $stmt->execute([':id' => $archiveId]);
            if ($stmt->rowCount() > 0) {
                setFlash('success', 'Application archived successfully. The record is retained for reporting.');
            } else {
                setFlash('error', 'The application could not be archived. It may already be archived.');
            }
        } catch (PDOException $e) {
            setFlash('error', 'The application could not be archived. Apply the database migration and try again.');
        }
    } elseif (function_exists('archiveApplicationRecord')) {
        archiveApplicationRecord($archiveId);
        setFlash('success', 'Application archived successfully.');
    }
    redirectTo('coordinator/applications/manage.php');
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_id'])) {
    $restoreId = (int) $_POST['restore_id'];
    if ($pdo && $restoreId > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE applications SET archived_at = NULL WHERE application_id = :id AND archived_at IS NOT NULL");
            $stmt->execute([':id' => $restoreId]);
            setFlash('success', 'Application restored to the active application list.');
        } catch (PDOException $e) {
            setFlash('error', 'The application could not be restored. Try again.');
        }
    }
    redirectTo('coordinator/applications/manage.php');
}



if (($_GET['delete'] ?? '') !== '') {
    setFlash('error', 'Applications can no longer be deleted from the website. Use Archive instead.');
    redirectTo('coordinator/applications/manage.php');
}




$applications = [];

if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
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
                a.status
            FROM applications a
            JOIN students s ON a.student_id = s.student_id
            LEFT JOIN users u ON s.user_id = u.user_id
            WHERE a.archived_at IS NULL
              AND s.archived_at IS NULL
            ORDER BY a.submitted_at DESC
        ");
        $applications = $stmt->fetchAll();
    } catch (PDOException $e) {
        $applications = [];
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Application Management</h2>
    <p>View and process submitted student applications by track and program.</p>
</div>
<?php
    $archivedAppCount = 0;
    if ($pdo) {
        try {
            $archivedAppCount = (int) $pdo->query('SELECT COUNT(*) FROM applications WHERE archived_at IS NOT NULL')->fetchColumn();
        } catch (PDOException $e) {
            $archivedAppCount = 0;
        }
    }
?>
<?php if ($archivedAppCount > 0): ?>
<div class="alert alert-info" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
    <div><i class="fas fa-box-archive"></i> <strong><?= $archivedAppCount ?></strong> archived application<?= $archivedAppCount === 1 ? '' : 's' ?> are retained for reporting.</div>
    <a href="<?= url('coordinator/applications/archived.php') ?>" class="btn btn-sm btn-outline"><i class="fas fa-box-archive"></i> View Archived Applications</a>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Submitted Applications <span class="filter-count" data-filter-count></span></h3>
    </div>
    <div class="card-body">
        <div class="filter-bar" data-filter-table="#appsTable">
            <div class="filter-search">
                <i class="fas fa-search"></i>
                <input type="search" data-filter-q placeholder="Search by name, title, program, stage, status...">
            </div>
            <button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button>
            <select data-filter="track">
                <option value="">All Tracks</option>
                <option>Thesis</option>
                <option>Capstone</option>
                <option>Seminar Paper</option>
            </select>
            <select data-filter="status">
                <option value="">All Statuses</option>
                <?php foreach (STATUSES as $key => $info): ?>
                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($info['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <select data-filter="program">
                <option value="">All Programs</option>
                <?php foreach (array_keys(PROGRAMS) as $code): ?>
                <option><?= htmlspecialchars($code) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="table-responsive">
            <table class="data-table" id="appsTable">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Program</th>
                        <th>Track</th>
                        <th>Stage</th>
                        <th>Title</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app):
                        $rawTrack = $app['db_track'] ?? (function_exists('getTrackForProgram') ? getTrackForProgram($app['program']) : 'thesis');
                        $track = function_exists('getTrackLabel') ? getTrackLabel($rawTrack) : ucfirst($rawTrack);
                    ?>
                    <tr data-status="<?= htmlspecialchars($app['status']) ?>"
                        data-program="<?= htmlspecialchars($app['program']) ?>"
                        data-track="<?= htmlspecialchars($track) ?>"
                        data-search="<?= htmlspecialchars(strtolower($app['student'] . ' ' . $app['program'] . ' ' . $app['stage'] . ' ' . $app['title'] . ' ' . $app['status'] . ' ' . $track)) ?>">
                        <td><strong><?= htmlspecialchars($app['student']) ?></strong></td>
                        <td><?= htmlspecialchars($app['program']) ?></td>
                        <td><span style="font-size:0.75rem;background:var(--gray-100);padding:2px 8px;border-radius:99px;"><?= htmlspecialchars($track) ?></span></td>
                        <td><?= htmlspecialchars($app['stage']) ?></td>
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($app['title']) ?></td>
                        <td><?= date('M d, Y', strtotime($app['date'])) ?></td>
                        <td><?= statusBadge($app['status']) ?></td>
                        <td class="actions">
                            <a href="<?= url('coordinator/applications/view.php?id=' . $app['id']) ?>" class="btn btn-sm btn-outline" title="View"><i class="fas fa-eye"></i></a>
                            <a href="<?= url('coordinator/applications/process.php?id=' . $app['id']) ?>" class="btn btn-sm btn-primary" title="Process"><i class="fas fa-cog"></i></a>
                            <button type="button" class="btn btn-sm btn-danger" data-archive-open data-application-id="<?= (int) $app['id'] ?>" data-application-student="<?= htmlspecialchars($app['student']) ?>" data-application-title="<?= htmlspecialchars($app['title']) ?>" title="Archive"><i class="fas fa-box-archive"></i> Archive</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr data-filter-empty <?= $applications ? 'hidden' : '' ?>>
                        <td colspan="8" style="text-align:center;color:var(--gray-400);padding:2rem;">No applications match your search or filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="archiveApplicationModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="archiveApplicationTitle">
        <div class="modal-header"><h3 id="archiveApplicationTitle">Archive application?</h3><button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button></div>
        <div class="modal-body"><p>Archive the application for <strong id="archiveApplicationStudent"></strong>? The application will be removed from the active list, but its record and documents will be retained for reporting.</p><p style="margin-top:0.5rem;font-size:0.85rem;color:var(--gray-500);"><em id="archiveApplicationTitleText"></em></p></div>
        <form method="post" action="<?= url('coordinator/applications/manage.php') ?>" class="modal-footer">
            <input type="hidden" name="archive_id" id="archiveApplicationId">
            <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-danger"><i class="fas fa-box-archive"></i> Archive Application</button>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('[data-archive-open]').forEach(button => button.addEventListener('click', () => {
    document.getElementById('archiveApplicationId').value = button.dataset.applicationId;
    document.getElementById('archiveApplicationStudent').textContent = button.dataset.applicationStudent;
    document.getElementById('archiveApplicationTitleText').textContent = button.dataset.applicationTitle ? '\u201C' + button.dataset.applicationTitle + '\u201D' : '';
    document.getElementById('archiveApplicationModal').classList.add('active');
}));
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
