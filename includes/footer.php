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

<script src="<?= asset('js/script.js') ?>"></script>
</body>
</html>
