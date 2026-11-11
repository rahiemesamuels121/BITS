import { tableOptionImporter } from './table-option-importer.js';
﻿import { buildPreview, element as el } from './form-renderer.js';
import { hidePageLoading, showPageLoading } from './page-loading.js';

const root = document.querySelector('[data-form-builder]');
if (root) initialiseBuilder(root);

function initialiseBuilder(root) {
    const initial = JSON.parse(document.querySelector('#form-builder-data').textContent);
    const state = { ...initial.definition, settings: initial.settings, revision: initial.revision };
    state.fields = state.fields.map(field => ({...field, options:field.options ?? [], validation_rules:{...(field.validation_rules ?? {})}}));
    const types = { text:['Aa','Short Text'], textarea:['¶','Long Text'], number:['#','Number'], email:['@','Email'], phone:['☎','Phone Number'], date:['▦','Date'], time:['◷','Time'], select:['⌄','Dropdown'], radio:['◉','Radio Buttons'], checkboxes:['☑','Checkboxes'], boolean:['✓','Yes / No'], file:['↑','File Upload'], heading:['H','Section Heading'], description:['≡','Instructions'] };
    const presentation = ['heading','description'];
    const choiceTypes = ['select','radio','checkboxes'];
    const safeTypes = ['pdf','jpg','jpeg','png','webp','txt','csv','docx','xlsx'];
    const canvas = root.querySelector('[data-builder-fields]');
    const propertyContent = root.querySelector('[data-property-content]');
    const titleInput = root.querySelector('[data-builder-title]');
    const descriptionInput = root.querySelector('[data-builder-description]');
    const errors = root.querySelector('[data-builder-errors]');
    const saveStatus = root.querySelector('[data-save-state]');
    const settingsDialog = document.querySelector('#fb-settings');
    const previewDialog = document.querySelector('#fb-preview');
    const publishedDialog = document.querySelector('#fb-published');
    const saveButton = root.querySelector('[data-builder-save]');
    const publishButton = root.querySelector('[data-builder-publish]');
    let selectedKey = state.fields[0]?.field_key ?? null;
    let busy = false;
    let lastOpener;
    const clone = value => JSON.parse(JSON.stringify(value));
    const fingerprint = () => JSON.stringify({ title:state.title, description:state.description ?? '', fields:state.fields, settings:state.settings });
    let saved = fingerprint();
    const dirty = () => fingerprint() !== saved;
    const key = () => `q_${crypto.getRandomValues(new Uint32Array(2)).join('_')}`;
    const selected = () => state.fields.find(field => field.field_key === selectedKey);
    const setPanel = name => {
        root.querySelector('[data-builder-active-panel]').dataset.builderActivePanel = name;
        root.querySelectorAll('[data-builder-panel]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.builderPanel === name)));
    };
    const markChanged = () => {
        saveStatus.textContent = dirty() ? 'Unsaved changes' : 'All draft changes saved';
        saveStatus.classList.toggle('is-dirty', dirty());
        root.querySelector('[data-builder-title-label]').textContent = state.title || 'Untitled form';
    };
    const changed = () => { markChanged(); renderCanvas(); };
    const showError = error => {
        errors.replaceChildren(el('strong','',error.message || 'Unable to complete this action.'));
        if (error.errors) {
            const list = el('ul');
            Object.entries(error.errors).forEach(([field,messages]) => messages.forEach(message => list.append(el('li','',`${field}: ${message}`))));
            errors.append(list);
        }
        errors.hidden = false;
        errors.scrollIntoView({ block:'nearest', behavior:'auto' });
    };
    const api = async (url, method, body) => {
        const response = await fetch(url, { method, credentials:'same-origin', headers:{ 'Accept':'application/json', 'Content-Type':'application/json', 'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content }, body:JSON.stringify(body) });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(response.status === 401 || response.status === 419 ? 'Your session expired. Keep this draft open and sign in again in another tab, then retry.' : data.message || `Request failed (${response.status}). Please try again.`);
            error.errors = data.errors;
            throw error;
        }
        return data;
    };
    const setBusy = (value, message = 'Saving your form…') => {
        busy = value;
        saveButton.disabled = value;
        publishButton.disabled = value;
        saveButton.textContent = value ? 'Saving…' : 'Save Draft';
        if (value) showPageLoading(message);
        else hidePageLoading();
    };
    const save = async () => {
        if (busy) return false;
        errors.hidden = true;
        const savedFingerprint = fingerprint();
        const payload = clone({ title:state.title, description:state.description, fields:state.fields, settings:state.settings, revision:state.revision });
        setBusy(true);
        try {
            const result = await api(initial.save_url, 'PUT', payload);
            state.revision = result.revision;
            saved = savedFingerprint;
            markChanged();
            if (!dirty()) saveStatus.textContent = `Draft saved at ${new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}`;
            return true;
        } catch (error) { showError(error); return false; }
        finally { setBusy(false); }
    };
    const open = (dialog, opener) => { lastOpener = opener; dialog.showModal(); document.documentElement.classList.add('admin-dialog-open'); };
    [settingsDialog,previewDialog,publishedDialog].forEach(dialog => {
        dialog.querySelectorAll('[data-fb-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
        dialog.addEventListener('close', () => { document.documentElement.classList.remove('admin-dialog-open'); lastOpener?.focus(); });
        dialog.addEventListener('click', event => {
            if (event.target !== dialog) return;
            const bounds = dialog.getBoundingClientRect();
            if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
        });
    });

    function renderCanvas() {
        canvas.replaceChildren();
        root.querySelector('[data-builder-empty]').hidden = state.fields.length > 0;
        state.fields.forEach((field,index) => {
            const card = el('article',`fb-field-card${field.field_key === selectedKey ? ' is-selected' : ''}`);
            card.dataset.fieldKey = field.field_key; card.tabIndex = 0; card.draggable = true;
            card.setAttribute('aria-label', `Question ${index + 1}: ${field.label}. Press Enter to edit.`);
            const top = el('div','fb-field-card-top');
            top.append(el('span','',`${String(index + 1).padStart(2,'0')} · ${types[field.type][1]}`));
            const actions = el('div','fb-card-actions');
            [['edit','Edit','Edit question'],['up','↑','Move up'],['down','↓','Move down'],['copy','⧉','Duplicate question'],['delete','×','Delete question']].forEach(([action,text,label]) => {
                const button = el('button','',text); button.type = 'button'; button.dataset.fieldAction = action; button.setAttribute('aria-label',label);
                button.disabled = (action === 'up' && index === 0) || (action === 'down' && index === state.fields.length - 1);
                actions.append(button);
            });
            top.append(actions); card.append(top);
            const label = el('h3','',field.label || 'Untitled question'); if (field.required && !presentation.includes(field.type)) label.append(el('span','','*')); card.append(label);
            if (choiceTypes.includes(field.type) || field.type === 'boolean') {
                const options = field.type === 'boolean' ? ['Yes','No'] : field.options ?? [];
                if (field.type === 'select' && field.option_source) card.append(el('div','fb-option-preview','Live database options'));
                options.forEach(option => card.append(el('div','fb-option-preview',`${field.type === 'checkboxes' ? '□' : '○'}  ${field.option_labels?.[option] ?? option}`)));
            } else if (!presentation.includes(field.type)) card.append(el('div',`fb-control-preview${field.type === 'textarea' ? ' is-paragraph' : ''}`, field.type === 'file' ? '↑ Choose a file' : field.placeholder || (field.type === 'date' ? 'mm/dd/yyyy' : field.type === 'time' ? '--:--' : 'Your answer…')));
            if (field.help_text) card.append(el('p','fb-card-help',field.help_text));
            if (field.conditional_rules) card.append(el('span','fb-conditional-label','Conditional visibility'));
            canvas.append(card);
        });
    }

    function renderProperties() {
        const field = selected();
        propertyContent.replaceChildren();
        if (!field) { propertyContent.append(el('div','fb-property-empty','Select a question to edit its properties.')); return; }
        const panel = el('div','fb-property-form');
        const index = state.fields.indexOf(field);
        field.validation_rules ??= {};
        const input = (label, type, value, callback, options = {}) => {
            const wrap = el('label','',label);
            const control = el(type === 'textarea' ? 'textarea' : 'input');
            if (type !== 'textarea') control.type = type;
            else control.rows = 3;
            control.value = value ?? '';
            Object.assign(control, options);
            control.addEventListener('input', () => { callback(control.value,control); changed(); });
            wrap.append(control); panel.append(wrap); return control;
        };
        const select = (label, choices, value, callback, multiple = false) => {
            const wrap = el('label','',label); const control = el('select'); control.multiple = multiple;
            choices.forEach(([key,text]) => { const option = el('option','',text); option.value = key; option.selected = multiple ? (Array.isArray(value) && value.includes(key)) : String(value ?? '') === key; control.append(option); });
            control.addEventListener('change', () => { callback(multiple ? [...control.selectedOptions].map(option => option.value) : control.value); changed(); });
            wrap.append(control); panel.append(wrap); return control;
        };
        const toggle = (label, value, callback, disabled = false) => {
            const wrap = el('label','fm-toggle'); const control = el('input'); control.type = 'checkbox'; control.checked = !!value; control.disabled = disabled;
            control.addEventListener('change', () => { callback(control.checked); changed(); }); wrap.append(control,el('span','',label)); panel.append(wrap); return control;
        };
        input(field.type === 'description' ? 'Instruction text' : 'Field Label', 'text', field.label, value => field.label = value, {maxLength:255});
        if (['text','textarea','number','email','phone','select'].includes(field.type)) input('Placeholder','text',field.placeholder,value => field.placeholder = value || null,{maxLength:255});
        input('Help Text','textarea',field.help_text,value => field.help_text = value || null,{maxLength:2000});
        if (!presentation.includes(field.type)) toggle('Required question',field.required,value => field.required = value);
        if (field.type === 'select' && (!field.option_source || field.option_source.source === 'users')) {
            toggle('Link responses to the selected client', state.settings.client_reference_field === field.field_key, enabled => {
                state.settings.client_reference_field = enabled ? field.field_key : null;
                if (enabled) field.required = false;
                renderProperties();
            });
            panel.append(el('p','fb-property-note','Use account IDs as option values. A selected client sees the response only through their assigned contract. Respondents can remain anonymous.'));
        }
        if (choiceTypes.includes(field.type)) {
            panel.append(el('h3','','Answer options'));
            if (field.type === 'select' && Object.keys(initial.option_sources ?? {}).length) {
                panel.append(tableOptionImporter(initial.option_sources, initial.option_sources_url, (options, labels) => {
                    field.option_source = null;
                    field.options = options;
                    field.option_labels = labels;
                    field.default_value = null;
                    changed();
                    renderCanvas();
                    renderProperties();
                }, optionSource => {
                    field.option_source = optionSource;
                    field.options = [];
                    field.option_labels = {};
                    field.default_value = null;
                    changed();
                    renderCanvas();
                    renderProperties();
                }, field.option_source));
            }

            const options = el('div','fb-options-list');
            if (field.option_source) {
                panel.append(el('p','fb-property-note','Current matching database records appear whenever the public form is opened. Up to 100 options are supported; use filters to narrow larger lists.'));
                const manual = el('button','admin-button','Switch to manual options'); manual.type = 'button';
                manual.addEventListener('click', () => {
                    field.option_source = null;
                    field.options = ['Option 1','Option 2'];
                    field.option_labels = {};
                    changed();
                    renderProperties();
                });
                panel.append(manual);
            } else {
                (field.options ?? []).forEach((option,position) => {
                    const row = el('div','fb-option-editor'); const control = el('input'); control.type = 'text'; control.value = option; control.maxLength = 200; control.setAttribute('aria-label',`Answer option ${position+1}`);
                    control.addEventListener('input', () => {
                        const previous = field.options[position]; field.options[position] = control.value;
                        state.fields.forEach(dependent => { if (dependent.conditional_rules?.field === field.field_key && dependent.conditional_rules.value === previous) dependent.conditional_rules.value = control.value; });
                        if (Array.isArray(field.default_value)) field.default_value = field.default_value.map(value => value === previous ? control.value : value);
                        else if (field.default_value === previous) field.default_value = control.value;
                        changed();
                    });
                    control.addEventListener('change', () => {
                        const defaults = panel.querySelector('[data-default-options]');
                        if (!defaults) return;
                        defaults.replaceChildren();
                        const choices = field.options.map(value => [value,field.option_labels?.[value] ?? value]);
                        if (field.type !== 'checkboxes') choices.unshift(['','No default']);
                        choices.forEach(([value,text]) => {
                            const option = el('option','',text); option.value = value;
                            option.selected = Array.isArray(field.default_value) ? field.default_value.includes(value) : String(field.default_value ?? '') === value;
                            defaults.append(option);
                        });
                    });
                    if (field.option_labels && Object.hasOwn(field.option_labels, option)) {
                        control.readOnly = true;
                        control.setAttribute('aria-label', `Submitted ID ${option}`);
                        const label = el('input'); label.type = 'text'; label.value = field.option_labels[option]; label.maxLength = 200;
                        label.setAttribute('aria-label', `Display label for ID ${option}`);
                        label.addEventListener('input', () => { field.option_labels[option] = label.value; changed(); });
                        row.append(label);
                    }
                    row.append(control);
                    [['up','↑'],['down','↓'],['remove','×']].forEach(([action,text]) => {
                        const button = el('button','',text); button.type = 'button'; button.setAttribute('aria-label',`${action} option ${position+1}`);
                        button.disabled = action === 'up' && !position || action === 'down' && position === field.options.length - 1;
                        button.addEventListener('click', () => {
                            if (action === 'remove') {
                                const [removed] = field.options.splice(position,1);
                                if (field.option_labels) delete field.option_labels[removed];
                                if (Array.isArray(field.default_value)) field.default_value = field.default_value.filter(value => value !== removed);
                                else if (field.default_value === removed) field.default_value = null;
                            } else { const destination = position + (action === 'up' ? -1 : 1); [field.options[position],field.options[destination]] = [field.options[destination],field.options[position]]; }
                            changed(); renderProperties();
                        }); row.append(button);
                    }); options.append(row);
                }); panel.append(options);
                const add = el('button','admin-button','+ Add option'); add.type = 'button'; add.disabled = field.options.length >= 100;
                add.addEventListener('click', () => { let n = field.options.length+1; while(field.options.includes(`Option ${n}`)) n++; field.options.push(`Option ${n}`); changed(); renderProperties(); }); panel.append(add);
            }
        }
        if (!presentation.includes(field.type) && field.type !== 'file') {
            if (choiceTypes.includes(field.type)) {
                if (field.type === 'select' && field.option_source) {
                    panel.append(el('p','fb-property-note','Default selections are unavailable for live database dropdowns.'));
                } else {
                    const choices = (field.options ?? []).map(option => [option,field.option_labels?.[option] ?? option]);
                    if (field.type !== 'checkboxes') choices.unshift(['','No default']);
                    select('Default Value', choices, field.default_value, value => field.default_value = value === '' ? null : value, field.type === 'checkboxes').dataset.defaultOptions = '';
                }
            } else if (field.type === 'boolean') select('Default Value',[['','No default'],['1','Yes'],['0','No']],field.default_value === true ? '1' : field.default_value === false ? '0' : '',value => field.default_value = value === '' ? null : value === '1');
            else input('Default Value', ['number','date','time'].includes(field.type) ? field.type : 'text',field.default_value,value => field.default_value = value === '' ? null : field.type === 'number' ? Number(value) : value);
        }
        if (field.type === 'number') {
            panel.append(el('h3','','Numeric limits'));
            ['min','max'].forEach(rule => input(rule === 'min' ? 'Minimum value' : 'Maximum value','number',field.validation_rules[rule],value => field.validation_rules[rule] = value === '' ? null : Number(value)));
        }
        if (['text','textarea','email','phone'].includes(field.type)) {
            panel.append(el('h3','','Text validation'));
            ['min_length','max_length'].forEach(rule => input(rule === 'min_length' ? 'Minimum length' : 'Maximum length','number',field.validation_rules[rule],value => field.validation_rules[rule] = value === '' ? null : Number(value),{min:rule === 'min_length' ? 0 : 1,max:100000,step:1}));
        }
        if (field.type === 'file') {
            panel.append(el('h3','','Upload restrictions'));
            panel.append(el('p','fb-property-note','Private uploads only. Executable and HTML/SVG file types are not supported.'));
            safeTypes.forEach(extension => toggle(extension.toUpperCase(),(field.validation_rules.allowed_types ?? ['pdf','jpg','png']).includes(extension),checked => {
                const types = new Set(field.validation_rules.allowed_types ?? ['pdf','jpg','png']); if (checked) types.add(extension); else types.delete(extension); field.validation_rules.allowed_types = [...types];
            }));
            input('Maximum file size (KB)','number',field.validation_rules.max_size ?? 2048,value => field.validation_rules.max_size = value === '' ? 2048 : Number(value),{min:1,max:10240,step:1});
        }
        if (!presentation.includes(field.type)) input('Custom validation message','text',field.validation_rules.message,value => field.validation_rules.message = value || null,{maxLength:255});
        panel.append(el('h3','','Conditional visibility'));
        const earlier = state.fields.slice(0,index).filter(item => ![...presentation,'file'].includes(item.type));
        toggle('Show only when…',!!field.conditional_rules,enabled => {
            field.conditional_rules = enabled ? { field:earlier[0]?.field_key ?? '', operator:'equals', value:'' } : null;
            renderProperties();
        }, !earlier.length && !field.conditional_rules);
        if (!earlier.length) panel.append(el('p','fb-property-note','Add an answer question above this field to use conditional visibility.'));
        if (field.conditional_rules) {
            const rule = field.conditional_rules;
            select('Earlier question',[['','Select question'],...earlier.map(item => [item.field_key,item.label || types[item.type][1]])],rule.field,value => { rule.field = value; rule.operator = 'equals'; rule.value = ''; renderProperties(); });
            const source = earlier.find(item => item.field_key === rule.field);
            const operators = [['equals','Equals'],['not_equals','Not Equals']];
            if (source && ['text','textarea','email','phone','checkboxes'].includes(source.type)) operators.push(['contains','Contains']);
            select('Operation',operators,rule.operator,value => rule.operator = value);
            if (source?.type === 'boolean') select('Answer value',[['','Select answer'],['1','Yes'],['0','No']],String(rule.value),value => rule.value = value);
            else if (source?.type === 'select' && source.option_source) input('Answer value','text',rule.value,value => rule.value = value,{maxLength:1000});
            else if (source && choiceTypes.includes(source.type)) select('Answer value',[['','Select answer'],...(source.options ?? []).map(value => [value,source.option_labels?.[value] ?? value])],rule.value,value => rule.value = value);
            else input('Answer value',source?.type === 'number' ? 'number' : 'text',rule.value,value => rule.value = value,{maxLength:1000});
        }
        panel.append(el('h3','','Stable field key'),el('code','',field.field_key),el('p','fb-property-note','This key stays with the question across published versions. Duplicating a question creates a new key.'));
        propertyContent.append(panel);
    }

    const choose = (fieldKey, showProperties = false) => {
        selectedKey = fieldKey; renderCanvas(); renderProperties();
        if (showProperties && window.matchMedia('(max-width: 1100px)').matches) setPanel('properties');
        if (showProperties) propertyContent.querySelector('input,textarea,select')?.focus();
    };
    const insert = (type, position = state.fields.length) => {
        if (state.fields.length >= 200) { showError(new Error('A form can contain up to 200 fields.')); return; }
        const field = { field_key:key(), label:types[type][1], type, placeholder:null, help_text:null, required:false, options:choiceTypes.includes(type) ? ['Option 1','Option 2'] : [], validation_rules:type === 'file' ? {allowed_types:['pdf','jpg','png'],max_size:2048} : {}, conditional_rules:null, default_value:null };
        state.fields.splice(position,0,field); selectedKey = field.field_key; changed(); choose(selectedKey,true);
    };
    root.querySelectorAll('[data-field-type]').forEach(button => {
        button.addEventListener('click', () => insert(button.dataset.fieldType));
        button.addEventListener('dragstart', event => { event.dataTransfer.setData('application/x-bits-type',button.dataset.fieldType); event.dataTransfer.effectAllowed = 'copy'; });
    });
    root.querySelector('[data-add-first]').addEventListener('click', () => insert('text'));
    root.querySelector('[data-builder-add-question]').addEventListener('click', () => insert('text'));
    root.querySelector('[data-tool-search]').addEventListener('input', event => {
        root.querySelectorAll('[data-field-type]').forEach(button => { button.hidden = !types[button.dataset.fieldType][1].toLowerCase().includes(event.target.value.toLowerCase()); });
    });
    root.querySelectorAll('[data-builder-panel]').forEach(button => button.addEventListener('click', () => setPanel(button.dataset.builderPanel)));
    titleInput.addEventListener('input', () => { state.title = titleInput.value; markChanged(); });
    descriptionInput.addEventListener('input', () => { state.description = descriptionInput.value; markChanged(); });
    canvas.addEventListener('click', event => {
        const card = event.target.closest('[data-field-key]'); if (!card) return;
        const index = state.fields.findIndex(field => field.field_key === card.dataset.fieldKey);
        const action = event.target.closest('[data-field-action]')?.dataset.fieldAction;
        if (!action || action === 'edit') { choose(card.dataset.fieldKey,true); return; }
        if (action === 'delete') { state.fields.splice(index,1); selectedKey = state.fields[Math.min(index,state.fields.length-1)]?.field_key ?? null; }
        if (action === 'copy') { if (state.fields.length >= 200) return; const copy = clone(state.fields[index]); copy.field_key = key(); copy.label += ' (copy)'; state.fields.splice(index+1,0,copy); selectedKey = copy.field_key; }
        if (action === 'up' || action === 'down') { const destination = index + (action === 'up' ? -1 : 1); if (destination < 0 || destination >= state.fields.length) return; [state.fields[index],state.fields[destination]] = [state.fields[destination],state.fields[index]]; selectedKey = card.dataset.fieldKey; }
        changed(); renderProperties();
        canvas.querySelector(`[data-field-key="${selectedKey}"]`)?.focus({preventScroll:true});
    });
    canvas.addEventListener('keydown', event => {
        if (!['Enter',' '].includes(event.key) || !event.target.matches('[data-field-key]')) return;
        event.preventDefault(); choose(event.target.dataset.fieldKey,true);
        propertyContent.querySelector('input,textarea,select')?.focus();
    });
    canvas.addEventListener('dragstart', event => {
        const card = event.target.closest('[data-field-key]'); if (!card) return;
        event.dataTransfer.setData('application/x-bits-key',card.dataset.fieldKey); event.dataTransfer.effectAllowed = 'move'; card.classList.add('is-dragging');
    });
    root.querySelector('[data-builder-canvas]').addEventListener('dragover', event => {
        if (![...event.dataTransfer.types].some(type => ['application/x-bits-key','application/x-bits-type'].includes(type))) return;
        event.preventDefault(); canvas.querySelectorAll('.is-drop-target').forEach(card => card.classList.remove('is-drop-target')); event.target.closest('[data-field-key]')?.classList.add('is-drop-target');
    });
    root.querySelector('[data-builder-canvas]').addEventListener('drop', event => {
        const type = event.dataTransfer.getData('application/x-bits-type'); const fieldKey = event.dataTransfer.getData('application/x-bits-key'); if (!type && !fieldKey) return;
        event.preventDefault(); const target = event.target.closest('[data-field-key]'); let position = target ? state.fields.findIndex(field => field.field_key === target.dataset.fieldKey) : state.fields.length;
        if (type && types[type]) insert(type,position);
        else { const previous = state.fields.findIndex(field => field.field_key === fieldKey); if (previous < 0) return; const [field] = state.fields.splice(previous,1); if (previous < position) position--; state.fields.splice(position,0,field); selectedKey = fieldKey; changed(); renderProperties(); }
    });
    document.addEventListener('dragend', () => canvas.querySelectorAll('.is-dragging,.is-drop-target').forEach(card => card.classList.remove('is-dragging','is-drop-target')));

    const syncSettings = () => {
        const clientReference = settingsDialog.querySelector('[data-setting="client_reference_field"]');
        clientReference.replaceChildren();
        const none = el('option','','None — do not link responses to clients'); none.value = ''; clientReference.append(none);
        state.fields.filter(field => field.type === 'select' && (!field.option_source || field.option_source.source === 'users')).forEach(field => {
            const option = el('option','',`${field.label || 'Untitled question'} (${field.field_key})`); option.value = field.field_key; clientReference.append(option);
        });
        const currentReference = state.settings.client_reference_field;
        if (currentReference && ![...clientReference.options].some(option => option.value === currentReference)) {
            const missing = el('option','',`Unavailable question (${currentReference}) — choose another or None`); missing.value = currentReference; clientReference.append(missing);
        }
        settingsDialog.querySelectorAll('[data-setting]').forEach(input => { const value = state.settings[input.dataset.setting]; if (input.type === 'checkbox') input.checked = !!value; else input.value = input.type === 'datetime-local' ? (value ?? '').slice(0,16) : value ?? ''; });
        settingsDialog.querySelectorAll('[data-meta-setting]').forEach(input => { input.value = state[input.dataset.metaSetting] ?? ''; });
    };
    settingsDialog.querySelectorAll('[data-setting]').forEach(input => input.addEventListener('input', () => {
        const name = input.dataset.setting;
        state.settings[name] = input.type === 'checkbox' ? input.checked : input.type === 'number' ? (input.value === '' ? null : Number(input.value)) : input.type === 'datetime-local' ? (input.value ? input.value + ':00Z' : null) : input.value || (['logo_url', 'client_reference_field'].includes(name) ? null : '');
        if (name === 'require_authentication' && input.checked) { state.settings.allow_anonymous = false; settingsDialog.querySelector('[data-setting="allow_anonymous"]').checked = false; }
        if (name === 'allow_anonymous' && input.checked) { state.settings.require_authentication = false; settingsDialog.querySelector('[data-setting="require_authentication"]').checked = false; }
        markChanged();
    }));
    settingsDialog.querySelectorAll('[data-meta-setting]').forEach(input => input.addEventListener('input', () => { state[input.dataset.metaSetting] = input.value; titleInput.value = state.title; descriptionInput.value = state.description ?? ''; markChanged(); }));
    root.querySelector('[data-builder-settings-open]').addEventListener('click', event => { syncSettings(); open(settingsDialog,event.currentTarget); });
    root.querySelector('[data-builder-preview]').addEventListener('click', event => { previewDialog.querySelector('[data-live-preview]').replaceChildren(buildPreview({title:state.title,description:state.description,fields:clone(state.fields)},clone(state.settings))); open(previewDialog,event.currentTarget); });
    saveButton.addEventListener('click',save);
    publishButton.addEventListener('click', async () => {
        if (!await save()) return;
        if (dirty()) { showError(new Error('Your draft changed while saving. Save again before publishing.')); return; }
        setBusy(true, 'Publishing your form…'); publishButton.textContent = 'Publishing…';
        try {
            const result = await api(initial.publish_url,'POST',{revision:state.revision});
            const status = publishedDialog.querySelector('[data-published-status]'); status.textContent = `Publication status: Published · Version ${result.version}. Response settings and limits apply.`;
            publishedDialog.querySelector('[data-published-url]').value = result.url;
            publishedDialog.querySelector('[data-published-open]').href = result.url;
            const badge = document.querySelector('.fm-form-nav > .fm-status'); badge.className = 'fm-status fm-status-published'; badge.textContent = 'Published';
            open(publishedDialog,publishButton);
        } catch (error) { showError(error); }
        finally { setBusy(false); publishButton.textContent = 'Publish ↗'; }
    });
    publishedDialog.querySelector('[data-builder-copy]').addEventListener('click', async event => {
        const input = publishedDialog.querySelector('[data-published-url]');
        const button = event.currentTarget;
        try { await navigator.clipboard.writeText(input.value); button.textContent = 'Copied!'; }
        catch { input.select(); button.textContent = 'Select and copy this link'; }
    });
    window.addEventListener('beforeunload', event => { if (dirty()) { event.preventDefault(); event.returnValue = ''; } });
    renderCanvas(); renderProperties();
    // Rendering initializes optional rule objects; establish the clean initial state afterwards.
    saved = fingerprint(); markChanged();
    if (initial.settings_open) { syncSettings(); open(settingsDialog,root.querySelector('[data-builder-settings-open]')); }
}
