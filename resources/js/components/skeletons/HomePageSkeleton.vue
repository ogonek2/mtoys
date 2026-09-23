<template>
    <div class="animate-pulse py-8 md:py-12">
        <div class="mx-auto max-w-site space-y-8 px-4 sm:px-6 lg:px-8">
            <div class="h-10 w-64 bg-gray-200"></div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                <div v-for="n in 6" :key="n" class="aspect-square bg-gray-200"></div>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
                <ProductCardSkeleton v-for="n in 5" :key="n" />
            </div>
        </div>
    </div>
</template>

<script>
import ProductCardSkeleton from './ProductCardSkeleton.vue';

export default {
    name: 'HomePageSkeleton',
    components: { ProductCardSkeleton },
};
</script>
