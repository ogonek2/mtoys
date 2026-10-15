<?php

namespace App\Filament\Pages;

use App\Services\ProductFeedService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
use Throwable;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageProductFeeds extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRss;

    protected static string|UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?string $navigationLabel = 'Фиды товаров';

    protected static ?string $title = 'Товарные фиды';

    protected static ?int $navigationSort = 95;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public bool $isBuilding = false;

    public int $buildPercent = 0;

    public int $buildPage = 0;

    public int $buildPagesTotal = 0;

    public int $buildProcessed = 0;

    public int $buildTotal = 0;

    public int $buildIncluded = 0;

    public string $buildLabel = '';

    public function mount(): void
    {
        $this->fillFromMeta();
        $this->syncBuildFromCache();

        if ($this->isBuilding) {
            $this->startBuildPolling();
        }
    }

    public function getSubheading(): ?string
    {
        return 'Google Merchant Center и Facebook / Meta Catalog';
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Callout::make(fn (): string => 'Сборка фидов: '.$this->buildPercent.'%')
                    ->description(fn (): string => $this->buildLabel !== ''
                        ? $this->buildLabel
                        : 'Обработка каталога по страницам…')
                    ->info()
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (): bool => $this->isBuilding),

                Callout::make('Фиды устарели')
                    ->description('Каталог изменился после последней генерации. Запустите постраничную сборку.')
                    ->warning()
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->visible(fn (): bool => ! $this->isBuilding && (bool) ($this->data['dirty'] ?? false)),

                Section::make('Статус')
                    ->description('Количество товаров сверяется с XML на диске')
                    ->columns(4)
                    ->schema([
                        TextInput::make('count')
                            ->label('В фиде')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('catalog_total')
                            ->label('В каталоге')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('excluded')
                            ->label('Исключено')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText(fn (): ?string => $this->excludedHint()),
                        TextInput::make('generated_at')
                            ->label('Последняя генерация')
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Grid::make(['default' => 1, 'lg' => 2])
                    ->schema([
                        Section::make('Google Merchant Center')
                            ->description('XML-фид RSS 2.0 (namespace g:)')
                            ->icon(Heroicon::OutlinedGlobeAlt)
                            ->headerActions([
                                Action::make('copyGoogle')
                                    ->label('Копировать URL')
                                    ->icon(Heroicon::OutlinedClipboard)
                                    ->color('gray')
                                    ->action(fn () => $this->copyUrl('google')),
                            ])
                            ->schema([
                                TextInput::make('google')
                                    ->label('URL фида')
                                    ->disabled()
                                    ->dehydrated(false),
                                Text::make('Merchant Center → Товары → Фиды → добавить по ссылке. Страна UA, язык uk.')
                                    ->size(TextSize::Small)
                                    ->color('gray'),
                            ]),

                        Section::make('Facebook / Meta Catalog')
                            ->description('CSV UTF-8 для Commerce Manager')
                            ->icon(Heroicon::OutlinedShoppingBag)
                            ->headerActions([
                                Action::make('copyFacebook')
                                    ->label('Копировать URL')
                                    ->icon(Heroicon::OutlinedClipboard)
                                    ->color('gray')
                                    ->action(fn () => $this->copyUrl('facebook')),
                            ])
                            ->schema([
                                TextInput::make('facebook')
                                    ->label('URL фида')
                                    ->disabled()
                                    ->dehydrated(false),
                                Text::make('Commerce Manager → Каталог → Источники данных → URL-фид.')
                                    ->size(TextSize::Small)
                                    ->color('gray'),
                            ]),
                    ]),

                Section::make('Правила фида')
                    ->collapsed()
                    ->schema([
                        Text::make('Сборка идёт постранично (по '.ProductFeedService::PAGE_SIZE.' товаров × 2 страницы за тик), чтобы не упираться в таймаут PHP. ID товара стабильный, артикул → mpn.')
                            ->size(TextSize::Small)
                            ->color('gray'),
                        Text::make(fn (): string => $this->filesHint())
                            ->size(TextSize::Small)
                            ->color('gray'),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openGoogle')
                ->label('Google XML')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (): string => ProductFeedService::publicUrl('google'), shouldOpenInNewTab: true),
            Action::make('openFacebook')
                ->label('Facebook CSV')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (): string => ProductFeedService::publicUrl('facebook'), shouldOpenInNewTab: true),
            Action::make('cancelBuild')
                ->label('Остановить')
                ->icon(Heroicon::OutlinedXMark)
                ->color('danger')
                ->visible(fn (): bool => $this->isBuilding)
                ->requiresConfirmation()
                ->action(function (): void {
                    ProductFeedService::cancelBuild();
                    $this->isBuilding = false;
                    $this->buildLabel = '';
                    $this->js('clearInterval(window.__mtoysFeedBuildTimer)');
                    Notification::make()->title('Сборка остановлена')->warning()->send();
                }),
            Action::make('generate')
                ->label('Сгенерировать сейчас')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('primary')
                ->disabled(fn (): bool => $this->isBuilding)
                ->requiresConfirmation()
                ->modalHeading('Постраничная сборка фидов')
                ->modalDescription('Каталог обрабатывается порциями по '.ProductFeedService::PAGE_SIZE.' товаров. Прогресс — в блоке «Сборка фидов» на странице.')
                ->successNotification(null)
                ->action(fn () => $this->startPaginatedBuild()),
        ];
    }

    public function startPaginatedBuild(): void
    {
        try {
            $state = ProductFeedService::startBuild();
        } catch (Throwable $e) {
            Log::channel('single')->error('product-feeds: start failed', [
                'message' => $e->getMessage(),
            ]);
            Notification::make()
                ->title('Не удалось начать сборку')
                ->body(Str::limit($e->getMessage() ?: $e::class, 400))
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $this->applyBuildState($state);
        $this->isBuilding = true;

        Notification::make()
            ->title('Сборка запущена')
            ->body('Каталог обрабатывается по страницам — не закрывайте вкладку.')
            ->info()
            ->send();

        $this->startBuildPolling();
    }

    /**
     * @return bool true = сборка завершена (остановить poll)
     */
    public function tickBuild(): bool
    {
        if (! $this->isBuilding) {
            return true;
        }

        try {
            // По 1 странице за тик; финализация XML — отдельным тиком после pending_finalize.
            $state = ProductFeedService::processNextPages(1);
        } catch (Throwable $e) {
            $this->isBuilding = false;
            Log::channel('single')->error('product-feeds: tick failed', [
                'message' => $e->getMessage(),
            ]);
            Notification::make()
                ->title('Ошибка сборки фидов')
                ->body(Str::limit($e->getMessage() ?: $e::class, 400))
                ->danger()
                ->persistent()
                ->send();

            return true;
        }

        $this->applyBuildState($state);
        $status = (string) ($state['status'] ?? '');

        if ($status === 'done') {
            $this->isBuilding = false;
            $this->fillFromMeta();
            Notification::make()
                ->title('Фиды обновлены')
                ->body('В фиде: '.(int) ($state['included'] ?? 0).' из '.(int) ($state['total'] ?? 0).' товаров')
                ->success()
                ->send();

            return true;
        }

        if ($status === 'failed') {
            $this->isBuilding = false;
            Notification::make()
                ->title('Сборка прервалась')
                ->body(Str::limit((string) ($state['error'] ?? 'Неизвестная ошибка'), 400))
                ->danger()
                ->persistent()
                ->send();

            return true;
        }

        return false;
    }

    protected function startBuildPolling(): void
    {
        $this->js(<<<'JS'
            clearInterval(window.__mtoysFeedBuildTimer);
            window.__mtoysFeedBuildBusy = false;
            window.__mtoysFeedBuildTimer = setInterval(async () => {
                if (window.__mtoysFeedBuildBusy) {
                    return;
                }
                window.__mtoysFeedBuildBusy = true;
                try {
                    const done = await $wire.tickBuild();
                    if (done) {
                        clearInterval(window.__mtoysFeedBuildTimer);
                    }
                } catch (e) {
                    console.error(e);
                } finally {
                    window.__mtoysFeedBuildBusy = false;
                }
            }, 1000);
        JS);
    }

    protected function syncBuildFromCache(): void
    {
        $state = ProductFeedService::buildStatus();
        if ($state === null) {
            return;
        }

        $status = (string) ($state['status'] ?? '');
        if (in_array($status, ['running', 'pending_finalize', 'finalizing'], true)) {
            $this->isBuilding = true;
            $this->applyBuildState($state);
        }
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected function applyBuildState(array $state): void
    {
        $this->buildPercent = (int) ($state['percent'] ?? 0);
        $this->buildPage = (int) ($state['page'] ?? 0);
        $this->buildPagesTotal = (int) ($state['pages_total'] ?? 0);
        $this->buildProcessed = (int) ($state['processed'] ?? 0);
        $this->buildTotal = (int) ($state['total'] ?? 0);
        $this->buildIncluded = (int) ($state['included'] ?? 0);

        $status = (string) ($state['status'] ?? '');
        $this->buildLabel = match ($status) {
            'pending_finalize' => 'Каталог собран — следующий шаг: запись XML/CSV…',
            'finalizing' => 'Запись Google XML и Facebook CSV…',
            'done' => 'Готово',
            'failed' => 'Ошибка',
            default => sprintf(
                'Страница %d из %d · обработано %d / %d · в фид %d',
                $this->buildPage,
                max(1, $this->buildPagesTotal),
                $this->buildProcessed,
                $this->buildTotal,
                $this->buildIncluded,
            ),
        };
    }

    protected function copyUrl(string $type): void
    {
        $url = ProductFeedService::publicUrl($type);
        $this->js('window.navigator.clipboard.writeText('.Js::from($url).')');

        Notification::make()
            ->title('URL скопирован')
            ->body($url)
            ->success()
            ->send();
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    protected function fillFromMeta(?array $meta = null): void
    {
        $meta ??= ProductFeedService::meta();

        $generatedAt = null;
        if (! empty($meta['generated_at'])) {
            $generatedAt = \Illuminate\Support\Carbon::parse($meta['generated_at'])
                ->timezone(config('app.timezone'))
                ->format('d.m.Y H:i');
        }

        $this->form->fill([
            'count' => (string) ((int) ($meta['count'] ?? 0)),
            'catalog_total' => (string) ((int) ($meta['catalog_total'] ?? 0)),
            'excluded' => (string) ((int) ($meta['excluded'] ?? 0)),
            'generated_at' => $generatedAt ?? 'ещё не создавался',
            'google' => (string) ($meta['google'] ?? ''),
            'facebook' => (string) ($meta['facebook'] ?? ''),
            'dirty' => (bool) ($meta['dirty'] ?? false),
            'google_bytes' => (int) ($meta['google_bytes'] ?? 0),
            'facebook_bytes' => (int) ($meta['facebook_bytes'] ?? 0),
            'excluded_reasons' => $meta['excluded_reasons'] ?? [],
        ]);
    }

    protected function excludedHint(): ?string
    {
        $reasons = $this->data['excluded_reasons'] ?? [];
        if (! is_array($reasons) || $reasons === []) {
            return null;
        }

        $labels = [
            'no_name' => 'без названия',
            'no_price' => 'без цены',
            'no_image' => 'без фото',
            'no_url' => 'без URL',
            'duplicate_id' => 'дубликат id',
        ];

        $parts = [];
        foreach ($labels as $key => $label) {
            $n = (int) ($reasons[$key] ?? 0);
            if ($n > 0) {
                $parts[] = "{$label}: {$n}";
            }
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }

    protected function filesHint(): string
    {
        $g = (int) ($this->data['google_bytes'] ?? 0);
        $f = (int) ($this->data['facebook_bytes'] ?? 0);

        return sprintf(
            'Размер файлов: Google %s · Facebook %s',
            $g > 0 ? number_format($g / 1024, 1).' KB' : 'нет',
            $f > 0 ? number_format($f / 1024, 1).' KB' : 'нет',
        );
    }
}
