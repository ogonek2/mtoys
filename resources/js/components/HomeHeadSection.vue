<template>
    <section class="home-head">
        <a :href="catalogUrl" class="home-head__mobile-catalog">
            <AppIcon name="shopping-bag" :size="18" />
            <span>Каталог товарів</span>
            <AppIcon name="chevron-right" :size="16" />
        </a>

        <div class="home-head__grid">
            <aside class="home-head__sidebar">
                <a :href="catalogUrl" class="home-head__sidebar-title">
                    <span class="flex items-center gap-2">
                        <AppIcon name="shopping-bag" :size="18" />
                        <span>Каталог товарів</span>
                    </span>
                    <AppIcon name="chevron-right" :size="16" />
                </a>
                <nav class="home-head__sidebar-nav" aria-label="Категорії">
                    <a v-for="(category, index) in sidebarCategories" :key="category.href || category.url"
                        :href="category.href" class="home-head__cat">
                        <span class="home-head__cat-icon">
                            <AppIcon :name="categoryIcon(index)" :size="18" />
                        </span>
                        <span class="home-head__cat-name">{{ category.name }}</span>
                        <AppIcon name="chevron-right" :size="16" class="home-head__cat-arrow" />
                    </a>
                    <a v-if="categories.length > sidebarLimit" :href="catalogUrl"
                        class="home-head__cat home-head__cat--more">
                        <span class="home-head__cat-icon">
                            <AppIcon name="layout-grid" :size="18" />
                        </span>
                        <span class="home-head__cat-name">Усі категорії</span>
                        <AppIcon name="chevron-right" :size="16" class="home-head__cat-arrow" />
                    </a>
                </nav>
            </aside>

            <div class="home-head__banners">
                <div class="home-banner" @mouseenter="pauseTop" @mouseleave="resumeTop">
                    <div class="home-banner__track" :style="topTrackStyle">
                        <div v-for="(slide, i) in topSlides" :key="'top-' + i" class="home-banner__slide"
                            :style="{ backgroundImage: `url(${slide.image})` }">
                            <div class="home-banner__shade home-banner__shade--teal"></div>
                            <div class="home-banner__content">
                                <h2 class="home-banner__title">{{ slide.title }}</h2>
                                <p class="home-banner__text">{{ slide.text }}</p>
                                <a :href="slide.href" class="home-banner__cta">{{ slide.cta }}</a>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="home-banner__nav home-banner__nav--prev" aria-label="Попередній"
                        @click="prevTop">
                        <AppIcon name="chevron-left" :size="20" />
                    </button>
                    <button type="button" class="home-banner__nav home-banner__nav--next" aria-label="Наступний"
                        @click="nextTop">
                        <AppIcon name="chevron-right" :size="20" />
                    </button>
                    <div class="home-banner__dots">
                        <button v-for="(_, i) in topSlides" :key="'td-' + i" type="button" class="home-banner__dot"
                            :class="{ 'is-active': topIndex === i }" :aria-label="`Слайд ${i + 1}`"
                            @click="topIndex = i" />
                    </div>
                </div>

                <div class="home-banner" @mouseenter="pauseBottom" @mouseleave="resumeBottom">
                    <div class="home-banner__track" :style="bottomTrackStyle">
                        <div v-for="(slide, i) in bottomSlides" :key="'bot-' + i" class="home-banner__slide"
                            :style="{ backgroundImage: `url(${slide.image})` }">
                            <div class="home-banner__shade home-banner__shade--coral"></div>
                            <div class="home-banner__content">
                                <h2 class="home-banner__title">{{ slide.title }}</h2>
                                <p class="home-banner__text">{{ slide.text }}</p>
                                <a :href="slide.href" class="home-banner__cta">{{ slide.cta }}</a>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="home-banner__nav home-banner__nav--prev" aria-label="Попередній"
                        @click="prevBottom">
                        <AppIcon name="chevron-left" :size="20" />
                    </button>
                    <button type="button" class="home-banner__nav home-banner__nav--next" aria-label="Наступний"
                        @click="nextBottom">
                        <AppIcon name="chevron-right" :size="20" />
                    </button>
                    <div class="home-banner__dots">
                        <button v-for="(_, i) in bottomSlides" :key="'bd-' + i" type="button" class="home-banner__dot"
                            :class="{ 'is-active': bottomIndex === i }" :aria-label="`Слайд ${i + 1}`"
                            @click="bottomIndex = i" />
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import AppIcon from './AppIcon.vue';

const CAT_ICONS = ['package', 'box', 'layers', 'star', 'tag', 'shopping-bag', 'boxes', 'gift', 'puzzle'];

export default {
    name: 'HomeHeadSection',
    components: { AppIcon },
    props: {
        categories: { type: Array, default: () => [] },
        catalogUrl: { type: String, default: '/catalog' },
    },
    setup(props) {
        const sidebarLimit = 9;
        const topIndex = ref(0);
        const bottomIndex = ref(0);
        let topTimer = null;
        let bottomTimer = null;

        const sidebarCategories = computed(() => props.categories.slice(0, sidebarLimit));

        const topSlides = computed(() => [
            {
                title: 'Іграшки, що надихають грати',
                text: 'Широкий асортимент для дітей і подарунків — оптом і в роздріб.',
                cta: 'Перейти в каталог',
                href: props.catalogUrl,
                image: 'https://images.unsplash.com/photo-1558060370-d644479cb6f7?w=1400&q=80',
            },
            {
                title: 'Новинки сезону',
                text: 'Свіжі надходження іграшок — обирайте першими.',
                cta: 'Дивитись новинки',
                href: `${props.catalogUrl}?new=1`,
                image: 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=1400&q=80',
            },
            {
                title: 'Вигідний опт для магазинів',
                text: 'Зручні умови співпраці та швидка комплектація замовлень.',
                cta: 'Товари оптом',
                href: `${props.catalogUrl}?wholesale=1`,
                image: 'https://images.unsplash.com/photo-1566576912321-d58ddd7a6088?w=1400&q=80',
            },
        ]);

        const bottomSlides = computed(() => [
            {
                title: 'Подарунки, що радують',
                text: 'Підберемо ідею для дня народження, свята чи сюрпризу.',
                cta: 'Обрати подарунок',
                href: props.catalogUrl,
                image: 'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=1400&q=80',
            },
            {
                title: 'Швидка доставка по Україні',
                text: 'Нова Пошта та зручні умови для роздрібних і оптових замовлень.',
                cta: 'Доставка і оплата',
                href: '/oplata-i-dostavka',
                image: 'https://images.unsplash.com/photo-1587654780291-39c9404d746b?w=1400&q=80',
            },
            {
                title: 'Потрібна консультація?',
                text: 'Допоможемо з вибором асортименту та комплектацією партії.',
                cta: 'Звʼязатися',
                href: '/kontaktna-informatsiia',
                image: 'https://images.unsplash.com/photo-1476703993599-0035a21b17a9?w=1400&q=80',
            },
        ]);

        const topTrackStyle = computed(() => ({
            transform: `translateX(-${topIndex.value * 100}%)`,
        }));
        const bottomTrackStyle = computed(() => ({
            transform: `translateX(-${bottomIndex.value * 100}%)`,
        }));

        const features = [
            { icon: 'shield-check', label: 'Перевірена якість' },
            { icon: 'truck', label: 'Швидка доставка' },
            { icon: 'handshake', label: 'Опт і роздріб' },
            { icon: 'headphones', label: 'Консультація спеціаліста' },
        ];

        function categoryIcon(index) {
            return CAT_ICONS[index % CAT_ICONS.length];
        }

        function nextTop() {
            topIndex.value = (topIndex.value + 1) % topSlides.value.length;
        }
        function prevTop() {
            topIndex.value = (topIndex.value - 1 + topSlides.value.length) % topSlides.value.length;
        }
        function nextBottom() {
            bottomIndex.value = (bottomIndex.value + 1) % bottomSlides.value.length;
        }
        function prevBottom() {
            bottomIndex.value = (bottomIndex.value - 1 + bottomSlides.value.length) % bottomSlides.value.length;
        }

        function startTop() {
            stopTop();
            topTimer = setInterval(nextTop, 5500);
        }
        function stopTop() {
            if (topTimer) clearInterval(topTimer);
            topTimer = null;
        }
        function startBottom() {
            stopBottom();
            bottomTimer = setInterval(nextBottom, 6500);
        }
        function stopBottom() {
            if (bottomTimer) clearInterval(bottomTimer);
            bottomTimer = null;
        }

        onMounted(() => {
            startTop();
            startBottom();
        });
        onUnmounted(() => {
            stopTop();
            stopBottom();
        });

        return {
            sidebarLimit,
            sidebarCategories,
            topSlides,
            bottomSlides,
            topIndex,
            bottomIndex,
            topTrackStyle,
            bottomTrackStyle,
            features,
            categoryIcon,
            nextTop,
            prevTop,
            nextBottom,
            prevBottom,
            pauseTop: stopTop,
            resumeTop: startTop,
            pauseBottom: stopBottom,
            resumeBottom: startBottom,
        };
    },
};
</script>
