<?php
/* CSITE configuration module: Flash messages, redirects, stage maps, stage options, and archive helpers. */

// Starts formatFileSize(), which turns a byte count into a readable size.
function formatFileSize(int $bytes): string {
    // Handles empty or invalid sizes with a dash placeholder.
    if ($bytes <= 0) {
        // Returns a dash because no usable file size is available.
        return '—';
    // Closes the size check.
    }
    // Converts bytes to megabytes and formats them with two decimals.
    return number_format($bytes / 1048576, 2) . ' MB';
// Closes formatFileSize().
}

// Starts redirectTo(), a helper that sends the browser to another page.
function redirectTo(string $path): void {
    // Sets the Location header using the app base URL helper.
    header('Location: ' . url($path));
    // Stops the script so no code runs after the redirect.
    exit;
// Closes redirectTo().
}

// Starts setFlash(), which stores a one-time message for the next page.
function setFlash(string $type, string $message): void {
    // Starts the session first when no session is active yet.
    if (session_status() === PHP_SESSION_NONE) session_start();
    // Saves the message type and text in the session flash slot.
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
// Closes setFlash().
}

// Starts pullFlash(), which reads and clears the stored flash message.
function pullFlash(): ?array {
    // Starts the session first when no session is active yet.
    if (session_status() === PHP_SESSION_NONE) session_start();
    // Reads the flash message that a previous page stored, if any.
    $flash = $_SESSION['flash'] ?? null;
    // Removes the flash so it is shown only once.
    unset($_SESSION['flash']);
    // Returns the flash array, or null when there was no message.
    return is_array($flash) ? $flash : null;
// Closes pullFlash().
}

// Starts currentCoordinatorName(), which returns the coordinator display name.
function currentCoordinatorName(): string {
    // Starts the session first when no session is active yet.
    if (session_status() === PHP_SESSION_NONE) session_start();
    // Reads the logged-in user's full name from the session.
    $name = trim((string) ($_SESSION['user']['full_name'] ?? ''));
    // Falls back to the mock coordinator name or a generic title.
    return $name !== '' ? $name : ($GLOBALS['mockCoordinator']['name'] ?? 'Graduate Program Coordinator');
// Closes currentCoordinatorName().
}

// Starts appWorkflowDefaults(), which returns the default application fields.
function appWorkflowDefaults(): array {
    // Returns the default values array used for new application records.
    return [
        // Starts with an empty per-stage workflow state map.
        'workflowState' => [],
        // Marks the application fee as not yet recorded.
        'paymentRecorded' => false,
        // Marks the application as not ready for presentation yet.
        'readyForPresentation' => false,
        // Leaves the final result blank until the defense is graded.
        'result' => '',
        // Marks the graduate school endorsement as not given.
        'gradSchoolEndorsed' => false,
        // Leaves the revision instructions blank.
        'revisionInstructions' => '',
        // Leaves the receipt number blank.
        'receiptNumber' => '',
        // Leaves the payment date blank.
        'paymentDate' => '',
        // Leaves the payment amount blank.
        'paymentAmount' => '',
    // Closes the defaults array.
    ];
// Closes appWorkflowDefaults().
}

// Starts getWorkflowStageMap(), which keys stage details by stage key.
function getWorkflowStageMap(string $track): array {
    // Prepares an empty map for the stages of this track.
    $map = [];
    // Loops over the stages defined by the workflow of the track.
    foreach (getWorkflow($track)['stages'] as $stage) {
        // Stores the full stage details under its stage key.
        $map[$stage['key']] = $stage;
    // Closes the stage loop.
    }
    // Returns the stage map for the track.
    return $map;
// Closes getWorkflowStageMap().
}

// Starts getWorkflowStageLabels(), which keys stage labels by stage key.
function getWorkflowStageLabels(string $track): array {
    // Prepares an empty list of stage labels.
    $labels = [];
    // Loops over the workflow stages of the track.
    foreach (getWorkflow($track)['stages'] as $stage) {
        // Stores the readable label under the stage key.
        $labels[$stage['key']] = $stage['label'];
    // Closes the stage loop.
    }
    // Returns the stage label map.
    return $labels;
// Closes getWorkflowStageLabels().
}

// Starts presentationStageOptions(), listing the track's stage labels.
function presentationStageOptions(string $track): array {
    // Returns the stage key to label map for the track as a plain list.
    return array_values(getStagesForTrack($track));
// Closes presentationStageOptions().
}

/**
 * Return the presentation stages that may be scheduled for this student.
 *
 * A schedule belongs to the student's current workflow stage. Earlier stages
 * are already complete and later stages are locked until the current stage
 * has been approved, so the selector normally contains one valid option.
 */
// Starts presentationStageOptionsForStudent(), listing stages a student may schedule.
function presentationStageOptionsForStudent(string $email, array $student): array {
    // Reads the student's track and trims any extra spaces.
    $track = trim((string) ($student['track'] ?? ''));
    // Handles students whose record has no track yet.
    if ($track === '') {
        // Guesses the track from the student's program name.
        $track = getTrackForProgram((string) ($student['program'] ?? ''));
    // Closes the missing track fallback.
    }

    // Loads the ordered workflow stages for the track.
    $stages = getWorkflow($track)['stages'];
    // Returns an empty list when the workflow has no stages.
    if (!$stages) {
        // Ends with an empty array because no stage can be offered.
        return [];
    // Closes the empty workflow guard.
    }

    // Finds the index of the student's current workflow stage.
    $currentIdx = workflowCurrentIndex(getStudentProgress($email, $track), $stages);
    // Gets the current stage details, or null when the index is out of range.
    $currentStage = $stages[$currentIdx] ?? null;
    // Stops when there is no current stage or it has no key.
    if (!$currentStage || ($currentStage['key'] ?? '') === '') {
        // Returns an empty list because no valid stage exists.
        return [];
    // Closes the current stage check.
    }

    // Returns only the current stage so earlier and later stages stay locked.
    return [(string) $currentStage['key'] => (string) $currentStage['label']];
// Closes presentationStageOptionsForStudent().
}

// Starts stageKeyFromLabel(), which maps a stage label to its stage key.
function stageKeyFromLabel(string $label): string {
    // Lowercases the label so matching ignores letter case.
    $l = strtolower($label);
    // Treats any label mentioning concept as the concept stage.
    if (str_contains($l, 'concept')) {
        // Returns the concept stage key.
        return 'concept';
    // Closes the concept label check.
    }
    // Treats any label mentioning final as the final defense stage.
    if (str_contains($l, 'final')) {
        // Returns the final stage key.
        return 'final';
    // Closes the final label check.
    }
    // Falls back to proposal for every other stage label.
    return 'proposal';
// Closes stageKeyFromLabel().
}

// Starts displayStageLabel(), which shows a readable stage name for a student.
function displayStageLabel(array $student): string {
    // Reads the student's track, guessing it from the program if missing.
    $track = $student['track'] ?? getTrackForProgram($student['program'] ?? 'MSCS');
    // Reads the student's current stage key from the record.
    $key = $student['currentStage'] ?? '';
    // Checks whether the student has not started the workflow yet.
    if ($key === 'not_started' || $key === 'Not Started') {
        // Returns the not started text for new students.
        return 'Not Started';
    // Closes the not started check.
    }
    // Loads the stage label map for the student's track.
    $wf = getWorkflowStageLabels($track);
    // Checks whether the current stage key has a readable label.
    if (isset($wf[$key])) {
        // Returns the readable label for the current stage.
        return $wf[$key];
    // Closes the label lookup check.
    }
    // Loads the stage list for the track as a fallback.
    $stages = getStagesForTrack($track);
    // Returns the stage label, the raw key, or the key text as a last resort.
    return $stages[$key] ?? (string) $key;
// Closes displayStageLabel().
}

// Starts coordDeleteForm(), which builds a small delete form with a hidden id.
function coordDeleteForm(string $actionUrl, string $id, string $message, string $title = 'Delete'): string {
    // Opens a POST form with the action URL and the confirm message attribute.
    return '<form method="post" action="' . htmlspecialchars($actionUrl) . '" style="display:inline;" data-confirm-form data-confirm-message="' . htmlspecialchars($message) . '">'
        // Adds the hidden delete_id field that carries the record id.
        . '<input type="hidden" name="delete_id" value="' . htmlspecialchars($id) . '">'
        // Adds the delete button that asks for confirmation before submitting.
        . '<button type="button" class="btn btn-sm btn-danger" data-confirm-trigger title="' . htmlspecialchars($title) . '"><i class="fas fa-trash"></i> Delete</button>'
        // Closes the delete form markup.
        . '</form>';
// Closes coordDeleteForm().
}

// Starts postedDeleteId(), which reads the delete id from a POST request.
function postedDeleteId(): string {
    // Only reads the id when the current request is a POST request.
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        // Returns an empty id for non-POST requests.
        return '';
    // Closes the request method check.
    }
    // Returns the trimmed delete_id value posted by the form.
    return trim((string) ($_POST['delete_id'] ?? ''));
// Closes postedDeleteId().
}

// Starts coordDeleteLink(), which builds a delete button for a URL.
function coordDeleteLink(string $href, string $message): string {
    // Returns a button carrying the delete URL and confirm message in data attributes.
    return '<button type="button" class="btn btn-sm btn-danger" title="Delete" data-confirm-url="' . htmlspecialchars($href) . '" data-confirm-message="' . htmlspecialchars($message) . '"><i class="fas fa-trash"></i> Delete</button>';
// Closes coordDeleteLink().
}

// Starts archiveStudentRecord(), which soft-deletes a student record.
function archiveStudentRecord(string $id): void {
    // Loads all student records from the session store.
    $students = storeGet('students');
    // Loops by reference over the students to find the matching id.
    foreach ($students as &$student) {
        // Checks whether this student has the requested id.
        if ((string) ($student['id'] ?? '') === $id) {
            // Stamps the archive time in ISO format instead of removing the record.
            $student['archivedAt'] = date('c');
            // Stops looping after the student is archived.
            break;
        // Closes the student id check.
        }
    // Closes the student loop.
    }
    // Breaks the reference after the loop.
    unset($student);
    // Saves the updated students list.
    storeSet('students', $students);
// Closes archiveStudentRecord().
}

// Starts archiveApplicationRecord(), which soft-deletes an application.
function archiveApplicationRecord(int $id): void {
    // Loads all application records from the session store.
    $apps = storeGet('applications');
    // Loops by reference over the applications to find the matching id.
    foreach ($apps as &$app) {
        // Checks whether this application has the requested id.
        if ((int) ($app['id'] ?? 0) === $id) {
            // Stamps the archive time so the application is hidden but kept.
            $app['archivedAt'] = date('c');
            // Stops looping after the application is archived.
            break;
        // Closes the application id check.
        }
    // Closes the application loop.
    }
    // Breaks the reference after the loop.
    unset($app);
    // Saves the updated applications list.
    storeSet('applications', $apps);
// Closes archiveApplicationRecord().
}

// Starts deleteApplicationRecord(), which removes an application from the store.
function deleteApplicationRecord(int $id): void {
    // Keeps only applications whose id is not the one being deleted.
    storeSet('applications', array_values(array_filter(storeGet('applications'), static function ($a) use ($id) {
        // Returns true for records that should be kept.
        return (int) $a['id'] !== $id;
    // Closes the filter callback and saves the filtered list.
    })));
// Closes deleteApplicationRecord().
}
