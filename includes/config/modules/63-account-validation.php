<?php
/* CSITE configuration module: Student account validation and coordinator notification helpers. */

/**
 * Make sure the users table has the account validation columns.
 *
 * Existing accounts are marked approved, so only accounts created after this
 * feature was added start as pending. The check runs once per request and is
 * skipped when the database class is not available.
 */
// Creates the account validation columns when they are missing.
function ensureAccountValidationSchema(): void {
    // Remembers whether the schema was already checked in this request.
    static $checked = false;
    // Stops when the check already ran or the DB class is missing.
    if ($checked || !class_exists('DB')) {
        // Returns early because there is nothing else to do.
        return;
    // Closes the early-return check.
    }
    // Marks the check as done so it does not run again in this request.
    $checked = true;
    // Starts a guarded block because the table may not exist yet.
    try {
        // Reads every column of the users table.
        $columns = DB::query('SHOW COLUMNS FROM `users`')->fetchAll(PDO::FETCH_COLUMN);
        // Adds the account status column when it is missing.
        if (!in_array('account_status', $columns, true)) {
            // Stores pending or approved status for every student account.
            DB::getConnection()->exec("ALTER TABLE `users` ADD COLUMN `account_status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved' AFTER `role`");
        // Closes the account status check.
        }
        // Adds the review date column when it is missing.
        if (!in_array('account_reviewed_at', $columns, true)) {
            // Records when the coordinator approved or rejected the account.
            DB::getConnection()->exec('ALTER TABLE `users` ADD COLUMN `account_reviewed_at` DATETIME NULL DEFAULT NULL AFTER `account_status`');
        // Closes the review date check.
        }
        // Adds the reviewer column when it is missing.
        if (!in_array('account_reviewed_by', $columns, true)) {
            // Records which coordinator reviewed the account.
            DB::getConnection()->exec('ALTER TABLE `users` ADD COLUMN `account_reviewed_by` INT(11) NULL DEFAULT NULL AFTER `account_reviewed_at`');
        // Closes the reviewer check.
        }
    // Ignores schema errors so the page can explain the problem instead of crashing.
    } catch (Throwable $e) {
        // Leaves the schema untouched when the check could not run.
    // Closes the catch block.
    }
// Closes the ensureAccountValidationSchema function.
}

/**
 * The validation status of a user row.
 *
 * Older rows have no status column, so they count as approved. This keeps the
 * existing demo accounts and every account made before this feature working.
 */
// Reads the account status from a user row.
function studentAccountStatus(?array $user): string {
    // Reads the stored status in lowercase.
    $status = strtolower(trim((string) ($user['account_status'] ?? '')));
    // Falls back to approved for accounts without a status.
    return in_array($status, ['pending', 'approved', 'rejected'], true) ? $status : 'approved';
// Closes the studentAccountStatus function.
}

// Returns the readable label for an account status.
function studentAccountStatusLabel(string $status): string {
    // Shows the pending status as waiting for validation.
    if ($status === 'pending') {
        // Returns the pending label.
        return 'Waiting for validation';
    // Closes the pending label check.
    }
    // Shows the rejected status as not approved.
    if ($status === 'rejected') {
        // Returns the rejected label.
        return 'Not approved';
    // Closes the rejected label check.
    }
    // Shows every other status as approved.
    return 'Approved';
// Closes the studentAccountStatusLabel function.
}

// Returns the message shown to a student whose account is not approved yet.
function studentAccountStatusMessage(string $status): string {
    // Explains the pending account state.
    if ($status === 'pending') {
        // Tells the student to wait for the coordinator.
        return 'Your account is waiting for coordinator validation. You can sign in after it is approved.';
    // Closes the pending message check.
    }
    // Explains the rejected account state.
    if ($status === 'rejected') {
        // Tells the student to contact the coordinator.
        return 'Your account registration was not approved. Please contact the coordinator for assistance.';
    // Closes the rejected message check.
    }
    // Returns an empty message for approved accounts.
    return '';
// Closes the studentAccountStatusMessage function.
}

// Counts the students whose accounts still need validation.
function pendingStudentAccountCount(): int {
    // Starts a guarded block so a missing column cannot break the page.
    try {
        // Counts the pending student accounts.
        $row = DB::query("SELECT COUNT(*) AS total FROM `users` WHERE `role` = 'student' AND `account_status` = 'pending'")->fetch();
        // Returns the count, or zero when it is missing.
        return (int) ($row['total'] ?? 0);
    // Catches database errors and reports no pending accounts.
    } catch (Throwable $e) {
        // Returns zero when the count could not be read.
        return 0;
    // Closes the catch block.
    }
// Closes the pendingStudentAccountCount function.
}

// Loads the students waiting for account validation.
function pendingStudentAccounts(): array {
    // Starts a guarded block so the page can still load on a database error.
    try {
        // Joins the account row with the student profile entered at registration.
        return DB::query(
            "SELECT u.user_id, u.full_name, u.email, u.created_at, u.account_status,
                    s.student_id, s.first_name, s.last_name, s.middle_initial,
                    s.age, s.gender, s.birthdate, s.sex, s.program, s.track, s.enrollment_date
               FROM `users` u
               LEFT JOIN `students` s ON s.user_id = u.user_id
              WHERE u.`role` = 'student' AND u.`account_status` = 'pending'
              ORDER BY u.created_at ASC, u.user_id ASC"
        )->fetchAll();
    // Catches database errors and returns an empty list.
    } catch (Throwable $e) {
        // Returns no pending accounts when the query failed.
        return [];
    // Closes the catch block.
    }
// Closes the pendingStudentAccounts function.
}

// Loads accounts the coordinator already reviewed, newest first.
function recentlyReviewedStudentAccounts(int $limit = 10): array {
    // Starts a guarded block so the page can still load on a database error.
    try {
        // Joins the account row with its student profile.
        return DB::query(
            "SELECT u.user_id, u.full_name, u.email, u.created_at, u.account_status,
                    u.account_reviewed_at, s.student_id, s.first_name, s.last_name,
                    s.middle_initial, s.program, s.track
               FROM `users` u
               LEFT JOIN `students` s ON s.user_id = u.user_id
              WHERE u.`role` = 'student' AND u.`account_status` IN ('approved', 'rejected')
                AND u.`account_reviewed_at` IS NOT NULL
              ORDER BY u.account_reviewed_at DESC, u.user_id DESC
              LIMIT " . max(1, min(50, $limit))
        )->fetchAll();
    // Catches database errors and returns an empty list.
    } catch (Throwable $e) {
        // Returns no reviewed accounts when the query failed.
        return [];
    // Closes the catch block.
    }
// Closes the recentlyReviewedStudentAccounts function.
}

/**
 * Approve or reject a pending student account.
 *
 * Only a pending student account can be reviewed, so a double click cannot
 * change an account that was already decided.
 */
// Saves the coordinator decision for a student account.
function setStudentAccountStatus(int $userId, string $status, int $reviewerId = 0): bool {
    // Allows only the two review decisions.
    if (!in_array($status, ['approved', 'rejected'], true) || $userId <= 0) {
        // Returns false for an invalid request.
        return false;
    // Closes the request check.
    }
    // Starts a guarded block so the caller can show a friendly error.
    try {
        // Updates only a pending student account.
        $stmt = DB::getConnection()->prepare(
            "UPDATE `users`
                SET `account_status` = :status,
                    `account_reviewed_at` = NOW(),
                    `account_reviewed_by` = :reviewer
              WHERE `user_id` = :user_id
                AND `role` = 'student'
                AND `account_status` = 'pending'"
        );
        // Runs the update with the posted decision.
        $stmt->execute([
            // Stores the new account status.
            'status' => $status,
            // Stores the coordinator who made the decision.
            'reviewer' => $reviewerId > 0 ? $reviewerId : null,
            // Selects the student account to update.
            'user_id' => $userId,
        // Closes the parameter array.
        ]);
        // Reports whether a pending account was changed.
        return $stmt->rowCount() > 0;
    // Catches database errors and reports a failed update.
    } catch (Throwable $e) {
        // Returns false when the account could not be updated.
        return false;
    // Closes the catch block.
    }
// Closes the setStudentAccountStatus function.
}

/**
 * Block a signed-in student whose account is no longer approved.
 *
 * The login page already refuses pending and rejected accounts. This second
 * check covers a student who was already signed in when the coordinator
 * rejected the account, so the coordinator's decision takes effect at once.
 */
// Signs out a student who is not approved and sends them to the login page.
function enforceStudentAccountApproval(): void {
    // Reads the signed-in user array.
    $sessionUser = $_SESSION['user'] ?? [];
    // Stops unless the signed-in user is a student.
    if (($sessionUser['role'] ?? '') !== 'student') {
        // Returns for guests and coordinators.
        return;
    // Closes the student check.
    }
    // Reads the user id from the session.
    $userId = (int) ($sessionUser['user_id'] ?? $_SESSION['user_id'] ?? 0);
    // Stops when the student cannot be identified.
    if ($userId <= 0 || !class_exists('DB')) {
        // Leaves the session alone when the account cannot be checked.
        return;
    // Closes the user id check.
    }
    // Starts a guarded block so a database error does not lock the page.
    try {
        // Loads the current account row.
        $user = DB::find('users', ['user_id' => $userId]);
    // Catches database errors and allows the page to continue.
    } catch (Throwable $e) {
        // Returns when the account status could not be read.
        return;
    // Closes the catch block.
    }
    // Stops when the account row is missing.
    if (!$user) {
        // Returns because there is nothing to compare.
        return;
    // Closes the missing row check.
    }
    // Reads the current validation status.
    $status = studentAccountStatus($user);
    // Allows approved accounts through.
    if ($status === 'approved') {
        // Returns for an approved account.
        return;
    // Closes the approved check.
    }
    // Clears the signed-in identity so the student cannot use the portal.
    unset(
        // Removes the full session user row.
        $_SESSION['user'],
        // Removes the plain user id.
        $_SESSION['user_id'],
        // Removes the selected student id.
        $_SESSION['student_id'],
        // Removes the display name.
        $_SESSION['full_name'],
        // Removes the email.
        $_SESSION['email'],
        // Removes the role.
        $_SESSION['role']
    // Closes the cleared session values.
    );
    // Queues the reason so the login page can explain it.
    $_SESSION['flash'] = ['type' => 'error', 'message' => studentAccountStatusMessage($status)];
    // Sends the student back to the login page.
    header('Location: ' . url('student/login.php'));
    // Stops the script after the redirect.
    exit;
// Closes the enforceStudentAccountApproval function.
}
