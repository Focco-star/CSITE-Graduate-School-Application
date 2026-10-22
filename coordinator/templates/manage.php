<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

$pageTitle   = 'Manage Templates';
$role        = 'coordinator';
$currentPage = 'templates';
$userName    = $mockCoordinator['name'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Template &amp; Forms Library</h2>
    <p>All documents organized by course type, program, stage, and document type.</p>
</div>

<?php
// "Sync from Google Drive" applies the changes. On a normal page load the
// portal only looks at Drive to find out what changed; nothing is
// downloaded until the coordinator asks for it.
$syncMessage = '';
$syncNotice  = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['form'] ?? '') === 'sync-templates') {
    $syncResult  = syncTemplatesFromDrive(true);
    $syncMessage = (string) ($syncResult['message'] ?? '');
    $syncNotice  = empty($syncResult['ok']) ? 'is-error' : 'is-success';
} else {
    // Find out whether Drive holds files that are not in the system yet.
    $detect = templateDetectDriveChanges();
    if (empty($detect['skipped']) && !empty($detect['message']) && empty($detect['ok'])) {
        $syncMessage = (string) $detect['message'];
        $syncNotice  = 'is-error';
    }
}

// The files that are waiting to be synchronized.
$pendingChanges = templatePendingChanges();
$pendingCount   = count($pendingChanges);
$pendingAdded   = 0;
$pendingUpdated = 0;
foreach ($pendingChanges as $pendingItem) {
    if (($pendingItem['change_type'] ?? '') === 'added') {
        $pendingAdded++;
    } else {
        $pendingUpdated++;
    }
}
$pendingParts = [];
if ($pendingAdded > 0) {
    $pendingParts[] = $pendingAdded . ' new file' . ($pendingAdded === 1 ? '' : 's') . ' added';
}
if ($pendingUpdated > 0) {
    $pendingParts[] = $pendingUpdated . ' file' . ($pendingUpdated === 1 ? '' : 's') . ' updated';
}
$pendingMessage = $pendingParts ? implode(' and ', $pendingParts) : '';

$templates = databaseTemplateRows();
$templates = $templates ?: getPaperLibraryRows();

// The same library the students see, grouped by track, course and stage.
$templateLibrary     = templateLibrary();
$templateLibraryMode = 'coordinator';

$libraryStages = [];
foreach ($templateLibrary as $libraryTrack) {
    foreach ($libraryTrack['stages'] as $libraryStage) {
        $libraryStages[$libraryStage['label']] = $libraryStage['label'];
    }
}

// The updates that are waiting to be read. They are collected before the
// "seen" mark is written, so the list still shows which ones were new.
//
// The history only keeps what happened in the last day, and a file that was
// changed more than once is listed once.
templatePruneNotifications(24);
$latestSynced   = templateNotificationsList('coordinator', '', 12, 'unseen');
$templateUpdates = templateNotificationsList('coordinator', '', 40, 'seen');
markTemplateNotificationsSeen('coordinator');

$lastSyncAt = templateLastSyncAt();
?>

<?php if ($syncMessage !== ''): ?>
<div class="page-notice <?= htmlspecialchars($syncNotice) ?>">
    <i class="fas fa-cloud"></i>
    <span class="page-notice-text"><?= htmlspecialchars($syncMessage) ?></span>
</div>
<?php endif; ?>

<?php if (!empty($latestSynced)): ?>
<div class="card tpl-latest-card">
    <div class="card-header">
        <h3><i class="fas fa-cloud-arrow-down"></i> Latest Synced Files</h3>
        <span class="tpl-update-count"><?= count($latestSynced) ?> file<?= count($latestSynced) === 1 ? '' : 's' ?></span>
    </div>
    <div class="card-body">
        <p class="tpl-update-hint">These files were taken from the Google Drive template folder and are already live for the students.</p>
        <ul class="tpl-update-list">
            <?php foreach ($latestSynced as $update): ?>
            <li class="tpl-update-item is-new">
                <i class="fas <?= $update['change_type'] === 'added' ? 'fa-file-circle-plus' : 'fa-file-circle-check' ?>" aria-hidden="true"></i>
                <div class="tpl-update-body">
                    <strong class="tpl-update-name"><?= htmlspecialchars($update['file_name']) ?></strong>
                    <span class="tpl-update-meta">
                        <?= $update['change_type'] === 'added' ? 'New template added' : 'Template updated' ?>
                        &middot; Last updated <?= htmlspecialchars($update['changedLabel']) ?>
                        (<?= htmlspecialchars($update['ago']) ?>)
                    </span>
                </div>
                <span class="tpl-update-new">New</span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($templateUpdates)): ?>
<div class="card tpl-updates-card">
    <button type="button" class="card-header tpl-updates-toggle" id="tplUpdatesToggle" aria-expanded="false" aria-controls="tplUpdatesBody">
        <h3><i class="fas fa-bell"></i> Template Updates</h3>
        <span class="tpl-update-count"><?= count($templateUpdates) ?> update<?= count($templateUpdates) === 1 ? '' : 's' ?></span>
        <i class="fas fa-chevron-down tpl-updates-caret" aria-hidden="true"></i>
    </button>
    <div class="card-body" id="tplUpdatesBody" hidden>
        <p class="tpl-update-hint">The files the teacher added or replaced in the Google Drive template folder during the last day. They are already live for the students.</p>
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
<?php elseif (empty($latestSynced)): ?>
<div class="card tpl-updates-card">
    <button type="button" class="card-header tpl-updates-toggle" id="tplUpdatesToggle" aria-expanded="false" aria-controls="tplUpdatesBody">
        <h3><i class="fas fa-bell"></i> Template Updates</h3>
        <span class="tpl-update-count">0 updates</span>
        <i class="fas fa-chevron-down tpl-updates-caret" aria-hidden="true"></i>
    </button>
    <div class="card-body" id="tplUpdatesBody" hidden>
        <p class="tpl-update-hint">Nothing changed in the Google Drive template folder during the last day.</p>
    </div>
</div>
<?php endif; ?>
<script>
(function () {
    // "Template Updates" opens the history.
    var toggle = document.getElementById('tplUpdatesToggle');
    var body = document.getElementById('tplUpdatesBody');
    if (!toggle || !body) return;
    toggle.addEventListener('click', function () {
        var open = body.hidden;
        body.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.classList.toggle('is-open', open);
    });
})();
</script>

<div class="form-actions" style="border:none;padding:0;margin-bottom:1rem;display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
    <a href="<?= url('coordinator/templates/add.php') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Add Document</a>
    <form method="post" action="<?= url('coordinator/templates/manage.php') ?>" style="display:inline;" id="tplSyncForm">
        <input type="hidden" name="form" value="sync-templates">
        <button type="submit" class="btn btn-outline" id="tplSyncButton">
            <i class="fas fa-rotate"></i> Sync from Google Drive
            <span class="tpl-pending-badge" data-template-pending<?= $pendingCount > 0 ? '' : ' hidden' ?>
                  title="<?= $pendingCount ?> file(s) waiting to be synchronized"><?= $pendingCount > 9 ? '9+' : $pendingCount ?></span>
        </button>
    </form>
</div>

<?php if ($pendingCount > 0): ?>
<div class="tpl-pending-notice" data-template-pending-notice>
    <i class="fas fa-bell"></i>
    <span>
        <strong><?= htmlspecialchars($pendingMessage) ?></strong> &mdash;
        Click <strong>Sync from Google Drive</strong> to apply the changes.
    </span>
    <button type="button" class="btn btn-sm btn-outline" data-template-pending-list>Show files</button>
</div>
<div class="tpl-pending-files" data-template-pending-files hidden>
    <ul>
        <?php foreach ($pendingChanges as $pendingItem): ?>
        <li>
            <i class="fas <?= ($pendingItem['change_type'] ?? '') === 'added' ? 'fa-file-circle-plus' : 'fa-file-circle-check' ?>"></i>
            <span class="tpl-pending-name"><?= htmlspecialchars($pendingItem['file_name']) ?></span>
            <span class="tpl-pending-meta">
                <?= ($pendingItem['change_type'] ?? '') === 'added' ? 'New file' : 'Updated' ?>
                <?php if (!empty($pendingItem['changedLabel'])): ?>
                &middot; <?= htmlspecialchars($pendingItem['changedLabel']) ?>
                <?php endif; ?>
            </span>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<script>
(function () {
    var toggle = document.querySelector('[data-template-pending-list]');
    var files  = document.querySelector('[data-template-pending-files]');
    if (!toggle || !files) return;
    toggle.addEventListener('click', function () {
        files.hidden = !files.hidden;
        toggle.textContent = files.hidden ? 'Show files' : 'Hide files';
    });
})();
</script>
<?php endif; ?>

<div class="filter-bar" data-filter-table="#tplLibrary" style="margin-bottom:1rem;">
    <div class="filter-search">
        <i class="fas fa-search"></i>
        <input type="search" data-filter-q placeholder="Search templates, forms, stages..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
    </div>
    <button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button>
    <select data-filter="track">
        <option value="">All tracks</option>
        <?php foreach ($templateLibrary as $libraryTrack): ?>
        <option value="<?= htmlspecialchars($libraryTrack['trackCode']) ?>" <?= (($_GET['track'] ?? '') === $libraryTrack['trackCode']) ? 'selected' : '' ?>><?= htmlspecialchars($libraryTrack['trackName']) ?></option>
        <?php endforeach; ?>
    </select>
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
    <span class="tpl-sync-status"><i class="fas fa-cloud"></i> Google Drive checked <?= htmlspecialchars(templateTimeAgo($lastSyncAt)) ?></span>
</div>

<div id="tplLibrary">
<?php require_once __DIR__ . '/../../includes/template_library.php'; ?>

<div class="card empty-state" data-filter-empty hidden style="text-align:center;padding:3rem 1.5rem;color:var(--gray-400);">
    <i class="fas fa-folder-open" style="font-size:2.5rem;margin-bottom:1rem;color:var(--gray-300);"></i>
    <h4 style="font-size:1.1rem;color:var(--gray-600);margin-bottom:0.35rem;">No templates found</h4>
    <p style="font-size:0.875rem;">No forms or templates match your search or filter criteria.</p>
</div>
</div>

<script>
(function () {
    // Hide a track that has no document left after the filters were applied.
    // The refresh runs after the filter of the page, so the tiles are already
    // hidden when the tracks are looked at.
    var library = document.getElementById('tplLibrary');
    if (!library) return;
    function refreshTracks() {
        library.querySelectorAll('.tpl-track-section').forEach(function (section) {
            var items = [...section.querySelectorAll('.template-item')];
            var anyVisible = items.some(function (item) {
                return !item.hidden && item.style.display !== 'none';
            });
            section.hidden = !anyVisible;
            section.style.display = anyVisible ? '' : 'none';
        });
    }
    function schedule() { setTimeout(refreshTracks, 0); }
    var bar = document.querySelector('[data-filter-table="#tplLibrary"]');
    if (bar) {
        var search = bar.querySelector('[data-filter-q]');
        var selects = [...bar.querySelectorAll('select[data-filter]')];
        search?.addEventListener('input', schedule);
        selects.forEach(function (sel) { sel.addEventListener('change', schedule); });
    }
    window.addEventListener('load', schedule);
    schedule();
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
