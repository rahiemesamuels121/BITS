export function tableOptionImporter(sources, url, apply, applyLive, currentSource = null) {
    const make = (tag, text = '') => { const node = document.createElement(tag); node.textContent = text; return node; };
    const box = make('details'); box.className = 'fb-table-importer'; box.open = Boolean(currentSource);
    box.append(make('summary', 'Populate from table'));
    box.append(make('p', 'Choose a table, display column, and filters for the records to show. Labels are displayed; row IDs are submitted.'));
    const fillSelect = (control, choices) => {
        control.replaceChildren(...Object.entries(choices).map(([value, text]) => {
            const option = make('option', text); option.value = value; return option;
        }));
    };
    const select = (label, choices, parent = box) => {
        const wrap = make('label', label); const control = make('select');
        fillSelect(control, choices); wrap.append(control); parent.append(wrap); return control;
    };
    const source = select('Source table', Object.fromEntries(Object.entries(sources).map(([key, item]) => [key, item.label])));
    if (currentSource && Object.hasOwn(sources, currentSource.source)) source.value = currentSource.source;
    const column = select('Display column', sources[source.value].columns);
    if (currentSource?.column && Object.hasOwn(sources[source.value].columns, currentSource.column)) column.value = currentSource.column;
    const filterColumns = () => sources[source.value].filter_columns ?? Object.fromEntries(
        Object.entries(sources[source.value].columns).map(([key, label]) => [key, {label, type: 'text'}]),
    );
    const filterSection = make('fieldset'); filterSection.className = 'fb-source-filters';
    filterSection.append(make('legend', 'Filter displayed records'));
    filterSection.append(make('p', 'All filters must match. Combine up to 5 rules.'));
    const filters = make('div'); filters.dataset.sourceFilters = ''; filterSection.append(filters);
    const filterSummary = make('p'); filterSummary.className = 'fb-source-filter-summary'; filterSection.append(filterSummary);
    const add = make('button', '+ Add filter'); add.type = 'button'; add.className = 'admin-button'; filterSection.append(add); box.append(filterSection);
    const preview = make('button', 'Preview options'); preview.type = 'button'; preview.className = 'admin-button'; box.append(preview);
    const status = make('p'); status.setAttribute('role', 'status'); status.setAttribute('aria-live', 'polite'); box.append(status);
    const list = make('ol'); list.className = 'fb-source-preview'; box.append(list);
    const use = make('button', 'Use these options'); use.type = 'button'; use.className = 'admin-button admin-button-primary'; use.disabled = true; box.append(use);
    const useLive = make('button', 'Use live database options'); useLive.type = 'button'; useLive.className = 'admin-button'; useLive.hidden = typeof applyLive !== 'function'; box.append(useLive);
    box.append(make('p', 'Use live database options to keep these filters applied to current records. Then save and publish the form. “Use these options” imports a snapshot instead.'));
    let options = []; let labels = {}; let generation = 0;
    const configuration = () => ({
        source: source.value, column: column.value,
        filters: [...filters.children].map(row => ({
            column: row.querySelector('[data-filter-column]').value,
            operator: row.querySelector('[data-filter-operator]').value,
            value: row.querySelector('[data-filter-value]').value,
        })),
    });
    const invalidate = () => {
        generation++; options = []; use.disabled = true; list.replaceChildren(); status.textContent = ''; preview.disabled = false;
        const count = filters.children.length;
        add.disabled = count >= 5;
        filterSummary.textContent = count ? `${count} ${count === 1 ? 'filter' : 'filters'} applied together.` : 'No filters: all records with a display value are included.';
    };
    const validFilters = () => {
        const invalid = [...filters.querySelectorAll('input, select')].find(control => !control.checkValidity());
        if (!invalid) return true;
        invalid.reportValidity(); status.textContent = 'Complete each filter before previewing or applying it.'; return false;
    };
    const addFilter = (saved = null) => {
        if (filters.children.length >= 5) return;
        const row = make('div'); row.className = 'fb-source-filter';
        const fields = filterColumns();
        const field = select('Filter column', Object.fromEntries(Object.entries(fields).map(([key, metadata]) => [key, metadata.label])), row);
        field.dataset.filterColumn = ''; field.required = true;
        if (saved && Object.hasOwn(fields, saved.column)) field.value = saved.column;
        const operator = select('Match', {}, row); operator.dataset.filterOperator = ''; operator.required = true;
        const valueWrap = make('label', 'Value'); row.append(valueWrap);
        const renderValue = (value = '', match = 'equals') => {
            const metadata = fields[field.value];
            const operators = metadata.type === 'date'
                ? {equals: 'On', not_equals: 'Not on', greater_than: 'After', greater_or_equal: 'On or after', less_than: 'Before', less_or_equal: 'On or before'}
                : ['select', 'number'].includes(metadata.type)
                    ? {equals: 'Equals', not_equals: 'Does not equal'}
                    : {equals: 'Equals', not_equals: 'Does not equal', contains: 'Contains'};
            fillSelect(operator, operators); operator.value = match;
            if (!operator.value) {
                const placeholder = make('option', 'Choose a match'); placeholder.value = ''; operator.prepend(placeholder); operator.value = '';
            }
            const control = make(metadata.type === 'select' ? 'select' : 'input');
            if (metadata.type === 'select') {
                fillSelect(control, metadata.choices ?? {});
                const placeholder = make('option', 'Choose a value'); placeholder.value = ''; control.prepend(placeholder);
                // Preserve deleted choices visibly, so reopening cannot silently change a saved filter.
                if (value && !Object.hasOwn(metadata.choices ?? {}, value)) {
                    const missing = make('option', `Unavailable value (${value})`); missing.value = value; control.append(missing);
                    control.setCustomValidity('Choose an available value.');
                    control.addEventListener('change', () => control.setCustomValidity(''));
                }
            } else {
                control.type = metadata.type === 'date' ? 'date' : metadata.type === 'number' ? 'number' : 'text';
                control.maxLength = 200;
                if (metadata.type === 'number') { control.min = '0'; control.step = '1'; }
            }
            control.dataset.filterValue = ''; control.required = true; control.value = value;
            valueWrap.replaceChildren(document.createTextNode('Value'), control);
        };
        renderValue(saved?.value ?? '', saved?.operator ?? 'equals');
        field.addEventListener('change', () => { renderValue(); invalidate(); });
        const remove = make('button', 'Remove filter'); remove.type = 'button'; remove.className = 'admin-button';
        remove.addEventListener('click', () => { row.remove(); invalidate(); }); row.append(remove);
        row.addEventListener('input', invalidate); row.addEventListener('change', invalidate);
        filters.append(row); invalidate();
    };
    add.addEventListener('click', () => addFilter());
    source.addEventListener('change', () => { fillSelect(column, sources[source.value].columns); filters.replaceChildren(); invalidate(); });
    column.addEventListener('change', invalidate);
    (currentSource?.filters ?? []).forEach(addFilter);
    invalidate();
    preview.addEventListener('click', async () => {
        if (!validFilters()) return;
        invalidate(); const request = generation; preview.disabled = true; status.textContent = 'Loading options…';
        try {
            const response = await fetch(url, {
                method: 'POST', headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''},
                body: JSON.stringify(configuration()),
            });
            const data = await response.json();
            if (request !== generation) return;
            if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat()[0] || data.message || 'Unable to load options.');
            options = data.options; labels = data.option_labels ?? {};
            list.replaceChildren(...options.map(option => make('li', `${labels[option] ?? option} (ID: ${option})`)));
            status.textContent = options.length ? `${options.length} matching options. Review them before applying.` : 'No matching values. Change your filters or link the live source to include future matches.';
            use.disabled = !options.length;
        } catch (error) {
            if (request === generation) status.textContent = error.message || 'Unable to load options. Please retry.';
        } finally {
            if (request === generation) preview.disabled = false;
        }
    });
    use.addEventListener('click', () => { if (options.length) apply([...options], {...labels}); });
    useLive.addEventListener('click', () => { if (validFilters() && typeof applyLive === 'function') applyLive(configuration()); });
    return box;
}
