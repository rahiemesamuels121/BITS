import './forms.js';
import './session-security.js';
import './page-loading.js';
import './contract-assignments.js';

const menuToggle = document.querySelector('[data-menu-toggle]');
const navigation = document.querySelector('#site-navigation');

// Animate on entry without hiding content when JavaScript is unavailable.
const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
const activeReveals = new Set();
let revealObserver;

if ('IntersectionObserver' in window && typeof Element.prototype.animate === 'function') {
    const revealSelector = [
        '.section-heading',
        '.feature-card:not(.expertise-card)',
        '.about-symbol',
        '.about-copy',
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

const expertiseGrid = document.querySelector('.expertise-grid');

if (expertiseGrid && 'IntersectionObserver' in window && typeof Element.prototype.animate === 'function') {
    const expertiseCards = [...expertiseGrid.querySelectorAll('.expertise-card')];
    let expertiseAnimated = false;

    const expertiseObserver = new IntersectionObserver((entries, observer) => {
        if (!entries.some((entry) => entry.isIntersecting) || expertiseAnimated || motionPreference.matches) {
            return;
        }

        expertiseAnimated = true;
        observer.disconnect();

        const cardsPerRow = window.matchMedia('(max-width: 760px)').matches ? 1 : 3;

        expertiseCards.forEach((card, index) => {
            const row = Math.floor(index / cardsPerRow);
            const positionInRow = index % cardsPerRow;
            const direction = row % 2 === 0 ? -1 : 1;
            const animation = card.animate([
                { opacity: 0, transform: `translate3d(${direction * 70}px, 22px, 0) scale(.985)` },
                { opacity: 1, transform: 'translate3d(0, 0, 0) scale(1)' },
            ], {
                duration: 820,
                delay: (row * 130) + (positionInRow * 110),
                easing: 'cubic-bezier(0.16, 1, 0.3, 1)',
                fill: 'backwards',
            });

            activeReveals.add(animation);
            animation.finished.then(
                () => activeReveals.delete(animation),
                () => activeReveals.delete(animation),
            );
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -60px 0px' });

    expertiseObserver.observe(expertiseGrid);

    motionPreference.addEventListener('change', () => {
        if (motionPreference.matches) {
            expertiseObserver.disconnect();
        } else if (!expertiseAnimated) {
            expertiseObserver.observe(expertiseGrid);
        }
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

import './contract-term.js';
