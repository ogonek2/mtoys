@extends('layouts.app')

@section('seo')
    <title>Оплата і доставка - ZMART</title>
    <meta name="description" content="Інформація про способи оплати та доставки в інтернет-магазині ZMART. Швидка доставка по всій Україні, зручні способи оплати.">
@endsection

@section('content')
    <!-- Breadcrumbs -->
    <section class="bg-gray-50 py-4">
        <div class="max-w-site mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ url('/') }}" class="text-gray-600 hover:text-emerald-600 transition-colors">
                    <i class="fas fa-home"></i>
                </a>
                <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                <span class="text-gray-900 font-medium">Оплата і доставка</span>
            </nav>
        </div>
    </section>

    <!-- Header -->
    <section class="bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 py-12">
        <div class="max-w-site mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">Оплата і доставка</h1>
                <p class="text-xl text-emerald-100">Зручні способи оплати та швидка доставка по всій Україні</p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="bg-gray-50 min-h-screen py-8">
        <div class="max-w-site mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-4 gap-8">
                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    @include('includes.main.information_bar')
                    </div>
                
                <!-- Main Content -->
                <div class="lg:col-span-3">
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="p-8">
                            <!-- Payment Methods Section -->
                            <div class="mb-12">
                                <h2 class="text-3xl font-bold text-gray-900 mb-8 flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-xl flex items-center justify-center mr-4">
                                        <i class="fas fa-credit-card text-white"></i>
                                    </div>
                                    Способи оплати замовлень
                                </h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-2xl p-6">
                                        <div class="flex items-center mb-4">
                                            <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-cyan-500 rounded-xl flex items-center justify-center mr-4">
                                                <i class="fas fa-credit-card text-white text-lg"></i>
                                            </div>
                                            <h3 class="text-xl font-bold text-gray-900">Банківською карткою</h3>
                                        </div>
                                        <p class="text-gray-700 mb-4">
                                            Оплата замовлення здійснюється безпосередньо на сайті банківською карткою 
                                            <strong>Visa</strong> або <strong>Mastercard</strong>.
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            Відправка товару здійснюється після підтвердження платежу.
                                        </p>
                                    </div>
                                    
                                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl p-6">
                                        <div class="flex items-center mb-4">
                                            <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center mr-4">
                                                <i class="fas fa-university text-white text-lg"></i>
                                            </div>
                                            <h3 class="text-xl font-bold text-gray-900">Оплата за реквізитами</h3>
                                        </div>
                                        <p class="text-gray-700 mb-4">
                                            Оплату замовлення за реквізитами можна здійснити у відділенні будь-якого банку, 
                                            зі свого поточного рахунку або з поточного рахунку компанії.
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            Відправка замовлення відбувається після надходження коштів на наш рахунок.
                                        </p>
                                    </div>
                                    
                                    <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-2xl p-6 md:col-span-2">
                                        <div class="flex items-center mb-4">
                                            <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-indigo-500 rounded-xl flex items-center justify-center mr-4">
                                                <i class="fas fa-truck text-white text-lg"></i>
                                            </div>
                                            <h3 class="text-xl font-bold text-gray-900">Післяплата у відділенні Нової пошти</h3>
                                        </div>
                                        <p class="text-gray-700 mb-4">
                                            Замовлення можна оплатити після того, як Ви отримали і оглянули товар на Новій пошті.
                                        </p>
                                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                            <p class="text-sm text-yellow-800">
                                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                                <strong>Зверніть увагу!</strong> Поштовий оператор може стягувати додаткові кошти за переказ грошей. 
                                                Вартість переказу заздалегідь уточнюйте в оператора.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Delivery Rules Section -->
                            <div class="mb-12">
                                <h2 class="text-3xl font-bold text-gray-900 mb-8 flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-red-500 rounded-xl flex items-center justify-center mr-4">
                                        <i class="fas fa-shipping-fast text-white"></i>
                                    </div>
                                    Правила відправки і доставки
                        </h2>

                                <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-2xl p-8">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                                <i class="fas fa-clock text-emerald-500 mr-3"></i>
                                                Час відправки
                                            </h3>
                                            <div class="space-y-4">
                                                <div class="bg-white rounded-xl p-4 shadow-sm">
                                                    <h4 class="font-semibold text-gray-900 mb-2">Звичайні замовлення</h4>
                                                    <p class="text-gray-700 text-sm">
                                                        Відправки замовлень, <strong>підтверджених</strong> до 16.00 з понеділка по п'ятницю, 
                                                        відправляються в той же день.
                                                    </p>
                                                </div>
                                                
                                                <div class="bg-white rounded-xl p-4 shadow-sm">
                                                    <h4 class="font-semibold text-gray-900 mb-2">Вихідні та пізні замовлення</h4>
                                                    <p class="text-gray-700 text-sm">
                                                        Замовлення, прийняті в п'ятницю після 16.00 або у вихідні дні, 
                                                        відправляються у найближчий робочий день.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                                                <i class="fas fa-map-marker-alt text-blue-500 mr-3"></i>
                                                Терміни доставки
                                            </h3>
                                            <div class="bg-white rounded-xl p-4 shadow-sm">
                                                <p class="text-gray-700 mb-4">
                                                    Терміни доставки в середньому займають <strong>1-3 дні</strong> і залежать від місця призначення замовлення.
                                                </p>
                                                <p class="text-sm text-gray-600">
                            Більш детальну інформацію про терміни доставки можна отримати в поштового оператора Нова пошта.
                        </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Confirmed Orders Section -->
                            <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-2xl p-8">
                                <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                                    <div class="w-8 h-8 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-check-circle text-white"></i>
                                    </div>
                                    Підтверджені замовлення
                                </h3>
                                
                                <p class="text-gray-700 mb-6">
                                    <strong>Підтвердженими</strong> вважаються замовлення:
                                </p>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="flex items-start">
                                        <i class="fas fa-phone text-emerald-500 mt-1 mr-3"></i>
                                        <div>
                                            <h4 class="font-semibold text-gray-900 mb-2">Телефонне підтвердження</h4>
                                            <p class="text-gray-700 text-sm">
                                                Підтверджені покупцем після телефонного дзвінка менеджера або в переписці 
                                                у будь-якому зручному месенджері
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start">
                                        <i class="fas fa-money-bill-wave text-emerald-500 mt-1 mr-3"></i>
                                        <div>
                                            <h4 class="font-semibold text-gray-900 mb-2">Безготівкова оплата</h4>
                                            <p class="text-gray-700 text-sm">
                                                При виборі безготівкового розрахунку оплата за замовлення підтверджена 
                                                та кошти надійшли на рахунок продавця
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
@endsection
