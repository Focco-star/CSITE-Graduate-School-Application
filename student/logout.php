<?php
require_once __DIR__ . '/../includes/config.php';

unset($_SESSION['current_student_id'], $_SESSION['current_student_email']);
redirectTo('student/login.php');
