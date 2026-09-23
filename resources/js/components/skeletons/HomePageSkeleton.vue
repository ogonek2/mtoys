<template>
    <div class="home-page animate-pulse bg-[#F2F7FA]">
        <div class="mx-auto max-w-site space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <div class="grid gap-4 lg:grid-cols-[260px_1fr]">
                <div class="hidden h-80 rounded-2xl bg-slate-200 lg:block"></div>
                <div class="space-y-3">
                    <div class="h-44 rounded-2xl bg-slate-200 sm:h-56"></div>
                    <div class="h-36 rounded-2xl bg-slate-200"></div>
                </div>
            </div>

            <div class="flex gap-2 overflow-hidden">
                <div v-for="n in 6" :key="'chip-' + n" class="h-9 w-28 shrink-0 rounded-full bg-slate-200"></div>
            </div>

            <div class="h-40 rounded-2xl bg-slate-200 md:h-36"></div>

            <div class="space-y-3">
                <div class="h-7 w-48 rounded bg-slate-200"></div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <ProductCardSkeleton v-for="n in 5" :key="'p1-' + n" />
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                <div v-for="n in 3" :key="'promo-' + n" class="h-36 rounded-2xl bg-slate-200"></div>
            </div>

            <div class="space-y-3">
                <div class="h-7 w-40 rounded bg-slate-200"></div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <ProductCardSkeleton v-for="n in 5" :key="'p2-' + n" />
                </div>
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
