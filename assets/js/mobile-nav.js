/**
 * Mobile navigation drawer.
 *
 * Handles opening/closing the off-canvas menu (button, backdrop, Escape),
 * body scroll locking, a basic focus trap, and the per-section accordion
 * toggles. All state is expressed by toggling `.is-open` on #mobile-nav and
 * `hidden` on each sub-menu; the visual transitions live in the stylesheet.
 */
document.addEventListener('DOMContentLoaded', function () {
    const nav = document.getElementById('mobile-nav');
    if (!nav) {
        return;
    }

    const openBtn = document.querySelector('.mobile-nav-open');
    const closeBtn = nav.querySelector('.mobile-nav-close');
    const backdrop = nav.querySelector('.mobile-nav-backdrop');
    const panel = nav.querySelector('.mobile-nav-panel');

    const FOCUSABLE = 'a[href], button:not([disabled]), input, [tabindex]:not([tabindex="-1"])';

    const isOpen = () => nav.classList.contains('is-open');

    function open() {
        nav.classList.add('is-open');
        nav.setAttribute('aria-hidden', 'false');
        if (openBtn) {
            openBtn.setAttribute('aria-expanded', 'true');
        }
        document.body.style.overflow = 'hidden';
        // Focus the first control inside the panel once it starts sliding in.
        window.requestAnimationFrame(() => {
            if (closeBtn) {
                closeBtn.focus();
            }
        });
    }

    function close() {
        nav.classList.remove('is-open');
        nav.setAttribute('aria-hidden', 'true');
        if (openBtn) {
            openBtn.setAttribute('aria-expanded', 'false');
            openBtn.focus();
        }
        document.body.style.overflow = '';
    }

    if (openBtn) {
        openBtn.addEventListener('click', open);
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', close);
    }
    if (backdrop) {
        backdrop.addEventListener('click', close);
    }

    // Escape closes; Tab is trapped inside the panel while open.
    document.addEventListener('keydown', (e) => {
        if (!isOpen()) {
            return;
        }
        if (e.key === 'Escape') {
            close();
            return;
        }
        if (e.key === 'Tab' && panel) {
            const items = Array.from(panel.querySelectorAll(FOCUSABLE)).filter(
                (el) => el.offsetParent !== null
            );
            if (!items.length) {
                return;
            }
            const first = items[0];
            const last = items[items.length - 1];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        }
    });

    // Never leave the drawer stuck open when resizing up to the desktop nav.
    window.addEventListener('resize', () => {
        if (isOpen() && window.innerWidth >= 768) {
            close();
        }
    });

    // Per-section accordion toggles.
    nav.querySelectorAll('.mobile-submenu-toggle').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const panelId = toggle.getAttribute('aria-controls');
            const submenu = panelId ? document.getElementById(panelId) : null;
            if (!submenu) {
                return;
            }
            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            submenu.classList.toggle('hidden', expanded);
            const chevron = toggle.querySelector('svg');
            if (chevron) {
                chevron.classList.toggle('rotate-180', !expanded);
            }
        });
    });
});
