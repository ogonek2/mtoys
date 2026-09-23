<template>
    <Teleport to="body">
        <div
            v-if="visible"
            class="callback-modal is-visible"
            role="presentation"
            @keydown.esc.prevent="close">
            <div class="callback-modal__backdrop" @click="close"></div>
            <div
                ref="dialog"
                class="callback-modal__dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby="callback-title"
                tabindex="-1"
                @click.stop>
                <div class="callback-modal__header">
                    <div>
                        <h3 id="callback-title" class="callback-modal__title">Замовити дзвінок</h3>
                        <p class="callback-modal__subtitle">Залиште номер — передзвонимо в робочий час</p>
                    </div>
                    <button type="button" class="callback-modal__close" aria-label="Закрити" @click="close">
                        <AppIcon name="x" :size="22" />
                    </button>
                </div>
                <div class="callback-modal__body">
                    <div v-if="success" class="callback-modal__alert callback-modal__alert--success">{{ success }}</div>
                    <div v-if="error" class="callback-modal__alert callback-modal__alert--error">{{ error }}</div>
                    <form class="callback-modal__form" @submit.prevent="submit">
                        <label class="callback-modal__label">
                            Номер телефону *
                            <input
                                ref="phoneInput"
                                v-model="form.phone"
                                type="tel"
                                required
                                autocomplete="tel"
                                class="callback-modal__input"
                                placeholder="+380 (__ ) ___ __ __"
                                @input="onPhoneInput">
                        </label>
                        <label class="callback-modal__label">
                            Повідомлення <span class="callback-modal__optional">(необов'язково)</span>
                            <textarea v-model="form.message" rows="3" class="callback-modal__input" placeholder="Коротко опишіть питання"></textarea>
                        </label>
                        <button type="submit" class="callback-modal__submit" :disabled="loading">
                            {{ loading ? 'Відправлення...' : 'Відправити' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script>
import AppIcon from './AppIcon.vue';

export default {
    name: 'CallbackModal',
    components: { AppIcon },
    props: {
        actionUrl: { type: String, default: '/contact-request' },
    },
    data() {
        return {
            visible: false,
            loading: false,
            success: '',
            error: '',
            form: { phone: '', message: '' },
            openHandlers: [],
        };
    },
    mounted() {
        this.bindOpenTriggers();
        window.openCallbackModal = () => this.open();
        window.closeCallbackModal = () => this.close();
    },
    beforeUnmount() {
        this.unbindOpenTriggers();
        if (this.visible) {
            document.body.style.overflow = '';
        }
        if (window.openCallbackModal) delete window.openCallbackModal;
        if (window.closeCallbackModal) delete window.closeCallbackModal;
    },
    methods: {
        bindOpenTriggers() {
            this.unbindOpenTriggers();
            const handler = (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.open();
            };
            document.querySelectorAll('[data-callback-open]').forEach((el) => {
                el.addEventListener('click', handler);
                this.openHandlers.push({ el, handler });
            });
        },
        unbindOpenTriggers() {
            this.openHandlers.forEach(({ el, handler }) => {
                el.removeEventListener('click', handler);
            });
            this.openHandlers = [];
        },
        open() {
            this.success = '';
            this.error = '';
            this.form = { phone: '', message: '' };
            this.visible = true;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => {
                this.$refs.dialog?.focus?.();
                this.$refs.phoneInput?.focus?.();
            });
        },
        close() {
            this.visible = false;
            this.loading = false;
            document.body.style.overflow = '';
        },
        onPhoneInput(event) {
            const raw = String(event?.target?.value ?? this.form.phone);
            let digits = raw.replace(/\D/g, '');

            if (digits.startsWith('0')) {
                digits = `38${digits}`;
            }
            if (!digits.startsWith('380') && digits.length > 0) {
                digits = `380${digits.replace(/^380?/, '')}`;
            }

            digits = digits.slice(0, 12);
            if (!digits) {
                this.form.phone = '';
                return;
            }

            let out = '+380';
            const rest = digits.slice(3);
            if (rest.length > 0) out += ` (${rest.slice(0, 2)}`;
            if (rest.length >= 2) out += `) ${rest.slice(2, 5)}`;
            if (rest.length >= 5) out += ` ${rest.slice(5, 7)}`;
            if (rest.length >= 7) out += ` ${rest.slice(7, 9)}`;
            this.form.phone = out;
        },
        async refreshCsrfToken() {
            try {
                const res = await fetch('/csrf-token', {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                if (data.token) {
                    const meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta) meta.setAttribute('content', data.token);
                    return data.token;
                }
            } catch {
                // fall through
            }
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },
        async submit() {
            this.loading = true;
            this.success = '';
            this.error = '';
            const csrf = await this.refreshCsrfToken();

            try {
                const body = new FormData();
                body.append('phone', this.form.phone);
                body.append('message', this.form.message);
                if (csrf) body.append('_token', csrf);

                const response = await fetch(this.actionUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                    },
                    credentials: 'same-origin',
                    body,
                });

                if (response.status === 419) {
                    const retryToken = await this.refreshCsrfToken();
                    if (retryToken && retryToken !== csrf) {
                        body.set('_token', retryToken);
                        const retry = await fetch(this.actionUrl, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': retryToken,
                            },
                            credentials: 'same-origin',
                            body,
                        });
                        const retryData = await retry.json().catch(() => ({}));
                        if (retry.ok && retryData.success) {
                            this.success = retryData.message || 'Запит успішно відправлено.';
                            this.form = { phone: '', message: '' };
                            return;
                        }
                    }
                    this.error = 'Сесія застаріла. Оновіть сторінку і спробуйте ще раз.';
                    return;
                }

                const data = await response.json().catch(() => ({}));

                if (response.ok && data.success) {
                    this.success = data.message || 'Запит успішно відправлено.';
                    this.form = { phone: '', message: '' };
                } else {
                    let message = data.message || null;
                    if (!message && data.errors) {
                        message = Object.values(data.errors).flat().join('\n');
                    }
                    this.error = message || 'Сталася помилка. Спробуйте, будь ласка, пізніше.';
                }
            } catch {
                this.error = 'Сталася помилка. Спробуйте, будь ласка, пізніше.';
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
