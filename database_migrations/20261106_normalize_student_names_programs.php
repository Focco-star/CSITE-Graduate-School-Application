<?php
// One-time cleanup: proper-case student names and store full program labels.
require_once __DIR__ . '/../includes/config/config.php';
require_once __DIR__ . '/../includes/config/db.php';

$pdo = DB::getConnection();
$rows = $pdo->query("SELECT student_id, user_id, first_name, last_name, middle_initial, program, track FROM students")->fetchAll();

$nameChanges = 0;
$programChanges = 0;

foreach ($rows as $row) {
    $first = formatPersonName((string) $row['first_name']);
    $last = formatPersonName((string) $row['last_name']);
    $mi = strtoupper(trim((string) ($row['middle_initial'] ?? '')));
    $mi = $mi !== '' ? rtrim($mi, '.') : '';
    $code = programCodeForStudent($row);
    $label = PROGRAMS[$code] ?? (string) $row['program'];

    $patch = [];
    if ($first !== (string) $row['first_name']) {
        $patch['first_name'] = $first;
    }
    if ($last !== (string) $row['last_name']) {
        $patch['last_name'] = $last;
    }
    if ($mi !== (string) ($row['middle_initial'] ?? '')) {
        $patch['middle_initial'] = $mi;
    }
    if ($label !== (string) $row['program']) {
        $patch['program'] = $label;
        $programChanges++;
    }

    if ($patch) {
        DB::update('students', $patch, ['student_id' => (int) $row['student_id']]);
        $nameChanges++;
    }

    $fullName = canonicalStudentName($first, $last, $mi);
    $current = DB::find('users', ['user_id' => (int) $row['user_id']]);
    if ($current && (string) $current['full_name'] !== $fullName) {
        DB::update('users', ['full_name' => $fullName], ['user_id' => (int) $row['user_id']]);
    }
}

echo "students updated: $nameChanges\n";
echo "program labels updated: $programChanges\n";
foreach ($pdo->query("SELECT first_name, last_name, program, track FROM students WHERE first_name IN ('MARC','Mj') OR student_id IN (123456) ORDER BY student_id") as $row) {
    echo "  {$row['first_name']} {$row['last_name']} | {$row['program']} | {$row['track']}\n";
}
