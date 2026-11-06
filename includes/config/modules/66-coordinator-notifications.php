<?php
/* CSITE configuration module: Coordinator notifications for submitted applications, documents, and pending accounts. */

// Creates the notification table when it does not exist yet.
function ensureCoordinatorNotificationSchema(): void {
    // Runs the schema check only once per request.
    static $done = false;
    // Stops when the table was already checked.
    if ($done) {
        // Returns without repeating the query.
        return;
    // Closes the once-per-request check.
    }
    // Marks the check as done.
    $done = true;
    // Starts a guarded block so a database error never breaks a page.
    try {
        // Creates the table with one row per coordinator action.
        DB::getConnection()->exec("CREATE TABLE IF NOT EXISTS `coordinator_notifications` (
            `notification_id` INT(11) NOT NULL AUTO_INCREMENT,
            `recipient_user_id` INT(11) NOT NULL,
            `type` ENUM('application','account','document') NOT NULL,
            `reference_id` INT(11) NOT NULL,
            `title` VARCHAR(200) NOT NULL,
            `message` VARCHAR(500) NOT NULL,
            `link` VARCHAR(500) NOT NULL,
            `created_at` DATETIME NOT NULL,
            `seen_at` DATETIME NULL DEFAULT NULL,
            `seen_by` INT(11) NULL DEFAULT NULL,
            `removed_at` DATETIME NULL DEFAULT NULL,
            `removed_by` INT(11) NULL DEFAULT NULL,
            PRIMARY KEY (`notification_id`),
            UNIQUE KEY `uq_coord_notification` (`recipient_user_id`, `type`, `reference_id`),
            KEY `idx_coord_unseen` (`recipient_user_id`, `seen_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Adds the document notification type to an existing table.
        $typeColumn = DB::query("SHOW COLUMNS FROM `coordinator_notifications` LIKE 'type'")->fetch();
        // Checks whether the current enum still lacks the document type.
        if ($typeColumn && stripos((string) ($typeColumn['Type'] ?? ''), "'document'") === false) {
            // Widens the enum so document submissions can be stored.
            DB::getConnection()->exec("ALTER TABLE `coordinator_notifications` MODIFY `type` ENUM('application','account','document') NOT NULL");
        // Closes the document type migration check.
        }

        // Reads the current notification table columns.
        $notificationColumns = DB::query('SHOW COLUMNS FROM `coordinator_notifications`')->fetchAll(PDO::FETCH_COLUMN);
        // Adds the removal time column when it is missing.
        if (!in_array('removed_at', $notificationColumns, true)) {
            // Stores when the coordinator removed the notification.
            DB::getConnection()->exec('ALTER TABLE `coordinator_notifications` ADD COLUMN `removed_at` DATETIME NULL DEFAULT NULL AFTER `seen_by`');
        // Closes the removal time column check.
        }
        // Adds the removal author column when it is missing.
        if (!in_array('removed_by', $notificationColumns, true)) {
            // Stores which coordinator removed the notification.
            DB::getConnection()->exec('ALTER TABLE `coordinator_notifications` ADD COLUMN `removed_by` INT(11) NULL DEFAULT NULL AFTER `removed_at`');
        // Closes the removal author column check.
        }
    // Catches a database error and leaves the page usable.
    } catch (Throwable $e) {
        // Ignores the error because notifications are not critical.
    // Closes the schema catch block.
    }
// Ends ensureCoordinatorNotificationSchema.
}

// Returns the coordinator page to reopen after removing a notification.
function coordinatorNotificationReturnPath(): string {
    // Falls back to the homepage when the current page cannot be used.
    $fallback = 'coordinator/homepage.php';
    // Reads the current request path and query.
    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    // Reads the path part of the application base URL.
    $basePath = (string) (parse_url(BASE_URL, PHP_URL_PATH) ?: '');
    // Removes the application base path when it is present.
    if ($basePath !== '' && str_starts_with($requestUri, $basePath)) {
        // Keeps only the page path below the base.
        $requestUri = substr($requestUri, strlen($basePath));
    // Closes the base path check.
    }
    // Removes the leading slash from the page path.
    $path = ltrim($requestUri, '/');
    // Accepts only a coordinator page path.
    if (preg_match('~^coordinator/[A-Za-z0-9_./?=&%#-]+$~', $path)) {
        // Returns the safe return path.
        return $path;
    // Closes the coordinator path check.
    }
    // Returns the homepage fallback.
    return $fallback;
// Ends coordinatorNotificationReturnPath.
}

// Removes one notification from a coordinator's lists.
function removeCoordinatorNotification(int $notificationId, int $coordinatorId): bool {
    // Stops when either id is missing.
    if ($notificationId <= 0 || $coordinatorId <= 0 || !class_exists('DB')) {
        // Reports that nothing was removed.
        return false;
    // Closes the argument check.
    }
    // Makes sure the removal columns exist.
    ensureCoordinatorNotificationSchema();
    // Starts a guarded block for the update.
    try {
        // Marks the notification as removed instead of deleting the row, so
        // the synchronization does not create it again.
        $stmt = DB::getConnection()->prepare(
            "UPDATE `coordinator_notifications`
                SET `removed_at` = NOW(), `removed_by` = :removed_by
              WHERE `notification_id` = :id
                AND `recipient_user_id` = :recipient
                AND `removed_at` IS NULL"
        );
        // Runs the removal with the notification and coordinator ids.
        $stmt->execute(['removed_by' => $coordinatorId, 'recipient' => $coordinatorId, 'id' => $notificationId]);
        // Reports whether a notification was removed.
        return $stmt->rowCount() > 0;
    // Catches a database error and reports failure.
    } catch (Throwable $e) {
        // Returns false when the notification could not be removed.
        return false;
    // Closes the removal catch block.
    }
// Ends removeCoordinatorNotification.
}

// Builds the process-page link for a stage notification.
function coordinatorStageNotificationLink(int $applicationId, string $stageLabel, bool $documentsAnchor = false): string {
    // Falls back to the applications list when no application id is known.
    if ($applicationId <= 0) {
        // Returns the coordinator list page.
        return 'coordinator/applications/manage.php';
    // Closes the missing application check.
    }
    // Starts the link with the application id.
    $link = 'coordinator/applications/process.php?id=' . $applicationId;
    // Works out the stage key from the stored stage label.
    $stageKey = stageKeyFromLabel($stageLabel);
    // Adds the stage so the process page opens the right card.
    if ($stageKey !== '') {
        // Appends the stage key to the link.
        $link .= '&stage=' . rawurlencode($stageKey);
        // Adds the documents anchor when the alert is about a document.
        if ($documentsAnchor) {
            // Points straight at the documents card of that stage.
            $link .= '#documentsSection-' . rawurlencode($stageKey);
        // Closes the documents anchor check.
        }
    // Closes the stage key check.
    }
    // Returns the finished link.
    return $link;
// Ends coordinatorStageNotificationLink.
}

/**
 * Creates or refreshes one notification for every coordinator.
 *
 * The unique key of the table is the recipient, type, and reference, so a
 * document that is sent again reuses the same row. When $refreshExisting is
 * true the row becomes unread again, which is what a fresh student submission
 * should do. The background synchronization passes false so simply opening a
 * coordinator page never turns an old notification new.
 */
// Publishes one notification to every coordinator.
function publishCoordinatorNotification(
    string $type,
    int $referenceId,
    string $title,
    string $message,
    string $link,
    ?string $createdAt = null,
    bool $refreshExisting = true
): void {
    // Stops on an unknown type or a missing reference.
    if (!in_array($type, ['application', 'account', 'document'], true) || $referenceId <= 0 || !class_exists('DB')) {
        // Returns without writing anything.
        return;
    // Closes the argument check.
    }
    // Makes sure the table and its document type exist.
    ensureCoordinatorNotificationSchema();
    // Starts with the supplied event time, or empty for the database clock.
    $created = trim((string) $createdAt);
    // Starts a guarded block so notifications never break the page.
    try {
        // Gets the shared connection.
        $pdo = DB::getConnection();
        // Uses the database clock so notification times match the stored rows.
        if ($created === '') {
            // Reads the current database time.
            $created = (string) $pdo->query('SELECT NOW()')->fetchColumn();
        // Closes the database clock check.
        }
        // Falls back to the PHP clock when the database returned nothing.
        if ($created === '') {
            // Uses the PHP time as a last resort.
            $created = date('Y-m-d H:i:s');
        // Closes the PHP fallback.
        }
        // Reads every coordinator who should receive the notification.
        $recipients = $pdo->query("SELECT `user_id` FROM `users` WHERE `role` = 'coordinator'")->fetchAll(PDO::FETCH_COLUMN);
        // Stops when there is no coordinator account.
        if (!$recipients) {
            // Returns without writing anything.
            return;
        // Closes the recipients check.
        }
        // Looks for an existing notification of the same event.
        $find = $pdo->prepare(
            "SELECT `notification_id`, `created_at`
               FROM `coordinator_notifications`
              WHERE `recipient_user_id` = :recipient
                AND `type` = :type
                AND `reference_id` = :reference
              LIMIT 1"
        );
        // Prepares the insert for a brand-new event.
        $insert = $pdo->prepare(
            "INSERT INTO `coordinator_notifications`
                (`recipient_user_id`, `type`, `reference_id`, `title`, `message`, `link`, `created_at`)
             VALUES (:recipient, :type, :reference, :title, :message, :link, :created)"
        );
        // Prepares the refresh of an existing event.
        $update = $pdo->prepare(
            "UPDATE `coordinator_notifications`
                SET `title` = :title,
                    `message` = :message,
                    `link` = :link,
                    `created_at` = :created,
                    `seen_at` = NULL,
                    `seen_by` = NULL,
                    `removed_at` = NULL,
                    `removed_by` = NULL
              WHERE `notification_id` = :id"
        );
        // Loops over every coordinator.
        foreach ($recipients as $recipient) {
            // Binds the shared event values.
            $values = [
                // Stores the coordinator who receives the row.
                'recipient' => (int) $recipient,
                // Stores the notification type.
                'type' => $type,
                // Stores the id of the application, document, or account.
                'reference' => $referenceId,
                // Stores the short title.
                'title' => $title,
                // Stores the readable message.
                'message' => $message,
                // Stores the page that opens the work.
                'link' => $link,
                // Stores when the event happened.
                'created' => $created,
            ];
            // Looks for the existing row of this coordinator.
            $find->execute([
                // Targets this recipient.
                'recipient' => (int) $recipient,
                // Targets this event type.
                'type' => $type,
                // Targets this event id.
                'reference' => $referenceId,
            ]);
            // Reads the row, or false when it does not exist.
            $existing = $find->fetch();
            // Refreshes an existing notification when requested.
            if ($existing && $refreshExisting) {
                // Writes the new message and makes the row unread again.
                $update->execute([
                    // Binds the refreshed title.
                    'title' => $title,
                    // Binds the refreshed message.
                    'message' => $message,
                    // Binds the refreshed link.
                    'link' => $link,
                    // Binds the refreshed event time.
                    'created' => $created,
                    // Targets the existing row.
                    'id' => (int) $existing['notification_id'],
                ]);
            // Adds a notification only when one does not exist.
            } elseif (!$existing) {
                // Inserts the new notification.
                $insert->execute($values);
            // Closes the insert and refresh branches.
            }
        // Closes the coordinator loop.
        }
    // Catches a database error because notifications are not critical.
    } catch (Throwable $e) {
        // Leaves the page working without the notification.
    // Closes the publish catch block.
    }
// Ends publishCoordinatorNotification.
}

// Tells every coordinator that a student completed an application submission.
function notifyCoordinatorApplicationSubmission(int $applicationId, string $studentName, string $stageLabel): void {
    // Stops when the application id is missing.
    if ($applicationId <= 0) {
        // Returns because there is nothing to link to.
        return;
    // Closes the missing application check.
    }
    // Uses a readable fallback for the student name.
    $name = trim($studentName) !== '' ? trim($studentName) : 'A student';
    // Uses a readable fallback for the stage.
    $stage = trim($stageLabel) !== '' ? trim($stageLabel) : 'an application';
    // Publishes the completion notification.
    publishCoordinatorNotification(
        // Marks the event as an application submission.
        'application',
        // Uses the application as the event id.
        $applicationId,
        // Sets the notification title.
        'New application submitted',
        // Names the student and the stage they completed.
        $name . ' submitted ' . $stage . ' for review.',
        // Opens the process page for that application.
        coordinatorStageNotificationLink($applicationId, $stage),
        // Uses the database clock so the time matches the stored submission.
        null,
        // Refreshes a previous notification when the student submits again.
        true
    );
// Ends notifyCoordinatorApplicationSubmission.
}

// Tells every coordinator that a student sent a document for review.
function notifyCoordinatorDocumentSubmission(int $documentId, string $studentName, string $documentType, string $stageLabel, int $applicationId = 0): void {
    // Stops when the document id is missing.
    if ($documentId <= 0 || !class_exists('DB')) {
        // Returns because there is nothing to link to.
        return;
    // Closes the missing document check.
    }
    // Fills the application id and stage from the stored document when needed.
    if ($applicationId <= 0 || trim($stageLabel) === '') {
        // Starts a guarded lookup so a database error never blocks the upload.
        try {
            // Reads the document row that was just saved.
            $lookup = DB::getConnection()->prepare(
                "SELECT `application_id`, `stage`
                   FROM `application_documents`
                  WHERE `document_id` = :document_id
                  LIMIT 1"
            );
            // Runs the lookup with the document id.
            $lookup->execute(['document_id' => $documentId]);
            // Reads the row, or false when it is missing.
            $row = $lookup->fetch();
            // Uses the stored application id and stage as fallbacks.
            if ($row) {
                // Keeps a real application id from the caller.
                if ($applicationId <= 0) {
                    // Uses the stored application id.
                    $applicationId = (int) ($row['application_id'] ?? 0);
                // Closes the application id fallback.
                }
                // Keeps a real stage from the caller.
                if (trim($stageLabel) === '') {
                    // Uses the stored stage label.
                    $stageLabel = (string) ($row['stage'] ?? '');
                // Closes the stage fallback.
                }
            // Closes the found-row check.
            }
        // Catches a lookup error and keeps the caller's values.
        } catch (Throwable $e) {
            // Leaves the values unchanged.
        // Closes the lookup catch block.
        }
    // Closes the fallback lookup.
    }
    // Uses a readable fallback for the student name.
    $name = trim($studentName) !== '' ? trim($studentName) : 'A student';
    // Uses a readable fallback for the document type.
    $document = trim($documentType) !== '' ? trim($documentType) : 'a document';
    // Uses a readable fallback for the stage.
    $stage = trim($stageLabel) !== '' ? trim($stageLabel) : 'their stage';
    // Avoids repeating the stage when the document itself is the paper.
    $message = strcasecmp($document, $stage) === 0
        // Says the paper was submitted when both labels match.
        ? $name . ' submitted ' . $document . ' for review.'
        // Otherwise names the document and the stage it belongs to.
        : $name . ' submitted ' . $document . ' for ' . $stage . '.';
    // Publishes the document notification.
    publishCoordinatorNotification(
        // Marks the event as a document submission.
        'document',
        // Uses the document as the event id.
        $documentId,
        // Sets the notification title.
        'Document submitted for review',
        // Uses the message built above.
        $message,
        // Opens the documents card of the matching process page.
        coordinatorStageNotificationLink($applicationId, $stage, true),
        // Uses the database clock so the time matches the stored upload.
        null,
        // Refreshes a previous notification when the same document is sent again.
        true
    );
// Ends notifyCoordinatorDocumentSubmission.
}

// Keeps the notification rows in step with the real account and application states.
function syncCoordinatorNotifications(): void {
    // Makes sure the table exists.
    ensureCoordinatorNotificationSchema();
    // Starts a guarded block for the synchronization queries.
    try {
        // Gets the shared connection.
        $pdo = DB::getConnection();
        // Reads every coordinator who should receive notifications.
        $coordinators = $pdo->query("SELECT `user_id` FROM `users` WHERE `role` = 'coordinator'")->fetchAll(PDO::FETCH_COLUMN);
        // Stops when there is no coordinator account.
        if (!$coordinators) {
            // Returns without doing any work.
            return;
        // Closes the coordinator check.
        }

        // Prepares the insert that ignores duplicates.
        $insert = $pdo->prepare(
            "INSERT IGNORE INTO `coordinator_notifications`
                (`recipient_user_id`, `type`, `reference_id`, `title`, `message`, `link`, `created_at`)
             VALUES (:recipient, :type, :reference, :title, :message, :link, :created)"
        );

        // Reads the student accounts still waiting for validation.
        $accounts = $pdo->query(
            "SELECT `user_id`, `full_name`, `email`, `created_at`
               FROM `users`
              WHERE `role` = 'student' AND `account_status` = 'pending'"
        )->fetchAll();

        // Adds one notification per coordinator for every pending account.
        foreach ($coordinators as $coordinatorId) {
            // Loops over the pending accounts.
            foreach ($accounts as $account) {
                // Stores a readable action message.
                $message = trim((string) $account['full_name']) . ' (' . trim((string) $account['email']) . ') is waiting for account validation.';
                // Inserts the account notification, ignoring a duplicate.
                $insert->execute([
                    'recipient' => (int) $coordinatorId,
                    'type' => 'account',
                    'reference' => (int) $account['user_id'],
                    'title' => 'New student account for validation',
                    'message' => $message,
                    'link' => 'coordinator/students/pending.php',
                    'created' => (string) ($account['created_at'] ?? date('Y-m-d H:i:s')),
                ]);
            // Closes the pending account loop.
            }
        // Closes the coordinator loop for accounts.
        }

        // Reads the applications that are waiting for the coordinator's review.
        $applications = $pdo->query(
            "SELECT a.`application_id`, a.`presentation_stage`, a.`submitted_at`, u.`full_name`
               FROM `applications` a
               INNER JOIN `users` u ON u.`user_id` = a.`user_id`
              WHERE a.`status` = 'submitted' AND a.`archived_at` IS NULL"
        )->fetchAll();

        // Adds one notification per coordinator for every submitted application.
        foreach ($coordinators as $coordinatorId) {
            // Loops over the submitted applications.
            foreach ($applications as $application) {
                // Builds the stage part of the message.
                $stage = trim((string) $application['presentation_stage']);
                // Stores a readable action message.
                $message = trim((string) $application['full_name']) . ' submitted '
                    . ($stage !== '' ? $stage : 'an application') . ' for review.';
                // Inserts the application notification, ignoring a duplicate.
                $insert->execute([
                    'recipient' => (int) $coordinatorId,
                    'type' => 'application',
                    'reference' => (int) $application['application_id'],
                    'title' => 'New application submitted',
                    'message' => $message,
                    'link' => 'coordinator/applications/process.php?id=' . (int) $application['application_id'],
                    'created' => (string) ($application['submitted_at'] ?? date('Y-m-d H:i:s')),
                ]);
            // Closes the submitted application loop.
            }
        // Closes the coordinator loop for applications.
        }

        // Moves notifications whose action is already done into the updates list.
        $pdo->exec(
            "UPDATE `coordinator_notifications` n
             LEFT JOIN `users` u ON n.`type` = 'account' AND u.`user_id` = n.`reference_id`
             LEFT JOIN `applications` a ON n.`type` = 'application' AND a.`application_id` = n.`reference_id`
             LEFT JOIN `application_documents` d ON n.`type` = 'document' AND d.`document_id` = n.`reference_id`
             LEFT JOIN `applications` da ON n.`type` = 'document' AND da.`application_id` = d.`application_id`
                SET n.`seen_at` = COALESCE(n.`seen_at`, NOW())
              WHERE n.`seen_at` IS NULL
                AND (
                    (n.`type` = 'account' AND (u.`user_id` IS NULL OR u.`account_status` <> 'pending'))
                    OR (n.`type` = 'application' AND (a.`application_id` IS NULL OR a.`status` <> 'submitted' OR a.`archived_at` IS NOT NULL))
                    OR (n.`type` = 'document' AND (d.`document_id` IS NULL OR d.`status` <> 'submitted' OR da.`archived_at` IS NOT NULL))
                )"
        );
    // Catches a database error so a page still renders.
    } catch (Throwable $e) {
        // Ignores the error because notifications are not critical.
    // Closes the synchronization catch block.
    }
// Ends syncCoordinatorNotifications.
}

// Loads the new and previously viewed notifications for one coordinator.
function coordinatorNotifications(int $coordinatorId, int $limit = 20): array {
    // Starts with an empty result.
    $result = ['new' => [], 'updates' => [], 'unseen' => 0];
    // Stops when no coordinator id was given.
    if ($coordinatorId <= 0) {
        // Returns the empty result.
        return $result;
    // Closes the empty-id check.
    }
    // Keeps the rows in step with the current statuses.
    syncCoordinatorNotifications();
    // Starts a guarded block for the reads.
    try {
        // Gets the shared connection.
        $pdo = DB::getConnection();
        // Counts every unseen notification.
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM `coordinator_notifications` WHERE `recipient_user_id` = :id AND `seen_at` IS NULL AND `removed_at` IS NULL");
        // Runs the count with the coordinator id.
        $countStmt->execute(['id' => $coordinatorId]);
        // Stores the unseen count.
        $result['unseen'] = (int) $countStmt->fetchColumn();

        // Reads the newest unseen notifications.
        $newStmt = $pdo->prepare(
            "SELECT * FROM `coordinator_notifications`
              WHERE `recipient_user_id` = :id AND `seen_at` IS NULL AND `removed_at` IS NULL
              ORDER BY `created_at` DESC, `notification_id` DESC
              LIMIT " . (int) $limit
        );
        // Runs the unseen query.
        $newStmt->execute(['id' => $coordinatorId]);
        // Stores the unseen rows.
        $result['new'] = $newStmt->fetchAll();

        // Reads the most recently viewed notifications.
        $updateStmt = $pdo->prepare(
            "SELECT * FROM `coordinator_notifications`
              WHERE `recipient_user_id` = :id AND `seen_at` IS NOT NULL AND `removed_at` IS NULL
              ORDER BY `seen_at` DESC, `notification_id` DESC
              LIMIT " . (int) $limit
        );
        // Runs the updates query.
        $updateStmt->execute(['id' => $coordinatorId]);
        // Stores the viewed rows.
        $result['updates'] = $updateStmt->fetchAll();
    // Catches a database error and returns what is available.
    } catch (Throwable $e) {
        // Leaves the result as it is.
    // Closes the read catch block.
    }
    // Returns the notification groups.
    return $result;
// Ends coordinatorNotifications.
}

// Marks one notification as viewed and returns its row.
function markCoordinatorNotificationSeen(int $notificationId, int $coordinatorId): ?array {
    // Stops when either id is missing.
    if ($notificationId <= 0 || $coordinatorId <= 0) {
        // Returns nothing for an invalid request.
        return null;
    // Closes the invalid-id check.
    }
    // Keeps the rows in step with the current statuses.
    syncCoordinatorNotifications();
    // Starts a guarded block for the update.
    try {
        // Gets the shared connection.
        $pdo = DB::getConnection();
        // Reads the notification that belongs to this coordinator.
        $find = $pdo->prepare("SELECT * FROM `coordinator_notifications` WHERE `notification_id` = :id AND `recipient_user_id` = :coordinator AND `removed_at` IS NULL LIMIT 1");
        // Runs the lookup.
        $find->execute(['id' => $notificationId, 'coordinator' => $coordinatorId]);
        // Reads the row.
        $row = $find->fetch();
        // Stops when the notification does not exist.
        if (!$row) {
            // Returns nothing for an unknown notification.
            return null;
        // Closes the missing-row check.
        }
        // Marks the notification as viewed.
        $update = $pdo->prepare("UPDATE `coordinator_notifications` SET `seen_at` = NOW(), `seen_by` = :coordinator WHERE `notification_id` = :id AND `seen_at` IS NULL AND `removed_at` IS NULL");
        // Runs the update.
        $update->execute(['coordinator' => $coordinatorId, 'id' => $notificationId]);
        // Returns the notification so the caller can follow its link.
        return $row;
    // Catches a database error and returns nothing.
    } catch (Throwable $e) {
        // Returns nothing when the update failed.
        return null;
    // Closes the update catch block.
    }
// Ends markCoordinatorNotificationSeen.
}

// Formats a notification timestamp for the bell and homepage.
function coordinatorNotificationTime(?string $datetime): string {
    // Reads the timestamp.
    $value = trim((string) $datetime);
    // Returns a dash when there is no timestamp.
    if ($value === '') {
        // Returns the empty marker.
        return '';
    // Closes the empty timestamp check.
    }
    // Starts a guarded block so an invalid date never breaks the page.
    try {
        // Parses the timestamp.
        $then = new DateTime($value);
        // Reads the current time.
        $now = new DateTime();
        // Works out the difference in seconds.
        $seconds = max(0, $now->getTimestamp() - $then->getTimestamp());
        // Shows very recent items as just now.
        if ($seconds < 60) {
            // Returns the short label.
            return 'Just now';
        // Closes the seconds check.
        }
        // Shows items under an hour in minutes.
        if ($seconds < 3600) {
            // Returns the minutes label.
            return intdiv($seconds, 60) . 'm ago';
        // Closes the minutes check.
        }
        // Shows items under a day in hours.
        if ($seconds < 86400) {
            // Returns the hours label.
            return intdiv($seconds, 3600) . 'h ago';
        // Closes the hours check.
        }
        // Shows older items with a date.
        return $then->format('M j, g:i A');
    // Catches an invalid date string.
    } catch (Throwable $e) {
        // Returns the raw value as a fallback.
        return $value;
    // Closes the invalid-date handler.
    }
// Ends coordinatorNotificationTime.
}

// Counts the applications still waiting for the coordinator's first review.
function coordinatorNewApplicationCount(): int {
    // Starts a guarded block for the count.
    try {
        // Counts submitted, unarchived applications.
        return (int) DB::query("SELECT COUNT(*) FROM `applications` WHERE `status` = 'submitted' AND `archived_at` IS NULL")->fetchColumn();
    // Catches a database error and reports zero.
    } catch (Throwable $e) {
        // Returns zero when the count failed.
        return 0;
    // Closes the count catch block.
    }
// Ends coordinatorNewApplicationCount.
}
