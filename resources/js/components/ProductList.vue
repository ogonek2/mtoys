<template>
    <div class="w-full">
        <div v-if="!loading && items.length > 0"
             data-product-grid
             class="product-grid grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-3.5 lg:grid-cols-4 xl:grid-cols-5 xl:gap-4">
            <ProductCardItem
                v-for="product in items"
                :key="product.id"
                :product="product"
                :show-new-badge="showNewBadge"
            />
        </div>

        <div v-if="loading" class="py-12 text-center text-gray-500">
            <div class="mx-auto mb-4 h-8 w-8 animate-spin rounded-full border-2 border-gray-200 border-t-[#FF6B4A]"></div>
            <p class="text-sm">Завантаження товарів...</p>
        </div>

        <div v-if="!loading && items.length === 0" class="px-4 py-12 text-center text-gray-500">
            <AppIcon name="box" :size="40" icon-class="mx-auto mb-4 text-gray-300" />
            <h5 class="mb-2 font-bold text-[#1A2B36]">Товари не знайдено</h5>
            <p class="text-sm">Спробуйте змінити параметри пошуку</p>
        </div>
    </div>
</template>

<script>
import ProductCardItem from './ProductCardItem.vue';
import AppIcon from './AppIcon.vue';

export default {
    name: 'ProductList',
    components: { ProductCardItem, AppIcon },
    props: {
        products: { type: Array, default: () => [] },
        pagination: { type: Object, default: () => ({}) },
        showNewBadge: { type: Boolean, default: false },
    },
    data() {
        return {
            loading: false,
            items: [],
            page: {},
        };
    },
    mounted() {
        this.hydrateFromPropsOrDataset();
    },
    watch: {
        products: { deep: true, handler() { this.hydrateFromPropsOrDataset(); } },
        pagination: { deep: true, handler() { this.hydrateFromPropsOrDataset(); } },
    },
    methods: {
        hydrateFromPropsOrDataset() {
            // Always trust the products prop (including empty filtered results).
            // Falling back to dataset when length === 0 kept stale cards after filters.
            this.items = Array.isArray(this.products) ? this.products : [];
            this.page = this.pagination && typeof this.pagination === 'object' ? this.pagination : {};
        },
    },
};
</script>
