<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/program_chart.php';
require_once __DIR__ . '/../includes/calendar.php';

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


$activeStudentsCount = 0;
$students = function_exists('storeGet') ? (array) (storeGet('students') ?? []) : [];
$completedStudentsCount = count(array_filter($students, static function ($student) {
    return in_array($student['status'] ?? '', ['completed', 'approved'], true);
}));
$activePanelMembersCount = 0;
$academicYearStart = (int) date('n') >= 7 ? (int) date('Y') : (int) date('Y') - 1;
$academicYearStartDate = strtotime($academicYearStart . '-07-01');
$academicYearEndDate = strtotime(($academicYearStart + 1) . '-07-01');
$academicYearLabel = $academicYearStart . '-' . ($academicYearStart + 1);
$upcomingPresentations = [];

// Merge saved schedules with workflow-only schedules so the academic-year
// summary counts each presentation once.
if (function_exists('databaseSchedules')) {
    try {
        $upcomingPresentations = databaseSchedules();
    } catch (Throwable $e) {
        $upcomingPresentations = [];
    }
}

// Merge database-backed scheduled applications (schedule/add.php records the
// presentation date/time/venue/panel in applications.workflow_state), so the
// count also works across sessions where the session-only schedule list is
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

if ($pdo) {
    try {
        $studentStatuses = $pdo->query(
            "SELECT
                (SELECT a.status
                 FROM applications a
                 WHERE a.user_id = s.user_id AND a.archived_at IS NULL
                 ORDER BY a.submitted_at DESC, a.application_id DESC
                 LIMIT 1) AS latest_status
             FROM students s
             WHERE s.archived_at IS NULL"
        )->fetchAll(PDO::FETCH_COLUMN);
        $completedStudentsCount = count(array_filter($studentStatuses, static function ($status) {
            return in_array($status, ['completed', 'approved'], true);
        }));

        $stmt = $pdo->query("SELECT COUNT(*) FROM students WHERE archived_at IS NULL");
        $activeStudentsCount = (int) $stmt->fetchColumn();

    } catch (PDOException $e) {

    }
}

$panelMembers = function_exists('databasePanelMembers') ? databasePanelMembers() : [];
$activePanelMembersCount = count(array_filter($panelMembers, static function ($member) {
    return ($member['availability'] ?? '') === 'available';
}));

$presentationsThisAcademicYear = count(array_filter($upcomingPresentations, static function ($presentation) use ($academicYearStartDate, $academicYearEndDate) {
    $date = strtotime((string) ($presentation['date'] ?? ''));
    return $date !== false
        && $date >= $academicYearStartDate
        && $date < $academicYearEndDate
        && ($presentation['status'] ?? '') !== 'cancelled';
}));

if (!$activeStudentsCount && !$pdo) {
    $activeStudentsCount = count($students);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h2>Welcome, <?= htmlspecialchars($userName) ?>!</h2>
    <p>Overview of applications, submissions, and upcoming presentations requiring your attention.</p>
</div>

<section class="quick-summary">
    <div class="quick-summary-header">
        <h3>Quick Summary</h3>
        <span>Current academic year: <?= htmlspecialchars($academicYearLabel) ?></span>
    </div>
    <div class="quick-summary-body">
        <div class="quick-summary-grid">
            <a href="<?= url('coordinator/students/manage.php') ?>" class="quick-summary-stat">
                <span class="quick-summary-icon"><i class="fas fa-check-circle"></i></span>
                <span>
                    <span class="quick-summary-value"><?= $completedStudentsCount ?></span>
                    <span class="quick-summary-label">Completed This Year</span>
                </span>
            </a>
            <a href="<?= url('coordinator/panel/manage.php') ?>" class="quick-summary-stat">
                <span class="quick-summary-icon"><i class="fas fa-chalkboard-teacher"></i></span>
                <span>
                    <span class="quick-summary-value"><?= $activePanelMembersCount ?></span>
                    <span class="quick-summary-label">Active Panel Members</span>
                </span>
            </a>
            <a href="<?= url('coordinator/schedule/manage.php') ?>" class="quick-summary-stat">
                <span class="quick-summary-icon"><i class="fas fa-chalkboard"></i></span>
                <span>
                    <span class="quick-summary-value"><?= $presentationsThisAcademicYear ?></span>
                    <span class="quick-summary-label">Presentations, SY <?= htmlspecialchars($academicYearLabel) ?></span>
                </span>
            </a>
            <a href="<?= url('coordinator/students/manage.php') ?>" class="quick-summary-stat">
                <span class="quick-summary-icon"><i class="fas fa-user-graduate"></i></span>
                <span>
                    <span class="quick-summary-value"><?= $activeStudentsCount ?></span>
                    <span class="quick-summary-label">Active Students</span>
                </span>
            </a>
        </div>
    </div>
</section>

<?php renderScheduleCalendar(); ?>

<?php renderProgramPopulationChart($pdo ?? null); ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>