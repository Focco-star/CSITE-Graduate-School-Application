<?php
/* CSITE configuration module: Coordinator status rules and student/application query helpers. */

/**
 * Every registered student together with the applications they have submitted.
 *
 * Students who have not applied yet are part of the result, so the coordinator
 * can see at a glance who is registered but has not submitted an application.
 * The applications of a student are kept together under the student, newest
 * first, so one student never appears twice because of several applications.
 */
// Lists every registered student with the applications they submitted.
function coordinatorStudentsWithApplications(): array {
    // Starts the list of students.
    $students = [];
    // Starts the applications grouped by user id.
    $applicationsByUser = [];

    // Only queries the database when the DB class is loaded.
    if (class_exists('DB')) {
        // Starts the block that may throw database errors.
        try {
            // Gets the shared database connection.
            $pdo = DB::getConnection();

            // Prepares the query for all registered students.
            $stmt = $pdo->prepare(
                // Selects the user and student details of each student.
                'SELECT u.user_id AS userId, u.full_name, u.email, u.created_at AS registeredAt,
                        s.student_id, s.first_name, s.last_name, s.middle_initial,
                        s.program, s.track, s.adviser_name, s.enrollment_date
                 FROM students s
                 INNER JOIN users u ON u.user_id = s.user_id
                 WHERE u.role = "student" AND s.archived_at IS NULL
                 ORDER BY s.last_name, s.first_name, s.middle_initial'
            // Closes the student select statement.
            );
            // Runs the student query.
            $stmt->execute();

            // Prepares the query for the applications of those students.
            $appStmt = $pdo->prepare(
                // Selects the application fields the coordinator list needs.
                'SELECT a.application_id AS id, a.user_id, a.presentation_stage AS stage,
                        a.paper_title AS title, a.status, a.submitted_at AS date,
                        a.result, a.coordinator_comment AS coordinatorComment,
                        a.grad_school_endorsed AS gradSchoolEndorsed,
                        a.payment_recorded AS paymentRecorded,
                        a.ready_for_presentation AS readyForPresentation,
                        a.receipt_number AS receiptNumber
                 FROM applications a
                 WHERE a.archived_at IS NULL
                 ORDER BY a.submitted_at DESC, a.application_id DESC'
            // Closes the application select statement.
            );
            // Runs the application query.
            $appStmt->execute();
            // Loops through every application row.
            foreach ($appStmt->fetchAll() as $application) {
                // Groups each application under its owner's user id.
                $applicationsByUser[(int) $application['user_id']][] = $application;
            // Closes the application loop.
            }

            // Loops through the student rows.
            foreach ($stmt->fetchAll() as $row) {
                // Reads the numeric user id of the student.
                $userId = (int) $row['userId'];
                // Works out the program code of the student.
                $programCode = programCodeForStudent($row);
                // Reads the track stored on the student record.
                $rawTrack = (string) ($row['track'] ?? '');
                // Checks whether the student has no track stored.
                if ($rawTrack === '') {
                    // Falls back to the default track of the program.
                    $rawTrack = getTrackForProgram($programCode);
                // Closes the missing track check.
                }
                // Turns the track key into its display label.
                $trackLabel = getTrackLabel($rawTrack);
                // Adds one student entry to the result list.
                $students[] = [
                    // Sets the user id of the student.
                    'userId'       => $userId,
                    // Sets the school student id.
                    'studentId'    => (string) ($row['student_id'] ?? ''),
                    // Builds the student's display name.
                    'name'         => studentDisplayName($row),
                    // Sets the student's email address.
                    'email'        => (string) ($row['email'] ?? ''),
                    // Sets the short program code.
                    'program'      => $programCode,
                    // Sets the full program name.
                    'programName'  => PROGRAMS[$programCode] ?? (string) ($row['program'] ?? ''),
                    // Sets the raw track key.
                    'track'        => $rawTrack,
                    // Sets the track display label.
                    'trackLabel'   => $trackLabel,
                    // Sets the adviser name.
                    'adviser'      => (string) ($row['adviser_name'] ?? ''),
                    // Sets the enrollment date.
                    'enrolled'     => (string) ($row['enrollment_date'] ?? ''),
                    // Sets the date the account was registered.
                    'registeredAt' => (string) ($row['registeredAt'] ?? ''),
                    // Notes that each application carries its program and track.
                    // Every application carries the program and the track of the
                    // Explains why the Applications menu can filter by program.
                    // student it belongs to, so the Applications menu can filter
                    // Continues the note about filtering application rows.
                    // on the program of a single application row.
                    // Maps each application to add the program code and track label.
                    'applications' => array_map(static function (array $application) use ($programCode, $trackLabel) {
                        // Sets the program code on the application row.
                        $application['program'] = $programCode;
                        // Sets the track label on the application row.
                        $application['track'] = $trackLabel;
                        // Returns the updated application row.
                        return $application;
                    // Runs the mapping over the student's applications, or none.
                    }, $applicationsByUser[$userId] ?? []),
                // Closes the student entry array.
                ];
            // Closes the student loop.
            }
        // Catches any database error.
        } catch (Throwable $e) {
            // Returns an empty list when the query fails.
            return [];
        // Closes the try/catch block.
        }
    // Closes the DB availability check.
    }

    // Returns the collected students.
    return $students;
// Ends coordinatorStudentsWithApplications.
}

/**
 * Why a coordinator may not move an application to a status yet, or null when
 * the move is allowed.
 *
 * The status has to agree with what the student has actually done, so a
 * record cannot claim to be ready, scheduled or finished while the milestone
 * workflow says otherwise.
 */
/**
 * Why a status may not be saved.
 *
 * $statusChanged tells whether the coordinator is actually moving the
 * student to this status. When they are only working on a document the
 * status stays where it is, and the requirements of a status they are not
 * choosing may not block them: sending a document back for a revision
 * would otherwise be impossible while the student is marked as ready.
 *
 * Returns null when the status may be saved.
 */
// Explains why a status cannot be saved yet, or null when it can.
function applicationStatusBlockedReason(string $status, array $milestoneSteps, string $result = '', bool $statusChanged = true, string $currentStatus = ''): ?string {
    // Starts a map of milestone key to state.
    $state = [];
    // Loops through the milestone steps.
    foreach ($milestoneSteps as $step) {
        // Stores each milestone status by its key.
        $state[$step['key'] ?? ''] = $step['state']['status'] ?? 'pending';
    // Closes the state map loop.
    }

    // Stops a completed stage from being moved back to an earlier status.
    if ($statusChanged && in_array($currentStatus, ['completed', 'approved'], true) && $status !== $currentStatus
        && !($currentStatus === 'approved' && $status === 'completed')) {
        // Explains that a finished stage is closed.
        return 'This stage is already marked as Presentation Complete and cannot be moved back to an earlier status.';
    }

    // Keeps the approved result on a completed stage intact.
    if (!$statusChanged && $currentStatus === 'completed' && $status === 'completed' && $result !== 'approved') {
        // Explains that a completed stage keeps its approved result.
        return 'The approved result of a completed stage cannot be cleared or changed.';
    }

    // Allows the early statuses that need no milestone checks.
    if ($status === '' || $status === 'submitted' || $status === 'under_review') {
        // Returns null so the status may be saved.
        return null;
    // Closes the early status check.
    }

    // Checks whether the coordinator chose ready for presentation.
    if ($status === 'ready_for_presentation') {
        // Checks whether the status itself is not being changed.
        if (!$statusChanged) {
            // The student is not being moved now, so document work stays allowed.
            return null;
        // Closes the unchanged status check.
        }
        // A scheduled or finished presentation cannot move back to ready.
        if (($state['presentation'] ?? 'pending') === 'done') {
            // Returns the reason when the presentation is already on record.
            return 'The presentation is already scheduled, so the stage cannot be moved back to Ready for Presentation.';
        // Closes the already-scheduled check.
        }
        // Requires every milestone before readiness to be complete.
        foreach (['paper', 'adviser_endorsement', 'coord_review', 'grad_school_endorsement', 'payment'] as $requiredKey) {
            // Checks whether this required milestone is done.
            if (($state[$requiredKey] ?? 'pending') !== 'done') {
                // Returns the reason when a prerequisite is still open.
                return 'The student cannot be marked as Ready for Presentation until the paper, adviser endorsement, coordinator review, Graduate School endorsement, and payment are complete.';
            // Closes the required milestone check.
            }
        // Closes the prerequisite loop.
        }
        // Allows the ready status when every prerequisite is done.
        return null;
    // Closes the ready-for-presentation branch.
    }

    // Checks whether the coordinator chose scheduled.
    if ($status === 'scheduled') {
        // Checks whether the status itself is not being changed.
        if (!$statusChanged) {
            // The student is not being moved now, so document work stays allowed.
            return null;
        // Closes the unchanged status check.
        }
        // Requires a verified presentation schedule first.
        if (($state['presentation'] ?? 'pending') !== 'done') {
            // Returns the reason when no confirmed schedule exists.
            return 'The student cannot be marked as Scheduled for Presentation until the presentation schedule is confirmed.';
        // Closes the presentation check.
        }
        // Allows the scheduled status.
        return null;
    // Closes the scheduled branch.
    }

    // Checks whether the coordinator chose completed.
    if ($status === 'completed') {
        // Checks whether the status itself is not being changed.
        if (!$statusChanged) {
            // The student is not being moved now, so document work stays allowed.
            return null;
        // Closes the unchanged status check.
        }
        // Requires a confirmed presentation before the stage can finish.
        if (($state['presentation'] ?? 'pending') !== 'done') {
            // Returns the reason when the presentation has not been verified.
            return 'The student cannot be marked as Presentation Complete before the presentation is scheduled and confirmed.';
        // Closes the presentation check.
        }
        // A revision result never completes the stage.
        if ($result === 'requires_revision') {
            // Returns the reason when the result still needs revision.
            return 'The student cannot be marked as Presentation Complete while the presentation result requires revision.';
        // Closes the revision check.
        }
        // Requires an approved result.
        if (trim($result) === '') {
            // Returns the reason when the result is missing.
            return 'The student cannot be marked as Presentation Complete before the presentation result is recorded.';
        // Closes the empty-result check.
        }
        // Only an approved result completes the stage.
        if ($result !== 'approved') {
            // Returns the reason when the result is not approved.
            return 'The student cannot be marked as Presentation Complete until the presentation result is approved.';
        // Closes the approved-result check.
        }
        // Allows the completed status once the result is approved.
        return null;
    // Closes the completed branch.
    }

    // Returns null for any other status.
    return null;
// Ends applicationStatusBlockedReason.
}
