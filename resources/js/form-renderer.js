export const element = (tag, className = '', text) => {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = String(text);
    return node;
};

const scalar = value => typeof value === 'boolean' ? (value ? '1' : '0') : (value === null || value === undefined ? '' : String(value));

export function fieldVisible(field, answers, visibility) {
    const rule = field.conditional_rules;
    if (!rule) return true;
    const answer = answers[rule.field];
    if (!visibility[rule.field] || answer === undefined || answer === null || answer === '' || (Array.isArray(answer) && !answer.length)) return false;
    const expected = typeof answer === 'number' && scalar(rule.value).trim() !== '' && Number.isFinite(Number(rule.value)) ? scalar(Number(rule.value)) : scalar(rule.value);
    const values = Array.isArray(answer) ? answer.map(scalar) : [scalar(answer)];
    if (rule.operator === 'equals') return values.includes(expected);
    if (rule.operator === 'not_equals') return !values.includes(expected);
    if (rule.operator === 'contains') return Array.isArray(answer) ? values.includes(expected) : values[0].includes(expected);
    return false;
}

export function initialiseRenderer(form) {
    if (form.dataset.rendererReady) return;
    let schema;
    try { schema = JSON.parse(form.dataset.schema); } catch { return; }
    form.dataset.rendererReady = 'true';
    const wrappers = [...form.querySelectorAll('[data-render-field]')];
    const fields = new Map(schema.fields.map(field => [field.field_key, field]));
    const answers = () => {
        const result = {};
        wrappers.forEach(wrapper => {
            const field = fields.get(wrapper.dataset.renderField);
            const controls = [...wrapper.querySelectorAll('input, textarea, select')];
            if (field.type === 'checkboxes') result[field.field_key] = controls.filter(input => input.checked).map(input => input.value);
            else if (['radio','boolean'].includes(field.type)) result[field.field_key] = controls.find(input => input.checked)?.value ?? '';
            else { const value = controls[0]?.value ?? ''; result[field.field_key] = field.type === 'number' && value !== '' && Number.isFinite(Number(value)) ? Number(value) : value; }
        });
        return result;
    };
    const update = () => {
        const values = answers();
        const visible = {};
        wrappers.forEach(wrapper => {
            const field = fields.get(wrapper.dataset.renderField);
            const key = field.field_key;
            visible[key] = fieldVisible(field, values, visible);
            wrapper.hidden = !visible[key];
            const controls = [...wrapper.querySelectorAll('input, textarea, select')];
            controls.forEach((control, index) => {
                control.disabled = !visible[key];
                control.required = !!field.required && visible[key] && (field.type !== 'checkboxes' || (index === 0 && !values[key].length));
            });
        });
    };
    const validateFile = input => {
        const wrapper = input.closest('[data-render-field]');
        const field = fields.get(wrapper?.dataset.renderField);
        if (!field || field.type !== 'file') return;
        const file = input.files[0];
        if (!file) return;
        const rules = field.validation_rules ?? {};
        const allowed = rules.allowed_types?.length ? rules.allowed_types : ['pdf','jpg','png'];
        const ext = file.name.split('.').at(-1).toLowerCase();
        if (!allowed.includes(ext)) input.setCustomValidity(`Allowed file types: ${allowed.join(', ')}.`);
        else if (file.size > (rules.max_size || 2048) * 1024) input.setCustomValidity(`Maximum file size is ${rules.max_size || 2048} KB.`);
    };
    const validatePhone = input => {
        const field = fields.get(input.closest('[data-render-field]')?.dataset.renderField);
        if (field?.type === 'phone' && input.value && !/^[+0-9() .\-]{5,50}$/.test(input.value)) input.setCustomValidity(field.validation_rules?.message || 'Enter a phone number using digits, spaces, +, parentheses, or hyphens.');
    };
    form.addEventListener('input', event => {
        event.target.closest('[data-render-field]')?.querySelectorAll('input,textarea,select').forEach(input => input.setCustomValidity(''));
        update();
        if (event.target.matches('input')) validatePhone(event.target);
    });
    form.addEventListener('change', event => {
        event.target.closest('[data-render-field]')?.querySelectorAll('input,textarea,select').forEach(input => input.setCustomValidity(''));
        if (event.target.type === 'file') validateFile(event.target);
        update();
        if (event.target.matches('input')) validatePhone(event.target);
    });
    form.addEventListener('invalid', event => {
        const field = fields.get(event.target.closest('[data-render-field]')?.dataset.renderField);
        if (field?.validation_rules?.message) event.target.setCustomValidity(field.validation_rules.message);
    }, true);
    form.addEventListener('submit', event => {
        update();
        form.querySelectorAll('input[type="file"]:not(:disabled)').forEach(validateFile);
        form.querySelectorAll('input[type="tel"]:not(:disabled)').forEach(validatePhone);
        if (!form.reportValidity()) { event.preventDefault(); return; }
        if (form.dataset.preview === 'true') {
            event.preventDefault();
            const feedback = form.querySelector('[data-preview-feedback]');
            feedback.textContent = 'Preview validated. No response was saved.';
            feedback.setAttribute('tabindex', '-1');
            feedback.focus();
        } else {
            const button = form.querySelector('[type="submit"]');
            button.disabled = true;
            button.textContent = 'Submitting…';
        }
    });
    wrappers.forEach(wrapper => {
        const hint = wrapper.querySelector('.fr-help');
        if (hint) wrapper.querySelectorAll('input, select, textarea').forEach(input => input.setAttribute('aria-describedby', hint.id));
        const error = wrapper.querySelector('.fr-field-error');
        if (error) {
            error.id = `error-${wrapper.dataset.renderField}`;
            wrapper.querySelectorAll('input,select,textarea').forEach(input => { input.setAttribute('aria-invalid','true'); input.setAttribute('aria-describedby',[hint?.id,error.id].filter(Boolean).join(' ')); });
        }
    });
    update();
}

export function buildPreview(schema, settings) {
    const form = element('form', `form-renderer fr-accent-${['indigo','lime','blue'].includes(settings.accent) ? settings.accent : 'indigo'} fr-spacing-${settings.spacing} fr-layout-${settings.layout}`);
    form.dataset.formRenderer = '';
    form.dataset.preview = 'true';
    form.dataset.schema = JSON.stringify(schema);
    if (settings.show_title) {
        const header = element('header', 'fr-header');
        if (/^https:\/\//i.test(settings.logo_url ?? '')) { const logo = element('img','fr-logo'); logo.src = settings.logo_url; logo.alt = 'Form logo'; logo.referrerPolicy = 'no-referrer'; header.append(logo); }
        header.append(element('h1','',schema.title || 'Untitled form'));
        if (schema.description) header.append(element('p','',schema.description));
        header.append(element('p','fr-required-note','Questions marked * are required.'));
        form.append(header);
    }
    const list = element('div','fr-fields');
    schema.fields.forEach(field => {
        const wrapper = element('div','fr-field');
        wrapper.dataset.renderField = field.field_key;
        const rules = field.validation_rules ?? {};
        if (['heading','description'].includes(field.type)) {
            wrapper.classList.add('fr-presentation');
            wrapper.append(element(field.type === 'heading' ? 'h2' : 'p','fr-instruction',field.label));
            if (field.help_text) wrapper.append(element('p','',field.help_text));
            list.append(wrapper);
            return;
        }
        const choices = ['radio','checkboxes','boolean'].includes(field.type);
        const container = choices ? element('fieldset') : wrapper;
        const label = element(choices ? 'legend' : 'label','',field.label || 'Untitled question');
        const id = `preview-answer-${field.field_key}`;
        if (!choices) label.htmlFor = id;
        if (field.required) label.append(element('span','fr-required',' *'));
        container.append(label);
        const name = `answers[${field.field_key}]`;
        if (choices) {
            const options = field.type === 'boolean' ? [['1','Yes'], ['0','No']] : (field.options ?? []).map(value => [value,value]);
            options.forEach(([value,text]) => {
                const row = element('label','fr-choice');
                const input = element('input');
                input.type = field.type === 'checkboxes' ? 'checkbox' : 'radio';
                input.name = name + (field.type === 'checkboxes' ? '[]' : '');
                input.value = value;
                input.checked = field.type === 'checkboxes' ? (Array.isArray(field.default_value) && field.default_value.includes(value)) : field.default_value !== null && field.default_value !== undefined && scalar(field.default_value) === value;
                row.append(input,element('span','',text));
                container.append(row);
            });
            wrapper.append(container);
        } else {
            const input = element(field.type === 'textarea' ? 'textarea' : field.type === 'select' ? 'select' : 'input');
            input.id = id; input.name = name;
            if (field.type === 'select') {
                const empty = element('option','',field.placeholder || 'Select an option'); empty.value = ''; input.append(empty);
                (field.options ?? []).forEach(value => { const option = element('option','',field.option_labels?.[value] ?? value); option.value = value; input.append(option); });
            } else if (field.type === 'textarea') input.rows = 4;
            else input.type = ({ phone:'tel', file:'file', number:'number', email:'email', date:'date', time:'time' })[field.type] || 'text';
            if (field.type !== 'file') input.value = field.default_value === null || field.default_value === undefined ? '' : scalar(field.default_value);
            input.placeholder = field.placeholder || '';
            if (field.type === 'number') { input.step = 'any'; input.min = rules.min ?? -1e15; input.max = rules.max ?? 1e15; }
            if (['text','textarea','email','phone'].includes(field.type)) { input.minLength = rules.min_length ?? 0; input.maxLength = rules.max_length ?? (field.type === 'textarea' ? 10000 : field.type === 'email' ? 254 : field.type === 'phone' ? 50 : 1000); }
            if (field.type === 'file') input.accept = (rules.allowed_types?.length ? rules.allowed_types : ['pdf','jpg','png']).map(ext => `.${ext}`).join(',');
            wrapper.append(input);
        }
        if (field.help_text) { const help = element('p','fr-help',field.help_text); help.id = `preview-help-${field.field_key}`; wrapper.append(help); }
        list.append(wrapper);
    });
    form.append(list);
    const footer = element('div','fr-footer');
    const submit = element('button','admin-button admin-button-primary fr-submit',settings.submit_text || 'Submit response'); submit.type = 'submit';
    const feedback = element('p','','Preview mode — responses will not be saved.'); feedback.dataset.previewFeedback = ''; feedback.setAttribute('role','status');
    footer.append(submit,feedback); form.append(footer);
    initialiseRenderer(form);
    return form;
}

document.querySelectorAll('[data-form-renderer]').forEach(initialiseRenderer);
