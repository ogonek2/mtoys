import { createApp } from 'vue';
import { addProductToCart, toggleWishlistItem, isProductInWishlist } from './utils/cart.js';
import { initNavigation } from './site/navigation.js';
import { initScrollReveal } from './site/scrollReveal.js';

import ProductList from './components/ProductList.vue';
import OpenCartButton from './components/OpenCartButton.vue';
import WishlistButton from './components/WishlistButton.vue';
import ToastNotification from './components/ToastNotification.vue';
import CartList from './components/CartList.vue';
import Search from './components/Search.vue';
import ProductBuyBox from './components/ProductBuyBox.vue';
import CallbackModal from './components/CallbackModal.vue';
import ProductGallery from './components/ProductGallery.vue';
import CategoryTreeManager from './components/CategoryTreeManager.vue';
import CategoryTreeNode from './components/CategoryTreeNode.vue';

import SpaApp from './spa/SpaApp.vue';
import router from './router/index.js';
import { initSpaNavigation } from './spa/spaNavigation.js';

function buildToast() {
    const dispatch = (type, title, message, product = null) => {
        window.dispatchEvent(new CustomEvent('show-toast', {
            detail: { title, message, type, product },
        }));
    };

    return {
        success: (message, product = null) => dispatch('success', 'Успіх', message, product),
        error: (message, product = null) => dispatch('error', 'Помилка', message, product),
        info: (message, product = null) => dispatch('info', 'Інформація', message, product),
        warning: (message, product = null) => dispatch('warning', 'Попередження', message, product),
    };
}

function safeJsonParse(value, fallback) {
    try {
        if (typeof value !== 'string' || value.trim() === '') return fallback;
        return JSON.parse(value);
    } catch {
        return fallback;
    }
}

function mountTag(tagName, component, getProps) {
    document.querySelectorAll(tagName).forEach((el) => {
        if (el.__vue_app__) return;
        const app = createApp(component, getProps ? getProps(el) : {});
        app.config.globalProperties.$toast = window.$toast;
        app.mount(el);
    });
}

function initSpa() {
    const root = document.getElementById('spa-root');
    if (!root || root.__vue_app__) return false;

    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }

    const app = createApp(SpaApp);
    app.use(router);
    app.config.globalProperties.$toast = window.$toast;
    app.mount(root);

    initSpaNavigation(router);
    router.isReady().catch(() => {});

    return true;
}

function initPwa() {
    if (!('serviceWorker' in navigator)) return;

    import('virtual:pwa-register').then(({ registerSW }) => {
        registerSW({ immediate: true });
    }).catch(() => {});
}

document.addEventListener('DOMContentLoaded', () => {
    try {
        window.$toast = buildToast();
        window.addProductToCart = addProductToCart;
        window.toggleWishlistItem = toggleWishlistItem;
        window.isProductInWishlist = isProductInWishlist;
        window.addToCart = (id, name, price, image, articule, availability, isWholesale, wholesalePrice, wholesaleMinQuantity) => {
            addProductToCart({
                id, name, price, discount: 0,
                image_path: image, articule, availability,
                is_wholesale: isWholesale,
                wholesale_price: wholesalePrice,
                wholesale_min_quantity: wholesaleMinQuantity,
            }, 1);
        };

        initNavigation();
        initSpa();
        initPwa();
        initScrollReveal();

        mountTag('product-list', ProductList, (el) => ({
            products: safeJsonParse(el.getAttribute('data-products'), []),
            pagination: safeJsonParse(el.getAttribute('data-pagination'), {}),
            showNewBadge: el.hasAttribute('data-show-new-badge'),
        }));
        mountTag('open-cart-button', OpenCartButton, (el) => ({
            labeled: el.hasAttribute('labeled'),
        }));
        mountTag('wishlist-button', WishlistButton, (el) => ({
            labeled: el.hasAttribute('labeled'),
        }));
        mountTag('toast-notification', ToastNotification);
        mountTag('cart-list', CartList);
        mountTag('product-buy-box', ProductBuyBox);

        const headerSearchMount = document.getElementById('headerSearchMount');
        if (headerSearchMount && !headerSearchMount.__vue_app__) {
            const searchApp = createApp(Search, { variant: 'header' });
            searchApp.config.globalProperties.$toast = window.$toast;
            searchApp.mount(headerSearchMount);
        }

        mountTag('callback-modal', CallbackModal, (el) => ({
            actionUrl: el.dataset.action || '/contact-request',
        }));
        mountTag('product-gallery', ProductGallery, (el) => ({
            images: safeJsonParse(el.dataset.images, []),
            alt: el.dataset.alt || '',
            discount: parseInt(el.dataset.discount || '0', 10),
            isWholesale: el.dataset.wholesale === 'true',
            placeholder: el.dataset.placeholder || undefined,
        }));

        const adminApp = document.getElementById('category-tree-app');
        if (adminApp && !adminApp.__vue_app__) {
            const categoryApp = createApp(CategoryTreeManager);
            categoryApp.config.globalProperties.$toast = window.$toast;
            categoryApp.component('category-tree-node', CategoryTreeNode);
            categoryApp.mount(adminApp);
        }
    } catch (error) {
        console.error('Помилка ініціалізації Vue:', error);
    }
});
