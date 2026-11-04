<?php /* Starts the shared list of template updates. */ ?>
<?php
// Reads the update rows passed by the page.
$updateItems = is_array($updateItems ?? null) ? $updateItems : [];
// Stops when there is nothing to list.
if (!$updateItems) {
    // Returns without printing an empty list.
    return;
// Closes the empty-updates check.
}
?>
<?php /* Opens the list that holds one row per template update. */ ?>
<ul class="tpl-update-list">
<?php /*     Loops over every template update. */ ?>
    <?php foreach ($updateItems as $update): ?>
<?php /*     One update row, highlighted when it is new to the student. */ ?>
    <li class="tpl-update-item<?= !empty($update['isNew']) ? ' is-new' : '' ?>">
<?php /*         Plus or check icon depending on added or updated. */ ?>
        <i class="fas <?= ($update['change_type'] ?? '') === 'added' ? 'fa-file-circle-plus' : 'fa-file-circle-check' ?>" aria-hidden="true"></i>
<?php /*         Body of the update row. */ ?>
        <div class="tpl-update-body">
<?php /*             Shows the template file name. */ ?>
            <strong class="tpl-update-name"><?= htmlspecialchars((string) ($update['file_name'] ?? '')) ?></strong>
<?php /*             Small line of details under the file name. */ ?>
            <span class="tpl-update-meta">
<?php /*                 Says New template added or Template updated. */ ?>
                <?= ($update['change_type'] ?? '') === 'added' ? 'New template added' : 'Template updated' ?>
<?php /*                 Shows when the template was last changed. */ ?>
                &middot; Last updated <?= htmlspecialchars((string) ($update['changedLabel'] ?? '')) ?>
<?php /*                 Shows how long ago the change happened. */ ?>
                (<?= htmlspecialchars((string) ($update['ago'] ?? '')) ?>)
<?php /*             Closes the details line. */ ?>
            </span>
<?php /*         Closes the update row body. */ ?>
        </div>
<?php /*         Shows a New badge for updates the student has not seen. */ ?>
        <?php if (!empty($update['isNew'])): ?><span class="tpl-update-new">New</span><?php endif; ?>
<?php /*     Closes the update row. */ ?>
    </li>
<?php /*     Ends the loop over template updates. */ ?>
    <?php endforeach; ?>
<?php /* Closes the update list. */ ?>
</ul>
