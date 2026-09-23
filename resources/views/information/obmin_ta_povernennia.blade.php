@extends('layouts.app')

@section('seo')
    <title>Обмін та повернення - ZMART</title>
    <meta name="description" content="Умови обміну та повернення товарів в інтернет-магазині ZMART. 14 днів на повернення, якісний сервіс.">
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
                <span class="text-gray-900 font-medium">Обмін та повернення</span>
            </nav>
        </div>
    </section>

    <!-- Header -->
    <section class="bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 py-12">
        <div class="max-w-site mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">Обмін та повернення</h1>
                <p class="text-xl text-emerald-100">Зручні умови обміну та повернення товарів</p>
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
                            <!-- Return Conditions -->
                            <div class="mb-12">
                                <h2 class="text-3xl font-bold text-gray-900 mb-8 flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-xl flex items-center justify-center mr-4">
                                        <i class="fas fa-undo text-white"></i>
                                    </div>
                                    Умови повернення
                                </h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl p-6">
                                        <div class="flex items-center mb-4">
                                            <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center mr-4">
                                                <i class="fas fa-check-circle text-white text-lg"></i>
                                            </div>
                                            <h3 class="text-xl font-bold text-gray-900">Стан товару</h3>
                                        </div>
                                        <p class="text-gray-700">
                                            Поверненню підлягає <strong>новий товар без слідів експлуатації</strong> 
                                            зі збереженням товарного вигляду, споживчих властивостей та упаковки.
                                        </p>
                                    </div>
                                    
                                    <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-2xl p-6">
                                        <div class="flex items-center mb-4">
                                            <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-cyan-500 rounded-xl flex items-center justify-center mr-4">
                                                <i class="fas fa-calendar-alt text-white text-lg"></i>
                                            </div>
                                            <h3 class="text-xl font-bold text-gray-900">Термін повернення</h3>
                                        </div>
                                        <p class="text-gray-700">
                                            Повернення товару відбувається протягом <strong>14 днів</strong> після покупки, 
                                            відповідно до чинного законодавства України.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Return Process -->
                            <div class="mb-12">
                                <h2 class="text-3xl font-bold text-gray-900 mb-8 flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-indigo-500 rounded-xl flex items-center justify-center mr-4">
                                        <i class="fas fa-list-ol text-white"></i>
                                    </div>
                                    Процедура повернення
                                </h2>
                                
                                <div class="space-y-6">
                                    <div class="flex items-start">
                                        <div class="w-8 h-8 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-full flex items-center justify-center mr-4 mt-1">
                                            <span class="text-white font-bold text-sm">1</span>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="text-lg font-bold text-gray-900 mb-2">Зв'яжіться з нами</h3>
                                            <p class="text-gray-700">
                                                Зверніться до нашого менеджера за телефоном <strong>063 63 100 41</strong> 
                                                або напишіть на email <strong>office@mtoys.com.ua</strong>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start">
                                        <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-cyan-500 rounded-full flex items-center justify-center mr-4 mt-1">
                                            <span class="text-white font-bold text-sm">2</span>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="text-lg font-bold text-gray-900 mb-2">Підготуйте товар</h3>
                                            <p class="text-gray-700">
                                                Упакуйте товар у оригінальну упаковку разом з усіма аксесуарами, 
                                                документами та гарантійним талоном
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start">
                                        <div class="w-8 h-8 bg-gradient-to-br from-purple-400 to-indigo-500 rounded-full flex items-center justify-center mr-4 mt-1">
                                            <span class="text-white font-bold text-sm">3</span>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="text-lg font-bold text-gray-900 mb-2">Відправте товар</h3>
                                            <p class="text-gray-700">
                                                Відправте товар службою доставки "Нова пошта" на наш адрес. 
                                                Вартість доставки при поверненні оплачує покупець
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start">
                                        <div class="w-8 h-8 bg-gradient-to-br from-orange-400 to-red-500 rounded-full flex items-center justify-center mr-4 mt-1">
                                            <span class="text-white font-bold text-sm">4</span>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="text-lg font-bold text-gray-900 mb-2">Отримайте гроші</h3>
                                            <p class="text-gray-700">
                                                Після перевірки товару ми повернемо кошти на вашу картку або рахунок 
                                                протягом 3-5 робочих днів
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Exchange Conditions -->
                            <div class="mb-12">
                                <h2 class="text-3xl font-bold text-gray-900 mb-8 flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl flex items-center justify-center mr-4">
                                        <i class="fas fa-exchange-alt text-white"></i>
                                    </div>
                                    Умови обміну
                                </h2>
                                
                                <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-2xl p-8">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-900 mb-4">Обмін на інший товар</h3>
                                            <ul class="space-y-3">
                                                <li class="flex items-center text-gray-700">
                                                    <i class="fas fa-check text-emerald-500 mr-3"></i>
                                                    Товар не підійшов за розміром, кольором або характеристиками
                                                </li>
                                                <li class="flex items-center text-gray-700">
                                                    <i class="fas fa-check text-emerald-500 mr-3"></i>
                                                    Виявлений заводський брак
                                                </li>
                                                <li class="flex items-center text-gray-700">
                                                    <i class="fas fa-check text-emerald-500 mr-3"></i>
                                                    Товар не відповідає опису
                                                </li>
                                            </ul>
                    </div>
                                        
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-900 mb-4">Додаткові умови</h3>
                                            <ul class="space-y-3">
                                                <li class="flex items-center text-gray-700">
                                                    <i class="fas fa-info-circle text-blue-500 mr-3"></i>
                                                    Обмін можливий протягом 14 днів
                                                </li>
                                                <li class="flex items-center text-gray-700">
                                                    <i class="fas fa-info-circle text-blue-500 mr-3"></i>
                                                    Товар повинен бути в оригінальній упаковці
                                                </li>
                                                <li class="flex items-center text-gray-700">
                                                    <i class="fas fa-info-circle text-blue-500 mr-3"></i>
                                                    При обміні на більш дорогий товар доплата за рахунок покупця
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Info -->
                            <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-2xl p-8 text-center">
                                <h3 class="text-2xl font-bold text-gray-900 mb-4">Потрібна допомога?</h3>
                                <p class="text-gray-700 mb-6">
                                    Якщо у вас виникли питання щодо обміну або повернення товару, 
                                    звертайтеся до наших менеджерів
                                </p>
                                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                                    <a href="tel:0636310041" class="bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-200 shadow-lg hover:shadow-xl">
                                        <i class="fas fa-phone mr-2"></i>
                                        063 63 100 41
                                    </a>
                                    <a href="mailto:office@mtoys.com.ua" class="bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-200 shadow-lg hover:shadow-xl">
                                        <i class="fas fa-envelope mr-2"></i>
                                        Написати нам
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

@section('scripts')
@endsection
