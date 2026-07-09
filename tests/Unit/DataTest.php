<?php

use Cegem360\GoogleReviews\Data\PlaceData;
use Cegem360\GoogleReviews\Data\ReviewData;

it('round-trips review data through array', function (): void {
    $review = new ReviewData(
        googleReviewId: 'abc',
        authorName: 'Jane',
        authorPhotoUrl: 'https://x/y.jpg',
        rating: 5,
        text: 'Great',
        language: 'en',
        publishedAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
    );

    $restored = ReviewData::fromArray($review->toArray());

    expect($restored->authorName)->toBe('Jane')
        ->and($restored->rating)->toBe(5)
        ->and($restored->publishedAt?->format('c'))->toBe('2026-01-01T10:00:00+00:00');
});

it('round-trips place data with nested reviews', function (): void {
    $place = new PlaceData(
        placeId: 'place-1',
        name: 'Acme',
        rating: 4.5,
        userRatingsTotal: 120,
        reviews: [new ReviewData('r1', 'Bob', null, 4, 'ok', 'hu', null)],
    );

    $restored = PlaceData::fromArray($place->toArray());

    expect($restored->rating)->toBe(4.5)
        ->and($restored->reviews)->toHaveCount(1)
        ->and($restored->reviews[0])->toBeInstanceOf(ReviewData::class)
        ->and($restored->reviews[0]->authorName)->toBe('Bob');
});
