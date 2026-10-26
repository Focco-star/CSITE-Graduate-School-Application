<?php /* Starts the PHP code for the presentation notifications page. */ ?>
<?php
// Loads config settings, helpers, and the demo data arrays.
require_once __DIR__ . '/../../includes/config.php';
// Loads the database helper in case schedule members come from MySQL.
require_once __DIR__ . '/../../includes/db.php';

// Sets the page title used in the browser tab.
$pageTitle   = 'Presentation Notifications';
// Tells the layout the user is a coordinator.
$role        = 'coordinator';
// Marks the 'schedule' sidebar link as active.
$currentPage = 'schedule';
// Gets the signed-in coordinator's name for the header greeting.
$userName    = currentCoordinatorName();

// Loads the schedule by id from the URL, or falls back to the first one.
$sch = findSchedule((string) ($_GET['id'] ?? '')) ?? (storeGet('schedules')[0] ?? null);
// Loads the panel members assigned to that schedule's application.
$notificationPanelMembers = $sch ? databaseSchedulePanelMembers((int) ($sch['applicationId'] ?? 0)) : [];
// Starts mapping each panel member row into a full display name.
$panelNames = array_map(static function (array $member): string {
    // Builds 'Last, First Middle' text for one panel member.
    return trim($member['last_name'] . ', ' . $member['first_name'] . (!empty($member['middle_name']) ? ' ' . $member['middle_name'] : ''));
// Closes the array_map call that created the names.
}, $notificationPanelMembers);
// Checks whether the schedule has a date, time, and venue filled in.
$isScheduled = $sch && !empty($sch['date']) && !empty($sch['time']) && !empty($sch['venue']);

// Loads the shared header and navigation.
require_once __DIR__ . '/../../includes/header.php';
// Closes PHP so the HTML below is printed.
?>

<?php /* Opens the page header area. */ ?>
<div class="page-header">
<?php /*     Shows the big heading 'Presentation Notifications'. */ ?>
    <h2>Presentation Notifications</h2>
<?php /*     Shows the student name and stage, or a fallback sentence. */ ?>
    <p><?= $sch ? htmlspecialchars(($sch['studentName'] ?? '') . ' — ' . ($sch['stage'] ?? '')) : 'Notification status for panel members and documentor.' ?></p>
<?php /* Closes the page header area. */ ?>
</div>


<?php /* Opens the card with the presentation details. */ ?>
<div class="card">
<?php /*     Card header with the heading 'Presentation Details'. */ ?>
    <div class="card-header"><h3>Presentation Details</h3></div>
<?php /*     Opens the card body. */ ?>
    <div class="card-body">
<?php /*         Starts a grid that lays out the detail items in two columns. */ ?>
        <div class="detail-grid">
<?php /*             Shows the student's name for this presentation. */ ?>
            <div class="detail-item"><label>Student</label><span><?= htmlspecialchars($sch['studentName'] ?? '—') ?></span></div>
<?php /*             Shows the presentation stage being defended. */ ?>
            <div class="detail-item"><label>Stage</label><span><?= htmlspecialchars($sch['stage'] ?? '—') ?></span></div>
<?php /*             Shows the date and time, or a dash when no schedule is loaded. */ ?>
            <div class="detail-item"><label>Date &amp; Time</label><span><?= $sch ? htmlspecialchars(($sch['date'] ?? '') . ' – ' . ($sch['time'] ?? '')) : '—' ?></span></div>
<?php /*             Only shows the venue row when a venue was set. */ ?>
            <?php if (!empty($sch['venue'])): ?>
<?php /*             Shows the room or place of the presentation. */ ?>
            <div class="detail-item"><label>Venue</label><span><?= htmlspecialchars($sch['venue']) ?></span></div>
<?php /*             Ends the venue check. */ ?>
            <?php endif; ?>
<?php /*         Closes the detail grid. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the presentation details card. */ ?>
</div>

<?php /* Opens the card listing who must be notified. */ ?>
<div class="card">
<?php /*     Card header 'Panel and Documentor Notifications'. */ ?>
    <div class="card-header"><h3>Panel &amp; Documentor Notifications</h3></div>
<?php /*     Opens the card body that wraps the table. */ ?>
    <div class="card-body">
<?php /*         Wraps the table so it scrolls sideways on small screens. */ ?>
        <div class="table-responsive">
<?php /*             Starts the notifications table. */ ?>
            <table class="data-table">
<?php /*                 Opens the table head section. */ ?>
                <thead>
<?php /*                     Header row with Name, Role, and Notification columns. */ ?>
                    <tr><th>Name</th><th>Role</th><th>Notification</th></tr>
<?php /*                 Closes the table head section. */ ?>
                </thead>
<?php /*                 Starts the table body. */ ?>
                <tbody>
<?php /*                     Loops over each panel member name with its index. */ ?>
                    <?php foreach ($panelNames as $i => $name): ?>
<?php /*                     Starts a table row for this panel member. */ ?>
                    <tr>
<?php /*                         Shows the panel member's full name in bold. */ ?>
                        <td><strong><?= htmlspecialchars($name) ?></strong></td>
<?php /*                         Shows 'Panel Member' plus the 1-based number. */ ?>
                        <td>Panel Member <?= $i + 1 ?></td>
<?php /*                         Shows a ready or waiting badge depending on the schedule details. */ ?>
                        <td><?= $isScheduled ? '<span class="status-badge status-confirmed">Automatically ready</span>' : '<span class="status-badge status-pending">Waiting for schedule details</span>' ?></td>
<?php /*                     Ends the table row for this panel member. */ ?>
                    </tr>
<?php /*                     Ends the loop over panel members. */ ?>
                    <?php endforeach; ?>
<?php /*                     Starts a row for the adviser. */ ?>
                    <tr>
<?php /*                         Shows the adviser's name, or a dash when unknown. */ ?>
                        <td><strong><?= htmlspecialchars($sch['adviser'] ?? '—') ?></strong></td>
<?php /*                         Labels this row as the Adviser. */ ?>
                        <td>Adviser</td>
<?php /*                         Shows the adviser's notification badge for this presentation. */ ?>
                        <td><?= $isScheduled ? '<span class="status-badge status-confirmed">Automatically ready</span>' : '<span class="status-badge status-pending">Waiting for schedule details</span>' ?></td>
<?php /*                     Ends the adviser row. */ ?>
                    </tr>
<?php /*                     Starts a row for the documentor. */ ?>
                    <tr>
<?php /*                         Shows the documentor's name, or a dash when unknown. */ ?>
                        <td><strong><?= htmlspecialchars($sch['documentor'] ?? '—') ?></strong></td>
<?php /*                         Labels this row as the Documentor. */ ?>
                        <td><strong>Documentor</strong></td>
<?php /*                         Shows the documentor's notification badge. */ ?>
                        <td><?= $isScheduled ? '<span class="status-badge status-confirmed">Automatically ready</span>' : '<span class="status-badge status-pending">Waiting for schedule details</span>' ?></td>
<?php /*                     Ends the documentor row. */ ?>
                    </tr>
<?php /*                 Closes the table body. */ ?>
                </tbody>
<?php /*             Closes the notifications table. */ ?>
            </table>
<?php /*         Closes the scrolling table wrapper. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /*     Opens the card footer area with the note and Back button. */ ?>
    <div class="card-footer">
<?php /*         Explains that readiness is automatic and no manual invite is needed. */ ?>
        <span style="font-size:.85rem;color:var(--gray-500);">Notification readiness is derived automatically from the completed schedule. No manual invitation status is needed.</span>
<?php /*         Back button returning to the schedule list. */ ?>
        <a href="<?= url('coordinator/schedule/manage.php') ?>" class="btn btn-outline">Back</a>
<?php /*     Closes the card footer. */ ?>
    </div>
<?php /* Closes the notifications card. */ ?>
</div>

<?php /* Loads the shared footer and closes the page. */ ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
