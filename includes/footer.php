<?php

$role = $role ?? 'public';
?>
<?php if ($role !== 'public'): ?>
        </main>
        <footer class="app-footer">
            <p>&copy; <?= date('Y') ?> Ateneo de Zamboanga University – College of Science and Information Technology</p>
            <p class="footer-sub"><?= htmlspecialchars(SITE_TAGLINE) ?></p>
        </footer>
    </div>
</div>
<?php else: ?>
</main>
<footer class="public-footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> Ateneo de Zamboanga University – CSITE Graduate School</p>
        <p class="footer-sub"><?= htmlspecialchars(SITE_TAGLINE) ?></p>
    </div>
</footer>
<?php endif; ?>

<div class="modal-overlay" id="globalConfirmModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="globalConfirmTitle">
        <div class="modal-header"><h3 id="globalConfirmTitle">Please confirm</h3><button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button></div>
        <div class="modal-body"><p id="globalConfirmMessage">Are you sure you want to continue?</p></div>
        <div class="modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button type="button" class="btn btn-danger" id="globalConfirmAccept">Confirm</button></div>
    </div>
</div>

<script src="<?= asset('js/script.js') ?>"></script>
</body>
</html>
