<?php
/* CSITE configuration module: Document types, open upload stages, submission requirements, document actions, and missing requirements. */

/**
 * The document types a student may pick for a stage of their track.
 *
 * The paper and the adviser endorsement come from the stage definition of the
 * track itself, so a thesis student, a capstone student and a seminar-paper
 * student each get their own wording. The revision, the receipt and the
 * Graduate School endorsement are the same for every track.
 */
// Lists the document types a student may pick for a stage.
function documentTypesForStage(array $stage, string $track, string $stageKey): array {
    // Reads the paper label, falling back to the stage short label.
    $paperLabel = $stage['documents'][0]['label'] ?? ($stage['shortLabel'] ?? 'Paper') . ' Paper';
    // Starts the type list with the paper.
    $types = [$paperLabel];
    // Checks whether the stage defines an adviser form label.
    if (!empty($stage['adviserEndorsementForm']['label'])) {
        // Adds the adviser endorsement form type.
        $types[] = $stage['adviserEndorsementForm']['label'];
    // Closes the adviser form check.
    }
    // Checks whether the stage defines a Graduate School form label.
    if (!empty($stage['gradSchoolEndorsementForm']['label'])) {
        // Adds the Graduate School endorsement type.
        $types[] = $stage['gradSchoolEndorsementForm']['label'];
    // Closes the Graduate School form check.
    }
    // Adds the official receipt type, which every track shares.
    $types[] = 'Official Receipt / Payment Proof';
    // Returns the unique, non-empty type list.
    return array_values(array_unique(array_filter($types)));
// Ends documentTypesForStage.
}

/**
 * The document types the student may upload now.
 *
 * The Milestone Tracker is the source of truth. The milestones of a stage are
 * followed in the order of the workflow: the first one that is not finished is
 * the work of the student now, and only its document type is open. Every type
 * behind it waits, until the milestones before it are done, so a document that
 * the coordinator sent back keeps the later documents locked. A milestone that
 * was sent back is open again, so the student can upload a replacement.
 *
 * The Document Type list of the Application page, the Upload buttons of the
 * Dashboard and the check of an upload all use this one rule, so they can
 * never disagree about what the student is allowed to send.
 *
 * @param  array<int, array<string, mixed>>  $milestoneSteps
 * @return array<int, string> the document types that are open
 */
// Lists the document types the student may upload now.
function openDocumentTypes(array $milestoneSteps): array {
    // Starts with no open document type.
    $open = [];
    // Loops through the milestone steps in order.
    foreach ($milestoneSteps as $step) {
        // Reads the document type of this milestone.
        $docType = trim((string) ($step['docType'] ?? ''));
        // Checks whether the milestone has no document type.
        if ($docType === '') {
            // Notes that coordinator-only milestones have nothing to upload.
            // A milestone of the coordinator without a document of the student.
            // Skips milestones with no document type.
            continue;
        // Closes the no-document check.
        }
        // Reads the milestone state, defaulting to pending.
        $state = (string) ($step['state']['status'] ?? 'pending');
        // Checks whether the milestone is already done.
        if ($state === 'done') {
            // Notes that a finished milestone needs no upload.
            // Finished: there is nothing left to upload.
            // Skips the finished milestone.
            continue;
        // Closes the done check.
        }
        // Notes that the first unfinished milestone is the current work.
        // The first milestone that is not finished is the work of the student
        // Explains that later milestones wait for this one.
        // now. Everything behind it waits for it.
        // Opens this document type for upload.
        $open[] = $docType;
        // Stops after the first open document type.
        break;
    // Closes the milestone loop.
    }
    // Returns the open document types.
    return $open;
// Ends openDocumentTypes.
}

/**
 * The stages of a track the student may send documents for.
 *
 * The stages are followed one after the other. A stage the student has not
 * reached yet is locked, so none of its document types are open: the workflow
 * of the track asks for one stage after the other.
 *
 * @param  array<int, array<string, mixed>>|array<string, array<string, mixed>>  $stages
 * @return array<string, bool> the stage key => the stage is open for uploads
 */
// Maps each stage of a track to whether uploads are open.
function openStagesForUploads(array $progress, array $stages): array {
    // Turns the stage list into a plain numbered list.
    $list = array_values($stages);
    // Works out the lock state of every stage.
    $locks = stageLockStates($list, workflowCurrentIndex($progress, $list));
    // Starts the stage key to open flag map.
    $open = [];
    // Loops through the stages in order.
    foreach ($list as $stage) {
        // Reads the key of this stage.
        $key = (string) ($stage['key'] ?? '');
        // Marks the stage open unless its lock says otherwise.
        $open[$key] = !($locks[$key]['locked'] ?? false);
    // Closes the stage loop.
    }
    // Returns the stage open flags.
    return $open;
// Ends openStagesForUploads.
}

/**
 * The document types that are open for a student, for every stage of a track.
 *
 * A stage the student has not reached yet stays locked, so it has no open
 * document type at all.
 *
 * @param  array<int, array<string, mixed>>|array<string, array<string, mixed>>  $stages
 * @return array<string, array<int, string>>
 */
// Maps every stage of a track to its open document types.
function openDocumentTypesByStage(string $email, string $track, array $stages, array $progress = []): array {
    // Works out stage locks, or none when no progress was passed.
    $stageOpen = $progress ? openStagesForUploads($progress, $stages) : [];
    // Starts the stage key to document types map.
    $open = [];
    // Loops through the stage keys of the track.
    foreach (array_keys($stages) as $stageKey) {
        // Casts the stage key to a string.
        $stageKey = (string) $stageKey;
        // Checks whether this stage is locked for uploads.
        if (isset($stageOpen[$stageKey]) && !$stageOpen[$stageKey]) {
            // Gives a locked stage no open document types.
            $open[$stageKey] = [];
            // Skips to the next stage.
            continue;
        // Closes the locked stage check.
        }
        // Lists the open document types of the unlocked stage.
        $open[$stageKey] = openDocumentTypes(stageMilestoneSteps($email, $track, $stageKey));
    // Closes the stage loop.
    }
    // Returns the open document types by stage.
    return $open;
// Ends openDocumentTypesByStage.
}

/**
 * The requirements a student has to meet to submit an application for a stage.
 *
 * The fields are always required. The document that has to be attached is the
 * paper of the stage, which follows the track: a thesis student attaches the
 * Thesis Proposal, a capstone student the Capstone Proposal, a seminar-paper
 * student the Seminar Paper Proposal.
 *
 * The adviser endorsement form, the official receipt and the Graduate School
 * endorsement are required by the track as well, but they belong to the later
 * steps of the workflow and never block the submission - the coordinator sees
 * them on the Milestone Tracker.
 *
 * Returns ['fields' => [...], 'documents' => [...]] with the labels of what is
 * still missing.
 */
// Lists the fields and documents still missing before submission.
function applicationSubmissionRequirements(array $stage, string $track, string $stageKey, array $values, array $uploads = []): array {
    // Starts the list of missing fields.
    $missingFields = [];
    // Checks whether the research title is empty.
    if (trim((string) ($values['title'] ?? '')) === '') {
        // Adds the title to the missing fields.
        $missingFields[] = 'Research / Paper Title';
    // Closes the title check.
    }
    // Checks whether the adviser name is empty.
    if (trim((string) ($values['adviser'] ?? '')) === '') {
        // Adds the adviser name to the missing fields.
        $missingFields[] = 'Adviser Name';
    // Closes the adviser check.
    }
    // Checks whether the abstract is empty.
    if (trim((string) ($values['abstract'] ?? '')) === '') {
        // Adds the abstract to the missing fields.
        $missingFields[] = 'Brief Description / Abstract';
    // Closes the abstract check.
    }
    // Checks whether the enrollment date is empty.
    if (trim((string) ($values['enroll_date'] ?? '')) === '') {
        // Adds the enrollment date to the missing fields.
        $missingFields[] = 'Enrollment / Start Date';
    // Closes the enrollment date check.
    }

    // Starts the list of missing documents.
    $missingDocuments = [];
    // Reads the paper label from the stage definition.
    $paperLabel = $stage['documents'][0]['label'] ?? '';
    // Checks whether the stage defines a paper label.
    if ($paperLabel !== '') {
        // Starts the list of uploaded document kinds.
        $uploadedKinds = [];
        // Loops through the student's uploads.
        foreach ($uploads as $upload) {
            // Records the kind of each uploaded document.
            $uploadedKinds[] = classifyUploadDocType((string) ($upload['docType'] ?? ''));
        // Closes the upload loop.
        }
        // Checks whether no paper was uploaded yet.
        if (!in_array('paper', $uploadedKinds, true)) {
            // Adds the paper label to the missing documents.
            $missingDocuments[] = $paperLabel;
        // Closes the paper upload check.
        }
    // Closes the paper label check.
    }

    // Returns the missing fields and missing documents.
    return ['fields' => $missingFields, 'documents' => $missingDocuments];
// Ends applicationSubmissionRequirements.
}

/**
 * The status badge of a document or requirement.
 *
 * The labels and the colours follow the Requirements Checklist the coordinator
 * has always seen:
 *   Pending      - the student has not uploaded it yet
 *   Submitted    - the student uploaded it, the coordinator still has to review it
 *   Under Review - it waits for the coordinator's own action
 *   Approved     - the coordinator accepted it
 *   Rejected     - it needs corrections or a resubmission
 *
 * Returns ['label' => ..., 'class' => ...].
 */
/**
 * Put the document actions of the coordinator on top of the milestone states.
 *
 * The states come from the documents that are stored. When the coordinator
 * picks a new action for a document, the milestone of that document follows
 * immediately, so the check of the status sees the change that is being
 * made instead of the one that was stored before.
 *
 * Only the milestone of the document that was touched is changed, so the
 * other milestones of the stage keep their own state.
 *
 * @param  array<int, array<string, mixed>>  $milestones
 * @param  array<string, string>             $actions  document id => action
 * @return array<int, array<string, mixed>>
 */
// Applies the coordinator's document actions to the milestone states.
function applyDocumentActionsToMilestones(array $milestones, array $actions): array {
    // Returns the milestones unchanged when either list is empty.
    if (!$milestones || !$actions) {
        // Returns early without changes.
        return $milestones;
    // Closes the empty check.
    }

    // Notes the table that maps a document kind to its milestone.
    // The kind of a document tells the milestone it belongs to.
    // Starts the document kind to milestone key map.
    $kindToStep = [
        // Maps the paper document to the paper milestone.
        'paper'                 => 'paper',
        // Maps the adviser endorsement document to its milestone.
        'adviser_endorsement'   => 'adviser_endorsement',
        // Maps the Graduate School endorsement document to its milestone.
        'grad_school_endorsement' => 'grad_school_endorsement',
        // Maps the receipt document to the payment milestone.
        'receipt'               => 'payment',
    // Closes the kind map.
    ];

    // Starts the milestone key to new state map.
    $states = [];
    // Loops through the chosen document actions.
    foreach ($actions as $uploadId => $action) {
        // Loads the upload the action belongs to.
        $upload = findUpload((string) $uploadId);
        // Checks whether the upload no longer exists.
        if (!$upload) {
            // Skips actions for missing uploads.
            continue;
        // Closes the missing upload check.
        }
        // Works out the document kind of the upload.
        $kind = classifyUploadDocType((string) ($upload['docType'] ?? ''));
        // Finds the milestone key for that kind.
        $step = $kindToStep[$kind] ?? '';
        // Checks whether the kind maps to no milestone.
        if ($step === '') {
            // Skips kinds that have no milestone.
            continue;
        // Closes the unmapped kind check.
        }
        // Stores the milestone state the action produces.
        $states[$step] = documentMilestoneState($action);
    // Closes the action loop.
    }
    // Checks whether no milestone state was produced.
    if (!$states) {
        // Returns the milestones unchanged.
        return $milestones;
    // Closes the no-state check.
    }

    // Loops through the milestones to apply the new states.
    foreach ($milestones as $index => $milestone) {
        // Reads the key of this milestone.
        $key = (string) ($milestone['key'] ?? '');
        // Checks whether this milestone has a new state.
        if (!isset($states[$key])) {
            // Skips milestones that were not touched.
            continue;
        // Closes the new state check.
        }
        // Sets the milestone status to the new state.
        $milestones[$index]['state']['status'] = $states[$key];
        // Sets the review value to the same state.
        $milestones[$index]['state']['review'] = $states[$key];
    // Closes the milestone loop.
    }

    // Notes that the coordinator review follows the document reviews.
    // The review of the coordinator follows the documents they looked at.
    // Reads the state of the first milestone, the paper.
    $paperState = $milestones[0]['state']['status'] ?? '';
    // Starts with no adviser endorsement state.
    $adviserState = null;
    // Looks for the adviser endorsement milestone.
    foreach ($milestones as $index => $milestone) {
        // Checks whether this milestone is the adviser endorsement.
        if (($milestone['key'] ?? '') === 'adviser_endorsement') {
            // Reads the adviser endorsement state.
            $adviserState = $milestones[$index]['state']['status'] ?? '';
        // Closes the adviser endorsement check.
        }
    // Closes the adviser lookup loop.
    }
    // Loops through the milestones to update the coordinator review.
    foreach ($milestones as $index => $milestone) {
        // Skips any milestone that is not the coordinator review.
        if (($milestone['key'] ?? '') !== 'coord_review') {
            // Moves on to the next milestone.
            continue;
        // Closes the review check.
        }
        // Flags the review when the paper or endorsement is flagged.
        if ($paperState === 'flagged' || $adviserState === 'flagged') {
            // Sets the review milestone to flagged.
            $milestones[$index]['state']['status'] = 'flagged';
        // Completes the review when both documents are verified.
        } elseif ($paperState === 'done' && $adviserState === 'done') {
            // Sets the review milestone to done.
            $milestones[$index]['state']['status'] = 'done';
        // Handles the case where documents still wait for review.
        } else {
            // Sets the review milestone back to pending.
            $milestones[$index]['state']['status'] = 'pending';
        // Closes the review state branches.
        }
    // Closes the review loop.
    }

    // Returns the milestones with the new states.
    return $milestones;
// Ends applyDocumentActionsToMilestones.
}

/**
 * The next step of a student, in the order of their workflow.
 *
 * The steps of the track are followed in order, so the next step is the first
 * one that is not finished yet. A step that waits for the coordinator is
 * named as well, so the student always knows where they stand.
 *
 * @return array{key:string,label:string,status:string,detail:string,coord:bool}|null
 */
// Returns the first milestone of a stage that is not finished.
function nextMilestoneStep(array $steps): ?array {
    // Loops through the milestone steps in order.
    foreach ($steps as $step) {
        // Reads the state of this step, defaulting to pending.
        $status = (string) ($step['state']['status'] ?? 'pending');
        // Checks whether the step is already done.
        if ($status === 'done') {
            // Skips the finished step.
            continue;
        // Closes the done check.
        }
        // Starts the returned summary of the next step.
        return [
            // Returns the step key.
            'key'    => (string) ($step['key'] ?? ''),
            // Returns the step label.
            'label'  => (string) ($step['label'] ?? ''),
            // Returns the step status.
            'status' => $status,
            // Returns the step detail text.
            'detail' => (string) ($step['detail'] ?? ''),
            // Returns whether the coordinator owns this step.
            'coord'  => (bool) ($step['coord'] ?? false),
        // Closes the returned step array.
        ];
    // Closes the step loop.
    }
    // Returns null when every step is done.
    return null;
// Ends nextMilestoneStep.
}

/**
 * The document status a milestone follows.
 *
 * submitted   the student sent it, the coordinator has not looked at it yet
 * verified    the coordinator approved it
 * incomplete  the coordinator needs something fixed
 * revision    the coordinator asks for a new version
 * pending     the document is not there at all
 */
// Turns a document status into the milestone state it means.
function documentMilestoneState(?string $status): string {
    // Normalises the status by trimming and lowercasing it.
    $status = strtolower(trim((string) $status));
    // Checks whether the coordinator verified the document.
    if ($status === 'verified' || $status === 'approved') {
        // Maps a verified document to the done state.
        return 'done';
    // Closes the verified check.
    }
    // Checks whether the document needs a fix or a new version.
    if ($status === 'incomplete' || $status === 'revision') {
        // Maps incomplete and revision documents to flagged.
        return 'flagged';
    // Closes the revision and incomplete check.
    }
    // Checks whether the document is only submitted.
    if ($status === 'submitted') {
        // Maps a submitted document to the submitted state.
        return 'submitted';
    // Closes the submitted check.
    }
    // Treats every other status as pending.
    return 'pending';
// Ends documentMilestoneState.
}

/**
 * The word the student and the coordinator see for a document status.
 *
 * @return array{label:string,class:string,action:string}
 */
// Builds the badge text, class, and action of a document status.
function documentStatusBadge(?array $upload): array {
    // Checks whether the student has no upload.
    if (!$upload) {
        // Returns the Pending badge when nothing was uploaded.
        return ['label' => 'Pending', 'class' => 'status-pending', 'action' => 'submitted'];
    // Closes the no-upload check.
    }
    // Reads the upload status in lowercase, defaulting to submitted.
    $status = strtolower(trim((string) ($upload['status'] ?? 'submitted')));
    // Checks whether the coordinator verified the document.
    if ($status === 'verified' || $status === 'approved') {
        // Returns the Verified badge.
        return ['label' => 'Verified', 'class' => 'status-approved', 'action' => 'verified'];
    // Closes the verified check.
    }
    // Checks whether the document was marked incomplete.
    if ($status === 'incomplete') {
        // Returns the Incomplete badge.
        return ['label' => 'Incomplete', 'class' => 'status-revision', 'action' => 'incomplete'];
    // Closes the incomplete check.
    }
    // Checks whether a revision was requested.
    if ($status === 'revision') {
        // Returns the Revision badge.
        return ['label' => 'Revision', 'class' => 'status-revision', 'action' => 'revision'];
    // Closes the revision check.
    }
    // Returns the Submitted badge for every other status.
    return ['label' => 'Submitted', 'class' => 'status-submitted', 'action' => 'submitted'];
// Ends documentStatusBadge.
}

/**
 * The status badge of a student-submitted requirement, with receipt/payment
 * records as the only coordinator-entered exception.
 */
// Builds the status badge of a requirement and its upload.
function requirementStatusBadge(array $requirement, ?array $upload, array $app = []): array {
    // Checks whether this is a receipt with no uploaded file.
    if (($requirement['kind'] ?? '') === 'receipt' && empty($upload)) {
        // Notes that payment may be recorded without a receipt file.
        // The payment can also be recorded without a receipt upload.
        // Checks for a recorded payment or a receipt number.
        return (!empty($app['paymentRecorded']) || trim((string) ($app['receiptNumber'] ?? '')) !== '')
            // Returns Verified when the coordinator recorded the payment.
            ? ['label' => 'Verified', 'class' => 'status-approved', 'action' => 'verified']
            // Returns Not Submitted when no payment was recorded.
            : ['label' => 'Not Submitted', 'class' => 'status-not-submitted', 'action' => 'submitted'];
    // Closes the receipt without upload branch.
    }
    // Checks whether the student has no upload at all.
    if (!$upload) {
        // Notes that the student has not sent this document yet.
        // The student has not sent this document yet.
        // Returns the Not Submitted badge.
        return ['label' => 'Not Submitted', 'class' => 'status-not-submitted', 'action' => 'submitted'];
    // Closes the no-upload check.
    }
    // Uses the normal document badge for an upload.
    return documentStatusBadge($upload);
// Ends requirementStatusBadge.
}

/**
 * The documents and requirements of a stage in one list.
 *
 * Every requirement of the track comes first; the ones the student already
 * uploaded carry the upload (file name, date, review status), the ones that
 * are still missing are listed as Pending. Uploads that are not one of the
 * requirements - a revision, for instance - are listed after them, so nothing
 * is hidden and nothing is listed twice.
 *
 * Returns a list of ['requirement' => array|null, 'upload' => array|null].
 */
// Merges the stage requirements with the student's uploads.
function documentsAndRequirements(array $requiredDocs, array $uploads, array $app = []): array {
    // Starts the list of result rows.
    $rows = [];
    // Starts the set of upload ids already listed.
    $used = [];

    // Loops through every required document of the stage.
    foreach ($requiredDocs as $requirement) {
        // Starts a block comment on how the newest file is matched.
        /*
         * The newest file of a requirement is the one that is reviewed, so a
         * file the student sent earlier never shows up a second time. Every
         * file of that kind belongs to the requirement, which keeps a
         * requirement from being listed twice.
         */
        // Finds the newest upload that matches this requirement.
        $matched = latestUploadOfKind($uploads, (string) $requirement['kind']);

        // Notes that every file of this kind belongs to the requirement.
        // Every file of that kind belongs to this requirement.
        // Loops through the uploads to mark the matched files.
        foreach ($uploads as $doc) {
            // Checks whether this upload has the requirement's kind.
            if (classifyUploadDocType((string) ($doc['docType'] ?? '')) === $requirement['kind']) {
                // Marks this upload id as already listed.
                $used[(string) $doc['id']] = true;
            // Closes the kind check.
            }
        // Closes the upload marking loop.
        }

        // Adds the requirement with its newest upload to the rows.
        $rows[] = ['requirement' => $requirement, 'upload' => $matched];
    // Closes the requirement loop.
    }

    // Notes that the remaining uploads are extra files.
    // Files that are not one of the requirements of this stage.
    // Loops through all uploads again.
    foreach ($uploads as $doc) {
        // Checks whether this upload was not matched to a requirement.
        if (!isset($used[(string) $doc['id']])) {
            // Adds the extra upload as a row without a requirement.
            $rows[] = ['requirement' => null, 'upload' => $doc];
        // Closes the unmatched upload check.
        }
    // Closes the extra upload loop.
    }

    // Returns the merged requirement and upload rows.
    return $rows;
// Ends documentsAndRequirements.
}

/**
 * One application with everything the coordinator needs to work on it.
 *
 * Returns null when the application does not exist or has been archived.
 */
// Loads one application with the details the coordinator needs.
function applicationById(int $id): ?array {
    // Rejects an invalid id or a missing DB class.
    if ($id <= 0 || !class_exists('DB')) {
        // Returns null when the application cannot be loaded.
        return null;
    // Closes the guard check.
    }
    // Starts the block that may throw database errors.
    try {
        // Starts the application select statement.
        $stmt = DB::getConnection()->prepare(
            // Begins the SQL query and its first column, the application id.
            "SELECT
                a.application_id AS id,
                u.email AS studentEmail,
                COALESCE(NULLIF(TRIM(CONCAT(s.last_name, ', ', s.first_name, IF(s.middle_initial IS NULL OR TRIM(REPLACE(s.middle_initial, '.', '')) = '', '', CONCAT(' ', TRIM(REPLACE(s.middle_initial, '.', '')))))), ''), u.full_name) AS student,
                CASE
                    WHEN s.program LIKE '%Computer Science%' THEN 'MSCS'
                    WHEN s.program LIKE '%Information Technology%' THEN 'MIT'
                    WHEN s.program LIKE '%Mathematics%' THEN 'MATH'
                    ELSE s.program
                END AS program,
                s.track,
                s.adviser_name AS adviser,
                a.presentation_stage AS stage,
                a.paper_title AS title,
                a.status,
                a.coordinator_comment AS coordinatorComment,
                a.grad_school_endorsed AS gradSchoolEndorsed,
                a.payment_recorded AS paymentRecorded,
                a.receipt_number AS receiptNumber,
                a.payment_date AS paymentDate,
                a.payment_amount AS paymentAmount,
                a.ready_for_presentation AS readyForPresentation,
                a.workflow_state AS workflowState,
                a.result AS result,
                a.submitted_at AS date
             FROM applications a
             INNER JOIN users u ON u.user_id = a.user_id
             LEFT JOIN students s ON s.user_id = u.user_id
             WHERE a.application_id = :id AND a.archived_at IS NULL
             LIMIT 1"
        // Closes the SQL query and prepares the statement.
        );
        // Runs the query with the application id.
        $stmt->execute(['id' => $id]);
        // Fetches the application row.
        $row = $stmt->fetch();
        // Checks whether no application was found.
        if (!$row) {
            // Returns null when the application is missing or archived.
            return null;
        // Closes the missing row check.
        }
        // Adds the stage key worked out from the stage label.
        $row['stageKey'] = stageKeyFromLabel((string) $row['stage']);
        // Decodes the stored workflow state JSON.
        $row['workflowState'] = json_decode((string) ($row['workflowState'] ?? '[]'), true) ?: [];
        // Returns the complete application row.
        return $row;
    // Catches any database error.
    } catch (Throwable $e) {
        // Returns null when the query fails.
        return null;
    // Closes the try/catch block.
    }
// Ends applicationById.
}

/**
 * The identity a coordinator sees next to an application: the student's full
 * name, the name of their program and their assigned adviser.
 *
 * It is read from the users and students records, not from an application row,
 * so every stage of the same student shows the same details even when that
 * stage's application row does not carry them.
 *
 * @return array{name: string, program: string, programCode: string, adviser: string}|null
 */
// Loads the student identity behind an email address.
function studentProfileForEmail(string $email): ?array {
    // Trims spaces from the email.
    $email = trim($email);
    // Checks for an empty email or a missing DB class.
    if ($email === '' || !class_exists('DB')) {
        // Returns null when there is nothing to look up.
        return null;
    // Closes the guard check.
    }
    // Starts the block that may throw database errors.
    try {
        // Starts the student select statement.
        $stmt = DB::getConnection()->prepare(
            // Selects the account name and the student details for the email.
            'SELECT u.full_name, u.email,
                    s.first_name, s.last_name, s.middle_initial,
                    s.program, s.track, s.adviser_name
             FROM users u
             LEFT JOIN students s ON s.user_id = u.user_id
             WHERE u.email = :email
             LIMIT 1'
        // Closes the student select statement.
        );
        // Runs the query with the email address.
        $stmt->execute(['email' => $email]);
        // Fetches the matching row.
        $row = $stmt->fetch();
        // Checks whether the email has no user record.
        if (!$row) {
            // Returns null when the email is unknown.
            return null;
        // Closes the missing row check.
        }
        // Works out the program code from the row.
        $programCode = programCodeForStudent($row);
        // Starts the identity returned to the caller.
        return [
            // Uses the student display name, or the account name.
            'name' => studentDisplayName($row) ?: (string) ($row['full_name'] ?? ''),
            // Uses the program name, or the stored program text.
            'program' => PROGRAMS[$programCode] ?? trim((string) ($row['program'] ?? '')),
            // Returns the short program code.
            'programCode' => $programCode,
            // Returns the adviser name.
            'adviser' => trim((string) ($row['adviser_name'] ?? '')),
            // Returns the track key.
            'track' => trim((string) ($row['track'] ?? '')),
        // Closes the returned identity array.
        ];
    // Catches any database error.
    } catch (Throwable $e) {
        // Returns null when the lookup fails.
        return null;
    // Closes the try/catch block.
    }
// Ends studentProfileForEmail.
}

/**
 * Every non-archived application of a student, newest first.
 */
// Lists a student's non-archived applications, newest first.
function applicationsForStudent(string $email): array {
    // Checks for an empty email or a missing DB class.
    if ($email === '' || !class_exists('DB')) {
        // Returns an empty list when there is nothing to query.
        return [];
    // Closes the guard check.
    }
    // Starts the block that may throw database errors.
    try {
        // Starts the application select statement.
        $stmt = DB::getConnection()->prepare(
            // Selects the application fields for the student email.
            'SELECT a.application_id AS id, a.user_id, a.presentation_stage AS stage, a.paper_title AS title,
                    a.status, a.submitted_at AS date, a.result,
                    a.coordinator_comment AS coordinatorComment,
                    a.grad_school_endorsed AS gradSchoolEndorsed,
                    a.payment_recorded AS paymentRecorded,
                    a.receipt_number AS receiptNumber,
                    a.payment_date AS paymentDate,
                    a.payment_amount AS paymentAmount,
                    a.ready_for_presentation AS readyForPresentation,
                    a.workflow_state AS workflowState
             FROM applications a
             INNER JOIN users u ON u.user_id = a.user_id
             WHERE u.email = :email AND a.archived_at IS NULL
             ORDER BY a.submitted_at DESC, a.application_id DESC'
        // Closes the application select statement.
        );
        // Runs the query with the email address.
        $stmt->execute(['email' => $email]);
        // Starts the list of application rows.
        $rows = [];
        // Loops through the fetched application rows.
        foreach ($stmt->fetchAll() as $row) {
            // Casts the application id to an integer.
            $row['id'] = (int) $row['id'];
            // Adds the stage key worked out from the stage label.
            $row['stageKey'] = stageKeyFromLabel((string) $row['stage']);
            // Decodes the stored workflow state JSON.
            $row['workflowState'] = json_decode((string) ($row['workflowState'] ?? '[]'), true) ?: [];
            // Adds the application row to the list.
            $rows[] = $row;
        // Closes the application row loop.
        }
        // Returns the application rows.
        return $rows;
    // Catches any database error.
    } catch (Throwable $e) {
        // Returns an empty list when the query fails.
        return [];
    // Closes the try/catch block.
    }
// Ends applicationsForStudent.
}

/**
 * The application a student already has for a stage, if any.
 */
// Finds the application a student has for one stage.
function applicationForStage(string $email, string $stageKey): ?array {
    // Loops through the student's applications.
    foreach (applicationsForStudent($email) as $application) {
        // Checks whether this application is for the requested stage.
        if (($application['stageKey'] ?? '') === $stageKey) {
            // Returns the matching application.
            return $application;
        // Closes the stage match check.
        }
    // Closes the application loop.
    }
    // Returns null when the stage has no application.
    return null;
// Ends applicationForStage.
}

/**
 * What a student still has to hand over before a presentation may be
 * scheduled.
 *
 * The list follows the track (seminar paper, capstone or thesis) and the stage
 * the application is currently on, and it follows the same milestone workflow
 * the student sees: the paper and the adviser endorsement have to be uploaded
 * and verified, the student has to upload a Graduate School endorsement for
 * review, the coordinator has to mark the student ready, and the official
 * receipt has to be recorded.
 *
 * An empty array means every requirement is met and the presentation may be
 * scheduled.
 */
// Lists what a student must still submit before a presentation.
function applicationMissingRequirements(string $track, string $stageKey, array $uploads, array $app = []): array {
    // Starts the list of missing requirements.
    $missing = [];

    // Loops through the required documents of the stage.
    foreach (requiredDocumentsForStage($track, $stageKey) as $requirement) {
        // Notes that a receipt can be met by recorded payment.
        // A receipt can also be satisfied by payment details recorded by the coordinator.
        // Checks whether this requirement is the receipt.
        if ($requirement['kind'] === 'receipt') {
            // Skips the receipt because it is checked later.
            continue;
        // Closes the receipt check.
        }
        // Finds the best upload of this requirement kind.
        $document = bestUploadOfKind($uploads, $requirement['kind']);
        // Checks whether that upload was verified or approved.
        $verified = $document && in_array($document['status'] ?? '', ['verified', 'approved'], true);
        // Checks whether the requirement is not yet verified.
        if (!$verified) {
            // Starts the missing requirement entry.
            $missing[] = [
                // Sets the display label of the requirement.
                'label' => $requirement['label'],
                // Sets the document kind of the requirement.
                'kind'  => $requirement['kind'],
                // Chooses the reason based on the upload.
                'why'   => $document
                    // Explains that the file waits for coordinator review.
                    ? 'Uploaded but not yet verified by the coordinator'
                    // Explains that the student has not submitted it.
                    : 'Not submitted by the student',
            // Closes the missing requirement entry.
            ];
        // Closes the not-verified check.
        }
    // Closes the requirement loop.
    }

    // Starts a block comment on how the receipt is satisfied.
    /*
     * The official receipt is satisfied by a verified receipt upload. A
     * student who sent the receipt but whose file was sent back has to send
     * it again, so the review of the file decides. Without a file the
     * payment the coordinator recorded counts.
     */
    // Finds the newest receipt upload.
    $receiptDocument = bestUploadOfKind($uploads, 'receipt');
    // Checks whether the payment does not count as recorded.
    if (!paymentIsRecorded($app, $uploads)) {
        // Starts a missing entry for the official receipt.
        $missing[] = [
            // Sets the receipt requirement label.
            'label' => 'Official Payment / Receipt',
            // Sets the receipt document kind.
            'kind'  => 'receipt',
            // Chooses the receipt reason from the upload.
            'why'   => $receiptDocument
                // Explains that the file waits for coordinator review.
                ? 'Uploaded but not yet verified by the coordinator'
                // Explains that payment and receipt are both missing.
                : 'Payment not recorded and receipt not verified',
        // Closes the receipt missing entry.
        ];
    // Closes the payment check.
    }

    // Checks whether the coordinator has not marked readiness.
    if (empty($app['readyForPresentation'])) {
        // Starts a missing entry for ready for presentation.
        $missing[] = [
            // Sets the readiness requirement label.
            'label' => 'Ready for Presentation',
            // Sets the readiness requirement kind.
            'kind'  => 'ready_for_presentation',
            // Explains that the coordinator has not marked readiness.
            'why'   => 'The coordinator has not yet marked the student ready',
        // Closes the readiness missing entry.
        ];
    // Closes the readiness check.
    }

    // Returns the list of missing requirements.
    return $missing;
// Ends applicationMissingRequirements.
}

