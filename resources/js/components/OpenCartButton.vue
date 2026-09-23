<template>
    <a href="/koshyk"
       :class="labeled ? 'nav-action' : 'relative p-2.5 nav-icon-btn hover:bg-[#F2F7FA] transition-colors inline-flex items-center'"
       aria-label="Кошик">
        <span :class="labeled ? 'nav-action__icon' : ''" class="relative">
            <AppIcon name="shopping-cart" :size="labeled ? 22 : 20" />
            <span v-if="cartCount > 0"
                  class="nav-action__badge">
                {{ cartCount }}
            </span>
        </span>
        <span v-if="labeled" class="nav-action__label">Кошик</span>
    </a>
</template>

<script>
import AppIcon from './AppIcon.vue';

export default {
    name: 'OpenCartButton',
    components: { AppIcon },
    props: {
        labeled: { type: Boolean, default: false },
    },
    data() {
        return { cartCount: 0 };
    },
    mounted() {
        this.updateCartCount();
        window.addEventListener('cart-updated', this.updateCartCount);
    },
    unmounted() {
        window.removeEventListener('cart-updated', this.updateCartCount);
    },
    methods: {
        updateCartCount() {
            try {
                const cart = JSON.parse(localStorage.getItem('cart') || '[]');
                this.cartCount = cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
            } catch {
                this.cartCount = 0;
            }
        },
    },
};
</script>
