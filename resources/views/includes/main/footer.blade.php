<!-- Footer -->
<footer class="bg-[#0E3A45] text-white">
    <div class="max-w-site mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- About -->
            <div class="lg:col-span-1">
                <div class="flex items-center gap-3 mb-6">
                    <img src="{{ asset('storage/src/logo.svg') }}" alt="Mtoys" class="h-auto w-20 brightness-0 invert">
                    <div>
                        <h3 class="font-heading text-xl font-bold">
                            M<span class="text-[#FF6B4A]">toys</span>
                        </h3>
                        <p class="text-[10px] tracking-brand-wide text-white/50 uppercase">Іграшки для гри і подарунків</p>
                    </div>
                </div>
                <p class="text-white/60 mb-6 leading-relaxed text-sm">
                    Яскраві іграшки оптом і в роздріб. Широкий асортимент, перевірена якість та швидка доставка по Україні.
                </p>
            </div>

            <!-- Information -->
            <div>
                <h4 class="font-heading text-base font-bold mb-4 text-[#FFB703]">Інформація</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ route('pro_kompaniiu') }}" class="text-white/60 hover:text-white transition-colors text-sm">Про компанію</a></li>
                    <li><a href="{{ route('garantiya') }}" class="text-white/60 hover:text-white transition-colors text-sm">Гарантія</a></li>
                    <li><a href="{{ route('oplata') }}" class="text-white/60 hover:text-white transition-colors text-sm">Оплата</a></li>
                    <li><a href="{{ route('dostavka_ta_povernennia') }}" class="text-white/60 hover:text-white transition-colors text-sm">Доставка та повернення</a></li>
                    <li><a href="{{ route('dohovir_oferty') }}" class="text-white/60 hover:text-white transition-colors text-sm">Договір публічної оферти</a></li>
                    <li><a href="{{ route('privacy_policy') }}" class="text-white/60 hover:text-white transition-colors text-sm">Політика конфіденційності</a></li>
                </ul>
            </div>

            <!-- Contacts -->
            <div>
                <h4 class="font-heading text-base font-bold mb-4 text-[#FFB703]">Контакти</h4>
                <ul class="space-y-3 text-sm">
                    <li class="flex items-start gap-3">
                        <x-lucide-icon name="phone" width="16" class="text-[#FF6B4A] mt-0.5" />
                        <div>
                            <a href="tel:0636310041" class="text-white/80 hover:text-white transition-colors">063 63 100 41</a>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <x-lucide-icon name="mail" width="16" class="text-[#FF6B4A] mt-0.5" />
                        <a href="mailto:office@mtoys.com.ua" class="text-white/80 hover:text-white transition-colors">office@mtoys.com.ua</a>
                    </li>
                    <li class="flex items-start gap-3">
                        <x-lucide-icon name="map-pin" width="16" class="text-[#FF6B4A] mt-0.5" />
                        <span class="text-white/60">вул. Фабрична, номер контейнер 2177, 7 км, Одеса</span>
                    </li>
                </ul>
            </div>

            <!-- Working Hours -->
            <div>
                <h4 class="font-heading text-base font-bold mb-4 text-[#FFB703]">Графік роботи</h4>
                <ul class="space-y-2 text-sm mb-6">
                    <li class="flex justify-between">
                        <span class="text-white/50">Пн – Чт</span>
                        <span class="text-white/80">08:00 – 18:00</span>
                    </li>
                    <li class="flex justify-between">
                        <span class="text-white/50">П'ятниця</span>
                        <span class="text-red-400">Вихідний</span>
                    </li>
                    <li class="flex justify-between">
                        <span class="text-white/50">Сб – Нд</span>
                        <span class="text-white/80">08:00 – 18:00</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="max-w-site mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <div class="flex flex-col md:flex-row justify-between items-center gap-3 text-sm text-white/50">
                <div>&copy; {{ date('Y') }} Mtoys. Всі права захищені.</div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('kontaktna_informatsiia') }}" class="hover:text-[#FF6B4A] transition-colors">Контакти</a>
                    <a href="{{ route('privacy_policy') }}" class="hover:text-[#FF6B4A] transition-colors">Конфіденційність</a>
                    <a href="{{ route('dohovir_oferty') }}" class="hover:text-[#FF6B4A] transition-colors">Оферта</a>
                </div>
            </div>
        </div>
    </div>
</footer>
