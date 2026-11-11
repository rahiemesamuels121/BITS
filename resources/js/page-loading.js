let overlay;
let previousInert = new Map();
let previousBodyBusy = null;
let loadingDocument;
let isLoading = false;

export function showPageLoading(message = 'Working…') {
    if (isLoading && loadingDocument !== document) {
        hidePageLoading();
    }

    if (isLoading) {
        return;
    }

    if (!overlay || overlay.ownerDocument !== document) {
        overlay = document.createElement('div');
        overlay.className = 'page-loading';
        overlay.hidden = true;
        overlay.setAttribute('role', 'status');
        overlay.setAttribute('aria-live', 'polite');
        const spinner = document.createElement('span');
        spinner.className = 'page-loading-spinner';
        spinner.setAttribute('aria-hidden', 'true');
        const status = document.createElement('span');
        status.dataset.pageLoadingMessage = '';
        overlay.append(spinner, status);
        document.body.append(overlay);
    }

    isLoading = true;
    loadingDocument = document;
    previousBodyBusy = document.body.getAttribute('aria-busy');
    previousInert = new Map([...document.body.children]
        .filter((element) => element !== overlay)
        .map((element) => [element, element.inert]));

    overlay.querySelector('[data-page-loading-message]').textContent = message;
    overlay.hidden = false;
    document.body.setAttribute('aria-busy', 'true');
    previousInert.forEach((_, element) => {
        element.inert = true;
    });
}

export function hidePageLoading() {
    if (!isLoading) {
        return;
    }

    isLoading = false;
    overlay.hidden = true;
    previousInert.forEach((wasInert, element) => {
        element.inert = wasInert;
    });
    previousInert.clear();

    if (previousBodyBusy === null) {
        loadingDocument.body.removeAttribute('aria-busy');
    } else {
        loadingDocument.body.setAttribute('aria-busy', previousBodyBusy);
    }
    loadingDocument = null;
}

document.addEventListener('click', (event) => {
    const link = event.target.closest?.('a[href]');

    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey
        || link.hasAttribute('download') || link.hasAttribute('data-no-loading')) {
        return;
    }

    const target = (link.getAttribute('target') || document.querySelector('base[target]')?.target || '_self').toLowerCase();
    const destination = new URL(link.href, document.baseURI);
    const current = new URL(window.location.href);

    if (target !== '_self' || !['http:', 'https:'].includes(destination.protocol)
        || (destination.origin === current.origin && destination.pathname === current.pathname
            && destination.search === current.search && destination.hash)) {
        return;
    }

    queueMicrotask(() => {
        if (!event.defaultPrevented) {
            showPageLoading(link.dataset.loadingMessage || 'Loading page…');
        }
    });
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    const submitter = event.submitter;
    const method = (submitter?.formMethod || form.method).toLowerCase();
    const target = (submitter?.formTarget || form.target || document.querySelector('base[target]')?.target || '_self').toLowerCase();

    if (isLoading) {
        event.preventDefault();
        return;
    }

    if (form.tagName !== 'FORM' || form.hasAttribute('data-no-loading') || method === 'dialog' || target !== '_self') {
        return;
    }

    queueMicrotask(() => {
        if (!event.defaultPrevented) {
            showPageLoading(submitter?.dataset.loadingMessage || form.dataset.loadingMessage || 'Submitting…');
        }
    });
}, true);

window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        hidePageLoading();
    }
});
