<?php

declare(strict_types=1);

use Cegem360\GoogleReviews\Filament\GoogleReviewsPlugin;
use Filament\Contracts\Plugin;

it('exposes a stable plugin id', function (): void {
    // Filament\Contracts\Plugin is an interface, so interface_exists() (not
    // class_exists()) must be used here — class_exists() always returns
    // false for interfaces, which would make this test skip unconditionally
    // even when Filament is installed.
    if (! interface_exists(Plugin::class)) {
        $this->markTestSkipped('Filament not installed.');
    }

    expect(GoogleReviewsPlugin::make()->getId())->toBe('google-reviews');
});
