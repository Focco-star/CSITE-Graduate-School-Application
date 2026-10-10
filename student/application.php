<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

/* ------------------------------------------------------------------
 * Helpers shared by "Upload Document Only", "Submit Application" and
 * "Submit Edited Application"
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

/** Stores the file and records it in the student's upload history. Throws RuntimeException on failure. */
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

/** Normalises a stored / posted enrollment date to Y-m-d for the editable date field ('' when unreadable). */
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
 * Saves the enrollment / start date. It is remembered in the session and also written to
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
$uploadError   = '';
$editError     = '';
$editOpen      = false;  // re-open the edit window when saving it failed

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
 * POST: Upload Document Only
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'upload') {
    $docType  = trim($_POST['docType'] ?? '');
    $stageKey = trim($_POST['stage'] ?? '');
    $file     = $_FILES['document'] ?? null;
    $uploadError = (string) csiteCheckUpload($file, $docType, $stageKey, $stages, $mockStudent['email'], $track);
    if ($uploadError === '') {
        try {
            csiteSaveUpload($file, $docType, $stageKey, $stages, $mockStudent['email'], (int) ($sessionUser['user_id'] ?? 0));
            $popup = [
                'title'   => 'Document Submitted!',
                'message' => '"' . $file['name'] . '" was uploaded successfully.',
            ];
        } catch (RuntimeException $e) {
            $uploadError = $e->getMessage();
        }
    }
}

/* ------------------------------------------------------------------
 * POST: Submit Application
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'application') {
    $title = trim($_POST['title'] ?? '');
    $adviser = formatPersonName($_POST['adviser'] ?? '');
    $stageKey = $_POST['stage'] ?? '';
    $enrollDate = csiteParseDate($_POST['enroll_date'] ?? '');
    $stageLockError = isset($stages[$stageKey])
        ? validateApplicationStageSequence($progress, $stages, $stageKey)
        : null;
    if ($title === '' || $adviser === '' || !isset($stages[$stageKey])) {
        $appError = 'Research title, adviser, and presentation stage are required.';
    } elseif ($stageLockError !== null) {
        $appError = $stageLockError;
    } elseif ($enrollDate === false) {
        $appError = 'Please enter a valid enrollment / start date.';
    } else {
        try {
            $pdo = DB::getConnection();
            $userId = (int) ($sessionUser['user_id'] ?? $_SESSION['user_id'] ?? 0);
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

            $pdo->beginTransaction();
            $insertApplication = $pdo->prepare(
                'INSERT INTO applications (user_id, presentation_stage, paper_title, status)
                 VALUES (:user_id, :presentation_stage, :paper_title, :status)'
            );
            $insertApplication->execute([
                'user_id' => $userId,
                'presentation_stage' => $stages[$stageKey]['label'],
                'paper_title' => $title,
                'status' => 'submitted',
            ]);

            $updateAdviser = $pdo->prepare(
                'UPDATE students SET adviser_name = :adviser_name WHERE user_id = :user_id'
            );
            $updateAdviser->execute([
                'adviser_name' => $adviser,
                'user_id' => $userId,
            ]);

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
                $upsertStmt = $pdo->prepare(
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
                );
                $upsertStmt->execute([
                    'first_name' => formatPersonName($identity['first_name']),
                    'last_name' => formatPersonName($identity['last_name']),
                    'middle_initial' => $identity['middle_initial'],
                    'age' => (int) $identity['age'],
                    'gender' => $identity['gender'],
                    'program' => $identity['program'],
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
            $pdo->commit();

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

            addApplicationRecord([
                'studentEmail' => $mockStudent['email'],
                'student' => $mockStudent['name'],
                'program' => $mockStudent['program'],
                'stageKey' => $stageKey,
                'title' => $title,
                'adviser' => $adviser,
                'abstract' => trim($_POST['abstract'] ?? ''),
            ]);

            if ($enrollDate !== null) {
                csiteSaveEnrollDate($pdo, $userId, $enrollDate);
            }

            $popup = [
                'title'   => 'Application Sent Successfully',
                'message' => 'Your application for "' . $title . '" was submitted successfully.',
            ];
            $mockStudent = currentStudentProfile($mockStudent);
            if (!empty($sessionUser)) {
                $mockStudent['name']  = $sessionUser['full_name'] ?? $sessionUser['name'] ?? $mockStudent['name'];
                $mockStudent['email'] = $sessionUser['email'] ?? $mockStudent['email'];
            }
            if ($enrollDate !== null) {
                $mockStudent['enroll_date'] = $enrollDate;
            }
            $track = getTrackForProgram($mockStudent['program']);
            $trackLabel = getTrackLabel($track);
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $appError = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Unable to save your application. Please try again.';
        }
    }
}

/* ------------------------------------------------------------------
 * POST: Submit Edited Application
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'edit_application') {
    $editTitle     = trim($_POST['title'] ?? '');
    $editAdviser   = formatPersonName($_POST['adviser'] ?? '');
    $editStageKey  = $_POST['stage'] ?? '';
    $editDocType   = trim($_POST['docType'] ?? '');
    $originalTitle = trim($_POST['original_title'] ?? '');
    $editEnroll    = csiteParseDate($_POST['enroll_date'] ?? '');
    $editFile      = $_FILES['document'] ?? null;
    $editHasFile   = csiteUploadHasFile($editFile);
    $editOpen      = true; // stays open unless everything below succeeds

    // Keeping the stage the student is already on is always allowed; moving to another one follows the same stage lock as a new application.
    $editStageLock = (isset($stages[$editStageKey]) && $editStageKey !== $currentStageKey)
        ? validateApplicationStageSequence($progress, $stages, $editStageKey)
        : null;

    if ($editTitle === '' || $editAdviser === '' || !isset($stages[$editStageKey])) {
        $editError = 'Research title, adviser, and presentation stage are required.';
    } elseif ($editStageLock !== null) {
        $editError = $editStageLock;
    } elseif ($editEnroll === false) {
        $editError = 'Please enter a valid enrollment / start date.';
    } elseif ($editHasFile && ($uploadCheck = csiteCheckUpload($editFile, $editDocType, $editStageKey, $stages, $mockStudent['email'], $track)) !== null) {
        $editError = $uploadCheck;
    } else {
        $applicationSaved = false;
        try {
            $pdo = DB::getConnection();
            $userId = (int) ($sessionUser['user_id'] ?? $_SESSION['user_id'] ?? 0);

            $pdo->beginTransaction();
            $exists = $pdo->prepare('SELECT COUNT(*) FROM applications WHERE user_id = :user_id AND paper_title = :paper_title');
            $exists->execute(['user_id' => $userId, 'paper_title' => $originalTitle]);
            if ((int) $exists->fetchColumn() === 0) {
                throw new RuntimeException('We could not find the application you are editing. Please refresh the page and try again.');
            }
            $pdo->prepare(
                'UPDATE applications
                 SET presentation_stage = :presentation_stage, paper_title = :paper_title
                 WHERE user_id = :user_id AND paper_title = :original_title'
            )->execute([
                'presentation_stage' => $stages[$editStageKey]['label'],
                'paper_title'        => $editTitle,
                'user_id'            => $userId,
                'original_title'     => $originalTitle,
            ]);
            $pdo->prepare('UPDATE students SET adviser_name = :adviser_name WHERE user_id = :user_id')
                ->execute(['adviser_name' => $editAdviser, 'user_id' => $userId]);
            $pdo->commit();
            $applicationSaved = true;

            if ($editEnroll !== null) {
                csiteSaveEnrollDate($pdo, $userId, $editEnroll);
                $mockStudent['enroll_date'] = $editEnroll;
            }

            $identity = databaseStudentIdentity();
            if ($identity) {
                upsertSessionStudent($identity, ['adviser' => $editAdviser, 'title' => $editTitle]);
            }
            $mockStudent['title']   = $editTitle;
            $mockStudent['adviser'] = $editAdviser;

            if ($editHasFile) {
                csiteSaveUpload($editFile, $editDocType, $editStageKey, $stages, $mockStudent['email'], $userId);
            }

            $editOpen = false;
            $popup = [
                'title'   => 'Application Updated!',
                'message' => $editHasFile
                    ? 'Your application for "' . $editTitle . '" was updated and "' . $editFile['name'] . '" was uploaded.'
                    : 'Your application for "' . $editTitle . '" was updated successfully.',
            ];
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e instanceof RuntimeException) {
                $editError = $applicationSaved
                    ? 'Your application changes were saved, but the document could not be uploaded: ' . $e->getMessage()
                    : $e->getMessage();
            } else {
                $editError = 'Unable to save your changes. Please try again.';
            }
        }
    }
}

/* ------------------------------------------------------------------
 * View data
 * ------------------------------------------------------------------ */
$uploadedDocs = uploadsForEmail($mockStudent['email']);
$hasApplication = trim((string) ($mockStudent['title'] ?? '')) !== '';
$docTypesByStage = [];
foreach ($stages as $key => $s) {
    $docTypesByStage[$key] = [
        $s['shortLabel'] . ' Paper',
        $s['shortLabel'] . ' Adviser Endorsement Form',
        'Official Receipt / Payment Proof',
        'Revised / Corrected Document',
    ];
}

$stageKeyByLabel = array_flip(array_column($stages, 'label'));

// Values shown in the edit window (the posted ones again if saving failed)
$editVals = [
    'stage'       => $currentStageKey,
    'title'       => $mockStudent['title'] ?? '',
    'adviser'     => $mockStudent['adviser'] ?? '',
    'enroll_date' => $mockStudent['enroll_date'] ?? '',
    'docType'     => '',
];
if ($editOpen) {
    $editVals['stage']       = isset($stages[$_POST['stage'] ?? '']) ? $_POST['stage'] : $editVals['stage'];
    $editVals['title']       = trim($_POST['title'] ?? '');
    $editVals['adviser']     = trim($_POST['adviser'] ?? '');
    $editVals['enroll_date'] = trim($_POST['enroll_date'] ?? '');
    $editVals['docType']     = trim($_POST['docType'] ?? '');
}

$shortPath = array_map(static function ($s) {
    return $s['shortLabel'];
}, $workflow['stages']);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h2><?= htmlspecialchars($trackLabel) ?> Presentation Application</h2>
    <p>Submit your application and upload required documents for the current stage.</p>
</div>

<div class="track-indicator track-<?= htmlspecialchars($track) ?>">
    <span class="track-indicator-badge"><?= htmlspecialchars($trackLabel) ?> Track</span>
    <strong class="track-indicator-program"><?= htmlspecialchars($mockStudent['program_name']) ?></strong>
</div>

<?php if ($appError): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($appError) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-file-alt"></i> Application &amp; Document Upload</h3></div>
    <div class="card-body">
        <form class="form-card" style="box-shadow:none;border:none;padding:0;" method="post" action="<?= url('student/application.php') ?>" data-validate>
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
                    <select name="stage" required>
                        <?php foreach ($stages as $key => $s): ?>
                        <?php if (validateApplicationStageSequence($progress, $stages, $key) !== null) continue; ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= $key === $currentStageKey ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['label']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="field-hint">Stages shown are for the <?= htmlspecialchars($trackLabel) ?> track only.</p>
                </div>
            </div>
            <div class="form-field">
                <label>Research / Paper Title <span class="required">*</span></label>
                <input type="text" name="title" value="<?= htmlspecialchars($mockStudent['title']) ?>" required placeholder="Enter your <?= strtolower($trackLabel) ?> title">
            </div>
            <div class="form-row">
                <div class="form-field">
                    <label>Adviser Name <span class="required">*</span></label>
                    <input type="text" name="adviser" value="<?= htmlspecialchars($mockStudent['adviser']) ?>" required placeholder="Full name of your adviser">
                </div>
                <div class="form-field">
                    <label>Enrollment / Start Date</label>
                    <?= csiteDateField('enroll_date', $mockStudent['enroll_date'] ?? '') ?>
                </div>
            </div>
            <div class="form-field">
                <label>Brief Description / Abstract</label>
                <textarea name="abstract" placeholder="Provide a brief description of your research or project..."></textarea>
            </div>
            <div class="form-actions" style="border-top:1px solid var(--gray-100);padding-top:1rem;margin-top:1rem;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Application</button>
                <a href="<?= url('student/dashboard.php') ?>" class="btn btn-outline">Cancel</a>
            </div>
        </form>

        <div style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--gray-100);">
            <h4 style="font-size:0.9rem;font-weight:700;color:var(--adzu-navy);margin-bottom:0.75rem;">
                <i class="fas fa-cloud-upload-alt" style="margin-right:6px;"></i>Attach Documents
            </h4>
            <?php if ($uploadError): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($uploadError) ?></div>
            <?php endif; ?>
            <div class="alert alert-warning" style="margin-bottom:1rem;">
                <i class="fas fa-exclamation-triangle"></i>
                <div>Upload your <strong>paper</strong> and <strong>signed adviser endorsement form</strong> before submitting. Official receipt must be uploaded after payment.</div>
            </div>
            <form method="post" action="<?= url('student/application.php') ?>" enctype="multipart/form-data">
                <input type="hidden" name="form" value="upload">
                <div class="form-row">
                    <div class="form-field">
                        <label>Document Type <span class="required">*</span></label>
                        <select name="docType" id="appUploadDocType">
                            <option value="">Select document type</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Stage</label>
                        <select name="stage" id="appUploadStage">
                            <?php foreach ($stages as $key => $s): ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= $key === $currentStageKey ? 'selected' : '' ?>><?= htmlspecialchars($s['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
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
                </div>
                <button type="submit" class="btn btn-outline btn-sm"><i class="fas fa-upload"></i> Upload Document Only</button>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-history"></i> Upload History</h3>
        <span class="filter-count" data-filter-count></span>
    </div>
    <div class="card-body">
        <div class="filter-bar" data-filter-table="#appUploadTable">
            <div class="filter-search">
                <i class="fas fa-search"></i>
                <input type="search" data-filter-q placeholder="Search by file name, type, or stage...">
            </div>
            <button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button>
        </div>
        <div class="table-responsive">
            <table class="data-table" id="appUploadTable">
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Type</th>
                        <th>Stage</th>
                        <th>Date</th>
                        <th>Size</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($uploadedDocs as $doc): ?>
                    <tr data-search="<?= htmlspecialchars(strtolower($doc['fileName'] . ' ' . $doc['docType'] . ' ' . $doc['stage'])) ?>">
                        <td><strong style="font-size:0.85rem;"><?= htmlspecialchars($doc['fileName']) ?></strong></td>
                        <td><?= htmlspecialchars($doc['docType']) ?></td>
                        <td><?= htmlspecialchars($doc['stage']) ?></td>
                        <td><?= htmlspecialchars($doc['date']) ?></td>
                        <td><?= htmlspecialchars(formatFileSize((int) $doc['size'])) ?></td>
                        <td><?= statusBadge($doc['status']) ?></td>
                        <td class="actions">
                            <?php if (uploadStoragePath($doc)): ?>
                            <a class="btn btn-sm btn-outline btn-icon" title="View" target="_blank" href="<?= url('student/download.php?id=' . urlencode($doc['id']) . '&view=1') ?>"><i class="fas fa-eye"></i></a>
                            <a class="btn btn-sm btn-outline btn-icon" title="Download" href="<?= url('student/download.php?id=' . urlencode($doc['id'])) ?>"><i class="fas fa-download"></i></a>
                            <?php else: ?><span style="font-size:.75rem;color:var(--gray-400);">Unavailable</span><?php endif; ?>
                            <?php if ($hasApplication): ?>
                            <button type="button" class="btn btn-sm btn-outline btn-icon" title="Edit application" aria-label="Edit application"
                                    data-edit-application
                                    data-stage="<?= htmlspecialchars($stageKeyByLabel[$doc['stage']] ?? $currentStageKey) ?>"
                                    data-doc-type="<?= htmlspecialchars($doc['docType']) ?>"><i class="fas fa-pencil-alt"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr data-filter-empty <?= $uploadedDocs ? 'hidden' : '' ?>>
                        <td colspan="7" style="text-align:center;color:var(--gray-400);padding:2rem;">No documents uploaded yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Your <?= htmlspecialchars($trackLabel) ?> Presentation Path</h3></div>
    <div class="card-body">
        <?php if ($isThesis): ?>
        <p style="margin-bottom:1rem;font-size:0.875rem;color:var(--gray-500);">As an MSCS/thesis student, you will undergo three presentations:</p>
        <ul class="track-stages">
            <li><span class="stage-num">1</span> Concept Paper Presentation → upon approval, proceed to proposal</li>
            <li><span class="stage-num">2</span> Thesis Proposal Presentation → upon approval, conduct research</li>
            <li><span class="stage-num">3</span> Final Thesis Defense → upon approval, submit to Graduate School</li>
        </ul>
        <?php elseif ($track === 'seminar'): ?>
        <p style="margin-bottom:1rem;font-size:0.875rem;color:var(--gray-500);">As a seminar paper student, you will undergo two presentations:</p>
        <ul class="track-stages">
            <li><span class="stage-num">1</span> Seminar Paper Proposal Presentation → upon approval, complete the paper</li>
            <li><span class="stage-num">2</span> Final Seminar Paper Presentation → upon approval, submit to Graduate School</li>
        </ul>
        <?php else: ?>
        <p style="margin-bottom:1rem;font-size:0.875rem;color:var(--gray-500);">As a capstone student, you will undergo two presentations:</p>
        <ul class="track-stages">
            <li><span class="stage-num">1</span> Capstone Proposal Presentation → upon approval, complete the capstone</li>
            <li><span class="stage-num">2</span> Final Capstone Presentation → upon approval, submit to Graduate School</li>
        </ul>
        <?php endif; ?>
    </div>
</div>

<?php if ($hasApplication): ?>
<!-- Edit application: mini pop-up, stays open until "Submit Edited Application" (or the X / Cancel) is used -->
<div class="edit-modal-overlay <?= $editOpen ? 'active' : '' ?>" id="editApplicationModal">
    <div class="modal edit-modal" role="dialog" aria-modal="true" aria-labelledby="editAppHeading">
        <form method="post" action="<?= url('student/application.php') ?>" enctype="multipart/form-data" data-validate>
            <input type="hidden" name="form" value="edit_application">
            <input type="hidden" name="original_title" value="<?= htmlspecialchars($mockStudent['title']) ?>">

            <div class="modal-header">
                <h3 id="editAppHeading"><i class="fas fa-pencil-alt"></i> Edit Application</h3>
                <button type="button" class="modal-close" data-edit-close aria-label="Close"><i class="fas fa-times"></i></button>
            </div>

            <div class="modal-body">
                <?php if ($editError): ?>
                <div class="alert alert-danger edit-modal-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($editError) ?></div>
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-field">
                        <label>Presentation Stage <span class="required">*</span></label>
                        <select name="stage" id="editAppStage" required>
                            <?php foreach ($stages as $key => $s): ?>
                            <?php
                            // Hide locked stages, but never hide the stage the student is on or has picked
                            if ($key !== $currentStageKey && $key !== $editVals['stage']
                                && validateApplicationStageSequence($progress, $stages, $key) !== null) {
                                continue;
                            }
                            ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= $key === $editVals['stage'] ? 'selected' : '' ?>><?= htmlspecialchars($s['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Enrollment / Start Date</label>
                        <?= csiteDateField('enroll_date', $editVals['enroll_date']) ?>
                    </div>
                </div>
                <div class="form-field">
                    <label>Research / Paper Title <span class="required">*</span></label>
                    <input type="text" name="title" value="<?= htmlspecialchars($editVals['title']) ?>" required placeholder="Enter your <?= strtolower($trackLabel) ?> title">
                </div>
                <div class="form-field">
                    <label>Adviser Name <span class="required">*</span></label>
                    <input type="text" name="adviser" value="<?= htmlspecialchars($editVals['adviser']) ?>" required placeholder="Full name of your adviser">
                </div>

                <div class="edit-modal-section"><i class="fas fa-cloud-upload-alt"></i>Attach Document <span style="font-weight:500;color:var(--gray-400);">(optional)</span></div>
                <div class="form-field">
                    <label>Document Type</label>
                    <select name="docType" id="editAppDocType" data-selected="<?= htmlspecialchars($editVals['docType']) ?>">
                        <option value="">Select document type (optional)</option>
                    </select>
                    <p class="field-hint">Required only if you attach a file below.</p>
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
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-edit-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Edited Application</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($popup): ?>
<div id="successPopupData" hidden
     data-title="<?= htmlspecialchars($popup['title']) ?>"
     data-message="<?= htmlspecialchars($popup['message']) ?>"></div>
<?php endif; ?>

<script>
(function () {
    const types = <?= json_encode($docTypesByStage) ?>;

    // Keeps a "Document Type" list in sync with its "Stage" select
    function bindDocTypes(stageId, typeId, placeholder) {
        const stage = document.getElementById(stageId);
        const typeSel = document.getElementById(typeId);
        if (!stage || !typeSel) return;
        function fill() {
            const keep = typeSel.dataset.selected || typeSel.value;
            const opts = types[stage.value] || [];
            typeSel.innerHTML = '<option value="">' + placeholder + '</option>' + opts.map(o => '<option>' + o + '</option>').join('');
            if (keep && opts.indexOf(keep) !== -1) typeSel.value = keep;
            typeSel.dataset.selected = '';
        }
        stage.addEventListener('change', fill);
        fill();
    }

    // Upload-only form requires a type; the edit window only needs one when a file is attached
    bindDocTypes('appUploadStage', 'appUploadDocType', 'Select document type');
    bindDocTypes('editAppStage', 'editAppDocType', 'Select document type (optional)');
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>