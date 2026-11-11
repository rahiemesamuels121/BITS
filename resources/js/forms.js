import './form-renderer.js';
import './form-builder.js';

const workspace = document.querySelector('.forms-workspace');
if (workspace) {
    document.body.classList.add('admin-ready');
    const sidebar = document.querySelector('#admin-sidebar');
    const toggle = document.querySelector('[data-admin-menu]');
    const topbar = document.querySelector('.admin-topbar');
    const closeMenu = () => { sidebar.classList.remove('is-open'); toggle.setAttribute('aria-expanded','false'); };
    const syncHeight = () => document.querySelector('.admin-layout').style.setProperty('--admin-topbar-height',`${topbar.getBoundingClientRect().height}px`);
    toggle.addEventListener('click', () => { const open = !sidebar.classList.contains('is-open'); sidebar.classList.toggle('is-open',open); toggle.setAttribute('aria-expanded',String(open)); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && sidebar.classList.contains('is-open')) { closeMenu(); toggle.focus(); } });
    document.addEventListener('click', event => { if (!sidebar.contains(event.target) && !toggle.contains(event.target)) closeMenu(); });
    if ('ResizeObserver' in window) new ResizeObserver(syncHeight).observe(topbar);
    window.addEventListener('resize', () => { syncHeight(); if (innerWidth > 760) closeMenu(); });
    syncHeight();

    const confirmDialog = document.querySelector('#fm-confirm');
    let pendingForm;
    let bypass;
    document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
        if (bypass === form) return;
        event.preventDefault();
        if (!confirmDialog || typeof confirmDialog.showModal !== 'function') { if (confirm(form.dataset.confirm)) { bypass = form; form.requestSubmit(); } return; }
        pendingForm = form;
        confirmDialog.querySelector('[data-confirm-message]').textContent = form.dataset.confirm;
        confirmDialog.showModal();
    }));
    confirmDialog?.querySelector('[data-confirm-cancel]').addEventListener('click', () => confirmDialog.close());
    confirmDialog?.querySelector('[data-confirm-yes]').addEventListener('click', () => { const form = pendingForm; bypass = form; confirmDialog.close(); form.requestSubmit(); });
}

document.querySelectorAll('[data-copy-url]').forEach(button => button.addEventListener('click', async () => {
    try { await navigator.clipboard.writeText(button.dataset.copyUrl); button.textContent = 'Copied!'; }
    catch { const input = button.parentElement.querySelector('input'); if (input) { input.select(); button.textContent = 'Select and copy the link'; } }
}));

document.querySelectorAll('[data-preview-device]').forEach(button => button.addEventListener('click', () => {
    const scope = button.closest('.fb-preview-dialog') || document;
    scope.querySelectorAll('[data-preview-frame]').forEach(frame => frame.classList.toggle('is-mobile',button.dataset.previewDevice === 'mobile'));
    scope.querySelectorAll('[data-preview-device]').forEach(option => option.setAttribute('aria-pressed',String(option === button)));
}));
