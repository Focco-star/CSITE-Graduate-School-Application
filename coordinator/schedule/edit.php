<?php /* Opens PHP mode so the server can run the edit form logic. */ ?>
<?php
// Loads config.php, which holds site settings and the url() helper.
require_once __DIR__ . '/../../includes/config/config.php';
// Loads db.php, the DB class used for database queries here.
require_once __DIR__ . '/../../includes/config/db.php';

// Checks whether a session is already running.
if (session_status() === PHP_SESSION_NONE) {
    // Starts the session so the logged-in coordinator is known.
    session_start();
// Closes the if that starts the session.
}

// Reads the logged-in user array from the session, or an empty array.
$sessionUser = $_SESSION['user'] ?? [];
// If the user is missing or is not a coordinator, block access.
if (!is_array($sessionUser) || strtolower((string) ($sessionUser['role'] ?? '')) !== 'coordinator') {
    // Sends a non-coordinator to the coordinator login page.
    redirectTo('coordinator/login.php');
// Closes the if that guarded this page for coordinators only.
}

// Sets the page title shown in the browser tab.
$pageTitle   = 'Edit Presentation Schedule';
// Tells the header this page belongs to the coordinator role.
$role        = 'coordinator';
// Marks the sidebar item for schedules as the active one.
$currentPage = 'schedule';
// Takes the coordinator's display name for the header greeting.
$userName    = $mockCoordinator['name'];

// Finds the schedule by the id in the URL, or falls back to the first one.
$sch = findSchedule((string) ($_GET['id'] ?? '')) ?? (storeGet('schedules')[0] ?? null);

// Picker-friendly current values: the edit form uses the same date/time
// pickers as the Schedule Presentation form, so feed them machine formats.
// Converts the stored date into the YYYY-MM-DD value a date input needs.
$editDate = scheduleDateYMD((string) ($sch['date'] ?? '')) ?? '';
// Converts the stored time into a 24-hour HH:MM:SS value.
$editTime = scheduleTimeHMS((string) ($sch['time'] ?? ''));
// Keeps only the hour and minute for the time input.
$editTime = $editTime !== '' ? substr($editTime, 0, 5) : '';

// Pool-backed Academic Assessment Team options (same sources as add.php).
// Starts an empty list for the adviser pool options.
$adviserOpts = [];
// Starts an empty list for the documenter pool options.
$documenterOpts = [];
// Starts an empty error message for the documenter pool.
$documenterPoolError = '';
// Starts a try block so the adviser pool can fail safely.
try {
    // Loads adviser names, available ones first, for the drop-down.
    $adviserOpts = DB::query("SELECT name, availability FROM advisor_pool ORDER BY availability = 'available' DESC, name ASC")->fetchAll();
// Catches a database error so the adviser list stays empty.
} catch (Throwable $e) {
// Closes the try/catch that loaded the adviser pool.
}
// Starts a try block so the documenter pool can fail safely.
try {
    // Prepares a query for available documenters and their qualification.
    $documenterOpts = DB::query(
        // Opens the SQL text that selects available documenter rows.
        "SELECT first_name, middle_name, last_name, qualification
         FROM documenter_pool
         WHERE availability = 'available'
         ORDER BY last_name, first_name, middle_name"
    // Runs the query and fetches all available documenter rows.
    )->fetchAll();
// Catches a database error, usually because the pool table is missing.
} catch (Throwable $e) {
    // Logs the documenter pool error to the server error log.
    error_log('[documenter pool] ' . $e->getMessage());
    // Sets the message telling the coordinator to apply the migration.
    $documenterPoolError = 'The documenter pool is unavailable. Apply the documenter-pool migration before rescheduling a presentation.';
// Closes the try/catch for the documenter pool.
}
// Prepares the list of documenter select options.
$documenterOptions = [];
// Loops over each documenter row returned from the pool.
foreach ($documenterOpts as $documenter) {
    // Builds one option entry holding a name and a qualification.
    $documenterOptions[] = [
        // Joins the name parts into the 'Last, First Middle' label.
        'name' => trim($documenter['last_name'] . ', ' . $documenter['first_name'] . ' ' . ($documenter['middle_name'] ?? '')),
        // Stores the qualification text or an empty string.
        'qualification' => (string) ($documenter['qualification'] ?? ''),
    // Closes the option entry array.
    ];
// Closes the loop over the documenter rows.
}

// Panel members assigned to THIS schedule only, preselected panel slots.
// Loads the panel members assigned to this schedule only.
$assignedPanel = databaseSchedulePanelMembersForSchedule((int) ($sch['id'] ?? 0));
// Maps each panel row to a 'Last, First Middle' text.
$parsed = array_map(static function (array $member): string {
    // Builds the person's name from last, first and middle parts.
    return trim($member['last_name'] . ', ' . $member['first_name'] . (!empty($member['middle_name']) ? ' ' . $member['middle_name'] : ''));
// Closes the mapping function used for the panel names.
}, $assignedPanel);

// Documenter stored on this schedule, rebuilt as the 'Last, First Middle'
// value the documenter select submits (the row stores 'First Middle Last').
// Starts the current documenter label as empty text.
$currentDocumentor = '';
// Splits the stored documenter name into single words.
$documentorWords = preg_split('/\s+/', trim((string) ($sch['documentor'] ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
// Only rebuild the label when the stored name has words.
if ($documentorWords) {
    // Takes the first word as the documenter's first name.
    $docFirst = (string) array_shift($documentorWords);
    // Takes the last word as the documenter's last name.
    $docLast = (string) array_pop($documentorWords);
    // Builds 'Last, First Middle' for preselection in the drop-down.
    $currentDocumentor = trim($docLast . ', ' . $docFirst . ($documentorWords ? ' ' . implode(' ', $documentorWords) : ''));
// Closes the if that rebuilt the documenter label.
}

// Only save changes when the form was posted and a schedule exists.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $sch) {
    // Joins the chosen panel members into one comma-separated text.
    $panel = implode(', ', array_filter([$_POST['panel1'] ?? '', $_POST['panel2'] ?? '', $_POST['panel3'] ?? '']));
    // Reads the saved schedule status before the edit.
    $storedStatus = (string) ($sch['status'] ?? 'pending');
    // A completed schedule keeps its completed status when other details change.
    $newStatus = $storedStatus === 'completed'
        ? 'completed'
        : (($_POST['status'] ?? 'pending') === 'confirmed' ? 'confirmed' : 'pending');
    // Updates the schedule row through updateScheduleRecord().
    updateScheduleRecord($sch['id'], [
        // Saves the date from the form, or the old one when it is missing.
        'date' => trim($_POST['date'] ?? $sch['date']),
        // Saves the time from the form, or the old one when it is missing.
        'time' => trim($_POST['time'] ?? $sch['time']),
        // Saves the venue from the form, or the old one when it is missing.
        'venue' => trim($_POST['venue'] ?? ($sch['venue'] ?? '')),
        // Keeps a completed schedule completed; otherwise stores the chosen status.
        'status' => $newStatus,
        // Formats the adviser name, or keeps the stored adviser.
        'adviser' => formatPersonName($_POST['adviser'] ?? ($sch['adviser'] ?? '')),
        // Stores the panel text, or keeps the old panel when it is blank.
        'panel' => $panel ?: ($sch['panel'] ?? 'TBD'),
        // Stores the chosen documenter name.
        'documentor' => trim($_POST['documentor'] ?? ''),
    // Closes the array of updated schedule values.
    ]);
    // Returns the coordinator to the schedule list after saving.
    redirectTo('coordinator/schedule/manage.php');
// Closes the if that handled the posted form.
}

// Loads the shared header, which prints the page top and sidebar.
require_once __DIR__ . '/../../includes/layout/header.php';
// Closes PHP mode and switches back to HTML output.
?>

<?php /* Page header wrapper that groups the title and description. */ ?>
<div class="page-header">
<?php /*     Shows the heading 'Edit Presentation Schedule'. */ ?>
    <h2>Edit Presentation Schedule</h2>
<?php /*     Explains what the coordinator can change on this page. */ ?>
    <p>Update the presentation date, venue, and assign the Academic Assessment Team.</p>
<?php /* Closes the page header wrapper. */ ?>
</div>

<?php /* If no schedule was found, show a warning instead of the form. */ ?>
<?php if (!$sch): ?>
<?php /* Warning box saying the schedule could not be found. */ ?>
<div class="alert alert-warning">Schedule not found.</div>
<?php /* Else branch: a schedule exists, so draw the edit form. */ ?>
<?php else: ?>
<?php /* Form that posts the changes back with the schedule id in the URL. */ ?>
<form method="post" action="<?= url('coordinator/schedule/edit.php?id=' . urlencode($sch['id'])) ?>" data-validate>
<?php /*     Card that holds the date, time, venue and status fields. */ ?>
    <div class="card">
<?php /*         Card header titled 'Schedule Details'. */ ?>
        <div class="card-header"><h3>Schedule Details</h3></div>
<?php /*         Card body wrapper for the schedule fields. */ ?>
        <div class="card-body">
<?php /*             Row that puts the date and time fields side by side. */ ?>
            <div class="form-row">
<?php /*                 Field wrapper for the presentation date. */ ?>
                <div class="form-field">
<?php /*                     Label for the date field, marked as required. */ ?>
                    <label>Presentation Date <span class="required">*</span></label>
<?php /*                     Wrapper that styles the date picker input. */ ?>
                    <div class="schedule-picker-wrap">
<?php /*                         Date input filled with the schedule date in YYYY-MM-DD form. */ ?>
                        <input class="schedule-picker" type="date" name="date" required value="<?= htmlspecialchars($editDate) ?>">
<?php /*                     Closes the date picker wrapper. */ ?>
                    </div>
<?php /*                 Closes the date field wrapper. */ ?>
                </div>
<?php /*                 Field wrapper for the presentation time. */ ?>
                <div class="form-field">
<?php /*                     Label for the time field, marked as required. */ ?>
                    <label>Presentation Time <span class="required">*</span></label>
<?php /*                     Wrapper that styles the time picker input. */ ?>
                    <div class="schedule-picker-wrap">
<?php /*                         Time input filled with the schedule time in HH:MM form. */ ?>
                        <input class="schedule-picker schedule-time-picker" type="time" name="time" required value="<?= htmlspecialchars($editTime) ?>">
<?php /*                     Closes the time picker wrapper. */ ?>
                    </div>
<?php /*                 Closes the time field wrapper. */ ?>
                </div>
<?php /*             Closes the row holding date and time. */ ?>
            </div>
<?php /*             Field wrapper for the venue text box. */ ?>
            <div class="form-field">
<?php /*                 Label for the venue field, marked as required. */ ?>
                <label>Venue <span class="required">*</span></label>
<?php /*                 Venue input prefilled with the stored venue text. */ ?>
                <input type="text" name="venue" required value="<?= htmlspecialchars($sch['venue'] ?? '') ?>" placeholder="e.g. CSITE Seminar Room, Room 204">
<?php /*             Closes the venue field wrapper. */ ?>
            </div>
<?php /*             Field wrapper for the schedule status drop-down. */ ?>
            <div class="form-field">
<?php /*                 Label for the status field. */ ?>
                <label>Schedule Status</label>
<?php /*                 Drop-down that chooses pending or confirmed status. */ ?>
                <select name="status" <?= $sch['status'] === 'completed' ? 'disabled' : '' ?>>
<?php /*                     Completed option, shown only for a finished presentation. */ ?>
                    <?php if ($sch['status'] === 'completed'): ?>
                    <option value="completed" selected>Completed</option>
                    <?php else: ?>
<?php /*                     Pending option, preselected when the schedule is pending. */ ?>
                    <option value="pending" <?= $sch['status'] === 'pending' ? 'selected' : '' ?>>Pending Confirmation</option>
<?php /*                     Confirmed option, preselected when the schedule is confirmed. */ ?>
                    <option value="confirmed" <?= $sch['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <?php endif; ?>
<?php /*                 Closes the status drop-down. */ ?>
                </select>
<?php /*                 Explains that completed presentations keep their status. */ ?>
                <?php if ($sch['status'] === 'completed'): ?>
                <p class="field-hint">A completed presentation keeps its status when other details are edited.</p>
                <?php endif; ?>
<?php /*             Closes the status field wrapper. */ ?>
            </div>
<?php /*             Info panel listing what must be done before a presentation. */ ?>
            <div style="margin-top:0.5rem;padding:0.9rem 1rem;background:rgba(37,99,235,0.04);border-radius:var(--radius);border:1px solid rgba(37,99,235,0.12);font-size:0.82rem;">
<?php /*                 Panel title with a clipboard icon for the checklist. */ ?>
                <p style="font-weight:700;color:var(--gray-700);margin-bottom:0.5rem;"><i class="fas fa-clipboard-check" style="margin-right:5px;color:var(--adzu-navy);"></i>Prerequisites before scheduling:</p>
<?php /*                 Loops over the five prerequisite items shown to the coordinator. */ ?>
                <?php foreach (['Paper submitted and verified','Adviser endorsement verified','Graduate School endorsement submitted and verified','Official receipt / payment recorded','Stage marked as Ready for Presentation'] as $req): ?>
<?php /*                 Row holding one check icon and its requirement text. */ ?>
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.2rem;">
<?php /*                     Small green check icon marking a prerequisite. */ ?>
                    <i class="fas fa-check-circle" style="color:var(--success);font-size:0.75rem;"></i>
<?php /*                     Prints the prerequisite text, escaped for safety. */ ?>
                    <span style="color:var(--gray-600);"><?= htmlspecialchars($req) ?></span>
<?php /*                 Closes the prerequisite row. */ ?>
                </div>
<?php /*                 Closes the loop over the prerequisite items. */ ?>
                <?php endforeach; ?>
<?php /*             Closes the prerequisite info panel. */ ?>
            </div>
<?php /*         Closes the card body of Schedule Details. */ ?>
        </div>
<?php /*     Closes the Schedule Details card. */ ?>
    </div>

<?php /*     Card for the Academic Assessment Team fields. */ ?>
    <div class="card">
<?php /*         Card header titled 'Academic Assessment Team'. */ ?>
        <div class="card-header"><h3>Academic Assessment Team</h3></div>
<?php /*         Card body wrapper for the team fields. */ ?>
        <div class="card-body">
<?php /*             Row holding the adviser drop-down. */ ?>
            <div class="form-row">
<?php /*                 Field wrapper for the adviser field. */ ?>
                <div class="form-field">
<?php /*                     Label for the adviser field. */ ?>
                    <label>Adviser</label>
<?php /*                     Adviser drop-down that submits the chosen adviser name. */ ?>
                    <select name="adviser">
<?php /*                         Empty option that keeps the currently stored adviser. */ ?>
                        <option value="">Keep current</option>
<?php /*                         Loops over adviser rows and flags those marked unavailable. */ ?>
                        <?php foreach ($adviserOpts as $adviser): $isUnavailable = ($adviser['availability'] ?? '') !== 'available'; ?>
<?php /*                         Option shows the adviser, selected when already assigned to this job. */ ?>
                        <option <?= $isUnavailable ? 'disabled' : '' ?> <?= (string) ($sch['adviser'] ?? '') === $adviser['name'] ? 'selected' : '' ?>><?= htmlspecialchars($adviser['name']) ?><?= $isUnavailable ? ' [Unavailable]' : '' ?></option>
<?php /*                         Closes the loop over the adviser options. */ ?>
                        <?php endforeach; ?>
<?php /*                     Closes the adviser drop-down. */ ?>
                    </select>
<?php /*                 Closes the adviser field wrapper. */ ?>
                </div>
<?php /*             Closes the row holding the adviser drop-down. */ ?>
            </div>
<?php /*             Row that puts panel members 1 and 2 side by side. */ ?>
            <div class="form-row">
<?php /*                 Field wrapper for panel member 1. */ ?>
                <div class="form-field">
<?php /*                     Label for panel member 1. */ ?>
                    <label>Panel Member 1</label>
<?php /*                     Panel 1 drop-down with the shared panel-member-select class. */ ?>
                    <select name="panel1" class="panel-member-select">
<?php /*                         Empty option that keeps the current panel member 1. */ ?>
                        <option value="">Keep current</option>
<?php /*                         Loops over all panel member choices. */ ?>
                        <?php foreach (panelSelectOptions() as $p): ?>
<?php /*                         Option is selected when it matches the stored panel member 1. */ ?>
                        <option <?= ($parsed[0] ?? '') !== '' && ($parsed[0] === $p || str_contains($p, $parsed[0])) ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
<?php /*                         Closes the panel 1 option loop. */ ?>
                        <?php endforeach; ?>
<?php /*                     Closes the panel 1 drop-down. */ ?>
                    </select>
<?php /*                 Closes the panel 1 field wrapper. */ ?>
                </div>
<?php /*                 Field wrapper for panel member 2. */ ?>
                <div class="form-field">
<?php /*                     Label for panel member 2. */ ?>
                    <label>Panel Member 2</label>
<?php /*                     Panel 2 drop-down with the shared panel-member-select class. */ ?>
                    <select name="panel2" class="panel-member-select">
<?php /*                         Empty option that keeps the current panel member 2. */ ?>
                        <option value="">Keep current</option>
<?php /*                         Loops over all panel member choices. */ ?>
                        <?php foreach (panelSelectOptions() as $p): ?>
<?php /*                         Option is selected when it matches the stored panel member 2. */ ?>
                        <option <?= ($parsed[1] ?? '') !== '' && ($parsed[1] === $p || str_contains($p, $parsed[1])) ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
<?php /*                         Closes the panel 2 option loop. */ ?>
                        <?php endforeach; ?>
<?php /*                     Closes the panel 2 drop-down. */ ?>
                    </select>
<?php /*                 Closes the panel 2 field wrapper. */ ?>
                </div>
<?php /*             Closes the row holding panel members 1 and 2. */ ?>
            </div>
<?php /*             Row that puts panel member 3 and the documenter together. */ ?>
            <div class="form-row">
<?php /*                 Field wrapper for panel member 3. */ ?>
                <div class="form-field">
<?php /*                     Label for panel member 3. */ ?>
                    <label>Panel Member 3</label>
<?php /*                     Panel 3 drop-down with the shared panel-member-select class. */ ?>
                    <select name="panel3" class="panel-member-select">
<?php /*                         Empty option that keeps the current panel member 3. */ ?>
                        <option value="">Keep current</option>
<?php /*                         Loops over all panel member choices. */ ?>
                        <?php foreach (panelSelectOptions() as $p): ?>
<?php /*                         Option is selected when it matches the stored panel member 3. */ ?>
                        <option <?= ($parsed[2] ?? '') !== '' && ($parsed[2] === $p || str_contains($p, $parsed[2])) ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
<?php /*                         Closes the panel 3 option loop. */ ?>
                        <?php endforeach; ?>
<?php /*                     Closes the panel 3 drop-down. */ ?>
                    </select>
<?php /*                 Closes the panel 3 field wrapper. */ ?>
                </div>
<?php /*                 Field wrapper for the documenter drop-down. */ ?>
                <div class="form-field">
<?php /*                     Label for the documenter field. */ ?>
                    <label>Documenter</label>
<?php /*                     Documenter drop-down, disabled when the pool is unavailable. */ ?>
                    <select name="documentor" <?= $documenterPoolError ? 'disabled' : '' ?>>
<?php /*                         Empty option that keeps the currently stored documenter. */ ?>
                        <option value="">Keep current</option>
<?php /*                         Loops over the available documenter options. */ ?>
                        <?php foreach ($documenterOptions as $documenter): ?>
<?php /*                         Option is selected when it matches the stored documenter name. */ ?>
                        <option value="<?= htmlspecialchars($documenter['name']) ?>" <?= $currentDocumentor !== '' && $documenter['name'] === $currentDocumentor ? 'selected' : '' ?>><?= htmlspecialchars($documenter['name'] . ($documenter['qualification'] !== '' ? ' (' . $documenter['qualification'] . ')' : '')) ?></option>
<?php /*                         Closes the loop over the documenter options. */ ?>
                        <?php endforeach; ?>
<?php /*                         Shows a disabled note when no documenter is available. */ ?>
                        <?php if (!$documenterPoolError && !$documenterOptions): ?><option value="" disabled>No available documenters found</option><?php endif; ?>
<?php /*                     Closes the documenter drop-down. */ ?>
                    </select>
<?php /*                     Only show the pool error hint when there is an error. */ ?>
                    <?php if ($documenterPoolError): ?>
<?php /*                     Prints the documenter pool error message under the drop-down. */ ?>
                    <p class="field-hint"><?= htmlspecialchars($documenterPoolError) ?></p>
<?php /*                     Closes the if that showed the pool error hint. */ ?>
                    <?php endif; ?>
<?php /*                 Closes the documenter field wrapper. */ ?>
                </div>
<?php /*             Closes the row holding panel 3 and the documenter. */ ?>
            </div>
<?php /*         Closes the card body of Academic Assessment Team. */ ?>
        </div>
<?php /*     Closes the Academic Assessment Team card. */ ?>
    </div>

<?php /*     Action row holding the save and cancel buttons. */ ?>
    <div class="form-actions">
<?php /*         Submit button that saves the schedule changes. */ ?>
        <button type="submit" class="btn btn-primary"><i class="fas fa-calendar-check"></i> Save Changes</button>
<?php /*         Cancel link that goes back to the schedule list. */ ?>
        <a href="<?= url('coordinator/schedule/manage.php') ?>" class="btn btn-outline">Cancel</a>
<?php /*     Closes the action row. */ ?>
    </div>
<?php /* Closes the edit form. */ ?>
</form>

<?php /* Starts a JavaScript block that keeps panel choices unique. */ ?>
<script>
<?php /* Wraps the script in a function so variables stay private. */ ?>
(function () {
<?php /*     Collects the three panel member drop-downs into an array. */ ?>
    const panelSelects = Array.from(document.querySelectorAll('.panel-member-select'));
<?php /*     Function that stops the same panel member being chosen twice. */ ?>
    function updatePanelChoices() {
<?php /*         Collects the panel values already chosen, ignoring placeholders. */ ?>
        const selectedValues = panelSelects.map(select => select.value).filter(Boolean);
<?php /*         Loops over each panel member drop-down. */ ?>
        panelSelects.forEach(select => {
<?php /*             Loops over every option inside that drop-down. */ ?>
            Array.from(select.options).forEach(option => {
<?php /*                 Starts the check for an option already taken elsewhere. */ ?>
                option.disabled = option.value !== ''
<?php /*                     Skips the option that is already chosen in this drop-down. */ ?>
                    && option.value !== select.value
<?php /*                     Completes the check: another drop-down already uses this name. */ ?>
                    && selectedValues.includes(option.value);
<?php /*             Closes the option loop. */ ?>
            });
<?php /*         Closes the panel drop-down loop. */ ?>
        });
<?php /*     Closes the updatePanelChoices function. */ ?>
    }
<?php /*     Re-checks panel options whenever any drop-down changes. */ ?>
    panelSelects.forEach(select => select.addEventListener('change', updatePanelChoices));
<?php /*     Runs the check once so saved duplicates are disabled on load. */ ?>
    updatePanelChoices();
<?php /* Closes and runs the function that set up the panel checks. */ ?>
})();
<?php /* Closes the JavaScript block. */ ?>
</script>
<?php /* Closes the else that drew the form only when a schedule exists. */ ?>
<?php endif; ?>

<?php /* Loads the shared footer, which prints closing tags and scripts. */ ?>
<?php require_once __DIR__ . '/../../includes/layout/footer.php'; ?>
