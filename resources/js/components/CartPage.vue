<template>
    <div class="cart-page">
        <div v-if="cart.length > 0" class="cart-page__layout">
            <div class="cart-page__items">
                <div v-for="item in cart" :key="item.id" class="cart-page__item">
                    <a :href="itemUrl(item)" class="cart-page__thumb">
                        <img v-if="item.image" :src="item.image" :alt="item.name">
                        <AppIcon v-else name="image" :size="28" icon-class="text-gray-300" />
                    </a>

                    <div class="cart-page__info">
                        <h3 class="cart-page__name">
                            <a :href="itemUrl(item)">{{ item.name }}</a>
                        </h3>
                        <p class="cart-page__articule" v-if="item.articule">Артикул: {{ item.articule }}</p>
                        <div class="cart-page__price-row">
                            <span class="cart-page__price">{{ formatPrice(getItemPrice(item)) }} ₴</span>
                            <span v-if="isWholesaleActive(item)" class="cart-page__opt">Опт</span>
                        </div>

                        <div class="cart-page__actions">
                            <div class="pcard__qty cart-page__qty">
                                <button type="button" class="pcard__qty-btn" :disabled="draftQty(item) <= 1" @click="decrease(item.id)">
                                    <AppIcon name="minus" :size="14" />
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
                                    <span class="pcard__qty-unit">шт</span>
                                </label>
                                <button type="button" class="pcard__qty-btn" @click="increase(item.id)">
                                    <AppIcon name="plus" :size="14" />
                                </button>
                            </div>
                            <button type="button" class="cart-page__remove" @click="remove(item.id)">
                                <AppIcon name="trash" :size="16" />
                            </button>
                        </div>
                    </div>

                    <div class="cart-page__subtotal">
                        {{ formatPrice(getItemPrice(item) * item.quantity) }} ₴
                    </div>
                </div>
            </div>

            <aside class="cart-page__summary">
                <h2 class="cart-page__summary-title">Підсумок</h2>
                <div class="cart-page__summary-row">
                    <span>Товарів</span>
                    <span>{{ totalItems }} шт</span>
                </div>
                <div class="cart-page__summary-row cart-page__summary-total">
                    <span>Разом</span>
                    <span>{{ formatPrice(totalPrice) }} ₴</span>
                </div>

                <p v-if="isBelowMinimum" class="cart-page__notice cart-page__notice--warn">
                    Мінімальна сума — {{ formatPrice(minOrderTotal) }} {{ currencySymbol }}. Додайте ще на {{ formatPrice(amountToReachMinimum) }} {{ currencySymbol }}
                </p>
                <p v-else-if="minOrderTotal > 0" class="cart-page__notice cart-page__notice--ok">
                    Мінімальну суму досягнуто — можна оформлювати замовлення
                </p>

                <a :href="checkoutUrl"
                   class="cart-page__checkout"
                   :class="{ 'cart-page__checkout--disabled': isBelowMinimum }"
                   @click.prevent="goCheckout">
                    Оформити замовлення
                    <AppIcon name="arrow-right" :size="18" />
                </a>
                <a href="/catalog" class="cart-page__continue">Продовжити покупки</a>
            </aside>
        </div>

        <div v-else class="cart-page__empty">
            <AppIcon name="shopping-cart" :size="48" icon-class="text-gray-300" />
            <h2>Кошик порожній</h2>
            <p>Додайте товари з каталогу</p>
            <a href="/catalog" class="btn-domiko-primary inline-flex items-center gap-2 px-6 py-3">
                <AppIcon name="shopping-bag" :size="18" />
                Перейти в каталог
            </a>
        </div>
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
    name: 'CartPage',
    components: { AppIcon },
    data() {
        return {
            cart: [],
            checkoutUrl: '/checkout',
            qtyDrafts: {},
            minOrderTotal: getMinOrderTotal(),
            currencySymbol: getCurrencySymbol(),
        };
    },
    computed: {
        totalPrice() {
            return Number(this.cart.reduce((sum, item) => sum + this.getItemPrice(item) * item.quantity, 0).toFixed(2));
        },
        totalItems() {
            return this.cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
        },
        isBelowMinimum() {
            return this.minOrderTotal > 0 && this.totalPrice < this.minOrderTotal;
        },
        amountToReachMinimum() {
            const diff = this.minOrderTotal - this.totalPrice;
            return diff > 0 ? Math.ceil(diff) : 0;
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
            if (this.$toast) this.$toast.info('Видалено з кошика');
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
        itemUrl(item) {
            return item.url ? `/catalog/categoriya/catalog/${item.url}` : '/catalog';
        },
        goCheckout() {
            if (this.isBelowMinimum) {
                if (this.$toast) {
                    this.$toast.warning(`Мінімальна сума замовлення — ${this.formatPrice(this.minOrderTotal)} ${this.currencySymbol}`);
                }
                return;
            }
            window.location.href = this.checkoutUrl;
        },
    },
};
</script>

<style scoped>
.cart-page__layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

@media (min-width: 1024px) {
    .cart-page__layout { grid-template-columns: 1fr 320px; align-items: start; }
}

.cart-page__item {
    display: grid;
    grid-template-columns: 80px 1fr auto;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #ddd;
    background: #fff;
    margin-bottom: 0.75rem;
}

.cart-page__thumb {
    width: 80px; height: 80px;
    border: 1px solid #eee;
    display: flex; align-items: center; justify-content: center;
    background: #fafafa;
}

.cart-page__thumb img { max-width: 100%; max-height: 100%; object-fit: contain; }

.cart-page__name { font-size: 0.9375rem; font-weight: 600; margin: 0 0 0.25rem; }
.cart-page__name a { color: #1A2B36; text-decoration: none; }
.cart-page__name a:hover { color: #FF6B4A; }
.cart-page__articule { font-size: 0.75rem; color: #888; margin: 0 0 0.5rem; }
.cart-page__price { font-weight: 700; color: #1A2B36; font-family: Fredoka, sans-serif; }
.cart-page__opt { font-size: 0.6875rem; background: #FF6B4A; color: #1A2B36; padding: 0.125rem 0.375rem; margin-left: 0.5rem; }
.cart-page__actions { display: flex; align-items: center; gap: 0.75rem; margin-top: 0.75rem; }
.cart-page__qty {
    max-width: 168px;
    width: 168px;
    flex-shrink: 0;
}
.cart-page__remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    background: #fff;
    border: 1px solid #ddd;
    color: #666;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}
.cart-page__remove:hover { border-color: #1A2B36; color: #1A2B36; }
.cart-page__subtotal { font-weight: 700; font-family: Fredoka, sans-serif; white-space: nowrap; align-self: start; padding-top: 0.25rem; }

.cart-page__summary {
    border: 1px solid #ddd;
    background: #fff;
    padding: 1.25rem;
    position: sticky;
    top: 5rem;
}

.cart-page__summary-title { font-family: Fredoka, sans-serif; font-size: 1.125rem; font-weight: 700; margin: 0 0 1rem; color: #0E3A45; }
.cart-page__summary-row { display: flex; justify-content: space-between; font-size: 0.875rem; margin-bottom: 0.5rem; color: #666; }
.cart-page__summary-total { font-size: 1.125rem; font-weight: 700; color: #1A2B36; border-top: 1px solid #eee; padding-top: 0.75rem; margin-top: 0.75rem; margin-bottom: 1rem; }

.cart-page__notice { font-size: 0.8125rem; padding: 0.75rem; margin-bottom: 1rem; border: 1px solid #eee; }
.cart-page__notice--warn { background: #fff8f0; color: #666; border-color: #FF6B4A; }
.cart-page__notice--ok { background: #EEF8F7; color: #1A2B36; }

.cart-page__checkout {
    display: flex; align-items: center; justify-content: center; gap: 0.5rem;
    width: 100%; padding: 0.875rem;
    background: #FF6B4A; color: #1A2B36;
    font-weight: 700; font-family: Fredoka, sans-serif;
    text-decoration: none; border: none; cursor: pointer;
    transition: background 0.2s;
}

.cart-page__checkout:hover { background: #E85A3C; }
.cart-page__checkout--disabled { background: #eee; color: #999; pointer-events: none; }

.cart-page__continue {
    display: block; text-align: center; margin-top: 0.75rem;
    font-size: 0.875rem; color: #666; text-decoration: none;
}
.cart-page__continue:hover { color: #FF6B4A; }

.cart-page__empty {
    text-align: center; padding: 4rem 1rem;
    border: 1px solid #ddd; background: #fff;
}

.cart-page__empty h2 { font-family: Fredoka, sans-serif; font-size: 1.5rem; margin: 1rem 0 0.5rem; color: #0E3A45; }
.cart-page__empty p { color: #666; margin-bottom: 1.5rem; }
</style>
