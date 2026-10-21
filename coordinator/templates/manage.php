<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

$pageTitle   = 'Manage Templates';
$role        = 'coordinator';
$currentPage = 'templates';
$userName    = $mockCoordinator['name'];

$templates = databaseTemplateRows();
$templates = $templates ?: getPaperLibraryRows();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Templates &amp; Forms</h2>
    <p>Manage the forms and templates available for students to download.</p>
</div>

<div class="filter-bar" data-filter-table="#coordinatorTplGrid" style="margin-bottom:1rem;">
    <a href="<?= url('coordinator/templates/add.php') ?>" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i> Add Document
    </a>
    <div class="filter-search">
        <i class="fas fa-search"></i>
        <input type="search" data-filter-q placeholder="Search templates, forms, stages..." style="max-width:520px;">
    </div>
    <button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button>
    <select data-filter="course">
        <option value="">All course types</option>
        <option value="Thesis">Thesis</option>
        <option value="Capstone">Capstone</option>
        <option value="Seminar Paper">Seminar Paper</option>
    </select>
    <select data-filter="program">
        <option value="">All programs</option>
        <?php foreach (array_keys(PROGRAMS) as $program): ?>
        <option value="<?= htmlspecialchars($program) ?>"><?= htmlspecialchars($program) ?></option>
        <?php endforeach; ?>
    </select>
    <select data-filter="stage">
        <option value="">All stages</option>
        <?php
        $stages = array_values(array_unique(array_filter(array_map(
            static fn ($template) => trim((string) ($template['stage'] ?? '')),
            $templates
        ))));
        foreach ($stages as $stage):
        ?>
        <option value="<?= htmlspecialchars($stage) ?>"><?= htmlspecialchars($stage) ?></option>
        <?php endforeach; ?>
    </select>
    <select data-filter="type">
        <option value="">All types</option>
        <option value="Form">Form</option>
        <option value="Template">Template</option>
        <option value="Reference">Reference</option>
    </select>
    <span class="filter-count" data-filter-count></span>
</div>

<div id="coordinatorTplGrid">
    <?php
    $templateGroups = [];
    foreach ($templates as $i => $template) {
        $stage = trim((string) ($template['stage'] ?? '')) ?: 'Reference Documents';
        $courseType = (string) ($template['courseType'] ?? '');
        $groupKey = $courseType . '|' . $stage;
        $templateGroups[$groupKey]['courseType'] = $courseType;
        $templateGroups[$groupKey]['stage'] = $stage;
        $templateGroups[$groupKey]['items'][] = [$i, $template];
    }
    ?>
    <?php foreach ($templateGroups as $group): ?>
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-layer-group"></i> <?= htmlspecialchars($group['stage']) ?><?= $group['courseType'] !== '' ? ' <span style="font-weight:400;color:var(--gray-500);">(' . htmlspecialchars($group['courseType']) . ')</span>' : '' ?></h3>
        </div>
        <div class="card-body">
            <div class="template-grid">
                <?php foreach ($group['items'] as [$i, $template]):
                    $type = ucfirst(strtolower(trim((string) ($template['docType'] ?? ''))));
                    if (!in_array($type, ['Form', 'Template', 'Reference'], true)) {
                        $type = 'Template';
                    }
                    $programs = (string) ($template['programs'] ?? '');
                    $search = strtolower(trim(implode(' ', [
                        $template['label'] ?? '',
                        $template['file'] ?? '',
                        $template['description'] ?? '',
                        $group['courseType'],
                        $programs,
                        $group['stage'],
                        $type,
                    ])));
                    $icon = $type === 'Form' ? 'fa-file-signature' : ($type === 'Reference' ? 'fa-book-open' : 'fa-file-word');
                ?>
                <div class="template-item" data-course="<?= htmlspecialchars($group['courseType']) ?>" data-program="<?= htmlspecialchars($programs) ?>" data-stage="<?= htmlspecialchars($group['stage']) ?>" data-type="<?= htmlspecialchars($type) ?>" data-search="<?= htmlspecialchars($search) ?>">
                    <div class="template-icon"><i class="fas <?= $icon ?>"></i></div>
                    <h4><?= htmlspecialchars($template['label'] ?? $template['file'] ?? 'Untitled document') ?></h4>
                    <p><?= htmlspecialchars($template['description'] ?? '') ?></p>
                    <span class="template-meta"><i class="fas fa-tag"></i> <?= htmlspecialchars($type) ?></span>
                    <span class="template-meta"><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($programs !== '' ? $programs : $group['courseType']) ?></span>
                    <div style="display:flex;gap:0.4rem;flex-wrap:wrap;margin-top:auto;">
                        <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($template['url']) ?>" download>
                            <i class="fas fa-download"></i> Download
                        </a>
                        <a class="btn btn-outline btn-sm" href="<?= url('coordinator/templates/edit.php?id=' . urlencode((string) ($template['id'] ?? ($i + 1)))) ?>">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="card empty-state" data-filter-empty hidden style="text-align:center;padding:3rem 1.5rem;color:var(--gray-400);">
        <i class="fas fa-folder-open" style="font-size:2.5rem;margin-bottom:1rem;color:var(--gray-300);"></i>
        <h4 style="font-size:1.1rem;color:var(--gray-600);margin-bottom:0.35rem;">No templates found</h4>
        <p style="font-size:0.875rem;">No forms or templates match your search or filter criteria.</p>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
