<?php /* Starts the PHP block for the coordinator template library page. */ ?>
<?php
// Loads the shared configuration, helper functions, and constants.
require_once __DIR__ . '/../../includes/config/config.php';
// Loads the shared database connection class.
require_once __DIR__ . '/../../includes/config/db.php';

// Sets the browser page title to 'Manage Templates'.
$pageTitle   = 'Manage Templates';
// Tells the header layout the page belongs to the coordinator role.
$role        = 'coordinator';
// Marks the sidebar item 'templates' as the active menu entry.
$currentPage = 'templates';
// Uses the coordinator name from the shared layout data for the header.
$userName    = $mockCoordinator['name'];

// Loads the shared header, sidebar, and page shell.
require_once __DIR__ . '/../../includes/layout/header.php';
// Closes PHP so the HTML sections below are printed.
?>

<?php /* Opens the page header block at the top of the page. */ ?>
<div class="page-header">
<?php /*     Main heading 'Template and Forms Library' seen by the coordinator. */ ?>
    <h2>Template &amp; Forms Library</h2>
<?php /*     Short description of how the documents are organised. */ ?>
    <p>All documents organized by course type, program, stage, and document type.</p>
<?php /* Closes the page header block. */ ?>
</div>

<?php /* Reopens PHP to prepare the Google Drive sync values. */ ?>
<?php
// "Sync from Google Drive" applies the changes. On a normal page load the
// portal only looks at Drive to find out what changed; nothing is
// downloaded until the coordinator asks for it.
// Prepares an empty message that reports the last sync result.
$syncMessage = '';
// Prepares the CSS class that colours the message as success or error.
$syncNotice  = '';

// Runs the sync only when the sync form was posted.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['form'] ?? '') === 'sync-templates') {
    // Asks Google Drive for changes and applies them to the library.
    $syncResult  = syncTemplatesFromDrive(true);
    // Reads the message text returned by the sync helper.
    $syncMessage = (string) ($syncResult['message'] ?? '');
    // Sets the message style to error when the sync failed.
    $syncNotice  = empty($syncResult['ok']) ? 'is-error' : 'is-success';
// Runs when the page was simply opened instead of synced.
} else {
    // Comment explaining that a normal page load only checks Drive for changes.
    // Find out whether Drive holds files that are not in the system yet.
    // Checks Drive for files that are missing from the system.
    $detect = templateDetectDriveChanges();
    // Keeps the check message only when the check failed and was not skipped.
    if (empty($detect['skipped']) && !empty($detect['message']) && empty($detect['ok'])) {
        // Shows the problem message returned by the Drive check.
        $syncMessage = (string) $detect['message'];
        // Marks the message as an error style.
        $syncNotice  = 'is-error';
    // Closes the 'check failed' condition.
    }
// Closes the sync and check branch.
}

// The files that are waiting to be synchronized.
// Gets the list of files that are waiting to be synchronised.
$pendingChanges = templatePendingChanges();
// Counts how many files are pending.
$pendingCount   = count($pendingChanges);
// Starts the counter for newly added files.
$pendingAdded   = 0;
// Starts the counter for updated files.
$pendingUpdated = 0;
// Loops over each pending file to count its change type.
foreach ($pendingChanges as $pendingItem) {
    // Checks whether this pending file is a new addition.
    if (($pendingItem['change_type'] ?? '') === 'added') {
        // Adds one to the new files counter.
        $pendingAdded++;
    // Runs for files that were changed rather than added.
    } else {
        // Adds one to the updated files counter.
        $pendingUpdated++;
    // Closes the change type check.
    }
// Closes the pending files loop.
}
// Prepares an empty list of phrases for the pending summary.
$pendingParts = [];
// Adds an added phrase when new files are waiting.
if ($pendingAdded > 0) {
    // Builds the new files text, adding a plural s when needed.
    $pendingParts[] = $pendingAdded . ' new file' . ($pendingAdded === 1 ? '' : 's') . ' added';
// Closes the new files condition.
}
// Adds an updated phrase when changed files are waiting.
if ($pendingUpdated > 0) {
    // Builds the updated files text, adding a plural s when needed.
    $pendingParts[] = $pendingUpdated . ' file' . ($pendingUpdated === 1 ? '' : 's') . ' updated';
// Closes the updated files condition.
}
// Joins the phrases into one sentence, or leaves it empty.
$pendingMessage = $pendingParts ? implode(' and ', $pendingParts) : '';

// Loads the template rows that are stored in the database.
$templates = databaseTemplateRows();
// Falls back to the built-in paper library when the table is empty.
$templates = $templates ?: getPaperLibraryRows();

// The same library the students see, grouped by track, course and stage.
// Builds the template library grouped by track, course, and stage.
$templateLibrary     = templateLibrary();
// Tells the library partial that this is the coordinator view.
$templateLibraryMode = 'coordinator';

// Prepares a list that collects every stage label for the stage filter.
$libraryStages = [];
// Loops over each track in the template library.
foreach ($templateLibrary as $libraryTrack) {
    // Loops over each stage inside the current track.
    foreach ($libraryTrack['stages'] as $libraryStage) {
        // Stores the stage label once, keyed by itself, to avoid duplicates.
        $libraryStages[$libraryStage['label']] = $libraryStage['label'];
    // Closes the stages loop.
    }
// Closes the tracks loop.
}

// The updates that are waiting to be read. They are collected before the
// "seen" mark is written, so the list still shows which ones were new.
//
// The history only keeps what happened in the last day, and a file that was
// changed more than once is listed once.
// Removes template notifications that are older than 24 hours.
templatePruneNotifications(24);
// Loads the newest unseen synced files for the top card.
$latestSynced   = templateNotificationsList('coordinator', '', 12, 'unseen');
// Loads recent seen updates for the collapsible history card.
$templateUpdates = templateNotificationsList('coordinator', '', 40, 'seen');
// Marks the coordinator's template updates as seen.
markTemplateNotificationsSeen('coordinator');

// Gets the timestamp of the last Google Drive check.
$lastSyncAt = templateLastSyncAt();

/*
 * A course is matched through the tracks assigned to it (program_tracks), so
 * the course filter can keep exactly the tracks that belong to the selected
 * course. A course that can be followed both ways keeps both of its tracks.
 */
// Prepares a map from each program code to its track codes.
$courseTrackMap = [];
// Loops over every program defined in the PROGRAMS constant.
foreach (PROGRAMS as $programCode => $programName) {
    // Stores the lowercased track list for this program code.
    $courseTrackMap[$programCode] = array_values(array_map('strtolower', getTracksForProgram($programCode)));
// Closes the programs loop.
}
// Closes PHP before the page notices are printed.
?>

<?php /* Shows the notice bar only when a sync message exists. */ ?>
<?php if ($syncMessage !== ''): ?>
<?php /* Notice box coloured by the success or error style. */ ?>
<div class="page-notice <?= htmlspecialchars($syncNotice) ?>">
<?php /*     Cloud icon at the start of the notice. */ ?>
    <i class="fas fa-cloud"></i>
<?php /*     Prints the escaped sync or check message text. */ ?>
    <span class="page-notice-text"><?= htmlspecialchars($syncMessage) ?></span>
<?php /* Closes the notice box. */ ?>
</div>
<?php /* Closes the 'has message' condition. */ ?>
<?php endif; ?>

<?php /* Shows the latest synced files card only when there are new files. */ ?>
<?php if (!empty($latestSynced)): ?>
<?php /* Card that lists files just taken from Google Drive. */ ?>
<div class="card tpl-latest-card">
<?php /*     Opens the card header. */ ?>
    <div class="card-header">
<?php /*         Card title with a download cloud icon. */ ?>
        <h3><i class="fas fa-cloud-arrow-down"></i> Latest Synced Files</h3>
<?php /*         Shows how many files were synced, using singular or plural. */ ?>
        <span class="tpl-update-count"><?= count($latestSynced) ?> file<?= count($latestSynced) === 1 ? '' : 's' ?></span>
<?php /*     Closes the card header. */ ?>
    </div>
<?php /*     Opens the card body. */ ?>
    <div class="card-body">
<?php /*         Hint telling the coordinator these files are already live. */ ?>
        <p class="tpl-update-hint">These files were taken from the Google Drive template folder and are already live for the students.</p>
<?php /*         Opens the list of latest synced files. */ ?>
        <ul class="tpl-update-list">
<?php /*             Loops over each newly synced file. */ ?>
            <?php foreach ($latestSynced as $update): ?>
<?php /*             List item marked as new for styling. */ ?>
            <li class="tpl-update-item is-new">
<?php /*                 Chooses an add icon or a check icon by change type. */ ?>
                <i class="fas <?= $update['change_type'] === 'added' ? 'fa-file-circle-plus' : 'fa-file-circle-check' ?>" aria-hidden="true"></i>
<?php /*                 Opens the wrapper for the file name and details. */ ?>
                <div class="tpl-update-body">
<?php /*                     Prints the escaped file name of the synced file. */ ?>
                    <strong class="tpl-update-name"><?= htmlspecialchars($update['file_name']) ?></strong>
<?php /*                     Opens the metadata line of the synced file. */ ?>
                    <span class="tpl-update-meta">
<?php /*                         Says 'New template added' or 'Template updated'. */ ?>
                        <?= $update['change_type'] === 'added' ? 'New template added' : 'Template updated' ?>
<?php /*                         Shows when the file last changed in Drive. */ ?>
                        &middot; Last updated <?= htmlspecialchars($update['changedLabel']) ?>
<?php /*                         Shows the relative time such as '2 hours ago'. */ ?>
                        (<?= htmlspecialchars($update['ago']) ?>)
<?php /*                     Closes the metadata line. */ ?>
                    </span>
<?php /*                 Closes the file text wrapper. */ ?>
                </div>
<?php /*                 Shows the 'New' badge on the right of the item. */ ?>
                <span class="tpl-update-new">New</span>
<?php /*             Closes the list item. */ ?>
            </li>
<?php /*             Closes the latest synced files loop. */ ?>
            <?php endforeach; ?>
<?php /*         Closes the file list. */ ?>
        </ul>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the latest synced card. */ ?>
</div>
<?php /* Closes the 'has latest synced files' condition. */ ?>
<?php endif; ?>

<?php /* Shows the updates history card only when seen updates exist. */ ?>
<?php if (!empty($templateUpdates)): ?>
<?php /* Card holding the collapsible template update history. */ ?>
<div class="card tpl-updates-card">
<?php /*     Header button that expands or collapses the update list. */ ?>
    <button type="button" class="card-header tpl-updates-toggle" id="tplUpdatesToggle" aria-expanded="false" aria-controls="tplUpdatesBody">
<?php /*         Card title with a bell icon. */ ?>
        <h3><i class="fas fa-bell"></i> Template Updates</h3>
<?php /*         Shows how many updates are listed, using singular or plural. */ ?>
        <span class="tpl-update-count"><?= count($templateUpdates) ?> update<?= count($templateUpdates) === 1 ? '' : 's' ?></span>
<?php /*         Chevron icon that hints the section can be expanded. */ ?>
        <i class="fas fa-chevron-down tpl-updates-caret" aria-hidden="true"></i>
<?php /*     Closes the toggle button. */ ?>
    </button>
<?php /*     Collapsible body that starts hidden. */ ?>
    <div class="card-body" id="tplUpdatesBody" hidden>
<?php /*         Explains these are files added or replaced in the last day. */ ?>
        <p class="tpl-update-hint">The files the teacher added or replaced in the Google Drive template folder during the last day. They are already live for the students.</p>
<?php /*         Opens the list of template updates. */ ?>
        <ul class="tpl-update-list">
<?php /*             Loops over each update entry. */ ?>
            <?php foreach ($templateUpdates as $update): ?>
<?php /*             List item marked as new when the update is unseen. */ ?>
            <li class="tpl-update-item<?= !empty($update['isNew']) ? ' is-new' : '' ?>">
<?php /*                 Chooses an add icon or a check icon by change type. */ ?>
                <i class="fas <?= $update['change_type'] === 'added' ? 'fa-file-circle-plus' : 'fa-file-circle-check' ?>" aria-hidden="true"></i>
<?php /*                 Opens the wrapper for the update name and details. */ ?>
                <div class="tpl-update-body">
<?php /*                     Prints the escaped file name of the update. */ ?>
                    <strong class="tpl-update-name"><?= htmlspecialchars($update['file_name']) ?></strong>
<?php /*                     Opens the metadata line of the update. */ ?>
                    <span class="tpl-update-meta">
<?php /*                         Says 'New template added' or 'Template updated'. */ ?>
                        <?= $update['change_type'] === 'added' ? 'New template added' : 'Template updated' ?>
<?php /*                         Shows when the file last changed. */ ?>
                        &middot; Last updated <?= htmlspecialchars($update['changedLabel']) ?>
<?php /*                         Shows the relative time of the change. */ ?>
                        (<?= htmlspecialchars($update['ago']) ?>)
<?php /*                     Closes the metadata line. */ ?>
                    </span>
<?php /*                 Closes the update text wrapper. */ ?>
                </div>
<?php /*                 Shows the 'New' badge only for unseen updates. */ ?>
                <?php if (!empty($update['isNew'])): ?><span class="tpl-update-new">New</span><?php endif; ?>
<?php /*             Closes the update list item. */ ?>
            </li>
<?php /*             Closes the updates loop. */ ?>
            <?php endforeach; ?>
<?php /*         Closes the update list. */ ?>
        </ul>
<?php /*     Closes the collapsible card body. */ ?>
    </div>
<?php /* Closes the updates history card. */ ?>
</div>
<?php /* Runs the empty card when no updates exist and nothing was synced. */ ?>
<?php elseif (empty($latestSynced)): ?>
<?php /* Card used to tell the coordinator there is nothing new. */ ?>
<div class="card tpl-updates-card">
<?php /*     Header button for the empty updates card. */ ?>
    <button type="button" class="card-header tpl-updates-toggle" id="tplUpdatesToggle" aria-expanded="false" aria-controls="tplUpdatesBody">
<?php /*         Card title with a bell icon. */ ?>
        <h3><i class="fas fa-bell"></i> Template Updates</h3>
<?php /*         Shows that there are zero updates to list. */ ?>
        <span class="tpl-update-count">0 updates</span>
<?php /*         Chevron icon for the empty card header. */ ?>
        <i class="fas fa-chevron-down tpl-updates-caret" aria-hidden="true"></i>
<?php /*     Closes the empty card toggle button. */ ?>
    </button>
<?php /*     Hidden body of the empty updates card. */ ?>
    <div class="card-body" id="tplUpdatesBody" hidden>
<?php /*         Message saying nothing changed in the last day. */ ?>
        <p class="tpl-update-hint">Nothing changed in the Google Drive template folder during the last day.</p>
<?php /*     Closes the empty card body. */ ?>
    </div>
<?php /* Closes the empty updates card. */ ?>
</div>
<?php /* Closes the updates and empty card condition. */ ?>
<?php endif; ?>
<?php /* Starts the script that toggles the updates history. */ ?>
<script>
<?php /* Wraps the toggle script so no variables leak globally. */ ?>
(function () {
<?php /*     Comment explaining that the Template Updates header opens the history. */ ?>
    // "Template Updates" opens the history.
<?php /*     Finds the Template Updates header button. */ ?>
    var toggle = document.getElementById('tplUpdatesToggle');
<?php /*     Finds the collapsible body of that card. */ ?>
    var body = document.getElementById('tplUpdatesBody');
<?php /*     Stops when either element is missing on the page. */ ?>
    if (!toggle || !body) return;
<?php /*     Listens for a click on the updates header. */ ?>
    toggle.addEventListener('click', function () {
<?php /*         Reads whether the body is currently hidden. */ ?>
        var open = body.hidden;
<?php /*         Flips the hidden state to open or close the list. */ ?>
        body.hidden = !open;
<?php /*         Updates the aria-expanded value for screen readers. */ ?>
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
<?php /*         Toggles the is-open class so the chevron can rotate. */ ?>
        toggle.classList.toggle('is-open', open);
<?php /*     Closes the click listener. */ ?>
    });
<?php /* Closes and immediately runs the toggle script wrapper. */ ?>
})();
<?php /* Ends the script block. */ ?>
</script>

<?php /* Toolbar row holding the add button and the sync form. */ ?>
<div class="form-actions" style="border:none;padding:0;margin-bottom:1rem;display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
<?php /*     Primary button that opens the Add Document page. */ ?>
    <a href="<?= url('coordinator/templates/add.php') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Add Document</a>
<?php /*     Sync form that posts back to this manage page. */ ?>
    <form method="post" action="<?= url('coordinator/templates/manage.php') ?>" style="display:inline;" id="tplSyncForm">
<?php /*         Hidden field telling PHP that this is the sync action. */ ?>
        <input type="hidden" name="form" value="sync-templates">
<?php /*         Submit button that starts the Google Drive sync. */ ?>
        <button type="submit" class="btn btn-outline" id="tplSyncButton">
<?php /*             Rotate icon shown beside the sync label. */ ?>
            <i class="fas fa-rotate"></i> Sync from Google Drive
<?php /*             Badge element shown only when files are pending. */ ?>
            <span class="tpl-pending-badge" data-template-pending<?= $pendingCount > 0 ? '' : ' hidden' ?>
<?php /*                   Badge count capped at 9+ with a tooltip showing the real count. */ ?>
                  title="<?= $pendingCount ?> file(s) waiting to be synchronized"><?= $pendingCount > 9 ? '9+' : $pendingCount ?></span>
<?php /*         Closes the sync submit button. */ ?>
        </button>
<?php /*     Closes the sync form. */ ?>
    </form>
<?php /* Closes the toolbar row. */ ?>
</div>

<?php /* Shows the pending notice only when files are waiting. */ ?>
<?php if ($pendingCount > 0): ?>
<?php /* Notice box listing how many files still need syncing. */ ?>
<div class="tpl-pending-notice" data-template-pending-notice>
<?php /*     Bell icon at the start of the pending notice. */ ?>
    <i class="fas fa-bell"></i>
<?php /*     Opens the text wrapper of the pending notice. */ ?>
    <span>
<?php /*         Prints the pending summary, for example '2 new files added'. */ ?>
        <strong><?= htmlspecialchars($pendingMessage) ?></strong> &mdash;
<?php /*         Tells the coordinator to press Sync to apply the changes. */ ?>
        Click <strong>Sync from Google Drive</strong> to apply the changes.
<?php /*     Closes the pending notice text wrapper. */ ?>
    </span>
<?php /*     Button that shows or hides the list of pending files. */ ?>
    <button type="button" class="btn btn-sm btn-outline" data-template-pending-list>Show files</button>
<?php /* Closes the pending notice box. */ ?>
</div>
<?php /* Hidden panel that lists the pending files. */ ?>
<div class="tpl-pending-files" data-template-pending-files hidden>
<?php /*     Opens the pending files list. */ ?>
    <ul>
<?php /*         Loops over each pending file entry. */ ?>
        <?php foreach ($pendingChanges as $pendingItem): ?>
<?php /*         Opens one pending file list item. */ ?>
        <li>
<?php /*             Chooses an add icon or a check icon by change type. */ ?>
            <i class="fas <?= ($pendingItem['change_type'] ?? '') === 'added' ? 'fa-file-circle-plus' : 'fa-file-circle-check' ?>"></i>
<?php /*             Prints the escaped pending file name. */ ?>
            <span class="tpl-pending-name"><?= htmlspecialchars($pendingItem['file_name']) ?></span>
<?php /*             Opens the pending file metadata line. */ ?>
            <span class="tpl-pending-meta">
<?php /*                 Says 'New file' or 'Updated' for this entry. */ ?>
                <?= ($pendingItem['change_type'] ?? '') === 'added' ? 'New file' : 'Updated' ?>
<?php /*                 Shows the change date only when it exists. */ ?>
                <?php if (!empty($pendingItem['changedLabel'])): ?>
<?php /*                 Prints when the pending file was changed. */ ?>
                &middot; <?= htmlspecialchars($pendingItem['changedLabel']) ?>
<?php /*                 Closes the 'has change date' condition. */ ?>
                <?php endif; ?>
<?php /*             Closes the pending metadata line. */ ?>
            </span>
<?php /*         Closes the pending file list item. */ ?>
        </li>
<?php /*         Closes the pending files loop. */ ?>
        <?php endforeach; ?>
<?php /*     Closes the pending files list. */ ?>
    </ul>
<?php /* Closes the hidden pending panel. */ ?>
</div>
<?php /* Starts the script that toggles the pending file list. */ ?>
<script>
<?php /* Wraps the pending list script in a function. */ ?>
(function () {
<?php /*     Finds the Show files button. */ ?>
    var toggle = document.querySelector('[data-template-pending-list]');
<?php /*     Finds the hidden pending files panel. */ ?>
    var files  = document.querySelector('[data-template-pending-files]');
<?php /*     Stops when either element is missing. */ ?>
    if (!toggle || !files) return;
<?php /*     Listens for a click on the Show files button. */ ?>
    toggle.addEventListener('click', function () {
<?php /*         Flips the hidden state of the pending panel. */ ?>
        files.hidden = !files.hidden;
<?php /*         Changes the button label to Show files or Hide files. */ ?>
        toggle.textContent = files.hidden ? 'Show files' : 'Hide files';
<?php /*     Closes the click listener. */ ?>
    });
<?php /* Closes and immediately runs the pending list script. */ ?>
})();
<?php /* Ends the script block. */ ?>
</script>
<?php /* Closes the 'files are pending' condition. */ ?>
<?php endif; ?>

<?php /* Filter bar that searches and filters the template library. */ ?>
<div class="filter-bar" data-filter-table="#tplLibrary" style="margin-bottom:1rem;">
<?php /*     Opens the search input wrapper. */ ?>
    <div class="filter-search">
<?php /*         Search icon shown inside the search input. */ ?>
        <i class="fas fa-search"></i>
<?php /*         Search box that keeps the q value from the URL. */ ?>
        <input type="search" data-filter-q placeholder="Search templates, forms, stages..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
<?php /*     Closes the search input wrapper. */ ?>
    </div>
<?php /*     Clear button that resets all filters, hidden until used. */ ?>
    <button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button>
<?php /*     Course filter dropdown. */ ?>
    <select data-filter="course">
<?php /*         Default option showing every course. */ ?>
        <option value="">All courses</option>
<?php /*         Loops over each program to build the course options. */ ?>
        <?php foreach (PROGRAMS as $programCode => $programName): ?>
<?php /*         Course option kept selected when course is in the URL. */ ?>
        <option value="<?= htmlspecialchars($programCode) ?>" <?= (($_GET['course'] ?? '') === $programCode) ? 'selected' : '' ?>><?= htmlspecialchars($programName) ?></option>
<?php /*         Closes the course options loop. */ ?>
        <?php endforeach; ?>
<?php /*     Closes the course dropdown. */ ?>
    </select>
<?php /*     Track filter dropdown. */ ?>
    <select data-filter="track">
<?php /*         Default option showing every track. */ ?>
        <option value="">All tracks</option>
<?php /*         Loops over each track in the template library. */ ?>
        <?php foreach ($templateLibrary as $libraryTrack): ?>
<?php /*         Track option kept selected when track is in the URL. */ ?>
        <option value="<?= htmlspecialchars($libraryTrack['trackCode']) ?>" <?= (($_GET['track'] ?? '') === $libraryTrack['trackCode']) ? 'selected' : '' ?>><?= htmlspecialchars($libraryTrack['trackName']) ?></option>
<?php /*         Closes the track options loop. */ ?>
        <?php endforeach; ?>
<?php /*     Closes the track dropdown. */ ?>
    </select>
<?php /*     Stage filter dropdown. */ ?>
    <select data-filter="stage">
<?php /*         Default option showing every stage. */ ?>
        <option value="">All stages</option>
<?php /*         Loops over the stage labels collected earlier. */ ?>
        <?php foreach ($libraryStages as $stageLabel => $stageTitle): ?>
<?php /*         Stage option kept selected when stage is in the URL. */ ?>
        <option value="<?= htmlspecialchars($stageLabel) ?>" <?= (($_GET['stage'] ?? '') === $stageLabel) ? 'selected' : '' ?>><?= htmlspecialchars($stageTitle) ?></option>
<?php /*         Closes the stage options loop. */ ?>
        <?php endforeach; ?>
<?php /*         Extra stage option for reference documents. */ ?>
        <option value="Reference Documents" <?= (($_GET['stage'] ?? '') === 'Reference Documents') ? 'selected' : '' ?>>Reference Documents</option>
<?php /*     Closes the stage dropdown. */ ?>
    </select>
<?php /*     Document type filter dropdown. */ ?>
    <select data-filter="type">
<?php /*         Default option showing every document type. */ ?>
        <option value="">All types</option>
<?php /*         Type option for forms. */ ?>
        <option value="Form" <?= (($_GET['type'] ?? '') === 'Form') ? 'selected' : '' ?>>Form</option>
<?php /*         Type option for templates. */ ?>
        <option value="Template" <?= (($_GET['type'] ?? '') === 'Template') ? 'selected' : '' ?>>Template</option>
<?php /*         Type option for reference material. */ ?>
        <option value="Reference" <?= (($_GET['type'] ?? '') === 'Reference') ? 'selected' : '' ?>>Reference</option>
<?php /*     Closes the type dropdown. */ ?>
    </select>
<?php /*     Placeholder where the count of visible items is shown. */ ?>
    <span class="filter-count" data-filter-count></span>
<?php /*     Shows how long ago Google Drive was last checked. */ ?>
    <span class="tpl-sync-status"><i class="fas fa-cloud"></i> Google Drive checked <?= htmlspecialchars(templateTimeAgo($lastSyncAt)) ?></span>
<?php /* Closes the filter bar. */ ?>
</div>

<?php /* Wrapper around the rendered template library tiles. */ ?>
<div id="tplLibrary">
<?php /* Includes the shared library partial that draws tracks and documents. */ ?>
<?php require_once __DIR__ . '/../../includes/components/template_library.php'; ?>

<?php /* Empty state card shown when no tile matches the filters. */ ?>
<div class="card empty-state" data-filter-empty hidden style="text-align:center;padding:3rem 1.5rem;color:var(--gray-400);">
<?php /*     Large folder icon for the empty state card. */ ?>
    <i class="fas fa-folder-open" style="font-size:2.5rem;margin-bottom:1rem;color:var(--gray-300);"></i>
<?php /*     Empty state title 'No templates found'. */ ?>
    <h4 style="font-size:1.1rem;color:var(--gray-600);margin-bottom:0.35rem;">No templates found</h4>
<?php /*     Explains that nothing matches the search or the filters. */ ?>
    <p style="font-size:0.875rem;">No forms or templates match your search or filter criteria.</p>
<?php /* Closes the empty state card. */ ?>
</div>
<?php /* Closes the template library wrapper. */ ?>
</div>

<?php /* Starts a script that passes course and track data to the page. */ ?>
<script>
<?php /* Prints the course to track map as JSON for the filter script. */ ?>
window.CSITE_COURSE_TRACKS = <?= json_encode($courseTrackMap) ?>;
<?php /* Ends the data script block. */ ?>
</script>
<?php /* Starts the script that keeps tracks and course chips in sync. */ ?>
<script>
<?php /* Wraps the filter helper script in a function. */ ?>
(function () {
<?php /*     Comment explaining tracks with no matching documents are hidden. */ ?>
    // Hide a track that has no document left after the filters were applied.
<?php /*     Comment explaining the refresh runs after the page filter is applied. */ ?>
    // The refresh runs after the filter of the page, so the tiles are already
<?php /*     Comment explaining the track tiles are already hidden when checked. */ ?>
    // hidden when the tracks are looked at.
<?php /*     Finds the template library wrapper element. */ ?>
    var library = document.getElementById('tplLibrary');
<?php /*     Stops when the library wrapper is missing. */ ?>
    if (!library) return;
<?php /*     Finds the course dropdown in the filter bar. */ ?>
    var courseFilter = document.querySelector('[data-filter="course"]');

<?php /*     Defines a function that hides tracks with no visible documents. */ ?>
    function refreshTracks() {
<?php /*         Loops over every track section in the library. */ ?>
        library.querySelectorAll('.tpl-track-section').forEach(function (section) {
<?php /*             Collects the document tiles inside this track section. */ ?>
            var items = [...section.querySelectorAll('.template-item')];
<?php /*             Checks whether at least one tile is still visible. */ ?>
            var anyVisible = items.some(function (item) {
<?php /*                 Tests each tile for the hidden state or display none. */ ?>
                return !item.hidden && item.style.display !== 'none';
<?php /*             Closes the visibility test callback. */ ?>
            });
<?php /*             Hides the track section when nothing inside it is visible. */ ?>
            section.hidden = !anyVisible;
<?php /*             Also sets display none so the layout collapses cleanly. */ ?>
            section.style.display = anyVisible ? '' : 'none';
<?php /*         Closes the track section loop. */ ?>
        });
<?php /*     Closes the refreshTracks function. */ ?>
    }

<?php /*     Comment explaining a picked course narrows the course chips row. */ ?>
    // When a course is picked, show only that course in the "Courses using
<?php /*     Comment explaining the track sections are left to the page filter. */ ?>
    // this track" row; its track sections are handled by the page filter.
<?php /*     Defines a function that hides course chips not matching the course. */ ?>
    function refreshCourseChips() {
<?php /*         Reads the currently selected course value, or empty text. */ ?>
        var selected = courseFilter ? courseFilter.value : '';
<?php /*         Loops over every course chip in the library. */ ?>
        library.querySelectorAll('.tpl-course-chip').forEach(function (chip) {
<?php /*             Shows the chip when no course is chosen or when it matches. */ ?>
            var show = !selected || chip.dataset.course === selected;
<?php /*             Hides the chip when it does not match the chosen course. */ ?>
            chip.hidden = !show;
<?php /*             Also sets display none so the chip takes no space. */ ?>
            chip.style.display = show ? '' : 'none';
<?php /*         Closes the chip loop. */ ?>
        });
<?php /*     Closes the refreshCourseChips function. */ ?>
    }

<?php /*     Defines a function that refreshes the view after filtering. */ ?>
    function schedule() {
<?php /*         Waits one tick so the page filter can finish first. */ ?>
        setTimeout(function () {
<?php /*             Re-hides tracks that now have no visible documents. */ ?>
            refreshTracks();
<?php /*             Hides course chips that do not match the chosen course. */ ?>
            refreshCourseChips();
<?php /*         Closes the delayed refresh callback. */ ?>
        }, 0);
<?php /*     Closes the schedule function. */ ?>
    }
<?php /*     Finds the filter bar that controls the template library. */ ?>
    var bar = document.querySelector('[data-filter-table="#tplLibrary"]');
<?php /*     Runs the listener setup only when the filter bar exists. */ ?>
    if (bar) {
<?php /*         Finds the search input inside the filter bar. */ ?>
        var search = bar.querySelector('[data-filter-q]');
<?php /*         Finds the clear filters button. */ ?>
        var clearBtn = bar.querySelector('[data-filter-clear]');
<?php /*         Collects all the filter dropdowns into a list. */ ?>
        var selects = [...bar.querySelectorAll('select[data-filter]')];
<?php /*         Refreshes the view when the search text changes. */ ?>
        search?.addEventListener('input', schedule);
<?php /*         Listens for key presses in the search box. */ ?>
        search?.addEventListener('keydown', function (event) {
<?php /*             Comment explaining Escape clears the filters then refreshes the tiles. */ ?>
            // Escape clears the filters; refresh once the page filter has run.
<?php /*             Refreshes after Escape clears the filters. */ ?>
            if (event.key === 'Escape') schedule();
<?php /*         Closes the search keydown listener. */ ?>
        });
<?php /*         Refreshes the view when the clear button is clicked. */ ?>
        clearBtn?.addEventListener('click', schedule);
<?php /*         Refreshes the view whenever a filter dropdown changes. */ ?>
        selects.forEach(function (sel) { sel.addEventListener('change', schedule); });
<?php /*     Closes the 'filter bar exists' block. */ ?>
    }
<?php /*     Refreshes once the whole page has loaded. */ ?>
    window.addEventListener('load', schedule);
<?php /*     Runs an initial refresh when the script starts. */ ?>
    schedule();
<?php /* Closes and immediately runs the filter helper script. */ ?>
})();
<?php /* Ends the script block. */ ?>
</script>

<?php /* Loads the shared footer and closes the page. */ ?>
<?php require_once __DIR__ . '/../../includes/layout/footer.php'; ?>
