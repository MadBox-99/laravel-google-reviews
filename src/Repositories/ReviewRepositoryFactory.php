<?php

namespace Madbox99\GoogleReviews\Repositories;

use Madbox99\GoogleReviews\Contracts\ReviewRepository;
use Madbox99\GoogleReviews\Settings\SettingsResolver;

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
