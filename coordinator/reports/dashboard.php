<?php
require_once __DIR__ . '/../../includes/config.php';

$pageTitle   = 'Reports';
$role        = 'coordinator';
$currentPage = 'reports';
$userName    = $mockCoordinator['name'];
$students    = storeGet('students');
$ongoing     = count(array_filter($students, static function ($s) {
    return !in_array($s['status'], ['completed', 'approved'], true);
}));
$completed   = count($students) - $ongoing;

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Reports Dashboard</h2>
    <p>Generate and view system reports. Detailed statistics are kept here to avoid cluttering the main dashboard.</p>
</div>

<div class="info-grid">
    <a href="<?= url('coordinator/reports/students.php') ?>" class="info-card" style="text-decoration:none;">
        <div class="icon"><i class="fas fa-user-graduate"></i></div>
        <h3>Student Reports</h3>
        <p>Semester-based reports of ongoing and completed students by capstone/thesis progress.</p>
    </a>
    <a href="<?= url('coordinator/reports/panels.php') ?>" class="info-card" style="text-decoration:none;">
        <div class="icon"><i class="fas fa-users"></i></div>
        <h3>Panel Participation Reports</h3>
        <p>Summary of panel member assignments and participation frequency.</p>
    </a>
</div>

<div class="card" style="margin-top:1.5rem;">
    <div class="card-header"><h3>Quick Summary</h3></div>
    <div class="card-body">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-spinner"></i></div>
                <div class="stat-value"><?= $ongoing ?></div>
                <div class="stat-label">Ongoing Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value"><?= $completed ?></div>
                <div class="stat-label">Completed (This Year)</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="stat-value">5</div>
                <div class="stat-label">Active Panel Members</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-presentation"></i></div>
                <div class="stat-value">24</div>
                <div class="stat-label">Presentations (AY 2024–25)</div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
