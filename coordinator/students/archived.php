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

    // ---------- RESTORE ----------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_id'])) {
        $stmt = $pdo->prepare('UPDATE students SET archived_at = NULL WHERE student_id = :id AND archived_at IS NOT NULL');
        $stmt->execute(['id' => (int) $_POST['restore_id']]);
        setFlash('success', 'Student restored to the active student list.');
        redirectTo('coordinator/students/archived.php');
    }

    // ---------- PERMANENT DELETE ----------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
        $studentId = (int) $_POST['delete_id'];
        $storedFiles = [];
        $deleted = false;

        try {
            $pdo->beginTransaction();

            // Only archived students can be deleted from this page.
            $stmt = $pdo->prepare('SELECT user_id FROM students WHERE student_id = :id AND archived_at IS NOT NULL FOR UPDATE');
            $stmt->execute(['id' => $studentId]);
            $row = $stmt->fetch();

            if ($row) {
                // Collect uploaded file names before the rows disappear.
                $stmt = $pdo->prepare('SELECT stored_name FROM application_documents WHERE user_id = :uid');
                $stmt->execute(['uid' => (int) $row['user_id']]);
                $storedFiles = $stmt->fetchAll(PDO::FETCH_COLUMN);

                // Deleting the user account cascades to students -> applications -> documents.
                if (!empty($row['user_id'])) {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :uid AND role = 'student'");
                    $stmt->execute(['uid' => (int) $row['user_id']]);
                }
                // Safety net in case the student row still exists.
                $stmt = $pdo->prepare('DELETE FROM students WHERE student_id = :id AND archived_at IS NOT NULL');
                $stmt->execute(['id' => $studentId]);

                $deleted = true;
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $deleted = false;
            $error = 'The student could not be deleted. Please try again.';
        }

        if ($deleted) {
            // Remove uploaded files from disk (adjust the folder to match your upload path).
            $uploadDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : dirname(__DIR__, 2) . '/uploads/';
            foreach ($storedFiles as $file) {
                $path = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . basename($file);
                if (is_file($path)) @unlink($path);
            }
            setFlash('success', 'Student was permanently deleted from the system.');
            redirectTo('coordinator/students/archived.php');
        } elseif ($error === '') {
            $error = 'That student record was not found or is no longer archived.';
        }
    }

    $stmt = $pdo->query("SELECT s.student_id, TRIM(CONCAT(s.last_name, ', ', s.first_name, IF(s.middle_initial IS NULL OR TRIM(REPLACE(s.middle_initial, '.', '')) = '', '', CONCAT(' ', TRIM(REPLACE(s.middle_initial, '.', '')))))) AS name, s.program, s.track, s.enrollment_date, s.archived_at FROM students s WHERE s.archived_at IS NOT NULL ORDER BY s.last_name ASC, s.first_name ASC");
    $students = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = 'Archived student records are unavailable. Confirm that the archive database migration has been applied.';
}

require_once __DIR__ . '/../../includes/header.php';
?>
<style>
    /* ---------- Shared confirmation dialog (Delete + Restore) ---------- */
    .confirm-modal {
        max-width: 480px;
        width: 100%;
        text-align: left;
        border-top: none;
        overflow: hidden;
    }

    .confirm-header {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--gray-200);
    }
    .confirm-icon {
        flex: 0 0 44px;
        width: 44px; height: 44px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem;
    }
    .confirm-icon.is-danger  { background: var(--danger-bg); color: var(--danger); }
    .confirm-icon.is-primary { background: var(--gray-100);   color: var(--adzu-navy); }

    .confirm-header h3 {
        margin: 0;
        font-family: var(--font-heading);
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--adzu-navy);
        line-height: 1.3;
    }
    .confirm-header .confirm-subtitle {
        margin: 0.15rem 0 0;
        font-size: 0.8rem;
        color: var(--gray-500);
    }

    .confirm-body { padding: 1.25rem 1.5rem; }
    .confirm-message {
        margin: 0 0 1rem;
        font-size: 0.9rem;
        line-height: 1.55;
        color: var(--gray-600);
    }

    .confirm-details {
        margin: 0 0 1rem;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-md, 8px);
        background: var(--gray-50);
    }
    .confirm-details-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 1rem;
        padding: 0.6rem 0.9rem;
        font-size: 0.85rem;
    }
    .confirm-details-row + .confirm-details-row { border-top: 1px solid var(--gray-200); }
    .confirm-details-label {
        color: var(--gray-500);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-size: 0.72rem;
        white-space: nowrap;
    }
    .confirm-details-value {
        color: var(--adzu-navy);
        font-weight: 600;
        text-align: right;
        word-break: break-word;
    }

    .confirm-list {
        margin: 0 0 1rem;
        padding-left: 1.15rem;
        font-size: 0.85rem;
        color: var(--gray-600);
        line-height: 1.7;
    }

    .confirm-note {
        display: flex;
        gap: 0.6rem;
        align-items: flex-start;
        margin: 0;
        padding: 0.7rem 0.9rem;
        border-radius: var(--radius-md, 8px);
        font-size: 0.8rem;
        line-height: 1.5;
    }
    .confirm-note i { margin-top: 0.15rem; }
    .confirm-note.is-danger  { background: var(--danger-bg); color: var(--danger); }
    .confirm-note.is-neutral { background: var(--gray-100);  color: var(--gray-600); }

    .confirm-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.6rem;
        padding: 1rem 1.5rem;
        background: var(--gray-50);
        border-top: 1px solid var(--gray-200);
    }
    .confirm-footer .btn { min-width: 120px; justify-content: center; }

    @media (max-width: 480px) {
        .confirm-footer { flex-direction: column-reverse; }
        .confirm-footer .btn { width: 100%; }
    }

    .data-table td .actions form { margin: 0; }
</style>

<div class="page-header"><h2>Archived Students</h2><p>View retained student records, restore a record when it becomes active again, or permanently delete it.</p></div>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card"><div class="card-header"><h3>Archived Student Records <span class="filter-count" data-filter-count></span></h3></div><div class="card-body">
    <div class="filter-bar" data-filter-table="#archivedStudentsTable"><div class="filter-search"><i class="fas fa-search"></i><input type="search" data-filter-q placeholder="Search archived students..."></div><button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button></div>
    <div class="table-responsive"><table class="data-table" id="archivedStudentsTable"><thead><tr><th>Student</th><th>Program</th><th>Track</th><th>Enrolled</th><th>Archived</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($students as $student): ?><tr data-search="<?= htmlspecialchars(strtolower($student['name'] . ' ' . $student['program'] . ' ' . $student['track'])) ?>"><td><strong><?= htmlspecialchars($student['name']) ?></strong></td><td><?= htmlspecialchars($student['program']) ?></td><td><?= htmlspecialchars(getTrackLabel($student['track'])) ?></td><td><?= htmlspecialchars(date('M d, Y', strtotime($student['enrollment_date']))) ?></td><td><?= htmlspecialchars(date('M d, Y g:i A', strtotime($student['archived_at']))) ?></td><td><div class="actions"><button type="button" class="btn btn-sm btn-primary" data-restore-trigger data-student-id="<?= (int) $student['student_id'] ?>" data-student-name="<?= htmlspecialchars($student['name']) ?>" data-student-program="<?= htmlspecialchars($student['program']) ?>"><i class="fas fa-undo"></i> Restore</button><button type="button" class="btn btn-sm btn-danger" data-delete-trigger data-student-id="<?= (int) $student['student_id'] ?>" data-student-name="<?= htmlspecialchars($student['name']) ?>" data-student-program="<?= htmlspecialchars($student['program']) ?>"><i class="fas fa-trash-alt"></i> Delete</button></div></td></tr><?php endforeach; ?>
    <tr data-filter-empty <?= $students ? 'hidden' : '' ?>><td colspan="6" style="text-align:center;color:var(--gray-400);padding:2rem;">No archived students found.</td></tr>
    </tbody></table></div>
</div></div>

<!-- Restore confirmation pop-up -->
<div class="modal-overlay" id="restoreStudentModal" role="dialog" aria-modal="true" aria-labelledby="restoreModalTitle">
    <div class="modal confirm-modal">
        <form method="post" id="restoreStudentForm">
            <input type="hidden" name="restore_id" id="restoreStudentId" value="">
            <div class="confirm-header">
                <div class="confirm-icon is-primary"><i class="fas fa-undo"></i></div>
                <div>
                    <h3 id="restoreModalTitle">Restore Student Record</h3>
                    <p class="confirm-subtitle">Return this record to the active student list.</p>
                </div>
            </div>
            <div class="confirm-body">
                <p class="confirm-message">Please confirm that you want to restore the following student.</p>
                <div class="confirm-details">
                    <div class="confirm-details-row">
                        <span class="confirm-details-label">Student</span>
                        <span class="confirm-details-value" id="restoreStudentName"></span>
                    </div>
                    <div class="confirm-details-row">
                        <span class="confirm-details-label">Program</span>
                        <span class="confirm-details-value" id="restoreStudentProgram"></span>
                    </div>
                </div>
                <p class="confirm-note is-neutral">
                    <i class="fas fa-info-circle"></i>
                    <span>The student will be removed from the archive and will appear in the active student list again.</span>
                </p>
            </div>
            <div class="confirm-footer">
                <button type="button" class="btn btn-outline" id="restoreCancelBtn">Cancel</button>
                <button type="submit" class="btn btn-primary" id="restoreConfirmBtn"><i class="fas fa-undo"></i> Restore Student</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete confirmation pop-up -->
<div class="modal-overlay" id="deleteStudentModal" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
    <div class="modal confirm-modal">
        <form method="post" id="deleteStudentForm">
            <input type="hidden" name="delete_id" id="deleteStudentId" value="">
            <div class="confirm-header">
                <div class="confirm-icon is-danger"><i class="fas fa-trash-alt"></i></div>
                <div>
                    <h3 id="deleteModalTitle">Permanently Delete Student</h3>
                    <p class="confirm-subtitle">This action is permanent and cannot be undone.</p>
                </div>
            </div>
            <div class="confirm-body">
                <p class="confirm-message">Please confirm that you want to permanently delete the following student.</p>
                <div class="confirm-details">
                    <div class="confirm-details-row">
                        <span class="confirm-details-label">Student</span>
                        <span class="confirm-details-value" id="deleteStudentName"></span>
                    </div>
                    <div class="confirm-details-row">
                        <span class="confirm-details-label">Program</span>
                        <span class="confirm-details-value" id="deleteStudentProgram"></span>
                    </div>
                </div>
                <ul class="confirm-list">
                    <li>Student account</li>
                    <li>Submitted applications</li>
                    <li>Uploaded documents</li>
                </ul>
                <p class="confirm-note is-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>All of the items above will be permanently removed from the system and database.</span>
                </p>
            </div>
            <div class="confirm-footer">
                <button type="button" class="btn btn-outline" id="deleteCancelBtn">Cancel</button>
                <button type="submit" class="btn btn-danger" id="deleteConfirmBtn"><i class="fas fa-trash-alt"></i> Delete Student</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    // Reusable controller for a confirmation modal.
    function setupConfirmModal(cfg) {
        var overlay    = document.getElementById(cfg.overlay);
        var form       = document.getElementById(cfg.form);
        var idInput    = document.getElementById(cfg.idInput);
        var nameEl     = document.getElementById(cfg.name);
        var programEl  = document.getElementById(cfg.program);
        var cancelBtn  = document.getElementById(cfg.cancel);
        var confirmBtn = document.getElementById(cfg.confirm);

        function open(id, name, program) {
            idInput.value = id;
            nameEl.textContent = name;
            programEl.textContent = program || '—';
            confirmBtn.disabled = false;
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            cancelBtn.focus(); // safe default: Cancel
        }

        function close() {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
            idInput.value = '';
            nameEl.textContent = '';
            programEl.textContent = '';
        }

        // Open from any trigger button (works with filtered rows too)
        document.addEventListener('click', function (e) {
            var btn = e.target.closest(cfg.trigger);
            if (btn) {
                open(
                    btn.getAttribute('data-student-id'),
                    btn.getAttribute('data-student-name'),
                    btn.getAttribute('data-student-program')
                );
            }
        });

        cancelBtn.addEventListener('click', close);

        // Click outside the box cancels
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) close();
        });

        // Esc cancels
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && overlay.classList.contains('active')) close();
        });

        // Prevent double submit
        form.addEventListener('submit', function () {
            confirmBtn.disabled = true;
        });
    }

    setupConfirmModal({
        overlay: 'restoreStudentModal', form: 'restoreStudentForm',
        idInput: 'restoreStudentId', name: 'restoreStudentName', program: 'restoreStudentProgram',
        cancel: 'restoreCancelBtn', confirm: 'restoreConfirmBtn',
        trigger: '[data-restore-trigger]'
    });

    setupConfirmModal({
        overlay: 'deleteStudentModal', form: 'deleteStudentForm',
        idInput: 'deleteStudentId', name: 'deleteStudentName', program: 'deleteStudentProgram',
        cancel: 'deleteCancelBtn', confirm: 'deleteConfirmBtn',
        trigger: '[data-delete-trigger]'
    });
})();
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>