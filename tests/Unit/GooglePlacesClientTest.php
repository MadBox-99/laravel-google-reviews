<?php

use Madbox99\GoogleReviews\Http\GooglePlacesClient;
use Madbox99\GoogleReviews\Settings\SettingsResolver;
use Illuminate\Support\Facades\Http;

it('maps the places api response into a PlaceData with reviews', function (): void {
    config()->set('google-reviews.api_key', 'test-key');

    $body = json_decode(file_get_contents(__DIR__.'/../Fixtures/place-details.json'), true);
    Http::fake([
        'places.googleapis.com/*' => Http::response($body, 200),
    ]);

    $client = new GooglePlacesClient(new SettingsResolver);
    $place = $client->fetch('ChIJ-place-123');

    expect($place->placeId)->toBe('ChIJ-place-123')
        ->and($place->name)->toBe('Acme Kft.')
        ->and($place->rating)->toBe(4.6)
        ->and($place->userRatingsTotal)->toBe(231)
        ->and($place->reviews)->toHaveCount(2)
        ->and($place->reviews[0]->authorName)->toBe('Kiss Anna')
        ->and($place->reviews[0]->authorPhotoUrl)->toBe('https://example.com/anna.jpg')
        ->and($place->reviews[0]->rating)->toBe(5)
        ->and($place->reviews[0]->text)->toBe('Kiváló szolgáltatás!')
        ->and($place->reviews[0]->publishedAt?->format('Y-m-d'))->toBe('2026-05-01');

    Http::assertSent(fn ($request) => $request->hasHeader('X-Goog-Api-Key', 'test-key'));
});

it('throws when the api key is missing', function (): void {
    config()->set('google-reviews.api_key');

    $client = new GooglePlacesClient(new SettingsResolver);

    $client->fetch('ChIJ-place-123');
})->throws(RuntimeException::class);

it('throws when the http request fails', function (): void {
    config()->set('google-reviews.api_key', 'test-key');
    Http::fake(['places.googleapis.com/*' => Http::response([], 500)]);

    $client = new GooglePlacesClient(new SettingsResolver);
    $client->fetch('ChIJ-place-123');
})->throws(RuntimeException::class);
