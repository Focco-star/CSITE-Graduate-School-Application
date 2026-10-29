<?php
/* CSITE configuration module: Student names, identity normalization, profile rows, and account records. */

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
