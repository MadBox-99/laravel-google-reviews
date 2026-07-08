<?php

use Cegem360\GoogleReviews\Models\GoogleReview;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    config()->set('google-reviews.api_key', 'test-key');
    config()->set('google-reviews.driver', 'database');
    config()->set('google-reviews.place_id', 'ChIJ-place-123');

    $body = json_decode(file_get_contents(__DIR__.'/../Fixtures/place-details.json'), true);
    Http::fake(['places.googleapis.com/*' => Http::response($body, 200)]);
});

it('syncs configured place reviews into the database', function () {
    $this->artisan('google-reviews:sync')
        ->assertExitCode(0);

    expect(GoogleReview::where('place_id', 'ChIJ-place-123')->count())->toBe(2);
});

it('accepts an explicit place id argument', function () {
    $this->artisan('google-reviews:sync', ['place' => 'ChIJ-place-123'])
        ->assertExitCode(0);

    expect(GoogleReview::count())->toBe(2);
});

it('returns failure when a place cannot be synced', function () {
    config()->set('google-reviews.api_key', 'test-key');
    config()->set('google-reviews.driver', 'database');
    config()->set('google-reviews.place_id', 'ChIJ-place-123');

    // Reset the fake so the failing response is the only registered stub. Laravel
    // matches stubs in registration order (first match wins), so a second
    // Http::fake() for the same URL pattern would not override the beforeEach 200.
    Http::swap(new \Illuminate\Http\Client\Factory);
    Http::fake(['places.googleapis.com/*' => Http::response([], 500)]);

    $this->artisan('google-reviews:sync')->assertExitCode(1);
});
