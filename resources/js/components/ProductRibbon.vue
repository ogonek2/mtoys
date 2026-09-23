<template>
    <section class="product-ribbon bg-transparent pb-14 pt-2 md:pb-20">
        <div class="mx-auto max-w-site px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div class="flex flex-wrap items-baseline gap-x-5 gap-y-2">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        class="product-ribbon__tab"
                        :class="{ 'product-ribbon__tab--active': active === tab.key }"
                        @click="active = tab.key">
                        {{ tab.label }}
                    </button>
                </div>
                <a
                    :href="activeHref"
                    class="hidden items-center gap-1.5 text-sm font-semibold text-[#0E3A45] transition-colors hover:text-[#FF6B4A] sm:inline-flex">
                    Дивитись усі
                    <AppIcon name="arrow-right" :size="16" />
                </a>
            </div>

            <div v-if="activeProducts.length" class="product-ribbon__track">
                <ProductCardItem
                    v-for="product in activeProducts"
                    :key="product.id"
                    :product="product"
                    :show-new-badge="active === 'new'"
                    class="product-ribbon__card" />
            </div>
            <div v-else class="rounded-2xl border border-[#D5E2E8] bg-[#F2F7FA] px-6 py-12 text-center text-sm text-[#5B6B76]">
                Товари цієї підбірки скоро з'являться
            </div>

            <div class="mt-6 text-center sm:hidden">
                <a :href="activeHref" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#0E3A45]">
                    Дивитись усі
                    <AppIcon name="arrow-right" :size="16" />
                </a>
            </div>
        </div>
    </section>
</template>

<script>
import AppIcon from './AppIcon.vue';
import ProductCardItem from './ProductCardItem.vue';

export default {
    name: 'ProductRibbon',
    components: { AppIcon, ProductCardItem },
    props: {
        newProducts: { type: Array, default: () => [] },
        saleProducts: { type: Array, default: () => [] },
        wholesaleProducts: { type: Array, default: () => [] },
        popularProducts: { type: Array, default: () => [] },
        catalogUrl: { type: String, default: '/catalog' },
    },
    data() {
        return {
            active: 'new',
            tabs: [
                { key: 'new', label: 'Новинки', query: 'new=1' },
                { key: 'sale', label: 'Акційний товар', query: 'discount=1' },
                { key: 'wholesale', label: 'Опт', query: 'wholesale=1' },
                { key: 'popular', label: 'Популярні', query: '' },
            ],
        };
    },
    computed: {
        activeProducts() {
            const map = {
                new: this.newProducts,
                sale: this.saleProducts,
                wholesale: this.wholesaleProducts,
                popular: this.popularProducts,
            };
            return map[this.active] || [];
        },
        activeHref() {
            const tab = this.tabs.find((t) => t.key === this.active);
            if (!tab?.query) return this.catalogUrl;
            return `${this.catalogUrl}?${tab.query}`;
        },
    },
    mounted() {
        const firstWithItems = this.tabs.find((tab) => {
            const map = {
                new: this.newProducts,
                sale: this.saleProducts,
                wholesale: this.wholesaleProducts,
                popular: this.popularProducts,
            };
            return (map[tab.key] || []).length > 0;
        });
        if (firstWithItems) this.active = firstWithItems.key;
    },
};
</script>
