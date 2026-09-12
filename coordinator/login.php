<?php
$pageTitle = 'Coordinator Login';
$role = 'public';
$bodyClass = 'login-body';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/slideshow.php';
?>

<div class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <img src="<?= asset('img/adzu-seal.png') ?>" alt="AdZU Seal">
        </div>
        <h1>Coordinator Portal</h1>
        <p class="login-subtitle"><?= htmlspecialchars(SITE_SHORT) ?> – Graduate Program Coordinator</p>

        <form class="login-form" action="<?= url('coordinator/dashboard.php') ?>" method="get" data-validate>
            <div class="form-group">
                <label for="username">Username / Email</label>
                <div class="input-icon-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="username" name="username" placeholder="gpc-csite@adzu.edu.ph" required>
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="has-toggle" placeholder="Password" required>
                    <button type="button" class="pw-toggle" data-toggle-password="password" tabindex="-1" aria-label="Show password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div class="login-back">
            <a href="<?= url('public/index.php') ?>"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
