<template>
    <div class="bg-[#F2F7FA] animate-pulse">
        <div class="border-b border-gray-200 bg-white px-4 py-3 sm:px-6 lg:px-8">
            <div class="mx-auto flex max-w-site gap-2">
                <div class="h-4 w-4 bg-gray-200"></div>
                <div class="h-4 w-24 bg-gray-200"></div>
            </div>
        </div>

        <div class="mx-auto max-w-site px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid items-start gap-2 lg:grid-cols-[260px_1fr] lg:gap-2">
                <aside class="hidden border border-gray-200 bg-white p-5 lg:block">
                    <div class="mb-4 h-6 w-32 bg-gray-200"></div>
                    <div class="space-y-4">
                        <div class="h-10 bg-gray-100"></div>
                        <div class="h-10 bg-gray-100"></div>
                        <div class="h-10 bg-gray-100"></div>
                        <div class="h-10 bg-gray-200"></div>
                    </div>
                </aside>

                <div>
                    <div class="mb-5 flex justify-between border border-gray-200 bg-white p-4">
                        <div class="h-5 w-40 bg-gray-200"></div>
                        <div class="h-9 w-44 bg-gray-100"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-1.5 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4 xl:grid-cols-5">
                        <ProductCardSkeleton v-for="n in count" :key="n" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import ProductCardSkeleton from './ProductCardSkeleton.vue';

export default {
    name: 'CatalogPageSkeleton',
    components: { ProductCardSkeleton },
    props: {
        count: { type: Number, default: 10 },
    },
};
</script>
