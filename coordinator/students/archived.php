<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Archived Students';
$role = 'coordinator';
$currentPage = 'archived_students';
$userName = currentCoordinatorName();
$error = '';
$students = [];

try {
    $pdo = DB::getConnection();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_id'])) {
        $stmt = $pdo->prepare('UPDATE students SET archived_at = NULL WHERE student_id = :id AND archived_at IS NOT NULL');
        $stmt->execute(['id' => (int) $_POST['restore_id']]);
        setFlash('success', 'Student restored to the active student list.');
        redirectTo('coordinator/students/archived.php');
    }
    $stmt = $pdo->query("SELECT s.student_id, CONCAT_WS(' ', s.first_name, NULLIF(TRIM(REPLACE(s.middle_initial, '.', '')), ''), s.last_name) AS name, s.program, s.track, s.enrollment_date, s.archived_at FROM students s WHERE s.archived_at IS NOT NULL ORDER BY s.archived_at DESC");
    $students = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = 'Archived student records are unavailable. Confirm that the archive database migration has been applied.';
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><h2>Archived Students</h2><p>View retained student records and restore a record when it becomes active again.</p></div>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card"><div class="card-header"><h3>Archived Student Records <span class="filter-count" data-filter-count></span></h3></div><div class="card-body">
    <div class="filter-bar" data-filter-table="#archivedStudentsTable"><div class="filter-search"><i class="fas fa-search"></i><input type="search" data-filter-q placeholder="Search archived students..."></div><button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button></div>
    <div class="table-responsive"><table class="data-table" id="archivedStudentsTable"><thead><tr><th>Student</th><th>Program</th><th>Track</th><th>Enrolled</th><th>Archived</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($students as $student): ?><tr data-search="<?= htmlspecialchars(strtolower($student['name'] . ' ' . $student['program'] . ' ' . $student['track'])) ?>"><td><strong><?= htmlspecialchars($student['name']) ?></strong></td><td><?= htmlspecialchars($student['program']) ?></td><td><?= htmlspecialchars(getTrackLabel($student['track'])) ?></td><td><?= htmlspecialchars(date('M d, Y', strtotime($student['enrollment_date']))) ?></td><td><?= htmlspecialchars(date('M d, Y g:i A', strtotime($student['archived_at']))) ?></td><td><form method="post" data-confirm-form data-confirm-message="Restore <?= htmlspecialchars($student['name']) ?> to the active student list?"><input type="hidden" name="restore_id" value="<?= (int) $student['student_id'] ?>"><button type="button" class="btn btn-sm btn-primary" data-confirm-trigger><i class="fas fa-undo"></i> Restore</button></form></td></tr><?php endforeach; ?>
    <tr data-filter-empty <?= $students ? 'hidden' : '' ?>><td colspan="6" style="text-align:center;color:var(--gray-400);padding:2rem;">No archived students found.</td></tr>
    </tbody></table></div>
</div></div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
