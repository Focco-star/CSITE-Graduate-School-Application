<?php
require_once __DIR__ . '/../../includes/config.php';

$pageTitle   = 'Edit Template';
$role        = 'coordinator';
$currentPage = 'templates';
$userName    = $mockCoordinator['name'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Edit Template / Form</h2>
    <p>Update template details or replace the template file.</p>
</div>

<form class="card" data-validate>
    <div class="card-body">
        <div class="form-field">
            <label>Template / Form Name <span class="required">*</span></label>
            <input type="text" required value="Proposal Template (MSCS)">
        </div>
        <div class="form-row">
            <div class="form-field">
                <label>Program <span class="required">*</span></label>
                <select required>
                    <?php foreach (PROGRAMS as $code => $name): ?>
                    <option value="<?= $code ?>" <?= $code === 'MSCS' ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label>Presentation / Document Type <span class="required">*</span></label>
                <select required>
                    <option>Concept Paper</option>
                    <option selected>Proposal</option>
                    <option>Final Presentation</option>
                    <option>Endorsement</option>
                </select>
            </div>
        </div>
        <div class="form-field">
            <label>Current File</label>
            <p style="font-size:0.875rem;color:var(--gray-600);"><i class="fas fa-file-word"></i> proposal_template_mscs.docx <span style="color:var(--gray-400);">(uploaded Jun 15, 2024)</span></p>
        </div>
        <div class="form-field">
            <label>Replace Template File</label>
            <div class="file-upload-area">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Click or drag to upload a new file (optional)</p>
                <div class="file-name"></div>
                <input type="file" accept=".pdf,.docx,.doc">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            <a href="<?= url('coordinator/templates/manage.php') ?>" class="btn btn-outline">Cancel</a>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
