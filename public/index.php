<?php
$pageTitle = 'Home';
$role = 'public';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="hero">
    <div class="container hero-content">
        <div class="hero-logo">
            <img src="<?= asset('img/adzu-seal.png') ?>" alt="AdZU Seal">
        </div>
        <h1><?= htmlspecialchars(SITE_NAME) ?></h1>
        <p class="tagline"><?= htmlspecialchars(SITE_TAGLINE) ?></p>
        <div class="hero-actions">
            <a href="<?= url('student/login.php') ?>" class="btn btn-primary btn-lg">
                <i class="fas fa-user-graduate"></i> Student Login
            </a>
            <a href="<?= url('coordinator/login.php') ?>" class="btn btn-secondary btn-lg">
                <i class="fas fa-user-tie"></i> Coordinator Login
            </a>
        </div>
    </div>
</section>

<section class="info-section">
    <div class="container">
        <h2 class="section-title">About the System</h2>
        <p class="section-subtitle">
            A centralized online platform for CSITE graduate students to apply, submit requirements,
            and track their capstone or thesis presentation progress.
        </p>
        <div class="info-grid info-grid--static">
            <div class="info-card">
                <div class="icon"><i class="fas fa-file-upload"></i></div>
                <h3>Online Application</h3>
                <p>Apply for capstone or thesis presentations online. No need to visit the office to submit forms and documents.</p>
            </div>
            <div class="info-card">
                <div class="icon"><i class="fas fa-download"></i></div>
                <h3>Templates &amp; Forms</h3>
                <p>Download program-specific templates for concept papers, proposals, final presentations, and adviser endorsement forms.</p>
            </div>
            <div class="info-card">
                <div class="icon"><i class="fas fa-chart-line"></i></div>
                <h3>Status Tracking</h3>
                <p>Monitor your application status, document submissions, and presentation stage progress in real time.</p>
            </div>
        </div>
    </div>
</section>

<section class="info-section">
    <div class="container">
        <h2 class="section-title">Application Process</h2>
        <p class="section-subtitle">Follow these steps to apply for your capstone or thesis presentation.</p>
        <div class="process-steps">
            <div class="process-step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h4>Login with Your ADZU Account</h4>
                    <p>Sign in using your ADZU Gmail account.</p>
                </div>
            </div>
            <div class="process-step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h4>Download Required Templates</h4>
                    <p>Access and download the appropriate templates based on your program and presentation stage.</p>
                </div>
            </div>
            <div class="process-step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h4>Prepare Your Paper &amp; Get Adviser Endorsement</h4>
                    <p>Write your concept paper, proposal, or final paper. Have your adviser review and sign the endorsement form.</p>
                </div>
            </div>
            <div class="process-step">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h4>Upload Documents &amp; Submit Application</h4>
                    <p>Upload your paper and signed adviser endorsement. Submit your application for coordinator review.</p>
                </div>
            </div>
            <div class="process-step">
                <div class="step-number">5</div>
                <div class="step-content">
                    <h4>Presentation Scheduling</h4>
                    <p>Once approved, the coordinator schedules your presentation and assigns panel members.</p>
                </div>
            </div>
            <div class="process-step">
                <div class="step-number">6</div>
                <div class="step-content">
                    <h4>Complete &amp; Graduate</h4>
                    <p>After final presentation approval, submit your final copy to the Graduate School for hardbound and graduation.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="info-section">
    <div class="container">
        <h2 class="section-title">Presentation Paths</h2>
        <p class="section-subtitle">Your presentation stages depend on your program and research track.</p>
        <div class="track-grid">
            <div class="track-card">
                <div class="track-card-header"><i class="fas fa-book"></i> Thesis Track</div>
                <div class="track-card-body">
                    <p style="font-size:0.78rem;color:var(--gray-500);margin-bottom:0.75rem;">For MSCS students</p>
                    <ul class="track-stages">
                        <li><span class="stage-num">1</span> Concept Paper Presentation</li>
                        <li><span class="stage-num">2</span> Thesis Proposal Presentation</li>
                        <li><span class="stage-num">3</span> Final Thesis Defense</li>
                    </ul>
                </div>
            </div>
            <div class="track-card">
                <div class="track-card-header"><i class="fas fa-laptop-code"></i> Capstone Track</div>
                <div class="track-card-body">
                    <p style="font-size:0.78rem;color:var(--gray-500);margin-bottom:0.75rem;">For MIT, MLIS, MSEd-Bio, MSEd-Chem, MSEd-GenSci &amp; MSEd-Phys students</p>
                    <ul class="track-stages">
                        <li><span class="stage-num">1</span> Capstone Proposal Presentation</li>
                        <li><span class="stage-num">2</span> Final Capstone Presentation</li>
                    </ul>
                </div>
            </div>
            <div class="track-card">
                <div class="track-card-header"><i class="fas fa-scroll"></i> Seminar Paper Track</div>
                <div class="track-card-body">
                    <p style="font-size:0.78rem;color:var(--gray-500);margin-bottom:0.75rem;">For MMEd students</p>
                    <ul class="track-stages">
                        <li><span class="stage-num">1</span> Seminar Paper Proposal Presentation</li>
                        <li><span class="stage-num">2</span> Final Seminar Paper Presentation</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="info-section">
    <div class="container" style="text-align:center;">
        <h2 class="section-title">Ready to Get Started?</h2>
        <p class="section-subtitle">Login with your ADZU account to begin your application.</p>
        <a href="<?= url('student/login.php') ?>" class="btn btn-primary btn-lg">
            <i class="fas fa-sign-in-alt"></i> Go to Student Login
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
