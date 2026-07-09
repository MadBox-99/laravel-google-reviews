<?php

use Cegem360\GoogleReviews\Data\PlaceData;
use Cegem360\GoogleReviews\Data\ReviewData;
use Cegem360\GoogleReviews\Repositories\CacheReviewRepository;

it('stores and reads a place through the cache', function (): void {
    $place = new PlaceData('place-1', 'Acme', 4.2, 50, [
        new ReviewData('r1', 'Anna', null, 5, 'Nice', 'hu', null),
    ]);

    $repo = new CacheReviewRepository(ttl: 3600);
    $repo->store($place);

    $restored = $repo->get('place-1');

    expect($restored)->not->toBeNull()
        ->and($restored->name)->toBe('Acme')
        ->and($restored->reviews)->toHaveCount(1)
        ->and($restored->reviews[0]->authorName)->toBe('Anna');
});

it('returns null when nothing is cached', function (): void {
    expect((new CacheReviewRepository)->get('missing'))->toBeNull();
});
