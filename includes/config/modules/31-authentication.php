<?php
/* CSITE configuration module: Student authentication, profile updates, and login/session identity helpers. */

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
