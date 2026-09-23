@php
    use Filament\Support\Enums\Alignment;
    use Filament\Support\Icons\Heroicon;
    use Filament\Support\View\ComponentAttributeBag as FilamentComponentAttributeBag;
    use Filament\Support\View\Components\BadgeComponent;
@endphp

<div
    class="fi-product-import-tasks"
    @if ($pollingInterval)
        wire:poll.{{ $pollingInterval }}="tick"
    @endif
>
    <x-filament::modal
        :alignment="$imports->isEmpty() ? Alignment::Center : null"
        close-button
        :description="$imports->isEmpty() ? 'Когда поставите файл в очередь, порции появятся здесь — по мере выполнения.' : null"
        :heading="$imports->isEmpty() ? 'Задач пока нет' : null"
        :icon="$imports->isEmpty() ? Heroicon::OutlinedQueueList : null"
        icon-color="gray"
        id="product-import-tasks"
        slide-over
        :sticky-header="$imports->isNotEmpty()"
        teleport="body"
        width="md"
    >
        <x-slot name="trigger">
            <x-filament::icon-button
                :badge="$activeCount ?: null"
                :badge-color="$activeCount ? 'warning' : null"
                color="gray"
                :icon="Heroicon::OutlinedQueueList"
                icon-size="lg"
                label="Задачи импорта"
                class="fi-topbar-product-import-tasks-btn"
            />
        </x-slot>

        @if ($imports->isNotEmpty())
            <x-slot name="header">
                <div>
                    <h2 id="product-import-tasks.heading" class="fi-modal-heading">
                        Задачи импорта

                        @if ($activeCount)
                            <span
                                {{ (new FilamentComponentAttributeBag)->color(BadgeComponent::class, 'warning')->class(['fi-badge fi-size-xs']) }}
                            >
                                {{ $activeCount }}
                            </span>
                        @endif
                    </h2>

                    <p class="fi-modal-description">
                        Файл режется на порции, каждая уходит отдельной задачей.
                        @if ($pendingJobs > 0)
                            В очереди ещё {{ $pendingJobs }} {{ $pendingJobs === 1 ? 'задача' : 'задач' }}.
                        @endif
                    </p>
                </div>
            </x-slot>

            <div role="list" class="fi-product-import-tasks-list" style="display:flex;flex-direction:column;gap:0.75rem;">
                @foreach ($imports as $import)
                    <article
                        role="listitem"
                        wire:key="product-import-{{ $import->id }}"
                        class="fi-section fi-section-has-content rounded-xl border border-gray-200 p-4 dark:border-white/10"
                    >
                        <div style="display:flex;justify-content:space-between;gap:0.75rem;align-items:flex-start;">
                            <div style="min-width:0;">
                                <div class="fi-font-medium" style="word-break:break-word;">
                                    {{ $import->file_name }}
                                </div>
                                <div class="fi-fo-field-wrp-helper-text" style="margin-top:0.25rem;">
                                    {{ $import->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                                    · порции по {{ $import->chunk_size }} строк
                                </div>
                            </div>

                            <span
                                {{ (new FilamentComponentAttributeBag)->color(BadgeComponent::class, $import->statusColor())->class(['fi-badge fi-size-sm']) }}
                            >
                                {{ $import->statusLabel() }}
                            </span>
                        </div>

                        <div style="margin-top:0.75rem;">
                            <div
                                role="progressbar"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="{{ $import->progress() }}"
                                style="height:0.5rem;border-radius:999px;background:rgba(107,114,128,0.2);overflow:hidden;"
                            >
                                <div
                                    style="height:100%;width:{{ $import->progress() }}%;border-radius:999px;background:{{ $import->isActive() ? '#d97706' : ($import->status === 'failed' ? '#dc2626' : '#16a34a') }};transition:width 0.4s ease;"
                                ></div>
                            </div>
                            <div class="fi-fo-field-wrp-helper-text" style="margin-top:0.35rem;">
                                {{ $import->progress() }}% · {{ $import->summary() }}
                            </div>
                        </div>

                        @if ($import->problems)
                            <ul class="fi-fo-field-wrp-helper-text" style="margin-top:0.5rem;padding-left:1rem;list-style:disc;">
                                @foreach (array_slice($import->problems, 0, 4) as $problem)
                                    <li>{{ $problem }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="fi-ac" style="margin-top:0.75rem;">
                            @if ($import->isActive())
                                <x-filament::button
                                    color="danger"
                                    size="sm"
                                    outlined
                                    wire:click="cancelImport({{ $import->id }})"
                                    wire:confirm="Остановить импорт «{{ $import->file_name }}»? Уже обработанные товары останутся."
                                >
                                    Остановить
                                </x-filament::button>
                            @else
                                <x-filament::button
                                    color="gray"
                                    size="sm"
                                    outlined
                                    wire:click="dismiss({{ $import->id }})"
                                >
                                    Убрать
                                </x-filament::button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </x-filament::modal>
</div>
