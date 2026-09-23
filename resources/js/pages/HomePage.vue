<template>
    <div class="home-page bg-[#F2F7FA]">
        <HomeHeadSection
            :categories="categories"
            :catalog-url="catalogUrl" />

        <HomeCategoryChips
            :categories="categories"
            :catalog-url="catalogUrl" />

        <HomeGiftFinder
            :categories="categories"
            :catalog-url="catalogUrl" />

        <HomeProductRail
            title="Хіт хітів"
            icon="star"
            :products="popularProducts"
            :all-href="catalogUrl"
            all-label="Усі хіти" />

        <HomeProductRail
            title="Новинки"
            icon="sparkles"
            :products="newProducts"
            :all-href="`${catalogUrl}?new=1`"
            all-label="Усі новинки"
            :show-new-badge="true" />

        <HomeProductRail
            title="Спеціально для вас"
            icon="shopping-bag"
            :products="forYouProducts"
            :all-href="`${catalogUrl}?discount=1`"
            all-label="Дивитись усі" />

        <HomePromoStrip :catalog-url="catalogUrl" />

        <HomeProductRail
            title="Акційні товари"
            icon="tag"
            :products="saleProducts"
            :all-href="`${catalogUrl}?discount=1`"
            all-label="Усі акції" />

        <HomeProductRail
            title="Опт для магазинів"
            icon="boxes"
            :products="wholesaleProducts"
            :all-href="`${catalogUrl}?wholesale=1`"
            all-label="Усі оптові" />

        <section class="home-cats">
            <div class="mx-auto max-w-site px-4 sm:px-6 lg:px-8">
                <div class="home-cats__head">
                    <div>
                        <h2 class="home-cats__title">Категорії іграшок</h2>
                        <p class="home-cats__subtitle">Оберіть розділ і одразу переходьте до товарів</p>
                    </div>
                    <a :href="catalogUrl" class="home-cats__all">
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

        <section class="home-benefits">
            <div class="mx-auto max-w-site px-4 sm:px-6 lg:px-8">
                <div class="home-benefits__grid">
                    <div v-for="item in benefits" :key="item.label" class="home-benefits__item">
                        <span class="home-benefits__icon">
                            <AppIcon :name="item.icon" :size="22" />
                        </span>
                        <div>
                            <h3 class="home-benefits__label">{{ item.label }}</h3>
                            <p class="home-benefits__text">{{ item.text }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="home-seo">
            <div class="mx-auto max-w-site px-4 sm:px-6 lg:px-8">
                <article class="home-seo__card">
                    <h2 class="home-seo__title">Інтернет-магазин іграшок Mtoys</h2>
                    <div class="home-seo__body" :class="{ 'is-open': seoOpen }">
                        <p>
                            Mtoys — інтернет-магазин іграшок для дітей і подарунків з доставкою по Україні.
                            У каталозі зібрані конструктори, м’які іграшки, настільні ігри, творчі набори та товари оптом для магазинів.
                        </p>
                        <p>
                            Ми допомагаємо швидко підібрати асортимент: зручний каталог, акційні пропозиції,
                            новинки сезону та оптова ціна для постійних партнерів. Замовлення комплектуємо оперативно
                            та відправляємо зручним для вас способом.
                        </p>
                        <p>
                            Потрібна консультація? Зателефонуйте або напишіть — підкажемо позиції під бюджет,
                            вік дитини та формат продажу (роздріб чи опт).
                        </p>
                    </div>
                    <button type="button" class="home-seo__more" @click="seoOpen = !seoOpen">
                        {{ seoOpen ? 'Згорнути' : 'Читати далі' }}
                    </button>
                </article>
            </div>
        </section>

        <section class="home-cta">
            <div class="mx-auto max-w-site px-4 sm:px-6 lg:px-8">
                <div class="home-cta__card">
                    <div>
                        <h2 class="home-cta__title">Потрібна допомога з вибором?</h2>
                        <p class="home-cta__text">Напишіть або зателефонуйте — підберемо іграшки під ваш запит.</p>
                    </div>
                    <div class="home-cta__actions">
                        <a href="tel:0636310041" class="home-cta__btn home-cta__btn--primary">
                            <AppIcon name="phone" :size="18" />
                            063 63 100 41
                        </a>
                        <a href="mailto:office@mtoys.com.ua" class="home-cta__btn home-cta__btn--ghost">
                            <AppIcon name="mail" :size="18" />
                            office@mtoys.com.ua
                        </a>
                        <a href="/kontaktna-informatsiia" class="home-cta__link">Контакти →</a>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script>
import { computed, ref } from 'vue';
import { spaState } from '../spa/spaStore.js';
import AppIcon from '../components/AppIcon.vue';
import CategoryCard from '../components/CategoryCard.vue';
import HomeHeadSection from '../components/HomeHeadSection.vue';
import HomeCategoryChips from '../components/HomeCategoryChips.vue';
import HomeGiftFinder from '../components/HomeGiftFinder.vue';
import HomeProductRail from '../components/HomeProductRail.vue';
import HomePromoStrip from '../components/HomePromoStrip.vue';

export default {
    name: 'HomePage',
    components: {
        AppIcon,
        CategoryCard,
        HomeHeadSection,
        HomeCategoryChips,
        HomeGiftFinder,
        HomeProductRail,
        HomePromoStrip,
    },
    setup() {
        const data = computed(() => spaState.pageData || {});
        const seoOpen = ref(false);

        const popularProducts = computed(() => data.value.popularProducts || []);
        const newProducts = computed(() => data.value.newProducts || []);
        const saleProducts = computed(() => data.value.saleProducts || []);
        const wholesaleProducts = computed(() => data.value.wholesaleProducts || []);

        const forYouProducts = computed(() => {
            const seen = new Set();
            const out = [];
            for (const product of [...saleProducts.value, ...popularProducts.value, ...newProducts.value]) {
                const id = product?.id;
                if (!id || seen.has(id)) continue;
                seen.add(id);
                out.push(product);
                if (out.length >= 10) break;
            }
            return out;
        });

        const benefits = [
            { icon: 'truck', label: 'Швидка доставка', text: 'Нова Пошта та зручні варіанти отримання по Україні' },
            { icon: 'shield-check', label: 'Перевірена якість', text: 'Товари для дітей з акцентом на безпеку та надійність' },
            { icon: 'boxes', label: 'Опт і роздріб', text: 'Зручні умови для магазинів і роздрібних покупців' },
            { icon: 'headphones', label: 'Консультація', text: 'Допоможемо з вибором асортименту та комплектацією' },
        ];

        return {
            popularProducts,
            newProducts,
            saleProducts,
            wholesaleProducts,
            forYouProducts,
            categories: computed(() => data.value.categories || []),
            catalogUrl: computed(() => data.value.routes?.catalog || '/catalog'),
            benefits,
            seoOpen,
        };
    },
};
</script>
