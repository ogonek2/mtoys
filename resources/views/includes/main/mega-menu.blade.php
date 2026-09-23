@php
    $megaMenuItems = $megaMenuItems ?? get_mega_menu_data();
    $megaAccent = ['#FF6B4A', '#2EC4B6', '#FFB703', '#0E3A45', '#E85A3C', '#1A9B8E'];
@endphp

<div id="megaMenu" class="mega-menu" aria-hidden="true">
    <div class="mega-menu__shell">
        <div class="mega-menu__inner">
            <div class="mega-menu__intro">
                <div class="mega-menu__intro-text">
                    <p class="mega-menu__intro-brand">M<span>toys</span> каталог</p>
                    <p class="mega-menu__intro-hint">Оберіть категорію іграшок</p>
                </div>
                <a href="{{ route('catalog') }}" class="mega-menu__intro-cta">
                    Усі товари
                    <x-lucide-icon name="arrow-right" width="14" />
                </a>
            </div>

            {{-- Mobile: horizontal category chips --}}
            <div class="mega-menu__chips" role="tablist" aria-label="Категорії">
                @foreach ($megaMenuItems as $index => $item)
                    <button type="button"
                            class="mega-menu__chip {{ $index === 0 ? 'mega-menu__chip--active' : '' }}"
                            data-mega-panel="mega-panel-{{ $item['category']->id }}"
                            role="tab"
                            aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                        <span class="mega-menu__chip-dot" style="--mega-dot: {{ $megaAccent[$index % count($megaAccent)] }}"></span>
                        {{ $item['category']->name }}
                        @if ($item['count'] > 0)
                            <span class="mega-menu__chip-count">{{ $item['count'] }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            <div class="mega-menu__body">
                <nav class="mega-menu__nav" aria-label="Каталог">
                    <ul class="mega-menu__cats" role="list">
                        @foreach ($megaMenuItems as $index => $item)
                            @php
                                $letter = mb_strtoupper(mb_substr($item['category']->name, 0, 1));
                                $dot = $megaAccent[$index % count($megaAccent)];
                            @endphp
                            <li>
                                <button type="button"
                                        class="mega-menu__cat {{ $index === 0 ? 'mega-menu__cat--active' : '' }}"
                                        data-mega-panel="mega-panel-{{ $item['category']->id }}"
                                        aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                                    <span class="mega-menu__cat-icon" style="--mega-dot: {{ $dot }}">{{ $letter }}</span>
                                    <span class="mega-menu__cat-name">{{ $item['category']->name }}</span>
                                    @if ($item['count'] > 0)
                                        <span class="mega-menu__cat-count">{{ $item['count'] }}</span>
                                    @endif
                                    <x-lucide-icon name="chevron-right" width="14" class="mega-menu__cat-arrow" />
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <div class="mega-menu__panels">
                    @foreach ($megaMenuItems as $index => $item)
                        @php
                            $subs = collect($item['children'] ?? []);
                            $deepSubs = $subs->flatMap(fn ($block) => $block['children'] ?? []);
                            $allSubs = $subs->concat($deepSubs)->values();

                            $panelProducts = $item['products'];
                            if ($panelProducts->isEmpty() && $subs->isNotEmpty()) {
                                $panelProducts = $subs
                                    ->flatMap(fn ($block) => $block['products'] ?? collect())
                                    ->concat($deepSubs->flatMap(fn ($block) => $block['products'] ?? collect()))
                                    ->unique(fn ($p) => $p->id)
                                    ->take(12)
                                    ->values();
                            }
                        @endphp

                        <div id="mega-panel-{{ $item['category']->id }}"
                             class="mega-menu__panel {{ $index === 0 ? 'mega-menu__panel--active' : '' }}"
                             role="tabpanel">
                            <div class="mega-menu__panel-head">
                                <a href="{{ route('catalog_category_page', $item['category']->url) }}"
                                   class="mega-menu__panel-title">
                                    {{ $item['category']->name }}
                                    <x-lucide-icon name="arrow-right" width="16" />
                                </a>
                                @if ($item['count'] > 0 || $panelProducts->isNotEmpty())
                                    <span class="mega-menu__panel-meta">
                                        {{ max($item['count'], $panelProducts->count()) }} товарів
                                    </span>
                                @endif
                            </div>

                            @if ($allSubs->isNotEmpty())
                                <p class="mega-menu__section-label">Підкатегорії</p>
                                <div class="mega-menu__subs">
                                    @foreach ($allSubs as $block)
                                        @include('includes.main.mega-menu-category', ['block' => $block])
                                    @endforeach
                                </div>
                            @endif

                            @if ($panelProducts->isNotEmpty())
                                <p class="mega-menu__section-label">Популярне в розділі</p>
                                <div class="mega-menu__products">
                                    @foreach ($panelProducts as $product)
                                        @include('includes.main.mega-menu-product', ['product' => $product])
                                    @endforeach
                                </div>
                                <a href="{{ route('catalog_category_page', $item['category']->url) }}"
                                   class="mega-menu__more">
                                    Дивитись усі
                                    <x-lucide-icon name="arrow-right" width="14" />
                                </a>
                            @elseif ($allSubs->isEmpty())
                                <p class="mega-menu__empty">Іграшки скоро з'являться</p>
                                <a href="{{ route('catalog_category_page', $item['category']->url) }}"
                                   class="mega-menu__more">
                                    Перейти до категорії
                                    <x-lucide-icon name="arrow-right" width="14" />
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mega-menu__footer">
                <a href="{{ route('catalog') }}" class="mega-menu__footer-link mega-menu__footer-link--accent">
                    <x-lucide-icon name="layout-grid" width="16" />
                    <span>Весь каталог</span>
                </a>
                <a href="{{ route('catalog') }}?discount=1" class="mega-menu__footer-link">
                    <x-lucide-icon name="tag" width="16" />
                    <span>Акції</span>
                </a>
                <a href="{{ route('catalog') }}?new=1" class="mega-menu__footer-link">
                    <x-lucide-icon name="star" width="16" />
                    <span>Новинки</span>
                </a>
                <a href="{{ route('catalog') }}?wholesale=1" class="mega-menu__footer-link">
                    <x-lucide-icon name="boxes" width="16" />
                    <span>Опт</span>
                </a>
            </div>
        </div>
    </div>
</div>

<div id="megaMenuOverlay" class="mega-menu-overlay" aria-hidden="true"></div>
