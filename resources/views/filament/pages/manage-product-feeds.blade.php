<x-filament-panels::page>
    @php
        $meta = $this->getMeta();
        $generatedAt = ! empty($meta['generated_at'])
            ? \Illuminate\Support\Carbon::parse($meta['generated_at'])->timezone(config('app.timezone'))->format('d.m.Y H:i')
            : null;
    @endphp

    <div class="space-y-6">
        @if (! empty($meta['dirty']))
            <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-100">
                Есть изменения товаров — фиды помечены как устаревшие. Нажмите «Сгенерировать сейчас» или дождитесь фонового обновления.
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">Google Merchant Center</x-slot>
                <x-slot name="description">XML-фид для Google Shopping / Merchant Center</x-slot>

                <div class="space-y-3 text-sm">
                    <div>
                        <div class="mb-1 font-medium text-gray-700 dark:text-gray-200">URL фида</div>
                        <code class="block break-all rounded-lg bg-gray-100 px-3 py-2 text-xs dark:bg-gray-800">{{ $meta['google'] }}</code>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300">
                        В Merchant Center: Продукты → Фиды → добавить фид по ссылке (scheduled fetch).
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <x-filament::button tag="a" :href="$meta['google']" target="_blank" color="gray" size="sm">
                            Открыть XML
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Facebook / Meta Catalog</x-slot>
                <x-slot name="description">CSV-фид для каталога Commerce Manager</x-slot>

                <div class="space-y-3 text-sm">
                    <div>
                        <div class="mb-1 font-medium text-gray-700 dark:text-gray-200">URL фида</div>
                        <code class="block break-all rounded-lg bg-gray-100 px-3 py-2 text-xs dark:bg-gray-800">{{ $meta['facebook'] }}</code>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300">
                        В Meta Commerce Manager: Каталог → Источники данных → добавить URL CSV-фида.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <x-filament::button tag="a" :href="$meta['facebook']" target="_blank" color="gray" size="sm">
                            Открыть CSV
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">Статус</x-slot>
            <dl class="grid gap-4 sm:grid-cols-3 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Товаров в фиде</dt>
                    <dd class="mt-1 text-lg font-semibold">{{ (int) ($meta['count'] ?? 0) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Последняя генерация</dt>
                    <dd class="mt-1 text-lg font-semibold">{{ $generatedAt ?? 'ещё не создавался' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Автообновление</dt>
                    <dd class="mt-1 text-lg font-semibold">при создании / изменении / удалении товара</dd>
                </div>
            </dl>
            <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">
                В фид попадают товары с названием, ценой &gt; 0 и главным фото. Наличие и скидка берутся из карточки товара.
            </p>
        </x-filament::section>
    </div>
</x-filament-panels::page>
