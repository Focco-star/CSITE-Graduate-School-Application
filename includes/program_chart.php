<?php
/**
 * Shared "Student Population by Academic Program" component.
 *
 * Used by BOTH coordinator/dashboard.php and coordinator/reports/dashboard.php
 * so the two pages always show identical data, colors and chart behavior.
 *
 * Usage:  renderProgramPopulationChart($pdo ?? null);
 */

if (!function_exists('programPopulationPalette')) {

    /** Brand-grounded, high-contrast palette. Colors beyond 12 programs are generated. */
    function programPopulationPalette(): array
    {
        return [
            '#060297', // Ateneo navy
            '#FFB82B', // Ateneo gold
            '#f43f5e', // rose
            '#10b981', // emerald
            '#8b5cf6', // purple
            '#4285F4', // blue
            '#ea580c', // deep orange
            '#0f766e', // dark teal
            '#0891b2', // cyan
            '#64748b', // slate
            '#84cc16', // lime
            '#7c2d12', // brown
        ];
    }

    function programPopulationColor(int $index): string
    {
        $palette = programPopulationPalette();
        if ($index < count($palette)) {
            return $palette[$index];
        }
        $extra = $index - count($palette);
        $hue   = (int) round(fmod($extra * 137.508 + 18, 360));
        $light = 38 + ($extra % 3) * 8;
        return "hsl({$hue}, 62%, {$light}%)";
    }

    /** Readable text color (for labels drawn on top of a slice). */
    function programPopulationInk(string $color): string
    {
        if (preg_match('/^#([0-9a-f]{6})$/i', $color, $m)) {
            $r = hexdec(substr($m[1], 0, 2));
            $g = hexdec(substr($m[1], 2, 2));
            $b = hexdec(substr($m[1], 4, 2));
            $lum = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
            return $lum > 0.62 ? '#1a222c' : '#ffffff';
        }
        return '#ffffff';
    }

    /**
     * Optional display names. If students.program stores short codes, map them here,
     * e.g. ['CODE' => 'Full program name']. Left empty on purpose: nothing is invented.
     */
    function programPopulationLabels(): array
    {
        return [];
    }

    function programPopulationConnection(?PDO $pdo): ?PDO
    {
        if ($pdo instanceof PDO) {
            return $pdo;
        }
        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            return $GLOBALS['pdo'];
        }
        try {
            $host = defined('DB_HOST') ? DB_HOST : 'localhost';
            $name = defined('DB_NAME') ? DB_NAME : 'csite_grad_school';
            $user = defined('DB_USER') ? DB_USER : 'root';
            $pass = defined('DB_PASS') ? DB_PASS : '';
            return new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (Throwable $e) {
            return null;
        }
    }

    /** @return array<string,int> program name => number of active students */
    function programPopulationCounts(?PDO $pdo): array
    {
        $pdo = programPopulationConnection($pdo);

        if ($pdo) {
            try {
                $stmt = $pdo->query(
                    "SELECT COALESCE(NULLIF(TRIM(program), ''), 'Unspecified Program') AS program_name,
                            COUNT(*) AS total
                     FROM students
                     WHERE archived_at IS NULL
                     GROUP BY program_name"
                );
                $counts = [];
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $counts[(string) $row['program_name']] = (int) $row['total'];
                }
                return $counts;
            } catch (Throwable $e) {
                // fall through to the session store
            }
        }

        if (function_exists('storeGet')) {
            try {
                $counts = [];
                foreach ((array) (storeGet('students') ?? []) as $s) {
                    if (!is_array($s) || !empty($s['archivedAt'] ?? '')) {
                        continue;
                    }
                    $name = trim((string) ($s['program'] ?? ''));
                    if ($name === '') {
                        $name = 'Unspecified Program';
                    }
                    $counts[$name] = ($counts[$name] ?? 0) + 1;
                }
                return $counts;
            } catch (Throwable $e) {
                return [];
            }
        }

        return [];
    }

    /**
     * Colors are assigned alphabetically by program, so a program keeps the same
     * color in every chart type and on both dashboards. Output is sorted by size.
     */
    function programPopulationData(?PDO $pdo = null): array
    {
        $counts = programPopulationCounts($pdo);
        $labels = programPopulationLabels();

        $names = array_map('strval', array_keys($counts));
        natcasesort($names);
        $names = array_values($names);

        $colors = [];
        foreach ($names as $i => $name) {
            $colors[$name] = programPopulationColor($i);
        }

        $total = 0;
        foreach ($counts as $c) {
            if ($c > 0) {
                $total += (int) $c;
            }
        }

        $programs = [];
        foreach ($counts as $name => $count) {
            $name  = (string) $name;
            $count = (int) $count;
            if ($count <= 0) {
                continue;
            }
            $color      = $colors[$name];
            $programs[] = [
                'key'     => $name,
                'label'   => (string) ($labels[$name] ?? $name),
                'count'   => $count,
                'percent' => number_format($count / $total * 100, 1, '.', ''),
                'color'   => $color,
                'ink'     => programPopulationInk($color),
            ];
        }

        usort($programs, static function ($a, $b) {
            if ($a['count'] !== $b['count']) {
                return $b['count'] <=> $a['count'];
            }
            return strcasecmp($a['label'], $b['label']);
        });

        return ['programs' => $programs, 'total' => $total];
    }

    function renderProgramPopulationChart(?PDO $pdo = null): void
    {
        static $instance = 0;
        $instance++;
        $uid = 'popChart' . $instance;

        try {
            $data = programPopulationData($pdo);
        } catch (Throwable $e) {
            $data = ['programs' => [], 'total' => 0];
        }

        $programs = $data['programs'];
        $total    = (int) $data['total'];
        $e        = static function ($v) {
            return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        };

        $types = [
            'bar'   => ['Bar Chart',            'fa-chart-bar'],
            'hbar'  => ['Horizontal Bar Chart', 'fa-align-left'],
            'pie'   => ['Pie Chart',            'fa-chart-pie'],
            'donut' => ['Donut Chart',          'fa-circle-notch'],
        ];

        $json = json_encode(
            ['programs' => $programs, 'total' => $total],
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
        );
        if ($json === false) {
            $json = '{"programs":[],"total":0}';
        }
        ?>
<section class="pop-dashboard" id="<?= $e($uid) ?>" data-program-chart aria-labelledby="<?= $e($uid) ?>Title">
    <header class="pop-banner">
        <div class="pop-banner-mark" aria-hidden="true"><i class="fas fa-university"></i></div>
        <div class="pop-banner-text">
            <span class="pop-eyebrow">Student Population Dashboard</span>
            <h2 class="pop-title" id="<?= $e($uid) ?>Title">Ateneo de Zamboanga University</h2>
            <p class="pop-subtitle">Student population by academic program</p>
        </div>
        <div class="pop-banner-total">
            <strong><?= number_format($total) ?></strong>
            <span>Total Students</span>
        </div>
    </header>

    <div class="pop-panel">
    <?php if (!$programs || $total <= 0): ?>
        <div class="empty-state">
            <i class="fas fa-user-graduate"></i>
            <h4>No student records yet</h4>
            <p>The chart will appear once students are enrolled.</p>
        </div>
    <?php else: ?>
        <div class="pop-panel-head">
            <div class="pop-panel-heading">
                <h3 data-pop-chart-title>Total Students per Program</h3>
                <p data-pop-chart-desc></p>
            </div>

            <div class="pop-select" data-pop-select>
                <button type="button" class="pop-select-btn" data-pop-toggle
                        aria-haspopup="menu" aria-expanded="false" aria-controls="<?= $e($uid) ?>Menu">
                    <i class="fas fa-chart-bar" data-pop-current-icon aria-hidden="true"></i>
                    <span data-pop-current-label>Bar Chart</span>
                    <i class="fas fa-chevron-down pop-select-caret" aria-hidden="true"></i>
                </button>
                <ul class="pop-menu" id="<?= $e($uid) ?>Menu" role="menu" aria-label="Chart type" data-pop-menu hidden>
                    <?php foreach ($types as $key => $t): ?>
                    <li role="none">
                        <button type="button" role="menuitemradio" aria-checked="false" class="pop-menu-item"
                                data-chart-type="<?= $e($key) ?>" data-label="<?= $e($t[0]) ?>" data-icon="<?= $e($t[1]) ?>">
                            <i class="fas <?= $e($t[1]) ?>" aria-hidden="true"></i>
                            <span><?= $e($t[0]) ?></span>
                            <i class="fas fa-check pop-check" aria-hidden="true"></i>
                        </button>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="pop-body">
            <div class="pop-stage" data-pop-stage>
                <div class="pop-chart" data-pop-chart role="group" aria-label="Student population chart"></div>
                <div class="pop-tooltip" data-pop-tooltip hidden></div>
                <noscript><p class="pop-noscript">Enable JavaScript to view the chart. The figures are listed in the legend.</p></noscript>
            </div>

            <aside class="pop-legend" aria-label="Academic programs legend">
                <h4>Academic Programs</h4>
                <ul class="pop-legend-list">
                    <?php foreach ($programs as $i => $p): ?>
                    <li class="pop-legend-item" data-index="<?= (int) $i ?>">
                        <span class="pop-swatch" style="background: <?= $e($p['color']) ?>"></span>
                        <span class="pop-legend-name"><?= $e($p['label']) ?></span>
                        <span class="pop-legend-figures">
                            <strong><?= number_format($p['count']) ?></strong>
                            <small><?= $e($p['percent']) ?>%</small>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="pop-legend-total">
                    <span>Total Students</span>
                    <strong><?= number_format($total) ?></strong>
                </div>
            </aside>
        </div>

        <p class="pop-footnote">Figures reflect active (non-archived) student records.</p>
        <script type="application/json" data-pop-data><?= $json ?></script>
    <?php endif; ?>
    </div>
</section>
        <?php
    }
}