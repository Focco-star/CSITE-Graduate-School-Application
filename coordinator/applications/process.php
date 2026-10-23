<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

$pageTitle   = 'Process Application';
$role        = 'coordinator';
$currentPage = 'applications';
$userName    = $mockCoordinator['name'];

$appId = (int) ($_GET['id'] ?? 0);
$app = $appId > 0 ? applicationById($appId) : null;
$isDatabaseApplication = $app !== null;

if (!$app) {
    $app = findApplication($appId) ?? (storeGet('applications')[0] ?? null);
}
$processSuccess = '';
$processError = '';

/* ------------------------------------------------------------------
 * The track of the student and the stages of that track
 * ------------------------------------------------------------------ */
$track      = $app ? ($app['track'] ?? getTrackForProgram($app['program'])) : 'thesis';
// The stages of the student's track: $stageList keeps the order of the
// workflow, $stages is the same list keyed by stage key.
$stageList = getWorkflow($track)['stages'];
$stages = [];
foreach ($stageList as $stageDefinition) {
    $stages[$stageDefinition['key']] = $stageDefinition;
}
$stageKeys  = array_keys($stages);
$trackLabel = getTrackLabel($track);
$email      = (string) ($app['studentEmail'] ?? '');

/* ------------------------------------------------------------------
 * POST: Save Process
 *
 * Everything the coordinator changed on the selected stage is saved here:
 * the application status, the comment, the milestones, the document review
 * statuses, the workflow records, the presentation result and the stage
 * approvals.
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app) {
    $selectedStage = trim((string) ($_POST['selected_stage'] ?? ($app['stageKey'] ?? '')));
    if (!isset($stages[$selectedStage])) {
        $selectedStage = (string) ($app['stageKey'] ?? ($stageKeys[0] ?? ''));
    }

    // The stage the coordinator was looking at decides which application row
    // is saved. Everything else on the page follows that stage.
    $postedAppIds = $_POST['stage_app_id'] ?? [];
    $targetAppId  = (int) ($postedAppIds[$selectedStage] ?? 0);
    if ($targetAppId > 0 && $targetAppId !== (int) ($app['id'] ?? 0)) {
        $target = applicationById($targetAppId);
        if ($target) {
            $app = $target;
            $isDatabaseApplication = true;
        }
    }

    // Everything the coordinator edits for the selected stage. The field names
    // are scoped per stage, so a stage never mixes its values up with another
    // one when the page is saved.
    $stagePosted = $_POST['stage_data'][$selectedStage] ?? [];
    if (!is_array($stagePosted)) {
        $stagePosted = [];
    }

    $status = (string) ($stagePosted['status'] ?? '');
    $stageKey = (string) ($stagePosted['stage'] ?? ($app['stageKey'] ?? 'proposal'));
    $comment = trim((string) ($stagePosted['comment'] ?? ''));
    // Approving or revoking the progression to the next stage.
    $approveStage   = trim((string) ($_POST['approve_stage'] ?? ''));
    $disapproveStage = trim((string) ($_POST['disapprove_stage'] ?? ''));
    $allowed = COORDINATOR_STATUSES;
    // A record written before the status list changed keeps its own status
    // until the coordinator picks one of the current ones.
    if (!empty($app['status']) && $app['status'] === $status && !in_array($status, $allowed, true)) {
        $allowed[] = $app['status'];
    }
    if (!in_array($status, $allowed, true)) {
        $processError = 'Select a valid application status.';
    } else {
        $track = $app['track'] ?? getTrackForProgram($app['program']);
        // The label of every stage of the track (the stage definitions
        // themselves stay in $stages, which is built further down).
        $stageLabels = getStagesForTrack($track);
        $workflowState = $app['workflowState'] ?? [];

        /*
         * The status has to agree with what the student has actually done, so
         * a record cannot claim to be ready, scheduled or finished while the
         * milestone workflow says otherwise. The milestones come from the same
         * helper the student's tracker uses.
         */
        $currentStageKey = (string) ($app['stageKey'] ?? stageKeyFromLabel((string) ($app['stage'] ?? '')));
        $statusMilestones = stageMilestoneSteps((string) ($app['studentEmail'] ?? ''), $track, $currentStageKey);

        /*
         * The milestones above come from the database, so they still show the
         * state the documents had before this save. The actions the coordinator
         * is picking right now are put on top of them, so a document that is
         * verified in this very save counts as verified right away. Without
         * this a coordinator could not verify a document again after it had
         * been sent back to the student.
         */
        $postedDocumentActions = [];
        foreach (($_POST['upload_status'] ?? []) as $uploadId => $uploadStatus) {
            if (!in_array($uploadStatus, ['submitted', 'verified', 'incomplete', 'revision'], true)) {
                continue;
            }
            $actionUpload = findUpload((string) $uploadId);
            if (!$actionUpload) {
                continue;
            }
            $postedDocumentActions[(string) $uploadId] = $uploadStatus;
        }
        $statusMilestones = applyDocumentActionsToMilestones($statusMilestones, $postedDocumentActions);

        // The presentation result the coordinator picked. A stage without an
        // application has no field to pick from, so the result that is already
        // saved is kept instead of being cleared by the save.
        $resultWasPosted = array_key_exists('result', $stagePosted);
        $postedResult    = $resultWasPosted ? trim((string) $stagePosted['result']) : '';
        $effectiveResult = $resultWasPosted ? $postedResult : (string) ($app['result'] ?? '');

        // The coordinator is only moving the student to another status when
        // the status itself is different. Changing a document is a different
        // kind of work, and it may not be blocked by the requirements of a
        // status the student is not being moved to.
        $statusChanged = ($status !== (string) ($app['status'] ?? ''));

        $blockedReason = applicationStatusBlockedReason($status, $statusMilestones, $effectiveResult, $statusChanged);
        if ($blockedReason !== null) {
            $processError = $blockedReason;
        } else {
        foreach (($_POST['upload_status'] ?? []) as $uploadId => $uploadStatus) {
            if (!in_array($uploadStatus, ['submitted', 'verified', 'incomplete', 'revision'], true)) continue;
            $upload = findUpload((string) $uploadId);
            if (!$upload) continue;


            $linkedAppId = (int) ($upload['applicationId'] ?? 0);
            if ($linkedAppId !== (int) $app['id']) {
                if ($linkedAppId !== 0 || empty($app['studentEmail'])
                    || strcasecmp((string) ($upload['studentEmail'] ?? ''), (string) $app['studentEmail']) !== 0) {
                    continue;
                }
            }
            updateUploadStatus((string) $uploadId, $uploadStatus);
            $key = classifyUploadDocType((string) $upload['docType']);
            $workflowState[$key] = $uploadStatus;
        }

        /*
         * Payment and readiness follow their submitted records; the Graduate
         * School endorsement is a student-uploaded document reviewed above.
         */
        $docStatusByKind = [];
        $postedStatuses = $_POST['upload_status'] ?? [];
        $storedStatuses = [];
        foreach (uploadsForApplication((int) $app['id']) as $doc) {
            $storedStatuses[(string) $doc['id']] = (string) ($doc['status'] ?? 'submitted');
        }
        /*
         * The status of the newest file of every kind. A student may send the
         * same kind of document more than once, and the file that was sent
         * last is the one that is reviewed, so it is the one that counts.
         */
        foreach (uploadsForApplication((int) $app['id']) as $doc) {
            $kind = classifyUploadDocType((string) ($doc['docType'] ?? ''));
            $id   = (string) $doc['id'];
            $date = (string) ($doc['date'] ?? '');
            if (!isset($docStatusByKind[$kind]) || $date >= (string) $docStatusByKind[$kind]['date']) {
                $docStatusByKind[$kind] = ['date' => $date, 'id' => $id];
            }
        }
        foreach ($docStatusByKind as $kind => $row) {
            $docStatusByKind[$kind] = isset($postedStatuses[$row['id']])
                ? (string) $postedStatuses[$row['id']]
                : ($storedStatuses[$row['id']] ?? 'submitted');
        }
        $receiptVerified = ($docStatusByKind['receipt'] ?? '') === 'verified';

        $gradSchoolUpload = latestUploadOfKind(uploadsForApplication((int) $app['id']), 'grad_school_endorsement');
        $gradEndorsed = $gradSchoolUpload
            && in_array(($gradSchoolUpload['status'] ?? ''), ['verified', 'approved'], true);
        // The receipt details come from the student, so the payment counts as
        // recorded once the student sent the details or the coordinator
        // verified the receipt.
        $paymentRecorded = $receiptVerified || trim((string) ($app['receiptNumber'] ?? '')) !== '';
        $readyForPresentation = array_key_exists('readyForPresentation', $stagePosted)
            ? ($stagePosted['readyForPresentation'] === '1')
            : !empty($app['readyForPresentation']);

        $updatedFields = [
            'status' => $status,
            'stageKey' => $stageKey,
            'stage' => $stageLabels[$stageKey] ?? $app['stage'],
            'coordinatorComment' => $comment,
            'workflowState' => $workflowState,
            'gradSchoolEndorsed' => $gradEndorsed,
            'paymentRecorded' => $paymentRecorded,
            'readyForPresentation' => $readyForPresentation,
            'result' => $effectiveResult,
        ];

        try {
            if ($isDatabaseApplication) {
                $saveStatus = DB::getConnection()->prepare(
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
                );
                $saveStatus->execute([
                    'status' => $status,
                    'presentation_stage' => $updatedFields['stage'],
                    'coordinator_comment' => $comment,
                    'grad_school_endorsed' => $updatedFields['gradSchoolEndorsed'] ? 1 : 0,
                    'payment_recorded' => $updatedFields['paymentRecorded'] ? 1 : 0,
                    'ready_for_presentation' => $updatedFields['readyForPresentation'] ? 1 : 0,
                    'workflow_state' => json_encode($workflowState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null,
                    'result' => $updatedFields['result'] !== '' ? $updatedFields['result'] : null,
                    'application_id' => (int) $app['id'],
                ]);
                saveApplicationComment((int) $app['id'], $comment);
            }


            if (findApplication((int) $app['id'])) {
                updateApplicationRecord((int) $app['id'], $updatedFields);
            }

            $app = array_merge($app, $updatedFields);
            $processSuccess = 'Application progress has been saved successfully.';
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $processError = 'Unable to save application progress. Please try again.';
        }

        /*
         * Approving (or revoking) the progression to the next stage.
         *
         * The approval is only applied here, when the coordinator presses Save
         * Process. Approving marks the stage complete, which is what unlocks
         * the next stage for the student; revoking takes that away again.
         * Documents and workflow history are never touched.
         */
        if ($processError === '' && ($approveStage !== '' || $disapproveStage !== '')) {
            $actionStage   = $approveStage !== '' ? $approveStage : $disapproveStage;
            $actionAppId  = (int) ($postedAppIds[$actionStage] ?? 0);
            $actionApp    = $actionAppId > 0 ? applicationById($actionAppId) : null;
            if (!$actionApp) {
                $processError = 'The student has no application for that stage, so the progression cannot be approved.';
            } elseif ($approveStage !== '') {
                $actionUploads = uploadsForApplication($actionAppId);
                $actionMissing = applicationMissingRequirements($track, $actionStage, $actionUploads, $actionApp);
                // The result of the stage that is approved, not the one of the
                // stage the coordinator happens to be looking at.
                $actionStagePosted = $_POST['stage_data'][$actionStage] ?? [];
                if (!is_array($actionStagePosted)) {
                    $actionStagePosted = [];
                }
                $actionResult = trim((string) ($actionStagePosted['result'] ?? '')) !== ''
                    ? trim((string) $actionStagePosted['result'])
                    : (string) ($actionApp['result'] ?? '');
                if ($actionMissing) {
                    $processError = 'This stage cannot be approved yet. Please complete the required items first.';
                } elseif ($actionResult === '') {
                    $processError = 'Record the presentation result before approving the next stage.';
                } else {
                    updateApplicationRecord($actionAppId, ['status' => 'completed', 'result' => $actionResult]);
                    $processSuccess = 'Application progress has been saved successfully.';
                }
            } else {
                updateApplicationRecord($actionAppId, ['status' => 'ready_for_presentation']);
                $processSuccess = 'Application progress has been saved successfully.';
            }
        }

        /*
         * Read the application back, so the page shows exactly what is stored:
         * the approval action may have changed the status after the form was
         * written, and the stages of the track are built from the database.
         */
        if ($isDatabaseApplication && (int) ($app['id'] ?? 0) > 0) {
            $fresh = applicationById((int) $app['id']);
            if ($fresh) {
                $app = $fresh;
            }
        }
        }
    }
}

/* ------------------------------------------------------------------
 * The stages of the student, each with everything that belongs to it
 * ------------------------------------------------------------------ */
$studentApps  = $email !== '' ? applicationsForStudent($email) : [];
$schedules    = $email !== '' ? schedulesForEmail($email) : [];
$progress     = $email !== '' ? getStudentProgress($email, $track) : [];
$currentIdx   = workflowCurrentIndex($progress, $stageList);
$currentStageKey = $stageKeys[$currentIdx] ?? '';
$lockStates   = stageLockStates($stageList, $currentIdx);

$panels = [];
foreach ($stages as $key => $stage) {
    // The application the student has for this stage (the one being edited
    // always wins for its own stage).
    $stageApp = null;
    foreach ($studentApps as $candidate) {
        if (($candidate['stageKey'] ?? '') === $key) {
            $stageApp = $candidate;
            break;
        }
    }
    if ((string) ($app['stageKey'] ?? '') === $key && $app) {
        $stageApp = array_merge($stageApp ?? [], $app);
    }

    $stageUploads   = $stageApp ? uploadsForApplication((int) $stageApp['id']) : [];
    $required       = requiredDocumentsForStage($track, $key);
    $documentRows   = documentsAndRequirements($required, $stageUploads, $stageApp ?? []);
    $milestoneSteps = stageMilestoneSteps($email, $track, $key);
    $milestoneStats = milestoneProgress($milestoneSteps);
    $missing        = applicationMissingRequirements($track, $key, $stageUploads, $stageApp ?? []);

    // The presentation scheduled for this stage.
    $stageSchedule = null;
    foreach ($schedules as $schedule) {
        if ((int) ($schedule['applicationId'] ?? 0) === (int) ($stageApp['id'] ?? 0)) {
            $stageSchedule = $schedule;
            break;
        }
        if (stageKeyFromLabel((string) ($schedule['stage'] ?? '')) === $key) {
            $stageSchedule = $schedule;
            break;
        }
    }

    $requirementsComplete = true;
    foreach ($milestoneSteps as $step) {
        if (in_array($step['key'] ?? '', ['paper', 'adviser_endorsement', 'coord_review', 'grad_school_endorsement', 'payment', 'ready_for_presentation'], true)) {
            if (($step['state']['status'] ?? 'pending') !== 'done') {
                $requirementsComplete = false;
                break;
            }
        }
    }

    $panels[$key] = [
        'key'        => $key,
        'stage'      => $stage,
        'app'        => $stageApp,
        'appId'      => (int) ($stageApp['id'] ?? 0),
        'status'     => (string) ($stageApp['status'] ?? ''),
        'title'      => (string) ($stageApp['title'] ?? ''),
        'date'       => (string) ($stageApp['date'] ?? ''),
        'result'     => (string) ($stageApp['result'] ?? ''),
        'uploads'    => $stageUploads,
        'required'   => $required,
        'documents'  => $documentRows,
        'milestones' => $milestoneSteps,
        'stats'      => $milestoneStats,
        'missing'    => $missing,
        'schedule'   => $stageSchedule,
        'locked'     => $lockStates[$key]['locked'] ?? false,
        'requirementsComplete' => $requirementsComplete,
        'unlocked'   => $missing === [],
    ];
}

/* The stage the coordinator is looking at. */
$selectedStage = trim((string) ($_GET['stage'] ?? ($_POST['selected_stage'] ?? ($app['stageKey'] ?? ($stageKeys[0] ?? '')))));
if (!isset($panels[$selectedStage])) {
    $selectedStage = (string) ($app['stageKey'] ?? ($stageKeys[0] ?? ''));
}
if (!isset($panels[$selectedStage])) {
    $selectedStage = (string) ($stageKeys[0] ?? '');
}

// The progress over the whole track.
$overallDone = 0;
$overallTotal = 0;
foreach ($panels as $panel) {
    $overallDone += (int) $panel['stats']['done'];
    $overallTotal += (int) $panel['stats']['total'];
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Process Application</h2>
    <p>Review the student's progress, documents and workflow for <?= $app ? htmlspecialchars($app['student']) : 'this student' ?>.</p>
</div>

<div class="page-notifications" id="pageNotifications" aria-live="polite"></div>

<?php if ($processSuccess): ?>
<div class="alert alert-success" data-auto-dismiss><i class="fas fa-check-circle"></i> <?= htmlspecialchars($processSuccess) ?></div>
<?php endif; ?>
<?php if ($processError): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($processError) ?></div>
<?php endif; ?>

<?php if (!$app): ?>
<div class="alert alert-warning"><i class="fas fa-info-circle"></i> No application selected.</div>
<?php else: ?>

<?php /* ---------------- the sliding progress tracker ---------------- */ ?>
<div class="stage-slider" id="stageSlider">
    <button type="button" class="stage-slider-nav" id="stageSliderPrev" aria-label="Previous stage"><i class="fas fa-chevron-left"></i></button>
    <div class="stage-slider-viewport">
        <div class="stage-slider-track" id="stageSliderTrack">
            <?php foreach ($panels as $key => $panel):
                $stageStatus = $panel['status'];
                $isCompleted = in_array($stageStatus, ['completed', 'approved'], true);
                $isLocked    = $panel['locked'];
                $isCurrent   = ($key === $currentStageKey);
                $isNext      = !$isCompleted && !$isLocked && !$isCurrent;
                if ($isCompleted) {
                    $sliderClass = 'is-completed';
                    $sliderLabel = 'Completed';
                    $sliderBadge = 'status-approved';
                } elseif ($isLocked) {
                    $sliderClass = 'is-locked';
                    $sliderLabel = 'Locked';
                    $sliderBadge = 'status-not-submitted';
                } elseif ($isCurrent) {
                    $sliderClass = 'is-active';
                    $sliderLabel = 'In Progress';
                    $sliderBadge = 'status-confirmed';
                } else {
                    $sliderClass = 'is-approved';
                    $sliderLabel = 'Approved';
                    $sliderBadge = 'status-submitted';
                }
                $stageNumber = array_search($key, $stageKeys, true);
            ?>
            <button type="button" class="stage-slider-card <?= $sliderClass ?><?= $key === $selectedStage ? ' is-selected' : '' ?>"
                    data-stage="<?= htmlspecialchars($key) ?>"
                    aria-pressed="<?= $key === $selectedStage ? 'true' : 'false' ?>">
                <span class="stage-slider-num">
                    <?php if ($isLocked): ?><i class="fas fa-lock" aria-hidden="true"></i><?php else: ?><?= (int) $stageNumber + 1 ?><?php endif; ?>
                </span>
                <span class="stage-slider-text">
                    <span class="stage-slider-label"><?= htmlspecialchars($panel['stage']['label']) ?></span>
                    <span class="status-badge <?= $sliderBadge ?> stage-slider-badge"><?= htmlspecialchars($sliderLabel) ?></span>
                </span>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
    <button type="button" class="stage-slider-nav" id="stageSliderNext" aria-label="Next stage"><i class="fas fa-chevron-right"></i></button>
</div>

<div class="stage-slider-summary">
    <span><i class="fas fa-map-marker-alt" style="margin-right:5px;"></i>Current stage:
        <strong><?= htmlspecialchars($stageList[$currentIdx]['label'] ?? '—') ?></strong></span>
    <span><i class="fas fa-route" style="margin-right:5px;"></i>Track:
        <strong><?= htmlspecialchars($trackLabel) ?></strong></span>
    <span><i class="fas fa-graduation-cap" style="margin-right:5px;"></i>Overall progress:
        <strong><?= (int) $overallDone ?> / <?= (int) $overallTotal ?> milestones</strong></span>
</div>

<form method="post" action="<?= url('coordinator/applications/process.php?id=' . $app['id']) ?>" id="processForm" data-validate>
    <input type="hidden" name="selected_stage" id="selectedStageField" value="<?= htmlspecialchars($selectedStage) ?>">
    <input type="hidden" name="approve_stage" id="approveStageField" value="">
    <input type="hidden" name="disapprove_stage" id="disapproveStageField" value="">
    <?php foreach ($panels as $key => $panel): ?>
    <input type="hidden" name="stage_app_id[<?= htmlspecialchars($key) ?>]" value="<?= (int) $panel['appId'] ?>">
    <?php endforeach; ?>

    <?php /* ---------------- one panel per stage ---------------- */ ?>
    <?php foreach ($panels as $key => $panel):
        $stageApp  = $panel['app'];
        $stageName = $panel['stage']['label'];
        $isSelected = $key === $selectedStage;
        // The receipt details come from the student, who sends them with the
        // official receipt. The coordinator only reads them, never changes them.
        $receiptDetails = applicationReceiptDetails($stageApp ?? []);
    ?>
    <section class="stage-panel" data-stage="<?= htmlspecialchars($key) ?>" data-saved-approved="<?= in_array($panel['status'], ['completed', 'approved'], true) ? '1' : '0' ?>"<?= $isSelected ? '' : ' hidden' ?>>

        <div class="card">
            <div class="card-header"><h3>Current Application</h3></div>
            <div class="card-body">
                <?php if (!$stageApp): ?>
                <div class="alert alert-info" style="margin:0;">
                    <i class="fas fa-info-circle"></i>
                    <div>The student has no application for <strong><?= htmlspecialchars($stageName) ?></strong> yet.</div>
                </div>
                <?php else: ?>
                <div class="detail-grid">
                    <div class="detail-item"><label>Student</label><span><?= htmlspecialchars($stageApp['student'] ?? '') ?></span></div>
                    <div class="detail-item"><label>Program</label><span><?= htmlspecialchars($stageApp['program'] ?? '') ?></span></div>
                    <div class="detail-item"><label>Track</label><span><?= htmlspecialchars($trackLabel) ?></span></div>
                    <div class="detail-item"><label>Adviser</label><span><?= htmlspecialchars($stageApp['adviser'] ?? '—') ?></span></div>
                    <div class="detail-item"><label>Current Stage</label><span><?= htmlspecialchars($stageName) ?></span></div>
                    <div class="detail-item"><label>Current Status</label><span><?= statusBadge($panel['status']) ?></span></div>
                    <div class="detail-item" style="grid-column:1/-1;"><label>Research / Paper Title</label><span><?= htmlspecialchars($panel['title']) ?></span></div>
                    <div class="detail-item"><label>Submitted</label><span><?= $panel['date'] ? htmlspecialchars(date('M d, Y', strtotime($panel['date']))) : '—' ?></span></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Update Status</h3></div>
            <div class="card-body">
                <?php if (!$stageApp): ?>
                <p class="field-hint" style="margin:0;">The student submits an application for this stage first — then the status can be updated here.</p>
                <?php else: ?>
                <div class="form-row">
                    <div class="form-field">
                        <label>Application Status <span class="required">*</span></label>
                        <select name="stage_data[<?= htmlspecialchars($key) ?>][status]" required>
                            <option value="">Select status</option>
                            <?php foreach (COORDINATOR_STATUSES as $statusKey): ?>
                            <option value="<?= $statusKey ?>" <?= $panel['status'] === $statusKey ? 'selected' : '' ?>><?= htmlspecialchars(STATUSES[$statusKey]['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Presentation Stage</label>
                        <select name="stage_data[<?= htmlspecialchars($key) ?>][stage]">
                            <?php foreach ($panels as $stageOptionKey => $stageOption): ?>
                            <option value="<?= htmlspecialchars($stageOptionKey) ?>" <?= $key === $stageOptionKey ? 'selected' : '' ?>><?= htmlspecialchars($stageOption['stage']['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="field-hint">The stage this application belongs to.</p>
                    </div>
                </div>
                <div class="form-field">
                    <label>Recommendations / Comments</label>
                    <textarea name="stage_data[<?= htmlspecialchars($key) ?>][comment]" placeholder="Enter recommendations or revision notes for the student..."><?= htmlspecialchars($stageApp['coordinatorComment'] ?? '') ?></textarea>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card milestone-card" id="milestone-tracker-<?= htmlspecialchars($key) ?>">
            <div class="card-header">
                <h3><i class="fas fa-tasks"></i> Milestone Tracker — <?= htmlspecialchars($stageName) ?></h3>
                <div class="ms-overview">
                    <span class="ms-overview-text"><?= (int) $panel['stats']['done'] ?> of <?= (int) $panel['stats']['total'] ?> milestones complete</span>
                    <span class="ms-overview-bar"><span class="ms-overview-fill" style="width:<?= (int) $panel['stats']['total'] > 0 ? (int) round($panel['stats']['done'] * 100 / $panel['stats']['total']) : 0 ?>%;"></span></span>
                </div>
            </div>
            <div class="card-body">
                <?php if (!$panel['milestones']): ?>
                <p class="ms-empty">No milestones are defined for this stage.</p>
                <?php else: ?>
                <div class="ms-stage is-current is-open" data-ms-stage>
                    <div class="ms-stage-body" style="border-top:none;">
                        <?php foreach ($panel['milestones'] as $si => $step):
                            $state = $step['state']['status'] ?? 'pending';
                            $isDone = $state === 'done';
                            $isFlagged = $state === 'flagged';
                            $isSubmitted = $state === 'submitted';
                            $isActive = !$isDone && !$isFlagged && !$isSubmitted && $si === $panel['stats']['firstPending'];
                            $iconState = $isFlagged ? 'flagged' : ($isDone ? 'done' : ($isSubmitted ? 'submitted' : ($isActive ? 'active' : 'pending')));
                            $rowClass = $isFlagged ? 'is-flagged' : ($isDone ? 'is-done' : ($isSubmitted ? 'is-submitted' : ($isActive ? 'is-active' : 'is-pending')));
                            $stepKey = $step['key'] ?? '';
                        ?>
                        <div class="ms-step <?= $rowClass ?>">
                            <div class="ms-step-col">
                                <?= stepIconHtml($iconState) ?>
                                <?php if ($si < count($panel['milestones']) - 1): ?>
                                <span class="ms-step-line<?= $isDone ? ' done' : '' ?>"></span>
                                <?php endif; ?>
                            </div>
                            <div class="ms-step-body">
                                <div class="ms-step-label">
                                    <span><?= htmlspecialchars($step['label']) ?></span>
                                    <?php if ($step['coord']): ?><span class="coord-chip">Coordinator</span><?php endif; ?>
                                    <?php if ($isActive): ?><span class="ms-step-tag active">Next</span><?php endif; ?>
                                    <?php if ($isDone): ?><span class="ms-step-tag done">Verified</span><?php endif; ?>
                                    <?php if ($isSubmitted): ?><span class="ms-step-tag submitted">Submitted</span><?php endif; ?>
                                    <?php if ($isFlagged): ?><span class="ms-step-tag flagged">Action needed</span><?php endif; ?>
                                </div>
                                <p class="ms-step-detail"><?= htmlspecialchars($step['detail']) ?></p>
                            </div>
                            <div class="ms-step-action">
                                <?php if ($stepKey === 'ready_for_presentation' && $stageApp): ?>
                                    <label style="display:inline-flex;align-items:center;gap:.45rem;font-size:.78rem;cursor:pointer;white-space:nowrap;">
                                        <input type="hidden" name="stage_data[<?= htmlspecialchars($key) ?>][readyForPresentation]" value="0">
                                        <input type="checkbox" name="stage_data[<?= htmlspecialchars($key) ?>][readyForPresentation]" value="1" style="width:auto;" <?= !empty($stageApp['readyForPresentation']) ? 'checked' : '' ?>>
                                        <span><?= !empty($stageApp['readyForPresentation']) ? 'Ready' : 'Mark Ready' ?></span>
                                    </label>
                                <?php elseif ($stepKey === 'result'): ?>
                                    <span class="ms-step-detail" style="margin:0;"><?= htmlspecialchars($panel['result'] === 'approved' ? 'Approved' : ($panel['result'] === 'requires_revision' ? 'Requires revision' : 'Not recorded')) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="stage-approval">
                    <?php $stageIndex = array_search($key, $stageKeys, true); $nextStageKey = ($stageIndex !== false && isset($stageKeys[$stageIndex + 1])) ? $stageKeys[$stageIndex + 1] : ''; ?>
                    <?php $stageApproved = in_array($panel['status'], ['completed', 'approved'], true); ?>
                    <?php $canDecide = $nextStageKey !== '' && !$panel['locked']; ?>
                    <?php if ($nextStageKey !== ''): ?>
                        <?php if ($canDecide): ?>
                        <?php
                        // The stage can be approved when the student submitted
                        // it, its milestones are complete and the result was
                        // recorded. Both options always stay visible so the
                        // coordinator can see what can be done here.
                        $approveReady = $panel['requirementsComplete'] && $panel['result'] !== '' && (bool) $stageApp;
                        $nextLabel = $panels[$nextStageKey]['stage']['label'];
                        ?>
                        <span class="stage-approval-label"><i class="fas fa-forward" aria-hidden="true"></i> Stage progression</span>
                        <button type="button" class="btn btn-primary" data-approve-stage="<?= htmlspecialchars($key) ?>"
                                data-stage-label="<?= htmlspecialchars($stageName) ?>"
                                data-next-label="<?= htmlspecialchars($nextLabel) ?>"
                                data-ready="<?= $approveReady ? '1' : '0' ?>"
                                <?= $approveReady ? '' : 'disabled' ?>
                                title="<?= $approveReady
                                    ? 'Approve ' . htmlspecialchars($stageName) . ' and unlock ' . htmlspecialchars($nextLabel) . ' for the student'
                                    : 'This stage cannot be approved yet. Please complete the required items first.' ?>">
                            <i class="fas fa-check"></i> Approve Next Stage
                        </button>
                        <button type="button" class="btn btn-outline" data-disapprove-stage="<?= htmlspecialchars($key) ?>"
                                data-stage-label="<?= htmlspecialchars($stageName) ?>"
                                data-next-label="<?= htmlspecialchars($nextLabel) ?>"
                                <?= $stageApproved ? '' : 'disabled' ?>
                                title="<?= $stageApproved
                                    ? 'Revoke the approval of ' . htmlspecialchars($stageName) . ' and lock ' . htmlspecialchars($nextLabel) . ' again'
                                    : htmlspecialchars($stageName) . ' is not approved yet' ?>">
                            <i class="fas fa-undo"></i> Disapprove Next Stage
                        </button>
                        <span class="stage-approval-hint">
                            <?php if ($stageApproved): ?>
                                <strong><?= htmlspecialchars($nextLabel) ?></strong> is unlocked for the student.
                            <?php else: ?>
                                Approving <strong><?= htmlspecialchars($stageName) ?></strong> unlocks <strong><?= htmlspecialchars($nextLabel) ?></strong> for the student.
                            <?php endif; ?>
                        </span>
                        <?php else: ?>
                        <span class="stage-approval-hint">
                            <i class="fas fa-lock" aria-hidden="true"></i>
                            <?= $stageApp
                                ? 'This stage is locked — the coordinator cannot approve the progression from here.'
                                : 'The student has no application for this stage yet.' ?>
                        </span>
                        <?php endif; ?>
                    <?php else: ?>
                    <span class="stage-approval-hint"><i class="fas fa-flag-checkered" aria-hidden="true"></i>This is the last stage of the <?= htmlspecialchars($trackLabel) ?> track — there is no next stage to approve.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card" id="documentsSection-<?= htmlspecialchars($key) ?>">
            <div class="card-header"><h3>Documents and Requirements</h3></div>
            <div class="card-body">
                <?php if ($panel['required']): ?>
                <p class="field-hint" style="margin-bottom:0.75rem;">
                    The documents the <strong><?= htmlspecialchars($trackLabel) ?></strong> track requires for
                    <strong><?= htmlspecialchars($stageName) ?></strong>, together with everything the student uploaded.
                </p>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Document / Requirement</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$panel['documents']): ?>
                            <tr><td colspan="4" style="color:var(--gray-400);">No documents or requirements for this stage yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($panel['documents'] as $documentRow):
                                $requirement = $documentRow['requirement'];
                                $document    = $documentRow['upload'];
                                $badge = $requirement
                                    ? requirementStatusBadge($requirement, $document, $stageApp ?? [])
                                    : documentStatusBadge($document);
                                $label = $document ? $document['fileName'] : ($requirement['label'] ?? 'Document');
                                $type = $document['docType'] ?? ($requirement['label'] ?? '');
                                $submittedDate = $document && !empty($document['date']) ? date('M d, Y', strtotime($document['date'])) : '';
                                // The official receipt carries the details of the
                                // payment, so clicking it shows them.
                                $isReceipt = (bool) $document
                                    && classifyUploadDocType((string) ($document['docType'] ?? '')) === 'receipt';
                            ?>
                            <tr>
                                <td>
                                    <?php if ($isReceipt): ?>
                                    <details class="tpl-doc-records">
                                        <summary><strong><?= htmlspecialchars($label) ?></strong> <i class="fas fa-chevron-down" aria-hidden="true"></i></summary>
                                        <div class="tpl-doc-records-body">
                                            <div><span>Receipt Number</span><strong><?= htmlspecialchars($receiptDetails['receiptNumber'] !== '' ? $receiptDetails['receiptNumber'] : '—') ?></strong></div>
                                            <div><span>Amount</span><strong><?= htmlspecialchars($receiptDetails['amount'] !== '' ? $receiptDetails['amount'] : '—') ?></strong></div>
                                            <div><span>Payment Date</span><strong><?= htmlspecialchars($receiptDetails['date'] !== '' ? $receiptDetails['date'] : '—') ?></strong></div>
                                        </div>
                                    </details>
                                    <?php else: ?>
                                    <strong><?= htmlspecialchars($label) ?></strong>
                                    <?php endif; ?>
                                    <?php if ($type !== '' && $type !== $label): ?>
                                    <div style="font-size:0.75rem;color:var(--gray-500);"><?= htmlspecialchars($type) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="status-badge <?= $badge['class'] ?>"><?= htmlspecialchars($badge['label']) ?></span></td>
                                <td><?= $submittedDate !== '' ? htmlspecialchars($submittedDate) : '<span style="color:var(--gray-400);">—</span>' ?></td>
                                <td class="actions">
                                    <?php if ($document): ?>
                                        <?php if (uploadStoragePath($document)): ?>
                                        <a class="btn btn-sm btn-outline" href="<?= url('coordinator/download.php?id=' . urlencode($document['id'])) ?>"><i class="fas fa-download"></i> Download</a>
                                        <?php else: ?>
                                        <span style="color:var(--gray-400);font-size:.8rem;">Unavailable</span>
                                        <?php endif; ?>
                                        <select name="upload_status[<?= htmlspecialchars($document['id']) ?>]" style="padding:3px 8px;margin-left:.4rem;" title="Review status">
                                            <option value="submitted" <?= $document['status'] === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                                            <option value="verified" <?= in_array($document['status'], ['verified', 'approved'], true) ? 'selected' : '' ?>>Verified</option>
                                            <option value="incomplete" <?= $document['status'] === 'incomplete' ? 'selected' : '' ?>>Incomplete</option>
                                            <option value="revision" <?= $document['status'] === 'revision' ? 'selected' : '' ?>>Revision</option>
                                        </select>
                                    <?php elseif ($requirement && ($requirement['kind'] ?? '') === 'receipt'): ?>
                                        <span style="color:var(--gray-400);font-size:.8rem;">Not submitted yet</span>
                                    <?php else: ?>
                                    <span class="status-badge status-not-submitted">Not Submitted</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Workflow Records — <?= htmlspecialchars($stageName) ?></h3></div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-field">
                        <label>Receipt Number</label>
                        <p class="workflow-record-value"><?= htmlspecialchars($receiptDetails['receiptNumber'] !== '' ? $receiptDetails['receiptNumber'] : '—') ?></p>
                    </div>
                    <div class="form-field">
                        <label>Amount</label>
                        <p class="workflow-record-value"><?= htmlspecialchars($receiptDetails['amount'] !== '' ? $receiptDetails['amount'] : '—') ?></p>
                    </div>
                    <div class="form-field">
                        <label>Payment Date</label>
                        <p class="workflow-record-value"><?= htmlspecialchars($receiptDetails['date'] !== '' ? $receiptDetails['date'] : '—') ?></p>
                    </div>
                </div>
                <?php if ($receiptDetails['receiptNumber'] === '' && $receiptDetails['amount'] === '' && $receiptDetails['date'] === ''): ?>
                <p class="field-hint" style="margin:0;">
                    The student has not sent an official receipt for this stage yet. The receipt number, the amount
                    and the payment date come with the receipt.
                </p>
                <?php endif; ?>
                <p class="field-hint" style="margin-top:.75rem;">
                    The student-submitted Graduate School endorsement, payment record, readiness mark and presentation
                    result are handled in the <strong>Milestone Tracker</strong> and the
                    <strong>Schedule Presentation</strong> section of this stage.
                </p>
            </div>
        </div>

        <div class="card" id="scheduleSection-<?= htmlspecialchars($key) ?>">
            <div class="card-header"><h3><i class="fas fa-calendar-plus"></i> Schedule Presentation — <?= htmlspecialchars($stageName) ?></h3></div>
            <div class="card-body">
                <?php if ($panel['unlocked']): ?>
                <div class="alert alert-success" style="margin-bottom:1rem;">
                    <i class="fas fa-unlock"></i>
                    <div>All required documents and requirements are complete — <strong><?= htmlspecialchars($app['student'] ?? '') ?></strong> can proceed to have the presentation scheduled.</div>
                </div>
                <div class="req-checklist" id="scheduleNoticePanel-<?= htmlspecialchars($key) ?>" hidden>
                    <h4>Ready to Schedule</h4>
                    <p style="margin:0;font-size:.83rem;color:var(--gray-700);">
                        Every requirement of the <strong><?= htmlspecialchars($trackLabel) ?></strong> track for
                        <strong><?= htmlspecialchars($stageName) ?></strong> is complete.
                    </p>
                </div>
                <?php else: ?>
                <div class="alert alert-warning" style="margin-bottom:1rem;">
                    <i class="fas fa-lock"></i>
                    <div>Scheduling is locked. <strong><?= count($panel['missing']) ?></strong> requirement<?= count($panel['missing']) === 1 ? ' is' : 's are' ?>
                    still missing for the <strong><?= htmlspecialchars($trackLabel) ?></strong> track.</div>
                </div>
                <div class="req-checklist" id="missingPanel-<?= htmlspecialchars($key) ?>" hidden>
                    <h4>Missing Documents and Requirements</h4>
                    <ul style="margin:0;padding-left:1.1rem;font-size:.83rem;color:var(--gray-700);">
                        <?php foreach ($panel['missing'] as $missingItem): ?>
                        <li style="margin-bottom:.35rem;">
                            <strong><?= htmlspecialchars($missingItem['label']) ?></strong>
                            <span style="color:var(--gray-500);"> — <?= htmlspecialchars($missingItem['why']) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-actions stage-panel-actions">
            <?php if ($panel['unlocked'] && $stageApp): ?>
            <a href="<?= url('coordinator/schedule/add.php?app=' . (int) $stageApp['id']) ?>" class="btn btn-outline" id="schedulePresentationBtn-<?= htmlspecialchars($key) ?>"><i class="fas fa-calendar-plus"></i> Schedule Presentation</a>
            <?php else: ?>
            <button type="button" class="btn btn-outline" disabled title="Complete the missing requirements before scheduling the presentation"><i class="fas fa-calendar-plus"></i> Schedule Presentation</button>
            <?php endif; ?>

            <button type="button" class="btn btn-outline" id="scheduleLockBtn-<?= htmlspecialchars($key) ?>" aria-expanded="false"
                    aria-controls="<?= $panel['unlocked'] ? 'scheduleNoticePanel-' . htmlspecialchars($key) : 'missingPanel-' . htmlspecialchars($key) ?>">
                <i class="fas <?= $panel['unlocked'] ? 'fa-unlock' : 'fa-lock' ?>"></i> <?= $panel['unlocked'] ? 'Unlocked' : 'Locked' ?>
            </button>

            <div class="form-field" style="margin:0;min-width:230px;">
                <label>Presentation Result</label>
                <?php
                // The result can be recorded as soon as the stage has an
                // application. Whether the presentation was scheduled in the
                // portal is a hint, not a lock: the coordinator knows whether
                // the presentation already took place.
                $resultDisabled = empty($stageApp);
                ?>
                <select name="stage_data[<?= htmlspecialchars($key) ?>][result]" <?= $resultDisabled ? 'disabled' : '' ?>
                        title="<?= $resultDisabled ? 'The student has no application for this stage yet' : 'Record the result of the presentation' ?>">
                    <option value="" <?= empty($panel['result']) ? 'selected' : '' ?>>Not yet presented</option>
                    <option value="approved" <?= $panel['result'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="requires_revision" <?= $panel['result'] === 'requires_revision' ? 'selected' : '' ?>>Requires Revision</option>
                </select>
                <?php if (!$resultDisabled && empty($panel['schedule'])): ?>
                <p class="field-hint" style="margin:.35rem 0 0;">
                    <i class="fas fa-info-circle" style="margin-right:5px;"></i>
                    The presentation is not scheduled yet. You can still record the result if it already took place.
                </p>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endforeach; ?>

    <div class="form-actions no-print">
        <button type="submit" class="btn btn-primary" id="saveProcessBtn"><i class="fas fa-save"></i> Save Process</button>
        <a href="<?= url('coordinator/applications/manage.php') ?>" class="btn btn-outline" id="cancelProcessBtn">Cancel</a>
    </div>
</form>

<?php endif; ?>

<script>
(function () {
    const form = document.getElementById('processForm');
    if (!form) return;

    const slider = document.getElementById('stageSlider');
    const track = document.getElementById('stageSliderTrack');
    const cards = [...document.querySelectorAll('.stage-slider-card')];
    const panels = [...document.querySelectorAll('.stage-panel')];
    const selectedField = document.getElementById('selectedStageField');
    const approveField = document.getElementById('approveStageField');
    const disapproveField = document.getElementById('disapproveStageField');
    const noticeBox = document.getElementById('pageNotifications');
    let current = <?= (int) array_search($selectedStage, $stageKeys, true) ?> >= 0 ? <?= (int) array_search($selectedStage, $stageKeys, true) ?> : 0;

    /* ------------------------------------------------------------------
     * In-system notifications
     *
     * A short message inside the page, never a browser pop-up. Temporary
     * notices dismiss themselves; a notice with actions stays until the
     * coordinator answers.
     * ------------------------------------------------------------------ */
    function notify(message, type, options) {
        if (!noticeBox) return null;
        const settings = options || {};
        const notice = document.createElement('div');
        notice.className = 'page-notice is-' + (type || 'info');
        const text = document.createElement('span');
        text.className = 'page-notice-text';
        text.textContent = message;
        notice.appendChild(text);

        if (settings.actions && settings.actions.length) {
            const actions = document.createElement('div');
            actions.className = 'page-notice-actions';
            settings.actions.forEach(function (action) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn ' + (action.variant || 'btn-outline');
                button.textContent = action.label;
                button.addEventListener('click', action.onClick);
                actions.appendChild(button);
            });
            notice.appendChild(actions);
        } else {
            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'btn btn-outline';
            close.setAttribute('aria-label', 'Dismiss');
            close.innerHTML = '&times;';
            close.addEventListener('click', function () { notice.remove(); });
            notice.appendChild(close);
        }

        noticeBox.appendChild(notice);
        if (!settings.sticky) {
            setTimeout(function () { notice.remove(); }, 6000);
        }
        return notice;
    }

    /* ------------------------------------------------------------------
     * The approval buttons follow the saved state and the pending decision:
     * a stage that is not approved yet can be approved, a stage that is
     * approved can be revoked, and a pending decision can still be changed.
     * ------------------------------------------------------------------ */
    function refreshApprovalButtons() {
        document.querySelectorAll('.stage-panel').forEach(function (panel) {
            const stageKey = panel.dataset.stage;
            const savedApproved = panel.dataset.savedApproved === '1';
            const pending = approveField && approveField.value === stageKey
                ? 'approve'
                : (disapproveField && disapproveField.value === stageKey ? 'disapprove' : '');
            const approveBtn = panel.querySelector('[data-approve-stage]');
            const disapproveBtn = panel.querySelector('[data-disapprove-stage]');
            if (approveBtn) {
                const ready = approveBtn.dataset.ready === '1';
                // Approved already: there is nothing left to approve. Otherwise
                // the stage has to be complete before it can be approved.
                approveBtn.disabled = savedApproved || !ready;
                approveBtn.classList.toggle('is-pending', pending === 'approve');
            }
            if (disapproveBtn) {
                disapproveBtn.disabled = !savedApproved && pending !== 'disapprove';
                disapproveBtn.classList.toggle('is-pending', pending === 'disapprove');
            }
        });
    }

    // Both decisions are recorded as pending and are applied by Save Process.
    function recordDecision(stageKey, decision) {
        if (approveField) approveField.value = decision === 'approve' ? stageKey : '';
        if (disapproveField) disapproveField.value = decision === 'disapprove' ? stageKey : '';

        const index = cards.findIndex(function (c) { return c.dataset.stage === stageKey; });
        const nextCard = index >= 0 ? cards[index + 1] : null;
        if (nextCard) {
            const badge = nextCard.querySelector('.stage-slider-badge');
            if (badge) {
                badge.textContent = decision === 'approve' ? 'Pending Approval' : 'Pending Revoke';
                badge.className = 'status-badge ' + (decision === 'approve' ? 'status-review' : 'status-revision') + ' stage-slider-badge';
            }
            nextCard.classList.toggle('is-locked', decision === 'disapprove');
            nextCard.classList.toggle('is-approved', decision === 'approve');
        }
        refreshApprovalButtons();

        notify(
            decision === 'approve'
                ? 'Stage approval is pending. Click Save Process to apply your changes.'
                : 'Stage disapproval is pending. Click Save Process to apply your changes.',
            'info',
            { actions: [
                { label: 'Undo', variant: 'btn-outline', onClick: function () {
                    if (approveField) approveField.value = '';
                    if (disapproveField) disapproveField.value = '';
                    const card = document.querySelector('.stage-slider-card[data-stage="' + stageKey + '"]');
                    const badge = card ? card.querySelector('.stage-slider-badge') : null;
                    if (badge) {
                        badge.textContent = card.classList.contains('is-locked') ? 'Locked' : 'Approved';
                        badge.className = 'status-badge ' + (card.classList.contains('is-locked') ? 'status-not-submitted' : 'status-submitted') + ' stage-slider-badge';
                    }
                    refreshApprovalButtons();
                    notice.remove();
                } }
            ] }
        );
    }

    document.querySelectorAll('[data-approve-stage]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (button.dataset.ready !== '1') {
                notify('This stage cannot be approved yet. Please complete the required items first.', 'warning');
                return;
            }
            recordDecision(button.dataset.approveStage, 'approve');
        });
    });

    document.querySelectorAll('[data-disapprove-stage]').forEach(function (button) {
        button.addEventListener('click', function () {
            recordDecision(button.dataset.disapproveStage, 'disapprove');
        });
    });

    // Remember the original disabled state of every control so a panel can be
    // shown again without re-enabling something that was disabled on purpose.
    panels.forEach(function (panel) {
        panel.querySelectorAll('input, select, textarea, button').forEach(function (control) {
            control.dataset.origDisabled = control.disabled ? '1' : '0';
        });
    });

    function slideTo(index) {
        if (!cards.length) return;
        current = Math.max(0, Math.min(cards.length - 1, index));
        const card = cards[current];
        const stageKey = card.dataset.stage;

        // The track is centred when everything fits; when it is wider than the
        // window it slides so the selected stage stays in view.
        const viewport = document.querySelector('.stage-slider-viewport');
        if (track && viewport) {
            const maxOffset = Math.max(0, track.scrollWidth - viewport.clientWidth);
            const cardCenter = card.offsetLeft + card.offsetWidth / 2;
            let offset = cardCenter - (viewport.clientWidth / 2);
            offset = Math.max(-maxOffset, Math.min(0, offset));
            track.style.transform = 'translateX(' + offset + 'px)';
        }
        cards.forEach(function (c, i) {
            c.classList.toggle('is-selected', i === current);
            c.setAttribute('aria-pressed', i === current ? 'true' : 'false');
        });
        panels.forEach(function (panel) {
            const active = panel.dataset.stage === stageKey;
            panel.hidden = !active;
            panel.querySelectorAll('input, select, textarea, button').forEach(function (control) {
                control.disabled = !active || control.dataset.origDisabled === '1';
            });
        });
        if (selectedField) selectedField.value = stageKey;

        const prevBtn = document.getElementById('stageSliderPrev');
        const nextBtn = document.getElementById('stageSliderNext');
        if (prevBtn) prevBtn.disabled = current === 0;
        if (nextBtn) nextBtn.disabled = current === cards.length - 1;
    }

    cards.forEach(function (card, index) {
        card.addEventListener('click', function () { slideTo(index); });
    });
    document.getElementById('stageSliderPrev')?.addEventListener('click', function () { slideTo(current - 1); });
    document.getElementById('stageSliderNext')?.addEventListener('click', function () { slideTo(current + 1); });

    // The locked / unlocked button of a stage shows what is still missing.
    document.querySelectorAll('[id^="scheduleLockBtn-"]').forEach(function (button) {
        button.addEventListener('click', function () {
            const panelId = button.getAttribute('aria-controls');
            const panel = panelId ? document.getElementById(panelId) : null;
            if (!panel) return;
            const open = button.getAttribute('aria-expanded') === 'true';
            if (open) {
                panel.setAttribute('hidden', '');
            } else {
                panel.removeAttribute('hidden');
            }
            button.setAttribute('aria-expanded', open ? 'false' : 'true');
        });
    });

    slideTo(current);
    refreshApprovalButtons();

    /* ------------------------------------------------------------------
     * Save Process
     *
     * One button saves every pending change of the page. It is guarded
     * against a double click so the same change is never sent twice.
     * ------------------------------------------------------------------ */
    const saveBtn = document.getElementById('saveProcessBtn');
    if (saveBtn) {
        form.addEventListener('submit', function () {
            if (form.dataset.saving === '1') return;
            form.dataset.saving = '1';
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
