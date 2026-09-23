<template>
    <article
        class="pcard flex h-full flex-col"
        :class="{ 'pcard--oos': !inStock, 'pcard--in-cart': inCart }">
        <div class="pcard__media relative aspect-[4/5] sm:aspect-square" :class="{ 'opacity-55 grayscale': !inStock }">
            <a :href="productUrl" class="block h-full w-full" :title="product.name">
                <img v-if="product.image_path" :src="product.image_path" :alt="product.name" loading="lazy"
                    class="h-full w-full object-contain p-2.5 sm:p-3" />
                <div v-else class="flex h-full w-full items-center justify-center text-gray-300">
                    <AppIcon name="image" :size="28" />
                </div>
            </a>

            <span v-if="product.discount > 0"
                class="pcard__badge absolute left-2 top-2 bg-[#FF6B4A] px-2 py-0.5 text-[0.625rem] font-bold text-white">
                -{{ product.discount }}%
            </span>
            <span v-else-if="showNewBadge"
                class="pcard__badge absolute left-2 top-2 bg-[#FFB703] px-2 py-0.5 text-[0.625rem] font-bold text-[#1A2B36]">
                New
            </span>

            <span
                v-if="inCart"
                class="pcard__badge absolute bottom-2 left-2 bg-[#0E3A45]/90 px-2 py-0.5 text-[0.625rem] font-semibold text-white backdrop-blur-sm">
                {{ cartQuantity }} у кошику
            </span>

            <button type="button"
                class="pcard__wish absolute right-2 top-2 flex h-8 w-8 items-center justify-center text-gray-400 transition-colors"
                :class="{ 'is-active': inWishlist }"
                :title="inWishlist ? 'Прибрати з обраного' : 'Додати в обране'"
                @click="handleWishlist">
                <AppIcon name="heart" :size="15" />
            </button>
        </div>

        <div class="pcard__body flex flex-1 flex-col gap-1.5 p-2.5 sm:p-3" :class="{ 'opacity-70': !inStock }">
            <h3 class="pcard__title">
                <a :href="productUrl" class="transition-colors hover:text-[#FF6B4A]">{{ product.name }}</a>
            </h3>

            <div class="pcard__price-row">
                <span class="pcard__stock-dot" :class="inStock ? 'pcard__stock-dot--yes' : 'pcard__stock-dot--no'" :title="inStock ? 'В наявності' : 'Немає в наявності'"></span>
                <span class="pcard__price" :class="{ 'pcard__price--empty': !hasPrice }">{{ priceLabel }}</span>
                <span v-if="hasPrice && product.discount > 0" class="pcard__old">{{ formatPrice(product.price) }}</span>
            </div>

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

            <div class="pcard__actions mt-auto" :class="{ 'pcard__actions--solo': !inStock }">
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

                <button type="button"
                    class="pcard__cart-btn"
                    :class="inCart ? 'pcard__cart-btn--added' : ''"
                    :disabled="!inStock"
                    :title="cartButtonTitle"
                    @click="handleAddToCart">
                    <AppIcon name="shopping-cart" :size="16" class="shrink-0" />
                    <span class="pcard__cart-label">{{ cartButtonLabel }}</span>
                </button>
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
        unitLabel() {
            return this.product.unit_name || 'шт';
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
        cartButtonLabel() {
            if (!this.inStock) return 'Немає';
            if (this.inCart) return `+${this.quantity}`;
            return 'В кошик';
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
            const qty = parseQuantity(value, 1);
            this.quantityInput = String(qty);
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
