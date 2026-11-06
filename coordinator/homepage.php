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

// Loads the database class used by the notification and count helpers.
require_once __DIR__ . '/../includes/config/db.php';
// Reads the coordinator id used for the notification list (session stores it as id).
$coordinatorId = (int) ($sessionUser['id'] ?? $sessionUser['user_id'] ?? 0);
// Loads the latest new and viewed notifications.
$homeNotifications = coordinatorNotifications($coordinatorId);
// Counts the submitted applications waiting for review.
$homeNewApplications = coordinatorNewApplicationCount();
// Counts the student accounts waiting for validation.
$homePendingAccounts = function_exists('pendingStudentAccountCount') ? pendingStudentAccountCount() : 0;
// Remembers the page to reopen after a notification is removed.
$homeNotificationReturnPath = coordinatorNotificationReturnPath();

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

<?php /* Card below the greeting that shows what needs the coordinator's attention. */ ?>
    <section class="home-attention" aria-labelledby="homeAttentionTitle"
             data-home-attention
             data-attention-endpoint="<?= url('coordinator/attention_data.php') ?>"
             data-attention-remove-endpoint="<?= url('coordinator/notification_remove.php') ?>"
             data-attention-return="<?= htmlspecialchars($homeNotificationReturnPath) ?>">
<?php /*     Header of the attention card. */ ?>
        <div class="home-attention-head">
<?php /*         Title and short explanation. */ ?>
            <div>
                <h3 id="homeAttentionTitle"><i class="fas fa-bell" aria-hidden="true"></i> Needs your attention</h3>
                <p>New applications and account validations are listed here first.</p>
            </div>
<?php /*         Small synchronisation note. */ ?>
            <span class="home-attention-sync"><i class="fas fa-rotate" aria-hidden="true"></i> Live from the database</span>
        </div>

<?php /*     The two main actions stay visible at all times. */ ?>
        <div class="home-attention-grid">
<?php /*         Tile linking to the applications that are waiting for review. */ ?>
            <a class="home-attention-tile" href="<?= url('coordinator/applications/manage.php') ?>">
                <span class="home-attention-icon"><i class="fas fa-file-circle-check" aria-hidden="true"></i></span>
                <span class="home-attention-value" data-attention-count="applications"><?= (int) $homeNewApplications ?></span>
                <span class="home-attention-label">New applications</span>
                <span class="home-attention-hint">Submitted and waiting for review</span>
            </a>
<?php /*         Tile linking to the accounts waiting for validation. */ ?>
            <a class="home-attention-tile" href="<?= url('coordinator/students/pending.php') ?>">
                <span class="home-attention-icon"><i class="fas fa-user-check" aria-hidden="true"></i></span>
                <span class="home-attention-value" data-attention-count="accounts"><?= (int) $homePendingAccounts ?></span>
                <span class="home-attention-label">Accounts awaiting validation</span>
                <span class="home-attention-hint">Approve or reject new student accounts</span>
            </a>
        </div>

<?php /*     Collapsible list for the individual notifications. */ ?>
        <?php $homeNotificationItems = $homeNotifications['new']; ?>
        <?php $homeNotificationCount = count($homeNotificationItems); ?>
        <div class="home-attention-dropdown" data-attention-dropdown<?= $homeNotificationCount === 0 ? ' hidden' : '' ?>>
<?php /*         Button that opens and closes the notification list. */ ?>
            <button type="button" class="home-attention-toggle" data-attention-toggle aria-expanded="false" aria-controls="homeAttentionList">
                <span class="home-attention-toggle-label"><i class="fas fa-list-ul" aria-hidden="true"></i> Recent notifications <span class="home-attention-toggle-count" data-attention-total><?= $homeNotificationCount ?></span></span>
                <i class="fas fa-chevron-down home-attention-caret" aria-hidden="true"></i>
            </button>
<?php /*         Hidden by default; the button above reveals it. */ ?>
            <ul class="home-attention-list" id="homeAttentionList" data-attention-list hidden>
                <?php foreach ($homeNotificationItems as $notification): ?>
                <li>
                    <div class="home-attention-list-item">
                        <a href="<?= url('coordinator/notifications.php?open=' . (int) $notification['notification_id']) ?>">
                            <span class="home-attention-list-icon"><i class="fas <?= $notification['type'] === 'account' ? 'fa-user-check' : ($notification['type'] === 'document' ? 'fa-file-arrow-up' : 'fa-file-circle-check') ?>" aria-hidden="true"></i></span>
                            <span class="home-attention-list-body">
                                <strong><?= htmlspecialchars((string) $notification['title']) ?></strong>
                                <span><?= htmlspecialchars((string) $notification['message']) ?></span>
                            </span>
                            <span class="home-attention-list-time"><?= htmlspecialchars(coordinatorNotificationTime((string) $notification['created_at'])) ?></span>
                        </a>
                        <form method="post" action="<?= url('coordinator/notification_remove.php') ?>" class="home-attention-remove-form">
                            <input type="hidden" name="notification_id" value="<?= (int) $notification['notification_id'] ?>">
                            <input type="hidden" name="return" value="<?= htmlspecialchars($homeNotificationReturnPath) ?>">
                            <button type="submit" class="home-attention-remove" title="Remove notification" aria-label="Remove notification"><i class="fas fa-xmark" aria-hidden="true"></i></button>
                        </form>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
<?php /*     Empty state when there is nothing new. */ ?>
        <p class="home-attention-empty" data-attention-empty<?= $homeNotificationCount > 0 ? ' hidden' : '' ?>><i class="fas fa-circle-check" aria-hidden="true"></i> No new notifications. Everything is up to date.</p>
    </section>

<?php /* Existing schedule calendar. */ ?>
    <?php renderScheduleCalendar(); ?>
</div>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
