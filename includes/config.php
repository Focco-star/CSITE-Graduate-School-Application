<?php


define('SITE_NAME', 'CSITE Graduate School Application');
define('SITE_SHORT', 'CSITE Grad App');
define('SITE_TAGLINE', 'Capstone & Thesis Presentation Application System');
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
    'name'         => 'Robbie Ryan A. Torres',
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
    'name' => 'Ma\'am Precious Opinion',
    'role' => 'Graduate Program Coordinator – CSITE',
    'email'=> 'gpc-csite@adzu.edu.ph',
];

function statusBadge(string $status): string {
    $info = STATUSES[$status] ?? ['label' => ucfirst($status), 'class' => 'status-pending'];
    return '<span class="status-badge ' . $info['class'] . '">' . htmlspecialchars($info['label']) . '</span>';
}

function getStagesForTrack(string $track): array {
    if ($track === 'capstone') return STAGES_CAPSTONE;
    if ($track === 'seminar') return STAGES_SEMINAR;
    return STAGES_THESIS;
}

function getTrackForProgram(string $program): string {
    $capstone = ['MIT', 'MSED_CHEM', 'MSED_GS', 'MSED_BIO', 'MSED_PHY', 'MLIS'];
    if (in_array($program, $capstone, true)) return 'capstone';
    if ($program === 'MATH') return 'seminar';
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
        return $capstone;
    }
    if ($track === 'seminar') {
        return $seminar;
    }
    return $thesis;
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
    $mi = trim((string) ($s['middleInitial'] ?? ''));
    $mid = $mi !== '' ? ' ' . rtrim($mi, '.') . '.' : '';
    return trim(($s['firstName'] ?? '') . $mid . ' ' . ($s['lastName'] ?? ''));
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
    $id = (string) ($_SESSION['current_student_id'] ?? '');
    $s = $id !== '' ? findStudentById($id) : null;
    $email = currentStudentEmail($fallback);
    if (!$s) {
        $s = findStudentByEmail($email);
    }
    if (!$s) {
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
        'firstName' => $input['firstName'],
        'lastName' => $input['lastName'],
        'middleInitial' => $input['middleInitial'] ?? '',
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
    foreach (storeGet('applications') as $a) {
        if ((int) $a['id'] === $id) {
            return $a;
        }
    }
    return null;
}

function latestApplicationForEmail(string $email): ?array {
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
        'id' => 'up_' . uniqid(),
        'applicationId' => $u['applicationId'] ?? null,
        'studentEmail' => $u['studentEmail'],
        'fileName' => $u['fileName'],
        'docType' => $u['docType'],
        'stage' => $u['stage'],
        'size' => (int) ($u['size'] ?? 0),
        'date' => date('Y-m-d'),
        'status' => 'submitted',
        'notes' => $u['notes'] ?? '',
        'storedFile' => $u['storedFile'] ?? '',
        'mimeType' => $u['mimeType'] ?? '',
    ];
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

function findUpload(string $id): ?array {
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
        $doc = strtolower((string) $u['docType']);
        $key = 'paper';
        if (str_contains($doc, 'receipt') || str_contains($doc, 'payment')) {
            $key = 'receipt';
        } elseif (str_contains($doc, 'endorsement') || str_contains($doc, 'adviser')) {
            $key = 'adviser_endorsement';
        }
        if (!empty($workflowState[$key]) && in_array($workflowState[$key], ['verified', 'incomplete', 'submitted'], true)) {
            $u['status'] = $workflowState[$key];
        }
    }
    unset($u);
    storeSet('uploads', $list);
}

function uploadsForEmail(string $email): array {
    return array_values(array_filter(storeGet('uploads'), static function ($u) use ($email) {
        return strcasecmp((string) $u['studentEmail'], $email) === 0;
    }));
}

function uploadsForApplication(int $id): array {
    return array_values(array_filter(storeGet('uploads'), static function ($u) use ($id) {
        return (int) ($u['applicationId'] ?? 0) === $id;
    }));
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
    if ($track === 'capstone') {
        return array_values(STAGES_CAPSTONE);
    }
    if ($track === 'seminar') {
        return array_values(STAGES_SEMINAR);
    }
    return array_values(STAGES_THESIS);
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
    return '<form method="post" action="' . htmlspecialchars($actionUrl) . '" style="display:inline;" onsubmit="return confirm(' . htmlspecialchars(json_encode($message), ENT_QUOTES) . ');">'
        . '<input type="hidden" name="delete_id" value="' . htmlspecialchars($id) . '">'
        . '<button type="submit" class="btn btn-sm btn-danger" title="' . htmlspecialchars($title) . '"><i class="fas fa-trash"></i> Delete</button>'
        . '</form>';
}

function postedDeleteId(): string {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return '';
    }
    return trim((string) ($_POST['delete_id'] ?? ''));
}

function coordDeleteLink(string $href, string $message): string {
    return '<a href="' . htmlspecialchars($href) . '" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm(' . htmlspecialchars(json_encode($message), ENT_QUOTES) . ');"><i class="fas fa-trash"></i> Delete</a>';
}

function deleteStudentRecord(string $id): void {
    storeSet('students', array_values(array_filter(storeGet('students'), static function ($s) use ($id) {
        return (string) $s['id'] !== $id;
    })));
}

function deleteApplicationRecord(int $id): void {
    storeSet('applications', array_values(array_filter(storeGet('applications'), static function ($a) use ($id) {
        return (int) $a['id'] !== $id;
    })));
}

function deleteScheduleRecord(string $id): void {
    storeSet('schedules', array_values(array_filter(storeGet('schedules'), static function ($s) use ($id) {
        return (string) $s['id'] !== $id;
    })));
}

function deletePanelMemberRecord(string $id): void {
    storeSet('panels', array_values(array_filter(storeGet('panels'), static function ($p) use ($id) {
        return (string) $p['id'] !== $id;
    })));
}

function findPanelMember(string $id): ?array {
    foreach (storeGet('panels') as $p) {
        if ((string) $p['id'] === $id) {
            return $p;
        }
    }
    return null;
}

function addPanelMemberRecord(array $m): array {
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
    return null;
}

function schedulesForEmail(string $email): array {
    return array_values(array_filter(storeGet('schedules'), static function ($s) use ($email) {
        return strcasecmp((string) ($s['studentEmail'] ?? ''), $email) === 0;
    }));
}

function addScheduleRecord(array $s): array {
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
    foreach (storeGet('panels') as $p) {
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

    $rows = [];
    foreach ($stages as $stage) {
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
            $rows[] = blankStageProgress($stage);
            continue;
        }

        $wf = $app['workflowState'] ?? [];
        $paperUpload = null;
        $adviserUpload = null;
        $paymentUpload = null;
        foreach ($uploads as $u) {
            $doc = strtolower((string) $u['docType']);
            $sameStage = stripos((string) $u['stage'], $stage['shortLabel'] ?? $stage['label']) !== false
                || stripos((string) $u['stage'], $stage['label']) !== false
                || stageKeyFromLabel((string) $u['stage']) === $stage['key'];
            if (!$sameStage) {
                continue;
            }
            if (str_contains($doc, 'receipt') || str_contains($doc, 'payment')) {
                $paymentUpload = $u;
            } elseif (str_contains($doc, 'endorsement') || str_contains($doc, 'adviser')) {
                $adviserUpload = $u;
            } elseif (str_contains($doc, 'paper') || str_contains($doc, 'proposal') || str_contains($doc, 'thesis') || str_contains($doc, 'capstone') || str_contains($doc, 'seminar')) {
                $paperUpload = $u;
            }
        }

        $paperWf = $wf['paper'] ?? '';
        $advWf = $wf['adviser_endorsement'] ?? '';
        $paperStatus = $paperWf === 'verified' ? 'done' : ($paperWf === 'incomplete' ? 'flagged' : ($paperUpload ? 'done' : 'pending'));
        $adviserStatus = $advWf === 'verified' ? 'done' : ($advWf === 'incomplete' ? 'flagged' : ($adviserUpload ? 'done' : 'pending'));
        $coordStatus = ($paperWf === 'incomplete' || $advWf === 'incomplete') ? 'flagged' : (($paperWf === 'verified' && $advWf === 'verified') ? 'done' : 'pending');

        $schedule = null;
        foreach ($schedules as $s) {
            if ((int) ($s['applicationId'] ?? 0) === (int) $app['id'] || stripos((string) $s['stage'], $stage['shortLabel'] ?? '') !== false) {
                $schedule = $s;
                break;
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
            'payment' => ['status' => !empty($app['paymentRecorded']) ? 'done' : 'pending', 'submitted' => $app['paymentDate'] ?? ($paymentUpload['date'] ?? '')],
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

    $hasAny = false;
    foreach ($rows as $r) {
        if ($r['stageStatus'] !== 'not_started') {
            $hasAny = true;
            break;
        }
    }
    $demoEmails = ['1@adzu.edu.ph', 'rtorres@adzu.edu.ph', '2@adzu.edu.ph', 'jgler@adzu.edu.ph', '3@adzu.edu.ph'];
    $isDemo = in_array(strtolower($email), $demoEmails, true);
    if (!$hasAny && $isDemo) {
        return demoProgressForTrack($track);
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
