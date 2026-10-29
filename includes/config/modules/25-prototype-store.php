<?php
/* CSITE configuration module: Session-backed prototype store used by demo/fallback pages. */

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
