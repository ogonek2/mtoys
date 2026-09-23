<template>
    <a href="/obrane"
       :class="labeled ? 'nav-action' : 'relative p-2.5 nav-icon-btn hover:bg-[#F2F7FA] transition-colors inline-flex items-center'"
       aria-label="Обране">
        <span :class="labeled ? 'nav-action__icon' : ''" class="relative">
            <AppIcon name="heart" :size="labeled ? 22 : 20" />
            <span v-if="wishlistCount > 0"
                  class="nav-action__badge">
                {{ wishlistCount }}
            </span>
        </span>
        <span v-if="labeled" class="nav-action__label">Обране</span>
    </a>
</template>

<script>
import AppIcon from './AppIcon.vue';

export default {
    name: 'WishlistButton',
    components: { AppIcon },
    props: {
        labeled: { type: Boolean, default: false },
    },
    data() {
        return { wishlistCount: 0 };
    },
    mounted() {
        this.updateWishlistCount();
        window.addEventListener('wishlist-updated', this.updateWishlistCount);
    },
    unmounted() {
        window.removeEventListener('wishlist-updated', this.updateWishlistCount);
    },
    methods: {
        updateWishlistCount() {
            try {
                const wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
                this.wishlistCount = wishlist.length;
            } catch {
                this.wishlistCount = 0;
            }
        },
    },
};
</script>
