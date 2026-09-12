<?php
require_once __DIR__ . '/../../includes/config.php';

$pageTitle   = 'Graduate School Endorsement';
$role        = 'coordinator';
$currentPage = 'applications';
$userName    = $mockCoordinator['name'];

$app = findApplication((int) ($_GET['id'] ?? 0)) ?? (storeGet('applications')[0] ?? null);

$studentName = $app['student'] ?? $mockStudent['name'];
$programName = $app ? (PROGRAMS[$app['program']] ?? $app['program']) : $mockStudent['program_name'];
$title = $app['title'] ?? $mockStudent['title'];
$stage = $app['stage'] ?? 'Proposal Presentation';
$adviser = $app['adviser'] ?? $mockStudent['adviser'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header no-print">
    <h2>Graduate School Endorsement Form</h2>
    <p>Auto-populated endorsement for the Graduate School Administrative Office.</p>
</div>

<div class="form-actions no-print" style="margin-bottom:1.5rem;border:none;padding:0;">
    <button class="btn btn-primary" data-print><i class="fas fa-print"></i> Print Endorsement</button>
    <a href="<?= url('coordinator/applications/view.php' . ($app ? '?id=' . $app['id'] : '')) ?>" class="btn btn-outline">Back to Application</a>
</div>

<div class="card" id="endorsementForm">
    <div class="card-body" style="padding:2rem;">
        <div style="text-align:center;margin-bottom:2rem;">
            <h2 style="color:var(--adzu-navy);font-size:1.25rem;">ATENEO DE ZAMBOANGA UNIVERSITY</h2>
            <p style="font-size:0.9rem;color:var(--gray-600);">Graduate School – Endorsement for Presentation</p>
        </div>

        <div class="detail-grid" style="margin-bottom:2rem;">
            <div class="detail-item"><label>Student Name</label><span><?= htmlspecialchars($studentName) ?></span></div>
            <div class="detail-item"><label>Program</label><span><?= htmlspecialchars($programName) ?></span></div>
            <div class="detail-item" style="grid-column:1/-1;"><label>Research / Paper Title</label><span><?= htmlspecialchars($title) ?></span></div>
            <div class="detail-item"><label>Presentation Stage</label><span><?= htmlspecialchars($stage) ?></span></div>
            <div class="detail-item"><label>Presentation Date</label><span>April 10, 2025</span></div>
        </div>

        <h3 style="font-size:1rem;color:var(--adzu-navy);margin-bottom:1rem;border-bottom:1px solid var(--gray-200);padding-bottom:0.5rem;">Panel Composition</h3>
        <div class="table-responsive" style="margin-bottom:2rem;">
            <table class="data-table">
                <thead><tr><th>Role</th><th>Name</th><th>Qualification</th></tr></thead>
                <tbody>
                    <tr><td>Panel Member 1</td><td>Dr. Juan Dela Cruz</td><td>PhD in Computer Science</td></tr>
                    <tr><td>Panel Member 2</td><td>Dr. Ana Reyes</td><td>PhD in Information Technology</td></tr>
                    <tr><td>Panel Member 3</td><td>Prof. Miguel Santos</td><td>MS in Computer Science</td></tr>
                    <tr><td>Adviser</td><td><?= htmlspecialchars($adviser) ?></td><td>PhD in Computer Science</td></tr>
                    <tr><td>Documentor</td><td>Prof. Lisa Fernandez</td><td>MS in Information Technology</td></tr>
                </tbody>
            </table>
        </div>

        <div style="margin-top:3rem;display:grid;grid-template-columns:1fr 1fr;gap:3rem;">
            <div>
                <p style="border-top:1px solid var(--gray-400);padding-top:0.5rem;font-size:0.85rem;">
                    <strong><?= htmlspecialchars($mockCoordinator['name']) ?></strong><br>
                    Graduate Program Coordinator – CSITE<br>
                    Date: <?= date('F d, Y') ?>
                </p>
            </div>
            <div>
                <p style="border-top:1px solid var(--gray-400);padding-top:0.5rem;font-size:0.85rem;">
                    <strong>Graduate School Administrative Office</strong><br>
                    Received by: ___________________<br>
                    Date: ___________________
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
