<template>
    <section v-if="categories.length" class="home-chips">
        <div class="mx-auto max-w-site px-4 sm:px-6 lg:px-8">
            <div class="home-chips__head">
                <h2 class="home-chips__title">Популярні категорії</h2>
                <a :href="catalogUrl" class="home-chips__all">
                    Усі категорії
                    <AppIcon name="arrow-right" :size="15" />
                </a>
            </div>
            <div class="home-chips__track" role="list">
                <a
                    v-for="(category, index) in categories"
                    :key="category.url"
                    :href="category.href || `/catalog/categoriya/${category.url}`"
                    class="home-chips__item"
                    role="listitem">
                    <span class="home-chips__icon">
                        <AppIcon :name="iconFor(index)" :size="16" />
                    </span>
                    <span class="home-chips__name">{{ category.name }}</span>
                </a>
            </div>
        </div>
    </section>
</template>

<script>
import AppIcon from './AppIcon.vue';

const ICONS = ['package', 'box', 'layers', 'star', 'tag', 'shopping-bag', 'boxes', 'gift', 'puzzle'];

export default {
    name: 'HomeCategoryChips',
    components: { AppIcon },
    props: {
        categories: { type: Array, default: () => [] },
        catalogUrl: { type: String, default: '/catalog' },
    },
    methods: {
        iconFor(index) {
            return ICONS[index % ICONS.length];
        },
    },
};
</script>
