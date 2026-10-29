<?php /* Opens the PHP block that builds the public home page of the system. */ ?>
<?php
// Sets the browser tab title to Home before the shared header is loaded.
$pageTitle = 'Home';
// Marks this page as public so the header shows the guest navigation links.
$role = 'public';
// Includes the shared header file with the CSS, fonts and top navigation bar.
require_once __DIR__ . '/../includes/layout/header.php';
// Closes the PHP block so the HTML below is sent to the browser as-is.
?>

<?php /* Opens the hero banner section holding the school seal and login buttons. */ ?>
<section class="hero">
<?php /*     Opens the centered container div that limits the hero content width. */ ?>
    <div class="container hero-content">
<?php /*         Opens the div that positions the round ATENEO seal inside the hero. */ ?>
        <div class="hero-logo">
<?php /*             Shows the AdZU seal image, using asset() to build its URL in /assets/img. */ ?>
            <img src="<?= asset('img/adzu-seal.png') ?>" alt="AdZU Seal">
<?php /*         Closes the seal wrapper div. */ ?>
        </div>
<?php /*         Prints the site name from the SITE_NAME constant as the main heading. */ ?>
        <h1><?= htmlspecialchars(SITE_NAME) ?></h1>
<?php /*         Shows the SITE_TAGLINE constant as a short subtitle under the heading. */ ?>
        <p class="tagline"><?= htmlspecialchars(SITE_TAGLINE) ?></p>
<?php /*         Opens the row that holds the two login buttons side by side. */ ?>
        <div class="hero-actions">
<?php /*             Starts a link that goes to the student login page built by url(). */ ?>
            <a href="<?= url('student/login.php') ?>" class="btn btn-primary btn-lg">
<?php /*                 Graduate cap icon plus the label Student Login inside the button. */ ?>
                <i class="fas fa-user-graduate"></i> Student Login
<?php /*             Closes the Student Login link. */ ?>
            </a>
<?php /*             Starts a link that goes to the coordinator login page. */ ?>
            <a href="<?= url('coordinator/login.php') ?>" class="btn btn-secondary btn-lg">
<?php /*                 Tie icon plus the label Coordinator Login inside the button. */ ?>
                <i class="fas fa-user-tie"></i> Coordinator Login
<?php /*             Closes the Coordinator Login link. */ ?>
            </a>
<?php /*         Closes the hero actions row div. */ ?>
        </div>
<?php /*     Closes the hero content container div. */ ?>
    </div>
<?php /* Closes the hero banner section. */ ?>
</section>

<?php /* Opens the About section that explains what the system is for. */ ?>
<section class="info-section">
<?php /*     Opens the container div that centers the About content. */ ?>
    <div class="container">
<?php /*         Shows the section heading About the System. */ ?>
        <h2 class="section-title">About the System</h2>
<?php /*         Opens the paragraph that introduces the system in one sentence. */ ?>
        <p class="section-subtitle">
<?php /*             First text line of the intro: an online platform for CSITE graduate students. */ ?>
            A centralized online platform for CSITE graduate students to apply, submit requirements,
<?php /*             Second text line: students track capstone, seminar paper or thesis progress. */ ?>
            and track their capstone, seminar paper or thesis presentation progress.
<?php /*         Closes the intro paragraph. */ ?>
        </p>
<?php /*         Opens the three-column grid that holds the feature cards. */ ?>
        <div class="info-grid info-grid--static">
<?php /*             Opens the first feature card about online application. */ ?>
            <div class="info-card">
<?php /*                 Opens the icon box and draws the upload file icon. */ ?>
                <div class="icon"><i class="fas fa-file-upload"></i></div>
<?php /*                 Card title Online Application. */ ?>
                <h3>Online Application</h3>
<?php /*                 Card text saying forms and documents can be submitted without visiting the office. */ ?>
                <p>Apply for capstone, seminar paper or thesis presentations online. No need to visit the office to submit forms and documents.</p>
<?php /*             Closes the Online Application card. */ ?>
            </div>
<?php /*             Opens the second feature card about downloadable templates. */ ?>
            <div class="info-card">
<?php /*                 Opens the icon box and draws the download icon. */ ?>
                <div class="icon"><i class="fas fa-download"></i></div>
<?php /*                 Card title Templates &amp; Forms, where &amp; prints an ampersand. */ ?>
                <h3>Templates &amp; Forms</h3>
<?php /*                 Card text listing the templates students can download. */ ?>
                <p>Download program-specific templates for concept papers, proposals, final presentations, and adviser endorsement forms.</p>
<?php /*             Closes the Templates and Forms card. */ ?>
            </div>
<?php /*             Opens the third feature card about status tracking. */ ?>
            <div class="info-card">
<?php /*                 Opens the icon box and draws the chart line icon. */ ?>
                <div class="icon"><i class="fas fa-chart-line"></i></div>
<?php /*                 Card title Status Tracking. */ ?>
                <h3>Status Tracking</h3>
<?php /*                 Card text about monitoring application status and stage progress. */ ?>
                <p>Monitor your application status, document submissions, and presentation stage progress in real time.</p>
<?php /*             Closes the Status Tracking card. */ ?>
            </div>
<?php /*         Closes the feature grid div. */ ?>
        </div>
<?php /*     Closes the About container div. */ ?>
    </div>
<?php /* Closes the About section. */ ?>
</section>

<?php /* Opens the Application Process section listing the six steps. */ ?>
<section class="info-section">
<?php /*     Opens the container div for the process section. */ ?>
    <div class="container">
<?php /*         Shows the heading Application Process. */ ?>
        <h2 class="section-title">Application Process</h2>
<?php /*         Tells the reader these steps explain how to apply for a presentation. */ ?>
        <p class="section-subtitle">Follow these steps to apply for your capstone, seminar paper or thesis presentation.</p>
<?php /*         Opens the wrapper div that holds all six numbered process steps. */ ?>
        <div class="process-steps">
<?php /*             Opens the card for step 1 of the process. */ ?>
            <div class="process-step">
<?php /*                 Shows the big number 1 on the left of the step. */ ?>
                <div class="step-number">1</div>
<?php /*                 Opens the div that holds the step 1 title and description. */ ?>
                <div class="step-content">
<?php /*                     Step 1 title: log in with your ADZU account. */ ?>
                    <h4>Login with Your ADZU Account</h4>
<?php /*                     Step 1 text telling students to sign in with their ADZU Gmail. */ ?>
                    <p>Sign in using your ADZU Gmail account.</p>
<?php /*                 Closes the step 1 text block. */ ?>
                </div>
<?php /*             Closes the step 1 card. */ ?>
            </div>
<?php /*             Opens the card for step 2 of the process. */ ?>
            <div class="process-step">
<?php /*                 Shows the big number 2 on the left of the step. */ ?>
                <div class="step-number">2</div>
<?php /*                 Opens the div that holds the step 2 title and description. */ ?>
                <div class="step-content">
<?php /*                     Step 2 title: download the required templates. */ ?>
                    <h4>Download Required Templates</h4>
<?php /*                     Step 2 text about picking templates for your program and stage. */ ?>
                    <p>Access and download the appropriate templates based on your program and presentation stage.</p>
<?php /*                 Closes the step 2 text block. */ ?>
                </div>
<?php /*             Closes the step 2 card. */ ?>
            </div>
<?php /*             Opens the card for step 3 of the process. */ ?>
            <div class="process-step">
<?php /*                 Shows the big number 3 on the left of the step. */ ?>
                <div class="step-number">3</div>
<?php /*                 Opens the div that holds the step 3 title and description. */ ?>
                <div class="step-content">
<?php /*                     Step 3 title: prepare the paper and get adviser endorsement. */ ?>
                    <h4>Prepare Your Paper &amp; Get Adviser Endorsement</h4>
<?php /*                     Step 3 text: write the paper and have the adviser sign the endorsement form. */ ?>
                    <p>Write your concept paper, proposal, or final paper. Have your adviser review and sign the endorsement form.</p>
<?php /*                 Closes the step 3 text block. */ ?>
                </div>
<?php /*             Closes the step 3 card. */ ?>
            </div>
<?php /*             Opens the card for step 4 of the process. */ ?>
            <div class="process-step">
<?php /*                 Shows the big number 4 on the left of the step. */ ?>
                <div class="step-number">4</div>
<?php /*                 Opens the div that holds the step 4 title and description. */ ?>
                <div class="step-content">
<?php /*                     Step 4 title: upload documents and submit the application. */ ?>
                    <h4>Upload Documents &amp; Submit Application</h4>
<?php /*                     Step 4 text: upload the paper and endorsement, then send for review. */ ?>
                    <p>Upload your paper and signed adviser endorsement. Submit your application for coordinator review.</p>
<?php /*                 Closes the step 4 text block. */ ?>
                </div>
<?php /*             Closes the step 4 card. */ ?>
            </div>
<?php /*             Opens the card for step 5 of the process. */ ?>
            <div class="process-step">
<?php /*                 Shows the big number 5 on the left of the step. */ ?>
                <div class="step-number">5</div>
<?php /*                 Opens the div that holds the step 5 title and description. */ ?>
                <div class="step-content">
<?php /*                     Step 5 title: presentation scheduling. */ ?>
                    <h4>Presentation Scheduling</h4>
<?php /*                     Step 5 text: the coordinator schedules the defense and picks the panel. */ ?>
                    <p>Once approved, the coordinator schedules your presentation and assigns panel members.</p>
<?php /*                 Closes the step 5 text block. */ ?>
                </div>
<?php /*             Closes the step 5 card. */ ?>
            </div>
<?php /*             Opens the card for step 6 of the process. */ ?>
            <div class="process-step">
<?php /*                 Shows the big number 6 on the left of the step. */ ?>
                <div class="step-number">6</div>
<?php /*                 Opens the div that holds the step 6 title and description. */ ?>
                <div class="step-content">
<?php /*                     Step 6 title: complete and graduate. */ ?>
                    <h4>Complete &amp; Graduate</h4>
<?php /*                     Step 6 text about submitting the final hardbound copy after approval. */ ?>
                    <p>After final presentation approval, submit your final copy to the Graduate School for hardbound and graduation.</p>
<?php /*                 Closes the step 6 text block. */ ?>
                </div>
<?php /*             Closes the step 6 card. */ ?>
            </div>
<?php /*         Closes the wrapper div holding all six process steps. */ ?>
        </div>
<?php /*     Closes the process section container div. */ ?>
    </div>
<?php /* Closes the Application Process section. */ ?>
</section>

<?php /* Opens the Presentation Paths section that compares the three tracks. */ ?>
<section class="info-section">
<?php /*     Opens the container div for the tracks section. */ ?>
    <div class="container">
<?php /*         Shows the heading Presentation Paths. */ ?>
        <h2 class="section-title">Presentation Paths</h2>
<?php /*         Explains that stages depend on the student's program and research track. */ ?>
        <p class="section-subtitle">Your presentation stages depend on your program and research track.</p>
<?php /*         Opens the grid that lays the three track cards side by side. */ ?>
        <div class="track-grid">
<?php /*             Opens the Thesis Track card. */ ?>
            <div class="track-card">
<?php /*                 Card header with a book icon and the label Thesis Track. */ ?>
                <div class="track-card-header"><i class="fas fa-book"></i> Thesis Track</div>
<?php /*                 Opens the card body that holds the program note and stage list. */ ?>
                <div class="track-card-body">
<?php /*                     Small grey note saying the thesis track is for MSCS and MSMEd students. */ ?>
                    <p style="font-size:0.78rem;color:var(--gray-500);margin-bottom:0.75rem;">For MSCS &amp; MSMEd students</p>
<?php /*                     Opens the bullet list of thesis presentation stages. */ ?>
                    <ul class="track-stages">
<?php /*                         Stage 1 of the thesis track: concept paper presentation. */ ?>
                        <li><span class="stage-num">1</span> Concept Paper Presentation</li>
<?php /*                         Stage 2 of the thesis track: thesis proposal presentation. */ ?>
                        <li><span class="stage-num">2</span> Thesis Proposal Presentation</li>
<?php /*                         Stage 3 of the thesis track: final thesis defense. */ ?>
                        <li><span class="stage-num">3</span> Final Thesis Defense</li>
<?php /*                     Closes the thesis stage list. */ ?>
                    </ul>
<?php /*                 Closes the Thesis card body. */ ?>
                </div>
<?php /*             Closes the Thesis Track card. */ ?>
            </div>
<?php /*             Opens the Capstone Track card. */ ?>
            <div class="track-card">
<?php /*                 Card header with a laptop code icon and the label Capstone Track. */ ?>
                <div class="track-card-header"><i class="fas fa-laptop-code"></i> Capstone Track</div>
<?php /*                 Opens the card body that holds the program note and stage list. */ ?>
                <div class="track-card-body">
<?php /*                     Small grey note listing the programs that follow the capstone track. */ ?>
                    <p style="font-size:0.78rem;color:var(--gray-500);margin-bottom:0.75rem;">For MIT, MLIS, MSEd-Bio, MSEd-Chem, MSEd-GenSci &amp; MSEd-Phys students</p>
<?php /*                     Opens the bullet list of capstone presentation stages. */ ?>
                    <ul class="track-stages">
<?php /*                         Stage 1 of the capstone track: capstone proposal presentation. */ ?>
                        <li><span class="stage-num">1</span> Capstone Proposal Presentation</li>
<?php /*                         Stage 2 of the capstone track: final capstone presentation. */ ?>
                        <li><span class="stage-num">2</span> Final Capstone Presentation</li>
<?php /*                     Closes the capstone stage list. */ ?>
                    </ul>
<?php /*                 Closes the Capstone card body. */ ?>
                </div>
<?php /*             Closes the Capstone Track card. */ ?>
            </div>
<?php /*             Opens the Seminar Paper Track card. */ ?>
            <div class="track-card">
<?php /*                 Card header with a scroll icon and the label Seminar Paper Track. */ ?>
                <div class="track-card-header"><i class="fas fa-scroll"></i> Seminar Paper Track</div>
<?php /*                 Opens the card body that holds the program note and stage list. */ ?>
                <div class="track-card-body">
<?php /*                     Small grey note saying the seminar paper track is for MMEd students. */ ?>
                    <p style="font-size:0.78rem;color:var(--gray-500);margin-bottom:0.75rem;">For MMEd students</p>
<?php /*                     Opens the bullet list of seminar paper presentation stages. */ ?>
                    <ul class="track-stages">
<?php /*                         Stage 1 of the seminar track: seminar paper proposal presentation. */ ?>
                        <li><span class="stage-num">1</span> Seminar Paper Proposal Presentation</li>
<?php /*                         Stage 2 of the seminar track: final seminar paper presentation. */ ?>
                        <li><span class="stage-num">2</span> Final Seminar Paper Presentation</li>
<?php /*                     Closes the seminar paper stage list. */ ?>
                    </ul>
<?php /*                 Closes the Seminar Paper card body. */ ?>
                </div>
<?php /*             Closes the Seminar Paper Track card. */ ?>
            </div>
<?php /*         Closes the track grid div. */ ?>
        </div>
<?php /*     Closes the tracks section container div. */ ?>
    </div>
<?php /* Closes the Presentation Paths section. */ ?>
</section>

<?php /* Opens the closing section that invites visitors to log in. */ ?>
<section class="info-section">
<?php /*     Opens the container div and centers its text with an inline style. */ ?>
    <div class="container" style="text-align:center;">
<?php /*         Shows the heading Ready to Get Started?. */ ?>
        <h2 class="section-title">Ready to Get Started?</h2>
<?php /*         Tells visitors to log in with their ADZU account to start applying. */ ?>
        <p class="section-subtitle">Login with your ADZU account to begin your application.</p>
<?php /*         Starts a large primary button linking to the student login page. */ ?>
        <a href="<?= url('student/login.php') ?>" class="btn btn-primary btn-lg">
<?php /*             Sign-in icon plus the label Go to Student Login. */ ?>
            <i class="fas fa-sign-in-alt"></i> Go to Student Login
<?php /*         Closes the student login button link. */ ?>
        </a>
<?php /*     Closes the closing section container div. */ ?>
    </div>
<?php /* Closes the closing call-to-action section. */ ?>
</section>

<?php /* Includes the shared footer file with the closing scripts, then ends the page. */ ?>
<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
