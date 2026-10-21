<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$mockStudent = currentStudentProfile($mockStudent);

$pageTitle   = 'Templates & Forms';
$role        = 'student';
$currentPage = 'templates';
$userName    = $mockStudent['name'];

$track       = $mockStudent['track'] ?? getTrackForProgram($mockStudent['program']);
$trackLabel  = getTrackLabel($track);
$workflow    = getWorkflow($track);
$databaseTemplates = databaseTemplateRows($track);
$configuredFiles = [];
foreach ($workflow['stages'] as $configuredStage) {
    foreach ($configuredStage['documents'] as $configuredDocument) {
        if (($configuredDocument['file'] ?? '') !== '') {
            $configuredFiles[strtolower(trim($configuredDocument['file']))] = true;
        }
    }
    foreach (['adviserEndorsementForm', 'gradSchoolEndorsementForm'] as $formKey) {
        $configuredFile = $configuredStage[$formKey]['file'] ?? '';
        if ($configuredFile !== '') {
            $configuredFiles[strtolower(trim($configuredFile))] = true;
        }
    }
}
foreach ($workflow['refs'] as $configuredReference) {
    if (($configuredReference['file'] ?? '') !== '') {
        $configuredFiles[strtolower(trim($configuredReference['file']))] = true;
    }
}
$databaseByStage = [];
$databaseReferences = [];
foreach ($databaseTemplates as $template) {
    $databaseFile = strtolower(trim((string) ($template['file'] ?? '')));
    if ($databaseFile !== '' && isset($configuredFiles[$databaseFile])) {
        continue;
    }
    $templateStage = trim((string) ($template['stage'] ?? ''));
    $templateDoc = [
        'label' => $template['label'] ?? $template['file'] ?? 'Template',
        'file' => $template['file'] ?? '',
        'folder' => '',
        'type' => strtolower((string) ($template['docType'] ?? 'template')),
        'description' => $template['description'] ?? '',
        'url' => $template['url'] ?? '',
    ];
    if ($templateStage === '' || $templateStage === 'Reference Documents') {
        $databaseReferences[] = $templateDoc;
    } else {
        $databaseByStage[$templateStage][] = $templateDoc;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h2>Templates &amp; Forms</h2>
    <p>Download forms and templates for <?= htmlspecialchars($mockStudent['program_name']) ?> (<?= htmlspecialchars($trackLabel) ?> Track).</p>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    <div>
        Download the forms and templates intended for your program and presentation stage.
    </div>
</div>

<div class="filter-bar" data-filter-table="#studentTplGrid" style="margin-bottom:1rem;">
    <div class="filter-search">
        <i class="fas fa-search"></i>
        <input type="search" data-filter-q placeholder="Search templates, forms, stages..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
    </div>
    <button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button>
    <select data-filter="stage">
        <option value="">All stages</option>
        <?php foreach ($workflow['stages'] as $s): ?>
        <option value="<?= htmlspecialchars($s['label']) ?>" <?= (($_GET['stage'] ?? '') === $s['label']) ? 'selected' : '' ?>><?= htmlspecialchars($s['shortLabel'] ?? $s['label']) ?></option>
        <?php endforeach; ?>
        <?php if (!empty($workflow['refs'])): ?>
        <option value="Reference Documents" <?= (($_GET['stage'] ?? '') === 'Reference Documents') ? 'selected' : '' ?>>Reference Documents</option>
        <?php endif; ?>
    </select>
    <select data-filter="type">
        <option value="">All types</option>
        <option value="Form" <?= (($_GET['type'] ?? '') === 'Form') ? 'selected' : '' ?>>Form</option>
        <option value="Template" <?= (($_GET['type'] ?? '') === 'Template') ? 'selected' : '' ?>>Template</option>
        <option value="Reference" <?= (($_GET['type'] ?? '') === 'Reference') ? 'selected' : '' ?>>Reference</option>
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
    foreach ($databaseByStage[$stage['label']] ?? [] as $databaseDoc) {
        $stageDocs[] = $databaseDoc;
    }
    $stageDocs = array_values(array_filter($stageDocs, static function ($d) {
        return ($d['file'] ?? '') !== '' || ($d['url'] ?? '') !== '';
    }));
?>
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-layer-group"></i> <?= htmlspecialchars($stage['label']) ?></h3>
    </div>
    <div class="card-body">
        <div class="template-grid">
            <?php foreach ($stageDocs as $doc):
                $href = ($doc['url'] ?? '') !== ''
                    ? $doc['url']
                    : asset('papers/' . rawurlencode($doc['folder']) . '/' . rawurlencode($doc['file']));
                $isForm = strtolower((string) ($doc['type'] ?? '')) === 'form';
                $isReference = strtolower((string) ($doc['type'] ?? '')) === 'reference';
                $displayType = $isForm ? 'Form' : ($isReference ? 'Reference' : 'Template');
                $searchKeywords = strtolower($doc['label'] . ' ' . $doc['file'] . ' ' . ($doc['description'] ?? '') . ' ' . $stage['label'] . ' ' . ($stage['shortLabel'] ?? '') . ' ' . ($isForm ? 'form' : 'template'));
            ?>
            <div class="template-item" data-stage="<?= htmlspecialchars($stage['label']) ?>" data-type="<?= htmlspecialchars($displayType) ?>" data-search="<?= htmlspecialchars($searchKeywords) ?>">
                <div class="template-icon"><i class="fas <?= $isForm ? 'fa-file-signature' : ($isReference ? 'fa-book-open' : 'fa-file-word') ?>"></i></div>
                <h4><?= htmlspecialchars($doc['label']) ?></h4>
                <p><?= htmlspecialchars($doc['description']) ?></p>
                <span class="template-meta"><i class="fas fa-tag"></i> <?= $isForm ? 'Form' : ($isReference ? 'Reference' : 'Template') ?></span>
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
            <?php foreach (array_merge($workflow['refs'], $databaseReferences) as $doc):
                $searchKeywords = strtolower($doc['label'] . ' ' . $doc['file'] . ' ' . ($doc['description'] ?? '') . ' reference documents');
                $href = ($doc['url'] ?? '') !== ''
                    ? $doc['url']
                    : asset('papers/' . rawurlencode($doc['folder']) . '/' . rawurlencode($doc['file']));
            ?>
            <div class="template-item" data-stage="Reference Documents" data-type="Reference" data-search="<?= htmlspecialchars($searchKeywords) ?>">
                <div class="template-icon"><i class="fas fa-file-word"></i></div>
                <h4><?= htmlspecialchars($doc['label']) ?></h4>
                <p><?= htmlspecialchars($doc['description']) ?></p>
                <span class="template-meta"><i class="fas fa-tag"></i> Reference</span>
                <a class="btn btn-primary btn-sm" style="align-self:flex-start;" href="<?= htmlspecialchars($href) ?>" download>
                    <i class="fas fa-download"></i> Download
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card empty-state" data-filter-empty hidden style="text-align:center;padding:3rem 1.5rem;color:var(--gray-400);">
    <i class="fas fa-folder-open" style="font-size:2.5rem;margin-bottom:1rem;color:var(--gray-300);"></i>
    <h4 style="font-size:1.1rem;color:var(--gray-600);margin-bottom:0.35rem;">No templates found</h4>
    <p style="font-size:0.875rem;">No forms or templates match your search or filter criteria.</p>
</div>
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
                    <p>Upload your completed documents on the <a href="<?= url('student/application.php') ?>">Application</a> page and submit your application.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
