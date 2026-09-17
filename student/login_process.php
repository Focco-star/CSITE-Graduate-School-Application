<?php

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

    $user = DB::find('users', ['email' => $email]);

    if ($user && password_verify($password, $user['password'])) {

        if ($user['role'] !== 'student') {
            header('Location: ' . url('student/login.php?error=' . urlencode('Access denied. Not a student account.')));
            exit;
        }

        $student = DB::find('students', ['user_id' => $user['user_id']]);

        $_SESSION['user_id']    = $user['user_id'];
        $_SESSION['student_id'] = $student['student_id'] ?? null;
        $_SESSION['full_name']  = $user['full_name'];
        $_SESSION['email']      = $user['email'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['user']       = $user;
        setFlash('success', 'Welcome back, ' . $user['full_name'] . '.');

        header('Location: ' . url('student/dashboard.php'));
        exit;
    } else {
        header('Location: ' . url('student/login.php?error=' . urlencode('Invalid email or password.')));
        exit;
    }
}
