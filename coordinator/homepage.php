<?php
require_once __DIR__ . '/../includes/config/config.php';
require_once __DIR__ . '/../includes/components/calendar.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sessionUser = $_SESSION['user'] ?? [];
$userName = $sessionUser['full_name'] ?? $mockCoordinator['name'] ?? 'Coordinator';
$pageTitle = 'Home';
$role = 'coordinator';
$currentPage = 'home';

$academicYearStart = (int) date('n') >= 7 ? (int) date('Y') : (int) date('Y') - 1;
$academicYearLabel = $academicYearStart . '-' . ($academicYearStart + 1);

require_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="coordinator-home">
    <section class="dash-hero" aria-labelledby="homeWelcomeTitle">
        <div class="dash-hero-main">
            <span class="dash-hero-mark" aria-hidden="true"><i class="fas fa-university"></i></span>
            <div class="dash-hero-copy">
                <span class="dash-hero-eyebrow"><i class="fas fa-graduation-cap" aria-hidden="true"></i> CSITE Graduate School</span>
                <h2 class="dash-hero-title" id="homeWelcomeTitle">Welcome, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="dash-hero-subtitle">Graduate Program Coordinator <span class="dash-hero-dot" aria-hidden="true">&middot;</span> Ateneo de Zamboanga University</p>
            </div>
        </div>
        <div class="dash-hero-aside">
            <span class="dash-hero-ay"><i class="fas fa-calendar-days" aria-hidden="true"></i> Academic Year <?= htmlspecialchars($academicYearLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </section>

    <?php renderScheduleCalendar(); ?>
</div>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
