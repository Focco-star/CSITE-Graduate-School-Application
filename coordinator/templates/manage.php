<?php
require_once __DIR__ . '/../../includes/config.php';

$pageTitle   = 'Manage Templates';
$role        = 'coordinator';
$currentPage = 'templates';
$userName    = $mockCoordinator['name'];

$templates = getPaperLibraryRows();
$stages    = array_values(array_unique(array_column($templates, 'stage')));

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2>Template &amp; Forms Library</h2>
    <p>All documents organized by course type, program, stage, and document type.</p>
</div>

<div class="form-actions" style="border:none;padding:0;margin-bottom:1rem;display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
    <a href="<?= url('coordinator/templates/add.php') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Add Document</a>
    <div style="position:relative;flex:1 1 220px;">
        <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:0.85rem;"></i>
        <input type="search" id="tplSearch" placeholder="Search by name, file, program, track, stage, type..."
            style="width:100%;padding:0.45rem 0.75rem 0.45rem 2.2rem;border:1px solid var(--gray-300);border-radius:var(--radius);font-size:0.85rem;">
    </div>
    <button type="button" class="btn btn-sm btn-outline" id="tplClear" hidden>Clear</button>
</div>

<div style="margin-bottom:1.5rem;display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
    <select id="tplCourse" style="padding:0.4rem 0.75rem;border:1px solid var(--gray-300);border-radius:var(--radius);font-size:0.85rem;">
        <option value="">All</option>
        <option>Thesis</option>
        <option>Capstone</option>
        <option>Seminar Paper</option>
    </select>
    <select id="tplProgram" style="padding:0.4rem 0.75rem;border:1px solid var(--gray-300);border-radius:var(--radius);font-size:0.85rem;">
        <option value="">All</option>
        <?php foreach (array_keys(PROGRAMS) as $code): ?>
        <option><?= htmlspecialchars($code) ?></option>
        <?php endforeach; ?>
    </select>
    <select id="tplStage" style="padding:0.4rem 0.75rem;border:1px solid var(--gray-300);border-radius:var(--radius);font-size:0.85rem;">
        <option value="">All</option>
        <?php foreach ($stages as $stage): ?>
        <option><?= htmlspecialchars($stage) ?></option>
        <?php endforeach; ?>
    </select>
    <select id="tplType" style="padding:0.4rem 0.75rem;border:1px solid var(--gray-300);border-radius:var(--radius);font-size:0.85rem;">
        <option value="">All</option>
        <option>Template</option>
        <option>Form</option>
        <option>Reference</option>
    </select>
    <span id="tplCount" style="font-size:0.82rem;color:var(--gray-500);margin-left:auto;"></span>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="data-table" id="tplTable">
                <thead>
                    <tr>
                        <th>Document / File Name</th>
                        <th>Course Type</th>
                        <th>Program</th>
                        <th>Stage</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templates as $i => $tpl): ?>
                    <tr data-course="<?= htmlspecialchars($tpl['courseType']) ?>"
                        data-program="<?= htmlspecialchars($tpl['programs']) ?>"
                        data-stage="<?= htmlspecialchars($tpl['stage']) ?>"
                        data-type="<?= htmlspecialchars($tpl['docType']) ?>"
                        data-search="<?= htmlspecialchars(strtolower($tpl['label'] . ' ' . $tpl['file'] . ' ' . $tpl['programs'] . ' ' . $tpl['courseType'] . ' ' . $tpl['stage'] . ' ' . $tpl['docType'])) ?>">
                        <td>
                            <div style="display:flex;align-items:center;gap:0.5rem;">
                                <i class="fas fa-file-word" style="color:#2b579a;"></i>
                                <div>
                                    <strong style="display:block;font-size:0.85rem;"><?= htmlspecialchars($tpl['label']) ?></strong>
                                    <span style="font-size:0.73rem;color:var(--gray-400);"><?= htmlspecialchars($tpl['file']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($tpl['courseType']) ?></td>
                        <td><?= htmlspecialchars($tpl['programs']) ?></td>
                        <td><?= htmlspecialchars($tpl['stage']) ?></td>
                        <td><?= htmlspecialchars($tpl['docType']) ?></td>
                        <td class="actions">
                            <a href="<?= htmlspecialchars($tpl['url']) ?>" class="btn btn-sm btn-primary" download title="Download"><i class="fas fa-download"></i></a>
                            <a href="<?= url('coordinator/templates/edit.php?id=' . ($i + 1)) ?>" class="btn btn-sm btn-outline" title="Edit"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr id="tplEmpty" hidden>
                        <td colspan="6" style="text-align:center;color:var(--gray-400);padding:2rem;">No documents match the selected filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    const rows = [...document.querySelectorAll('#tplTable tbody tr[data-search]')];
    const empty = document.getElementById('tplEmpty');
    const count = document.getElementById('tplCount');
    const search = document.getElementById('tplSearch');
    const clearBtn = document.getElementById('tplClear');
    const filters = ['tplCourse', 'tplProgram', 'tplStage', 'tplType'].map(id => document.getElementById(id));

    function apply() {
        const q = search.value.trim().toLowerCase();
        const [course, program, stage, type] = filters.map(el => el.value);
        let visible = 0;
        rows.forEach(row => {
            const ok = (!course || row.dataset.course === course)
                && (!program || row.dataset.program.includes(program))
                && (!stage || row.dataset.stage === stage)
                && (!type || row.dataset.type === type)
                && (!q || row.dataset.search.includes(q));
            row.hidden = !ok;
            if (ok) visible += 1;
        });
        empty.hidden = visible > 0;
        count.textContent = visible + ' document' + (visible === 1 ? '' : 's');
        clearBtn.hidden = !q;
    }

    search.addEventListener('input', apply);
    search.addEventListener('keydown', e => { if (e.key === 'Escape') { search.value = ''; apply(); } });
    clearBtn.addEventListener('click', () => { search.value = ''; apply(); });
    filters.forEach(el => el.addEventListener('change', apply));
    apply();
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
