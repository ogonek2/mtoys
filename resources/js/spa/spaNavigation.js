import { isSpaPath } from './spaApi.js';

export function initSpaNavigation(router) {
    document.addEventListener('click', (event) => {
        if (event.defaultPrevented) return;

        const anchor = event.target.closest('a[href]');
        if (!anchor) return;
        if (anchor.hasAttribute('download')) return;
        if (anchor.target && anchor.target !== '_self') return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const href = anchor.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) {
            return;
        }

        let url;
        try {
            url = new URL(href, window.location.origin);
        } catch {
            return;
        }

        if (url.origin !== window.location.origin) return;
        if (!isSpaPath(url.pathname)) return;

        event.preventDefault();

        const target = `${url.pathname}${url.search}${url.hash}`;
        if (target === router.currentRoute.value.fullPath) return;

        window.closeMobileMenu?.();
        window.closeCatalogMenu?.();
        window.closeSearchModal?.();

        router.push(target);
    });
}
