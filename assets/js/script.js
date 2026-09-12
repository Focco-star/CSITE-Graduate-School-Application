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

    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            const msg = el.dataset.confirm || 'Are you sure you want to proceed?';
            if (!confirm(msg)) e.preventDefault();
        });
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
        parent.addEventListener('click', () => {
            const submenu = parent.nextElementSibling;
            const chevron = parent.querySelector('.nav-chevron');
            submenu?.classList.toggle('open');
            chevron?.classList.toggle('open');
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
                if (ok) visible += 1;
            });
            if (empty) {
                empty.hidden = visible > 0;
                empty.querySelector('td') && (empty.style.display = visible > 0 ? 'none' : '');
            }
            if (countEl) {
                countEl.textContent = '(' + visible + ' result' + (visible === 1 ? '' : 's') + ')';
            }
            if (clearBtn) clearBtn.hidden = !q;
        }

        search?.addEventListener('input', apply);
        search?.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                search.value = '';
                apply();
            }
        });
        clearBtn?.addEventListener('click', () => {
            search.value = '';
            apply();
        });
        selects.forEach(sel => sel.addEventListener('change', apply));
        apply();
    });

})();
