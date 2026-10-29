<?php /* Starts the PHP page that builds the student Application form. */ ?>
<?php
// Loads the shared config file with paths, session setup and helper functions.
require_once __DIR__ . '/../includes/config/config.php';
// Loads the database helper file so DB::getConnection() is available.
require_once __DIR__ . '/../includes/config/db.php';

/* ------------------------------------------------------------------
 * Helpers shared by the Application form
 * ------------------------------------------------------------------ */

/** True when the browser actually sent a file. */
// Defines a helper that tells whether the browser really sent a file.
function csiteUploadHasFile($file): bool
// Opens the body of the csiteUploadHasFile helper.
{
    // Returns true only when a file array exists and its error code is not no file.
    return is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
// Closes the csiteUploadHasFile helper.
}

/** Validates a document upload. Returns an error message, or null when it is fine. */
// Defines the upload checker, returning an error message or null when the file is fine.
function csiteCheckUpload($file, string $docType, string $stageKey, array $stages, string $email, string $track): ?string
// Opens the body of the csiteCheckUpload helper.
{
    // If no document type was picked, the upload cannot be checked.
    if ($docType === '') {
        // Returns a message asking the student to choose a document type.
        return 'Please select a document type.';
    // Closes the empty document type check.
    }
    // Checks that a stage key was sent and that it exists in the workflow stages.
    if ($stageKey === '' || !isset($stages[$stageKey])) {
        // Returns a message asking the student to choose a stage.
        return 'Please select a stage.';
    // Closes the stage key check.
    }
    // Asks the workflow whether this document is allowed at this stage yet.
    if (($seqError = validateUploadSequence($email, $track, $stageKey, $docType)) !== null) {
        // Returns the sequence error, for example a document that is not open yet.
        return $seqError;
    // Closes the upload sequence check.
    }
    // Checks that a file was actually attached to the request.
    if (!csiteUploadHasFile($file)) {
        // Returns a message asking the student to pick a file.
        return 'Please select a file to upload.';
    // Closes the missing file check.
    }
    // Checks the PHP upload error code reported by the browser.
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        // Returns a message for a failed upload so the student can retry.
        return 'The file could not be uploaded. Try again.';
    // Closes the upload error check.
    }
    // Reads the file extension from the original name in lower case.
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    // Allows only PDF, DOC and DOCX documents.
    if (!in_array($ext, ['pdf', 'doc', 'docx'], true)) {
        // Returns a message listing the accepted file types.
        return 'Only PDF, DOC, or DOCX files are allowed.';
    // Closes the file type check.
    }
    // Rejects files bigger than 20 megabytes.
    if ($file['size'] > 20 * 1024 * 1024) {
        // Returns a message saying the file is too large.
        return 'File exceeds the 20 MB maximum size.';
    // Closes the file size check.
    }
    // Returns null, meaning the upload passed every check.
    return null;
// Closes the csiteCheckUpload helper.
}

/** Stores the file and records it against the student. Throws RuntimeException on failure. */
// Defines a helper that stores the file and records it as a student document.
function csiteSaveUpload(array $file, string $docType, string $stageKey, array $stages, string $email, int $userId): void
// Opens the body of the csiteSaveUpload helper.
{
    // Moves the uploaded file into storage and returns its stored path and MIME type.
    $saved  = storeStudentUpload($file);
    // Finds the newest application of this student so the document can point to it.
    $linked = latestApplicationForEmail($email);
    // Saves the document record with its type, stage, size and stored file details.
    recordStudentDocument([
        // Links the document to the application id, or null when there is none yet.
        'applicationId' => $linked['id'] ?? null,
        // Stores the email of the student who owns the document.
        'studentEmail'  => $email,
        // Keeps the original file name the student uploaded.
        'fileName'      => $file['name'],
        // Stores the document type, such as Research Paper or Official Receipt.
        'docType'       => $docType,
        // Stores the readable stage label the document belongs to.
        'stage'         => $stages[$stageKey]['label'],
        // Stores the file size in bytes.
        'size'          => $file['size'],
        // Stores the internal path of the saved file.
        'storedFile'    => $saved['storedFile'],
        // Stores the detected MIME type of the file.
        'mimeType'      => $saved['mimeType'],
    // Closes the record array and passes the student email and user id.
    ], $email, $userId);
// Closes the csiteSaveUpload helper.
}

/** Parses a Y-m-d date. Returns the date string, null when blank, or false when invalid. */
// Defines a date parser returning the date string, null when blank, false when invalid.
function csiteParseDate($raw)
// Opens the body of the csiteParseDate helper.
{
    // Trims spaces and casts the posted value to text.
    $raw = trim((string) $raw);
    // Checks whether the value is empty, meaning no date was given.
    if ($raw === '') {
        // Returns null for a blank date.
        return null;
    // Closes the blank date check.
    }
    // Tries to build a date object from the Y-m-d text.
    $d = DateTime::createFromFormat('Y-m-d', $raw);
    // Returns the date only when it round-trips exactly, otherwise false.
    return ($d && $d->format('Y-m-d') === $raw) ? $raw : false;
// Closes the csiteParseDate helper.
}

/** Normalises a stored / posted enrolment date to Y-m-d for the editable date field. */
// Defines a helper that turns any stored date into Y-m-d text for the date field.
function csiteDateValue($raw): string
// Opens the body of the csiteDateValue helper.
{
    // Trims spaces and casts the value to text.
    $raw = trim((string) $raw);
    // Checks whether no date value is stored.
    if ($raw === '') {
        // Returns an empty string so the date input stays blank.
        return '';
    // Closes the blank value check.
    }
    // Detects a value that is already in YYYY-MM-DD form.
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
        // Returns the already formatted date unchanged.
        return $raw;
    // Closes the YYYY-MM-DD check.
    }
    // Converts other date formats into a Unix timestamp.
    $ts = strtotime($raw);
    // Formats the timestamp as Y-m-d, or returns blank when parsing failed.
    return $ts ? date('Y-m-d', $ts) : '';
// Closes the csiteDateValue helper.
}

/** Editable (typeable) date input with a calendar button. The calendar itself lives in shared/app.js. */
// Defines a helper that builds a typeable date input with a calendar button.
function csiteDateField(string $name, $value): string
// Opens the body of the csiteDateField helper.
{
    // Starts the date picker wrapper that shared/app.js hooks into.
    return '<div class="date-picker" data-date-picker>'
        // Creates the text input, escaping the field name and the current date value.
        . '<input type="text" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars(csiteDateValue($value)) . '"'
        // Adds the placeholder, numeric keyboard hint, autocomplete off and length limit.
        . ' placeholder="YYYY-MM-DD" inputmode="numeric" autocomplete="off" maxlength="10"'
        // Requires the YYYY-MM-DD pattern and shows a format hint on hover.
        . ' pattern="\d{4}-\d{2}-\d{2}" title="Use the format YYYY-MM-DD">'
        // Adds the button that opens the calendar when clicked.
        . '<button type="button" class="date-picker-btn" data-date-toggle aria-label="Open calendar" title="Open calendar">'
        // Draws the calendar icon and closes the button.
        . '<i class="fas fa-calendar-alt"></i></button>'
        // Closes the date picker wrapper and ends the returned HTML.
        . '</div>';
// Closes the csiteDateField helper.
}

/**
 * Saves the enrolment / start date. It is remembered in the session and also written to
 * students.enroll_date when that column exists (a missing column is ignored, not fatal).
 */
// Defines a helper that saves the enrolment date in the session and the students table.
function csiteSaveEnrollDate(?PDO $pdo, int $userId, string $date): void
// Opens the body of the csiteSaveEnrollDate helper.
{
    // Remembers the date in the session so the form shows it right away.
    $_SESSION['enroll_date_override'] = $date;
    // Skips the database write when there is no connection or no user id.
    if (!$pdo || $userId <= 0) {
        // Leaves the session value as the only copy of the date.
        return;
    // Closes the no connection or no user check.
    }
    // Starts a guarded block because the enroll_date column may not exist.
    try {
        // Prepares the update of the student's enrolment date.
        $pdo->prepare('UPDATE students SET enroll_date = :enroll_date WHERE user_id = :user_id')
            // Writes the date onto the students row of the logged-in user.
            ->execute(['enroll_date' => $date, 'user_id' => $userId]);
    // Catches the error raised when the enroll_date column is missing.
    } catch (Throwable $e) {
        // Notes that the schema has no such column and the session value still applies.
        // column not present in this schema; the session value above still applies
    // Closes the catch block.
    }
// Closes the csiteSaveEnrollDate helper.
}

/**
 * Uploading a document before the application is submitted leaves a placeholder
 * application row behind for that stage (paper_title = "Untitled paper"), because
 * the document needs something to be linked to. The student submits the real
 * application afterwards, so that placeholder is filled in here instead of
 * leaving an empty extra application for the coordinator.
 *
 * Returns the application id that was reused, or 0 when a new row is needed.
 */
// Defines a helper that finds and fills the empty placeholder application of a stage.
function csiteFillPlaceholderApplication(PDO $pdo, int $userId, string $stageLabel, string $title): int
// Opens the body of the csiteFillPlaceholderApplication helper.
{
    // Starts a guarded block because these queries may fail on an older schema.
    try {
        // Prepares the query that looks for a placeholder application row.
        $find = $pdo->prepare(
            // Selects the oldest placeholder application of this user and stage with no real title.
            'SELECT application_id FROM applications
             WHERE user_id = :user_id AND archived_at IS NULL
               AND presentation_stage = :stage
               AND (paper_title IS NULL OR TRIM(paper_title) = "" OR paper_title = "Untitled paper")
             ORDER BY submitted_at ASC LIMIT 1'
        // Ends the prepared SELECT statement.
        );
        // Runs the query with the user id and the stage label.
        $find->execute(['user_id' => $userId, 'stage' => $stageLabel]);
        // Reads the found application id, or 0 when no placeholder exists.
        $id = (int) ($find->fetchColumn() ?: 0);
        // Checks whether no placeholder was found.
        if ($id <= 0) {
            // Returns 0 so the caller inserts a new application instead.
            return 0;
        // Closes the no placeholder check.
        }
        // Prepares the update that fills in the placeholder row.
        $pdo->prepare(
            // Sets the real paper title and marks the placeholder as submitted.
            'UPDATE applications
             SET paper_title = :paper_title, status = :status
             WHERE application_id = :id AND user_id = :user_id'
        // Runs the update with the new title and the row identifiers.
        )->execute([
            // Writes the paper title the student typed.
            'paper_title' => $title,
            // Marks the reused application as submitted.
            'status'      => 'submitted',
            // Targets the placeholder application id.
            'id'          => $id,
            // Limits the update to this student's own row.
            'user_id'     => $userId,
        // Closes the update parameters.
        ]);
        // Returns the application id that was reused.
        return $id;
    // Catches any failure and falls back to creating a new row.
    } catch (Throwable $e) {
        // Reports that no placeholder was reused.
        return 0;
    // Closes the catch block.
    }
// Closes the csiteFillPlaceholderApplication helper.
}

/**
 * Drops the empty placeholder record the document upload left in the session
 * store, so the student ends up with exactly one application per stage.
 */
// Defines a helper that removes the blank placeholder entry from the session store.
function csiteDropPlaceholderSessionRecord(string $email, string $stageKey): void
// Opens the body of the csiteDropPlaceholderSessionRecord helper.
{
    // Reads the applications kept in the session store.
    $apps = storeGet('applications');
    // Stops when the store holds no applications.
    if (!$apps) {
        // Returns early because there is nothing to clean up.
        return;
    // Closes the empty store check.
    }
    // Keeps every entry except the untitled one for this student and stage.
    $kept = array_values(array_filter($apps, static function ($a) use ($email, $stageKey) {
        // Entries that belong to other students are always kept.
        if (strcasecmp((string) ($a['studentEmail'] ?? ''), $email) !== 0) {
            // Keeps this entry as it is.
            return true;
        // Closes the other student check.
        }
        // Works out the stage key of the entry, from its label when needed.
        $key = $a['stageKey'] ?? stageKeyFromLabel((string) ($a['stage'] ?? ''));
        // Entries that belong to other stages are always kept.
        if ($key !== $stageKey) {
            // Keeps this entry as it is.
            return true;
        // Closes the other stage check.
        }
        // Drops the entry only when its title is empty.
        return trim((string) ($a['title'] ?? '')) !== '';
    // Closes the filter callback and re-indexes the remaining entries.
    }));
    // Checks whether anything was actually removed.
    if (count($kept) !== count($apps)) {
        // Saves the cleaned list back to the session store.
        storeSet('applications', $kept);
    // Closes the changed list check.
    }
// Closes the csiteDropPlaceholderSessionRecord helper.
}

// Starts a session only when one is not running already.
if (session_status() === PHP_SESSION_NONE) {
    // Opens the PHP session that remembers the logged-in user.
    session_start();
// Closes the session start check.
}

// Reads the logged-in user from the session, or an empty array.
$sessionUser = $_SESSION['user'] ?? [];
// Loads the student profile that fills the application form.
$mockStudent = currentStudentProfile($mockStudent);

// When someone is logged in, their account details are used.
if (!empty($sessionUser)) {
    // Takes the full name from the session user, with fallbacks.
    $mockStudent['name']  = $sessionUser['full_name'] ?? $sessionUser['name'] ?? $mockStudent['name'];
    // Takes the email address from the session user.
    $mockStudent['email'] = $sessionUser['email'] ?? $mockStudent['email'];
    // Only overrides the program when the session user has one.
    if (!empty($sessionUser['program'])) {
        // Uses the program code stored on the logged-in user.
        $mockStudent['program'] = $sessionUser['program'];
    // Closes the program check.
    }
    // Only overrides the program name when the session user has one.
    if (!empty($sessionUser['program_name'])) {
        // Uses the readable program name stored on the logged-in user.
        $mockStudent['program_name'] = $sessionUser['program_name'];
    // Closes the program name check.
    }
// Closes the logged-in user block.
}
// Checks for an enrolment date the student saved earlier in the session.
if (!empty($_SESSION['enroll_date_override'])) {
    // Copies the saved enrolment date into the student profile.
    $mockStudent['enroll_date'] = $_SESSION['enroll_date_override'];
// Closes the saved enrolment date check.
}

// Sets the browser page title to Application.
$pageTitle   = 'Application';
// Tells the layout that this page belongs to the student role.
$role        = 'student';
// Highlights the Application item in the student sidebar.
$currentPage = 'application';
// Shows the student name in the top bar.
$userName    = $mockStudent['name'];
// Finds the track (thesis, capstone or seminar) for the student program.
$track       = getTrackForProgram($mockStudent['program']);
// Gets the readable track name for display.
$trackLabel  = getTrackLabel($track);
// Loads the milestone workflow of this track.
$workflow    = getWorkflow($track);
// Starts an empty list that will hold the stages by key.
$stages      = [];
// Loops over the stages of the track workflow in order.
foreach ($workflow['stages'] as $s) {
    // Stores each stage under its key so it can be looked up quickly.
    $stages[$s['key']] = $s;
// Closes the stage loop.
}
// True when the student's track is the Thesis track.
$isThesis      = $track === 'thesis';
// Placeholder for the success pop-up; null means nothing to show.
$popup         = null;   // ['title' => ..., 'message' => ...] -> shown as the success pop-up
// Holds the validation error message shown above the form.
$appError      = '';
// Reads the logged-in user id, or 0 when it is unknown.
$userId        = (int) ($sessionUser['user_id'] ?? $_SESSION['user_id'] ?? 0);

// Loads available advisers from the coordinator-managed pool.
$adviserOpts = [];
$adviserPoolError = '';
try {
    $adviserStmt = DB::query(
        "SELECT DISTINCT TRIM(name) AS name
         FROM advisor_pool
         WHERE availability = 'available' AND TRIM(name) <> ''
         ORDER BY name ASC"
    );
    $adviserOpts = array_map('strval', $adviserStmt->fetchAll(PDO::FETCH_COLUMN));
} catch (Throwable $e) {
    error_log('[student application adviser pool] ' . $e->getMessage());
    $adviserPoolError = 'The adviser pool could not be loaded. Please refresh the page or contact the coordinator.';
}

// Loads the milestone progress of this student for the track.
$progress = getStudentProgress($mockStudent['email'], $track);
// Starts from the stage saved on the student profile.
$currentStageKey = $mockStudent['current_stage'] ?? '';
// Loops over the progress rows of the stages.
foreach ($progress as $p) {
    // Finds the first stage that is not completed or approved yet.
    if (!in_array($p['stageStatus'], ['completed', 'approved'], true)) {
        // Marks that unfinished stage as the one the student is working on.
        $currentStageKey = $p['stageKey'];
        // Stops looking once the first unfinished stage is found.
        break;
    // Closes the unfinished stage check.
    }
// Closes the progress loop.
}
// Falls back when the stored stage key is not in the workflow.
if (!isset($stages[$currentStageKey])) {
    // Uses the first stage of the workflow, or proposal as a last resort.
    $currentStageKey = array_key_first($stages) ?: 'proposal';
// Closes the unknown stage fallback.
}

/* ------------------------------------------------------------------
 * The draft
 *
 * The values the student typed live in the database, so they survive a trip
 * through the Dashboard, a refresh, or a logout and login.
 * ------------------------------------------------------------------ */
// Loads the draft values this user saved earlier.
$draft = loadApplicationDraft($userId);

/* ------------------------------------------------------------------
 * Draft endpoint
 *
 * The form saves itself in the background (and when the student leaves the
 * page), so it posts here. It answers with a 1x1 pixel and never renders a
 * page of its own.
 * ------------------------------------------------------------------ */
// Handles the background auto-save request sent by the form.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'draft') {
    // Starts saving the typed values as this user's draft.
    saveApplicationDraft($userId, [
        // Keeps the paper title the student typed.
        'title'          => $_POST['title'] ?? '',
        // Keeps the adviser name.
        'adviser'        => $_POST['adviser'] ?? '',
        // Keeps the brief description or abstract.
        'abstract'       => $_POST['abstract'] ?? '',
        // Keeps the enrolment date text.
        'enroll_date'    => $_POST['enroll_date'] ?? '',
        // Keeps the selected stage key.
        'stage_key'      => $_POST['stage'] ?? '',
        // Keeps the chosen document type.
        'doc_type'       => $_POST['docType'] ?? '',
        // Keeps the name of the file the student picked.
        'file_name'      => $_POST['file_name'] ?? '',
        // Keeps the official receipt number.
        'receipt_number' => $_POST['receiptNumber'] ?? '',
        // Keeps the amount paid.
        'payment_amount' => $_POST['paymentAmount'] ?? '',
        // Keeps the payment date text.
        'payment_date'   => $_POST['paymentDate'] ?? '',
    // Closes the draft array and writes it to the database.
    ]);
    // Answers with a GIF type because the browser expects an image.
    header('Content-Type: image/gif');
    // Stops the browser from caching the tiny response.
    header('Cache-Control: no-store, no-cache, must-revalidate');
    // Sends a 1x1 transparent GIF pixel as the whole answer.
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    // Stops the script so no page HTML is rendered.
    exit;
// Closes the draft auto-save branch.
}

/* ------------------------------------------------------------------
 * Discard endpoint
 *
 * The student throws away the values they typed. Only the four unsent
 * fields are cleared - uploaded documents, submitted applications and the
 * student record are never touched.
 * ------------------------------------------------------------------ */
// Handles the request that clears the unsent draft fields.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'discard_draft') {
    // Clears only the four unsent draft fields of this user.
    discardApplicationDraftFields($userId);
    // Answers with an image content type.
    header('Content-Type: image/gif');
    // Prevents the browser from caching the discard response.
    header('Cache-Control: no-store, no-cache, must-revalidate');
    // Returns the same 1x1 GIF pixel.
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    // Ends the request without rendering the page.
    exit;
// Closes the discard draft branch.
}

/* ------------------------------------------------------------------
 * The values of the form
 *
 * What was typed in this request wins, then the saved draft, then the
 * application the student already submitted, then the student profile.
 * ------------------------------------------------------------------ */
// Starts with no posted values until a POST request is seen.
$postedValues = null;
// Only reads posted values when the form was submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collects the field values sent by the form in this request.
    $postedValues = [
        // Reads the posted title, trimmed, empty when missing.
        'title'         => trim((string) ($_POST['title'] ?? '')),
        // Reads the posted adviser name.
        'adviser'       => trim((string) ($_POST['adviser'] ?? '')),
        // Reads the posted abstract text.
        'abstract'      => trim((string) ($_POST['abstract'] ?? '')),
        // Reads the posted enrolment date.
        'enroll_date'   => trim((string) ($_POST['enroll_date'] ?? '')),
        // Reads the posted stage key.
        'stage'         => trim((string) ($_POST['stage'] ?? '')),
        // Reads the posted document type.
        'docType'       => trim((string) ($_POST['docType'] ?? '')),
        // Reads the posted receipt number.
        'receiptNumber' => trim((string) ($_POST['receiptNumber'] ?? '')),
        // Reads the posted payment amount.
        'paymentAmount' => trim((string) ($_POST['paymentAmount'] ?? '')),
        // Reads the posted payment date.
        'paymentDate'   => trim((string) ($_POST['paymentDate'] ?? '')),
    // Closes the posted values array.
    ];
// Closes the POST check.
}

// Finds an application this student already submitted for the current stage.
$existingApplication = applicationForStage($mockStudent['email'], $currentStageKey);

// Builds the values that will fill the form fields.
$formValues = [
    // Starts the title from the student profile.
    'title'         => (string) ($mockStudent['title'] ?? ''),
    // Starts the adviser name from the student profile.
    'adviser'       => (string) ($mockStudent['adviser'] ?? ''),
    // The abstract always starts empty.
    'abstract'      => '',
    // Starts the enrolment date from the student profile.
    'enroll_date'   => (string) ($mockStudent['enroll_date'] ?? ''),
    // Uses the stage the student is currently working on.
    'stage'         => $currentStageKey,
    // No document type is preselected.
    'docType'       => '',
    // The receipt number starts blank.
    'receiptNumber' => '',
    // The amount paid starts blank.
    'paymentAmount' => '',
    // The payment date starts blank.
    'paymentDate'   => '',
// Closes the starting form values.
];
// When a submission already exists, reuse its details on the form.
if ($existingApplication) {
    // Explains why the submitted details stay on the form.
    // The student already submitted this stage: keep their details on the form
    // So the student can attach more documents without retyping anything.
    // so they can attach further documents without retyping anything.
    // Takes the title from the existing application.
    $formValues['title']   = (string) ($existingApplication['title'] ?? $formValues['title']);
    // Takes the adviser name from the existing application.
    $formValues['adviser'] = (string) ($existingApplication['adviser'] ?? $formValues['adviser']);
    // Also restores the receipt details the student entered before.
    // The receipt details the student entered are kept as well.
    // Reads the receipt number, amount and date from the application.
    $existingReceipt = applicationReceiptDetails($existingApplication);
    // Fills in the saved receipt number.
    $formValues['receiptNumber'] = $existingReceipt['receiptNumber'];
    // Fills in the saved amount paid.
    $formValues['paymentAmount'] = $existingReceipt['amount'];
    // Builds the payment date only when one was saved.
    $formValues['paymentDate']   = $existingReceipt['date'] !== ''
        // Formats the stored payment date as Y-m-d.
        ? date('Y-m-d', strtotime($existingReceipt['date']))
        // Leaves the payment date blank when nothing was saved.
        : '';
// Closes the existing application block.
}
// When a saved draft exists, its values take priority.
if ($draft) {
    // Loops over the plain text fields kept in the draft.
    foreach (['title', 'adviser', 'abstract', 'enroll_date', 'stage', 'docType'] as $field) {
        // Only overrides a field when the draft holds a value.
        if (($draft[$field] ?? '') !== '') {
            // Copies the draft value into the form values.
            $formValues[$field] = (string) $draft[$field];
        // Closes the draft value check.
        }
    // Closes the plain text field loop.
    }
    // Maps the draft column names to the form field names for the payment details.
    foreach (['receipt_number' => 'receiptNumber', 'payment_amount' => 'paymentAmount', 'payment_date' => 'paymentDate'] as $draftField => $formField) {
        // Uses the draft value when it is set and not empty.
        if (($draft[$draftField] ?? '') !== null && ($draft[$draftField] ?? '') !== '') {
            // Copies the receipt or payment value into the form.
            $formValues[$formField] = (string) $draft[$draftField];
        // Closes the payment value check.
        }
    // Closes the payment field loop.
    }
// Closes the saved draft block.
}
// Finally, values posted in this request win over everything else.
if ($postedValues !== null) {
    // Overwrites the form values with the posted ones.
    $formValues = array_merge($formValues, $postedValues);
// Closes the posted values block.
}

/*
 * The presentation stage is not a choice for the student: it is the stage the
 * Milestone Tracker says they are working on. It is read again on every page
 * load, so the field moves on by itself when the current stage is completed.
 */
// Forces the stage to the one the Milestone Tracker says is current.
$formValues['stage'] = $currentStageKey;

/*
 * The Brief Description / Abstract is only asked for at the last stage of the
 * student's own track workflow (Final Thesis, Final Capstone or Final Seminar
 * Paper). Earlier stages keep the field hidden, and the requirement check below
 * follows the same rule.
 */
// Finds the key of the last stage in the workflow.
$finalStageKey = (string) array_key_last($stages);
// Shows the abstract field only when the form is on the final stage.
$showAbstract  = $finalStageKey !== '' && $formValues['stage'] === $finalStageKey;

/*
 * The document types that are open for the student, taken from the Milestone
 * Tracker of every stage. A type that is not open is shown in the list, but
 * it can not be picked: the workflow of the track asks for the documents in
 * the order of the milestones.
 */
// Lists the document types the workflow allows now, per stage.
$openTypesByStage = openDocumentTypesByStage($mockStudent['email'], $track, $stages, $progress);

// The Milestone Tracker sends the student here for a particular stage, and
// for the official receipt it also says which document is expected.
// Reads the stage key that a Milestone Tracker link put in the URL.
$requestedStage = trim((string) ($_GET['stage'] ?? ''));
// Reads the document type the tracker link asks for.
$requestedDocType = trim((string) ($_GET['docType'] ?? ''));
// Applies the link only on a normal page load with a known stage.
if ($postedValues === null && isset($stages[$requestedStage])) {
    // Explains that an old tracker link can never move the student to another stage.
    /*
     * The Milestone Tracker may link here with a stage in the URL. The form
     * always stays on the stage the student is working on, so an old link can
     * never move it forward or back.
     */
    // Keeps the form on the current stage and ignores the link stage.
    $formValues['stage'] = $currentStageKey;
    // Reloads the submission of that stage so its details can be shown.
    $existingApplication = applicationForStage($mockStudent['email'], $formValues['stage']);
    // When the stage was already submitted, refill its details.
    if ($existingApplication) {
        // Uses the saved title.
        $formValues['title']   = (string) ($existingApplication['title'] ?? $formValues['title']);
        // Uses the saved adviser name.
        $formValues['adviser'] = (string) ($existingApplication['adviser'] ?? $formValues['adviser']);
        // Reads the saved receipt details.
        $existingReceipt = applicationReceiptDetails($existingApplication);
        // Fills the receipt number.
        $formValues['receiptNumber'] = $existingReceipt['receiptNumber'];
        // Fills the amount paid.
        $formValues['paymentAmount'] = $existingReceipt['amount'];
        // Checks whether a payment date was saved.
        $formValues['paymentDate']   = $existingReceipt['date'] !== ''
            // Formats the payment date as Y-m-d.
            ? date('Y-m-d', strtotime($existingReceipt['date']))
            // Uses a blank date when none was saved.
            : '';
    // Closes the refill block.
    }
// Closes the tracker stage link block.
}
// Applies a document type from the link on a normal page load.
if ($postedValues === null && $requestedDocType !== '') {
    // Explains that only documents open at this stage can be selected.
    // Only a document that the workflow asks for now is put on the form. A
    // A document of a later milestone stays locked until its turn comes.
    // document of a later milestone stays locked until its turn comes.
    // Gets the document types open for the stage the form is showing.
    $openForStage = $openTypesByStage[$formValues['stage']] ?? [];
    // Loops over the open types looking for the requested one.
    foreach ($openForStage as $openType) {
        // Compares the names while ignoring upper and lower case.
        if (strcasecmp($openType, $requestedDocType) === 0) {
            // Selects the requested document type on the form.
            $formValues['docType'] = $requestedDocType;
            // Stops searching once the type is found.
            break;
        // Closes the document type match check.
        }
    // Closes the open types loop.
    }
// Closes the requested document type block.
}
// Reads the file name remembered by the draft, if any.
$draftFileName = (string) ($draft['file_name'] ?? '');
// Reads when the draft was last saved.
$draftSavedAt  = (string) ($draft['updated_at'] ?? '');

/* ------------------------------------------------------------------
 * POST: Submit Application (with the attached document)
 * ------------------------------------------------------------------ */
// Handles the real submission of the application form.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'application') {
    // Copies the paper title from the form values.
    $title       = $formValues['title'];
    // Cleans up the adviser name's capitalisation and spacing.
    $adviser     = formatPersonName($formValues['adviser']);
    // Copies the stage key being submitted.
    $stageKey    = $formValues['stage'];
    // Copies the abstract text.
    $abstract    = $formValues['abstract'];
    // Parses the enrolment date; false means the text is not a valid date.
    $enrollDate  = csiteParseDate($formValues['enroll_date']);
    // Copies the chosen document type.
    $docType     = $formValues['docType'];
    // Gets the uploaded document from the request, or null when none was sent.
    $file        = $_FILES['document'] ?? null;
    // Checks whether a real file came with the request.
    $hasFile     = csiteUploadHasFile($file);

    // Notes that an official receipt also needs the payment details.
    // The official receipt comes with the details of the payment: the
    // Lists the three payment details kept with the receipt.
    // receipt number, the amount and the day it was paid.
    // True when the chosen document is classified as an official receipt.
    $isReceipt      = ($docType !== '' && classifyUploadDocType($docType) === 'receipt');
    // Reads the receipt number from the form.
    $receiptNumber  = trim((string) $formValues['receiptNumber']);
    // Reads the amount paid from the form.
    $paymentAmount  = trim((string) $formValues['paymentAmount']);
    // Reads the payment date from the form.
    $paymentDate    = trim((string) $formValues['paymentDate']);

    // Explains that an existing submission is updated instead of duplicated.
    // An application the student already submitted for this stage is updated
    // And it is not blocked by the stage order check.
    // rather than created again, and it is not blocked by the stage sequence.
    // Looks for a submission of this stage that the student already sent.
    $existing = applicationForStage($mockStudent['email'], $stageKey);
    // Keeps the lock error empty when the stage was already submitted.
    $stageLockError = $existing
        // Uses null so the update is allowed.
        ? null
        // Otherwise checks that the earlier stages were finished first.
        : (isset($stages[$stageKey]) ? validateApplicationStageSequence($progress, $stages, $stageKey) : null);

    // Notes that the checks below describe the result of this request.
    // What the student will have for this stage once this request is done.
    // Starts the list of documents already uploaded for this stage.
    $stageUploads = [];
    // Loops over every document this student uploaded.
    foreach (uploadsForEmail($mockStudent['email']) as $uploaded) {
        // Keeps only the documents that belong to this stage.
        if (stageKeyFromLabel((string) ($uploaded['stage'] ?? '')) === $stageKey) {
            // Adds the matching document to the stage list.
            $stageUploads[] = $uploaded;
        // Closes the stage match check.
        }
    // Closes the uploaded documents loop.
    }

    // True when the student typed at least one of the application details.
    $hasDetails = $title !== '' || $adviser !== '' || $abstract !== '' || $formValues['enroll_date'] !== '';
    // Explains that details are required only for a new application.
    // The fields are required for a NEW application. An application the
    // An application already sent keeps the details it has.
    // student already sent keeps its details - they only attach the
    // The student then only attaches the documents that are still missing.
    // documents that are still outstanding for that stage.
    // Requires the detail fields only when no submission exists yet.
    $requireFields = !$existing;
    // Starts the list of missing fields and documents.
    $missing = [];

    // Runs the requirement check when there are details or a new application.
    if ($hasDetails || $requireFields) {
        // Asks the workflow which fields and documents are still missing.
        $missing = applicationSubmissionRequirements(
            // Passes the stage definition, or an empty array when the key is unknown.
            $stages[$stageKey] ?? [],
            // Passes the student's track.
            $track,
            // Passes the stage key being submitted.
            $stageKey,
            // Starts the array of typed values used by the check.
            [
                // Passes the paper title.
                'title'       => $title,
                // Passes the cleaned adviser name.
                'adviser'     => $adviser,
                // Passes the abstract text.
                'abstract'    => $abstract,
                // Passes the raw enrolment date text.
                'enroll_date' => $formValues['enroll_date'],
            // Ends the typed values array.
            ],
            // Passes the documents already uploaded for this stage.
            $stageUploads
        // Stores the check result in the missing list.
        );
        // Notes that the abstract is only required at the final stage.
        // The abstract is only required once the student reaches the final
        // Which is the only stage where the abstract field is shown.
        // stage, where the field is shown.
        // Removes the abstract requirement for the earlier stages.
        if (!$showAbstract) {
            // Filters the list of missing fields.
            $missing['fields'] = array_values(array_filter(
                // The list of missing fields to filter.
                $missing['fields'],
                // Drops the abstract entry from that list.
                static fn($field) => $field !== 'Brief Description / Abstract'
            // Re-indexes the filtered list.
            ));
        // Closes the abstract requirement block.
        }
        // Notes that a document attached with this request counts for the stage.
        // The document that comes with this request counts towards the
        // So it is removed from the list of missing documents.
        // requirements of the stage.
        // When a paper document is attached, count its requirement as done.
        if ($hasFile && $docType !== '' && classifyUploadDocType($docType) === 'paper') {
            // Removes the first required document label from the missing list.
            $missing['documents'] = array_values(array_diff($missing['documents'], [$stages[$stageKey]['documents'][0]['label'] ?? '']));
        // Closes the attached paper document block.
        }
        // For an existing application, the detail fields are not forced.
        if (!$requireFields) {
            // Clears the missing field list.
            $missing['fields'] = [];
        // Closes the existing application block.
        }
    // Closes the requirement check block.
    }

    // Starts the validation chain: the stage key must exist first.
    if ($adviserPoolError !== '') {
        $appError = $adviserPoolError;
    } elseif (!in_array(trim((string) $formValues['adviser']), $adviserOpts, true)) {
        $appError = 'Please select an available adviser from the adviser pool.';
    } elseif (!isset($stages[$stageKey])) {
        // Error message for an unknown presentation stage.
        $appError = 'Please select a valid presentation stage.';
    // Next, the stage order must allow this submission.
    } elseif ($stageLockError !== null) {
        // Shows the stage sequence error to the student.
        $appError = $stageLockError;
    // Next, the enrolment date must be valid.
    } elseif ($enrollDate === false) {
        // Error message for a badly typed enrolment date.
        $appError = 'Please enter a valid enrollment / start date.';
    // A receipt must come with a receipt number.
    } elseif ($isReceipt && $receiptNumber === '') {
        // Error asking for the receipt number.
        $appError = 'Please enter the receipt number of your official receipt.';
    // A receipt must come with the amount paid.
    } elseif ($isReceipt && $paymentAmount === '') {
        // Error asking for the amount paid.
        $appError = 'Please enter the amount you paid for the official receipt.';
    // The amount paid must be a number.
    } elseif ($isReceipt && !is_numeric($paymentAmount)) {
        // Error showing an example amount such as 1500.00.
        $appError = 'Please enter the amount as a number, for example 1500.00.';
    // The amount paid must be greater than zero.
    } elseif ($isReceipt && (float) $paymentAmount <= 0) {
        // Error asking for a positive amount.
        $appError = 'Please enter the amount you paid.';
    // A receipt must come with the payment date.
    } elseif ($isReceipt && $paymentDate === '') {
        // Error asking for the payment date.
        $appError = 'Please enter the date you paid for the official receipt.';
    // The payment date must be a real Y-m-d date.
    } elseif ($isReceipt && !csiteParseDate($paymentDate)) {
        // Error message for an invalid payment date.
        $appError = 'Please enter a valid payment date.';
    // The student must type details or attach a document.
    } elseif (!$hasDetails && !$hasFile) {
        // Error telling the student to fill in or attach something first.
        $appError = 'Fill in your application details or attach a document before you submit.';
    // A new application must complete all its required fields.
    } elseif ($requireFields && $missing['fields']) {
        // Lists the missing field names in one message.
        $appError = 'Please complete: ' . implode(', ', $missing['fields']) . '.';
    // The documents required for this stage must all be present.
    } elseif ($missing['documents']) {
        // Lists the missing document names in one message.
        $appError = 'Your application needs these documents for this stage: ' . implode(', ', $missing['documents']) . '.';
    // Finally, the attached file itself must pass the upload checks.
    } elseif ($hasFile && ($uploadCheck = csiteCheckUpload($file, $docType, $stageKey, $stages, $mockStudent['email'], $track)) !== null) {
        // Shows the upload error, such as a wrong file type.
        $appError = $uploadCheck;
    // Everything passed, so the application can be saved.
    } else {
        // Flags whether the application row was saved.
        $applicationSaved = false;
        // Flags whether the document was saved.
        $documentSaved = false;
        // Starts the database work inside a try block.
        try {
            // Opens the shared MySQL connection.
            $pdo = DB::getConnection();
            $adviserCheck = $pdo->prepare(
                "SELECT 1
                 FROM advisor_pool
                 WHERE TRIM(name) = :name AND availability = 'available'
                 LIMIT 1"
            );
            $adviserCheck->execute(['name' => trim((string) $formValues['adviser'])]);
            if (!$adviserCheck->fetchColumn()) {
                throw new RuntimeException('Please select an available adviser from the adviser pool.');
            }
            // Prepares a query to find the student row of this user.
            $studentLookup = $pdo->prepare(
                // Selects the student id for the logged-in user.
                'SELECT s.student_id
                 FROM students s
                 WHERE s.user_id = :user_id AND s.archived_at IS NULL
                 LIMIT 1'
            // Ends the prepared query.
            );
            // Runs the query with the user id parameter.
            $studentLookup->execute([
                // Binds the logged-in user id.
                'user_id' => $userId,
            // Closes the query parameters.
            ]);
            // Reads the student id, or 0 when the row is missing.
            $studentId = (int) ($studentLookup->fetchColumn() ?: 0);

            // Stops when the user has no student profile row.
            if ($studentId <= 0) {
                // Raises an error that is shown as a friendly message.
                throw new RuntimeException('Your student profile was not found. Please sign in again or contact the coordinator.');
            // Closes the missing student profile check.
            }

            // Gets the readable stage name that will be stored.
            $stageLabel = $stages[$stageKey]['label'];

            // Starts a transaction so all writes succeed or none do.
            $pdo->beginTransaction();

            // Only touches the application row when the student typed details.
            if ($hasDetails) {
                // Notes that an early upload may have created a placeholder application.
                // The document may have been uploaded first, so a placeholder
                // That placeholder is filled in instead of adding a second row.
                // application may already exist for this stage - fill that one
                // So the student keeps exactly one application per stage.
                // in instead of adding a second, empty application.
                // Tries to fill the placeholder and returns its id, or 0 when there is none.
                $reusedApplicationId = csiteFillPlaceholderApplication($pdo, $userId, $stageLabel, $title);
                // When a placeholder was reused, update its title and status.
                if ($reusedApplicationId > 0) {
                    // Prepares the update of the reused application.
                    $pdo->prepare(
                        // Sets the real title and marks the row submitted.
                        'UPDATE applications
                         SET paper_title = :paper_title, status = :status
                         WHERE application_id = :id AND user_id = :user_id'
                    // Runs the update with the new values.
                    )->execute([
                        // Passes the real paper title.
                        'paper_title' => $title,
                        // Marks the reused application as submitted.
                        'status'      => 'submitted',
                        // Targets the reused application id.
                        'id'          => $reusedApplicationId,
                        // Limits the update to this student.
                        'user_id'     => $userId,
                    // Closes the update parameters.
                    ]);
                // Otherwise update the submission that already exists.
                } elseif ($existing) {
                    // Notes that only the details are refreshed here.
                    // The application for this stage already exists: refresh the
                    // The status stays under the coordinator's control.
                    // details the student filled in, but leave the status to
                    // That is why status is not written by this update.
                    // the coordinator.
                    // Prepares the update of the existing submission.
                    $pdo->prepare(
                        // Updates the title only when it is not empty, plus the stage label.
                        'UPDATE applications
                         SET paper_title = COALESCE(NULLIF(:paper_title, ""), paper_title),
                             presentation_stage = :presentation_stage
                         WHERE application_id = :id AND user_id = :user_id'
                    // Runs the update with the new values.
                    )->execute([
                        // The new title, ignored by COALESCE when it is blank.
                        'paper_title'        => $title,
                        // Confirms the presentation stage label.
                        'presentation_stage' => $stageLabel,
                        // Targets the existing application id.
                        'id'                 => (int) $existing['id'],
                        // Limits the update to this student.
                        'user_id'            => $userId,
                    // Closes the update parameters.
                    ]);
                // Otherwise a brand new application row is inserted.
                } else {
                    // Prepares the insert statement.
                    $insertApplication = $pdo->prepare(
                        // Inserts the user, stage, title and submitted status.
                        'INSERT INTO applications (user_id, presentation_stage, paper_title, status)
                         VALUES (:user_id, :presentation_stage, :paper_title, :status)'
                    // Ends the prepared insert statement.
                    );
                    // Runs the insert with the new values.
                    $insertApplication->execute([
                        // The owner of the application.
                        'user_id' => $userId,
                        // The stage label of this submission.
                        'presentation_stage' => $stageLabel,
                        // The title the student typed.
                        'paper_title' => $title,
                        // Marks the new application as submitted.
                        'status' => 'submitted',
                    // Closes the insert parameters.
                    ]);
                // Closes the placeholder, existing or new application branch.
                }

                // Prepares saving the adviser name on the student row.
                $pdo->prepare('UPDATE students SET adviser_name = :adviser_name WHERE user_id = :user_id')
                    // Writes the adviser name and user id.
                    ->execute(['adviser_name' => $adviser, 'user_id' => $userId]);

                // Prepares a query that reads the student identity fields.
                $identityStmt = $pdo->prepare(
                    // Selects name parts, age, gender, program, track, email and full name.
                    'SELECT s.student_id, s.first_name, s.last_name, s.middle_initial, s.age, s.gender, s.program, s.track, u.email, u.full_name
                     FROM students s
                     INNER JOIN users u ON u.user_id = s.user_id
                     WHERE s.user_id = :user_id
                     LIMIT 1'
                // Ends the prepared identity query.
                );
                // Runs the identity query for the logged-in user.
                $identityStmt->execute(['user_id' => $userId]);
                // Reads the identity row, or an empty array when it is missing.
                $identity = $identityStmt->fetch() ?: [];
                // Only rewrites the identity when the row was found.
                if ($identity) {
                    // Works out the program code from the stored program value.
                    $programCode = programCodeForStudent(['program' => $identity['program']]);
                    // Prepares the identity update.
                    $pdo->prepare(
                        // Rewrites the name, age, gender, program, track and adviser of the student.
                        'UPDATE students
                         SET first_name = :first_name,
                             last_name = :last_name,
                             middle_initial = :middle_initial,
                             age = :age,
                             gender = :gender,
                             program = :program,
                             track = :track,
                             adviser_name = :adviser_name
                         WHERE user_id = :user_id'
                    // Runs the identity update with the normalised values.
                    )->execute([
                        // Cleaned up first name.
                        'first_name' => formatPersonName($identity['first_name']),
                        // Cleaned up last name.
                        'last_name' => formatPersonName($identity['last_name']),
                        // Middle initial exactly as stored.
                        'middle_initial' => $identity['middle_initial'],
                        // Age cast to an integer.
                        'age' => (int) $identity['age'],
                        // Gender exactly as stored.
                        'gender' => $identity['gender'],
                        // Program code worked out above.
                        'program' => $programCode,
                        // Sets the track column to the student's current track so it stays unchanged.
                        'track' => $identity['track'],
                        // Saves the adviser name the student typed into students.adviser_name.
                        'adviser_name' => $adviser,
                        // Binds the logged-in user id for the WHERE user_id clause.
                        'user_id' => $userId,
                    // Closes the execute parameter array for the students UPDATE.
                    ]);
                    // Prepares an UPDATE that refreshes full_name on the users row.
                    $pdo->prepare('UPDATE users SET full_name = :full_name WHERE user_id = :user_id')
                        // Opens the parameter array for the users UPDATE.
                        ->execute([
                            // Builds the canonical full name from first, last and middle initial.
                            'full_name' => canonicalStudentName($identity['first_name'], $identity['last_name'], $identity['middle_initial']),
                            // Binds the user id that the UPDATE targets.
                            'user_id' => $userId,
                        // Closes the parameter array and runs the users UPDATE.
                        ]);
                // Closes the if ($identity) block that refreshed the student names.
                }
            // Closes the if ($hasDetails) block that saved the application details.
            }
            // Commits the transaction so every database change is kept.
            $pdo->commit();
            // Flags that the application part was saved, for error messages later.
            $applicationSaved = true;

            // Runs only when the student submitted application details.
            if ($hasDetails) {
                // Reloads the student identity from the database after the updates.
                $identity = databaseStudentIdentity();
                // Runs only when the student identity row was found.
                if ($identity) {
                    // Reads the current session user array, or an empty array.
                    $sessionUser = $_SESSION['user'] ?? [];
                    // Refreshes the session full name with the saved database value.
                    $sessionUser['full_name'] = $identity['full_name'];
                    // Refreshes the session email with the saved database value.
                    $sessionUser['email'] = $identity['email'];
                    // Stores the refreshed user array back in the session.
                    $_SESSION['user'] = $sessionUser;
                    // Updates the session student copy with the new adviser and title.
                    upsertSessionStudent($identity, ['adviser' => $adviser, 'title' => $title]);
                    // Starts merging the fresh identity values into $mockStudent.
                    $mockStudent = array_merge($mockStudent, [
                        // Sets the student name shown on the page from the database.
                        'name' => $identity['full_name'],
                        // Sets the student email shown on the page.
                        'email' => $identity['email'],
                        // Sets the program code from the database.
                        'program' => $identity['program'],
                        // Sets the readable program name.
                        'program_name' => $identity['program_name'],
                        // Sets the track from the database.
                        'track' => $identity['track'],
                        // Sets the adviser name for the session copy.
                        'adviser' => $adviser,
                        // Sets the paper title for the session copy.
                        'title' => $title,
                    // Closes the array_merge call for $mockStudent.
                    ]);
                // Closes the if ($identity) block for the session refresh.
                }

                // Removes the placeholder session record so the real one is used.
                csiteDropPlaceholderSessionRecord($mockStudent['email'], $stageKey);
                // Adds the submitted application to the session record list.
                addApplicationRecord([
                    // Passes the student email as the record key.
                    'studentEmail' => $mockStudent['email'],
                    // Passes the student name.
                    'student' => $mockStudent['name'],
                    // Passes the program code.
                    'program' => $mockStudent['program'],
                    // Passes the stage key of the submission.
                    'stageKey' => $stageKey,
                    // Passes the paper title.
                    'title' => $title,
                    // Passes the adviser name.
                    'adviser' => $adviser,
                    // Passes the abstract text.
                    'abstract' => $abstract,
                // Closes the addApplicationRecord call.
                ]);

                // Runs only when the enrollment date parsed correctly.
                if ($enrollDate !== null) {
                    // Saves the enrollment date for the student in the database.
                    csiteSaveEnrollDate($pdo, $userId, $enrollDate);
                // Closes the if ($enrollDate !== null) block.
                }
            // Closes the if ($hasDetails) block.
            }

            // Runs only when the student attached a file.
            if ($hasFile) {
                // Saves the uploaded file and its database row for the stage.
                csiteSaveUpload($file, $docType, $stageKey, $stages, $mockStudent['email'], $userId);
                // Flags that the document was saved successfully.
                $documentSaved = true;
            // Closes the if ($hasFile) block.
            }

            // Comment noting that the receipt details are stored with the document.
            // The receipt details the student entered are saved with the
            // Comment noting the coordinator sees the receipt in the same stage.
            // document, so the coordinator sees them in the same stage.
            // Runs only when the chosen document type is the official receipt.
            if ($isReceipt) {
                // Looks up the saved application id for this student and stage.
                $receiptAppId = databaseApplicationIdForStudent($userId, $stages[$stageKey]['label'] ?? '');
                // Runs when no database application id was found.
                if ($receiptAppId === null || $receiptAppId <= 0) {
                    // Falls back to the session application record for the stage.
                    $receiptApplication = applicationForStage($mockStudent['email'], $stageKey);
                    // Casts the fallback id to an integer, or 0 when missing.
                    $receiptAppId = (int) ($receiptApplication['id'] ?? 0);
                // Closes the if that found no database application id.
                }
                // Runs only when a valid application id is available.
                if ($receiptAppId > 0) {
                    // Saves the receipt number, date and amount for the application.
                    saveApplicationReceipt(
                        // Passes the application id.
                        $receiptAppId,
                        // Passes the receipt number typed by the student.
                        $receiptNumber,
                        // Converts the payment date to a database date string.
                        (string) csiteParseDate($paymentDate),
                        // Passes the amount paid.
                        $paymentAmount,
                        // Passes the student user id.
                        $userId
                    // Closes the saveApplicationReceipt call.
                    );
                // Closes the if ($receiptAppId > 0) block.
                }
            // Closes the if ($isReceipt) block.
            }

            // Comment noting the draft is no longer needed after saving.
            // Everything is saved: the draft is no longer needed.
            // Deletes the saved draft because the submission succeeded.
            clearApplicationDraft($userId);
            // Clears $draft so the draft banner shows the plain Draft label.
            $draft = null;

            // Comment introducing the friendly success notifications.
            // Short, friendly notifications in the style of the Student
            // Comment referring to the Student Dashboard Milestone Tracker style.
            // Dashboard's Milestone Tracker.
            // Runs when both details and a document were submitted.
            if ($hasDetails && $documentSaved) {
                // Starts the success popup data for this case.
                $popup = [
                    // Sets the popup title.
                    'title'   => 'Application submitted successfully.',
                    // Sets the popup message with the uploaded file name.
                    'message' => 'Your application and "' . $file['name'] . '" were sent to the coordinator for review.',
                // Closes the popup array.
                ];
            // Runs when only the application details were submitted.
            } elseif ($hasDetails) {
                // Starts the success popup for details only.
                $popup = [
                    // Sets the popup title.
                    'title'   => 'Application submitted successfully.',
                    // Sets the message without a file name.
                    'message' => 'Your application was sent to the coordinator for review.',
                // Closes the popup array.
                ];
            // Runs when only a document was uploaded.
            } else {
                // Starts the success popup for the document only.
                $popup = [
                    // Sets the popup title.
                    'title'   => 'Document uploaded successfully.',
                    // Sets the message with the uploaded file name.
                    'message' => '"' . $file['name'] . '" was sent to the coordinator for review.',
                // Closes the popup array.
                ];
            // Closes the if / elseif / else popup chain.
            }
        // Catches any error thrown while saving the submission.
        } catch (Throwable $e) {
            // Checks whether a database transaction is still open.
            if (isset($pdo) && $pdo->inTransaction()) {
                // Rolls back the unfinished transaction.
                $pdo->rollBack();
            // Closes the transaction check.
            }
            // Comment saying the typed values are kept so nothing is lost.
            // Keep what the student typed so nothing is lost.
            // Saves what the student typed as a draft so nothing is lost.
            saveApplicationDraft($userId, [
                // Passes the paper title to the draft.
                'title'          => $title,
                // Passes the adviser name to the draft.
                'adviser'        => $adviser,
                // Passes the abstract text to the draft.
                'abstract'       => $abstract,
                // Passes the enrollment date from the form values.
                'enroll_date'    => $formValues['enroll_date'],
                // Passes the current stage key.
                'stage_key'      => $stageKey,
                // Passes the chosen document type.
                'doc_type'       => $docType,
                // Passes the file name, or empty text when no file was picked.
                'file_name'      => $hasFile ? (string) $file['name'] : '',
                // Passes the receipt number.
                'receipt_number' => $receiptNumber,
                // Passes the payment amount.
                'payment_amount' => $paymentAmount,
                // Passes the payment date.
                'payment_date'   => $paymentDate,
            // Closes the draft array.
            ]);
            // Starts the error message, noting when the application part was saved.
            $appError = $applicationSaved
                // Adds the upload error text to the message.
                ? 'Your application was saved, but the document could not be uploaded: ' . $e->getMessage()
                // Uses the raw error when the application was not saved at all.
                : $e->getMessage();
        // Closes the catch block.
        }
    // Closes the else block that handled the real save.
    }

    // Runs when the submission finished with no error.
    if ($appError === '') {
        // Comment noting the form returns to the saved application.
        // The submission went through; the form is back to the saved application.
        // Reads the saved application again for the current stage.
        $existingApplication = applicationForStage($mockStudent['email'], $formValues['stage']);
        // Runs only when the saved application exists.
        if ($existingApplication) {
            // Keeps the saved title in the form instead of clearing it.
            $formValues['title'] = (string) ($existingApplication['title'] ?? $formValues['title']);
        // Closes the if ($existingApplication) block.
        }
        // Clears the abstract box after a successful submit.
        $formValues['abstract'] = '';
        // Clears the chosen document type.
        $formValues['docType'] = '';
        // Clears the draft file name note.
        $draftFileName = '';
    // Closes the if ($appError === '') block.
    }
// Closes the POST application handler.
}

/* ------------------------------------------------------------------
 * View data
 * ------------------------------------------------------------------ */
// Starts the map of document types for every stage.
$docTypesByStage = [];
// Loops through each stage of the student's track.
foreach ($stages as $key => $stage) {
    // Stores the document type options for this stage.
    $docTypesByStage[$key] = documentTypesForStage($stage, $track, $key);
// Closes the foreach over the stages.
}

// Gets the document labels required for the current stage.
$requiredDocs = requiredDocumentsForStage($track, $formValues['stage']);
// Reads the definition array of the current stage, or empty.
$stageDefinition = $stages[$formValues['stage']] ?? [];
// Starts computing the requirements still missing for this stage.
$missingRequirements = applicationSubmissionRequirements(
    // Passes the current stage definition.
    $stageDefinition,
    // Passes the student's track.
    $track,
    // Passes the current stage key.
    $formValues['stage'],
    // Starts the array of values the student filled in.
    [
        // Passes the paper title.
        'title'       => $formValues['title'],
        // Passes the adviser name.
        'adviser'     => $formValues['adviser'],
        // Passes the abstract text.
        'abstract'    => $formValues['abstract'],
        // Passes the enrollment date.
        'enroll_date' => $formValues['enroll_date'],
    // Closes the form values array.
    ],
    // Keeps only the uploads that belong to the current stage.
    array_values(array_filter(uploadsForEmail($mockStudent['email']), static function ($u) use ($formValues) {
        // Callback compares the upload stage label with the form stage.
        return stageKeyFromLabel((string) ($u['stage'] ?? '')) === $formValues['stage'];
    // Closes the filter callback and array_values call.
    }))
// Closes the applicationSubmissionRequirements call.
);

// The documents still missing for every stage of the track, so the browser
// check matches the stage the student picked.
// Starts the map of missing documents for every stage.
$missingDocumentsByStage = [];
// Loops through each stage to collect its missing documents.
foreach ($stages as $key => $stage) {
    // Collects the uploads that belong to this stage.
    $stageUploads = array_values(array_filter(uploadsForEmail($mockStudent['email']), static function ($u) use ($key) {
        // Callback compares the upload stage label with this stage key.
        return stageKeyFromLabel((string) ($u['stage'] ?? '')) === $key;
    // Closes the filter callback call.
    }));
    // Computes missing requirements with dummy field values.
    $missingDocumentsByStage[$key] = applicationSubmissionRequirements(
        // Passes this stage definition.
        $stage,
        // Passes the track.
        $track,
        // Passes the stage key.
        $key,
        // Uses x placeholders so only document gaps count.
        ['title' => 'x', 'adviser' => 'x', 'abstract' => 'x', 'enroll_date' => 'x'],
        // Passes the uploads collected for this stage.
        $stageUploads
    // Keeps only the documents part of the result.
    )['documents'];
// Closes the foreach over the stages.
}

/* ------------------------------------------------------------------
 * Where the student stands, in the compact style of the Milestone
 * Tracker on the Student Dashboard.
 * ------------------------------------------------------------------ */
// Gets the milestone steps of the current stage.
$stageMilestones = stageMilestoneSteps($mockStudent['email'], $track, $formValues['stage']);
// Starts the list of requirement-type milestones.
$requirementMilestones = [];
// Loops through all milestone steps.
foreach ($stageMilestones as $step) {
    // Keeps only paper, endorsement, review, payment and ready steps.
    if (in_array($step['key'] ?? '', ['paper', 'adviser_endorsement', 'coord_review', 'grad_school_endorsement', 'payment', 'ready_for_presentation'], true)) {
        // Adds this step to the requirement list.
        $requirementMilestones[] = $step;
    // Closes the if that filters the step key.
    }
// Closes the foreach over the milestones.
}
// Starts complete unless the requirement list is empty.
$requirementsComplete = $requirementMilestones !== [];
// Loops through the requirement milestones.
foreach ($requirementMilestones as $step) {
    // Checks whether this milestone is still not done.
    if (($step['state']['status'] ?? 'pending') !== 'done') {
        // Marks the requirements as incomplete.
        $requirementsComplete = false;
        // Stops checking at the first unfinished milestone.
        break;
    // Closes the if that found an unfinished step.
    }
// Closes the foreach over requirement milestones.
}
// Checks that the first stage document is no longer missing.
$paperAttached = !in_array($stageDefinition['documents'][0]['label'] ?? '', $missingRequirements['documents'], true)
    // Also requires that the stage really has a first document label.
    && !empty($stageDefinition['documents'][0]['label'] ?? '');
// True when the student already has an application for this stage.
$applicationSent = $existingApplication !== null;

/*
 * A document the coordinator sent back has to be dealt with first, so the
 * student is told what is wrong before anything else.
 */
// Starts with no flagged milestone found.
$flaggedMilestone = null;
// Loops through the stage milestones.
foreach ($stageMilestones as $step) {
    // Checks whether this milestone was flagged by the coordinator.
    if (($step['state']['status'] ?? '') === 'flagged') {
        // Remembers the first flagged milestone.
        $flaggedMilestone = $step;
        // Stops looking after the first flag.
        break;
    // Closes the if that found a flagged step.
    }
// Closes the foreach over milestones.
}

// Runs when a flagged milestone needs the student's attention.
if ($flaggedMilestone) {
    // Uses the flagged style for the status tag.
    $statusTag = 'flagged';
    // Shows Action needed as the status text.
    $statusText = 'Action needed';
    // Shows the coordinator's note, or a default instruction.
    $statusNote = (string) ($flaggedMilestone['detail'] ?? 'A document has to be corrected.');
// Runs when the application is sent and all requirements are done.
} elseif ($applicationSent && $requirementsComplete) {
    // Uses the done style for the tag.
    $statusTag = 'done';
    // Shows that the requirements are complete.
    $statusText = 'Requirements completed.';
    // Tells the student they are waiting for coordinator approval.
    $statusNote = 'Waiting for coordinator approval.';
// Runs when the application was sent but not all requirements are done.
} elseif ($applicationSent) {
    // Uses the done style for the tag.
    $statusTag = 'done';
    // Shows that the document was submitted for review.
    $statusText = 'Document submitted for review.';
    // Tells the student the documents are with the coordinator.
    $statusNote = 'Your documents are with the coordinator.';
// Runs when the paper is already attached.
} elseif ($paperAttached) {
    // Uses the active style for the tag.
    $statusTag = 'active';
    // Shows that the form is ready to submit.
    $statusText = 'Ready to submit.';
    // Tells the student to submit when ready.
    $statusNote = 'Your paper is attached. Submit your application when you are ready.';
// Runs when nothing has been attached yet.
} else {
    // Uses the flagged style to draw attention.
    $statusTag = 'flagged';
    // Shows Action needed.
    $statusText = 'Action needed';
    // Tells the student to attach the paper first.
    $statusNote = 'Attach your paper to submit your application.';
// Closes the status if / elseif / else chain.
}

// Loads the shared page header and navigation.
require_once __DIR__ . '/../includes/layout/header.php';
// Closes PHP mode and starts the HTML output.
?>

<?php /* Starts the page banner header block. */ ?>
<div class="page-header page-banner">
<?php /*     Shows the track name followed by Presentation Application. */ ?>
    <h2><?= htmlspecialchars($trackLabel) ?> Presentation Application</h2>
<?php /*     Short instruction about filling the form and attaching documents. */ ?>
    <p>Fill in your application, attach your documents and send everything in one go.</p>
<?php /* Closes the page banner div. */ ?>
</div>

<?php /* Track indicator box with a class for the student's track. */ ?>
<div class="track-indicator track-<?= htmlspecialchars($track) ?>">
<?php /*     Badge showing the track name. */ ?>
    <span class="track-indicator-badge"><?= htmlspecialchars($trackLabel) ?> Track</span>
<?php /*     Shows the program name of the student. */ ?>
    <strong class="track-indicator-program"><?= htmlspecialchars($mockStudent['program_name']) ?></strong>
<?php /* Closes the track indicator div. */ ?>
</div>

<?php /* Runs only when there is an error message to show. */ ?>
<?php if ($appError): ?>
<?php /* Red alert box with the escaped error message. */ ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($appError) ?></div>
<?php /* Ends the error message condition. */ ?>
<?php endif; ?>

<?php /* Draft notice box above the application form. */ ?>
<div class="attach-status draft-notice" style="margin-bottom:1rem;">
<?php /*     Shows Draft saved when a draft exists, otherwise Draft. */ ?>
    <span class="ms-step-tag done"><?= $draft ? 'Draft saved' : 'Draft' ?></span>
<?php /*     Starts the note text span. */ ?>
    <span class="attach-status-note">
<?php /*         Runs when a draft was already saved. */ ?>
        <?php if ($draft): ?>
<?php /*             Tells the student their entries are kept. */ ?>
            Your entries are kept on this page — you can leave and come back to them.
<?php /*         Runs when there is no saved draft yet. */ ?>
        <?php else: ?>
<?php /*             Explains that entries save as the student types. */ ?>
            Your entries are saved as you type, so you can leave this page and come back to them.
<?php /*         Ends the draft text condition. */ ?>
        <?php endif; ?>
<?php /*     Closes the note span. */ ?>
    </span>
<?php /*     Button that deletes the saved draft values. */ ?>
    <button type="button" class="btn btn-sm btn-outline" id="discardDraftBtn"><i class="fas fa-trash"></i> Discard Draft</button>
<?php /* Closes the draft notice box. */ ?>
</div>

<?php /* Starts the application card container. */ ?>
<div class="card" id="applicationFormCard">
<?php /*     Card header with the form title and icon. */ ?>
    <div class="card-header"><h3><i class="fas fa-file-alt"></i> Application &amp; Document Upload</h3></div>
<?php /*     Starts the card body that holds the form. */ ?>
    <div class="card-body">
<?php /*         Form posts to application.php as multipart for the file upload. */ ?>
        <form class="form-card" id="applicationForm" style="box-shadow:none;border:none;padding:0;" method="post" action="<?= url('student/application.php') ?>" enctype="multipart/form-data" data-validate>
<?php /*             Hidden field that marks this POST as an application submission. */ ?>
            <input type="hidden" name="form" value="application">
<?php /*             Row holding the full name and email fields. */ ?>
            <div class="form-row">
<?php /*                 Field wrapper for the full name. */ ?>
                <div class="form-field">
<?php /*                     Label for the full name. */ ?>
                    <label>Full Name</label>
<?php /*                     Read-only full name taken from the student record. */ ?>
                    <input type="text" value="<?= htmlspecialchars($mockStudent['name']) ?>" readonly>
<?php /*                 Closes the full name field. */ ?>
                </div>
<?php /*                 Field wrapper for the email. */ ?>
                <div class="form-field">
<?php /*                     Label for the ADZU email. */ ?>
                    <label>ADZU Email</label>
<?php /*                     Read-only email taken from the student record. */ ?>
                    <input type="email" value="<?= htmlspecialchars($mockStudent['email']) ?>" readonly>
<?php /*                 Closes the email field. */ ?>
                </div>
<?php /*             Closes the name and email row. */ ?>
            </div>
<?php /*             Row holding the program and stage fields. */ ?>
            <div class="form-row">
<?php /*                 Field wrapper for the program. */ ?>
                <div class="form-field">
<?php /*                     Label for the program. */ ?>
                    <label>Program</label>
<?php /*                     Read-only program name. */ ?>
                    <input type="text" value="<?= htmlspecialchars($mockStudent['program_name']) ?>" readonly>
<?php /*                 Closes the program field. */ ?>
                </div>
<?php /*                 Field wrapper for the presentation stage. */ ?>
                <div class="form-field">
<?php /*                     Label for the presentation stage. */ ?>
                    <label>Presentation Stage</label>
<?php /*                     Read-only stage label from the student's milestone stage. */ ?>
                    <input type="text" value="<?= htmlspecialchars($stages[$formValues['stage']]['label'] ?? '') ?>" readonly aria-readonly="true">
<?php /*                     Hidden input that sends the stage key with the form. */ ?>
                    <input type="hidden" name="stage" value="<?= htmlspecialchars($formValues['stage']) ?>">
<?php /*                     Hint explaining that the stage comes from the Milestone Tracker. */ ?>
                    <p class="field-hint">Set automatically from your Milestone Tracker. It moves to the next stage when you complete the milestones of this one.</p>
<?php /*                 Closes the stage field. */ ?>
                </div>
<?php /*             Closes the program and stage row. */ ?>
            </div>
<?php /*             Field wrapper for the research title. */ ?>
            <div class="form-field">
<?php /*                 Label marking the title as required. */ ?>
                <label>Research / Paper Title <span class="required">*</span></label>
<?php /*                 Text input for the paper title, prefilled from the form values. */ ?>
                <input type="text" id="applicationTitle" name="title" value="<?= htmlspecialchars($formValues['title']) ?>" placeholder="Enter your <?= strtolower($trackLabel) ?> title">
<?php /*             Closes the title field. */ ?>
            </div>
<?php /*             Row holding the adviser and enrollment date. */ ?>
            <div class="form-row">
<?php /*                 Field wrapper for the adviser name. */ ?>
                <div class="form-field">
<?php /*                     Label marking the adviser name as required. */ ?>
                    <label>Adviser Name <span class="required">*</span></label>
<?php /*                     Adviser choices come from the available adviser pool. */ ?>
                    <select name="adviser" required <?= $adviserOpts ? '' : 'disabled' ?>>
                        <option value="">Select an available adviser</option>
                        <?php foreach ($adviserOpts as $adviserName): ?>
                            <option value="<?= htmlspecialchars($adviserName, ENT_QUOTES, 'UTF-8') ?>" <?= strcasecmp(trim($formValues['adviser']), $adviserName) === 0 ? 'selected' : '' ?>><?= htmlspecialchars($adviserName, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                        <?php if (!$adviserOpts): ?>
                            <option value="" disabled selected><?= $adviserPoolError !== '' ? 'Adviser pool unavailable' : 'No available advisers in the pool' ?></option>
                        <?php endif; ?>
                    </select>
                    <p class="field-hint">Choose an available adviser from the current adviser pool.</p>
<?php /*                 Closes the adviser field. */ ?>
                </div>
<?php /*                 Field wrapper for the enrollment date. */ ?>
                <div class="form-field">
<?php /*                     Label marking the enrollment date as required. */ ?>
                    <label>Enrollment / Start Date <span class="required">*</span></label>
<?php /*                     Renders the shared date field for enroll_date. */ ?>
                    <?= csiteDateField('enroll_date', $formValues['enroll_date']) ?>
<?php /*                 Closes the enrollment date field. */ ?>
                </div>
<?php /*             Closes the adviser and date row. */ ?>
            </div>
<?php /*             Shows the abstract field only on the final stage. */ ?>
            <?php if ($showAbstract): ?>
<?php /*             Field wrapper for the abstract. */ ?>
            <div class="form-field">
<?php /*                 Label marking the abstract as required. */ ?>
                <label>Brief Description / Abstract <span class="required">*</span></label>
<?php /*                 Textarea holding the brief description text. */ ?>
                <textarea name="abstract" placeholder="Provide a brief description of your research or project..."><?= htmlspecialchars($formValues['abstract']) ?></textarea>
<?php /*             Closes the abstract field. */ ?>
            </div>
<?php /*             Ends the abstract condition. */ ?>
            <?php endif; ?>

<?php /*             Starts the upload section with a top divider. */ ?>
            <div style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--gray-100);">
<?php /*                 Heading style for the Attach Document title. */ ?>
                <h4 style="font-size:0.9rem;font-weight:700;color:var(--adzu-navy);margin-bottom:0.75rem;">
<?php /*                     Upload icon and the Attach Document heading text. */ ?>
                    <i class="fas fa-cloud-upload-alt" style="margin-right:6px;"></i>Attach Document
<?php /*                 Closes the upload heading. */ ?>
                </h4>
<?php /*                 Paragraph that shows the current document status. */ ?>
                <p class="attach-status">
<?php /*                     Status tag with the computed class and text. */ ?>
                    <span class="ms-step-tag <?= $statusTag ?>"><?= htmlspecialchars($statusText) ?></span>
<?php /*                     Explains the status in a short sentence. */ ?>
                    <span class="attach-status-note"><?= htmlspecialchars($statusNote) ?></span>
<?php /*                 Closes the status paragraph. */ ?>
                </p>
<?php /*                 Row for the document type field. */ ?>
                <div class="form-row">
<?php /*                     Field wrapper for the document type. */ ?>
                    <div class="form-field">
<?php /*                         Label marking the document type as required. */ ?>
                        <label>Document Type <span class="required">*</span></label>
<?php /*                         Select whose options are filled by JavaScript. */ ?>
                        <select name="docType" id="applicationDocType">
<?php /*                             Default empty choice in the document type list. */ ?>
                            <option value="">Select document type</option>
<?php /*                         Closes the document type select. */ ?>
                        </select>
<?php /*                         Hint that the type should match the current stage. */ ?>
                        <p class="field-hint" id="documentTypeHint">Pick the document that matches the stage you are applying for.</p>
<?php /*                     Closes the document type field. */ ?>
                    </div>
<?php /*                 Closes the document type row. */ ?>
                </div>
<?php /*                 Field wrapper for the file upload. */ ?>
                <div class="form-field">
<?php /*                     Label for the file input. */ ?>
                    <label>File</label>
<?php /*                     Styles the drag and drop upload area. */ ?>
                    <div class="file-upload-area">
<?php /*                         Cloud upload icon. */ ?>
                        <i class="fas fa-cloud-upload-alt"></i>
<?php /*                         Tells the student they can drag or click to browse. */ ?>
                        <p>Drag and drop your file here, or click to browse</p>
<?php /*                         Lists the accepted formats and the 20 MB limit. */ ?>
                        <p style="font-size:0.75rem;margin-top:0.25rem;">PDF, DOC, or DOCX — Max 20 MB</p>
<?php /*                         Placeholder where the chosen file name appears. */ ?>
                        <div class="file-name"></div>
<?php /*                         File input named document, accepting PDF and Word files. */ ?>
                        <input type="file" name="document" accept=".pdf,.docx,.doc">
<?php /*                     Closes the upload area. */ ?>
                    </div>
<?php /*                     Shows the draft file note only when a file name was saved. */ ?>
                    <?php if ($draftFileName !== ''): ?>
<?php /*                     Hint paragraph about the draft file. */ ?>
                    <p class="field-hint" id="draftFileNote">
<?php /*                         Paperclip icon before the note. */ ?>
                        <i class="fas fa-paperclip" style="margin-right:5px;"></i>
<?php /*                         Shows the file name kept in the draft. */ ?>
                        Your draft keeps the file name <strong><?= htmlspecialchars($draftFileName) ?></strong>.
<?php /*                         Warns that the file must be picked again. */ ?>
                        Browsers cannot restore a selected file for you — pick it again to attach it.
<?php /*                     Closes the draft file note. */ ?>
                    </p>
<?php /*                     Ends the draft file note condition. */ ?>
                    <?php endif; ?>
<?php /*                 Closes the file upload field. */ ?>
                </div>

<?php /*                 Starts PHP to decide whether receipt fields are visible. */ ?>
                <?php
                // Comment noting payment details are only asked for the receipt.
                // The details of the payment are only asked for when the
                // Comment noting the receipt fields follow the document type.
                // document is the official receipt.
                // Shows receipt fields only when the chosen type is a receipt.
                $showReceiptFields = ($formValues['docType'] !== '' && classifyUploadDocType($formValues['docType']) === 'receipt');
                // Ends the PHP block and returns to HTML.
                ?>
<?php /*                 Receipt fields row, hidden unless the type is a receipt. */ ?>
                <div class="form-row" id="receiptFields"<?= $showReceiptFields ? '' : ' hidden' ?>>
<?php /*                     Field wrapper for the receipt number. */ ?>
                    <div class="form-field">
<?php /*                         Label marking the receipt number as required. */ ?>
                        <label>Receipt Number <span class="required">*</span></label>
<?php /*                         Text input for the receipt number. */ ?>
                        <input type="text" name="receiptNumber" id="receiptNumber"
<?php /*                                Prefills the receipt number from the form values. */ ?>
                               value="<?= htmlspecialchars($formValues['receiptNumber']) ?>"
<?php /*                                Placeholder describing the receipt number. */ ?>
                               placeholder="Number printed on your official receipt">
<?php /*                     Closes the receipt number field. */ ?>
                    </div>
<?php /*                     Field wrapper for the amount paid. */ ?>
                    <div class="form-field">
<?php /*                         Label marking the amount as required. */ ?>
                        <label>Amount <span class="required">*</span></label>
<?php /*                         Text input for the amount with a decimal keyboard hint. */ ?>
                        <input type="text" name="paymentAmount" id="paymentAmount" inputmode="decimal"
<?php /*                                Prefills the amount from the form values. */ ?>
                               value="<?= htmlspecialchars($formValues['paymentAmount']) ?>"
<?php /*                                Placeholder showing an amount example. */ ?>
                               placeholder="e.g. 1500.00">
<?php /*                     Closes the amount field. */ ?>
                    </div>
<?php /*                     Field wrapper for the payment date. */ ?>
                    <div class="form-field">
<?php /*                         Label marking the payment date as required. */ ?>
                        <label>Payment Date <span class="required">*</span></label>
<?php /*                         Date input for the day the fee was paid. */ ?>
                        <input type="date" name="paymentDate" id="paymentDate"
<?php /*                                Prefills the payment date from the form values. */ ?>
                               value="<?= htmlspecialchars($formValues['paymentDate']) ?>">
<?php /*                     Closes the payment date field. */ ?>
                    </div>
<?php /*                 Closes the receipt fields row. */ ?>
                </div>
<?php /*                 Receipt hint paragraph, hidden unless the type is a receipt. */ ?>
                <p class="field-hint" id="receiptHint"<?= $showReceiptFields ? '' : ' hidden' ?>>
<?php /*                     Receipt icon before the hint. */ ?>
                    <i class="fas fa-receipt" style="margin-right:5px;"></i>
<?php /*                     Explains that the coordinator sees the receipt details. */ ?>
                    Enter the details printed on your official receipt. The coordinator sees them with the document.
<?php /*                 Closes the receipt hint. */ ?>
                </p>
<?php /*             Closes the upload section div. */ ?>
            </div>

<?php /*             Action bar holding the submit, new and cancel buttons. */ ?>
            <div class="form-actions" style="border-top:1px solid var(--gray-100);padding-top:1rem;margin-top:1rem;">
<?php /*                 Submit button that sends the application form. */ ?>
                <button type="submit" class="btn btn-primary" id="submitApplicationBtn"><i class="fas fa-paper-plane"></i> Submit Application</button>
<?php /*                 Button that clears the form for another application. */ ?>
                <button type="button" class="btn btn-outline" id="submitAnotherBtn"><i class="fas fa-plus"></i> Submit Another Application</button>
<?php /*                 Cancel link back to the student dashboard. */ ?>
                <a href="<?= url('student/dashboard.php') ?>" class="btn btn-outline">Cancel</a>
<?php /*             Closes the action bar. */ ?>
            </div>
<?php /*         Closes the application form. */ ?>
        </form>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the application card. */ ?>
</div>

<?php /* Shows the popup data only when a popup message was set. */ ?>
<?php if ($popup): ?>
<?php /* Hidden element that carries the popup text for JavaScript. */ ?>
<div id="successPopupData" hidden
<?php /*      Data attribute holding the popup title. */ ?>
     data-title="<?= htmlspecialchars($popup['title']) ?>"
<?php /*      Data attribute holding the popup message. */ ?>
     data-message="<?= htmlspecialchars($popup['message']) ?>"></div>
<?php /* Ends the popup data condition. */ ?>
<?php endif; ?>

<?php /* Starts the JavaScript block for the application page. */ ?>
<script>
<?php /* Starts an isolated function so variables stay local. */ ?>
(function () {
<?php /*     Passes the document types per stage to JavaScript. */ ?>
    const types = <?= json_encode($docTypesByStage) ?>;
<?php /*     Passes the open types per stage to JavaScript. */ ?>
    const openTypes = <?= json_encode($openTypesByStage) ?>;
<?php /*     Passes the missing documents per stage to JavaScript. */ ?>
    const missingDocuments = <?= json_encode($missingDocumentsByStage) ?>;
<?php /*     Finds the application form element. */ ?>
    const form = document.getElementById('applicationForm');
<?php /*     Comment noting the stage is fixed to the current milestone. */ ?>
    // The stage is fixed to the student's current milestone stage (the field
<?php /*     Comment noting the document types are filled once for that stage. */ ?>
    // is read-only), so the document types are filled once for that stage.
<?php /*     Stores the current stage key for the script. */ ?>
    const currentStageKey = <?= json_encode($formValues['stage']) ?>;
<?php /*     Finds the document type select element. */ ?>
    const typeSelect = document.getElementById('applicationDocType');
<?php /*     Finds the file input inside the form. */ ?>
    const fileInput = form.querySelector('input[type="file"]');
<?php /*     Finds the submit application button. */ ?>
    const submitBtn = document.getElementById('submitApplicationBtn');
<?php /*     Tells the script whether an application already exists. */ ?>
    const hasExistingApplication = <?= $existingApplication ? 'true' : 'false' ?>;
<?php /*     Finds the document type hint paragraph. */ ?>
    const typeHint = document.getElementById('documentTypeHint');

<?php /*     Comment about the document types allowed for the stage. */ ?>
    // The document types that may be picked for the chosen stage. The Milestone
<?php /*     Comment noting the Milestone Tracker decides the allowed type. */ ?>
    // Tracker decides: the type of the milestone the student is working on.
<?php /*     Defines a helper that returns the open types of a stage. */ ?>
    function openTypesFor(stageKey) {
<?php /*         Returns the open type list, or an empty list. */ ?>
        return openTypes[stageKey] || [];
<?php /*     Closes the openTypesFor function. */ ?>
    }

<?php /*     Comment about keeping the document type list in sync. */ ?>
    // Keeps the "Document Type" list in sync with the chosen stage. A type
<?php /*     Comment noting locked types are shown but cannot be picked. */ ?>
    // that the workflow does not ask for yet is shown, but it stays locked.
<?php /*     Defines the function that rebuilds the document type list. */ ?>
    function fillDocTypes(keep) {
<?php /*         Gets every document type of the current stage. */ ?>
        const opts = types[currentStageKey] || [];
<?php /*         Gets the types that are open for the current stage. */ ?>
        const open = openTypesFor(currentStageKey);
<?php /*         Keeps the earlier choice, or falls back to the select value. */ ?>
        const previous = keep || typeSelect.value;
<?php /*         Resets the select with the placeholder option. */ ?>
        typeSelect.innerHTML = '<option value="">Select document type</option>'
<?php /*             Maps each document type to an option element. */ ?>
            + opts.map(function (o) {
<?php /*                 Checks whether this type is currently open. */ ?>
                const isOpen = open.indexOf(o) !== -1;
<?php /*                 Builds the option and disables it when the type is locked. */ ?>
                return '<option value="' + o + '"' + (isOpen ? '' : ' disabled')
<?php /*                     Adds the locked label to disabled options. */ ?>
                    + '>' + o + (isOpen ? '' : ' — locked') + '</option>';
<?php /*             Joins the option strings into the select. */ ?>
            }).join('');
<?php /*         Restores the earlier choice when it is still open. */ ?>
        if (previous && open.indexOf(previous) !== -1) {
<?php /*             Selects the earlier document type. */ ?>
            typeSelect.value = previous;
<?php /*         Closes the restore choice condition. */ ?>
        }
<?php /*         Runs when the hint paragraph exists. */ ?>
        if (typeHint) {
<?php /*             Chooses the hint text based on the open types. */ ?>
            typeHint.textContent = open.length
<?php /*                 Tells the student which document to send next. */ ?>
                ? 'Send the ' + open[0] + ' — it is the next step of your workflow.'
<?php /*                 Tells the student every document was already sent. */ ?>
                : 'Every document of this stage is with the coordinator already.';
<?php /*         Closes the hint text condition. */ ?>
        }
<?php /*     Closes the fillDocTypes function. */ ?>
    }
<?php /*     Fills the list once with the saved document type. */ ?>
    fillDocTypes('<?= htmlspecialchars($formValues['docType']) ?>');

<?php /*     Starts the comment block for the official receipt logic. */ ?>
    /* ------------------------------------------------------------------
<?php /*      Comment heading for the official receipt section. */ ?>
     * Official receipt
<?php /*      Comment explaining that payment details belong to the receipt. */ ?>
     *
<?php /*      Comment noting details are only asked for the receipt. */ ?>
     * The details of the payment are only asked for when the document is
<?php /*      Comment noting the fields show and hide with the type. */ ?>
     * the official receipt, so they are shown and hidden with the document
<?php /*      Comment noting the fields follow the chosen document type. */ ?>
     * type.
<?php /*      Ends the official receipt comment block. */ ?>
     * ------------------------------------------------------------------ */
<?php /*     Finds the receipt fields row. */ ?>
    const receiptFields = document.getElementById('receiptFields');
<?php /*     Finds the receipt hint paragraph. */ ?>
    const receiptHint = document.getElementById('receiptHint');
<?php /*     Stores the receipt document type name for comparison. */ ?>
    const receiptType = <?= json_encode('Official Receipt / Payment Proof') ?>;

<?php /*     Defines a helper that checks for a receipt type name. */ ?>
    function isReceiptType(value) {
<?php /*         Lowercases the value so the check ignores case. */ ?>
        const name = String(value || '').toLowerCase();
<?php /*         Returns true when the name mentions receipt or payment. */ ?>
        return name.indexOf('receipt') !== -1 || name.indexOf('payment') !== -1;
<?php /*     Closes the isReceiptType function. */ ?>
    }

<?php /*     Defines the function that shows or hides receipt fields. */ ?>
    function toggleReceiptFields() {
<?php /*         Checks whether the selected document type is a receipt. */ ?>
        const show = isReceiptType(typeSelect.value);
<?php /*         Shows the receipt row only for receipt documents. */ ?>
        if (receiptFields) receiptFields.hidden = !show;
<?php /*         Shows the receipt hint with the same rule. */ ?>
        if (receiptHint) receiptHint.hidden = !show;
<?php /*         Comment noting the fields are only required for receipts. */ ?>
        // The three fields are only required while the receipt is the
<?php /*         Comment noting the fields matter only for the receipt document. */ ?>
        // document that is being sent.
<?php /*         Loops over the three receipt input ids. */ ?>
        ['receiptNumber', 'paymentAmount', 'paymentDate'].forEach(function (id) {
<?php /*             Finds each receipt input by id. */ ?>
            const field = document.getElementById(id);
<?php /*             Makes the input required only while it is visible. */ ?>
            if (field) field.required = show;
<?php /*         Closes the forEach over receipt fields. */ ?>
        });
<?php /*     Closes the toggleReceiptFields function. */ ?>
    }
<?php /*     Re-runs the toggle when the document type changes. */ ?>
    typeSelect.addEventListener('change', toggleReceiptFields);
<?php /*     Runs the toggle once when the page loads. */ ?>
    toggleReceiptFields();

<?php /*     Starts the comment block for draft saving. */ ?>
    /* ------------------------------------------------------------------
<?php /*      Comment heading for the draft section. */ ?>
     * Draft
<?php /*      Comment noting the form saves itself in the background. */ ?>
     *
<?php /*      Comment noting the draft is also saved when leaving. */ ?>
     * The form saves itself in the background and when the student leaves
<?php /*      Comment noting nothing is lost when changing menus. */ ?>
     * the page, so nothing is lost when they move to another menu.
<?php /*      Ends the draft comment block. */ ?>
     * ------------------------------------------------------------------ */
<?php /*     Defines the function that builds the draft request body. */ ?>
    function draftPayload() {
<?php /*         Creates a new form encoded data object. */ ?>
        const data = new URLSearchParams();
<?php /*         Marks the request as a draft save. */ ?>
        data.append('form', 'draft');
<?php /*         Lists the field names that are saved in the draft. */ ?>
        ['title', 'adviser', 'abstract', 'enroll_date', 'stage', 'docType',
<?php /*          Loops over those field names. */ ?>
         'receiptNumber', 'paymentAmount', 'paymentDate'].forEach(function (name) {
<?php /*             Finds the field inside the form. */ ?>
            const field = form.querySelector('[name="' + name + '"]');
<?php /*             Adds the field value when the field exists. */ ?>
            if (field) data.append(name, field.value);
<?php /*         Closes the forEach over field names. */ ?>
        });
<?php /*         Adds the chosen file name, or empty text when none. */ ?>
        data.append('file_name', fileInput && fileInput.files && fileInput.files.length ? fileInput.files[0].name : '');
<?php /*         Returns the finished draft data. */ ?>
        return data;
<?php /*     Closes the draftPayload function. */ ?>
    }

<?php /*     Holds the timer id used to delay draft saves. */ ?>
    var draftTimer = null;
<?php /*     Flag that pauses draft saving when needed. */ ?>
    var draftDisabled = false;
<?php /*     Defines the function that posts the draft. */ ?>
    function saveDraft() {
<?php /*         Stops when draft saving is disabled. */ ?>
        if (draftDisabled) return;
<?php /*         Starts a try block so draft errors stay harmless. */ ?>
        try {
<?php /*             Posts the draft to the same page address. */ ?>
            fetch(window.location.pathname + window.location.search, {
<?php /*                 Uses the POST method. */ ?>
                method: 'POST',
<?php /*                 Sends the draft field values as the body. */ ?>
                body: draftPayload(),
<?php /*                 Marks the request as an AJAX request. */ ?>
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
<?php /*                 Sends the session cookie with the request. */ ?>
                credentials: 'same-origin',
<?php /*                 Lets the request finish even if the page unloads. */ ?>
                keepalive: true
<?php /*             Closes the fetch options object. */ ?>
            });
<?php /*         Ignores draft errors because a draft is only a convenience. */ ?>
        } catch (e) { /* the draft is a convenience, never a blocker */ }
<?php /*     Closes the saveDraft function. */ ?>
    }
<?php /*     Defines the function that delays the draft save. */ ?>
    function scheduleDraft() {
<?php /*         Stops when draft saving is disabled. */ ?>
        if (draftDisabled) return;
<?php /*         Cancels the pending timer. */ ?>
        clearTimeout(draftTimer);
<?php /*         Saves the draft 700 ms after the last change. */ ?>
        draftTimer = setTimeout(saveDraft, 700);
<?php /*     Closes the scheduleDraft function. */ ?>
    }

<?php /*     Schedules a draft save when the student types. */ ?>
    form.addEventListener('input', scheduleDraft);
<?php /*     Schedules a draft save when a field value changes. */ ?>
    form.addEventListener('change', scheduleDraft);

<?php /*     Comment noting a draft is saved when leaving the page. */ ?>
    // Leaving the page (another menu, a refresh, a close) saves the draft.
<?php /*     Runs just before the student leaves the page. */ ?>
    window.addEventListener('beforeunload', function () {
<?php /*         Stops when drafts are disabled or beacons are unsupported. */ ?>
        if (draftDisabled || !navigator.sendBeacon) return;
<?php /*         Sends the draft with a beacon that survives leaving. */ ?>
        navigator.sendBeacon(window.location.pathname + window.location.search, draftPayload());
<?php /*     Closes the beforeunload listener. */ ?>
    });

<?php /*     Starts the comment block for form validation. */ ?>
    /* ------------------------------------------------------------------
<?php /*      Comment heading for the validation section. */ ?>
     * Validation
<?php /*      Comment noting an application is sent only when complete. */ ?>
     *
<?php /*      Comment noting every required field must be filled. */ ?>
     * An application is only sent when it is complete: every required
<?php /*      Comment noting the stage documents must be attached. */ ?>
     * field is filled and the documents of the stage are attached.
<?php /*      Ends the validation comment block. */ ?>
     * ------------------------------------------------------------------ */
<?php /*     Defines the helper that shows an error under a field. */ ?>
    function fieldError(field, message) {
<?php /*         Finds the field group to attach the message to. */ ?>
        const group = field.closest('.form-field') || field.parentElement;
<?php /*         Stops when no group element was found. */ ?>
        if (!group) return;
<?php /*         Adds the error style class to the group. */ ?>
        group.classList.add('has-error');
<?php /*         Looks for an existing error message element. */ ?>
        let err = group.querySelector('.field-error');
<?php /*         Creates the error element only when it is missing. */ ?>
        if (!err) {
<?php /*             Creates a new div for the message. */ ?>
            err = document.createElement('div');
<?php /*             Gives it the field-error class. */ ?>
            err.className = 'field-error';
<?php /*             Adds the message element to the group. */ ?>
            group.appendChild(err);
<?php /*         Closes the create error element condition. */ ?>
        }
<?php /*         Writes the error text into the element. */ ?>
        err.textContent = message;
<?php /*     Closes the fieldError function. */ ?>
    }

<?php /*     Defines the function that removes all error messages. */ ?>
    function clearErrors() {
<?php /*         Removes the error style from every field group. */ ?>
        form.querySelectorAll('.has-error').forEach(function (g) { g.classList.remove('has-error'); });
<?php /*         Removes every error message element. */ ?>
        form.querySelectorAll('.field-error').forEach(function (e) { e.remove(); });
<?php /*     Closes the clearErrors function. */ ?>
    }

<?php /*     Runs when the student submits the form. */ ?>
    form.addEventListener('submit', function (e) {
<?php /*         Clears old error messages first. */ ?>
        clearErrors();

<?php /*         Finds the title input. */ ?>
        const title = form.querySelector('[name="title"]');
<?php /*         Finds the adviser input. */ ?>
        const adviser = form.querySelector('[name="adviser"]');
<?php /*         Finds the abstract textarea. */ ?>
        const abstract = form.querySelector('[name="abstract"]');
<?php /*         Finds the enrollment date field. */ ?>
        const enroll = form.querySelector('[name="enroll_date"]');
<?php /*         Starts an object with the trimmed field values. */ ?>
        const values = {
<?php /*             Reads the trimmed title. */ ?>
            title: title.value.trim(),
<?php /*             Reads the trimmed adviser name. */ ?>
            adviser: adviser.value.trim(),
<?php /*             Reads the abstract, or empty text when hidden. */ ?>
            abstract: abstract ? abstract.value.trim() : '',
<?php /*             Reads the enrollment date. */ ?>
            enroll_date: enroll.value.trim()
<?php /*         Closes the values object. */ ?>
        };
<?php /*         True when at least one application detail was typed. */ ?>
        const hasDetails = values.title !== '' || values.adviser !== '' || values.abstract !== '' || values.enroll_date !== '';
<?php /*         True when the student picked a file. */ ?>
        const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

<?php /*         Comment noting only new applications are validated fully. */ ?>
        // Only a NEW application is validated as a whole. A student who
<?php /*         Comment noting an existing application only needs outstanding documents. */ ?>
        // already sent their application only attaches the documents that
<?php /*         Comment completing the note about the outstanding documents. */ ?>
        // are still outstanding for that stage.
<?php /*         Validates the whole form for a new application. */ ?>
        if (hasDetails && !hasExistingApplication) {
<?php /*             Shows an error when the title is empty. */ ?>
            if (!values.title) { fieldError(title, 'Enter your research / paper title.'); e.preventDefault(); }
<?php /*             Shows an error when no adviser was selected. */ ?>
            if (!values.adviser) { fieldError(adviser, 'Select an adviser from the available adviser pool.'); e.preventDefault(); }
<?php /*             Shows an error when the abstract is shown but empty. */ ?>
            if (abstract && !values.abstract) { fieldError(abstract, 'Write a brief description of your research.'); e.preventDefault(); }
<?php /*             Shows an error when the enrollment date is empty. */ ?>
            if (!values.enroll_date) { fieldError(enroll, 'Enter your enrollment / start date.'); e.preventDefault(); }
<?php /*             Gets the documents still missing for the current stage. */ ?>
            const stillMissing = missingDocuments[currentStageKey] || [];
<?php /*             Checks whether required documents are missing without a file. */ ?>
            if (stillMissing.length && !hasFile) {
<?php /*                 Lists the missing documents in the error message. */ ?>
                fieldError(typeSelect, 'Attach these documents for this stage: ' + stillMissing.join(', ') + '.');
<?php /*                 Blocks the form from being submitted. */ ?>
                e.preventDefault();
<?php /*             Closes the missing documents check. */ ?>
            }
<?php /*             Checks whether a file was picked without a document type. */ ?>
            if (hasFile && !typeSelect.value) {
<?php /*                 Asks the student to pick the document type. */ ?>
                fieldError(typeSelect, 'Select the document type of the file you attached.');
<?php /*                 Blocks the form from being submitted. */ ?>
                e.preventDefault();
<?php /*             Closes the document type check. */ ?>
            }
<?php /*         Handles the case where no file was picked. */ ?>
        } else if (!hasFile) {
<?php /*             Blocks the form from being submitted. */ ?>
            e.preventDefault();
<?php /*             Asks for details or a document. */ ?>
            fieldError(typeSelect, 'Fill in your application details or attach a document.');
<?php /*         Closes the no file case. */ ?>
        }

<?php /*         Runs when validation stopped the submit. */ ?>
        if (e.defaultPrevented) {
<?php /*             Scrolls the page to the top to show the errors. */ ?>
            window.scrollTo({ top: 0, behavior: 'smooth' });
<?php /*         Closes the blocked submit condition. */ ?>
        }
<?php /*     Closes the submit event listener. */ ?>
    });

<?php /*     Comment explaining the Submit Another Application button. */ ?>
    // "Submit Another Application": clear the form for a fresh application.
<?php /*     Finds the Submit Another Application button. */ ?>
    var anotherBtn = document.getElementById('submitAnotherBtn');
<?php /*     Runs only when that button exists. */ ?>
    if (anotherBtn) {
<?php /*         Adds a click listener for starting a new application. */ ?>
        anotherBtn.addEventListener('click', function () {
<?php /*             Loops over the four text fields to clear them. */ ?>
            ['title', 'adviser', 'abstract', 'enroll_date'].forEach(function (name) {
<?php /*                 Finds each field by name. */ ?>
                const field = form.querySelector('[name="' + name + '"]');
<?php /*                 Empties the field value. */ ?>
                if (field) field.value = '';
<?php /*             Closes the clear fields loop. */ ?>
            });
<?php /*             Resets the document type select. */ ?>
            typeSelect.value = '';
<?php /*             Clears the chosen file. */ ?>
            if (fileInput) fileInput.value = '';
<?php /*             Finds the draft file note. */ ?>
            var note = document.getElementById('draftFileNote');
<?php /*             Removes the draft file note. */ ?>
            if (note) note.remove();
<?php /*             Comment noting the draft is cleared for the new application. */ ?>
            // The draft is cleared: the student explicitly starts a new one.
<?php /*             Pauses draft saving while the form is reset. */ ?>
            draftDisabled = true;
<?php /*             Creates the data for an empty draft. */ ?>
            const data = new URLSearchParams();
<?php /*             Marks the request as a draft save. */ ?>
            data.append('form', 'draft');
<?php /*             Posts the empty draft to replace the saved one. */ ?>
            fetch(window.location.pathname + window.location.search, {
<?php /*                 Uses POST with the session cookie and keepalive. */ ?>
                method: 'POST', body: data, credentials: 'same-origin', keepalive: true
<?php /*             Closes the fetch call. */ ?>
            });
<?php /*             Scrolls back to the top of the page. */ ?>
            window.scrollTo({ top: 0, behavior: 'smooth' });
<?php /*         Closes the click listener. */ ?>
        });
<?php /*     Closes the button exists condition. */ ?>
    }

<?php /*     Comment explaining the Discard Draft button. */ ?>
    // "Discard Draft": forget the four values the student typed. Uploaded
<?php /*     Comment noting uploaded files and applications are untouched. */ ?>
    // documents and submitted applications are never touched.
<?php /*     Finds the Discard Draft button. */ ?>
    var discardBtn = document.getElementById('discardDraftBtn');
<?php /*     Runs only when the discard button exists. */ ?>
    if (discardBtn) {
<?php /*         Adds a click listener to the discard button. */ ?>
        discardBtn.addEventListener('click', function () {
<?php /*             Asks the student to confirm before discarding. */ ?>
            if (!window.confirm('Discard your saved draft? Only the values you typed are removed — your uploaded documents and submitted application stay exactly as they are.')) {
<?php /*                 Stops when the student cancels the confirm dialog. */ ?>
                return;
<?php /*             Closes the confirm check. */ ?>
            }
<?php /*             Comment noting the four fields are cleared straight away. */ ?>
            // Clear the four fields straight away.
<?php /*             Loops over the four fields to clear. */ ?>
            ['title', 'adviser', 'abstract', 'enroll_date'].forEach(function (name) {
<?php /*                 Finds each field by name. */ ?>
                var field = form.querySelector('[name="' + name + '"]');
<?php /*                 Empties the field value. */ ?>
                if (field) field.value = '';
<?php /*             Closes the clear fields loop. */ ?>
            });
<?php /*             Removes any error messages. */ ?>
            clearErrors();
<?php /*             Cancels a pending draft save. */ ?>
            clearTimeout(draftTimer);
<?php /*             Pauses draft saving until the discard request finishes. */ ?>
            draftDisabled = true;   // the draft stays gone, even on reload
<?php /*             Creates the discard request data. */ ?>
            var data = new URLSearchParams();
<?php /*             Marks the request as a discard draft action. */ ?>
            data.append('form', 'discard_draft');
<?php /*             Posts the discard request to the same page. */ ?>
            fetch(window.location.pathname + window.location.search, {
<?php /*                 Uses POST with the session cookie. */ ?>
                method: 'POST', body: data, credentials: 'same-origin'
<?php /*             Runs after the server answers. */ ?>
            }).then(function () {
<?php /*                 Allows draft saving again. */ ?>
                draftDisabled = false;   // new typing is saved again
<?php /*                 Finds the draft notice banner. */ ?>
                var banner = document.querySelector('.draft-notice');
<?php /*                 Removes the draft notice banner. */ ?>
                if (banner) banner.remove();
<?php /*                 Finds the draft file note. */ ?>
                var note = document.getElementById('draftFileNote');
<?php /*                 Removes the draft file note. */ ?>
                if (note) note.remove();
<?php /*                 Checks whether the shared success popup helper exists. */ ?>
                if (typeof window.showSuccessPopup === 'function') {
<?php /*                     Shows the draft discarded popup. */ ?>
                    window.showSuccessPopup({
<?php /*                         Sets the popup title. */ ?>
                        title: 'Draft discarded successfully.',
<?php /*                         Sets the popup message. */ ?>
                        message: 'The values you typed were removed. Your documents and application were not changed.',
<?php /*                         Closes the popup after four seconds. */ ?>
                        duration: 4000
<?php /*                     Closes the popup options object. */ ?>
                    });
<?php /*                 Closes the popup helper condition. */ ?>
                }
<?php /*             Closes the then callback. */ ?>
            });
<?php /*         Closes the click listener. */ ?>
        });
<?php /*     Closes the discard button condition. */ ?>
    }
<?php /* Closes and runs the isolated function. */ ?>
})();
<?php /* Closes the script block. */ ?>
</script>

<?php /* Loads the shared page footer. */ ?>
<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
