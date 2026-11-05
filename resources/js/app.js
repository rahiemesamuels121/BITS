const menuToggle = document.querySelector('[data-menu-toggle]');
const navigation = document.querySelector('#site-navigation');

// Animate on entry without hiding content when JavaScript is unavailable.
const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
const activeReveals = new Set();
let revealObserver;

if ('IntersectionObserver' in window && typeof Element.prototype.animate === 'function') {
    const revealSelector = [
        '.hero > .hero-eyebrow',
        '.hero > h1',
        '.hero > .hero-description',
        '.hero > .hero-actions',
        '.hero > .hero-note',
        '.quick-actions',
        '.platform-preview',
        '.principles-strip > span',
        '.section-heading',
        '.feature-card',
        '.about-symbol',
        '.about-copy',
        '.get-started-section > :is(.eyebrow, h2, p, .button)',
        '.auth-card',
    ].join(', ');
    const revealed = new WeakSet();

    const observeReveals = () => {
        if (motionPreference.matches) {
            return;
        }

        revealObserver = new IntersectionObserver((entries) => {
            if (motionPreference.matches) {
                return;
            }

            entries.forEach((entry) => {
                if (!entry.isIntersecting || revealed.has(entry.target)) {
                    return;
                }

                const element = entry.target;
                revealed.add(element);
                revealObserver.unobserve(element);

                const siblings = [...element.parentElement.children]
                    .filter((sibling) => sibling.matches(revealSelector));
                const delay = Math.min(siblings.indexOf(element), 3) * 85;
                const animation = element.animate([
                    { opacity: 0, translate: '0 24px' },
                    { opacity: 1, translate: '0 0' },
                ], {
                    duration: 680,
                    delay,
                    easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                    fill: 'backwards',
                });

                activeReveals.add(animation);
                animation.finished.then(
                    () => activeReveals.delete(animation),
                    () => activeReveals.delete(animation),
                );
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -24px 0px' });

        document.querySelectorAll(revealSelector).forEach((element) => {
            if (!revealed.has(element)) {
                revealObserver.observe(element);
            }
        });
    };

    observeReveals();

    motionPreference.addEventListener('change', () => {
        revealObserver?.disconnect();
        activeReveals.forEach((animation) => animation.cancel());
        activeReveals.clear();
        observeReveals();
    });
}

if (menuToggle && navigation) {
    navigation.dataset.enhanced = 'true';

    const setMenuOpen = (open) => {
        menuToggle.setAttribute('aria-expanded', String(open));
        navigation.dataset.open = String(open);
    };

    setMenuOpen(false);

    menuToggle.addEventListener('click', () => {
        setMenuOpen(menuToggle.getAttribute('aria-expanded') !== 'true');
    });

    navigation.addEventListener('click', (event) => {
        if (event.target.closest('a')) {
            setMenuOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && menuToggle.getAttribute('aria-expanded') === 'true') {
            setMenuOpen(false);
            menuToggle.focus();
        }
    });

    const desktop = window.matchMedia('(min-width: 1040px)');

    desktop.addEventListener('change', (event) => {
        if (event.matches) {
            setMenuOpen(false);
        }
    });
}

const tour = document.querySelector('#platform-tour');

if (tour && typeof tour.showModal === 'function') {
    const stepButtons = [...tour.querySelectorAll('[data-tour-step]')];
    const panels = [...tour.querySelectorAll('[data-tour-panel]')];
    let currentStep = 0;
    let returnFocus = null;

    const selectStep = (step, focus = false) => {
        const selectedButton = stepButtons.find((button) => Number(button.dataset.tourStep) === step);

        if (!selectedButton) {
            return;
        }

        currentStep = step;

        stepButtons.forEach((button) => {
            const selected = button === selectedButton;
            button.setAttribute('aria-selected', String(selected));
            button.tabIndex = selected ? 0 : -1;
        });

        panels.forEach((panel) => {
            panel.hidden = Number(panel.dataset.tourPanel) !== step;
        });

        if (focus) {
            selectedButton.focus();
        }
    };

    selectStep(currentStep);

    document.querySelectorAll('[data-open-tour]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            returnFocus = button;
            selectStep(0);

            if (!tour.open) {
                tour.showModal();
                document.documentElement.classList.add('tour-open');
            }
        });
    });

    tour.querySelectorAll('[data-close-dialog]').forEach((button) => {
        button.addEventListener('click', () => tour.close());
    });

    tour.addEventListener('click', (event) => {
        if (event.target !== tour) {
            return;
        }

        const bounds = tour.getBoundingClientRect();
        const outside = event.clientX < bounds.left || event.clientX > bounds.right
            || event.clientY < bounds.top || event.clientY > bounds.bottom;

        if (outside) {
            tour.close();
        }
    });

    tour.addEventListener('close', () => {
        document.documentElement.classList.remove('tour-open');

        if (returnFocus?.isConnected) {
            returnFocus.focus();
        }
    });

    stepButtons.forEach((button, index) => {
        button.addEventListener('click', () => selectStep(Number(button.dataset.tourStep)));

        button.addEventListener('keydown', (event) => {
            let targetIndex;

            switch (event.key) {
                case 'ArrowRight':
                    targetIndex = (index + 1) % stepButtons.length;
                    break;
                case 'ArrowLeft':
                    targetIndex = (index - 1 + stepButtons.length) % stepButtons.length;
                    break;
                case 'Home':
                    targetIndex = 0;
                    break;
                case 'End':
                    targetIndex = stepButtons.length - 1;
                    break;
                default:
                    return;
            }

            event.preventDefault();
            selectStep(Number(stepButtons[targetIndex].dataset.tourStep), true);
        });
    });

    tour.querySelectorAll('[data-tour-next]').forEach((button) => {
        button.addEventListener('click', () => {
            const index = stepButtons.findIndex((step) => Number(step.dataset.tourStep) === currentStep);
            const next = stepButtons[(index + 1) % stepButtons.length];

            if (next) {
                selectStep(Number(next.dataset.tourStep));
                panels.find((panel) => Number(panel.dataset.tourPanel) === currentStep)?.focus();
            }
        });
    });
}
