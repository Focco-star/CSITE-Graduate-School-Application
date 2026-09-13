<?php
require_once __DIR__ . '/../../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle   = 'Manage Students';
$role        = 'coordinator';
$currentPage = 'students';
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

// Handle student record deletion from DB
if (isset($_GET['delete']) && $_GET['delete'] !== '') {
    $deleteId = (int) $_GET['delete'];
    if ($pdo && $deleteId > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = :id");
            $stmt->execute([':id' => $deleteId]);
        } catch (PDOException $e) {
            // Error handling fallback
        }
    } elseif (function_exists('deleteStudentRecord')) {
        deleteStudentRecord((string) $_GET['delete']);
    }
    redirectTo('coordinator/students/manage.php');
}

// Fetch real student records from database
$students = [];

if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                s.student_id AS id,
                COALESCE(
                    NULLIF(TRIM(CONCAT(s.first_name, ' ', IFNULL(s.middle_initial, ''), ' ', s.last_name)), ''), 
                    u.full_name
                ) AS name,
                COALESCE(s.program, 'MSCS') AS program,
                s.track,
                s.created_at AS enrollDate,
                latest_app.presentation_stage AS stageLabel,
                latest_app.status AS status
            FROM students s
            LEFT JOIN users u ON s.user_id = u.user_id
            LEFT JOIN (
                SELECT a1.*
                FROM applications a1
                INNER JOIN (
                    SELECT student_id, MAX(submitted_at) AS max_submitted
                    FROM applications
                    GROUP BY student_id
                ) a2 ON a1.student_id = a2.student_id AND a1.submitted_at = a2.max_submitted
            ) latest_app ON s.student_id = latest_app.student_id
            ORDER BY s.student_id ASC
        ");
        $students = $stmt->fetchAll();
    } catch (PDOException $e) {
        $students = [];
    }
}

// Fallback logic if database query returns empty or PDO isn't available
if (empty($students) && function_exists('storeGet')) {
    $students = storeGet('students') ?? [];
}

$statusFilters = ['not_started','submitted','under_review','for_payment','payment_recorded','ready_for_presentation','scheduled','approved','requires_revision','completed'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Student Management</h2>
    <p>View CSITE graduate student records and application progress.</p>
</div>

<div class="card">
    <div class="card-header">
        <h3>Graduate Students <span class="filter-count" data-filter-count></span></h3>
    </div>
    <div class="card-body">
        <div class="filter-bar" data-filter-table="#studentsTable">
            <div class="filter-search">
                <i class="fas fa-search"></i>
                <input type="search" data-filter-q placeholder="Search by name, program, stage, or status...">
            </div>
            <button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button>
            <select data-filter="program">
                <option value="">All Programs</option>
                <?php foreach (array_keys(PROGRAMS) as $code): ?>
                <option><?= htmlspecialchars($code) ?></option>
                <?php endforeach; ?>
            </select>
            <select data-filter="status">
                <option value="">All Statuses</option>
                <?php foreach ($statusFilters as $key): ?>
                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars(STATUSES[$key]['label'] ?? ucfirst($key)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="table-responsive">
            <table class="data-table" id="studentsTable">
                <thead>
                    <tr><th>Student</th><th>Program</th><th>Track</th><th>Current Stage</th><th>Enrolled</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s):
                        $name = function_exists('studentDisplayName') ? studentDisplayName($s) : ($s['name'] ?? 'Student');
                        $stageLabel = !empty($s['stageLabel']) ? $s['stageLabel'] : (function_exists('displayStageLabel') ? displayStageLabel($s) : 'Not Started');
                        $rawTrack = $s['track'] ?? (function_exists('getTrackForProgram') ? getTrackForProgram($s['program']) : 'thesis');
                        $trackLabel = $s['trackLabel'] ?? (function_exists('getTrackLabel') ? getTrackLabel($rawTrack) : ucfirst($rawTrack));
                        $status = !empty($s['status']) ? $s['status'] : 'not_started';
                        $enrollDate = !empty($s['enrollDate']) ? $s['enrollDate'] : '2024-08-01';
                    ?>
                    <tr data-program="<?= htmlspecialchars($s['program']) ?>"
                        data-status="<?= htmlspecialchars($status) ?>"
                        data-search="<?= htmlspecialchars(strtolower($name . ' ' . $s['program'] . ' ' . $stageLabel . ' ' . $status . ' ' . $trackLabel)) ?>">
                        <td><strong><?= htmlspecialchars($name) ?></strong></td>
                        <td><?= htmlspecialchars($s['program']) ?></td>
                        <td><span style="font-size:0.75rem;background:var(--gray-100);padding:2px 8px;border-radius:99px;"><?= htmlspecialchars($trackLabel) ?></span></td>
                        <td><?= htmlspecialchars($stageLabel) ?></td>
                        <td><?= date('M Y', strtotime($enrollDate . (strlen($enrollDate) === 7 ? '-01' : ''))) ?></td>
                        <td><?= statusBadge($status) ?></td>
                        <td class="actions">
                            <a href="<?= url('coordinator/students/view.php?id=' . urlencode($s['id'])) ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i> View</a>
                            <?= function_exists('coordDeleteLink') ? coordDeleteLink(url('coordinator/students/manage.php?delete=' . urlencode($s['id'])), 'Delete this student record? This cannot be undone.') : '' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr data-filter-empty <?= $students ? 'hidden' : '' ?>>
                        <td colspan="7" style="text-align:center;color:var(--gray-400);padding:2rem;">No students match your search or filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>