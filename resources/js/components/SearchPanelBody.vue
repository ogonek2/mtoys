<template>
    <div class="search-panel" :class="mode === 'sheet' ? 'search-panel--sheet' : 'search-panel--dropdown'">
        <div v-if="loading" class="search-panel__state">
            <div class="search-panel__spinner"></div>
            <p>Шукаємо...</p>
        </div>

        <div v-else-if="query.length >= 2 && suggestions.length === 0" class="search-panel__state">
            <AppIcon name="search" :size="36" icon-class="text-gray-300" />
            <p class="search-panel__state-title">Нічого не знайдено</p>
            <p class="search-panel__state-text">Спробуйте інший запит або перейдіть у каталог</p>
            <a href="/catalog" class="search-panel__catalog-link">Перейти в каталог</a>
        </div>

        <template v-else-if="suggestions.length > 0">
            <div class="search-panel__head">
                <p class="search-panel__head-title">Товари</p>
                <button type="button" class="search-panel__head-action" @click="$emit('submit')">
                    Усі результати
                    <AppIcon name="arrow-right" :size="14" />
                </button>
            </div>

            <ul class="search-panel__list">
                <li v-for="item in suggestions" :key="item.id">
                    <a :href="productUrl(item)" class="search-panel__item" @click="$emit('pick')">
                        <span class="search-panel__thumb">
                            <img v-if="item.image_path" :src="item.image_path" :alt="item.name" loading="lazy">
                            <AppIcon v-else name="image" :size="20" icon-class="text-gray-300" />
                        </span>
                        <span class="search-panel__body">
                            <span class="search-panel__name">{{ item.name }}</span>
                            <span class="search-panel__meta">
                                <span class="search-panel__price">{{ formatProductPrice(item.price) }}</span>
                                <span v-if="item.discount > 0" class="search-panel__discount">-{{ item.discount }}%</span>
                            </span>
                        </span>
                        <AppIcon name="chevron-right" :size="16" icon-class="search-panel__arrow" />
                    </a>
                </li>
            </ul>

            <div class="search-panel__footer">
                <button type="button" class="search-panel__all-btn" @click="$emit('submit')">
                    Показати всі результати для «{{ query }}»
                </button>
            </div>
        </template>
    </div>
</template>

<script>
import AppIcon from './AppIcon.vue';
import { formatProductPrice as displayProductPrice } from '../utils/cart.js';

export default {
    name: 'SearchPanelBody',
    components: { AppIcon },
    props: {
        mode: { type: String, default: 'dropdown' },
        loading: { type: Boolean, default: false },
        query: { type: String, default: '' },
        suggestions: { type: Array, default: () => [] },
    },
    emits: ['submit', 'pick'],
    methods: {
        productUrl(item) {
            const category = item.category_url || 'catalog';
            return `/catalog/categoriya/${encodeURIComponent(category)}/${encodeURIComponent(item.url)}`;
        },
        formatProductPrice(price) {
            return displayProductPrice(price, { currency: '₴' });
        },
    },
};
</script>
