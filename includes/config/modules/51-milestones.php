<?php
/* CSITE configuration module: Milestone steps, milestone progress, payment checks, and coordinator student queries. */

// Returns sample milestone progress for the given track.
function demoProgressForTrack(string $track): array {
    // Checks whether the track is capstone.
    if ($track === 'capstone') {
        // Returns the capstone sample progress.
        return demoCapstoneProgress();
    // Closes the capstone check.
    }
    // Checks whether the track is seminar paper.
    if ($track === 'seminar') {
        // Returns the seminar paper sample progress.
        return demoSeminarProgress();
    // Closes the seminar check.
    }
    // Falls back to the thesis sample progress.
    return demoThesisProgress();
// Closes demoProgressForTrack().
}

// Returns sample thesis progress used for demos.
function demoThesisProgress(): array {
    // Starts the list of thesis stage samples.
    return [
        // Starts the Concept Paper sample stage.
        [
            // Marks the Concept Paper as completed.
            'stageKey' => 'concept', 'stageStatus' => 'completed',
            // Shows the paper as done and submitted in November 2024.
            'paper' => ['status' => 'done', 'submitted' => 'Nov 10, 2024'],
            // Shows the adviser endorsement as done.
            'adviserEndorsement' => ['status' => 'done', 'submitted' => 'Nov 08, 2024'],
            // Shows the coordinator review as done with no comment.
            'coordReview' => ['status' => 'done', 'comment' => ''],
            // Shows the Graduate School endorsement as done.
            'gradSchoolEndorsement' => ['status' => 'done'],
            // Shows the payment as done.
            'payment' => ['status' => 'done', 'submitted' => 'Nov 22, 2024'],
            // Shows the presentation-ready milestone as done.
            'readyForPresentation' => ['status' => 'done'],
            // Shows the Concept Paper presentation details.
            'presentation' => ['status' => 'done', 'date' => 'Dec 05, 2024', 'time' => '9:00 AM', 'venue' => 'CSITE Seminar Room', 'panel' => 'Dela Cruz, Reyes, Santos'],
            // Shows the result as approved.
            'result' => ['status' => 'done', 'value' => 'approved'],
            // Has no coordinator comment.
            'coordinatorComments' => '',
        // Closes the Concept Paper sample.
        ],
        // Starts the Thesis Proposal sample stage.
        [
            // Marks the proposal as under review.
            'stageKey' => 'proposal', 'stageStatus' => 'under_review',
            // Shows the proposal paper as submitted in March 2025.
            'paper' => ['status' => 'done', 'submitted' => 'Mar 15, 2025'],
            // Shows the adviser endorsement as done.
            'adviserEndorsement' => ['status' => 'done', 'submitted' => 'Mar 14, 2025'],
            // Flags the coordinator review with a literature review comment.
            'coordReview' => ['status' => 'flagged', 'comment' => 'Please complete your review of related literature section.'],
            // Shows the Graduate School endorsement as pending.
            'gradSchoolEndorsement' => ['status' => 'pending'],
            // Shows the payment as still pending.
            'payment' => ['status' => 'pending', 'submitted' => ''],
            // Shows the presentation-ready milestone as pending.
            'readyForPresentation' => ['status' => 'pending'],
            // Has no presentation details yet.
            'presentation' => ['status' => 'pending', 'date' => '', 'time' => '', 'venue' => '', 'panel' => ''],
            // Has no result yet.
            'result' => ['status' => 'pending', 'value' => ''],
            // Adds a coordinator comment about the literature review.
            'coordinatorComments' => 'Your proposal looks promising. Please address the incomplete literature review before we can proceed to Graduate School endorsement.',
        // Closes the Thesis Proposal sample.
        ],
        // Starts the Final Thesis sample stage.
        [
            // Marks the final stage as not started.
            'stageKey' => 'final', 'stageStatus' => 'not_started',
            // Shows the final paper as pending.
            'paper' => ['status' => 'pending', 'submitted' => ''],
            // Shows the adviser endorsement as pending.
            'adviserEndorsement' => ['status' => 'pending', 'submitted' => ''],
            // Shows the coordinator review as pending.
            'coordReview' => ['status' => 'pending', 'comment' => ''],
            // Shows the Graduate School endorsement as pending.
            'gradSchoolEndorsement' => ['status' => 'pending'],
            // Shows the payment as pending.
            'payment' => ['status' => 'pending', 'submitted' => ''],
            // Shows the presentation-ready milestone as pending.
            'readyForPresentation' => ['status' => 'pending'],
            // Has no presentation details yet.
            'presentation' => ['status' => 'pending', 'date' => '', 'time' => '', 'venue' => '', 'panel' => ''],
            // Has no result yet.
            'result' => ['status' => 'pending', 'value' => ''],
            // Has no coordinator comment.
            'coordinatorComments' => '',
        // Closes the Final Thesis sample.
        ],
    // Closes the thesis sample list.
    ];
// Closes demoThesisProgress().
}

// Returns sample capstone progress used for demos.
function demoCapstoneProgress(): array {
    // Starts the list of capstone stage samples.
    return [
        // Starts the Capstone Proposal sample stage.
        [
            // Marks the proposal as ready for presentation.
            'stageKey' => 'proposal', 'stageStatus' => 'ready_for_presentation',
            // Shows the proposal paper as submitted in February 2025.
            'paper' => ['status' => 'done', 'submitted' => 'Feb 20, 2025'],
            // Shows the adviser endorsement as done.
            'adviserEndorsement' => ['status' => 'done', 'submitted' => 'Feb 18, 2025'],
            // Shows the coordinator review as done with no comment.
            'coordReview' => ['status' => 'done', 'comment' => ''],
            // Shows the Graduate School endorsement as done.
            'gradSchoolEndorsement' => ['status' => 'done'],
            // Shows the payment as done in March 2025.
            'payment' => ['status' => 'done', 'submitted' => 'Mar 05, 2025'],
            // Shows the presentation-ready milestone as done.
            'readyForPresentation' => ['status' => 'done'],
            // Shows a pending presentation scheduled for April 2025.
            'presentation' => ['status' => 'pending', 'date' => 'Apr 20, 2025', 'time' => '10:00 AM', 'venue' => 'CSITE Seminar Room', 'panel' => 'TBD'],
            // Has no result yet.
            'result' => ['status' => 'pending', 'value' => ''],
            // Has no coordinator comment.
            'coordinatorComments' => '',
        // Closes the Capstone Proposal sample.
        ],
        // Starts the Final Capstone sample stage.
        [
            // Marks the final stage as not started.
            'stageKey' => 'final', 'stageStatus' => 'not_started',
            // Shows the final paper as pending.
            'paper' => ['status' => 'pending', 'submitted' => ''],
            // Shows the adviser endorsement as pending.
            'adviserEndorsement' => ['status' => 'pending', 'submitted' => ''],
            // Shows the coordinator review as pending.
            'coordReview' => ['status' => 'pending', 'comment' => ''],
            // Shows the Graduate School endorsement as pending.
            'gradSchoolEndorsement' => ['status' => 'pending'],
            // Shows the payment as pending.
            'payment' => ['status' => 'pending', 'submitted' => ''],
            // Shows the presentation-ready milestone as pending.
            'readyForPresentation' => ['status' => 'pending'],
            // Has no presentation details yet.
            'presentation' => ['status' => 'pending', 'date' => '', 'time' => '', 'venue' => '', 'panel' => ''],
            // Has no result yet.
            'result' => ['status' => 'pending', 'value' => ''],
            // Has no coordinator comment.
            'coordinatorComments' => '',
        // Closes the demo final-stage entry of the capstone progress list.
        ],
    // Closes the array of demo capstone stage progress entries.
    ];
// Ends demoCapstoneProgress, which returns sample capstone progress.
}

// Starts demoSeminarProgress, returning sample seminar-paper progress.
function demoSeminarProgress(): array {
    // Returns the list of demo stage progress entries.
    return [
        // Starts the entry for the proposal stage.
        [
            // Sets the proposal stage key and its submitted status.
            'stageKey' => 'proposal', 'stageStatus' => 'submitted',
            // Marks the proposal paper as done and records its submission date.
            'paper' => ['status' => 'done', 'submitted' => 'Mar 01, 2025'],
            // Marks the adviser endorsement as done with its submission date.
            'adviserEndorsement' => ['status' => 'done', 'submitted' => 'Feb 28, 2025'],
            // Leaves the coordinator review pending with an empty comment.
            'coordReview' => ['status' => 'pending', 'comment' => ''],
            // Leaves the Graduate School endorsement pending.
            'gradSchoolEndorsement' => ['status' => 'pending'],
            // Leaves the payment pending with no submission date.
            'payment' => ['status' => 'pending', 'submitted' => ''],
            // Leaves the ready-for-presentation flag pending.
            'readyForPresentation' => ['status' => 'pending'],
            // Leaves the presentation pending with empty date, time, venue, and panel.
            'presentation' => ['status' => 'pending', 'date' => '', 'time' => '', 'venue' => '', 'panel' => ''],
            // Leaves the presentation result pending with no value.
            'result' => ['status' => 'pending', 'value' => ''],
            // Stores an empty coordinator comment for this stage.
            'coordinatorComments' => '',
        // Closes the proposal stage entry.
        ],
        // Starts the entry for the final stage.
        [
            // Sets the final stage key with a not-started status.
            'stageKey' => 'final', 'stageStatus' => 'not_started',
            // Leaves the final paper pending with no submission date.
            'paper' => ['status' => 'pending', 'submitted' => ''],
            // Leaves the final adviser endorsement pending.
            'adviserEndorsement' => ['status' => 'pending', 'submitted' => ''],
            // Leaves the final coordinator review pending with an empty comment.
            'coordReview' => ['status' => 'pending', 'comment' => ''],
            // Leaves the final Graduate School endorsement pending.
            'gradSchoolEndorsement' => ['status' => 'pending'],
            // Leaves the final payment pending with no date.
            'payment' => ['status' => 'pending', 'submitted' => ''],
            // Leaves the final ready-for-presentation flag pending.
            'readyForPresentation' => ['status' => 'pending'],
            // Leaves the final presentation pending with empty details.
            'presentation' => ['status' => 'pending', 'date' => '', 'time' => '', 'venue' => '', 'panel' => ''],
            // Leaves the final result pending with no value.
            'result' => ['status' => 'pending', 'value' => ''],
            // Stores an empty coordinator comment for the final stage.
            'coordinatorComments' => '',
        // Closes the final stage entry.
        ],
    // Closes the list of demo stage entries.
    ];
// Ends demoSeminarProgress.
}

// Builds the icon markup for one milestone state.
function stepIconHtml(string $state): string {
    // Checks whether the milestone is done.
    if ($state === 'done') {
        // Returns a check icon for a completed milestone.
        return '<span class="step-icon step-icon-done"><i class="fas fa-check"></i></span>';
    // Closes the done branch.
    }
    // Checks whether the milestone was flagged for correction.
    if ($state === 'flagged') {
        // Returns an exclamation icon for a flagged milestone.
        return '<span class="step-icon step-icon-flagged"><i class="fas fa-exclamation"></i></span>';
    // Closes the flagged branch.
    }
    // Checks whether the milestone is submitted and waits for review.
    if ($state === 'submitted') {
        // Returns a clock icon for a submitted milestone.
        return '<span class="step-icon step-icon-submitted"><i class="fas fa-clock"></i></span>';
    // Closes the submitted branch.
    }
    // Checks whether the milestone is the active one.
    if ($state === 'active') {
        // Returns a dot icon for the active milestone.
        return '<span class="step-icon step-icon-active"><span class="step-icon-dot"></span></span>';
    // Closes the active branch.
    }
    // Returns the plain pending icon for every other state.
    return '<span class="step-icon step-icon-pending"></span>';
// Ends stepIconHtml.
}

/**
 * The milestone steps of one stage, with labels that follow the track and the
 * stage. A thesis student therefore sees "Thesis Proposal Prepared &
 * Submitted" and "Thesis Proposal Endorsement to Graduate School", while a
 * capstone student sees the capstone wording, and a seminar-paper student the
 * seminar wording.
 *
 * Each step: label, detail, state (done | flagged | pending), coord (whether
 * the coordinator is responsible) and whether the student can still upload it.
 */
// Builds the milestone steps of one stage with track-specific labels.
function milestoneStepsForStage(array $p, string $track, string $stageKey): array {
    // Starts with no matching stage definition.
    $stage = null;
    // Loops through the stages of the track workflow.
    foreach (getWorkflow($track)['stages'] as $candidate) {
        // Checks whether this stage matches the requested stage key.
        if (($candidate['key'] ?? '') === $stageKey) {
            // Remembers the matching stage definition.
            $stage = $candidate;
            // Stops looking once the stage is found.
            break;
        // Closes the stage match check.
        }
    // Closes the loop over the stage definitions.
    }
    // Reads the paper label from the stage, defaulting to Paper.
    $paperLabel = $stage['documents'][0]['label'] ?? 'Paper';
    // Reads the adviser endorsement form label with a fallback.
    $adviserLabel = $stage['adviserEndorsementForm']['label'] ?? 'Adviser Endorsement Form';
    // Reads the Graduate School endorsement label with a fallback.
    $gradLabel = $stage['gradSchoolEndorsementForm']['label'] ?? 'Endorsement to Graduate School';

    // Starts a block comment explaining how milestone wording is chosen.
    /*
     * The wording of a milestone follows what the coordinator decided about
     * the document, so the student always knows where they stand.
     */
    // Defines a helper that turns a document entry into its detail text.
    $documentDetail = static function (array $entry, string $uploadHint, string $missingHint): string {
        // Reads the stored status, defaulting to pending.
        $status = (string) ($entry['status'] ?? 'pending');
        // Reads the coordinator review value in lowercase.
        $review = strtolower((string) ($entry['review'] ?? ''));
        // Checks whether the document is verified.
        if ($status === 'done') {
            // Returns Verified plus the submission date when it is known.
            return 'Verified' . (!empty($entry['submitted']) ? ': ' . $entry['submitted'] : '');
        // Closes the verified branch.
        }
        // Checks whether the document is submitted but not yet reviewed.
        if ($status === 'submitted') {
            // Starts the Submitted text with the submission date.
            return 'Submitted' . (!empty($entry['submitted']) ? ': ' . $entry['submitted'] : '')
                // Adds the waiting-for-the-coordinator note.
                . ' — waiting for the coordinator to review it';
        // Closes the submitted branch.
        }
        // Checks whether the coordinator marked the document incomplete.
        if ($review === 'incomplete') {
            // Explains that the coordinator needs something fixed.
            return 'Incomplete — the coordinator needs something fixed in this document';
        // Closes the incomplete branch.
        }
        // Checks whether the coordinator asked for a revision.
        if ($review === 'revision') {
            // Asks the student to upload a new version.
            return 'Revision requested — upload a new version of this document';
        // Closes the revision branch.
        }
        // Checks whether the milestone is flagged for attention.
        if ($status === 'flagged') {
            // Says the coordinator asked for a correction.
            return 'Needs attention — the coordinator asked for a correction';
        // Closes the flagged branch.
        }
        // Falls back to the upload or missing hint for this document.
        return !empty($entry['submitted']) ? $uploadHint : $missingHint;
    // Ends the detail helper closure.
    };

    // Returns the ordered list of milestone steps for the stage.
    return [
        // Starts the paper milestone entry.
        [
            // Sets the key that identifies the paper milestone.
            'key'    => 'paper',
            // Builds the paper label from the stage wording.
            'label'  => $paperLabel . ' Prepared & Submitted',
            // Uses the paper state, defaulting to pending.
            'state'  => $p['paper'] ?? ['status' => 'pending'],
            // Marks the paper as the student's own task.
            'coord'  => false,
            // Sets the document type the student uploads for this step.
            'docType' => $paperLabel,
            // Builds the detail text from the paper state.
            'detail' => $documentDetail(
                // Passes the stored paper entry to the detail helper.
                $p['paper'] ?? [],
                // Shows the upload date when the paper was already sent.
                !empty($p['paper']['submitted']) ? 'Uploaded: ' . $p['paper']['submitted'] : '',
                // Tells the student to upload the paper when it is missing.
                'Upload your ' . strtolower($paperLabel) . ' to continue'
            // Closes the paper detail call.
            ),
        // Closes the paper milestone entry.
        ],
        // Starts the adviser endorsement milestone entry.
        [
            // Sets the key for the adviser endorsement milestone.
            'key'    => 'adviser_endorsement',
            // Labels the step as the adviser endorsement submission.
            'label'  => 'Adviser Endorsement Submitted',
            // Uses the endorsement state, defaulting to pending.
            'state'  => $p['adviserEndorsement'] ?? ['status' => 'pending'],
            // Marks the endorsement as the student's own task.
            'coord'  => false,
            // Sets the adviser form document type.
            'docType' => $adviserLabel,
            // Builds the detail text for the endorsement.
            'detail' => $documentDetail(
                // Passes the stored endorsement entry to the helper.
                $p['adviserEndorsement'] ?? [],
                // Shows the upload date when the endorsement was sent.
                !empty($p['adviserEndorsement']['submitted']) ? 'Uploaded: ' . $p['adviserEndorsement']['submitted'] : '',
                // Tells the student to get the adviser signature.
                'Obtain your adviser’s signature on the ' . $adviserLabel
            // Closes the endorsement detail call.
            ),
        // Closes the adviser endorsement entry.
        ],
        // Starts the coordinator document review milestone entry.
        [
            // Sets the key for the coordinator review milestone.
            'key'    => 'coord_review',
            // Labels the step as the coordinator document review.
            'label'  => 'Coordinator Document Review',
            // Uses the review state, defaulting to pending.
            'state'  => $p['coordReview'] ?? ['status' => 'pending'],
            // Marks the review as the coordinator's task.
            'coord'  => true,
            // Gives the review no document type to upload.
            'docType' => '',
            // Uses the review comment, or a status message when it is empty.
            'detail' => (($p['coordReview']['comment'] ?? '') ?: (($p['coordReview']['status'] ?? '') === 'done'
                // Says the documents were verified when the review is done.
                ? 'Documents verified'
                // Otherwise says the review still waits for the coordinator.
                : 'Waiting for coordinator')),
        // Closes the review detail call.
        ],
        // Closes the coordinator review entry.
        [
            // Starts the Graduate School endorsement milestone entry.
            'key'    => 'grad_school_endorsement',
            // Sets the key for the Graduate School endorsement milestone.
            'label'  => $gradLabel,
            // Uses the endorsement label from the stage definition.
            'state'  => $p['gradSchoolEndorsement'] ?? ['status' => 'pending'],
            // Uses the endorsement state, defaulting to pending.
            'coord'  => false,
            // Marks the endorsement as the student's own task.
            'docType' => $gradLabel,
            // Sets the Graduate School form document type.
            'detail' => $documentDetail(
                // Builds the detail text for the endorsement form.
                $p['gradSchoolEndorsement'] ?? [],
                // Passes the stored endorsement entry to the helper.
                !empty($p['gradSchoolEndorsement']['submitted']) ? 'Uploaded: ' . $p['gradSchoolEndorsement']['submitted'] : '',
                // Shows the upload date when the form was sent.
                'Complete the Graduate School endorsement form and upload it for review'
            // Tells the student to complete and upload the form.
            ),
        // Closes the endorsement detail call.
        ],
        // Closes the Graduate School endorsement entry.
        [
            // Starts the payment milestone entry.
            'key'    => 'payment',
            // Sets the key for the payment milestone.
            'label'  => 'Official Receipt / Payment Uploaded',
            // Labels the step as the receipt or payment upload.
            'state'  => $p['payment'] ?? ['status' => 'pending'],
            // Uses the payment state, defaulting to pending.
            'coord'  => false,
            // Marks the payment as the student's own task.
            'docType' => 'Official Receipt / Payment Proof',
            // Sets the receipt document type.
            'detail' => $documentDetail(
                // Builds the detail text for the payment.
                $p['payment'] ?? [],
                // Shows the received date when the receipt was sent.
                !empty($p['payment']['submitted']) ? 'Received: ' . $p['payment']['submitted'] : '',
                // Tells the student to submit the official receipt.
                'Submit the official receipt after payment at the Graduate School'
            // Closes the payment detail call.
            ),
        // Closes the payment milestone entry.
        ],
        // Starts the ready-for-presentation milestone entry.
        [
            // Sets the key for the ready-for-presentation milestone.
            'key'    => 'ready_for_presentation',
            // Labels the step as the ready-for-presentation status.
            'label'  => 'Status Set to Ready for Presentation',
            // Uses the readiness state, defaulting to pending.
            'state'  => $p['readyForPresentation'] ?? ['status' => 'pending'],
            // Marks readiness as the coordinator's task.
            'coord'  => true,
            // Checks whether the coordinator already marked the student ready.
            'detail' => ($p['readyForPresentation']['status'] ?? '') === 'done'
                // Says the coordinator confirmed readiness.
                ? 'Coordinator confirmed readiness'
                // Otherwise says readiness comes after receipt verification.
                : 'Coordinator marks ready after the receipt is verified',
        // Closes the readiness detail expression.
        ],
        // Closes the ready-for-presentation entry.
        [
            // Starts the presentation milestone entry.
            'key'    => 'presentation',
            // Sets the key for the presentation milestone.
            'label'  => 'Presentation Scheduled',
            // Labels the step as the presentation schedule.
            'state'  => $p['presentation'] ?? ['status' => 'pending'],
            // Uses the presentation state, defaulting to pending.
            'coord'  => true,
            // Marks scheduling as the coordinator's task.
            'detail' => !empty($p['presentation']['date'])
                // Shows a schedule once a presentation date is set.
                ? trim($p['presentation']['date'] . ' – ' . ($p['presentation']['time'] ?? '')
                    // Starts the schedule text with the date and time.
                    . (!empty($p['presentation']['venue']) ? ', ' . $p['presentation']['venue'] : '')
                    // Appends the venue when the coordinator set one.
                    . (!empty($p['presentation']['panel']) ? ' | Panel: ' . $p['presentation']['panel'] : ''))
                // Appends the panel names when they are known.
                : 'Coordinator schedules the date, time, venue, and panel',
        // Otherwise explains that the coordinator schedules the presentation.
        ],
        // Closes the presentation detail expression.
        [
            // Closes the presentation milestone entry.
            'key'    => 'result',
            // Starts the presentation result milestone entry.
            'label'  => 'Presentation Conducted & Result Recorded',
            // Sets the key for the result milestone.
            'state'  => $p['result'] ?? ['status' => 'pending'],
            // Labels the step as the conducted presentation and recorded result.
            'coord'  => true,
            // Uses the result state, defaulting to pending.
            'detail' => (($p['result']['value'] ?? '') === 'approved'
                // Marks result recording as the coordinator's task.
                ? 'Approved — proceed to the next stage'
                // Checks whether the result is approved.
                : (($p['result']['value'] ?? '') === 'revision'
                    // Tells the student to proceed to the next stage.
                    ? 'Revision required — resubmit the updated paper'
                    // Checks whether the result asks for a revision.
                    : 'Awaiting presentation')),
        // Tells the student to resubmit the updated paper.
        ],
    // Otherwise says the presentation is still awaited.
    ];
// Closes the result entry and the list of milestone steps.
}

/**
 * How many milestones of a stage are complete, and the index of the first
 * milestone that is still outstanding (used to decide which steps may still
 * be uploaded by the student).
 */
// Counts finished milestones and finds the first unfinished one.
function milestoneProgress(array $steps): array {
    // Starts the count of finished milestones at zero.
    $done = 0;
    // Starts the first pending index at zero.
    $firstPending = 0;
    // Loops through the milestones in order.
    foreach ($steps as $i => $step) {
        // Checks whether this milestone is done.
        if (($step['state']['status'] ?? 'pending') === 'done') {
            // Adds one to the finished count.
            $done++;
            // Moves the first pending index past this finished milestone.
            $firstPending = $i + 1;
        // Handles the first milestone that is not done.
        } else {
            // Remembers this index as the first pending milestone.
            $firstPending = $i;
            // Stops at the first unfinished milestone.
            break;
        // Closes the done check.
        }
    // Closes the loop over milestones.
    }
    // Returns the done count, the total, and the first pending index.
    return ['done' => $done, 'total' => count($steps), 'firstPending' => $firstPending];
// Ends milestoneProgress.
}

/**
 * The milestone progress of a student in the stage they are currently working
 * on: the same "N of N milestones complete" the Milestone Tracker inside
 * Applications -> Edit shows.
 *
 * The Coordinator's Applications list derives its progress bar from this, so
 * the list and the tracker can never disagree, and every track (thesis,
 * capstone, seminar paper) is measured with its own milestone sequence. The
 * numbers follow the workflow live: approving a stage or verifying a
 * document changes them on the next list load, because they are computed
 * from the stored state and never kept as a copy.
 *
 * @return array{done:int,total:int,stageKey:string,stageLabel:string}
 */
// Computes the milestone progress of the student's current stage.
function studentMilestoneProgress(string $email, string $track): array {
    // Reads the stage list of the student's track.
    $stages = getWorkflow($track)['stages'];
    // Finds the index of the stage the student is working on.
    $currentIdx = workflowCurrentIndex(getStudentProgress($email, $track), $stages);
    // Reads the key of the current stage.
    $stageKey = (string) ($stages[$currentIdx]['key'] ?? '');
    // Reads the label of the current stage.
    $stageLabel = (string) ($stages[$currentIdx]['label'] ?? '');
    // Counts the milestones of that stage.
    $stats = milestoneProgress(stageMilestoneSteps($email, $track, $stageKey));
    // Returns the progress numbers and the stage details.
    return [
        // Casts the finished milestone count to an integer.
        'done'       => (int) ($stats['done'] ?? 0),
        // Casts the total milestone count to an integer.
        'total'      => (int) ($stats['total'] ?? 0),
        // Returns the current stage key.
        'stageKey'   => $stageKey,
        // Returns the current stage label.
        'stageLabel' => $stageLabel,
    // Closes the returned progress array.
    ];
// Ends studentMilestoneProgress.
}

/**
 * The milestone steps of one stage as the student sees them.
 *
 * The coordinator's Applications -> Edit page calls this too, so both sides
 * always show the same steps, in the same order, with the same states: the
 * tracker is computed from the stored applications and documents, never from a
 * second copy of the rules.
 */
// Builds the milestone steps of one stage for a student.
function stageMilestoneSteps(string $email, string $track, string $stageKey): array {
    // Loops through the student's stored stage progress rows.
    foreach (getStudentProgress($email, $track) as $row) {
        // Checks whether this row belongs to the requested stage.
        if (($row['stageKey'] ?? '') === $stageKey) {
            // Returns the milestone steps built from that row.
            return milestoneStepsForStage($row, $track, $stageKey);
        // Closes the stage match check.
        }
    // Closes the progress loop.
    }

    // Notes the fallback used when the stage has no application yet.
    // No application for this stage yet: show the empty workflow of the stage.
    // Loops through the stage definitions of the track.
    foreach (getWorkflow($track)['stages'] as $stage) {
        // Checks whether this definition matches the stage key.
        if (($stage['key'] ?? '') === $stageKey) {
            // Returns the steps of an empty stage workflow.
            return milestoneStepsForStage(blankStageProgress($stage), $track, $stageKey);
        // Closes the stage match check.
        }
    // Closes the stage definition loop.
    }
    // Returns no steps when the stage is unknown.
    return [];
// Ends stageMilestoneSteps.
}

/**
 * The best upload of a given kind (paper | adviser_endorsement | receipt |
 * grad_school_endorsement): a verified one wins, otherwise the most recent
 * upload of that kind.
 */
/**
 * Whether the official payment of an application counts as recorded.
 *
 * The receipt file the student sent is what the coordinator reviews, so it
 * decides: a file that was sent back has to be sent again. Without a file
 * the payment the coordinator recorded counts.
 */
// Checks whether an application's official payment counts as recorded.
function paymentIsRecorded(array $app, array $uploads = []): bool {
    // Finds the newest receipt upload of the application.
    $receipt = latestUploadOfKind($uploads, 'receipt');
    // Checks whether a receipt file was uploaded.
    if ($receipt) {
        // Uses the receipt status, verified or approved, as the answer.
        return in_array((string) ($receipt['status'] ?? ''), ['verified', 'approved'], true);
    // Closes the receipt upload check.
    }
    // Falls back to the payment details the coordinator recorded.
    return !empty($app['paymentRecorded']) || trim((string) ($app['receiptNumber'] ?? '')) !== '';
// Ends paymentIsRecorded.
}

/**
 * The document of a kind that counts.
 *
 * It is the newest file of that kind, the same one the coordinator sees in
 * the list of documents and the same one that decides the milestone.
 */
// Returns the document of a kind that decides the milestone.
function bestUploadOfKind(array $uploads, string $kind): ?array {
    // Uses the newest upload of that kind.
    return latestUploadOfKind($uploads, $kind);
// Ends bestUploadOfKind.
}

/**
 * The automatic submission indicator of a student in the coordinator's
 * Applications menu.
 *
 * It is deliberately NOT an application status: a student who has not applied
 * yet has no application at all, so there is nothing for the coordinator to
 * edit. The badge only says whether the student has submitted something.
 */
// Builds the submission badge for the coordinator's student list.
function submissionBadge(bool $hasApplication): string {
    // Checks whether the student already has an application.
    if ($hasApplication) {
        // Returns a Submitted badge when they do.
        return '<span class="status-badge status-submitted">Submitted</span>';
    // Closes the has-application check.
    }
    // Returns a Not Yet Submitted badge otherwise.
    return '<span class="status-badge status-not-submitted">Not Yet Submitted</span>';
// Ends submissionBadge.
}
