<?php

use Cegem360\GoogleReviews\Data\PlaceData;
use Cegem360\GoogleReviews\Data\ReviewData;
use Cegem360\GoogleReviews\GoogleReviewsManager;

it('renders the reviews component with place data', function (): void {
    $place = new PlaceData('place-1', 'Acme Kft.', 4.5, 100, [
        new ReviewData('r1', 'Kiss Anna', null, 5, 'Kiváló!', 'hu', null),
    ]);

    $this->mock(GoogleReviewsManager::class, function ($mock) use ($place): void {
        $mock->shouldReceive('for')->andReturn($place);
    });

    $html = view('google-reviews::components.google-reviews', ['placeId' => 'place-1'])->render();

    expect($html)->toContain('Acme Kft.')
        ->toContain('Kiss Anna')
        ->toContain('Kiváló!')
        ->toContain('4.5');
});

it('renders a graceful empty state when no place data exists', function (): void {
    $this->mock(GoogleReviewsManager::class, function ($mock): void {
        $mock->shouldReceive('for')->andReturn(null);
    });

    $html = view('google-reviews::components.google-reviews', ['placeId' => 'missing'])->render();

    expect($html)->toContain('google-reviews');
});
