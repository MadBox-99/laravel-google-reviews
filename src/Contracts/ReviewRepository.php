<?php

declare(strict_types=1);

namespace Madbox99\GoogleReviews\Contracts;

use Madbox99\GoogleReviews\Data\PlaceData;

interface ReviewRepository
{
    public function store(PlaceData $place): void;

    public function get(string $placeId): ?PlaceData;
}
