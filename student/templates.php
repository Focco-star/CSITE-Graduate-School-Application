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

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h2>Templates &amp; Forms</h2>
    <p>Download forms and templates for <?= htmlspecialchars($mockStudent['program_name']) ?> (<?= htmlspecialchars($trackLabel) ?> Track).</p>
</div>

<?php
// Look at Google Drive to find out whether the teacher changed a form. The
// change itself is applied by the coordinator, so the student is told that
// a new version is on its way.
maybeAutoSyncTemplates();

$pendingChanges = templatePendingChanges($track);
$pendingCount   = count($pendingChanges);

// The library is read from the same records the coordinator manages, so a
// template the coordinator changes is the one the student gets.
$templateLibrary       = templateLibrary($track);
$templateLibraryMode   = 'student';
$templateLibraryCourse = [
    'code' => (string) ($mockStudent['program'] ?? ''),
    'name' => (string) ($mockStudent['program_name'] ?? ''),
];

// The updates that are waiting to be read. They are collected before the
// "seen" mark is written, so the list still shows which ones were new.
$templateUpdates = templateNotificationsList('student', $track);
markTemplateNotificationsSeen('student', $track);

$lastSyncAt = templateLastSyncAt();
$libraryStages = [];
foreach ($templateLibrary as $libraryTrack) {
    foreach ($libraryTrack['stages'] as $libraryStage) {
        $libraryStages[$libraryStage['label']] = $libraryStage['label'];
    }
}
?>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    <div>
        All documents below are specific to <strong><?= htmlspecialchars($mockStudent['program_name']) ?></strong>.
        Use only the files intended for your program and presentation stage.
    </div>
</div>

<?php if ($pendingCount > 0): ?>
<div class="tpl-pending-notice" data-template-pending-notice>
    <i class="fas fa-bell"></i>
    <span>
        A new version of <?= $pendingCount === 1 ? 'a template' : 'templates' ?> for your track
        (<?= htmlspecialchars(implode(', ', array_map(static fn($item) => (string) $item['file_name'], $pendingChanges))) ?>)
        is being applied. Please download it again once the notice is gone.
    </span>
</div>
<?php endif; ?>

<?php if (!empty($templateUpdates)): ?>
<div class="card tpl-updates-card">
    <div class="card-header">
        <h3><i class="fas fa-bell"></i> Template Updates</h3>
        <span class="tpl-update-count"><?= count($templateUpdates) ?> update<?= count($templateUpdates) === 1 ? '' : 's' ?></span>
    </div>
    <div class="card-body">
        <p class="tpl-update-hint">These forms and templates were added or changed by the Graduate School. Download the newest version before you fill it in.</p>
        <ul class="tpl-update-list">
            <?php foreach ($templateUpdates as $update): ?>
            <li class="tpl-update-item<?= !empty($update['isNew']) ? ' is-new' : '' ?>">
                <i class="fas <?= $update['change_type'] === 'added' ? 'fa-file-circle-plus' : 'fa-file-circle-check' ?>" aria-hidden="true"></i>
                <div class="tpl-update-body">
                    <strong class="tpl-update-name"><?= htmlspecialchars($update['file_name']) ?></strong>
                    <span class="tpl-update-meta">
                        <?= $update['change_type'] === 'added' ? 'New template added' : 'Template updated' ?>
                        &middot; Last updated <?= htmlspecialchars($update['changedLabel']) ?>
                        (<?= htmlspecialchars($update['ago']) ?>)
                    </span>
                </div>
                <?php if (!empty($update['isNew'])): ?><span class="tpl-update-new">New</span><?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<div class="filter-bar" data-filter-table="#studentTplGrid" style="margin-bottom:1rem;">
    <div class="filter-search">
        <i class="fas fa-search"></i>
        <input type="search" data-filter-q placeholder="Search templates, forms, stages..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
    </div>
    <button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button>
    <span class="tpl-sync-status"><i class="fas fa-cloud"></i> Google Drive checked <?= htmlspecialchars(templateTimeAgo($lastSyncAt)) ?></span>
    <select data-filter="stage">
        <option value="">All stages</option>
        <?php foreach ($libraryStages as $stageLabel => $stageTitle): ?>
        <option value="<?= htmlspecialchars($stageLabel) ?>" <?= (($_GET['stage'] ?? '') === $stageLabel) ? 'selected' : '' ?>><?= htmlspecialchars($stageTitle) ?></option>
        <?php endforeach; ?>
        <option value="Reference Documents" <?= (($_GET['stage'] ?? '') === 'Reference Documents') ? 'selected' : '' ?>>Reference Documents</option>
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
<?php require_once __DIR__ . '/../includes/template_library.php'; ?>
</div>

<div class="card empty-state" data-filter-empty hidden style="text-align:center;padding:3rem 1.5rem;color:var(--gray-400);">
    <i class="fas fa-folder-open" style="font-size:2.5rem;margin-bottom:1rem;color:var(--gray-300);"></i>
    <h4 style="font-size:1.1rem;color:var(--gray-600);margin-bottom:0.35rem;">No templates found</h4>
    <p style="font-size:0.875rem;">No forms or templates match your search or filter criteria.</p>
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
                    <h4>Complete the Graduate School Endorsement</h4>
                    <p>Download and complete the endorsement form for your stage, then upload it on the <a href="<?= url('student/application.php') ?>">Application</a> page for review.</p>
                </div>
            </div>
            <div class="process-step">
                <div class="step-number">5</div>
                <div class="step-content">
                    <h4>Upload &amp; Submit</h4>
                    <p>Upload your completed documents on the <a href="<?= url('student/application.php') ?>">Application</a> page and submit your application.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
