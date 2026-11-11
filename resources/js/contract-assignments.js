export function initializeContractAssignments(root) {
    const search = root.querySelector('[data-assignment-search]');
    const role = root.querySelector('[data-assignment-role]');
    const rows = [...root.querySelectorAll('[data-assignment-row]')];
    const empty = root.querySelector('[data-assignment-empty]');
    const summary = root.querySelector('[data-assignment-summary]');

    const updateDuration = row => {
        const checkbox = row.querySelector('input[name="client_ids[]"]');
        const duration = row.querySelector('[data-assignment-duration]');
        if (!duration) return;

        const selected = checkbox.checked && !checkbox.disabled;
        const custom = selected && duration.value === 'custom';
        const dates = row.querySelector('[data-assignment-custom-dates]');
        const start = row.querySelector('[data-assignment-start]');
        const end = row.querySelector('[data-assignment-end]');
        duration.disabled = !selected;
        duration.required = selected;
        dates.hidden = !custom;
        [start, end].forEach(input => { input.disabled = !custom; input.required = custom; });
        end.min = start.value;
        row.querySelector('[data-assignment-inherited]').hidden = !selected || custom;
    };

    const update = () => {
        const query = search.value.trim().toLocaleLowerCase();
        let visible = 0;

        rows.forEach(row => {
            updateDuration(row);
            row.hidden = Boolean((role.value && row.dataset.role !== role.value)
                || !row.dataset.search.toLocaleLowerCase().includes(query));
            if (!row.hidden) visible++;
        });

        const selected = root.querySelectorAll('input[name="client_ids[]"]:checked:not(:disabled)').length;
        empty.hidden = visible > 0;
        summary.textContent = `${selected} selected · ${visible} of ${rows.length} accounts shown`;
    };

    root.querySelector('[data-assignment-filters]').hidden = false;
    search.addEventListener('input', update);
    role.addEventListener('change', update);
    root.addEventListener('change', update);
    root.addEventListener('input', event => {
        if (event.target.matches('[data-assignment-start]')) updateDuration(event.target.closest('[data-assignment-row]'));
    });
    root.addEventListener('invalid', event => {
        const row = event.target.closest('[data-assignment-row]');
        if (row?.hidden) {
            search.value = '';
            role.value = '';
            update();
        }
    }, true);
    update();
}

document.querySelectorAll('[data-contract-assignments]').forEach(initializeContractAssignments);
