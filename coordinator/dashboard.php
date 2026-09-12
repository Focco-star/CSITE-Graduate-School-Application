<?php
require_once __DIR__ . '/../includes/config.php';

$pageTitle   = 'Coordinator Dashboard';
$role        = 'coordinator';
$currentPage = 'dashboard';
$userName    = $mockCoordinator['name'];

$pendingApps = array_values(array_filter(storeGet('applications'), static function ($a) {
    return in_array($a['status'], ['submitted', 'under_review'], true);
}));
$students = storeGet('students');
$activeStudents = array_values(array_filter($students, static function ($s) {
    return !in_array($s['status'], ['completed', 'approved'], true);
}));

$upcomingPresentations = storeGet('schedules');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h2>Coordinator Dashboard</h2>
    <p>Overview of applications, submissions, and upcoming presentations requiring your attention.</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-inbox"></i></div>
        <div class="stat-value"><?= count($pendingApps) ?></div>
        <div class="stat-label">Pending Applications</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon gold"><i class="fas fa-file-alt"></i></div>
        <div class="stat-value"><?= count(storeGet('applications')) ?></div>
        <div class="stat-label">Total Applications</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-calendar-alt"></i></div>
        <div class="stat-value"><?= count($upcomingPresentations) ?></div>
        <div class="stat-label">Upcoming Presentations</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-value"><?= count($activeStudents) ?></div>
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
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Student</th><th>Stage</th><th>Date</th><th>Time</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($upcomingPresentations as $pres): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($pres['studentName'] ?? $pres['student'] ?? '') ?></strong></td>
                        <td><?= htmlspecialchars($pres['stage']) ?></td>
                        <td><?= htmlspecialchars($pres['date']) ?></td>
                        <td><?= htmlspecialchars($pres['time']) ?></td>
                        <td><a href="<?= url('coordinator/schedule/edit.php?id=' . urlencode($pres['id'] ?? '')) ?>" class="btn btn-sm btn-outline">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
