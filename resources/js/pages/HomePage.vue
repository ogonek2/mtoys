<template>
    <div class="home-page bg-[#F2F7FA]">
        <HomeHeadSection
            :categories="categories"
            :catalog-url="catalogUrl" />

        <section class="py-14 md:py-20">
            <div class="mx-auto max-w-site px-4 sm:px-6 lg:px-8">
                <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2 class="font-heading text-2xl font-semibold text-[#0E3A45] md:text-3xl">Категорії іграшок</h2>
                        <p class="mt-1.5 text-sm text-[#5B6B76]">Оберіть розділ і одразу переходьте до товарів</p>
                    </div>
                    <a href="/catalog"
                       class="inline-flex items-center gap-2 rounded-full bg-[#0E3A45] px-5 py-2.5 font-heading text-sm font-semibold text-white transition-transform hover:-translate-y-0.5 hover:bg-[#164E5C]">
                        Весь каталог
                        <AppIcon name="arrow-right" :size="16" />
                    </a>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <CategoryCard
                        v-for="(category, index) in categories"
                        :key="category.url"
                        :category="category"
                        :index="index" />
                </div>
            </div>
        </section>

        <ProductRibbon
            :new-products="newProducts"
            :sale-products="saleProducts"
            :wholesale-products="wholesaleProducts"
            :popular-products="popularProducts"
            :catalog-url="catalogUrl" />
    </div>
</template>

<script>
import { computed } from 'vue';
import { spaState } from '../spa/spaStore.js';
import AppIcon from '../components/AppIcon.vue';
import CategoryCard from '../components/CategoryCard.vue';
import HomeHeadSection from '../components/HomeHeadSection.vue';
import ProductRibbon from '../components/ProductRibbon.vue';

export default {
    name: 'HomePage',
    components: { AppIcon, CategoryCard, HomeHeadSection, ProductRibbon },
    setup() {
        const data = computed(() => spaState.pageData || {});

        return {
            popularProducts: computed(() => data.value.popularProducts || []),
            newProducts: computed(() => data.value.newProducts || []),
            saleProducts: computed(() => data.value.saleProducts || []),
            wholesaleProducts: computed(() => data.value.wholesaleProducts || []),
            categories: computed(() => data.value.categories || []),
            catalogUrl: computed(() => data.value.routes?.catalog || '/catalog'),
        };
    },
};
</script>
