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
    <p>Generate and review official system reports. Detailed statistics are kept on this page to keep the main dashboard concise.</p>
</div>

<div class="report-summary">
    <div class="report-summary-header">
        <h3>Quick Summary</h3>
        <span>Current academic year</span>
    </div>
    <div class="report-summary-body">
        <div class="report-stat-grid">
            <div class="report-stat">
                <div class="report-stat-icon"><i class="fas fa-spinner"></i></div>
                <div>
                    <div class="report-stat-value"><?= $ongoing ?></div>
                    <div class="report-stat-label">Ongoing Students</div>
                </div>
            </div>
            <div class="report-stat">
                <div class="report-stat-icon"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div class="report-stat-value"><?= $completed ?></div>
                    <div class="report-stat-label">Completed This Year</div>
                </div>
            </div>
            <div class="report-stat">
                <div class="report-stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <div>
                    <div class="report-stat-value">5</div>
                    <div class="report-stat-label">Active Panel Members</div>
                </div>
            </div>
            <div class="report-stat">
                <div class="report-stat-icon"><i class="fas fa-chalkboard"></i></div>
                <div>
                    <div class="report-stat-value">24</div>
                    <div class="report-stat-label">Presentations, SY 2026-2027</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="report-grid">
    <a href="<?= url('coordinator/reports/students.php') ?>" class="report-link-card">
        <div class="report-link-icon"><i class="fas fa-user-graduate"></i></div>
        <h3>Student Reports</h3>
        <p>Semester-based listing of ongoing and completed students, organized by capstone, thesis, and seminar paper progress.</p>
        <span class="report-link-cta">View report <i class="fas fa-arrow-right"></i></span>
    </a>
    <a href="<?= url('coordinator/reports/panels.php') ?>" class="report-link-card">
        <div class="report-link-icon"><i class="fas fa-users"></i></div>
        <h3>Panel Participation Reports</h3>
        <p>Summary of panel member assignments and how often each member participates in scheduled presentations.</p>
        <span class="report-link-cta">View report <i class="fas fa-arrow-right"></i></span>
    </a>
</div>


<?php require_once __DIR__ . '/../../includes/footer.php'; ?>