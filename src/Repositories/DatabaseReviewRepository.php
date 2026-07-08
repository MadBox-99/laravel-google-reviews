<?php

namespace Cegem360\GoogleReviews\Repositories;

use Cegem360\GoogleReviews\Contracts\ReviewRepository;
use Cegem360\GoogleReviews\Data\PlaceData;
use Cegem360\GoogleReviews\Data\ReviewData;
use Cegem360\GoogleReviews\Models\GoogleReview;
use Cegem360\GoogleReviews\Models\GoogleReviewPlace;
use DateTimeImmutable;

class DatabaseReviewRepository implements ReviewRepository
{
    public function store(PlaceData $place): void
    {
        GoogleReviewPlace::updateOrCreate(
            ['place_id' => $place->placeId],
            [
                'name' => $place->name,
                'rating' => $place->rating,
                'user_ratings_total' => $place->userRatingsTotal,
                'synced_at' => now(),
            ],
        );

        foreach ($place->reviews as $review) {
            GoogleReview::updateOrCreate(
                ['place_id' => $place->placeId, 'google_review_id' => $review->googleReviewId],
                [
                    'author_name' => $review->authorName,
                    'author_photo_url' => $review->authorPhotoUrl,
                    'rating' => $review->rating,
                    'text' => $review->text,
                    'language' => $review->language,
                    'published_at' => $review->publishedAt,
                    'raw' => $review->toArray(),
                ],
            );
        }
    }

    public function get(string $placeId): ?PlaceData
    {
        $placeModel = GoogleReviewPlace::where('place_id', $placeId)->first();
        if (! $placeModel) {
            return null;
        }

        $reviews = GoogleReview::where('place_id', $placeId)
            ->where('visible', true)
            ->orderByDesc('published_at')
            ->get()
            ->map(fn (GoogleReview $r) => new ReviewData(
                googleReviewId: $r->google_review_id,
                authorName: $r->author_name,
                authorPhotoUrl: $r->author_photo_url,
                rating: (int) $r->rating,
                text: (string) $r->text,
                language: $r->language,
                publishedAt: $r->published_at
                    ? new DateTimeImmutable($r->published_at->toIso8601String())
                    : null,
            ))
            ->all();

        return new PlaceData(
            placeId: $placeModel->place_id,
            name: $placeModel->name,
            rating: $placeModel->rating,
            userRatingsTotal: $placeModel->user_ratings_total,
            reviews: $reviews,
        );
    }
}
