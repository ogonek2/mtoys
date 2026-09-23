<template>
    <section class="home-gift">
        <div class="mx-auto max-w-site px-4 sm:px-6 lg:px-8">
            <div class="home-gift__card">
                <div class="home-gift__intro">
                    <span class="home-gift__badge">
                        <AppIcon name="gift" :size="18" />
                        Підбір подарунку
                    </span>
                    <h2 class="home-gift__title">Підберемо іграшку за хвилину</h2>
                    <p class="home-gift__text">Вкажіть бюджет і категорію — відкриємо каталог з підходящими товарами.</p>
                </div>

                <form class="home-gift__form" @submit.prevent="submit">
                    <label class="home-gift__field">
                        <span class="home-gift__label">Ціна від, ₴</span>
                        <input v-model="priceMin" type="number" min="0" step="1" inputmode="numeric" placeholder="0" class="home-gift__input">
                    </label>
                    <label class="home-gift__field">
                        <span class="home-gift__label">Ціна до, ₴</span>
                        <input v-model="priceMax" type="number" min="0" step="1" inputmode="numeric" placeholder="5000" class="home-gift__input">
                    </label>
                    <label class="home-gift__field home-gift__field--wide">
                        <span class="home-gift__label">Категорія</span>
                        <select v-model="categoryUrl" class="home-gift__input home-gift__select">
                            <option value="">Усі категорії</option>
                            <option v-for="cat in categories" :key="cat.url" :value="cat.url">{{ cat.name }}</option>
                        </select>
                    </label>
                    <button type="submit" class="home-gift__submit">
                        Знайти подарунок
                        <AppIcon name="arrow-right" :size="16" />
                    </button>
                </form>
            </div>
        </div>
    </section>
</template>

<script>
import AppIcon from './AppIcon.vue';

export default {
    name: 'HomeGiftFinder',
    components: { AppIcon },
    props: {
        categories: { type: Array, default: () => [] },
        catalogUrl: { type: String, default: '/catalog' },
    },
    data() {
        return {
            priceMin: '',
            priceMax: '',
            categoryUrl: '',
        };
    },
    methods: {
        submit() {
            const params = new URLSearchParams();
            const min = String(this.priceMin || '').trim();
            const max = String(this.priceMax || '').trim();
            if (min !== '') params.set('price_min', min);
            if (max !== '') params.set('price_max', max);

            const qs = params.toString();
            if (this.categoryUrl) {
                const base = `/catalog/categoriya/${encodeURIComponent(this.categoryUrl)}`;
                window.location.href = qs ? `${base}?${qs}` : base;
                return;
            }

            window.location.href = qs ? `${this.catalogUrl}?${qs}` : this.catalogUrl;
        },
    },
};
</script>
