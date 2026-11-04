<?php
/* Renders the coordinator comment notifications for one stage. */
// Stops when there is nothing to show.
if (empty($notifications) || !is_array($notifications)) {
    // Returns without printing anything.
    return;
// Closes the empty-notification check.
}
?>
<?php /* Opens the notification list for this stage. */ ?>
<div class="ms-notifications" aria-label="Coordinator comments">
<?php /*     Loops over every visible coordinator comment. */ ?>
    <?php foreach ($notifications as $notification): ?>
<?php /*     Opens one coordinator comment notification. */ ?>
    <div class="ms-notification">
<?php /*         Shows the comment icon. */ ?>
        <div class="ms-notification-icon" aria-hidden="true"><i class="fas fa-comment-dots"></i></div>
<?php /*         Holds the comment heading, text, and author. */ ?>
        <div class="ms-notification-main">
<?php /*             Opens the heading row with the label and date. */ ?>
            <div class="ms-notification-head">
<?php /*                 Names the notification type. */ ?>
                <strong>Coordinator comment</strong>
<?php /*                 Shows when the coordinator wrote the comment. */ ?>
                <span class="ms-notification-date"><?= htmlspecialchars(!empty($notification['createdAt']) ? date('M j, Y g:i A', strtotime((string) $notification['createdAt'])) : '') ?></span>
<?php /*             Closes the heading row. */ ?>
            </div>
<?php /*             Shows the comment text, keeping its line breaks. */ ?>
            <p class="ms-notification-text"><?= nl2br(htmlspecialchars((string) ($notification['text'] ?? ''))) ?></p>
<?php /*             Shows the coordinator name under the comment. */ ?>
            <div class="ms-notification-author">— <?= htmlspecialchars((string) ($notification['coordinatorName'] ?? 'Coordinator')) ?></div>
<?php /*         Closes the comment content. */ ?>
        </div>
<?php /*         Opens the form that dismisses this notification. */ ?>
        <form method="post" class="ms-notification-dismiss">
<?php /*             Identifies the comment being removed. */ ?>
            <input type="hidden" name="remove_comment_id" value="<?= (int) ($notification['commentId'] ?? 0) ?>">
<?php /*             Keeps the current stage when the Process Tracker reloads. */ ?>
            <?php if (!empty($notificationReturnStage)): ?>
            <input type="hidden" name="return_stage" value="<?= htmlspecialchars((string) $notificationReturnStage) ?>">
<?php /*             Closes the return-stage check. */ ?>
            <?php endif; ?>
<?php /*             The X button removes the comment notification. */ ?>
            <button type="submit" class="ms-notification-x" title="Remove this comment notification" aria-label="Remove this comment notification"><i class="fas fa-xmark" aria-hidden="true"></i></button>
<?php /*         Closes the dismiss form. */ ?>
        </form>
<?php /*     Closes this notification. */ ?>
    </div>
<?php /*     Ends the loop over notifications. */ ?>
    <?php endforeach; ?>
<?php /* Closes the notification list. */ ?>
</div>
