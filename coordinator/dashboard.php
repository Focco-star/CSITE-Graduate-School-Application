<?php
require_once __DIR__ . '/../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sessionUser = $_SESSION['user'] ?? [];
$userName    = !empty($sessionUser['full_name']) ? $sessionUser['full_name'] : ($mockCoordinator['name'] ?? 'Coordinator');
$pageTitle   = 'Welcome, ' . $userName;
$role        = 'coordinator';
$currentPage = 'dashboard';

// Ensure PDO connection fallback
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

// Database stats and dynamic applications list
$pendingAppsCount = 0;
$totalAppsCount   = 0;
$activeStudentsCount = 0;
$pendingApps = [];

if ($pdo) {
    try {
        // Pending applications count (submitted, under_review)
        $stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE status IN ('submitted', 'under_review')");
        $pendingAppsCount = (int) $stmt->fetchColumn();

        // Total applications count
        $stmt = $pdo->query("SELECT COUNT(*) FROM applications");
        $totalAppsCount = (int) $stmt->fetchColumn();

        // Active students count
        $stmt = $pdo->query("SELECT COUNT(*) FROM students");
        $activeStudentsCount = (int) $stmt->fetchColumn();

        // Applications requiring attention from DB
        $stmt = $pdo->prepare("
            SELECT 
                a.application_id AS id,
                COALESCE(
                    NULLIF(TRIM(CONCAT(s.first_name, ' ', s.last_name)), ''), 
                    u.full_name
                ) AS student,
                COALESCE(s.program, 'Graduate Program') AS program,
                a.presentation_stage AS stage,
                a.submitted_at AS date,
                a.status
            FROM applications a
            JOIN students s ON a.student_id = s.student_id
            LEFT JOIN users u ON s.user_id = u.user_id
            WHERE a.status IN ('submitted', 'under_review')
            ORDER BY a.submitted_at DESC
        ");
        $stmt->execute();
        $pendingApps = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Fallback to storeGet if query fails
    }
}

// Fallback logic if DB returns empty or unavailable
if (empty($pendingApps) && function_exists('storeGet')) {
    $pendingApps = array_values(array_filter(storeGet('applications') ?? [], static function ($a) {
        return in_array($a['status'] ?? '', ['submitted', 'under_review'], true);
    }));
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h2>Welcome, <?= htmlspecialchars($userName) ?>!</h2>
    <p>Overview of applications, submissions, and upcoming presentations requiring your attention.</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-inbox"></i></div>
        <div class="stat-value"><?= $pendingAppsCount ?></div>
        <div class="stat-label">Pending Applications</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon gold"><i class="fas fa-file-alt"></i></div>
        <div class="stat-value"><?= $totalAppsCount ?></div>
        <div class="stat-label">Total Applications</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-calendar-alt"></i></div>
        <div class="stat-value">0</div>
        <div class="stat-label">Upcoming Presentations</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-value"><?= $activeStudentsCount ?></div>
        <div class="stat-label">Active Students</div>
    </div>
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
        <div style="text-align: center; padding: 2rem; color: var(--gray-400); font-weight: 500;">
            No upcoming presentations.
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-bolt"></i> Quick Navigation</h3></div>
    <div class="card-body">
        <div class="info-grid">
            <a href="<?= url('coordinator/applications/manage.php') ?>" class="info-card" style="text-decoration:none;">
                <div class="icon"><i class="fas fa-file-alt"></i></div>
                <h3>Applications</h3>
                <p>View and process student applications.</p>
            </a>
            <a href="<?= url('coordinator/students/manage.php') ?>" class="info-card" style="text-decoration:none;">
                <div class="icon"><i class="fas fa-user-graduate"></i></div>
                <h3>Students</h3>
                <p>Manage graduate student records.</p>
            </a>
            <a href="<?= url('coordinator/schedule/add.php') ?>" class="info-card" style="text-decoration:none;">
                <div class="icon"><i class="fas fa-calendar-plus"></i></div>
                <h3>Schedule Presentation</h3>
                <p>Assign panels and set presentation dates.</p>
            </a>
            <a href="<?= url('coordinator/reports/dashboard.php') ?>" class="info-card" style="text-decoration:none;">
                <div class="icon"><i class="fas fa-chart-bar"></i></div>
                <h3>Reports</h3>
                <p>Generate student and panel reports.</p>
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>