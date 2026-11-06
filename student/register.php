<?php /* Opens the PHP block for the student profile setup page. */ ?>
<?php
// Loads the shared config with session, constants, and helpers.
require_once __DIR__ . '/../includes/config/config.php';
// Loads the DB helper used to insert users and students.
require_once __DIR__ . '/../includes/config/db.php';
// Loads the Google sign-in helpers used for prefilling the form.
require_once __DIR__ . '/../includes/integrations/google.php';

// A profile verified by Google a moment ago (student/google_auth.php). It only
// fills the name and email; the student still chooses the password and the
// rest of their details.
// Reads a Google-verified profile from the session, if there is one.
$googleProfile = googleSessionProfile();
// Starts with an empty error message for the registration form.
$registerError = '';
// Only runs the registration code when the form was submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cleans and formats the typed first name.
    $first = formatPersonName($_POST['firstName'] ?? '');
    // Cleans and formats the typed last name.
    $last = formatPersonName($_POST['lastName'] ?? '');
    // Reads the ADZU email, trims spaces, and stores it in lowercase.
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    // Reads the chosen program code from the form.
    $program = trim((string) ($_POST['program'] ?? ''));
    // Reads the first password field.
    $password = $_POST['password'] ?? '';
    // Reads the password confirmation field.
    $confirm = $_POST['confirmPassword'] ?? '';
    // Reads the birthdate value, trimmed.
    $birthdate = trim((string) ($_POST['birthdate'] ?? ''));
    // Reads the chosen biological sex and normalises it.
    $sex = normalizeSex($_POST['sex'] ?? '');
    // Reads the middle initial, uppercased and trimmed.
    $middleInitial = strtoupper(trim((string) ($_POST['middleInitial'] ?? '')));
    // Reads the typed ADZU ID number.
    $studentIdRaw = trim((string) ($_POST['studentId'] ?? ''));
    // Reads the track chosen by the student.
    $track = trim((string) ($_POST['track'] ?? ''));

    // When a program has only one track, use it without asking the student.
    if ($program !== '' && isset(PROGRAMS[$program])) {
        // Reads the tracks allowed for the chosen program.
        $programTracks = getTracksForProgram($program);
        // Fills in the only track when there is just one.
        if (count($programTracks) === 1) {
            // Uses the program's single track.
            $track = $programTracks[0];
        // Closes the single-track check.
        }
    // Closes the program check.
    }

    // Collects every required field that was left empty.
    $missingFields = [];
    // Checks each required text field in form order.
    foreach ([
        'First Name'        => $first,
        'Last Name'         => $last,
        'Middle Initial'    => $middleInitial,
        'Birthdate'         => $birthdate,
        'Sex'               => $sex,
        'Program'           => $program,
        'ADZU Email'        => $email,
        'ADZU ID Number'    => $studentIdRaw,
        'Password'          => $password,
        'Re-enter Password' => $confirm,
    ] as $label => $value) {
        // Adds the label when its value is empty.
        if (trim((string) $value) === '') {
            // Remembers the missing field name.
            $missingFields[] = $label;
        // Closes the empty-value check.
        }
    // Closes the required field loop.
    }
    // Requires a track when the program allows more than one.
    if ($program !== '' && isset(PROGRAMS[$program]) && count(getTracksForProgram($program)) > 1 && $track === '') {
        // Adds the track to the missing fields.
        $missingFields[] = 'Track';
    // Closes the track check.
    }

    // Reports every missing field by name instead of a generic message.
    if ($missingFields) {
        // Copies the list so the message can format it safely.
        $missingNames = $missingFields;
        // Formats one item, two items, or a longer list with "and".
        if (count($missingNames) === 1) {
            // Uses the single field name.
            $missingText = $missingNames[0];
        } elseif (count($missingNames) === 2) {
            // Joins two field names with "and".
            $missingText = $missingNames[0] . ' and ' . $missingNames[1];
        } else {
            // Uses the last field for the final "and" item.
            $lastMissing = array_pop($missingNames);
            // Joins the rest with commas and the last with "and".
            $missingText = implode(', ', $missingNames) . ', and ' . $lastMissing;
        }
        // Builds a readable list of the missing fields.
        $registerError = 'Please complete the following required field' . (count($missingFields) === 1 ? '' : 's') . ': ' . $missingText . '.';
    // Validates the student ID format.
    } elseif (!ctype_digit($studentIdRaw) || strlen($studentIdRaw) < 4 || strlen($studentIdRaw) > 10) {
        // Sets an error about the invalid ADZU ID number.
        $registerError = 'Enter a valid ADZU ID number (4–10 digits).';
    // Checks that the program code exists in PROGRAMS.
    } elseif (!isset(PROGRAMS[$program])) {
        // Sets an error telling the student to pick a valid program.
        $registerError = 'Select a valid program.';
    // Checks the track is allowed for the chosen program.
    } elseif (!in_array($track, getTracksForProgram($program), true)) {
        // Sets an error telling the student to pick their program's track.
        $registerError = 'Select the track your program follows.';
    // Validates the birthdate.
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate) || ageFromBirthdate($birthdate) < 15) {
        // Sets an error about an invalid or future birthdate.
        $registerError = 'Enter a valid birthdate.';
    // Validates the sex value.
    } elseif (!in_array($sex, ['Male', 'Female', 'Intersex'], true)) {
        // Sets an error about the missing sex value.
        $registerError = 'Select your sex.';
    // Validates that the email looks like a real email address.
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Sets an error about the invalid email address.
        $registerError = 'Enter a valid ADZU email address.';
    // Checks that both password fields match.
    } elseif ($password !== $confirm) {
        // Sets an error saying the passwords do not match.
        $registerError = 'Passwords do not match.';
    // Requires at least 5 characters and one number.
    } elseif (strlen($password) < 5 || !preg_match('/\d/', $password)) {
        // Sets an error explaining the password rules.
        $registerError = 'Password must be at least 5 characters and include one number.';
    // All checks passed, so start the database work.
    } else {
        // Starts a try block so errors can roll the database back.
        try {
            // Opens the shared PDO database connection.
            $pdo = DB::getConnection();
            // Adds the account validation columns when they are missing.
            ensureAccountValidationSchema();

            // Looks for an existing user row with the same email.
            $existingUser = DB::find('users', ['email' => $email]);
            // If that email is already registered, refuse the sign-up.
            if ($existingUser) {
                // Throws an error so the catch block can show the message.
                throw new LogicException('This email address is already registered. Sign in with that account instead.');
            // Closes the existing-user check.
            }

            // Builds the full student name from first, last, and initial.
            $fullName = canonicalStudentName($first, $last, $middleInitial);
            // Explains that the official student ID is the typed ADZU number.
            // Official student ID = the ADZU ID number typed by the student.
            // Continues: the email number is only used when the field is missing.
            // Falls back to the numeric part of the ADZU email only when the
            // Continues: for example when an old cached form is submitted.
            // field is missing (e.g. a stale cached form).
            // Converts the typed ADZU ID to an integer, or 0 if missing.
            $postedStudentId = ($studentIdRaw !== '' && ctype_digit($studentIdRaw)) ? (int) $studentIdRaw : 0;
            // Tries to read a student number from the email address.
            $emailStudentId = extractStudentIdFromEmail($email);
            // Picks the typed ID first, otherwise the ID from the email.
            $explicitStudentId = $postedStudentId > 0
                // Uses the typed student ID when it is greater than zero.
                ? $postedStudentId
                // Otherwise uses the number from the email, or 0.
                : (($emailStudentId !== '' && ctype_digit($emailStudentId)) ? (int) $emailStudentId : 0);

            // Starts a transaction so user and student rows save together.
            $pdo->beginTransaction();

            // Starts inserting a new row into the users table.
            $user = DB::insert('users', [
                // Stores the student's full name.
                'full_name' => $fullName,
                // Stores the email used to sign in.
                'email'     => $email,
                // Hashes the password before saving it, never plain text.
                'password'  => password_hash($password, PASSWORD_DEFAULT),
                // Gives the new account the student role.
                'role'      => 'student',
                // Keeps the new account locked until the coordinator validates it.
                'account_status' => 'pending'
            // Closes the user column array and runs the insert.
            ]);

            // Starts the array of columns for the students table.
            $newStudent = [
                // Links the profile to the new user by user_id.
                'user_id'        => $user['user_id'],
                // Stores the first name.
                'first_name'     => $first,
                // Stores the last name.
                'last_name'      => $last,
                // Stores the middle initial without a trailing dot.
                'middle_initial' => rtrim($middleInitial, '.'),
                // Works out the age from the birthdate.
                'age'            => ageFromBirthdate($birthdate),
                // Stores the birthdate.
                'birthdate'      => $birthdate,
                // Stores the biological sex.
                'sex'            => $sex,
                // Keeps the legacy gender column in sync with the sex value.
                'gender'         => $sex,
                // Stores the full program name, falling back to the code.
                'program'        => PROGRAMS[$program] ?? $program,
                // Stores the chosen track: thesis, capstone, or seminar.
                'track'          => $track,
                // Sets the enrollment date to today.
                'enrollment_date'=> date('Y-m-d')
            // Closes the student column array.
            ];
            // Only handles the ID when a real student number was given.
            if ($explicitStudentId > 0) {
                // Checks whether another student already used this ID.
                $taken = DB::find('students', ['student_id' => $explicitStudentId]);
                // If the ID is taken, stop the registration.
                if ($taken) {
                    // Throws an error explaining the ID is already registered.
                    throw new LogicException('This ADZU ID number is already registered. Use your own ID or contact the coordinator.');
                // Closes the taken-ID check.
                }
                // Adds the explicit student ID to the new student row.
                $newStudent['student_id'] = $explicitStudentId;
            // Closes the explicit-ID block.
            }
            // Inner try block for the student insert.
            try {
                // Inserts the student profile row and returns it.
                $student = DB::insert('students', $newStudent);
            // Catches a database error from the student insert.
            } catch (PDOException $e) {
                // Explains the duplicate-key case where the same ID is submitted twice.
                // Race on the same ID submitted twice: report it instead of silently assigning another ID.
                // Detects a duplicate-key error on the same ADZU ID.
                if ($explicitStudentId > 0 && (string) ($e->getCode() ?? '') === '23000') {
                    // Reports the duplicate ID instead of assigning a new one.
                    throw new LogicException('This ADZU ID number is already registered. Use your own ID or contact the coordinator.');
                // Closes the duplicate-key check.
                }
                // Re-throws any other database error to the outer handler.
                throw $e;
            // Closes the inner try/catch.
            }
            // Saves both inserts permanently.
            $pdo->commit();

            // Clears the temporary Google profile from the session.
            googleClearProfile();
            // Queues the message that explains the account needs validation.
            setFlash('success', 'Account created successfully. The coordinator must validate your account before you can sign in.');
            // Sends the student to the login page without signing them in.
            redirectTo('student/login.php');

        // Catches a duplicate-key or other database error while saving.
        } catch (PDOException $e) {
            // Rolls back the transaction if one is still open.
            if (isset($pdo) && $pdo->inTransaction()) {
                // Cancels the half-saved inserts.
                $pdo->rollBack();
            // Closes the rollback if.
            }
            // Shows a friendly message for a duplicate email or ID.
            $registerError = ((string) ($e->getCode() ?? '') === '23000')
                ? 'This email address or ADZU ID number is already registered.'
                : 'The account could not be created. Please try again.';
        // Catches the readable validation errors raised above.
        } catch (Exception $e) {
            // Rolls back the transaction if one is still open.
            if (isset($pdo) && $pdo->inTransaction()) {
                // Cancels the half-saved inserts.
                $pdo->rollBack();
            // Closes the rollback if.
            }
            // Shows the error message on the form.
            $registerError = $e->getMessage();
        // Closes the catch block.
        }
    // Closes the else branch that ran the inserts.
    }
// Closes the POST check.
}

// What the form shows. Posted values win, so a validation error never wipes
// what the student typed; otherwise the verified Google profile fills the
// name and the ADZU email.
// Reads the posted first name so a failed submit keeps it.
$prefillFirst = formatPersonName($_POST['firstName'] ?? '');
// Reads the posted last name for the same reason.
$prefillLast  = formatPersonName($_POST['lastName'] ?? '');
// Reads the posted email for the same reason and keeps it lowercase.
$prefillEmail = strtolower(trim((string) ($_POST['email'] ?? '')));
// Reads the posted middle initial.
$prefillMiddle = strtoupper(trim((string) ($_POST['middleInitial'] ?? '')));
// Reads the posted birthdate.
$prefillBirthdate = trim((string) ($_POST['birthdate'] ?? ''));
// Reads the posted sex.
$prefillSex = normalizeSex($_POST['sex'] ?? '');
// Reads the posted program code.
$prefillProgram = trim((string) ($_POST['program'] ?? ''));
// Reads the posted track code.
$prefillTrack = trim((string) ($_POST['track'] ?? ''));
// Reads the posted ADZU ID number.
$prefillStudentId = trim((string) ($_POST['studentId'] ?? ''));

// If Google verified the student, use it to fill blanks.
if ($googleProfile) {
    // Splits the Google full name into first and last names.
    [$googleFirst, $googleLast] = googleNameParts($googleProfile);
    // Only fill the first name when the student left it empty.
    if ($prefillFirst === '') {
        // Uses the first name from the Google profile.
        $prefillFirst = $googleFirst;
    // Closes the first-name fill.
    }
    // Only fill the last name when it is empty.
    if ($prefillLast === '') {
        // Uses the last name from the Google profile.
        $prefillLast = $googleLast;
    // Closes the last-name fill.
    }
    // Only fill the email when the student left it empty.
    if ($prefillEmail === '') {
        // Uses the verified Google email address.
        $prefillEmail = (string) ($googleProfile['email'] ?? '');
    // Closes the email fill.
    }
// Closes the Google prefill block.
}

// Sets the page title to Student Profile Setup.
$pageTitle = 'Student Profile Setup';
// Tells the layout this is a public page with no sidebar.
$role = 'public';
// Adds the login page body class for the dark background.
$bodyClass = 'login-body';
// Includes the shared header markup and styles.
require_once __DIR__ . '/../includes/layout/header.php';
// Includes the login slideshow panel.
require_once __DIR__ . '/../includes/layout/slideshow.php';

// Starts a map of helper text shown for each track.
$trackHints = [
    // Hint text for the three-presentation thesis track.
    'thesis'   => 'Thesis track — 3 presentations (Concept Paper → Proposal → Final Defense)',
    // Hint text for the two-presentation capstone track.
    'capstone' => 'Capstone track — 2 presentations (Proposal → Final Presentation)',
    // Hint text for the two-presentation seminar paper track.
    'seminar'  => 'Seminar Paper track — 2 presentations (Proposal → Final Paper)',
// Closes the track hints array.
];
// Closes the PHP block before the form HTML.
?>

<?php /* Centred login-style page wrapper for the registration card. */ ?>
<div class="login-page" style="align-items:flex-start;padding-top:3rem;padding-bottom:3rem;">
<?php /*     The card that holds the whole sign-up form. */ ?>
    <div class="login-card register-card">
<?php /*         Holds the AdZU seal image. */ ?>
        <div class="login-logo">
<?php /*             Shows the university seal at the top of the card. */ ?>
            <img src="<?= asset('img/adzu-seal.png') ?>" alt="AdZU Seal">
<?php /*         Closes the logo box. */ ?>
        </div>
<?php /*         Main heading telling the student to complete their profile. */ ?>
        <h1>Student Profile Setup</h1>
<?php /*         Subtitle explaining the form is the last step. */ ?>
        <p class="login-subtitle">Create your account. The coordinator validates it before your first sign-in.</p>

<?php /*         If the previous attempt had an error, show it. */ ?>
        <?php if ($registerError): ?>
<?php /*         Red alert box with the error message. */ ?>
        <div class="alert alert-danger" style="margin-bottom:1rem;"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($registerError) ?></div>
<?php /*         Closes the error alert if. */ ?>
        <?php endif; ?>

<?php /*         Registration form posting back to this same page. */ ?>
        <form class="login-form" action="<?= url('student/register.php') ?>" method="post" data-validate novalidate>
<?php /*             Section label for the personal information fields. */ ?>
            <div class="form-section-label">Personal Information</div>

<?php /*             Two-column grid for the first and last name. */ ?>
            <div class="register-grid-2">
<?php /*                 Group for the first name field. */ ?>
                <div class="form-group">
<?php /*                     Label for the first name input. */ ?>
                    <label for="firstName">First Name *</label>
<?php /*                     Wrapper that puts a user icon inside the input. */ ?>
                    <div class="input-icon-wrap">
<?php /*                         User icon shown inside the first name box. */ ?>
                        <i class="fas fa-user"></i>
<?php /*                         Text input for the first name, prefilled and required. */ ?>
                        <input type="text" id="firstName" name="firstName" placeholder="First name" value="<?= htmlspecialchars($prefillFirst) ?>" autocomplete="given-name" required>
<?php /*                     Closes the icon wrapper. */ ?>
                    </div>
<?php /*                 Closes the first-name group. */ ?>
                </div>
<?php /*                 Group for the last name field. */ ?>
                <div class="form-group">
<?php /*                     Label for the last name input. */ ?>
                    <label for="lastName">Last Name *</label>
<?php /*                     Icon wrapper for the last name box. */ ?>
                    <div class="input-icon-wrap">
<?php /*                         User icon inside the last name box. */ ?>
                        <i class="fas fa-user"></i>
<?php /*                         Text input for the last name, prefilled and required. */ ?>
                        <input type="text" id="lastName" name="lastName" placeholder="Last name" value="<?= htmlspecialchars($prefillLast) ?>" autocomplete="family-name" required>
<?php /*                     Closes the last name icon wrapper. */ ?>
                    </div>
<?php /*                 Closes the last-name group. */ ?>
                </div>
<?php /*             Closes the two-column name grid. */ ?>
            </div>

<?php /*             Three-column grid for initial, age, and gender. */ ?>
            <div class="register-grid-3">
<?php /*                 Group for the middle initial. */ ?>
                <div class="form-group">
<?php /*                     Label for the middle initial input. */ ?>
                    <label for="middleInitial">Middle Initial <span class="required">*</span></label>
<?php /*                     Icon wrapper around the middle initial input. */ ?>
                    <div class="input-icon-wrap">
<?php /*                         User icon inside the middle initial box. */ ?>
                        <i class="fas fa-user"></i>
<?php /*                         Short text input for the middle initial, forced uppercase. */ ?>
                        <input type="text" id="middleInitial" name="middleInitial" placeholder="e.g. R" maxlength="2" style="text-transform:uppercase;" value="<?= htmlspecialchars($prefillMiddle) ?>" required>
<?php /*                     Closes the middle initial wrapper. */ ?>
                    </div>
<?php /*                 Closes the middle initial group. */ ?>
                </div>
<?php /*                 Group for the birthdate field. */ ?>
                <div class="form-group">
<?php /*                     Label for the birthdate input. */ ?>
                    <label for="birthdate">Birthdate *</label>
<?php /*                     Icon wrapper around the birthdate input. */ ?>
                    <div class="input-icon-wrap">
<?php /*                         Calendar icon inside the birthdate box. */ ?>
                        <i class="fas fa-calendar-alt"></i>
<?php /*                         Date input that cannot pick a future date. */ ?>
                        <input type="date" id="birthdate" name="birthdate" max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($prefillBirthdate) ?>" data-birthdate required>
<?php /*                     Closes the birthdate wrapper. */ ?>
                    </div>
<?php /*                 Closes the birthdate group. */ ?>
                </div>
<?php /*                 Group for the biological sex dropdown. */ ?>
                <div class="form-group">
<?php /*                     Label for the sex dropdown. */ ?>
                    <label for="sex">Sex *</label>
<?php /*                     Icon wrapper around the sex select. */ ?>
                    <div class="input-icon-wrap">
<?php /*                         Venus and Mars icon inside the select box. */ ?>
                        <i class="fas fa-venus-mars"></i>
<?php /*                         Dropdown listing the biological sex choices. */ ?>
                        <select id="sex" name="sex" required>
<?php /*                             Placeholder option that cannot be submitted. */ ?>
                            <option value="" disabled <?= $prefillSex === '' ? 'selected' : '' ?>>Select</option>
<?php /*                             One option for each valid sex value. */ ?>
                            <?php foreach (['Male', 'Female', 'Intersex'] as $sexOption): ?>
<?php /*                             Keeps the posted value selected after an error. */ ?>
                            <option value="<?= htmlspecialchars($sexOption) ?>" <?= $prefillSex === $sexOption ? 'selected' : '' ?>><?= htmlspecialchars($sexOption) ?></option>
<?php /*                             Ends the sex option loop. */ ?>
                            <?php endforeach; ?>
<?php /*                         Closes the sex dropdown. */ ?>
                        </select>
<?php /*                     Closes the sex icon wrapper. */ ?>
                    </div>
<?php /*                 Closes the sex group. */ ?>
                </div>
<?php /*             Closes the three-column grid. */ ?>
            </div>

<?php /*             Section label for the academic program part. */ ?>
            <div class="form-section-label">Academic Program</div>
<?php /*             Group for the program picker. */ ?>
            <div class="form-group">
<?php /*                 Label saying a program must be chosen. */ ?>
                <label>Program *</label>
<?php /*                 Hidden input that stores the chosen program code. */ ?>
                <input type="hidden" name="program" id="programValue" value="<?= htmlspecialchars($prefillProgram) ?>" required>
<?php /*                 Wrapper for the custom program dropdown. */ ?>
                <div class="program-picker">
<?php /*                     Button that opens the program list. */ ?>
                    <button type="button" class="program-picker-toggle" id="programToggle">
<?php /*                         Graduation cap icon in the picker button. */ ?>
                        <i class="fas fa-graduation-cap" style="color:var(--adzu-gold);font-size:0.9rem;"></i>
<?php /*                         Text that shows the chosen program or Select your program. */ ?>
                        <span id="programLabel" style="flex:1;font-size:0.9rem;">Select your program</span>
<?php /*                         Chevron icon hinting the list opens downward. */ ?>
                        <i class="fas fa-chevron-down" style="color:rgba(255,255,255,0.4);font-size:0.75rem;"></i>
<?php /*                     Closes the picker button. */ ?>
                    </button>
<?php /*                     Panel that holds one button per program. */ ?>
                    <div class="program-list" id="programList">
<?php /*                         Loops over every program code and name. */ ?>
                        <?php foreach (PROGRAMS as $code => $name): ?>
<?php /*                         Program option button carrying its code and allowed tracks. */ ?>
                        <button type="button" class="program-option" data-code="<?= htmlspecialchars($code) ?>" data-tracks="<?= htmlspecialchars(implode(',', getTracksForProgram($code))) ?>">
<?php /*                             Shows the program's full name. */ ?>
                            <span><?= htmlspecialchars($name) ?></span>
<?php /*                         Closes the program option button. */ ?>
                        </button>
<?php /*                         Ends the loop over programs. */ ?>
                        <?php endforeach; ?>
<?php /*                     Closes the program list panel. */ ?>
                    </div>
<?php /*                 Closes the program picker wrapper. */ ?>
                </div>
<?php /*                 Hidden hint paragraph that explains the chosen track. */ ?>
                <p id="trackHint" style="display:none;margin-top:0.5rem;font-size:0.76rem;color:rgba(255,255,255,0.55);">
<?php /*                     Info icon before the track hint text. */ ?>
                    <i class="fas fa-info-circle" style="color:var(--adzu-gold);"></i>
<?php /*                     Empty span where the track hint text is written. */ ?>
                    <span></span>
<?php /*                 Closes the track hint paragraph. */ ?>
                </p>
<?php /*             Closes the program group. */ ?>
            </div>

<?php /*             Hidden group that appears when a track must be chosen. */ ?>
            <div class="form-group" id="trackGroup" style="display:none;">
<?php /*                 Label for the track dropdown. */ ?>
                <label for="trackSelect">Track *</label>
<?php /*                 Icon wrapper around the track select. */ ?>
                <div class="input-icon-wrap">
<?php /*                     Route icon inside the track select. */ ?>
                    <i class="fas fa-route"></i>
<?php /*                     Dropdown for the track, filled by JavaScript. */ ?>
                    <select id="trackSelect" name="track" required>
<?php /*                         Placeholder option telling the student to pick a track. */ ?>
                        <option value="">Select your track</option>
<?php /*                     Closes the track dropdown. */ ?>
                    </select>
<?php /*                 Hint paragraph explaining why a track is needed. */ ?>
                </div>
<?php /*                 Id of the paragraph so script can show or hide it. */ ?>
                <p class="field-hint" style="color:rgba(255,255,255,0.55);" id="trackChoiceHint">
<?php /*                     The sentence shown under the track dropdown. */ ?>
                    Your program can be followed in more than one way. Choose the track you are enrolled in.
<?php /*                 Closes the track hint paragraph. */ ?>
                </p>
<?php /*             Closes the track group. */ ?>
            </div>

<?php /*             Section label for the account credentials part. */ ?>
            <div class="form-section-label">Account Credentials</div>
<?php /*             Group for the email field. */ ?>
            <div class="form-group">
<?php /*                 Label for the ADZU email input. */ ?>
                <label for="email">ADZU Email *</label>
<?php /*                 Icon wrapper around the email input. */ ?>
                <div class="input-icon-wrap">
<?php /*                     Envelope icon inside the email box. */ ?>
                    <i class="fas fa-envelope"></i>
<?php /*                     Email input, prefilled, with the ADZU address example. */ ?>
                    <input type="email" id="email" name="email" placeholder="yourname@adzu.edu.ph" value="<?= htmlspecialchars($prefillEmail) ?>" autocomplete="email" required>
<?php /*                 Closes the email wrapper. */ ?>
                </div>
<?php /*             Closes the email group. */ ?>
            </div>
<?php /*             Group for the ADZU ID number. */ ?>
            <div class="form-group">
<?php /*                 Label for the ADZU ID input. */ ?>
                <label for="studentId">ADZU ID Number *</label>
<?php /*                 Icon wrapper around the ID input. */ ?>
                <div class="input-icon-wrap">
<?php /*                     ID card icon inside the ID box. */ ?>
                    <i class="fas fa-id-card"></i>
<?php /*                     Text input for the ADZU ID number, required. */ ?>
                    <input type="text" id="studentId" name="studentId" placeholder="e.g. 259344" value="<?= htmlspecialchars($prefillStudentId) ?>" required>
<?php /*                 Closes the ID wrapper. */ ?>
                </div>
<?php /*             Closes the ID group. */ ?>
            </div>
<?php /*             Two-column grid for the two password fields. */ ?>
            <div class="register-grid-2">
<?php /*                 Group for the password field. */ ?>
                <div class="form-group">
<?php /*                     Label for the password input. */ ?>
                    <label for="regPassword">Password *</label>
<?php /*                     Icon wrapper around the password input. */ ?>
                    <div class="input-icon-wrap">
<?php /*                         Lock icon inside the password box. */ ?>
                        <i class="fas fa-lock"></i>
<?php /*                         Password input requiring at least five characters and one number. */ ?>
                        <input type="password" id="regPassword" name="password" class="has-toggle" placeholder="Min. 5 chars + 1 number" required minlength="5" pattern="(?=.*[0-9]).{5,}" title="Use at least 5 characters and include at least 1 number.">
<?php /*                         Eye button that reveals or hides the typed password. */ ?>
                        <button type="button" class="pw-toggle" data-toggle-password="regPassword" tabindex="-1"><i class="fas fa-eye"></i></button>
<?php /*                     Closes the password wrapper. */ ?>
                    </div>
<?php /*                 Closes the password group. */ ?>
                </div>
<?php /*                 Group for the password confirmation field. */ ?>
                <div class="form-group">
<?php /*                     Label for the re-enter password input. */ ?>
                    <label for="confirmPassword">Re-enter Password *</label>
<?php /*                     Icon wrapper around the confirmation input. */ ?>
                    <div class="input-icon-wrap">
<?php /*                         Lock icon inside the confirmation box. */ ?>
                        <i class="fas fa-lock"></i>
<?php /*                         Password input where the student repeats the password. */ ?>
                        <input type="password" id="confirmPassword" name="confirmPassword" class="has-toggle" placeholder="Repeat password" required>
<?php /*                         Eye button for the confirmation field. */ ?>
                        <button type="button" class="pw-toggle" data-toggle-password="confirmPassword" tabindex="-1"><i class="fas fa-eye"></i></button>
<?php /*                     Closes the confirmation wrapper. */ ?>
                    </div>
<?php /*                 Closes the confirmation group. */ ?>
                </div>
<?php /*             Closes the two-column password grid. */ ?>
            </div>
<?php /*             Small note paragraph describing the password rules. */ ?>
            <p style="margin:0.25rem 0 1.75rem;font-size:0.73rem;color:rgba(255,255,255,0.4);line-height:1.5;">
<?php /*                 Shield icon in front of the password note. */ ?>
                <i class="fas fa-shield-alt" style="margin-right:5px;color:var(--adzu-gold);"></i>
<?php /*                 Text explaining the password requirements. */ ?>
                Password must be at least 5 characters and include at least 1 number. Longer passwords are accepted.
<?php /*             Closes the password note paragraph. */ ?>
            </p>
<?php /*             Explains that a coordinator must approve the new account. */ ?>
            <div class="alert alert-info" style="margin-bottom:1.25rem;font-size:0.78rem;">
<?php /*                 Clock icon marking the approval step. */ ?>
                <i class="fas fa-clock"></i>
<?php /*                 Tells the student who validates the account. */ ?>
                <span>After you submit, the Graduate Program Coordinator will validate your account. You can sign in only after it is approved.</span>
<?php /*             Closes the approval note. */ ?>
            </div>
<?php /*             Big submit button that sends the whole form. */ ?>
            <button type="submit" class="btn btn-primary btn-lg">
<?php /*                 Check icon and the text Create Account. */ ?>
                <i class="fas fa-check-circle"></i> Create Account
<?php /*             Closes the submit button. */ ?>
            </button>
<?php /*         Closes the registration form. */ ?>
        </form>

<?php /*         Footer area under the form. */ ?>
        <div class="login-back">
<?php /*             Link that takes the student back to the login page. */ ?>
            <a href="<?= url('student/login.php') ?>"><i class="fas fa-arrow-left"></i> Back to Login</a>
<?php /*         Closes the back-to-login area. */ ?>
        </div>
<?php /*     Closes the registration card. */ ?>
    </div>
<?php /* Closes the page wrapper. */ ?>
</div>

<?php /* Starts a script that feeds track data to the page. */ ?>
<script>
<?php /* Gives JavaScript the per-track hint sentences. */ ?>
window.CSITE_TRACK_HINTS = <?= json_encode($trackHints) ?>;
<?php /* Starts a map of readable track labels for JavaScript. */ ?>
window.CSITE_TRACK_LABELS = <?= json_encode([
    // JavaScript label for the thesis track.
    'thesis'   => 'Thesis',
    // JavaScript label for the capstone track.
    'capstone' => 'Capstone',
    // JavaScript label for the seminar paper track.
    'seminar'  => 'Seminar Paper',
// Closes the track label map and prints it as JSON.
]) ?>;
<?php /* Passes the posted track so the form can restore it after an error. */ ?>
window.CSITE_SELECTED_TRACK = <?= json_encode($prefillTrack) ?>;
<?php /* Closes the script block. */ ?>
</script>

<?php /* Includes the shared footer that ends the page. */ ?>
<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>