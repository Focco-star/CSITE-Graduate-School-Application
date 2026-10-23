<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sessionUser = $_SESSION['user'] ?? [];
if (!is_array($sessionUser) || strtolower((string) ($sessionUser['role'] ?? '')) !== 'coordinator') {
    redirectTo('coordinator/login.php');
}

$pageTitle   = 'Schedule Presentation';
$role        = 'coordinator';
$currentPage = 'schedule';
$userName    = $mockCoordinator['name'];

$students = storeGet('students');
try {
    $databaseStudents = DB::query(
        "SELECT s.student_id, s.first_name, s.last_name, s.middle_initial,
                s.program, s.track, s.adviser_name AS adviser, u.email
         FROM students s
         INNER JOIN users u ON u.user_id = s.user_id
         WHERE s.archived_at IS NULL AND u.role = 'student'
         ORDER BY s.last_name, s.first_name"
    )->fetchAll();
    if ($databaseStudents) {
        $students = array_map(static function (array $student): array {
            $student['program'] = programCodeForStudent($student);
            $student['track'] = $student['track'] ?: getTrackForProgram($student['program']);
            return $student;
        }, $databaseStudents);
    }
} catch (Throwable $e) {
    // Keep the prototype student list available when the database is unavailable.
}
$panelOpts = panelSelectOptions();
$adviserOpts = [];
try {
    $adviserOpts = DB::query("SELECT name, availability FROM advisor_pool ORDER BY availability = 'available' DESC, name ASC")->fetchAll();
} catch (Throwable $e) {
}
$preselected = '';
if (!empty($_GET['app'])) {
    $preApp = findApplication((int) $_GET['app']);
    $preselected = $preApp['studentEmail'] ?? '';
}

$stageMap = [];
foreach ($students as $s) {
    $track = $s['track'] ?? getTrackForProgram($s['program']);
    $stageMap[$s['email']] = [
        'track' => getTrackLabel($track),
        'program' => $s['program'],
        'stages' => presentationStageOptions($track),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['studentEmail'] ?? ''));
    $st = findStudentByEmail($email);
    if (!$st) {
        try {
            $studentStmt = DB::getConnection()->prepare(
                'SELECT s.student_id, s.first_name, s.last_name, s.middle_initial,
                        s.program, s.track, s.adviser_name AS adviser, u.email
                 FROM students s
                 INNER JOIN users u ON u.user_id = s.user_id
                 WHERE u.email = :email AND s.archived_at IS NULL
                 LIMIT 1'
            );
            $studentStmt->execute(['email' => $email]);
            $st = $studentStmt->fetch() ?: null;
            if ($st) {
                $st['program'] = programCodeForStudent($st);
                $st['track'] = $st['track'] ?: getTrackForProgram($st['program']);
            }
        } catch (Throwable $e) {
            $st = null;
        }
    }
    $stage = trim((string) ($_POST['stage'] ?? ''));
    $date = trim((string) ($_POST['date'] ?? ''));
    $time = trim((string) ($_POST['time'] ?? ''));
    $venue = trim((string) ($_POST['venue'] ?? ''));
    $panelMembers = array_map(
        static fn(string $key): string => trim((string) ($_POST[$key] ?? '')),
        ['panel1', 'panel2', 'panel3']
    );
    $adviser = trim((string) ($_POST['adviser'] ?? ''));
    $documentor = trim((string) ($_POST['documentor'] ?? ''));
    $dateValue = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    $dateErrors = DateTimeImmutable::getLastErrors();
    $timeValue = DateTimeImmutable::createFromFormat('!H:i', $time);
    $timeErrors = DateTimeImmutable::getLastErrors();
    $dateIsValid = $dateValue !== false
        && ($dateErrors === false || ((int) $dateErrors['warning_count'] === 0 && (int) $dateErrors['error_count'] === 0))
        && $dateValue->format('Y-m-d') === $date;
    $timeIsValid = $timeValue !== false
        && ($timeErrors === false || ((int) $timeErrors['warning_count'] === 0 && (int) $timeErrors['error_count'] === 0))
        && $timeValue->format('H:i') === $time;
    $validStages = $stageMap[$email]['stages'] ?? [];

    if (!$st || $stage === '' || !$dateIsValid || !$timeIsValid || $venue === ''
        || !in_array($stage, $validStages, true) || in_array('', $panelMembers, true)
        || $adviser === '' || $documentor === '') {
        setFlash('error', 'Complete all required schedule details and try again.');
        redirectTo('coordinator/schedule/add.php');
    }

    $databaseUserId = databaseUserIdForEmail($email);
    if (class_exists('DB')) {
        $applicationId = $databaseUserId > 0
            ? (int) (databaseApplicationIdForStudent($databaseUserId, $stage) ?? 0)
            : 0;
        if ($applicationId <= 0) {
            setFlash('error', 'This student has no active application for the selected presentation stage. The schedule was not saved.');
            redirectTo('coordinator/schedule/add.php');
        }
    } else {
        $app = latestApplicationForEmail($email);
        $applicationId = (int) ($app['id'] ?? 0);
        if ($applicationId <= 0) {
            setFlash('error', 'No application was found for this student. The schedule was not saved.');
            redirectTo('coordinator/schedule/add.php');
        }
    }

    $panel = implode(', ', $panelMembers);
    $status = ($_POST['status'] ?? 'pending') === 'confirmed' ? 'confirmed' : 'pending';
    try {
        addScheduleRecord([
            'studentEmail' => $email,
            'studentName' => studentDisplayName($st),
            'applicationId' => $applicationId,
            'stage' => $stage,
            'date' => $date,
            'time' => $time,
            'venue' => $venue,
            'panel' => $panel,
            'adviser' => formatPersonName($adviser),
            'documentor' => $documentor,
            'status' => $status,
        ]);
    } catch (Throwable $e) {
        error_log('[schedule save] ' . $e->getMessage());
        setFlash('error', 'The presentation schedule could not be saved. Please verify the database migrations are applied and try again.');
        redirectTo('coordinator/schedule/add.php');
    }

    if (class_exists('DB')) {
        try {
            $pdo = DB::getConnection();
            $cur = $pdo->prepare('SELECT workflow_state FROM applications WHERE application_id = :id LIMIT 1');
            $cur->execute(['id' => $applicationId]);
            $wf = json_decode((string) ($cur->fetchColumn() ?: '[]'), true) ?: [];
            $wf['presentation_date'] = $date;
            $wf['presentation_time'] = $time;
            $wf['presentation_venue'] = $venue;
            $wf['presentation_panel'] = $panel;
            $upd = $pdo->prepare('UPDATE applications SET status = :status, workflow_state = :wf WHERE application_id = :id');
            $upd->execute([
                'status' => 'scheduled',
                'wf' => json_encode($wf, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'id' => $applicationId,
            ]);
        } catch (Throwable $e) {
            error_log('[schedule save] Application workflow update failed: ' . $e->getMessage());
            setFlash('error', 'The schedule was saved, but the application status could not be updated.');
            redirectTo('coordinator/schedule/manage.php');
        }
    } else {
        updateApplicationRecord($applicationId, ['status' => 'scheduled']);
    }

    setFlash('success', 'Presentation schedule saved successfully.');
    redirectTo('coordinator/schedule/manage.php');
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Schedule Presentation</h2>
    <p>Set presentation date, venue, and assign panel members. Presentation stages shown depend on the selected student's course type.</p>
</div>

<form method="post" action="<?= url('coordinator/schedule/add.php') ?>" data-validate>
    <div class="card">
        <div class="card-header"><h3>Student &amp; Stage</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-field">
                    <label>Student <span class="required">*</span></label>
                    <select name="studentEmail" id="schedStudent" required>
                        <option value="">Select student</option>
                        <?php foreach ($students as $s): ?>
                        <option value="<?= htmlspecialchars($s['email']) ?>" <?= $s['email'] === $preselected ? 'selected' : '' ?>>
                            <?= htmlspecialchars(studentDisplayName($s)) ?> (<?= htmlspecialchars($s['program']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label>Course Type</label>
                    <input type="text" id="schedTrack" value="" readonly>
                </div>
            </div>
            <div class="form-row">
                <div class="form-field">
                    <label>Presentation Stage <span class="required">*</span></label>
                    <select name="stage" id="schedStage" required>
                        <option value="">Select a student first</option>
                    </select>
                    <p class="field-hint" id="schedStageHint"></p>
                </div>
                <div class="form-field">
                    <label>Program</label>
                    <input type="text" id="schedProgram" value="" readonly>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Schedule Details</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-field">
                    <label>Presentation Date <span class="required">*</span></label>
                    <input type="date" name="date" required>
                </div>
                <div class="form-field">
                    <label>Presentation Time <span class="required">*</span></label>
                    <input type="time" name="time" required>
                </div>
            </div>
            <div class="form-field">
                <label>Venue <span class="required">*</span></label>
                <input type="text" name="venue" required placeholder="e.g. CSITE Seminar Room, Room 204">
            </div>
            <div class="form-field">
                <label>Schedule Status</label>
                <select name="status">
                    <option value="pending">Pending Confirmation</option>
                    <option value="confirmed">Confirmed</option>
                </select>
            </div>
            <div style="margin-top:0.5rem;padding:0.9rem 1rem;background:rgba(37,99,235,0.04);border-radius:var(--radius);border:1px solid rgba(37,99,235,0.12);font-size:0.82rem;">
                <p style="font-weight:700;color:var(--gray-700);margin-bottom:0.5rem;"><i class="fas fa-clipboard-check" style="margin-right:5px;color:var(--adzu-navy);"></i>Prerequisites before scheduling:</p>
                <?php foreach (['Paper submitted and verified','Adviser endorsement verified','Graduate School endorsement recorded','Official receipt / payment recorded','Stage marked as Ready for Presentation'] as $req): ?>
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.2rem;">
                    <i class="fas fa-check-circle" style="color:var(--success);font-size:0.75rem;"></i>
                    <span style="color:var(--gray-600);"><?= htmlspecialchars($req) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3>Panel Assignment</h3>
            <a href="<?= url('coordinator/panel/add.php') ?>" class="btn btn-outline" style="font-size: 0.82rem; padding: 0.35rem 0.75rem;">
                <i class="fas fa-plus"></i> Add Panel Member
            </a>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-field">
                    <label>Panel Member 1 <span class="required">*</span></label>
                    <select name="panel1" required>
                        <option value="">Select panel member</option>
                        <?php foreach ($panelOpts as $p): ?><option><?= htmlspecialchars($p) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label>Panel Member 2 <span class="required">*</span></label>
                    <select name="panel2" required>
                        <option value="">Select panel member</option>
                        <?php foreach ($panelOpts as $p): ?><option><?= htmlspecialchars($p) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-field">
                    <label>Panel Member 3 <span class="required">*</span></label>
                    <select name="panel3" required>
                        <option value="">Select panel member</option>
                        <?php foreach ($panelOpts as $p): ?><option><?= htmlspecialchars($p) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label>Adviser <span class="required">*</span></label>
                    <select name="adviser" required>
                        <option value="">Select adviser</option>
                        <?php foreach ($adviserOpts as $adviser): $isUnavailable = ($adviser['availability'] ?? '') !== 'available'; ?>
                        <option <?= $isUnavailable ? 'disabled' : '' ?>><?= htmlspecialchars($adviser['name']) ?><?= $isUnavailable ? ' [Unavailable]' : '' ?></option>
                        <?php endforeach; ?>
                        <?php if (!$adviserOpts): ?><option value="" disabled>No available advisers found</option><?php endif; ?>
                    </select>
                </div>
            </div>
            <div class="form-field">
                <label>Documentor <span class="required">*</span></label>
                <select name="documentor" required>
                    <option value="">Select documentor</option>
                    <?php foreach ($panelOpts as $p): ?><option><?= htmlspecialchars($p) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-calendar-check"></i> Finalize Schedule</button>
        <a href="<?= url('coordinator/schedule/manage.php') ?>" class="btn btn-outline">Cancel</a>
    </div>
</form>

<script>
(function () {
    const map = <?= json_encode($stageMap) ?>;
    const student = document.getElementById('schedStudent');
    const stage = document.getElementById('schedStage');
    const track = document.getElementById('schedTrack');
    const program = document.getElementById('schedProgram');
    const hint = document.getElementById('schedStageHint');
    function fill() {
        const info = map[student.value];
        stage.innerHTML = '';
        if (!info) {
            track.value = '';
            program.value = '';
            hint.textContent = '';
            stage.appendChild(new Option('Select a student first', ''));
            return;
        }
        track.value = info.track;
        program.value = info.program;
        hint.textContent = 'Stages shown for ' + info.track + ' track only.';
        stage.appendChild(new Option('Select stage', ''));
        info.stages.forEach(s => stage.appendChild(new Option(s, s)));
    }
    student.addEventListener('change', fill);
    fill();
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>