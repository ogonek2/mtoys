<template>
    <div class="mx-auto max-w-site px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8">
            <p class="mb-1 font-heading text-xs font-semibold uppercase tracking-[0.25em] text-[#FF6B4A]">Mtoys</p>
            <h1 class="font-heading text-2xl font-bold text-[#0E3A45] md:text-3xl">Кошик</h1>
        </div>
        <CartPage />
    </div>
</template>

<script>
import CartPage from '../components/CartPage.vue';

export default {
    name: 'CartRoutePage',
    components: { CartPage },
};
</script>
