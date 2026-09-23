<template>
    <div class="call-float" :class="{ 'is-open': open }">
        <div v-show="open" class="call-float__panel" role="dialog" aria-label="Зв'язатися з нами" @click.stop>
            <p class="call-float__eyebrow">Ми на звʼязку</p>
            <a :href="`tel:${phoneTel}`" class="call-float__phone" @click.stop>
                <AppIcon name="phone" :size="18" />
                <span>{{ phoneLabel }}</span>
            </a>
            <button type="button" class="call-float__callback" @click.stop="openCallback">
                <AppIcon name="headphones" :size="16" />
                Замовити дзвінок
            </button>
            <a href="/kontaktna-informatsiia" class="call-float__link" @click.stop>Контакти →</a>
        </div>

        <button
            type="button"
            class="call-float__btn"
            :aria-expanded="open ? 'true' : 'false'"
            :aria-label="open ? 'Закрити' : 'Зателефонувати'"
            @click.stop="toggle">
            <span class="call-float__pulse" aria-hidden="true"></span>
            <span class="call-float__pulse call-float__pulse--delay" aria-hidden="true"></span>
            <AppIcon :name="open ? 'x' : 'phone'" :size="22" class="call-float__icon" />
        </button>
    </div>
</template>

<script>
import AppIcon from './AppIcon.vue';

export default {
    name: 'FloatingCallButton',
    components: { AppIcon },
    props: {
        phone: { type: String, default: '063 63 100 41' },
    },
    data() {
        return {
            open: false,
            outsideBound: false,
        };
    },
    computed: {
        phoneLabel() {
            return this.phone;
        },
        phoneTel() {
            return this.phone.replace(/\D/g, '');
        },
    },
    beforeUnmount() {
        this.unbindOutside();
    },
    methods: {
        toggle() {
            if (this.open) {
                this.close();
            } else {
                this.openPanel();
            }
        },
        openPanel() {
            this.open = true;
            // Підписуємось після поточного кліку, інакше той самий click одразу закриє панель.
            this.$nextTick(() => {
                setTimeout(() => this.bindOutside(), 0);
            });
        },
        close() {
            this.open = false;
            this.unbindOutside();
        },
        openCallback() {
            this.close();
            if (typeof window.openCallbackModal === 'function') {
                window.openCallbackModal();
            }
        },
        bindOutside() {
            if (this.outsideBound || !this.open) return;
            document.addEventListener('pointerdown', this.onOutside, true);
            document.addEventListener('keydown', this.onKeydown, true);
            this.outsideBound = true;
        },
        unbindOutside() {
            if (!this.outsideBound) return;
            document.removeEventListener('pointerdown', this.onOutside, true);
            document.removeEventListener('keydown', this.onKeydown, true);
            this.outsideBound = false;
        },
        onOutside(e) {
            if (!this.open) return;
            const root = this.$el;
            if (root && root.contains(e.target)) return;
            this.close();
        },
        onKeydown(e) {
            if (e.key === 'Escape') this.close();
        },
    },
};
</script>
