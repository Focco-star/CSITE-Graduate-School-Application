<?php /* Opens the PHP block for the endorsement form page. */ ?>
<?php
// Loads shared config helpers and demo data stores.
require_once __DIR__ . '/../../includes/config.php';
// Loads the DB class for database access.
require_once __DIR__ . '/../../includes/db.php';

// Sets the browser tab title to Graduate School Endorsement.
$pageTitle   = 'Graduate School Endorsement';
// Marks the page as part of the coordinator area.
$role        = 'coordinator';
// Highlights the Applications menu in the sidebar.
$currentPage = 'applications';
// Gets the logged-in coordinator's name for the top bar.
$userName    = currentCoordinatorName();

// Loads the application by URL id, or the first stored one as fallback.
$app = findApplication((int) ($_GET['id'] ?? 0)) ?? (storeGet('applications')[0] ?? null);

// Sets the student name from the application or the demo student.
$studentName = $app['student'] ?? $mockStudent['name'];
// Turns the program code into its full name, or uses the demo program.
$programName = $app ? (PROGRAMS[$app['program']] ?? $app['program']) : $mockStudent['program_name'];
// Sets the research title from the application or the demo student.
$title = $app['title'] ?? $mockStudent['title'];
// Sets the presentation stage, defaulting to Proposal Presentation.
$stage = $app['stage'] ?? 'Proposal Presentation';
// Sets the adviser name from the application or the demo student.
$adviser = $app['adviser'] ?? $mockStudent['adviser'];
// Gets the coordinator name that will sign the form.
$coordinatorName = currentCoordinatorName();
// Loads the panel members scheduled for this application.
$endorsementPanel = databaseSchedulePanelMembers((int) ($app['id'] ?? 0));
// Checks whether no panel members were found.
if (!$endorsementPanel) {
    // Falls back to the first four members of the general panel pool.
    $endorsementPanel = array_slice(databasePanelMembers(), 0, 4);
// Closes the panel fallback check.
}

// Loads the shared header with sidebar.
require_once __DIR__ . '/../../includes/header.php';
// Closes PHP so the HTML below is printed.
?>

<?php /* Opens the page banner, hidden when printing. */ ?>
<div class="page-header page-banner no-print">
<?php /*     Shows the Graduate School Endorsement Form heading. */ ?>
    <h2>Graduate School Endorsement Form</h2>
<?php /*     Explains that the form is filled in automatically. */ ?>
    <p>Auto-populated endorsement for the Graduate School Administrative Office.</p>
<?php /* Closes the page banner. */ ?>
</div>

<?php /* Opens a toolbar that is hidden when printing. */ ?>
<div class="form-actions no-print" style="margin-bottom:1.5rem;border:none;padding:0;">
<?php /*     Button that sends the endorsement form to the printer. */ ?>
    <button class="btn btn-primary" data-print><i class="fas fa-print"></i> Print Endorsement</button>
<?php /*     Link back to the full application page. */ ?>
    <a href="<?= url('coordinator/applications/view.php' . ($app ? '?id=' . $app['id'] : '')) ?>" class="btn btn-outline">Back to Application</a>
<?php /* Closes the print toolbar. */ ?>
</div>

<?php /* Opens the printable endorsement card. */ ?>
<div class="card" id="endorsementForm">
<?php /*     Opens the card body with extra padding. */ ?>
    <div class="card-body" style="padding:2rem;">
<?php /*         Centres the school letterhead block. */ ?>
        <div style="text-align:center;margin-bottom:2rem;">
<?php /*             Prints the university name as the letterhead. */ ?>
            <h2 style="color:var(--adzu-navy);font-size:1.25rem;">ATENEO DE ZAMBOANGA UNIVERSITY</h2>
<?php /*             Prints the endorsement subtitle for the Graduate School. */ ?>
            <p style="font-size:0.9rem;color:var(--gray-600);">Graduate School – Endorsement for Presentation</p>
<?php /*         Closes the letterhead block. */ ?>
        </div>

<?php /*         Opens a grid of student and presentation details. */ ?>
        <div class="detail-grid" style="margin-bottom:2rem;">
<?php /*             Shows the student name safely escaped. */ ?>
            <div class="detail-item"><label>Student Name</label><span><?= htmlspecialchars($studentName) ?></span></div>
<?php /*             Shows the full program name. */ ?>
            <div class="detail-item"><label>Program</label><span><?= htmlspecialchars($programName) ?></span></div>
<?php /*             Shows the research title across the whole grid row. */ ?>
            <div class="detail-item" style="grid-column:1/-1;"><label>Research / Paper Title</label><span><?= htmlspecialchars($title) ?></span></div>
<?php /*             Shows which presentation stage this endorsement is for. */ ?>
            <div class="detail-item"><label>Presentation Stage</label><span><?= htmlspecialchars($stage) ?></span></div>
<?php /*             Shows the fixed presentation date for the form. */ ?>
            <div class="detail-item"><label>Presentation Date</label><span>April 10, 2025</span></div>
<?php /*         Closes the details grid. */ ?>
        </div>

<?php /*         Heading for the panel composition table. */ ?>
        <h3 style="font-size:1rem;color:var(--adzu-navy);margin-bottom:1rem;border-bottom:1px solid var(--gray-200);padding-bottom:0.5rem;">Panel Composition</h3>
<?php /*         Wrapper allowing the panel table to scroll. */ ?>
        <div class="table-responsive" style="margin-bottom:2rem;">
<?php /*             Opens the panel table. */ ?>
            <table class="data-table">
<?php /*                 Header row with Role, Name and Qualification columns. */ ?>
                <thead><tr><th>Role</th><th>Name</th><th>Qualification</th></tr></thead>
<?php /*                 Opens the panel table body. */ ?>
                <tbody>
<?php /*                     Loops over the first three panel members with their index. */ ?>
                    <?php foreach (array_slice($endorsementPanel, 0, 3) as $index => $member): ?>
<?php /*                     Prints each member's role number, name and qualification. */ ?>
                    <tr><td>Panel Member <?= $index + 1 ?></td><td><?= htmlspecialchars($member['name']) ?></td><td><?= htmlspecialchars($member['qualification']) ?></td></tr>
<?php /*                     Ends the panel member loop. */ ?>
                    <?php endforeach; ?>
<?php /*                     Adds the adviser row with a fixed qualification text. */ ?>
                    <tr><td>Adviser</td><td><?= htmlspecialchars($adviser) ?></td><td>PhD in Computer Science</td></tr>
<?php /*                     Prints a documentor row when a fourth panel member exists. */ ?>
                    <?php if (isset($endorsementPanel[3])): ?><tr><td>Documentor</td><td><?= htmlspecialchars($endorsementPanel[3]['name']) ?></td><td><?= htmlspecialchars($endorsementPanel[3]['qualification']) ?></td></tr><?php endif; ?>
<?php /*                 Closes the panel table body. */ ?>
                </tbody>
<?php /*             Closes the panel table. */ ?>
            </table>
<?php /*         Closes the scrolling wrapper. */ ?>
        </div>

<?php /*         Opens a two-column signature area with a top margin. */ ?>
        <div style="margin-top:3rem;display:grid;grid-template-columns:1fr 1fr;gap:3rem;">
<?php /*             Opens the left column for the coordinator signature. */ ?>
            <div>
<?php /*                 Opens the signature paragraph with a top border line. */ ?>
                <p style="border-top:1px solid var(--gray-400);padding-top:0.5rem;font-size:0.85rem;">
<?php /*                     Prints the coordinator name above the signature line. */ ?>
                    <strong><?= htmlspecialchars($coordinatorName) ?></strong><br>
<?php /*                     Prints the coordinator's role title. */ ?>
                    Graduate Program Coordinator – CSITE<br>
<?php /*                     Prints today's date under the signature. */ ?>
                    Date: <?= date('F d, Y') ?>
<?php /*                 Closes the coordinator signature paragraph. */ ?>
                </p>
<?php /*             Closes the left signature column. */ ?>
            </div>
<?php /*             Opens the right column for the receiving office. */ ?>
            <div>
<?php /*                 Opens the receiving office paragraph with a top border. */ ?>
                <p style="border-top:1px solid var(--gray-400);padding-top:0.5rem;font-size:0.85rem;">
<?php /*                     Prints the Graduate School Administrative Office name. */ ?>
                    <strong>Graduate School Administrative Office</strong><br>
<?php /*                     Leaves a blank line for the receiver's signature. */ ?>
                    Received by: ___________________<br>
<?php /*                     Leaves a blank line for the received date. */ ?>
                    Date: ___________________
<?php /*                 Closes the receiving office paragraph. */ ?>
                </p>
<?php /*             Closes the right signature column. */ ?>
            </div>
<?php /*         Closes the two-column signature area. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the endorsement card. */ ?>
</div>

<?php /* Loads the shared footer scripts and closes PHP. */ ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
