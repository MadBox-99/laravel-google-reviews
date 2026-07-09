<?php

declare(strict_types=1);

namespace Cegem360\GoogleReviews\Facades;

use Cegem360\GoogleReviews\Data\PlaceData;
use Cegem360\GoogleReviews\GoogleReviewsManager;
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
