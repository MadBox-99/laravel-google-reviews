<?php

namespace Cegem360\GoogleReviews\Repositories;

use Cegem360\GoogleReviews\Contracts\ReviewRepository;
use Cegem360\GoogleReviews\Data\PlaceData;
use Illuminate\Support\Facades\Cache;

class CacheReviewRepository implements ReviewRepository
{
    public function __construct(private int $ttl = 86400) {}

    public function store(PlaceData $place): void
    {
        Cache::put($this->key($place->placeId), $place->toArray(), $this->ttl);
    }

    public function get(string $placeId): ?PlaceData
    {
        $data = Cache::get($this->key($placeId));

        return $data ? PlaceData::fromArray($data) : null;
    }

    private function key(string $placeId): string
    {
        return "google-reviews:{$placeId}";
    }
}
