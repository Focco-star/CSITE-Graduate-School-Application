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

})();