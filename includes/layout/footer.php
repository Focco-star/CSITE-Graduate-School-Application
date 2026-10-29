<?php /* Opens PHP for the shared footer partial. */ ?>
<?php

// Uses the page role if one was set, otherwise treats the page as public.
$role = $role ?? 'public';
// Closes the PHP block so the HTML footer can be printed.
?>
<?php /* Checks whether this page belongs to a logged-in student or coordinator. */ ?>
<?php if ($role !== 'public'): ?>
<?php /*         Closes the main content area that the header opened. */ ?>
        </main>
<?php /*         Starts the footer used inside the application layout. */ ?>
        <footer class="app-footer">
<?php /*             Prints the current year and the university and college name. */ ?>
            <p>&copy; <?= date('Y') ?> Ateneo de Zamboanga University – College of Science and Information Technology</p>
<?php /*             Prints the site tagline below the copyright line. */ ?>
            <p class="footer-sub"><?= htmlspecialchars(SITE_TAGLINE) ?></p>
<?php /*         Closes the application footer. */ ?>
        </footer>
<?php /*     Closes the inner application layout wrapper. */ ?>
    </div>
<?php /* Closes the outer page wrapper. */ ?>
</div>
<?php /* Else branch for public pages that are not inside the app layout. */ ?>
<?php else: ?>
<?php /* Closes the main content area for a public page. */ ?>
</main>
<?php /* Starts the simpler footer used on public pages. */ ?>
<footer class="public-footer">
<?php /*     Starts the centered container that limits the footer width. */ ?>
    <div class="container">
<?php /*         Prints the current year and the graduate school name. */ ?>
        <p>&copy; <?= date('Y') ?> Ateneo de Zamboanga University – CSITE Graduate School</p>
<?php /*         Prints the site tagline below the copyright line. */ ?>
        <p class="footer-sub"><?= htmlspecialchars(SITE_TAGLINE) ?></p>
<?php /*     Closes the centered container. */ ?>
    </div>
<?php /* Closes the public footer. */ ?>
</footer>
<?php /* Ends the if/else that chose the app or public footer. */ ?>
<?php endif; ?>

<?php /* Starts the shared confirmation modal overlay. */ ?>
<div class="modal-overlay" id="globalConfirmModal" aria-hidden="true">
<?php /*     Starts the modal dialog box and labels it for screen readers. */ ?>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="globalConfirmTitle">
<?php /*         Shows the modal title and the close button. */ ?>
        <div class="modal-header"><h3 id="globalConfirmTitle">Please confirm</h3><button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button></div>
<?php /*         Holds the confirmation question that JavaScript fills in. */ ?>
        <div class="modal-body"><p id="globalConfirmMessage">Are you sure you want to continue?</p></div>
<?php /*         Shows the Cancel and Confirm buttons for the modal. */ ?>
        <div class="modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button type="button" class="btn btn-danger" id="globalConfirmAccept">Confirm</button></div>
<?php /*     Closes the modal dialog box. */ ?>
    </div>
<?php /* Closes the modal overlay. */ ?>
</div>

<?php /* Loads the shared interface script with a version based on its last edit time. */ ?>
<script src="<?= asset('js/shared/app.js?v=' . filemtime(__DIR__ . '/../../assets/js/shared/app.js')) ?>"></script>
<?php /* Loads the Student Population dashboard chart script. */ ?>
<script src="<?= asset('js/coordinator/population-dashboard.js?v=' . filemtime(__DIR__ . '/../../assets/js/coordinator/population-dashboard.js')) ?>"></script>
<?php /* Loads the presentation schedule calendar script last. */ ?>
<script src="<?= asset('js/coordinator/schedule-calendar.js?v=' . filemtime(__DIR__ . '/../../assets/js/coordinator/schedule-calendar.js')) ?>"></script>
<?php /* Closes the body element. */ ?>
</body>
<?php /* Closes the HTML document. */ ?>
</html>
