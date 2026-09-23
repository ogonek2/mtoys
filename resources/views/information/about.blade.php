@extends('layouts.app')

@section('seo')
    <title>Про нас — Mtoys</title>
    <meta name="description" content="Дізнайтеся більше про Mtoys — ваш надійний інтернет-магазин товарів для дому, кухні та ванної. Якість, гарантія, швидка доставка.">
@endsection

@section('content')
    <section class="bg-[#F2F7FA] py-4">
        <div class="max-w-site mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ url('/') }}" class="text-gray-600 hover:text-[#FF6B4A] transition-colors">
                    <i class="fas fa-home"></i>
                </a>
                <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                <span class="text-[#0E3A45] font-medium">Про нас</span>
            </nav>
        </div>
    </section>

    <section class="bg-[#0E3A45] py-12">
        <div class="max-w-site mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="font-heading text-xs font-semibold uppercase tracking-[0.25em] text-[#FF6B4A] mb-3">Mtoys</p>
            <h1 class="font-heading text-4xl md:text-5xl font-bold text-white mb-4">Про нас</h1>
            <p class="text-xl text-white/70">Якісні товари для дому, кухні та ванної</p>
        </div>
    </section>

    <div class="bg-[#F2F7FA] min-h-screen py-8">
        <div class="max-w-site mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-4 gap-8">
                <div class="lg:col-span-1">
                    @include('includes.main.information_bar')
                </div>

                <div class="lg:col-span-3">
                    <div class="bg-white border border-gray-100 shadow-sm overflow-hidden">
                        <div class="p-8 space-y-10">

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div class="border border-gray-100 bg-[#F2F7FA] p-8">
                                    <div class="flex items-center mb-6 gap-3">
                                        <span class="w-12 h-12 bg-[#0E3A45] flex items-center justify-center shrink-0">
                                            <i class="fas fa-bullseye text-[#FF6B4A] text-lg"></i>
                                        </span>
                                        <h3 class="font-heading text-2xl font-bold text-[#0E3A45]">Наша місія</h3>
                                    </div>
                                    <p class="text-gray-700 leading-relaxed text-sm">
                                        Ми створили <strong class="text-[#0E3A45]">Mtoys</strong>, щоб зробити якісні товари для дому доступними кожному українцю.
                                        Наша мета — забезпечити вас сучасними товарами за розумною ціною з максимально зручним сервісом.
                                    </p>
                                </div>

                                <div class="border border-gray-100 bg-[#F2F7FA] p-8">
                                    <div class="flex items-center mb-6 gap-3">
                                        <span class="w-12 h-12 bg-[#0E3A45] flex items-center justify-center shrink-0">
                                            <i class="fas fa-gift text-[#FF6B4A] text-lg"></i>
                                        </span>
                                        <h3 class="font-heading text-2xl font-bold text-[#0E3A45]">Що ми пропонуємо</h3>
                                    </div>
                                    <ul class="space-y-2.5 text-sm">
                                        <li class="flex items-center text-gray-700 gap-2">
                                            <i class="fas fa-check text-[#FF6B4A]"></i>
                                            Іграшки, кухні та ванної
                                        </li>
                                        <li class="flex items-center text-gray-700 gap-2">
                                            <i class="fas fa-check text-[#FF6B4A]"></i>
                                            Аксесуари та побутова техніка
                                        </li>
                                        <li class="flex items-center text-gray-700 gap-2">
                                            <i class="fas fa-check text-[#FF6B4A]"></i>
                                            Професійна консультація та підтримка
                                        </li>
                                        <li class="flex items-center text-gray-700 gap-2">
                                            <i class="fas fa-check text-[#FF6B4A]"></i>
                                            Швидка доставка по Україні
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div>
                                <h3 class="font-heading text-2xl font-bold text-[#0E3A45] mb-6 text-center">Чому обирають Mtoys</h3>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    @foreach ([
                                        ['icon' => 'fa-star', 'title' => 'Якість товарів', 'desc' => 'Великий вибір перевірених товарів'],
                                        ['icon' => 'fa-tags', 'title' => 'Кращі ціни', 'desc' => 'Конкурентні ціни та акції'],
                                        ['icon' => 'fa-thumbs-up', 'title' => 'Відгуки клієнтів', 'desc' => 'Позитивні відгуки покупців'],
                                        ['icon' => 'fa-bolt', 'title' => 'Швидке замовлення', 'desc' => 'Просте та швидке оформлення'],
                                    ] as $item)
                                    <div class="text-center border border-gray-100 p-5">
                                        <span class="w-14 h-14 bg-[#0E3A45] flex items-center justify-center mx-auto mb-3">
                                            <i class="fas {{ $item['icon'] }} text-[#FF6B4A] text-lg"></i>
                                        </span>
                                        <h4 class="font-heading font-bold text-[#0E3A45] mb-1 text-sm">{{ $item['title'] }}</h4>
                                        <p class="text-xs text-gray-500">{{ $item['desc'] }}</p>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="border border-gray-100 bg-[#F2F7FA] p-8">
                                <h3 class="font-heading text-2xl font-bold text-[#0E3A45] mb-6 text-center">Контактна інформація</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center text-sm">
                                    <div>
                                        <span class="w-12 h-12 bg-[#0E3A45] flex items-center justify-center mx-auto mb-3">
                                            <i class="fas fa-envelope text-[#FF6B4A]"></i>
                                        </span>
                                        <h4 class="font-bold text-[#0E3A45] mb-1">Email</h4>
                                        <a href="mailto:office@mtoys.com.ua" class="text-gray-600 hover:text-[#FF6B4A] transition-colors">office@mtoys.com.ua</a>
                                    </div>
                                    <div>
                                        <span class="w-12 h-12 bg-[#0E3A45] flex items-center justify-center mx-auto mb-3">
                                            <i class="fas fa-phone text-[#FF6B4A]"></i>
                                        </span>
                                        <h4 class="font-bold text-[#0E3A45] mb-1">Телефон</h4>
                                        <a href="tel:0636310041" class="text-gray-600 hover:text-[#FF6B4A] transition-colors block">063 63 100 41</a>
                                    </div>
                                    <div>
                                        <span class="w-12 h-12 bg-[#0E3A45] flex items-center justify-center mx-auto mb-3">
                                            <i class="fas fa-map-marker-alt text-[#FF6B4A]"></i>
                                        </span>
                                        <h4 class="font-bold text-[#0E3A45] mb-1">Адреса</h4>
                                        <p class="text-gray-500">вул. Фабрична, номер контейнер 2177, 7 км, Одеса</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-[#0E3A45] p-8 text-center">
                                <h3 class="font-heading text-2xl font-bold text-white mb-3">Дякуємо за довіру!</h3>
                                <p class="text-white/70">Дякуємо, що обираєте <strong class="text-[#FF6B4A]">Mtoys</strong>. Ми працюємо для вас.</p>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
