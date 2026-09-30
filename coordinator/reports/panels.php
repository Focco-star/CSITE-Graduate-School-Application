<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

$pageTitle   = 'Panel Participation Reports';
$role        = 'coordinator';
$currentPage = 'reports';
$userName    = $mockCoordinator['name'];

$panelReport = databasePanelParticipation();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Panel Participation Reports</h2>
    <p>Summary of panel member assignments and service frequency for capstone/thesis presentations.</p>
</div>

<div class="card">
    <div class="card-header">
        <h3>Panel Participation Summary</h3>
        <button class="btn btn-sm btn-outline"><i class="fas fa-download"></i> Export Report</button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Panel Member</th><th>Qualification</th><th>Times Served</th><th>Last Assignment</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($panelReport as $p): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                        <td><?= htmlspecialchars($p['qualification']) ?></td>
                        <td><?= (int) $p['times'] ?></td>
                        <td><?= !empty($p['last_assignment']) ? date('M d, Y', strtotime($p['last_assignment'])) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Participation Overview</h3></div>
    <div class="card-body">
        <div class="stats-grid">
            <?php foreach (array_slice($panelReport, 0, 4) as $p): ?>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-user"></i></div>
                <div class="stat-value"><?= (int) $p['times'] ?></div>
                <div class="stat-label"><?= htmlspecialchars(explode(' ', $p['name'])[1] ?? $p['name']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <p style="font-size:0.8rem;color:var(--gray-400);margin-top:1rem;text-align:center;">
            Detailed charts can be added here in a future iteration. Summary counts are shown above.
        </p>
    </div>
</div>

<div class="form-actions">
    <a href="<?= url('coordinator/reports/dashboard.php') ?>" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Reports</a>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
