<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/program_chart.php';

$pageTitle   = 'Reports';
$role        = 'coordinator';
$currentPage = 'reports';
$userName    = $mockCoordinator['name'] ?? 'Coordinator';

$students = function_exists('storeGet') ? (array) (storeGet('students') ?? []) : [];
$ongoing  = count(array_filter($students, static function ($s) {
    return !in_array($s['status'] ?? '', ['completed', 'approved'], true);
}));
$completed = count($students) - $ongoing;

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

<?php renderProgramPopulationChart($pdo ?? null); ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>