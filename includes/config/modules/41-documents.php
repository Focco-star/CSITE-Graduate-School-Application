<?php
/* CSITE configuration module: Document uploads, normalization, comments, payments, and receipt details. */

// Adds or replaces an upload record in the session store.
function addUploadRecord(array $u): array {
    // Loads the session uploads.
    $list = storeGet('uploads');
    // Starts the normalized upload record.
    $rec = [
        // Keeps the given id or creates a unique one.
        'id' => (string) ($u['id'] ?? '') !== '' ? (string) $u['id'] : 'up_' . uniqid(),
        // Links the upload to an application when given.
        'applicationId' => $u['applicationId'] ?? null,
        // Stores the student email.
        'studentEmail' => $u['studentEmail'],
        // Stores the original file name.
        'fileName' => $u['fileName'],
        // Stores the document type.
        'docType' => $u['docType'],
        // Stores the workflow stage.
        'stage' => $u['stage'],
        // Stores the file size as an integer.
        'size' => (int) ($u['size'] ?? 0),
        // Stores the upload date or the current date and time.
        'date' => $u['date'] ?? date('Y-m-d H:i:s'),
        // Stores the status or defaults to submitted.
        'status' => $u['status'] ?? 'submitted',
        // Stores notes or an empty string.
        'notes' => $u['notes'] ?? '',
        // Stores the saved file name or an empty string.
        'storedFile' => $u['storedFile'] ?? '',
        // Stores the MIME type or an empty string.
        'mimeType' => $u['mimeType'] ?? '',
    // Closes the upload record array.
    ];
    // Loops through the uploads with their indexes.
    foreach ($list as $i => $existing) {
        // Checks whether an upload with the same id exists.
        if ((string) ($existing['id'] ?? '') === $rec['id']) {
            // Merges the new values into the existing upload.
            $list[$i] = array_merge($existing, $rec);
            // Saves the updated uploads list.
            storeSet('uploads', $list);
            // Returns the updated upload record.
            return $list[$i];
        // Closes the id comparison.
        }
    // Closes the loop over uploads.
    }
    // Adds the new upload record to the list.
    $list[] = $rec;
    // Saves the uploads list.
    storeSet('uploads', $list);
    // Returns the new upload record.
    return $rec;
// Closes the addUploadRecord function.
}

// Validates and stores an uploaded student file in storage uploads.
function storeStudentUpload(array $file): array {
    // Takes the base file name without any folder path.
    $original = basename((string) ($file['name'] ?? ''));
    // Reads the lowercase file extension.
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    // Only allows PDF, DOC, or DOCX files.
    if (!in_array($ext, ['pdf', 'doc', 'docx'], true)) {
        // Throws an error for other file types.
        throw new RuntimeException('Only PDF, DOC, or DOCX files are allowed.');
    // Closes the file-type check.
    }
    // Rejects files larger than 20 MB.
    if ((int) ($file['size'] ?? 0) > 20 * 1024 * 1024) {
        // Throws an error for files that are too big.
        throw new RuntimeException('File exceeds the 20 MB maximum size.');
    // Closes the file-size check.
    }
    // Builds the path to the storage uploads folder.
    $storage = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads';
    // Creates the folder when missing and fails if it still is.
    if (!is_dir($storage) && !mkdir($storage, 0750, true) && !is_dir($storage)) {
        // Throws an error when storage is unavailable.
        throw new RuntimeException('Upload storage is not available.');
    // Closes the storage-folder check.
    }
    // Creates a random stored file name with the original extension.
    $stored = bin2hex(random_bytes(16)) . '.' . $ext;
    // Moves the uploaded file into the storage folder.
    if (!move_uploaded_file((string) $file['tmp_name'], $storage . DIRECTORY_SEPARATOR . $stored)) {
        // Throws an error when the file could not be saved.
        throw new RuntimeException('The file could not be saved. Try again.');
    // Closes the move check.
    }
    // Returns the stored name, MIME type, and original name.
    return ['storedFile' => $stored, 'mimeType' => (string) ($file['type'] ?? ''), 'originalName' => $original];
// Closes the storeStudentUpload function.
}

/**
 * Normalize a MySQL `application_documents` row into the exact shape the
 * session-backed store uses, so every view/consumer works with either source
 * without knowing where the record came from.
 */
// Converts an application documents row into the session store shape.
function normalizeDatabaseDocument(array $row, string $email = ''): array {
    // Starts the normalized document array.
    return [
        // Uses document_id, then id, as the document id.
        'id'            => (string) ($row['document_id'] ?? $row['id'] ?? ''),
        // Keeps the numeric user id, which the inline note marks as critical.
        'user_id'       => isset($row['user_id']) ? (int) $row['user_id'] : null, // <-- THIS IS CRITICAL
        // Uses the application id as an integer, or null.
        'applicationId' => isset($row['application_id']) && $row['application_id'] !== null ? (int) $row['application_id'] : null,
        // Builds a lowercase student email from the email or row.
        'studentEmail'  => strtolower($email !== '' ? $email : (string) ($row['studentEmail'] ?? $row['email'] ?? '')),
        // Uses original name, then fileName, as the file name.
        'fileName'      => (string) ($row['original_name'] ?? $row['fileName'] ?? ''),
        // Uses document type, then docType, as the document type.
        'docType'       => (string) ($row['document_type'] ?? $row['docType'] ?? ''),
        // Reads the stage, or an empty string.
        'stage'         => (string) ($row['stage'] ?? ''),
        // Reads the file size as an integer.
        'size'          => (int) ($row['file_size'] ?? $row['size'] ?? 0),
        // Reads the upload date, or an empty string.
        'date'          => (string) ($row['uploaded_at'] ?? $row['date'] ?? ''),
        // Reads the status, or defaults to submitted.
        'status'        => (string) ($row['status'] ?? 'submitted'),
        // Reads the notes, or an empty string.
        'notes'         => (string) ($row['notes'] ?? ''),
        // Uses stored name, then storedFile, as the saved name.
        'storedFile'    => (string) ($row['stored_name'] ?? $row['storedFile'] ?? ''),
        // Uses mime type, then mimeType, as the MIME type.
        'mimeType'      => (string) ($row['mime_type'] ?? $row['mimeType'] ?? ''),
        // Marks the record as coming from the database.
        'source'        => 'db',
    // Closes the normalized document array.
    ];
// Closes the normalizeDatabaseDocument function.
}

/**
 * Resolve the primary users.user_id for an email address.
 * This is the unified key used by applications + application_documents.
 */
// Finds the users user_id for an email address.
function databaseUserIdForEmail(string $email): int {
    // Returns zero when DB is missing or the email is empty.
    if (!class_exists('DB') || $email === '') {
        // Returns zero because the lookup cannot run.
        return 0;
    // Closes the DB-and-email check.
    }
    // Starts the try block for the user lookup.
    try {
        // Prepares the SQL that selects the user id.
        $stmt = DB::getConnection()->prepare(
            // Selects the user id for the given email.
            'SELECT user_id FROM users WHERE email = :email LIMIT 1'
        // Closes the prepared statement text.
        );
        // Runs the query with the email.
        $stmt->execute(['email' => $email]);
        // Returns the user id, or zero when nothing was found.
        return (int) ($stmt->fetchColumn() ?: 0);
    // Catches query errors so callers get a safe value.
    } catch (Throwable $e) {
        // Returns zero when the lookup fails.
        return 0;
    // Closes the catch block.
    }
// Closes the databaseUserIdForEmail function.
}

// Finds the user id of the first coordinator account.
function databaseCoordinatorUserId(): int {
    // Returns zero when the DB class is not available.
    if (!class_exists('DB')) return 0;
    // Starts the try block for the coordinator query.
    try {
        // Queries the first coordinator user id and returns it, or zero.
        return (int) (DB::getConnection()->query("SELECT user_id FROM users WHERE role = 'coordinator' ORDER BY user_id LIMIT 1")->fetchColumn() ?: 0);
    // Catches query errors so callers get a safe value.
    } catch (Throwable $e) {
        // Returns zero when the query fails.
        return 0;
    // Closes the catch block.
    }
// Closes the databaseCoordinatorUserId function.
}

// Saves a coordinator comment for an application.
function saveApplicationComment(int $applicationId, string $comment): void {
    // Finds the coordinator user id to record as the author.
    $coordinatorId = databaseCoordinatorUserId();
    // Stops when the application, coordinator, or comment is missing.
    if ($applicationId <= 0 || $coordinatorId <= 0 || trim($comment) === '') return;
    // Prepares the INSERT into the application comments table.
    $stmt = DB::getConnection()->prepare('INSERT INTO application_comments (application_id, coordinator_user_id, comment_text) VALUES (:application_id, :coordinator_user_id, :comment_text)');
    // Runs the insert with the application, coordinator, and comment.
    $stmt->execute(['application_id' => $applicationId, 'coordinator_user_id' => $coordinatorId, 'comment_text' => trim($comment)]);
// Closes the saveApplicationComment function.
}

// Saves or updates a payment row for an application.
function saveApplicationPayment(int $applicationId, string $receiptNumber, string $paymentDate, string $amount, int $userId = 0): void {
    // Uses the given user id, or falls back to the coordinator.
    $recorder = $userId > 0 ? $userId : (databaseCoordinatorUserId() ?: 0);
    // Stops when the application or a required payment detail is missing.
    if ($applicationId <= 0 || $recorder <= 0 || $receiptNumber === '' || $paymentDate === '' || $amount === '') return;
    // Prepares the INSERT into the payments table.
    $stmt = DB::getConnection()->prepare(
        // Inserts the payment columns for this application.
        'INSERT INTO payments (application_id, recorded_by_user_id, receipt_number, payment_date, amount)
         VALUES (:application_id, :recorded_by_user_id, :receipt_number, :payment_date, :amount)
         ON DUPLICATE KEY UPDATE recorded_by_user_id = VALUES(recorded_by_user_id), receipt_number = VALUES(receipt_number), payment_date = VALUES(payment_date), amount = VALUES(amount)'
    // Closes the prepared statement call.
    );
    // Starts the values bound to the insert.
    $stmt->execute([
        // Binds the application id.
        'application_id' => $applicationId,
        // Binds the user who recorded the payment.
        'recorded_by_user_id' => $recorder,
        // Binds the receipt number.
        'receipt_number' => $receiptNumber,
        // Binds the payment date.
        'payment_date' => $paymentDate,
        // Binds the amount as a decimal number.
        'amount' => (float) $amount,
    // Closes the bound values array.
    ]);
// Closes the saveApplicationPayment function.
}

/**
 * Save the receipt details the student entered with the official receipt.
 *
 * The details belong to the application of the stage the student is on, so
 * the coordinator sees them in that stage of the application. The payment is
 * kept in the payments table as well, with the student recorded as the one
 * who entered it.
 */
// Saves receipt details on the application and in the payments table.
function saveApplicationReceipt(int $applicationId, string $receiptNumber, string $paymentDate, string $amount, int $userId = 0): void {
    // Stops when the application or a required detail is missing.
    if ($applicationId <= 0 || $receiptNumber === '' || $paymentDate === '' || $amount === '') {
        // Returns without saving.
        return;
    // Closes the required-details check.
    }

    // Notes that the details only belong to a real applications row.
    // The details only belong to a real row of the applications table.
    // Checks that the application row exists in the database.
    $exists = DB::query('SELECT application_id FROM applications WHERE application_id = :id LIMIT 1', ['id' => $applicationId])->fetch();
    // Checks whether the application was not found.
    if (!$exists) {
        // Returns without saving when the row is missing.
        return;
    // Closes the row-exists check.
    }

    // Prepares the UPDATE for the receipt columns.
    DB::getConnection()->prepare(
        // Starts the UPDATE of the applications row.
        'UPDATE applications
            SET receipt_number = :receipt_number,
                payment_date   = :payment_date,
                payment_amount = :payment_amount
          WHERE application_id = :id'
    // Runs the update with the receipt values.
    )->execute([
        // Binds the receipt number.
        'receipt_number' => $receiptNumber,
        // Binds the payment date.
        'payment_date'   => $paymentDate,
        // Binds the payment amount as a decimal number.
        'payment_amount' => (float) $amount,
        // Binds the application id.
        'id'             => $applicationId,
    // Closes the bound values array.
    ]);

    // Also records the payment in the payments table.
    saveApplicationPayment($applicationId, $receiptNumber, $paymentDate, $amount, $userId);
// Closes the saveApplicationReceipt function.
}

/**
 * The receipt details of an application, ready to be shown.
 *
 * @return array{receiptNumber:string,amount:string,date:string}
 */
// Builds the receipt number, amount, and date for display.
function applicationReceiptDetails(array $app): array {
    // Reads and trims the receipt number.
    $receipt = trim((string) ($app['receiptNumber'] ?? ''));
    // Reads the payment amount, or null.
    $amount  = $app['paymentAmount'] ?? null;
    // Reads and trims the payment date.
    $date    = trim((string) ($app['paymentDate'] ?? ''));

    // Starts the receipt details array.
    return [
        // Sets the receipt number.
        'receiptNumber' => $receipt,
        // Formats the amount with two decimals, or leaves it empty.
        'amount'        => ($amount === null || $amount === '') ? '' : number_format((float) $amount, 2),
        // Formats the date as month day year, or leaves it empty.
        'date'          => $date !== '' ? date('M j, Y', strtotime($date)) : '',
    // Closes the details array.
    ];
// Closes the applicationReceiptDetails function.
}
