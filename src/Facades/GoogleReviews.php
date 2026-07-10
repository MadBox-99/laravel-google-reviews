<?php

declare(strict_types=1);

namespace Madbox99\GoogleReviews\Facades;

use Madbox99\GoogleReviews\Data\PlaceData;
use Madbox99\GoogleReviews\GoogleReviewsManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static PlaceData refresh(?string $placeId = null)
 * @method static PlaceData|null for(?string $placeId = null)
 *
 * @see GoogleReviewsManager
 */
class GoogleReviews extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GoogleReviewsManager::class;
    }
}
