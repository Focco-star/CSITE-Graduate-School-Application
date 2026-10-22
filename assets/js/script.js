(function () {
    'use strict';

    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('sidebarToggle');

    function openSidebar() {
        sidebar?.classList.add('open');
        overlay?.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar?.classList.remove('open');
        overlay?.classList.remove('active');
        document.body.style.overflow = '';
    }

    toggleBtn?.addEventListener('click', () => {
        sidebar?.classList.contains('open') ? closeSidebar() : openSidebar();
    });

    overlay?.addEventListener('click', closeSidebar);

    document.querySelectorAll('.tab-list').forEach(tabList => {
        const buttons = tabList.querySelectorAll('.tab-btn');
        const container = tabList.closest('.tabs') || tabList.parentElement;
        const panels = container.querySelectorAll('.tab-panel');

        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.dataset.tab;
                buttons.forEach(b => b.classList.remove('active'));
                panels.forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                container.querySelector(`#${target}`)?.classList.add('active');
            });
        });
    });

    document.querySelectorAll('[data-modal-open]').forEach(trigger => {
        trigger.addEventListener('click', e => {
            e.preventDefault();
            const id = trigger.dataset.modalOpen;
            document.getElementById(id)?.classList.add('active');
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.modal-overlay')?.classList.remove('active');
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) overlay.classList.remove('active');
        });
    });

    const confirmModal = document.getElementById('globalConfirmModal');
    const confirmMessage = document.getElementById('globalConfirmMessage');
    const confirmAccept = document.getElementById('globalConfirmAccept');
    let confirmAction = null;
    function openConfirm(message, action) {
        confirmMessage.textContent = message || 'Are you sure you want to continue?';
        confirmAction = action;
        confirmModal?.classList.add('active');
    }
    document.querySelectorAll('[data-confirm-url]').forEach(button => {
        button.addEventListener('click', () => openConfirm(button.dataset.confirmMessage, () => { window.location.href = button.dataset.confirmUrl; }));
    });
    document.querySelectorAll('[data-confirm-form] [data-confirm-trigger]').forEach(button => {
        button.addEventListener('click', () => {
            const form = button.closest('form');
            openConfirm(form?.dataset.confirmMessage, () => form?.submit());
        });
    });
    confirmAccept?.addEventListener('click', () => {
        const action = confirmAction;
        confirmAction = null;
        confirmModal?.classList.remove('active');
        action?.();
    });

    document.querySelectorAll('.file-upload-area').forEach(area => {
        const input = area.querySelector('input[type="file"]');
        const nameDisplay = area.querySelector('.file-name');

        area.addEventListener('click', () => input?.click());

        area.addEventListener('dragover', e => {
            e.preventDefault();
            area.classList.add('dragover');
        });

        area.addEventListener('dragleave', () => area.classList.remove('dragover'));

        area.addEventListener('drop', e => {
            e.preventDefault();
            area.classList.remove('dragover');
            if (input && e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                showFileName(input, nameDisplay);
            }
        });

        input?.addEventListener('change', () => {
            if (input.files[0]) {
                const file = input.files[0];
                const ext = (file.name.split('.').pop() || '').toLowerCase();
                if (!['pdf', 'doc', 'docx'].includes(ext)) {
                    input.value = '';
                    if (nameDisplay) nameDisplay.textContent = 'Only PDF, DOC, or DOCX files are allowed.';
                    return;
                }
                if (file.size > 20 * 1024 * 1024) {
                    input.value = '';
                    if (nameDisplay) nameDisplay.textContent = 'File exceeds the 20 MB maximum size.';
                    return;
                }
            }
            showFileName(input, nameDisplay);
        });
    });

    function showFileName(input, display) {
        if (display && input.files.length) {
            display.textContent = input.files[0].name;
        }
    }

    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', e => {
            let valid = true;
            form.querySelectorAll('[required]').forEach(field => {
                const group = field.closest('.form-field') || field.parentElement;
                group?.classList.remove('has-error');
                group?.querySelector('.field-error')?.remove();

                if (!field.value.trim()) {
                    valid = false;
                    group?.classList.add('has-error');
                    const err = document.createElement('div');
                    err.className = 'field-error';
                    err.textContent = 'This field is required.';
                    group?.appendChild(err);
                }
            });

            form.querySelectorAll('[type="email"]').forEach(field => {
                if (field.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
                    valid = false;
                    const group = field.closest('.form-field');
                    group?.classList.add('has-error');
                }
            });

            if (!valid) e.preventDefault();
        });
    });

    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.3s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    document.querySelectorAll('[data-print]').forEach(btn => {
        btn.addEventListener('click', () => window.print());
    });

    document.querySelectorAll('[data-toggle-password]').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.togglePassword);
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.querySelector('i')?.classList.toggle('fa-eye', !show);
            btn.querySelector('i')?.classList.toggle('fa-eye-slash', show);
        });
    });

    document.querySelectorAll('[data-submenu-toggle]').forEach(parent => {
        const toggleSubmenu = () => {
            const submenu = parent.nextElementSibling;
            if (!submenu) return;
            const isOpen = submenu.classList.toggle('open');
            parent.querySelector('.nav-chevron')?.classList.toggle('open', isOpen);
            // MyADZU theme state classes (root + nested levels)
            if (parent.classList.contains('myadzu-root') || parent.classList.contains('myadzu-sub-toggle')) {
                parent.classList.toggle('is-open', isOpen);
            } else {
                parent.classList.toggle('active', isOpen);
            }
            if (parent.hasAttribute('aria-expanded')) {
                parent.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            }
        };
        parent.addEventListener('click', toggleSubmenu);
        // Keyboard accessibility: Enter / Space toggles div[role="button"]
        parent.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleSubmenu();
            }
        });
    });

    const slides = document.querySelectorAll('.slideshow-bg .slide');
    if (slides.length > 1) {
        let current = 0;
        setInterval(() => {
            slides[current].classList.remove('active');
            current = (current + 1) % slides.length;
            slides[current].classList.add('active');
        }, 20000);
    }

    const programToggle = document.getElementById('programToggle');
    const programList = document.getElementById('programList');
    const programValue = document.getElementById('programValue');
    const programLabel = document.getElementById('programLabel');
    const trackHint = document.getElementById('trackHint');
    const hints = window.CSITE_TRACK_HINTS || {};

    programToggle?.addEventListener('click', () => {
        programList?.classList.toggle('open');
    });

    programList?.querySelectorAll('.program-option').forEach(opt => {
        opt.addEventListener('click', () => {
            programList.querySelectorAll('.program-option').forEach(o => o.classList.remove('selected'));
            opt.classList.add('selected');
            programValue.value = opt.dataset.code;
            programLabel.textContent = opt.textContent.trim();
            programToggle.classList.add('has-value');
            programList.classList.remove('open');
            const track = opt.dataset.track;
            if (trackHint && hints[track]) {
                trackHint.style.display = 'flex';
                trackHint.querySelector('span').textContent = hints[track];
            }
        });
    });

    document.querySelectorAll('[data-filter-table]').forEach(bar => {
        const root = document.querySelector(bar.dataset.filterTable);
        if (!root) return;
        const search = bar.querySelector('[data-filter-q]');
        const clearBtn = bar.querySelector('[data-filter-clear]');
        const selects = [...bar.querySelectorAll('select[data-filter]')];
        const countEl = bar.querySelector('[data-filter-count]') || document.querySelector('[data-filter-count]');
        const rows = [...root.querySelectorAll('[data-search]')];
        const empty = root.querySelector('[data-filter-empty]');

        function apply() {
            const q = (search?.value || '').trim().toLowerCase();
            let visible = 0;
            rows.forEach(row => {
                let ok = !q || (row.dataset.search || '').includes(q);
                selects.forEach(sel => {
                    const key = sel.dataset.filter;
                    const val = sel.value;
                    if (val && (row.dataset[key] || '') !== val) ok = false;
                });
                row.hidden = !ok;
                row.style.display = ok ? '' : 'none';
                if (ok) visible += 1;
            });

            // If items are nested inside cards (e.g. student template stage cards), toggle card visibility
            root.querySelectorAll('.card').forEach(card => {
                const cardItems = card.querySelectorAll('[data-search]');
                if (cardItems.length > 0) {
                    const cardHasVisible = [...cardItems].some(item => !item.hidden && item.style.display !== 'none');
                    card.hidden = !cardHasVisible;
                    card.style.display = cardHasVisible ? '' : 'none';
                }
            });

            if (empty) {
                empty.hidden = visible > 0;
                empty.style.display = visible > 0 ? 'none' : '';
                empty.querySelector('td') && (empty.style.display = visible > 0 ? 'none' : '');
            }
            if (countEl) {
                countEl.textContent = '(' + visible + ' result' + (visible === 1 ? '' : 's') + ')';
            }
            if (clearBtn) {
                const hasActiveFilter = Boolean(q || selects.some(sel => sel.value));
                clearBtn.hidden = !hasActiveFilter;
            }
        }

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
    });

    /* ------------------------------------------------------------------
     * Success pop-up: animated giant check, auto-closes after 5 seconds,
     * can be dismissed early with the X (or Esc). Triggered by a hidden
     * <div id="successPopupData" data-title data-message [data-redirect]>.
     * ------------------------------------------------------------------ */
    function showSuccessPopup(opts) {
        const duration = opts.duration || 5000;
        const overlay = document.createElement('div');
        overlay.className = 'success-popup-overlay';
        overlay.setAttribute('role', 'alertdialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-labelledby', 'successPopupTitle');
        overlay.innerHTML =
            '<div class="success-popup">' +
                '<button type="button" class="success-popup-close" aria-label="Close"><i class="fas fa-times"></i></button>' +
                '<div class="success-check" aria-hidden="true">' +
                    '<svg viewBox="0 0 120 120">' +
                        '<circle class="sc-bg" cx="60" cy="60" r="54"/>' +
                        '<circle class="sc-pulse" cx="60" cy="60" r="54"/>' +
                        '<circle class="sc-ring" cx="60" cy="60" r="54" pathLength="100"/>' +
                        '<path class="sc-tick" d="M35 62 L53 80 L87 42" pathLength="100"/>' +
                    '</svg>' +
                '</div>' +
                '<h3 id="successPopupTitle"></h3>' +
                '<p></p>' +
                '<div class="success-popup-timer"><span></span></div>' +
            '</div>';

        overlay.querySelector('h3').textContent = opts.title || 'Success';
        const msg = overlay.querySelector('p');
        if (opts.message) msg.textContent = opts.message; else msg.remove();
        overlay.querySelector('.success-popup-timer span').style.animationDuration = duration + 'ms';

        let closed = false;
        let timer = null;

        function close() {
            if (closed) return;
            closed = true;
            clearTimeout(timer);
            document.removeEventListener('keydown', onKey);
            overlay.classList.add('closing');
            setTimeout(() => {
                overlay.remove();
                if (opts.redirect) window.location.href = opts.redirect;
            }, 220);
        }
        function onKey(e) { if (e.key === 'Escape') close(); }

        const closeBtn = overlay.querySelector('.success-popup-close');
        closeBtn.addEventListener('click', close);
        document.addEventListener('keydown', onKey);
        document.body.appendChild(overlay);
        closeBtn.focus({ preventScroll: true });
        timer = setTimeout(close, duration);
    }
    window.showSuccessPopup = showSuccessPopup;

    const successData = document.getElementById('successPopupData');
    if (successData) {
        showSuccessPopup({
            title: successData.dataset.title,
            message: successData.dataset.message,
            redirect: successData.dataset.redirect || '',
            duration: parseInt(successData.dataset.duration, 10) || 5000
        });
    }

    /* ------------------------------------------------------------------
     * Editable date field + themed calendar (gray / yellow like the sidebar).
     * Markup: <div class="date-picker" data-date-picker><input type="text">
     *         <button data-date-toggle></button></div>
     * The value is always typed / stored as YYYY-MM-DD.
     * ------------------------------------------------------------------ */
    (function initDatePickers() {
        const wrappers = document.querySelectorAll('[data-date-picker]');
        if (!wrappers.length) return;

        const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        const DAYS = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
        const pad = n => String(n).padStart(2, '0');
        const toISO = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());

        function parseISO(s) {
            const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec((s || '').trim());
            if (!m) return null;
            const y = +m[1], mo = +m[2] - 1, d = +m[3];
            const dt = new Date(y, mo, d);
            return (dt.getFullYear() === y && dt.getMonth() === mo && dt.getDate() === d) ? dt : null;
        }

        const pop = document.createElement('div');
        pop.className = 'dp-popover';
        pop.setAttribute('role', 'dialog');
        pop.setAttribute('aria-label', 'Choose a date');
        pop.innerHTML =
            '<div class="dp-head">' +
                '<button type="button" class="dp-nav" data-dp-prev aria-label="Previous month"><i class="fas fa-chevron-left"></i></button>' +
                '<select data-dp-month aria-label="Month"></select>' +
                '<select data-dp-year aria-label="Year"></select>' +
                '<button type="button" class="dp-nav" data-dp-next aria-label="Next month"><i class="fas fa-chevron-right"></i></button>' +
            '</div>' +
            '<div class="dp-week"></div>' +
            '<div class="dp-grid"></div>' +
            '<div class="dp-foot"><button type="button" data-dp-today>Today</button><button type="button" data-dp-clear>Clear</button></div>';
        document.body.appendChild(pop);

        const monthSel = pop.querySelector('[data-dp-month]');
        const yearSel = pop.querySelector('[data-dp-year]');
        const grid = pop.querySelector('.dp-grid');
        pop.querySelector('.dp-week').innerHTML = DAYS.map(d => '<span>' + d + '</span>').join('');
        monthSel.innerHTML = MONTHS.map((m, i) => '<option value="' + i + '">' + m + '</option>').join('');

        let active = null;   // { input, btn }
        let viewY = 0, viewM = 0;

        function fillYears(selectedYear) {
            const now = new Date().getFullYear();
            const from = Math.min(now - 40, selectedYear);
            const to = Math.max(now + 10, selectedYear);
            let html = '';
            for (let y = to; y >= from; y--) html += '<option value="' + y + '">' + y + '</option>';
            yearSel.innerHTML = html;
        }

        function render() {
            if (!active) return;
            fillYears(viewY);
            monthSel.value = String(viewM);
            yearSel.value = String(viewY);

            const selected = parseISO(active.input.value);
            const todayISO = toISO(new Date());
            const selectedISO = selected ? toISO(selected) : '';
            const first = new Date(viewY, viewM, 1);
            const start = new Date(viewY, viewM, 1 - first.getDay());

            let html = '';
            for (let i = 0; i < 42; i++) {
                const d = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
                const iso = toISO(d);
                const cls = ['dp-day'];
                if (d.getMonth() !== viewM) cls.push('other');
                if (iso === todayISO) cls.push('today');
                if (iso === selectedISO) cls.push('selected');
                html += '<button type="button" class="' + cls.join(' ') + '" data-date="' + iso + '">' + d.getDate() + '</button>';
            }
            grid.innerHTML = html;
        }

        function position() {
            if (!active) return;
            const r = active.input.getBoundingClientRect();
            const w = pop.offsetWidth, h = pop.offsetHeight;
            let left = Math.max(8, Math.min(r.left, window.innerWidth - w - 8));
            let top = r.bottom + 6;
            if (top + h > window.innerHeight - 8 && r.top - h - 6 > 8) top = r.top - h - 6;
            pop.style.left = left + 'px';
            pop.style.top = top + 'px';
        }

        function open(input, btn) {
            if (active) active.btn.setAttribute('aria-expanded', 'false');
            active = { input, btn };
            const base = parseISO(input.value) || new Date();
            viewY = base.getFullYear();
            viewM = base.getMonth();
            btn.setAttribute('aria-expanded', 'true');
            pop.classList.add('open');
            render();
            position();
        }

        function close() {
            if (!active) return;
            active.btn.setAttribute('aria-expanded', 'false');
            pop.classList.remove('open');
            active = null;
        }

        function commit(value) {
            if (!active) return;
            const input = active.input;
            input.value = value;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            close();
            input.focus();
        }

        function shiftMonth(delta) {
            const d = new Date(viewY, viewM + delta, 1);
            viewY = d.getFullYear();
            viewM = d.getMonth();
            render();
        }

        pop.querySelector('[data-dp-prev]').addEventListener('click', () => shiftMonth(-1));
        pop.querySelector('[data-dp-next]').addEventListener('click', () => shiftMonth(1));
        monthSel.addEventListener('change', () => { viewM = +monthSel.value; render(); });
        yearSel.addEventListener('change', () => { viewY = +yearSel.value; render(); });
        pop.querySelector('[data-dp-today]').addEventListener('click', () => commit(toISO(new Date())));
        pop.querySelector('[data-dp-clear]').addEventListener('click', () => commit(''));
        grid.addEventListener('click', e => {
            const btn = e.target.closest('[data-date]');
            if (btn) commit(btn.dataset.date);
        });

        document.addEventListener('mousedown', e => {
            if (!active) return;
            if (pop.contains(e.target) || active.btn.contains(e.target) || active.input.contains(e.target)) return;
            close();
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && active) { const i = active.input; close(); i.focus(); }
        });
        window.addEventListener('resize', position);
        window.addEventListener('scroll', position, true);

        wrappers.forEach(wrap => {
            const input = wrap.querySelector('input');
            const btn = wrap.querySelector('[data-date-toggle]');
            if (!input || !btn) return;
            const group = wrap.closest('.form-field') || wrap.parentElement;
            btn.setAttribute('aria-haspopup', 'dialog');
            btn.setAttribute('aria-expanded', 'false');

            function setError(message) {
                input.setCustomValidity(message || '');
                group.classList.toggle('has-error', Boolean(message));
                group.querySelector('.date-error')?.remove();
                if (message) {
                    const err = document.createElement('div');
                    err.className = 'field-error date-error';
                    err.textContent = message;
                    group.appendChild(err);
                }
            }

            function check(final) {
                const v = input.value.trim();
                if (v === '' || parseISO(v)) { setError(''); return; }
                // Only nag while typing once all 10 characters are in; always nag on blur
                if (final || v.length === 10) setError('Enter a valid date as YYYY-MM-DD.');
                else input.setCustomValidity('Enter a valid date as YYYY-MM-DD.');
            }

            btn.addEventListener('click', () => {
                if (active && active.input === input) close(); else open(input, btn);
            });

            // Auto-insert the dashes while typing digits
            input.addEventListener('input', e => {
                const typingAtEnd = input.selectionStart === input.value.length;
                if (typingAtEnd && !(e.inputType || '').startsWith('delete')) {
                    const d = input.value.replace(/\D/g, '').slice(0, 8);
                    let out = d.slice(0, 4);
                    if (d.length > 4) out += '-' + d.slice(4, 6);
                    if (d.length > 6) out += '-' + d.slice(6, 8);
                    input.value = out;
                }
                check(false);
                if (active && active.input === input) {
                    const p = parseISO(input.value);
                    if (p) { viewY = p.getFullYear(); viewM = p.getMonth(); }
                    render();
                }
            });
            input.addEventListener('blur', () => check(true));
            check(false);
        });
    })();

    /* ==========================================================================
 * Student Population dashboard (shared by both coordinator dashboards)
 * Pure SVG: no external chart library. Types: bar, hbar, pie, donut.
 * Data comes from <script type="application/json" data-pop-data>.
 * ========================================================================== */
(function () {
    'use strict';

    const SVG_NS = 'http://www.w3.org/2000/svg';
    const STORAGE_KEY = 'adzuProgramChartType';
    const DEFAULT_TYPE = 'bar';
    const REDUCED_MOTION = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    const META = {
        bar:   { title: 'Total Students per Program',        desc: t => 'Number of enrolled students in each academic program (' + fmt(t) + ' in total).' },
        hbar:  { title: 'Total Students per Program',        desc: t => 'Programs ranked by number of enrolled students (' + fmt(t) + ' in total).' },
        pie:   { title: 'Share of Total Student Population', desc: t => 'Each program\'s proportion of the ' + fmt(t) + ' enrolled students.' },
        donut: { title: 'Share of Total Student Population', desc: t => 'Each program\'s proportion of the ' + fmt(t) + ' enrolled students.' }
    };

    /* ---------- helpers ---------- */
    function svgEl(tag, attrs, text) {
        const node = document.createElementNS(SVG_NS, tag);
        if (attrs) Object.keys(attrs).forEach(k => node.setAttribute(k, attrs[k]));
        if (text !== undefined && text !== null) node.textContent = text;
        return node;
    }
    function fmt(n) { return Number(n).toLocaleString('en-US'); }
    function truncate(str, max) {
        str = String(str);
        max = Math.max(4, Math.floor(max));
        return str.length > max ? str.slice(0, max - 1).trimEnd() + '\u2026' : str;
    }
    function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }

    /** Integer-friendly axis: returns { top, ticks } with top >= maxVal. */
    function axisScale(maxVal) {
        const v = Math.max(1, maxVal);
        const raw = v / 5;
        const exp = Math.pow(10, Math.floor(Math.log10(raw)));
        const f = raw / exp;
        const nf = f <= 1 ? 1 : f <= 2 ? 2 : f <= 5 ? 5 : 10;
        const step = Math.max(1, nf * exp);
        const top = Math.ceil(v / step) * step;
        const ticks = [];
        for (let t = 0; t <= top; t += step) ticks.push(t);
        return { top: top, ticks: ticks };
    }

    function readStoredType() { try { return window.sessionStorage.getItem(STORAGE_KEY); } catch (e) { return null; } }
    function storeType(t) { try { window.sessionStorage.setItem(STORAGE_KEY, t); } catch (e) { /* ignore */ } }

    /* ---------- one chart instance ---------- */
    function initProgramChart(root) {
        if (root.dataset.popReady === '1') return;

        const dataEl  = root.querySelector('[data-pop-data]');
        const chartEl = root.querySelector('[data-pop-chart]');
        const stage   = root.querySelector('[data-pop-stage]');
        const tip     = root.querySelector('[data-pop-tooltip]');
        const toggle  = root.querySelector('[data-pop-toggle]');
        const menu    = root.querySelector('[data-pop-menu]');
        const select  = root.querySelector('[data-pop-select]');
        const titleEl = root.querySelector('[data-pop-chart-title]');
        const descEl  = root.querySelector('[data-pop-chart-desc]');
        const labelEl = root.querySelector('[data-pop-current-label]');
        const iconEl  = root.querySelector('[data-pop-current-icon]');
        if (!dataEl || !chartEl || !stage || !tip || !toggle || !menu || !select) return;

        let payload;
        try { payload = JSON.parse(dataEl.textContent); } catch (e) { payload = null; }
        const programs = payload && Array.isArray(payload.programs) ? payload.programs : [];
        const total = payload ? Number(payload.total) || 0 : 0;
        if (!programs.length || total <= 0) return;
        root.dataset.popReady = '1';

        const options = Array.prototype.slice.call(menu.querySelectorAll('[data-chart-type]'));
        const legendItems = Array.prototype.slice.call(root.querySelectorAll('.pop-legend-item'));

        let type = DEFAULT_TYPE;
        let rafId = 0;
        let switchTimer = 0;
        let lastW = 0;

        /* ----- tooltip + highlight ----- */
        function setActive(i) {
            root.querySelectorAll('[data-index]').forEach(function (n) {
                n.classList.toggle('is-active', i !== null && Number(n.getAttribute('data-index')) === i);
            });
            chartEl.classList.toggle('has-active', i !== null);
        }

        function hideTip() { tip.hidden = true; }

        function showTip(i, x, y) {
            const p = programs[i];
            if (!p) return;
            tip.textContent = '';
            const head = document.createElement('div');
            head.className = 'pop-tooltip-name';
            const dot = document.createElement('span');
            dot.className = 'pop-tooltip-dot';
            dot.style.background = p.color;
            head.appendChild(dot);
            head.appendChild(document.createTextNode(p.label));
            const meta = document.createElement('div');
            meta.className = 'pop-tooltip-meta';
            meta.textContent = fmt(p.count) + ' student' + (p.count === 1 ? '' : 's') + ' \u00b7 ' + p.percent + '% of total';
            tip.appendChild(head);
            tip.appendChild(meta);
            tip.hidden = false;

            const sr = stage.getBoundingClientRect();
            const tw = tip.offsetWidth, th = tip.offsetHeight;
            let left = x - sr.left + 14;
            let top = y - sr.top + 14;
            if (left + tw > sr.width - 4) left = x - sr.left - tw - 14;
            if (top + th > sr.height - 4) top = y - sr.top - th - 14;
            tip.style.left = Math.max(4, left) + 'px';
            tip.style.top = Math.max(4, top) + 'px';
        }

        function bindShape(node, i) {
            const p = programs[i];
            node.setAttribute('tabindex', '0');
            node.setAttribute('role', 'img');
            node.setAttribute('aria-label', p.label + ': ' + fmt(p.count) + ' students, ' + p.percent + '%');
            node.addEventListener('pointerenter', function (e) { setActive(i); showTip(i, e.clientX, e.clientY); });
            node.addEventListener('pointermove', function (e) { showTip(i, e.clientX, e.clientY); });
            node.addEventListener('pointerleave', function (e) {
                if (e.pointerType === 'touch') return; // keep visible on touch until next tap elsewhere
                setActive(null); hideTip();
            });
            node.addEventListener('focus', function () {
                setActive(i);
                const r = node.getBoundingClientRect();
                showTip(i, r.left + r.width / 2, r.top + Math.min(r.height / 2, 40));
            });
            node.addEventListener('blur', function () { setActive(null); hideTip(); });
        }

        root.addEventListener('pointerdown', function (e) {
            if (e.pointerType === 'touch' && !(e.target.closest && e.target.closest('.pop-shape'))) {
                setActive(null); hideTip();
            }
        });

        legendItems.forEach(function (li) {
            const i = Number(li.getAttribute('data-index'));
            li.setAttribute('tabindex', '0');
            li.addEventListener('pointerenter', function () { setActive(i); });
            li.addEventListener('pointerleave', function () { setActive(null); });
            li.addEventListener('focus', function () { setActive(i); });
            li.addEventListener('blur', function () { setActive(null); });
        });

        /* ----- renderers ----- */
        function drawBar(W) {
            const n = programs.length;
            const rotate = n > 5 || W < 480;
            const H = Math.round(Math.max(300, Math.min(400, W * 0.58)));
            const m = { top: 28, right: 16, bottom: rotate ? 92 : 52, left: 48 };
            const innerW = W - m.left - m.right;
            const innerH = H - m.top - m.bottom;
            const sc = axisScale(Math.max.apply(null, programs.map(function (p) { return p.count; })));
            const svg = svgEl('svg', { viewBox: '0 0 ' + W + ' ' + H, class: 'pop-svg', preserveAspectRatio: 'xMidYMid meet' });

            sc.ticks.forEach(function (t) {
                const y = m.top + innerH - (t / sc.top) * innerH;
                svg.appendChild(svgEl('line', { x1: m.left, x2: W - m.right, y1: y, y2: y, class: t === 0 ? 'pop-axis' : 'pop-grid' }));
                svg.appendChild(svgEl('text', { x: m.left - 8, y: y + 4, 'text-anchor': 'end', class: 'pop-tick' }, fmt(t)));
            });

            const band = innerW / n;
            const barW = Math.max(6, Math.min(64, band * 0.62));
            const maxChars = Math.floor(band / 6.6);

            programs.forEach(function (p, i) {
                const h = (p.count / sc.top) * innerH;
                const x = m.left + band * i + (band - barW) / 2;
                const y = m.top + innerH - h;
                const cx = x + barW / 2;

                const bar = svgEl('rect', { x: x, y: y, width: barW, height: h, rx: 4, fill: p.color, class: 'pop-shape pop-bar', 'data-index': i });
                bar.style.animationDelay = (i * 60) + 'ms';
                bindShape(bar, i);
                svg.appendChild(bar);

                const val = svgEl('text', { x: cx, y: y - 8, 'text-anchor': 'middle', class: 'pop-value pop-fade' }, fmt(p.count));
                val.style.animationDelay = (350 + i * 60) + 'ms';
                svg.appendChild(val);

                const ly = m.top + innerH + 18;
                const lbl = svgEl('text', { x: cx, y: ly, 'text-anchor': rotate ? 'end' : 'middle', class: 'pop-cat' }, truncate(p.label, rotate ? 18 : maxChars));
                if (rotate) lbl.setAttribute('transform', 'rotate(-35 ' + cx + ' ' + ly + ')');
                svg.appendChild(lbl);
            });

            return { svg: svg, start: null };
        }

        function drawHBar(W) {
            const n = programs.length;
            const rowH = 42, barH = 24;
            const m = { top: 12, right: W < 480 ? 96 : 108, bottom: 34, left: Math.round(Math.min(170, Math.max(96, W * 0.3))) };
            const H = m.top + m.bottom + n * rowH;
            const innerW = Math.max(40, W - m.left - m.right);
            const sc = axisScale(Math.max.apply(null, programs.map(function (p) { return p.count; })));
            const svg = svgEl('svg', { viewBox: '0 0 ' + W + ' ' + H, class: 'pop-svg', preserveAspectRatio: 'xMidYMid meet' });

            sc.ticks.forEach(function (t) {
                const x = m.left + (t / sc.top) * innerW;
                svg.appendChild(svgEl('line', { x1: x, x2: x, y1: m.top, y2: m.top + n * rowH, class: t === 0 ? 'pop-axis' : 'pop-grid' }));
                svg.appendChild(svgEl('text', { x: x, y: H - 12, 'text-anchor': 'middle', class: 'pop-tick' }, fmt(t)));
            });

            const maxChars = (m.left - 14) / 6.4;
            programs.forEach(function (p, i) {
                const y = m.top + i * rowH;
                const by = y + (rowH - barH) / 2;
                const w = Math.max(2, (p.count / sc.top) * innerW);

                svg.appendChild(svgEl('text', { x: m.left - 10, y: by + barH / 2 + 4, 'text-anchor': 'end', class: 'pop-cat' }, truncate(p.label, maxChars)));

                const bar = svgEl('rect', { x: m.left, y: by, width: w, height: barH, rx: 4, fill: p.color, class: 'pop-shape pop-bar-h', 'data-index': i });
                bar.style.animationDelay = (i * 60) + 'ms';
                bindShape(bar, i);
                svg.appendChild(bar);

                const val = svgEl('text', { x: m.left + w + 8, y: by + barH / 2 + 4, 'text-anchor': 'start', class: 'pop-value pop-fade' }, fmt(p.count) + ' \u00b7 ' + p.percent + '%');
                val.style.animationDelay = (350 + i * 60) + 'ms';
                svg.appendChild(val);
            });

            return { svg: svg, start: null };
        }

        function drawRadial(donut) {
            const c = 200, outer = 176;
            const sw = donut ? 70 : outer;
            const r = outer - sw / 2;
            const C = 2 * Math.PI * r;
            const labelR = donut ? r : outer * 0.64;
            const gap = programs.length > 1 ? (donut ? 1.5 : 2) : 0;

            const svg = svgEl('svg', { viewBox: '0 0 400 400', class: 'pop-svg pop-svg-radial', preserveAspectRatio: 'xMidYMid meet' });
            const g = svgEl('g', { transform: 'rotate(-90 ' + c + ' ' + c + ')' });
            if (donut) g.appendChild(svgEl('circle', { cx: c, cy: c, r: r, fill: 'none', 'stroke-width': sw, class: 'pop-track' }));

            const slices = [];
            let acc = 0;
            programs.forEach(function (p, i) {
                const len = (p.count / total) * C;
                const start = acc;
                acc += len;
                const node = svgEl('circle', {
                    cx: c, cy: c, r: r, fill: 'none', stroke: p.color, 'stroke-width': sw,
                    'stroke-dasharray': '0 ' + C, 'stroke-dashoffset': -(start + gap / 2),
                    class: 'pop-shape pop-slice', 'data-index': i
                });
                bindShape(node, i);
                g.appendChild(node);
                slices.push({ node: node, start: start, len: len });
            });
            svg.appendChild(g);

            // Labels (percentage, plus count on larger slices)
            programs.forEach(function (p, i) {
                const frac = p.count / total;
                if (frac < 0.05) return; // too small to label: see legend / tooltip
                const s = slices[i];
                const a = ((s.start + s.len / 2) / C) * 2 * Math.PI;
                const lx = c + labelR * Math.sin(a);
                const ly = c - labelR * Math.cos(a);
                const withCount = frac >= 0.08;
                const pct = svgEl('text', { x: lx, y: withCount ? ly - 2 : ly + 5, fill: p.ink, class: 'pop-slice-pct pop-fade' }, p.percent + '%');
                pct.style.animationDelay = '650ms';
                svg.appendChild(pct);
                if (withCount) {
                    const cnt = svgEl('text', { x: lx, y: ly + 14, fill: p.ink, class: 'pop-slice-cnt pop-fade' }, fmt(p.count));
                    cnt.style.animationDelay = '650ms';
                    svg.appendChild(cnt);
                }
            });

            if (donut) {
                svg.appendChild(svgEl('text', { x: c, y: c + 6, class: 'pop-center-value' }, fmt(total)));
                svg.appendChild(svgEl('text', { x: c, y: c + 28, class: 'pop-center-label' }, 'Total Students'));
            }

            function applySweep(t) {
                const drawn = t * C;
                slices.forEach(function (s) {
                    const vis = Math.max(0, Math.min(s.len - gap, drawn - s.start - gap / 2));
                    s.node.setAttribute('stroke-dasharray', vis.toFixed(3) + ' ' + C.toFixed(3));
                });
            }

            return {
                svg: svg,
                start: function (animate) {
                    if (!animate) { applySweep(1); return; }
                    const t0 = performance.now();
                    applySweep(0);
                    const tick = function (now) {
                        const t = Math.min(1, (now - t0) / 850);
                        applySweep(easeOutCubic(t));
                        if (t < 1) rafId = requestAnimationFrame(tick);
                    };
                    rafId = requestAnimationFrame(tick);
                }
            };
        }

        function currentWidth() { return Math.round(chartEl.clientWidth) || 640; }

        function showError() {
            chartEl.textContent = '';
            const box = document.createElement('div');
            box.className = 'alert alert-warning';
            box.textContent = 'The chart could not be displayed. The figures are still available in the legend.';
            chartEl.appendChild(box);
        }

        function draw(animate) {
            cancelAnimationFrame(rafId);
            hideTip();
            setActive(null);
            const animated = !!animate && !REDUCED_MOTION;
            chartEl.classList.toggle('no-anim', !animated);
            try {
                lastW = currentWidth();
                let result;
                if (type === 'hbar') result = drawHBar(lastW);
                else if (type === 'pie') result = drawRadial(false);
                else if (type === 'donut') result = drawRadial(true);
                else result = drawBar(lastW);

                chartEl.textContent = '';
                chartEl.appendChild(result.svg);
                chartEl.setAttribute('aria-label', META[type].title + '. ' + programs.map(function (p) {
                    return p.label + ': ' + fmt(p.count) + ' students (' + p.percent + '%)';
                }).join('; '));
                if (result.start) result.start(animated);
            } catch (err) {
                if (window.console) console.error('Program chart error:', err);
                showError();
            }
        }

        function setType(next, opts) {
            opts = opts || {};
            if (!META[next]) next = DEFAULT_TYPE;
            type = next;

            options.forEach(function (o) {
                const on = o.getAttribute('data-chart-type') === type;
                o.setAttribute('aria-checked', on ? 'true' : 'false');
                o.classList.toggle('is-selected', on);
                if (on) {
                    if (labelEl) labelEl.textContent = o.getAttribute('data-label') || '';
                    if (iconEl) iconEl.className = 'fas ' + (o.getAttribute('data-icon') || 'fa-chart-bar');
                }
            });
            if (titleEl) titleEl.textContent = META[type].title;
            if (descEl) descEl.textContent = META[type].desc(total);
            if (opts.persist) storeType(type);

            clearTimeout(switchTimer);
            if (opts.fade && !REDUCED_MOTION) {
                stage.classList.add('is-switching');
                switchTimer = setTimeout(function () {
                    draw(true);
                    stage.classList.remove('is-switching');
                }, 170);
            } else {
                stage.classList.remove('is-switching');
                draw(true);
            }
        }

        /* ----- dropdown ----- */
        function openMenu() {
            menu.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            const current = options.filter(function (o) { return o.getAttribute('data-chart-type') === type; })[0] || options[0];
            if (current) current.focus();
        }
        function closeMenu(returnFocus) {
            menu.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            if (returnFocus) toggle.focus();
        }

        toggle.addEventListener('click', function () { menu.hidden ? openMenu() : closeMenu(false); });
        toggle.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown' && menu.hidden) { e.preventDefault(); openMenu(); }
        });
        options.forEach(function (opt, idx) {
            opt.addEventListener('click', function () {
                setType(opt.getAttribute('data-chart-type'), { persist: true, fade: true });
                closeMenu(true);
            });
            opt.addEventListener('keydown', function (e) {
                let target = null;
                if (e.key === 'ArrowDown') target = options[(idx + 1) % options.length];
                else if (e.key === 'ArrowUp') target = options[(idx - 1 + options.length) % options.length];
                else if (e.key === 'Home') target = options[0];
                else if (e.key === 'End') target = options[options.length - 1];
                else if (e.key === 'Tab') { closeMenu(false); return; }
                if (target) { e.preventDefault(); target.focus(); }
            });
        });
        document.addEventListener('click', function (e) {
            if (!menu.hidden && !select.contains(e.target)) closeMenu(false);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !menu.hidden) closeMenu(true);
        });

        /* ----- responsive redraw (bar charts depend on width) ----- */
        function onResize() {
            if ((type === 'bar' || type === 'hbar') && Math.abs(currentWidth() - lastW) > 8) draw(false);
        }
        if ('ResizeObserver' in window) {
            new ResizeObserver(onResize).observe(chartEl);
        } else {
            let resizeTimer = 0;
            window.addEventListener('resize', function () { clearTimeout(resizeTimer); resizeTimer = setTimeout(onResize, 120); });
        }

        /* ----- go ----- */
        const stored = readStoredType();
        setType(META[stored] ? stored : DEFAULT_TYPE, { persist: false, fade: false });
    }

    function boot() {
        document.querySelectorAll('[data-program-chart]').forEach(function (root) {
            try { initProgramChart(root); }
            catch (err) { if (window.console) console.error('Program chart failed to start:', err); }
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();

})();

/* ==========================================================================
 * Presentation Schedule Calendar
 * Used on coordinator/dashboard.php and coordinator/schedule/manage.php.
 * Markup: includes/calendar.php  |  Data: coordinator/schedule/calendar_data.php
 * ========================================================================== */
(function () {
    'use strict';

    const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const CLOSE_MS = 180;
    const REDUCED = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    const STATUS_LABELS = { pending: 'Pending', confirmed: 'Confirmed', completed: 'Completed', scheduled: 'Scheduled' };

    /* ---------- small DOM helpers (all text goes through textContent) ---------- */
    function el(tag, cls, text) {
        const n = document.createElement(tag);
        if (cls) n.className = cls;
        if (text !== undefined && text !== null) n.textContent = text;
        return n;
    }
    function icon(name) {
        const i = el('i', 'fas ' + name);
        i.setAttribute('aria-hidden', 'true');
        return i;
    }

    /* ---------- calendar-date maths (UTC arithmetic: no local-time drift) ---------- */
    function pad(n) { return n < 10 ? '0' + n : String(n); }
    function toISO(y, m, d) { return y + '-' + pad(m) + '-' + pad(d); }
    function parseISO(s) {
        const p = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s || '');
        return p ? { y: +p[1], m: +p[2], d: +p[3] } : null;
    }
    function utcDate(iso) { const p = parseISO(iso); return new Date(Date.UTC(p.y, p.m - 1, p.d)); }
    function fromUtc(dt) { return toISO(dt.getUTCFullYear(), dt.getUTCMonth() + 1, dt.getUTCDate()); }
    function addDays(iso, n) { const dt = utcDate(iso); dt.setUTCDate(dt.getUTCDate() + n); return fromUtc(dt); }
    function weekday(iso) { return utcDate(iso).getUTCDay(); }
    function daysInMonth(y, m) { return new Date(Date.UTC(y, m, 0)).getUTCDate(); }
    function shiftMonth(iso, delta) {
        const p = parseISO(iso);
        const idx = p.y * 12 + (p.m - 1) + delta;
        const y = Math.floor(idx / 12), m = (idx % 12) + 1;
        return toISO(y, m, Math.min(p.d, daysInMonth(y, m)));
    }
    function longDate(iso) {
        return utcDate(iso).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', timeZone: 'UTC' });
    }

    /** Today's date (YYYY-MM-DD) in the configured timezone, not the visitor's. */
    function todayIn(tz) {
        try {
            const parts = new Intl.DateTimeFormat('en-US', { timeZone: tz, year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(new Date());
            const get = t => (parts.find(p => p.type === t) || {}).value;
            const iso = get('year') + '-' + get('month') + '-' + get('day');
            return parseISO(iso) ? iso : null;
        } catch (e) {
            return null;
        }
    }

    /* ---------------------------------------------------------------------- */
    function initCalendar(root) {
        const endpoint = root.dataset.endpoint;
        const tz = root.dataset.timezone || 'Asia/Manila';
        const pollMs = Math.max(0, parseInt(root.dataset.poll, 10) || 0);

        const refs = {
            grid: root.querySelector('[data-psc-grid]'),
            calendar: root.querySelector('.psc-calendar'),
            month: root.querySelector('[data-psc-month]'),
            summary: root.querySelector('[data-psc-summary]'),
            prev: root.querySelector('[data-psc-prev]'),
            next: root.querySelector('[data-psc-next]'),
            todayBtn: root.querySelector('[data-psc-today]'),
            alert: root.querySelector('[data-psc-alert]'),
            alertMsg: root.querySelector('[data-psc-alert-msg]'),
            retry: root.querySelector('[data-psc-retry]'),
            overlay: root.querySelector('[data-psc-overlay]'),
        };
        refs.dialog = refs.overlay.querySelector('.psc-dialog');
        refs.dlgTitle = refs.overlay.querySelector('[data-psc-dlg-title]');
        refs.dlgSub = refs.overlay.querySelector('[data-psc-dlg-sub]');
        refs.dlgBody = refs.overlay.querySelector('[data-psc-dlg-body]');
        refs.close = refs.overlay.querySelector('[data-psc-close]');
        // Lift the dialog out of the page layout so no parent can clip or stack over it.
        document.body.appendChild(refs.overlay);

        /* ----- state ----- */
        let today = todayIn(tz) || root.dataset.today;
        const t0 = parseISO(today);
        let view = { y: t0.y, m: t0.m };
        let focusISO = today;
        const cache = new Map();      // range key -> { dates, signature }
        let dates = {};               // events for the visible grid
        let loaded = false;           // true once the visible grid has real data
        let loading = false;
        let errorMsg = '';
        let seq = 0;
        let controller = null;
        const dlg = { open: false, iso: null, trigger: null, locked: false, prevOverflow: '', timer: 0 };

        /* ----- range helpers ----- */
        function gridStart() {
            const first = toISO(view.y, view.m, 1);
            return addDays(first, -weekday(first));
        }
        function range() {
            const start = gridStart();
            const end = addDays(start, 41);
            return { start: start, end: end, key: start + '|' + end };
        }
        function inView(iso) { const p = parseISO(iso); return p.y === view.y && p.m === view.m; }

        /* ----- rendering ----- */
        function monthCount() {
            const prefix = view.y + '-' + pad(view.m) + '-';
            let n = 0;
            Object.keys(dates).forEach(k => { if (k.indexOf(prefix) === 0) n += dates[k].length; });
            return n;
        }

        function updateSummary() {
            const label = MONTHS[view.m - 1] + ' ' + view.y;
            if (!loaded) {
                refs.summary.textContent = errorMsg ? 'Schedule unavailable' : 'Loading schedules…';
                return;
            }
            const n = monthCount();
            refs.summary.textContent = n === 0
                ? 'No presentations scheduled in ' + label
                : n + (n === 1 ? ' presentation' : ' presentations') + ' scheduled in ' + label;
        }

        function updateStatus() {
            root.classList.toggle('is-loading', loading);
            refs.calendar.setAttribute('aria-busy', loading && !loaded ? 'true' : 'false');
            refs.alert.hidden = !(errorMsg && !loaded);
            refs.alertMsg.textContent = errorMsg || '';
            updateSummary();
        }

        function buildCell(iso) {
            const p = parseISO(iso);
            const list = dates[iso] || [];
            const n = list.length;
            const isToday = iso === today;

            const cell = el('div', 'psc-cell');
            cell.setAttribute('role', 'gridcell');

            const btn = el('button', 'psc-day');
            btn.type = 'button';
            btn.dataset.date = iso;
            btn.tabIndex = iso === focusISO ? 0 : -1;
            if (n) btn.classList.add('is-assigned');
            if (isToday) { btn.classList.add('is-today'); btn.setAttribute('aria-current', 'date'); }
            if (!inView(iso)) btn.classList.add('is-outside');

            let label = longDate(iso) + '. ';
            if (!loaded) label += 'Schedule loading.';
            else label += n ? n + (n === 1 ? ' presentation scheduled.' : ' presentations scheduled.') : 'No presentations scheduled.';
            if (isToday) label += ' Today.';
            btn.setAttribute('aria-label', label);

            btn.appendChild(el('span', 'psc-day-num', String(p.d)));
            if (n) {
                const meta = el('span', 'psc-day-meta');
                meta.appendChild(el('span', 'psc-count', String(n)));
                if (list[0].startLabel) meta.appendChild(el('span', 'psc-time', list[0].startLabel));
                btn.appendChild(meta);
            }
            cell.appendChild(btn);
            return cell;
        }

        function render() {
            const hadFocus = refs.grid.contains(document.activeElement);
            refs.month.textContent = MONTHS[view.m - 1] + ' ' + view.y;
            const start = gridStart();
            refs.grid.textContent = '';
            for (let w = 0; w < 6; w++) {
                const row = el('div', 'psc-week');
                row.setAttribute('role', 'row');
                for (let i = 0; i < 7; i++) row.appendChild(buildCell(addDays(start, w * 7 + i)));
                refs.grid.appendChild(row);
            }
            updateStatus();
            if (hadFocus) focusDay(focusISO);
        }

        function focusDay(iso) {
            const btn = refs.grid.querySelector('[data-date="' + iso + '"]');
            if (!btn) return;
            refs.grid.querySelectorAll('.psc-day[tabindex="0"]').forEach(b => { b.tabIndex = -1; });
            btn.tabIndex = 0;
            btn.focus({ preventScroll: false });
        }

        /* ----- loading ----- */
        function fail(err, hadData) {
            if (hadData) return; // a failed background refresh keeps showing the last good data
            errorMsg = (err && err.userMessage) || 'We couldn’t load the schedule. Check your connection and try again.';
            updateStatus();
            refreshDialog();
        }

        function load(silent) {
            const r = range();
            if (controller) controller.abort();
            controller = 'AbortController' in window ? new AbortController() : null;
            const mine = ++seq;
            const hadData = cache.has(r.key);
            if (!silent || !hadData) { loading = true; errorMsg = ''; updateStatus(); }

            const url = endpoint + (endpoint.indexOf('?') > -1 ? '&' : '?') + 'start=' + r.start + '&end=' + r.end;
            fetch(url, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { Accept: 'application/json' },
                signal: controller ? controller.signal : undefined,
            })
                .then(res => res.json().catch(() => null).then(body => ({ res: res, body: body })))
                .then(out => {
                    if (mine !== seq) return;
                    if (!out.res.ok || !out.body || out.body.ok !== true) {
                        const e = new Error('Request failed');
                        e.userMessage = out.body && out.body.error;
                        throw e;
                    }
                    const prev = cache.get(r.key);
                    const next = { dates: out.body.dates || {}, signature: out.body.signature || '' };
                    cache.set(r.key, next);
                    loading = false;
                    errorMsg = '';
                    const changed = !prev || prev.signature !== next.signature || !loaded;
                    dates = next.dates;
                    loaded = true;
                    if (changed) { render(); refreshDialog(); } else { updateStatus(); }
                })
                .catch(err => {
                    if (err && err.name === 'AbortError') return;
                    if (mine !== seq) return;
                    loading = false;
                    fail(err, hadData);
                    updateStatus();
                });
        }

        function setView(y, m, opts) {
            opts = opts || {};
            view = { y: y, m: m };
            const first = toISO(y, m, 1);
            if (!opts.keepFocus) focusISO = inView(today) ? today : first;
            const cached = cache.get(range().key);
            dates = cached ? cached.dates : {};
            loaded = !!cached;
            errorMsg = '';
            render();
            if (opts.focus) focusDay(focusISO);
            load(false);
        }

        function stepMonth(delta) {
            const idx = view.y * 12 + (view.m - 1) + delta;
            setView(Math.floor(idx / 12), (idx % 12) + 1);
        }

        function moveFocus(iso) {
            const p = parseISO(iso);
            focusISO = iso;
            if (p.y !== view.y || p.m !== view.m) setView(p.y, p.m, { keepFocus: true, focus: true });
            else focusDay(iso);
        }

        /* ----- details dialog ----- */
        function eventCard(ev) {
            const card = el('article', 'psc-event');

            const time = el('div', 'psc-event-time');
            const clock = el('span', 'psc-event-clock');
            clock.appendChild(icon('fa-clock'));
            clock.appendChild(document.createTextNode(' Time'));
            time.appendChild(clock);
            if (ev.startLabel) {
                time.appendChild(el('strong', 'psc-event-start', ev.startLabel));
                const tail = [];
                if (ev.endLabel) tail.push('to ' + ev.endLabel);
                if (ev.duration) tail.push(ev.duration);
                if (tail.length) time.appendChild(el('span', 'psc-event-end', tail.join(' · ')));
            } else {
                time.appendChild(el('strong', 'psc-event-start', 'To be announced'));
            }
            card.appendChild(time);

            const main = el('div', 'psc-event-main');
            const status = (ev.status || '').toLowerCase();
            if (STATUS_LABELS[status]) {
                const chip = el('span', 'psc-status psc-status-' + status);
                chip.appendChild(el('span', 'psc-status-dot'));
                chip.appendChild(document.createTextNode(STATUS_LABELS[status]));
                main.appendChild(chip);
            }

            const who = el('div', 'psc-field');
            who.appendChild(el('span', 'psc-field-label', 'Student'));
            who.appendChild(el('span', 'psc-event-student', ev.student || 'Student not recorded'));
            main.appendChild(who);

            const what = el('div', 'psc-field');
            what.appendChild(el('span', 'psc-field-label', 'Presentation title'));
            what.appendChild(el('span', 'psc-event-title', ev.title || 'Title not recorded'));
            main.appendChild(what);

            const meta = el('div', 'psc-event-meta');
            if (ev.stage) meta.appendChild(el('span', 'psc-chip', ev.stage));
            if (ev.venue) {
                const v = el('span', 'psc-venue');
                v.appendChild(icon('fa-map-marker-alt'));
                v.appendChild(document.createTextNode(' ' + ev.venue));
                meta.appendChild(v);
            }
            if (meta.childNodes.length) main.appendChild(meta);

            card.appendChild(main);
            return card;
        }

        function notice(iconName, text, retry) {
            const box = el('div', 'psc-note');
            box.appendChild(icon(iconName));
            box.appendChild(el('p', null, text));
            if (retry) {
                const b = el('button', 'psc-alert-btn', 'Try again');
                b.type = 'button';
                b.addEventListener('click', () => load(false));
                box.appendChild(b);
            }
            return box;
        }

        function fillDialog() {
            const iso = dlg.iso;
            const list = dates[iso] || [];
            refs.dlgTitle.textContent = longDate(iso);
            refs.dlgBody.textContent = '';
            refs.dlgBody.scrollTop = 0;

            if (!loaded && errorMsg) {
                refs.dlgSub.textContent = '';
                refs.dlgBody.appendChild(notice('fa-exclamation-triangle', errorMsg, true));
            } else if (!loaded) {
                refs.dlgSub.textContent = '';
                const box = notice('fa-circle-notch fa-spin', 'Loading schedule…');
                refs.dlgBody.appendChild(box);
            } else if (!list.length) {
                refs.dlgSub.textContent = '';
                refs.dlgBody.appendChild(notice('fa-calendar', 'No presentations scheduled for this date'));
            } else {
                refs.dlgSub.textContent = list.length + (list.length === 1 ? ' presentation scheduled' : ' presentations scheduled');
                const wrap = el('div', 'psc-events');
                list.forEach(ev => wrap.appendChild(eventCard(ev)));
                refs.dlgBody.appendChild(wrap);
            }
        }

        function refreshDialog() { if (dlg.open) fillDialog(); }

        function openDialog(iso, trigger) {
            dlg.iso = iso;
            dlg.trigger = trigger;
            fillDialog();
            if (dlg.timer) { clearTimeout(dlg.timer); dlg.timer = 0; }
            if (!dlg.locked) {
                dlg.prevOverflow = document.body.style.overflow;
                document.body.style.overflow = 'hidden';
                dlg.locked = true;
            }
            dlg.open = true;
            refs.overlay.hidden = false;
            refs.overlay.classList.remove('is-closing');
            // Restart the entrance animation on every open.
            void refs.overlay.offsetWidth;
            refs.overlay.classList.add('is-open');
            refs.close.focus({ preventScroll: true });
        }

        function finishClose() {
            dlg.timer = 0;
            refs.overlay.hidden = true;
            refs.overlay.classList.remove('is-open', 'is-closing');
            if (dlg.locked) { document.body.style.overflow = dlg.prevOverflow; dlg.locked = false; }
            const target = refs.grid.querySelector('[data-date="' + dlg.iso + '"]') || dlg.trigger;
            if (target && document.contains(target)) {
                focusISO = dlg.iso || focusISO;
                refs.grid.querySelectorAll('.psc-day[tabindex="0"]').forEach(b => { b.tabIndex = -1; });
                target.tabIndex = 0;
                target.focus({ preventScroll: true });
            }
        }

        function closeDialog() {
            if (!dlg.open) return;
            dlg.open = false;
            if (REDUCED) { finishClose(); return; }
            refs.overlay.classList.remove('is-open');
            refs.overlay.classList.add('is-closing');
            dlg.timer = setTimeout(finishClose, CLOSE_MS + 40);
        }

        /* ----- events ----- */
        refs.prev.addEventListener('click', () => stepMonth(-1));
        refs.next.addEventListener('click', () => stepMonth(1));
        refs.todayBtn.addEventListener('click', () => {
            today = todayIn(tz) || today;
            const p = parseISO(today);
            focusISO = today;
            setView(p.y, p.m, { keepFocus: true });
        });
        refs.retry.addEventListener('click', () => load(false));

        refs.grid.addEventListener('click', e => {
            const btn = e.target.closest && e.target.closest('.psc-day');
            if (btn) openDialog(btn.dataset.date, btn);
        });

        refs.grid.addEventListener('keydown', e => {
            const btn = e.target.closest && e.target.closest('.psc-day');
            if (!btn || e.altKey || e.ctrlKey || e.metaKey) return;
            const iso = btn.dataset.date;
            let next = null;
            switch (e.key) {
                case 'ArrowLeft': next = addDays(iso, -1); break;
                case 'ArrowRight': next = addDays(iso, 1); break;
                case 'ArrowUp': next = addDays(iso, -7); break;
                case 'ArrowDown': next = addDays(iso, 7); break;
                case 'Home': next = addDays(iso, -weekday(iso)); break;
                case 'End': next = addDays(iso, 6 - weekday(iso)); break;
                case 'PageUp': next = shiftMonth(iso, -1); break;
                case 'PageDown': next = shiftMonth(iso, 1); break;
                default: return;
            }
            e.preventDefault();
            moveFocus(next);
        });

        refs.close.addEventListener('click', closeDialog);
        refs.overlay.addEventListener('mousedown', e => { if (e.target === refs.overlay) closeDialog(); });
        refs.overlay.addEventListener('keydown', e => {
            if (e.key !== 'Tab') return;
            const items = Array.prototype.filter.call(
                refs.dialog.querySelectorAll('button, [href], [tabindex]:not([tabindex="-1"])'),
                n => !n.disabled && n.offsetParent !== null
            );
            if (!items.length) { e.preventDefault(); refs.dialog.focus(); return; }
            const first = items[0], last = items[items.length - 1];
            if (e.shiftKey && (document.activeElement === first || document.activeElement === refs.dialog)) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        });
        document.addEventListener('keydown', e => {
            if (dlg.open && e.key === 'Escape') { e.preventDefault(); closeDialog(); }
        });

        /* ----- real-time behaviour ----- */
        function checkToday() {
            const t = todayIn(tz);
            if (t && t !== today) { today = t; render(); }
        }
        setInterval(checkToday, 60000);
        if (pollMs) {
            setInterval(() => { if (!document.hidden) load(true); }, pollMs);
        }
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) { checkToday(); load(true); }
        });
        window.addEventListener('online', () => load(true));

        /* ----- go ----- */
        render();
        load(false);
    }

    function boot() {
        document.querySelectorAll('[data-schedule-calendar]').forEach(root => {
            try { initCalendar(root); }
            catch (err) { if (window.console) console.error('Schedule calendar failed to start:', err); }
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();