<?php
/* CSITE configuration module: Status badges, navigation helpers, asset/URL builders, and track display helpers. */

// Builds the coloured badge HTML for a given status key.
function statusBadge(string $status): string {
    // Looks up the status details, falling back to a plain label.
    $info = STATUSES[$status] ?? ['label' => ucfirst($status), 'class' => 'status-pending'];
    // Returns the badge with the escaped label and its CSS class.
    return '<span class="status-badge ' . $info['class'] . '">' . htmlspecialchars($info['label']) . '</span>';
// Ends the statusBadge function.
}

// Returns the ordered stages for a track, from the database if possible.
function getStagesForTrack(string $track): array {
    // Thesis stages are the default fallback.
    $fallback = STAGES_THESIS;
    // Uses capstone stages when that track was asked for.
    if ($track === 'capstone') {
        // Switches the fallback to the capstone stages.
        $fallback = STAGES_CAPSTONE;
    // Uses seminar stages when that track was asked for.
    } elseif ($track === 'seminar') {
        // Switches the fallback to the seminar stages.
        $fallback = STAGES_SEMINAR;
    // Ends the track selection.
    }

    // Without the database class the built-in list is returned.
    if (!class_exists('DB')) {
        // Returns the fallback stage list.
        return $fallback;
    // Ends the database availability check.
    }

    // Tries to read the stages from the database.
    try {
        // Prepares a query for the stages of this track.
        $stmt = DB::getConnection()->prepare(
            // Query joins stages to their track and orders them for display.
            'SELECT workflow_stages.stage_key, workflow_stages.stage_label
             FROM workflow_stages
             INNER JOIN tracks ON tracks.track_id = workflow_stages.track_id
             WHERE tracks.track_code = :track
             ORDER BY workflow_stages.stage_order, workflow_stages.stage_id'
        // Ends the prepared query text.
        );
        // Runs the query with the track code as the parameter.
        $stmt->execute(['track' => $track]);
        // Collects stage key and label pairs.
        $stages = [];
        // Loops over every stage row returned by the database.
        foreach ($stmt->fetchAll() as $stage) {
            // Stores each stage label under its stage key.
            $stages[$stage['stage_key']] = $stage['stage_label'];
        // Ends the loop over stage rows.
        }
        // Returns the database stages, or the fallback when none exist.
        return $stages ?: $fallback;
    // Ignores database errors so the page still loads.
    } catch (Throwable $e) {
        // Falls back to the built-in stage list.
        return $fallback;
    // Ends the catch block.
    }
// Ends the getStagesForTrack function.
}

/**
 * The tracks a course can follow.
 *
 * The student picks the track during the account setup, so a course that can
 * be followed in more than one way offers all of them. The list comes from
 * the table program_tracks; the list in the code is the fallback for when the
 * table is not there yet.
 *
 * @return array<int, string> the track codes
 */
// Returns the track codes a program can follow.
function getTracksForProgram(string $program): array {
    // Trims spaces from the program code or name.
    $trimmed = trim($program);
    // No program was given, so there are no tracks.
    if ($trimmed === '') {
        // Returns an empty track list.
        return [];
    // Ends the empty program check.
    }

    // Accepts either a program code or its full name as input.
    // The name of a course is accepted as well as its code.
    // Starts by assuming the value is already the program code.
    $code = $trimmed;
    // Checks each known program name against the given value.
    foreach (PROGRAMS as $programCode => $label) {
        // Matches the full program name ignoring letter case.
        if (strcasecmp($label, $trimmed) === 0) {
            // Switches to the program code that matched.
            $code = $programCode;
            // Stops searching once the code is known.
            break;
        // Ends the program name check.
        }
    // Ends the loop over known programs.
    }

    // Tries the database tables when they are available.
    if (class_exists('DB')) {
        // Starts the database lookup.
        try {
            // Prepares a query for the tracks linked to the program.
            $stmt = DB::getConnection()->prepare(
                // Query joins programs, program_tracks and tracks by program code.
                'SELECT t.track_code
                   FROM programs p
                   INNER JOIN program_tracks pt ON pt.program_id = p.program_id
                   INNER JOIN tracks t ON t.track_id = pt.track_id
                  WHERE p.program_code = :code
                  ORDER BY pt.is_primary DESC, t.track_id'
            // Ends the prepared query text.
            );
            // Runs the query with the program code.
            $stmt->execute(['code' => $code]);
            // Collects the track codes found for the program.
            $tracks = [];
            // Loops over each joined row returned.
            foreach ($stmt->fetchAll() as $row) {
                // Normalises the track code to lowercase.
                $track = strtolower(trim((string) $row['track_code']));
                // Skips blanks and tracks already added.
                if ($track !== '' && !in_array($track, $tracks, true)) {
                    // Adds the new track code to the list.
                    $tracks[] = $track;
                // Ends the check on this row.
                }
            // Ends the loop over rows.
            }
            // Returns the database list when it is not empty.
            if ($tracks) {
                // Returns the tracks found in the database.
                return $tracks;
            // Ends the non-empty check.
            }
        // Falls back to the coded list if the query fails.
        } catch (Throwable $e) {
            // The fallback list below is used when the tables are missing.
            // Keep the list of the code when the table is not available.
        // Ends the catch block.
        }
    // Ends the database branch.
    }

    // Returns the built-in tracks for the code, or an empty list.
    return PROGRAM_TRACKS[$code] ?? [];
// Ends the getTracksForProgram function.
}

/**
 * The track a course belongs to when it can only be followed in one way.
 *
 * For a course with more than one track this is the track that is named first,
 * so the older code keeps working. The track a student really follows is the
 * one in their own record.
 */
// Returns the main track for a program.
function getTrackForProgram(string $program): string {
    // Trims spaces from the program value.
    $trimmed = trim($program);

    // Looks the program up in the database when possible.
    if ($trimmed !== '' && class_exists('DB')) {
        // Starts the database lookup.
        try {
            // Prepares a query for the track linked to the program.
            $stmt = DB::getConnection()->prepare(
                // Query matches the program by code, exact name, or partial name.
                'SELECT tracks.track_code
                 FROM programs
                 INNER JOIN tracks ON tracks.track_id = programs.track_id
                 WHERE programs.program_code = :program
                    OR programs.program_name = :program
                    OR programs.program_name LIKE :program_like
                 LIMIT 1'
            // Ends the prepared query text.
            );
            // Runs the query with the program values as parameters.
            $stmt->execute([
                // Binds the trimmed value to the code and exact name tests.
                'program' => $trimmed,
                // Binds a wildcard version for the partial name match.
                'program_like' => '%' . $trimmed . '%',
            // Ends the parameter array.
            ]);
            // Reads the first track code returned by the query.
            $databaseTrack = $stmt->fetchColumn();
            // Uses the database track when a valid code was returned.
            if (is_string($databaseTrack) && $databaseTrack !== '') {
                // Returns the track code in lowercase.
                return strtolower($databaseTrack);
            // Ends the valid code check.
            }
        // Ignores errors when the new tables are not set up yet.
        } catch (Throwable $e) {
            // The code mapping below is the emergency fallback.
            // Keep the existing program mapping when the new tables are unavailable.
        // Ends the catch block.
        }
    // Ends the database branch.
    }

    // Asks for the list of tracks for this program.
    $tracks = getTracksForProgram($trimmed);
    // Uses the first track when the list is not empty.
    if ($tracks) {
        // Returns the first track as the main track.
        return $tracks[0];
    // Ends the non-empty check.
    }

    // Detects a capstone track from text in the program name.
    if (stripos($trimmed, 'capstone') !== false) {
        // Returns the capstone track.
        return 'capstone';
    // Ends the capstone text check.
    }
    // Detects a seminar track from text in the program name.
    if (stripos($trimmed, 'seminar') !== false) {
        // Returns the seminar track.
        return 'seminar';
    // Ends the seminar text check.
    }
    // Detects a thesis track from text in the program name.
    if (stripos($trimmed, 'thesis') !== false) {
        // Returns the thesis track.
        return 'thesis';
    // Ends the thesis text check.
    }
    // Defaults to the thesis track when nothing else matched.
    return 'thesis';
// Ends the getTrackForProgram function.
}

// Returns the friendly label for a track code.
function getTrackLabel(string $track): string {
    // Capstone is shown as Capstone.
    if ($track === 'capstone') return 'Capstone';
    // The seminar track is shown as Seminar Paper.
    if ($track === 'seminar') return 'Seminar Paper';
    // Every other track is labelled Thesis.
    return 'Thesis';
// Ends the getTrackLabel function.
}

// Returns the active CSS class when the page matches the current one.
function navActive(string $page, string $current): string {
    // Compares the page key with the current page key.
    return $page === $current ? 'active' : '';
// Ends the navActive function.
}

// Builds a full URL for a file inside the assets folder.
function asset(string $path): string {
    // Joins the base URL with the assets path.
    return BASE_URL . '/assets/' . ltrim($path, '/');
// Ends the asset function.
}

// Builds a full URL for a path inside the site.
function url(string $path): string {
    // Joins the base URL with the given path.
    return BASE_URL . '/' . ltrim($path, '/');
// Ends the url function.
}

// Builds the download URL for a paper stored in the papers folder.
function papersUrl(string $folder, string $file): string {
    // Returns nothing when the folder or file name is missing.
    if ($folder === '' || $file === '') {
        // Returns an empty URL.
        return '';
    // Ends the missing name check.
    }
    // Encodes the folder and file names for a safe URL.
    return asset('papers/' . rawurlencode($folder) . '/' . rawurlencode($file));
// Ends the papersUrl function.
}
