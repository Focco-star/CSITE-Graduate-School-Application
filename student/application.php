<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

/* ------------------------------------------------------------------
 * Helpers shared by the Application form
 * ------------------------------------------------------------------ */

/** True when the browser actually sent a file. */
function csiteUploadHasFile($file): bool
{
    return is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

/** Validates a document upload. Returns an error message, or null when it is fine. */
function csiteCheckUpload($file, string $docType, string $stageKey, array $stages, string $email, string $track): ?string
{
    if ($docType === '') {
        return 'Please select a document type.';
    }
    if ($stageKey === '' || !isset($stages[$stageKey])) {
        return 'Please select a stage.';
    }
    if (($seqError = validateUploadSequence($email, $track, $stageKey, $docType)) !== null) {
        return $seqError;
    }
    if (!csiteUploadHasFile($file)) {
        return 'Please select a file to upload.';
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return 'The file could not be uploaded. Try again.';
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'doc', 'docx'], true)) {
        return 'Only PDF, DOC, or DOCX files are allowed.';
    }
    if ($file['size'] > 20 * 1024 * 1024) {
        return 'File exceeds the 20 MB maximum size.';
    }
    return null;
}

/** Stores the file and records it against the student. Throws RuntimeException on failure. */
function csiteSaveUpload(array $file, string $docType, string $stageKey, array $stages, string $email, int $userId): void
{
    $saved  = storeStudentUpload($file);
    $linked = latestApplicationForEmail($email);
    recordStudentDocument([
        'applicationId' => $linked['id'] ?? null,
        'studentEmail'  => $email,
        'fileName'      => $file['name'],
        'docType'       => $docType,
        'stage'         => $stages[$stageKey]['label'],
        'size'          => $file['size'],
        'storedFile'    => $saved['storedFile'],
        'mimeType'      => $saved['mimeType'],
    ], $email, $userId);
}

/** Parses a Y-m-d date. Returns the date string, null when blank, or false when invalid. */
function csiteParseDate($raw)
{
    $raw = trim((string) $raw);
    if ($raw === '') {
        return null;
    }
    $d = DateTime::createFromFormat('Y-m-d', $raw);
    return ($d && $d->format('Y-m-d') === $raw) ? $raw : false;
}

/** Normalises a stored / posted enrolment date to Y-m-d for the editable date field. */
function csiteDateValue($raw): string
{
    $raw = trim((string) $raw);
    if ($raw === '') {
        return '';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
        return $raw;
    }
    $ts = strtotime($raw);
    return $ts ? date('Y-m-d', $ts) : '';
}

/** Editable (typeable) date input with a calendar button. The calendar itself lives in script.js. */
function csiteDateField(string $name, $value): string
{
    return '<div class="date-picker" data-date-picker>'
        . '<input type="text" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars(csiteDateValue($value)) . '"'
        . ' placeholder="YYYY-MM-DD" inputmode="numeric" autocomplete="off" maxlength="10"'
        . ' pattern="\d{4}-\d{2}-\d{2}" title="Use the format YYYY-MM-DD">'
        . '<button type="button" class="date-picker-btn" data-date-toggle aria-label="Open calendar" title="Open calendar">'
        . '<i class="fas fa-calendar-alt"></i></button>'
        . '</div>';
}

/**
 * Saves the enrolment / start date. It is remembered in the session and also written to
 * students.enroll_date when that column exists (a missing column is ignored, not fatal).
 */
function csiteSaveEnrollDate(?PDO $pdo, int $userId, string $date): void
{
    $_SESSION['enroll_date_override'] = $date;
    if (!$pdo || $userId <= 0) {
        return;
    }
    try {
        $pdo->prepare('UPDATE students SET enroll_date = :enroll_date WHERE user_id = :user_id')
            ->execute(['enroll_date' => $date, 'user_id' => $userId]);
    } catch (Throwable $e) {
        // column not present in this schema; the session value above still applies
    }
}

/**
 * Uploading a document before the application is submitted leaves a placeholder
 * application row behind for that stage (paper_title = "Untitled paper"), because
 * the document needs something to be linked to. The student submits the real
 * application afterwards, so that placeholder is filled in here instead of
 * leaving an empty extra application for the coordinator.
 *
 * Returns the application id that was reused, or 0 when a new row is needed.
 */
function csiteFillPlaceholderApplication(PDO $pdo, int $userId, string $stageLabel, string $title): int
{
    try {
        $find = $pdo->prepare(
            'SELECT application_id FROM applications
             WHERE user_id = :user_id AND archived_at IS NULL
               AND presentation_stage = :stage
               AND (paper_title IS NULL OR TRIM(paper_title) = "" OR paper_title = "Untitled paper")
             ORDER BY submitted_at ASC LIMIT 1'
        );
        $find->execute(['user_id' => $userId, 'stage' => $stageLabel]);
        $id = (int) ($find->fetchColumn() ?: 0);
        if ($id <= 0) {
            return 0;
        }
        $pdo->prepare(
            'UPDATE applications
             SET paper_title = :paper_title, status = :status
             WHERE application_id = :id AND user_id = :user_id'
        )->execute([
            'paper_title' => $title,
            'status'      => 'submitted',
            'id'          => $id,
            'user_id'     => $userId,
        ]);
        return $id;
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * Drops the empty placeholder record the document upload left in the session
 * store, so the student ends up with exactly one application per stage.
 */
function csiteDropPlaceholderSessionRecord(string $email, string $stageKey): void
{
    $apps = storeGet('applications');
    if (!$apps) {
        return;
    }
    $kept = array_values(array_filter($apps, static function ($a) use ($email, $stageKey) {
        if (strcasecmp((string) ($a['studentEmail'] ?? ''), $email) !== 0) {
            return true;
        }
        $key = $a['stageKey'] ?? stageKeyFromLabel((string) ($a['stage'] ?? ''));
        if ($key !== $stageKey) {
            return true;
        }
        return trim((string) ($a['title'] ?? '')) !== '';
    }));
    if (count($kept) !== count($apps)) {
        storeSet('applications', $kept);
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sessionUser = $_SESSION['user'] ?? [];
$mockStudent = currentStudentProfile($mockStudent);

if (!empty($sessionUser)) {
    $mockStudent['name']  = $sessionUser['full_name'] ?? $sessionUser['name'] ?? $mockStudent['name'];
    $mockStudent['email'] = $sessionUser['email'] ?? $mockStudent['email'];
    if (!empty($sessionUser['program'])) {
        $mockStudent['program'] = $sessionUser['program'];
    }
    if (!empty($sessionUser['program_name'])) {
        $mockStudent['program_name'] = $sessionUser['program_name'];
    }
}
if (!empty($_SESSION['enroll_date_override'])) {
    $mockStudent['enroll_date'] = $_SESSION['enroll_date_override'];
}

$pageTitle   = 'Application';
$role        = 'student';
$currentPage = 'application';
$userName    = $mockStudent['name'];
$track       = getTrackForProgram($mockStudent['program']);
$trackLabel  = getTrackLabel($track);
$workflow    = getWorkflow($track);
$stages      = [];
foreach ($workflow['stages'] as $s) {
    $stages[$s['key']] = $s;
}
$isThesis      = $track === 'thesis';
$popup         = null;   // ['title' => ..., 'message' => ...] -> shown as the success pop-up
$appError      = '';
$userId        = (int) ($sessionUser['user_id'] ?? $_SESSION['user_id'] ?? 0);

$progress = getStudentProgress($mockStudent['email'], $track);
$currentStageKey = $mockStudent['current_stage'] ?? '';
foreach ($progress as $p) {
    if (!in_array($p['stageStatus'], ['completed', 'approved'], true)) {
        $currentStageKey = $p['stageKey'];
        break;
    }
}
if (!isset($stages[$currentStageKey])) {
    $currentStageKey = array_key_first($stages) ?: 'proposal';
}

/* ------------------------------------------------------------------
 * The draft
 *
 * The values the student typed live in the database, so they survive a trip
 * through the Dashboard, a refresh, or a logout and login.
 * ------------------------------------------------------------------ */
$draft = loadApplicationDraft($userId);

/* ------------------------------------------------------------------
 * Draft endpoint
 *
 * The form saves itself in the background (and when the student leaves the
 * page), so it posts here. It answers with a 1x1 pixel and never renders a
 * page of its own.
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'draft') {
    saveApplicationDraft($userId, [
        'title'       => $_POST['title'] ?? '',
        'adviser'     => $_POST['adviser'] ?? '',
        'abstract'    => $_POST['abstract'] ?? '',
        'enroll_date' => $_POST['enroll_date'] ?? '',
        'stage_key'   => $_POST['stage'] ?? '',
        'doc_type'    => $_POST['docType'] ?? '',
        'file_name'   => $_POST['file_name'] ?? '',
    ]);
    header('Content-Type: image/gif');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    exit;
}

/* ------------------------------------------------------------------
 * Discard endpoint
 *
 * The student throws away the values they typed. Only the four unsent
 * fields are cleared - uploaded documents, submitted applications and the
 * student record are never touched.
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'discard_draft') {
    discardApplicationDraftFields($userId);
    header('Content-Type: image/gif');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    exit;
}

/* ------------------------------------------------------------------
 * The values of the form
 *
 * What was typed in this request wins, then the saved draft, then the
 * application the student already submitted, then the student profile.
 * ------------------------------------------------------------------ */
$postedValues = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedValues = [
        'title'       => trim((string) ($_POST['title'] ?? '')),
        'adviser'     => trim((string) ($_POST['adviser'] ?? '')),
        'abstract'    => trim((string) ($_POST['abstract'] ?? '')),
        'enroll_date' => trim((string) ($_POST['enroll_date'] ?? '')),
        'stage'       => trim((string) ($_POST['stage'] ?? '')),
        'docType'     => trim((string) ($_POST['docType'] ?? '')),
    ];
}

$existingApplication = applicationForStage($mockStudent['email'], $currentStageKey);

$formValues = [
    'title'       => (string) ($mockStudent['title'] ?? ''),
    'adviser'     => (string) ($mockStudent['adviser'] ?? ''),
    'abstract'    => '',
    'enroll_date' => (string) ($mockStudent['enroll_date'] ?? ''),
    'stage'       => $currentStageKey,
    'docType'     => '',
];
if ($existingApplication) {
    // The student already submitted this stage: keep their details on the form
    // so they can attach further documents without retyping anything.
    $formValues['title']   = (string) ($existingApplication['title'] ?? $formValues['title']);
    $formValues['adviser'] = (string) ($existingApplication['adviser'] ?? $formValues['adviser']);
}
if ($draft) {
    foreach (['title', 'adviser', 'abstract', 'enroll_date', 'stage', 'docType'] as $field) {
        if (($draft[$field] ?? '') !== '') {
            $formValues[$field] = (string) $draft[$field];
        }
    }
}
if ($postedValues !== null) {
    $formValues = array_merge($formValues, $postedValues);
}
if (!isset($stages[$formValues['stage']])) {
    $formValues['stage'] = $currentStageKey;
}

// The Milestone Tracker sends the student here for a particular stage.
$requestedStage = trim((string) ($_GET['stage'] ?? ''));
if ($postedValues === null && isset($stages[$requestedStage])) {
    $formValues['stage'] = $requestedStage;
    $existingApplication = applicationForStage($mockStudent['email'], $requestedStage);
    if ($existingApplication) {
        $formValues['title']   = (string) ($existingApplication['title'] ?? $formValues['title']);
        $formValues['adviser'] = (string) ($existingApplication['adviser'] ?? $formValues['adviser']);
    }
}
$draftFileName = (string) ($draft['file_name'] ?? '');
$draftSavedAt  = (string) ($draft['updated_at'] ?? '');

/* ------------------------------------------------------------------
 * POST: Submit Application (with the attached document)
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'application') {
    $title       = $formValues['title'];
    $adviser     = formatPersonName($formValues['adviser']);
    $stageKey    = $formValues['stage'];
    $abstract    = $formValues['abstract'];
    $enrollDate  = csiteParseDate($formValues['enroll_date']);
    $docType     = $formValues['docType'];
    $file        = $_FILES['document'] ?? null;
    $hasFile     = csiteUploadHasFile($file);

    // An application the student already submitted for this stage is updated
    // rather than created again, and it is not blocked by the stage sequence.
    $existing = applicationForStage($mockStudent['email'], $stageKey);
    $stageLockError = $existing
        ? null
        : (isset($stages[$stageKey]) ? validateApplicationStageSequence($progress, $stages, $stageKey) : null);

    // What the student will have for this stage once this request is done.
    $stageUploads = [];
    foreach (uploadsForEmail($mockStudent['email']) as $uploaded) {
        if (stageKeyFromLabel((string) ($uploaded['stage'] ?? '')) === $stageKey) {
            $stageUploads[] = $uploaded;
        }
    }

    $hasDetails = $title !== '' || $adviser !== '' || $abstract !== '' || $formValues['enroll_date'] !== '';
    // The fields are required for a NEW application. An application the
    // student already sent keeps its details - they only attach the
    // documents that are still outstanding for that stage.
    $requireFields = !$existing;
    $missing = [];

    if ($hasDetails || $requireFields) {
        $missing = applicationSubmissionRequirements(
            $stages[$stageKey] ?? [],
            $track,
            $stageKey,
            [
                'title'       => $title,
                'adviser'     => $adviser,
                'abstract'    => $abstract,
                'enroll_date' => $formValues['enroll_date'],
            ],
            $stageUploads
        );
        // The document that comes with this request counts towards the
        // requirements of the stage.
        if ($hasFile && $docType !== '' && classifyUploadDocType($docType) === 'paper') {
            $missing['documents'] = array_values(array_diff($missing['documents'], [$stages[$stageKey]['documents'][0]['label'] ?? '']));
        }
        if (!$requireFields) {
            $missing['fields'] = [];
        }
    }

    if (!isset($stages[$stageKey])) {
        $appError = 'Please select a valid presentation stage.';
    } elseif ($stageLockError !== null) {
        $appError = $stageLockError;
    } elseif ($enrollDate === false) {
        $appError = 'Please enter a valid enrollment / start date.';
    } elseif (!$hasDetails && !$hasFile) {
        $appError = 'Fill in your application details or attach a document before you submit.';
    } elseif ($requireFields && $missing['fields']) {
        $appError = 'Please complete: ' . implode(', ', $missing['fields']) . '.';
    } elseif ($missing['documents']) {
        $appError = 'Your application needs these documents for this stage: ' . implode(', ', $missing['documents']) . '.';
    } elseif ($hasFile && ($uploadCheck = csiteCheckUpload($file, $docType, $stageKey, $stages, $mockStudent['email'], $track)) !== null) {
        $appError = $uploadCheck;
    } else {
        $applicationSaved = false;
        $documentSaved = false;
        try {
            $pdo = DB::getConnection();
            $studentLookup = $pdo->prepare(
                'SELECT s.student_id
                 FROM students s
                 WHERE s.user_id = :user_id AND s.archived_at IS NULL
                 LIMIT 1'
            );
            $studentLookup->execute([
                'user_id' => $userId,
            ]);
            $studentId = (int) ($studentLookup->fetchColumn() ?: 0);

            if ($studentId <= 0) {
                throw new RuntimeException('Your student profile was not found. Please sign in again or contact the coordinator.');
            }

            $stageLabel = $stages[$stageKey]['label'];

            $pdo->beginTransaction();

            if ($hasDetails) {
                // The document may have been uploaded first, so a placeholder
                // application may already exist for this stage - fill that one
                // in instead of adding a second, empty application.
                $reusedApplicationId = csiteFillPlaceholderApplication($pdo, $userId, $stageLabel, $title);
                if ($reusedApplicationId > 0) {
                    $pdo->prepare(
                        'UPDATE applications
                         SET paper_title = :paper_title, status = :status
                         WHERE application_id = :id AND user_id = :user_id'
                    )->execute([
                        'paper_title' => $title,
                        'status'      => 'submitted',
                        'id'          => $reusedApplicationId,
                        'user_id'     => $userId,
                    ]);
                } elseif ($existing) {
                    // The application for this stage already exists: refresh the
                    // details the student filled in, but leave the status to
                    // the coordinator.
                    $pdo->prepare(
                        'UPDATE applications
                         SET paper_title = COALESCE(NULLIF(:paper_title, ""), paper_title),
                             presentation_stage = :presentation_stage
                         WHERE application_id = :id AND user_id = :user_id'
                    )->execute([
                        'paper_title'        => $title,
                        'presentation_stage' => $stageLabel,
                        'id'                 => (int) $existing['id'],
                        'user_id'            => $userId,
                    ]);
                } else {
                    $insertApplication = $pdo->prepare(
                        'INSERT INTO applications (user_id, presentation_stage, paper_title, status)
                         VALUES (:user_id, :presentation_stage, :paper_title, :status)'
                    );
                    $insertApplication->execute([
                        'user_id' => $userId,
                        'presentation_stage' => $stageLabel,
                        'paper_title' => $title,
                        'status' => 'submitted',
                    ]);
                }

                $pdo->prepare('UPDATE students SET adviser_name = :adviser_name WHERE user_id = :user_id')
                    ->execute(['adviser_name' => $adviser, 'user_id' => $userId]);

                $identityStmt = $pdo->prepare(
                    'SELECT s.student_id, s.first_name, s.last_name, s.middle_initial, s.age, s.gender, s.program, s.track, u.email, u.full_name
                     FROM students s
                     INNER JOIN users u ON u.user_id = s.user_id
                     WHERE s.user_id = :user_id
                     LIMIT 1'
                );
                $identityStmt->execute(['user_id' => $userId]);
                $identity = $identityStmt->fetch() ?: [];
                if ($identity) {
                    $programCode = programCodeForStudent(['program' => $identity['program']]);
                    $pdo->prepare(
                        'UPDATE students
                         SET first_name = :first_name,
                             last_name = :last_name,
                             middle_initial = :middle_initial,
                             age = :age,
                             gender = :gender,
                             program = :program,
                             track = :track,
                             adviser_name = :adviser_name
                         WHERE user_id = :user_id'
                    )->execute([
                        'first_name' => formatPersonName($identity['first_name']),
                        'last_name' => formatPersonName($identity['last_name']),
                        'middle_initial' => $identity['middle_initial'],
                        'age' => (int) $identity['age'],
                        'gender' => $identity['gender'],
                        'program' => $programCode,
                        'track' => $identity['track'],
                        'adviser_name' => $adviser,
                        'user_id' => $userId,
                    ]);
                    $pdo->prepare('UPDATE users SET full_name = :full_name WHERE user_id = :user_id')
                        ->execute([
                            'full_name' => canonicalStudentName($identity['first_name'], $identity['last_name'], $identity['middle_initial']),
                            'user_id' => $userId,
                        ]);
                }
            }
            $pdo->commit();
            $applicationSaved = true;

            if ($hasDetails) {
                $identity = databaseStudentIdentity();
                if ($identity) {
                    $sessionUser = $_SESSION['user'] ?? [];
                    $sessionUser['full_name'] = $identity['full_name'];
                    $sessionUser['email'] = $identity['email'];
                    $_SESSION['user'] = $sessionUser;
                    upsertSessionStudent($identity, ['adviser' => $adviser, 'title' => $title]);
                    $mockStudent = array_merge($mockStudent, [
                        'name' => $identity['full_name'],
                        'email' => $identity['email'],
                        'program' => $identity['program'],
                        'program_name' => $identity['program_name'],
                        'track' => $identity['track'],
                        'adviser' => $adviser,
                        'title' => $title,
                    ]);
                }

                csiteDropPlaceholderSessionRecord($mockStudent['email'], $stageKey);
                addApplicationRecord([
                    'studentEmail' => $mockStudent['email'],
                    'student' => $mockStudent['name'],
                    'program' => $mockStudent['program'],
                    'stageKey' => $stageKey,
                    'title' => $title,
                    'adviser' => $adviser,
                    'abstract' => $abstract,
                ]);

                if ($enrollDate !== null) {
                    csiteSaveEnrollDate($pdo, $userId, $enrollDate);
                }
            }

            if ($hasFile) {
                csiteSaveUpload($file, $docType, $stageKey, $stages, $mockStudent['email'], $userId);
                $documentSaved = true;
            }

            // Everything is saved: the draft is no longer needed.
            clearApplicationDraft($userId);
            $draft = null;

            // Short, friendly notifications in the style of the Student
            // Dashboard's Milestone Tracker.
            if ($hasDetails && $documentSaved) {
                $popup = [
                    'title'   => 'Application submitted successfully.',
                    'message' => 'Your application and "' . $file['name'] . '" were sent to the coordinator for review.',
                ];
            } elseif ($hasDetails) {
                $popup = [
                    'title'   => 'Application submitted successfully.',
                    'message' => 'Your application was sent to the coordinator for review.',
                ];
            } else {
                $popup = [
                    'title'   => 'Document uploaded successfully.',
                    'message' => '"' . $file['name'] . '" was sent to the coordinator for review.',
                ];
            }
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // Keep what the student typed so nothing is lost.
            saveApplicationDraft($userId, [
                'title'       => $title,
                'adviser'     => $adviser,
                'abstract'    => $abstract,
                'enroll_date' => $formValues['enroll_date'],
                'stage_key'   => $stageKey,
                'doc_type'    => $docType,
                'file_name'   => $hasFile ? (string) $file['name'] : '',
            ]);
            $appError = $applicationSaved
                ? 'Your application was saved, but the document could not be uploaded: ' . $e->getMessage()
                : $e->getMessage();
        }
    }

    if ($appError === '') {
        // The submission went through; the form is back to the saved application.
        $existingApplication = applicationForStage($mockStudent['email'], $formValues['stage']);
        if ($existingApplication) {
            $formValues['title'] = (string) ($existingApplication['title'] ?? $formValues['title']);
        }
        $formValues['abstract'] = '';
        $formValues['docType'] = '';
        $draftFileName = '';
    }
}

/* ------------------------------------------------------------------
 * View data
 * ------------------------------------------------------------------ */
$docTypesByStage = [];
foreach ($stages as $key => $stage) {
    $docTypesByStage[$key] = documentTypesForStage($stage, $track, $key);
}

$requiredDocs = requiredDocumentsForStage($track, $formValues['stage']);
$stageDefinition = $stages[$formValues['stage']] ?? [];
$missingRequirements = applicationSubmissionRequirements(
    $stageDefinition,
    $track,
    $formValues['stage'],
    [
        'title'       => $formValues['title'],
        'adviser'     => $formValues['adviser'],
        'abstract'    => $formValues['abstract'],
        'enroll_date' => $formValues['enroll_date'],
    ],
    array_values(array_filter(uploadsForEmail($mockStudent['email']), static function ($u) use ($formValues) {
        return stageKeyFromLabel((string) ($u['stage'] ?? '')) === $formValues['stage'];
    }))
);

// The documents still missing for every stage of the track, so the browser
// check matches the stage the student picked.
$missingDocumentsByStage = [];
foreach ($stages as $key => $stage) {
    $stageUploads = array_values(array_filter(uploadsForEmail($mockStudent['email']), static function ($u) use ($key) {
        return stageKeyFromLabel((string) ($u['stage'] ?? '')) === $key;
    }));
    $missingDocumentsByStage[$key] = applicationSubmissionRequirements(
        $stage,
        $track,
        $key,
        ['title' => 'x', 'adviser' => 'x', 'abstract' => 'x', 'enroll_date' => 'x'],
        $stageUploads
    )['documents'];
}

/* ------------------------------------------------------------------
 * Where the student stands, in the compact style of the Milestone
 * Tracker on the Student Dashboard.
 * ------------------------------------------------------------------ */
$stageMilestones = stageMilestoneSteps($mockStudent['email'], $track, $formValues['stage']);
$requirementMilestones = [];
foreach ($stageMilestones as $step) {
    if (in_array($step['key'] ?? '', ['paper', 'adviser_endorsement', 'coord_review', 'grad_school_endorsement', 'payment', 'ready_for_presentation'], true)) {
        $requirementMilestones[] = $step;
    }
}
$requirementsComplete = $requirementMilestones !== [];
foreach ($requirementMilestones as $step) {
    if (($step['state']['status'] ?? 'pending') !== 'done') {
        $requirementsComplete = false;
        break;
    }
}
$paperAttached = !in_array($stageDefinition['documents'][0]['label'] ?? '', $missingRequirements['documents'], true)
    && !empty($stageDefinition['documents'][0]['label'] ?? '');
$applicationSent = $existingApplication !== null;

if ($applicationSent && $requirementsComplete) {
    $statusTag = 'done';
    $statusText = 'Requirements completed.';
    $statusNote = 'Waiting for coordinator approval.';
} elseif ($applicationSent) {
    $statusTag = 'done';
    $statusText = 'Document submitted for review.';
    $statusNote = 'Your documents are with the coordinator.';
} elseif ($paperAttached) {
    $statusTag = 'active';
    $statusText = 'Ready to submit.';
    $statusNote = 'Your paper is attached. Submit your application when you are ready.';
} else {
    $statusTag = 'flagged';
    $statusText = 'Action needed';
    $statusNote = 'Attach your paper to submit your application.';
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h2><?= htmlspecialchars($trackLabel) ?> Presentation Application</h2>
    <p>Fill in your application, attach your documents and send everything in one go.</p>
</div>

<div class="track-indicator track-<?= htmlspecialchars($track) ?>">
    <span class="track-indicator-badge"><?= htmlspecialchars($trackLabel) ?> Track</span>
    <strong class="track-indicator-program"><?= htmlspecialchars($mockStudent['program_name']) ?></strong>
</div>

<?php if ($appError): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($appError) ?></div>
<?php endif; ?>

<div class="attach-status draft-notice" style="margin-bottom:1rem;">
    <span class="ms-step-tag done"><?= $draft ? 'Draft saved' : 'Draft' ?></span>
    <span class="attach-status-note">
        <?php if ($draft): ?>
            Your entries are kept on this page — you can leave and come back to them.
        <?php else: ?>
            Your entries are saved as you type, so you can leave this page and come back to them.
        <?php endif; ?>
    </span>
    <button type="button" class="btn btn-sm btn-outline" id="discardDraftBtn"><i class="fas fa-trash"></i> Discard Draft</button>
</div>

<div class="card" id="applicationFormCard">
    <div class="card-header"><h3><i class="fas fa-file-alt"></i> Application &amp; Document Upload</h3></div>
    <div class="card-body">
        <form class="form-card" id="applicationForm" style="box-shadow:none;border:none;padding:0;" method="post" action="<?= url('student/application.php') ?>" enctype="multipart/form-data" data-validate>
            <input type="hidden" name="form" value="application">
            <div class="form-row">
                <div class="form-field">
                    <label>Full Name</label>
                    <input type="text" value="<?= htmlspecialchars($mockStudent['name']) ?>" readonly>
                </div>
                <div class="form-field">
                    <label>ADZU Email</label>
                    <input type="email" value="<?= htmlspecialchars($mockStudent['email']) ?>" readonly>
                </div>
            </div>
            <div class="form-row">
                <div class="form-field">
                    <label>Program</label>
                    <input type="text" value="<?= htmlspecialchars($mockStudent['program_name']) ?>" readonly>
                </div>
                <div class="form-field">
                    <label>Presentation Stage <span class="required">*</span></label>
                    <select name="stage" id="applicationStage" required>
                        <?php foreach ($stages as $key => $s): ?>
                        <?php if (validateApplicationStageSequence($progress, $stages, $key) !== null && $key !== $formValues['stage']) continue; ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= $formValues['stage'] === $key ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['label']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="field-hint">Stages shown are for the <?= htmlspecialchars($trackLabel) ?> track only.</p>
                </div>
            </div>
            <div class="form-field">
                <label>Research / Paper Title <span class="required">*</span></label>
                <input type="text" id="applicationTitle" name="title" value="<?= htmlspecialchars($formValues['title']) ?>" placeholder="Enter your <?= strtolower($trackLabel) ?> title">
            </div>
            <div class="form-row">
                <div class="form-field">
                    <label>Adviser Name <span class="required">*</span></label>
                    <input type="text" name="adviser" value="<?= htmlspecialchars($formValues['adviser']) ?>" placeholder="Full name of your adviser">
                </div>
                <div class="form-field">
                    <label>Enrollment / Start Date <span class="required">*</span></label>
                    <?= csiteDateField('enroll_date', $formValues['enroll_date']) ?>
                </div>
            </div>
            <div class="form-field">
                <label>Brief Description / Abstract <span class="required">*</span></label>
                <textarea name="abstract" placeholder="Provide a brief description of your research or project..."><?= htmlspecialchars($formValues['abstract']) ?></textarea>
            </div>

            <div style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--gray-100);">
                <h4 style="font-size:0.9rem;font-weight:700;color:var(--adzu-navy);margin-bottom:0.75rem;">
                    <i class="fas fa-cloud-upload-alt" style="margin-right:6px;"></i>Attach Document
                </h4>
                <p class="attach-status">
                    <span class="ms-step-tag <?= $statusTag ?>"><?= htmlspecialchars($statusText) ?></span>
                    <span class="attach-status-note"><?= htmlspecialchars($statusNote) ?></span>
                </p>
                <div class="form-row">
                    <div class="form-field">
                        <label>Document Type <span class="required">*</span></label>
                        <select name="docType" id="applicationDocType">
                            <option value="">Select document type</option>
                        </select>
                        <p class="field-hint" id="documentTypeHint">Pick the document that matches the stage you are applying for.</p>
                    </div>
                </div>
                <div class="form-field">
                    <label>File</label>
                    <div class="file-upload-area">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Drag and drop your file here, or click to browse</p>
                        <p style="font-size:0.75rem;margin-top:0.25rem;">PDF, DOC, or DOCX — Max 20 MB</p>
                        <div class="file-name"></div>
                        <input type="file" name="document" accept=".pdf,.docx,.doc">
                    </div>
                    <?php if ($draftFileName !== ''): ?>
                    <p class="field-hint" id="draftFileNote">
                        <i class="fas fa-paperclip" style="margin-right:5px;"></i>
                        Your draft keeps the file name <strong><?= htmlspecialchars($draftFileName) ?></strong>.
                        Browsers cannot restore a selected file for you — pick it again to attach it.
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-actions" style="border-top:1px solid var(--gray-100);padding-top:1rem;margin-top:1rem;">
                <button type="submit" class="btn btn-primary" id="submitApplicationBtn"><i class="fas fa-paper-plane"></i> Submit Application</button>
                <button type="button" class="btn btn-outline" id="submitAnotherBtn"><i class="fas fa-plus"></i> Submit Another Application</button>
                <a href="<?= url('student/dashboard.php') ?>" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php if ($popup): ?>
<div id="successPopupData" hidden
     data-title="<?= htmlspecialchars($popup['title']) ?>"
     data-message="<?= htmlspecialchars($popup['message']) ?>"></div>
<?php endif; ?>

<script>
(function () {
    const types = <?= json_encode($docTypesByStage) ?>;
    const missingDocuments = <?= json_encode($missingDocumentsByStage) ?>;
    const form = document.getElementById('applicationForm');
    const stageSelect = document.getElementById('applicationStage');
    const typeSelect = document.getElementById('applicationDocType');
    const fileInput = form.querySelector('input[type="file"]');
    const submitBtn = document.getElementById('submitApplicationBtn');
    const hasExistingApplication = <?= $existingApplication ? 'true' : 'false' ?>;

    // Keeps the "Document Type" list in sync with the chosen stage.
    function fillDocTypes(keep) {
        const opts = types[stageSelect.value] || [];
        const previous = keep || typeSelect.value;
        typeSelect.innerHTML = '<option value="">Select document type</option>'
            + opts.map(function (o) { return '<option>' + o + '</option>'; }).join('');
        if (previous && opts.indexOf(previous) !== -1) typeSelect.value = previous;
    }
    stageSelect.addEventListener('change', function () { fillDocTypes(''); });
    fillDocTypes('<?= htmlspecialchars($formValues['docType']) ?>');

    /* ------------------------------------------------------------------
     * Draft
     *
     * The form saves itself in the background and when the student leaves
     * the page, so nothing is lost when they move to another menu.
     * ------------------------------------------------------------------ */
    function draftPayload() {
        const data = new URLSearchParams();
        data.append('form', 'draft');
        ['title', 'adviser', 'abstract', 'enroll_date', 'stage', 'docType'].forEach(function (name) {
            const field = form.querySelector('[name="' + name + '"]');
            if (field) data.append(name, field.value);
        });
        data.append('file_name', fileInput && fileInput.files && fileInput.files.length ? fileInput.files[0].name : '');
        return data;
    }

    var draftTimer = null;
    var draftDisabled = false;
    function saveDraft() {
        if (draftDisabled) return;
        try {
            fetch(window.location.pathname + window.location.search, {
                method: 'POST',
                body: draftPayload(),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                keepalive: true
            });
        } catch (e) { /* the draft is a convenience, never a blocker */ }
    }
    function scheduleDraft() {
        if (draftDisabled) return;
        clearTimeout(draftTimer);
        draftTimer = setTimeout(saveDraft, 700);
    }

    form.addEventListener('input', scheduleDraft);
    form.addEventListener('change', scheduleDraft);

    // Leaving the page (another menu, a refresh, a close) saves the draft.
    window.addEventListener('beforeunload', function () {
        if (draftDisabled || !navigator.sendBeacon) return;
        navigator.sendBeacon(window.location.pathname + window.location.search, draftPayload());
    });

    /* ------------------------------------------------------------------
     * Validation
     *
     * An application is only sent when it is complete: every required
     * field is filled and the documents of the stage are attached.
     * ------------------------------------------------------------------ */
    function fieldError(field, message) {
        const group = field.closest('.form-field') || field.parentElement;
        if (!group) return;
        group.classList.add('has-error');
        let err = group.querySelector('.field-error');
        if (!err) {
            err = document.createElement('div');
            err.className = 'field-error';
            group.appendChild(err);
        }
        err.textContent = message;
    }

    function clearErrors() {
        form.querySelectorAll('.has-error').forEach(function (g) { g.classList.remove('has-error'); });
        form.querySelectorAll('.field-error').forEach(function (e) { e.remove(); });
    }

    form.addEventListener('submit', function (e) {
        clearErrors();

        const title = form.querySelector('[name="title"]');
        const adviser = form.querySelector('[name="adviser"]');
        const abstract = form.querySelector('[name="abstract"]');
        const enroll = form.querySelector('[name="enroll_date"]');
        const values = {
            title: title.value.trim(),
            adviser: adviser.value.trim(),
            abstract: abstract.value.trim(),
            enroll_date: enroll.value.trim()
        };
        const hasDetails = values.title !== '' || values.adviser !== '' || values.abstract !== '' || values.enroll_date !== '';
        const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

        // Only a NEW application is validated as a whole. A student who
        // already sent their application only attaches the documents that
        // are still outstanding for that stage.
        if (hasDetails && !hasExistingApplication) {
            if (!values.title) { fieldError(title, 'Enter your research / paper title.'); e.preventDefault(); }
            if (!values.adviser) { fieldError(adviser, 'Enter the name of your adviser.'); e.preventDefault(); }
            if (!values.abstract) { fieldError(abstract, 'Write a brief description of your research.'); e.preventDefault(); }
            if (!values.enroll_date) { fieldError(enroll, 'Enter your enrollment / start date.'); e.preventDefault(); }
            const stillMissing = missingDocuments[stageSelect.value] || [];
            if (stillMissing.length && !hasFile) {
                fieldError(typeSelect, 'Attach these documents for this stage: ' + stillMissing.join(', ') + '.');
                e.preventDefault();
            }
            if (hasFile && !typeSelect.value) {
                fieldError(typeSelect, 'Select the document type of the file you attached.');
                e.preventDefault();
            }
        } else if (!hasFile) {
            e.preventDefault();
            fieldError(typeSelect, 'Fill in your application details or attach a document.');
        }

        if (e.defaultPrevented) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    // "Submit Another Application": clear the form for a fresh application.
    var anotherBtn = document.getElementById('submitAnotherBtn');
    if (anotherBtn) {
        anotherBtn.addEventListener('click', function () {
            ['title', 'adviser', 'abstract', 'enroll_date'].forEach(function (name) {
                const field = form.querySelector('[name="' + name + '"]');
                if (field) field.value = '';
            });
            typeSelect.value = '';
            if (fileInput) fileInput.value = '';
            var note = document.getElementById('draftFileNote');
            if (note) note.remove();
            // The draft is cleared: the student explicitly starts a new one.
            draftDisabled = true;
            const data = new URLSearchParams();
            data.append('form', 'draft');
            fetch(window.location.pathname + window.location.search, {
                method: 'POST', body: data, credentials: 'same-origin', keepalive: true
            });
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // "Discard Draft": forget the four values the student typed. Uploaded
    // documents and submitted applications are never touched.
    var discardBtn = document.getElementById('discardDraftBtn');
    if (discardBtn) {
        discardBtn.addEventListener('click', function () {
            if (!window.confirm('Discard your saved draft? Only the values you typed are removed — your uploaded documents and submitted application stay exactly as they are.')) {
                return;
            }
            // Clear the four fields straight away.
            ['title', 'adviser', 'abstract', 'enroll_date'].forEach(function (name) {
                var field = form.querySelector('[name="' + name + '"]');
                if (field) field.value = '';
            });
            clearErrors();
            clearTimeout(draftTimer);
            draftDisabled = true;   // the draft stays gone, even on reload
            var data = new URLSearchParams();
            data.append('form', 'discard_draft');
            fetch(window.location.pathname + window.location.search, {
                method: 'POST', body: data, credentials: 'same-origin'
            }).then(function () {
                draftDisabled = false;   // new typing is saved again
                var banner = document.querySelector('.draft-notice');
                if (banner) banner.remove();
                var note = document.getElementById('draftFileNote');
                if (note) note.remove();
                if (typeof window.showSuccessPopup === 'function') {
                    window.showSuccessPopup({
                        title: 'Draft discarded successfully.',
                        message: 'The values you typed were removed. Your documents and application were not changed.',
                        duration: 4000
                    });
                }
            });
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
