<template>
    <div class="buy-box">
        <div v-if="inStock" class="buy-box__qty pcard__qty pcard__qty--lg">
            <button type="button" class="pcard__qty-btn" :disabled="quantity <= 1" aria-label="Зменшити кількість" @click="decreaseQty">
                <AppIcon name="minus" :size="18" />
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
                <span class="pcard__qty-unit">{{ unitLabel }}</span>
            </label>
            <button type="button" class="pcard__qty-btn" aria-label="Збільшити кількість" @click="increaseQty">
                <AppIcon name="plus" :size="18" />
            </button>
        </div>

        <div class="buy-box__actions">
            <button
                type="button"
                class="buy-box__cart"
                :class="{ 'buy-box__cart--in-cart': inCart }"
                :disabled="!inStock"
                @click="handleAddToCart">
                <AppIcon name="shopping-cart" :size="18" />
                <template v-if="!inStock">Немає в наявності</template>
                <template v-else-if="inCart">У кошику · ще +{{ quantity }}</template>
                <template v-else>Додати до кошика</template>
            </button>
            <button type="button"
                    class="buy-box__wishlist"
                    :class="{ 'buy-box__wishlist--active': inWishlist }"
                    @click="handleWishlist"
                    title="Обране">
                <AppIcon name="heart" :size="20" />
            </button>
        </div>

        <p v-if="inCart" class="buy-box__cart-qty">Зараз у кошику: {{ cartQuantity }} {{ unitLabel }}</p>

        <a v-if="inCart" href="/koshyk" class="buy-box__goto">
            Перейти в кошик
            <AppIcon name="arrow-right" :size="16" />
        </a>
    </div>
</template>

<script>
import AppIcon from './AppIcon.vue';
import {
    addProductToCart,
    toggleWishlistItem,
    isProductInWishlist,
    isInStock,
    getCartQuantity,
    parseQuantity,
    onQtyKeydown,
    onQtyPaste,
    filterQtyInputEvent,
} from '../utils/cart.js';

export default {
    name: 'ProductBuyBox',
    components: { AppIcon },
    props: {
        productData: { type: Object, default: null },
    },
    data() {
        return {
            product: {},
            quantityInput: '1',
            inWishlist: false,
            cartQuantity: 0,
        };
    },
    computed: {
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
    },
    watch: {
        productData: {
            deep: true,
            handler(value) {
                if (value && typeof value === 'object') {
                    this.product = { ...value };
                    this.syncState();
                }
            },
        },
    },
    mounted() {
        this.readProduct();
        this.syncState();
        window.addEventListener('cart-updated', this.syncCart);
        window.addEventListener('wishlist-updated', this.syncWishlist);
        window.addEventListener('set-product-qty', this.onExternalQty);
    },
    unmounted() {
        window.removeEventListener('cart-updated', this.syncCart);
        window.removeEventListener('wishlist-updated', this.syncWishlist);
        window.removeEventListener('set-product-qty', this.onExternalQty);
    },
    methods: {
        onQtyKeydown,
        onQtyPaste,
        readProduct() {
            if (this.productData && typeof this.productData === 'object') {
                this.product = { ...this.productData };
                return;
            }

            try {
                this.product = JSON.parse(this.$el?.dataset?.product || '{}');
            } catch {
                this.product = {};
            }
        },
        syncCart() {
            this.cartQuantity = getCartQuantity(this.product.id);
        },
        syncWishlist() {
            this.inWishlist = isProductInWishlist(this.product.id);
        },
        syncState() {
            this.syncCart();
            this.syncWishlist();
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
        onExternalQty(event) {
            const detail = event?.detail || {};
            if (detail.id != null && detail.id != this.product.id) return;
            if (detail.qty != null) this.setQty(detail.qty);
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

<style scoped>
.buy-box__qty { margin-bottom: 0.75rem; max-width: 280px; }

.buy-box__actions {
    display: flex;
    gap: 0.5rem;
}

.buy-box__cart {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.875rem 1.25rem;
    background: #FF6B4A;
    border: none;
    color: #1A2B36;
    font-family: Fredoka, sans-serif;
    font-weight: 700;
    font-size: 0.9375rem;
    cursor: pointer;
    transition: background 0.2s;
}

.buy-box__cart:hover:not(:disabled) { background: #E85A3C; }
.buy-box__cart:disabled { background: #eee; color: #999; cursor: not-allowed; }

.buy-box__cart--in-cart {
    background: #0E3A45;
    color: #fff;
}

.buy-box__cart--in-cart:hover:not(:disabled) {
    background: #16375f;
}

.buy-box__cart-qty {
    margin: 0.5rem 0 0;
    font-size: 0.8125rem;
    color: #64748b;
}

.buy-box__wishlist {
    width: 3rem;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border: 1px solid #ddd;
    color: #666;
    cursor: pointer;
    transition: all 0.2s;
}

.buy-box__wishlist:hover,
.buy-box__wishlist--active {
    border-color: #FF6B4A;
    color: #1A2B36;
    background: #EEF8F7;
}

.buy-box__goto {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    margin-top: 0.75rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #0E3A45;
    text-decoration: none;
}

.buy-box__goto:hover { color: #FF6B4A; }
</style>
