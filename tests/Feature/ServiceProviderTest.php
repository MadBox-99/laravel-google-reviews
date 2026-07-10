<?php

use Madbox99\GoogleReviews\GoogleReviewsManager;
use Madbox99\GoogleReviews\Settings\SettingsResolver;

it('binds the manager and settings resolver as singletons', function (): void {
    expect(resolve(GoogleReviewsManager::class))->toBeInstanceOf(GoogleReviewsManager::class)
        ->and(resolve(SettingsResolver::class))->toBe(resolve(SettingsResolver::class));
});

it('merges the package config', function (): void {
    expect(config('google-reviews.driver'))->not->toBeNull();
});
