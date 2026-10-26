<?php /* Starts the PHP code for the edit template page. */ ?>
<?php
// Loads config settings, helpers, and demo data arrays.
require_once __DIR__ . '/../../includes/config.php';
// Loads the database helper used when updating templates.
require_once __DIR__ . '/../../includes/db.php';

// Sets the page title used in the browser tab.
$pageTitle   = 'Edit Template';
// Tells the layout the signed-in user is a coordinator.
$role        = 'coordinator';
// Marks the 'templates' sidebar link as active.
$currentPage = 'templates';
// Takes the demo coordinator name for the header greeting.
$userName    = $mockCoordinator['name'];
// Loads the template record whose id is in the URL.
$template = findTemplateRecord((string) ($_GET['id'] ?? ''));
// Starts an empty error message for the form.
$templateError = '';
// Checks the form was posted and the template exists.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $template) {
    // Opens a try block so update errors can be shown.
    try {
        // Starts the call that saves changes to the template.
        updateTemplateRecord((string) $template['template_id'], [
            // Reads the template name typed in the form.
            'template_name' => $_POST['template_name'] ?? '',
            // Reads the document type, defaulting to 'template'.
            'document_type' => $_POST['document_type'] ?? 'template',
            // Reads the optional description text.
            'description' => $_POST['description'] ?? '',
            // Keeps the existing stage label from the loaded record.
            'stage_label' => $template['stage_label'] ?? '',
        // Passes the newly uploaded file, if one was chosen.
        ], $_FILES['template_file'] ?? []);
        // Goes back to the template list after a successful save.
        redirectTo('coordinator/templates/manage.php');
    // Catches any error thrown while updating the template.
    } catch (Throwable $e) {
        // Stores the error text so it can be shown on the page.
        $templateError = $e->getMessage();
    // Closes the update error handler.
    }
// Closes the form submission block.
}

// Loads the shared header and navigation.
require_once __DIR__ . '/../../includes/header.php';
// Closes PHP so the HTML form below is printed.
?>

<?php /* Opens the page header area. */ ?>
<div class="page-header">
<?php /*     Shows the big heading 'Edit Template / Form'. */ ?>
    <h2>Edit Template / Form</h2>
<?php /*     Explains that this page updates details or replaces the file. */ ?>
    <p>Update template details or replace the template file.</p>
<?php /* Closes the page header area. */ ?>
</div>

<?php /* Shows a red alert with the error text when saving failed. */ ?>
<?php if ($templateError): ?><div class="alert alert-danger"><?= htmlspecialchars($templateError) ?></div><?php endif; ?>

<?php /* Starts the card form that uploads files with POST. */ ?>
<form class="card" method="post" enctype="multipart/form-data" data-validate>
<?php /*     Opens the card body that holds the form fields. */ ?>
    <div class="card-body">
<?php /*         Opens the field for the template name. */ ?>
        <div class="form-field">
<?php /*             Label for the template name, with a required star. */ ?>
            <label>Template / Form Name <span class="required">*</span></label>
<?php /*             Text input pre-filled with the current template name. */ ?>
            <input type="text" name="template_name" required value="<?= htmlspecialchars($template['template_name'] ?? 'Proposal Template (MSCS)') ?>">
<?php /*         Closes the template name field. */ ?>
        </div>
<?php /*         Starts a row that puts two fields side by side. */ ?>
        <div class="form-row">
<?php /*             Opens the program field. */ ?>
            <div class="form-field">
<?php /*                 Label for the program, with a required star. */ ?>
                <label>Program <span class="required">*</span></label>
<?php /*                 Dropdown named document_type that posts the chosen program. */ ?>
                <select name="document_type" required>
<?php /*                     Loops over each program code and name in the PROGRAMS list. */ ?>
                    <?php foreach (PROGRAMS as $code => $name): ?>
<?php /*                     Shows one program, preselecting MSCS by default. */ ?>
                    <option value="<?= $code ?>" <?= $code === 'MSCS' ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
<?php /*                     Ends the program option loop. */ ?>
                    <?php endforeach; ?>
<?php /*                 Closes the program dropdown. */ ?>
                </select>
<?php /*             Closes the program field. */ ?>
            </div>
<?php /*             Opens the presentation / document type field. */ ?>
            <div class="form-field">
<?php /*                 Label for the document type, with a required star. */ ?>
                <label>Presentation / Document Type <span class="required">*</span></label>
<?php /*                 Dropdown listing the document type choices. */ ?>
                <select required>
<?php /*                     Option 'Form', preselected when the record is a form. */ ?>
                    <option value="form" <?= ($template['document_type'] ?? '') === 'form' ? 'selected' : '' ?>>Form</option>
<?php /*                     Option 'Template', preselected when the record is a template. */ ?>
                    <option value="template" <?= ($template['document_type'] ?? 'template') === 'template' ? 'selected' : '' ?>>Template</option>
<?php /*                     Option 'Reference', preselected when it is a reference. */ ?>
                    <option value="reference" <?= ($template['document_type'] ?? '') === 'reference' ? 'selected' : '' ?>>Reference</option>
<?php /*                 Closes the document type dropdown. */ ?>
                </select>
<?php /*             Closes the document type field. */ ?>
            </div>
<?php /*         Closes the two-column form row. */ ?>
        </div>
<?php /*         Opens the field showing the current file. */ ?>
        <div class="form-field">
<?php /*             Label 'Current File'. */ ?>
            <label>Current File</label>
<?php /*             Shows the stored file name with a Word icon. */ ?>
            <p style="font-size:0.875rem;color:var(--gray-600);"><i class="fas fa-file-word"></i> <?= htmlspecialchars($template['file_name'] ?? 'proposal_template_mscs.docx') ?></p>
<?php /*         Closes the current file field. */ ?>
        </div>
<?php /*         Opens the field for replacing the file. */ ?>
        <div class="form-field">
<?php /*             Label 'Replace Template File'. */ ?>
            <label>Replace Template File</label>
<?php /*             Opens the drag-and-drop upload area box. */ ?>
            <div class="file-upload-area">
<?php /*                 Shows a cloud upload icon. */ ?>
                <i class="fas fa-cloud-upload-alt"></i>
<?php /*                 Tells the user the new upload is optional. */ ?>
                <p>Click or drag to upload a new file (optional)</p>
<?php /*                 Placeholder where the chosen file name appears. */ ?>
                <div class="file-name"></div>
<?php /*                 File input for the optional new file. */ ?>
                <input type="file" name="template_file" accept=".pdf,.docx,.doc">
<?php /*             Closes the upload area box. */ ?>
            </div>
<?php /*         Closes the replace file field. */ ?>
        </div>
<?php /*         Opens the footer row holding the buttons. */ ?>
        <div class="form-actions">
<?php /*             Save button that submits the changes. */ ?>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
<?php /*             Cancel link returning to the template list. */ ?>
            <a href="<?= url('coordinator/templates/manage.php') ?>" class="btn btn-outline">Cancel</a>
<?php /*         Closes the button row. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the card form. */ ?>
</form>

<?php /* Loads the shared footer and closes the page. */ ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
