<?php

namespace Cegem360\GoogleReviews\Facades;

use Cegem360\GoogleReviews\GoogleReviewsManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Cegem360\GoogleReviews\Data\PlaceData refresh(?string $placeId = null)
 * @method static \Cegem360\GoogleReviews\Data\PlaceData|null for(?string $placeId = null)
 *
 * @see \Cegem360\GoogleReviews\GoogleReviewsManager
 */
class GoogleReviews extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GoogleReviewsManager::class;
    }
}
