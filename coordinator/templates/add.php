<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

$pageTitle   = 'Add Template';
$role        = 'coordinator';
$currentPage = 'templates';
$userName    = $mockCoordinator['name'];

$templateError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        createTemplateRecord([
            'template_name' => $_POST['template_name'] ?? '',
            'program_track' => $_POST['program_track'] ?? '',
            'stage_label' => $_POST['stage_label'] ?? '',
            'document_type' => $_POST['document_type'] ?? 'template',
            'description' => $_POST['description'] ?? '',
        ], $_FILES['template_file'] ?? []);
        redirectTo('coordinator/templates/manage.php');
    } catch (Throwable $e) {
        $templateError = $e->getMessage();
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Add Template / Form</h2>
    <p>Upload a new template for students to download.</p>
</div>

<?php if ($templateError): ?><div class="alert alert-danger"><?= htmlspecialchars($templateError) ?></div><?php endif; ?>

<form class="card" method="post" enctype="multipart/form-data" data-validate>
    <div class="card-body">
        <div class="form-field">
            <label>Template / Form Name <span class="required">*</span></label>
            <input type="text" name="template_name" required placeholder="e.g., Proposal Template (MSCS)">
        </div>
        <div class="form-row">
            <div class="form-field">
                <label>Program <span class="required">*</span></label>
                <select name="program_track" required>
                    <option value="">Select track</option>
                    <option value="thesis">Thesis</option>
                    <option value="capstone">Capstone</option>
                    <option value="seminar">Seminar Paper</option>
                </select>
            </div>
            <div class="form-field">
                <label>Presentation / Document Type <span class="required">*</span></label>
                <select name="stage_label" required>
                    <option value="">Select stage</option>
                    <?php foreach (['Concept Paper', 'Thesis Proposal', 'Final Thesis', 'Capstone Proposal', 'Final Capstone', 'Seminar Paper Proposal', 'Final Seminar Paper'] as $stage): ?>
                    <option value="<?= htmlspecialchars($stage) ?>"><?= htmlspecialchars($stage) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-field">
            <label>Template File <span class="required">*</span></label>
            <div class="file-upload-area">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Click or drag to upload template file</p>
                <p style="font-size:0.75rem;">PDF, DOCX (Max 10MB)</p>
                <div class="file-name"></div>
                <input type="file" name="template_file" accept=".pdf,.docx,.doc" required>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Template</button>
            <a href="<?= url('coordinator/templates/manage.php') ?>" class="btn btn-outline">Cancel</a>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
