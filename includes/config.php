<?php /* Starts the PHP file that holds shared settings and helper functions. */ ?>
<?php


// Sets the full site name shown in page titles and headers.
define('SITE_NAME', 'CSITE Graduate School Application');
// Sets the shorter site name used where space is limited.
define('SITE_SHORT', 'CSITE Graduate School Application');
// Sets the one-line tagline that describes the system.
define('SITE_TAGLINE', 'Capstone, Seminar Paper and Thesis Presentation Application System');
// Served from the domain root on InfinityFree, from a subfolder on local XAMPP.
// Only works out the base URL once, unless it was already defined.
if (!defined('BASE_URL')) {
    // Reads the host name of the current request in lowercase.
    $csiteHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    // Checks for a BASE_URL value supplied by the server environment.
    $csiteEnvBase = getenv('BASE_URL');
    // Uses the environment value when one was actually provided.
    if ($csiteEnvBase !== false) {
        // Sets BASE_URL from the environment, without a trailing slash.
        define('BASE_URL', rtrim($csiteEnvBase, '/'));
    // On InfinityFree the site sits at the domain root, so the base is empty.
    } elseif (str_contains($csiteHost, 'infinityfree')) {
        // Sets an empty base URL for the InfinityFree deployment.
        define('BASE_URL', '');
    // Otherwise the folder under htdocs has to be worked out.
    } else {
        // Explains that the served folder is detected instead of assumed.
        /*
         * Work out the folder this copy of the project is served from instead
         * of assuming the project name, so every copy under htdocs keeps its
         * own URL and its links never cross into another copy.
         */
        // Gets the absolute path of the project folder with forward slashes.
        $csiteProjectRoot = str_replace('\\', '/', (string) realpath(dirname(__DIR__)));
        // Gets the absolute path of the web server document root.
        $csiteDocRoot = str_replace('\\', '/', (string) realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? '')));
        // Starts with an empty base path that is filled in below.
        $csiteBase = '';
        // Tracks whether the base path has been found yet.
        $csiteResolved = false;

        // Only compares the paths when both were resolved successfully.
        if ($csiteProjectRoot !== '' && $csiteDocRoot !== '') {
            // Removes any trailing slash from the document root.
            $csiteDocBase = rtrim($csiteDocRoot, '/');
            // Checks whether the project folder sits inside the document root.
            if ($csiteDocBase !== '' && strpos($csiteProjectRoot, $csiteDocBase . '/') === 0) {
                // Keeps the part of the project path below the document root.
                $csiteBase = substr($csiteProjectRoot, strlen($csiteDocBase));
                // Marks the base path as resolved.
                $csiteResolved = true;
            // Handles the case where the project folder is the document root.
            } elseif ($csiteProjectRoot === $csiteDocBase) {
                // Uses an empty base path for a project at the root.
                $csiteBase = '';
                // Marks the base path as resolved.
                $csiteResolved = true;
            // Ends the elseif branch for an equal document root.
            }
        // Ends the check on the project and document root paths.
        }

        // Falls back to another method when the paths did not resolve.
        if (!$csiteResolved) {
            // Fallback for servers that do not report the document root.
            // Fallback when the document root is not reported: take the folder
            // It uses the folder above student/, coordinator/, includes/ or public/.
            // above the student/, coordinator/, includes/ or public/ directory.
            // Reads the current script path with forward slashes.
            $csiteScript = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
            // Tries each known folder name that can appear in the script path.
            foreach (['/student/', '/coordinator/', '/includes/', '/public/'] as $csiteMarker) {
                // Finds where that folder name appears in the script path.
                $csitePos = strpos($csiteScript, $csiteMarker);
                // Stops at the first marker that was found.
                if ($csitePos !== false) {
                    // Everything before the marker is the base path.
                    $csiteBase = substr($csiteScript, 0, $csitePos);
                    // Marks the base path as resolved.
                    $csiteResolved = true;
                    // Stops looking once the base path is known.
                    break;
                // Ends the check for this marker folder.
                }
            // Ends the loop over the marker folders.
            }
            // Last resort: use the folder of the script itself.
            if (!$csiteResolved && $csiteScript !== '' && $csiteScript[0] === '/') {
                // Takes the script's own directory as the base path.
                $csiteBase = rtrim(str_replace('\\', '/', dirname($csiteScript)), '/');
            // Ends the last-resort check.
            }
        // Ends the fallback block for an unresolved base path.
        }

        // Defines the final base URL without a trailing slash.
        define('BASE_URL', rtrim($csiteBase, '/'));
    // Ends the else branch for hosts that are not InfinityFree.
    }
// Ends the BASE_URL definition block.
}

// Sets the main brand colour used across the site.
define('COLOR_PRIMARY', '#060297');
// Sets the accent colour used for highlights and buttons.
define('COLOR_ACCENT', '#FFB82B');
// Sets the darker accent colour used when hovering.
define('COLOR_ACCENT_HOVER', '#e5a526');

// Defines the three presentation stages of the thesis track.
define('STAGES_THESIS', [
    // Maps the concept stage key to its display label.
    'concept'  => 'Concept Paper Presentation',
    // Maps the proposal stage key to its display label.
    'proposal' => 'Thesis Proposal Presentation',
    // Maps the final defense stage key to its display label.
    'final'    => 'Final Thesis Defense',
// Ends the thesis stage labels.
]);

// Defines the two presentation stages of the capstone track.
define('STAGES_CAPSTONE', [
    // Maps the capstone proposal stage key to its label.
    'proposal' => 'Capstone Proposal Presentation',
    // Maps the capstone final stage key to its label.
    'final'    => 'Final Capstone Presentation',
// Ends the capstone stage labels.
]);

// Defines the two presentation stages of the seminar paper track.
define('STAGES_SEMINAR', [
    // Maps the seminar proposal stage key to its label.
    'proposal' => 'Seminar Paper Proposal Presentation',
    // Maps the seminar final stage key to its label.
    'final'    => 'Final Seminar Paper Presentation',
// Ends the seminar stage labels.
]);

// Defines every application status with its label and CSS class.
define('STATUSES', [
    // Status pending shows the label Pending with the pending style.
    'pending'                  => ['label' => 'Pending',                  'class' => 'status-pending'],
    // Status not_started uses the pending look.
    'not_started'              => ['label' => 'Not Started',              'class' => 'status-pending'],
    // Status draft uses the pending look.
    'draft'                    => ['label' => 'Draft',                    'class' => 'status-pending'],
    // Status submitted uses the submitted style.
    'submitted'                => ['label' => 'Submitted',                'class' => 'status-submitted'],
    // Status under_review uses the review style.
    'under_review'             => ['label' => 'Under Review',             'class' => 'status-review'],
    // Status for_payment uses the review style.
    'for_payment'              => ['label' => 'For Payment',              'class' => 'status-review'],
    // Status payment_recorded uses the approved style.
    'payment_recorded'         => ['label' => 'Payment Recorded',         'class' => 'status-approved'],
    // Status ready_for_presentation uses the ready style.
    'ready_for_presentation'   => ['label' => 'Ready for Presentation',   'class' => 'status-ready'],
    // Status scheduled shows the scheduled presentation style.
    'scheduled'                => ['label' => 'Scheduled for Presentation', 'class' => 'status-scheduled'],
    // Status approved uses the approved style.
    'approved'                 => ['label' => 'Approved',                 'class' => 'status-approved'],
    // Status confirmed uses the confirmed style.
    'confirmed'                => ['label' => 'Confirmed',                'class' => 'status-confirmed'],
    // Status requires_revision uses the revision style.
    'requires_revision'        => ['label' => 'Requires Revision',        'class' => 'status-revision'],
    // Status completed shows Presentation Complete.
    'completed'                => ['label' => 'Presentation Complete',    'class' => 'status-completed'],
    // Status verified uses the approved style.
    'verified'                 => ['label' => 'Verified',                 'class' => 'status-approved'],
    // Status incomplete uses the revision style.
    'incomplete'               => ['label' => 'Incomplete',               'class' => 'status-revision'],
// Ends the status definitions.
]);

/**
 * The application statuses the coordinator may set from the Applications -> Edit
 * page. These are the states the coordinator works with; the older prototype
 * states (for_payment, payment_recorded, approved, requires_revision) stay in
 * the STATUSES map so that records written before the change still render.
 */
// Lists the statuses a coordinator may choose on the edit page.
define('COORDINATOR_STATUSES', [
    // Allows the submitted status.
    'submitted',
    // Allows the under review status.
    'under_review',
    // Allows the ready for presentation status.
    'ready_for_presentation',
    // Allows the scheduled status.
    'scheduled',
    // Allows the completed status.
    'completed',
// Ends the list of coordinator statuses.
]);

// Maps every program code to its full program name.
define('PROGRAMS', [
    // MSCS is the Master of Science in Computer Science.
    'MSCS'      => 'Master of Science in Computer Science',
    // MSLIS is the Master of Library and Information Science.
    'MSLIS'     => 'Master of Library and Information Science',
    // MIT is the Master in Information Technology.
    'MIT'       => 'Master in Information Technology',
    // MLIS is the Master in Library and Information System.
    'MLIS'      => 'Master in Library and Information System',
    // MATH is the Master in Mathematics Education.
    'MATH'      => 'Master in Mathematics Education',
    // MSED_CHEM is Science Education major in Chemistry.
    'MSED_CHEM' => 'Master in Science Education major in Chemistry',
    // MSED_GS is Science Education major in General Science.
    'MSED_GS'   => 'Master in Science Education major in General Science',
    // MSED_BIO is Science Education major in Biology.
    'MSED_BIO'  => 'Master in Science Education major in Biology',
    // MSED_PHY is Science Education major in Physics.
    'MSED_PHY'  => 'Master in Science Education major in Physics',
    // MSME is the Master of Science in Mechanical Engineering.
    'MSME'      => 'Master of Science in Mechanical Engineering',
// Ends the program name map.
]);

/*
 * The tracks every course can follow.
 *
 * A course does not always belong to a single track: the Master of Science in
 * Education courses and the Master of Science in Mechanical Engineering can be
 * followed as a Capstone or as a Seminar Paper, so the student picks the
 * track during the account setup. The table program_tracks holds the same
 * list; this is the list the code falls back on.
 */
// Lists the tracks each program can follow as a code fallback.
define('PROGRAM_TRACKS', [
    // MSCS students follow the thesis track.
    'MSCS'      => ['thesis'],
    // MSLIS students follow the thesis track.
    'MSLIS'     => ['thesis'],
    // MIT students follow the capstone track.
    'MIT'       => ['capstone'],
    // MLIS students follow the capstone track.
    'MLIS'      => ['capstone'],
    // MATH students follow the seminar track.
    'MATH'      => ['seminar'],
    // MSED_CHEM students may choose capstone or seminar.
    'MSED_CHEM' => ['capstone', 'seminar'],
    // MSED_GS students may choose capstone or seminar.
    'MSED_GS'   => ['capstone', 'seminar'],
    // MSED_BIO students may choose capstone or seminar.
    'MSED_BIO'  => ['capstone', 'seminar'],
    // MSED_PHY students may choose capstone or seminar.
    'MSED_PHY'  => ['capstone', 'seminar'],
    // MSME students may choose capstone or seminar.
    'MSME'      => ['capstone', 'seminar'],
// Ends the program track map.
]);

// Sample student record used for demos and prototypes.
$mockStudent = [
    // Sample student ID.
    'id'           => '2024-001',
    // Sample name in Last, First format.
    'name'         => 'Torres, Robbie Ryan A',
    // Sample student email address.
    'email'        => 'rtorres@adzu.edu.ph',
    // Sample program code.
    'program'      => 'MSCS',
    // Sample full program name.
    'program_name' => 'Master of Science in Computer Science',
    // Sample track for the student.
    'track'        => 'thesis',
    // Sample enrollment date.
    'enroll_date'  => '2024-08-15',
    // Sample stage the student is currently working on.
    'current_stage'=> 'proposal',
    // Sample application status.
    'status'       => 'under_review',
    // Sample research title.
    'title'        => 'AI-Powered Academic Advising System for Graduate Students',
    // Sample adviser name.
    'adviser'      => 'Dr. Maria Santos',
    // Sample deadline for completing the program.
    'completion_deadline' => '2027-08-15',
// Ends the sample student record.
];

// Sample coordinator record used for demos.
$mockCoordinator = [
    // Sample coordinator ID.
    'id'   => 'GPC-001',
    // Sample coordinator name.
    'name' => 'Opinion, Precious',
    // Sample coordinator role.
    'role' => 'Graduate Program Coordinator – CSITE',
    // Sample coordinator email.
    'email'=> 'gpc-csite@adzu.edu.ph',
// Ends the sample coordinator record.
];

// Builds the coloured badge HTML for a given status key.
function statusBadge(string $status): string {
    // Looks up the status details, falling back to a plain label.
    $info = STATUSES[$status] ?? ['label' => ucfirst($status), 'class' => 'status-pending'];
    // Returns the badge with the escaped label and its CSS class.
    return '<span class="status-badge ' . $info['class'] . '">' . htmlspecialchars($info['label']) . '</span>';
// Ends the statusBadge function.
}

// Returns the ordered stages for a track, from the database if possible.
function getStagesForTrack(string $track): array {
    // Thesis stages are the default fallback.
    $fallback = STAGES_THESIS;
    // Uses capstone stages when that track was asked for.
    if ($track === 'capstone') {
        // Switches the fallback to the capstone stages.
        $fallback = STAGES_CAPSTONE;
    // Uses seminar stages when that track was asked for.
    } elseif ($track === 'seminar') {
        // Switches the fallback to the seminar stages.
        $fallback = STAGES_SEMINAR;
    // Ends the track selection.
    }

    // Without the database class the built-in list is returned.
    if (!class_exists('DB')) {
        // Returns the fallback stage list.
        return $fallback;
    // Ends the database availability check.
    }

    // Tries to read the stages from the database.
    try {
        // Prepares a query for the stages of this track.
        $stmt = DB::getConnection()->prepare(
            // Query joins stages to their track and orders them for display.
            'SELECT workflow_stages.stage_key, workflow_stages.stage_label
             FROM workflow_stages
             INNER JOIN tracks ON tracks.track_id = workflow_stages.track_id
             WHERE tracks.track_code = :track
             ORDER BY workflow_stages.stage_order, workflow_stages.stage_id'
        // Ends the prepared query text.
        );
        // Runs the query with the track code as the parameter.
        $stmt->execute(['track' => $track]);
        // Collects stage key and label pairs.
        $stages = [];
        // Loops over every stage row returned by the database.
        foreach ($stmt->fetchAll() as $stage) {
            // Stores each stage label under its stage key.
            $stages[$stage['stage_key']] = $stage['stage_label'];
        // Ends the loop over stage rows.
        }
        // Returns the database stages, or the fallback when none exist.
        return $stages ?: $fallback;
    // Ignores database errors so the page still loads.
    } catch (Throwable $e) {
        // Falls back to the built-in stage list.
        return $fallback;
    // Ends the catch block.
    }
// Ends the getStagesForTrack function.
}

/**
 * The tracks a course can follow.
 *
 * The student picks the track during the account setup, so a course that can
 * be followed in more than one way offers all of them. The list comes from
 * the table program_tracks; the list in the code is the fallback for when the
 * table is not there yet.
 *
 * @return array<int, string> the track codes
 */
// Returns the track codes a program can follow.
function getTracksForProgram(string $program): array {
    // Trims spaces from the program code or name.
    $trimmed = trim($program);
    // No program was given, so there are no tracks.
    if ($trimmed === '') {
        // Returns an empty track list.
        return [];
    // Ends the empty program check.
    }

    // Accepts either a program code or its full name as input.
    // The name of a course is accepted as well as its code.
    // Starts by assuming the value is already the program code.
    $code = $trimmed;
    // Checks each known program name against the given value.
    foreach (PROGRAMS as $programCode => $label) {
        // Matches the full program name ignoring letter case.
        if (strcasecmp($label, $trimmed) === 0) {
            // Switches to the program code that matched.
            $code = $programCode;
            // Stops searching once the code is known.
            break;
        // Ends the program name check.
        }
    // Ends the loop over known programs.
    }

    // Tries the database tables when they are available.
    if (class_exists('DB')) {
        // Starts the database lookup.
        try {
            // Prepares a query for the tracks linked to the program.
            $stmt = DB::getConnection()->prepare(
                // Query joins programs, program_tracks and tracks by program code.
                'SELECT t.track_code
                   FROM programs p
                   INNER JOIN program_tracks pt ON pt.program_id = p.program_id
                   INNER JOIN tracks t ON t.track_id = pt.track_id
                  WHERE p.program_code = :code
                  ORDER BY pt.is_primary DESC, t.track_id'
            // Ends the prepared query text.
            );
            // Runs the query with the program code.
            $stmt->execute(['code' => $code]);
            // Collects the track codes found for the program.
            $tracks = [];
            // Loops over each joined row returned.
            foreach ($stmt->fetchAll() as $row) {
                // Normalises the track code to lowercase.
                $track = strtolower(trim((string) $row['track_code']));
                // Skips blanks and tracks already added.
                if ($track !== '' && !in_array($track, $tracks, true)) {
                    // Adds the new track code to the list.
                    $tracks[] = $track;
                // Ends the check on this row.
                }
            // Ends the loop over rows.
            }
            // Returns the database list when it is not empty.
            if ($tracks) {
                // Returns the tracks found in the database.
                return $tracks;
            // Ends the non-empty check.
            }
        // Falls back to the coded list if the query fails.
        } catch (Throwable $e) {
            // The fallback list below is used when the tables are missing.
            // Keep the list of the code when the table is not available.
        // Ends the catch block.
        }
    // Ends the database branch.
    }

    // Returns the built-in tracks for the code, or an empty list.
    return PROGRAM_TRACKS[$code] ?? [];
// Ends the getTracksForProgram function.
}

/**
 * The track a course belongs to when it can only be followed in one way.
 *
 * For a course with more than one track this is the track that is named first,
 * so the older code keeps working. The track a student really follows is the
 * one in their own record.
 */
// Returns the main track for a program.
function getTrackForProgram(string $program): string {
    // Trims spaces from the program value.
    $trimmed = trim($program);

    // Looks the program up in the database when possible.
    if ($trimmed !== '' && class_exists('DB')) {
        // Starts the database lookup.
        try {
            // Prepares a query for the track linked to the program.
            $stmt = DB::getConnection()->prepare(
                // Query matches the program by code, exact name, or partial name.
                'SELECT tracks.track_code
                 FROM programs
                 INNER JOIN tracks ON tracks.track_id = programs.track_id
                 WHERE programs.program_code = :program
                    OR programs.program_name = :program
                    OR programs.program_name LIKE :program_like
                 LIMIT 1'
            // Ends the prepared query text.
            );
            // Runs the query with the program values as parameters.
            $stmt->execute([
                // Binds the trimmed value to the code and exact name tests.
                'program' => $trimmed,
                // Binds a wildcard version for the partial name match.
                'program_like' => '%' . $trimmed . '%',
            // Ends the parameter array.
            ]);
            // Reads the first track code returned by the query.
            $databaseTrack = $stmt->fetchColumn();
            // Uses the database track when a valid code was returned.
            if (is_string($databaseTrack) && $databaseTrack !== '') {
                // Returns the track code in lowercase.
                return strtolower($databaseTrack);
            // Ends the valid code check.
            }
        // Ignores errors when the new tables are not set up yet.
        } catch (Throwable $e) {
            // The code mapping below is the emergency fallback.
            // Keep the existing program mapping when the new tables are unavailable.
        // Ends the catch block.
        }
    // Ends the database branch.
    }

    // Asks for the list of tracks for this program.
    $tracks = getTracksForProgram($trimmed);
    // Uses the first track when the list is not empty.
    if ($tracks) {
        // Returns the first track as the main track.
        return $tracks[0];
    // Ends the non-empty check.
    }

    // Detects a capstone track from text in the program name.
    if (stripos($trimmed, 'capstone') !== false) {
        // Returns the capstone track.
        return 'capstone';
    // Ends the capstone text check.
    }
    // Detects a seminar track from text in the program name.
    if (stripos($trimmed, 'seminar') !== false) {
        // Returns the seminar track.
        return 'seminar';
    // Ends the seminar text check.
    }
    // Detects a thesis track from text in the program name.
    if (stripos($trimmed, 'thesis') !== false) {
        // Returns the thesis track.
        return 'thesis';
    // Ends the thesis text check.
    }
    // Defaults to the thesis track when nothing else matched.
    return 'thesis';
// Ends the getTrackForProgram function.
}

// Returns the friendly label for a track code.
function getTrackLabel(string $track): string {
    // Capstone is shown as Capstone.
    if ($track === 'capstone') return 'Capstone';
    // The seminar track is shown as Seminar Paper.
    if ($track === 'seminar') return 'Seminar Paper';
    // Every other track is labelled Thesis.
    return 'Thesis';
// Ends the getTrackLabel function.
}

// Returns the active CSS class when the page matches the current one.
function navActive(string $page, string $current): string {
    // Compares the page key with the current page key.
    return $page === $current ? 'active' : '';
// Ends the navActive function.
}

// Builds a full URL for a file inside the assets folder.
function asset(string $path): string {
    // Joins the base URL with the assets path.
    return BASE_URL . '/assets/' . ltrim($path, '/');
// Ends the asset function.
}

// Builds a full URL for a path inside the site.
function url(string $path): string {
    // Joins the base URL with the given path.
    return BASE_URL . '/' . ltrim($path, '/');
// Ends the url function.
}

// Builds the download URL for a paper stored in the papers folder.
function papersUrl(string $folder, string $file): string {
    // Returns nothing when the folder or file name is missing.
    if ($folder === '' || $file === '') {
        // Returns an empty URL.
        return '';
    // Ends the missing name check.
    }
    // Encodes the folder and file names for a safe URL.
    return asset('papers/' . rawurlencode($folder) . '/' . rawurlencode($file));
// Ends the papersUrl function.
}

// Returns the full workflow definition for a track.
function getWorkflow(string $track): array {
    // Folder name that holds the thesis template files.
    $T = 'THESIS';
    // Folder name that holds the capstone template files.
    $C = 'CAPSTONE';
    // Folder name that holds the seminar paper template files.
    $S = 'SEMINAR PAPER';

    // Starts the thesis workflow with its stages and references.
    $thesis = [
        // Starts the list of thesis stages.
        'stages' => [
            // Starts the thesis concept paper stage.
            [
                // Stage key used in the database and in links.
                'key' => 'concept',
                // Stage name shown to students and coordinators.
                'label' => 'Concept Paper',
                // Short name used when building document type names.
                'shortLabel' => 'Concept Paper',
                // Documents the student must upload for this stage.
                'documents' => [
                    // Concept paper template file in CSITE thesis format.
                    ['id' => 'concept_paper', 'label' => 'Concept Paper', 'file' => 'GRADUATE SCHOOL - CSITE - THESIS FORMAT.docx', 'folder' => $T, 'type' => 'template', 'description' => 'Written concept paper following CSITE thesis format'],
                    // Receipt slot, filled by the student's own uploaded proof.
                    ['id' => 'concept_receipt', 'label' => 'Official Receipt / Payment Proof', 'file' => '', 'folder' => '', 'type' => 'receipt', 'description' => 'Scanned copy of official receipt from Graduate School'],
                // Ends the concept stage document list.
                ],
                // Adviser endorsement form for the concept paper.
                'adviserEndorsementForm' => ['id' => 'concept_adviser', 'label' => 'Concept Paper Adviser Endorsement Form', 'file' => '1 CONCEPT PAPER ADVISER ENDORSEMENT FORM_.docx', 'folder' => $T, 'type' => 'form', 'description' => 'Signed by your research adviser'],
                // Graduate School endorsement form for the concept paper.
                'gradSchoolEndorsementForm' => ['id' => 'concept_gradschool', 'label' => 'Concept Paper Endorsement to Graduate School', 'file' => '2 CONCEPT PAPER ENDORSEMENT TO GRADSCHOOL.docx', 'folder' => $T, 'type' => 'form', 'description' => 'Complete this form and upload it for Graduate School review'],
            // Ends the concept paper stage.
            ],
            // Starts the thesis proposal stage.
            [
                // Stage key for the proposal stage.
                'key' => 'proposal',
                // Stage name shown for the proposal.
                'label' => 'Thesis Proposal',
                // Short name used for the proposal documents.
                'shortLabel' => 'Proposal',
                // Documents required for the proposal stage.
                'documents' => [
                    // Thesis proposal template file.
                    ['id' => 'proposal_paper', 'label' => 'Thesis Proposal', 'file' => 'GRADUATE SCHOOL - CSITE - THESIS FORMAT.docx', 'folder' => $T, 'type' => 'template', 'description' => 'Thesis proposal following CSITE thesis format'],
                    // Receipt slot for the proposal payment.
                    ['id' => 'proposal_receipt', 'label' => 'Official Receipt / Payment Proof', 'file' => '', 'folder' => '', 'type' => 'receipt', 'description' => 'Scanned copy of official receipt from Graduate School'],
                // Ends the proposal document list.
                ],
                // Adviser endorsement form for the thesis proposal.
                'adviserEndorsementForm' => ['id' => 'proposal_adviser', 'label' => 'Thesis Proposal Adviser Endorsement Form', 'file' => '3 THESIS PROPOSAL ADVISER ENDORSEMENT FORM.docx', 'folder' => $T, 'type' => 'form', 'description' => 'Signed by your research adviser'],
                // Graduate School endorsement form for the thesis proposal.
                'gradSchoolEndorsementForm' => ['id' => 'proposal_gradschool', 'label' => 'Thesis Proposal Endorsement to Graduate School', 'file' => '4 THESIS PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'folder' => $T, 'type' => 'form', 'description' => 'Complete this form and upload it for Graduate School review'],
            // Ends the thesis proposal stage.
            ],
            // Starts the final thesis stage.
            [
                // Stage key for the final defense.
                'key' => 'final',
                // Stage name shown for the final thesis.
                'label' => 'Final Thesis',
                // Short name used for the final thesis documents.
                'shortLabel' => 'Final Thesis',
                // Documents required for the final thesis stage.
                'documents' => [
                    // Final thesis template file.
                    ['id' => 'final_paper', 'label' => 'Final Thesis', 'file' => 'GRADUATE SCHOOL - CSITE - THESIS FORMAT.docx', 'folder' => $T, 'type' => 'template', 'description' => 'Complete final thesis following CSITE format'],
                    // Receipt slot for the final defense payment.
                    ['id' => 'final_receipt', 'label' => 'Official Receipt / Payment Proof', 'file' => '', 'folder' => '', 'type' => 'receipt', 'description' => 'Scanned copy of official receipt from Graduate School'],
                // Ends the final thesis document list.
                ],
                // Adviser endorsement form for the final thesis.
                'adviserEndorsementForm' => ['id' => 'final_adviser', 'label' => 'Final Thesis Adviser Endorsement Form', 'file' => '5 FINAL THESIS ADVISER ENDORSEMENT FORM.docx', 'folder' => $T, 'type' => 'form', 'description' => 'Signed by your research adviser'],
                // Graduate School endorsement form for the final thesis.
                'gradSchoolEndorsementForm' => ['id' => 'final_gradschool', 'label' => 'Final Thesis Endorsement to Graduate School', 'file' => '6 FINAL THESIS ENDORSEMENT TO GRADSCHOOL.docx', 'folder' => $T, 'type' => 'form', 'description' => 'Complete this form and upload it for Graduate School review'],
            // Ends the final thesis stage.
            ],
        // Ends the thesis stage list.
        ],
        // Reference files that apply to every thesis stage.
        'refs' => [
            // Official Graduate School thesis format guide.
            ['id' => 'thesis_format', 'label' => 'Graduate School Thesis Format', 'file' => 'GRADUATE SCHOOL - CSITE - THESIS FORMAT.docx', 'folder' => $T, 'type' => 'template', 'description' => 'Official format guide for all thesis documents'],
            // Final thesis cover page template for 2026.
            ['id' => 'thesis_cover', 'label' => 'Final Thesis Cover Page', 'file' => 'FINAL THESIS COVER PAGE v2026.docx', 'folder' => $T, 'type' => 'template', 'description' => 'Cover page template (v2026)'],
        // Ends the thesis reference list.
        ],
    // Ends the thesis workflow definition.
    ];

    // Starts the capstone workflow with its stages and references.
    $capstone = [
        // Starts the list of capstone stages.
        'stages' => [
            // Starts the capstone proposal stage.
            [
                // Stage key for the capstone proposal.
                'key' => 'proposal',
                // Stage name shown for the capstone proposal.
                'label' => 'Capstone Proposal',
                // Short name used for proposal documents.
                'shortLabel' => 'Proposal',
                // Documents required for the capstone proposal.
                'documents' => [
                    // Capstone proposal template file from MIT.
                    ['id' => 'cp_proposal', 'label' => 'Capstone Proposal', 'file' => 'MIT GradSchool CAPSTONE PROJECT PROPOSAL TEMPLATE v2025.docx', 'folder' => $C, 'type' => 'template', 'description' => 'Capstone proposal following MIT template (v2025)'],
                    // Receipt slot for the capstone proposal payment.
                    ['id' => 'cp_receipt', 'label' => 'Official Receipt / Payment Proof', 'file' => '', 'folder' => '', 'type' => 'receipt', 'description' => 'Scanned copy of official receipt from Graduate School'],
                // Ends the capstone proposal document list.
                ],
                // Adviser endorsement form for the capstone proposal.
                'adviserEndorsementForm' => ['id' => 'cp_adviser', 'label' => 'Capstone Adviser Endorsement Form – Proposal', 'file' => '1 CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'folder' => $C, 'type' => 'form', 'description' => 'Signed by your capstone adviser'],
                // Graduate School endorsement form for the proposal.
                'gradSchoolEndorsementForm' => ['id' => 'cp_gradschool', 'label' => 'Capstone Proposal Endorsement to Graduate School', 'file' => '2 CAPSTONE PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'folder' => $C, 'type' => 'form', 'description' => 'Complete this form and upload it for Graduate School review'],
            // Ends the capstone proposal stage.
            ],
            // Starts the final capstone stage.
            [
                // Stage key for the final capstone.
                'key' => 'final',
                // Stage name shown for the final capstone.
                'label' => 'Final Capstone',
                // Short name used for final capstone documents.
                'shortLabel' => 'Final Capstone',
                // Documents required for the final capstone.
                'documents' => [
                    // Final capstone project document template.
                    ['id' => 'cf_paper', 'label' => 'Final Capstone', 'file' => 'MIT GradSchool CAPSTONE PROJECT PROPOSAL TEMPLATE v2025.docx', 'folder' => $C, 'type' => 'template', 'description' => 'Final capstone project document'],
                    // Receipt slot for the final capstone payment.
                    ['id' => 'cf_receipt', 'label' => 'Official Receipt / Payment Proof', 'file' => '', 'folder' => '', 'type' => 'receipt', 'description' => 'Scanned copy of official receipt from Graduate School'],
                // Ends the final capstone document list.
                ],
                // Adviser endorsement form for the final capstone.
                'adviserEndorsementForm' => ['id' => 'cf_adviser', 'label' => 'Final Capstone Adviser Endorsement Form', 'file' => '3 FINAL CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'folder' => $C, 'type' => 'form', 'description' => 'Signed by your capstone adviser'],
                // Graduate School endorsement form for the final capstone.
                'gradSchoolEndorsementForm' => ['id' => 'cf_gradschool', 'label' => 'Final Capstone Endorsement to Graduate School', 'file' => '4 FINAL CAPSTONE ENDORSEMENT TO GRADSCHOOL.docx', 'folder' => $C, 'type' => 'form', 'description' => 'Complete this form and upload it for Graduate School review'],
            // Ends the final capstone stage.
            ],
        // Ends the capstone stage list.
        ],
        // Reference files that apply to every capstone stage.
        'refs' => [
            // Official MIT capstone proposal template.
            ['id' => 'cap_template', 'label' => 'Capstone Project Proposal Template', 'file' => 'MIT GradSchool CAPSTONE PROJECT PROPOSAL TEMPLATE v2025.docx', 'folder' => $C, 'type' => 'template', 'description' => 'Official MIT capstone proposal template (v2025)'],
            // MIT capstone project guidelines document.
            ['id' => 'cap_guidelines', 'label' => 'Capstone Guidelines', 'file' => 'MIT Capstone Guidelines.docx', 'folder' => $C, 'type' => 'template', 'description' => 'MIT capstone project guidelines'],
            // Final capstone cover page template for 2026.
            ['id' => 'cap_cover', 'label' => 'Final Capstone Cover Page', 'file' => 'FINAL CAPSTONE COVER PAGE v2026.docx', 'folder' => $C, 'type' => 'template', 'description' => 'Cover page template (v2026)'],
        // Ends the capstone reference list.
        ],
    // Ends the capstone workflow definition.
    ];

    // Starts the seminar paper workflow.
    $seminar = [
        // Starts the list of seminar paper stages.
        'stages' => [
            // Starts the seminar paper proposal stage.
            [
                // Stage key for the seminar proposal.
                'key' => 'proposal',
                // Stage name shown for the seminar proposal.
                'label' => 'Seminar Paper Proposal',
                // Short name used for proposal documents.
                'shortLabel' => 'Proposal',
                // Documents required for the seminar proposal.
                'documents' => [
                    // Seminar paper proposal template file.
                    ['id' => 'sp_proposal', 'label' => 'Seminar Paper Proposal', 'file' => 'MATH SEMINAR PAPER TEMPLATE FORMAT.docx', 'folder' => $S, 'type' => 'template', 'description' => 'Seminar paper proposal following MATH template format'],
                    // Receipt slot for the seminar proposal payment.
                    ['id' => 'sp_receipt', 'label' => 'Official Receipt / Payment Proof', 'file' => '', 'folder' => '', 'type' => 'receipt', 'description' => 'Scanned copy of official receipt from Graduate School'],
                // Ends the seminar proposal document list.
                ],
                // Adviser endorsement form for the seminar proposal.
                'adviserEndorsementForm' => ['id' => 'sp_adviser', 'label' => 'Seminar Paper Proposal Adviser Endorsement Form', 'file' => '1 SEMINAR PAPER PROPOSAL ADVISER ENDORSEMENT FORM .docx', 'folder' => $S, 'type' => 'form', 'description' => 'Signed by your research adviser'],
                // Graduate School endorsement form for the proposal.
                'gradSchoolEndorsementForm' => ['id' => 'sp_gradschool', 'label' => 'Seminar Paper Proposal Endorsement to Graduate School', 'file' => '2 SEMINAR PAPER PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'folder' => $S, 'type' => 'form', 'description' => 'Complete this form and upload it for Graduate School review'],
            // Ends the seminar paper proposal stage.
            ],
            // Starts the final seminar paper stage.
            [
                // Stage key for the final seminar paper.
                'key' => 'final',
                // Stage name shown for the final seminar paper.
                'label' => 'Final Seminar Paper',
                // Short name used for final paper documents.
                'shortLabel' => 'Final Paper',
                // Documents required for the final seminar paper.
                'documents' => [
                    // Final seminar paper template file.
                    ['id' => 'sf_paper', 'label' => 'Final Seminar Paper', 'file' => 'MATH SEMINAR PAPER TEMPLATE FORMAT.docx', 'folder' => $S, 'type' => 'template', 'description' => 'Complete final seminar paper'],
                    // Receipt slot for the final paper payment.
                    ['id' => 'sf_receipt', 'label' => 'Official Receipt / Payment Proof', 'file' => '', 'folder' => '', 'type' => 'receipt', 'description' => 'Scanned copy of official receipt from Graduate School'],
                // Ends the final seminar paper document list.
                ],
                // Adviser endorsement form for the final paper.
                'adviserEndorsementForm' => ['id' => 'sf_adviser', 'label' => 'Final Seminar Paper Adviser Endorsement Form', 'file' => '3 FINAL SEMINAR PAPER ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'folder' => $S, 'type' => 'form', 'description' => 'Signed by your research adviser'],
                // Graduate School endorsement form for the final paper.
                'gradSchoolEndorsementForm' => ['id' => 'sf_gradschool', 'label' => 'Final Seminar Paper Endorsement to Graduate School', 'file' => '2 SEMINAR PAPER PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'folder' => $S, 'type' => 'form', 'description' => 'Complete this form and upload it for Graduate School review'],
            // Ends the final seminar paper stage.
            ],
        // Ends the seminar paper stage list.
        ],
        // Reference files that apply to every seminar paper stage.
        'refs' => [
            // Official seminar paper template format.
            ['id' => 'sem_format', 'label' => 'Seminar Paper Format', 'file' => 'MATH SEMINAR PAPER TEMPLATE FORMAT.docx', 'folder' => $S, 'type' => 'template', 'description' => 'Official seminar paper template format'],
            // Final seminar paper cover page template for 2026.
            ['id' => 'sem_cover', 'label' => 'Final Seminar Paper Cover Page', 'file' => 'FINAL SEMINAR PAPER COVER PAGE v2026.docx', 'folder' => $S, 'type' => 'template', 'description' => 'Cover page template (v2026)'],
        // Ends the seminar paper reference list.
        ],
    // Ends the seminar paper workflow definition.
    ];

    // Returns the capstone workflow when the capstone track was asked for.
    if ($track === 'capstone') {
        // Loads the capstone stages, with database stages when available.
        return getDatabaseWorkflow($track, $capstone);
    // Ends the capstone branch.
    }
    // Returns the seminar workflow when the seminar track was asked for.
    if ($track === 'seminar') {
        // Loads the seminar stages, with database stages when available.
        return getDatabaseWorkflow($track, $seminar);
    // Ends the seminar branch.
    }
    // Thesis is the default workflow for any other track.
    return getDatabaseWorkflow($track, $thesis);
// Ends the getWorkflow function.
}

/**
 * The documents a student must submit for a given track and stage.
 * The list differs per track (thesis / capstone / seminar) and per stage, so
 * the coordinator's checklist always matches the requirements of the paper
 * actually under review.
 *
 * Each entry carries: label, kind (paper | adviser_endorsement | receipt |
 * grad_school_endorsement) and the document_type the student uploads for it.
 */
// Lists the documents a stage requires for a given track.
function requiredDocumentsForStage(string $track, string $stageKey): array {
    // Loads the workflow definition for the track.
    $workflow = getWorkflow($track);
    // Holds the stage that is being looked for.
    $stage = null;
    // Searches the workflow stages for the requested key.
    foreach ($workflow['stages'] as $candidate) {
        // Checks whether this stage has the requested key.
        if (($candidate['key'] ?? '') === $stageKey) {
            // Keeps the stage that matched.
            $stage = $candidate;
            // Stops searching once the stage is found.
            break;
        // Ends the key check.
        }
    // Ends the loop over stages.
    }
    // Unknown stage key, so nothing is required.
    if (!$stage) {
        // Returns an empty requirement list.
        return [];
    // Ends the missing stage check.
    }

    // Short stage name used in document type labels.
    $shortLabel = $stage['shortLabel'] ?? 'Paper';
    // List of required document entries to return.
    $required = [];
    // Adds each ordinary document of the stage.
    foreach (($stage['documents'] ?? []) as $doc) {
        // Checks whether this document is a payment receipt.
        $isReceipt = ($doc['type'] ?? '') === 'receipt';
        // Starts one required document entry.
        $required[] = [
            // Document name shown to the student.
            'label'   => $doc['label'] ?? '',
            // Kind of document: receipt or paper.
            'kind'    => $isReceipt ? 'receipt' : 'paper',
            // Document type the student uploads for it.
            'docType' => $isReceipt ? 'Official Receipt / Payment Proof' : ($shortLabel . ' Paper'),
        // Ends the required document entry.
        ];
    // Ends the loop over stage documents.
    }
    // Adds the adviser endorsement form when the stage has one.
    if (!empty($stage['adviserEndorsementForm']['label'])) {
        // Starts the adviser endorsement requirement.
        $required[] = [
            // Label of the adviser endorsement form.
            'label'   => $stage['adviserEndorsementForm']['label'],
            // Marks this as an adviser endorsement.
            'kind'    => 'adviser_endorsement',
            // Document type built from the stage short name.
            'docType' => $shortLabel . ' Adviser Endorsement Form',
        // Ends the adviser endorsement entry.
        ];
    // Ends the adviser form check.
    }
    // Adds the Graduate School endorsement form when present.
    if (!empty($stage['gradSchoolEndorsementForm']['label'])) {
        // Starts the Graduate School endorsement requirement.
        $required[] = [
            // Label of the Graduate School endorsement form.
            'label'   => $stage['gradSchoolEndorsementForm']['label'],
            // Marks this as a Graduate School endorsement.
            'kind'    => 'grad_school_endorsement',
            // Uses the form label as the document type.
            'docType' => $stage['gradSchoolEndorsementForm']['label'],
        // Ends the Graduate School endorsement entry.
        ];
    // Ends the Graduate School form check.
    }
    // Returns the full list of required documents.
    return $required;
// Ends the requiredDocumentsForStage function.
}

// Replaces workflow stages with the ones saved in the database.
function getDatabaseWorkflow(string $track, array $workflow): array {
    // Without the database class the coded workflow is used.
    if (!class_exists('DB')) {
        // Returns the coded workflow unchanged.
        return $workflow;
    // Ends the database availability check.
    }

    // Tries to read the saved stages for this track.
    try {
        // Prepares a query for the stages of this track.
        $stmt = DB::getConnection()->prepare(
            // Query joins stages to tracks and orders them for display.
            'SELECT workflow_stages.stage_key, workflow_stages.stage_label
             FROM workflow_stages
             INNER JOIN tracks ON tracks.track_id = workflow_stages.track_id
             WHERE tracks.track_code = :track
             ORDER BY workflow_stages.stage_order, workflow_stages.stage_id'
        // Ends the prepared query text.
        );
        // Runs the query with the track code.
        $stmt->execute(['track' => $track]);
        // Fetches all stage rows returned by the query.
        $databaseStages = $stmt->fetchAll();
        // Keeps the coded workflow when the database has no stages.
        if (!$databaseStages) {
            // Returns the original workflow.
            return $workflow;
        // Ends the empty result check.
        }

        // Indexes the coded stages by their key.
        $configuredStages = [];
        // Loops over the stages defined in code.
        foreach ($workflow['stages'] as $configuredStage) {
            // Stores each coded stage under its key.
            $configuredStages[$configuredStage['key']] = $configuredStage;
        // Ends the loop over coded stages.
        }

        // Collects the stages in database order.
        $stages = [];
        // Loops over the stage rows from the database.
        foreach ($databaseStages as $databaseStage) {
            // Reads the key of this database stage.
            $stageKey = $databaseStage['stage_key'];
            // Uses the coded stage, or builds an empty one when it is new.
            $stage = $configuredStages[$stageKey] ?? [
                // Uses the database stage key.
                'key' => $stageKey,
                // Uses the database stage label.
                'label' => $databaseStage['stage_label'],
                // Uses the database label as the short name too.
                'shortLabel' => $databaseStage['stage_label'],
                // New stages start with no documents.
                'documents' => [],
                // Empty adviser endorsement form placeholder.
                'adviserEndorsementForm' => ['file' => ''],
                // Empty Graduate School endorsement form placeholder.
                'gradSchoolEndorsementForm' => ['file' => ''],
            // Ends the new stage definition.
            ];
            // Overrides the label with the one saved in the database.
            $stage['label'] = $databaseStage['stage_label'];
            // Adds the stage to the ordered list.
            $stages[] = $stage;
        // Ends the loop over database stages.
        }
        // Replaces the workflow stages with the database order.
        $workflow['stages'] = $stages;
    // Falls back to the coded workflow if the query fails.
    } catch (Throwable $e) {
        // Returns the coded workflow.
        return $workflow;
    // Ends the catch block.
    }

    // Returns the workflow, now with database stages when found.
    return $workflow;
// Ends the getDatabaseWorkflow function.
}

// Builds the paper library rows from the workflow files.
function getPaperLibraryRows(): array {
    // Rows to display in the paper library.
    $rows = [];
    // Keeps track of files already added to avoid duplicates.
    $seen = [];

    // Helper that adds one paper row if it is new.
    $push = static function (array &$rows, array &$seen, array $doc, string $courseType, string $programs, string $stage, string $docType): void {
        // Builds a key from folder, file, stage and document type.
        $key = $doc['folder'] . '|' . $doc['file'] . '|' . $stage . '|' . $docType;
        // Skips files with no name and ones already listed.
        if ($doc['file'] === '' || isset($seen[$key])) {
            // Returns without adding the row.
            return;
        // Ends the duplicate check.
        }
        // Marks this file as listed.
        $seen[$key] = true;
        // Starts one row for the paper library table.
        $rows[] = [
            // Document name shown in the row.
            'label' => $doc['label'],
            // File name of the template or form.
            'file' => $doc['file'],
            // Folder that holds the file.
            'folder' => $doc['folder'],
            // Course type such as Thesis or Capstone.
            'courseType' => $courseType,
            // Program codes the file belongs to.
            'programs' => $programs,
            // Stage the file is used in.
            'stage' => $stage,
            // Kind of document: Template, Form or Reference.
            'docType' => $docType,
            // Download URL built from the folder and file.
            'url' => papersUrl($doc['folder'], $doc['file']),
        // Ends the paper library row.
        ];
    // Ends the push helper function.
    };

    // Loops over the three tracks with their course names and programs.
    foreach ([['thesis', 'Thesis', 'MSCS'], ['capstone', 'Capstone', 'MIT'], ['seminar', 'Seminar Paper', 'MATH']] as [$track, $course, $programs]) {
        // Loads the workflow definition for this track.
        $wf = getWorkflow($track);
        // Goes through every stage of the track.
        foreach ($wf['stages'] as $stage) {
            // Goes through every document of the stage.
            foreach ($stage['documents'] as $doc) {
                // Only template files are listed as downloadable papers.
                if (($doc['type'] ?? '') === 'template') {
                    // Builds a key for this template file.
                    $fileKey = $doc['folder'] . '|' . $doc['file'] . '|Template';
                    // Adds the template only if it is not already listed.
                    if (!isset($seen[$fileKey])) {
                        // Adds the template row to the library.
                        $push($rows, $seen, $doc, $course, $programs, $stage['label'], 'Template');
                        // Remembers the template so it is not added twice.
                        $seen[$fileKey] = true;
                    // Ends the template check.
                    }
                // Ends the loop over documents.
                }
            // Ends the loop over stages.
            }
            // Adds the adviser endorsement form as a Form row.
            $push($rows, $seen, $stage['adviserEndorsementForm'], $course, $programs, $stage['label'], 'Form');
            // Adds the Graduate School form as a Form row.
            $push($rows, $seen, $stage['gradSchoolEndorsementForm'], $course, $programs, $stage['label'], 'Form');
        // Ends the loop over stages for this track.
        }
        // Goes through the reference files of the track.
        foreach ($wf['refs'] as $doc) {
            // Builds a key for this reference file.
            $fileKey = $doc['folder'] . '|' . $doc['file'] . '|Reference';
            // Skips files already added as a template or reference.
            if (!isset($seen[$fileKey]) && !isset($seen[$doc['folder'] . '|' . $doc['file'] . '|Template'])) {
                // Adds the reference row for all stages.
                $push($rows, $seen, $doc, $course, $programs, 'All Stages', 'Reference');
                // Remembers the reference so it is not added twice.
                $seen[$fileKey] = true;
            // Ends the reference check.
            }
        // Ends the loop over reference files.
        }
    // Ends the loop over tracks.
    }

    // Returns all rows for the paper library page.
    return $rows;
// Ends the getPaperLibraryRows function.
}


// Starts a session only when one is not running yet.
if (session_status() !== PHP_SESSION_ACTIVE) {
    // Opens the session that stores the prototype data.
    session_start();
// Ends the session start check.
}

// Reads an array from the session, or the default when missing.
function storeGet(string $key, array $default = []): array {
    // Returns the stored value or the given default.
    return $_SESSION[$key] ?? $default;
// Ends the storeGet function.
}

// Writes an array into the session under a key.
function storeSet(string $key, array $value): void {
    // Stores the value in the session.
    $_SESSION[$key] = $value;
// Ends the storeSet function.
}

// Fills the session with demo data on the first visit.
function initPrototypeStore(): void {
    // Stops early when the demo data was already seeded.
    if (!empty($_SESSION['csite_seeded_v3'])) {
        // Returns without seeding again.
        return;
    // Ends the seeded check.
    }

    // Demo student records used by the prototype pages.
    $students = [
        // Demo student Robbie Ryan Torres on the thesis track.
        ['id' => 'seed_rtorres', 'email' => 'rtorres@adzu.edu.ph', 'firstName' => 'Robbie Ryan', 'lastName' => 'Torres', 'middleInitial' => 'A', 'age' => '24', 'gender' => 'Male', 'program' => 'MSCS', 'track' => 'thesis', 'trackLabel' => 'Thesis', 'enrollDate' => '2024-08-15', 'currentStage' => 'proposal', 'status' => 'under_review', 'title' => 'AI-Powered Academic Advising System for Graduate Students', 'adviser' => 'Dr. Maria Santos'],
        // Demo student John Matthew Gler on the capstone track.
        ['id' => 'seed_jgler', 'email' => 'jgler@adzu.edu.ph', 'firstName' => 'John Matthew', 'lastName' => 'Gler', 'middleInitial' => 'SJ', 'age' => '', 'gender' => '', 'program' => 'MIT', 'track' => 'capstone', 'trackLabel' => 'Capstone', 'enrollDate' => '2023-08-15', 'currentStage' => 'final', 'status' => 'submitted', 'title' => 'Cloud-Based Inventory Management', 'adviser' => 'Dr. Maria Santos'],
        // Demo student Sean Benedict Recto on the thesis track.
        ['id' => 'seed_srecto', 'email' => 'srecto@adzu.edu.ph', 'firstName' => 'Sean Benedict', 'lastName' => 'Recto', 'middleInitial' => 'D', 'age' => '', 'gender' => '', 'program' => 'MSCS', 'track' => 'thesis', 'trackLabel' => 'Thesis', 'enrollDate' => '2024-08-15', 'currentStage' => 'concept', 'status' => 'submitted', 'title' => 'Blockchain for Academic Records', 'adviser' => 'Dr. Maria Santos'],
        // Demo student Marc Laurence Arbillera on the capstone track.
        ['id' => 'seed_marbillera', 'email' => 'marbillera@adzu.edu.ph', 'firstName' => 'Marc Laurence', 'lastName' => 'Arbillera', 'middleInitial' => 'M', 'age' => '', 'gender' => '', 'program' => 'MIT', 'track' => 'capstone', 'trackLabel' => 'Capstone', 'enrollDate' => '2024-01-15', 'currentStage' => 'proposal', 'status' => 'approved', 'title' => 'E-Learning Platform for Rural Areas', 'adviser' => 'Dr. Maria Santos'],
        // Demo student Rhett Ushley Epino on the thesis track.
        ['id' => 'seed_repino', 'email' => 'repino@adzu.edu.ph', 'firstName' => 'Rhett Ushley', 'lastName' => 'Epino', 'middleInitial' => 'E', 'age' => '', 'gender' => '', 'program' => 'MSCS', 'track' => 'thesis', 'trackLabel' => 'Thesis', 'enrollDate' => '2022-08-15', 'currentStage' => 'final', 'status' => 'requires_revision', 'title' => 'Graduate Application System', 'adviser' => 'Dr. Maria Santos'],
    // Ends the demo student list.
    ];

    // Demo application records linked to the demo students.
    $applications = [
        // Demo application for Robbie Torres at the proposal stage.
        ['id' => 1, 'studentEmail' => 'rtorres@adzu.edu.ph', 'student' => 'Robbie Ryan A. Torres', 'program' => 'MSCS', 'track' => 'thesis', 'stage' => 'Thesis Proposal', 'stageKey' => 'proposal', 'title' => 'AI-Powered Academic Advising System', 'date' => '2025-03-20', 'status' => 'under_review', 'coordinatorComment' => '', 'adviser' => 'Dr. Maria Santos'],
        // Demo application for John Matthew Gler at the final capstone.
        ['id' => 2, 'studentEmail' => 'jgler@adzu.edu.ph', 'student' => 'John Matthew SJ. Gler', 'program' => 'MIT', 'track' => 'capstone', 'stage' => 'Final Capstone', 'stageKey' => 'final', 'title' => 'Cloud-Based Inventory Management', 'date' => '2025-03-18', 'status' => 'submitted', 'coordinatorComment' => '', 'adviser' => 'Dr. Maria Santos'],
        // Demo application for Sean Recto at the concept paper stage.
        ['id' => 3, 'studentEmail' => 'srecto@adzu.edu.ph', 'student' => 'Sean Benedict D. Recto', 'program' => 'MSCS', 'track' => 'thesis', 'stage' => 'Concept Paper', 'stageKey' => 'concept', 'title' => 'Blockchain for Academic Records', 'date' => '2025-03-22', 'status' => 'submitted', 'coordinatorComment' => '', 'adviser' => 'Dr. Maria Santos'],
        // Demo application for Marc Arbillera at the capstone proposal.
        ['id' => 4, 'studentEmail' => 'marbillera@adzu.edu.ph', 'student' => 'Marc Laurence M. Arbillera', 'program' => 'MIT', 'track' => 'capstone', 'stage' => 'Capstone Proposal', 'stageKey' => 'proposal', 'title' => 'E-Learning Platform for Rural Areas', 'date' => '2025-03-10', 'status' => 'approved', 'coordinatorComment' => '', 'adviser' => 'Dr. Maria Santos'],
        // Demo application for Rhett Epino at the final thesis stage.
        ['id' => 5, 'studentEmail' => 'repino@adzu.edu.ph', 'student' => 'Rhett Ushley E. Epino', 'program' => 'MSCS', 'track' => 'thesis', 'stage' => 'Final Thesis', 'stageKey' => 'final', 'title' => 'Graduate Application System', 'date' => '2025-02-28', 'status' => 'requires_revision', 'coordinatorComment' => '', 'adviser' => 'Dr. Maria Santos'],
    // Ends the demo application list.
    ];

    // Demo uploaded files linked to application 1.
    $uploads = [
        // Demo concept paper upload that was already approved.
        ['id' => 'up_1', 'applicationId' => 1, 'studentEmail' => 'rtorres@adzu.edu.ph', 'fileName' => 'Concept Paper – v1.pdf', 'docType' => 'Concept Paper', 'stage' => 'Concept Paper Presentation', 'size' => 1200000, 'date' => '2024-11-10', 'status' => 'approved'],
        // Demo adviser endorsement upload for the concept paper.
        ['id' => 'up_2', 'applicationId' => 1, 'studentEmail' => 'rtorres@adzu.edu.ph', 'fileName' => 'Adviser Endorsement – Concept.pdf', 'docType' => 'Signed Adviser Endorsement', 'stage' => 'Concept Paper Presentation', 'size' => 480000, 'date' => '2024-11-08', 'status' => 'approved'],
        // Demo proposal upload that is still under review.
        ['id' => 'up_3', 'applicationId' => 1, 'studentEmail' => 'rtorres@adzu.edu.ph', 'fileName' => 'Research Proposal – v2.pdf', 'docType' => 'Proposal', 'stage' => 'Thesis Proposal Presentation', 'size' => 2100000, 'date' => '2025-03-15', 'status' => 'under_review'],
        // Demo adviser endorsement upload for the proposal.
        ['id' => 'up_4', 'applicationId' => 1, 'studentEmail' => 'rtorres@adzu.edu.ph', 'fileName' => 'Adviser Endorsement – Proposal.pdf', 'docType' => 'Signed Adviser Endorsement', 'stage' => 'Thesis Proposal Presentation', 'size' => 390000, 'date' => '2025-03-14', 'status' => 'submitted'],
    // Ends the demo upload list.
    ];

    // Demo panel member records.
    $panels = [
        // Demo panel member Dr. Juan Dela Cruz.
        ['id' => 'seed_pm_Dela_Cruz', 'name' => 'Dr. Juan Dela Cruz', 'qualification' => 'PhD in Computer Science', 'email' => 'delacruz@adzu.edu.ph', 'notes' => '', 'panelSessions' => 8, 'availability' => 'available'],
        // Demo panel member Dr. Ana Reyes.
        ['id' => 'seed_pm_Reyes', 'name' => 'Dr. Ana Reyes', 'qualification' => 'PhD in Information Technology', 'email' => 'reyes@adzu.edu.ph', 'notes' => '', 'panelSessions' => 6, 'availability' => 'available'],
        // Demo panel member Prof. Miguel Santos.
        ['id' => 'seed_pm_Santos', 'name' => 'Prof. Miguel Santos', 'qualification' => 'MS in Computer Science', 'email' => 'santos@adzu.edu.ph', 'notes' => '', 'panelSessions' => 12, 'availability' => 'available'],
        // Demo panel member Prof. Lisa Fernandez.
        ['id' => 'seed_pm_Fernandez', 'name' => 'Prof. Lisa Fernandez', 'qualification' => 'MS in Information Technology', 'email' => 'fernandez@adzu.edu.ph', 'notes' => '', 'panelSessions' => 10, 'availability' => 'available'],
        // Demo panel member Dr. Maria Santos.
        ['id' => 'seed_pm_Maria', 'name' => 'Dr. Maria Santos', 'qualification' => 'PhD in Computer Science', 'email' => 'msantos@adzu.edu.ph', 'notes' => '', 'panelSessions' => 5, 'availability' => 'available'],
    // Ends the demo panel list.
    ];

    // Demo presentation schedules.
    $schedules = [
        // Demo schedule for Marc Arbillera's capstone proposal.
        ['id' => 'sch_seed_1', 'studentEmail' => 'marbillera@adzu.edu.ph', 'studentName' => 'Marc Laurence M. Arbillera', 'applicationId' => 4, 'stage' => 'Capstone Proposal Presentation', 'date' => 'Apr 10, 2025', 'time' => '9:00 AM', 'venue' => 'CSITE Seminar Room', 'panel' => 'Dela Cruz, Reyes, Santos', 'status' => 'confirmed', 'createdAt' => '2025-03-01T00:00:00+08:00'],
        // Demo schedule for Rhett Epino's final thesis defense.
        ['id' => 'sch_seed_2', 'studentEmail' => 'repino@adzu.edu.ph', 'studentName' => 'Rhett Ushley E. Epino', 'applicationId' => 5, 'stage' => 'Final Thesis Defense', 'date' => 'Apr 15, 2025', 'time' => '1:00 PM', 'venue' => 'CSITE Seminar Room', 'panel' => 'Reyes, Santos, Fernandez', 'status' => 'confirmed', 'createdAt' => '2025-03-01T00:00:00+08:00'],
        // Demo schedule for Robbie Torres's thesis proposal.
        ['id' => 'sch_seed_3', 'studentEmail' => 'rtorres@adzu.edu.ph', 'studentName' => 'Robbie Ryan A. Torres', 'applicationId' => 1, 'stage' => 'Thesis Proposal Presentation', 'date' => 'Apr 20, 2025', 'time' => '10:00 AM', 'venue' => '', 'panel' => 'TBD', 'status' => 'pending', 'createdAt' => '2025-03-01T00:00:00+08:00'],
    // Ends the demo schedule list.
    ];

    // Default workflow fields added to every demo application.
    $wfDefaults = [
        // Per-document workflow state, empty at the start.
        'workflowState' => [],
        // Payment has not been recorded yet.
        'paymentRecorded' => false,
        // Application is not ready for presentation yet.
        'readyForPresentation' => false,
        // Final result text, still empty.
        'result' => '',
        // Graduate School endorsement flag, false by default.
        'gradSchoolEndorsed' => false,
        // Revision notes for the student, empty by default.
        'revisionInstructions' => '',
        // Official receipt number, empty by default.
        'receiptNumber' => '',
        // Date the payment was made, empty by default.
        'paymentDate' => '',
        // Amount paid for the presentation fee, empty by default.
        'paymentAmount' => '',
    // Ends the workflow defaults.
    ];
    // Loops over applications by reference so they can be changed.
    foreach ($applications as &$appRow) {
        // Fills in missing workflow fields with the defaults.
        $appRow = array_merge($wfDefaults, $appRow);
    // Ends the loop over applications.
    }
    // Breaks the reference after the loop.
    unset($appRow);

    // Extra demo students with simple numeric emails.
    $demoStudents = [
        // Demo student Alex Santos on the thesis track.
        ['id' => 'seed_alex', 'email' => '1@adzu.edu.ph', 'firstName' => 'Alex', 'lastName' => 'Santos', 'middleInitial' => 'R', 'age' => '25', 'gender' => 'Male', 'program' => 'MSCS', 'track' => 'thesis', 'trackLabel' => 'Thesis', 'enrollDate' => '2024-08-15', 'currentStage' => 'not_started', 'status' => 'not_started', 'title' => '', 'adviser' => ''],
        // Demo student Maria Cruz on the capstone track.
        ['id' => 'seed_maria', 'email' => '2@adzu.edu.ph', 'firstName' => 'Maria', 'lastName' => 'Cruz', 'middleInitial' => 'L', 'age' => '26', 'gender' => 'Female', 'program' => 'MIT', 'track' => 'capstone', 'trackLabel' => 'Capstone', 'enrollDate' => '2024-08-15', 'currentStage' => 'not_started', 'status' => 'not_started', 'title' => '', 'adviser' => ''],
        // Demo student Jose Reyes on the seminar track.
        ['id' => 'seed_jose', 'email' => '3@adzu.edu.ph', 'firstName' => 'Jose', 'lastName' => 'Reyes', 'middleInitial' => 'M', 'age' => '27', 'gender' => 'Male', 'program' => 'MATH', 'track' => 'seminar', 'trackLabel' => 'Seminar Paper', 'enrollDate' => '2024-08-15', 'currentStage' => 'not_started', 'status' => 'not_started', 'title' => '', 'adviser' => ''],
    // Ends the extra demo student list.
    ];
    // Collects the lowercase emails of the existing demo students.
    $emails = array_map(static fn($s) => strtolower($s['email']), $students);
    // Goes through the extra demo students.
    foreach ($demoStudents as $ds) {
        // Checks that the email is not already used.
        if (!in_array(strtolower($ds['email']), $emails, true)) {
            // Adds the new demo student.
            $students[] = $ds;
        // Ends the email check.
        }
    // Ends the loop over extra students.
    }

    // Demo login accounts with their profile details.
    $accounts = [
        // Demo account for Alex Santos with password ABCD1234.
        '1@adzu.edu.ph' => ['password' => 'ABCD1234', 'profile' => ['firstName' => 'Alex', 'lastName' => 'Santos', 'middleInitial' => 'R', 'age' => '25', 'gender' => 'Male', 'email' => '1@adzu.edu.ph', 'program' => 'MSCS']],
        // Demo account for Maria Cruz with password ABCD1234.
        '2@adzu.edu.ph' => ['password' => 'ABCD1234', 'profile' => ['firstName' => 'Maria', 'lastName' => 'Cruz', 'middleInitial' => 'L', 'age' => '26', 'gender' => 'Female', 'email' => '2@adzu.edu.ph', 'program' => 'MIT']],
        // Demo account for Jose Reyes with password ABCD1234.
        '3@adzu.edu.ph' => ['password' => 'ABCD1234', 'profile' => ['firstName' => 'Jose', 'lastName' => 'Reyes', 'middleInitial' => 'M', 'age' => '27', 'gender' => 'Male', 'email' => '3@adzu.edu.ph', 'program' => 'MATH']],
        // Demo account for Robbie Torres with password ABCD1234.
        'rtorres@adzu.edu.ph' => ['password' => 'ABCD1234', 'profile' => ['firstName' => 'Robbie Ryan', 'lastName' => 'Torres', 'middleInitial' => 'A', 'age' => '24', 'gender' => 'Male', 'email' => 'rtorres@adzu.edu.ph', 'program' => 'MSCS']],
        // Demo account for John Matthew Gler with password ABCD1234.
        'jgler@adzu.edu.ph' => ['password' => 'ABCD1234', 'profile' => ['firstName' => 'John Matthew', 'lastName' => 'Gler', 'middleInitial' => 'SJ', 'age' => '', 'gender' => '', 'email' => 'jgler@adzu.edu.ph', 'program' => 'MIT']],
        // Demo account for Sean Recto with password ABCD1234.
        'srecto@adzu.edu.ph' => ['password' => 'ABCD1234', 'profile' => ['firstName' => 'Sean Benedict', 'lastName' => 'Recto', 'middleInitial' => 'D', 'age' => '', 'gender' => '', 'email' => 'srecto@adzu.edu.ph', 'program' => 'MSCS']],
    // Ends the demo account list.
    ];

    // Saves the demo students in the session.
    storeSet('students', $students);
    // Sorts the session students by name.
    sortSessionStudentsAlphabetically();
    // Saves the demo applications in the session.
    storeSet('applications', $applications);
    // Saves the demo uploads in the session.
    storeSet('uploads', $uploads);
    // Saves the demo panel members in the session.
    storeSet('panels', $panels);
    // Saves the demo schedules in the session.
    storeSet('schedules', $schedules);
    // Saves the demo accounts in the session.
    storeSet('accounts', $accounts);
    // Marks the demo data as seeded.
    $_SESSION['csite_seeded'] = true;
    // Marks the second demo data version as seeded.
    $_SESSION['csite_seeded_v2'] = true;
    // Marks the newest demo data version as seeded.
    $_SESSION['csite_seeded_v3'] = true;
// Ends the initPrototypeStore function.
}

// Builds the display name for a student row.
function studentDisplayName(array $s): string {
    // Prefers the full name already stored in the database.
    $databaseName = trim((string) ($s['name'] ?? $s['full_name'] ?? ''));
    // Uses the stored name when it is not empty.
    if ($databaseName !== '') {
        // Returns the stored full name.
        return $databaseName;
    // Ends the stored name check.
    }
    // Reads the first name from either naming style.
    $first = trim((string) ($s['firstName'] ?? $s['first_name'] ?? ''));
    // Reads the last name from either naming style.
    $last = trim((string) ($s['lastName'] ?? $s['last_name'] ?? ''));
    // Reads the middle initial from either naming style.
    $mi = trim((string) ($s['middleInitial'] ?? $s['middle_initial'] ?? ''));
    // Removes the trailing dot from the middle initial.
    $mi = $mi !== '' ? rtrim($mi, '.') : '';
    // Builds the name from first, last and middle initial.
    return canonicalStudentName($first, $last, $mi);
// Ends the studentDisplayName function.
}

/**
 * The student's name in the order people say it ("Juan R. Dela Cruz"), used by
 * the welcome message on the dashboard and by the notification shown right
 * after signing in. Falls back to users.full_name when the students row is
 * incomplete.
 */
// Builds the friendly name used in welcome messages.
function studentWelcomeName(?array $student, ?array $user = null): string {
    // Reads and formats the first name.
    $first = formatPersonName((string) ($student['first_name'] ?? $student['firstName'] ?? ''));
    // Reads and formats the last name.
    $last = formatPersonName((string) ($student['last_name'] ?? $student['lastName'] ?? ''));
    // Reads the middle initial.
    $middle = trim((string) ($student['middle_initial'] ?? $student['middleInitial'] ?? ''));
    // Adds a dot after the middle initial when there is one.
    $middle = $middle !== '' ? rtrim($middle, '.') . '.' : '';

    // Joins the names and collapses repeated spaces.
    $name = trim(preg_replace('/\s+/', ' ', trim($first . ' ' . $middle . ' ' . $last)) ?? '');
    // Returns the joined name when it is not empty.
    if ($name !== '') {
        // Returns the spoken-order name.
        return $name;
    // Ends the non-empty check.
    }

    // Falls back to the user's stored full name.
    return trim((string) ($user['full_name'] ?? $user['name'] ?? ''));
// Ends the studentWelcomeName function.
}

// Fixes the letter case of a person name.
function formatPersonName(?string $name): string {
    // Trims and collapses spaces in the name.
    $name = trim(preg_replace('/\s+/', ' ', (string) $name) ?? '');
    // Leaves empty or already mixed-case names unchanged.
    if ($name === '' || ($name !== strtoupper($name) && $name !== strtolower($name))) {
        // Returns the name as it is.
        return $name;
    // Ends the unchanged case check.
    }
    // Capitalises each word of an all-caps or all-lower name.
    return ucwords(strtolower($name), " \t\r\n\f\v-'");
// Ends the formatPersonName function.
}

/**
 * Build the single canonical student display name used everywhere.
 * Format: "Last, First MI" (no trailing dot) — the same value the database
 * triggers write to users.full_name, so the Users/application record and the
 * Students table can never diverge. Because the last name leads, a plain
 * alphabetical ORDER BY on this value (or on last_name, first_name) is correct.
 */
// Builds the standard Last, First MI name.
function canonicalStudentName(string $first, string $last, ?string $middleInitial = ''): string {
    // Formats the first name.
    $first = formatPersonName($first);
    // Formats the last name.
    $last = formatPersonName($last);
    // Trims the middle initial.
    $mi = trim((string) $middleInitial);
    // Removes any dot from the middle initial.
    $mi = $mi !== '' ? rtrim($mi, '.') : '';
    // Returns an empty name when both parts are missing.
    if ($last === '' && $first === '') {
        // Returns an empty string.
        return '';
    // Ends the empty name check.
    }
    // Only a first name is known.
    if ($last === '') {
        // Returns the first name with the middle initial.
        return trim($first . ($mi !== '' ? ' ' . $mi : ''));
    // Ends the first-name-only case.
    }
    // Only a last name is known.
    if ($first === '') {
        // Returns the last name with the middle initial after a comma.
        return trim($last . ($mi !== '' ? ', ' . $mi : ''));
    // Ends the last-name-only case.
    }
    // Returns the full name as Last, First MI.
    return trim($last . ', ' . $first . ($mi !== '' ? ' ' . $mi : ''));
// Ends the canonicalStudentName function.
}

/**
 * Extract the official student ID from an ADZU email address.
 * Keeps only the numeric characters of the local part:
 * e.g. co240255@adzu.edu.ph -> 240255.
 */
// Pulls the student ID digits out of an ADZU email address.
function extractStudentIdFromEmail(string $email): string {
    // Trims spaces around the email address.
    $email = trim($email);
    // Rejects empty text or text that is not an email.
    if ($email === '' || !str_contains($email, '@')) {
        // Returns an empty student ID.
        return '';
    // Ends the email check.
    }
    // Takes the part of the email before the @ in lowercase.
    $local = strtolower(explode('@', $email, 2)[0]);
    // Checks whether the local part contains digits.
    if (preg_match_all('/\d+/', $local, $m)) {
        // Joins all digit groups into one student ID.
        return implode('', $m[0]);
    // Ends the digit match check.
    }
    // Returns an empty ID when there are no digits.
    return '';
// Ends the extractStudentIdFromEmail function.
}

/**
 * Resolve the program code for a student row regardless of whether the stored
 * value is the code (MSCS) or the full program label.
 */
// Returns the program code for a student row.
function programCodeForStudent(array $student): string {
    // Reads the program value stored for the student.
    $program = trim((string) ($student['program'] ?? ''));
    // Uses MSCS when no program is stored.
    if ($program === '') {
        // Returns the default program code.
        return 'MSCS';
    // Ends the empty program check.
    }
    // Checks whether the value is already a known program code.
    if (isset(PROGRAMS[$program])) {
        // Returns the program code as it is.
        return $program;
    // Ends the known code check.
    }
    // Finds the code whose label matches the stored value.
    $code = array_key_first(array_filter(PROGRAMS, static fn($label) => $label === $program));
    // Returns the code when a label matched.
    if ($code) {
        // Returns the matching program code.
        return $code;
    // Ends the label match check.
    }
    // Older accounts may store the program name with the track in brackets.
    // A student that was registered before can still carry the name of the
    // Example: Master of Science in Computer Science (Thesis).
    // track in the program, e.g. "Master of Science in Computer Science
    // The course name is the same whatever track is written.
    // (Thesis)". The name of the course is the same either way.
    // Removes a trailing bracketed track name.
    $withoutTrack = trim((string) preg_replace('/\s*\([^)]*\)\s*$/', '', $program));
    // Tries again to match a program label without the track.
    $code = array_key_first(array_filter(PROGRAMS, static fn($label) => $label === $withoutTrack));
    // Returns the code, or the original value as a last resort.
    return $code ?: $program;
// Ends the programCodeForStudent function.
}

/**
 * Persist the full student identity into the MySQL students table.
 * This is the single write point that keeps students in sync with the
 * application/registration record and with users.full_name (via DB triggers).
 */
// Creates or updates the student row for a user.
function upsertStudentIdentity(array $input, int $userId): array {
    // Gets the shared database connection.
    $pdo = DB::getConnection();

    // Reads the first name from the input.
    $first = trim((string) ($input['first_name'] ?? $input['firstName'] ?? ''));
    // Reads the last name from the input.
    $last  = trim((string) ($input['last_name'] ?? $input['lastName'] ?? ''));
    // Reads the middle initial from the input.
    $mi    = trim((string) ($input['middle_initial'] ?? $input['middleInitial'] ?? ''));
    // Removes any dot from the middle initial.
    $mi    = $mi !== '' ? rtrim($mi, '.') : '';
    // Works out the full program label for the student.
    $programLabel = $input['program_label'] ?? (isset(PROGRAMS[$input['program'] ?? '']) ? PROGRAMS[$input['program']] : ($input['program'] ?? 'MSCS'));
    // Uses the given track or the program's default track.
    $track = $input['track'] ?? getTrackForProgram($input['program'] ?? 'MSCS');
    // Reads the gender from the input.
    $gender = trim((string) ($input['gender'] ?? ''));
    // Reads the age as a whole number.
    $age = (int) ($input['age'] ?? 0);
    // Builds the standard display name for users.full_name.
    $fullName = canonicalStudentName($first, $last, $mi);

    // Looks for an existing students row for this user.
    $existing = DB::find('students', ['user_id' => (int) $userId]);
    // Reads the email used to derive a student ID.
    $emailForId = trim((string) ($input['email'] ?? ''));
    // Falls back to the user's email when none was given.
    if ($emailForId === '') {
        // Tries to load the user row for its email.
        try {
            // Finds the user row by user id.
            $owner = DB::find('users', ['user_id' => (int) $userId]);
            // Takes the email from the user row.
            $emailForId = trim((string) ($owner['email'] ?? ''));
        // Ignores errors while loading the user.
        } catch (Throwable $e) {
            // Uses an empty email when loading failed.
            $emailForId = '';
        // Ends the catch block.
        }
    // Ends the email fallback check.
    }
    // Updates the row when the student already exists.
    if ($existing) {
        // Starts a transaction so both tables change together.
        $pdo->beginTransaction();
        // Starts the update attempt.
        try {
            // Values to write into the students row.
            $patch = [
                // New first name.
                'first_name' => $first,
                // New last name.
                'last_name' => $last,
                // New middle initial without a dot.
                'middle_initial' => $mi,
                // New age.
                'age' => $age,
                // New gender.
                'gender' => $gender,
                // Full program label.
                'program' => $programLabel,
                // Selected track.
                'track' => $track,
                // Keeps the stored adviser when no new one is given.
                'adviser_name' => trim((string) ($input['adviser_name'] ?? $existing['adviser_name'] ?? '')),
            // Ends the update values.
            ];
            // Updates the students row for this student id.
            DB::update('students', $patch, ['student_id' => (int) $existing['student_id']]);
            // Updates users.full_name to match the new name.
            DB::update('users', ['full_name' => $fullName], ['user_id' => (int) $userId]);
            // Saves both changes together.
            $pdo->commit();
        // Rolls everything back when either update fails.
        } catch (Throwable $e) {
            // Only rolls back when a transaction is still open.
            if ($pdo->inTransaction()) {
                // Cancels the partial changes.
                $pdo->rollBack();
            // Ends the transaction check.
            }
            // Passes the error up so the caller can report it.
            throw $e;
        // Ends the catch block.
        }
        // Reflects the saved first name in the returned row.
        $existing['first_name'] = $first;
        // Reflects the saved last name in the returned row.
        $existing['last_name'] = $last;
        // Reflects the saved middle initial in the returned row.
        $existing['middle_initial'] = $mi;
        // Reflects the saved age in the returned row.
        $existing['age'] = $age;
        // Reflects the saved gender in the returned row.
        $existing['gender'] = $gender;
        // Reflects the saved program in the returned row.
        $existing['program'] = $programLabel;
        // Reflects the saved track in the returned row.
        $existing['track'] = $track;
        // Reflects the saved adviser name in the returned row.
        $existing['adviser_name'] = trim((string) ($input['adviser_name'] ?? $existing['adviser_name'] ?? ''));
        // Returns the updated student row.
        return $existing;
    // Ends the update branch.
    }

    // Starts a transaction for the new student row.
    $pdo->beginTransaction();
    // Starts the insert attempt.
    try {
        // Values for the new students row.
        $newRow = [
            // Links the row to the user account.
            'user_id' => (int) $userId,
            // Student first name.
            'first_name' => $first,
            // Student last name.
            'last_name' => $last,
            // Student middle initial without a dot.
            'middle_initial' => $mi,
            // Student age.
            'age' => $age,
            // Student gender.
            'gender' => $gender,
            // Full program label.
            'program' => $programLabel,
            // Selected track.
            'track' => $track,
            // Adviser name, empty when not given.
            'adviser_name' => trim((string) ($input['adviser_name'] ?? '')),
            // Sets the enrollment date to today.
            'enrollment_date' => date('Y-m-d'),
        // Ends the new row values.
        ];
        // The official student ID comes from the ADZU ID typed by the student.
        // Official student ID = the ADZU ID number typed by the student
        // The input may use student_id or studentId.
        // ($input['student_id'] / $input['studentId']). Falls back to the numeric
        // The email digits are only a fallback for older callers.
        // part of the ADZU email only for legacy callers that omit it.
        // Reads the typed student ID and trims it.
        $postedIdRaw = trim((string) ($input['student_id'] ?? $input['studentId'] ?? ''));
        // Placeholder for the digits taken from the email.
        $emailDigits = '';
        // Uses the email digits when the typed ID is missing or invalid.
        if ($postedIdRaw === '' || !ctype_digit($postedIdRaw)) {
            // Extracts the numeric part of the email.
            $emailDigits = $emailForId !== '' ? extractStudentIdFromEmail($emailForId) : '';
        // Ends the typed ID check.
        }
        // Converts a valid typed ID to a number, or 0.
        $explicitStudentId = ($postedIdRaw !== '' && ctype_digit($postedIdRaw)) ? (int) $postedIdRaw : 0;
        // Remembers whether the ID came from the email.
        $idFromEmail = false;
        // Falls back to the email digits when there is no typed ID.
        if ($explicitStudentId <= 0) {
            // Uses the email digits when they are numeric.
            $explicitStudentId = ($emailDigits !== '' && ctype_digit($emailDigits)) ? (int) $emailDigits : 0;
            // Notes that the ID came from the email.
            $idFromEmail = $explicitStudentId > 0;
        // Ends the fallback check.
        }
        // Sets the student id only when a number was found.
        if ($explicitStudentId > 0) {
            // Adds the student id to the new row.
            $newRow['student_id'] = $explicitStudentId;
        // Ends the student id check.
        }
        // Tries to insert the new student row.
        try {
            // Inserts the row and returns the saved student.
            $student = DB::insert('students', $newRow);
        // Catches a duplicate key error on insert.
        } catch (PDOException $e) {
            // Auto-assigns the id again when the email digits collide.
            // Email-derived digit collision: fall back to auto-assign.
            // Notes that a typed ADZU ID collision is rethrown so the caller can report it.
            // A typed ADZU ID collision is rethrown so the caller can report it.
            // Retries only when the ID came from the email and MySQL reported a duplicate key.
            if ($idFromEmail && $explicitStudentId > 0 && (string) ($e->getCode() ?? '') === '23000') {
                // Removes the colliding student_id so MySQL can assign the next free one.
                unset($newRow['student_id']);
                // Inserts the student row again without the conflicting student_id.
                $student = DB::insert('students', $newRow);
            // Handles every other database error by rethrowing it.
            } else {
                // Rethrows the error so the calling page can report the problem.
                throw $e;
            // Closes the else block that rethrows the error.
            }
        // Closes the inner try/catch block around the second insert.
        }
        // Commits the transaction so the new student is saved for good.
        $pdo->commit();
        // Returns the saved student row, re-read from the database.
        return DB::find('students', ['user_id' => (int) $userId]) ?: $student;
    // Catches any failure so the transaction can be rolled back.
    } catch (Throwable $e) {
        // Checks whether a transaction is still open before rolling back.
        if ($pdo->inTransaction()) {
            // Rolls back the transaction so no half-saved student remains.
            $pdo->rollBack();
        // Closes the in-transaction check.
        }
        // Rethrows the error after the rollback.
        throw $e;
    // Closes the catch block for the outer try.
    }
// Closes the insertStudentIdentity function.
}

/**
 * Build the session-prototype student row that mirrors the MySQL students row
 * exactly, so every hybrid screen shows the identical identity.
 */
// Builds the session student row from the identity data and an optional row.
function studentRowFromIdentity(array $identity, array $sessionRow = []): array {
    // Reads the first name, accepting database or camelCase keys.
    $first = trim((string) ($identity['first_name'] ?? $identity['firstName'] ?? ''));
    // Reads the last name from either key style.
    $last  = trim((string) ($identity['last_name'] ?? $identity['lastName'] ?? ''));
    // Reads the middle initial from either key style.
    $mi    = trim((string) ($identity['middle_initial'] ?? $identity['middleInitial'] ?? ''));
    // Removes a trailing period from the middle initial when present.
    $mi    = $mi !== '' ? rtrim($mi, '.') : '';
    // Finds the program code for the student's program.
    $programCode = programCodeForStudent($identity);
    // Uses the given track or looks it up from the program code.
    $track = $identity['track'] ?? getTrackForProgram($programCode);
    // Reads the age as trimmed text.
    $age = trim((string) ($identity['age'] ?? ''));
    // Reads the gender as trimmed text.
    $gender = trim((string) ($identity['gender'] ?? ''));
    // Reads the enrollment date, or uses today's date.
    $enroll = (string) ($identity['enrollment_date'] ?? $identity['enrollDate'] ?? date('Y-m-d'));

    // Starts the array that mirrors a students table row.
    return [
        // Uses the existing student id or makes a temporary one.
        'id' => (string) ($sessionRow['id'] ?? 's_' . uniqid()),
        // Uses the identity email, then the session row email.
        'email' => (string) ($identity['email'] ?? $sessionRow['email'] ?? ''),
        // Sets the first name.
        'firstName' => $first,
        // Sets the last name.
        'lastName' => $last,
        // Sets the middle initial.
        'middleInitial' => $mi,
        // Sets the age.
        'age' => $age,
        // Sets the gender.
        'gender' => $gender,
        // Sets the program code.
        'program' => $programCode,
        // Sets the track code.
        'track' => $track,
        // Sets the readable track label.
        'trackLabel' => getTrackLabel($track),
        // Sets the enrollment date.
        'enrollDate' => $enroll,
        // Keeps the session current stage, or starts at not_started.
        'currentStage' => (string) ($sessionRow['currentStage'] ?? 'not_started'),
        // Keeps the session status, or starts at not_started.
        'status' => (string) ($sessionRow['status'] ?? 'not_started'),
        // Keeps the paper title from the session row.
        'title' => (string) ($sessionRow['title'] ?? ''),
        // Uses the session adviser, or the identity adviser name.
        'adviser' => (string) ($sessionRow['adviser'] ?? $identity['adviser_name'] ?? ''),
    // Closes the student row array.
    ];
// Closes the studentRowFromIdentity function.
}

/**
 * Insert or replace the session-prototype student record for an email with the
 * canonical identity, so the prototype screens can never show a stale name.
 */
// Creates or updates the session student row for this identity.
function upsertSessionStudent(array $identity, array $sessionRow = []): void {
    // Loads all session students.
    $students = storeGet('students');
    // Builds a lowercase email for matching.
    $email = strtolower((string) ($identity['email'] ?? $sessionRow['email'] ?? ''));
    // Stops when there is no email to match on.
    if ($email === '') {
        // Returns early because the row cannot be identified.
        return;
    // Closes the empty-email check.
    }
    // Builds the canonical student row for this identity.
    $row = studentRowFromIdentity($identity, $sessionRow);
    // Keeps the original email casing from the identity record.
    $row['email'] = $identity['email'] ?? $sessionRow['email'] ?? $row['email'];
    // Starts the flag that says whether an existing row was found.
    $found = false;
    // Loops through the students by reference so a row can be replaced.
    foreach ($students as &$s) {
        // Compares emails without caring about letter case.
        if (strcasecmp((string) ($s['email'] ?? ''), $email) === 0) {
            // Merges the new values into the existing student row.
            $s = array_merge($s, $row);
            // Marks that the student row was found.
            $found = true;
            // Stops the loop once the row is updated.
            break;
        // Closes the email comparison.
        }
    // Closes the loop over the students.
    }
    // Breaks the reference after the loop.
    unset($s);
    // Checks whether no existing row matched.
    if (!$found) {
        // Adds the new student row at the end of the list.
        $students[] = $row;
    // Closes the not-found check.
    }
    // Saves the updated students list.
    storeSet('students', $students);
    // Re-sorts the students by last name then first name.
    sortSessionStudentsAlphabetically();
    // Remembers the current student id in the session.
    $_SESSION['current_student_id'] = $row['id'];
    // Remembers the current student email in the session.
    $_SESSION['current_student_email'] = $row['email'];
// Closes the upsertSessionStudent function.
}

/**
 * Read the authenticated student's identity straight from MySQL — the single
 * source of truth. Falls back to session data only when the DB is unavailable.
 */
// Reads the logged-in student's identity from the database.
function databaseStudentIdentity(): array {
    // Gets the session user array or an empty array.
    $sessionUser = $_SESSION['user'] ?? [];
    // Reads the user id from the session user or the plain session key.
    $userId = (int) ($sessionUser['user_id'] ?? $_SESSION['user_id'] ?? 0);
    // Returns nothing when there is no user id or no DB class.
    if ($userId <= 0 || !class_exists('DB')) {
        // Returns an empty identity when the user is unknown.
        return [];
    // Closes the user-id check.
    }
    // Starts the try block for the database lookups.
    try {
        // Finds the students row for this user id.
        $dbStudent = DB::find('students', ['user_id' => $userId]);
        // Finds the users row for the same user id.
        $dbUser = $userId > 0 ? DB::find('users', ['user_id' => $userId]) : null;
        // Returns empty when either the student or user row is missing.
        if (!$dbStudent || !$dbUser) {
            // Returns an empty identity when the profile is incomplete.
            return [];
        // Closes the missing-row check.
        }
        // Starts the identity array returned to callers.
        return [
            // Sets the numeric student id.
            'student_id' => (int) $dbStudent['student_id'],
            // Sets the user id.
            'user_id' => $userId,
            // Sets the email from the users row.
            'email' => $dbUser['email'],
            // Sets the full name from the users row.
            'full_name' => $dbUser['full_name'],
            // Sets the first name from the students row.
            'first_name' => $dbStudent['first_name'],
            // Sets the last name from the students row.
            'last_name' => $dbStudent['last_name'],
            // Sets the middle initial, or empty text when null.
            'middle_initial' => $dbStudent['middle_initial'] ?? '',
            // Sets the age as an integer.
            'age' => (int) $dbStudent['age'],
            // Sets the gender, or empty text when null.
            'gender' => $dbStudent['gender'] ?? '',
            // Finds the program code for this student.
            'program' => programCodeForStudent($dbStudent),
            // Sets the program label exactly as stored.
            'program_label' => $dbStudent['program'],
            // Strips the parenthesized part to get a clean program name.
            'program_name' => preg_replace('/\s*\(.*\)$/', '', (string) $dbStudent['program']) ?: $dbStudent['program'],
            // Sets the track code.
            'track' => $dbStudent['track'],
            // Sets the readable track label.
            'track_label' => getTrackLabel($dbStudent['track']),
            // Sets the adviser name when one is recorded.
            'adviser_name' => $dbStudent['adviser_name'] ?? '',
            // Sets the enrollment date when one is recorded.
            'enrollment_date' => $dbStudent['enrollment_date'] ?? '',
        // Closes the identity array.
        ];
    // Catches any database error so the page can still load.
    } catch (Throwable $e) {
        // Returns an empty identity when the query fails.
        return [];
    // Closes the catch block.
    }
// Closes the databaseStudentIdentity function.
}

// Finds a student by email, starting with the session list.
function findStudentByEmail(string $email): ?array {
    // Loops through the session students.
    foreach (storeGet('students') as $s) {
        // Compares emails without caring about letter case.
        if (strcasecmp((string) $s['email'], $email) === 0) {
            // Returns the matching session student row.
            return $s;
        // Closes the email comparison.
        }
    // Closes the loop over session students.
    }

    // Explains that the real students of the system live in the database.
    // The students of the system live in the database. Without this a real
    // Notes that without the database a real student cannot be found.
    // student cannot be found, and the track of the application that is made
    // Notes that their application track would then be guessed wrong.
    // for them would be guessed wrong.
    // Normalizes the email by trimming spaces and lowering the case.
    $email = strtolower(trim($email));
    // Only queries the database when an email exists and DB is loaded.
    if ($email !== '' && class_exists('DB')) {
        // Starts the try block for the database lookup.
        try {
            // Prepares the SQL that selects the student and user columns.
            $stmt = DB::getConnection()->prepare(
                // Selects all student columns plus the email and full name.
                'SELECT s.*, u.email, u.full_name
                   FROM students s
                   INNER JOIN users u ON u.user_id = s.user_id
                  WHERE u.email = :email
                  LIMIT 1'
            // Closes the prepared SQL statement text.
            );
            // Runs the query with the email as the bound value.
            $stmt->execute(['email' => $email]);
            // Fetches the matching row.
            $row = $stmt->fetch();
            // Checks whether a database row was found.
            if ($row) {
                // Returns the database student row.
                return $row;
            // Closes the row-found check.
            }
        // Catches database errors so the prototype fallback still works.
        } catch (Throwable $e) {
            // Notes that the prototype students are kept when the DB is unavailable.
            // Keep the prototype students when the database is not available.
        // Closes the catch block.
        }
    // Closes the email-and-DB check.
    }

    // Returns null when no student matched anywhere.
    return null;
// Closes the findStudentByEmail function.
}

// Finds a session student by their id.
function findStudentById(string $id): ?array {
    // Loops through the session students.
    foreach (storeGet('students') as $s) {
        // Compares the stored id with the requested id.
        if ((string) $s['id'] === $id) {
            // Returns the matching student row.
            return $s;
        // Closes the id comparison.
        }
    // Closes the loop over session students.
    }
    // Returns null when no student has that id.
    return null;
// Closes the findStudentById function.
}

// Gets the email of the student currently selected.
function currentStudentEmail(array $fallback): string {
    // Reads the current student id from the session.
    $id = (string) ($_SESSION['current_student_id'] ?? '');
    // Checks whether a current student id is set.
    if ($id !== '') {
        // Looks up the student row for that id.
        $student = findStudentById($id);
        // Checks whether the student row was found.
        if ($student) {
            // Returns the email stored on the student row.
            return (string) $student['email'];
        // Closes the student-found check.
        }
    // Closes the current-id check.
    }
    // Falls back to the session email or the fallback array email.
    return $_SESSION['current_student_email'] ?? $fallback['email'];
// Closes the currentStudentEmail function.
}

// Unused duplicate profile helper with a misspelled name; reads the DB profile.
function currentStudentProfilee(?array $defaultMock = null): array {
    // Notes that a real profile is fetched when a user id is in session.
    // If session has a logged-in user ID, fetch real profile from DB
    // Only queries when a user id is set and the DB class exists.
    if (isset($_SESSION['user_id']) && class_exists('DB')) {
        // Starts the try block for the profile query.
        try {
            // Prepares the SQL that joins students and users for this user.
            $stmt = DB::getConnection()->prepare("
                SELECT s.*, u.email, u.id as user_id 
                FROM students s 
                INNER JOIN users u ON u.id = s.user_id 
                WHERE s.user_id = :uid 
                LIMIT 1
            ");
            // Runs the query with the session user id.
            $stmt->execute(['uid' => $_SESSION['user_id']]);
            // Fetches the student row as an associative array.
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            // Checks whether the database returned a student.
            if ($student) {
                // Returns the raw database student row.
                return $student;
            // Closes the student-found check.
            }
        // Catches query errors so the fallback can be used.
        } catch (Throwable $e) {
            // Notes that the code falls back when the query fails.
            // Fall back if DB query fails
        // Closes the catch block.
        }
    // Closes the user-id-and-DB check.
    }

    // Notes the mock array is used when no session user is found.
    // Fall back to mock student array if session user isn't found
    // Returns the default mock profile or an empty array.
    return $defaultMock ?? [];
// Closes the currentStudentProfilee function.
}

// Builds the profile array used by the student pages.
function currentStudentProfile(array $fallback): array {
    // Gets the session user array or an empty array.
    $sessionUser = $_SESSION['user'] ?? [];
    
    // Notes that the session or current student id is used.
    // Check session or current student ID
    // Reads the user id from the session, or null when absent.
    $userId = $sessionUser['user_id'] ?? $_SESSION['user_id'] ?? null;

    // Only queries the database when a user id is present.
    if ($userId && class_exists('DB')) {
        // Starts the try block for the profile query.
        try {
            // Prepares the SQL that selects the student and user columns.
            $stmt = DB::getConnection()->prepare("
                SELECT s.*, u.email AS user_email, u.full_name AS user_full_name
                FROM students s
                INNER JOIN users u ON u.user_id = s.user_id
                WHERE s.user_id = :uid
                LIMIT 1
            ");
            // Runs the query with the user id cast to an integer.
            $stmt->execute(['uid' => (int)$userId]);
            // Fetches the student row as an associative array.
            $dbStudent = $stmt->fetch(PDO::FETCH_ASSOC);

            // Checks whether the database returned a student.
            if ($dbStudent) {
                // Finds the program code for the database student.
                $programCode = programCodeForStudent($dbStudent);
                // Uses the PROGRAMS name for the code, or the stored program text.
                $programName = PROGRAMS[$programCode] ?? $dbStudent['program'];
                // Uses the enrollment date, or today when it is empty.
                $enroll = $dbStudent['enrollment_date'] ?: date('Y-m-d');
                
                // Reads the full name from the joined users row.
                $fullName = trim((string) ($dbStudent['user_full_name'] ?? ''));
                // Checks whether the full name is empty.
                if ($fullName === '') {
                    // Builds the name from first name, last name, and middle initial.
                    $fullName = canonicalStudentName($dbStudent['first_name'], $dbStudent['last_name'], $dbStudent['middle_initial'] ?? '');
                // Closes the empty-name check.
                }

                // Returns the fallback array updated with the database values.
                return array_merge($fallback, [
                    // Uses the database student id as a string.
                    'id' => (string) $dbStudent['student_id'],
                    // Uses the database user id.
                    'user_id' => (int) $dbStudent['user_id'],
                    // Sets the display name.
                    'name' => $fullName,
                    // Sets the email, or an empty string.
                    'email' => $dbStudent['user_email'] ?? '',
                    // Sets the program code.
                    'program' => $programCode,
                    // Strips the parenthesized part from the program name.
                    'program_name' => preg_replace('/\s*\(.*\)$/', '', $programName) ?: $programName,
                    // Sets the track code.
                    'track' => $dbStudent['track'],
                    // Sets the readable track label.
                    'trackLabel' => getTrackLabel($dbStudent['track']),
                    // Sets the enrollment date.
                    'enroll_date' => $enroll,
                    // Sets the current stage, or not_started.
                    'current_stage' => $dbStudent['current_stage'] ?? 'not_started',
                    // Sets the status, or not_started.
                    'status' => $dbStudent['status'] ?? 'not_started',
                    // Sets the paper title, or an empty string.
                    'title' => $dbStudent['title'] ?? '',
                    // Sets the adviser name, or an empty string.
                    'adviser' => $dbStudent['adviser_name'] ?? '',
                    // Computes the completion deadline three years after enrollment.
                    'completion_deadline' => ((int) substr($enroll, 0, 4) + 3) . substr($enroll, 4)
                // Closes the merged profile array.
                ]);
            // Closes the student-found check.
            }
        // Catches query errors so the fallbacks below still run.
        } catch (Throwable $e) {
            // Notes that the exception is silenced to use the fallbacks.
            // Silence exception and drop down to fallbacks
        // Closes the catch block.
        }
    // Closes the user-id-and-DB check.
    }

    // Notes that the fallback lookup logic continues below.
    // Keep rest of your fallback lookup logic below...
    // Reads the current student id from the session.
    $id = (string) ($_SESSION['current_student_id'] ?? '');
    // Finds the student by id, or null when no id is set.
    $s = $id !== '' ? findStudentById($id) : null;
    // Gets the current email as another way to find the student.
    $email = currentStudentEmail($fallback);
    // Checks whether the id lookup found nothing.
    if (!$s) {
        // Tries to find the student by email instead.
        $s = findStudentByEmail($email);
    // Closes the id-lookup check.
    }
    // Checks whether the student is still not found.
    if (!$s) {
        // Checks whether the session user has the student role.
        if (($sessionUser['role'] ?? '') === 'student') {
            // Uses the session full name as the display name.
            $fallback['name'] = $sessionUser['full_name'] ?? $fallback['name'];
            // Uses the session email as the email.
            $fallback['email'] = $sessionUser['email'] ?? $fallback['email'];
        // Closes the student-role check.
        }
        // Returns the untouched fallback when there is no student row.
        return $fallback;
    // Closes the not-found check.
    }

    // Reads the program code from the session student row.
    $program = $s['program'];
    // Finds the track that belongs to that program.
    $track = getTrackForProgram($program);
    // Gets the full program name from PROGRAMS, or the code.
    $fullName = PROGRAMS[$program] ?? $program;
    // Strips the parenthesized part from the program name.
    $programName = preg_replace('/\s*\(.*\)$/', '', $fullName) ?: $fullName;
    // Reads the enrollment date, or uses the fallback date.
    $enroll = $s['enrollDate'] ?? $fallback['enroll_date'];
    // Checks whether the date only has a year and month.
    if (strlen($enroll) === 7) {
        // Adds the first day of the month to the short date.
        $enroll .= '-01';
    // Closes the short-date check.
    }
    // Reads the enrollment year from the date.
    $year = (int) substr($enroll, 0, 4);

    // Returns the fallback array updated with the session student values.
    return array_merge($fallback, [
        // Sets the session student id.
        'id' => $s['id'],
        // Sets the display name built by studentDisplayName.
        'name' => studentDisplayName($s),
        // Sets the email.
        'email' => $s['email'],
        // Sets the program code.
        'program' => $program,
        // Sets the clean program name.
        'program_name' => $programName,
        // Sets the track code.
        'track' => $track,
        // Sets the readable track label.
        'trackLabel' => getTrackLabel($track),
        // Sets the enrollment date.
        'enroll_date' => $enroll,
        // Sets the current stage, or proposal.
        'current_stage' => $s['currentStage'] ?? 'proposal',
        // Sets the status, or pending.
        'status' => $s['status'] ?? 'pending',
        // Sets the title or keeps the fallback title.
        'title' => $s['title'] ?? $fallback['title'],
        // Sets the adviser or keeps the fallback adviser.
        'adviser' => $s['adviser'] ?? $fallback['adviser'],
        // Computes the completion deadline three years after enrollment.
        'completion_deadline' => ($year + 3) . substr($enroll, 4),
    // Closes the merged profile array.
    ]);
// Closes the currentStudentProfile function.
}

/**
 * Keep the session student list in the same alphabetical order as the
 * database (last name, then first name) so a newly created account lands in
 * its correct sorted position instead of appended at the end.
 */
// Sorts the session students by last name then first name.
function sortSessionStudentsAlphabetically(): void {
    // Loads the session students.
    $students = storeGet('students');
    // Sorts the list with a comparison function.
    usort($students, static function ($a, $b) {
        // Compares the last names of the two students.
        $c = strcasecmp(
            // Reads the first student's last name from either key style.
            trim((string) ($a['lastName'] ?? $a['last_name'] ?? '')),
            // Reads the second student's last name from either key style.
            trim((string) ($b['lastName'] ?? $b['last_name'] ?? ''))
        // Closes the last-name comparison.
        );
        // Checks whether the last names differ.
        if ($c !== 0) {
            // Returns the last-name comparison result.
            return $c;
        // Closes the last-name check.
        }
        // Compares first names when the last names are equal.
        return strcasecmp(
            // Reads the first student's first name.
            trim((string) ($a['firstName'] ?? $a['first_name'] ?? '')),
            // Reads the second student's first name.
            trim((string) ($b['firstName'] ?? $b['first_name'] ?? ''))
        // Closes the first-name comparison.
        );
    // Closes the sort callback.
    });
    // Saves the sorted students list.
    storeSet('students', $students);
// Closes the sort function.
}

// Adds a new student record to the session store.
function addRegisteredStudent(array $input): array {
    // Loads the current session students.
    $list = storeGet('students');
    // Loops through the students to check for a duplicate email.
    foreach ($list as $s) {
        // Compares emails without caring about letter case.
        if (strcasecmp($s['email'], $input['email']) === 0) {
            // Throws an error when an account already uses this email.
            throw new LogicException('An account already exists for this email. Sign in with that account instead.');
        // Closes the duplicate-email check.
        }
    // Closes the loop over students.
    }
    // Finds the track that belongs to the chosen program.
    $track = getTrackForProgram($input['program']);
    // Starts the new student row.
    $rec = [
        // Creates a unique student id.
        'id' => 's_' . uniqid(),
        // Stores the email.
        'email' => $input['email'],
        // Stores the trimmed first name.
        'firstName' => trim($input['firstName']),
        // Stores the trimmed last name.
        'lastName' => trim($input['lastName']),
        // Stores the middle initial without a trailing period.
        'middleInitial' => rtrim(trim($input['middleInitial'] ?? ''), '.'),
        // Stores the age, or an empty string.
        'age' => $input['age'] ?? '',
        // Stores the gender, or an empty string.
        'gender' => $input['gender'] ?? '',
        // Stores the program code.
        'program' => $input['program'],
        // Stores the track code.
        'track' => $track,
        // Stores the readable track label.
        'trackLabel' => getTrackLabel($track),
        // Stores today as the enrollment date.
        'enrollDate' => date('Y-m-d'),
        // Starts the student at the Not Started stage.
        'currentStage' => 'Not Started',
        // Sets the status to not_started.
        'status' => 'not_started',
        // Starts with an empty paper title.
        'title' => '',
        // Starts with no adviser.
        'adviser' => '',
    // Closes the new student row array.
    ];
    // Adds the new row to the students list.
    $list[] = $rec;
    // Saves the updated students list.
    storeSet('students', $list);
    // Re-sorts the students list alphabetically.
    sortSessionStudentsAlphabetically();
    // Remembers the new student id in the session.
    $_SESSION['current_student_id'] = $rec['id'];
    // Remembers the new student email in the session.
    $_SESSION['current_student_email'] = $rec['email'];
    // Returns the new student row.
    return $rec;
// Closes the addRegisteredStudent function.
}

// Loads the saved accounts keyed by email.
function loadAccounts(): array {
    // Returns the accounts array from the session store.
    return storeGet('accounts');
// Closes the loadAccounts function.
}

// Saves an account profile and keeps the old password when none is given.
function saveAccount(array $profile, string $password = ''): void {
    // Loads the existing accounts.
    $accounts = loadAccounts();
    // Uses the lowercase email as the account key.
    $key = strtolower((string) $profile['email']);
    // Reads the previous account or uses an empty default.
    $prev = $accounts[$key] ?? ['password' => '', 'profile' => []];
    // Starts the account entry for this email.
    $accounts[$key] = [
        // Keeps the new password, or the old one when empty.
        'password' => $password !== '' ? $password : ($prev['password'] ?? ''),
        // Stores the profile data.
        'profile' => $profile,
    // Closes the account entry array.
    ];
    // Saves the accounts array.
    storeSet('accounts', $accounts);
// Closes the saveAccount function.
}

// Finds a saved account by email.
function findAccount(string $email): ?array {
    // Loads all accounts.
    $accounts = loadAccounts();
    // Lowercases the email to build the key.
    $key = strtolower($email);
    // Returns the account for that key, or null.
    return $accounts[$key] ?? null;
// Closes the findAccount function.
}

// Checks a student's email and password and logs them in.
function authenticateStudent(string $email, string $password): array {
    // Trims spaces from the typed email.
    $email = trim($email);
    // Requires an email address ending in @adzu.edu.ph.
    if (!preg_match('/^[^\s@]+@adzu\.edu\.ph$/i', $email)) {
        // Returns an error asking for a valid ADZU email.
        return ['ok' => false, 'field' => 'email', 'message' => 'Enter a valid ADZU email address (e.g. yourname@adzu.edu.ph)'];
    // Closes the ADZU email check.
    }
    // Looks for a saved account with this email.
    $account = findAccount($email);
    // Checks whether no saved account exists.
    if (!$account) {
        // Tries to find a session student with this email.
        $student = findStudentByEmail($email);
        // Checks whether no student profile exists either.
        if (!$student) {
            // Returns an error asking the user to create an account.
            return ['ok' => false, 'field' => 'email', 'message' => 'There is no account found, please create an account.'];
        // Closes the no-student check.
        }
        // Builds a temporary account from the student record.
        $account = [
            // Gives the temporary account a default password.
            'password' => 'ABCD1234',
            // Starts the temporary profile array.
            'profile' => [
                // Copies the first name.
                'firstName' => $student['firstName'],
                // Copies the last name.
                'lastName' => $student['lastName'],
                // Copies the middle initial, or empty text.
                'middleInitial' => $student['middleInitial'] ?? '',
                // Copies the age, or empty text.
                'age' => $student['age'] ?? '',
                // Copies the gender, or empty text.
                'gender' => $student['gender'] ?? '',
                // Copies the email.
                'email' => $student['email'],
                // Copies the program code.
                'program' => $student['program'],
            // Closes the temporary profile array.
            ],
        // Closes the temporary account array.
        ];
    // Closes the no-account check.
    }
    // Checks whether the password field is empty.
    if ($password === '') {
        // Returns an error saying the password is required.
        return ['ok' => false, 'field' => 'password', 'message' => 'Password is required'];
    // Closes the empty-password check.
    }
    // Compares the typed password with the stored one.
    if ($password !== ($account['password'] ?? '')) {
        // Returns an error for a wrong password.
        return ['ok' => false, 'field' => 'password', 'message' => 'Incorrect password. Please try again.'];
    // Closes the password comparison.
    }
    // Finds the student profile for the account email.
    $student = findStudentByEmail((string) $account['profile']['email']);
    // Checks whether the account still has a student profile.
    if (!$student) {
        // Returns an error telling the user to contact the coordinator.
        return ['ok' => false, 'field' => 'email', 'message' => 'This account no longer has a student profile. Please contact the coordinator.'];
    // Closes the missing-profile check.
    }
    // Remembers the student id in the session.
    $_SESSION['current_student_id'] = $student['id'];
    // Remembers the student email in the session.
    $_SESSION['current_student_email'] = $student['email'];
    // Returns success together with the account profile.
    return ['ok' => true, 'profile' => $account['profile']];
// Closes the authenticateStudent function.
}

// Updates the logged-in student's profile fields in the session store.
function updateLoggedInStudentProfile(array $input): void {
    // Gets the current student email.
    $email = currentStudentEmail(['email' => '']);
    // Checks whether no email is available.
    if ($email === '') {
        // Stops when the student cannot be identified.
        return;
    // Closes the empty-email check.
    }
    // Loads the session students.
    $students = storeGet('students');
    // Loops through the students by reference.
    foreach ($students as &$s) {
        // Skips students whose email does not match.
        if (strcasecmp($s['email'], $email) !== 0) {
            // Moves on to the next student.
            continue;
        // Closes the email check.
        }
        // Updates the first name when one was typed.
        $s['firstName'] = $input['firstName'] ?? $s['firstName'];
        // Updates the last name when one was typed.
        $s['lastName'] = $input['lastName'] ?? $s['lastName'];
        // Updates the middle initial when one was typed.
        $s['middleInitial'] = $input['middleInitial'] ?? $s['middleInitial'];
        // Updates the age when one was typed.
        $s['age'] = $input['age'] ?? $s['age'];
        // Updates the gender when one was typed.
        $s['gender'] = $input['gender'] ?? $s['gender'];
        // Updates the program when one was typed.
        $s['program'] = $input['program'] ?? $s['program'];
        // Checks whether a new email was typed.
        if (!empty($input['email'])) {
            // Stores the new email on the student row.
            $s['email'] = $input['email'];
            // Updates the session email as well.
            $_SESSION['current_student_email'] = $input['email'];
        // Closes the new-email check.
        }
        // Recomputes the track from the program.
        $s['track'] = getTrackForProgram($s['program']);
        // Refreshes the readable track label.
        $s['trackLabel'] = getTrackLabel($s['track']);
        // Saves the matching account profile.
        saveAccount([
            // Copies the first name into the account.
            'firstName' => $s['firstName'],
            // Copies the last name into the account.
            'lastName' => $s['lastName'],
            // Copies the middle initial into the account.
            'middleInitial' => $s['middleInitial'],
            // Copies the age into the account.
            'age' => $s['age'],
            // Copies the gender into the account.
            'gender' => $s['gender'],
            // Copies the email into the account.
            'email' => $s['email'],
            // Copies the program into the account.
            'program' => $s['program'],
        // Closes the account profile array.
        ]);
        // Remembers the student id in the session.
        $_SESSION['current_student_id'] = $s['id'];
        // Stops the loop after updating the matching student.
        break;
    // Closes the loop over students.
    }
    // Breaks the reference after the loop.
    unset($s);
    // Saves the updated students list.
    storeSet('students', $students);
    // Re-sorts the students alphabetically.
    sortSessionStudentsAlphabetically();
// Closes the updateLoggedInStudentProfile function.
}

// Adds a new application to the session store.
function addApplicationRecord(array $a): array {
    // Loads the session applications.
    $list = storeGet('applications');
    // Uses the current time as the new application id.
    $id = time();
    // Finds the track that belongs to the program.
    $track = getTrackForProgram($a['program']);
    // Gets the stage labels in workflow order for that track.
    $stages = getWorkflowStageLabels($track);
    // Uses the given stage key or starts at proposal.
    $stageKey = $a['stageKey'] ?? 'proposal';
    // Starts the record from the default application fields.
    $rec = array_merge(appWorkflowDefaults(), [
        // Sets the application id.
        'id' => $id,
        // Sets the student email that owns the application.
        'studentEmail' => $a['studentEmail'],
        // Sets the student display name.
        'student' => $a['student'],
        // Sets the program code.
        'program' => $a['program'],
        // Sets the track code.
        'track' => $track,
        // Sets the stage label for the stage key.
        'stage' => $stages[$stageKey] ?? $a['stage'] ?? '',
        // Sets the stage key.
        'stageKey' => $stageKey,
        // Sets the paper title.
        'title' => $a['title'],
        // Sets today as the submission date.
        'date' => date('Y-m-d'),
        // Marks the application as submitted.
        'status' => 'submitted',
        // Starts with no coordinator comment.
        'coordinatorComment' => '',
        // Sets the adviser, or an empty string.
        'adviser' => $a['adviser'] ?? '',
        // Sets the abstract, or an empty string.
        'abstract' => $a['abstract'] ?? '',
    // Closes the new application record.
    ]);
    // Adds the record to the applications list.
    $list[] = $rec;
    // Saves the applications list.
    storeSet('applications', $list);
    // Mirrors the new stage and status onto the student row.
    updateStudentStage($a['studentEmail'], $stageKey, 'submitted', $a['title'] ?? null, $a['adviser'] ?? null);
    // Returns the new application record.
    return $rec;
// Closes the addApplicationRecord function.
}

// Finds an application by id, first in the database.
function findApplication(int $id): ?array {
    // Only queries when DB exists and the id is positive.
    if (class_exists('DB') && $id > 0) {
        // Starts the try block for the database lookup.
        try {
            // Prepares the SQL that selects one application with its student details.
            $stmt = DB::getConnection()->prepare(
                // Starts the SQL text that loads applications with student email and formatted name.
                'SELECT a.application_id AS id, u.email AS studentEmail,
                        COALESCE(NULLIF(TRIM(CONCAT(s.last_name, ", ", s.first_name, IF(s.middle_initial IS NULL OR s.middle_initial = "", "", CONCAT(" ", s.middle_initial)))), ""), u.full_name) AS student,
                        CASE WHEN s.program LIKE "%Computer Science%" THEN "MSCS" WHEN s.program LIKE "%Information Technology%" THEN "MIT" WHEN s.program LIKE "%Mathematics%" THEN "MATH" ELSE s.program END AS program,
                        s.track, s.adviser_name AS adviser, a.presentation_stage AS stage,
                        a.paper_title AS title, a.status, a.coordinator_comment AS coordinatorComment,
                        a.result, a.submitted_at AS date
                 FROM applications a
                 INNER JOIN users u ON u.user_id = a.user_id
                 LEFT JOIN students s ON s.user_id = u.user_id
                 WHERE a.application_id = :id AND a.archived_at IS NULL
                 LIMIT 1'
            // Closes the prepared SQL statement text.
            );
            // Runs the query with the application id.
            $stmt->execute(['id' => $id]);
            // Fetches the matching row.
            $row = $stmt->fetch();
            // Checks whether a database row was found.
            if ($row) {
                // Converts the stage label back into a stage key.
                $row['stageKey'] = stageKeyFromLabel((string) $row['stage']);
                // Returns the database application row.
                return $row;
            // Closes the row-found check.
            }
        // Catches query errors so the session fallback still works.
        } catch (Throwable $e) {
        // Closes the catch block.
        }
    // Closes the DB-and-id check.
    }
    // Loops through the session applications.
    foreach (storeGet('applications') as $a) {
        // Compares the stored id with the requested id.
        if ((int) $a['id'] === $id) {
            // Returns the matching session application.
            return $a;
        // Closes the id comparison.
        }
    // Closes the loop over applications.
    }
    // Returns null when the application is not found.
    return null;
// Closes the findApplication function.
}

// Finds the newest application for an email address.
function latestApplicationForEmail(string $email): ?array {
    // Only queries when DB exists and an email is given.
    if (class_exists('DB') && $email !== '') {
        // Starts the try block for the database lookup.
        try {
            // Prepares the SQL that selects the newest application of a student.
            $stmt = DB::getConnection()->prepare(
                // Starts the same application query used to build the coordinator report list.
                'SELECT a.application_id AS id, u.email AS studentEmail,
                        COALESCE(NULLIF(TRIM(CONCAT(s.last_name, ", ", s.first_name, IF(s.middle_initial IS NULL OR s.middle_initial = "", "", CONCAT(" ", s.middle_initial)))), ""), u.full_name) AS student,
                        CASE WHEN s.program LIKE "%Computer Science%" THEN "MSCS" WHEN s.program LIKE "%Information Technology%" THEN "MIT" WHEN s.program LIKE "%Mathematics%" THEN "MATH" ELSE s.program END AS program,
                        s.track, s.adviser_name AS adviser, a.presentation_stage AS stage,
                        a.paper_title AS title, a.status, a.coordinator_comment AS coordinatorComment,
                        a.result, a.submitted_at AS date
                 FROM applications a
                 INNER JOIN users u ON u.user_id = a.user_id
                 LEFT JOIN students s ON s.user_id = u.user_id
                 WHERE u.email = :email AND a.archived_at IS NULL
                 ORDER BY a.submitted_at DESC LIMIT 1'
            // Closes the prepared SQL statement text.
            );
            // Runs the query with the email parameter.
            $stmt->execute(['email' => $email]);
            // Fetches the matching row.
            $row = $stmt->fetch();
            // Checks whether a database row was found.
            if ($row) {
                // Converts the stage label back into a stage key.
                $row['stageKey'] = stageKeyFromLabel((string) $row['stage']);
                // Returns the database application row.
                return $row;
            // Closes the row-found check.
            }
        // Catches query errors so the session fallback still works.
        } catch (Throwable $e) {
        // Closes the catch block.
        }
    // Closes the DB-and-email check.
    }
    // Starts with no fallback application.
    $found = null;
    // Loops through the session applications.
    foreach (storeGet('applications') as $a) {
        // Compares the student emails without caring about case.
        if (strcasecmp((string) $a['studentEmail'], $email) === 0) {
            // Keeps this application as the newest match so far.
            $found = $a;
        // Closes the email comparison.
        }
    // Closes the loop over applications.
    }
    // Returns the newest match, or null.
    return $found;
// Closes the latestApplicationForEmail function.
}

// Updates a student's stage and status, and optionally mirrors the latest application.
function updateStudentStage(string $email, string $stage, string $status, ?string $title = null, ?string $adviser = null, bool $mirrorLatestApp = true): void {
    // Loads the session students.
    $students = storeGet('students');
    // Loops through the students by reference.
    foreach ($students as &$s) {
        // Compares emails without caring about letter case.
        if (strcasecmp($s['email'], $email) === 0) {
            // Stores the new current stage.
            $s['currentStage'] = $stage;
            // Stores the new status.
            $s['status'] = $status;
            // Checks whether a non-empty title was given.
            if ($title !== null && $title !== '') {
                // Updates the paper title.
                $s['title'] = $title;
            // Closes the title check.
            }
            // Checks whether a non-empty adviser was given.
            if ($adviser !== null && $adviser !== '') {
                // Updates the adviser.
                $s['adviser'] = $adviser;
            // Closes the adviser check.
            }
            // Stops the loop once the student is updated.
            break;
        // Closes the email comparison.
        }
    // Closes the loop over students.
    }
    // Breaks the reference after the loop.
    unset($s);
    // Saves the updated students list.
    storeSet('students', $students);

    // Checks whether the latest application should not be mirrored.
    if (!$mirrorLatestApp) {
        // Stops here when mirroring is turned off.
        return;
    // Closes the mirror check.
    }

    // Loads the session applications.
    $apps = storeGet('applications');
    // Starts the latest index at -1, meaning none found.
    $last = -1;
    // Loops through the applications with their indexes.
    foreach ($apps as $i => $a) {
        // Compares the student email without caring about case.
        if (strcasecmp((string) $a['studentEmail'], $email) === 0) {
            // Remembers the index of the newest matching application.
            $last = $i;
        // Closes the email comparison.
        }
    // Closes the loop over applications.
    }
    // Checks whether a matching application was found.
    if ($last >= 0) {
        // Copies the new status onto the application.
        $apps[$last]['status'] = $status;
        // Gets the stage labels for the application's track.
        $stages = getStagesForTrack($apps[$last]['track'] ?? getTrackForProgram($apps[$last]['program']));
        // Checks whether the stage key exists for that track.
        if (isset($stages[$stage])) {
            // Sets the stage key on the application.
            $apps[$last]['stageKey'] = $stage;
            // Sets the readable stage label.
            $apps[$last]['stage'] = $stages[$stage];
        // Closes the stage-key check.
        }
        // Checks whether a title was given.
        if ($title) {
            // Copies the title onto the application.
            $apps[$last]['title'] = $title;
        // Closes the title check.
        }
        // Saves the updated applications list.
        storeSet('applications', $apps);
    // Closes the matching-application check.
    }
// Closes the updateStudentStage function.
}

// Updates an application in the database, or the session store as fallback.
function updateApplicationRecord(int $id, array $patch): ?array {
    // Only uses the database when DB exists and the id is positive.
    if (class_exists('DB') && $id > 0) {
        // Starts the try block for the database update.
        try {
            // Starts the array of columns to update.
            $data = [];
            // Adds the status column when the patch has one.
            if (array_key_exists('status', $patch)) $data['status'] = $patch['status'];
            // Maps the stage key to the presentation_stage column.
            if (array_key_exists('stage', $patch)) $data['presentation_stage'] = $patch['stage'];
            // Maps the coordinator comment to its column.
            if (array_key_exists('coordinatorComment', $patch)) $data['coordinator_comment'] = $patch['coordinatorComment'];
            // Maps the result, storing null when it is empty.
            if (array_key_exists('result', $patch)) $data['result'] = $patch['result'] !== '' ? $patch['result'] : null;
            // Runs the update only when there is something to change.
            if ($data) DB::update('applications', $data, ['application_id' => $id]);
            // Also saves the comment in the application comments table.
            if (!empty($patch['coordinatorComment'])) saveApplicationComment($id, (string) $patch['coordinatorComment']);
            // Returns the application read back from the database.
            return findApplication($id);
        // Catches update errors so the session fallback can run.
        } catch (Throwable $e) {
        // Closes the catch block.
        }
    // Closes the DB-and-id check.
    }
    // Loads the session applications.
    $apps = storeGet('applications');
    // Loops through the applications with their indexes.
    foreach ($apps as $i => $a) {
        // Skips applications whose id is different.
        if ((int) $a['id'] !== $id) {
            // Moves on to the next application.
            continue;
        // Closes the id check.
        }
        // Merges the patch into the matching application.
        $apps[$i] = array_merge($a, $patch);
        // Saves the updated applications list.
        storeSet('applications', $apps);
        // Reads the student email from the updated application.
        $email = $apps[$i]['studentEmail'];
        // Reads the status from the updated application.
        $status = $apps[$i]['status'];
        // Reads the stage key, or defaults to proposal.
        $stage = $apps[$i]['stageKey'] ?? 'proposal';
        // Mirrors the values onto the student without mirroring applications again.
        updateStudentStage($email, $stage, $status, $apps[$i]['title'] ?? null, $apps[$i]['adviser'] ?? null, false);
        // Returns the updated application.
        return $apps[$i];
    // Closes the loop over applications.
    }
    // Returns null when no application matched.
    return null;
// Closes the updateApplicationRecord function.
}

// Adds or replaces an upload record in the session store.
function addUploadRecord(array $u): array {
    // Loads the session uploads.
    $list = storeGet('uploads');
    // Starts the normalized upload record.
    $rec = [
        // Keeps the given id or creates a unique one.
        'id' => (string) ($u['id'] ?? '') !== '' ? (string) $u['id'] : 'up_' . uniqid(),
        // Links the upload to an application when given.
        'applicationId' => $u['applicationId'] ?? null,
        // Stores the student email.
        'studentEmail' => $u['studentEmail'],
        // Stores the original file name.
        'fileName' => $u['fileName'],
        // Stores the document type.
        'docType' => $u['docType'],
        // Stores the workflow stage.
        'stage' => $u['stage'],
        // Stores the file size as an integer.
        'size' => (int) ($u['size'] ?? 0),
        // Stores the upload date or the current date and time.
        'date' => $u['date'] ?? date('Y-m-d H:i:s'),
        // Stores the status or defaults to submitted.
        'status' => $u['status'] ?? 'submitted',
        // Stores notes or an empty string.
        'notes' => $u['notes'] ?? '',
        // Stores the saved file name or an empty string.
        'storedFile' => $u['storedFile'] ?? '',
        // Stores the MIME type or an empty string.
        'mimeType' => $u['mimeType'] ?? '',
    // Closes the upload record array.
    ];
    // Loops through the uploads with their indexes.
    foreach ($list as $i => $existing) {
        // Checks whether an upload with the same id exists.
        if ((string) ($existing['id'] ?? '') === $rec['id']) {
            // Merges the new values into the existing upload.
            $list[$i] = array_merge($existing, $rec);
            // Saves the updated uploads list.
            storeSet('uploads', $list);
            // Returns the updated upload record.
            return $list[$i];
        // Closes the id comparison.
        }
    // Closes the loop over uploads.
    }
    // Adds the new upload record to the list.
    $list[] = $rec;
    // Saves the uploads list.
    storeSet('uploads', $list);
    // Returns the new upload record.
    return $rec;
// Closes the addUploadRecord function.
}

// Validates and stores an uploaded student file in storage uploads.
function storeStudentUpload(array $file): array {
    // Takes the base file name without any folder path.
    $original = basename((string) ($file['name'] ?? ''));
    // Reads the lowercase file extension.
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    // Only allows PDF, DOC, or DOCX files.
    if (!in_array($ext, ['pdf', 'doc', 'docx'], true)) {
        // Throws an error for other file types.
        throw new RuntimeException('Only PDF, DOC, or DOCX files are allowed.');
    // Closes the file-type check.
    }
    // Rejects files larger than 20 MB.
    if ((int) ($file['size'] ?? 0) > 20 * 1024 * 1024) {
        // Throws an error for files that are too big.
        throw new RuntimeException('File exceeds the 20 MB maximum size.');
    // Closes the file-size check.
    }
    // Builds the path to the storage uploads folder.
    $storage = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads';
    // Creates the folder when missing and fails if it still is.
    if (!is_dir($storage) && !mkdir($storage, 0750, true) && !is_dir($storage)) {
        // Throws an error when storage is unavailable.
        throw new RuntimeException('Upload storage is not available.');
    // Closes the storage-folder check.
    }
    // Creates a random stored file name with the original extension.
    $stored = bin2hex(random_bytes(16)) . '.' . $ext;
    // Moves the uploaded file into the storage folder.
    if (!move_uploaded_file((string) $file['tmp_name'], $storage . DIRECTORY_SEPARATOR . $stored)) {
        // Throws an error when the file could not be saved.
        throw new RuntimeException('The file could not be saved. Try again.');
    // Closes the move check.
    }
    // Returns the stored name, MIME type, and original name.
    return ['storedFile' => $stored, 'mimeType' => (string) ($file['type'] ?? ''), 'originalName' => $original];
// Closes the storeStudentUpload function.
}

/**
 * Normalize a MySQL `application_documents` row into the exact shape the
 * session-backed store uses, so every view/consumer works with either source
 * without knowing where the record came from.
 */
// Converts an application documents row into the session store shape.
function normalizeDatabaseDocument(array $row, string $email = ''): array {
    // Starts the normalized document array.
    return [
        // Uses document_id, then id, as the document id.
        'id'            => (string) ($row['document_id'] ?? $row['id'] ?? ''),
        // Keeps the numeric user id, which the inline note marks as critical.
        'user_id'       => isset($row['user_id']) ? (int) $row['user_id'] : null, // <-- THIS IS CRITICAL
        // Uses the application id as an integer, or null.
        'applicationId' => isset($row['application_id']) && $row['application_id'] !== null ? (int) $row['application_id'] : null,
        // Builds a lowercase student email from the email or row.
        'studentEmail'  => strtolower($email !== '' ? $email : (string) ($row['studentEmail'] ?? $row['email'] ?? '')),
        // Uses original name, then fileName, as the file name.
        'fileName'      => (string) ($row['original_name'] ?? $row['fileName'] ?? ''),
        // Uses document type, then docType, as the document type.
        'docType'       => (string) ($row['document_type'] ?? $row['docType'] ?? ''),
        // Reads the stage, or an empty string.
        'stage'         => (string) ($row['stage'] ?? ''),
        // Reads the file size as an integer.
        'size'          => (int) ($row['file_size'] ?? $row['size'] ?? 0),
        // Reads the upload date, or an empty string.
        'date'          => (string) ($row['uploaded_at'] ?? $row['date'] ?? ''),
        // Reads the status, or defaults to submitted.
        'status'        => (string) ($row['status'] ?? 'submitted'),
        // Reads the notes, or an empty string.
        'notes'         => (string) ($row['notes'] ?? ''),
        // Uses stored name, then storedFile, as the saved name.
        'storedFile'    => (string) ($row['stored_name'] ?? $row['storedFile'] ?? ''),
        // Uses mime type, then mimeType, as the MIME type.
        'mimeType'      => (string) ($row['mime_type'] ?? $row['mimeType'] ?? ''),
        // Marks the record as coming from the database.
        'source'        => 'db',
    // Closes the normalized document array.
    ];
// Closes the normalizeDatabaseDocument function.
}

/**
 * Resolve the primary users.user_id for an email address.
 * This is the unified key used by applications + application_documents.
 */
// Finds the users user_id for an email address.
function databaseUserIdForEmail(string $email): int {
    // Returns zero when DB is missing or the email is empty.
    if (!class_exists('DB') || $email === '') {
        // Returns zero because the lookup cannot run.
        return 0;
    // Closes the DB-and-email check.
    }
    // Starts the try block for the user lookup.
    try {
        // Prepares the SQL that selects the user id.
        $stmt = DB::getConnection()->prepare(
            // Selects the user id for the given email.
            'SELECT user_id FROM users WHERE email = :email LIMIT 1'
        // Closes the prepared statement text.
        );
        // Runs the query with the email.
        $stmt->execute(['email' => $email]);
        // Returns the user id, or zero when nothing was found.
        return (int) ($stmt->fetchColumn() ?: 0);
    // Catches query errors so callers get a safe value.
    } catch (Throwable $e) {
        // Returns zero when the lookup fails.
        return 0;
    // Closes the catch block.
    }
// Closes the databaseUserIdForEmail function.
}

// Finds the user id of the first coordinator account.
function databaseCoordinatorUserId(): int {
    // Returns zero when the DB class is not available.
    if (!class_exists('DB')) return 0;
    // Starts the try block for the coordinator query.
    try {
        // Queries the first coordinator user id and returns it, or zero.
        return (int) (DB::getConnection()->query("SELECT user_id FROM users WHERE role = 'coordinator' ORDER BY user_id LIMIT 1")->fetchColumn() ?: 0);
    // Catches query errors so callers get a safe value.
    } catch (Throwable $e) {
        // Returns zero when the query fails.
        return 0;
    // Closes the catch block.
    }
// Closes the databaseCoordinatorUserId function.
}

// Saves a coordinator comment for an application.
function saveApplicationComment(int $applicationId, string $comment): void {
    // Finds the coordinator user id to record as the author.
    $coordinatorId = databaseCoordinatorUserId();
    // Stops when the application, coordinator, or comment is missing.
    if ($applicationId <= 0 || $coordinatorId <= 0 || trim($comment) === '') return;
    // Prepares the INSERT into the application comments table.
    $stmt = DB::getConnection()->prepare('INSERT INTO application_comments (application_id, coordinator_user_id, comment_text) VALUES (:application_id, :coordinator_user_id, :comment_text)');
    // Runs the insert with the application, coordinator, and comment.
    $stmt->execute(['application_id' => $applicationId, 'coordinator_user_id' => $coordinatorId, 'comment_text' => trim($comment)]);
// Closes the saveApplicationComment function.
}

// Saves or updates a payment row for an application.
function saveApplicationPayment(int $applicationId, string $receiptNumber, string $paymentDate, string $amount, int $userId = 0): void {
    // Uses the given user id, or falls back to the coordinator.
    $recorder = $userId > 0 ? $userId : (databaseCoordinatorUserId() ?: 0);
    // Stops when the application or a required payment detail is missing.
    if ($applicationId <= 0 || $recorder <= 0 || $receiptNumber === '' || $paymentDate === '' || $amount === '') return;
    // Prepares the INSERT into the payments table.
    $stmt = DB::getConnection()->prepare(
        // Inserts the payment columns for this application.
        'INSERT INTO payments (application_id, recorded_by_user_id, receipt_number, payment_date, amount)
         VALUES (:application_id, :recorded_by_user_id, :receipt_number, :payment_date, :amount)
         ON DUPLICATE KEY UPDATE recorded_by_user_id = VALUES(recorded_by_user_id), receipt_number = VALUES(receipt_number), payment_date = VALUES(payment_date), amount = VALUES(amount)'
    // Closes the prepared statement call.
    );
    // Starts the values bound to the insert.
    $stmt->execute([
        // Binds the application id.
        'application_id' => $applicationId,
        // Binds the user who recorded the payment.
        'recorded_by_user_id' => $recorder,
        // Binds the receipt number.
        'receipt_number' => $receiptNumber,
        // Binds the payment date.
        'payment_date' => $paymentDate,
        // Binds the amount as a decimal number.
        'amount' => (float) $amount,
    // Closes the bound values array.
    ]);
// Closes the saveApplicationPayment function.
}

/**
 * Save the receipt details the student entered with the official receipt.
 *
 * The details belong to the application of the stage the student is on, so
 * the coordinator sees them in that stage of the application. The payment is
 * kept in the payments table as well, with the student recorded as the one
 * who entered it.
 */
// Saves receipt details on the application and in the payments table.
function saveApplicationReceipt(int $applicationId, string $receiptNumber, string $paymentDate, string $amount, int $userId = 0): void {
    // Stops when the application or a required detail is missing.
    if ($applicationId <= 0 || $receiptNumber === '' || $paymentDate === '' || $amount === '') {
        // Returns without saving.
        return;
    // Closes the required-details check.
    }

    // Notes that the details only belong to a real applications row.
    // The details only belong to a real row of the applications table.
    // Checks that the application row exists in the database.
    $exists = DB::query('SELECT application_id FROM applications WHERE application_id = :id LIMIT 1', ['id' => $applicationId])->fetch();
    // Checks whether the application was not found.
    if (!$exists) {
        // Returns without saving when the row is missing.
        return;
    // Closes the row-exists check.
    }

    // Prepares the UPDATE for the receipt columns.
    DB::getConnection()->prepare(
        // Starts the UPDATE of the applications row.
        'UPDATE applications
            SET receipt_number = :receipt_number,
                payment_date   = :payment_date,
                payment_amount = :payment_amount
          WHERE application_id = :id'
    // Runs the update with the receipt values.
    )->execute([
        // Binds the receipt number.
        'receipt_number' => $receiptNumber,
        // Binds the payment date.
        'payment_date'   => $paymentDate,
        // Binds the payment amount as a decimal number.
        'payment_amount' => (float) $amount,
        // Binds the application id.
        'id'             => $applicationId,
    // Closes the bound values array.
    ]);

    // Also records the payment in the payments table.
    saveApplicationPayment($applicationId, $receiptNumber, $paymentDate, $amount, $userId);
// Closes the saveApplicationReceipt function.
}

/**
 * The receipt details of an application, ready to be shown.
 *
 * @return array{receiptNumber:string,amount:string,date:string}
 */
// Builds the receipt number, amount, and date for display.
function applicationReceiptDetails(array $app): array {
    // Reads and trims the receipt number.
    $receipt = trim((string) ($app['receiptNumber'] ?? ''));
    // Reads the payment amount, or null.
    $amount  = $app['paymentAmount'] ?? null;
    // Reads and trims the payment date.
    $date    = trim((string) ($app['paymentDate'] ?? ''));

    // Starts the receipt details array.
    return [
        // Sets the receipt number.
        'receiptNumber' => $receipt,
        // Formats the amount with two decimals, or leaves it empty.
        'amount'        => ($amount === null || $amount === '') ? '' : number_format((float) $amount, 2),
        // Formats the date as month day year, or leaves it empty.
        'date'          => $date !== '' ? date('M j, Y', strtotime($date)) : '',
    // Closes the details array.
    ];
// Closes the applicationReceiptDetails function.
}

// Loads active templates with their track and stage details.
function databaseTemplateRows(string $trackCode = ''): array {
    // Returns an empty list when the DB class is missing.
    if (!class_exists('DB')) return [];
    // Starts the try block for the template query.
    try {
        // Notes that the Drive columns hold the last-updated text.
        // The Drive columns carry the "last updated" text, so the schema has
        // Notes that the schema must exist before the query runs.
        // to be in place before the query runs.
        // Creates the template sync columns first when that helper exists.
        if (function_exists('ensureTemplateSyncSchema')) {
            // Calls the schema helper.
            ensureTemplateSyncSchema();
        // Closes the helper check.
        }
        // Runs the template query.
        $rows = DB::query(
            // Selects the template id, label, file, and document type.
            'SELECT t.template_id AS id, t.template_name AS label, t.file_name AS file,
                    t.document_type AS docType, t.file_path AS filePath,
                    t.drive_file_id AS driveFileId, t.drive_modified_at AS driveModifiedAt,
                    t.updated_at AS updatedAt, t.needs_review AS needsReview,
                    tr.track_id AS trackId, tr.track_code AS trackCode, tr.track_name AS trackName,
                    t.stage_id AS stageId, ws.stage_key AS stageKey, ws.stage_label AS stage,
                    t.description
             FROM templates t
             INNER JOIN tracks tr ON tr.track_id = t.track_id
             LEFT JOIN workflow_stages ws ON ws.stage_id = t.stage_id
               WHERE t.is_active = 1 AND (:track_code_filter = "" OR tr.track_code = :track_code)
             ORDER BY tr.track_id, ws.stage_order, t.template_name'
           // Passes the track filter values and fetches all rows.
           , ['track_code_filter' => strtolower($trackCode), 'track_code' => strtolower($trackCode)])->fetchAll();
        // Loops through the template rows by reference.
        foreach ($rows as &$row) {
            // Adds the public URL for the template file.
            $row['url'] = templateFileUrl((string) $row['filePath']);
            // Starts the formatted modified date empty.
            $row['modifiedLabel'] = '';
            // Starts the relative modified time empty.
            $row['modifiedAgo']   = '';
            // Reads the Drive modified timestamp.
            $stamp = trim((string) ($row['driveModifiedAt'] ?? ''));
            // Checks whether the Drive timestamp is empty.
            if ($stamp === '') {
                // Falls back to the database updated timestamp.
                $stamp = trim((string) ($row['updatedAt'] ?? ''));
            // Closes the Drive timestamp check.
            }
            // Checks whether a timestamp is available.
            if ($stamp !== '') {
                // Converts the timestamp into Unix time.
                $time = strtotime($stamp);
                // Checks whether the conversion worked.
                if ($time) {
                    // Sets the long modified date.
                    $row['modifiedLabel'] = date('M j, Y', $time);
                    // Sets the short modified date.
                    $row['modifiedShort'] = date('M j', $time);
                    // Sets the relative time, such as 2 days ago.
                    $row['modifiedAgo']   = templateTimeAgo($time);
                // Closes the time-conversion check.
                }
            // Closes the timestamp check.
            }
            // Converts needs_review into a true or false flag.
            $row['needsReview'] = !empty($row['needsReview']);
            // Capitalizes the document type for the label.
            $row['typeLabel'] = ucfirst(strtolower(trim((string) ($row['docType'] ?? 'template'))));
            // Checks whether the label is Form, Template, or Reference.
            if (!in_array($row['typeLabel'], ['Form', 'Template', 'Reference'], true)) {
                // Falls back to the Template label for other values.
                $row['typeLabel'] = 'Template';
            // Closes the label check.
            }
        // Closes the loop over template rows.
        }
        // Breaks the reference after the loop.
        unset($row);
        // Returns the prepared template rows.
        return $rows;
    // Catches query errors so the page still loads.
    } catch (Throwable $e) {
        // Returns an empty list when the query fails.
        return [];
    // Closes the catch block.
    }
// Closes the databaseTemplateRows function.
}

/**
 * The workflow stages of every track, in the order the students work through
 * them.
 *
 * @return array<string, array<int, array{key:string,label:string,order:int}>>
 */
// Builds the ordered workflow stages for every track.
function templateWorkflowStages(): array {
    // Starts with an empty stages array.
    $stages = [];

    // Only queries the database when DB is available.
    if (class_exists('DB')) {
        // Starts the try block for the stage query.
        try {
            // Runs the stage query.
            $rows = DB::query(
                // Selects the track code and the stage key, label, and order.
                'SELECT t.track_code, w.stage_key, w.stage_label, w.stage_order
                   FROM workflow_stages w
                   INNER JOIN tracks t ON t.track_id = w.track_id
                  ORDER BY t.track_id, w.stage_order, w.stage_id'
            // Fetches all stage rows.
            )->fetchAll();
            // Loops through the stage rows.
            foreach ($rows as $row) {
                // Reads the lowercase track code.
                $track = strtolower(trim((string) $row['track_code']));
                // Adds the stage under its track.
                $stages[$track][] = [
                    // Sets the stage key.
                    'key'   => (string) $row['stage_key'],
                    // Sets the stage label.
                    'label' => (string) $row['stage_label'],
                    // Sets the stage order number.
                    'order' => (int) $row['stage_order'],
                // Closes the stage array.
                ];
            // Closes the loop over stage rows.
            }
        // Catches query errors so the fallback below can run.
        } catch (Throwable $e) {
            // Clears the stages so the fallback is used.
            $stages = [];
        // Closes the catch block.
        }
    // Closes the DB check.
    }

    // Notes that the built-in workflow is the fallback.
    // The workflow of the application is the fallback when the tables are
    // Notes the fallback runs when the tables are unavailable.
    // not available.
    // Checks whether no stages were loaded from the database.
    if (!$stages) {
        // Loops through the three track codes.
        foreach (['thesis', 'capstone', 'seminar'] as $track) {
            // Loops through the built-in stages with their indexes.
            foreach (getWorkflow($track)['stages'] as $index => $stage) {
                // Adds the fallback stage under its track.
                $stages[$track][] = [
                    // Sets the stage key, or an empty string.
                    'key'   => (string) ($stage['key'] ?? ''),
                    // Sets the stage label, or an empty string.
                    'label' => (string) ($stage['label'] ?? ''),
                    // Uses the index plus one as the order.
                    'order' => $index + 1,
                // Closes the fallback stage array.
                ];
            // Closes the loop over built-in stages.
            }
        // Closes the loop over tracks.
        }
    // Closes the no-stages check.
    }

    // Returns the stages grouped by track.
    return $stages;
// Closes the templateWorkflowStages function.
}

/**
 * The graduate programs of every track.
 *
 * The programs table holds the courses of the system; the PROGRAMS list in
 * this file holds the same courses with the track they follow. Both are read
 * so that a course is never left out.
 *
 * @return array<string, array<int, array{code:string,name:string}>>
 */
// Builds the list of graduate programs under each track.
function templateCoursesByTrack(): array {
    // Starts with an empty program list for each track.
    $courses = ['thesis' => [], 'capstone' => [], 'seminar' => []];
    // Starts the set of program codes already added.
    $seen    = [];

    // Checks that the DB class is loaded before reading program and track data.
    if (class_exists('DB')) {
        // Starts a guarded block so a database error does not break the page.
        try {
            // Notes that one course can follow more than one track.
            // A course can follow more than one track, so it is listed under
            // Explains that the course is listed under every track it belongs to.
            // every track it belongs to.
            // Runs the query that lists each active program together with its tracks.
            $rows = DB::query(
                // Selects the program code, program name, and track code for each pair.
                'SELECT p.program_code, p.program_name, t.track_code
                   FROM program_tracks pt
                   INNER JOIN programs p ON p.program_id = pt.program_id
                   INNER JOIN tracks t ON t.track_id = pt.track_id
                  WHERE p.is_active = 1
                  ORDER BY p.program_code, pt.is_primary DESC, t.track_id'
            // Fetches every matching program and track row as an array.
            )->fetchAll();
            // Loops over each program and track row returned by MySQL.
            foreach ($rows as $row) {
                // Reads the program code and trims extra spaces.
                $code  = trim((string) $row['program_code']);
                // Reads the track code in lowercase so it matches the track keys.
                $track = strtolower(trim((string) $row['track_code']));
                // Skips rows with no code or a track the page does not display.
                if ($code === '' || !isset($courses[$track])) {
                    // Moves on to the next row without adding this one.
                    continue;
                // Closes the check for an empty code or unknown track.
                }
                // Remembers that this program is already listed under this track.
                $seen[$code][$track] = true;
                // Adds the program to the course list of its track.
                $courses[$track][] = ['code' => $code, 'name' => trim((string) $row['program_name'])];
            // Closes the loop over the database rows.
            }
        // Catches database errors so the page can fall back to defaults.
        } catch (Throwable $e) {
            // Resets the course lists when the database query fails.
            $courses = ['thesis' => [], 'capstone' => [], 'seminar' => []];
        // Closes the database error handler.
        }
    // Closes the block that runs only when the DB class exists.
    }

    // Walks the built-in program list to add any course still missing.
    foreach (PROGRAMS as $code => $label) {
        // Removes a trailing parenthesised note from the program label.
        $name = trim((string) preg_replace('/\s*\([^)]*\)\s*$/', '', $label));
        // Loops over the tracks that this program belongs to.
        foreach (getTracksForProgram($code) as $track) {
            // Skips unknown tracks and programs already added from MySQL.
            if (!isset($courses[$track]) || isset($seen[$code][$track])) {
                // Jumps to the next track for this program.
                continue;
            // Closes the duplicate and unknown-track check.
            }
            // Marks the program as listed under this track.
            $seen[$code][$track] = true;
            // Adds the fallback program entry with its cleaned display name.
            $courses[$track][] = ['code' => $code, 'name' => $name !== '' ? $name : $code];
        // Closes the loop over this program's tracks.
        }
    // Closes the loop over the built-in program list.
    }

    // Returns the course lists grouped by track.
    return $courses;
// Closes the templateCoursesByTrack function.
}

/**
 * The whole template library, grouped the way both portals show it.
 *
 * Every track holds its courses, its workflow stages and its reference
 * documents. A template that belongs to a stage is shown with that stage; a
 * template that belongs to the track itself (the paper format of the track)
 * is shown at every stage, because it is used at every stage.
 *
 * @param  string $trackCode only this track, or every track when empty
 * @return array<int, array<string, mixed>>
 */
// Builds the template library grouped by track, stage, and reference.
function templateLibrary(string $trackCode = ''): array {
    // Loads the template rows from MySQL, optionally for one track.
    $rows      = databaseTemplateRows($trackCode);
    // Loads the workflow stages of each track.
    $stages    = templateWorkflowStages();
    // Loads the courses that each track offers.
    $courses   = templateCoursesByTrack();
    // Starts an empty list of track details.
    $trackMeta = [];

    // Only reads track details when the DB class is available.
    if (class_exists('DB')) {
        // Guards the track query against database errors.
        try {
            // Loops over every track row and records its id, code, and name.
            foreach (DB::query('SELECT track_id, track_code, track_name FROM tracks ORDER BY track_id')->fetchAll() as $track) {
                // Stores each track under its lowercase code for quick lookup.
                $trackMeta[strtolower((string) $track['track_code'])] = [
                    // Saves the numeric track id.
                    'id'   => (int) $track['track_id'],
                    // Saves the lowercase track code.
                    'code' => strtolower((string) $track['track_code']),
                    // Saves the display name of the track.
                    'name' => (string) $track['track_name'],
                // Closes the track details array.
                ];
            // Closes the loop over the tracks table.
            }
        // Catches database errors while loading the tracks.
        } catch (Throwable $e) {
            // Clears the track list when the query fails.
            $trackMeta = [];
        // Closes the track error handler.
        }
    // Closes the block guarded by class_exists.
    }

    // Falls back to the default tracks when no track row was read.
    if (!$trackMeta) {
        // Loops over the three default track codes and names.
        foreach (['thesis' => 'Thesis', 'capstone' => 'Capstone', 'seminar' => 'Seminar Paper'] as $code => $name) {
            // Adds a default track entry with id zero.
            $trackMeta[$code] = ['id' => 0, 'code' => $code, 'name' => $name];
        // Closes the loop over the default tracks.
        }
    // Closes the fallback block for missing tracks.
    }

    // Notes that templates are sorted by track, stage, and track-wide.
    // Sort the templates into their track, their stage and the track itself.
    // Starts the bucket list that groups templates per track.
    $byTrack = [];
    // Loops over every template row to place it in a bucket.
    foreach ($rows as $row) {
        // Reads the track code of the template in lowercase.
        $code = strtolower((string) ($row['trackCode'] ?? ''));
        // Checks whether the template has no track code.
        if ($code === '') {
            // Skips templates without a track because they cannot be grouped.
            continue;
        // Closes the empty track code check.
        }
        // Finds out whether the template is tied to a workflow stage.
        $hasStage  = ($row['stageId'] !== null && (int) $row['stageId'] > 0);
        // Flags a track-wide template that is used at every stage.
        $isTrackWide = (!$hasStage && $row['typeLabel'] === 'Template');
        // Flags a reference document that has no stage.
        $isReference = (!$hasStage && $row['typeLabel'] !== 'Template');

        // Detects a form template that has no stage attached.
        if (!$hasStage && $row['typeLabel'] === 'Form') {
            // Explains that a form without a stage cannot be filed.
            // A form without a stage cannot be filed, so it is flagged for
            // Explains that such a form is flagged for the coordinator.
            // the coordinator instead of being put in the wrong place.
            // Marks the stage-less form so the coordinator sees it.
            $row['needsReview'] = true;
        // Closes the stage-less form check.
        }

        // Chooses the bucket: references, trackWide, or staged.
        $bucket = $isReference ? 'references' : ($isTrackWide ? 'trackWide' : 'staged');
        // Stores the template under its track, bucket, and stage id.
        $byTrack[$code][$bucket][$hasStage ? (int) $row['stageId'] : 0][] = $row;
    // Closes the loop over the template rows.
    }

    // Starts the final library list returned to the caller.
    $library = [];

    // Loops over each track to build its library entry.
    foreach ($trackMeta as $code => $meta) {
        // Skips tracks other than the one that was requested.
        if ($trackCode !== '' && $code !== strtolower($trackCode)) {
            // Moves to the next track when the current one is filtered out.
            continue;
        // Closes the track filter check.
        }

        // Reads the template buckets of this track.
        $group      = $byTrack[$code] ?? [];
        // Reads the templates that apply to the whole track.
        $trackWide  = $group['trackWide'][0] ?? [];
        // Reads the reference documents of this track.
        $references = $group['references'][0] ?? [];
        // Reads the stage-specific templates of this track.
        $staged     = $group['staged'] ?? [];

        // Starts the list of stages for this track.
        $stageList = [];
        // Loops over the workflow stages of this track.
        foreach (($stages[$code] ?? []) as $stage) {
            // Starts the template list for the current stage.
            $items = [];
            // Loops over the staged template buckets keyed by stage id.
            foreach ($staged as $stageId => $bucket) {
                // Loops over each template inside the bucket.
                foreach ($bucket as $item) {
                    // Checks whether the template belongs to this stage.
                    if ((string) ($item['stageKey'] ?? '') === $stage['key']) {
                        // Adds the matching template to the stage list.
                        $items[] = $item;
                    // Closes the stage match check.
                    }
                // Closes the loop over the bucket items.
                }
            // Closes the loop over the stage buckets.
            }
            // Notes that track-wide templates appear at every stage.
            // The templates of the track itself are used at every stage.
            // Adds every track-wide template to this stage.
            foreach ($trackWide as $item) {
                // Appends the track-wide template to the stage items.
                $items[] = $item;
            // Closes the loop over track-wide templates.
            }

            // Starts one stage entry for the library.
            $stageList[] = [
                // Saves the stage key.
                'key'       => $stage['key'],
                // Saves the stage label shown to users.
                'label'     => $stage['label'],
                // Saves the position of the stage in the workflow.
                'order'     => $stage['order'],
                // Saves the templates that belong to the stage.
                'templates' => $items,
            // Closes the stage entry.
            ];
        // Closes the loop over the track's stages.
        }

        // Collects track-wide and reference templates for the review check.
        $allOfTrack = array_merge($trackWide, $references);
        // Loops over the stage buckets of this track.
        foreach ($staged as $bucket) {
            // Adds the stage templates to the same collection.
            $allOfTrack = array_merge($allOfTrack, $bucket);
        // Closes the loop over the stage buckets.
        }

        // Keeps only the templates that need coordinator review.
        $review = array_values(array_filter(
            // Passes the collected templates to the filter.
            $allOfTrack,
            // Selects the items flagged with needsReview.
            static fn($item) => !empty($item['needsReview'])
        // Closes the filter call and reindexes the results.
        ));

        // Notes that templates with missing files are still listed.
        // Templates that are put aside because their file is not there yet
        // Explains that this keeps the gap visible instead of silent.
        // are listed as well, so the gap is not silent.
        // Loops over the templates whose files are missing.
        foreach (templateMissingTemplates($code) as $missing) {
            // Tags the item so the page can explain why it is listed.
            $missing['reason'] = 'missing';
            // Adds the missing-file template to the review list.
            $review[] = $missing;
        // Closes the loop over missing templates.
        }

        // Starts one track entry for the returned library.
        $library[] = [
            // Saves the track code.
            'trackCode'   => $code,
            // Saves the track name.
            'trackName'   => $meta['name'],
            // Saves the numeric track id.
            'trackId'     => $meta['id'],
            // Saves the courses offered under this track.
            'courses'     => $courses[$code] ?? [],
            // Saves the stage list with its templates.
            'stages'      => $stageList,
            // Saves the reference documents of the track.
            'references'  => $references,
            // Saves the templates used at every stage.
            'trackWide'   => $trackWide,
            // Saves the items that need coordinator review.
            'needsReview' => $review,
        // Closes the track library entry.
        ];
    // Closes the loop over the tracks.
    }

    // Returns the grouped template library.
    return $library;
// Closes the templateLibrary function.
}

/**
 * The templates of a track whose file is not available yet.
 *
 * They are kept in the table so nothing is lost, but they are not part of
 * the library until the file is there.
 */
// Returns the templates of a track whose file is still missing.
function templateMissingTemplates(string $trackCode): array {
    // Checks that the DB class is available before querying.
    if (!class_exists('DB')) {
        // Returns an empty list when there is no database.
        return [];
    // Closes the DB availability check.
    }
    // Starts a guarded block for the missing-template query.
    try {
        // Queries the templates table for disabled items waiting for a file.
        $rows = DB::query(
            // Selects the template id, label, file, and review flags.
            'SELECT t.template_id AS id, t.template_name AS label, t.file_name AS file,
                    t.description, t.needs_review AS needsReview,
                    tr.track_id AS trackId, tr.track_code AS trackCode, tr.track_name AS trackName,
                    t.stage_id AS stageId, ws.stage_key AS stageKey, ws.stage_label AS stage
               FROM templates t
               INNER JOIN tracks tr ON tr.track_id = t.track_id
               LEFT JOIN workflow_stages ws ON ws.stage_id = t.stage_id
              WHERE t.is_active = 0 AND t.needs_review = 1 AND tr.track_code = :code
              ORDER BY t.template_name'
            // Passes the lowercase track code as the query parameter.
            , ['code' => strtolower($trackCode)]
        // Fetches all matching template rows.
        )->fetchAll();
        // Loops by reference so each row can be changed in place.
        foreach ($rows as &$row) {
            // Marks each row as needing review.
            $row['needsReview'] = true;
            // Sets the type label used by the library grouping.
            $row['typeLabel']   = 'Template';
        // Closes the loop over the rows.
        }
        // Breaks the reference after the loop to avoid side effects.
        unset($row);
        // Returns the missing templates.
        return $rows;
    // Catches database errors while reading the templates.
    } catch (Throwable $e) {
        // Returns an empty list when the query fails.
        return [];
    // Closes the error handler.
    }
// Closes the templateMissingTemplates function.
}

// Creates a template row and stores its uploaded file.
function createTemplateRecord(array $data, array $file): array {
    // Takes the safe base name of the uploaded file.
    $original = basename((string) ($file['name'] ?? ''));
    // Reads the lowercase extension of the file name.
    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    // Allows only PDF, DOC, and DOCX template files.
    if (!in_array($extension, ['pdf', 'doc', 'docx'], true)) {
        // Stops the upload with a clear message for other file types.
        throw new RuntimeException('Only PDF, DOC, or DOCX templates are allowed.');
    // Closes the file type check.
    }
    // Rejects files over 20 MB or uploads that failed.
    if ((int) ($file['size'] ?? 0) > 20 * 1024 * 1024 || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        // Stops the upload with an invalid file message.
        throw new RuntimeException('The template file is invalid or exceeds 20 MB.');
    // Closes the size and upload error check.
    }
    // Sets the folder where template files are saved.
    $storage = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'templates';
    // Creates the template folder when it does not exist.
    if (!is_dir($storage) && !mkdir($storage, 0750, true) && !is_dir($storage)) {
        // Stops when the storage folder cannot be created.
        throw new RuntimeException('Template storage is not available.');
    // Closes the storage folder check.
    }
    // Makes a random stored file name with the original extension.
    $stored = bin2hex(random_bytes(16)) . '.' . $extension;
    // Moves the uploaded file into the template storage folder.
    if (!move_uploaded_file((string) $file['tmp_name'], $storage . DIRECTORY_SEPARATOR . $stored)) {
        // Stops when the file cannot be saved.
        throw new RuntimeException('The template file could not be saved.');
    // Closes the move upload check.
    }
    // Looks up the track chosen by the coordinator.
    $track = DB::find('tracks', ['track_code' => strtolower((string) $data['program_track'])]);
    // Stops when the chosen track is not configured.
    if (!$track) throw new RuntimeException('The selected track is not configured.');
    // Looks up the stage by track id and stage label.
    $stage = DB::find('workflow_stages', ['track_id' => (int) $track['track_id'], 'stage_label' => $data['stage_label']]);
    // Notes that the coordinator already chose the track and stage.
    // The coordinator picked the track and the stage, so nothing is left to
    // Explains that a stage-less form is flagged instead of filed.
    // review. A form without a stage is flagged instead of being filed.
    // Flags a form only when no matching stage was found.
    $needsReview = (($data['document_type'] ?? '') === 'form' && empty($stage['stage_id']));

    // Opens the shared database connection.
    $pdo = DB::getConnection();
    // Prepares the insert for the new template row.
    $stmt = $pdo->prepare(
        // Inserts the track, stage, name, file, and review flags.
        'INSERT INTO `templates`
            (`track_id`, `stage_id`, `template_name`, `description`, `document_type`,
             `file_name`, `file_path`, `mime_type`, `is_active`, `needs_review`, `managed_by_user_id`)
         VALUES
            (:track_id, :stage_id, :template_name, :description, :document_type,
             :file_name, :file_path, :mime_type, 1, :needs_review, :managed_by_user_id)'
    // Closes the prepared insert statement.
    );
    // Runs the insert with the template values.
    $stmt->execute([
        // Binds the chosen track id.
        'track_id' => (int) $track['track_id'],
        // Binds the stage id, or null when none was found.
        'stage_id' => $stage['stage_id'] ?? null,
        // Binds the trimmed template name.
        'template_name' => trim($data['template_name']),
        // Binds the trimmed description, or null when it is empty.
        'description' => trim((string) ($data['description'] ?? '')) ?: null,
        // Binds the document type chosen by the coordinator.
        'document_type' => $data['document_type'],
        // Binds the original file name.
        'file_name' => $original,
        // Binds the file path inside storage/templates.
        'file_path' => 'storage/templates/' . $stored,
        // Binds the MIME type, or null when it is unknown.
        'mime_type' => (string) ($file['type'] ?? '') ?: null,
        // Binds 1 when the template needs review, otherwise 0.
        'needs_review' => $needsReview ? 1 : 0,
        // Binds the coordinator user id, falling back to 1.
        'managed_by_user_id' => databaseCoordinatorUserId() ?: 1,
    // Closes the value list for the insert.
    ]);

    // Returns the newly inserted template row.
    return DB::find('templates', ['template_id' => (int) $pdo->lastInsertId()]);
// Closes the createTemplateRecord function.
}
// Finds one template row by its numeric id.
function findTemplateRecord(string $id): ?array {
    // Returns null for a missing DB class or a non-numeric id.
    if (!class_exists('DB') || !ctype_digit($id)) return null;
    // Prepares a query that joins the template, its track, and stage.
    $stmt = DB::getConnection()->prepare('SELECT t.*, tr.track_code, ws.stage_label FROM templates t INNER JOIN tracks tr ON tr.track_id = t.track_id LEFT JOIN workflow_stages ws ON ws.stage_id = t.stage_id WHERE t.template_id = :id LIMIT 1');
    // Runs the query with the numeric template id.
    $stmt->execute(['id' => (int) $id]);
    // Returns the template row, or null when nothing matched.
    return $stmt->fetch() ?: null;
// Closes the findTemplateRecord function.
}

// Updates a template, replacing its file when a new one is sent.
function updateTemplateRecord(string $id, array $data, array $file = []): ?array {
    // Loads the existing template row first.
    $template = findTemplateRecord($id);
    // Stops when the template does not exist.
    if (!$template) return null;
    // Starts the fields that can be updated in place.
    $fields = [
        // Sets the new trimmed template name.
        'template_name' => trim($data['template_name']),
        // Sets the description, or null when it is empty.
        'description' => trim((string) ($data['description'] ?? '')) ?: null,
        // Sets the new document type.
        'document_type' => $data['document_type'],
    // Closes the field list.
    ];
    // Checks whether a replacement file was uploaded.
    if (!empty($file['name'])) {
        // Creates a new template row that uses the replacement file.
        $replacement = createTemplateRecord($data + ['program_track' => $template['track_code']], $file);
        // Disables the old template row.
        DB::update('templates', ['is_active' => 0], ['template_id' => (int) $template['template_id']]);
        // Returns the new template instead of the old one.
        return $replacement;
    // Closes the replacement file branch.
    }
    // Saves the edited fields without touching the file.
    DB::update('templates', $fields, ['template_id' => (int) $id]);
    // Returns the freshly updated template row.
    return findTemplateRecord($id);
// Closes the updateTemplateRecord function.
}

/**
 * Resolve the MySQL students.student_id for an email address.
 * Kept for screens that still need the students PK (profile edits); new
 * application/document relations use databaseUserIdForEmail() instead.
 */
// Finds the students.student_id that belongs to an email address.
function databaseStudentIdForEmail(string $email): int {
    // Finds the users.user_id for the email first.
    $userId = databaseUserIdForEmail($email);
    // Checks whether no user matched the email.
    if ($userId <= 0) {
        // Returns zero when the user is unknown.
        return 0;
    // Closes the user id check.
    }
    // Guards the student lookup against database errors.
    try {
        // Prepares the query for the student row of this user.
        $stmt = DB::getConnection()->prepare(
            // Selects the student id linked to the user id.
            'SELECT student_id FROM students WHERE user_id = :user_id LIMIT 1'
        // Closes the prepared statement.
        );
        // Runs the query with the user id.
        $stmt->execute(['user_id' => $userId]);
        // Returns the student id, or 0 when none was found.
        return (int) ($stmt->fetchColumn() ?: 0);
    // Catches database errors in the student lookup.
    } catch (Throwable $e) {
        // Returns zero when the lookup fails.
        return 0;
    // Closes the error handler.
    }
// Closes the databaseStudentIdForEmail function.
}

/**
 * Latest (or stage-matching) application id for a user. Used to link a
 * freshly uploaded document to the student's active application so the
 * coordinator panel can surface it immediately.
 * NOTE: unified key is users.user_id (not students.student_id).
 */
// Finds the application id for a user, optionally for one stage.
function databaseApplicationIdForStudent(int $userId, string $stageLabel = ''): ?int {
    // Returns early when the DB class or the user id is missing.
    if (!class_exists('DB') || $userId <= 0) {
        // Returns null when there is no database or user.
        return null;
    // Closes the availability check.
    }
    // Guards the application lookups against database errors.
    try {
        // Opens the shared database connection.
        $pdo = DB::getConnection();
        // Checks whether a stage label was given.
        if ($stageLabel !== '') {
            // Prepares a query for the application of this stage.
            $stmt = $pdo->prepare(
                // Selects the id and stage of the matching application.
                'SELECT application_id, presentation_stage FROM applications
                 WHERE user_id = :user_id AND archived_at IS NULL AND presentation_stage = :stage
                 ORDER BY submitted_at DESC LIMIT 1'
            // Closes the prepared statement.
            );
            // Runs the query with the user id and stage label.
            $stmt->execute(['user_id' => $userId, 'stage' => $stageLabel]);
            // Reads the first application id returned.
            $exact = $stmt->fetchColumn();
            // Checks whether an exact stage match was found.
            if ($exact) {
                // Returns the application id of the exact stage match.
                return (int) $exact;
            // Closes the exact match check.
            }

            // Prepares a query for all live applications of the user.
            $stageStmt = $pdo->prepare(
                // Selects the id and stage of every live application.
                'SELECT application_id, presentation_stage FROM applications
                 WHERE user_id = :user_id AND archived_at IS NULL
                 ORDER BY submitted_at DESC'
            // Closes the prepared statement.
            );
            // Runs the query with the user id.
            $stageStmt->execute(['user_id' => $userId]);
            // Converts the requested stage label into a stage key.
            $requestedStageKey = stageKeyFromLabel($stageLabel);
            // Loops over the applications of this user.
            foreach ($stageStmt->fetchAll() as $application) {
                // Checks whether the application is for the same stage.
                if (stageKeyFromLabel((string) $application['presentation_stage']) === $requestedStageKey) {
                    // Returns the matching application id.
                    return (int) $application['application_id'];
                // Closes the stage key match check.
                }
            // Closes the loop over the applications.
            }
        // Closes the stage label branch.
        }
        // Prepares the fallback query for the latest application.
        $stmt = $pdo->prepare(
            // Selects the newest application id of the user.
            'SELECT application_id FROM applications
             WHERE user_id = :user_id AND archived_at IS NULL
             ORDER BY submitted_at DESC LIMIT 1'
        // Closes the prepared statement.
        );
        // Runs the fallback query with the user id.
        $stmt->execute(['user_id' => $userId]);
        // Returns the latest application id, or null when none exists.
        return ($id = $stmt->fetchColumn()) ? (int) $id : null;
    // Catches database errors in the application lookup.
    } catch (Throwable $e) {
        // Returns null when the lookup fails.
        return null;
    // Closes the error handler.
    }
// Closes the databaseApplicationIdForStudent function.
}

/**
 * Every non-archived application for a user, newest first.
 * Returns the title/stage the student submitted (spelling included).
 * The student's "Application" page no longer lists a history table —
 * the coordinator reviews submissions from their own side — so this is
 * kept as a lookup helper for records and reporting.
 */
// Lists every non-archived application of a user, newest first.
function databaseApplicationsForUser(int $userId): array {
    // Returns early when the DB class or the user id is missing.
    if (!class_exists('DB') || $userId <= 0) {
        // Returns an empty list when there is no database or user.
        return [];
    // Closes the availability check.
    }
    // Guards the application list query against errors.
    try {
        // Prepares the query for the user's applications.
        $stmt = DB::getConnection()->prepare(
            // Selects the id, stage, title, status, and date.
            'SELECT application_id AS id, presentation_stage AS stage,
                    paper_title AS title, status, submitted_at AS date
             FROM applications
             WHERE user_id = :user_id AND archived_at IS NULL
             ORDER BY submitted_at DESC, application_id DESC'
        // Closes the prepared statement.
        );
        // Runs the query with the user id.
        $stmt->execute(['user_id' => $userId]);
        // Starts the list of application rows.
        $rows = [];
        // Loops over every application row.
        foreach ($stmt->fetchAll() as $row) {
            // Converts the application id to an integer.
            $row['id'] = (int) $row['id'];
            // Adds the stage key derived from the stage label.
            $row['stageKey'] = stageKeyFromLabel((string) ($row['stage'] ?? ''));
            // Adds the row to the result list.
            $rows[] = $row;
        // Closes the loop over the application rows.
        }
        // Returns the application list.
        return $rows;
    // Catches database errors in the application list.
    } catch (Throwable $e) {
        // Returns an empty list when the query fails.
        return [];
    // Closes the error handler.
    }
// Closes the databaseApplicationsForUser function.
}

/**
 * Read student documents straight from MySQL, optionally scoped.
 * This is what makes an upload visible to the coordinator: the student and the
 * coordinator are different PHP sessions, so a session-only store can never be
 * seen across accounts.
 *
 * Unified key: application_documents.user_id -> users.user_id.
 *
 * $applicationId NULL + $userId set  -> every document for that user
 * $applicationId set + $userId set   -> documents linked to the application
 *                                          OR still unlinked (application_id IS NULL)
 */
// Reads student documents from MySQL with optional filtering.
function databaseDocuments(?int $applicationId = null, int $userId = 0, bool $includeUnlinked = true): array {
    // Checks that the DB class is available.
    if (!class_exists('DB')) {
        // Returns an empty list without a database.
        return [];
    // Closes the DB availability check.
    }
    // Guards the document query against database errors.
    try {
        // Opens the shared database connection.
        $pdo = DB::getConnection();
        // Starts the list of SQL conditions.
        $where = [];
        // Starts the list of bound query parameters.
        $params = [];
        // Checks whether an application id was supplied.
        if ($applicationId !== null && $applicationId > 0) {
            // Also includes unlinked documents of the same user.
            if ($userId > 0 && $includeUnlinked) {
                // Filters by the application or the user's unlinked documents.
                $where[] = '(d.application_id = :application_id OR (d.application_id IS NULL AND d.user_id = :user_id))';
                // Binds the application id.
                $params['application_id'] = $applicationId;
                // Binds the user id used for unlinked documents.
                $params['user_id'] = $userId;
            // Handles the case where only the application id is used.
            } else {
                // Filters documents by application id only.
                $where[] = 'd.application_id = :application_id';
                // Binds the application id.
                $params['application_id'] = $applicationId;
            // Closes the application-only branch.
            }
        // Uses only the user id when no application id was given.
        } elseif ($userId > 0) {
            // Filters documents by user id.
            $where[] = 'd.user_id = :user_id';
            // Binds the user id.
            $params['user_id'] = $userId;
        // Handles a call with no application and no user.
        } else {
            // Returns nothing when the scope is unknown.
            return [];
        // Closes the scope checks.
        }
        // Builds the select for document details and the student email.
        $sql = 'SELECT d.document_id, d.application_id, d.user_id, d.stage, d.document_type,
                       d.original_name, d.stored_name, d.mime_type, d.file_size, d.status, d.uploaded_at,
                       u.email AS studentEmail
                FROM application_documents d
                INNER JOIN users u ON u.user_id = d.user_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY d.uploaded_at DESC, d.document_id DESC';
        // Prepares the final document query.
        $stmt = $pdo->prepare($sql);
        // Runs the query with the bound parameters.
        $stmt->execute($params);
        // Converts each database row into the app document shape.
        return array_map(
            // Normalizes one row and passes the student email.
            static fn($row) => normalizeDatabaseDocument($row, (string) ($row['studentEmail'] ?? '')),
            // Passes all fetched document rows to the mapper.
            $stmt->fetchAll()
        // Closes the array_map call.
        );
    // Catches database errors in the document query.
    } catch (Throwable $e) {
        // Returns an empty list when the query fails.
        return [];
    // Closes the error handler.
    }
// Closes the databaseDocuments function.
}

/**
 * Fetch a single document by its MySQL primary key.
 */
// Fetches one application document by its primary key.
function databaseDocumentById(int $documentId): ?array {
    // Returns early for a missing DB or an invalid document id.
    if (!class_exists('DB') || $documentId <= 0) {
        // Returns null when the document cannot be queried.
        return null;
    // Closes the availability check.
    }
    // Guards the single document query against errors.
    try {
        // Prepares the query for one document.
        $stmt = DB::getConnection()->prepare(
            // Selects the document fields and the student email.
            'SELECT d.document_id AS id, d.document_id, d.application_id, d.user_id, d.stage, d.document_type,
                    d.original_name, d.stored_name, d.mime_type, d.file_size, d.status, d.uploaded_at,
                    u.email AS studentEmail
             FROM application_documents d
             LEFT JOIN users u ON u.user_id = d.user_id
             WHERE d.document_id = :document_id
             LIMIT 1'
        // Closes the prepared statement.
        );
        // Runs the query with the document id.
        $stmt->execute(['document_id' => $documentId]);
        // Fetches the matching document row.
        $row = $stmt->fetch();
        // Normalizes the row or returns null when it is not found.
        return $row ? normalizeDatabaseDocument($row, (string) ($row['studentEmail'] ?? '')) : null;
    // Catches database errors in the single document query.
    } catch (Throwable $e) {
        // Returns null when the query fails.
        return null;
    // Closes the error handler.
    }
// Closes the databaseDocumentById function.
}

// Maps a document type label to a short kind for comparisons.
function classifyUploadDocType(string $docType): string {
    // Lowercases the label so matching ignores case.
    $doc = strtolower($docType);
    // Checks whether the type is a payment receipt.
    if (str_contains($doc, 'receipt') || str_contains($doc, 'payment')) {
        // Treats it as a receipt.
        return 'receipt';
    // Closes the receipt check.
    }
    // Checks whether the type is a Graduate School endorsement.
    if (str_contains($doc, 'graduate school')) {
        // Treats it as a Graduate School endorsement.
        return 'grad_school_endorsement';
    // Closes the Graduate School check.
    }
    // Checks whether the type is an adviser endorsement.
    if (str_contains($doc, 'endorsement') || str_contains($doc, 'adviser')) {
        // Treats it as an adviser endorsement.
        return 'adviser_endorsement';
    // Closes the adviser endorsement check.
    }
    // Falls back to the paper kind for every other document.
    return 'paper';
// Closes the classifyUploadDocType function.
}

/**
 * The newest file of a kind.
 *
 * A student may send the same kind of document more than once. The file that
 * was sent last is the one the coordinator reviews, so it is the one that
 * decides the state of a milestone and of a requirement. Every place that
 * looks for the document of a kind uses this one rule, so the list of
 * documents of the coordinator, the milestone tracker and the requirements of
 * the stage can never disagree.
 */
// Finds the newest upload of a given document kind.
function latestUploadOfKind(array $uploads, string $kind): ?array {
    // Starts with no newest upload.
    $latest = null;
    // Starts with an empty newest date.
    $latestDate = '';
    // Starts with no newest upload id.
    $latestId = null;
    // Loops over every upload passed in.
    foreach ($uploads as $upload) {
        // Skips uploads that belong to a different kind.
        if (classifyUploadDocType((string) ($upload['docType'] ?? '')) !== $kind) {
            // Moves to the next upload.
            continue;
        // Closes the kind check.
        }
        // Reads the upload date as text.
        $date = (string) ($upload['date'] ?? '');
        // Reads the upload id as text.
        $id   = (string) ($upload['id'] ?? '');
        // Notes that uploads in the same second are ordered by id.
        // Two files that were sent in the same second are told apart by their
        // Explains that the higher id is treated as the newer file.
        // number, so the one that was added last is the newer one.
        // Starts checking whether this upload is newer.
        $newer = $latest === null
            // Accepts an upload that has a later date.
            || $date > $latestDate
            // Breaks a same-date tie using the higher id.
            || ($date === $latestDate && $latestId !== null && $id !== '' && $id > $latestId);
        // Checks whether this upload beats the current newest.
        if ($newer) {
            // Records this upload as the newest one.
            $latest = $upload;
            // Saves the date of the newest upload.
            $latestDate = $date;
            // Saves the id of the newest upload.
            $latestId = $id !== '' ? $id : $latestId;
        // Closes the newer upload check.
        }
    // Closes the loop over the uploads.
    }
    // Returns the newest upload, or null when none matched.
    return $latest;
// Closes the latestUploadOfKind function.
}

/**
 * The check of an upload against the workflow of the track.
 *
 * The Milestone Tracker decides which document the student may send now, so
 * this check uses the same rule as the Document Type list of the Application
 * page and the Upload buttons of the Dashboard.
 *
 * Returns null when the upload is allowed, otherwise the reason.
 */
// Checks whether the student may send this document now.
function validateUploadSequence(string $email, string $track, string $stageKey, string $docType): ?string {
    // Trims spaces from the chosen document type.
    $docType = trim($docType);
    // Checks whether no document type was chosen.
    if ($docType === '') {
        // Asks the student to choose a document first.
        return 'Choose the document you are sending.';
    // Closes the empty document type check.
    }

    // Loads the workflow stages of the student's track.
    $stages = getWorkflow($track)['stages'] ?? [];
    // Falls back to the stage key as the stage label.
    $stageLabel = $stageKey;
    // Loops over the stages to find the matching one.
    foreach ($stages as $s) {
        // Checks whether this stage matches the requested key.
        if (($s['key'] ?? '') === $stageKey) {
            // Uses the stage label when the key matches.
            $stageLabel = $s['label'] ?? $stageKey;
            // Stops searching once the stage is found.
            break;
        // Closes the stage match check.
        }
    // Closes the loop over the stages.
    }

    // Reads the student's progress for this track.
    $progress = getStudentProgress($email, $track);

    // Notes that a stage the student has not reached stays locked.
    // A stage the student has not reached yet stays locked: the workflow of
    // Explains that the workflow asks for one stage after another.
    // the track asks for one stage after the other.
    // Works out which stages accept uploads right now.
    $stageOpen = openStagesForUploads($progress, $stages);
    // Checks whether this stage is still locked.
    if (isset($stageOpen[$stageKey]) && !$stageOpen[$stageKey]) {
        // Tells the student that the stage is not open yet.
        return 'You cannot send documents for ' . $stageLabel . ' yet. '
            // Points the student to the dashboard Milestone Tracker.
            . 'Follow the Milestone Tracker on your dashboard for the stage you are working on.';
    // Closes the locked stage check.
    }

    // Lists the document types that may be sent at this stage now.
    $open = openDocumentTypes(stageMilestoneSteps($email, $track, $stageKey));
    // Loops over the allowed document types.
    foreach ($open as $type) {
        // Checks whether the chosen type is allowed now.
        if (strcasecmp($type, $docType) === 0) {
            // Returns null to mean the upload is allowed.
            return null;
        // Closes the allowed type check.
        }
    // Closes the loop over the allowed types.
    }

    // Reads the document type that the workflow expects first.
    $expected = $open[0] ?? '';
    // Checks whether another document must be sent first.
    if ($expected !== '') {
        // Tells the student which document comes first.
        return 'Your workflow for ' . $stageLabel . ' asks for the ' . $expected
            // Explains that the coordinator can review it afterwards.
            . ' first. Send that one, then the coordinator can review it.';
    // Closes the expected document check.
    }
    // Explains that every document of the stage was already sent.
    return 'All the documents of ' . $stageLabel . ' are already with the coordinator. '
        // Points the student to what comes next on the tracker.
        . 'Follow the Milestone Tracker on your dashboard for what comes next.';
// Closes the validateUploadSequence function.
}

// Finds the session application row for an email and stage.
function findStageApplicationForEmail(string $email, string $stageKey): ?array {
    // Loops over the applications kept in the session store.
    foreach (storeGet('applications') as $a) {
        // Skips rows that belong to another email.
        if (strcasecmp((string) ($a['studentEmail'] ?? ''), $email) !== 0) {
            // Moves to the next application row.
            continue;
        // Closes the email match check.
        }
        // Checks whether the row is for the requested stage.
        if (($a['stageKey'] ?? stageKeyFromLabel($a['stage'] ?? '')) === $stageKey) {
            // Returns the matching application row.
            return $a;
        // Closes the stage match check.
        }
    // Closes the loop over the applications.
    }
    // Returns null when no matching application exists.
    return null;
// Closes the findStageApplicationForEmail function.
}

// Makes sure an application row exists for the email and stage.
function ensureStageApplication(string $email, string $stageKey, string $stageLabel): ?array {
    // Normalizes the email to lowercase without spaces.
    $email = strtolower(trim($email));
    // Checks whether the email is empty.
    if ($email === '') {
        // Returns null when there is no email to use.
        return null;
    // Closes the empty email check.
    }
    // Looks for an existing row for this email and stage.
    $existing = findStageApplicationForEmail($email, $stageKey);
    // Reads the track from the existing row, if there is one.
    $track = $existing['track'] ?? null;
    // Checks whether the track is still unknown.
    if ($track === null) {
        // Looks up the student profile for this email.
        $student = findStudentByEmail($email);
        // Uses the student's track or derives it from the program.
        $track = $student['track'] ?? getTrackForProgram($student['program'] ?? 'MSCS');
    // Closes the track lookup branch.
    }
    // Checks whether no track could be found.
    if ($track === null || $track === '') {
        // Falls back to the thesis track.
        $track = 'thesis';
    // Closes the track fallback check.
    }
    // Reads the stage labels of the chosen track.
    $stages = getWorkflowStageLabels($track);
    // Uses the standard stage label when one exists.
    $canonLabel = $stages[$stageKey] ?? $stageLabel;

    // Checks whether an application row already exists.
    if ($existing) {
        // Checks whether the row is still unsubmitted.
        if (in_array($existing['status'] ?? '', ['not_started', 'pending', 'draft', ''], true)) {
            // Marks the existing row as submitted.
            updateApplicationRecord((int) $existing['id'], ['status' => 'submitted']);
            // Updates the in-memory status to match the store.
            $existing['status'] = 'submitted';
        // Closes the status update check.
        }
    // Handles the case where no application row exists.
    } else {
        // Looks up the student profile again for the new row.
        $student = findStudentByEmail($email);
        // Creates a new application row for this stage.
        $existing = addApplicationRecord([
            // Saves the student email on the new row.
            'studentEmail' => $email,
            // Saves the display name, or the email when it is unknown.
            'student' => $student ? studentDisplayName($student) : $email,
            // Saves the program, defaulting to MSCS.
            'program' => $student['program'] ?? 'MSCS',
            // Saves the standard stage label.
            'stage' => $canonLabel,
            // Saves the stage key.
            'stageKey' => $stageKey,
            // Starts with an empty paper title.
            'title' => '',
            // Saves the adviser name when the profile has one.
            'adviser' => $student['adviser'] ?? '',
        // Closes the new application row values.
        ]);
    // Closes the create-row branch.
    }

    // Also mirrors the row into MySQL when the DB class exists.
    if (class_exists('DB')) {
        // Guards the MySQL mirroring against errors.
        try {
            // Finds the users.user_id for this email.
            $userId = databaseUserIdForEmail($email);
            // Checks whether the email maps to a user.
            if ($userId > 0) {
                // Opens the shared database connection.
                $pdo = DB::getConnection();
                // Prepares a query for the application of this stage.
                $stmt = $pdo->prepare(
                    // Selects the id, status, and title of the live application.
                    'SELECT application_id, status, paper_title FROM applications
                     WHERE user_id = :user_id AND archived_at IS NULL AND presentation_stage = :stage
                     ORDER BY submitted_at DESC LIMIT 1'
                // Closes the prepared statement.
                );
                // Runs the query with the user id and stage label.
                $stmt->execute(['user_id' => $userId, 'stage' => $canonLabel]);
                // Fetches the matching application row.
                $row = $stmt->fetch();
                // Checks whether MySQL already has this application.
                if ($row) {
                    // Checks whether the database row is still unsubmitted.
                    if (in_array($row['status'] ?? '', ['not_started', 'pending', 'draft', ''], true)) {
                        // Prepares an update that marks the row as submitted.
                        $upd = $pdo->prepare('UPDATE applications SET status = :status WHERE application_id = :id');
                        // Runs the status update for this application.
                        $upd->execute(['status' => 'submitted', 'id' => (int) $row['application_id']]);
                    // Closes the database status check.
                    }
                // Handles the case where MySQL has no application row.
                } else {
                    // Starts with an empty paper title.
                    $title = '';
                    // Reads the student's latest application for its title.
                    $latest = latestApplicationForEmail($email);
                    // Checks whether the latest application has a title.
                    if (!empty($latest['title'])) {
                        // Reuses the previous paper title.
                        $title = (string) $latest['title'];
                    // Closes the title check.
                    }
                    // Prepares an insert for a new application row.
                    $ins = $pdo->prepare(
                        // Inserts the user, stage, paper title, and status.
                        'INSERT INTO applications (user_id, presentation_stage, paper_title, status)
                         VALUES (:user_id, :presentation_stage, :paper_title, :status)'
                    // Closes the prepared insert.
                    );
                    // Runs the insert with the application values.
                    $ins->execute([
                        // Binds the user id.
                        'user_id' => $userId,
                        // Binds the standard stage label.
                        'presentation_stage' => $canonLabel,
                        // Binds the title, or Untitled paper when it is empty.
                        'paper_title' => $title !== '' ? $title : 'Untitled paper',
                        // Binds the submitted status.
                        'status' => 'submitted',
                    // Closes the insert values.
                    ]);
                // Closes the new database row branch.
                }
            // Closes the user id check.
            }
        // Catches database errors while mirroring the row.
        } catch (Throwable $e) {
        // Closes the empty catch block.
        }
    // Closes the DB availability block.
    }
    // Returns the stored row, or the in-memory row as a fallback.
    return findStageApplicationForEmail($email, $stageKey) ?? $existing;
// Closes the ensureStageApplication function.
}

/**
 * The single write point for a student document.
 *
 * Records the upload in BOTH stores: the session prototype (kept for the
 * legacy prototype screens) and MySQL `application_documents` (the shared
 * source of truth the coordinator panel reads). Writing only to the session
 * was the reason a coordinator could never see a student's upload.
 */
/**
 * The document a new upload replaces.
 *
 * A student who sends a document again replaces the record of the document
 * they sent before, so the coordinator always reviews the newest file and no
 * duplicate records pile up. A "Revision" replaces the document the
 * coordinator sent back to the student.
 */
// Finds the document record that a new upload should replace.
function findReplaceableDocument(int $applicationId, string $stageLabel, string $docType): ?array {
    // Checks whether the application id is valid.
    if ($applicationId <= 0) {
        // Returns null when there is no application to search.
        return null;
    // Closes the application id check.
    }

    // Starts the list of documents from the same stage.
    $sameStage = [];
    // Loops over the documents of the application.
    foreach (uploadsForApplication($applicationId) as $upload) {
        // Reads the stage label of the document.
        $stage = (string) ($upload['stage'] ?? '');
        // Compares the stage label while ignoring case.
        $same  = strcasecmp($stage, $stageLabel) === 0
            // Also compares the stage keys for a looser match.
            || stageKeyFromLabel($stage) === stageKeyFromLabel($stageLabel);
        // Checks whether the document is from the same stage.
        if ($same) {
            // Adds the document to the same-stage list.
            $sameStage[] = $upload;
        // Closes the same-stage check.
        }
    // Closes the loop over the documents.
    }
    // Checks whether the stage has no documents yet.
    if (!$sameStage) {
        // Returns null when there is nothing to replace.
        return null;
    // Closes the empty stage list check.
    }

    // Notes that a Revision replaces the document sent back by the coordinator.
    // A revision replaces the document that was sent back to the student.
    // Checks whether the new document is a revision.
    if (strcasecmp(trim($docType), 'Revision') === 0) {
        // Loops over the documents of the same stage.
        foreach ($sameStage as $upload) {
            // Looks for a document sent back for revision.
            if (in_array((string) ($upload['status'] ?? ''), ['incomplete', 'revision'], true)) {
                // Returns that document so the revision replaces it.
                return $upload;
            // Closes the returned document check.
            }
        // Closes the loop over same-stage documents.
        }
    // Closes the revision branch.
    }

    // Notes that other documents replace the document of the same kind.
    // Otherwise the document of the same kind is replaced.
    // Classifies the new document type.
    $kind = classifyUploadDocType($docType);
    // Loops over the same-stage documents again.
    foreach ($sameStage as $upload) {
        // Checks whether the document is of the same kind.
        if (classifyUploadDocType((string) ($upload['docType'] ?? '')) === $kind) {
            // Returns that document so it can be replaced.
            return $upload;
        // Closes the same-kind check.
        }
    // Closes the loop over same-stage documents.
    }

    // Returns null when no document should be replaced.
    return null;
// Closes the findReplaceableDocument function.
}

/**
 * Remove a stored file when no document uses it any more.
 */
// Deletes a stored file that no document uses any more.
function removeUnreferencedUploadFile(string $storedName): void {
    // Keeps only the safe file name of the stored file.
    $stored = basename(trim($storedName));
    // Checks for an empty name or a missing DB class.
    if ($stored === '' || !class_exists('DB')) {
        // Stops when the file name or the database is unusable.
        return;
    // Closes the input check.
    }
    // Guards the reference count query against errors.
    try {
        // Counts how many documents still point to this file.
        $used = (int) DB::query(
            // Counts rows in application_documents by stored name.
            'SELECT COUNT(*) c FROM application_documents WHERE stored_name = :name',
            // Passes the stored file name as the parameter.
            ['name' => $stored]
        // Reads the count value from the result row.
        )->fetch()['c'];
        // Checks whether any document still uses the file.
        if ($used > 0) {
            // Keeps the file when it is still referenced.
            return;
        // Closes the reference count check.
        }
    // Catches database errors during the count.
    } catch (Throwable $e) {
        // Does nothing when the count cannot be read.
        return;
    // Closes the error handler.
    }

    // Resolves the full path of the stored file.
    $path = uploadStoragePath(['storedFile' => $stored]);
    // Checks whether the file really exists on disk.
    if ($path && is_file($path)) {
        // Deletes the unused file from storage.
        @unlink($path);
    // Closes the file existence check.
    }
// Closes the removeUnreferencedUploadFile function.
}

// Saves a student document in both the session store and MySQL.
function recordStudentDocument(array $meta, ?string $email = null, ?int $userId = null): ?int {
    // Normalizes the email from the argument or the metadata.
    $email = strtolower((string) ($email ?? ($meta['studentEmail'] ?? '')));
    // Checks whether an email is available.
    if ($email === '') {
        // Returns null when the document has no owner.
        return null;
    // Closes the empty email check.
    }

    // Reads the stage label from the document metadata.
    $stageLabel = (string) ($meta['stage'] ?? '');
    // Converts the stage label into a stage key.
    $stageKey = stageKeyFromLabel($stageLabel);
    // Ensures an application row exists for this stage.
    $stageApp = ensureStageApplication($email, $stageKey, $stageLabel);
    // Checks whether an application row was found or created.
    if ($stageApp) {
        // Saves the application id in the metadata.
        $meta['applicationId'] = $stageApp['id'];
    // Closes the application row check.
    }

    // Starts with no MySQL document id.
    $documentId = null;
    // Writes to MySQL only when the DB class is available.
    if (class_exists('DB')) {
        // Guards the MySQL document write against errors.
        try {
            // Uses the given user id or looks it up from the email.
            $resolvedUserId = $userId !== null && $userId > 0 ? (int) $userId : databaseUserIdForEmail($email);
            // Checks whether a user id was resolved.
            if ($resolvedUserId > 0) {
                // Finds the application id for this user and stage.
                $applicationId = databaseApplicationIdForStudent($resolvedUserId, $stageLabel);
                // Falls back to the application id stored in the session row.
                if ($applicationId === null && !empty($meta['applicationId'])) {
                    // Reads the candidate application id.
                    $candidate = (int) $meta['applicationId'];
                    // Checks whether the candidate id is valid.
                    if ($candidate > 0) {
                        // Prepares a check that the application exists in MySQL.
                        $existsStmt = DB::getConnection()->prepare(
                            // Selects 1 when the application row exists.
                            'SELECT 1 FROM applications WHERE application_id = :id LIMIT 1'
                        // Closes the prepared statement.
                        );
                        // Runs the existence check with the candidate id.
                        $existsStmt->execute(['id' => $candidate]);
                        // Checks whether the application row was found.
                        if ($existsStmt->fetchColumn()) {
                            // Uses the confirmed application id.
                            $applicationId = $candidate;
                        // Closes the found application check.
                        }
                    // Closes the candidate id check.
                    }
                // Closes the fallback application id block.
                }
                // Reads the document status, defaulting to submitted.
                $status = (string) ($meta['status'] ?? 'submitted');
                // Checks whether the status is one of the allowed values.
                if (!in_array($status, ['submitted', 'verified', 'incomplete', 'revision'], true)) {
                    // Falls back to submitted for an unknown status.
                    $status = 'submitted';
                // Closes the status check.
                }

                // Explains why a resent document replaces the earlier record.
                /*
                 * A document that is sent again replaces the record of the
                 * document that was there before: the newest file is the one
                 * that is reviewed, and the student never ends up with two
                 * records of the same document.
                 */
                // Looks for a document record that can be replaced.
                $replaceable = findReplaceableDocument(
                    // Passes the application id.
                    (int) $applicationId,
                    // Passes the stage label.
                    $stageLabel,
                    // Passes the document type.
                    (string) ($meta['docType'] ?? '')
                // Closes the findReplaceableDocument call.
                );
                // Reads the id of the replaceable document, or 0.
                $replaceableId = (int) ($replaceable['id'] ?? 0);

                // Checks whether an earlier document was found.
                if ($replaceableId > 0) {
                    // Reads the stored name of the file being replaced.
                    $previousFile = (string) ($replaceable['storedFile'] ?? $replaceable['stored_name'] ?? '');
                    // Prepares the update that reuses the existing document row.
                    $stmt = DB::getConnection()->prepare(
                        // Updates the document row with the new file details.
                        'UPDATE application_documents
                            SET original_name = :original_name,
                                stored_name   = :stored_name,
                                mime_type     = :mime_type,
                                file_size     = :file_size,
                                status        = :status,
                                uploaded_at   = CURRENT_TIMESTAMP
                          WHERE document_id = :document_id'
                    // Closes the prepared update.
                    );
                    // Runs the update with the new file values.
                    $stmt->execute([
                        // Binds the new original file name.
                        'original_name' => (string) ($meta['fileName'] ?? ''),
                        // Binds the new stored file name.
                        'stored_name'   => (string) ($meta['storedFile'] ?? ''),
                        // Binds the new MIME type.
                        'mime_type'     => (string) ($meta['mimeType'] ?? ''),
                        // Binds the new file size.
                        'file_size'     => (int) ($meta['size'] ?? 0),
                        // Sets the status back to submitted.
                        'status'        => 'submitted',
                        // Binds the document row being replaced.
                        'document_id'   => $replaceableId,
                    // Closes the update values.
                    ]);
                    // Keeps the same document id for the new file.
                    $documentId = $replaceableId;
                    // Notes that the replaced file is no longer used.
                    // The file that was replaced is not used any more.
                    // Checks whether a different old file should be removed.
                    if ($previousFile !== '' && $previousFile !== (string) ($meta['storedFile'] ?? '')) {
                        // Deletes the old file when nothing else uses it.
                        removeUnreferencedUploadFile($previousFile);
                    // Closes the old file cleanup check.
                    }
                // Handles the case where a new document row is needed.
                } else {
                    // Prepares the insert for a new document row.
                    $stmt = DB::getConnection()->prepare(
                        // Inserts the new document into application_documents.
                        'INSERT INTO application_documents
                            (application_id, user_id, stage, document_type, original_name, stored_name, mime_type, file_size, status)
                          VALUES
                            (:application_id, :user_id, :stage, :document_type, :original_name, :stored_name, :mime_type, :file_size, :status)'
                    // Closes the prepared insert.
                    );
                    // Runs the insert with the document values.
                    $stmt->execute([
                        // Binds the application id.
                        'application_id' => $applicationId,
                        // Binds the user id.
                        'user_id' => $resolvedUserId,
                        // Binds the stage label.
                        'stage' => $stageLabel,
                        // Binds the document type, defaulting to Document.
                        'document_type' => (string) ($meta['docType'] ?? 'Document'),
                        // Binds the original file name.
                        'original_name' => (string) ($meta['fileName'] ?? ''),
                        // Binds the stored file name.
                        'stored_name' => (string) ($meta['storedFile'] ?? ''),
                        // Binds the MIME type.
                        'mime_type' => (string) ($meta['mimeType'] ?? ''),
                        // Binds the file size.
                        'file_size' => (int) ($meta['size'] ?? 0),
                        // Binds the document status.
                        'status' => $status,
                    // Closes the insert values.
                    ]);
                    // Saves the new document id from the insert.
                    $documentId = (int) DB::getConnection()->lastInsertId();
                // Closes the insert branch.
                }
            // Closes the resolved user id check.
            }
        // Catches database errors during the document write.
        } catch (Throwable $e) {
            // Logs the failure with the email and the error message.
            error_log('CSITE: failed to record application document for ' . $email . ' — ' . $e->getMessage());
            // Clears the document id because the write failed.
            $documentId = null;
        // Closes the error handler.
        }
    // Closes the DB availability block.
    }

    // Also saves the upload in the session store.
    addUploadRecord(array_merge($meta, [
        // Saves the student email with the upload.
        'studentEmail' => $email,
        // Saves the application id when one is known.
        'applicationId' => $meta['applicationId'] ?? null,
        // Uses the MySQL id, or null when the write failed.
        'id' => $documentId ? (string) $documentId : null,
        // Notes that a student upload always waits for review.
        // A document the student sends is always waiting for the review.
        // Marks the upload as submitted for review.
        'status' => 'submitted',
    // Closes the upload record values.
    ]));

    // Returns the new document id, or null on failure.
    return $documentId;
// Closes the recordStudentDocument function.
}

// Finds an upload by id in MySQL first, then the session store.
function findUpload(string $id): ?array {
    // Checks whether the id looks like a MySQL number.
    if (ctype_digit($id)) {
        // Looks the document up in MySQL.
        $databaseUpload = databaseDocumentById((int) $id);
        // Checks whether MySQL returned the document.
        if ($databaseUpload) {
            // Returns the database document when it was found.
            return $databaseUpload;
        // Closes the database result check.
        }
    // Closes the numeric id check.
    }

    // Loops over the uploads kept in the session store.
    foreach (storeGet('uploads') as $upload) {
        // Checks whether the upload id matches.
        if ((string) ($upload['id'] ?? '') === $id) {
            // Returns the matching session upload.
            return $upload;
        // Closes the id match check.
        }
    // Closes the loop over the session uploads.
    }

    // Returns null when no upload matches the id.
    return null;
// Closes the findUpload function.
}

// Finds the real file path of an uploaded document.
function uploadStoragePath(array $upload): ?string {
    // Reads the safe stored file name of the upload.
    $stored = basename((string) ($upload['storedFile'] ?? $upload['stored_name'] ?? ''));
    // Returns null when the stored name is empty.
    if ($stored === '') return null;

    // Builds the base storage folder path.
    $baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR;

    // Notes that files directly inside storage are checked first.
    // Check directly inside /storage/
    // Builds the path directly inside the storage folder.
    $path = $baseDir . $stored;
    // Returns the path when the file exists there.
    if (is_file($path)) return $path;

    // Notes that storage/uploads is checked when the first path fails.
    // Check inside /storage/uploads/
    // Builds the path inside the storage/uploads folder.
    $pathUploads = $baseDir . 'uploads' . DIRECTORY_SEPARATOR . $stored;
    // Returns the path when the file exists there.
    if (is_file($pathUploads)) return $pathUploads;

    // Returns null when the file is not found.
    return null;
// Closes the uploadStoragePath function.
}

/**
 * Locate a LibreOffice binary for print-faithful Word rendering, or null when
 * it is not installed. Result is memoized per request.
 */
// Finds the LibreOffice program, or null when it is not installed.
function libreOfficePath(): ?string {
    // Remembers whether the lookup already ran in this request.
    static $resolved = false;
    // Remembers the LibreOffice path found earlier.
    static $path = null;
    // Checks whether the result is already known.
    if ($resolved) {
        // Returns the cached LibreOffice path.
        return $path;
    // Closes the cached result check.
    }
    // Marks the lookup as done for this request.
    $resolved = true;
    // Lists the usual LibreOffice install locations.
    $candidates = [
        // Adds the 64-bit Program Files path.
        'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
        // Adds the 32-bit Program Files path.
        'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
    // Closes the candidate path list.
    ];
    // Loops over the possible LibreOffice paths.
    foreach ($candidates as $candidate) {
        // Checks whether LibreOffice exists at this path.
        if (is_file($candidate)) {
            // Saves the found executable path.
            $path = $candidate;
            // Returns the LibreOffice path.
            return $path;
        // Closes the file existence check.
        }
    // Closes the loop over the candidate paths.
    }
    // Guards the shell lookup against errors.
    try {
        // Asks Windows where the soffice.exe program is installed.
        $where = @shell_exec('where soffice.exe 2>nul');
        // Checks whether the command returned text.
        if (is_string($where)) {
            // Splits the command output into separate lines.
            foreach (preg_split('/\R/', $where) as $line) {
                // Trims spaces from this output line.
                $line = trim($line);
                // Checks whether the line names an existing file.
                if ($line !== '' && is_file($line)) {
                    // Saves the found executable path.
                    $path = $line;
                    // Returns the LibreOffice path.
                    return $path;
                // Closes the existing file check.
                }
            // Closes the loop over the output lines.
            }
        // Closes the text output check.
        }
    // Ignores any error raised by the shell lookup.
    } catch (Throwable $e) {}
    // Returns the found path, or null when LibreOffice is missing.
    return $path;
// Closes the libreOfficePath function.
}

/**
 * Render a .docx file to PDF via LibreOffice (cached in storage/converted).
 * Returns the converted PDF path, or null when unavailable (LibreOffice not
 * installed or conversion failed). Callers fall back to the mammoth.js preview.
 */
// Starts convertedPdfPath(), which returns a cached PDF path for a .docx file.
function convertedPdfPath(string $sourcePath): ?string {
    // Only continues for an existing .docx file; other file types are not converted.
    if (strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) !== 'docx' || !is_file($sourcePath)) {
        // Returns null so the caller keeps the normal preview instead of a PDF.
        return null;
    // Closes the file type and existence check.
    }
    // Builds the storage/converted folder path where generated PDFs are cached.
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'converted';
    // Creates the converted folder when missing and fails when it cannot be made.
    if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
        // Returns null because the PDF cannot be written without that folder.
        return null;
    // Closes the folder creation check.
    }
    // Sets the PDF path to the source file name with a .pdf extension.
    $pdf = $dir . DIRECTORY_SEPARATOR . pathinfo($sourcePath, PATHINFO_FILENAME) . '.pdf';
    // Reuses the cached PDF when it is newer than the source .docx file.
    if (is_file($pdf) && filemtime($pdf) >= filemtime($sourcePath)) {
        // Returns the cached PDF path without running LibreOffice again.
        return $pdf;
    // Closes the cache freshness check.
    }
    // Finds the LibreOffice executable used for the conversion.
    $soffice = libreOfficePath();
    // Skips conversion when LibreOffice or the exec() function is unavailable.
    if ($soffice === null || !function_exists('exec')) {
        // Returns null so callers fall back to the mammoth.js preview.
        return null;
    // Closes the LibreOffice availability check.
    }
    // Builds the headless LibreOffice command that converts the file to PDF.
    $cmd = '"' . $soffice . '" --headless --convert-to pdf --outdir '
        // Adds the escaped output folder and source path plus error redirection.
        . escapeshellarg($dir) . ' ' . escapeshellarg($sourcePath) . ' 2>&1';
    // Starts the guarded block so conversion errors do not break the page.
    try {
        // Allows up to 120 seconds for the conversion to finish.
        @set_time_limit(120);
        // Prepares an empty array that receives the command output lines.
        $out = [];
        // Defaults the exit code to 1, which means the conversion failed.
        $code = 1;
        // Runs the LibreOffice conversion and captures output and exit code.
        @exec($cmd, $out, $code);
    // Catches any error thrown while running the conversion.
    } catch (Throwable $e) {
        // Returns null when the conversion could not be started.
        return null;
    // Closes the try/catch around the conversion command.
    }
    // Returns the PDF only when conversion succeeded and the file exists.
    return ($code === 0 && is_file($pdf)) ? $pdf : null;
// Closes convertedPdfPath().
}

// Starts updateUploadStatus(), which changes the status of one upload.
function updateUploadStatus(string $id, string $status): void {
    // Runs the database update only for numeric ids when the DB layer exists.
    if (ctype_digit($id) && class_exists('DB')) {
        // Starts the guarded block around the status update query.
        try {
            // Prepares the UPDATE statement for the application_documents table.
            $stmt = DB::getConnection()->prepare(
                // Sets the new status for the document matching the given document_id.
                'UPDATE application_documents SET status = :status WHERE document_id = :document_id'
            // Closes the prepared SQL statement text.
            );
            // Executes the update with the status and the numeric document id.
            $stmt->execute(['status' => $status, 'document_id' => (int) $id]);
        // Catches database errors so the session copy is still updated.
        } catch (Throwable $e) {
        // Closes the empty catch block for the database update.
        }
    // Closes the database-only branch.
    }
    // Loads the session uploads list to update the matching record too.
    $list = storeGet('uploads');
    // Loops by reference so the matching upload can be changed in place.
    foreach ($list as &$u) {
        // Skips uploads whose id does not match the requested document.
        if ((string) $u['id'] !== $id) {
            // Continues to the next upload in the list.
            continue;
        // Closes the upload id comparison.
        }
        // Sets the new status on the matching session upload.
        $u['status'] = $status;
        // Stops looping once the matching upload is updated.
        break;
    // Closes the loop over the session uploads.
    }
    // Breaks the reference so the loop variable is safe to reuse.
    unset($u);
    // Saves the updated uploads list back to the session store.
    storeSet('uploads', $list);
// Closes updateUploadStatus().
}

// Starts syncUploadStatusesFromWorkflow(), copying workflow statuses to uploads.
function syncUploadStatusesFromWorkflow(int $appId, array $workflowState): void {
    // Loads the session uploads list for the given application.
    $list = storeGet('uploads');
    // Loops by reference over each upload record.
    foreach ($list as &$u) {
        // Skips uploads that belong to a different application.
        if ((int) ($u['applicationId'] ?? 0) !== $appId) {
            // Continues to the next upload record.
            continue;
        // Closes the application id check.
        }
        // Maps the upload document type to its workflow state key.
        $key = classifyUploadDocType((string) $u['docType']);
        // Copies the workflow status only when it is a known review status.
        if (!empty($workflowState[$key]) && in_array($workflowState[$key], ['verified', 'incomplete', 'revision', 'submitted'], true)) {
            // Stores the workflow review status on the upload record.
            $u['status'] = $workflowState[$key];
        // Closes the status copy condition.
        }
    // Closes the loop over the session uploads.
    }
    // Breaks the reference after the loop.
    unset($u);
    // Saves the updated uploads list back to the session store.
    storeSet('uploads', $list);
// Closes syncUploadStatusesFromWorkflow().
}

// Starts uploadsForEmail(), which merges session and database uploads.
function uploadsForEmail(string $email): array {
    // Keeps only session uploads whose student email matches, ignoring case.
    $sessionUploads = array_values(array_filter(storeGet('uploads'), static function ($u) use ($email) {
        // Compares the upload's student email with the requested email.
        return strcasecmp((string) $u['studentEmail'], $email) === 0;
    // Closes the filter callback for session uploads.
    }));
    // Loads the database documents for the user id that owns this email.
    $dbUploads = databaseDocuments(null, databaseUserIdForEmail($email));
    // Prepares the merged result list.
    $merged = [];
    // Prepares a set of keys already added so duplicates are skipped.
    $seen = [];
    // Adds every session upload to the merged list first.
    foreach ($sessionUploads as $u) {
        // Records the id and stored file pair so duplicates can be detected.
        $seen[(string) ($u['id'] ?? '') . '|' . (string) ($u['storedFile'] ?? '')] = true;
        // Appends the session upload to the merged list.
        $merged[] = $u;
    // Closes the loop over session uploads.
    }
    // Loops over the database uploads to add the missing ones.
    foreach ($dbUploads as $u) {
        // Builds the same id and stored file key for the database upload.
        $key = (string) ($u['id'] ?? '') . '|' . (string) ($u['storedFile'] ?? '');
        // Skips the database upload when the session already had it.
        if (isset($seen[$key])) {
            // Continues to the next database upload.
            continue;
        // Closes the duplicate check.
        }
        // Marks the key as seen so it is not added twice.
        $seen[$key] = true;
        // Appends the database upload to the merged list.
        $merged[] = $u;
    // Closes the loop over database uploads.
    }
    // Returns the combined list of session and database uploads.
    return $merged;
// Closes uploadsForEmail().
}

// Starts uploadsForApplication(), which merges uploads for one application.
function uploadsForApplication(int $id): array {
    // Sets the owner user id to zero until the database lookup runs.
    $userId = 0;
    // Looks up the owner only when DB exists and the application id is valid.
    if (class_exists('DB') && $id > 0) {
        // Starts the guarded block for the owner lookup.
        try {
            // Prepares a query for the user_id that owns this application.
            $stmt = DB::getConnection()->prepare('SELECT user_id FROM applications WHERE application_id = :id LIMIT 1');
            // Runs the query with the application id parameter.
            $stmt->execute(['id' => $id]);
            // Stores the owner user id, or 0 when no row was found.
            $userId = (int) ($stmt->fetchColumn() ?: 0);
        // Catches database errors so the page can still show uploads.
        } catch (Throwable $e) {
            // Falls back to user id 0 when the lookup fails.
            $userId = 0;
        // Closes the catch block for the owner lookup.
        }
    // Closes the owner lookup condition.
    }
    // Keeps only session uploads that belong to this application id.
    $sessionUploads = array_values(array_filter(storeGet('uploads'), static function ($u) use ($id) {
        // Compares the upload's application id with the requested one.
        return (int) ($u['applicationId'] ?? 0) === $id;
    // Closes the filter callback for session uploads.
    }));
    // Loads database documents for this application and its owner.
    $dbUploads = databaseDocuments($id, $userId, $userId > 0);
    // Prepares the merged result list.
    $merged = [];
    // Prepares the set of stored files already merged.
    $seen = [];
    // Adds each session upload to the merged list first.
    foreach ($sessionUploads as $u) {
        // Records its stored file name so duplicates are skipped later.
        $seen[(string) ($u['storedFile'] ?? '')] = true;
        // Appends the session upload to the merged list.
        $merged[] = $u;
    // Closes the loop over session uploads.
    }
    // Loops over the database uploads to add the missing ones.
    foreach ($dbUploads as $u) {
        // Reads the stored file name used as the duplicate key.
        $key = (string) ($u['storedFile'] ?? '');
        // Skips the database upload when its file is already merged.
        if ($key !== '' && isset($seen[$key])) {
            // Continues to the next database upload.
            continue;
        // Closes the duplicate file check.
        }
        // Appends the database upload to the merged list.
        $merged[] = $u;
    // Closes the loop over database uploads.
    }
    // Returns the combined upload list for the application.
    return $merged;
// Closes uploadsForApplication().
}

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

// Starts deleteScheduleRecord(), deleting a schedule from the DB and the store.
function deleteScheduleRecord(string $id): void {
    // Runs the database branch for numeric ids or db_ prefixed ids.
    if (class_exists('DB') && (ctype_digit($id) || preg_match('/^db_(\d+)$/', $id, $matches))) {
        // Gets the shared PDO connection for the delete transaction.
        $pdo = DB::getConnection();
        // Reads the application id from a db_ style id, or 0 for numeric ids.
        $applicationId = ctype_digit($id) ? 0 : (int) $matches[1];
        // Begins a transaction so the schedule and application change together.
        $pdo->beginTransaction();
        // Starts the guarded block for the delete transaction.
        try {
            // Handles real numeric schedule ids stored in the schedules table.
            if (ctype_digit($id)) {
                // Prepares a query for the application that owns this schedule.
                $scheduleStmt = $pdo->prepare('SELECT application_id FROM schedules WHERE schedule_id = :id LIMIT 1');
                // Runs the query with the schedule id.
                $scheduleStmt->execute(['id' => (int) $id]);
                // Stores the owning application id found by the query.
                $applicationId = (int) ($scheduleStmt->fetchColumn() ?: 0);
                // Prepares the delete statement for the schedule row.
                $deleteStmt = $pdo->prepare('DELETE FROM schedules WHERE schedule_id = :id');
                // Deletes the schedule with the given id.
                $deleteStmt->execute(['id' => (int) $id]);
            // Closes the numeric id branch.
            }

            // Only updates the application when an owning application was found.
            if ($applicationId > 0) {
                // Prepares a query for the application workflow state and status.
                $applicationStmt = $pdo->prepare('SELECT workflow_state, status FROM applications WHERE application_id = :id LIMIT 1');
                // Runs the query with the application id.
                $applicationStmt->execute(['id' => $applicationId]);
                // Fetches the application row.
                $application = $applicationStmt->fetch();
                // Only proceeds when the application row exists.
                if ($application) {
                    // Decodes the stored JSON workflow state into an array.
                    $workflow = json_decode((string) ($application['workflow_state'] ?? ''), true);
                    // Falls back to an empty array when the JSON is invalid.
                    $workflow = is_array($workflow) ? $workflow : [];
                    // Opens the unset call that clears the schedule details from workflow.
                    unset(
                        // Removes the saved presentation date.
                        $workflow['presentation_date'],
                        // Removes the saved presentation time.
                        $workflow['presentation_time'],
                        // Removes the saved presentation venue.
                        $workflow['presentation_venue'],
                        // Removes the saved presentation panel.
                        $workflow['presentation_panel'],
                        // Removes the saved presentation status.
                        $workflow['presentation_status']
                    // Closes the unset call.
                    );
                    // Prepares the update that saves the cleaned workflow state.
                    $applicationUpdate = $pdo->prepare(
                        // Opens the SQL that stores the workflow state and resets the scheduled status.
                        "UPDATE applications
                         SET workflow_state = :workflow_state,
                             status = CASE WHEN status = 'scheduled' THEN 'ready_for_presentation' ELSE status END
                         WHERE application_id = :id"
                    // Closes the prepared statement definition.
                    );
                    // Executes the update with the cleaned workflow state and the id.
                    $applicationUpdate->execute([
                        // Encodes the workflow state back to JSON for storage.
                        'workflow_state' => json_encode($workflow, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        // Passes the application id parameter.
                        'id' => $applicationId,
                    // Closes the execute parameter array.
                    ]);
                // Closes the application exists check.
                }
            // Closes the owning application check.
            }

            // Commits the transaction so both changes are saved.
            $pdo->commit();
        // Catches errors so the transaction can be rolled back first.
        } catch (Throwable $e) {
            // Checks whether the transaction is still open.
            if ($pdo->inTransaction()) {
                // Rolls back the unsaved changes.
                $pdo->rollBack();
            // Closes the transaction check.
            }
            // Rethrows the error so the caller sees it.
            throw $e;
        // Closes the try/catch for the transaction.
        }
    // Throws for invalid ids when the database is in use.
    } elseif (class_exists('DB')) {
        // Raises an error because the schedule id format is not valid.
        throw new InvalidArgumentException('The schedule identifier is invalid.');
    // Closes the invalid id branch.
    }

    // Loads the session schedules list for the store cleanup.
    $storedSchedules = storeGet('schedules');
    // Resets the tracked application id to zero.
    $applicationId = 0;
    // Loops over the stored schedules to find the deleted one.
    foreach ($storedSchedules as $schedule) {
        // Checks whether this stored schedule has the deleted id.
        if ((string) ($schedule['id'] ?? '') === $id) {
            // Remembers its application id before removing the schedule.
            $applicationId = (int) ($schedule['applicationId'] ?? 0);
            // Stops looping once the schedule is found.
            break;
        // Closes the schedule id check.
        }
    // Closes the stored schedule loop.
    }
    // Saves the schedule list with the deleted record filtered out.
    storeSet('schedules', array_values(array_filter($storedSchedules, static function ($s) use ($id) {
        // Keeps only schedules whose id differs from the deleted id.
        return (string) ($s['id'] ?? '') !== $id;
    // Closes the filter callback and the storeSet call.
    })));

    // Only cleans the application when the schedule had an application id.
    if ($applicationId > 0) {
        // Loads the session applications list.
        $applications = storeGet('applications');
        // Loops by reference over the applications to find the right one.
        foreach ($applications as &$application) {
            // Skips applications that are not the schedule's application.
            if ((int) ($application['id'] ?? 0) !== $applicationId) {
                // Continues to the next application record.
                continue;
            // Closes the application id check.
            }
            // Only clears workflow keys when the workflow state is an array.
            if (is_array($application['workflowState'] ?? null)) {
                // Loops over the presentation keys that must be removed.
                foreach (['presentation_date', 'presentation_time', 'presentation_venue', 'presentation_panel', 'presentation_status'] as $key) {
                    // Removes this presentation key from the workflow state.
                    unset($application['workflowState'][$key]);
                // Closes the presentation key loop.
                }
            // Closes the workflow state array check.
            }
            // Checks whether the application is still marked as scheduled.
            if (($application['status'] ?? '') === 'scheduled') {
                // Moves the status back to ready for presentation.
                $application['status'] = 'ready_for_presentation';
            // Closes the scheduled status check.
            }
        // Closes the application loop.
        }
        // Breaks the reference after the loop.
        unset($application);
        // Saves the updated applications list.
        storeSet('applications', $applications);
    // Closes the application cleanup block.
    }
// Closes deleteScheduleRecord().
}

// Starts deletePanelMemberRecord(), which deletes a panel member.
function deletePanelMemberRecord(string $id): void {
    // Uses the database branch for numeric panel member ids.
    if (class_exists('DB') && ctype_digit($id)) {
        // Starts the guarded block for the delete query.
        try {
            // Prepares the DELETE statement for the panel_members table.
            $stmt = DB::getConnection()->prepare('DELETE FROM panel_members WHERE panel_member_id = :id');
            // Deletes the panel member with the given id.
            $stmt->execute(['id' => (int) $id]);
            // Returns early because the database delete succeeded.
            return;
        // Catches database errors so the session store is cleaned instead.
        } catch (Throwable $e) {
        // Closes the empty catch block.
        }
    // Closes the database branch.
    }
    // Saves the panel list with the deleted member filtered out.
    storeSet('panels', array_values(array_filter(storeGet('panels'), static function ($p) use ($id) {
        // Keeps only panel members whose id differs from the deleted id.
        return (string) $p['id'] !== $id;
    // Closes the filter callback and the storeSet call.
    })));
// Closes deletePanelMemberRecord().
}

// Starts normalizePanelMemberRow(), which shapes a panel row for the views.
function normalizePanelMemberRow(array $row): array {
    // Trims the member's first name from the database row.
    $firstName = trim((string) ($row['first_name'] ?? ''));
    // Trims the middle name, empty when it was not set.
    $middleName = trim((string) ($row['middle_name'] ?? ''));
    // Trims the last name from the database row.
    $lastName = trim((string) ($row['last_name'] ?? ''));
    // Joins first and middle names into the given name text.
    $givenName = trim(implode(' ', array_filter([$firstName, $middleName])));
    // Returns the normalized panel member array.
    return [
        // Uses the panel_member_id, or id, as the member id.
        'id' => (string) ($row['panel_member_id'] ?? $row['id'] ?? ''),
        // Builds a last name first name label, or falls back to the name field.
        'name' => $lastName !== '' ? $lastName . ', ' . $givenName : (string) ($row['name'] ?? ''),
        // Keeps the clean first name.
        'first_name' => $firstName,
        // Keeps the clean middle name.
        'middle_name' => $middleName,
        // Keeps the clean last name.
        'last_name' => $lastName,
        // Keeps the qualification text, empty when not set.
        'qualification' => (string) ($row['qualification'] ?? ''),
        // Keeps the email address, empty when not set.
        'email' => (string) ($row['email'] ?? ''),
        // Keeps the notes, empty when not set.
        'notes' => (string) ($row['notes'] ?? ''),
        // Stores how many schedules the member is assigned to.
        'panelSessions' => (int) ($row['panel_sessions'] ?? $row['panelSessions'] ?? 0),
        // Keeps the availability, defaulting to available.
        'availability' => (string) ($row['availability'] ?? 'available'),
    // Closes the normalized member array.
    ];
// Closes normalizePanelMemberRow().
}

// Starts databasePanelMembers(), which lists panel members from the DB.
function databasePanelMembers(): array {
    // Runs the query only when the database layer exists.
    if (class_exists('DB')) {
        // Starts the guarded block for the panel member query.
        try {
            // Counts each member's schedule assignments and sorts by name.
            $rows = DB::query('SELECT p.*, COUNT(a.assignment_id) AS panel_sessions FROM panel_members p LEFT JOIN schedule_panel_assignments a ON a.panel_member_id = p.panel_member_id GROUP BY p.panel_member_id ORDER BY p.last_name, p.first_name, p.middle_name')->fetchAll();
            // Normalizes every database row into the shared member shape.
            return array_map('normalizePanelMemberRow', $rows);
        // Catches query errors so the session store can be used.
        } catch (Throwable $e) {
        // Closes the empty catch block.
        }
    // Closes the database condition.
    }
    // Returns the session store panel members in the same shape.
    return array_map('normalizePanelMemberRow', storeGet('panels'));
// Closes databasePanelMembers().
}

// Starts findPanelMember(), which finds one panel member by id.
function findPanelMember(string $id): ?array {
    // Searches the session store panel list first.
    foreach (storeGet('panels') as $p) {
        // Checks whether this stored member has the requested id.
        if ((string) $p['id'] === $id) {
            // Returns the stored member in normalized shape.
            return normalizePanelMemberRow($p);
        // Closes the stored member id check.
        }
    // Closes the store search loop.
    }
    // Falls back to the database for numeric panel member ids.
    if (class_exists('DB') && ctype_digit($id)) {
        // Starts the guarded block for the database lookup.
        try {
            // Prepares a query for the panel member row.
            $stmt = DB::getConnection()->prepare('SELECT * FROM panel_members WHERE panel_member_id = :id LIMIT 1');
            // Runs the query with the numeric member id.
            $stmt->execute(['id' => (int) $id]);
            // Fetches the member row.
            $row = $stmt->fetch();
            // Returns the normalized row, or null when it was not found.
            return $row ? normalizePanelMemberRow($row) : null;
        // Catches database errors and returns null.
        } catch (Throwable $e) {
            // Returns null because the lookup failed.
            return null;
        // Closes the catch block.
        }
    // Closes the database condition.
    }
    // Returns null when no panel member matched the id.
    return null;
// Closes findPanelMember().
}

// Starts addPanelMemberRecord(), which inserts a new panel member.
function addPanelMemberRecord(array $m): array {
    // Uses the database when it exists and names were provided.
    if (class_exists('DB') && isset($m['first_name'], $m['last_name'])) {
        // Inserts the member into the panel_members table.
        $row = DB::insert('panel_members', [
            // Formats the first name consistently before saving.
            'first_name' => formatPersonName($m['first_name']),
            // Formats the optional middle name, storing null when empty.
            'middle_name' => formatPersonName((string) ($m['middle_name'] ?? '')) ?: null,
            // Formats the last name consistently before saving.
            'last_name' => formatPersonName($m['last_name']),
            // Trims the qualification text.
            'qualification' => trim($m['qualification']),
            // Trims the email, storing null when empty.
            'email' => trim((string) ($m['email'] ?? '')) ?: null,
            // Trims the notes, storing null when empty.
            'notes' => trim((string) ($m['notes'] ?? '')) ?: null,
            // Saves the availability, defaulting to available.
            'availability' => $m['availability'] ?? 'available',
        // Closes the insert data array.
        ]);
        // Returns the inserted member in normalized shape.
        return normalizePanelMemberRow($row);
    // Closes the database branch.
    }
    // Loads the session store panel list.
    $list = storeGet('panels');
    // Starts the new record built for the session store.
    $rec = [
        // Generates a pm_ prefixed unique id for the session record.
        'id' => 'pm_' . uniqid(),
        // Stores the member's display name.
        'name' => $m['name'],
        // Stores the qualification text.
        'qualification' => $m['qualification'],
        // Stores the email, empty string when not provided.
        'email' => $m['email'] ?? '',
        // Stores the notes, empty string when not provided.
        'notes' => $m['notes'] ?? '',
        // Stores the session count, defaulting to zero.
        'panelSessions' => (int) ($m['panelSessions'] ?? 0),
        // Stores the availability, defaulting to available.
        'availability' => $m['availability'] ?? 'available',
    // Closes the new record array.
    ];
    // Appends the new member to the list.
    $list[] = $rec;
    // Saves the updated panel list.
    storeSet('panels', $list);
    // Returns the new session record.
    return $rec;
// Closes addPanelMemberRecord().
}

// Starts updatePanelMemberRecord(), which updates one panel member.
function updatePanelMemberRecord(string $id, array $patch): ?array {
    // Uses the database branch for numeric member ids.
    if (class_exists('DB') && ctype_digit($id)) {
        // Keeps only the fields the edit form is allowed to change.
        $data = array_intersect_key($patch, array_flip(['first_name', 'middle_name', 'last_name', 'qualification', 'email', 'notes', 'availability']));
        // Only updates when at least one allowed field was sent.
        if ($data) {
            // Loops over the name fields that need consistent formatting.
            foreach (['first_name', 'middle_name', 'last_name'] as $nameField) {
                // Checks whether this name field was actually submitted.
                if (array_key_exists($nameField, $data)) {
                    // Formats the submitted name field value.
                    $data[$nameField] = formatPersonName((string) $data[$nameField]);
                // Closes the name field presence check.
                }
            // Closes the name formatting loop.
            }
            // Loops over the fields that should be null when empty.
            foreach (['middle_name', 'email', 'notes'] as $nullable) {
                // Checks whether this optional field was submitted.
                if (array_key_exists($nullable, $data)) {
                    // Trims the value and stores null when it is empty.
                    $data[$nullable] = trim((string) $data[$nullable]) ?: null;
                // Closes the optional field presence check.
                }
            // Closes the optional field loop.
            }
            // Runs the update on panel_members for the given member id.
            DB::update('panel_members', $data, ['panel_member_id' => (int) $id]);
        // Closes the data check.
        }
        // Returns the fresh member record after the update.
        return findPanelMember($id);
    // Closes the database branch.
    }
    // Loads the session store panel list.
    $list = storeGet('panels');
    // Loops over the stored members to find the matching id.
    foreach ($list as $i => $p) {
        // Skips members whose id does not match.
        if ((string) $p['id'] !== $id) {
            // Continues to the next stored member.
            continue;
        // Closes the id check.
        }
        // Merges the submitted changes into the stored member.
        $list[$i] = array_merge($p, $patch);
        // Saves the updated panel list.
        storeSet('panels', $list);
        // Returns the updated member record.
        return $list[$i];
    // Closes the stored member loop.
    }
    // Returns null when no member matched the id.
    return null;
// Closes updatePanelMemberRecord().
}

/**
 * =========================================================================
 * Documenter Pool Functions
 * =========================================================================
 */

// Starts normalizeDocumenterRow(), which shapes a documenter row for views.
function normalizeDocumenterRow(array $row): array {
    // Trims the documenter's first name.
    $firstName = trim((string) ($row['first_name'] ?? ''));
    // Trims the middle name, empty when it was not set.
    $middleName = trim((string) ($row['middle_name'] ?? ''));
    // Trims the last name.
    $lastName = trim((string) ($row['last_name'] ?? ''));
    // Joins first and middle names into the given name text.
    $givenName = trim(implode(' ', array_filter([$firstName, $middleName])));
    // Returns the normalized documenter array.
    return [
        // Uses the documenter_id, or id, as the documenter id.
        'id' => (string) ($row['documenter_id'] ?? $row['id'] ?? ''),
        // Builds a last name first name label, or falls back to the name field.
        'name' => $lastName !== '' ? $lastName . ', ' . $givenName : (string) ($row['name'] ?? ''),
        // Keeps the clean first name.
        'first_name' => $firstName,
        // Keeps the clean middle name.
        'middle_name' => $middleName,
        // Keeps the clean last name.
        'last_name' => $lastName,
        // Keeps the qualification text, empty when not set.
        'qualification' => (string) ($row['qualification'] ?? ''),
        // Keeps the email address, empty when not set.
        'email' => (string) ($row['email'] ?? ''),
        // Keeps the notes, empty when not set.
        'notes' => (string) ($row['notes'] ?? ''),
        // Keeps the availability, defaulting to available.
        'availability' => (string) ($row['availability'] ?? 'available'),
    // Closes the normalized documenter array.
    ];
// Closes normalizeDocumenterRow().
}

// Starts databaseDocumenters(), which lists documenters from the database.
function databaseDocumenters(): array {
    // Runs the query only when the database layer exists.
    if (class_exists('DB')) {
        // Starts the guarded block for the documenter query.
        try {
            // Counts each documenter's schedules and sorts them by name.
            $rows = DB::query('SELECT d.*, COUNT(s.schedule_id) AS documenter_sessions FROM documenter_pool d LEFT JOIN schedules s ON s.documenter_id = d.documenter_id GROUP BY d.documenter_id ORDER BY d.last_name, d.first_name, d.middle_name')->fetchAll();
            // Normalizes every row into the shared documenter shape.
            return array_map('normalizeDocumenterRow', $rows);
        // Catches query errors so the session store can be used.
        } catch (Throwable $e) {
        // Closes the empty catch block.
        }
    // Closes the database condition.
    }
    // Returns the session store documenters in the same shape.
    return array_map('normalizeDocumenterRow', storeGet('documenters') ?: []);
// Closes databaseDocumenters().
}

// Starts findDocumenter(), which finds one documenter by id.
function findDocumenter(string $id): ?array {
    // Searches the session store documenter list first.
    foreach (storeGet('documenters') as $d) {
        // Checks whether this stored documenter has the requested id.
        if ((string) $d['id'] === $id) {
            // Returns the stored documenter in normalized shape.
            return normalizeDocumenterRow($d);
        // Closes the stored documenter id check.
        }
    // Closes the store search loop.
    }
    // Falls back to the database for numeric documenter ids.
    if (class_exists('DB') && ctype_digit($id)) {
        // Starts the guarded block for the database lookup.
        try {
            // Prepares a query for the documenter_pool row.
            $stmt = DB::getConnection()->prepare('SELECT * FROM documenter_pool WHERE documenter_id = :id LIMIT 1');
            // Runs the query with the numeric documenter id.
            $stmt->execute(['id' => (int) $id]);
            // Fetches the documenter row.
            $row = $stmt->fetch();
            // Returns the normalized row, or null when it was not found.
            return $row ? normalizeDocumenterRow($row) : null;
        // Catches database errors and returns null.
        } catch (Throwable $e) {
            // Returns null because the lookup failed.
            return null;
        // Closes the catch block.
        }
    // Closes the database condition.
    }
    // Returns null when no documenter matched the id.
    return null;
// Closes findDocumenter().
}

// Starts addDocumenterRecord(), which inserts a new documenter.
function addDocumenterRecord(array $m): array {
    // Uses the database when it exists and names were provided.
    if (class_exists('DB') && isset($m['first_name'], $m['last_name'])) {
        // Inserts the documenter into the documenter_pool table.
        $row = DB::insert('documenter_pool', [
            // Formats the first name consistently before saving.
            'first_name' => formatPersonName($m['first_name']),
            // Formats the optional middle name, storing null when empty.
            'middle_name' => formatPersonName((string) ($m['middle_name'] ?? '')) ?: null,
            // Formats the last name consistently before saving.
            'last_name' => formatPersonName($m['last_name']),
            // Trims the qualification text.
            'qualification' => trim($m['qualification']),
            // Trims the email, storing null when empty.
            'email' => trim((string) ($m['email'] ?? '')) ?: null,
            // Trims the notes, storing null when empty.
            'notes' => trim((string) ($m['notes'] ?? '')) ?: null,
            // Saves the availability, defaulting to available.
            'availability' => $m['availability'] ?? 'available',
        // Closes the insert data array.
        ]);
        // Returns the inserted documenter in normalized shape.
        return normalizeDocumenterRow($row);
    // Closes the database branch.
    }
    // Loads the session store documenter list.
    $list = storeGet('documenters') ?: [];
    // Starts the new record built for the session store.
    $rec = [
        // Generates a doc_ prefixed unique id for the session record.
        'id' => 'doc_' . uniqid(),
        // Stores the documenter's display name.
        'name' => $m['name'],
        // Stores the qualification text.
        'qualification' => $m['qualification'],
        // Stores the email, empty string when not provided.
        'email' => $m['email'] ?? '',
        // Stores the notes, empty string when not provided.
        'notes' => $m['notes'] ?? '',
        // Stores the availability, defaulting to available.
        'availability' => $m['availability'] ?? 'available',
    // Closes the new record array.
    ];
    // Appends the new documenter to the list.
    $list[] = $rec;
    // Saves the updated documenter list.
    storeSet('documenters', $list);
    // Returns the new session record.
    return $rec;
// Closes addDocumenterRecord().
}

// Starts updateDocumenterRecord(), which updates one documenter.
function updateDocumenterRecord(string $id, array $patch): ?array {
    // Uses the database branch for numeric documenter ids.
    if (class_exists('DB') && ctype_digit($id)) {
        // Keeps only the fields the edit form is allowed to change.
        $data = array_intersect_key($patch, array_flip(['first_name', 'middle_name', 'last_name', 'qualification', 'email', 'notes', 'availability']));
        // Only updates when at least one allowed field was sent.
        if ($data) {
            // Loops over the name fields that need consistent formatting.
            foreach (['first_name', 'middle_name', 'last_name'] as $nameField) {
                // Checks whether this name field was actually submitted.
                if (array_key_exists($nameField, $data)) {
                    // Formats the submitted name field value.
                    $data[$nameField] = formatPersonName((string) $data[$nameField]);
                // Closes the name field presence check.
                }
            // Closes the name formatting loop.
            }
            // Loops over the fields that should be null when empty.
            foreach (['middle_name', 'email', 'notes'] as $nullable) {
                // Checks whether this optional field was submitted.
                if (array_key_exists($nullable, $data)) {
                    // Trims the value and stores null when it is empty.
                    $data[$nullable] = trim((string) $data[$nullable]) ?: null;
                // Closes the optional field presence check.
                }
            // Closes the optional field loop.
            }
            // Runs the update on documenter_pool for the given documenter id.
            DB::update('documenter_pool', $data, ['documenter_id' => (int) $id]);
        // Closes the data check.
        }
        // Returns the fresh documenter record after the update.
        return findDocumenter($id);
    // Closes the database branch.
    }
    // Loads the session store documenter list.
    $list = storeGet('documenters') ?: [];
    // Loops over the stored documenters to find the matching id.
    foreach ($list as $i => $d) {
        // Skips documenters whose id does not match.
        if ((string) $d['id'] !== $id) {
            // Continues to the next stored documenter.
            continue;
        // Closes the id check.
        }
        // Merges the submitted changes into the stored documenter.
        $list[$i] = array_merge($d, $patch);
        // Saves the updated documenter list.
        storeSet('documenters', $list);
        // Returns the updated documenter record.
        return $list[$i];
    // Closes the stored documenter loop.
    }
    // Returns null when no documenter matched the id.
    return null;
// Closes updateDocumenterRecord().
}

// Starts deleteDocumenterRecord(), which deletes a documenter.
function deleteDocumenterRecord(string $id): void {
    // Uses the database branch for numeric documenter ids.
    if (class_exists('DB') && ctype_digit($id)) {
        // Starts the guarded block for the assignment check.
        try {
            // Prepares a count of schedules already using this documenter.
            $assigned = DB::getConnection()->prepare('SELECT COUNT(*) FROM schedules WHERE documenter_id = :id');
            // Runs the count query with the documenter id.
            $assigned->execute(['id' => (int) $id]);
            // Checks whether any schedule still uses this documenter.
            if ((int) $assigned->fetchColumn() > 0) {
                // Stops deletion and tells the coordinator to mark them unavailable instead.
                throw new LogicException('This documenter is assigned to existing schedules. Mark the documenter unavailable instead of removing them.');
            // Closes the assignment check.
            }
            // Deletes the documenter row now that nothing uses it.
            DB::query('DELETE FROM documenter_pool WHERE documenter_id = :id', ['id' => (int) $id]);
        // Catches the error so it can be rethrown to the caller.
        } catch (Throwable $e) {
            // Rethrows the error so the calling page can show it.
            throw $e;
        // Closes the try/catch.
        }
    // Closes the database branch.
    }
    // Loads the session store documenter list.
    $list = storeGet('documenters') ?: [];
    // Loops over the stored documenters to find the matching id.
    foreach ($list as $i => $d) {
        // Checks whether this stored documenter has the deleted id.
        if ((string) $d['id'] === $id) {
            // Removes the matching documenter from the session list.
            unset($list[$i]);
            // Saves the reindexed documenter list.
            storeSet('documenters', array_values($list));
            // Stops looping after the documenter is removed.
            break;
        // Closes the id check.
        }
    // Closes the stored documenter loop.
    }
// Closes deleteDocumenterRecord().
}

// Starts documenterSelectOptions(), which lists available documenters.
function documenterSelectOptions(): array {
    // Starts the guarded block for the documenter query.
    try {
        // Loads available documenters with names and qualifications, sorted by name.
        $rows = DB::query("SELECT first_name, middle_name, last_name, qualification FROM documenter_pool WHERE availability = 'available' ORDER BY last_name, first_name, middle_name")->fetchAll();
        // Prepares the list of option labels.
        $options = [];
        // Loops over each available documenter row.
        foreach ($rows as $row) {
            // Builds the last name first name label for the option.
            $name = trim($row['last_name'] . ', ' . $row['first_name'] . ' ' . ($row['middle_name'] ?? ''));
            // Adds the qualification in parentheses when it is not empty.
            $options[] = $name . ($row['qualification'] !== '' ? ' (' . $row['qualification'] . ')' : '');
        // Closes the documenter loop.
        }
        // Returns the list of documenter option labels.
        return $options;
    // Catches query errors and returns an empty list.
    } catch (Throwable $e) {
        // Returns an empty list when the lookup fails.
        return [];
    // Closes the catch block.
    }
// Closes documenterSelectOptions().
}

// Starts findSchedule(), which finds one schedule by id.
function findSchedule(string $id): ?array {
    // Searches the session store schedules first.
    foreach (storeGet('schedules') as $s) {
        // Checks whether this stored schedule has the requested id.
        if ((string) $s['id'] === $id) {
            // Returns the matching session schedule.
            return $s;
        // Closes the schedule id check.
        }
    // Closes the store search loop.
    }
    // Falls back to the database for numeric schedule ids.
    if (class_exists('DB') && ctype_digit($id)) {
        // Starts the guarded block for the schedule query.
        try {
            // Prepares the query that joins schedule, application, user and panel data.
            $stmt = DB::getConnection()->prepare(
                // Opens the SQL selecting schedule fields, student, adviser, documentor and panel.
                'SELECT sc.schedule_id AS id, sc.application_id AS applicationId,
                        u.email AS studentEmail,
                        TRIM(CONCAT(s.last_name, ", ", s.first_name, IF(s.middle_initial IS NULL OR s.middle_initial = "", "", CONCAT(" ", s.middle_initial)))) AS studentName,
                        a.presentation_stage AS stage, sc.presentation_date AS date,
                        sc.start_time AS time, sc.venue, sc.status, ap.name AS adviser,
                        TRIM(CONCAT_WS(" ", sc.documentor_first_name, sc.documentor_middle_name, sc.documentor_last_name)) AS documentor,
                        GROUP_CONCAT(CONCAT(pm.last_name, ", ", pm.first_name, IF(pm.middle_name IS NULL OR pm.middle_name = "", "", CONCAT(" ", pm.middle_name)) ) ORDER BY spa.assignment_id SEPARATOR ", ") AS panel
                 FROM schedules sc
                 INNER JOIN applications a ON a.application_id = sc.application_id
                 INNER JOIN users u ON u.user_id = a.user_id
                 LEFT JOIN students s ON s.user_id = u.user_id
                 LEFT JOIN advisor_pool ap ON ap.adviser_id = sc.adviser_id
                 LEFT JOIN schedule_panel_assignments spa ON spa.schedule_id = sc.schedule_id
                 LEFT JOIN panel_members pm ON pm.panel_member_id = spa.panel_member_id
                 WHERE sc.schedule_id = :id
                 GROUP BY sc.schedule_id
                 LIMIT 1'
            // Closes the prepared statement definition.
            );
            // Runs the query with the numeric schedule id.
            $stmt->execute(['id' => (int) $id]);
            // Returns the schedule row, or null when it was not found.
            return $stmt->fetch() ?: null;
        // Catches query errors and returns null.
        } catch (Throwable $e) {
            // Returns null because the lookup failed.
            return null;
        // Closes the catch block.
        }
    // Closes the database condition.
    }
    // Returns null when no schedule matched the id.
    return null;
// Closes findSchedule().
}

// Starts databaseSchedules(), which lists all schedules from the database.
function databaseSchedules(): array {
    // Uses the session store when the database layer is missing.
    if (!class_exists('DB')) {
        // Returns the stored schedules as a fallback.
        return storeGet('schedules');
    // Closes the missing database check.
    }
    // Starts the guarded block for the schedule list query.
    try {
        // Runs the query that joins schedules with students, advisers and panels.
        $rows = DB::query(
            // Opens the SQL selecting schedule fields, student, adviser, documentor and panel.
            'SELECT sc.schedule_id AS id, sc.application_id AS applicationId,
                    u.email AS studentEmail,
                    TRIM(CONCAT(s.last_name, ", ", s.first_name, IF(s.middle_initial IS NULL OR s.middle_initial = "", "", CONCAT(" ", s.middle_initial)))) AS studentName,
                    a.presentation_stage AS stage, sc.presentation_date AS date,
                    sc.start_time AS time, sc.venue, sc.status, ap.name AS adviser,
                    TRIM(CONCAT_WS(" ", sc.documentor_first_name, sc.documentor_middle_name, sc.documentor_last_name)) AS documentor,
                    GROUP_CONCAT(CONCAT(pm.last_name, ", ", pm.first_name, IF(pm.middle_name IS NULL OR pm.middle_name = "", "", CONCAT(" ", pm.middle_name)) ) ORDER BY spa.assignment_id SEPARATOR ", ") AS panel
             FROM schedules sc
             INNER JOIN applications a ON a.application_id = sc.application_id
             INNER JOIN users u ON u.user_id = a.user_id
             LEFT JOIN students s ON s.user_id = u.user_id
             LEFT JOIN advisor_pool ap ON ap.adviser_id = sc.adviser_id
             LEFT JOIN schedule_panel_assignments spa ON spa.schedule_id = sc.schedule_id
             LEFT JOIN panel_members pm ON pm.panel_member_id = spa.panel_member_id
             GROUP BY sc.schedule_id
             ORDER BY sc.presentation_date, sc.start_time'
        // Fetches all schedule rows from the query.
        )->fetchAll();
        // Returns the schedule rows.
        return $rows;
    // Catches query errors so the session store can be used.
    } catch (Throwable $e) {
        // Returns the stored schedules when the query fails.
        return storeGet('schedules');
    // Closes the catch block.
    }
// Closes databaseSchedules().
}

// Starts databaseSchedulePanelMembers(), listing panel members by application.
function databaseSchedulePanelMembers(int $applicationId): array {
    // Returns early for missing DB or invalid id, then opens the try block.
    if (!class_exists('DB') || $applicationId <= 0) return [];    try {
        // Runs the query that joins assignments with panel member details.
        $rows = DB::query(
                // Opens the SQL selecting member fields plus the assigned panel role.
                'SELECT pm.*, spa.panel_role
             FROM schedules sc
             INNER JOIN schedule_panel_assignments spa ON spa.schedule_id = sc.schedule_id
             INNER JOIN panel_members pm ON pm.panel_member_id = spa.panel_member_id
             WHERE sc.application_id = :application_id
             ORDER BY spa.assignment_id'
        // Runs the query with the application id and fetches all rows.
        , ['application_id' => $applicationId])->fetchAll();
        // Notes that the rows use the same shape as the panel pool.
        // The same shape as the panel pool, so every consumer finds the name
        // Notes that consumers find the member name under the same key.
        // of a member under the same key.
        // Maps each database row into the shared panel member shape.
        return array_map(static function (array $row): array {
            // Normalizes the row into the common member array.
            $member = normalizePanelMemberRow($row);
            // Adds the panel role only when the query returned one.
            if (isset($row['panel_role'])) {
                // Stores the panel role as text on the member.
                $member['panel_role'] = (string) $row['panel_role'];
            // Closes the panel role check.
            }
            // Returns the normalized member for this row.
            return $member;
        // Closes the mapping callback and the array_map call.
        }, $rows);
    // Catches query errors and returns an empty list.
    } catch (Throwable $e) {
        // Returns an empty list when the lookup fails.
        return [];
    // Closes the catch block.
    }
// Closes databaseSchedulePanelMembers().
}

/**
 * Panel members assigned to exactly ONE schedule - the edit form preselects
 * these, so it must not mix in members of any other schedule of the same
 * application the way databaseSchedulePanelMembers() does.
 */
// Starts databaseSchedulePanelMembersForSchedule(), the per-schedule variant.
function databaseSchedulePanelMembersForSchedule(int $scheduleId): array {
    // Returns early when the database is missing or the schedule id is invalid.
    if (!class_exists('DB') || $scheduleId <= 0) return [];
    // Starts the guarded block for the per-schedule panel query.
    try {
        // Runs the query for the panel members of one schedule.
        $rows = DB::query(
                // Opens the SQL selecting member fields plus the assigned panel role.
                'SELECT pm.*, spa.panel_role
             FROM schedule_panel_assignments spa
             INNER JOIN panel_members pm ON pm.panel_member_id = spa.panel_member_id
             WHERE spa.schedule_id = :schedule_id
             ORDER BY spa.assignment_id',
            // Runs the query with the schedule id and fetches all rows.
            ['schedule_id' => $scheduleId])->fetchAll();
        // Maps each row into the shared panel member shape.
        return array_map(static function (array $row): array {
            // Normalizes the row into the common member array.
            $member = normalizePanelMemberRow($row);
            // Adds the panel role only when the query returned one.
            if (isset($row['panel_role'])) {
                // Stores the panel role as text on the member.
                $member['panel_role'] = (string) $row['panel_role'];
            // Closes the panel role check.
            }
            // Returns the normalized member for this row.
            return $member;
        // Closes the mapping callback and the array_map call.
        }, $rows);
    // Catches query errors and returns an empty list.
    } catch (Throwable $e) {
        // Returns an empty list when the lookup fails.
        return [];
    // Closes the catch block.
    }
// Closes databaseSchedulePanelMembersForSchedule().
}

// Starts schedulesForEmail(), which lists a student's schedules by email.
function schedulesForEmail(string $email): array {
    // Uses the database only when it exists and an email was given.
    if (class_exists('DB') && $email !== '') {
        // Starts the guarded block for the schedule query.
        try {
            // Prepares the query for this student's schedules, ordered by date and time.
            $stmt = DB::getConnection()->prepare('SELECT sc.schedule_id AS id, sc.application_id AS applicationId, u.email AS studentEmail, a.presentation_stage AS stage, sc.presentation_date AS date, sc.start_time AS time, sc.venue, sc.status FROM schedules sc INNER JOIN applications a ON a.application_id = sc.application_id INNER JOIN users u ON u.user_id = a.user_id WHERE u.email = :email ORDER BY sc.presentation_date, sc.start_time');
            // Runs the query with the student's email.
            $stmt->execute(['email' => $email]);
            // Returns all schedule rows for the student.
            return $stmt->fetchAll();
        // Catches query errors so the session store can be used.
        } catch (Throwable $e) {
        // Closes the empty catch block.
        }
    // Closes the database condition.
    }
    // Filters the stored schedules down to this student email.
    return array_values(array_filter(storeGet('schedules'), static function ($s) use ($email) {
        // Compares emails without caring about letter case.
        return strcasecmp((string) ($s['studentEmail'] ?? ''), $email) === 0;
    // Closes the filter callback and returns the matching list.
    }));
// Closes schedulesForEmail().
}

/**
 * Who is on the panel of each scheduled presentation of a student, together
 * with the adviser and the documenter of that schedule.
 *
 * The keys are the workflow stage keys ('concept', 'proposal', 'final'), so the
 * Student Dashboard can show the team next to the date, time and venue of the
 * stage. The coordinator pages stay the single source of truth: this only
 * reads schedules, schedule_panel_assignments, panel_members and advisor_pool.
 *
 * @return array<string, array{scheduleId: string, panel: array<int, array{name: string, role: string}>, adviser: string, documentor: string}>
 */
// Starts studentPresentationDetails(), which collects team details per stage.
function studentPresentationDetails(string $email): array {
    // Trims the student email before using it in queries.
    $email = trim($email);
    // Stops when no email was given.
    if ($email === '') {
        // Returns an empty list because no student can be matched.
        return [];
    // Closes the empty email check.
    }

    // Prepares the map of stage key to presentation details.
    $details = [];
    // Prepares the map from schedule id to stage key.
    $stageBySchedule = [];

    // Runs the queries only when the database layer exists.
    if (class_exists('DB')) {
        // Starts the guarded block for the presentation queries.
        try {
            // Prepares the query for schedule, adviser and documentor details.
            $stmt = DB::getConnection()->prepare(
                // Opens the SQL selecting schedule id, stage, adviser and documentor name.
                'SELECT sc.schedule_id AS id, a.presentation_stage AS stage,
                        ap.name AS adviser,
                        TRIM(CONCAT_WS(" ", sc.documentor_first_name, sc.documentor_middle_name, sc.documentor_last_name)) AS documentor
                 FROM schedules sc
                 INNER JOIN applications a ON a.application_id = sc.application_id
                 INNER JOIN users u ON u.user_id = a.user_id
                 LEFT JOIN advisor_pool ap ON ap.adviser_id = sc.adviser_id
                 WHERE u.email = :email AND a.archived_at IS NULL
                 ORDER BY sc.presentation_date ASC, sc.start_time ASC'
            // Closes the prepared statement definition.
            );
            // Runs the query with the student email.
            $stmt->execute(['email' => $email]);
            // Loops over each schedule row of the student.
            foreach ($stmt->fetchAll() as $row) {
                // Converts the schedule stage label into a stage key.
                $key = stageKeyFromLabel((string) ($row['stage'] ?? ''));
                // Notes that later schedules replace older ones for the same stage.
                // Later schedules win, so a rescheduled stage shows the newest.
                // Starts the details entry for this stage.
                $details[$key] = [
                    // Stores the schedule id for the stage.
                    'scheduleId' => (string) ($row['id'] ?? ''),
                    // Starts with an empty panel list to be filled later.
                    'panel' => [],
                    // Stores the trimmed adviser name.
                    'adviser' => trim((string) ($row['adviser'] ?? '')),
                    // Notes that some documenter entries include the qualification.
                    // Some schedules carry the qualification next to the name;
                    // Notes that the student only needs the documenter name.
                    // the student only needs the name itself.
                    // Stores the documenter name with parentheses and extra spaces removed.
                    'documentor' => trim(preg_replace('/\s+/', ' ', preg_replace('/\([^)]*\)/', ' ', (string) ($row['documentor'] ?? ''))) ?? ''),
                // Closes the stage details array.
                ];
                // Remembers which stage this schedule id belongs to.
                $stageBySchedule[(string) ($row['id'] ?? '')] = $key;
            // Closes the schedule row loop.
            }

            // Loads panel members only when schedules were found.
            if ($stageBySchedule) {
                // Prepares the query for panel members of the student's schedules.
                $panelStmt = DB::getConnection()->prepare(
                    // Opens the SQL selecting schedule id, role and member names.
                    'SELECT spa.schedule_id, spa.panel_role, pm.first_name, pm.middle_name, pm.last_name
                     FROM schedule_panel_assignments spa
                     INNER JOIN panel_members pm ON pm.panel_member_id = spa.panel_member_id
                     INNER JOIN schedules sc ON sc.schedule_id = spa.schedule_id
                     INNER JOIN applications a ON a.application_id = sc.application_id
                     INNER JOIN users u ON u.user_id = a.user_id
                     WHERE u.email = :email
                     ORDER BY spa.assignment_id ASC'
                // Closes the prepared statement definition.
                );
                // Runs the query with the student email.
                $panelStmt->execute(['email' => $email]);
                // Loops over each panel assignment row.
                foreach ($panelStmt->fetchAll() as $row) {
                    // Reads the schedule id of this panel assignment.
                    $scheduleId = (string) ($row['schedule_id'] ?? '');
                    // Finds the stage key for that schedule, blank when unknown.
                    $key = $stageBySchedule[$scheduleId] ?? '';
                    // Skips assignments with no stage or no details entry.
                    if ($key === '' || !isset($details[$key])) {
                        // Continues to the next panel assignment.
                        continue;
                    // Closes the stage check.
                    }
                    // Notes that panels of older schedules are ignored.
                    // Ignore the panel of an older schedule of the same stage.
                    // Skips panels that belong to an older schedule of the same stage.
                    if ((string) ($details[$key]['scheduleId'] ?? '') !== $scheduleId) {
                        // Continues to the next panel assignment.
                        continue;
                    // Closes the older schedule check.
                    }
                    // Collects the member name parts that are not empty.
                    $parts = array_values(array_filter([
                        // Adds the trimmed first name when present.
                        trim((string) ($row['first_name'] ?? '')),
                        // Adds the trimmed middle name when present.
                        trim((string) ($row['middle_name'] ?? '')),
                        // Adds the trimmed last name when present.
                        trim((string) ($row['last_name'] ?? '')),
                    // Closes the name parts array and its filter callback.
                    ], static fn($part) => $part !== ''));
                    // Skips members with no usable name parts.
                    if (!$parts) {
                        // Continues to the next panel assignment.
                        continue;
                    // Closes the empty name check.
                    }
                    // Starts one panel member entry for the stage.
                    $details[$key]['panel'][] = [
                        // Formats the joined name with single spaces.
                        'name' => formatPersonName(preg_replace('/\s+/', ' ', implode(' ', $parts)) ?: ''),
                        // Stores the panel role, defaulting to member.
                        'role' => (string) ($row['panel_role'] ?? 'member'),
                    // Closes the panel member entry.
                    ];
                // Closes the panel assignment loop.
                }
            // Closes the panel query block.
            }
        // Catches query errors so the session fallback can run.
        } catch (Throwable $e) {
            // Clears the collected details because the query data failed.
            $details = [];
            // Clears the schedule to stage map too.
            $stageBySchedule = [];
        // Closes the catch block.
        }
    // Closes the database block.
    }

    // Starts a comment explaining the session-only fallback below.
    /*
     * Session-only prototype data has no schedule ids or panel assignments, so
     * keep the comma separated panel string the coordinator typed.
     */
    // Uses the session schedules only when no database details were found.
    if (!$details) {
        // Loops over the stored schedules.
        foreach (storeGet('schedules') as $schedule) {
            // Skips schedules whose student email is different.
            if (strcasecmp((string) ($schedule['studentEmail'] ?? ''), $email) !== 0) {
                // Continues to the next stored schedule.
                continue;
            // Closes the email check.
            }
            // Prepares an empty panel list for this schedule.
            $panel = [];
            // Splits the stored comma separated panel names.
            foreach (explode(',', (string) ($schedule['panel'] ?? '')) as $name) {
                // Trims spaces around the typed member name.
                $name = trim($name);
                // Skips empty names and the TBD placeholder.
                if ($name === '' || strcasecmp($name, 'TBD') === 0) {
                    // Continues to the next typed name.
                    continue;
                // Closes the placeholder check.
                }
                // Adds the typed member name with the member role.
                $panel[] = ['name' => $name, 'role' => 'member'];
            // Closes the typed name loop.
            }
            // Stores the details for the stage of this stored schedule.
            $details[stageKeyFromLabel((string) ($schedule['stage'] ?? ''))] = [
                // Stores the session schedule id.
                'scheduleId' => (string) ($schedule['id'] ?? ''),
                // Stores the typed panel list.
                'panel' => $panel,
                // Stores the trimmed adviser name.
                'adviser' => trim((string) ($schedule['adviser'] ?? '')),
                // Stores the trimmed documenter name.
                'documentor' => trim((string) ($schedule['documentor'] ?? '')),
            // Closes the stage details array.
            ];
        // Closes the stored schedule loop.
        }
    // Closes the session fallback block.
    }

    // Returns the collected presentation details by stage.
    return $details;
// Closes studentPresentationDetails().
}

/**
 * Normalise any schedule date a form can submit to 'Y-m-d'.
 *
 * The picker-backed schedule forms post machine dates ('2026-10-10'), while the
 * display side of the app writes milestone dates like 'Oct 10, 2026'. Accept both
 * (plus the other spellings strtotime understands) so valid dates never hit the
 * "invalid date or time" guard just because of the format they arrive in.
 */
// Starts scheduleDateYMD(), which converts any submitted date to Y-m-d.
function scheduleDateYMD(string $raw): ?string {
    // Trims spaces around the submitted date text.
    $raw = trim($raw);
    // Loops over the accepted date formats, most exact first.
    foreach (['!Y-m-d', '!Y-n-j', '!m/d/Y', '!n/j/Y', '!n/j/y', 'M j, Y', 'F j, Y', 'l, F j, Y'] as $format) {
        // Tries to read the date text using one of the accepted date formats.
        $d = DateTimeImmutable::createFromFormat($format, $raw);
        // Checks the date parsed and formats back to exactly the same text.
        if ($d instanceof DateTimeImmutable && $d->format(str_replace('!', '', $format)) === $raw) {
            // Returns the date in MySQL's Y-m-d form when a format matched.
            return $d->format('Y-m-d');
        // Closes the parsed-date check.
        }
    // Closes the date format loop.
    }
    // Starts the note about the fallback date parser below.
    // Fallback for the remaining human spellings - but never let a bare time
    // Explains that a time alone must not be treated as a date.
    // ("9:30", "9:30 AM") masquerade as a date, so a year has to be present.
    // Rejects text without a four-digit year so a time alone is not a date.
    if (!preg_match('/\b\d{4}\b/', $raw)) {
        // Returns null because the date is not usable.
        return null;
    // Closes the year check.
    }
    // Lets PHP's strtotime guess the date from other human spellings.
    $ts = strtotime($raw);
    // Returns the parsed date, or null when strtotime fails.
    return $ts === false ? null : date('Y-m-d', $ts);
// Closes scheduleDateYMD().
}

/**
 * Normalise any schedule time a form can submit to 'H:i:s'.
 * Accepts '14:55', '04:55:00', '4:55 PM', '4:55 pm', '9:05 am', ...
 */
// Normalises any submitted schedule time into the H:i:s database format.
function scheduleTimeHMS(string $raw): ?string {
    // Trims spaces around the submitted time text.
    $raw = trim($raw);
    // Checks whether the submitted time is empty.
    if ($raw === '') {
        // Returns null because there is no time to normalise.
        return null;
    // Closes the empty-time check.
    }
    // Tries each accepted clock format on the submitted time.
    foreach (['!H:i', '!H:i:s', '!g:i A', '!g:i:s A', '!G:i', '!G:i:s'] as $format) {
        // Parses the time text with the current format.
        $d = DateTimeImmutable::createFromFormat($format, $raw);
        // Checks the time parsed and matched the text ignoring letter case.
        if ($d instanceof DateTimeImmutable && strcasecmp($d->format(str_replace('!', '', $format)), $raw) === 0) {
            // Returns the time in 24-hour H:i:s database form.
            return $d->format('H:i:s');
        // Closes the parsed-time check.
        }
    // Closes the time format loop.
    }
    // Starts the note about the fallback time parser.
    // Fallback for spellings createFromFormat chokes on ('9:5 am'). A time
    // Explains that a time must still look like a clock value.
    // must still look like a clock value, anchored to a date for strtotime.
    // Requires a clock-like value before using the fallback parser.
    if (!preg_match('/^\d{1,2}:\d{1,2}(:\d{1,2})?\s*(?:[AaPp][Mm])?$/', $raw)) {
        // Returns null when the text does not look like a time.
        return null;
    // Closes the clock pattern check.
    }
    // Parses the time with a fixed dummy date so strtotime accepts it.
    $ts = strtotime('2000-01-01 ' . $raw);
    // Returns the time in H:i:s, or null when parsing fails.
    return $ts === false ? null : date('H:i:s', $ts);
// Closes scheduleTimeHMS().
}

/**
 * Split a documentor name written 'Last, First Middle' (the format the
 * Documenter Pool selects submit) into the schedules table's three name
 * columns, the same way addScheduleRecord() and updateScheduleRecord() store it.
 */
// Splits a 'Last, First Middle' documentor name into its three parts.
function documentorNameParts(string $name): array {
    // Removes extra spaces from the whole name.
    $name = trim($name);
    // Starts the surname as an empty string.
    $last = '';
    // Starts the list of given names as an empty array.
    $given = [];
    // Only splits the name when something was typed.
    if ($name !== '') {
        // Splits the name at the first comma and trims both halves.
        $halves = array_map('trim', explode(',', $name, 2));
        // Takes the text before the comma as the surname.
        $last = $halves[0];
        // Splits the given names on spaces and drops empty pieces.
        $given = array_values(array_filter(preg_split('/\s+/', $halves[1] ?? '') ?: [], 'strlen'));
    // Closes the non-empty name check.
    }
    // Returns the three name parts as an array.
    return [
        // Puts the surname in the last-name slot.
        'last' => $last,
        // Takes the first given name, or empty when none was typed.
        'first' => $given ? (string) array_shift($given) : '',
        // Joins the remaining given names into the middle name.
        'middle' => $given ? implode(' ', $given) : '',
    // Closes the returned name array.
    ];
// Closes documentorNameParts().
}

/**
 * Write a saved schedule into the workflow state of its application.
 *
 * The Milestone Tracker reads the presentation milestone from the application's
 * workflow_state. Writing the schedule only to the schedules table left
 * "Presentation Scheduled" unverified even though the schedule said Confirmed,
 * so the two pages could disagree. Every schedule save now goes through here.
 *
 * Only the keys that are present are written, so a later edit that changes the
 * date does not wipe the panel (and vice versa).
 *
 * @param array{date?: string, time?: string, venue?: string, panel?: string, status?: string} $schedule
 */
// Copies a saved schedule into the application's workflow state.
function syncScheduleToApplication(int $applicationId, array $schedule): void {
    // Skips the sync when the application id is missing or the DB is unavailable.
    if ($applicationId <= 0 || !class_exists('DB')) {
        // Returns early because there is nothing safe to update.
        return;
    // Closes the guard check.
    }
    // Starts the block that protects the database work.
    try {
        // Gets the shared PDO database connection.
        $pdo = DB::getConnection();
        // Reads the workflow_state JSON for this application.
        $stmt = $pdo->prepare('SELECT workflow_state FROM applications WHERE application_id = :id LIMIT 1');
        // Runs the query with the application id bound.
        $stmt->execute(['id' => $applicationId]);
        // Fetches the application row.
        $row = $stmt->fetch();
        // Stops when no application row was found.
        if (!$row) {
            // Returns because there is no row to update.
            return;
        // Closes the missing-row check.
        }

        // Decodes the stored workflow state JSON into an array.
        $wf = json_decode((string) ($row['workflow_state'] ?? '[]'), true);
        // Falls back to an empty array when the JSON is not usable.
        $wf = is_array($wf) ? $wf : [];

        // Only writes the presentation status when the schedule sends one.
        if (array_key_exists('status', $schedule)) {
            // Stores 'confirmed' for confirmed or completed, otherwise 'pending'.
            $wf['presentation_status'] = in_array((string) $schedule['status'], ['confirmed', 'completed'], true) ? 'confirmed' : 'pending';
        // Closes the status check.
        }
        // Only writes the date when the schedule has a non-empty date.
        if (!empty($schedule['date'])) {
            // Normalises the submitted date to Y-m-d.
            $ymd = scheduleDateYMD((string) $schedule['date']);
            // Checks that the date could be understood.
            if ($ymd !== null) {
                // Saves the date in the display format 'M j, Y'.
                $wf['presentation_date'] = date('M j, Y', strtotime($ymd));
            // Closes the parsed-date check.
            }
        // Closes the date check.
        }
        // Only writes the time when the schedule has a non-empty time.
        if (!empty($schedule['time'])) {
            // Normalises the submitted time to H:i:s.
            $hms = scheduleTimeHMS((string) $schedule['time']);
            // Checks that the time could be understood.
            if ($hms !== null) {
                // Saves the time in the display format 'g:i A'.
                $wf['presentation_time'] = date('g:i A', strtotime('2000-01-01 ' . $hms));
            // Closes the parsed-time check.
            }
        // Closes the time check.
        }
        // Writes the venue even when the coordinator cleared it.
        if (array_key_exists('venue', $schedule)) {
            // Saves the trimmed venue text in the workflow state.
            $wf['presentation_venue'] = trim((string) $schedule['venue']);
        // Closes the venue check.
        }
        // Only writes a panel when a non-empty panel text was sent.
        if (array_key_exists('panel', $schedule) && trim((string) $schedule['panel']) !== '') {
            // Saves the trimmed panel text in the workflow state.
            $wf['presentation_panel'] = trim((string) $schedule['panel']);
        // Closes the panel check.
        }
        // Defaults the presentation date key so it always exists.
        $wf['presentation_date'] = $wf['presentation_date'] ?? '';
        // Defaults the presentation time key so it always exists.
        $wf['presentation_time'] = $wf['presentation_time'] ?? '';
        // Defaults the presentation venue key so it always exists.
        $wf['presentation_venue'] = $wf['presentation_venue'] ?? '';
        // Defaults the presentation panel key so it always exists.
        $wf['presentation_panel'] = $wf['presentation_panel'] ?? '';

        // Writes the updated workflow state back to the applications table.
        $pdo->prepare('UPDATE applications SET workflow_state = :wf WHERE application_id = :id')
            // Binds the JSON text and the application id for the update.
            ->execute([
                // Encodes the workflow state without escaping unicode or slashes.
                'wf' => json_encode($wf, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                // Passes the application id used in the WHERE clause.
                'id' => $applicationId,
            // Closes the update parameters array.
            ]);
    // Catches any database error so scheduling still succeeds.
    } catch (Throwable $e) {
        // Logs the schedule sync error for the developer.
        error_log('[schedule sync] ' . $e->getMessage());
    // Closes the try/catch block.
    }
// Closes syncScheduleToApplication().
}

// Adds a new presentation schedule and returns the saved record.
function addScheduleRecord(array $s): array {
    // Uses MySQL when a database exists and an application id is given.
    if (class_exists('DB') && !empty($s['applicationId'])) {
        // Gets the shared PDO connection.
        $pdo = DB::getConnection();
        // Finds the logged-in coordinator's user id.
        $coordinator = (int) ($_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0);
        // Checks whether no coordinator is logged in.
        if ($coordinator <= 0) {
            // Falls back to the first coordinator account in the users table.
            $coordinator = (int) ($pdo->query("SELECT user_id FROM users WHERE role = 'coordinator' ORDER BY user_id LIMIT 1")->fetchColumn() ?: 0);
        // Closes the missing-coordinator check.
        }
        // Stops when still no coordinator account was found.
        if ($coordinator <= 0) {
            // Throws an error because a schedule needs a managing coordinator.
            throw new RuntimeException('No coordinator account is available to manage the schedule.');
        // Closes the coordinator check.
        }
        // Reads and trims the submitted schedule date.
        $date = trim((string) ($s['date'] ?? ''));
        // Reads and trims the submitted start time.
        $time = trim((string) ($s['time'] ?? ''));
        // Normalises the date to Y-m-d for the database.
        $dateValue = scheduleDateYMD($date);
        // Normalises the time to H:i:s for the database.
        $timeValue = scheduleTimeHMS($time);
        // Checks whether the date or time could not be understood.
        if ($dateValue === null || $timeValue === null) {
            // Rejects the save with a clear invalid date or time error.
            throw new InvalidArgumentException('The schedule date or time is invalid.');
        // Closes the date and time validation.
        }
        // Looks up the adviser id by the adviser's name.
        $adviserStmt = $pdo->prepare('SELECT adviser_id FROM advisor_pool WHERE name = :name LIMIT 1');
        // Runs the adviser lookup with the submitted name.
        $adviserStmt->execute(['name' => trim((string) ($s['adviser'] ?? ''))]);
        // Stores the adviser id, or zero when no match was found.
        $adviserId = (int) ($adviserStmt->fetchColumn() ?: 0);
        // Splits the documentor name into last, first and middle parts.
        $documentorParts = documentorNameParts((string) ($s['documentor'] ?? ''));
        // Uses the first name, or null when the field is empty.
        $documentorFirst = $documentorParts['first'] !== '' ? $documentorParts['first'] : null;
        // Uses the middle name, or null when the field is empty.
        $documentorMiddle = $documentorParts['middle'] !== '' ? $documentorParts['middle'] : null;
        // Uses the surname as the required last-name value.
        $documentorLast = $documentorParts['last'];
        // Starts a transaction so the schedule save is all-or-nothing.
        $pdo->beginTransaction();
        // Starts the try block that can roll the transaction back.
        try {
            // Prepares the INSERT statement for the schedules table.
            $stmt = $pdo->prepare(
                // Starts the SQL text that inserts or updates the schedule row.
                'INSERT INTO schedules
                    (application_id, managed_by_user_id, presentation_date, start_time, venue, adviser_id,
                     documentor_first_name, documentor_middle_name, documentor_last_name, status)
                 VALUES
                    (:application_id, :managed_by_user_id, :presentation_date, :start_time, :venue, :adviser_id,
                     :documentor_first_name, :documentor_middle_name, :documentor_last_name, :status)
                 ON DUPLICATE KEY UPDATE
                    schedule_id = LAST_INSERT_ID(schedule_id),
                    managed_by_user_id = VALUES(managed_by_user_id),
                    presentation_date = VALUES(presentation_date),
                    start_time = VALUES(start_time),
                    venue = VALUES(venue),
                    adviser_id = VALUES(adviser_id),
                    documentor_first_name = VALUES(documentor_first_name),
                    documentor_middle_name = VALUES(documentor_middle_name),
                    documentor_last_name = VALUES(documentor_last_name),
                    status = VALUES(status)'
            // Closes the insert statement text.
            );
            // Binds every schedule column value for the insert.
            $stmt->execute([
                // Passes the application this schedule belongs to.
                'application_id' => (int) $s['applicationId'],
                // Passes the coordinator who manages this schedule.
                'managed_by_user_id' => $coordinator,
                // Passes the normalised presentation date.
                'presentation_date' => $dateValue,
                // Passes the normalised start time.
                'start_time' => $timeValue,
                // Passes the trimmed venue text.
                'venue' => trim((string) ($s['venue'] ?? '')),
                // Passes the adviser id, or null when no adviser matched.
                'adviser_id' => $adviserId > 0 ? $adviserId : null,
                // Passes the documentor's first name.
                'documentor_first_name' => $documentorFirst,
                // Passes the documentor's middle name.
                'documentor_middle_name' => $documentorMiddle,
                // Passes the documentor's surname, or null when empty.
                'documentor_last_name' => $documentorLast !== '' ? $documentorLast : null,
                // Stores 'confirmed' only when the schedule was confirmed.
                'status' => ($s['status'] ?? 'pending') === 'confirmed' ? 'confirmed' : 'pending',
            // Closes the execute parameter array.
            ]);
            // Reads the schedule id of the inserted or updated row.
            $scheduleId = (int) $pdo->lastInsertId();
            // Checks whether MySQL did not return a schedule id.
            if ($scheduleId <= 0) {
                // Prepares a lookup of the schedule id for this application.
                $scheduleStmt = $pdo->prepare('SELECT schedule_id FROM schedules WHERE application_id = :application_id LIMIT 1');
                // Runs the schedule id lookup.
                $scheduleStmt->execute(['application_id' => (int) $s['applicationId']]);
                // Reads the found schedule id, or zero when missing.
                $scheduleId = (int) ($scheduleStmt->fetchColumn() ?: 0);
            // Closes the missing schedule id check.
            }
            // Checks again that a usable schedule id exists.
            if ($scheduleId <= 0) {
                // Throws an error because the saved row cannot be found.
                throw new RuntimeException('The schedule could not be loaded after saving.');
            // Closes the schedule id check.
            }
            // Removes the old panel assignments before saving the new ones.
            $pdo->prepare('DELETE FROM schedule_panel_assignments WHERE schedule_id = :schedule_id')
                // Runs the delete for this schedule id.
                ->execute(['schedule_id' => $scheduleId]);
            // Prepares the insert that links panel members to the schedule.
            $assignmentStmt = $pdo->prepare('INSERT IGNORE INTO schedule_panel_assignments (schedule_id, panel_member_id, panel_role) VALUES (:schedule_id, :panel_member_id, :panel_role)');
            // Reads the panel text submitted by the coordinator.
            $panelText = (string) ($s['panel'] ?? '');
            // Loads every panel member so names can be matched.
            $panelRows = $pdo->query('SELECT panel_member_id, first_name, middle_name, last_name FROM panel_members')->fetchAll();
            // Loops over each panel member row.
            foreach ($panelRows as $panelRow) {
                // Builds the member's displayed name as 'Last, First Middle'.
                $panelName = trim($panelRow['last_name'] . ', ' . $panelRow['first_name'] . (!empty($panelRow['middle_name']) ? ' ' . $panelRow['middle_name'] : ''));
                // Skips members whose name is not in the panel text.
                if ($panelName === '' || !str_contains($panelText, $panelName)) {
                    // Continues to the next panel member.
                    continue;
                // Closes the name match check.
                }
                // Reads the panel member id as an integer.
                $memberId = (int) $panelRow['panel_member_id'];
                // Checks that the panel member id is valid.
                if ($memberId > 0) {
                    // Links this panel member to the schedule as a member.
                    $assignmentStmt->execute(['schedule_id' => $scheduleId, 'panel_member_id' => $memberId, 'panel_role' => 'member']);
                // Closes the valid member id check.
                }
            // Ends the panel member loop.
            }
            // Starts the note about keeping the tracker in step with the schedule.
            // Keep the Milestone Tracker in step with the schedule that was
            // Explains that a confirmed schedule verifies the presentation step.
            // just saved (Confirmed -> "Presentation Scheduled" verified).
            // Syncs the saved schedule into the application's workflow state.
            syncScheduleToApplication((int) $s['applicationId'], [
                // Sends the normalised date to the sync.
                'date' => $dateValue,
                // Sends the normalised time to the sync.
                'time' => $timeValue,
                // Sends the trimmed venue to the sync.
                'venue' => trim((string) ($s['venue'] ?? '')),
                // Sends the panel text to the sync.
                'panel' => (string) ($s['panel'] ?? ''),
                // Sends the confirmed or pending status to the sync.
                'status' => ($s['status'] ?? 'pending') === 'confirmed' ? 'confirmed' : 'pending',
            // Closes the sync data array.
            ]);
            // Commits the transaction now that everything saved.
            $pdo->commit();
            // Returns the saved schedule, or the input data with its id.
            return findSchedule((string) $scheduleId) ?: array_merge($s, ['id' => (string) $scheduleId]);
        // Catches an error so the transaction can be rolled back.
        } catch (Throwable $e) {
            // Undoes the partial save.
            $pdo->rollBack();
            // Rethrows the error for the caller to handle.
            throw $e;
        // Closes the try/catch block.
        }
    // Closes the database branch.
    }
    // Loads the JSON fallback schedule list.
    $list = storeGet('schedules');
    // Starts the fallback record with default values.
    $rec = array_merge([
        // Gives the fallback record a unique id.
        'id' => 'sch_' . uniqid(),
        // Defaults the student email to empty.
        'studentEmail' => '',
        // Defaults the student name to empty.
        'studentName' => '',
        // Defaults the application id to null.
        'applicationId' => null,
        // Defaults the stage label to empty.
        'stage' => '',
        // Defaults the date to empty.
        'date' => '',
        // Defaults the time to empty.
        'time' => '',
        // Defaults the venue to empty.
        'venue' => '',
        // Defaults the panel to TBD.
        'panel' => 'TBD',
        // Defaults the adviser to empty.
        'adviser' => '',
        // Defaults the documentor to empty.
        'documentor' => '',
        // Defaults the notifications list to empty.
        'notifications' => [],
        // Defaults the status to pending.
        'status' => 'pending',
        // Stamps the record with the current date and time.
        'createdAt' => date('c'),
    // Merges the submitted schedule fields over the defaults.
    ], $s);
    // Adds the new record to the stored schedule list.
    $list[] = $rec;
    // Saves the updated list back to storage.
    storeSet('schedules', $list);
    // Returns the new fallback record.
    return $rec;
// Closes addScheduleRecord().
}

// Updates an existing schedule and returns the changed record.
function updateScheduleRecord(string $id, array $patch): ?array {
    // Uses MySQL when available and the id is a numeric database id.
    if (class_exists('DB') && ctype_digit($id)) {
        // Starts the array of columns that will be updated.
        $data = [];
        // Only updates the date when the patch includes one.
        if (isset($patch['date'])) {
            // Normalises the new date to Y-m-d.
            $dateValue = scheduleDateYMD((string) $patch['date']);
            // Queues the new date when it was understood.
            if ($dateValue !== null) $data['presentation_date'] = $dateValue;
        // Closes the date check.
        }
        // Only updates the time when the patch includes one.
        if (isset($patch['time'])) {
            // Normalises the new time to H:i:s.
            $timeValue = scheduleTimeHMS((string) $patch['time']);
            // Queues the new time when it was understood.
            if ($timeValue !== null) $data['start_time'] = $timeValue;
        // Closes the time check.
        }
        // Queues the trimmed venue when it was sent.
        if (isset($patch['venue'])) $data['venue'] = trim((string) $patch['venue']);
        // Queues 'confirmed' or 'pending' when a status was sent.
        if (isset($patch['status'])) $data['status'] = $patch['status'] === 'confirmed' ? 'confirmed' : 'pending';
        // Only looks up the adviser when the name was sent.
        if (isset($patch['adviser'])) {
            // Reads and trims the adviser name.
            $adviserName = trim((string) $patch['adviser']);
            // Checks that the adviser name is not empty.
            if ($adviserName !== '') {
                // Prepares the adviser id lookup by name.
                $adviserStmt = DB::getConnection()->prepare('SELECT adviser_id FROM advisor_pool WHERE name = :name LIMIT 1');
                // Runs the lookup with the adviser name.
                $adviserStmt->execute(['name' => $adviserName]);
                // Queues the adviser id, or null when no match was found.
                $data['adviser_id'] = (int) ($adviserStmt->fetchColumn() ?: 0) ?: null;
            // Closes the non-empty adviser check.
            }
        // Closes the adviser patch check.
        }
        // Only splits the documentor name when it was sent.
        if (isset($patch['documentor'])) {
            // Splits the name into last, first and middle parts.
            $documentorParts = documentorNameParts((string) $patch['documentor']);
            // Checks that a surname was typed.
            if ($documentorParts['last'] !== '') {
                // Queues the documentor's surname.
                $data['documentor_last_name'] = $documentorParts['last'];
                // Queues the first name, or null when empty.
                $data['documentor_first_name'] = $documentorParts['first'] !== '' ? $documentorParts['first'] : null;
                // Queues the middle name, or null when empty.
                $data['documentor_middle_name'] = $documentorParts['middle'] !== '' ? $documentorParts['middle'] : null;
            // Closes the surname check.
            }
        // Closes the documentor patch check.
        }
        // Writes the changed columns to the schedule row.
        if ($data) DB::update('schedules', $data, ['schedule_id' => (int) $id]);
        // Rebuilds the panel links only when a panel was sent.
        if (isset($patch['panel'])) {
            // Gets the shared PDO connection.
            $pdo = DB::getConnection();
            // Deletes the old panel assignments for this schedule.
            $pdo->prepare('DELETE FROM schedule_panel_assignments WHERE schedule_id = :id')->execute(['id' => (int) $id]);
            // Prepares the insert that adds panel members to the schedule.
            $assignmentStmt = $pdo->prepare('INSERT IGNORE INTO schedule_panel_assignments (schedule_id, panel_member_id, panel_role) VALUES (:schedule_id, :panel_member_id, "member")');
            // Reads the panel text from the patch.
            $panelText = (string) $patch['panel'];
            // Loads every panel member so names can be matched.
            $panelRows = $pdo->query('SELECT panel_member_id, first_name, middle_name, last_name FROM panel_members')->fetchAll();
            // Loops over each panel member row.
            foreach ($panelRows as $panelRow) {
                // Builds the member's displayed name as 'Last, First Middle'.
                $panelName = trim($panelRow['last_name'] . ', ' . $panelRow['first_name'] . (!empty($panelRow['middle_name']) ? ' ' . $panelRow['middle_name'] : ''));
                // Checks whether this member's name appears in the panel text.
                if ($panelName !== '' && str_contains($panelText, $panelName)) {
                    // Links the matching panel member to the schedule.
                    $assignmentStmt->execute(['schedule_id' => (int) $id, 'panel_member_id' => (int) $panelRow['panel_member_id']]);
                // Closes the name match check.
                }
            // Ends the panel member loop.
            }
        // Closes the panel patch check.
        }
        
        // Starts the note about syncing the workflow state with the schedule.
        // Keep the application's workflow state in step with the schedule for
        // Explains that a confirmed schedule verifies the presentation step.
        // the Milestone Tracker (Confirmed -> "Presentation Scheduled"
        // Notes that the schedule row is read back after an edit.
        // verified). The schedule row is read back so an edit that only moved
        // Notes that the coordinator's status is kept after the edit.
        // the date still keeps the status the coordinator sees.
        // Only syncs when the database connection class exists.
        if (class_exists('DB')) {
            // Starts the block that protects the schedule sync.
            try {
                // Gets the shared PDO connection.
                $pdo = DB::getConnection();
                // Reads the saved schedule row so the sync uses stored values.
                $sched = $pdo->prepare('SELECT application_id, presentation_date, start_time, venue, status FROM schedules WHERE schedule_id = :id LIMIT 1');
                // Runs the schedule lookup with the schedule id.
                $sched->execute(['id' => (int) $id]);
                // Fetches the schedule row.
                $scheduleRow = $sched->fetch();
                // Reads the application id linked to this schedule.
                $appId = (int) ($scheduleRow['application_id'] ?? 0);
                // Only syncs when the schedule belongs to an application.
                if ($appId > 0) {
                    // Builds the schedule data sent to the workflow sync.
                    $sync = [
                        // Uses the stored presentation date.
                        'date' => (string) ($scheduleRow['presentation_date'] ?? ''),
                        // Uses the stored start time.
                        'time' => (string) ($scheduleRow['start_time'] ?? ''),
                        // Uses the stored venue.
                        'venue' => (string) ($scheduleRow['venue'] ?? ''),
                        // Uses the stored status.
                        'status' => (string) ($scheduleRow['status'] ?? 'pending'),
                    // Closes the sync data array.
                    ];
                    // Adds the panel text when the patch included one.
                    if (isset($patch['panel']) && trim((string) $patch['panel']) !== '') {
                        // Stores the trimmed panel text for the sync.
                        $sync['panel'] = (string) $patch['panel'];
                    // Closes the panel check.
                    }
                    // Writes the schedule values into the application workflow state.
                    syncScheduleToApplication($appId, $sync);
                // Closes the application check.
                }
            // Catches sync errors so the schedule update still succeeds.
            } catch (Throwable $e) {
                // Logs the schedule sync error for the developer.
                error_log('[schedule sync] ' . $e->getMessage());
            // Closes the try/catch block.
            }
        // Closes the database sync check.
        }
        
        // Returns the freshly loaded schedule record.
        return findSchedule($id);
    // Closes the database branch.
    }
    // Loads the JSON fallback schedule list.
    $list = storeGet('schedules');
    // Loops over stored schedules to find the matching id.
    foreach ($list as $i => $s) {
        // Skips schedules whose id does not match.
        if ((string) $s['id'] !== $id) {
            // Continues to the next stored schedule.
            continue;
        // Closes the id match check.
        }
        // Merges the patch values into the found schedule.
        $list[$i] = array_merge($s, $patch);
        // Saves the updated schedule list to storage.
        storeSet('schedules', $list);
        // Returns the updated fallback schedule.
        return $list[$i];
    // Closes the stored schedule loop.
    }
    // Returns null when no schedule had that id.
    return null;
// Closes updateScheduleRecord().
}

// Builds the list of panel names shown in the schedule form.
function panelSelectOptions(): array {
    // Starts the empty options list.
    $opts = [];
    // Loops over the panel members stored in the database.
    foreach (databasePanelMembers() as $p) {
        // Adds the member's name with their qualification in brackets.
        $opts[] = $p['name'] . ($p['qualification'] ? ' (' . $p['qualification'] . ')' : '');
    // Closes the panel member loop.
    }
    // Returns the database names, or sample names when none exist.
    return $opts ?: [
        // Sample panel option: Dr. Juan Dela Cruz.
        'Dr. Juan Dela Cruz (PhD in Computer Science)',
        // Sample panel option: Dr. Ana Reyes.
        'Dr. Ana Reyes (PhD in Information Technology)',
        // Sample panel option: Prof. Miguel Santos.
        'Prof. Miguel Santos (MS in Computer Science)',
        // Sample panel option: Prof. Lisa Fernandez.
        'Prof. Lisa Fernandez (MS in Information Technology)',
        // Sample panel option: Dr. Maria Santos.
        'Dr. Maria Santos (PhD in Computer Science)',
    // Closes the sample options array.
    ];
// Closes panelSelectOptions().
}

/**
 * Determine the index of the student's current workflow stage.
 *
 * The coordinator's "advance to next stage" renames the single application row's
 * presentation_stage (rather than keeping a row per completed stage), so earlier
 * stages legitimately have no row and read as 'not_started'. Scanning back-to-front
 * and taking the furthest stage that actually carries a status makes the stepper
 * land on the stage the student is really at, not on an earlier blank stage.
 */
// Finds which workflow stage the student is currently on.
function workflowCurrentIndex(array $progress, array $stages): int {
    // Counts how many stages the track has.
    $count = count($stages);
    // Handles a track with no stages.
    if ($count === 0) {
        // Returns the first index when there are no stages.
        return 0;
    // Closes the empty track check.
    }
    // Scans the stages from last to first.
    for ($i = $count - 1; $i >= 0; $i--) {
        // Reads the progress row for this stage, or an empty array.
        $p = $progress[$i] ?? [];
        // Reads the stage status, defaulting to not_started.
        $st = $p['stageStatus'] ?? 'not_started';
        // Checks whether the status means the student started the stage.
        if (!in_array($st, ['not_started', 'pending', 'draft', ''], true)) {
            // Checks whether that started stage is still unfinished.
            if (!in_array($st, ['completed', 'approved'], true)) {
                // Returns this stage as the current one.
                return $i;
            // Closes the unfinished stage check.
            }
            // Moves to the next stage, capped at the last index.
            return min($i + 1, $count - 1);
        // Closes the started stage block.
        }
    // Closes the backwards stage loop.
    }
    // Returns the first stage when nothing has been started.
    return 0;
// Closes workflowCurrentIndex().
}

/**
 * The workflow stage a student is currently working on, as its presentation
 * label ('Concept Paper', ...).
 *
 * A presentation schedule can only exist for the stage a student is on, so
 * the Coordinator's schedule forms take this as the authoritative stage of
 * the schedule, whatever the form itself tried to send. It works for every
 * track, because the stage always comes from the student's own track
 * workflow - thesis, capstone and seminar paper alike.
 */
// Gets the label of the stage the student is currently working on.
function studentCurrentStageLabel(string $email, array $student): string {
    // Reads and trims the student's track.
    $track = trim((string) ($student['track'] ?? ''));
    // Handles a student with no stored track.
    if ($track === '') {
        // Works out the track from the student's program instead.
        $track = getTrackForProgram((string) ($student['program'] ?? ''));
    // Closes the missing track check.
    }
    // Loads the ordered stages of the student's track.
    $stages = getWorkflow($track)['stages'];
    // Handles a track without workflow stages.
    if (!$stages) {
        // Returns an empty label because no stage exists.
        return '';
    // Closes the empty stages check.
    }
    // Works out the index of the student's current stage.
    $currentIdx = workflowCurrentIndex(getStudentProgress($email, $track), $stages);
    // Returns the stage label, or empty when none was found.
    return (string) ($stages[$currentIdx]['label'] ?? '');
// Closes studentCurrentStageLabel().
}

/**
 * Every presentation stage of the student's track, in workflow order, with the
 * state the Schedule Presentation form shows:
 *
 *  - open      : may be scheduled now
 *  - scheduled : already has a presentation schedule (locked)
 *  - future    : the student has not reached the stage yet (locked)
 *
 * The list follows the milestone sequence of the student's track, so it works
 * the same for thesis, capstone and seminar-paper students.
 *
 * @return array<int, array{key: string, label: string, locked: bool, state: string, reason: string}>
 */
// Lists every presentation stage with its open, scheduled or future state.
function studentPresentationStageChoices(string $email, array $student): array {
    // Reads and trims the student's track.
    $track = trim((string) ($student['track'] ?? ''));
    // Handles a student with no stored track.
    if ($track === '') {
        // Works out the track from the student's program instead.
        $track = getTrackForProgram((string) ($student['program'] ?? ''));
    // Closes the missing track check.
    }
    // Loads the ordered stages of the student's track.
    $workflowStages = getWorkflow($track)['stages'];
    // Handles a track without workflow stages.
    if (!$workflowStages) {
        // Returns an empty list because there is nothing to schedule.
        return [];
    // Closes the empty stages check.
    }

    // Loads the student's milestone progress.
    $progress = getStudentProgress($email, $track);
    // Works out the index of the stage they are on.
    $currentIdx = workflowCurrentIndex($progress, $workflowStages);

    // Starts a block comment explaining why a stage cannot be scheduled twice.
    /*
     * A stage that already has a schedule cannot be scheduled twice. The
     * schedule table is the record the Presentation Schedules page shows; the
     * workflow state is checked as well so a presentation recorded before the
     * schedules table existed still locks its stage.
     */
    // Starts the list of stage keys that already have a schedule.
    $scheduledKeys = [];
    // Loops over every schedule saved for this student.
    foreach (schedulesForEmail($email) as $schedule) {
        // Turns the schedule's stage text into a stage key.
        $key = stageKeyFromLabel((string) ($schedule['stage'] ?? ''));
        // Checks that a known stage key was found.
        if ($key !== '') {
            // Marks this stage key as already scheduled.
            $scheduledKeys[$key] = true;
        // Closes the stage key check.
        }
    // Closes the schedule loop.
    }
    // Also checks the progress rows for recorded presentations.
    foreach ($progress as $row) {
        // Reads the stage key of the progress row.
        $key = (string) ($row['stageKey'] ?? '');
        // Checks whether this row already has a presentation date.
        if ($key !== '' && !empty($row['presentation']['date'])) {
            // Marks the stage as scheduled so it cannot be booked twice.
            $scheduledKeys[$key] = true;
        // Closes the presentation date check.
        }
    // Closes the progress loop.
    }

    // Starts the list of stage choices shown in the form.
    $choices = [];
    // Loops over each stage in workflow order.
    foreach ($workflowStages as $index => $stage) {
        // Reads the stage key.
        $key = (string) $stage['key'];
        // Reads the stage label used in messages.
        $label = (string) $stage['label'];
        // Starts the stage as open by default.
        $state = 'open';
        // Starts the reason text as empty.
        $reason = '';
        // Checks whether this stage already has a schedule.
        if (isset($scheduledKeys[$key])) {
            // Marks the stage as scheduled and locked.
            $state = 'scheduled';
            // Explains that the existing schedule should be edited instead.
            $reason = 'The ' . $label . ' is already scheduled for this student. Edit that schedule instead of creating a new one.';
        // Otherwise checks whether the stage is still in the future.
        } elseif ($index > $currentIdx) {
            // Marks the stage as future and locked.
            $state = 'future';
            // Explains that the student has not reached this stage.
            $reason = 'The student has not reached the ' . $label . ' stage yet.';
        // Closes the future stage check.
        }
        // Adds this stage's choice entry to the list.
        $choices[] = [
            // Stores the stage key.
            'key' => $key,
            // Stores the stage label.
            'label' => $label,
            // Locks the stage unless its state is open.
            'locked' => $state !== 'open',
            // Stores the computed state.
            'state' => $state,
            // Stores the reason shown to the coordinator.
            'reason' => $reason,
        // Closes the choice entry array.
        ];
    // Closes the stage loop.
    }

    // Returns all stage choices.
    return $choices;
// Closes studentPresentationStageChoices().
}

/**
 * The presentation stages a coordinator may schedule right now: the stages of
 * the student's track that are neither scheduled yet nor still in the future.
 */
// Returns the stages the coordinator may schedule right now.
function studentValidPresentationStages(string $email, array $student): array {
    // Starts the list of open stages.
    $open = [];
    // Loops over every presentation stage choice.
    foreach (studentPresentationStageChoices($email, $student) as $choice) {
        // Only keeps stages that are not locked.
        if (!$choice['locked']) {
            // Adds the stage key and label to the open list.
            $open[] = ['key' => $choice['key'], 'label' => $choice['label']];
        // Closes the unlocked check.
        }
    // Closes the choice loop.
    }
    // Returns the open stages.
    return $open;
// Closes studentValidPresentationStages().
}

/**
 * Stage visual state for the stepper: 'done' when completed/approved or already
 * passed by the current stage, 'active' for the current stage, otherwise 'pending'.
 */
// Returns the stepper state: done, active or pending.
function workflowStageState(array $p, int $idx, int $currentIdx): string {
    // Reads the stage status, defaulting to not_started.
    $st = $p['stageStatus'] ?? 'not_started';
    // Treats finished or already passed stages as done.
    if (in_array($st, ['completed', 'approved'], true) || $idx < $currentIdx) {
        // Returns 'done' for this stage.
        return 'done';
    // Closes the done check.
    }
    // Returns 'active' for the current stage, otherwise 'pending'.
    return $idx === $currentIdx ? 'active' : 'pending';
// Closes workflowStageState().
}

/**
 * The lock state of every stage of a track.
 *
 * The stage the student is working on is open. Every stage after it stays
 * locked until the coordinator approves the progression, and only the stage
 * that is approved next is unlocked - the ones after it remain locked.
 *
 * The order comes from the track itself, so a thesis student walks
 * Concept Paper -> Thesis Proposal -> Final Thesis, a capstone student
 * Capstone Proposal -> Final Capstone, and a seminar-paper student
 * Seminar Paper Proposal -> Final Seminar Paper.
 *
 * Returns one entry per stage key:
 *   locked => whether the stage is still closed to the student
 *   label  => the badge text
 *   reason => why it is locked
 */
// Builds the lock state of every stage for the given current index.
function stageLockStates(array $stages, int $currentIndex): array {
    // Reads the current stage label used in the lock reason.
    $currentLabel = $stages[$currentIndex]['label'] ?? 'the current stage';
    // Starts the lock state map.
    $states = [];
    // Loops over the stages in workflow order.
    foreach (array_values($stages) as $index => $stage) {
        // Locks every stage that comes after the current one.
        $locked = $index > $currentIndex;
        // Starts the state entry for this stage key.
        $states[$stage['key']] = [
            // Stores whether the stage is locked.
            'locked' => $locked,
            // Shows 'Locked' or 'Open' as the badge text.
            'label'  => $locked ? 'Locked' : 'Open',
            // Starts the reason text.
            'reason' => $locked
                // Explains that the coordinator must approve the current stage.
                ? 'Locked until the coordinator approves ' . $currentLabel . '.'
                // Leaves the reason empty for open stages.
                : '',
        // Closes the state entry array.
        ];
    // Closes the stage loop.
    }
    // Returns the lock state map.
    return $states;
// Closes stageLockStates().
}

/**
 * The stage a student is allowed to work on, and whether a requested stage is
 * still locked for them.
 *
 * Returns ['stageKey' => ..., 'locked' => bool, 'reason' => string]. A locked
 * stage falls back to the stage the student is really at, so a student can
 * never reach a future stage before the coordinator approved it.
 */
// Decides which stage a student may work on and whether it is locked.
function resolveStudentStageAccess(array $progress, array $stages, int $currentIndex, string $requestedKey): array {
    // Builds the lock states for the student's stages.
    $states = stageLockStates($stages, $currentIndex);
    // Casts the requested stage key to a string.
    $requestedKey = (string) $requestedKey;
    // Checks that the requested key exists in the track.
    if ($requestedKey !== '' && isset($states[$requestedKey])) {
        // Checks whether the requested stage is not locked.
        if (!$states[$requestedKey]['locked']) {
            // Allows the requested stage with no lock reason.
            return ['stageKey' => $requestedKey, 'locked' => false, 'reason' => ''];
        // Closes the unlocked check.
        }
        // Returns a locked result for a future stage.
        return [
            // Falls back to the first stage key when resolving the key.
            'stageKey' => $states ? array_key_first($states) : $requestedKey,
            // Marks the result as locked.
            'locked'   => true,
            // Returns the reason the stage is locked.
            'reason'   => $states[$requestedKey]['reason'],
        // Closes the locked result array.
        ];
    // Closes the requested key check.
    }
    // Reads the ordered stage keys.
    $keys = array_keys($states);
    // Returns the stage at the current index.
    return [
        // Uses the current index, falling back to the first key.
        'stageKey' => $keys[$currentIndex] ?? ($keys[0] ?? ''),
        // This fallback stage is not locked.
        'locked'   => false,
        // Has no lock reason.
        'reason'   => '',
    // Closes the fallback result array.
    ];
// Closes resolveStudentStageAccess().
}

// Builds an empty progress row for one workflow stage.
function blankStageProgress(array $stage): array {
    // Reuses one 'pending' status array for every milestone.
    $pending = ['status' => 'pending'];
    // Starts the progress row returned to the caller.
    return [
        // Stores the stage key.
        'stageKey' => $stage['key'],
        // Marks the stage as not started.
        'stageStatus' => 'not_started',
        // Paper milestone starts as pending.
        'paper' => $pending,
        // Adviser endorsement milestone starts as pending.
        'adviserEndorsement' => $pending,
        // Coordinator review milestone starts as pending.
        'coordReview' => $pending,
        // Graduate School endorsement milestone starts as pending.
        'gradSchoolEndorsement' => $pending,
        // Payment milestone starts as pending.
        'payment' => $pending,
        // Ready-for-presentation milestone starts as pending.
        'readyForPresentation' => $pending,
        // Presentation milestone starts as pending.
        'presentation' => $pending,
        // Result milestone starts as pending.
        'result' => $pending,
        // No coordinator comment yet.
        'coordinatorComments' => '',
    // Closes the progress row array.
    ];
// Closes blankStageProgress().
}

// Builds the milestone progress rows for a student and track.
function getStudentProgress(string $email, string $track): array {
    // Loads the ordered stages of the student's track.
    $stages = getWorkflow($track)['stages'];
    // Filters the stored applications down to this student's email.
    $apps = array_values(array_filter(storeGet('applications'), static function ($a) use ($email) {
        // Keeps only applications whose student email matches.
        return strcasecmp((string) $a['studentEmail'], $email) === 0;
    // Closes the filter callback call.
    }));
    // Loads the student's uploaded documents.
    $uploads = uploadsForEmail($email);
    // Loads the presentation schedules for the student.
    $schedules = schedulesForEmail($email);

    // Uses the database when the connection class is available.
    if (class_exists('DB')) {
        // Starts the block that tolerates database errors.
        try {
            // Reads the student's applications with all milestone columns.
            $stmt = DB::getConnection()->prepare('SELECT a.application_id AS id, a.presentation_stage AS stage, a.paper_title AS title, a.status, a.coordinator_comment AS coordinatorComment, a.grad_school_endorsed AS gradSchoolEndorsed, a.payment_recorded AS paymentRecorded, a.receipt_number AS receiptNumber, a.payment_date AS paymentDate, a.payment_amount AS paymentAmount, a.ready_for_presentation AS readyForPresentation, a.workflow_state AS workflowState, a.result AS result, a.submitted_at AS date FROM applications a INNER JOIN users u ON u.user_id = a.user_id WHERE u.email = :email AND a.archived_at IS NULL ORDER BY a.submitted_at ASC');
            // Runs the application query with the student's email.
            $stmt->execute(['email' => $email]);
            // Fetches all matching application rows.
            $dbApps = $stmt->fetchAll();
            // Checks whether the database returned any applications.
            if ($dbApps) {
                // Maps each database row to the progress format and decodes its state.
                $apps = array_map(static function ($app) use ($email, $track) { $app['studentEmail'] = $email; $app['track'] = $track; $app['stageKey'] = stageKeyFromLabel($app['stage']); $app['workflowState'] = json_decode((string) ($app['workflowState'] ?? '[]'), true) ?: []; return $app; }, $dbApps);
                // Reads the student's uploaded documents with their review status.
                $docStmt = DB::getConnection()->prepare('SELECT d.document_id AS id, d.application_id AS applicationId, d.original_name AS fileName, d.document_type AS docType, d.stage, d.file_size AS size, d.uploaded_at AS date, d.status, d.stored_name AS storedFile, d.mime_type AS mimeType FROM application_documents d INNER JOIN users u ON u.user_id = d.user_id WHERE u.email = :email ORDER BY d.uploaded_at ASC, d.document_id ASC');
                // Runs the documents query with the student's email.
                $docStmt->execute(['email' => $email]);
                // Fetches all matching document rows.
                $dbUploads = $docStmt->fetchAll();
                // Replaces the uploads with database documents when any were found.
                if ($dbUploads) $uploads = $dbUploads;
            // Closes the database rows check.
            }
        // Catches database errors so the tracker still renders.
        } catch (Throwable $e) {
        // Ignores the error and keeps the stored fallback data.
        }
    // Closes the database block.
    }

    // Starts the list of progress rows.
    $rows = [];
    // Tracks the furthest stage the student has reached.
    $activeStageIndex = -1;
    // Loops over the student's applications.
    foreach ($apps as $app) {
        // Works out the application's stage key.
        $appStageKey = $app['stageKey'] ?? stageKeyFromLabel($app['stage'] ?? '');
        // Loops over the track stages with their indexes.
        foreach ($stages as $stageIndex => $stage) {
            // Checks whether this application matches the stage.
            if ($appStageKey === $stage['key']) {
                // Keeps the furthest matching stage index.
                $activeStageIndex = max($activeStageIndex, $stageIndex);
                // Stops looking once the stage matched.
                break;
            // Closes the stage match check.
            }
        // Closes the stage loop.
        }
    // Closes the application loop.
    }

    // Builds one progress row per workflow stage.
    foreach ($stages as $stageIndex => $stage) {
        // Starts with no application for this stage.
        $app = null;
        // Loops over the applications to find one for this stage.
        foreach ($apps as $a) {
            // Works out the application's stage key.
            $key = $a['stageKey'] ?? stageKeyFromLabel($a['stage'] ?? '');
            // Matches by stage key or by the stage label text.
            if ($key === $stage['key'] || strcasecmp((string) ($a['stage'] ?? ''), $stage['label']) === 0) {
                // Remembers the application for this stage.
                $app = $a;
            // Closes the stage match check.
            }
        // Closes the application loop.
        }
        // Checks whether no application was found for the stage.
        if (!$app) {
            // Tries a second, stricter match by stage key only.
            foreach ($apps as $a) {
                // Works out the application's stage key.
                $key = $a['stageKey'] ?? stageKeyFromLabel($a['stage'] ?? '');
                // Checks whether the stage key matches exactly.
                if ($key === $stage['key']) {
                    // Remembers the application for this stage.
                    $app = $a;
                    // Stops looking once the key matched.
                    break;
                // Closes the key match check.
                }
            // Closes the key loop.
            }
        // Closes the second match block.
        }
        // Checks again whether still no application was found.
        if (!$app) {
            // Builds a blank progress row for this stage.
            $blank = blankStageProgress($stage);
            // Checks whether the student already passed this stage.
            if ($activeStageIndex > $stageIndex) {
                // Marks the past stage as completed.
                $blank['stageStatus'] = 'completed';
                // Marks the paper milestone as done for a past stage.
                $blank['paper'] = ['status' => 'done'];
                // Marks the adviser endorsement as done.
                $blank['adviserEndorsement'] = ['status' => 'done'];
                // Marks the coordinator review as done with no comment.
                $blank['coordReview'] = ['status' => 'done', 'comment' => ''];
                // Marks the Graduate School endorsement as done.
                $blank['gradSchoolEndorsement'] = ['status' => 'done'];
                // Marks the payment milestone as done.
                $blank['payment'] = ['status' => 'done'];
                // Marks the presentation-ready milestone as done.
                $blank['readyForPresentation'] = ['status' => 'done'];
                // Marks the presentation as done.
                $blank['presentation'] = ['status' => 'done'];
                // Marks the result as approved.
                $blank['result'] = ['status' => 'done', 'value' => 'approved'];
            // Closes the past stage check.
            }
            // Adds the blank row to the progress list.
            $rows[] = $blank;
            // Skips the rest and moves to the next stage.
            continue;
        // Closes the missing application block.
        }

        // Reads the application's workflow state array.
        $wf = $app['workflowState'] ?? [];
        // Starts with no paper upload.
        $paperUpload = null;
        // Starts with no adviser endorsement upload.
        $adviserUpload = null;
        // Starts with no Graduate School endorsement upload.
        $gradSchoolUpload = null;
        // Starts with no payment receipt upload.
        $paymentUpload = null;
        // Starts the list of uploads for this stage.
        $stageUploads = [];
        // Loops over the student's uploaded documents.
        foreach ($uploads as $u) {
            // Checks whether the upload belongs to this stage by short label.
            $sameStage = stripos((string) $u['stage'], $stage['shortLabel'] ?? $stage['label']) !== false
                // Also checks the upload stage against the full stage label.
                || stripos((string) $u['stage'], $stage['label']) !== false
                // Also checks the upload stage by its stage key.
                || stageKeyFromLabel((string) $u['stage']) === $stage['key'];
            // Checks whether this upload belongs to another stage.
            if (!$sameStage) {
                // Skips uploads that belong to another stage.
                continue;
            // Closes the other stage check.
            }
            // Adds this upload to the stage's list.
            $stageUploads[] = $u;
        // Closes the upload loop.
        }
        // Starts the note explaining which upload decides a milestone.
        /*
         * The file that was sent last is the one that is reviewed, so it is
         * the one that decides the state of the milestone. The same rule is
         * used for the list of documents of the coordinator and for the
         * requirements of the stage, so the two portals can never disagree
         * about the same document.
         */
        // Picks the newest paper upload for this stage.
        $paperUpload      = latestUploadOfKind($stageUploads, 'paper');
        // Picks the newest adviser endorsement upload.
        $adviserUpload    = latestUploadOfKind($stageUploads, 'adviser_endorsement');
        // Picks the newest Graduate School endorsement upload.
        $gradSchoolUpload = latestUploadOfKind($stageUploads, 'grad_school_endorsement');
        // Picks the newest payment receipt upload.
        $paymentUpload    = latestUploadOfKind($stageUploads, 'receipt');

        // Reads the paper status stored in the workflow state.
        $paperWf = $wf['paper'] ?? '';
        // Reads the adviser endorsement status from the workflow state.
        $advWf = $wf['adviser_endorsement'] ?? '';
        // Reads the Graduate School endorsement status.
        $gradWf = $wf['grad_school_endorsement'] ?? '';
        // Reads the payment status from the workflow state.
        $paymentWf = $wf['payment'] ?? '';

        // Starts the note that document status decides the milestone.
        /*
         * The status of the document itself decides the state of the
         * milestone. The coordinator reviews every document and picks one of
         * the four actions; the student sees exactly that on their tracker.
         * A document that was only uploaded waits for the review, so it does
         * not move the student on before the coordinator had a look at it.
         */
        // Checks whether a paper upload exists for this stage.
        if ($paperUpload) {
            // Uses the paper document's own status as the paper state.
            $paperWf = (string) ($paperUpload['status'] ?? 'submitted');
        // Closes the paper upload check.
        }
        // Checks whether an adviser endorsement upload exists.
        if ($adviserUpload) {
            // Uses the endorsement document's own status.
            $advWf = (string) ($adviserUpload['status'] ?? 'submitted');
        // Closes the adviser upload check.
        }
        // Checks whether a Graduate School endorsement upload exists.
        if ($gradSchoolUpload) {
            // Uses the endorsement document's own status.
            $gradWf = (string) ($gradSchoolUpload['status'] ?? 'submitted');
        // Closes the Graduate School upload check.
        }
        // Checks whether a payment receipt upload exists.
        if ($paymentUpload) {
            // Uses the receipt document's own status.
            $paymentWf = (string) ($paymentUpload['status'] ?? 'submitted');
        // Closes the payment upload check.
        }

        // Maps the paper review status to a milestone state.
        $paperStatus = $paperUpload ? documentMilestoneState($paperWf) : 'pending';
        // Maps the adviser endorsement status to a milestone state.
        $adviserStatus = $adviserUpload ? documentMilestoneState($advWf) : 'pending';
        // Flags coordinator review when the paper or endorsement is flagged.
        $coordStatus = ($paperStatus === 'flagged' || $adviserStatus === 'flagged')
            // Uses 'flagged' for the coordinator review state.
            ? 'flagged'
            // Otherwise done only when both documents are done.
            : (($paperStatus === 'done' && $adviserStatus === 'done') ? 'done' : 'pending');
        // Maps the Graduate School endorsement status to a milestone state.
        $gradStatus = $gradSchoolUpload ? documentMilestoneState($gradWf) : 'pending';
        // Starts the payment state from the receipt when one exists.
        $paymentStatus = $paymentUpload
            // Maps the receipt review status to a milestone state.
            ? documentMilestoneState($paymentWf)
            // Falls back to the recorded payment flag, otherwise pending.
            : (paymentIsRecorded($app) ? 'done' : 'pending');

        // Starts with no schedule for this stage.
        $schedule = null;
        // Starts the presentation state as pending.
        $presentationStatus = 'pending';
        // Starts the presentation date as empty.
        $presentationDate = '';
        // Starts the presentation time as empty.
        $presentationTime = '';
        // Starts the presentation venue as empty.
        $presentationVenue = '';
        // Starts the presentation panel as empty.
        $presentationPanel = '';
        // Checks whether the workflow state already holds a date.
        if (!empty($wf['presentation_date'])) {
            // Builds a schedule array from the workflow state.
            $schedule = [
                // Uses the stored presentation date.
                'date' => (string) ($wf['presentation_date'] ?? ''),
                // Uses the stored presentation time.
                'time' => (string) ($wf['presentation_time'] ?? ''),
                // Uses the stored presentation venue.
                'venue' => (string) ($wf['presentation_venue'] ?? ''),
                // Uses the stored presentation panel.
                'panel' => (string) ($wf['presentation_panel'] ?? ''),
                // Keeps the application id with the schedule data.
                'applicationId' => $app['id'] ?? null,
            // Closes the schedule array.
            ];
            // Copies the presentation date for the progress row.
            $presentationDate = (string) ($wf['presentation_date'] ?? '');
            // Copies the presentation time for the progress row.
            $presentationTime = (string) ($wf['presentation_time'] ?? '');
            // Copies the presentation venue for the progress row.
            $presentationVenue = (string) ($wf['presentation_venue'] ?? '');
            // Copies the presentation panel for the progress row.
            $presentationPanel = (string) ($wf['presentation_panel'] ?? '');
            // Marks the presentation done only when it is confirmed.
            $presentationStatus = ($wf['presentation_status'] ?? '') === 'confirmed' ? 'done' : 'pending';
            // Starts the note that the schedule row decides the milestone.
            /*
             * The schedule row is the record the coordinator sees on the
             * Presentation Schedules page, so when one exists for this
             * application it decides the milestone. Applications written
             * before the workflow state carried the confirmation then still
             * show the right state.
             */
            // Loops over the schedules saved for the student.
            foreach ($schedules as $s) {
                // Checks whether this schedule belongs to the application.
                if ((int) ($s['applicationId'] ?? 0) === (int) ($app['id'] ?? 0)) {
                    // Uses the schedule row's confirmed or completed status.
                    $presentationStatus = in_array((string) ($s['status'] ?? ''), ['confirmed', 'completed'], true) ? 'done' : 'pending';
                    // Stops looking once the matching schedule was found.
                    break;
                // Closes the application match check.
                }
            // Closes the schedule loop.
            }
        // Handles the case where the workflow state has no date.
        } else {
            // Loops over saved schedules to find one for this stage.
            foreach ($schedules as $s) {
                // Matches the schedule by application id or by stage label.
                if ((int) ($s['applicationId'] ?? 0) === (int) $app['id'] || stripos((string) $s['stage'], $stage['shortLabel'] ?? '') !== false) {
                    // Uses this schedule as the stage's schedule.
                    $schedule = $s;
                    // Copies the schedule date.
                    $presentationDate = (string) ($s['date'] ?? '');
                    // Copies the schedule time.
                    $presentationTime = (string) ($s['time'] ?? '');
                    // Copies the schedule venue.
                    $presentationVenue = (string) ($s['venue'] ?? '');
                    // Copies the schedule panel.
                    $presentationPanel = (string) ($s['panel'] ?? '');
                    // Marks the presentation done only when confirmed.
                    $presentationStatus = ($s['status'] ?? 'pending') === 'confirmed' ? 'done' : 'pending';
                    // Stops looking after the first matching schedule.
                    break;
                // Closes the application match check.
                }
            // Closes the schedule loop.
            }
        // Closes the workflow-date branch.
        }

        // Reads the application's result value.
        $resultVal = $app['result'] ?? '';
        // Adds the finished progress row to the list.
        $rows[] = [
            // Stores the stage key.
            'stageKey' => $stage['key'],
            // Stores the application's stage status.
            'stageStatus' => $app['status'],
            // Stores the paper state, date and review status.
            'paper' => ['status' => $paperStatus, 'submitted' => $paperUpload['date'] ?? '', 'review' => $paperWf],
            // Stores the adviser endorsement state, date and review.
            'adviserEndorsement' => ['status' => $adviserStatus, 'submitted' => $adviserUpload['date'] ?? '', 'review' => $advWf],
            // Stores the coordinator review state and any comment.
            'coordReview' => ['status' => $coordStatus, 'comment' => $coordStatus === 'flagged' ? ($app['coordinatorComment'] ?? '') : ''],
            // Stores the Graduate School endorsement state, date and review.
            'gradSchoolEndorsement' => ['status' => $gradStatus, 'submitted' => $gradSchoolUpload['date'] ?? '', 'review' => $gradWf],
            // Stores the payment state, date and review status.
            'payment' => ['status' => $paymentStatus, 'submitted' => !empty($app['paymentDate']) ? $app['paymentDate'] : ($paymentUpload['date'] ?? ''), 'review' => $paymentWf],
            // Marks presentation-ready as done when the flag is set.
            'readyForPresentation' => ['status' => !empty($app['readyForPresentation']) ? 'done' : 'pending'],
            // Starts the presentation details array.
            'presentation' => [
                // Stores the presentation state.
                'status' => $presentationStatus,
                // Stores the presentation date.
                'date' => $presentationDate,
                // Stores the presentation time.
                'time' => $presentationTime,
                // Stores the presentation venue.
                'venue' => $presentationVenue,
                // Stores the presentation panel.
                'panel' => $presentationPanel,
            // Closes the presentation details array.
            ],
            // Starts the result details array.
            'result' => [
                // Maps approved or requires_revision to a result state.
                'status' => $resultVal === 'approved' ? 'done' : ($resultVal === 'requires_revision' ? 'flagged' : 'pending'),
                // Maps the result value used for display.
                'value' => $resultVal === 'approved' ? 'approved' : ($resultVal === 'requires_revision' ? 'revision' : ''),
            // Closes the result details array.
            ],
            // Stores the coordinator's overall comment.
            'coordinatorComments' => $app['coordinatorComment'] ?? '',
        // Closes the progress row array.
        ];
    // Closes the stage progress loop.
    }

    // Returns all stage progress rows.
    return $rows;
// Closes getStudentProgress().
}

/**
 * Return an error when a student tries to submit a stage before its predecessor
 * has been approved or completed.
 *
 * A stage the student already finished cannot be applied for again, and a
 * stage after the one they are working on stays closed until the coordinator
 * approves the progression.
 */
// Validates that a student may apply for the requested stage.
function validateApplicationStageSequence(array $progress, array $stages, string $requestedStageKey): ?string {
    // Reads the ordered stage keys of the track.
    $stageKeys = array_keys($stages);
    // Finds the position of the requested stage key.
    $requestedIndex = array_search($requestedStageKey, $stageKeys, true);
    // Checks whether the requested stage is not in the track.
    if ($requestedIndex === false) {
        // Notes that a stage outside the track is never open.
        // A stage that is not part of the track is never open.
        // Returns an error for an unknown presentation stage.
        return 'Select a valid presentation stage.';
    // Closes the unknown stage check.
    }

    // Notes that a completed stage is closed for good.
    // A stage the student has already completed is closed for good.
    // Loops over the student's progress rows.
    foreach ($progress as $stage) {
        // Skips progress rows for other stages.
        if (($stage['stageKey'] ?? '') !== $requestedStageKey) {
            // Continues to the next progress row.
            continue;
        // Closes the stage key check.
        }
        // Checks whether this stage is already completed or approved.
        if (in_array(($stage['stageStatus'] ?? ''), ['completed', 'approved'], true)) {
            // Reads the stage label for the error message.
            $completedLabel = $stages[$requestedStageKey]['label'] ?? 'this stage';
            // Returns an error saying the stage is already completed.
            return 'You have already completed the ' . $completedLabel . '.';
        // Closes the completed stage check.
        }
    // Closes the progress loop.
    }

    // Checks whether the requested stage is the first one.
    if ($requestedIndex === 0) {
        // Allows the first stage with no error.
        return null;
    // Closes the first stage check.
    }

    // Reads the stage key that comes before the requested one.
    $previousStageKey = $stageKeys[$requestedIndex - 1];
    // Loops over progress rows to find the previous stage.
    foreach ($progress as $stage) {
        // Skips progress rows for other stages.
        if (($stage['stageKey'] ?? '') !== $previousStageKey) {
            // Continues to the next progress row.
            continue;
        // Closes the stage key check.
        }

        // Checks whether the previous stage is approved or completed.
        if (in_array(($stage['stageStatus'] ?? ''), ['approved', 'completed'], true)) {
            // Allows the request because the previous stage is done.
            return null;
        // Closes the previous stage check.
        }

        // Reads the previous stage label for the error message.
        $previousLabel = $stages[$previousStageKey]['label'] ?? 'previous stage';
        // Returns an error asking the student to finish the previous stage.
        return 'You cannot apply for this stage yet. Complete and receive approval for the ' . $previousLabel . ' first.';
    // Closes the progress loop.
    }

    // Reads the previous stage label for the fallback error.
    $previousLabel = $stages[$previousStageKey]['label'] ?? 'previous stage';
    // Returns an error because the previous stage was not found.
    return 'You cannot apply for this stage yet. Submit and complete the ' . $previousLabel . ' first.';
// Closes validateApplicationStageSequence().
}

// Returns sample milestone progress for the given track.
function demoProgressForTrack(string $track): array {
    // Checks whether the track is capstone.
    if ($track === 'capstone') {
        // Returns the capstone sample progress.
        return demoCapstoneProgress();
    // Closes the capstone check.
    }
    // Checks whether the track is seminar paper.
    if ($track === 'seminar') {
        // Returns the seminar paper sample progress.
        return demoSeminarProgress();
    // Closes the seminar check.
    }
    // Falls back to the thesis sample progress.
    return demoThesisProgress();
// Closes demoProgressForTrack().
}

// Returns sample thesis progress used for demos.
function demoThesisProgress(): array {
    // Starts the list of thesis stage samples.
    return [
        // Starts the Concept Paper sample stage.
        [
            // Marks the Concept Paper as completed.
            'stageKey' => 'concept', 'stageStatus' => 'completed',
            // Shows the paper as done and submitted in November 2024.
            'paper' => ['status' => 'done', 'submitted' => 'Nov 10, 2024'],
            // Shows the adviser endorsement as done.
            'adviserEndorsement' => ['status' => 'done', 'submitted' => 'Nov 08, 2024'],
            // Shows the coordinator review as done with no comment.
            'coordReview' => ['status' => 'done', 'comment' => ''],
            // Shows the Graduate School endorsement as done.
            'gradSchoolEndorsement' => ['status' => 'done'],
            // Shows the payment as done.
            'payment' => ['status' => 'done', 'submitted' => 'Nov 22, 2024'],
            // Shows the presentation-ready milestone as done.
            'readyForPresentation' => ['status' => 'done'],
            // Shows the Concept Paper presentation details.
            'presentation' => ['status' => 'done', 'date' => 'Dec 05, 2024', 'time' => '9:00 AM', 'venue' => 'CSITE Seminar Room', 'panel' => 'Dela Cruz, Reyes, Santos'],
            // Shows the result as approved.
            'result' => ['status' => 'done', 'value' => 'approved'],
            // Has no coordinator comment.
            'coordinatorComments' => '',
        // Closes the Concept Paper sample.
        ],
        // Starts the Thesis Proposal sample stage.
        [
            // Marks the proposal as under review.
            'stageKey' => 'proposal', 'stageStatus' => 'under_review',
            // Shows the proposal paper as submitted in March 2025.
            'paper' => ['status' => 'done', 'submitted' => 'Mar 15, 2025'],
            // Shows the adviser endorsement as done.
            'adviserEndorsement' => ['status' => 'done', 'submitted' => 'Mar 14, 2025'],
            // Flags the coordinator review with a literature review comment.
            'coordReview' => ['status' => 'flagged', 'comment' => 'Please complete your review of related literature section.'],
            // Shows the Graduate School endorsement as pending.
            'gradSchoolEndorsement' => ['status' => 'pending'],
            // Shows the payment as still pending.
            'payment' => ['status' => 'pending', 'submitted' => ''],
            // Shows the presentation-ready milestone as pending.
            'readyForPresentation' => ['status' => 'pending'],
            // Has no presentation details yet.
            'presentation' => ['status' => 'pending', 'date' => '', 'time' => '', 'venue' => '', 'panel' => ''],
            // Has no result yet.
            'result' => ['status' => 'pending', 'value' => ''],
            // Adds a coordinator comment about the literature review.
            'coordinatorComments' => 'Your proposal looks promising. Please address the incomplete literature review before we can proceed to Graduate School endorsement.',
        // Closes the Thesis Proposal sample.
        ],
        // Starts the Final Thesis sample stage.
        [
            // Marks the final stage as not started.
            'stageKey' => 'final', 'stageStatus' => 'not_started',
            // Shows the final paper as pending.
            'paper' => ['status' => 'pending', 'submitted' => ''],
            // Shows the adviser endorsement as pending.
            'adviserEndorsement' => ['status' => 'pending', 'submitted' => ''],
            // Shows the coordinator review as pending.
            'coordReview' => ['status' => 'pending', 'comment' => ''],
            // Shows the Graduate School endorsement as pending.
            'gradSchoolEndorsement' => ['status' => 'pending'],
            // Shows the payment as pending.
            'payment' => ['status' => 'pending', 'submitted' => ''],
            // Shows the presentation-ready milestone as pending.
            'readyForPresentation' => ['status' => 'pending'],
            // Has no presentation details yet.
            'presentation' => ['status' => 'pending', 'date' => '', 'time' => '', 'venue' => '', 'panel' => ''],
            // Has no result yet.
            'result' => ['status' => 'pending', 'value' => ''],
            // Has no coordinator comment.
            'coordinatorComments' => '',
        // Closes the Final Thesis sample.
        ],
    // Closes the thesis sample list.
    ];
// Closes demoThesisProgress().
}

// Returns sample capstone progress used for demos.
function demoCapstoneProgress(): array {
    // Starts the list of capstone stage samples.
    return [
        // Starts the Capstone Proposal sample stage.
        [
            // Marks the proposal as ready for presentation.
            'stageKey' => 'proposal', 'stageStatus' => 'ready_for_presentation',
            // Shows the proposal paper as submitted in February 2025.
            'paper' => ['status' => 'done', 'submitted' => 'Feb 20, 2025'],
            // Shows the adviser endorsement as done.
            'adviserEndorsement' => ['status' => 'done', 'submitted' => 'Feb 18, 2025'],
            // Shows the coordinator review as done with no comment.
            'coordReview' => ['status' => 'done', 'comment' => ''],
            // Shows the Graduate School endorsement as done.
            'gradSchoolEndorsement' => ['status' => 'done'],
            // Shows the payment as done in March 2025.
            'payment' => ['status' => 'done', 'submitted' => 'Mar 05, 2025'],
            // Shows the presentation-ready milestone as done.
            'readyForPresentation' => ['status' => 'done'],
            // Shows a pending presentation scheduled for April 2025.
            'presentation' => ['status' => 'pending', 'date' => 'Apr 20, 2025', 'time' => '10:00 AM', 'venue' => 'CSITE Seminar Room', 'panel' => 'TBD'],
            // Has no result yet.
            'result' => ['status' => 'pending', 'value' => ''],
            // Has no coordinator comment.
            'coordinatorComments' => '',
        // Closes the Capstone Proposal sample.
        ],
        // Starts the Final Capstone sample stage.
        [
            // Marks the final stage as not started.
            'stageKey' => 'final', 'stageStatus' => 'not_started',
            // Shows the final paper as pending.
            'paper' => ['status' => 'pending', 'submitted' => ''],
            // Shows the adviser endorsement as pending.
            'adviserEndorsement' => ['status' => 'pending', 'submitted' => ''],
            // Shows the coordinator review as pending.
            'coordReview' => ['status' => 'pending', 'comment' => ''],
            // Shows the Graduate School endorsement as pending.
            'gradSchoolEndorsement' => ['status' => 'pending'],
            // Shows the payment as pending.
            'payment' => ['status' => 'pending', 'submitted' => ''],
            // Shows the presentation-ready milestone as pending.
            'readyForPresentation' => ['status' => 'pending'],
            // Has no presentation details yet.
            'presentation' => ['status' => 'pending', 'date' => '', 'time' => '', 'venue' => '', 'panel' => ''],
            // Has no result yet.
            'result' => ['status' => 'pending', 'value' => ''],
            // Has no coordinator comment.
            'coordinatorComments' => '',
        // Closes the demo final-stage entry of the capstone progress list.
        ],
    // Closes the array of demo capstone stage progress entries.
    ];
// Ends demoCapstoneProgress, which returns sample capstone progress.
}

// Starts demoSeminarProgress, returning sample seminar-paper progress.
function demoSeminarProgress(): array {
    // Returns the list of demo stage progress entries.
    return [
        // Starts the entry for the proposal stage.
        [
            // Sets the proposal stage key and its submitted status.
            'stageKey' => 'proposal', 'stageStatus' => 'submitted',
            // Marks the proposal paper as done and records its submission date.
            'paper' => ['status' => 'done', 'submitted' => 'Mar 01, 2025'],
            // Marks the adviser endorsement as done with its submission date.
            'adviserEndorsement' => ['status' => 'done', 'submitted' => 'Feb 28, 2025'],
            // Leaves the coordinator review pending with an empty comment.
            'coordReview' => ['status' => 'pending', 'comment' => ''],
            // Leaves the Graduate School endorsement pending.
            'gradSchoolEndorsement' => ['status' => 'pending'],
            // Leaves the payment pending with no submission date.
            'payment' => ['status' => 'pending', 'submitted' => ''],
            // Leaves the ready-for-presentation flag pending.
            'readyForPresentation' => ['status' => 'pending'],
            // Leaves the presentation pending with empty date, time, venue, and panel.
            'presentation' => ['status' => 'pending', 'date' => '', 'time' => '', 'venue' => '', 'panel' => ''],
            // Leaves the presentation result pending with no value.
            'result' => ['status' => 'pending', 'value' => ''],
            // Stores an empty coordinator comment for this stage.
            'coordinatorComments' => '',
        // Closes the proposal stage entry.
        ],
        // Starts the entry for the final stage.
        [
            // Sets the final stage key with a not-started status.
            'stageKey' => 'final', 'stageStatus' => 'not_started',
            // Leaves the final paper pending with no submission date.
            'paper' => ['status' => 'pending', 'submitted' => ''],
            // Leaves the final adviser endorsement pending.
            'adviserEndorsement' => ['status' => 'pending', 'submitted' => ''],
            // Leaves the final coordinator review pending with an empty comment.
            'coordReview' => ['status' => 'pending', 'comment' => ''],
            // Leaves the final Graduate School endorsement pending.
            'gradSchoolEndorsement' => ['status' => 'pending'],
            // Leaves the final payment pending with no date.
            'payment' => ['status' => 'pending', 'submitted' => ''],
            // Leaves the final ready-for-presentation flag pending.
            'readyForPresentation' => ['status' => 'pending'],
            // Leaves the final presentation pending with empty details.
            'presentation' => ['status' => 'pending', 'date' => '', 'time' => '', 'venue' => '', 'panel' => ''],
            // Leaves the final result pending with no value.
            'result' => ['status' => 'pending', 'value' => ''],
            // Stores an empty coordinator comment for the final stage.
            'coordinatorComments' => '',
        // Closes the final stage entry.
        ],
    // Closes the list of demo stage entries.
    ];
// Ends demoSeminarProgress.
}

// Builds the icon markup for one milestone state.
function stepIconHtml(string $state): string {
    // Checks whether the milestone is done.
    if ($state === 'done') {
        // Returns a check icon for a completed milestone.
        return '<span class="step-icon step-icon-done"><i class="fas fa-check"></i></span>';
    // Closes the done branch.
    }
    // Checks whether the milestone was flagged for correction.
    if ($state === 'flagged') {
        // Returns an exclamation icon for a flagged milestone.
        return '<span class="step-icon step-icon-flagged"><i class="fas fa-exclamation"></i></span>';
    // Closes the flagged branch.
    }
    // Checks whether the milestone is submitted and waits for review.
    if ($state === 'submitted') {
        // Returns a clock icon for a submitted milestone.
        return '<span class="step-icon step-icon-submitted"><i class="fas fa-clock"></i></span>';
    // Closes the submitted branch.
    }
    // Checks whether the milestone is the active one.
    if ($state === 'active') {
        // Returns a dot icon for the active milestone.
        return '<span class="step-icon step-icon-active"><span class="step-icon-dot"></span></span>';
    // Closes the active branch.
    }
    // Returns the plain pending icon for every other state.
    return '<span class="step-icon step-icon-pending"></span>';
// Ends stepIconHtml.
}

/**
 * The milestone steps of one stage, with labels that follow the track and the
 * stage. A thesis student therefore sees "Thesis Proposal Prepared &
 * Submitted" and "Thesis Proposal Endorsement to Graduate School", while a
 * capstone student sees the capstone wording, and a seminar-paper student the
 * seminar wording.
 *
 * Each step: label, detail, state (done | flagged | pending), coord (whether
 * the coordinator is responsible) and whether the student can still upload it.
 */
// Builds the milestone steps of one stage with track-specific labels.
function milestoneStepsForStage(array $p, string $track, string $stageKey): array {
    // Starts with no matching stage definition.
    $stage = null;
    // Loops through the stages of the track workflow.
    foreach (getWorkflow($track)['stages'] as $candidate) {
        // Checks whether this stage matches the requested stage key.
        if (($candidate['key'] ?? '') === $stageKey) {
            // Remembers the matching stage definition.
            $stage = $candidate;
            // Stops looking once the stage is found.
            break;
        // Closes the stage match check.
        }
    // Closes the loop over the stage definitions.
    }
    // Reads the paper label from the stage, defaulting to Paper.
    $paperLabel = $stage['documents'][0]['label'] ?? 'Paper';
    // Reads the adviser endorsement form label with a fallback.
    $adviserLabel = $stage['adviserEndorsementForm']['label'] ?? 'Adviser Endorsement Form';
    // Reads the Graduate School endorsement label with a fallback.
    $gradLabel = $stage['gradSchoolEndorsementForm']['label'] ?? 'Endorsement to Graduate School';

    // Starts a block comment explaining how milestone wording is chosen.
    /*
     * The wording of a milestone follows what the coordinator decided about
     * the document, so the student always knows where they stand.
     */
    // Defines a helper that turns a document entry into its detail text.
    $documentDetail = static function (array $entry, string $uploadHint, string $missingHint): string {
        // Reads the stored status, defaulting to pending.
        $status = (string) ($entry['status'] ?? 'pending');
        // Reads the coordinator review value in lowercase.
        $review = strtolower((string) ($entry['review'] ?? ''));
        // Checks whether the document is verified.
        if ($status === 'done') {
            // Returns Verified plus the submission date when it is known.
            return 'Verified' . (!empty($entry['submitted']) ? ': ' . $entry['submitted'] : '');
        // Closes the verified branch.
        }
        // Checks whether the document is submitted but not yet reviewed.
        if ($status === 'submitted') {
            // Starts the Submitted text with the submission date.
            return 'Submitted' . (!empty($entry['submitted']) ? ': ' . $entry['submitted'] : '')
                // Adds the waiting-for-the-coordinator note.
                . ' — waiting for the coordinator to review it';
        // Closes the submitted branch.
        }
        // Checks whether the coordinator marked the document incomplete.
        if ($review === 'incomplete') {
            // Explains that the coordinator needs something fixed.
            return 'Incomplete — the coordinator needs something fixed in this document';
        // Closes the incomplete branch.
        }
        // Checks whether the coordinator asked for a revision.
        if ($review === 'revision') {
            // Asks the student to upload a new version.
            return 'Revision requested — upload a new version of this document';
        // Closes the revision branch.
        }
        // Checks whether the milestone is flagged for attention.
        if ($status === 'flagged') {
            // Says the coordinator asked for a correction.
            return 'Needs attention — the coordinator asked for a correction';
        // Closes the flagged branch.
        }
        // Falls back to the upload or missing hint for this document.
        return !empty($entry['submitted']) ? $uploadHint : $missingHint;
    // Ends the detail helper closure.
    };

    // Returns the ordered list of milestone steps for the stage.
    return [
        // Starts the paper milestone entry.
        [
            // Sets the key that identifies the paper milestone.
            'key'    => 'paper',
            // Builds the paper label from the stage wording.
            'label'  => $paperLabel . ' Prepared & Submitted',
            // Uses the paper state, defaulting to pending.
            'state'  => $p['paper'] ?? ['status' => 'pending'],
            // Marks the paper as the student's own task.
            'coord'  => false,
            // Sets the document type the student uploads for this step.
            'docType' => $paperLabel,
            // Builds the detail text from the paper state.
            'detail' => $documentDetail(
                // Passes the stored paper entry to the detail helper.
                $p['paper'] ?? [],
                // Shows the upload date when the paper was already sent.
                !empty($p['paper']['submitted']) ? 'Uploaded: ' . $p['paper']['submitted'] : '',
                // Tells the student to upload the paper when it is missing.
                'Upload your ' . strtolower($paperLabel) . ' to continue'
            // Closes the paper detail call.
            ),
        // Closes the paper milestone entry.
        ],
        // Starts the adviser endorsement milestone entry.
        [
            // Sets the key for the adviser endorsement milestone.
            'key'    => 'adviser_endorsement',
            // Labels the step as the adviser endorsement submission.
            'label'  => 'Adviser Endorsement Submitted',
            // Uses the endorsement state, defaulting to pending.
            'state'  => $p['adviserEndorsement'] ?? ['status' => 'pending'],
            // Marks the endorsement as the student's own task.
            'coord'  => false,
            // Sets the adviser form document type.
            'docType' => $adviserLabel,
            // Builds the detail text for the endorsement.
            'detail' => $documentDetail(
                // Passes the stored endorsement entry to the helper.
                $p['adviserEndorsement'] ?? [],
                // Shows the upload date when the endorsement was sent.
                !empty($p['adviserEndorsement']['submitted']) ? 'Uploaded: ' . $p['adviserEndorsement']['submitted'] : '',
                // Tells the student to get the adviser signature.
                'Obtain your adviser’s signature on the ' . $adviserLabel
            // Closes the endorsement detail call.
            ),
        // Closes the adviser endorsement entry.
        ],
        // Starts the coordinator document review milestone entry.
        [
            // Sets the key for the coordinator review milestone.
            'key'    => 'coord_review',
            // Labels the step as the coordinator document review.
            'label'  => 'Coordinator Document Review',
            // Uses the review state, defaulting to pending.
            'state'  => $p['coordReview'] ?? ['status' => 'pending'],
            // Marks the review as the coordinator's task.
            'coord'  => true,
            // Gives the review no document type to upload.
            'docType' => '',
            // Uses the review comment, or a status message when it is empty.
            'detail' => (($p['coordReview']['comment'] ?? '') ?: (($p['coordReview']['status'] ?? '') === 'done'
                // Says the documents were verified when the review is done.
                ? 'Documents verified'
                // Otherwise says the review still waits for the coordinator.
                : 'Waiting for coordinator')),
        // Closes the review detail call.
        ],
        // Closes the coordinator review entry.
        [
            // Starts the Graduate School endorsement milestone entry.
            'key'    => 'grad_school_endorsement',
            // Sets the key for the Graduate School endorsement milestone.
            'label'  => $gradLabel,
            // Uses the endorsement label from the stage definition.
            'state'  => $p['gradSchoolEndorsement'] ?? ['status' => 'pending'],
            // Uses the endorsement state, defaulting to pending.
            'coord'  => false,
            // Marks the endorsement as the student's own task.
            'docType' => $gradLabel,
            // Sets the Graduate School form document type.
            'detail' => $documentDetail(
                // Builds the detail text for the endorsement form.
                $p['gradSchoolEndorsement'] ?? [],
                // Passes the stored endorsement entry to the helper.
                !empty($p['gradSchoolEndorsement']['submitted']) ? 'Uploaded: ' . $p['gradSchoolEndorsement']['submitted'] : '',
                // Shows the upload date when the form was sent.
                'Complete the Graduate School endorsement form and upload it for review'
            // Tells the student to complete and upload the form.
            ),
        // Closes the endorsement detail call.
        ],
        // Closes the Graduate School endorsement entry.
        [
            // Starts the payment milestone entry.
            'key'    => 'payment',
            // Sets the key for the payment milestone.
            'label'  => 'Official Receipt / Payment Uploaded',
            // Labels the step as the receipt or payment upload.
            'state'  => $p['payment'] ?? ['status' => 'pending'],
            // Uses the payment state, defaulting to pending.
            'coord'  => false,
            // Marks the payment as the student's own task.
            'docType' => 'Official Receipt / Payment Proof',
            // Sets the receipt document type.
            'detail' => $documentDetail(
                // Builds the detail text for the payment.
                $p['payment'] ?? [],
                // Shows the received date when the receipt was sent.
                !empty($p['payment']['submitted']) ? 'Received: ' . $p['payment']['submitted'] : '',
                // Tells the student to submit the official receipt.
                'Submit the official receipt after payment at the Graduate School'
            // Closes the payment detail call.
            ),
        // Closes the payment milestone entry.
        ],
        // Starts the ready-for-presentation milestone entry.
        [
            // Sets the key for the ready-for-presentation milestone.
            'key'    => 'ready_for_presentation',
            // Labels the step as the ready-for-presentation status.
            'label'  => 'Status Set to Ready for Presentation',
            // Uses the readiness state, defaulting to pending.
            'state'  => $p['readyForPresentation'] ?? ['status' => 'pending'],
            // Marks readiness as the coordinator's task.
            'coord'  => true,
            // Checks whether the coordinator already marked the student ready.
            'detail' => ($p['readyForPresentation']['status'] ?? '') === 'done'
                // Says the coordinator confirmed readiness.
                ? 'Coordinator confirmed readiness'
                // Otherwise says readiness comes after receipt verification.
                : 'Coordinator marks ready after the receipt is verified',
        // Closes the readiness detail expression.
        ],
        // Closes the ready-for-presentation entry.
        [
            // Starts the presentation milestone entry.
            'key'    => 'presentation',
            // Sets the key for the presentation milestone.
            'label'  => 'Presentation Scheduled',
            // Labels the step as the presentation schedule.
            'state'  => $p['presentation'] ?? ['status' => 'pending'],
            // Uses the presentation state, defaulting to pending.
            'coord'  => true,
            // Marks scheduling as the coordinator's task.
            'detail' => !empty($p['presentation']['date'])
                // Shows a schedule once a presentation date is set.
                ? trim($p['presentation']['date'] . ' – ' . ($p['presentation']['time'] ?? '')
                    // Starts the schedule text with the date and time.
                    . (!empty($p['presentation']['venue']) ? ', ' . $p['presentation']['venue'] : '')
                    // Appends the venue when the coordinator set one.
                    . (!empty($p['presentation']['panel']) ? ' | Panel: ' . $p['presentation']['panel'] : ''))
                // Appends the panel names when they are known.
                : 'Coordinator schedules the date, time, venue, and panel',
        // Otherwise explains that the coordinator schedules the presentation.
        ],
        // Closes the presentation detail expression.
        [
            // Closes the presentation milestone entry.
            'key'    => 'result',
            // Starts the presentation result milestone entry.
            'label'  => 'Presentation Conducted & Result Recorded',
            // Sets the key for the result milestone.
            'state'  => $p['result'] ?? ['status' => 'pending'],
            // Labels the step as the conducted presentation and recorded result.
            'coord'  => true,
            // Uses the result state, defaulting to pending.
            'detail' => (($p['result']['value'] ?? '') === 'approved'
                // Marks result recording as the coordinator's task.
                ? 'Approved — proceed to the next stage'
                // Checks whether the result is approved.
                : (($p['result']['value'] ?? '') === 'revision'
                    // Tells the student to proceed to the next stage.
                    ? 'Revision required — resubmit the updated paper'
                    // Checks whether the result asks for a revision.
                    : 'Awaiting presentation')),
        // Tells the student to resubmit the updated paper.
        ],
    // Otherwise says the presentation is still awaited.
    ];
// Closes the result entry and the list of milestone steps.
}

/**
 * How many milestones of a stage are complete, and the index of the first
 * milestone that is still outstanding (used to decide which steps may still
 * be uploaded by the student).
 */
// Counts finished milestones and finds the first unfinished one.
function milestoneProgress(array $steps): array {
    // Starts the count of finished milestones at zero.
    $done = 0;
    // Starts the first pending index at zero.
    $firstPending = 0;
    // Loops through the milestones in order.
    foreach ($steps as $i => $step) {
        // Checks whether this milestone is done.
        if (($step['state']['status'] ?? 'pending') === 'done') {
            // Adds one to the finished count.
            $done++;
            // Moves the first pending index past this finished milestone.
            $firstPending = $i + 1;
        // Handles the first milestone that is not done.
        } else {
            // Remembers this index as the first pending milestone.
            $firstPending = $i;
            // Stops at the first unfinished milestone.
            break;
        // Closes the done check.
        }
    // Closes the loop over milestones.
    }
    // Returns the done count, the total, and the first pending index.
    return ['done' => $done, 'total' => count($steps), 'firstPending' => $firstPending];
// Ends milestoneProgress.
}

/**
 * The milestone progress of a student in the stage they are currently working
 * on: the same "N of N milestones complete" the Milestone Tracker inside
 * Applications -> Edit shows.
 *
 * The Coordinator's Applications list derives its progress bar from this, so
 * the list and the tracker can never disagree, and every track (thesis,
 * capstone, seminar paper) is measured with its own milestone sequence. The
 * numbers follow the workflow live: approving a stage or verifying a
 * document changes them on the next list load, because they are computed
 * from the stored state and never kept as a copy.
 *
 * @return array{done:int,total:int,stageKey:string,stageLabel:string}
 */
// Computes the milestone progress of the student's current stage.
function studentMilestoneProgress(string $email, string $track): array {
    // Reads the stage list of the student's track.
    $stages = getWorkflow($track)['stages'];
    // Finds the index of the stage the student is working on.
    $currentIdx = workflowCurrentIndex(getStudentProgress($email, $track), $stages);
    // Reads the key of the current stage.
    $stageKey = (string) ($stages[$currentIdx]['key'] ?? '');
    // Reads the label of the current stage.
    $stageLabel = (string) ($stages[$currentIdx]['label'] ?? '');
    // Counts the milestones of that stage.
    $stats = milestoneProgress(stageMilestoneSteps($email, $track, $stageKey));
    // Returns the progress numbers and the stage details.
    return [
        // Casts the finished milestone count to an integer.
        'done'       => (int) ($stats['done'] ?? 0),
        // Casts the total milestone count to an integer.
        'total'      => (int) ($stats['total'] ?? 0),
        // Returns the current stage key.
        'stageKey'   => $stageKey,
        // Returns the current stage label.
        'stageLabel' => $stageLabel,
    // Closes the returned progress array.
    ];
// Ends studentMilestoneProgress.
}

/**
 * The milestone steps of one stage as the student sees them.
 *
 * The coordinator's Applications -> Edit page calls this too, so both sides
 * always show the same steps, in the same order, with the same states: the
 * tracker is computed from the stored applications and documents, never from a
 * second copy of the rules.
 */
// Builds the milestone steps of one stage for a student.
function stageMilestoneSteps(string $email, string $track, string $stageKey): array {
    // Loops through the student's stored stage progress rows.
    foreach (getStudentProgress($email, $track) as $row) {
        // Checks whether this row belongs to the requested stage.
        if (($row['stageKey'] ?? '') === $stageKey) {
            // Returns the milestone steps built from that row.
            return milestoneStepsForStage($row, $track, $stageKey);
        // Closes the stage match check.
        }
    // Closes the progress loop.
    }

    // Notes the fallback used when the stage has no application yet.
    // No application for this stage yet: show the empty workflow of the stage.
    // Loops through the stage definitions of the track.
    foreach (getWorkflow($track)['stages'] as $stage) {
        // Checks whether this definition matches the stage key.
        if (($stage['key'] ?? '') === $stageKey) {
            // Returns the steps of an empty stage workflow.
            return milestoneStepsForStage(blankStageProgress($stage), $track, $stageKey);
        // Closes the stage match check.
        }
    // Closes the stage definition loop.
    }
    // Returns no steps when the stage is unknown.
    return [];
// Ends stageMilestoneSteps.
}

/**
 * The best upload of a given kind (paper | adviser_endorsement | receipt |
 * grad_school_endorsement): a verified one wins, otherwise the most recent
 * upload of that kind.
 */
/**
 * Whether the official payment of an application counts as recorded.
 *
 * The receipt file the student sent is what the coordinator reviews, so it
 * decides: a file that was sent back has to be sent again. Without a file
 * the payment the coordinator recorded counts.
 */
// Checks whether an application's official payment counts as recorded.
function paymentIsRecorded(array $app, array $uploads = []): bool {
    // Finds the newest receipt upload of the application.
    $receipt = latestUploadOfKind($uploads, 'receipt');
    // Checks whether a receipt file was uploaded.
    if ($receipt) {
        // Uses the receipt status, verified or approved, as the answer.
        return in_array((string) ($receipt['status'] ?? ''), ['verified', 'approved'], true);
    // Closes the receipt upload check.
    }
    // Falls back to the payment details the coordinator recorded.
    return !empty($app['paymentRecorded']) || trim((string) ($app['receiptNumber'] ?? '')) !== '';
// Ends paymentIsRecorded.
}

/**
 * The document of a kind that counts.
 *
 * It is the newest file of that kind, the same one the coordinator sees in
 * the list of documents and the same one that decides the milestone.
 */
// Returns the document of a kind that decides the milestone.
function bestUploadOfKind(array $uploads, string $kind): ?array {
    // Uses the newest upload of that kind.
    return latestUploadOfKind($uploads, $kind);
// Ends bestUploadOfKind.
}

/**
 * The automatic submission indicator of a student in the coordinator's
 * Applications menu.
 *
 * It is deliberately NOT an application status: a student who has not applied
 * yet has no application at all, so there is nothing for the coordinator to
 * edit. The badge only says whether the student has submitted something.
 */
// Builds the submission badge for the coordinator's student list.
function submissionBadge(bool $hasApplication): string {
    // Checks whether the student already has an application.
    if ($hasApplication) {
        // Returns a Submitted badge when they do.
        return '<span class="status-badge status-submitted">Submitted</span>';
    // Closes the has-application check.
    }
    // Returns a Not Yet Submitted badge otherwise.
    return '<span class="status-badge status-not-submitted">Not Yet Submitted</span>';
// Ends submissionBadge.
}

/**
 * Every registered student together with the applications they have submitted.
 *
 * Students who have not applied yet are part of the result, so the coordinator
 * can see at a glance who is registered but has not submitted an application.
 * The applications of a student are kept together under the student, newest
 * first, so one student never appears twice because of several applications.
 */
// Lists every registered student with the applications they submitted.
function coordinatorStudentsWithApplications(): array {
    // Starts the list of students.
    $students = [];
    // Starts the applications grouped by user id.
    $applicationsByUser = [];

    // Only queries the database when the DB class is loaded.
    if (class_exists('DB')) {
        // Starts the block that may throw database errors.
        try {
            // Gets the shared database connection.
            $pdo = DB::getConnection();

            // Prepares the query for all registered students.
            $stmt = $pdo->prepare(
                // Selects the user and student details of each student.
                'SELECT u.user_id AS userId, u.full_name, u.email, u.created_at AS registeredAt,
                        s.student_id, s.first_name, s.last_name, s.middle_initial,
                        s.program, s.track, s.adviser_name, s.enrollment_date
                 FROM students s
                 INNER JOIN users u ON u.user_id = s.user_id
                 WHERE u.role = "student" AND s.archived_at IS NULL
                 ORDER BY s.last_name, s.first_name, s.middle_initial'
            // Closes the student select statement.
            );
            // Runs the student query.
            $stmt->execute();

            // Prepares the query for the applications of those students.
            $appStmt = $pdo->prepare(
                // Selects the application fields the coordinator list needs.
                'SELECT a.application_id AS id, a.user_id, a.presentation_stage AS stage,
                        a.paper_title AS title, a.status, a.submitted_at AS date,
                        a.result, a.coordinator_comment AS coordinatorComment,
                        a.grad_school_endorsed AS gradSchoolEndorsed,
                        a.payment_recorded AS paymentRecorded,
                        a.ready_for_presentation AS readyForPresentation,
                        a.receipt_number AS receiptNumber
                 FROM applications a
                 WHERE a.archived_at IS NULL
                 ORDER BY a.submitted_at DESC, a.application_id DESC'
            // Closes the application select statement.
            );
            // Runs the application query.
            $appStmt->execute();
            // Loops through every application row.
            foreach ($appStmt->fetchAll() as $application) {
                // Groups each application under its owner's user id.
                $applicationsByUser[(int) $application['user_id']][] = $application;
            // Closes the application loop.
            }

            // Loops through the student rows.
            foreach ($stmt->fetchAll() as $row) {
                // Reads the numeric user id of the student.
                $userId = (int) $row['userId'];
                // Works out the program code of the student.
                $programCode = programCodeForStudent($row);
                // Reads the track stored on the student record.
                $rawTrack = (string) ($row['track'] ?? '');
                // Checks whether the student has no track stored.
                if ($rawTrack === '') {
                    // Falls back to the default track of the program.
                    $rawTrack = getTrackForProgram($programCode);
                // Closes the missing track check.
                }
                // Turns the track key into its display label.
                $trackLabel = getTrackLabel($rawTrack);
                // Adds one student entry to the result list.
                $students[] = [
                    // Sets the user id of the student.
                    'userId'       => $userId,
                    // Sets the school student id.
                    'studentId'    => (string) ($row['student_id'] ?? ''),
                    // Builds the student's display name.
                    'name'         => studentDisplayName($row),
                    // Sets the student's email address.
                    'email'        => (string) ($row['email'] ?? ''),
                    // Sets the short program code.
                    'program'      => $programCode,
                    // Sets the full program name.
                    'programName'  => PROGRAMS[$programCode] ?? (string) ($row['program'] ?? ''),
                    // Sets the raw track key.
                    'track'        => $rawTrack,
                    // Sets the track display label.
                    'trackLabel'   => $trackLabel,
                    // Sets the adviser name.
                    'adviser'      => (string) ($row['adviser_name'] ?? ''),
                    // Sets the enrollment date.
                    'enrolled'     => (string) ($row['enrollment_date'] ?? ''),
                    // Sets the date the account was registered.
                    'registeredAt' => (string) ($row['registeredAt'] ?? ''),
                    // Notes that each application carries its program and track.
                    // Every application carries the program and the track of the
                    // Explains why the Applications menu can filter by program.
                    // student it belongs to, so the Applications menu can filter
                    // Continues the note about filtering application rows.
                    // on the program of a single application row.
                    // Maps each application to add the program code and track label.
                    'applications' => array_map(static function (array $application) use ($programCode, $trackLabel) {
                        // Sets the program code on the application row.
                        $application['program'] = $programCode;
                        // Sets the track label on the application row.
                        $application['track'] = $trackLabel;
                        // Returns the updated application row.
                        return $application;
                    // Runs the mapping over the student's applications, or none.
                    }, $applicationsByUser[$userId] ?? []),
                // Closes the student entry array.
                ];
            // Closes the student loop.
            }
        // Catches any database error.
        } catch (Throwable $e) {
            // Returns an empty list when the query fails.
            return [];
        // Closes the try/catch block.
        }
    // Closes the DB availability check.
    }

    // Returns the collected students.
    return $students;
// Ends coordinatorStudentsWithApplications.
}

/**
 * Why a coordinator may not move an application to a status yet, or null when
 * the move is allowed.
 *
 * The status has to agree with what the student has actually done, so a
 * record cannot claim to be ready, scheduled or finished while the milestone
 * workflow says otherwise.
 */
/**
 * Why a status may not be saved.
 *
 * $statusChanged tells whether the coordinator is actually moving the
 * student to this status. When they are only working on a document the
 * status stays where it is, and the requirements of a status they are not
 * choosing may not block them: sending a document back for a revision
 * would otherwise be impossible while the student is marked as ready.
 *
 * Returns null when the status may be saved.
 */
// Explains why a status cannot be saved yet, or null when it can.
function applicationStatusBlockedReason(string $status, array $milestoneSteps, string $result = '', bool $statusChanged = true): ?string {
    // Allows the early statuses that need no milestone checks.
    if ($status === '' || $status === 'submitted' || $status === 'under_review') {
        // Returns null so the status may be saved.
        return null;
    // Closes the early status check.
    }

    // Starts a map of milestone key to state.
    $state = [];
    // Loops through the milestone steps.
    foreach ($milestoneSteps as $step) {
        // Stores each milestone status by its key.
        $state[$step['key'] ?? ''] = $step['state']['status'] ?? 'pending';
    // Closes the state map loop.
    }

    // Checks whether the coordinator chose ready for presentation.
    if ($status === 'ready_for_presentation') {
        // Checks whether the status itself is not being changed.
        if (!$statusChanged) {
            // Notes that readiness is only checked on a real status move.
            // The student is not being moved to this status now, so the
            // Explains that documents are not blocked while editing.
            // readiness is only checked when they are moved to it.
            // Returns null so the document action is allowed.
            return null;
        // Closes the unchanged status check.
        }
        // Requires a verified paper and adviser endorsement first.
        if (($state['paper'] ?? 'pending') !== 'done' || ($state['adviser_endorsement'] ?? 'pending') !== 'done') {
            // Returns the reason when either document is not verified.
            return 'The student cannot be marked as Ready for Presentation before the paper and the signed adviser endorsement form are uploaded and verified.';
        // Closes the paper and endorsement check.
        }
        // Allows the ready status when the documents are verified.
        return null;
    // Closes the ready-for-presentation branch.
    }

    // Checks whether the coordinator chose scheduled.
    if ($status === 'scheduled') {
        // Requires a scheduled presentation first.
        if (($state['presentation'] ?? 'pending') !== 'done') {
            // Returns the reason when no presentation is scheduled.
            return 'The student cannot be marked as Scheduled for Presentation before the presentation has been scheduled.';
        // Closes the presentation check.
        }
        // Allows the scheduled status.
        return null;
    // Closes the scheduled branch.
    }

    // Checks whether the coordinator chose completed.
    if ($status === 'completed') {
        // Accepts a typed result or a done result milestone.
        $recorded = trim($result) !== '' || ($state['result'] ?? 'pending') === 'done';
        // Checks whether no result was recorded.
        if (!$recorded) {
            // Returns the reason when the result is missing.
            return 'The student cannot be marked as Presentation Complete before the presentation result is recorded.';
        // Closes the result check.
        }
        // Allows the completed status.
        return null;
    // Closes the completed branch.
    }

    // Returns null for any other status.
    return null;
// Ends applicationStatusBlockedReason.
}

/**
 * The application draft of a student.
 *
 * The Application form is backed by this row so the values the student typed
 * survive a trip through the Dashboard, a refresh, or a logout and login. One
 * draft per student, so nothing leaks between students.
 *
 * Returns null when the student has no draft.
 */
// Loads the saved application draft of one student.
function loadApplicationDraft(int $userId): ?array {
    // Skips the query for an invalid user or missing DB class.
    if ($userId <= 0 || !class_exists('DB')) {
        // Returns null when there is nothing to load.
        return null;
    // Closes the guard check.
    }
    // Starts the block that may throw database errors.
    try {
        // Prepares the draft select query.
        $stmt = DB::getConnection()->prepare(
            // Selects the draft fields the form restores.
            'SELECT title, adviser, abstract, enroll_date, stage_key, doc_type, file_name,
                    receipt_number, payment_amount, payment_date, updated_at
             FROM application_drafts
             WHERE user_id = :user_id
             LIMIT 1'
        // Closes the draft select statement.
        );
        // Runs the query with the student's user id.
        $stmt->execute(['user_id' => $userId]);
        // Fetches the draft row.
        $row = $stmt->fetch();
        // Returns the row, or null when no draft exists.
        return $row ?: null;
    // Catches any database error.
    } catch (Throwable $e) {
        // Returns null when the draft cannot be read.
        return null;
    // Closes the try/catch block.
    }
// Ends loadApplicationDraft.
}

/**
 * Stores (or refreshes) the application draft of a student.
 *
 * An empty draft is removed instead of stored, so a cleared form does not
 * leave a stale draft behind.
 */
// Stores or refreshes the application draft of a student.
function saveApplicationDraft(int $userId, array $values): void {
    // Skips saving for an invalid user or missing DB class.
    if ($userId <= 0 || !class_exists('DB')) {
        // Returns without saving.
        return;
    // Closes the guard check.
    }
    // Starts the block that may throw database errors.
    try {
        // Gets the shared database connection.
        $pdo = DB::getConnection();
        // Trims the typed research title.
        $title       = trim((string) ($values['title'] ?? ''));
        // Trims the typed adviser name.
        $adviser     = trim((string) ($values['adviser'] ?? ''));
        // Trims the typed abstract.
        $abstract    = trim((string) ($values['abstract'] ?? ''));
        // Trims the typed enrollment date.
        $enrollDate  = trim((string) ($values['enroll_date'] ?? ''));
        // Trims the selected stage key.
        $stageKey    = trim((string) ($values['stage_key'] ?? ''));
        // Trims the selected document type.
        $docType     = trim((string) ($values['doc_type'] ?? ''));
        // Trims the chosen file name.
        $fileName    = trim((string) ($values['file_name'] ?? ''));
        // Trims the typed receipt number.
        $receiptNo   = trim((string) ($values['receipt_number'] ?? ''));
        // Trims the typed payment amount.
        $payAmount   = trim((string) ($values['payment_amount'] ?? ''));
        // Trims the typed payment date.
        $payDate     = trim((string) ($values['payment_date'] ?? ''));

        // Checks whether every draft field is empty after trimming.
        if ($title === '' && $adviser === '' && $abstract === '' && $enrollDate === '' && $docType === '' && $fileName === ''
            // Continues the empty check with the document and payment fields.
            && $receiptNo === '' && $payAmount === '' && $payDate === '') {
            // Removes the draft instead of storing an empty one.
            clearApplicationDraft($userId);
            // Returns after clearing the empty draft.
            return;
        // Closes the empty draft check.
        }

        // Prepares the insert that also updates an existing draft.
        $stmt = $pdo->prepare(
            // Inserts the draft columns for the student.
            'INSERT INTO application_drafts (user_id, title, adviser, abstract, enroll_date, stage_key, doc_type, file_name,
                                            receipt_number, payment_amount, payment_date)
             VALUES (:user_id, :title, :adviser, :abstract, :enroll_date, :stage_key, :doc_type, :file_name,
                     :receipt_number, :payment_amount, :payment_date)
             ON DUPLICATE KEY UPDATE
                title          = VALUES(title),
                adviser        = VALUES(adviser),
                abstract       = VALUES(abstract),
                enroll_date    = VALUES(enroll_date),
                stage_key      = VALUES(stage_key),
                doc_type       = VALUES(doc_type),
                file_name      = VALUES(file_name),
                receipt_number = VALUES(receipt_number),
                payment_amount = VALUES(payment_amount),
                payment_date   = VALUES(payment_date),
                updated_at     = CURRENT_TIMESTAMP'
        // Closes the draft insert statement.
        );
        // Runs the statement with the trimmed values.
        $stmt->execute([
            // Binds the user id.
            'user_id'        => $userId,
            // Binds the research title.
            'title'          => $title,
            // Binds the adviser name.
            'adviser'        => $adviser,
            // Binds the abstract.
            'abstract'       => $abstract,
            // Binds the enrollment date.
            'enroll_date'    => $enrollDate,
            // Binds the stage key.
            'stage_key'      => $stageKey,
            // Binds the document type.
            'doc_type'       => $docType,
            // Binds the file name.
            'file_name'      => $fileName,
            // Stores null instead of an empty receipt number.
            'receipt_number' => $receiptNo !== '' ? $receiptNo : null,
            // Stores the payment amount as a number, or null.
            'payment_amount' => $payAmount !== '' ? (float) $payAmount : null,
            // Stores the payment date, or null.
            'payment_date'   => $payDate !== '' ? $payDate : null,
        // Closes the bound values array.
        ]);
    // Catches any database error.
    } catch (Throwable $e) {
        // Logs the failure so the draft problem can be traced.
        error_log('CSITE: could not save the application draft — ' . $e->getMessage());
    // Closes the try/catch block.
    }
// Ends saveApplicationDraft.
}

/**
 * Clears the four unsent fields of the application draft of a student.
 *
 * "Discard Draft" only throws away what the student typed: the research
 * title, the adviser, the abstract and the enrolment date. Uploaded
 * documents, submitted applications and the student record are never
 * touched by this.
 */
// Clears the unsent fields of a student's application draft.
function discardApplicationDraftFields(int $userId): void {
    // Skips for an invalid user or missing DB class.
    if ($userId <= 0 || !class_exists('DB')) {
        // Returns without doing anything.
        return;
    // Closes the guard check.
    }
    // Starts the block that may throw database errors.
    try {
        // Prepares an update of the draft row.
        DB::getConnection()->prepare(
            // Empties the typed fields and the payment details.
            'UPDATE application_drafts
             SET title = "", adviser = "", abstract = "", enroll_date = "",
                 receipt_number = NULL, payment_amount = NULL, payment_date = NULL,
                 updated_at = CURRENT_TIMESTAMP
             WHERE user_id = :user_id'
        // Runs the update for the student.
        )->execute(['user_id' => $userId]);
    // Catches any database error.
    } catch (Throwable $e) {
        // Logs the failure so the discard problem can be traced.
        error_log('CSITE: could not discard the application draft — ' . $e->getMessage());
    // Closes the try/catch block.
    }
// Ends discardApplicationDraftFields.
}

/**
 * Removes the application draft of a student. Called after a successful
 * submission and when the student explicitly starts a new application.
 */
// Deletes the application draft of a student.
function clearApplicationDraft(int $userId): void {
    // Skips for an invalid user or missing DB class.
    if ($userId <= 0 || !class_exists('DB')) {
        // Returns without deleting.
        return;
    // Closes the guard check.
    }
    // Starts the block that may throw database errors.
    try {
        // Deletes the draft row of the student.
        DB::getConnection()->prepare('DELETE FROM application_drafts WHERE user_id = :user_id')
            // Runs the delete with the student's user id.
            ->execute(['user_id' => $userId]);
    // Catches any database error.
    } catch (Throwable $e) {
        // Logs the failure so the delete problem can be traced.
        error_log('CSITE: could not clear the application draft — ' . $e->getMessage());
    // Closes the try/catch block.
    }
// Ends clearApplicationDraft.
}

/**
 * The document types a student may pick for a stage of their track.
 *
 * The paper and the adviser endorsement come from the stage definition of the
 * track itself, so a thesis student, a capstone student and a seminar-paper
 * student each get their own wording. The revision, the receipt and the
 * Graduate School endorsement are the same for every track.
 */
// Lists the document types a student may pick for a stage.
function documentTypesForStage(array $stage, string $track, string $stageKey): array {
    // Reads the paper label, falling back to the stage short label.
    $paperLabel = $stage['documents'][0]['label'] ?? ($stage['shortLabel'] ?? 'Paper') . ' Paper';
    // Starts the type list with the paper.
    $types = [$paperLabel];
    // Checks whether the stage defines an adviser form label.
    if (!empty($stage['adviserEndorsementForm']['label'])) {
        // Adds the adviser endorsement form type.
        $types[] = $stage['adviserEndorsementForm']['label'];
    // Closes the adviser form check.
    }
    // Checks whether the stage defines a Graduate School form label.
    if (!empty($stage['gradSchoolEndorsementForm']['label'])) {
        // Adds the Graduate School endorsement type.
        $types[] = $stage['gradSchoolEndorsementForm']['label'];
    // Closes the Graduate School form check.
    }
    // Adds the official receipt type, which every track shares.
    $types[] = 'Official Receipt / Payment Proof';
    // Returns the unique, non-empty type list.
    return array_values(array_unique(array_filter($types)));
// Ends documentTypesForStage.
}

/**
 * The document types the student may upload now.
 *
 * The Milestone Tracker is the source of truth. The milestones of a stage are
 * followed in the order of the workflow: the first one that is not finished is
 * the work of the student now, and only its document type is open. Every type
 * behind it waits, until the milestones before it are done, so a document that
 * the coordinator sent back keeps the later documents locked. A milestone that
 * was sent back is open again, so the student can upload a replacement.
 *
 * The Document Type list of the Application page, the Upload buttons of the
 * Dashboard and the check of an upload all use this one rule, so they can
 * never disagree about what the student is allowed to send.
 *
 * @param  array<int, array<string, mixed>>  $milestoneSteps
 * @return array<int, string> the document types that are open
 */
// Lists the document types the student may upload now.
function openDocumentTypes(array $milestoneSteps): array {
    // Starts with no open document type.
    $open = [];
    // Loops through the milestone steps in order.
    foreach ($milestoneSteps as $step) {
        // Reads the document type of this milestone.
        $docType = trim((string) ($step['docType'] ?? ''));
        // Checks whether the milestone has no document type.
        if ($docType === '') {
            // Notes that coordinator-only milestones have nothing to upload.
            // A milestone of the coordinator without a document of the student.
            // Skips milestones with no document type.
            continue;
        // Closes the no-document check.
        }
        // Reads the milestone state, defaulting to pending.
        $state = (string) ($step['state']['status'] ?? 'pending');
        // Checks whether the milestone is already done.
        if ($state === 'done') {
            // Notes that a finished milestone needs no upload.
            // Finished: there is nothing left to upload.
            // Skips the finished milestone.
            continue;
        // Closes the done check.
        }
        // Notes that the first unfinished milestone is the current work.
        // The first milestone that is not finished is the work of the student
        // Explains that later milestones wait for this one.
        // now. Everything behind it waits for it.
        // Opens this document type for upload.
        $open[] = $docType;
        // Stops after the first open document type.
        break;
    // Closes the milestone loop.
    }
    // Returns the open document types.
    return $open;
// Ends openDocumentTypes.
}

/**
 * The stages of a track the student may send documents for.
 *
 * The stages are followed one after the other. A stage the student has not
 * reached yet is locked, so none of its document types are open: the workflow
 * of the track asks for one stage after the other.
 *
 * @param  array<int, array<string, mixed>>|array<string, array<string, mixed>>  $stages
 * @return array<string, bool> the stage key => the stage is open for uploads
 */
// Maps each stage of a track to whether uploads are open.
function openStagesForUploads(array $progress, array $stages): array {
    // Turns the stage list into a plain numbered list.
    $list = array_values($stages);
    // Works out the lock state of every stage.
    $locks = stageLockStates($list, workflowCurrentIndex($progress, $list));
    // Starts the stage key to open flag map.
    $open = [];
    // Loops through the stages in order.
    foreach ($list as $stage) {
        // Reads the key of this stage.
        $key = (string) ($stage['key'] ?? '');
        // Marks the stage open unless its lock says otherwise.
        $open[$key] = !($locks[$key]['locked'] ?? false);
    // Closes the stage loop.
    }
    // Returns the stage open flags.
    return $open;
// Ends openStagesForUploads.
}

/**
 * The document types that are open for a student, for every stage of a track.
 *
 * A stage the student has not reached yet stays locked, so it has no open
 * document type at all.
 *
 * @param  array<int, array<string, mixed>>|array<string, array<string, mixed>>  $stages
 * @return array<string, array<int, string>>
 */
// Maps every stage of a track to its open document types.
function openDocumentTypesByStage(string $email, string $track, array $stages, array $progress = []): array {
    // Works out stage locks, or none when no progress was passed.
    $stageOpen = $progress ? openStagesForUploads($progress, $stages) : [];
    // Starts the stage key to document types map.
    $open = [];
    // Loops through the stage keys of the track.
    foreach (array_keys($stages) as $stageKey) {
        // Casts the stage key to a string.
        $stageKey = (string) $stageKey;
        // Checks whether this stage is locked for uploads.
        if (isset($stageOpen[$stageKey]) && !$stageOpen[$stageKey]) {
            // Gives a locked stage no open document types.
            $open[$stageKey] = [];
            // Skips to the next stage.
            continue;
        // Closes the locked stage check.
        }
        // Lists the open document types of the unlocked stage.
        $open[$stageKey] = openDocumentTypes(stageMilestoneSteps($email, $track, $stageKey));
    // Closes the stage loop.
    }
    // Returns the open document types by stage.
    return $open;
// Ends openDocumentTypesByStage.
}

/**
 * The requirements a student has to meet to submit an application for a stage.
 *
 * The fields are always required. The document that has to be attached is the
 * paper of the stage, which follows the track: a thesis student attaches the
 * Thesis Proposal, a capstone student the Capstone Proposal, a seminar-paper
 * student the Seminar Paper Proposal.
 *
 * The adviser endorsement form, the official receipt and the Graduate School
 * endorsement are required by the track as well, but they belong to the later
 * steps of the workflow and never block the submission - the coordinator sees
 * them on the Milestone Tracker.
 *
 * Returns ['fields' => [...], 'documents' => [...]] with the labels of what is
 * still missing.
 */
// Lists the fields and documents still missing before submission.
function applicationSubmissionRequirements(array $stage, string $track, string $stageKey, array $values, array $uploads = []): array {
    // Starts the list of missing fields.
    $missingFields = [];
    // Checks whether the research title is empty.
    if (trim((string) ($values['title'] ?? '')) === '') {
        // Adds the title to the missing fields.
        $missingFields[] = 'Research / Paper Title';
    // Closes the title check.
    }
    // Checks whether the adviser name is empty.
    if (trim((string) ($values['adviser'] ?? '')) === '') {
        // Adds the adviser name to the missing fields.
        $missingFields[] = 'Adviser Name';
    // Closes the adviser check.
    }
    // Checks whether the abstract is empty.
    if (trim((string) ($values['abstract'] ?? '')) === '') {
        // Adds the abstract to the missing fields.
        $missingFields[] = 'Brief Description / Abstract';
    // Closes the abstract check.
    }
    // Checks whether the enrollment date is empty.
    if (trim((string) ($values['enroll_date'] ?? '')) === '') {
        // Adds the enrollment date to the missing fields.
        $missingFields[] = 'Enrollment / Start Date';
    // Closes the enrollment date check.
    }

    // Starts the list of missing documents.
    $missingDocuments = [];
    // Reads the paper label from the stage definition.
    $paperLabel = $stage['documents'][0]['label'] ?? '';
    // Checks whether the stage defines a paper label.
    if ($paperLabel !== '') {
        // Starts the list of uploaded document kinds.
        $uploadedKinds = [];
        // Loops through the student's uploads.
        foreach ($uploads as $upload) {
            // Records the kind of each uploaded document.
            $uploadedKinds[] = classifyUploadDocType((string) ($upload['docType'] ?? ''));
        // Closes the upload loop.
        }
        // Checks whether no paper was uploaded yet.
        if (!in_array('paper', $uploadedKinds, true)) {
            // Adds the paper label to the missing documents.
            $missingDocuments[] = $paperLabel;
        // Closes the paper upload check.
        }
    // Closes the paper label check.
    }

    // Returns the missing fields and missing documents.
    return ['fields' => $missingFields, 'documents' => $missingDocuments];
// Ends applicationSubmissionRequirements.
}

/**
 * The status badge of a document or requirement.
 *
 * The labels and the colours follow the Requirements Checklist the coordinator
 * has always seen:
 *   Pending      - the student has not uploaded it yet
 *   Submitted    - the student uploaded it, the coordinator still has to review it
 *   Under Review - it waits for the coordinator's own action
 *   Approved     - the coordinator accepted it
 *   Rejected     - it needs corrections or a resubmission
 *
 * Returns ['label' => ..., 'class' => ...].
 */
/**
 * Put the document actions of the coordinator on top of the milestone states.
 *
 * The states come from the documents that are stored. When the coordinator
 * picks a new action for a document, the milestone of that document follows
 * immediately, so the check of the status sees the change that is being
 * made instead of the one that was stored before.
 *
 * Only the milestone of the document that was touched is changed, so the
 * other milestones of the stage keep their own state.
 *
 * @param  array<int, array<string, mixed>>  $milestones
 * @param  array<string, string>             $actions  document id => action
 * @return array<int, array<string, mixed>>
 */
// Applies the coordinator's document actions to the milestone states.
function applyDocumentActionsToMilestones(array $milestones, array $actions): array {
    // Returns the milestones unchanged when either list is empty.
    if (!$milestones || !$actions) {
        // Returns early without changes.
        return $milestones;
    // Closes the empty check.
    }

    // Notes the table that maps a document kind to its milestone.
    // The kind of a document tells the milestone it belongs to.
    // Starts the document kind to milestone key map.
    $kindToStep = [
        // Maps the paper document to the paper milestone.
        'paper'                 => 'paper',
        // Maps the adviser endorsement document to its milestone.
        'adviser_endorsement'   => 'adviser_endorsement',
        // Maps the Graduate School endorsement document to its milestone.
        'grad_school_endorsement' => 'grad_school_endorsement',
        // Maps the receipt document to the payment milestone.
        'receipt'               => 'payment',
    // Closes the kind map.
    ];

    // Starts the milestone key to new state map.
    $states = [];
    // Loops through the chosen document actions.
    foreach ($actions as $uploadId => $action) {
        // Loads the upload the action belongs to.
        $upload = findUpload((string) $uploadId);
        // Checks whether the upload no longer exists.
        if (!$upload) {
            // Skips actions for missing uploads.
            continue;
        // Closes the missing upload check.
        }
        // Works out the document kind of the upload.
        $kind = classifyUploadDocType((string) ($upload['docType'] ?? ''));
        // Finds the milestone key for that kind.
        $step = $kindToStep[$kind] ?? '';
        // Checks whether the kind maps to no milestone.
        if ($step === '') {
            // Skips kinds that have no milestone.
            continue;
        // Closes the unmapped kind check.
        }
        // Stores the milestone state the action produces.
        $states[$step] = documentMilestoneState($action);
    // Closes the action loop.
    }
    // Checks whether no milestone state was produced.
    if (!$states) {
        // Returns the milestones unchanged.
        return $milestones;
    // Closes the no-state check.
    }

    // Loops through the milestones to apply the new states.
    foreach ($milestones as $index => $milestone) {
        // Reads the key of this milestone.
        $key = (string) ($milestone['key'] ?? '');
        // Checks whether this milestone has a new state.
        if (!isset($states[$key])) {
            // Skips milestones that were not touched.
            continue;
        // Closes the new state check.
        }
        // Sets the milestone status to the new state.
        $milestones[$index]['state']['status'] = $states[$key];
        // Sets the review value to the same state.
        $milestones[$index]['state']['review'] = $states[$key];
    // Closes the milestone loop.
    }

    // Notes that the coordinator review follows the document reviews.
    // The review of the coordinator follows the documents they looked at.
    // Reads the state of the first milestone, the paper.
    $paperState = $milestones[0]['state']['status'] ?? '';
    // Starts with no adviser endorsement state.
    $adviserState = null;
    // Looks for the adviser endorsement milestone.
    foreach ($milestones as $index => $milestone) {
        // Checks whether this milestone is the adviser endorsement.
        if (($milestone['key'] ?? '') === 'adviser_endorsement') {
            // Reads the adviser endorsement state.
            $adviserState = $milestones[$index]['state']['status'] ?? '';
        // Closes the adviser endorsement check.
        }
    // Closes the adviser lookup loop.
    }
    // Loops through the milestones to update the coordinator review.
    foreach ($milestones as $index => $milestone) {
        // Skips any milestone that is not the coordinator review.
        if (($milestone['key'] ?? '') !== 'coord_review') {
            // Moves on to the next milestone.
            continue;
        // Closes the review check.
        }
        // Flags the review when the paper or endorsement is flagged.
        if ($paperState === 'flagged' || $adviserState === 'flagged') {
            // Sets the review milestone to flagged.
            $milestones[$index]['state']['status'] = 'flagged';
        // Completes the review when both documents are verified.
        } elseif ($paperState === 'done' && $adviserState === 'done') {
            // Sets the review milestone to done.
            $milestones[$index]['state']['status'] = 'done';
        // Handles the case where documents still wait for review.
        } else {
            // Sets the review milestone back to pending.
            $milestones[$index]['state']['status'] = 'pending';
        // Closes the review state branches.
        }
    // Closes the review loop.
    }

    // Returns the milestones with the new states.
    return $milestones;
// Ends applyDocumentActionsToMilestones.
}

/**
 * The next step of a student, in the order of their workflow.
 *
 * The steps of the track are followed in order, so the next step is the first
 * one that is not finished yet. A step that waits for the coordinator is
 * named as well, so the student always knows where they stand.
 *
 * @return array{key:string,label:string,status:string,detail:string,coord:bool}|null
 */
// Returns the first milestone of a stage that is not finished.
function nextMilestoneStep(array $steps): ?array {
    // Loops through the milestone steps in order.
    foreach ($steps as $step) {
        // Reads the state of this step, defaulting to pending.
        $status = (string) ($step['state']['status'] ?? 'pending');
        // Checks whether the step is already done.
        if ($status === 'done') {
            // Skips the finished step.
            continue;
        // Closes the done check.
        }
        // Starts the returned summary of the next step.
        return [
            // Returns the step key.
            'key'    => (string) ($step['key'] ?? ''),
            // Returns the step label.
            'label'  => (string) ($step['label'] ?? ''),
            // Returns the step status.
            'status' => $status,
            // Returns the step detail text.
            'detail' => (string) ($step['detail'] ?? ''),
            // Returns whether the coordinator owns this step.
            'coord'  => (bool) ($step['coord'] ?? false),
        // Closes the returned step array.
        ];
    // Closes the step loop.
    }
    // Returns null when every step is done.
    return null;
// Ends nextMilestoneStep.
}

/**
 * The document status a milestone follows.
 *
 * submitted   the student sent it, the coordinator has not looked at it yet
 * verified    the coordinator approved it
 * incomplete  the coordinator needs something fixed
 * revision    the coordinator asks for a new version
 * pending     the document is not there at all
 */
// Turns a document status into the milestone state it means.
function documentMilestoneState(?string $status): string {
    // Normalises the status by trimming and lowercasing it.
    $status = strtolower(trim((string) $status));
    // Checks whether the coordinator verified the document.
    if ($status === 'verified' || $status === 'approved') {
        // Maps a verified document to the done state.
        return 'done';
    // Closes the verified check.
    }
    // Checks whether the document needs a fix or a new version.
    if ($status === 'incomplete' || $status === 'revision') {
        // Maps incomplete and revision documents to flagged.
        return 'flagged';
    // Closes the revision and incomplete check.
    }
    // Checks whether the document is only submitted.
    if ($status === 'submitted') {
        // Maps a submitted document to the submitted state.
        return 'submitted';
    // Closes the submitted check.
    }
    // Treats every other status as pending.
    return 'pending';
// Ends documentMilestoneState.
}

/**
 * The word the student and the coordinator see for a document status.
 *
 * @return array{label:string,class:string,action:string}
 */
// Builds the badge text, class, and action of a document status.
function documentStatusBadge(?array $upload): array {
    // Checks whether the student has no upload.
    if (!$upload) {
        // Returns the Pending badge when nothing was uploaded.
        return ['label' => 'Pending', 'class' => 'status-pending', 'action' => 'submitted'];
    // Closes the no-upload check.
    }
    // Reads the upload status in lowercase, defaulting to submitted.
    $status = strtolower(trim((string) ($upload['status'] ?? 'submitted')));
    // Checks whether the coordinator verified the document.
    if ($status === 'verified' || $status === 'approved') {
        // Returns the Verified badge.
        return ['label' => 'Verified', 'class' => 'status-approved', 'action' => 'verified'];
    // Closes the verified check.
    }
    // Checks whether the document was marked incomplete.
    if ($status === 'incomplete') {
        // Returns the Incomplete badge.
        return ['label' => 'Incomplete', 'class' => 'status-revision', 'action' => 'incomplete'];
    // Closes the incomplete check.
    }
    // Checks whether a revision was requested.
    if ($status === 'revision') {
        // Returns the Revision badge.
        return ['label' => 'Revision', 'class' => 'status-revision', 'action' => 'revision'];
    // Closes the revision check.
    }
    // Returns the Submitted badge for every other status.
    return ['label' => 'Submitted', 'class' => 'status-submitted', 'action' => 'submitted'];
// Ends documentStatusBadge.
}

/**
 * The status badge of a student-submitted requirement, with receipt/payment
 * records as the only coordinator-entered exception.
 */
// Builds the status badge of a requirement and its upload.
function requirementStatusBadge(array $requirement, ?array $upload, array $app = []): array {
    // Checks whether this is a receipt with no uploaded file.
    if (($requirement['kind'] ?? '') === 'receipt' && empty($upload)) {
        // Notes that payment may be recorded without a receipt file.
        // The payment can also be recorded without a receipt upload.
        // Checks for a recorded payment or a receipt number.
        return (!empty($app['paymentRecorded']) || trim((string) ($app['receiptNumber'] ?? '')) !== '')
            // Returns Verified when the coordinator recorded the payment.
            ? ['label' => 'Verified', 'class' => 'status-approved', 'action' => 'verified']
            // Returns Not Submitted when no payment was recorded.
            : ['label' => 'Not Submitted', 'class' => 'status-not-submitted', 'action' => 'submitted'];
    // Closes the receipt without upload branch.
    }
    // Checks whether the student has no upload at all.
    if (!$upload) {
        // Notes that the student has not sent this document yet.
        // The student has not sent this document yet.
        // Returns the Not Submitted badge.
        return ['label' => 'Not Submitted', 'class' => 'status-not-submitted', 'action' => 'submitted'];
    // Closes the no-upload check.
    }
    // Uses the normal document badge for an upload.
    return documentStatusBadge($upload);
// Ends requirementStatusBadge.
}

/**
 * The documents and requirements of a stage in one list.
 *
 * Every requirement of the track comes first; the ones the student already
 * uploaded carry the upload (file name, date, review status), the ones that
 * are still missing are listed as Pending. Uploads that are not one of the
 * requirements - a revision, for instance - are listed after them, so nothing
 * is hidden and nothing is listed twice.
 *
 * Returns a list of ['requirement' => array|null, 'upload' => array|null].
 */
// Merges the stage requirements with the student's uploads.
function documentsAndRequirements(array $requiredDocs, array $uploads, array $app = []): array {
    // Starts the list of result rows.
    $rows = [];
    // Starts the set of upload ids already listed.
    $used = [];

    // Loops through every required document of the stage.
    foreach ($requiredDocs as $requirement) {
        // Starts a block comment on how the newest file is matched.
        /*
         * The newest file of a requirement is the one that is reviewed, so a
         * file the student sent earlier never shows up a second time. Every
         * file of that kind belongs to the requirement, which keeps a
         * requirement from being listed twice.
         */
        // Finds the newest upload that matches this requirement.
        $matched = latestUploadOfKind($uploads, (string) $requirement['kind']);

        // Notes that every file of this kind belongs to the requirement.
        // Every file of that kind belongs to this requirement.
        // Loops through the uploads to mark the matched files.
        foreach ($uploads as $doc) {
            // Checks whether this upload has the requirement's kind.
            if (classifyUploadDocType((string) ($doc['docType'] ?? '')) === $requirement['kind']) {
                // Marks this upload id as already listed.
                $used[(string) $doc['id']] = true;
            // Closes the kind check.
            }
        // Closes the upload marking loop.
        }

        // Adds the requirement with its newest upload to the rows.
        $rows[] = ['requirement' => $requirement, 'upload' => $matched];
    // Closes the requirement loop.
    }

    // Notes that the remaining uploads are extra files.
    // Files that are not one of the requirements of this stage.
    // Loops through all uploads again.
    foreach ($uploads as $doc) {
        // Checks whether this upload was not matched to a requirement.
        if (!isset($used[(string) $doc['id']])) {
            // Adds the extra upload as a row without a requirement.
            $rows[] = ['requirement' => null, 'upload' => $doc];
        // Closes the unmatched upload check.
        }
    // Closes the extra upload loop.
    }

    // Returns the merged requirement and upload rows.
    return $rows;
// Ends documentsAndRequirements.
}

/**
 * One application with everything the coordinator needs to work on it.
 *
 * Returns null when the application does not exist or has been archived.
 */
// Loads one application with the details the coordinator needs.
function applicationById(int $id): ?array {
    // Rejects an invalid id or a missing DB class.
    if ($id <= 0 || !class_exists('DB')) {
        // Returns null when the application cannot be loaded.
        return null;
    // Closes the guard check.
    }
    // Starts the block that may throw database errors.
    try {
        // Starts the application select statement.
        $stmt = DB::getConnection()->prepare(
            // Begins the SQL query and its first column, the application id.
            "SELECT
                a.application_id AS id,
                u.email AS studentEmail,
                COALESCE(NULLIF(TRIM(CONCAT(s.last_name, ', ', s.first_name, IF(s.middle_initial IS NULL OR TRIM(REPLACE(s.middle_initial, '.', '')) = '', '', CONCAT(' ', TRIM(REPLACE(s.middle_initial, '.', '')))))), ''), u.full_name) AS student,
                CASE
                    WHEN s.program LIKE '%Computer Science%' THEN 'MSCS'
                    WHEN s.program LIKE '%Information Technology%' THEN 'MIT'
                    WHEN s.program LIKE '%Mathematics%' THEN 'MATH'
                    ELSE s.program
                END AS program,
                s.track,
                s.adviser_name AS adviser,
                a.presentation_stage AS stage,
                a.paper_title AS title,
                a.status,
                a.coordinator_comment AS coordinatorComment,
                a.grad_school_endorsed AS gradSchoolEndorsed,
                a.payment_recorded AS paymentRecorded,
                a.receipt_number AS receiptNumber,
                a.payment_date AS paymentDate,
                a.payment_amount AS paymentAmount,
                a.ready_for_presentation AS readyForPresentation,
                a.workflow_state AS workflowState,
                a.result AS result,
                a.submitted_at AS date
             FROM applications a
             INNER JOIN users u ON u.user_id = a.user_id
             LEFT JOIN students s ON s.user_id = u.user_id
             WHERE a.application_id = :id AND a.archived_at IS NULL
             LIMIT 1"
        // Closes the SQL query and prepares the statement.
        );
        // Runs the query with the application id.
        $stmt->execute(['id' => $id]);
        // Fetches the application row.
        $row = $stmt->fetch();
        // Checks whether no application was found.
        if (!$row) {
            // Returns null when the application is missing or archived.
            return null;
        // Closes the missing row check.
        }
        // Adds the stage key worked out from the stage label.
        $row['stageKey'] = stageKeyFromLabel((string) $row['stage']);
        // Decodes the stored workflow state JSON.
        $row['workflowState'] = json_decode((string) ($row['workflowState'] ?? '[]'), true) ?: [];
        // Returns the complete application row.
        return $row;
    // Catches any database error.
    } catch (Throwable $e) {
        // Returns null when the query fails.
        return null;
    // Closes the try/catch block.
    }
// Ends applicationById.
}

/**
 * The identity a coordinator sees next to an application: the student's full
 * name, the name of their program and their assigned adviser.
 *
 * It is read from the users and students records, not from an application row,
 * so every stage of the same student shows the same details even when that
 * stage's application row does not carry them.
 *
 * @return array{name: string, program: string, programCode: string, adviser: string}|null
 */
// Loads the student identity behind an email address.
function studentProfileForEmail(string $email): ?array {
    // Trims spaces from the email.
    $email = trim($email);
    // Checks for an empty email or a missing DB class.
    if ($email === '' || !class_exists('DB')) {
        // Returns null when there is nothing to look up.
        return null;
    // Closes the guard check.
    }
    // Starts the block that may throw database errors.
    try {
        // Starts the student select statement.
        $stmt = DB::getConnection()->prepare(
            // Selects the account name and the student details for the email.
            'SELECT u.full_name, u.email,
                    s.first_name, s.last_name, s.middle_initial,
                    s.program, s.track, s.adviser_name
             FROM users u
             LEFT JOIN students s ON s.user_id = u.user_id
             WHERE u.email = :email
             LIMIT 1'
        // Closes the student select statement.
        );
        // Runs the query with the email address.
        $stmt->execute(['email' => $email]);
        // Fetches the matching row.
        $row = $stmt->fetch();
        // Checks whether the email has no user record.
        if (!$row) {
            // Returns null when the email is unknown.
            return null;
        // Closes the missing row check.
        }
        // Works out the program code from the row.
        $programCode = programCodeForStudent($row);
        // Starts the identity returned to the caller.
        return [
            // Uses the student display name, or the account name.
            'name' => studentDisplayName($row) ?: (string) ($row['full_name'] ?? ''),
            // Uses the program name, or the stored program text.
            'program' => PROGRAMS[$programCode] ?? trim((string) ($row['program'] ?? '')),
            // Returns the short program code.
            'programCode' => $programCode,
            // Returns the adviser name.
            'adviser' => trim((string) ($row['adviser_name'] ?? '')),
            // Returns the track key.
            'track' => trim((string) ($row['track'] ?? '')),
        // Closes the returned identity array.
        ];
    // Catches any database error.
    } catch (Throwable $e) {
        // Returns null when the lookup fails.
        return null;
    // Closes the try/catch block.
    }
// Ends studentProfileForEmail.
}

/**
 * Every non-archived application of a student, newest first.
 */
// Lists a student's non-archived applications, newest first.
function applicationsForStudent(string $email): array {
    // Checks for an empty email or a missing DB class.
    if ($email === '' || !class_exists('DB')) {
        // Returns an empty list when there is nothing to query.
        return [];
    // Closes the guard check.
    }
    // Starts the block that may throw database errors.
    try {
        // Starts the application select statement.
        $stmt = DB::getConnection()->prepare(
            // Selects the application fields for the student email.
            'SELECT a.application_id AS id, a.user_id, a.presentation_stage AS stage, a.paper_title AS title,
                    a.status, a.submitted_at AS date, a.result,
                    a.coordinator_comment AS coordinatorComment,
                    a.grad_school_endorsed AS gradSchoolEndorsed,
                    a.payment_recorded AS paymentRecorded,
                    a.receipt_number AS receiptNumber,
                    a.payment_date AS paymentDate,
                    a.payment_amount AS paymentAmount,
                    a.ready_for_presentation AS readyForPresentation,
                    a.workflow_state AS workflowState
             FROM applications a
             INNER JOIN users u ON u.user_id = a.user_id
             WHERE u.email = :email AND a.archived_at IS NULL
             ORDER BY a.submitted_at DESC, a.application_id DESC'
        // Closes the application select statement.
        );
        // Runs the query with the email address.
        $stmt->execute(['email' => $email]);
        // Starts the list of application rows.
        $rows = [];
        // Loops through the fetched application rows.
        foreach ($stmt->fetchAll() as $row) {
            // Casts the application id to an integer.
            $row['id'] = (int) $row['id'];
            // Adds the stage key worked out from the stage label.
            $row['stageKey'] = stageKeyFromLabel((string) $row['stage']);
            // Decodes the stored workflow state JSON.
            $row['workflowState'] = json_decode((string) ($row['workflowState'] ?? '[]'), true) ?: [];
            // Adds the application row to the list.
            $rows[] = $row;
        // Closes the application row loop.
        }
        // Returns the application rows.
        return $rows;
    // Catches any database error.
    } catch (Throwable $e) {
        // Returns an empty list when the query fails.
        return [];
    // Closes the try/catch block.
    }
// Ends applicationsForStudent.
}

/**
 * The application a student already has for a stage, if any.
 */
// Finds the application a student has for one stage.
function applicationForStage(string $email, string $stageKey): ?array {
    // Loops through the student's applications.
    foreach (applicationsForStudent($email) as $application) {
        // Checks whether this application is for the requested stage.
        if (($application['stageKey'] ?? '') === $stageKey) {
            // Returns the matching application.
            return $application;
        // Closes the stage match check.
        }
    // Closes the application loop.
    }
    // Returns null when the stage has no application.
    return null;
// Ends applicationForStage.
}

/**
 * What a student still has to hand over before a presentation may be
 * scheduled.
 *
 * The list follows the track (seminar paper, capstone or thesis) and the stage
 * the application is currently on, and it follows the same milestone workflow
 * the student sees: the paper and the adviser endorsement have to be uploaded
 * and verified, the student has to upload a Graduate School endorsement for
 * review, the coordinator has to mark the student ready, and the official
 * receipt has to be recorded.
 *
 * An empty array means every requirement is met and the presentation may be
 * scheduled.
 */
// Lists what a student must still submit before a presentation.
function applicationMissingRequirements(string $track, string $stageKey, array $uploads, array $app = []): array {
    // Starts the list of missing requirements.
    $missing = [];

    // Loops through the required documents of the stage.
    foreach (requiredDocumentsForStage($track, $stageKey) as $requirement) {
        // Notes that a receipt can be met by recorded payment.
        // A receipt can also be satisfied by payment details recorded by the coordinator.
        // Checks whether this requirement is the receipt.
        if ($requirement['kind'] === 'receipt') {
            // Skips the receipt because it is checked later.
            continue;
        // Closes the receipt check.
        }
        // Finds the best upload of this requirement kind.
        $document = bestUploadOfKind($uploads, $requirement['kind']);
        // Checks whether that upload was verified or approved.
        $verified = $document && in_array($document['status'] ?? '', ['verified', 'approved'], true);
        // Checks whether the requirement is not yet verified.
        if (!$verified) {
            // Starts the missing requirement entry.
            $missing[] = [
                // Sets the display label of the requirement.
                'label' => $requirement['label'],
                // Sets the document kind of the requirement.
                'kind'  => $requirement['kind'],
                // Chooses the reason based on the upload.
                'why'   => $document
                    // Explains that the file waits for coordinator review.
                    ? 'Uploaded but not yet verified by the coordinator'
                    // Explains that the student has not submitted it.
                    : 'Not submitted by the student',
            // Closes the missing requirement entry.
            ];
        // Closes the not-verified check.
        }
    // Closes the requirement loop.
    }

    // Starts a block comment on how the receipt is satisfied.
    /*
     * The official receipt is satisfied by a verified receipt upload. A
     * student who sent the receipt but whose file was sent back has to send
     * it again, so the review of the file decides. Without a file the
     * payment the coordinator recorded counts.
     */
    // Finds the newest receipt upload.
    $receiptDocument = bestUploadOfKind($uploads, 'receipt');
    // Checks whether the payment does not count as recorded.
    if (!paymentIsRecorded($app, $uploads)) {
        // Starts a missing entry for the official receipt.
        $missing[] = [
            // Sets the receipt requirement label.
            'label' => 'Official Payment / Receipt',
            // Sets the receipt document kind.
            'kind'  => 'receipt',
            // Chooses the receipt reason from the upload.
            'why'   => $receiptDocument
                // Explains that the file waits for coordinator review.
                ? 'Uploaded but not yet verified by the coordinator'
                // Explains that payment and receipt are both missing.
                : 'Payment not recorded and receipt not verified',
        // Closes the receipt missing entry.
        ];
    // Closes the payment check.
    }

    // Checks whether the coordinator has not marked readiness.
    if (empty($app['readyForPresentation'])) {
        // Starts a missing entry for ready for presentation.
        $missing[] = [
            // Sets the readiness requirement label.
            'label' => 'Ready for Presentation',
            // Sets the readiness requirement kind.
            'kind'  => 'ready_for_presentation',
            // Explains that the coordinator has not marked readiness.
            'why'   => 'The coordinator has not yet marked the student ready',
        // Closes the readiness missing entry.
        ];
    // Closes the readiness check.
    }

    // Returns the list of missing requirements.
    return $missing;
// Ends applicationMissingRequirements.
}

// Initialises the demo data store used by the prototype pages.
initPrototypeStore();
// Loads the current student profile into the demo variable.
$mockStudent = currentStudentProfile($mockStudent);

// Google Drive template sync and the update notifications it feeds.
// Loaded last so the helpers above are already available to them.
// Loads the Google Drive helper used by the template sync.
require_once __DIR__ . '/gdrive.php';
// Loads the template sync helper for template updates.
require_once __DIR__ . '/template_sync.php';
