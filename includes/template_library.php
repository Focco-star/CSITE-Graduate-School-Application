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

$templateLibrary        = $templateLibrary ?? [];
$templateLibraryMode    = $templateLibraryMode ?? 'student';
$templateLibraryCourse  = $templateLibraryCourse ?? [];
$templateLibraryIsCoordinator = ($templateLibraryMode === 'coordinator');

/** The icon of a template tile. */
$templateLibraryIcon = static function (array $template): string {
    $type = (string) ($template['typeLabel'] ?? 'Template');
    if ($type === 'Form') {
        return 'fa-file-signature';
    }
    if ($type === 'Reference') {
        return 'fa-book-open';
    }
    return 'fa-file-word';
};
?>

<?php foreach ($templateLibrary as $track): ?>
<section class="tpl-track-section" data-track="<?= htmlspecialchars($track['trackCode']) ?>">

    <div class="tpl-track-head">
        <h3 class="tpl-track-title">
            <i class="fas fa-layer-group"></i>
            <?= htmlspecialchars($track['trackName']) ?>
        </h3>
        <?php if (!empty($track['courses'])): ?>
        <div class="tpl-track-courses">
            <span class="tpl-track-courses-label">
                <?= $templateLibraryIsCoordinator ? 'Courses using this track' : 'Your program' ?>
            </span>
            <?php foreach ($track['courses'] as $course): ?>
                <?php
                $isOwn = ($templateLibraryCourse['code'] ?? '') === $course['code'];
                if (!$templateLibraryIsCoordinator && !$isOwn) {
                    continue;
                }
                ?>
                <span class="tpl-course-chip<?= $isOwn ? ' is-own' : '' ?>">
                    <strong><?= htmlspecialchars($course['code']) ?></strong>
                    <span><?= htmlspecialchars($course['name']) ?></span>
                </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if ($templateLibraryIsCoordinator && !empty($track['needsReview'])): ?>
        <div class="tpl-review-note">
            <i class="fas fa-flag"></i>
            <span>
                <?php $reviewLabels = []; foreach ($track['needsReview'] as $reviewItem) {
                    $reviewLabels[] = htmlspecialchars((string) ($reviewItem['label'] ?? ''))
                        . (($reviewItem['reason'] ?? '') === 'missing' ? ' (file not uploaded yet)' : ' (stage unknown)');
                } ?>
                <?= implode(', ', $reviewLabels) ?>
            </span>
        </div>
        <?php endif; ?>
    </div>

    <?php foreach ($track['stages'] as $stage): ?>
        <?php if (!$stage['templates']) { continue; } ?>
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-flag"></i> <?= htmlspecialchars($stage['label']) ?></h3>
            <span class="tpl-stage-count"><?= count($stage['templates']) ?> document<?= count($stage['templates']) === 1 ? '' : 's' ?></span>
        </div>
        <div class="card-body">
            <div class="template-grid">
                <?php foreach ($stage['templates'] as $template):
                    $search = strtolower(trim(
                        ($template['label'] ?? '') . ' ' . ($template['file'] ?? '') . ' '
                        . ($template['description'] ?? '') . ' ' . $stage['label'] . ' '
                        . ($template['typeLabel'] ?? '') . ' ' . $track['trackName']
                    ));
                ?>
                <div class="template-item<?= !empty($template['needsReview']) ? ' needs-review' : '' ?>"
                     data-stage="<?= htmlspecialchars($stage['label']) ?>"
                     data-type="<?= htmlspecialchars($template['typeLabel'] ?? 'Template') ?>"
                     data-track="<?= htmlspecialchars($track['trackCode']) ?>"
                     data-search="<?= htmlspecialchars($search) ?>">
                    <div class="template-icon"><i class="fas <?= $templateLibraryIcon($template) ?>"></i></div>
                    <h4><?= htmlspecialchars($template['label']) ?></h4>
                    <p><?= htmlspecialchars($template['description'] ?? '') ?></p>
                    <span class="template-meta"><i class="fas fa-tag"></i> <?= htmlspecialchars($template['typeLabel'] ?? 'Template') ?></span>
                    <?php if (!empty($template['modifiedLabel'])): ?>
                    <span class="template-meta"><i class="fas fa-clock-rotate-left"></i> Updated <?= htmlspecialchars($template['modifiedLabel']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($template['needsReview'])): ?>
                    <span class="tpl-review-chip"><i class="fas fa-flag"></i> Needs review</span>
                    <?php endif; ?>
                    <div class="template-actions">
                        <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($template['url']) ?>" download>
                            <i class="fas fa-download"></i> Download
                        </a>
                        <?php if ($templateLibraryIsCoordinator): ?>
                        <a class="btn btn-sm btn-outline" href="<?= url('coordinator/templates/edit.php?id=' . urlencode((string) ($template['id'] ?? ''))) ?>" title="Edit template">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (!empty($track['references'])): ?>
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-book-open"></i> Reference Documents</h3>
            <span class="tpl-stage-count"><?= count($track['references']) ?> document<?= count($track['references']) === 1 ? '' : 's' ?></span>
        </div>
        <div class="card-body">
            <div class="template-grid">
                <?php foreach ($track['references'] as $template):
                    $search = strtolower(trim(
                        ($template['label'] ?? '') . ' ' . ($template['file'] ?? '') . ' '
                        . ($template['description'] ?? '') . ' reference documents '
                        . ($template['typeLabel'] ?? '') . ' ' . $track['trackName']
                    ));
                ?>
                <div class="template-item<?= !empty($template['needsReview']) ? ' needs-review' : '' ?>"
                     data-stage="Reference Documents"
                     data-type="<?= htmlspecialchars($template['typeLabel'] ?? 'Reference') ?>"
                     data-track="<?= htmlspecialchars($track['trackCode']) ?>"
                     data-search="<?= htmlspecialchars($search) ?>">
                    <div class="template-icon"><i class="fas <?= $templateLibraryIcon($template) ?>"></i></div>
                    <h4><?= htmlspecialchars($template['label']) ?></h4>
                    <p><?= htmlspecialchars($template['description'] ?? '') ?></p>
                    <span class="template-meta"><i class="fas fa-tag"></i> <?= htmlspecialchars($template['typeLabel'] ?? 'Reference') ?></span>
                    <?php if (!empty($template['modifiedLabel'])): ?>
                    <span class="template-meta"><i class="fas fa-clock-rotate-left"></i> Updated <?= htmlspecialchars($template['modifiedLabel']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($template['needsReview'])): ?>
                    <span class="tpl-review-chip"><i class="fas fa-flag"></i> Needs review</span>
                    <?php endif; ?>
                    <div class="template-actions">
                        <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($template['url']) ?>" download>
                            <i class="fas fa-download"></i> Download
                        </a>
                        <?php if ($templateLibraryIsCoordinator): ?>
                        <a class="btn btn-sm btn-outline" href="<?= url('coordinator/templates/edit.php?id=' . urlencode((string) ($template['id'] ?? ''))) ?>" title="Edit template">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

</section>
<?php endforeach; ?>
