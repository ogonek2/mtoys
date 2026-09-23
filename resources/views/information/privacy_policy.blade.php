@extends('layouts.app')

@section('seo')
    <title>Політика конфіденційності — Mtoys</title>
    <meta name="description" content="Політика конфіденційності інтернет-магазину Mtoys. Захист персональних даних користувачів.">
@endsection

@section('content')
    <section class="bg-[#F2F7FA] py-4">
        <div class="max-w-site mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ url('/') }}" class="text-gray-600 hover:text-[#FF6B4A] transition-colors">
                    <i class="fas fa-home"></i>
                </a>
                <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                <span class="text-[#0E3A45] font-medium">Політика конфіденційності</span>
            </nav>
        </div>
    </section>

    <section class="bg-[#0E3A45] py-12">
        <div class="max-w-site mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="font-heading text-xs font-semibold uppercase tracking-[0.25em] text-[#FF6B4A] mb-3">Mtoys</p>
            <h1 class="font-heading text-4xl md:text-5xl font-bold text-white mb-4">Політика конфіденційності</h1>
            <p class="text-xl text-white/70">Захист персональних даних користувачів</p>
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

                            <p class="text-center text-gray-500 text-xs">Останнє оновлення: 28 липня 2025 року</p>

                            <p class="text-base">Ця Політика конфіденційності пояснює, як інтернет-магазин <strong>Mtoys</strong> (далі — «ми», «наш сайт») збирає, використовує та захищає персональні дані користувачів (далі — «ви», «користувач») при відвідуванні та використанні сайту.</p>

                            <section>
                                <h2 class="font-heading text-xl font-bold text-[#0E3A45] mb-4 flex items-center gap-3">
                                    <span class="w-8 h-8 bg-[#0E3A45] flex items-center justify-center shrink-0"><i class="fas fa-info-circle text-[#FF6B4A] text-sm"></i></span>
                                    1. Збір інформації
                                </h2>
                                <p class="mb-3">Ми можемо збирати такі дані під час використання сайту:</p>
                                <ul class="list-disc list-inside space-y-1.5 ml-2">
                                    <li>Ім'я та прізвище</li>
                                    <li>Номер телефону</li>
                                    <li>Електронна пошта</li>
                                    <li>Адреса доставки</li>
                                    <li>IP-адреса, дані браузера та тип пристрою</li>
                                </ul>
                            </section>

                            <section>
                                <h2 class="font-heading text-xl font-bold text-[#0E3A45] mb-4 flex items-center gap-3">
                                    <span class="w-8 h-8 bg-[#0E3A45] flex items-center justify-center shrink-0"><i class="fas fa-cogs text-[#FF6B4A] text-sm"></i></span>
                                    2. Використання інформації
                                </h2>
                                <p class="mb-3">Ми використовуємо ваші дані з наступною метою:</p>
                                <ul class="list-disc list-inside space-y-1.5 ml-2">
                                    <li>Обробка та доставка замовлень</li>
                                    <li>Зв'язок із клієнтом</li>
                                    <li>Надання підтримки</li>
                                    <li>Покращення роботи сайту</li>
                                    <li>Рекламні та маркетингові повідомлення (з вашої згоди)</li>
                                </ul>
                            </section>

                            <section>
                                <h2 class="font-heading text-xl font-bold text-[#0E3A45] mb-4 flex items-center gap-3">
                                    <span class="w-8 h-8 bg-[#0E3A45] flex items-center justify-center shrink-0"><i class="fas fa-shield-alt text-[#FF6B4A] text-sm"></i></span>
                                    3. Захист персональних даних
                                </h2>
                                <p>Ми вживаємо всі необхідні технічні та організаційні заходи для захисту ваших персональних даних від несанкціонованого доступу, зміни чи знищення.</p>
                            </section>

                            <section>
                                <h2 class="font-heading text-xl font-bold text-[#0E3A45] mb-4 flex items-center gap-3">
                                    <span class="w-8 h-8 bg-[#0E3A45] flex items-center justify-center shrink-0"><i class="fas fa-users text-[#FF6B4A] text-sm"></i></span>
                                    4. Розкриття інформації третім особам
                                </h2>
                                <p class="mb-3">Ми не продаємо і не передаємо ваші персональні дані третім особам, за винятком випадків, коли це необхідно для:</p>
                                <ul class="list-disc list-inside space-y-1.5 ml-2">
                                    <li>Виконання замовлення (служби доставки, платіжні сервіси тощо)</li>
                                    <li>Дотримання законодавства України</li>
                                </ul>
                            </section>

                            <section>
                                <h2 class="font-heading text-xl font-bold text-[#0E3A45] mb-4 flex items-center gap-3">
                                    <span class="w-8 h-8 bg-[#0E3A45] flex items-center justify-center shrink-0"><i class="fas fa-cookie-bite text-[#FF6B4A] text-sm"></i></span>
                                    5. Cookies
                                </h2>
                                <p>Ми використовуємо файли cookie для зручності користувачів, аналітики трафіку та покращення функціональності сайту. Ви можете змінити налаштування cookies у своєму браузері.</p>
                            </section>

                            <section>
                                <h2 class="font-heading text-xl font-bold text-[#0E3A45] mb-4 flex items-center gap-3">
                                    <span class="w-8 h-8 bg-[#0E3A45] flex items-center justify-center shrink-0"><i class="fas fa-external-link-alt text-[#FF6B4A] text-sm"></i></span>
                                    6. Посилання на інші сайти
                                </h2>
                                <p>Наш сайт може містити посилання на сторонні ресурси. Ми не несемо відповідальності за зміст або політику конфіденційності таких сайтів.</p>
                            </section>

                            <section>
                                <h2 class="font-heading text-xl font-bold text-[#0E3A45] mb-4 flex items-center gap-3">
                                    <span class="w-8 h-8 bg-[#0E3A45] flex items-center justify-center shrink-0"><i class="fas fa-handshake text-[#FF6B4A] text-sm"></i></span>
                                    7. Згода
                                </h2>
                                <p>Використовуючи наш сайт, ви надаєте згоду на збір та обробку ваших персональних даних відповідно до цієї Політики конфіденційності.</p>
                            </section>

                            <section>
                                <h2 class="font-heading text-xl font-bold text-[#0E3A45] mb-4 flex items-center gap-3">
                                    <span class="w-8 h-8 bg-[#0E3A45] flex items-center justify-center shrink-0"><i class="fas fa-user-check text-[#FF6B4A] text-sm"></i></span>
                                    8. Права користувача
                                </h2>
                                <p class="mb-3">Ви маєте право:</p>
                                <ul class="list-disc list-inside space-y-1.5 ml-2">
                                    <li>Отримати інформацію про свої персональні дані</li>
                                    <li>Змінити або видалити свої дані</li>
                                    <li>Відкликати згоду на обробку персональних даних</li>
                                </ul>
                            </section>

                            <section>
                                <h2 class="font-heading text-xl font-bold text-[#0E3A45] mb-4 flex items-center gap-3">
                                    <span class="w-8 h-8 bg-[#0E3A45] flex items-center justify-center shrink-0"><i class="fas fa-edit text-[#FF6B4A] text-sm"></i></span>
                                    9. Зміни до політики
                                </h2>
                                <p>Ми можемо періодично оновлювати цю Політику. Всі зміни будуть публікуватися на цій сторінці з відповідною датою оновлення.</p>
                            </section>

                            <section>
                                <h2 class="font-heading text-xl font-bold text-[#0E3A45] mb-4 flex items-center gap-3">
                                    <span class="w-8 h-8 bg-[#0E3A45] flex items-center justify-center shrink-0"><i class="fas fa-phone text-[#FF6B4A] text-sm"></i></span>
                                    10. Контактна інформація
                                </h2>
                                <p class="mb-3">Якщо у вас виникли питання щодо Політики конфіденційності, звертайтесь:</p>
                                <ul class="space-y-1.5 ml-2">
                                    <li><strong>Email:</strong> office@mtoys.com.ua</li>
                                    <li><strong>Телефон:</strong> 063 63 100 41</li>
                                    <li><strong>Адреса:</strong> вул. Фабрична, номер контейнер 2177, 7 км, Одеса</li>
                                </ul>
                            </section>

                            <div class="border border-[#FF6B4A]/40 bg-[#EEF8F7] p-6 text-center text-[#0E3A45] text-sm">
                                Використовуючи сайт Mtoys, ви погоджуєтесь з цією політикою конфіденційності.
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
