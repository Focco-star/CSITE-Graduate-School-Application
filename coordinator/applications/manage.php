<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

$pageTitle   = 'Applications';
$role        = 'coordinator';
$currentPage = 'applications';
$userName    = !empty($_SESSION['user']['full_name']) ? $_SESSION['user']['full_name'] : ($mockCoordinator['name'] ?? 'Coordinator');

if (!isset($pdo) || !($pdo instanceof PDO)) {
    try {
        $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
        $dbName = defined('DB_NAME') ? DB_NAME : 'csite_grad_school';
        $dbUser = defined('DB_USER') ? DB_USER : 'root';
        $dbPass = defined('DB_PASS') ? DB_PASS : '';
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        $pdo = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_id'])) {
    $archiveId = (int) $_POST['archive_id'];
    if ($pdo && $archiveId > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE applications SET archived_at = NOW() WHERE application_id = :id AND archived_at IS NULL");
            $stmt->execute(['id' => $archiveId]);
            if ($stmt->rowCount() > 0) {
                setFlash('success', 'Application archived successfully. The record is retained for reporting.');
            } else {
                setFlash('error', 'The application could not be archived. It may already be archived.');
            }
        } catch (PDOException $e) {
            setFlash('error', 'The application could not be archived. Apply the database migration and try again.');
        }
    } elseif (function_exists('archiveApplicationRecord')) {
        archiveApplicationRecord($archiveId);
        setFlash('success', 'Application archived successfully.');
    }
    redirectTo('coordinator/applications/manage.php');
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_id'])) {
    $restoreId = (int) $_POST['restore_id'];
    if ($pdo && $restoreId > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE applications SET archived_at = NULL WHERE application_id = :id AND archived_at IS NOT NULL");
            $stmt->execute(['id' => $restoreId]);
            setFlash('success', 'Application restored to the active application list.');
        } catch (PDOException $e) {
            setFlash('error', 'The application could not be restored. Try again.');
        }
    }
    redirectTo('coordinator/applications/manage.php');
}



if (($_GET['delete'] ?? '') !== '') {
    setFlash('error', 'Applications can no longer be deleted from the website. Use Archive instead.');
    redirectTo('coordinator/applications/manage.php');
}

/* ------------------------------------------------------------------
 * Every registered student, with the applications they have submitted.
 *
 * A student who has registered but not applied yet is part of the list as
 * well: they carry the automatic "Not Yet Submitted" indicator, and their
 * application details read N/A because there is no application to show.
 * ------------------------------------------------------------------ */
$students = coordinatorStudentsWithApplications();

$totalStudents      = count($students);
$withApplication    = 0;
$withoutApplication = 0;
$applicationCount   = 0;
foreach ($students as $student) {
    if ($student['applications']) {
        $withApplication++;
        $applicationCount += count($student['applications']);
    } else {
        $withoutApplication++;
    }
}

$archivedAppCount = 0;
if ($pdo) {
    try {
        $archivedAppCount = (int) $pdo->query('SELECT COUNT(*) FROM applications WHERE archived_at IS NOT NULL')->fetchColumn();
    } catch (PDOException $e) {
        $archivedAppCount = 0;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Application Management</h2>
    <p>Every registered student and the applications they have submitted.</p>
</div>
<?php if ($archivedAppCount > 0): ?>
<div class="alert alert-info" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
    <div><i class="fas fa-box-archive"></i> <strong><?= $archivedAppCount ?></strong> archived application<?= $archivedAppCount === 1 ? '' : 's' ?> are retained for reporting.</div>
    <a href="<?= url('coordinator/applications/archived.php') ?>" class="btn btn-sm btn-outline"><i class="fas fa-box-archive"></i> View Archived Applications</a>
</div>
<?php endif; ?>

<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-value"><?= (int) $totalStudents ?></div>
        <div class="stat-label">Registered Students</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-file-alt"></i></div>
        <div class="stat-value"><?= (int) $withApplication ?></div>
        <div class="stat-label">Submitted an Application</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon gold"><i class="fas fa-clock"></i></div>
        <div class="stat-value"><?= (int) $withoutApplication ?></div>
        <div class="stat-label">Not Yet Submitted</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-layer-group"></i></div>
        <div class="stat-value"><?= (int) $applicationCount ?></div>
        <div class="stat-label">Applications on Record</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Students &amp; Applications <span class="filter-count" data-filter-count></span></h3>
    </div>
    <div class="card-body">
        <div class="filter-bar" data-apps-filter="#appsTable">
            <div class="filter-search">
                <i class="fas fa-search"></i>
                <input type="search" data-filter-q placeholder="Search by student name, title, adviser...">
            </div>
            <button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button>
            <select data-filter="submission">
                <option value="">All Submission Statuses</option>
                <option value="not_submitted">Not Yet Submitted</option>
                <option value="submitted">Submitted</option>
            </select>
            <select data-filter="appstatus">
                <option value="">All Application Statuses</option>
                <?php foreach (COORDINATOR_STATUSES as $key): ?>
                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars(STATUSES[$key]['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <select data-filter="program">
                <option value="">All Programs</option>
                <?php foreach (array_keys(PROGRAMS) as $code): ?>
                <option><?= htmlspecialchars($code) ?></option>
                <?php endforeach; ?>
            </select>
            <select data-filter="track">
                <option value="">All Tracks</option>
                <option>Thesis</option>
                <option>Capstone</option>
                <option>Seminar Paper</option>
            </select>
        </div>
        <div class="table-responsive">
            <table class="data-table" id="appsTable">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Submission Status</th>
                        <th>Application Status</th>
                        <th>Program</th>
                        <th>Track</th>
                        <th>Application Title</th>
                        <th>Adviser</th>
                        <th>Current Stage</th>
                        <th>Submission Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student):
                        $applications = $student['applications'];
                        $primary = $applications[0] ?? null;
                        $children = array_slice($applications, 1);
                        $groupId = 'grp-' . (int) $student['userId'];
                        $studentUrl = url('coordinator/applications/student.php?id=' . (int) $student['userId']);

                        // Each row carries the program and the track of the
                        // application it shows, so the program filter works on
                        // the application and not only on the student.
                        $rowProgram = $primary['program'] ?? $student['program'];
                        $rowTrack   = $primary['track'] ?? $student['trackLabel'];

                        // The searchable text of a row is its own: the student
                        // plus the application the row shows. A search that
                        // matches an older application therefore matches that
                        // row and not the newest one.
                        $parentSearch = strtolower(implode(' ', array_filter([
                            $student['name'], $student['email'], $student['program'],
                            $student['trackLabel'], $student['adviser'],
                            $primary['stage'] ?? '', $primary['title'] ?? '', $primary['status'] ?? '',
                        ])));
                    ?>
                    <?php if (!$primary): ?>
                    <!-- Registered, but no application yet. -->
                    <tr data-submission="not_submitted"
                        data-appstatus=""
                        data-program="<?= htmlspecialchars($student['program']) ?>"
                        data-track="<?= htmlspecialchars($student['trackLabel']) ?>"
                        data-group="<?= htmlspecialchars($groupId) ?>"
                        data-search="<?= htmlspecialchars($parentSearch) ?>">
                        <td>
                            <div class="student-cell">
                                <a href="<?= $studentUrl ?>" class="student-name-link"><?= htmlspecialchars($student['name']) ?></a>
                            </div>
                        </td>
                        <td><?= submissionBadge(false) ?></td>
                        <td><span class="text-muted-soft">N/A</span></td>
                        <td><?= htmlspecialchars($student['program']) ?></td>
                        <td><span class="track-chip"><?= htmlspecialchars($student['trackLabel']) ?></span></td>
                        <td><span class="text-muted-soft">N/A</span></td>
                        <td><?= htmlspecialchars($student['adviser'] !== '' ? $student['adviser'] : 'N/A') ?></td>
                        <td><span class="text-muted-soft">N/A</span></td>
                        <td><span class="text-muted-soft">N/A</span></td>
                        <td class="actions">
                            <a href="<?= $studentUrl ?>" class="btn btn-sm btn-outline"><i class="fas fa-user"></i> View Student</a>
                        </td>
                    </tr>
                    <?php else: ?>
                    <tr class="app-group-row" data-submission="submitted"
                        data-appstatus="<?= htmlspecialchars($primary['status']) ?>"
                        data-program="<?= htmlspecialchars($rowProgram) ?>"
                        data-track="<?= htmlspecialchars($rowTrack) ?>"
                        data-group="<?= htmlspecialchars($groupId) ?>"
                        data-search="<?= htmlspecialchars($parentSearch) ?>">
                        <td>
                            <div class="student-cell">
                                <?php if ($children): ?>
                                <button type="button" class="student-cell-toggle" data-group-toggle="<?= htmlspecialchars($groupId) ?>" aria-expanded="false" title="Show the other applications of this student">
                                    <i class="fas fa-chevron-right toggle-caret" aria-hidden="true"></i>
                                    <span class="student-group-count"><?= count($applications) ?></span>
                                </button>
                                <?php endif; ?>
                                <a href="<?= $studentUrl ?>" class="student-name-link"><?= htmlspecialchars($student['name']) ?></a>
                            </div>
                        </td>
                        <td><?= submissionBadge(true) ?></td>
                        <td data-app-cell><span class="cell-value"><?= statusBadge($primary['status']) ?></span></td>
                        <td data-app-cell><span class="cell-value"><?= htmlspecialchars($rowProgram) ?></span></td>
                        <td data-app-cell><span class="cell-value"><span class="track-chip"><?= htmlspecialchars($rowTrack) ?></span></span></td>
                        <td class="app-title-cell" data-app-cell><span class="cell-value"><?= htmlspecialchars($primary['title']) ?></span></td>
                        <td><?= htmlspecialchars($student['adviser'] !== '' ? $student['adviser'] : '—') ?></td>
                        <td data-app-cell><span class="cell-value"><?= htmlspecialchars($primary['stage']) ?></span></td>
                        <td data-app-cell><span class="cell-value"><?= $primary['date'] ? date('M d, Y', strtotime($primary['date'])) : '—' ?></span></td>
                        <td class="actions">
                            <a href="<?= url('coordinator/applications/view.php?id=' . (int) $primary['id']) ?>" class="btn btn-sm btn-outline" title="View application"><i class="fas fa-eye"></i></a>
                            <a href="<?= url('coordinator/applications/process.php?id=' . (int) $primary['id']) ?>" class="btn btn-sm btn-primary" title="Process application"><i class="fas fa-cog"></i></a>
                            <button type="button" class="btn btn-sm btn-danger" data-archive-open data-application-id="<?= (int) $primary['id'] ?>" data-application-student="<?= htmlspecialchars($student['name']) ?>" data-application-title="<?= htmlspecialchars($primary['title']) ?>" title="Archive"><i class="fas fa-box-archive"></i> Archive</button>
                        </td>
                    </tr>
                    <?php foreach ($children as $child): ?>
                    <tr class="app-child-row" data-child-of="<?= htmlspecialchars($groupId) ?>" data-submission="submitted" data-appstatus="<?= htmlspecialchars($child['status']) ?>" data-program="<?= htmlspecialchars($child['program'] ?? $rowProgram) ?>" data-track="<?= htmlspecialchars($child['track'] ?? $rowTrack) ?>" data-search="<?= htmlspecialchars(strtolower($student['name'] . ' ' . $child['stage'] . ' ' . $child['title'] . ' ' . $child['status'])) ?>">
                        <td><span class="child-label"><i class="fas fa-level-down" aria-hidden="true"></i> Also submitted</span></td>
                        <td><?= submissionBadge(true) ?></td>
                        <td><?= statusBadge($child['status']) ?></td>
                        <td><?= htmlspecialchars($child['program'] ?? $rowProgram) ?></td>
                        <td><span class="track-chip"><?= htmlspecialchars($child['track'] ?? $rowTrack) ?></span></td>
                        <td class="child-title app-title-cell"><?= htmlspecialchars($child['title']) ?></td>
                        <td><?= htmlspecialchars($student['adviser'] !== '' ? $student['adviser'] : '—') ?></td>
                        <td><?= htmlspecialchars($child['stage']) ?></td>
                        <td><?= $child['date'] ? date('M d, Y', strtotime($child['date'])) : '—' ?></td>
                        <td class="actions">
                            <a href="<?= url('coordinator/applications/view.php?id=' . (int) $child['id']) ?>" class="btn btn-sm btn-outline" title="View application"><i class="fas fa-eye"></i></a>
                            <a href="<?= url('coordinator/applications/process.php?id=' . (int) $child['id']) ?>" class="btn btn-sm btn-primary" title="Process application"><i class="fas fa-cog"></i></a>
                            <button type="button" class="btn btn-sm btn-danger" data-archive-open data-application-id="<?= (int) $child['id'] ?>" data-application-student="<?= htmlspecialchars($student['name']) ?>" data-application-title="<?= htmlspecialchars($child['title']) ?>" title="Archive"><i class="fas fa-box-archive"></i> Archive</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    <?php endforeach; ?>
                    <tr data-filter-empty <?= $students ? 'hidden' : '' ?>>
                        <td colspan="10" style="text-align:center;color:var(--gray-400);padding:2rem;">No students match your search or filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="archiveApplicationModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="archiveApplicationTitle">
        <div class="modal-header"><h3 id="archiveApplicationTitle">Archive application?</h3><button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button></div>
        <div class="modal-body"><p>Archive the application for <strong id="archiveApplicationStudent"></strong>? The application will be removed from the active list, but its record and documents will be retained for reporting.</p><p style="margin-top:0.5rem;font-size:0.85rem;color:var(--gray-500);"><em id="archiveApplicationTitleText"></em></p></div>
        <form method="post" action="<?= url('coordinator/applications/manage.php') ?>" class="modal-footer">
            <input type="hidden" name="archive_id" id="archiveApplicationId">
            <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-danger"><i class="fas fa-box-archive"></i> Archive Application</button>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('[data-archive-open]').forEach(button => button.addEventListener('click', () => {
    document.getElementById('archiveApplicationId').value = button.dataset.applicationId;
    document.getElementById('archiveApplicationStudent').textContent = button.dataset.applicationStudent;
    document.getElementById('archiveApplicationTitleText').textContent = button.dataset.applicationTitle ? '\u201C' + button.dataset.applicationTitle + '\u201D' : '';
    document.getElementById('archiveApplicationModal').classList.add('active');
}));

// Group-aware filter for this table.
//
// The shared filter in assets/js/script.js hides rows one by one, which would
// leave a student visible without the applications that belong to them. Here
// the group is the unit: a group stays visible when any of its applications
// matches, and its child rows follow the group.
(function () {
    const bar = document.querySelector('[data-apps-filter]');
    const table = document.querySelector(bar?.dataset.appsFilter);
    if (!bar || !table) return;

    const search = bar.querySelector('[data-filter-q]');
    const clearBtn = bar.querySelector('[data-filter-clear]');
    const selects = [...bar.querySelectorAll('select[data-filter]')];
    const countEl = bar.querySelector('[data-filter-count]') || document.querySelector('[data-filter-count]');
    const empty = table.querySelector('[data-filter-empty]');
    const groups = [...table.querySelectorAll('tr[data-group]')];

    // True when a row passes the current search text and dropdown filters.
    function rowMatches(row, q) {
        let ok = !q || (row.dataset.search || '').includes(q);
        selects.forEach(sel => {
            const key = sel.dataset.filter;
            const val = sel.value;
            if (!val) return;
            if ((row.dataset[key] || '') !== val) ok = false;
        });
        return ok;
    }

    function apply() {
        const q = (search?.value || '').trim().toLowerCase();
        const filtering = !!(q || selects.some(sel => sel.value));
        let visibleGroups = 0;
        let visibleApps = 0;

        groups.forEach(row => {
            const groupId = row.dataset.group;
            const children = [...table.querySelectorAll('tr[data-child-of="' + groupId + '"]')];
            const toggle = row.querySelector('[data-group-toggle]');
            let expanded = toggle?.getAttribute('aria-expanded') === 'true';

            // A row matches when it passes the search text and the dropdown
            // filters. Every row carries the program and the track of the
            // application it shows, so the program filter works per
            // application and not only per student.
            const parentMatches = rowMatches(row, q);
            const childMatches = children.map(child => rowMatches(child, q));
            const childHit = childMatches.some(Boolean);
            const groupOk = parentMatches || childHit;

            row.hidden = !groupOk;
            row.style.display = groupOk ? '' : 'none';

            if (!groupOk) {
                children.forEach(child => {
                    child.classList.remove('is-shown');
                    child.hidden = true;
                    child.style.display = 'none';
                });
                return;
            }

            // When a filter or a search matches only an older application of
            // this student, the group opens so the matching application is
            // actually visible instead of sitting behind a closed arrow.
            // Once the coordinator has touched the arrow themselves, their
            // choice wins and the group stays as they left it.
            if (filtering && !parentMatches && childHit && toggle && toggle.dataset.manual !== '1') {
                toggle.setAttribute('aria-expanded', 'true');
                expanded = true;
            }

            // The newest application of the group does not pass the filter:
            // its details are hidden so the table never shows a record the
            // coordinator filtered out.
            row.classList.toggle('is-parent-mismatch', groupOk && !parentMatches && filtering);

            visibleGroups += 1;
            if (parentMatches) visibleApps += 1;
            children.forEach((child, i) => {
                if (childMatches[i]) visibleApps += 1;
            });

            // The arrow always decides whether the extra applications of the
            // student are shown, whether a filter is active or not.
            children.forEach((child, i) => {
                const show = childMatches[i] && expanded;
                child.classList.toggle('is-shown', show);
                child.hidden = !show;
                child.style.display = show ? '' : 'none';
            });
        });

        if (empty) {
            empty.hidden = visibleGroups > 0;
            empty.style.display = visibleGroups > 0 ? 'none' : '';
        }
        if (countEl) {
            countEl.textContent = '(' + visibleGroups + ' student' + (visibleGroups === 1 ? '' : 's') + ')';
        }
        if (clearBtn) {
            clearBtn.hidden = !filtering;
        }
    }

    // Clicking the caret opens or closes that student's earlier applications,
    // then the group is re-evaluated against the filters. The choice of the
    // coordinator is remembered, so a filter never overrides the arrow.
    document.querySelectorAll('[data-group-toggle]').forEach(button => {
        button.addEventListener('click', () => {
            const expanded = button.getAttribute('aria-expanded') === 'true';
            button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            button.setAttribute('title', expanded ? 'Show the other applications of this student' : 'Hide the other applications of this student');
            button.dataset.manual = '1';
            apply();
        });
    });

    search?.addEventListener('input', apply);
    search?.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            search.value = '';
            selects.forEach(sel => { sel.value = ''; });
            apply();
        }
    });
    clearBtn?.addEventListener('click', () => {
        if (search) search.value = '';
        selects.forEach(sel => { sel.value = ''; });
        apply();
    });
    selects.forEach(sel => sel.addEventListener('change', apply));
    apply();
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
