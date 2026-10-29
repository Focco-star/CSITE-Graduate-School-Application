<?php /* Starts the PHP code for the add template page. */ ?>
<?php
// Loads config settings, helpers, and demo data arrays.
require_once __DIR__ . '/../../includes/config/config.php';
// Loads the database helper used when saving templates.
require_once __DIR__ . '/../../includes/config/db.php';

// Sets the page title used in the browser tab.
$pageTitle   = 'Add Template';
// Tells the layout the signed-in user is a coordinator.
$role        = 'coordinator';
// Marks the 'templates' sidebar link as active.
$currentPage = 'templates';
// Takes the demo coordinator name for the header greeting.
$userName    = $mockCoordinator['name'];

// Starts an empty error message for the form.
$templateError = '';
// Checks whether the form was submitted with POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Opens a try block so save errors can be shown to the user.
    try {
        // Starts the call that saves the new template record.
        createTemplateRecord([
            // Reads the template name typed in the form.
            'template_name' => $_POST['template_name'] ?? '',
            // Reads the chosen program or track value.
            'program_track' => $_POST['program_track'] ?? '',
            // Reads the chosen stage label value.
            'stage_label' => $_POST['stage_label'] ?? '',
            // Reads the document type, defaulting to 'template'.
            'document_type' => $_POST['document_type'] ?? 'template',
            // Reads the optional description text.
            'description' => $_POST['description'] ?? '',
        // Passes the uploaded file from the form to the save function.
        ], $_FILES['template_file'] ?? []);
        // Goes back to the template list after a successful save.
        redirectTo('coordinator/templates/manage.php');
    // Catches any error thrown while saving the template.
    } catch (Throwable $e) {
        // Stores the error text so it can be shown on the page.
        $templateError = $e->getMessage();
    // Closes the save error handler.
    }
// Closes the form submission block.
}

// Loads the shared header and navigation.
require_once __DIR__ . '/../../includes/layout/header.php';
// Closes PHP so the HTML form below is printed.
?>

<?php /* Opens the page header area. */ ?>
<div class="page-header">
<?php /*     Shows the big heading 'Add Template / Form'. */ ?>
    <h2>Add Template / Form</h2>
<?php /*     Explains that this page uploads a template for students. */ ?>
    <p>Upload a new template for students to download.</p>
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
<?php /*             Text input where the coordinator types the template name. */ ?>
            <input type="text" name="template_name" required placeholder="e.g., Proposal Template (MSCS)">
<?php /*         Closes the template name field. */ ?>
        </div>
<?php /*         Starts a row that puts two fields side by side. */ ?>
        <div class="form-row">
<?php /*             Opens the program field. */ ?>
            <div class="form-field">
<?php /*                 Label for the program, with a required star. */ ?>
                <label>Program <span class="required">*</span></label>
<?php /*                 Dropdown for choosing the program or track. */ ?>
                <select name="program_track" required>
<?php /*                     Placeholder option asking the user to select a track. */ ?>
                    <option value="">Select track</option>
<?php /*                     Option for the Thesis track. */ ?>
                    <option value="thesis">Thesis</option>
<?php /*                     Option for the Capstone track. */ ?>
                    <option value="capstone">Capstone</option>
<?php /*                     Option for the Seminar Paper track. */ ?>
                    <option value="seminar">Seminar Paper</option>
<?php /*                 Closes the program dropdown. */ ?>
                </select>
<?php /*             Closes the program field. */ ?>
            </div>
<?php /*             Opens the stage / document type field. */ ?>
            <div class="form-field">
<?php /*                 Label for the stage or document type, with a required star. */ ?>
                <label>Presentation / Document Type <span class="required">*</span></label>
<?php /*                 Dropdown for choosing the presentation stage. */ ?>
                <select name="stage_label" required>
<?php /*                     Placeholder option asking the user to select a stage. */ ?>
                    <option value="">Select stage</option>
<?php /*                     Loops over the fixed list of stage names to build options. */ ?>
                    <?php foreach (['Concept Paper', 'Thesis Proposal', 'Final Thesis', 'Capstone Proposal', 'Final Capstone', 'Seminar Paper Proposal', 'Final Seminar Paper'] as $stage): ?>
<?php /*                     Shows one stage name as an option, escaped for safety. */ ?>
                    <option value="<?= htmlspecialchars($stage) ?>"><?= htmlspecialchars($stage) ?></option>
<?php /*                     Ends the stage option loop. */ ?>
                    <?php endforeach; ?>
<?php /*                 Closes the stage dropdown. */ ?>
                </select>
<?php /*             Closes the stage field. */ ?>
            </div>
<?php /*         Closes the two-column form row. */ ?>
        </div>
<?php /*         Opens the field for the template file. */ ?>
        <div class="form-field">
<?php /*             Label for the file upload, with a required star. */ ?>
            <label>Template File <span class="required">*</span></label>
<?php /*             Opens the drag-and-drop upload area box. */ ?>
            <div class="file-upload-area">
<?php /*                 Shows a cloud upload icon. */ ?>
                <i class="fas fa-cloud-upload-alt"></i>
<?php /*                 Tells the user they can click or drag to upload. */ ?>
                <p>Click or drag to upload template file</p>
<?php /*                 Notes the allowed file types and the 10MB limit. */ ?>
                <p style="font-size:0.75rem;">PDF, DOCX (Max 10MB)</p>
<?php /*                 Placeholder where the chosen file name appears. */ ?>
                <div class="file-name"></div>
<?php /*                 File input that accepts PDF or Word files. */ ?>
                <input type="file" name="template_file" accept=".pdf,.docx,.doc" required>
<?php /*             Closes the upload area box. */ ?>
            </div>
<?php /*         Closes the template file field. */ ?>
        </div>
<?php /*         Opens the footer row holding the buttons. */ ?>
        <div class="form-actions">
<?php /*             Save button that submits the form with a save icon. */ ?>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Template</button>
<?php /*             Cancel link returning to the template list. */ ?>
            <a href="<?= url('coordinator/templates/manage.php') ?>" class="btn btn-outline">Cancel</a>
<?php /*         Closes the button row. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the card form. */ ?>
</form>

<?php /* Loads the shared footer and closes the page. */ ?>
<?php require_once __DIR__ . '/../../includes/layout/footer.php'; ?>
