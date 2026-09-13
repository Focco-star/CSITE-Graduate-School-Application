<?php
require_once __DIR__ . '/../../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle   = 'Manage Applications';
$role        = 'coordinator';
$currentPage = 'applications';
$userName    = !empty($_SESSION['user']['full_name']) ? $_SESSION['user']['full_name'] : ($mockCoordinator['name'] ?? 'Coordinator');

// Ensure PDO database connection
if (!isset($pdo) || !($pdo instanceof PDO)) {
    try {
        $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
        $dbName = defined('DB_NAME') ? DB_NAME : 'websys';
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

// Handle application deletion
if (($_GET['delete'] ?? '') !== '') {
    $deleteId = (int) $_GET['delete'];
    if ($pdo && $deleteId > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM applications WHERE application_id = :id");
            $stmt->execute([':id' => $deleteId]);
        } catch (PDOException $e) {
            // Silence error
        }
    } elseif (function_exists('deleteApplicationRecord')) {
        deleteApplicationRecord($deleteId);
    }
    redirectTo('coordinator/applications/manage.php');
}

// Fetch applications directly from database
$applications = [];

if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                a.application_id AS id,
                COALESCE(
                    NULLIF(TRIM(CONCAT(s.first_name, ' ', IFNULL(s.middle_initial, ''), ' ', s.last_name)), ''), 
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
                            <?= function_exists('coordDeleteLink') ? coordDeleteLink(url('coordinator/applications/manage.php?delete=' . $app['id']), 'Delete this application? This cannot be undone.') : '' ?>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>