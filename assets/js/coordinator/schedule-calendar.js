// Starts the schedule calendar script as a function that runs immediately.
(function () {
    // Turns on strict mode for safer JavaScript.
    'use strict';

    // Defines initScheduleCalendar(), which sets up one presentation calendar.
    function initScheduleCalendar(root) {
        // Reads the JSON endpoint address from the data-endpoint attribute.
        const endpoint = root.dataset.endpoint;
        // Finds the grid that holds the day cells.
        const grid = root.querySelector('[data-psc-grid]');
        // Finds the heading that shows the month name.
        const monthLabel = root.querySelector('[data-psc-month]');
        // Finds the line that summarises how many presentations there are.
        const summary = root.querySelector('[data-psc-summary]');
        // Finds the loading progress indicator.
        const progress = root.querySelector('[data-psc-progress]');
        // Finds the box that shows load errors.
        const alertBox = root.querySelector('[data-psc-alert]');
        // Finds the element that holds the error message text.
        const alertMessage = root.querySelector('[data-psc-alert-msg]');
        // Finds the calendar wrapper used for the busy state.
        const calendar = root.querySelector('.psc-calendar');
        // Finds the overlay that dims the page behind the dialog.
        const overlay = root.querySelector('[data-psc-overlay]');
        // Finds the event details dialog.
        const dialog = root.querySelector('.psc-dialog');
        // Finds the dialog title element.
        const dialogTitle = root.querySelector('[data-psc-dlg-title]');
        // Finds the dialog subtitle element.
        const dialogSub = root.querySelector('[data-psc-dlg-sub]');
        // Finds the dialog body that lists event details.
        const dialogBody = root.querySelector('[data-psc-dlg-body]');
        // Reads the polling interval in milliseconds, or zero for none.
        const pollMs = Number(root.dataset.poll) || 0;
        // Reads today date from the markup, or starts empty.
        let today = root.dataset.today || '';
        // Sets the visible month from that date, or from the clock.
        let month = today ? new Date(`${today}T00:00:00Z`) : new Date();
        // Remembers the last data signature to avoid needless redraws.
        let lastSignature = '';
        // Holds the fetch controller so requests can be cancelled.
        let activeController = null;
        // Tracks whether a request is already running.
        let loading = false;
        // Counts requests so stale responses can be ignored.
        let requestSequence = 0;
        // Holds the interval id used for polling.
        let pollTimer = 0;
        // Remembers the button that opened the dialog so focus can return.
        let returnFocus = null;

        // Stops when required markup is missing, so nothing breaks.
        if (!endpoint || !grid || !monthLabel || !calendar) return;
        // Normalises the month to the first day at UTC midnight.
        month = new Date(Date.UTC(month.getUTCFullYear(), month.getUTCMonth(), 1));

        // Defines dateKey(), which formats a date as YYYY-MM-DD.
        function dateKey(date) {
            // Builds the key with zero-padded month and day numbers.
            return `${date.getUTCFullYear()}-${String(date.getUTCMonth() + 1).padStart(2, '0')}-${String(date.getUTCDate()).padStart(2, '0')}`;
        // Closes the dateKey() function.
        }

        // Defines addDays(), which returns a date shifted by some days.
        function addDays(date, days) {
            // Copies the date so the original is not changed.
            const result = new Date(date);
            // Adds the days in UTC so time zones cannot shift the date.
            result.setUTCDate(result.getUTCDate() + days);
            // Returns the shifted date.
            return result;
        // Closes the addDays() function.
        }

        // Defines makeElement(), a shortcut for creating a styled element.
        function makeElement(tag, className, text) {
            // Creates the element with the given tag name.
            const element = document.createElement(tag);
            // Applies the CSS class when one was given.
            if (className) element.className = className;
            // Sets the text content when text was given.
            if (text !== undefined) element.textContent = text;
            // Returns the new element.
            return element;
        // Closes the makeElement() function.
        }

        // Defines setBusy(), which shows or hides the loading state.
        function setBusy(value) {
            // Sets aria-busy so screen readers know the calendar is loading.
            calendar.setAttribute('aria-busy', value ? 'true' : 'false');
            // Shows the progress bar only while loading.
            progress?.classList.toggle('is-active', value);
        // Closes the setBusy() function.
        }

        // Defines closeDialog(), which hides the event dialog.
        function closeDialog() {
            // Hides the overlay and the dialog.
            overlay.hidden = true;
            // Removes the body class that locked page scrolling.
            document.body.classList.remove('psc-dialog-open');
            // Returns focus to the button that opened the dialog.
            returnFocus?.focus();
        // Closes the closeDialog() function.
        }

        // Defines showEvent(), which fills the dialog with one presentation.
        function showEvent(event, date, trigger) {
            // Remembers which button opened the dialog.
            returnFocus = trigger;
            // Shows the student name as the dialog title.
            dialogTitle.textContent = event.student || 'Presentation';
            // Starts a date formatter for a long, readable date.
            const prettyDate = new Intl.DateTimeFormat(undefined, {
                // Uses full weekday and month names in UTC.
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', timeZone: 'UTC'
            // Formats the clicked date for display.
            }).format(new Date(`${date}T00:00:00Z`));
            // Shows the date, start time, and end time in the subtitle.
            dialogSub.textContent = [prettyDate, event.startLabel, event.endLabel ? `to ${event.endLabel}` : ''].filter(Boolean).join(' · ');
            // Empties the dialog body before adding new rows.
            dialogBody.replaceChildren();
            // Starts the list of label and value pairs to display.
            [
                // Adds the presentation title row.
                ['Title', event.title],
                // Adds the workflow stage row.
                ['Stage', event.stage],
                // Adds the venue row.
                ['Venue', event.venue],
                // Adds the status row, using the friendly label when present.
                ['Status', event.statusLabel || event.status]
            // Loops over each label and value pair.
            ].forEach(([label, value]) => {
                // Skips pairs whose value is empty.
                if (!value) return;
                // Creates a row div for the detail.
                const row = makeElement('div', 'psc-detail-row');
                // Adds the label span and the value span to the row.
                row.append(makeElement('span', 'psc-detail-label', label), makeElement('span', 'psc-detail-value', value));
                // Adds the finished row to the dialog body.
                dialogBody.appendChild(row);
            // Closes the detail loop.
            });
            // Checks whether the event has a duration to show.
            if (event.duration) {
                // Creates a row for the duration.
                const row = makeElement('div', 'psc-detail-row');
                // Adds the Duration label and its value.
                row.append(makeElement('span', 'psc-detail-label', 'Duration'), makeElement('span', 'psc-detail-value', event.duration));
                // Adds the duration row to the dialog body.
                dialogBody.appendChild(row);
            // Closes the duration block.
            }
            // Shows the overlay and dialog.
            overlay.hidden = false;
            // Locks page scrolling while the dialog is open.
            document.body.classList.add('psc-dialog-open');
            // Moves keyboard focus into the dialog.
            dialog.focus();
        // Closes the showEvent() function.
        }

        // Defines createEventButton(), which makes a clickable event chip.
        function createEventButton(event, date) {
            // Creates the button element for the event.
            const button = makeElement('button', 'psc-event');
            // Sets the button type so it never submits a form.
            button.type = 'button';
            // Reads the event status key, or an empty string.
            const statusKey = String(event.statusKey || event.status || '')
                // Lowercases it so the CSS class name matches.
                .toLowerCase()
                // Removes characters that are not safe in a class name.
                .replace(/[^a-z0-9_-]/g, '');
            // Uses the given status label when one exists.
            const statusLabel = event.statusLabel
                // Otherwise builds a label by capitalising the status key.
                || (statusKey ? statusKey.charAt(0).toUpperCase() + statusKey.slice(1) : '');
            // Adds a status class so each status can have its own colour.
            if (statusKey) button.classList.add(`psc-event--${statusKey}`);
            // Builds the hover tooltip from the event details.
            button.title = [event.student, event.title, event.startLabel, statusLabel].filter(Boolean).join(' · ');
            // Adds the start time when the event has one.
            if (event.startLabel) button.appendChild(makeElement('span', 'psc-event-time', event.startLabel));
            // Adds the student name, falling back to the title.
            button.appendChild(makeElement('span', 'psc-event-name', event.student || event.title || 'Presentation'));
            // Adds the status label when there is one.
            if (statusLabel) button.appendChild(makeElement('span', 'psc-event-status', statusLabel));
            // Opens the details dialog when the button is clicked.
            button.addEventListener('click', () => showEvent(event, date, button));
            // Returns the finished button.
            return button;
        // Closes the createEventButton() function.
        }

        // Defines render(), which builds the 42 day cells of the month grid.
        function render(dates) {
            // Starts a formatter for the month and year heading.
            const monthName = new Intl.DateTimeFormat(undefined, {
                // Uses the long month name and the year in UTC.
                month: 'long', year: 'numeric', timeZone: 'UTC'
            // Formats the month currently being shown.
            }).format(month);
            // Shows that month name above the calendar.
            monthLabel.textContent = monthName;
            // Gets the first day of the month.
            const firstDay = new Date(Date.UTC(month.getUTCFullYear(), month.getUTCMonth(), 1));
            // Starts the grid on the Sunday on or before that first day.
            const start = addDays(firstDay, -firstDay.getUTCDay());
            // Creates a fragment so cells can be added in one go.
            const fragment = document.createDocumentFragment();
            // Starts counting presentations inside the current month.
            let eventCount = 0;

            // Loops 42 times to fill six weeks of cells.
            for (let index = 0; index < 42; index++) {
                // Calculates the date for this cell.
                const date = addDays(start, index);
                // Builds the lookup key for that date.
                const key = dateKey(date);
                // Gets that date events, or an empty list.
                const events = Array.isArray(dates[key]) ? dates[key] : [];
                // Adds to the monthly count only for days inside this month.
                if (date.getUTCMonth() === month.getUTCMonth()) eventCount += events.length;

                // Creates the day cell div.
                const cell = makeElement('div', 'psc-day');
                // Marks the cell as a grid cell for screen readers.
                cell.setAttribute('role', 'gridcell');
                // Dims days that belong to the previous or next month.
                if (date.getUTCMonth() !== month.getUTCMonth()) cell.classList.add('is-outside');
                // Highlights the cell for today.
                if (key === today) cell.classList.add('is-today');
                // Marks days that have presentations.
                if (events.length) cell.classList.add('has-events');

                // Creates the heading row for the day number and count.
                const heading = makeElement('div', 'psc-day-heading');
                // Starts adding the day number and schedule count.
                heading.append(
                    // Adds the day of the month.
                    makeElement('span', 'psc-day-number', String(date.getUTCDate())),
                    // Adds the schedule count text, singular or plural.
                    makeElement('span', 'psc-day-count', events.length ? `${events.length} ${events.length === 1 ? 'schedule' : 'schedules'}` : '')
                // Closes the heading append call.
                );
                // Adds the heading to the day cell.
                cell.appendChild(heading);

                // Shows at most three event buttons per day.
                events.slice(0, 3).forEach(event => cell.appendChild(createEventButton(event, key)));
                // Checks whether some events were hidden.
                if (events.length > 3) {
                    // Creates the more-events button for the hidden events.
                    const more = makeElement('button', 'psc-more-events', `+${events.length - 3} more`);
                    // Sets the button type so it never submits a form.
                    more.type = 'button';
                    // Expands the rest of the events when clicked.
                    more.addEventListener('click', () => {
                        // Removes the more-events button.
                        more.remove();
                        // Adds the remaining events to the cell.
                        events.slice(3).forEach(event => cell.appendChild(createEventButton(event, key)));
                    // Closes the click listener.
                    });
                    // Adds the more-events button to the cell.
                    cell.appendChild(more);
                // Closes the more-events block.
                }
                // Adds the finished cell to the fragment.
                fragment.appendChild(cell);
            // Closes the 42-cell loop.
            }

            // Replaces the grid contents with the new cells.
            grid.replaceChildren(fragment);
            // Writes the monthly summary, singular or plural.
            summary.textContent = `${eventCount} ${eventCount === 1 ? 'presentation' : 'presentations'} this month`;
        // Closes the render() function.
        }

        // Defines loadSchedules(), which fetches the month schedules.
        async function loadSchedules() {
            // Stops when a request is already in flight.
            if (loading) return;
            // Marks the request as running.
            loading = true;
            // Gives this request a number so stale replies can be ignored.
            const requestId = ++requestSequence;
            // Creates a controller so the request can be aborted.
            const controller = new AbortController();
            // Stores the controller so a month change can cancel the request.
            activeController = controller;
            // Gets the first day of the visible month.
            const firstDay = new Date(Date.UTC(month.getUTCFullYear(), month.getUTCMonth(), 1));
            // Finds the first day shown in the grid.
            const gridStart = addDays(firstDay, -firstDay.getUTCDay());
            // Finds the last day shown, 41 days after the first.
            const gridEnd = addDays(gridStart, 41);
            // Builds the request URL from the endpoint attribute.
            const requestUrl = new URL(endpoint, window.location.href);
            // Sends the grid start date as a query parameter.
            requestUrl.searchParams.set('start', dateKey(gridStart));
            // Sends the grid end date as a query parameter.
            requestUrl.searchParams.set('end', dateKey(gridEnd));

            // Shows the loading state.
            setBusy(true);
            // Hides any previous error message.
            alertBox.hidden = true;
            // Starts the guarded request block.
            try {
                // Asks the server for the schedules as JSON.
                const response = await fetch(requestUrl.toString(), {
                    // Tells the server to reply with JSON.
                    headers: { Accept: 'application/json' },
                    // Sends the session cookie so the signed-in user is recognised.
                    credentials: 'same-origin',
                    // Prevents the browser from reusing an old cached reply.
                    cache: 'no-store',
                    // Lets the controller abort this request.
                    signal: controller.signal
                // Closes the fetch options object.
                });
                // Reads the JSON body of the reply.
                const payload = await response.json();
                // Checks the HTTP status, the ok flag, and the dates object.
                if (!response.ok || payload.ok !== true || !payload.dates || typeof payload.dates !== 'object') {
                    // Throws an error using the server message or a friendly one.
                    throw new Error(payload.error || 'We couldn’t load the schedule. Please try again.');
                // Closes the response check block.
                }
                // Checks whether the server also sent today date.
                if (payload.today) {
                    // Clears the cached signature when the day has changed.
                    if (today !== payload.today) lastSignature = '';
                    // Stores the server today date in memory.
                    today = payload.today;
                    // Stores the server today date on the root for later use.
                    root.dataset.today = payload.today;
                // Closes the today block.
                }
                // Redraws only when the data changed or the grid is still empty.
                if (payload.signature !== lastSignature || !grid.childElementCount) {
                    // Builds the calendar grid from the returned dates.
                    render(payload.dates);
                    // Remembers the signature so identical data skips redrawing.
                    lastSignature = payload.signature;
                // Closes the redraw check.
                }
            // Catches failed requests and other errors.
            } catch (error) {
                // Ignores aborted requests because they were cancelled on purpose.
                if (error.name !== 'AbortError') {
                    // Shows the error message, or a friendly fallback text.
                    alertMessage.textContent = error.message || 'We couldn’t load the schedule. Please try again.';
                    // Makes the error box visible.
                    alertBox.hidden = false;
                    // Replaces the summary with a failure message.
                    summary.textContent = 'Schedule could not be loaded.';
                // Closes the error block.
                }
            // Runs cleanup code whatever happened above.
            } finally {
                // Cleans up only when this is still the newest request.
                if (requestId === requestSequence) {
                    // Clears the running flag.
                    loading = false;
                    // Forgets the finished controller.
                    activeController = null;
                    // Hides the loading state.
                    setBusy(false);
                // Closes the newest-request check.
                }
            // Closes the finally block.
            }
        // Closes the loadSchedules() function.
        }

        // Defines changeMonth(), which switches the calendar to another month.
        function changeMonth(nextMonth) {
            // Cancels any request still loading for the old month.
            activeController?.abort();
            // Resets the loading flag after the cancellation.
            loading = false;
            // Moves the visible month to the first day of the new month.
            month = new Date(Date.UTC(nextMonth.getUTCFullYear(), nextMonth.getUTCMonth(), 1));
            // Clears the cached signature so the grid is rebuilt.
            lastSignature = '';
            // Loads the schedules for the new month.
            loadSchedules();
        // Closes the changeMonth() function.
        }

        // Wires the previous-month button when it exists.
        root.querySelector('[data-psc-prev]')?.addEventListener('click', () => {
            // Moves back one calendar month.
            changeMonth(new Date(Date.UTC(month.getUTCFullYear(), month.getUTCMonth() - 1, 1)));
        // Closes the previous-month listener.
        });
        // Wires the next-month button when it exists.
        root.querySelector('[data-psc-next]')?.addEventListener('click', () => {
            // Moves forward one calendar month.
            changeMonth(new Date(Date.UTC(month.getUTCFullYear(), month.getUTCMonth() + 1, 1)));
        // Closes the next-month listener.
        });
        // Wires the today button when it exists.
        root.querySelector('[data-psc-today]')?.addEventListener('click', () => {
            // Reads today date from the root or from memory.
            const currentToday = root.dataset.today || today;
            // Converts it into a date object at UTC midnight.
            const date = currentToday ? new Date(`${currentToday}T00:00:00Z`) : new Date();
            // Jumps the calendar back to that month.
            changeMonth(date);
        // Closes the today button listener.
        });
        // Wires the retry button so it loads the schedules again.
        root.querySelector('[data-psc-retry]')?.addEventListener('click', loadSchedules);
        // Wires every close button inside the dialog.
        root.querySelectorAll('[data-psc-close]').forEach(button => button.addEventListener('click', closeDialog));
        // Listens for clicks on the dialog overlay.
        overlay?.addEventListener('click', event => {
            // Closes the dialog only when the overlay itself was clicked.
            if (event.target === overlay) closeDialog();
        // Closes the overlay click listener.
        });
        // Listens for keys on the whole page.
        document.addEventListener('keydown', event => {
            // Escape closes the event dialog when it is open.
            if (event.key === 'Escape' && overlay && !overlay.hidden) closeDialog();
        // Closes the document keydown listener.
        });

        // Loads the schedules as soon as the calendar is ready.
        loadSchedules();
        // Checks whether automatic polling is configured.
        if (pollMs > 0) {
            // Starts a repeating timer for refreshing the data.
            pollTimer = window.setInterval(() => {
                // Refreshes only when the tab is visible and no request runs.
                if (!document.hidden && !loading) loadSchedules();
            // Uses the poll interval given in the markup.
            }, pollMs);
        // Closes the polling block.
        }
        // Stops the timer and requests when the page is being left.
        window.addEventListener('pagehide', () => {
            // Clears the polling interval.
            window.clearInterval(pollTimer);
            // Aborts any request still in flight.
            activeController?.abort();
        // Runs this cleanup only once.
        }, { once: true });
    // Closes the initScheduleCalendar() function.
    }

    // Defines bootScheduleCalendars(), which starts every calendar.
    function bootScheduleCalendars() {
        // Finds each schedule calendar on the page and sets it up.
        document.querySelectorAll('[data-schedule-calendar]').forEach(initScheduleCalendar);
    // Closes the bootScheduleCalendars() function.
    }

    // Checks whether the document is still loading.
    if (document.readyState === 'loading') {
        // Starts the calendars after the page finishes loading.
        document.addEventListener('DOMContentLoaded', bootScheduleCalendars);
    // Otherwise starts them immediately.
    } else {
        // Starts the calendars now because the page is ready.
        bootScheduleCalendars();
    // Closes the if and else block.
    }
// Closes and runs the schedule calendar script function.
})();