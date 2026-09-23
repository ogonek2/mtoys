@extends('layouts.app')

@section('seo')
    <title>Контактна інформація — Mtoys</title>
    <meta name="description" content="Контактна інформація інтернет-магазину Mtoys. Телефон, адреса, email. Зв'яжіться з нами для консультації та замовлення.">
@endsection

@section('content')
    <section class="bg-[#F2F7FA] py-4">
        <div class="max-w-site mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ url('/') }}" class="text-gray-600 hover:text-[#FF6B4A] transition-colors">
                    <i class="fas fa-home"></i>
                </a>
                <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                <span class="text-[#0E3A45] font-medium">Контактна інформація</span>
            </nav>
        </div>
    </section>

    <section class="bg-[#0E3A45] py-12">
        <div class="max-w-site mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="font-heading text-xs font-semibold uppercase tracking-[0.25em] text-[#FF6B4A] mb-3">Mtoys</p>
            <h1 class="font-heading text-4xl md:text-5xl font-bold text-white mb-4">Контактна інформація</h1>
            <p class="text-xl text-white/70">Зв'яжіться з нами для консультації та замовлення</p>
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

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="border border-gray-100 bg-[#F2F7FA] p-6 text-center">
                                    <span class="w-14 h-14 bg-[#0E3A45] flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-phone text-[#FF6B4A] text-xl"></i>
                                    </span>
                                    <h3 class="font-heading text-lg font-bold text-[#0E3A45] mb-3">Телефон</h3>
                                    <a href="tel:0636310041" class="text-[#0E3A45] hover:text-[#FF6B4A] transition-colors text-lg font-semibold block mb-2">063 63 100 41</a>
                                </div>

                                <div class="border border-gray-100 bg-[#F2F7FA] p-6 text-center">
                                    <span class="w-14 h-14 bg-[#0E3A45] flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-map-marker-alt text-[#FF6B4A] text-xl"></i>
                                    </span>
                                    <h3 class="font-heading text-lg font-bold text-[#0E3A45] mb-3">Адреса</h3>
                                    <p class="text-gray-600 text-sm leading-relaxed">
                                        вул. Фабрична, номер контейнер 2177, 7 км, Одеса
                                    </p>
                                </div>

                                <div class="border border-gray-100 bg-[#F2F7FA] p-6 text-center">
                                    <span class="w-14 h-14 bg-[#0E3A45] flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-envelope text-[#FF6B4A] text-xl"></i>
                                    </span>
                                    <h3 class="font-heading text-lg font-bold text-[#0E3A45] mb-3">Пошта</h3>
                                    <a href="mailto:office@mtoys.com.ua" class="text-[#0E3A45] hover:text-[#FF6B4A] transition-colors font-semibold">office@mtoys.com.ua</a>
                                </div>
                            </div>

                            <div class="border border-gray-100 bg-[#F2F7FA] p-8">
                                <h3 class="font-heading text-2xl font-bold text-[#0E3A45] mb-6 text-center flex items-center justify-center gap-3">
                                    <span class="w-8 h-8 bg-[#0E3A45] flex items-center justify-center shrink-0"><i class="fas fa-clock text-[#FF6B4A] text-sm"></i></span>
                                    Графік роботи
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center text-sm">
                                    <div>
                                        <h4 class="font-bold text-[#0E3A45] mb-1">Пн — Чт</h4>
                                        <p class="text-gray-600">08:00 — 18:00</p>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-[#0E3A45] mb-1">П'ятниця</h4>
                                        <p class="text-red-500 font-semibold">Вихідний</p>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-[#0E3A45] mb-1">Сб — Нд</h4>
                                        <p class="text-gray-600">08:00 — 18:00</p>
                                    </div>
                                </div>
                            </div>

                            <div class="border border-gray-100 bg-[#F2F7FA] p-8">
                                <h3 class="font-heading text-2xl font-bold text-[#0E3A45] mb-6 text-center">Написати нам</h3>

                                @if (session('contact_success'))
                                    <div class="mb-6 border border-green-200 bg-green-50 text-green-700 px-4 py-3 text-sm text-center">
                                        {{ session('contact_success') }}
                                    </div>
                                @elseif (session('contact_error'))
                                    <div class="mb-6 border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm text-center">
                                        {{ session('contact_error') }}
                                    </div>
                                @endif

                                @if ($errors->any())
                                    <div class="mb-6 border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
                                        <ul class="list-disc list-inside space-y-1 text-left">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <form class="space-y-5" method="POST" action="{{ route('contact_request') }}">
                                    @csrf
                                    <div>
                                        <label for="contact-phone" class="block text-sm font-bold text-[#0E3A45] mb-2">Номер телефону *</label>
                                        <input id="contact-phone" name="phone" type="text" value="{{ old('phone') }}" required class="w-full px-4 py-3 border border-gray-200 focus:border-[#FF6B4A] focus:ring-0 transition-colors text-sm" placeholder="063 63 100 41">
                                        @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="contact-message" class="block text-sm font-bold text-[#0E3A45] mb-2">Повідомлення <span class="text-gray-400 font-normal">(необов'язково)</span></label>
                                        <textarea id="contact-message" name="message" rows="4" class="w-full px-4 py-3 border border-gray-200 focus:border-[#FF6B4A] focus:ring-0 transition-colors text-sm" placeholder="Ваше повідомлення">{{ old('message') }}</textarea>
                                        @error('message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="text-center">
                                        <button type="submit" class="btn-domiko-primary px-8 py-3 inline-flex items-center justify-center gap-2">
                                            <i class="fas fa-paper-plane"></i> Відправити повідомлення
                                        </button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
