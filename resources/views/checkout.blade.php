@extends('layouts.app')

@section('seo')
    <title>Оформлення замовлення — Mtoys</title>
    <meta name="description" content="Оформлення замовлення в інтернет-магазині Mtoys. Доставка Новою Поштою по всій Україні.">
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .checkout-page {
            background:
                radial-gradient(circle at 0% 0%, rgba(255, 107, 74, 0.07), transparent 28%),
                radial-gradient(circle at 100% 8%, rgba(46, 196, 182, 0.08), transparent 26%),
                #F2F7FA;
            min-height: 60vh;
        }

        .checkout-crumbs {
            background: #fff;
            border-bottom: 1px solid #e8eef2;
            padding: 0.85rem 0;
        }

        .checkout-hero {
            max-width: var(--max-width-site);
            margin: 0 auto;
            padding: 1.5rem 1rem 0.25rem;
        }

        @media (min-width: 640px) {
            .checkout-hero { padding-left: 1.5rem; padding-right: 1.5rem; }
        }

        @media (min-width: 1024px) {
            .checkout-hero { padding-left: 2rem; padding-right: 2rem; padding-top: 1.75rem; }
        }

        .checkout-hero__card {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.35rem 1.4rem;
            border-radius: 1.25rem;
            background:
                linear-gradient(120deg, #0E3A45 0%, #164E5C 55%, #0E3A45 100%),
                radial-gradient(circle at 90% 20%, rgba(255, 107, 74, 0.35), transparent 40%);
            color: #fff;
            box-shadow: 0 16px 36px rgba(14, 58, 69, 0.18);
        }

        .checkout-hero__eyebrow {
            margin: 0 0 0.35rem;
            font-family: var(--font-heading);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #FFB703;
        }

        .checkout-hero__title {
            margin: 0 0 0.4rem;
            font-family: var(--font-heading);
            font-size: clamp(1.5rem, 3vw, 2rem);
            font-weight: 700;
            line-height: 1.15;
        }

        .checkout-hero__text {
            margin: 0;
            max-width: 32rem;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.78);
        }

        .checkout-steps {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
        }

        .checkout-steps span {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.75rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            font-size: 0.75rem;
            font-weight: 700;
        }

        .checkout-steps strong {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.2rem;
            height: 1.2rem;
            border-radius: 999px;
            background: #FF6B4A;
            font-size: 0.6875rem;
        }

        .checkout-layout {
            max-width: var(--max-width-site);
            margin: 0 auto;
            padding: 1.25rem 1rem 2.5rem;
            display: grid;
            gap: 1.25rem;
        }

        @media (min-width: 640px) {
            .checkout-layout { padding-left: 1.5rem; padding-right: 1.5rem; }
        }

        @media (min-width: 1024px) {
            .checkout-layout {
                grid-template-columns: minmax(0, 1fr) 340px;
                gap: 1.5rem;
                padding: 1.5rem 2rem 3rem;
                align-items: start;
            }
        }

        .checkout-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .checkout-block {
            background: #fff;
            border: 1px solid #e8eef2;
            border-radius: 1.15rem;
            padding: 1.25rem 1.15rem;
            box-shadow: 0 8px 24px rgba(14, 58, 69, 0.05);
        }

        @media (min-width: 640px) {
            .checkout-block { padding: 1.4rem 1.5rem; }
        }

        .checkout-block__head {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.1rem;
        }

        .checkout-block__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 999px;
            background: rgba(255, 107, 74, 0.12);
            color: #FF6B4A;
            flex-shrink: 0;
        }

        .checkout-block__title {
            margin: 0;
            font-family: var(--font-heading);
            font-size: 1.125rem;
            font-weight: 700;
            color: #0E3A45;
        }

        .checkout-block__hint {
            margin: 0.15rem 0 0;
            font-size: 0.8125rem;
            color: #5B6B76;
        }

        .checkout-option {
            display: block;
            position: relative;
            border: 1.5px solid #e5e7eb;
            border-radius: 1rem;
            background: #fff;
            padding: 1rem 1.05rem;
            cursor: pointer;
            transition: border-color .18s, background .18s, box-shadow .18s, transform .18s;
        }

        .checkout-option:hover {
            border-color: #cbd5e1;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(14, 58, 69, 0.06);
        }

        .checkout-option.active {
            border-color: transparent;
            background: rgba(255, 107, 74, 0.08);
            box-shadow: inset 0 0 0 1.5px #FF6B4A, 0 8px 20px rgba(255, 107, 74, 0.1);
        }

        .checkout-option__badge {
            position: absolute;
            top: 0.7rem;
            right: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 999px;
            background: #FF6B4A;
            color: #fff;
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .checkout-option__row {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
        }

        .checkout-option__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.6rem;
            height: 2.6rem;
            border-radius: 0.85rem;
            background: #0E3A45;
            color: #FF6B4A;
            flex-shrink: 0;
        }

        .checkout-option.active .checkout-option__icon {
            background: #FF6B4A;
            color: #fff;
        }

        .checkout-option__title {
            display: block;
            margin-bottom: 0.2rem;
            font-family: var(--font-heading);
            font-weight: 700;
            color: #0E3A45;
        }

        .checkout-option__desc {
            display: block;
            font-size: 0.8125rem;
            color: #5B6B76;
            line-height: 1.35;
        }

        .checkout-field {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.85rem;
            background: #fff;
            font-size: 0.875rem;
            color: #1A2B36;
            transition: border-color .15s, box-shadow .15s;
        }

        .checkout-field:focus {
            border-color: #FF6B4A;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 107, 74, 0.16);
        }

        .checkout-field.is-invalid { border-color: #dc2626; }

        .checkout-label {
            display: block;
            margin-bottom: 0.45rem;
            font-size: 0.8125rem;
            font-weight: 700;
            color: #0E3A45;
        }

        .invalid-feedback {
            margin-top: 0.35rem;
            font-size: 0.75rem;
            color: #dc2626;
        }

        .checkout-note {
            border-radius: 0.9rem;
            border: 1px solid rgba(46, 196, 182, 0.28);
            background: rgba(46, 196, 182, 0.1);
            padding: 0.9rem 1rem;
            font-size: 0.875rem;
            color: #0E3A45;
        }

        .checkout-submit {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 3.25rem;
            padding: 0.9rem 1.5rem;
            border: none;
            border-radius: 999px;
            background: #FF6B4A;
            color: #fff;
            font-family: var(--font-heading);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s, transform .2s, box-shadow .2s;
            box-shadow: 0 10px 24px rgba(255, 107, 74, 0.28);
        }

        .checkout-submit:hover:not(:disabled) {
            background: #E85A3C;
            transform: translateY(-1px);
        }

        .checkout-submit:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        .checkout-aside {
            background: #fff;
            border: 1px solid #e8eef2;
            border-radius: 1.15rem;
            padding: 1.25rem 1.15rem;
            box-shadow: 0 8px 24px rgba(14, 58, 69, 0.05);
        }

        @media (min-width: 1024px) {
            .checkout-aside {
                position: sticky;
                top: calc(var(--header-height, 146px) + 1rem);
                padding: 1.4rem 1.35rem;
            }
        }

        .checkout-aside__title {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            margin: 0 0 1rem;
            font-family: var(--font-heading);
            font-size: 1.05rem;
            font-weight: 700;
            color: #0E3A45;
        }

        .checkout-aside__title span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.15rem;
            height: 2.15rem;
            border-radius: 999px;
            background: #0E3A45;
            color: #FF6B4A;
        }

        .select2-container--default .select2-selection--single {
            height: 48px;
            border: 1px solid #e5e7eb;
            border-radius: 0.85rem;
            padding: 8px 12px;
            background: #fff;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 30px;
            color: #1A2B36;
            padding-left: 0;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 46px; }

        .select2-dropdown {
            border-color: #e5e7eb;
            border-radius: 0.85rem;
            overflow: hidden;
            z-index: 10050;
        }

        .select2-container { width: 100% !important; }
        .select2-container--open { z-index: 10050; }

        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background: #0E3A45;
        }

        #novaposhta-details .select2-container--default .select2-selection--single {
            cursor: pointer;
            background: #fff;
        }

        .checkout-preloader {
            backdrop-filter: blur(4px);
        }
    </style>
@endpush

@section('content')
    <div id="preloader" class="checkout-preloader fixed inset-0 z-50 hidden items-center justify-center bg-white/90">
        <div class="flex flex-col items-center">
            <div class="h-11 w-11 animate-spin rounded-full border-2 border-gray-200 border-t-[#FF6B4A]"></div>
            <p class="mt-4 font-heading text-sm font-semibold text-[#0E3A45]">Оформлюємо замовлення...</p>
        </div>
    </div>

    <div class="checkout-page">
        <nav class="checkout-crumbs" aria-label="Breadcrumb">
            <div class="mx-auto max-w-site px-4 sm:px-6 lg:px-8">
                <ol class="flex flex-wrap items-center gap-1.5 text-[0.8125rem] text-slate-500">
                    <li>
                        <a href="{{ url('/') }}" class="transition-colors hover:text-[#0E3A45]" aria-label="Головна">
                            <i class="fas fa-home"></i>
                        </a>
                    </li>
                    <li><i class="fas fa-chevron-right text-xs text-gray-300"></i></li>
                    <li>
                        <a href="{{ route('cart') }}" class="transition-colors hover:text-[#0E3A45]">Кошик</a>
                    </li>
                    <li><i class="fas fa-chevron-right text-xs text-gray-300"></i></li>
                    <li><span class="font-semibold text-[#1A2B36]">Оформлення</span></li>
                </ol>
            </div>
        </nav>

        <header class="checkout-hero">
            <div class="checkout-hero__card">
                <div>
                    <p class="checkout-hero__eyebrow">Mtoys</p>
                    <h1 class="checkout-hero__title">Оформлення замовлення</h1>
                    <p class="checkout-hero__text">Заповніть дані доставки — відправимо замовлення в той самий день до 14:00</p>
                </div>
                <div class="checkout-steps" aria-hidden="true">
                    <span><strong>1</strong> Доставка</span>
                    <span><strong>2</strong> Контакти</span>
                    <span><strong>3</strong> Оплата</span>
                </div>
            </div>
        </header>

        <div class="checkout-layout">
            <form id="order-form" class="checkout-form">
                <section class="checkout-block">
                    <div class="checkout-block__head">
                        <span class="checkout-block__icon"><i class="fas fa-truck"></i></span>
                        <div>
                            <h2 class="checkout-block__title">Спосіб доставки</h2>
                            <p class="checkout-block__hint">Оберіть зручний варіант отримання</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <label class="checkout-option delivery-option">
                            <input type="radio" name="delivery_service" value="novaposhta" class="sr-only">
                            <span class="checkout-option__badge">Популярно</span>
                            <span class="checkout-option__row pr-14">
                                <span class="checkout-option__icon"><i class="fas fa-truck"></i></span>
                                <span>
                                    <span class="checkout-option__title">Нова Пошта</span>
                                    <span class="checkout-option__desc">Відділення або поштомат, 1–3 дні</span>
                                </span>
                            </span>
                        </label>

                        <label class="checkout-option delivery-option">
                            <input type="radio" name="delivery_service" value="courier" class="sr-only">
                            <span class="checkout-option__row">
                                <span class="checkout-option__icon"><i class="fas fa-motorcycle"></i></span>
                                <span>
                                    <span class="checkout-option__title">Кур'єр</span>
                                    <span class="checkout-option__desc">Доставка додому або в офіс</span>
                                </span>
                            </span>
                        </label>

                        <label class="checkout-option delivery-option md:col-span-2">
                            <input type="radio" name="delivery_service" value="pickup" class="sr-only">
                            <span class="checkout-option__row">
                                <span class="checkout-option__icon"><i class="fas fa-store"></i></span>
                                <span>
                                    <span class="checkout-option__title">Самовивіз</span>
                                    <span class="checkout-option__desc">Забрати замовлення самостійно</span>
                                </span>
                            </span>
                        </label>
                    </div>
                </section>

                <section id="delivery-details" class="checkout-block hidden">
                    <div class="checkout-block__head">
                        <span class="checkout-block__icon"><i class="fas fa-map-marker-alt"></i></span>
                        <div>
                            <h2 class="checkout-block__title">Адреса доставки</h2>
                            <p class="checkout-block__hint">Куди відправити замовлення</p>
                        </div>
                    </div>

                    <div id="novaposhta-details" class="hidden grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="checkout-label">Місто <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <select id="city-select" class="checkout-field">
                                    <option value="">Оберіть місто</option>
                                </select>
                                <div id="city-loader" class="absolute top-1/2 right-3 hidden -translate-y-1/2">
                                    <div class="h-5 w-5 animate-spin rounded-full border-2 border-gray-200 border-t-[#FF6B4A]"></div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="checkout-label">Відділення <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <select id="warehouse-select" class="checkout-field">
                                    <option value="">Оберіть відділення</option>
                                </select>
                                <div id="warehouse-loader" class="absolute top-1/2 right-3 hidden -translate-y-1/2">
                                    <div class="h-5 w-5 animate-spin rounded-full border-2 border-gray-200 border-t-[#FF6B4A]"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="manual-address-details" class="hidden">
                        <label for="manual-address" class="checkout-label">Адреса доставки <span class="text-red-500">*</span></label>
                        <textarea id="manual-address" class="checkout-field" rows="3" placeholder="Місто, вулиця, будинок, квартира"></textarea>
                    </div>
                </section>

                <section class="checkout-block">
                    <div class="checkout-block__head">
                        <span class="checkout-block__icon"><i class="fas fa-user"></i></span>
                        <div>
                            <h2 class="checkout-block__title">Дані отримувача</h2>
                            <p class="checkout-block__hint">Для звʼязку та доставки</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label for="name" class="checkout-label">Ім'я <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name" class="checkout-field" placeholder="Ваше ім'я" autocomplete="given-name">
                        </div>
                        <div>
                            <label for="lastname" class="checkout-label">Прізвище <span class="text-red-500">*</span></label>
                            <input type="text" id="lastname" name="lastname" class="checkout-field" placeholder="Ваше прізвище" autocomplete="family-name">
                        </div>
                        <div>
                            <label for="fathername" class="checkout-label">По батькові</label>
                            <input type="text" id="fathername" name="fathername" class="checkout-field" placeholder="Необов'язково" autocomplete="additional-name">
                        </div>
                        <div>
                            <label for="phone" class="checkout-label">Телефон <span class="text-red-500">*</span></label>
                            <input type="tel" id="phone" name="phone" class="checkout-field" placeholder="+380XXXXXXXXX" required autocomplete="tel">
                        </div>
                        <div class="md:col-span-2">
                            <label for="email" class="checkout-label">Email <span class="text-red-500">*</span></label>
                            <input type="email" id="email" name="email" class="checkout-field" placeholder="name@example.com" required autocomplete="email">
                        </div>
                        <div class="md:col-span-2">
                            <label for="comment" class="checkout-label">Коментар до замовлення</label>
                            <textarea id="comment" name="comment" class="checkout-field" rows="3" placeholder="Додаткові побажання"></textarea>
                        </div>
                    </div>
                </section>

                <section class="checkout-block">
                    <div class="checkout-block__head">
                        <span class="checkout-block__icon"><i class="fas fa-credit-card"></i></span>
                        <div>
                            <h2 class="checkout-block__title">Спосіб оплати</h2>
                            <p class="checkout-block__hint">Оплата після підтвердження замовлення</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <label class="checkout-option payment-option">
                            <input type="radio" name="payment_method" value="cash" class="sr-only">
                            <span class="checkout-option__row">
                                <span class="checkout-option__icon"><i class="fas fa-money-bill-wave"></i></span>
                                <span>
                                    <span class="checkout-option__title">Накладений платіж</span>
                                    <span class="checkout-option__desc">Готівка при отриманні</span>
                                </span>
                            </span>
                        </label>
                        <label class="checkout-option payment-option">
                            <input type="radio" name="payment_method" value="bank_transfer" class="sr-only">
                            <span class="checkout-option__row">
                                <span class="checkout-option__icon"><i class="fas fa-university"></i></span>
                                <span>
                                    <span class="checkout-option__title">Банківський переказ</span>
                                    <span class="checkout-option__desc">Оплата за рахунком</span>
                                </span>
                            </span>
                        </label>
                        <label class="checkout-option payment-option">
                            <input type="radio" name="payment_method" value="card_payment" class="sr-only">
                            <span class="checkout-option__row">
                                <span class="checkout-option__icon"><i class="fas fa-credit-card"></i></span>
                                <span>
                                    <span class="checkout-option__title">Картка при отриманні</span>
                                    <span class="checkout-option__desc">Термінал у кур'єра</span>
                                </span>
                            </span>
                        </label>
                        <label class="checkout-option payment-option">
                            <input type="radio" name="payment_method" value="pickup_payment" class="sr-only">
                            <span class="checkout-option__row">
                                <span class="checkout-option__icon"><i class="fas fa-store"></i></span>
                                <span>
                                    <span class="checkout-option__title">Оплата при самовивозі</span>
                                    <span class="checkout-option__desc">Готівка або картка в магазині</span>
                                </span>
                            </span>
                        </label>
                    </div>

                    <div id="payment-info" class="mt-4 hidden">
                        <div id="payment-info-cash" class="checkout-note hidden">
                            <strong>Накладений платіж:</strong> оплата готівкою при отриманні. Кур'єр матиме чек і квитанцію.
                        </div>
                        <div id="payment-info-bank_transfer" class="checkout-note hidden">
                            <strong>Банківський переказ:</strong> після підтвердження надішлемо реквізити на пошту або в месенджер.
                        </div>
                        <div id="payment-info-card_payment" class="checkout-note hidden">
                            <strong>Оплата карткою при отриманні:</strong> у кур'єра буде термінал. Підтримуються основні платіжні системи.
                        </div>
                        <div id="payment-info-pickup_payment" class="checkout-note hidden">
                            <strong>Оплата при самовивозі:</strong> готівкою або карткою в магазині.
                        </div>
                    </div>
                </section>

                <section class="checkout-block">
                    <button type="submit" id="submit-order" class="checkout-submit">
                        <i class="fas fa-check"></i>
                        Оформити замовлення
                    </button>
                    <p id="checkout-minimum-message" class="mt-3 hidden text-center text-sm"></p>
                    @if (!empty(shop_settings_public()['checkout_notice']))
                        <p class="mt-2 text-center text-sm text-slate-500">{{ shop_settings_public()['checkout_notice'] }}</p>
                    @endif
                </section>

                <meta name="csrf-token" content="{{ csrf_token() }}">
            </form>

            <aside class="checkout-aside">
                <h3 class="checkout-aside__title">
                    <span><i class="fas fa-shopping-cart text-sm"></i></span>
                    Ваше замовлення
                </h3>
                <cart-list></cart-list>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        const MIN_ORDER_TOTAL = (window.__SHOP_SETTINGS__ && window.__SHOP_SETTINGS__.min_order_enabled === false)
            ? 0
            : (Number(window.__SHOP_SETTINGS__?.min_order_total) || 1000);
        const CURRENCY_SYMBOL = window.__SHOP_SETTINGS__?.currency_symbol || '₴';

        $(document).ready(function() {
            let selectedCityRef = '';
            const submitButton = document.getElementById('submit-order');
            const minOrderMessage = document.getElementById('checkout-minimum-message');

            const updateSubmitState = () => {
                if (!submitButton) return;

                const totalInput = document.getElementById('total_price_stream');
                const rawValue = totalInput ? parseFloat(totalInput.value) : 0;
                const total = isNaN(rawValue) ? 0 : rawValue;

                if (minOrderMessage) {
                    minOrderMessage.classList.remove('text-red-600', 'text-[#0E3A45]');
                }

                if (MIN_ORDER_TOTAL > 0 && total < MIN_ORDER_TOTAL) {
                    submitButton.disabled = true;
                    submitButton.classList.add('cursor-not-allowed', 'opacity-60', 'pointer-events-none');
                    if (minOrderMessage) {
                        const difference = Math.ceil(MIN_ORDER_TOTAL - total);
                        minOrderMessage.textContent = `Мінімальна сума замовлення — ${MIN_ORDER_TOTAL.toLocaleString('uk-UA')} ${CURRENCY_SYMBOL}. Додайте товарів ще на ${difference.toLocaleString('uk-UA')} ${CURRENCY_SYMBOL}.`;
                        minOrderMessage.classList.add('text-red-600');
                        minOrderMessage.classList.remove('hidden');
                    }
                } else {
                    submitButton.disabled = false;
                    submitButton.classList.remove('cursor-not-allowed', 'opacity-60', 'pointer-events-none');
                    if (minOrderMessage) {
                        if (MIN_ORDER_TOTAL > 0) {
                            minOrderMessage.textContent = 'Мінімальна сума замовлення виконана. Можна оформлювати.';
                            minOrderMessage.classList.add('text-[#0E3A45]');
                            minOrderMessage.classList.remove('hidden');
                        } else {
                            minOrderMessage.classList.add('hidden');
                        }
                    }
                }
            };

            updateSubmitState();
            setTimeout(updateSubmitState, 400);
            window.addEventListener('cart-updated', updateSubmitState);

            $('input[name="delivery_service"]').on('change', function() {
                $('.delivery-option').removeClass('active');
                $(this).closest('.delivery-option').addClass('active');
                showDeliveryDetails($(this).val());
            });

            $('.delivery-option').on('click', function() {
                const radio = $(this).find('input[type="radio"]');
                radio.prop('checked', true).trigger('change');
            });

            $('input[name="payment_method"]').on('change', function() {
                $('.payment-option').removeClass('active');
                $(this).closest('.payment-option').addClass('active');
                showPaymentInfo($(this).val());
            });

            $('.payment-option').on('click', function() {
                const radio = $(this).find('input[type="radio"]');
                radio.prop('checked', true).trigger('change');
            });

            $('#city-select').select2({
                placeholder: 'Почніть вводити місто…',
                allowClear: true,
                width: '100%',
                minimumInputLength: 2,
                dropdownParent: $('#novaposhta-details'),
                language: {
                    inputTooShort: () => 'Введіть щонайменше 2 літери',
                    noResults: () => 'Міста не знайдено',
                    searching: () => 'Пошук…',
                    errorLoading: () => 'Помилка завантаження',
                },
                ajax: {
                    url: '/cities',
                    dataType: 'json',
                    delay: 400,
                    data: (params) => ({ q: params.term || '' }),
                    processResults: (data) => ({
                        results: (Array.isArray(data) ? data : []).map((city) => ({
                            id: city.Ref,
                            text: city.Description,
                        })),
                    }),
                    cache: true,
                },
            });

            const $warehouse = $('#warehouse-select');
            $warehouse.select2({
                placeholder: 'Спочатку оберіть місто',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#novaposhta-details'),
                language: {
                    noResults: () => 'Відділення не знайдено',
                    searching: () => 'Пошук…',
                },
            });
            $warehouse.prop('disabled', true);

            let warehousesXhr = null;

            function resetWarehouses(placeholder) {
                if (warehousesXhr) {
                    warehousesXhr.abort();
                    warehousesXhr = null;
                }
                $warehouse.empty().append(`<option value="">${placeholder}</option>`);
                $warehouse.val(null).trigger('change');
            }

            function loadWarehousesOnce(cityRef) {
                resetWarehouses('Завантаження…');
                $warehouse.prop('disabled', true);
                $('#warehouse-loader').removeClass('hidden');

                warehousesXhr = $.ajax({
                    method: 'POST',
                    url: '/warehouses',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    data: {
                        cityRef: cityRef,
                        _token: $('meta[name="csrf-token"]').attr('content'),
                    },
                })
                    .done(function(data) {
                        const list = Array.isArray(data) ? data : [];
                        resetWarehouses(list.length ? 'Оберіть відділення' : 'Відділень не знайдено');
                        list.forEach((wh) => {
                            $warehouse.append(
                                $('<option>', { value: wh.Ref, text: wh.Description })
                            );
                        });
                        $warehouse.prop('disabled', list.length === 0);
                        $warehouse.trigger('change.select2');
                    })
                    .fail(function(xhr) {
                        if (xhr.statusText === 'abort') return;
                        resetWarehouses('Помилка завантаження відділень');
                        $warehouse.prop('disabled', true);
                    })
                    .always(function() {
                        warehousesXhr = null;
                        $('#warehouse-loader').addClass('hidden');
                    });
            }

            function showDeliveryDetails(service) {
                $('#delivery-details').removeClass('hidden');
                $('#novaposhta-details').addClass('hidden');
                $('#manual-address-details').addClass('hidden');

                if (service === 'novaposhta') {
                    $('#novaposhta-details').removeClass('hidden');
                    $('#city-select, #warehouse-select').trigger('change.select2');
                } else if (service === 'pickup') {
                    $('#delivery-details').addClass('hidden');
                } else {
                    $('#manual-address-details').removeClass('hidden');
                }
            }

            function showPaymentInfo(method) {
                $('#payment-info').removeClass('hidden');
                $('#payment-info > div').addClass('hidden');
                $(`#payment-info-${method}`).removeClass('hidden');
            }

            $('#city-select').on('change', function() {
                selectedCityRef = $(this).val() || '';
                if (selectedCityRef) {
                    loadWarehousesOnce(selectedCityRef);
                } else {
                    resetWarehouses('Спочатку оберіть місто');
                    $warehouse.prop('disabled', true);
                }
            });

            function validateForm() {
                let isValid = true;
                $('input, textarea, select').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                const deliveryService = $('input[name="delivery_service"]:checked').val();
                if (!deliveryService) {
                    showError('Оберіть спосіб доставки');
                    isValid = false;
                }

                if (deliveryService === 'novaposhta') {
                    if (!$('#city-select').val()) {
                        showFieldError('#city-select', 'Оберіть місто');
                        isValid = false;
                    }
                    if (!$('#warehouse-select').val()) {
                        showFieldError('#warehouse-select', 'Оберіть відділення');
                        isValid = false;
                    }
                } else if (deliveryService && deliveryService !== 'pickup') {
                    const manualAddress = $('#manual-address').val().trim();
                    if (!manualAddress || manualAddress.length < 2) {
                        showFieldError('#manual-address', 'Введіть адресу доставки');
                        isValid = false;
                    }
                }

                const name = $('#name').val().trim();
                const lastname = $('#lastname').val().trim();
                const phone = $('#phone').val().trim();

                if (!name || name.length < 2) {
                    showFieldError('#name', "Введіть коректне ім'я");
                    isValid = false;
                }
                if (!lastname || lastname.length < 2) {
                    showFieldError('#lastname', 'Введіть коректне прізвище');
                    isValid = false;
                }
                if (!phone || phone.length < 10) {
                    showFieldError('#phone', 'Введіть коректний номер телефону');
                    isValid = false;
                }

                const email = ($('#email').val() || '').trim();
                const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                if (!email || !emailOk) {
                    showFieldError('#email', 'Введіть коректний email');
                    isValid = false;
                }

                if (!$('input[name="payment_method"]:checked').val()) {
                    showError('Оберіть спосіб оплати');
                    isValid = false;
                }

                return isValid;
            }

            function showFieldError(selector, message) {
                $(selector).addClass('is-invalid');
                $(selector).after(`<div class="invalid-feedback">${message}</div>`);
            }

            function showError(message) {
                alert(message);
            }

            async function refreshCsrfToken() {
                try {
                    const res = await fetch('/csrf-token', {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    const data = await res.json();
                    if (data.token) {
                        $('meta[name="csrf-token"]').attr('content', data.token);
                        return data.token;
                    }
                } catch (e) {}
                return $('meta[name="csrf-token"]').attr('content');
            }

            $('#order-form').on('submit', async function(e) {
                e.preventDefault();
                if (!validateForm()) return;

                const cart = localStorage.getItem('cart');
                if (!cart || cart === '[]' || cart === '{}' || cart === 'null') {
                    alert('Кошик порожній. Додайте товари до кошика.');
                    return;
                }

                $('#preloader').removeClass('hidden').addClass('flex');

                const token = await refreshCsrfToken();

                $.ajax({
                    type: 'POST',
                    url: '/order-submit',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    data: {
                        delivery_service: $('input[name="delivery_service"]:checked').val(),
                        city: (($('#city-select').select2('data')[0] || {}).text || ''),
                        warehouse: (($('#warehouse-select').select2('data')[0] || {}).text || ''),
                        manual_address: $('#manual-address').val().trim(),
                        name: $('#name').val().trim(),
                        lastname: $('#lastname').val().trim(),
                        fathername: $('#fathername').val().trim(),
                        phone: $('#phone').val().trim(),
                        email: $('#email').val().trim(),
                        comment: $('#comment').val().trim(),
                        payment: $('input[name="payment_method"]:checked').val(),
                        cart: cart,
                        total_price: $('#total_price_stream').val(),
                        _token: token
                    },
                    success: function(response) {
                        $('#preloader').addClass('hidden').removeClass('flex');
                        localStorage.removeItem('cart');
                        const redirectUrl = (response && response.redirect) ? response.redirect : '/thank-you';
                        window.location.href = redirectUrl;
                    },
                    error: function(xhr) {
                        $('#preloader').addClass('hidden').removeClass('flex');
                        let errorMessage = 'Помилка під час надсилання замовлення. Спробуйте ще раз.';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.error) errorMessage = response.error;
                            else if (response.message) errorMessage = response.message;
                            else if (response.errors) {
                                errorMessage = Object.values(response.errors).flat().join('\n');
                            }
                        } catch (err) {}
                        if (xhr.status === 419) {
                            errorMessage = 'Сесія застаріла. Оновіть сторінку і спробуйте ще раз.';
                        }
                        alert(errorMessage);
                    }
                });
            });
        });
    </script>
@endpush
