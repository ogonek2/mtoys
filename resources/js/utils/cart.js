export const PRICE_ON_REQUEST_LABEL = 'Ціна уточнюється';

export function parsePriceNumber(price) {
    if (price === null || price === undefined || price === '') {
        return null;
    }

    const num = typeof price === 'string'
        ? parseFloat(price.replace(/[^\d.,]/g, '').replace(',', '.'))
        : Number(price);

    return Number.isFinite(num) ? num : null;
}

/** true, если цену можно показать числом (не 0 и не пустая). */
export function hasSellablePrice(price) {
    const num = parsePriceNumber(price);
    return num !== null && num > 0;
}

export function formatPrice(price) {
    const num = parsePriceNumber(price);
    return Math.round(num || 0).toLocaleString('uk-UA');
}

/**
 * Цена товара для витрины: «123 ₴» или «Ціна уточнюється».
 * @param {unknown} price
 * @param {{ currency?: string }} [options]
 */
export function formatProductPrice(price, options = {}) {
    if (!hasSellablePrice(price)) {
        return PRICE_ON_REQUEST_LABEL;
    }

    const currency = options.currency;
    const formatted = formatPrice(price);

    return currency ? `${formatted} ${currency}` : formatted;
}

export function finalPrice(product) {
    const price = parsePriceNumber(product?.price) ?? 0;
    const discount = parseFloat(product?.discount) || 0;
    if (discount > 0 && price > 0) {
        return price * (1 - discount / 100);
    }
    return price;
}

export function isInStock(product) {
    const a = product.availability;
    return a !== 2 && a !== '2' && a !== 'out_of_stock' && a !== false && a !== 0 && a !== '0';
}

export function parseQuantity(value, fallback = 1) {
    const n = parseInt(String(value ?? '').replace(/\D/g, ''), 10);
    return Number.isFinite(n) && n > 0 ? n : fallback;
}

/** Strip everything except digits (for qty fields). */
export function digitsOnly(value) {
    return String(value ?? '').replace(/\D/g, '');
}

/** Block non-digit keypresses; allow navigation / shortcuts. */
export function onQtyKeydown(event) {
    if (event.ctrlKey || event.metaKey || event.altKey) return;

    const allowed = [
        'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
        'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
        'Home', 'End',
    ];
    if (allowed.includes(event.key)) return;

    if (!/^\d$/.test(event.key)) {
        event.preventDefault();
    }
}

/** Paste only digits into a qty input. */
export function onQtyPaste(event) {
    event.preventDefault();
    const input = event.target;
    if (!input || typeof input.value !== 'string') return;

    const pasted = digitsOnly((event.clipboardData || window.clipboardData)?.getData('text'));
    const start = input.selectionStart ?? input.value.length;
    const end = input.selectionEnd ?? input.value.length;
    const next = digitsOnly(input.value.slice(0, start) + pasted + input.value.slice(end));

    input.value = next;
    const caret = Math.min(start + pasted.length, next.length);
    try {
        input.setSelectionRange(caret, caret);
    } catch {
        // ignore
    }
    input.dispatchEvent(new Event('input', { bubbles: true }));
}

/** Keep only digits while typing (safety net for IME / mobile). */
export function filterQtyInputEvent(event) {
    const input = event.target;
    if (!input) return '';
    const cleaned = digitsOnly(input.value);
    if (input.value !== cleaned) {
        const start = input.selectionStart ?? cleaned.length;
        input.value = cleaned;
        const caret = Math.min(start - 1, cleaned.length);
        try {
            input.setSelectionRange(Math.max(0, caret), Math.max(0, caret));
        } catch {
            // ignore
        }
    }
    return cleaned;
}

export function getCartItems() {
    try {
        const cart = JSON.parse(localStorage.getItem('cart') || '[]');
        return Array.isArray(cart) ? cart : [];
    } catch {
        return [];
    }
}

export function isProductInCart(productId) {
    return getCartItems().some((item) => item.id == productId);
}

export function getCartQuantity(productId) {
    const item = getCartItems().find((entry) => entry.id == productId);
    return item ? parseQuantity(item.quantity, 0) : 0;
}

export function hasWholesaleOffer(product) {
    if (!product) return false;
    const price = parseFloat(product.wholesale_price);
    const min = parseInt(product.wholesale_min_quantity, 10);
    const flagged = product.is_wholesale === true
        || product.is_wholesale === 1
        || product.is_wholesale === '1';
    return (flagged || price > 0) && price > 0 && Number.isFinite(min) && min > 0;
}

export function addProductToCart(product, quantity = 1) {
    const cart = getCartItems();
    const id = product.id;
    const price = finalPrice(product);
    const qty = parseQuantity(quantity, 1);
    const existing = cart.find((item) => item.id == id);

    if (existing) {
        existing.quantity = parseQuantity(existing.quantity, 0) + qty;
        existing.name = product.name || existing.name;
        existing.price = price;
        existing.image = product.image_path || product.image || existing.image || '';
        existing.articule = product.articule || existing.articule || 'Не вказано';
        if (hasWholesaleOffer(product)) {
            existing.isWholesale = true;
            existing.wholesalePrice = parseFloat(product.wholesale_price);
            existing.wholesaleMinQuantity = parseInt(product.wholesale_min_quantity, 10);
        }
    } else {
        const item = {
            id,
            name: product.name,
            price,
            image: product.image_path || product.image || '',
            articule: product.articule || 'Не вказано',
            quantity: qty,
        };

        if (hasWholesaleOffer(product)) {
            item.isWholesale = true;
            item.wholesalePrice = parseFloat(product.wholesale_price);
            item.wholesaleMinQuantity = parseInt(product.wholesale_min_quantity, 10);
        }

        cart.push(item);
    }

    localStorage.setItem('cart', JSON.stringify(cart));
    window.dispatchEvent(new Event('cart-updated'));

    if (window.$toast) {
        window.$toast.success(qty > 1 ? `Додано ${qty} шт. у кошик` : 'Додано в кошик');
    }

    return qty;
}

export function toggleWishlistItem(product) {
    const wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
    const id = product.id;
    const index = wishlist.findIndex((item) => (typeof item === 'object' ? item.id : item) == id);

    if (index > -1) {
        wishlist.splice(index, 1);
        if (window.$toast) window.$toast.info('Видалено з обраного');
    } else {
        wishlist.push({
            id,
            name: product.name,
            price: finalPrice(product),
            image: product.image_path || product.image || '',
            image_path: product.image_path || product.image || '',
            url: product.url || '',
            category_url: product.category_url || 'catalog',
            articule: product.articule || '',
            discount: product.discount || 0,
        });
        if (window.$toast) window.$toast.success('Додано в обране');
    }

    localStorage.setItem('wishlist', JSON.stringify(wishlist));
    window.dispatchEvent(new Event('wishlist-updated'));
}

export function isProductInWishlist(productId) {
    const wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
    return wishlist.some((item) => (typeof item === 'object' ? item.id : item) == productId);
}
