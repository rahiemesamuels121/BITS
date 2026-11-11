const adminDashboard = document.querySelector('[data-admin-dashboard]');

if (adminDashboard) {
    initialiseAdminDashboard(adminDashboard);
}

function initialiseAdminDashboard(root) {
    const dataElement = document.querySelector('#admin-demo-data');
    if (!dataElement) return;

    let demo;
    try {
        demo = JSON.parse(dataElement.textContent);
    } catch {
        return;
    }

    const systems = demo.systems.map((system) => ({ ...system }));
    const tickets = demo.tickets;
    const panels = [...root.querySelectorAll('[data-admin-panel]')];
    const navLinks = [...root.querySelectorAll('[data-admin-nav]')];
    const search = root.querySelector('[data-admin-search]');
    const statusFilter = root.querySelector('[data-admin-status]');
    const ticketFilter = root.querySelector('[data-admin-ticket-status]');
    const roleFilter = root.querySelector('[data-admin-role]');
    const sidebar = root.querySelector('#admin-sidebar');
    const topbar = root.querySelector('.admin-topbar');
    const menuToggle = root.querySelector('[data-admin-menu]');
    const detailsDialog = document.querySelector('#admin-details');
    const addDialog = document.querySelector('#admin-add-system');
    const toast = document.querySelector('[data-admin-toast]');
    const dialogOpeners = new WeakMap();
    let currentView = 'overview';
    let toastTimer;

    const views = {
        overview: ['Overview', 'Systems overview', 'A clearer picture of your infrastructure, team, and service delivery.'],
        systems: ['Systems', 'System inventory', 'Keep every host, environment, and resource reading in one place.'],
        tickets: ['Service tickets', 'Service tickets', 'Stay close to the work, the owner, and the next step.'],
        access: ['Users & access', 'Users & access', 'An overview of your sample team and workspace roles.'],
        activity: ['Activity log', 'Activity log', 'Follow the latest sample events across your workspace.'],
    };

    const node = (tag, className, text) => {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (text !== undefined) element.textContent = String(text);
        return element;
    };

    const notify = (message) => {
        clearTimeout(toastTimer);
        toast.textContent = message;
        toast.hidden = false;
        toastTimer = setTimeout(() => { toast.hidden = true; }, 5500);
    };

    const closeMenu = () => {
        sidebar.classList.remove('is-open');
        menuToggle.setAttribute('aria-expanded', 'false');
    };

    const applyFilters = () => {
        const panel = panels.find((element) => element.dataset.adminPanel === currentView);
        if (!panel) return;
        const query = search.value.trim().toLowerCase();
        panel.querySelectorAll('[data-admin-row]').forEach((row) => {
            const matchesSearch = row.dataset.search.includes(query);
            const matchesStatus = currentView !== 'systems' || statusFilter.value === 'all' || row.dataset.status === statusFilter.value;
            const matchesTicket = currentView !== 'tickets' || ticketFilter.value === 'all' || row.dataset.ticketStatus === ticketFilter.value;
            const matchesRole = currentView !== 'access' || roleFilter.value === 'all' || row.dataset.role === roleFilter.value;
            row.hidden = !(matchesSearch && matchesStatus && matchesTicket && matchesRole);
        });
        panel.querySelectorAll('[data-admin-empty]').forEach((empty) => {
            const rows = [...empty.parentElement.querySelectorAll('[data-admin-row]')];
            empty.hidden = rows.some((row) => !row.hidden);
        });
    };

    const selectView = (view, focus = false) => {
        if (!views[view]) view = 'overview';
        currentView = view;
        panels.forEach((panel) => { panel.hidden = panel.dataset.adminPanel !== view; });
        navLinks.forEach((link) => {
            const active = link.dataset.adminNav === view;
            link.classList.toggle('is-active', active);
            if (active) link.setAttribute('aria-current', 'page');
            else link.removeAttribute('aria-current');
        });
        root.querySelector('[data-admin-breadcrumb]').textContent = views[view][0];
        const heading = root.querySelector('[data-admin-heading]');
        heading.textContent = views[view][1];
        root.querySelector('[data-admin-description]').textContent = views[view][2];
        document.title = `${views[view][0]} — BITS Systems Admin`;
        closeMenu();
        applyFilters();
        if (focus) {
            heading.tabIndex = -1;
            heading.focus({ preventScroll: true });
            window.scrollTo({ top: 0, behavior: 'instant' });
        }
    };

    navLinks.forEach((link) => link.addEventListener('click', (event) => {
        event.preventDefault();
        const view = link.dataset.adminNav;
        history.replaceState(null, '', `#${view}`);
        selectView(view, true);
    }));
    window.addEventListener('hashchange', () => selectView(location.hash.slice(1)));
    [search, statusFilter, ticketFilter, roleFilter].forEach((input) => input.addEventListener('input', applyFilters));

    menuToggle.addEventListener('click', () => {
        const open = !sidebar.classList.contains('is-open');
        sidebar.classList.toggle('is-open', open);
        menuToggle.setAttribute('aria-expanded', String(open));
        if (open) sidebar.querySelector('[data-admin-nav]').focus();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sidebar.classList.contains('is-open')) {
            closeMenu();
            menuToggle.focus();
        }
    });
    document.addEventListener('click', (event) => {
        if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) closeMenu();
    });
    window.matchMedia('(min-width: 761px)').addEventListener('change', closeMenu);

    const updateTopbarHeight = () => root.style.setProperty('--admin-topbar-height', `${topbar.getBoundingClientRect().height}px`);
    if ('ResizeObserver' in window) {
        new ResizeObserver(updateTopbarHeight).observe(topbar);
    } else {
        window.addEventListener('resize', updateTopbarHeight);
    }

    const openDialog = (dialog, opener) => {
        if (typeof dialog.showModal !== 'function') return;
        dialogOpeners.set(dialog, opener);
        dialog.showModal();
        document.documentElement.classList.add('admin-dialog-open');
    };
    [detailsDialog, addDialog].forEach((dialog) => {
        dialog.querySelectorAll('[data-admin-close]').forEach((button) => button.addEventListener('click', () => dialog.close()));
        dialog.addEventListener('click', (event) => {
            if (event.target !== dialog) return;
            const bounds = dialog.getBoundingClientRect();
            if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
        });
        dialog.addEventListener('close', () => {
            document.documentElement.classList.remove('admin-dialog-open');
            const opener = dialogOpeners.get(dialog);
            if (opener?.isConnected) opener.focus();
        });
    });

    const showDetails = (title, kind, fields, opener) => {
        detailsDialog.querySelector('#admin-detail-title').textContent = title;
        detailsDialog.querySelector('[data-admin-detail-kind]').textContent = kind;
        const list = detailsDialog.querySelector('[data-admin-detail-fields]');
        list.replaceChildren();
        fields.forEach(([label, value]) => {
            const pair = node('div');
            pair.append(node('dt', '', label), node('dd', '', value));
            list.append(pair);
        });
        openDialog(detailsDialog, opener);
    };

    root.addEventListener('click', (event) => {
        const systemButton = event.target.closest('[data-admin-system]');
        const ticketButton = event.target.closest('[data-admin-ticket]');
        if (systemButton) {
            const system = systems.find((item) => item.id === systemButton.dataset.adminSystem);
            if (!system) return;
            showDetails(system.name, 'SYSTEM DETAILS', [
                ['System ID', system.id], ['Hostname', system.hostname], ['Environment', system.environment],
                ['Type', system.type], ['Status', system.status === 'Unknown' ? 'Not monitored' : system.status],
                ['Operating system', system.os], ['IP address', system.ip], ['Uptime', system.uptime],
                ['CPU usage', system.cpu === null ? 'No reading' : `${system.cpu}%`],
                ['Memory usage', system.memory === null ? 'No reading' : `${system.memory}%`],
            ], systemButton);
        }
        if (ticketButton) {
            const ticket = tickets.find((item) => item.id === ticketButton.dataset.adminTicket);
            if (!ticket) return;
            showDetails(ticket.subject, 'SERVICE TICKET', Object.entries({
                'Ticket ID': ticket.id, Category: ticket.category, Priority: ticket.priority,
                Status: ticket.status, 'Assigned to': ticket.assignee, 'Last update': ticket.updated,
            }), ticketButton);
        }
    });

    root.querySelector('[data-admin-add]').addEventListener('click', (event) => openDialog(addDialog, event.currentTarget));

    const utilisationCell = (value) => {
        const cell = node('td');
        const wrapper = node('div', 'admin-utilisation');
        wrapper.append(node('span', '', value === null ? '—' : `${value}%`));
        const track = node('i');
        const bar = node('b', value > 85 ? 'is-high' : '');
        bar.style.width = `${value ?? 0}%`;
        track.append(bar);
        wrapper.append(track);
        cell.append(wrapper);
        return cell;
    };

    const renderSystemRows = () => {
        root.querySelectorAll('[data-admin-systems]').forEach((body) => {
            const list = body.dataset.adminSystems === 'overview' ? systems.slice(0, 4) : systems;
            body.replaceChildren();
            list.forEach((system) => {
                const row = node('tr');
                row.dataset.adminRow = '';
                row.dataset.search = [system.name, system.hostname, system.environment, system.status, system.status === 'Unknown' ? 'Not monitored' : ''].join(' ').toLowerCase();
                row.dataset.status = system.status;
                const nameCell = node('td');
                const nameWrap = node('div', 'admin-system-name');
                const nameText = node('div');
                nameText.append(node('strong', '', system.name), node('small', '', system.hostname));
                nameWrap.append(node('span', 'admin-system-icon', '▤'), nameText);
                nameCell.append(nameWrap);
                const environment = node('td');
                environment.append(node('span', 'admin-environment', system.environment));
                const status = node('td');
                const badge = node('span', `admin-status status-${system.status.toLowerCase()}`);
                badge.append(node('i'), document.createTextNode(system.status === 'Unknown' ? 'Not monitored' : system.status));
                status.append(badge);
                const action = node('td');
                const button = node('button', 'admin-row-action', 'Details →');
                button.type = 'button';
                button.dataset.adminSystem = system.id;
                button.setAttribute('aria-label', `View ${system.name} details`);
                action.append(button);
                row.append(nameCell, environment, status, utilisationCell(system.cpu), utilisationCell(system.memory), action);
                body.append(row);
            });
        });
        root.querySelectorAll('[data-admin-count="systems"], [data-admin-stat="systems"]').forEach((element) => {
            element.textContent = element.hasAttribute('data-admin-stat') ? String(systems.length).padStart(2, '0') : systems.length;
        });
        root.querySelector('[data-admin-stat="healthy"]').textContent = String(systems.filter((item) => item.status === 'Healthy').length).padStart(2, '0');
        applyFilters();
    };

    const addForm = addDialog.querySelector('[data-admin-add-form]');
    addForm.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!addForm.reportValidity()) return;
        const fields = new FormData(addForm);
        const hostname = String(fields.get('hostname')).trim();
        const hostnameInput = addForm.elements.namedItem('hostname');
        hostnameInput.setCustomValidity('');
        if (systems.some((system) => system.hostname.toLowerCase() === hostname.toLowerCase())) {
            hostnameInput.setCustomValidity('This hostname is already in the demo inventory.');
            hostnameInput.reportValidity();
            return;
        }
        const name = String(fields.get('name')).trim();
        const nameInput = addForm.elements.namedItem('name');
        nameInput.setCustomValidity(name ? '' : 'Enter a system name.');
        if (!addForm.reportValidity()) return;
        systems.push({ id: `SYS-${String(systems.length + 1).padStart(3, '0')}`, name, hostname,
            type: String(fields.get('type')), environment: String(fields.get('environment')),
            os: String(fields.get('os')).trim() || 'Not specified', status: 'Unknown', cpu: null,
            memory: null, uptime: '—', ip: 'Not assigned' });
        renderSystemRows();
        search.value = '';
        statusFilter.value = 'all';
        history.replaceState(null, '', '#systems');
        selectView('systems');
        addDialog.close();
        addForm.reset();
        notify(`${name} added to the demo inventory. Monitoring is not connected.`);
    });
    addForm.querySelectorAll('input').forEach((input) => input.addEventListener('input', () => input.setCustomValidity('')));

    root.querySelector('[data-admin-export]').addEventListener('click', () => {
        const columns = ['ID', 'Name', 'Hostname', 'Environment', 'Type', 'Status', 'CPU (%)', 'Memory (%)', 'Operating system', 'IP address'];
        const escapeCsv = (value) => {
            let text = String(value ?? '');
            if (/^[=+\-@\t\r]/.test(text)) text = `'${text}`;
            return `"${text.replaceAll('"', '""')}"`;
        };
        const rows = systems.map((system) => [system.id, system.name, system.hostname, system.environment,
            system.type, system.status, system.cpu, system.memory, system.os, system.ip]);
        const csv = [columns, ...rows].map((row) => row.map(escapeCsv).join(',')).join('\r\n');
        const url = URL.createObjectURL(new Blob(['\uFEFF', csv], { type: 'text/csv;charset=utf-8;' }));
        const link = node('a');
        link.href = url;
        link.download = 'bits-system-inventory.csv';
        document.body.append(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
        notify(`Exported ${systems.length} sample systems.`);
    });

    const chartData = { cpu: [34, 46, 39, 57, 49, 64, 53], memory: [52, 61, 58, 67, 63, 73, 68] };
    const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    root.querySelectorAll('[data-admin-chart]').forEach((button) => button.addEventListener('click', () => {
        const type = button.dataset.adminChart;
        const values = chartData[type];
        root.querySelectorAll('[data-admin-chart]').forEach((tab) => tab.setAttribute('aria-pressed', String(tab === button)));
        root.querySelectorAll('[data-admin-bar]').forEach((bar, index) => {
            bar.style.height = `${values[index]}%`;
            bar.title = `${days[index]}: ${values[index]}%`;
        });
        root.querySelector('[data-admin-chart-value]').replaceChildren(document.createTextNode(String(values.at(-1))), node('span', '', '%'));
        root.querySelector('[data-admin-chart-caption]').textContent = `Latest ${type === 'cpu' ? 'CPU' : 'memory'} sample`;
        root.querySelector('[data-admin-chart-visual]').setAttribute('aria-label', `Sample ${type} utilisation Monday to Sunday: ${values.join(', ')} percent`);
    }));

    document.body.classList.add('admin-ready');
    updateTopbarHeight();
    selectView(location.hash.slice(1));
}
