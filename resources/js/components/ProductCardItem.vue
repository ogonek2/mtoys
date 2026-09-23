<template>
    <article
        class="pcard"
        :class="{ 'pcard--oos': !inStock, 'pcard--in-cart': inCart }">
        <div class="pcard__media" :class="{ 'pcard__media--oos': !inStock }">
            <a :href="productUrl" class="pcard__media-link" :title="product.name">
                <img
                    v-if="product.image_path"
                    :src="product.image_path"
                    :alt="product.name"
                    loading="lazy"
                    class="pcard__img" />
                <div v-else class="pcard__img-empty">
                    <AppIcon name="image" :size="32" />
                </div>
            </a>

            <span v-if="product.discount > 0" class="pcard__badge pcard__badge--sale">
                -{{ product.discount }}%
            </span>
            <span v-else-if="showNewBadge" class="pcard__badge pcard__badge--new">NEW</span>

            <button
                type="button"
                class="pcard__wish"
                :class="{ 'is-active': inWishlist }"
                :title="inWishlist ? 'Прибрати з обраного' : 'Додати в обране'"
                :aria-pressed="inWishlist"
                @click="handleWishlist">
                <AppIcon name="heart" :size="18" />
            </button>
        </div>

        <div class="pcard__body">
            <div class="pcard__meta">
                <div v-if="ratingValue > 0" class="pcard__stars" :aria-label="`${ratingValue} з 5`">
                    <AppIcon
                        v-for="n in 5"
                        :key="n"
                        name="star"
                        :size="12"
                        :icon-class="n <= Math.round(ratingValue) ? 'pcard__star pcard__star--on' : 'pcard__star'" />
                </div>
                <span v-else class="pcard__reviews">{{ reviewsLabel }}</span>
            </div>

            <h3 class="pcard__title">
                <a :href="productUrl" :title="product.name">{{ product.name }}</a>
            </h3>

            <button
                v-if="hasWholesale"
                type="button"
                class="pcard__wholesale"
                :class="{ 'pcard__wholesale--active': quantity >= wholesaleMinQty }"
                :title="`Опт від ${wholesaleMinQty} ${pluralUnitLabel}`"
                @click="selectWholesaleQty">
                <span class="pcard__wholesale-price">{{ formatPrice(product.wholesale_price) }} ₴</span>
                <span class="pcard__wholesale-from">опт · {{ wholesaleMinQty }}+</span>
            </button>

            <div class="pcard__price-row">
                <div class="pcard__price-wrap">
                    <span class="pcard__price" :class="{ 'pcard__price--empty': !hasPrice }">{{ priceLabel }}</span>
                    <span v-if="hasPrice && product.discount > 0" class="pcard__old">{{ formatPrice(product.price) }}</span>
                </div>
            </div>

            <button
                v-if="boxQty"
                type="button"
                class="pcard__box-btn"
                :title="`Обрати ${boxQty} ${pluralUnitLabel}`"
                @click="selectBoxQty">
                Ящик · {{ boxQty }} {{ pluralUnitLabel }}
            </button>
        </div>

        <div class="pcard__cart-panel" :class="{ 'pcard__cart-panel--solo': !inStock }">
            <div v-if="inStock" class="pcard__qty" title="Кількість">
                <button type="button" class="pcard__qty-btn" :disabled="quantity <= 1" aria-label="Зменшити" @click="decreaseQty">
                    <AppIcon name="minus" :size="14" />
                </button>
                <label class="pcard__qty-field">
                    <input
                        ref="qtyInput"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        autocomplete="off"
                        class="pcard__qty-input"
                        aria-label="Кількість"
                        :value="quantityInput"
                        @keydown="onQtyKeydown"
                        @paste="onQtyPaste"
                        @input="onQtyInput"
                        @keydown.enter.prevent="handleAddToCart"
                        @blur="normalizeQty" />
                </label>
                <button type="button" class="pcard__qty-btn" aria-label="Збільшити" @click="increaseQty">
                    <AppIcon name="plus" :size="14" />
                </button>
            </div>

            <button
                type="button"
                class="pcard__buy"
                :class="{ 'pcard__buy--added': inCart }"
                :disabled="!inStock"
                :title="cartButtonTitle"
                @click="handleAddToCart">
                <AppIcon name="shopping-cart" :size="16" class="shrink-0" />
                <span>{{ cartButtonLabel }}</span>
            </button>
        </div>
    </article>
</template>

<script>
import AppIcon from './AppIcon.vue';
import {
    addProductToCart,
    toggleWishlistItem,
    isProductInWishlist,
    finalPrice,
    formatPrice,
    formatProductPrice,
    hasSellablePrice,
    isInStock,
    hasWholesaleOffer,
    getCartQuantity,
    parseQuantity,
    onQtyKeydown,
    onQtyPaste,
    filterQtyInputEvent,
} from '../utils/cart.js';

export default {
    name: 'ProductCardItem',
    components: { AppIcon },
    props: {
        product: { type: Object, required: true },
        showNewBadge: { type: Boolean, default: false },
    },
    data() {
        return {
            quantityInput: '1',
            inWishlist: false,
            cartQuantity: 0,
        };
    },
    computed: {
        productUrl() {
            const cat = this.product.category_url || 'catalog';
            return `/catalog/categoriya/${cat}/${this.product.url}`;
        },
        inStock() {
            return isInStock(this.product);
        },
        inCart() {
            return this.cartQuantity > 0;
        },
        quantity() {
            return parseQuantity(this.quantityInput, 1);
        },
        pluralUnitLabel() {
            return this.product.unit_name_plural || this.product.unit_name || 'шт';
        },
        hasWholesale() {
            return hasWholesaleOffer(this.product);
        },
        hasPrice() {
            return hasSellablePrice(finalPrice(this.product));
        },
        priceLabel() {
            return formatProductPrice(finalPrice(this.product), { currency: '₴' });
        },
        wholesaleMinQty() {
            return parseQuantity(this.product.wholesale_min_quantity, 0);
        },
        boxQty() {
            return parseQuantity(this.product.units_per_box, 0);
        },
        reviewsCount() {
            const n = Number(this.product.reviews_count ?? this.product.reviews ?? 0);
            return Number.isFinite(n) && n > 0 ? Math.floor(n) : 0;
        },
        ratingValue() {
            const n = Number(this.product.rating ?? this.product.avg_rating ?? 0);
            return Number.isFinite(n) && n > 0 ? Math.min(5, n) : 0;
        },
        reviewsLabel() {
            const n = this.reviewsCount;
            if (n === 0) return '0 відгуків';
            const mod10 = n % 10;
            const mod100 = n % 100;
            if (mod10 === 1 && mod100 !== 11) return `${n} відгук`;
            if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return `${n} відгуки`;
            return `${n} відгуків`;
        },
        cartButtonLabel() {
            if (!this.inStock) return 'Немає';
            if (this.inCart) return `+${this.quantity}`;
            return 'Купити';
        },
        cartButtonTitle() {
            if (!this.inStock) return 'Немає в наявності';
            if (this.inCart) return `Додати ще ${this.quantity}`;
            return 'Додати до кошика';
        },
    },
    mounted() {
        this.syncWishlist();
        this.syncCart();
        window.addEventListener('wishlist-updated', this.syncWishlist);
        window.addEventListener('cart-updated', this.syncCart);
    },
    unmounted() {
        window.removeEventListener('wishlist-updated', this.syncWishlist);
        window.removeEventListener('cart-updated', this.syncCart);
    },
    methods: {
        finalPrice,
        formatPrice,
        onQtyKeydown,
        onQtyPaste,
        syncWishlist() {
            this.inWishlist = isProductInWishlist(this.product.id);
        },
        syncCart() {
            this.cartQuantity = getCartQuantity(this.product.id);
        },
        readQty() {
            const fromDom = this.$refs.qtyInput?.value;
            return parseQuantity(fromDom ?? this.quantityInput, 1);
        },
        setQty(value) {
            this.quantityInput = String(parseQuantity(value, 1));
        },
        onQtyInput(event) {
            this.quantityInput = filterQtyInputEvent(event);
        },
        decreaseQty() {
            this.setQty(Math.max(1, this.readQty() - 1));
        },
        increaseQty() {
            this.setQty(this.readQty() + 1);
        },
        normalizeQty() {
            this.setQty(this.readQty());
        },
        selectWholesaleQty() {
            if (!this.wholesaleMinQty) return;
            this.setQty(this.wholesaleMinQty);
        },
        selectBoxQty() {
            if (!this.boxQty) return;
            this.setQty(this.boxQty);
        },
        handleAddToCart() {
            if (!this.inStock) return;
            const qty = this.readQty();
            this.setQty(qty);
            addProductToCart(this.product, qty);
            this.syncCart();
        },
        handleWishlist() {
            toggleWishlistItem(this.product);
            this.syncWishlist();
        },
    },
};
</script>
