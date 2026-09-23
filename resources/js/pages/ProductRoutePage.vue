<template>
    <div v-if="product" class="product-page">
        <nav class="product-page__breadcrumbs" aria-label="Breadcrumb">
            <div class="mx-auto max-w-site px-4 py-3 sm:px-6 lg:px-8">
                <ol class="product-page__crumb-list">
                    <li>
                        <a href="/" @click="onCrumbClick($event, '/')"><AppIcon name="home" :size="16" /></a>
                    </li>
                    <template v-for="(crumb, index) in breadcrumbs" :key="index">
                        <li><AppIcon name="chevron-right" :size="14" class="product-page__crumb-sep" /></li>
                        <li>
                            <a v-if="crumb.url" :href="crumb.url" @click="onCrumbClick($event, crumb.url)">{{ crumb.label }}</a>
                            <span v-else class="product-page__crumb-current" aria-current="page">{{ crumb.label }}</span>
                        </li>
                    </template>
                </ol>
            </div>
        </nav>

        <section class="product-page__main">
            <div class="mx-auto max-w-site px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <div class="product-page__grid">
                    <ProductGallery
                        :images="images"
                        :alt="product.name"
                        :discount="product.discount || 0"
                        :is-wholesale="Boolean(product.is_wholesale && product.wholesale_price)"
                        :placeholder="placeholder" />

                    <div class="product-page__buy">
                        <h1 class="product-page__title">{{ product.name }}</h1>

                        <div class="product-page__meta">
                            <span class="product-page__articule">Артикул: <strong>{{ product.articule || '—' }}</strong></span>
                            <span v-if="product.brand" class="product-page__brand">{{ product.brand }}</span>
                            <span
                                class="product-page__stock"
                                :class="product.inStock ? 'product-page__stock--in' : 'product-page__stock--out'">
                                <span class="pcard__stock-dot" :class="product.inStock ? 'pcard__stock-dot--yes' : 'pcard__stock-dot--no'"></span>
                                {{ product.inStock ? 'В наявності' : 'Немає в наявності' }}
                            </span>
                        </div>

                        <dl v-if="facts.length" class="product-page__facts">
                            <div v-for="fact in facts" :key="fact.label" class="product-page__fact">
                                <dt>
                                    <AppIcon :name="fact.icon" :size="14" />
                                    {{ fact.label }}
                                </dt>
                                <dd>{{ fact.value }}</dd>
                            </div>
                        </dl>

                        <div class="product-page__price-block">
                            <div class="product-page__price-row">
                                <template v-if="hasPrice">
                                    <span class="product-page__price">{{ formatPrice(product.finalPrice) }}</span>
                                    <span class="product-page__currency">грн</span>
                                    <span class="product-page__unit">/ {{ product.unit_name }}</span>
                                </template>
                                <span v-else class="product-page__price product-page__price--on-request">{{ priceOnRequestLabel }}</span>
                            </div>
                            <p v-if="hasPrice && product.discount > 0" class="product-page__old-price">{{ formatPrice(product.price) }} грн</p>
                            <p class="product-page__min-order">Замовлення від {{ minOrderQuantity }} {{ product.unit_name }}</p>

                            <button
                                v-if="product.is_wholesale && product.wholesale_price && product.wholesale_min_quantity"
                                type="button"
                                class="product-page__wholesale product-page__wholesale--action"
                                :title="`Обрати ${product.wholesale_min_quantity} ${product.unit_name_plural || product.unit_name || 'шт'}`"
                                @click="selectWholesaleQty">
                                <div class="product-page__wholesale-label">
                                    <AppIcon name="package" :size="16" />
                                    Оптова ціна — натисніть, щоб обрати кількість
                                </div>
                                <div class="product-page__wholesale-row">
                                    <span class="product-page__wholesale-price">{{ formatPrice(product.wholesale_price) }} грн</span>
                                    <span class="product-page__wholesale-from">від {{ product.wholesale_min_quantity }} {{ product.unit_name_plural || product.unit_name }}</span>
                                </div>
                            </button>
                        </div>

                        <div class="product-page__buy-actions">
                            <ProductBuyBox :product-data="buyBoxProduct" />
                        </div>

                        <p v-if="minOrderTotal > 0" class="product-page__min-sum">
                            <AppIcon name="info" :size="14" />
                            Мінімальна сума замовлення — {{ formatPrice(minOrderTotal) }} {{ currencyLabel }}
                        </p>

                        <ul class="product-page__trust">
                            <li><AppIcon name="truck" :size="16" /> Доставка по Україні</li>
                            <li><AppIcon name="shield-check" :size="16" /> Гарантія якості</li>
                            <li><AppIcon name="rotate-ccw" :size="16" /> Повернення 14 днів</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section class="product-page__details">
            <div class="mx-auto max-w-site px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
                <div class="product-page__details-grid">
                    <div v-if="characteristics.length" class="product-page__section">
                        <h2 class="product-page__section-title">Характеристики</h2>
                        <table class="product-page__specs">
                            <tbody>
                                <tr v-for="(char, index) in characteristics" :key="index">
                                    <th>{{ char.name }}</th>
                                    <td>{{ char.value }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="product-page__section" :class="{ 'product-page__section--full': !characteristics.length }">
                        <h2 class="product-page__section-title">Опис</h2>
                        <div v-if="product.description" class="product-page__description prose max-w-none" v-html="product.description"></div>
                        <p v-else class="text-[#64748b]">Опис товару відсутній.</p>

                        <div v-if="product.complectation" class="product-page__complectation">
                            <h3 class="product-page__complectation-title">Комплектація</h3>
                            <p>{{ product.complectation }}</p>
                        </div>
                    </div>
                </div>

                <div class="product-page__delivery">
                    <div class="product-page__delivery-col">
                        <h3 class="product-page__delivery-title">Доставка</h3>
                        <ul class="product-page__delivery-list">
                            <li><AppIcon name="truck" :size="18" /> Нова Пошта — 1–3 робочих дні</li>
                            <li><AppIcon name="map-pin" :size="18" /> Самовивіз з магазину</li>
                        </ul>
                    </div>
                    <div class="product-page__delivery-col">
                        <h3 class="product-page__delivery-title">Оплата</h3>
                        <ul class="product-page__delivery-list">
                            <li><AppIcon name="banknote" :size="18" /> Готівкою при отриманні</li>
                            <li><AppIcon name="credit-card" :size="18" /> Банківською карткою</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="recommendedProducts.length" class="product-page__similar">
            <div class="mx-auto max-w-site px-4 py-10 sm:px-6 lg:px-8 lg:py-12">
                <h2 class="product-page__similar-title font-heading">Схожі товари</h2>
                <ProductList :products="recommendedProducts" :show-new-badge="false" />
            </div>
        </section>
    </div>
</template>

<script>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { spaState } from '../spa/spaStore.js';
import AppIcon from '../components/AppIcon.vue';
import ProductGallery from '../components/ProductGallery.vue';
import ProductBuyBox from '../components/ProductBuyBox.vue';
import ProductList from '../components/ProductList.vue';
import { getMinOrderTotal, getCurrencyLabel } from '../utils/shopSettings.js';
import { hasSellablePrice, PRICE_ON_REQUEST_LABEL } from '../utils/cart.js';

export default {
    name: 'ProductRoutePage',
    components: { AppIcon, ProductGallery, ProductBuyBox, ProductList },
    setup() {
        const router = useRouter();
        const data = computed(() => spaState.pageData || {});

        const onCrumbClick = (event, url) => {
            if (!url) return;
            try {
                const parsed = new URL(url, window.location.origin);
                if (parsed.origin !== window.location.origin) return;
                event.preventDefault();
                router.push(parsed.pathname + parsed.search);
            } catch {
                // default navigation
            }
        };

        const product = computed(() => data.value.product || null);

        const hasPrice = computed(() => hasSellablePrice(product.value?.finalPrice ?? product.value?.price));

        const minOrderQuantity = computed(() => {
            const value = Number(product.value?.min_order_quantity);
            return value > 0 ? value : 1;
        });

        const facts = computed(() => {
            const item = product.value;
            if (!item) return [];

            const rows = [];
            if (item.brand) rows.push({ icon: 'award', label: 'Бренд', value: item.brand });
            if (item.country) rows.push({ icon: 'globe', label: 'Країна', value: item.country });
            if (item.weight) rows.push({ icon: 'weight', label: 'Вага', value: `${item.weight} кг` });
            if (item.condition_label) rows.push({ icon: 'shield-check', label: 'Стан', value: item.condition_label });
            if (item.units_per_box) {
                rows.push({
                    icon: 'box',
                    label: 'В ящику',
                    value: `${item.units_per_box} ${item.unit_name_plural || item.unit_name || 'шт'}`,
                });
            }

            return rows;
        });

        return {
            product,
            facts,
            hasPrice,
            priceOnRequestLabel: PRICE_ON_REQUEST_LABEL,
            minOrderQuantity,
            minOrderTotal: getMinOrderTotal(),
            currencyLabel: getCurrencyLabel(),
            images: computed(() => data.value.images || []),
            characteristics: computed(() => data.value.characteristics || []),
            recommendedProducts: computed(() => data.value.recommendedProducts || []),
            breadcrumbs: computed(() => data.value.breadcrumbs || []),
            buyBoxProduct: computed(() => data.value.buyBoxProduct || {}),
            placeholder: 'https://via.placeholder.com/800x800/ffffff/1E1E1E?text=Немає+фото',
            onCrumbClick,
        };
    },
    methods: {
        formatPrice(value) {
            const num = Number(value) || 0;
            return num.toLocaleString('uk-UA', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        },
        selectWholesaleQty() {
            const item = this.product;
            if (!item?.wholesale_min_quantity) return;
            window.dispatchEvent(new CustomEvent('set-product-qty', {
                detail: {
                    id: item.id,
                    qty: item.wholesale_min_quantity,
                },
            }));
        },
    },
};
</script>
