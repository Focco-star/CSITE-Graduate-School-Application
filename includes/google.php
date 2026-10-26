<?php /* <?php /* Google Sign-In helpers: verifies Google tokens before trusting profile data. * / */ ?>
<?php
/**
 * Google Sign-In helpers.
 *
 * The student login page asks Google Identity Services for an access token.
 * The token is posted to student/google_auth.php, and this file checks it with
 * Google before any of the profile data is trusted. Only the verified email,
 * name and picture are kept, and only for the registration step.
 *
 * The Client ID is not a secret. It lives in includes/google_config.php (or the
 * GOOGLE_CLIENT_ID environment variable) so the same code can run on XAMPP and
 * on the deployed site with a different OAuth client.
 */

// // Loads the shared config helpers, including formatPersonName used later in this file.
require_once __DIR__ . '/config.php';

// The Client ID lives outside this helper so it can differ between the local
// XAMPP copy and the deployed site.
// // Loads the optional google_config.php file that holds the OAuth client id.
if (is_file(__DIR__ . '/google_config.php')) {
    // // Includes the client-id config only when that file exists on this server.
    require_once __DIR__ . '/google_config.php';
// // Closes the check for google_config.php.
}

// // Runs the helper definitions only once, even if this file is included twice.
if (!defined('CSITE_GOOGLE_HELPER')) {
    // // Defines the CSITE_GOOGLE_HELPER flag that marks this file as already loaded.
    define('CSITE_GOOGLE_HELPER', true);

    // /** Documents googleClientId, which returns the Web OAuth client id or empty text. */
    /** The Web OAuth Client ID, or an empty string when sign-in is not set up. */
    // // Declares googleClientId, the function that supplies the OAuth client id.
    function googleClientId(): string
    // // Opens the body of googleClientId.
    {
        // // Reads the GOOGLE_CLIENT_ID environment variable, when the server sets one.
        $env = getenv('GOOGLE_CLIENT_ID');
        // // If the environment value is real non-empty text, use its trimmed form.
        if (is_string($env) && trim($env) !== '') {
            // // Returns the trimmed client id taken from the environment.
            return trim($env);
        // // Closes the environment-variable check.
        }
        // // Falls back to the GOOGLE_CLIENT_ID constant from google_config.php.
        if (defined('GOOGLE_CLIENT_ID')) {
            // // Returns the constant value as trimmed text.
            return trim((string) constant('GOOGLE_CLIENT_ID'));
        // // Closes the constant check.
        }
        // // Returns empty text, which means Google sign-in is not set up.
        return '';
    // // Closes googleClientId.
    }

    // // Declares googleSignInConfigured, which reports whether sign-in can be offered.
    function googleSignInConfigured(): bool
    // // Opens the body of googleSignInConfigured.
    {
        // // Returns true only when a client id was found.
        return googleClientId() !== '';
    // // Closes googleSignInConfigured.
    }

    // /** Documents googleOAuthState, the one-time value that blocks forged sign-ins. */
    /**
     * One-time value that ties the browser request to this session. It stops a
     * different site from pushing its own Google token into a student's form.
     */
    // // Declares googleOAuthState, which returns the anti-forgery state kept in the session.
    function googleOAuthState(): string
    // // Opens the body of googleOAuthState.
    {
        // // Creates a state value when the session has none or holds a non-text value.
        if (empty($_SESSION['google_oauth_state']) || !is_string($_SESSION['google_oauth_state'])) {
            // // Stores 16 random bytes as hex text, so the state is hard to guess.
            $_SESSION['google_oauth_state'] = bin2hex(random_bytes(16));
        // // Closes the check for a missing state value.
        }
        // // Returns the state stored in the session.
        return $_SESSION['google_oauth_state'];
    // // Closes googleOAuthState.
    }

    // // Declares googleOAuthStateMatches, which compares a posted state with the session one.
    function googleOAuthStateMatches(string $state): bool
    // // Opens the body of googleOAuthStateMatches.
    {
        // // Reads the expected state from the session, using empty text as the default.
        $expected = $_SESSION['google_oauth_state'] ?? '';
        // // Returns true only when both are non-empty and equal, using a safe comparison.
        return is_string($expected) && $expected !== '' && hash_equals($expected, $state);
    // // Closes googleOAuthStateMatches.
    }

    // // Declares googleRotateOAuthState, which replaces the state after a successful sign-in.
    function googleRotateOAuthState(): void
    // // Opens the body of googleRotateOAuthState.
    {
        // // Saves a fresh random state so the old value cannot be reused.
        $_SESSION['google_oauth_state'] = bin2hex(random_bytes(16));
    // // Closes googleRotateOAuthState.
    }

    // /** Documents googleHttpGet, a small GET helper with a timeout for Google calls. */
    /** Small GET helper with a timeout, so a slow Google cannot hang the page. */
    // // Declares googleHttpGet, which returns the response body text or null on failure.
    function googleHttpGet(string $url): ?string
    // // Opens the body of googleHttpGet.
    {
        // // Uses cURL when the PHP cURL extension is available.
        if (function_exists('curl_init')) {
            // // Starts a cURL request for the given Google URL.
            $handle = curl_init($url);
            // // Applies one option array to the request: return body, timeouts, no redirects.
            curl_setopt_array($handle, [
                // // Asks cURL to return the response body as text.
                CURLOPT_RETURNTRANSFER => true,
                // // Sets the total request timeout to 12 seconds.
                CURLOPT_TIMEOUT        => 12,
                // // Sets the connection timeout to 8 seconds.
                CURLOPT_CONNECTTIMEOUT => 8,
                // // Refuses to follow redirects, so the answer really comes from Google.
                CURLOPT_FOLLOWLOCATION => false,
                // // Sends a user agent that identifies this CSITE application.
                CURLOPT_USERAGENT      => 'CSITE-Graduate-School/1.0',
                // // Asks Google to reply with JSON.
                CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            // // Closes the cURL option array.
            ]);
            // // Runs the request and stores the response body.
            $body = curl_exec($handle);
            // // Reads the HTTP status code that Google returned.
            $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
            // // Reads the cURL error text, which is empty when the request worked.
            $curlError = curl_error($handle);
            // // Closes the cURL handle to free memory.
            curl_close($handle);

            // // If Google sent back a non-empty body, return it, even for 400 or 401 errors.
            if (is_string($body) && $body !== '') {
                // // Notes that error replies still carry Google's JSON error text.
                // 400/401 still carries Google's JSON error, which the caller
                // // Says the caller turns that JSON error into a readable message.
                // turns into a readable message.
                // // Returns the response body text.
                return $body;
            // // Closes the non-empty body check.
            }
            // // Accepts an empty body only when there was no cURL error and the status was 2xx.
            if ($curlError === '' && $status >= 200 && $status < 300) {
                // // Returns empty text for a successful request that had no body.
                return '';
            // // Closes the success check.
            }
            // // Returns null when the request failed, so the caller can show an error.
            return null;
        // // Closes the cURL branch.
        }

        // // Builds a stream context for servers without cURL, setting timeout and headers.
        $context = stream_context_create([
            // // Opens the http wrapper options.
            'http' => [
                // // Sets the same 12 second timeout for the fallback request.
                'timeout' => 12,
                // // Sends the Accept: application/json header.
                'header'  => "Accept: application/json\r\n",
            // // Closes the http wrapper options.
            ],
        // // Closes the stream context array.
        ]);
        // // Fetches the URL with file_get_contents and hides any warning.
        $body = @file_get_contents($url, false, $context);
        // // Returns the body text, or null when nothing was read.
        return is_string($body) ? $body : null;
    // // Closes googleHttpGet.
    }

    // /** Documents googleVerifyAccessToken, which validates a token with Google. */
    /**
     * Ask Google who owns an access token and make sure the token was issued
     * to this application.
     *
     * @return array{ok: bool, message?: string, profile?: array<string, mixed>}
     */
    // // Declares googleVerifyAccessToken, which checks the token and returns a result array.
    function googleVerifyAccessToken(string $accessToken): array
    // // Opens the body of googleVerifyAccessToken.
    {
        // // Trims spaces from the access token before checking it.
        $accessToken = trim($accessToken);
        // // Rejects empty, over-long or oddly formatted tokens before calling Google.
        if ($accessToken === '' || strlen($accessToken) > 4096 || !preg_match('/^[A-Za-z0-9._\-]+$/', $accessToken)) {
            // // Returns an error result when the token format is not acceptable.
            return ['ok' => false, 'message' => 'The Google sign-in response was not valid. Please try again.'];
        // // Closes the token format check.
        }

        // // Asks Google's tokeninfo endpoint who owns this access token.
        $body = googleHttpGet('https://oauth2.googleapis.com/tokeninfo?access_token=' . rawurlencode($accessToken));
        // // Handles the case where Google could not be reached at all.
        if ($body === null) {
            // // Returns an error result telling the student to check the connection.
            return ['ok' => false, 'message' => 'Google could not be reached. Please check your connection and try again.'];
        // // Closes the network failure check.
        }

        // // Decodes the JSON reply into a PHP array.
        $data = json_decode($body, true);
        // // Handles a reply that is not valid JSON.
        if (!is_array($data)) {
            // // Returns an error result for the unexpected Google response.
            return ['ok' => false, 'message' => 'Google returned an unexpected response. Please try again.'];
        // // Closes the JSON check.
        }

        // // Checks the decoded data and builds the profile result.
        return googleProfileFromTokenInfo($data);
    // // Closes googleVerifyAccessToken.
    }

    // /** Documents googleProfileFromTokenInfo, which validates the tokeninfo fields. */
    /**
     * Turn a tokeninfo response into the small profile the form uses. Kept
     * separate from the HTTP call so the checks can be tested on their own.
     *
     * @return array{ok: bool, message?: string, profile?: array<string, mixed>}
     */
    // // Declares googleProfileFromTokenInfo, which turns tokeninfo data into a profile.
    function googleProfileFromTokenInfo(array $data): array
    // // Opens the body of googleProfileFromTokenInfo.
    {
        // // Detects an error field in the Google reply.
        if (!empty($data['error'])) {
            // // Returns an error result when the token is invalid or revoked.
            return ['ok' => false, 'message' => 'That Google sign-in is no longer valid. Please try again.'];
        // // Closes the error field check.
        }

        // // Compares the token audience with this application's client id.
        // The token has to belong to this OAuth client, not another site.
        // // Reads the configured client id for the comparison.
        $clientId = googleClientId();
        // // Rejects the token when its audience belongs to a different OAuth client.
        if ($clientId !== '' && !hash_equals($clientId, (string) ($data['aud'] ?? ''))) {
            // // Returns an error result for a token issued to another application.
            return ['ok' => false, 'message' => 'The Google sign-in came from a different application. Please try again.'];
        // // Closes the audience check.
        }

        // // Reads the email from the token, lowercased and trimmed.
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        // // Rejects a missing or badly formed email address.
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // // Returns an error result when Google shared no usable email.
            return ['ok' => false, 'message' => 'Google did not share an email address for this account.'];
        // // Closes the email format check.
        }

        // // Reads the email_verified flag, which Google may send as bool, text or number.
        $verified = $data['email_verified'] ?? false;
        // // Accepts true, "true", 1 or "1" as a verified email.
        if (!($verified === true || $verified === 'true' || $verified === 1 || $verified === '1')) {
            // // Returns an error result asking the student to verify the email first.
            return ['ok' => false, 'message' => 'Please verify your Google email address first, then try again.'];
        // // Closes the email verification check.
        }

        // // Rejects a token whose expiry time has already passed.
        if (!empty($data['exp']) && (int) $data['exp'] < time()) {
            // // Returns an error result telling the student the sign-in expired.
            return ['ok' => false, 'message' => 'That Google sign-in has expired. Please try again.'];
        // // Closes the expiry check.
        }

        // // Builds the success result containing the cleaned profile fields.
        return [
            // // Marks the result as successful.
            'ok' => true,
            // // Starts the profile array kept for the registration step.
            'profile' => [
                // // Keeps Google's stable subject id for this account.
                'sub'          => (string) ($data['sub'] ?? ''),
                // // Keeps the verified, lowercased email address.
                'email'        => $email,
                // // Keeps the given (first) name from Google, trimmed.
                'given_name'   => trim((string) ($data['given_name'] ?? '')),
                // // Keeps the family (last) name from Google, trimmed.
                'family_name'  => trim((string) ($data['family_name'] ?? '')),
                // // Keeps the full display name from Google, trimmed.
                'name'         => trim((string) ($data['name'] ?? '')),
                // // Keeps the profile picture URL from Google.
                'picture'      => (string) ($data['picture'] ?? ''),
                // // Records when the token was verified, used later to expire the profile.
                'verified_at'  => time(),
            // // Closes the profile array.
            ],
        // // Closes the returned result array.
        ];
    // // Closes googleProfileFromTokenInfo.
    }

    // /** Documents googleNameParts, which works out a first and last name. */
    /**
     * Work out a first and last name from the Google profile, falling back to
     * the email local part when Google does not provide both parts.
     *
     * @param array<string, mixed> $profile
     * @return array{0: string, 1: string}
     */
    // // Declares googleNameParts, returning a first and last name pair.
    function googleNameParts(array $profile): array
    // // Opens the body of googleNameParts.
    {
        // // Reads the given name from the profile, trimmed.
        $first = trim((string) ($profile['given_name'] ?? ''));
        // // Reads the family name from the profile, trimmed.
        $last = trim((string) ($profile['family_name'] ?? ''));

        // // Fills a missing first or last name from the full name.
        if ($first === '' || $last === '') {
            // // Reads the full name from the profile, trimmed.
            $full = trim((string) ($profile['name'] ?? ''));
            // // Only splits the full name when it is not empty.
            if ($full !== '') {
                // // Splits the full name on runs of whitespace.
                $parts = preg_split('/\s+/', $full) ?: [];
                // // Removes empty pieces from the split result.
                $parts = array_values(array_filter($parts, static fn($part) => $part !== ''));
                // // Uses the first piece as the first name when it is still missing.
                if ($first === '' && $parts) {
                    // // Takes the first piece off the list.
                    $first = (string) array_shift($parts);
                // // Closes the first name fill.
                }
                // // Uses the remaining pieces as the last name when it is still missing.
                if ($last === '' && $parts) {
                    // // Joins the remaining pieces with spaces.
                    $last = implode(' ', $parts);
                // // Closes the last name fill.
                }
            // // Closes the full name check.
            }
        // // Closes the full name fallback.
        }

        // // Falls back to the email address when a name part is still missing.
        if ($first === '' || $last === '') {
            // // Derives first and last name guesses from the email address.
            [$emailFirst, $emailLast] = googleNamesFromEmail((string) ($profile['email'] ?? ''));
            // // Uses the email guess only when the first name is empty.
            if ($first === '') {
                // // Stores the first name guess.
                $first = $emailFirst;
            // // Closes the first name guess.
            }
            // // Uses the email guess only when the last name is empty.
            if ($last === '') {
                // // Stores the last name guess.
                $last = $emailLast;
            // // Closes the last name guess.
            }
        // // Closes the email fallback.
        }

        // // Returns both names cleaned up by formatPersonName.
        return [formatPersonName($first), formatPersonName($last)];
    // // Closes googleNameParts.
    }

    // /** Documents googleNamesFromEmail, which guesses names from an email address. */
    /**
     * Derive a name from an email local part. ADZU-style IDs such as
     * co222222@adzu.edu.ph carry no name, so they intentionally return blanks
     * and the student fills those fields in.
     *
     * @return array{0: string, 1: string}
     */
    // // Declares googleNamesFromEmail, returning a first and last name pair.
    function googleNamesFromEmail(string $email): array
    // // Opens the body of googleNamesFromEmail.
    {
        // // Lowercases and trims the email address.
        $email = strtolower(trim($email));
        // // Rejects an empty address or one without an @ sign.
        if ($email === '' || !str_contains($email, '@')) {
            // // Returns two empty names when the address is unusable.
            return ['', ''];
        // // Closes the email check.
        }

        // // Takes the part before the @ sign, called the email local part.
        $local = explode('@', $email, 2)[0];
        // // Explains that ADZU accounts carry an ID in the local part, not a name.
        // ADZU accounts such as co222222@adzu.edu.ph (and the staff prefixes)
        // // Notes that staff prefixes also carry numbers, not names.
        // carry an ID, not a name.
        // // Treats ID-style local parts such as co222222 as nameless.
        if ($local === '' || preg_match('/^(co|st|fac|admin|student)[._-]?\d+$/i', $local)) {
            // // Returns two empty names for ADZU ID style accounts.
            return ['', ''];
        // // Closes the ID style check.
        }

        // // Splits the local part on dots, underscores, plus signs and hyphens.
        $parts = preg_split('/[._+\-]+/', $local) ?: [];
        // // Cleans the pieces, keeping only likely name words.
        $parts = array_values(array_filter($parts, static function ($part) {
            // // Explains the filter: drop number fragments, keep name words.
            // Drop student-number fragments, keep name words.
            // // Keeps a piece when it is non-empty and has no long number.
            return $part !== '' && !preg_match('/\d{3,}/', $part);
        // // Closes the filter callback.
        }));
        // // Handles the case where every piece was dropped.
        if (empty($parts)) {
            // // Returns two empty names when nothing usable is left.
            return ['', ''];
        // // Closes the empty pieces check.
        }

        // // Uses the first remaining piece, capitalised, as the first name.
        $first = ucfirst((string) array_shift($parts));
        // // Joins the rest as the last name, or leaves it empty.
        $last = $parts ? ucwords(implode(' ', $parts)) : '';
        // // Returns the guessed first and last name.
        return [$first, $last];
    // // Closes googleNamesFromEmail.
    }

    // /** Documents googleStoreProfile, which keeps the verified profile in the session. */
    /** Keep the verified profile for the registration step only. */
    // // Declares googleStoreProfile, which saves the verified profile for registration.
    function googleStoreProfile(array $profile): void
    // // Opens the body of googleStoreProfile.
    {
        // // Stores only the safe profile fields under the session key google_profile.
        $_SESSION['google_profile'] = [
            // // Keeps Google's subject id for the account.
            'sub'         => (string) ($profile['sub'] ?? ''),
            // // Keeps the verified email address.
            'email'       => (string) ($profile['email'] ?? ''),
            // // Keeps the given name.
            'given_name'  => (string) ($profile['given_name'] ?? ''),
            // // Keeps the family name.
            'family_name' => (string) ($profile['family_name'] ?? ''),
            // // Keeps the full display name.
            'name'        => (string) ($profile['name'] ?? ''),
            // // Keeps the picture URL.
            'picture'     => (string) ($profile['picture'] ?? ''),
            // // Records when the profile was verified, defaulting to now.
            'verified_at' => (int) ($profile['verified_at'] ?? time()),
        // // Closes the session profile array.
        ];
    // // Closes googleStoreProfile.
    }

    // /** Documents googleSessionProfile, which returns the stored profile or null. */
    /** The verified profile, or null when there is none or it has gone stale. */
    // // Declares googleSessionProfile, returning the stored profile or null.
    function googleSessionProfile(): ?array
    // // Opens the body of googleSessionProfile.
    {
        // // Reads the stored Google profile from the session.
        $profile = $_SESSION['google_profile'] ?? null;
        // // Rejects a missing profile or one without an email address.
        if (!is_array($profile) || empty($profile['email'])) {
            // // Returns null so the form asks for Google sign-in again.
            return null;
        // // Closes the stored profile check.
        }

        // // Reads the verification time, defaulting to 0 when it is unknown.
        $verifiedAt = (int) ($profile['verified_at'] ?? 0);
        // // Expires the profile after one hour (3600 seconds) so stale data is dropped.
        if ($verifiedAt > 0 && (time() - $verifiedAt) > 3600) {
            // // Removes the expired profile from the session.
            unset($_SESSION['google_profile']);
            // // Returns null because the profile is too old.
            return null;
        // // Closes the expiry check.
        }

        // // Returns the profile while it is still fresh.
        return $profile;
    // // Closes googleSessionProfile.
    }

    // // Declares googleClearProfile, which forgets the stored Google profile.
    function googleClearProfile(): void
    // // Opens the body of googleClearProfile.
    {
        // // Removes the google_profile value from the session.
        unset($_SESSION['google_profile']);
    // // Closes googleClearProfile.
    }
// // Closes the defined-helper guard block opened on line 23.
}
