<?php
/* CSITE configuration module: Application draft load/save/discard/clear helpers. */

/**
 * The application draft of a student.
 *
 * The Application form is backed by this row so the values the student typed
 * survive a trip through the Dashboard, a refresh, or a logout and login. One
 * draft per student, so nothing leaks between students.
 *
 * Returns null when the student has no draft.
 */
// Loads the saved application draft of one student.
function loadApplicationDraft(int $userId): ?array {
    // Skips the query for an invalid user or missing DB class.
    if ($userId <= 0 || !class_exists('DB')) {
        // Returns null when there is nothing to load.
        return null;
    // Closes the guard check.
    }
    // Starts the block that may throw database errors.
    try {
        // Prepares the draft select query.
        $stmt = DB::getConnection()->prepare(
            // Selects the draft fields the form restores.
            'SELECT title, adviser, abstract, enroll_date, stage_key, doc_type, file_name,
                    receipt_number, payment_amount, payment_date, updated_at
             FROM application_drafts
             WHERE user_id = :user_id
             LIMIT 1'
        // Closes the draft select statement.
        );
        // Runs the query with the student's user id.
        $stmt->execute(['user_id' => $userId]);
        // Fetches the draft row.
        $row = $stmt->fetch();
        // Returns the row, or null when no draft exists.
        return $row ?: null;
    // Catches any database error.
    } catch (Throwable $e) {
        // Returns null when the draft cannot be read.
        return null;
    // Closes the try/catch block.
    }
// Ends loadApplicationDraft.
}

/**
 * Stores (or refreshes) the application draft of a student.
 *
 * An empty draft is removed instead of stored, so a cleared form does not
 * leave a stale draft behind.
 */
// Stores or refreshes the application draft of a student.
function saveApplicationDraft(int $userId, array $values): void {
    // Skips saving for an invalid user or missing DB class.
    if ($userId <= 0 || !class_exists('DB')) {
        // Returns without saving.
        return;
    // Closes the guard check.
    }
    // Starts the block that may throw database errors.
    try {
        // Gets the shared database connection.
        $pdo = DB::getConnection();
        // Trims the typed research title.
        $title       = trim((string) ($values['title'] ?? ''));
        // Trims the typed adviser name.
        $adviser     = trim((string) ($values['adviser'] ?? ''));
        // Trims the typed abstract.
        $abstract    = trim((string) ($values['abstract'] ?? ''));
        // Trims the typed enrollment date.
        $enrollDate  = trim((string) ($values['enroll_date'] ?? ''));
        // Trims the selected stage key.
        $stageKey    = trim((string) ($values['stage_key'] ?? ''));
        // Trims the selected document type.
        $docType     = trim((string) ($values['doc_type'] ?? ''));
        // Trims the chosen file name.
        $fileName    = trim((string) ($values['file_name'] ?? ''));
        // Trims the typed receipt number.
        $receiptNo   = trim((string) ($values['receipt_number'] ?? ''));
        // Trims the typed payment amount.
        $payAmount   = trim((string) ($values['payment_amount'] ?? ''));
        // Trims the typed payment date.
        $payDate     = trim((string) ($values['payment_date'] ?? ''));

        // Checks whether every draft field is empty after trimming.
        if ($title === '' && $adviser === '' && $abstract === '' && $enrollDate === '' && $docType === '' && $fileName === ''
            // Continues the empty check with the document and payment fields.
            && $receiptNo === '' && $payAmount === '' && $payDate === '') {
            // Removes the draft instead of storing an empty one.
            clearApplicationDraft($userId);
            // Returns after clearing the empty draft.
            return;
        // Closes the empty draft check.
        }

        // Prepares the insert that also updates an existing draft.
        $stmt = $pdo->prepare(
            // Inserts the draft columns for the student.
            'INSERT INTO application_drafts (user_id, title, adviser, abstract, enroll_date, stage_key, doc_type, file_name,
                                            receipt_number, payment_amount, payment_date)
             VALUES (:user_id, :title, :adviser, :abstract, :enroll_date, :stage_key, :doc_type, :file_name,
                     :receipt_number, :payment_amount, :payment_date)
             ON DUPLICATE KEY UPDATE
                title          = VALUES(title),
                adviser        = VALUES(adviser),
                abstract       = VALUES(abstract),
                enroll_date    = VALUES(enroll_date),
                stage_key      = VALUES(stage_key),
                doc_type       = VALUES(doc_type),
                file_name      = VALUES(file_name),
                receipt_number = VALUES(receipt_number),
                payment_amount = VALUES(payment_amount),
                payment_date   = VALUES(payment_date),
                updated_at     = CURRENT_TIMESTAMP'
        // Closes the draft insert statement.
        );
        // Runs the statement with the trimmed values.
        $stmt->execute([
            // Binds the user id.
            'user_id'        => $userId,
            // Binds the research title.
            'title'          => $title,
            // Binds the adviser name.
            'adviser'        => $adviser,
            // Binds the abstract.
            'abstract'       => $abstract,
            // Binds the enrollment date.
            'enroll_date'    => $enrollDate,
            // Binds the stage key.
            'stage_key'      => $stageKey,
            // Binds the document type.
            'doc_type'       => $docType,
            // Binds the file name.
            'file_name'      => $fileName,
            // Stores null instead of an empty receipt number.
            'receipt_number' => $receiptNo !== '' ? $receiptNo : null,
            // Stores the payment amount as a number, or null.
            'payment_amount' => $payAmount !== '' ? (float) $payAmount : null,
            // Stores the payment date, or null.
            'payment_date'   => $payDate !== '' ? $payDate : null,
        // Closes the bound values array.
        ]);
    // Catches any database error.
    } catch (Throwable $e) {
        // Logs the failure so the draft problem can be traced.
        error_log('CSITE: could not save the application draft — ' . $e->getMessage());
    // Closes the try/catch block.
    }
// Ends saveApplicationDraft.
}

/**
 * Clears the four unsent fields of the application draft of a student.
 *
 * "Discard Draft" only throws away what the student typed: the research
 * title, the adviser, the abstract and the enrolment date. Uploaded
 * documents, submitted applications and the student record are never
 * touched by this.
 */
// Clears the unsent fields of a student's application draft.
function discardApplicationDraftFields(int $userId): void {
    // Skips for an invalid user or missing DB class.
    if ($userId <= 0 || !class_exists('DB')) {
        // Returns without doing anything.
        return;
    // Closes the guard check.
    }
    // Starts the block that may throw database errors.
    try {
        // Prepares an update of the draft row.
        DB::getConnection()->prepare(
            // Empties the typed fields and the payment details.
            'UPDATE application_drafts
             SET title = "", adviser = "", abstract = "", enroll_date = "",
                 receipt_number = NULL, payment_amount = NULL, payment_date = NULL,
                 updated_at = CURRENT_TIMESTAMP
             WHERE user_id = :user_id'
        // Runs the update for the student.
        )->execute(['user_id' => $userId]);
    // Catches any database error.
    } catch (Throwable $e) {
        // Logs the failure so the discard problem can be traced.
        error_log('CSITE: could not discard the application draft — ' . $e->getMessage());
    // Closes the try/catch block.
    }
// Ends discardApplicationDraftFields.
}

/**
 * Removes the application draft of a student. Called after a successful
 * submission and when the student explicitly starts a new application.
 */
// Deletes the application draft of a student.
function clearApplicationDraft(int $userId): void {
    // Skips for an invalid user or missing DB class.
    if ($userId <= 0 || !class_exists('DB')) {
        // Returns without deleting.
        return;
    // Closes the guard check.
    }
    // Starts the block that may throw database errors.
    try {
        // Deletes the draft row of the student.
        DB::getConnection()->prepare('DELETE FROM application_drafts WHERE user_id = :user_id')
            // Runs the delete with the student's user id.
            ->execute(['user_id' => $userId]);
    // Catches any database error.
    } catch (Throwable $e) {
        // Logs the failure so the delete problem can be traced.
        error_log('CSITE: could not clear the application draft — ' . $e->getMessage());
    // Closes the try/catch block.
    }
// Ends clearApplicationDraft.
}
