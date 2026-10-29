<?php /* Opens the PHP block that sets the Google Sign-In client id for the site. */ ?>
<?php
/**
 * Google Sign-In client for the student portal.
 *
 * 1. Open https://console.cloud.google.com/apis/credentials
 * 2. Create an OAuth 2.0 Client ID of type "Web application".
 * 3. Under "Authorised JavaScript origins" add:
 *      http://localhost
 *    (add the deployed domain as well when the site goes live).
 * 4. Paste the Client ID between the quotes below.
 *
 * The Client ID is a public value; it is safe to keep in the project. The
 * Client Secret is not used by this sign-in flow and must never be added here.
 *
 * GOOGLE_CLIENT_ID can also be supplied as an environment variable, which wins
 * over the value below.
 */

// Only defines the constant when something else, such as an env var, has not set it yet.
if (!defined('GOOGLE_CLIENT_ID')) {
    // Stores the public Google OAuth client id that the student portal uses to sign in.
    define('GOOGLE_CLIENT_ID', '441446697888-4uov8i42t8nqops0idb39295nm34v2vn.apps.googleusercontent.com');
// Closes the if block that guards the GOOGLE_CLIENT_ID definition.
}
