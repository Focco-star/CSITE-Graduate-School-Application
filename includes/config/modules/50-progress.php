<?php
/* CSITE configuration module: Current-stage calculation, stage locks, student progress, and stage sequence validation. */

/**
 * Determine the index of the student's current workflow stage.
 *
 * The coordinator's "advance to next stage" renames the single application row's
 * presentation_stage (rather than keeping a row per completed stage), so earlier
 * stages legitimately have no row and read as 'not_started'. Scanning back-to-front
 * and taking the furthest stage that actually carries a status makes the stepper
 * land on the stage the student is really at, not on an earlier blank stage.
 */
// Finds which workflow stage the student is currently on.
function workflowCurrentIndex(array $progress, array $stages): int {
    // Counts how many stages the track has.
    $count = count($stages);
    // Handles a track with no stages.
    if ($count === 0) {
        // Returns the first index when there are no stages.
        return 0;
    // Closes the empty track check.
    }
    // Scans the stages from last to first.
    for ($i = $count - 1; $i >= 0; $i--) {
        // Reads the progress row for this stage, or an empty array.
        $p = $progress[$i] ?? [];
        // Reads the stage status, defaulting to not_started.
        $st = $p['stageStatus'] ?? 'not_started';
        // Checks whether the status means the student started the stage.
        if (!in_array($st, ['not_started', 'pending', 'draft', ''], true)) {
            // Checks whether that started stage is still unfinished.
            if (!in_array($st, ['completed', 'approved'], true)) {
                // Returns this stage as the current one.
                return $i;
            // Closes the unfinished stage check.
            }
            // Moves to the next stage, capped at the last index.
            return min($i + 1, $count - 1);
        // Closes the started stage block.
        }
    // Closes the backwards stage loop.
    }
    // Returns the first stage when nothing has been started.
    return 0;
// Closes workflowCurrentIndex().
}

/**
 * The workflow stage a student is currently working on, as its presentation
 * label ('Concept Paper', ...).
 *
 * A presentation schedule can only exist for the stage a student is on, so
 * the Coordinator's schedule forms take this as the authoritative stage of
 * the schedule, whatever the form itself tried to send. It works for every
 * track, because the stage always comes from the student's own track
 * workflow - thesis, capstone and seminar paper alike.
 */
// Gets the label of the stage the student is currently working on.
function studentCurrentStageLabel(string $email, array $student): string {
    // Reads and trims the student's track.
    $track = trim((string) ($student['track'] ?? ''));
    // Handles a student with no stored track.
    if ($track === '') {
        // Works out the track from the student's program instead.
        $track = getTrackForProgram((string) ($student['program'] ?? ''));
    // Closes the missing track check.
    }
    // Loads the ordered stages of the student's track.
    $stages = getWorkflow($track)['stages'];
    // Handles a track without workflow stages.
    if (!$stages) {
        // Returns an empty label because no stage exists.
        return '';
    // Closes the empty stages check.
    }
    // Works out the index of the student's current stage.
    $currentIdx = workflowCurrentIndex(getStudentProgress($email, $track), $stages);
    // Returns the stage label, or empty when none was found.
    return (string) ($stages[$currentIdx]['label'] ?? '');
// Closes studentCurrentStageLabel().
}

/**
 * Every presentation stage of the student's track, in workflow order, with the
 * state the Schedule Presentation form shows:
 *
 *  - open      : may be scheduled now
 *  - scheduled : already has a presentation schedule (locked)
 *  - future    : the student has not reached the stage yet (locked)
 *
 * The list follows the milestone sequence of the student's track, so it works
 * the same for thesis, capstone and seminar-paper students.
 *
 * @return array<int, array{key: string, label: string, locked: bool, state: string, reason: string}>
 */
// Lists every presentation stage with its open, scheduled or future state.
function studentPresentationStageChoices(string $email, array $student): array {
    // Reads and trims the student's track.
    $track = trim((string) ($student['track'] ?? ''));
    // Handles a student with no stored track.
    if ($track === '') {
        // Works out the track from the student's program instead.
        $track = getTrackForProgram((string) ($student['program'] ?? ''));
    // Closes the missing track check.
    }
    // Loads the ordered stages of the student's track.
    $workflowStages = getWorkflow($track)['stages'];
    // Handles a track without workflow stages.
    if (!$workflowStages) {
        // Returns an empty list because there is nothing to schedule.
        return [];
    // Closes the empty stages check.
    }

    // Loads the student's milestone progress.
    $progress = getStudentProgress($email, $track);
    // Works out the index of the stage they are on.
    $currentIdx = workflowCurrentIndex($progress, $workflowStages);

    // Starts a block comment explaining why a stage cannot be scheduled twice.
    /*
     * A stage that already has a schedule cannot be scheduled twice. The
     * schedule table is the record the Presentation Schedules page shows; the
     * workflow state is checked as well so a presentation recorded before the
     * schedules table existed still locks its stage.
     */
    // Starts the list of stage keys that already have a schedule.
    $scheduledKeys = [];
    // Loops over every schedule saved for this student.
    foreach (schedulesForEmail($email) as $schedule) {
        // Turns the schedule's stage text into a stage key.
        $key = stageKeyFromLabel((string) ($schedule['stage'] ?? ''));
        // Checks that a known stage key was found.
        if ($key !== '') {
            // Marks this stage key as already scheduled.
            $scheduledKeys[$key] = true;
        // Closes the stage key check.
        }
    // Closes the schedule loop.
    }
    // Also checks the progress rows for recorded presentations.
    foreach ($progress as $row) {
        // Reads the stage key of the progress row.
        $key = (string) ($row['stageKey'] ?? '');
        // Checks whether this row already has a presentation date.
        if ($key !== '' && !empty($row['presentation']['date'])) {
            // Marks the stage as scheduled so it cannot be booked twice.
            $scheduledKeys[$key] = true;
        // Closes the presentation date check.
        }
    // Closes the progress loop.
    }

    // Starts the list of stage choices shown in the form.
    $choices = [];
    // Loops over each stage in workflow order.
    foreach ($workflowStages as $index => $stage) {
        // Reads the stage key.
        $key = (string) $stage['key'];
        // Reads the stage label used in messages.
        $label = (string) $stage['label'];
        // Starts the stage as open by default.
        $state = 'open';
        // Starts the reason text as empty.
        $reason = '';
        // Checks whether this stage already has a schedule.
        if (isset($scheduledKeys[$key])) {
            // Marks the stage as scheduled and locked.
            $state = 'scheduled';
            // Explains that the existing schedule should be edited instead.
            $reason = 'The ' . $label . ' is already scheduled for this student. Edit that schedule instead of creating a new one.';
        // Otherwise checks whether the stage is still in the future.
        } elseif ($index > $currentIdx) {
            // Marks the stage as future and locked.
            $state = 'future';
            // Explains that the student has not reached this stage.
            $reason = 'The student has not reached the ' . $label . ' stage yet.';
        // Closes the future stage check.
        }
        // Adds this stage's choice entry to the list.
        $choices[] = [
            // Stores the stage key.
            'key' => $key,
            // Stores the stage label.
            'label' => $label,
            // Locks the stage unless its state is open.
            'locked' => $state !== 'open',
            // Stores the computed state.
            'state' => $state,
            // Stores the reason shown to the coordinator.
            'reason' => $reason,
        // Closes the choice entry array.
        ];
    // Closes the stage loop.
    }

    // Returns all stage choices.
    return $choices;
// Closes studentPresentationStageChoices().
}

/**
 * The presentation stages a coordinator may schedule right now: the stages of
 * the student's track that are neither scheduled yet nor still in the future.
 */
// Returns the stages the coordinator may schedule right now.
function studentValidPresentationStages(string $email, array $student): array {
    // Starts the list of open stages.
    $open = [];
    // Loops over every presentation stage choice.
    foreach (studentPresentationStageChoices($email, $student) as $choice) {
        // Only keeps stages that are not locked.
        if (!$choice['locked']) {
            // Adds the stage key and label to the open list.
            $open[] = ['key' => $choice['key'], 'label' => $choice['label']];
        // Closes the unlocked check.
        }
    // Closes the choice loop.
    }
    // Returns the open stages.
    return $open;
// Closes studentValidPresentationStages().
}

/**
 * Stage visual state for the stepper: 'done' when completed/approved or already
 * passed by the current stage, 'active' for the current stage, otherwise 'pending'.
 */
// Returns the stepper state: done, active or pending.
function workflowStageState(array $p, int $idx, int $currentIdx): string {
    // Reads the stage status, defaulting to not_started.
    $st = $p['stageStatus'] ?? 'not_started';
    // Treats finished or already passed stages as done.
    if (in_array($st, ['completed', 'approved'], true) || $idx < $currentIdx) {
        // Returns 'done' for this stage.
        return 'done';
    // Closes the done check.
    }
    // Returns 'active' for the current stage, otherwise 'pending'.
    return $idx === $currentIdx ? 'active' : 'pending';
// Closes workflowStageState().
}

/**
 * The lock state of every stage of a track.
 *
 * The stage the student is working on is open. Every stage after it stays
 * locked until the coordinator approves the progression, and only the stage
 * that is approved next is unlocked - the ones after it remain locked.
 *
 * The order comes from the track itself, so a thesis student walks
 * Concept Paper -> Thesis Proposal -> Final Thesis, a capstone student
 * Capstone Proposal -> Final Capstone, and a seminar-paper student
 * Seminar Paper Proposal -> Final Seminar Paper.
 *
 * Returns one entry per stage key:
 *   locked => whether the stage is still closed to the student
 *   label  => the badge text
 *   reason => why it is locked
 */
// Builds the lock state of every stage for the given current index.
function stageLockStates(array $stages, int $currentIndex): array {
    // Reads the current stage label used in the lock reason.
    $currentLabel = $stages[$currentIndex]['label'] ?? 'the current stage';
    // Starts the lock state map.
    $states = [];
    // Loops over the stages in workflow order.
    foreach (array_values($stages) as $index => $stage) {
        // Locks every stage that comes after the current one.
        $locked = $index > $currentIndex;
        // Starts the state entry for this stage key.
        $states[$stage['key']] = [
            // Stores whether the stage is locked.
            'locked' => $locked,
            // Shows 'Locked' or 'Open' as the badge text.
            'label'  => $locked ? 'Locked' : 'Open',
            // Starts the reason text.
            'reason' => $locked
                // Explains that the coordinator must approve the current stage.
                ? 'Locked until the coordinator approves ' . $currentLabel . '.'
                // Leaves the reason empty for open stages.
                : '',
        // Closes the state entry array.
        ];
    // Closes the stage loop.
    }
    // Returns the lock state map.
    return $states;
// Closes stageLockStates().
}

/**
 * The stage a student is allowed to work on, and whether a requested stage is
 * still locked for them.
 *
 * Returns ['stageKey' => ..., 'locked' => bool, 'reason' => string]. A locked
 * stage falls back to the stage the student is really at, so a student can
 * never reach a future stage before the coordinator approved it.
 */
// Decides which stage a student may work on and whether it is locked.
function resolveStudentStageAccess(array $progress, array $stages, int $currentIndex, string $requestedKey): array {
    // Builds the lock states for the student's stages.
    $states = stageLockStates($stages, $currentIndex);
    // Casts the requested stage key to a string.
    $requestedKey = (string) $requestedKey;
    // Checks that the requested key exists in the track.
    if ($requestedKey !== '' && isset($states[$requestedKey])) {
        // Checks whether the requested stage is not locked.
        if (!$states[$requestedKey]['locked']) {
            // Allows the requested stage with no lock reason.
            return ['stageKey' => $requestedKey, 'locked' => false, 'reason' => ''];
        // Closes the unlocked check.
        }
        // Returns a locked result for a future stage.
        return [
            // Falls back to the first stage key when resolving the key.
            'stageKey' => $states ? array_key_first($states) : $requestedKey,
            // Marks the result as locked.
            'locked'   => true,
            // Returns the reason the stage is locked.
            'reason'   => $states[$requestedKey]['reason'],
        // Closes the locked result array.
        ];
    // Closes the requested key check.
    }
    // Reads the ordered stage keys.
    $keys = array_keys($states);
    // Returns the stage at the current index.
    return [
        // Uses the current index, falling back to the first key.
        'stageKey' => $keys[$currentIndex] ?? ($keys[0] ?? ''),
        // This fallback stage is not locked.
        'locked'   => false,
        // Has no lock reason.
        'reason'   => '',
    // Closes the fallback result array.
    ];
// Closes resolveStudentStageAccess().
}

// Builds an empty progress row for one workflow stage.
function blankStageProgress(array $stage): array {
    // Reuses one 'pending' status array for every milestone.
    $pending = ['status' => 'pending'];
    // Starts the progress row returned to the caller.
    return [
        // Stores the stage key.
        'stageKey' => $stage['key'],
        // Marks the stage as not started.
        'stageStatus' => 'not_started',
        // Paper milestone starts as pending.
        'paper' => $pending,
        // Adviser endorsement milestone starts as pending.
        'adviserEndorsement' => $pending,
        // Coordinator review milestone starts as pending.
        'coordReview' => $pending,
        // Graduate School endorsement milestone starts as pending.
        'gradSchoolEndorsement' => $pending,
        // Payment milestone starts as pending.
        'payment' => $pending,
        // Ready-for-presentation milestone starts as pending.
        'readyForPresentation' => $pending,
        // Presentation milestone starts as pending.
        'presentation' => $pending,
        // Result milestone starts as pending.
        'result' => $pending,
        // No coordinator comment yet.
        'coordinatorComments' => '',
    // Closes the progress row array.
    ];
// Closes blankStageProgress().
}

// Builds the milestone progress rows for a student and track.
function getStudentProgress(string $email, string $track): array {
    // Loads the ordered stages of the student's track.
    $stages = getWorkflow($track)['stages'];
    // Filters the stored applications down to this student's email.
    $apps = array_values(array_filter(storeGet('applications'), static function ($a) use ($email) {
        // Keeps only applications whose student email matches.
        return strcasecmp((string) $a['studentEmail'], $email) === 0;
    // Closes the filter callback call.
    }));
    // Loads the student's uploaded documents.
    $uploads = uploadsForEmail($email);
    // Loads the presentation schedules for the student.
    $schedules = schedulesForEmail($email);

    // Uses the database when the connection class is available.
    if (class_exists('DB')) {
        // Starts the block that tolerates database errors.
        try {
            // Reads the student's applications with all milestone columns.
            $stmt = DB::getConnection()->prepare('SELECT a.application_id AS id, a.presentation_stage AS stage, a.paper_title AS title, a.status, a.coordinator_comment AS coordinatorComment, a.grad_school_endorsed AS gradSchoolEndorsed, a.payment_recorded AS paymentRecorded, a.receipt_number AS receiptNumber, a.payment_date AS paymentDate, a.payment_amount AS paymentAmount, a.ready_for_presentation AS readyForPresentation, a.workflow_state AS workflowState, a.result AS result, a.submitted_at AS date FROM applications a INNER JOIN users u ON u.user_id = a.user_id WHERE u.email = :email AND a.archived_at IS NULL ORDER BY a.submitted_at ASC');
            // Runs the application query with the student's email.
            $stmt->execute(['email' => $email]);
            // Fetches all matching application rows.
            $dbApps = $stmt->fetchAll();
            // Checks whether the database returned any applications.
            if ($dbApps) {
                // Maps each database row to the progress format and decodes its state.
                $apps = array_map(static function ($app) use ($email, $track) { $app['studentEmail'] = $email; $app['track'] = $track; $app['stageKey'] = stageKeyFromLabel($app['stage']); $app['workflowState'] = json_decode((string) ($app['workflowState'] ?? '[]'), true) ?: []; return $app; }, $dbApps);
                // Reads the student's uploaded documents with their review status.
                $docStmt = DB::getConnection()->prepare('SELECT d.document_id AS id, d.application_id AS applicationId, d.original_name AS fileName, d.document_type AS docType, d.stage, d.file_size AS size, d.uploaded_at AS date, d.status, d.stored_name AS storedFile, d.mime_type AS mimeType FROM application_documents d INNER JOIN users u ON u.user_id = d.user_id WHERE u.email = :email ORDER BY d.uploaded_at ASC, d.document_id ASC');
                // Runs the documents query with the student's email.
                $docStmt->execute(['email' => $email]);
                // Fetches all matching document rows.
                $dbUploads = $docStmt->fetchAll();
                // Replaces the uploads with database documents when any were found.
                if ($dbUploads) $uploads = $dbUploads;
            // Closes the database rows check.
            }
        // Catches database errors so the tracker still renders.
        } catch (Throwable $e) {
        // Ignores the error and keeps the stored fallback data.
        }
    // Closes the database block.
    }

    // Starts the list of progress rows.
    $rows = [];
    // Tracks the furthest stage the student has reached.
    $activeStageIndex = -1;
    // Loops over the student's applications.
    foreach ($apps as $app) {
        // Works out the application's stage key.
        $appStageKey = $app['stageKey'] ?? stageKeyFromLabel($app['stage'] ?? '');
        // Loops over the track stages with their indexes.
        foreach ($stages as $stageIndex => $stage) {
            // Checks whether this application matches the stage.
            if ($appStageKey === $stage['key']) {
                // Keeps the furthest matching stage index.
                $activeStageIndex = max($activeStageIndex, $stageIndex);
                // Stops looking once the stage matched.
                break;
            // Closes the stage match check.
            }
        // Closes the stage loop.
        }
    // Closes the application loop.
    }

    // Builds one progress row per workflow stage.
    foreach ($stages as $stageIndex => $stage) {
        // Starts with no application for this stage.
        $app = null;
        // Loops over the applications to find one for this stage.
        foreach ($apps as $a) {
            // Works out the application's stage key.
            $key = $a['stageKey'] ?? stageKeyFromLabel($a['stage'] ?? '');
            // Matches by stage key or by the stage label text.
            if ($key === $stage['key'] || strcasecmp((string) ($a['stage'] ?? ''), $stage['label']) === 0) {
                // Remembers the application for this stage.
                $app = $a;
            // Closes the stage match check.
            }
        // Closes the application loop.
        }
        // Checks whether no application was found for the stage.
        if (!$app) {
            // Tries a second, stricter match by stage key only.
            foreach ($apps as $a) {
                // Works out the application's stage key.
                $key = $a['stageKey'] ?? stageKeyFromLabel($a['stage'] ?? '');
                // Checks whether the stage key matches exactly.
                if ($key === $stage['key']) {
                    // Remembers the application for this stage.
                    $app = $a;
                    // Stops looking once the key matched.
                    break;
                // Closes the key match check.
                }
            // Closes the key loop.
            }
        // Closes the second match block.
        }
        // Checks again whether still no application was found.
        if (!$app) {
            // Builds a blank progress row for this stage.
            $blank = blankStageProgress($stage);
            // Checks whether the student already passed this stage.
            if ($activeStageIndex > $stageIndex) {
                // Marks the past stage as completed.
                $blank['stageStatus'] = 'completed';
                // Marks the paper milestone as done for a past stage.
                $blank['paper'] = ['status' => 'done'];
                // Marks the adviser endorsement as done.
                $blank['adviserEndorsement'] = ['status' => 'done'];
                // Marks the coordinator review as done with no comment.
                $blank['coordReview'] = ['status' => 'done', 'comment' => ''];
                // Marks the Graduate School endorsement as done.
                $blank['gradSchoolEndorsement'] = ['status' => 'done'];
                // Marks the payment milestone as done.
                $blank['payment'] = ['status' => 'done'];
                // Marks the presentation-ready milestone as done.
                $blank['readyForPresentation'] = ['status' => 'done'];
                // Marks the presentation as done.
                $blank['presentation'] = ['status' => 'done'];
                // Marks the result as approved.
                $blank['result'] = ['status' => 'done', 'value' => 'approved'];
            // Closes the past stage check.
            }
            // Adds the blank row to the progress list.
            $rows[] = $blank;
            // Skips the rest and moves to the next stage.
            continue;
        // Closes the missing application block.
        }

        // Reads the application's workflow state array.
        $wf = $app['workflowState'] ?? [];
        // Starts with no paper upload.
        $paperUpload = null;
        // Starts with no adviser endorsement upload.
        $adviserUpload = null;
        // Starts with no Graduate School endorsement upload.
        $gradSchoolUpload = null;
        // Starts with no payment receipt upload.
        $paymentUpload = null;
        // Starts the list of uploads for this stage.
        $stageUploads = [];
        // Loops over the student's uploaded documents.
        foreach ($uploads as $u) {
            // Checks whether the upload belongs to this stage by short label.
            $sameStage = stripos((string) $u['stage'], $stage['shortLabel'] ?? $stage['label']) !== false
                // Also checks the upload stage against the full stage label.
                || stripos((string) $u['stage'], $stage['label']) !== false
                // Also checks the upload stage by its stage key.
                || stageKeyFromLabel((string) $u['stage']) === $stage['key'];
            // Checks whether this upload belongs to another stage.
            if (!$sameStage) {
                // Skips uploads that belong to another stage.
                continue;
            // Closes the other stage check.
            }
            // Adds this upload to the stage's list.
            $stageUploads[] = $u;
        // Closes the upload loop.
        }
        // Starts the note explaining which upload decides a milestone.
        /*
         * The file that was sent last is the one that is reviewed, so it is
         * the one that decides the state of the milestone. The same rule is
         * used for the list of documents of the coordinator and for the
         * requirements of the stage, so the two portals can never disagree
         * about the same document.
         */
        // Picks the newest paper upload for this stage.
        $paperUpload      = latestUploadOfKind($stageUploads, 'paper');
        // Picks the newest adviser endorsement upload.
        $adviserUpload    = latestUploadOfKind($stageUploads, 'adviser_endorsement');
        // Picks the newest Graduate School endorsement upload.
        $gradSchoolUpload = latestUploadOfKind($stageUploads, 'grad_school_endorsement');
        // Picks the newest payment receipt upload.
        $paymentUpload    = latestUploadOfKind($stageUploads, 'receipt');

        // Reads the paper status stored in the workflow state.
        $paperWf = $wf['paper'] ?? '';
        // Reads the adviser endorsement status from the workflow state.
        $advWf = $wf['adviser_endorsement'] ?? '';
        // Reads the Graduate School endorsement status.
        $gradWf = $wf['grad_school_endorsement'] ?? '';
        // Reads the payment status from the workflow state.
        $paymentWf = $wf['payment'] ?? '';

        // Starts the note that document status decides the milestone.
        /*
         * The status of the document itself decides the state of the
         * milestone. The coordinator reviews every document and picks one of
         * the four actions; the student sees exactly that on their tracker.
         * A document that was only uploaded waits for the review, so it does
         * not move the student on before the coordinator had a look at it.
         */
        // Checks whether a paper upload exists for this stage.
        if ($paperUpload) {
            // Uses the paper document's own status as the paper state.
            $paperWf = (string) ($paperUpload['status'] ?? 'submitted');
        // Closes the paper upload check.
        }
        // Checks whether an adviser endorsement upload exists.
        if ($adviserUpload) {
            // Uses the endorsement document's own status.
            $advWf = (string) ($adviserUpload['status'] ?? 'submitted');
        // Closes the adviser upload check.
        }
        // Checks whether a Graduate School endorsement upload exists.
        if ($gradSchoolUpload) {
            // Uses the endorsement document's own status.
            $gradWf = (string) ($gradSchoolUpload['status'] ?? 'submitted');
        // Closes the Graduate School upload check.
        }
        // Checks whether a payment receipt upload exists.
        if ($paymentUpload) {
            // Uses the receipt document's own status.
            $paymentWf = (string) ($paymentUpload['status'] ?? 'submitted');
        // Closes the payment upload check.
        }

        // Maps the paper review status to a milestone state.
        $paperStatus = $paperUpload ? documentMilestoneState($paperWf) : 'pending';
        // Maps the adviser endorsement status to a milestone state.
        $adviserStatus = $adviserUpload ? documentMilestoneState($advWf) : 'pending';
        // Flags coordinator review when the paper or endorsement is flagged.
        $coordStatus = ($paperStatus === 'flagged' || $adviserStatus === 'flagged')
            // Uses 'flagged' for the coordinator review state.
            ? 'flagged'
            // Otherwise done only when both documents are done.
            : (($paperStatus === 'done' && $adviserStatus === 'done') ? 'done' : 'pending');
        // Maps the Graduate School endorsement status to a milestone state.
        $gradStatus = $gradSchoolUpload ? documentMilestoneState($gradWf) : 'pending';
        // Starts the payment state from the receipt when one exists.
        $paymentStatus = $paymentUpload
            // Maps the receipt review status to a milestone state.
            ? documentMilestoneState($paymentWf)
            // Falls back to the recorded payment flag, otherwise pending.
            : (paymentIsRecorded($app) ? 'done' : 'pending');

        // Starts with no schedule for this stage.
        $schedule = null;
        // Starts the presentation state as pending.
        $presentationStatus = 'pending';
        // Starts the presentation date as empty.
        $presentationDate = '';
        // Starts the presentation time as empty.
        $presentationTime = '';
        // Starts the presentation venue as empty.
        $presentationVenue = '';
        // Starts the presentation panel as empty.
        $presentationPanel = '';
        // Checks whether the workflow state already holds a date.
        if (!empty($wf['presentation_date'])) {
            // Builds a schedule array from the workflow state.
            $schedule = [
                // Uses the stored presentation date.
                'date' => (string) ($wf['presentation_date'] ?? ''),
                // Uses the stored presentation time.
                'time' => (string) ($wf['presentation_time'] ?? ''),
                // Uses the stored presentation venue.
                'venue' => (string) ($wf['presentation_venue'] ?? ''),
                // Uses the stored presentation panel.
                'panel' => (string) ($wf['presentation_panel'] ?? ''),
                // Keeps the application id with the schedule data.
                'applicationId' => $app['id'] ?? null,
            // Closes the schedule array.
            ];
            // Copies the presentation date for the progress row.
            $presentationDate = (string) ($wf['presentation_date'] ?? '');
            // Copies the presentation time for the progress row.
            $presentationTime = (string) ($wf['presentation_time'] ?? '');
            // Copies the presentation venue for the progress row.
            $presentationVenue = (string) ($wf['presentation_venue'] ?? '');
            // Copies the presentation panel for the progress row.
            $presentationPanel = (string) ($wf['presentation_panel'] ?? '');
            // Marks the presentation done only when it is confirmed.
            $presentationStatus = ($wf['presentation_status'] ?? '') === 'confirmed' ? 'done' : 'pending';
            // Starts the note that the schedule row decides the milestone.
            /*
             * The schedule row is the record the coordinator sees on the
             * Presentation Schedules page, so when one exists for this
             * application it decides the milestone. Applications written
             * before the workflow state carried the confirmation then still
             * show the right state.
             */
            // Loops over the schedules saved for the student.
            foreach ($schedules as $s) {
                // Checks whether this schedule belongs to the application.
                if ((int) ($s['applicationId'] ?? 0) === (int) ($app['id'] ?? 0)) {
                    // Uses the schedule row's confirmed or completed status.
                    $presentationStatus = in_array((string) ($s['status'] ?? ''), ['confirmed', 'completed'], true) ? 'done' : 'pending';
                    // Stops looking once the matching schedule was found.
                    break;
                // Closes the application match check.
                }
            // Closes the schedule loop.
            }
        // Handles the case where the workflow state has no date.
        } else {
            // Loops over saved schedules to find one for this stage.
            foreach ($schedules as $s) {
                // Matches the schedule by application id or by stage label.
                if ((int) ($s['applicationId'] ?? 0) === (int) $app['id'] || stripos((string) $s['stage'], $stage['shortLabel'] ?? '') !== false) {
                    // Uses this schedule as the stage's schedule.
                    $schedule = $s;
                    // Copies the schedule date.
                    $presentationDate = (string) ($s['date'] ?? '');
                    // Copies the schedule time.
                    $presentationTime = (string) ($s['time'] ?? '');
                    // Copies the schedule venue.
                    $presentationVenue = (string) ($s['venue'] ?? '');
                    // Copies the schedule panel.
                    $presentationPanel = (string) ($s['panel'] ?? '');
                    // Marks the presentation done only when confirmed.
                    $presentationStatus = ($s['status'] ?? 'pending') === 'confirmed' ? 'done' : 'pending';
                    // Stops looking after the first matching schedule.
                    break;
                // Closes the application match check.
                }
            // Closes the schedule loop.
            }
        // Closes the workflow-date branch.
        }

        // Reads the application's result value.
        $resultVal = $app['result'] ?? '';
        // Adds the finished progress row to the list.
        $rows[] = [
            // Stores the stage key.
            'stageKey' => $stage['key'],
            // Stores the application's stage status.
            'stageStatus' => $app['status'],
            // Stores the paper state, date and review status.
            'paper' => ['status' => $paperStatus, 'submitted' => $paperUpload['date'] ?? '', 'review' => $paperWf],
            // Stores the adviser endorsement state, date and review.
            'adviserEndorsement' => ['status' => $adviserStatus, 'submitted' => $adviserUpload['date'] ?? '', 'review' => $advWf],
            // Stores the coordinator review state and any comment.
            'coordReview' => ['status' => $coordStatus, 'comment' => $coordStatus === 'flagged' ? ($app['coordinatorComment'] ?? '') : ''],
            // Stores the Graduate School endorsement state, date and review.
            'gradSchoolEndorsement' => ['status' => $gradStatus, 'submitted' => $gradSchoolUpload['date'] ?? '', 'review' => $gradWf],
            // Stores the payment state, date and review status.
            'payment' => ['status' => $paymentStatus, 'submitted' => !empty($app['paymentDate']) ? $app['paymentDate'] : ($paymentUpload['date'] ?? ''), 'review' => $paymentWf],
            // Marks presentation-ready as done when the flag is set.
            'readyForPresentation' => ['status' => !empty($app['readyForPresentation']) ? 'done' : 'pending'],
            // Starts the presentation details array.
            'presentation' => [
                // Stores the presentation state.
                'status' => $presentationStatus,
                // Stores the presentation date.
                'date' => $presentationDate,
                // Stores the presentation time.
                'time' => $presentationTime,
                // Stores the presentation venue.
                'venue' => $presentationVenue,
                // Stores the presentation panel.
                'panel' => $presentationPanel,
            // Closes the presentation details array.
            ],
            // Starts the result details array.
            'result' => [
                // Maps approved or requires_revision to a result state.
                'status' => $resultVal === 'approved' ? 'done' : ($resultVal === 'requires_revision' ? 'flagged' : 'pending'),
                // Maps the result value used for display.
                'value' => $resultVal === 'approved' ? 'approved' : ($resultVal === 'requires_revision' ? 'revision' : ''),
            // Closes the result details array.
            ],
            // Stores the coordinator's overall comment.
            'coordinatorComments' => $app['coordinatorComment'] ?? '',
        // Closes the progress row array.
        ];
    // Closes the stage progress loop.
    }

    // Returns all stage progress rows.
    return $rows;
// Closes getStudentProgress().
}

/**
 * Return an error when a student tries to submit a stage before its predecessor
 * has been approved or completed.
 *
 * A stage the student already finished cannot be applied for again, and a
 * stage after the one they are working on stays closed until the coordinator
 * approves the progression.
 */
// Validates that a student may apply for the requested stage.
function validateApplicationStageSequence(array $progress, array $stages, string $requestedStageKey): ?string {
    // Reads the ordered stage keys of the track.
    $stageKeys = array_keys($stages);
    // Finds the position of the requested stage key.
    $requestedIndex = array_search($requestedStageKey, $stageKeys, true);
    // Checks whether the requested stage is not in the track.
    if ($requestedIndex === false) {
        // Notes that a stage outside the track is never open.
        // A stage that is not part of the track is never open.
        // Returns an error for an unknown presentation stage.
        return 'Select a valid presentation stage.';
    // Closes the unknown stage check.
    }

    // Notes that a completed stage is closed for good.
    // A stage the student has already completed is closed for good.
    // Loops over the student's progress rows.
    foreach ($progress as $stage) {
        // Skips progress rows for other stages.
        if (($stage['stageKey'] ?? '') !== $requestedStageKey) {
            // Continues to the next progress row.
            continue;
        // Closes the stage key check.
        }
        // Checks whether this stage is already completed or approved.
        if (in_array(($stage['stageStatus'] ?? ''), ['completed', 'approved'], true)) {
            // Reads the stage label for the error message.
            $completedLabel = $stages[$requestedStageKey]['label'] ?? 'this stage';
            // Returns an error saying the stage is already completed.
            return 'You have already completed the ' . $completedLabel . '.';
        // Closes the completed stage check.
        }
    // Closes the progress loop.
    }

    // Checks whether the requested stage is the first one.
    if ($requestedIndex === 0) {
        // Allows the first stage with no error.
        return null;
    // Closes the first stage check.
    }

    // Reads the stage key that comes before the requested one.
    $previousStageKey = $stageKeys[$requestedIndex - 1];
    // Loops over progress rows to find the previous stage.
    foreach ($progress as $stage) {
        // Skips progress rows for other stages.
        if (($stage['stageKey'] ?? '') !== $previousStageKey) {
            // Continues to the next progress row.
            continue;
        // Closes the stage key check.
        }

        // Checks whether the previous stage is approved or completed.
        if (in_array(($stage['stageStatus'] ?? ''), ['approved', 'completed'], true)) {
            // Allows the request because the previous stage is done.
            return null;
        // Closes the previous stage check.
        }

        // Reads the previous stage label for the error message.
        $previousLabel = $stages[$previousStageKey]['label'] ?? 'previous stage';
        // Returns an error asking the student to finish the previous stage.
        return 'You cannot apply for this stage yet. Complete and receive approval for the ' . $previousLabel . ' first.';
    // Closes the progress loop.
    }

    // Reads the previous stage label for the fallback error.
    $previousLabel = $stages[$previousStageKey]['label'] ?? 'previous stage';
    // Returns an error because the previous stage was not found.
    return 'You cannot apply for this stage yet. Submit and complete the ' . $previousLabel . ' first.';
// Closes validateApplicationStageSequence().
}
