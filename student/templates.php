<?php /* Opens the PHP block for the student templates page. */ ?>
<?php
// Loads the shared config with session, constants, and helpers.
require_once __DIR__ . '/../includes/config.php';
// Loads the DB helper used by the template helpers.
require_once __DIR__ . '/../includes/db.php';

// Loads the logged-in student's profile into $mockStudent.
$mockStudent = currentStudentProfile($mockStudent);

// Sets the page title to Templates and Forms.
$pageTitle   = 'Templates & Forms';
// Tells the shared layout that the role is student.
$role        = 'student';
// Marks templates as the active sidebar menu item.
$currentPage = 'templates';
// Stores the student's name for the header.
$userName    = $mockStudent['name'];

// Reads the track, guessing it from the program if missing.
$track       = $mockStudent['track'] ?? getTrackForProgram($mockStudent['program']);
// Turns the track key into a readable label.
$trackLabel  = getTrackLabel($track);
// Loads the workflow definition for this track.
$workflow    = getWorkflow($track);

// Includes the shared header with styles and navigation.
require_once __DIR__ . '/../includes/header.php';
// Closes the PHP block before the page HTML.
?>

<?php /* Banner at the top of the Templates and Forms page. */ ?>
<div class="page-header page-banner">
<?php /*     Heading of the Templates and Forms page. */ ?>
    <h2>Templates &amp; Forms</h2>
<?php /*     Shows the program name and track the files are meant for. */ ?>
    <p>Download forms and templates for <?= htmlspecialchars($mockStudent['program_name']) ?> (<?= htmlspecialchars($trackLabel) ?> Track).</p>
<?php /* Closes the page banner. */ ?>
</div>

<?php /* Starts PHP to gather the template library and notices. */ ?>
<?php
// Look at Google Drive to find out whether the teacher changed a form. The
// change itself is applied by the coordinator, so the student is told that
// a new version is on its way.
// Checks Google Drive for template changes before listing them.
maybeAutoSyncTemplates();

// Gets the templates whose new versions are still being applied.
$pendingChanges = templatePendingChanges($track);
// Counts how many pending changes there are.
$pendingCount   = count($pendingChanges);

// The library is read from the same records the coordinator manages, so a
// template the coordinator changes is the one the student gets.
// Loads the template library for the student's track.
$templateLibrary       = templateLibrary($track);
// Tells the library include that this is the student view.
$templateLibraryMode   = 'student';
// Starts the course details shown in the library header.
$templateLibraryCourse = [
    // Stores the program code.
    'code' => (string) ($mockStudent['program'] ?? ''),
    // Stores the readable program name.
    'name' => (string) ($mockStudent['program_name'] ?? ''),
// Closes the course details array.
];

// The updates that are waiting to be read. They are collected before the
// "seen" mark is written, so the list still shows which ones were new.
// Loads the template update notices for students.
$templateUpdates = templateNotificationsList('student', $track);
// Marks those update notices as read for this student.
markTemplateNotificationsSeen('student', $track);

// Reads the time Google Drive was last checked.
$lastSyncAt = templateLastSyncAt();
// Starts an empty list of stage names for the filter.
$libraryStages = [];
// Loops over each track in the library.
foreach ($templateLibrary as $libraryTrack) {
    // Loops over each stage in that track.
    foreach ($libraryTrack['stages'] as $libraryStage) {
        // Adds the stage label as a filter option.
        $libraryStages[$libraryStage['label']] = $libraryStage['label'];
    // Closes the stage loop.
    }
// Closes the track loop.
}
// Closes the PHP block before the notice banners.
?>

<?php /* Blue info alert that explains the files are program specific. */ ?>
<div class="alert alert-info">
<?php /*     Info icon inside the alert. */ ?>
    <i class="fas fa-info-circle"></i>
<?php /*     Wraps the alert text. */ ?>
    <div>
<?php /*         Shows the student's program name in bold. */ ?>
        All documents below are specific to <strong><?= htmlspecialchars($mockStudent['program_name']) ?></strong>.
<?php /*         Tells the student to use only files for their own program. */ ?>
        Use only the files intended for your program and presentation stage.
<?php /*     Closes the alert text wrapper. */ ?>
    </div>
<?php /* Closes the info alert box. */ ?>
</div>

<?php /* Only shows the pending notice when a change is coming. */ ?>
<?php if ($pendingCount > 0): ?>
<?php /* Soft notice box that warns about an incoming new version. */ ?>
<div class="tpl-pending-notice" data-template-pending-notice>
<?php /*     Bell icon inside the pending notice. */ ?>
    <i class="fas fa-bell"></i>
<?php /*     Wraps the pending notice text. */ ?>
    <span>
<?php /*         Says a template or templates are being updated. */ ?>
        A new version of <?= $pendingCount === 1 ? 'a template' : 'templates' ?> for your track
<?php /*         Lists the names of the files being updated. */ ?>
        (<?= htmlspecialchars(implode(', ', array_map(static fn($item) => (string) $item['file_name'], $pendingChanges))) ?>)
<?php /*         Asks the student to download again once the notice is gone. */ ?>
        is being applied. Please download it again once the notice is gone.
<?php /*     Closes the notice text wrapper. */ ?>
    </span>
<?php /* Closes the pending notice box. */ ?>
</div>
<?php /* Closes the pending notice if. */ ?>
<?php endif; ?>

<?php /* Only shows the updates card when there are updates. */ ?>
<?php if (!empty($templateUpdates)): ?>
<?php /* Card that lists recently added or changed templates. */ ?>
<div class="card tpl-updates-card">
<?php /*     Header of the updates card. */ ?>
    <div class="card-header">
<?php /*         Title of the updates card with a bell icon. */ ?>
        <h3><i class="fas fa-bell"></i> Template Updates</h3>
<?php /*         Shows how many updates there are, with correct wording. */ ?>
        <span class="tpl-update-count"><?= count($templateUpdates) ?> update<?= count($templateUpdates) === 1 ? '' : 's' ?></span>
<?php /*     Closes the updates card header. */ ?>
    </div>
<?php /*     Body of the updates card. */ ?>
    <div class="card-body">
<?php /*         Hint telling the student to download the newest version. */ ?>
        <p class="tpl-update-hint">These forms and templates were added or changed by the Graduate School. Download the newest version before you fill it in.</p>
<?php /*         List that holds one entry per template update. */ ?>
        <ul class="tpl-update-list">
<?php /*             Loops over each template update. */ ?>
            <?php foreach ($templateUpdates as $update): ?>
<?php /*             One update row, highlighted when it is new to the student. */ ?>
            <li class="tpl-update-item<?= !empty($update['isNew']) ? ' is-new' : '' ?>">
<?php /*                 Plus or check icon depending on added or updated. */ ?>
                <i class="fas <?= $update['change_type'] === 'added' ? 'fa-file-circle-plus' : 'fa-file-circle-check' ?>" aria-hidden="true"></i>
<?php /*                 Body of the update row. */ ?>
                <div class="tpl-update-body">
<?php /*                     Shows the template file name. */ ?>
                    <strong class="tpl-update-name"><?= htmlspecialchars($update['file_name']) ?></strong>
<?php /*                     Small line of details under the file name. */ ?>
                    <span class="tpl-update-meta">
<?php /*                         Says New template added or Template updated. */ ?>
                        <?= $update['change_type'] === 'added' ? 'New template added' : 'Template updated' ?>
<?php /*                         Shows when the template was last changed. */ ?>
                        &middot; Last updated <?= htmlspecialchars($update['changedLabel']) ?>
<?php /*                         Shows how long ago the change happened. */ ?>
                        (<?= htmlspecialchars($update['ago']) ?>)
<?php /*                     Closes the details line. */ ?>
                    </span>
<?php /*                 Closes the update row body. */ ?>
                </div>
<?php /*                 Shows a New badge for updates the student has not seen. */ ?>
                <?php if (!empty($update['isNew'])): ?><span class="tpl-update-new">New</span><?php endif; ?>
<?php /*             Closes the update row. */ ?>
            </li>
<?php /*             Ends the loop over template updates. */ ?>
            <?php endforeach; ?>
<?php /*         Closes the update list. */ ?>
        </ul>
<?php /*     Closes the updates card body. */ ?>
    </div>
<?php /* Closes the updates card. */ ?>
</div>
<?php /* Closes the updates card if. */ ?>
<?php endif; ?>

<?php /* Filter bar that searches and filters the template grid. */ ?>
<div class="filter-bar" data-filter-table="#studentTplGrid" style="margin-bottom:1rem;">
<?php /*     Search box wrapper. */ ?>
    <div class="filter-search">
<?php /*         Search icon inside the box. */ ?>
        <i class="fas fa-search"></i>
<?php /*         Search input, prefilled from the q parameter in the URL. */ ?>
        <input type="search" data-filter-q placeholder="Search templates, forms, stages..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
<?php /*     Closes the search box wrapper. */ ?>
    </div>
<?php /*     Clear button that resets the filters. */ ?>
    <button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button>
<?php /*     Shows when Google Drive was last checked. */ ?>
    <span class="tpl-sync-status"><i class="fas fa-cloud"></i> Google Drive checked <?= htmlspecialchars(templateTimeAgo($lastSyncAt)) ?></span>
<?php /*     Dropdown that filters templates by stage. */ ?>
    <select data-filter="stage">
<?php /*         Option that shows every stage. */ ?>
        <option value="">All stages</option>
<?php /*         Loops over the stage names collected earlier. */ ?>
        <?php foreach ($libraryStages as $stageLabel => $stageTitle): ?>
<?php /*         One stage option, selected when it matches the URL. */ ?>
        <option value="<?= htmlspecialchars($stageLabel) ?>" <?= (($_GET['stage'] ?? '') === $stageLabel) ? 'selected' : '' ?>><?= htmlspecialchars($stageTitle) ?></option>
<?php /*         Ends the stage option loop. */ ?>
        <?php endforeach; ?>
<?php /*         Extra option for reference documents. */ ?>
        <option value="Reference Documents" <?= (($_GET['stage'] ?? '') === 'Reference Documents') ? 'selected' : '' ?>>Reference Documents</option>
<?php /*     Closes the stage dropdown. */ ?>
    </select>
<?php /*     Dropdown that filters templates by document type. */ ?>
    <select data-filter="type">
<?php /*         Option that shows every type. */ ?>
        <option value="">All types</option>
<?php /*         Option for Form documents, selected when in the URL. */ ?>
        <option value="Form" <?= (($_GET['type'] ?? '') === 'Form') ? 'selected' : '' ?>>Form</option>
<?php /*         Option for Template documents, selected when in the URL. */ ?>
        <option value="Template" <?= (($_GET['type'] ?? '') === 'Template') ? 'selected' : '' ?>>Template</option>
<?php /*         Option for Reference documents, selected when in the URL. */ ?>
        <option value="Reference" <?= (($_GET['type'] ?? '') === 'Reference') ? 'selected' : '' ?>>Reference</option>
<?php /*     Closes the type dropdown. */ ?>
    </select>
<?php /*     Place where the number of matching files is written. */ ?>
    <span class="filter-count" data-filter-count></span>
<?php /* Closes the filter bar. */ ?>
</div>

<?php /* Grid container that holds all template cards. */ ?>
<div id="studentTplGrid">
<?php /* Includes the shared library that draws the template cards. */ ?>
<?php require_once __DIR__ . '/../includes/template_library.php'; ?>
<?php /* Closes the template grid container. */ ?>
</div>

<?php /* Empty-state card shown when no template matches the filters. */ ?>
<div class="card empty-state" data-filter-empty hidden style="text-align:center;padding:3rem 1.5rem;color:var(--gray-400);">
<?php /*     Folder icon for the empty state. */ ?>
    <i class="fas fa-folder-open" style="font-size:2.5rem;margin-bottom:1rem;color:var(--gray-300);"></i>
<?php /*     Heading saying No templates found. */ ?>
    <h4 style="font-size:1.1rem;color:var(--gray-600);margin-bottom:0.35rem;">No templates found</h4>
<?php /*     Explains that no file matches the search or filters. */ ?>
    <p style="font-size:0.875rem;">No forms or templates match your search or filter criteria.</p>
<?php /* Closes the empty-state card. */ ?>
</div>

<?php /* Card that explains how to use the templates. */ ?>
<div class="card">
<?php /*     Header with the title How to Use Templates. */ ?>
    <div class="card-header"><h3>How to Use Templates</h3></div>
<?php /*     Body of the how-to card. */ ?>
    <div class="card-body">
<?php /*         Wrapper that holds the numbered steps. */ ?>
        <div class="process-steps">
<?php /*             First step block. */ ?>
            <div class="process-step">
<?php /*                 Number one in the step circle. */ ?>
                <div class="step-number">1</div>
<?php /*                 Content of the first step. */ ?>
                <div class="step-content">
<?php /*                     Step title: Download the Correct Template. */ ?>
                    <h4>Download the Correct Template</h4>
<?php /*                     Tells the student to pick the template for their stage. */ ?>
                    <p>Choose the template matching your current presentation stage.</p>
<?php /*                 Closes the first step content. */ ?>
                </div>
<?php /*             Closes the first step block. */ ?>
            </div>
<?php /*             Second step block. */ ?>
            <div class="process-step">
<?php /*                 Number two in the step circle. */ ?>
                <div class="step-number">2</div>
<?php /*                 Content of the second step. */ ?>
                <div class="step-content">
<?php /*                     Step title: Prepare Your Document. */ ?>
                    <h4>Prepare Your Document</h4>
<?php /*                     Tells the student to follow the template format exactly. */ ?>
                    <p>Follow the template format exactly when writing your paper.</p>
<?php /*                 Closes the second step content. */ ?>
                </div>
<?php /*             Closes the second step block. */ ?>
            </div>
<?php /*             Third step block. */ ?>
            <div class="process-step">
<?php /*                 Number three in the step circle. */ ?>
                <div class="step-number">3</div>
<?php /*                 Content of the third step. */ ?>
                <div class="step-content">
<?php /*                     Step title: Get Adviser Endorsement. */ ?>
                    <h4>Get Adviser Endorsement</h4>
<?php /*                     Explains how to get the adviser to sign the endorsement form. */ ?>
                    <p>Download the adviser endorsement form, have it signed by your adviser, then upload the scanned copy.</p>
<?php /*                 Closes the third step content. */ ?>
                </div>
<?php /*             Closes the third step block. */ ?>
            </div>
<?php /*             Fourth step block. */ ?>
            <div class="process-step">
<?php /*                 Number four in the step circle. */ ?>
                <div class="step-number">4</div>
<?php /*                 Content of the fourth step. */ ?>
                <div class="step-content">
<?php /*                     Step title: Complete the Graduate School Endorsement. */ ?>
                    <h4>Complete the Graduate School Endorsement</h4>
<?php /*                     Tells the student to upload the endorsement form for review. */ ?>
                    <p>Download and complete the endorsement form for your stage, then upload it on the <a href="<?= url('student/application.php') ?>">Application</a> page for review.</p>
<?php /*                 Closes the fourth step content. */ ?>
                </div>
<?php /*             Closes the fourth step block. */ ?>
            </div>
<?php /*             Fifth step block. */ ?>
            <div class="process-step">
<?php /*                 Number five in the step circle. */ ?>
                <div class="step-number">5</div>
<?php /*                 Content of the fifth step. */ ?>
                <div class="step-content">
<?php /*                     Step title: Upload and Submit. */ ?>
                    <h4>Upload &amp; Submit</h4>
<?php /*                     Tells the student to upload documents and submit online. */ ?>
                    <p>Upload your completed documents on the <a href="<?= url('student/application.php') ?>">Application</a> page and submit your application.</p>
<?php /*                 Closes the fifth step content. */ ?>
                </div>
<?php /*             Closes the fifth step block. */ ?>
            </div>
<?php /*         Closes the numbered steps wrapper. */ ?>
        </div>
<?php /*     Closes the how-to card body. */ ?>
    </div>
<?php /* Closes the how-to card. */ ?>
</div>

<?php /* Includes the shared footer that ends the page. */ ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
