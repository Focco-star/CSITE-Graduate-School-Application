<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sessionUser = $_SESSION['user'] ?? [];
$userName    = !empty($sessionUser['full_name']) ? $sessionUser['full_name'] : ($mockCoordinator['name'] ?? 'Coordinator');
$pageTitle   = 'Dashboard';
$role        = 'coordinator';
$currentPage = 'dashboard';


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


$pendingAppsCount = 0;
$totalAppsCount   = 0;
$activeStudentsCount = 0;
$decidedAppsCount = 0;
$pendingApps = [];
$upcomingPresentations = [];

if (function_exists('databaseSchedules')) {
    try {
        $upcomingPresentations = databaseSchedules();
    } catch (Throwable $e) {
        $upcomingPresentations = [];
    }
}

// Merge database-backed scheduled applications (schedule/add.php records the
// presentation date/time/venue/panel in applications.workflow_state), so the
// card also works across sessions where the session-only schedule list is
// empty. Session rows win on duplicates (same application).
if ($pdo && class_exists('DB')) {
    try {
        $scheduledStmt = $pdo->prepare(
            "SELECT a.application_id AS applicationId,
                    COALESCE(
                        NULLIF(TRIM(CONCAT(s.last_name, ', ', s.first_name, IF(s.middle_initial IS NULL OR TRIM(REPLACE(s.middle_initial, '.', '')) = '', '', CONCAT(' ', TRIM(REPLACE(s.middle_initial, '.', '')))))), ''),
                        u.full_name
                    ) AS studentName,
                    u.email AS studentEmail,
                    a.presentation_stage AS stage,
                    a.workflow_state AS workflowState,
                    a.status
             FROM applications a
             JOIN users u ON u.user_id = a.user_id
             LEFT JOIN students s ON s.user_id = u.user_id
             WHERE a.archived_at IS NULL AND a.status = 'scheduled'"
        );
        $scheduledStmt->execute();
        $coveredAppIds = [];
        foreach ($upcomingPresentations as $existing) {
            $candidate = (int) ($existing['applicationId'] ?? $existing['application_id'] ?? 0);
            if ($candidate > 0) {
                $coveredAppIds[$candidate] = true;
            }
        }
        foreach ($scheduledStmt->fetchAll() as $row) {
            $appId = (int) ($row['applicationId'] ?? 0);
            if ($appId <= 0 || isset($coveredAppIds[$appId])) {
                continue;
            }
            $wf = json_decode((string) ($row['workflowState'] ?? ''), true) ?: [];
            if (empty($wf['presentation_date'])) {
                continue;
            }
            $upcomingPresentations[] = [
                'id' => 'db_' . $appId,
                'applicationId' => $appId,
                'studentEmail' => $row['studentEmail'] ?? '',
                'studentName' => $row['studentName'] ?? '',
                'stage' => $row['stage'] ?? '',
                'date' => (string) ($wf['presentation_date'] ?? ''),
                'time' => (string) ($wf['presentation_time'] ?? ''),
                'venue' => (string) ($wf['presentation_venue'] ?? ''),
                'panel' => (string) ($wf['presentation_panel'] ?? 'TBD'),
                'status' => $row['status'] ?? 'scheduled',
            ];
        }
    } catch (Throwable $e) {
    }
}

usort($upcomingPresentations, static function ($a, $b) {
    $ta = strtotime((string) ($a['date'] ?? ''));
    $tb = strtotime((string) ($b['date'] ?? ''));
    if ($ta && $tb) {
        return $ta <=> $tb;
    }
    return $ta ? -1 : ($tb ? 1 : 0);
});

if ($pdo) {
    try {

        $stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE archived_at IS NULL AND status IN ('submitted', 'under_review')");
        $pendingAppsCount = (int) $stmt->fetchColumn();


        $stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE archived_at IS NULL");
        $totalAppsCount = (int) $stmt->fetchColumn();


        $stmt = $pdo->query("SELECT COUNT(*) FROM students WHERE archived_at IS NULL");
        $activeStudentsCount = (int) $stmt->fetchColumn();


        $stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE archived_at IS NULL AND status IN ('approved', 'completed')");
        $decidedAppsCount = (int) $stmt->fetchColumn();


        $stmt = $pdo->prepare("
            SELECT 
                a.application_id AS id,
                COALESCE(
                    NULLIF(TRIM(CONCAT(s.last_name, ', ', s.first_name, IF(s.middle_initial IS NULL OR TRIM(REPLACE(s.middle_initial, '.', '')) = '', '', CONCAT(' ', TRIM(REPLACE(s.middle_initial, '.', '')))))), ''), 
                    u.full_name
                ) AS student,
                COALESCE(s.program, 'Graduate Program') AS program,
                a.presentation_stage AS stage,
                a.submitted_at AS date,
                a.status
            FROM applications a
            JOIN users u ON u.user_id = a.user_id
            LEFT JOIN students s ON s.user_id = u.user_id
            WHERE a.archived_at IS NULL
              AND a.status IN ('submitted', 'under_review')
            -- Alphabetical by last name, then first name ('Last, First MI' format).
            ORDER BY SUBSTRING_INDEX(u.full_name, ',', 1) ASC, SUBSTRING_INDEX(u.full_name, ',', -1) ASC, a.submitted_at DESC
        ");
        $stmt->execute();
        $pendingApps = $stmt->fetchAll();
    } catch (PDOException $e) {

    }
}




if (!$pdo && empty($pendingApps) && function_exists('storeGet')) {
    $pendingApps = array_values(array_filter(storeGet('applications') ?? [], static function ($a) {
        return empty($a['archivedAt'] ?? '') && in_array($a['status'] ?? '', ['submitted', 'under_review'], true);
    }));
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h2>Welcome, <?= htmlspecialchars($userName) ?>!</h2>
    <p>Overview of applications, submissions, and upcoming presentations requiring your attention.</p>
</div>

<div class="dashstat-grid theme-ateneo">
    <a href="<?= url('coordinator/applications/manage.php') ?>" class="dashstat dashstat-rose">
        <div class="dashstat-text">
            <div class="dashstat-value"><?= $pendingAppsCount ?></div>
            <div class="dashstat-label">Pending Applications</div>
            <div class="dashstat-link">Review now <i class="fas fa-arrow-right"></i></div>
        </div>
        <div class="dashstat-icon"><i class="fas fa-inbox"></i></div>
    </a>
    <a href="<?= url('coordinator/applications/manage.php') ?>" class="dashstat dashstat-amber">
        <div class="dashstat-text">
            <div class="dashstat-value"><?= $totalAppsCount ?></div>
            <div class="dashstat-label">Total Applications</div>
            <div class="dashstat-link">View all <i class="fas fa-arrow-right"></i></div>
        </div>
        <div class="dashstat-icon"><i class="fas fa-file-alt"></i></div>
    </a>
    <a href="<?= url('coordinator/schedule/manage.php') ?>" class="dashstat dashstat-blue">
        <div class="dashstat-text">
            <div class="dashstat-value"><?= count($upcomingPresentations) ?></div>
            <div class="dashstat-label">Upcoming Presentations</div>
            <div class="dashstat-link">See schedule <i class="fas fa-arrow-right"></i></div>
        </div>
        <div class="dashstat-icon"><i class="fas fa-calendar-alt"></i></div>
    </a>
    <a href="<?= url('coordinator/students/manage.php') ?>" class="dashstat dashstat-emerald">
        <div class="dashstat-text">
            <div class="dashstat-value"><?= $activeStudentsCount ?></div>
            <div class="dashstat-label">Active Students</div>
            <div class="dashstat-link">Manage <i class="fas fa-arrow-right"></i></div>
        </div>
        <div class="dashstat-icon"><i class="fas fa-user-graduate"></i></div>
    </a>
    <a href="<?= url('coordinator/reports/dashboard.php') ?>" class="dashstat dashstat-purple">
        <div class="dashstat-text">
            <div class="dashstat-value"><?= $decidedAppsCount ?></div>
            <div class="dashstat-label">Decided Outcomes</div>
            <div class="dashstat-link">Reports <i class="fas fa-arrow-right"></i></div>
        </div>
        <div class="dashstat-icon"><i class="fas fa-chart-bar"></i></div>
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-exclamation-circle"></i> Applications Requiring Attention</h3>
        <a href="<?= url('coordinator/applications/manage.php') ?>" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Student</th><th>Program</th><th>Stage</th><th>Submitted</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php if (!empty($pendingApps)): ?>
                        <?php foreach ($pendingApps as $app): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($app['student']) ?></strong></td>
                            <td><?= htmlspecialchars($app['program']) ?></td>
                            <td><?= htmlspecialchars($app['stage']) ?></td>
                            <td><?= date('M d, Y', strtotime($app['date'])) ?></td>
                            <td><?= statusBadge($app['status']) ?></td>
                            <td><a href="<?= url('coordinator/applications/view.php?id=' . $app['id']) ?>" class="btn btn-sm btn-primary">Review</a></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:1.5rem; color:var(--gray-400);">No applications requiring attention.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-calendar-check"></i> Upcoming Presentations</h3>
        <a href="<?= url('coordinator/schedule/manage.php') ?>" class="btn btn-sm btn-outline">Manage Schedules</a>
    </div>
    <div class="card-body">
        <?php if (!empty($upcomingPresentations)): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Student</th><th>Stage</th><th>Date</th><th>Time</th><th>Venue</th><th>Panel</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($upcomingPresentations as $up): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($up['studentName'] ?? $up['student'] ?? '') ?></strong></td>
                        <td><?= htmlspecialchars($up['stage'] ?? '') ?></td>
                        <td><?= htmlspecialchars($up['date'] ?? '') ?></td>
                        <td><?= htmlspecialchars($up['time'] ?? '') ?></td>
                        <td><?= htmlspecialchars($up['venue'] ?? '') ?></td>
                        <td><?= htmlspecialchars($up['panel'] ?? $up['panels'] ?? '') ?></td>
                        <td><?= statusBadge($up['status'] ?? 'scheduled') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 2rem; color: var(--gray-400); font-weight: 500;">
            No upcoming presentations.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
