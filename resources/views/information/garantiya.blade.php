@extends('layouts.app')

@section('seo')
    <title>Гарантія — Mtoys</title>
    <meta name="description" content="Гарантія та умови її надання в інтернет-магазині Mtoys. Обмін і повернення протягом 14 днів, випадки відмови в гарантії.">
@endsection

@section('content')
    <section class="bg-[#F2F7FA] py-4">
        <div class="max-w-site mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ url('/') }}" class="text-gray-600 hover:text-[#FF6B4A] transition-colors">
                    <i class="fas fa-home"></i>
                </a>
                <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                <span class="text-[#0E3A45] font-medium">Гарантія</span>
            </nav>
        </div>
    </section>

    <section class="bg-[#0E3A45] py-12">
        <div class="max-w-site mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="font-heading text-xs font-semibold uppercase tracking-[0.25em] text-[#FF6B4A] mb-3">Mtoys</p>
            <h1 class="font-heading text-4xl md:text-5xl font-bold text-white mb-4">Гарантія</h1>
            <p class="text-xl text-white/70">Гарантія та умови її надання</p>
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
                        <div class="p-8 space-y-10 text-gray-700 leading-relaxed text-sm">

                            <section>
                                <h2 class="font-heading text-2xl font-bold text-[#0E3A45] mb-4 flex items-center gap-3">
                                    <span class="w-10 h-10 bg-[#0E3A45] flex items-center justify-center shrink-0">
                                        <i class="fas fa-shield-alt text-[#FF6B4A]"></i>
                                    </span>
                                    Які умови супроводжують надання гарантії?
                                </h2>
                                <p class="text-base leading-relaxed">
                                    Ми гарантуємо обмін або повернення товару протягом <strong>14 днів</strong> з моменту покупки
                                    за умови збереження його цілісності та товарного вигляду. Зверніть увагу, що гарантія
                                    не поширюється на товари, повернення яких обмежене чинним законодавством України.
                                </p>
                            </section>

                            <section>
                                <h2 class="font-heading text-xl font-bold text-[#0E3A45] mb-4">Гарантія не надається, якщо:</h2>
                                <ul class="space-y-3">
                                    <li class="flex gap-3">
                                        <span class="text-[#FF6B4A] shrink-0 mt-0.5">—</span>
                                        <span>порушено цілісність гарантійних пломб;</span>
                                    </li>
                                    <li class="flex gap-3">
                                        <span class="text-[#FF6B4A] shrink-0 mt-0.5">—</span>
                                        <span>є механічні або інші пошкодження, що виникли внаслідок умисних або необережних дій покупця чи третіх осіб;</span>
                                    </li>
                                    <li class="flex gap-3">
                                        <span class="text-[#FF6B4A] shrink-0 mt-0.5">—</span>
                                        <span>порушено правила використання, викладені в експлуатаційних документах;</span>
                                    </li>
                                    <li class="flex gap-3">
                                        <span class="text-[#FF6B4A] shrink-0 mt-0.5">—</span>
                                        <span>було здійснено несанкціоноване розкриття, ремонт або змінено внутрішні комунікації та компоненти товару, змінено конструкцію чи схеми товару;</span>
                                    </li>
                                    <li class="flex gap-3">
                                        <span class="text-[#FF6B4A] shrink-0 mt-0.5">—</span>
                                        <span>серійний або IMEI-номер, що зберігається в пам’яті виробу, змінено, стерто або не може бути встановлено.</span>
                                    </li>
                                </ul>
                            </section>

                            <section>
                                <h2 class="font-heading text-xl font-bold text-[#0E3A45] mb-4">Гарантійні зобов’язання не поширюються на такі несправності:</h2>
                                <ul class="space-y-3">
                                    <li class="flex gap-3">
                                        <span class="text-[#FF6B4A] shrink-0 mt-0.5">—</span>
                                        <span>природний знос або вичерпання ресурсу;</span>
                                    </li>
                                    <li class="flex gap-3">
                                        <span class="text-[#FF6B4A] shrink-0 mt-0.5">—</span>
                                        <span>випадкові пошкодження, спричинені клієнтом, або пошкодження, що виникли внаслідок недбалого ставлення чи використання (вплив рідини, запиленості, потрапляння всередину корпусу сторонніх предметів тощо);</span>
                                    </li>
                                    <li class="flex gap-3">
                                        <span class="text-[#FF6B4A] shrink-0 mt-0.5">—</span>
                                        <span>пошкодження внаслідок стихійних лих (природних явищ);</span>
                                    </li>
                                    <li class="flex gap-3">
                                        <span class="text-[#FF6B4A] shrink-0 mt-0.5">—</span>
                                        <span>пошкодження, спричинені аварійним підвищенням або зниженням напруги в електромережі чи неправильним підключенням до електромережі;</span>
                                    </li>
                                    <li class="flex gap-3">
                                        <span class="text-[#FF6B4A] shrink-0 mt-0.5">—</span>
                                        <span>пошкодження, спричинені дефектами системи, в якій використовувався товар, або виникли внаслідок з’єднання та підключення товару до інших виробів;</span>
                                    </li>
                                    <li class="flex gap-3">
                                        <span class="text-[#FF6B4A] shrink-0 mt-0.5">—</span>
                                        <span>пошкодження, спричинені використанням товару не за призначенням або з порушенням правил експлуатації.</span>
                                    </li>
                                </ul>
                            </section>

                            <div class="bg-[#0E3A45] p-8 text-center">
                                <h3 class="font-heading text-xl font-bold text-white mb-3">Питання щодо гарантії?</h3>
                                <p class="text-white/70 mb-6 text-sm">Наші менеджери допоможуть оформити гарантійне звернення</p>
                                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                                    <a href="tel:0636310041" class="btn-domiko-primary px-6 py-3 inline-flex items-center justify-center gap-2">
                                        <i class="fas fa-phone"></i> 063 63 100 41
                                    </a>
                                    <a href="mailto:office@mtoys.com.ua" class="border border-[#FF6B4A] text-[#FF6B4A] hover:bg-[#FF6B4A] hover:text-[#0E3A45] px-6 py-3 inline-flex items-center justify-center gap-2 transition-colors font-heading font-semibold">
                                        <i class="fas fa-envelope"></i> Написати нам
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
