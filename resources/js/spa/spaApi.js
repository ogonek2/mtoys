const SPA_PATHS = [
    '/',
    '/home',
    '/catalog',
    '/koshyk',
    '/obrane',
];

export function isSpaPath(pathname) {
    if (SPA_PATHS.includes(pathname)) return true;
    if (pathname.startsWith('/catalog/categoriya/')) return true;
    return false;
}

export function readSpaInitial() {
    const el = document.getElementById('spa-initial');
    if (!el?.textContent) return null;
    try {
        return JSON.parse(el.textContent);
    } catch {
        return null;
    }
}

export async function fetchSpaPage(url) {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }

    const payload = await response.json();
    if (!payload.success) {
        throw new Error(payload.message || 'SPA fetch failed');
    }

    return payload;
}

export function applyPageMeta(meta = {}) {
    if (meta.title) {
        document.title = meta.title;
    }

    let descriptionTag = document.querySelector('meta[name="description"]');
    if (meta.description) {
        if (!descriptionTag) {
            descriptionTag = document.createElement('meta');
            descriptionTag.setAttribute('name', 'description');
            document.head.appendChild(descriptionTag);
        }
        descriptionTag.setAttribute('content', meta.description);
    }
}

export function buildSpaApiUrl(routeName, params = {}, query = {}) {
    const search = new URLSearchParams(query).toString();
    const suffix = search ? `?${search}` : '';

    switch (routeName) {
        case 'home':
            return `/api/spa/home${suffix}`;
        case 'catalog':
            return `/api/spa/catalog${suffix}`;
        case 'category':
            return `/api/spa/category/${encodeURIComponent(params.category)}${suffix}`;
        case 'product':
            return `/api/spa/product/${encodeURIComponent(params.category)}/${encodeURIComponent(params.product)}${suffix}`;
        default:
            return null;
    }
}
