<template>
    <div class="product-page__gallery">
        <div class="product-page__main-image-wrap">
            <span v-if="discount > 0" class="product-page__badge">-{{ discount }}%</span>
            <span v-if="isWholesale" class="product-page__badge product-page__badge--wholesale">Опт</span>

            <div class="product-page__main-image">
                <img
                    :src="currentSrc"
                    :alt="alt"
                    class="product-page__image"
                    @error="onImageError">

                <template v-if="images.length > 1">
                    <button type="button" class="product-page__nav product-page__nav--prev" aria-label="Попереднє фото" @click="prev">
                        <AppIcon name="chevron-left" :size="20" />
                    </button>
                    <button type="button" class="product-page__nav product-page__nav--next" aria-label="Наступне фото" @click="next">
                        <AppIcon name="chevron-right" :size="20" />
                    </button>
                    <span class="product-page__counter">{{ current + 1 }} / {{ images.length }}</span>
                </template>
            </div>
        </div>

        <div v-if="images.length > 1" class="product-page__thumbs">
            <button
                v-for="(src, index) in images"
                :key="index"
                type="button"
                class="product-page__thumb"
                :class="{ 'product-page__thumb--active': index === current }"
                :aria-label="`Фото ${index + 1}`"
                @click="go(index)">
                <img :src="src" alt="" @error="onThumbError">
            </button>
        </div>
    </div>
</template>

<script>
import AppIcon from './AppIcon.vue';

export default {
    name: 'ProductGallery',
    components: { AppIcon },
    props: {
        images: { type: Array, default: () => [] },
        alt: { type: String, default: '' },
        discount: { type: Number, default: 0 },
        isWholesale: { type: Boolean, default: false },
        placeholder: {
            type: String,
            default: 'https://via.placeholder.com/800x800/ffffff/1E1E1E?text=Немає+фото',
        },
    },
    data() {
        return { current: 0 };
    },
    computed: {
        currentSrc() {
            return this.images[this.current] || this.placeholder;
        },
    },
    methods: {
        go(index) {
            this.current = index;
        },
        prev() {
            this.go((this.current - 1 + this.images.length) % this.images.length);
        },
        next() {
            this.go((this.current + 1) % this.images.length);
        },
        onImageError(e) {
            e.target.src = this.placeholder;
        },
        onThumbError(e) {
            e.target.src = this.placeholder;
        },
    },
};
</script>
