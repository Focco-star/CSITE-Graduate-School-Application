<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

$pageTitle   = 'Edit Template';
$role        = 'coordinator';
$currentPage = 'templates';
$userName    = $mockCoordinator['name'];
$template = findTemplateRecord((string) ($_GET['id'] ?? ''));
$templateError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $template) {
    try {
        updateTemplateRecord((string) $template['template_id'], [
            'template_name' => $_POST['template_name'] ?? '',
            'document_type' => $_POST['document_type'] ?? 'template',
            'description' => $_POST['description'] ?? '',
            'stage_label' => $template['stage_label'] ?? '',
        ], $_FILES['template_file'] ?? []);
        redirectTo('coordinator/templates/manage.php');
    } catch (Throwable $e) {
        $templateError = $e->getMessage();
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Edit Template / Form</h2>
    <p>Update template details or replace the template file.</p>
</div>

<?php if ($templateError): ?><div class="alert alert-danger"><?= htmlspecialchars($templateError) ?></div><?php endif; ?>

<form class="card" method="post" enctype="multipart/form-data" data-validate>
    <div class="card-body">
        <div class="form-field">
            <label>Template / Form Name <span class="required">*</span></label>
            <input type="text" name="template_name" required value="<?= htmlspecialchars($template['template_name'] ?? 'Proposal Template (MSCS)') ?>">
        </div>
        <div class="form-row">
            <div class="form-field">
                <label>Program <span class="required">*</span></label>
                <select name="document_type" required>
                    <?php foreach (PROGRAMS as $code => $name): ?>
                    <option value="<?= $code ?>" <?= $code === 'MSCS' ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label>Presentation / Document Type <span class="required">*</span></label>
                <select required>
                    <option value="form" <?= ($template['document_type'] ?? '') === 'form' ? 'selected' : '' ?>>Form</option>
                    <option value="template" <?= ($template['document_type'] ?? 'template') === 'template' ? 'selected' : '' ?>>Template</option>
                    <option value="reference" <?= ($template['document_type'] ?? '') === 'reference' ? 'selected' : '' ?>>Reference</option>
                </select>
            </div>
        </div>
        <div class="form-field">
            <label>Current File</label>
            <p style="font-size:0.875rem;color:var(--gray-600);"><i class="fas fa-file-word"></i> <?= htmlspecialchars($template['file_name'] ?? 'proposal_template_mscs.docx') ?></p>
        </div>
        <div class="form-field">
            <label>Replace Template File</label>
            <div class="file-upload-area">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Click or drag to upload a new file (optional)</p>
                <div class="file-name"></div>
                <input type="file" name="template_file" accept=".pdf,.docx,.doc">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            <a href="<?= url('coordinator/templates/manage.php') ?>" class="btn btn-outline">Cancel</a>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
