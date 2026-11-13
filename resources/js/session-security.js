// History restoration must recheck the server session before showing a private screen.
window.addEventListener('pageshow', (event) => {
    if (event.persisted && document.querySelector('[data-private-page]')) {
        window.location.reload();
    }
});
