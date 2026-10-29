    // Starts the comment block for the Student Population dashboard script.
    /* ==========================================================================
 * Student Population dashboard (shared by both coordinator dashboards)
 * Pure SVG: no external chart library. Types: bar, hbar, pie, donut.
 * Data comes from <script type="application/json" data-pop-data>.
 * ========================================================================== */
// Starts the dashboard script as a function that runs immediately.
(function () {
    // Turns on strict mode so common JavaScript mistakes throw errors.
    'use strict';

    // Stores the SVG namespace needed to create SVG elements.
    const SVG_NS = 'http://www.w3.org/2000/svg';
    // Names the session-storage key that remembers the chosen chart type.
    const STORAGE_KEY = 'adzuProgramChartType';
    // Sets the bar chart as the default chart type.
    const DEFAULT_TYPE = 'bar';
    // Detects the reduced-motion setting so animations can be skipped.
    const REDUCED_MOTION = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    // Starts the META table holding each chart type title and description.
    const META = {
        // Describes the vertical bar chart and its caption text.
        bar:   { title: 'Total Students per Program',        desc: t => 'Number of enrolled students in each academic program (' + fmt(t) + ' in total).' },
        // Describes the horizontal bar chart and its caption text.
        hbar:  { title: 'Total Students per Program',        desc: t => 'Programs ranked by number of enrolled students (' + fmt(t) + ' in total).' },
        // Describes the pie chart and its caption text.
        pie:   { title: 'Share of Total Student Population', desc: t => 'Each program\'s proportion of the ' + fmt(t) + ' enrolled students.' },
        // Describes the donut chart and its caption text.
        donut: { title: 'Share of Total Student Population', desc: t => 'Each program\'s proportion of the ' + fmt(t) + ' enrolled students.' }
    // Closes the META table.
    };

    // Starts the helper functions section of the dashboard script.
    /* ---------- helpers ---------- */
    // Defines svgEl(), which creates one SVG element with attributes and text.
    function svgEl(tag, attrs, text) {
        // Creates the SVG element in the correct SVG namespace.
        const node = document.createElementNS(SVG_NS, tag);
        // Copies each given attribute onto the new element.
        if (attrs) Object.keys(attrs).forEach(k => node.setAttribute(k, attrs[k]));
        // Adds text content when some text was supplied.
        if (text !== undefined && text !== null) node.textContent = text;
        // Returns the finished SVG element.
        return node;
    // Closes the svgEl() helper.
    }
    // Formats a number with commas, such as 1,250.
    function fmt(n) { return Number(n).toLocaleString('en-US'); }
    // Defines truncate(), which shortens long labels to fit a chart.
    function truncate(str, max) {
        // Converts the value to text so it always has a length.
        str = String(str);
        // Keeps the limit a whole number of at least 4 characters.
        max = Math.max(4, Math.floor(max));
        // Cuts the text and adds an ellipsis when it is too long.
        return str.length > max ? str.slice(0, max - 1).trimEnd() + '\u2026' : str;
    // Closes the truncate() helper.
    }
    // Eases animation speed so it starts fast and slows at the end.
    function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }

    // Documents axisScale(), which builds axis steps and the top value.
    /** Integer-friendly axis: returns { top, ticks } with top >= maxVal. */
    // Defines axisScale(), returning a top value and a list of tick marks.
    function axisScale(maxVal) {
        // Avoids a zero or negative maximum by using at least 1.
        const v = Math.max(1, maxVal);
        // Divides the maximum into about five parts.
        const raw = v / 5;
        // Finds the power of ten below that part size.
        const exp = Math.pow(10, Math.floor(Math.log10(raw)));
        // Normalises the part size into the range 1 to 10.
        const f = raw / exp;
        // Rounds the step to 1, 2, 5, or 10 for friendly axis labels.
        const nf = f <= 1 ? 1 : f <= 2 ? 2 : f <= 5 ? 5 : 10;
        // Multiplies that choice back by the power of ten, at least 1.
        const step = Math.max(1, nf * exp);
        // Rounds the top of the axis up to a multiple of the step.
        const top = Math.ceil(v / step) * step;
        // Starts an empty list of tick values.
        const ticks = [];
        // Adds tick values from 0 up to the top in step sizes.
        for (let t = 0; t <= top; t += step) ticks.push(t);
        // Returns the top value and the tick list.
        return { top: top, ticks: ticks };
    // Closes the axisScale() function.
    }

    // Reads the saved chart type from session storage, ignoring errors.
    function readStoredType() { try { return window.sessionStorage.getItem(STORAGE_KEY); } catch (e) { return null; } }
    // Saves the chosen chart type in session storage, ignoring errors.
    function storeType(t) { try { window.sessionStorage.setItem(STORAGE_KEY, t); } catch (e) { /* ignore */ } }

    // Starts the section for one chart instance.
    /* ---------- one chart instance ---------- */
    // Defines initProgramChart(), which sets up one dashboard chart.
    function initProgramChart(root) {
        // Stops when this chart was already initialised before.
        if (root.dataset.popReady === '1') return;

        // Finds the JSON data element that holds the program figures.
        const dataEl  = root.querySelector('[data-pop-data]');
        // Finds the element that will receive the drawn chart.
        const chartEl = root.querySelector('[data-pop-chart]');
        // Finds the stage element used to position the tooltip.
        const stage   = root.querySelector('[data-pop-stage]');
        // Finds the floating tooltip element.
        const tip     = root.querySelector('[data-pop-tooltip]');
        // Finds the button that opens the chart-type dropdown.
        const toggle  = root.querySelector('[data-pop-toggle]');
        // Finds the dropdown menu that lists the chart types.
        const menu    = root.querySelector('[data-pop-menu]');
        // Finds the wrapper that holds the dropdown button and menu.
        const select  = root.querySelector('[data-pop-select]');
        // Finds the element that shows the chart title.
        const titleEl = root.querySelector('[data-pop-chart-title]');
        // Finds the element that shows the chart description.
        const descEl  = root.querySelector('[data-pop-chart-desc]');
        // Finds the element that shows the current chart type label.
        const labelEl = root.querySelector('[data-pop-current-label]');
        // Finds the icon element for the current chart type.
        const iconEl  = root.querySelector('[data-pop-current-icon]');
        // Stops when any required part of the chart markup is missing.
        if (!dataEl || !chartEl || !stage || !tip || !toggle || !menu || !select) return;

        // Declares the variable that will hold the parsed chart data.
        let payload;
        // Parses the JSON data, falling back to null when it is invalid.
        try { payload = JSON.parse(dataEl.textContent); } catch (e) { payload = null; }
        // Reads the programs array, or an empty array when absent.
        const programs = payload && Array.isArray(payload.programs) ? payload.programs : [];
        // Reads the total student count, defaulting to zero.
        const total = payload ? Number(payload.total) || 0 : 0;
        // Stops when there are no programs or no students to show.
        if (!programs.length || total <= 0) return;
        // Marks the root as ready so it is not set up twice.
        root.dataset.popReady = '1';

        // Collects the chart-type buttons inside the dropdown menu.
        const options = Array.prototype.slice.call(menu.querySelectorAll('[data-chart-type]'));
        // Collects the legend items so they can highlight chart shapes.
        const legendItems = Array.prototype.slice.call(root.querySelectorAll('.pop-legend-item'));

        // Holds the chart type currently being shown.
        let type = DEFAULT_TYPE;
        // Holds the running animation frame id so it can be cancelled.
        let rafId = 0;
        // Holds the timer used during the fade between chart types.
        let switchTimer = 0;
        // Remembers the last measured chart width for resize redraws.
        let lastW = 0;

        // Starts the tooltip and highlight section.
        /* ----- tooltip + highlight ----- */
        // Defines setActive(), which highlights the shape for one program.
        function setActive(i) {
            // Loops over every element that carries a data-index value.
            root.querySelectorAll('[data-index]').forEach(function (n) {
                // Marks only the element for index i as active.
                n.classList.toggle('is-active', i !== null && Number(n.getAttribute('data-index')) === i);
            // Closes the loop over the indexed elements.
            });
            // Adds a class on the chart when something is highlighted.
            chartEl.classList.toggle('has-active', i !== null);
        // Closes the setActive() function.
        }

        // Defines hideTip(), which hides the floating tooltip.
        function hideTip() { tip.hidden = true; }

        // Defines showTip(), which fills and places the tooltip.
        function showTip(i, x, y) {
            // Gets the program data for the shape that was pointed at.
            const p = programs[i];
            // Stops when no program matches that index.
            if (!p) return;
            // Clears any previous tooltip content.
            tip.textContent = '';
            // Creates the tooltip heading div for the program name.
            const head = document.createElement('div');
            // Applies the heading style class.
            head.className = 'pop-tooltip-name';
            // Creates the small coloured dot span.
            const dot = document.createElement('span');
            // Applies the dot style class.
            dot.className = 'pop-tooltip-dot';
            // Colours the dot with the program chart colour.
            dot.style.background = p.color;
            // Adds the coloured dot to the heading.
            head.appendChild(dot);
            // Adds the program label text to the heading.
            head.appendChild(document.createTextNode(p.label));
            // Creates the second tooltip line for the numbers.
            const meta = document.createElement('div');
            // Applies the meta line style class.
            meta.className = 'pop-tooltip-meta';
            // Writes the student count and its percentage of the total.
            meta.textContent = fmt(p.count) + ' student' + (p.count === 1 ? '' : 's') + ' \u00b7 ' + p.percent + '% of total';
            // Adds the heading to the tooltip.
            tip.appendChild(head);
            // Adds the numbers line to the tooltip.
            tip.appendChild(meta);
            // Makes the tooltip visible.
            tip.hidden = false;

            // Measures the chart stage to keep the tooltip inside it.
            const sr = stage.getBoundingClientRect();
            // Reads the tooltip width and height for the edge checks.
            const tw = tip.offsetWidth, th = tip.offsetHeight;
            // Places the tooltip 14px right of the pointer.
            let left = x - sr.left + 14;
            // Places the tooltip 14px below the pointer.
            let top = y - sr.top + 14;
            // Flips the tooltip to the left when it would overflow the stage.
            if (left + tw > sr.width - 4) left = x - sr.left - tw - 14;
            // Flips the tooltip above the pointer when it would overflow below.
            if (top + th > sr.height - 4) top = y - sr.top - th - 14;
            // Applies the left position, never closer than 4px to the edge.
            tip.style.left = Math.max(4, left) + 'px';
            // Applies the top position, never closer than 4px to the edge.
            tip.style.top = Math.max(4, top) + 'px';
        // Closes the showTip() function.
        }

        // Defines bindShape(), which adds hover, focus, and tap handling to a shape.
        function bindShape(node, i) {
            // Gets the program data that this shape represents.
            const p = programs[i];
            // Makes the shape reachable with the Tab key.
            node.setAttribute('tabindex', '0');
            // Tells assistive tech the shape acts as an image.
            node.setAttribute('role', 'img');
            // Describes the program, count, and percentage for screen readers.
            node.setAttribute('aria-label', p.label + ': ' + fmt(p.count) + ' students, ' + p.percent + '%');
            // Highlights the shape and shows its tooltip when the pointer enters.
            node.addEventListener('pointerenter', function (e) { setActive(i); showTip(i, e.clientX, e.clientY); });
            // Keeps the tooltip following the pointer while it moves.
            node.addEventListener('pointermove', function (e) { showTip(i, e.clientX, e.clientY); });
            // Handles the pointer leaving the shape.
            node.addEventListener('pointerleave', function (e) {
                // On touch screens the tooltip stays until the user taps elsewhere.
                if (e.pointerType === 'touch') return; // keep visible on touch until next tap elsewhere
                // Clears the highlight and hides the tooltip.
                setActive(null); hideTip();
            // Closes the pointerleave listener.
            });
            // Handles keyboard focus on the shape.
            node.addEventListener('focus', function () {
                // Highlights the focused shape.
                setActive(i);
                // Measures the shape so the tooltip can sit near it.
                const r = node.getBoundingClientRect();
                // Shows the tooltip near the middle of the focused shape.
                showTip(i, r.left + r.width / 2, r.top + Math.min(r.height / 2, 40));
            // Closes the focus listener.
            });
            // Clears the highlight and tooltip when the shape loses focus.
            node.addEventListener('blur', function () { setActive(null); hideTip(); });
        // Closes the bindShape() function.
        }

        // Listens for pointer presses inside the chart area.
        root.addEventListener('pointerdown', function (e) {
            // On touch, clears the tooltip when the tap missed a chart shape.
            if (e.pointerType === 'touch' && !(e.target.closest && e.target.closest('.pop-shape'))) {
                // Removes the highlight and hides the tooltip.
                setActive(null); hideTip();
            // Closes the touch condition block.
            }
        // Closes the pointerdown listener.
        });

        // Loops over the legend items.
        legendItems.forEach(function (li) {
            // Reads the program index stored on the legend item.
            const i = Number(li.getAttribute('data-index'));
            // Makes the legend item keyboard focusable.
            li.setAttribute('tabindex', '0');
            // Highlights the matching shape when the pointer enters the legend item.
            li.addEventListener('pointerenter', function () { setActive(i); });
            // Clears the highlight when the pointer leaves the legend item.
            li.addEventListener('pointerleave', function () { setActive(null); });
            // Highlights the shape when the legend item is focused.
            li.addEventListener('focus', function () { setActive(i); });
            // Clears the highlight when the legend item loses focus.
            li.addEventListener('blur', function () { setActive(null); });
        // Closes the legend loop.
        });

        // Starts the renderer functions that draw each chart type.
        /* ----- renderers ----- */
        // Defines drawBar(), which draws the vertical bar chart.
        function drawBar(W) {
            // Counts how many programs the chart must show.
            const n = programs.length;
            // Rotates labels when there are many programs or the chart is narrow.
            const rotate = n > 5 || W < 480;
            // Sets the chart height between 300 and 400 pixels.
            const H = Math.round(Math.max(300, Math.min(400, W * 0.58)));
            // Sets the chart margins, with a taller bottom when labels rotate.
            const m = { top: 28, right: 16, bottom: rotate ? 92 : 52, left: 48 };
            // Works out the drawing width inside the margins.
            const innerW = W - m.left - m.right;
            // Works out the drawing height inside the margins.
            const innerH = H - m.top - m.bottom;
            // Builds an axis scale from the biggest program count.
            const sc = axisScale(Math.max.apply(null, programs.map(function (p) { return p.count; })));
            // Creates the SVG canvas with a viewBox matching width and height.
            const svg = svgEl('svg', { viewBox: '0 0 ' + W + ' ' + H, class: 'pop-svg', preserveAspectRatio: 'xMidYMid meet' });

            // Loops over the axis tick values.
            sc.ticks.forEach(function (t) {
                // Turns a tick value into a vertical pixel position.
                const y = m.top + innerH - (t / sc.top) * innerH;
                // Draws the grid line, using a darker style for the zero line.
                svg.appendChild(svgEl('line', { x1: m.left, x2: W - m.right, y1: y, y2: y, class: t === 0 ? 'pop-axis' : 'pop-grid' }));
                // Draws the tick label to the left of the chart.
                svg.appendChild(svgEl('text', { x: m.left - 8, y: y + 4, 'text-anchor': 'end', class: 'pop-tick' }, fmt(t)));
            // Closes the tick loop.
            });

            // Works out how much horizontal space each program gets.
            const band = innerW / n;
            // Sizes each bar at 62 percent of that space, between 6 and 64 pixels.
            const barW = Math.max(6, Math.min(64, band * 0.62));
            // Estimates how many label characters fit in one band.
            const maxChars = Math.floor(band / 6.6);

            // Loops over the programs to draw each bar.
            programs.forEach(function (p, i) {
                // Calculates the bar height from the count and the axis top.
                const h = (p.count / sc.top) * innerH;
                // Calculates the bar left position, centring it in its band.
                const x = m.left + band * i + (band - barW) / 2;
                // Calculates the bar top so it stands on the axis.
                const y = m.top + innerH - h;
                // Finds the horizontal centre of the bar for its labels.
                const cx = x + barW / 2;

                // Creates the bar rectangle with rounded corners and the program colour.
                const bar = svgEl('rect', { x: x, y: y, width: barW, height: h, rx: 4, fill: p.color, class: 'pop-shape pop-bar', 'data-index': i });
                // Delays each bar animation so they appear one after another.
                bar.style.animationDelay = (i * 60) + 'ms';
                // Adds hover, focus, and click behaviour to this bar.
                bindShape(bar, i);
                // Adds the bar to the SVG.
                svg.appendChild(bar);

                // Draws the count value above the bar.
                const val = svgEl('text', { x: cx, y: y - 8, 'text-anchor': 'middle', class: 'pop-value pop-fade' }, fmt(p.count));
                // Delays the value label so it fades in after the bar.
                val.style.animationDelay = (350 + i * 60) + 'ms';
                // Adds the value label to the SVG.
                svg.appendChild(val);

                // Sets the vertical position for the category label below the axis.
                const ly = m.top + innerH + 18;
                // Draws the truncated program name as the category label.
                const lbl = svgEl('text', { x: cx, y: ly, 'text-anchor': rotate ? 'end' : 'middle', class: 'pop-cat' }, truncate(p.label, rotate ? 18 : maxChars));
                // Rotates the label when space is tight.
                if (rotate) lbl.setAttribute('transform', 'rotate(-35 ' + cx + ' ' + ly + ')');
                // Adds the category label to the SVG.
                svg.appendChild(lbl);
            // Closes the loop over programs.
            });

            // Returns the finished SVG with no start animation callback.
            return { svg: svg, start: null };
        // Closes the drawBar() function.
        }

        // Defines drawHBar(), which draws the horizontal bar chart.
        function drawHBar(W) {
            // Counts how many programs the chart must show.
            const n = programs.length;
            // Sets the height of each row and of each bar.
            const rowH = 42, barH = 24;
            // Sets the margins, leaving room for program names on the left.
            const m = { top: 12, right: W < 480 ? 96 : 108, bottom: 34, left: Math.round(Math.min(170, Math.max(96, W * 0.3))) };
            // Sets the overall height from the number of rows.
            const H = m.top + m.bottom + n * rowH;
            // Works out the usable width for the bars.
            const innerW = Math.max(40, W - m.left - m.right);
            // Builds an axis scale from the biggest program count.
            const sc = axisScale(Math.max.apply(null, programs.map(function (p) { return p.count; })));
            // Creates the SVG canvas with a viewBox matching width and height.
            const svg = svgEl('svg', { viewBox: '0 0 ' + W + ' ' + H, class: 'pop-svg', preserveAspectRatio: 'xMidYMid meet' });

            // Loops over the axis tick values.
            sc.ticks.forEach(function (t) {
                // Turns a tick value into a horizontal pixel position.
                const x = m.left + (t / sc.top) * innerW;
                // Draws the vertical grid line, darker at the zero line.
                svg.appendChild(svgEl('line', { x1: x, x2: x, y1: m.top, y2: m.top + n * rowH, class: t === 0 ? 'pop-axis' : 'pop-grid' }));
                // Draws the tick label under the axis.
                svg.appendChild(svgEl('text', { x: x, y: H - 12, 'text-anchor': 'middle', class: 'pop-tick' }, fmt(t)));
            // Closes the tick loop.
            });

            // Estimates how many label characters fit in the left margin.
            const maxChars = (m.left - 14) / 6.4;
            // Loops over the programs to draw each row.
            programs.forEach(function (p, i) {
                // Finds the top of this row.
                const y = m.top + i * rowH;
                // Centres the bar vertically inside the row.
                const by = y + (rowH - barH) / 2;
                // Calculates the bar length, with a minimum of 2 pixels.
                const w = Math.max(2, (p.count / sc.top) * innerW);

                // Draws the program name to the left of its bar.
                svg.appendChild(svgEl('text', { x: m.left - 10, y: by + barH / 2 + 4, 'text-anchor': 'end', class: 'pop-cat' }, truncate(p.label, maxChars)));

                // Creates the bar rectangle with rounded corners and the program colour.
                const bar = svgEl('rect', { x: m.left, y: by, width: w, height: barH, rx: 4, fill: p.color, class: 'pop-shape pop-bar-h', 'data-index': i });
                // Delays each bar animation so they appear one after another.
                bar.style.animationDelay = (i * 60) + 'ms';
                // Adds hover, focus, and click behaviour to this bar.
                bindShape(bar, i);
                // Adds the bar to the SVG.
                svg.appendChild(bar);

                // Draws the count and percentage just after the bar ends.
                const val = svgEl('text', { x: m.left + w + 8, y: by + barH / 2 + 4, 'text-anchor': 'start', class: 'pop-value pop-fade' }, fmt(p.count) + ' \u00b7 ' + p.percent + '%');
                // Delays the value label so it fades in after the bar.
                val.style.animationDelay = (350 + i * 60) + 'ms';
                // Adds the value label to the SVG.
                svg.appendChild(val);
            // Closes the loop over programs.
            });

            // Returns the finished SVG with no start animation callback.
            return { svg: svg, start: null };
        // Closes the drawHBar() function.
        }

        // Defines drawRadial(), which draws a pie chart or a donut chart.
        function drawRadial(donut) {
            // Sets the centre point and outer radius of the circle.
            const c = 200, outer = 176;
            // Uses a thick stroke for a donut ring, or a full fill for a pie.
            const sw = donut ? 70 : outer;
            // Calculates the ring radius so the stroke stays inside the canvas.
            const r = outer - sw / 2;
            // Calculates the circumference, used to measure each slice length.
            const C = 2 * Math.PI * r;
            // Places labels on the ring for donuts or closer in for pies.
            const labelR = donut ? r : outer * 0.64;
            // Adds a small gap between slices when there is more than one.
            const gap = programs.length > 1 ? (donut ? 1.5 : 2) : 0;

            // Creates the square SVG canvas with a 400 by 400 viewBox.
            const svg = svgEl('svg', { viewBox: '0 0 400 400', class: 'pop-svg pop-svg-radial', preserveAspectRatio: 'xMidYMid meet' });
            // Groups the slices and rotates them so they start at the top.
            const g = svgEl('g', { transform: 'rotate(-90 ' + c + ' ' + c + ')' });
            // For donuts, draws the grey track ring behind the coloured slices.
            if (donut) g.appendChild(svgEl('circle', { cx: c, cy: c, r: r, fill: 'none', 'stroke-width': sw, class: 'pop-track' }));

            // Starts an empty list that will hold each slice geometry.
            const slices = [];
            // Tracks how much of the circle has been used so far.
            let acc = 0;
            // Loops over the programs to draw one slice each.
            programs.forEach(function (p, i) {
                // Calculates this slice length from its share of the total.
                const len = (p.count / total) * C;
                // Remembers where this slice starts on the circle.
                const start = acc;
                // Moves the running position forward by this slice length.
                acc += len;
                // Starts creating the circle that draws this slice.
                const node = svgEl('circle', {
                    // Centres the circle and sets its colour and stroke thickness.
                    cx: c, cy: c, r: r, fill: 'none', stroke: p.color, 'stroke-width': sw,
                    // Starts with no visible dash, offset to this slice start.
                    'stroke-dasharray': '0 ' + C, 'stroke-dashoffset': -(start + gap / 2),
                    // Tags the slice as a chart shape with its program index.
                    class: 'pop-shape pop-slice', 'data-index': i
                // Closes the circle attribute object.
                });
                // Adds hover, focus, and click behaviour to this slice.
                bindShape(node, i);
                // Adds the slice to the rotated group.
                g.appendChild(node);
                // Saves the slice element and its start and length for animation.
                slices.push({ node: node, start: start, len: len });
            // Closes the loop over programs.
            });
            // Adds the group of slices to the SVG.
            svg.appendChild(g);

            // Notes that labels show percentages, plus counts on larger slices.
            // Labels (percentage, plus count on larger slices)
            // Loops over the programs again to place their labels.
            programs.forEach(function (p, i) {
                // Calculates this program fraction of all students.
                const frac = p.count / total;
                // Skips labels on slices under 5 percent because they would overlap.
                if (frac < 0.05) return; // too small to label: see legend / tooltip
                // Gets the saved geometry for this slice.
                const s = slices[i];
                // Finds the angle at the middle of the slice, in radians.
                const a = ((s.start + s.len / 2) / C) * 2 * Math.PI;
                // Calculates the label horizontal position.
                const lx = c + labelR * Math.sin(a);
                // Calculates the label vertical position.
                const ly = c - labelR * Math.cos(a);
                // Shows the count too when the slice is at least 8 percent.
                const withCount = frac >= 0.08;
                // Draws the percentage label, nudged when a count sits below it.
                const pct = svgEl('text', { x: lx, y: withCount ? ly - 2 : ly + 5, fill: p.ink, class: 'pop-slice-pct pop-fade' }, p.percent + '%');
                // Delays the percentage label so it fades in with the sweep.
                pct.style.animationDelay = '650ms';
                // Adds the percentage label to the SVG.
                svg.appendChild(pct);
                // Checks whether this slice is big enough to also show its count.
                if (withCount) {
                    // Draws the student count under the percentage.
                    const cnt = svgEl('text', { x: lx, y: ly + 14, fill: p.ink, class: 'pop-slice-cnt pop-fade' }, fmt(p.count));
                    // Delays the count label so it fades in with the sweep.
                    cnt.style.animationDelay = '650ms';
                    // Adds the count label to the SVG.
                    svg.appendChild(cnt);
                // Closes the withCount block.
                }
            // Closes the label loop.
            });

            // Checks whether this is a donut chart that needs centre text.
            if (donut) {
                // Draws the total number of students in the donut hole.
                svg.appendChild(svgEl('text', { x: c, y: c + 6, class: 'pop-center-value' }, fmt(total)));
                // Draws the words Total Students under that number.
                svg.appendChild(svgEl('text', { x: c, y: c + 28, class: 'pop-center-label' }, 'Total Students'));
            // Closes the donut centre block.
            }

            // Defines applySweep(), which reveals slices up to a fraction.
            function applySweep(t) {
                // Calculates how much of the circle should be drawn so far.
                const drawn = t * C;
                // Loops over each saved slice.
                slices.forEach(function (s) {
                    // Works out the visible length of this slice, never negative.
                    const vis = Math.max(0, Math.min(s.len - gap, drawn - s.start - gap / 2));
                    // Applies the dash length so the slice appears to grow.
                    s.node.setAttribute('stroke-dasharray', vis.toFixed(3) + ' ' + C.toFixed(3));
                // Closes the slice loop.
                });
            // Closes the applySweep() function.
            }

            // Returns the SVG plus a start function the caller can run.
            return {
                // Hands back the finished SVG element.
                svg: svg,
                // Defines start(), which plays the sweep animation when required.
                start: function (animate) {
                    // Without animation, instantly shows the complete circle.
                    if (!animate) { applySweep(1); return; }
                    // Records the animation start time.
                    const t0 = performance.now();
                    // Starts the sweep from empty.
                    applySweep(0);
                    // Defines the frame function used by requestAnimationFrame.
                    const tick = function (now) {
                        // Converts elapsed time into a progress value between 0 and 1.
                        const t = Math.min(1, (now - t0) / 850);
                        // Advances the sweep using the easing curve.
                        applySweep(easeOutCubic(t));
                        // Keeps requesting frames until the animation reaches the end.
                        if (t < 1) rafId = requestAnimationFrame(tick);
                    // Closes the frame function.
                    };
                    // Starts the animation by requesting the first frame.
                    rafId = requestAnimationFrame(tick);
                // Closes the start() function.
                }
            // Closes the returned object.
            };
        // Closes the drawRadial() function.
        }

        // Returns the chart pixel width, defaulting to 640 when hidden.
        function currentWidth() { return Math.round(chartEl.clientWidth) || 640; }

        // Defines showError(), which shows a warning when drawing fails.
        function showError() {
            // Clears whatever was previously in the chart area.
            chartEl.textContent = '';
            // Creates the warning box element.
            const box = document.createElement('div');
            // Styles it with the Bootstrap warning alert classes.
            box.className = 'alert alert-warning';
            // Explains the chart failed but the legend still has the figures.
            box.textContent = 'The chart could not be displayed. The figures are still available in the legend.';
            // Adds the warning box to the chart area.
            chartEl.appendChild(box);
        // Closes the showError() function.
        }

        // Defines draw(), which redraws the chart for the current type.
        function draw(animate) {
            // Cancels any animation frame still running from an earlier draw.
            cancelAnimationFrame(rafId);
            // Hides the tooltip before the chart changes.
            hideTip();
            // Clears any highlighted shape.
            setActive(null);
            // Enables animation only when asked and motion is not reduced.
            const animated = !!animate && !REDUCED_MOTION;
            // Turns on the no-anim class when animation is off.
            chartEl.classList.toggle('no-anim', !animated);
            // Starts the guarded drawing block so errors can be caught.
            try {
                // Measures the chart width and remembers it.
                lastW = currentWidth();
                // Declares the variable that will hold the drawing result.
                let result;
                // Draws the horizontal bar chart when that type is active.
                if (type === 'hbar') result = drawHBar(lastW);
                // Draws the pie chart when that type is active.
                else if (type === 'pie') result = drawRadial(false);
                // Draws the donut chart when that type is active.
                else if (type === 'donut') result = drawRadial(true);
                // Falls back to the vertical bar chart for every other type.
                else result = drawBar(lastW);

                // Clears the old chart from the page.
                chartEl.textContent = '';
                // Adds the newly drawn SVG chart.
                chartEl.appendChild(result.svg);
                // Writes a text description of the chart for screen readers.
                chartEl.setAttribute('aria-label', META[type].title + '. ' + programs.map(function (p) {
                    // Builds each program label, count, and percentage text.
                    return p.label + ': ' + fmt(p.count) + ' students (' + p.percent + '%)';
                // Joins those descriptions, finishing the aria-label attribute.
                }).join('; '));
                // Runs the chart start callback when the renderer provides one.
                if (result.start) result.start(animated);
            // Catches any error thrown while drawing.
            } catch (err) {
                // Logs the error to the console when a console exists.
                if (window.console) console.error('Program chart error:', err);
                // Shows the friendly warning instead of a broken chart.
                showError();
            // Closes the try and catch block.
            }
        // Closes the draw() function.
        }

        // Defines setType(), which switches the chart to another type.
        function setType(next, opts) {
            // Uses an empty options object when none was given.
            opts = opts || {};
            // Falls back to the default type when the name is unknown.
            if (!META[next]) next = DEFAULT_TYPE;
            // Stores the new chart type.
            type = next;

            // Loops over every chart-type dropdown option.
            options.forEach(function (o) {
                // Checks whether this option matches the active type.
                const on = o.getAttribute('data-chart-type') === type;
                // Updates aria-checked so screen readers know the selection.
                o.setAttribute('aria-checked', on ? 'true' : 'false');
                // Adds or removes the selected style class.
                o.classList.toggle('is-selected', on);
                // Updates the button label and icon only for the chosen option.
                if (on) {
                    // Shows the option label text on the dropdown button.
                    if (labelEl) labelEl.textContent = o.getAttribute('data-label') || '';
                    // Shows the option icon on the dropdown button.
                    if (iconEl) iconEl.className = 'fas ' + (o.getAttribute('data-icon') || 'fa-chart-bar');
                // Closes the if block for the chosen option.
                }
            // Closes the loop over the options.
            });
            // Updates the chart title for the new type.
            if (titleEl) titleEl.textContent = META[type].title;
            // Updates the chart description, including the total count.
            if (descEl) descEl.textContent = META[type].desc(total);
            // Saves the choice in session storage when persistence is on.
            if (opts.persist) storeType(type);

            // Cancels a pending fade timer before starting a new switch.
            clearTimeout(switchTimer);
            // Checks whether the fade option is on and motion is allowed.
            if (opts.fade && !REDUCED_MOTION) {
                // Adds the switching class that fades the chart stage out.
                stage.classList.add('is-switching');
                // Starts a short timer so the fade can finish first.
                switchTimer = setTimeout(function () {
                    // Draws the chart with animation after the short delay.
                    draw(true);
                    // Removes the switching class so the stage fades back in.
                    stage.classList.remove('is-switching');
                // Closes the timer callback and sets its 170 millisecond delay.
                }, 170);
            // Otherwise redraw immediately without the fade effect.
            } else {
                // Makes sure the switching class is removed.
                stage.classList.remove('is-switching');
                // Draws the chart right away with animation.
                draw(true);
            // Closes the if and else block.
            }
        // Closes the setType() function.
        }

        // Starts the dropdown menu section.
        /* ----- dropdown ----- */
        // Defines openMenu(), which shows the chart-type menu.
        function openMenu() {
            // Makes the menu visible.
            menu.hidden = false;
            // Tells assistive tech the menu is expanded.
            toggle.setAttribute('aria-expanded', 'true');
            // Finds the option for the current type, or the first option.
            const current = options.filter(function (o) { return o.getAttribute('data-chart-type') === type; })[0] || options[0];
            // Moves keyboard focus onto that option when it exists.
            if (current) current.focus();
        // Closes the openMenu() function.
        }
        // Defines closeMenu(), which hides the menu and can refocus the button.
        function closeMenu(returnFocus) {
            // Hides the menu.
            menu.hidden = true;
            // Tells assistive tech the menu is collapsed.
            toggle.setAttribute('aria-expanded', 'false');
            // Returns focus to the dropdown button when asked.
            if (returnFocus) toggle.focus();
        // Closes the closeMenu() function.
        }

        // Opens the menu on click, or closes it when already open.
        toggle.addEventListener('click', function () { menu.hidden ? openMenu() : closeMenu(false); });
        // Handles keys pressed on the dropdown button.
        toggle.addEventListener('keydown', function (e) {
            // ArrowDown opens the menu while it is closed.
            if (e.key === 'ArrowDown' && menu.hidden) { e.preventDefault(); openMenu(); }
        // Closes the button keydown listener.
        });
        // Loops over the menu options to add click and key handling.
        options.forEach(function (opt, idx) {
            // Handles a click on one chart-type option.
            opt.addEventListener('click', function () {
                // Switches the chart type, remembering it and fading the change.
                setType(opt.getAttribute('data-chart-type'), { persist: true, fade: true });
                // Closes the menu and returns focus to the button.
                closeMenu(true);
            // Closes the click listener.
            });
            // Handles arrow and other keys while focus is on an option.
            opt.addEventListener('keydown', function (e) {
                // Starts with no target option for the pressed key.
                let target = null;
                // ArrowDown moves to the next option, wrapping to the first.
                if (e.key === 'ArrowDown') target = options[(idx + 1) % options.length];
                // ArrowUp moves to the previous option, wrapping to the last.
                else if (e.key === 'ArrowUp') target = options[(idx - 1 + options.length) % options.length];
                // Home jumps to the first option.
                else if (e.key === 'Home') target = options[0];
                // End jumps to the last option.
                else if (e.key === 'End') target = options[options.length - 1];
                // Tab closes the menu and lets focus move on.
                else if (e.key === 'Tab') { closeMenu(false); return; }
                // Moves focus to the target option when one was chosen.
                if (target) { e.preventDefault(); target.focus(); }
            // Closes the option keydown listener.
            });
        // Closes the loop over the options.
        });
        // Listens for clicks anywhere on the page.
        document.addEventListener('click', function (e) {
            // Closes the menu when the click landed outside the dropdown.
            if (!menu.hidden && !select.contains(e.target)) closeMenu(false);
        // Closes the document click listener.
        });
        // Listens for key presses on the page.
        document.addEventListener('keydown', function (e) {
            // Escape closes the open menu and refocuses the button.
            if (e.key === 'Escape' && !menu.hidden) closeMenu(true);
        // Closes the document keydown listener.
        });

        // Starts the responsive redraw section for bar charts.
        /* ----- responsive redraw (bar charts depend on width) ----- */
        // Defines onResize(), called when the chart area changes width.
        function onResize() {
            // Redraws bar charts only when the width changed by over 8 pixels.
            if ((type === 'bar' || type === 'hbar') && Math.abs(currentWidth() - lastW) > 8) draw(false);
        // Closes the onResize() function.
        }
        // Uses ResizeObserver when the browser supports it.
        if ('ResizeObserver' in window) {
            // Watches the chart element and calls onResize on size changes.
            new ResizeObserver(onResize).observe(chartEl);
        // Otherwise falls back to the window resize event.
        } else {
            // Holds the debounce timer for the resize fallback.
            let resizeTimer = 0;
            // Waits for resizing to stop, then redraws after 120 milliseconds.
            window.addEventListener('resize', function () { clearTimeout(resizeTimer); resizeTimer = setTimeout(onResize, 120); });
        // Closes the resize fallback block.
        }

        // Starts the chart boot sequence.
        /* ----- go ----- */
        // Reads the chart type saved earlier in this session.
        const stored = readStoredType();
        // Starts with the saved type when valid, otherwise the default bar chart.
        setType(META[stored] ? stored : DEFAULT_TYPE, { persist: false, fade: false });
    // Closes the initProgramChart() function.
    }

    // Defines boot(), which starts every chart found on the page.
    function boot() {
        // Loops over each element marked as a program chart.
        document.querySelectorAll('[data-program-chart]').forEach(function (root) {
            // Tries to set up that chart.
            try { initProgramChart(root); }
            // Logs a failure so one broken chart does not stop the others.
            catch (err) { if (window.console) console.error('Program chart failed to start:', err); }
        // Closes the chart loop.
        });
    // Closes the boot() function.
    }

    // Waits for the page to load before booting, or boots right away.
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    // Runs boot() immediately when the page is already loaded.
    else boot();
// Closes and runs the dashboard script function.
})();