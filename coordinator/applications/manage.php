<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle   = 'Manage Applications';
$role        = 'coordinator';
$currentPage = 'applications';
$userName    = !empty($_SESSION['user']['full_name']) ? $_SESSION['user']['full_name'] : ($mockCoordinator['name'] ?? 'Coordinator');


if (!isset($pdo) || !($pdo instanceof PDO)) {
    try {
        $pdo = DB::getConnection();
    } catch (PDOException $e) {
        $pdo = null;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_id'])) {
    $archiveId = (int) $_POST['archive_id'];
    if ($pdo && $archiveId > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE applications SET archived_at = NOW() WHERE application_id = :id AND archived_at IS NULL");
            $stmt->execute([':id' => $archiveId]);
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
            $stmt->execute([':id' => $restoreId]);
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




$applications = [];

if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                a.application_id AS id,
                a.user_id AS user_id,
                COALESCE(
                    NULLIF(TRIM(CONCAT(s.last_name, ', ', s.first_name, IF(s.middle_initial IS NULL OR TRIM(REPLACE(s.middle_initial, '.', '')) = '', '', CONCAT(' ', TRIM(REPLACE(s.middle_initial, '.', '')))))), ''), 
                    u.full_name
                ) AS student,
                CASE 
                    WHEN s.program LIKE '%Computer Science%' THEN 'MSCS'
                    WHEN s.program LIKE '%Information Technology%' THEN 'MIT'
                    WHEN s.program LIKE '%Mathematics%' THEN 'MATH'
                    ELSE s.program 
                END AS program,
                s.track AS db_track,
                s.adviser_name AS adviser,
                a.presentation_stage AS stage,
                a.paper_title AS title,
                a.submitted_at AS date,
                a.status
            FROM applications a
            JOIN users u ON u.user_id = a.user_id
            LEFT JOIN students s ON s.user_id = u.user_id
            WHERE a.archived_at IS NULL
              AND s.archived_at IS NULL
            -- Alphabetical by last name, then first name ('Last, First MI' format).
            ORDER BY SUBSTRING_INDEX(u.full_name, ',', 1) ASC, SUBSTRING_INDEX(u.full_name, ',', -1) ASC, a.submitted_at DESC
        ");
        $applications = $stmt->fetchAll();
    } catch (PDOException $e) {
        $applications = [];
    }
}

/*
 * Group the applications by student so the list shows one row per student
 * instead of one row per submission. The newest application of each student
 * becomes the visible "parent" row; the student's other applications are kept
 * as child rows that are revealed when the student name is clicked.
 */
$studentGroups = [];
foreach ($applications as $app) {
    $groupKey = 'u' . (int) ($app['user_id'] ?? 0) . '|' . (string) $app['student'];
    $studentGroups[$groupKey][] = $app;
}
$groupedApplications = [];
foreach ($studentGroups as $groupKey => $groupRows) {
    // Rows arrive newest-first from the ORDER BY, so index 0 is the latest.
    $groupedApplications[] = [
        'key'      => $groupKey,
        'primary'  => $groupRows[0],
        'children' => array_slice($groupRows, 1),
        'total'    => count($groupRows),
    ];
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Application Management</h2>
    <p>View and process submitted student applications by track and program.</p>
</div>
<?php
    $archivedAppCount = 0;
    if ($pdo) {
        try {
            $archivedAppCount = (int) $pdo->query('SELECT COUNT(*) FROM applications WHERE archived_at IS NOT NULL')->fetchColumn();
        } catch (PDOException $e) {
            $archivedAppCount = 0;
        }
    }
?>
<?php if ($archivedAppCount > 0): ?>
<div class="alert alert-info" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
    <div><i class="fas fa-box-archive"></i> <strong><?= $archivedAppCount ?></strong> archived application<?= $archivedAppCount === 1 ? '' : 's' ?> are retained for reporting.</div>
    <a href="<?= url('coordinator/applications/archived.php') ?>" class="btn btn-sm btn-outline"><i class="fas fa-box-archive"></i> View Archived Applications</a>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Submitted Applications <span class="filter-count" data-filter-count></span></h3>
    </div>
    <div class="card-body">
        <div class="filter-bar" data-apps-filter="#appsTable">
            <div class="filter-search">
                <i class="fas fa-search"></i>
                <input type="search" data-filter-q placeholder="Search by name, title, program, stage, status...">
            </div>
            <button type="button" class="btn btn-sm btn-outline" data-filter-clear hidden>Clear</button>
            <select data-filter="track">
                <option value="">All Tracks</option>
                <option>Thesis</option>
                <option>Capstone</option>
                <option>Seminar Paper</option>
            </select>
            <select data-filter="status">
                <option value="">All Statuses</option>
                <?php foreach (STATUSES as $key => $info): ?>
                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($info['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <select data-filter="program">
                <option value="">All Programs</option>
                <?php foreach (array_keys(PROGRAMS) as $code): ?>
                <option><?= htmlspecialchars($code) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="table-responsive">
            <table class="data-table" id="appsTable">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Program</th>
                        <th>Track</th>
                        <th>Adviser</th>
                        <th>Stage</th>
                        <th>Title</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groupedApplications as $group):
                        $app = $group['primary'];
                        $rawTrack = $app['db_track'] ?? (function_exists('getTrackForProgram') ? getTrackForProgram($app['program']) : 'thesis');
                        $track = function_exists('getTrackLabel') ? getTrackLabel($rawTrack) : ucfirst($rawTrack);
                        $childCount = count($group['children']);
                        $groupId = 'grp-' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) $group['key']);
                        // Searchable text for the whole group (parent + children) so a
                        // search still finds a student whose match sits in a child row.
                        $groupSearch = [];
                        foreach (array_merge([$app], $group['children']) as $row) {
                            $groupSearch[] = $row['student'] . ' ' . $row['program'] . ' ' . $row['stage'] . ' ' . $row['title'] . ' ' . $row['status'] . ' ' . $track;
                        }
                        $searchText = strtolower(implode(' ', $groupSearch));
                    ?>
                    <tr data-status="<?= htmlspecialchars($app['status']) ?>"
                        data-program="<?= htmlspecialchars($app['program']) ?>"
                        data-track="<?= htmlspecialchars($track) ?>"
                        data-group="<?= htmlspecialchars($groupId) ?>"
                        data-search="<?= htmlspecialchars($searchText) ?>">
                        <td>
                            <?php if ($childCount > 0): ?>
                            <button type="button" class="student-cell-toggle" data-group-toggle="<?= htmlspecialchars($groupId) ?>" aria-expanded="false" title="Show the other applications of this student">
                                <i class="fas fa-chevron-right toggle-caret" aria-hidden="true"></i>
                                <strong><?= htmlspecialchars($app['student']) ?></strong>
                                <span class="student-group-count"><?= (int) $group['total'] ?></span>
                            </button>
                            <?php else: ?>
                            <strong><?= htmlspecialchars($app['student']) ?></strong>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($app['program']) ?></td>
                        <td><span style="font-size:0.75rem;background:var(--gray-100);padding:2px 8px;border-radius:99px;"><?= htmlspecialchars($track) ?></span></td>
                        <td><?= htmlspecialchars($app['adviser'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($app['stage']) ?></td>
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($app['title']) ?></td>
                        <td><?= date('M d, Y', strtotime($app['date'])) ?></td>
                        <td><?= statusBadge($app['status']) ?></td>
                        <td class="actions">
                            <a href="<?= url('coordinator/applications/view.php?id=' . $app['id']) ?>" class="btn btn-sm btn-outline" title="View"><i class="fas fa-eye"></i></a>
                            <a href="<?= url('coordinator/applications/process.php?id=' . $app['id']) ?>" class="btn btn-sm btn-primary" title="Process"><i class="fas fa-cog"></i></a>
                            <button type="button" class="btn btn-sm btn-danger" data-archive-open data-application-id="<?= (int) $app['id'] ?>" data-application-student="<?= htmlspecialchars($app['student']) ?>" data-application-title="<?= htmlspecialchars($app['title']) ?>" title="Archive"><i class="fas fa-box-archive"></i> Archive</button>
                        </td>
                    </tr>
                    <?php foreach ($group['children'] as $child): ?>
                    <tr class="app-child-row" data-child-of="<?= htmlspecialchars($groupId) ?>" data-status="<?= htmlspecialchars($child['status']) ?>" data-program="<?= htmlspecialchars($child['program']) ?>" data-track="<?= htmlspecialchars($track) ?>" data-search="<?= htmlspecialchars(strtolower($child['student'] . ' ' . $child['program'] . ' ' . $child['stage'] . ' ' . $child['title'] . ' ' . $child['status'] . ' ' . $track)) ?>">
                        <td><span class="child-label"><i class="fas fa-level-down" aria-hidden="true"></i> Also submitted</span></td>
                        <td><?= htmlspecialchars($child['program']) ?></td>
                        <td><span style="font-size:0.75rem;background:var(--gray-100);padding:2px 8px;border-radius:99px;"><?= htmlspecialchars($track) ?></span></td>
                        <td><?= htmlspecialchars($child['adviser'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($child['stage']) ?></td>
                        <td class="child-title" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($child['title']) ?></td>
                        <td><?= date('M d, Y', strtotime($child['date'])) ?></td>
                        <td><?= statusBadge($child['status']) ?></td>
                        <td class="actions">
                            <a href="<?= url('coordinator/applications/view.php?id=' . $child['id']) ?>" class="btn btn-sm btn-outline" title="View"><i class="fas fa-eye"></i></a>
                            <a href="<?= url('coordinator/applications/process.php?id=' . $child['id']) ?>" class="btn btn-sm btn-primary" title="Process"><i class="fas fa-cog"></i></a>
                            <button type="button" class="btn btn-sm btn-danger" data-archive-open data-application-id="<?= (int) $child['id'] ?>" data-application-student="<?= htmlspecialchars($child['student']) ?>" data-application-title="<?= htmlspecialchars($child['title']) ?>" title="Archive"><i class="fas fa-box-archive"></i> Archive</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                    <tr data-filter-empty <?= $groupedApplications ? 'hidden' : '' ?>>
                        <td colspan="9" style="text-align:center;color:var(--gray-400);padding:2rem;">No applications match your search or filters.</td>
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

// Expandable student groups + group-aware filter for this table.
//
// The shared filter in assets/js/script.js hides rows one by one, which would
// leave a student visible without the applications that belong to them. Here
// the group is the unit: a group stays visible when any of its applications
// matches, and its child rows follow the group (visible + expanded).
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
        let visibleGroups = 0;
        let visibleApps = 0;

        groups.forEach(row => {
            const groupId = row.dataset.group;
            const children = [...table.querySelectorAll('tr[data-child-of="' + groupId + '"]')];
            const toggle = row.querySelector('[data-group-toggle]');
            const expanded = toggle?.getAttribute('aria-expanded') === 'true';

            // A group matches when the parent row matches OR any of its
            // applications matches, so a search on a title that sits in a
            // child row still surfaces the student.
            let groupOk = rowMatches(row, q);
            const childMatches = children.map(child => rowMatches(child, q));
            if (!groupOk && childMatches.some(Boolean)) groupOk = true;

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

            visibleGroups += 1;
            // Count the applications that actually match the current filters.
            visibleApps += rowMatches(row, q) ? 1 : 0;
            children.forEach((child, i) => {
                if (childMatches[i]) visibleApps += 1;
            });

            // Child rows follow the group: shown only when the group is open
            // and the child itself passes the current filters.
            children.forEach((child, i) => {
                const show = expanded && childMatches[i];
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
            countEl.textContent = '(' + visibleApps + ' application' + (visibleApps === 1 ? '' : 's') + ')';
        }
        if (clearBtn) {
            clearBtn.hidden = !(q || selects.some(sel => sel.value));
        }
    }

    // Clicking a student name opens or closes that student's earlier
    // applications, then the group is re-evaluated against the filters.
    document.querySelectorAll('[data-group-toggle]').forEach(button => {
        button.addEventListener('click', () => {
            const expanded = button.getAttribute('aria-expanded') === 'true';
            button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
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
