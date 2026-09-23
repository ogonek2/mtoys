<template>
    <div class="checkout-cart">
        <div v-if="cart.length > 0" class="checkout-cart__list">
            <div v-for="item in cart" :key="item.id" class="checkout-cart__item">
                <div class="checkout-cart__thumb">
                    <img v-if="item.image" :src="item.image" :alt="item.name">
                    <AppIcon v-else name="image" :size="22" icon-class="text-gray-300" />
                </div>

                <div class="checkout-cart__info">
                    <h4 class="checkout-cart__name">{{ item.name }}</h4>
                    <p v-if="item.articule" class="checkout-cart__articule">Артикул: {{ item.articule }}</p>
                    <div class="checkout-cart__price-row">
                        <span class="checkout-cart__price">{{ formatPrice(getItemPrice(item)) }} ₴</span>
                        <span v-if="isWholesaleActive(item)" class="checkout-cart__opt">Опт</span>
                    </div>

                    <div class="checkout-cart__actions">
                        <div class="pcard__qty checkout-cart__qty">
                            <button type="button" class="pcard__qty-btn" :disabled="draftQty(item) <= 1" @click="decrease(item.id)">
                                <AppIcon name="minus" :size="12" />
                            </button>
                            <label class="pcard__qty-field">
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    autocomplete="off"
                                    class="pcard__qty-input"
                                    aria-label="Кількість"
                                    :value="qtyDrafts[item.id] ?? item.quantity"
                                    @keydown="onQtyKeydown"
                                    @paste="onQtyPaste"
                                    @input="onCartQtyInput(item.id, $event)"
                                    @blur="commitCartQty(item.id, $event)"
                                    @keydown.enter.prevent="commitCartQty(item.id, $event)" />
                            </label>
                            <button type="button" class="pcard__qty-btn" @click="increase(item.id)">
                                <AppIcon name="plus" :size="12" />
                            </button>
                        </div>
                        <button type="button" class="checkout-cart__remove" @click="remove(item.id)" title="Видалити">
                            <AppIcon name="trash" :size="14" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="checkout-cart__empty">
            <AppIcon name="shopping-cart" :size="36" icon-class="text-gray-300" />
            <h4>Кошик порожній</h4>
            <p>Додайте товари для оформлення замовлення</p>
            <a href="/catalog" class="btn-domiko-primary inline-flex items-center gap-2 px-5 py-2.5 text-sm">
                <AppIcon name="shopping-bag" :size="16" />
                До каталогу
            </a>
        </div>

        <div v-if="cart.length > 0" class="checkout-cart__total">
            <div class="checkout-cart__total-row">
                <span>Товарів</span>
                <span>{{ totalItems }} шт</span>
            </div>
            <div class="checkout-cart__total-row checkout-cart__total-row--sum">
                <span>Разом</span>
                <span>{{ formatPrice(totalPrice) }} ₴</span>
            </div>
            <p v-if="isBelowMinimum" class="checkout-cart__notice checkout-cart__notice--warn">
                Мінімальна сума — {{ formatPrice(minOrderTotal) }} {{ currencySymbol }}. Додайте ще на {{ formatPrice(amountToReachMinimum) }} {{ currencySymbol }}.
            </p>
            <p v-else-if="minOrderTotal > 0" class="checkout-cart__notice checkout-cart__notice--ok">
                Мінімальну суму досягнуто — можна оформлювати.
            </p>
        </div>

        <input type="hidden" id="total_price_stream" :value="totalPrice">
    </div>
</template>

<script>
import AppIcon from './AppIcon.vue';
import {
    parseQuantity,
    onQtyKeydown,
    onQtyPaste,
    filterQtyInputEvent,
} from '../utils/cart.js';
import { getMinOrderTotal, getCurrencySymbol } from '../utils/shopSettings.js';

export default {
    name: 'CartList',
    components: { AppIcon },
    data() {
        return {
            cart: [],
            qtyDrafts: {},
            minOrderTotal: getMinOrderTotal(),
            currencySymbol: getCurrencySymbol(),
        };
    },
    computed: {
        totalPrice() {
            const total = this.cart.reduce((sum, item) => sum + this.getItemPrice(item) * item.quantity, 0);
            return Number(total.toFixed(2));
        },
        totalItems() {
            return this.cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
        },
        isBelowMinimum() {
            return this.minOrderTotal > 0 && this.totalPrice < this.minOrderTotal;
        },
        amountToReachMinimum() {
            const difference = this.minOrderTotal - this.totalPrice;
            return difference > 0 ? Math.ceil(difference) : 0;
        },
    },
    mounted() {
        this.loadCart();
        window.addEventListener('cart-updated', this.loadCart);
    },
    unmounted() {
        window.removeEventListener('cart-updated', this.loadCart);
    },
    methods: {
        onQtyKeydown,
        onQtyPaste,
        draftQty(item) {
            if (Object.prototype.hasOwnProperty.call(this.qtyDrafts, item.id)) {
                return parseQuantity(this.qtyDrafts[item.id], 1);
            }
            return parseQuantity(item.quantity, 1);
        },
        loadCart() {
            try {
                this.cart = JSON.parse(localStorage.getItem('cart') || '[]');
            } catch {
                this.cart = [];
            }
            this.qtyDrafts = {};
        },
        saveCart() {
            localStorage.setItem('cart', JSON.stringify(this.cart));
            window.dispatchEvent(new Event('cart-updated'));
        },
        clearDraft(id) {
            if (Object.prototype.hasOwnProperty.call(this.qtyDrafts, id)) {
                delete this.qtyDrafts[id];
            }
        },
        setQuantity(id, value) {
            const item = this.cart.find((entry) => entry.id == id);
            if (!item) return;
            item.quantity = parseQuantity(value, 1);
            this.clearDraft(id);
            this.saveCart();
        },
        onCartQtyInput(id, event) {
            this.qtyDrafts[id] = filterQtyInputEvent(event);
        },
        commitCartQty(id, event) {
            const raw = filterQtyInputEvent(event);
            this.setQuantity(id, raw);
            if (event?.target) event.target.value = String(parseQuantity(raw, 1));
        },
        increase(id) {
            const item = this.cart.find((entry) => entry.id == id);
            if (!item) return;
            this.setQuantity(id, parseQuantity(item.quantity, 1) + 1);
        },
        decrease(id) {
            const item = this.cart.find((entry) => entry.id == id);
            if (!item || item.quantity <= 1) return;
            this.setQuantity(id, parseQuantity(item.quantity, 1) - 1);
        },
        remove(id) {
            this.cart = this.cart.filter((entry) => entry.id != id);
            this.clearDraft(id);
            this.saveCart();
        },
        getItemPrice(item) {
            if (item.isWholesale && item.wholesalePrice && item.wholesaleMinQuantity && item.quantity >= item.wholesaleMinQuantity) {
                return parseFloat(item.wholesalePrice);
            }

            let price = item.price;
            if (typeof price === 'string') {
                price = parseFloat(price.replace(/[^\d.,]/g, '').replace(',', '.'));
            }

            return isNaN(price) ? 0 : price;
        },
        isWholesaleActive(item) {
            return item.isWholesale && item.wholesalePrice && item.wholesaleMinQuantity && item.quantity >= item.wholesaleMinQuantity;
        },
        formatPrice(price) {
            return Math.round(price).toLocaleString('uk-UA');
        },
    },
};
</script>

<style scoped>
.checkout-cart__list {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    max-height: 24rem;
    overflow-y: auto;
    padding-right: 0.25rem;
}

.checkout-cart__item {
    display: grid;
    grid-template-columns: 64px 1fr;
    gap: 0.75rem;
    padding: 0.7rem;
    border: 1px solid #e8eef2;
    border-radius: 0.9rem;
    background: #f7fbfc;
}

.checkout-cart__thumb {
    width: 64px;
    height: 64px;
    border-radius: 0.7rem;
    border: none;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.checkout-cart__thumb img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.checkout-cart__name {
    margin: 0 0 0.2rem;
    font-size: 0.8125rem;
    font-weight: 700;
    color: #1A2B36;
    line-height: 1.35;
}

.checkout-cart__articule {
    margin: 0 0 0.35rem;
    font-size: 0.6875rem;
    color: #5B6B76;
}

.checkout-cart__price {
    font-weight: 700;
    font-size: 0.875rem;
    font-family: Fredoka, sans-serif;
    color: #0E3A45;
}

.checkout-cart__opt {
    margin-left: 0.4rem;
    font-size: 0.625rem;
    font-weight: 700;
    background: #FF6B4A;
    color: #fff;
    padding: 0.12rem 0.4rem;
    border-radius: 999px;
}

.checkout-cart__actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.checkout-cart__qty {
    max-width: 140px;
    width: 140px;
    flex-shrink: 0;
}

.checkout-cart__remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.35rem;
    height: 2.35rem;
    margin: 0;
    padding: 0;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 999px;
    color: #5B6B76;
    cursor: pointer;
    line-height: 1;
    transition: border-color 0.15s, color 0.15s, background 0.15s;
}

.checkout-cart__remove:hover {
    border-color: #FF6B4A;
    color: #FF6B4A;
    background: rgba(255, 107, 74, 0.08);
}

.checkout-cart__empty {
    text-align: center;
    padding: 2rem 1rem;
    border: 1px dashed #d5e2e8;
    border-radius: 0.9rem;
    background: #f7fbfc;
}

.checkout-cart__empty h4 {
    font-family: Fredoka, sans-serif;
    font-size: 1rem;
    margin: 0.75rem 0 0.35rem;
    color: #0E3A45;
}

.checkout-cart__empty p {
    color: #5B6B76;
    font-size: 0.8125rem;
    margin: 0 0 1rem;
}

.checkout-cart__total {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px dashed #e5edf1;
}

.checkout-cart__total-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.8125rem;
    color: #5B6B76;
    margin-bottom: 0.4rem;
}

.checkout-cart__total-row--sum {
    font-size: 1.125rem;
    font-weight: 700;
    color: #0E3A45;
    font-family: Fredoka, sans-serif;
    margin: 0.75rem 0 1rem;
}

.checkout-cart__notice {
    font-size: 0.75rem;
    padding: 0.75rem;
    border: 1px solid #e8eef2;
    border-radius: 0.75rem;
    margin: 0;
}

.checkout-cart__notice--warn {
    background: rgba(255, 107, 74, 0.08);
    border-color: rgba(255, 107, 74, 0.35);
    color: #5B6B76;
}

.checkout-cart__notice--ok {
    background: rgba(46, 196, 182, 0.1);
    border-color: rgba(46, 196, 182, 0.28);
    color: #0E3A45;
}
</style>
