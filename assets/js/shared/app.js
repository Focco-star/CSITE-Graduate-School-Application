// Starts an immediately-invoked function so this page's variables stay private.
(function () {
    // Turns on strict mode so careless JavaScript mistakes become visible errors.
    'use strict';

    // Finds the sidebar element by id so it can be opened and closed.
    const sidebar = document.getElementById('sidebar');
    // Finds the dark overlay that sits behind the sidebar.
    const overlay = document.getElementById('sidebarOverlay');
    // Finds the hamburger button that toggles the sidebar.
    const toggleBtn = document.getElementById('sidebarToggle');

    // Defines openSidebar, the function that shows the mobile sidebar.
    function openSidebar() {
        // Adds the open class so CSS slides the sidebar into view.
        sidebar?.classList.add('open');
        // Adds the active class so the overlay becomes visible.
        overlay?.classList.add('active');
        // Locks page scrolling while the sidebar is open.
        document.body.style.overflow = 'hidden';
    // Closes the openSidebar function.
    }

    // Defines closeSidebar, the function that hides the sidebar again.
    function closeSidebar() {
        // Removes the open class so the sidebar slides away.
        sidebar?.classList.remove('open');
        // Removes the active class so the overlay fades out.
        overlay?.classList.remove('active');
        // Gives page scrolling back to the user.
        document.body.style.overflow = '';
    // Closes the closeSidebar function.
    }

    // Listens for a click on the hamburger button.
    toggleBtn?.addEventListener('click', () => {
        // Closes the sidebar if it is open, otherwise opens it.
        sidebar?.classList.contains('open') ? closeSidebar() : openSidebar();
    // Closes the sidebar toggle click handler.
    });

    // Closes the sidebar when the dark overlay is clicked.
    overlay?.addEventListener('click', closeSidebar);

    // Loops over every tab group on the page.
    document.querySelectorAll('.tab-list').forEach(tabList => {
        // Collects the tab buttons inside this group.
        const buttons = tabList.querySelectorAll('.tab-btn');
        // Finds the tabs container, or the parent element as a fallback.
        const container = tabList.closest('.tabs') || tabList.parentElement;
        // Collects the tab panels that can be shown or hidden.
        const panels = container.querySelectorAll('.tab-panel');

        // Loops over each tab button in this group.
        buttons.forEach(btn => {
            // Listens for a click on this tab button.
            btn.addEventListener('click', () => {
                // Reads which panel id this button controls from data-tab.
                const target = btn.dataset.tab;
                // Removes the active look from every tab button.
                buttons.forEach(b => b.classList.remove('active'));
                // Hides every tab panel in the group.
                panels.forEach(p => p.classList.remove('active'));
                // Marks the clicked button as the active tab.
                btn.classList.add('active');
                // Finds the matching panel by id and marks it active.
                container.querySelector(`#${target}`)?.classList.add('active');
            // Closes this tab button click handler.
            });
        // Closes the loop over the tab buttons.
        });
    // Closes the loop over the tab groups.
    });

    // Loops over every element that opens a modal.
    document.querySelectorAll('[data-modal-open]').forEach(trigger => {
        // Listens for a click on the trigger.
        trigger.addEventListener('click', e => {
            // Stops the browser's default link or button action.
            e.preventDefault();
            // Reads the modal id from data-modal-open.
            const id = trigger.dataset.modalOpen;
            // Finds that modal and adds the active class to show it.
            document.getElementById(id)?.classList.add('active');
        // Closes the modal-open click handler.
        });
    // Closes the loop over modal triggers.
    });

    // Loops over every element that closes a modal.
    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        // Listens for a click on the close button.
        btn.addEventListener('click', () => {
            // Finds the parent modal and removes its active class.
            btn.closest('.modal-overlay')?.classList.remove('active');
        // Closes the modal-close click handler.
        });
    // Closes the loop over close buttons.
    });

    // Loops over every modal backdrop element.
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        // Listens for a click on the backdrop.
        overlay.addEventListener('click', e => {
            // Only closes when the backdrop itself was clicked, not its content.
            if (e.target === overlay) overlay.classList.remove('active');
        // Closes the backdrop click handler.
        });
    // Closes the loop over modal backdrops.
    });

    // Finds the shared confirmation modal element.
    const confirmModal = document.getElementById('globalConfirmModal');
    // Finds the paragraph that shows the confirmation question.
    const confirmMessage = document.getElementById('globalConfirmMessage');
    // Finds the Accept button inside the confirmation modal.
    const confirmAccept = document.getElementById('globalConfirmAccept');
    // Stores the action to run later, or null when nothing is pending.
    let confirmAction = null;
    // Defines openConfirm, which asks the user before running an action.
    function openConfirm(message, action) {
        // Shows the given question, or a default one, in the modal.
        confirmMessage.textContent = message || 'Are you sure you want to continue?';
        // Remembers the callback so the Accept button can run it.
        confirmAction = action;
        // Shows the confirmation modal.
        confirmModal?.classList.add('active');
    // Closes the openConfirm function.
    }
    // Loops over buttons that confirm before visiting a URL.
    document.querySelectorAll('[data-confirm-url]').forEach(button => {
        // On click, asks first, then sends the browser to the data URL.
        button.addEventListener('click', () => openConfirm(button.dataset.confirmMessage, () => { window.location.href = button.dataset.confirmUrl; }));
    // Closes the confirm-URL click handler.
    });
    // Loops over submit buttons inside forms that need confirmation.
    document.querySelectorAll('[data-confirm-form] [data-confirm-trigger]').forEach(button => {
        // Listens for a click on the confirm trigger.
        button.addEventListener('click', () => {
            // Finds the form that contains this button.
            const form = button.closest('form');
            // Asks first, then submits that form when the user accepts.
            openConfirm(form?.dataset.confirmMessage, () => form?.submit());
        // Closes the confirm-trigger click handler.
        });
    // Closes the loop over form confirm triggers.
    });
    // Listens for a click on the modal's Accept button.
    confirmAccept?.addEventListener('click', () => {
        // Copies the stored action before clearing the slot.
        const action = confirmAction;
        // Forgets the pending action now that it is running.
        confirmAction = null;
        // Hides the confirmation modal.
        confirmModal?.classList.remove('active');
        // Runs the stored action only if one exists.
        action?.();
    // Closes the Accept button click handler.
    });

    // Loops over every drag-and-drop file upload area.
    document.querySelectorAll('.file-upload-area').forEach(area => {
        // Finds the hidden file input inside this area.
        const input = area.querySelector('input[type="file"]');
        // Finds the text element that shows the chosen file name.
        const nameDisplay = area.querySelector('.file-name');

        // Clicking anywhere in the area opens the file picker.
        area.addEventListener('click', () => input?.click());

        // Listens for files being dragged over the area.
        area.addEventListener('dragover', e => {
            // Stops the browser from opening the dragged file.
            e.preventDefault();
            // Adds the dragover class for highlight styling.
            area.classList.add('dragover');
        // Closes the dragover handler.
        });

        // Removes the highlight when the file leaves the area.
        area.addEventListener('dragleave', () => area.classList.remove('dragover'));

        // Listens for a file being dropped on the area.
        area.addEventListener('drop', e => {
            // Stops the browser's default drop behaviour.
            e.preventDefault();
            // Removes the dragover highlight.
            area.classList.remove('dragover');
            // Checks that an input exists and at least one file was dropped.
            if (input && e.dataTransfer.files.length) {
                // Copies the dropped files into the hidden input.
                input.files = e.dataTransfer.files;
                // Shows the dropped file name in the area.
                showFileName(input, nameDisplay);
            // Closes the dropped-files check.
            }
        // Closes the drop event handler.
        });

        // Listens for a file being chosen with the picker.
        input?.addEventListener('change', () => {
            // Only checks the file when one was actually selected.
            if (input.files[0]) {
                // Stores the selected file for easy access.
                const file = input.files[0];
                // Reads the file extension in lowercase.
                const ext = (file.name.split('.').pop() || '').toLowerCase();
                // Checks it against the allowed PDF, DOC and DOCX types.
                if (!['pdf', 'doc', 'docx'].includes(ext)) {
                    // Clears the input so the bad file is not submitted.
                    input.value = '';
                    // Tells the user only PDF, DOC or DOCX files are allowed.
                    if (nameDisplay) nameDisplay.textContent = 'Only PDF, DOC, or DOCX files are allowed.';
                    // Stops the rest of the handler for a bad file.
                    return;
                // Closes the file-type check.
                }
                // Checks whether the file is larger than 20 MB.
                if (file.size > 20 * 1024 * 1024) {
                    // Clears the input so the big file is not submitted.
                    input.value = '';
                    // Tells the user the file is too large.
                    if (nameDisplay) nameDisplay.textContent = 'File exceeds the 20 MB maximum size.';
                    // Stops the rest of the handler for a big file.
                    return;
                // Closes the size check.
                }
            // Closes the files-selected check.
            }
            // Shows the accepted file's name under the upload area.
            showFileName(input, nameDisplay);
        // Closes the file change handler.
        });
    // Closes the loop over upload areas.
    });

    // Defines showFileName, which prints the chosen file name.
    function showFileName(input, display) {
        // Only updates when a display element and a file exist.
        if (display && input.files.length) {
            // Writes the first selected file's name into the display.
            display.textContent = input.files[0].name;
        // Closes the display check.
        }
    // Closes the showFileName function.
    }

    // Loops over every form that asks for client-side validation.
    document.querySelectorAll('form[data-validate]').forEach(form => {
        // Listens for the form being submitted.
        form.addEventListener('submit', e => {
            // Starts by assuming the form is valid.
            let valid = true;
            // Loops over every field marked required.
            form.querySelectorAll('[required]').forEach(field => {
                // Finds the field's wrapper, or uses its parent.
                const group = field.closest('.form-field') || field.parentElement;
                // Removes any old error styling from the wrapper.
                group?.classList.remove('has-error');
                // Removes any old required-field error message.
                group?.querySelector('.field-error')?.remove();

                // Checks whether the required field is empty.
                if (!field.value.trim()) {
                    // Marks the whole form as invalid.
                    valid = false;
                    // Adds the error styling to the wrapper.
                    group?.classList.add('has-error');
                    // Creates the element for the error message.
                    const err = document.createElement('div');
                    // Gives the message its styling class.
                    err.className = 'field-error';
                    // Sets the error text the user will read.
                    err.textContent = 'This field is required.';
                    // Adds the message under the field.
                    group?.appendChild(err);
                // Closes the empty-field check.
                }
            // Closes the loop over required fields.
            });

            // Loops over every email field in the form.
            form.querySelectorAll('[type="email"]').forEach(field => {
                // Checks the email with a simple pattern when it is not empty.
                if (field.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
                    // Marks the form invalid for a badly formed email.
                    valid = false;
                    // Finds the email field's wrapper.
                    const group = field.closest('.form-field');
                    // Adds the error styling to that wrapper.
                    group?.classList.add('has-error');
                // Closes the email condition.
                }
            // Closes the loop over email fields.
            });

            // Stops the submit so the server never sees invalid data.
            if (!valid) e.preventDefault();
        // Closes the form submit handler.
        });
    // Closes the loop over validated forms.
    });

    // Loops over alerts that should disappear on their own.
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
        // Starts a five-second wait before fading the alert.
        setTimeout(() => {
            // Makes opacity changes fade smoothly.
            alert.style.transition = 'opacity 0.3s';
            // Fades the alert out.
            alert.style.opacity = '0';
            // Removes the alert from the page after the fade.
            setTimeout(() => alert.remove(), 300);
        // Sets the five-second delay for the fade.
        }, 5000);
    // Closes the alert auto-dismiss loop.
    });

    // Loops over every button that prints the page.
    document.querySelectorAll('[data-print]').forEach(btn => {
        // Opens the browser print dialog on click.
        btn.addEventListener('click', () => window.print());
    // Closes the print button loop.
    });

    // Loops over buttons that show or hide a password.
    document.querySelectorAll('[data-toggle-password]').forEach(btn => {
        // Listens for a click on the eye button.
        btn.addEventListener('click', () => {
            // Finds the password input named in data-toggle-password.
            const input = document.getElementById(btn.dataset.togglePassword);
            // Stops if that input does not exist.
            if (!input) return;
            // True when the field is currently hidden.
            const show = input.type === 'password';
            // Switches the input between password and plain text.
            input.type = show ? 'text' : 'password';
            // Shows the open-eye icon when the password is hidden.
            btn.querySelector('i')?.classList.toggle('fa-eye', !show);
            // Shows the crossed-eye icon when the password is visible.
            btn.querySelector('i')?.classList.toggle('fa-eye-slash', show);
        // Closes the eye button click handler.
        });
    // Closes the password toggle loop.
    });

    // Loops over sidebar parents that expand a submenu.
    document.querySelectorAll('[data-submenu-toggle]').forEach(parent => {
        // Defines toggleSubmenu, which flips the submenu open or closed.
        const toggleSubmenu = () => {
            // Finds the submenu that follows this parent in the markup.
            const submenu = parent.nextElementSibling;
            // Stops if there is no submenu to open.
            if (!submenu) return;
            // Toggles the open class and records the new state.
            const isOpen = submenu.classList.toggle('open');
            // Spins the little chevron arrow to match the state.
            parent.querySelector('.nav-chevron')?.classList.toggle('open', isOpen);
            // Heads the MyADZU theme state-class handling below.
            // MyADZU theme state classes (root + nested levels)
            // Checks for the MyADZU theme's root or nested toggle classes.
            if (parent.classList.contains('myadzu-root') || parent.classList.contains('myadzu-sub-toggle')) {
                // Uses the theme's is-open class for those parents.
                parent.classList.toggle('is-open', isOpen);
            // Otherwise uses the normal sidebar styling.
            } else {
                // Toggles the standard active class instead.
                parent.classList.toggle('active', isOpen);
            // Closes the theme-versus-normal branch.
            }
            // Checks whether the parent reports its expanded state.
            if (parent.hasAttribute('aria-expanded')) {
                // Keeps aria-expanded in sync for screen readers.
                parent.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            // Closes the aria-expanded check.
            }
        // Closes the toggleSubmenu function.
        };
        // Runs the toggle when the parent is clicked.
        parent.addEventListener('click', toggleSubmenu);
        // Heads the keyboard handling that also opens submenus.
        // Keyboard accessibility: Enter / Space toggles div[role="button"]
        // Adds keyboard support for parents that are not real buttons.
        parent.addEventListener('keydown', e => {
            // Handles Enter and Space like a button press.
            if (e.key === 'Enter' || e.key === ' ') {
                // Stops the page from scrolling on the Space key.
                e.preventDefault();
                // Runs the same toggle used by the mouse.
                toggleSubmenu();
            // Closes the key check.
            }
        // Closes the keydown handler.
        });
    // Closes the submenu toggle loop.
    });

    // Collects the background slides of the homepage slideshow.
    const slides = document.querySelectorAll('.slideshow-bg .slide');
    // Only starts the slideshow when there are two or more slides.
    if (slides.length > 1) {
        // Remembers which slide is showing now.
        let current = 0;
        // Starts a timer that advances the slideshow.
        setInterval(() => {
            // Hides the slide that is currently active.
            slides[current].classList.remove('active');
            // Moves to the next slide and wraps back to the first.
            current = (current + 1) % slides.length;
            // Shows the new active slide.
            slides[current].classList.add('active');
        // Sets the slideshow delay to 20 seconds.
        }, 20000);
    // Closes the slideshow setup block.
    }

    // Finds the button that opens the program dropdown.
    const programToggle = document.getElementById('programToggle');
    // Finds the list of program options.
    const programList = document.getElementById('programList');
    // Finds the hidden input that stores the chosen program code.
    const programValue = document.getElementById('programValue');
    // Finds the label that shows the chosen program name.
    const programLabel = document.getElementById('programLabel');
    // Finds the box that shows the workflow hint for a track.
    const trackHint = document.getElementById('trackHint');
    // Finds the wrapper around the track selector.
    const trackGroup = document.getElementById('trackGroup');
    // Finds the track dropdown that students pick from.
    const trackSelect = document.getElementById('trackSelect');
    // Finds the hint text shown under the track dropdown.
    const trackChoiceHint = document.getElementById('trackChoiceHint');
    // Reads the track workflow hints that PHP passed to the page.
    const hints = window.CSITE_TRACK_HINTS || {};
    // Reads the friendly track labels that PHP passed to the page.
    const trackLabels = window.CSITE_TRACK_LABELS || {};

    // Heads the note about how many tracks a course allows.
    // The tracks a course can follow. A course that can be followed in more
    // Continues the note about single-track and multi-track courses.
    // than one way lets the student choose; a course with one track is set
    // Ends the note about choosing a track.
    // for them and locked.
    // Defines fillTracks, which fills the track dropdown for a program.
    function fillTracks(tracks) {
        // Stops if this page has no track dropdown.
        if (!trackSelect) return;
        // Finds the form the track dropdown belongs to.
        const form = trackSelect.form;
        // Looks for an existing hidden input that locked the track.
        let lockedField = form ? form.querySelector('input[type="hidden"][data-track-lock]') : null;
        // Splits the comma-separated track codes into a clean list.
        const list = String(tracks || '').split(',').map(function (t) { return t.trim(); }).filter(Boolean);

        // Re-enables the dropdown before rebuilding its options.
        trackSelect.disabled = false;
        // Checks whether an old locked value is still present.
        if (lockedField) {
            // Removes that old hidden input.
            lockedField.remove();
            // Clears the reference to the removed input.
            lockedField = null;
        // Closes the old locked-field cleanup.
        }

        // Starts the dropdown with a placeholder option.
        trackSelect.innerHTML = '<option value="">Select your track</option>'
            // Maps each track code to an option element.
            + list.map(function (t) {
                // Builds one option using the friendly label when available.
                return '<option value="' + t + '">' + (trackLabels[t] || t) + '</option>';
            // Joins the option strings and puts them in the dropdown.
            }).join('');
        // Stops if there is no track group to show.
        if (!trackGroup) return;
        // Checks whether this program has no tracks at all.
        if (!list.length) {
            // Hides the whole track group when there are no tracks.
            trackGroup.style.display = 'none';
            // Stops early because there is nothing to show.
            return;
        // Closes the no-tracks check.
        }
        // Shows the track group when at least one track exists.
        trackGroup.style.display = 'block';
        // Checks whether the program has exactly one track.
        if (list.length === 1) {
            // Heads the note about courses with only one track.
            // A course with one track is set for the student and cannot be
            // Explains why the hidden field carries the locked track value.
            // changed. The hidden field carries the value because a disabled
            // Ends the note about the locked track field.
            // select is not part of the submitted form.
            // Selects that only track automatically.
            trackSelect.value = list[0];
            // Disables the dropdown so the student cannot change it.
            trackSelect.disabled = true;
            // Only adds the hidden input when the form was found.
            if (form) {
                // Creates a hidden input to carry the locked track value.
                lockedField = document.createElement('input');
                // Makes the new input a hidden field.
                lockedField.type = 'hidden';
                // Names it track so PHP receives it like the dropdown.
                lockedField.name = 'track';
                // Stores the single track code in the hidden field.
                lockedField.value = list[0];
                // Marks the field so it can be found and removed later.
                lockedField.setAttribute('data-track-lock', '');
                // Adds the hidden field to the form.
                form.appendChild(lockedField);
            // Closes the hidden-field block.
            }
            // Checks whether a track hint element exists.
            if (trackChoiceHint) {
                // Explains that the track was chosen automatically.
                trackChoiceHint.textContent = 'Your program follows the ' + (trackLabels[list[0]] || list[0]) + ' track. It has been selected for you.';
            // Closes the single-track hint block.
            }
        // Handles programs that allow more than one track.
        } else {
            // Checks whether a track hint element exists.
            if (trackChoiceHint) {
                // Tells the student to choose the track they are enrolled in.
                trackChoiceHint.textContent = 'Your program can be followed in more than one way. Choose the track you are enrolled in.';
            // Closes the multiple-track hint block.
            }
        // Closes the single-versus-multiple branch.
        }
    // Closes the fillTracks function.
    }

    // Heads the short note about the track workflow hint.
    // The workflow of a track, shown under the program.
    // Defines showTrackHint, which shows a track's workflow steps.
    function showTrackHint(track) {
        // Stops when there is no hint box or no hint for this track.
        if (!trackHint || !hints[track]) return;
        // Shows the hint box using a flex layout.
        trackHint.style.display = 'flex';
        // Puts the workflow description inside the hint box.
        trackHint.querySelector('span').textContent = hints[track];
    // Closes the showTrackHint function.
    }

    // Listens for a click on the program dropdown button.
    programToggle?.addEventListener('click', () => {
        // Opens or closes the program list.
        programList?.classList.toggle('open');
    // Closes the program toggle click handler.
    });

    // Loops over every program option in the list.
    programList?.querySelectorAll('.program-option').forEach(opt => {
        // Listens for a click on one program option.
        opt.addEventListener('click', () => {
            // Removes the selected look from every option.
            programList.querySelectorAll('.program-option').forEach(o => o.classList.remove('selected'));
            // Marks the clicked option as selected.
            opt.classList.add('selected');
            // Stores the program code in the hidden input.
            programValue.value = opt.dataset.code;
            // Shows the program name on the dropdown button.
            programLabel.textContent = opt.textContent.trim();
            // Styles the button as having a chosen value.
            programToggle.classList.add('has-value');
            // Closes the program list after a choice.
            programList.classList.remove('open');
            // Rebuilds the track dropdown for this program.
            fillTracks(opt.dataset.tracks);
            // Shows the workflow hint for the current track.
            showTrackHint(trackSelect ? trackSelect.value : '');
        // Closes the program option click handler.
        });
    // Closes the program option loop.
    });

    // Listens for the student changing the track dropdown.
    trackSelect?.addEventListener('change', function () {
        // Shows the workflow hint for the newly chosen track.
        showTrackHint(trackSelect.value);
    // Closes the track change handler.
    });

    // Loops over every filter bar that controls a table or list.
    document.querySelectorAll('[data-filter-table]').forEach(bar => {
        // Finds the container the filter bar applies to.
        const root = document.querySelector(bar.dataset.filterTable);
        // Stops if that container is not on the page.
        if (!root) return;
        // Finds the search text box.
        const search = bar.querySelector('[data-filter-q]');
        // Finds the button that clears all filters.
        const clearBtn = bar.querySelector('[data-filter-clear]');
        // Collects the dropdown filters as an array.
        const selects = [...bar.querySelectorAll('select[data-filter]')];
        // Finds the element that shows how many results are visible.
        const countEl = bar.querySelector('[data-filter-count]') || document.querySelector('[data-filter-count]');
        // Collects the filterable rows as an array.
        const rows = [...root.querySelectorAll('[data-search]')];
        // Finds the message shown when nothing matches.
        const empty = root.querySelector('[data-filter-empty]');

        // Defines apply, which hides rows that do not match the filters.
        function apply() {
            // Reads and lowercases the search text.
            const q = (search?.value || '').trim().toLowerCase();
            // Counts how many rows are visible.
            let visible = 0;
            // Loops over every filterable row.
            rows.forEach(row => {
                // A row passes the text search when it contains the search text.
                let ok = !q || (row.dataset.search || '').includes(q);
                // Loops over each dropdown filter.
                selects.forEach(sel => {
                    // Reads which data field this dropdown filters on.
                    const key = sel.dataset.filter;
                    // Reads the dropdown's chosen value.
                    const val = sel.value;
                    // Skips the dropdown when nothing is chosen.
                    if (!val) return;
                    // Checks for the special course filter.
                    if (key === 'course') {
                        // Heads the note about matching a course through its tracks.
                        // A course is matched through the tracks assigned to it:
                        // Explains that templates are stored per track, not per course.
                        // the template library is stored per track, not per
                        // Ends the note about the course-to-track filter.
                        // course, so its items carry the track code.
                        // Looks up which tracks belong to the chosen course.
                        const allowed = (window.CSITE_COURSE_TRACKS || {})[val] || [];
                        // Hides the row when its track is not one of them.
                        if (!allowed.includes((row.dataset.track || '').toLowerCase())) ok = false;
                    // Otherwise compares the row's matching data field.
                    } else if ((row.dataset[key] || '') !== val) {
                        // Hides the row when the values are different.
                        ok = false;
                    // Closes the dropdown comparison.
                    }
                // Closes the dropdown filter loop.
                });
                // Hides the row when it failed any filter.
                row.hidden = !ok;
                // Also sets inline display so hiding always works.
                row.style.display = ok ? '' : 'none';
                // Adds one to the visible count for matching rows.
                if (ok) visible += 1;
            // Closes the row loop.
            });

            // Heads the note about hiding cards whose items were all filtered out.
            // If items are nested inside cards (e.g. student template stage cards), toggle card visibility
            // Loops over cards that may hold several filterable items.
            root.querySelectorAll('.card').forEach(card => {
                // Collects the filterable items inside this card.
                const cardItems = card.querySelectorAll('[data-search]');
                // Only checks cards that actually contain items.
                if (cardItems.length > 0) {
                    // True when at least one item in the card is still visible.
                    const cardHasVisible = [...cardItems].some(item => !item.hidden && item.style.display !== 'none');
                    // Hides the card when all of its items were filtered out.
                    card.hidden = !cardHasVisible;
                    // Sets inline display to match so the card really hides.
                    card.style.display = cardHasVisible ? '' : 'none';
                // Closes the card check.
                }
            // Closes the card loop.
            });

            // Checks whether the no-results message exists.
            if (empty) {
                // Hides that message when at least one row is visible.
                empty.hidden = visible > 0;
                // Sets inline display so the message obeys the count.
                empty.style.display = visible > 0 ? 'none' : '';
                // Also clears the message row when there are results.
                empty.querySelector('td') && (empty.style.display = visible > 0 ? 'none' : '');
            // Closes the empty message block.
            }
            // Checks whether a result counter exists.
            if (countEl) {
                // Writes the count with correct singular or plural wording.
                countEl.textContent = '(' + visible + ' result' + (visible === 1 ? '' : 's') + ')';
            // Closes the counter block.
            }
            // Checks whether a clear button exists.
            if (clearBtn) {
                // True when a search text or any dropdown value is set.
                const hasActiveFilter = Boolean(q || selects.some(sel => sel.value));
                // Shows the clear button only when a filter is active.
                clearBtn.hidden = !hasActiveFilter;
            // Closes the clear button block.
            }
        // Closes the apply function.
        }

        // Re-applies the filters whenever the student types.
        search?.addEventListener('input', apply);
        // Listens for keys pressed in the search box.
        search?.addEventListener('keydown', e => {
            // Handles the Escape key by resetting the search.
            if (e.key === 'Escape') {
                // Clears the search text.
                search.value = '';
                // Resets every dropdown filter.
                selects.forEach(sel => { sel.value = ''; });
                // Re-applies the filters after the reset.
                apply();
            // Closes the Escape check.
            }
        // Closes the search keydown handler.
        });
        // Listens for a click on the clear button.
        clearBtn?.addEventListener('click', () => {
            // Clears the search text.
            if (search) search.value = '';
            // Resets every dropdown filter.
            selects.forEach(sel => { sel.value = ''; });
            // Re-applies the filters after clearing.
            apply();
        // Closes the clear button handler.
        });
        // Re-applies the filters whenever a dropdown changes.
        selects.forEach(sel => sel.addEventListener('change', apply));
        // Runs once at start so the count and message are correct.
        apply();
    // Closes the loop over filter bars.
    });

    // Opens the block comment that describes the success pop-up.
    /* ------------------------------------------------------------------
     * Success pop-up: animated giant check, auto-closes after 5 seconds,
     * can be dismissed early with the X (or Esc). Triggered by a hidden
     * <div id="successPopupData" data-title data-message [data-redirect]>.
     * ------------------------------------------------------------------ */
    // Defines showSuccessPopup, which builds and shows the success pop-up.
    function showSuccessPopup(opts) {
        // Uses the given duration, or five seconds by default.
        const duration = opts.duration || 5000;
        // Creates the full-screen overlay element.
        const overlay = document.createElement('div');
        // Gives the overlay its styling class.
        overlay.className = 'success-popup-overlay';
        // Tells assistive tech the overlay is an alert dialog.
        overlay.setAttribute('role', 'alertdialog');
        // Marks the dialog as modal while it is open.
        overlay.setAttribute('aria-modal', 'true');
        // Points the dialog's label at its own title element.
        overlay.setAttribute('aria-labelledby', 'successPopupTitle');
        // Starts building the pop-up markup string.
        overlay.innerHTML =
            // Opens the pop-up box with its styling class.
            '<div class="success-popup">' +
                // Adds the X close button with a times icon.
                '<button type="button" class="success-popup-close" aria-label="Close"><i class="fas fa-times"></i></button>' +
                // Opens the animated check wrapper.
                '<div class="success-check" aria-hidden="true">' +
                    // Starts the inline drawing.
                    '<svg viewBox="0 0 120 120">' +
                        // Draws the soft background circle.
                        '<circle class="sc-bg" cx="60" cy="60" r="54"/>' +
                        // Draws the pulsing circle behind the check.
                        '<circle class="sc-pulse" cx="60" cy="60" r="54"/>' +
                        // Draws the ring that fills around the check.
                        '<circle class="sc-ring" cx="60" cy="60" r="54" pathLength="100"/>' +
                        // Draws the tick path that animates into a check mark.
                        '<path class="sc-tick" d="M35 62 L53 80 L87 42" pathLength="100"/>' +
                    // Closes the inline drawing.
                    '</svg>' +
                // Closes the check wrapper div.
                '</div>' +
                // Adds the empty title heading, filled in later.
                '<h3 id="successPopupTitle"></h3>' +
                // Adds the empty message paragraph, filled in later.
                '<p></p>' +
                // Adds the thin timer bar that counts the auto-close time.
                '<div class="success-popup-timer"><span></span></div>' +
            // Closes the pop-up box markup.
            '</div>';

        // Fills the title, defaulting to the word Success.
        overlay.querySelector('h3').textContent = opts.title || 'Success';
        // Finds the message paragraph in the new pop-up.
        const msg = overlay.querySelector('p');
        // Shows the message when given, otherwise drops the paragraph.
        if (opts.message) msg.textContent = opts.message; else msg.remove();
        // Sets the timer bar animation to last the whole duration.
        overlay.querySelector('.success-popup-timer span').style.animationDuration = duration + 'ms';

        // Tracks whether the pop-up is already closing.
        let closed = false;
        // Holds the auto-close timer id.
        let timer = null;

        // Defines close, which safely removes the pop-up only once.
        function close() {
            // Stops if the pop-up is already closing.
            if (closed) return;
            // Marks the pop-up as closing now.
            closed = true;
            // Cancels the pending auto-close timer.
            clearTimeout(timer);
            // Removes the Escape key listener.
            document.removeEventListener('keydown', onKey);
            // Adds the closing class so CSS can fade it out.
            overlay.classList.add('closing');
            // Waits for the fade before removing the element.
            setTimeout(() => {
                // Removes the overlay from the page.
                overlay.remove();
                // Sends the browser to the next page when a redirect is set.
                if (opts.redirect) window.location.href = opts.redirect;
            // Sets the 220 millisecond fade wait.
            }, 220);
        // Closes the close function.
        }
        // Handles Escape so the pop-up can be dismissed early.
        function onKey(e) { if (e.key === 'Escape') close(); }

        // Finds the X close button inside the pop-up.
        const closeBtn = overlay.querySelector('.success-popup-close');
        // Closes the pop-up when the X is clicked.
        closeBtn.addEventListener('click', close);
        // Lets the Escape key close the pop-up.
        document.addEventListener('keydown', onKey);
        // Adds the pop-up to the page.
        document.body.appendChild(overlay);
        // Moves focus to the close button for keyboard users.
        closeBtn.focus({ preventScroll: true });
        // Starts the timer that closes the pop-up automatically.
        timer = setTimeout(close, duration);
    // Closes the showSuccessPopup function.
    }
    // Exposes the pop-up helper so other scripts can call it.
    window.showSuccessPopup = showSuccessPopup;

    // Finds the hidden element that carries the success message.
    const successData = document.getElementById('successPopupData');
    // Only shows a pop-up when that element exists.
    if (successData) {
        // Calls the pop-up helper with the data written by PHP.
        showSuccessPopup({
            // Uses the title stored in data-title.
            title: successData.dataset.title,
            // Uses the message stored in data-message.
            message: successData.dataset.message,
            // Uses the redirect link, or empty text when it is absent.
            redirect: successData.dataset.redirect || '',
            // Reads the custom duration, defaulting to 5000 milliseconds.
            duration: parseInt(successData.dataset.duration, 10) || 5000
        // Closes the options object passed to the helper.
        });
    // Closes the success element check.
    }

    // Opens the block comment that describes the date picker.
    /* ------------------------------------------------------------------
     * Editable date field + themed calendar (gray / yellow like the sidebar).
     * Markup: <div class="date-picker" data-date-picker><input type="text">
     *         <button data-date-toggle></button></div>
     * The value is always typed / stored as YYYY-MM-DD.
     * ------------------------------------------------------------------ */
    // Starts an IIFE that upgrades every date input on the page.
    (function initDatePickers() {
        // Opens the block comment that explains replacing native date inputs.
        /*
         * Native date inputs open a calendar that the browser draws outside
         * the page, so that calendar is missing when the tab is shared
         * (Teams, Zoom, Meet) or recorded. Give every one of them the same
         * in-page calendar the other dates already use. The field keeps its
         * name, id and YYYY-MM-DD value, so nothing about the form changes.
         */
        // Loops over every native date input on the page.
        document.querySelectorAll('input[type="date"]').forEach(input => {
            // Skips inputs that asked to keep the browser's own picker.
            if (input.dataset.keepNative !== undefined) return;

            // Creates the wrapper that will hold the input and button.
            const wrap = document.createElement('div');
            // Gives the wrapper the date-picker styling class.
            wrap.className = 'date-picker';
            // Marks the wrapper so it can be found later.
            wrap.setAttribute('data-date-picker', '');
            // Inserts the wrapper just before the input.
            input.parentNode.insertBefore(wrap, input);
            // Moves the input inside its new wrapper.
            wrap.appendChild(input);

            // Changes the input to a plain text field.
            input.type = 'text';
            // Shows the expected YYYY-MM-DD format as a placeholder.
            input.setAttribute('placeholder', 'YYYY-MM-DD');
            // Asks phones to show a number-friendly keyboard.
            input.setAttribute('inputmode', 'numeric');
            // Stops the browser from autofilling an old date.
            input.setAttribute('autocomplete', 'off');
            // Limits typing to ten characters.
            input.setAttribute('maxlength', '10');
            // Adds a pattern that checks the date format.
            input.setAttribute('pattern', '\\d{4}-\\d{2}-\\d{2}');
            // Shows the format hint when the field is hovered.
            input.setAttribute('title', 'Use the format YYYY-MM-DD');

            // Creates the calendar button that opens the picker.
            const btn = document.createElement('button');
            // Keeps the button from submitting the form.
            btn.type = 'button';
            // Gives the button the date picker styling class.
            btn.className = 'date-picker-btn';
            // Marks the button as the calendar toggle.
            btn.setAttribute('data-date-toggle', '');
            // Gives the button a readable label for screen readers.
            btn.setAttribute('aria-label', 'Open calendar');
            // Shows the same hint as a tooltip.
            btn.setAttribute('title', 'Open calendar');
            // Puts a calendar icon inside the button.
            btn.innerHTML = '<i class="fas fa-calendar-alt"></i>';
            // Adds the button inside the wrapper.
            wrap.appendChild(btn);
        // Closes the loop over date inputs.
        });

        // Collects every date picker wrapper on the page.
        const wrappers = document.querySelectorAll('[data-date-picker]');
        // Stops when the page has no date pickers.
        if (!wrappers.length) return;

        // Lists the month names used by the calendar header.
        const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        // Lists the two-letter weekday headings.
        const DAYS = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
        // Helper that pads a number to two digits.
        const pad = n => String(n).padStart(2, '0');
        // Converts a Date object into a YYYY-MM-DD string.
        const toISO = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());

        // Defines parseISO, which turns YYYY-MM-DD text into a Date.
        function parseISO(s) {
            // Matches the text against the YYYY-MM-DD pattern.
            const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec((s || '').trim());
            // Returns nothing when the text is not in that shape.
            if (!m) return null;
            // Reads the year, zero-based month and day as numbers.
            const y = +m[1], mo = +m[2] - 1, d = +m[3];
            // Builds a Date from those parts.
            const dt = new Date(y, mo, d);
            // Returns the date only when those parts make a real date.
            return (dt.getFullYear() === y && dt.getMonth() === mo && dt.getDate() === d) ? dt : null;
        // Closes the parseISO function.
        }

        // Creates the shared calendar popover element.
        const pop = document.createElement('div');
        // Gives the popover its styling class.
        pop.className = 'dp-popover';
        // Tells assistive tech the popover is a dialog.
        pop.setAttribute('role', 'dialog');
        // Gives the dialog a readable name.
        pop.setAttribute('aria-label', 'Choose a date');
        // Starts building the calendar markup string.
        pop.innerHTML =
            // Opens the header row.
            '<div class="dp-head">' +
                // Adds the previous month arrow button.
                '<button type="button" class="dp-nav" data-dp-prev aria-label="Previous month"><i class="fas fa-chevron-left"></i></button>' +
                // Adds the empty month dropdown.
                '<select data-dp-month aria-label="Month"></select>' +
                // Adds the empty year dropdown.
                '<select data-dp-year aria-label="Year"></select>' +
                // Adds the next month arrow button.
                '<button type="button" class="dp-nav" data-dp-next aria-label="Next month"><i class="fas fa-chevron-right"></i></button>' +
            // Closes the calendar header.
            '</div>' +
            // Adds the weekday heading row.
            '<div class="dp-week"></div>' +
            // Adds the empty grid that will hold the days.
            '<div class="dp-grid"></div>' +
            // Adds the Today and Clear buttons at the bottom.
            '<div class="dp-foot"><button type="button" data-dp-today>Today</button><button type="button" data-dp-clear>Clear</button></div>';
        // Puts the calendar popover on the page.
        document.body.appendChild(pop);

        // Finds the month dropdown inside the popover.
        const monthSel = pop.querySelector('[data-dp-month]');
        // Finds the year dropdown inside the popover.
        const yearSel = pop.querySelector('[data-dp-year]');
        // Finds the day grid inside the popover.
        const grid = pop.querySelector('.dp-grid');
        // Fills the weekday row with Su through Sa.
        pop.querySelector('.dp-week').innerHTML = DAYS.map(d => '<span>' + d + '</span>').join('');
        // Fills the month dropdown with January through December.
        monthSel.innerHTML = MONTHS.map((m, i) => '<option value="' + i + '">' + m + '</option>').join('');

        // Remembers which input and button currently own the calendar.
        let active = null;   // { input, btn }
        // Holds the year and month the calendar is viewing.
        let viewY = 0, viewM = 0;

        // Defines fillYears, which rebuilds the year dropdown.
        function fillYears(selectedYear) {
            // Reads the current year from the clock.
            const now = new Date().getFullYear();
            // Starts 40 years back, or earlier if the chosen year is older.
            const from = Math.min(now - 40, selectedYear);
            // Ends 10 years ahead, or later if the chosen year is newer.
            const to = Math.max(now + 10, selectedYear);
            // Starts with an empty option string.
            let html = '';
            // Adds one option per year, newest first.
            for (let y = to; y >= from; y--) html += '<option value="' + y + '">' + y + '</option>';
            // Puts the year options into the dropdown.
            yearSel.innerHTML = html;
        // Closes the fillYears function.
        }

        // Defines render, which draws the calendar for the current month.
        function render() {
            // Stops when no date field is active.
            if (!active) return;
            // Refreshes the year dropdown so it includes the view year.
            fillYears(viewY);
            // Selects the month being viewed.
            monthSel.value = String(viewM);
            // Selects the year being viewed.
            yearSel.value = String(viewY);

            // Reads the date already typed in the field, if valid.
            const selected = parseISO(active.input.value);
            // Builds today's date in YYYY-MM-DD form.
            const todayISO = toISO(new Date());
            // Builds the selected date in the same form.
            const selectedISO = selected ? toISO(selected) : '';
            // Finds the first day of the month being viewed.
            const first = new Date(viewY, viewM, 1);
            // Starts the grid on the Sunday on or before that day.
            const start = new Date(viewY, viewM, 1 - first.getDay());

            // Starts with an empty string for the day buttons.
            let html = '';
            // Loops 42 times to fill six weeks of boxes.
            for (let i = 0; i < 42; i++) {
                // Builds the date for this grid cell.
                const d = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
                // Converts that date to YYYY-MM-DD.
                const iso = toISO(d);
                // Starts the class list for the day button.
                const cls = ['dp-day'];
                // Marks days that belong to the next or previous month.
                if (d.getMonth() !== viewM) cls.push('other');
                // Marks today's date.
                if (iso === todayISO) cls.push('today');
                // Marks the date already chosen in the field.
                if (iso === selectedISO) cls.push('selected');
                // Adds the day button with its classes and date value.
                html += '<button type="button" class="' + cls.join(' ') + '" data-date="' + iso + '">' + d.getDate() + '</button>';
            // Closes the six-week loop.
            }
            // Puts all the day buttons into the grid.
            grid.innerHTML = html;
        // Closes the render function.
        }

        // Defines position, which places the popover near the field.
        function position() {
            // Stops when no date field is active.
            if (!active) return;
            // Measures where the active field sits on screen.
            const r = active.input.getBoundingClientRect();
            // Measures the popover's width and height.
            const w = pop.offsetWidth, h = pop.offsetHeight;
            // Keeps the popover inside the window on the left side.
            let left = Math.max(8, Math.min(r.left, window.innerWidth - w - 8));
            // Tries to place the popover just below the field.
            let top = r.bottom + 6;
            // Flips the popover above the field when there is no room.
            if (top + h > window.innerHeight - 8 && r.top - h - 6 > 8) top = r.top - h - 6;
            // Sets the popover's left position.
            pop.style.left = left + 'px';
            // Sets the popover's top position.
            pop.style.top = top + 'px';
        // Closes the position function.
        }

        // Defines open, which shows the calendar for one field.
        function open(input, btn) {
            // Tells the previously active button it is collapsed.
            if (active) active.btn.setAttribute('aria-expanded', 'false');
            // Remembers the new active input and button.
            active = { input, btn };
            // Uses the field's date, or today, as the starting month.
            const base = parseISO(input.value) || new Date();
            // Sets the calendar year from that date.
            viewY = base.getFullYear();
            // Sets the calendar month from that date.
            viewM = base.getMonth();
            // Tells assistive tech the button is expanded.
            btn.setAttribute('aria-expanded', 'true');
            // Shows the popover with the open class.
            pop.classList.add('open');
            // Draws the days for that month.
            render();
            // Places the popover next to the field.
            position();
        // Closes the open function.
        }

        // Defines close, which hides the calendar popover.
        function close() {
            // Stops when no date field is active.
            if (!active) return;
            // Tells the button it is collapsed again.
            active.btn.setAttribute('aria-expanded', 'false');
            // Hides the popover.
            pop.classList.remove('open');
            // Forgets the active field.
            active = null;
        // Closes the close function.
        }

        // Defines commit, which writes the chosen date into the field.
        function commit(value) {
            // Stops when no date field is active.
            if (!active) return;
            // Stores the active input for convenience.
            const input = active.input;
            // Writes the new date value into the field.
            input.value = value;
            // Fires an input event so live listeners react.
            input.dispatchEvent(new Event('input', { bubbles: true }));
            // Fires a change event so existing handlers react.
            input.dispatchEvent(new Event('change', { bubbles: true }));
            // Hides the calendar after choosing.
            close();
            // Puts the cursor back in the field.
            input.focus();
        // Closes the commit function.
        }

        // Defines shiftMonth, which moves the calendar by whole months.
        function shiftMonth(delta) {
            // Builds the first day of the shifted month.
            const d = new Date(viewY, viewM + delta, 1);
            // Updates the view year, even across a year change.
            viewY = d.getFullYear();
            // Updates the view month, wrapping correctly.
            viewM = d.getMonth();
            // Redraws the calendar.
            render();
        // Closes the shiftMonth function.
        }

        // Moves one month back when the left arrow is clicked.
        pop.querySelector('[data-dp-prev]').addEventListener('click', () => shiftMonth(-1));
        // Moves one month forward when the right arrow is clicked.
        pop.querySelector('[data-dp-next]').addEventListener('click', () => shiftMonth(1));
        // Jumps to the month picked in the month dropdown.
        monthSel.addEventListener('change', () => { viewM = +monthSel.value; render(); });
        // Jumps to the year picked in the year dropdown.
        yearSel.addEventListener('change', () => { viewY = +yearSel.value; render(); });
        // Fills in today's date when Today is clicked.
        pop.querySelector('[data-dp-today]').addEventListener('click', () => commit(toISO(new Date())));
        // Empties the field when Clear is clicked.
        pop.querySelector('[data-dp-clear]').addEventListener('click', () => commit(''));
        // Listens for clicks inside the day grid.
        grid.addEventListener('click', e => {
            // Finds the day button that was clicked.
            const btn = e.target.closest('[data-date]');
            // Writes that day's date into the field.
            if (btn) commit(btn.dataset.date);
        // Closes the day grid click handler.
        });

        // Listens for mouse presses anywhere on the page.
        document.addEventListener('mousedown', e => {
            // Does nothing when no calendar is open.
            if (!active) return;
            // Keeps the calendar open for clicks on it or its field.
            if (pop.contains(e.target) || active.btn.contains(e.target) || active.input.contains(e.target)) return;
            // Closes the calendar for any other click.
            close();
        // Closes the outside click handler.
        });
        // Listens for keys pressed on the page.
        document.addEventListener('keydown', e => {
            // Closes the calendar with Escape and refocuses the field.
            if (e.key === 'Escape' && active) { const i = active.input; close(); i.focus(); }
        // Closes the Escape handler.
        });
        // Keeps the popover beside its field when the window resizes.
        window.addEventListener('resize', position);
        // Keeps it in place while the page scrolls.
        window.addEventListener('scroll', position, true);

        // Loops over every date picker wrapper.
        wrappers.forEach(wrap => {
            // Finds the text input in the wrapper.
            const input = wrap.querySelector('input');
            // Finds the calendar button in the wrapper.
            const btn = wrap.querySelector('[data-date-toggle]');
            // Skips broken wrappers that lack one of them.
            if (!input || !btn) return;
            // Finds the form-field wrapper used for error styling.
            const group = wrap.closest('.form-field') || wrap.parentElement;
            // Tells assistive tech the button opens a dialog.
            btn.setAttribute('aria-haspopup', 'dialog');
            // Says the calendar starts closed.
            btn.setAttribute('aria-expanded', 'false');

            // Defines setError, which shows or clears a date error.
            function setError(message) {
                // Sets the browser's validity message, or clears it.
                input.setCustomValidity(message || '');
                // Adds or removes the error styling on the wrapper.
                group.classList.toggle('has-error', Boolean(message));
                // Removes any old date error message.
                group.querySelector('.date-error')?.remove();
                // Only shows a message when one was given.
                if (message) {
                    // Creates the error message element.
                    const err = document.createElement('div');
                    // Gives it the field error classes.
                    err.className = 'field-error date-error';
                    // Writes the message text.
                    err.textContent = message;
                    // Adds the message under the field.
                    group.appendChild(err);
                // Closes the message check.
                }
            // Closes the setError function.
            }

            // Defines check, which validates the typed date.
            function check(final) {
                // Reads the trimmed value of the field.
                const v = input.value.trim();
                // Clears the error when the field is empty or holds a real date.
                if (v === '' || parseISO(v)) { setError(''); return; }
                // Notes why the date error waits until ten characters are typed.
                // Only nag while typing once all 10 characters are in; always nag on blur
                // Shows the error on blur or once ten characters are typed.
                if (final || v.length === 10) setError('Enter a valid date as YYYY-MM-DD.');
                // Otherwise only flags the field without showing text yet.
                else input.setCustomValidity('Enter a valid date as YYYY-MM-DD.');
            // Closes the check function.
            }

            // Listens for a click on the calendar button.
            btn.addEventListener('click', () => {
                // Closes the calendar if open for this field, otherwise opens it.
                if (active && active.input === input) close(); else open(input, btn);
            // Closes the calendar button handler.
            });

            // Heads the code that inserts the date dashes while typing.
            // Auto-insert the dashes while typing digits
            // Listens for typing in the date field.
            input.addEventListener('input', e => {
                // True when the cursor is at the end of the text.
                const typingAtEnd = input.selectionStart === input.value.length;
                // Only auto-formats when typing forward, not deleting.
                if (typingAtEnd && !(e.inputType || '').startsWith('delete')) {
                    // Keeps only digits and limits them to eight.
                    const d = input.value.replace(/\D/g, '').slice(0, 8);
                    // Starts the output with the four-digit year.
                    let out = d.slice(0, 4);
                    // Adds a dash and the month digits once typed.
                    if (d.length > 4) out += '-' + d.slice(4, 6);
                    // Adds a second dash and the day digits once typed.
                    if (d.length > 6) out += '-' + d.slice(6, 8);
                    // Writes the formatted date back into the field.
                    input.value = out;
                // Closes the auto-format check.
                }
                // Re-checks the date without nagging while typing.
                check(false);
                // Checks whether this field owns the open calendar.
                if (active && active.input === input) {
                    // Reads the date typed so far.
                    const p = parseISO(input.value);
                    // Moves the calendar to that date's month when valid.
                    if (p) { viewY = p.getFullYear(); viewM = p.getMonth(); }
                    // Redraws the calendar with the new month.
                    render();
                // Closes the open-calendar check.
                }
            // Closes the date typing handler.
            });
            // Runs a full check when the student leaves the field.
            input.addEventListener('blur', () => check(true));
            // Runs an initial check when the page loads.
            check(false);
        // Closes the loop over date pickers.
        });
    // Closes and runs the date picker setup function.
    })();

    // Opens the block comment that describes the time picker.
    /* ------------------------------------------------------------------
     * Editable time field + in-page time list.
     *
     * The browser's own time picker is drawn outside the page, so it does not
     * appear in a shared tab. The value is still posted as HH:MM, exactly the
     * format the native input produced.
     * ------------------------------------------------------------------ */
    // Starts an IIFE that upgrades every time input on the page.
    (function initTimePickers() {
        // Collects every native time input.
        const inputs = document.querySelectorAll('input[type="time"]');
        // Stops when the page has no time inputs.
        if (!inputs.length) return;

        // Helper that pads a number to two digits.
        const pad = n => String(n).padStart(2, '0');

        // Creates the shared time popover element.
        const pop = document.createElement('div');
        // Gives the popover its styling class.
        pop.className = 'tp-popover';
        // Tells assistive tech the popover is a dialog.
        pop.setAttribute('role', 'dialog');
        // Gives the dialog a readable name.
        pop.setAttribute('aria-label', 'Choose a time');
        // Starts building the time picker markup string.
        pop.innerHTML =
            // Adds the popover title.
            '<p class="tp-title">Choose a time</p>' +
            // Opens the three-column layout.
            '<div class="tp-cols">' +
                // Adds the empty hour column.
                '<div class="tp-col" data-tp-hours aria-label="Hour"></div>' +
                // Adds the empty minute column.
                '<div class="tp-col" data-tp-minutes aria-label="Minute"></div>' +
                // Opens the AM/PM column.
                '<div class="tp-col tp-meridiem">' +
                    // Adds the AM button.
                    '<button type="button" class="tp-cell" data-tp-mer="AM">AM</button>' +
                    // Adds the PM button.
                    '<button type="button" class="tp-cell" data-tp-mer="PM">PM</button>' +
                // Closes the AM/PM column.
                '</div>' +
            // Closes the three-column layout.
            '</div>' +
            // Opens the footer row.
            '<div class="tp-foot">' +
                // Adds the Now button that jumps to the current time.
                '<button type="button" data-tp-now>Now</button>' +
                // Adds the Done button that closes the picker.
                '<button type="button" data-tp-done>Done</button>' +
            // Closes the footer and the markup string.
            '</div>';
        // Puts the time popover on the page.
        document.body.appendChild(pop);

        // Finds the hour column.
        const hourCol = pop.querySelector('[data-tp-hours]');
        // Finds the minute column.
        const minCol = pop.querySelector('[data-tp-minutes]');
        // Fills the hour column with buttons for 1 to 12.
        hourCol.innerHTML = Array.from({ length: 12 }, (_, i) => '<button type="button" class="tp-cell" data-hour="' + (i + 1) + '">' + (i + 1) + '</button>').join('');
        // Fills the minute column with buttons for 00 to 59.
        minCol.innerHTML = Array.from({ length: 60 }, (_, i) => '<button type="button" class="tp-cell" data-minute="' + i + '">' + pad(i) + '</button>').join('');

        // Remembers which input and button own the time picker.
        let active = null;           // { input, btn }
        // Starts the picker at 9:00 AM until a value is read.
        let hour12 = 9, minute = 0, isPm = false;

        // Defines parseTime, which reads an HH:MM value.
        function parseTime(value) {
            // Matches the text against an hours-and-minutes pattern.
            const m = /^(\d{1,2}):(\d{2})/.exec((value || '').trim());
            // Returns nothing when the text does not match.
            if (!m) return null;
            // Reads the hour and minute as numbers.
            const h = +m[1], mi = +m[2];
            // Rejects impossible hours or minutes.
            if (h > 23 || mi > 59) return null;
            // Returns the hour and minute in an object.
            return { h, mi };
        // Closes the parseTime function.
        }

        // Defines render, which highlights the chosen time.
        function render() {
            // Loops over the hour buttons.
            hourCol.querySelectorAll('[data-hour]').forEach(cell => {
                // Highlights the button matching the chosen hour.
                cell.classList.toggle('is-selected', +cell.dataset.hour === hour12);
            // Closes the hour loop.
            });
            // Loops over the minute buttons.
            minCol.querySelectorAll('[data-minute]').forEach(cell => {
                // Highlights the button matching the chosen minute.
                cell.classList.toggle('is-selected', +cell.dataset.minute === minute);
            // Closes the minute loop.
            });
            // Loops over the AM and PM buttons.
            pop.querySelectorAll('[data-tp-mer]').forEach(cell => {
                // Highlights AM or PM to match the current state.
                cell.classList.toggle('is-selected', (cell.dataset.tpMer === 'PM') === isPm);
            // Closes the AM/PM loop.
            });
            // Scrolls the chosen hour into view.
            hourCol.querySelector('.tp-cell.is-selected')?.scrollIntoView({ block: 'nearest' });
            // Scrolls the chosen minute into view.
            minCol.querySelector('.tp-cell.is-selected')?.scrollIntoView({ block: 'nearest' });
        // Closes the render function.
        }

        // Defines apply, which writes the chosen time into the field.
        function apply() {
            // Stops when no time field is active.
            if (!active) return;
            // Converts the 12-hour choice into a 24-hour value.
            const h24 = isPm ? (hour12 % 12) + 12 : (hour12 % 12);
            // Builds the HH:MM text that the form expects.
            const value = pad(h24) + ':' + pad(minute);
            // Stores the active input for convenience.
            const input = active.input;
            // Skips the update when the value has not changed.
            if (input.value === value) return;
            // Writes the time into the field.
            input.value = value;
            // Fires an input event so live listeners react.
            input.dispatchEvent(new Event('input', { bubbles: true }));
            // Fires a change event so existing handlers react.
            input.dispatchEvent(new Event('change', { bubbles: true }));
        // Closes the apply function.
        }

        // Defines position, which places the popover near the field.
        function position() {
            // Stops when no time field is active.
            if (!active) return;
            // Measures where the active field sits on screen.
            const r = active.input.getBoundingClientRect();
            // Measures the popover's width and height.
            const w = pop.offsetWidth, h = pop.offsetHeight;
            // Keeps the popover inside the window on the left side.
            const left = Math.max(8, Math.min(r.left, window.innerWidth - w - 8));
            // Tries to place the popover just below the field.
            let top = r.bottom + 6;
            // Flips the popover above the field when there is no room.
            if (top + h > window.innerHeight - 8 && r.top - h - 6 > 8) top = r.top - h - 6;
            // Sets the popover's left position.
            pop.style.left = left + 'px';
            // Sets the popover's top position, never above the window.
            pop.style.top = Math.max(8, top) + 'px';
        // Closes the position function.
        }

        // Defines syncFromInput, which reads the field into the picker.
        function syncFromInput(input) {
            // Parses the time already in the field.
            const parsed = parseTime(input.value);
            // Uses the field's time when it is valid.
            if (parsed) {
                // Converts the 24-hour hour into a 12-hour hour.
                const h12 = parsed.h % 12;
                // Shows 12 instead of 0 for midnight or noon.
                hour12 = h12 === 0 ? 12 : h12;
                // Copies the minute into the picker state.
                minute = parsed.mi;
                // Marks PM for hours from noon onward.
                isPm = parsed.h >= 12;
            // Falls back to the current time when the field is empty.
            } else {
                // Reads the current date and time.
                const now = new Date();
                // Converts the current hour into a 12-hour hour.
                const h12 = now.getHours() % 12;
                // Shows 12 instead of 0 for midnight or noon.
                hour12 = h12 === 0 ? 12 : h12;
                // Rounds the current minute to the nearest five.
                minute = Math.round(now.getMinutes() / 5) * 5 % 60;
                // Marks PM for afternoon and evening times.
                isPm = now.getHours() >= 12;
            // Closes the fallback branch.
            }
        // Closes the syncFromInput function.
        }

        // Defines open, which shows the time picker for one field.
        function open(input, btn) {
            // Tells the previously active button it is collapsed.
            if (active) active.btn.setAttribute('aria-expanded', 'false');
            // Remembers the new active input and button.
            active = { input, btn };
            // Loads the field's current time into the picker.
            syncFromInput(input);
            // Tells assistive tech the button is expanded.
            btn.setAttribute('aria-expanded', 'true');
            // Shows the popover with the open class.
            pop.classList.add('open');
            // Highlights the chosen hour, minute and AM/PM.
            render();
            // Places the popover next to the field.
            position();
        // Closes the open function.
        }

        // Defines close, which hides the time popover.
        function close() {
            // Stops when no time field is active.
            if (!active) return;
            // Tells the button it is collapsed again.
            active.btn.setAttribute('aria-expanded', 'false');
            // Hides the popover.
            pop.classList.remove('open');
            // Forgets the active field.
            active = null;
        // Closes the close function.
        }

        // Listens for clicks in the hour column.
        hourCol.addEventListener('click', e => {
            // Finds the hour button that was clicked.
            const cell = e.target.closest('[data-hour]');
            // Ignores clicks that missed a button.
            if (!cell) return;
            // Stores the chosen hour.
            hour12 = +cell.dataset.hour;
            // Redraws the highlight.
            render();
            // Writes the new time into the field.
            apply();
        // Closes the hour click handler.
        });
        // Listens for clicks in the minute column.
        minCol.addEventListener('click', e => {
            // Finds the minute button that was clicked.
            const cell = e.target.closest('[data-minute]');
            // Ignores clicks that missed a button.
            if (!cell) return;
            // Stores the chosen minute.
            minute = +cell.dataset.minute;
            // Redraws the highlight.
            render();
            // Writes the new time into the field.
            apply();
        // Closes the minute click handler.
        });
        // Loops over the AM and PM buttons.
        pop.querySelectorAll('[data-tp-mer]').forEach(cell => {
            // Listens for a click on each one.
            cell.addEventListener('click', () => {
                // Stores whether PM was chosen.
                isPm = cell.dataset.tpMer === 'PM';
                // Redraws the highlight.
                render();
                // Writes the new time into the field.
                apply();
            // Closes the AM/PM click handler.
            });
        // Closes the AM/PM loop.
        });
        // Listens for a click on the Now button.
        pop.querySelector('[data-tp-now]').addEventListener('click', () => {
            // Stops when no time field is active.
            if (!active) return;
            // Reads the current date and time.
            const now = new Date();
            // Converts the current hour into a 12-hour hour.
            const h12 = now.getHours() % 12;
            // Shows 12 instead of 0 for midnight or noon.
            hour12 = h12 === 0 ? 12 : h12;
            // Copies the current minute.
            minute = now.getMinutes();
            // Marks PM for afternoon and evening times.
            isPm = now.getHours() >= 12;
            // Redraws the highlight.
            render();
            // Writes the current time into the field.
            apply();
        // Closes the Now button handler.
        });
        // Listens for a click on the Done button.
        pop.querySelector('[data-tp-done]').addEventListener('click', () => {
            // Stops when no time field is active.
            if (!active) return;
            // Stores the active input before closing.
            const input = active.input;
            // Hides the popover.
            close();
            // Puts the cursor back in the field.
            input.focus();
        // Closes the Done button handler.
        });

        // Listens for mouse presses anywhere on the page.
        document.addEventListener('mousedown', e => {
            // Does nothing when no picker is open.
            if (!active) return;
            // Keeps the picker open for clicks on it or its button.
            if (pop.contains(e.target) || active.btn.contains(e.target)) return;
            // Closes the picker for any other click.
            close();
        // Closes the outside click handler.
        });
        // Listens for keys pressed on the page.
        document.addEventListener('keydown', e => {
            // Closes the picker with Escape and refocuses the field.
            if (e.key === 'Escape' && active) { const input = active.input; close(); input.focus(); }
        // Closes the Escape handler.
        });
        // Keeps the popover beside its field when the window resizes.
        window.addEventListener('resize', position);
        // Keeps it in place while the page scrolls.
        window.addEventListener('scroll', e => {
            // Repositions on scroll unless the scroll happened inside the picker.
            if (active && !pop.contains(e.target)) position();
        // Closes the scroll handler and its capture setting.
        }, true);

        // Loops over every time input on the page.
        inputs.forEach(input => {
            // Skips inputs that asked to keep the browser's own picker.
            if (input.dataset.keepNative !== undefined) return;

            // Creates the wrapper that will hold the input and button.
            const wrap = document.createElement('div');
            // Gives the wrapper the time-picker styling class.
            wrap.className = 'time-picker';
            // Inserts the wrapper just before the input.
            input.parentNode.insertBefore(wrap, input);
            // Moves the input inside its new wrapper.
            wrap.appendChild(input);

            // Changes the input to a plain text field.
            input.type = 'text';
            // Shows the expected HH:MM format as a placeholder.
            input.setAttribute('placeholder', 'HH:MM');
            // Asks phones to show a number-friendly keyboard.
            input.setAttribute('inputmode', 'numeric');
            // Stops the browser from autofilling an old time.
            input.setAttribute('autocomplete', 'off');
            // Limits typing to five characters.
            input.setAttribute('maxlength', '5');
            // Adds a pattern that checks a 24-hour HH:MM value.
            input.setAttribute('pattern', '([01][0-9]|2[0-3]):[0-5][0-9]');
            // Shows the format hint when the field is hovered.
            input.setAttribute('title', 'Use the 24-hour format HH:MM');

            // Creates the clock button that opens the picker.
            const btn = document.createElement('button');
            // Keeps the button from submitting the form.
            btn.type = 'button';
            // Gives the button the picker styling classes.
            btn.className = 'date-picker-btn time-picker-btn';
            // Tells assistive tech that the button opens a dialog.
            btn.setAttribute('aria-haspopup', 'dialog');
            // Says the picker starts closed.
            btn.setAttribute('aria-expanded', 'false');
            // Gives the button a readable label for screen readers.
            btn.setAttribute('aria-label', 'Open time picker');
            // Shows the same hint as a tooltip.
            btn.setAttribute('title', 'Open time picker');
            // Puts a clock icon inside the button.
            btn.innerHTML = '<i class="fas fa-clock"></i>';
            // Adds the button inside the wrapper.
            wrap.appendChild(btn);

            // Listens for a click on the clock button.
            btn.addEventListener('click', () => {
                // Closes the picker if open for this field, otherwise opens it.
                if (active && active.input === input) close(); else open(input, btn);
            // Closes the clock button handler.
            });

            // Notes that typing 0445 should become 04:45.
            // Type 0445 -> 04:45
            // Listens for typing in the time field.
            input.addEventListener('input', e => {
                // Leaves the text alone while the user is deleting.
                if ((e.inputType || '').startsWith('delete')) return;
                // Keeps only digits and limits them to four.
                const digits = input.value.replace(/\D/g, '').slice(0, 4);
                // Adds the colon automatically, so 0445 becomes 04:45.
                input.value = digits.length > 2 ? digits.slice(0, 2) + ':' + digits.slice(2) : digits;
            // Closes the time typing handler.
            });
        // Closes the loop over time inputs.
        });
    // Closes and runs the time picker setup function.
    })();

    // Starts the comment block for the in-page dropdown helper.
    /* ------------------------------------------------------------------
     * In-page dropdown menu for native <select> elements.
     *
     * The list of a native select is drawn by the browser outside the page,
     * so nobody watching a shared tab ever sees the options. This opens a
     * plain page list instead. The select itself keeps its name, value,
     * options, validation and change events: choosing an item is the same as
     * choosing it in the native list.
     * ------------------------------------------------------------------ */
    // Runs the select-menu helper immediately as a self-contained function.
    (function initSelectMenus() {
        // Collects every select element on the page into an array.
        const selects = Array.from(document.querySelectorAll('select'));
        // Stops early when the page has no dropdowns to improve.
        if (!selects.length) return;

        // Notes that touch devices keep the browser's own picker.
        // Touch devices keep the native control: its picker is the better
        // That picker suits touch better; this custom list targets desktop use.
        // experience there, and screen sharing is a desktop concern.
        // Leaves early when the device reports a coarse pointer, meaning touch input.
        if (window.matchMedia && window.matchMedia('(pointer: coarse)').matches) return;

        // Creates the floating div that will hold the custom option list.
        const menu = document.createElement('div');
        // Gives the list the CSS class that styles the custom menu.
        menu.className = 'cselect-menu';
        // Marks the list as a listbox so screen readers describe it correctly.
        menu.setAttribute('role', 'listbox');
        // Hides the list until a select is clicked.
        menu.hidden = true;
        // Adds the list to the page body so it can float above everything.
        document.body.appendChild(menu);

        // Tracks the open select, its option rows, and the highlighted index.
        let active = null;       // { select, items: [{ el, opt }], index }
        // Remembers the last pointer type so touch clicks can be filtered out.
        let lastPointerType = 'mouse';

        // Defines close(), which hides the list and clears its contents.
        function close(returnFocus) {
            // Does nothing when no select list is currently open.
            if (!active) return;
            // Saves the select element before the shared state is cleared.
            const select = active.select;
            // Hides the custom option list.
            menu.hidden = true;
            // Empties the option rows so nothing is left behind.
            menu.innerHTML = '';
            // Removes the dark theme class used on the login screens.
            menu.classList.remove('is-dark');
            // Forgets the open select, so the list counts as fully closed.
            active = null;
            // Optionally returns keyboard focus to the select it belonged to.
            if (returnFocus) select.focus({ preventScroll: true });
        // Closes the close() function.
        }

        // Defines position(), which places the list next to its select.
        function position() {
            // Stops when nothing is open, so there is nothing to position.
            if (!active) return;
            // Measures the select on screen to know where to draw the list.
            const r = active.select.getBoundingClientRect();
            // Makes the list at least as wide as the select, but never under 160px.
            menu.style.minWidth = Math.max(160, r.width) + 'px';
            // Starts the list at a safe left offset before measuring its height.
            menu.style.left = '8px';
            // Starts the list at a safe top offset before measuring its height.
            menu.style.top = '8px';
            // Shows the list so its height can be measured.
            menu.hidden = false;

            // Reads the list's full height to decide which direction to open.
            const h = menu.offsetHeight;
            // Calculates the space below the select, leaving a 10px margin.
            const below = window.innerHeight - r.bottom - 10;
            // Calculates the space above the select, leaving a 10px margin.
            const above = r.top - 10;
            // Opens upwards when little room is left below and more is above.
            // Open downwards unless there is almost no room and more above.
            // Stores that decision as openUp for the layout code below.
            const openUp = below < 180 && above > below;
            // Caps the list height at 340px and keeps at least 120px visible.
            const maxH = Math.min(340, Math.max(120, openUp ? above : below));
            // Adds or removes the is-up class so the list can style itself.
            menu.classList.toggle('is-up', openUp);
            // Applies the computed maximum height so a long list can scroll.
            menu.style.maxHeight = maxH + 'px';

            // Keeps the list inside the window by clamping its left position.
            const left = Math.max(8, Math.min(r.left, window.innerWidth - menu.offsetWidth - 8));
            // Chooses the top position: above the select or just below it.
            const top = openUp
                // When opening up, sits the list above the field and off the edge.
                ? Math.max(8, r.top - Math.min(h, maxH) - 6)
                // When opening down, places the list 6px below the select.
                : r.bottom + 6;
            // Applies the final left position in pixels.
            menu.style.left = left + 'px';
            // Applies the final top position, never closer than 8px to the edge.
            menu.style.top = Math.max(8, top) + 'px';
        // Closes the position() function.
        }

        // Defines highlight(), which marks one option as keyboard-focused.
        function highlight(index, scroll) {
            // Stops when no list is open.
            if (!active) return;
            // Gets the array of option rows for the open select.
            const items = active.items;
            // Stops when the list has no options at all.
            if (!items.length) return;
            // Keeps the index inside the valid range of options.
            active.index = Math.max(0, Math.min(items.length - 1, index));
            // Toggles the is-active class so only the chosen row looks highlighted.
            items.forEach((item, i) => item.el.classList.toggle('is-active', i === active.index));
            // Scrolls the highlighted row into view when asked.
            if (scroll) items[active.index].el.scrollIntoView({ block: 'nearest' });
        // Closes the highlight() function.
        }

        // Defines choose(), which applies a clicked option to the real select.
        function choose(item) {
            // Ignores the click when nothing is open or the option is disabled.
            if (!active || !item || item.opt.disabled) return;
            // Gets the select whose list is open.
            const select = active.select;
            // Checks whether the option value differs from the select value.
            if (select.value !== item.opt.value) {
                // Copies the chosen value onto the real select element.
                select.value = item.opt.value;
                // Fires a change event so the page reacts like a native selection.
                select.dispatchEvent(new Event('change', { bubbles: true }));
            // Closes the if block that checks for a value change.
            }
            // Closes the list and returns focus to the select.
            close(true);
        // Closes the choose() function.
        }

        // Defines build(), which rebuilds the list rows for one select.
        function build(select) {
            // Clears any old rows from the menu.
            menu.innerHTML = '';
            // Starts an empty array that will collect the option entries.
            const items = [];

            // Helper that creates one clickable row for a single option.
            function addOption(opt) {
                // Creates the entry object linking the row element and the option.
                const entry = { el: null, opt };
                // Creates the row element as a plain div.
                const el = document.createElement('div');
                // Gives the row the CSS class used for custom select options.
                el.className = 'cselect-option';
                // Tells assistive tech the row behaves like a listbox option.
                el.setAttribute('role', 'option');
                // Reports whether this option is the selected one.
                el.setAttribute('aria-selected', opt.selected ? 'true' : 'false');
                // Reports whether this option is disabled.
                el.setAttribute('aria-disabled', opt.disabled ? 'true' : 'false');
                // Adds a class so disabled options look greyed out.
                el.classList.toggle('is-disabled', Boolean(opt.disabled));
                // Adds a class so the selected option looks highlighted.
                el.classList.toggle('is-selected', opt.selected);
                // Builds the check icon and a span that will hold the option text.
                el.innerHTML = '<i class="fas fa-check cselect-check" aria-hidden="true"></i><span></span>';
                // Writes the option label into that span, trimmed of spaces.
                el.querySelector('span').textContent = (opt.label || opt.textContent || '').trim();
                // Checks whether the option carries a data-reason attribute.
                if (opt.dataset && opt.dataset.reason) {
                    // Shows that reason as a tooltip on the row.
                    el.title = opt.dataset.reason;
                // Closes the if block for the data-reason tooltip.
                }
                // Highlights the row when the mouse hovers over it.
                el.addEventListener('mouseenter', () => {
                    // Skips highlighting for disabled options.
                    if (!opt.disabled) highlight(items.indexOf(entry), false);
                // Closes the mouseenter listener.
                });
                // Handles a click on the option row.
                el.addEventListener('click', () => {
                    // Checks whether this option is disabled and cannot be chosen.
                    if (opt.disabled) {
                        // Explains why disabled options still report a reason to the page.
                        // Tell the page why this option cannot be picked. The
                        // The Schedule Presentation form uses it to show availability notices.
                        // Schedule Presentation form uses it to show the
                        // It shows messages such as already scheduled, or not reached yet.
                        // "already scheduled" / "not reached yet" notice.
                        // Fires a custom cselect:blocked event so the page can explain the block.
                        select.dispatchEvent(new CustomEvent('cselect:blocked', {
                            // Lets the event bubble up to page-level listeners.
                            bubbles: true,
                            // Starts the detail object sent with the event.
                            detail: {
                                // Passes the blocked option value.
                                value: opt.value,
                                // Passes the blocked option label text.
                                label: (opt.label || opt.textContent || '').trim(),
                                // Passes the reason text, empty when none was given.
                                reason: (opt.dataset && opt.dataset.reason) || ''
                            // Closes the detail object.
                            }
                        // Closes the custom event call.
                        }));
                        // Closes the list so the notice the page shows can be read.
                        // Close the list so the notice the page shows is visible.
                        // Focus goes back to the select because the list is closing.
                        close(true);
                        // Stops the click handler for disabled options here.
                        return;
                    // Closes the disabled-option if block.
                    }
                    // Applies the chosen option through choose().
                    choose(entry);
                // Closes the click listener.
                });
                // Adds the finished row to the floating menu.
                menu.appendChild(el);
                // Stores the row element on the entry so it can be highlighted later.
                entry.el = el;
                // Adds the entry to the items array.
                items.push(entry);
            // Closes the addOption() helper.
            }

            // Loops over each child of the select, which are options or optgroups.
            Array.from(select.children).forEach(node => {
                // Checks whether the child is an optgroup that groups options.
                if (node.tagName === 'OPTGROUP') {
                    // Creates a heading div for the option group.
                    const group = document.createElement('div');
                    // Gives the group heading its CSS class.
                    group.className = 'cselect-group';
                    // Shows the group label text.
                    group.textContent = node.label;
                    // Adds the group heading to the menu.
                    menu.appendChild(group);
                    // Builds a row for every option inside this group.
                    Array.from(node.children).forEach(opt => addOption(opt));
                // Checks whether the child is a plain option element.
                } else if (node.tagName === 'OPTION') {
                    // Builds a row for the option.
                    addOption(node);
                // Closes the option branch.
                }
            // Closes the loop over the select children.
            });

            // Returns the collected option entries to the caller.
            return items;
        // Closes the build() function.
        }

        // Defines open(), which shows the custom list for one select.
        function open(select) {
            // Ignores the request when the select itself is disabled.
            if (select.disabled) return;
            // Closes the list when the same select is clicked twice.
            if (active && active.select === select) { close(false); return; }
            // Closes any other open list first, without moving focus.
            close(false);

            // Builds fresh rows from the select current options.
            const items = build(select);
            // Stops when the select has no options to show.
            if (!items.length) return;

            // Uses the dark menu theme on the login card or login page.
            menu.classList.toggle('is-dark', Boolean(select.closest('.login-card, .login-page')));
            // Stores the open state: the select, its rows, and the index.
            active = { select, items, index: Math.max(0, select.selectedIndex) };
            // Makes the list visible.
            menu.hidden = false;
            // Places the list beside the select.
            position();
            // Highlights the currently selected option and scrolls to it.
            highlight(active.index, true);
        // Closes the open() function.
        }

        // Listens for mouse presses anywhere on the page.
        document.addEventListener('mousedown', e => {
            // Does nothing when no list is open.
            if (!active) return;
            // Keeps the list open when the click is inside the list or select.
            if (menu.contains(e.target) || active.select.contains(e.target)) return;
            // Closes the list when the user clicks somewhere else.
            close(false);
        // Closes the document mousedown listener.
        });

        // Listens for key presses to drive the open list by keyboard.
        document.addEventListener('keydown', e => {
            // Ignores keys when no list is open.
            if (!active) return;
            // Gets the option rows of the open list.
            const { items } = active;
            // Escape closes the list and returns focus to the select.
            if (e.key === 'Escape') { e.preventDefault(); close(true); return; }
            // Tab closes the list and lets focus move on normally.
            if (e.key === 'Tab') { close(false); return; }
            // Enter or Space chooses the highlighted option.
            if (e.key === 'Enter' || e.key === ' ') {
                // Stops the browser default action for those keys.
                e.preventDefault();
                // Stops the key event from reaching other handlers.
                e.stopPropagation();
                // Applies the highlighted option.
                choose(items[active.index]);
                // Ends the handler after choosing.
                return;
            // Closes the Enter and Space if block.
            }
            // Maps ArrowDown to step 1 and ArrowUp to step -1, else 0.
            const step = e.key === 'ArrowDown' ? 1 : (e.key === 'ArrowUp' ? -1 : 0);
            // Leaves other keys alone.
            if (!step) return;
            // Prevents the page from scrolling on arrow keys.
            e.preventDefault();
            // Stops the arrow key event from bubbling further.
            e.stopPropagation();
            // Starts the next index at the highlighted option plus the step.
            let next = active.index + step;
            // Skips disabled options while moving in the arrow direction.
            while (next >= 0 && next < items.length && items[next].opt.disabled) next += step;
            // Highlights the new row only when it is inside the list.
            if (next >= 0 && next < items.length) highlight(next, true);
        // Closes the document keydown listener.
        });

        // Repositions the list whenever the window is resized.
        window.addEventListener('resize', position);
        // Listens for scrolling so the list can follow its select.
        window.addEventListener('scroll', e => {
            // Repositions unless the scroll happened inside the menu itself.
            if (active && !menu.contains(e.target)) position();
        // Uses the capture phase so scrolls in inner containers are seen.
        }, true);

        // Loops over each select to attach the custom menu behaviour.
        selects.forEach(select => {
            // Skips multi-selects, list boxes, and selects marked to stay native.
            if (select.multiple || select.size > 1 || select.dataset.nativeSelect !== undefined) return;

            // Records which pointer kind pressed the select.
            select.addEventListener('pointerdown', e => {
                // Falls back to mouse when the browser gives no pointer type.
                lastPointerType = e.pointerType || 'mouse';
            // Closes the pointerdown listener.
            });
            // Handles the mousedown that would normally open the native list.
            select.addEventListener('mousedown', e => {
                // Only reacts to the main (left) mouse button.
                if (e.button !== 0) return;
                // Notes that a finger should keep using the native picker.
                // A finger keeps the native picker: it is the better control
                // The native picker suits touch; this list is for desktop use.
                // there, and this page list is for the desktop.
                // Leaves early when the press came from a finger.
                if (lastPointerType === 'touch') return;
                // Blocks the native list from opening so our list shows instead.
                // Stops the native list from opening; our list opens instead.
                // Cancels the browser default dropdown behaviour.
                e.preventDefault();
                // Focuses the select without scrolling the page.
                select.focus({ preventScroll: true });
                // Opens the custom option list for this select.
                open(select);
            // Closes the mousedown listener.
            });
            // Handles keyboard opening of the custom list.
            select.addEventListener('keydown', e => {
                // Does nothing when this select list is already open.
                if (active && active.select === select) return;
                // Opens the list on ArrowDown, ArrowUp, Enter, or Space.
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter' || e.key === ' ') {
                    // Stops those keys from doing their normal browser action.
                    e.preventDefault();
                    // Opens the custom list for this select.
                    open(select);
                // Closes the key condition block.
                }
            // Closes the keydown listener.
            });
            // Explains that a value changed elsewhere also closes this list.
            // A value chosen elsewhere (keyboard typing, script) closes the list.
            // Listens for change events on the select.
            select.addEventListener('change', () => {
                // Closes the list when it belongs to this select.
                if (active && active.select === select) close(false);
            // Closes the change listener.
            });
        // Closes the loop over the selects.
        });
    // Closes and runs the select-menu helper function.
    })();

// Closes the outer script wrapper that began earlier in the file.
})();