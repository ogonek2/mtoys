<template>
    <section class="home-promo">
        <div class="mx-auto max-w-site px-4 sm:px-6 lg:px-8">
            <div class="home-promo__grid">
                <a
                    v-for="item in items"
                    :key="item.href"
                    :href="item.href"
                    class="home-promo__card"
                    :class="`home-promo__card--${item.tone}`">
                    <div class="home-promo__body">
                        <span class="home-promo__eyebrow">{{ item.eyebrow }}</span>
                        <h3 class="home-promo__title">{{ item.title }}</h3>
                        <p class="home-promo__text">{{ item.text }}</p>
                        <span class="home-promo__cta">
                            {{ item.cta }}
                            <AppIcon name="arrow-right" :size="15" />
                        </span>
                    </div>
                </a>
            </div>
        </div>
    </section>
</template>

<script>
import { computed } from 'vue';
import AppIcon from './AppIcon.vue';

export default {
    name: 'HomePromoStrip',
    components: { AppIcon },
    props: {
        catalogUrl: { type: String, default: '/catalog' },
    },
    setup(props) {
        const items = computed(() => [
            {
                eyebrow: 'Новинки',
                title: 'Свіжі надходження',
                text: 'Обирайте іграшки першими — щотижня оновлюємо вітрину.',
                cta: 'Дивитись новинки',
                href: `${props.catalogUrl}?new=1`,
                tone: 'mint',
            },
            {
                eyebrow: 'Знижки',
                title: 'Акційні пропозиції',
                text: 'Вигідні ціни на популярні позиції для роздробу й опту.',
                cta: 'До акцій',
                href: `${props.catalogUrl}?discount=1`,
                tone: 'coral',
            },
            {
                eyebrow: 'Опт',
                title: 'Умови для магазинів',
                text: 'Оптові ціни та швидка комплектація партій під ваш асортимент.',
                cta: 'Товари оптом',
                href: `${props.catalogUrl}?wholesale=1`,
                tone: 'navy',
            },
        ]);

        return { items };
    },
};
</script>
