<?php

declare(strict_types=1);

namespace Cegem360\GoogleReviews;

use Cegem360\GoogleReviews\Data\PlaceData;
use Cegem360\GoogleReviews\Http\GooglePlacesClient;
use Cegem360\GoogleReviews\Repositories\ReviewRepositoryFactory;
use Cegem360\GoogleReviews\Settings\SettingsResolver;
use RuntimeException;

class GoogleReviewsManager
{
    public function __construct(
        private readonly GooglePlacesClient $client,
        private readonly ReviewRepositoryFactory $factory,
        private readonly SettingsResolver $settings,
    ) {}

    public function refresh(?string $placeId = null): PlaceData
    {
        $id = $this->resolvePlaceId($placeId);
        $place = $this->client->fetch($id);
        $this->factory->make()->store($place);

        return $place;
    }

    public function for(?string $placeId = null): ?PlaceData
    {
        return $this->factory->make()->get($this->resolvePlaceId($placeId));
    }

    public function resolvePlaceId(?string $placeId): string
    {
        if ($placeId) {
            return $placeId;
        }

        $configured = $this->settings->placeId();
        if (is_array($configured)) {
            $configured = $configured[0] ?? null;
        }

        throw_unless($configured, RuntimeException::class, 'No Google place id configured.');

        return (string) $configured;
    }
}
