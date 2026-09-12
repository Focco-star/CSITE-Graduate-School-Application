<?php
require_once __DIR__ . '/../../includes/config.php';

$pageTitle   = 'Student Profile';
$role        = 'coordinator';
$currentPage = 'students';
$userName    = $mockCoordinator['name'];

$rec = findStudentById((string) ($_GET['id'] ?? '')) ?? findStudentByEmail($mockStudent['email']);
$profile = $rec ? currentStudentProfile(array_merge($mockStudent, [
    'email' => $rec['email'],
])) : $mockStudent;
$latest = latestApplicationForEmail($profile['email']);
$stages = getStagesForTrack($profile['track']);
$studentSchedules = schedulesForEmail($profile['email']);
$latestSchedule = $studentSchedules ? end($studentSchedules) : null;

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Student Profile</h2>
    <p>Detailed capstone/thesis information for <?= htmlspecialchars($profile['name']) ?>.</p>
</div>

<div class="card">
    <div class="card-header"><h3>Personal Information</h3></div>
    <div class="card-body">
        <div class="detail-grid">
            <div class="detail-item"><label>Student Name</label><span><?= htmlspecialchars($profile['name']) ?></span></div>
            <div class="detail-item"><label>Program</label><span><?= htmlspecialchars($profile['program_name']) ?></span></div>
            <div class="detail-item"><label>Email</label><span><?= htmlspecialchars($profile['email']) ?></span></div>
            <div class="detail-item"><label>Track</label><span><?= htmlspecialchars(($rec['trackLabel'] ?? getTrackLabel($profile['track']))) ?></span></div>
            <div class="detail-item"><label>Age</label><span><?= htmlspecialchars(($rec['age'] ?? '') !== '' ? $rec['age'] : '—') ?></span></div>
            <div class="detail-item"><label>Gender</label><span><?= htmlspecialchars(($rec['gender'] ?? '') !== '' ? $rec['gender'] : '—') ?></span></div>
            <div class="detail-item"><label>Current Status</label><span><?= statusBadge($profile['status']) ?></span></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Important Dates</h3></div>
    <div class="card-body">
        <div class="detail-grid">
            <div class="detail-item"><label>Enrollment / Start Date</label><span><?= date('F d, Y', strtotime($profile['enroll_date'])) ?></span></div>
            <div class="detail-item"><label>Application Date</label><span><?= $latest ? date('F d, Y', strtotime($latest['date'])) : '—' ?></span></div>
            <div class="detail-item"><label>Completion Deadline (3 Years)</label><span><?= date('F d, Y', strtotime($profile['completion_deadline'])) ?></span></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Capstone/Thesis Information</h3></div>
    <div class="card-body">
        <div class="detail-grid">
            <div class="detail-item" style="grid-column:1/-1;"><label>Research Title</label><span><?= htmlspecialchars($latest['title'] ?? $profile['title'] ?: '—') ?></span></div>
            <div class="detail-item"><label>Adviser</label><span><?= htmlspecialchars($latest['adviser'] ?? $profile['adviser'] ?: '—') ?></span></div>
            <div class="detail-item"><label>Current Stage</label><span><?= htmlspecialchars(displayStageLabel($rec ?: ['track' => $profile['track'], 'program' => $profile['program'], 'currentStage' => $profile['current_stage']])) ?></span></div>
        </div>
    </div>
</div>

<div class="form-actions">
    <?php if ($latest): ?>
    <a href="<?= url('coordinator/applications/view.php?id=' . $latest['id']) ?>" class="btn btn-primary"><i class="fas fa-file-alt"></i> View Application</a>
    <?php endif; ?>
    <?php if ($latestSchedule): ?>
    <a href="<?= url('coordinator/schedule/notification.php?id=' . urlencode($latestSchedule['id'])) ?>" class="btn btn-secondary"><i class="fas fa-file-export"></i> Notify Documentor</a>
    <?php endif; ?>
    <a href="<?= url('coordinator/students/manage.php') ?>" class="btn btn-outline">Back to List</a>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
