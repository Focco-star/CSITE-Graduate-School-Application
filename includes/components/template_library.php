<?php /* <?php /* Shared renderer of the template library, used by the coordinator and student pages. * / */ ?>
<?php
/**
 * Shared renderer of the template library.
 * ------------------------------------------------------------------
 * Both portals show the same records in the same way: the templates are
 * grouped by track, every track shows the courses that follow it, the
 * templates of every workflow stage, and the reference documents of the
 * track.
 *
 * The coordinator page adds the management actions (edit, and the flag for
 * a template that still needs to be filed); the student page shows only the
 * track the student belongs to.
 *
 * Expects before the include:
 *   $templateLibrary        the grouped library from templateLibrary()
 *   $templateLibraryMode    'coordinator' or 'student'
 *   $templateLibraryCourse  the course of the signed in student (student mode)
 */

// // Takes the grouped template library passed by the page, or an empty array.
$templateLibrary        = $templateLibrary ?? [];
// // Reads the display mode, defaulting to the student view.
$templateLibraryMode    = $templateLibraryMode ?? 'student';
// // Reads the signed-in student's course, defaulting to an empty array.
$templateLibraryCourse  = $templateLibraryCourse ?? [];
// // True when the page is the coordinator view, which adds edit actions.
$templateLibraryIsCoordinator = ($templateLibraryMode === 'coordinator');

/** The course codes and names of a track, for the search of its templates. */
// // Declares a helper that joins a track's course codes and names for searching.
$templateCourseSearch = static function (array $track): string {
    // // Starts the list of searchable course text.
    $parts = [];
    // // Loops over the courses that follow this track.
    foreach (($track['courses'] ?? []) as $course) {
        // // Adds the course code to the search text.
        $parts[] = (string) ($course['code'] ?? '');
        // // Adds the course name to the search text.
        $parts[] = (string) ($course['name'] ?? '');
    // // Closes the course loop.
    }
    // // Joins the non-empty pieces with spaces for the search string.
    return implode(' ', array_filter($parts));
// // Closes the course search helper.
};

/** The icon of a template tile. */
// // Declares a helper that chooses the icon for a template tile.
$templateLibraryIcon = static function (array $template): string {
    // // Reads the template type label, defaulting to Template.
    $type = (string) ($template['typeLabel'] ?? 'Template');
    // // Checks whether the template is a Form.
    if ($type === 'Form') {
        // // Returns the signature icon for forms.
        return 'fa-file-signature';
    // // Closes the Form check.
    }
    // // Checks whether the template is a Reference document.
    if ($type === 'Reference') {
        // // Returns the open book icon for references.
        return 'fa-book-open';
    // // Closes the Reference check.
    }
    // // Returns the Word document icon for every other template type.
    return 'fa-file-word';
// // Closes the icon helper.
};
// // Ends PHP so the template library markup below can be printed.
?>

<?php /* <?php /* Loops over each track in the template library. * / ?> */ ?>
<?php foreach ($templateLibrary as $track): ?>
<?php /* <!-- One section per track, tagged with the track code for filtering. --> */ ?>
<section class="tpl-track-section" data-track="<?= htmlspecialchars($track['trackCode']) ?>">

<?php /*     <!-- Head of the track section: title, courses and review note. --> */ ?>
    <div class="tpl-track-head">
<?php /*         <!-- Track title line. --> */ ?>
        <h3 class="tpl-track-title">
<?php /*             <!-- Layer icon shown before the track name. --> */ ?>
            <i class="fas fa-layer-group"></i>
<?php /*             <!-- Prints the track name, escaped for safe HTML. --> */ ?>
            <?= htmlspecialchars($track['trackName']) ?>
<?php /*         <!-- Closes the track title heading. --> */ ?>
        </h3>
<?php /*         <?php /* Shows the course list only when this track has courses. * / ?> */ ?>
        <?php if (!empty($track['courses'])): ?>
<?php /*         <!-- Row of course chips for this track. --> */ ?>
        <div class="tpl-track-courses">
<?php /*             <!-- Label above the course chips. --> */ ?>
            <span class="tpl-track-courses-label">
<?php /*                 <!-- Coordinators see 'Courses using this track', students see 'Your program'. --> */ ?>
                <?= $templateLibraryIsCoordinator ? 'Courses using this track' : 'Your program' ?>
<?php /*             <!-- Closes the course list label. --> */ ?>
            </span>
<?php /*             <?php /* Loops over the courses that follow this track. * / ?> */ ?>
            <?php foreach ($track['courses'] as $course): ?>
<?php /*                 <?php /* Starts a small PHP block that works out the student's own course. * / */ ?>
                <?php
                // // True when this course matches the signed-in student's course code.
                $isOwn = ($templateLibraryCourse['code'] ?? '') === $course['code'];
                // // In student mode, skips courses that are not the student's own.
                if (!$templateLibraryIsCoordinator && !$isOwn) {
                    // // Jumps to the next course in the loop.
                    continue;
                // // Closes the student-mode course check.
                }
                // // Ends the PHP block and returns to HTML output.
                ?>
<?php /*                 <!-- Course chip, marked as is-own when it is the student's course. --> */ ?>
                <span class="tpl-course-chip<?= $isOwn ? ' is-own' : '' ?>" data-course="<?= htmlspecialchars((string) ($course['code'] ?? '')) ?>">
<?php /*                     <!-- Course code in bold. --> */ ?>
                    <strong><?= htmlspecialchars($course['code']) ?></strong>
<?php /*                     <!-- Course name under the code. --> */ ?>
                    <span><?= htmlspecialchars($course['name']) ?></span>
<?php /*                 <!-- Closes the course chip. --> */ ?>
                </span>
<?php /*             <?php /* Ends the course loop. * / ?> */ ?>
            <?php endforeach; ?>
<?php /*         <!-- Closes the course chip row. --> */ ?>
        </div>
<?php /*         <?php /* Ends the course list check. * / ?> */ ?>
        <?php endif; ?>
<?php /*         <?php /* Shows the review note only for coordinators when templates need review. * / ?> */ ?>
        <?php if ($templateLibraryIsCoordinator && !empty($track['needsReview'])): ?>
<?php /*         <!-- Note listing templates that still need to be filed. --> */ ?>
        <div class="tpl-review-note">
<?php /*             <!-- Flag icon for the review note. --> */ ?>
            <i class="fas fa-flag"></i>
<?php /*             <!-- Opens the text wrapper for the review labels. --> */ ?>
            <span>
<?php /*                 <?php /* Builds a label for each template that needs review. * / */ ?>
                <?php $reviewLabels = []; foreach ($track['needsReview'] as $reviewItem) {
                    // // Adds the template label, escaped, to the review label list.
                    $reviewLabels[] = htmlspecialchars((string) ($reviewItem['label'] ?? ''))
                        // // Adds why it needs review: file missing or stage unknown.
                        . (($reviewItem['reason'] ?? '') === 'missing' ? ' (file not uploaded yet)' : ' (stage unknown)');
                // // Closes the review item loop and the PHP block.
                } ?>
<?php /*                 <!-- Prints the review labels joined with commas. --> */ ?>
                <?= implode(', ', $reviewLabels) ?>
<?php /*             <!-- Closes the review text wrapper. --> */ ?>
            </span>
<?php /*         <!-- Closes the review note. --> */ ?>
        </div>
<?php /*         <?php /* Ends the review note check. * / ?> */ ?>
        <?php endif; ?>
<?php /*     <!-- Closes the track section head. --> */ ?>
    </div>

<?php /*     <?php /* Loops over every workflow stage of this track. * / ?> */ ?>
    <?php foreach ($track['stages'] as $stage): ?>
<?php /*         <?php /* Skips stages that have no templates to show. * / ?> */ ?>
        <?php if (!$stage['templates']) { continue; } ?>
<?php /*     <!-- Card that holds one stage's templates. --> */ ?>
    <div class="card">
<?php /*         <!-- Card header with the stage name and document count. --> */ ?>
        <div class="card-header">
<?php /*             <!-- Stage name with a flag icon, escaped for safety. --> */ ?>
            <h3><i class="fas fa-flag"></i> <?= htmlspecialchars($stage['label']) ?></h3>
<?php /*             <!-- Number of documents in this stage, with correct singular or plural. --> */ ?>
            <span class="tpl-stage-count"><?= count($stage['templates']) ?> document<?= count($stage['templates']) === 1 ? '' : 's' ?></span>
<?php /*         <!-- Closes the card header. --> */ ?>
        </div>
<?php /*         <!-- Card body holding the template grid. --> */ ?>
        <div class="card-body">
<?php /*             <!-- Grid of template tiles for this stage. --> */ ?>
            <div class="template-grid">
<?php /*                 <?php /* Starts the template loop and builds its search text. * / */ ?>
                <?php foreach ($stage['templates'] as $template):
                    // // Builds a lowercase search string from the template's label and file.
                    $search = strtolower(trim(
                        // // Adds the file name to the search text.
                        ($template['label'] ?? '') . ' ' . ($template['file'] ?? '') . ' '
                        // // Adds the description and the stage label.
                        . ($template['description'] ?? '') . ' ' . $stage['label'] . ' '
                        // // Adds the type label and the track name.
                        . ($template['typeLabel'] ?? '') . ' ' . $track['trackName'] . ' '
                        // // Adds the track's course codes and names.
                        . $templateCourseSearch($track)
                    // // Closes the search string and trims it.
                    ));
                // // Ends the PHP block and returns to HTML output.
                ?>
<?php /*                 <!-- One template tile, marked needs-review when the file is missing. --> */ ?>
                <div class="template-item<?= !empty($template['needsReview']) ? ' needs-review' : '' ?>"
<?php /*                      <!-- Data attribute storing the stage label for filtering. --> */ ?>
                     data-stage="<?= htmlspecialchars($stage['label']) ?>"
<?php /*                      <!-- Data attribute storing the template type label. --> */ ?>
                     data-type="<?= htmlspecialchars($template['typeLabel'] ?? 'Template') ?>"
<?php /*                      <!-- Data attribute storing the track code. --> */ ?>
                     data-track="<?= htmlspecialchars($track['trackCode']) ?>"
<?php /*                      <!-- Data attribute holding the lowercase search text. --> */ ?>
                     data-search="<?= htmlspecialchars($search) ?>">
<?php /*                     <!-- Icon box using the type-based icon helper. --> */ ?>
                    <div class="template-icon"><i class="fas <?= $templateLibraryIcon($template) ?>"></i></div>
<?php /*                     <!-- Template title, escaped for safety. --> */ ?>
                    <h4><?= htmlspecialchars($template['label']) ?></h4>
<?php /*                     <!-- Template description, empty when none was given. --> */ ?>
                    <p><?= htmlspecialchars($template['description'] ?? '') ?></p>
<?php /*                     <!-- Small meta line showing the template type with a tag icon. --> */ ?>
                    <span class="template-meta"><i class="fas fa-tag"></i> <?= htmlspecialchars($template['typeLabel'] ?? 'Template') ?></span>
<?php /*                     <?php /* Shows the updated time only when one is available. * / ?> */ ?>
                    <?php if (!empty($template['modifiedLabel'])): ?>
<?php /*                     <!-- Meta line with the last updated label and a clock icon. --> */ ?>
                    <span class="template-meta"><i class="fas fa-clock-rotate-left"></i> Updated <?= htmlspecialchars($template['modifiedLabel']) ?></span>
<?php /*                     <?php /* Ends the updated time check. * / ?> */ ?>
                    <?php endif; ?>
<?php /*                     <?php /* Shows the review chip when the template still needs review. * / ?> */ ?>
                    <?php if (!empty($template['needsReview'])): ?>
<?php /*                     <!-- Chip telling the coordinator this template needs review. --> */ ?>
                    <span class="tpl-review-chip"><i class="fas fa-flag"></i> Needs review</span>
<?php /*                     <?php /* Ends the review chip check. * / ?> */ ?>
                    <?php endif; ?>
<?php /*                     <!-- Row of actions for this template tile. --> */ ?>
                    <div class="template-actions">
<?php /*                         <!-- Download button linking to the template file. --> */ ?>
                        <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($template['url']) ?>" download>
<?php /*                             <!-- Download icon and text inside the button. --> */ ?>
                            <i class="fas fa-download"></i> Download
<?php /*                         <!-- Closes the download button. --> */ ?>
                        </a>
<?php /*                         <?php /* Shows the edit link only in the coordinator view. * / ?> */ ?>
                        <?php if ($templateLibraryIsCoordinator): ?>
<?php /*                         <!-- Edit button linking to the template edit page with its id. --> */ ?>
                        <a class="btn btn-sm btn-outline" href="<?= url('coordinator/templates/edit.php?id=' . urlencode((string) ($template['id'] ?? ''))) ?>" title="Edit template">
<?php /*                             <!-- Pencil icon inside the edit button. --> */ ?>
                            <i class="fas fa-edit"></i>
<?php /*                         <!-- Closes the edit button. --> */ ?>
                        </a>
<?php /*                         <?php /* Ends the coordinator-only edit check. * / ?> */ ?>
                        <?php endif; ?>
<?php /*                     <!-- Closes the actions row. --> */ ?>
                    </div>
<?php /*                 <!-- Closes the template tile. --> */ ?>
                </div>
<?php /*                 <?php /* Ends the stage template loop. * / ?> */ ?>
                <?php endforeach; ?>
<?php /*             <!-- Closes the template grid. --> */ ?>
            </div>
<?php /*         <!-- Closes the card body. --> */ ?>
        </div>
<?php /*     <!-- Closes the stage card. --> */ ?>
    </div>
<?php /*     <?php /* Ends the stage loop. * / ?> */ ?>
    <?php endforeach; ?>

<?php /*     <?php /* Shows reference documents only when the track has some. * / ?> */ ?>
    <?php if (!empty($track['references'])): ?>
<?php /*     <!-- Card that holds the reference documents of this track. --> */ ?>
    <div class="card">
<?php /*         <!-- Card header with the reference heading and count. --> */ ?>
        <div class="card-header">
<?php /*             <!-- Reference Documents heading with an open book icon. --> */ ?>
            <h3><i class="fas fa-book-open"></i> Reference Documents</h3>
<?php /*             <!-- Number of reference documents, with correct singular or plural. --> */ ?>
            <span class="tpl-stage-count"><?= count($track['references']) ?> document<?= count($track['references']) === 1 ? '' : 's' ?></span>
<?php /*         <!-- Closes the card header. --> */ ?>
        </div>
<?php /*         <!-- Card body holding the reference grid. --> */ ?>
        <div class="card-body">
<?php /*             <!-- Grid of reference document tiles. --> */ ?>
            <div class="template-grid">
<?php /*                 <?php /* Starts the reference loop and builds its search text. * / */ ?>
                <?php foreach ($track['references'] as $template):
                    // // Builds a lowercase search string from the reference's label and file.
                    $search = strtolower(trim(
                        // // Adds the file name to the search text.
                        ($template['label'] ?? '') . ' ' . ($template['file'] ?? '') . ' '
                        // // Adds the description and the words 'reference documents'.
                        . ($template['description'] ?? '') . ' reference documents '
                        // // Adds the type label and the track name.
                        . ($template['typeLabel'] ?? '') . ' ' . $track['trackName'] . ' '
                        // // Adds the track's course codes and names.
                        . $templateCourseSearch($track)
                    // // Closes the search string and trims it.
                    ));
                // // Ends the PHP block and returns to HTML output.
                ?>
<?php /*                 <!-- One reference tile, marked needs-review when the file is missing. --> */ ?>
                <div class="template-item<?= !empty($template['needsReview']) ? ' needs-review' : '' ?>"
<?php /*                      <!-- Fixed stage label for reference documents. --> */ ?>
                     data-stage="Reference Documents"
<?php /*                      <!-- Data attribute with the reference type label. --> */ ?>
                     data-type="<?= htmlspecialchars($template['typeLabel'] ?? 'Reference') ?>"
<?php /*                      <!-- Data attribute storing the track code. --> */ ?>
                     data-track="<?= htmlspecialchars($track['trackCode']) ?>"
<?php /*                      <!-- Data attribute holding the lowercase search text. --> */ ?>
                     data-search="<?= htmlspecialchars($search) ?>">
<?php /*                     <!-- Icon box using the type-based icon helper. --> */ ?>
                    <div class="template-icon"><i class="fas <?= $templateLibraryIcon($template) ?>"></i></div>
<?php /*                     <!-- Reference title, escaped for safety. --> */ ?>
                    <h4><?= htmlspecialchars($template['label']) ?></h4>
<?php /*                     <!-- Reference description, empty when none was given. --> */ ?>
                    <p><?= htmlspecialchars($template['description'] ?? '') ?></p>
<?php /*                     <!-- Small meta line showing the type with a tag icon. --> */ ?>
                    <span class="template-meta"><i class="fas fa-tag"></i> <?= htmlspecialchars($template['typeLabel'] ?? 'Reference') ?></span>
<?php /*                     <?php /* Shows the updated time only when one is available. * / ?> */ ?>
                    <?php if (!empty($template['modifiedLabel'])): ?>
<?php /*                     <!-- Meta line with the last updated label and a clock icon. --> */ ?>
                    <span class="template-meta"><i class="fas fa-clock-rotate-left"></i> Updated <?= htmlspecialchars($template['modifiedLabel']) ?></span>
<?php /*                     <?php /* Ends the updated time check. * / ?> */ ?>
                    <?php endif; ?>
<?php /*                     <?php /* Shows the review chip when the reference still needs review. * / ?> */ ?>
                    <?php if (!empty($template['needsReview'])): ?>
<?php /*                     <!-- Chip telling the coordinator this reference needs review. --> */ ?>
                    <span class="tpl-review-chip"><i class="fas fa-flag"></i> Needs review</span>
<?php /*                     <?php /* Ends the review chip check. * / ?> */ ?>
                    <?php endif; ?>
<?php /*                     <!-- Row of actions for this reference tile. --> */ ?>
                    <div class="template-actions">
<?php /*                         <!-- Download button linking to the reference file. --> */ ?>
                        <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($template['url']) ?>" download>
<?php /*                             <!-- Download icon and text inside the button. --> */ ?>
                            <i class="fas fa-download"></i> Download
<?php /*                         <!-- Closes the download button. --> */ ?>
                        </a>
<?php /*                         <?php /* Shows the edit link only in the coordinator view. * / ?> */ ?>
                        <?php if ($templateLibraryIsCoordinator): ?>
<?php /*                         <!-- Edit button linking to the template edit page with its id. --> */ ?>
                        <a class="btn btn-sm btn-outline" href="<?= url('coordinator/templates/edit.php?id=' . urlencode((string) ($template['id'] ?? ''))) ?>" title="Edit template">
<?php /*                             <!-- Pencil icon inside the edit button. --> */ ?>
                            <i class="fas fa-edit"></i>
<?php /*                         <!-- Closes the edit button. --> */ ?>
                        </a>
<?php /*                         <?php /* Ends the coordinator-only edit check. * / ?> */ ?>
                        <?php endif; ?>
<?php /*                     <!-- Closes the actions row. --> */ ?>
                    </div>
<?php /*                 <!-- Closes the reference tile. --> */ ?>
                </div>
<?php /*                 <?php /* Ends the reference loop. * / ?> */ ?>
                <?php endforeach; ?>
<?php /*             <!-- Closes the reference grid. --> */ ?>
            </div>
<?php /*         <!-- Closes the card body. --> */ ?>
        </div>
<?php /*     <!-- Closes the reference card. --> */ ?>
    </div>
<?php /*     <?php /* Ends the reference documents check. * / ?> */ ?>
    <?php endif; ?>

<?php /* <!-- Closes the track section. --> */ ?>
</section>
<?php /* <?php /* Ends the track loop, finishing the template library output. * / ?> */ ?>
<?php endforeach; ?>
