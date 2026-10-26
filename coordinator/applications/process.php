<?php /* Starts the PHP script for the coordinator Process Application page. */ ?>
<?php
// Loads the shared config file with constants, helpers and session setup.
require_once __DIR__ . '/../../includes/config.php';
// Loads the database helper so MySQL queries can run through DB.
require_once __DIR__ . '/../../includes/db.php';

// Sets the page title used in the browser tab and page header.
$pageTitle   = 'Process Application';
// Tells the header and sidebar that this page belongs to the coordinator role.
$role        = 'coordinator';
// Marks applications as the active sidebar menu item.
$currentPage = 'applications';
// Uses the mock coordinator name so the header greets the logged-in coordinator.
$userName    = $mockCoordinator['name'];

// Reads the application id from the URL query string and casts it to an integer.
$appId = (int) ($_GET['id'] ?? 0);
// Loads the application row from the database when a valid id was given.
$app = $appId > 0 ? applicationById($appId) : null;
// Remembers whether the application came from MySQL, so the right save path runs.
$isDatabaseApplication = $app !== null;

// If no database application was found, try the demo store instead.
if (!$app) {
    // Looks up a demo application by id, or falls back to the first stored application.
    $app = findApplication($appId) ?? (storeGet('applications')[0] ?? null);
// Closes the fallback lookup that runs when no database application exists.
}
// Starts the success message empty; it is filled after a successful save.
$processSuccess = '';
// Starts the error message empty; it is filled when a save is rejected.
$processError = '';

/* ------------------------------------------------------------------
 * The track of the student and the stages of that track
 * ------------------------------------------------------------------ */
// Picks the student track from the application, or works it out from the program.
$track      = $app ? ($app['track'] ?? getTrackForProgram($app['program'])) : 'thesis';
// The stages of the student's track: $stageList keeps the order of the
// workflow, $stages is the same list keyed by stage key.
// Gets the ordered list of workflow stages for the student track.
$stageList = getWorkflow($track)['stages'];
// Prepares an empty list that will hold the stages keyed by stage key.
$stages = [];
// Loops over each stage definition of the track.
foreach ($stageList as $stageDefinition) {
    // Stores each stage under its key so it can be looked up by name.
    $stages[$stageDefinition['key']] = $stageDefinition;
// Closes the loop that builds the keyed stage list.
}
// Collects the stage keys in workflow order for numbering and navigation.
$stageKeys  = array_keys($stages);
// Gets the readable track name (Thesis, Capstone or Seminar Paper) for display.
$trackLabel = getTrackLabel($track);
// Reads the student email from the application; it links to the profile and uploads.
$email      = (string) ($app['studentEmail'] ?? '');

// The student behind the application, read once from the users and students
// records so every stage panel shows the same full name, program name and
// assigned adviser.
// Loads the student profile (name, program, adviser) once for every stage panel.
$studentProfile   = $email !== '' ? studentProfileForEmail($email) : null;
// Loads per-stage presentation details such as the adviser for this student.
$presentationTeam = $email !== '' ? studentPresentationDetails($email) : [];

/* ------------------------------------------------------------------
 * POST: Save Process
 *
 * Everything the coordinator changed on the selected stage is saved here:
 * the application status, the comment, the milestones, the document review
 * statuses, the workflow records, the presentation result and the stage
 * approvals.
 * ------------------------------------------------------------------ */
// Only runs the save logic when the form was submitted and an application exists.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app) {
    // Reads which stage tab was submitted, defaulting to the application stageKey.
    $selectedStage = trim((string) ($_POST['selected_stage'] ?? ($app['stageKey'] ?? '')));
    // Checks that the submitted stage is a real stage of this track.
    if (!isset($stages[$selectedStage])) {
        // Falls back to the application stage, or the first stage, when the posted one is invalid.
        $selectedStage = (string) ($app['stageKey'] ?? ($stageKeys[0] ?? ''));
    // Closes the check that validates the selected stage key.
    }

    // Comment: the selected stage decides which application row is saved.
    // The stage the coordinator was looking at decides which application row
    // Comment: everything else on the page follows that stage.
    // is saved. Everything else on the page follows that stage.
    // Reads the hidden map of stage key to application id sent by the form.
    $postedAppIds = $_POST['stage_app_id'] ?? [];
    // Gets the application id that belongs to the stage being saved.
    $targetAppId  = (int) ($postedAppIds[$selectedStage] ?? 0);
    // If the posted id points to a different application, load that row before saving.
    if ($targetAppId > 0 && $targetAppId !== (int) ($app['id'] ?? 0)) {
        // Loads the application row for the stage the coordinator is editing.
        $target = applicationById($targetAppId);
        // Only switches rows when that application really exists.
        if ($target) {
            // Swaps in the application for the selected stage so edits land on the right row.
            $app = $target;
            // Marks that this application came from the database, so it saves with SQL.
            $isDatabaseApplication = true;
        // Closes the check that switches to the selected stage application.
        }
    // Closes the block that handles a different posted application id.
    }

    // Comment: these are the fields the coordinator edits for the selected stage.
    // Everything the coordinator edits for the selected stage. The field names
    // Comment: field names are scoped per stage so values never mix.
    // are scoped per stage, so a stage never mixes its values up with another
    // Comment: each stage keeps its own values when the page is saved.
    // one when the page is saved.
    // Reads all fields the coordinator submitted for the selected stage.
    $stagePosted = $_POST['stage_data'][$selectedStage] ?? [];
    // Guards against a malformed submission where the stage data is not an array.
    if (!is_array($stagePosted)) {
        // Replaces bad stage data with an empty array so later reads stay safe.
        $stagePosted = [];
    // Closes the guard for malformed stage data.
    }

    // Reads the application status the coordinator chose.
    $status = (string) ($stagePosted['status'] ?? '');
    // Reads the stage key being saved, defaulting to the application stage or proposal.
    $stageKey = (string) ($stagePosted['stage'] ?? ($app['stageKey'] ?? 'proposal'));
    // Reads and trims the coordinator recommendation and comment text.
    $comment = trim((string) ($stagePosted['comment'] ?? ''));
    // Comment: the next two fields approve or revoke the next stage.
    // Approving or revoking the progression to the next stage.
    // Reads the hidden field that says the coordinator clicked Approve.
    $approveStage   = trim((string) ($_POST['approve_stage'] ?? ''));
    // Reads the hidden field that says the coordinator clicked Revoke approval.
    $disapproveStage = trim((string) ($_POST['disapprove_stage'] ?? ''));
    // Copies the list of statuses a coordinator is allowed to set.
    $allowed = COORDINATOR_STATUSES;
    // Comment: an old record keeps its own status until a current status is picked.
    // A record written before the status list changed keeps its own status
    // Comment: this prevents an old status from being rejected on save.
    // until the coordinator picks one of the current ones.
    // Keeps the application legacy status valid when it is unchanged and not listed.
    if (!empty($app['status']) && $app['status'] === $status && !in_array($status, $allowed, true)) {
        // Adds the legacy status to the allowed list so the save is not rejected.
        $allowed[] = $app['status'];
    // Closes the legacy-status exception.
    }
    // Rejects the save when the chosen status is not in the allowed list.
    if (!in_array($status, $allowed, true)) {
        // Sets the error the coordinator sees for an invalid status.
        $processError = 'Select a valid application status.';
    // Starts the main save block that runs for a valid status.
    } else {
        // Reloads the track from the saved application so the save uses current data.
        $track = $app['track'] ?? getTrackForProgram($app['program']);
        // Comment: stage labels for the track are fetched next.
        // The label of every stage of the track (the stage definitions
        // Comment: stage definitions stay in the stages list built further down.
        // themselves stay in $stages, which is built further down).
        // Gets the display label of every stage so the saved stage name matches the track.
        $stageLabels = getStagesForTrack($track);
        // Loads the stored document workflow states that will be updated below.
        $workflowState = $app['workflowState'] ?? [];

        // Opens a comment block: the status must agree with the student milestones.
        /*
         * The status has to agree with what the student has actually done, so
         * a record cannot claim to be ready, scheduled or finished while the
         * milestone workflow says otherwise. The milestones come from the same
         * helper the student's tracker uses.
         */
        // Works out the current stage key, or derives it from the stage label.
        $currentStageKey = (string) ($app['stageKey'] ?? stageKeyFromLabel((string) ($app['stage'] ?? '')));
        // Builds the milestone list for this student and stage to validate the status.
        $statusMilestones = stageMilestoneSteps((string) ($app['studentEmail'] ?? ''), $track, $currentStageKey);

        // Opens a comment block: this save document actions are merged into milestones.
        /*
         * The milestones above come from the database, so they still show the
         * state the documents had before this save. The actions the coordinator
         * is picking right now are put on top of them, so a document that is
         * verified in this very save counts as verified right away. Without
         * this a coordinator could not verify a document again after it had
         * been sent back to the student.
         */
        // Starts an empty map of upload id to the review status chosen in this save.
        $postedDocumentActions = [];
        // Loops over every document review status posted with the form.
        foreach (($_POST['upload_status'] ?? []) as $uploadId => $uploadStatus) {
            // Ignores any posted status that is not a valid review value.
            if (!in_array($uploadStatus, ['submitted', 'verified', 'incomplete', 'revision'], true)) {
                // Skips this upload because its posted status is not recognised.
                continue;
            // Closes the valid-status check for a posted upload.
            }
            // Loads the upload row to confirm the document really exists.
            $actionUpload = findUpload((string) $uploadId);
            // Skips upload ids that do not match any stored document.
            if (!$actionUpload) {
                // Skips this upload because no matching document was found.
                continue;
            // Closes the missing-upload check.
            }
            // Records the chosen status for this upload so milestones can use it.
            $postedDocumentActions[(string) $uploadId] = $uploadStatus;
        // Closes the loop over posted document statuses.
        }
        // Applies this save document decisions on top of the stored milestones.
        $statusMilestones = applyDocumentActionsToMilestones($statusMilestones, $postedDocumentActions);

        // Comment: reads the presentation result the coordinator picked.
        // The presentation result the coordinator picked. A stage without an
        // Comment: a stage without an application has no result field to post.
        // application has no field to pick from, so the result that is already
        // Comment: the already saved result is kept instead of being cleared.
        // saved is kept instead of being cleared by the save.
        // Checks whether the form included a presentation result field for this stage.
        $resultWasPosted = array_key_exists('result', $stagePosted);
        // Reads and trims the posted result, or empty text when none was sent.
        $postedResult    = $resultWasPosted ? trim((string) $stagePosted['result']) : '';
        // Uses the posted result, or keeps the saved one when the field was absent.
        $effectiveResult = $resultWasPosted ? $postedResult : (string) ($app['result'] ?? '');

        // Comment: requirements only block a real status change, not document work.
        // The coordinator is only moving the student to another status when
        // Comment: changing a document is a different kind of work.
        // the status itself is different. Changing a document is a different
        // Comment: document edits are not blocked by an unrelated status.
        // kind of work, and it may not be blocked by the requirements of a
        // Comment: the requirements only apply to the status being moved to.
        // status the student is not being moved to.
        // True when the coordinator is actually changing the application status.
        $statusChanged = ($status !== (string) ($app['status'] ?? ''));

        // Asks the helper whether the new status is blocked by missing requirements.
        $blockedReason = applicationStatusBlockedReason($status, $statusMilestones, $effectiveResult, $statusChanged);
        // If the move is not allowed, show the reason instead of saving.
        if ($blockedReason !== null) {
            // Shows why the status cannot be changed yet.
            $processError = $blockedReason;
        // Starts the block that saves when the status change is allowed.
        } else {
        // Loops over the posted document review statuses to store them.
        foreach (($_POST['upload_status'] ?? []) as $uploadId => $uploadStatus) {
            // Skips statuses that are not one of the four valid review values.
            if (!in_array($uploadStatus, ['submitted', 'verified', 'incomplete', 'revision'], true)) continue;
            // Loads the upload record being reviewed.
            $upload = findUpload((string) $uploadId);
            // Skips upload ids that do not exist in storage.
            if (!$upload) continue;


            // Reads the application id the upload is attached to.
            $linkedAppId = (int) ($upload['applicationId'] ?? 0);
            // Checks the upload belongs to the application being saved.
            if ($linkedAppId !== (int) $app['id']) {
                // Allows uploads with no application id only when the student email matches.
                if ($linkedAppId !== 0 || empty($app['studentEmail'])
                    // Compares the upload student email to the application email, ignoring case.
                    || strcasecmp((string) ($upload['studentEmail'] ?? ''), (string) $app['studentEmail']) !== 0) {
                    // Skips this upload because it belongs to someone else.
                    continue;
                // Closes the ownership check for the upload.
                }
            // Closes the block that checks the upload application.
            }
            // Saves the new review status for the document.
            updateUploadStatus((string) $uploadId, $uploadStatus);
            // Maps the document type to a workflow key such as receipt or paper.
            $key = classifyUploadDocType((string) $upload['docType']);
            // Stores the document status in the application workflow state.
            $workflowState[$key] = $uploadStatus;
        // Closes the loop that saves posted document statuses.
        }

        // Opens a comment block: payment and readiness follow their records.
        /*
         * Payment and readiness follow their submitted records; the Graduate
         * School endorsement is a student-uploaded document reviewed above.
         */
        // Prepares a map that will hold the reviewed status of each document kind.
        $docStatusByKind = [];
        // Reads the raw posted upload statuses for quick lookup.
        $postedStatuses = $_POST['upload_status'] ?? [];
        // Prepares a map of upload id to its currently stored status.
        $storedStatuses = [];
        // Loops over the application uploads to remember their stored statuses.
        foreach (uploadsForApplication((int) $app['id']) as $doc) {
            // Stores each upload current status, defaulting to submitted.
            $storedStatuses[(string) $doc['id']] = (string) ($doc['status'] ?? 'submitted');
        // Closes the loop that collects stored upload statuses.
        }
        // Opens a comment block: the newest file of every kind counts.
        /*
         * The status of the newest file of every kind. A student may send the
         * same kind of document more than once, and the file that was sent
         * last is the one that is reviewed, so it is the one that counts.
         */
        // Loops over the uploads again to find the newest file of each kind.
        foreach (uploadsForApplication((int) $app['id']) as $doc) {
            // Classifies the document type into a workflow kind.
            $kind = classifyUploadDocType((string) ($doc['docType'] ?? ''));
            // Reads the upload id as text.
            $id   = (string) $doc['id'];
            // Reads the upload date used to decide which file is newest.
            $date = (string) ($doc['date'] ?? '');
            // Keeps the newest upload for this kind, replacing older ones.
            if (!isset($docStatusByKind[$kind]) || $date >= (string) $docStatusByKind[$kind]['date']) {
                // Remembers the date and id of the newest file of this kind.
                $docStatusByKind[$kind] = ['date' => $date, 'id' => $id];
            // Closes the check that keeps the newest upload per kind.
            }
        // Closes the loop that finds the newest upload of each kind.
        }
        // Loops over each document kind to work out its effective status.
        foreach ($docStatusByKind as $kind => $row) {
            // Uses the posted status when present, otherwise the stored status.
            $docStatusByKind[$kind] = isset($postedStatuses[$row['id']])
                // Takes the status the coordinator just chose.
                ? (string) $postedStatuses[$row['id']]
                // Falls back to the stored status, or submitted when unknown.
                : ($storedStatuses[$row['id']] ?? 'submitted');
        // Closes the loop that resolves each document kind status.
        }
        // True when the newest receipt document has been verified.
        $receiptVerified = ($docStatusByKind['receipt'] ?? '') === 'verified';

        // Finds the newest Graduate School endorsement upload.
        $gradSchoolUpload = latestUploadOfKind(uploadsForApplication((int) $app['id']), 'grad_school_endorsement');
        // Starts the endorsement flag from whether such an upload exists.
        $gradEndorsed = $gradSchoolUpload
            // Counts the endorsement as given only if it is verified or approved.
            && in_array(($gradSchoolUpload['status'] ?? ''), ['verified', 'approved'], true);
        // Comment: receipt details come from the student.
        // The receipt details come from the student, so the payment counts as
        // Comment: payment counts as recorded once details exist.
        // recorded once the student sent the details or the coordinator
        // Comment: the coordinator only verifies, never edits receipt details.
        // verified the receipt.
        // Payment counts as recorded if the receipt is verified or details were sent.
        $paymentRecorded = $receiptVerified || trim((string) ($app['receiptNumber'] ?? '')) !== '';
        // Uses the posted readiness checkbox when provided, otherwise the saved value.
        $readyForPresentation = array_key_exists('readyForPresentation', $stagePosted)
            // Reads the checkbox as true only when it posted the value 1.
            ? ($stagePosted['readyForPresentation'] === '1')
            // Keeps the saved readiness flag when the field was not posted.
            : !empty($app['readyForPresentation']);

        // Builds the array of application fields to save.
        $updatedFields = [
            // Saves the chosen application status.
            'status' => $status,
            // Saves the stage key this application belongs to.
            'stageKey' => $stageKey,
            // Saves the stage label looked up from the track.
            'stage' => $stageLabels[$stageKey] ?? $app['stage'],
            // Saves the coordinator comment for the student.
            'coordinatorComment' => $comment,
            // Saves the updated document workflow states.
            'workflowState' => $workflowState,
            // Saves whether the Graduate School endorsement is verified.
            'gradSchoolEndorsed' => $gradEndorsed,
            // Saves whether the payment details are on record.
            'paymentRecorded' => $paymentRecorded,
            // Saves whether the student is ready for presentation.
            'readyForPresentation' => $readyForPresentation,
            // Saves the presentation result, keeping the old one when not posted.
            'result' => $effectiveResult,
        // Closes the array of fields to save.
        ];

        // Starts the save attempt so database errors can be caught.
        try {
            // When the application is in MySQL, update it with a prepared statement.
            if ($isDatabaseApplication) {
                // Prepares the SQL UPDATE for the applications table.
                $saveStatus = DB::getConnection()->prepare(
                    // Opens the UPDATE query; the next lines list columns and the WHERE clause.
                    'UPDATE applications
                     SET status = :status,
                         presentation_stage = :presentation_stage,
                         coordinator_comment = :coordinator_comment,
                         grad_school_endorsed = :grad_school_endorsed,
                         payment_recorded = :payment_recorded,
                         ready_for_presentation = :ready_for_presentation,
                         workflow_state = :workflow_state,
                         result = :result
                     WHERE application_id = :application_id'
                // Closes and compiles the prepared UPDATE statement.
                );
                // Runs the UPDATE with the values bound to each placeholder.
                $saveStatus->execute([
                    // Binds the new application status.
                    'status' => $status,
                    // Binds the stage label used as the presentation stage.
                    'presentation_stage' => $updatedFields['stage'],
                    // Binds the coordinator comment.
                    'coordinator_comment' => $comment,
                    // Binds the endorsement flag as 1 or 0.
                    'grad_school_endorsed' => $updatedFields['gradSchoolEndorsed'] ? 1 : 0,
                    // Binds the payment-recorded flag as 1 or 0.
                    'payment_recorded' => $updatedFields['paymentRecorded'] ? 1 : 0,
                    // Binds the readiness flag as 1 or 0.
                    'ready_for_presentation' => $updatedFields['readyForPresentation'] ? 1 : 0,
                    // Binds the workflow states as JSON text.
                    'workflow_state' => json_encode($workflowState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null,
                    // Binds the presentation result, or null when empty.
                    'result' => $updatedFields['result'] !== '' ? $updatedFields['result'] : null,
                    // Binds the id of the application being updated.
                    'application_id' => (int) $app['id'],
                // Closes the parameter list and executes the UPDATE.
                ]);
                // Also stores the comment in the application comment history.
                saveApplicationComment((int) $app['id'], $comment);
            // Closes the database update branch.
            }


            // Checks whether a demo-store record exists for this application.
            if (findApplication((int) $app['id'])) {
                // Updates the demo application record with the same fields.
                updateApplicationRecord((int) $app['id'], $updatedFields);
            // Closes the demo-store update branch.
            }

            // Refreshes the in-memory application so the page shows the saved values.
            $app = array_merge($app, $updatedFields);
            // Shows the success message after saving.
            $processSuccess = 'Application progress has been saved successfully.';
        // Catches any save error so the page can show a friendly message.
        } catch (Throwable $e) {
            // Checks a transaction is open before trying to roll it back.
            if (isset($pdo) && $pdo->inTransaction()) {
                // Undoes the half-finished database changes.
                $pdo->rollBack();
            // Closes the transaction rollback check.
            }
            // Tells the coordinator the save failed and can be retried.
            $processError = 'Unable to save application progress. Please try again.';
        // Closes the try/catch around the save.
        }

        // Opens a comment block: approving or revoking stage progression.
        /*
         * Approving (or revoking) the progression to the next stage.
         *
         * The approval is only applied here, when the coordinator presses Save
         * Process. Approving marks the stage complete, which is what unlocks
         * the next stage for the student; revoking takes that away again.
         * Documents and workflow history are never touched.
         */
        // Applies an approve or revoke action only when the save had no errors.
        if ($processError === '' && ($approveStage !== '' || $disapproveStage !== '')) {
            // Uses the approved stage, or the revoked one when approve was not sent.
            $actionStage   = $approveStage !== '' ? $approveStage : $disapproveStage;
            // Gets the application id for the stage the action targets.
            $actionAppId  = (int) ($postedAppIds[$actionStage] ?? 0);
            // Loads that application, or null when no id was posted.
            $actionApp    = $actionAppId > 0 ? applicationById($actionAppId) : null;
            // If the stage has no application, the action cannot be applied.
            if (!$actionApp) {
                // Explains that the stage has no application to approve.
                $processError = 'The student has no application for that stage, so the progression cannot be approved.';
            // Otherwise, handle the approve action.
            } elseif ($approveStage !== '') {
                // Loads the uploads of the stage being approved.
                $actionUploads = uploadsForApplication($actionAppId);
                // Checks which required documents are still missing for that stage.
                $actionMissing = applicationMissingRequirements($track, $actionStage, $actionUploads, $actionApp);
                // Comment: use the result of the stage being approved.
                // The result of the stage that is approved, not the one of the
                // Comment: not the result of the stage the coordinator is viewing.
                // stage the coordinator happens to be looking at.
                // Reads the posted fields of the stage being approved.
                $actionStagePosted = $_POST['stage_data'][$actionStage] ?? [];
                // Guards against malformed stage data for the approved stage.
                if (!is_array($actionStagePosted)) {
                    // Uses an empty array when the posted data is invalid.
                    $actionStagePosted = [];
                // Closes the guard for the approved stage data.
                }
                // Uses the result posted for that stage, falling back to its saved result.
                $actionResult = trim((string) ($actionStagePosted['result'] ?? '')) !== ''
                    // Takes the freshly posted result when it is not empty.
                    ? trim((string) $actionStagePosted['result'])
                    // Otherwise keeps the result already stored on the application.
                    : (string) ($actionApp['result'] ?? '');
                // Blocks approval while required documents are still missing.
                if ($actionMissing) {
                    // Tells the coordinator to complete the required items first.
                    $processError = 'This stage cannot be approved yet. Please complete the required items first.';
                // Also blocks approval when no presentation result is recorded.
                } elseif ($actionResult === '') {
                    // Asks the coordinator to record the result before approving.
                    $processError = 'Record the presentation result before approving the next stage.';
                // A result of requires_revision must not unlock the next stage.
                } elseif ($actionResult === 'requires_revision') {
                    // Comment: a revision keeps the student on the same stage.
                    // A revision keeps the student right where they are; the
                    // Comment: the next stage opens only once the result is approved.
                    // next stage opens only once the result is approved.
                    // Explains that the next stage stays locked until the revision is approved.
                    $processError = 'The presentation result requires revision. The next stage stays locked until the revised result is approved.';
                // Starts the branch that actually completes the stage.
                } else {
                    // Marks the stage completed and stores the approved result.
                    updateApplicationRecord($actionAppId, ['status' => 'completed', 'result' => $actionResult]);
                    // Confirms the approval was saved.
                    $processSuccess = 'Application progress has been saved successfully.';
                // Closes the approval branch.
                }
            // Handles the revoke action when approve was not requested.
            } else {
                // Sends the stage back to ready_for_presentation, locking the next stage.
                updateApplicationRecord($actionAppId, ['status' => 'ready_for_presentation']);
                // Confirms the revoke was saved.
                $processSuccess = 'Application progress has been saved successfully.';
            // Closes the revoke branch.
            }
        // Closes the approve and revoke action block.
        }

        // Opens a comment block: re-read the application after saving.
        /*
         * Read the application back, so the page shows exactly what is stored:
         * the approval action may have changed the status after the form was
         * written, and the stages of the track are built from the database.
         */
        // Only reloads when the application exists in the database.
        if ($isDatabaseApplication && (int) ($app['id'] ?? 0) > 0) {
            // Reads the application again so the page shows what is actually stored.
            $fresh = applicationById((int) $app['id']);
            // Only replaces the local copy when the reload succeeded.
            if ($fresh) {
                // Swaps in the freshly loaded application row.
                $app = $fresh;
            // Closes the reload check.
            }
        // Closes the block that re-reads the application.
        }
        // Closes the else branch that runs when the status change is allowed.
        }
    // Closes the else branch that runs for a valid status.
    }
// Closes the POST save block.
}

/* ------------------------------------------------------------------
 * The stages of the student, each with everything that belongs to it
 * ------------------------------------------------------------------ */
// Loads all applications of this student, one per workflow stage.
$studentApps  = $email !== '' ? applicationsForStudent($email) : [];
// Loads the presentation schedules linked to the student email.
$schedules    = $email !== '' ? schedulesForEmail($email) : [];
// Loads the student milestone progress for the track.
$progress     = $email !== '' ? getStudentProgress($email, $track) : [];
// Finds which stage the student is currently working on.
$currentIdx   = workflowCurrentIndex($progress, $stageList);
// Gets the key of the current stage, or empty text if none.
$currentStageKey = $stageKeys[$currentIdx] ?? '';
// Works out which stages are locked because earlier ones are unfinished.
$lockStates   = stageLockStates($stageList, $currentIdx);

// Prepares the array that will hold one display panel per stage.
$panels = [];
// Builds a panel for every stage of the track.
foreach ($stages as $key => $stage) {
    // Comment: finds the student application that belongs to this stage.
    // The application the student has for this stage (the one being edited
    // Comment: the edited stage always wins for its own panel.
    // always wins for its own stage).
    // Starts with no application for this stage.
    $stageApp = null;
    // Searches the student applications for this stage.
    foreach ($studentApps as $candidate) {
        // Matches the application whose stage key equals this stage.
        if (($candidate['stageKey'] ?? '') === $key) {
            // Remembers the matching application for this stage.
            $stageApp = $candidate;
            // Stops searching once the stage application is found.
            break;
        // Closes the matching condition.
        }
    // Closes the loop over the student applications.
    }
    // Lets the application being edited override its own stage data.
    if ((string) ($app['stageKey'] ?? '') === $key && $app) {
        // Merges the edited application into the stage panel data.
        $stageApp = array_merge($stageApp ?? [], $app);
    // Closes the override for the edited stage.
    }

    // Loads the documents uploaded for this stage application.
    $stageUploads   = $stageApp ? uploadsForApplication((int) $stageApp['id']) : [];
    // Gets the required documents defined for this stage of the track.
    $required       = requiredDocumentsForStage($track, $key);
    // Combines requirements and uploads into rows for the document checklist.
    $documentRows   = documentsAndRequirements($required, $stageUploads, $stageApp ?? []);
    // Builds the milestone steps for this stage.
    $milestoneSteps = stageMilestoneSteps($email, $track, $key);
    // Counts how many milestones are done out of the total.
    $milestoneStats = milestoneProgress($milestoneSteps);
    // Lists the requirements still missing for this stage.
    $missing        = applicationMissingRequirements($track, $key, $stageUploads, $stageApp ?? []);

    // Comment: finds the presentation scheduled for this stage.
    // The presentation scheduled for this stage.
    // Starts with no schedule for this stage.
    $stageSchedule = null;
    // Searches the student schedules for this stage.
    foreach ($schedules as $schedule) {
        // Matches a schedule by the stage application id.
        if ((int) ($schedule['applicationId'] ?? 0) === (int) ($stageApp['id'] ?? 0)) {
            // Remembers the matching schedule.
            $stageSchedule = $schedule;
            // Stops searching after finding a schedule by application id.
            break;
        // Closes the application-id match.
        }
        // Otherwise matches a schedule by its stage label.
        if (stageKeyFromLabel((string) ($schedule['stage'] ?? '')) === $key) {
            // Remembers the schedule found by stage label.
            $stageSchedule = $schedule;
            // Stops searching after matching by stage label.
            break;
        // Closes the stage-label match.
        }
    // Closes the loop over schedules.
    }

    // Assumes the stage requirements are complete until a check fails.
    $requirementsComplete = true;
    // Checks each milestone that counts as a requirement.
    foreach ($milestoneSteps as $step) {
        // Only these milestone keys are treated as real requirements.
        if (in_array($step['key'] ?? '', ['paper', 'adviser_endorsement', 'coord_review', 'grad_school_endorsement', 'payment', 'ready_for_presentation', 'presentation'], true)) {
            // If a required milestone is not done, the stage is incomplete.
            if (($step['state']['status'] ?? 'pending') !== 'done') {
                // Marks the stage requirements as not complete.
                $requirementsComplete = false;
                // Stops checking after the first unfinished requirement.
                break;
            // Closes the done check.
            }
        // Closes the requirement key filter.
        }
    // Closes the milestone loop.
    }

    // Opens a comment block: when the Presentation Result control opens.
    /*
     * The Presentation Result control belongs to 'Presentation Conducted &
     * Result Recorded', the last milestone of the stage. It opens only once
     * every earlier milestone is done (schedule confirmed, payment verified,
     * readiness marked), or once a result is already on record so the
     * coordinator can update the decision.
     */
    // The result control opens if a result is already recorded.
    $resultReached = (string) ($stageApp['result'] ?? '') !== '';
    // Otherwise, check whether all earlier milestones are done.
    if (!$resultReached) {
        // Assumes the result milestone can be reached until a check fails.
        $resultReached = true;
        // Loops over the milestones before the result step.
        foreach ($milestoneSteps as $msStep) {
            // Skips the result milestone itself.
            if (($msStep['key'] ?? '') === 'result') {
                // Moves on to the next milestone.
                continue;
            // Closes the result milestone skip.
            }
            // A milestone that is not done keeps the result control locked.
            if (($msStep['state']['status'] ?? 'pending') !== 'done') {
                // Marks the result control as not yet reachable.
                $resultReached = false;
                // Stops checking at the first unfinished milestone.
                break;
            // Closes the done check.
            }
        // Closes the loop over earlier milestones.
        }
    // Closes the block that checks earlier milestones.
    }

    // Stores everything the page needs for this stage panel.
    $panels[$key] = [
        // Panel key: the stage key.
        'key'        => $key,
        // Panel stage: the stage definition with its label.
        'stage'      => $stage,
        // Panel app: the application row for this stage, if any.
        'app'        => $stageApp,
        // Panel appId: the application id as an integer, or 0.
        'appId'      => (int) ($stageApp['id'] ?? 0),
        // Panel status: the application status text.
        'status'     => (string) ($stageApp['status'] ?? ''),
        // Panel title: the research or paper title.
        'title'      => (string) ($stageApp['title'] ?? ''),
        // Panel date: when the application was submitted.
        'date'       => (string) ($stageApp['date'] ?? ''),
        // Panel result: the recorded presentation result.
        'result'     => (string) ($stageApp['result'] ?? ''),
        // Panel uploads: files uploaded for this stage.
        'uploads'    => $stageUploads,
        // Panel required: the required documents of the stage.
        'required'   => $required,
        // Panel documents: requirement rows with their uploads.
        'documents'  => $documentRows,
        // Panel milestones: the step list shown in the tracker.
        'milestones' => $milestoneSteps,
        // Panel stats: done and total milestone counts.
        'stats'      => $milestoneStats,
        // Panel missing: requirements still not met.
        'missing'    => $missing,
        // Panel schedule: the presentation schedule, if any.
        'schedule'   => $stageSchedule,
        // Panel locked: whether the stage is still locked.
        'locked'     => $lockStates[$key]['locked'] ?? false,
        // Panel requirementsComplete: whether required milestones are done.
        'requirementsComplete' => $requirementsComplete,
        // Panel resultReached: whether the result control may open.
        'resultReached' => $resultReached,
        // Panel unlocked: true when nothing is missing.
        'unlocked'   => $missing === [],
    // Closes the panel data array.
    ];
// Closes the loop that builds the stage panels.
}

/* The stage the coordinator is looking at. */
// Picks the stage to show from the URL, then POST, then the application.
$selectedStage = trim((string) ($_GET['stage'] ?? ($_POST['selected_stage'] ?? ($app['stageKey'] ?? ($stageKeys[0] ?? '')))));
// Checks the chosen stage exists in the panels.
if (!isset($panels[$selectedStage])) {
    // Falls back to the application stage when the chosen one is unknown.
    $selectedStage = (string) ($app['stageKey'] ?? ($stageKeys[0] ?? ''));
// Closes the first validation of the selected stage.
}
// Checks again in case the application stage is also unknown.
if (!isset($panels[$selectedStage])) {
    // Falls back to the first stage of the track.
    $selectedStage = (string) ($stageKeys[0] ?? '');
// Closes the second validation of the selected stage.
}

// The progress over the whole track.
// Starts the count of finished milestones for the whole track.
$overallDone = 0;
// Starts the count of all milestones for the whole track.
$overallTotal = 0;
// Adds up milestone counts across every stage panel.
foreach ($panels as $panel) {
    // Adds this panel done milestone count.
    $overallDone += (int) $panel['stats']['done'];
    // Adds this panel total milestone count.
    $overallTotal += (int) $panel['stats']['total'];
// Closes the overall progress loop.
}

// Loads the shared page header, sidebar and opening HTML.
require_once __DIR__ . '/../../includes/header.php';
// Closes PHP so the HTML below can be printed.
?>

<?php /* Opens the page banner that holds the title and student summary. */ ?>
<div class="page-header page-banner">
<?php /*     Shows the page heading Process Application. */ ?>
    <h2>Process Application</h2>
<?php /*     Greets the coordinator and names the student whose application is open. */ ?>
    <p>Review the student's progress, documents and workflow for <?= htmlspecialchars((string) (($studentProfile['name'] ?? '') ?: ($app['student'] ?? 'this student'))) ?>.</p>
<?php /* Closes the page banner. */ ?>
</div>

<?php /* Empty container where JavaScript can post notifications. */ ?>
<div class="page-notifications" id="pageNotifications" aria-live="polite"></div>

<?php /* Only shows the success alert when a save just succeeded. */ ?>
<?php if ($processSuccess): ?>
<?php /* Displays the green success alert with a check icon. */ ?>
<div class="alert alert-success" data-auto-dismiss><i class="fas fa-check-circle"></i> <?= htmlspecialchars($processSuccess) ?></div>
<?php /* Ends the success alert condition. */ ?>
<?php endif; ?>
<?php /* Only shows the error alert when a save was rejected. */ ?>
<?php if ($processError): ?>
<?php /* Displays the red error alert with the error message. */ ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($processError) ?></div>
<?php /* Ends the error alert condition. */ ?>
<?php endif; ?>

<?php /* When no application could be loaded, show a warning instead of the page. */ ?>
<?php if (!$app): ?>
<?php /* Displays No application selected with an info icon. */ ?>
<div class="alert alert-warning"><i class="fas fa-info-circle"></i> No application selected.</div>
<?php /* Otherwise, render the full process page for the loaded application. */ ?>
<?php else: ?>

<?php /* PHP comment marking the sliding progress tracker section. */ ?>
<?php /* ---------------- the sliding progress tracker ---------------- */ ?>
<?php /* Opens the horizontal slider that shows the student stage cards. */ ?>
<div class="stage-slider" id="stageSlider">
<?php /*     Left arrow button that scrolls the stage slider backwards. */ ?>
    <button type="button" class="stage-slider-nav" id="stageSliderPrev" aria-label="Previous stage"><i class="fas fa-chevron-left"></i></button>
<?php /*     Opens the visible window of the stage slider. */ ?>
    <div class="stage-slider-viewport">
<?php /*         Opens the track that holds all stage cards side by side. */ ?>
        <div class="stage-slider-track" id="stageSliderTrack">
<?php /*             Starts a loop that prints one slider card per stage. */ ?>
            <?php foreach ($panels as $key => $panel):
                // Reads the stage application status.
                $stageStatus = $panel['status'];
                // True when the stage is completed or approved.
                $isCompleted = in_array($stageStatus, ['completed', 'approved'], true);
                // True when the stage is still locked.
                $isLocked    = $panel['locked'];
                // True when this is the stage the student is on now.
                $isCurrent   = ($key === $currentStageKey);
                // True for stages that are open but not current or completed.
                $isNext      = !$isCompleted && !$isLocked && !$isCurrent;
                // Picks the card style for a completed stage.
                if ($isCompleted) {
                    // Card gets the completed style class.
                    $sliderClass = 'is-completed';
                    // Card badge reads Completed.
                    $sliderLabel = 'Completed';
                    // Badge uses the approved colour style.
                    $sliderBadge = 'status-approved';
                // Otherwise, style the card as locked.
                } elseif ($isLocked) {
                    // Card gets the locked style class.
                    $sliderClass = 'is-locked';
                    // Card badge reads Locked.
                    $sliderLabel = 'Locked';
                    // Badge uses the not-submitted colour style.
                    $sliderBadge = 'status-not-submitted';
                // Otherwise, style the card as the current stage.
                } elseif ($isCurrent) {
                    // Card gets the active style class.
                    $sliderClass = 'is-active';
                    // Card badge reads In Progress.
                    $sliderLabel = 'In Progress';
                    // Badge uses the confirmed colour style.
                    $sliderBadge = 'status-confirmed';
                // Any remaining stage is shown as already approved.
                } else {
                    // Card gets the approved style class.
                    $sliderClass = 'is-approved';
                    // Card badge reads Approved.
                    $sliderLabel = 'Approved';
                    // Badge uses the submitted colour style.
                    $sliderBadge = 'status-submitted';
                // Closes the card-style if/else chain.
                }
                // Finds the stage position in the workflow for its number.
                $stageNumber = array_search($key, $stageKeys, true);
            // Closes PHP so the card markup can be printed.
            ?>
<?php /*             Prints the clickable stage card, highlighted when selected. */ ?>
            <button type="button" class="stage-slider-card <?= $sliderClass ?><?= $key === $selectedStage ? ' is-selected' : '' ?>"
<?php /*                     Stores the stage key on the card for JavaScript. */ ?>
                    data-stage="<?= htmlspecialchars($key) ?>"
<?php /*                     Tells screen readers whether this card is the selected one. */ ?>
                    aria-pressed="<?= $key === $selectedStage ? 'true' : 'false' ?>">
<?php /*                 Opens the circle that shows the stage number or lock icon. */ ?>
                <span class="stage-slider-num">
<?php /*                     Shows a lock icon for locked stages, otherwise the stage number. */ ?>
                    <?php if ($isLocked): ?><i class="fas fa-lock" aria-hidden="true"></i><?php else: ?><?= (int) $stageNumber + 1 ?><?php endif; ?>
<?php /*                 Closes the stage number circle. */ ?>
                </span>
<?php /*                 Opens the text area of the card. */ ?>
                <span class="stage-slider-text">
<?php /*                     Shows the stage readable label. */ ?>
                    <span class="stage-slider-label"><?= htmlspecialchars($panel['stage']['label']) ?></span>
<?php /*                     Shows the coloured badge with the stage status text. */ ?>
                    <span class="status-badge <?= $sliderBadge ?> stage-slider-badge"><?= htmlspecialchars($sliderLabel) ?></span>
<?php /*                 Closes the card text area. */ ?>
                </span>
<?php /*             Closes the stage card button. */ ?>
            </button>
<?php /*             Ends the loop that prints the slider cards. */ ?>
            <?php endforeach; ?>
<?php /*         Closes the slider track. */ ?>
        </div>
<?php /*     Closes the slider viewport. */ ?>
    </div>
<?php /*     Right arrow button that scrolls the stage slider forwards. */ ?>
    <button type="button" class="stage-slider-nav" id="stageSliderNext" aria-label="Next stage"><i class="fas fa-chevron-right"></i></button>
<?php /* Closes the stage slider. */ ?>
</div>

<?php /* Opens the summary row under the slider. */ ?>
<div class="stage-slider-summary">
<?php /*     Starts the summary item that shows the current stage. */ ?>
    <span><i class="fas fa-map-marker-alt" style="margin-right:5px;"></i>Current stage:
<?php /*         Prints the label of the student current stage. */ ?>
        <strong><?= htmlspecialchars($stageList[$currentIdx]['label'] ?? '—') ?></strong></span>
<?php /*     Starts the summary item that shows the track. */ ?>
    <span><i class="fas fa-route" style="margin-right:5px;"></i>Track:
<?php /*         Prints the track name. */ ?>
        <strong><?= htmlspecialchars($trackLabel) ?></strong></span>
<?php /*     Starts the summary item for overall milestone progress. */ ?>
    <span><i class="fas fa-graduation-cap" style="margin-right:5px;"></i>Overall progress:
<?php /*         Prints done milestones out of the total. */ ?>
        <strong><?= (int) $overallDone ?> / <?= (int) $overallTotal ?> milestones</strong></span>
<?php /* Closes the summary row. */ ?>
</div>

<?php /* Opens the POST form that saves changes back to this page. */ ?>
<form method="post" action="<?= url('coordinator/applications/process.php?id=' . $app['id']) ?>" id="processForm" data-validate>
<?php /*     Hidden field that tells the server which stage tab was open. */ ?>
    <input type="hidden" name="selected_stage" id="selectedStageField" value="<?= htmlspecialchars($selectedStage) ?>">
<?php /*     Hidden field filled by JavaScript when Approve is clicked. */ ?>
    <input type="hidden" name="approve_stage" id="approveStageField" value="">
<?php /*     Hidden field filled by JavaScript when Revoke is clicked. */ ?>
    <input type="hidden" name="disapprove_stage" id="disapproveStageField" value="">
<?php /*     Prints one hidden application id field per stage. */ ?>
    <?php foreach ($panels as $key => $panel): ?>
<?php /*     Sends the application id that belongs to each stage. */ ?>
    <input type="hidden" name="stage_app_id[<?= htmlspecialchars($key) ?>]" value="<?= (int) $panel['appId'] ?>">
<?php /*     Ends the loop of hidden application id fields. */ ?>
    <?php endforeach; ?>

<?php /*     PHP comment marking the start of the per-stage panels. */ ?>
    <?php /* ---------------- one panel per stage ---------------- */ ?>
<?php /*     Starts a loop that prints one full panel per stage. */ ?>
    <?php foreach ($panels as $key => $panel):
        // Gets this panel application row.
        $stageApp  = $panel['app'];
        // Gets this stage readable name.
        $stageName = $panel['stage']['label'];
        // True when this panel is the one currently shown.
        $isSelected = $key === $selectedStage;
        // Comment: receipt details come from the student.
        // The receipt details come from the student, who sends them with the
        // Comment: the coordinator only reads them, never changes them.
        // official receipt. The coordinator only reads them, never changes them.
        // Reads the receipt details the student submitted for this stage.
        $receiptDetails = applicationReceiptDetails($stageApp ?? []);
    // Closes PHP so the panel markup can be printed.
    ?>
<?php /*     Opens the panel for this stage, hidden unless it is selected. */ ?>
    <section class="stage-panel" data-stage="<?= htmlspecialchars($key) ?>" data-saved-approved="<?= in_array($panel['status'], ['completed', 'approved'], true) ? '1' : '0' ?>"<?= $isSelected ? '' : ' hidden' ?>>

<?php /*         Opens the card that shows the current application details. */ ?>
        <div class="card">
<?php /*             Card header titled Current Application. */ ?>
            <div class="card-header"><h3>Current Application</h3></div>
<?php /*             Opens the body of the details card. */ ?>
            <div class="card-body">
<?php /*                 When the student has no application for this stage, show a notice. */ ?>
                <?php if (!$stageApp): ?>
<?php /*                 Opens the info box explaining the missing application. */ ?>
                <div class="alert alert-info" style="margin:0;">
<?php /*                     Shows an info icon inside the notice. */ ?>
                    <i class="fas fa-info-circle"></i>
<?php /*                     Tells the coordinator the student has no application for this stage yet. */ ?>
                    <div>The student has no application for <strong><?= htmlspecialchars($stageName) ?></strong> yet.</div>
<?php /*                 Closes the info notice. */ ?>
                </div>
<?php /*                 Otherwise, show the application details. */ ?>
                <?php else: ?>
<?php /*                 Opens a PHP block to compute the details shown below. */ ?>
                <?php
                // Opens a comment block: where the student details come from.
                /*
                 * The student's own records are the source of these three
                 * details. The application row of a stage may not carry them
                 * (only the row the page was opened with does), so falling back
                 * to it alone would leave the other stages blank.
                 */
                // Gets the student full name from the profile, application or stage row.
                $stageStudentName = (string) (($studentProfile['name'] ?? '') ?: ($stageApp['student'] ?? '') ?: ($app['student'] ?? ''));
                // Gets the program code from the stage or the student profile.
                $stageProgramCode = (string) ($stageApp['program'] ?? $studentProfile['programCode'] ?? '');
                // Gets the full program name from the profile or the PROGRAMS map.
                $stageProgramName = (string) (($studentProfile['program'] ?? '') ?: (PROGRAMS[$stageProgramCode] ?? $stageProgramCode));
                // Gets the adviser from the profile, presentation team or application.
                $stageAdviser     = (string) (($studentProfile['adviser'] ?? '') ?: ($presentationTeam[$key]['adviser'] ?? '') ?: ($stageApp['adviser'] ?? '') ?: ($app['adviser'] ?? ''));
                // Closes the PHP block that prepared the details.
                ?>
<?php /*                 Opens the grid that lays out the application details. */ ?>
                <div class="detail-grid">
<?php /*                     Shows the student name, or a dash when unknown. */ ?>
                    <div class="detail-item"><label>Student</label><span><?= htmlspecialchars($stageStudentName !== '' ? $stageStudentName : '—') ?></span></div>
<?php /*                     Shows the student program name. */ ?>
                    <div class="detail-item"><label>Program</label><span><?= htmlspecialchars($stageProgramName !== '' ? $stageProgramName : '—') ?></span></div>
<?php /*                     Shows the track label for this application. */ ?>
                    <div class="detail-item"><label>Track</label><span><?= htmlspecialchars($trackLabel) ?></span></div>
<?php /*                     Shows the adviser name, or a dash when unknown. */ ?>
                    <div class="detail-item"><label>Adviser</label><span><?= htmlspecialchars($stageAdviser !== '' ? $stageAdviser : '—') ?></span></div>
<?php /*                     Shows the stage this panel belongs to. */ ?>
                    <div class="detail-item"><label>Current Stage</label><span><?= htmlspecialchars($stageName) ?></span></div>
<?php /*                     Shows the status as a coloured badge. */ ?>
                    <div class="detail-item"><label>Current Status</label><span><?= statusBadge($panel['status']) ?></span></div>
<?php /*                     Shows the research or paper title across the full row. */ ?>
                    <div class="detail-item" style="grid-column:1/-1;"><label>Research / Paper Title</label><span><?= htmlspecialchars($panel['title']) ?></span></div>
<?php /*                     Shows the submission date as Mmm dd, yyyy, or a dash. */ ?>
                    <div class="detail-item"><label>Submitted</label><span><?= $panel['date'] ? htmlspecialchars(date('M d, Y', strtotime($panel['date']))) : '—' ?></span></div>
<?php /*                 Closes the details grid. */ ?>
                </div>
<?php /*                 Ends the check for a missing stage application. */ ?>
                <?php endif; ?>
<?php /*             Closes the details card body. */ ?>
            </div>
<?php /*         Closes the details card. */ ?>
        </div>

<?php /*         Opens the card for updating the application status. */ ?>
        <div class="card">
<?php /*             Card header titled Update Status. */ ?>
            <div class="card-header"><h3>Update Status</h3></div>
<?php /*             Opens the body of the update status card. */ ?>
            <div class="card-body">
<?php /*                 When there is no application, show a hint instead of the status form. */ ?>
                <?php if (!$stageApp): ?>
<?php /*                 Tells the coordinator the student must submit this stage first. */ ?>
                <p class="field-hint" style="margin:0;">The student submits an application for this stage first — then the status can be updated here.</p>
<?php /*                 Otherwise, show the status fields. */ ?>
                <?php else: ?>
<?php /*                 Opens a row that holds the status and stage fields. */ ?>
                <div class="form-row">
<?php /*                     Opens the field for the application status. */ ?>
                    <div class="form-field">
<?php /*                         Labels the status dropdown as required. */ ?>
                        <label>Application Status <span class="required">*</span></label>
<?php /*                         Status dropdown that posts the chosen status for this stage. */ ?>
                        <select name="stage_data[<?= htmlspecialchars($key) ?>][status]" required>
<?php /*                             Placeholder option asking the coordinator to select a status. */ ?>
                            <option value="">Select status</option>
<?php /*                             Loops over the statuses a coordinator may set. */ ?>
                            <?php foreach (COORDINATOR_STATUSES as $statusKey): ?>
<?php /*                             Prints each status option and marks the saved one as selected. */ ?>
                            <option value="<?= $statusKey ?>" <?= $panel['status'] === $statusKey ? 'selected' : '' ?>><?= htmlspecialchars(STATUSES[$statusKey]['label']) ?></option>
<?php /*                             Ends the status options loop. */ ?>
                            <?php endforeach; ?>
<?php /*                         Closes the status dropdown. */ ?>
                        </select>
<?php /*                     Closes the status field. */ ?>
                    </div>
<?php /*                     Opens the field that shows the presentation stage. */ ?>
                    <div class="form-field">
<?php /*                         Labels the read-only presentation stage field. */ ?>
                        <label>Presentation Stage</label>
<?php /*                         PHP comment explaining why the stage cannot be edited here. */ ?>
                        <?php /*
                         * The stage is decided by the student's workflow
                         * progress and cannot be picked or changed here, so
                         * the field only shows the stage read-only. The hidden
                         * field keeps saving the stage this application
                         * belongs to, exactly as before.
                         */ ?>
<?php /*                         Shows the stage name as read-only text. */ ?>
                        <input type="text" value="<?= htmlspecialchars($stageName) ?>" readonly>
<?php /*                         Hidden field still sends the stage key with the form. */ ?>
                        <input type="hidden" name="stage_data[<?= htmlspecialchars($key) ?>][stage]" value="<?= htmlspecialchars($key) ?>">
<?php /*                         Explains the stage is set automatically from workflow progress. */ ?>
                        <p class="field-hint">Set automatically from the student's workflow progress.</p>
<?php /*                     Closes the presentation stage field. */ ?>
                    </div>
<?php /*                 Closes the form row. */ ?>
                </div>
<?php /*                 Opens the field for the coordinator comment. */ ?>
                <div class="form-field">
<?php /*                     Labels the recommendations and comments box. */ ?>
                    <label>Recommendations / Comments</label>
<?php /*                     Text box for recommendations, pre-filled with the saved comment. */ ?>
                    <textarea name="stage_data[<?= htmlspecialchars($key) ?>][comment]" placeholder="Enter recommendations or revision notes for the student..."><?= htmlspecialchars($stageApp['coordinatorComment'] ?? '') ?></textarea>
<?php /*                 Closes the comment field. */ ?>
                </div>
<?php /*                 Ends the check for a missing stage application. */ ?>
                <?php endif; ?>
<?php /*             Closes the update status card body. */ ?>
            </div>
<?php /*         Closes the update status card. */ ?>
        </div>

<?php /*         Opens the milestone tracker card for this stage. */ ?>
        <div class="card milestone-card" id="milestone-tracker-<?= htmlspecialchars($key) ?>">
<?php /*             Opens the header bar of the milestone tracker card. */ ?>
            <div class="card-header">
<?php /*                 Shows the card title Milestone Tracker with the current stage name. */ ?>
                <h3><i class="fas fa-tasks"></i> Milestone Tracker — <?= htmlspecialchars($stageName) ?></h3>
<?php /*                 Opens the summary box that shows milestone progress counts. */ ?>
                <div class="ms-overview">
<?php /*                     Prints how many milestones are done out of the total for this stage. */ ?>
                    <span class="ms-overview-text"><?= (int) $panel['stats']['done'] ?> of <?= (int) $panel['stats']['total'] ?> milestones complete</span>
<?php /*                     Draws the progress bar whose width is the done percentage of all milestones. */ ?>
                    <span class="ms-overview-bar"><span class="ms-overview-fill" style="width:<?= (int) $panel['stats']['total'] > 0 ? (int) round($panel['stats']['done'] * 100 / $panel['stats']['total']) : 0 ?>%;"></span></span>
<?php /*                 Closes the milestone summary box. */ ?>
                </div>
<?php /*             Closes the milestone card header. */ ?>
            </div>
<?php /*             Opens the body of the milestone tracker card. */ ?>
            <div class="card-body">
<?php /*                 Checks whether this stage has no milestones defined. */ ?>
                <?php if (!$panel['milestones']): ?>
<?php /*                 Shows a message when the stage has no milestones. */ ?>
                <p class="ms-empty">No milestones are defined for this stage.</p>
<?php /*                 Starts the branch used when milestones exist. */ ?>
                <?php else: ?>
<?php /*                 Opens the milestone stage wrapper used by the tracker styles. */ ?>
                <div class="ms-stage is-current is-open" data-ms-stage>
<?php /*                     Opens the inner body that holds the milestone rows. */ ?>
                    <div class="ms-stage-body" style="border-top:none;">
<?php /*                         Loops over every milestone, keeping its index in $si and row data in $step. */ ?>
                        <?php foreach ($panel['milestones'] as $si => $step):
                            // Reads the milestone status from $step, defaulting to pending.
                            $state = $step['state']['status'] ?? 'pending';
                            // Marks the milestone as done when its status is done.
                            $isDone = $state === 'done';
                            // Marks the milestone as flagged when its status is flagged.
                            $isFlagged = $state === 'flagged';
                            // Marks the milestone as submitted when its status is submitted.
                            $isSubmitted = $state === 'submitted';
                            // Sets $isActive for the first pending milestone that is not done, flagged or submitted.
                            $isActive = !$isDone && !$isFlagged && !$isSubmitted && $si === $panel['stats']['firstPending'];
                            // Chooses the icon state shown for this milestone row.
                            $iconState = $isFlagged ? 'flagged' : ($isDone ? 'done' : ($isSubmitted ? 'submitted' : ($isActive ? 'active' : 'pending')));
                            // Chooses the CSS class that colours this milestone row.
                            $rowClass = $isFlagged ? 'is-flagged' : ($isDone ? 'is-done' : ($isSubmitted ? 'is-submitted' : ($isActive ? 'is-active' : 'is-pending')));
                            // Reads the milestone key used to decide which action control to show.
                            $stepKey = $step['key'] ?? '';
                        // Closes the PHP block that prepared the milestone variables.
                        ?>
<?php /*                         Opens one milestone row with its state class. */ ?>
                        <div class="ms-step <?= $rowClass ?>">
<?php /*                             Opens the left column that holds the icon and connector line. */ ?>
                            <div class="ms-step-col">
<?php /*                                 Prints the milestone icon for the chosen state. */ ?>
                                <?= stepIconHtml($iconState) ?>
<?php /*                                 Checks that this is not the last milestone before drawing a connector. */ ?>
                                <?php if ($si < count($panel['milestones']) - 1): ?>
<?php /*                                 Draws the vertical connector line, marked done when the step is done. */ ?>
                                <span class="ms-step-line<?= $isDone ? ' done' : '' ?>"></span>
<?php /*                                 Ends the connector line check. */ ?>
                                <?php endif; ?>
<?php /*                             Closes the icon column. */ ?>
                            </div>
<?php /*                             Opens the column that holds the milestone label and detail. */ ?>
                            <div class="ms-step-body">
<?php /*                                 Opens the row that holds the milestone label and its tags. */ ?>
                                <div class="ms-step-label">
<?php /*                                     Prints the milestone label, escaped for safe HTML output. */ ?>
                                    <span><?= htmlspecialchars($step['label']) ?></span>
<?php /*                                     Shows a Coordinator chip when the milestone is set by the coordinator. */ ?>
                                    <?php if ($step['coord']): ?><span class="coord-chip">Coordinator</span><?php endif; ?>
<?php /*                                     Shows a Next tag on the milestone the student should work on. */ ?>
                                    <?php if ($isActive): ?><span class="ms-step-tag active">Next</span><?php endif; ?>
<?php /*                                     Shows a Verified tag when the milestone is done. */ ?>
                                    <?php if ($isDone): ?><span class="ms-step-tag done">Verified</span><?php endif; ?>
<?php /*                                     Shows a Submitted tag when the student submitted the milestone. */ ?>
                                    <?php if ($isSubmitted): ?><span class="ms-step-tag submitted">Submitted</span><?php endif; ?>
<?php /*                                     Shows an Action needed tag when the milestone is flagged. */ ?>
                                    <?php if ($isFlagged): ?><span class="ms-step-tag flagged">Action needed</span><?php endif; ?>
<?php /*                                 Closes the milestone label row. */ ?>
                                </div>
<?php /*                                 Prints the milestone detail text under its label. */ ?>
                                <p class="ms-step-detail"><?= htmlspecialchars($step['detail']) ?></p>
<?php /*                             Closes the label and detail column. */ ?>
                            </div>
<?php /*                             Opens the action column for this milestone. */ ?>
                            <div class="ms-step-action">
<?php /*                                 For the readiness milestone, checks that a stage application exists. */ ?>
                                <?php if ($stepKey === 'ready_for_presentation' && $stageApp): ?>
<?php /*                                     Opens the inline label that wraps the readiness checkbox. */ ?>
                                    <label style="display:inline-flex;align-items:center;gap:.45rem;font-size:.78rem;cursor:pointer;white-space:nowrap;">
<?php /*                                         Hidden field sends 0 so an unchecked box still saves a value. */ ?>
                                        <input type="hidden" name="stage_data[<?= htmlspecialchars($key) ?>][readyForPresentation]" value="0">
<?php /*                                         Checkbox that marks the student ready for the presentation. */ ?>
                                        <input type="checkbox" name="stage_data[<?= htmlspecialchars($key) ?>][readyForPresentation]" value="1" style="width:auto;" <?= !empty($stageApp['readyForPresentation']) ? 'checked' : '' ?>>
<?php /*                                         Label text switches between Ready and Mark Ready. */ ?>
                                        <span><?= !empty($stageApp['readyForPresentation']) ? 'Ready' : 'Mark Ready' ?></span>
<?php /*                                     Closes the readiness label. */ ?>
                                    </label>
<?php /*                                 For the result milestone, shows the recorded presentation result. */ ?>
                                <?php elseif ($stepKey === 'result'): ?>
<?php /*                                     Prints Approved, Requires revision or Not recorded for the result. */ ?>
                                    <span class="ms-step-detail" style="margin:0;"><?= htmlspecialchars($panel['result'] === 'approved' ? 'Approved' : ($panel['result'] === 'requires_revision' ? 'Requires revision' : 'Not recorded')) ?></span>
<?php /*                                 Ends the milestone action checks. */ ?>
                                <?php endif; ?>
<?php /*                             Closes the milestone action column. */ ?>
                            </div>
<?php /*                         Closes the milestone row. */ ?>
                        </div>
<?php /*                         Ends the loop over milestones. */ ?>
                        <?php endforeach; ?>
<?php /*                     Closes the milestone body wrapper. */ ?>
                    </div>
<?php /*                 Closes the milestone stage wrapper. */ ?>
                </div>
<?php /*                 Ends the check for an empty milestone list. */ ?>
                <?php endif; ?>

<?php /*                 Opens the box holding the stage approval controls. */ ?>
                <div class="stage-approval">
<?php /*                     Finds the stage index and the next stage key, or empty at the last stage. */ ?>
                    <?php $stageIndex = array_search($key, $stageKeys, true); $nextStageKey = ($stageIndex !== false && isset($stageKeys[$stageIndex + 1])) ? $stageKeys[$stageIndex + 1] : ''; ?>
<?php /*                     Treats the stage as approved when its status is completed or approved. */ ?>
                    <?php $stageApproved = in_array($panel['status'], ['completed', 'approved'], true); ?>
<?php /*                     Allows a decision only when a next stage exists and this stage is not locked. */ ?>
                    <?php $canDecide = $nextStageKey !== '' && !$panel['locked']; ?>
<?php /*                     Checks whether there is a next stage after this one. */ ?>
                    <?php if ($nextStageKey !== ''): ?>
<?php /*                         Checks whether the coordinator may approve or revoke this stage. */ ?>
                        <?php if ($canDecide): ?>
<?php /*                         Opens the PHP block that works out the approval values. */ ?>
                        <?php
                        // Explains that a stage is approved once the student has submitted it.
                        // The stage can be approved when the student submitted
                        // Notes that milestones must be complete before approval.
                        // it, its milestones are complete and the result was
                        // Notes that both buttons stay visible so the coordinator sees the options.
                        // recorded. Both options always stay visible so the
                        // Notes that the buttons show what can currently be done here.
                        // coordinator can see what can be done here.
                        // Sets $approveReady when requirements, result and application all allow approval.
                        $approveReady = $panel['requirementsComplete'] && ($panel['result'] ?? '') === 'approved' && (bool) $stageApp;
                        // Reads the label of the next stage for the button text and hints.
                        $nextLabel = $panels[$nextStageKey]['stage']['label'];
                        // Closes the PHP block that prepared the approval values.
                        ?>
<?php /*                         Shows the Stage progression label above the approval buttons. */ ?>
                        <span class="stage-approval-label"><i class="fas fa-forward" aria-hidden="true"></i> Stage progression</span>
<?php /*                         Approve button carries the stage key for the pending decision. */ ?>
                        <button type="button" class="btn btn-outline" data-approve-stage="<?= htmlspecialchars($key) ?>"
<?php /*                                 Stores the current stage label used in the confirmation message. */ ?>
                                data-stage-label="<?= htmlspecialchars($stageName) ?>"
<?php /*                                 Stores the next stage label used in the confirmation message. */ ?>
                                data-next-label="<?= htmlspecialchars($nextLabel) ?>"
<?php /*                                 Tells the script whether approval is currently allowed. */ ?>
                                data-ready="<?= $approveReady ? '1' : '0' ?>"
<?php /*                                 Disables the approve button until the stage is ready. */ ?>
                                <?= $approveReady ? '' : 'disabled' ?>
<?php /*                                 Starts the tooltip text that explains why approval is or is not possible. */ ?>
                                title="<?= $approveReady
                                    // Tooltip shown when the stage can be approved and the next stage unlocked.
                                    ? 'Approve ' . htmlspecialchars($stageName) . ' and unlock ' . htmlspecialchars($nextLabel) . ' for the student'
                                    // Checks whether the recorded result needs revision.
                                    : (($panel['result'] ?? '') === 'requires_revision'
                                        // Tooltip telling the coordinator to approve after fixing the result.
                                        ? 'The result requires revision - approve the next stage once the result is set to Approved'
                                        // Fallback tooltip asking for the required items to be completed first.
                                        : 'This stage cannot be approved yet. Please complete the required items first.') ?>">
<?php /*                             Button text for approving the stage and unlocking the next one. */ ?>
                            <i class="fas fa-check"></i> Approve Next Stage
<?php /*                         Closes the approve button. */ ?>
                        </button>
<?php /*                         Disapprove button carries the stage key for the pending decision. */ ?>
                        <button type="button" class="btn btn-outline" data-disapprove-stage="<?= htmlspecialchars($key) ?>"
<?php /*                                 Stores the stage label for the disapproval message. */ ?>
                                data-stage-label="<?= htmlspecialchars($stageName) ?>"
<?php /*                                 Stores the next stage label for the disapproval message. */ ?>
                                data-next-label="<?= htmlspecialchars($nextLabel) ?>"
<?php /*                                 Disables disapproving unless the stage is already approved. */ ?>
                                <?= $stageApproved ? '' : 'disabled' ?>
<?php /*                                 Starts the tooltip for the disapprove button. */ ?>
                                title="<?= $stageApproved
                                    // Tooltip explaining that disapproval locks the next stage again.
                                    ? 'Revoke the approval of ' . htmlspecialchars($stageName) . ' and lock ' . htmlspecialchars($nextLabel) . ' again'
                                    // Tooltip shown when the stage is not approved yet.
                                    : htmlspecialchars($stageName) . ' is not approved yet' ?>">
<?php /*                             Button text for revoking the approval. */ ?>
                            <i class="fas fa-undo"></i> Disapprove Next Stage
<?php /*                         Closes the disapprove button. */ ?>
                        </button>
<?php /*                         Opens the hint line under the approval buttons. */ ?>
                        <span class="stage-approval-hint">
<?php /*                             Checks whether this stage is already approved. */ ?>
                            <?php if ($stageApproved): ?>
<?php /*                                 Tells the coordinator the next stage is unlocked for the student. */ ?>
                                <strong><?= htmlspecialchars($nextLabel) ?></strong> is unlocked for the student.
<?php /*                             Starts the branch for a stage that is not approved yet. */ ?>
                            <?php else: ?>
<?php /*                                 Explains that approving this stage unlocks the next one. */ ?>
                                Approving <strong><?= htmlspecialchars($stageName) ?></strong> unlocks <strong><?= htmlspecialchars($nextLabel) ?></strong> for the student.
<?php /*                             Ends the approved or not approved branch. */ ?>
                            <?php endif; ?>
<?php /*                         Closes the approval hint line. */ ?>
                        </span>
<?php /*                         Starts the branch used when the coordinator cannot decide here. */ ?>
                        <?php else: ?>
<?php /*                         Opens the hint shown for a locked or missing stage application. */ ?>
                        <span class="stage-approval-hint">
<?php /*                             Shows a lock icon in the hint. */ ?>
                            <i class="fas fa-lock" aria-hidden="true"></i>
<?php /*                             Chooses between a locked stage message and a missing application message. */ ?>
                            <?= $stageApp
                                // Message saying the stage is locked and cannot be approved here.
                                ? 'This stage is locked — the coordinator cannot approve the progression from here.'
                                // Message saying the student has no application for this stage.
                                : 'The student has no application for this stage yet.' ?>
<?php /*                         Closes the locked stage hint. */ ?>
                        </span>
<?php /*                         Ends the branch where the coordinator can decide. */ ?>
                        <?php endif; ?>
<?php /*                     Starts the branch for the last stage of the track. */ ?>
                    <?php else: ?>
<?php /*                     Tells the coordinator this is the last stage, so there is no next stage. */ ?>
                    <span class="stage-approval-hint"><i class="fas fa-flag-checkered" aria-hidden="true"></i>This is the last stage of the <?= htmlspecialchars($trackLabel) ?> track — there is no next stage to approve.</span>
<?php /*                     Ends the check for a next stage. */ ?>
                    <?php endif; ?>
<?php /*                 Closes the stage approval box. */ ?>
                </div>
<?php /*             Closes the body of the milestone tracker card. */ ?>
            </div>
<?php /*         Closes the milestone tracker card. */ ?>
        </div>

<?php /*         Opens the card that lists documents and requirements for this stage. */ ?>
        <div class="card" id="documentsSection-<?= htmlspecialchars($key) ?>">
<?php /*             Card header title for documents and requirements. */ ?>
            <div class="card-header"><h3>Documents and Requirements</h3></div>
<?php /*             Opens the body of the documents card. */ ?>
            <div class="card-body">
<?php /*                 Checks whether this stage has a required documents list. */ ?>
                <?php if ($panel['required']): ?>
<?php /*                 Opens a hint paragraph describing the documents shown. */ ?>
                <p class="field-hint" style="margin-bottom:0.75rem;">
<?php /*                     Names the track whose documents are listed. */ ?>
                    The documents the <strong><?= htmlspecialchars($trackLabel) ?></strong> track requires for
<?php /*                     Names the stage the documents belong to. */ ?>
                    <strong><?= htmlspecialchars($stageName) ?></strong>, together with everything the student uploaded.
<?php /*                 Closes the documents hint paragraph. */ ?>
                </p>
<?php /*                 Ends the required documents hint. */ ?>
                <?php endif; ?>
<?php /*                 Opens the scrollable wrapper around the documents table. */ ?>
                <div class="table-responsive">
<?php /*                     Starts the table that lists documents and requirements. */ ?>
                    <table class="data-table">
<?php /*                         Opens the table head section. */ ?>
                        <thead>
<?php /*                             Opens the header row. */ ?>
                            <tr>
<?php /*                                 Column header for the document or requirement name. */ ?>
                                <th>Document / Requirement</th>
<?php /*                                 Column header for the review status badge. */ ?>
                                <th>Status</th>
<?php /*                                 Column header for the submission date. */ ?>
                                <th>Submitted</th>
<?php /*                                 Column header for the action controls. */ ?>
                                <th>Actions</th>
<?php /*                             Closes the header row. */ ?>
                            </tr>
<?php /*                         Closes the table head section. */ ?>
                        </thead>
<?php /*                         Opens the table body. */ ?>
                        <tbody>
<?php /*                             Checks whether the stage has no documents or requirements. */ ?>
                            <?php if (!$panel['documents']): ?>
<?php /*                             Shows a single empty row spanning all four columns. */ ?>
                            <tr><td colspan="4" style="color:var(--gray-400);">No documents or requirements for this stage yet.</td></tr>
<?php /*                             Ends the empty documents check. */ ?>
                            <?php endif; ?>
<?php /*                             Loops over each document or requirement row as $documentRow. */ ?>
                            <?php foreach ($panel['documents'] as $documentRow):
                                // Reads the requirement definition from the row, if any.
                                $requirement = $documentRow['requirement'];
                                // Reads the student upload from the row, if any.
                                $document    = $documentRow['upload'];
                                // Starts choosing the status badge for this row.
                                $badge = $requirement
                                    // Builds a badge from the requirement and the upload together.
                                    ? requirementStatusBadge($requirement, $document, $stageApp ?? [])
                                    // Builds a badge from the upload alone when there is no requirement.
                                    : documentStatusBadge($document);
                                // Uses the uploaded file name, or the requirement label as the row title.
                                $label = $document ? $document['fileName'] : ($requirement['label'] ?? 'Document');
                                // Reads the document type, falling back to the requirement label.
                                $type = $document['docType'] ?? ($requirement['label'] ?? '');
                                // Formats the upload date as Mon dd, yyyy, or leaves it empty.
                                $submittedDate = $document && !empty($document['date']) ? date('M d, Y', strtotime($document['date'])) : '';
                                // Notes that the official receipt holds the payment details.
                                // The official receipt carries the details of the
                                // Notes that clicking the receipt row shows those details.
                                // payment, so clicking it shows them.
                                // Starts checking whether this upload is an official receipt.
                                $isReceipt = (bool) $document
                                    // Classifies the document type to see whether it is a receipt.
                                    && classifyUploadDocType((string) ($document['docType'] ?? '')) === 'receipt';
                            // Closes the PHP block that prepared the row values.
                            ?>
<?php /*                             Opens one table row for this document or requirement. */ ?>
                            <tr>
<?php /*                                 Opens the cell that holds the document name. */ ?>
                                <td>
<?php /*                                     Checks whether this document is a receipt. */ ?>
                                    <?php if ($isReceipt): ?>
<?php /*                                     Opens a collapsible details block for the receipt. */ ?>
                                    <details class="tpl-doc-records">
<?php /*                                         Receipt summary shows the file name and a chevron icon. */ ?>
                                        <summary><strong><?= htmlspecialchars($label) ?></strong> <i class="fas fa-chevron-down" aria-hidden="true"></i></summary>
<?php /*                                         Opens the body of the receipt details. */ ?>
                                        <div class="tpl-doc-records-body">
<?php /*                                             Shows the receipt number, or a dash when unknown. */ ?>
                                            <div><span>Receipt Number</span><strong><?= htmlspecialchars($receiptDetails['receiptNumber'] !== '' ? $receiptDetails['receiptNumber'] : '—') ?></strong></div>
<?php /*                                             Shows the receipt amount, or a dash when unknown. */ ?>
                                            <div><span>Amount</span><strong><?= htmlspecialchars($receiptDetails['amount'] !== '' ? $receiptDetails['amount'] : '—') ?></strong></div>
<?php /*                                             Shows the receipt payment date, or a dash when unknown. */ ?>
                                            <div><span>Payment Date</span><strong><?= htmlspecialchars($receiptDetails['date'] !== '' ? $receiptDetails['date'] : '—') ?></strong></div>
<?php /*                                         Closes the receipt details body. */ ?>
                                        </div>
<?php /*                                     Closes the receipt details block. */ ?>
                                    </details>
<?php /*                                     Starts the branch for documents that are not receipts. */ ?>
                                    <?php else: ?>
<?php /*                                     Prints the document name in bold. */ ?>
                                    <strong><?= htmlspecialchars($label) ?></strong>
<?php /*                                     Ends the receipt and plain document branch. */ ?>
                                    <?php endif; ?>
<?php /*                                     Shows the document type when it differs from the label. */ ?>
                                    <?php if ($type !== '' && $type !== $label): ?>
<?php /*                                     Prints the document type in small grey text. */ ?>
                                    <div style="font-size:0.75rem;color:var(--gray-500);"><?= htmlspecialchars($type) ?></div>
<?php /*                                     Ends the document type check. */ ?>
                                    <?php endif; ?>
<?php /*                                 Closes the document name cell. */ ?>
                                </td>
<?php /*                                 Shows the status badge with its colour class and label. */ ?>
                                <td><span class="status-badge <?= $badge['class'] ?>"><?= htmlspecialchars($badge['label']) ?></span></td>
<?php /*                                 Shows the submission date, or a dash when there is none. */ ?>
                                <td><?= $submittedDate !== '' ? htmlspecialchars($submittedDate) : '<span style="color:var(--gray-400);">—</span>' ?></td>
<?php /*                                 Opens the cell that holds the row actions. */ ?>
                                <td class="actions">
<?php /*                                     Checks whether the student uploaded this document. */ ?>
                                    <?php if ($document): ?>
<?php /*                                         Checks whether the uploaded file still exists in storage. */ ?>
                                        <?php if (uploadStoragePath($document)): ?>
<?php /*                                         Download button opens the coordinator download script for the file. */ ?>
                                        <a class="btn btn-sm btn-outline" href="<?= url('coordinator/download.php?id=' . urlencode($document['id'])) ?>"><i class="fas fa-download"></i> Download</a>
<?php /*                                         Starts the branch used when the file is missing from storage. */ ?>
                                        <?php else: ?>
<?php /*                                         Tells the coordinator the file is unavailable. */ ?>
                                        <span style="color:var(--gray-400);font-size:.8rem;">Unavailable</span>
<?php /*                                         Ends the file existence check. */ ?>
                                        <?php endif; ?>
<?php /*                                         Review dropdown posts a new status for this upload id. */ ?>
                                        <select name="upload_status[<?= htmlspecialchars($document['id']) ?>]" class="doc-review-select" title="Review status">
<?php /*                                             Submitted option is selected when the upload status is submitted. */ ?>
                                            <option value="submitted" <?= $document['status'] === 'submitted' ? 'selected' : '' ?>>Submitted</option>
<?php /*                                             Verified option is selected for verified or approved uploads. */ ?>
                                            <option value="verified" <?= in_array($document['status'], ['verified', 'approved'], true) ? 'selected' : '' ?>>Verified</option>
<?php /*                                             Incomplete option is selected when the status is incomplete. */ ?>
                                            <option value="incomplete" <?= $document['status'] === 'incomplete' ? 'selected' : '' ?>>Incomplete</option>
<?php /*                                             Revision option is selected when the status is revision. */ ?>
                                            <option value="revision" <?= $document['status'] === 'revision' ? 'selected' : '' ?>>Revision</option>
<?php /*                                         Closes the review status dropdown. */ ?>
                                        </select>
<?php /*                                     For a receipt requirement with no upload, shows a special note. */ ?>
                                    <?php elseif ($requirement && ($requirement['kind'] ?? '') === 'receipt'): ?>
<?php /*                                         Tells the coordinator the receipt was not submitted yet. */ ?>
                                        <span style="color:var(--gray-400);font-size:.8rem;">Not submitted yet</span>
<?php /*                                     Starts the branch for a requirement with no upload. */ ?>
                                    <?php else: ?>
<?php /*                                     Shows a Not Submitted badge for the requirement. */ ?>
                                    <span class="status-badge status-not-submitted">Not Submitted</span>
<?php /*                                     Ends the upload or requirement checks. */ ?>
                                    <?php endif; ?>
<?php /*                                 Closes the actions cell. */ ?>
                                </td>
<?php /*                             Closes the document table row. */ ?>
                            </tr>
<?php /*                             Ends the loop over document rows. */ ?>
                            <?php endforeach; ?>
<?php /*                         Closes the table body. */ ?>
                        </tbody>
<?php /*                     Closes the documents table. */ ?>
                    </table>
<?php /*                 Closes the scrollable table wrapper. */ ?>
                </div>
<?php /*             Closes the documents card body. */ ?>
            </div>
<?php /*         Closes the documents card. */ ?>
        </div>

<?php /*         Opens the card that shows the workflow records for the stage. */ ?>
        <div class="card">
<?php /*             Card header title for workflow records with the stage name. */ ?>
            <div class="card-header"><h3>Workflow Records — <?= htmlspecialchars($stageName) ?></h3></div>
<?php /*             Opens the body of the workflow records card. */ ?>
            <div class="card-body">
<?php /*                 Opens a row that lays out the three record fields. */ ?>
                <div class="form-row">
<?php /*                     Opens the field that shows the receipt number. */ ?>
                    <div class="form-field">
<?php /*                         Label for the receipt number field. */ ?>
                        <label>Receipt Number</label>
<?php /*                         Shows the receipt number, or a dash when it is empty. */ ?>
                        <p class="workflow-record-value"><?= htmlspecialchars($receiptDetails['receiptNumber'] !== '' ? $receiptDetails['receiptNumber'] : '—') ?></p>
<?php /*                     Closes the receipt number field. */ ?>
                    </div>
<?php /*                     Opens the field that shows the payment amount. */ ?>
                    <div class="form-field">
<?php /*                         Label for the amount field. */ ?>
                        <label>Amount</label>
<?php /*                         Shows the payment amount, or a dash when it is empty. */ ?>
                        <p class="workflow-record-value"><?= htmlspecialchars($receiptDetails['amount'] !== '' ? $receiptDetails['amount'] : '—') ?></p>
<?php /*                     Closes the amount field. */ ?>
                    </div>
<?php /*                     Opens the field that shows the payment date. */ ?>
                    <div class="form-field">
<?php /*                         Label for the payment date field. */ ?>
                        <label>Payment Date</label>
<?php /*                         Shows the payment date, or a dash when it is empty. */ ?>
                        <p class="workflow-record-value"><?= htmlspecialchars($receiptDetails['date'] !== '' ? $receiptDetails['date'] : '—') ?></p>
<?php /*                     Closes the payment date field. */ ?>
                    </div>
<?php /*                 Closes the workflow record row. */ ?>
                </div>
<?php /*                 Checks whether all three receipt details are still empty. */ ?>
                <?php if ($receiptDetails['receiptNumber'] === '' && $receiptDetails['amount'] === '' && $receiptDetails['date'] === ''): ?>
<?php /*                 Opens the hint paragraph shown when no receipt was sent. */ ?>
                <p class="field-hint" style="margin:0;">
<?php /*                     Explains that the receipt number, amount and date come with the receipt. */ ?>
                    The student has not sent an official receipt for this stage yet. The receipt number, the amount
<?php /*                     Continues the explanation about the missing receipt. */ ?>
                    and the payment date come with the receipt.
<?php /*                 Closes the missing receipt hint. */ ?>
                </p>
<?php /*                 Ends the empty receipt check. */ ?>
                <?php endif; ?>
<?php /*                 Opens a hint paragraph about where the other records are handled. */ ?>
                <p class="field-hint" style="margin-top:.75rem;">
<?php /*                     Lists endorsement, payment, readiness and result as handled elsewhere. */ ?>
                    The student-submitted Graduate School endorsement, payment record, readiness mark and presentation
<?php /*                     Points the coordinator to the Milestone Tracker. */ ?>
                    result are handled in the <strong>Milestone Tracker</strong> and the
<?php /*                     Points the coordinator to the Schedule Presentation section. */ ?>
                    <strong>Schedule Presentation</strong> section of this stage.
<?php /*                 Closes the second hint paragraph. */ ?>
                </p>
<?php /*             Closes the workflow records card body. */ ?>
            </div>
<?php /*         Closes the workflow records card. */ ?>
        </div>

<?php /*         Opens the card used to schedule the presentation. */ ?>
        <div class="card" id="scheduleSection-<?= htmlspecialchars($key) ?>">
<?php /*             Card header title for scheduling with the stage name. */ ?>
            <div class="card-header"><h3><i class="fas fa-calendar-plus"></i> Schedule Presentation — <?= htmlspecialchars($stageName) ?></h3></div>
<?php /*             Opens the body of the scheduling card. */ ?>
            <div class="card-body">
<?php /*                 Checks that a schedule exists and has a date. */ ?>
                <?php if ($panel['schedule'] && !empty($panel['schedule']['date'])): ?>
<?php /*                 Sets $scheduleStatus to confirmed, otherwise pending. */ ?>
                <?php $scheduleStatus = ($panel['schedule']['status'] ?? '') === 'confirmed' ? 'confirmed' : 'pending'; ?>
<?php /*                 Opens an alert coloured green when confirmed and yellow otherwise. */ ?>
                <div class="alert <?= $scheduleStatus === 'confirmed' ? 'alert-success' : 'alert-warning' ?>" style="margin-bottom:1rem;">
<?php /*                     Shows a check or hourglass icon that matches the schedule status. */ ?>
                    <i class="fas <?= $scheduleStatus === 'confirmed' ? 'fa-check-circle' : 'fa-hourglass-half' ?>"></i>
<?php /*                     Opens the wrapper for the schedule alert text. */ ?>
                    <div>
<?php /*                         Shows the scheduled date, time and venue when they are set. */ ?>
                        Presentation scheduled: <strong><?= htmlspecialchars((string) $panel['schedule']['date']) ?><?= !empty($panel['schedule']['time']) ? ' — ' . htmlspecialchars((string) $panel['schedule']['time']) : '' ?></strong><?= !empty($panel['schedule']['venue']) ? ', ' . htmlspecialchars((string) $panel['schedule']['venue']) : '' ?>.
<?php /*                         Shows whether the schedule is confirmed or pending confirmation. */ ?>
                        Schedule status: <strong><?= $scheduleStatus === 'confirmed' ? 'Confirmed' : 'Pending Confirmation' ?></strong>
<?php /*                         Explains how the Presentation Scheduled milestone is treated. */ ?>
                        — the <strong>Presentation Scheduled</strong> milestone is
<?php /*                         Says Verified when confirmed, or Pending until confirmed. */ ?>
                        <?= $scheduleStatus === 'confirmed' ? '<strong>Verified</strong>' : '<strong>Pending</strong> until the schedule is confirmed' ?>
<?php /*                         Notes that the milestone state shows in both trackers. */ ?>
                        in both the Coordinator and Student trackers.
<?php /*                     Closes the schedule alert text wrapper. */ ?>
                    </div>
<?php /*                 Closes the schedule alert. */ ?>
                </div>
<?php /*                 Ends the check for an existing schedule. */ ?>
                <?php endif; ?>
<?php /*                 Checks whether all requirements are complete, unlocking scheduling. */ ?>
                <?php if ($panel['unlocked']): ?>
<?php /*                 Opens the success alert shown when scheduling is unlocked. */ ?>
                <div class="alert alert-success" style="margin-bottom:1rem;">
<?php /*                     Shows an unlock icon in the success alert. */ ?>
                    <i class="fas fa-unlock"></i>
<?php /*                     Tells the coordinator the named student can now be scheduled. */ ?>
                    <div>All required documents and requirements are complete — <strong><?= htmlspecialchars($app['student'] ?? '') ?></strong> can proceed to have the presentation scheduled.</div>
<?php /*                 Closes the unlocked success alert. */ ?>
                </div>
<?php /*                 Opens the hidden Ready to Schedule checklist panel. */ ?>
                <div class="req-checklist" id="scheduleNoticePanel-<?= htmlspecialchars($key) ?>" hidden>
<?php /*                     Heading for the Ready to Schedule panel. */ ?>
                    <h4>Ready to Schedule</h4>
<?php /*                     Opens the paragraph that explains readiness. */ ?>
                    <p style="margin:0;font-size:.83rem;color:var(--gray-700);">
<?php /*                         Names the track whose requirements are complete. */ ?>
                        Every requirement of the <strong><?= htmlspecialchars($trackLabel) ?></strong> track for
<?php /*                         Names the stage whose requirements are complete. */ ?>
                        <strong><?= htmlspecialchars($stageName) ?></strong> is complete.
<?php /*                     Closes the readiness paragraph. */ ?>
                    </p>
<?php /*                 Closes the Ready to Schedule explanation panel. */ ?>
                </div>
<?php /*                 Starts the branch used when requirements are still missing. */ ?>
                <?php else: ?>
<?php /*                 Opens the warning alert shown when scheduling is locked. */ ?>
                <div class="alert alert-warning" style="margin-bottom:1rem;">
<?php /*                     Shows a lock icon in the warning alert. */ ?>
                    <i class="fas fa-lock"></i>
<?php /*                     Shows how many requirements are still missing for this stage. */ ?>
                    <div>Scheduling is locked. <strong><?= count($panel['missing']) ?></strong> requirement<?= count($panel['missing']) === 1 ? ' is' : 's are' ?>
<?php /*                     Names the track the missing requirements belong to. */ ?>
                    still missing for the <strong><?= htmlspecialchars($trackLabel) ?></strong> track.</div>
<?php /*                 Closes the locked scheduling alert. */ ?>
                </div>
<?php /*                 Opens the hidden panel that lists missing requirements. */ ?>
                <div class="req-checklist" id="missingPanel-<?= htmlspecialchars($key) ?>" hidden>
<?php /*                     Heading for the missing requirements panel. */ ?>
                    <h4>Missing Documents and Requirements</h4>
<?php /*                     Opens the list of missing documents and requirements. */ ?>
                    <ul style="margin:0;padding-left:1.1rem;font-size:.83rem;color:var(--gray-700);">
<?php /*                         Loops over each missing item as $missingItem. */ ?>
                        <?php foreach ($panel['missing'] as $missingItem): ?>
<?php /*                         Opens one list item for a missing requirement. */ ?>
                        <li style="margin-bottom:.35rem;">
<?php /*                             Prints the missing requirement label in bold. */ ?>
                            <strong><?= htmlspecialchars($missingItem['label']) ?></strong>
<?php /*                             Prints the reason why the requirement is still missing. */ ?>
                            <span style="color:var(--gray-500);"> — <?= htmlspecialchars($missingItem['why']) ?></span>
<?php /*                         Closes the missing requirement list item. */ ?>
                        </li>
<?php /*                         Ends the loop over missing requirements. */ ?>
                        <?php endforeach; ?>
<?php /*                     Closes the missing requirements list. */ ?>
                    </ul>
<?php /*                 Closes the missing requirements panel. */ ?>
                </div>
<?php /*                 Ends the unlocked or locked scheduling branch. */ ?>
                <?php endif; ?>
<?php /*             Closes the scheduling card body. */ ?>
            </div>
<?php /*         Closes the scheduling card. */ ?>
        </div>

<?php /*         Opens the action row under the scheduling card. */ ?>
        <div class="form-actions stage-panel-actions">
<?php /*             Checks that scheduling is unlocked and a stage application exists. */ ?>
            <?php if ($panel['unlocked'] && $stageApp): ?>
<?php /*             Schedule Presentation link opens the add schedule page for this application. */ ?>
            <a href="<?= url('coordinator/schedule/add.php?app=' . (int) $stageApp['id']) ?>" class="btn btn-outline" id="schedulePresentationBtn-<?= htmlspecialchars($key) ?>"><i class="fas fa-calendar-plus"></i> Schedule Presentation</a>
<?php /*             Starts the branch used when scheduling is still locked. */ ?>
            <?php else: ?>
<?php /*             Disabled button explains that missing requirements must be completed first. */ ?>
            <button type="button" class="btn btn-outline" disabled title="Complete the missing requirements before scheduling the presentation"><i class="fas fa-calendar-plus"></i> Schedule Presentation</button>
<?php /*             Ends the scheduler link or disabled button branch. */ ?>
            <?php endif; ?>

<?php /*             Lock or unlock button toggles the checklist panel for this stage. */ ?>
            <button type="button" class="btn btn-outline" id="scheduleLockBtn-<?= htmlspecialchars($key) ?>" aria-expanded="false"
<?php /*                     Points aria-controls at the ready panel or the missing panel. */ ?>
                    aria-controls="<?= $panel['unlocked'] ? 'scheduleNoticePanel-' . htmlspecialchars($key) : 'missingPanel-' . htmlspecialchars($key) ?>">
<?php /*                 Shows an unlock or lock icon with the Unlocked or Locked label. */ ?>
                <i class="fas <?= $panel['unlocked'] ? 'fa-unlock' : 'fa-lock' ?>"></i> <?= $panel['unlocked'] ? 'Unlocked' : 'Locked' ?>
<?php /*             Closes the lock toggle button. */ ?>
            </button>

<?php /*             Opens the field that holds the presentation result selector. */ ?>
            <div class="form-field" style="margin:0;">
<?php /*                 Opens a PHP block that explains when the result can be edited. */ ?>
                <?php
                // Notes that the result opens only after the presentation milestone.
                // The result opens only for workflows that reached
                // Names the Presentation Conducted and Result Recorded milestone.
                // 'Presentation Conducted & Result Recorded' - the schedule is
                // Notes the schedule must be confirmed and earlier milestones done.
                // confirmed and every earlier milestone is done. A result that
                // Notes that an existing result stays editable and decides itself.
                // is already on record stays editable and decides itself. The
                // Notes the portal presentation time is only a hint.
                // presentation taking place in the portal is a hint; the
                // Notes the coordinator knows whether the presentation happened.
                // coordinator knows whether it already happened.
                // Disables the result when there is no application or the milestone is not reached.
                $resultDisabled = empty($stageApp) || empty($panel['resultReached']);
                // Closes the PHP block that decided whether the result is disabled.
                ?>
<?php /*                 Opens a PHP comment describing the bordered result box below. */ ?>
                <?php /*
                 * One bordered box, styled like the outline buttons beside it.
                 * The label sits inside the box, next to the result; the
                 * select itself is borderless, so the box is the button look.
                 */ ?>
<?php /*                 Opens the bordered box around the result label and selector. */ ?>
                <div class="stage-result-box<?= $resultDisabled ? ' is-disabled' : '' ?>">
<?php /*                     Shows the Presentation Result label inside the box. */ ?>
                    <span class="stage-result-label">Presentation Result</span>
<?php /*                     Result dropdown posts the chosen result for this stage. */ ?>
                    <select name="stage_data[<?= htmlspecialchars($key) ?>][result]" class="stage-result-select" <?= $resultDisabled ? 'disabled' : '' ?>
<?php /*                             Starts the tooltip text for the result dropdown. */ ?>
                            title="<?= $resultDisabled
                                // Checks whether the stage has no application.
                                ? (empty($stageApp)
                                    // Tooltip saying no application exists for this stage yet.
                                    ? 'The student has no application for this stage yet'
                                    // Tooltip saying the result unlocks at the result milestone.
                                    : 'Enabled when the milestone reaches Presentation Conducted & Result Recorded')
                                // Tooltip prompting the coordinator to record the result.
                                : 'Record the result of the presentation' ?>">
<?php /*                         Not yet presented option is selected when no result is saved. */ ?>
                        <option value="" <?= empty($panel['result']) ? 'selected' : '' ?>>Not yet presented</option>
<?php /*                         Approved option is selected when the saved result is approved. */ ?>
                        <option value="approved" <?= $panel['result'] === 'approved' ? 'selected' : '' ?>>Approved</option>
<?php /*                         Requires Revision option is selected for a revision result. */ ?>
                        <option value="requires_revision" <?= $panel['result'] === 'requires_revision' ? 'selected' : '' ?>>Requires Revision</option>
<?php /*                     Closes the result dropdown. */ ?>
                    </select>
<?php /*                 Closes the result box. */ ?>
                </div>
<?php /*                 Checks that the result is enabled but no schedule exists. */ ?>
                <?php if (!$resultDisabled && empty($panel['schedule'])): ?>
<?php /*                 Opens the hint paragraph about recording a result without a schedule. */ ?>
                <p class="field-hint" style="margin:.35rem 0 0;">
<?php /*                     Shows an info icon in the hint. */ ?>
                    <i class="fas fa-info-circle" style="margin-right:5px;"></i>
<?php /*                     Tells the coordinator the result can still be recorded if it happened. */ ?>
                    The presentation is not scheduled yet. You can still record the result if it already took place.
<?php /*                 Closes the no schedule hint paragraph. */ ?>
                </p>
<?php /*                 Ends the no schedule hint check. */ ?>
                <?php endif; ?>
<?php /*             Closes the presentation result field. */ ?>
            </div>
<?php /*         Closes the stage action row. */ ?>
        </div>
<?php /*     Closes the stage panel section. */ ?>
    </section>
<?php /*     Ends the loop over all workflow stages. */ ?>
    <?php endforeach; ?>

<?php /*     Opens the final action row that is hidden when printing. */ ?>
    <div class="form-actions no-print">
<?php /*         Save Process button submits every change on the page. */ ?>
        <button type="submit" class="btn btn-primary" id="saveProcessBtn"><i class="fas fa-save"></i> Save Process</button>
<?php /*         Cancel link returns to the applications management page. */ ?>
        <a href="<?= url('coordinator/applications/manage.php') ?>" class="btn btn-outline" id="cancelProcessBtn">Cancel</a>
<?php /*     Closes the final action row. */ ?>
    </div>
<?php /* Closes the process form. */ ?>
</form>

<?php /* Ends the main display check before the scripts start. */ ?>
<?php endif; ?>

<?php /* Starts the script block for the stage slider and approval actions. */ ?>
<script>
<?php /* Wraps all page scripts in an IIFE to keep variables private. */ ?>
(function () {
<?php /*     Gets the process form element. */ ?>
    const form = document.getElementById('processForm');
<?php /*     Stops the script when the form is not on the page. */ ?>
    if (!form) return;

<?php /*     Gets the stage slider container. */ ?>
    const slider = document.getElementById('stageSlider');
<?php /*     Gets the track element that slides the stage cards. */ ?>
    const track = document.getElementById('stageSliderTrack');
<?php /*     Collects all stage cards into an array. */ ?>
    const cards = [...document.querySelectorAll('.stage-slider-card')];
<?php /*     Collects all stage panels into an array. */ ?>
    const panels = [...document.querySelectorAll('.stage-panel')];
<?php /*     Gets the hidden field that stores the selected stage. */ ?>
    const selectedField = document.getElementById('selectedStageField');
<?php /*     Gets the hidden field that stores a pending approval. */ ?>
    const approveField = document.getElementById('approveStageField');
<?php /*     Gets the hidden field that stores a pending disapproval. */ ?>
    const disapproveField = document.getElementById('disapproveStageField');
<?php /*     Gets the container where in page notices are shown. */ ?>
    const noticeBox = document.getElementById('pageNotifications');
<?php /*     Sets the starting index from the stage selected by PHP. */ ?>
    let current = <?= (int) array_search($selectedStage, $stageKeys, true) ?> >= 0 ? <?= (int) array_search($selectedStage, $stageKeys, true) ?> : 0;

<?php /*     Starts a block comment about the in page notification helper. */ ?>
    /* ------------------------------------------------------------------
<?php /*      Names the notification section of the script. */ ?>
     * In-system notifications
<?php /*      Blank comment line separating the heading from the explanation. */ ?>
     *
<?php /*      Notes that notices are short messages inside the page. */ ?>
     * A short message inside the page, never a browser pop-up. Temporary
<?php /*      Notes that temporary notices dismiss themselves. */ ?>
     * notices dismiss themselves; a notice with actions stays until the
<?php /*      Notes that a notice with actions waits for the coordinator. */ ?>
     * coordinator answers.
<?php /*      Closes the notification comment block. */ ?>
     * ------------------------------------------------------------------ */
<?php /*     Defines notify, which shows an in page message with optional actions. */ ?>
    function notify(message, type, options) {
<?php /*         Stops when there is no notice container on the page. */ ?>
        if (!noticeBox) return null;
<?php /*         Uses the passed options, or an empty object. */ ?>
        const settings = options || {};
<?php /*         Creates the notice element. */ ?>
        const notice = document.createElement('div');
<?php /*         Sets the notice class from its type, defaulting to info. */ ?>
        notice.className = 'page-notice is-' + (type || 'info');
<?php /*         Creates the span that holds the notice text. */ ?>
        const text = document.createElement('span');
<?php /*         Sets the class used to style the notice text. */ ?>
        text.className = 'page-notice-text';
<?php /*         Puts the message text into the span. */ ?>
        text.textContent = message;
<?php /*         Adds the text span to the notice. */ ?>
        notice.appendChild(text);

<?php /*         Checks whether the notice has action buttons. */ ?>
        if (settings.actions && settings.actions.length) {
<?php /*             Creates the container for the action buttons. */ ?>
            const actions = document.createElement('div');
<?php /*             Sets the class for the action row. */ ?>
            actions.className = 'page-notice-actions';
<?php /*             Loops over each requested action. */ ?>
            settings.actions.forEach(function (action) {
<?php /*                 Creates a button for the action. */ ?>
                const button = document.createElement('button');
<?php /*                 Sets the button type so it does not submit the form. */ ?>
                button.type = 'button';
<?php /*                 Sets the button class from the action variant. */ ?>
                button.className = 'btn ' + (action.variant || 'btn-outline');
<?php /*                 Sets the button label. */ ?>
                button.textContent = action.label;
<?php /*                 Attaches the action click handler. */ ?>
                button.addEventListener('click', action.onClick);
<?php /*                 Adds the button to the action row. */ ?>
                actions.appendChild(button);
<?php /*             Ends the action loop. */ ?>
            });
<?php /*             Adds the action row to the notice. */ ?>
            notice.appendChild(actions);
<?php /*         Starts the branch used when there are no actions. */ ?>
        } else {
<?php /*             Creates the dismiss button. */ ?>
            const close = document.createElement('button');
<?php /*             Sets the dismiss button type. */ ?>
            close.type = 'button';
<?php /*             Sets the dismiss button class. */ ?>
            close.className = 'btn btn-outline';
<?php /*             Gives the dismiss button an accessible label. */ ?>
            close.setAttribute('aria-label', 'Dismiss');
<?php /*             Shows a multiplication sign as the close icon. */ ?>
            close.innerHTML = '&times;';
<?php /*             Removes the notice when the dismiss button is clicked. */ ?>
            close.addEventListener('click', function () { notice.remove(); });
<?php /*             Adds the dismiss button to the notice. */ ?>
            notice.appendChild(close);
<?php /*         Ends the else branch that added the dismiss button. */ ?>
        }

<?php /*         Adds the notice to the page notice container. */ ?>
        noticeBox.appendChild(notice);
<?php /*         Checks whether the notice should stay on screen. */ ?>
        if (!settings.sticky) {
<?php /*             Removes the notice automatically after six seconds. */ ?>
            setTimeout(function () { notice.remove(); }, 6000);
<?php /*         Ends the auto dismiss check. */ ?>
        }
<?php /*         Returns the notice so callers can remove it later. */ ?>
        return notice;
<?php /*     Closes the notify function. */ ?>
    }

<?php /*     Starts a block comment about the approval button state. */ ?>
    /* ------------------------------------------------------------------
<?php /*      Notes that the buttons follow the saved state and pending decision. */ ?>
     * The approval buttons follow the saved state and the pending decision:
<?php /*      Notes that an unapproved stage can be approved. */ ?>
     * a stage that is not approved yet can be approved, a stage that is
<?php /*      Notes that an approved stage can be revoked. */ ?>
     * approved can be revoked, and a pending decision can still be changed.
<?php /*      Closes the approval comment block. */ ?>
     * ------------------------------------------------------------------ */
<?php /*     Defines refreshApprovalButtons, which updates every stage button. */ ?>
    function refreshApprovalButtons() {
<?php /*         Loops over every stage panel. */ ?>
        document.querySelectorAll('.stage-panel').forEach(function (panel) {
<?php /*             Reads the stage key from the panel data attribute. */ ?>
            const stageKey = panel.dataset.stage;
<?php /*             Reads whether the stage is saved as approved. */ ?>
            const savedApproved = panel.dataset.savedApproved === '1';
<?php /*             Starts working out whether an approval is pending for this stage. */ ?>
            const pending = approveField && approveField.value === stageKey
<?php /*                 Uses approve when the approve field holds this stage key. */ ?>
                ? 'approve'
<?php /*                 Otherwise uses disapprove when the disapprove field matches. */ ?>
                : (disapproveField && disapproveField.value === stageKey ? 'disapprove' : '');
<?php /*             Finds the approve button inside the panel. */ ?>
            const approveBtn = panel.querySelector('[data-approve-stage]');
<?php /*             Finds the disapprove button inside the panel. */ ?>
            const disapproveBtn = panel.querySelector('[data-disapprove-stage]');
<?php /*             Checks whether this panel has an approve button. */ ?>
            if (approveBtn) {
<?php /*                 Reads whether the panel is ready to be approved. */ ?>
                const ready = approveBtn.dataset.ready === '1';
<?php /*                 Notes that an approved stage has nothing left to approve. */ ?>
                // Approved already: there is nothing left to approve. Otherwise
<?php /*                 Notes the stage must be complete before it can be approved. */ ?>
                // the stage has to be complete before it can be approved.
<?php /*                 Disables approve when already approved or not ready. */ ?>
                approveBtn.disabled = savedApproved || !ready;
<?php /*                 Highlights the approve button when its decision is pending. */ ?>
                approveBtn.classList.toggle('is-pending', pending === 'approve');
<?php /*             Ends the approve button check. */ ?>
            }
<?php /*             Checks whether this panel has a disapprove button. */ ?>
            if (disapproveBtn) {
<?php /*                 Disables disapprove unless approved or already pending. */ ?>
                disapproveBtn.disabled = !savedApproved && pending !== 'disapprove';
<?php /*                 Highlights the disapprove button when its decision is pending. */ ?>
                disapproveBtn.classList.toggle('is-pending', pending === 'disapprove');
<?php /*             Ends the disapprove button check. */ ?>
            }
<?php /*         Ends the loop over stage panels. */ ?>
        });
<?php /*     Closes refreshApprovalButtons. */ ?>
    }

<?php /*     Notes that decisions stay pending until Save Process is used. */ ?>
    // Both decisions are recorded as pending and are applied by Save Process.
<?php /*     Defines recordDecision, which stores an approval or disapproval. */ ?>
    function recordDecision(stageKey, decision) {
<?php /*         Stores the stage key in the approve field when approving. */ ?>
        if (approveField) approveField.value = decision === 'approve' ? stageKey : '';
<?php /*         Stores the stage key in the disapprove field when disapproving. */ ?>
        if (disapproveField) disapproveField.value = decision === 'disapprove' ? stageKey : '';

<?php /*         Finds the card index of the stage that was just decided. */ ?>
        const index = cards.findIndex(function (c) { return c.dataset.stage === stageKey; });
<?php /*         Gets the card of the next stage, if it exists. */ ?>
        const nextCard = index >= 0 ? cards[index + 1] : null;
<?php /*         Checks whether a next stage card exists. */ ?>
        if (nextCard) {
<?php /*             Finds the badge shown on the next stage card. */ ?>
            const badge = nextCard.querySelector('.stage-slider-badge');
<?php /*             Checks whether the badge was found. */ ?>
            if (badge) {
<?php /*                 Sets the badge text to Pending Approval or Pending Revoke. */ ?>
                badge.textContent = decision === 'approve' ? 'Pending Approval' : 'Pending Revoke';
<?php /*                 Sets the badge colour for the pending decision. */ ?>
                badge.className = 'status-badge ' + (decision === 'approve' ? 'status-review' : 'status-revision') + ' stage-slider-badge';
<?php /*             Ends the badge check. */ ?>
            }
<?php /*             Locks the next stage card when the decision is disapproval. */ ?>
            nextCard.classList.toggle('is-locked', decision === 'disapprove');
<?php /*             Marks the next stage card approved when the decision is approval. */ ?>
            nextCard.classList.toggle('is-approved', decision === 'approve');
<?php /*         Ends the next card check. */ ?>
        }
<?php /*         Refreshes the approval buttons after the pending change. */ ?>
        refreshApprovalButtons();

<?php /*         Shows a notice that the decision is waiting to be saved. */ ?>
        notify(
<?php /*             Chooses the message based on the decision. */ ?>
            decision === 'approve'
<?php /*                 Message telling the coordinator to save the approval. */ ?>
                ? 'Stage approval is pending. Click Save Process to apply your changes.'
<?php /*                 Message telling the coordinator to save the disapproval. */ ?>
                : 'Stage disapproval is pending. Click Save Process to apply your changes.',
<?php /*             Uses the info style for the notice. */ ?>
            'info',
<?php /*             Adds an Undo action to the notice. */ ?>
            { actions: [
<?php /*                 Defines what the Undo action does when clicked. */ ?>
                { label: 'Undo', variant: 'btn-outline', onClick: function () {
<?php /*                     Clears the pending approval field. */ ?>
                    if (approveField) approveField.value = '';
<?php /*                     Clears the pending disapproval field. */ ?>
                    if (disapproveField) disapproveField.value = '';
<?php /*                     Finds the stage card that was changed. */ ?>
                    const card = document.querySelector('.stage-slider-card[data-stage="' + stageKey + '"]');
<?php /*                     Finds the badge on that card. */ ?>
                    const badge = card ? card.querySelector('.stage-slider-badge') : null;
<?php /*                     Checks whether the badge exists. */ ?>
                    if (badge) {
<?php /*                         Restores the badge text to Locked or Approved. */ ?>
                        badge.textContent = card.classList.contains('is-locked') ? 'Locked' : 'Approved';
<?php /*                         Restores the badge colour to match the stage state. */ ?>
                        badge.className = 'status-badge ' + (card.classList.contains('is-locked') ? 'status-not-submitted' : 'status-submitted') + ' stage-slider-badge';
<?php /*                     Ends the badge check. */ ?>
                    }
<?php /*                     Refreshes the approval buttons after the undo. */ ?>
                    refreshApprovalButtons();
<?php /*                     Removes the pending notice. */ ?>
                    notice.remove();
<?php /*                 Ends the Undo click handler. */ ?>
                } }
<?php /*             Ends the actions list passed to notify. */ ?>
            ] }
<?php /*         Ends the notify call. */ ?>
        );
<?php /*     Closes recordDecision. */ ?>
    }

<?php /*     Adds a click handler to every approve button. */ ?>
    document.querySelectorAll('[data-approve-stage]').forEach(function (button) {
<?php /*         Handles the click on an approve button. */ ?>
        button.addEventListener('click', function () {
<?php /*             Checks whether the stage is allowed to be approved. */ ?>
            if (button.dataset.ready !== '1') {
<?php /*                 Warns the coordinator that required items are still incomplete. */ ?>
                notify('This stage cannot be approved yet. Please complete the required items first.', 'warning');
<?php /*                 Stops without recording a decision. */ ?>
                return;
<?php /*             Ends the readiness check. */ ?>
            }
<?php /*             Records the approval as a pending decision. */ ?>
            recordDecision(button.dataset.approveStage, 'approve');
<?php /*         Ends the approve click handler. */ ?>
        });
<?php /*     Ends the loop over approve buttons. */ ?>
    });

<?php /*     Adds a click handler to every disapprove button. */ ?>
    document.querySelectorAll('[data-disapprove-stage]').forEach(function (button) {
<?php /*         Handles the click on a disapprove button. */ ?>
        button.addEventListener('click', function () {
<?php /*             Records the disapproval as a pending decision. */ ?>
            recordDecision(button.dataset.disapproveStage, 'disapprove');
<?php /*         Ends the disapprove click handler. */ ?>
        });
<?php /*     Ends the loop over disapprove buttons. */ ?>
    });

<?php /*     Notes that each control remembers its original disabled state. */ ?>
    // Remember the original disabled state of every control so a panel can be
<?php /*     Notes this prevents re enabling something disabled on purpose. */ ?>
    // shown again without re-enabling something that was disabled on purpose.
<?php /*     Loops over every stage panel. */ ?>
    panels.forEach(function (panel) {
<?php /*         Loops over every form control inside the panel. */ ?>
        panel.querySelectorAll('input, select, textarea, button').forEach(function (control) {
<?php /*             Saves whether the control was originally disabled. */ ?>
            control.dataset.origDisabled = control.disabled ? '1' : '0';
<?php /*         Ends the control loop. */ ?>
        });
<?php /*     Ends the panel loop. */ ?>
    });

<?php /*     Defines slideTo, which shows one stage card and its panel. */ ?>
    function slideTo(index) {
<?php /*         Stops when there are no stage cards. */ ?>
        if (!cards.length) return;
<?php /*         Keeps the index inside the valid card range. */ ?>
        current = Math.max(0, Math.min(cards.length - 1, index));
<?php /*         Gets the card at the chosen index. */ ?>
        const card = cards[current];
<?php /*         Reads the stage key of that card. */ ?>
        const stageKey = card.dataset.stage;

<?php /*         Notes that the track is centred when everything fits. */ ?>
        // The track is centred when everything fits; when it is wider than the
<?php /*         Notes that the track slides so the selected stage stays visible. */ ?>
        // window it slides so the selected stage stays in view.
<?php /*         Gets the slider viewport element. */ ?>
        const viewport = document.querySelector('.stage-slider-viewport');
<?php /*         Checks that both the track and viewport exist. */ ?>
        if (track && viewport) {
<?php /*             Calculates how far the track can scroll. */ ?>
            const maxOffset = Math.max(0, track.scrollWidth - viewport.clientWidth);
<?php /*             Finds the horizontal centre of the selected card. */ ?>
            const cardCenter = card.offsetLeft + card.offsetWidth / 2;
<?php /*             Works out the offset that centres the card in the viewport. */ ?>
            let offset = cardCenter - (viewport.clientWidth / 2);
<?php /*             Keeps the offset between the maximum scroll and zero. */ ?>
            offset = Math.max(-maxOffset, Math.min(0, offset));
<?php /*             Moves the track with a CSS translate. */ ?>
            track.style.transform = 'translateX(' + offset + 'px)';
<?php /*         Ends the track centring block. */ ?>
        }
<?php /*         Loops over every stage card. */ ?>
        cards.forEach(function (c, i) {
<?php /*             Marks only the current card as selected. */ ?>
            c.classList.toggle('is-selected', i === current);
<?php /*             Updates aria pressed to show which card is active. */ ?>
            c.setAttribute('aria-pressed', i === current ? 'true' : 'false');
<?php /*         Ends the card loop. */ ?>
        });
<?php /*         Loops over every stage panel. */ ?>
        panels.forEach(function (panel) {
<?php /*             Checks whether this panel belongs to the selected stage. */ ?>
            const active = panel.dataset.stage === stageKey;
<?php /*             Hides every panel except the selected one. */ ?>
            panel.hidden = !active;
<?php /*             Loops over the controls inside the panel. */ ?>
            panel.querySelectorAll('input, select, textarea, button').forEach(function (control) {
<?php /*                 Enables controls only in the active panel and only if originally enabled. */ ?>
                control.disabled = !active || control.dataset.origDisabled === '1';
<?php /*             Ends the panel control loop. */ ?>
            });
<?php /*         Ends the panel loop. */ ?>
        });
<?php /*         Stores the selected stage key in the hidden field. */ ?>
        if (selectedField) selectedField.value = stageKey;

<?php /*         Gets the previous stage button. */ ?>
        const prevBtn = document.getElementById('stageSliderPrev');
<?php /*         Gets the next stage button. */ ?>
        const nextBtn = document.getElementById('stageSliderNext');
<?php /*         Disables the previous button on the first stage. */ ?>
        if (prevBtn) prevBtn.disabled = current === 0;
<?php /*         Disables the next button on the last stage. */ ?>
        if (nextBtn) nextBtn.disabled = current === cards.length - 1;
<?php /*     Closes slideTo. */ ?>
    }

<?php /*     Adds a click handler to each stage card. */ ?>
    cards.forEach(function (card, index) {
<?php /*         Slides to the card that was clicked. */ ?>
        card.addEventListener('click', function () { slideTo(index); });
<?php /*     Ends the card click loop. */ ?>
    });
<?php /*     Previous button slides one stage back. */ ?>
    document.getElementById('stageSliderPrev')?.addEventListener('click', function () { slideTo(current - 1); });
<?php /*     Next button slides one stage forward. */ ?>
    document.getElementById('stageSliderNext')?.addEventListener('click', function () { slideTo(current + 1); });

<?php /*     Notes that the lock button shows what is still missing. */ ?>
    // The locked / unlocked button of a stage shows what is still missing.
<?php /*     Adds a click handler to every stage lock button. */ ?>
    document.querySelectorAll('[id^="scheduleLockBtn-"]').forEach(function (button) {
<?php /*         Handles the click on a lock button. */ ?>
        button.addEventListener('click', function () {
<?php /*             Reads the id of the panel the button controls. */ ?>
            const panelId = button.getAttribute('aria-controls');
<?php /*             Gets that panel element. */ ?>
            const panel = panelId ? document.getElementById(panelId) : null;
<?php /*             Stops when the panel is missing. */ ?>
            if (!panel) return;
<?php /*             Checks whether the panel is currently open. */ ?>
            const open = button.getAttribute('aria-expanded') === 'true';
<?php /*             Runs when the panel is open. */ ?>
            if (open) {
<?php /*                 Hides the panel. */ ?>
                panel.setAttribute('hidden', '');
<?php /*             Runs when the panel is closed. */ ?>
            } else {
<?php /*                 Shows the panel. */ ?>
                panel.removeAttribute('hidden');
<?php /*             Ends the open or closed check. */ ?>
            }
<?php /*             Updates aria expanded to match the new state. */ ?>
            button.setAttribute('aria-expanded', open ? 'false' : 'true');
<?php /*         Ends the lock button click handler. */ ?>
        });
<?php /*     Ends the lock button loop. */ ?>
    });

<?php /*     Shows the starting stage when the page loads. */ ?>
    slideTo(current);
<?php /*     Sets the correct disabled state of the approval buttons at load. */ ?>
    refreshApprovalButtons();

<?php /*     Starts a block comment about the Save Process button. */ ?>
    /* ------------------------------------------------------------------
<?php /*      Names the save behaviour section of the script. */ ?>
     * Save Process
<?php /*      Blank comment line separating the heading from the explanation. */ ?>
     *
<?php /*      Notes that one button saves every pending change. */ ?>
     * One button saves every pending change of the page. It is guarded
<?php /*      Notes the guard that stops the same change being sent twice. */ ?>
     * against a double click so the same change is never sent twice.
<?php /*      Closes the Save Process comment block. */ ?>
     * ------------------------------------------------------------------ */
<?php /*     Gets the Save Process button. */ ?>
    const saveBtn = document.getElementById('saveProcessBtn');
<?php /*     Checks whether the save button exists. */ ?>
    if (saveBtn) {
<?php /*         Listens for the form submit. */ ?>
        form.addEventListener('submit', function () {
<?php /*             Ignores a second submit while a save is already running. */ ?>
            if (form.dataset.saving === '1') return;
<?php /*             Marks the form as saving. */ ?>
            form.dataset.saving = '1';
<?php /*             Disables the save button during the save. */ ?>
            saveBtn.disabled = true;
<?php /*             Shows a spinner and Saving text on the button. */ ?>
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
<?php /*         Ends the submit listener. */ ?>
        });
<?php /*     Ends the save button check. */ ?>
    }
<?php /* Runs and closes the page script wrapper. */ ?>
})();
<?php /* Closes the script block. */ ?>
</script>

<?php /* Loads the shared footer that closes the page layout. */ ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
