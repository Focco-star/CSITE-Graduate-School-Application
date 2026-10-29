<?php
/* CSITE configuration module: Track workflows, required documents, database workflow loading, and paper library rows. */

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
