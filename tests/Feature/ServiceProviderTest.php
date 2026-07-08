<?php

use Cegem360\GoogleReviews\GoogleReviewsManager;
use Cegem360\GoogleReviews\Settings\SettingsResolver;

it('binds the manager and settings resolver as singletons', function () {
    expect(app(GoogleReviewsManager::class))->toBeInstanceOf(GoogleReviewsManager::class)
        ->and(app(SettingsResolver::class))->toBe(app(SettingsResolver::class));
});

it('merges the package config', function () {
    expect(config('google-reviews.driver'))->not->toBeNull();
});
