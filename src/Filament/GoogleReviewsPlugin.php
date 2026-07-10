<?php

namespace Madbox99\GoogleReviews\Filament;

use Madbox99\GoogleReviews\Filament\Pages\GoogleReviewsSettings;
use Filament\Contracts\Plugin;
use Filament\Panel;

class GoogleReviewsPlugin implements Plugin
{
    public static function make(): static
    {
        return resolve(static::class);
    }

    public function getId(): string
    {
        return 'google-reviews';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            GoogleReviewsSettings::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
