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
        $csiteProjectRoot = str_replace('\\', '/', (string) realpath(dirname(__DIR__, 3)));
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
