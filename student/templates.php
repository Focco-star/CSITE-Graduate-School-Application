<?php
require_once __DIR__ . '/../includes/config.php';

$mockStudent = currentStudentProfile($mockStudent);

$pageTitle   = 'Templates & Forms';
$role        = 'student';
$currentPage = 'templates';
$userName    = $mockStudent['name'];

$track       = $mockStudent['track'] ?? getTrackForProgram($mockStudent['program']);
$trackLabel  = getTrackLabel($track);
$workflow    = getWorkflow($track);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h2>Templates &amp; Forms</h2>
    <p>Download forms and templates for <?= htmlspecialchars($mockStudent['program_name']) ?> (<?= htmlspecialchars($trackLabel) ?> Track).</p>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    <div>
        All documents below are specific to <strong><?= htmlspecialchars($mockStudent['program_name']) ?></strong>.
        Use only the files intended for your program and presentation stage.
    </div>
</div>

<div class="filter-bar" data-filter-table="#studentTplGrid" style="margin-bottom:1rem;">
    <div class="filter-search">
        <i class="fas fa-search"></i>
        <input type="search" data-filter-q placeholder="Search templates and forms...">
    </div>
    <button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button>
    <select data-filter="type">
        <option value="">All types</option>
        <option>Form</option>
        <option>Template</option>
        <option>Reference</option>
    </select>
    <span class="filter-count" data-filter-count></span>
</div>

<div id="studentTplGrid">
<?php foreach ($workflow['stages'] as $stage):
    $stageDocs = [$stage['adviserEndorsementForm'], $stage['gradSchoolEndorsementForm']];
    foreach ($stage['documents'] as $doc) {
        if (($doc['type'] ?? '') === 'template' && ($doc['file'] ?? '') !== '') {
            $stageDocs[] = $doc;
        }
    }
    $stageDocs = array_values(array_filter($stageDocs, static function ($d) {
        return ($d['file'] ?? '') !== '';
    }));
?>
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-layer-group"></i> <?= htmlspecialchars($stage['label']) ?></h3>
    </div>
    <div class="card-body">
        <div class="template-grid">
            <?php foreach ($stageDocs as $doc):
                $href = asset('papers/' . rawurlencode($doc['folder']) . '/' . rawurlencode($doc['file']));
                $isForm = ($doc['type'] ?? '') === 'form';
            ?>
            <div class="template-item" data-type="<?= $isForm ? 'Form' : 'Template' ?>" data-search="<?= htmlspecialchars(strtolower($doc['label'] . ' ' . $doc['file'] . ' ' . ($isForm ? 'form' : 'template'))) ?>">
                <div class="template-icon"><i class="fas <?= $isForm ? 'fa-file-signature' : 'fa-file-word' ?>"></i></div>
                <h4><?= htmlspecialchars($doc['label']) ?></h4>
                <p><?= htmlspecialchars($doc['description']) ?></p>
                <span class="template-meta"><i class="fas fa-tag"></i> <?= $isForm ? 'Form' : 'Template' ?></span>
                <a class="btn btn-primary btn-sm" style="align-self:flex-start;" href="<?= htmlspecialchars($href) ?>" download>
                    <i class="fas fa-download"></i> Download
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php if (!empty($workflow['refs'])): ?>
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-book-open"></i> Reference Documents</h3>
    </div>
    <div class="card-body">
        <div class="template-grid">
            <?php foreach ($workflow['refs'] as $doc): ?>
            <div class="template-item" data-type="Reference" data-search="<?= htmlspecialchars(strtolower($doc['label'] . ' ' . $doc['file'] . ' reference')) ?>">
                <div class="template-icon"><i class="fas fa-file-word"></i></div>
                <h4><?= htmlspecialchars($doc['label']) ?></h4>
                <p><?= htmlspecialchars($doc['description']) ?></p>
                <span class="template-meta"><i class="fas fa-tag"></i> Reference</span>
                <a class="btn btn-primary btn-sm" style="align-self:flex-start;" href="<?= htmlspecialchars(asset('papers/' . rawurlencode($doc['folder']) . '/' . rawurlencode($doc['file']))) ?>" download>
                    <i class="fas fa-download"></i> Download
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>
</div>

<div class="card">
    <div class="card-header"><h3>How to Use Templates</h3></div>
    <div class="card-body">
        <div class="process-steps">
            <div class="process-step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h4>Download the Correct Template</h4>
                    <p>Choose the template matching your current presentation stage.</p>
                </div>
            </div>
            <div class="process-step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h4>Prepare Your Document</h4>
                    <p>Follow the template format exactly when writing your paper.</p>
                </div>
            </div>
            <div class="process-step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h4>Get Adviser Endorsement</h4>
                    <p>Download the adviser endorsement form, have it signed by your adviser, then upload the scanned copy.</p>
                </div>
            </div>
            <div class="process-step">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h4>Upload &amp; Submit</h4>
                    <p>Upload your completed documents in the <a href="<?= url('student/upload.php') ?>">Document Upload</a> section and submit your application.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
