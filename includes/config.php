<?php


define('SITE_NAME', 'CSITE Graduate School Application');
define('SITE_SHORT', 'CSITE Graduate School Application');
define('SITE_TAGLINE', 'Capstone, Seminar Paper and Thesis Presentation Application System');
define('BASE_URL', '/CSITE-Graduate-School-Application');

define('COLOR_PRIMARY', '#060297');
define('COLOR_ACCENT', '#FFB82B');
define('COLOR_ACCENT_HOVER', '#e5a526');

define('STAGES_THESIS', [
    'concept'  => 'Concept Paper Presentation',
    'proposal' => 'Thesis Proposal Presentation',
    'final'    => 'Final Thesis Defense',
]);

define('STAGES_CAPSTONE', [
    'proposal' => 'Capstone Proposal Presentation',
    'final'    => 'Final Capstone Presentation',
]);

define('STAGES_SEMINAR', [
    'proposal' => 'Seminar Paper Proposal Presentation',
    'final'    => 'Final Seminar Paper Presentation',
]);

define('STATUSES', [
    'pending'                  => ['label' => 'Pending',                  'class' => 'status-pending'],
    'not_started'              => ['label' => 'Not Started',              'class' => 'status-pending'],
    'draft'                    => ['label' => 'Draft',                    'class' => 'status-pending'],
    'submitted'                => ['label' => 'Submitted',                'class' => 'status-submitted'],
    'under_review'             => ['label' => 'Under Review',             'class' => 'status-review'],
    'for_payment'              => ['label' => 'For Payment',              'class' => 'status-review'],
    'payment_recorded'         => ['label' => 'Payment Recorded',         'class' => 'status-approved'],
    'ready_for_presentation'   => ['label' => 'Ready for Presentation',   'class' => 'status-confirmed'],
    'scheduled'                => ['label' => 'Scheduled',                'class' => 'status-confirmed'],
    'approved'                 => ['label' => 'Approved',                 'class' => 'status-approved'],
    'confirmed'                => ['label' => 'Confirmed',                'class' => 'status-confirmed'],
    'requires_revision'        => ['label' => 'Requires Revision',        'class' => 'status-revision'],
    'completed'                => ['label' => 'Completed',                'class' => 'status-completed'],
    'verified'                 => ['label' => 'Verified',                 'class' => 'status-approved'],
    'incomplete'               => ['label' => 'Incomplete',               'class' => 'status-revision'],
]);

define('PROGRAMS', [
    'MSCS'      => 'Master of Science in Computer Science (Thesis)',
    'MIT'       => 'Master in Information Technology (Capstone)',
    'MATH'      => 'Master in Mathematics Education (Seminar Paper)',
    'MSED_CHEM' => 'Master in Science Education major in Chemistry (Capstone)',
    'MSED_GS'   => 'Master in Science Education major in General Science (Capstone)',
    'MSED_BIO'  => 'Master in Science Education major in Biology (Capstone)',
    'MSED_PHY'  => 'Master in Science Education major in Physics (Capstone)',
    'MLIS'      => 'Master in Library and Information System (Capstone)',
]);

$mockStudent = [
    'id'           => '2024-001',
    'name'         => 'Torres, Robbie Ryan A',
    'email'        => 'rtorres@adzu.edu.ph',
    'program'      => 'MSCS',
    'program_name' => 'Master of Science in Computer Science',
    'track'        => 'thesis',
    'enroll_date'  => '2024-08-15',
    'current_stage'=> 'proposal',
    'status'       => 'under_review',
    'title'        => 'AI-Powered Academic Advising System for Graduate Students',
    'adviser'      => 'Dr. Maria Santos',
    'completion_deadline' => '2027-08-15',
];

$mockCoordinator = [
    'id'   => 'GPC-001',
    'name' => 'Opinion, Precious',
    'role' => 'Graduate Program Coordinator – CSITE',
    'email'=> 'gpc-csite@adzu.edu.ph',
];

function statusBadge(string $status): string {
    $info = STATUSES[$status] ?? ['label' => ucfirst($status), 'class' => 'status-pending'];
    return '<span class="status-badge ' . $info['class'] . '">' . htmlspecialchars($info['label']) . '</span>';
}

function getStagesForTrack(string $track): array {
    $fallback = STAGES_THESIS;
    if ($track === 'capstone') {
        $fallback = STAGES_CAPSTONE;
    } elseif ($track === 'seminar') {
        $fallback = STAGES_SEMINAR;
    }

    if (!class_exists('DB')) {
        return $fallback;
    }

    try {
        $stmt = DB::getConnection()->prepare(
            'SELECT workflow_stages.stage_key, workflow_stages.stage_label
             FROM workflow_stages
             INNER JOIN tracks ON tracks.track_id = workflow_stages.track_id
             WHERE tracks.track_code = :track
             ORDER BY workflow_stages.stage_order, workflow_stages.stage_id'
        );
        $stmt->execute(['track' => $track]);
        $stages = [];
        foreach ($stmt->fetchAll() as $stage) {
            $stages[$stage['stage_key']] = $stage['stage_label'];
        }
        return $stages ?: $fallback;
    } catch (Throwable $e) {
        return $fallback;
    }
}

function getTrackForProgram(string $program): string {
    $trimmed = trim($program);

    if ($trimmed !== '' && class_exists('DB')) {
        try {
            $stmt = DB::getConnection()->prepare(
                'SELECT tracks.track_code
                 FROM programs
                 INNER JOIN tracks ON tracks.track_id = programs.track_id
                 WHERE programs.program_code = :program
                    OR programs.program_name = :program
                    OR programs.program_name LIKE :program_like
                 LIMIT 1'
            );
            $stmt->execute([
                'program' => $trimmed,
                'program_like' => '%' . $trimmed . '%',
            ]);
            $databaseTrack = $stmt->fetchColumn();
            if (is_string($databaseTrack) && $databaseTrack !== '') {
                return $databaseTrack;
            }
        } catch (Throwable $e) {
            // Keep the existing program mapping when the new tables are unavailable.
        }
    }

    if (stripos($trimmed, 'capstone') !== false) {
        return 'capstone';
    }
    if (stripos($trimmed, 'seminar') !== false) {
        return 'seminar';
    }
    if (stripos($trimmed, 'thesis') !== false) {
        return 'thesis';
    }
    $capstone = ['MIT', 'MSED_CHEM', 'MSED_GS', 'MSED_BIO', 'MSED_PHY', 'MLIS'];
    if (in_array($trimmed, $capstone, true)) {
        return 'capstone';
    }
    if ($trimmed === 'MATH') {
        return 'seminar';
    }
    return 'thesis';
}

function getTrackLabel(string $track): string {
    if ($track === 'capstone') return 'Capstone';
    if ($track === 'seminar') return 'Seminar Paper';
    return 'Thesis';
}

function navActive(string $page, string $current): string {
    return $page === $current ? 'active' : '';
}

function asset(string $path): string {
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

function url(string $path): string {
    return BASE_URL . '/' . ltrim($path, '/');
}

function papersUrl(string $folder, string $file): string {
    if ($folder === '' || $file === '') {
        return '';
    }
    return asset('papers/' . rawurlencode($folder) . '/' . rawurlencode($file));
}

function getWorkflow(string $track): array {
    $T = 'THESIS';
    $C = 'CAPSTONE';
    $S = 'SEMINAR PAPER';

    $thesis = [
        'stages' => [
            [
                'key' => 'concept',
                'label' => 'Concept Paper',
                'shortLabel' => 'Concept Paper',
                'documents' => [
                    ['id' => 'concept_paper', 'label' => 'Concept Paper', 'file' => 'GRADUATE SCHOOL - CSITE - THESIS FORMAT.docx', 'folder' => $T, 'type' => 'template', 'description' => 'Written concept paper following CSITE thesis format'],
                    ['id' => 'concept_receipt', 'label' => 'Official Receipt / Payment Proof', 'file' => '', 'folder' => '', 'type' => 'receipt', 'description' => 'Scanned copy of official receipt from Graduate School'],
                ],
                'adviserEndorsementForm' => ['id' => 'concept_adviser', 'label' => 'Concept Paper Adviser Endorsement Form', 'file' => '1 CONCEPT PAPER ADVISER ENDORSEMENT FORM_.docx', 'folder' => $T, 'type' => 'form', 'description' => 'Signed by your research adviser'],
                'gradSchoolEndorsementForm' => ['id' => 'concept_gradschool', 'label' => 'Concept Paper Endorsement to Graduate School', 'file' => '2 CONCEPT PAPER ENDORSEMENT TO GRADSCHOOL.docx', 'folder' => $T, 'type' => 'form', 'description' => 'Issued by the coordinator to the Graduate School'],
            ],
            [
                'key' => 'proposal',
                'label' => 'Thesis Proposal',
                'shortLabel' => 'Proposal',
                'documents' => [
                    ['id' => 'proposal_paper', 'label' => 'Thesis Proposal', 'file' => 'GRADUATE SCHOOL - CSITE - THESIS FORMAT.docx', 'folder' => $T, 'type' => 'template', 'description' => 'Thesis proposal following CSITE thesis format'],
                    ['id' => 'proposal_receipt', 'label' => 'Official Receipt / Payment Proof', 'file' => '', 'folder' => '', 'type' => 'receipt', 'description' => 'Scanned copy of official receipt from Graduate School'],
                ],
                'adviserEndorsementForm' => ['id' => 'proposal_adviser', 'label' => 'Thesis Proposal Adviser Endorsement Form', 'file' => '3 THESIS PROPOSAL ADVISER ENDORSEMENT FORM.docx', 'folder' => $T, 'type' => 'form', 'description' => 'Signed by your research adviser'],
                'gradSchoolEndorsementForm' => ['id' => 'proposal_gradschool', 'label' => 'Thesis Proposal Endorsement to Graduate School', 'file' => '4 THESIS PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'folder' => $T, 'type' => 'form', 'description' => 'Issued by the coordinator to the Graduate School'],
            ],
            [
                'key' => 'final',
                'label' => 'Final Thesis',
                'shortLabel' => 'Final Thesis',
                'documents' => [
                    ['id' => 'final_paper', 'label' => 'Final Thesis', 'file' => 'GRADUATE SCHOOL - CSITE - THESIS FORMAT.docx', 'folder' => $T, 'type' => 'template', 'description' => 'Complete final thesis following CSITE format'],
                    ['id' => 'final_receipt', 'label' => 'Official Receipt / Payment Proof', 'file' => '', 'folder' => '', 'type' => 'receipt', 'description' => 'Scanned copy of official receipt from Graduate School'],
                ],
                'adviserEndorsementForm' => ['id' => 'final_adviser', 'label' => 'Final Thesis Adviser Endorsement Form', 'file' => '5 FINAL THESIS ADVISER ENDORSEMENT FORM.docx', 'folder' => $T, 'type' => 'form', 'description' => 'Signed by your research adviser'],
                'gradSchoolEndorsementForm' => ['id' => 'final_gradschool', 'label' => 'Final Thesis Endorsement to Graduate School', 'file' => '6 FINAL THESIS ENDORSEMENT TO GRADSCHOOL.docx', 'folder' => $T, 'type' => 'form', 'description' => 'Issued by the coordinator to the Graduate School'],
            ],
        ],
        'refs' => [
            ['id' => 'thesis_format', 'label' => 'Graduate School Thesis Format', 'file' => 'GRADUATE SCHOOL - CSITE - THESIS FORMAT.docx', 'folder' => $T, 'type' => 'template', 'description' => 'Official format guide for all thesis documents'],
            ['id' => 'thesis_cover', 'label' => 'Final Thesis Cover Page', 'file' => 'FINAL THESIS COVER PAGE v2026.docx', 'folder' => $T, 'type' => 'template', 'description' => 'Cover page template (v2026)'],
        ],
    ];

    $capstone = [
        'stages' => [
            [
                'key' => 'proposal',
                'label' => 'Capstone Proposal',
                'shortLabel' => 'Proposal',
                'documents' => [
                    ['id' => 'cp_proposal', 'label' => 'Capstone Proposal', 'file' => 'MIT GradSchool CAPSTONE PROJECT PROPOSAL TEMPLATE v2025.docx', 'folder' => $C, 'type' => 'template', 'description' => 'Capstone proposal following MIT template (v2025)'],
                    ['id' => 'cp_receipt', 'label' => 'Official Receipt / Payment Proof', 'file' => '', 'folder' => '', 'type' => 'receipt', 'description' => 'Scanned copy of official receipt from Graduate School'],
                ],
                'adviserEndorsementForm' => ['id' => 'cp_adviser', 'label' => 'Capstone Adviser Endorsement Form – Proposal', 'file' => '1 CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'folder' => $C, 'type' => 'form', 'description' => 'Signed by your capstone adviser'],
                'gradSchoolEndorsementForm' => ['id' => 'cp_gradschool', 'label' => 'Capstone Proposal Endorsement to Graduate School', 'file' => '2 CAPSTONE PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'folder' => $C, 'type' => 'form', 'description' => 'Issued by the coordinator to the Graduate School'],
            ],
            [
                'key' => 'final',
                'label' => 'Final Capstone',
                'shortLabel' => 'Final Capstone',
                'documents' => [
                    ['id' => 'cf_paper', 'label' => 'Final Capstone', 'file' => 'MIT GradSchool CAPSTONE PROJECT PROPOSAL TEMPLATE v2025.docx', 'folder' => $C, 'type' => 'template', 'description' => 'Final capstone project document'],
                    ['id' => 'cf_receipt', 'label' => 'Official Receipt / Payment Proof', 'file' => '', 'folder' => '', 'type' => 'receipt', 'description' => 'Scanned copy of official receipt from Graduate School'],
                ],
                'adviserEndorsementForm' => ['id' => 'cf_adviser', 'label' => 'Final Capstone Adviser Endorsement Form', 'file' => '3 FINAL CAPSTONE ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'folder' => $C, 'type' => 'form', 'description' => 'Signed by your capstone adviser'],
                'gradSchoolEndorsementForm' => ['id' => 'cf_gradschool', 'label' => 'Final Capstone Endorsement to Graduate School', 'file' => '4 FINAL CAPSTONE ENDORSEMENT TO GRADSCHOOL.docx', 'folder' => $C, 'type' => 'form', 'description' => 'Issued by the coordinator to the Graduate School'],
            ],
        ],
        'refs' => [
            ['id' => 'cap_template', 'label' => 'Capstone Project Proposal Template', 'file' => 'MIT GradSchool CAPSTONE PROJECT PROPOSAL TEMPLATE v2025.docx', 'folder' => $C, 'type' => 'template', 'description' => 'Official MIT capstone proposal template (v2025)'],
            ['id' => 'cap_guidelines', 'label' => 'Capstone Guidelines', 'file' => 'MIT Capstone Guidelines.docx', 'folder' => $C, 'type' => 'template', 'description' => 'MIT capstone project guidelines'],
            ['id' => 'cap_cover', 'label' => 'Final Capstone Cover Page', 'file' => 'FINAL CAPSTONE COVER PAGE v2026.docx', 'folder' => $C, 'type' => 'template', 'description' => 'Cover page template (v2026)'],
        ],
    ];

    $seminar = [
        'stages' => [
            [
                'key' => 'proposal',
                'label' => 'Seminar Paper Proposal',
                'shortLabel' => 'Proposal',
                'documents' => [
                    ['id' => 'sp_proposal', 'label' => 'Seminar Paper Proposal', 'file' => 'MATH SEMINAR PAPER TEMPLATE FORMAT.docx', 'folder' => $S, 'type' => 'template', 'description' => 'Seminar paper proposal following MATH template format'],
                    ['id' => 'sp_receipt', 'label' => 'Official Receipt / Payment Proof', 'file' => '', 'folder' => '', 'type' => 'receipt', 'description' => 'Scanned copy of official receipt from Graduate School'],
                ],
                'adviserEndorsementForm' => ['id' => 'sp_adviser', 'label' => 'Seminar Paper Proposal Adviser Endorsement Form', 'file' => '1 SEMINAR PAPER PROPOSAL ADVISER ENDORSEMENT FORM .docx', 'folder' => $S, 'type' => 'form', 'description' => 'Signed by your research adviser'],
                'gradSchoolEndorsementForm' => ['id' => 'sp_gradschool', 'label' => 'Seminar Paper Proposal Endorsement to Graduate School', 'file' => '2 SEMINAR PAPER PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'folder' => $S, 'type' => 'form', 'description' => 'Issued by the coordinator to the Graduate School'],
            ],
            [
                'key' => 'final',
                'label' => 'Final Seminar Paper',
                'shortLabel' => 'Final Paper',
                'documents' => [
                    ['id' => 'sf_paper', 'label' => 'Final Seminar Paper', 'file' => 'MATH SEMINAR PAPER TEMPLATE FORMAT.docx', 'folder' => $S, 'type' => 'template', 'description' => 'Complete final seminar paper'],
                    ['id' => 'sf_receipt', 'label' => 'Official Receipt / Payment Proof', 'file' => '', 'folder' => '', 'type' => 'receipt', 'description' => 'Scanned copy of official receipt from Graduate School'],
                ],
                'adviserEndorsementForm' => ['id' => 'sf_adviser', 'label' => 'Final Seminar Paper Adviser Endorsement Form', 'file' => '3 FINAL SEMINAR PAPER ADVISER ENDORSEMENT FORM - PROPOSAL.docx', 'folder' => $S, 'type' => 'form', 'description' => 'Signed by your research adviser'],
                'gradSchoolEndorsementForm' => ['id' => 'sf_gradschool', 'label' => 'Final Seminar Paper Endorsement to Graduate School', 'file' => '2 SEMINAR PAPER PROPOSAL ENDORSEMENT TO GRADSCHOOL.docx', 'folder' => $S, 'type' => 'form', 'description' => 'Issued by the coordinator to the Graduate School'],
            ],
        ],
        'refs' => [
            ['id' => 'sem_format', 'label' => 'Seminar Paper Format', 'file' => 'MATH SEMINAR PAPER TEMPLATE FORMAT.docx', 'folder' => $S, 'type' => 'template', 'description' => 'Official seminar paper template format'],
            ['id' => 'sem_cover', 'label' => 'Final Seminar Paper Cover Page', 'file' => 'FINAL SEMINAR PAPER COVER PAGE v2026.docx', 'folder' => $S, 'type' => 'template', 'description' => 'Cover page template (v2026)'],
        ],
    ];

    if ($track === 'capstone') {
        return getDatabaseWorkflow($track, $capstone);
    }
    if ($track === 'seminar') {
        return getDatabaseWorkflow($track, $seminar);
    }
    return getDatabaseWorkflow($track, $thesis);
}

function getDatabaseWorkflow(string $track, array $workflow): array {
    if (!class_exists('DB')) {
        return $workflow;
    }

    try {
        $stmt = DB::getConnection()->prepare(
            'SELECT workflow_stages.stage_key, workflow_stages.stage_label
             FROM workflow_stages
             INNER JOIN tracks ON tracks.track_id = workflow_stages.track_id
             WHERE tracks.track_code = :track
             ORDER BY workflow_stages.stage_order, workflow_stages.stage_id'
        );
        $stmt->execute(['track' => $track]);
        $databaseStages = $stmt->fetchAll();
        if (!$databaseStages) {
            return $workflow;
        }

        $configuredStages = [];
        foreach ($workflow['stages'] as $configuredStage) {
            $configuredStages[$configuredStage['key']] = $configuredStage;
        }

        $stages = [];
        foreach ($databaseStages as $databaseStage) {
            $stageKey = $databaseStage['stage_key'];
            $stage = $configuredStages[$stageKey] ?? [
                'key' => $stageKey,
                'label' => $databaseStage['stage_label'],
                'shortLabel' => $databaseStage['stage_label'],
                'documents' => [],
                'adviserEndorsementForm' => ['file' => ''],
                'gradSchoolEndorsementForm' => ['file' => ''],
            ];
            $stage['label'] = $databaseStage['stage_label'];
            $stages[] = $stage;
        }
        $workflow['stages'] = $stages;
    } catch (Throwable $e) {
        return $workflow;
    }

    return $workflow;
}

function getPaperLibraryRows(): array {
    $rows = [];
    $seen = [];

    $push = static function (array &$rows, array &$seen, array $doc, string $courseType, string $programs, string $stage, string $docType): void {
        $key = $doc['folder'] . '|' . $doc['file'] . '|' . $stage . '|' . $docType;
        if ($doc['file'] === '' || isset($seen[$key])) {
            return;
        }
        $seen[$key] = true;
        $rows[] = [
            'label' => $doc['label'],
            'file' => $doc['file'],
            'folder' => $doc['folder'],
            'courseType' => $courseType,
            'programs' => $programs,
            'stage' => $stage,
            'docType' => $docType,
            'url' => papersUrl($doc['folder'], $doc['file']),
        ];
    };

    foreach ([['thesis', 'Thesis', 'MSCS'], ['capstone', 'Capstone', 'MIT'], ['seminar', 'Seminar Paper', 'MATH']] as [$track, $course, $programs]) {
        $wf = getWorkflow($track);
        foreach ($wf['stages'] as $stage) {
            foreach ($stage['documents'] as $doc) {
                if (($doc['type'] ?? '') === 'template') {
                    $fileKey = $doc['folder'] . '|' . $doc['file'] . '|Template';
                    if (!isset($seen[$fileKey])) {
                        $push($rows, $seen, $doc, $course, $programs, $stage['label'], 'Template');
                        $seen[$fileKey] = true;
                    }
                }
            }
            $push($rows, $seen, $stage['adviserEndorsementForm'], $course, $programs, $stage['label'], 'Form');
            $push($rows, $seen, $stage['gradSchoolEndorsementForm'], $course, $programs, $stage['label'], 'Form');
        }
        foreach ($wf['refs'] as $doc) {
            $fileKey = $doc['folder'] . '|' . $doc['file'] . '|Reference';
            if (!isset($seen[$fileKey]) && !isset($seen[$doc['folder'] . '|' . $doc['file'] . '|Template'])) {
                $push($rows, $seen, $doc, $course, $programs, 'All Stages', 'Reference');
                $seen[$fileKey] = true;
            }
        }
    }

    return $rows;
}


if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function storeGet(string $key, array $default = []): array {
    return $_SESSION[$key] ?? $default;
}

function storeSet(string $key, array $value): void {
    $_SESSION[$key] = $value;
}

function initPrototypeStore(): void {
    if (!empty($_SESSION['csite_seeded_v3'])) {
        return;
    }

    $students = [
        ['id' => 'seed_rtorres', 'email' => 'rtorres@adzu.edu.ph', 'firstName' => 'Robbie Ryan', 'lastName' => 'Torres', 'middleInitial' => 'A', 'age' => '24', 'gender' => 'Male', 'program' => 'MSCS', 'track' => 'thesis', 'trackLabel' => 'Thesis', 'enrollDate' => '2024-08-15', 'currentStage' => 'proposal', 'status' => 'under_review', 'title' => 'AI-Powered Academic Advising System for Graduate Students', 'adviser' => 'Dr. Maria Santos'],
        ['id' => 'seed_jgler', 'email' => 'jgler@adzu.edu.ph', 'firstName' => 'John Matthew', 'lastName' => 'Gler', 'middleInitial' => 'SJ', 'age' => '', 'gender' => '', 'program' => 'MIT', 'track' => 'capstone', 'trackLabel' => 'Capstone', 'enrollDate' => '2023-08-15', 'currentStage' => 'final', 'status' => 'submitted', 'title' => 'Cloud-Based Inventory Management', 'adviser' => 'Dr. Maria Santos'],
        ['id' => 'seed_srecto', 'email' => 'srecto@adzu.edu.ph', 'firstName' => 'Sean Benedict', 'lastName' => 'Recto', 'middleInitial' => 'D', 'age' => '', 'gender' => '', 'program' => 'MSCS', 'track' => 'thesis', 'trackLabel' => 'Thesis', 'enrollDate' => '2024-08-15', 'currentStage' => 'concept', 'status' => 'submitted', 'title' => 'Blockchain for Academic Records', 'adviser' => 'Dr. Maria Santos'],
        ['id' => 'seed_marbillera', 'email' => 'marbillera@adzu.edu.ph', 'firstName' => 'Marc Laurence', 'lastName' => 'Arbillera', 'middleInitial' => 'M', 'age' => '', 'gender' => '', 'program' => 'MIT', 'track' => 'capstone', 'trackLabel' => 'Capstone', 'enrollDate' => '2024-01-15', 'currentStage' => 'proposal', 'status' => 'approved', 'title' => 'E-Learning Platform for Rural Areas', 'adviser' => 'Dr. Maria Santos'],
        ['id' => 'seed_repino', 'email' => 'repino@adzu.edu.ph', 'firstName' => 'Rhett Ushley', 'lastName' => 'Epino', 'middleInitial' => 'E', 'age' => '', 'gender' => '', 'program' => 'MSCS', 'track' => 'thesis', 'trackLabel' => 'Thesis', 'enrollDate' => '2022-08-15', 'currentStage' => 'final', 'status' => 'requires_revision', 'title' => 'Graduate Application System', 'adviser' => 'Dr. Maria Santos'],
    ];

    $applications = [
        ['id' => 1, 'studentEmail' => 'rtorres@adzu.edu.ph', 'student' => 'Robbie Ryan A. Torres', 'program' => 'MSCS', 'track' => 'thesis', 'stage' => 'Thesis Proposal', 'stageKey' => 'proposal', 'title' => 'AI-Powered Academic Advising System', 'date' => '2025-03-20', 'status' => 'under_review', 'coordinatorComment' => '', 'adviser' => 'Dr. Maria Santos'],
        ['id' => 2, 'studentEmail' => 'jgler@adzu.edu.ph', 'student' => 'John Matthew SJ. Gler', 'program' => 'MIT', 'track' => 'capstone', 'stage' => 'Final Capstone', 'stageKey' => 'final', 'title' => 'Cloud-Based Inventory Management', 'date' => '2025-03-18', 'status' => 'submitted', 'coordinatorComment' => '', 'adviser' => 'Dr. Maria Santos'],
        ['id' => 3, 'studentEmail' => 'srecto@adzu.edu.ph', 'student' => 'Sean Benedict D. Recto', 'program' => 'MSCS', 'track' => 'thesis', 'stage' => 'Concept Paper', 'stageKey' => 'concept', 'title' => 'Blockchain for Academic Records', 'date' => '2025-03-22', 'status' => 'submitted', 'coordinatorComment' => '', 'adviser' => 'Dr. Maria Santos'],
        ['id' => 4, 'studentEmail' => 'marbillera@adzu.edu.ph', 'student' => 'Marc Laurence M. Arbillera', 'program' => 'MIT', 'track' => 'capstone', 'stage' => 'Capstone Proposal', 'stageKey' => 'proposal', 'title' => 'E-Learning Platform for Rural Areas', 'date' => '2025-03-10', 'status' => 'approved', 'coordinatorComment' => '', 'adviser' => 'Dr. Maria Santos'],
        ['id' => 5, 'studentEmail' => 'repino@adzu.edu.ph', 'student' => 'Rhett Ushley E. Epino', 'program' => 'MSCS', 'track' => 'thesis', 'stage' => 'Final Thesis', 'stageKey' => 'final', 'title' => 'Graduate Application System', 'date' => '2025-02-28', 'status' => 'requires_revision', 'coordinatorComment' => '', 'adviser' => 'Dr. Maria Santos'],
    ];

    $uploads = [
        ['id' => 'up_1', 'applicationId' => 1, 'studentEmail' => 'rtorres@adzu.edu.ph', 'fileName' => 'Concept Paper – v1.pdf', 'docType' => 'Concept Paper', 'stage' => 'Concept Paper Presentation', 'size' => 1200000, 'date' => '2024-11-10', 'status' => 'approved'],
        ['id' => 'up_2', 'applicationId' => 1, 'studentEmail' => 'rtorres@adzu.edu.ph', 'fileName' => 'Adviser Endorsement – Concept.pdf', 'docType' => 'Signed Adviser Endorsement', 'stage' => 'Concept Paper Presentation', 'size' => 480000, 'date' => '2024-11-08', 'status' => 'approved'],
        ['id' => 'up_3', 'applicationId' => 1, 'studentEmail' => 'rtorres@adzu.edu.ph', 'fileName' => 'Research Proposal – v2.pdf', 'docType' => 'Proposal', 'stage' => 'Thesis Proposal Presentation', 'size' => 2100000, 'date' => '2025-03-15', 'status' => 'under_review'],
        ['id' => 'up_4', 'applicationId' => 1, 'studentEmail' => 'rtorres@adzu.edu.ph', 'fileName' => 'Adviser Endorsement – Proposal.pdf', 'docType' => 'Signed Adviser Endorsement', 'stage' => 'Thesis Proposal Presentation', 'size' => 390000, 'date' => '2025-03-14', 'status' => 'submitted'],
    ];

    $panels = [
        ['id' => 'seed_pm_Dela_Cruz', 'name' => 'Dr. Juan Dela Cruz', 'qualification' => 'PhD in Computer Science', 'email' => 'delacruz@adzu.edu.ph', 'notes' => '', 'panelSessions' => 8, 'availability' => 'available'],
        ['id' => 'seed_pm_Reyes', 'name' => 'Dr. Ana Reyes', 'qualification' => 'PhD in Information Technology', 'email' => 'reyes@adzu.edu.ph', 'notes' => '', 'panelSessions' => 6, 'availability' => 'available'],
        ['id' => 'seed_pm_Santos', 'name' => 'Prof. Miguel Santos', 'qualification' => 'MS in Computer Science', 'email' => 'santos@adzu.edu.ph', 'notes' => '', 'panelSessions' => 12, 'availability' => 'available'],
        ['id' => 'seed_pm_Fernandez', 'name' => 'Prof. Lisa Fernandez', 'qualification' => 'MS in Information Technology', 'email' => 'fernandez@adzu.edu.ph', 'notes' => '', 'panelSessions' => 10, 'availability' => 'available'],
        ['id' => 'seed_pm_Maria', 'name' => 'Dr. Maria Santos', 'qualification' => 'PhD in Computer Science', 'email' => 'msantos@adzu.edu.ph', 'notes' => '', 'panelSessions' => 5, 'availability' => 'available'],
    ];

    $schedules = [
        ['id' => 'sch_seed_1', 'studentEmail' => 'marbillera@adzu.edu.ph', 'studentName' => 'Marc Laurence M. Arbillera', 'applicationId' => 4, 'stage' => 'Capstone Proposal Presentation', 'date' => 'Apr 10, 2025', 'time' => '9:00 AM', 'venue' => 'CSITE Seminar Room', 'panel' => 'Dela Cruz, Reyes, Santos', 'status' => 'confirmed', 'createdAt' => '2025-03-01T00:00:00+08:00'],
        ['id' => 'sch_seed_2', 'studentEmail' => 'repino@adzu.edu.ph', 'studentName' => 'Rhett Ushley E. Epino', 'applicationId' => 5, 'stage' => 'Final Thesis Defense', 'date' => 'Apr 15, 2025', 'time' => '1:00 PM', 'venue' => 'CSITE Seminar Room', 'panel' => 'Reyes, Santos, Fernandez', 'status' => 'confirmed', 'createdAt' => '2025-03-01T00:00:00+08:00'],
        ['id' => 'sch_seed_3', 'studentEmail' => 'rtorres@adzu.edu.ph', 'studentName' => 'Robbie Ryan A. Torres', 'applicationId' => 1, 'stage' => 'Thesis Proposal Presentation', 'date' => 'Apr 20, 2025', 'time' => '10:00 AM', 'venue' => '', 'panel' => 'TBD', 'status' => 'pending', 'createdAt' => '2025-03-01T00:00:00+08:00'],
    ];

    $wfDefaults = [
        'workflowState' => [],
        'paymentRecorded' => false,
        'readyForPresentation' => false,
        'result' => '',
        'gradSchoolEndorsed' => false,
        'revisionInstructions' => '',
        'receiptNumber' => '',
        'paymentDate' => '',
        'paymentAmount' => '',
    ];
    foreach ($applications as &$appRow) {
        $appRow = array_merge($wfDefaults, $appRow);
    }
    unset($appRow);

    $demoStudents = [
        ['id' => 'seed_alex', 'email' => '1@adzu.edu.ph', 'firstName' => 'Alex', 'lastName' => 'Santos', 'middleInitial' => 'R', 'age' => '25', 'gender' => 'Male', 'program' => 'MSCS', 'track' => 'thesis', 'trackLabel' => 'Thesis', 'enrollDate' => '2024-08-15', 'currentStage' => 'not_started', 'status' => 'not_started', 'title' => '', 'adviser' => ''],
        ['id' => 'seed_maria', 'email' => '2@adzu.edu.ph', 'firstName' => 'Maria', 'lastName' => 'Cruz', 'middleInitial' => 'L', 'age' => '26', 'gender' => 'Female', 'program' => 'MIT', 'track' => 'capstone', 'trackLabel' => 'Capstone', 'enrollDate' => '2024-08-15', 'currentStage' => 'not_started', 'status' => 'not_started', 'title' => '', 'adviser' => ''],
        ['id' => 'seed_jose', 'email' => '3@adzu.edu.ph', 'firstName' => 'Jose', 'lastName' => 'Reyes', 'middleInitial' => 'M', 'age' => '27', 'gender' => 'Male', 'program' => 'MATH', 'track' => 'seminar', 'trackLabel' => 'Seminar Paper', 'enrollDate' => '2024-08-15', 'currentStage' => 'not_started', 'status' => 'not_started', 'title' => '', 'adviser' => ''],
    ];
    $emails = array_map(static fn($s) => strtolower($s['email']), $students);
    foreach ($demoStudents as $ds) {
        if (!in_array(strtolower($ds['email']), $emails, true)) {
            $students[] = $ds;
        }
    }

    $accounts = [
        '1@adzu.edu.ph' => ['password' => 'ABCD1234', 'profile' => ['firstName' => 'Alex', 'lastName' => 'Santos', 'middleInitial' => 'R', 'age' => '25', 'gender' => 'Male', 'email' => '1@adzu.edu.ph', 'program' => 'MSCS']],
        '2@adzu.edu.ph' => ['password' => 'ABCD1234', 'profile' => ['firstName' => 'Maria', 'lastName' => 'Cruz', 'middleInitial' => 'L', 'age' => '26', 'gender' => 'Female', 'email' => '2@adzu.edu.ph', 'program' => 'MIT']],
        '3@adzu.edu.ph' => ['password' => 'ABCD1234', 'profile' => ['firstName' => 'Jose', 'lastName' => 'Reyes', 'middleInitial' => 'M', 'age' => '27', 'gender' => 'Male', 'email' => '3@adzu.edu.ph', 'program' => 'MATH']],
        'rtorres@adzu.edu.ph' => ['password' => 'ABCD1234', 'profile' => ['firstName' => 'Robbie Ryan', 'lastName' => 'Torres', 'middleInitial' => 'A', 'age' => '24', 'gender' => 'Male', 'email' => 'rtorres@adzu.edu.ph', 'program' => 'MSCS']],
        'jgler@adzu.edu.ph' => ['password' => 'ABCD1234', 'profile' => ['firstName' => 'John Matthew', 'lastName' => 'Gler', 'middleInitial' => 'SJ', 'age' => '', 'gender' => '', 'email' => 'jgler@adzu.edu.ph', 'program' => 'MIT']],
        'srecto@adzu.edu.ph' => ['password' => 'ABCD1234', 'profile' => ['firstName' => 'Sean Benedict', 'lastName' => 'Recto', 'middleInitial' => 'D', 'age' => '', 'gender' => '', 'email' => 'srecto@adzu.edu.ph', 'program' => 'MSCS']],
    ];

    storeSet('students', $students);
    sortSessionStudentsAlphabetically();
    storeSet('applications', $applications);
    storeSet('uploads', $uploads);
    storeSet('panels', $panels);
    storeSet('schedules', $schedules);
    storeSet('accounts', $accounts);
    $_SESSION['csite_seeded'] = true;
    $_SESSION['csite_seeded_v2'] = true;
    $_SESSION['csite_seeded_v3'] = true;
}

function studentDisplayName(array $s): string {
    $databaseName = trim((string) ($s['name'] ?? $s['full_name'] ?? ''));
    if ($databaseName !== '') {
        return $databaseName;
    }
    $first = trim((string) ($s['firstName'] ?? $s['first_name'] ?? ''));
    $last = trim((string) ($s['lastName'] ?? $s['last_name'] ?? ''));
    $mi = trim((string) ($s['middleInitial'] ?? $s['middle_initial'] ?? ''));
    $mi = $mi !== '' ? rtrim($mi, '.') : '';
    return canonicalStudentName($first, $last, $mi);
}

function formatPersonName(?string $name): string {
    $name = trim(preg_replace('/\s+/', ' ', (string) $name) ?? '');
    if ($name === '' || ($name !== strtoupper($name) && $name !== strtolower($name))) {
        return $name;
    }
    return ucwords(strtolower($name), " \t\r\n\f\v-'");
}

/**
 * Build the single canonical student display name used everywhere.
 * Format: "Last, First MI" (no trailing dot) — the same value the database
 * triggers write to users.full_name, so the Users/application record and the
 * Students table can never diverge. Because the last name leads, a plain
 * alphabetical ORDER BY on this value (or on last_name, first_name) is correct.
 */
function canonicalStudentName(string $first, string $last, ?string $middleInitial = ''): string {
    $first = formatPersonName($first);
    $last = formatPersonName($last);
    $mi = trim((string) $middleInitial);
    $mi = $mi !== '' ? rtrim($mi, '.') : '';
    if ($last === '' && $first === '') {
        return '';
    }
    if ($last === '') {
        return trim($first . ($mi !== '' ? ' ' . $mi : ''));
    }
    if ($first === '') {
        return trim($last . ($mi !== '' ? ', ' . $mi : ''));
    }
    return trim($last . ', ' . $first . ($mi !== '' ? ' ' . $mi : ''));
}

/**
 * Extract the official student ID from an ADZU email address.
 * Keeps only the numeric characters of the local part:
 * e.g. co240255@adzu.edu.ph -> 240255.
 */
function extractStudentIdFromEmail(string $email): string {
    $email = trim($email);
    if ($email === '' || !str_contains($email, '@')) {
        return '';
    }
    $local = strtolower(explode('@', $email, 2)[0]);
    if (preg_match_all('/\d+/', $local, $m)) {
        return implode('', $m[0]);
    }
    return '';
}

/**
 * Resolve the program code for a student row regardless of whether the stored
 * value is the code (MSCS) or the full program label.
 */
function programCodeForStudent(array $student): string {
    $program = trim((string) ($student['program'] ?? ''));
    if ($program === '') {
        return 'MSCS';
    }
    if (isset(PROGRAMS[$program])) {
        return $program;
    }
    $code = array_key_first(array_filter(PROGRAMS, static fn($label) => $label === $program));
    return $code ?: $program;
}

/**
 * Persist the full student identity into the MySQL students table.
 * This is the single write point that keeps students in sync with the
 * application/registration record and with users.full_name (via DB triggers).
 */
function upsertStudentIdentity(array $input, int $userId): array {
    $pdo = DB::getConnection();

    $first = trim((string) ($input['first_name'] ?? $input['firstName'] ?? ''));
    $last  = trim((string) ($input['last_name'] ?? $input['lastName'] ?? ''));
    $mi    = trim((string) ($input['middle_initial'] ?? $input['middleInitial'] ?? ''));
    $mi    = $mi !== '' ? rtrim($mi, '.') : '';
    $programLabel = $input['program_label'] ?? (isset(PROGRAMS[$input['program'] ?? '']) ? PROGRAMS[$input['program']] : ($input['program'] ?? 'MSCS'));
    $track = $input['track'] ?? getTrackForProgram($input['program'] ?? 'MSCS');
    $gender = trim((string) ($input['gender'] ?? ''));
    $age = (int) ($input['age'] ?? 0);
    $fullName = canonicalStudentName($first, $last, $mi);

    $existing = DB::find('students', ['user_id' => (int) $userId]);
    $emailForId = trim((string) ($input['email'] ?? ''));
    if ($emailForId === '') {
        try {
            $owner = DB::find('users', ['user_id' => (int) $userId]);
            $emailForId = trim((string) ($owner['email'] ?? ''));
        } catch (Throwable $e) {
            $emailForId = '';
        }
    }
    $emailDigits = $emailForId !== '' ? extractStudentIdFromEmail($emailForId) : '';
    if ($existing) {
        $pdo->beginTransaction();
        try {
            $patch = [
                'first_name' => $first,
                'last_name' => $last,
                'middle_initial' => $mi,
                'age' => $age,
                'gender' => $gender,
                'program' => $programLabel,
                'track' => $track,
                'adviser_name' => trim((string) ($input['adviser_name'] ?? $existing['adviser_name'] ?? '')),
            ];
            DB::update('students', $patch, ['student_id' => (int) $existing['student_id']]);
            DB::update('users', ['full_name' => $fullName], ['user_id' => (int) $userId]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        $existing['first_name'] = $first;
        $existing['last_name'] = $last;
        $existing['middle_initial'] = $mi;
        $existing['age'] = $age;
        $existing['gender'] = $gender;
        $existing['program'] = $programLabel;
        $existing['track'] = $track;
        $existing['adviser_name'] = trim((string) ($input['adviser_name'] ?? $existing['adviser_name'] ?? ''));
        return $existing;
    }

    $pdo->beginTransaction();
    try {
        $newRow = [
            'user_id' => (int) $userId,
            'first_name' => $first,
            'last_name' => $last,
            'middle_initial' => $mi,
            'age' => $age,
            'gender' => $gender,
            'program' => $programLabel,
            'track' => $track,
            'adviser_name' => trim((string) ($input['adviser_name'] ?? '')),
            'enrollment_date' => date('Y-m-d'),
        ];
        // Official student ID = numeric part of the ADZU email
        // (e.g. co259344@adzu.edu.ph -> student_id 259344).
        $explicitStudentId = ($emailDigits !== '' && ctype_digit($emailDigits)) ? (int) $emailDigits : 0;
        if ($explicitStudentId > 0) {
            $newRow['student_id'] = $explicitStudentId;
        }
        try {
            $student = DB::insert('students', $newRow);
        } catch (PDOException $e) {
            // Rare digit collision (different email, same digits): fall back to auto-assign.
            if ($explicitStudentId > 0 && (string) ($e->getCode() ?? '') === '23000') {
                unset($newRow['student_id']);
                $student = DB::insert('students', $newRow);
            } else {
                throw $e;
            }
        }
        $pdo->commit();
        return DB::find('students', ['user_id' => (int) $userId]) ?: $student;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Build the session-prototype student row that mirrors the MySQL students row
 * exactly, so every hybrid screen shows the identical identity.
 */
function studentRowFromIdentity(array $identity, array $sessionRow = []): array {
    $first = trim((string) ($identity['first_name'] ?? $identity['firstName'] ?? ''));
    $last  = trim((string) ($identity['last_name'] ?? $identity['lastName'] ?? ''));
    $mi    = trim((string) ($identity['middle_initial'] ?? $identity['middleInitial'] ?? ''));
    $mi    = $mi !== '' ? rtrim($mi, '.') : '';
    $programCode = programCodeForStudent($identity);
    $track = $identity['track'] ?? getTrackForProgram($programCode);
    $age = trim((string) ($identity['age'] ?? ''));
    $gender = trim((string) ($identity['gender'] ?? ''));
    $enroll = (string) ($identity['enrollment_date'] ?? $identity['enrollDate'] ?? date('Y-m-d'));

    return [
        'id' => (string) ($sessionRow['id'] ?? 's_' . uniqid()),
        'email' => (string) ($identity['email'] ?? $sessionRow['email'] ?? ''),
        'firstName' => $first,
        'lastName' => $last,
        'middleInitial' => $mi,
        'age' => $age,
        'gender' => $gender,
        'program' => $programCode,
        'track' => $track,
        'trackLabel' => getTrackLabel($track),
        'enrollDate' => $enroll,
        'currentStage' => (string) ($sessionRow['currentStage'] ?? 'not_started'),
        'status' => (string) ($sessionRow['status'] ?? 'not_started'),
        'title' => (string) ($sessionRow['title'] ?? ''),
        'adviser' => (string) ($sessionRow['adviser'] ?? $identity['adviser_name'] ?? ''),
    ];
}

/**
 * Insert or replace the session-prototype student record for an email with the
 * canonical identity, so the prototype screens can never show a stale name.
 */
function upsertSessionStudent(array $identity, array $sessionRow = []): void {
    $students = storeGet('students');
    $email = strtolower((string) ($identity['email'] ?? $sessionRow['email'] ?? ''));
    if ($email === '') {
        return;
    }
    $row = studentRowFromIdentity($identity, $sessionRow);
    $row['email'] = $identity['email'] ?? $sessionRow['email'] ?? $row['email'];
    $found = false;
    foreach ($students as &$s) {
        if (strcasecmp((string) ($s['email'] ?? ''), $email) === 0) {
            $s = array_merge($s, $row);
            $found = true;
            break;
        }
    }
    unset($s);
    if (!$found) {
        $students[] = $row;
    }
    storeSet('students', $students);
    sortSessionStudentsAlphabetically();
    $_SESSION['current_student_id'] = $row['id'];
    $_SESSION['current_student_email'] = $row['email'];
}

/**
 * Read the authenticated student's identity straight from MySQL — the single
 * source of truth. Falls back to session data only when the DB is unavailable.
 */
function databaseStudentIdentity(): array {
    $sessionUser = $_SESSION['user'] ?? [];
    $userId = (int) ($sessionUser['user_id'] ?? $_SESSION['user_id'] ?? 0);
    if ($userId <= 0 || !class_exists('DB')) {
        return [];
    }
    try {
        $dbStudent = DB::find('students', ['user_id' => $userId]);
        $dbUser = $userId > 0 ? DB::find('users', ['user_id' => $userId]) : null;
        if (!$dbStudent || !$dbUser) {
            return [];
        }
        return [
            'student_id' => (int) $dbStudent['student_id'],
            'user_id' => $userId,
            'email' => $dbUser['email'],
            'full_name' => $dbUser['full_name'],
            'first_name' => $dbStudent['first_name'],
            'last_name' => $dbStudent['last_name'],
            'middle_initial' => $dbStudent['middle_initial'] ?? '',
            'age' => (int) $dbStudent['age'],
            'gender' => $dbStudent['gender'] ?? '',
            'program' => programCodeForStudent($dbStudent),
            'program_label' => $dbStudent['program'],
            'program_name' => preg_replace('/\s*\(.*\)$/', '', (string) $dbStudent['program']) ?: $dbStudent['program'],
            'track' => $dbStudent['track'],
            'track_label' => getTrackLabel($dbStudent['track']),
            'adviser_name' => $dbStudent['adviser_name'] ?? '',
            'enrollment_date' => $dbStudent['enrollment_date'] ?? '',
        ];
    } catch (Throwable $e) {
        return [];
    }
}

function findStudentByEmail(string $email): ?array {
    foreach (storeGet('students') as $s) {
        if (strcasecmp((string) $s['email'], $email) === 0) {
            return $s;
        }
    }
    return null;
}

function findStudentById(string $id): ?array {
    foreach (storeGet('students') as $s) {
        if ((string) $s['id'] === $id) {
            return $s;
        }
    }
    return null;
}

function currentStudentEmail(array $fallback): string {
    $id = (string) ($_SESSION['current_student_id'] ?? '');
    if ($id !== '') {
        $student = findStudentById($id);
        if ($student) {
            return (string) $student['email'];
        }
    }
    return $_SESSION['current_student_email'] ?? $fallback['email'];
}

function currentStudentProfile(array $fallback): array {
    $sessionUser = $_SESSION['user'] ?? [];
    if (($sessionUser['role'] ?? '') === 'student' && !empty($sessionUser['user_id']) && class_exists('DB')) {
        try {
            $dbStudent = DB::find('students', ['user_id' => (int) $sessionUser['user_id']]);
            if ($dbStudent) {
                $programCode = programCodeForStudent($dbStudent);
                $programName = PROGRAMS[$programCode] ?? $dbStudent['program'];
                $enroll = $dbStudent['enrollment_date'] ?: date('Y-m-d');
                $fullName = trim((string) ($sessionUser['full_name'] ?? ''));
                if ($fullName === '') {
                    $fullName = canonicalStudentName($dbStudent['first_name'], $dbStudent['last_name'], $dbStudent['middle_initial'] ?? '');
                }
                return array_merge($fallback, ['id' => (string) $dbStudent['student_id'], 'name' => $fullName, 'email' => $sessionUser['email'] ?? $dbStudent['email'] ?? '', 'program' => $programCode, 'program_name' => preg_replace('/\s*\(.*\)$/', '', $programName) ?: $programName, 'track' => $dbStudent['track'], 'trackLabel' => getTrackLabel($dbStudent['track']), 'enroll_date' => $enroll, 'current_stage' => 'not_started', 'status' => 'not_started', 'title' => '', 'adviser' => $dbStudent['adviser_name'] ?? '', 'completion_deadline' => ((int) substr($enroll, 0, 4) + 3) . substr($enroll, 4)]);
            }
        } catch (Throwable $e) {
        }
    }
    $id = (string) ($_SESSION['current_student_id'] ?? '');
    $s = $id !== '' ? findStudentById($id) : null;
    $email = currentStudentEmail($fallback);
    if (!$s) {
        $s = findStudentByEmail($email);
    }
    if (!$s) {
        if (($sessionUser['role'] ?? '') === 'student') {
            $fallback['name'] = $sessionUser['full_name'] ?? $fallback['name'];
            $fallback['email'] = $sessionUser['email'] ?? $fallback['email'];
        }
        return $fallback;
    }
    $program = $s['program'];
    $track = getTrackForProgram($program);
    $fullName = PROGRAMS[$program] ?? $program;
    $programName = preg_replace('/\s*\(.*\)$/', '', $fullName) ?: $fullName;
    $enroll = $s['enrollDate'] ?? $fallback['enroll_date'];
    if (strlen($enroll) === 7) {
        $enroll .= '-01';
    }
    $year = (int) substr($enroll, 0, 4);
    return array_merge($fallback, [
        'id' => $s['id'],
        'name' => studentDisplayName($s),
        'email' => $s['email'],
        'program' => $program,
        'program_name' => $programName,
        'track' => $track,
        'trackLabel' => getTrackLabel($track),
        'enroll_date' => $enroll,
        'current_stage' => $s['currentStage'] ?? 'proposal',
        'status' => $s['status'] ?? 'pending',
        'title' => $s['title'] ?? $fallback['title'],
        'adviser' => $s['adviser'] ?? $fallback['adviser'],
        'completion_deadline' => ($year + 3) . substr($enroll, 4),
    ]);
}

/**
 * Keep the session student list in the same alphabetical order as the
 * database (last name, then first name) so a newly created account lands in
 * its correct sorted position instead of appended at the end.
 */
function sortSessionStudentsAlphabetically(): void {
    $students = storeGet('students');
    usort($students, static function ($a, $b) {
        $c = strcasecmp(
            trim((string) ($a['lastName'] ?? $a['last_name'] ?? '')),
            trim((string) ($b['lastName'] ?? $b['last_name'] ?? ''))
        );
        if ($c !== 0) {
            return $c;
        }
        return strcasecmp(
            trim((string) ($a['firstName'] ?? $a['first_name'] ?? '')),
            trim((string) ($b['firstName'] ?? $b['first_name'] ?? ''))
        );
    });
    storeSet('students', $students);
}

function addRegisteredStudent(array $input): array {
    $list = storeGet('students');
    foreach ($list as $s) {
        if (strcasecmp($s['email'], $input['email']) === 0) {
            throw new LogicException('An account already exists for this email. Sign in with that account instead.');
        }
    }
    $track = getTrackForProgram($input['program']);
    $rec = [
        'id' => 's_' . uniqid(),
        'email' => $input['email'],
        'firstName' => trim($input['firstName']),
        'lastName' => trim($input['lastName']),
        'middleInitial' => rtrim(trim($input['middleInitial'] ?? ''), '.'),
        'age' => $input['age'] ?? '',
        'gender' => $input['gender'] ?? '',
        'program' => $input['program'],
        'track' => $track,
        'trackLabel' => getTrackLabel($track),
        'enrollDate' => date('Y-m-d'),
        'currentStage' => 'Not Started',
        'status' => 'not_started',
        'title' => '',
        'adviser' => '',
    ];
    $list[] = $rec;
    storeSet('students', $list);
    sortSessionStudentsAlphabetically();
    $_SESSION['current_student_id'] = $rec['id'];
    $_SESSION['current_student_email'] = $rec['email'];
    return $rec;
}

function loadAccounts(): array {
    return storeGet('accounts');
}

function saveAccount(array $profile, string $password = ''): void {
    $accounts = loadAccounts();
    $key = strtolower((string) $profile['email']);
    $prev = $accounts[$key] ?? ['password' => '', 'profile' => []];
    $accounts[$key] = [
        'password' => $password !== '' ? $password : ($prev['password'] ?? ''),
        'profile' => $profile,
    ];
    storeSet('accounts', $accounts);
}

function findAccount(string $email): ?array {
    $accounts = loadAccounts();
    $key = strtolower($email);
    return $accounts[$key] ?? null;
}

function authenticateStudent(string $email, string $password): array {
    $email = trim($email);
    if (!preg_match('/^[^\s@]+@adzu\.edu\.ph$/i', $email)) {
        return ['ok' => false, 'field' => 'email', 'message' => 'Enter a valid ADZU email address (e.g. yourname@adzu.edu.ph)'];
    }
    $account = findAccount($email);
    if (!$account) {
        $student = findStudentByEmail($email);
        if (!$student) {
            return ['ok' => false, 'field' => 'email', 'message' => 'There is no account found, please create an account.'];
        }
        $account = [
            'password' => 'ABCD1234',
            'profile' => [
                'firstName' => $student['firstName'],
                'lastName' => $student['lastName'],
                'middleInitial' => $student['middleInitial'] ?? '',
                'age' => $student['age'] ?? '',
                'gender' => $student['gender'] ?? '',
                'email' => $student['email'],
                'program' => $student['program'],
            ],
        ];
    }
    if ($password === '') {
        return ['ok' => false, 'field' => 'password', 'message' => 'Password is required'];
    }
    if ($password !== ($account['password'] ?? '')) {
        return ['ok' => false, 'field' => 'password', 'message' => 'Incorrect password. Please try again.'];
    }
    $student = findStudentByEmail((string) $account['profile']['email']);
    if (!$student) {
        return ['ok' => false, 'field' => 'email', 'message' => 'This account no longer has a student profile. Please contact the coordinator.'];
    }
    $_SESSION['current_student_id'] = $student['id'];
    $_SESSION['current_student_email'] = $student['email'];
    return ['ok' => true, 'profile' => $account['profile']];
}

function updateLoggedInStudentProfile(array $input): void {
    $email = currentStudentEmail(['email' => '']);
    if ($email === '') {
        return;
    }
    $students = storeGet('students');
    foreach ($students as &$s) {
        if (strcasecmp($s['email'], $email) !== 0) {
            continue;
        }
        $s['firstName'] = $input['firstName'] ?? $s['firstName'];
        $s['lastName'] = $input['lastName'] ?? $s['lastName'];
        $s['middleInitial'] = $input['middleInitial'] ?? $s['middleInitial'];
        $s['age'] = $input['age'] ?? $s['age'];
        $s['gender'] = $input['gender'] ?? $s['gender'];
        $s['program'] = $input['program'] ?? $s['program'];
        if (!empty($input['email'])) {
            $s['email'] = $input['email'];
            $_SESSION['current_student_email'] = $input['email'];
        }
        $s['track'] = getTrackForProgram($s['program']);
        $s['trackLabel'] = getTrackLabel($s['track']);
        saveAccount([
            'firstName' => $s['firstName'],
            'lastName' => $s['lastName'],
            'middleInitial' => $s['middleInitial'],
            'age' => $s['age'],
            'gender' => $s['gender'],
            'email' => $s['email'],
            'program' => $s['program'],
        ]);
        $_SESSION['current_student_id'] = $s['id'];
        break;
    }
    unset($s);
    storeSet('students', $students);
    sortSessionStudentsAlphabetically();
}

function addApplicationRecord(array $a): array {
    $list = storeGet('applications');
    $id = time();
    $track = getTrackForProgram($a['program']);
    $stages = getWorkflowStageLabels($track);
    $stageKey = $a['stageKey'] ?? 'proposal';
    $rec = array_merge(appWorkflowDefaults(), [
        'id' => $id,
        'studentEmail' => $a['studentEmail'],
        'student' => $a['student'],
        'program' => $a['program'],
        'track' => $track,
        'stage' => $stages[$stageKey] ?? $a['stage'] ?? '',
        'stageKey' => $stageKey,
        'title' => $a['title'],
        'date' => date('Y-m-d'),
        'status' => 'submitted',
        'coordinatorComment' => '',
        'adviser' => $a['adviser'] ?? '',
        'abstract' => $a['abstract'] ?? '',
    ]);
    $list[] = $rec;
    storeSet('applications', $list);
    updateStudentStage($a['studentEmail'], $stageKey, 'submitted', $a['title'] ?? null, $a['adviser'] ?? null);
    return $rec;
}

function findApplication(int $id): ?array {
    if (class_exists('DB') && $id > 0) {
        try {
            $stmt = DB::getConnection()->prepare(
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
            );
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            if ($row) {
                $row['stageKey'] = stageKeyFromLabel((string) $row['stage']);
                return $row;
            }
        } catch (Throwable $e) {
        }
    }
    foreach (storeGet('applications') as $a) {
        if ((int) $a['id'] === $id) {
            return $a;
        }
    }
    return null;
}

function latestApplicationForEmail(string $email): ?array {
    if (class_exists('DB') && $email !== '') {
        try {
            $stmt = DB::getConnection()->prepare(
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
            );
            $stmt->execute(['email' => $email]);
            $row = $stmt->fetch();
            if ($row) {
                $row['stageKey'] = stageKeyFromLabel((string) $row['stage']);
                return $row;
            }
        } catch (Throwable $e) {
        }
    }
    $found = null;
    foreach (storeGet('applications') as $a) {
        if (strcasecmp((string) $a['studentEmail'], $email) === 0) {
            $found = $a;
        }
    }
    return $found;
}

function updateStudentStage(string $email, string $stage, string $status, ?string $title = null, ?string $adviser = null, bool $mirrorLatestApp = true): void {
    $students = storeGet('students');
    foreach ($students as &$s) {
        if (strcasecmp($s['email'], $email) === 0) {
            $s['currentStage'] = $stage;
            $s['status'] = $status;
            if ($title !== null && $title !== '') {
                $s['title'] = $title;
            }
            if ($adviser !== null && $adviser !== '') {
                $s['adviser'] = $adviser;
            }
            break;
        }
    }
    unset($s);
    storeSet('students', $students);

    if (!$mirrorLatestApp) {
        return;
    }

    $apps = storeGet('applications');
    $last = -1;
    foreach ($apps as $i => $a) {
        if (strcasecmp((string) $a['studentEmail'], $email) === 0) {
            $last = $i;
        }
    }
    if ($last >= 0) {
        $apps[$last]['status'] = $status;
        $stages = getStagesForTrack($apps[$last]['track'] ?? getTrackForProgram($apps[$last]['program']));
        if (isset($stages[$stage])) {
            $apps[$last]['stageKey'] = $stage;
            $apps[$last]['stage'] = $stages[$stage];
        }
        if ($title) {
            $apps[$last]['title'] = $title;
        }
        storeSet('applications', $apps);
    }
}

function updateApplicationRecord(int $id, array $patch): ?array {
    if (class_exists('DB') && $id > 0) {
        try {
            $data = [];
            if (array_key_exists('status', $patch)) $data['status'] = $patch['status'];
            if (array_key_exists('stage', $patch)) $data['presentation_stage'] = $patch['stage'];
            if (array_key_exists('coordinatorComment', $patch)) $data['coordinator_comment'] = $patch['coordinatorComment'];
            if (array_key_exists('result', $patch)) $data['result'] = $patch['result'] !== '' ? $patch['result'] : null;
            if ($data) DB::update('applications', $data, ['application_id' => $id]);
            if (!empty($patch['coordinatorComment'])) saveApplicationComment($id, (string) $patch['coordinatorComment']);
            return findApplication($id);
        } catch (Throwable $e) {
        }
    }
    $apps = storeGet('applications');
    foreach ($apps as $i => $a) {
        if ((int) $a['id'] !== $id) {
            continue;
        }
        $apps[$i] = array_merge($a, $patch);
        storeSet('applications', $apps);
        $email = $apps[$i]['studentEmail'];
        $status = $apps[$i]['status'];
        $stage = $apps[$i]['stageKey'] ?? 'proposal';
        updateStudentStage($email, $stage, $status, $apps[$i]['title'] ?? null, $apps[$i]['adviser'] ?? null, false);
        return $apps[$i];
    }
    return null;
}

function addUploadRecord(array $u): array {
    $list = storeGet('uploads');
    $rec = [
        'id' => (string) ($u['id'] ?? '') !== '' ? (string) $u['id'] : 'up_' . uniqid(),
        'applicationId' => $u['applicationId'] ?? null,
        'studentEmail' => $u['studentEmail'],
        'fileName' => $u['fileName'],
        'docType' => $u['docType'],
        'stage' => $u['stage'],
        'size' => (int) ($u['size'] ?? 0),
        'date' => $u['date'] ?? date('Y-m-d H:i:s'),
        'status' => $u['status'] ?? 'submitted',
        'notes' => $u['notes'] ?? '',
        'storedFile' => $u['storedFile'] ?? '',
        'mimeType' => $u['mimeType'] ?? '',
    ];
    foreach ($list as $i => $existing) {
        if ((string) ($existing['id'] ?? '') === $rec['id']) {
            $list[$i] = array_merge($existing, $rec);
            storeSet('uploads', $list);
            return $list[$i];
        }
    }
    $list[] = $rec;
    storeSet('uploads', $list);
    return $rec;
}

function storeStudentUpload(array $file): array {
    $original = basename((string) ($file['name'] ?? ''));
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'doc', 'docx'], true)) {
        throw new RuntimeException('Only PDF, DOC, or DOCX files are allowed.');
    }
    if ((int) ($file['size'] ?? 0) > 20 * 1024 * 1024) {
        throw new RuntimeException('File exceeds the 20 MB maximum size.');
    }
    $storage = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($storage) && !mkdir($storage, 0750, true) && !is_dir($storage)) {
        throw new RuntimeException('Upload storage is not available.');
    }
    $stored = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file((string) $file['tmp_name'], $storage . DIRECTORY_SEPARATOR . $stored)) {
        throw new RuntimeException('The file could not be saved. Try again.');
    }
    return ['storedFile' => $stored, 'mimeType' => (string) ($file['type'] ?? ''), 'originalName' => $original];
}

/**
 * Normalize a MySQL `application_documents` row into the exact shape the
 * session-backed store uses, so every view/consumer works with either source
 * without knowing where the record came from.
 */
function normalizeDatabaseDocument(array $row, string $email = ''): array {
    return [
        'id' => (string) ($row['document_id'] ?? $row['id'] ?? ''),
        'applicationId' => isset($row['application_id']) && $row['application_id'] !== null ? (int) $row['application_id'] : null,
        'studentEmail' => strtolower($email !== '' ? $email : (string) ($row['studentEmail'] ?? $row['email'] ?? '')),
        'fileName' => (string) ($row['original_name'] ?? $row['fileName'] ?? ''),
        'docType' => (string) ($row['document_type'] ?? $row['docType'] ?? ''),
        'stage' => (string) ($row['stage'] ?? ''),
        'size' => (int) ($row['file_size'] ?? $row['size'] ?? 0),
        'date' => (string) ($row['uploaded_at'] ?? $row['date'] ?? ''),
        'status' => (string) ($row['status'] ?? 'submitted'),
        'notes' => (string) ($row['notes'] ?? ''),
        'storedFile' => (string) ($row['stored_name'] ?? $row['storedFile'] ?? ''),
        'mimeType' => (string) ($row['mime_type'] ?? $row['mimeType'] ?? ''),
        'source' => 'db',
    ];
}

/**
 * Resolve the primary users.user_id for an email address.
 * This is the unified key used by applications + application_documents.
 */
function databaseUserIdForEmail(string $email): int {
    if (!class_exists('DB') || $email === '') {
        return 0;
    }
    try {
        $stmt = DB::getConnection()->prepare(
            'SELECT user_id FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        return (int) ($stmt->fetchColumn() ?: 0);
    } catch (Throwable $e) {
        return 0;
    }
}

function databaseCoordinatorUserId(): int {
    if (!class_exists('DB')) return 0;
    try {
        return (int) (DB::getConnection()->query("SELECT user_id FROM users WHERE role = 'coordinator' ORDER BY user_id LIMIT 1")->fetchColumn() ?: 0);
    } catch (Throwable $e) {
        return 0;
    }
}

function saveApplicationComment(int $applicationId, string $comment): void {
    $coordinatorId = databaseCoordinatorUserId();
    if ($applicationId <= 0 || $coordinatorId <= 0 || trim($comment) === '') return;
    $stmt = DB::getConnection()->prepare('INSERT INTO application_comments (application_id, coordinator_user_id, comment_text) VALUES (:application_id, :coordinator_user_id, :comment_text)');
    $stmt->execute(['application_id' => $applicationId, 'coordinator_user_id' => $coordinatorId, 'comment_text' => trim($comment)]);
}

function saveApplicationPayment(int $applicationId, string $receiptNumber, string $paymentDate, string $amount): void {
    $coordinatorId = databaseCoordinatorUserId();
    if ($applicationId <= 0 || $coordinatorId <= 0 || $receiptNumber === '' || $paymentDate === '' || $amount === '') return;
    $stmt = DB::getConnection()->prepare(
        'INSERT INTO payments (application_id, recorded_by_user_id, receipt_number, payment_date, amount)
         VALUES (:application_id, :recorded_by_user_id, :receipt_number, :payment_date, :amount)
         ON DUPLICATE KEY UPDATE recorded_by_user_id = VALUES(recorded_by_user_id), receipt_number = VALUES(receipt_number), payment_date = VALUES(payment_date), amount = VALUES(amount)'
    );
    $stmt->execute([
        'application_id' => $applicationId,
        'recorded_by_user_id' => $coordinatorId,
        'receipt_number' => $receiptNumber,
        'payment_date' => $paymentDate,
        'amount' => (float) $amount,
    ]);
}

function databaseTemplateRows(string $trackCode = ''): array {
    if (!class_exists('DB')) return [];
    try {
        $rows = DB::query(
            'SELECT t.template_id AS id, t.template_name AS label, t.file_name AS file,
                    t.document_type AS docType, t.file_path AS filePath,
                    tr.track_name AS courseType, ws.stage_label AS stage,
                    t.description, p.program_code AS programs
             FROM templates t
             INNER JOIN tracks tr ON tr.track_id = t.track_id
             LEFT JOIN workflow_stages ws ON ws.stage_id = t.stage_id
             LEFT JOIN programs p ON p.track_id = tr.track_id
               WHERE t.is_active = 1 AND (:track_code_filter = "" OR tr.track_code = :track_code)
             ORDER BY tr.track_name, ws.stage_order, t.template_name'
           , ['track_code_filter' => strtolower($trackCode), 'track_code' => strtolower($trackCode)])->fetchAll();
        foreach ($rows as &$row) {
            $row['url'] = asset((string) $row['filePath']);
        }
        unset($row);
        return $rows;
    } catch (Throwable $e) {
        return [];
    }
}

function createTemplateRecord(array $data, array $file): array {
    $original = basename((string) ($file['name'] ?? ''));
    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($extension, ['pdf', 'doc', 'docx'], true)) {
        throw new RuntimeException('Only PDF, DOC, or DOCX templates are allowed.');
    }
    if ((int) ($file['size'] ?? 0) > 20 * 1024 * 1024 || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The template file is invalid or exceeds 20 MB.');
    }
    $storage = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'templates';
    if (!is_dir($storage) && !mkdir($storage, 0750, true) && !is_dir($storage)) {
        throw new RuntimeException('Template storage is not available.');
    }
    $stored = bin2hex(random_bytes(16)) . '.' . $extension;
    if (!move_uploaded_file((string) $file['tmp_name'], $storage . DIRECTORY_SEPARATOR . $stored)) {
        throw new RuntimeException('The template file could not be saved.');
    }
    $track = DB::find('tracks', ['track_code' => strtolower((string) $data['program_track'])]);
    if (!$track) throw new RuntimeException('The selected track is not configured.');
    $stage = DB::find('workflow_stages', ['track_id' => (int) $track['track_id'], 'stage_label' => $data['stage_label']]);
    $row = DB::insert('templates', [
        'track_id' => (int) $track['track_id'],
        'stage_id' => $stage['stage_id'] ?? null,
        'template_name' => trim($data['template_name']),
        'description' => trim((string) ($data['description'] ?? '')) ?: null,
        'document_type' => $data['document_type'],
        'file_name' => $original,
        'file_path' => 'storage/templates/' . $stored,
        'mime_type' => (string) ($file['type'] ?? '') ?: null,
        'managed_by_user_id' => databaseCoordinatorUserId(),
    ]);
    return $row;
}

function findTemplateRecord(string $id): ?array {
    if (!class_exists('DB') || !ctype_digit($id)) return null;
    $stmt = DB::getConnection()->prepare('SELECT t.*, tr.track_code, ws.stage_label FROM templates t INNER JOIN tracks tr ON tr.track_id = t.track_id LEFT JOIN workflow_stages ws ON ws.stage_id = t.stage_id WHERE t.template_id = :id LIMIT 1');
    $stmt->execute(['id' => (int) $id]);
    return $stmt->fetch() ?: null;
}

function updateTemplateRecord(string $id, array $data, array $file = []): ?array {
    $template = findTemplateRecord($id);
    if (!$template) return null;
    $fields = [
        'template_name' => trim($data['template_name']),
        'description' => trim((string) ($data['description'] ?? '')) ?: null,
        'document_type' => $data['document_type'],
    ];
    if (!empty($file['name'])) {
        $replacement = createTemplateRecord($data + ['program_track' => $template['track_code']], $file);
        DB::update('templates', ['is_active' => 0], ['template_id' => (int) $template['template_id']]);
        return $replacement;
    }
    DB::update('templates', $fields, ['template_id' => (int) $id]);
    return findTemplateRecord($id);
}

/**
 * Resolve the MySQL students.student_id for an email address.
 * Kept for screens that still need the students PK (profile edits); new
 * application/document relations use databaseUserIdForEmail() instead.
 */
function databaseStudentIdForEmail(string $email): int {
    $userId = databaseUserIdForEmail($email);
    if ($userId <= 0) {
        return 0;
    }
    try {
        $stmt = DB::getConnection()->prepare(
            'SELECT student_id FROM students WHERE user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        return (int) ($stmt->fetchColumn() ?: 0);
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * Latest (or stage-matching) application id for a user. Used to link a
 * freshly uploaded document to the student's active application so the
 * coordinator panel can surface it immediately.
 * NOTE: unified key is users.user_id (not students.student_id).
 */
function databaseApplicationIdForStudent(int $userId, string $stageLabel = ''): ?int {
    if (!class_exists('DB') || $userId <= 0) {
        return null;
    }
    try {
        $pdo = DB::getConnection();
        if ($stageLabel !== '') {
            $stmt = $pdo->prepare(
                'SELECT application_id, presentation_stage FROM applications
                 WHERE user_id = :user_id AND archived_at IS NULL AND presentation_stage = :stage
                 ORDER BY submitted_at DESC LIMIT 1'
            );
            $stmt->execute(['user_id' => $userId, 'stage' => $stageLabel]);
            $exact = $stmt->fetchColumn();
            if ($exact) {
                return (int) $exact;
            }

            $stageStmt = $pdo->prepare(
                'SELECT application_id, presentation_stage FROM applications
                 WHERE user_id = :user_id AND archived_at IS NULL
                 ORDER BY submitted_at DESC'
            );
            $stageStmt->execute(['user_id' => $userId]);
            $requestedStageKey = stageKeyFromLabel($stageLabel);
            foreach ($stageStmt->fetchAll() as $application) {
                if (stageKeyFromLabel((string) $application['presentation_stage']) === $requestedStageKey) {
                    return (int) $application['application_id'];
                }
            }
        }
        $stmt = $pdo->prepare(
            'SELECT application_id FROM applications
             WHERE user_id = :user_id AND archived_at IS NULL
             ORDER BY submitted_at DESC LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        return ($id = $stmt->fetchColumn()) ? (int) $id : null;
    } catch (Throwable $e) {
        return null;
    }
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
function databaseDocuments(?int $applicationId = null, int $userId = 0, bool $includeUnlinked = true): array {
    if (!class_exists('DB')) {
        return [];
    }
    try {
        $pdo = DB::getConnection();
        $where = [];
        $params = [];
        if ($applicationId !== null && $applicationId > 0) {
            if ($userId > 0 && $includeUnlinked) {
                $where[] = '(d.application_id = :application_id OR (d.application_id IS NULL AND d.user_id = :user_id))';
                $params['application_id'] = $applicationId;
                $params['user_id'] = $userId;
            } else {
                $where[] = 'd.application_id = :application_id';
                $params['application_id'] = $applicationId;
            }
        } elseif ($userId > 0) {
            $where[] = 'd.user_id = :user_id';
            $params['user_id'] = $userId;
        } else {
            return [];
        }
        $sql = 'SELECT d.document_id, d.application_id, d.user_id, d.stage, d.document_type,
                       d.original_name, d.stored_name, d.mime_type, d.file_size, d.status, d.uploaded_at,
                       u.email AS studentEmail
                FROM application_documents d
                INNER JOIN users u ON u.user_id = d.user_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY d.uploaded_at DESC, d.document_id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return array_map(
            static fn($row) => normalizeDatabaseDocument($row, (string) ($row['studentEmail'] ?? '')),
            $stmt->fetchAll()
        );
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Fetch a single document by its MySQL primary key.
 */
function databaseDocumentById(int $documentId): ?array {
    if (!class_exists('DB') || $documentId <= 0) {
        return null;
    }
    try {
        $stmt = DB::getConnection()->prepare(
            'SELECT d.document_id, d.application_id, d.user_id, d.stage, d.document_type,
                    d.original_name, d.stored_name, d.mime_type, d.file_size, d.status, d.uploaded_at,
                    u.email AS studentEmail
             FROM application_documents d
             INNER JOIN users u ON u.user_id = d.user_id
             WHERE d.document_id = :document_id
             LIMIT 1'
        );
        $stmt->execute(['document_id' => $documentId]);
        $row = $stmt->fetch();
        return $row ? normalizeDatabaseDocument($row, (string) ($row['studentEmail'] ?? '')) : null;
    } catch (Throwable $e) {
        return null;
    }
}

function classifyUploadDocType(string $docType): string {
    $doc = strtolower($docType);
    if (str_contains($doc, 'receipt') || str_contains($doc, 'payment')) {
        return 'receipt';
    }
    if (str_contains($doc, 'endorsement') || str_contains($doc, 'adviser')) {
        return 'adviser_endorsement';
    }
    return 'paper';
}

function validateUploadSequence(string $email, string $track, string $stageKey, string $docType): ?string {
    $kind = classifyUploadDocType($docType);
    if ($kind === 'paper') {
        return null;
    }
    $stages = getWorkflow($track)['stages'] ?? [];
    $stageLabel = $stageKey;
    foreach ($stages as $s) {
        if (($s['key'] ?? '') === $stageKey) {
            $stageLabel = $s['label'] ?? $stageKey;
            break;
        }
    }
    $row = null;
    foreach (getStudentProgress($email, $track) as $p) {
        if (($p['stageKey'] ?? '') === $stageKey) {
            $row = $p;
            break;
        }
    }
    if ($kind === 'adviser_endorsement') {
        if (($row['paper']['status'] ?? 'pending') !== 'done') {
            return 'Upload the ' . $stageLabel . ' paper first before the adviser endorsement form.';
        }
        return null;
    }
    if (($row['gradSchoolEndorsement']['status'] ?? 'pending') !== 'done') {
        return 'Your coordinator must issue the Graduate School endorsement for ' . $stageLabel . ' before you upload the official receipt.';
    }
    return null;
}

function findStageApplicationForEmail(string $email, string $stageKey): ?array {
    foreach (storeGet('applications') as $a) {
        if (strcasecmp((string) ($a['studentEmail'] ?? ''), $email) !== 0) {
            continue;
        }
        if (($a['stageKey'] ?? stageKeyFromLabel($a['stage'] ?? '')) === $stageKey) {
            return $a;
        }
    }
    return null;
}

function ensureStageApplication(string $email, string $stageKey, string $stageLabel): ?array {
    $email = strtolower(trim($email));
    if ($email === '') {
        return null;
    }
    $existing = findStageApplicationForEmail($email, $stageKey);
    $track = $existing['track'] ?? null;
    if ($track === null) {
        $student = findStudentByEmail($email);
        $track = $student['track'] ?? getTrackForProgram($student['program'] ?? 'MSCS');
    }
    if ($track === null || $track === '') {
        $track = 'thesis';
    }
    $stages = getWorkflowStageLabels($track);
    $canonLabel = $stages[$stageKey] ?? $stageLabel;

    if ($existing) {
        if (in_array($existing['status'] ?? '', ['not_started', 'pending', 'draft', ''], true)) {
            updateApplicationRecord((int) $existing['id'], ['status' => 'submitted']);
            $existing['status'] = 'submitted';
        }
    } else {
        $student = findStudentByEmail($email);
        $existing = addApplicationRecord([
            'studentEmail' => $email,
            'student' => $student ? studentDisplayName($student) : $email,
            'program' => $student['program'] ?? 'MSCS',
            'stage' => $canonLabel,
            'stageKey' => $stageKey,
            'title' => '',
            'adviser' => $student['adviser'] ?? '',
        ]);
    }

    if (class_exists('DB')) {
        try {
            $userId = databaseUserIdForEmail($email);
            if ($userId > 0) {
                $pdo = DB::getConnection();
                $stmt = $pdo->prepare(
                    'SELECT application_id, status, paper_title FROM applications
                     WHERE user_id = :user_id AND archived_at IS NULL AND presentation_stage = :stage
                     ORDER BY submitted_at DESC LIMIT 1'
                );
                $stmt->execute(['user_id' => $userId, 'stage' => $canonLabel]);
                $row = $stmt->fetch();
                if ($row) {
                    if (in_array($row['status'] ?? '', ['not_started', 'pending', 'draft', ''], true)) {
                        $upd = $pdo->prepare('UPDATE applications SET status = :status WHERE application_id = :id');
                        $upd->execute(['status' => 'submitted', 'id' => (int) $row['application_id']]);
                    }
                } else {
                    $title = '';
                    $latest = latestApplicationForEmail($email);
                    if (!empty($latest['title'])) {
                        $title = (string) $latest['title'];
                    }
                    $ins = $pdo->prepare(
                        'INSERT INTO applications (user_id, presentation_stage, paper_title, status)
                         VALUES (:user_id, :presentation_stage, :paper_title, :status)'
                    );
                    $ins->execute([
                        'user_id' => $userId,
                        'presentation_stage' => $canonLabel,
                        'paper_title' => $title !== '' ? $title : 'Untitled paper',
                        'status' => 'submitted',
                    ]);
                }
            }
        } catch (Throwable $e) {
        }
    }
    return findStageApplicationForEmail($email, $stageKey) ?? $existing;
}

/**
 * The single write point for a student document.
 *
 * Records the upload in BOTH stores: the session prototype (kept for the
 * legacy prototype screens) and MySQL `application_documents` (the shared
 * source of truth the coordinator panel reads). Writing only to the session
 * was the reason a coordinator could never see a student's upload.
 */
function recordStudentDocument(array $meta, ?string $email = null, ?int $userId = null): ?int {
    $email = strtolower((string) ($email ?? ($meta['studentEmail'] ?? '')));
    if ($email === '') {
        return null;
    }

    $stageLabel = (string) ($meta['stage'] ?? '');
    $stageKey = stageKeyFromLabel($stageLabel);
    $stageApp = ensureStageApplication($email, $stageKey, $stageLabel);
    if ($stageApp) {
        $meta['applicationId'] = $stageApp['id'];
    }

    $documentId = null;
    if (class_exists('DB')) {
        try {
            $resolvedUserId = $userId !== null && $userId > 0 ? (int) $userId : databaseUserIdForEmail($email);
            if ($resolvedUserId > 0) {
                $applicationId = databaseApplicationIdForStudent($resolvedUserId, $stageLabel);
                if ($applicationId === null && !empty($meta['applicationId'])) {
                    $candidate = (int) $meta['applicationId'];
                    if ($candidate > 0) {
                        $existsStmt = DB::getConnection()->prepare(
                            'SELECT 1 FROM applications WHERE application_id = :id LIMIT 1'
                        );
                        $existsStmt->execute(['id' => $candidate]);
                        if ($existsStmt->fetchColumn()) {
                            $applicationId = $candidate;
                        }
                    }
                }
                $status = (string) ($meta['status'] ?? 'submitted');
                if (!in_array($status, ['submitted', 'verified', 'incomplete'], true)) {
                    $status = 'submitted';
                }
                $stmt = DB::getConnection()->prepare(
                    'INSERT INTO application_documents
                        (application_id, user_id, stage, document_type, original_name, stored_name, mime_type, file_size, status)
                     VALUES
                        (:application_id, :user_id, :stage, :document_type, :original_name, :stored_name, :mime_type, :file_size, :status)'
                );
                $stmt->execute([
                    'application_id' => $applicationId,
                    'user_id' => $resolvedUserId,
                    'stage' => $stageLabel,
                    'document_type' => (string) ($meta['docType'] ?? 'Document'),
                    'original_name' => (string) ($meta['fileName'] ?? ''),
                    'stored_name' => (string) ($meta['storedFile'] ?? ''),
                    'mime_type' => (string) ($meta['mimeType'] ?? ''),
                    'file_size' => (int) ($meta['size'] ?? 0),
                    'status' => $status,
                ]);
                $documentId = (int) DB::getConnection()->lastInsertId();
            }
        } catch (Throwable $e) {
            error_log('CSITE: failed to record application document for ' . $email . ' — ' . $e->getMessage());
            $documentId = null;
        }
    }

    addUploadRecord(array_merge($meta, [
        'studentEmail' => $email,
        'applicationId' => $meta['applicationId'] ?? null,
        'id' => $documentId ? (string) $documentId : null,
    ]));

    return $documentId;
}

function findUpload(string $id): ?array {
    if (ctype_digit($id)) {
        $databaseUpload = databaseDocumentById((int) $id);
        if ($databaseUpload) {
            return $databaseUpload;
        }
    }
    foreach (storeGet('uploads') as $upload) {
        if ((string) ($upload['id'] ?? '') === $id) return $upload;
    }
    return null;
}

function uploadStoragePath(array $upload): ?string {
    $stored = basename((string) ($upload['storedFile'] ?? ''));
    if ($stored === '') return null;
    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $stored;
    return is_file($path) ? $path : null;
}

function updateUploadStatus(string $id, string $status): void {
    if (ctype_digit($id) && class_exists('DB')) {
        try {
            $stmt = DB::getConnection()->prepare(
                'UPDATE application_documents SET status = :status WHERE document_id = :document_id'
            );
            $stmt->execute(['status' => $status, 'document_id' => (int) $id]);
        } catch (Throwable $e) {
        }
    }
    $list = storeGet('uploads');
    foreach ($list as &$u) {
        if ((string) $u['id'] !== $id) {
            continue;
        }
        $u['status'] = $status;
        break;
    }
    unset($u);
    storeSet('uploads', $list);
}

function syncUploadStatusesFromWorkflow(int $appId, array $workflowState): void {
    $list = storeGet('uploads');
    foreach ($list as &$u) {
        if ((int) ($u['applicationId'] ?? 0) !== $appId) {
            continue;
        }
        $key = classifyUploadDocType((string) $u['docType']);
        if (!empty($workflowState[$key]) && in_array($workflowState[$key], ['verified', 'incomplete', 'submitted'], true)) {
            $u['status'] = $workflowState[$key];
        }
    }
    unset($u);
    storeSet('uploads', $list);
}

function uploadsForEmail(string $email): array {
    $sessionUploads = array_values(array_filter(storeGet('uploads'), static function ($u) use ($email) {
        return strcasecmp((string) $u['studentEmail'], $email) === 0;
    }));
    $dbUploads = databaseDocuments(null, databaseUserIdForEmail($email));
    $merged = [];
    $seen = [];
    foreach ($sessionUploads as $u) {
        $seen[(string) ($u['id'] ?? '') . '|' . (string) ($u['storedFile'] ?? '')] = true;
        $merged[] = $u;
    }
    foreach ($dbUploads as $u) {
        $key = (string) ($u['id'] ?? '') . '|' . (string) ($u['storedFile'] ?? '');
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $merged[] = $u;
    }
    return $merged;
}

function uploadsForApplication(int $id): array {
    $userId = 0;
    if (class_exists('DB') && $id > 0) {
        try {
            $stmt = DB::getConnection()->prepare('SELECT user_id FROM applications WHERE application_id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $userId = (int) ($stmt->fetchColumn() ?: 0);
        } catch (Throwable $e) {
            $userId = 0;
        }
    }
    $sessionUploads = array_values(array_filter(storeGet('uploads'), static function ($u) use ($id) {
        return (int) ($u['applicationId'] ?? 0) === $id;
    }));
    $dbUploads = databaseDocuments($id, $userId, $userId > 0);
    $merged = [];
    $seen = [];
    foreach ($sessionUploads as $u) {
        $seen[(string) ($u['storedFile'] ?? '')] = true;
        $merged[] = $u;
    }
    foreach ($dbUploads as $u) {
        $key = (string) ($u['storedFile'] ?? '');
        if ($key !== '' && isset($seen[$key])) {
            continue;
        }
        $merged[] = $u;
    }
    return $merged;
}

function formatFileSize(int $bytes): string {
    if ($bytes <= 0) {
        return '—';
    }
    return number_format($bytes / 1048576, 2) . ' MB';
}

function redirectTo(string $path): void {
    header('Location: ' . url($path));
    exit;
}

function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function pullFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function currentCoordinatorName(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $name = trim((string) ($_SESSION['user']['full_name'] ?? ''));
    return $name !== '' ? $name : ($GLOBALS['mockCoordinator']['name'] ?? 'Graduate Program Coordinator');
}

function appWorkflowDefaults(): array {
    return [
        'workflowState' => [],
        'paymentRecorded' => false,
        'readyForPresentation' => false,
        'result' => '',
        'gradSchoolEndorsed' => false,
        'revisionInstructions' => '',
        'receiptNumber' => '',
        'paymentDate' => '',
        'paymentAmount' => '',
    ];
}

function getWorkflowStageMap(string $track): array {
    $map = [];
    foreach (getWorkflow($track)['stages'] as $stage) {
        $map[$stage['key']] = $stage;
    }
    return $map;
}

function getWorkflowStageLabels(string $track): array {
    $labels = [];
    foreach (getWorkflow($track)['stages'] as $stage) {
        $labels[$stage['key']] = $stage['label'];
    }
    return $labels;
}

function presentationStageOptions(string $track): array {
    return array_values(getStagesForTrack($track));
}

function stageKeyFromLabel(string $label): string {
    $l = strtolower($label);
    if (str_contains($l, 'concept')) {
        return 'concept';
    }
    if (str_contains($l, 'final')) {
        return 'final';
    }
    return 'proposal';
}

function displayStageLabel(array $student): string {
    $track = $student['track'] ?? getTrackForProgram($student['program'] ?? 'MSCS');
    $key = $student['currentStage'] ?? '';
    if ($key === 'not_started' || $key === 'Not Started') {
        return 'Not Started';
    }
    $wf = getWorkflowStageLabels($track);
    if (isset($wf[$key])) {
        return $wf[$key];
    }
    $stages = getStagesForTrack($track);
    return $stages[$key] ?? (string) $key;
}

function coordDeleteForm(string $actionUrl, string $id, string $message, string $title = 'Delete'): string {
    return '<form method="post" action="' . htmlspecialchars($actionUrl) . '" style="display:inline;" data-confirm-form data-confirm-message="' . htmlspecialchars($message) . '">'
        . '<input type="hidden" name="delete_id" value="' . htmlspecialchars($id) . '">'
        . '<button type="button" class="btn btn-sm btn-danger" data-confirm-trigger title="' . htmlspecialchars($title) . '"><i class="fas fa-trash"></i> Delete</button>'
        . '</form>';
}

function postedDeleteId(): string {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return '';
    }
    return trim((string) ($_POST['delete_id'] ?? ''));
}

function coordDeleteLink(string $href, string $message): string {
    return '<button type="button" class="btn btn-sm btn-danger" title="Delete" data-confirm-url="' . htmlspecialchars($href) . '" data-confirm-message="' . htmlspecialchars($message) . '"><i class="fas fa-trash"></i> Delete</button>';
}

function archiveStudentRecord(string $id): void {
    $students = storeGet('students');
    foreach ($students as &$student) {
        if ((string) ($student['id'] ?? '') === $id) {
            $student['archivedAt'] = date('c');
            break;
        }
    }
    unset($student);
    storeSet('students', $students);
}

function archiveApplicationRecord(int $id): void {
    $apps = storeGet('applications');
    foreach ($apps as &$app) {
        if ((int) ($app['id'] ?? 0) === $id) {
            $app['archivedAt'] = date('c');
            break;
        }
    }
    unset($app);
    storeSet('applications', $apps);
}

function deleteApplicationRecord(int $id): void {
    storeSet('applications', array_values(array_filter(storeGet('applications'), static function ($a) use ($id) {
        return (int) $a['id'] !== $id;
    })));
}

function deleteScheduleRecord(string $id): void {
    if (class_exists('DB') && ctype_digit($id)) {
        try {
            $stmt = DB::getConnection()->prepare('DELETE FROM schedules WHERE schedule_id = :id');
            $stmt->execute(['id' => (int) $id]);
            return;
        } catch (Throwable $e) {
        }
    }
    storeSet('schedules', array_values(array_filter(storeGet('schedules'), static function ($s) use ($id) {
        return (string) $s['id'] !== $id;
    })));
}

function deletePanelMemberRecord(string $id): void {
    if (class_exists('DB') && ctype_digit($id)) {
        try {
            $stmt = DB::getConnection()->prepare('DELETE FROM panel_members WHERE panel_member_id = :id');
            $stmt->execute(['id' => (int) $id]);
            return;
        } catch (Throwable $e) {
        }
    }
    storeSet('panels', array_values(array_filter(storeGet('panels'), static function ($p) use ($id) {
        return (string) $p['id'] !== $id;
    })));
}

function normalizePanelMemberRow(array $row): array {
    $firstName = trim((string) ($row['first_name'] ?? ''));
    $middleName = trim((string) ($row['middle_name'] ?? ''));
    $lastName = trim((string) ($row['last_name'] ?? ''));
    $givenName = trim(implode(' ', array_filter([$firstName, $middleName])));
    return [
        'id' => (string) ($row['panel_member_id'] ?? $row['id'] ?? ''),
        'name' => $lastName !== '' ? $lastName . ', ' . $givenName : (string) ($row['name'] ?? ''),
        'first_name' => $firstName,
        'middle_name' => $middleName,
        'last_name' => $lastName,
        'qualification' => (string) ($row['qualification'] ?? ''),
        'email' => (string) ($row['email'] ?? ''),
        'notes' => (string) ($row['notes'] ?? ''),
        'panelSessions' => (int) ($row['panel_sessions'] ?? $row['panelSessions'] ?? 0),
        'availability' => (string) ($row['availability'] ?? 'available'),
    ];
}

function databasePanelMembers(): array {
    if (class_exists('DB')) {
        try {
            $rows = DB::query('SELECT p.*, COUNT(a.assignment_id) AS panel_sessions FROM panel_members p LEFT JOIN schedule_panel_assignments a ON a.panel_member_id = p.panel_member_id GROUP BY p.panel_member_id ORDER BY p.last_name, p.first_name, p.middle_name')->fetchAll();
            return array_map('normalizePanelMemberRow', $rows);
        } catch (Throwable $e) {
        }
    }
    return array_map('normalizePanelMemberRow', storeGet('panels'));
}

function databasePanelParticipation(): array {
    if (!class_exists('DB')) return [];
    try {
        return DB::query(
            'SELECT p.panel_member_id, CONCAT(p.last_name, ", ", p.first_name, IF(p.middle_name IS NULL OR p.middle_name = "", "", CONCAT(" ", p.middle_name))) AS name,
                    p.qualification, COUNT(spa.assignment_id) AS times,
                    MAX(sc.presentation_date) AS last_assignment
             FROM panel_members p
             LEFT JOIN schedule_panel_assignments spa ON spa.panel_member_id = p.panel_member_id
             LEFT JOIN schedules sc ON sc.schedule_id = spa.schedule_id
             GROUP BY p.panel_member_id
             ORDER BY times DESC, p.last_name, p.first_name'
        )->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function findPanelMember(string $id): ?array {
    foreach (storeGet('panels') as $p) {
        if ((string) $p['id'] === $id) {
            return normalizePanelMemberRow($p);
        }
    }
    if (class_exists('DB') && ctype_digit($id)) {
        try {
            $stmt = DB::getConnection()->prepare('SELECT * FROM panel_members WHERE panel_member_id = :id LIMIT 1');
            $stmt->execute(['id' => (int) $id]);
            $row = $stmt->fetch();
            return $row ? normalizePanelMemberRow($row) : null;
        } catch (Throwable $e) {
            return null;
        }
    }
    return null;
}

function addPanelMemberRecord(array $m): array {
    if (class_exists('DB') && isset($m['first_name'], $m['last_name'])) {
        $row = DB::insert('panel_members', [
            'first_name' => formatPersonName($m['first_name']),
            'middle_name' => formatPersonName((string) ($m['middle_name'] ?? '')) ?: null,
            'last_name' => formatPersonName($m['last_name']),
            'qualification' => trim($m['qualification']),
            'email' => trim((string) ($m['email'] ?? '')) ?: null,
            'notes' => trim((string) ($m['notes'] ?? '')) ?: null,
            'availability' => $m['availability'] ?? 'available',
        ]);
        return normalizePanelMemberRow($row);
    }
    $list = storeGet('panels');
    $rec = [
        'id' => 'pm_' . uniqid(),
        'name' => $m['name'],
        'qualification' => $m['qualification'],
        'email' => $m['email'] ?? '',
        'notes' => $m['notes'] ?? '',
        'panelSessions' => (int) ($m['panelSessions'] ?? 0),
        'availability' => $m['availability'] ?? 'available',
    ];
    $list[] = $rec;
    storeSet('panels', $list);
    return $rec;
}

function updatePanelMemberRecord(string $id, array $patch): ?array {
    if (class_exists('DB') && ctype_digit($id)) {
        $data = array_intersect_key($patch, array_flip(['first_name', 'middle_name', 'last_name', 'qualification', 'email', 'notes', 'availability']));
        if ($data) {
            foreach (['first_name', 'middle_name', 'last_name'] as $nameField) {
                if (array_key_exists($nameField, $data)) {
                    $data[$nameField] = formatPersonName((string) $data[$nameField]);
                }
            }
            foreach (['middle_name', 'email', 'notes'] as $nullable) {
                if (array_key_exists($nullable, $data)) {
                    $data[$nullable] = trim((string) $data[$nullable]) ?: null;
                }
            }
            DB::update('panel_members', $data, ['panel_member_id' => (int) $id]);
        }
        return findPanelMember($id);
    }
    $list = storeGet('panels');
    foreach ($list as $i => $p) {
        if ((string) $p['id'] !== $id) {
            continue;
        }
        $list[$i] = array_merge($p, $patch);
        storeSet('panels', $list);
        return $list[$i];
    }
    return null;
}

function findSchedule(string $id): ?array {
    foreach (storeGet('schedules') as $s) {
        if ((string) $s['id'] === $id) {
            return $s;
        }
    }
    if (class_exists('DB') && ctype_digit($id)) {
        try {
            $stmt = DB::getConnection()->prepare(
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
            );
            $stmt->execute(['id' => (int) $id]);
            return $stmt->fetch() ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }
    return null;
}

function databaseSchedules(): array {
    if (!class_exists('DB')) {
        return storeGet('schedules');
    }
    try {
        $rows = DB::query(
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
        )->fetchAll();
        return $rows;
    } catch (Throwable $e) {
        return storeGet('schedules');
    }
}

function databaseSchedulePanelMembers(int $applicationId): array {
    if (!class_exists('DB') || $applicationId <= 0) return [];
    try {
        return DB::query(
                'SELECT pm.*, spa.panel_role
             FROM schedules sc
             INNER JOIN schedule_panel_assignments spa ON spa.schedule_id = sc.schedule_id
             INNER JOIN panel_members pm ON pm.panel_member_id = spa.panel_member_id
             WHERE sc.application_id = :application_id
             ORDER BY spa.assignment_id'
        , ['application_id' => $applicationId])->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function schedulesForEmail(string $email): array {
    if (class_exists('DB') && $email !== '') {
        try {
            $stmt = DB::getConnection()->prepare('SELECT sc.schedule_id AS id, sc.application_id AS applicationId, u.email AS studentEmail, a.presentation_stage AS stage, sc.presentation_date AS date, sc.start_time AS time, sc.venue, sc.status FROM schedules sc INNER JOIN applications a ON a.application_id = sc.application_id INNER JOIN users u ON u.user_id = a.user_id WHERE u.email = :email ORDER BY sc.presentation_date, sc.start_time');
            $stmt->execute(['email' => $email]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
        }
    }
    return array_values(array_filter(storeGet('schedules'), static function ($s) use ($email) {
        return strcasecmp((string) ($s['studentEmail'] ?? ''), $email) === 0;
    }));
}

function addScheduleRecord(array $s): array {
    if (class_exists('DB') && !empty($s['applicationId'])) {
        $date = date('Y-m-d', strtotime((string) ($s['date'] ?? '')));
        $time = date('H:i:s', strtotime((string) ($s['time'] ?? '')));
        $coordinator = (int) (DB::getConnection()->query("SELECT user_id FROM users WHERE role = 'coordinator' ORDER BY user_id LIMIT 1")->fetchColumn() ?: 0);
        $pdo = DB::getConnection();
        $adviserStmt = $pdo->prepare('SELECT adviser_id FROM advisor_pool WHERE name = :name LIMIT 1');
        $adviserStmt->execute(['name' => trim((string) ($s['adviser'] ?? ''))]);
        $adviserId = (int) ($adviserStmt->fetchColumn() ?: 0);
        $documentorParts = array_map('trim', explode(',', (string) ($s['documentor'] ?? ''), 2));
        $documentorLast = $documentorParts[0] ?? '';
        $documentorGiven = array_values(array_filter(preg_split('/\s+/', $documentorParts[1] ?? '') ?: []));
        $documentorFirst = array_shift($documentorGiven) ?: null;
        $documentorMiddle = $documentorGiven ? implode(' ', $documentorGiven) : null;
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO schedules (application_id, managed_by_user_id, presentation_date, start_time, venue, adviser_id, documentor_first_name, documentor_middle_name, documentor_last_name, status) VALUES (:application_id, :managed_by_user_id, :presentation_date, :start_time, :venue, :adviser_id, :documentor_first_name, :documentor_middle_name, :documentor_last_name, :status)');
            $stmt->execute([
                'application_id' => (int) $s['applicationId'],
                'managed_by_user_id' => $coordinator,
                'presentation_date' => $date,
                'start_time' => $time,
                'venue' => trim((string) ($s['venue'] ?? '')),
                'adviser_id' => $adviserId > 0 ? $adviserId : null,
                'documentor_first_name' => $documentorFirst,
                'documentor_middle_name' => $documentorMiddle,
                'documentor_last_name' => $documentorLast !== '' ? $documentorLast : null,
                'status' => ($s['status'] ?? 'pending') === 'confirmed' ? 'confirmed' : 'pending',
            ]);
            $scheduleId = (int) $pdo->lastInsertId();
            $memberStmt = $pdo->prepare('SELECT panel_member_id FROM panel_members WHERE CONCAT(last_name, ", ", first_name, IF(middle_name IS NULL OR middle_name = "", "", CONCAT(" ", middle_name))) = :name LIMIT 1');
            $assignmentStmt = $pdo->prepare('INSERT IGNORE INTO schedule_panel_assignments (schedule_id, panel_member_id, panel_role) VALUES (:schedule_id, :panel_member_id, :panel_role)');
            $panelText = (string) ($s['panel'] ?? '');
            $documentorText = trim((string) ($s['documentor'] ?? ''));
            $panelRows = $pdo->query('SELECT panel_member_id, first_name, middle_name, last_name FROM panel_members')->fetchAll();
            foreach ($panelRows as $panelRow) {
                $panelName = trim($panelRow['last_name'] . ', ' . $panelRow['first_name'] . (!empty($panelRow['middle_name']) ? ' ' . $panelRow['middle_name'] : ''));
                if ($panelName === '' || $panelName === $documentorText || !str_contains($panelText, $panelName)) {
                    continue;
                }
                $memberId = (int) $panelRow['panel_member_id'];
                if ($memberId > 0) {
                    $assignmentStmt->execute(['schedule_id' => $scheduleId, 'panel_member_id' => $memberId, 'panel_role' => 'member']);
                }
            }
            if (!empty($s['documentor'])) {
                $memberStmt->execute(['name' => trim((string) $s['documentor'])]);
                $documentorId = (int) ($memberStmt->fetchColumn() ?: 0);
                if ($documentorId > 0) {
                    $assignmentStmt->execute(['schedule_id' => $scheduleId, 'panel_member_id' => $documentorId, 'panel_role' => 'documentor']);
                }
            }
            $pdo->commit();
            return findSchedule((string) $scheduleId) ?: array_merge($s, ['id' => (string) $scheduleId]);
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    $list = storeGet('schedules');
    $rec = array_merge([
        'id' => 'sch_' . uniqid(),
        'studentEmail' => '',
        'studentName' => '',
        'applicationId' => null,
        'stage' => '',
        'date' => '',
        'time' => '',
        'venue' => '',
        'panel' => 'TBD',
        'adviser' => '',
        'documentor' => '',
        'notifications' => [],
        'status' => 'pending',
        'createdAt' => date('c'),
    ], $s);
    $list[] = $rec;
    storeSet('schedules', $list);
    return $rec;
}

function updateScheduleRecord(string $id, array $patch): ?array {
    if (class_exists('DB') && ctype_digit($id)) {
        $data = [];
        if (isset($patch['date'])) $data['presentation_date'] = date('Y-m-d', strtotime((string) $patch['date']));
        if (isset($patch['time'])) $data['start_time'] = date('H:i:s', strtotime((string) $patch['time']));
        if (isset($patch['venue'])) $data['venue'] = trim((string) $patch['venue']);
        if (isset($patch['status'])) $data['status'] = $patch['status'] === 'confirmed' ? 'confirmed' : 'pending';
        if (isset($patch['adviser'])) {
            $adviserName = trim((string) $patch['adviser']);
            if ($adviserName !== '') {
                $adviserStmt = DB::getConnection()->prepare('SELECT adviser_id FROM advisor_pool WHERE name = :name LIMIT 1');
                $adviserStmt->execute(['name' => $adviserName]);
                $data['adviser_id'] = (int) ($adviserStmt->fetchColumn() ?: 0) ?: null;
            }
        }
        if ($data) DB::update('schedules', $data, ['schedule_id' => (int) $id]);
        if (isset($patch['panel'])) {
            $pdo = DB::getConnection();
            $pdo->prepare('DELETE FROM schedule_panel_assignments WHERE schedule_id = :id')->execute(['id' => (int) $id]);
            $assignmentStmt = $pdo->prepare('INSERT IGNORE INTO schedule_panel_assignments (schedule_id, panel_member_id, panel_role) VALUES (:schedule_id, :panel_member_id, "member")');
            $panelText = (string) $patch['panel'];
            $panelRows = $pdo->query('SELECT panel_member_id, first_name, middle_name, last_name FROM panel_members')->fetchAll();
            foreach ($panelRows as $panelRow) {
                $panelName = trim($panelRow['last_name'] . ', ' . $panelRow['first_name'] . (!empty($panelRow['middle_name']) ? ' ' . $panelRow['middle_name'] : ''));
                if ($panelName !== '' && str_contains($panelText, $panelName)) {
                    $assignmentStmt->execute(['schedule_id' => (int) $id, 'panel_member_id' => (int) $panelRow['panel_member_id']]);
                }
            }
        }
        return findSchedule($id);
    }
    $list = storeGet('schedules');
    foreach ($list as $i => $s) {
        if ((string) $s['id'] !== $id) {
            continue;
        }
        $list[$i] = array_merge($s, $patch);
        storeSet('schedules', $list);
        return $list[$i];
    }
    return null;
}

function panelSelectOptions(): array {
    $opts = [];
    foreach (databasePanelMembers() as $p) {
        $opts[] = $p['name'] . ($p['qualification'] ? ' (' . $p['qualification'] . ')' : '');
    }
    return $opts ?: [
        'Dr. Juan Dela Cruz (PhD in Computer Science)',
        'Dr. Ana Reyes (PhD in Information Technology)',
        'Prof. Miguel Santos (MS in Computer Science)',
        'Prof. Lisa Fernandez (MS in Information Technology)',
        'Dr. Maria Santos (PhD in Computer Science)',
    ];
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
function workflowCurrentIndex(array $progress, array $stages): int {
    $count = count($stages);
    if ($count === 0) {
        return 0;
    }
    for ($i = $count - 1; $i >= 0; $i--) {
        $p = $progress[$i] ?? [];
        $st = $p['stageStatus'] ?? 'not_started';
        if (!in_array($st, ['not_started', 'pending', 'draft', ''], true)) {
            if (!in_array($st, ['completed', 'approved'], true)) {
                return $i;
            }
            return min($i + 1, $count - 1);
        }
    }
    return 0;
}

/**
 * Stage visual state for the stepper: 'done' when completed/approved or already
 * passed by the current stage, 'active' for the current stage, otherwise 'pending'.
 */
function workflowStageState(array $p, int $idx, int $currentIdx): string {
    $st = $p['stageStatus'] ?? 'not_started';
    if (in_array($st, ['completed', 'approved'], true) || $idx < $currentIdx) {
        return 'done';
    }
    return $idx === $currentIdx ? 'active' : 'pending';
}

function blankStageProgress(array $stage): array {
    $pending = ['status' => 'pending'];
    return [
        'stageKey' => $stage['key'],
        'stageStatus' => 'not_started',
        'paper' => $pending,
        'adviserEndorsement' => $pending,
        'coordReview' => $pending,
        'gradSchoolEndorsement' => $pending,
        'payment' => $pending,
        'readyForPresentation' => $pending,
        'presentation' => $pending,
        'result' => $pending,
        'coordinatorComments' => '',
    ];
}

function getStudentProgress(string $email, string $track): array {
    $stages = getWorkflow($track)['stages'];
    $apps = array_values(array_filter(storeGet('applications'), static function ($a) use ($email) {
        return strcasecmp((string) $a['studentEmail'], $email) === 0;
    }));
    $uploads = uploadsForEmail($email);
    $schedules = schedulesForEmail($email);

    if (class_exists('DB')) {
        try {
            $stmt = DB::getConnection()->prepare('SELECT a.application_id AS id, a.presentation_stage AS stage, a.paper_title AS title, a.status, a.coordinator_comment AS coordinatorComment, a.grad_school_endorsed AS gradSchoolEndorsed, a.payment_recorded AS paymentRecorded, a.receipt_number AS receiptNumber, a.payment_date AS paymentDate, a.payment_amount AS paymentAmount, a.ready_for_presentation AS readyForPresentation, a.workflow_state AS workflowState, a.result AS result, a.submitted_at AS date FROM applications a INNER JOIN users u ON u.user_id = a.user_id WHERE u.email = :email AND a.archived_at IS NULL ORDER BY a.submitted_at ASC');
            $stmt->execute(['email' => $email]);
            $dbApps = $stmt->fetchAll();
            if ($dbApps) {
                $apps = array_map(static function ($app) use ($email, $track) { $app['studentEmail'] = $email; $app['track'] = $track; $app['stageKey'] = stageKeyFromLabel($app['stage']); $app['workflowState'] = json_decode((string) ($app['workflowState'] ?? '[]'), true) ?: []; return $app; }, $dbApps);
                $docStmt = DB::getConnection()->prepare('SELECT d.application_id AS applicationId, d.original_name AS fileName, d.document_type AS docType, d.stage, d.file_size AS size, d.uploaded_at AS date, d.status, d.stored_name AS storedFile, d.mime_type AS mimeType FROM application_documents d INNER JOIN users u ON u.user_id = d.user_id WHERE u.email = :email ORDER BY d.uploaded_at ASC');
                $docStmt->execute(['email' => $email]);
                $dbUploads = $docStmt->fetchAll();
                if ($dbUploads) $uploads = $dbUploads;
            }
        } catch (Throwable $e) {
        }
    }

    $rows = [];
    $activeStageIndex = -1;
    foreach ($apps as $app) {
        $appStageKey = $app['stageKey'] ?? stageKeyFromLabel($app['stage'] ?? '');
        foreach ($stages as $stageIndex => $stage) {
            if ($appStageKey === $stage['key']) {
                $activeStageIndex = max($activeStageIndex, $stageIndex);
                break;
            }
        }
    }

    foreach ($stages as $stageIndex => $stage) {
        $app = null;
        foreach ($apps as $a) {
            $key = $a['stageKey'] ?? stageKeyFromLabel($a['stage'] ?? '');
            if ($key === $stage['key'] || strcasecmp((string) ($a['stage'] ?? ''), $stage['label']) === 0) {
                $app = $a;
            }
        }
        if (!$app) {
            foreach ($apps as $a) {
                $key = $a['stageKey'] ?? stageKeyFromLabel($a['stage'] ?? '');
                if ($key === $stage['key']) {
                    $app = $a;
                    break;
                }
            }
        }
        if (!$app) {
            $blank = blankStageProgress($stage);
            if ($activeStageIndex > $stageIndex) {
                $blank['stageStatus'] = 'completed';
                $blank['paper'] = ['status' => 'done'];
                $blank['adviserEndorsement'] = ['status' => 'done'];
                $blank['coordReview'] = ['status' => 'done', 'comment' => ''];
                $blank['gradSchoolEndorsement'] = ['status' => 'done'];
                $blank['payment'] = ['status' => 'done'];
                $blank['readyForPresentation'] = ['status' => 'done'];
                $blank['presentation'] = ['status' => 'done'];
                $blank['result'] = ['status' => 'done', 'value' => 'approved'];
            }
            $rows[] = $blank;
            continue;
        }

        $wf = $app['workflowState'] ?? [];
        $paperUpload = null;
        $adviserUpload = null;
        $paymentUpload = null;
        foreach ($uploads as $u) {
            $sameStage = stripos((string) $u['stage'], $stage['shortLabel'] ?? $stage['label']) !== false
                || stripos((string) $u['stage'], $stage['label']) !== false
                || stageKeyFromLabel((string) $u['stage']) === $stage['key'];
            if (!$sameStage) {
                continue;
            }
            $kind = classifyUploadDocType((string) $u['docType']);
            if ($kind === 'receipt') {
                $paymentUpload = $u;
            } elseif ($kind === 'adviser_endorsement') {
                $adviserUpload = $u;
            } else {
                $paperUpload = $u;
            }
        }

        $paperWf = $wf['paper'] ?? '';
        $advWf = $wf['adviser_endorsement'] ?? '';
        if ($paperUpload && in_array($paperUpload['status'] ?? '', ['verified', 'approved'], true)) {
            $paperWf = 'verified';
        } elseif ($paperUpload && ($paperUpload['status'] ?? '') === 'incomplete') {
            $paperWf = 'incomplete';
        }
        if ($adviserUpload && in_array($adviserUpload['status'] ?? '', ['verified', 'approved'], true)) {
            $advWf = 'verified';
        } elseif ($adviserUpload && ($adviserUpload['status'] ?? '') === 'incomplete') {
            $advWf = 'incomplete';
        }
        $paperStatus = $paperWf === 'verified' ? 'done' : ($paperWf === 'incomplete' ? 'flagged' : ($paperUpload ? 'done' : 'pending'));
        $adviserStatus = $advWf === 'verified' ? 'done' : ($advWf === 'incomplete' ? 'flagged' : ($adviserUpload ? 'done' : 'pending'));
        $coordStatus = ($paperWf === 'incomplete' || $advWf === 'incomplete') ? 'flagged' : (($paperWf === 'verified' && $advWf === 'verified') ? 'done' : 'pending');

        $schedule = null;
        if (!empty($wf['presentation_date'])) {
            $schedule = [
                'date' => (string) ($wf['presentation_date'] ?? ''),
                'time' => (string) ($wf['presentation_time'] ?? ''),
                'venue' => (string) ($wf['presentation_venue'] ?? ''),
                'panel' => (string) ($wf['presentation_panel'] ?? ''),
                'applicationId' => $app['id'] ?? null,
            ];
        } else {
            foreach ($schedules as $s) {
                if ((int) ($s['applicationId'] ?? 0) === (int) $app['id'] || stripos((string) $s['stage'], $stage['shortLabel'] ?? '') !== false) {
                    $schedule = $s;
                    break;
                }
            }
        }

        $resultVal = $app['result'] ?? '';
        $rows[] = [
            'stageKey' => $stage['key'],
            'stageStatus' => $app['status'],
            'paper' => ['status' => $paperStatus, 'submitted' => $paperUpload['date'] ?? ''],
            'adviserEndorsement' => ['status' => $adviserStatus, 'submitted' => $adviserUpload['date'] ?? ''],
            'coordReview' => ['status' => $coordStatus, 'comment' => $coordStatus === 'flagged' ? ($app['coordinatorComment'] ?? '') : ''],
            'gradSchoolEndorsement' => ['status' => !empty($app['gradSchoolEndorsed']) ? 'done' : 'pending'],
            'payment' => ['status' => !empty($app['paymentRecorded']) ? 'done' : 'pending', 'submitted' => !empty($app['paymentDate']) ? $app['paymentDate'] : ($paymentUpload['date'] ?? '')],
            'readyForPresentation' => ['status' => !empty($app['readyForPresentation']) ? 'done' : 'pending'],
            'presentation' => [
                'status' => $schedule && !empty($schedule['date']) ? 'done' : 'pending',
                'date' => $schedule['date'] ?? '',
                'time' => $schedule['time'] ?? '',
                'venue' => $schedule['venue'] ?? '',
                'panel' => $schedule['panel'] ?? '',
            ],
            'result' => [
                'status' => $resultVal ? 'done' : 'pending',
                'value' => $resultVal === 'approved' ? 'approved' : ($resultVal === 'requires_revision' ? 'revision' : ''),
            ],
            'coordinatorComments' => $app['coordinatorComment'] ?? '',
        ];
    }

    return $rows;
}

function demoProgressForTrack(string $track): array {
    if ($track === 'capstone') {
        return demoCapstoneProgress();
    }
    if ($track === 'seminar') {
        return demoSeminarProgress();
    }
    return demoThesisProgress();
}

function demoThesisProgress(): array {
    return [
        [
            'stageKey' => 'concept', 'stageStatus' => 'completed',
            'paper' => ['status' => 'done', 'submitted' => 'Nov 10, 2024'],
            'adviserEndorsement' => ['status' => 'done', 'submitted' => 'Nov 08, 2024'],
            'coordReview' => ['status' => 'done', 'comment' => ''],
            'gradSchoolEndorsement' => ['status' => 'done'],
            'payment' => ['status' => 'done', 'submitted' => 'Nov 22, 2024'],
            'readyForPresentation' => ['status' => 'done'],
            'presentation' => ['status' => 'done', 'date' => 'Dec 05, 2024', 'time' => '9:00 AM', 'venue' => 'CSITE Seminar Room', 'panel' => 'Dela Cruz, Reyes, Santos'],
            'result' => ['status' => 'done', 'value' => 'approved'],
            'coordinatorComments' => '',
        ],
        [
            'stageKey' => 'proposal', 'stageStatus' => 'under_review',
            'paper' => ['status' => 'done', 'submitted' => 'Mar 15, 2025'],
            'adviserEndorsement' => ['status' => 'done', 'submitted' => 'Mar 14, 2025'],
            'coordReview' => ['status' => 'flagged', 'comment' => 'Please complete your review of related literature section.'],
            'gradSchoolEndorsement' => ['status' => 'pending'],
            'payment' => ['status' => 'pending', 'submitted' => ''],
            'readyForPresentation' => ['status' => 'pending'],
            'presentation' => ['status' => 'pending', 'date' => '', 'time' => '', 'venue' => '', 'panel' => ''],
            'result' => ['status' => 'pending', 'value' => ''],
            'coordinatorComments' => 'Your proposal looks promising. Please address the incomplete literature review before we can proceed to Graduate School endorsement.',
        ],
        [
            'stageKey' => 'final', 'stageStatus' => 'not_started',
            'paper' => ['status' => 'pending', 'submitted' => ''],
            'adviserEndorsement' => ['status' => 'pending', 'submitted' => ''],
            'coordReview' => ['status' => 'pending', 'comment' => ''],
            'gradSchoolEndorsement' => ['status' => 'pending'],
            'payment' => ['status' => 'pending', 'submitted' => ''],
            'readyForPresentation' => ['status' => 'pending'],
            'presentation' => ['status' => 'pending', 'date' => '', 'time' => '', 'venue' => '', 'panel' => ''],
            'result' => ['status' => 'pending', 'value' => ''],
            'coordinatorComments' => '',
        ],
    ];
}

function demoCapstoneProgress(): array {
    return [
        [
            'stageKey' => 'proposal', 'stageStatus' => 'ready_for_presentation',
            'paper' => ['status' => 'done', 'submitted' => 'Feb 20, 2025'],
            'adviserEndorsement' => ['status' => 'done', 'submitted' => 'Feb 18, 2025'],
            'coordReview' => ['status' => 'done', 'comment' => ''],
            'gradSchoolEndorsement' => ['status' => 'done'],
            'payment' => ['status' => 'done', 'submitted' => 'Mar 05, 2025'],
            'readyForPresentation' => ['status' => 'done'],
            'presentation' => ['status' => 'pending', 'date' => 'Apr 20, 2025', 'time' => '10:00 AM', 'venue' => 'CSITE Seminar Room', 'panel' => 'TBD'],
            'result' => ['status' => 'pending', 'value' => ''],
            'coordinatorComments' => '',
        ],
        [
            'stageKey' => 'final', 'stageStatus' => 'not_started',
            'paper' => ['status' => 'pending', 'submitted' => ''],
            'adviserEndorsement' => ['status' => 'pending', 'submitted' => ''],
            'coordReview' => ['status' => 'pending', 'comment' => ''],
            'gradSchoolEndorsement' => ['status' => 'pending'],
            'payment' => ['status' => 'pending', 'submitted' => ''],
            'readyForPresentation' => ['status' => 'pending'],
            'presentation' => ['status' => 'pending', 'date' => '', 'time' => '', 'venue' => '', 'panel' => ''],
            'result' => ['status' => 'pending', 'value' => ''],
            'coordinatorComments' => '',
        ],
    ];
}

function demoSeminarProgress(): array {
    return [
        [
            'stageKey' => 'proposal', 'stageStatus' => 'submitted',
            'paper' => ['status' => 'done', 'submitted' => 'Mar 01, 2025'],
            'adviserEndorsement' => ['status' => 'done', 'submitted' => 'Feb 28, 2025'],
            'coordReview' => ['status' => 'pending', 'comment' => ''],
            'gradSchoolEndorsement' => ['status' => 'pending'],
            'payment' => ['status' => 'pending', 'submitted' => ''],
            'readyForPresentation' => ['status' => 'pending'],
            'presentation' => ['status' => 'pending', 'date' => '', 'time' => '', 'venue' => '', 'panel' => ''],
            'result' => ['status' => 'pending', 'value' => ''],
            'coordinatorComments' => '',
        ],
        [
            'stageKey' => 'final', 'stageStatus' => 'not_started',
            'paper' => ['status' => 'pending', 'submitted' => ''],
            'adviserEndorsement' => ['status' => 'pending', 'submitted' => ''],
            'coordReview' => ['status' => 'pending', 'comment' => ''],
            'gradSchoolEndorsement' => ['status' => 'pending'],
            'payment' => ['status' => 'pending', 'submitted' => ''],
            'readyForPresentation' => ['status' => 'pending'],
            'presentation' => ['status' => 'pending', 'date' => '', 'time' => '', 'venue' => '', 'panel' => ''],
            'result' => ['status' => 'pending', 'value' => ''],
            'coordinatorComments' => '',
        ],
    ];
}

function stepIconHtml(string $state): string {
    if ($state === 'done') {
        return '<span class="step-icon step-icon-done"><i class="fas fa-check"></i></span>';
    }
    if ($state === 'flagged') {
        return '<span class="step-icon step-icon-flagged"><i class="fas fa-exclamation"></i></span>';
    }
    if ($state === 'active') {
        return '<span class="step-icon step-icon-active"><span class="step-icon-dot"></span></span>';
    }
    return '<span class="step-icon step-icon-pending"></span>';
}

initPrototypeStore();
$mockStudent = currentStudentProfile($mockStudent);
