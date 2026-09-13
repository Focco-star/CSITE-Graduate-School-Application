<?php
// student/login_process.php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        header('Location: ' . url('student/login.php?error=' . urlencode('Please fill in all fields.')));
        exit;
    }

    // 1. Look up user by email in the database
    $user = DB::find('users', ['email' => $email]);

    // 2. Verify account existence and check password hash
    if ($user && password_verify($password, $user['password'])) {
        
        // Ensure user is a student
        if ($user['role'] !== 'student') {
            header('Location: ' . url('student/login.php?error=' . urlencode('Access denied. Not a student account.')));
            exit;
        }

        // Fetch corresponding student profile details
        $student = DB::find('students', ['user_id' => $user['user_id']]);

        // 3. Store authentication session data
        $_SESSION['user_id']    = $user['user_id'];
        $_SESSION['student_id'] = $student['student_id'] ?? null;
        $_SESSION['full_name']  = $user['full_name'];
        $_SESSION['email']      = $user['email'];
        $_SESSION['role']       = $user['role'];

        // Redirect to student dashboard
        header('Location: ' . url('student/dashboard.php'));
        exit;
    } else {
        // Authentication failed
        header('Location: ' . url('student/login.php?error=' . urlencode('Invalid email or password.')));
        exit;
    }
}