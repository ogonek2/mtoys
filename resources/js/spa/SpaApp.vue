<template>
    <div class="spa-app">
        <template v-if="showSkeleton">
            <HomePageSkeleton v-if="skeletonType === 'home'" />
            <CatalogPageSkeleton v-else-if="skeletonType === 'catalog' || skeletonType === 'category'" />
            <ProductPageSkeleton v-else-if="skeletonType === 'product'" />
        </template>
        <router-view v-show="!showSkeleton" :key="routeKey" />
    </div>
</template>

<script>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { spaState } from './spaStore.js';
import HomePageSkeleton from '../components/skeletons/HomePageSkeleton.vue';
import CatalogPageSkeleton from '../components/skeletons/CatalogPageSkeleton.vue';
import ProductPageSkeleton from '../components/skeletons/ProductPageSkeleton.vue';

export default {
    name: 'SpaApp',
    components: {
        HomePageSkeleton,
        CatalogPageSkeleton,
        ProductPageSkeleton,
    },
    setup() {
        const route = useRoute();

        const showSkeleton = computed(() => spaState.loading);
        const skeletonType = computed(() => spaState.pendingRouteName || route.name || 'home');
        const routeKey = computed(() => `${route.name}:${JSON.stringify(route.params)}`);

        return {
            showSkeleton,
            skeletonType,
            routeKey,
        };
    },
};
</script>
