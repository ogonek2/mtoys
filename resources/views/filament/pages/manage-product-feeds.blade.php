<x-filament-panels::page>
    @php
        $meta = $this->getMeta();
        $generatedAt = ! empty($meta['generated_at'])
            ? \Illuminate\Support\Carbon::parse($meta['generated_at'])->timezone(config('app.timezone'))->format('d.m.Y H:i')
            : null;
        $reasons = $meta['excluded_reasons'] ?? [];
        $reasonLabels = [
            'no_name' => 'без названия',
            'no_price' => 'без цены',
            'no_image' => 'без фото',
            'no_url' => 'без URL',
            'duplicate_id' => 'дубликат id',
        ];
    @endphp

    <div class="fi-fo-component-ctn grid gap-6">
        @if (! empty($meta['dirty']))
            <div class="rounded-xl border border-warning-300 bg-warning-50 px-4 py-3 text-sm text-warning-900 dark:border-warning-600 dark:bg-warning-950 dark:text-warning-100">
                Каталог изменился после последней генерации. Нажмите «Сгенерировать сейчас» или дождитесь автообновления.
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-sm text-gray-500 dark:text-gray-400">В фиде</div>
                <div class="mt-1 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ (int) ($meta['count'] ?? 0) }}</div>
                <div class="mt-1 text-xs text-gray-500">из {{ (int) ($meta['catalog_total'] ?? 0) }} в каталоге</div>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-sm text-gray-500 dark:text-gray-400">Исключено</div>
                <div class="mt-1 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ (int) ($meta['excluded'] ?? 0) }}</div>
                <div class="mt-1 text-xs text-gray-500">
                    @php
                        $parts = [];
                        foreach ($reasonLabels as $key => $label) {
                            $n = (int) ($reasons[$key] ?? 0);
                            if ($n > 0) {
                                $parts[] = "{$label}: {$n}";
                            }
                        }
                    @endphp
                    {{ $parts === [] ? 'все подходящие товары включены' : implode(' · ', $parts) }}
                </div>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-sm text-gray-500 dark:text-gray-400">Последняя генерация</div>
                <div class="mt-1 text-xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $generatedAt ?? '—' }}</div>
                <div class="mt-1 text-xs text-gray-500">автообновление при изменении товаров</div>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-sm text-gray-500 dark:text-gray-400">Файлы</div>
                <div class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                    Google: {{ ! empty($meta['google_exists']) ? number_format(((int) ($meta['google_bytes'] ?? 0)) / 1024, 1) . ' KB' : 'нет' }}
                </div>
                <div class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                    Facebook: {{ ! empty($meta['facebook_exists']) ? number_format(((int) ($meta['facebook_bytes'] ?? 0)) / 1024, 1) . ' KB' : 'нет' }}
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-gray-950 dark:text-white">Google Merchant Center</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">XML-фид (RSS 2.0 + namespace g:)</p>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">XML</span>
                </div>

                <label class="mt-5 block text-sm font-medium text-gray-700 dark:text-gray-200">URL фида</label>
                <div class="mt-1.5 flex gap-2" x-data="{ copied: false }">
                    <input
                        type="text"
                        readonly
                        value="{{ $meta['google'] }}"
                        class="fi-input block w-full rounded-lg border-none bg-gray-50 px-3 py-2 text-sm text-gray-950 ring-1 ring-gray-950/10 dark:bg-white/5 dark:text-white dark:ring-white/10"
                        x-ref="googleUrl"
                    />
                    <x-filament::button
                        color="gray"
                        size="sm"
                        x-on:click="navigator.clipboard.writeText($refs.googleUrl.value); copied = true; setTimeout(() => copied = false, 1500)"
                    >
                        <span x-text="copied ? 'Скопировано' : 'Копировать'">Копировать</span>
                    </x-filament::button>
                </div>

                <ol class="mt-4 list-decimal space-y-1 pl-5 text-sm text-gray-600 dark:text-gray-300">
                    <li>Merchant Center → Товары → Фиды</li>
                    <li>Добавить фид по ссылке (scheduled fetch)</li>
                    <li>Вставить URL выше, страна UA, язык uk</li>
                </ol>
            </section>

            <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-gray-950 dark:text-white">Facebook / Meta Catalog</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">CSV UTF-8 для Commerce Manager</p>
                    </div>
                    <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700 dark:bg-sky-950 dark:text-sky-300">CSV</span>
                </div>

                <label class="mt-5 block text-sm font-medium text-gray-700 dark:text-gray-200">URL фида</label>
                <div class="mt-1.5 flex gap-2" x-data="{ copied: false }">
                    <input
                        type="text"
                        readonly
                        value="{{ $meta['facebook'] }}"
                        class="fi-input block w-full rounded-lg border-none bg-gray-50 px-3 py-2 text-sm text-gray-950 ring-1 ring-gray-950/10 dark:bg-white/5 dark:text-white dark:ring-white/10"
                        x-ref="facebookUrl"
                    />
                    <x-filament::button
                        color="gray"
                        size="sm"
                        x-on:click="navigator.clipboard.writeText($refs.facebookUrl.value); copied = true; setTimeout(() => copied = false, 1500)"
                    >
                        <span x-text="copied ? 'Скопировано' : 'Копировать'">Копировать</span>
                    </x-filament::button>
                </div>

                <ol class="mt-4 list-decimal space-y-1 pl-5 text-sm text-gray-600 dark:text-gray-300">
                    <li>Meta Commerce Manager → Каталог</li>
                    <li>Источники данных → Добавить URL-фид</li>
                    <li>Вставить CSV-ссылку, расписание обновления</li>
                </ol>
            </section>
        </div>

        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Что попадает в фид</h2>
            <div class="mt-3 grid gap-3 text-sm text-gray-600 dark:text-gray-300 md:grid-cols-2">
                <ul class="list-disc space-y-1 pl-5">
                    <li>Название, цена &gt; 0, хотя бы одно фото (главное или из галереи)</li>
                    <li>Стабильный <code>id</code> = ID товара (без дублей по артикулу)</li>
                    <li>Артикул → <code>mpn</code>, иначе <code>identifier_exists=no</code></li>
                </ul>
                <ul class="list-disc space-y-1 pl-5">
                    <li>Цена и скидка в UAH, наличие из карточки</li>
                    <li>Картинки через CDN / абсолютный HTTPS URL</li>
                    <li>Автопересборка после сохранения, импорта и удаления</li>
                </ul>
            </div>
        </section>
    </div>
</x-filament-panels::page>
