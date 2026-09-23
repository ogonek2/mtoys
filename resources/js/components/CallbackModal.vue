<template>
    <Teleport to="body">
        <div v-if="visible" class="callback-modal is-visible" @keydown.esc="close">
            <div class="callback-modal__backdrop" @click="close"></div>
            <div class="callback-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="callback-title">
                <div class="callback-modal__header">
                    <div>
                        <h3 id="callback-title" class="callback-modal__title">Замовити консультацію</h3>
                        <p class="callback-modal__subtitle">Заповніть форму, і ми передзвонимо протягом робочого часу</p>
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
                                v-model="form.phone"
                                type="tel"
                                required
                                class="callback-modal__input"
                                placeholder="063 63 100 41"
                                @input="formatPhone">
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
        };
    },
    mounted() {
        document.querySelectorAll('[data-callback-open]').forEach((el) => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                this.open();
            });
        });
        window.openCallbackModal = () => this.open();
        window.closeCallbackModal = () => this.close();
    },
    methods: {
        open() {
            this.success = '';
            this.error = '';
            this.form = { phone: '', message: '' };
            this.visible = true;
            document.body.style.overflow = 'hidden';
        },
        close() {
            this.visible = false;
            document.body.style.overflow = '';
        },
        formatPhone() {
            const digits = this.form.phone.replace(/\D/g, '').slice(0, 12);
            if (!digits.startsWith('380') && digits.length) {
                this.form.phone = '+380';
                return;
            }
            let out = '+380';
            const rest = digits.slice(3);
            if (rest.length > 0) out += ` (${rest.slice(0, 2)}`;
            if (rest.length >= 2) out += `) ${rest.slice(2, 5)}`;
            if (rest.length >= 5) out += ` ${rest.slice(5, 7)}`;
            if (rest.length >= 7) out += ` ${rest.slice(7, 9)}`;
            this.form.phone = out.trim();
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
            } catch (e) {
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
