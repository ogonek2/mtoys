@extends('layouts.app')

@section('seo')
    <title>Оплата — Mtoys</title>
    <meta name="description" content="Способи оплати замовлень в інтернет-магазині Mtoys: готівка, безготівковий розрахунок, банківська картка через LiqPay.">
@endsection

@section('content')
    <section class="bg-[#F2F7FA] py-4">
        <div class="max-w-site mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ url('/') }}" class="text-gray-600 hover:text-[#FF6B4A] transition-colors">
                    <i class="fas fa-home"></i>
                </a>
                <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                <span class="text-[#0E3A45] font-medium">Оплата</span>
            </nav>
        </div>
    </section>

    <section class="bg-[#0E3A45] py-12">
        <div class="max-w-site mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="font-heading text-xs font-semibold uppercase tracking-[0.25em] text-[#FF6B4A] mb-3">Mtoys</p>
            <h1 class="font-heading text-4xl md:text-5xl font-bold text-white mb-4">Оплата</h1>
            <p class="text-xl text-white/70">Готівкою, безготівковим розрахунком або банківськими картками</p>
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
                                        <i class="fas fa-university text-[#FF6B4A]"></i>
                                    </span>
                                    Як оплатити товар безготівковим розрахунком?
                                </h2>
                                <p class="text-base leading-relaxed">
                                    Після оформлення заявки на електронну пошту або за номером телефону в один із месенджерів
                                    буде надіслано рахунок із реквізитами для оплати.
                                    Відвантаження замовлення здійснюється лише після зарахування коштів на рахунок магазину.
                                </p>
                            </section>

                            <section>
                                <h2 class="font-heading text-2xl font-bold text-[#0E3A45] mb-4 flex items-center gap-3">
                                    <span class="w-10 h-10 bg-[#0E3A45] flex items-center justify-center shrink-0">
                                        <i class="fas fa-money-bill-wave text-[#FF6B4A]"></i>
                                    </span>
                                    Як можна оплатити замовлення готівкою?
                                </h2>
                                <p class="text-base leading-relaxed">
                                    Замовлення оплачується готівкою при отриманні в пункті видачі, кур'єру або у фізичному магазині.
                                </p>
                            </section>

                            <div class="border border-[#FF6B4A]/40 bg-[#EEF8F7] p-6 text-base text-[#0E3A45]">
                                <i class="fas fa-bell text-[#FF6B4A] mr-2"></i>
                                Ми зателефонуємо вам або надішлемо повідомлення про відправку замовлення до відділення перевізника,
                                заплановану дату отримання в магазині або за вашою адресою.
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
