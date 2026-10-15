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

    public function mount(): void
    {
        $this->fillFromMeta();
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
                Callout::make('Фиды устарели')
                    ->description('Каталог изменился. Нажмите «Сгенерировать сейчас».')
                    ->warning()
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->visible(fn (): bool => (bool) ($this->data['dirty'] ?? false)),

                Section::make('Статус')
                    ->columns(4)
                    ->schema([
                        TextInput::make('count')
                            ->label('В фиде')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('catalog_total')
                            ->label('Просмотрено в каталоге')
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
                            ->description('XML-фид RSS 2.0')
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
                            ->description('CSV UTF-8')
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

                Section::make('Правила')
                    ->collapsed()
                    ->schema([
                        Text::make('Кнопка читает товары из БД порциями по 100 и сразу пишет Google XML + Facebook CSV. Без фоновых процессов и пагинации в браузере.')
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
            Action::make('generate')
                ->label('Сгенерировать сейчас')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Собрать фиды?')
                ->modalDescription('Прямая выгрузка из БД в XML/CSV. Обычно 15–40 секунд — подождите, не закрывайте вкладку.')
                ->successNotification(null)
                ->action(function (): void {
                    @set_time_limit(0);
                    @ini_set('max_execution_time', '0');

                    try {
                        $meta = ProductFeedService::generate();
                    } catch (Throwable $e) {
                        Log::channel('single')->error('product-feeds: admin generate failed', [
                            'message' => $e->getMessage(),
                        ]);
                        Notification::make()
                            ->title('Ошибка генерации')
                            ->body(Str::limit($e->getMessage() ?: $e::class, 400))
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    $this->fillFromMeta($meta);

                    Notification::make()
                        ->title('Фиды обновлены')
                        ->body("В фиде: {$meta['count']} из {$meta['catalog_total']} товаров")
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function copyUrl(string $type): void
    {
        $url = ProductFeedService::publicUrl($type);
        $this->js('window.navigator.clipboard.writeText('.Js::from($url).')');
        Notification::make()->title('URL скопирован')->body($url)->success()->send();
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    protected function fillFromMeta(?array $meta = null): void
    {
        $meta ??= ProductFeedService::meta();

        $generatedAt = ! empty($meta['generated_at'])
            ? \Illuminate\Support\Carbon::parse($meta['generated_at'])
                ->timezone(config('app.timezone'))
                ->format('d.m.Y H:i')
            : null;

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
            'Размер: Google %s · Facebook %s',
            $g > 0 ? number_format($g / 1024, 1).' KB' : 'нет',
            $f > 0 ? number_format($f / 1024, 1).' KB' : 'нет',
        );
    }
}
