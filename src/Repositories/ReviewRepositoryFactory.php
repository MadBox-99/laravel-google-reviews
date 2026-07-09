<?php

namespace Cegem360\GoogleReviews\Repositories;

use Cegem360\GoogleReviews\Contracts\ReviewRepository;
use Cegem360\GoogleReviews\Settings\SettingsResolver;

class ReviewRepositoryFactory
{
    public function __construct(private readonly SettingsResolver $settings) {}

    public function make(): ReviewRepository
    {
        return match ($this->settings->driver()) {
            'cache' => new CacheReviewRepository($this->settings->cacheTtl()),
            default => new DatabaseReviewRepository,
        };
    }
}
