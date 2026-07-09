<?php

use Cegem360\GoogleReviews\Filament\GoogleReviewsPlugin;

it('exposes a stable plugin id', function () {
    // Filament\Contracts\Plugin is an interface, so interface_exists() (not
    // class_exists()) must be used here — class_exists() always returns
    // false for interfaces, which would make this test skip unconditionally
    // even when Filament is installed.
    if (! interface_exists(\Filament\Contracts\Plugin::class)) {
        $this->markTestSkipped('Filament not installed.');
    }

    expect(GoogleReviewsPlugin::make()->getId())->toBe('google-reviews');
});
