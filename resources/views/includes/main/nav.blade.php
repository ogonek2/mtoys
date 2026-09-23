<header class="site-header" id="siteHeader">
    {{-- Utility links --}}
    <div class="site-header__utility">
        <div class="site-header__utility-inner max-w-site mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="site-header__utility-nav" aria-label="Додаткова навігація">
                <a href="{{ route('pro_kompaniiu') }}" class="site-header__utility-link">Про нас</a>
                <a href="{{ route('oplata_i_dostavka') }}" class="site-header__utility-link">Доставка і оплата</a>
                <a href="{{ route('obmin_ta_povernennia') }}" class="site-header__utility-link">Обмін та повернення</a>
                <a href="{{ route('kontaktna_informatsiia') }}" class="site-header__utility-link">Контакти</a>
            </nav>
            <button type="button" data-callback-open class="site-header__callback">
                <x-lucide-icon name="headphones" width="14" />
                Замовити дзвінок
            </button>
        </div>
    </div>

    {{-- Main row --}}
    <div class="site-header__main">
        <div class="site-header__main-inner max-w-site mx-auto px-4 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="site-header__logo group">
                <img src="{{ asset('storage/src/logo.svg') }}" alt="Mtoys"
                     class="site-header__logo-img group-hover:opacity-90 transition-opacity">
                <div class="site-header__logo-text">
                    <span class="site-header__logo-name">
                        M<span class="site-header__logo-accent">toys</span>
                    </span>
                    <span class="site-header__logo-tagline">Іграшки для гри і подарунків</span>
                </div>
            </a>

            <div class="site-header__catalog-wrap mega-menu-trigger" id="megaMenuTrigger">
                <button type="button" id="megaMenuBtn" class="site-header__catalog-btn"
                        aria-expanded="false" aria-controls="megaMenu">
                    <x-lucide-icon name="layout-grid" width="18" />
                    <span>Каталог</span>
                    <x-lucide-icon name="chevron-down" width="16" class="mega-menu-trigger__chevron" />
                </button>
            </div>

            <div class="site-header__actions">
                <a href="tel:0636310041" class="site-header__phone">
                    <span class="site-header__phone-icon">
                        <x-lucide-icon name="phone" width="20" />
                    </span>
                    <span class="site-header__phone-body">
                        <span class="site-header__phone-number">063 63 100 41</span>
                        <span class="site-header__phone-label">Зателефонуйте нам</span>
                    </span>
                </a>

                <div class="site-header__action-group">
                    <div class="header-search-anchor" id="headerSearchAnchor">
                        <button type="button" data-nav-action="search-open" class="nav-action nav-action--search-open" aria-label="Пошук">
                            <span class="nav-action__icon">
                                <x-lucide-icon name="search" width="22" class="nav-action__icon-search" />
                            </span>
                            <span class="nav-action__label">Пошук</span>
                        </button>

                        <div class="site-header__search-bar" id="headerSearchBar" aria-hidden="true">
                            <div id="headerSearchMount" class="header-search"></div>
                            <button type="button" class="header-search__close" data-nav-action="search-close" aria-label="Закрити пошук">
                                <x-lucide-icon name="x" width="22" />
                            </button>
                        </div>
                    </div>

                    <wishlist-button labeled></wishlist-button>
                    <open-cart-button labeled></open-cart-button>

                    <button type="button" class="nav-action nav-action--menu lg:hidden"
                            data-nav-action="mobile-menu-toggle" aria-label="Меню">
                        <span class="nav-action__icon">
                            <x-lucide-icon name="menu" width="22" />
                        </span>
                        <span class="nav-action__label">Меню</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('includes.main.mega-menu')
</header>

<div id="searchResultsPanel" class="search-results-panel" aria-hidden="true"></div>

<div id="mobileMenuOverlay" class="mobile-menu-overlay lg:hidden" data-nav-action="mobile-menu-close" aria-hidden="true"></div>

<aside id="mobileMenu" class="mobile-menu lg:hidden" aria-hidden="true" aria-label="Меню сайту">
    <div class="mobile-menu__header">
        <a href="{{ url('/') }}" class="mobile-menu__brand">
            <img src="{{ asset('storage/src/logo.svg') }}" alt="Mtoys" class="mobile-menu__logo">
            <div class="mobile-menu__brand-text">
                <span class="mobile-menu__brand-name">M<span>toys</span></span>
                <span class="mobile-menu__brand-tag">Іграшки для гри і подарунків</span>
            </div>
        </a>
        <button type="button" class="mobile-menu__close" data-nav-action="mobile-menu-close" aria-label="Закрити меню">
            <x-lucide-icon name="x" width="22" />
        </button>
    </div>

    <nav class="mobile-menu__body">
        <p class="mobile-menu__section">Каталог</p>
        <a href="{{ route('catalog') }}" class="mobile-menu__link mobile-menu__link--accent">
            <span class="mobile-menu__link-icon"><x-lucide-icon name="layout-grid" width="18" /></span>
            <span class="mobile-menu__link-text">Весь каталог</span>
            <x-lucide-icon name="chevron-right" width="16" class="mobile-menu__link-arrow" />
        </a>

        <p class="mobile-menu__section">Інформація</p>
        <a href="{{ route('pro_kompaniiu') }}" class="mobile-menu__link">
            <span class="mobile-menu__link-icon"><x-lucide-icon name="info" width="18" /></span>
            <span class="mobile-menu__link-text">Про нас</span>
        </a>
        <a href="{{ route('oplata_i_dostavka') }}" class="mobile-menu__link">
            <span class="mobile-menu__link-icon"><x-lucide-icon name="truck" width="18" /></span>
            <span class="mobile-menu__link-text">Доставка і оплата</span>
        </a>
        <a href="{{ route('obmin_ta_povernennia') }}" class="mobile-menu__link">
            <span class="mobile-menu__link-icon"><x-lucide-icon name="rotate-ccw" width="18" /></span>
            <span class="mobile-menu__link-text">Обмін та повернення</span>
        </a>
        <a href="{{ route('kontaktna_informatsiia') }}" class="mobile-menu__link">
            <span class="mobile-menu__link-icon"><x-lucide-icon name="phone" width="18" /></span>
            <span class="mobile-menu__link-text">Контакти</span>
        </a>

        <p class="mobile-menu__section">Покупки</p>
        <a href="{{ route('wishlist') }}" class="mobile-menu__link">
            <span class="mobile-menu__link-icon"><x-lucide-icon name="heart" width="18" /></span>
            <span class="mobile-menu__link-text">Обране</span>
        </a>
        <a href="{{ route('cart') }}" class="mobile-menu__link">
            <span class="mobile-menu__link-icon"><x-lucide-icon name="shopping-cart" width="18" /></span>
            <span class="mobile-menu__link-text">Кошик</span>
        </a>
    </nav>

    <div class="mobile-menu__footer">
        <a href="tel:0636310041" class="mobile-menu__phone">
            <span class="mobile-menu__phone-icon"><x-lucide-icon name="phone" width="20" /></span>
            <span class="mobile-menu__phone-body">
                <span class="mobile-menu__phone-number">063 63 100 41</span>
                <span class="mobile-menu__phone-label">Зателефонуйте нам</span>
            </span>
        </a>
        <button type="button" data-callback-open class="mobile-menu__callback">
            <x-lucide-icon name="headphones" width="18" />
            Замовити дзвінок
        </button>
    </div>
</aside>
