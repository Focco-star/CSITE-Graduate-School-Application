<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/calendar.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle   = 'Presentation Schedules';
$role        = 'coordinator';
$currentPage = 'schedule';
$userName    = $mockCoordinator['name'];

if (isset($_GET['delete']) && $_GET['delete'] !== '') {
    try {
        deleteScheduleRecord((string) $_GET['delete']);
        setFlash('success', 'Presentation schedule removed from the list and calendar.');
    } catch (Throwable $e) {
        error_log('[schedule delete] ' . $e->getMessage());
        setFlash('error', 'The presentation schedule could not be removed. Please try again.');
    }
    redirectTo('coordinator/schedule/manage.php');
}

$schedules = databaseSchedules();

// ---------------------------------------------------------------------------
// Upcoming presentations (moved here from coordinator/dashboard.php)
// ---------------------------------------------------------------------------
if (!isset($pdo) || !($pdo instanceof PDO)) {
    try {
        $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
        $dbName = defined('DB_NAME') ? DB_NAME : 'csite_grad_school';
        $dbUser = defined('DB_USER') ? DB_USER : 'root';
        $dbPass = defined('DB_PASS') ? DB_PASS : '';
        $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        $pdo = null;
    }
}

$upcomingPresentations = is_array($schedules) ? $schedules : [];

// Merge database-backed scheduled applications (schedule/add.php records the
// presentation date/time/venue/panel in applications.workflow_state), so the
// list also works across sessions where the session-only schedule list is
// empty. Existing rows win on duplicates (same application).
if ($pdo) {
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
                'id'            => 'db_' . $appId,
                'applicationId' => $appId,
                'studentEmail'  => $row['studentEmail'] ?? '',
                'studentName'   => $row['studentName'] ?? '',
                'stage'         => $row['stage'] ?? '',
                'date'          => (string) ($wf['presentation_date'] ?? ''),
                'time'          => (string) ($wf['presentation_time'] ?? ''),
                'venue'         => (string) ($wf['presentation_venue'] ?? ''),
                'panel'         => (string) ($wf['presentation_panel'] ?? 'TBD'),
                'status'        => $row['status'] ?? 'scheduled',
            ];
        }
    } catch (Throwable $e) {
        // Keep whatever we have; the page still renders.
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
$schedules = $upcomingPresentations;

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Presentation Schedule Dashboard</h2>
    <p>View and manage scheduled capstone/thesis presentations.</p>
</div>

<div class="form-actions" style="border:none;padding:0;margin-bottom:1.5rem;">
    <a href="<?= url('coordinator/schedule/add.php') ?>" class="btn btn-primary"><i class="fas fa-calendar-plus"></i> Schedule Presentation</a>
</div>

<?php renderScheduleCalendar(); ?>

<div class="card">
    <div class="card-header"><h3>Scheduled Presentations</h3></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Stage</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Panel Members</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$schedules): ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--gray-400);padding:2rem;">No presentations scheduled yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($schedules as $sch): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($sch['studentName'] ?? $sch['student'] ?? '') ?></strong></td>
                        <td><?= htmlspecialchars($sch['stage']) ?></td>
                        <td><?= htmlspecialchars($sch['date']) ?></td>
                        <td><?= htmlspecialchars($sch['time']) ?></td>
                        <td style="max-width:200px;font-size:0.8rem;"><?= htmlspecialchars($sch['panel'] ?? $sch['panels'] ?? '') ?></td>
                        <td><?= statusBadge($sch['status']) ?></td>
                        <td class="actions">
                            <?php if (strpos((string) ($sch['id'] ?? ''), 'db_') !== 0): ?>
                            <a href="<?= url('coordinator/schedule/edit.php?id=' . urlencode($sch['id'])) ?>" class="btn btn-sm btn-outline" title="Edit"><i class="fas fa-edit"></i></a>
                            <a href="<?= url('coordinator/schedule/panel.php?id=' . urlencode($sch['id'])) ?>" class="btn btn-sm btn-outline" title="Panel"><i class="fas fa-users"></i></a>
                            <a href="<?= url('coordinator/schedule/notification.php?id=' . urlencode($sch['id'])) ?>" class="btn btn-sm btn-primary" title="Notification preview"><i class="fas fa-bell"></i></a>
                            <?= coordDeleteLink(url('coordinator/schedule/manage.php?delete=' . urlencode($sch['id'])), 'Delete this schedule?') ?>
                            <?php else: ?>
                            <?= coordDeleteLink(url('coordinator/schedule/manage.php?delete=' . urlencode($sch['id'])), 'Remove this presentation schedule from the list and calendar?') ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>