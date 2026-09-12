<?php
require_once __DIR__ . '/../includes/config.php';

$mockStudent = currentStudentProfile($mockStudent);

$pageTitle   = 'Document Upload';
$role        = 'student';
$currentPage = 'application';
$userName    = $mockStudent['name'];
$track       = $mockStudent['track'];
$workflow    = getWorkflow($track);
$stages      = [];
foreach ($workflow['stages'] as $s) {
    $stages[$s['key']] = $s;
}

$uploadError = '';
$uploadSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $docType = trim($_POST['docType'] ?? '');
    $stageKey = trim($_POST['stage'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
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
                    'notes' => $notes,
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

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h2>Document Upload</h2>
    <p>Upload required documents for your <?= htmlspecialchars(getTrackLabel($track)) ?> presentation process.</p>
</div>

<?php if ($uploadSuccess): ?>
<div class="alert alert-success" data-auto-dismiss><i class="fas fa-check-circle"></i> <?= htmlspecialchars($uploadSuccess) ?></div>
<?php endif; ?>
<?php if ($uploadError): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($uploadError) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h3>Upload New Document</h3></div>
    <div class="card-body">
        <form method="post" action="<?= url('student/upload.php') ?>" enctype="multipart/form-data" data-validate>
            <div class="form-row">
                <div class="form-field">
                    <label>Presentation Stage <span class="required">*</span></label>
                    <select name="stage" id="uploadStage" required>
                        <option value="">Select stage</option>
                        <?php foreach ($stages as $key => $s): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= $key === ($mockStudent['current_stage'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($s['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label>Document Type <span class="required">*</span></label>
                    <select name="docType" id="uploadDocType" required>
                        <option value="">Select document type</option>
                    </select>
                </div>
            </div>
            <div class="form-field">
                <label>File <span class="required">*</span></label>
                <div class="file-upload-area">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Drag and drop your file here, or click to browse</p>
                    <p style="font-size:0.75rem;margin-top:0.25rem;">Accepted: PDF, DOC, DOCX (Max 20MB)</p>
                    <div class="file-name"></div>
                    <input type="file" name="document" accept=".pdf,.docx,.doc" required>
                </div>
            </div>
            <div class="form-field">
                <label>Notes (optional)</label>
                <textarea name="notes" placeholder="Add any notes about this upload (e.g., revision version)..."></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Document</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Uploaded Documents</h3>
        <span class="filter-count" data-filter-count></span>
    </div>
    <div class="card-body">
        <div class="filter-bar" data-filter-table="#uploadTable">
            <div class="filter-search">
                <i class="fas fa-search"></i>
                <input type="search" data-filter-q placeholder="Search by file name, type, or stage...">
            </div>
            <button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button>
        </div>
        <div class="table-responsive">
            <table class="data-table" id="uploadTable">
                <thead>
                    <tr>
                        <th>Document</th>
                        <th>Type</th>
                        <th>Stage</th>
                        <th>Upload Date</th>
                        <th>Size</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($uploadedDocs as $doc): ?>
                    <tr data-search="<?= htmlspecialchars(strtolower($doc['fileName'] . ' ' . $doc['docType'] . ' ' . $doc['stage'])) ?>">
                        <td><strong><?= htmlspecialchars($doc['fileName']) ?></strong></td>
                        <td><?= htmlspecialchars($doc['docType']) ?></td>
                        <td><?= htmlspecialchars($doc['stage']) ?></td>
                        <td><?= date('M d, Y', strtotime($doc['date'])) ?></td>
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

<script>
(function () {
    const types = <?= json_encode($docTypesByStage) ?>;
    const stage = document.getElementById('uploadStage');
    const typeSel = document.getElementById('uploadDocType');
    function fill() {
        const opts = types[stage.value] || [];
        typeSel.innerHTML = '<option value="">Select document type</option>' + opts.map(o => '<option>' + o + '</option>').join('');
    }
    stage.addEventListener('change', fill);
    fill();
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
