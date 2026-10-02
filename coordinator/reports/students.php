<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

$pageTitle   = 'Student Reports';
$role        = 'coordinator';
$currentPage = 'reports';
$userName    = $mockCoordinator['name'];

$ongoing = [];
$completed = [];

try {
    $pdo = DB::getConnection();

    // Ongoing = every active student + their latest application stage (if any),
    // alphabetical by last name, then first name — same source as Students list,
// so new accounts appear here automatically.
    $stmt = $pdo->query(
        "SELECT
            TRIM(CONCAT(s.last_name, ', ', s.first_name, IF(s.middle_initial IS NULL OR TRIM(REPLACE(s.middle_initial, '.', '')) = '', '', CONCAT(' ', TRIM(REPLACE(s.middle_initial, '.', '')))))) AS name,
            CASE
                WHEN s.program LIKE '%Computer Science%' THEN 'MSCS'
                WHEN s.program LIKE '%Information Technology%' THEN 'MIT'
                WHEN s.program LIKE '%Mathematics%' THEN 'MATH'
                ELSE s.program
            END AS program,
            COALESCE(latest_app.presentation_stage, 'Not Started') AS stage,
            DATE_FORMAT(s.enrollment_date, '%Y-%m') AS since
         FROM students s
         LEFT JOIN users u ON u.user_id = s.user_id
         LEFT JOIN (
             SELECT a1.*
             FROM applications a1
             INNER JOIN (
                 SELECT user_id, MAX(submitted_at) AS max_submitted
                 FROM applications
                 WHERE archived_at IS NULL
                 GROUP BY user_id
             ) a2 ON a1.user_id = a2.user_id AND a1.submitted_at = a2.max_submitted
         ) latest_app ON latest_app.user_id = s.user_id
         WHERE s.archived_at IS NULL
         ORDER BY s.last_name ASC, s.first_name ASC"
    );
    $ongoing = $stmt->fetchAll();

    // Completed = active students whose latest completed application carries the
    // final outcome (latest 'completed' application per student).
    $stmt = $pdo->query(
        "SELECT
            TRIM(CONCAT(s.last_name, ', ', s.first_name, IF(s.middle_initial IS NULL OR TRIM(REPLACE(s.middle_initial, '.', '')) = '', '', CONCAT(' ', TRIM(REPLACE(s.middle_initial, '.', '')))))) AS name,
            CASE
                WHEN s.program LIKE '%Computer Science%' THEN 'MSCS'
                WHEN s.program LIKE '%Information Technology%' THEN 'MIT'
                WHEN s.program LIKE '%Mathematics%' THEN 'MATH'
                ELSE s.program
            END AS program,
            a.paper_title AS title,
            DATE_FORMAT(a.updated_at, '%Y-%m') AS completed
         FROM students s
         INNER JOIN users u ON u.user_id = s.user_id
         INNER JOIN applications a ON a.user_id = u.user_id
            AND a.status = 'completed' AND a.archived_at IS NULL
         WHERE s.archived_at IS NULL
           AND NOT EXISTS (
               SELECT 1 FROM applications a2
               WHERE a2.user_id = a.user_id
                 AND a2.status = 'completed' AND a2.archived_at IS NULL
                 AND (a2.submitted_at > a.submitted_at
                      OR (a2.submitted_at = a.submitted_at AND a2.application_id > a.application_id))
           )
         ORDER BY s.last_name ASC, s.first_name ASC"
    );
    $completed = $stmt->fetchAll();
} catch (Throwable $e) {
    $ongoing = [];
    $completed = [];
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Student Reports</h2>
    <p>Semester-based reports of students by capstone/thesis progress.</p>
</div>

<div class="card">
    <div class="card-header">
        <h3>Filter</h3>
        <div style="display:flex;gap:0.5rem;">
            <select style="padding:0.4rem 0.75rem;border:1px solid var(--gray-300);border-radius:var(--radius);font-size:0.85rem;">
                <option>1st Semester 2024–2025</option>
                <option>2nd Semester 2024–2025</option>
                <option>1st Semester 2025–2026</option>
            </select>
            <button class="btn btn-sm btn-outline"><i class="fas fa-download"></i> Export</button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Ongoing Students (<?= count($ongoing) ?>)</h3></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Student</th><th>Program</th><th>Current Stage</th><th>Since</th></tr></thead>
                <tbody>
                    <?php foreach ($ongoing as $s): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                        <td><?= htmlspecialchars($s['program']) ?></td>
                        <td><?= htmlspecialchars($s['stage']) ?></td>
                        <td><?= htmlspecialchars($s['since']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$ongoing): ?>
                    <tr><td colspan="4" style="text-align:center;color:var(--gray-400);padding:1.5rem;">No ongoing students found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Completed Students (<?= count($completed) ?>)</h3></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Student</th><th>Program</th><th>Title</th><th>Completed</th></tr></thead>
                <tbody>
                    <?php foreach ($completed as $s): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                        <td><?= htmlspecialchars($s['program']) ?></td>
                        <td><?= htmlspecialchars($s['title']) ?></td>
                        <td><?= htmlspecialchars($s['completed']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$completed): ?>
                    <tr><td colspan="4" style="text-align:center;color:var(--gray-400);padding:1.5rem;">No completed students yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="form-actions">
    <a href="<?= url('coordinator/reports/dashboard.php') ?>" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Reports</a>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
