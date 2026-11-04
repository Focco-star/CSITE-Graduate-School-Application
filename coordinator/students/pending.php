<?php /* Starts the coordinator account validation page. */ ?>
<?php
// Loads the shared settings and helper functions.
require_once __DIR__ . '/../../includes/config/config.php';
// Loads the database class used to review the student accounts.
require_once __DIR__ . '/../../includes/config/db.php';

// Adds the account validation columns if they are not in the database yet.
ensureAccountValidationSchema();

// Sets the browser tab title for the account validation page.
$pageTitle   = 'Account Validation';
// Tells the layout this page belongs to the coordinator portal.
$role        = 'coordinator';
// Marks the Account Validation link as active in the sidebar.
$currentPage = 'account_validation';
// Uses the signed-in coordinator name, or the demo name as a fallback.
$userName    = !empty($_SESSION['user']['full_name']) ? $_SESSION['user']['full_name'] : ($mockCoordinator['name'] ?? 'Coordinator');

// Handles the approve or reject buttons from the account list.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['review_user_id'])) {
    // Reads the student account being reviewed.
    $reviewUserId = (int) $_POST['review_user_id'];
    // Reads the coordinator decision.
    $decision = (string) ($_POST['decision'] ?? '');
    // Reads the coordinator user id from either session key style.
    $reviewerId = (int) ($_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0);
    // Converts the decision into the stored account status.
    $newStatus = $decision === 'approve' ? 'approved' : ($decision === 'reject' ? 'rejected' : '');
    // Only accepts a real decision for a real account.
    if ($reviewUserId > 0 && $newStatus !== '') {
        // Saves the decision and checks that a pending account was changed.
        if (setStudentAccountStatus($reviewUserId, $newStatus, $reviewerId)) {
            // Tells the coordinator the decision was saved.
            setFlash('success', $newStatus === 'approved'
                ? 'Student account approved. The student can now sign in.'
                : 'Student account rejected. The student cannot sign in and was told to contact the coordinator.');
        // Explains that the account was already reviewed or could not be changed.
        } else {
            // Shows a safe error when the account was already handled.
            setFlash('error', 'That account could not be updated. It may have already been reviewed.');
        // Closes the update result check.
        }
    // Closes the request check.
    }
    // Reloads the page so the list shows the new status.
    redirectTo('coordinator/students/pending.php');
// Closes the approval form handler.
}

// Loads the students whose accounts still need validation.
$pendingAccounts = pendingStudentAccounts();
// Loads the accounts the coordinator recently approved or rejected.
$reviewedAccounts = recentlyReviewedStudentAccounts(8);
// Counts the waiting accounts for the page heading.
$pendingCount = count($pendingAccounts);

// Includes the shared header with the sidebar and top bar.
require_once __DIR__ . '/../../includes/layout/header.php';
// Closes PHP so the page content below is printed.
?>

<?php /* Opens the page heading block. */ ?>
<div class="page-header">
<?php /*     Shows the page title. */ ?>
    <h2>Account Validation</h2>
<?php /*     Explains what the coordinator does on this page. */ ?>
    <p>Review new student accounts before the students can sign in to the portal.</p>
<?php /* Closes the page heading block. */ ?>
</div>

<?php /* Explains that the bell follows these waiting accounts. */ ?>
<div class="page-notice is-info account-validation-notice">
<?php /*     Bell icon matching the top bar notification. */ ?>
    <i class="fas fa-bell"></i>
<?php /*     Opens the notice text. */ ?>
    <span class="page-notice-text">
<?php /*         Shows how many accounts are waiting. */ ?>
        <strong><?= $pendingCount ?> student account<?= $pendingCount === 1 ? '' : 's' ?></strong>
<?php /*         Explains what the coordinator must do. */ ?>
        <?= $pendingCount === 1 ? 'is' : 'are' ?> waiting for validation. Approve an account to let the student sign in with the email and password chosen at registration.
<?php /*     Closes the notice text. */ ?>
    </span>
<?php /* Closes the notice box. */ ?>
</div>

<?php /* Opens the card that holds the waiting account table. */ ?>
<div class="card">
<?php /*     Card header showing the title and the live count. */ ?>
    <div class="card-header">
<?php /*         Card title with the account check icon. */ ?>
        <h3><i class="fas fa-user-check"></i> Waiting for Validation <span style="font-size:0.8rem;font-weight:400;color:var(--gray-500);">(<?= $pendingCount ?>)</span></h3>
<?php /*     Closes the card header. */ ?>
    </div>
<?php /*     Opens the card body around the table. */ ?>
    <div class="card-body">
<?php /*         Wrapper that lets the table scroll sideways on small screens. */ ?>
        <div class="table-responsive">
<?php /*             Starts the table of pending student accounts. */ ?>
            <table class="data-table">
<?php /*                 Opens the table header row. */ ?>
                <thead>
<?php /*                     Names the information the coordinator checks. */ ?>
                    <tr><th>Student</th><th>ADZU ID</th><th>Program &amp; Track</th><th>Details</th><th>Registered</th><th>Actions</th></tr>
<?php /*                 Closes the table header row. */ ?>
                </thead>
<?php /*                 Opens the table body. */ ?>
                <tbody>
<?php /*                     Shows a friendly empty row when nobody is waiting. */ ?>
                    <?php if (!$pendingAccounts): ?>
<?php /*                     Empty row spanning every column. */ ?>
                    <tr><td colspan="6" style="text-align:center;color:var(--gray-400);padding:2rem;"><i class="fas fa-circle-check" aria-hidden="true"></i> No student accounts are waiting for validation.</td></tr>
<?php /*                     Closes the empty row check. */ ?>
                    <?php endif; ?>
<?php /*                     Loops over every account waiting for validation. */ ?>
                    <?php foreach ($pendingAccounts as $account): ?>
<?php /*                     Builds the display name from the student profile or account row. */ ?>
                    <?php
                    // Reads the full name stored on the account.
                    $accountName = trim((string) ($account['full_name'] ?? ''));
                    // Builds the name from the profile when the account name is empty.
                    if ($accountName === '') {
                        // Uses the standard Last, First MI name format.
                        $accountName = canonicalStudentName((string) ($account['first_name'] ?? ''), (string) ($account['last_name'] ?? ''), (string) ($account['middle_initial'] ?? ''));
                    // Closes the name fallback.
                    }
                    // Finds the program code from the stored program value.
                    $accountProgramCode = programCodeForStudent($account);
                    // Uses the readable program name, or the stored value.
                    $accountProgram = PROGRAMS[$accountProgramCode] ?? (string) ($account['program'] ?? '');
                    // Reads the track code, or works it out from the program.
                    $accountTrack = trim((string) ($account['track'] ?? '')) ?: getTrackForProgram($accountProgramCode);
                    ?>
<?php /*                     Opens one pending account row. */ ?>
                    <tr>
<?php /*                         Opens the student name and email cell. */ ?>
                        <td>
<?php /*                             Prints the student name in bold. */ ?>
                            <strong><?= htmlspecialchars($accountName) ?></strong>
<?php /*                             Prints the email used to sign in. */ ?>
                            <div style="font-size:0.75rem;color:var(--gray-400);"><?= htmlspecialchars((string) ($account['email'] ?? '')) ?></div>
<?php /*                         Closes the student cell. */ ?>
                        </td>
<?php /*                         Opens the ADZU ID cell. */ ?>
                        <td style="font-size:0.83rem;font-weight:600;color:var(--adzu-navy);">
<?php /*                             Shows the ADZU ID entered at registration. */ ?>
                            <?= htmlspecialchars((string) ($account['student_id'] ?? '—')) ?>
<?php /*                         Closes the ADZU ID cell. */ ?>
                        </td>
<?php /*                         Opens the program and track cell. */ ?>
                        <td style="font-size:0.83rem;">
<?php /*                             Shows the program name. */ ?>
                            <span><?= htmlspecialchars((string) $accountProgram) ?></span>
<?php /*                             Shows the track under the program name. */ ?>
                            <div style="font-size:0.75rem;color:var(--gray-400);"><?= htmlspecialchars(getTrackLabel($accountTrack)) ?> track</div>
<?php /*                         Closes the program and track cell. */ ?>
                        </td>
<?php /*                         Opens the details cell. */ ?>
                        <td style="font-size:0.8rem;color:var(--gray-600);">
<?php /*                             Shows age and gender when they were entered. */ ?>
                            <?= (int) ($account['age'] ?? 0) > 0 ? (int) $account['age'] . ' years old' : '—' ?>
<?php /*                             Separates the age and gender details. */ ?>
                            <?php if (!empty($account['gender'])): ?><span aria-hidden="true"> &middot; </span><?= htmlspecialchars((string) $account['gender']) ?><?php endif; ?>
<?php /*                         Closes the details cell. */ ?>
                        </td>
<?php /*                         Opens the registration date cell. */ ?>
                        <td style="font-size:0.8rem;color:var(--gray-600);">
<?php /*                             Prints the date the account was created. */ ?>
                            <?= htmlspecialchars(!empty($account['created_at']) ? date('M j, Y g:i A', strtotime((string) $account['created_at'])) : '—') ?>
<?php /*                         Closes the registration date cell. */ ?>
                        </td>
<?php /*                         Opens the actions cell. */ ?>
                        <td class="actions">
<?php /*                             Form that sends the coordinator decision for this account. */ ?>
                            <form method="post" class="account-review-form">
<?php /*                                 Identifies the account being reviewed. */ ?>
                                <input type="hidden" name="review_user_id" value="<?= (int) $account['user_id'] ?>">
<?php /*                                 Approve button that unlocks the student login. */ ?>
                                <button type="submit" name="decision" value="approve" class="btn btn-sm btn-success" title="Approve this student account"><i class="fas fa-check" aria-hidden="true"></i> Approve</button>
<?php /*                                 Reject button that keeps the student out of the portal. */ ?>
                                <button type="submit" name="decision" value="reject" class="btn btn-sm btn-outline" title="Reject this student account" onclick="return confirm('Reject this student account? The student will not be able to sign in.');"><i class="fas fa-xmark" aria-hidden="true"></i> Reject</button>
<?php /*                             Closes the review form. */ ?>
                            </form>
<?php /*                         Closes the actions cell. */ ?>
                        </td>
<?php /*                     Closes this pending account row. */ ?>
                    </tr>
<?php /*                     Ends the loop over pending accounts. */ ?>
                    <?php endforeach; ?>
<?php /*                 Closes the table body. */ ?>
                </tbody>
<?php /*             Closes the pending account table. */ ?>
            </table>
<?php /*         Closes the responsive table wrapper. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the waiting accounts card. */ ?>
</div>

<?php /* Shows the review history only when the coordinator has reviewed accounts. */ ?>
<?php if (!empty($reviewedAccounts)): ?>
<?php /* Opens the card that holds the recent decisions. */ ?>
<div class="card" style="margin-top:1.25rem;">
<?php /*     Card header naming the review history. */ ?>
    <div class="card-header"><h3><i class="fas fa-clock-rotate-left"></i> Recently Reviewed</h3></div>
<?php /*     Opens the card body around the history table. */ ?>
    <div class="card-body">
<?php /*         Wrapper that lets the history table scroll sideways. */ ?>
        <div class="table-responsive">
<?php /*             Starts the recently reviewed accounts table. */ ?>
            <table class="data-table">
<?php /*                 Opens the table header. */ ?>
                <thead>
<?php /*                     Names the history columns. */ ?>
                    <tr><th>Student</th><th>ADZU ID</th><th>Decision</th><th>Reviewed</th></tr>
<?php /*                 Closes the table header. */ ?>
                </thead>
<?php /*                 Opens the table body. */ ?>
                <tbody>
<?php /*                     Loops over each reviewed account. */ ?>
                    <?php foreach ($reviewedAccounts as $reviewed): ?>
<?php /*                     Chooses the badge style for the saved decision. */ ?>
                    <tr>
<?php /*                         Prints the reviewed student name. */ ?>
                        <td>
<?php /*                             Shows the student name in bold. */ ?>
                            <strong><?= htmlspecialchars(trim((string) ($reviewed['full_name'] ?? ''))) ?></strong>
<?php /*                             Shows the email under the name. */ ?>
                            <div style="font-size:0.75rem;color:var(--gray-400);"><?= htmlspecialchars((string) ($reviewed['email'] ?? '')) ?></div>
<?php /*                         Closes the reviewed student cell. */ ?>
                        </td>
<?php /*                         Prints the ADZU ID. */ ?>
                        <td style="font-size:0.83rem;"><?= htmlspecialchars((string) ($reviewed['student_id'] ?? '—')) ?></td>
<?php /*                         Shows the saved decision as a coloured pill. */ ?>
                        <td><span class="account-status-pill is-<?= htmlspecialchars((string) $reviewed['account_status']) ?>"><?= htmlspecialchars(studentAccountStatusLabel((string) $reviewed['account_status'])) ?></span></td>
<?php /*                         Shows when the decision was saved. */ ?>
                        <td style="font-size:0.8rem;color:var(--gray-600);"><?= htmlspecialchars(!empty($reviewed['account_reviewed_at']) ? date('M j, Y g:i A', strtotime((string) $reviewed['account_reviewed_at'])) : '—') ?></td>
<?php /*                     Closes the reviewed account row. */ ?>
                    </tr>
<?php /*                     Ends the loop over reviewed accounts. */ ?>
                    <?php endforeach; ?>
<?php /*                 Closes the history table body. */ ?>
                </tbody>
<?php /*             Closes the history table. */ ?>
            </table>
<?php /*         Closes the history table wrapper. */ ?>
        </div>
<?php /*     Closes the history card body. */ ?>
    </div>
<?php /* Closes the history card. */ ?>
</div>
<?php /* Closes the review history check. */ ?>
<?php endif; ?>

<?php /* Includes the shared footer that closes the page layout. */ ?>
<?php require_once __DIR__ . '/../../includes/layout/footer.php'; ?>
