export const DEFAULT_SHOP_SETTINGS = Object.freeze({
    min_order_total: 1000,
    min_order_enabled: true,
    free_delivery_from: 10000,
    free_delivery_enabled: true,
    announcement_text: 'Безкоштовна доставка від 10 000₴',
    store_name: 'Mtoys',
    currency_symbol: '₴',
    currency_label: 'грн',
    contact_phone: '063 63 100 41',
    contact_email: 'office@mtoys.com.ua',
    contact_address: 'вул. Фабрична, номер контейнер 2177, 7 км, Одеса',
    checkout_notice: '',
});

export function getShopSettings() {
    const fromWindow = typeof window !== 'undefined' ? window.__SHOP_SETTINGS__ : null;
    if (fromWindow && typeof fromWindow === 'object') {
        return { ...DEFAULT_SHOP_SETTINGS, ...fromWindow };
    }
    return { ...DEFAULT_SHOP_SETTINGS };
}

/** Effective minimum order total (0 when disabled). */
export function getMinOrderTotal() {
    const settings = getShopSettings();
    if (settings.min_order_enabled === false) return 0;
    const value = Number(settings.min_order_total);
    return Number.isFinite(value) && value > 0 ? value : 1000;
}

export function formatMoneyAmount(amount) {
    return Math.round(Number(amount) || 0).toLocaleString('uk-UA');
}

export function getCurrencySymbol() {
    return getShopSettings().currency_symbol || '₴';
}

export function getCurrencyLabel() {
    return getShopSettings().currency_label || 'грн';
}
