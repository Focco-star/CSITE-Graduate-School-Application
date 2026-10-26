<?php /* Starts the PHP code for the panel assignment page. */ ?>
<?php
// Loads config settings, helpers, and the demo data arrays.
require_once __DIR__ . '/../../includes/config.php';
// Loads the database helper for panel member data.
require_once __DIR__ . '/../../includes/db.php';

// Sets the page title used in the browser tab.
$pageTitle   = 'Panel Assignment';
// Tells the layout the signed-in user is a coordinator.
$role        = 'coordinator';
// Marks the 'schedule' sidebar link as active.
$currentPage = 'schedule';
// Takes the demo coordinator name for the header greeting.
$userName    = $mockCoordinator['name'];

// Loads the schedule by id from the URL, or falls back to the first one.
$sch = findSchedule((string) ($_GET['id'] ?? '')) ?? (storeGet('schedules')[0] ?? null);
// Loads the panel members already assigned to that schedule.
$assignedPanelMembers = $sch ? databaseSchedulePanelMembers((int) ($sch['applicationId'] ?? 0)) : [];
// Starts mapping each assigned member row into a name.
$panelNames = array_map(static function (array $member): string {
    // Builds 'Last, First Middle' text for one assigned member.
    return trim($member['last_name'] . ', ' . $member['first_name'] . (!empty($member['middle_name']) ? ' ' . $member['middle_name'] : ''));
// Closes the array_map call for assigned panel names.
}, $assignedPanelMembers);
// Loads the full list of panel members who can be assigned.
$members = databasePanelMembers();

// Checks that the form was submitted and asked to toggle availability.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_avail'])) {
    // Finds the panel member whose id was submitted.
    $pm = findPanelMember((string) $_POST['toggle_avail']);
    // Checks that the panel member record was found.
    if ($pm) {
        // Updates the panel member row with a new availability value.
        updatePanelMemberRecord($pm['id'], [
            // Flips 'available' to 'unavailable' or the other way round.
            'availability' => ($pm['availability'] ?? 'available') === 'available' ? 'unavailable' : 'available',
        // Closes the array passed to the update function.
        ]);
    // Closes the found-member check.
    }
    // Reads the current schedule id so we can return to the same page.
    $qid = $sch['id'] ?? '';
    // Redirects back to this page, keeping the schedule id in the URL.
    redirectTo('coordinator/schedule/panel.php' . ($qid ? '?id=' . urlencode($qid) : ''));
// Closes the availability toggle block.
}

// Loads the shared header and navigation.
require_once __DIR__ . '/../../includes/header.php';
// Closes PHP so the HTML below is printed.
?>

<?php /* Opens the page header area. */ ?>
<div class="page-header">
<?php /*     Shows the big heading 'Panel Assignment'. */ ?>
    <h2>Panel Assignment</h2>
<?php /*     Shows student, stage, and date, or a fallback sentence. */ ?>
    <p><?= $sch ? htmlspecialchars(($sch['studentName'] ?? '') . ' — ' . ($sch['stage'] ?? '') . ' (' . ($sch['date'] ?? '') . ')') : 'Panel composition for selected presentation.' ?></p>
<?php /* Closes the page header area. */ ?>
</div>

<?php /* Opens the card listing the assigned panel. */ ?>
<div class="card">
<?php /*     Card header showing the panel and the student name. */ ?>
    <div class="card-header"><h3>Assigned Panel — <?= htmlspecialchars($sch['studentName'] ?? '—') ?></h3></div>
<?php /*     Opens the card body. */ ?>
    <div class="card-body">
<?php /*         Only shows the list when panel members are assigned. */ ?>
        <?php if ($panelNames): ?>
<?php /*         Starts a vertical flex column for the panel member rows. */ ?>
        <div style="display:flex;flex-direction:column;gap:0.6rem;">
<?php /*             Loops over each assigned panel name with its index. */ ?>
            <?php foreach ($panelNames as $idx => $name): ?>
<?php /*             A row box with gray background and border for one member. */ ?>
            <div style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 1rem;background:var(--gray-50);border-radius:var(--radius);border:1px solid var(--gray-200);">
<?php /*                 Draws a round avatar circle behind the user icon. */ ?>
                <div style="width:32px;height:32px;border-radius:50%;background:rgba(6,2,151,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
<?php /*                     Shows a small user icon inside the circle. */ ?>
                    <i class="fas fa-user" style="color:var(--adzu-navy);font-size:0.8rem;"></i>
<?php /*                 Closes the avatar circle. */ ?>
                </div>
<?php /*                 Opens the text box holding the name and label. */ ?>
                <div>
<?php /*                     Shows the member's name in semibold text. */ ?>
                    <div style="font-weight:600;font-size:0.875rem;"><?= htmlspecialchars($name) ?></div>
<?php /*                     Shows 'Panel Member' plus the 1-based number. */ ?>
                    <div style="font-size:0.73rem;color:var(--gray-400);">Panel Member <?= $idx + 1 ?></div>
<?php /*                 Closes the text box. */ ?>
                </div>
<?php /*             Closes the row box for this member. */ ?>
            </div>
<?php /*             Ends the loop over assigned panel members. */ ?>
            <?php endforeach; ?>
<?php /*         Closes the vertical flex column. */ ?>
        </div>
<?php /*         Runs when no panel members are assigned. */ ?>
        <?php else: ?>
<?php /*         Shows a gray message that nobody is assigned yet. */ ?>
        <p style="color:var(--gray-400);font-size:0.85rem;">No panel members assigned to this schedule yet.</p>
<?php /*         Ends the assigned / not-assigned check. */ ?>
        <?php endif; ?>
<?php /*         Only shows the edit button when a schedule is loaded. */ ?>
        <?php if ($sch): ?>
<?php /*         Adds top margin above the edit button. */ ?>
        <div style="margin-top:0.75rem;">
<?php /*             Link to edit the schedule so panel members can be changed. */ ?>
            <a class="btn btn-primary coord-edit-btn" href="<?= url('coordinator/schedule/edit.php?id=' . urlencode($sch['id'])) ?>"><i class="fas fa-pen-to-square" aria-hidden="true"></i> Edit Panel in Schedule</a>
<?php /*         Closes the edit button wrapper. */ ?>
        </div>
<?php /*         Ends the schedule check. */ ?>
        <?php endif; ?>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /* Closes the assigned panel card. */ ?>
</div>

<?php /* Opens the card for the pool of available panel members. */ ?>
<div class="card">
<?php /*     Card header 'Panel Member Pool'. */ ?>
    <div class="card-header"><h3>Panel Member Pool</h3></div>
<?php /*     Opens the card body. */ ?>
    <div class="card-body">
<?php /*         Wraps the table so it scrolls sideways on small screens. */ ?>
        <div class="table-responsive">
<?php /*             Starts the panel member pool table. */ ?>
            <table class="data-table">
<?php /*                 Opens the table head section. */ ?>
                <thead>
<?php /*                     Header row: Name, Qualification, Panel Sessions, Availability. */ ?>
                    <tr><th>Name</th><th>Qualification</th><th>Panel Sessions</th><th>Availability</th></tr>
<?php /*                 Closes the table head section. */ ?>
                </thead>
<?php /*                 Starts the table body. */ ?>
                <tbody>
<?php /*                     Loops over every panel member in the pool. */ ?>
                    <?php foreach ($members as $pm): ?>
<?php /*                     Starts a table row for this panel member. */ ?>
                    <tr>
<?php /*                         Shows the panel member's name in bold. */ ?>
                        <td><strong><?= htmlspecialchars($pm['name']) ?></strong></td>
<?php /*                         Shows the member's qualification in smaller text. */ ?>
                        <td style="font-size:0.82rem;"><?= htmlspecialchars($pm['qualification']) ?></td>
<?php /*                         Shows how many panel sessions the member has joined. */ ?>
                        <td><?= (int) $pm['panelSessions'] ?></td>
<?php /*                         Opens the cell holding the availability form. */ ?>
                        <td>
<?php /*                             Small form that posts to toggle this member's availability. */ ?>
                            <form method="post">
<?php /*                                 Hidden field carrying the member id to the server. */ ?>
                                <input type="hidden" name="toggle_avail" value="<?= htmlspecialchars($pm['id']) ?>">
<?php /*                                 Submit button coloured green when available, outline when not. */ ?>
                                <button type="submit" class="btn btn-sm <?= ($pm['availability'] ?? '') === 'available' ? 'btn-success' : 'btn-outline' ?>" style="font-size:0.73rem;padding:2px 10px;">
<?php /*                                     Button label saying Available or Unavailable. */ ?>
                                    <?= ($pm['availability'] ?? '') === 'available' ? 'Available' : 'Unavailable' ?>
<?php /*                                 Closes the submit button. */ ?>
                                </button>
<?php /*                             Closes the small availability form. */ ?>
                            </form>
<?php /*                         Closes the availability cell. */ ?>
                        </td>
<?php /*                     Ends the table row for this member. */ ?>
                    </tr>
<?php /*                     Ends the loop over panel members. */ ?>
                    <?php endforeach; ?>
<?php /*                 Closes the table body. */ ?>
                </tbody>
<?php /*             Closes the panel member pool table. */ ?>
            </table>
<?php /*         Closes the scrolling table wrapper. */ ?>
        </div>
<?php /*     Closes the card body. */ ?>
    </div>
<?php /*     Opens the card footer with the back link. */ ?>
    <div class="card-footer">
<?php /*         Link returning to the schedule list page. */ ?>
        <a href="<?= url('coordinator/schedule/manage.php') ?>" class="btn btn-outline">Back to Schedules</a>
<?php /*     Closes the card footer. */ ?>
    </div>
<?php /* Closes the panel member pool card. */ ?>
</div>

<?php /* Loads the shared footer and closes the page. */ ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
