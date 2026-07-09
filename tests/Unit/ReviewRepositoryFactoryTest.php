<?php

use Cegem360\GoogleReviews\Repositories\CacheReviewRepository;
use Cegem360\GoogleReviews\Repositories\DatabaseReviewRepository;
use Cegem360\GoogleReviews\Repositories\ReviewRepositoryFactory;
use Cegem360\GoogleReviews\Settings\SettingsResolver;

it('builds the database repository by default', function (): void {
    config()->set('google-reviews.driver', 'database');

    $repo = (new ReviewRepositoryFactory(new SettingsResolver))->make();

    expect($repo)->toBeInstanceOf(DatabaseReviewRepository::class);
});

it('builds the cache repository when configured', function (): void {
    config()->set('google-reviews.driver', 'cache');

    $repo = (new ReviewRepositoryFactory(new SettingsResolver))->make();

    expect($repo)->toBeInstanceOf(CacheReviewRepository::class);
});
