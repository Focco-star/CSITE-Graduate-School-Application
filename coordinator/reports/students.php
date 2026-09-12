<?php
require_once __DIR__ . '/../../includes/config.php';

$pageTitle   = 'Student Reports';
$role        = 'coordinator';
$currentPage = 'reports';
$userName    = $mockCoordinator['name'];

$ongoing = [
    ['name' => 'Robbie Ryan A. Torres', 'program' => 'MSCS', 'stage' => 'Proposal', 'since' => '2024-08'],
    ['name' => 'Sean Benedict D. Recto', 'program' => 'MSCS', 'stage' => 'Concept', 'since' => '2024-08'],
    ['name' => 'Marc Laurence M. Arbillera', 'program' => 'MIT', 'stage' => 'Proposal', 'since' => '2024-01'],
];

$completed = [
    ['name' => 'Jane Doe', 'program' => 'MSCS', 'completed' => '2024-12', 'title' => 'Machine Learning for Healthcare'],
    ['name' => 'John Smith', 'program' => 'MIT', 'completed' => '2024-11', 'title' => 'Mobile App for Disaster Response'],
];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Student Reports</h2>
    <p>Semester-based reports of students by capstone/thesis progress.</p>
</div>

<div class="card">
    <div class="card-header">
        <h3>Filter</h3>
        <div style="display:flex;gap:0.5rem;">
            <select style="padding:0.4rem 0.75rem;border:1px solid var(--gray-300);border-radius:var(--radius);font-size:0.85rem;">
                <option>1st Semester 2024–2025</option>
                <option>2nd Semester 2024–2025</option>
                <option>1st Semester 2025–2026</option>
            </select>
            <button class="btn btn-sm btn-outline"><i class="fas fa-download"></i> Export</button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Ongoing Students (10)</h3></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Student</th><th>Program</th><th>Current Stage</th><th>Since</th></tr></thead>
                <tbody>
                    <?php foreach ($ongoing as $s): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                        <td><?= htmlspecialchars($s['program']) ?></td>
                        <td><?= htmlspecialchars($s['stage']) ?></td>
                        <td><?= htmlspecialchars($s['since']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Completed Students (5)</h3></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Student</th><th>Program</th><th>Title</th><th>Completed</th></tr></thead>
                <tbody>
                    <?php foreach ($completed as $s): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                        <td><?= htmlspecialchars($s['program']) ?></td>
                        <td><?= htmlspecialchars($s['title']) ?></td>
                        <td><?= htmlspecialchars($s['completed']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="form-actions">
    <a href="<?= url('coordinator/reports/dashboard.php') ?>" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Reports</a>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
