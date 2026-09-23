<template>
    <section v-if="products.length" class="home-rail">
        <div class="mx-auto max-w-site px-4 sm:px-6 lg:px-8">
            <div class="home-rail__head">
                <div class="home-rail__title-wrap">
                    <span v-if="icon" class="home-rail__icon" aria-hidden="true">
                        <AppIcon :name="icon" :size="20" />
                    </span>
                    <h2 class="home-rail__title">{{ title }}</h2>
                </div>
                <a v-if="allHref" :href="allHref" class="home-rail__all">
                    {{ allLabel }}
                    <AppIcon name="arrow-right" :size="16" />
                </a>
            </div>

            <div class="home-rail__grid product-grid">
                <ProductCardItem
                    v-for="product in products"
                    :key="product.id"
                    :product="product"
                    :show-new-badge="showNewBadge" />
            </div>
        </div>
    </section>
</template>

<script>
import AppIcon from './AppIcon.vue';
import ProductCardItem from './ProductCardItem.vue';

export default {
    name: 'HomeProductRail',
    components: { AppIcon, ProductCardItem },
    props: {
        title: { type: String, required: true },
        products: { type: Array, default: () => [] },
        icon: { type: String, default: '' },
        allHref: { type: String, default: '' },
        allLabel: { type: String, default: 'Усі товари' },
        showNewBadge: { type: Boolean, default: false },
    },
};
</script>
