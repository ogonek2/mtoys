@extends('layouts.app')

@section('seo')
    <title>Результати пошуку: {{ $originalQuery }} — Mtoys</title>
    <meta name="description" content="Результати пошуку товарів за запитом «{{ $originalQuery }}» в інтернет-магазині Mtoys">
@endsection

@section('content')
@php
    $total = $getProducts->total();
    $hasResults = $getProducts->isNotEmpty();
    $currentPage = $getProducts->currentPage();
    $lastPage = $getProducts->lastPage();

    $visiblePages = [];
    if ($lastPage > 1) {
        $start = max(1, $currentPage - 2);
        $end = min($lastPage, $currentPage + 2);
        if ($start > 1) {
            $visiblePages[] = ['type' => 'page', 'num' => 1];
            if ($start > 2) {
                $visiblePages[] = ['type' => 'dots'];
            }
        }
        for ($page = $start; $page <= $end; $page++) {
            $visiblePages[] = [
                'type' => $page === $currentPage ? 'current' : 'page',
                'num' => $page,
            ];
        }
        if ($end < $lastPage) {
            if ($end < $lastPage - 1) {
                $visiblePages[] = ['type' => 'dots'];
            }
            $visiblePages[] = ['type' => 'page', 'num' => $lastPage];
        }
    }

    $paginationPayload = [
        'current_page' => $currentPage,
        'last_page' => $lastPage,
        'per_page' => $getProducts->perPage(),
        'total' => $total,
        'from' => $getProducts->firstItem(),
        'to' => $getProducts->lastItem(),
    ];
@endphp

<div class="bg-[#F2F7FA]">
    {{-- Breadcrumbs --}}
    <nav aria-label="Breadcrumb" class="border-b border-gray-200 bg-white">
        <div class="mx-auto max-w-site px-4 py-6 sm:px-6 lg:px-8">
            <ol class="flex flex-wrap items-center gap-1.5 text-[0.8125rem] text-slate-500">
                <li>
                    <a href="{{ url('/') }}" class="transition-colors hover:text-[#0E3A45]" aria-label="Головна">
                        <x-lucide-icon name="home" width="16" />
                    </a>
                </li>
                <li><x-lucide-icon name="chevron-right" width="14" class="text-gray-300" /></li>
                <li>
                    <span class="max-w-xs truncate font-medium text-[#1A2B36]" aria-current="page">Пошук</span>
                </li>
            </ol>
        </div>
    </nav>

    {{-- Search hero --}}
    <section class="border-b border-gray-200 bg-white">
        <div class="mx-auto max-w-site px-4 py-8 sm:px-6 md:py-10 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center border border-gray-200 bg-[#F2F7FA] text-[#0E3A45]">
                    <x-lucide-icon name="search" width="26" />
                </div>
                <h1 class="font-heading text-2xl font-bold text-[#0E3A45] md:text-3xl">
                    Результати пошуку
                </h1>
                <p class="mt-2 text-sm text-slate-500 md:text-base">
                    За запитом:
                    <span class="font-semibold text-[#1A2B36]">«{{ $originalQuery }}»</span>
                </p>

                @if ($hasResults)
                    <p class="mt-3 text-sm text-slate-500">
                        Знайдено
                        <strong class="font-bold text-[#0E3A45]">{{ $total }}</strong>
                        {{ $total === 1 ? 'товар' : 'товарів' }}
                    </p>
                @else
                    <p class="mt-3 text-sm text-slate-500">За вашим запитом нічого не знайдено</p>
                @endif

                @if ($usedAlternative && $query !== $originalQuery)
                    <div class="mt-4 inline-flex items-center gap-2 border border-[#FF6B4A]/40 bg-[#EEF8F7] px-4 py-2 text-sm text-[#1A2B36]">
                        <x-lucide-icon name="info" width="16" class="text-[#FF6B4A]" />
                        Показано результати для <strong class="ml-1">«{{ $query }}»</strong>
                    </div>
                @endif
            </div>

            {{-- Refine search --}}
            <form action="{{ route('search.index') }}" method="GET"
                  class="mx-auto mt-8 flex max-w-2xl flex-col gap-2 sm:flex-row sm:items-stretch">
                <label for="searchPageQuery" class="sr-only">Пошуковий запит</label>
                <div class="flex min-w-0 flex-1 items-center gap-2.5 border border-gray-300 bg-white px-3.5 focus-within:border-[#FF6B4A] focus-within:shadow-[0_0_0_2px_rgba(212,175,90,0.18)]">
                    <x-lucide-icon name="search" width="18" class="shrink-0 text-slate-400" />
                    <input id="searchPageQuery"
                           type="search"
                           name="q"
                           value="{{ $originalQuery }}"
                           autocomplete="off"
                           placeholder="Уточніть пошуковий запит..."
                           class="min-w-0 flex-1 border-0 bg-transparent py-3 text-sm text-[#1A2B36] outline-none placeholder:text-slate-400">
                </div>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 bg-[#FF6B4A] px-6 py-3 font-heading text-sm font-semibold text-[#1A2B36] transition-colors hover:bg-[#E85A3C]">
                    <x-lucide-icon name="search" width="16" />
                    Шукати
                </button>
            </form>
        </div>
    </section>

    {{-- Suggestions --}}
    @if (!empty($suggestions))
        <section class="border-b border-gray-200 bg-white py-5">
            <div class="mx-auto max-w-site px-4 sm:px-6 lg:px-8">
                <h2 class="mb-3 flex items-center gap-2 font-heading text-sm font-semibold text-[#0E3A45]">
                    <x-lucide-icon name="lightbulb" width="16" class="text-[#FF6B4A]" />
                    Можливо, ви мали на увазі:
                </h2>
                <div class="flex flex-wrap gap-2">
                    @foreach ($suggestions as $suggestion)
                        <a href="{{ url('/search?q=' . urlencode($suggestion)) }}"
                           class="inline-flex items-center border border-gray-300 bg-[#F2F7FA] px-3.5 py-2 text-sm text-[#1A2B36] transition-colors hover:border-[#0E3A45] hover:text-[#0E3A45]">
                            «{{ $suggestion }}»
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Main content --}}
    <section class="mx-auto max-w-site px-4 py-8 sm:px-6 md:py-12 lg:px-8">
        @if (!$hasResults)
            <div class="border border-gray-200 bg-white px-6 py-14 text-center sm:px-10">
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center border border-gray-200 bg-[#F2F7FA] text-gray-300">
                    <x-lucide-icon name="search-x" width="36" />
                </div>
                <h2 class="font-heading text-xl font-bold text-[#0E3A45] md:text-2xl">Нічого не знайдено</h2>
                <p class="mx-auto mt-3 max-w-md text-sm text-slate-500 md:text-base">
                    За запитом
                    <span class="font-semibold text-[#1A2B36]">«{{ $originalQuery }}»</span>
                    товари не знайдені. Спробуйте інші ключові слова або перегляньте каталог.
                </p>

                <div class="mx-auto mt-8 max-w-md border border-gray-200 bg-[#F2F7FA] p-5 text-left">
                    <h3 class="mb-3 flex items-center gap-2 font-heading text-sm font-semibold text-[#0E3A45]">
                        <x-lucide-icon name="circle-check" width="16" class="text-[#FF6B4A]" />
                        Поради щодо пошуку
                    </h3>
                    <ul class="space-y-2 text-sm text-slate-600">
                        <li class="flex items-start gap-2">
                            <x-lucide-icon name="check" width="14" class="mt-0.5 shrink-0 text-[#FF6B4A]" />
                            Перевірте правильність написання
                        </li>
                        <li class="flex items-start gap-2">
                            <x-lucide-icon name="check" width="14" class="mt-0.5 shrink-0 text-[#FF6B4A]" />
                            Спробуйте більш загальні слова
                        </li>
                        <li class="flex items-start gap-2">
                            <x-lucide-icon name="check" width="14" class="mt-0.5 shrink-0 text-[#FF6B4A]" />
                            Використовуйте синоніми або скорочення
                        </li>
                        <li class="flex items-start gap-2">
                            <x-lucide-icon name="check" width="14" class="mt-0.5 shrink-0 text-[#FF6B4A]" />
                            Переконайтесь, що всі слова написані без помилок
                        </li>
                    </ul>
                </div>

                <a href="{{ route('catalog') }}"
                   class="mt-8 inline-flex items-center gap-2 bg-[#0E3A45] px-6 py-3.5 font-heading text-sm font-semibold text-white transition-colors hover:bg-[#0a1830]">
                    <x-lucide-icon name="arrow-left" width="16" />
                    Перейти в каталог
                </a>
            </div>
        @else
            <div class="mb-5 flex flex-wrap items-center justify-between gap-4 border border-gray-200 bg-white px-5 py-4">
                <div>
                    <h2 class="font-heading text-base font-bold text-[#0E3A45]">Знайдені товари</h2>
                    <p class="mt-0.5 text-sm text-slate-500">
                        Сторінка {{ $currentPage }} з {{ $lastPage }}
                    </p>
                </div>
                <a href="{{ route('catalog') }}"
                   class="inline-flex items-center gap-2 border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-[#1A2B36] transition-colors hover:border-[#0E3A45] hover:text-[#0E3A45]">
                    <x-lucide-icon name="arrow-left" width="16" />
                    До каталогу
                </a>
            </div>

            <product-list
                data-products='@json($getProducts->items())'
                data-pagination='@json($paginationPayload)'>
            </product-list>

            @if ($getProducts->hasPages())
                <nav class="mt-8 border-t border-gray-200 pt-6" aria-label="Сторінки">
                    <div class="flex flex-wrap items-center justify-center gap-1.5">
                        @if ($getProducts->onFirstPage())
                            <span class="inline-flex h-9 min-w-9 cursor-not-allowed items-center justify-center border border-gray-200 bg-gray-50 px-2.5 text-gray-300">
                                <x-lucide-icon name="chevron-left" width="16" />
                            </span>
                        @else
                            <a href="{{ $getProducts->previousPageUrl() }}"
                               class="inline-flex h-9 min-w-9 items-center justify-center border border-gray-300 bg-white px-2.5 text-sm text-[#1A2B36] transition-colors hover:border-[#0E3A45] hover:text-[#0E3A45]"
                               aria-label="Попередня сторінка">
                                <x-lucide-icon name="chevron-left" width="16" />
                            </a>
                        @endif

                        @foreach ($visiblePages as $pageItem)
                            @if ($pageItem['type'] === 'dots')
                                <span class="px-1 text-sm text-gray-400">…</span>
                            @elseif ($pageItem['type'] === 'current')
                                <span class="inline-flex h-9 min-w-9 items-center justify-center border border-[#0E3A45] bg-[#0E3A45] px-2.5 text-sm font-bold text-white">
                                    {{ $pageItem['num'] }}
                                </span>
                            @else
                                <a href="{{ $getProducts->url($pageItem['num']) }}"
                                   class="inline-flex h-9 min-w-9 items-center justify-center border border-gray-300 bg-white px-2.5 text-sm text-[#1A2B36] transition-colors hover:border-[#0E3A45] hover:text-[#0E3A45]">
                                    {{ $pageItem['num'] }}
                                </a>
                            @endif
                        @endforeach

                        @if ($getProducts->hasMorePages())
                            <a href="{{ $getProducts->nextPageUrl() }}"
                               class="inline-flex h-9 min-w-9 items-center justify-center border border-gray-300 bg-white px-2.5 text-sm text-[#1A2B36] transition-colors hover:border-[#0E3A45] hover:text-[#0E3A45]"
                               aria-label="Наступна сторінка">
                                <x-lucide-icon name="chevron-right" width="16" />
                            </a>
                        @else
                            <span class="inline-flex h-9 min-w-9 cursor-not-allowed items-center justify-center border border-gray-200 bg-gray-50 px-2.5 text-gray-300">
                                <x-lucide-icon name="chevron-right" width="16" />
                            </span>
                        @endif
                    </div>

                    @if ($total > 0)
                        <p class="mt-4 text-center text-[0.8125rem] text-slate-500">
                            Показано {{ $getProducts->firstItem() }}–{{ $getProducts->lastItem() }} з {{ $total }} товарів
                        </p>
                    @endif
                </nav>
            @endif
        @endif
    </section>
</div>
@endsection
