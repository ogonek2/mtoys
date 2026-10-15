<?php

namespace App\Filament\Pages;

use App\Services\ProductFeedService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Js;
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

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->fillFromMeta();
    }

    public function getSubheading(): ?string
    {
        return 'URL для Google Merchant и Facebook Catalog. Обновляются автоматически при изменении товаров.';
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Статус')
                ->columns(3)
                ->schema([
                    TextInput::make('count')->label('Товаров в фиде')->disabled()->dehydrated(false),
                    TextInput::make('generated_at')->label('Последнее обновление')->disabled()->dehydrated(false),
                    TextInput::make('mode')->label('Режим')->disabled()->dehydrated(false),
                ]),

            Grid::make(['default' => 1, 'lg' => 2])->schema([
                Section::make('Google Merchant')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->headerActions([
                        Action::make('copyGoogle')
                            ->label('Копировать')
                            ->icon(Heroicon::OutlinedClipboard)
                            ->color('gray')
                            ->action(fn () => $this->copyUrl('google')),
                    ])
                    ->schema([
                        TextInput::make('google')->label('URL')->disabled()->dehydrated(false),
                        Text::make('XML: /feeds/google.xml')->size(TextSize::Small)->color('gray'),
                    ]),
                Section::make('Facebook Catalog')
                    ->icon(Heroicon::OutlinedShoppingBag)
                    ->headerActions([
                        Action::make('copyFacebook')
                            ->label('Копировать')
                            ->icon(Heroicon::OutlinedClipboard)
                            ->color('gray')
                            ->action(fn () => $this->copyUrl('facebook')),
                    ])
                    ->schema([
                        TextInput::make('facebook')->label('URL')->disabled()->dehydrated(false),
                        Text::make('CSV: /feeds/facebook.csv')->size(TextSize::Small)->color('gray'),
                    ]),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])->id('form'),
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
            Action::make('refreshMeta')
                ->label('Обновить статус')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action(function (): void {
                    $this->fillFromMeta();
                    Notification::make()->title('Статус обновлён')->success()->send();
                }),
        ];
    }

    protected function copyUrl(string $type): void
    {
        $url = ProductFeedService::publicUrl($type);
        $this->js('window.navigator.clipboard.writeText('.Js::from($url).')');
        Notification::make()->title('Скопировано')->success()->send();
    }

    /** @param  array<string, mixed>|null  $meta */
    protected function fillFromMeta(?array $meta = null): void
    {
        $meta ??= ProductFeedService::meta();
        $at = ! empty($meta['generated_at'])
            ? \Illuminate\Support\Carbon::parse($meta['generated_at'])->timezone(config('app.timezone'))->format('d.m.Y H:i')
            : '—';

        $this->form->fill([
            'count' => (string) (int) ($meta['count'] ?? 0),
            'generated_at' => $at,
            'mode' => 'Авто (по товарам)',
            'google' => (string) ($meta['google'] ?? ''),
            'facebook' => (string) ($meta['facebook'] ?? ''),
        ]);
    }
}
