<template>
    <div class="wishlist-page">
        <div v-if="items.length > 0" class="wishlist-page__grid">
            <article v-for="item in items" :key="item.id" class="wishlist-page__item">
                <a :href="itemUrl(item)" class="wishlist-page__thumb">
                    <img v-if="item.image || item.image_path" :src="item.image || item.image_path" :alt="item.name">
                    <AppIcon v-else name="image" :size="28" icon-class="text-gray-300" />
                </a>

                <div class="wishlist-page__body">
                    <h3 class="wishlist-page__name">
                        <a :href="itemUrl(item)">{{ item.name }}</a>
                    </h3>
                    <p class="wishlist-page__price">{{ formatProductPrice(item.price) }}</p>

                    <div class="wishlist-page__actions">
                        <a :href="itemUrl(item)" class="wishlist-page__view">
                            <AppIcon name="eye" :size="16" />
                            Переглянути
                        </a>
                        <button type="button" class="wishlist-page__cart" @click="addToCart(item)">
                            <AppIcon name="shopping-cart" :size="16" />
                            В кошик
                        </button>
                        <button type="button" class="wishlist-page__remove" @click="remove(item.id)">
                            <AppIcon name="trash" :size="16" />
                        </button>
                    </div>
                </div>
            </article>
        </div>

        <div v-else class="wishlist-page__empty">
            <AppIcon name="heart" :size="48" icon-class="text-gray-300" />
            <h2>Обране порожнє</h2>
            <p>Додайте товари, які вам сподобались</p>
            <a href="/catalog" class="btn-domiko-primary inline-flex items-center gap-2 px-6 py-3">
                <AppIcon name="shopping-bag" :size="18" />
                Перейти в каталог
            </a>
        </div>
    </div>
</template>

<script>
import AppIcon from './AppIcon.vue';
import { addProductToCart, formatProductPrice as displayProductPrice } from '../utils/cart.js';

export default {
    name: 'WishlistPage',
    components: { AppIcon },
    data() {
        return { items: [] };
    },
    mounted() {
        this.load();
        window.addEventListener('wishlist-updated', this.load);
    },
    unmounted() {
        window.removeEventListener('wishlist-updated', this.load);
    },
    methods: {
        load() {
            try {
                const raw = JSON.parse(localStorage.getItem('wishlist') || '[]');
                this.items = raw.map(entry => {
                    if (typeof entry === 'object' && entry !== null) return entry;
                    return { id: entry, name: 'Товар #' + entry, price: 0 };
                });
            } catch {
                this.items = [];
            }
        },
        remove(id) {
            this.items = this.items.filter(i => i.id != id);
            localStorage.setItem('wishlist', JSON.stringify(this.items));
            window.dispatchEvent(new Event('wishlist-updated'));
            if (this.$toast) this.$toast.info('Видалено з обраного');
        },
        addToCart(item) {
            addProductToCart({
                id: item.id,
                name: item.name,
                price: item.price,
                discount: item.discount || 0,
                image_path: item.image || item.image_path,
                articule: item.articule,
                availability: 1,
            }, 1);
        },
        formatProductPrice(price) {
            return displayProductPrice(price, { currency: '₴' });
        },
        itemUrl(item) {
            const cat = item.category_url || 'catalog';
            const slug = item.url || '';
            return slug ? `/catalog/categoriya/${cat}/${slug}` : '/catalog';
        },
    },
};
</script>

<style scoped>
.wishlist-page__grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.75rem;
}

@media (min-width: 640px) {
    .wishlist-page__grid { grid-template-columns: repeat(2, 1fr); }
}
@media (min-width: 1024px) {
    .wishlist-page__grid { grid-template-columns: repeat(3, 1fr); }
}

.wishlist-page__item {
    display: flex; gap: 1rem;
    border: 1px solid #ddd; background: #fff; padding: 1rem;
}

.wishlist-page__thumb {
    width: 80px; height: 80px; flex-shrink: 0;
    border: 1px solid #eee; background: #fafafa;
    display: flex; align-items: center; justify-content: center;
}

.wishlist-page__thumb img { max-width: 100%; max-height: 100%; object-fit: contain; }

.wishlist-page__name { font-size: 0.875rem; font-weight: 600; margin: 0 0 0.375rem; line-height: 1.35; }
.wishlist-page__name a { color: #1A2B36; text-decoration: none; }
.wishlist-page__name a:hover { color: #FF6B4A; }
.wishlist-page__price { font-weight: 700; color: #1A2B36; font-family: Fredoka, sans-serif; margin: 0 0 0.75rem; }

.wishlist-page__actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }

.wishlist-page__view,
.wishlist-page__cart {
    display: inline-flex; align-items: center; gap: 0.375rem;
    padding: 0.375rem 0.625rem; font-size: 0.75rem; font-weight: 600;
    text-decoration: none; border: none; cursor: pointer;
}

.wishlist-page__view { background: #FF6B4A; color: #1A2B36; }
.wishlist-page__view:hover { background: #E85A3C; }
.wishlist-page__cart { background: #0E3A45; color: #fff; }
.wishlist-page__cart:hover { background: #1A2433; }

.wishlist-page__remove {
    background: none; border: 1px solid #ddd; padding: 0.375rem 0.5rem;
    color: #666; cursor: pointer;
}
.wishlist-page__remove:hover { border-color: #1A2B36; color: #1A2B36; }

.wishlist-page__empty {
    text-align: center; padding: 4rem 1rem;
    border: 1px solid #ddd; background: #fff;
}

.wishlist-page__empty h2 { font-family: Fredoka, sans-serif; font-size: 1.5rem; margin: 1rem 0 0.5rem; color: #0E3A45; }
.wishlist-page__empty p { color: #666; margin-bottom: 1.5rem; }
</style>
