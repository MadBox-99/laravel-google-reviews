<?php

namespace Cegem360\GoogleReviews\Data;

final readonly class PlaceData
{
    /** @param ReviewData[] $reviews */
    public function __construct(
        public string $placeId,
        public ?string $name,
        public ?float $rating,
        public ?int $userRatingsTotal,
        public array $reviews,
    ) {}

    public function toArray(): array
    {
        return [
            'place_id' => $this->placeId,
            'name' => $this->name,
            'rating' => $this->rating,
            'user_ratings_total' => $this->userRatingsTotal,
            'reviews' => array_map(fn (ReviewData $r) => $r->toArray(), $this->reviews),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            placeId: (string) $data['place_id'],
            name: $data['name'] ?? null,
            rating: isset($data['rating']) ? (float) $data['rating'] : null,
            userRatingsTotal: isset($data['user_ratings_total']) ? (int) $data['user_ratings_total'] : null,
            reviews: array_map(
                fn (array $r) => ReviewData::fromArray($r),
                $data['reviews'] ?? [],
            ),
        );
    }
}
