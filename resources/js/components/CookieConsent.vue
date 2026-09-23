<template>
    <Teleport to="body">
        <transition name="cookie-banner">
            <div v-if="visible" class="cookie-banner" role="dialog" aria-live="polite" aria-label="Файли cookie">
                <div class="cookie-banner__inner">
                    <div class="cookie-banner__text">
                        <strong class="cookie-banner__title">Ми використовуємо файли cookie</strong>
                        <p>
                            Це допомагає зручніше користуватися сайтом і покращувати його роботу.
                            Детальніше — у
                            <a href="/privacy-policy" class="cookie-banner__link">політиці конфіденційності</a>.
                        </p>
                    </div>
                    <div class="cookie-banner__actions">
                        <button type="button" class="cookie-banner__btn cookie-banner__btn--ghost" @click="decline">
                            Лише необхідні
                        </button>
                        <button type="button" class="cookie-banner__btn cookie-banner__btn--primary" @click="accept">
                            Прийняти
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<script>
const STORAGE_KEY = 'mtoys_cookie_consent';

export default {
    name: 'CookieConsent',
    data() {
        return { visible: false };
    },
    mounted() {
        try {
            if (!localStorage.getItem(STORAGE_KEY)) {
                this.visible = true;
            }
        } catch {
            this.visible = true;
        }
    },
    methods: {
        accept() {
            this.save('accepted');
        },
        decline() {
            this.save('necessary');
        },
        save(value) {
            try {
                localStorage.setItem(STORAGE_KEY, value);
            } catch {
                // ignore
            }
            this.visible = false;
        },
    },
};
</script>
