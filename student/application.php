<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

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
$appSuccess    = '';
$appError      = '';
$uploadError   = '';
$uploadSuccess = '';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'upload') {
    $docType = trim($_POST['docType'] ?? '');
    $stageKey = trim($_POST['stage'] ?? '');
    $file = $_FILES['document'] ?? null;
    if ($docType === '') {
        $uploadError = 'Please select a document type.';
    } elseif ($stageKey === '' || !isset($stages[$stageKey])) {
        $uploadError = 'Please select a stage.';
    } elseif (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $uploadError = 'Please select a file to upload.';
    } elseif (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $uploadError = 'The file could not be uploaded. Try again.';
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'doc', 'docx'], true)) {
            $uploadError = 'Only PDF, DOC, or DOCX files are allowed.';
        } elseif ($file['size'] > 20 * 1024 * 1024) {
            $uploadError = 'File exceeds the 20 MB maximum size.';
        } else {
            try {
                $saved = storeStudentUpload($file);
                $linked = latestApplicationForEmail($mockStudent['email']);
                addUploadRecord([
                    'applicationId' => $linked['id'] ?? null,
                    'studentEmail' => $mockStudent['email'],
                    'fileName' => $file['name'],
                    'docType' => $docType,
                    'stage' => $stages[$stageKey]['label'],
                    'size' => $file['size'],
                    'storedFile' => $saved['storedFile'],
                    'mimeType' => $saved['mimeType'],
                ]);
                $uploadSuccess = '"' . $file['name'] . '" uploaded successfully.';
            } catch (RuntimeException $e) {
                $uploadError = $e->getMessage();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'application') {
    $title = trim($_POST['title'] ?? '');
    $adviser = trim($_POST['adviser'] ?? '');
    $stageKey = $_POST['stage'] ?? '';
    if ($title === '' || $adviser === '' || !isset($stages[$stageKey])) {
        $appError = 'Research title, adviser, and presentation stage are required.';
    } else {
        try {
            $pdo = DB::getConnection();
            $userId = (int) ($sessionUser['user_id'] ?? $_SESSION['user_id'] ?? 0);
            $studentLookup = $pdo->prepare(
                'SELECT s.student_id
                 FROM students s
                 INNER JOIN users u ON u.user_id = s.user_id
                 WHERE s.user_id = :user_id OR u.email = :email
                 ORDER BY s.student_id DESC
                 LIMIT 1'
            );
            $studentLookup->execute([
                'user_id' => $userId,
                'email' => $mockStudent['email'],
            ]);
            $studentId = (int) ($studentLookup->fetchColumn() ?: 0);

            if ($studentId <= 0) {
                throw new RuntimeException('Your student profile was not found. Please sign in again or contact the coordinator.');
            }

            $pdo->beginTransaction();
            $insertApplication = $pdo->prepare(
                'INSERT INTO applications (student_id, presentation_stage, paper_title, status)
                 VALUES (:student_id, :presentation_stage, :paper_title, :status)'
            );
            $insertApplication->execute([
                'student_id' => $studentId,
                'presentation_stage' => $stages[$stageKey]['label'],
                'paper_title' => $title,
                'status' => 'submitted',
            ]);

            $updateAdviser = $pdo->prepare(
                'UPDATE students SET adviser_name = :adviser_name WHERE student_id = :student_id'
            );
            $updateAdviser->execute([
                'adviser_name' => $adviser,
                'student_id' => $studentId,
            ]);
            $pdo->commit();

            // Keep the existing session-based screens in sync while the coordinator modules
            // are gradually migrated to MySQL.
            addApplicationRecord([
                'studentEmail' => $mockStudent['email'],
                'student' => $mockStudent['name'],
                'program' => $mockStudent['program'],
                'stageKey' => $stageKey,
                'title' => $title,
                'adviser' => $adviser,
                'abstract' => trim($_POST['abstract'] ?? ''),
            ]);

            $appSuccess = 'Application submitted successfully for "' . $title . '".';
            $mockStudent = currentStudentProfile($mockStudent);
            if (!empty($sessionUser)) {
                $mockStudent['name']  = $sessionUser['full_name'] ?? $sessionUser['name'] ?? $mockStudent['name'];
                $mockStudent['email'] = $sessionUser['email'] ?? $mockStudent['email'];
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

$uploadedDocs = uploadsForEmail($mockStudent['email']);
$docTypesByStage = [];
foreach ($stages as $key => $s) {
    $docTypesByStage[$key] = [
        $s['shortLabel'] . ' Paper',
        $s['shortLabel'] . ' Adviser Endorsement Form',
        'Official Receipt / Payment Proof',
        'Revised / Corrected Document',
    ];
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

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    <div>
        <strong><?= htmlspecialchars($mockStudent['program_name']) ?></strong>
        — <?= htmlspecialchars($trackLabel) ?> Track
        &nbsp;(<?= htmlspecialchars(implode(' → ', $shortPath)) ?>)
    </div>
</div>

<?php if ($appSuccess): ?>
<div class="alert alert-success" data-auto-dismiss><i class="fas fa-check-circle"></i> <?= htmlspecialchars($appSuccess) ?></div>
<?php endif; ?>
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
                    <input type="date" value="<?= htmlspecialchars($mockStudent['enroll_date']) ?>" readonly>
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
            <?php if ($uploadSuccess): ?>
            <div class="alert alert-success" data-auto-dismiss><i class="fas fa-check-circle"></i> <?= htmlspecialchars($uploadSuccess) ?></div>
            <?php endif; ?>
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
                        <label>Document Type</label>
                        <select name="docType" id="appUploadDocType">
                            <option value="">Select document type (optional)</option>
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

<script>
(function () {
    const types = <?= json_encode($docTypesByStage) ?>;
    const stage = document.getElementById('appUploadStage');
    const typeSel = document.getElementById('appUploadDocType');
    function fill() {
        const opts = types[stage.value] || [];
        typeSel.innerHTML = '<option value="">Select document type (optional)</option>' + opts.map(o => '<option>' + o + '</option>').join('');
    }
    stage.addEventListener('change', fill);
    fill();
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
