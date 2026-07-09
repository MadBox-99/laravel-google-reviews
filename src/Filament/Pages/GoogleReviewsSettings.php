<?php

namespace Cegem360\GoogleReviews\Filament\Pages;

use BackedEnum;
use Cegem360\GoogleReviews\Settings\SettingsResolver;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class GoogleReviewsSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $title = 'Google Reviews';

    protected string $view = 'google-reviews::filament.pages.google-reviews-settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $settings = resolve(SettingsResolver::class);

        $this->form->fill([
            'place_id' => is_array($settings->placeId())
                ? implode(',', $settings->placeId())
                : $settings->placeId(),
            'driver' => $settings->driver(),
            'cache_ttl' => $settings->cacheTtl(),
            'language' => $settings->language(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('place_id')
                    ->label('Google Place ID')
                    ->helperText('Comma-separate multiple place ids.')
                    ->required(),
                Select::make('driver')
                    ->options(['database' => 'Database', 'cache' => 'Cache'])
                    ->required(),
                TextInput::make('cache_ttl')
                    ->label('Cache TTL (seconds)')
                    ->numeric()
                    ->default(86400),
                TextInput::make('language')
                    ->maxLength(5)
                    ->default('hu'),
                TextInput::make('api_key_note')
                    ->label('API key')
                    ->disabled()
                    ->default('Configured via GOOGLE_PLACES_API_KEY in .env'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $table = config('google-reviews.settings_table', 'google_review_settings');

        foreach (['place_id', 'driver', 'cache_ttl', 'language'] as $key) {
            $exists = DB::table($table)->where('key', $key)->exists();

            DB::table($table)->updateOrInsert(
                ['key' => $key],
                array_merge(
                    ['value' => (string) ($this->data[$key] ?? ''), 'updated_at' => now()],
                    $exists ? [] : ['created_at' => now()],
                ),
            );
        }

        Notification::make()->title('Saved')->success()->send();
    }
}
