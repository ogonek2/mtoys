<template>
    <CatalogPage v-if="config" :key="pageKey" :config="config" />
</template>

<script>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { spaState } from '../spa/spaStore.js';
import CatalogPage from '../components/CatalogPage.vue';

export default {
    name: 'CatalogRoutePage',
    components: { CatalogPage },
    setup() {
        const route = useRoute();
        const config = computed(() => spaState.pageData || null);
        const pageKey = computed(() => `${route.name}:${route.params.category || 'root'}`);

        return { config, pageKey };
    },
};
</script>
