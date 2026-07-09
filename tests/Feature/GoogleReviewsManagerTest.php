<?php

use Cegem360\GoogleReviews\GoogleReviewsManager;
use Cegem360\GoogleReviews\Http\GooglePlacesClient;
use Cegem360\GoogleReviews\Repositories\ReviewRepositoryFactory;
use Cegem360\GoogleReviews\Settings\SettingsResolver;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    config()->set('google-reviews.api_key', 'test-key');
    config()->set('google-reviews.driver', 'database');
    config()->set('google-reviews.place_id', 'ChIJ-place-123');

    $body = json_decode(file_get_contents(__DIR__.'/../Fixtures/place-details.json'), true);
    Http::fake(['places.googleapis.com/*' => Http::response($body, 200)]);
});

function makeManager(): GoogleReviewsManager
{
    $settings = new SettingsResolver;

    return new GoogleReviewsManager(
        new GooglePlacesClient($settings),
        new ReviewRepositoryFactory($settings),
        $settings,
    );
}

it('refreshes from the api and stores, then reads back', function (): void {
    $manager = makeManager();

    $fetched = $manager->refresh();
    expect($fetched->reviews)->toHaveCount(2);

    $stored = $manager->for();
    expect($stored)->not->toBeNull()
        ->and($stored->name)->toBe('Acme Kft.')
        ->and($stored->reviews)->toHaveCount(2);
});

it('resolves place id from an explicit argument over settings', function (): void {
    $manager = makeManager();

    $fetched = $manager->refresh('ChIJ-place-123');
    expect($fetched->placeId)->toBe('ChIJ-place-123');
});
