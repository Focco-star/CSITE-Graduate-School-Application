<?php /* Opens the JSON endpoint that verifies a Google sign-in token. */ ?>
<?php
/**
 * Receives the access token from "Sign in with Google", checks it with Google,
 * and remembers the verified profile for the registration form.
 *
 * Nothing the browser says about the student is trusted: the email and name
 * come from Google's tokeninfo response, and the token must belong to this
 * application.
 */

// Loads the shared configuration, helpers and session settings.
require_once __DIR__ . '/../includes/config/config.php';
// Loads the database class used to check whether the user already exists.
require_once __DIR__ . '/../includes/config/db.php';
// Loads the Google helper functions that check tokens and store profiles.
require_once __DIR__ . '/../includes/integrations/google.php';

// Tells the browser that the reply is JSON text in UTF-8.
header('Content-Type: application/json; charset=utf-8');
// Forbids the browser and proxies from storing this response.
header('Cache-Control: no-store');
// Stops the browser from guessing the content type of the reply.
header('X-Content-Type-Options: nosniff');

// Defines one helper that always answers with JSON and then stops.
function googleAuthReply(array $payload, int $status = 200): void
// Opens the body of the googleAuthReply helper function.
{
    // Sets the HTTP status code for the reply, 200 by default.
    http_response_code($status);
    // Prints the payload array as JSON without escaping slashes in URLs.
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    // Stops the script so nothing else is sent after the JSON reply.
    exit;
// Closes the googleAuthReply helper function.
}

// Only POST requests may sign in with Google; anything else is rejected.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Starts the error reply payload for a wrong request method.
    googleAuthReply([
        // Marks the reply as failed with ok set to false.
        'ok' => false,
        // Gives the student a short, friendly error message.
        'message' => 'Google sign-in could not be completed.',
    // Sends the error reply with status 405 Method Not Allowed.
    ], 405);
// Closes the if block that rejected non-POST requests.
}

// Checks whether the Google client id and secret are configured.
if (!googleSignInConfigured()) {
    // Starts the error reply payload for an unconfigured Google sign-in.
    googleAuthReply([
        // Marks the reply as failed.
        'ok' => false,
        // Tells the student to use their email and password instead.
        'message' => 'Google Sign-In is not set up yet. Please use your email and password instead.',
    // Sends the reply with status 503 Service Unavailable.
    ], 503);
// Closes the if block that handled missing Google settings.
}

// Reads the raw request body, which should hold the token as JSON.
$raw = file_get_contents('php://input');
// Decodes the JSON body into a PHP array so its keys can be read.
$payload = json_decode((string) $raw, true);
// If the body was not valid JSON, fall back to normal form fields.
if (!is_array($payload)) {
    // Uses the form fields from $_POST as the payload.
    $payload = $_POST;
// Closes the if block that handled a non-JSON body.
}

// Reads the Google access token sent by the page, or empty text.
$accessToken = (string) ($payload['access_token'] ?? '');
// Reads the state value used to block cross-site request forgery.
$state = (string) ($payload['state'] ?? '');

// Checks that the state value matches the one saved in the session.
if (!googleOAuthStateMatches($state)) {
    // Starts the error reply payload for an expired sign-in session.
    googleAuthReply([
        // Marks the reply as failed.
        'ok' => false,
        // Asks the student to reload the page and try again.
        'message' => 'Your sign-in session expired. Please reload the page and try again.',
    // Sends the reply with status 400 Bad Request.
    ], 400);
// Closes the if block that rejected a bad state value.
}

// Asks Google to verify the token and return the real profile details.
$result = googleVerifyAccessToken($accessToken);
// If verification failed, reply with the error instead of trusting the browser.
if (empty($result['ok'])) {
    // Starts the error reply payload for a token Google did not accept.
    googleAuthReply([
        // Marks the reply as failed.
        'ok' => false,
        // Uses Google's own error message, or a generic one when it is missing.
        'message' => (string) ($result['message'] ?? 'Google sign-in could not be verified.'),
    // Sends the reply with status 401 Unauthorized.
    ], 401);
// Closes the if block that handled failed token verification.
}

// Takes the verified profile array out of the verification result.
$profile = $result['profile'];
// Reads the verified email address that Google returned.
$email = (string) $profile['email'];

// An existing profile cannot be created twice. Point the student at the normal
// sign-in form instead of failing after they have filled the whole page in.
// Looks for an existing user with this email so no duplicate account is made.
if (DB::find('users', ['email' => $email])) {
    // Starts the reply that tells the student they already have a profile.
    googleAuthReply([
        // Marks the reply as failed.
        'ok' => false,
        // Adds the code exists so the page can react specially.
        'code' => 'exists',
        // Returns the email so the sign-in form can prefill it.
        'email' => $email,
        // Tells the student to sign in with their password instead.
        'message' => 'This Google account already has a profile. Please sign in with your password.',
    // Sends the reply with status 409 Conflict.
    ], 409);
// Closes the if block that handled an already registered email.
}

// Creates a fresh state value so the old one cannot be reused.
googleRotateOAuthState();
// Saves the verified Google profile for the registration form.
googleStoreProfile($profile);

// Starts the success reply that sends the student to registration.
googleAuthReply([
    // Marks the reply as successful.
    'ok' => true,
    // Returns the verified email address.
    'email' => $email,
    // Gives the page the address of the registration form with google=1.
    'redirect' => url('student/register.php?google=1'),
// Sends the success reply and ends the script.
]);
