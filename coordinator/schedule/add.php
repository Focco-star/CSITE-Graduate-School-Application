<?php
require_once __DIR__ . '/../../includes/config.php';

$pageTitle   = 'Schedule Presentation';
$role        = 'coordinator';
$currentPage = 'schedule';
$userName    = $mockCoordinator['name'];

$students = storeGet('students');
$panelOpts = panelSelectOptions();
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
    $email = $_POST['studentEmail'] ?? '';
    $st = findStudentByEmail($email);
    $stage = trim($_POST['stage'] ?? '');
    if ($st && $stage !== '') {
        $displayDate = trim($_POST['date'] ?? '');
        if ($displayDate !== '') {
            $ts = strtotime($displayDate);
            if ($ts) {
                $displayDate = date('M j, Y', $ts);
            }
        }
        $displayTime = trim($_POST['time'] ?? '');
        if ($displayTime !== '') {
            $tts = strtotime('2000-01-01 ' . $displayTime);
            if ($tts) {
                $displayTime = date('g:i A', $tts);
            }
        }
        $panel = implode(', ', array_filter([$_POST['panel1'] ?? '', $_POST['panel2'] ?? '', $_POST['panel3'] ?? '']));
        $app = latestApplicationForEmail($email);
        addScheduleRecord([
            'studentEmail' => $email,
            'studentName' => studentDisplayName($st),
            'applicationId' => $app['id'] ?? null,
            'stage' => $stage,
            'date' => $displayDate,
            'time' => $displayTime,
            'venue' => trim($_POST['venue'] ?? ''),
            'panel' => $panel ?: 'TBD',
            'adviser' => trim($_POST['adviser'] ?? ''),
            'documentor' => trim($_POST['documentor'] ?? ''),
            'status' => ($_POST['status'] ?? 'pending') === 'confirmed' ? 'confirmed' : 'pending',
        ]);
        if ($app) {
            updateApplicationRecord((int) $app['id'], ['status' => 'scheduled']);
        }
        redirectTo('coordinator/schedule/manage.php');
    }
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
        <div class="card-header"><h3>Panel Assignment</h3></div>
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
                        <option>Dr. Maria Santos</option>
                        <option>Dr. Juan Dela Cruz</option>
                        <option>Dr. Ana Reyes</option>
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
