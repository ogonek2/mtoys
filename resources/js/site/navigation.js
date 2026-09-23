function lockBodyScroll(lock) {
    document.body.classList.toggle('overflow-hidden', lock);
}

function isMobileMega() {
    return window.matchMedia('(max-width: 1023px)').matches;
}

function updateHeaderMetrics() {
    const header = document.getElementById('siteHeader');
    if (!header) return;
    const bottom = Math.max(0, Math.round(header.getBoundingClientRect().bottom));
    document.documentElement.style.setProperty('--site-header-bottom', `${bottom}px`);
    document.documentElement.style.setProperty('--header-height', `${bottom}px`);
}

function initHeaderSearch() {
    const header = document.getElementById('siteHeader');
    const panel = document.getElementById('searchResultsPanel');
    const searchBar = document.getElementById('headerSearchBar');
    if (!header) return;

    const setOpen = (open) => {
        header.classList.toggle('site-header--search-open', open);
        searchBar?.setAttribute('aria-hidden', open ? 'false' : 'true');
        panel?.setAttribute('aria-hidden', open ? 'false' : 'true');
        panel?.classList.toggle('search-results-panel--visible', open && isMobileMega());

        if (open) {
            window.dispatchEvent(new CustomEvent('header-search-open'));
            updateHeaderMetrics();
            if (isMobileMega()) lockBodyScroll(true);
        } else {
            window.dispatchEvent(new CustomEvent('header-search-close'));
            updateHeaderMetrics();
            lockBodyScroll(false);
        }
    };

    const open = () => {
        window.closeMobileMenu?.();
        window.closeCatalogMenu?.();
        setOpen(true);
    };

    const close = () => setOpen(false);

    document.querySelectorAll('[data-nav-action="search-open"]').forEach((el) => {
        el.addEventListener('click', (event) => {
            event.preventDefault();
            if (header.classList.contains('site-header--search-open')) {
                close();
            } else {
                open();
            }
        });
    });

    document.querySelectorAll('[data-nav-action="search-close"]').forEach((el) => {
        el.addEventListener('click', (event) => {
            event.preventDefault();
            close();
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && header.classList.contains('site-header--search-open')) {
            close();
        }
    });

    window.toggleSearchModal = open;
    window.closeSearchModal = close;

    window.addEventListener('resize', () => {
        if (!header.classList.contains('site-header--search-open')) return;
        panel?.classList.toggle('search-results-panel--visible', isMobileMega());
        lockBodyScroll(isMobileMega());
        updateHeaderMetrics();
    });
}

function initMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    const overlay = document.getElementById('mobileMenuOverlay');
    if (!menu || !overlay) return;

    const setOpen = (open) => {
        menu.classList.toggle('mobile-menu--open', open);
        overlay.classList.toggle('mobile-menu-overlay--visible', open);
        menu.setAttribute('aria-hidden', open ? 'false' : 'true');
        overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
        lockBodyScroll(open);
    };

    const toggle = () => {
        window.closeCatalogMenu?.();
        setOpen(!menu.classList.contains('mobile-menu--open'));
    };

    const close = () => setOpen(false);

    document.querySelectorAll('[data-nav-action="mobile-menu-toggle"]').forEach((el) => {
        el.addEventListener('click', toggle);
    });
    document.querySelectorAll('[data-nav-action="mobile-menu-close"]').forEach((el) => {
        el.addEventListener('click', close);
    });
    overlay.addEventListener('click', close);

    menu.querySelectorAll('a[href]').forEach((link) => {
        link.addEventListener('click', close);
    });

    menu.querySelectorAll('[data-callback-open]').forEach((btn) => {
        btn.addEventListener('click', close);
    });

    window.toggleMobileMenu = toggle;
    window.closeMobileMenu = close;
}

function initMegaMenu() {
    const header = document.getElementById('siteHeader');
    const trigger = document.getElementById('megaMenuTrigger');
    const btn = document.getElementById('megaMenuBtn');
    const menu = document.getElementById('megaMenu');
    const overlay = document.getElementById('megaMenuOverlay');
    if (!header || !menu) return;

    let closeTimer = null;
    let isOpen = false;

    const catButtons = menu.querySelectorAll('[data-mega-panel]');
    const panels = menu.querySelectorAll('.mega-menu__panel');

    const activatePanel = (panelId) => {
        catButtons.forEach((b) => {
            const active = b.dataset.megaPanel === panelId;
            b.classList.toggle('mega-menu__cat--active', active);
            b.classList.toggle('mega-menu__chip--active', active);
            b.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach((p) => {
            p.classList.toggle('mega-menu__panel--active', p.id === panelId);
        });
    };

    catButtons.forEach((button) => {
        button.addEventListener('mouseenter', () => {
            if (isMobileMega()) return;
            activatePanel(button.dataset.megaPanel);
        });
        button.addEventListener('focus', () => {
            if (isMobileMega()) return;
            activatePanel(button.dataset.megaPanel);
        });
        button.addEventListener('click', () => {
            activatePanel(button.dataset.megaPanel);
            if (isMobileMega()) {
                menu.querySelector('.mega-menu__panels')?.scrollTo({ top: 0 });
            }
        });
    });

    const open = () => {
        clearTimeout(closeTimer);
        if (isOpen) return;
        window.closeMobileMenu?.();
        isOpen = true;
        updateHeaderMetrics();
        header.classList.add('site-header--mega-open');
        menu.classList.add('is-open');
        menu.setAttribute('aria-hidden', 'false');
        overlay?.classList.add('is-visible');
        overlay?.setAttribute('aria-hidden', 'false');
        btn?.setAttribute('aria-expanded', 'true');
        if (isMobileMega()) {
            lockBodyScroll(true);
        }
    };

    const close = () => {
        clearTimeout(closeTimer);
        if (!isOpen) return;
        isOpen = false;
        header.classList.remove('site-header--mega-open');
        menu.classList.remove('is-open');
        menu.setAttribute('aria-hidden', 'true');
        overlay?.classList.remove('is-visible');
        overlay?.setAttribute('aria-hidden', 'true');
        btn?.setAttribute('aria-expanded', 'false');
        if (isMobileMega()) {
            lockBodyScroll(false);
        }
    };

    const scheduleClose = () => {
        if (isMobileMega()) return;
        clearTimeout(closeTimer);
        closeTimer = setTimeout(close, 180);
    };

    trigger?.addEventListener('mouseenter', () => {
        if (isMobileMega()) return;
        open();
    });
    menu.addEventListener('mouseenter', () => clearTimeout(closeTimer));
    header.addEventListener('mouseleave', scheduleClose);

    btn?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        isOpen ? close() : open();
    });

    overlay?.addEventListener('click', close);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });

    window.addEventListener('resize', () => {
        updateHeaderMetrics();
        if (!isMobileMega() && isOpen) {
            lockBodyScroll(false);
        }
    });

    window.toggleCatalogMenu = () => btn?.click();
    window.closeCatalogMenu = close;
}

export function initNavigation() {
    initHeaderSearch();
    initMobileMenu();
    initMegaMenu();
    updateHeaderMetrics();
}
