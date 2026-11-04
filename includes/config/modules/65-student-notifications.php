<?php
/* CSITE configuration module: Coordinator comment notifications for students. */

/**
 * Make sure the comment table has the student and coordinator removal columns.
 *
 * A comment is never deleted when either side removes it. The timestamps only
 * hide the comment from the active lists, so the coordinator keeps the full
 * history of what happened to the application.
 */
// Creates the removal columns when they are missing.
function ensureStudentCommentNotificationSchema(): void {
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
        // Reads every column of the application_comments table.
        $columns = DB::query('SHOW COLUMNS FROM `application_comments`')->fetchAll(PDO::FETCH_COLUMN);
        // Adds the student removal column when it is missing.
        if (!in_array('student_dismissed_at', $columns, true)) {
            // Stores when the student removed the comment notification.
            DB::getConnection()->exec('ALTER TABLE `application_comments` ADD COLUMN `student_dismissed_at` DATETIME NULL DEFAULT NULL AFTER `created_at`');
        // Closes the student removal column check.
        }
        // Adds the coordinator removal time column when it is missing.
        if (!in_array('coordinator_removed_at', $columns, true)) {
            // Stores when the coordinator removed the comment.
            DB::getConnection()->exec('ALTER TABLE `application_comments` ADD COLUMN `coordinator_removed_at` DATETIME NULL DEFAULT NULL AFTER `student_dismissed_at`');
        // Closes the coordinator removal time check.
        }
        // Adds the coordinator removal author column when it is missing.
        if (!in_array('coordinator_removed_by', $columns, true)) {
            // Stores which coordinator removed the comment.
            DB::getConnection()->exec('ALTER TABLE `application_comments` ADD COLUMN `coordinator_removed_by` INT(11) NULL DEFAULT NULL AFTER `coordinator_removed_at`');
        // Closes the coordinator removal author check.
        }
    // Ignores schema errors so the page can still render.
    } catch (Throwable $e) {
        // Leaves the schema untouched when the check could not run.
    // Closes the catch block.
    }
// Closes the ensureStudentCommentNotificationSchema function.
}

/**
 * The newest coordinator comment of every application owned by a student.
 *
 * Only the newest active comment of each application is returned, so saving
 * the same stage repeatedly never produces a stack of duplicate notifications.
 * Comments removed by the coordinator or the student are skipped.
 */
// Loads the coordinator comment notifications for a student.
function studentCoordinatorComments(string $email): array {
    // Trims the email and stops when it is empty or the DB class is missing.
    $email = trim($email);
    // Returns no notifications when the student cannot be identified.
    if ($email === '' || !class_exists('DB')) {
        // Returns an empty notification list.
        return [];
    // Closes the email and DB check.
    }
    // Makes sure the dismissal column exists before it is read.
    ensureStudentCommentNotificationSchema();
    // Starts a guarded block so a database error cannot break the dashboard.
    try {
        // Loads the newest visible comment of every application of this student.
        $rows = DB::query(
            // Selects the comment, its stage, and the coordinator name.
            "SELECT c.`comment_id`, c.`application_id`, c.`comment_text`, c.`created_at`,
                    a.`presentation_stage` AS stage,
                    cu.`full_name` AS coordinator_name
               FROM `application_comments` c
               INNER JOIN `applications` a ON a.`application_id` = c.`application_id`
               INNER JOIN `users` su ON su.`user_id` = a.`user_id`
               LEFT JOIN `users` cu ON cu.`user_id` = c.`coordinator_user_id`
              WHERE su.`email` = :email
                AND a.`archived_at` IS NULL
                AND c.`coordinator_removed_at` IS NULL
                AND c.`student_dismissed_at` IS NULL
                AND TRIM(c.`comment_text`) <> ''
                AND c.`comment_id` = (
                    SELECT MAX(c2.`comment_id`)
                      FROM `application_comments` c2
                     WHERE c2.`application_id` = c.`application_id`
                       AND c2.`coordinator_removed_at` IS NULL
                       AND c2.`student_dismissed_at` IS NULL
                )
              ORDER BY c.`created_at` DESC, c.`comment_id` DESC",
            // Binds the student email.
            ['email' => $email]
        // Closes the query call.
        )->fetchAll();
        // Starts the normalized notification list.
        $notifications = [];
        // Loops over every comment row.
        foreach ($rows as $row) {
            // Adds one notification in the shape the student pages expect.
            $notifications[] = [
                // Keeps the comment id used by the dismiss button.
                'commentId'       => (int) ($row['comment_id'] ?? 0),
                // Keeps the application id the comment belongs to.
                'applicationId'   => (int) ($row['application_id'] ?? 0),
                // Keeps the readable stage label.
                'stage'           => (string) ($row['stage'] ?? ''),
                // Works out the stage key used by the milestone tracker.
                'stageKey'        => stageKeyFromLabel((string) ($row['stage'] ?? '')),
                // Keeps the comment text.
                'text'            => trim((string) ($row['comment_text'] ?? '')),
                // Keeps the time the comment was written.
                'createdAt'       => (string) ($row['created_at'] ?? ''),
                // Uses the coordinator name, or a friendly fallback.
                'coordinatorName' => trim((string) ($row['coordinator_name'] ?? '')) ?: 'Coordinator',
            // Closes the notification array.
            ];
        // Closes the loop over comment rows.
        }
        // Returns the notifications newest first.
        return $notifications;
    // Catches database errors and reports no notifications.
    } catch (Throwable $e) {
        // Returns an empty notification list.
        return [];
    // Closes the catch block.
    }
// Closes the studentCoordinatorComments function.
}

/**
 * Every active coordinator comment for a student's applications, newest first.
 *
 * The student comment box uses this list to show the full conversation for
 * each stage, not just the newest notification. Removed comments are skipped,
 * and repeated saves of the same text are shown only once.
 */
// Loads all active coordinator comments for a student.
function studentAllCoordinatorComments(string $email): array {
    // Trims the email and stops when it is empty or the DB class is missing.
    $email = trim($email);
    // Returns no comments when the student cannot be identified.
    if ($email === '' || !class_exists('DB')) {
        // Returns an empty comment list.
        return [];
    // Closes the email and DB check.
    }
    // Makes sure the removal columns exist before they are read.
    ensureStudentCommentNotificationSchema();
    // Starts a guarded block so a database error cannot break the dashboard.
    try {
        // Loads every active comment for the student's applications.
        $rows = DB::query(
            // Selects the comment, its stage, and the coordinator name.
            "SELECT c.`comment_id`, c.`application_id`, c.`comment_text`, c.`created_at`,
                    a.`presentation_stage` AS stage,
                    cu.`full_name` AS coordinator_name
               FROM `application_comments` c
               INNER JOIN `applications` a ON a.`application_id` = c.`application_id`
               INNER JOIN `users` su ON su.`user_id` = a.`user_id`
               LEFT JOIN `users` cu ON cu.`user_id` = c.`coordinator_user_id`
              WHERE su.`email` = :email
                AND a.`archived_at` IS NULL
                AND c.`coordinator_removed_at` IS NULL
                AND c.`student_dismissed_at` IS NULL
                AND TRIM(c.`comment_text`) <> ''
              ORDER BY c.`created_at` DESC, c.`comment_id` DESC",
            // Binds the student email.
            ['email' => $email]
        // Closes the query call.
        )->fetchAll();
        // Starts the normalized comment list.
        $comments = [];
        // Remembers application/text pairs already listed.
        $seen = [];
        // Loops over every active comment row.
        foreach ($rows as $row) {
            // Builds a key so repeated saves of the same text appear once.
            $commentKey = (string) ($row['application_id'] ?? '') . '|' . trim((string) ($row['comment_text'] ?? ''));
            // Skips a text that was already shown by a newer row.
            if (isset($seen[$commentKey])) {
                // Continues to the next comment row.
                continue;
            // Closes the duplicate-text check.
            }
            // Marks this application/text pair as listed.
            $seen[$commentKey] = true;
            // Adds one comment in the shape the student page expects.
            $comments[] = [
                // Keeps the comment id.
                'commentId'       => (int) ($row['comment_id'] ?? 0),
                // Keeps the application id the comment belongs to.
                'applicationId'   => (int) ($row['application_id'] ?? 0),
                // Keeps the readable stage label.
                'stage'           => (string) ($row['stage'] ?? ''),
                // Works out the stage key used by the milestone tracker.
                'stageKey'        => stageKeyFromLabel((string) ($row['stage'] ?? '')),
                // Keeps the comment text.
                'text'            => trim((string) ($row['comment_text'] ?? '')),
                // Keeps the time the comment was written.
                'createdAt'       => (string) ($row['created_at'] ?? ''),
                // Uses the coordinator name, or a friendly fallback.
                'coordinatorName' => trim((string) ($row['coordinator_name'] ?? '')) ?: 'Coordinator',
            // Closes the comment array.
            ];
        // Closes the loop over comment rows.
        }
        // Returns the comments newest first.
        return $comments;
    // Catches database errors and reports no comments.
    } catch (Throwable $e) {
        // Returns an empty comment list.
        return [];
    // Closes the catch block.
    }
// Closes the studentAllCoordinatorComments function.
}

/**
 * Every coordinator comment saved for one application, newest first.
 *
 * The coordinator uses this list to review what was sent and whether the
 * comment is active, removed by the coordinator, or removed by the student.
 */
// Loads the comment history of one application.
function applicationCommentHistory(int $applicationId): array {
    // Rejects invalid application ids or a missing DB class.
    if ($applicationId <= 0 || !class_exists('DB')) {
        // Returns an empty history.
        return [];
    // Closes the request check.
    }
    // Makes sure the removal columns exist before they are read.
    ensureStudentCommentNotificationSchema();
    // Starts a guarded block so the page can still load on a database error.
    try {
        // Loads every comment of the application with its coordinator.
        $rows = DB::query(
            // Selects the comment details and both removal timestamps.
            'SELECT c.`comment_id`, c.`comment_text`, c.`created_at`,
                    c.`student_dismissed_at`, c.`coordinator_removed_at`, c.`coordinator_removed_by`,
                    cu.`full_name` AS coordinator_name
               FROM `application_comments` c
               LEFT JOIN `users` cu ON cu.`user_id` = c.`coordinator_user_id`
              WHERE c.`application_id` = :application_id
              ORDER BY c.`comment_id` DESC',
            // Binds the application id.
            ['application_id' => $applicationId]
        // Closes the query call.
        )->fetchAll();
        // Starts the normalized history list.
        $history = [];
        // Remembers comment texts already listed so old repeated saves stay out.
        $seenTexts = [];
        // Loops over every comment row, newest first.
        foreach ($rows as $row) {
            // Reads the trimmed comment text.
            $commentText = trim((string) ($row['comment_text'] ?? ''));
            // Skips empty comments and texts already shown by a newer row.
            if ($commentText === '' || isset($seenTexts[$commentText])) {
                // Continues to the next history row.
                continue;
            // Closes the duplicate-text check.
            }
            // Marks this text as listed.
            $seenTexts[$commentText] = true;
            // Adds one history entry used by the coordinator view.
            $history[] = [
                // Keeps the comment id.
                'commentId'       => (int) ($row['comment_id'] ?? 0),
                // Keeps the comment text.
                'text'            => $commentText,
                // Keeps when the comment was written.
                'createdAt'       => (string) ($row['created_at'] ?? ''),
                // Uses the coordinator name, or a friendly fallback.
                'coordinatorName' => trim((string) ($row['coordinator_name'] ?? '')) ?: 'Coordinator',
                // Keeps when the student removed the notification.
                'studentRemovedAt' => (string) ($row['student_dismissed_at'] ?? ''),
                // Marks the comment as removed by the student.
                'studentRemoved'   => !empty($row['student_dismissed_at']),
                // Keeps when the coordinator removed the comment.
                'coordinatorRemovedAt' => (string) ($row['coordinator_removed_at'] ?? ''),
                // Keeps which coordinator removed the comment.
                'coordinatorRemovedBy' => (int) ($row['coordinator_removed_by'] ?? 0),
                // Marks the comment as removed by the coordinator.
                'coordinatorRemoved'   => !empty($row['coordinator_removed_at']),
                // Marks the comment as still active when neither side removed it.
                'active'               => empty($row['student_dismissed_at']) && empty($row['coordinator_removed_at']),
            // Closes the history entry.
            ];
        // Closes the loop over comment rows.
        }
        // Returns the newest comments first.
        return $history;
    // Catches database errors and reports no history.
    } catch (Throwable $e) {
        // Returns an empty history.
        return [];
    // Closes the catch block.
    }
// Closes the applicationCommentHistory function.
}

/**
 * Keep applications.coordinator_comment equal to the newest active comment.
 *
 * The status pages read that column, so removed comments must not stay there.
 * When no active comment is left, the column is cleared.
 */
// Refreshes the application's visible coordinator comment.
function refreshApplicationCoordinatorComment(int $applicationId, string $removedText = ''): void {
    // Rejects invalid ids or a missing DB class.
    if ($applicationId <= 0 || !class_exists('DB')) {
        // Returns because there is nothing to refresh.
        return;
    // Closes the request check.
    }
    // Starts a guarded block so a database error cannot break the removal.
    try {
        // Reads the comment currently shown on the application.
        $currentComment = (string) DB::query(
            // Selects the application's visible comment.
            'SELECT `coordinator_comment` FROM `applications` WHERE `application_id` = :application_id LIMIT 1',
            // Binds the application id.
            ['application_id' => $applicationId]
        // Closes the query call.
        )->fetchColumn();
        // Leaves a different older comment untouched when it is not the one removed.
        if (trim($currentComment) !== '' && $removedText !== '' && trim($currentComment) !== trim($removedText)) {
            // Returns because another visible comment is still in place.
            return;
        // Closes the different-comment check.
        }
        // Reads the newest comment that neither side removed.
        $latest = DB::query(
            // Selects the newest active comment text.
            'SELECT `comment_text`
               FROM `application_comments`
              WHERE `application_id` = :application_id
                AND `coordinator_removed_at` IS NULL
                AND `student_dismissed_at` IS NULL
              ORDER BY `comment_id` DESC
              LIMIT 1',
            // Binds the application id.
            ['application_id' => $applicationId]
        // Closes the query call.
        )->fetchColumn();
        // Uses the active comment, or an empty string when none is left.
        $visibleComment = $latest !== false ? (string) $latest : '';
        // Updates the application column used by the status pages.
        $stmt = DB::getConnection()->prepare(
            // Sets the visible comment on the application.
            'UPDATE `applications` SET `coordinator_comment` = :comment WHERE `application_id` = :application_id'
        );
        // Runs the update with the active text.
        $stmt->execute([
            // Binds the visible comment.
            'comment'        => $visibleComment,
            // Binds the application id.
            'application_id' => $applicationId,
        // Closes the parameter list.
        ]);
    // Catches database errors so the removal still succeeds.
    } catch (Throwable $e) {
        // Leaves the application column unchanged on a database error.
    // Closes the catch block.
    }
// Closes the refreshApplicationCoordinatorComment function.
}

/**
 * Remove one coordinator comment notification for the student who owns it.
 *
 * Every copy of the same comment text on that application is marked removed,
 * so an older repeated save cannot reappear as a new notification. The comment
 * row itself stays in the table for the coordinator history.
 */
// Removes one comment notification for a student.
function removeStudentComment(int $commentId, int $userId): bool {
    // Rejects invalid ids or a missing DB class.
    if ($commentId <= 0 || $userId <= 0 || !class_exists('DB')) {
        // Returns false for an invalid request.
        return false;
    // Closes the request check.
    }
    // Makes sure the removal columns exist before they are updated.
    ensureStudentCommentNotificationSchema();
    // Starts a guarded block so the caller can show a friendly message.
    try {
        // Loads the comment and confirms that this student owns its application.
        $row = DB::query(
            // Selects the application and text for the comment.
            'SELECT c.`application_id`, c.`comment_text`
               FROM `application_comments` c
               INNER JOIN `applications` a ON a.`application_id` = c.`application_id`
              WHERE c.`comment_id` = :comment_id
                AND a.`user_id` = :user_id
              LIMIT 1',
            // Binds the comment and the signed-in student.
            ['comment_id' => $commentId, 'user_id' => $userId]
        // Closes the query call.
        )->fetch();
        // Stops when the comment is missing or belongs to another student.
        if (!$row) {
            // Returns false because the student may not remove it.
            return false;
        // Closes the ownership check.
        }
        // Reads the application and text that identify this comment.
        $applicationId = (int) ($row['application_id'] ?? 0);
        $commentText   = (string) ($row['comment_text'] ?? '');
        // Marks every copy of the same comment text as removed by the student.
        $stmt = DB::getConnection()->prepare(
            // Updates the application/text pair so repeated saves are covered.
            'UPDATE `application_comments`
                SET `student_dismissed_at` = NOW()
              WHERE `application_id` = :application_id
                AND `comment_text` = :comment_text
                AND `student_dismissed_at` IS NULL'
        );
        // Runs the update.
        $stmt->execute([
            // Binds the application that owns the comment.
            'application_id' => $applicationId,
            // Binds the comment text being removed.
            'comment_text'   => $commentText,
        // Closes the parameter list.
        ]);
        // Updates the application column used by the other student pages.
        refreshApplicationCoordinatorComment($applicationId, $commentText);
        // Reports that the comment is now removed for this student.
        return true;
    // Catches database errors and reports a failed update.
    } catch (Throwable $e) {
        // Returns false when the comment could not be removed.
        return false;
    // Closes the catch block.
    }
// Closes the removeStudentComment function.
}

/**
 * Remove a comment from the student side while keeping it in history.
 *
 * The coordinator can remove any comment. Every copy of the same text on that
 * application is marked removed, so no older duplicate stays visible.
 */
// Removes a comment as the coordinator.
function removeCoordinatorComment(int $commentId, int $coordinatorUserId): bool {
    // Rejects invalid ids or a missing DB class.
    if ($commentId <= 0 || !class_exists('DB')) {
        // Returns false for an invalid request.
        return false;
    // Closes the request check.
    }
    // Makes sure the removal columns exist before they are updated.
    ensureStudentCommentNotificationSchema();
    // Starts a guarded block so the caller can show a friendly message.
    try {
        // Loads the application and text for the comment being removed.
        $row = DB::query(
            // Selects the application and comment text.
            'SELECT `application_id`, `comment_text` FROM `application_comments` WHERE `comment_id` = :comment_id LIMIT 1',
            // Binds the comment id.
            ['comment_id' => $commentId]
        // Closes the query call.
        )->fetch();
        // Stops when the comment does not exist.
        if (!$row) {
            // Returns false for an unknown comment.
            return false;
        // Closes the missing-comment check.
        }
        // Reads the application and text that identify this comment.
        $applicationId = (int) ($row['application_id'] ?? 0);
        $commentText   = (string) ($row['comment_text'] ?? '');
        // Marks every copy of the same comment text as removed by the coordinator.
        $stmt = DB::getConnection()->prepare(
            // Updates the application/text pair so repeated saves are covered.
            'UPDATE `application_comments`
                SET `coordinator_removed_at` = NOW(),
                    `coordinator_removed_by` = :coordinator_user_id
              WHERE `application_id` = :application_id
                AND `comment_text` = :comment_text
                AND `coordinator_removed_at` IS NULL'
        );
        // Runs the update with the coordinator identity.
        $stmt->execute([
            // Binds the coordinator who removed the comment.
            'coordinator_user_id' => $coordinatorUserId > 0 ? $coordinatorUserId : null,
            // Binds the application that owns the comment.
            'application_id'      => $applicationId,
            // Binds the comment text being removed.
            'comment_text'        => $commentText,
        // Closes the parameter list.
        ]);
        // Updates the application column so the student pages stop showing it.
        refreshApplicationCoordinatorComment($applicationId, $commentText);
        // Reports that the comment is now removed from the student side.
        return true;
    // Catches database errors and reports a failed update.
    } catch (Throwable $e) {
        // Returns false when the comment could not be removed.
        return false;
    // Closes the catch block.
    }
// Closes the removeCoordinatorComment function.
}
