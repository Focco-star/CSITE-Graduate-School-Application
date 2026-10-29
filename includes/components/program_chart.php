<?php /* <?php /* Shared "Student Population by Academic Program" chart used by the dashboards. * / */ ?>
<?php
/**
 * Shared "Student Population by Academic Program" component.
 *
 * Used by coordinator/dashboard.php and coordinator/reports/dashboard.php so
 * both pages show the same data, colors and chart behavior. The heading can be
 * adapted per page through the optional $options array.
 *
 * Usage:  renderProgramPopulationChart($pdo ?? null);
 *         renderProgramPopulationChart($pdo ?? null, ['title' => '...']);
 */

// // Defines the helpers only once, even when several pages include this file.
if (!function_exists('programPopulationPalette')) {

    // /** Documents programPopulationPalette, the fixed list of chart colours. */
    /** Brand-grounded, high-contrast palette. Colors beyond 12 programs are generated. */
    // // Declares programPopulationPalette, which returns the chart colour list.
    function programPopulationPalette(): array
    // // Opens the body of programPopulationPalette.
    {
        // // Returns the ordered list of brand and accent colours.
        return [
            // // Palette entry 1: Ateneo navy, used for the largest program.
            '#060297', // Ateneo navy
            // // Palette entry 2: Ateneo gold.
            '#FFB82B', // Ateneo gold
            // // Palette entry 3: rose.
            '#f43f5e', // rose
            // // Palette entry 4: emerald.
            '#10b981', // emerald
            // // Palette entry 5: purple.
            '#8b5cf6', // purple
            // // Palette entry 6: Google blue.
            '#4285F4', // blue
            // // Palette entry 7: deep orange.
            '#ea580c', // deep orange
            // // Palette entry 8: dark teal.
            '#0f766e', // dark teal
            // // Palette entry 9: cyan.
            '#0891b2', // cyan
            // // Palette entry 10: slate grey.
            '#64748b', // slate
            // // Palette entry 11: lime.
            '#84cc16', // lime
            // // Palette entry 12: brown.
            '#7c2d12', // brown
        // // Closes the palette array.
        ];
    // // Closes programPopulationPalette.
    }

    // // Declares programPopulationColor, which picks a colour for a program index.
    function programPopulationColor(int $index): string
    // // Opens the body of programPopulationColor.
    {
        // // Loads the fixed palette of twelve colours.
        $palette = programPopulationPalette();
        // // Uses a fixed palette colour when the index is still inside the list.
        if ($index < count($palette)) {
            // // Returns the palette colour for this index.
            return $palette[$index];
        // // Closes the fixed palette check.
        }
        // // Counts how far past the palette this index is.
        $extra = $index - count($palette);
        // // Builds a hue with the golden angle so extra colours stay distinct.
        $hue   = (int) round(fmod($extra * 137.508 + 18, 360));
        // // Varies lightness slightly for each extra program.
        $light = 38 + ($extra % 3) * 8;
        // // Returns a generated HSL colour for programs past the palette.
        return "hsl({$hue}, 62%, {$light}%)";
    // // Closes programPopulationColor.
    }

    // /** Documents programPopulationInk, which picks readable label text colour. */
    /** Readable text color (for labels drawn on top of a slice). */
    // // Declares programPopulationInk, returning dark or white text for a background.
    function programPopulationInk(string $color): string
    // // Opens the body of programPopulationInk.
    {
        // // Matches six-digit hex colours so their RGB parts can be read.
        if (preg_match('/^#([0-9a-f]{6})$/i', $color, $m)) {
            // // Reads the red part of the hex colour.
            $r = hexdec(substr($m[1], 0, 2));
            // // Reads the green part of the hex colour.
            $g = hexdec(substr($m[1], 2, 2));
            // // Reads the blue part of the hex colour.
            $b = hexdec(substr($m[1], 4, 2));
            // // Calculates brightness from the three colour parts on a 0 to 1 scale.
            $lum = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
            // // Returns dark text for light backgrounds and white for dark ones.
            return $lum > 0.62 ? '#1a222c' : '#ffffff';
        // // Closes the hex colour check.
        }
        // // Defaults to white text for colours that are not hex, such as HSL.
        return '#ffffff';
    // // Closes programPopulationInk.
    }

    // /** Documents programPopulationLabels, the code-to-name map. */
    // // Declares programPopulationLabels, which returns program display names.
    function programPopulationLabels(): array
    // // Opens the body of programPopulationLabels.
    {
        return [
            'mscs' => 'Master of Science in Computer Science',
            'master of science in computer science' => 'Master of Science in Computer Science',
            'mslis' => 'Master of Library and Information Science',
            'master of library and information science' => 'Master of Library and Information Science',
            'math' => 'Master in Mathematics Education',
            'master in mathematics education' => 'Master in Mathematics Education',
        ];
    // // Closes programPopulationLabels.
    }

    // // Declares programPopulationConnection, which returns a usable PDO connection.
    function programPopulationConnection(?PDO $pdo): ?PDO
    // // Opens the body of programPopulationConnection.
    {
        // // Reuses the connection passed in by the page when it is a PDO object.
        if ($pdo instanceof PDO) {
            // // Returns that connection.
            return $pdo;
        // // Closes the passed-in connection check.
        }
        // // Uses the global $pdo when one is already open.
        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            // // Returns the global connection.
            return $GLOBALS['pdo'];
        // // Closes the global connection check.
        }
        // // Tries to open a new MySQL connection with the project settings.
        try {
            // // Reads the database host, defaulting to localhost.
            $host = defined('DB_HOST') ? DB_HOST : 'localhost';
            // // Reads the database name, defaulting to csite_grad_school.
            $name = defined('DB_NAME') ? DB_NAME : 'csite_grad_school';
            // // Reads the database user, defaulting to root.
            $user = defined('DB_USER') ? DB_USER : 'root';
            // // Reads the database password, defaulting to empty text.
            $pass = defined('DB_PASS') ? DB_PASS : '';
            // // Opens a PDO connection to the CSITE database using utf8mb4.
            return new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $pass, [
                // // Makes database errors throw exceptions so they can be caught.
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                // // Fetches rows as associative arrays by default.
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // // Closes the PDO options array.
            ]);
        // // Catches any connection failure, such as a stopped MySQL service.
        } catch (Throwable $e) {
            // // Returns null so callers fall back to session data.
            return null;
        // // Closes the try/catch block.
        }
    // // Closes programPopulationConnection.
    }

    // /** Documents programPopulationCounts, the program name to student count map. */
    /** @return array<string,int> program name => number of active students */
    // // Declares programPopulationCounts, which counts active students per program.
    function programPopulationCounts(?PDO $pdo): array
    // // Opens the body of programPopulationCounts.
    {
        // // Gets a connection, either the one passed in or a fresh one.
        $pdo = programPopulationConnection($pdo);

        // // Only queries the database when a connection is available.
        if ($pdo) {
            // // Tries the SQL query and falls back to session data on failure.
            try {
                // // Prepares and runs the counting query on the students table.
                $stmt = $pdo->query(
                    // // Selects the program name, or 'Unspecified Program', with the row count.
                    "SELECT COALESCE(NULLIF(TRIM(program), ''), 'Unspecified Program') AS program_name,
                            COUNT(*) AS total
                     FROM students
                     WHERE archived_at IS NULL
                     GROUP BY program_name"
                // // Closes the query call.
                );
                // // Starts the map of program name to student count.
                $counts = [];
                // // Loops over every row returned by the query.
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    // // Stores the count for this program name as an integer.
                    $counts[(string) $row['program_name']] = (int) $row['total'];
                // // Closes the row loop.
                }
                // // Returns the counts keyed by program name.
                return $counts;
            // // Catches any query error, such as a missing students table.
            } catch (Throwable $e) {
                // // Notes that execution continues to the session-based fallback below.
                // fall through to the session store
            // // Closes the try/catch block.
            }
        // // Closes the database branch.
        }

        // // Falls back to session storage when the project has storeGet available.
        if (function_exists('storeGet')) {
            // // Tries the session fallback and returns no data if it fails.
            try {
                // // Starts the map of program name to student count.
                $counts = [];
                // // Loops over the students stored in the session.
                foreach ((array) (storeGet('students') ?? []) as $s) {
                    // // Skips entries that are not arrays or that are archived.
                    if (!is_array($s) || !empty($s['archivedAt'] ?? '')) {
                        // // Continues to the next stored student.
                        continue;
                    // // Closes the skip check.
                    }
                    // // Reads the stored program code, trimmed.
                    $name = trim((string) ($s['program'] ?? ''));
                    // // Handles a stored student with no program filled in.
                    if ($name === '') {
                        // // Labels such students as 'Unspecified Program'.
                        $name = 'Unspecified Program';
                    // // Closes the empty program check.
                    }
                    // // Adds one to the count for this program.
                    $counts[$name] = ($counts[$name] ?? 0) + 1;
                // // Closes the stored student loop.
                }
                // // Returns the counts built from session data.
                return $counts;
            // // Catches any error while reading session data.
            } catch (Throwable $e) {
                // // Returns no data when the session fallback fails.
                return [];
            // // Closes the try/catch block.
            }
        // // Closes the storeGet branch.
        }

        // // Returns an empty map when neither the database nor the session has data.
        return [];
    // // Closes programPopulationCounts.
    }

    // /** Documents programPopulationData, which builds the chart data array. */
    /**
     * Colors are assigned alphabetically by program, so a program keeps the same
     * color in every chart type and on both dashboards. Output is sorted by size.
     */
    // // Declares programPopulationData, returning the slices, colours and total.
    function programPopulationData(?PDO $pdo = null): array
    // // Opens the body of programPopulationData.
    {
        // // Gets the student count for every program.
        $counts = programPopulationCounts($pdo);
        // // Gets optional display names for program codes.
        $labels = programPopulationLabels();
        $normalizedCounts = [];
        foreach ($counts as $name => $count) {
            $name = trim((string) $name);
            $label = $labels[strtolower($name)] ?? ($name !== '' ? $name : 'Unspecified Program');
            $normalizedCounts[$label] = ($normalizedCounts[$label] ?? 0) + (int) $count;
        }
        $counts = $normalizedCounts;

        // // Turns the program keys into strings for sorting.
        $names = array_map('strval', array_keys($counts));
        // // Sorts program names in natural, case-insensitive alphabetical order.
        natcasesort($names);
        // // Reindexes the sorted names from zero.
        $names = array_values($names);

        // // Starts the map of program name to chart colour.
        $colors = [];
        // // Loops over the sorted program names with their positions.
        foreach ($names as $i => $name) {
            // // Gives each program a colour based on its alphabetical position.
            $colors[$name] = programPopulationColor($i);
        // // Closes the colour loop.
        }

        // // Starts the running total of students.
        $total = 0;
        // // Loops over every program count.
        foreach ($counts as $c) {
            // // Counts only positive numbers.
            if ($c > 0) {
                // // Adds this program's count to the total.
                $total += (int) $c;
            // // Closes the positive count check.
            }
        // // Closes the total loop.
        }

        // // Starts the list of chart slices.
        $programs = [];
        // // Loops over each program name and its count.
        foreach ($counts as $name => $count) {
            // // Casts the program name to text.
            $name  = (string) $name;
            // // Casts the count to an integer.
            $count = (int) $count;
            // // Skips programs with a zero or negative count.
            if ($count <= 0) {
                // // Continues to the next program.
                continue;
            // // Closes the zero count check.
            }
            // // Gets the colour assigned to this program.
            $color      = $colors[$name];
            // // Adds one chart slice for this program.
            $programs[] = [
                // // Stores the raw program name as the slice key.
                'key'     => $name,
                // // Uses the normalized full program name.
                'label'   => $name,
                // // Stores the number of students.
                'count'   => $count,
                // // Stores the share of all students, with one decimal place.
                'percent' => number_format($count / $total * 100, 1, '.', ''),
                // // Stores the slice colour.
                'color'   => $color,
                // // Stores the readable label text colour.
                'ink'     => programPopulationInk($color),
            // // Closes the slice array.
            ];
        // // Closes the program loop.
        }

        // // Sorts the slices so the biggest program comes first.
        usort($programs, static function ($a, $b) {
            // // Compares counts when the two slices differ in size.
            if ($a['count'] !== $b['count']) {
                // // Puts the larger count first, giving descending order.
                return $b['count'] <=> $a['count'];
            // // Closes the count comparison.
            }
            // // Breaks ties alphabetically by label.
            return strcasecmp($a['label'], $b['label']);
        // // Closes the sort callback.
        });

        // // Returns the sorted slices and the total number of students.
        return ['programs' => $programs, 'total' => $total];
    // // Closes programPopulationData.
    }

    // // Declares renderProgramPopulationChart, which prints the whole chart section.
    function renderProgramPopulationChart(?PDO $pdo = null, array $options = []): void
    // // Opens the body of renderProgramPopulationChart.
    {
        // // Keeps a counter that is shared across every call to this function.
        static $instance = 0;
        // // Increases the counter so each chart on a page gets a unique id.
        $instance++;
        // // Builds the unique id prefix used by the markup and its ARIA links.
        $uid = 'popChart' . $instance;

        // // Explains that pages may override the heading text through $options.
        // The heading can be adapted by the page that embeds the chart. The
        // // Notes that the defaults keep the usual wording on other pages.
        // defaults keep the established wording on every other page.
        // // Reads the small eyebrow text, defaulting to the dashboard wording.
        $eyebrow  = trim((string) ($options['eyebrow'] ?? 'Student Population Dashboard'));
        // // Reads the main heading, defaulting to the university name.
        $heading  = trim((string) ($options['title'] ?? 'Ateneo de Zamboanga University'));
        // // Reads the subtitle, defaulting to the program population wording.
        $subtitle = trim((string) ($options['subtitle'] ?? 'Student population by academic program'));
        // // Reads the Font Awesome icon class, defaulting to fa-university.
        $icon     = trim((string) ($options['icon'] ?? 'fa-university'));

        // // Tries to build the chart data.
        try {
            // // Collects program counts, colours and the total from the database.
            $data = programPopulationData($pdo);
        // // Catches any error while building the data.
        } catch (Throwable $e) {
            // // Falls back to an empty chart so the page still renders.
            $data = ['programs' => [], 'total' => 0];
        // // Closes the try/catch block.
        }

        // // Reads the list of program slices.
        $programs = $data['programs'];
        // // Reads the total number of students as an integer.
        $total    = (int) $data['total'];
        // // Defines a small helper that escapes text for safe HTML output.
        $e        = static function ($v) {
            // // Escapes the value with htmlspecialchars using UTF-8 and quotes.
            return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        // // Closes the escaping helper.
        };

        // // Lists the four chart types offered in the dropdown.
        $types = [
            // // Bar chart: menu label and Font Awesome icon class.
            'bar'   => ['Bar Chart',            'fa-chart-bar'],
            // // Horizontal bar chart entry.
            'hbar'  => ['Horizontal Bar Chart', 'fa-align-left'],
            // // Pie chart entry.
            'pie'   => ['Pie Chart',            'fa-chart-pie'],
            // // Donut chart entry.
            'donut' => ['Donut Chart',          'fa-circle-notch'],
        // // Closes the chart type list.
        ];

        // // Encodes the chart data as JSON for the page script.
        $json = json_encode(
            // // Passes the slices and the total inside the JSON payload.
            ['programs' => $programs, 'total' => $total],
            // // Escapes HTML-sensitive characters so the JSON is safe inside a script tag.
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
        // // Closes the json_encode call.
        );
        // // Handles a JSON encoding failure.
        if ($json === false) {
            // // Uses an empty payload so the script still gets valid JSON.
            $json = '{"programs":[],"total":0}';
        // // Closes the JSON failure check.
        }
        // // Ends PHP so the chart markup below can be printed.
        ?>
<?php /* <!-- Chart section with a unique id, an ARIA label and a hook attribute for the script. --> */ ?>
<section class="pop-dashboard" id="<?= $e($uid) ?>" data-program-chart aria-labelledby="<?= $e($uid) ?>Title">
<?php /*     <!-- Banner header showing the icon, the titles and the total students. --> */ ?>
    <header class="pop-banner">
<?php /*         <!-- Round mark holding the chosen Font Awesome icon. --> */ ?>
        <div class="pop-banner-mark" aria-hidden="true"><i class="fas <?= $e($icon) ?>"></i></div>
<?php /*         <!-- Text block holding the eyebrow, heading and subtitle. --> */ ?>
        <div class="pop-banner-text">
<?php /*             <!-- Small eyebrow label above the heading, escaped for safety. --> */ ?>
            <span class="pop-eyebrow"><?= $e($eyebrow) ?></span>
<?php /*             <!-- Main heading, also the ARIA label target for the whole section. --> */ ?>
            <h2 class="pop-title" id="<?= $e($uid) ?>Title"><?= $e($heading) ?></h2>
<?php /*             <!-- Subtitle line under the heading. --> */ ?>
            <p class="pop-subtitle"><?= $e($subtitle) ?></p>
<?php /*         <!-- Closes the banner text block. --> */ ?>
        </div>
<?php /*         <!-- Box on the right showing the total number of students. --> */ ?>
        <div class="pop-banner-total">
<?php /*             <!-- The total student count with thousands separators. --> */ ?>
            <strong><?= number_format($total) ?></strong>
<?php /*             <!-- Label under the total count. --> */ ?>
            <span>Total Students</span>
<?php /*         <!-- Closes the total box. --> */ ?>
        </div>
<?php /*     <!-- Closes the banner header. --> */ ?>
    </header>

<?php /*     <!-- Panel holding the chart controls, the chart area and the legend. --> */ ?>
    <div class="pop-panel">
<?php /*     <?php /* Shows the empty state when there are no slices or the total is zero. * / ?> */ ?>
    <?php if (!$programs || $total <= 0): ?>
<?php /*         <!-- Empty state box shown when no student records exist. --> */ ?>
        <div class="empty-state">
<?php /*             <!-- Graduation cap icon for the empty state. --> */ ?>
            <i class="fas fa-user-graduate"></i>
<?php /*             <!-- Empty state title. --> */ ?>
            <h4>No student records yet</h4>
<?php /*             <!-- Empty state explanation for the coordinator. --> */ ?>
            <p>The chart will appear once students are enrolled.</p>
<?php /*         <!-- Closes the empty state box. --> */ ?>
        </div>
<?php /*     <?php /* Otherwise prints the chart, the type menu and the legend. * / ?> */ ?>
    <?php else: ?>
<?php /*         <!-- Header row above the chart with the title and the type selector. --> */ ?>
        <div class="pop-panel-head">
<?php /*             <!-- Wrapper for the chart title and its description. --> */ ?>
            <div class="pop-panel-heading">
<?php /*                 <!-- Chart title, updated by the script for each chart type. --> */ ?>
                <h3 data-pop-chart-title>Total Students per Program</h3>
<?php /*                 <!-- Empty description line that the script fills in. --> */ ?>
                <p data-pop-chart-desc></p>
<?php /*             <!-- Closes the title wrapper. --> */ ?>
            </div>

<?php /*             <!-- Custom dropdown that lets the user pick the chart type. --> */ ?>
            <div class="pop-select" data-pop-select>
<?php /*                 <!-- Button that opens and closes the chart type menu. --> */ ?>
                <button type="button" class="pop-select-btn" data-pop-toggle
<?php /*                         <!-- ARIA attributes describing the menu button and the menu it controls. --> */ ?>
                        aria-haspopup="menu" aria-expanded="false" aria-controls="<?= $e($uid) ?>Menu">
<?php /*                     <!-- Icon showing the currently selected chart type. --> */ ?>
                    <i class="fas fa-chart-bar" data-pop-current-icon aria-hidden="true"></i>
<?php /*                     <!-- Text label of the currently selected chart type. --> */ ?>
                    <span data-pop-current-label>Bar Chart</span>
<?php /*                     <!-- Small chevron showing that the menu can be opened. --> */ ?>
                    <i class="fas fa-chevron-down pop-select-caret" aria-hidden="true"></i>
<?php /*                 <!-- Closes the dropdown button. --> */ ?>
                </button>
<?php /*                 <!-- Menu list of chart types, hidden until the user opens it. --> */ ?>
                <ul class="pop-menu" id="<?= $e($uid) ?>Menu" role="menu" aria-label="Chart type" data-pop-menu hidden>
<?php /*                     <?php /* Loops over the four chart types to build the menu items. * / ?> */ ?>
                    <?php foreach ($types as $key => $t): ?>
<?php /*                     <!-- One menu row per chart type. --> */ ?>
                    <li role="none">
<?php /*                         <!-- Menu item button holding the type key, label and icon. --> */ ?>
                        <button type="button" role="menuitemradio" aria-checked="false" class="pop-menu-item"
<?php /*                                 <!-- Data attributes the script reads: type key, label and icon class. --> */ ?>
                                data-chart-type="<?= $e($key) ?>" data-label="<?= $e($t[0]) ?>" data-icon="<?= $e($t[1]) ?>">
<?php /*                             <!-- Icon for this chart type. --> */ ?>
                            <i class="fas <?= $e($t[1]) ?>" aria-hidden="true"></i>
<?php /*                             <!-- Visible label for this chart type. --> */ ?>
                            <span><?= $e($t[0]) ?></span>
<?php /*                             <!-- Check mark shown next to the selected chart type. --> */ ?>
                            <i class="fas fa-check pop-check" aria-hidden="true"></i>
<?php /*                         <!-- Closes the menu item button. --> */ ?>
                        </button>
<?php /*                     <!-- Closes the menu row. --> */ ?>
                    </li>
<?php /*                     <?php /* Ends the chart type loop. * / ?> */ ?>
                    <?php endforeach; ?>
<?php /*                 <!-- Closes the chart type menu. --> */ ?>
                </ul>
<?php /*             <!-- Closes the dropdown wrapper. --> */ ?>
            </div>
<?php /*         <!-- Closes the panel header row. --> */ ?>
        </div>

<?php /*         <!-- Body area holding the chart stage and the legend. --> */ ?>
        <div class="pop-body">
<?php /*             <!-- Stage that positions the chart and the tooltip. --> */ ?>
            <div class="pop-stage" data-pop-stage>
<?php /*                 <!-- Empty container where the script draws the chart. --> */ ?>
                <div class="pop-chart" data-pop-chart role="group" aria-label="Student population chart"></div>
<?php /*                 <!-- Tooltip box shown when hovering a slice or bar. --> */ ?>
                <div class="pop-tooltip" data-pop-tooltip hidden></div>
<?php /*                 <!-- Message shown when JavaScript is disabled in the browser. --> */ ?>
                <noscript><p class="pop-noscript">Enable JavaScript to view the chart. The figures are listed in the legend.</p></noscript>
<?php /*             <!-- Closes the chart stage. --> */ ?>
            </div>

<?php /*             <!-- Legend listing every program with its colour, count and percentage. --> */ ?>
            <aside class="pop-legend" aria-label="Academic programs legend">
<?php /*                 <!-- Legend heading. --> */ ?>
                <h4>Academic Programs</h4>
<?php /*                 <!-- List of program legend rows. --> */ ?>
                <ul class="pop-legend-list">
<?php /*                     <?php /* Loops over every program slice to print a legend row. * / ?> */ ?>
                    <?php foreach ($programs as $i => $p): ?>
<?php /*                     <!-- One legend row, tagged with its index for hover highlighting. --> */ ?>
                    <li class="pop-legend-item" data-index="<?= (int) $i ?>">
<?php /*                         <!-- Colour swatch using the program's slice colour. --> */ ?>
                        <span class="pop-swatch" style="background: <?= $e($p['color']) ?>"></span>
<?php /*                         <!-- Program name shown in the legend. --> */ ?>
                        <span class="pop-legend-name"><?= $e($p['label']) ?></span>
<?php /*                         <!-- Count and percentage figures for this program. --> */ ?>
                        <span class="pop-legend-figures">
<?php /*                             <!-- Number of students, with thousands separators. --> */ ?>
                            <strong><?= number_format($p['count']) ?></strong>
<?php /*                             <!-- Share of all students, escaped for safety. --> */ ?>
                            <small><?= $e($p['percent']) ?>%</small>
<?php /*                         <!-- Closes the figures wrapper. --> */ ?>
                        </span>
<?php /*                     <!-- Closes the legend row. --> */ ?>
                    </li>
<?php /*                     <?php /* Ends the program legend loop. * / ?> */ ?>
                    <?php endforeach; ?>
<?php /*                 <!-- Closes the legend list. --> */ ?>
                </ul>
<?php /*                 <!-- Legend footer showing the total number of students. --> */ ?>
                <div class="pop-legend-total">
<?php /*                     <!-- Label for the legend total. --> */ ?>
                    <span>Total Students</span>
<?php /*                     <!-- The total student count. --> */ ?>
                    <strong><?= number_format($total) ?></strong>
<?php /*                 <!-- Closes the legend total box. --> */ ?>
                </div>
<?php /*             <!-- Closes the legend aside. --> */ ?>
            </aside>
<?php /*         <!-- Closes the chart body area. --> */ ?>
        </div>

<?php /*         <!-- Footnote explaining that archived students are not counted. --> */ ?>
        <p class="pop-footnote">Figures reflect active (non-archived) student records.</p>
<?php /*         <!-- JSON data block that the chart script reads. --> */ ?>
        <script type="application/json" data-pop-data><?= $json ?></script>
<?php /*     <?php /* Ends the if/else, so either the chart or the empty state was printed. * / ?> */ ?>
    <?php endif; ?>
<?php /*     <!-- Closes the panel. --> */ ?>
    </div>
<?php /* <!-- Closes the chart section. --> */ ?>
</section>
<?php /*         <?php /* Reopens PHP after the HTML markup. * / ?> */ ?>
        <?php
    // // Closes renderProgramPopulationChart.
    }
// // Closes the function_exists guard opened on line 13.
}