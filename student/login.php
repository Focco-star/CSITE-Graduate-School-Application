<?php /* Opens PHP mode so the server can run the login logic below. */ ?>
<?php
// Starts the session so the system can remember the logged-in student.
session_start();

// Loads config.php, which holds site settings plus the url() and asset() helpers.
require_once __DIR__ . '/../includes/config.php';
// Loads db.php, the small DB class used to look up users and students.
require_once __DIR__ . '/../includes/db.php';
// Loads google.php, which has the Google sign-in helper functions.
require_once __DIR__ . '/../includes/google.php';

// Checks whether the Google sign-in keys are set, giving true or false.
$googleConfigured = googleSignInConfigured();
// Creates a security state token for Google login, or empty text if not set up.
$googleState = $googleConfigured ? googleOAuthState() : '';
// Creates an empty variable to hold any login error message.
$loginError = '';

// Only runs the login check when the form was submitted by POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Reads the typed email, trims spaces, and falls back to empty text.
    $email    = trim($_POST['username'] ?? '');
    // Reads the typed password, or an empty string if it is missing.
    $password = $_POST['password'] ?? '';

    // Checks if either the email or the password field is empty.
    if (empty($email) || empty($password)) {
        // Sets the error message shown when a field is left blank.
        $loginError = 'Please enter both your email and password.';
    // Else branch: both fields have values, so continue the login attempt.
    } else {

        // Finds the user row in the database whose email matches what was typed.
        $user = DB::find('users', ['email' => $email]);

        // If the user exists and the stored password hash matches, log them in.
        if ($user && password_verify($password, $user['password'])) {
            // Blocks accounts whose role is not student from using this portal.
            if ($user['role'] !== 'student') {
                // Sets an error saying the account is not registered as a student.
                $loginError = 'Access denied. Account is not registered as a student.';
            // Else branch: the account is a student, so start the login session.
            } else {

                // Finds the student profile row that belongs to this user id.
                $student = DB::find('students', ['user_id' => $user['user_id']]);

                // Saves the user id in the session so other pages know who is logged in.
                $_SESSION['user_id']    = $user['user_id'];
                // Saves the student id in the session, or null if no profile exists yet.
                $_SESSION['student_id'] = $student['student_id'] ?? null;
                // Saves the full name in the session for greetings on later pages.
                $_SESSION['full_name']  = $user['full_name'];
                // Saves the email in the session so later pages can use it.
                $_SESSION['email']      = $user['email'];
                // Saves the role in the session, which later pages use for access control.
                $_SESSION['role']       = $user['role'];
                // Saves the whole user row in the session for quick access.
                $_SESSION['user']       = $user;
                // Shows a welcome flash message using the student's display name.
                setFlash('success', 'Welcome, Student ' . studentWelcomeName($student, $user) . '.');

                // Sends the browser to the student dashboard after a successful login.
                header('Location: ' . url('student/dashboard.php'));
                // Stops the script so no other code runs after the redirect.
                exit;
            // Closes the else block that handled a valid student account.
            }
        // Else branch: the user was not found or the password was wrong.
        } else {
            // Sets a generic error so nobody can tell which part was wrong.
            $loginError = 'Invalid email address or password.';
        // Closes the if that checked the user and password.
        }
    // Closes the else block for a non-empty email and password.
    }
// Closes the POST block that handled the submitted form.
}

// Sets the page title shown in the browser tab.
$pageTitle = 'Student Login';
// Sets the role to public so the header shows the guest menu.
$role = 'public';
// Adds a body CSS class used for the login page styling.
$bodyClass = 'login-body';
// Includes the shared header with the styles and navigation.
require_once __DIR__ . '/../includes/header.php';
// Includes the background slideshow shown behind the login card.
require_once __DIR__ . '/../includes/slideshow.php';
// Closes PHP mode and switches back to normal HTML output.
?>

<?php /* Wrapper div for the whole login page layout. */ ?>
<div class="login-page">
<?php /*     White card box that holds the login form and the links. */ ?>
    <div class="login-card">
<?php /*         Container div for the school seal image at the top of the card. */ ?>
        <div class="login-logo">
<?php /*             Shows the AdZU seal image, with asset() building the file path. */ ?>
            <img src="<?= asset('img/adzu-seal.png') ?>" alt="AdZU Seal">
<?php /*         Closes the logo container div. */ ?>
        </div>
<?php /*         Shows the short school name as the heading, escaped for safety. */ ?>
        <h1><?= htmlspecialchars(SITE_SHORT) ?></h1>
<?php /*         Subtitle telling students to sign in with their ADZU account. */ ?>
        <p class="login-subtitle">Student Portal — Sign in with your ADZU account</p>

<?php /*         PHP if: only shows the error box when there is a login error. */ ?>
        <?php if ($loginError): ?>
<?php /*         Red alert box that prints the login error message, escaped for safety. */ ?>
        <div class="alert alert-danger" style="margin-bottom:1rem;"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($loginError) ?></div>
<?php /*         Closes the PHP if that showed the login error box. */ ?>
        <?php endif; ?>

<?php /*         Login form that posts the email and password back to this page. */ ?>
        <form class="login-form" action="<?= url('student/login.php') ?>" method="post" data-validate>
<?php /*             Form group wrapper for the email label, icon and input. */ ?>
            <div class="form-group">
<?php /*                 Label for the email field, linked to it by the for attribute. */ ?>
                <label for="username">ADZU Email</label>
<?php /*                 Wrapper that places the icon inside the input area. */ ?>
                <div class="input-icon-wrap">
<?php /*                     Envelope icon shown inside the email input. */ ?>
                    <i class="fas fa-envelope"></i>
<?php /*                     Email input named username, required, and refilled with the typed value. */ ?>
                    <input type="email" id="username" name="username" placeholder="yourname@adzu.edu.ph" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
<?php /*                 Closes the input icon wrapper div. */ ?>
                </div>
<?php /*             Closes the email form group div. */ ?>
            </div>
<?php /*             Form group for the password field, with extra space at the bottom. */ ?>
            <div class="form-group" style="margin-bottom:1.75rem;">
<?php /*                 Label for the password field. */ ?>
                <label for="password">Password</label>
<?php /*                 Wrapper that places the lock icon inside the password input. */ ?>
                <div class="input-icon-wrap">
<?php /*                     Lock icon shown inside the password input. */ ?>
                    <i class="fas fa-lock"></i>
<?php /*                     Password input named password, required, with hidden characters. */ ?>
                    <input type="password" id="password" name="password" class="has-toggle" placeholder="Enter your password" required>
<?php /*                     Eye button that shows or hides the password, and does not submit the form. */ ?>
                    <button type="button" class="pw-toggle" data-toggle-password="password" tabindex="-1" aria-label="Show password">
<?php /*                         Eye icon inside the password toggle button. */ ?>
                        <i class="fas fa-eye"></i>
<?php /*                     Closes the password toggle button. */ ?>
                    </button>
<?php /*                 Closes the password input icon wrapper div. */ ?>
                </div>
<?php /*             Closes the password form group div. */ ?>
            </div>
<?php /*             Submit button that sends the login form to the server. */ ?>
            <button type="submit" class="btn btn-primary btn-lg">
<?php /*                 Sign-in icon and the Sign In button text. */ ?>
                <i class="fas fa-sign-in-alt"></i> Sign In
<?php /*             Closes the submit button. */ ?>
            </button>
<?php /*             Google button, type button so clicking it does not submit the form. */ ?>
            <button type="button" class="btn btn-google btn-lg" id="googleSignInBtn" style="margin-top:0.75rem;" aria-describedby="googleSigninError">
<?php /*                 Google icon and label text inside the Google button. */ ?>
                <i class="fab fa-google" id="googleSignInIcon"></i> <span id="googleSignInLabel">Sign in with Google</span>
<?php /*             Closes the Google sign-in button. */ ?>
            </button>
<?php /*             Hidden red alert box for Google sign-in errors, filled in by JavaScript. */ ?>
            <div class="alert alert-danger" id="googleSigninError" role="alert" style="display:none;margin-top:0.75rem;">
<?php /*                 Icon and an empty span that JavaScript fills with the error text. */ ?>
                <i class="fas fa-exclamation-circle"></i><span></span>
<?php /*             Closes the Google error alert div. */ ?>
            </div>
<?php /*         Closes the login form element. */ ?>
        </form>

<?php /*         Divider text asking new users whether they need an account. */ ?>
        <div class="login-divider">New to the system?</div>

<?php /*         Link styled as a button that goes to the student register page. */ ?>
        <a href="<?= url('student/register.php') ?>" class="btn btn-lg btn-ghost">
<?php /*             User-plus icon and the text for the create account button. */ ?>
            <i class="fas fa-user-plus"></i> Create Account / Set Up Profile
<?php /*         Closes the create account link. */ ?>
        </a>

<?php /*         Container div for the small helper links under the form. */ ?>
        <div class="login-links">
<?php /*             Small link that also leads to the student register page. */ ?>
            <a href="<?= url('student/register.php') ?>">Need an account? Register</a>
<?php /*         Closes the login links container div. */ ?>
        </div>
<?php /*         Container div for the back to home link. */ ?>
        <div class="login-back">
<?php /*             Link with a left arrow icon that returns to the public home page. */ ?>
            <a href="<?= url('public/index.php') ?>"><i class="fas fa-arrow-left"></i> Back to Home</a>
<?php /*         Closes the back to home container div. */ ?>
        </div>
<?php /*     Closes the login card div. */ ?>
    </div>
<?php /* Closes the login page wrapper div. */ ?>
</div>

<?php /* PHP if: only loads the Google script when Google sign-in is configured. */ ?>
<?php if ($googleConfigured): ?>
<?php /* Loads Google's sign-in library, async and defer so the page stays fast. */ ?>
<script src="https://accounts.google.com/gsi/client" async defer></script>
<?php /* Closes the PHP if that loaded the Google library. */ ?>
<?php endif; ?>
<?php /* Starts a JavaScript block for the Google sign-in button. */ ?>
<script>
<?php /* Starts an IIFE so these variables stay private to this script. */ ?>
(function () {
<?php /*     Gets the Google sign-in button element by its id. */ ?>
    var button = document.getElementById('googleSignInBtn');
<?php /*     Stops the script if the button is not on this page. */ ?>
    if (!button) return;

<?php /*     Puts the Google client id into JS safely, escaped by json_encode. */ ?>
    var clientId = <?= json_encode(googleClientId(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
<?php /*     Puts the security state token into JS as a safe string. */ ?>
    var state = <?= json_encode($googleState, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
<?php /*     Puts the Google auth endpoint URL into JS as a safe string. */ ?>
    var endpoint = <?= json_encode(url('student/google_auth.php'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
<?php /*     Gets the Google error alert box element. */ ?>
    var errorBox = document.getElementById('googleSigninError');
<?php /*     Gets the span inside the error box where the message is written. */ ?>
    var errorText = errorBox ? errorBox.querySelector('span') : null;
<?php /*     Gets the Google icon so it can be swapped while loading. */ ?>
    var icon = document.getElementById('googleSignInIcon');
<?php /*     Gets the Google button label so its text can be changed. */ ?>
    var label = document.getElementById('googleSignInLabel');
<?php /*     Holds the Google token client object, created on the first click. */ ?>
    var tokenClient = null;
<?php /*     Flag that is true while a Google sign-in is in progress. */ ?>
    var busy = false;
<?php /*     Flag that records whether Google already answered our request. */ ?>
    var callbackStarted = false;

<?php /*     Function that hides the Google error box and clears its text. */ ?>
    function hideError() {
<?php /*         Leaves early if the error box element does not exist. */ ?>
        if (!errorBox) return;
<?php /*         Hides the error box by setting its display to none. */ ?>
        errorBox.style.display = 'none';
<?php /*         Clears the old error text inside the span. */ ?>
        if (errorText) errorText.textContent = '';
<?php /*     Closes the hideError function. */ ?>
    }

<?php /*     Function that shows a Google error message inside the alert box. */ ?>
    function showError(message) {
<?php /*         Leaves early if the error box element does not exist. */ ?>
        if (!errorBox) return;
<?php /*         Writes the given message into the error span. */ ?>
        if (errorText) errorText.textContent = message;
<?php /*         Shows the error box using flex display. */ ?>
        errorBox.style.display = 'flex';
<?php /*     Closes the showError function. */ ?>
    }

<?php /*     Function that turns the loading state on or off for the button. */ ?>
    function setBusy(value) {
<?php /*         Stores the busy flag so other functions know sign-in is running. */ ?>
        busy = value;
<?php /*         Disables the button while busy so it cannot be clicked twice. */ ?>
        button.disabled = value;
<?php /*         Sets aria-busy so screen readers know the button is working. */ ?>
        button.setAttribute('aria-busy', value ? 'true' : 'false');
<?php /*         Swaps the Google icon for a spinning icon while busy. */ ?>
        if (icon) icon.className = value ? 'fas fa-circle-notch fa-spin' : 'fab fa-google';
<?php /*         Changes the button text to Connecting to Google while busy. */ ?>
        if (label) label.textContent = value ? 'Connecting to Google…' : 'Sign in with Google';
<?php /*     Closes the setBusy function. */ ?>
    }

<?php /*     Function that runs when Google returns a token or an error. */ ?>
    function handleToken(response) {
<?php /*         Marks that Google answered, so the cancel recovery does not fire. */ ?>
        callbackStarted = true;
<?php /*         Checks if Google gave no response or returned an error. */ ?>
        if (!response || response.error) {
<?php /*             Turns off the loading state on the button. */ ?>
            setBusy(false);
<?php /*             Tells the user that Google sign-in was cancelled. */ ?>
            showError('Google sign-in was cancelled. You can try again or use your email and password.');
<?php /*             Stops the function because there is no token to send. */ ?>
            return;
<?php /*         Closes the if that handled a cancelled or failed response. */ ?>
        }

<?php /*         Sends the Google access token to our server to verify and log in. */ ?>
        fetch(endpoint, {
<?php /*             Uses POST because data is being sent to the server. */ ?>
            method: 'POST',
<?php /*             Sends cookies too so the server keeps the same session. */ ?>
            credentials: 'same-origin',
<?php /*             Says that the request body is JSON. */ ?>
            headers: { 'Content-Type': 'application/json' },
<?php /*             Body holds the access token and the state token as JSON. */ ?>
            body: JSON.stringify({ access_token: response.access_token, state: state })
<?php /*         Handles the server reply once it arrives. */ ?>
        }).then(function (res) {
<?php /*             Reads the JSON reply, using an empty object if parsing fails. */ ?>
            return res.json().catch(function () { return {}; }).then(function (data) {
<?php /*                 If the reply is ok and has a redirect, the login worked. */ ?>
                if (res.ok && data.ok && data.redirect) {
<?php /*                     Sends the browser to the dashboard URL the server gave. */ ?>
                    window.location.href = data.redirect;
<?php /*                     Stops here so the error code below is not run. */ ?>
                    return;
<?php /*                 Closes the if that handled a successful Google login. */ ?>
                }
<?php /*                 Turns off the loading state since the login did not work. */ ?>
                setBusy(false);
<?php /*                 If the account already exists, the server sends back its email. */ ?>
                if (data.code === 'exists' && data.email) {
<?php /*                     Gets the email input so it can be prefilled for the student. */ ?>
                    var emailField = document.getElementById('username');
<?php /*                     Fills the email input with the email from the server. */ ?>
                    if (emailField) emailField.value = data.email;
<?php /*                 Closes the if that prefilled the email field. */ ?>
                }
<?php /*                 Shows the server message, or a general fallback message. */ ?>
                showError(data.message || 'Google sign-in could not be completed. Please try again.');
<?php /*             Closes the inner handler that read the JSON reply. */ ?>
            });
<?php /*         Handles network errors such as no internet connection. */ ?>
        }).catch(function () {
<?php /*             Turns off the loading state so the button works again. */ ?>
            setBusy(false);
<?php /*             Tells the user to check the connection and try again. */ ?>
            showError('Google sign-in could not be completed. Please check your connection and try again.');
<?php /*         Closes the catch handler of the fetch call. */ ?>
        });
<?php /*     Closes the handleToken function. */ ?>
    }

<?php /*     Adds a click listener to the Google sign-in button. */ ?>
    button.addEventListener('click', function () {
<?php /*         Ignores extra clicks while a sign-in is already running. */ ?>
        if (busy) return;
<?php /*         Clears any old Google error before starting a new attempt. */ ?>
        hideError();

<?php /*         If there is no client id, Google sign-in is not configured yet. */ ?>
        if (!clientId) {
<?php /*             Tells the user to use the email and password form instead. */ ?>
            showError('Google Sign-In is not set up yet. Please use your email and password, or create an account.');
<?php /*             Stops the handler because Google cannot start. */ ?>
            return;
<?php /*         Closes the if for a missing client id. */ ?>
        }

<?php /*         Checks that the Google library loaded and offers oauth2. */ ?>
        if (!(window.google && window.google.accounts && window.google.accounts.oauth2)) {
<?php /*             Tells the user that the Google library is still loading. */ ?>
            showError('Google Sign-In is still loading. Please try again in a moment.');
<?php /*             Stops the handler so it does not call a missing library. */ ?>
            return;
<?php /*         Closes the if that checked for the Google library. */ ?>
        }

<?php /*         Creates the token client only the first time it is needed. */ ?>
        if (!tokenClient) {
<?php /*             Starts a Google token client using our client id. */ ?>
            tokenClient = window.google.accounts.oauth2.initTokenClient({
<?php /*                 Passes the client id that identifies this app to Google. */ ?>
                client_id: clientId,
<?php /*                 Asks Google for the openid, email and profile details. */ ?>
                scope: 'openid email profile',
<?php /*                 Sets handleToken as the function that receives the result. */ ?>
                callback: handleToken
<?php /*             Closes the token client settings object. */ ?>
            });
<?php /*         Closes the if that created the token client. */ ?>
        }

<?php /*         Resets the answered flag before asking Google again. */ ?>
        callbackStarted = false;
<?php /*         Turns on the loading spinner on the Google button. */ ?>
        setBusy(true);
<?php /*         Opens the Google account picker and asks for an access token. */ ?>
        tokenClient.requestAccessToken({ prompt: 'select_account' });
<?php /*     Closes the click listener function. */ ?>
    });

<?php /*     Notes that closing the Google window means no token is returned. */ ?>
    // If the student closes the Google window without answering, the token
<?php /*     Explains that the callback then never runs, so the button must be reset. */ ?>
    // callback never runs. Give them their button back instead of leaving it
<?php /*     Notes the short delay that gives a slow Google answer time to arrive. */ ?>
    // spinning. The short delay lets a slow answer arrive first.
<?php /*     Function that restores the button if the Google window is closed. */ ?>
    function recoverIfCancelled() {
<?php /*         Does nothing if no sign-in is currently running. */ ?>
        if (!busy) return;
<?php /*         Waits two seconds so a slow Google answer can arrive first. */ ?>
        setTimeout(function () {
<?php /*             If still busy and Google never answered, treat it as cancelled. */ ?>
            if (busy && !callbackStarted) {
<?php /*                 Turns off the loading state to give the button back. */ ?>
                setBusy(false);
<?php /*                 Shows the cancelled message to the user. */ ?>
                showError('Google sign-in was cancelled. You can try again or use your email and password.');
<?php /*             Closes the if inside the timeout. */ ?>
            }
<?php /*         Closes the timeout, with 2000 milliseconds as the delay. */ ?>
        }, 2000);
<?php /*     Closes the recoverIfCancelled function. */ ?>
    }

<?php /*     Runs the cancel check when the browser window gets focus again. */ ?>
    window.addEventListener('focus', recoverIfCancelled);
<?php /*     Also listens for tab visibility changes, like returning to this tab. */ ?>
    document.addEventListener('visibilitychange', function () {
<?php /*         Checks if the page is visible again to the user. */ ?>
        if (document.visibilityState === 'visible') {
<?php /*             Runs the cancel check when the tab becomes visible. */ ?>
            recoverIfCancelled();
<?php /*         Closes the if that checked visibility. */ ?>
        }
<?php /*     Closes the visibilitychange listener function. */ ?>
    });
<?php /* Closes and runs the IIFE that set up the Google button. */ ?>
})();
<?php /* Closes the JavaScript block. */ ?>
</script>

<?php /* Includes the shared footer with the scripts and closing tags. */ ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
