<template>
    <div v-if="visible" class="toast-notification" :class="typeClass">
        <div class="toast-content">
            <div class="toast-header">
                <div class="toast-icon">
                    <i :class="iconClass"></i>
                </div>
                <div class="toast-info">
                    <h6 class="toast-title">{{ title }}</h6>
                    <p class="toast-message">{{ message }}</p>
                </div>
                <button class="toast-close" @click="hide">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div v-if="product" class="toast-product">
                <img v-if="product.image" :src="product.image" :alt="product.name" class="product-image">
                <div v-else class="product-placeholder">
                    <i class="fas fa-image"></i>
                </div>
                <div class="product-details">
                    <h6 class="product-name">{{ product.name }}</h6>
                    <p class="product-price">{{ formatProductPrice(product.price) }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { formatProductPrice as displayProductPrice } from '../utils/cart.js';

export default {
    name: "ToastNotification",
    data() {
        return {
            visible: false,
            title: '',
            message: '',
            type: 'success',
            product: null,
            timeout: null
        };
    },
    computed: {
        typeClass() {
            return `toast-${this.type}`;
        },
        iconClass() {
            const icons = {
                success: 'fas fa-check-circle',
                error: 'fas fa-exclamation-circle',
                warning: 'fas fa-exclamation-triangle',
                info: 'fas fa-info-circle'
            };
            return icons[this.type] || icons.info;
        }
    },
    mounted() {
        // Слушаем глобальные события для показа уведомлений
        window.addEventListener('show-toast', this.showToast);
    },
    unmounted() {
        window.removeEventListener('show-toast', this.showToast);
        if (this.timeout) {
            clearTimeout(this.timeout);
        }
    },
    methods: {
        showToast(event) {
            console.log('showToast вызван с событием:', event);
            console.log('Детали события:', event.detail);
            
            const { title, message, type = 'success', product = null, duration = 4000 } = event.detail;
            
            console.log('Параметры тостера:', { title, message, type, product, duration });
            
            this.title = title;
            this.message = message;
            this.type = type;
            this.product = product;
            
            this.visible = true;
            console.log('Тостер сделан видимым, visible =', this.visible);
            
            // Автоматически скрываем через указанное время
            if (this.timeout) {
                clearTimeout(this.timeout);
            }
            
            this.timeout = setTimeout(() => {
                this.hide();
            }, duration);
        },
        hide() {
            this.visible = false;
            this.product = null;
        },
        formatProductPrice(price) {
            return displayProductPrice(price, { currency: '₴' });
        }
    }
};
</script>

<style scoped>
.toast-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    min-width: 350px;
    max-width: 400px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    border-left: 4px solid;
    animation: slideIn 0.3s ease-out;
    overflow: hidden;
}

.toast-success {
    border-left-color: #10b981;
}

.toast-error {
    border-left-color: #ef4444;
}

.toast-warning {
    border-left-color: #f59e0b;
}

.toast-info {
    border-left-color: #3b82f6;
}

.toast-content {
    padding: 16px;
}

.toast-header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 12px;
}

.toast-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.toast-success .toast-icon {
    color: #10b981;
}

.toast-error .toast-icon {
    color: #ef4444;
}

.toast-warning .toast-icon {
    color: #f59e0b;
}

.toast-info .toast-icon {
    color: #3b82f6;
}

.toast-info {
    flex: 1;
    min-width: 0;
}

.toast-title {
    margin: 0 0 4px 0;
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
}

.toast-message {
    margin: 0;
    font-size: 13px;
    color: #6b7280;
    line-height: 1.4;
}

.toast-close {
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.toast-close:hover {
    background: #f3f4f6;
    color: #6b7280;
}

.toast-product {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.product-image {
    width: 48px;
    height: 48px;
    object-fit: contain;
    border-radius: 6px;
    background: white;
}

.product-placeholder {
    width: 48px;
    height: 48px;
    background: #e5e7eb;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
}

.product-details {
    flex: 1;
    min-width: 0;
}

.product-name {
    margin: 0 0 4px 0;
    font-size: 13px;
    font-weight: 600;
    color: #1f2937;
    line-height: 1.3;
}

.product-price {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
    color: #10b981;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .toast-notification {
        left: 20px;
        right: 20px;
        min-width: auto;
        max-width: none;
    }
}
</style>
