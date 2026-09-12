<?php
require_once __DIR__ . '/../../includes/config.php';

$pageTitle   = 'Add Template';
$role        = 'coordinator';
$currentPage = 'templates';
$userName    = $mockCoordinator['name'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Add Template / Form</h2>
    <p>Upload a new template for students to download.</p>
</div>

<form class="card" data-validate>
    <div class="card-body">
        <div class="form-field">
            <label>Template / Form Name <span class="required">*</span></label>
            <input type="text" required placeholder="e.g., Proposal Template (MSCS)">
        </div>
        <div class="form-row">
            <div class="form-field">
                <label>Program <span class="required">*</span></label>
                <select required>
                    <option value="">Select program</option>
                    <option>All Programs</option>
                    <?php foreach (PROGRAMS as $code => $name): ?>
                    <option value="<?= $code ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label>Presentation / Document Type <span class="required">*</span></label>
                <select required>
                    <option value="">Select type</option>
                    <option>Concept Paper</option>
                    <option>Proposal</option>
                    <option>Final Presentation</option>
                    <option>Endorsement</option>
                    <option>Recommendations</option>
                    <option>Other</option>
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
                <input type="file" accept=".pdf,.docx,.doc" required>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Template</button>
            <a href="<?= url('coordinator/templates/manage.php') ?>" class="btn btn-outline">Cancel</a>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
