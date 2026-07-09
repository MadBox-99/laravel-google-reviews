<?php

declare(strict_types=1);

namespace Cegem360\GoogleReviews\Contracts;

use Cegem360\GoogleReviews\Data\PlaceData;

interface ReviewRepository
{
    public function store(PlaceData $place): void;

    public function get(string $placeId): ?PlaceData;
}
