<?php
require_once __DIR__ . '/../../includes/config.php';

$pageTitle   = 'Manage Applications';
$role        = 'coordinator';
$currentPage = 'applications';
$userName    = $mockCoordinator['name'];

if (($_GET['delete'] ?? '') !== '') {
    deleteApplicationRecord((int) $_GET['delete']);
    redirectTo('coordinator/applications/manage.php');
}

$applications = storeGet('applications');

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Application Management</h2>
    <p>View and process submitted student applications by track and program.</p>
</div>

<div class="card">
    <div class="card-header">
        <h3>Submitted Applications <span class="filter-count" data-filter-count></span></h3>
    </div>
    <div class="card-body">
        <div class="filter-bar" data-filter-table="#appsTable">
            <div class="filter-search">
                <i class="fas fa-search"></i>
                <input type="search" data-filter-q placeholder="Search by name, title, program, stage, status...">
            </div>
            <button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button>
            <select data-filter="track">
                <option value="">All Tracks</option>
                <option>Thesis</option>
                <option>Capstone</option>
                <option>Seminar Paper</option>
            </select>
            <select data-filter="status">
                <option value="">All Statuses</option>
                <?php foreach (STATUSES as $key => $info): ?>
                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($info['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <select data-filter="program">
                <option value="">All Programs</option>
                <?php foreach (array_keys(PROGRAMS) as $code): ?>
                <option><?= htmlspecialchars($code) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="table-responsive">
            <table class="data-table" id="appsTable">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Program</th>
                        <th>Track</th>
                        <th>Stage</th>
                        <th>Title</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app):
                        $track = getTrackLabel($app['track'] ?? getTrackForProgram($app['program']));
                    ?>
                    <tr data-status="<?= htmlspecialchars($app['status']) ?>"
                        data-program="<?= htmlspecialchars($app['program']) ?>"
                        data-track="<?= htmlspecialchars($track) ?>"
                        data-search="<?= htmlspecialchars(strtolower($app['student'] . ' ' . $app['program'] . ' ' . $app['stage'] . ' ' . $app['title'] . ' ' . $app['status'] . ' ' . $track)) ?>">
                        <td><strong><?= htmlspecialchars($app['student']) ?></strong></td>
                        <td><?= htmlspecialchars($app['program']) ?></td>
                        <td><span style="font-size:0.75rem;background:var(--gray-100);padding:2px 8px;border-radius:99px;"><?= htmlspecialchars($track) ?></span></td>
                        <td><?= htmlspecialchars($app['stage']) ?></td>
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($app['title']) ?></td>
                        <td><?= date('M d, Y', strtotime($app['date'])) ?></td>
                        <td><?= statusBadge($app['status']) ?></td>
                        <td class="actions">
                            <a href="<?= url('coordinator/applications/view.php?id=' . $app['id']) ?>" class="btn btn-sm btn-outline" title="View"><i class="fas fa-eye"></i></a>
                            <a href="<?= url('coordinator/applications/process.php?id=' . $app['id']) ?>" class="btn btn-sm btn-primary" title="Process"><i class="fas fa-cog"></i></a>
                            <?= coordDeleteLink(url('coordinator/applications/manage.php?delete=' . $app['id']), 'Delete this application? This cannot be undone.') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr data-filter-empty <?= $applications ? 'hidden' : '' ?>>
                        <td colspan="8" style="text-align:center;color:var(--gray-400);padding:2rem;">No applications match your search or filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
