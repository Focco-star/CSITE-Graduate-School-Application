<?php /* Opens the handler that checks a student's email and password at sign-in. */ ?>
<?php

// Starts the session so the login details can be remembered across pages.
session_start();
// Loads the shared configuration and helper functions.
require_once __DIR__ . '/../includes/config/config.php';
// Loads the database class used to look up the user account.
require_once __DIR__ . '/../includes/config/db.php';

// Only run the login check when the form was submitted with POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Reads the typed email and trims spaces around it.
    $email    = trim($_POST['email'] ?? '');
    // Reads the typed password exactly as entered.
    $password = $_POST['password'] ?? '';

    // If either the email or the password is empty, send an error back.
    if (empty($email) || empty($password)) {
        // Redirects to the login page with a fill in all fields message.
        header('Location: ' . url('student/login.php?error=' . urlencode('Please fill in all fields.')));
        // Stops the script after the redirect.
        exit;
    // Closes the if block that checked for empty fields.
    }

    // Finds the user row whose email matches what was typed.
    $user = DB::find('users', ['email' => $email]);

    // If the user exists and the stored password hash matches, log them in.
    if ($user && password_verify($password, $user['password'])) {

        // Blocks accounts that are not students, such as coordinators or admins.
        if ($user['role'] !== 'student') {
            // Redirects back to the login page with an access denied message.
            header('Location: ' . url('student/login.php?error=' . urlencode('Access denied. Not a student account.')));
            // Stops the script after the redirect.
            exit;
        // Closes the if block that rejected non-student roles.
        }

        // Finds the student profile row linked to this user account.
        $student = DB::find('students', ['user_id' => $user['user_id']]);

        // Saves the user id in the session so other pages know who is logged in.
        $_SESSION['user_id']    = $user['user_id'];
        // Saves the student id, or null when no student profile exists yet.
        $_SESSION['student_id'] = $student['student_id'] ?? null;
        // Saves the full name for greetings in the header.
        $_SESSION['full_name']  = $user['full_name'];
        // Saves the email address in the session.
        $_SESSION['email']      = $user['email'];
        // Saves the role, which is student here.
        $_SESSION['role']       = $user['role'];
        // Saves the whole user row so pages can read other fields.
        $_SESSION['user']       = $user;
        // Queues a welcome message that is shown once on the next page.
        setFlash('success', 'Welcome, Student ' . studentWelcomeName($student, $user) . '.');

        // Sends the logged-in student to their dashboard.
        header('Location: ' . url('student/dashboard.php'));
        // Stops the script after the redirect.
        exit;
    // Runs when no user matched or the password was wrong.
    } else {
        // Redirects back to the login page with an invalid credentials message.
        header('Location: ' . url('student/login.php?error=' . urlencode('Invalid email or password.')));
        // Stops the script after the redirect.
        exit;
    // Closes the else block for a failed sign-in.
    }
// Closes the if block that handled the POST request.
}
