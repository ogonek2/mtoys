import { createRouter, createWebHistory } from 'vue-router';
import HomePage from '../pages/HomePage.vue';
import CatalogRoutePage from '../pages/CatalogRoutePage.vue';
import ProductRoutePage from '../pages/ProductRoutePage.vue';
import CartRoutePage from '../pages/CartRoutePage.vue';
import WishlistRoutePage from '../pages/WishlistRoutePage.vue';
import {
    fetchSpaPage,
    applyPageMeta,
    buildSpaApiUrl,
    readSpaInitial,
} from '../spa/spaApi.js';
import { setSpaPage, setSpaLoading } from '../spa/spaStore.js';
import {
    saveScrollPosition,
    getScrollPosition,
    applyScrollPosition,
    waitForSpaContent,
    waitForNextPaint,
} from '../spa/scrollMemory.js';

const routes = [
    { path: '/', name: 'home', component: HomePage },
    { path: '/home', redirect: '/' },
    { path: '/catalog', name: 'catalog', component: CatalogRoutePage },
    { path: '/catalog/categoriya/:category', name: 'category', component: CatalogRoutePage },
    { path: '/catalog/categoriya/:category/:product', name: 'product', component: ProductRoutePage },
    { path: '/koshyk', name: 'cart', component: CartRoutePage },
    { path: '/obrane', name: 'wishlist', component: WishlistRoutePage },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to) {
        if (to.hash) {
            return waitForSpaContent().then(() => ({
                el: to.hash,
                behavior: 'smooth',
            }));
        }

        return false;
    },
});

const initial = readSpaInitial();
let hydrated = false;

function resolveApiUrl(to) {
    const query = { ...to.query };

    switch (to.name) {
        case 'home':
            return buildSpaApiUrl('home', {}, query);
        case 'catalog':
            return buildSpaApiUrl('catalog', {}, query);
        case 'category':
            return buildSpaApiUrl('category', { category: to.params.category }, query);
        case 'product':
            return buildSpaApiUrl('product', {
                category: to.params.category,
                product: to.params.product,
            }, query);
        default:
            return null;
    }
}

function routeMatchesInitial(to, init) {
    if (!init?.name || to.name !== init.name) return false;

    if (to.name === 'category' && to.params.category !== init.params?.category) {
        return false;
    }

    if (to.name === 'product') {
        if (to.params.category !== init.params?.category) return false;
        if (to.params.product !== init.params?.product) return false;
    }

    return true;
}

function isQueryOnlyCatalogNav(to, from) {
    if (!from.name) return false;
    if (to.name !== from.name) return false;

    if (to.name === 'catalog') {
        return true;
    }

    if (to.name === 'category') {
        return to.params.category === from.params.category;
    }

    return false;
}

router.beforeEach(async (to, from) => {
    if (from.fullPath && from.fullPath !== to.fullPath) {
        saveScrollPosition(from.fullPath);
    }

    if (to.name === 'cart' || to.name === 'wishlist') {
        if (!hydrated && initial && routeMatchesInitial(to, initial)) {
            applyPageMeta(initial.meta);
            setSpaPage(initial);
            hydrated = true;
        } else {
            applyPageMeta({
                title: to.name === 'cart' ? 'Кошик — Mtoys' : 'Обране — Mtoys',
                description: to.name === 'cart' ? 'Кошик покупок Mtoys' : 'Обрані товари Mtoys',
            });
            setSpaPage({ data: {}, meta: {} });
        }
        setSpaLoading(false);
        return true;
    }

    const apiUrl = resolveApiUrl(to);
    if (!apiUrl) return true;

    if (!hydrated && initial && routeMatchesInitial(to, initial)) {
        applyPageMeta(initial.meta);
        setSpaPage(initial);
        hydrated = true;
        setSpaLoading(false);
        return true;
    }

    if (isQueryOnlyCatalogNav(to, from)) {
        return true;
    }

    setSpaLoading(true, to.name);

    try {
        const payload = await fetchSpaPage(apiUrl);
        applyPageMeta(payload.meta);
        setSpaPage(payload);
    } catch {
        window.$toast?.error?.('Не вдалося завантажити сторінку. Спробуйте ще раз.');
        setSpaLoading(false);
        return from.name ? false : '/';
    } finally {
        setSpaLoading(false);
    }

    return true;
});

router.afterEach(async (to, from, failure) => {
    if (failure) return;

    if (to.hash) return;

    await waitForNextPaint();

    const cached = getScrollPosition(to.fullPath);
    if (cached) {
        await applyScrollPosition(cached);
        return;
    }

    const queryOnlyCatalog = isQueryOnlyCatalogNav(to, from);
    if (!queryOnlyCatalog && from.fullPath !== to.fullPath) {
        window.scrollTo({ left: 0, top: 0, behavior: 'auto' });
    }
});

export default router;
