<?php

use Cegem360\GoogleReviews\Data\PlaceData;
use Cegem360\GoogleReviews\Data\ReviewData;
use Cegem360\GoogleReviews\Models\GoogleReview;
use Cegem360\GoogleReviews\Repositories\DatabaseReviewRepository;

function samplePlace(): PlaceData
{
    return new PlaceData(
        placeId: 'place-1',
        name: 'Acme',
        rating: 4.5,
        userRatingsTotal: 100,
        reviews: [
            new ReviewData('r1', 'Anna', null, 5, 'Great', 'hu', new DateTimeImmutable('2026-01-01T00:00:00Z')),
            new ReviewData('r2', 'Bela', null, 4, 'Good', 'hu', null),
        ],
    );
}

it('stores and reads a place round-trip', function () {
    $repo = new DatabaseReviewRepository();
    $repo->store(samplePlace());

    $place = $repo->get('place-1');

    expect($place)->not->toBeNull()
        ->and($place->name)->toBe('Acme')
        ->and($place->rating)->toBe(4.5)
        ->and($place->reviews)->toHaveCount(2);
});

it('upserts instead of duplicating on repeated store', function () {
    $repo = new DatabaseReviewRepository();
    $repo->store(samplePlace());
    $repo->store(samplePlace());

    expect(GoogleReview::where('place_id', 'place-1')->count())->toBe(2);
});

it('hides reviews flagged not visible', function () {
    $repo = new DatabaseReviewRepository();
    $repo->store(samplePlace());
    GoogleReview::where('google_review_id', 'r2')->update(['visible' => false]);

    $place = $repo->get('place-1');

    expect($place->reviews)->toHaveCount(1)
        ->and($place->reviews[0]->googleReviewId)->toBe('r1');
});

it('returns null for an unknown place', function () {
    expect((new DatabaseReviewRepository())->get('nope'))->toBeNull();
});
