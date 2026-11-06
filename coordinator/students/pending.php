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
            // Reads the student name so the on-page message is specific.
            $reviewedName = '';
            // Guards the name lookup against a database error.
            try {
                // Finds the reviewed account.
                $reviewedUser = DB::find('users', ['user_id' => $reviewUserId]);
                // Reads the full name from the account.
                $reviewedName = trim((string) ($reviewedUser['full_name'] ?? ''));
            // Catches a lookup error and uses the generic name.
            } catch (Throwable $e) {
                // Leaves the name empty.
                $reviewedName = '';
            // Closes the lookup guard.
            }
            // Chooses the readable subject for the message.
            $reviewedSubject = $reviewedName !== '' ? $reviewedName : 'The student';
            // Tells the coordinator the decision was saved.
            setFlash('success', $newStatus === 'approved'
                ? 'Student account approved. ' . $reviewedSubject . ' can now sign in.'
                : 'Student account rejected. ' . $reviewedSubject . ' cannot sign in and was told to contact the coordinator.');
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
<?php /*                             Shows the birthdate and sex entered at registration. */ ?>
                            <?php
                            // Reads the birthdate stored for this account.
                            $accountBirthdate = trim((string) ($account['birthdate'] ?? ''));
                            // Reads the sex, falling back to the legacy gender column.
                            $accountSex = normalizeSex($account['sex'] ?? ($account['gender'] ?? ''));
                            // Works out the age from the birthdate, or keeps the stored age.
                            $accountAge = $accountBirthdate !== '' ? ageFromBirthdate($accountBirthdate) : (int) ($account['age'] ?? 0);
                            ?>
                            <?= $accountBirthdate !== '' ? htmlspecialchars(date('M j, Y', strtotime($accountBirthdate))) . ' (' . (int) $accountAge . ' years old)' : htmlspecialchars($accountAge > 0 ? $accountAge . ' years old' : '—') ?>
<?php /*                             Separates the birthdate and sex details. */ ?>
                            <?php if ($accountSex !== ''): ?><span aria-hidden="true"> &middot; </span><?= htmlspecialchars($accountSex) ?><?php endif; ?>
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
<?php /*                                 Reject button opening an on-page confirmation. */ ?>
                                <button type="submit" name="decision" value="reject" class="btn btn-sm btn-outline" title="Reject this student account"
                                        data-reject-account
                                        data-student="<?= htmlspecialchars($accountName) ?>"
                                        data-id="<?= htmlspecialchars((string) ($account['student_id'] ?? '')) ?>"
                                        data-email="<?= htmlspecialchars((string) ($account['email'] ?? '')) ?>"><i class="fas fa-xmark" aria-hidden="true"></i> Reject</button>
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

<?php /* On-page confirmation shown before an account is rejected. */ ?>
<div class="confirm-overlay" id="rejectAccountModal" role="dialog" aria-modal="true" aria-labelledby="rejectAccountTitle">
<?php /*     The confirmation card. */ ?>
    <div class="confirm-modal" role="document">
<?php /*         Header with the warning icon and title. */ ?>
        <div class="confirm-header">
            <span class="confirm-icon"><i class="fas fa-user-xmark" aria-hidden="true"></i></span>
            <h3 id="rejectAccountTitle">Reject this student account?</h3>
        </div>
<?php /*         Body with the student details and the consequence. */ ?>
        <div class="confirm-body">
            <p class="confirm-intro">This student will not be able to sign in to the portal.</p>
            <table class="confirm-details">
                <tr><th>Student</th><td data-reject-name>&mdash;</td></tr>
                <tr><th>ADZU ID</th><td data-reject-id>&mdash;</td></tr>
                <tr><th>Email</th><td data-reject-email>&mdash;</td></tr>
            </table>
            <div class="confirm-notice"><i class="fas fa-circle-info" aria-hidden="true"></i> The account stays on record. If the student contacts you, you can still approve it later.</div>
        </div>
<?php /*         Footer with Cancel and Reject account. */ ?>
        <div class="confirm-footer">
            <button type="button" class="btn btn-outline" data-reject-cancel>Cancel</button>
            <button type="button" class="btn confirm-submit is-delete" data-reject-confirm><i class="fas fa-xmark" aria-hidden="true"></i> Reject account</button>
        </div>
    </div>
</div>

<?php /* Starts the reject confirmation behaviour. */ ?>
<script>
(function () {
    // Finds the confirmation modal.
    var modal = document.getElementById('rejectAccountModal');
    // Stops when this page has no modal.
    if (!modal) return;
    // Finds the detail fields and buttons.
    var nameField = modal.querySelector('[data-reject-name]');
    var idField = modal.querySelector('[data-reject-id]');
    var emailField = modal.querySelector('[data-reject-email]');
    var cancelButton = modal.querySelector('[data-reject-cancel]');
    var confirmButton = modal.querySelector('[data-reject-confirm]');
    // Remembers the form and button waiting for confirmation.
    var pendingForm = null;
    var pendingButton = null;
    // Remembers where focus came from.
    var lastFocus = null;

    // Opens the confirmation with the chosen student's details.
    function openModal(button) {
        // Saves the form and button for the confirm step.
        pendingButton = button;
        pendingForm = button.closest('form');
        lastFocus = document.activeElement;
        // Fills the student details.
        nameField.textContent = button.dataset.student || '—';
        idField.textContent = button.dataset.id || '—';
        emailField.textContent = button.dataset.email || '—';
        // Shows the modal and locks the page scroll.
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
        // Focuses the safe Cancel button first.
        cancelButton.focus();
    }

    // Closes the confirmation without changing the account.
    function closeModal() {
        // Hides the modal and restores scrolling.
        modal.classList.remove('open');
        document.body.style.overflow = '';
        // Clears the pending action.
        pendingForm = null;
        pendingButton = null;
        // Returns focus to the Reject button.
        if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
    }

    // Opens the modal from any Reject button.
    document.querySelectorAll('[data-reject-account]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            // Stops the form from submitting before the confirmation.
            event.preventDefault();
            // Shows the on-page confirmation.
            openModal(button);
        });
    });

    // Cancels the rejection.
    cancelButton.addEventListener('click', closeModal);

    // Closes when the dark backdrop is clicked.
    modal.addEventListener('click', function (event) {
        // Only reacts to clicks on the overlay itself.
        if (event.target === modal) closeModal();
    });

    // Closes with the Escape key.
    document.addEventListener('keydown', function (event) {
        // Only reacts when the modal is open.
        if (event.key === 'Escape' && modal.classList.contains('open')) closeModal();
    });

    // Sends the rejection after the coordinator confirms.
    confirmButton.addEventListener('click', function () {
        // Stops when there is no form to submit.
        if (!pendingForm || !pendingButton) return;
        // Uses requestSubmit so the reject button's decision value is sent.
        if (typeof pendingForm.requestSubmit === 'function') {
            pendingForm.requestSubmit(pendingButton);
        } else {
            // Falls back to a hidden field on older browsers.
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'decision';
            hidden.value = 'reject';
            pendingForm.appendChild(hidden);
            pendingForm.submit();
        }
    });
})();
</script>

<?php /* Includes the shared footer that closes the page layout. */ ?>
<?php require_once __DIR__ . '/../../includes/layout/footer.php'; ?>
