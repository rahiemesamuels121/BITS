document.querySelectorAll('[data-contract-term]').forEach(root => {
    const duration = root.querySelector('[data-contract-duration]');
    const dates = root.querySelector('[data-contract-dates]');
    const start = dates.querySelector('[name="starts_at"]');
    const end = dates.querySelector('[name="ends_at"]');
    const update = () => {
        const indefinite = duration.value === '1';
        dates.hidden = indefinite;
        [start, end].forEach(input => { input.disabled = indefinite; input.required = !indefinite; });
        end.min = start.value;
    };
    duration.addEventListener('change', update);
    start.addEventListener('input', update);
    update();
});
