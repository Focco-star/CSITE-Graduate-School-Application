<?php
// Applies the schema changes for birthdate, sex and coordinator notifications.
require_once __DIR__ . '/../includes/config/config.php';
require_once __DIR__ . '/../includes/config/db.php';

$pdo = DB::getConnection();

function hasColumn(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($column));
    return (bool) $stmt->fetch();
}

if (!hasColumn($pdo, 'students', 'birthdate')) {
    $pdo->exec("ALTER TABLE `students` ADD COLUMN `birthdate` DATE NULL AFTER `middle_initial`");
    echo "added students.birthdate\n";
}
if (!hasColumn($pdo, 'students', 'sex')) {
    $pdo->exec("ALTER TABLE `students` ADD COLUMN `sex` VARCHAR(20) NULL AFTER `gender`");
    echo "added students.sex\n";
}

$pdo->exec("UPDATE `students` SET `sex` = `gender` WHERE (`sex` IS NULL OR `sex` = '') AND `gender` <> ''");

$pdo->exec("UPDATE `programs` SET `program_name` = 'Master of Library and Information Science' WHERE `program_code` = 'MLIS'");
$pdo->exec("UPDATE `programs` SET `program_name` = 'Master of Science in Library and Information Science' WHERE `program_code` = 'MSLIS'");

$pdo->exec("UPDATE `students`
            SET `program` = 'Master of Science in Library and Information Science'
            WHERE `track` = 'thesis'
              AND (`program` LIKE '%Library%' OR `program` IN ('MSLIS', 'MLIS'))");
$pdo->exec("UPDATE `students`
            SET `program` = 'Master of Library and Information Science'
            WHERE `track` = 'capstone'
              AND (`program` LIKE '%Library%' OR `program` IN ('MSLIS', 'MLIS'))");

$pdo->exec("CREATE TABLE IF NOT EXISTS `coordinator_notifications` (
    `notification_id` INT(11) NOT NULL AUTO_INCREMENT,
    `recipient_user_id` INT(11) NOT NULL,
    `type` ENUM('application','account') NOT NULL,
    `reference_id` INT(11) NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `message` VARCHAR(500) NOT NULL,
    `link` VARCHAR(500) NOT NULL,
    `created_at` DATETIME NOT NULL,
    `seen_at` DATETIME NULL DEFAULT NULL,
    `seen_by` INT(11) NULL DEFAULT NULL,
    PRIMARY KEY (`notification_id`),
    UNIQUE KEY `uq_coord_notification` (`recipient_user_id`, `type`, `reference_id`),
    KEY `idx_coord_unseen` (`recipient_user_id`, `seen_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

echo "notifications table ready\n";
echo "library rows:\n";
foreach ($pdo->query("SELECT student_id, program, track FROM students WHERE program LIKE '%Library%' ORDER BY student_id") as $row) {
    echo "  {$row['student_id']} | {$row['program']} | {$row['track']}\n";
}
echo "programs:\n";
foreach ($pdo->query("SELECT program_code, program_name FROM programs WHERE program_code IN ('MLIS','MSLIS')") as $row) {
    echo "  {$row['program_code']} | {$row['program_name']}\n";
}
