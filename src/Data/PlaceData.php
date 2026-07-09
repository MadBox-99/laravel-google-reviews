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

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'place_id' => $this->placeId,
            'name' => $this->name,
            'rating' => $this->rating,
            'user_ratings_total' => $this->userRatingsTotal,
            'reviews' => array_map(fn (ReviewData $r): array => $r->toArray(), $this->reviews),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            placeId: (string) $data['place_id'],
            name: $data['name'] ?? null,
            rating: isset($data['rating']) ? (float) $data['rating'] : null,
            userRatingsTotal: isset($data['user_ratings_total']) ? (int) $data['user_ratings_total'] : null,
            reviews: array_map(
                ReviewData::fromArray(...),
                $data['reviews'] ?? [],
            ),
        );
    }
}
